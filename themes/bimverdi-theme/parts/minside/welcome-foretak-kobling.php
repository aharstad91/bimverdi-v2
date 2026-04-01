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
        <h2 class="text-lg font-medium text-[#1A1A1A] mb-1">
            <?php _e('Koble til ditt foretak', 'bimverdi'); ?>
        </h2>
        <p class="text-sm text-[#5A5A5A] mb-4">
            <?php _e('Søk etter din arbeidsgiver for å koble profilen din til et foretak.', 'bimverdi'); ?>
        </p>

        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </div>
            <input type="text"
                   data-search-input
                   class="w-full pl-10 pr-4 py-3 text-sm border border-[#D6D1C6] rounded-lg bg-white placeholder-[#888888] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent"
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

    <?php if ($show_welcome_banner) : ?>
    <!-- Hopp over -->
    <div class="mt-6 pt-6 border-t border-[#E7E5E4]">
        <a href="<?php echo esc_url(home_url('/min-side/')); ?>"
           class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors inline-flex items-center gap-1">
            <?php _e('Hopp over — jeg gjør dette senere', 'bimverdi'); ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
    </div>
    <?php endif; ?>
</div>
