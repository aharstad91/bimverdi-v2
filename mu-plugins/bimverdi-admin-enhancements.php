<?php
/**
 * BIM Verdi - Admin Enhancements
 *
 * Forbedringer for admin-grensesnittet:
 * - ID-kolonne for alle CPT-er (for rask navigering)
 * - Låste Brreg-felt vises som read-only
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * =============================================================================
 * 1. ID-KOLONNE FOR ALLE CPT-ER
 * =============================================================================
 * Bård bruker URL-endring for rask navigering mellom posts
 * (f.eks. deltaker=20 → deltaker=48)
 */

/**
 * Liste over CPT-er som skal ha ID-kolonne
 */
function bimverdi_get_cpts_for_id_column() {
    return array(
        'foretak',
        'verktoy',
        'arrangement',
        'pamelding',
        'case',
        'prosjekt',
        'theme_group',
        'artikkel',
        'kunnskapskilde',
    );
}

/**
 * Legg til ID-kolonne for alle CPT-er
 */
add_action('admin_init', 'bimverdi_add_id_columns_to_cpts');

function bimverdi_add_id_columns_to_cpts() {
    $cpts = bimverdi_get_cpts_for_id_column();

    foreach ($cpts as $cpt) {
        // Legg til kolonne
        add_filter("manage_{$cpt}_posts_columns", 'bimverdi_add_id_column');
        // Vis ID i kolonnen
        add_action("manage_{$cpt}_posts_custom_column", 'bimverdi_display_id_column', 10, 2);
        // Gjør kolonnen sorterbar
        add_filter("manage_edit-{$cpt}_sortable_columns", 'bimverdi_sortable_id_column');
    }
}

/**
 * Legg til ID-kolonne først i listen
 */
function bimverdi_add_id_column($columns) {
    $new_columns = array();

    // Legg til checkbox først hvis den finnes
    if (isset($columns['cb'])) {
        $new_columns['cb'] = $columns['cb'];
        unset($columns['cb']);
    }

    // Legg til ID-kolonne
    $new_columns['post_id'] = __('ID', 'bimverdi');

    // Legg til resten av kolonnene
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
    }

    return $new_columns;
}

/**
 * Vis ID i kolonnen
 */
function bimverdi_display_id_column($column, $post_id) {
    if ($column === 'post_id') {
        echo '<code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 12px;">' . $post_id . '</code>';
    }
}

/**
 * Gjør ID-kolonnen sorterbar
 */
function bimverdi_sortable_id_column($columns) {
    $columns['post_id'] = 'ID';
    return $columns;
}

/**
 * Sett kolonnebredde for ID
 */
add_action('admin_head', 'bimverdi_id_column_styles');

function bimverdi_id_column_styles() {
    $screen = get_current_screen();
    if (!$screen) return;

    $cpts = bimverdi_get_cpts_for_id_column();

    // Sjekk om vi er på en CPT-liste
    if ($screen->base === 'edit' && in_array($screen->post_type, $cpts)) {
        echo '<style>
            .column-post_id {
                width: 60px;
            }
        </style>';
    }
}


/**
 * =============================================================================
 * 2. LÅSTE BRREG-FELT I ADMIN (READ-ONLY)
 * =============================================================================
 * Felt som synkes fra Brønnøysundregistrene skal være låst.
 * Brreg = source of truth
 *
 * Låste felt: Bedriftsnavn, Org.nr, Adresse, Postnummer, Poststed
 * Åpne felt: Bedriftsbeskrivelse, Logo, Nettside
 */

/**
 * Liste over Brreg-synkede felt som skal være låst
 */
function bimverdi_get_locked_brreg_fields() {
    return array(
        'bedriftsnavn',
        'organisasjonsnummer',
        'adresse',
        'postnummer',
        'poststed',
        'land',
    );
}

/**
 * Legg til info-melding øverst i foretak edit-skjerm
 */
add_action('edit_form_top', 'bimverdi_show_brreg_locked_notice');

function bimverdi_show_brreg_locked_notice($post) {
    if ($post->post_type !== 'foretak') {
        return;
    }

    $org_nr = get_field('organisasjonsnummer', $post->ID);
    if (!$org_nr) {
        return;
    }

    ?>
    <div class="notice notice-info inline" style="margin: 15px 0;">
        <p>
            <strong>Brreg-data:</strong>
            Felt markert med <span style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
            <span class="dashicons dashicons-lock" style="font-size: 12px; width: 12px; height: 12px; line-height: 12px;"></span> Låst</span>
            hentes fra Brønnøysundregistrene og kan ikke redigeres direkte.
        </p>
    </div>
    <?php
}

/**
 * Merk låste ACF-felt som read-only via ACF filter (før rendering)
 */
add_filter('acf/load_field', 'bimverdi_set_brreg_fields_readonly', 10, 1);

function bimverdi_set_brreg_fields_readonly($field) {
    // Sjekk om vi er på foretak edit-skjerm i admin
    if (!is_admin()) {
        return $field;
    }

    global $pagenow, $typenow;

    // Kun på post.php (edit) eller post-new.php (new)
    if (!in_array($pagenow, ['post.php', 'post-new.php'])) {
        return $field;
    }

    // Sjekk post type
    if (!$typenow) {
        $typenow = isset($_GET['post_type']) ? $_GET['post_type'] : '';
        if (!$typenow && isset($_GET['post'])) {
            $typenow = get_post_type($_GET['post']);
        }
    }

    if ($typenow !== 'foretak') {
        return $field;
    }

    $locked_fields = bimverdi_get_locked_brreg_fields();

    if (in_array($field['name'], $locked_fields)) {
        // Sett readonly attributt
        $field['readonly'] = 1;
        $field['disabled'] = 0; // Ikke disabled, bare readonly (så verdien fortsatt sendes)

        // Legg til CSS-klasse for styling
        $field['wrapper']['class'] = isset($field['wrapper']['class'])
            ? $field['wrapper']['class'] . ' bimverdi-brreg-locked'
            : 'bimverdi-brreg-locked';
    }

    return $field;
}

/**
 * Beskytt låste felt fra å bli oppdatert (ekstra sikkerhet)
 */
add_filter('acf/update_value', 'bimverdi_protect_brreg_fields', 10, 3);

function bimverdi_protect_brreg_fields($value, $post_id, $field) {
    // Kun for foretak CPT
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'foretak') {
        return $value;
    }

    // Tillat oppdatering fra Brreg-synk (spesifikk action)
    if (doing_action('bimverdi_brreg_sync')) {
        return $value;
    }

    $locked_fields = bimverdi_get_locked_brreg_fields();

    // Hvis feltet er låst, behold eksisterende verdi
    if (in_array($field['name'], $locked_fields)) {
        $existing_value = get_field($field['name'], $post_id);
        if (!empty($existing_value)) {
            return $existing_value;
        }
    }

    return $value;
}

/**
 * Auto-populer Brreg-felt fra API hvis de er tomme
 * Henter data direkte fra Brønnøysundregistrene basert på org.nr
 */
add_filter('acf/load_value', 'bimverdi_auto_populate_from_brreg', 10, 3);

function bimverdi_auto_populate_from_brreg($value, $post_id, $field) {
    // Kun for tomme verdier
    if (!empty($value)) {
        return $value;
    }

    // Kun for foretak
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'foretak') {
        return $value;
    }

    // Kun for Brreg-synkede felt
    $brreg_fields = array('bedriftsnavn', 'adresse', 'postnummer', 'poststed', 'land');
    if (!in_array($field['name'], $brreg_fields)) {
        return $value;
    }

    // Hent cached Brreg-data eller fetch fra API
    $brreg_data = bimverdi_get_cached_brreg_data($post_id);

    if (!$brreg_data) {
        // Fallback til post_title for bedriftsnavn
        if ($field['name'] === 'bedriftsnavn') {
            return $post->post_title;
        }
        return $value;
    }

    // Map felt til Brreg-data
    $field_mapping = array(
        'bedriftsnavn' => 'navn',
        'adresse'      => 'adresse',
        'postnummer'   => 'postnummer',
        'poststed'     => 'poststed',
        'land'         => 'land',
    );

    $brreg_key = $field_mapping[$field['name']] ?? null;

    if ($brreg_key && isset($brreg_data[$brreg_key])) {
        return $brreg_data[$brreg_key];
    }

    // Fallback til post_title for bedriftsnavn
    if ($field['name'] === 'bedriftsnavn') {
        return $post->post_title;
    }

    return $value;
}

/**
 * Hent Brreg-data med caching (per request + transient)
 */
function bimverdi_get_cached_brreg_data($post_id) {
    // Per-request cache
    static $cache = array();

    if (isset($cache[$post_id])) {
        return $cache[$post_id];
    }

    // Hent org.nr
    $org_nr = get_field('organisasjonsnummer', $post_id);
    if (!$org_nr || !preg_match('/^\d{9}$/', $org_nr)) {
        $cache[$post_id] = false;
        return false;
    }

    // Sjekk transient cache først (24 timer)
    $transient_key = 'brreg_foretak_' . $org_nr;
    $cached_data = get_transient($transient_key);

    if ($cached_data !== false) {
        $cache[$post_id] = $cached_data;
        return $cached_data;
    }

    // Hent fra Brreg API
    $api_url = 'https://data.brreg.no/enhetsregisteret/api/enheter/' . $org_nr;

    $response = wp_remote_get($api_url, array(
        'timeout' => 5,
        'headers' => array('Accept' => 'application/json'),
    ));

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        $cache[$post_id] = false;
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || !isset($data['organisasjonsnummer'])) {
        $cache[$post_id] = false;
        return false;
    }

    // Formater data
    $brreg_data = array(
        'navn'       => $data['navn'] ?? '',
        'adresse'    => isset($data['forretningsadresse']['adresse'])
                        ? implode(', ', $data['forretningsadresse']['adresse'])
                        : '',
        'postnummer' => $data['forretningsadresse']['postnummer'] ?? '',
        'poststed'   => $data['forretningsadresse']['poststed'] ?? '',
        'land'       => $data['forretningsadresse']['land'] ?? 'Norge',
    );

    // Cache i 24 timer
    set_transient($transient_key, $brreg_data, DAY_IN_SECONDS);

    $cache[$post_id] = $brreg_data;
    return $brreg_data;
}

/**
 * =============================================================================
 * 3. AUTO-PREPEND HTTPS:// TIL URL-FELT
 * =============================================================================
 * ACF URL-felt krever gyldig URL med protokoll.
 * Dette filteret legger automatisk til https:// hvis brukeren glemmer det.
 */

/**
 * Auto-prepend https:// til URL-felt hvis det mangler protokoll
 */
add_filter('acf/update_value/type=url', 'bimverdi_auto_prepend_https_to_url', 10, 3);

function bimverdi_auto_prepend_https_to_url($value, $post_id, $field) {
    // Skip empty values
    if (empty($value)) {
        return $value;
    }

    // Trim whitespace
    $value = trim($value);

    // Skip if already has protocol
    if (preg_match('/^https?:\/\//i', $value)) {
        return $value;
    }

    // Skip if it starts with // (protocol-relative URL)
    if (strpos($value, '//') === 0) {
        return 'https:' . $value;
    }

    // Add https:// prefix
    return 'https://' . $value;
}

/**
 * =============================================================================
 * 4. SKJUL STANDARD "INNLEGG" (POSTS) FRA ADMIN-MENYEN
 * =============================================================================
 * BIM Verdi bruker kun egne CPT-er (artikkel, kunnskapskilde osv.).
 * Standard WordPress-innlegg brukes ikke og fjernes fra menyen for å unngå forvirring.
 */
add_action('admin_menu', 'bimverdi_hide_default_posts_menu');

function bimverdi_hide_default_posts_menu() {
    remove_menu_page('edit.php');
}

/**
 * Legg til CSS for locked fields i admin
 */
add_action('admin_head', 'bimverdi_locked_fields_admin_css');

function bimverdi_locked_fields_admin_css() {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'foretak') {
        return;
    }

    ?>
    <style>
        /* Locked Brreg field styling */
        .bimverdi-brreg-locked input[readonly],
        .bimverdi-brreg-locked textarea[readonly] {
            background-color: #f5f5f5 !important;
            border-color: #ddd !important;
            color: #666 !important;
            cursor: not-allowed !important;
        }

        .bimverdi-brreg-locked input[readonly]:focus,
        .bimverdi-brreg-locked textarea[readonly]:focus {
            border-color: #ddd !important;
            box-shadow: none !important;
            outline: none !important;
        }

        /* Subtle background for locked field wrapper */
        .bimverdi-brreg-locked {
            position: relative;
        }

        .bimverdi-brreg-locked::before {
            content: '';
            position: absolute;
            top: 0;
            left: -12px;
            width: 4px;
            height: 100%;
            background: #e5e5e5;
            border-radius: 2px;
        }

        /* Lock badge styling */
        .bimverdi-lock-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            color: #666;
            margin-left: 6px;
            vertical-align: middle;
        }
    </style>
    <?php
}
