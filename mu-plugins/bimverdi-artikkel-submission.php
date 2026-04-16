<?php
/**
 * BIM Verdi - Artikkel Submission Handler
 *
 * Handles plain HTML form submission for creating/editing/deleting artikler.
 * Pattern follows bimverdi-tool-registration.php.
 *
 * Pattern: POST-Redirect-GET (PRG)
 * - Create success: /min-side/artikler/?submitted=1
 * - Edit success:   /min-side/artikler/?updated=1
 * - Delete success: /min-side/artikler/?deleted=1
 * - Error:          referrer URL with ?bv_error=<code>
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle artikkel create/edit form submission
 * Priority 20 ensures CPT and taxonomies are registered first
 */
add_action('init', function () {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $is_create = isset($_POST['bimverdi_register_artikkel']);
    $is_edit = isset($_POST['bimverdi_edit_artikkel']);

    if (!$is_create && !$is_edit) {
        return;
    }

    if (!is_user_logged_in()) {
        wp_redirect(home_url('/logg-inn/'));
        exit;
    }

    $user_id = get_current_user_id();
    $redirect_error = $is_edit
        ? home_url('/min-side/artikler/rediger/?id=' . intval($_POST['artikkel_id'] ?? 0))
        : home_url('/min-side/artikler/skriv/');

    // Verify nonce
    $nonce_action = $is_edit ? 'bimverdi_edit_artikkel' : 'bimverdi_register_artikkel';
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', $nonce_action)) {
        wp_redirect(add_query_arg('bv_error', 'nonce', $redirect_error));
        exit;
    }

    // Honeypot check
    if (!empty($_POST['bv_website_url'] ?? '')) {
        wp_redirect(home_url('/min-side/artikler/' . ($is_edit ? '?updated=1' : '?submitted=1')));
        exit;
    }

    // Rate limiting: max 5 submissions per hour per user
    $rate_key = 'bv_artikkel_reg_' . $user_id;
    $attempts = (int) get_transient($rate_key);
    if ($attempts >= 5) {
        wp_redirect(add_query_arg('bv_error', 'rate_limit', $redirect_error));
        exit;
    }
    set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);

    // Premium access check (server-side double-check)
    if (function_exists('bimverdi_can_access') && !bimverdi_can_access('write_article')) {
        wp_redirect(home_url('/min-side/'));
        exit;
    }

    // Get company ID from user meta (NEVER from form)
    $company_id = get_user_meta($user_id, 'bimverdi_company_id', true)
               ?: get_user_meta($user_id, 'bim_verdi_company_id', true);

    if (!$company_id) {
        wp_redirect(add_query_arg('bv_error', 'no_company', $redirect_error));
        exit;
    }

    // --- Sanitize inputs ---
    $title       = sanitize_text_field($_POST['artikkel_title'] ?? '');
    $ingress     = sanitize_text_field($_POST['artikkel_ingress'] ?? '');
    $content     = wp_kses_post($_POST['artikkel_content'] ?? '');
    $temagrupper = array_map('intval', (array) ($_POST['temagrupper'] ?? []));
    $verktoykategorier = array_map('intval', (array) ($_POST['verktoykategorier'] ?? []));
    $kunnskapskilder = array_map('intval', (array) ($_POST['kunnskapskilder'] ?? []));
    $eksterne_lenker_urls = array_map('esc_url_raw', (array) ($_POST['eksterne_lenker_url'] ?? []));
    $eksterne_lenker_labels = array_map('sanitize_text_field', (array) ($_POST['eksterne_lenker_label'] ?? []));

    // --- Validate required fields ---
    if (empty($title)) {
        wp_redirect(add_query_arg('bv_error', 'missing_title', $redirect_error));
        exit;
    }

    if (mb_strlen($title) > 120) {
        wp_redirect(add_query_arg('bv_error', 'title_too_long', $redirect_error));
        exit;
    }

    if (mb_strlen($ingress) > 300) {
        wp_redirect(add_query_arg('bv_error', 'ingress_too_long', $redirect_error));
        exit;
    }

    $content_stripped = wp_strip_all_tags($content);
    if (mb_strlen($content_stripped) < 100) {
        wp_redirect(add_query_arg('bv_error', 'content_too_short', $redirect_error));
        exit;
    }

    // Validate taxonomy selections
    $temagrupper = array_filter($temagrupper, function($id) {
        return term_exists($id, 'temagruppe');
    });
    if (empty($temagrupper)) {
        wp_redirect(add_query_arg('bv_error', 'missing_temagruppe', $redirect_error));
        exit;
    }

    $verktoykategorier = array_filter($verktoykategorier, function($id) {
        return term_exists($id, 'verktoykategori');
    });

    // Validate kunnskapskilde IDs (must be published kunnskapskilde posts)
    $kunnskapskilder = array_filter($kunnskapskilder, function($id) {
        return get_post_type($id) === 'kunnskapskilde' && get_post_status($id) === 'publish';
    });

    // Build externe lenker array (max 5, filter empty)
    $eksterne_lenker = [];
    for ($i = 0; $i < min(5, count($eksterne_lenker_urls)); $i++) {
        $url = $eksterne_lenker_urls[$i] ?? '';
        $label = $eksterne_lenker_labels[$i] ?? '';
        if (!empty($url)) {
            $eksterne_lenker[] = [
                'url' => $url,
                'label' => $label ?: $url,
            ];
        }
    }

    // --- Handle image upload ---
    $thumbnail_id = 0;
    if (!empty($_FILES['artikkel_image']) && $_FILES['artikkel_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $file_type = wp_check_filetype($_FILES['artikkel_image']['name']);
        $mime_check = wp_check_filetype_and_ext($_FILES['artikkel_image']['tmp_name'], $_FILES['artikkel_image']['name']);

        if (!in_array($mime_check['type'], $allowed_types) && !in_array($file_type['type'], $allowed_types)) {
            wp_redirect(add_query_arg('bv_error', 'invalid_file_type', $redirect_error));
            exit;
        }

        if ($_FILES['artikkel_image']['size'] > 2 * 1024 * 1024) {
            wp_redirect(add_query_arg('bv_error', 'file_too_large', $redirect_error));
            exit;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $upload = wp_handle_upload($_FILES['artikkel_image'], ['test_form' => false]);

        if (isset($upload['error'])) {
            error_log('BIMVerdi artikkel image upload error: ' . $upload['error']);
            wp_redirect(add_query_arg('bv_error', 'upload_failed', $redirect_error));
            exit;
        }

        $attachment = [
            'post_mime_type' => $upload['type'],
            'post_title'     => sanitize_file_name($title . '-forsidebilde'),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $thumbnail_id = wp_insert_attachment($attachment, $upload['file']);
        if (!is_wp_error($thumbnail_id)) {
            $metadata = wp_generate_attachment_metadata($thumbnail_id, $upload['file']);
            wp_update_attachment_metadata($thumbnail_id, $metadata);
        }
    }

    // --- EDIT MODE ---
    if ($is_edit) {
        $artikkel_id = intval($_POST['artikkel_id'] ?? 0);
        $existing = get_post($artikkel_id);

        if (!$existing || $existing->post_type !== 'artikkel') {
            wp_redirect(add_query_arg('bv_error', 'not_found', $redirect_error));
            exit;
        }

        // Ownership check
        if ((int) $existing->post_author !== (int) $user_id && !current_user_can('manage_options')) {
            wp_redirect(add_query_arg('bv_error', 'not_owner', $redirect_error));
            exit;
        }

        // Race guard: only pending articles can be edited
        if (get_post_status($artikkel_id) !== 'pending') {
            wp_redirect(add_query_arg('bv_error', 'already_published', home_url('/min-side/artikler/')));
            exit;
        }

        wp_update_post([
            'ID'           => $artikkel_id,
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'pending',
        ]);

        $post_id = $artikkel_id;
    }
    // --- CREATE MODE ---
    else {
        $post_id = wp_insert_post([
            'post_type'    => 'artikkel',
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'pending',
            'post_author'  => $user_id,
        ], true);

        if (is_wp_error($post_id)) {
            error_log('BIMVerdi artikkel creation error: ' . $post_id->get_error_message());
            wp_redirect(add_query_arg('bv_error', 'system', $redirect_error));
            exit;
        }
    }

    // --- Save taxonomies ---
    wp_set_object_terms($post_id, array_values($temagrupper), 'temagruppe');
    wp_set_object_terms($post_id, array_values($verktoykategorier), 'verktoykategori');

    // --- Save ACF fields ---
    if (function_exists('update_field')) {
        // Ingress: use provided or auto-extract from content
        $ingress_value = !empty($ingress) ? $ingress : mb_substr(wp_strip_all_tags($content), 0, 200);
        update_field('artikkel_ingress', $ingress_value, $post_id);
        update_field('artikkel_bedrift', intval($company_id), $post_id);
    }

    // --- Save post meta (native, not ACF) ---
    update_post_meta($post_id, '_bv_kunnskapskilder', array_values($kunnskapskilder));
    update_post_meta($post_id, '_bv_eksterne_lenker', $eksterne_lenker);

    // --- Set thumbnail ---
    if ($thumbnail_id) {
        set_post_thumbnail($post_id, $thumbnail_id);
    }

    // Clear rate limit on success
    delete_transient($rate_key);

    $action = $is_edit ? 'updated' : 'submitted';
    error_log("BIMVerdi: Artikkel {$action}: {$post_id} ({$title}) by user {$user_id}");

    $param = $is_edit ? 'updated' : 'submitted';
    wp_redirect(add_query_arg($param, '1', home_url('/min-side/artikler/')));
    exit;
}, 20);

/**
 * Handle artikkel deletion
 *
 * URL pattern: /min-side/artikler/?action=delete_artikkel&artikkel_id=XX&_wpnonce=YY
 * Only pending articles can be deleted by their author.
 *
 * Pattern: GET-Redirect-GET
 */
add_action('init', function () {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        return;
    }

    if (($_GET['action'] ?? '') !== 'delete_artikkel') {
        return;
    }

    $redirect_list = home_url('/min-side/artikler/');

    if (!is_user_logged_in()) {
        wp_redirect(home_url('/logg-inn/?redirect_to=' . urlencode($redirect_list)));
        exit;
    }

    $artikkel_id = intval($_GET['artikkel_id'] ?? 0);
    if (!$artikkel_id) {
        wp_redirect(add_query_arg('bv_error', 'not_found', $redirect_list));
        exit;
    }

    // Verify nonce
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'delete_artikkel_' . $artikkel_id)) {
        wp_redirect(add_query_arg('bv_error', 'nonce', $redirect_list));
        exit;
    }

    $artikkel = get_post($artikkel_id);
    if (!$artikkel || $artikkel->post_type !== 'artikkel') {
        wp_redirect(add_query_arg('bv_error', 'not_found', $redirect_list));
        exit;
    }

    // Only author can delete (or admin)
    $user_id = get_current_user_id();
    if ((int) $artikkel->post_author !== (int) $user_id && !current_user_can('manage_options')) {
        wp_redirect(add_query_arg('bv_error', 'not_owner', $redirect_list));
        exit;
    }

    // Only pending articles can be deleted from Min Side
    if (get_post_status($artikkel_id) !== 'pending') {
        wp_redirect(add_query_arg('bv_error', 'already_published', $redirect_list));
        exit;
    }

    $result = wp_trash_post($artikkel_id);

    if (!$result) {
        error_log("BIMVerdi: Artikkel deletion failed for {$artikkel_id} by user {$user_id}");
        wp_redirect(add_query_arg('bv_error', 'system', $redirect_list));
        exit;
    }

    error_log("BIMVerdi: Artikkel deleted (trashed): {$artikkel_id} ({$artikkel->post_title}) by user {$user_id}");
    wp_redirect(add_query_arg('deleted', '1', $redirect_list));
    exit;
}, 20);
