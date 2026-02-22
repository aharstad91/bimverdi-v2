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
 * Returns a small admin badge with user ID and wp-admin user edit link.
 * Shows the author/registerer of a resource. Only visible for admins.
 *
 * @param int|null $post_id Post ID (defaults to current post)
 * @return string HTML badge or empty string
 */
function bimverdi_admin_user_badge($post_id = null) {
    if (!current_user_can('manage_options')) {
        return '';
    }

    if (!$post_id) {
        $post_id = get_the_ID();
    }

    if (!$post_id) {
        return '';
    }

    // Determine the responsible user based on post type
    $post_type = get_post_type($post_id);
    $user_id = 0;

    if ($post_type === 'foretak') {
        // Foretak: use hovedkontaktperson
        $user_id = get_field('hovedkontaktperson', $post_id);
        if (is_array($user_id)) {
            $user_id = $user_id['ID'] ?? 0;
        }
    } else {
        // Verktøy, kunnskapskilde, etc.: try registrert_av first, then post_author
        $registrert_av = get_field('registrert_av', $post_id);
        if ($registrert_av) {
            $user_id = is_array($registrert_av) ? ($registrert_av['ID'] ?? 0) : $registrert_av;
        }
        if (!$user_id) {
            $user_id = get_post_field('post_author', $post_id);
        }
    }

    if (!$user_id) {
        return '';
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return '';
    }

    $edit_url = admin_url('user-edit.php?user_id=' . $user_id);
    $display = $user->display_name ?: $user->user_login;

    return sprintf(
        '<a href="%s" class="bv-admin-badge bv-admin-badge--user" title="Redaktør: %s (bruker #%d)" target="_blank">👤 %s #%d</a>',
        esc_url($edit_url),
        esc_attr($display),
        (int) $user_id,
        esc_html($display),
        (int) $user_id
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
        .bv-admin-badge--user {
            color: #0369A1;
            background: #E0F2FE;
            border-color: #7DD3FC;
        }
        .bv-admin-badge--user:hover {
            background: #BAE6FD;
            color: #0C4A6E;
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
