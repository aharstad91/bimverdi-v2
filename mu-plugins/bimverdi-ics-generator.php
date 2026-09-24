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
        'permission_callback' => '__return_true',
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
    $filename = sanitize_file_name(bimverdi_ics_tittel($arrangement_id)) . '.ics';
    
    // Output ICS file
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    echo $ics_content;
    exit;
}

/**
 * Ren arrangementstittel for kalender og e-post.
 *
 * WordPress lagrer tittelen HTML-kodet («Nytt &amp; Nyttig»). En kalenderfil
 * er ren tekst, så uten dekoding viser kalenderen bokstavelig «&amp;».
 *
 * @param int $arrangement_id
 * @return string
 */
function bimverdi_ics_tittel($arrangement_id) {
    $tittel = html_entity_decode(get_the_title($arrangement_id), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(wp_strip_all_tags($tittel));
}

/**
 * Tolk ACF-dato + klokkeslett som norsk tid.
 *
 * PHPs standard-tidssone er UTC her (WordPress setter den slik), så både
 * createFromFormat uten tidssone og strtotime ville lest «14:00» som 14:00 UTC.
 * Klokkeslettene i admin er norsk tid, så vi tolker dem eksplisitt i
 * wp_timezone() (Europe/Oslo).
 *
 * @param string $dato ACF-dato (Y-m-d, Ymd eller d.m.Y)
 * @param string $tid  Klokkeslett (H:i eller H:i:s)
 * @return DateTimeImmutable|null
 */
function bimverdi_ics_parse_tid($dato, $tid) {
    if (!$dato || !$tid) {
        return null;
    }

    $tz  = wp_timezone();
    $tid = substr(trim($tid), 0, 5);

    foreach (array('Y-m-d H:i', 'Ymd H:i', 'd.m.Y H:i') as $format) {
        $dt = DateTimeImmutable::createFromFormat('!' . $format, $dato . ' ' . $tid, $tz);
        if ($dt) {
            return $dt;
        }
    }

    try {
        return new DateTimeImmutable($dato . ' ' . $tid, $tz);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Hent start/slutt, sted og beskrivelse for et arrangement — felles for
 * ICS-fila og Google/Outlook-lenkene, så de ikke kan skli fra hverandre.
 *
 * @param int $arrangement_id
 * @return array|WP_Error
 */
function bimverdi_ics_data($arrangement_id) {
    $dato      = get_field('arrangement_dato', $arrangement_id);
    $tid_start = get_field('tidspunkt_start', $arrangement_id);
    $tid_slutt = get_field('tidspunkt_slutt', $arrangement_id);

    if (!$dato || !$tid_start) {
        return new WP_Error('missing_data', 'Arrangementet mangler dato eller tidspunkt', array('status' => 404));
    }

    $start = bimverdi_ics_parse_tid($dato, $tid_start);
    if (!$start) {
        return new WP_Error('invalid_date', 'Ugyldig dato eller tidspunkt', array('status' => 404));
    }

    // Slutt faller tilbake til én time etter start hvis den mangler eller er
    // før start (feilregistrering i admin).
    $slutt = bimverdi_ics_parse_tid($dato, $tid_slutt);
    if (!$slutt || $slutt <= $start) {
        $slutt = $start->modify('+1 hour');
    }

    $format         = get_field('arrangement_type', $arrangement_id);
    $fysisk_adresse = trim((string) get_field('sted_adresse', $arrangement_id));
    $motelenke      = trim((string) get_field('online_lenke', $arrangement_id));
    $har_lenke      = $motelenke && $format !== 'fysisk';

    // Sted: en kort, lesbar tekst. Selve møtelenka står i beskrivelsen.
    // Tidligere lå hele Teams-URL-en her, og kalenderen viste den som et langt
    // uleselig «sted» over beskrivelsen der den også sto.
    $digitalt = (false !== stripos($motelenke, 'teams.microsoft.com') || false !== stripos($motelenke, 'teams.live.com'))
        ? 'Digitalt (Microsoft Teams)'
        : 'Digitalt';

    $sted = '';
    if ($format === 'fysisk') {
        $sted = $fysisk_adresse;
    } elseif ($format === 'digitalt') {
        $sted = $motelenke ? $digitalt : 'Digitalt';
    } elseif ($format === 'hybrid') {
        $sted = implode(' og ', array_filter(array($fysisk_adresse, $motelenke ? lcfirst($digitalt) : '')));
    }

    // Beskrivelse — bevisst minimal: ingen tekst fra post_content, kun metadata og permalink.
    // Bård 2026-04-21: "IKKE LEGGE INNHOLD FRA LINKEN TIL ARRANGEMENTET I DESCRIPTION".
    $beskrivelse = '';
    if ($har_lenke) {
        $beskrivelse .= "Bli med i møtet:\n" . $motelenke . "\n\n";
    }
    $beskrivelse .= "Arrangert av BIM Verdi\nLes mer: " . get_permalink($arrangement_id);

    return array(
        'tittel'      => bimverdi_ics_tittel($arrangement_id),
        'start'       => $start,
        'slutt'       => $slutt,
        'sted'        => $sted,
        'beskrivelse' => $beskrivelse,
        'url'         => get_permalink($arrangement_id),
    );
}

/**
 * Tidspunkt som UTC i ICS-format (20261008T120000Z).
 *
 * UTC med Z er entydig i alle kalendere uten at vi må sende med en
 * VTIMEZONE-blokk. Uten Z («flytende tid») ville en mottaker i en annen
 * tidssone fått arrangementet på feil klokkeslett.
 *
 * @param DateTimeInterface $dt
 * @return string
 */
function bimverdi_ics_utc($dt) {
    return $dt->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
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
        return new WP_Error('not_found', 'Arrangement ikke funnet', array('status' => 404));
    }

    $data = bimverdi_ics_data($arrangement_id);
    if (is_wp_error($data)) {
        return $data;
    }

    $uid = 'arrangement-' . $arrangement_id . '@' . parse_url(home_url(), PHP_URL_HOST);

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
    $ics[] = 'DTSTART:' . bimverdi_ics_utc($data['start']);
    $ics[] = 'DTEND:' . bimverdi_ics_utc($data['slutt']);
    $ics[] = 'SUMMARY:' . bimverdi_ics_escape($data['tittel']);

    if ($data['sted']) {
        $ics[] = 'LOCATION:' . bimverdi_ics_escape($data['sted']);
    }

    $ics[] = 'DESCRIPTION:' . bimverdi_ics_escape($data['beskrivelse']);
    $ics[] = 'URL:' . $data['url'];
    $ics[] = 'STATUS:CONFIRMED';
    $ics[] = 'ORGANIZER;CN=BIM Verdi:mailto:post@bimverdi.no';

    // Add reminder 1 hour before
    $ics[] = 'BEGIN:VALARM';
    $ics[] = 'TRIGGER:-PT1H';
    $ics[] = 'ACTION:DISPLAY';
    $ics[] = 'DESCRIPTION:' . bimverdi_ics_escape('Påminnelse: ' . $data['tittel']);
    $ics[] = 'END:VALARM';

    $ics[] = 'END:VEVENT';
    $ics[] = 'END:VCALENDAR';

    // RFC 5545: hver linje, også den siste, avsluttes med CRLF.
    return implode("\r\n", array_map('bimverdi_ics_fold', $ics)) . "\r\n";
}

/**
 * Brett en ICS-linje til maks 75 byte per linje (RFC 5545 §3.1).
 *
 * Fortsettelseslinjer starter med ett mellomrom. Vi teller byte, ikke tegn,
 * og deler aldri midt i et flerbyte UTF-8-tegn (æ, ø, å).
 *
 * @param string $line
 * @return string
 */
function bimverdi_ics_fold($line) {
    if (strlen($line) <= 75) {
        return $line;
    }

    $linjer = array();
    $gjeldende = '';
    $maks = 75;

    foreach (preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY) as $tegn) {
        if (strlen($gjeldende) + strlen($tegn) > $maks) {
            $linjer[] = $gjeldende;
            $gjeldende = '';
            $maks = 74; // ledende mellomrom teller med i de 75
        }
        $gjeldende .= $tegn;
    }
    $linjer[] = $gjeldende;

    return implode("\r\n ", $linjer);
}

/**
 * Escape text for ICS format
 * 
 * @param string $text
 * @return string
 */
function bimverdi_ics_escape($text) {
    // Rekkefølgen er hele poenget: backslash MÅ escapes først.
    //
    // Sto linjeskift-erstatningen først, traff backslash-erstatningen etterpå
    // også backslashen i \n-sekvensen vi nettopp lagde, og linjeskiftet endte
    // som \\n i fila. Kalenderklienten viser da teksten \n i stedet for å bryte
    // linja, og Outlook drar tegnene med inn i Teams-lenka over. Det er feilen
    // Kjell meldte i uke 38: lenken virker, men «ser veldig rart ut».
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace(array(',', ';'), array('\,', '\;'), $text);
    $text = str_replace(array("\r\n", "\r", "\n"), '\n', $text);
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
    
    $filename = sanitize_file_name(bimverdi_ics_tittel($arrangement_id)) . '.ics';
    
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
    $ics_content = bimverdi_generate_ics($arrangement_id);

    if (is_wp_error($ics_content)) {
        return;
    }

    $user = get_user_by('ID', $user_id);
    $arrangement = get_post($arrangement_id);

    if (!$user || !$arrangement) {
        return;
    }

    // Get event details for email
    $dato          = get_field('arrangement_dato', $arrangement_id);
    $tid_start     = get_field('tidspunkt_start', $arrangement_id);
    $format        = get_field('arrangement_type', $arrangement_id);
    $fysisk_adresse = get_field('sted_adresse', $arrangement_id);
    $motelenke     = get_field('online_lenke', $arrangement_id);

    $dato_formatted = $dato ? wp_date('j. F Y', strtotime($dato)) : '';

    $subject  = 'Påmelding bekreftet: ' . bimverdi_ics_tittel($arrangement_id);
    $ics_url  = rest_url('bimverdi/v1/ics/arrangement/' . $arrangement_id);
    $minside  = home_url('/min-side/arrangementer/');
    $title_e  = esc_html(bimverdi_ics_tittel($arrangement_id));
    $name_e   = esc_html($user->display_name);

    // Location line
    $sted = '';
    if ($format === 'fysisk' && $fysisk_adresse) {
        $sted = '<p>📍 <strong>Sted:</strong> ' . esc_html($fysisk_adresse) . '</p>';
    } elseif ($format === 'digitalt' && $motelenke) {
        $sted = '<p>💻 <strong>Møtelenke:</strong> <a href="' . esc_url($motelenke) . '">' . esc_html($motelenke) . '</a></p>';
    } elseif ($format === 'hybrid') {
        if ($fysisk_adresse) $sted .= '<p>📍 <strong>Sted:</strong> ' . esc_html($fysisk_adresse) . '</p>';
        if ($motelenke)      $sted .= '<p>💻 <strong>Møtelenke:</strong> <a href="' . esc_url($motelenke) . '">' . esc_html($motelenke) . '</a></p>';
    }

    $message = '
<html><body style="font-family:sans-serif;color:#1A1A1A;max-width:560px;margin:0 auto;padding:24px">
<p>Hei ' . $name_e . '!</p>
<p>Din påmelding til <strong>' . $title_e . '</strong> er bekreftet.</p>
<p>📅 <strong>Dato:</strong> ' . esc_html($dato_formatted) . '</p>
<p>🕐 <strong>Tid:</strong> ' . esc_html($tid_start) . '</p>
' . $sted . '
<p style="margin-top:20px">
  📅 <a href="' . esc_url($ics_url) . '" style="color:#FF8B5E;font-weight:600">Last ned kalender-invitasjon (.ics)</a>
</p>
<p style="font-size:13px;color:#888">
  Du må åpne .ics-filen med din kalender-applikasjon for å legge arrangementet inn i kalenderen din.
</p>
<hr style="border:none;border-top:1px solid #eee;margin:24px 0">
<p><a href="' . esc_url($minside) . '">Administrer dine påmeldinger på Min Side</a></p>
<p>Med vennlig hilsen,<br><strong>BIM Verdi</strong></p>
</body></html>';

    wp_mail($user->user_email, $subject, $message, ['Content-Type: text/html; charset=UTF-8']);

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
    if (!get_post($arrangement_id)) {
        return array();
    }

    // Samme data som ICS-fila: norsk tid, ingen tekst fra post_content (Bård 2026-04-21).
    $data = bimverdi_ics_data($arrangement_id);
    if (is_wp_error($data)) {
        return array();
    }

    $title    = rawurlencode($data['tittel']);
    $location = rawurlencode($data['sted']);
    $details  = rawurlencode($data['beskrivelse']);

    // Google tar UTC med Z; Outlook tar ISO 8601 med Z.
    $google_dates  = bimverdi_ics_utc($data['start']) . '/' . bimverdi_ics_utc($data['slutt']);
    $utc           = new DateTimeZone('UTC');
    $outlook_start = $data['start']->setTimezone($utc)->format('Y-m-d\TH:i:s\Z');
    $outlook_end   = $data['slutt']->setTimezone($utc)->format('Y-m-d\TH:i:s\Z');

    return array(
        'ics'     => bimverdi_get_ics_url($arrangement_id),
        'google'  => "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$title}&dates={$google_dates}&details={$details}&location={$location}",
        'outlook' => "https://outlook.live.com/calendar/0/action/compose?subject={$title}&startdt={$outlook_start}&enddt={$outlook_end}&body={$details}&location={$location}",
    );
}
