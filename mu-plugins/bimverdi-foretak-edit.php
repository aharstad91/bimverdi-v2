<?php
/**
 * BIM Verdi - Foretak Edit Handler
 *
 * Handles plain HTML form submission for editing company (foretak) profile.
 * Replaces Gravity Forms Form #7 with a direct POST handler.
 *
 * Pattern: POST-Redirect-GET (PRG)
 * - Success: /min-side/foretak/?updated=1
 * - Error:   /min-side/foretak/rediger/?bv_error=<code>
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle foretak edit form submission
 */
add_action('init', function () {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bimverdi_edit_foretak'])) {
        return;
    }

    // Must be logged in
    if (!is_user_logged_in()) {
        wp_redirect(home_url('/logg-inn/'));
        exit;
    }

    $user_id = get_current_user_id();
    $redirect_error = home_url('/min-side/foretak/rediger/');

    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bimverdi_edit_foretak')) {
        wp_redirect(add_query_arg('bv_error', 'nonce', $redirect_error));
        exit;
    }

    // Get company ID from hidden field
    $company_id = absint($_POST['company_id'] ?? 0);
    if (!$company_id) {
        wp_redirect(add_query_arg('bv_error', 'missing_company', $redirect_error));
        exit;
    }

    // Verify the company exists and is a foretak
    $company = get_post($company_id);
    $cpt = defined('BV_CPT_COMPANY') ? BV_CPT_COMPANY : 'foretak';
    if (!$company || $company->post_type !== $cpt) {
        wp_redirect(add_query_arg('bv_error', 'invalid_company', $redirect_error));
        exit;
    }

    // Verify user is hovedkontakt or admin
    $is_hovedkontakt = function_exists('bimverdi_is_hovedkontakt') && bimverdi_is_hovedkontakt($user_id, $company_id);
    $is_admin = current_user_can('manage_options');
    if (!$is_hovedkontakt && !$is_admin) {
        wp_redirect(add_query_arg('bv_error', 'not_authorized', $redirect_error));
        exit;
    }

    // Rate limiting: max 10 edits per hour per user
    $rate_key = 'bv_foretak_edit_' . $user_id;
    $attempts = (int) get_transient($rate_key);
    if ($attempts >= 10) {
        wp_redirect(add_query_arg('bv_error', 'rate_limit', $redirect_error));
        exit;
    }
    set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);

    // --- Sanitize editable inputs ---
    $beskrivelse = sanitize_textarea_field($_POST['beskrivelse'] ?? '');
    $telefon     = sanitize_text_field($_POST['telefon'] ?? '');
    $epost       = sanitize_email($_POST['epost'] ?? '');
    $nettside    = esc_url_raw($_POST['nettside'] ?? '');

    // Sanitize taxonomy selections
    $bransje_rolle = array_map('sanitize_text_field', (array) ($_POST['bransje_rolle'] ?? []));
    $kundetyper    = array_map('sanitize_text_field', (array) ($_POST['kundetyper'] ?? []));

    // --- Handle logo upload (optional) ---
    $logo_attachment_id = 0;
    if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $file_type = wp_check_filetype($_FILES['logo']['name']);
        $mime_check = wp_check_filetype_and_ext($_FILES['logo']['tmp_name'], $_FILES['logo']['name']);

        if (!in_array($mime_check['type'], $allowed_types) && !in_array($file_type['type'], $allowed_types)) {
            wp_redirect(add_query_arg('bv_error', 'invalid_file_type', $redirect_error));
            exit;
        }

        if ($_FILES['logo']['size'] > 5 * 1024 * 1024) {
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

        $attachment = [
            'post_mime_type' => $upload['type'],
            'post_title'     => sanitize_file_name($company->post_title . '-logo'),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $logo_attachment_id = wp_insert_attachment($attachment, $upload['file']);
        if (!is_wp_error($logo_attachment_id)) {
            $metadata = wp_generate_attachment_metadata($logo_attachment_id, $upload['file']);
            wp_update_attachment_metadata($logo_attachment_id, $metadata);
        }
    }

    // --- Update foretak post ---
    $update_data = [
        'ID'           => $company_id,
        'post_content' => $beskrivelse,
    ];

    $result = wp_update_post($update_data, true);

    if (is_wp_error($result)) {
        error_log('BIMVerdi foretak update error: ' . $result->get_error_message());
        wp_redirect(add_query_arg('bv_error', 'system', $redirect_error));
        exit;
    }

    // --- Update ACF fields ---
    if (function_exists('update_field')) {
        update_field('beskrivelse', $beskrivelse, $company_id);
        update_field('telefon', $telefon, $company_id);
        update_field('epost', $epost, $company_id);
        update_field('nettside', $nettside, $company_id);
        update_field('hjemmeside', $nettside, $company_id);

        if ($logo_attachment_id) {
            update_field('logo', $logo_attachment_id, $company_id);
        }
    } else {
        update_post_meta($company_id, 'beskrivelse', $beskrivelse);
        update_post_meta($company_id, 'telefon', $telefon);
        update_post_meta($company_id, 'epost', $epost);
        update_post_meta($company_id, 'nettside', $nettside);
        update_post_meta($company_id, 'hjemmeside', $nettside);

        if ($logo_attachment_id) {
            set_post_thumbnail($company_id, $logo_attachment_id);
        }
    }

    // --- Update taxonomies ---
    $cpt_tax_industry = defined('BV_TAX_INDUSTRY') ? BV_TAX_INDUSTRY : 'bransjekategori';
    $cpt_tax_customer = defined('BV_TAX_CUSTOMER_TYPE') ? BV_TAX_CUSTOMER_TYPE : 'kundetype';

    if (!empty($bransje_rolle)) {
        wp_set_object_terms($company_id, $bransje_rolle, $cpt_tax_industry);
    } else {
        wp_set_object_terms($company_id, [], $cpt_tax_industry);
    }

    if (!empty($kundetyper)) {
        wp_set_object_terms($company_id, $kundetyper, $cpt_tax_customer);
    } else {
        wp_set_object_terms($company_id, [], $cpt_tax_customer);
    }

    // Clear rate limit on success
    delete_transient($rate_key);

    error_log('BIMVerdi: Foretak updated: ' . $company_id . ' by user ' . $user_id);

    // Redirect to foretak page with success
    wp_redirect(add_query_arg('updated', '1', home_url('/min-side/foretak/')));
    exit;
});
