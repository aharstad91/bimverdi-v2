<?php
/**
 * User Registration Form Handler
 * 
 * Handles user registration Gravity Forms submissions.
 * Creates WordPress user and links to existing or new Foretak post.
 *
 * @package BIM_Verdi_Core
 * @version 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_User_Form_Handler {
    
    const FORM_ID = 3; // User registration form
    const NEW_COMPANY_VALUE = 'opprett-ny-bedrift';
    
    /**
     * Initialize the handler
     */
    public function __construct() {
        add_action('gform_after_submission_' . self::FORM_ID, array($this, 'handle_submission'), 10, 2);
        add_filter('gform_validation_' . self::FORM_ID, array($this, 'validate_submission'));
        add_filter('gform_confirmation_' . self::FORM_ID, array($this, 'custom_confirmation'), 10, 4);
        add_filter('gform_pre_render_' . self::FORM_ID, array($this, 'fix_form_conditional_logic'), 9); // Run BEFORE populate
        add_filter('gform_pre_render_' . self::FORM_ID, array($this, 'populate_company_dropdown'), 10);
    }
    
    /**
     * Handle user registration form submission
     * 
     * PROFILE-ONLY HANDLER:
     * Form 3 is now simplified to profile registration only.
     * Company logic has been moved to gform-user-meta-sync.php (mu-plugin).
     * 
     * This handler still creates the WordPress user, but company operations are skipped.
     * User meta synchronization is handled by the mu-plugin.
     * 
     * @param array $entry The submitted form entry
     * @param array $form The form object
     */
    public function handle_submission($entry, $form) {
        
        try {
            // Extract form data
            $user_data = $this->extract_user_data($entry);
            // Company choice removed in simplified form - see gform-user-meta-sync.php
            $company_choice = null;
            
            // Create WordPress user
            $user_id = $this->create_user($user_data);
            
            if (is_wp_error($user_id)) {
                throw new Exception('Failed to create user: ' . $user_id->get_error_message());
            }
            
            // PROFILE-ONLY: Skip company creation logic
            // Company association is handled by separate logic or manual admin assignment
            $company_id = null;
            
            // PROFILE-ONLY: Skip company linking for now
            // Company assignment can be done manually via WordPress admin or separate logic
            if ($company_id) {
                update_user_meta($user_id, 'bim_verdi_company_id', $company_id);
                update_user_meta($user_id, 'bim_verdi_company_role', 'deltaker');
            }
            
            // Send welcome email
            $this->send_welcome_email($user_id, $company_id, $user_data['email'], $user_data['first_name']);
            
            // Auto-login the user
            // User will be redirected to /min-side via Gravity Forms redirect setting
            $this->auto_login_user($user_id);
            
            // Store success data for logging purposes
            gform_update_meta($entry['id'], 'created_user_id', $user_id);
            gform_update_meta($entry['id'], 'created_company_id', $company_id);
            
            error_log("BIM Verdi User Handler: Registration complete - User ID: {$user_id}, Company ID: {$company_id}, Auto-login complete");
            
        } catch (Exception $e) {
            error_log('BIM Verdi User Handler Exception: ' . $e->getMessage());
            gform_update_meta($entry['id'], 'registration_error', $e->getMessage());
        }
    }
    
    /**
     * Validate form submission
     * 
     * NOTE: Form 3 has been simplified to be profile-only. Company logic is now handled by separate logic.
     * This validation now only checks password and email.
     * 
     * @param array $validation_result
     * @return array
     */
    public function validate_submission($validation_result) {
        
        $form = $validation_result['form'];
        $is_valid = $validation_result['is_valid'];
        
        $email = sanitize_email(rgar($_POST, 'input_3')); // Field 3: Email
        $password = sanitize_text_field(rgar($_POST, 'input_7')); // Field 7: Password
        $password_confirm = sanitize_text_field(rgar($_POST, 'input_8')); // Field 8: Confirm
        // Company fields removed in simplified form - see gform-user-meta-sync.php for new profile-only logic
        $company_choice = null;
        $org_number = null;
        
        // 1. Check for duplicate email
        if (email_exists($email)) {
            $is_valid = false;
            
            foreach ($form['fields'] as &$field) {
                if ($field->id == 3) {
                    $field->validation_message = 'E-posten er allerede registrert. <a href="' . wp_login_url() . '">Logg inn her</a> eller bruk en annen e-post.';
                    $field['failed_validation'] = true;
                }
            }
        }
        
        // 2. Check password match
        if ($password !== $password_confirm) {
            $is_valid = false;
            
            foreach ($form['fields'] as &$field) {
                if ($field->id == 8) {
                    $field->validation_message = 'Passordene stemmer ikke overens.';
                    $field['failed_validation'] = true;
                }
            }
        }
        
        // 3. Check password strength
        if (strlen($password) < 8) {
            $is_valid = false;
            
            foreach ($form['fields'] as &$field) {
                if ($field->id == 7) {
                    $field->validation_message = 'Passord må være minst 8 tegn.';
                    $field['failed_validation'] = true;
                }
            }
        }
        
        // 4. PROFILE-ONLY FORM: Company validation removed (see gform-user-meta-sync.php)
        // The following company-related checks are no longer needed for this simplified form:
        // - Org number format validation
        // - Duplicate org number check
        // - Company choice validation
        
        $validation_result['form'] = $form;
        $validation_result['is_valid'] = $is_valid;
        
        return $validation_result;
    }
    
    /**
     * Custom confirmation message
     * 
     * NOTE: Gravity Forms is configured to redirect to /min-side via admin UI
     * Min Side will show CTA to connect to company if user has no company linked
     * 
     * @param string $confirmation
     * @param array $form
     * @param array $entry
     * @param bool $ajax
     * @return string
     */
    public function custom_confirmation($confirmation, $form, $entry, $ajax) {
        // Let Gravity Forms handle the redirect via admin settings to /min-side
        // Min Side dashboard will show the "Koble til Foretak" alert if needed
        return $confirmation;
    }
    
    /**
     * Extract user data from form entry
     * 
     * @param array $entry
     * @return array
     */
    private function extract_user_data($entry) {
        return array(
            'first_name' => sanitize_text_field(rgar($entry, '1')),
            'last_name' => sanitize_text_field(rgar($entry, '2')),
            'email' => sanitize_email(rgar($entry, '3')),
            'phone' => sanitize_text_field(rgar($entry, '4')),
            'title' => sanitize_text_field(rgar($entry, '5')),
            'linkedin' => sanitize_text_field(rgar($entry, '6')),
            'password' => rgar($entry, '7'), // NOT sanitized - hashing handles it
        );
    }
    
    /**
     * Extract company data from form entry (new company only)
     * 
     * @param array $entry
     * @return array
     */
    private function extract_company_data($entry) {
        return array(
            'org_number' => sanitize_text_field(rgar($entry, '10')),
            'bedriftsnavn' => sanitize_text_field(rgar($entry, '11')),
            'description' => sanitize_textarea_field(rgar($entry, '12')),
            'address' => sanitize_text_field(rgar($entry, '13')),
            'zip' => sanitize_text_field(rgar($entry, '14')),
            'city' => sanitize_text_field(rgar($entry, '15')),
            'website' => esc_url_raw(rgar($entry, '16')),
            'phone' => sanitize_text_field(rgar($entry, '17')),
            'email' => sanitize_email(rgar($entry, '18')),
        );
    }
    
    /**
     * Create WordPress user
     * 
     * Saves standard WP fields (first_name, last_name) as user_meta,
     * and profile fields (phone, job_title, linkedin_url) to ACF.
     * 
     * ACF becomes the single source of truth for profile data:
     * - Stored in wp_usermeta with ACF-specific keys
     * - Automatically appear in wp-admin/user-edit.php
     * - Can be edited from WordPress admin or frontend acf_form()
     * - Queryable via ACF and WP_Query
     * 
     * @param array $user_data
     * @return int|WP_Error User ID or error
     */
    private function create_user($user_data) {
        
        $user_id = wp_create_user(
            $user_data['email'],
            $user_data['password'],
            $user_data['email']
        );
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Set standard WordPress user meta
        update_user_meta($user_id, 'first_name', $user_data['first_name']);
        update_user_meta($user_id, 'last_name', $user_data['last_name']);
        
        // Save profile data to ACF fields (single source of truth)
        // ACF stores these in wp_usermeta with acf-specific keys
        if (function_exists('update_field')) {
            // Phone number
            if (!empty($user_data['phone'])) {
                update_field('phone', $user_data['phone'], 'user_' . $user_id);
            }
            
            // Job title / Stilling
            if (!empty($user_data['title'])) {
                update_field('job_title', $user_data['title'], 'user_' . $user_id);
            }
            
            // LinkedIn URL
            if (!empty($user_data['linkedin'])) {
                update_field('linkedin_url', $user_data['linkedin'], 'user_' . $user_id);
            }
        }
        
        // Set default role
        $user_obj = new WP_User($user_id);
        $user_obj->set_role('company_user');
        
        return $user_id;
    }
    
    /**
     * Create company post (new company only)
     * 
     * @param array $company_data
     * @param int $user_id
     * @return int|WP_Error Post ID or error
     */
    private function create_company_post($company_data, $user_id) {
        
        $post_id = wp_insert_post(array(
            'post_type' => 'foretak',
            'post_title' => $company_data['bedriftsnavn'],
            'post_content' => $company_data['description'],
            'post_status' => 'pending',
            'post_author' => $user_id,
        ), true);
        
        return $post_id;
    }
    
    /**
     * Save company ACF fields
     * 
     * @param int $company_id
     * @param array $company_data
     * @param int $user_id
     */
    private function save_company_acf_fields($company_id, $company_data, $user_id) {
        
        update_field('organisasjonsnummer', $company_data['org_number'], $company_id);
        update_field('foretaksnavn', $company_data['bedriftsnavn'], $company_id);
        update_field('beskrivelse', $company_data['description'], $company_id);
        update_field('adresse', $company_data['address'], $company_id);
        update_field('postnummer', $company_data['zip'], $company_id);
        update_field('poststed', $company_data['city'], $company_id);
        update_field('nettside', $company_data['website'], $company_id);
        update_field('telefon', $company_data['phone'], $company_id);
        update_field('kontaktperson', $user_id, $company_id);
        update_field('medlemsstatus', 'pending', $company_id);
    }
    
    /**
     * Set company taxonomies
     * 
     * @param int $company_id
     * @param array $entry
     */
    private function set_company_taxonomies($company_id, $entry) {
        
        $categories = rgar($entry, '20'); // Field 20: Categories
        $customer_types = rgar($entry, '21'); // Field 21: Customer types
        $theme_groups = rgar($entry, '22'); // Field 22: Theme groups
        
        if (!empty($categories)) {
            wp_set_post_terms($company_id, (array)$categories, 'bransjekategori');
        }
        
        if (!empty($customer_types)) {
            wp_set_post_terms($company_id, (array)$customer_types, 'kundetype');
        }
        
        if (!empty($theme_groups)) {
            wp_set_post_terms($company_id, (array)$theme_groups, 'temagruppe');
        }
    }
    
    /**
     * Send welcome email to new user
     * 
     * @param int $user_id
     * @param int $company_id
     * @param string $email
     * @param string $first_name
     */
    private function send_welcome_email($user_id, $company_id, $email, $first_name) {
        
        $company_name = get_the_title($company_id);
        $company_status = get_post_status($company_id);
        
        $subject = 'Velkomst til BIM Verdi - Din konto er opprettet! 🎉';
        
        $message = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #0F0F0F; background: #F7F5EF; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: white; border-radius: 8px; }
                .header { background: linear-gradient(135deg, #FF8B5E 0%, #E67A4E 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { padding: 20px; }
                .button { background: #FF8B5E; color: white; padding: 12px 24px; border-radius: 25px; text-decoration: none; display: inline-block; margin: 20px 0; }
                .footer { background: #F7F5EF; padding: 15px; text-align: center; font-size: 12px; color: #4F4F4F; }
                code { background: #F7F5EF; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Velkomst til BIM Verdi!</h1>
                </div>
                <div class="content">
                    <p>Hei <strong>' . $first_name . '</strong>,</p>
                    
                    <p>Din brukerkonto er nå opprettet og du er klar til å logge inn på BIM Verdi-nettstedet! 🚀</p>
                    
                    <h3>Din bedrift: ' . $company_name . '</h3>
                    <p><em>Status: ' . ($company_status === 'pending' ? 'Under godkjenning' : 'Aktiv') . '</em></p>
                    
                    <p>Du kan nå:</p>
                    <ul>
                        <li>Logge inn på Min Side</li>
                        <li>Fullføre bedriftsprofilen</li>
                        <li>Registrere verktøy og artikler</li>
                        <li>Melde deg på arrangementer</li>
                    </ul>
                    
                    <a href="' . wp_login_url() . '" class="button">Logg inn her →</a>
                    
                    <hr>
                    
                    <p><strong>Innloggingsopplysninger:</strong></p>
                    <p>E-post: <code>' . $email . '</code></p>
                    <p><em>Passord: Det du valgte under registrering</em></p>
                    
                    <p>Hvis du glemte passord, kan du <a href="' . wp_lostpassword_url() . '">tilbakestille det her</a>.</p>
                    
                    <hr>
                    
                    <p>Lykke til, og velkommen til nettverket!</p>
                    
                    <p>Med vennlig hilsen,<br>
                    <strong>BIM Verdi Team</strong></p>
                </div>
                <div class="footer">
                    <p>&copy; 2025 BIM Verdi. Alle rettigheter forbeholdt.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Populate company dropdown field with all foretak posts
     * 
     * This is called before form renders to dynamically populate
     * Field 26 (company dropdown) with all registered companies.
     * 
     * @param array $form The form object
     * @return array Modified form object
     */
    public function populate_company_dropdown($form) {
        // Get all foretak posts
        $foretak_posts = get_posts(array(
            'post_type' => 'foretak',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        ));
        
        // Build choices array
        $choices = array(
            array('text' => '-- Velg bedrift --', 'value' => '')
        );
        
        foreach ($foretak_posts as $post) {
            $choices[] = array(
                'text' => $post->post_title,
                'value' => $post->ID
            );
        }
        
        // Find Field 26 (company dropdown) and update its choices
        foreach ($form['fields'] as &$field) {
            if ($field->id === 26 && $field->type === 'select') {
                $field->choices = $choices;
                break;
            }
        }
        
        return $form;
    }
    
    /**
     * Fix conditional logic for Field 26 dropdown
     * 
     * Gravity Forms doesn't render conditional fields in the HTML at all.
     * So we remove the conditional logic from the form object before rendering,
     * and then use JavaScript on the frontend to show/hide it.
     *
     * @param array $form The form object  
     * @return array Modified form object
     */
    public function fix_form_conditional_logic($form) {
        return $form;
    }
    
    /**
     * Automatically log in the user after registration
     * 
     * Sets the current user in WordPress and creates an authentication cookie
     * so the user is immediately logged in after form submission.
     * 
     * @param int $user_id The user ID to log in
     * @return bool True on success, false on failure
     */
    private function auto_login_user($user_id) {
        
        if (!$user_id || is_wp_error($user_id)) {
            error_log('BIM Verdi User Handler: Cannot auto-login invalid user ID');
            return false;
        }
        
        // Get the user object
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            error_log('BIM Verdi User Handler: User not found for ID: ' . $user_id);
            return false;
        }
        
        // Set the current user in WordPress
        wp_set_current_user($user_id, $user->user_login);
        
        // Set authentication cookie (this makes the user logged in)
        // Duration: 14 days (1209600 seconds)
        wp_set_auth_cookie($user_id, false, is_ssl());
        
        // Optional: Update the wp_logged_in cookie for frontend access
        do_action('wp_login', $user->user_login, $user);
        
        error_log('BIM Verdi User Handler: User ' . $user_id . ' automatically logged in');
        
        return true;
    }
}

// Instantiate the handler
new BIM_Verdi_User_Form_Handler();
