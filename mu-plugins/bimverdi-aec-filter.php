<?php
/**
 * Plugin Name: BIM Verdi - Verktøy-spørringshjelpere
 * Description: Felles hjelpere for verktøy-spørringer: utelate de synkroniserte AEC AI Hub-verktøyene, og finne hvilket foretak som eier et verktøy.
 * Version: 1.1.0
 *
 * Bakgrunn: AEC AI Hub-synken (august 2026) la ~1900 eksterne verktøy inn i
 * samme CPT som deltakernes egne ~40. Det er riktig for katalogen, men gjør
 * enkelte visninger ubrukelige — Bård, Trello #347 punkt 5: «Fjern verktøy fra
 * kilden AIinAEC som underlag for grafene. De blir for omfattende med så mange
 * ressurser.» Samme problem i nyhetsbrevet (punkt 9.2), der «Se alle 1944»
 * druknet deltakernes verktøy.
 *
 * Diskriminatoren er `_bv_aec_source`, ikke ACF-feltet `kilde`. `kilde` er
 * tomt/fraværende for deltakerverktøy, så en `!=`-sammenligning der treffer
 * ikke rader som mangler nøkkelen. `_bv_aec_source` settes på hvert
 * hub-verktøy av upserteren og er den samme diskriminatoren resten av koden
 * bruker (verktoy-katalog.php, ressurs-rig.php, single-theme_group.php).
 * Den fanger også de få «orphaned» hub-verktøyene som ikke lenger er managed.
 *
 * Ligger i mu-plugins og ikke i temaet fordi både temaets maler og
 * nyhetsbrev-mu-pluginen trenger den. mu-plugins lastes først, så funksjonen
 * finnes uansett hvem som kaller.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Metanøkkelen som skiller hub-verktøy fra deltakerverktøy.
 */
if (!defined('BIMVERDI_AEC_META_SOURCE')) {
    define('BIMVERDI_AEC_META_SOURCE', '_bv_aec_source');
}

/**
 * meta_query-klausul som utelater AEC AI Hub-verktøy.
 *
 * @return array Klausul-liste egnet for 'meta_query'.
 */
function bimverdi_meta_query_uten_aec() {
    return array(
        array(
            'key'     => BIMVERDI_AEC_META_SOURCE,
            'compare' => 'NOT EXISTS',
        ),
    );
}

/**
 * Legg «uten AEC»-filteret på et sett spørrings-argumenter.
 *
 * Finnes det allerede en meta_query, nestes den HELE som én undergruppe under
 * en ny AND. Da bevares dens egen interne relation (en OR-gruppe forblir en
 * OR-gruppe) i stedet for at klausulene blandes sammen på toppnivå — der ville
 * en eksisterende 'relation' => 'OR' gjort AEC-filteret valgfritt, og filteret
 * ville ikke virket.
 *
 * @param array $args Argumenter til WP_Query/get_posts.
 * @return array Samme argumenter med filteret på.
 */
function bimverdi_query_args_uten_aec(array $args) {
    $ny = bimverdi_meta_query_uten_aec();

    if (empty($args['meta_query'])) {
        $args['meta_query'] = $ny;
        return $args;
    }

    $args['meta_query'] = array(
        'relation' => 'AND',
        $args['meta_query'],
        $ny[0],
    );

    return $args;
}

/**
 * Antall publiserte deltakerverktøy (uten hub-verktøyene).
 *
 * Erstatter wp_count_posts('verktoy')->publish der tallet skal gjelde
 * deltakernes verktøy alene. Cachet i 15 minutter — brukes i nyhetsbrev-
 * forhåndsvisning som kan lastes mange ganger på rad, og tallet endrer seg
 * bare når et verktøy registreres eller synken kjører.
 *
 * @param bool $tving_ny Hopp over cache.
 * @return int
 */
function bimverdi_antall_deltakerverktoy($tving_ny = false) {
    $cache_nokkel = 'bimverdi_antall_deltakerverktoy';

    if (!$tving_ny) {
        $cachet = get_transient($cache_nokkel);
        if ($cachet !== false) {
            return (int) $cachet;
        }
    }

    $q = new WP_Query(bimverdi_query_args_uten_aec(array(
        'post_type'              => 'verktoy',
        'post_status'            => 'publish',
        'posts_per_page'         => 1,
        'fields'                 => 'ids',
        'no_found_rows'          => false,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    )));

    $antall = (int) $q->found_posts;
    set_transient($cache_nokkel, $antall, 15 * MINUTE_IN_SECONDS);

    return $antall;
}

/**
 * Tøm tellingen når et verktøy endres, slik at nyhetsbrevet ikke viser et
 * gammelt tall rett etter at Bård har publisert et nytt deltakerverktøy.
 */
add_action('save_post_verktoy', 'bimverdi_tom_deltakerverktoy_cache');
add_action('deleted_post', 'bimverdi_tom_deltakerverktoy_cache');
add_action('trashed_post', 'bimverdi_tom_deltakerverktoy_cache');
add_action('untrashed_post', 'bimverdi_tom_deltakerverktoy_cache');

function bimverdi_tom_deltakerverktoy_cache() {
    delete_transient('bimverdi_antall_deltakerverktoy');
}

/**
 * Hvilket foretak eier et verktøy?
 *
 * Feltet heter `eier_leverandor`, og det er den nøkkelen all produksjonskode
 * bruker (single-verktoy, single-foretak, verktøykatalogen, Min side, og
 * tool-registration som skriver den). Demo-grafene leste historisk
 * `tilknyttet_foretak` — et felt som ikke finnes på verktøy-CPT-en og som
 * derfor alltid var tomt. Resultatet var at foretak↔verktøy-koblingene i
 * grafene aldri ble laget, og matrise-demoen falt tilbake til demo-data.
 * Oppdaget under Trello #347 pkt 5 (02.09.2026).
 *
 * `tilknyttet_foretak` beholdes som fallback: det ER navnet på det tilsvarende
 * feltet på BRUKER (bruker → foretak), og skulle en installasjon ha data der
 * på verktøy, skal den ikke miste koblingen.
 *
 * @param int $tool_id
 * @return int Foretak-ID, eller 0 hvis ukjent.
 */
function bimverdi_verktoy_eier_foretak_id($tool_id) {
    $tool_id = (int) $tool_id;
    if (!$tool_id) {
        return 0;
    }

    foreach (array('eier_leverandor', 'tilknyttet_foretak') as $nokkel) {
        $verdi = function_exists('get_field')
            ? get_field($nokkel, $tool_id)
            : get_post_meta($tool_id, $nokkel, true);

        if (empty($verdi)) {
            // ACF kan være av; prøv rå meta før vi går videre til neste nøkkel
            $verdi = get_post_meta($tool_id, $nokkel, true);
        }

        // post_object kan gi WP_Post, ID, eller array ved multiple
        if (is_array($verdi)) {
            $verdi = reset($verdi);
        }
        if (is_object($verdi) && isset($verdi->ID)) {
            $verdi = $verdi->ID;
        }

        if ((int) $verdi > 0) {
            return (int) $verdi;
        }
    }

    return 0;
}
