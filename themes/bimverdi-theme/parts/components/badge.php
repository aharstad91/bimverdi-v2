<?php
/**
 * BIMVerdi Badge Component
 *
 * Renders inline badge/pill elements for status indicators,
 * categories, temagruppe labels, and type tags.
 *
 * Variants:
 * - status: General status indicator (default)
 * - category: Subtle style with border for taxonomy/category labels
 * - temagruppe: Theme group label
 * - type: Content type indicator
 *
 * Colors:
 * green, yellow, red, gray, blue, orange, purple, teal, amber
 *
 * Usage:
 *
 * // Simple status badge
 * <?php bimverdi_badge([
 *     'text'    => 'Aktiv',
 *     'color'   => 'green',
 * ]); ?>
 *
 * // Category badge with icon
 * <?php bimverdi_badge([
 *     'text'    => 'ByggesaksBIM',
 *     'variant' => 'category',
 *     'icon'    => 'building-2',
 * ]); ?>
 *
 * // Medium size badge
 * <?php bimverdi_badge([
 *     'text'    => 'Partner',
 *     'variant' => 'type',
 *     'color'   => 'purple',
 *     'size'    => 'medium',
 * ]); ?>
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render a badge component
 *
 * @param array $args Badge configuration
 *   - text (string) Badge label text
 *   - variant (string) 'status' | 'category' | 'temagruppe' | 'type' - default 'status'
 *   - color (string) 'green' | 'yellow' | 'red' | 'gray' | 'blue' | 'orange' | 'purple' | 'teal' | 'amber' - default 'gray'
 *   - size (string) 'small' | 'medium' - default 'small'
 *   - icon (string) Lucide icon name (optional)
 *   - class (string) Additional CSS classes
 * @return void
 */
function bimverdi_badge($args = []) {
    $defaults = [
        'text'    => '',
        'variant' => 'status',    // status, category, temagruppe, type
        'color'   => 'gray',      // green, yellow, red, gray, blue, orange, purple, teal, amber
        'size'    => 'small',     // small, medium
        'icon'    => null,         // Optional Lucide icon name
        'class'   => '',
    ];

    $args = wp_parse_args($args, $defaults);

    // Build CSS classes
    $classes = ['bv-badge'];
    $classes[] = 'bv-badge--' . $args['variant'];
    $classes[] = 'bv-badge--' . $args['color'];
    $classes[] = 'bv-badge--' . $args['size'];

    if ($args['class']) {
        $classes[] = $args['class'];
    }

    $class_string = implode(' ', $classes);

    // Icon SVG
    $icon_html = '';
    if ($args['icon']) {
        $icon_size = $args['size'] === 'medium' ? 14 : 12;
        $icon_html = '<span class="bv-badge__icon">' . bimverdi_get_icon_svg($args['icon'], $icon_size) . '</span>';
    }

    // Render
    echo '<span class="' . esc_attr($class_string) . '">'
        . $icon_html
        . esc_html($args['text'])
        . '</span>';
}
