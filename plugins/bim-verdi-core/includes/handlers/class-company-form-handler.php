<?php
/**
 * Company Form Handler
 * 
 * Handles company registration Gravity Forms submissions
 /**
 * Creates WordPress user and Foretak post
 *
 * Processes form submission from Gravity Forms (company registration form)
 * Creates a new WordPress user account linked to a new Foretak post
 * 
 * @package BIM_Verdi_Core
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Company_Form_Handler {
    
    const FORM_ID = 2; // Company registration form
    
    /**
     * Initialize the handler
     */
    public function __construct() {
        add_action('gform_after_submission_' . self::FORM_ID, array($this, 'handle_submission'), 10, 2);
        add_filter('gform_validation_' . self::FORM_ID, array($this, 'validate_submission'));
        add_filter('gform_confirmation_' . self::FORM_ID, array($this, 'custom_confirmation'), 10, 4);
    }
    
    /**
     * Handle company registration form submission
     * 
     * Creates:
     * 1. Foretak post (company CPT)
     * 2. Links current logged-in user to company (if logged in)
     * 
     * NOTE: This form NO LONGER creates users. It only creates the Foretak CPT.
     * User must be logged in to submit this form.
     * 
     * @param array $entry The submitted form entry
     * @param array $form The form object
     */
    public function handle_submission($entry, $form) {
        
        // Get current logged-in user (if any)
        $current_user_id = get_current_user_id();
        
        // Extract form data (only company data now, no user/contact data)
        $company_data = $this->extract_company_data($entry);
        $categories = $this->extract_categories($entry);
        
        // Create company post
        $company_id = $this->create_company_post($company_data, $current_user_id);
        
        if (is_wp_error($company_id)) {
            error_log('BIM Verdi Company Handler: Failed to create company - ' . $company_id->get_error_message());
            gform_update_meta($entry['id'], 'registration_error', $company_id->get_error_message());
            return;
        }
        
        // Save ACF fields
        $this->save_acf_fields($company_id, $company_data, $current_user_id);
        
        // Handle logo upload
        if (!empty($company_data['logo_url'])) {
            $this->handle_logo_upload($company_data['logo_url'], $company_id);
        }
        
        // Link current user to company (if logged in)
        if ($current_user_id) {
            update_user_meta($current_user_id, 'bim_verdi_company_id', $company_id);
            
            // Also update ACF field for user-company relationship
            if (function_exists('update_field')) {
                update_field('tilknyttet_foretak', $company_id, 'user_' . $current_user_id);
                update_field('foretak_rolle', 'eier', 'user_' . $current_user_id);
            }
        }
        
        // Set taxonomies
        $this->set_taxonomies($company_id, $categories);
        
        // Store success data
        gform_update_meta($entry['id'], 'created_company_id', $company_id);
        if ($current_user_id) {
            gform_update_meta($entry['id'], 'linked_user_id', $current_user_id);
        }
        
        error_log("BIM Verdi Company Handler: Company created - Company ID: {$company_id}, Linked to User ID: {$current_user_id}");
    }
    
    /**
     * Validate form submission
     * 
     * @param array $validation_result
     * @return array
     */
    public function validate_submission($validation_result) {
        // Validation logic here - check for duplicates, etc.
        return $validation_result;
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
        
        // Get created company ID from entry meta
        $company_id = gform_get_meta($entry['id'], 'created_company_id');
        $user_id = gform_get_meta($entry['id'], 'linked_user_id');
        
        $confirmation = '<div class="alert alert-success bg-green-50 border border-green-200 text-green-800 p-6 rounded-lg">';
        $confirmation .= '<h3 class="text-xl font-bold mb-2">Foretak registrert!</h3>';
        $confirmation .= '<p class="mb-4">Foretaket er opprettet';
        
        if ($user_id) {
            $confirmation .= ' og koblet til din brukerkonto';
        }
        
        $confirmation .= '.</p>';
        $confirmation .= '<p class="mb-4">Du blir omdirigert til Min Side om få sekunder...</p>';
        $confirmation .= '<p><a href="' . home_url('/min-side/') . '" class="btn btn-primary">Gå til Min Side nå</a></p>';
        $confirmation .= '</div>';
        
        // Add redirect script
        $confirmation .= '<script>setTimeout(function() { window.location.href = "' . home_url('/min-side/?foretak_koblet=1') . '"; }, 3000);</script>';
        
        return $confirmation;
    }
    
    /**
     * Extract company data from form entry
     * 
     * NOTE: Form fields updated - no longer includes contact person details
     * Only company information is extracted
     * 
     * @param array $entry
     * @return array
     */
    private function extract_company_data($entry) {
        return array(
            'org_nummer' => rgar($entry, '1'),
            'bedriftsnavn' => rgar($entry, '2'),
            'beskrivelse' => rgar($entry, '3'),
            'logo_url' => rgar($entry, '4'),
            'adresse' => rgar($entry, '5'),
            'postnummer' => rgar($entry, '6'),
            'poststed' => rgar($entry, '7'),
            'nettside' => rgar($entry, '8'),
            'telefon' => rgar($entry, '9'), // Company phone (not contact person phone)
        );
    }
    

    
    /**
     * Extract category selections from form entry
     * 
     * @param array $entry
     * @return array
     */
    private function extract_categories($entry) {
        return array(
            'bransjekategori' => rgar($entry, '20'),
            'kundetype' => rgar($entry, '21'),
        );
    }
    

    
    /**
     * Create company post
     * 
     * @param array $company_data
     * @param int $user_id
     * @return int|WP_Error
     */
    private function create_company_post($company_data, $user_id) {
        
        return wp_insert_post(array(
            'post_type' => 'foretak',
            'post_title' => $company_data['bedriftsnavn'],
            'post_content' => $company_data['beskrivelse'],
            'post_status' => 'pending',
            'post_author' => $user_id,
        ));
    }
    
    /**
     * Save ACF fields for company post
     * 
     * @param int $post_id
     * @param array $company_data
     * @param int $user_id (optional - current logged-in user)
     */
    private function save_acf_fields($post_id, $company_data, $user_id = null) {
        
        if (!function_exists('update_field')) {
            return;
        }
        
        update_field('organisasjonsnummer', $company_data['org_nummer'], $post_id);
        update_field('bedriftsnavn', $company_data['bedriftsnavn'], $post_id);
        update_field('beskrivelse', $company_data['beskrivelse'], $post_id);
        update_field('adresse', $company_data['adresse'], $post_id);
        update_field('postnummer', $company_data['postnummer'], $post_id);
        update_field('poststed', $company_data['poststed'], $post_id);
        update_field('nettside', $company_data['nettside'], $post_id);
        update_field('telefon', $company_data['telefon'], $post_id);
        
        // Set hovedkontaktperson (primary contact) to current user if logged in
        // This is used by invitation system and sidebar to identify company owner
        if ($user_id) {
            update_field('hovedkontaktperson', $user_id, $post_id);
            update_field('kontaktperson', $user_id, $post_id); // Legacy field
            
            // Also give user company_owner role for permissions
            $user = get_user_by('id', $user_id);
            if ($user && !in_array('administrator', $user->roles)) {
                $user->add_role('company_owner');
            }
        }
        
        // Set default values for invitation system
        update_field('er_aktiv_deltaker', true, $post_id);
        update_field('antall_invitasjoner_tillatt', 5, $post_id);
        
        update_field('medlemsstatus', 'pending', $post_id);
    }
    
    /**
     * Handle logo upload
     * 
     * @param string $logo_url
     * @param int $post_id
     * @return int|false
     */
    private function handle_logo_upload($logo_url, $post_id) {
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $tmp = download_url($logo_url);
        
        if (is_wp_error($tmp)) {
            error_log('BIM Verdi Company Handler: Failed to download logo - ' . $tmp->get_error_message());
            return false;
        }
        
        $file_array = array(
            'name' => basename($logo_url),
            'tmp_name' => $tmp
        );
        
        $attachment_id = media_handle_sideload($file_array, $post_id);
        
        if (file_exists($tmp)) {
            @unlink($tmp);
        }
        
        if (is_wp_error($attachment_id)) {
            error_log('BIM Verdi Company Handler: Failed to create logo attachment - ' . $attachment_id->get_error_message());
            return false;
        }
        
        if (function_exists('update_field')) {
            update_field('logo', $attachment_id, $post_id);
        }
        
        return $attachment_id;
    }
    
    /**
     * Set taxonomies for company post
     * 
     * @param int $post_id
     * @param array $categories
     */
    private function set_taxonomies($post_id, $categories) {
        
        if (!empty($categories['bransjekategori'])) {
            wp_set_object_terms($post_id, sanitize_text_field($categories['bransjekategori']), 'bransjekategori');
        }
        
        if (!empty($categories['kundetype'])) {
            $kundetype_terms = array_map('sanitize_text_field', explode(',', $categories['kundetype']));
            wp_set_object_terms($post_id, $kundetype_terms, 'kundetype');
        }
    }
    

}
