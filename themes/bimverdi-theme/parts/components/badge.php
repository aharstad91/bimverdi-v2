<?php
/**
 * BIMVerdi Badge Component (shadcn-inspired)
 *
 * Inline badge/pill for status indicators, categories, and labels.
 *
 * Variants:
 * - default: Solid dark background (primary action)
 * - secondary: Subtle gray background
 * - destructive: Red/error styling
 * - outline: Border only, no fill
 *
 * Usage:
 *
 * <?php bimverdi_badge(['text' => 'Badge']); ?>
 * <?php bimverdi_badge(['text' => 'Secondary', 'variant' => 'secondary']); ?>
 * <?php bimverdi_badge(['text' => 'Destructive', 'variant' => 'destructive']); ?>
 * <?php bimverdi_badge(['text' => 'Outline', 'variant' => 'outline']); ?>
 *
 * // With semantic color (works with any variant)
 * <?php bimverdi_badge(['text' => 'Aktiv', 'color' => 'green']); ?>
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render a badge component
 *
 * @param array $args Badge configuration
 *   - text (string) Badge label text
 *   - variant (string) 'default' | 'secondary' | 'destructive' | 'outline'
 *   - color (string) Semantic color override: 'green' | 'yellow' | 'red' | 'gray' | 'blue' | 'orange' | 'purple' | 'teal' | 'amber'
 *   - icon (string) Lucide icon name (optional)
 *   - class (string) Additional CSS classes
 * @return void
 */
function bimverdi_badge($args = []) {
    $defaults = [
        'text'    => '',
        'variant' => 'default',
        'color'   => '',
        'icon'    => null,
        'class'   => '',
    ];

    $args = wp_parse_args($args, $defaults);

    // Legacy variant aliases
    $variant_map = [
        'status'     => 'secondary',
        'category'   => 'outline',
        'temagruppe' => 'secondary',
        'type'       => 'secondary',
    ];
    if (isset($variant_map[$args['variant']])) {
        $args['variant'] = $variant_map[$args['variant']];
    }

    // Legacy size param — ignored (single size now), but don't break calls
    // Legacy 'small'/'medium' sizes are accepted but not applied

    // Build CSS classes
    $classes = ['bv-badge'];
    $classes[] = 'bv-badge--' . $args['variant'];

    // Semantic color overrides variant styling
    if ($args['color']) {
        $classes[] = 'bv-badge--' . $args['color'];
    }

    if ($args['class']) {
        $classes[] = $args['class'];
    }

    $class_string = implode(' ', $classes);

    // Icon SVG
    $icon_html = '';
    if ($args['icon']) {
        $icon_html = '<span class="bv-badge__icon">' . bimverdi_get_icon_svg($args['icon'], 12) . '</span>';
    }

    echo '<span class="' . esc_attr($class_string) . '">'
        . $icon_html
        . esc_html($args['text'])
        . '</span>';
}
