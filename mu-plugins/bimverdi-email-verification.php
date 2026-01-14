<?php
/**
 * BIM Verdi Email Verification System
 * 
 * Håndterer to-stegs brukerregistrering:
 * 1. Email-only signup → sender verifiserings-epost
 * 2. Verifiseringsside → fullfør med navn + passord
 * 
 * @package BIMVerdi
 * @version 1.0.0
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
     * Form IDs - konfigurer via Gravity Forms > Email Verification innstillinger
     * Eller sett options: bimverdi_email_form_id og bimverdi_verify_form_id
     */
    private $email_form_id;
    private $verify_form_id;
    
    /**
     * Constructor - initialize hooks
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'bimverdi_pending_registrations';
        
        // Get form IDs from options (default to 5 and 6)
        $this->email_form_id = (int) get_option('bimverdi_email_form_id', 5);
        $this->verify_form_id = (int) get_option('bimverdi_verify_form_id', 6);
        
        // Database setup
        register_activation_hook(__FILE__, array($this, 'create_table'));
        add_action('admin_init', array($this, 'maybe_create_table'));
        
        // Form handlers - use gform_after_submission with form check
        add_action('gform_after_submission', array($this, 'handle_form_submission'), 10, 2);
        
        // Form validation
        add_filter('gform_validation', array($this, 'validate_forms'));
        
        // Pre-populate verification form
        add_filter('gform_pre_render', array($this, 'prepopulate_verification_form'));
        
        // Custom confirmation for email form
        add_filter('gform_confirmation', array($this, 'email_form_confirmation'), 10, 4);
        
        // Cleanup expired tokens (daily cron)
        add_action('bimverdi_cleanup_expired_tokens', array($this, 'cleanup_expired_tokens'));
        if (!wp_next_scheduled('bimverdi_cleanup_expired_tokens')) {
            wp_schedule_event(time(), 'daily', 'bimverdi_cleanup_expired_tokens');
        }
        
        // Admin settings page
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add settings page under Gravity Forms menu
     */
    public function add_settings_page() {
        // Use options-general.php as parent for reliability
        add_options_page(
            'BIM Verdi Email Verification',
            'Email Verification',
            'manage_options',
            'bimverdi-email-verification',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('bimverdi_email_verification', 'bimverdi_email_form_id', 'intval');
        register_setting('bimverdi_email_verification', 'bimverdi_verify_form_id', 'intval');
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>BIM Verdi Email Verification Settings</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('bimverdi_email_verification'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Email Signup Form ID</th>
                        <td>
                            <input type="number" name="bimverdi_email_form_id" value="<?php echo esc_attr($this->email_form_id); ?>" class="small-text">
                            <p class="description">Form for steg 1 - kun e-postadresse (standard: 5)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Verify Account Form ID</th>
                        <td>
                            <input type="number" name="bimverdi_verify_form_id" value="<?php echo esc_attr($this->verify_form_id); ?>" class="small-text">
                            <p class="description">Form for steg 2 - navn og passord (standard: 6)</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <hr>
            
            <h2>Opprett skjemaer automatisk</h2>
            <p>Klikk knappen under for å opprette de nødvendige Gravity Forms skjemaene:</p>
            <a href="<?php echo admin_url('?bimverdi_setup_forms=1'); ?>" class="button button-primary">
                Opprett Email Verification Forms
            </a>
            <p class="description" style="margin-top: 10px;">
                <strong>Merk:</strong> Etter opprettelse, noter ned Form ID-ene og oppdater feltene over.
            </p>
        </div>
        <?php
    }
    
    /**
     * Handle form submission - routes to correct handler based on form ID
     */
    public function handle_form_submission($entry, $form) {
        if ($form['id'] == $this->email_form_id) {
            $this->handle_email_signup($entry, $form);
        } elseif ($form['id'] == $this->verify_form_id) {
            $this->handle_verification($entry, $form);
        }
    }
    
    /**
     * Validate forms - routes to correct validator based on form ID
     */
    public function validate_forms($validation_result) {
        $form = $validation_result['form'];
        
        if ($form['id'] == $this->email_form_id) {
            return $this->validate_email_signup($validation_result);
        } elseif ($form['id'] == $this->verify_form_id) {
            return $this->validate_verification($validation_result);
        }
        
        return $validation_result;
    }
    
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
        
        // Store version for future migrations
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
            // Delete old one and create new
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
                // Handle comma-separated IPs (X-Forwarded-For)
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
     * Validate email signup form
     * 
     * @param array $validation_result
     * @return array
     */
    public function validate_email_signup($validation_result) {
        $form = $validation_result['form'];
        $email = sanitize_email(rgpost('input_1')); // Field 1: Email
        
        // Check if email already exists as WordPress user
        if (email_exists($email)) {
            $validation_result['is_valid'] = false;
            
            foreach ($form['fields'] as &$field) {
                if ($field->id == 1) {
                    $field->failed_validation = true;
                    $field->validation_message = 'Denne e-postadressen er allerede registrert. <a href="' . wp_login_url() . '">Logg inn her</a>';
                }
            }
        }
        
        $validation_result['form'] = $form;
        return $validation_result;
    }
    
    /**
     * Handle email signup form submission
     * 
     * @param array $entry
     * @param array $form
     */
    public function handle_email_signup($entry, $form) {
        $email = sanitize_email(rgar($entry, '1')); // Field 1: Email
        
        // Create pending registration
        $pending = $this->create_pending_registration($email);
        
        if (!$pending) {
            error_log('BIMVerdi: Failed to create pending registration for ' . $email);
            return;
        }
        
        // Send verification email
        $this->send_verification_email($email, $pending['token']);
        
        // Store entry meta for reference
        gform_update_meta($entry['id'], 'verification_token', $pending['token']);
        gform_update_meta($entry['id'], 'verification_sent', current_time('mysql'));
        
        error_log('BIMVerdi: Verification email sent to ' . $email);
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
        
        $subject = 'Aktiver din BIM Verdi-konto 🚀';
        
        $message = $this->get_verification_email_html($email, $verification_url);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: BIM Verdi <noreply@bimverdi.no>',
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
            <title>Aktiver din BIM Verdi-konto</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #FDF6E3; color: #1F2937;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #FDF6E3;">
                <tr>
                    <td style="padding: 40px 20px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                            
                            <!-- Header -->
                            <tr>
                                <td style="background: linear-gradient(135deg, #F97316 0%, #EA580C 100%); padding: 40px 30px; text-align: center;">
                                    <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">
                                        🏗️ BIM Verdi
                                    </h1>
                                </td>
                            </tr>
                            
                            <!-- Content -->
                            <tr>
                                <td style="padding: 40px 30px;">
                                    
                                    <!-- Icon -->
                                    <div style="text-align: center; margin-bottom: 30px;">
                                        <div style="display: inline-block; background-color: #FEF3C7; border-radius: 50%; padding: 20px;">
                                            <span style="font-size: 48px;">✉️</span>
                                        </div>
                                    </div>
                                    
                                    <h2 style="margin: 0 0 20px 0; color: #1F2937; font-size: 24px; text-align: center;">
                                        Din BIM Verdi-konto er nesten klar!
                                    </h2>
                                    
                                    <p style="margin: 0 0 20px 0; color: #4B5563; font-size: 16px; line-height: 1.6; text-align: center;">
                                        Du er på vei til å bli en del av Norges ledende BIM-nettverk! 
                                        Vi trenger bare å <strong>bekrefte e-postadressen din</strong> for å aktivere kontoen.
                                    </p>
                                    
                                    <!-- CTA Button -->
                                    <div style="text-align: center; margin: 30px 0;">
                                        <a href="<?php echo esc_url($verification_url); ?>" 
                                           style="display: inline-block; background-color: #F97316; color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-size: 18px; font-weight: 600; transition: background-color 0.2s;">
                                            Aktiver kontoen min →
                                        </a>
                                    </div>
                                    
                                    <p style="margin: 30px 0 0 0; color: #9CA3AF; font-size: 14px; text-align: center;">
                                        Lenken er gyldig i 24 timer.
                                    </p>
                                    
                                    <hr style="border: none; border-top: 1px solid #E5E7EB; margin: 30px 0;">
                                    
                                    <!-- Why account section -->
                                    <h3 style="margin: 0 0 15px 0; color: #1F2937; font-size: 16px;">
                                        Hvorfor trenger jeg en konto?
                                    </h3>
                                    
                                    <p style="margin: 0 0 20px 0; color: #6B7280; font-size: 14px; line-height: 1.6;">
                                        Med en BIM Verdi-konto kan du delta i nettverkets aktiviteter, 
                                        melde deg på arrangementer og få tilgang til medlemsportalen. 
                                        Når du kobler kontoen til et foretak, får du også tilgang til verktøyregistrering, 
                                        artikkelskriving og mer.
                                    </p>
                                    
                                    <!-- Fallback link -->
                                    <p style="margin: 20px 0 0 0; color: #9CA3AF; font-size: 12px; text-align: center;">
                                        Fungerer ikke knappen? Kopier denne lenken til nettleseren:<br>
                                        <a href="<?php echo esc_url($verification_url); ?>" style="color: #F97316; word-break: break-all;">
                                            <?php echo esc_html($verification_url); ?>
                                        </a>
                                    </p>
                                    
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #F9FAFB; padding: 20px 30px; text-align: center;">
                                    <p style="margin: 0; color: #9CA3AF; font-size: 12px;">
                                        © <?php echo date('Y'); ?> BIM Verdi. Alle rettigheter forbeholdt.
                                    </p>
                                    <p style="margin: 10px 0 0 0; color: #9CA3AF; font-size: 12px;">
                                        Du mottar denne e-posten fordi noen registrerte seg med <?php echo esc_html($email); ?> på BIM Verdi.<br>
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
     * Custom confirmation for email form
     * Shows success message with locked email field
     * 
     * @param mixed $confirmation
     * @param array $form
     * @param array $entry
     * @param bool $ajax
     * @return string
     */
    public function email_form_confirmation($confirmation, $form, $entry, $ajax) {
        // Only apply to email signup form
        if ($form['id'] != $this->email_form_id) {
            return $confirmation;
        }
        
        $email = sanitize_email(rgar($entry, '1'));
        
        ob_start();
        ?>
        <div class="bimverdi-signup-confirmation">
            <!-- Success Alert -->
            <wa-alert variant="success" open class="mb-6">
                <wa-icon slot="icon" name="check-circle" library="fa"></wa-icon>
                <strong>Flott!</strong> Vi har sendt deg en e-post med en verifiseringslenke. 
                Åpne e-posten og klikk på lenken for å fullføre registreringen.
            </wa-alert>
            
            <!-- Locked email display -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">E-postadresse</label>
                <div class="flex items-center gap-3 p-4 bg-gray-100 rounded-lg border border-gray-200">
                    <wa-icon name="envelope" library="fa" class="text-gray-500"></wa-icon>
                    <span class="text-gray-700 font-medium"><?php echo esc_html($email); ?></span>
                    <wa-icon name="lock" library="fa" class="text-gray-400 ml-auto"></wa-icon>
                </div>
            </div>
            
            <!-- Help text -->
            <div class="mt-6 p-4 bg-amber-50 rounded-lg border border-amber-200">
                <div class="flex items-start gap-3">
                    <wa-icon name="lightbulb" library="fa" class="text-amber-600 mt-0.5"></wa-icon>
                    <div class="text-sm text-amber-800">
                        <p class="font-medium mb-1">Finner du ikke e-posten?</p>
                        <ul class="list-disc list-inside space-y-1 text-amber-700">
                            <li>Sjekk søppelpost/spam-mappen</li>
                            <li>Vent noen minutter og prøv igjen</li>
                            <li>Kontakt oss hvis problemet vedvarer</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Resend link -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Ikke mottatt e-post? 
                    <a href="<?php echo esc_url(home_url('/registrer/')); ?>" class="text-orange-600 hover:text-orange-700 font-medium">
                        Prøv igjen
                    </a>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Pre-populate verification form with email and token from URL
     * 
     * @param array $form
     * @return array
     */
    public function prepopulate_verification_form($form) {
        // Only apply to verification form
        if ($form['id'] != $this->verify_form_id) {
            return $form;
        }
        
        // Get email and token from URL
        $email = isset($_GET['email']) ? sanitize_email(urldecode($_GET['email'])) : '';
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
        
        foreach ($form['fields'] as &$field) {
            // Field 1: Email (hidden, pre-populated)
            if ($field->id == 1) {
                $field->defaultValue = $email;
            }
            // Field 4: Token (hidden, pre-populated)
            if ($field->id == 4) {
                $field->defaultValue = $token;
            }
        }
        
        return $form;
    }
    
    /**
     * Validate verification form
     * 
     * @param array $validation_result
     * @return array
     */
    public function validate_verification($validation_result) {
        $form = $validation_result['form'];
        
        $email = sanitize_email(rgpost('input_1')); // Field 1: Email (hidden)
        $token = sanitize_text_field(rgpost('input_4')); // Field 4: Token (hidden)
        $password = rgpost('input_3'); // Field 3: Password
        
        // Verify token
        $token_check = $this->verify_token($token, $email);
        
        if (!$token_check['valid']) {
            $validation_result['is_valid'] = false;
            
            // Add error to a visible field
            foreach ($form['fields'] as &$field) {
                if ($field->id == 2) { // Name field
                    $field->failed_validation = true;
                    $field->validation_message = $token_check['message'];
                    break;
                }
            }
        }
        
        // Check password strength
        if (strlen($password) < 8) {
            $validation_result['is_valid'] = false;
            
            foreach ($form['fields'] as &$field) {
                if ($field->id == 3) {
                    $field->failed_validation = true;
                    $field->validation_message = 'Passord må være minst 8 tegn.';
                }
            }
        }
        
        $validation_result['form'] = $form;
        return $validation_result;
    }
    
    /**
     * Handle verification form submission - create user
     * 
     * @param array $entry
     * @param array $form
     */
    public function handle_verification($entry, $form) {
        $email = sanitize_email(rgar($entry, '1')); // Field 1: Email
        $full_name = sanitize_text_field(rgar($entry, '2')); // Field 2: Full name
        $password = rgar($entry, '3'); // Field 3: Password
        $token = sanitize_text_field(rgar($entry, '4')); // Field 4: Token
        
        // Verify token one more time
        $token_check = $this->verify_token($token, $email);
        
        if (!$token_check['valid']) {
            error_log('BIMVerdi: Token verification failed during submission: ' . $token_check['code']);
            return;
        }
        
        // Parse full name into first/last
        $name_parts = explode(' ', $full_name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
        
        // Create WordPress user
        $user_id = wp_create_user($email, $password, $email);
        
        if (is_wp_error($user_id)) {
            error_log('BIMVerdi: Failed to create user: ' . $user_id->get_error_message());
            return;
        }
        
        // Set user meta
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        
        // Set display name
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $full_name,
        ));
        
        // Set role (profil_bruker - no company attached)
        $user = new WP_User($user_id);
        $user->set_role('subscriber'); // Will upgrade when company is linked
        
        // Mark as verified user without company
        update_user_meta($user_id, 'bimverdi_account_type', 'profil');
        update_user_meta($user_id, 'bimverdi_company_id', ''); // Empty = no company
        update_user_meta($user_id, 'bimverdi_registered_at', current_time('mysql'));
        
        // Mark pending registration as verified
        $this->mark_as_verified($token_check['pending']->id);
        
        // Auto-login the user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, false, is_ssl());
        do_action('wp_login', $email, $user);
        
        // Store reference in entry
        gform_update_meta($entry['id'], 'created_user_id', $user_id);
        
        // SECURITY: Delete the form entry to prevent password storage
        // The entry contains password in clear text - must be deleted
        if (class_exists('GFAPI')) {
            GFAPI::delete_entry($entry['id']);
            error_log('BIMVerdi: Entry deleted for security (contained password)');
        }
        
        error_log('BIMVerdi: User created and logged in: ' . $user_id . ' (' . $email . ')');
    }
}

// Initialize the system
add_action('plugins_loaded', function() {
    new BIMVerdi_Email_Verification();
});

// NOTE: Helper functions bimverdi_user_has_company(), bimverdi_get_user_company(), 
// and bimverdi_get_account_type() are now defined in bimverdi-access-control.php
