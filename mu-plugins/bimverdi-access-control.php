<?php
/**
 * BIM Verdi Access Control System
 * 
 * Håndterer tilgangskontroll basert på om brukeren har foretak tilknyttet.
 * 
 * Brukernivåer:
 * - Profil-bruker: Registrert, men uten foretak. Begrenset tilgang.
 * - Foretak-bruker: Koblet til foretak. Full tilgang.
 * 
 * @package BIMVerdi
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main access control class
 */
class BIMVerdi_Access_Control {
    
    /**
     * Features that require company connection
     */
    const COMPANY_REQUIRED_FEATURES = array(
        'register_tool',      // Registrere verktøy
        'edit_tool',          // Redigere verktøy
        'join_temagruppe',    // Velge temagrupper
        'company_profile',    // Redigere foretaksprofil
        'view_members_full',  // Se fullt medlemsinnhold
    );
    
    /**
     * Features available to all registered users
     */
    const OPEN_FEATURES = array(
        'view_dashboard',     // Se Min Side dashboard
        'edit_profile',       // Redigere egen profil
        'view_catalog',       // Se medlemskatalog
        'view_tools',         // Se verktøykatalog
        'register_event',     // Melde seg på arrangementer
        'view_events',        // Se arrangementer
        'connect_company',    // Koble til foretak
    );
    
    /**
     * Initialize hooks
     */
    public function __construct() {
        // Add user meta box in admin
        add_action('show_user_profile', array($this, 'show_company_status_admin'));
        add_action('edit_user_profile', array($this, 'show_company_status_admin'));
        
        // REST API for checking access
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Shortcode for locked feature UI
        add_shortcode('bimverdi_locked_feature', array($this, 'locked_feature_shortcode'));
        
        // Template redirect for protected pages
        add_action('template_redirect', array($this, 'check_page_access'));
    }
    
    /**
     * Check if user can access a feature
     * 
     * @param string $feature Feature slug
     * @param int|null $user_id User ID (defaults to current user)
     * @return bool
     */
    public static function can_access($feature, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Not logged in = no access
        if (!$user_id) {
            return false;
        }
        
        // Admin always has access
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Open features - all logged in users
        if (in_array($feature, self::OPEN_FEATURES)) {
            return true;
        }
        
        // Company-required features
        if (in_array($feature, self::COMPANY_REQUIRED_FEATURES)) {
            return self::user_has_company($user_id);
        }
        
        // Unknown feature - deny by default
        return false;
    }
    
    /**
     * Check if user has a company linked
     * 
     * @param int|null $user_id
     * @return bool
     */
    public static function user_has_company($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        // Try new meta key first, then legacy key, then ACF field
        $company_id = get_user_meta($user_id, 'bimverdi_company_id', true);
        
        if (empty($company_id)) {
            $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true); // Legacy fallback
        }
        
        if (empty($company_id) && function_exists('get_field')) {
            $company_id = get_field('tilknyttet_foretak', 'user_' . $user_id); // ACF fallback
        }
        
        if (empty($company_id)) {
            return false;
        }
        
        // Verify company exists (accept publish or pending status)
        $company = get_post($company_id);
        return $company && $company->post_type === 'foretak' && in_array($company->post_status, array('publish', 'pending'));
    }
    
    /**
     * Get user's company data
     * 
     * @param int|null $user_id
     * @return array|false
     */
    public static function get_user_company($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!self::user_has_company($user_id)) {
            return false;
        }
        
        // Try new meta key first, then legacy key, then ACF field
        $company_id = get_user_meta($user_id, 'bimverdi_company_id', true);
        
        if (empty($company_id)) {
            $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true); // Legacy fallback
        }
        
        if (empty($company_id) && function_exists('get_field')) {
            $company_id = get_field('tilknyttet_foretak', 'user_' . $user_id); // ACF fallback
        }
        
        $company = get_post($company_id);
        
        if (!$company) {
            return false;
        }
        
        return array(
            'id' => $company->ID,
            'name' => $company->post_title,
            'status' => $company->post_status,
            'url' => get_permalink($company->ID),
            'role' => get_user_meta($user_id, 'bimverdi_company_role', true) ?: 'medlem',
        );
    }
    
    /**
     * Get user's account type
     * 
     * @param int|null $user_id
     * @return string 'profil', 'foretak', or 'guest'
     */
    public static function get_account_type($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return 'guest';
        }
        
        return self::user_has_company($user_id) ? 'foretak' : 'profil';
    }
    
    /**
     * Show company status in user admin
     */
    public function show_company_status_admin($user) {
        if (!current_user_can('edit_users')) {
            return;
        }
        
        $has_company = self::user_has_company($user->ID);
        $company = self::get_user_company($user->ID);
        $account_type = self::get_account_type($user->ID);
        ?>
        <h2>BIM Verdi Status</h2>
        <table class="form-table">
            <tr>
                <th><label>Kontotype</label></th>
                <td>
                    <?php if ($account_type === 'foretak'): ?>
                        <span style="color: green;">✅ Foretak-bruker</span>
                    <?php else: ?>
                        <span style="color: orange;">⚠️ Profil-bruker (uten foretak)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($company): ?>
            <tr>
                <th><label>Tilknyttet foretak</label></th>
                <td>
                    <a href="<?php echo get_edit_post_link($company['id']); ?>">
                        <?php echo esc_html($company['name']); ?>
                    </a>
                    <br>
                    <small>Status: <?php echo esc_html($company['status']); ?> | Rolle: <?php echo esc_html($company['role']); ?></small>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <th><label>Registrert</label></th>
                <td>
                    <?php 
                    $registered_at = get_user_meta($user->ID, 'bimverdi_registered_at', true);
                    echo $registered_at ? esc_html($registered_at) : 'Ukjent';
                    ?>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('bimverdi/v1', '/access/check', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_check_access'),
            'permission_callback' => '__return_true',
            'args' => array(
                'feature' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));
        
        register_rest_route('bimverdi/v1', '/access/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_status'),
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ));
    }
    
    /**
     * REST: Check feature access
     */
    public function rest_check_access($request) {
        $feature = sanitize_text_field($request->get_param('feature'));
        
        return array(
            'feature' => $feature,
            'has_access' => self::can_access($feature),
            'account_type' => self::get_account_type(),
            'requires_company' => in_array($feature, self::COMPANY_REQUIRED_FEATURES),
        );
    }
    
    /**
     * REST: Get user status
     */
    public function rest_get_status($request) {
        $user_id = get_current_user_id();
        
        return array(
            'user_id' => $user_id,
            'account_type' => self::get_account_type($user_id),
            'has_company' => self::user_has_company($user_id),
            'company' => self::get_user_company($user_id),
            'available_features' => self::OPEN_FEATURES,
            'locked_features' => self::user_has_company($user_id) ? array() : self::COMPANY_REQUIRED_FEATURES,
        );
    }
    
    /**
     * Shortcode for locked feature UI
     * 
     * Usage: [bimverdi_locked_feature feature="register_tool"]Content here[/bimverdi_locked_feature]
     */
    public function locked_feature_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'feature' => '',
            'message' => '',
        ), $atts);
        
        // If user has access, show content
        if (self::can_access($atts['feature'])) {
            return do_shortcode($content);
        }
        
        // Otherwise show locked UI
        return self::render_locked_ui($atts['feature'], $atts['message']);
    }
    
    /**
     * Render locked feature UI
     * 
     * @param string $feature
     * @param string $custom_message
     * @return string HTML
     */
    public static function render_locked_ui($feature, $custom_message = '') {
        $feature_names = array(
            'register_tool' => 'Registrere verktøy',
            'edit_tool' => 'Redigere verktøy',
            'join_temagruppe' => 'Velge temagrupper',
            'company_profile' => 'Redigere foretaksprofil',
            'view_members_full' => 'Se fullt medlemsinnhold',
        );
        
        $feature_name = isset($feature_names[$feature]) ? $feature_names[$feature] : $feature;
        $message = $custom_message ?: "For å {$feature_name} må du koble kontoen til et foretak.";
        
        ob_start();
        ?>
        <div class="bimverdi-locked-feature">
            <div class="locked-content">
                <div class="locked-icon">
                    <wa-icon name="lock" library="fa"></wa-icon>
                </div>
                <div class="locked-text">
                    <p class="locked-message"><?php echo esc_html($message); ?></p>
                    <wa-button variant="brand" size="small" href="<?php echo esc_url(home_url('/min-side/koble-foretak/')); ?>">
                        <wa-icon slot="prefix" name="building" library="fa"></wa-icon>
                        Koble til foretak
                    </wa-button>
                </div>
            </div>
        </div>
        <style>
        .bimverdi-locked-feature {
            background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
            border: 1px solid #F59E0B;
            border-radius: 0.75rem;
            padding: 1.25rem;
            margin: 1rem 0;
        }
        .bimverdi-locked-feature .locked-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .bimverdi-locked-feature .locked-icon {
            flex-shrink: 0;
            width: 3rem;
            height: 3rem;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #F59E0B;
            font-size: 1.25rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .bimverdi-locked-feature .locked-message {
            color: #92400E;
            margin: 0 0 0.75rem 0;
            font-size: 0.9375rem;
            font-weight: 500;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Check page access on template redirect
     */
    public function check_page_access() {
        // Only for logged in users
        if (!is_user_logged_in()) {
            return;
        }
        
        // Define protected pages and their required features
        $protected_pages = array(
            'min-side/registrer-verktoy' => 'register_tool',
            'min-side/rediger-verktoy' => 'edit_tool',
            'min-side/temagrupper' => 'join_temagruppe',
            'min-side/foretak' => 'company_profile',
        );
        
        $current_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        // Remove site path if in subdirectory
        $site_path = trim(parse_url(home_url(), PHP_URL_PATH), '/');
        if ($site_path && strpos($current_path, $site_path) === 0) {
            $current_path = trim(substr($current_path, strlen($site_path)), '/');
        }

        // Pages that should be accessible without company (open to all logged-in users)
        $open_pages = array(
            'min-side/foretak/registrer',
            'min-side/registrer-foretak',
        );
        foreach ($open_pages as $open_page) {
            if (strpos($current_path, $open_page) === 0) {
                return;
            }
        }

        foreach ($protected_pages as $page_path => $feature) {
            if (strpos($current_path, $page_path) === 0) {
                if (!self::can_access($feature)) {
                    // Redirect to Min Side with message
                    wp_redirect(add_query_arg('access_denied', $feature, home_url('/min-side/')));
                    exit;
                }
            }
        }
    }
}

// Initialize
new BIMVerdi_Access_Control();

/**
 * Template helper functions
 */

/**
 * Check if current user can access a feature
 * 
 * @param string $feature
 * @return bool
 */
function bimverdi_can_access($feature) {
    return BIMVerdi_Access_Control::can_access($feature);
}

/**
 * Check if user has a company linked (wrapper function)
 * 
 * @param int|null $user_id
 * @return bool
 */
function bimverdi_user_has_company($user_id = null) {
    return BIMVerdi_Access_Control::user_has_company($user_id);
}

/**
 * Get user's account type (wrapper function)
 * 
 * @param int|null $user_id
 * @return string 'profil', 'foretak', or 'guest'
 */
function bimverdi_get_account_type($user_id = null) {
    return BIMVerdi_Access_Control::get_account_type($user_id);
}

/**
 * Get user's company data (wrapper function)
 * 
 * @param int|null $user_id
 * @return array|false
 */
function bimverdi_get_user_company($user_id = null) {
    return BIMVerdi_Access_Control::get_user_company($user_id);
}

/**
 * Render locked feature card for Min Side
 * 
 * @param string $title Card title
 * @param string $description Description text
 * @param string $icon Icon name
 * @param string $feature Feature slug (for linking)
 */
function bimverdi_locked_card($title, $description, $icon = 'lock', $feature = '') {
    ?>
    <wa-card class="bimverdi-locked-card opacity-75">
        <div class="p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center">
                        <wa-icon name="<?php echo esc_attr($icon); ?>" library="fa" class="text-gray-400"></wa-icon>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-400"><?php echo esc_html($title); ?></h3>
                </div>
                <wa-badge variant="warning">
                    <wa-icon name="lock" library="fa" class="mr-1"></wa-icon>
                    Krever foretak
                </wa-badge>
            </div>
            <p class="text-gray-500 text-sm mb-4"><?php echo esc_html($description); ?></p>
            <wa-button variant="neutral" outline size="small" href="<?php echo esc_url(home_url('/min-side/koble-foretak/')); ?>">
                <wa-icon slot="prefix" name="building" library="fa"></wa-icon>
                Koble til foretak for å låse opp
            </wa-button>
        </div>
    </wa-card>
    <?php
}

/**
 * Render "Connect to company" CTA banner
 */
function bimverdi_connect_company_cta() {
    if (BIMVerdi_Access_Control::user_has_company()) {
        return; // Already connected
    }
    ?>
    <div class="bimverdi-connect-cta bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl p-6 mb-6">
        <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
            <div class="flex-shrink-0">
                <div class="w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center">
                    <wa-icon name="building" library="fa" class="text-amber-600 text-2xl"></wa-icon>
                </div>
            </div>
            <div class="flex-grow">
                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                    Få full tilgang til BIM Verdi
                </h3>
                <p class="text-gray-600 text-sm">
                    Koble kontoen din til et foretak for å registrere verktøy,
                    delta i temagrupper og mer.
                </p>
            </div>
            <div class="flex-shrink-0">
                <wa-button variant="brand" href="<?php echo esc_url(home_url('/min-side/koble-foretak/')); ?>">
                    <wa-icon slot="prefix" name="link" library="fa"></wa-icon>
                    Koble til foretak
                </wa-button>
            </div>
        </div>
    </div>
    <?php
}
