<?php
/**
 * BIM Verdi - Taxonomy Helper Functions
 *
 * Provides cached, loop-safe functions for converting taxonomy slugs
 * to readable names. Uses batch-loading to avoid N+1 queries.
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get readable taxonomy term name from slug.
 *
 * Uses batch-cache: first call for a taxonomy loads ALL terms in one query.
 * Subsequent calls are pure cache hits. Safe to use in loops.
 *
 * @param string $slug     Term slug (e.g. 'arkitekt_radgiver')
 * @param string $taxonomy Taxonomy name (e.g. 'bransjekategori')
 * @param string $fallback Fallback if term not found (defaults to humanized slug)
 * @return string Readable term name
 */
function bimverdi_get_readable_term(string $slug, string $taxonomy, string $fallback = ''): string {
    if (empty($slug) || empty($taxonomy)) {
        return $fallback;
    }

    $cache_group = 'bv_terms_by_slug';
    $cache_key   = $taxonomy . '|' . $slug;

    $cached = wp_cache_get($cache_key, $cache_group);
    if (false !== $cached) {
        return $cached !== '__not_found__' ? $cached : ($fallback ?: ucfirst(str_replace('_', ' ', $slug)));
    }

    // First miss: load ALL terms for this taxonomy in one query
    bimverdi_prime_taxonomy_slug_cache($taxonomy);

    $cached = wp_cache_get($cache_key, $cache_group);
    if (false !== $cached && $cached !== '__not_found__') {
        return $cached;
    }

    wp_cache_set($cache_key, '__not_found__', $cache_group, HOUR_IN_SECONDS);
    return $fallback ?: ucfirst(str_replace('_', ' ', $slug));
}

/**
 * Batch-load all terms for a taxonomy into the object cache.
 *
 * @param string $taxonomy Taxonomy name
 */
function bimverdi_prime_taxonomy_slug_cache(string $taxonomy): void {
    $primed_key = 'primed_' . $taxonomy;
    if (wp_cache_get($primed_key, 'bv_taxonomy_primed')) {
        return;
    }

    $terms = get_terms([
        'taxonomy'                => $taxonomy,
        'hide_empty'              => false,
        'update_term_meta_cache'  => false,
    ]);

    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            wp_cache_set(
                $taxonomy . '|' . $term->slug,
                $term->name,
                'bv_terms_by_slug',
                HOUR_IN_SECONDS
            );
        }
    }

    wp_cache_set($primed_key, true, 'bv_taxonomy_primed', MINUTE_IN_SECONDS);
}

/**
 * Get first taxonomy term name for a post.
 *
 * Uses get_the_terms() which reads from WP_Query's pre-loaded term cache
 * (0 extra queries in a standard loop).
 *
 * @param int    $post_id  Post ID
 * @param string $taxonomy Taxonomy name
 * @param string $fallback Fallback value
 * @return string Term name or fallback
 */
function bimverdi_get_first_term_name(int $post_id, string $taxonomy, string $fallback = ''): string {
    $terms = get_the_terms($post_id, $taxonomy);
    return ($terms && !is_wp_error($terms)) ? $terms[0]->name : $fallback;
}
