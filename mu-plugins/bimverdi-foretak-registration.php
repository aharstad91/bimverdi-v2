<?php
/**
 * BIM Verdi - Foretak Registration Handler
 *
 * Handles plain HTML form submission for company (foretak) registration.
 * Replaces Gravity Forms Form #2 with a direct POST handler.
 *
 * Pattern: POST-Redirect-GET (PRG)
 * - Success: /min-side/foretak/?registered=1
 * - Error:   /min-side/foretak/registrer/?bv_error=<code>
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle foretak registration form submission
 */
add_action('init', function () {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bimverdi_register_foretak'])) {
        return;
    }

    // Must be logged in
    if (!is_user_logged_in()) {
        wp_redirect(home_url('/logg-inn/'));
        exit;
    }

    $user_id = get_current_user_id();
    $redirect_error = home_url('/min-side/foretak/registrer/');

    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bimverdi_register_foretak')) {
        wp_redirect(add_query_arg('bv_error', 'nonce', $redirect_error));
        exit;
    }

    // Honeypot check
    if (!empty($_POST['bv_website_url'] ?? '')) {
        wp_redirect(home_url('/min-side/foretak/?registered=1'));
        exit;
    }

    // Rate limiting: max 3 attempts per hour per user
    $rate_key = 'bv_foretak_reg_' . $user_id;
    $attempts = (int) get_transient($rate_key);
    if ($attempts >= 3) {
        wp_redirect(add_query_arg('bv_error', 'rate_limit', $redirect_error));
        exit;
    }
    set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);

    // Check if user already has a company
    if (function_exists('bimverdi_user_has_foretak')) {
        $existing = bimverdi_user_has_foretak($user_id);
        if ($existing && get_post_status($existing) === 'publish') {
            wp_redirect(home_url('/min-side/foretak/'));
            exit;
        }
    }

    // --- Sanitize inputs ---
    $bedriftsnavn     = sanitize_text_field($_POST['bedriftsnavn'] ?? '');
    $organisasjonsnummer = sanitize_text_field($_POST['organisasjonsnummer'] ?? '');
    $beskrivelse      = sanitize_textarea_field($_POST['beskrivelse'] ?? '');
    $gateadresse      = sanitize_text_field($_POST['gateadresse'] ?? '');
    $postnummer       = sanitize_text_field($_POST['postnummer'] ?? '');
    $poststed         = sanitize_text_field($_POST['poststed'] ?? '');
    $nettside         = esc_url_raw($_POST['nettside'] ?? '');
    $bransje_rolle    = array_map('sanitize_text_field', (array) ($_POST['bransje_rolle'] ?? []));
    $kundetyper       = array_map('sanitize_text_field', (array) ($_POST['kundetyper'] ?? []));

    // --- Validate required fields ---
    if (empty($bedriftsnavn)) {
        wp_redirect(add_query_arg('bv_error', 'missing_name', $redirect_error));
        exit;
    }

    if (empty($organisasjonsnummer) || !preg_match('/^\d{9}$/', $organisasjonsnummer)) {
        wp_redirect(add_query_arg('bv_error', 'invalid_orgnr', $redirect_error));
        exit;
    }

    if (empty($beskrivelse)) {
        wp_redirect(add_query_arg('bv_error', 'missing_description', $redirect_error));
        exit;
    }

    if (empty($bransje_rolle)) {
        wp_redirect(add_query_arg('bv_error', 'missing_bransje', $redirect_error));
        exit;
    }

    // Validate bransje_rolle values against allowed list
    $allowed_bransje = [
        'bestiller_byggherre', 'arkitekt_radgiver', 'entreprenor_byggmester',
        'byggevareprodusent', 'byggevarehandel', 'eiendom_drift',
        'digital_leverandor', 'organisasjon', 'tjenesteleverandor',
        'offentlig', 'utdanning', 'boligutvikler', 'radgivende_ingenior', 'annet',
    ];
    $bransje_rolle = array_intersect($bransje_rolle, $allowed_bransje);
    if (empty($bransje_rolle)) {
        wp_redirect(add_query_arg('bv_error', 'missing_bransje', $redirect_error));
        exit;
    }

    // Validate kundetyper values against allowed list
    $allowed_kundetyper = [
        'bestiller_byggherre', 'arkitekt_radgiver', 'entreprenor_byggmester',
        'byggevareprodusent', 'byggevarehandel', 'eiendom_drift',
        'digital_leverandor', 'organisasjon', 'tjenesteleverandor',
        'offentlig', 'utdanning', 'annet',
    ];
    $kundetyper = array_intersect($kundetyper, $allowed_kundetyper);

    // Check if org number is already registered
    $existing_foretak = get_posts([
        'post_type'   => defined('BV_CPT_COMPANY') ? BV_CPT_COMPANY : 'foretak',
        'post_status' => 'publish',
        'meta_key'    => 'organisasjonsnummer',
        'meta_value'  => $organisasjonsnummer,
        'numberposts' => 1,
    ]);
    if (!empty($existing_foretak)) {
        wp_redirect(add_query_arg('bv_error', 'orgnr_exists', $redirect_error));
        exit;
    }

    // --- Handle logo upload ---
    $logo_attachment_id = 0;
    if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $file_type = wp_check_filetype($_FILES['logo']['name']);
        $mime_check = wp_check_filetype_and_ext($_FILES['logo']['tmp_name'], $_FILES['logo']['name']);

        if (!in_array($mime_check['type'], $allowed_types) && !in_array($file_type['type'], $allowed_types)) {
            wp_redirect(add_query_arg('bv_error', 'invalid_file_type', $redirect_error));
            exit;
        }

        // Validate file size (max 2MB)
        if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
            wp_redirect(add_query_arg('bv_error', 'file_too_large', $redirect_error));
            exit;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $upload = wp_handle_upload($_FILES['logo'], ['test_form' => false]);

        if (isset($upload['error'])) {
            error_log('BIMVerdi foretak logo upload error: ' . $upload['error']);
            wp_redirect(add_query_arg('bv_error', 'upload_failed', $redirect_error));
            exit;
        }

        // Create attachment
        $attachment = [
            'post_mime_type' => $upload['type'],
            'post_title'     => sanitize_file_name($bedriftsnavn . '-logo'),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $logo_attachment_id = wp_insert_attachment($attachment, $upload['file']);
        if (!is_wp_error($logo_attachment_id)) {
            $metadata = wp_generate_attachment_metadata($logo_attachment_id, $upload['file']);
            wp_update_attachment_metadata($logo_attachment_id, $metadata);
        }
    }

    // --- Create foretak post ---
    $post_data = [
        'post_type'    => defined('BV_CPT_COMPANY') ? BV_CPT_COMPANY : 'foretak',
        'post_title'   => $bedriftsnavn,
        'post_content' => $beskrivelse,
        'post_status'  => 'publish',
        'post_author'  => $user_id,
    ];

    $foretak_id = wp_insert_post($post_data, true);

    if (is_wp_error($foretak_id)) {
        error_log('BIMVerdi foretak creation error: ' . $foretak_id->get_error_message());
        wp_redirect(add_query_arg('bv_error', 'system', $redirect_error));
        exit;
    }

    // --- Set ACF fields ---
    if (function_exists('update_field')) {
        update_field('organisasjonsnummer', $organisasjonsnummer, $foretak_id);
        update_field('gateadresse', $gateadresse, $foretak_id);
        update_field('postnummer', $postnummer, $foretak_id);
        update_field('poststed', $poststed, $foretak_id);
        update_field('nettside', $nettside, $foretak_id);
        update_field('bransje_rolle', array_values($bransje_rolle), $foretak_id);
        update_field('kundetyper', array_values($kundetyper), $foretak_id);
        update_field('hovedkontaktperson', $user_id, $foretak_id);

        if ($logo_attachment_id) {
            update_field('logo', $logo_attachment_id, $foretak_id);
        }
    } else {
        // Fallback to post meta if ACF is not available
        update_post_meta($foretak_id, 'organisasjonsnummer', $organisasjonsnummer);
        update_post_meta($foretak_id, 'gateadresse', $gateadresse);
        update_post_meta($foretak_id, 'postnummer', $postnummer);
        update_post_meta($foretak_id, 'poststed', $poststed);
        update_post_meta($foretak_id, 'nettside', $nettside);
        update_post_meta($foretak_id, 'hovedkontaktperson', $user_id);

        if ($logo_attachment_id) {
            set_post_thumbnail($foretak_id, $logo_attachment_id);
        }
    }

    // --- Link user to foretak ---
    update_user_meta($user_id, 'bimverdi_company_id', $foretak_id);
    update_user_meta($user_id, 'bim_verdi_company_id', $foretak_id);
    update_user_meta($user_id, 'bimverdi_account_type', 'foretak');

    // Set user as hovedkontakt role if they have a basic role
    $user = new WP_User($user_id);
    $basic_roles = ['subscriber', 'medlem'];
    if (!empty(array_intersect($basic_roles, $user->roles))) {
        $user->set_role('deltaker');
    }

    // Set ACF user field
    if (function_exists('update_field')) {
        update_field('tilknyttet_foretak', $foretak_id, 'user_' . $user_id);
    }

    // Clear rate limit on success
    delete_transient($rate_key);

    error_log('BIMVerdi: Foretak created: ' . $foretak_id . ' (' . $bedriftsnavn . ') by user ' . $user_id);

    // Redirect to foretak page with success
    wp_redirect(add_query_arg('registered', '1', home_url('/min-side/foretak/')));
    exit;
});
