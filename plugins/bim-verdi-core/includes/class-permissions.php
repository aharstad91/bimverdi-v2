<?php
/**
 * Permissions and Access Control for BIM Verdi
 *
 * @package BIMVerdiCore
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Permissions {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Company profile edit permissions
        add_filter('map_meta_cap', array($this, 'company_edit_permissions'), 10, 4);
        
        // Filter posts based on ownership
        add_filter('pre_get_posts', array($this, 'filter_user_posts'));
    }
    
    /**
     * Control who can edit company profiles
     * Only company owner can edit their own company
     */
    public function company_edit_permissions($caps, $cap, $user_id, $args) {
        // Check if we're editing a post
        if ($cap !== 'edit_post' || !isset($args[0])) {
            return $caps;
        }
        
        $post = get_post($args[0]);
        
        // Only apply to foretak post type
        if (!$post || $post->post_type !== 'foretak') {
            return $caps;
        }
        
        // Admin can always edit
        if (user_can($user_id, 'manage_options')) {
            return $caps;
        }
        
        // Get user's linked company
        $user_company_id = get_user_meta($user_id, 'tilknyttet_bedrift', true);
        
        // Check if this is the user's company
        if ($user_company_id && $user_company_id == $post->ID) {
            // User is editing their own company
            if (BIM_Verdi_User_Roles::is_company_owner($user_id)) {
                // Company owner can edit
                return array('edit_posts');
            }
        }
        
        // Deny access
        return array('do_not_allow');
    }
    
    /**
     * Filter posts in admin to show only relevant ones
     */
    public function filter_user_posts($query) {
        // Only in admin
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Don't filter for admins
        if (current_user_can('manage_options')) {
            return;
        }
        
        global $pagenow;
        $user_id = get_current_user_id();
        
        // Filter based on post type
        $post_type = $query->get('post_type');
        
        // Case/Prosjektidé - show only own submissions
        if ($post_type === 'case' && $pagenow === 'edit.php') {
            $query->set('author', $user_id);
        }
        
        // Påmelding - show only own registrations
        if ($post_type === 'pamelding' && $pagenow === 'edit.php') {
            $query->set('meta_query', array(
                array(
                    'key' => 'bruker',
                    'value' => $user_id,
                )
            ));
        }
    }
    
    /**
     * Check if user can edit company
     */
    public static function can_edit_company($company_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Admin can always edit
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Check if it's user's company and they're owner
        $user_company_id = get_user_meta($user_id, 'tilknyttet_bedrift', true);
        
        return $user_company_id == $company_id && BIM_Verdi_User_Roles::is_company_owner($user_id);
    }
    
    /**
     * Check if user can view company (for privacy settings)
     */
    public static function can_view_company($company_id) {
        // All logged-in users can view member profiles
        return is_user_logged_in();
    }
}
