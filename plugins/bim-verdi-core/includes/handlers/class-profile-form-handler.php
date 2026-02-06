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
        
        // Field mapping: Field ID => Profile data key (text fields only)
        $field_mapping = array(
            1 => 'first_name',
            2 => 'last_name',
            3 => 'email',
            4 => 'phone',
            5 => 'job_title',
            6 => 'linkedin_url',
            7 => 'middle_name',
        );

        // Checkbox fields: Field ID => Profile data key
        $checkbox_fields = array(
            9 => 'registration_background',
            10 => 'topic_interests',
        );

        // Set default values for each field
        foreach ($form['fields'] as &$field) {
            $field_id = (int) $field->id;

            // Text/url fields: set defaultValue
            if (isset($field_mapping[$field_id]) && isset($profile[$field_mapping[$field_id]])) {
                $field->defaultValue = $profile[$field_mapping[$field_id]];
            }

            // Checkbox fields: mark matching choices as selected
            if (isset($checkbox_fields[$field_id]) && !empty($profile[$checkbox_fields[$field_id]])) {
                $saved_values = (array) $profile[$checkbox_fields[$field_id]];
                if (!empty($field->choices)) {
                    foreach ($field->choices as &$choice) {
                        $choice['isSelected'] = in_array($choice['value'], $saved_values);
                    }
                }
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
            'first_name' => sanitize_text_field(rgar($entry, '1')),      // Field 1: First Name
            'last_name' => sanitize_text_field(rgar($entry, '2')),       // Field 2: Last Name
            'email' => sanitize_email(rgar($entry, '3')),                // Field 3: Email
            'phone' => sanitize_text_field(rgar($entry, '4')),           // Field 4: Phone
            'job_title' => sanitize_text_field(rgar($entry, '5')),       // Field 5: Job Title
            'linkedin_url' => esc_url_raw(rgar($entry, '6')),            // Field 6: LinkedIn URL
            'middle_name' => sanitize_text_field(rgar($entry, '7')),     // Field 7: Middle Name
            'profile_image_url' => rgar($entry, '8'),                    // Field 8: Profile Image (upload URL)
            'registration_background' => $this->extract_checkbox_values($entry, 9, 6),  // Field 9
            'topic_interests' => $this->extract_checkbox_values($entry, 10, 6),          // Field 10
        );

        error_log('BIM Verdi Profile Handler - Extracted data: ' . json_encode($data));

        return $data;
    }

    /**
     * Extract checkbox values from a Gravity Forms entry
     *
     * Checkbox inputs are stored as field_id.1, field_id.2, etc.
     *
     * @param array $entry
     * @param int $field_id
     * @param int $max_inputs
     * @return array Array of selected values
     */
    private function extract_checkbox_values($entry, $field_id, $max_inputs) {
        $values = array();
        for ($i = 1; $i <= $max_inputs; $i++) {
            $value = rgar($entry, $field_id . '.' . $i);
            if (!empty($value)) {
                $values[] = sanitize_text_field($value);
            }
        }
        return $values;
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
                update_field('phone', $profile_data['phone'], 'user_' . $user_id);
            }

            // Job title
            if (!empty($profile_data['job_title'])) {
                update_field('job_title', $profile_data['job_title'], 'user_' . $user_id);
            }

            // LinkedIn URL
            if (!empty($profile_data['linkedin_url'])) {
                update_field('linkedin_url', $profile_data['linkedin_url'], 'user_' . $user_id);
            }

            // Middle name
            update_field('middle_name', $profile_data['middle_name'] ?? '', 'user_' . $user_id);

            // Profile image (import from GF upload to media library)
            if (!empty($profile_data['profile_image_url'])) {
                $attachment_id = $this->import_profile_image($profile_data['profile_image_url'], $user_id);
                if ($attachment_id) {
                    update_field('profile_image', $attachment_id, 'user_' . $user_id);
                    error_log('BIM Verdi Profile Handler - Updated profile_image: attachment ' . $attachment_id);
                }
            }

            // Registration background (checkbox array)
            if (!empty($profile_data['registration_background'])) {
                update_field('registration_background', $profile_data['registration_background'], 'user_' . $user_id);
            }

            // Topic interests (checkbox array) + sync to legacy meta
            if (!empty($profile_data['topic_interests'])) {
                update_field('topic_interests', $profile_data['topic_interests'], 'user_' . $user_id);
                update_user_meta($user_id, 'bim_verdi_temagrupper', $profile_data['topic_interests']);
            }
        }
    }

    /**
     * Import a Gravity Forms uploaded file into the WordPress media library
     *
     * @param string $file_url URL of the uploaded file
     * @param int $user_id User ID (for context)
     * @return int|false Attachment ID or false on failure
     */
    private function import_profile_image($file_url, $user_id) {
        if (empty($file_url)) {
            return false;
        }

        // Require WP file handling functions
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Convert URL to local file path
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_url);

        // If file doesn't exist at the expected path, try gravity_forms path
        if (!file_exists($file_path)) {
            // Try the raw URL path
            $parsed = wp_parse_url($file_url);
            if (!empty($parsed['path'])) {
                $file_path = ABSPATH . ltrim($parsed['path'], '/');
            }
        }

        if (!file_exists($file_path)) {
            error_log('BIM Verdi Profile Handler - Profile image file not found: ' . $file_path);
            return false;
        }

        // Delete existing profile image if present
        if (function_exists('get_field')) {
            $existing_image_id = get_field('profile_image', 'user_' . $user_id);
            if ($existing_image_id) {
                wp_delete_attachment($existing_image_id, true);
            }
        }

        // Prepare file for sideloading
        $filetype = wp_check_filetype(basename($file_path));
        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name('profil-' . $user_id . '-' . basename($file_path)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        // Copy file to uploads directory
        $upload = wp_upload_bits(basename($file_path), null, file_get_contents($file_path));

        if ($upload['error']) {
            error_log('BIM Verdi Profile Handler - Upload error: ' . $upload['error']);
            return false;
        }

        $attachment_id = wp_insert_attachment($attachment, $upload['file']);

        if (is_wp_error($attachment_id)) {
            error_log('BIM Verdi Profile Handler - Insert attachment error: ' . $attachment_id->get_error_message());
            return false;
        }

        // Generate attachment metadata
        $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attach_data);

        return $attachment_id;
    }
}

// Instantiate the handler
new BIM_Verdi_Profile_Form_Handler();
