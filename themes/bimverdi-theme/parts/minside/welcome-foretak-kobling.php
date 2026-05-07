<?php
/**
 * BV20: Welcome Foretak-kobling Widget
 *
 * Reusable widget for linking a user to a company.
 * Used in two contexts:
 *   - 'welcome' (dashboard ?welcome=1): includes welcome banner + hopp over link
 *   - 'foretak' (foretak-detail): standalone widget without banner
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$context = $args['context'] ?? 'welcome';
$show_welcome_banner = ($context === 'welcome');
$user = wp_get_current_user();
$first_name = $user->first_name ?: $user->display_name;
?>

<div id="foretak-kobling-widget"
     class="mb-8"
     data-rest-url="<?php echo esc_attr(rest_url('bimverdi/v1/brreg/')); ?>"
     data-rest-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>"
     data-ajax-url="<?php echo esc_attr(admin_url('admin-ajax.php')); ?>"
     data-auto-join-nonce="<?php echo esc_attr(wp_create_nonce('bimverdi_auto_join')); ?>"
     data-bruker-foretak-nonce="<?php echo esc_attr(wp_create_nonce('bimverdi_foretak_kobling')); ?>"
     data-home-url="<?php echo esc_attr(home_url('/')); ?>">

    <!-- Search Section -->
    <div class="py-6">
        <h2 class="text-2xl font-medium text-[#1A1A1A] mb-2">
            <?php _e('Koble til ditt foretak', 'bimverdi'); ?>
        </h2>
        <p class="text-base text-[#5A5A5A] mb-5">
            <?php _e('Søk etter din arbeidsgiver for å koble profilen din til et foretak. Dette er nødvendig for registrering til arrangement, sjekk om din arbeidsgiver er registrert som deltaker etc.', 'bimverdi'); ?>
        </p>

        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 block">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </div>
            <input type="search"
                   data-search-input
                   class="w-full !pl-12 !pr-4 !py-4 !h-auto !text-base border border-[#D6D1C6] !rounded-lg bg-white placeholder-[#888888] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent"
                   placeholder="<?php esc_attr_e('Søk etter foretaksnavn eller org.nr...', 'bimverdi'); ?>"
                   autocomplete="off" />
        </div>
    </div>

    <!-- Search Results (populated by JS) -->
    <div data-results
         class="border border-[#E7E5E4] rounded-lg overflow-hidden empty:border-0 max-h-[300px] overflow-y-auto"
         aria-live="polite">
    </div>

    <!-- Selected Company Detail (populated by JS) -->
    <div data-detail class="mt-4" aria-live="polite">
    </div>

    <!-- Sekundær CTA: Finner du ikke arbeidsgiver? -->
    <p class="mt-4 text-sm text-[#5A5A5A]">
        <?php _e('Finner du ikke arbeidsgiver?', 'bimverdi'); ?>
        <a href="<?php echo esc_url(home_url('/min-side/foretak/registrer/')); ?>"
           class="text-[#FF8B5E] hover:text-[#E87341] font-medium underline-offset-2 hover:underline">
            <?php _e('Registrer som nytt foretak', 'bimverdi'); ?>
        </a>
    </p>

    <!-- Motivasjon: Deltakeravgift og -nivå (pricing-pattern) -->
    <div class="mt-10 pt-8 border-t border-[#E7E5E4]">
        <h3 class="text-xs font-semibold text-[#5A5A5A] uppercase tracking-wider mb-2">
            <?php _e('Deltakeravgift og -nivå', 'bimverdi'); ?>
        </h3>
        <p class="text-xs text-[#5A5A5A] mb-4">
            <?php _e('Sammenlign nivåene under og velg det som passer for deg eller arbeidsgiveren din.', 'bimverdi'); ?>
        </p>
        <?php
        if (function_exists('bimverdi_render_pattern')) {
            echo bimverdi_render_pattern('pricing-tabell');
        }
        ?>
    </div>

</div>
