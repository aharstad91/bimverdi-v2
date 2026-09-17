<?php
/**
 * Plugin Name: BIM Verdi - Eier/leverandør-kolonne på verktøy
 * Description: Viser hvem som står bak hvert verktøy i wp-admin-lista, og lar Bård filtrere hub-verktøyene bort.
 * Version: 1.0.0
 *
 * Bård, Trello #350 punkt 4.1: «Legg til kolonne for 'Eier/leverandør' på
 * edit.php?post_type=verktoy». Bakgrunnen er punkt 4.2: en registrering han
 * trodde kom fra et deltakerforetak var i virkeligheten gjort av AEC AI
 * Hub-synken, og lista ga ham ingen måte å se forskjell på.
 *
 * Kolonnen leser `bimverdi_verktoy_eier_foretak_id()` — samme kilde som
 * katalogen og foretaksprofilen, slik at admin og front-end aldri viser
 * ulike eiere. Har verktøyet ingen eier, men bærer `_bv_aec_source`, er det
 * et hub-verktøy og kolonnen sier «AIinAEC-hub». Da slipper vi å skrive
 * AIinAEC inn som et foretak på ~1900 poster for å svare på spørsmålet
 * kolonnen egentlig stiller: kom dette fra en deltaker eller fra synken?
 *
 * Kolonnen er ikke sorterbar. Sortering på metanøkkel i wp-admin skjuler
 * radene som mangler nøkkelen, og det er nettopp deltakerverktøyene — de
 * ville forsvunnet fra lista ved klikk. Filteret under gjør samme nytte
 * uten den fella.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Legg kolonnen rett etter tittelen.
 */
add_filter('manage_verktoy_posts_columns', 'bimverdi_verktoy_kolonne_registrer');
function bimverdi_verktoy_kolonne_registrer($kolonner) {
    $ny = array();
    foreach ($kolonner as $nokkel => $etikett) {
        $ny[$nokkel] = $etikett;
        if ($nokkel === 'title') {
            $ny['bv_eier'] = 'Eier/leverandør';
        }
    }

    // Fantes ingen tittelkolonne (filtrert bort av noen andre), legg den bakerst.
    if (!isset($ny['bv_eier'])) {
        $ny['bv_eier'] = 'Eier/leverandør';
    }

    return $ny;
}

/**
 * Innholdet i kolonnen.
 */
add_action('manage_verktoy_posts_custom_column', 'bimverdi_verktoy_kolonne_innhold', 10, 2);
function bimverdi_verktoy_kolonne_innhold($kolonne, $post_id) {
    if ($kolonne !== 'bv_eier') {
        return;
    }

    $foretak_id = function_exists('bimverdi_verktoy_eier_foretak_id')
        ? bimverdi_verktoy_eier_foretak_id($post_id)
        : 0;

    if ($foretak_id) {
        $navn = get_the_title($foretak_id);
        $lenke = get_edit_post_link($foretak_id);
        echo $lenke
            ? '<a href="' . esc_url($lenke) . '">' . esc_html($navn) . '</a>'
            : esc_html($navn);
        return;
    }

    $kilde = get_post_meta($post_id, BIMVERDI_AEC_META_SOURCE, true);
    if (!empty($kilde)) {
        echo '<span style="color:#646970;">AIinAEC-hub</span>';
        return;
    }

    echo '<span style="color:#d63638;" title="Verktøyet har ingen eier og kommer ikke fra synken">Ingen</span>';
}

/**
 * Nedtrekk over lista: alle / kun deltakerverktøy / kun hub-verktøy.
 *
 * Uten dette drukner de ~40 deltakerverktøyene i ~1900 hub-verktøy, som er
 * grunnen til at Bård ikke oppdaget Smart Innovations registrering.
 */
add_action('restrict_manage_posts', 'bimverdi_verktoy_kildefilter_felt');
function bimverdi_verktoy_kildefilter_felt($post_type) {
    if ($post_type !== 'verktoy') {
        return;
    }

    $valgt = isset($_GET['bv_kilde']) ? sanitize_key(wp_unslash($_GET['bv_kilde'])) : '';
    $valg = array(
        ''          => 'Alle kilder',
        'deltaker'  => 'Kun deltakerverktøy',
        'aiinaec'   => 'Kun AIinAEC-hub',
    );

    echo '<select name="bv_kilde">';
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

add_action('pre_get_posts', 'bimverdi_verktoy_kildefilter_query');
function bimverdi_verktoy_kildefilter_query($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    if ($query->get('post_type') !== 'verktoy') {
        return;
    }

    $valgt = isset($_GET['bv_kilde']) ? sanitize_key(wp_unslash($_GET['bv_kilde'])) : '';
    if ($valgt !== 'deltaker' && $valgt !== 'aiinaec') {
        return;
    }

    $klausul = array(
        'key'     => BIMVERDI_AEC_META_SOURCE,
        'compare' => $valgt === 'deltaker' ? 'NOT EXISTS' : 'EXISTS',
    );

    $eksisterende = $query->get('meta_query');
    $query->set('meta_query', empty($eksisterende)
        ? array($klausul)
        : array('relation' => 'AND', $eksisterende, $klausul));
}
