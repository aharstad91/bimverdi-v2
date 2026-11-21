<?php
/**
 * Plugin Name: BIM Verdi — Min Side-varsler (B-029)
 * Description: Minimal varseltabell for Min Side. Første bruker er krav 31 (meldinger).
 * Version: 1.0.0
 *
 * B-029 (beslutningsloggen i bimverdi-context): én tabell
 * wp_bimverdi_notifications med id, user_id, type, payload (JSON),
 * opprettet_dato og lest_dato. Ikke en universell varselmotor.
 *
 * Runde A av krav 31 lager bare tabellen og funksjonene. Klokke-ikon i
 * headeren og /min-side/varslinger/ kommer i en senere runde.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

const BIMVERDI_VARSLER_DB_VERSION = '1';

/**
 * @return string Tabellnavn med prefiks.
 */
function bv_varsler_tabell() {
    global $wpdb;
    return $wpdb->prefix . 'bimverdi_notifications';
}

/**
 * Opprett tabellen. Kjøres bare når versjonsnøkkelen mangler eller er gammel.
 *
 * @return void
 */
function bv_varsler_opprett_tabell() {
    global $wpdb;

    $tabell  = bv_varsler_tabell();
    $collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$tabell} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        type varchar(40) NOT NULL,
        payload longtext NULL,
        opprettet_dato datetime NOT NULL,
        lest_dato datetime NULL DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY user_lest (user_id, lest_dato),
        KEY type (type)
    ) {$collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    update_option('bimverdi_varsler_db_version', BIMVERDI_VARSLER_DB_VERSION);
}

add_action('init', function () {
    if (get_option('bimverdi_varsler_db_version') !== BIMVERDI_VARSLER_DB_VERSION) {
        bv_varsler_opprett_tabell();
    }
}, 5);

/**
 * Lag et varsel for en bruker.
 *
 * @param int    $user_id
 * @param string $type    Kort nøkkel, f.eks. 'ny_melding'.
 * @param array  $payload Data som trengs for å vise varselet.
 * @return int|false ID på ny rad, eller false.
 */
function bv_varsel_opprett($user_id, $type, array $payload = array()) {
    global $wpdb;

    $user_id = (int) $user_id;
    if ($user_id <= 0 || $type === '') {
        return false;
    }

    $ok = $wpdb->insert(
        bv_varsler_tabell(),
        array(
            'user_id'        => $user_id,
            'type'           => sanitize_key($type),
            'payload'        => wp_json_encode($payload),
            'opprettet_dato' => current_time('mysql', true),
        ),
        array('%d', '%s', '%s', '%s')
    );

    return $ok ? (int) $wpdb->insert_id : false;
}

/**
 * Antall uleste varsler for en bruker.
 *
 * @param int         $user_id
 * @param string|null $type Begrens til én type. Null = alle.
 * @return int
 */
function bv_varsler_antall_uleste($user_id, $type = null) {
    global $wpdb;

    $tabell = bv_varsler_tabell();
    if ($type) {
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabell} WHERE user_id = %d AND type = %s AND lest_dato IS NULL",
            (int) $user_id,
            $type
        ));
    }

    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabell} WHERE user_id = %d AND lest_dato IS NULL",
        (int) $user_id
    ));
}

/**
 * Marker uleste varsler av en type som lest, valgfritt bare de der
 * payload[$nokkel] == $verdi (f.eks. alle 'ny_melding' for én tråd).
 *
 * @param int         $user_id
 * @param string      $type
 * @param string|null $nokkel
 * @param mixed       $verdi
 * @return int Antall rader markert.
 */
function bv_varsler_marker_lest($user_id, $type, $nokkel = null, $verdi = null) {
    global $wpdb;

    $tabell = bv_varsler_tabell();
    $rader  = $wpdb->get_results($wpdb->prepare(
        "SELECT id, payload FROM {$tabell} WHERE user_id = %d AND type = %s AND lest_dato IS NULL",
        (int) $user_id,
        $type
    ));

    $ids = array();
    foreach ((array) $rader as $rad) {
        if ($nokkel === null) {
            $ids[] = (int) $rad->id;
            continue;
        }
        $data = json_decode((string) $rad->payload, true);
        if (is_array($data) && isset($data[$nokkel]) && (string) $data[$nokkel] === (string) $verdi) {
            $ids[] = (int) $rad->id;
        }
    }

    if (!$ids) {
        return 0;
    }

    $plassholdere = implode(',', array_fill(0, count($ids), '%d'));
    $parametre    = array_merge(array(current_time('mysql', true)), $ids);

    return (int) $wpdb->query($wpdb->prepare(
        "UPDATE {$tabell} SET lest_dato = %s WHERE id IN ({$plassholdere})",
        $parametre
    ));
}
