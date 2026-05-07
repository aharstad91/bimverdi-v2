<?php
/**
 * Min Side — Oppgraderingsskjema
 *
 * Kun tilgjengelig for hovedkontakt for gratisforetak (bv_rolle = 'Ikke deltaker').
 * Submitter til admin-init-handler i bimverdi-foretak-oppgradering.php.
 *
 * Plan: docs/plans/2026-04-29-001-feat-oppgraderingsvei-manuell-godkjenning-plan.md
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

// Tilgangskontroll: må være hovedkontakt for gratisforetak
$foretak_id = bimverdi_user_can_request_oppgradering();
if (!$foretak_id) {
    // Send dem tilbake til foretak-siden med riktig feilmelding
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_safe_redirect(home_url('/logg-inn/?redirect_to=' . urlencode(home_url('/min-side/foretak/oppgrader/'))));
        exit;
    }
    if (!bimverdi_user_has_company($user_id)) {
        wp_safe_redirect(home_url('/min-side/foretak/?bv_error=no_company'));
        exit;
    }
    if (!bimverdi_is_hovedkontakt($user_id)) {
        wp_safe_redirect(home_url('/min-side/foretak/?bv_error=not_hovedkontakt'));
        exit;
    }
    // Foretak er allerede betalende
    wp_safe_redirect(home_url('/min-side/foretak/?bv_error=already_paying'));
    exit;
}

$foretak       = get_post($foretak_id);
$foretak_navn  = $foretak ? $foretak->post_title : '';
$pending       = bimverdi_get_pending_oppgradering($foretak_id);
$bv_error      = isset($_GET['bv_error']) ? sanitize_key($_GET['bv_error']) : '';

// Two-step-flyt: ?nivaa=X velger nivå før faktura-skjemaet vises.
// Validér mot ACF, og ekskluder 'gratis' (ikke gyldig oppgraderingsmål).
$valid_oppgr_keys = function_exists('bimverdi_pricing_valid_plan_keys')
    ? array_values(array_filter(bimverdi_pricing_valid_plan_keys(), function ($k) { return $k !== 'gratis'; }))
    : [];
$selected_nivaa = isset($_GET['nivaa']) ? sanitize_key($_GET['nivaa']) : '';
if ($selected_nivaa && !in_array($selected_nivaa, $valid_oppgr_keys, true)) {
    $selected_nivaa = '';
}
$show_pricing_picker = empty($selected_nivaa);
// Visning: hent fra ACF plan_title (kan være Bård-redigert).
$selected_label = $selected_nivaa
    ? (function_exists('bimverdi_pricing_plan_title') ? bimverdi_pricing_plan_title($selected_nivaa) : ucfirst($selected_nivaa))
    : '';
// Submit-verdi: server-handler forventer Deltaker / Prosjektdeltaker / Partner
// (capitalized lowercase plan_key). Holder mapping uavhengig av plan_title
// for å være robust mot Bård-redigering av visningsnavn.
$selected_level = $selected_nivaa ? ucfirst($selected_nivaa) : '';

$error_messages = [
    'missing_level'         => 'Du må velge et nivå før du kan sende forespørselen.',
    'missing_terms'         => 'Du må akseptere betingelsene for å sende forespørselen.',
    'missing_invoice_email' => 'Faktura-e-post er påkrevd når EHF-faktura ikke brukes.',
    'missing_invoice_ref'   => 'Faktura-referanse er påkrevd.',
    'invalid_invoice_email' => 'Faktura-e-postadressen er ikke gyldig.',
    'rate_limit'            => 'For mange forsøk. Prøv igjen senere.',
    'nonce'                 => 'Sikkerhetstoken utløpt. Last siden på nytt og prøv igjen.',
    'invalid_level'         => 'Ugyldig nivå valgt. Prøv igjen.',
    'generic'               => 'Forespørselen kunne ikke sendes. Prøv igjen senere.',
];

// Pre-populer fakturafelter fra foretak-meta hvis allerede satt
$existing_ehf       = function_exists('get_field') ? get_field('ehf_faktura', $foretak_id) : '';
$existing_epost     = function_exists('get_field') ? get_field('faktura_epost', $foretak_id) : '';
$existing_referanse = function_exists('get_field') ? get_field('faktura_referanse', $foretak_id) : '';
if (empty($existing_ehf)) {
    $existing_ehf = 'nei';
}

?>

<div class="max-w-[960px] mx-auto px-6 py-12">

    <!-- Tilbake-lenke -->
    <a href="<?php echo esc_url(home_url('/min-side/foretak/')); ?>" class="inline-flex items-center gap-2 text-sm text-[#5A5A5A] hover:text-[#1A1A1A] mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        Tilbake til foretak
    </a>

    <!-- Header -->
    <header class="mb-10">
        <h1 class="text-3xl font-light text-[#1A1A1A] tracking-tight mb-2">Oppgrader til betalende deltaker</h1>
        <p class="text-base text-[#5A5A5A]">
            <?php echo esc_html($foretak_navn); ?> er i dag registrert som gratis brukerforetak.
            Velg ønsket deltakernivå nedenfor for å sende forespørsel til BIM Verdi.
            Du blir kontaktet manuelt for bekreftelse og fakturering.
        </p>
    </header>

    <?php if ($pending): ?>
        <!-- Pending-status -->
        <div class="border border-[#E5E0D5] rounded-lg p-6 bg-[#FFF8F5] mb-8">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FF8B5E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-[#1A1A1A] mb-1">Du har en pending forespørsel</h2>
                    <p class="text-sm text-[#5A5A5A]">
                        Forespørsel for <strong><?php echo esc_html($pending['level']); ?></strong> ble sendt
                        <?php echo esc_html(date_i18n('j. F Y \k\l. H:i', strtotime($pending['requested_at']))); ?>.
                        Bård vurderer den manuelt og sender bekreftelse + faktura når den er godkjent.
                    </p>
                    <p class="text-sm text-[#5A5A5A] mt-3">
                        Hvis du ønsker å endre forespørselen til et annet nivå, kan du sende en ny under — den vil erstatte den eksisterende.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($bv_error && isset($error_messages[$bv_error])): ?>
        <div class="border border-red-200 bg-red-50 rounded-lg p-4 mb-6">
            <p class="text-sm text-red-800"><?php echo esc_html($error_messages[$bv_error]); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($show_pricing_picker): ?>
        <!-- Step 1: bruker velger nivå via pricing-blokka. Klikk «Velg» lander på
             samme URL med ?nivaa={plan_key} → step 2 (faktura-skjema). -->
        <div class="mb-6">
            <h2 class="text-base font-semibold text-[#1A1A1A] mb-2"><?php _e('Velg deltakernivå', 'bimverdi'); ?></h2>
            <p class="text-sm text-[#5A5A5A]">
                <?php _e('Klikk «Velg» for nivået du vil oppgradere til. Du fyller inn faktura-info i neste steg.', 'bimverdi'); ?>
            </p>
        </div>
        <?php
        if (function_exists('bimverdi_pricing_table')) {
            echo bimverdi_pricing_table(null, [
                'cta_url_template'  => '/min-side/foretak/oppgrader/?nivaa={plan_key}',
                'exclude_plan_keys' => ['gratis'],
            ]);
        }
        ?>
    <?php else: ?>
    <form method="post" action="<?php echo esc_url(home_url('/min-side/foretak/oppgrader/')); ?>" class="space-y-8">
        <?php wp_nonce_field('bimverdi_oppgradering_request', '_wpnonce'); ?>
        <input type="hidden" name="bimverdi_oppgradering_request" value="1">
        <input type="hidden" name="foretak_id" value="<?php echo esc_attr($foretak_id); ?>">
        <input type="hidden" name="level" value="<?php echo esc_attr($selected_level); ?>">

        <!-- Honeypot (skjult, fanger bots) -->
        <div style="position:absolute;left:-9999px;" aria-hidden="true">
            <label for="bv_website_url">Nettside (la stå tom)</label>
            <input type="text" name="bv_website_url" id="bv_website_url" tabindex="-1" autocomplete="off">
        </div>

        <!-- Step 2: bekreft valgt nivå + tilbud om endring. -->
        <div class="flex items-start justify-between gap-4 p-4 bg-[#FFF8F5] border border-[#FF8B5E]/30 rounded-lg">
            <div>
                <p class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide mb-1"><?php _e('Valgt deltakernivå', 'bimverdi'); ?></p>
                <p class="text-base font-semibold text-[#1A1A1A]"><?php echo esc_html($selected_label); ?></p>
            </div>
            <a href="<?php echo esc_url(home_url('/min-side/foretak/oppgrader/')); ?>" class="text-sm text-[#FF8B5E] hover:underline whitespace-nowrap">
                <?php _e('Endre nivå', 'bimverdi'); ?>
            </a>
        </div>

        <hr class="border-[#E5E0D5]">

        <!-- Fakturainformasjon -->
        <fieldset>
            <legend class="text-sm font-semibold text-[#1A1A1A] mb-1">Fakturainformasjon</legend>
            <p class="text-xs text-[#888888] mb-3">Brukes når BIM Verdi sender faktura etter godkjenning. Forhåndsutfylt fra foretaket hvis allerede satt — endre om nødvendig.</p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                        Bruker foretaket EHF-faktura? <span class="text-red-600">*</span>
                    </label>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="ehf_faktura" value="ja"<?php checked($existing_ehf, 'ja'); ?>
                                   class="w-4 h-4 border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                            <span class="text-sm text-[#1A1A1A]">Ja</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="ehf_faktura" value="nei"<?php checked($existing_ehf, 'nei'); ?>
                                   class="w-4 h-4 border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                            <span class="text-sm text-[#1A1A1A]">Nei</span>
                        </label>
                    </div>
                </div>

                <div id="bv-faktura-epost-wrapper">
                    <label for="faktura_epost" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                        Faktura-e-post <span class="text-red-600">*</span>
                    </label>
                    <input type="email"
                           id="faktura_epost"
                           name="faktura_epost"
                           value="<?php echo esc_attr($existing_epost); ?>"
                           placeholder="faktura@firma.no"
                           class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent text-[#1A1A1A] placeholder:text-[#A8A29E]">
                    <p class="mt-1 text-xs text-[#888888]">E-postadresse som faktura sendes til. Påkrevd når EHF ikke brukes.</p>
                </div>

                <div>
                    <label for="faktura_referanse" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                        Faktura-referanse / prosjektnummer <span class="text-red-600">*</span>
                    </label>
                    <input type="text"
                           id="faktura_referanse"
                           name="faktura_referanse"
                           value="<?php echo esc_attr($existing_referanse); ?>"
                           maxlength="100"
                           required
                           class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent text-[#1A1A1A] placeholder:text-[#A8A29E]">
                    <p class="mt-1 text-xs text-[#888888]">Brukes for fakturaadressering. Kan være prosjektnummer eller intern referanse.</p>
                </div>
            </div>
        </fieldset>

        <hr class="border-[#E5E0D5]">

        <!-- Betingelser -->
        <fieldset>
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="accept_terms" value="1" required
                       class="w-4 h-4 mt-0.5 border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E] flex-shrink-0">
                <span class="text-sm text-[#1A1A1A]">
                    Jeg aksepterer
                    <a href="<?php echo esc_url(defined('BV_TERMS_URL') ? BV_TERMS_URL : 'https://www.bimverdi.no/betingelser'); ?>" target="_blank" rel="noopener" class="text-[#FF8B5E] underline underline-offset-2 hover:text-[#E5764A]">
                        betingelsene for medlemskap i BIM Verdi
                    </a>
                    <span class="text-red-600">*</span>
                </span>
            </label>
        </fieldset>

        <!-- Submit -->
        <div class="flex items-center gap-4 pt-4">
            <button type="submit" class="bv-btn bv-btn--primary">
                Send oppgraderingsforespørsel
            </button>
            <a href="<?php echo esc_url(home_url('/min-side/foretak/')); ?>" class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A]">
                Avbryt
            </a>
        </div>

        <p class="text-xs text-[#888888]">
            Når du sender forespørselen får du en bekreftelse på e-post. Bård i BIM Verdi godkjenner manuelt
            og sender deretter velkomst-e-post og faktura.
        </p>

    </form>
    <?php endif; ?>

</div>

<script>
(function() {
  'use strict';
  document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('form[action*="oppgrader"]');
    if (!form) return;

    var wrapper = document.getElementById('bv-faktura-epost-wrapper');
    var epostInput = document.getElementById('faktura_epost');
    if (!wrapper || !epostInput) return;

    function syncEpostRequired() {
      var ehf = form.querySelector('input[name="ehf_faktura"]:checked');
      var ehfNei = ehf && ehf.value === 'nei';
      wrapper.style.display = ehfNei ? '' : 'none';
      if (ehfNei) {
        epostInput.setAttribute('required', '');
      } else {
        epostInput.removeAttribute('required');
      }
    }

    form.addEventListener('change', function(e) {
      if (e.target.name === 'ehf_faktura') syncEpostRequired();
    });

    // Initial state
    syncEpostRequired();
  });
})();
</script>
