<?php
/**
 * BIMVerdi Pagination Component (shadcn-inspired)
 *
 * Renders page navigation with previous/next and numbered links.
 *
 * Usage:
 *
 * // Auto from WP query
 * <?php bimverdi_pagination(); ?>
 *
 * // Custom
 * <?php bimverdi_pagination([
 *     'current' => 2,
 *     'total'   => 10,
 *     'base'    => '/deltakere/page/%#%/',
 * ]); ?>
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render a pagination component
 *
 * @param array $args Pagination configuration
 *   - current (int) Current page number (default: from WP query)
 *   - total (int) Total pages (default: from WP query)
 *   - base (string) URL pattern with %#% placeholder
 *   - prev_text (string) Previous button text
 *   - next_text (string) Next button text
 *   - show_all (bool) Show all page numbers
 *   - mid_size (int) Pages to show on each side of current (default 1)
 *   - class (string) Additional CSS classes
 * @return void
 */
function bimverdi_pagination($args = []) {
    global $wp_query;

    $defaults = [
        'current'   => max(1, get_query_var('paged', 1)),
        'total'     => $wp_query->max_num_pages ?? 1,
        'base'      => '',
        'prev_text' => 'Previous',
        'next_text' => 'Next',
        'show_all'  => false,
        'mid_size'  => 1,
        'class'     => '',
    ];

    $args = wp_parse_args($args, $defaults);

    if ($args['total'] <= 1) return;

    $current = (int) $args['current'];
    $total = (int) $args['total'];

    // Generate page links via WordPress
    $paginate_args = [
        'current'   => $current,
        'total'     => $total,
        'show_all'  => $args['show_all'],
        'mid_size'  => $args['mid_size'],
        'prev_next' => false,
        'type'      => 'array',
    ];

    if ($args['base']) {
        $paginate_args['base'] = $args['base'];
    }

    $links = paginate_links($paginate_args);

    if (!$links) return;

    $wrapper_class = 'bv-pagination';
    if ($args['class']) $wrapper_class .= ' ' . $args['class'];

    $prev_url = ($current > 1) ? get_pagenum_link($current - 1) : '';
    $next_url = ($current < $total) ? get_pagenum_link($current + 1) : '';

    ?>
    <nav class="<?php echo esc_attr($wrapper_class); ?>" role="navigation" aria-label="Pagination">
        <ul class="bv-pagination__list">
            <!-- Previous -->
            <li>
                <?php if ($prev_url): ?>
                    <a href="<?php echo esc_url($prev_url); ?>" class="bv-pagination__link bv-pagination__prev">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        <span><?php echo esc_html($args['prev_text']); ?></span>
                    </a>
                <?php else: ?>
                    <span class="bv-pagination__link bv-pagination__prev bv-pagination__link--disabled" aria-disabled="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        <span><?php echo esc_html($args['prev_text']); ?></span>
                    </span>
                <?php endif; ?>
            </li>

            <!-- Page numbers -->
            <?php foreach ($links as $link): ?>
                <li>
                    <?php
                    // WordPress returns HTML strings — we re-wrap them with our classes
                    if (strpos($link, 'current') !== false) {
                        // Active page
                        preg_match('/>(.*?)</', $link, $m);
                        $num = $m[1] ?? '';
                        echo '<span class="bv-pagination__link bv-pagination__link--active" aria-current="page">' . esc_html($num) . '</span>';
                    } elseif (strpos($link, 'dots') !== false) {
                        // Ellipsis
                        echo '<span class="bv-pagination__ellipsis" aria-hidden="true">&hellip;</span>';
                    } else {
                        // Normal page link
                        preg_match('/href=["\']([^"\']*)["\']/', $link, $href_m);
                        preg_match('/>(.*?)</', $link, $text_m);
                        $href = $href_m[1] ?? '#';
                        $num = $text_m[1] ?? '';
                        echo '<a href="' . esc_url($href) . '" class="bv-pagination__link">' . esc_html($num) . '</a>';
                    }
                    ?>
                </li>
            <?php endforeach; ?>

            <!-- Next -->
            <li>
                <?php if ($next_url): ?>
                    <a href="<?php echo esc_url($next_url); ?>" class="bv-pagination__link bv-pagination__next">
                        <span><?php echo esc_html($args['next_text']); ?></span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                <?php else: ?>
                    <span class="bv-pagination__link bv-pagination__next bv-pagination__link--disabled" aria-disabled="true">
                        <span><?php echo esc_html($args['next_text']); ?></span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                <?php endif; ?>
            </li>
        </ul>
    </nav>
    <?php
}
