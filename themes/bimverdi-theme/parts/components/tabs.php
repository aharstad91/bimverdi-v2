<?php
/**
 * BIMVerdi Tabs Component (shadcn-inspired)
 *
 * Tabbed interface with two variants: default (pill container) and line (underline).
 *
 * Usage:
 *
 * <?php bimverdi_tabs([
 *     'id'      => 'account-tabs',
 *     'variant' => 'line',
 *     'default' => 'overview',
 *     'tabs'    => [
 *         'overview'  => 'Overview',
 *         'analytics' => 'Analytics',
 *         'reports'   => 'Reports',
 *     ],
 * ]); ?>
 *
 * <?php bimverdi_tab_panel('account-tabs', 'overview'); ?>
 *   <p>Overview content here.</p>
 * <?php bimverdi_tab_panel_end(); ?>
 *
 * <?php bimverdi_tab_panel('account-tabs', 'analytics'); ?>
 *   <p>Analytics content here.</p>
 * <?php bimverdi_tab_panel_end(); ?>
 *
 * <?php bimverdi_tabs_end(); ?>
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render the tab list (triggers) and open the tabs wrapper
 *
 * @param array $args Tabs configuration
 *   - id (string) Unique tabs ID — used to link triggers to panels
 *   - tabs (array) key => label pairs for each tab
 *   - default (string) Key of the default active tab (defaults to first)
 *   - variant (string) 'default' | 'line'
 *   - class (string) Additional CSS classes on wrapper
 * @return void
 */
function bimverdi_tabs($args = []) {
    $defaults = [
        'id'      => '',
        'tabs'    => [],
        'default' => '',
        'variant' => 'default',
        'class'   => '',
    ];

    $args = wp_parse_args($args, $defaults);

    if (empty($args['tabs'])) return;

    $default_tab = $args['default'] ?: array_key_first($args['tabs']);
    $variant = $args['variant'];

    $wrapper_class = 'bv-tabs';
    if ($args['class']) $wrapper_class .= ' ' . $args['class'];

    $list_class = 'bv-tabs__list';
    if ($variant === 'line') $list_class .= ' bv-tabs__list--line';

    ?>
    <div class="<?php echo esc_attr($wrapper_class); ?>" data-bv-tabs="<?php echo esc_attr($args['id']); ?>">
        <div class="<?php echo esc_attr($list_class); ?>" role="tablist">
            <?php foreach ($args['tabs'] as $key => $label):
                $is_active = ($key === $default_tab);
                $trigger_class = 'bv-tabs__trigger';
                if ($is_active) $trigger_class .= ' bv-tabs__trigger--active';
            ?>
                <button
                    type="button"
                    role="tab"
                    class="<?php echo esc_attr($trigger_class); ?>"
                    data-bv-tab="<?php echo esc_attr($key); ?>"
                    data-bv-tabs-id="<?php echo esc_attr($args['id']); ?>"
                    aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                    aria-controls="<?php echo esc_attr($args['id'] . '-' . $key); ?>"
                ><?php echo esc_html($label); ?></button>
            <?php endforeach; ?>
        </div>
    <?php
}

/**
 * Open a tab panel
 *
 * @param string $tabs_id The parent tabs ID
 * @param string $panel_id This panel's key
 * @return void
 */
function bimverdi_tab_panel($tabs_id, $panel_id) {
    // The first panel rendered is shown by default; JS handles the rest
    ?>
    <div
        class="bv-tabs__panel"
        id="<?php echo esc_attr($tabs_id . '-' . $panel_id); ?>"
        role="tabpanel"
        data-bv-tab-panel="<?php echo esc_attr($panel_id); ?>"
        data-bv-tabs-id="<?php echo esc_attr($tabs_id); ?>"
    >
    <?php
}

/**
 * Close a tab panel
 */
function bimverdi_tab_panel_end() {
    echo '</div>';
}

/**
 * Close the tabs wrapper
 */
function bimverdi_tabs_end() {
    echo '</div>';
}
