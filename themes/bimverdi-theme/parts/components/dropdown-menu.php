<?php
/**
 * BIMVerdi Dropdown Menu Component (shadcn-inspired)
 *
 * A trigger button (⋯) that opens a dropdown with action items.
 *
 * Usage:
 *
 * <?php bimverdi_dropdown_menu([
 *     'items' => [
 *         ['icon' => 'eye', 'text' => 'Se', 'href' => '/url/'],
 *         ['icon' => 'pencil', 'text' => 'Rediger', 'href' => '/url/'],
 *         ['separator' => true],
 *         ['icon' => 'trash-2', 'text' => 'Slett', 'href' => '/url/', 'variant' => 'danger'],
 *     ],
 * ]); ?>
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render a dropdown menu component
 *
 * @param array $args Dropdown configuration
 *   - items (array) Menu items, each with:
 *       - icon (string) Lucide icon name
 *       - text (string) Label
 *       - href (string) URL
 *       - target (string) Link target (_blank etc.)
 *       - variant (string) 'default' | 'danger'
 *       - separator (bool) Renders a divider line
 *   - trigger_icon (string) Lucide icon for trigger (default: 'more-horizontal')
 *   - align (string) 'right' | 'left' (default: 'right')
 *   - class (string) Additional CSS classes
 * @return void
 */
function bimverdi_dropdown_menu($args = []) {
    $defaults = [
        'items'        => [],
        'trigger_icon' => 'more-horizontal',
        'align'        => 'right',
        'class'        => '',
    ];

    $args = wp_parse_args($args, $defaults);

    if (empty($args['items'])) return;

    $wrapper_class = 'bv-dropdown';
    if ($args['class']) $wrapper_class .= ' ' . $args['class'];
    $align_class = $args['align'] === 'left' ? 'bv-dropdown__menu--left' : '';
    ?>
    <div class="<?php echo esc_attr($wrapper_class); ?>">
        <button type="button" class="bv-dropdown__trigger bv-btn bv-btn--ghost bv-btn--sm" aria-haspopup="true" aria-expanded="false">
            <?php echo bimverdi_icon($args['trigger_icon'], 16); ?>
        </button>
        <div class="bv-dropdown__menu <?php echo esc_attr($align_class); ?>" role="menu">
            <?php foreach ($args['items'] as $item):
                if (!empty($item['separator'])): ?>
                    <div class="bv-dropdown__separator"></div>
                <?php continue; endif;

                $variant_class = ($item['variant'] ?? '') === 'danger' ? ' bv-dropdown__item--danger' : '';
                $target = !empty($item['target']) ? ' target="' . esc_attr($item['target']) . '" rel="noopener"' : '';
            ?>
                <a href="<?php echo esc_url($item['href'] ?? '#'); ?>" class="bv-dropdown__item<?php echo $variant_class; ?>" role="menuitem"<?php echo $target; ?>>
                    <?php if (!empty($item['icon'])): ?>
                        <?php echo bimverdi_icon($item['icon'], 14); ?>
                    <?php endif; ?>
                    <?php echo esc_html($item['text'] ?? ''); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
