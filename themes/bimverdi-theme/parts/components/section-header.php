<?php
/**
 * BIMVerdi Section Header Component
 *
 * Renders a section header with optional eyebrow, heading, and subtitle.
 * Follows the same component pattern as button.php.
 *
 * Usage:
 *
 * <?php bimverdi_section_header([
 *     'eyebrow'  => 'Verktoy',
 *     'heading'  => 'Registrerte verktoy',
 *     'subtitle' => 'Se alle verktoy registrert av medlemmer.',
 * ]); ?>
 *
 * // Centered with h3
 * <?php bimverdi_section_header([
 *     'heading' => 'Om BIM Verdi',
 *     'align'   => 'center',
 *     'tag'     => 'h3',
 * ]); ?>
 *
 * // With CTA action button on the right
 * <?php bimverdi_section_header([
 *     'heading'  => 'Utforsk arrangementer',
 *     'subtitle' => 'Se alle kommende møter og workshops',
 *     'action'   => ['text' => 'Se alle', 'href' => '/arrangementer/', 'icon' => 'arrow-right'],
 * ]); ?>
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render a section header component
 *
 * @param array $args Section header configuration
 *   - eyebrow  (string) Small label above heading (optional)
 *   - heading  (string) Main heading text
 *   - subtitle (string) Subtitle below heading (optional)
 *   - align    (string) 'left' | 'center' - default 'left'
 *   - tag      (string) 'h1' | 'h2' | 'h3' | 'h4' - default 'h2'
 *   - action   (array)  CTA button on the right: ['text', 'href', 'variant', 'icon', 'icon_position']
 *   - class    (string) Additional CSS classes
 * @return void
 */
function bimverdi_section_header($args = []) {
    $defaults = [
        'eyebrow'  => '',
        'heading'  => '',
        'subtitle' => '',
        'align'    => 'left',
        'tag'      => 'h2',
        'action'   => [],
        'class'    => '',
    ];

    $args = wp_parse_args($args, $defaults);

    // Validate tag
    $allowed_tags = ['h1', 'h2', 'h3', 'h4'];
    $tag = in_array($args['tag'], $allowed_tags, true) ? $args['tag'] : 'h2';

    // Build CSS classes
    $classes = ['bv-section-header'];
    if ($args['align'] === 'center') {
        $classes[] = 'bv-section-header--center';
    }
    if ($args['class']) {
        $classes[] = $args['class'];
    }
    $class_string = implode(' ', $classes);

    $has_action = !empty($args['action']);

    if ($has_action) {
        echo '<div class="bv-section-header__row ' . esc_attr($args['class']) . '">';
        echo '<div class="' . esc_attr(implode(' ', array_diff($classes, [$args['class']]))) . '">';
    } else {
        echo '<div class="' . esc_attr($class_string) . '">';
    }

    if ($args['eyebrow']) {
        echo '<span class="bv-section-header__eyebrow">' . esc_html($args['eyebrow']) . '</span>';
    }

    if ($args['heading']) {
        echo '<' . $tag . ' class="bv-section-header__heading">' . esc_html($args['heading']) . '</' . $tag . '>';
    }

    if ($args['subtitle']) {
        echo '<p class="bv-section-header__subtitle">' . esc_html($args['subtitle']) . '</p>';
    }

    echo '</div>';

    if ($has_action) {
        $action = $args['action'];
        bimverdi_button([
            'text'          => $action['text'] ?? '',
            'href'          => $action['href'] ?? '#',
            'variant'       => $action['variant'] ?? 'primary',
            'icon'          => $action['icon'] ?? null,
            'icon_position' => $action['icon_position'] ?? 'right',
            'size'          => $action['size'] ?? 'medium',
        ]);
        echo '</div>';
    }
}
