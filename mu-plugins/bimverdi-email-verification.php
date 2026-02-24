<?php
/**
 * BIM Verdi Email Verification System
 *
 * Handles two-step user registration:
 * 1. Email-only signup -> sends verification email
 * 2. Verification page -> complete with name + password
 *
 * Uses plain HTML forms with POST-Redirect-GET pattern.
 * No Gravity Forms dependency.
 *
 * @package BIMVerdi
 * @version 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main class for email verification system
 */
class BIMVerdi_Email_Verification {

    /** @var string Database table name */
    private $table_name;

    /** @var int Token expiry in seconds (24 hours) */
    const TOKEN_EXPIRY = 86400;

    /**
     * Constructor - initialize hooks
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'bimverdi_pending_registrations';

        // Database setup
        register_activation_hook(__FILE__, array($this, 'create_table'));
        add_action('admin_init', array($this, 'maybe_create_table'));

        // POST handlers for form submissions
        add_action('init', array($this, 'handle_email_signup_submission'));
        add_action('init', array($this, 'handle_verification_submission'));

        // Cleanup expired tokens (daily cron)
        add_action('bimverdi_cleanup_expired_tokens', array($this, 'cleanup_expired_tokens'));
        if (!wp_next_scheduled('bimverdi_cleanup_expired_tokens')) {
            wp_schedule_event(time(), 'daily', 'bimverdi_cleanup_expired_tokens');
        }
    }

    // =========================================================================
    // POST Handlers
    // =========================================================================

    /**
     * Handle email signup form submission (Step 1)
     *
     * POST-Redirect-GET pattern:
     * - Success: /registrer/?success=1&email=X
     * - Error:   /registrer/?bv_error=<code>&email=X
     */
    public function handle_email_signup_submission() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bimverdi_email_signup'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bimverdi_email_signup')) {
            wp_redirect(add_query_arg('bv_error', 'nonce', home_url('/registrer/')));
            exit;
        }

        // Honeypot check
        if (!empty($_POST['bv_hp_field'] ?? '')) {
            // Bot detected - silently redirect to success to not reveal honeypot
            wp_redirect(add_query_arg('success', '1', home_url('/registrer/')));
            exit;
        }

        $email = sanitize_email($_POST['email'] ?? '');
        $redirect_base = home_url('/registrer/');

        // Validate email
        if (empty($email) || !is_email($email)) {
            wp_redirect(add_query_arg(array('bv_error' => 'invalid_email', 'email' => urlencode($email)), $redirect_base));
            exit;
        }

        // Rate limiting: max 3 per email per hour (check before any user lookup)
        $rate_key = 'bv_signup_' . md5($email);
        $attempts = (int) get_transient($rate_key);
        if ($attempts >= 3) {
            wp_redirect(add_query_arg(array('bv_error' => 'rate_limit', 'email' => urlencode($email)), $redirect_base));
            exit;
        }
        set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);

        // Security: Always show success to prevent email enumeration.
        // If user exists, send a "you already have an account" email instead.
        if (email_exists($email)) {
            $this->send_already_registered_email($email);
            wp_redirect(add_query_arg(array('success' => '1', 'email' => urlencode($email)), $redirect_base));
            exit;
        }

        // Create pending registration
        $pending = $this->create_pending_registration($email);

        if (!$pending) {
            error_log('BIMVerdi: Failed to create pending registration for ' . $email);
            // Still show success to prevent enumeration
            wp_redirect(add_query_arg(array('success' => '1', 'email' => urlencode($email)), $redirect_base));
            exit;
        }

        // Send verification email
        $this->send_verification_email($email, $pending['token']);

        error_log('BIMVerdi: Verification email sent to ' . $email);

        // Redirect to success
        wp_redirect(add_query_arg(array('success' => '1', 'email' => urlencode($email)), $redirect_base));
        exit;
    }

    /**
     * Handle verification form submission (Step 2)
     *
     * POST-Redirect-GET pattern:
     * - Success: /min-side/?welcome=1
     * - Error:   /aktiver-konto/?email=X&token=X&bv_error=<code>
     */
    public function handle_verification_submission() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bimverdi_verify_account'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bimverdi_verify_account')) {
            wp_redirect(add_query_arg('bv_error', 'nonce', home_url('/aktiver-konto/')));
            exit;
        }

        $email = sanitize_email($_POST['email'] ?? '');
        $token = sanitize_text_field($_POST['token'] ?? '');
        $full_name = sanitize_text_field($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';

        $error_redirect = add_query_arg(array(
            'email' => urlencode($email),
            'token' => urlencode($token),
        ), home_url('/aktiver-konto/'));

        // Validate name
        if (empty($full_name)) {
            wp_redirect(add_query_arg('bv_error', 'missing_name', $error_redirect));
            exit;
        }

        // Validate password
        if (strlen($password) < 8) {
            wp_redirect(add_query_arg('bv_error', 'weak_password', $error_redirect));
            exit;
        }

        // Verify token
        $token_check = $this->verify_token($token, $email);
        if (!$token_check['valid']) {
            wp_redirect(add_query_arg('bv_error', 'token_invalid', $error_redirect));
            exit;
        }

        // Check if user already exists
        if (email_exists($email)) {
            wp_redirect(add_query_arg('bv_error', 'user_exists', $error_redirect));
            exit;
        }

        // Create WordPress user
        $user_id = wp_create_user($email, $password, $email);

        if (is_wp_error($user_id)) {
            error_log('BIMVerdi: Failed to create user: ' . $user_id->get_error_message());
            wp_redirect(add_query_arg('bv_error', 'system', $error_redirect));
            exit;
        }

        // Parse full name into first/last
        $name_parts = explode(' ', $full_name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';

        // Set user meta
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);

        // Set display name
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $full_name,
        ));

        // Set role and account type
        // Note: wp_create_user triggers 'user_register' hook, which may have already
        // linked this user to a company via invitation (link_invited_user).
        // Only set subscriber/profil defaults if no company was linked.
        $user = new WP_User($user_id);
        $user_company = get_user_meta($user_id, 'bim_verdi_company_id', true);
        if (empty($user_company)) {
            $user_company = get_user_meta($user_id, 'bimverdi_company_id', true);
        }

        if (empty($user_company)) {
            $user->set_role('subscriber');
            update_user_meta($user_id, 'bimverdi_account_type', 'profil');
            update_user_meta($user_id, 'bimverdi_company_id', '');
        } else {
            update_user_meta($user_id, 'bimverdi_account_type', 'foretak');
        }
        update_user_meta($user_id, 'bimverdi_registered_at', current_time('mysql'));

        // Mark pending registration as verified
        $this->mark_as_verified($token_check['pending']->id);

        // Auto-login the user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, false, is_ssl());
        do_action('wp_login', $email, $user);

        error_log('BIMVerdi: User created and logged in: ' . $user_id . ' (' . $email . ')');

        // Redirect to profile edit (so new users complete their profile)
        wp_redirect(add_query_arg('welcome', '1', home_url('/min-side/profil/rediger/')));
        exit;
    }

    // =========================================================================
    // Database Methods (unchanged)
    // =========================================================================

    /**
     * Create database table for pending registrations
     */
    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(100) NOT NULL,
            token varchar(64) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            status varchar(20) DEFAULT 'pending',
            ip_address varchar(45) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY email (email),
            KEY status (status),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('bimverdi_email_verification_db_version', '1.0.0');
    }

    /**
     * Check and create table if needed (for mu-plugins)
     */
    public function maybe_create_table() {
        if (get_option('bimverdi_email_verification_db_version') !== '1.0.0') {
            $this->create_table();
        }
    }

    // =========================================================================
    // Token Methods (unchanged)
    // =========================================================================

    /**
     * Generate secure token
     *
     * @return string UUID-style token
     */
    private function generate_token() {
        return wp_generate_uuid4();
    }

    /**
     * Create pending registration
     *
     * @param string $email User email
     * @return array|false Token data or false on failure
     */
    public function create_pending_registration($email) {
        global $wpdb;

        $email = sanitize_email($email);

        // Check if email already has pending registration
        $existing = $this->get_pending_by_email($email);
        if ($existing && $existing->status === 'pending') {
            $this->delete_pending($existing->id);
        }

        $token = $this->generate_token();
        $expires_at = date('Y-m-d H:i:s', time() + self::TOKEN_EXPIRY);

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'email' => $email,
                'token' => $token,
                'expires_at' => $expires_at,
                'status' => 'pending',
                'ip_address' => $this->get_client_ip(),
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            error_log('BIMVerdi Email Verification: Failed to create pending registration for ' . $email);
            return false;
        }

        return array(
            'id' => $wpdb->insert_id,
            'email' => $email,
            'token' => $token,
            'expires_at' => $expires_at,
        );
    }

    /**
     * Get pending registration by token
     *
     * @param string $token
     * @return object|null
     */
    public function get_pending_by_token($token) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE token = %s",
                $token
            )
        );
    }

    /**
     * Get pending registration by email
     *
     * @param string $email
     * @return object|null
     */
    public function get_pending_by_email($email) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE email = %s ORDER BY created_at DESC LIMIT 1",
                sanitize_email($email)
            )
        );
    }

    /**
     * Verify token is valid
     *
     * @param string $token
     * @param string $email
     * @return array Status array with 'valid' boolean and 'message'
     */
    public function verify_token($token, $email) {
        $pending = $this->get_pending_by_token($token);

        if (!$pending) {
            return array(
                'valid' => false,
                'message' => 'Ugyldig verifiseringslenke. Vennligst registrer deg på nytt.',
                'code' => 'invalid_token'
            );
        }

        if ($pending->email !== sanitize_email($email)) {
            return array(
                'valid' => false,
                'message' => 'E-postadressen stemmer ikke med verifiseringslenken.',
                'code' => 'email_mismatch'
            );
        }

        if ($pending->status !== 'pending') {
            return array(
                'valid' => false,
                'message' => 'Denne lenken er allerede brukt. Vennligst logg inn eller registrer deg på nytt.',
                'code' => 'already_used'
            );
        }

        if (strtotime($pending->expires_at) < time()) {
            return array(
                'valid' => false,
                'message' => 'Verifiseringslenken har utløpt. Vennligst registrer deg på nytt.',
                'code' => 'expired'
            );
        }

        return array(
            'valid' => true,
            'message' => 'Token er gyldig',
            'code' => 'valid',
            'pending' => $pending
        );
    }

    /**
     * Mark pending registration as verified
     *
     * @param int $id Pending registration ID
     * @return bool
     */
    public function mark_as_verified($id) {
        global $wpdb;

        return $wpdb->update(
            $this->table_name,
            array('status' => 'verified'),
            array('id' => $id),
            array('%s'),
            array('%d')
        ) !== false;
    }

    /**
     * Delete pending registration
     *
     * @param int $id
     * @return bool
     */
    public function delete_pending($id) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        ) !== false;
    }

    /**
     * Cleanup expired tokens
     */
    public function cleanup_expired_tokens() {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$this->table_name} WHERE expires_at < NOW() OR status = 'verified'"
        );
    }

    // =========================================================================
    // Email Methods (unchanged)
    // =========================================================================

    /**
     * Get client IP address
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Send verification email
     *
     * @param string $email
     * @param string $token
     * @return bool
     */
    public function send_verification_email($email, $token) {
        $verification_url = add_query_arg(
            array(
                'email' => urlencode($email),
                'token' => $token,
            ),
            home_url('/aktiver-konto/')
        );

        $subject = 'Aktiver din BIM Verdi-konto';

        $message = $this->get_verification_email_html($email, $verification_url);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            // From-header settes av bimverdi-resend-mail.php
        );

        return wp_mail($email, $subject, $message, $headers);
    }

    /**
     * Get verification email HTML
     *
     * @param string $email
     * @param string $verification_url
     * @return string
     */
    private function get_verification_email_html($email, $verification_url) {
        ob_start();
        ?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bekreft e-postadressen din</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #F5F3EE; color: #1A1A1A;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F5F3EE;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 520px; margin: 0 auto;">

                    <!-- Logo -->
                    <tr>
                        <td style="text-align: center; padding-bottom: 32px;">
                            <span style="font-size: 20px; font-weight: 700; color: #1A1A1A;">BIM Verdi</span>
                        </td>
                    </tr>

                    <!-- Main Card -->
                    <tr>
                        <td>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
                                <tr>
                                    <td style="padding: 40px;">

                                        <!-- Greeting -->
                                        <p style="margin: 0 0 24px 0; color: #1A1A1A; font-size: 16px; line-height: 1.6;">
                                            Hei!
                                        </p>

                                        <p style="margin: 0 0 24px 0; color: #1A1A1A; font-size: 16px; line-height: 1.6;">
                                            Takk for at du registrerte deg på BIM Verdi. For å fullføre registreringen,
                                            trenger vi at du bekrefter e-postadressen din.
                                        </p>

                                        <!-- CTA Button -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 32px 0;">
                                            <tr>
                                                <td align="center">
                                                    <a href="<?php echo esc_url($verification_url); ?>"
                                                       style="display: inline-block; background-color: #1A1A1A; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 16px; font-weight: 500;">
                                                        Bekreft e-postadressen
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                        <p style="margin: 0 0 16px 0; color: #6B6B6B; font-size: 14px; line-height: 1.6;">
                                            Lenken er gyldig i 24 timer. Etter at du har bekreftet, kan du fullføre
                                            registreringen ved å velge et passord.
                                        </p>

                                        <!-- Separator -->
                                        <hr style="border: none; border-top: 1px solid #E8E8E8; margin: 24px 0;">

                                        <!-- What you get -->
                                        <p style="margin: 0 0 12px 0; color: #1A1A1A; font-size: 14px; font-weight: 600;">
                                            Med en BIM Verdi-konto kan du:
                                        </p>
                                        <ul style="margin: 0 0 16px 0; padding-left: 20px; color: #6B6B6B; font-size: 14px; line-height: 1.8;">
                                            <li>Melde deg på arrangementer og workshops</li>
                                            <li>Få tilgang til deltakerportalen</li>
                                            <li>Registrere BIM-verktøy (når koblet til foretak)</li>
                                            <li>Skrive og dele artikler</li>
                                        </ul>

                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 0; text-align: center;">
                            <p style="margin: 0 0 16px 0; color: #9B9B9B; font-size: 12px;">
                                Fungerer ikke knappen?
                                <a href="<?php echo esc_url($verification_url); ?>" style="color: #6B6B6B;">Klikk her</a>
                            </p>
                            <p style="margin: 0; color: #9B9B9B; font-size: 11px;">
                                Du mottar denne e-posten fordi <?php echo esc_html($email); ?> ble brukt til å registrere seg på BIM Verdi.<br>
                                Hvis dette ikke var deg, kan du trygt ignorere denne e-posten.
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
     * Send "already registered" email to prevent user enumeration.
     * Shows no error to the user — they just get a helpful email instead.
     */
    private function send_already_registered_email($email) {
        $login_url = home_url('/logg-inn/');
        $reset_url = home_url('/glemt-passord/');

        $subject = 'Du har allerede en BIM Verdi-konto';

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Du har allerede en konto</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #F5F3EE; color: #1A1A1A;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F5F3EE;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 520px; margin: 0 auto;">

                    <!-- Logo -->
                    <tr>
                        <td style="text-align: center; padding-bottom: 32px;">
                            <span style="font-size: 20px; font-weight: 700; color: #1A1A1A;">BIM Verdi</span>
                        </td>
                    </tr>

                    <!-- Main Card -->
                    <tr>
                        <td>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
                                <tr>
                                    <td style="padding: 40px;">

                                        <p style="margin: 0 0 24px 0; color: #1A1A1A; font-size: 16px; line-height: 1.6;">
                                            Hei!
                                        </p>

                                        <p style="margin: 0 0 24px 0; color: #1A1A1A; font-size: 16px; line-height: 1.6;">
                                            Noen prøvde å registrere en konto med denne e-postadressen, men du har allerede en konto hos BIM Verdi.
                                        </p>

                                        <!-- CTA Button -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 32px 0;">
                                            <tr>
                                                <td align="center">
                                                    <a href="<?php echo esc_url($login_url); ?>"
                                                       style="display: inline-block; background-color: #1A1A1A; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 16px; font-weight: 500;">
                                                        Logg inn
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                        <p style="margin: 0 0 16px 0; color: #6B6B6B; font-size: 14px; line-height: 1.6;">
                                            Husker du ikke passordet?
                                            <a href="<?php echo esc_url($reset_url); ?>" style="color: #1A1A1A; font-weight: 500;">Tilbakestill passordet</a>
                                        </p>

                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 0; text-align: center;">
                            <p style="margin: 0; color: #9B9B9B; font-size: 11px;">
                                Du mottar denne e-posten fordi <?php echo esc_html($email); ?> ble brukt til å forsøke registrering på BIM Verdi.<br>
                                Hvis dette ikke var deg, kan du trygt ignorere denne e-posten.
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
        $message = ob_get_clean();

        $headers = array('Content-Type: text/html; charset=UTF-8');
        return wp_mail($email, $subject, $message, $headers);
    }
}

// Initialize the system
add_action('plugins_loaded', function() {
    new BIMVerdi_Email_Verification();
});

// NOTE: Helper functions bimverdi_user_has_company(), bimverdi_get_user_company(),
// and bimverdi_get_account_type() are now defined in bimverdi-access-control.php
