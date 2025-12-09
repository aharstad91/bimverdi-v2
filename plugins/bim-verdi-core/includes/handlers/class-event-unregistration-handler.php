<?php
/**
 * Event Unregistration Handler
 * 
 * Handles unregistration (avmelding) from events
 * 
 * @package BIM_Verdi_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Event_Unregistration_Handler {
    
    public function __construct() {
        add_action('admin_post_bimverdi_avmeld_arrangement', array($this, 'handle_unregistration'));
        add_action('admin_post_nopriv_bimverdi_avmeld_arrangement', array($this, 'handle_unauthorized'));
    }
    
    /**
     * Handle unauthorized unregistration attempt
     */
    public function handle_unauthorized() {
        wp_redirect(wp_login_url());
        exit;
    }
    
    /**
     * Handle unregistration request
     */
    public function handle_unregistration() {
        // Verify user is logged in
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url());
            exit;
        }
        
        $user_id = get_current_user_id();
        $pamelding_id = isset($_POST['pamelding_id']) ? intval($_POST['pamelding_id']) : 0;
        $arrangement_id = isset($_POST['arrangement_id']) ? intval($_POST['arrangement_id']) : 0;
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'avmeld_arrangement_' . $pamelding_id)) {
            wp_die('Ugyldig forespørsel. Vennligst prøv igjen.');
        }
        
        // Verify påmelding exists
        $pamelding = get_post($pamelding_id);
        if (!$pamelding || $pamelding->post_type !== 'pamelding') {
            wp_die('Påmeldingen ble ikke funnet.');
        }
        
        // Verify user owns this registration
        $pamelding_bruker = get_field('pamelding_bruker', $pamelding_id);
        if ($pamelding_bruker != $user_id) {
            wp_die('Du har ikke tilgang til å avmelde denne påmeldingen.');
        }
        
        // Get arrangement for deadline check
        $arrangement_id = get_field('pamelding_arrangement', $pamelding_id);
        $arrangement = get_post($arrangement_id);
        
        if (!$arrangement) {
            wp_die('Arrangementet ble ikke funnet.');
        }
        
        // Check avmeldingsfrist (48 timer før)
        $dato = get_field('arrangement_dato', $arrangement_id);
        $tid_start = get_field('tidspunkt_start', $arrangement_id);
        $arrangement_datetime = strtotime($dato . ' ' . $tid_start);
        $avmeldingsfrist = $arrangement_datetime - (48 * 60 * 60);
        
        if (time() > $avmeldingsfrist) {
            // Redirect back with error message
            $redirect_url = add_query_arg(
                array(
                    'avmelding_error' => 'frist_passert',
                ),
                get_permalink($arrangement_id)
            );
            wp_redirect($redirect_url);
            exit;
        }
        
        // Update status to avmeldt
        update_field('pamelding_status', 'avmeldt', $pamelding_id);
        
        // Add note with timestamp
        $existing_notes = get_field('pamelding_notater', $pamelding_id) ?: '';
        $new_note = sprintf(
            "\n[%s] Bruker avmeldt seg selv",
            current_time('d.m.Y H:i')
        );
        update_field('pamelding_notater', $existing_notes . $new_note, $pamelding_id);
        
        // Send confirmation email to user
        $user = get_userdata($user_id);
        $this->send_unregistration_email($user, $arrangement);
        
        // Send notification to admin (optional)
        $this->send_admin_notification($user, $arrangement);
        
        // Trigger action for other plugins
        do_action('bimverdi_event_unregistration', $pamelding_id, $arrangement_id, $user_id);
        
        // Redirect with success message
        $redirect_url = add_query_arg(
            array(
                'avmelding_success' => '1',
            ),
            home_url('/min-side/arrangementer/')
        );
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Send unregistration confirmation email to user
     */
    private function send_unregistration_email($user, $arrangement) {
        $dato = get_field('arrangement_dato', $arrangement->ID);
        
        $subject = sprintf('Avmelding bekreftet: %s', $arrangement->post_title);
        
        $message = sprintf(
            "Hei %s,\n\n" .
            "Du er nå avmeldt fra følgende arrangement:\n\n" .
            "<strong>%s</strong>\n" .
            "Dato: %s\n\n" .
            "Hvis dette var en feil, kan du melde deg på igjen via arrangementssiden.\n\n" .
            "Med vennlig hilsen,\n" .
            "BIM Verdi",
            $user->display_name,
            $arrangement->post_title,
            date('j. F Y', strtotime($dato))
        );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: BIM Verdi <' . get_option('admin_email') . '>',
        );
        
        wp_mail($user->user_email, $subject, nl2br($message), $headers);
    }
    
    /**
     * Send notification to admin about unregistration
     */
    private function send_admin_notification($user, $arrangement) {
        $admin_email = get_option('admin_email');
        
        $subject = sprintf('Avmelding: %s fra %s', $user->display_name, $arrangement->post_title);
        
        $message = sprintf(
            "%s har meldt seg av arrangementet \"%s\".\n\n" .
            "E-post: %s\n" .
            "Tidspunkt: %s",
            $user->display_name,
            $arrangement->post_title,
            $user->user_email,
            current_time('d.m.Y H:i')
        );
        
        wp_mail($admin_email, $subject, $message);
    }
}

// Initialize
new BIM_Verdi_Event_Unregistration_Handler();
