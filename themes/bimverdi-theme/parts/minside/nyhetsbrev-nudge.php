<?php
/**
 * Nyhetsbrev-nudge på Min side-dashboardet
 *
 * Vises for innloggede brukere som ikke er påmeldt nyhetsbrevet.
 * Visningslogikk og POST-handler: mu-plugins/bimverdi-nyhetsbrev-nudge.php
 * Design: UI Contract Variant B — rolig seksjon med hairline-divider, ingen boks.
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bimverdi_skal_vise_nyhetsbrev_nudge') || !bimverdi_skal_vise_nyhetsbrev_nudge()) {
    return;
}
?>
<div class="mb-6 pb-6 border-b border-[#E7E5E4]">
    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex items-start gap-3 flex-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#F97316] flex-shrink-0 mt-0.5"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            <div>
                <p class="text-sm font-medium text-[#111827]">Du er ikke påmeldt nyhetsbrevet</p>
                <p class="text-sm text-[#57534E]">Motta nyheter og invitasjoner til arrangementer fra BIM Verdi.</p>
            </div>
        </div>
        <form method="post" class="flex items-center gap-3 flex-shrink-0">
            <?php wp_nonce_field('bimverdi_nb_nudge'); ?>
            <button type="submit" name="bimverdi_nb_nudge" value="pamelding" class="bv-btn bv-btn--primary bv-btn--small">
                Meld meg på
            </button>
            <button type="submit" name="bimverdi_nb_nudge" value="lukk" class="text-sm text-[#57534E] hover:text-[#111827] transition-colors underline-offset-2 hover:underline">
                Nei takk
            </button>
        </form>
    </div>
</div>
