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
 * Gratisbrukere godtar personvern-betingelser; betalende nivåer godtar
 * deltakelses-betingelser. Default (uten nivå) viser deltakelses-tekst.
 *
 * @param string $name  HTML name-attributt (default 'aksept_betingelser').
 * @param string $nivaa Plan-key ('gratis' | 'deltaker' | 'prosjektdeltaker' | 'partner' | '').
 * @return string HTML
 */
function bimverdi_render_terms_acceptance_field($name = 'aksept_betingelser', $nivaa = '') {
    $is_gratis = ($nivaa === 'gratis');
    $link_url  = $is_gratis ? home_url('/personvern/') : BV_TERMS_URL;
    $link_text = $is_gratis ? 'betingelsene for personvern' : 'betingelsene for deltakelse i BIM Verdi';

    ob_start();
    ?>
    <label class="flex items-start gap-3 cursor-pointer">
        <input type="checkbox" name="<?php echo esc_attr($name); ?>" value="1" required
               class="w-4 h-4 mt-0.5 border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E] flex-shrink-0">
        <span class="text-sm text-[#1A1A1A]">
            Jeg aksepterer
            <a href="<?php echo esc_url($link_url); ?>" target="_blank" rel="noopener" class="text-[#FF8B5E] underline underline-offset-2 hover:text-[#E5764A]">
                <?php echo esc_html($link_text); ?>
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
// FORETAK-HELPER: ROLLE-LABEL
// =============================================================================

/**
 * Returner brukervennlig label for et foretaks rolle.
 *
 * Mapper bv_rolle-verdier til riktig "[rolle]foretak"-tekst slik at f.eks.
 * 'Deltaker' vises som 'Deltakerforetak' (ikke 'Inaktiv deltaker') i UI.
 *
 * @param string|null $bv_rolle ACF-select-verdi ('Ikke deltaker', 'Deltaker', 'Prosjektdeltaker', 'Partner').
 * @return string Visningstekst.
 */
function bimverdi_foretak_rolle_label($bv_rolle) {
    switch ((string) $bv_rolle) {
        case 'Ikke deltaker':
            return 'Gratis brukerforetak';
        case 'Deltaker':
            return 'Deltakerforetak';
        case 'Prosjektdeltaker':
            return 'Prosjektdeltakerforetak';
        case 'Partner':
            return 'Partnerforetak';
        default:
            return $bv_rolle !== '' && $bv_rolle !== null ? (string) $bv_rolle : 'Foretak';
    }
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

// =============================================================================
// ACF-FELTER: FAKTURAINFORMASJON PÅ FORETAK-CPT
// =============================================================================

/**
 * Registrer ACF-felter for fakturainformasjon på foretak-CPT.
 *
 * Plan: docs/plans/2026-04-30-001-feat-fakturafelter-rabatt-disclaimer-deltakerniva-rename-plan.md
 *
 * Felter:
 *  - ehf_faktura: radio (ja/nei), default 'nei'
 *  - faktura_epost: email, conditional på ehf_faktura == nei
 *  - faktura_referanse: text (prosjektnummer eller intern referanse)
 *
 * Programmatisk registrering (acf_add_local_field_group) gir IDEMPOTENT
 * felt-definisjon uten DB-migrering. Felter vises automatisk i wp-admin
 * foretak-edit og er tilgjengelige via get_field()/update_field().
 */
add_action('acf/init', 'bimverdi_register_fakturainformasjon_fields');

function bimverdi_register_fakturainformasjon_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key'      => 'group_bv_fakturainformasjon',
        'title'    => 'Fakturainformasjon',
        'fields'   => [
            [
                'key'           => 'field_bv_ehf_faktura',
                'label'         => 'Bruker foretaket EHF-faktura?',
                'name'          => 'ehf_faktura',
                'type'          => 'radio',
                'instructions'  => 'EHF (Elektronisk handelsformat) er standard for offentlige og store private aktører.',
                'required'      => 0,
                'choices'       => [
                    'ja'  => 'Ja',
                    'nei' => 'Nei',
                ],
                'default_value' => 'nei',
                'layout'        => 'horizontal',
                'return_format' => 'value',
            ],
            [
                'key'           => 'field_bv_faktura_epost',
                'label'         => 'Faktura-e-post',
                'name'          => 'faktura_epost',
                'type'          => 'email',
                'instructions'  => 'E-postadresse som faktura sendes til. Påkrevd hvis EHF ikke brukes.',
                'required'      => 0,
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'field_bv_ehf_faktura',
                            'operator' => '==',
                            'value'    => 'nei',
                        ],
                    ],
                ],
            ],
            [
                'key'           => 'field_bv_faktura_referanse',
                'label'         => 'Faktura-referanse / prosjektnummer',
                'name'          => 'faktura_referanse',
                'type'          => 'text',
                'instructions'  => 'Brukes for fakturaadressering. Kan være prosjektnummer eller intern referanse. Uten dette risikerer fakturaen å bli returnert.',
                'required'      => 0,
                'maxlength'     => 100,
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'foretak',
                ],
            ],
        ],
        'menu_order'      => 5,
        'position'        => 'normal',
        'style'           => 'default',
        'label_placement' => 'top',
        'active'          => true,
        'description'     => 'Fakturadata for manuell fakturering ved oppgradering. Pre-populeres når brukere registrerer/oppgraderer.',
    ]);
}
