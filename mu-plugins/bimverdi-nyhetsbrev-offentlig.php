<?php
/**
 * BIM Verdi - Nyhetsbrev: skjult, delbar nettversjon
 *
 * Bård vil kunne sende et nyhetsbrev som eksempel til folk som ikke er
 * medlemmer (møte 20.10.2026). Nyhetsbrev-typen er ikke offentlig, og
 * forhåndsvisningen krever admin-innlogging, så her lages en egen lenke:
 *
 *   https://bimverdi.no/nyhetsbrev/<32 tegn tilfeldig kode>/
 *
 * - Lenken finnes bare hvis en admin trykker «Lag delbar lenke» i sidepanelet,
 *   og kan slås av igjen (koden slettes, gammel lenke gir 404).
 * - Viser nøyaktig det lagrede øyeblikksbildet (samme HTML som ble/blir sendt).
 * - Per-mottaker-avmeldingslenken fjernes: den peker til forsiden, slik at ingen
 *   som får lenken kan melde av et medlem.
 * - noindex (header + meta), ikke listet noe sted.
 * - Ingen rewrite-regler: adressen fanges i template_redirect, så ingen flush.
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

const BV_NB_META_OFFENTLIG = '_bv_nyhetsbrev_offentlig_token';

/** Delbar URL for et nyhetsbrev, eller tom streng hvis lenken ikke er laget. */
function bimverdi_nyhetsbrev_offentlig_url($post_id) {
    $token = get_post_meta((int) $post_id, BV_NB_META_OFFENTLIG, true);
    return $token ? home_url('/nyhetsbrev/' . $token . '/') : '';
}

/** Lag (eller gjenbruk) delbar lenke. Returnerer token. */
function bimverdi_nyhetsbrev_offentlig_opprett($post_id) {
    $post_id = (int) $post_id;
    $token   = get_post_meta($post_id, BV_NB_META_OFFENTLIG, true);
    if (!$token) {
        $token = bin2hex(random_bytes(16));
        update_post_meta($post_id, BV_NB_META_OFFENTLIG, $token);
    }
    return $token;
}

/** Slå av delbar lenke. Gammel adresse gir deretter vanlig 404. */
function bimverdi_nyhetsbrev_offentlig_fjern($post_id) {
    delete_post_meta((int) $post_id, BV_NB_META_OFFENTLIG);
}

/**
 * Gjør øyeblikksbildet trygt å vise offentlig: avmeldingslenken (med
 * %%BV_UID%%/%%BV_TOKEN%%) peker til forsiden, og ingen placeholdere blir igjen.
 */
function bimverdi_nyhetsbrev_offentlig_html($html) {
    $html = preg_replace(
        '/href="[^"]*bv_nb_avmeld=%%BV_UID%%[^"]*"/',
        'href="' . esc_url(home_url('/')) . '"',
        $html
    );
    $html = str_replace(array('%%BV_UID%%', '%%BV_TOKEN%%'), '', $html);

    // noindex også i selve dokumentet (i tillegg til X-Robots-Tag).
    $meta = '<meta name="robots" content="noindex,nofollow">';
    if (stripos($html, '</head>') !== false) {
        $html = preg_replace('#</head>#i', $meta . '</head>', $html, 1);
    }
    return $html;
}

add_action('template_redirect', function () {
    $sti = isset($_SERVER['REQUEST_URI']) ? wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
    if (!is_string($sti) || $sti === '') {
        return;
    }
    // Støtter WP i undermappe (localhost: /bimverdi-v2/).
    $base = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
    if ($base !== '' && strpos($sti, $base) === 0) {
        $sti = substr($sti, strlen($base));
    }
    if (!preg_match('#^nyhetsbrev/([a-f0-9]{32})$#', trim($sti, '/'), $m)) {
        return;
    }
    $token = $m[1];

    $ider = get_posts(array(
        'post_type'      => 'nyhetsbrev',
        'post_status'    => 'any',
        'meta_key'       => BV_NB_META_OFFENTLIG,
        'meta_value'     => $token,
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ));
    if (!$ider) {
        return; // Ukjent eller avslått kode: vanlig 404.
    }

    $html = get_post_meta((int) $ider[0], '_bv_nyhetsbrev_html', true);
    if (!is_string($html) || $html === '') {
        return;
    }

    status_header(200);
    nocache_headers();
    header('X-Robots-Tag: noindex, nofollow');
    header('Content-Type: text/html; charset=UTF-8');
    // Lagret HTML echoes rått, som i forhåndsvisningen: malen er eneste
    // escaping-grense (se bimverdi-nyhetsbrev-preview.php).
    echo bimverdi_nyhetsbrev_offentlig_html($html);
    exit;
}, 1);

/* -------------------------------------------------------------------------
 * Admin-handlinger (knappene i sidepanelet «Nyhetsbrev»)
 * ---------------------------------------------------------------------- */

foreach (array('opprett', 'fjern') as $handling) {
    add_action('admin_post_bimverdi_nyhetsbrev_offentlig_' . $handling, function () use ($handling) {
        if (!current_user_can('manage_options')) {
            wp_die('Ingen tilgang.');
        }
        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        check_admin_referer('bimverdi_nyhetsbrev_offentlig_' . $post_id);

        if (!$post_id || get_post_type($post_id) !== 'nyhetsbrev') {
            wp_die('Ugyldig nyhetsbrev.');
        }

        if ($handling === 'opprett') {
            if (!get_post_meta($post_id, '_bv_nyhetsbrev_html', true)) {
                wp_die('Nyhetsbrevet har ikke noe øyeblikksbilde å vise.');
            }
            bimverdi_nyhetsbrev_offentlig_opprett($post_id);
            $notice = 'offentlig_opprettet';
        } else {
            bimverdi_nyhetsbrev_offentlig_fjern($post_id);
            $notice = 'offentlig_fjernet';
        }

        wp_safe_redirect(add_query_arg('bv_nb_notice', $notice, admin_url('post.php?post=' . $post_id . '&action=edit')));
        exit;
    });
}
