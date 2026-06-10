<?php
/**
 * BIM Verdi - Nyhetsbrev forhåndsvisning (kun admin)
 *
 * Lar Bård/admin se den rendrede nyhetsbrev-malen mot ekte data i nettleseren,
 * uten å sende noe. Åpne:
 *
 *   /?bimverdi_nyhetsbrev_preview=1
 *
 * Krever `manage_options` (administrator). Andre brukere/gjester ser ingenting
 * spesielt — vanlig sidevisning fortsetter — så ruten er trygg i prod.
 *
 * Dette er et midlertidig fase 1-verktøy (forhåndsvisning). Selve utsendelsen
 * (cron/Resend) kommer i et eget steg.
 *
 * Plan: docs/plans/2026-06-03-001-feat-nyhetsbrev-mal-og-utsendelse-plan.md (1B/1C)
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('template_redirect', function () {
    if (!isset($_GET['bimverdi_nyhetsbrev_preview'])) {
        return;
    }

    // Kun administrator får se forhåndsvisningen.
    if (!current_user_can('manage_options')) {
        return;
    }

    $param = $_GET['bimverdi_nyhetsbrev_preview'];

    // Numerisk verdi = vis det LAGREDE øyeblikksbildet til et bestemt nyhetsbrev
    // (nøyaktig det som vil bli sendt). «1» / ikke-numerisk = live-render.
    if (is_numeric($param)) {
        $post_id = (int) $param;
        if ($post_id > 0 && get_post_type($post_id) === 'nyhetsbrev') {
            $stored = get_post_meta($post_id, '_bv_nyhetsbrev_html', true);
            if ($stored !== '') {
                nocache_headers();
                header('Content-Type: text/html; charset=UTF-8');
                // Lagret HTML echoes rått. Malen (parts/email/nyhetsbrev.php) er
                // ENESTE escaping-grense (esc_html/esc_url/esc_attr på alt dynamisk).
                // Legger du til et rikt-tekst/HTML-felt i malen: kjør det via wp_kses_post.
                echo $stored;
                exit;
            }
        }
    }

    if (!function_exists('bimverdi_render_nyhetsbrev')) {
        wp_die('Nyhetsbrev-innholdsfunksjonene er ikke lastet (bimverdi-nyhetsbrev-content.php).');
    }

    $html = bimverdi_render_nyhetsbrev();

    nocache_headers();
    header('Content-Type: text/html; charset=UTF-8');
    echo $html; // Allerede escaped i malen.
    exit;
});
