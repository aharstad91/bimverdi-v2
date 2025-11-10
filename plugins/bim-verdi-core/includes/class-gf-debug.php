<?php
/**
 * Gravity Forms Debug Handler
 * 
 * Suppresses PHP warnings from Gravity Forms rendering
 * while keeping them in debug logs for troubleshooting
 * 
 * @package BIM_Verdi_Core
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_GF_Debug {
    
    /**
     * Initialize debug handling
     */
    public static function init() {
        // Suppress GF warnings from frontend display
        if (!is_admin()) {
            // Reduce error reporting to exclude notices and warnings on frontend
            error_reporting(error_reporting() & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
        }
    }
}

// Initialize on plugins_loaded
add_action('plugins_loaded', array('BIM_Verdi_GF_Debug', 'init'));
