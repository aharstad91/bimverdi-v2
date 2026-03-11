<?php
/**
 * BIMVerdi Stat Pill Component
 *
 * Displays a number + label stacked vertically, used for
 * dashboard stats and summary metrics.
 *
 * Usage:
 *
 * <?php bimverdi_stat_pill([
 *     'number' => 12,
 *     'label'  => 'Verktøy',
 *     'color'  => 'orange',
 * ]); ?>
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render a stat pill component
 *
 * @param array $args Stat pill configuration
 *   - number (int|string) The number to display
 *   - label  (string)     Descriptive label below the number
 *   - color  (string)     'black' | 'orange' - default 'black'
 *   - class  (string)     Additional CSS classes
 * @return void
 */
function bimverdi_stat_pill($args = []) {
    $defaults = [
        'number' => 0,
        'label'  => '',
        'color'  => 'black',
        'class'  => '',
    ];

    $args = wp_parse_args($args, $defaults);

    // Build CSS classes
    $classes = ['bv-stat-pill'];
    $classes[] = 'bv-stat-pill--' . $args['color'];

    if ($args['class']) {
        $classes[] = $args['class'];
    }

    $class_string = implode(' ', $classes);

    echo '<div class="' . esc_attr($class_string) . '">';
    echo '<span class="bv-stat-pill__number">' . esc_html($args['number']) . '</span>';
    echo '<span class="bv-stat-pill__label">' . esc_html($args['label']) . '</span>';
    echo '</div>';
}
