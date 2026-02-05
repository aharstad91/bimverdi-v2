<?php
/**
 * BIM Verdi - Resend Email Integration
 *
 * Erstatter WordPress sin standard wp_mail() med Resend API.
 * Konfigurer API-nøkkel i wp-config.php:
 *
 * define('BIMVERDI_RESEND_API_KEY', 're_xxxxxxxxx');
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resend konfigurasjon
 *
 * MERK: Endre til noreply@bimverdi.no når domenet er verifisert i Resend.
 * Før det fungerer kun onboarding@resend.dev (Resend sandbox).
 */
define('BIMVERDI_RESEND_FROM_EMAIL', 'onboarding@resend.dev'); // TODO: Endre til noreply@bimverdi.no
define('BIMVERDI_RESEND_FROM_NAME', 'BIM Verdi');
define('BIMVERDI_RESEND_REPLY_TO', 'andreas@aharstad.no'); // Midlertidig - endre til hei@bimverdi.no

/**
 * Override wp_mail() til å bruke Resend API
 */
if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message, $headers = '', $attachments = array()) {
        // Sjekk om Resend er konfigurert
        if (!defined('BIMVERDI_RESEND_API_KEY') || empty(BIMVERDI_RESEND_API_KEY)) {
            // Fallback til PHP mail() hvis Resend ikke er konfigurert
            error_log('BIM Verdi: Resend API key ikke konfigurert, bruker PHP mail()');
            return bimverdi_fallback_mail($to, $subject, $message, $headers);
        }

        // Parse headers
        $parsed_headers = bimverdi_parse_email_headers($headers);

        // Bestem content type
        $content_type = isset($parsed_headers['content-type'])
            ? $parsed_headers['content-type']
            : 'text/plain';

        // Bestem from
        $from_email = isset($parsed_headers['from'])
            ? $parsed_headers['from']
            : BIMVERDI_RESEND_FROM_EMAIL;

        $from_name = BIMVERDI_RESEND_FROM_NAME;

        // Hvis from inneholder navn, parse det
        if (preg_match('/^(.+)\s*<(.+)>$/', $from_email, $matches)) {
            $from_name = trim($matches[1]);
            $from_email = trim($matches[2]);
        }

        // Reply-to
        $reply_to = isset($parsed_headers['reply-to'])
            ? $parsed_headers['reply-to']
            : BIMVERDI_RESEND_REPLY_TO;

        // Normaliser mottakere til array
        if (!is_array($to)) {
            $to = array_map('trim', explode(',', $to));
        }

        // Bygg Resend payload
        $payload = [
            'from' => sprintf('%s <%s>', $from_name, $from_email),
            'to' => $to,
            'subject' => $subject,
            'reply_to' => $reply_to,
        ];

        // Sett innhold basert på content type
        if (strpos($content_type, 'text/html') !== false) {
            $payload['html'] = $message;
        } else {
            $payload['text'] = $message;
        }

        // CC og BCC
        if (!empty($parsed_headers['cc'])) {
            $payload['cc'] = is_array($parsed_headers['cc'])
                ? $parsed_headers['cc']
                : array_map('trim', explode(',', $parsed_headers['cc']));
        }

        if (!empty($parsed_headers['bcc'])) {
            $payload['bcc'] = is_array($parsed_headers['bcc'])
                ? $parsed_headers['bcc']
                : array_map('trim', explode(',', $parsed_headers['bcc']));
        }

        // Send via Resend API
        $response = wp_remote_post('https://api.resend.com/emails', [
            'headers' => [
                'Authorization' => 'Bearer ' . BIMVERDI_RESEND_API_KEY,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($payload),
            'timeout' => 30,
        ]);

        // Håndter respons
        if (is_wp_error($response)) {
            error_log('BIM Verdi Resend feil: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code >= 200 && $status_code < 300) {
            // Logg suksess i debug-modus
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'BIM Verdi Resend: E-post sendt til %s (ID: %s)',
                    implode(', ', $to),
                    $body['id'] ?? 'ukjent'
                ));
            }
            return true;
        }

        // Logg feil
        error_log(sprintf(
            'BIM Verdi Resend feil (%d): %s',
            $status_code,
            $body['message'] ?? 'Ukjent feil'
        ));

        return false;
    }
}

/**
 * Parse e-post headers til array
 */
function bimverdi_parse_email_headers($headers) {
    $parsed = [];

    if (empty($headers)) {
        return $parsed;
    }

    if (is_string($headers)) {
        $headers = explode("\n", str_replace("\r\n", "\n", $headers));
    }

    foreach ($headers as $header) {
        if (strpos($header, ':') === false) {
            continue;
        }

        list($name, $value) = explode(':', $header, 2);
        $name = strtolower(trim($name));
        $value = trim($value);

        $parsed[$name] = $value;
    }

    return $parsed;
}

/**
 * Fallback til PHP mail() når Resend ikke er konfigurert
 */
function bimverdi_fallback_mail($to, $subject, $message, $headers) {
    $headers_string = '';

    if (is_array($headers)) {
        $headers_string = implode("\r\n", $headers);
    } else {
        $headers_string = $headers;
    }

    // Legg til standard headers hvis de mangler
    if (strpos(strtolower($headers_string), 'from:') === false) {
        $headers_string .= "\r\nFrom: " . BIMVERDI_RESEND_FROM_NAME . ' <' . BIMVERDI_RESEND_FROM_EMAIL . '>';
    }

    if (is_array($to)) {
        $to = implode(', ', $to);
    }

    return @mail($to, $subject, $message, $headers_string);
}

/**
 * Admin-varsling hvis Resend ikke er konfigurert
 */
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (defined('BIMVERDI_RESEND_API_KEY') && !empty(BIMVERDI_RESEND_API_KEY)) {
        return;
    }

    // Bare vis på dashboard og innstillinger
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->id, ['dashboard', 'options-general'])) {
        return;
    }

    echo '<div class="notice notice-warning"><p>';
    echo '<strong>BIM Verdi:</strong> Resend API-nøkkel er ikke konfigurert. ';
    echo 'Legg til <code>define(\'BIMVERDI_RESEND_API_KEY\', \'re_xxx\');</code> i wp-config.php';
    echo '</p></div>';
});

/**
 * Test-funksjon for å verifisere Resend-oppsett
 * Bruk: bimverdi_test_resend_email('din@epost.no')
 */
function bimverdi_test_resend_email($to_email) {
    $subject = 'Test fra BIM Verdi - Resend fungerer!';
    $message = '
    <html>
    <body style="font-family: system-ui, sans-serif; padding: 20px;">
        <h2>E-post test</h2>
        <p>Gratulerer! Resend-integrasjonen fungerer.</p>
        <p style="color: #666;">Sendt: ' . date('Y-m-d H:i:s') . '</p>
    </body>
    </html>';

    $headers = ['Content-Type: text/html; charset=UTF-8'];

    $result = wp_mail($to_email, $subject, $message, $headers);

    if ($result) {
        error_log("BIM Verdi: Test-e-post sendt til $to_email");
    } else {
        error_log("BIM Verdi: Test-e-post FEILET til $to_email");
    }

    return $result;
}
