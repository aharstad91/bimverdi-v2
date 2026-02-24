<?php
/**
 * Template Name: Registrer Foretak
 *
 * Redirects to Min Side version. This standalone template is deprecated.
 * All foretak registration is handled via /min-side/foretak/registrer/
 *
 * @package BimVerdi_Theme
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/logg-inn/?redirect_to=' . urlencode(home_url('/min-side/foretak/registrer/'))));
    exit;
}

wp_redirect(home_url('/min-side/foretak/registrer/'));
exit;
