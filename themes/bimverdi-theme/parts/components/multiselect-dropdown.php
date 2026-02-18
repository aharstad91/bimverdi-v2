<?php
/**
 * BIMVerdi Multi-Select Dropdown Component
 *
 * Compact dropdown with checkboxes for multi-value filtering.
 * Accessibility-first design based on Adrian Roselli's recommendations.
 *
 * Design specs:
 * - Trigger: 40px height, border-radius 8px
 * - Dropdown panel: max-height 256px with scroll
 * - Badge shows count of selected items
 * - Closes on outside click
 *
 * Usage:
 *
 * <?php bimverdi_multiselect_dropdown([
 *     'name' => 'temagruppe[]',
 *     'label' => 'Temagruppe',
 *     'options' => [
 *         'ByggesaksBIM' => 'ByggesaksBIM',
 *         'ProsjektBIM' => 'ProsjektBIM',
 *     ],
 *     'selected' => ['ByggesaksBIM'],
 * ]); ?>
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render a multi-select dropdown component
 *
 * @param array $args Dropdown configuration
 *   - name (string) Form field name (e.g., 'temagruppe[]')
 *   - label (string) Display label for the trigger button
 *   - options (array) Associative array of value => label
 *   - selected (array) Array of currently selected values
 *   - id (string) Optional unique ID (auto-generated if not provided)
 *   - class (string) Additional CSS classes for the container
 *   - filter_class (string) CSS class for checkboxes (used by JS filtering)
 * @return void
 */
function bimverdi_multiselect_dropdown($args = []) {
    $defaults = [
        'name'         => '',
        'label'        => 'Velg',
        'options'      => [],
        'selected'     => [],
        'counts'       => [],  // Optional: array of value => count for each option
        'id'           => '',
        'class'        => '',
        'filter_class' => '',
    ];

    $args = wp_parse_args($args, $defaults);

    // Generate unique ID if not provided
    $id = $args['id'] ?: 'bv-multiselect-' . wp_unique_id();
    $dropdown_id = $id . '-dropdown';

    // Count selected
    $selected_count = count($args['selected']);
    $has_selection = $selected_count > 0;

    // Build container classes
    $container_classes = 'bv-multiselect relative';
    if ($args['class']) {
        $container_classes .= ' ' . $args['class'];
    }
    ?>
    <div class="<?php echo esc_attr($container_classes); ?>" data-multiselect>
        <!-- Trigger Button -->
        <button
            type="button"
            class="bv-multiselect__trigger flex items-center gap-2 px-3 py-2 text-sm border border-[#E7E5E4] rounded-lg bg-white hover:border-[#A8A29E] transition-colors cursor-pointer whitespace-nowrap"
            aria-haspopup="listbox"
            aria-expanded="false"
            aria-controls="<?php echo esc_attr($dropdown_id); ?>"
            id="<?php echo esc_attr($id); ?>-trigger"
        >
            <span class="bv-multiselect__label text-[#111827]"><?php echo esc_html($args['label']); ?></span>
            <span
                class="bv-multiselect__count inline-flex items-center justify-center min-w-[22px] h-5 px-2 text-xs font-medium bg-[#111827] text-white rounded-full transition-opacity <?php echo $has_selection ? 'opacity-100' : 'opacity-0'; ?>"
                data-count
                aria-hidden="<?php echo $has_selection ? 'false' : 'true'; ?>"
            ><?php echo intval($selected_count); ?></span>
            <svg class="bv-multiselect__chevron w-4 h-4 text-[#57534E] transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m6 9 6 6 6-6"/>
            </svg>
        </button>

        <!-- Dropdown Panel -->
        <div
            id="<?php echo esc_attr($dropdown_id); ?>"
            class="bv-multiselect__dropdown hidden absolute z-30 mt-1 w-56 bg-white border border-[#E7E5E4] rounded-lg shadow-sm max-h-64 overflow-y-auto"
            role="listbox"
            aria-multiselectable="true"
            aria-labelledby="<?php echo esc_attr($id); ?>-trigger"
        >
            <fieldset class="p-2">
                <legend class="sr-only"><?php echo esc_html($args['label']); ?></legend>

                <?php foreach ($args['options'] as $value => $label):
                    $is_checked = in_array($value, $args['selected']);
                    $checkbox_id = $id . '-' . sanitize_title($value);
                    $checkbox_classes = 'filter-checkbox w-4 h-4 rounded border-[#E7E5E4] text-[#111827] focus:ring-[#111827] focus:ring-offset-0';
                    if ($args['filter_class']) {
                        $checkbox_classes .= ' ' . $args['filter_class'];
                    }
                    $count = isset($args['counts'][$value]) ? $args['counts'][$value] : null;
                ?>
                <label
                    class="bv-multiselect__option flex items-center gap-3 px-3 py-2 rounded cursor-pointer hover:bg-[#F5F5F4] <?php echo $is_checked ? 'bg-[#F5F5F4]' : ''; ?>"
                    for="<?php echo esc_attr($checkbox_id); ?>"
                >
                    <input
                        type="checkbox"
                        id="<?php echo esc_attr($checkbox_id); ?>"
                        name="<?php echo esc_attr($args['name']); ?>"
                        value="<?php echo esc_attr($value); ?>"
                        class="<?php echo esc_attr($checkbox_classes); ?>"
                        <?php checked($is_checked); ?>
                    >
                    <span class="flex-1 text-sm text-[#111827]"><?php echo esc_html($label); ?></span>
                    <?php if ($count !== null): ?>
                    <span class="text-xs text-[#A8A29E]"><?php echo intval($count); ?></span>
                    <?php endif; ?>
                </label>
                <?php endforeach; ?>
            </fieldset>
        </div>
    </div>
    <?php
}

/**
 * Output the JavaScript for multiselect dropdowns
 * Call this once at the end of the page/form
 */
function bimverdi_multiselect_dropdown_script() {
    static $script_output = false;
    if ($script_output) return;
    $script_output = true;
    ?>
    <script>
    (function() {
        // Toggle dropdown on trigger click
        document.querySelectorAll('[data-multiselect] .bv-multiselect__trigger').forEach(function(trigger) {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                var container = this.closest('[data-multiselect]');
                var dropdown = container.querySelector('.bv-multiselect__dropdown');
                var isOpen = !dropdown.classList.contains('hidden');

                // Close all other dropdowns first
                document.querySelectorAll('[data-multiselect] .bv-multiselect__dropdown').forEach(function(d) {
                    d.classList.add('hidden');
                    var t = d.previousElementSibling;
                    if (t) {
                        t.setAttribute('aria-expanded', 'false');
                        t.querySelector('.bv-multiselect__chevron').style.transform = '';
                    }
                });

                // Toggle this dropdown
                if (!isOpen) {
                    dropdown.classList.remove('hidden');
                    this.setAttribute('aria-expanded', 'true');
                    this.querySelector('.bv-multiselect__chevron').style.transform = 'rotate(180deg)';
                }
            });
        });

        // Update count badge when checkbox changes
        document.querySelectorAll('[data-multiselect] input[type="checkbox"]').forEach(function(cb) {
            cb.addEventListener('change', function() {
                var container = this.closest('[data-multiselect]');
                var countBadge = container.querySelector('[data-count]');
                var checked = container.querySelectorAll('input[type="checkbox"]:checked').length;

                countBadge.textContent = checked;
                if (checked > 0) {
                    countBadge.classList.remove('opacity-0');
                    countBadge.classList.add('opacity-100');
                    countBadge.setAttribute('aria-hidden', 'false');
                } else {
                    countBadge.classList.remove('opacity-100');
                    countBadge.classList.add('opacity-0');
                    countBadge.setAttribute('aria-hidden', 'true');
                }
            });
        });

        // Close dropdown on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[data-multiselect]')) {
                document.querySelectorAll('[data-multiselect] .bv-multiselect__dropdown').forEach(function(d) {
                    d.classList.add('hidden');
                    var t = d.previousElementSibling;
                    if (t) {
                        t.setAttribute('aria-expanded', 'false');
                        t.querySelector('.bv-multiselect__chevron').style.transform = '';
                    }
                });
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('[data-multiselect] .bv-multiselect__dropdown').forEach(function(d) {
                    d.classList.add('hidden');
                    var t = d.previousElementSibling;
                    if (t) {
                        t.setAttribute('aria-expanded', 'false');
                        t.querySelector('.bv-multiselect__chevron').style.transform = '';
                    }
                });
            }
        });
    })();
    </script>
    <?php
}
