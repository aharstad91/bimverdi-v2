<?php
/**
 * Plugin Name: BIM Verdi — Meldinger mellom brukere (krav 31, runde A)
 * Description: Samtaler person til person mellom brukere med foretakskobling. Tabeller, tilgangsregel, REST og skjemahåndtering. Visning ligger i temaet (parts/minside/meldinger-*.php).
 * Version: 1.0.0
 *
 * Krav: bedrift/31-KRAV-meldinger-v1.md i bimverdi-context. Beslutning B-039
 * (unntak fra B-011). Varsel via B-029 (bimverdi-varslinger.php), e-post via
 * felles skjelett (B-030, bimverdi_epost_dokument i bimverdi-epost-skjelett.php).
 *
 * E-POSTGATE (fail-closed)
 *   Mens gaten er låst, sendes e-post bare til administratorer. Alle andre
 *   hoppes over med logglinje [bv-meldinger]. Åpning er et bevisst steg:
 *       wp option update bimverdi_meldinger_epost_apen 1
 *   Option, ikke konstant: wp-config.php kan ikke endres via bard-gaten.
 *   Lokalt maskerer _local-email-blocker.php leveranser uansett.
 *   Alt annet enn strengen '1' = låst.
 *
 * RUNDE B (krav 31 v3)
 *   «Lukket melding» = person til person (dette). «Åpen melding» = meldingsfeltet.
 *   Startknapp (parts/components/lukket-melding-knapp.php) på artikkel og
 *   foretaksprofil, søk på /min-side/meldinger/ny/, «Skjul samtale».
 *   LANSERINGSBRYTER (fail-closed): til
 *       wp option update bimverdi_meldinger_lansert 1
 *   kan bare administratorer se startknappene og starte nye samtaler.
 *   Svar i eksisterende samtaler er alltid tillatt.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

const BIMVERDI_MELDINGER_DB_VERSION  = '2'; // 2: skjult_a/skjult_b (runde B, «Skjul samtale»).
const BIMVERDI_MELDINGER_MAKS_TEGN   = 5000;
const BIMVERDI_MELDINGER_MAKS_PER_TIME = 20;
const BIMVERDI_MELDINGER_EPOST_PAUSE = 1800; // 30 min mellom e-poster per samtale og mottaker.

/* =========================================================================
 * TABELLER
 * ========================================================================= */

function bv_meldinger_tabell_traader() {
    global $wpdb;
    return $wpdb->prefix . 'bimverdi_meldingstraader';
}

function bv_meldinger_tabell_meldinger() {
    global $wpdb;
    return $wpdb->prefix . 'bimverdi_meldinger';
}

/**
 * Opprett tabellene. Én tråd per par: deltaker_a er alltid den laveste user_id.
 *
 * @return void
 */
function bv_meldinger_opprett_tabeller() {
    global $wpdb;

    $traader  = bv_meldinger_tabell_traader();
    $meldinger = bv_meldinger_tabell_meldinger();
    $collate  = $wpdb->get_charset_collate();

    $sql_traader = "CREATE TABLE {$traader} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        deltaker_a bigint(20) unsigned NOT NULL,
        deltaker_b bigint(20) unsigned NOT NULL,
        opprettet datetime NOT NULL,
        siste_melding datetime NOT NULL,
        skjult_a datetime NULL DEFAULT NULL,
        skjult_b datetime NULL DEFAULT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY par (deltaker_a, deltaker_b),
        KEY deltaker_b (deltaker_b),
        KEY siste_melding (siste_melding)
    ) {$collate};";

    $sql_meldinger = "CREATE TABLE {$meldinger} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        traad_id bigint(20) unsigned NOT NULL,
        avsender_id bigint(20) unsigned NOT NULL,
        tekst text NOT NULL,
        opprettet datetime NOT NULL,
        lest_dato datetime NULL DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY traad_opprettet (traad_id, opprettet),
        KEY avsender_opprettet (avsender_id, opprettet)
    ) {$collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_traader);
    dbDelta($sql_meldinger);

    update_option('bimverdi_meldinger_db_version', BIMVERDI_MELDINGER_DB_VERSION);
}

add_action('init', function () {
    if (get_option('bimverdi_meldinger_db_version') !== BIMVERDI_MELDINGER_DB_VERSION) {
        bv_meldinger_opprett_tabeller();
    }
}, 5);

/* =========================================================================
 * HJELPERE
 * ========================================================================= */

/**
 * Har brukeren foretakskobling (Gratisforetak eller Deltaker+)?
 * Samme regel som krav 22 bruker (BIMVerdi_Access_Control).
 */
function bv_meldinger_har_foretak($user_id) {
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return false;
    }
    if (class_exists('BIMVerdi_Access_Control') && method_exists('BIMVerdi_Access_Control', 'user_has_company')) {
        return (bool) BIMVerdi_Access_Control::user_has_company($user_id);
    }
    return false;
}

/**
 * Tar brukeren imot nye samtaler? På som standard: bare eksplisitt '0' er av.
 */
function bv_meldinger_tar_imot($user_id) {
    return get_user_meta((int) $user_id, 'bv_ta_imot_meldinger', true) !== '0';
}

/**
 * Visningsnavn, eller «Slettet bruker».
 */
function bv_meldinger_navn($user_id) {
    $user = get_userdata((int) $user_id);
    if (!$user) {
        return 'Slettet bruker';
    }
    return $user->display_name !== '' ? $user->display_name : $user->user_login;
}

/**
 * Navn på brukerens foretak, eller tom streng.
 */
function bv_meldinger_foretaksnavn($user_id) {
    if (!function_exists('bimverdi_resolve_user_foretak_id')) {
        return '';
    }
    $foretak_id = bimverdi_resolve_user_foretak_id((int) $user_id);
    return $foretak_id ? (string) get_the_title($foretak_id) : '';
}

/**
 * Tråden mellom to brukere, eller null.
 */
function bv_meldinger_traad_for_par($bruker_1, $bruker_2) {
    global $wpdb;
    $a = min((int) $bruker_1, (int) $bruker_2);
    $b = max((int) $bruker_1, (int) $bruker_2);
    $tabell = bv_meldinger_tabell_traader();
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$tabell} WHERE deltaker_a = %d AND deltaker_b = %d",
        $a,
        $b
    ));
}

/**
 * Hent tråd etter ID, eller null.
 */
function bv_meldinger_hent_traad($traad_id) {
    global $wpdb;
    $tabell = bv_meldinger_tabell_traader();
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabell} WHERE id = %d", (int) $traad_id));
}

/**
 * Er brukeren part i tråden?
 */
function bv_meldinger_er_deltaker($traad, $user_id) {
    if (!$traad) {
        return false;
    }
    $user_id = (int) $user_id;
    return $user_id > 0 && ((int) $traad->deltaker_a === $user_id || (int) $traad->deltaker_b === $user_id);
}

/**
 * Den andre parten i tråden.
 */
function bv_meldinger_motpart($traad, $user_id) {
    return ((int) $traad->deltaker_a === (int) $user_id) ? (int) $traad->deltaker_b : (int) $traad->deltaker_a;
}

/**
 * Har brukeren skrevet minst én melding i tråden?
 */
function bv_meldinger_har_skrevet_i($traad_id, $user_id) {
    global $wpdb;
    $tabell = bv_meldinger_tabell_meldinger();
    return (bool) $wpdb->get_var($wpdb->prepare(
        "SELECT 1 FROM {$tabell} WHERE traad_id = %d AND avsender_id = %d LIMIT 1",
        (int) $traad_id,
        (int) $user_id
    ));
}

/**
 * Norske feiltekster (krav 31, «Tekster»).
 */
function bv_meldinger_feiltekst($kode, $mottaker_id = 0) {
    switch ($kode) {
        case 'ikke_innlogget':
            return 'Du må logge inn for å sende meldinger.';
        case 'mangler_foretak':
            return 'Du må koble deg til ditt foretak/arbeidsgiver før du går videre';
        case 'seg_selv':
            return 'Du kan ikke sende melding til deg selv.';
        case 'mottaker_uten_foretak':
        case 'mottaker_finnes_ikke':
            return 'Denne brukeren kan ikke motta meldinger.';
        case 'tar_ikke_imot':
            return sprintf('%s tar ikke imot meldinger.', bv_meldinger_navn($mottaker_id));
        case 'for_mange':
            return 'Du har sendt mange meldinger på kort tid. Prøv igjen om litt.';
        case 'tom':
            return 'Skriv en melding før du sender.';
        case 'for_lang':
            return 'Meldingen kan være maks 5 000 tegn.';
        case 'fant_ikke_traad':
            return 'Fant ikke samtalen.';
        case 'ikke_lansert':
            return 'Lukkede meldinger er ikke åpnet for alle ennå.';
        default:
            return 'Meldingen ble ikke sendt. Prøv igjen.';
    }
}

function bv_meldinger_feil($kode, $status = 403, $mottaker_id = 0) {
    return new WP_Error('bv_melding_' . $kode, bv_meldinger_feiltekst($kode, $mottaker_id), array('status' => $status));
}

/* =========================================================================
 * TILGANGSREGEL
 * ========================================================================= */

/**
 * Kan avsender sende en melding til mottaker nå?
 *
 * Krav 31, «Hvem kan sende til hvem». Administratorer følger samme regler.
 *
 * @param int $avsender_id
 * @param int $mottaker_id
 * @return true|WP_Error
 */
function bv_kan_sende_melding($avsender_id, $mottaker_id) {
    global $wpdb;

    $avsender_id = (int) $avsender_id;
    $mottaker_id = (int) $mottaker_id;

    if ($avsender_id <= 0) {
        return bv_meldinger_feil('ikke_innlogget', 401);
    }
    if (!bv_meldinger_har_foretak($avsender_id)) {
        return bv_meldinger_feil('mangler_foretak');
    }
    if ($avsender_id === $mottaker_id) {
        return bv_meldinger_feil('seg_selv', 400);
    }
    if ($mottaker_id <= 0 || !get_userdata($mottaker_id)) {
        return bv_meldinger_feil('mottaker_finnes_ikke', 404);
    }
    if (!bv_meldinger_har_foretak($mottaker_id)) {
        return bv_meldinger_feil('mottaker_uten_foretak');
    }

    if (!bv_meldinger_tar_imot($mottaker_id)) {
        // Unntak: svar i en samtale mottakeren selv har skrevet i.
        $traad = bv_meldinger_traad_for_par($avsender_id, $mottaker_id);
        if (!$traad || !bv_meldinger_har_skrevet_i($traad->id, $mottaker_id)) {
            return bv_meldinger_feil('tar_ikke_imot', 403, $mottaker_id);
        }
    }

    $tabell = bv_meldinger_tabell_meldinger();
    $siste_time = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabell} WHERE avsender_id = %d AND opprettet > %s",
        $avsender_id,
        gmdate('Y-m-d H:i:s', time() - HOUR_IN_SECONDS)
    ));
    if ($siste_time >= BIMVERDI_MELDINGER_MAKS_PER_TIME) {
        return bv_meldinger_feil('for_mange', 429);
    }

    return true;
}

/* =========================================================================
 * SENDING
 * ========================================================================= */

/**
 * Rens og valider meldingstekst.
 *
 * @return string|WP_Error
 */
function bv_meldinger_rens_tekst($tekst) {
    $tekst = trim(sanitize_textarea_field(wp_unslash((string) $tekst)));
    if ($tekst === '') {
        return bv_meldinger_feil('tom', 400);
    }
    if (mb_strlen($tekst, 'UTF-8') > BIMVERDI_MELDINGER_MAKS_TEGN) {
        return bv_meldinger_feil('for_lang', 400);
    }
    return $tekst;
}

/**
 * Send en melding fra avsender til mottaker. Finner eller lager tråden.
 *
 * @param int    $avsender_id
 * @param int    $mottaker_id
 * @param string $tekst Rå tekst (renses her).
 * @return array|WP_Error ['traad_id' => int, 'melding_id' => int]
 */
function bv_meldinger_send($avsender_id, $mottaker_id, $tekst) {
    global $wpdb;

    $tekst = bv_meldinger_rens_tekst($tekst);
    if (is_wp_error($tekst)) {
        return $tekst;
    }

    $tillatt = bv_kan_sende_melding($avsender_id, $mottaker_id);
    if (is_wp_error($tillatt)) {
        return $tillatt;
    }

    // Før lansering kan bare administratorer starte nye samtaler.
    if (!bv_meldinger_kan_starte($avsender_id) && !bv_meldinger_traad_for_par($avsender_id, $mottaker_id)) {
        return bv_meldinger_feil('ikke_lansert', 403);
    }

    $na = current_time('mysql', true);
    $a  = min((int) $avsender_id, (int) $mottaker_id);
    $b  = max((int) $avsender_id, (int) $mottaker_id);

    $traader = bv_meldinger_tabell_traader();
    // INSERT IGNORE: to samtidige førstemeldinger gir fortsatt bare én tråd.
    $wpdb->query($wpdb->prepare(
        "INSERT IGNORE INTO {$traader} (deltaker_a, deltaker_b, opprettet, siste_melding) VALUES (%d, %d, %s, %s)",
        $a,
        $b,
        $na,
        $na
    ));
    $traad = bv_meldinger_traad_for_par($a, $b);
    if (!$traad) {
        error_log(sprintf('[bv-meldinger] FEILET å lage tråd for %d og %d: %s', $a, $b, $wpdb->last_error));
        return bv_meldinger_feil('lagring', 500);
    }

    $ok = $wpdb->insert(
        bv_meldinger_tabell_meldinger(),
        array(
            'traad_id'    => (int) $traad->id,
            'avsender_id' => (int) $avsender_id,
            'tekst'       => $tekst,
            'opprettet'   => $na,
        ),
        array('%d', '%d', '%s', '%s')
    );
    if (!$ok) {
        error_log(sprintf('[bv-meldinger] FEILET å lagre melding i tråd %d: %s', $traad->id, $wpdb->last_error));
        return bv_meldinger_feil('lagring', 500);
    }
    $melding_id = (int) $wpdb->insert_id;

    // Ny melding gjør samtalen synlig igjen for den som har skjult den (krav 31 v2).
    $wpdb->query($wpdb->prepare(
        "UPDATE {$traader} SET siste_melding = %s, skjult_a = NULL, skjult_b = NULL WHERE id = %d",
        $na,
        (int) $traad->id
    ));

    // Varsling skal aldri gjøre at en lagret melding rapporteres som feilet.
    try {
        if (function_exists('bv_varsel_opprett')) {
            bv_varsel_opprett((int) $mottaker_id, 'ny_melding', array(
                'traad_id'    => (int) $traad->id,
                'avsender_id' => (int) $avsender_id,
                'melding_id'  => $melding_id,
            ));
        }
        bv_meldinger_epost((int) $mottaker_id, (int) $avsender_id, (int) $traad->id);
    } catch (\Throwable $e) {
        error_log('[bv-meldinger] Varsling feilet for melding ' . $melding_id . ': ' . $e->getMessage());
    }

    return array('traad_id' => (int) $traad->id, 'melding_id' => $melding_id);
}

/**
 * Svar i en eksisterende tråd.
 *
 * @return array|WP_Error
 */
function bv_meldinger_svar($avsender_id, $traad_id, $tekst) {
    $traad = bv_meldinger_hent_traad($traad_id);
    if (!bv_meldinger_er_deltaker($traad, $avsender_id)) {
        return bv_meldinger_feil('fant_ikke_traad', 404);
    }
    return bv_meldinger_send($avsender_id, bv_meldinger_motpart($traad, $avsender_id), $tekst);
}

/* =========================================================================
 * LESING
 * ========================================================================= */

/**
 * Brukerens tråder, nyeste først, med motpart, utdrag og antall uleste.
 *
 * @return array[]
 */
function bv_meldinger_traader($user_id) {
    global $wpdb;

    $user_id   = (int) $user_id;
    $traader   = bv_meldinger_tabell_traader();
    $meldinger = bv_meldinger_tabell_meldinger();

    $rader = $wpdb->get_results($wpdb->prepare(
        "SELECT t.*,
            (SELECT m.tekst FROM {$meldinger} m WHERE m.traad_id = t.id ORDER BY m.opprettet DESC, m.id DESC LIMIT 1) AS siste_tekst,
            (SELECT m.avsender_id FROM {$meldinger} m WHERE m.traad_id = t.id ORDER BY m.opprettet DESC, m.id DESC LIMIT 1) AS siste_avsender,
            (SELECT COUNT(*) FROM {$meldinger} m WHERE m.traad_id = t.id AND m.avsender_id <> %d AND m.lest_dato IS NULL) AS uleste
         FROM {$traader} t
         WHERE (t.deltaker_a = %d AND t.skjult_a IS NULL) OR (t.deltaker_b = %d AND t.skjult_b IS NULL)
         ORDER BY t.siste_melding DESC
         LIMIT 200",
        $user_id,
        $user_id,
        $user_id
    ));

    $ut = array();
    foreach ((array) $rader as $rad) {
        $motpart = bv_meldinger_motpart($rad, $user_id);
        $ut[] = array(
            'traad_id'      => (int) $rad->id,
            'motpart_id'    => $motpart,
            'motpart_navn'  => bv_meldinger_navn($motpart),
            'motpart_foretak' => bv_meldinger_foretaksnavn($motpart),
            'utdrag'        => wp_html_excerpt((string) $rad->siste_tekst, 120, '…'),
            'siste_fra_meg' => (int) $rad->siste_avsender === $user_id,
            'siste_melding' => (string) $rad->siste_melding,
            'uleste'        => (int) $rad->uleste,
        );
    }
    return $ut;
}

/**
 * Meldingene i en tråd, eldste først.
 *
 * @return array[]
 */
function bv_meldinger_i_traad($traad_id) {
    global $wpdb;
    $tabell = bv_meldinger_tabell_meldinger();
    $rader  = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$tabell} WHERE traad_id = %d ORDER BY opprettet ASC, id ASC",
        (int) $traad_id
    ));

    $ut = array();
    foreach ((array) $rader as $rad) {
        $ut[] = array(
            'id'          => (int) $rad->id,
            'avsender_id' => (int) $rad->avsender_id,
            'avsender'    => bv_meldinger_navn($rad->avsender_id),
            'foretak'     => bv_meldinger_foretaksnavn($rad->avsender_id),
            'tekst'       => (string) $rad->tekst,
            'opprettet'   => (string) $rad->opprettet,
            'lest_dato'   => $rad->lest_dato ? (string) $rad->lest_dato : null,
        );
    }
    return $ut;
}

/**
 * Marker motpartens meldinger i tråden som lest, og tilhørende varsler.
 */
function bv_meldinger_marker_lest($user_id, $traad_id) {
    global $wpdb;
    $tabell = bv_meldinger_tabell_meldinger();
    $wpdb->query($wpdb->prepare(
        "UPDATE {$tabell} SET lest_dato = %s WHERE traad_id = %d AND avsender_id <> %d AND lest_dato IS NULL",
        current_time('mysql', true),
        (int) $traad_id,
        (int) $user_id
    ));
    if (function_exists('bv_varsler_marker_lest')) {
        bv_varsler_marker_lest((int) $user_id, 'ny_melding', 'traad_id', (int) $traad_id);
    }
}

/**
 * Antall uleste meldinger totalt for brukeren (til menyen).
 */
function bv_meldinger_antall_uleste($user_id) {
    global $wpdb;
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return 0;
    }
    $traader   = bv_meldinger_tabell_traader();
    $meldinger = bv_meldinger_tabell_meldinger();
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$meldinger} m
         INNER JOIN {$traader} t ON t.id = m.traad_id
         WHERE ((t.deltaker_a = %d AND t.skjult_a IS NULL) OR (t.deltaker_b = %d AND t.skjult_b IS NULL))
           AND m.avsender_id <> %d
           AND m.lest_dato IS NULL",
        $user_id,
        $user_id,
        $user_id
    ));
}

/**
 * Lenke til en samtale på Min Side.
 */
function bv_meldinger_traad_url($traad_id) {
    return add_query_arg('id', (int) $traad_id, home_url('/min-side/meldinger/traad/'));
}

/**
 * Formater UTC-tid fra tabellen til norsk lokal tid.
 */
function bv_meldinger_tid($utc) {
    if (!$utc) {
        return '';
    }
    return get_date_from_gmt($utc, 'd.m.Y \k\l. H:i');
}

/* =========================================================================
 * RUNDE B: LANSERINGSBRYTER, STARTKNAPP, SØK OG SKJUL
 * ========================================================================= */

/**
 * Er lukkede meldinger lansert for alle? Fail-closed: bare strengen '1'.
 *     wp option update bimverdi_meldinger_lansert 1
 * Før lansering ser bare administratorer startknappene og «Ny lukket melding».
 */
function bv_meldinger_lansert() {
    return get_option('bimverdi_meldinger_lansert') === '1';
}

/**
 * Kan gjeldende besøkende se startknappene nå?
 */
function bv_meldinger_start_synlig() {
    return bv_meldinger_lansert() || current_user_can('manage_options');
}

/**
 * Kan denne brukeren starte en ny samtale? Samme bryter, men for en gitt
 * bruker (gjelder også REST og wp eval). Svar i eksisterende samtaler
 * er alltid tillatt.
 */
function bv_meldinger_kan_starte($user_id) {
    return bv_meldinger_lansert() || user_can((int) $user_id, 'manage_options');
}

/**
 * Lenke til «Ny lukket melding», valgfritt til en bestemt mottaker.
 */
function bv_meldinger_ny_url($mottaker_id = 0) {
    $url = home_url('/min-side/meldinger/ny/');
    return $mottaker_id ? add_query_arg('til', (int) $mottaker_id, $url) : $url;
}

/**
 * Hva knappen «Send lukket melding» skal gjøre for avsender → mottaker.
 *
 * @return array ['tilstand' => 'skjult'|'logg_inn'|'blokk'|'skriv', 'url' => string]
 */
function bv_meldinger_knapp_tilstand($avsender_id, $mottaker_id) {
    $avsender_id = (int) $avsender_id;
    $mottaker_id = (int) $mottaker_id;
    $skjult      = array('tilstand' => 'skjult', 'url' => '');

    if ($mottaker_id <= 0 || !bv_meldinger_start_synlig()) {
        return $skjult;
    }

    // Kan mottakeren motta i det hele tatt? Ellers ingen knapp, uansett hvem som ser.
    if (!get_userdata($mottaker_id) || !bv_meldinger_har_foretak($mottaker_id)) {
        return $skjult;
    }

    if ($avsender_id <= 0) {
        if (!bv_meldinger_tar_imot($mottaker_id)) {
            return $skjult;
        }
        return array(
            'tilstand' => 'logg_inn',
            'url'      => home_url('/logg-inn/?redirect_to=' . rawurlencode(bv_meldinger_ny_url($mottaker_id))),
        );
    }

    if ($avsender_id === $mottaker_id) {
        return $skjult;
    }

    // Krav 22: knappen vises, trykk gir blokk-meldingen (ny-siden viser blokk-veggen).
    if (!bv_meldinger_har_foretak($avsender_id)) {
        return array('tilstand' => 'blokk', 'url' => bv_meldinger_ny_url($mottaker_id));
    }

    $traad = bv_meldinger_traad_for_par($avsender_id, $mottaker_id);
    if (!bv_meldinger_tar_imot($mottaker_id) && !($traad && bv_meldinger_har_skrevet_i($traad->id, $mottaker_id))) {
        return $skjult;
    }

    return array(
        'tilstand' => 'skriv',
        'url'      => $traad ? bv_meldinger_traad_url($traad->id) : bv_meldinger_ny_url($mottaker_id),
    );
}

/**
 * Søk etter mottakere på navn eller foretaksnavn.
 *
 * Bare brukere med foretakskobling som tar imot meldinger. Aldri e-post i
 * søk eller treff. Avsender må selv ha foretakskobling.
 *
 * @return array[] [['id','navn','foretak'], …] maks 20, sortert på navn.
 */
function bv_meldinger_sok_mottakere($avsender_id, $sok) {
    $avsender_id = (int) $avsender_id;
    $sok         = trim(sanitize_text_field((string) $sok));

    if (!bv_meldinger_har_foretak($avsender_id) || mb_strlen($sok, 'UTF-8') < 2) {
        return array();
    }
    $sok = mb_substr($sok, 0, 60, 'UTF-8');

    $kandidater = array(); // user_id => foretak_id som må stemme (0 = hvilket som helst)

    // 1) Visningsnavn.
    foreach (get_users(array(
        'search'         => '*' . $sok . '*',
        'search_columns' => array('display_name'),
        'fields'         => 'ID',
        'number'         => 60,
    )) as $id) {
        $kandidater[(int) $id] = 0;
    }

    // 2) Fornavn / etternavn.
    foreach (get_users(array(
        'meta_query' => array(
            'relation' => 'OR',
            array('key' => 'first_name', 'value' => $sok, 'compare' => 'LIKE'),
            array('key' => 'last_name', 'value' => $sok, 'compare' => 'LIKE'),
        ),
        'fields'     => 'ID',
        'number'     => 60,
    )) as $id) {
        $kandidater[(int) $id] = 0;
    }

    // 3) Foretaksnavn → brukere koblet til de foretakene.
    $foretak_ids = get_posts(array(
        'post_type'      => 'foretak',
        'post_status'    => array('publish', 'pending', 'draft'),
        's'              => $sok,
        'fields'         => 'ids',
        'posts_per_page' => 20,
        'no_found_rows'  => true,
    ));
    if ($foretak_ids) {
        $meta = array('relation' => 'OR');
        foreach (array('bimverdi_company_id', 'bim_verdi_company_id', 'tilknyttet_foretak') as $nokkel) {
            $meta[] = array('key' => $nokkel, 'value' => array_map('strval', $foretak_ids), 'compare' => 'IN');
        }
        foreach (get_users(array('meta_query' => $meta, 'fields' => 'ID', 'number' => 100)) as $id) {
            if (!isset($kandidater[(int) $id])) {
                $kandidater[(int) $id] = -1; // må faktisk tilhøre et av foretakene
            }
        }
    }

    $treff = array();
    foreach ($kandidater as $id => $krav) {
        if ($id === $avsender_id || !bv_meldinger_har_foretak($id) || !bv_meldinger_tar_imot($id)) {
            continue;
        }
        $foretak_id = function_exists('bimverdi_resolve_user_foretak_id') ? (int) bimverdi_resolve_user_foretak_id($id) : 0;
        if ($krav === -1 && !in_array($foretak_id, array_map('intval', $foretak_ids), true)) {
            continue;
        }
        $treff[] = array(
            'id'      => $id,
            'navn'    => bv_meldinger_navn($id),
            'foretak' => $foretak_id ? (string) get_the_title($foretak_id) : '',
        );
    }

    usort($treff, function ($a, $b) {
        return strcasecmp($a['navn'], $b['navn']);
    });

    return array_slice($treff, 0, 20);
}

/**
 * Skjul en samtale i egen innboks (krav 31 v2). Motparten berøres ikke.
 *
 * @return true|WP_Error
 */
function bv_meldinger_skjul($user_id, $traad_id) {
    global $wpdb;
    $traad = bv_meldinger_hent_traad($traad_id);
    if (!bv_meldinger_er_deltaker($traad, $user_id)) {
        return bv_meldinger_feil('fant_ikke_traad', 404);
    }
    $kolonne = ((int) $traad->deltaker_a === (int) $user_id) ? 'skjult_a' : 'skjult_b';
    $wpdb->query($wpdb->prepare(
        "UPDATE " . bv_meldinger_tabell_traader() . " SET {$kolonne} = %s WHERE id = %d",
        current_time('mysql', true),
        (int) $traad->id
    ));
    bv_meldinger_marker_lest((int) $user_id, (int) $traad->id);
    return true;
}

/* =========================================================================
 * OPPRYDDING (krav 31, avklart 21.11.2026, står i personvernerklæringen)
 *
 *   - Samtaler begge parter har skjult, slettes 30 dager etter siste skjuling.
 *   - Alle samtaler slettes senest 3 år etter siste melding.
 *
 * Sletter tråd, meldinger og tilhørende 'ny_melding'-varsler for godt.
 * Kjøres daglig via WP-cron. Tørrkjøring: wp eval 'print_r(bv_meldinger_opprydding(true));'
 * ========================================================================= */

const BIMVERDI_MELDINGER_SKJULT_DAGER = 30;
const BIMVERDI_MELDINGER_MAKS_AAR     = 3;

/**
 * Finn og slett utløpte samtaler.
 *
 * @param bool $torr true = bare tell, slett ingenting.
 * @return array{traader:int, meldinger:int, varsler:int, torr:bool}
 */
function bv_meldinger_opprydding($torr = false) {
    global $wpdb;
    $traader   = bv_meldinger_tabell_traader();
    $meldinger = bv_meldinger_tabell_meldinger();
    $na        = time();
    $skjult_grense = gmdate('Y-m-d H:i:s', $na - BIMVERDI_MELDINGER_SKJULT_DAGER * DAY_IN_SECONDS);
    $alder_grense  = gmdate('Y-m-d H:i:s', strtotime('-' . BIMVERDI_MELDINGER_MAKS_AAR . ' years', $na));

    $ider = array_map('intval', (array) $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM {$traader}
         WHERE (skjult_a IS NOT NULL AND skjult_b IS NOT NULL AND GREATEST(skjult_a, skjult_b) < %s)
            OR siste_melding < %s
         LIMIT 1000",
        $skjult_grense,
        $alder_grense
    )));

    $resultat = array('traader' => count($ider), 'meldinger' => 0, 'varsler' => 0, 'torr' => (bool) $torr);
    if (!$ider) {
        return $resultat;
    }

    $liste = implode(',', $ider); // Bare heltall fra databasen.
    $resultat['meldinger'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$meldinger} WHERE traad_id IN ({$liste})");

    // Varsler: payload starter alltid med traad_id (se bv_meldinger_send).
    $varsel_tabell = function_exists('bv_varsler_tabell') ? bv_varsler_tabell() : '';
    $varsel_vilkar = array();
    foreach ($ider as $id) {
        $varsel_vilkar[] = $wpdb->prepare('payload LIKE %s', '{"traad_id":' . $id . ',%');
    }
    $varsel_where = "type = 'ny_melding' AND (" . implode(' OR ', $varsel_vilkar) . ')';
    if ($varsel_tabell !== '') {
        $resultat['varsler'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$varsel_tabell} WHERE {$varsel_where}");
    }

    if ($torr) {
        return $resultat;
    }

    $wpdb->query("DELETE FROM {$meldinger} WHERE traad_id IN ({$liste})");
    if ($varsel_tabell !== '') {
        $wpdb->query("DELETE FROM {$varsel_tabell} WHERE {$varsel_where}");
    }
    $wpdb->query("DELETE FROM {$traader} WHERE id IN ({$liste})");

    error_log(sprintf(
        '[bv-meldinger] Opprydding: slettet %d samtaler, %d meldinger, %d varsler.',
        $resultat['traader'], $resultat['meldinger'], $resultat['varsler']
    ));
    return $resultat;
}

add_action('bv_meldinger_opprydding', function () {
    bv_meldinger_opprydding(false);
});

add_action('init', function () {
    if (!wp_next_scheduled('bv_meldinger_opprydding')) {
        wp_schedule_event(strtotime('tomorrow 02:30 UTC'), 'daily', 'bv_meldinger_opprydding');
    }
}, 20);

/* =========================================================================
 * E-POST (B-030, bak fail-closed gate)
 * ========================================================================= */

function bv_meldinger_epost_gate_apen() {
    return get_option('bimverdi_meldinger_epost_apen') === '1';
}

/**
 * Send e-postvarsel om ny melding. Uten meldingstekst (krav 31).
 * Maks én e-post per samtale og mottaker per 30 min.
 *
 * @return bool Sendt.
 */
function bv_meldinger_epost($mottaker_id, $avsender_id, $traad_id) {
    $mottaker = get_userdata($mottaker_id);
    if (!$mottaker || !is_email($mottaker->user_email)) {
        return false;
    }

    if (!bv_meldinger_epost_gate_apen() && !user_can($mottaker, 'manage_options')) {
        error_log(sprintf('[bv-meldinger] GATE LÅST: hoppet over e-post til bruker %d (tråd %d).', $mottaker_id, $traad_id));
        return false;
    }

    $pause_nokkel = 'bv_meld_epost_' . (int) $traad_id . '_' . (int) $mottaker_id;
    if (get_transient($pause_nokkel)) {
        error_log(sprintf('[bv-meldinger] PAUSE: e-post til bruker %d for tråd %d sendt siste 30 min.', $mottaker_id, $traad_id));
        return false;
    }

    if (!function_exists('bimverdi_epost_dokument')) {
        error_log('[bv-meldinger] FEILET: bimverdi_epost_dokument() mangler.');
        return false;
    }

    // Avsendernavn er brukerstyrt: fjern linjeskift i emnet (header-injection).
    $navn    = trim(preg_replace('/[\r\n]+/', ' ', html_entity_decode(bv_meldinger_navn($avsender_id), ENT_QUOTES, 'UTF-8')));
    $foretak = bv_meldinger_foretaksnavn($avsender_id);
    $emne    = sprintf('Ny lukket melding fra %s på bimverdi.no', $navn);

    $fra = $foretak !== ''
        ? sprintf('%s i %s', esc_html($navn), esc_html($foretak))
        : esc_html($navn);

    $innhold = '<p style="margin: 0 0 16px 0; font-size: 16px; line-height: 1.6;">Hei ' . esc_html(bv_meldinger_navn($mottaker_id)) . ',</p>'
        . '<p style="margin: 0 0 16px 0; font-size: 16px; line-height: 1.6;">Du har fått en lukket melding fra ' . $fra . '. Les og svar på Min Side.</p>';

    $footer = '<p style="margin: 0;">Du får denne e-posten fordi noen sendte deg en lukket melding på bimverdi.no. '
        . 'Vil du ikke ta imot nye samtaler, kan du slå det av på '
        . '<a href="' . esc_url(home_url('/min-side/profil/rediger/')) . '" style="color: #6B6B6B;">profilen din</a>.</p>';

    $html = bimverdi_epost_dokument(array(
        'tittel'    => $emne,
        'innhold'   => $innhold,
        'cta_url'   => bv_meldinger_traad_url($traad_id),
        'cta_tekst' => 'Les meldingen',
        'footer'    => $footer,
    ));

    $ok = wp_mail($mottaker->user_email, $emne, $html, array('Content-Type: text/html; charset=UTF-8'));

    if ($ok) {
        set_transient($pause_nokkel, 1, BIMVERDI_MELDINGER_EPOST_PAUSE);
        error_log(sprintf('[bv-meldinger] SENDT e-post til bruker %d for tråd %d.', $mottaker_id, $traad_id));
    } else {
        error_log(sprintf('[bv-meldinger] FEILET e-post til bruker %d for tråd %d — meldingen er lagret uansett.', $mottaker_id, $traad_id));
    }
    return $ok;
}

/* =========================================================================
 * REST: bimverdi/v1/meldinger
 * ========================================================================= */

add_action('rest_api_init', function () {
    $innlogget = function () {
        return is_user_logged_in()
            ? true
            : new WP_Error('rest_forbidden', bv_meldinger_feiltekst('ikke_innlogget'), array('status' => 401));
    };

    register_rest_route('bimverdi/v1', '/meldinger', array(
        array(
            'methods'             => 'GET',
            'permission_callback' => $innlogget,
            'callback'            => function () {
                return rest_ensure_response(bv_meldinger_traader(get_current_user_id()));
            },
        ),
        array(
            'methods'             => 'POST',
            'permission_callback' => $innlogget,
            'args'                => array(
                'mottaker_id' => array('required' => true, 'type' => 'integer'),
                'tekst'       => array('required' => true, 'type' => 'string'),
            ),
            'callback'            => function (WP_REST_Request $req) {
                $res = bv_meldinger_send(get_current_user_id(), (int) $req['mottaker_id'], (string) $req['tekst']);
                return is_wp_error($res) ? $res : new WP_REST_Response($res, 201);
            },
        ),
    ));

    register_rest_route('bimverdi/v1', '/meldinger/mottakere', array(
        'methods'             => 'GET',
        'permission_callback' => $innlogget,
        'args'                => array(
            'sok' => array('required' => true, 'type' => 'string'),
        ),
        'callback'            => function (WP_REST_Request $req) {
            $bruker = get_current_user_id();
            if (!bv_meldinger_kan_starte($bruker)) {
                return bv_meldinger_feil('ikke_lansert', 403);
            }
            return rest_ensure_response(bv_meldinger_sok_mottakere($bruker, (string) $req['sok']));
        },
    ));

    register_rest_route('bimverdi/v1', '/meldinger/(?P<id>\d+)/skjul', array(
        'methods'             => 'POST',
        'permission_callback' => $innlogget,
        'callback'            => function (WP_REST_Request $req) {
            $res = bv_meldinger_skjul(get_current_user_id(), (int) $req['id']);
            return is_wp_error($res) ? $res : rest_ensure_response(array('skjult' => true));
        },
    ));

    register_rest_route('bimverdi/v1', '/meldinger/uleste', array(
        'methods'             => 'GET',
        'permission_callback' => $innlogget,
        'callback'            => function () {
            return rest_ensure_response(array('uleste' => bv_meldinger_antall_uleste(get_current_user_id())));
        },
    ));

    register_rest_route('bimverdi/v1', '/meldinger/(?P<id>\d+)', array(
        array(
            'methods'             => 'GET',
            'permission_callback' => $innlogget,
            'callback'            => function (WP_REST_Request $req) {
                $bruker = get_current_user_id();
                $traad  = bv_meldinger_hent_traad((int) $req['id']);
                if (!bv_meldinger_er_deltaker($traad, $bruker)) {
                    return bv_meldinger_feil('fant_ikke_traad', 404);
                }
                bv_meldinger_marker_lest($bruker, (int) $traad->id);
                $motpart = bv_meldinger_motpart($traad, $bruker);
                return rest_ensure_response(array(
                    'traad_id'        => (int) $traad->id,
                    'motpart_id'      => $motpart,
                    'motpart_navn'    => bv_meldinger_navn($motpart),
                    'motpart_foretak' => bv_meldinger_foretaksnavn($motpart),
                    'meldinger'       => bv_meldinger_i_traad((int) $traad->id),
                ));
            },
        ),
        array(
            'methods'             => 'POST',
            'permission_callback' => $innlogget,
            'args'                => array(
                'tekst' => array('required' => true, 'type' => 'string'),
            ),
            'callback'            => function (WP_REST_Request $req) {
                $res = bv_meldinger_svar(get_current_user_id(), (int) $req['id'], (string) $req['tekst']);
                return is_wp_error($res) ? $res : new WP_REST_Response($res, 201);
            },
        ),
    ));
});

/* =========================================================================
 * SKJEMA (svar fra samtalesiden, og innstillingen på profilen)
 * ========================================================================= */

/**
 * Svarskjemaet på /min-side/meldinger/traad/ poster hit (vanlig POST, ingen JS).
 * Ved feil beholdes teksten i et kortlivet utkast og vises igjen i feltet.
 */
add_action('template_redirect', function () {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($_POST['bv_melding_svar'])) {
        return;
    }

    $traad_id = (int) ($_POST['traad_id'] ?? 0);
    $tilbake  = bv_meldinger_traad_url($traad_id);

    if (!is_user_logged_in()) {
        wp_safe_redirect(home_url('/logg-inn/'));
        exit;
    }
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bv_melding_svar_' . $traad_id)) {
        wp_safe_redirect(add_query_arg('bv_feil', 'utlopt', $tilbake));
        exit;
    }

    $bruker = get_current_user_id();
    $res    = bv_meldinger_svar($bruker, $traad_id, $_POST['tekst'] ?? '');

    if (is_wp_error($res)) {
        set_transient('bv_meld_utkast_' . $bruker . '_' . $traad_id, wp_unslash((string) ($_POST['tekst'] ?? '')), 10 * MINUTE_IN_SECONDS);
        $kode = str_replace('bv_melding_', '', $res->get_error_code());
        wp_safe_redirect(add_query_arg('bv_feil', rawurlencode($kode), $tilbake));
        exit;
    }

    delete_transient('bv_meld_utkast_' . $bruker . '_' . $traad_id);
    wp_safe_redirect(add_query_arg('sendt', '1', $tilbake) . '#siste');
    exit;
});

/**
 * «Ny lukket melding» (/min-side/meldinger/ny/?til=ID) poster hit.
 */
add_action('template_redirect', function () {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($_POST['bv_melding_ny'])) {
        return;
    }

    $mottaker_id = (int) ($_POST['til'] ?? 0);
    $tilbake     = bv_meldinger_ny_url($mottaker_id);

    if (!is_user_logged_in()) {
        wp_safe_redirect(home_url('/logg-inn/?redirect_to=' . rawurlencode($tilbake)));
        exit;
    }
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bv_melding_ny_' . $mottaker_id)) {
        wp_safe_redirect(add_query_arg('bv_feil', 'utlopt', $tilbake));
        exit;
    }

    $bruker = get_current_user_id();
    $res    = bv_meldinger_send($bruker, $mottaker_id, $_POST['tekst'] ?? '');

    if (is_wp_error($res)) {
        set_transient('bv_meld_utkast_ny_' . $bruker . '_' . $mottaker_id, wp_unslash((string) ($_POST['tekst'] ?? '')), 10 * MINUTE_IN_SECONDS);
        $kode = str_replace('bv_melding_', '', $res->get_error_code());
        wp_safe_redirect(add_query_arg('bv_feil', rawurlencode($kode), $tilbake));
        exit;
    }

    delete_transient('bv_meld_utkast_ny_' . $bruker . '_' . $mottaker_id);
    wp_safe_redirect(add_query_arg('sendt', '1', bv_meldinger_traad_url($res['traad_id'])) . '#siste');
    exit;
});

/**
 * Bekreftet «Skjul samtale» poster hit.
 */
add_action('template_redirect', function () {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($_POST['bv_melding_skjul'])) {
        return;
    }
    $traad_id = (int) ($_POST['traad_id'] ?? 0);
    if (!is_user_logged_in()) {
        wp_safe_redirect(home_url('/logg-inn/'));
        exit;
    }
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bv_melding_skjul_' . $traad_id)) {
        wp_safe_redirect(add_query_arg('bv_feil', 'utlopt', bv_meldinger_traad_url($traad_id)));
        exit;
    }
    $res = bv_meldinger_skjul(get_current_user_id(), $traad_id);
    $url = home_url('/min-side/meldinger/');
    wp_safe_redirect(is_wp_error($res) ? $url : add_query_arg('skjult', '1', $url));
    exit;
});

/**
 * /min-side/meldinger/ny/?til=ID: finnes samtalen allerede, gå rett til den.
 */
add_action('template_redirect', function () {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET'
        || get_query_var('minside_route') !== 'meldinger/ny'
        || empty($_GET['til'])
        || isset($_GET['bv_feil'])
        || !is_user_logged_in()) {
        return;
    }
    $bruker = get_current_user_id();
    $traad  = bv_meldinger_traad_for_par($bruker, (int) $_GET['til']);
    if ($traad && (int) $_GET['til'] !== $bruker) {
        wp_safe_redirect(bv_meldinger_traad_url($traad->id) . '#siste');
        exit;
    }
});

/**
 * Marker samtalen som lest før headeren tegnes, så ulest-tallet i menyen
 * stemmer allerede på samme sidevisning.
 */
add_action('template_redirect', function () {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET'
        || get_query_var('minside_route') !== 'meldinger/traad'
        || empty($_GET['id'])
        || !is_user_logged_in()) {
        return;
    }
    $bruker = get_current_user_id();
    $traad  = bv_meldinger_hent_traad((int) $_GET['id']);
    if (bv_meldinger_er_deltaker($traad, $bruker)) {
        bv_meldinger_marker_lest($bruker, (int) $traad->id);
    }
});

/**
 * «Ta imot meldinger» på /min-side/profil/rediger/. Kjøres før profilskjemaets
 * egen handler (bimverdi-profile-edit.php, prioritet 10), som avslutter med redirect.
 * Skjulefeltet bv_meldinger_innstilling viser at avkrysningen var med i skjemaet,
 * slik at en tom avkrysning betyr «av» og ikke «feltet manglet».
 */
add_action('template_redirect', function () {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
        || !isset($_POST['bimverdi_edit_profile'], $_POST['bv_meldinger_innstilling'])
        || !is_user_logged_in()
        || !wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bimverdi_edit_profile')) {
        return;
    }
    update_user_meta(get_current_user_id(), 'bv_ta_imot_meldinger', empty($_POST['bv_ta_imot_meldinger']) ? '0' : '1');
}, 5);
