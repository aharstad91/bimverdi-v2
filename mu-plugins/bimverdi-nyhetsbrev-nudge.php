<?php
/**
 * Plugin Name: BIM Verdi - Nyhetsbrev-nudge på Min side
 * Description: Viser en påminnelse på Min side-dashboardet for innloggede brukere som ikke er påmeldt nyhetsbrevet, med ett-klikks påmelding. Respekterer aktiv avmelding (GDPR) og krav 22 (påmelding krever foretakskobling).
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Skal nudgen vises for denne brukeren?
 *
 * Vises KUN når alle disse stemmer:
 * - innlogget
 * - ikke allerede påmeldt (bimverdi_newsletter_subscribed !== '1')
 * - ikke aktivt avmeldt via GDPR-lenken (de har sagt tydelig nei — ikke mas)
 * - ikke lukket nudgen selv («Nei takk»)
 * - har lov til å melde seg på (krav 22 / R22.5: krever foretakskobling) —
 *   ellers nudger vi rett inn i blokk-veggen
 */
function bimverdi_skal_vise_nyhetsbrev_nudge($user_id = 0) {
    $user_id = $user_id ?: get_current_user_id();
    if (!$user_id) {
        return false;
    }

    if (get_user_meta($user_id, 'bimverdi_newsletter_subscribed', true) === '1') {
        return false;
    }

    if (function_exists('bimverdi_nyhetsbrev_er_avmeldt') && bimverdi_nyhetsbrev_er_avmeldt($user_id)) {
        return false;
    }

    if (get_user_meta($user_id, 'bimverdi_nyhetsbrev_nudge_lukket', true)) {
        return false;
    }

    if (function_exists('bimverdi_can_access') && !bimverdi_can_access('subscribe_newsletter')) {
        return false;
    }

    return true;
}

/**
 * POST-handler for nudgen (PRG-mønster som bimverdi-newsletter.php).
 * To handlinger: 'pamelding' (sett meta '1') og 'lukk' (ikke vis igjen).
 */
add_action('init', function () {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bimverdi_nb_nudge'])) {
        return;
    }

    $redirect = home_url('/min-side/');

    if (!is_user_logged_in()) {
        wp_redirect($redirect);
        exit;
    }

    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bimverdi_nb_nudge')) {
        wp_redirect($redirect);
        exit;
    }

    $user_id = get_current_user_id();
    $handling = sanitize_key($_POST['bimverdi_nb_nudge']);

    if ($handling === 'pamelding') {
        // Krav 22 / R22.5: server-side sjekk uansett om nudgen var synlig.
        if (function_exists('bimverdi_can_access') && !bimverdi_can_access('subscribe_newsletter')) {
            wp_redirect(add_query_arg('retry', '1', $redirect));
            exit;
        }
        update_user_meta($user_id, 'bimverdi_newsletter_subscribed', '1');
        wp_redirect(add_query_arg('nb_nudge', 'pameldt', $redirect));
        exit;
    }

    if ($handling === 'lukk') {
        update_user_meta($user_id, 'bimverdi_nyhetsbrev_nudge_lukket', current_time('mysql'));
        wp_redirect($redirect);
        exit;
    }

    wp_redirect($redirect);
    exit;
});
