<?php
/**
 * Plugin Name: BIM Verdi - Composer Autoloader
 * Description: Loads Composer-managed dependencies (vendor/autoload.php).
 * Version: 1.0.0
 *
 * Må lastes tidlig fordi andre mu-plugins kan bruke vendor-klasser.
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

$bv_autoload = WP_CONTENT_DIR . '/vendor/autoload.php';

if (file_exists($bv_autoload)) {
    require_once $bv_autoload;
} else {
    // Ikke fatal — siden skal kunne laste uten composer-deps installert.
    // Logges for debugging.
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('BIMVerdi: vendor/autoload.php mangler. Kjør `composer install` i wp-content/.');
    }
}
