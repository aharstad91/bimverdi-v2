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
 * Check if a foretak is Norwegian (eligible for BRREG lookup)
 *
 * @param int $post_id Foretak post ID
 * @return bool True if Norwegian or no land specified
 */
function bimverdi_is_norwegian_foretak($post_id) {
    $land = get_field('land', $post_id);

    // If no land specified, assume Norwegian
    if (empty($land)) {
        return true;
    }

    // Check for Norwegian variants
    $norwegian_values = array('norge', 'norway', 'no', 'nor');
    return in_array(strtolower(trim($land)), $norwegian_values);
}

/**
 * Check if an organization number is valid Norwegian format (9 digits)
 *
 * @param string $orgnr Organization number
 * @return bool True if valid Norwegian format
 */
function bimverdi_is_valid_norwegian_orgnr($orgnr) {
    return preg_match('/^\d{9}$/', $orgnr);
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
        $company['land'] = $addr['land'] ?? 'Norge';
        $company['landkode'] = $addr['landkode'] ?? 'NO';
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
 * =============================================================================
 * GRAVITY FORMS: Auto-sync Brreg data til ACF-felt ved foretak-opprettelse
 * =============================================================================
 * Når et foretak opprettes via Gravity Forms, hent full data fra Brreg
 * og lagre til ACF-feltene for å sikre at alt er korrekt.
 */
add_action('gform_advancedpostcreation_post_after_creation', 'bimverdi_sync_brreg_on_foretak_creation', 10, 4);

function bimverdi_sync_brreg_on_foretak_creation($post_id, $feed, $entry, $form) {
    // Sjekk at det er et foretak
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'foretak') {
        return;
    }

    // Sjekk om foretaket er norsk - hvis ikke, skip BRREG-synk
    if (!bimverdi_is_norwegian_foretak($post_id)) {
        error_log('BIM Verdi: Skipper BRREG-synk for utenlandsk foretak ' . $post_id);
        return;
    }

    // Hent org.nr fra entry eller ACF
    $org_nr = '';

    // Prøv å finne org.nr i entry (Field 1 i Form 2)
    if (is_array($entry)) {
        foreach ($entry as $key => $value) {
            if (is_numeric($key) && bimverdi_is_valid_norwegian_orgnr(trim($value))) {
                $org_nr = trim($value);
                break;
            }
        }
    }

    // Fallback: hent fra ACF
    if (!$org_nr) {
        $org_nr = get_field('organisasjonsnummer', $post_id);
    }

    if (!$org_nr || !bimverdi_is_valid_norwegian_orgnr($org_nr)) {
        error_log('BIM Verdi: Kunne ikke finne gyldig norsk org.nr for foretak ' . $post_id . ' (dette er OK for utenlandske foretak)');
        return;
    }

    // Hent data fra Brreg API
    $api_url = 'https://data.brreg.no/enhetsregisteret/api/enheter/' . $org_nr;

    $response = wp_remote_get($api_url, array(
        'timeout' => 10,
        'headers' => array('Accept' => 'application/json'),
    ));

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        error_log('BIM Verdi: Kunne ikke hente Brreg-data for org.nr ' . $org_nr);
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || !isset($data['organisasjonsnummer'])) {
        return;
    }

    // Oppdater ACF-felt med Brreg-data
    // Marker at dette er en Brreg-synk (bypass protection)
    do_action('bimverdi_brreg_sync');

    // Organisasjonsnummer
    update_field('organisasjonsnummer', $data['organisasjonsnummer'], $post_id);

    // Bedriftsnavn
    if (!empty($data['navn'])) {
        update_field('bedriftsnavn', $data['navn'], $post_id);

        // Oppdater også post_title hvis den er annerledes
        if ($post->post_title !== $data['navn']) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_title' => $data['navn'],
            ));
        }
    }

    // Adresse
    if (isset($data['forretningsadresse'])) {
        $addr = $data['forretningsadresse'];

        if (!empty($addr['adresse'])) {
            update_field('adresse', implode(', ', $addr['adresse']), $post_id);
        }
        if (!empty($addr['postnummer'])) {
            update_field('postnummer', $addr['postnummer'], $post_id);
        }
        if (!empty($addr['poststed'])) {
            update_field('poststed', $addr['poststed'], $post_id);
        }
        if (!empty($addr['land'])) {
            update_field('land', $addr['land'], $post_id);
        } else {
            update_field('land', 'Norge', $post_id);
        }
    }

    // Hjemmeside (hvis tilgjengelig fra Brreg)
    if (!empty($data['hjemmeside']) && empty(get_field('webside', $post_id))) {
        update_field('webside', $data['hjemmeside'], $post_id);
    }

    // Antall ansatte
    if (isset($data['antallAnsatte'])) {
        update_field('antall_ansatte', intval($data['antallAnsatte']), $post_id);
    }

    error_log('BIM Verdi: Synket Brreg-data for foretak ' . $post_id . ' (' . $data['navn'] . ')');
}

/**
 * Alternativ hook for standard post creation (ikke Advanced Post Creation)
 */
add_action('save_post_foretak', 'bimverdi_sync_brreg_on_foretak_save', 20, 3);

function bimverdi_sync_brreg_on_foretak_save($post_id, $post, $update) {
    // Ikke kjør på autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Ikke kjør ved revisjon
    if (wp_is_post_revision($post_id)) {
        return;
    }

    // Kun ved første opprettelse (ikke update)
    if ($update) {
        return;
    }

    // Sjekk om foretaket er norsk - hvis ikke, skip BRREG-synk
    if (!bimverdi_is_norwegian_foretak($post_id)) {
        return;
    }

    // Sjekk om Brreg-felt allerede er fylt ut
    $bedriftsnavn = get_field('bedriftsnavn', $post_id);
    $postnummer = get_field('postnummer', $post_id);

    // Hvis begge er fylt ut, anta at synk allerede er gjort
    if (!empty($bedriftsnavn) && !empty($postnummer)) {
        return;
    }

    // Hent org.nr - må være gyldig norsk format for BRREG-oppslag
    $org_nr = get_field('organisasjonsnummer', $post_id);
    if (!$org_nr || !bimverdi_is_valid_norwegian_orgnr($org_nr)) {
        return;
    }

    // Trigger synk (gjenbruk logikken)
    bimverdi_sync_brreg_on_foretak_creation($post_id, null, array(), null);
}

/**
 * =============================================================================
 * MIGRERING: Synk alle eksisterende foretak med Brreg-data
 * =============================================================================
 * Kjør én gang via: /wp-admin/?bimverdi_sync_all_foretak=1
 * Krever admin-tilgang.
 */
add_action('admin_init', 'bimverdi_maybe_sync_all_foretak');

function bimverdi_maybe_sync_all_foretak() {
    if (!isset($_GET['bimverdi_sync_all_foretak']) || $_GET['bimverdi_sync_all_foretak'] !== '1') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die('Du har ikke tilgang til denne funksjonen.');
    }

    // Hent alle foretak
    $foretak = get_posts(array(
        'post_type' => 'foretak',
        'post_status' => 'any',
        'posts_per_page' => -1,
    ));

    $results = array(
        'total' => count($foretak),
        'synced' => 0,
        'skipped' => 0,
        'errors' => array(),
    );

    foreach ($foretak as $post) {
        // Sjekk om foretaket er norsk
        if (!bimverdi_is_norwegian_foretak($post->ID)) {
            $results['skipped']++;
            $results['errors'][] = "ID {$post->ID}: Utenlandsk foretak (skipper BRREG)";
            continue;
        }

        $org_nr = get_field('organisasjonsnummer', $post->ID);

        if (!$org_nr || !bimverdi_is_valid_norwegian_orgnr($org_nr)) {
            $results['skipped']++;
            $results['errors'][] = "ID {$post->ID}: Mangler gyldig norsk org.nr";
            continue;
        }

        // Hent fra Brreg API
        $api_url = 'https://data.brreg.no/enhetsregisteret/api/enheter/' . $org_nr;
        $response = wp_remote_get($api_url, array(
            'timeout' => 10,
            'headers' => array('Accept' => 'application/json'),
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $results['skipped']++;
            $results['errors'][] = "ID {$post->ID}: API-feil for org.nr {$org_nr}";
            continue;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!$data || !isset($data['organisasjonsnummer'])) {
            $results['skipped']++;
            continue;
        }

        // Lagre til ACF-felt
        do_action('bimverdi_brreg_sync');

        if (!empty($data['navn'])) {
            update_field('bedriftsnavn', $data['navn'], $post->ID);
        }

        if (isset($data['forretningsadresse'])) {
            $addr = $data['forretningsadresse'];
            if (!empty($addr['adresse'])) {
                update_field('adresse', implode(', ', $addr['adresse']), $post->ID);
            }
            if (!empty($addr['postnummer'])) {
                update_field('postnummer', $addr['postnummer'], $post->ID);
            }
            if (!empty($addr['poststed'])) {
                update_field('poststed', $addr['poststed'], $post->ID);
            }
            update_field('land', $addr['land'] ?? 'Norge', $post->ID);
        }

        // Antall ansatte
        if (isset($data['antallAnsatte'])) {
            update_field('antall_ansatte', intval($data['antallAnsatte']), $post->ID);
        }

        $results['synced']++;

        // Liten pause for å ikke overbelaste API
        usleep(100000); // 0.1 sekund
    }

    // Vis resultat
    set_transient('bimverdi_sync_results', $results, 60);
    wp_redirect(admin_url('edit.php?post_type=foretak&bimverdi_sync_done=1'));
    exit;
}

// Vis resultat-melding
add_action('admin_notices', function() {
    if (!isset($_GET['bimverdi_sync_done'])) {
        return;
    }

    $results = get_transient('bimverdi_sync_results');
    delete_transient('bimverdi_sync_results');

    if (!$results) {
        return;
    }

    echo '<div class="notice notice-success is-dismissible">';
    echo '<p><strong>Brreg-synk fullført!</strong></p>';
    echo '<ul>';
    echo '<li>Totalt: ' . $results['total'] . ' foretak</li>';
    echo '<li>Synket: ' . $results['synced'] . '</li>';
    echo '<li>Hoppet over: ' . $results['skipped'] . '</li>';
    echo '</ul>';

    if (!empty($results['errors'])) {
        echo '<details><summary>Feil (' . count($results['errors']) . ')</summary><ul>';
        foreach (array_slice($results['errors'], 0, 10) as $error) {
            echo '<li>' . esc_html($error) . '</li>';
        }
        echo '</ul></details>';
    }

    echo '</div>';
});

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
