<?php
/**
 * BIM Verdi - Søknadsbistand Handler
 *
 * Handles plain HTML form submission for project idea leads.
 * Replaces Gravity Forms Form #9.
 *
 * Pattern: POST-Redirect-GET (PRG)
 * - Success: /soknadsbistand/?submitted=1
 * - Error:   /soknadsbistand/?bv_error=<code>
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle søknadsbistand form submission
 */
add_action('init', function () {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bimverdi_soknadsbistand'])) {
        return;
    }

    $redirect_error = home_url('/soknadsbistand/');

    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bimverdi_soknadsbistand')) {
        wp_redirect(add_query_arg('bv_error', 'nonce', $redirect_error));
        exit;
    }

    // Honeypot check
    if (!empty($_POST['bv_website_url'] ?? '')) {
        wp_redirect(add_query_arg('submitted', '1', $redirect_error));
        exit;
    }

    // Rate limiting: max 3 submissions per hour per IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_key = 'bv_soknadsbistand_' . md5($ip);
    $attempts = (int) get_transient($rate_key);
    if ($attempts >= 3) {
        wp_redirect(add_query_arg('bv_error', 'rate_limit', $redirect_error));
        exit;
    }
    set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);

    // --- Sanitize inputs ---
    $navn        = sanitize_text_field($_POST['navn'] ?? '');
    $epost       = sanitize_email($_POST['epost'] ?? '');
    $telefon     = sanitize_text_field($_POST['telefon'] ?? '');
    $bedrift     = sanitize_text_field($_POST['bedrift'] ?? '');
    $tittel      = sanitize_text_field($_POST['prosjekt_tittel'] ?? '');
    $beskrivelse = sanitize_textarea_field($_POST['prosjekt_beskrivelse'] ?? '');
    $samtykke    = !empty($_POST['samtykke']);

    // --- Validate required fields ---
    if (empty($navn) || empty($epost) || empty($tittel) || empty($beskrivelse)) {
        wp_redirect(add_query_arg('bv_error', 'missing_fields', $redirect_error));
        exit;
    }

    if (!is_email($epost)) {
        wp_redirect(add_query_arg('bv_error', 'invalid_email', $redirect_error));
        exit;
    }

    if (!$samtykke) {
        wp_redirect(add_query_arg('bv_error', 'missing_consent', $redirect_error));
        exit;
    }

    // --- Send notification email ---
    $admin_email = get_option('admin_email');
    $subject = 'Ny prosjektidé (søknadsbistand) - ' . $tittel;

    $body = "Ny prosjektidé innsendt via søknadsbistand-skjemaet:\n\n";
    $body .= "Navn: {$navn}\n";
    $body .= "E-post: {$epost}\n";
    if ($telefon) {
        $body .= "Telefon: {$telefon}\n";
    }
    if ($bedrift) {
        $body .= "Bedrift: {$bedrift}\n";
    }
    $body .= "\nProsjekttittel: {$tittel}\n";
    $body .= "\nBeskrivelse:\n{$beskrivelse}\n";
    $body .= "\nDato: " . current_time('d.m.Y H:i') . "\n";

    wp_mail($admin_email, $subject, $body, ['Content-Type: text/plain; charset=UTF-8']);

    // Clear rate limit on success
    delete_transient($rate_key);

    error_log('BIMVerdi: Søknadsbistand submitted by ' . $epost . ' - ' . $tittel);

    wp_redirect(add_query_arg('submitted', '1', $redirect_error));
    exit;
});
