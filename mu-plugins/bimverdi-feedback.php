<?php
/**
 * BIM Verdi - Tilbakemelding (Ros & Ris) Handler
 *
 * Handles feedback form submissions. Sends email via wp_mail (Resend)
 * to the site admin.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) exit;

/**
 * Process feedback form submission
 */
add_action('init', function () {
    if (empty($_POST['bimverdi_feedback_submit'])) {
        return;
    }

    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bimverdi_feedback')) {
        wp_safe_redirect(home_url('/tilbakemelding/?status=error'));
        exit;
    }

    // Honeypot check
    if (!empty($_POST['bv_website_url'])) {
        wp_safe_redirect(home_url('/tilbakemelding/?status=success'));
        exit;
    }

    // Rate limiting (5 per hour per IP)
    $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
    $transient_key = 'bv_feedback_' . md5($ip);
    $count = (int) get_transient($transient_key);
    if ($count >= 5) {
        wp_safe_redirect(home_url('/tilbakemelding/?status=rate_limit'));
        exit;
    }
    set_transient($transient_key, $count + 1, HOUR_IN_SECONDS);

    // Sanitize input
    $type    = sanitize_text_field($_POST['feedback_type'] ?? '');
    $message = sanitize_textarea_field($_POST['feedback_message'] ?? '');
    $name    = sanitize_text_field($_POST['feedback_name'] ?? '');
    $email   = sanitize_email($_POST['feedback_email'] ?? '');
    $page    = sanitize_text_field($_POST['feedback_page'] ?? '');

    // Validate required fields
    if (!in_array($type, ['ros', 'ris', 'forslag'], true)) {
        wp_safe_redirect(home_url('/tilbakemelding/?status=error'));
        exit;
    }

    if (empty($message) || strlen($message) < 10) {
        wp_safe_redirect(home_url('/tilbakemelding/?status=missing_message'));
        exit;
    }

    // Build email
    $type_labels = [
        'ros'     => 'Ros (positiv tilbakemelding)',
        'ris'     => 'Ris (noe som kan forbedres)',
        'forslag' => 'Forslag',
    ];

    $type_emoji = [
        'ros'     => "\xF0\x9F\x8C\x9F",
        'ris'     => "\xF0\x9F\x94\xA7",
        'forslag' => "\xF0\x9F\x92\xA1",
    ];

    $subject = $type_emoji[$type] . ' Tilbakemelding: ' . $type_labels[$type];

    $body  = "<h2>{$type_labels[$type]}</h2>";
    $body .= "<p><strong>Melding:</strong></p>";
    $body .= "<blockquote style='border-left:3px solid #FF8B5E;padding-left:12px;color:#333;'>" . nl2br(esc_html($message)) . "</blockquote>";

    if ($name) {
        $body .= "<p><strong>Navn:</strong> " . esc_html($name) . "</p>";
    }
    if ($email) {
        $body .= "<p><strong>E-post:</strong> " . esc_html($email) . "</p>";
    }
    if ($page) {
        $body .= "<p><strong>Gjelder side:</strong> " . esc_html($page) . "</p>";
    }

    // Add logged-in user info
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $body .= "<p><strong>Innlogget som:</strong> {$user->display_name} ({$user->user_email})</p>";
    }

    $body .= "<hr><p style='color:#999;font-size:12px;'>Sendt fra tilbakemeldingsskjemaet på " . home_url() . "</p>";

    $admin_email = get_option('admin_email');
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    if ($email) {
        $headers[] = 'Reply-To: ' . ($name ? "$name <$email>" : $email);
    }

    $sent = wp_mail($admin_email, $subject, $body, $headers);

    if ($sent) {
        wp_safe_redirect(home_url('/tilbakemelding/?status=success'));
    } else {
        wp_safe_redirect(home_url('/tilbakemelding/?status=error'));
    }
    exit;
});
