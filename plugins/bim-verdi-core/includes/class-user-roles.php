<?php
/**
 * Custom User Roles for BIM Verdi
 *
 * @package BIMVerdiCore
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_User_Roles {
    
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
        // Roles are created on plugin activation
        // See bim-verdi-core.php activate() method
    }
    
    /**
     * Create custom roles
     * Called on plugin activation
     */
    public static function create_roles() {
        
        // Role 1: Company Owner (Bedriftseier)
        // Can edit own company profile, manage tools, articles, ideas
        add_role('company_owner', __('Company Owner', 'bim-verdi-core'), array(
            'read'                   => true,
            'edit_posts'             => true,
            'edit_published_posts'   => true,
            'publish_posts'          => false,  // Posts must be approved
            'delete_posts'           => true,
            'delete_published_posts' => false,  // Can't delete published
            'upload_files'           => true,
            
            // Custom capabilities
            'edit_own_company'       => true,
            'manage_company_tools'   => true,
            'submit_articles'        => true,
            'submit_project_ideas'   => true,
            'register_events'        => true,
        ));
        
        // Role 2: Company User (Bedriftsbruker)
        // Limited access, can view and register for events
        add_role('company_user', __('Company User', 'bim-verdi-core'), array(
            'read'                   => true,
            'edit_posts'             => false,
            'upload_files'           => false,
            
            // Custom capabilities
            'view_company_profile'   => true,
            'submit_articles'        => true,
            'submit_project_ideas'   => true,
            'register_events'        => true,
        ));
        
        // Role 3: Member Coordinator (BIM Verdi Staff)
        // Can manage members, approve content, manage events
        add_role('member_coordinator', __('Member Coordinator', 'bim-verdi-core'), array(
            'read'                      => true,
            'edit_posts'                => true,
            'edit_others_posts'         => true,
            'edit_published_posts'      => true,
            'publish_posts'             => true,
            'delete_posts'              => true,
            'delete_others_posts'       => true,
            'delete_published_posts'    => true,
            'edit_pages'                => true,
            'edit_published_pages'      => true,
            'publish_pages'             => true,
            'delete_pages'              => true,
            'upload_files'              => true,
            'manage_categories'         => true,
            
            // Custom capabilities
            'manage_members'            => true,
            'approve_content'           => true,
            'manage_events'             => true,
            'view_all_submissions'      => true,
            'send_notifications'        => true,
        ));
        
        // Role 4: Public User (ikke-medlem)
        // Can only view public content
        add_role('public_user', __('Public User', 'bim-verdi-core'), array(
            'read' => true,
        ));
        
        // Add custom capabilities to Administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('edit_own_company');
            $admin_role->add_cap('manage_company_tools');
            $admin_role->add_cap('submit_articles');
            $admin_role->add_cap('submit_project_ideas');
            $admin_role->add_cap('register_events');
            $admin_role->add_cap('manage_members');
            $admin_role->add_cap('approve_content');
            $admin_role->add_cap('manage_events');
            $admin_role->add_cap('view_all_submissions');
            $admin_role->add_cap('send_notifications');
        }
    }
    
    /**
     * Remove custom roles
     * Called on plugin uninstall (not deactivation)
     */
    public static function remove_roles() {
        remove_role('company_owner');
        remove_role('company_user');
        remove_role('member_coordinator');
        remove_role('public_user');
    }
    
    /**
     * Check if user has company owner role
     */
    public static function is_company_owner($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        $user = get_userdata($user_id);
        return $user && in_array('company_owner', (array) $user->roles);
    }
    
    /**
     * Check if user has company user role
     */
    public static function is_company_user($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        $user = get_userdata($user_id);
        return $user && in_array('company_user', (array) $user->roles);
    }
    
    /**
     * Check if user has member coordinator role
     */
    public static function is_member_coordinator($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        $user = get_userdata($user_id);
        return $user && in_array('member_coordinator', (array) $user->roles);
    }
    
    /**
     * Check if user is any type of member (owner or user)
     */
    public static function is_member($user_id = null) {
        return self::is_company_owner($user_id) || self::is_company_user($user_id);
    }
    
    /**
     * Get user's linked company ID
     */
    public static function get_user_company($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        return get_user_meta($user_id, 'tilknyttet_bedrift', true);
    }
}
