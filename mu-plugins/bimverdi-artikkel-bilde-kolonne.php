<?php
/**
 * Plugin Name: BIM Verdi - Fremhevet bilde-kolonne på artikler
 * Description: Viser Ja/Nei i wp-admin-lista for artikler etter om artikkelen har fremhevet bilde.
 * Version: 1.0.0
 *
 * Bård: «angi i egen kolonne (ja/nei) om artikkelen har featured img» på
 * edit.php?post_type=artikkel. Gjør det lett å se hvilke artikler som mangler
 * bilde uten å åpne hver enkelt.
 *
 * Kolonnen er ikke sorterbar. Sortering på _thumbnail_id i wp-admin skjuler
 * radene som mangler nøkkelen — altså nettopp artiklene uten bilde.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Legg kolonnen rett etter tittelen.
 */
add_filter('manage_artikkel_posts_columns', 'bimverdi_artikkel_bilde_kolonne_registrer');
function bimverdi_artikkel_bilde_kolonne_registrer($kolonner) {
    $ny = array();
    foreach ($kolonner as $nokkel => $etikett) {
        $ny[$nokkel] = $etikett;
        if ($nokkel === 'title') {
            $ny['bv_fremhevet_bilde'] = 'Fremhevet bilde';
        }
    }

    if (!isset($ny['bv_fremhevet_bilde'])) {
        $ny['bv_fremhevet_bilde'] = 'Fremhevet bilde';
    }

    return $ny;
}

/**
 * Innholdet i kolonnen: Ja (grønn) eller Nei (rød).
 */
add_action('manage_artikkel_posts_custom_column', 'bimverdi_artikkel_bilde_kolonne_innhold', 10, 2);
function bimverdi_artikkel_bilde_kolonne_innhold($kolonne, $post_id) {
    if ($kolonne !== 'bv_fremhevet_bilde') {
        return;
    }

    if (has_post_thumbnail($post_id)) {
        echo '<span style="color:#00a32a;font-weight:600;">Ja</span>';
        return;
    }

    echo '<span style="color:#d63638;font-weight:600;">Nei</span>';
}

/**
 * Smal kolonne.
 */
add_action('admin_head-edit.php', 'bimverdi_artikkel_bilde_kolonne_stil');
function bimverdi_artikkel_bilde_kolonne_stil() {
    $skjerm = get_current_screen();
    if (!$skjerm || $skjerm->post_type !== 'artikkel') {
        return;
    }
    echo '<style>.column-bv_fremhevet_bilde{width:110px;}</style>';
}
