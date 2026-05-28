<?php
/**
 * Min Side — Bekreftelses-side etter selvbetjent oppgradering (krav 24-v4)
 *
 * Route: /min-side/oppgrader/fullfort/
 *
 * Endret 2026-05-28: pris- og kvartal-tall fjernet fra bekreftelsen (per
 * møte med Bård). Admin sender faktura manuelt etterpå. Query-param er
 * nå bare ?nivaa=deltaker.
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id    = get_current_user_id();
$foretak_id = bimverdi_resolve_user_foretak_id($user_id);
$foretak_navn = $foretak_id ? get_the_title($foretak_id) : '';

$nivaa = isset($_GET['nivaa']) ? sanitize_text_field($_GET['nivaa']) : '';

$nivaa_labels = [
    'deltaker'         => __('Deltaker', 'bimverdi'),
    'prosjektdeltaker' => __('Prosjektdeltaker', 'bimverdi'),
    'partner'          => __('Partner', 'bimverdi'),
];
$nivaa_label = $nivaa_labels[$nivaa] ?? '';

if (!$nivaa_label || !$foretak_id) {
    // Brukeren havnet her uten korrekt state — redirect til dashboard.
    wp_safe_redirect(bimverdi_minside_url(''));
    exit;
}
?>

<div class="max-w-[800px] mx-auto">
    <div class="text-center pt-12 pb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[#B3DB87]/30 mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#1A1A1A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <h1 class="text-3xl font-light text-[#1A1A1A] mb-3">
            <?php _e('Gratulerer!', 'bimverdi'); ?>
        </h1>
        <p class="text-lg text-[#1A1A1A]">
            <?php
            printf(
                /* translators: 1: foretaksnavn, 2: nivå */
                esc_html__('%1$s er nå %2$s i BIM Verdi.', 'bimverdi'),
                '<strong>' . esc_html($foretak_navn) . '</strong>',
                '<strong>' . esc_html($nivaa_label) . '</strong>'
            );
            ?>
        </p>
    </div>

    <div class="py-8 border-t border-b border-[#D6D1C6] space-y-4 text-[#1A1A1A]">
        <p>
            <?php _e('Du er nå hovedkontakt for foretaket. Alle gratisbrukere i ditt tidligere gratisforetak er automatisk lagt til som tilleggskontakter og kan nå bruke alle medlemsfunksjonene.', 'bimverdi'); ?>
        </p>

        <p>
            <?php _e('BIM Verdi-administrasjonen oppretter faktura manuelt og sender den til foretaket basert på fakturadetaljene du fylte ut.', 'bimverdi'); ?>
        </p>

        <p class="text-sm text-[#5A5A5A]">
            <?php _e('Se medlemsbetingelser:', 'bimverdi'); ?>
            <a href="https://bimverdi.no/betingelser/" target="_blank" rel="noopener" class="text-[#FF8B5E] underline">bimverdi.no/betingelser</a>.
            <?php _e('Spørsmål? Bruk', 'bimverdi'); ?>
            <a href="https://bimverdi.no/tilbakemelding/" target="_blank" rel="noopener" class="text-[#FF8B5E] underline">bimverdi.no/tilbakemelding</a>.
        </p>
    </div>

    <div class="flex items-center gap-4 mt-8 justify-center">
        <?php bimverdi_button([
            'text'    => __('Gå til foretaksprofilen', 'bimverdi'),
            'href'    => bimverdi_minside_url('foretak'),
            'variant' => 'primary',
            'size'    => 'medium',
        ]); ?>
        <a href="<?php echo esc_url(bimverdi_minside_url('')); ?>" class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A] underline">
            <?php _e('Gå til dashboard', 'bimverdi'); ?>
        </a>
    </div>
</div>
