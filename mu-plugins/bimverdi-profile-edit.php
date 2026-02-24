<?php
/**
 * BIM Verdi - Profile Edit Handler
 *
 * Handles plain HTML form submission for editing user profile.
 * Replaces Gravity Forms Form #4 with a direct POST handler.
 *
 * Pattern: POST-Redirect-GET (PRG)
 * Hook: template_redirect (runs after CPTs/taxonomies are registered on init)
 * - Success: /min-side/profil/?updated=1
 * - Error:   /min-side/profil/rediger/?bv_error=<code>
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle profile edit form submission
 */
add_action('template_redirect', function () {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bimverdi_edit_profile'])) {
        return;
    }

    // Must be logged in
    if (!is_user_logged_in()) {
        wp_redirect(home_url('/logg-inn/'));
        exit;
    }

    $user_id = get_current_user_id();
    $redirect_error = home_url('/min-side/profil/rediger/');

    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bimverdi_edit_profile')) {
        wp_redirect(add_query_arg('bv_error', 'nonce', $redirect_error));
        exit;
    }

    // Rate limiting: max 10 edits per hour per user
    $rate_key = 'bv_profile_edit_' . $user_id;
    $attempts = (int) get_transient($rate_key);
    if ($attempts >= 10) {
        wp_redirect(add_query_arg('bv_error', 'rate_limit', $redirect_error));
        exit;
    }
    set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);

    // --- Sanitize inputs ---
    $first_name   = sanitize_text_field($_POST['first_name'] ?? '');
    $middle_name  = sanitize_text_field($_POST['middle_name'] ?? '');
    $last_name    = sanitize_text_field($_POST['last_name'] ?? '');
    $phone        = sanitize_text_field($_POST['phone'] ?? '');
    $job_title    = sanitize_text_field($_POST['job_title'] ?? '');
    $linkedin_url = esc_url_raw($_POST['linkedin_url'] ?? '');

    // Sanitize checkbox arrays
    $registration_background = array_map('sanitize_text_field', (array) ($_POST['registration_background'] ?? []));
    $topic_interests         = array_map('sanitize_text_field', (array) ($_POST['topic_interests'] ?? []));

    // Validate required fields
    if (empty($first_name) || empty($last_name)) {
        wp_redirect(add_query_arg('bv_error', 'required_fields', $redirect_error));
        exit;
    }

    // Whitelist checkbox values
    $valid_backgrounds = ['oppdatering', 'tilleggskontakt', 'arrangement', 'nyhetsbrev', 'deltaker_verktoy', 'mote'];
    $registration_background = array_intersect($registration_background, $valid_backgrounds);

    $valid_topics = ['byggesaksbim', 'prosjektbim', 'eiendomsbim', 'miljobim', 'sirkbim', 'bimtech'];
    $topic_interests = array_intersect($topic_interests, $valid_topics);

    // --- Handle profile image upload (optional) ---
    $profile_image_id = 0;
    if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = wp_check_filetype($_FILES['profile_image']['name']);
        $mime_check = wp_check_filetype_and_ext($_FILES['profile_image']['tmp_name'], $_FILES['profile_image']['name']);

        if (!in_array($mime_check['type'], $allowed_types) && !in_array($file_type['type'], $allowed_types)) {
            wp_redirect(add_query_arg('bv_error', 'invalid_file_type', $redirect_error));
            exit;
        }

        if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
            wp_redirect(add_query_arg('bv_error', 'file_too_large', $redirect_error));
            exit;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $upload = wp_handle_upload($_FILES['profile_image'], ['test_form' => false]);

        if (isset($upload['error'])) {
            error_log('BIMVerdi profile image upload error: ' . $upload['error']);
            wp_redirect(add_query_arg('bv_error', 'upload_failed', $redirect_error));
            exit;
        }

        $attachment = [
            'post_mime_type' => $upload['type'],
            'post_title'     => sanitize_file_name($first_name . '-' . $last_name . '-profil'),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $profile_image_id = wp_insert_attachment($attachment, $upload['file']);
        if (!is_wp_error($profile_image_id)) {
            $metadata = wp_generate_attachment_metadata($profile_image_id, $upload['file']);
            wp_update_attachment_metadata($profile_image_id, $metadata);
        }
    }

    // --- Update WordPress user fields ---
    $user_data = [
        'ID'         => $user_id,
        'first_name' => $first_name,
        'last_name'  => $last_name,
    ];

    $result = wp_update_user($user_data);

    if (is_wp_error($result)) {
        error_log('BIMVerdi profile update error: ' . $result->get_error_message());
        wp_redirect(add_query_arg('bv_error', 'system', $redirect_error));
        exit;
    }

    // --- Update ACF fields ---
    if (function_exists('update_field')) {
        update_field('middle_name', $middle_name, 'user_' . $user_id);
        update_field('phone', $phone, 'user_' . $user_id);
        update_field('job_title', $job_title, 'user_' . $user_id);
        update_field('linkedin_url', $linkedin_url, 'user_' . $user_id);
        update_field('registration_background', $registration_background, 'user_' . $user_id);
        update_field('topic_interests', $topic_interests, 'user_' . $user_id);

        if ($profile_image_id) {
            update_field('profile_image', $profile_image_id, 'user_' . $user_id);
        }
    } else {
        update_user_meta($user_id, 'middle_name', $middle_name);
        update_user_meta($user_id, 'phone', $phone);
        update_user_meta($user_id, 'job_title', $job_title);
        update_user_meta($user_id, 'linkedin_url', $linkedin_url);
        update_user_meta($user_id, 'registration_background', $registration_background);
        update_user_meta($user_id, 'topic_interests', $topic_interests);

        if ($profile_image_id) {
            update_user_meta($user_id, 'profile_image', $profile_image_id);
        }
    }

    // Sync topic_interests to legacy bim_verdi_temagrupper meta
    update_user_meta($user_id, 'bim_verdi_temagrupper', $topic_interests);

    // Clear rate limit on success
    delete_transient($rate_key);

    error_log('BIMVerdi: Profile updated for user ' . $user_id);

    // Redirect to profile page with success
    wp_redirect(add_query_arg('updated', '1', home_url('/min-side/profil/')));
    exit;
});
