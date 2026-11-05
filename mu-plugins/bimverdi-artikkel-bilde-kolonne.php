<?php
/**
 * Plugin Name: BIM Verdi - Fremhevet bilde-kolonne på artikler
 * Description: Viser Ja/Nei i wp-admin-lista for artikler etter om artikkelen har fremhevet bilde, og lar deg filtrere på det.
 * Version: 1.1.0
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

/**
 * Nedtrekk over lista: alle / med bilde / uten bilde.
 */
add_action('restrict_manage_posts', 'bimverdi_artikkel_bildefilter_felt');
function bimverdi_artikkel_bildefilter_felt($post_type) {
    if ($post_type !== 'artikkel') {
        return;
    }

    $valgt = isset($_GET['bv_bilde']) ? sanitize_key(wp_unslash($_GET['bv_bilde'])) : '';
    $valg = array(
        ''     => 'Alle (bilde)',
        'med'  => 'Med fremhevet bilde',
        'uten' => 'Uten fremhevet bilde',
    );

    echo '<select name="bv_bilde">';
    foreach ($valg as $verdi => $etikett) {
        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr($verdi),
            selected($valgt, $verdi, false),
            esc_html($etikett)
        );
    }
    echo '</select>';
}

/**
 * Filtrer på _thumbnail_id. En tom eller 0-verdi regnes som «uten bilde»,
 * samme som has_post_thumbnail() i kolonnen.
 */
add_action('pre_get_posts', 'bimverdi_artikkel_bildefilter_query');
function bimverdi_artikkel_bildefilter_query($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    if ($query->get('post_type') !== 'artikkel') {
        return;
    }

    $valgt = isset($_GET['bv_bilde']) ? sanitize_key(wp_unslash($_GET['bv_bilde'])) : '';

    if ($valgt === 'med') {
        $klausul = array(
            'key'     => '_thumbnail_id',
            'value'   => array('', '0'),
            'compare' => 'NOT IN',
        );
    } elseif ($valgt === 'uten') {
        $klausul = array(
            'relation' => 'OR',
            array(
                'key'     => '_thumbnail_id',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => '_thumbnail_id',
                'value'   => array('', '0'),
                'compare' => 'IN',
            ),
        );
    } else {
        return;
    }

    $eksisterende = $query->get('meta_query');
    $query->set('meta_query', empty($eksisterende)
        ? array($klausul)
        : array('relation' => 'AND', $eksisterende, $klausul));
}
