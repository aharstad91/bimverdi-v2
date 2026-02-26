<?php
/**
 * ACF Options Page for Archive Page Intros
 *
 * Registers an options page where admin can edit title + intro text
 * for each public archive page (deltakere, verktøy, etc.)
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('acf/init', function () {
    if (!function_exists('acf_add_options_page')) {
        return;
    }

    acf_add_options_page([
        'page_title'  => 'Arkivsider',
        'menu_title'  => 'Arkivsider',
        'menu_slug'   => 'bimverdi-archive-intros',
        'capability'  => 'manage_options',
        'parent_slug' => 'options-general.php',
        'position'    => '',
        'icon_url'    => '',
        'redirect'    => false,
    ]);
});
