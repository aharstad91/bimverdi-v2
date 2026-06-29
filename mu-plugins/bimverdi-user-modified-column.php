<?php
/**
 * Plugin Name: BIM Verdi – «Sist oppdatert»-kolonne på brukere
 * Description: Sporer når en brukerprofil sist ble lagret og viser det som en
 *              valgfri kolonne i wp-admin/users.php. Bård 23.06: «se hvem som
 *              har vært innom». Kolonnen kan slås av/på via Screen Options.
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lagre tidspunkt for siste profil-endring.
 * profile_update fyrer ved admin-redigering OG brukerens egen profil-lagring.
 * user_register gir nye brukere en initiell verdi.
 */
add_action('profile_update', 'bimverdi_stamp_user_modified');
add_action('user_register', 'bimverdi_stamp_user_modified');
function bimverdi_stamp_user_modified($user_id) {
    update_user_meta((int) $user_id, 'bv_user_modified', current_time('mysql'));
}

/**
 * Registrer kolonnen. Kolonner lagt til via manage_users_columns dukker
 * automatisk opp som avhukbar checkbox i Screen Options.
 */
add_filter('manage_users_columns', 'bimverdi_add_modified_column');
function bimverdi_add_modified_column($columns) {
    $columns['bv_user_modified'] = __('Sist oppdatert', 'bimverdi');
    return $columns;
}

/**
 * Render kolonneverdien. Faller tilbake til registreringsdato for brukere som
 * aldri er endret siden sporingen startet (registrering = første «lagring»).
 */
add_filter('manage_users_custom_column', 'bimverdi_render_modified_column', 10, 3);
function bimverdi_render_modified_column($output, $column_name, $user_id) {
    if ($column_name !== 'bv_user_modified') {
        return $output;
    }

    $modified = get_user_meta($user_id, 'bv_user_modified', true);
    $is_fallback = false;
    if (!$modified) {
        $user = get_userdata($user_id);
        $modified = $user ? $user->user_registered : '';
        $is_fallback = true;
    }
    if (!$modified) {
        return '<span style="color:#a7aaad;">—</span>';
    }

    $ts    = strtotime($modified);
    $label = esc_html(wp_date('j. M Y H:i', $ts));
    if ($is_fallback) {
        // Vis dempet + tooltip når verdien er registreringsdato (aldri endret etterpå).
        return '<span style="color:#787c82;" title="Aldri endret siden sporing startet – viser registreringsdato">' . $label . '</span>';
    }
    return $label;
}

/**
 * Gjør «Sist oppdatert» sorterbar (synk 29.06). Selve meta-sorteringen +
 * $pagenow-vakta håndteres av bimverdi_handle_user_meta_orderby() i
 * bimverdi-custom-roles.php; her registrerer vi bare kolonnen som sorterbar
 * og kobler token → meta-nøkkel inn i det delte kartet.
 */
add_filter('manage_users_sortable_columns', 'bimverdi_modified_sortable_column');
function bimverdi_modified_sortable_column($columns) {
    $columns['bv_user_modified'] = 'bv_orderby_modified';
    return $columns;
}

add_filter('bimverdi_user_orderby_meta_map', 'bimverdi_modified_register_orderby');
function bimverdi_modified_register_orderby($map) {
    $map['bv_orderby_modified'] = 'bv_user_modified';
    return $map;
}
