<?php
/**
 * BIM Verdi — Felles helpers for skjemaer og e-poster.
 *
 * Sentral plassering for konstanter og helpers som brukes på tvers av flere
 * mu-plugins for registrerings-/innmeldings-flyter:
 *  - BV_NOTIFY_EMAIL: SuperOffice-kopi-adresse (post@bimverdi.no)
 *  - BV_TERMS_URL: lenke til betingelsene
 *  - bimverdi_render_terms_acceptance_field(): checkbox-blokk for skjema
 *  - bimverdi_render_terms_footer_html(): footer-snippet for e-post
 *  - bimverdi_send_admin_notification_email(): wp_mail-wrapper til BV_NOTIFY_EMAIL
 *  - bimverdi_validate_terms_acceptance(): server-side checkbox-validering
 *
 * Plan: docs/plans/2026-04-29-002-feat-bard-krav-eksisterende-skjemaer-plan.md
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// KONSTANTER (felles)
// =============================================================================

if (!defined('BV_NOTIFY_EMAIL')) {
    /** E-post-adresse for SuperOffice-dokumentasjon (kopi av alle hendelser). */
    define('BV_NOTIFY_EMAIL', 'post@bimverdi.no');
}

if (!defined('BV_TERMS_URL')) {
    /** Lenke til betingelser (skal med i alle skjemaer og e-poster). */
    define('BV_TERMS_URL', 'https://www.bimverdi.no/betingelser');
}

// =============================================================================
// FORM-HELPER: AKSEPT-CHECKBOX
// =============================================================================

/**
 * Render aksept-checkbox-blokk for skjema.
 *
 * Returnerer komplett <label>-blokk med checkbox + lenke til betingelser.
 * Konsistent UI på tvers av alle BV-skjemaer som krever aktiv aksept.
 *
 * @param string $name HTML name-attributt (default 'aksept_betingelser').
 * @return string HTML
 */
function bimverdi_render_terms_acceptance_field($name = 'aksept_betingelser') {
    ob_start();
    ?>
    <label class="flex items-start gap-3 cursor-pointer">
        <input type="checkbox" name="<?php echo esc_attr($name); ?>" value="1" required
               class="w-4 h-4 mt-0.5 border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E] flex-shrink-0">
        <span class="text-sm text-[#1A1A1A]">
            Jeg aksepterer
            <a href="<?php echo esc_url(BV_TERMS_URL); ?>" target="_blank" rel="noopener" class="text-[#FF8B5E] underline underline-offset-2 hover:text-[#E5764A]">
                betingelsene for medlemskap i BIM Verdi
            </a>
            <span class="text-red-600">*</span>
        </span>
    </label>
    <?php
    return ob_get_clean();
}

/**
 * Server-side validering av aksept-checkbox.
 *
 * @param array  $post_data POST-data (vanligvis $_POST).
 * @param string $field     Felt-navn (default 'aksept_betingelser').
 * @return bool true hvis akseptert, false ellers.
 */
function bimverdi_validate_terms_acceptance($post_data, $field = 'aksept_betingelser') {
    if (!is_array($post_data) || !isset($post_data[$field])) {
        return false;
    }
    return (string) $post_data[$field] === '1';
}

// =============================================================================
// EMAIL-HELPER: TERMS-FOOTER
// =============================================================================

/**
 * Render terms-footer HTML-snippet for innsetting i e-post-bodies.
 *
 * Brukes nederst i alle bekreftelses-/notifikasjons-e-poster slik at
 * mottakeren har lenken tilgjengelig.
 *
 * @return string HTML
 */
function bimverdi_render_terms_footer_html() {
    return sprintf(
        '<hr style="border:none;border-top:1px solid #E5E0D5;margin:24px 0 16px 0;">
        <p style="font-size:12px;color:#666;line-height:1.5;">
            Ved å bruke BIM Verdi godtar du våre
            <a href="%s" style="color:#FF8B5E;">betingelser for medlemskap</a>.
        </p>
        <p style="font-size:12px;color:#666;line-height:1.5;">Mvh<br>BIM Verdi</p>',
        esc_url(BV_TERMS_URL)
    );
}

// =============================================================================
// EMAIL-HELPER: ADMIN-KOPI (post@bimverdi.no)
// =============================================================================

/**
 * Send admin-kopi-e-post til BV_NOTIFY_EMAIL (SuperOffice/CRM-dokumentasjon).
 *
 * Wrapper rundt wp_mail() som setter default text/html-Content-Type, BV-From-header,
 * og logger til error_log ved feil. Blokkerer aldri kalleren — returnerer bool.
 *
 * @param string     $subject E-post-emne.
 * @param string     $body    HTML-body (terms-footer settes IKKE inn automatisk; legg til via bimverdi_render_terms_footer_html() hvis ønskelig).
 * @param array|null $headers Ekstra headers (default: text/html + BV-From).
 * @return bool wp_mail-resultat.
 */
function bimverdi_send_admin_notification_email($subject, $body, $headers = null) {
    if ($headers === null) {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: BIM Verdi <noreply@bimverdi.no>',
        ];
    }

    $sent = wp_mail(BV_NOTIFY_EMAIL, $subject, $body, $headers);

    if (!$sent) {
        error_log('BIMVerdi shared-helpers: admin-kopi-e-post feilet til ' . BV_NOTIFY_EMAIL . ' (subject: ' . $subject . ')');
    }

    return $sent;
}
