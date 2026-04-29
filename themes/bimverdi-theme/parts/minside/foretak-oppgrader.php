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

$error_messages = [
    'missing_level'  => 'Du må velge et nivå før du kan sende forespørselen.',
    'missing_terms'  => 'Du må akseptere betingelsene for å sende forespørselen.',
    'rate_limit'     => 'For mange forsøk. Prøv igjen senere.',
    'nonce'          => 'Sikkerhetstoken utløpt. Last siden på nytt og prøv igjen.',
    'invalid_level'  => 'Ugyldig nivå valgt. Prøv igjen.',
    'generic'        => 'Forespørselen kunne ikke sendes. Prøv igjen senere.',
];

$nivaaer = [
    'Deltaker' => [
        'label'     => 'Deltaker',
        'features'  => ['Temagrupper og lukkede møter', 'Verktøyregistrering', 'Rabatt på konferanser'],
        'personer'  => 3,
        'pris'      => '8 000',
    ],
    'Prosjektdeltaker' => [
        'label'     => 'Prosjektdeltaker',
        'features'  => ['Alt i Deltaker', '1-2 timer rådgivning/mnd', 'Prosjektkonsortier'],
        'personer'  => 4,
        'pris'      => '24 000',
    ],
    'Partner' => [
        'label'     => 'Partner',
        'features'  => ['Alt i Prosjektdeltaker', 'Utvidet rådgivning', 'Styringsgruppe og piloter'],
        'personer'  => 5,
        'pris'      => '48 000',
    ],
];
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
            Velg ønsket medlemsnivå nedenfor for å sende forespørsel til BIM Verdi.
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

    <form method="post" action="<?php echo esc_url(home_url('/min-side/foretak/oppgrader/')); ?>" class="space-y-8">
        <?php wp_nonce_field('bimverdi_oppgradering_request', '_wpnonce'); ?>
        <input type="hidden" name="bimverdi_oppgradering_request" value="1">
        <input type="hidden" name="foretak_id" value="<?php echo esc_attr($foretak_id); ?>">

        <!-- Honeypot (skjult, fanger bots) -->
        <div style="position:absolute;left:-9999px;" aria-hidden="true">
            <label for="bv_website_url">Nettside (la stå tom)</label>
            <input type="text" name="bv_website_url" id="bv_website_url" tabindex="-1" autocomplete="off">
        </div>

        <!-- Nivå-velger -->
        <fieldset>
            <legend class="text-sm font-semibold text-[#1A1A1A] mb-1">
                Velg medlemsnivå <span class="text-red-600">*</span>
            </legend>
            <p class="text-xs text-[#888888] mb-3">Du kan oppgradere senere hvis behovet endres</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <?php foreach ($nivaaer as $value => $type): ?>
                    <label class="relative p-4 rounded-lg border border-[#E5E0D5] hover:border-[#FF8B5E] hover:bg-[#FFF8F5] transition-colors cursor-pointer has-[:checked]:border-[#FF8B5E] has-[:checked]:bg-[#FFF8F5] flex flex-col">
                        <div class="flex items-center gap-2 mb-2">
                            <input type="radio" name="level" value="<?php echo esc_attr($value); ?>" required
                                   class="w-4 h-4 border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E] flex-shrink-0">
                            <span class="text-sm font-semibold text-[#1A1A1A]"><?php echo esc_html($type['label']); ?></span>
                        </div>
                        <ul class="space-y-1 flex-1">
                            <?php foreach ($type['features'] as $feature): ?>
                                <li class="text-xs text-[#5A5A5A] flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><path d="M20 6 9 17l-5-5"/></svg>
                                    <?php echo esc_html($feature); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="mt-3 pt-3 border-t border-[#E5E0D5] text-xs text-[#888888]">
                            <?php echo (int) $type['personer']; ?> personer · <?php echo esc_html($type['pris']); ?> kr/år
                        </p>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="mt-2 text-xs text-[#888888]">Fakturering avtales separat med BIM Verdi etter godkjenning</p>
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

</div>
