<?php
/**
 * Gravity Forms Dynamic Populate Script
 * 
 * Fallback JavaScript solution to populate Form 4 fields with user data
 * Useful if pre_render hook doesn't work
 */

wp_register_script(
    'bim-gform-populate',
    plugin_dir_url(__FILE__) . 'assets/js/gform-populate.js',
    array('jquery'),
    '1.0.0',
    true
);

wp_localize_script('bim-gform-populate', 'bimGformData', array(
    'userEmail' => wp_get_current_user()->user_email ?? '',
    'firstName' => wp_get_current_user()->first_name ?? '',
    'lastName' => wp_get_current_user()->last_name ?? '',
    'phone' => get_user_meta(wp_get_current_user()->ID, 'phone', true) ?? '',
    'jobTitle' => get_user_meta(wp_get_current_user()->ID, 'job_title', true) ?? '',
    'linkedinUrl' => get_user_meta(wp_get_current_user()->ID, 'linkedin_url', true) ?? '',
));

wp_enqueue_script('bim-gform-populate');
