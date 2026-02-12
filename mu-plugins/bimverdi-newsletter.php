<?php
/**
 * BIM Verdi - Newsletter Signup
 *
 * Handles footer newsletter form submissions via AJAX.
 * Stores subscribers in wp_options and sends admin notification.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers (both logged-in and logged-out users)
 */
add_action('wp_ajax_bimverdi_newsletter_signup', 'bimverdi_newsletter_signup');
add_action('wp_ajax_nopriv_bimverdi_newsletter_signup', 'bimverdi_newsletter_signup');

function bimverdi_newsletter_signup() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'bimverdi_newsletter')) {
        wp_send_json_error(array('message' => 'Ugyldig forespørsel.'));
    }

    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'Vennligst oppgi en gyldig e-postadresse.'));
    }

    // Get existing subscribers
    $subscribers = get_option('bimverdi_newsletter_subscribers', array());

    // Check for duplicates
    $existing_emails = array_column($subscribers, 'email');
    if (in_array($email, $existing_emails, true)) {
        wp_send_json_success(array('message' => 'Takk! Du er allerede registrert for nyhetsbrev.'));
    }

    // Add new subscriber
    $subscribers[] = array(
        'email' => $email,
        'date'  => current_time('Y-m-d H:i:s'),
        'ip'    => sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? '')),
    );
    update_option('bimverdi_newsletter_subscribers', $subscribers);

    // Send admin notification
    $admin_email = get_option('admin_email');
    $subject = 'Ny nyhetsbrev-påmelding: ' . $email;
    $body = sprintf(
        "Ny påmelding til BIM Verdi nyhetsbrev:\n\nE-post: %s\nDato: %s\nTotalt antall abonnenter: %d",
        $email,
        current_time('d.m.Y H:i'),
        count($subscribers)
    );
    wp_mail($admin_email, $subject, $body);

    wp_send_json_success(array('message' => 'Takk for din påmelding! Du vil motta nyheter og invitasjoner fra oss.'));
}

/**
 * Output newsletter nonce and AJAX URL in footer
 */
add_action('wp_footer', 'bimverdi_newsletter_footer_script');

function bimverdi_newsletter_footer_script() {
    $nonce = wp_create_nonce('bimverdi_newsletter');
    $ajax_url = admin_url('admin-ajax.php');
    ?>
    <script>
    (function() {
        var form = document.getElementById('bv-newsletter-form');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            var emailInput = form.querySelector('input[name="email"]');
            var btn = form.querySelector('button[type="submit"]');
            var msg = document.getElementById('bv-newsletter-msg');
            var email = emailInput.value.trim();

            if (!email) return;

            // Disable form
            btn.disabled = true;
            emailInput.disabled = true;

            var data = new FormData();
            data.append('action', 'bimverdi_newsletter_signup');
            data.append('email', email);
            data.append('nonce', '<?php echo esc_js($nonce); ?>');

            fetch('<?php echo esc_js($ajax_url); ?>', {
                method: 'POST',
                body: data,
                credentials: 'same-origin'
            })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (msg) {
                    msg.textContent = resp.data.message;
                    msg.style.display = 'block';
                    msg.style.color = resp.success ? '#2e7d32' : '#772015';
                }
                if (resp.success) {
                    emailInput.value = '';
                }
            })
            .catch(function() {
                if (msg) {
                    msg.textContent = 'Noe gikk galt. Prøv igjen senere.';
                    msg.style.display = 'block';
                    msg.style.color = '#772015';
                }
            })
            .finally(function() {
                btn.disabled = false;
                emailInput.disabled = false;
            });
        });
    })();
    </script>
    <?php
}
