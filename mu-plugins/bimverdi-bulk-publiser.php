<?php
/**
 * Plugin Name: BIM Verdi - Bulk-publisering av verktøy og artikler
 * Description: Legger «Publiser» inn i bulk-menyen på verktøy- og artikkellista i wp-admin.
 * Version: 1.0.0
 *
 * Bård, Trello #362 punkt 8 og 9.3: wp-admin tilbyr bare «Rediger» som
 * bulk-handling, så hvert utkast måtte åpnes og godkjennes for seg. Her får
 * han «Publiser» som samlet handling.
 *
 * Handlingen går via wp_update_post(), så de vanlige publiseringskoblingene
 * virker som ved enkeltpublisering. For artikler betyr det at «Artikkelen din
 * er publisert»-varselet går ut til forfatteren (transition_post_status
 * pending → publish, bimverdi-artikkel-redigering.php). Verktøy har ingen
 * publiseringsvarsel.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Posttyper som får handlingen, og hvilke statuser som kan publiseres.
 */
function bimverdi_bulk_publiser_posttyper() {
    return array('verktoy', 'artikkel');
}

foreach (bimverdi_bulk_publiser_posttyper() as $bv_posttype) {
    add_filter('bulk_actions-edit-' . $bv_posttype, 'bimverdi_bulk_publiser_meny');
    add_filter('handle_bulk_actions-edit-' . $bv_posttype, 'bimverdi_bulk_publiser_utfor', 10, 3);
}
unset($bv_posttype);

function bimverdi_bulk_publiser_meny($handlinger) {
    $handlinger['bv_publiser'] = 'Publiser';
    return $handlinger;
}

/**
 * Publiser valgte utkast og ventende poster. Alt annet hoppes over.
 * WordPress har allerede sjekket nonce før dette filteret kjører.
 */
function bimverdi_bulk_publiser_utfor($redirect_url, $handling, $post_ids) {
    if ($handling !== 'bv_publiser') {
        return $redirect_url;
    }

    $publisert = 0;
    $hoppet_over = 0;

    foreach ((array) $post_ids as $post_id) {
        $post_id = (int) $post_id;
        $status = get_post_status($post_id);

        if (!in_array($status, array('draft', 'pending'), true) || !current_user_can('publish_post', $post_id)) {
            $hoppet_over++;
            continue;
        }

        $resultat = wp_update_post(array('ID' => $post_id, 'post_status' => 'publish'), true);
        if (is_wp_error($resultat)) {
            $hoppet_over++;
            continue;
        }
        $publisert++;
    }

    return add_query_arg(
        array('bv_publisert' => $publisert, 'bv_hoppet_over' => $hoppet_over),
        remove_query_arg(array('bv_publisert', 'bv_hoppet_over'), $redirect_url)
    );
}

add_action('admin_notices', 'bimverdi_bulk_publiser_melding');
function bimverdi_bulk_publiser_melding() {
    if (!isset($_GET['bv_publisert'])) {
        return;
    }
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !in_array($screen->post_type, bimverdi_bulk_publiser_posttyper(), true)) {
        return;
    }

    $publisert = (int) $_GET['bv_publisert'];
    $hoppet_over = (int) ($_GET['bv_hoppet_over'] ?? 0);

    $tekst = $publisert === 1 ? '1 post er publisert.' : $publisert . ' poster er publisert.';
    if ($hoppet_over > 0) {
        $tekst .= ' ' . $hoppet_over . ' ble hoppet over (ikke utkast eller venter, eller mangler tilgang).';
    }

    printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html($tekst));
}
