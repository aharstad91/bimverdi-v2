<?php
/**
 * BIM Verdi - Newsletter Signup Handler
 *
 * Handles plain HTML form submission for newsletter signup in footer.
 * Replaces Gravity Forms Form #24.
 *
 * Pattern: POST-Redirect-GET (PRG)
 * - Success: referrer URL with ?newsletter=success
 * - Error:   referrer URL with ?newsletter=<error_code>
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle newsletter signup form submission
 */
add_action('init', function () {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bimverdi_newsletter_signup'])) {
        return;
    }

    // Get referrer for redirect
    $referrer = wp_get_referer() ?: home_url('/');
    // Strip existing newsletter params
    $referrer = remove_query_arg(['newsletter'], $referrer);

    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bimverdi_newsletter_signup')) {
        wp_redirect(add_query_arg('newsletter', 'error', $referrer));
        exit;
    }

    // Honeypot check
    if (!empty($_POST['bv_website_url'] ?? '')) {
        wp_redirect(add_query_arg('newsletter', 'success', $referrer));
        exit;
    }

    // Rate limiting: max 5 signups per hour per IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_key = 'bv_newsletter_' . md5($ip);
    $attempts = (int) get_transient($rate_key);
    if ($attempts >= 5) {
        wp_redirect(add_query_arg('newsletter', 'rate_limit', $referrer));
        exit;
    }
    set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);

    // Krav 22 / R22.4: nyhetsbrev krever foretak. Innloggede uten foretak
    // sendes til block-vegg + foretaks-kobling. Gjester redirectes til
    // innlogging (eksisterende WP-mønster) — etter login kommer de tilbake hit.
    if (is_user_logged_in()) {
        $current_user_id = get_current_user_id();
        if (
            function_exists('bimverdi_can_access')
            && !bimverdi_can_access('subscribe_newsletter')
            && !user_can($current_user_id, 'manage_options')
        ) {
            if (function_exists('bimverdi_remember_pending_oppgave')) {
                bimverdi_remember_pending_oppgave([
                    'url'   => $referrer,
                    'label' => 'nyhetsbrev-påmelding',
                    'context' => ['type' => 'newsletter'],
                ]);
            }
            wp_redirect(home_url('/min-side/?retry=1'));
            exit;
        }
    }

    // Sanitize and validate email
    $email = sanitize_email($_POST['newsletter_email'] ?? '');

    if (empty($email) || !is_email($email)) {
        wp_redirect(add_query_arg('newsletter', 'invalid_email', $referrer));
        exit;
    }

    // Check if already subscribed
    global $wpdb;
    $table = $wpdb->prefix . 'bimverdi_newsletter';

    // Create table if not exists
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        $charset_collate = $wpdb->get_charset_collate();
        $wpdb->query("CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL,
            subscribed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) {$charset_collate};");
    }

    // Check for duplicate
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table} WHERE email = %s",
        $email
    ));

    if ($existing) {
        // Already subscribed - treat as success (don't reveal existing subscribers)
        wp_redirect(add_query_arg('newsletter', 'success', $referrer));
        exit;
    }

    // Insert subscriber
    $inserted = $wpdb->insert($table, [
        'email'         => $email,
        'subscribed_at' => current_time('mysql'),
        'ip_address'    => $ip,
    ], ['%s', '%s', '%s']);

    if (!$inserted) {
        error_log('BIMVerdi newsletter signup error: ' . $wpdb->last_error);
        wp_redirect(add_query_arg('newsletter', 'error', $referrer));
        exit;
    }

    // Send admin notification til post@bimverdi.no (BV_NOTIFY_EMAIL via shared-helpers)
    $subject = 'Ny nyhetsbrev-påmelding - BIM Verdi';
    $body    = sprintf("Ny påmelding til nyhetsbrevet:\n\nE-post: %s\nDato: %s", $email, current_time('d.m.Y H:i'));
    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    if (function_exists('bimverdi_send_admin_notification_email')) {
        bimverdi_send_admin_notification_email($subject, $body, $headers);
    } else {
        // Fallback hvis shared-helpers ikke lastet (skal ikke skje)
        wp_mail(get_option('admin_email'), $subject, $body, $headers);
    }

    // Clear rate limit on success
    delete_transient($rate_key);

    error_log('BIMVerdi: Newsletter signup: ' . $email);

    wp_redirect(add_query_arg('newsletter', 'success', $referrer));
    exit;
});
