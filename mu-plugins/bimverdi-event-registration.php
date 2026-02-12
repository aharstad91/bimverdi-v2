<?php
/**
 * BIM Verdi - Internal Event Registration
 *
 * AJAX endpoint for creating/cancelling påmelding (event registration).
 * Works with the existing pamelding CPT and fires bimverdi_pamelding_created hook.
 *
 * @package BIM_Verdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX actions
 */
add_action('wp_ajax_bimverdi_register_event', 'bimverdi_ajax_register_event');
add_action('wp_ajax_bimverdi_unregister_event', 'bimverdi_ajax_unregister_event');

/**
 * Handle event registration via AJAX
 */
function bimverdi_ajax_register_event() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'bimverdi_event_registration')) {
        wp_send_json_error(['message' => 'Ugyldig forespørsel.'], 403);
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'Du må være innlogget.'], 401);
    }

    $arrangement_id = intval($_POST['arrangement_id'] ?? 0);
    if (!$arrangement_id || get_post_type($arrangement_id) !== 'arrangement') {
        wp_send_json_error(['message' => 'Ugyldig arrangement.'], 400);
    }

    // Check event is not past
    $status_toggle = get_field('arrangement_status_toggle', $arrangement_id) ?: 'kommende';
    if ($status_toggle === 'tidligere') {
        wp_send_json_error(['message' => 'Dette arrangementet er allerede avholdt.'], 400);
    }

    // Check event is not cancelled
    $status = get_field('arrangement_status', $arrangement_id) ?: 'planlagt';
    if ($status === 'avlyst') {
        wp_send_json_error(['message' => 'Dette arrangementet er avlyst.'], 400);
    }

    // Check registration deadline
    $pameldingsfrist = get_field('pameldingsfrist', $arrangement_id);
    $dato = get_field('arrangement_dato', $arrangement_id);
    if ($pameldingsfrist && strtotime($pameldingsfrist) < time()) {
        wp_send_json_error(['message' => 'Påmeldingsfristen har gått ut.'], 400);
    } elseif (!$pameldingsfrist && $dato && strtotime($dato) < strtotime('today')) {
        wp_send_json_error(['message' => 'Påmeldingsfristen har gått ut.'], 400);
    }

    // Check access control (adgang)
    $adgang = get_field('adgang', $arrangement_id) ?: 'deltakere';
    $access_check = bimverdi_check_event_access($user_id, $adgang);
    if (!$access_check['allowed']) {
        wp_send_json_error(['message' => $access_check['message']], 403);
    }

    // Check for duplicate registration
    $existing = bimverdi_get_user_registration($user_id, $arrangement_id);
    if ($existing) {
        wp_send_json_error(['message' => 'Du er allerede påmeldt dette arrangementet.'], 400);
    }

    // Check capacity
    $maks_deltakere = get_field('maks_deltakere', $arrangement_id);
    $registration_count = bimverdi_get_registration_count($arrangement_id);
    $is_waitlist = false;

    if ($maks_deltakere && $registration_count >= intval($maks_deltakere)) {
        $is_waitlist = true;
    }

    // Create registration
    $pamelding_status = $is_waitlist ? 'venteliste' : 'bekreftet';

    $pamelding_id = wp_insert_post([
        'post_type'   => 'pamelding',
        'post_status' => 'publish',
        'post_title'  => sprintf(
            'Påmelding: %s – %s',
            get_the_title($arrangement_id),
            get_userdata($user_id)->display_name
        ),
    ]);

    if (is_wp_error($pamelding_id)) {
        wp_send_json_error(['message' => 'Kunne ikke opprette påmelding. Prøv igjen.'], 500);
    }

    // Set ACF fields
    update_field('bruker', $user_id, $pamelding_id);
    update_field('arrangement', $arrangement_id, $pamelding_id);
    update_field('pamelding_status', $pamelding_status, $pamelding_id);

    // Also set with alternate field names for backwards compatibility
    update_field('pamelding_bruker', $user_id, $pamelding_id);
    update_field('pamelding_arrangement', $arrangement_id, $pamelding_id);

    // Fire hook for ICS email etc.
    do_action('bimverdi_pamelding_created', $pamelding_id, $arrangement_id, $user_id);

    $message = $is_waitlist
        ? 'Du er satt på venteliste for dette arrangementet.'
        : 'Du er påmeldt! Sjekk e-posten din for bekreftelse og kalenderfil.';

    wp_send_json_success([
        'message'         => $message,
        'status'          => $pamelding_status,
        'registration_id' => $pamelding_id,
    ]);
}

/**
 * Handle event unregistration via AJAX
 */
function bimverdi_ajax_unregister_event() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'bimverdi_event_registration')) {
        wp_send_json_error(['message' => 'Ugyldig forespørsel.'], 403);
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'Du må være innlogget.'], 401);
    }

    $arrangement_id = intval($_POST['arrangement_id'] ?? 0);
    if (!$arrangement_id) {
        wp_send_json_error(['message' => 'Ugyldig arrangement.'], 400);
    }

    // Find user's registration
    $registration = bimverdi_get_user_registration($user_id, $arrangement_id);
    if (!$registration) {
        wp_send_json_error(['message' => 'Du er ikke påmeldt dette arrangementet.'], 400);
    }

    // Check cancellation deadline
    if (function_exists('bimverdi_can_cancel_registration') && !bimverdi_can_cancel_registration($arrangement_id)) {
        wp_send_json_error(['message' => 'Avmeldingsfristen har passert.'], 400);
    }

    // Delete registration
    wp_delete_post($registration, true);

    wp_send_json_success([
        'message' => 'Du er nå avmeldt arrangementet.',
    ]);
}

/**
 * Check if user has access to register for an event
 *
 * @param int    $user_id
 * @param string $adgang  'alle', 'registrerte', 'deltakere', or legacy 'medlemmer'
 * @return array ['allowed' => bool, 'message' => string]
 */
function bimverdi_check_event_access($user_id, $adgang) {
    // 'alle' - anyone logged in can register
    if ($adgang === 'alle') {
        return ['allowed' => true, 'message' => ''];
    }

    // 'registrerte' - must be logged in (any role)
    if ($adgang === 'registrerte') {
        if (!$user_id) {
            return [
                'allowed' => false,
                'message' => 'Du må ha en registrert brukerkonto for å melde deg på.',
            ];
        }
        return ['allowed' => true, 'message' => ''];
    }

    // 'deltakere' or legacy 'medlemmer' - must have company with paying role
    if ($adgang === 'deltakere' || $adgang === 'medlemmer') {
        if (!function_exists('bimverdi_user_has_company') || !bimverdi_user_has_company($user_id)) {
            return [
                'allowed' => false,
                'message' => 'Dette arrangementet er for deltakere i BIM Verdi. Koble foretaket ditt for tilgang.',
            ];
        }

        // Check that the company has a paying role
        $company = bimverdi_get_user_company($user_id);
        if ($company) {
            $company_id = is_array($company) ? ($company['id'] ?? $company['ID'] ?? 0) : $company;
            $bv_rolle = get_field('bv_rolle', $company_id);
            if (!$bv_rolle || $bv_rolle === 'Ikke deltaker') {
                return [
                    'allowed' => false,
                    'message' => 'Dette arrangementet er for betalende deltakere i BIM Verdi.',
                ];
            }
        }

        return ['allowed' => true, 'message' => ''];
    }

    // Unknown adgang value - allow by default
    return ['allowed' => true, 'message' => ''];
}

/**
 * Get a user's registration for an arrangement
 *
 * @param int $user_id
 * @param int $arrangement_id
 * @return int|false Registration post ID or false
 */
function bimverdi_get_user_registration($user_id, $arrangement_id) {
    $registrations = get_posts([
        'post_type'      => 'pamelding',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'relation' => 'OR',
                [
                    'key'   => 'bruker',
                    'value' => $user_id,
                ],
                [
                    'key'   => 'pamelding_bruker',
                    'value' => $user_id,
                ],
            ],
            [
                'relation' => 'OR',
                [
                    'key'   => 'arrangement',
                    'value' => $arrangement_id,
                ],
                [
                    'key'   => 'pamelding_arrangement',
                    'value' => $arrangement_id,
                ],
            ],
        ],
    ]);

    return !empty($registrations) ? $registrations[0] : false;
}

/**
 * Count confirmed registrations for an arrangement
 *
 * @param int $arrangement_id
 * @return int
 */
function bimverdi_get_registration_count($arrangement_id) {
    $registrations = get_posts([
        'post_type'      => 'pamelding',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'relation' => 'OR',
                [
                    'key'   => 'arrangement',
                    'value' => $arrangement_id,
                ],
                [
                    'key'   => 'pamelding_arrangement',
                    'value' => $arrangement_id,
                ],
            ],
            [
                'key'     => 'pamelding_status',
                'value'   => 'bekreftet',
                'compare' => '=',
            ],
        ],
    ]);

    return count($registrations);
}

/**
 * Enqueue registration script on single arrangement pages
 */
add_action('wp_enqueue_scripts', function () {
    if (!is_singular('arrangement')) {
        return;
    }

    wp_localize_script('jquery', 'bimverdiEventReg', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('bimverdi_event_registration'),
    ]);
});
