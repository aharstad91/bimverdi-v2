<?php
/**
 * BIM Verdi - Tool Registration Handler
 *
 * Handles plain HTML form submission for creating/editing verktøy (tools).
 * Replaces Gravity Forms Form #1 + class-tool-form-handler.php.
 *
 * Pattern: POST-Redirect-GET (PRG)
 * - Create success: /min-side/verktoy/?registered=1
 * - Edit success:   /min-side/verktoy/?updated=1
 * - Error:          referrer URL with ?bv_error=<code>
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle tool registration/edit form submission
 */
add_action('init', function () {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    // Determine mode: create or edit
    $is_create = isset($_POST['bimverdi_register_tool']);
    $is_edit = isset($_POST['bimverdi_edit_tool']);

    if (!$is_create && !$is_edit) {
        return;
    }

    // Must be logged in
    if (!is_user_logged_in()) {
        wp_redirect(home_url('/logg-inn/'));
        exit;
    }

    $user_id = get_current_user_id();
    $redirect_error = $is_edit
        ? home_url('/min-side/verktoy/rediger/?id=' . intval($_POST['tool_id'] ?? 0))
        : home_url('/min-side/verktoy/registrer/');

    // Verify nonce
    $nonce_action = $is_edit ? 'bimverdi_edit_tool' : 'bimverdi_register_tool';
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', $nonce_action)) {
        wp_redirect(add_query_arg('bv_error', 'nonce', $redirect_error));
        exit;
    }

    // Honeypot check
    if (!empty($_POST['bv_website_url'] ?? '')) {
        wp_redirect(home_url('/min-side/verktoy/' . ($is_edit ? '?updated=1' : '?registered=1')));
        exit;
    }

    // Rate limiting: max 5 submissions per hour per user
    $rate_key = 'bv_tool_reg_' . $user_id;
    $attempts = (int) get_transient($rate_key);
    if ($attempts >= 5) {
        wp_redirect(add_query_arg('bv_error', 'rate_limit', $redirect_error));
        exit;
    }
    set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);

    // Get company ID from user meta (NEVER from form — security)
    $company_id = get_user_meta($user_id, 'bimverdi_company_id', true)
               ?: get_user_meta($user_id, 'bim_verdi_company_id', true);

    if (!$company_id) {
        wp_redirect(add_query_arg('bv_error', 'no_company', $redirect_error));
        exit;
    }

    // --- Sanitize inputs ---
    $tool_name       = sanitize_text_field($_POST['tool_name'] ?? '');
    $kort_beskrivelse = sanitize_text_field($_POST['kort_beskrivelse'] ?? '');
    $description     = wp_kses_post($_POST['description'] ?? '');
    $versjon         = sanitize_text_field($_POST['versjon'] ?? '');
    $tool_url        = esc_url_raw($_POST['tool_url'] ?? '');
    $produktbeskrivelse_url = esc_url_raw($_POST['produktbeskrivelse_url'] ?? '');
    $nedlasting_url  = esc_url_raw($_POST['nedlasting_url'] ?? '');
    $formaalstema    = sanitize_text_field($_POST['formaalstema'] ?? '');
    $bim_kompatibilitet = sanitize_text_field($_POST['bim_kompatibilitet'] ?? '');
    $type_ressurs    = sanitize_text_field($_POST['type_ressurs'] ?? '');
    $type_teknologi  = sanitize_text_field($_POST['type_teknologi'] ?? '');
    $anvendelser     = array_map('sanitize_text_field', (array) ($_POST['anvendelser'] ?? []));

    // --- Validate required fields ---
    if (empty($tool_name)) {
        wp_redirect(add_query_arg('bv_error', 'missing_name', $redirect_error));
        exit;
    }

    if (empty($kort_beskrivelse)) {
        wp_redirect(add_query_arg('bv_error', 'missing_kort_beskrivelse', $redirect_error));
        exit;
    }

    // Validate radio values against allowed lists
    $allowed_formaalstema = ['byggesak', 'prosjekt', 'eiendom', 'miljo', 'sirk', 'validering', 'opplaering', 'samhandling', 'prosjektutvikling'];
    if ($formaalstema && !in_array($formaalstema, $allowed_formaalstema)) {
        $formaalstema = '';
    }

    $allowed_bim = ['ifc_kompatibel', 'ifc_eksport', 'ifc_import', 'kobling_ifc', 'planlagt', 'vet_ikke'];
    if ($bim_kompatibilitet && !in_array($bim_kompatibilitet, $allowed_bim)) {
        $bim_kompatibilitet = '';
    }

    $allowed_ressurs = ['programvare', 'standard', 'metodikk', 'veileder', 'nettside', 'digital_tjeneste', 'saas', 'kurs'];
    if ($type_ressurs && !in_array($type_ressurs, $allowed_ressurs)) {
        $type_ressurs = '';
    }

    $allowed_teknologi = ['bruker_ki', 'ikke_ki', 'planlegger_ki', 'under_avklaring'];
    if ($type_teknologi && !in_array($type_teknologi, $allowed_teknologi)) {
        $type_teknologi = '';
    }

    $allowed_anvendelser = ['design', 'gis', 'dokumenter', 'prosjektledelse', 'kostnad', 'simulering', 'feltarbeid', 'fasilitets', 'barekraft', 'kommunikasjon', 'logistikk', 'kompetanse'];
    $anvendelser = array_intersect($anvendelser, $allowed_anvendelser);

    if (empty($anvendelser)) {
        wp_redirect(add_query_arg('bv_error', 'missing_anvendelser', $redirect_error));
        exit;
    }

    // --- Handle image upload ---
    $logo_attachment_id = 0;
    if (!empty($_FILES['tool_logo']) && $_FILES['tool_logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $file_type = wp_check_filetype($_FILES['tool_logo']['name']);
        $mime_check = wp_check_filetype_and_ext($_FILES['tool_logo']['tmp_name'], $_FILES['tool_logo']['name']);

        if (!in_array($mime_check['type'], $allowed_types) && !in_array($file_type['type'], $allowed_types)) {
            wp_redirect(add_query_arg('bv_error', 'invalid_file_type', $redirect_error));
            exit;
        }

        if ($_FILES['tool_logo']['size'] > 2 * 1024 * 1024) {
            wp_redirect(add_query_arg('bv_error', 'file_too_large', $redirect_error));
            exit;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $upload = wp_handle_upload($_FILES['tool_logo'], ['test_form' => false]);

        if (isset($upload['error'])) {
            error_log('BIMVerdi tool logo upload error: ' . $upload['error']);
            wp_redirect(add_query_arg('bv_error', 'upload_failed', $redirect_error));
            exit;
        }

        $attachment = [
            'post_mime_type' => $upload['type'],
            'post_title'     => sanitize_file_name($tool_name . '-logo'),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $logo_attachment_id = wp_insert_attachment($attachment, $upload['file']);
        if (!is_wp_error($logo_attachment_id)) {
            $metadata = wp_generate_attachment_metadata($logo_attachment_id, $upload['file']);
            wp_update_attachment_metadata($logo_attachment_id, $metadata);
        }
    }

    // --- EDIT MODE ---
    if ($is_edit) {
        $tool_id = intval($_POST['tool_id'] ?? 0);
        $existing_tool = get_post($tool_id);

        if (!$existing_tool || $existing_tool->post_type !== 'verktoy') {
            wp_redirect(add_query_arg('bv_error', 'not_found', $redirect_error));
            exit;
        }

        // Check permission
        $can_edit = false;
        if ($existing_tool->post_author == $user_id) $can_edit = true;
        $tool_company = get_field('eier_leverandor', $tool_id);
        if ($company_id && $tool_company && $tool_company == $company_id) $can_edit = true;
        if (current_user_can('manage_options')) $can_edit = true;

        if (!$can_edit) {
            wp_redirect(add_query_arg('bv_error', 'permission', $redirect_error));
            exit;
        }

        wp_update_post([
            'ID'          => $tool_id,
            'post_title'  => $tool_name,
            'post_status' => $existing_tool->post_status, // Preserve status
        ]);

        $post_id = $tool_id;
    }
    // --- CREATE MODE ---
    else {
        $post_id = wp_insert_post([
            'post_type'   => defined('BV_CPT_TOOL') ? BV_CPT_TOOL : 'verktoy',
            'post_title'  => $tool_name,
            'post_content' => '',
            'post_status' => 'draft',
            'post_author' => $user_id,
        ], true);

        if (is_wp_error($post_id)) {
            error_log('BIMVerdi tool creation error: ' . $post_id->get_error_message());
            wp_redirect(add_query_arg('bv_error', 'system', $redirect_error));
            exit;
        }
    }

    // --- Save ACF fields ---
    if (function_exists('update_field')) {
        update_field('verktoy_navn', $tool_name, $post_id);
        update_field('kort_beskrivelse', $kort_beskrivelse, $post_id);
        update_field('detaljert_beskrivelse', $description, $post_id);
        update_field('versjon', $versjon, $post_id);
        update_field('verktoy_lenke', $tool_url, $post_id);
        update_field('produktbeskrivelse_url', $produktbeskrivelse_url, $post_id);
        update_field('nedlasting_url', $nedlasting_url, $post_id);
        update_field('eier_leverandor', intval($company_id), $post_id);

        if ($formaalstema) update_field('formaalstema', $formaalstema, $post_id);
        if ($bim_kompatibilitet) update_field('bim_kompatibilitet', $bim_kompatibilitet, $post_id);
        if ($type_ressurs) update_field('type_ressurs', $type_ressurs, $post_id);
        if ($type_teknologi) update_field('type_teknologi', $type_teknologi, $post_id);
        if (!empty($anvendelser)) update_field('anvendelser', array_values($anvendelser), $post_id);

        if ($logo_attachment_id) {
            update_field('verktoy_logo', $logo_attachment_id, $post_id);
            set_post_thumbnail($post_id, $logo_attachment_id);
        }
    } else {
        update_post_meta($post_id, 'verktoy_navn', $tool_name);
        update_post_meta($post_id, 'kort_beskrivelse', $kort_beskrivelse);
        update_post_meta($post_id, 'detaljert_beskrivelse', $description);
        update_post_meta($post_id, 'versjon', $versjon);
        update_post_meta($post_id, 'verktoy_lenke', $tool_url);
        update_post_meta($post_id, 'produktbeskrivelse_url', $produktbeskrivelse_url);
        update_post_meta($post_id, 'nedlasting_url', $nedlasting_url);
        update_post_meta($post_id, 'eier_leverandor', intval($company_id));

        if ($logo_attachment_id) {
            set_post_thumbnail($post_id, $logo_attachment_id);
        }
    }

    // Clear rate limit on success
    delete_transient($rate_key);

    $action = $is_edit ? 'updated' : 'registered';
    error_log("BIMVerdi: Tool {$action}: {$post_id} ({$tool_name}) by user {$user_id}");

    $param = $is_edit ? 'updated' : 'registered';
    wp_redirect(add_query_arg($param, '1', home_url('/min-side/verktoy/')));
    exit;
});
