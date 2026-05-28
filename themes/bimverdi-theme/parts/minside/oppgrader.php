<?php
/**
 * Min Side — Selvbetjent oppgradering (krav 24-v4, oppdatert 2026-05-28)
 *
 * Route: /min-side/oppgrader/
 * Tilgjengelig for: Gratisbrukere (bv_foretakstype = 'gratisforetak')
 *
 * Endringer 2026-05-28 (møte med Bård):
 *  - Kvartalsvis pris-automatikk fjernet. Viser årspris.
 *  - Steg "Bekreft foretaksdata" fjernet (data kommer fra BRREG og kan
 *    ikke endres her uansett).
 *  - EHF-felt lagt til i faktureringsdetaljer med betinget validering:
 *    minst én av EHF eller fakturamottakers e-post må fylles ut.
 *  - Statisk disclaimer om kvartalsvis avregning beholdt som tekst.
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();

// Access-gate: kun Gratisbrukere (AK-03)
if (!function_exists('bimverdi_is_gratisbruker') || !bimverdi_is_gratisbruker($user_id)) {
    wp_safe_redirect(bimverdi_minside_url(''));
    exit;
}

$foretak_id = bimverdi_resolve_user_foretak_id($user_id);
$foretak    = $foretak_id ? get_post($foretak_id) : null;

if (!$foretak) {
    wp_safe_redirect(bimverdi_minside_url(''));
    exit;
}

$foretak_navn = get_the_title($foretak_id);
$priser       = function_exists('bimverdi_get_betingelser_prices') ? bimverdi_get_betingelser_prices() : null;

$user = wp_get_current_user();
$default_faktura_email = $user->user_email;

$error = isset($_GET['bv_error']) ? sanitize_text_field($_GET['bv_error']) : '';

$error_messages = [
    'invalid_nonce'         => __('Sikkerhetstoken utløpt. Last siden på nytt og prøv igjen.', 'bimverdi'),
    'spam_detected'         => __('Innsendingen ble blokkert som spam.', 'bimverdi'),
    'not_gratisbruker'      => __('Bare gratisforetak kan oppgraderes herfra.', 'bimverdi'),
    'no_foretak'            => __('Du er ikke tilknyttet et foretak.', 'bimverdi'),
    'invalid_nivaa'         => __('Ugyldig nivå valgt. Prøv igjen.', 'bimverdi'),
    'missing_acceptance'    => __('Du må godta medlemsbetingelsene for å fullføre oppgraderingen.', 'bimverdi'),
    'missing_invoice_kanal' => __('Du må fylle ut enten EHF-organisasjonsnummer eller fakturamottakers e-post.', 'bimverdi'),
    'invalid_invoice_email' => __('Fakturamottakers e-post er ikke gyldig.', 'bimverdi'),
    'pris_kunne_ikke_hentes' => __('Prisene kunne ikke hentes akkurat nå. Prøv igjen senere.', 'bimverdi'),
];
?>

<div class="max-w-[960px] mx-auto">

    <header class="pb-8 border-b border-[#D6D1C6]">
        <p class="text-sm text-[#5A5A5A] uppercase tracking-wide mb-2"><?php _e('Bli Deltaker+', 'bimverdi'); ?></p>
        <h1 class="text-3xl font-light text-[#1A1A1A]"><?php printf(esc_html__('Oppgrader %s', 'bimverdi'), esc_html($foretak_navn)); ?></h1>
        <p class="mt-3 text-[#5A5A5A] leading-relaxed max-w-2xl">
            <?php _e('Etter oppgraderingen blir du hovedkontakt for foretaket, og alle andre gratisbrukere i ditt foretak blir tilleggskontakter. Konverteringen skjer umiddelbart.', 'bimverdi'); ?>
        </p>
    </header>

    <?php if ($error): ?>
        <div class="mt-6 p-4 border border-[#772015] bg-[#FFF5F2] text-[#772015] text-sm rounded">
            <strong><?php _e('Noe gikk galt:', 'bimverdi'); ?></strong>
            <?php echo esc_html($error_messages[$error] ?? $error); ?>
        </div>
    <?php endif; ?>

    <?php if (!$priser): ?>
        <div class="mt-8 py-8 border-b border-[#D6D1C6]">
            <p class="text-[#772015]">
                <?php _e('Vi kunne ikke hente prisene akkurat nå. Vennligst prøv igjen senere eller gi tilbakemelding på', 'bimverdi'); ?>
                <a href="https://bimverdi.no/tilbakemelding/" class="underline">bimverdi.no/tilbakemelding</a>.
            </p>
        </div>
    <?php else: ?>

        <form method="post" action="" id="bv-oppgrader-form" class="mt-2">
            <input type="hidden" name="bimverdi_oppgrader_action" value="submit">
            <?php wp_nonce_field('bimverdi_oppgrader', '_bv_oppgrader_nonce'); ?>
            <input type="text" name="hp_navn" value="" style="position:absolute;left:-9999px;" tabindex="-1" autocomplete="off" aria-hidden="true">

            <!-- 1. Nivå-valg -->
            <section class="py-8 border-b border-[#D6D1C6]">
                <h2 class="text-lg font-medium text-[#1A1A1A] mb-2"><?php _e('1. Velg nivå', 'bimverdi'); ?></h2>
                <p class="text-sm text-[#5A5A5A] mb-6">
                    <?php _e('Alle tre nivåer gir lik tilgang i portalen. Se hva hvert nivå inneholder og fullstendige betingelser på', 'bimverdi'); ?>
                    <a href="https://bimverdi.no/betingelser/" target="_blank" rel="noopener" class="text-[#FF8B5E] underline"><?php _e('bimverdi.no/betingelser', 'bimverdi'); ?></a>.
                </p>

                <div class="space-y-3">
                    <?php
                    $nivaa_labels = [
                        'deltaker'         => __('Deltaker', 'bimverdi'),
                        'prosjektdeltaker' => __('Prosjektdeltaker', 'bimverdi'),
                        'partner'          => __('Partner', 'bimverdi'),
                    ];
                    foreach ($nivaa_labels as $nivaa_key => $nivaa_label):
                        $aarspris = (int) ($priser[$nivaa_key] ?? 0);
                    ?>
                        <label class="flex items-baseline gap-4 py-3 cursor-pointer hover:bg-[#FAFAF8] px-3 -mx-3 rounded transition-colors">
                            <input type="radio" name="nivaa" value="<?php echo esc_attr($nivaa_key); ?>"
                                   <?php checked('deltaker', $nivaa_key); ?>
                                   class="text-[#FF8B5E] focus:ring-[#FF8B5E]">
                            <span class="text-base font-medium text-[#1A1A1A] flex-1"><?php echo esc_html($nivaa_label); ?></span>
                            <span class="text-sm text-[#5A5A5A]">
                                <?php echo esc_html(number_format($aarspris, 0, ',', ' ')); ?> kr / år
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <p class="mt-6 text-xs text-[#5A5A5A] leading-relaxed">
                    <?php _e('Årsavgiften beregnes kvartalsvis fra det kvartalet du melder inn ditt foretak. Rabatt for oppstartbedrifter, utdanningsinstitusjoner og foretak med omsetning lavere enn 5 MNOK — ta kontakt på', 'bimverdi'); ?>
                    <a href="https://bimverdi.no/tilbakemelding/" target="_blank" rel="noopener" class="text-[#FF8B5E] underline">bimverdi.no/tilbakemelding</a>.
                </p>
            </section>

            <!-- 2. Faktureringsdetaljer -->
            <section class="py-8 border-b border-[#D6D1C6]">
                <h2 class="text-lg font-medium text-[#1A1A1A] mb-2"><?php _e('2. Faktureringsdetaljer', 'bimverdi'); ?></h2>
                <p class="text-sm text-[#5A5A5A] mb-6">
                    <?php _e('Fyll inn EHF-organisasjonsnummer hvis dere mottar EHF-faktura. Hvis ikke, skriv inn e-postadressen fakturaen skal sendes til. Minst ett av feltene må fylles ut.', 'bimverdi'); ?>
                </p>

                <div class="space-y-5 max-w-md">
                    <div>
                        <label class="block text-sm text-[#1A1A1A] mb-1" for="bv-po"><?php _e('Vår referanse / PO-nummer', 'bimverdi'); ?></label>
                        <input type="text" id="bv-po" name="po_nummer" class="w-full border border-[#D6D1C6] rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm text-[#1A1A1A] mb-1" for="bv-avd"><?php _e('Avdeling / kostnadssted', 'bimverdi'); ?></label>
                        <input type="text" id="bv-avd" name="avdeling" class="w-full border border-[#D6D1C6] rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm text-[#1A1A1A] mb-1" for="bv-ehf">
                            <?php _e('EHF-organisasjonsnummer', 'bimverdi'); ?>
                        </label>
                        <input type="text" id="bv-ehf" name="ehf_orgnr" inputmode="numeric" pattern="[0-9 ]*" maxlength="20"
                               class="w-full border border-[#D6D1C6] rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent"
                               placeholder="<?php echo esc_attr__('Eks. 999 999 999', 'bimverdi'); ?>">
                        <p class="mt-1 text-xs text-[#5A5A5A]">
                            <?php _e('Fyll ut hvis dere ønsker EHF-faktura.', 'bimverdi'); ?>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm text-[#1A1A1A] mb-1" for="bv-fakt-epost">
                            <?php _e('Fakturamottakers e-post', 'bimverdi'); ?>
                        </label>
                        <input type="email" id="bv-fakt-epost" name="faktura_epost" value="<?php echo esc_attr($default_faktura_email); ?>"
                               class="w-full border border-[#D6D1C6] rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                        <p class="mt-1 text-xs text-[#5A5A5A]">
                            <?php _e('Påkrevd hvis EHF ikke brukes.', 'bimverdi'); ?>
                        </p>
                    </div>

                    <div>
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="bruk_egen_fakturaadresse" value="1" id="bv-bruk-egen-adresse" class="mt-1 text-[#FF8B5E] focus:ring-[#FF8B5E]">
                            <span class="text-sm text-[#1A1A1A]">
                                <?php _e('Bruk annen fakturaadresse enn den fra Brønnøysundregisteret', 'bimverdi'); ?>
                            </span>
                        </label>
                        <div id="bv-egen-adresse-felt" class="mt-3 hidden">
                            <label class="block text-sm text-[#1A1A1A] mb-1" for="bv-egen-adresse"><?php _e('Fakturaadresse', 'bimverdi'); ?></label>
                            <textarea id="bv-egen-adresse" name="egen_fakturaadresse" rows="3" class="w-full border border-[#D6D1C6] rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent" placeholder="<?php echo esc_attr__('Gate, postnummer og sted', 'bimverdi'); ?>"></textarea>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. Bekreft Hovedkontakt-rolle og vilkår -->
            <section class="py-8 border-b border-[#D6D1C6]">
                <h2 class="text-lg font-medium text-[#1A1A1A] mb-2"><?php _e('3. Bekreft og fullfør', 'bimverdi'); ?></h2>

                <p class="text-sm text-[#5A5A5A] mb-6">
                    <?php
                    printf(
                        esc_html__('Du blir hovedkontakt for %s etter oppgraderingen. Andre gratisbrukere i samme foretak blir tilleggskontakter.', 'bimverdi'),
                        '<strong>' . esc_html($foretak_navn) . '</strong>'
                    );
                    ?>
                </p>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="vilkar_godtatt" value="1" required id="bv-vilkar" class="mt-1 text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]">
                        <?php _e('Jeg godtar', 'bimverdi'); ?>
                        <a href="https://bimverdi.no/betingelser/" target="_blank" rel="noopener" class="text-[#FF8B5E] underline"><?php _e('medlemsbetingelsene', 'bimverdi'); ?></a>
                        <?php _e('og bekrefter at jeg har fullmakt til å oppgradere foretaket.', 'bimverdi'); ?>
                    </span>
                </label>
            </section>

            <!-- Knapper -->
            <div class="flex items-center gap-4 mt-8">
                <a href="<?php echo esc_url(bimverdi_minside_url('')); ?>" class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A] underline">
                    <?php _e('Avbryt', 'bimverdi'); ?>
                </a>
                <?php bimverdi_button([
                    'text'    => __('Bekreft oppgradering', 'bimverdi'),
                    'type'    => 'submit',
                    'variant' => 'primary',
                    'size'    => 'medium',
                ]); ?>
            </div>
        </form>

    <?php endif; ?>
</div>

<script>
(function() {
    // Vis/skjul egen fakturaadresse-felt.
    const form = document.getElementById('bv-oppgrader-form');
    if (!form) return;

    const egenAdresseToggle = document.getElementById('bv-bruk-egen-adresse');
    const egenAdresseFelt = document.getElementById('bv-egen-adresse-felt');
    if (egenAdresseToggle && egenAdresseFelt) {
        egenAdresseToggle.addEventListener('change', function() {
            egenAdresseFelt.classList.toggle('hidden', !this.checked);
        });
    }

    // Klient-side hint: marker felter som "påkrevd" når den andre er tom.
    // Server-side validering er sannhetskilden — dette gir bare bedre UX.
    const ehfInput   = document.getElementById('bv-ehf');
    const epostInput = document.getElementById('bv-fakt-epost');
    if (ehfInput && epostInput) {
        function syncFakturaKanal() {
            const ehfHasValue   = ehfInput.value.trim() !== '';
            const epostHasValue = epostInput.value.trim() !== '';
            // Hvis EHF er fylt ut: e-post er ikke påkrevd. Ellers krev e-post.
            if (ehfHasValue) {
                epostInput.removeAttribute('required');
            } else if (!epostHasValue) {
                epostInput.setAttribute('required', '');
            }
        }
        ehfInput.addEventListener('input', syncFakturaKanal);
        epostInput.addEventListener('input', syncFakturaKanal);
        syncFakturaKanal();
    }
})();
</script>
