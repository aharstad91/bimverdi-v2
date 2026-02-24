<?php
/**
 * BIM Verdi - Gravity Forms Customizations
 *
 * Norwegian translations and brand styling for Gravity Forms.
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Translate GF "Other" choice to Norwegian "Annet"
 */
add_filter('gform_other_choice_value', function ($value, $field) {
    return 'Annet';
}, 10, 2);
