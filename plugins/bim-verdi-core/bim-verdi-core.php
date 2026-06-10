<?php
/**
 * Plugin Name: BIM Verdi Core
 * Plugin URI: https://bimverdi.no
 * Description: Core functionality plugin for BIM Verdi member portal - handles CPTs, taxonomies, user roles, and business logic
 * Version: 2.0.0
 * Author: BIM Verdi
 * Author URI: https://bimverdi.no
 * Text Domain: bim-verdi-core
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package BIMVerdiCore
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BIM_VERDI_CORE_VERSION', '2.0.0');
define('BIM_VERDI_CORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BIM_VERDI_CORE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BIM_VERDI_CORE_PLUGIN_FILE', __FILE__);

// --- AEC AI Hub-synk (Trinn 1) ----------------------------------------------
// Se includes/aec-ai-hub/README.md + docs/plans/2026-06-03-002-…-plan.md.
// Trinn 1 kjører UTEN live Notion-tilgang, mot en committet JSON-fixture, og
// publiserer ALDRI automatisk (godkjenning er en separat bulk/batch-handling).
if (!defined('BV_AIHUB_LIVE')) {
    define('BV_AIHUB_LIVE', false);          // false = les committet fixture; true = Trinn 2 live-stub (kaster)
}
if (!defined('BV_AIHUB_AUTOPUBLISH')) {
    define('BV_AIHUB_AUTOPUBLISH', false);   // hard sikring: importeren setter ALDRI 'publish' selv
}
if (!defined('BV_AIHUB_FIXTURE_PATH')) {
    define('BV_AIHUB_FIXTURE_PATH', BIM_VERDI_CORE_PLUGIN_DIR . 'data/aec-ai-hub-tools.json');
}
// Fixture-property-navn (referér konstanter, ikke magiske strenger, nedstrøms).
if (!defined('BV_AIHUB_PROP_CHAMPION')) {
    define('BV_AIHUB_PROP_CHAMPION', 'champion');      // import-filter: 238 av 475
}
if (!defined('BV_AIHUB_PROP_AI_DRIVEN')) {
    define('BV_AIHUB_PROP_AI_DRIVEN', 'ai_driven');    // AI-badge-gate: 176 av 238
}
if (!defined('BV_AIHUB_PROP_CATEGORIES')) {
    define('BV_AIHUB_PROP_CATEGORIES', 'categories');  // → temagruppe-mapping
}

/**
 * Main BIM Verdi Core Plugin Class
 */
class BIM_Verdi_Core {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
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
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize components
        add_action('plugins_loaded', array($this, 'init_components'));
        
        // Activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        // Load user roles
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/class-user-roles.php';
        
        // Load custom post types
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/class-post-types.php';
        
        // Load taxonomies
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/class-taxonomies.php';
        
        // Load permissions
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/class-permissions.php';
        
        // Load member registration handler
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/class-member-registration.php';
        
        // Load email notifications
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/class-email-notifications.php';
        
        // Load user profile helper functions
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/helpers/user-profile-helpers.php';
        
        // Load ACF user profile fields registration
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/acf/register-user-fields.php';

        // Load ACF foretak fields registration (bv_hoveddomene)
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/acf/register-foretak-fields.php';

        // Load AEC AI Hub-synk delte hjelpefunksjoner (bv_aec_normalize_url, bv_aec_name_key).
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/aec-ai-hub/helpers.php';

        // Load AEC AI Hub-datakilde (fixture-leser + Trinn 2 live-stub). Ren klassedefinisjon,
        // ingen side-effekter — instansieres/kalles av orkestratoren i senere units (Fase C+).
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/aec-ai-hub/class-tool-source.php';

        // Load AEC AI Hub-kategorimapper (AEC-kategori → temagruppe; umappbar → «Midlertidig»).
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/aec-ai-hub/class-category-mapper.php';

        // Load AEC AI Hub-upserter (managed-markør-vaktet, URL-keyet upsert + dedup + livssyklus).
        // Ren klassedefinisjon, ingen side-effekter — drives av Sync-orkestratoren via CLI (Unit 6).
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/aec-ai-hub/class-tool-upserter.php';

        // Load AEC AI Hub-synk-orkestrator (mutex → to-fase fetch → upsert → reconcile).
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/aec-ai-hub/class-aihub-sync.php';

        // Load AEC AI Hub-selftest (committet, self-cleaning; drives av `wp bimverdi aihub-selftest`).
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/aec-ai-hub/class-selftest.php';

        // Load AEC AI Hub-frontend-hjelpere (AI-badge, attribusjon, Kilde-verdi) brukt av temaets maler.
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/aec-ai-hub/frontend.php';

        // Load AEC AI Hub-adminrapport (read-only diagnose under «Verktøy»; ingen handlingsknapper).
        require_once BIM_VERDI_CORE_PLUGIN_DIR . 'includes/aec-ai-hub/class-admin-report.php';

        // Gravity Forms removed — all forms replaced with plain HTML + mu-plugin handlers.
        // Former files: class-gravity-forms-manager.php, setup/class-profile-form-migration.php

        // Load WP-CLI commands
        if (defined('WP_CLI') && WP_CLI) {
            require_once BIM_VERDI_CORE_PLUGIN_DIR . 'cli/class-foretak-import-command.php';
        }
    }
    
    /**
     * Initialize components
     */
    public function init_components() {
        // Initialize user roles
        BIM_Verdi_User_Roles::get_instance();
        
        // Initialize custom post types
        BIM_Verdi_Post_Types::get_instance();
        
        // Initialize taxonomies
        BIM_Verdi_Taxonomies::get_instance();
        
        // Initialize permissions
        BIM_Verdi_Permissions::get_instance();
        
        // Initialize member registration
        BIM_Verdi_Member_Registration::get_instance();
        
        // Initialize email notifications
        BIM_Verdi_Email_Notifications::get_instance();
        
        // Gravity Forms removed — all forms replaced with plain HTML + mu-plugin handlers.
        
        // Set ACF JSON save/load points
        $this->setup_acf_json();
    }
    
    /**
     * Setup ACF JSON sync
     */
    private function setup_acf_json() {
        // Save ACF field groups as JSON in plugin folder
        add_filter('acf/settings/save_json', function($path) {
            return BIM_VERDI_CORE_PLUGIN_DIR . 'acf-json';
        });
        
        // Load ACF field groups from JSON in plugin folder
        add_filter('acf/settings/load_json', function($paths) {
            $paths[] = BIM_VERDI_CORE_PLUGIN_DIR . 'acf-json';
            return $paths;
        });
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create custom user roles
        BIM_Verdi_User_Roles::create_roles();
        
        // Register CPTs and taxonomies
        BIM_Verdi_Post_Types::get_instance();
        BIM_Verdi_Taxonomies::get_instance();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option('bim_verdi_core_activated', true);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Note: We don't remove roles on deactivation to preserve user data
    }
}

/**
 * Initialize the plugin
 */
function bim_verdi_core() {
    return BIM_Verdi_Core::get_instance();
}

// Start the plugin
bim_verdi_core();
