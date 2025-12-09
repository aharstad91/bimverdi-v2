<?php
/**
 * Event Registration Form Handler
 * 
 * Handles "Påmelding arrangement" Gravity Forms submissions
 * Creates Påmelding (Registration) posts linking user to event
 * 
 * @package BIM_Verdi_Core
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Event_Registration_Form_Handler {
    
    const FORM_ID = 9; // "[Bruker] - Påmelding arrangement" form ID in Gravity Forms
    
    /**
     * Initialize the handler
     */
    public function __construct() {
        // Handle form submission
        add_action('gform_after_submission_' . self::FORM_ID, array($this, 'handle_submission'), 10, 2);
        
        // Pre-populate fields
        add_filter('gform_field_value_user_id', array($this, 'populate_user_id'));
        add_filter('gform_field_value_user_name', array($this, 'populate_user_name'));
        add_filter('gform_field_value_user_email', array($this, 'populate_user_email'));
        add_filter('gform_field_value_user_phone', array($this, 'populate_user_phone'));
        add_filter('gform_field_value_company_name', array($this, 'populate_company_name'));
        add_filter('gform_field_value_arrangement_id', array($this, 'populate_arrangement_id'));
        add_filter('gform_field_value_arrangement_title', array($this, 'populate_arrangement_title'));
        
        // Validation - check capacity before submission
        add_filter('gform_validation_' . self::FORM_ID, array($this, 'validate_capacity'));
        
        // Confirmation message customization
        add_filter('gform_confirmation_' . self::FORM_ID, array($this, 'custom_confirmation'), 10, 4);
        
        // Add ICS calendar file to notification
        add_filter('gform_notification_' . self::FORM_ID, array($this, 'attach_ics_file'), 10, 3);
    }
    
    /**
     * Populate user_id parameter
     */
    public function populate_user_id() {
        return get_current_user_id();
    }
    
    /**
     * Populate user_name parameter
     */
    public function populate_user_name() {
        $user = wp_get_current_user();
        return $user->ID ? $user->display_name : '';
    }
    
    /**
     * Populate user_email parameter
     */
    public function populate_user_email() {
        $user = wp_get_current_user();
        return $user->ID ? $user->user_email : '';
    }
    
    /**
     * Populate user_phone parameter
     */
    public function populate_user_phone() {
        $user_id = get_current_user_id();
        if (!$user_id) return '';
        
        return get_user_meta($user_id, 'telefon', true);
    }
    
    /**
     * Populate company_name parameter
     */
    public function populate_company_name() {
        $user_id = get_current_user_id();
        if (!$user_id) return '';
        
        $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
        if (!$company_id) return '';
        
        $company = get_post($company_id);
        return $company ? $company->post_title : '';
    }
    
    /**
     * Populate arrangement_id from URL or current post
     */
    public function populate_arrangement_id() {
        // Check URL parameter first
        if (isset($_GET['arrangement_id'])) {
            return intval($_GET['arrangement_id']);
        }
        
        // Check POST data (for form submission)
        if (isset($_POST['arrangement_id'])) {
            return intval($_POST['arrangement_id']);
        }
        
        // Check current post if we're on an arrangement
        global $post;
        if ($post && $post->post_type === 'arrangement') {
            return $post->ID;
        }
        
        // Check referrer URL
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
            $post_id = url_to_postid($referer);
            if ($post_id && get_post_type($post_id) === 'arrangement') {
                return $post_id;
            }
        }
        
        return 0;
    }
    
    /**
     * Populate arrangement_title
     */
    public function populate_arrangement_title() {
        $arrangement_id = $this->populate_arrangement_id();
        if (!$arrangement_id) return '';
        
        $arrangement = get_post($arrangement_id);
        return $arrangement ? $arrangement->post_title : '';
    }
    
    /**
     * Validate capacity before allowing submission
     */
    public function validate_capacity($validation_result) {
        $form = $validation_result['form'];
        
        // Get arrangement ID from submission
        $arrangement_id = 0;
        foreach ($form['fields'] as $field) {
            if ($field->inputName === 'arrangement_id' || strpos($field->cssClass, 'arrangement-id') !== false) {
                $arrangement_id = intval(rgpost("input_{$field->id}"));
                break;
            }
        }
        
        if (!$arrangement_id) {
            // Try to get from URL parameter
            $arrangement_id = isset($_GET['arrangement_id']) ? intval($_GET['arrangement_id']) : 0;
        }
        
        if (!$arrangement_id) {
            // Try to get from POST data
            $arrangement_id = isset($_POST['arrangement_id']) ? intval($_POST['arrangement_id']) : 0;
        }
        
        if (!$arrangement_id) {
            // Try to get from referrer
            if (isset($_SERVER['HTTP_REFERER'])) {
                $referer = $_SERVER['HTTP_REFERER'];
                // Parse arrangement_id from URL if present
                $parsed_url = parse_url($referer);
                if (isset($parsed_url['query'])) {
                    parse_str($parsed_url['query'], $query_params);
                    if (isset($query_params['arrangement_id'])) {
                        $arrangement_id = intval($query_params['arrangement_id']);
                    }
                }
                // Or try to get from path
                if (!$arrangement_id) {
                    $post_id = url_to_postid($referer);
                    if ($post_id && get_post_type($post_id) === 'arrangement') {
                        $arrangement_id = $post_id;
                    }
                }
            }
        }
        
        if (!$arrangement_id) {
            $validation_result['is_valid'] = false;
            // Find first field to attach error
            foreach ($form['fields'] as &$field) {
                if ($field->type !== 'hidden') {
                    $field->failed_validation = true;
                    $field->validation_message = 'Kunne ikke identifisere arrangementet. Vennligst prøv igjen.';
                    break;
                }
            }
            $validation_result['form'] = $form;
            return $validation_result;
        }
        
        // Check if user is already registered
        $user_id = get_current_user_id();
        $existing = get_posts(array(
            'post_type' => 'pamelding',
            'posts_per_page' => 1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'pamelding_arrangement',
                    'value' => $arrangement_id,
                ),
                array(
                    'key' => 'pamelding_bruker',
                    'value' => $user_id,
                ),
                array(
                    'key' => 'pamelding_status',
                    'value' => 'aktiv',
                ),
            ),
        ));
        
        if (!empty($existing)) {
            $validation_result['is_valid'] = false;
            foreach ($form['fields'] as &$field) {
                if ($field->type !== 'hidden') {
                    $field->failed_validation = true;
                    $field->validation_message = 'Du er allerede påmeldt dette arrangementet.';
                    break;
                }
            }
            $validation_result['form'] = $form;
            return $validation_result;
        }
        
        // Check capacity
        $maks_deltakere = get_field('maks_deltakere', $arrangement_id);
        if ($maks_deltakere) {
            $current_registrations = get_posts(array(
                'post_type' => 'pamelding',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => array(
                    array(
                        'key' => 'pamelding_arrangement',
                        'value' => $arrangement_id,
                    ),
                    array(
                        'key' => 'pamelding_status',
                        'value' => 'aktiv',
                    ),
                ),
            ));
            
            if (count($current_registrations) >= $maks_deltakere) {
                $validation_result['is_valid'] = false;
                foreach ($form['fields'] as &$field) {
                    if ($field->type !== 'hidden') {
                        $field->failed_validation = true;
                        $field->validation_message = 'Beklager, dette arrangementet er fulltegnet.';
                        break;
                    }
                }
                $validation_result['form'] = $form;
                return $validation_result;
            }
        }
        
        // Check registration deadline
        $pamelding_frist = get_field('pamelding_frist', $arrangement_id);
        $dato = get_field('arrangement_dato', $arrangement_id);
        $frist = $pamelding_frist ?: $dato;
        
        if (strtotime($frist) < strtotime('today')) {
            $validation_result['is_valid'] = false;
            foreach ($form['fields'] as &$field) {
                if ($field->type !== 'hidden') {
                    $field->failed_validation = true;
                    $field->validation_message = 'Påmeldingsfristen for dette arrangementet er passert.';
                    break;
                }
            }
            $validation_result['form'] = $form;
            return $validation_result;
        }
        
        return $validation_result;
    }
    
    /**
     * Handle form submission - create Påmelding post
     */
    public function handle_submission($entry, $form) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            $this->log_error('No user logged in during registration submission');
            return;
        }
        
        // Get arrangement ID from entry
        $arrangement_id = 0;
        foreach ($form['fields'] as $field) {
            if ($field->inputName === 'arrangement_id' || strpos($field->cssClass, 'arrangement-id') !== false) {
                $arrangement_id = intval(rgar($entry, $field->id));
                break;
            }
        }
        
        if (!$arrangement_id) {
            $this->log_error('No arrangement ID found in submission');
            return;
        }
        
        // Double-check not already registered
        $existing = get_posts(array(
            'post_type' => 'pamelding',
            'posts_per_page' => 1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'pamelding_arrangement',
                    'value' => $arrangement_id,
                ),
                array(
                    'key' => 'pamelding_bruker',
                    'value' => $user_id,
                ),
                array(
                    'key' => 'pamelding_status',
                    'value' => 'aktiv',
                ),
            ),
        ));
        
        if (!empty($existing)) {
            $this->log_error('User already registered - duplicate prevented');
            return;
        }
        
        // Get arrangement and user info
        $arrangement = get_post($arrangement_id);
        $user = get_userdata($user_id);
        
        if (!$arrangement || !$user) {
            $this->log_error('Invalid arrangement or user');
            return;
        }
        
        // Create Påmelding post
        $pamelding_data = array(
            'post_title' => sprintf('%s - %s', $user->display_name, $arrangement->post_title),
            'post_type' => 'pamelding',
            'post_status' => 'publish',
        );
        
        $pamelding_id = wp_insert_post($pamelding_data);
        
        if (is_wp_error($pamelding_id)) {
            $this->log_error('Failed to create påmelding: ' . $pamelding_id->get_error_message());
            return;
        }
        
        // Set ACF fields
        update_field('pamelding_bruker', $user_id, $pamelding_id);
        update_field('pamelding_arrangement', $arrangement_id, $pamelding_id);
        update_field('tidspunkt_pameldt', current_time('Y-m-d H:i:s'), $pamelding_id);
        update_field('pamelding_status', 'aktiv', $pamelding_id);
        
        // Get optional phone from entry
        foreach ($form['fields'] as $field) {
            if ($field->inputName === 'user_phone' || strpos($field->label, 'Telefon') !== false) {
                $phone = rgar($entry, $field->id);
                if ($phone) {
                    update_field('pamelding_notater', "Telefon: $phone", $pamelding_id);
                }
                break;
            }
        }
        
        // Store entry ID for reference
        update_post_meta($pamelding_id, '_gf_entry_id', $entry['id']);
        
        // Log success
        if (function_exists('GFCommon')) {
            GFCommon::log_debug("BIM Verdi Event Registration: Created påmelding #{$pamelding_id} for user #{$user_id} to arrangement #{$arrangement_id}");
        }
        
        // Trigger action for other plugins
        do_action('bimverdi_event_registration_created', $pamelding_id, $arrangement_id, $user_id);
    }
    
    /**
     * Custom confirmation message
     */
    public function custom_confirmation($confirmation, $form, $entry, $ajax) {
        // Get arrangement details for confirmation
        $arrangement_id = 0;
        
        // Try getting from entry field 2 (arrangement_id hidden field)
        if (!empty($entry['2'])) {
            $arrangement_id = intval($entry['2']);
        }
        
        // Fallback: try getting from entry data via field name
        if (!$arrangement_id) {
            foreach ($form['fields'] as $field) {
                if ($field->inputName === 'arrangement_id' || strpos($field->cssClass, 'arrangement-id') !== false) {
                    $arrangement_id = intval(rgar($entry, $field->id));
                    break;
                }
            }
        }
        
        // Fallback: check POST data
        if (!$arrangement_id && isset($_POST['input_2'])) {
            $arrangement_id = intval($_POST['input_2']);
        }
        
        // Fallback: check URL parameter
        if (!$arrangement_id && isset($_GET['arrangement_id'])) {
            $arrangement_id = intval($_GET['arrangement_id']);
        }
        
        // Fallback: parse from HTTP_REFERER
        if (!$arrangement_id && isset($_SERVER['HTTP_REFERER'])) {
            $referer_url = $_SERVER['HTTP_REFERER'];
            $arrangement_id = url_to_postid($referer_url);
        }
        
        // Build custom message if we have a valid arrangement
        if ($arrangement_id && get_post_type($arrangement_id) === 'arrangement') {
            $arrangement = get_post($arrangement_id);
            $dato = get_field('arrangement_dato', $arrangement_id);
            $tid = get_field('tidspunkt_start', $arrangement_id);
            
            $message = sprintf(
                '<div class="gform_confirmation_wrapper">
                    <wa-alert variant="success" open>
                        <wa-icon slot="icon" name="circle-check" library="fa"></wa-icon>
                        <strong>Du er nå påmeldt!</strong><br>
                        %s - %s kl. %s<br><br>
                        Du vil motta en bekreftelse på e-post med alle detaljer.
                    </wa-alert>
                </div>',
                esc_html($arrangement->post_title),
                date_i18n('j. F Y', strtotime($dato)),
                esc_html($tid)
            );
            
            // Return the custom message directly as string (Gravity Forms handles this)
            return $message;
        }
        
        return $confirmation;
    }
    
    /**
     * Attach ICS calendar file to notification
     */
    public function attach_ics_file($notification, $form, $entry) {
        // Only attach to user notification (not admin)
        if ($notification['name'] !== 'User Notification' && strpos($notification['name'], 'Bruker') === false) {
            return $notification;
        }
        
        // Get arrangement ID
        $arrangement_id = 0;
        foreach ($form['fields'] as $field) {
            if ($field->inputName === 'arrangement_id' || strpos($field->cssClass, 'arrangement-id') !== false) {
                $arrangement_id = intval(rgar($entry, $field->id));
                break;
            }
        }
        
        if (!$arrangement_id) {
            return $notification;
        }
        
        // Generate ICS file
        $ics_content = $this->generate_ics($arrangement_id);
        if (!$ics_content) {
            return $notification;
        }
        
        // Save ICS to temp file
        $upload_dir = wp_upload_dir();
        $ics_filename = 'arrangement-' . $arrangement_id . '-' . time() . '.ics';
        $ics_path = $upload_dir['basedir'] . '/temp/' . $ics_filename;
        
        // Ensure temp directory exists
        wp_mkdir_p($upload_dir['basedir'] . '/temp/');
        
        file_put_contents($ics_path, $ics_content);
        
        // Add attachment
        if (!isset($notification['attachments'])) {
            $notification['attachments'] = array();
        }
        $notification['attachments'][] = $ics_path;
        
        // Schedule cleanup
        wp_schedule_single_event(time() + 3600, 'bimverdi_cleanup_ics_file', array($ics_path));
        
        return $notification;
    }
    
    /**
     * Generate ICS calendar content
     */
    private function generate_ics($arrangement_id) {
        $arrangement = get_post($arrangement_id);
        if (!$arrangement) return null;
        
        $dato = get_field('arrangement_dato', $arrangement_id);
        $tid_start = get_field('tidspunkt_start', $arrangement_id);
        $tid_slutt = get_field('tidspunkt_slutt', $arrangement_id) ?: date('H:i', strtotime($tid_start) + 7200);
        $format = get_field('arrangement_format', $arrangement_id);
        $fysisk_adresse = get_field('fysisk_adresse', $arrangement_id);
        $motelenke = get_field('motelenke', $arrangement_id);
        $beskrivelse = get_field('arrangement_beskrivelse', $arrangement_id);
        
        // Build location string
        $location = '';
        if ($format === 'fysisk' || $format === 'hybrid') {
            $location = $fysisk_adresse;
        }
        if (($format === 'digitalt' || $format === 'hybrid') && $motelenke) {
            $location .= ($location ? ' / ' : '') . $motelenke;
        }
        
        // Format dates for ICS
        $dtstart = date('Ymd', strtotime($dato)) . 'T' . str_replace(':', '', $tid_start) . '00';
        $dtend = date('Ymd', strtotime($dato)) . 'T' . str_replace(':', '', $tid_slutt) . '00';
        $dtstamp = gmdate('Ymd\THis\Z');
        $uid = $arrangement_id . '-' . time() . '@' . parse_url(home_url(), PHP_URL_HOST);
        
        // Clean description
        $clean_description = wp_strip_all_tags($beskrivelse);
        $clean_description = str_replace(array("\r\n", "\r", "\n"), '\n', $clean_description);
        
        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//BIM Verdi//Event//NO\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "DTSTART;TZID=Europe/Oslo:$dtstart\r\n";
        $ics .= "DTEND;TZID=Europe/Oslo:$dtend\r\n";
        $ics .= "DTSTAMP:$dtstamp\r\n";
        $ics .= "UID:$uid\r\n";
        $ics .= "SUMMARY:" . $this->ics_escape($arrangement->post_title) . "\r\n";
        $ics .= "DESCRIPTION:" . $this->ics_escape($clean_description) . "\r\n";
        if ($location) {
            $ics .= "LOCATION:" . $this->ics_escape($location) . "\r\n";
        }
        $ics .= "URL:" . get_permalink($arrangement_id) . "\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";
        
        return $ics;
    }
    
    /**
     * Escape special characters for ICS format
     */
    private function ics_escape($string) {
        $string = str_replace('\\', '\\\\', $string);
        $string = str_replace(',', '\,', $string);
        $string = str_replace(';', '\;', $string);
        return $string;
    }
    
    /**
     * Log error message
     */
    private function log_error($message) {
        if (function_exists('GFCommon')) {
            GFCommon::log_error("BIM Verdi Event Registration: $message");
        }
        error_log("BIM Verdi Event Registration: $message");
    }
}

/**
 * Cleanup ICS temp files
 */
add_action('bimverdi_cleanup_ics_file', function($file_path) {
    if (file_exists($file_path)) {
        unlink($file_path);
    }
});
