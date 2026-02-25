<?php
/**
 * BIM Verdi - Kunnskapskilde Registration Handler
 *
 * Handles plain HTML form submission for creating/editing kunnskapskilder.
 * Replaces Gravity Forms Forms #19-23 + class-kunnskapskilde-form-handler.php.
 *
 * Pattern: POST-Redirect-GET (PRG)
 * - Create success: /min-side/kunnskapskilder/?registered=1
 * - Edit success:   /min-side/kunnskapskilder/?updated=1
 * - Error:          referrer URL with ?bv_error=<code>
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle kunnskapskilde registration/edit form submission
 */
add_action('init', function () {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $is_create = isset($_POST['bimverdi_register_kunnskapskilde']);
    $is_edit = isset($_POST['bimverdi_edit_kunnskapskilde']);

    if (!$is_create && !$is_edit) {
        return;
    }

    // Must be logged in
    if (!is_user_logged_in()) {
        wp_redirect(home_url('/logg-inn/'));
        exit;
    }

    $user_id = get_current_user_id();
    $kunnskapskilde_id = intval($_POST['kunnskapskilde_id'] ?? 0);
    $redirect_error = $is_edit
        ? home_url('/min-side/kunnskapskilder/rediger/?kunnskapskilde_id=' . $kunnskapskilde_id)
        : home_url('/min-side/kunnskapskilder/registrer/');

    // Verify nonce
    $nonce_action = $is_edit ? 'bimverdi_edit_kunnskapskilde' : 'bimverdi_register_kunnskapskilde';
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', $nonce_action)) {
        wp_redirect(add_query_arg('bv_error', 'nonce', $redirect_error));
        exit;
    }

    // Honeypot
    if (!empty($_POST['bv_website_url'] ?? '')) {
        wp_redirect(home_url('/min-side/kunnskapskilder/' . ($is_edit ? '?updated=1' : '?registered=1')));
        exit;
    }

    // Rate limiting
    $rate_key = 'bv_kilde_reg_' . $user_id;
    $attempts = (int) get_transient($rate_key);
    if ($attempts >= 5) {
        wp_redirect(add_query_arg('bv_error', 'rate_limit', $redirect_error));
        exit;
    }
    set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);

    // Get company (optional for kunnskapskilder)
    $company_id = get_user_meta($user_id, 'bimverdi_company_id', true)
               ?: get_user_meta($user_id, 'bim_verdi_company_id', true);

    // --- Sanitize inputs ---
    $navn                   = sanitize_text_field($_POST['kunnskapskilde_navn'] ?? '');
    $kort_beskrivelse       = sanitize_textarea_field($_POST['kort_beskrivelse'] ?? '');
    $detaljert_beskrivelse  = wp_kses_post($_POST['detaljert_beskrivelse'] ?? '');
    $ekstern_lenke          = esc_url_raw($_POST['ekstern_lenke'] ?? '');
    $utgiver                = sanitize_text_field($_POST['utgiver'] ?? '');
    $spraak                 = sanitize_text_field($_POST['spraak'] ?? '');
    $versjon                = sanitize_text_field($_POST['versjon'] ?? '');
    $utgivelsesaar          = sanitize_text_field($_POST['utgivelsesaar'] ?? '');
    $tilgang                = sanitize_text_field($_POST['tilgang'] ?? '');
    $kildetype              = sanitize_text_field($_POST['kildetype'] ?? '');
    $geografisk_gyldighet   = sanitize_text_field($_POST['geografisk_gyldighet'] ?? '');
    $dataformat             = sanitize_text_field($_POST['dataformat'] ?? '');
    $ant_lovpalagte         = sanitize_text_field($_POST['ant_lovpalagte_standarder'] ?? '');
    $lovpalagte             = sanitize_text_field($_POST['lovpalagte_standarder'] ?? '');
    $ant_anbefalte          = sanitize_text_field($_POST['ant_anbefalte_standarder'] ?? '');
    $anbefalte              = sanitize_text_field($_POST['anbefalte_standarder'] ?? '');
    $temagrupper            = array_map('sanitize_text_field', (array) ($_POST['temagrupper'] ?? []));
    $kategorier             = array_map('sanitize_text_field', (array) ($_POST['kategorier'] ?? []));

    // --- Validate required fields ---
    if (empty($navn)) {
        wp_redirect(add_query_arg('bv_error', 'missing_name', $redirect_error));
        exit;
    }

    if (empty($ekstern_lenke)) {
        wp_redirect(add_query_arg('bv_error', 'missing_url', $redirect_error));
        exit;
    }

    if (empty($kildetype)) {
        wp_redirect(add_query_arg('bv_error', 'missing_kildetype', $redirect_error));
        exit;
    }

    // Validate select values
    $allowed_kildetype = ['standard', 'veiledning', 'forskrift_norsk', 'forordning_eu', 'mal', 'forskningsrapport', 'casestudie', 'opplaering', 'dokumentasjon', 'nettressurs', 'annet'];
    if (!in_array($kildetype, $allowed_kildetype)) {
        $kildetype = '';
    }

    $allowed_spraak = ['norsk', 'engelsk', 'svensk', 'dansk', 'flerspraklig', 'annet'];
    if ($spraak && !in_array($spraak, $allowed_spraak)) {
        $spraak = '';
    }

    $allowed_geo = ['nasjonalt', 'nordisk', 'europeisk', 'internasjonalt', 'annet'];
    if ($geografisk_gyldighet && !in_array($geografisk_gyldighet, $allowed_geo)) {
        $geografisk_gyldighet = '';
    }

    $allowed_dataformat = ['pdf', 'web_aapent', 'web_lukket', 'api', 'ifc', 'database', 'annet'];
    if ($dataformat && !in_array($dataformat, $allowed_dataformat)) {
        $dataformat = '';
    }

    $allowed_tilgang = ['gratis', 'betalt', 'abonnement', 'ukjent'];
    if ($tilgang && !in_array($tilgang, $allowed_tilgang)) {
        $tilgang = '';
    }

    // --- URL uniqueness validation ---
    $url_check_args = [
        'post_type'   => 'kunnskapskilde',
        'post_status' => ['publish', 'draft', 'pending'],
        'meta_query'  => [
            'relation' => 'OR',
            ['key' => 'ekstern_lenke', 'value' => $ekstern_lenke, 'compare' => '='],
            ['key' => 'ekstern_lenke', 'value' => trailingslashit($ekstern_lenke), 'compare' => '='],
            ['key' => 'ekstern_lenke', 'value' => untrailingslashit($ekstern_lenke), 'compare' => '='],
        ],
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ];

    // Exclude current post when editing
    if ($is_edit && $kunnskapskilde_id) {
        $url_check_args['post__not_in'] = [$kunnskapskilde_id];
    }

    $existing_url = get_posts($url_check_args);
    if (!empty($existing_url)) {
        wp_redirect(add_query_arg('bv_error', 'url_duplicate', $redirect_error));
        exit;
    }

    // --- EDIT MODE ---
    if ($is_edit) {
        $existing = get_post($kunnskapskilde_id);

        if (!$existing || $existing->post_type !== 'kunnskapskilde') {
            wp_redirect(add_query_arg('bv_error', 'not_found', $redirect_error));
            exit;
        }

        // Check permission
        $registrert_av = get_field('registrert_av', $kunnskapskilde_id);
        $kilde_company = get_field('tilknyttet_bedrift', $kunnskapskilde_id);
        $can_edit = ($existing->post_author == $user_id)
                 || ($registrert_av && $registrert_av == $user_id)
                 || ($company_id && $kilde_company && $kilde_company == $company_id)
                 || current_user_can('manage_options');

        if (!$can_edit) {
            wp_redirect(add_query_arg('bv_error', 'permission', $redirect_error));
            exit;
        }

        wp_update_post([
            'ID'          => $kunnskapskilde_id,
            'post_title'  => $navn,
            'post_status' => $existing->post_status,
        ]);

        $post_id = $kunnskapskilde_id;
    }
    // --- CREATE MODE ---
    else {
        if (!isset($_POST['samtykke']) || empty($_POST['samtykke'])) {
            wp_redirect(add_query_arg('bv_error', 'missing_consent', $redirect_error));
            exit;
        }

        $post_id = wp_insert_post([
            'post_type'   => 'kunnskapskilde',
            'post_title'  => $navn,
            'post_content' => '',
            'post_status' => 'publish', // Auto-publish
            'post_author' => $user_id,
        ], true);

        if (is_wp_error($post_id)) {
            error_log('BIMVerdi kunnskapskilde creation error: ' . $post_id->get_error_message());
            wp_redirect(add_query_arg('bv_error', 'system', $redirect_error));
            exit;
        }
    }

    // --- Save ACF fields ---
    if (function_exists('update_field')) {
        update_field('kunnskapskilde_navn', $navn, $post_id);
        update_field('kort_beskrivelse', $kort_beskrivelse, $post_id);
        update_field('detaljert_beskrivelse', $detaljert_beskrivelse, $post_id);
        update_field('ekstern_lenke', $ekstern_lenke, $post_id);
        update_field('utgiver', $utgiver, $post_id);
        if ($spraak) update_field('spraak', $spraak, $post_id);
        update_field('versjon', $versjon, $post_id);
        if ($utgivelsesaar) update_field('utgivelsesaar', $utgivelsesaar, $post_id);
        if ($tilgang) update_field('tilgang', $tilgang, $post_id);
        if ($kildetype) update_field('kildetype', $kildetype, $post_id);
        if ($geografisk_gyldighet) update_field('geografisk_gyldighet', $geografisk_gyldighet, $post_id);
        if ($dataformat) update_field('dataformat', $dataformat, $post_id);
        update_field('ant_lovpalagte_standarder', $ant_lovpalagte, $post_id);
        update_field('lovpalagte_standarder', $lovpalagte, $post_id);
        update_field('ant_anbefalte_standarder', $ant_anbefalte, $post_id);
        update_field('anbefalte_standarder', $anbefalte, $post_id);
        update_field('registrert_av', $user_id, $post_id);

        if ($company_id) {
            update_field('tilknyttet_bedrift', intval($company_id), $post_id);
        }
    }

    // --- Save taxonomies ---
    if (!empty($temagrupper)) {
        wp_set_object_terms($post_id, $temagrupper, 'temagruppe');
    }
    if (!empty($kategorier)) {
        wp_set_object_terms($post_id, $kategorier, 'kunnskapskildekategori');
    }

    // Clear rate limit
    delete_transient($rate_key);

    $action = $is_edit ? 'updated' : 'registered';
    error_log("BIMVerdi: Kunnskapskilde {$action}: {$post_id} ({$navn}) by user {$user_id}");

    $param = $is_edit ? 'updated' : 'registered';
    wp_redirect(add_query_arg($param, '1', home_url('/min-side/kunnskapskilder/')));
    exit;
});
