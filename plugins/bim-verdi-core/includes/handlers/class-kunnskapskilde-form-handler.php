<?php
/**
 * Kunnskapskilde Form Handler
 *
 * Handles "Registrer kunnskapskilde" Gravity Forms submissions
 * Creates Kunnskapskilde posts with URL duplicate validation
 *
 * Uses dynamic Form ID from BIM_Verdi_Kunnskapskilde_Form_Setup
 *
 * @package BIM_Verdi_Core
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Kunnskapskilde_Form_Handler {

    private $form_id = null;
    private $field_map = array();

    /**
     * Initialize the handler
     */
    public function __construct() {
        // Defer hook registration until plugins_loaded to ensure GFAPI is available
        add_action('plugins_loaded', array($this, 'init_hooks'), 20);
    }

    /**
     * Initialize hooks after form is available
     */
    public function init_hooks() {
        // Load setup class if not already loaded
        if (!class_exists('BIM_Verdi_Kunnskapskilde_Form_Setup')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'setup/class-kunnskapskilde-form-setup.php';
        }

        // Get form ID (creates form if needed)
        $this->form_id = BIM_Verdi_Kunnskapskilde_Form_Setup::get_form_id();

        if (!$this->form_id) {
            error_log('BIM Verdi Kunnskapskilde Handler - Could not get/create form');
            return;
        }

        // Load field map
        $this->field_map = get_option('bim_verdi_kunnskapskilde_field_map', array());

        // Register hooks with the dynamic form ID
        add_action('gform_after_submission_' . $this->form_id, array($this, 'handle_submission'), 10, 2);

        // Pre-populate fields using Gravity Forms parameter system
        add_filter('gform_field_value_company_id', array($this, 'populate_company_id'));
        add_filter('gform_field_value_user_id', array($this, 'populate_user_id'));
        add_filter('gform_field_value_kunnskapskilde_id', array($this, 'populate_kunnskapskilde_id'));

        // Pre-render filters to update field properties
        add_filter('gform_pre_render_' . $this->form_id, array($this, 'prepopulate_company_field'));
        add_filter('gform_pre_validation_' . $this->form_id, array($this, 'prepopulate_company_field'));
        add_filter('gform_pre_submission_filter_' . $this->form_id, array($this, 'prepopulate_company_field'));

        // Pre-populate kunnskapskilde data for editing
        add_filter('gform_pre_render_' . $this->form_id, array($this, 'prepopulate_kunnskapskilde_fields'));
        add_filter('gform_pre_validation_' . $this->form_id, array($this, 'prepopulate_kunnskapskilde_fields'));

        // Add URL duplicate validation
        add_filter('gform_validation_' . $this->form_id, array($this, 'validate_unique_url'));

        // Add validation to inject company ID into submission
        add_filter('gform_validation_' . $this->form_id, array($this, 'inject_company_id'));

        // Customize submit button text based on edit/create mode
        add_filter('gform_submit_button_' . $this->form_id, array($this, 'customize_submit_button'), 10, 2);
    }

    /**
     * Get the form ID for use in templates
     */
    public function get_form_id() {
        if (!$this->form_id && class_exists('BIM_Verdi_Kunnskapskilde_Form_Setup')) {
            $this->form_id = BIM_Verdi_Kunnskapskilde_Form_Setup::get_form_id();
        }
        return $this->form_id;
    }

    /**
     * Get field ID by input name
     */
    private function get_field_id($input_name) {
        return isset($this->field_map[$input_name]) ? (int) $this->field_map[$input_name] : null;
    }

    /**
     * Populate company_id parameter with user's company
     */
    public function populate_company_id() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '';
        }
        return get_user_meta($user_id, 'bim_verdi_company_id', true);
    }

    /**
     * Populate user_id parameter with current user ID
     */
    public function populate_user_id() {
        return get_current_user_id();
    }

    /**
     * Populate kunnskapskilde_id parameter from URL
     */
    public function populate_kunnskapskilde_id() {
        return isset($_GET['kunnskapskilde_id']) ? intval($_GET['kunnskapskilde_id']) : 0;
    }

    /**
     * Pre-populate kunnskapskilde fields when editing existing entry
     */
    public function prepopulate_kunnskapskilde_fields($form) {
        $kunnskapskilde_id = isset($_GET['kunnskapskilde_id']) ? intval($_GET['kunnskapskilde_id']) : 0;

        if (!$kunnskapskilde_id) {
            return $form;
        }

        $kunnskapskilde = get_post($kunnskapskilde_id);
        if (!$kunnskapskilde || $kunnskapskilde->post_type !== 'kunnskapskilde') {
            return $form;
        }

        // Get ACF fields
        $acf_data = array(
            'kunnskapskilde_navn' => get_field('kunnskapskilde_navn', $kunnskapskilde_id),
            'kort_beskrivelse' => get_field('kort_beskrivelse', $kunnskapskilde_id),
            'detaljert_beskrivelse' => get_field('detaljert_beskrivelse', $kunnskapskilde_id),
            'ekstern_lenke' => get_field('ekstern_lenke', $kunnskapskilde_id),
            'sharepoint_lenke' => get_field('sharepoint_lenke', $kunnskapskilde_id),
            'utgiver' => get_field('utgiver', $kunnskapskilde_id),
            'spraak' => get_field('spraak', $kunnskapskilde_id),
            'versjon' => get_field('versjon', $kunnskapskilde_id),
            'utgivelsesaar' => get_field('utgivelsesaar', $kunnskapskilde_id),
            'kildetype' => get_field('kildetype', $kunnskapskilde_id),
        );

        // Get taxonomy terms
        $temagruppe_terms = wp_get_post_terms($kunnskapskilde_id, 'temagruppe');
        $kategori_terms = wp_get_post_terms($kunnskapskilde_id, 'kunnskapskildekategori');

        foreach ($form['fields'] as &$field) {
            $input_name = isset($field->inputName) ? $field->inputName : '';

            switch ($input_name) {
                case 'kunnskapskilde_navn':
                    if (!empty($acf_data['kunnskapskilde_navn'])) {
                        $field->defaultValue = $acf_data['kunnskapskilde_navn'];
                    }
                    break;

                case 'kort_beskrivelse':
                    if (!empty($acf_data['kort_beskrivelse'])) {
                        $field->defaultValue = $acf_data['kort_beskrivelse'];
                    }
                    break;

                case 'detaljert_beskrivelse':
                    if (!empty($acf_data['detaljert_beskrivelse'])) {
                        $field->defaultValue = $acf_data['detaljert_beskrivelse'];
                    }
                    break;

                case 'ekstern_lenke':
                    if (!empty($acf_data['ekstern_lenke'])) {
                        $field->defaultValue = $acf_data['ekstern_lenke'];
                    }
                    break;

                case 'sharepoint_lenke':
                    if (!empty($acf_data['sharepoint_lenke'])) {
                        $field->defaultValue = $acf_data['sharepoint_lenke'];
                    }
                    break;

                case 'utgiver':
                    if (!empty($acf_data['utgiver'])) {
                        $field->defaultValue = $acf_data['utgiver'];
                    }
                    break;

                case 'spraak':
                    if (!empty($acf_data['spraak']) && !empty($field->choices)) {
                        foreach ($field->choices as &$choice) {
                            $choice['isSelected'] = ($choice['value'] === $acf_data['spraak']);
                        }
                    }
                    break;

                case 'versjon':
                    if (!empty($acf_data['versjon'])) {
                        $field->defaultValue = $acf_data['versjon'];
                    }
                    break;

                case 'utgivelsesaar':
                    if (!empty($acf_data['utgivelsesaar'])) {
                        $field->defaultValue = $acf_data['utgivelsesaar'];
                    }
                    break;

                case 'kildetype':
                    if (!empty($acf_data['kildetype']) && !empty($field->choices)) {
                        foreach ($field->choices as &$choice) {
                            $choice['isSelected'] = ($choice['value'] === $acf_data['kildetype']);
                        }
                    }
                    break;

                case 'temagrupper':
                    if (!empty($temagruppe_terms) && !empty($field->choices)) {
                        $temagruppe_slugs = wp_list_pluck($temagruppe_terms, 'slug');
                        foreach ($field->choices as &$choice) {
                            $choice['isSelected'] = in_array($choice['value'], $temagruppe_slugs);
                        }
                    }
                    break;

                case 'kategorier':
                    if (!empty($kategori_terms) && !empty($field->choices)) {
                        $kategori_slugs = wp_list_pluck($kategori_terms, 'slug');
                        foreach ($field->choices as &$choice) {
                            $choice['isSelected'] = in_array($choice['value'], $kategori_slugs);
                        }
                    }
                    break;
            }
        }

        return $form;
    }

    /**
     * Pre-populate company field with user's company ID
     */
    public function prepopulate_company_field($form) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return $form;
        }

        $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
        if (!$company_id) {
            return $form;
        }

        foreach ($form['fields'] as &$field) {
            $input_name = isset($field->inputName) ? $field->inputName : '';

            if ($input_name === 'company_id' ||
                stripos($field->label, 'Foretak') !== false ||
                stripos($field->label, 'Bedrift') !== false) {
                $field->defaultValue = $company_id;
                $field->isRequired = false;
            }

            if ($input_name === 'user_id' ||
                stripos($field->label, 'Bruker-ID') !== false ||
                stripos($field->label, 'Bruker ID') !== false) {
                $field->defaultValue = $user_id;
                $field->isRequired = false;
            }
        }

        return $form;
    }

    /**
     * Validate that the URL is unique (not already registered)
     */
    public function validate_unique_url($validation_result) {
        $form = $validation_result['form'];

        // Get the current kunnskapskilde ID (if editing)
        $current_id = isset($_GET['kunnskapskilde_id']) ? intval($_GET['kunnskapskilde_id']) : 0;

        // Find the URL field by inputName
        foreach ($form['fields'] as &$field) {
            $input_name = isset($field->inputName) ? $field->inputName : '';

            if ($input_name === 'ekstern_lenke') {
                $url = rgpost('input_' . $field->id);

                if (!empty($url)) {
                    // Normalize URL for comparison
                    $normalized_url = trailingslashit(strtolower(trim($url)));

                    // Check if URL already exists in another kunnskapskilde
                    $existing = get_posts(array(
                        'post_type' => 'kunnskapskilde',
                        'post_status' => array('publish', 'draft', 'pending'),
                        'meta_query' => array(
                            array(
                                'key' => 'ekstern_lenke',
                                'value' => $url,
                                'compare' => '='
                            )
                        ),
                        'posts_per_page' => 1,
                        'fields' => 'ids',
                        'post__not_in' => $current_id ? array($current_id) : array()
                    ));

                    // Also check with trailing slash variation
                    if (empty($existing)) {
                        $url_with_slash = trailingslashit($url);
                        $url_without_slash = untrailingslashit($url);

                        $existing = get_posts(array(
                            'post_type' => 'kunnskapskilde',
                            'post_status' => array('publish', 'draft', 'pending'),
                            'meta_query' => array(
                                'relation' => 'OR',
                                array(
                                    'key' => 'ekstern_lenke',
                                    'value' => $url_with_slash,
                                    'compare' => '='
                                ),
                                array(
                                    'key' => 'ekstern_lenke',
                                    'value' => $url_without_slash,
                                    'compare' => '='
                                )
                            ),
                            'posts_per_page' => 1,
                            'fields' => 'ids',
                            'post__not_in' => $current_id ? array($current_id) : array()
                        ));
                    }

                    if (!empty($existing)) {
                        $existing_post = get_post($existing[0]);
                        $existing_name = $existing_post ? $existing_post->post_title : 'ukjent';

                        $field->failed_validation = true;
                        $field->validation_message = sprintf(
                            'Denne lenken er allerede registrert som "%s". Vennligst bruk en annen URL eller kontakt administrator hvis du mener dette er feil.',
                            esc_html($existing_name)
                        );
                        $validation_result['is_valid'] = false;

                        error_log("BIM Verdi Kunnskapskilde - URL duplicate detected: {$url} (existing post: " . $existing[0] . ")");
                    }
                }
                break;
            }
        }

        $validation_result['form'] = $form;
        return $validation_result;
    }

    /**
     * Inject company ID into form submission
     */
    public function inject_company_id($validation_result) {
        $form = $validation_result['form'];
        $user_id = get_current_user_id();

        if (!$user_id) {
            return $validation_result;
        }

        $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

        // Find company field and inject value
        foreach ($form['fields'] as $field) {
            $input_name = isset($field->inputName) ? $field->inputName : '';

            if ($input_name === 'company_id' ||
                stripos($field->label, 'Foretak') !== false) {
                $_POST["input_{$field->id}"] = $company_id;
                break;
            }
        }

        $validation_result['form'] = $form;
        return $validation_result;
    }

    /**
     * Customize submit button text based on edit/create mode
     */
    public function customize_submit_button($button, $form) {
        $kunnskapskilde_id = isset($_GET['kunnskapskilde_id']) ? intval($_GET['kunnskapskilde_id']) : 0;

        if ($kunnskapskilde_id) {
            // EDIT MODE
            $button = '<button type="submit" class="gform_button button btn btn-hjem-primary btn-lg" id="gform_submit_button_' . $form['id'] . '" style="display: inline-flex; align-items: center; justify-content: center;">';
            $button .= '<svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $button .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>';
            $button .= '</svg>';
            $button .= '<span>Oppdater kunnskapskilde</span>';
            $button .= '</button>';
        } else {
            // CREATE MODE
            $button = '<button type="submit" class="gform_button button btn btn-hjem-primary btn-lg" id="gform_submit_button_' . $form['id'] . '" style="display: inline-flex; align-items: center; justify-content: center;">';
            $button .= '<svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $button .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>';
            $button .= '</svg>';
            $button .= '<span>Registrer kunnskapskilde</span>';
            $button .= '</button>';
        }

        return $button;
    }

    /**
     * Handle kunnskapskilde registration form submission
     */
    public function handle_submission($entry, $form) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            error_log('Kunnskapskilde registration: User not logged in');
            return;
        }

        // Get user's company (optional for kunnskapskilder)
        $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

        // Extract form data using inputName mapping
        $data = $this->extract_form_data($entry, $form);

        // Check if we're editing
        $kunnskapskilde_id = !empty($data['kunnskapskilde_id']) ? intval($data['kunnskapskilde_id']) : 0;

        if ($kunnskapskilde_id) {
            // EDIT MODE
            $this->update_kunnskapskilde($kunnskapskilde_id, $data, $user_id, $company_id);
        } else {
            // CREATE MODE
            $this->create_kunnskapskilde($data, $user_id, $company_id);
        }
    }

    /**
     * Extract data from form entry using inputName mapping
     */
    private function extract_form_data($entry, $form) {
        $data = array();

        // Build field ID to inputName map from form
        $field_map = array();
        foreach ($form['fields'] as $field) {
            if (!empty($field->inputName)) {
                $field_map[$field->id] = $field->inputName;
            }
        }

        // Extract data by inputName
        foreach ($form['fields'] as $field) {
            $input_name = isset($field->inputName) ? $field->inputName : '';

            if ($field->type === 'checkbox') {
                // Handle checkbox fields (multiple values)
                $values = array();
                foreach ($entry as $key => $value) {
                    if (strpos($key, $field->id . '.') === 0 && !empty($value)) {
                        $values[] = $value;
                    }
                }
                $data[$input_name] = $values;
            } else {
                // Regular fields
                $data[$input_name] = rgar($entry, $field->id);
            }
        }

        return $data;
    }

    /**
     * Create new kunnskapskilde
     */
    private function create_kunnskapskilde($data, $user_id, $company_id) {
        $navn = isset($data['kunnskapskilde_navn']) ? $data['kunnskapskilde_navn'] : '';

        if (empty($navn)) {
            error_log('BIM Verdi Kunnskapskilde - Cannot create post without name');
            return;
        }

        $post_id = wp_insert_post(array(
            'post_type' => 'kunnskapskilde',
            'post_title' => sanitize_text_field($navn),
            'post_content' => '',
            'post_status' => 'draft', // Requires admin approval
            'post_author' => $user_id,
        ));

        if (is_wp_error($post_id)) {
            error_log('BIM Verdi Kunnskapskilde - Failed to create post: ' . $post_id->get_error_message());
            return;
        }

        $this->save_acf_fields($post_id, $data, $user_id, $company_id);
        $this->save_taxonomies($post_id, $data);

        error_log("BIM Verdi Kunnskapskilde - Created post {$post_id} by user {$user_id}");
    }

    /**
     * Update existing kunnskapskilde
     */
    private function update_kunnskapskilde($post_id, $data, $user_id, $company_id) {
        $existing = get_post($post_id);
        if (!$existing || $existing->post_type !== 'kunnskapskilde') {
            error_log("BIM Verdi Kunnskapskilde - Invalid post ID for update: {$post_id}");
            return;
        }

        // Check permissions
        $can_edit = false;
        if ($existing->post_author == $user_id) {
            $can_edit = true;
        }

        $registrert_av = get_field('registrert_av', $post_id);
        if ($registrert_av && $registrert_av == $user_id) {
            $can_edit = true;
        }

        if (current_user_can('manage_options')) {
            $can_edit = true;
        }

        if (!$can_edit) {
            error_log("BIM Verdi Kunnskapskilde - User {$user_id} cannot edit post {$post_id}");
            return;
        }

        $navn = isset($data['kunnskapskilde_navn']) ? $data['kunnskapskilde_navn'] : $existing->post_title;

        wp_update_post(array(
            'ID' => $post_id,
            'post_title' => sanitize_text_field($navn),
            'post_status' => $existing->post_status, // Preserve status
        ));

        $this->save_acf_fields($post_id, $data, $user_id, $company_id);
        $this->save_taxonomies($post_id, $data);

        error_log("BIM Verdi Kunnskapskilde - Updated post {$post_id} by user {$user_id}");
    }

    /**
     * Save ACF fields
     */
    private function save_acf_fields($post_id, $data, $user_id, $company_id) {
        if (!function_exists('update_field')) {
            error_log("BIM Verdi Kunnskapskilde - ACF not available!");
            return;
        }

        // Map inputName to ACF field name (they match in our setup)
        $field_mapping = array(
            'kunnskapskilde_navn' => 'kunnskapskilde_navn',
            'kort_beskrivelse' => 'kort_beskrivelse',
            'detaljert_beskrivelse' => 'detaljert_beskrivelse',
            'ekstern_lenke' => 'ekstern_lenke',
            'sharepoint_lenke' => 'sharepoint_lenke',
            'utgiver' => 'utgiver',
            'spraak' => 'spraak',
            'versjon' => 'versjon',
            'utgivelsesaar' => 'utgivelsesaar',
            'kildetype' => 'kildetype',
        );

        foreach ($field_mapping as $input_name => $acf_field) {
            if (isset($data[$input_name]) && !empty($data[$input_name])) {
                $value = $data[$input_name];

                // Apply appropriate sanitization
                switch ($input_name) {
                    case 'kort_beskrivelse':
                        $value = sanitize_textarea_field($value);
                        break;
                    case 'detaljert_beskrivelse':
                        $value = wp_kses_post($value);
                        break;
                    case 'ekstern_lenke':
                    case 'sharepoint_lenke':
                        $value = esc_url_raw($value);
                        break;
                    case 'utgivelsesaar':
                        $value = intval($value);
                        break;
                    default:
                        $value = sanitize_text_field($value);
                }

                update_field($acf_field, $value, $post_id);
            }
        }

        // Always save registrert_av
        update_field('registrert_av', $user_id, $post_id);

        // Save company if available
        if (!empty($company_id)) {
            update_field('tilknyttet_bedrift', intval($company_id), $post_id);
        }
    }

    /**
     * Save taxonomy terms
     */
    private function save_taxonomies($post_id, $data) {
        // Save kategorier
        if (!empty($data['kategorier']) && is_array($data['kategorier'])) {
            $clean_kategorier = array_map('sanitize_text_field', $data['kategorier']);
            wp_set_object_terms($post_id, $clean_kategorier, 'kunnskapskildekategori');
        }

        // Save temagrupper
        if (!empty($data['temagrupper']) && is_array($data['temagrupper'])) {
            $clean_temagrupper = array_map('sanitize_text_field', $data['temagrupper']);
            wp_set_object_terms($post_id, $clean_temagrupper, 'temagruppe');
        }
    }
}

/**
 * Helper function to get kunnskapskilde form ID
 */
function bim_verdi_get_kunnskapskilde_form_id() {
    if (!class_exists('BIM_Verdi_Kunnskapskilde_Form_Setup')) {
        require_once plugin_dir_path(__FILE__) . '../setup/class-kunnskapskilde-form-setup.php';
    }
    return BIM_Verdi_Kunnskapskilde_Form_Setup::get_form_id();
}
