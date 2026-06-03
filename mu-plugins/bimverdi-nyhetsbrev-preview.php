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

    if (!function_exists('bimverdi_render_nyhetsbrev')) {
        wp_die('Nyhetsbrev-innholdsfunksjonene er ikke lastet (bimverdi-nyhetsbrev-content.php).');
    }

    $html = bimverdi_render_nyhetsbrev();

    nocache_headers();
    header('Content-Type: text/html; charset=UTF-8');
    echo $html; // Allerede escaped i malen.
    exit;
});
