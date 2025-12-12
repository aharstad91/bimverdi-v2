<?php
/**
 * Leads Form Handler - Søknadsbistand
 * 
 * Handles public lead submissions (Innovasjon Norge "Rask avklaring" style)
 * Creates "case" posts for project ideas without requiring login
 * 
 * @package BIM_Verdi_Core
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Leads_Form_Handler {
    
    const FORM_ID = 10; // "[Public] - Søknadsbistand prosjektidé"
    
    // Email recipients for lead notifications
    const ADMIN_EMAIL = 'post@bimverdi.no'; // Main notification email
    const SECONDARY_EMAIL = 'bard@bimverdi.no'; // Project manager (Bård)
    
    /**
     * Initialize the handler
     */
    public function __construct() {
        // Handle form submission
        add_action('gform_after_submission_' . self::FORM_ID, array($this, 'handle_submission'), 10, 2);
        
        // Pre-populate user fields if logged in (nice-to-have)
        add_filter('gform_field_value_lead_name', array($this, 'populate_user_name'));
        add_filter('gform_field_value_lead_email', array($this, 'populate_user_email'));
        add_filter('gform_field_value_lead_phone', array($this, 'populate_user_phone'));
        add_filter('gform_field_value_lead_company', array($this, 'populate_user_company'));
        add_filter('gform_field_value_user_id', array($this, 'populate_user_id'));
        
        // Custom confirmation message
        add_filter('gform_confirmation_' . self::FORM_ID, array($this, 'custom_confirmation'), 10, 4);
    }
    
    /**
     * Populate name from logged-in user
     */
    public function populate_user_name() {
        $user = wp_get_current_user();
        if (!$user->ID) return '';
        
        $first_name = get_user_meta($user->ID, 'first_name', true);
        $last_name = get_user_meta($user->ID, 'last_name', true);
        
        if ($first_name || $last_name) {
            return trim($first_name . ' ' . $last_name);
        }
        return $user->display_name;
    }
    
    /**
     * Populate email from logged-in user
     */
    public function populate_user_email() {
        $user = wp_get_current_user();
        return $user->ID ? $user->user_email : '';
    }
    
    /**
     * Populate phone from logged-in user
     */
    public function populate_user_phone() {
        $user_id = get_current_user_id();
        if (!$user_id) return '';
        
        return get_user_meta($user_id, 'telefon', true);
    }
    
    /**
     * Populate company from logged-in user's foretak
     */
    public function populate_user_company() {
        $user_id = get_current_user_id();
        if (!$user_id) return '';
        
        $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
        if (!$company_id) return '';
        
        $company = get_post($company_id);
        return $company ? $company->post_title : '';
    }
    
    /**
     * Populate user_id for tracking
     */
    public function populate_user_id() {
        return get_current_user_id() ?: 0;
    }
    
    /**
     * Handle form submission - create case post and send notifications
     * 
     * @param array $entry The form entry
     * @param array $form The form object
     */
    public function handle_submission($entry, $form) {
        // Extract form data
        $contact_name = rgar($entry, '1');      // Kontaktnavn
        $contact_email = rgar($entry, '2');     // E-post
        $contact_company = rgar($entry, '3');   // Foretak
        $contact_phone = rgar($entry, '4');     // Telefon
        $project_title = rgar($entry, '5');     // Prosjekttittel
        $short_description = rgar($entry, '6'); // Kort beskrivelse av idé
        $benefit_business = rgar($entry, '7');  // Nytte for bedrift
        $benefit_society = rgar($entry, '8');   // Nytte for samfunn
        $need_knowledge = rgar($entry, '9');    // Behov for ny kunnskap/FoU
        $potential_partners = rgar($entry, '10'); // Mulige partnere
        $timeframe = rgar($entry, '11');        // Tidsramme
        $user_id = rgar($entry, '12');          // Hidden: User ID (if logged in)
        
        // Create the case post
        $post_data = array(
            'post_type'    => 'case',
            'post_status'  => 'publish',
            'post_title'   => sanitize_text_field($project_title),
            'post_content' => '', // Content is stored in ACF fields
            'post_author'  => $user_id ?: 1, // Use logged-in user or default to admin
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            // Log error but don't show to user
            error_log('BIM Verdi Leads: Failed to create case post - ' . $post_id->get_error_message());
            return;
        }
        
        // Build detailed description from all fields
        $detailed_description = $this->build_detailed_description(
            $short_description,
            $benefit_business,
            $benefit_society,
            $need_knowledge
        );
        
        // Update ACF fields
        update_field('kort_beskrivelse', sanitize_textarea_field($short_description), $post_id);
        update_field('detaljert_beskrivelse', wp_kses_post($detailed_description), $post_id);
        update_field('case_status', 'ny', $post_id); // "Ny" status for leads
        update_field('dato_sendt', current_time('Y-m-d'), $post_id);
        update_field('potensielle_partnere', sanitize_textarea_field($potential_partners), $post_id);
        
        // Store lead-specific fields as post meta
        update_post_meta($post_id, '_lead_contact_name', sanitize_text_field($contact_name));
        update_post_meta($post_id, '_lead_contact_email', sanitize_email($contact_email));
        update_post_meta($post_id, '_lead_contact_company', sanitize_text_field($contact_company));
        update_post_meta($post_id, '_lead_contact_phone', sanitize_text_field($contact_phone));
        update_post_meta($post_id, '_lead_benefit_business', sanitize_textarea_field($benefit_business));
        update_post_meta($post_id, '_lead_benefit_society', sanitize_textarea_field($benefit_society));
        update_post_meta($post_id, '_lead_need_knowledge', sanitize_textarea_field($need_knowledge));
        update_post_meta($post_id, '_lead_timeframe', sanitize_text_field($timeframe));
        update_post_meta($post_id, '_lead_source', 'public_form');
        update_post_meta($post_id, '_lead_user_id', intval($user_id));
        update_post_meta($post_id, '_lead_entry_id', $entry['id']); // Link to GF entry
        
        // If user is logged in, link to their company
        if ($user_id) {
            $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
            if ($company_id) {
                update_field('bedrift', $company_id, $post_id);
                update_field('innsendt_av', $user_id, $post_id);
            }
        }
        
        // Send notifications
        $this->send_admin_notification($post_id, $entry);
        $this->send_autoresponse($contact_email, $contact_name, $project_title);
        
        // Store the post ID in the entry for reference
        GFAPI::update_entry_property($entry['id'], 'post_id', $post_id);
        
        // Log success
        error_log("BIM Verdi Leads: Created case #{$post_id} for '{$project_title}' from {$contact_email}");
    }
    
    /**
     * Build detailed description from multiple fields
     */
    private function build_detailed_description($short, $business, $society, $knowledge) {
        $html = '<h3>Kort beskrivelse av idé</h3>';
        $html .= '<p>' . nl2br(esc_html($short)) . '</p>';
        
        if (!empty($business)) {
            $html .= '<h3>Nytte for bedrift</h3>';
            $html .= '<p>' . nl2br(esc_html($business)) . '</p>';
        }
        
        if (!empty($society)) {
            $html .= '<h3>Nytte for samfunn</h3>';
            $html .= '<p>' . nl2br(esc_html($society)) . '</p>';
        }
        
        if (!empty($knowledge)) {
            $html .= '<h3>Behov for ny kunnskap / FoU</h3>';
            $html .= '<p>' . nl2br(esc_html($knowledge)) . '</p>';
        }
        
        return $html;
    }
    
    /**
     * Send notification to BIM Verdi team
     */
    private function send_admin_notification($post_id, $entry) {
        $contact_name = get_post_meta($post_id, '_lead_contact_name', true);
        $contact_email = get_post_meta($post_id, '_lead_contact_email', true);
        $contact_company = get_post_meta($post_id, '_lead_contact_company', true);
        $contact_phone = get_post_meta($post_id, '_lead_contact_phone', true);
        $project_title = get_the_title($post_id);
        $timeframe = get_post_meta($post_id, '_lead_timeframe', true);
        
        $admin_url = admin_url('post.php?post=' . $post_id . '&action=edit');
        
        $subject = 'Ny prosjektidé: ' . $project_title;
        
        $message = "Hei!\n\n";
        $message .= "En ny prosjektidé er mottatt via søknadsbistands-skjemaet.\n\n";
        $message .= "───────────────────────────\n";
        $message .= "KONTAKTINFORMASJON\n";
        $message .= "───────────────────────────\n";
        $message .= "Navn: {$contact_name}\n";
        $message .= "E-post: {$contact_email}\n";
        $message .= "Foretak: {$contact_company}\n";
        $message .= "Telefon: {$contact_phone}\n\n";
        $message .= "───────────────────────────\n";
        $message .= "PROSJEKTIDÉ\n";
        $message .= "───────────────────────────\n";
        $message .= "Tittel: {$project_title}\n";
        $message .= "Tidsramme: {$timeframe}\n\n";
        $message .= "───────────────────────────\n\n";
        $message .= "Se fullstendig idé i admin:\n{$admin_url}\n\n";
        $message .= "───────────────────────────\n";
        $message .= "Husk å oppdatere status fra \"Ny\" når du starter behandling.\n\n";
        $message .= "Med vennlig hilsen,\nBIM Verdi Portal";
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: BIM Verdi Portal <noreply@bimverdi.no>',
        );
        
        // Send to primary admin
        wp_mail(self::ADMIN_EMAIL, $subject, $message, $headers);
        
        // Send to secondary (project manager)
        wp_mail(self::SECONDARY_EMAIL, $subject, $message, $headers);
    }
    
    /**
     * Send auto-response to the person who submitted
     */
    private function send_autoresponse($email, $name, $project_title) {
        $subject = 'Takk for din prosjektidé - ' . $project_title;
        
        $message = "Hei {$name}!\n\n";
        $message .= "Takk for at du har sendt inn din prosjektidé til BIM Verdi.\n\n";
        $message .= "───────────────────────────\n";
        $message .= "Prosjektidé: {$project_title}\n";
        $message .= "───────────────────────────\n\n";
        $message .= "Vi har mottatt informasjonen og vil gjennomgå idéen din.\n";
        $message .= "Du kan forvente å høre fra oss innen 5 virkedager.\n\n";
        $message .= "Har du spørsmål i mellomtiden? Kontakt oss på post@bimverdi.no.\n\n";
        $message .= "Med vennlig hilsen,\n";
        $message .= "BIM Verdi Team\n\n";
        $message .= "---\n";
        $message .= "BIM Verdi – Vi bygger fremtiden med BIM\n";
        $message .= "https://bimverdi.no";
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: BIM Verdi <post@bimverdi.no>',
            'Reply-To: BIM Verdi <post@bimverdi.no>',
        );
        
        wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Custom confirmation message
     */
    public function custom_confirmation($confirmation, $form, $entry, $ajax) {
        $name = rgar($entry, '1');
        $first_name = explode(' ', trim($name))[0];
        
        $confirmation = array(
            'type' => 'message',
            'message' => '
                <div class="gform_confirmation_wrapper">
                    <div class="gform_confirmation_message" style="text-align: center; padding: 2rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#22c55e" viewBox="0 0 16 16" style="margin-bottom: 1rem;">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                        </svg>
                        <h2 style="color: #1f2937; margin-bottom: 0.5rem;">Takk, ' . esc_html($first_name) . '!</h2>
                        <p style="color: #6b7280; margin-bottom: 1rem;">Vi har mottatt din prosjektidé og tar kontakt innen <strong>5 virkedager</strong>.</p>
                        <p style="color: #6b7280; font-size: 0.875rem;">En bekreftelse er sendt til din e-postadresse.</p>
                    </div>
                </div>
            ',
        );
        
        return $confirmation;
    }
}
