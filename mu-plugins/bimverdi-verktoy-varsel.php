<?php
/**
 * Plugin Name: BIM Verdi — Varsel om nytt verktøy
 * Description: Sender e-post til redaksjonen når en deltaker registrerer et verktøy fra Min side.
 * Version: 1.0.0
 *
 * Bakgrunn (Bård, møte 17.09.2026, Trello uke 38 punkt 4c): Smart Innovation
 * registrerte et verktøy, ingen fikk beskjed, og Bård klaget til dem på at de
 * ikke hadde sendt inn — før han fant registreringen liggende som kladd og
 * vente på hans egen godkjenning. Verktøy fra Min side opprettes med
 * post_status «draft» (bimverdi-tool-registration.php), så uten et varsel er
 * det ingenting som forteller redaksjonen at noe ligger og venter.
 *
 * Varselet henger på en egen action, ikke på wp_insert_post/transition_post_status.
 * Det er med vilje: AEC-synkroniseringen rører ~1900 hub-verktøy hver uke, og
 * en generisk post-hook ville sendt en e-post per verktøy. Her kan bare
 * registreringsskjemaet utløse varselet.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hvem varselet går til.
 *
 * @return string[] E-postadresser.
 */
function bimverdi_verktoy_varsel_mottakere() {
    $mottakere = apply_filters('bimverdi_verktoy_varsel_mottakere', array('post@bimverdi.no'));

    $rene = array();
    foreach ((array) $mottakere as $adresse) {
        $adresse = sanitize_email((string) $adresse);
        if ($adresse && is_email($adresse)) {
            $rene[] = $adresse;
        }
    }

    return array_values(array_unique($rene));
}

/**
 * Send varsel om at et verktøy er registrert og venter på godkjenning.
 *
 * @param int $verktoy_id Verktøy-post-ID.
 * @param int $bruker_id  Brukeren som registrerte det.
 * @return bool True hvis minst én e-post ble levert til wp_mail().
 */
function bimverdi_verktoy_varsel_send($verktoy_id, $bruker_id = 0) {
    $verktoy_id = (int) $verktoy_id;
    $verktoy    = $verktoy_id ? get_post($verktoy_id) : null;
    if (!$verktoy) {
        return false;
    }

    $mottakere = bimverdi_verktoy_varsel_mottakere();
    if (!$mottakere) {
        return false;
    }

    $navn = get_the_title($verktoy_id);
    if ($navn === '' && function_exists('get_field')) {
        $navn = (string) get_field('verktoy_navn', $verktoy_id);
    }
    $navn = $navn !== '' ? $navn : 'Uten navn';

    // Hvem står bak: personen som fylte ut skjemaet, og foretaket verktøyet
    // tilskrives. De er ofte, men ikke alltid, samme organisasjon.
    $bruker_id = $bruker_id ? (int) $bruker_id : (int) $verktoy->post_author;
    $bruker    = $bruker_id ? get_userdata($bruker_id) : null;
    $person    = $bruker ? $bruker->display_name : 'ukjent bruker';
    $person_epost = $bruker ? $bruker->user_email : '';

    $foretak_navn = '';
    if (function_exists('get_field')) {
        $eier = get_field('eier_leverandor', $verktoy_id);
        $eier_id = is_object($eier) ? (int) ($eier->ID ?? 0) : (int) $eier;
        if ($eier_id) {
            $foretak_navn = get_the_title($eier_id);
        }
    }

    $beskrivelse = function_exists('get_field')
        ? (string) get_field('kort_beskrivelse', $verktoy_id)
        : '';
    $beskrivelse = wp_trim_words(wp_strip_all_tags($beskrivelse), 40);

    $rediger_url = admin_url('post.php?post=' . $verktoy_id . '&action=edit');
    $venter      = ($verktoy->post_status !== 'publish');

    $rader = array(
        'Verktøy'   => $navn,
        'Registrert av' => $person . ($person_epost ? ' (' . $person_epost . ')' : ''),
        'Foretak'   => $foretak_navn !== '' ? $foretak_navn : '— ikke satt —',
        'Status'    => $venter ? 'Kladd — venter på din godkjenning' : 'Publisert',
    );

    $tabell = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;margin:0 0 20px;">';
    foreach ($rader as $etikett => $verdi) {
        $tabell .= '<tr>'
            . '<td style="padding:6px 12px 6px 0;font-family:Inter,Arial,sans-serif;font-size:14px;color:#5A5A5A;white-space:nowrap;vertical-align:top;">'
            . esc_html($etikett) . '</td>'
            . '<td style="padding:6px 0;font-family:Inter,Arial,sans-serif;font-size:14px;color:#1A1A1A;vertical-align:top;">'
            . esc_html($verdi) . '</td>'
            . '</tr>';
    }
    $tabell .= '</table>';

    $innhold = bimverdi_epost_avsnitt(
        $venter
            ? 'Et nytt verktøy er registrert fra Min side og ligger som kladd. Det blir ikke synlig på bimverdi.no før du publiserer det.'
            : 'Et nytt verktøy er registrert fra Min side.'
    );
    $innhold .= $tabell;
    if ($beskrivelse !== '') {
        $innhold .= bimverdi_epost_avsnitt(esc_html($beskrivelse), array('farge' => '#5A5A5A'));
    }

    $html = bimverdi_epost_dokument(array(
        'tittel'    => 'Nytt verktøy registrert',
        'innhold'   => $innhold,
        'cta_url'   => $rediger_url,
        'cta_tekst' => $venter ? 'Se over og publiser' : 'Åpne verktøyet',
        'footer'    => 'Du får denne e-posten fordi den er adressert til redaksjonen for bimverdi.no.',
    ));

    $emne = $venter
        ? sprintf('Nytt verktøy venter på godkjenning: %s', $navn)
        : sprintf('Nytt verktøy registrert: %s', $navn);

    $headers = array('Content-Type: text/html; charset=UTF-8');
    if ($person_epost && is_email($person_epost)) {
        $headers[] = 'Reply-To: ' . $person_epost;
    }

    $sendt = false;
    foreach ($mottakere as $mottaker) {
        $sendt = wp_mail($mottaker, $emne, $html, $headers) || $sendt;
    }

    if (!$sendt) {
        error_log(sprintf('BIMVerdi: varsel om verktøy %d (%s) kunne ikke sendes.', $verktoy_id, $navn));
    }

    return $sendt;
}

/**
 * Utløses av registreringsskjemaet i bimverdi-tool-registration.php.
 */
add_action('bimverdi_verktoy_registrert', 'bimverdi_verktoy_varsel_send', 10, 2);
