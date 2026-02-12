<?php
/**
 * Admin Helper Functions
 *
 * Utility functions for admin/editor users on the frontend.
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Returns a small admin badge with post ID and wp-admin edit link.
 * Only visible for users with manage_options capability.
 *
 * @param int|null $post_id Post ID (defaults to current post)
 * @return string HTML badge or empty string
 */
function bimverdi_admin_id_badge($post_id = null) {
    if (!current_user_can('manage_options')) {
        return '';
    }

    if (!$post_id) {
        $post_id = get_the_ID();
    }

    if (!$post_id) {
        return '';
    }

    $edit_url = get_edit_post_link($post_id);

    return sprintf(
        '<a href="%s" class="bv-admin-badge" title="Rediger i wp-admin" target="_blank">#%d</a>',
        esc_url($edit_url),
        (int) $post_id
    );
}

/**
 * Outputs inline styles for admin badges (called once).
 */
function bimverdi_admin_badge_styles() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <style>
        .bv-admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            font-size: 11px;
            font-weight: 500;
            font-family: ui-monospace, monospace;
            color: #7C3AED;
            background: #EDE9FE;
            border: 1px solid #C4B5FD;
            padding: 1px 6px;
            border-radius: 4px;
            text-decoration: none;
            vertical-align: middle;
            line-height: 1.4;
            margin-left: 6px;
            white-space: nowrap;
        }
        .bv-admin-badge:hover {
            background: #DDD6FE;
            color: #5B21B6;
        }
        .bv-admin-badge--small {
            font-size: 10px;
            padding: 0 4px;
            margin-left: 4px;
        }
    </style>
    <?php
}
add_action('wp_head', 'bimverdi_admin_badge_styles');
