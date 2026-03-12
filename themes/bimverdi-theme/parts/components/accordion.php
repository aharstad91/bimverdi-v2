<?php
/**
 * BIMVerdi Accordion Component (shadcn-inspired)
 *
 * Vertically stacked set of collapsible content sections.
 * Uses native <details>/<summary> — no JS needed.
 *
 * Usage:
 *
 * // Single accordion (only one open at a time — needs JS)
 * <?php bimverdi_accordion([
 *     'type'  => 'single',
 *     'items' => [
 *         ['title' => 'Question 1', 'content' => 'Answer 1', 'open' => true],
 *         ['title' => 'Question 2', 'content' => 'Answer 2'],
 *     ],
 * ]); ?>
 *
 * // Multiple (any number open)
 * <?php bimverdi_accordion([
 *     'type'  => 'multiple',
 *     'items' => [
 *         ['title' => 'Section A', 'content' => 'Content A'],
 *         ['title' => 'Section B', 'content' => 'Content B'],
 *     ],
 * ]); ?>
 *
 * // Bordered variant
 * <?php bimverdi_accordion([
 *     'variant' => 'bordered',
 *     'items'   => [...],
 * ]); ?>
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render an accordion component
 *
 * @param array $args Accordion configuration
 *   - items (array) Array of accordion items, each with:
 *       - title (string) Trigger text
 *       - content (string) Panel content (HTML allowed)
 *       - open (bool) Whether this item starts open
 *       - disabled (bool) Whether this item is disabled
 *   - type (string) 'single' (only one open) | 'multiple' (any open). Default 'single'.
 *   - variant (string) 'default' (bottom border) | 'bordered' (full border). Default 'default'.
 *   - class (string) Additional CSS classes
 * @return void
 */
function bimverdi_accordion($args = []) {
    $defaults = [
        'items'   => [],
        'type'    => 'single',
        'variant' => 'default',
        'class'   => '',
    ];

    $args = wp_parse_args($args, $defaults);

    if (empty($args['items'])) return;

    $wrapper_class = 'bv-accordion';
    if ($args['variant'] === 'bordered') $wrapper_class .= ' bv-accordion--bordered';
    if ($args['class']) $wrapper_class .= ' ' . $args['class'];

    $type = $args['type'] === 'multiple' ? 'multiple' : 'single';
    $accordion_id = 'bv-acc-' . wp_unique_id();

    ?>
    <div class="<?php echo esc_attr($wrapper_class); ?>" data-bv-accordion="<?php echo esc_attr($type); ?>" id="<?php echo esc_attr($accordion_id); ?>">
        <?php foreach ($args['items'] as $i => $item):
            $item = wp_parse_args($item, [
                'title'    => '',
                'content'  => '',
                'open'     => false,
                'disabled' => false,
            ]);

            $disabled_class = $item['disabled'] ? ' bv-accordion__item--disabled' : '';
        ?>
            <details class="bv-accordion__item<?php echo $disabled_class; ?>"<?php echo $item['open'] ? ' open' : ''; ?>>
                <summary class="bv-accordion__trigger"<?php echo $item['disabled'] ? ' tabindex="-1"' : ''; ?>>
                    <span class="bv-accordion__trigger-text"><?php echo esc_html($item['title']); ?></span>
                    <svg class="bv-accordion__chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </summary>
                <div class="bv-accordion__content">
                    <div class="bv-accordion__content-inner">
                        <?php echo wp_kses_post($item['content']); ?>
                    </div>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
    <?php
}
