<?php
/**
 * User Profile Helper Functions
 * 
 * Helper functions for retrieving, displaying, and querying user profile data
 * stored in ACF fields.
 * 
 * All profile data (phone, job_title, linkedin_url) is stored as ACF user fields
 * and can be accessed via these helpers or directly via ACF functions.
 * 
 * Usage:
 *   $profile = bim_get_user_profile($user_id);
 *   echo $profile['phone'];
 *   
 *   $phone = bim_get_user_profile_field('phone', $user_id);
 *   echo $phone;
 * 
 * @package BIM_Verdi_Core
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get complete user profile data
 * 
 * Returns all profile information for a user (both WordPress standard
 * fields and ACF fields). Useful for displaying user profile pages.
 * 
 * @param int $user_id User ID
 * @return array Array with keys: first_name, last_name, email, phone, job_title, linkedin_url, user_url
 */
function bim_get_user_profile($user_id) {
    
    if (!$user_id || !is_numeric($user_id)) {
        return array();
    }
    
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        return array();
    }
    
    return array(
        'ID' => $user_id,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->user_email,
        'phone' => bim_get_user_profile_field('phone', $user_id),
        'job_title' => bim_get_user_profile_field('job_title', $user_id),
        'linkedin_url' => bim_get_user_profile_field('linkedin_url', $user_id),
        'user_url' => get_author_posts_url($user_id),
    );
}

/**
 * Get single user profile field value
 * 
 * Retrieves a single profile field value from ACF.
 * Falls back to user_meta for legacy compatibility.
 * 
 * @param string $field_name Field name: 'phone', 'job_title', or 'linkedin_url'
 * @param int $user_id User ID
 * @return string|null Field value or null if not found
 */
function bim_get_user_profile_field($field_name, $user_id) {
    
    if (!$user_id || !is_numeric($user_id)) {
        return null;
    }
    
    // Try ACF first (primary storage)
    if (function_exists('get_field')) {
        $value = get_field($field_name, 'user_' . $user_id);
        if ($value !== false && $value !== null) {
            return $value;
        }
    }
    
    // Fallback: Check legacy user_meta keys for backward compatibility
    $legacy_meta_keys = array(
        'phone' => 'phone',
        'job_title' => 'stillingstittel',
        'linkedin_url' => 'linkedin_profile',
    );
    
    if (isset($legacy_meta_keys[$field_name])) {
        $value = get_user_meta($user_id, $legacy_meta_keys[$field_name], true);
        if ($value) {
            return $value;
        }
    }
    
    return null;
}

/**
 * Update user profile field via ACF
 * 
 * Saves or updates a single profile field value.
 * Should be used when updating profile data outside of Gravity Forms.
 * 
 * @param string $field_name Field name: 'phone', 'job_title', or 'linkedin_url'
 * @param string $value The value to save
 * @param int $user_id User ID
 * @return bool Success or failure
 */
function bim_update_user_profile_field($field_name, $value, $user_id) {
    
    if (!$user_id || !is_numeric($user_id)) {
        return false;
    }
    
    if (!function_exists('update_field')) {
        return false;
    }
    
    $result = update_field($field_name, $value, 'user_' . $user_id);
    
    return (bool) $result;
}

/**
 * Get user profile display name
 * 
 * Returns a formatted display name for the user.
 * Format: "First Last" or email if name not available.
 * 
 * @param int $user_id User ID
 * @return string Display name
 */
function bim_get_user_display_name($user_id) {
    
    $profile = bim_get_user_profile($user_id);
    
    if ($profile['first_name'] || $profile['last_name']) {
        return trim($profile['first_name'] . ' ' . $profile['last_name']);
    }
    
    return $profile['email'];
}

/**
 * Populate Gravity Forms form with user profile data
 * 
 * Pre-fills a Gravity Form with user profile data for editing.
 * Useful for "Edit Profile" forms or user account pages.
 * 
 * @param array $form The form object (from gform_pre_render)
 * @param int $user_id User ID to populate from
 * @return array Modified form object
 */
function bim_populate_form_with_user_data($form, $user_id) {
    
    if (!$user_id || !is_numeric($user_id)) {
        return $form;
    }
    
    $profile = bim_get_user_profile($user_id);
    
    // Map profile data to form fields
    // Adjust field IDs based on your form structure
    $field_mapping = array(
        1 => 'first_name',      // Field 1: First Name
        2 => 'last_name',       // Field 2: Last Name
        3 => 'email',           // Field 3: Email
        4 => 'phone',           // Field 4: Phone
        5 => 'job_title',       // Field 5: Job Title
        6 => 'linkedin_url',    // Field 6: LinkedIn URL
    );
    
    foreach ($form['fields'] as &$field) {
        $field_id = $field->id;
        
        if (isset($field_mapping[$field_id]) && isset($profile[$field_mapping[$field_id]])) {
            $field->defaultValue = $profile[$field_mapping[$field_id]];
        }
    }
    
    return $form;
}

/**
 * Query users by profile field value
 * 
 * Find users that have a specific value in a profile field.
 * Uses ACF query meta if available.
 * 
 * @param string $field_name Profile field name: 'phone', 'job_title', or 'linkedin_url'
 * @param string $value The value to search for
 * @param int $limit Maximum number of results (default: 10)
 * @return array Array of WP_User objects
 */
function bim_get_users_by_profile_field($field_name, $value, $limit = 10) {
    
    if (!$value) {
        return array();
    }
    
    // Use ACF query if available
    if (function_exists('get_field')) {
        $args = array(
            'meta_query' => array(
                array(
                    'key' => $field_name,
                    'value' => $value,
                    'compare' => 'LIKE',
                ),
            ),
            'number' => $limit,
        );
        
        $users = get_users($args);
        return $users;
    }
    
    // Fallback to standard user meta query
    $args = array(
        'meta_query' => array(
            array(
                'key' => $field_name,
                'value' => $value,
                'compare' => 'LIKE',
            ),
        ),
        'number' => $limit,
    );
    
    return get_users($args);
}

/**
 * Get all users with profile data loaded
 * 
 * Returns all users with their profile information pre-loaded.
 * Useful for user directories or admin lists.
 * 
 * @param array $args WP_User_Query arguments
 * @return array Array with user data and profile information
 */
function bim_get_users_with_profiles($args = array()) {
    
    $default_args = array(
        'role' => 'company_user',
        'orderby' => 'name',
        'order' => 'ASC',
        'number' => 50,
    );
    
    $args = wp_parse_args($args, $default_args);
    $users = get_users($args);
    
    $user_data = array();
    
    foreach ($users as $user) {
        $user_data[] = bim_get_user_profile($user->ID);
    }
    
    return $user_data;
}

/**
 * Check if user has complete profile
 * 
 * Verifies that a user has filled in all required profile fields.
 * Returns false if any required field is empty.
 * 
 * @param int $user_id User ID
 * @param array $required_fields Fields to check: 'phone', 'job_title', 'linkedin_url'
 * @return bool True if all required fields are filled
 */
function bim_user_has_complete_profile($user_id, $required_fields = array('phone', 'job_title')) {
    
    foreach ($required_fields as $field_name) {
        $value = bim_get_user_profile_field($field_name, $user_id);
        
        if (empty($value)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Get user profile edit URL (if frontend form exists)
 * 
 * Returns the URL to the user's profile edit page (if one is set up).
 * This assumes a page exists with an acf_form() for user profile editing.
 * 
 * @param int $user_id User ID
 * @return string URL or empty string if not configured
 */
function bim_get_user_profile_edit_url($user_id) {
    
    // If you set up a page with ID 123 for profile editing, configure here:
    $profile_page_id = apply_filters('bim_user_profile_edit_page_id', 0);
    
    if (!$profile_page_id) {
        return '';
    }
    
    return add_query_arg('user_id', $user_id, get_permalink($profile_page_id));
}

/**
 * Display user profile card HTML
 * 
 * Returns HTML for a user profile card showing name, title, phone, LinkedIn.
 * 
 * @param int $user_id User ID
 * @param array $options Display options
 * @return string HTML
 */
function bim_get_user_profile_card_html($user_id, $options = array()) {
    
    $profile = bim_get_user_profile($user_id);
    
    if (empty($profile['ID'])) {
        return '';
    }
    
    $defaults = array(
        'show_email' => false,
        'show_edit_link' => false,
        'show_avatar' => true,
        'class' => 'bim-profile-card',
    );
    
    $options = wp_parse_args($options, $defaults);
    
    $html = '<div class="' . esc_attr($options['class']) . '">';
    
    if ($options['show_avatar']) {
        $html .= '<div class="profile-avatar">';
        $html .= get_avatar($profile['email'], 80);
        $html .= '</div>';
    }
    
    $html .= '<div class="profile-info">';
    $html .= '<h3>' . esc_html(bim_get_user_display_name($user_id)) . '</h3>';
    
    if ($profile['job_title']) {
        $html .= '<p class="profile-title">' . esc_html($profile['job_title']) . '</p>';
    }
    
    if ($options['show_email']) {
        $html .= '<p class="profile-email"><a href="mailto:' . esc_attr($profile['email']) . '">' . esc_html($profile['email']) . '</a></p>';
    }
    
    if ($profile['phone']) {
        $html .= '<p class="profile-phone"><a href="tel:' . esc_attr($profile['phone']) . '">' . esc_html($profile['phone']) . '</a></p>';
    }
    
    if ($profile['linkedin_url']) {
        $html .= '<p class="profile-linkedin"><a href="' . esc_url($profile['linkedin_url']) . '" target="_blank">LinkedIn →</a></p>';
    }
    
    $html .= '</div>';
    
    if ($options['show_edit_link'] && current_user_can('edit_user', $user_id)) {
        $edit_url = bim_get_user_profile_edit_url($user_id);
        if ($edit_url) {
            $html .= '<p class="profile-edit"><a href="' . esc_url($edit_url) . '">Rediger profil →</a></p>';
        }
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Echo user profile card HTML
 * 
 * @see bim_get_user_profile_card_html()
 */
function bim_user_profile_card_html($user_id, $options = array()) {
    echo bim_get_user_profile_card_html($user_id, $options);
}
