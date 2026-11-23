<?php
/**
 * Knapp «Send lukket melding» (krav 31 v3, runde B)
 *
 * Usage:
 *   get_template_part('parts/components/lukket-melding-knapp', null, [
 *       'mottaker_id' => 123,
 *       'tekst'       => 'Send lukket melding', // valgfri
 *   ]);
 *
 * Tilstand avgjøres av bv_meldinger_knapp_tilstand() i
 * mu-plugins/bimverdi-meldinger.php: skjult, logg inn, blokk (krav 22) eller skriv.
 * Før lansering vises knappen bare for administratorer.
 *
 * Egen, avgrenset CSS (ingen nye Tailwind-klasser).
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bv_meldinger_knapp_tilstand')) {
    return;
}

$args        = $args ?? [];
$mottaker_id = isset($args['mottaker_id']) ? (int) $args['mottaker_id'] : 0;
$tekst       = isset($args['tekst']) && $args['tekst'] !== '' ? (string) $args['tekst'] : __('Send lukket melding', 'bimverdi');

// «Gjelder»: siden knappen står på følger med, så mottakeren ser hva det gjelder.
$gjelder_id = isset($args['gjelder_id']) ? (int) $args['gjelder_id'] : (is_singular() ? (int) get_queried_object_id() : 0);

$knapp = bv_meldinger_knapp_tilstand(get_current_user_id(), $mottaker_id, $gjelder_id);
if ($knapp['tilstand'] === 'skjult') {
    return;
}
if ($knapp['tilstand'] === 'logg_inn') {
    $tekst = __('Logg inn for å sende lukket melding', 'bimverdi');
}
?>
<a class="bv-lukket-knapp" href="<?php echo esc_url($knapp['url']); ?>">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    <span><?php echo esc_html($tekst); ?></span>
</a>
<?php if (!defined('BV_LUKKET_KNAPP_CSS')): define('BV_LUKKET_KNAPP_CSS', true); // CSS bare én gang per side ?>
<style>
.bv-lukket-knapp { display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; padding: 6px 12px; border: 1px solid #D6D1C6; border-radius: 8px; font-size: 13px; font-weight: 500; line-height: 1.3; color: #1A1A1A; text-decoration: none; background: #FFFFFF; }
.bv-lukket-knapp:hover { border-color: #1A1A1A; }
.bv-lukket-knapp:focus-visible { outline: 2px solid #FF8B5E; outline-offset: 2px; }
</style>
<?php endif; ?>
