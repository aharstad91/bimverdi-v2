<?php
/**
 * BIM Verdi — Foretak-oppgradering (gratisforetak → betalende deltaker)
 *
 * Manuelt godkjent oppgraderings-flyt:
 *  1. Hovedkontakt for gratisforetak sender forespørsel via /min-side/foretak/oppgrader/
 *  2. System lagrer pending-meta + history-event, sender 2 e-poster (bruker + post@bimverdi.no)
 *  3. Bård godkjenner manuelt via knapp på foretak-edit-siden i wp-admin
 *  4. System setter bv_rolle, clearer pending, sender bekreftelses-e-post + dokumentasjon
 *  5. Avvisning: samme arkitektur, sender e-post med begrunnelse
 *
 * Plan: docs/plans/2026-04-29-001-feat-oppgraderingsvei-manuell-godkjenning-plan.md
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// KONSTANTER
// =============================================================================

/** Meta-nøkkel for pending oppgraderings-forespørsel (én per foretak). */
const BV_OPPGRADERING_META_PENDING = '_bv_oppgradering_pending';

/** Meta-nøkkel for append-only audit-log av oppgraderingshendelser. */
const BV_OPPGRADERING_META_HISTORY = '_bv_oppgradering_history';

/** Gyldige bv_rolle-verdier som kan velges som oppgraderingsmål. */
const BV_OPPGRADERING_VALID_LEVELS = ['Deltaker', 'Prosjektdeltaker', 'Partner'];

// NOTE: BV_NOTIFY_EMAIL og BV_TERMS_URL defineres i bimverdi-shared-helpers.php
// (mu-plugin lastet alfabetisk etter denne filen). Refs i denne filen brukes
// kun runtime (init/admin-post hooks), så konstantene er tilgjengelige da.

// =============================================================================
// HELPER-FUNKSJONER: PENDING-FORESPØRSEL
// =============================================================================

/**
 * Hent pending oppgraderings-forespørsel for et foretak.
 *
 * @param int $foretak_id
 * @return array|null Array med 'level', 'requested_at' (ISO8601), 'requested_by_user_id', eller null hvis ingen pending.
 */
function bimverdi_get_pending_oppgradering($foretak_id) {
    $foretak_id = (int) $foretak_id;
    if (!$foretak_id || get_post_type($foretak_id) !== 'foretak') {
        return null;
    }

    $pending = get_post_meta($foretak_id, BV_OPPGRADERING_META_PENDING, true);
    if (!is_array($pending) || empty($pending['level'])) {
        return null;
    }

    return $pending;
}

/**
 * Sett pending oppgraderings-forespørsel på et foretak. Overskriver eksisterende.
 *
 * @param int    $foretak_id
 * @param string $level    Må være i BV_OPPGRADERING_VALID_LEVELS.
 * @param int    $user_id  Bruker som sender forespørselen.
 * @return bool true ved suksess, false hvis ugyldig input.
 */
function bimverdi_set_pending_oppgradering($foretak_id, $level, $user_id) {
    $foretak_id = (int) $foretak_id;
    $user_id = (int) $user_id;
    if (!$foretak_id || get_post_type($foretak_id) !== 'foretak') {
        return false;
    }
    if (!in_array($level, BV_OPPGRADERING_VALID_LEVELS, true)) {
        return false;
    }
    if (!$user_id) {
        return false;
    }

    return (bool) update_post_meta($foretak_id, BV_OPPGRADERING_META_PENDING, [
        'level'                 => $level,
        'requested_at'          => current_time('c'),
        'requested_by_user_id'  => $user_id,
    ]);
}

/**
 * Fjern pending oppgraderings-forespørsel (ved godkjenning eller avvisning).
 *
 * @param int $foretak_id
 * @return bool
 */
function bimverdi_clear_pending_oppgradering($foretak_id) {
    $foretak_id = (int) $foretak_id;
    if (!$foretak_id || get_post_type($foretak_id) !== 'foretak') {
        return false;
    }
    return (bool) delete_post_meta($foretak_id, BV_OPPGRADERING_META_PENDING);
}

// =============================================================================
// HELPER-FUNKSJONER: AUDIT-LOG (HISTORIKK)
// =============================================================================

/**
 * Hent oppgraderings-historikk for et foretak.
 *
 * @param int $foretak_id
 * @return array Liste av events, oldest-first. Tom array hvis ingen historikk.
 */
function bimverdi_get_oppgradering_history($foretak_id) {
    $foretak_id = (int) $foretak_id;
    if (!$foretak_id || get_post_type($foretak_id) !== 'foretak') {
        return [];
    }
    $history = get_post_meta($foretak_id, BV_OPPGRADERING_META_HISTORY, true);
    return is_array($history) ? $history : [];
}

/**
 * Append-only logging av oppgraderings-hendelse.
 *
 * Event-struktur:
 *   - type:     'request' | 'approved' | 'rejected'
 *   - level:    nivået forespurt/godkjent/avvist
 *   - date:     ISO8601 timestamp
 *   - user_id:  bruker som sendte forespørselen (alltid satt)
 *   - admin_id: admin som godkjente/avviste (kun for approved/rejected)
 *   - reason:   begrunnelse ved avvisning (kun for rejected)
 *
 * @param int   $foretak_id
 * @param array $event   Må inneholde minst 'type' og 'level'.
 * @return bool
 */
function bimverdi_append_oppgradering_history($foretak_id, $event) {
    $foretak_id = (int) $foretak_id;
    if (!$foretak_id || get_post_type($foretak_id) !== 'foretak') {
        return false;
    }
    if (empty($event['type']) || empty($event['level'])) {
        return false;
    }

    $history = bimverdi_get_oppgradering_history($foretak_id);

    // Sett standard date hvis mangler
    if (empty($event['date'])) {
        $event['date'] = current_time('c');
    }

    $history[] = $event;
    return (bool) update_post_meta($foretak_id, BV_OPPGRADERING_META_HISTORY, $history);
}

// =============================================================================
// HELPER-FUNKSJONER: TILGANGSKONTROLL
// =============================================================================

/**
 * Sjekk om en bruker kan sende oppgraderingsforespørsel for sitt foretak.
 *
 * Krav:
 *  - Innlogget
 *  - Hovedkontakt for sitt foretak
 *  - Foretaket har bv_rolle = 'Ikke deltaker' (gratisforetak)
 *
 * @param int|null $user_id  Default: gjeldende bruker.
 * @return int|false Foretak-ID hvis bruker kan oppgradere, ellers false.
 */
function bimverdi_user_can_request_oppgradering($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    if (!$user_id) {
        return false;
    }

    if (!bimverdi_is_hovedkontakt($user_id)) {
        return false;
    }

    // bimverdi_user_has_company returnerer bool — hent faktisk ID via _get_user_company
    if (!bimverdi_user_has_company($user_id)) {
        return false;
    }

    $company = bimverdi_get_user_company($user_id);
    $foretak_id = is_array($company) ? (int) ($company['id'] ?? 0) : (int) $company;
    if (!$foretak_id) {
        return false;
    }

    $rolle = function_exists('get_field')
        ? get_field('bv_rolle', $foretak_id)
        : get_post_meta($foretak_id, 'bv_rolle', true);

    if ($rolle !== 'Ikke deltaker') {
        return false;
    }

    return $foretak_id;
}

// =============================================================================
// SUBMISSION-HANDLER (POST → /min-side/foretak/oppgrader/)
// =============================================================================

add_action('init', 'bimverdi_handle_oppgradering_submission');

function bimverdi_handle_oppgradering_submission() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }
    if (empty($_POST['bimverdi_oppgradering_request'])) {
        return;
    }

    $redirect_back = home_url('/min-side/foretak/oppgrader/');

    // Innlogget?
    if (!is_user_logged_in()) {
        wp_safe_redirect(home_url('/logg-inn/'));
        exit;
    }

    $user_id = get_current_user_id();

    // Nonce
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bimverdi_oppgradering_request')) {
        wp_safe_redirect(add_query_arg('bv_error', 'nonce', $redirect_back));
        exit;
    }

    // Honeypot — silent success
    if (!empty($_POST['bv_website_url'] ?? '')) {
        wp_safe_redirect(add_query_arg('oppgradering_sendt', '1', home_url('/min-side/foretak/')));
        exit;
    }

    // Rate-limit (3/time, bypass for admins)
    if (!current_user_can('manage_options')) {
        $rate_key = 'bv_oppgradering_req_' . $user_id;
        $attempts = (int) get_transient($rate_key);
        if ($attempts >= 3) {
            wp_safe_redirect(add_query_arg('bv_error', 'rate_limit', $redirect_back));
            exit;
        }
        set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);
    }

    // Tilgang
    $foretak_id = bimverdi_user_can_request_oppgradering($user_id);
    if (!$foretak_id) {
        wp_safe_redirect(home_url('/min-side/foretak/?bv_error=already_paying'));
        exit;
    }

    // Validering: nivå
    $level = sanitize_text_field($_POST['level'] ?? '');
    if (empty($level)) {
        wp_safe_redirect(add_query_arg('bv_error', 'missing_level', $redirect_back));
        exit;
    }
    if (!in_array($level, BV_OPPGRADERING_VALID_LEVELS, true)) {
        wp_safe_redirect(add_query_arg('bv_error', 'invalid_level', $redirect_back));
        exit;
    }

    // Validering: betingelser
    if (empty($_POST['accept_terms'])) {
        wp_safe_redirect(add_query_arg('bv_error', 'missing_terms', $redirect_back));
        exit;
    }

    // Validering: fakturafelter
    $ehf_faktura       = sanitize_text_field($_POST['ehf_faktura'] ?? 'nei');
    $faktura_epost     = sanitize_email($_POST['faktura_epost'] ?? '');
    $faktura_referanse = sanitize_text_field($_POST['faktura_referanse'] ?? '');
    if (!in_array($ehf_faktura, ['ja', 'nei'], true)) {
        $ehf_faktura = 'nei';
    }
    if (empty($faktura_referanse)) {
        wp_safe_redirect(add_query_arg('bv_error', 'missing_invoice_ref', $redirect_back));
        exit;
    }
    if ($ehf_faktura === 'nei') {
        if (empty($faktura_epost)) {
            wp_safe_redirect(add_query_arg('bv_error', 'missing_invoice_email', $redirect_back));
            exit;
        }
        if (!is_email($faktura_epost)) {
            wp_safe_redirect(add_query_arg('bv_error', 'invalid_invoice_email', $redirect_back));
            exit;
        }
    }

    // Lagre fakturafelter på foretak-CPT (før pending — så data overlever ev. feil senere)
    if (function_exists('update_field')) {
        update_field('ehf_faktura', $ehf_faktura, $foretak_id);
        update_field('faktura_epost', $faktura_epost, $foretak_id);
        update_field('faktura_referanse', $faktura_referanse, $foretak_id);
    }

    // Lagre pending + history
    $set_ok = bimverdi_set_pending_oppgradering($foretak_id, $level, $user_id);
    if (!$set_ok) {
        error_log("BIMVerdi oppgradering: kunne ikke sette pending-meta for foretak $foretak_id, user $user_id");
        wp_safe_redirect(add_query_arg('bv_error', 'generic', $redirect_back));
        exit;
    }
    bimverdi_append_oppgradering_history($foretak_id, [
        'type'    => 'request',
        'level'   => $level,
        'user_id' => $user_id,
    ]);

    // Send e-poster (feil her blokkerer ikke flyten)
    bimverdi_send_oppgradering_request_emails($foretak_id, $level, $user_id);

    // Suksess-redirect
    wp_safe_redirect(add_query_arg('oppgradering_sendt', '1', home_url('/min-side/foretak/')));
    exit;
}

// =============================================================================
// E-POST: REQUEST-BEKREFTELSER
// =============================================================================

/**
 * Send 2 e-poster ved ny oppgraderingsforespørsel:
 *  1. Bekreftelse til hovedkontakt (bruker)
 *  2. Fakturaunderlag-kopi til post@bimverdi.no
 *
 * Feil logges til error_log men blokkerer ikke flyten.
 */
function bimverdi_send_oppgradering_request_emails($foretak_id, $level, $user_id) {
    $user = get_userdata($user_id);
    if (!$user) {
        error_log("BIMVerdi oppgradering: ukjent user_id $user_id");
        return;
    }

    $foretak       = get_post($foretak_id);
    $foretak_navn  = $foretak ? $foretak->post_title : '(ukjent foretak)';
    $org_nr        = function_exists('get_field') ? get_field('organisasjonsnummer', $foretak_id) : '';
    $admin_url_foretak = admin_url('post.php?post=' . $foretak_id . '&action=edit');

    // Fakturafelter (lagret rett før denne funksjonen kalles)
    $ehf_faktura       = function_exists('get_field') ? get_field('ehf_faktura', $foretak_id) : '';
    $faktura_epost     = function_exists('get_field') ? get_field('faktura_epost', $foretak_id) : '';
    $faktura_referanse = function_exists('get_field') ? get_field('faktura_referanse', $foretak_id) : '';

    $headers = ['Content-Type: text/html; charset=UTF-8'];

    // 1. Bekreftelse til bruker
    $bruker_subject = sprintf('[BIM Verdi] Oppgraderingsforespørsel mottatt — %s', $foretak_navn);
    $bruker_body = sprintf(
        '<p>Hei %s,</p>
        <p>Vi har mottatt din forespørsel om å oppgradere <strong>%s</strong> til <strong>%s</strong>.</p>
        <p>Bård Krogshus i BIM Verdi vurderer forespørselen manuelt og sender deg en bekreftelses-e-post med faktura når oppgraderingen er godkjent.</p>
        <p>Du kan se status på din forespørsel her: <a href="%s">Min Side — Foretak</a></p>
        <hr>
        <p style="font-size:12px;color:#666;">
            Ved å sende denne forespørselen har du akseptert
            <a href="%s">betingelsene for medlemskap i BIM Verdi</a>.
        </p>
        <p style="font-size:12px;color:#666;">Mvh<br>BIM Verdi</p>',
        esc_html($user->display_name),
        esc_html($foretak_navn),
        esc_html($level),
        esc_url(home_url('/min-side/foretak/')),
        esc_url(BV_TERMS_URL)
    );
    $sent_user = wp_mail($user->user_email, $bruker_subject, $bruker_body, $headers);
    if (!$sent_user) {
        error_log("BIMVerdi oppgradering: bruker-e-post feilet for $user->user_email (foretak $foretak_id)");
    }

    // 2. Kopi til post@bimverdi.no (fakturaunderlag)
    $admin_subject = sprintf('Ny oppgraderingsforespørsel: %s → %s', $foretak_navn, $level);
    $admin_body = sprintf(
        '<p>Ny oppgraderingsforespørsel mottatt:</p>
        <table style="border-collapse:collapse;font-size:14px;">
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Foretak</td><td><strong>%s</strong></td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Org.nr</td><td>%s</td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Forespurt nivå</td><td><strong>%s</strong></td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Sendt av</td><td>%s &lt;%s&gt;</td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Tidspunkt</td><td>%s</td></tr>
        </table>
        <h3 style="font-size:14px;margin-top:24px;margin-bottom:8px;color:#1A1A1A;">Fakturainformasjon</h3>
        <table style="border-collapse:collapse;font-size:14px;">
            <tr><td style="padding:4px 12px 4px 0;color:#666;">EHF-faktura</td><td><strong>%s</strong></td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Faktura-e-post</td><td>%s</td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Faktura-referanse</td><td>%s</td></tr>
        </table>
        <p style="margin-top:24px;">
            <a href="%s" style="background:#FF8B5E;color:#fff;padding:10px 16px;text-decoration:none;border-radius:6px;display:inline-block;">
                Åpne foretaket i wp-admin for å godkjenne
            </a>
        </p>
        <hr>
        <p style="font-size:12px;color:#666;">
            Bruker har akseptert <a href="%s">betingelsene</a>.
            Lagre dette i SuperOffice som fakturaunderlag.
        </p>',
        esc_html($foretak_navn),
        esc_html($org_nr ?: '—'),
        esc_html($level),
        esc_html($user->display_name),
        esc_html($user->user_email),
        esc_html(date_i18n('j. F Y \k\l. H:i')),
        esc_html($ehf_faktura ? ucfirst($ehf_faktura) : '—'),
        esc_html($faktura_epost ?: '—'),
        esc_html($faktura_referanse ?: '—'),
        esc_url($admin_url_foretak),
        esc_url(BV_TERMS_URL)
    );
    $sent_admin = wp_mail(BV_NOTIFY_EMAIL, $admin_subject, $admin_body, $headers);
    if (!$sent_admin) {
        error_log('BIMVerdi oppgradering: admin-kopi-e-post feilet til ' . BV_NOTIFY_EMAIL);
    }
}

// =============================================================================
// ADMIN: NOTICE + GODKJENN/AVVIS-KNAPPER PÅ FORETAK-EDIT
// =============================================================================

/**
 * Vis pending-notice øverst på foretak-edit-siden hvis forespørsel finnes.
 */
add_action('edit_form_top', 'bimverdi_show_oppgradering_admin_notice');

function bimverdi_show_oppgradering_admin_notice($post) {
    if (!$post || $post->post_type !== 'foretak') {
        return;
    }

    // Suksess-melding etter handling
    $action = isset($_GET['bv_oppgradering']) ? sanitize_key($_GET['bv_oppgradering']) : '';
    if ($action === 'approved') {
        echo '<div class="notice notice-success inline" style="margin: 15px 0;"><p><strong>✅ Oppgradering godkjent.</strong> Bekreftelses-e-post er sendt til hovedkontakt og kopi til ' . esc_html(BV_NOTIFY_EMAIL) . '.</p></div>';
    } elseif ($action === 'rejected') {
        echo '<div class="notice notice-warning inline" style="margin: 15px 0;"><p><strong>Oppgradering avvist.</strong> Avvisnings-e-post sendt til hovedkontakt med din begrunnelse.</p></div>';
    } elseif ($action === 'error') {
        $msg = isset($_GET['bv_msg']) ? sanitize_text_field(wp_unslash($_GET['bv_msg'])) : 'Ukjent feil.';
        echo '<div class="notice notice-error inline" style="margin: 15px 0;"><p><strong>Feil:</strong> ' . esc_html($msg) . '</p></div>';
    }

    $pending = bimverdi_get_pending_oppgradering($post->ID);
    if (!$pending) {
        return;
    }

    $requested_user = get_userdata((int) ($pending['requested_by_user_id'] ?? 0));
    $requested_user_label = $requested_user ? $requested_user->display_name . ' (' . $requested_user->user_email . ')' : 'Ukjent bruker';
    $requested_at_label = !empty($pending['requested_at'])
        ? date_i18n('j. F Y \k\l. H:i', strtotime($pending['requested_at']))
        : '';

    $approve_url = wp_nonce_url(
        admin_url('admin-post.php?action=bimverdi_approve_oppgradering&foretak_id=' . $post->ID),
        'bimverdi_approve_oppgradering_' . $post->ID
    );
    ?>
    <div class="notice notice-warning" style="margin: 15px 0; padding: 16px; border-left-width: 4px;">
        <h3 style="margin: 0 0 8px;">⏳ Pending oppgraderingsforespørsel</h3>
        <p style="margin: 0 0 8px; font-size: 14px;">
            Forespurt nivå: <strong><?php echo esc_html($pending['level']); ?></strong><br>
            Sendt av: <strong><?php echo esc_html($requested_user_label); ?></strong><br>
            Tidspunkt: <?php echo esc_html($requested_at_label); ?>
        </p>
        <div style="display:flex;gap:8px;align-items:flex-start;margin-top:12px;flex-wrap:wrap;">
            <a href="<?php echo esc_url($approve_url); ?>" class="button button-primary" onclick="return confirm('Godkjenn oppgradering til <?php echo esc_js($pending['level']); ?>? Dette setter bv_rolle og sender bekreftelses-e-post til bruker.');">
                ✅ Godkjenn oppgradering
            </a>

            <details style="display:inline-block;">
                <summary style="cursor:pointer;padding:6px 12px;border:1px solid #c3c4c7;border-radius:3px;background:#f6f7f7;display:inline-block;">
                    ❌ Avvis...
                </summary>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;padding:12px;border:1px solid #c3c4c7;border-radius:3px;background:#fff;max-width:500px;">
                    <input type="hidden" name="action" value="bimverdi_reject_oppgradering">
                    <input type="hidden" name="foretak_id" value="<?php echo esc_attr($post->ID); ?>">
                    <?php wp_nonce_field('bimverdi_reject_oppgradering_' . $post->ID); ?>
                    <label for="bv_reject_reason" style="display:block;margin-bottom:6px;font-weight:600;">
                        Begrunnelse (sendes til bruker):
                    </label>
                    <textarea name="reason" id="bv_reject_reason" rows="3" required style="width:100%;margin-bottom:8px;" placeholder="Eksempel: Manglende dokumentasjon, ikke aktuelt nivå, etc."></textarea>
                    <button type="submit" class="button">Send avvisning</button>
                </form>
            </details>
        </div>
        <p style="margin-top:12px;font-size:12px;color:#666;">
            Direkte redigering av <code>bv_rolle</code>-feltet under påvirker ikke denne forespørselen og sender ingen e-post — bruk knappene for formell godkjenning/avvisning.
        </p>
    </div>
    <?php
}

/**
 * Handler: Godkjenn oppgraderingsforespørsel.
 * Endpoint: admin-post.php?action=bimverdi_approve_oppgradering
 */
add_action('admin_post_bimverdi_approve_oppgradering', 'bimverdi_handle_approve_oppgradering');

function bimverdi_handle_approve_oppgradering() {
    $foretak_id = isset($_GET['foretak_id']) ? (int) $_GET['foretak_id'] : 0;

    if (!$foretak_id || get_post_type($foretak_id) !== 'foretak') {
        wp_die('Ugyldig foretak.');
    }

    if (!current_user_can('edit_post', $foretak_id) && !current_user_can('manage_options')) {
        wp_die('Du har ikke tilgang til å godkjenne oppgraderinger.');
    }

    check_admin_referer('bimverdi_approve_oppgradering_' . $foretak_id);

    $pending = bimverdi_get_pending_oppgradering($foretak_id);
    if (!$pending) {
        wp_safe_redirect(add_query_arg([
            'bv_oppgradering' => 'error',
            'bv_msg'          => 'Ingen pending forespørsel funnet.',
        ], admin_url('post.php?post=' . $foretak_id . '&action=edit')));
        exit;
    }

    $level = $pending['level'];
    $requested_user_id = (int) ($pending['requested_by_user_id'] ?? 0);
    $admin_id = get_current_user_id();

    // Sett bv_rolle via ACF (faller tilbake til post_meta hvis ACF mangler)
    if (function_exists('update_field')) {
        $update_ok = update_field('bv_rolle', $level, $foretak_id);
    } else {
        $update_ok = update_post_meta($foretak_id, 'bv_rolle', $level);
    }

    if (!$update_ok) {
        // update_field returnerer false hvis verdien er uendret — sjekk faktisk verdi
        $current = function_exists('get_field') ? get_field('bv_rolle', $foretak_id) : get_post_meta($foretak_id, 'bv_rolle', true);
        if ($current !== $level) {
            error_log("BIMVerdi oppgradering: kunne ikke sette bv_rolle på foretak $foretak_id");
            wp_safe_redirect(add_query_arg([
                'bv_oppgradering' => 'error',
                'bv_msg'          => 'Kunne ikke oppdatere bv_rolle. Prøv igjen.',
            ], admin_url('post.php?post=' . $foretak_id . '&action=edit')));
            exit;
        }
    }

    // Append history
    bimverdi_append_oppgradering_history($foretak_id, [
        'type'     => 'approved',
        'level'    => $level,
        'user_id'  => $requested_user_id,
        'admin_id' => $admin_id,
    ]);

    // Clear pending
    bimverdi_clear_pending_oppgradering($foretak_id);

    // Send bekreftelses-e-post til bruker + kopi til post@bimverdi.no
    bimverdi_send_oppgradering_approved_emails($foretak_id, $level, $requested_user_id, $admin_id);

    wp_safe_redirect(add_query_arg('bv_oppgradering', 'approved', admin_url('post.php?post=' . $foretak_id . '&action=edit')));
    exit;
}

/**
 * Handler: Avvis oppgraderingsforespørsel med begrunnelse.
 * Endpoint: admin-post.php?action=bimverdi_reject_oppgradering (POST)
 */
add_action('admin_post_bimverdi_reject_oppgradering', 'bimverdi_handle_reject_oppgradering');

function bimverdi_handle_reject_oppgradering() {
    $foretak_id = isset($_POST['foretak_id']) ? (int) $_POST['foretak_id'] : 0;

    if (!$foretak_id || get_post_type($foretak_id) !== 'foretak') {
        wp_die('Ugyldig foretak.');
    }

    if (!current_user_can('edit_post', $foretak_id) && !current_user_can('manage_options')) {
        wp_die('Du har ikke tilgang til å avvise oppgraderinger.');
    }

    check_admin_referer('bimverdi_reject_oppgradering_' . $foretak_id);

    $reason = sanitize_textarea_field($_POST['reason'] ?? '');
    if (empty($reason)) {
        wp_safe_redirect(add_query_arg([
            'bv_oppgradering' => 'error',
            'bv_msg'          => 'Begrunnelse mangler.',
        ], admin_url('post.php?post=' . $foretak_id . '&action=edit')));
        exit;
    }

    $pending = bimverdi_get_pending_oppgradering($foretak_id);
    if (!$pending) {
        wp_safe_redirect(add_query_arg([
            'bv_oppgradering' => 'error',
            'bv_msg'          => 'Ingen pending forespørsel funnet.',
        ], admin_url('post.php?post=' . $foretak_id . '&action=edit')));
        exit;
    }

    $level = $pending['level'];
    $requested_user_id = (int) ($pending['requested_by_user_id'] ?? 0);
    $admin_id = get_current_user_id();

    bimverdi_append_oppgradering_history($foretak_id, [
        'type'     => 'rejected',
        'level'    => $level,
        'user_id'  => $requested_user_id,
        'admin_id' => $admin_id,
        'reason'   => $reason,
    ]);

    bimverdi_clear_pending_oppgradering($foretak_id);

    bimverdi_send_oppgradering_rejected_emails($foretak_id, $level, $requested_user_id, $admin_id, $reason);

    wp_safe_redirect(add_query_arg('bv_oppgradering', 'rejected', admin_url('post.php?post=' . $foretak_id . '&action=edit')));
    exit;
}

// =============================================================================
// E-POST: GODKJENNING + AVVISNING
// =============================================================================

function bimverdi_send_oppgradering_approved_emails($foretak_id, $level, $user_id, $admin_id) {
    $user = get_userdata($user_id);
    $foretak = get_post($foretak_id);
    $foretak_navn = $foretak ? $foretak->post_title : '(ukjent foretak)';
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    if ($user) {
        $bruker_subject = sprintf('[BIM Verdi] Velkommen som %s — %s', $level, $foretak_navn);
        $bruker_body = sprintf(
            '<p>Hei %s,</p>
            <p>Gratulerer! Oppgraderingen av <strong>%s</strong> til <strong>%s</strong> er godkjent.</p>
            <p>Faktura sendes separat. Du har nå tilgang til alle funksjoner som følger med ditt nivå på Min Side.</p>
            <p><a href="%s" style="background:#FF8B5E;color:#fff;padding:10px 16px;text-decoration:none;border-radius:6px;display:inline-block;">Gå til Min Side</a></p>
            <hr>
            <p style="font-size:12px;color:#666;">
                <a href="%s">Betingelser for medlemskap i BIM Verdi</a>
            </p>
            <p style="font-size:12px;color:#666;">Mvh<br>BIM Verdi</p>',
            esc_html($user->display_name),
            esc_html($foretak_navn),
            esc_html($level),
            esc_url(home_url('/min-side/')),
            esc_url(BV_TERMS_URL)
        );
        $sent = wp_mail($user->user_email, $bruker_subject, $bruker_body, $headers);
        if (!$sent) {
            error_log("BIMVerdi oppgradering approved-mail feilet for $user->user_email");
        }
    }

    $admin_subject = sprintf('Oppgradering godkjent: %s → %s', $foretak_navn, $level);
    $admin_body = sprintf(
        '<p>Oppgradering godkjent og loggført:</p>
        <table style="border-collapse:collapse;font-size:14px;">
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Foretak</td><td><strong>%s</strong></td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Nytt nivå</td><td><strong>%s</strong></td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Hovedkontakt</td><td>%s</td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Godkjent av</td><td>%s</td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Tidspunkt</td><td>%s</td></tr>
        </table>
        <p style="font-size:12px;color:#666;margin-top:16px;">Lagre dette i SuperOffice som dokumentasjon på avtalt deltakernivå. Faktura sendes separat.</p>',
        esc_html($foretak_navn),
        esc_html($level),
        $user ? esc_html($user->display_name . ' <' . $user->user_email . '>') : 'Ukjent bruker',
        esc_html(get_userdata($admin_id)->display_name ?? 'Ukjent'),
        esc_html(date_i18n('j. F Y \k\l. H:i'))
    );
    $sent_admin = wp_mail(BV_NOTIFY_EMAIL, $admin_subject, $admin_body, $headers);
    if (!$sent_admin) {
        error_log('BIMVerdi oppgradering approved-admin-mail feilet');
    }
}

function bimverdi_send_oppgradering_rejected_emails($foretak_id, $level, $user_id, $admin_id, $reason) {
    $user = get_userdata($user_id);
    $foretak = get_post($foretak_id);
    $foretak_navn = $foretak ? $foretak->post_title : '(ukjent foretak)';
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    if ($user) {
        $bruker_subject = sprintf('[BIM Verdi] Oppdatering om oppgraderingsforespørsel — %s', $foretak_navn);
        $bruker_body = sprintf(
            '<p>Hei %s,</p>
            <p>Vi har vurdert din forespørsel om å oppgradere <strong>%s</strong> til <strong>%s</strong>.</p>
            <p>Begrunnelse fra BIM Verdi:</p>
            <blockquote style="border-left:3px solid #E5E0D5;padding:8px 16px;color:#555;margin:12px 0;">
                %s
            </blockquote>
            <p>Hvis du har spørsmål eller ønsker å justere forespørselen, ta gjerne kontakt med Bård direkte (<a href="mailto:%s">%s</a>) — eller send en ny forespørsel fra Min Side.</p>
            <hr>
            <p style="font-size:12px;color:#666;">
                <a href="%s">Betingelser for medlemskap i BIM Verdi</a>
            </p>
            <p style="font-size:12px;color:#666;">Mvh<br>BIM Verdi</p>',
            esc_html($user->display_name),
            esc_html($foretak_navn),
            esc_html($level),
            nl2br(esc_html($reason)),
            esc_attr(BV_NOTIFY_EMAIL),
            esc_html(BV_NOTIFY_EMAIL),
            esc_url(BV_TERMS_URL)
        );
        $sent = wp_mail($user->user_email, $bruker_subject, $bruker_body, $headers);
        if (!$sent) {
            error_log("BIMVerdi oppgradering rejected-mail feilet for $user->user_email");
        }
    }

    $admin_subject = sprintf('Oppgradering avvist: %s → %s', $foretak_navn, $level);
    $admin_body = sprintf(
        '<p>Oppgradering avvist og loggført:</p>
        <table style="border-collapse:collapse;font-size:14px;">
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Foretak</td><td><strong>%s</strong></td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Forespurt nivå</td><td>%s</td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Bruker</td><td>%s</td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Avvist av</td><td>%s</td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;">Tidspunkt</td><td>%s</td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;vertical-align:top;">Begrunnelse</td><td>%s</td></tr>
        </table>',
        esc_html($foretak_navn),
        esc_html($level),
        $user ? esc_html($user->display_name . ' <' . $user->user_email . '>') : 'Ukjent bruker',
        esc_html(get_userdata($admin_id)->display_name ?? 'Ukjent'),
        esc_html(date_i18n('j. F Y \k\l. H:i')),
        nl2br(esc_html($reason))
    );
    $sent_admin = wp_mail(BV_NOTIFY_EMAIL, $admin_subject, $admin_body, $headers);
    if (!$sent_admin) {
        error_log('BIMVerdi oppgradering rejected-admin-mail feilet');
    }
}

// =============================================================================
// AUDIT-LOG META-BOX (foretak edit-skjerm)
// =============================================================================

add_action('add_meta_boxes', 'bimverdi_register_oppgradering_history_metabox');

function bimverdi_register_oppgradering_history_metabox() {
    add_meta_box(
        'bv_oppgradering_history',
        'Oppgraderings-historikk',
        'bimverdi_render_oppgradering_history_metabox',
        'foretak',
        'normal',
        'low'
    );
}

function bimverdi_render_oppgradering_history_metabox($post) {
    $history = bimverdi_get_oppgradering_history($post->ID);

    if (empty($history)) {
        echo '<p style="color:#666;margin:0;">Ingen historikk.</p>';
        return;
    }

    // Reverse: nyeste først
    $events = array_reverse($history);

    $type_config = [
        'request'  => ['icon' => '⏳', 'label' => 'Forespørsel sendt', 'color' => '#FF8B5E'],
        'approved' => ['icon' => '✅', 'label' => 'Godkjent',           'color' => '#10B981'],
        'rejected' => ['icon' => '❌', 'label' => 'Avvist',             'color' => '#dc2626'],
    ];

    echo '<table class="widefat striped" style="border:none;">';
    echo '<thead><tr><th style="width:40px;"></th><th>Hendelse</th><th>Nivå</th><th>Bruker / Admin</th><th>Tidspunkt</th></tr></thead>';
    echo '<tbody>';

    foreach ($events as $event) {
        $type = $event['type'] ?? 'request';
        $cfg = $type_config[$type] ?? $type_config['request'];

        $user = !empty($event['user_id']) ? get_userdata((int) $event['user_id']) : null;
        $user_label = $user ? $user->display_name : 'Ukjent bruker';

        $admin = !empty($event['admin_id']) ? get_userdata((int) $event['admin_id']) : null;
        $admin_label = $admin ? ' (admin: ' . esc_html($admin->display_name) . ')' : '';

        $date_label = !empty($event['date']) ? date_i18n('j. F Y \k\l. H:i', strtotime($event['date'])) : '—';

        printf(
            '<tr><td style="font-size:18px;">%s</td><td><strong style="color:%s;">%s</strong>%s</td><td>%s</td><td>%s%s</td><td>%s</td></tr>',
            esc_html($cfg['icon']),
            esc_attr($cfg['color']),
            esc_html($cfg['label']),
            !empty($event['reason']) ? '<br><span style="color:#666;font-size:12px;">' . esc_html($event['reason']) . '</span>' : '',
            esc_html($event['level'] ?? '—'),
            esc_html($user_label),
            $admin_label,
            esc_html($date_label)
        );
    }

    echo '</tbody></table>';
}
