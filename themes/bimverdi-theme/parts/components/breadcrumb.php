<?php
/**
 * BIMVerdi Breadcrumb Component (shadcn-inspired)
 *
 * Usage:
 *
 * // Simple: Verktøy > Solibri
 * <?php bimverdi_breadcrumb([
 *     ['label' => 'Verktøy', 'href' => '/verktoy/'],
 *     ['label' => get_the_title()],
 * ]); ?>
 *
 * // With home: Hjem > Deltakere > Rambøll
 * <?php bimverdi_breadcrumb([
 *     ['label' => 'Hjem', 'href' => '/'],
 *     ['label' => 'Deltakere', 'href' => '/foretak/'],
 *     ['label' => 'Rambøll'],
 * ]); ?>
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render a breadcrumb navigation
 *
 * @param array $items Array of breadcrumb items, each with:
 *   - label (string) Display text
 *   - href (string|null) URL — omit for current page (last item)
 * @param array $args Optional config:
 *   - class (string) Additional CSS classes on <nav>
 *   - separator (string) 'chevron' (default) or 'slash'
 * @return void
 */
function bimverdi_breadcrumb($items = [], $args = []) {
    if (empty($items)) return;

    $defaults = [
        'class'     => '',
        'separator' => 'chevron',
    ];
    $args = wp_parse_args($args, $defaults);

    // Separator SVG
    if ($args['separator'] === 'slash') {
        $sep_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 2 9 22"/></svg>';
    } else {
        $sep_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>';
    }

    $nav_class = 'bv-breadcrumb';
    if ($args['class']) {
        $nav_class .= ' ' . $args['class'];
    }

    $last_index = count($items) - 1;
    ?>
    <nav class="<?php echo esc_attr($nav_class); ?>" aria-label="Brødsmulesti">
        <ol class="bv-breadcrumb__list">
            <?php foreach ($items as $i => $item):
                $is_last = ($i === $last_index);
                $has_link = !empty($item['href']) && !$is_last;
            ?>
                <?php if ($i > 0): ?>
                    <li class="bv-breadcrumb__separator" role="presentation"><?php echo $sep_svg; ?></li>
                <?php endif; ?>

                <?php if ($is_last): ?>
                    <li class="bv-breadcrumb__page" aria-current="page"><?php echo esc_html($item['label']); ?></li>
                <?php elseif ($has_link): ?>
                    <li class="bv-breadcrumb__item">
                        <a href="<?php echo esc_url($item['href']); ?>" class="bv-breadcrumb__link"><?php echo esc_html($item['label']); ?></a>
                    </li>
                <?php else: ?>
                    <li class="bv-breadcrumb__item"><?php echo esc_html($item['label']); ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
}
