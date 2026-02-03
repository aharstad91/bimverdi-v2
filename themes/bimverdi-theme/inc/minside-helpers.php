<?php
/**
 * Min Side Helper Functions
 * 
 * Consolidated helper functions for the Min Side member portal.
 * Used by the router template and parts files.
 * 
 * @package BimVerdi_Theme
 * @since 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * =============================================================================
 * URL & ROUTE HELPERS
 * =============================================================================
 */

/**
 * Get the base URL for Min Side
 * 
 * @return string
 */
function bimverdi_minside_base_url() {
    return home_url('/min-side');
}

/**
 * Generate a Min Side URL
 * 
 * @param string $route Route path (e.g., 'verktoy', 'verktoy/rediger', 'profil/passord')
 * @param array $params Optional query parameters
 * @return string Full URL
 */
function bimverdi_minside_url($route = '', $params = []) {
    $base = bimverdi_minside_base_url();
    
    if (empty($route) || $route === 'dashboard') {
        $url = $base . '/';
    } else {
        $url = trailingslashit($base) . trailingslashit(ltrim($route, '/'));
    }
    
    if (!empty($params)) {
        $url = add_query_arg($params, $url);
    }
    
    return $url;
}

/**
 * Get the current Min Side route from URL
 * 
 * @return string Route path (e.g., 'verktoy', 'verktoy/rediger')
 */
function bimverdi_get_current_route() {
    // Try query var first (set by rewrite rules)
    $route = get_query_var('minside_route', '');
    
    if (!empty($route)) {
        return sanitize_text_field($route);
    }
    
    // Fallback: Parse from URL
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = parse_url($request_uri, PHP_URL_PATH);
    $path = trim($path, '/');
    
    // Remove WordPress subfolder if present
    $home_path = trim(parse_url(home_url(), PHP_URL_PATH), '/');
    if (!empty($home_path) && strpos($path, $home_path) === 0) {
        $path = substr($path, strlen($home_path) + 1);
    }
    
    // Check if path starts with min-side
    if (strpos($path, 'min-side') === 0) {
        $route = substr($path, strlen('min-side'));
        $route = trim($route, '/');
        return $route ?: 'dashboard';
    }
    
    return 'dashboard';
}

/**
 * Check if current page is a specific Min Side route
 * 
 * @param string|array $routes Route(s) to check
 * @return bool
 */
function bimverdi_is_minside_route($routes) {
    $current = bimverdi_get_current_route();

    if (is_array($routes)) {
        foreach ($routes as $route) {
            // Skip empty strings to prevent false matches
            if ($route === '') continue;
            if ($current === $route || strpos($current, $route . '/') === 0) {
                return true;
            }
        }
        return false;
    }

    // Skip empty string check
    if ($routes === '') return false;

    return $current === $routes || strpos($current, $routes . '/') === 0;
}

/**
 * Get the primary route segment (first part of path)
 * 
 * @return string Primary route (e.g., 'verktoy' from 'verktoy/rediger')
 */
function bimverdi_get_primary_route() {
    $route = bimverdi_get_current_route();
    $parts = explode('/', $route);
    return $parts[0] ?: 'dashboard';
}

/**
 * =============================================================================
 * COMPANY & USER HELPERS
 * =============================================================================
 */

/**
 * Get user's company ID with fallback support for legacy keys
 * 
 * NOTE: This function may already be defined in bimverdi-access-control.php mu-plugin.
 * The check ensures we don't redeclare it.
 * 
 * Checks in order:
 * 1. bimverdi_company_id (new standard)
 * 2. bim_verdi_company_id (legacy)
 * 3. ACF field 'tilknyttet_foretak'
 * 
 * @param int|null $user_id User ID (defaults to current user)
 * @return int|false Company post ID or false if not found
 */
if (!function_exists('bimverdi_get_user_company')) {
    function bimverdi_get_user_company($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        // Try new key first
        $company_id = get_user_meta($user_id, 'bimverdi_company_id', true);
        
        // Fallback to legacy key
        if (empty($company_id)) {
            $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
        }
        
        // Fallback to ACF field
        if (empty($company_id) && function_exists('get_field')) {
            $acf_company = get_field('tilknyttet_foretak', 'user_' . $user_id);
            if ($acf_company) {
                $company_id = is_object($acf_company) ? $acf_company->ID : $acf_company;
            }
        }
        
        return $company_id ? (int) $company_id : false;
    }
}

/**
 * Check if user has a company linked
 * 
 * @param int|null $user_id User ID (defaults to current user)
 * @return bool
 */
if (!function_exists('bimverdi_user_has_company')) {
    function bimverdi_user_has_company($user_id = null) {
        return (bool) bimverdi_get_user_company($user_id);
    }
}

/**
 * Check if user is hovedkontakt (primary contact) for a company
 * 
 * @param int|null $user_id User ID (defaults to current user)
 * @param int|null $company_id Company ID (defaults to user's company)
 * @return bool
 */
if (!function_exists('bimverdi_is_hovedkontakt')) {
    function bimverdi_is_hovedkontakt($user_id = null, $company_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        if (!$company_id) {
            $company_id = bimverdi_get_user_company($user_id);
        }
        
        if (!$company_id) {
            return false;
        }
        
        // Check ACF field
        if (function_exists('get_field')) {
            $hovedkontakt_id = get_field('hovedkontaktperson', $company_id);
            if ($hovedkontakt_id) {
                // Could be user object or ID
                $hovedkontakt_id = is_object($hovedkontakt_id) ? $hovedkontakt_id->ID : $hovedkontakt_id;
                return (int) $hovedkontakt_id === (int) $user_id;
            }
        }
        
        return false;
    }
}

/**
 * Get user's account type for display
 * 
 * @param int|null $user_id User ID (defaults to current user)
 * @return string Account type label
 */
if (!function_exists('bimverdi_get_account_type')) {
    function bimverdi_get_account_type($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        
        if (!$user) {
            return 'Ukjent';
        }
        
        if (in_array('administrator', $user->roles)) {
            return 'Administrator';
        }
        
        if (bimverdi_is_hovedkontakt($user_id)) {
            return 'Hovedkontakt';
        }
        
        if (in_array('company_owner', $user->roles)) {
            return 'Foretakseier';
        }
        
        if (in_array('company_user', $user->roles)) {
            return 'Foretaksbruker';
        }
        
        return 'Deltaker';
    }
}

/**
 * Check if user's company is active (approved)
 * 
 * @param int|null $company_id Company ID (defaults to current user's company)
 * @return bool
 */
if (!function_exists('bimverdi_is_company_active')) {
    function bimverdi_is_company_active($company_id = null) {
        if (!$company_id) {
            $company_id = bimverdi_get_user_company();
        }
        
        if (!$company_id) {
            return false;
        }
        
        if (function_exists('get_field')) {
            return (bool) get_field('er_aktiv_deltaker', $company_id);
        }
        
        return false;
    }
}

/**
 * =============================================================================
 * AUTHENTICATION & PROTECTION
 * =============================================================================
 */

/**
 * Protect Min Side routes - redirect to login if not authenticated
 * 
 * Hook this to 'template_redirect' action
 */
if (!function_exists('bimverdi_protect_minside')) {
    function bimverdi_protect_minside() {
        // Only run on Min Side pages
        if (!bimverdi_is_on_minside()) {
            return;
        }
        
        // Check if logged in
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/logg-inn/?redirect_to=' . urlencode(bimverdi_minside_url())));
            exit;
        }
    }
}

/**
 * Check if we're currently on a Min Side page
 * 
 * @return bool
 */
if (!function_exists('bimverdi_is_on_minside')) {
    function bimverdi_is_on_minside() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($request_uri, PHP_URL_PATH);
        
        return strpos($path, '/min-side') !== false;
    }
}

/**
 * Redirect after login for company users
 * 
 * NOTE: This may conflict with bimverdi_login_redirect in functions.php.
 * Using function_exists check.
 * 
 * @param string $redirect_to Original redirect URL
 * @param string $request Request URL
 * @param WP_User $user User object
 * @return string Redirect URL
 */
if (!function_exists('bimverdi_login_redirect_minside')) {
    function bimverdi_login_redirect_minside($redirect_to, $request, $user) {
        if (!isset($user->roles) || !is_array($user->roles)) {
            return $redirect_to;
        }
        
        $member_roles = ['company_owner', 'company_user', 'subscriber'];
        
        if (array_intersect($member_roles, $user->roles)) {
            return bimverdi_minside_url();
        }
        
        return $redirect_to;
    }
}

/**
 * =============================================================================
 * ACCOUNT ROUTES (Profile & Company Settings)
 * =============================================================================
 */

/**
 * Check if current route is an account-related route
 *
 * Account routes include profile and foretak pages that show the account sidenav.
 *
 * @return bool
 */
if (!function_exists('bimverdi_is_account_route')) {
    function bimverdi_is_account_route() {
        $account_routes = [
            'profil',
            'profil/rediger',
            'profil/passord',
            'foretak',
            'foretak/rediger',
            'foretak/kolleger',
        ];

        $current = bimverdi_get_current_route();
        return in_array($current, $account_routes, true);
    }
}

/**
 * Get all account route definitions
 *
 * Returns the list of routes that are part of the account/settings section.
 * Used by account-sidenav component.
 *
 * @return array List of account route identifiers
 */
if (!function_exists('bimverdi_get_account_routes')) {
    function bimverdi_get_account_routes() {
        return [
            'profil',
            'profil/rediger',
            'profil/passord',
            'foretak',
            'foretak/rediger',
            'foretak/kolleger',
        ];
    }
}

/**
 * =============================================================================
 * ROUTE DEFINITIONS
 * =============================================================================
 */

/**
 * Get all Min Side routes
 * 
 * @return array Route map [route => part_file]
 */
if (!function_exists('bimverdi_get_minside_routes')) {
    function bimverdi_get_minside_routes() {
        return [
            // Dashboard
            'dashboard'           => 'dashboard',
            ''                    => 'dashboard',
            
            // Profile
            'profil'              => 'profil',
            'profil/rediger'      => 'profil-rediger',
            'profil/passord'      => 'profil-passord',
            
            // Company (Foretak)
            'foretak'             => 'foretak-detail',
            'foretak/rediger'     => 'foretak-rediger',
            'foretak/registrer'   => 'foretak-registrer',
            
            // Tools (Verktøy)
            'verktoy'             => 'verktoy-list',
            'verktoy/registrer'   => 'verktoy-registrer',
            'verktoy/rediger'     => 'verktoy-rediger',

            // Knowledge Sources (Kunnskapskilder)
            'kunnskapskilder'           => 'kunnskapskilder-list',
            'kunnskapskilder/registrer' => 'kunnskapskilder-registrer',
            'kunnskapskilder/rediger'   => 'kunnskapskilder-rediger',

            // Events
            'arrangementer'       => 'arrangementer-list',
            
            // Company Colleagues (hovedkontakt only)
            'foretak/kolleger'    => 'foretak-team',

            // Legacy redirect
            'foretak/team'        => 'foretak-team',
            
            // Legacy route mappings (for backward compatibility)
            'mine-verktoy'        => 'verktoy-list',
            'registrer-verktoy'   => 'verktoy-registrer',
            'rediger-verktoy'     => 'verktoy-rediger',
            'rediger-foretak'     => 'foretak-rediger',
            'registrer-foretak'   => 'foretak-registrer',
            'rediger-profil'      => 'profil-rediger',
            'endre-passord'       => 'profil-passord',
        ];
    }
}

/**
 * Get the part file for a given route
 * 
 * @param string $route Route path
 * @return string|null Part file name or null if not found
 */
if (!function_exists('bimverdi_get_route_part')) {
    function bimverdi_get_route_part($route) {
        $routes = bimverdi_get_minside_routes();
        return $routes[$route] ?? null;
    }
}

/**
 * =============================================================================
 * NAVIGATION DATA
 * =============================================================================
 */

/**
 * Get Min Side navigation items
 * 
 * @return array Navigation structure
 */
if (!function_exists('bimverdi_get_minside_nav')) {
    function bimverdi_get_minside_nav() {
        $user_id = get_current_user_id();
        $has_company = bimverdi_user_has_company($user_id);
        $is_hovedkontakt = bimverdi_is_hovedkontakt($user_id);
        
        // Count tools for badge
        $tool_count = 0;
        if ($has_company) {
            $company_id = bimverdi_get_user_company($user_id);
            // Use constant if defined, otherwise use slug directly
            $tool_cpt = defined('BV_CPT_TOOL') ? BV_CPT_TOOL : 'verktoy';
            $tools = get_posts([
                'post_type' => $tool_cpt,
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'tilknyttet_foretak',
                        'value' => $company_id,
                        'compare' => '='
                    ]
                ],
                'fields' => 'ids'
            ]);
            $tool_count = count($tools);
        }
        
        $nav = [
            'dashboard' => [
                'label' => 'Dashbord',
                'url' => bimverdi_minside_url(''),
                'icon' => 'layout-dashboard',
                'routes' => ['dashboard'],
            ],
            'verktoy' => [
                'label' => 'Mine verktøy',
                'url' => bimverdi_minside_url('verktoy'),
                'icon' => 'wrench',
                'badge' => $tool_count > 0 ? $tool_count : null,
                'routes' => ['verktoy', 'verktoy/registrer', 'verktoy/rediger'],
            ],
            'kunnskapskilder' => [
                'label' => 'Kunnskapskilder',
                'url' => bimverdi_minside_url('kunnskapskilder'),
                'icon' => 'book-open',
                'routes' => ['kunnskapskilder', 'kunnskapskilder/registrer', 'kunnskapskilder/rediger'],
            ],
            'arrangementer' => [
                'label' => 'Arrangementer',
                'url' => bimverdi_minside_url('arrangementer'),
                'icon' => 'calendar',
                'routes' => ['arrangementer'],
            ],
        ];
        
        return $nav;
    }
}

/**
 * =============================================================================
 * REWRITE RULES REGISTRATION
 * =============================================================================
 */

/**
 * Register Min Side rewrite rules
 * 
 * Called on 'init' action
 */
if (!function_exists('bimverdi_register_minside_rewrites')) {
    function bimverdi_register_minside_rewrites() {
        // Match /min-side/any/path/here/
        add_rewrite_rule(
            '^min-side/(.+?)/?$',
            'index.php?pagename=min-side&minside_route=$matches[1]',
            'top'
        );
        
        // Match /min-side/ (root)
        add_rewrite_rule(
            '^min-side/?$',
            'index.php?pagename=min-side&minside_route=dashboard',
            'top'
        );
    }
}

/**
 * Add minside_route to allowed query vars
 */
if (!function_exists('bimverdi_minside_query_vars')) {
    function bimverdi_minside_query_vars($vars) {
        $vars[] = 'minside_route';
        return $vars;
    }
}

/**
 * =============================================================================
 * INITIALIZATION HOOKS
 * =============================================================================
 */

// Register rewrite rules
add_action('init', 'bimverdi_register_minside_rewrites', 10);

// Register query var
add_filter('query_vars', 'bimverdi_minside_query_vars');

// Protect Min Side pages (only add if function defined in this file)
if (function_exists('bimverdi_protect_minside') && !has_action('template_redirect', 'bimverdi_protect_minside')) {
    add_action('template_redirect', 'bimverdi_protect_minside', 5);
}
