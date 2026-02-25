<?php
/**
 * BIM Verdi Company Invitations System
 * 
 * Håndterer invitasjon av kolleger til et foretak:
 * - Hovedkontakt sender invitasjon via e-post
 * - Invitert bruker klikker lenke → sendes direkte til /aktiver-konto/ (navn + passord)
 * - Bruker blir automatisk koblet til foretaket med tilleggskontakt rolle
 * 
 * @package BIMVerdi
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main class for company invitations system
 */
class BIMVerdi_Company_Invitations {
    
    /** @var string Database table name */
    private $table_name;
    
    /** @var int Token expiry in seconds (7 days) */
    const TOKEN_EXPIRY = 604800;
    
    /** @var int Maximum invitations per company (default) */
    const DEFAULT_MAX_INVITATIONS = 10;
    
    /**
     * Constructor - initialize hooks
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'bimverdi_invitations';
        
        // Database setup
        add_action('admin_init', array($this, 'maybe_create_table'));
        add_action('init', array($this, 'maybe_create_table'));
        
        // Handle invitation acceptance (query var)
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_invitation_link'));
        
        // Shortcode for accepting invitations
        add_shortcode('bimverdi_accept_invitation', array($this, 'shortcode_accept_invitation'));
        
        // Hook into user registration to auto-link invited users
        add_action('user_register', array($this, 'link_invited_user'), 10, 1);
        
        // AJAX handlers for sending invitations
        add_action('wp_ajax_bimverdi_send_invitation', array($this, 'ajax_send_invitation'));
        add_action('wp_ajax_bimverdi_revoke_invitation', array($this, 'ajax_revoke_invitation'));
        add_action('wp_ajax_bimverdi_remove_user_access', array($this, 'ajax_remove_user_access'));
        
        // Cleanup expired invitations (daily cron)
        add_action('bimverdi_cleanup_expired_invitations', array($this, 'cleanup_expired_invitations'));
        if (!wp_next_scheduled('bimverdi_cleanup_expired_invitations')) {
            wp_schedule_event(time(), 'daily', 'bimverdi_cleanup_expired_invitations');
        }
        
        // Admin notification for new invitations
        add_action('bimverdi_invitation_sent', array($this, 'notify_admin_invitation_sent'), 10, 3);
    }
    
    /**
     * Add rewrite rules for invitation links
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^aksepter-invitasjon/?$',
            'index.php?bimverdi_invitation_page=1',
            'top'
        );
    }
    
    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'bimverdi_invitation_page';
        $vars[] = 'invitation_token';
        return $vars;
    }
    
    /**
     * Create database table for invitations
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(100) NOT NULL,
            token varchar(64) NOT NULL,
            company_id bigint(20) unsigned NOT NULL,
            invited_by bigint(20) unsigned NOT NULL,
            role varchar(50) DEFAULT 'company_user',
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            used_at datetime DEFAULT NULL,
            used_by_user_id bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY email (email),
            KEY company_id (company_id),
            KEY status (status),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Store version for future migrations
        update_option('bimverdi_invitations_db_version', '1.0.0');
    }
    
    /**
     * Check and create table if needed
     */
    public function maybe_create_table() {
        if (get_option('bimverdi_invitations_db_version') !== '1.0.0') {
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
     * Get client IP address
     * 
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key]);
                return trim($ip[0]);
            }
        }
        return 'unknown';
    }
    
    /**
     * Check if user can send invitations for a company
     * 
     * @param int $user_id
     * @param int $company_id
     * @return bool
     */
    public function can_send_invitations($user_id, $company_id) {
        // Check if user is hovedkontakt for this company
        $hovedkontakt_id = get_field('hovedkontaktperson', $company_id);
        if ($hovedkontakt_id != $user_id) {
            return false;
        }
        
        // Check if company is active (any role except 'Ikke deltaker')
        $bv_rolle = get_field('bv_rolle', $company_id);
        if (!$bv_rolle || $bv_rolle === 'Ikke deltaker') {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get remaining invitations count for a company
     * 
     * @param int $company_id
     * @return int
     */
    public function get_remaining_invitations($company_id) {
        $max_allowed = (int) get_field('antall_invitasjoner_tillatt', $company_id);
        if ($max_allowed <= 0) {
            $max_allowed = self::DEFAULT_MAX_INVITATIONS;
        }
        
        $used = $this->count_used_invitations($company_id);
        
        return max(0, $max_allowed - $used);
    }
    
    /**
     * Count used invitations (pending + accepted)
     * 
     * @param int $company_id
     * @return int
     */
    public function count_used_invitations($company_id) {
        global $wpdb;
        
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} 
                WHERE company_id = %d 
                AND status IN ('pending', 'accepted')
                AND (expires_at > NOW() OR status = 'accepted')",
                $company_id
            )
        );
    }
    
    /**
     * Get all invitations for a company
     * 
     * @param int $company_id
     * @return array
     */
    public function get_company_invitations($company_id) {
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE company_id = %d 
                ORDER BY created_at DESC",
                $company_id
            )
        );
    }
    
    /**
     * Get pending invitations for a company
     * 
     * @param int $company_id
     * @return array
     */
    public function get_pending_invitations($company_id) {
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE company_id = %d 
                AND status = 'pending'
                AND expires_at > NOW()
                ORDER BY created_at DESC",
                $company_id
            )
        );
    }
    
    /**
     * Get all users linked to a company
     * 
     * @param int $company_id
     * @return array
     */
    public function get_company_users($company_id) {
        // Query users with either the new or legacy meta key
        $users = get_users(array(
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'bimverdi_company_id',
                    'value' => $company_id,
                ),
                array(
                    'key' => 'bim_verdi_company_id',
                    'value' => $company_id,
                ),
            ),
        ));

        // Add role info
        $hovedkontakt_id = get_field('hovedkontaktperson', $company_id);
        foreach ($users as &$user) {
            $user->is_hovedkontakt = ($user->ID == $hovedkontakt_id);
        }

        return $users;
    }
    
    /**
     * Send invitation
     * 
     * @param string $email
     * @param int $company_id
     * @param int $invited_by User ID of inviter
     * @param string $role Default role for invited user
     * @return array|WP_Error
     */
    public function send_invitation($email, $company_id, $invited_by, $role = 'tilleggskontakt') {
        global $wpdb;
        
        $email = sanitize_email($email);
        
        // Validate email
        if (!is_email($email)) {
            return new WP_Error('invalid_email', 'Ugyldig e-postadresse');
        }
        
        // Check if inviter can send invitations
        if (!$this->can_send_invitations($invited_by, $company_id)) {
            return new WP_Error('not_authorized', 'Du har ikke tillatelse til å sende invitasjoner for dette foretaket');
        }
        
        // Check remaining invitations
        if ($this->get_remaining_invitations($company_id) <= 0) {
            $max_allowed = (int) get_field('antall_invitasjoner_tillatt', $company_id);
            if ($max_allowed <= 0) {
                $max_allowed = self::DEFAULT_MAX_INVITATIONS;
            }
            return new WP_Error('limit_reached', sprintf(
                'Maksimalt antall invitasjoner er nådd (maks %d tilleggskontakter)',
                $max_allowed
            ));
        }
        
        // Check if email already has pending invitation for this company
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE email = %s AND company_id = %d AND status = 'pending' AND expires_at > NOW()",
                $email, $company_id
            )
        );
        
        if ($existing) {
            return new WP_Error('already_invited', 'Denne e-postadressen har allerede en ventende invitasjon');
        }
        
        // Check if user with this email is already linked to company
        $existing_user = get_user_by('email', $email);
        if ($existing_user) {
            $user_company_id = get_user_meta($existing_user->ID, 'bim_verdi_company_id', true);
            if ($user_company_id == $company_id) {
                return new WP_Error('already_linked', 'Denne brukeren er allerede koblet til foretaket');
            }
        }
        
        // Create invitation
        $token = $this->generate_token();
        $expires_at = date('Y-m-d H:i:s', time() + self::TOKEN_EXPIRY);
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'email' => $email,
                'token' => $token,
                'company_id' => $company_id,
                'invited_by' => $invited_by,
                'role' => $role,
                'status' => 'pending',
                'expires_at' => $expires_at,
            ),
            array('%s', '%s', '%d', '%d', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Kunne ikke opprette invitasjon');
        }
        
        $invitation_id = $wpdb->insert_id;
        
        // Send invitation email
        $email_sent = $this->send_invitation_email($email, $token, $company_id, $invited_by);
        
        if (!$email_sent) {
            // Log error but don't fail - invitation is created
            error_log('BIMVerdi Invitations: Failed to send email to ' . $email);
        }
        
        // Trigger action for admin notification
        do_action('bimverdi_invitation_sent', $invitation_id, $email, $company_id);
        
        return array(
            'id' => $invitation_id,
            'email' => $email,
            'token' => $token,
            'company_id' => $company_id,
            'expires_at' => $expires_at,
        );
    }
    
    /**
     * Send invitation email
     * 
     * @param string $email
     * @param string $token
     * @param int $company_id
     * @param int $invited_by
     * @return bool
     */
    private function send_invitation_email($email, $token, $company_id, $invited_by) {
        $company = get_post($company_id);
        $inviter = get_userdata($invited_by);
        
        if (!$company || !$inviter) {
            return false;
        }
        
        $invitation_url = add_query_arg(
            array('invitation_token' => $token),
            home_url('/aksepter-invitasjon/')
        );
        
        $subject = sprintf('Invitasjon til %s i BIM Verdi', $company->post_title);
        
        $message = sprintf(
            'Hei!

%s har invitert deg til å bli koblet til %s i BIM Verdi-portalen.

Klikk på lenken under for å akseptere invitasjonen:

%s

Denne lenken er gyldig i 7 dager.

Hvis du ikke kjenner til denne invitasjonen, kan du ignorere denne e-posten.

Med vennlig hilsen,
BIM Verdi',
            $inviter->display_name,
            $company->post_title,
            $invitation_url
        );
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: BIM Verdi <noreply@bimverdi.no>',
        );
        
        return wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Get invitation by token
     * 
     * @param string $token
     * @return object|null
     */
    public function get_invitation_by_token($token) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE token = %s",
                $token
            )
        );
    }
    
    /**
     * Validate invitation token
     * 
     * @param string $token
     * @return object|WP_Error
     */
    public function validate_invitation($token) {
        $invitation = $this->get_invitation_by_token($token);
        
        if (!$invitation) {
            return new WP_Error('invalid_token', 'Ugyldig invitasjonslenke');
        }
        
        if ($invitation->status !== 'pending') {
            return new WP_Error('already_used', 'Denne invitasjonen er allerede brukt');
        }
        
        if (strtotime($invitation->expires_at) < time()) {
            return new WP_Error('expired', 'Denne invitasjonen har utløpt');
        }
        
        return $invitation;
    }
    
    /**
     * Accept invitation and link user to company
     * 
     * @param string $token
     * @param int $user_id
     * @return bool|WP_Error
     */
    public function accept_invitation($token, $user_id) {
        global $wpdb;
        
        $invitation = $this->validate_invitation($token);
        
        if (is_wp_error($invitation)) {
            return $invitation;
        }
        
        // Link user to company
        update_user_meta($user_id, 'bim_verdi_company_id', $invitation->company_id);
        
        // Set ACF field if exists
        if (function_exists('update_field')) {
            update_field('tilknyttet_foretak', $invitation->company_id, 'user_' . $user_id);
        }
        
        // Set user role (with fallback for legacy 'company_user' values)
        // Never downgrade administrators
        $user = new WP_User($user_id);
        if (!in_array('administrator', $user->roles)) {
            $role = $invitation->role;
            if ($role === 'company_user' || empty($role)) {
                $role = 'tilleggskontakt';
            }
            $user->set_role($role);
        }
        
        // Mark invitation as used
        $wpdb->update(
            $this->table_name,
            array(
                'status' => 'accepted',
                'used_at' => current_time('mysql'),
                'used_by_user_id' => $user_id,
            ),
            array('id' => $invitation->id),
            array('%s', '%s', '%d'),
            array('%d')
        );
        
        // Notify admin
        do_action('bimverdi_invitation_accepted', $invitation->id, $user_id, $invitation->company_id);
        
        return true;
    }
    
    /**
     * Handle invitation link (template redirect)
     */
    public function handle_invitation_link() {
        // Check for invitation token in URL
        $token = isset($_GET['invitation_token']) ? sanitize_text_field($_GET['invitation_token']) : '';
        
        if (empty($token)) {
            return;
        }
        
        // Validate token
        $invitation = $this->validate_invitation($token);
        
        if (is_wp_error($invitation)) {
            // Redirect to error page or show message
            wp_redirect(add_query_arg('invitation_error', $invitation->get_error_code(), home_url('/registrer/')));
            exit;
        }
        
        // Store token in session for use after registration
        if (!session_id()) {
            session_start();
        }
        $_SESSION['bimverdi_invitation_token'] = $token;
        $_SESSION['bimverdi_invitation_email'] = $invitation->email;
        $_SESSION['bimverdi_invitation_company'] = $invitation->company_id;
        
        // Check if user is already logged in
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            
            // Verify email matches (optional - could allow different email)
            // For now, just accept the invitation
            $result = $this->accept_invitation($token, $current_user->ID);
            
            if (is_wp_error($result)) {
                wp_redirect(add_query_arg('invitation_error', $result->get_error_code(), home_url('/min-side/')));
            } else {
                wp_redirect(add_query_arg('invitation_accepted', '1', home_url('/min-side/')));
            }
            exit;
        }
        
        // Check if email already has a WordPress account → redirect to login
        $existing_user = get_user_by('email', $invitation->email);
        if ($existing_user) {
            $login_redirect = add_query_arg(
                'invitation_token', $token,
                home_url('/aksepter-invitasjon/')
            );
            wp_redirect(add_query_arg(
                'redirect_to', urlencode($login_redirect),
                home_url('/logg-inn/')
            ));
            exit;
        }

        // Auto-create pending registration (skips email verification step)
        $verification = new BIMVerdi_Email_Verification();
        $pending = $verification->create_pending_registration($invitation->email);

        if ($pending) {
            // Redirect directly to /aktiver-konto/ (name + password form)
            wp_redirect(add_query_arg(
                array(
                    'email' => urlencode($invitation->email),
                    'token' => $pending['token'],
                ),
                home_url('/aktiver-konto/')
            ));
        } else {
            // Fallback: redirect to registration page
            $company = get_post($invitation->company_id);
            wp_redirect(add_query_arg(
                array(
                    'invitation' => '1',
                    'company' => urlencode($company->post_title),
                ),
                home_url('/registrer/')
            ));
        }
        exit;
    }
    
    /**
     * Shortcode to display invitation acceptance UI
     * 
     * @return string HTML output
     */
    public function shortcode_accept_invitation() {
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
        
        ob_start();
        ?>
        <div class="bimverdi-invitation-accept max-w-lg mx-auto py-8">
        <?php
        
        // No token provided
        if (empty($token)) {
            ?>
            <wa-alert variant="warning" open>
                <wa-icon slot="icon" name="exclamation-triangle" library="fa"></wa-icon>
                <strong>Ingen invitasjon funnet</strong><br>
                Denne siden krever en gyldig invitasjonslenke. Sjekk e-posten din for invitasjonen.
            </wa-alert>
            <?php
        } else {
            // Validate token
            $invitation = $this->validate_invitation($token);
            
            if (is_wp_error($invitation)) {
                $error_code = $invitation->get_error_code();
                $error_message = 'Invitasjonen er ikke gyldig.';
                
                if ($error_code === 'expired') {
                    $error_message = 'Denne invitasjonen har utløpt. Be hovedkontakten om å sende en ny invitasjon.';
                } elseif ($error_code === 'used') {
                    $error_message = 'Denne invitasjonen er allerede brukt.';
                } elseif ($error_code === 'not_found') {
                    $error_message = 'Invitasjonen ble ikke funnet. Den kan ha blitt trukket tilbake.';
                }
                ?>
                <wa-alert variant="danger" open>
                    <wa-icon slot="icon" name="circle-xmark" library="fa"></wa-icon>
                    <strong>Ugyldig invitasjon</strong><br>
                    <?php echo esc_html($error_message); ?>
                </wa-alert>
                <p class="mt-4 text-center">
                    <a href="<?php echo esc_url(home_url('/registrer/')); ?>" class="text-purple-600 hover:underline">
                        Registrer deg vanlig →
                    </a>
                </p>
                <?php
            } else {
                $company = get_post($invitation->company_id);
                $company_name = $company ? $company->post_title : 'Ukjent bedrift';
                
                // Check if user is logged in
                if (is_user_logged_in()) {
                    $current_user = wp_get_current_user();
                    ?>
                    <wa-card>
                        <div class="text-center space-y-4">
                            <wa-icon name="building" library="fa" style="font-size: 3rem; color: var(--wa-color-purple-600);"></wa-icon>
                            <h2 class="text-xl font-semibold">Invitasjon fra <?php echo esc_html($company_name); ?></h2>
                            <p class="text-gray-600">
                                Du er invitert til å bli en del av <strong><?php echo esc_html($company_name); ?></strong>.
                            </p>
                            <p class="text-gray-500 text-sm">
                                Logget inn som: <?php echo esc_html($current_user->user_email); ?>
                            </p>
                            <form method="post" class="space-y-4">
                                <?php wp_nonce_field('accept_invitation', 'invitation_nonce'); ?>
                                <input type="hidden" name="invitation_token" value="<?php echo esc_attr($token); ?>">
                                <input type="hidden" name="action" value="accept_invitation">
                                <wa-button variant="brand" type="submit" size="large">
                                    <wa-icon slot="prefix" name="check" library="fa"></wa-icon>
                                    Godta invitasjon
                                </wa-button>
                            </form>
                        </div>
                    </wa-card>
                    <?php
                    
                    // Handle form submission
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
                        isset($_POST['action']) && $_POST['action'] === 'accept_invitation' &&
                        wp_verify_nonce($_POST['invitation_nonce'], 'accept_invitation')) {
                        
                        $result = $this->accept_invitation($token, $current_user->ID);
                        
                        if (is_wp_error($result)) {
                            ?>
                            <wa-alert variant="danger" open class="mt-4">
                                <?php echo esc_html($result->get_error_message()); ?>
                            </wa-alert>
                            <?php
                        } else {
                            ?>
                            <script>window.location.href = '<?php echo esc_url(home_url('/min-side/?invitation_accepted=1')); ?>';</script>
                            <wa-alert variant="success" open class="mt-4">
                                Du er nå koblet til <?php echo esc_html($company_name); ?>! Omdirigerer...
                            </wa-alert>
                            <?php
                        }
                    }
                } else {
                    // Not logged in - show registration prompt
                    ?>
                    <wa-card>
                        <div class="text-center space-y-4">
                            <wa-icon name="envelope-open-text" library="fa" style="font-size: 3rem; color: var(--wa-color-purple-600);"></wa-icon>
                            <h2 class="text-xl font-semibold">Du er invitert!</h2>
                            <p class="text-gray-600">
                                <strong><?php echo esc_html($company_name); ?></strong> har invitert deg til BIM Verdi.
                            </p>
                            <p class="text-gray-500 text-sm">
                                Invitasjonen gjelder: <?php echo esc_html($invitation->email); ?>
                            </p>
                            
                            <div class="border-t pt-4 mt-4 space-y-3">
                                <p class="font-medium">For å akseptere invitasjonen:</p>
                                
                                <wa-button variant="brand" size="large" href="<?php echo esc_url(add_query_arg('invitation_token', $token, home_url('/registrer/'))); ?>">
                                    <wa-icon slot="prefix" name="user-plus" library="fa"></wa-icon>
                                    Opprett ny konto
                                </wa-button>
                                
                                <p class="text-gray-500 text-sm">eller</p>
                                
                                <wa-button variant="neutral" outline href="<?php echo esc_url(home_url('/logg-inn/?redirect_to=' . urlencode(add_query_arg('token', $token, get_permalink())))); ?>">
                                    <wa-icon slot="prefix" name="right-to-bracket" library="fa"></wa-icon>
                                    Logg inn med eksisterende konto
                                </wa-button>
                            </div>
                        </div>
                    </wa-card>
                    <?php
                }
            }
        }
        ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Link invited user after registration
     * 
     * @param int $user_id
     */
    public function link_invited_user($user_id) {
        if (!session_id()) {
            session_start();
        }
        
        if (empty($_SESSION['bimverdi_invitation_token'])) {
            return;
        }
        
        $token = $_SESSION['bimverdi_invitation_token'];
        $result = $this->accept_invitation($token, $user_id);
        
        // Clear session
        unset($_SESSION['bimverdi_invitation_token']);
        unset($_SESSION['bimverdi_invitation_email']);
        unset($_SESSION['bimverdi_invitation_company']);
    }
    
    /**
     * Revoke an invitation
     * 
     * @param int $invitation_id
     * @param int $user_id User requesting revocation
     * @return bool|WP_Error
     */
    public function revoke_invitation($invitation_id, $user_id) {
        global $wpdb;
        
        $invitation = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $invitation_id)
        );
        
        if (!$invitation) {
            return new WP_Error('not_found', 'Invitasjon ikke funnet');
        }
        
        // Check if user can manage this invitation
        if (!$this->can_send_invitations($user_id, $invitation->company_id)) {
            return new WP_Error('not_authorized', 'Du har ikke tillatelse til å administrere denne invitasjonen');
        }
        
        if ($invitation->status !== 'pending') {
            return new WP_Error('cannot_revoke', 'Kan ikke trekke tilbake en allerede brukt invitasjon');
        }
        
        $wpdb->update(
            $this->table_name,
            array('status' => 'revoked'),
            array('id' => $invitation_id),
            array('%s'),
            array('%d')
        );
        
        return true;
    }
    
    /**
     * Remove user access from company
     * 
     * @param int $target_user_id User to remove
     * @param int $requesting_user_id User requesting removal
     * @return bool|WP_Error
     */
    public function remove_user_access($target_user_id, $requesting_user_id) {
        // Check both meta keys for company ID (new key first, then legacy)
        $user_company_id = get_user_meta($target_user_id, 'bimverdi_company_id', true);
        if (!$user_company_id) {
            $user_company_id = get_user_meta($target_user_id, 'bim_verdi_company_id', true);
        }

        if (!$user_company_id) {
            return new WP_Error('not_linked', 'Bruker er ikke koblet til noe foretak');
        }

        // Check if requesting user can manage this company
        if (!$this->can_send_invitations($requesting_user_id, $user_company_id)) {
            return new WP_Error('not_authorized', 'Du har ikke tillatelse til å fjerne brukere fra dette foretaket');
        }

        // Cannot remove hovedkontakt
        $hovedkontakt_id = get_field('hovedkontaktperson', $user_company_id);
        if ($target_user_id == $hovedkontakt_id) {
            return new WP_Error('cannot_remove_hovedkontakt', 'Kan ikke fjerne hovedkontaktperson');
        }

        // Remove company link (both keys)
        delete_user_meta($target_user_id, 'bimverdi_company_id');
        delete_user_meta($target_user_id, 'bim_verdi_company_id');

        if (function_exists('delete_field')) {
            delete_field('tilknyttet_foretak', 'user_' . $target_user_id);
        }

        // Change role back to subscriber
        $user = new WP_User($target_user_id);
        $user->set_role('subscriber');

        return true;
    }
    
    /**
     * AJAX: Send invitation
     */
    public function ajax_send_invitation() {
        check_ajax_referer('bimverdi_invitation_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Du må være logget inn'));
        }
        
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
        
        if (empty($email) || empty($company_id)) {
            wp_send_json_error(array('message' => 'Mangler påkrevde felt'));
        }
        
        $result = $this->send_invitation($email, $company_id, get_current_user_id());
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => 'Invitasjon sendt til ' . $email,
            'invitation' => $result,
        ));
    }
    
    /**
     * AJAX: Revoke invitation
     */
    public function ajax_revoke_invitation() {
        check_ajax_referer('bimverdi_invitation_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Du må være logget inn'));
        }
        
        $invitation_id = isset($_POST['invitation_id']) ? intval($_POST['invitation_id']) : 0;
        
        if (empty($invitation_id)) {
            wp_send_json_error(array('message' => 'Mangler invitasjons-ID'));
        }
        
        $result = $this->revoke_invitation($invitation_id, get_current_user_id());
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => 'Invitasjon trukket tilbake'));
    }
    
    /**
     * AJAX: Remove user access
     */
    public function ajax_remove_user_access() {
        check_ajax_referer('bimverdi_invitation_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Du må være logget inn'));
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (empty($user_id)) {
            wp_send_json_error(array('message' => 'Mangler bruker-ID'));
        }
        
        $result = $this->remove_user_access($user_id, get_current_user_id());
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => 'Brukertilgang fjernet'));
    }
    
    /**
     * Cleanup expired invitations
     */
    public function cleanup_expired_invitations() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$this->table_name} 
            WHERE status = 'pending' 
            AND expires_at < NOW()"
        );
    }
    
    /**
     * Notify admin when invitation is sent
     */
    public function notify_admin_invitation_sent($invitation_id, $email, $company_id) {
        $company = get_post($company_id);
        $admin_email = get_option('admin_email');
        
        $subject = 'Ny invitasjon sendt i BIM Verdi';
        $message = sprintf(
            "En ny invitasjon er sendt:\n\nE-post: %s\nForetak: %s\n\nLogg inn i WordPress for å se detaljer.",
            $email,
            $company ? $company->post_title : 'Ukjent'
        );
        
        wp_mail($admin_email, $subject, $message);
    }
}

// Initialize the class
function bimverdi_company_invitations() {
    static $instance = null;
    if ($instance === null) {
        $instance = new BIMVerdi_Company_Invitations();
    }
    return $instance;
}

// Start on plugins_loaded to ensure all dependencies are available
add_action('plugins_loaded', 'bimverdi_company_invitations');

/**
 * Helper function to get the invitations instance
 */
function bimverdi_get_invitations() {
    return bimverdi_company_invitations();
}
