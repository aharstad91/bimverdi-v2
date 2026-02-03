<?php
/**
 * BIMVerdi Filter Bar Component
 *
 * Compact horizontal filter bar with search input and multi-select dropdowns.
 * Replaces the tall checkbox-based filter sections on archive pages.
 *
 * Design specs (from plan):
 * - Height: ~56px (down from ~350px)
 * - Layout: Search | Dropdowns | Results count | Reset
 * - Mobile: Collapses to search + "Filtrer" button with bottom sheet
 *
 * Usage:
 *
 * <?php bimverdi_filter_bar([
 *     'form_id' => 'verktoy-filter-form',
 *     'search_name' => 's',
 *     'search_value' => $search,
 *     'search_placeholder' => 'Sok etter verktoy...',
 *     'dropdowns' => [
 *         [
 *             'name' => 'formaalstema[]',
 *             'label' => 'Temagruppe',
 *             'options' => $temagruppe_options,
 *             'selected' => $formaalstema,
 *             'filter_class' => 'filter-formaal',
 *         ],
 *     ],
 *     'result_count' => 36,
 *     'total_count' => 36,
 *     'result_label' => 'verktoy',
 * ]); ?>
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

// Include the multiselect dropdown component
require_once get_template_directory() . '/parts/components/multiselect-dropdown.php';

/**
 * Render a filter bar component
 *
 * @param array $args Filter bar configuration
 *   - form_id (string) Form element ID
 *   - search_name (string) Search input name attribute
 *   - search_value (string) Current search value
 *   - search_placeholder (string) Search input placeholder text
 *   - dropdowns (array) Array of dropdown configs for bimverdi_multiselect_dropdown()
 *   - result_count (int) Number of visible results
 *   - total_count (int) Total number of items
 *   - result_label (string) Label for results (e.g., 'verktoy')
 *   - show_reset (bool) Show reset button (default true)
 *   - reset_id (string) Reset button ID (default 'reset-filters')
 * @return void
 */
function bimverdi_filter_bar($args = []) {
    $defaults = [
        'form_id'            => 'filter-form',
        'search_name'        => 's',
        'search_value'       => '',
        'search_placeholder' => 'Sok...',
        'dropdowns'          => [],
        'result_count'       => 0,
        'total_count'        => 0,
        'result_label'       => 'resultater',
        'show_reset'         => true,
        'reset_id'           => 'reset-filters',
    ];

    $args = wp_parse_args($args, $defaults);

    // Count total active filters
    $active_filter_count = 0;
    foreach ($args['dropdowns'] as $dropdown) {
        $active_filter_count += count($dropdown['selected'] ?? []);
    }
    ?>

    <!-- Filter Bar -->
    <div class="bv-filter-bar bg-white rounded-lg border border-[#E5E0D8] mb-8">
        <form method="GET" id="<?php echo esc_attr($args['form_id']); ?>">

            <!-- Desktop Layout -->
            <div class="hidden md:flex items-center gap-4 p-4">

                <!-- Search Input -->
                <div class="relative flex-1 max-w-xs">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#5A5A5A]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input
                        type="text"
                        name="<?php echo esc_attr($args['search_name']); ?>"
                        id="<?php echo esc_attr($args['form_id']); ?>-search"
                        value="<?php echo esc_attr($args['search_value']); ?>"
                        placeholder="<?php echo esc_attr($args['search_placeholder']); ?>"
                        class="w-full pl-10 pr-4 py-2.5 text-sm border border-[#E5E0D8] rounded-lg focus:ring-2 focus:ring-[#1A1A1A] focus:border-transparent text-[#1A1A1A] placeholder-[#9A9A9A]"
                    >
                </div>

                <!-- Dropdowns -->
                <div class="flex items-center gap-2">
                    <?php foreach ($args['dropdowns'] as $dropdown): ?>
                        <?php bimverdi_multiselect_dropdown($dropdown); ?>
                    <?php endforeach; ?>
                </div>

                <!-- Spacer -->
                <div class="flex-1"></div>

                <!-- Results + Reset Group -->
                <div class="flex items-center gap-3">
                    <p class="text-sm text-[#5A5A5A] whitespace-nowrap">
                        Viser <span id="visible-count" class="font-medium text-[#1A1A1A]"><?php echo intval($args['result_count']); ?></span>
                        <?php if ($args['total_count'] > 0): ?>
                        av <?php echo intval($args['total_count']); ?>
                        <?php endif; ?>
                        <?php echo esc_html($args['result_label']); ?>
                    </p>
                    <?php if ($args['show_reset']): ?>
                    <span class="text-[#D6D1C6]">|</span>
                    <button
                        type="button"
                        id="<?php echo esc_attr($args['reset_id']); ?>"
                        class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors whitespace-nowrap"
                    >
                        Nullstill
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mobile Layout -->
            <div class="md:hidden p-4 space-y-3">

                <!-- Search Input -->
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#5A5A5A]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input
                        type="text"
                        name="<?php echo esc_attr($args['search_name']); ?>"
                        value="<?php echo esc_attr($args['search_value']); ?>"
                        placeholder="<?php echo esc_attr($args['search_placeholder']); ?>"
                        class="w-full pl-10 pr-4 py-3 text-sm border border-[#E5E0D8] rounded-lg focus:ring-2 focus:ring-[#1A1A1A] focus:border-transparent text-[#1A1A1A] placeholder-[#9A9A9A]"
                    >
                </div>

                <!-- Filter Button + Results -->
                <div class="flex items-center justify-between">
                    <button
                        type="button"
                        class="bv-filter-bar__mobile-trigger inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium bg-white border border-[#D6D1C6] rounded-lg"
                        aria-expanded="false"
                        aria-controls="mobile-filter-sheet"
                    >
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                        </svg>
                        <span>Filtrer</span>
                        <span class="bv-filter-bar__mobile-count inline-flex items-center justify-center min-w-[22px] h-5 px-2 text-xs font-medium bg-[#1A1A1A] text-white rounded-full transition-opacity <?php echo $active_filter_count > 0 ? 'opacity-100' : 'opacity-0'; ?>" data-mobile-count><?php echo intval($active_filter_count); ?></span>
                    </button>

                    <p class="text-sm text-[#5A5A5A]">
                        <span id="visible-count-mobile" class="font-medium text-[#1A1A1A]"><?php echo intval($args['result_count']); ?></span>
                        <?php echo esc_html($args['result_label']); ?>
                    </p>
                </div>
            </div>

            <!-- Mobile Bottom Sheet -->
            <div id="mobile-filter-sheet" class="bv-filter-sheet fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
                <!-- Backdrop -->
                <div class="bv-filter-sheet__backdrop fixed inset-0 bg-black/50"></div>

                <!-- Sheet -->
                <div class="bv-filter-sheet__panel fixed inset-x-0 bottom-0 bg-white rounded-t-2xl max-h-[85vh] flex flex-col">
                    <!-- Handle -->
                    <div class="flex justify-center py-3 flex-shrink-0">
                        <div class="w-12 h-1 bg-[#D6D1C6] rounded-full"></div>
                    </div>

                    <!-- Header -->
                    <div class="flex items-center justify-between px-4 pb-3 border-b border-[#D6D1C6] flex-shrink-0">
                        <h2 class="text-lg font-semibold text-[#1A1A1A]">Filtrer</h2>
                        <button type="button" class="bv-filter-sheet__close p-2 -mr-2 text-[#5A5A5A] hover:text-[#1A1A1A]" aria-label="Lukk">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M18 6 6 18M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Filter Content (scrollable) -->
                    <div class="overflow-y-auto flex-1 px-4 py-4">
                        <?php foreach ($args['dropdowns'] as $dropdown): ?>
                        <fieldset class="mb-6">
                            <legend class="text-sm font-medium text-[#1A1A1A] mb-3"><?php echo esc_html($dropdown['label']); ?></legend>
                            <div class="space-y-1">
                                <?php foreach ($dropdown['options'] as $value => $label):
                                    $is_checked = in_array($value, $dropdown['selected'] ?? []);
                                    $mobile_id = 'mobile-' . sanitize_title($dropdown['name']) . '-' . sanitize_title($value);
                                    $checkbox_classes = 'filter-checkbox w-5 h-5 rounded border-[#D6D1C6] text-[#1A1A1A] focus:ring-[#1A1A1A]';
                                    if (!empty($dropdown['filter_class'])) {
                                        $checkbox_classes .= ' ' . $dropdown['filter_class'];
                                    }
                                    $count = isset($dropdown['counts'][$value]) ? $dropdown['counts'][$value] : null;
                                ?>
                                <label class="flex items-center gap-3 px-3 py-3 rounded-lg cursor-pointer hover:bg-[#F7F5EF] <?php echo $is_checked ? 'bg-[#F7F5EF]' : ''; ?>">
                                    <input
                                        type="checkbox"
                                        id="<?php echo esc_attr($mobile_id); ?>"
                                        name="<?php echo esc_attr($dropdown['name']); ?>"
                                        value="<?php echo esc_attr($value); ?>"
                                        class="<?php echo esc_attr($checkbox_classes); ?>"
                                        data-syncs-with="<?php echo esc_attr($dropdown['name']); ?>"
                                        <?php checked($is_checked); ?>
                                    >
                                    <span class="flex-1 text-sm text-[#1A1A1A]"><?php echo esc_html($label); ?></span>
                                    <?php if ($count !== null): ?>
                                    <span class="text-xs text-[#9A9A9A]"><?php echo intval($count); ?></span>
                                    <?php endif; ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                        <?php endforeach; ?>
                    </div>

                    <!-- Footer -->
                    <div class="flex gap-3 p-4 border-t border-[#D6D1C6] flex-shrink-0">
                        <button
                            type="button"
                            class="bv-filter-sheet__reset flex-1 px-4 py-3 text-sm font-medium text-[#5A5A5A] border border-[#D6D1C6] rounded-lg"
                        >
                            Nullstill
                        </button>
                        <button
                            type="button"
                            class="bv-filter-sheet__apply flex-1 px-4 py-3 text-sm font-medium text-white bg-[#1A1A1A] rounded-lg"
                        >
                            Vis <span class="bv-filter-sheet__result-count"><?php echo intval($args['result_count']); ?></span> <?php echo esc_html($args['result_label']); ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php
    // Output the multiselect dropdown script
    bimverdi_multiselect_dropdown_script();

    // Output the filter bar script
    bimverdi_filter_bar_script($args);
}

/**
 * Output the JavaScript for the filter bar
 */
function bimverdi_filter_bar_script($args) {
    static $script_output = false;
    if ($script_output) return;
    $script_output = true;
    ?>
    <script>
    (function() {
        var sheet = document.getElementById('mobile-filter-sheet');
        var mobileTrigger = document.querySelector('.bv-filter-bar__mobile-trigger');
        var closeBtn = sheet ? sheet.querySelector('.bv-filter-sheet__close') : null;
        var backdrop = sheet ? sheet.querySelector('.bv-filter-sheet__backdrop') : null;
        var applyBtn = sheet ? sheet.querySelector('.bv-filter-sheet__apply') : null;
        var resetSheetBtn = sheet ? sheet.querySelector('.bv-filter-sheet__reset') : null;

        function openSheet() {
            if (sheet) {
                sheet.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeSheet() {
            if (sheet) {
                sheet.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        if (mobileTrigger) {
            mobileTrigger.addEventListener('click', openSheet);
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closeSheet);
        }

        if (backdrop) {
            backdrop.addEventListener('click', closeSheet);
        }

        if (applyBtn) {
            applyBtn.addEventListener('click', closeSheet);
        }

        // Sync mobile checkboxes with desktop
        if (sheet) {
            sheet.querySelectorAll('input[type="checkbox"][data-syncs-with]').forEach(function(mobileCb) {
                mobileCb.addEventListener('change', function() {
                    var name = this.getAttribute('data-syncs-with');
                    var value = this.value;
                    // Find matching desktop checkbox
                    var desktopCb = document.querySelector('[data-multiselect] input[name="' + name + '"][value="' + value + '"]');
                    if (desktopCb && desktopCb.checked !== this.checked) {
                        desktopCb.checked = this.checked;
                        desktopCb.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            });
        }

        // Sync desktop with mobile
        document.querySelectorAll('[data-multiselect] input[type="checkbox"]').forEach(function(desktopCb) {
            desktopCb.addEventListener('change', function() {
                if (!sheet) return;
                var name = this.name;
                var value = this.value;
                var mobileCb = sheet.querySelector('input[data-syncs-with="' + name + '"][value="' + value + '"]');
                if (mobileCb && mobileCb.checked !== this.checked) {
                    mobileCb.checked = this.checked;
                }
                updateMobileFilterCount();
            });
        });

        // Update mobile filter count badge
        function updateMobileFilterCount() {
            var count = document.querySelectorAll('[data-multiselect] input[type="checkbox"]:checked').length;
            var badge = document.querySelector('[data-mobile-count]');
            if (badge) {
                badge.textContent = count;
                if (count > 0) {
                    badge.classList.remove('opacity-0');
                    badge.classList.add('opacity-100');
                } else {
                    badge.classList.remove('opacity-100');
                    badge.classList.add('opacity-0');
                }
            }
        }

        // Reset from sheet
        if (resetSheetBtn) {
            resetSheetBtn.addEventListener('click', function() {
                // Trigger the main reset
                var mainReset = document.getElementById('<?php echo esc_js($args['reset_id']); ?>');
                if (mainReset) {
                    mainReset.click();
                }
                closeSheet();
            });
        }
    })();
    </script>
    <?php
}
