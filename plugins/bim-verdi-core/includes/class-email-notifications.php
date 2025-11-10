<?php
/**
 * Email Notifications for BIM Verdi
 *
 * @package BIMVerdiCore
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Email_Notifications {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Email functionality will be implemented later
    }
    
    /**
     * Send welcome email to new member
     */
    public static function send_welcome_email($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        $to = $user->user_email;
        $subject = 'Velkommen til BIM Verdi - Din Min Side er klar';
        
        $message = "Hei {$user->first_name},\n\n";
        $message .= "Velkommen til BIM Verdi!\n\n";
        $message .= "Din bedriftsprofil er nå opprettet og du kan logge inn på Min Side.\n\n";
        $message .= "Logg inn her: " . wp_login_url() . "\n\n";
        $message .= "På Min Side kan du:\n";
        $message .= "- Redigere bedriftsprofilen\n";
        $message .= "- Registrere verktøy\n";
        $message .= "- Skrive artikler\n";
        $message .= "- Sende inn prosjektidéer\n";
        $message .= "- Melde deg på arrangementer\n\n";
        $message .= "Har du spørsmål? Kontakt oss på post@bimverdi.no\n\n";
        $message .= "Vennlig hilsen,\n";
        $message .= "BIM Verdi";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send notification to admin when new content is submitted
     */
    public static function send_admin_notification($type, $title, $author_name) {
        $admin_email = get_option('admin_email');
        $subject = "Nytt innhold til gjennomgang: {$type}";
        
        $message = "Nytt {$type} er sendt inn til gjennomgang.\n\n";
        $message .= "Tittel: {$title}\n";
        $message .= "Fra: {$author_name}\n\n";
        $message .= "Logg inn i WordPress admin for å se innholdet.";
        
        return wp_mail($admin_email, $subject, $message);
    }
}
