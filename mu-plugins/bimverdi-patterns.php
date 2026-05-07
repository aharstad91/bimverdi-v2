<?php
/**
 * Reusable Patterns — felles infrastruktur for gjenbrukbare elementer.
 *
 * Bruker WordPress' innebygde Synced Patterns (wp_block CPT). Bård
 * redigerer en pattern én gang i Gutenberg, og PHP-templates rendrer
 * samme markup hvor som helst via:
 *
 *     echo bimverdi_render_pattern('pricing-tabell');
 *
 * Patterns finnes via slug (post_name), så de er stabile referanser
 * selv om navnet endres. Gir advarsel i admin hvis slug ikke finnes,
 * stille fallback (tom string) på frontend.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rendre en Synced Pattern som HTML.
 *
 * @param string $slug    post_name til wp_block (f.eks. 'pricing-tabell').
 * @param array  $args {
 *     @type bool $cache    Bruk objekt-cache. Default true.
 *     @type bool $required Vis admin_notice hvis ikke funnet. Default false.
 * }
 * @return string Rendret HTML, eller tom string hvis pattern mangler.
 */
function bimverdi_render_pattern(string $slug, array $args = []): string {
    $args = wp_parse_args($args, [
        'cache'    => true,
        'required' => false,
    ]);

    $cache_key = 'bv_pattern_' . md5($slug);

    if ($args['cache']) {
        $cached = wp_cache_get($cache_key, 'bimverdi_patterns');
        if ($cached !== false) {
            return (string) $cached;
        }
    }

    $post = bimverdi_get_pattern_by_slug($slug);

    if (!$post) {
        if ($args['required'] && is_admin() && current_user_can('edit_posts')) {
            add_action('admin_notices', function () use ($slug) {
                printf(
                    '<div class="notice notice-warning"><p>%s</p></div>',
                    sprintf(
                        esc_html__('Synced Pattern «%s» mangler. Opprett den under Patterns → Synced Patterns.', 'bimverdi'),
                        esc_html($slug)
                    )
                );
            });
        }
        return '';
    }

    $content = (string) $post->post_content;
    $rendered = (string) do_blocks($content);

    if ($args['cache']) {
        wp_cache_set($cache_key, $rendered, 'bimverdi_patterns', HOUR_IN_SECONDS);
    }

    return $rendered;
}

/**
 * Hent en wp_block post by slug.
 *
 * @param string $slug
 * @return WP_Post|null
 */
function bimverdi_get_pattern_by_slug(string $slug): ?WP_Post {
    $posts = get_posts([
        'name'             => $slug,
        'post_type'        => 'wp_block',
        'post_status'      => ['publish', 'private'],
        'numberposts'      => 1,
        'suppress_filters' => false,
    ]);

    return $posts ? $posts[0] : null;
}

/**
 * Invalidate pattern-cache når wp_block oppdateres.
 */
add_action('save_post_wp_block', 'bimverdi_invalidate_pattern_cache', 10, 1);

function bimverdi_invalidate_pattern_cache(int $post_id): void {
    $slug = get_post_field('post_name', $post_id);
    if ($slug) {
        wp_cache_delete('bv_pattern_' . md5($slug), 'bimverdi_patterns');
    }
}
