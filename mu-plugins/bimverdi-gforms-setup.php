<?php
/**
 * BIM Verdi - Gravity Forms Setup Script
 * 
 * Oppretter de nødvendige Gravity Forms for email-verifiserings-flyten.
 * Kjør denne én gang ved å besøke: /wp-admin/?bimverdi_setup_forms=1
 * 
 * MERK: Gravity Forms tildeler automatisk neste ledige ID.
 * Etter oppretting, oppdater FORM_ID konstantene i bimverdi-email-verification.php
 * 
 * @package BIMVerdi
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Setup Gravity Forms for email verification flow
 */
class BIMVerdi_GForms_Setup {
    
    /**
     * Initialize setup hooks
     */
    public function __construct() {
        add_action('admin_init', array($this, 'maybe_create_forms'));
        add_action('admin_notices', array($this, 'show_admin_notice'));
    }
    
    /**
     * Check if we should create forms
     */
    public function maybe_create_forms() {
        // Only run if explicitly triggered
        if (!isset($_GET['bimverdi_setup_forms']) || $_GET['bimverdi_setup_forms'] !== '1') {
            return;
        }
        
        // Only allow administrators
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if GFAPI exists
        if (!class_exists('GFAPI')) {
            add_settings_error(
                'bimverdi_forms',
                'gf_missing',
                'Gravity Forms er ikke installert eller aktivert.',
                'error'
            );
            return;
        }
        
        // Check if forms already exist (by title)
        $existing_forms = GFAPI::get_forms();
        $email_form_exists = false;
        $verify_form_exists = false;
        
        foreach ($existing_forms as $form) {
            if (strpos($form['title'], 'Email (Steg 1)') !== false) {
                $email_form_exists = $form['id'];
            }
            if (strpos($form['title'], 'Aktiver konto (Steg 2)') !== false) {
                $verify_form_exists = $form['id'];
            }
        }
        
        // Create forms
        $results = array();
        
        // Form: Email Signup
        if ($email_form_exists) {
            $results['email_form'] = array(
                'success' => false,
                'message' => "Email-skjema eksisterer allerede (ID: {$email_form_exists})"
            );
        } else {
            $results['email_form'] = $this->create_email_signup_form();
        }
        
        // Form: Verify Account
        if ($verify_form_exists) {
            $results['verify_form'] = array(
                'success' => false,
                'message' => "Verifiserings-skjema eksisterer allerede (ID: {$verify_form_exists})"
            );
        } else {
            $results['verify_form'] = $this->create_verify_account_form();
        }
        
        // Store results for admin notice
        set_transient('bimverdi_forms_setup_results', $results, 60);
        
        // Redirect to remove query param
        wp_redirect(admin_url('admin.php?page=gf_edit_forms&bimverdi_setup_complete=1'));
        exit;
    }
    
    /**
     * Create Email Signup Form
     * 
     * @return array Result with 'success' and 'message'
     */
    private function create_email_signup_form() {
        $form = array(
            'title' => 'Registrer - Email (Steg 1)',
            'description' => 'Første steg i registrering. Brukeren oppgir kun e-post og mottar verifiseringslenke.',
            'labelPlacement' => 'top_label',
            'descriptionPlacement' => 'below',
            'button' => array(
                'type' => 'text',
                'text' => 'Send verifiseringslenke',
                'imageUrl' => '',
            ),
            'fields' => array(
                // Field 1: Email
                array(
                    'type' => 'email',
                    'id' => 1,
                    'label' => 'E-postadresse',
                    'adminLabel' => 'email',
                    'isRequired' => true,
                    'size' => 'large',
                    'placeholder' => 'din@epost.no',
                    'description' => 'Vi sender deg en lenke for å fullføre registreringen.',
                    'cssClass' => 'bimverdi-email-field',
                    'enableAutocomplete' => true,
                    'autocompleteAttribute' => 'email',
                ),
            ),
            'cssClass' => 'bimverdi-email-signup-form',
            'enableHoneypot' => true,
            'enableAnimation' => false,
            'save' => array(
                'enabled' => false,
            ),
            // No redirect - custom confirmation handles this
            'confirmations' => array(
                array(
                    'id' => '1',
                    'name' => 'Standard bekreftelse',
                    'isDefault' => true,
                    'type' => 'message',
                    'message' => 'Behandles av PHP-handler',
                ),
            ),
            'notifications' => array(), // No default notifications - we send custom email
        );
        
        $result = GFAPI::add_form($form);
        
        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'message' => 'Kunne ikke opprette Form 10: ' . $result->get_error_message()
            );
        }
        
        return array(
            'success' => true,
            'message' => 'Form 10 (Email Signup) opprettet med ID: ' . $result,
            'form_id' => $result
        );
    }
    
    /**
     * Create Verify Account Form (Form ID 11)
     * 
     * @return array Result with 'success' and 'message'
     */
    private function create_verify_account_form() {
        // Check if form already exists
        $existing = GFAPI::get_form(11);
        if ($existing && !is_wp_error($existing)) {
            return array(
                'success' => false,
                'message' => 'Form 11 eksisterer allerede: ' . $existing['title']
            );
        }
        
        $form = array(
            'id' => 11,
            'title' => 'Aktiver konto (Steg 2)',
            'description' => 'Fullfør registreringen med navn og passord.',
            'labelPlacement' => 'top_label',
            'descriptionPlacement' => 'below',
            'button' => array(
                'type' => 'text',
                'text' => 'Aktiver kontoen min',
                'imageUrl' => '',
            ),
            'fields' => array(
                // Field 1: Email (hidden, pre-populated from URL)
                array(
                    'type' => 'hidden',
                    'id' => 1,
                    'label' => 'E-post',
                    'adminLabel' => 'email',
                    'defaultValue' => '',
                    'allowsPrepopulate' => true,
                    'inputName' => 'email',
                ),
                // Field 2: Full Name
                array(
                    'type' => 'text',
                    'id' => 2,
                    'label' => 'Fullt navn',
                    'adminLabel' => 'full_name',
                    'isRequired' => true,
                    'size' => 'large',
                    'placeholder' => 'Ola Nordmann',
                    'description' => 'Hva skal vi kalle deg?',
                    'cssClass' => 'bimverdi-name-field',
                    'enableAutocomplete' => true,
                    'autocompleteAttribute' => 'name',
                ),
                // Field 3: Password
                array(
                    'type' => 'password',
                    'id' => 3,
                    'label' => 'Velg et passord',
                    'adminLabel' => 'password',
                    'isRequired' => true,
                    'size' => 'large',
                    'placeholder' => '••••••••',
                    'description' => 'Minimum 8 tegn. Velg noe du husker!',
                    'cssClass' => 'bimverdi-password-field',
                    'enablePasswordInput' => true,
                    'inputs' => array(
                        array(
                            'id' => '3',
                            'label' => 'Passord',
                            'name' => '',
                        ),
                    ),
                ),
                // Field 4: Token (hidden, pre-populated from URL)
                array(
                    'type' => 'hidden',
                    'id' => 4,
                    'label' => 'Token',
                    'adminLabel' => 'token',
                    'defaultValue' => '',
                    'allowsPrepopulate' => true,
                    'inputName' => 'token',
                ),
            ),
            'cssClass' => 'bimverdi-verify-form',
            'enableHoneypot' => true,
            'enableAnimation' => false,
            'save' => array(
                'enabled' => false,
            ),
            // Redirect to Min Side after successful submission
            'confirmations' => array(
                array(
                    'id' => '1',
                    'name' => 'Redirect til Min Side',
                    'isDefault' => true,
                    'type' => 'redirect',
                    'url' => home_url('/min-side/'),
                    'queryString' => 'welcome=1',
                ),
            ),
            'notifications' => array(
                // Welcome email notification
                array(
                    'id' => '1',
                    'name' => 'Velkommen-epost',
                    'event' => 'form_submission',
                    'to' => '{Email:1}',
                    'toType' => 'field',
                    'subject' => 'Velkommen til BIM Verdi! 🎉',
                    'message' => $this->get_welcome_email_template(),
                    'from' => '{admin_email}',
                    'fromName' => 'BIM Verdi',
                    'replyTo' => '{admin_email}',
                    'isActive' => true,
                ),
            ),
        );
        
        $result = GFAPI::add_form($form);
        
        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'message' => 'Kunne ikke opprette Form 11: ' . $result->get_error_message()
            );
        }
        
        return array(
            'success' => true,
            'message' => 'Form 11 (Verify Account) opprettet med ID: ' . $result,
            'form_id' => $result
        );
    }
    
    /**
     * Get welcome email template
     * 
     * @return string
     */
    private function get_welcome_email_template() {
        return '
<h2>Velkommen til BIM Verdi, {Fullt navn:2}! 🎉</h2>

<p>Din konto er nå aktivert og du er klar til å utforske deltakerportalen.</p>

<h3>Hva kan du gjøre nå?</h3>
<ul>
    <li>✅ Utforske deltakerkatalogen</li>
    <li>✅ Se verktøykatalogen</li>
    <li>✅ Melde deg på arrangementer</li>
</ul>

<h3>Vil du ha full tilgang?</h3>
<p>For å registrere verktøy, skrive artikler og delta i temagrupper, må du koble kontoen til et foretak.</p>

<p><a href="' . home_url('/min-side/') . '">Gå til Min Side →</a></p>

<p>Velkommen til nettverket!</p>

<p>Med vennlig hilsen,<br>
BIM Verdi Team</p>
';
    }
    
    /**
     * Show admin notice with setup results
     */
    public function show_admin_notice() {
        // Check for setup complete flag
        if (isset($_GET['bimverdi_setup_complete'])) {
            $results = get_transient('bimverdi_forms_setup_results');
            delete_transient('bimverdi_forms_setup_results');
            
            if ($results) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>BIM Verdi Forms Setup:</strong></p>';
                echo '<ul>';
                
                foreach ($results as $key => $result) {
                    $icon = $result['success'] ? '✅' : '⚠️';
                    echo '<li>' . $icon . ' ' . esc_html($result['message']) . '</li>';
                }
                
                echo '</ul>';
                echo '</div>';
            }
        }
        
        // Show setup instructions on Gravity Forms page
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'gf_') !== false) {
            // Check if forms exist
            if (class_exists('GFAPI')) {
                $form_10 = GFAPI::get_form(10);
                $form_11 = GFAPI::get_form(11);
                
                if (!$form_10 || !$form_11) {
                    echo '<div class="notice notice-info is-dismissible">';
                    echo '<p><strong>BIM Verdi Email Verification:</strong> ';
                    echo 'Skjemaene for email-verifisering er ikke opprettet. ';
                    echo '<a href="' . admin_url('?bimverdi_setup_forms=1') . '">Klikk her for å opprette dem automatisk</a>.</p>';
                    echo '</div>';
                }
            }
        }
    }
}

// Initialize
new BIMVerdi_GForms_Setup();

/**
 * Manual form creation helper
 * 
 * If you prefer to create forms manually in the Gravity Forms admin,
 * here are the field configurations you need:
 * 
 * =========================================
 * FORM 10: Email Signup (Steg 1)
 * =========================================
 * Title: Registrer - Email (Steg 1)
 * 
 * Fields:
 * - Field 1: Email (Required)
 *   Type: Email
 *   Label: E-postadresse
 *   Placeholder: din@epost.no
 *   Description: Vi sender deg en lenke for å fullføre registreringen.
 * 
 * Button: Send verifiseringslenke
 * Confirmation: Custom (handled by PHP)
 * 
 * =========================================
 * FORM 11: Verify Account (Steg 2)
 * =========================================
 * Title: Aktiver konto (Steg 2)
 * 
 * Fields:
 * - Field 1: Hidden
 *   Label: E-post
 *   Admin Label: email
 *   Allow Prepopulate: Yes
 *   Parameter Name: email
 * 
 * - Field 2: Single Line Text (Required)
 *   Label: Fullt navn
 *   Placeholder: Ola Nordmann
 *   Description: Hva skal vi kalle deg?
 * 
 * - Field 3: Password (Required)
 *   Label: Velg et passord
 *   Placeholder: ••••••••
 *   Description: Minimum 8 tegn. Velg noe du husker!
 *   Enable Password Visibility: Yes
 * 
 * - Field 4: Hidden
 *   Label: Token
 *   Admin Label: token
 *   Allow Prepopulate: Yes
 *   Parameter Name: token
 * 
 * Button: Aktiver kontoen min
 * Confirmation: Redirect to /min-side/?welcome=1
 */
