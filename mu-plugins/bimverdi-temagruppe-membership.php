<?php
/**
 * BIM Verdi - Temagruppe Membership
 *
 * AJAX endpoints for joining/leaving temagrupper on behalf of a foretak.
 * Only hovedkontakt can join/leave. Joining sets the temagruppe taxonomy
 * term on the foretak CPT post.
 *
 * @package BIM_Verdi
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_bimverdi_join_temagruppe', 'bimverdi_ajax_join_temagruppe');
add_action('wp_ajax_bimverdi_leave_temagruppe', 'bimverdi_ajax_leave_temagruppe');

/**
 * Handle joining a temagruppe via AJAX
 */
function bimverdi_ajax_join_temagruppe() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'bimverdi_temagruppe_membership')) {
        wp_send_json_error(['message' => 'Ugyldig forespørsel.'], 403);
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'Du må være innlogget.'], 401);
    }

    // Must be hovedkontakt
    if (!bimverdi_is_hovedkontakt($user_id)) {
        wp_send_json_error(['message' => 'Kun hovedkontakt kan melde foretaket inn i en temagruppe.'], 403);
    }

    $term_id = intval($_POST['term_id'] ?? 0);
    if (!$term_id || !term_exists($term_id, 'temagruppe')) {
        wp_send_json_error(['message' => 'Ugyldig temagruppe.'], 400);
    }

    // Get foretak
    $foretak_id = bimverdi_user_has_foretak($user_id);
    if (!$foretak_id) {
        wp_send_json_error(['message' => 'Fant ikke foretaket ditt.'], 400);
    }

    // Check if already a member
    if (has_term($term_id, 'temagruppe', $foretak_id)) {
        wp_send_json_error(['message' => 'Foretaket er allerede med i denne gruppen.'], 400);
    }

    // Append the term (don't replace existing terms)
    $result = wp_set_post_terms($foretak_id, [$term_id], 'temagruppe', true);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => 'Noe gikk galt. Prøv igjen.'], 500);
    }

    $foretak_name = get_the_title($foretak_id);
    $term = get_term($term_id, 'temagruppe');

    wp_send_json_success([
        'message'      => $foretak_name . ' er nå med i ' . $term->name . '.',
        'foretak_id'   => $foretak_id,
        'foretak_name' => $foretak_name,
    ]);
}

/**
 * Handle leaving a temagruppe via AJAX
 */
function bimverdi_ajax_leave_temagruppe() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'bimverdi_temagruppe_membership')) {
        wp_send_json_error(['message' => 'Ugyldig forespørsel.'], 403);
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'Du må være innlogget.'], 401);
    }

    if (!bimverdi_is_hovedkontakt($user_id)) {
        wp_send_json_error(['message' => 'Kun hovedkontakt kan melde foretaket ut av en temagruppe.'], 403);
    }

    $term_id = intval($_POST['term_id'] ?? 0);
    if (!$term_id || !term_exists($term_id, 'temagruppe')) {
        wp_send_json_error(['message' => 'Ugyldig temagruppe.'], 400);
    }

    $foretak_id = bimverdi_user_has_foretak($user_id);
    if (!$foretak_id) {
        wp_send_json_error(['message' => 'Fant ikke foretaket ditt.'], 400);
    }

    if (!has_term($term_id, 'temagruppe', $foretak_id)) {
        wp_send_json_error(['message' => 'Foretaket er ikke med i denne gruppen.'], 400);
    }

    $result = wp_remove_object_terms($foretak_id, $term_id, 'temagruppe');

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => 'Noe gikk galt. Prøv igjen.'], 500);
    }

    wp_send_json_success([
        'message' => 'Foretaket er meldt ut av gruppen.',
    ]);
}
