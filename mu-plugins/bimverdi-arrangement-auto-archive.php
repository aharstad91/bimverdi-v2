<?php
/**
 * BIM Verdi - Auto-archive past arrangements
 *
 * Runs daily via WP-Cron. Automatically sets arrangement_status_toggle
 * to 'tidligere' when the event date has passed.
 *
 * @package BIM_Verdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Schedule the daily cron event on plugin load
 */
add_action('init', function () {
    if (!wp_next_scheduled('bimverdi_auto_archive_arrangements')) {
        wp_schedule_event(time(), 'daily', 'bimverdi_auto_archive_arrangements');
    }
});

/**
 * Auto-archive arrangements whose date has passed
 */
add_action('bimverdi_auto_archive_arrangements', 'bimverdi_run_auto_archive');

function bimverdi_run_auto_archive() {
    $today = current_time('Y-m-d');

    // Find all 'kommende' arrangements with a date before today
    $arrangements = get_posts([
        'post_type'      => 'arrangement',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => 'arrangement_status_toggle',
                'value'   => 'kommende',
                'compare' => '=',
            ],
            [
                'key'     => 'arrangement_dato',
                'value'   => $today,
                'compare' => '<',
                'type'    => 'DATE',
            ],
        ],
    ]);

    foreach ($arrangements as $arrangement_id) {
        update_field('arrangement_status_toggle', 'tidligere', $arrangement_id);
    }

    if (!empty($arrangements)) {
        error_log(sprintf(
            'BIM Verdi: Auto-archived %d arrangement(s): %s',
            count($arrangements),
            implode(', ', $arrangements)
        ));
    }
}

/**
 * Also check on single arrangement page load (real-time fallback)
 * If someone visits a past event that hasn't been archived yet, archive it now.
 */
add_action('template_redirect', function () {
    if (!is_singular('arrangement')) {
        return;
    }

    $arrangement_id = get_the_ID();
    $toggle = get_field('arrangement_status_toggle', $arrangement_id);
    $dato = get_field('arrangement_dato', $arrangement_id);

    if ($toggle === 'kommende' && $dato && strtotime($dato) < strtotime('today')) {
        update_field('arrangement_status_toggle', 'tidligere', $arrangement_id);
    }
});

/**
 * Cleanup on deactivation
 */
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('bimverdi_auto_archive_arrangements');
});
