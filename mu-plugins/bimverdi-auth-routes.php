<?php
/**
 * BIM Verdi - Authentication Routes
 *
 * Handles custom authentication routes via rewrite rules.
 * Replaces wp-login.php with branded frontend pages.
 *
 * Routes:
 * - /logg-inn/           → Login form
 * - /glemt-passord/      → Request password reset
 * - /tilbakestill-passord/ → Set new password (via email token)
 * - /send-verifisering/  → Resend verification email
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main class for authentication routes
 */
class BIMVerdi_Auth_Routes {

    /**
     * Route definitions
     * Maps URL slugs to template files in parts/auth/
     */
    private static $routes = [
        'logg-inn'            => 'login',
        'logg-ut'             => 'logout',  // Handled specially - no template
        'glemt-passord'       => 'forgot-password',
        'tilbakestill-passord' => 'reset-password',
        'send-verifisering'   => 'resend-verification',
    ];

    /**
     * Initialize hooks
     */
    public function __construct() {
        // Register rewrite rules
        add_action('init', [$this, 'register_rewrites']);

        // Add query vars
        add_filter('query_vars', [$this, 'add_query_vars']);

        // Template redirect - handle routing
        add_action('template_redirect', [$this, 'handle_template_redirect']);

        // Filter login URL to use custom page
        add_filter('login_url', [$this, 'custom_login_url'], 10, 3);

        // Filter logout redirect
        add_filter('logout_redirect', [$this, 'logout_redirect'], 10, 3);

        // Filter lost password URL
        add_filter('lostpassword_url', [$this, 'custom_lostpassword_url'], 10, 2);

        // Filter logout URL - high priority to override any other filters
        add_filter('logout_url', [$this, 'custom_logout_url'], 999, 2);

        // Handle login form submission
        add_action('init', [$this, 'handle_login_submission']);

        // Handle forgot password form submission
        add_action('init', [$this, 'handle_forgot_password_submission']);

        // Handle reset password form submission
        add_action('init', [$this, 'handle_reset_password_submission']);

        // Handle resend verification submission
        add_action('init', [$this, 'handle_resend_verification']);

        // Disable default WP login for non-admins (optional - can be enabled later)
        // add_action('init', [$this, 'redirect_wp_login']);
    }

    /**
     * Register rewrite rules for auth pages
     */
    public function register_rewrites() {
        foreach (self::$routes as $slug => $template) {
            add_rewrite_rule(
                '^' . $slug . '/?$',
                'index.php?bimverdi_auth_route=' . $slug,
                'top'
            );
        }
    }

    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'bimverdi_auth_route';
        return $vars;
    }

    /**
     * Handle template redirect for auth routes
     */
    public function handle_template_redirect() {
        $auth_route = get_query_var('bimverdi_auth_route');

        if (empty($auth_route) || !isset(self::$routes[$auth_route])) {
            return;
        }

        // Handle logout specially - no template needed
        if ($auth_route === 'logg-ut') {
            $this->handle_logout();
            return;
        }

        // If logged in user visits login page, redirect to min-side
        if (is_user_logged_in() && $auth_route === 'logg-inn') {
            wp_redirect(home_url('/min-side/'));
            exit;
        }

        // Load the appropriate template
        $template_file = get_template_directory() . '/parts/auth/' . self::$routes[$auth_route] . '.php';

        if (file_exists($template_file)) {
            // Set up basic WordPress context
            status_header(200);
            nocache_headers();

            include $template_file;
            exit;
        }

        // Fallback: 404 if template doesn't exist
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
    }

    /**
     * Handle logout action
     */
    private function handle_logout() {
        // Verify nonce if present (optional for logout)
        $nonce = $_GET['_wpnonce'] ?? '';

        if (!empty($nonce) && !wp_verify_nonce($nonce, 'bimverdi_logout')) {
            // Invalid nonce, redirect to home
            wp_redirect(home_url('/'));
            exit;
        }

        // Log out the user
        wp_logout();

        // Redirect to login page with logged out message
        wp_redirect(add_query_arg('logged_out', '1', home_url('/logg-inn/')));
        exit;
    }

    /**
     * Custom login URL
     *
     * @param string $login_url Original login URL
     * @param string $redirect  Redirect URL after login
     * @param bool   $force_reauth Force re-authentication
     * @return string Custom login URL
     */
    public function custom_login_url($login_url, $redirect = '', $force_reauth = false) {
        $url = home_url('/logg-inn/');

        if (!empty($redirect)) {
            $url = add_query_arg('redirect_to', urlencode($redirect), $url);
        }

        if ($force_reauth) {
            $url = add_query_arg('reauth', '1', $url);
        }

        return $url;
    }

    /**
     * Custom lost password URL
     *
     * @param string $url Original URL
     * @param string $redirect Redirect after
     * @return string Custom URL
     */
    public function custom_lostpassword_url($url, $redirect = '') {
        $custom_url = home_url('/glemt-passord/');

        if (!empty($redirect)) {
            $custom_url = add_query_arg('redirect_to', urlencode($redirect), $custom_url);
        }

        return $custom_url;
    }

    /**
     * Custom logout URL
     *
     * @param string $logout_url Original logout URL
     * @param string $redirect Redirect after logout
     * @return string Custom logout URL
     */
    public function custom_logout_url($logout_url, $redirect = '') {
        $url = home_url('/logg-ut/');

        // Add nonce for security
        $url = wp_nonce_url($url, 'bimverdi_logout');

        return $url;
    }

    /**
     * Redirect after logout
     *
     * @param string $redirect_to Redirect URL
     * @param string $requested_redirect_to Requested redirect
     * @param WP_User $user User object
     * @return string Redirect URL
     */
    public function logout_redirect($redirect_to, $requested_redirect_to, $user) {
        // Redirect to login page with logged out message
        return add_query_arg('logged_out', '1', home_url('/logg-inn/'));
    }

    /**
     * Handle login form submission
     */
    public function handle_login_submission() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bimverdi_login'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bimverdi_login')) {
            wp_redirect(add_query_arg('login_error', 'nonce', home_url('/logg-inn/')));
            exit;
        }

        $username = sanitize_user($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        // Security: Validate redirect_to to prevent open redirect
        $redirect_to = wp_validate_redirect(
            esc_url_raw($_POST['redirect_to'] ?? ''),
            home_url('/min-side/')
        );

        if (empty($username) || empty($password)) {
            wp_redirect(add_query_arg('login_error', 'empty', home_url('/logg-inn/')));
            exit;
        }

        // Rate limiting: 5 attempts per 20 minutes per IP+username
        $rate_key = 'bv_login_fail_' . substr(hash('sha256',
            ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . strtolower($username)), 0, 32);
        $rate_data = get_transient($rate_key);
        if ($rate_data !== false && ($rate_data['count'] ?? 0) >= 5) {
            wp_redirect(add_query_arg('login_error', 'blocked', home_url('/logg-inn/')));
            exit;
        }

        // Attempt login
        $user = wp_signon([
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember,
        ], is_ssl());

        if (is_wp_error($user)) {
            // Security: Use generic error code for ALL credential failures
            // to prevent user enumeration (OWASP OAT-014)
            $credential_errors = ['invalid_username', 'invalid_email', 'incorrect_password',
                                  'empty_username', 'empty_password', 'invalidcombo'];
            $error_code = !empty(array_intersect($user->get_error_codes(), $credential_errors))
                ? 'invalid'
                : 'invalid';

            // Security: Timing normalization — run dummy bcrypt hash for non-existent users
            // so response time is consistent regardless of whether user exists
            $user_not_found_codes = ['invalid_username', 'invalid_email', 'invalidcombo'];
            if (!empty(array_intersect($user->get_error_codes(), $user_not_found_codes))) {
                wp_check_password(bin2hex(random_bytes(16)),
                    '$P$BIx5M8FKfHagxQDqRGUyFhbXw3AFAO.');
            }

            // Rate limiting: increment failure count
            $rate_data = get_transient($rate_key) ?: ['count' => 0];
            $rate_data['count']++;
            set_transient($rate_key, $rate_data, 1200); // 20 min lockout window

            // Security: Do NOT include username in URL (leaks whether user exists)
            wp_redirect(add_query_arg('login_error', $error_code, home_url('/logg-inn/')));
            exit;
        }

        // Successful login — clear rate limit on success
        delete_transient($rate_key);
        wp_redirect(esc_url_raw($redirect_to));
        exit;
    }

    /**
     * Handle forgot password form submission
     */
    public function handle_forgot_password_submission() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bimverdi_forgot_password'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bimverdi_forgot_password')) {
            wp_redirect(add_query_arg('error', 'nonce', home_url('/glemt-passord/')));
            exit;
        }

        $email = sanitize_email($_POST['email'] ?? '');

        if (empty($email) || !is_email($email)) {
            wp_redirect(add_query_arg('error', 'invalid_email', home_url('/glemt-passord/')));
            exit;
        }

        // Get user by email
        $user = get_user_by('email', $email);

        // Always show success to prevent email enumeration
        // But only send email if user exists
        if ($user) {
            // Generate reset key
            $key = get_password_reset_key($user);

            if (!is_wp_error($key)) {
                $this->send_password_reset_email($user, $key);
            }
        }

        // Always redirect to success (security: don't reveal if email exists)
        wp_redirect(add_query_arg('success', '1', home_url('/glemt-passord/')));
        exit;
    }

    /**
     * Send password reset email
     *
     * @param WP_User $user User object
     * @param string $key Reset key
     * @return bool
     */
    private function send_password_reset_email($user, $key) {
        $reset_url = add_query_arg([
            'key'   => $key,
            'login' => rawurlencode($user->user_login),
        ], home_url('/tilbakestill-passord/'));

        $subject = 'Tilbakestill passordet ditt - BIM Verdi';

        $message = $this->get_reset_email_html($user, $reset_url);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: BIM Verdi <noreply@bimverdi.no>',
        ];

        return wp_mail($user->user_email, $subject, $message, $headers);
    }

    /**
     * Get password reset email HTML
     *
     * @param WP_User $user User object
     * @param string $reset_url Reset URL
     * @return string HTML email
     */
    private function get_reset_email_html($user, $reset_url) {
        $display_name = $user->display_name ?: $user->user_login;

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="no">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #F7F5EF; color: #1A1A1A;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F7F5EF;">
                <tr>
                    <td style="padding: 40px 20px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden;">

                            <!-- Header -->
                            <tr>
                                <td style="background-color: #1A1A1A; padding: 32px; text-align: center;">
                                    <span style="color: #ffffff; font-size: 24px; font-weight: 700;">BIM Verdi</span>
                                </td>
                            </tr>

                            <!-- Content -->
                            <tr>
                                <td style="padding: 40px 32px;">
                                    <h1 style="margin: 0 0 24px 0; color: #1A1A1A; font-size: 24px; font-weight: 600;">
                                        Tilbakestill passordet ditt
                                    </h1>

                                    <p style="margin: 0 0 16px 0; color: #5A5A5A; font-size: 16px; line-height: 1.6;">
                                        Hei <?php echo esc_html($display_name); ?>,
                                    </p>

                                    <p style="margin: 0 0 24px 0; color: #5A5A5A; font-size: 16px; line-height: 1.6;">
                                        Vi har mottatt en forespørsel om å tilbakestille passordet for kontoen din.
                                        Klikk på knappen under for å velge et nytt passord.
                                    </p>

                                    <!-- CTA Button -->
                                    <div style="text-align: center; margin: 32px 0;">
                                        <a href="<?php echo esc_url($reset_url); ?>"
                                           style="display: inline-block; background-color: #1A1A1A; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-size: 16px; font-weight: 600;">
                                            Tilbakestill passord
                                        </a>
                                    </div>

                                    <p style="margin: 24px 0 0 0; color: #888888; font-size: 14px; line-height: 1.6;">
                                        Lenken er gyldig i 24 timer. Hvis du ikke har bedt om å tilbakestille passordet,
                                        kan du trygt ignorere denne e-posten.
                                    </p>

                                    <hr style="border: none; border-top: 1px solid #E5E0D5; margin: 32px 0;">

                                    <p style="margin: 0; color: #888888; font-size: 12px; text-align: center;">
                                        Fungerer ikke knappen? Kopier denne lenken:<br>
                                        <a href="<?php echo esc_url($reset_url); ?>" style="color: #5A5A5A; word-break: break-all;">
                                            <?php echo esc_html($reset_url); ?>
                                        </a>
                                    </p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #F7F5EF; padding: 24px 32px; text-align: center;">
                                    <p style="margin: 0; color: #888888; font-size: 12px;">
                                        &copy; <?php echo date('Y'); ?> BIM Verdi
                                    </p>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle reset password form submission
     */
    public function handle_reset_password_submission() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bimverdi_reset_password'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bimverdi_reset_password')) {
            wp_redirect(add_query_arg('error', 'nonce', home_url('/tilbakestill-passord/')));
            exit;
        }

        $key = sanitize_text_field($_POST['key'] ?? '');
        $login = sanitize_user($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        // Validate
        if (empty($password) || strlen($password) < 8) {
            wp_redirect(add_query_arg([
                'error' => 'weak_password',
                'key'   => $key,
                'login' => $login,
            ], home_url('/tilbakestill-passord/')));
            exit;
        }

        if ($password !== $password_confirm) {
            wp_redirect(add_query_arg([
                'error' => 'mismatch',
                'key'   => $key,
                'login' => $login,
            ], home_url('/tilbakestill-passord/')));
            exit;
        }

        // Check key
        $user = check_password_reset_key($key, $login);

        if (is_wp_error($user)) {
            wp_redirect(add_query_arg('error', 'invalid_key', home_url('/tilbakestill-passord/')));
            exit;
        }

        // Reset password
        reset_password($user, $password);

        // Redirect to login with success
        wp_redirect(add_query_arg('reset', 'success', home_url('/logg-inn/')));
        exit;
    }

    /**
     * Handle resend verification email
     */
    public function handle_resend_verification() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bimverdi_resend_verification'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bimverdi_resend_verification')) {
            wp_redirect(add_query_arg('error', 'nonce', home_url('/send-verifisering/')));
            exit;
        }

        $email = sanitize_email($_POST['email'] ?? '');

        if (empty($email) || !is_email($email)) {
            wp_redirect(add_query_arg('error', 'invalid_email', home_url('/send-verifisering/')));
            exit;
        }

        // Check if email verification class exists and use it
        if (class_exists('BIMVerdi_Email_Verification')) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'bimverdi_pending_registrations';

            // Check if there's a pending registration
            $pending = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE email = %s AND status = 'pending' ORDER BY created_at DESC LIMIT 1",
                $email
            ));

            if ($pending) {
                // Create new verification and send email
                $verifier = new BIMVerdi_Email_Verification();
                $new_pending = $verifier->create_pending_registration($email);

                if ($new_pending) {
                    $verifier->send_verification_email($email, $new_pending['token']);
                }
            }
        }

        // Always show success to prevent email enumeration
        wp_redirect(add_query_arg('success', '1', home_url('/send-verifisering/')));
        exit;
    }

    /**
     * Get all auth routes
     *
     * @return array Route definitions
     */
    public static function get_routes() {
        return self::$routes;
    }

    /**
     * Get URL for an auth route
     *
     * @param string $route Route slug
     * @param array $params Query parameters
     * @return string Full URL
     */
    public static function get_url($route, $params = []) {
        if (!isset(self::$routes[$route])) {
            return home_url('/');
        }

        $url = home_url('/' . $route . '/');

        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }

        return $url;
    }
}

// Initialize
new BIMVerdi_Auth_Routes();

/**
 * Helper function to get auth URL
 *
 * @param string $route Route slug (logg-inn, glemt-passord, etc.)
 * @param array $params Query parameters
 * @return string URL
 */
function bimverdi_auth_url($route, $params = []) {
    return BIMVerdi_Auth_Routes::get_url($route, $params);
}

/**
 * Security: Block REST API user listing for unauthenticated requests
 * Prevents user enumeration via /wp-json/wp/v2/users
 */
add_filter('rest_endpoints', function ($endpoints) {
    if (!is_user_logged_in()) {
        unset($endpoints['/wp/v2/users']);
        unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    }
    return $endpoints;
});

/**
 * Security: Block ?author=N enumeration for unauthenticated users
 */
add_action('template_redirect', function () {
    if ((is_author() || isset($_GET['author'])) && !is_user_logged_in()) {
        wp_redirect(home_url('/'), 301);
        exit;
    }
});
