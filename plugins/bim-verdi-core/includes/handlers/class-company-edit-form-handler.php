<?php
/**
 * Company Edit Form Handler
 * 
 * Handles company profile editing Gravity Forms submissions (Form ID 7).
 * Updates existing Foretak post with submitted profile data.
 *
 * @package BIM_Verdi_Core
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Company_Edit_Form_Handler {
    
    const FORM_ID = 7; // Company edit form - [System] - Redigering av foretak
    
    /**
     * Initialize the handler
     */
    public function __construct() {
        // Handle form submission
        add_action('gform_after_submission_' . self::FORM_ID, array($this, 'handle_submission'), 10, 2);
        
        // Populate form fields with company data before rendering
        add_filter('gform_pre_render_' . self::FORM_ID, array($this, 'populate_form_with_company_data'));
        
        // Also populate for admin and conditional logic
        add_filter('gform_pre_validation_' . self::FORM_ID, array($this, 'populate_form_with_company_data'));
        add_filter('gform_admin_pre_render_' . self::FORM_ID, array($this, 'populate_form_with_company_data'));
        
        // Custom confirmation after successful update
        add_filter('gform_confirmation_' . self::FORM_ID, array($this, 'custom_confirmation'), 10, 4);
    }
    
    /**
     * Populate form fields with current company data
     * 
     * This filter runs before the form is rendered and sets default values
     * for all form fields based on the user's company profile data.
     * 
     * @param array $form The form object
     * @return array Modified form object
     */
    public function populate_form_with_company_data($form) {
        
        // Only populate if user is logged in
        if (!is_user_logged_in()) {
            return $form;
        }
        
        $current_user_id = get_current_user_id();
        $company_id = $this->get_user_company_id($current_user_id);
        
        if (!$company_id) {
            error_log('BIM Verdi Company Edit - No company found for user ' . $current_user_id);
            return $form;
        }
        
        // Get current company data
        $company_data = $this->get_company_data($company_id);
        
        if (empty($company_data)) {
            return $form;
        }
        
        // Field mapping: Field ID => Company data key
        // These IDs match the form structure created in create-company-edit-form.php
        $field_mapping = array(
            1  => 'organisasjonsnummer',   // Text (read-only display)
            2  => 'bedriftsnavn',           // Text
            3  => 'beskrivelse',            // Textarea
            // 4 = Logo (file upload - not prepopulated)
            5  => 'telefon',                // Phone
            6  => 'nettside',               // Website
            7  => 'adresse',                // Text
            8  => 'postnummer',             // Text
            9  => 'poststed',               // Text
            10 => 'company_id',             // Hidden field for company ID
        );
        
        // Set default values for each field
        foreach ($form['fields'] as &$field) {
            $field_id = $field->id;
            
            if (isset($field_mapping[$field_id]) && isset($company_data[$field_mapping[$field_id]])) {
                $field->defaultValue = $company_data[$field_mapping[$field_id]];
                error_log('BIM Verdi Company Edit - Set field ' . $field_id . ' (' . $field_mapping[$field_id] . ') to: ' . substr($company_data[$field_mapping[$field_id]], 0, 50));
            }
            
            // Special handling for hidden company_id field
            if ($field_id == 10) {
                $field->defaultValue = $company_id;
            }
        }
        
        error_log('BIM Verdi Company Edit - Form pre-render complete for company ' . $company_id);
        
        return $form;
    }
    
    /**
     * Handle company edit form submission
     * 
     * Updates the company profile data with submitted form values.
     * 
     * @param array $entry The submitted form entry
     * @param array $form The form object
     */
    public function handle_submission($entry, $form) {
        
        try {
            // Get company ID from hidden field
            $company_id = intval(rgar($entry, '10'));
            
            // Fallback: get company from current user
            if (!$company_id) {
                $current_user_id = get_current_user_id();
                $company_id = $this->get_user_company_id($current_user_id);
            }
            
            // Verify company exists and user has permission
            if (!$company_id || !$this->user_can_edit_company($company_id)) {
                throw new Exception('User does not have permission to edit this company');
            }
            
            // Extract form data
            $company_data = $this->extract_company_data($entry);
            
            // Update company
            $this->update_company($company_id, $company_data);
            
            // Handle logo upload if provided
            $logo_url = rgar($entry, '4');
            if (!empty($logo_url)) {
                $this->handle_logo_upload($logo_url, $company_id);
            }
            
            // Log successful update
            error_log("BIM Verdi Company Edit Handler: Company updated - Company ID: {$company_id}");
            gform_update_meta($entry['id'], 'company_updated_id', $company_id);
            
        } catch (Exception $e) {
            error_log('BIM Verdi Company Edit Handler Exception: ' . $e->getMessage());
            gform_update_meta($entry['id'], 'company_update_error', $e->getMessage());
        }
    }
    
    /**
     * Custom confirmation message
     * 
     * @param string $confirmation
     * @param array $form
     * @param array $entry
     * @param bool $ajax
     * @return string
     */
    public function custom_confirmation($confirmation, $form, $entry, $ajax) {
        
        $company_id = gform_get_meta($entry['id'], 'company_updated_id');
        $error = gform_get_meta($entry['id'], 'company_update_error');
        
        if ($error) {
            $confirmation = '<div class="gform_confirmation_message alert alert-danger bg-red-50 border border-red-200 text-red-800 p-6 rounded-lg">';
            $confirmation .= '<h3 class="text-xl font-bold mb-2">Feil ved oppdatering</h3>';
            $confirmation .= '<p>' . esc_html($error) . '</p>';
            $confirmation .= '</div>';
            return $confirmation;
        }
        
        $confirmation = '<div class="gform_confirmation_message alert alert-success bg-green-50 border border-green-200 text-green-800 p-6 rounded-lg">';
        $confirmation .= '<h3 class="text-xl font-bold mb-2">✅ Foretaksinformasjon oppdatert!</h3>';
        $confirmation .= '<p class="mb-4">Endringene dine er lagret.</p>';
        $confirmation .= '<p><a href="' . home_url('/min-side/foretak/') . '" class="wa-button bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600">← Tilbake til foretak</a></p>';
        $confirmation .= '</div>';
        
        return $confirmation;
    }
    
    /**
     * Get user's company ID
     * 
     * @param int $user_id
     * @return int|null
     */
    private function get_user_company_id($user_id) {
        // First check ACF field
        if (function_exists('get_field')) {
            $company_id = get_field('tilknyttet_foretak', 'user_' . $user_id);
            if ($company_id) {
                return is_object($company_id) ? $company_id->ID : intval($company_id);
            }
        }
        
        // Fallback to user meta
        $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
        return $company_id ? intval($company_id) : null;
    }
    
    /**
     * Get company data for populating the form
     * 
     * @param int $company_id
     * @return array
     */
    private function get_company_data($company_id) {
        $data = array();
        
        if (!function_exists('get_field')) {
            return $data;
        }
        
        $data['organisasjonsnummer'] = get_field('organisasjonsnummer', $company_id) ?: '';
        $data['bedriftsnavn'] = get_the_title($company_id) ?: '';
        $data['beskrivelse'] = get_field('beskrivelse', $company_id) ?: '';
        $data['telefon'] = get_field('telefon', $company_id) ?: '';
        $data['nettside'] = get_field('nettside', $company_id) ?: '';
        $data['adresse'] = get_field('adresse', $company_id) ?: '';
        $data['postnummer'] = get_field('postnummer', $company_id) ?: '';
        $data['poststed'] = get_field('poststed', $company_id) ?: '';
        $data['company_id'] = $company_id;
        
        return $data;
    }
    
    /**
     * Check if user can edit company
     * 
     * @param int $company_id
     * @return bool
     */
    private function user_can_edit_company($company_id) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $current_user_id = get_current_user_id();
        
        // Admins can edit any company
        if (current_user_can('administrator')) {
            return true;
        }
        
        // Check if user is hovedkontaktperson
        if (function_exists('get_field')) {
            $hovedkontakt = get_field('hovedkontaktperson', $company_id);
            $hovedkontakt_id = is_object($hovedkontakt) ? $hovedkontakt->ID : intval($hovedkontakt);
            
            if ($hovedkontakt_id == $current_user_id) {
                return true;
            }
        }
        
        // Check if user is linked to this company
        $user_company_id = $this->get_user_company_id($current_user_id);
        if ($user_company_id == $company_id) {
            // Check if user has edit role
            $foretak_rolle = get_field('foretak_rolle', 'user_' . $current_user_id);
            if (in_array($foretak_rolle, array('eier', 'admin', 'hovedkontakt'))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Extract company data from form entry
     * 
     * @param array $entry
     * @return array
     */
    private function extract_company_data($entry) {
        $data = array(
            // Field 1 (org.nr) is read-only, don't extract
            'bedriftsnavn' => sanitize_text_field(rgar($entry, '2')),
            'beskrivelse' => wp_kses_post(rgar($entry, '3')),
            // Field 4 is logo (handled separately)
            'telefon' => sanitize_text_field(rgar($entry, '5')),
            'nettside' => esc_url_raw(rgar($entry, '6')),
            'adresse' => sanitize_text_field(rgar($entry, '7')),
            'postnummer' => sanitize_text_field(rgar($entry, '8')),
            'poststed' => sanitize_text_field(rgar($entry, '9')),
        );
        
        error_log('BIM Verdi Company Edit Handler - Extracted data: ' . json_encode($data));
        
        return $data;
    }
    
    /**
     * Update company with submitted data
     * 
     * @param int $company_id
     * @param array $company_data
     */
    private function update_company($company_id, $company_data) {
        
        // Update post title (bedriftsnavn)
        if (!empty($company_data['bedriftsnavn'])) {
            wp_update_post(array(
                'ID' => $company_id,
                'post_title' => $company_data['bedriftsnavn'],
            ));
        }
        
        // Update ACF fields
        if (function_exists('update_field')) {
            
            if (!empty($company_data['bedriftsnavn'])) {
                update_field('bedriftsnavn', $company_data['bedriftsnavn'], $company_id);
            }
            
            // Allow empty values for optional fields
            update_field('beskrivelse', $company_data['beskrivelse'], $company_id);
            update_field('telefon', $company_data['telefon'], $company_id);
            update_field('nettside', $company_data['nettside'], $company_id);
            update_field('adresse', $company_data['adresse'], $company_id);
            update_field('postnummer', $company_data['postnummer'], $company_id);
            update_field('poststed', $company_data['poststed'], $company_id);
            
            error_log('BIM Verdi Company Edit Handler - Updated ACF fields for company ' . $company_id);
        }
    }
    
    /**
     * Handle logo upload
     * 
     * @param string $logo_url
     * @param int $company_id
     * @return int|false
     */
    private function handle_logo_upload($logo_url, $company_id) {
        
        if (empty($logo_url)) {
            return false;
        }
        
        // Find attachment by URL
        $attachment_id = attachment_url_to_postid($logo_url);
        
        if ($attachment_id && function_exists('update_field')) {
            update_field('logo', $attachment_id, $company_id);
            error_log('BIM Verdi Company Edit Handler - Updated logo for company ' . $company_id);
            return $attachment_id;
        }
        
        return false;
    }
}
