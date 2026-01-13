<?php
/**
 * User Profile Edit Form Handler
 * 
 * Handles user profile editing Gravity Forms submissions (Form ID 4).
 * Updates WordPress user meta and ACF fields with submitted profile data.
 *
 * @package BIM_Verdi_Core
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Profile_Form_Handler {
    
    const FORM_ID = 4; // User profile edit form
    
    /**
     * Initialize the handler
     */
    public function __construct() {
        add_action('gform_after_submission_' . self::FORM_ID, array($this, 'handle_submission'), 10, 2);
        // Populate form fields with user data before rendering
        add_filter('gform_pre_render_' . self::FORM_ID, array($this, 'populate_form_with_user_data'));
    }
    
    /**
     * Populate form fields with current user data
     * 
     * This filter runs before the form is rendered and sets default values
     * for all form fields based on the current user's profile data.
     * 
     * @param array $form The form object
     * @return array Modified form object
     */
    public function populate_form_with_user_data($form) {
        
        // Only populate if user is logged in
        if (!is_user_logged_in()) {
            return $form;
        }
        
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        
        // Get current user profile data
        $profile = bim_get_user_profile($user_id);
        
        // Field mapping: Field ID => Profile data key
        $field_mapping = array(
            1 => 'first_name',
            2 => 'last_name',
            3 => 'email',
            4 => 'phone',
            5 => 'job_title',
            6 => 'linkedin_url',
        );
        
        // Set default values for each field
        foreach ($form['fields'] as &$field) {
            $field_id = $field->id;
            
            if (isset($field_mapping[$field_id]) && isset($profile[$field_mapping[$field_id]])) {
                $field->defaultValue = $profile[$field_mapping[$field_id]];
                error_log('BIM Verdi Profile - Set field ' . $field_id . ' (' . $field_mapping[$field_id] . ') to: ' . $profile[$field_mapping[$field_id]]);
            }
        }
        
        error_log('BIM Verdi Profile - Form pre-render complete for user ' . $user_id);
        
        return $form;
    }
    
    /**
     * Handle user profile form submission
     * 
     * Updates the logged-in user's profile data with submitted form values.
     * - Standard WordPress user fields (first_name, last_name, user_email)
     * - ACF profile fields (phone, job_title, linkedin_url)
     * 
     * @param array $entry The submitted form entry
     * @param array $form The form object
     */
    public function handle_submission($entry, $form) {
        
        try {
            // Get the currently logged-in user
            $current_user = wp_get_current_user();
            $user_id = $current_user->ID;
            
            // Verify user is logged in
            if (!$user_id || $user_id === 0) {
                throw new Exception('User is not logged in');
            }
            
            // Extract form data
            $profile_data = $this->extract_profile_data($entry);
            
            // Update user
            $this->update_user($user_id, $profile_data);
            
            // Log successful update
            error_log("BIM Verdi Profile Handler: Profile updated - User ID: {$user_id}");
            gform_update_meta($entry['id'], 'profile_updated_user_id', $user_id);
            
        } catch (Exception $e) {
            error_log('BIM Verdi Profile Handler Exception: ' . $e->getMessage());
            gform_update_meta($entry['id'], 'profile_update_error', $e->getMessage());
        }
    }
    
    /**
     * Extract profile data from form entry
     * 
     * Maps form fields to profile data array
     * Field IDs must match your Gravity Forms form structure
     * 
     * @param array $entry
     * @return array
     */
    private function extract_profile_data($entry) {
        $data = array(
            'first_name' => sanitize_text_field(rgar($entry, '1')),  // Field 1: First Name
            'last_name' => sanitize_text_field(rgar($entry, '2')),   // Field 2: Last Name
            'email' => sanitize_email(rgar($entry, '3')),            // Field 3: Email
            'phone' => sanitize_text_field(rgar($entry, '4')),       // Field 4: Phone
            'job_title' => sanitize_text_field(rgar($entry, '5')),   // Field 5: Job Title
            'linkedin_url' => esc_url_raw(rgar($entry, '6')),        // Field 6: LinkedIn URL
        );
        
        // Debug logging
        error_log('BIM Verdi Profile Handler - Extracted data: ' . json_encode($data));
        
        return $data;
    }
    
    /**
     * Update user profile with submitted data
     * 
     * Updates both standard WP user fields and ACF fields.
     * ACF is the single source of truth for profile meta fields.
     * 
     * @param int $user_id The user to update
     * @param array $profile_data The profile data to save
     */
    private function update_user($user_id, $profile_data) {
        
        // Update standard WordPress user fields
        $user_update = array(
            'ID' => $user_id,
        );
        
        if (!empty($profile_data['first_name'])) {
            $user_update['first_name'] = $profile_data['first_name'];
        }
        
        if (!empty($profile_data['last_name'])) {
            $user_update['last_name'] = $profile_data['last_name'];
        }
        
        // Note: Email update is typically not allowed in profile edit for security reasons
        // If you want to allow email changes, add additional verification:
        // if (!empty($profile_data['email']) && email_exists($profile_data['email'])) {
        //     throw new Exception('Email already exists');
        // }
        // $user_update['user_email'] = $profile_data['email'];
        
        $result = wp_update_user($user_update);
        
        if (is_wp_error($result)) {
            throw new Exception('Failed to update user: ' . $result->get_error_message());
        }
        
        error_log('BIM Verdi Profile Handler - Updated WP user: ' . json_encode($user_update));
        
        // Update ACF fields (single source of truth for profile data)
        if (function_exists('update_field')) {
            
            // Phone number
            if (!empty($profile_data['phone'])) {
                $phone_result = update_field('phone', $profile_data['phone'], 'user_' . $user_id);
                error_log('BIM Verdi Profile Handler - Updated phone: ' . $profile_data['phone'] . ' (Result: ' . json_encode($phone_result) . ')');
            }
            
            // Job title
            if (!empty($profile_data['job_title'])) {
                $title_result = update_field('job_title', $profile_data['job_title'], 'user_' . $user_id);
                error_log('BIM Verdi Profile Handler - Updated job_title: ' . $profile_data['job_title'] . ' (Result: ' . json_encode($title_result) . ')');
            }
            
            // LinkedIn URL
            if (!empty($profile_data['linkedin_url'])) {
                $linkedin_result = update_field('linkedin_url', $profile_data['linkedin_url'], 'user_' . $user_id);
                error_log('BIM Verdi Profile Handler - Updated linkedin_url: ' . $profile_data['linkedin_url'] . ' (Result: ' . json_encode($linkedin_result) . ')');
            }
        }
    }
}

// Instantiate the handler
new BIM_Verdi_Profile_Form_Handler();
