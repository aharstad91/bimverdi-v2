<?php
/**
 * Member Registration Handler
 * Handles Gravity Forms submission and creates user + company
 *
 * @package BIMVerdiCore
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Member_Registration {
    
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
        // Hook into Gravity Forms submission
        // Note: The form ID will be set when creating the form
        // add_action('gform_after_submission_X', array($this, 'process_registration'), 10, 2);
    }
    
    /**
     * Process registration after form submission
     * This will be hooked to specific Gravity Form
     */
    public function process_registration($entry, $form) {
        // This is a placeholder for the actual implementation
        // Will be connected to Gravity Forms in step 9
        
        // Steps:
        // 1. Validate data
        // 2. Create WordPress user
        // 3. Create Medlemsbedrift post
        // 4. Set ACF fields
        // 5. Set taxonomies
        // 6. Link user to company via meta
        // 7. Send welcome email
        
        // Implementation will be completed in step 10
    }
}
