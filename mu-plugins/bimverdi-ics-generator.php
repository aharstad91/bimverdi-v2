<?php
/**
 * BIM Verdi - ICS Calendar Generator
 * 
 * Generates ICS calendar files for event registrations.
 * Provides download endpoint and email attachment functionality.
 * 
 * @package BIM_Verdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register REST API route for ICS download
 */
add_action('rest_api_init', function() {
    register_rest_route('bimverdi/v1', '/ics/arrangement/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'bimverdi_ics_download',
        'permission_callback' => 'is_user_logged_in',
        'args' => array(
            'id' => array(
                'required' => true,
                'type' => 'integer',
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
            ),
        ),
    ));
});

/**
 * Handle ICS file download
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response|void
 */
function bimverdi_ics_download($request) {
    $arrangement_id = $request->get_param('id');
    
    // Check if arrangement exists
    $arrangement = get_post($arrangement_id);
    if (!$arrangement || $arrangement->post_type !== 'arrangement') {
        return new WP_Error('not_found', 'Arrangement ikke funnet', array('status' => 404));
    }
    
    // Generate ICS content
    $ics_content = bimverdi_generate_ics($arrangement_id);
    
    if (is_wp_error($ics_content)) {
        return $ics_content;
    }
    
    // Clean filename
    $filename = sanitize_file_name($arrangement->post_title) . '.ics';
    
    // Output ICS file
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    echo $ics_content;
    exit;
}

/**
 * Generate ICS content for an arrangement
 * 
 * @param int $arrangement_id Post ID of the arrangement
 * @return string|WP_Error ICS content or error
 */
function bimverdi_generate_ics($arrangement_id) {
    $arrangement = get_post($arrangement_id);
    
    if (!$arrangement) {
        return new WP_Error('not_found', 'Arrangement ikke funnet');
    }
    
    // Get ACF fields
    $dato = get_field('arrangement_dato', $arrangement_id);
    $tid_start = get_field('tidspunkt_start', $arrangement_id);
    $tid_slutt = get_field('tidspunkt_slutt', $arrangement_id);
    $format = get_field('arrangement_format', $arrangement_id);
    $beskrivelse = get_field('arrangement_beskrivelse', $arrangement_id);
    $fysisk_adresse = get_field('fysisk_adresse', $arrangement_id);
    $motelenke = get_field('motelenke', $arrangement_id);
    
    if (!$dato || !$tid_start) {
        return new WP_Error('missing_data', 'Mangler dato eller tidspunkt');
    }
    
    // Parse date and times - try multiple formats
    $start_datetime = DateTime::createFromFormat('Ymd H:i', $dato . ' ' . $tid_start);
    if (!$start_datetime) {
        $start_datetime = DateTime::createFromFormat('Y-m-d H:i', $dato . ' ' . $tid_start);
    }
    if (!$start_datetime) {
        $start_datetime = DateTime::createFromFormat('d.m.Y H:i', $dato . ' ' . $tid_start);
    }
    if (!$start_datetime) {
        $timestamp = strtotime($dato . ' ' . $tid_start);
        if ($timestamp) {
            $start_datetime = new DateTime();
            $start_datetime->setTimestamp($timestamp);
        }
    }
    
    if (!$start_datetime) {
        return new WP_Error('invalid_date', 'Ugyldig dato eller tidspunkt');
    }
    
    // End time defaults to 1 hour after start if not set
    $end_datetime = null;
    if ($tid_slutt) {
        $end_datetime = DateTime::createFromFormat('Ymd H:i', $dato . ' ' . $tid_slutt);
        if (!$end_datetime) {
            $end_datetime = DateTime::createFromFormat('Y-m-d H:i', $dato . ' ' . $tid_slutt);
        }
        if (!$end_datetime) {
            $end_datetime = DateTime::createFromFormat('d.m.Y H:i', $dato . ' ' . $tid_slutt);
        }
        if (!$end_datetime) {
            $timestamp = strtotime($dato . ' ' . $tid_slutt);
            if ($timestamp) {
                $end_datetime = new DateTime();
                $end_datetime->setTimestamp($timestamp);
            }
        }
    }
    
    if (!$end_datetime) {
        $end_datetime = clone $start_datetime;
        $end_datetime->modify('+1 hour');
    }
    
    // Build location string
    $location = '';
    if ($format === 'fysisk' && $fysisk_adresse) {
        $location = $fysisk_adresse;
    } elseif ($format === 'digitalt' && $motelenke) {
        $location = $motelenke;
    } elseif ($format === 'hybrid') {
        $parts = array();
        if ($fysisk_adresse) $parts[] = $fysisk_adresse;
        if ($motelenke) $parts[] = 'Online: ' . $motelenke;
        $location = implode(' | ', $parts);
    }
    
    // Build description
    $description_parts = array();
    if ($beskrivelse) {
        $description_parts[] = wp_strip_all_tags($beskrivelse);
    }
    if ($motelenke && $format !== 'fysisk') {
        $description_parts[] = "\n\nMøtelenke: " . $motelenke;
    }
    $description_parts[] = "\n\nArrangert av BIM Verdi";
    $description_parts[] = "Les mer: " . get_permalink($arrangement_id);
    
    $description = implode('', $description_parts);
    
    // Generate unique ID for event
    $uid = 'arrangement-' . $arrangement_id . '@' . parse_url(home_url(), PHP_URL_HOST);
    
    // Build ICS content
    $ics = array();
    $ics[] = 'BEGIN:VCALENDAR';
    $ics[] = 'VERSION:2.0';
    $ics[] = 'PRODID:-//BIM Verdi//Arrangementer//NO';
    $ics[] = 'CALSCALE:GREGORIAN';
    $ics[] = 'METHOD:PUBLISH';
    $ics[] = 'X-WR-CALNAME:BIM Verdi Arrangement';
    $ics[] = 'BEGIN:VEVENT';
    $ics[] = 'UID:' . $uid;
    $ics[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
    $ics[] = 'DTSTART:' . $start_datetime->format('Ymd\THis');
    $ics[] = 'DTEND:' . $end_datetime->format('Ymd\THis');
    $ics[] = 'SUMMARY:' . bimverdi_ics_escape($arrangement->post_title);
    
    if ($location) {
        $ics[] = 'LOCATION:' . bimverdi_ics_escape($location);
    }
    
    if ($description) {
        $ics[] = 'DESCRIPTION:' . bimverdi_ics_escape($description);
    }
    
    $ics[] = 'URL:' . get_permalink($arrangement_id);
    $ics[] = 'STATUS:CONFIRMED';
    $ics[] = 'ORGANIZER;CN=BIM Verdi:mailto:post@bimverdi.no';
    
    // Add reminder 1 hour before
    $ics[] = 'BEGIN:VALARM';
    $ics[] = 'TRIGGER:-PT1H';
    $ics[] = 'ACTION:DISPLAY';
    $ics[] = 'DESCRIPTION:Påminnelse: ' . bimverdi_ics_escape($arrangement->post_title);
    $ics[] = 'END:VALARM';
    
    $ics[] = 'END:VEVENT';
    $ics[] = 'END:VCALENDAR';
    
    return implode("\r\n", $ics);
}

/**
 * Escape text for ICS format
 * 
 * @param string $text
 * @return string
 */
function bimverdi_ics_escape($text) {
    $text = str_replace(array("\r\n", "\r", "\n"), '\n', $text);
    $text = str_replace(array(',', ';', '\\'), array('\,', '\;', '\\\\'), $text);
    return $text;
}

/**
 * Get ICS download URL for an arrangement
 * 
 * @param int $arrangement_id
 * @return string
 */
function bimverdi_get_ics_url($arrangement_id) {
    return rest_url('bimverdi/v1/ics/arrangement/' . $arrangement_id);
}

/**
 * Generate ICS file path for email attachment
 * 
 * @param int $arrangement_id
 * @return string|false File path or false on error
 */
function bimverdi_generate_ics_file($arrangement_id) {
    $ics_content = bimverdi_generate_ics($arrangement_id);
    
    if (is_wp_error($ics_content)) {
        return false;
    }
    
    $arrangement = get_post($arrangement_id);
    $filename = sanitize_file_name($arrangement->post_title) . '.ics';
    
    $upload_dir = wp_upload_dir();
    $ics_dir = $upload_dir['basedir'] . '/ics-temp/';
    
    // Create directory if needed
    if (!file_exists($ics_dir)) {
        wp_mkdir_p($ics_dir);
        // Add .htaccess to prevent direct access
        file_put_contents($ics_dir . '.htaccess', 'deny from all');
    }
    
    $file_path = $ics_dir . $filename;
    
    if (file_put_contents($file_path, $ics_content)) {
        return $file_path;
    }
    
    return false;
}

/**
 * Hook into påmelding creation to send ICS attachment
 */
add_action('bimverdi_pamelding_created', function($pamelding_id, $arrangement_id, $user_id) {
    // Generate ICS file
    $ics_file = bimverdi_generate_ics_file($arrangement_id);
    
    if (!$ics_file) {
        return;
    }
    
    $user = get_user_by('ID', $user_id);
    $arrangement = get_post($arrangement_id);
    
    if (!$user || !$arrangement) {
        return;
    }
    
    // Get event details for email
    $dato = get_field('arrangement_dato', $arrangement_id);
    $tid_start = get_field('tidspunkt_start', $arrangement_id);
    $format = get_field('arrangement_format', $arrangement_id);
    $fysisk_adresse = get_field('fysisk_adresse', $arrangement_id);
    $motelenke = get_field('motelenke', $arrangement_id);
    
    // Format date nicely
    $dato_formatted = '';
    if ($dato) {
        $dato_obj = DateTime::createFromFormat('Ymd', $dato);
        if ($dato_obj) {
            $dato_formatted = $dato_obj->format('j. F Y');
        }
    }
    
    // Build email
    $subject = 'Påmelding bekreftet: ' . $arrangement->post_title;
    
    $message = "Hei {$user->display_name}!\n\n";
    $message .= "Din påmelding til {$arrangement->post_title} er bekreftet.\n\n";
    $message .= "📅 Dato: {$dato_formatted}\n";
    $message .= "🕐 Tid: {$tid_start}\n";
    
    if ($format === 'fysisk' && $fysisk_adresse) {
        $message .= "📍 Sted: {$fysisk_adresse}\n";
    } elseif ($format === 'digitalt' && $motelenke) {
        $message .= "💻 Møtelenke: {$motelenke}\n";
    } elseif ($format === 'hybrid') {
        if ($fysisk_adresse) {
            $message .= "📍 Sted: {$fysisk_adresse}\n";
        }
        if ($motelenke) {
            $message .= "💻 Møtelenke: {$motelenke}\n";
        }
    }
    
    $message .= "\n📎 Vi har lagt ved en kalenderfil (.ics) som du kan importere i din kalender.\n\n";
    $message .= "Du finner mer informasjon og kan administrere dine påmeldinger på Min Side:\n";
    $message .= home_url('/min-side/arrangementer/') . "\n\n";
    $message .= "Med vennlig hilsen,\nBIM Verdi";
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    $attachments = array($ics_file);
    
    wp_mail($user->user_email, $subject, $message, $headers, $attachments);
    
    // Clean up temp file after 1 hour
    wp_schedule_single_event(time() + 3600, 'bimverdi_cleanup_ics_file', array($ics_file));
    
}, 10, 3);

/**
 * Cleanup temporary ICS files
 */
add_action('bimverdi_cleanup_ics_file', function($file_path) {
    if (file_exists($file_path)) {
        unlink($file_path);
    }
});

/**
 * Add "Legg til i kalender" links helper
 * 
 * @param int $arrangement_id
 * @return array Links for various calendar services
 */
function bimverdi_get_calendar_links($arrangement_id) {
    $arrangement = get_post($arrangement_id);
    
    if (!$arrangement) {
        return array();
    }
    
    $dato = get_field('arrangement_dato', $arrangement_id);
    $tid_start = get_field('tidspunkt_start', $arrangement_id);
    $tid_slutt = get_field('tidspunkt_slutt', $arrangement_id);
    $fysisk_adresse = get_field('fysisk_adresse', $arrangement_id);
    $beskrivelse = wp_strip_all_tags(get_field('arrangement_beskrivelse', $arrangement_id) ?: '');
    
    if (!$dato || !$tid_start) {
        return array();
    }
    
    // Parse dates - try multiple formats (ACF may return different formats)
    $start = DateTime::createFromFormat('Ymd H:i', $dato . ' ' . $tid_start);
    if (!$start) {
        // Try Y-m-d format
        $start = DateTime::createFromFormat('Y-m-d H:i', $dato . ' ' . $tid_start);
    }
    if (!$start) {
        // Try d.m.Y format (Norwegian)
        $start = DateTime::createFromFormat('d.m.Y H:i', $dato . ' ' . $tid_start);
    }
    if (!$start) {
        // Try strtotime as fallback
        $timestamp = strtotime($dato . ' ' . $tid_start);
        if ($timestamp) {
            $start = new DateTime();
            $start->setTimestamp($timestamp);
        }
    }
    
    // If still can't parse, return empty
    if (!$start) {
        return array();
    }
    
    $end = null;
    if ($tid_slutt) {
        $end = DateTime::createFromFormat('Ymd H:i', $dato . ' ' . $tid_slutt);
        if (!$end) {
            $end = DateTime::createFromFormat('Y-m-d H:i', $dato . ' ' . $tid_slutt);
        }
        if (!$end) {
            $end = DateTime::createFromFormat('d.m.Y H:i', $dato . ' ' . $tid_slutt);
        }
        if (!$end) {
            $timestamp = strtotime($dato . ' ' . $tid_slutt);
            if ($timestamp) {
                $end = new DateTime();
                $end->setTimestamp($timestamp);
            }
        }
    }
    
    if (!$end) {
        $end = (clone $start)->modify('+1 hour');
    }
    
    $title = urlencode($arrangement->post_title);
    $location = urlencode($fysisk_adresse ?: '');
    $details = urlencode(substr($beskrivelse, 0, 500) . "\n\nLes mer: " . get_permalink($arrangement_id));
    
    // Format for Google Calendar
    $google_dates = $start->format('Ymd\THis') . '/' . $end->format('Ymd\THis');
    
    // Format for Outlook
    $outlook_start = $start->format('Y-m-d\TH:i:s');
    $outlook_end = $end->format('Y-m-d\TH:i:s');
    
    return array(
        'ics' => bimverdi_get_ics_url($arrangement_id),
        'google' => "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$title}&dates={$google_dates}&details={$details}&location={$location}",
        'outlook' => "https://outlook.live.com/calendar/0/action/compose?subject={$title}&startdt={$outlook_start}&enddt={$outlook_end}&body={$details}&location={$location}",
    );
}
