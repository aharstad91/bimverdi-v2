<?php
/**
 * BIM Verdi - Brønnøysund Register API Integration
 * 
 * Provides REST endpoints for searching the Norwegian Business Registry (Enhetsregisteret).
 * Used for autocomplete when registering companies.
 * 
 * @package BIM_Verdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register REST API routes for BRreg
 */
add_action('rest_api_init', function() {
    // Search by name or org number
    register_rest_route('bimverdi/v1', '/brreg/search', array(
        'methods' => 'GET',
        'callback' => 'bimverdi_brreg_search',
        'permission_callback' => 'is_user_logged_in',
        'args' => array(
            'query' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'description' => 'Search query (name or org number)',
            ),
        ),
    ));
    
    // Get single company by org number
    register_rest_route('bimverdi/v1', '/brreg/company/(?P<orgnr>\d{9})', array(
        'methods' => 'GET',
        'callback' => 'bimverdi_brreg_get_company',
        'permission_callback' => 'is_user_logged_in',
        'args' => array(
            'orgnr' => array(
                'required' => true,
                'type' => 'string',
                'validate_callback' => function($param) {
                    return preg_match('/^\d{9}$/', $param);
                },
                'description' => '9-digit organization number',
            ),
        ),
    ));
    
    // Check if org number already registered
    register_rest_route('bimverdi/v1', '/brreg/check-registered/(?P<orgnr>\d{9})', array(
        'methods' => 'GET',
        'callback' => 'bimverdi_brreg_check_registered',
        'permission_callback' => 'is_user_logged_in',
        'args' => array(
            'orgnr' => array(
                'required' => true,
                'type' => 'string',
                'validate_callback' => function($param) {
                    return preg_match('/^\d{9}$/', $param);
                },
            ),
        ),
    ));
});

/**
 * Search BRreg API
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function bimverdi_brreg_search($request) {
    $query = $request->get_param('query');
    
    if (strlen($query) < 3) {
        return new WP_Error('too_short', 'Søket må være minst 3 tegn', array('status' => 400));
    }
    
    // Check cache first
    $cache_key = 'brreg_search_' . md5($query);
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        return rest_ensure_response(array(
            'success' => true,
            'cached' => true,
            'results' => $cached,
        ));
    }
    
    // Determine if query is org number or name
    $is_orgnr = preg_match('/^\d{9}$/', preg_replace('/\s/', '', $query));
    
    if ($is_orgnr) {
        $api_url = 'https://data.brreg.no/enhetsregisteret/api/enheter/' . preg_replace('/\s/', '', $query);
    } else {
        $api_url = add_query_arg(array(
            'navn' => urlencode($query),
            'size' => 10,
        ), 'https://data.brreg.no/enhetsregisteret/api/enheter');
    }
    
    $response = wp_remote_get($api_url, array(
        'timeout' => 10,
        'headers' => array(
            'Accept' => 'application/json',
        ),
    ));
    
    if (is_wp_error($response)) {
        return new WP_Error('api_error', 'Kunne ikke kontakte Brønnøysundregistrene: ' . $response->get_error_message(), array('status' => 500));
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($status_code === 404) {
        return rest_ensure_response(array(
            'success' => true,
            'cached' => false,
            'results' => array(),
        ));
    }
    
    if ($status_code !== 200) {
        return new WP_Error('api_error', 'Feil fra Brønnøysundregistrene (HTTP ' . $status_code . ')', array('status' => 500));
    }
    
    // Parse results
    $results = array();
    
    if ($is_orgnr && isset($data['organisasjonsnummer'])) {
        // Single company result
        $results[] = bimverdi_brreg_format_company($data);
    } elseif (isset($data['_embedded']['enheter'])) {
        // Multiple results
        foreach ($data['_embedded']['enheter'] as $company) {
            $results[] = bimverdi_brreg_format_company($company);
        }
    }
    
    // Cache for 15 minutes
    set_transient($cache_key, $results, 15 * MINUTE_IN_SECONDS);
    
    return rest_ensure_response(array(
        'success' => true,
        'cached' => false,
        'results' => $results,
    ));
}

/**
 * Get single company from BRreg
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function bimverdi_brreg_get_company($request) {
    $orgnr = $request->get_param('orgnr');
    
    // Check cache first
    $cache_key = 'brreg_company_' . $orgnr;
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        return rest_ensure_response(array(
            'success' => true,
            'cached' => true,
            'company' => $cached,
        ));
    }
    
    $api_url = 'https://data.brreg.no/enhetsregisteret/api/enheter/' . $orgnr;
    
    $response = wp_remote_get($api_url, array(
        'timeout' => 10,
        'headers' => array(
            'Accept' => 'application/json',
        ),
    ));
    
    if (is_wp_error($response)) {
        return new WP_Error('api_error', 'Kunne ikke kontakte Brønnøysundregistrene', array('status' => 500));
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    
    if ($status_code === 404) {
        return new WP_Error('not_found', 'Organisasjonsnummer ikke funnet', array('status' => 404));
    }
    
    if ($status_code !== 200) {
        return new WP_Error('api_error', 'Feil fra Brønnøysundregistrene', array('status' => 500));
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    $company = bimverdi_brreg_format_company($data);
    
    // Cache for 15 minutes
    set_transient($cache_key, $company, 15 * MINUTE_IN_SECONDS);
    
    return rest_ensure_response(array(
        'success' => true,
        'cached' => false,
        'company' => $company,
    ));
}

/**
 * Check if org number is already registered as foretak
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function bimverdi_brreg_check_registered($request) {
    $orgnr = $request->get_param('orgnr');
    
    // Search for existing foretak with this org number
    $existing = get_posts(array(
        'post_type' => 'foretak',
        'post_status' => array('publish', 'pending', 'draft'),
        'meta_query' => array(
            array(
                'key' => 'organisasjonsnummer',
                'value' => $orgnr,
                'compare' => '=',
            ),
        ),
        'posts_per_page' => 1,
    ));
    
    $is_registered = !empty($existing);
    $foretak_id = $is_registered ? $existing[0]->ID : null;
    $foretak_name = $is_registered ? $existing[0]->post_title : null;
    
    return rest_ensure_response(array(
        'success' => true,
        'is_registered' => $is_registered,
        'foretak_id' => $foretak_id,
        'foretak_name' => $foretak_name,
    ));
}

/**
 * Format company data from BRreg API response
 * 
 * @param array $data Raw API response
 * @return array Formatted company data
 */
function bimverdi_brreg_format_company($data) {
    $company = array(
        'orgnr' => $data['organisasjonsnummer'] ?? '',
        'navn' => $data['navn'] ?? '',
        'organisasjonsform' => $data['organisasjonsform']['beskrivelse'] ?? '',
        'organisasjonsform_kode' => $data['organisasjonsform']['kode'] ?? '',
    );
    
    // Forretningsadresse (business address)
    if (isset($data['forretningsadresse'])) {
        $addr = $data['forretningsadresse'];
        $company['adresse'] = implode(', ', $addr['adresse'] ?? array());
        $company['postnummer'] = $addr['postnummer'] ?? '';
        $company['poststed'] = $addr['poststed'] ?? '';
        $company['kommune'] = $addr['kommune'] ?? '';
        $company['kommunenummer'] = $addr['kommunenummer'] ?? '';
    }
    
    // Postadresse (if different)
    if (isset($data['postadresse'])) {
        $post = $data['postadresse'];
        $company['postadresse'] = implode(', ', $post['adresse'] ?? array());
        $company['postadresse_postnummer'] = $post['postnummer'] ?? '';
        $company['postadresse_poststed'] = $post['poststed'] ?? '';
    }
    
    // Næringskoder (industry codes)
    if (isset($data['naeringskode1'])) {
        $company['naeringskode'] = $data['naeringskode1']['kode'] ?? '';
        $company['naeringskode_beskrivelse'] = $data['naeringskode1']['beskrivelse'] ?? '';
    }
    
    // Additional info
    $company['stiftelsesdato'] = $data['stiftelsesdato'] ?? '';
    $company['registreringsdato'] = $data['registreringsdatoEnhetsregisteret'] ?? '';
    $company['antall_ansatte'] = $data['antallAnsatte'] ?? 0;
    $company['hjemmeside'] = $data['hjemmeside'] ?? '';
    
    // Status flags
    $company['konkurs'] = $data['konkurs'] ?? false;
    $company['under_avvikling'] = $data['underAvvikling'] ?? false;
    $company['under_tvangsavvikling'] = $data['underTvangsavviklingEllerTvangsopplosning'] ?? false;
    
    return $company;
}

/**
 * Enqueue BRreg autocomplete JavaScript
 */
add_action('wp_enqueue_scripts', function() {
    // Load on foretak-related pages
    $should_load = false;
    
    // Check for specific page templates
    if (is_page_template('template-minside-registrer-foretak.php') || 
        is_page_template('template-minside-foretak.php')) {
        $should_load = true;
    }
    
    // Check for pages with "registrer" or "foretak" in slug
    if (is_page()) {
        global $post;
        if ($post && (
            strpos($post->post_name, 'foretak') !== false ||
            strpos($post->post_name, 'registrer') !== false
        )) {
            $should_load = true;
        }
    }
    
    // Also load if Gravity Forms Form 2 is embedded
    global $post;
    if ($post && (
        has_shortcode($post->post_content, 'gravityform') && 
        strpos($post->post_content, 'id="2"') !== false
    )) {
        $should_load = true;
    }
    
    // Always load on Min Side pages for now (simpler)
    if (is_page() && strpos($_SERVER['REQUEST_URI'], 'min-side') !== false) {
        $should_load = true;
    }
    
    if (!$should_load) {
        return;
    }
    
    wp_enqueue_script(
        'bimverdi-brreg-autocomplete',
        get_template_directory_uri() . '/assets/js/brreg-autocomplete.js',
        array(),
        '1.1.0', // Bumped version
        true
    );
    
    wp_localize_script('bimverdi-brreg-autocomplete', 'bimverdiBrreg', array(
        'restUrl' => rest_url('bimverdi/v1/brreg/'),
        'nonce' => wp_create_nonce('wp_rest'),
    ));
});
