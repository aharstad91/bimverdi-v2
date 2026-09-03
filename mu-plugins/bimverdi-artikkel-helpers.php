<?php
/**
 * Plugin Name: BIM Verdi - Artikkel-hjelpere
 * Description: Avleder om en artikkel er en «deltakerartikkel» (tilskrevet et deltakerforetak), og deler artikler i deltaker/andre for nyhetsbrevet.
 * Version: 1.0.0
 *
 * Bakgrunn: Bård, Trello #347 punkt 7.1 og 9.1, ville ha en ny artikkeltype
 * «Deltaker-artikkel» ved siden av Fagartikkel/Case/Nyhet. Andreas avgjorde
 * 02.09.2026 (A1) at det ikke skal være en ny type: type-feltet er SJANGER, og
 * «fra deltaker» er noe annet — en egenskap ved hvem som står bak. To begreper
 * i samme nedtrekksliste ville tvunget redaksjonen til å velge mellom
 * «Fagartikkel» og «Deltakerartikkel» for en artikkel som er begge.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Metanøkkelen som tilskriver en artikkel et foretak.
 */
if (!defined('BIMVERDI_ARTIKKEL_FORETAK_META')) {
    define('BIMVERDI_ARTIKKEL_FORETAK_META', 'artikkel_bedrift');
}

/**
 * Foretaket som skal VISES for artikkelen (byline).
 *
 * Kjeden er den samme single-artikkel.php alltid har brukt: feltet på
 * artikkelen, ellers forfatterens foretak via det kanoniske trenøkkel-
 * oppslaget i access-control.
 *
 * MERK at denne IKKE brukes til å avgjøre om noe er en deltakerartikkel — se
 * bimverdi_artikkel_er_deltakerartikkel() for hvorfor. Den finnes her for
 * visningsformål, så maler slipper å gjenta kjeden.
 *
 * @param int  $post_id
 * @param bool $med_forfatter_fallback Ta med forfatterens foretak.
 * @return int Foretak-ID, eller 0.
 */
function bimverdi_artikkel_foretak_id($post_id, $med_forfatter_fallback = true) {
    $post_id = (int) $post_id;
    if (!$post_id) {
        return 0;
    }

    $bedrift = function_exists('get_field')
        ? get_field(BIMVERDI_ARTIKKEL_FORETAK_META, $post_id)
        : get_post_meta($post_id, BIMVERDI_ARTIKKEL_FORETAK_META, true);

    if (empty($bedrift)) {
        $bedrift = get_post_meta($post_id, BIMVERDI_ARTIKKEL_FORETAK_META, true);
    }
    if (is_array($bedrift)) {
        $bedrift = reset($bedrift);
    }
    if (is_object($bedrift) && isset($bedrift->ID)) {
        $bedrift = $bedrift->ID;
    }
    if ((int) $bedrift > 0) {
        return (int) $bedrift;
    }

    if (!$med_forfatter_fallback) {
        return 0;
    }

    $forfatter_id = (int) get_post_field('post_author', $post_id);
    if ($forfatter_id && class_exists('BIMVerdi_Access_Control')) {
        $fra_bruker = BIMVerdi_Access_Control::lookup_company_id($forfatter_id);
        if ($fra_bruker) {
            return (int) $fra_bruker;
        }
    }

    return 0;
}

/**
 * Er artikkelen tilskrevet et deltakerforetak?
 *
 * Kravet er at `artikkel_bedrift` er EKSPLISITT satt, og at foretaket finnes
 * og er publisert. Forfatterens eget foretak teller bevisst IKKE.
 *
 * Hvorfor ikke: nesten alle med en konto her er koblet til et foretak, også
 * BIM Verdis egne. Lokalt er 32 av 34 publiserte artikler skrevet av Bård,
 * hvis konto er koblet til Verdinettverk AS. Med forfatterens foretak i kjeden
 * ble ALLE 34 klassifisert som deltakerartikler, og «Andre artikler» i
 * nyhetsbrevet ble stående tom — altså det motsatte av å skille de to.
 * Med kravet om et eksplisitt satt felt blir skillet det redaksjonelle: noen
 * har aktivt tilskrevet artikkelen et foretak, enten deltakeren selv gjennom
 * Min side (skjemaet setter feltet), eller Bård i wp-admin på deltakerens
 * vegne. Det er nettopp den handlingen «fra deltaker» beskriver.
 *
 * Foretaket må være publisert: et upublisert foretak (pending registrering,
 * kladd) gir ingen lenke vi kan sende folk til, og nyhetsbrevet skal ikke
 * lenke til en 404. Access-control status-sjekker med vilje ikke i sitt
 * data-lag — brukeren ER koblet uansett status — men for å PUBLISERE en
 * attribusjon er publisert riktig krav.
 *
 * AVGJORT 03.09.2026 (Bård, møte): Verdinettverk AS er BIM Verdis egen
 * redaksjon — «det er jo jeg som redaktør, så den skal ikke havne under
 * artikler fra deltakerne». Foretaket er unntatt via
 * `bimverdi_deltakerartikkel_unntatte_foretak`, se filteret nederst i fila.
 *
 * MERK NAVNGIVINGEN: overfor brukeren heter dette «Artikler fra deltakere»,
 * aldri «deltakerartikler» — Bård 03.09: det siste kan leses som artikler
 * OM deltakere, ikke FRA dem. Funksjonsnavnene her er interne og kan stå.
 *
 * @param int $post_id
 * @return bool
 */
function bimverdi_artikkel_er_deltakerartikkel($post_id) {
    $foretak_id = bimverdi_artikkel_foretak_id($post_id, false);
    if (!$foretak_id) {
        return false;
    }

    /**
     * Foretak som ikke skal regnes som «deltaker» i denne sammenhengen —
     * typisk BIM Verdis egen organisasjon. Tom som standard, så oppførselen
     * er forutsigbar uten oppsett og lik på localhost og prod.
     *
     * @param int[] $ids
     */
    $unntatt = array_map('intval', (array) apply_filters('bimverdi_deltakerartikkel_unntatte_foretak', array()));
    if (in_array($foretak_id, $unntatt, true)) {
        return false;
    }

    $foretak = get_post($foretak_id);
    if (!$foretak) {
        return false;
    }

    $cpt = defined('BV_CPT_COMPANY') ? BV_CPT_COMPANY : 'foretak';
    if ($foretak->post_type !== $cpt) {
        return false;
    }

    return $foretak->post_status === 'publish';
}

/**
 * meta_query som skiller deltakerartikler fra resten.
 *
 * Klausulen dekker feltet alene. Om foretaket faktisk er publisert kan ikke
 * uttrykkes i SQL her (det ville krevd en join mot posts på en meta-verdi), så
 * kallere som trenger den garantien må filtrere resultatet med
 * bimverdi_artikkel_er_deltakerartikkel(). bimverdi_artikler_gruppert() gjør
 * nettopp det.
 *
 * @param bool $deltaker True = kun tilskrevne, false = kun ikke-tilskrevne.
 * @return array
 */
function bimverdi_artikkel_meta_query_deltaker($deltaker = true) {
    if ($deltaker) {
        return array(
            array(
                'key'     => BIMVERDI_ARTIKKEL_FORETAK_META,
                'value'   => '',
                'compare' => '!=',
            ),
        );
    }

    // «Ikke tilskrevet» må dekke BÅDE fraværende nøkkel og tom streng.
    // ACF skriver '' når feltet tømmes, mens artikler som aldri har vært
    // innom skjemaet mangler raden helt.
    return array(
        'relation' => 'OR',
        array(
            'key'     => BIMVERDI_ARTIKKEL_FORETAK_META,
            'compare' => 'NOT EXISTS',
        ),
        array(
            'key'     => BIMVERDI_ARTIKKEL_FORETAK_META,
            'value'   => '',
            'compare' => '=',
        ),
    );
}

/**
 * Del artikler i «fra deltaker» og «andre».
 *
 * Én spørring per gruppe, sortert på sist endret slik nyhetsbrevet ellers gjør.
 * Deltaker-gruppa hentes med romslig grense og filtreres etterpå gjennom
 * bimverdi_artikkel_er_deltakerartikkel(), som luker ut artikler tilskrevet et
 * foretak som er slettet, upublisert eller unntatt. De som lukes ut skal ikke
 * forsvinne fra nyhetsbrevet — de hører hjemme under «andre» — så de legges
 * over dit.
 *
 * @param int   $per_gruppe Maks antall per gruppe (0 = ingen grense).
 * @param array $statuser   Poststatuser å ta med.
 * @return array{deltaker: int[], andre: int[]}
 */
function bimverdi_artikler_gruppert($per_gruppe = 3, $statuser = array('publish')) {
    $felles = array(
        'post_type'              => 'artikkel',
        'post_status'            => $statuser,
        'fields'                 => 'ids',
        'orderby'                => 'modified',
        'order'                  => 'DESC',
        'update_post_term_cache' => false,
    );

    $tilskrevne = get_posts(array_merge($felles, array(
        'posts_per_page' => -1,
        'meta_query'     => bimverdi_artikkel_meta_query_deltaker(true),
    )));

    $deltaker = array();
    $degradert = array();
    foreach ($tilskrevne as $id) {
        if (bimverdi_artikkel_er_deltakerartikkel($id)) {
            $deltaker[] = (int) $id;
        } else {
            $degradert[] = (int) $id;
        }
    }

    $andre = array_map('intval', get_posts(array_merge($felles, array(
        'posts_per_page' => -1,
        'meta_query'     => bimverdi_artikkel_meta_query_deltaker(false),
    ))));

    // Degraderte inn i «andre», og hold sorteringen på sist endret
    if ($degradert) {
        $andre = array_merge($andre, $degradert);
        $rekkefolge = array_flip(array_map('intval', get_posts(array_merge($felles, array(
            'posts_per_page' => -1,
        )))));
        usort($andre, function ($a, $b) use ($rekkefolge) {
            $pa = $rekkefolge[$a] ?? PHP_INT_MAX;
            $pb = $rekkefolge[$b] ?? PHP_INT_MAX;
            return $pa <=> $pb;
        });
    }

    if ($per_gruppe > 0) {
        $deltaker = array_slice($deltaker, 0, $per_gruppe);
        $andre    = array_slice($andre, 0, $per_gruppe);
    }

    return array('deltaker' => $deltaker, 'andre' => $andre);
}

/**
 * Antall artikler i hver gruppe, for «Se alle N»-lenkene i nyhetsbrevet.
 *
 * @return array{deltaker: int, andre: int}
 */
function bimverdi_artikler_antall_per_gruppe() {
    $alle = bimverdi_artikler_gruppert(0);

    return array(
        'deltaker' => count($alle['deltaker']),
        'andre'    => count($alle['andre']),
    );
}

/**
 * Verdinettverk AS er BIM Verdis egen redaksjon, ikke en deltaker.
 *
 * Slås opp på slug, ikke hardkodet ID: ID-en er 207 både lokalt og på prod
 * (verifisert 03.09.2026), men et oppslag som følger slugen tåler at
 * foretaket en gang blir opprettet på nytt. Memoisert per request — én
 * get_page_by_path() per prosess, og WP cacher den selv i tillegg.
 *
 * @param int[] $ids
 * @return int[]
 */
add_filter('bimverdi_deltakerartikkel_unntatte_foretak', function ($ids) {
    static $eget_foretak = null;

    if ($eget_foretak === null) {
        $cpt   = defined('BV_CPT_COMPANY') ? BV_CPT_COMPANY : 'foretak';
        $post  = get_page_by_path('verdinettverk-as', OBJECT, $cpt);
        $eget_foretak = $post ? (int) $post->ID : 0;
    }

    if ($eget_foretak) {
        $ids[] = $eget_foretak;
    }

    return $ids;
});
