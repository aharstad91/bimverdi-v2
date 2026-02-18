<?php
/**
 * Gravity Forms Manager
 * 
 * Orchestrates all Gravity Forms handlers
 * 
 * @package BIM_Verdi_Core
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Gravity_Forms_Manager {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize all form handlers
     */
    private function __construct() {
        $this->init_debug();
        $this->load_handlers();
    }
    
    /**
     * Initialize debug handler to suppress GF warnings
     */
    private function init_debug() {
        require_once plugin_dir_path(__FILE__) . 'class-gf-debug.php';
    }
    
    /**
     * Load and initialize all form handler classes
     */
    private function load_handlers() {
        
        // Load tool form handler
        require_once plugin_dir_path(__FILE__) . 'handlers/class-tool-form-handler.php';
        new BIM_Verdi_Tool_Form_Handler();
        
        // Load company form handler
        require_once plugin_dir_path(__FILE__) . 'handlers/class-company-form-handler.php';
        new BIM_Verdi_Company_Form_Handler();
        
        // Load user form handler (registration)
        require_once plugin_dir_path(__FILE__) . 'handlers/class-user-form-handler.php';
        new BIM_Verdi_User_Form_Handler();
        
        // Load profile form handler (profile editing)
        require_once plugin_dir_path(__FILE__) . 'handlers/class-profile-form-handler.php';
        new BIM_Verdi_Profile_Form_Handler();

        // Load kunnskapskilde form handler
        require_once plugin_dir_path(__FILE__) . 'handlers/class-kunnskapskilde-form-handler.php';
        new BIM_Verdi_Kunnskapskilde_Form_Handler();

        // Load company edit form handler (editing existing company)
        require_once plugin_dir_path(__FILE__) . 'handlers/class-company-edit-form-handler.php';
        new BIM_Verdi_Company_Edit_Form_Handler();

        // TODO: Load additional handlers as you create them:
        // - class-event-form-handler.php
        // - class-case-form-handler.php
    }
}

// Initialize the manager
add_action('plugins_loaded', function() {
    BIM_Verdi_Gravity_Forms_Manager::get_instance();
});
