<?php
/**
 * BIM Verdi — Migration: legg til invitasjons_type-kolonne i wp_bimverdi_invitations
 *
 * Krav 23-v3 + 24-v4 AK-17 forutsetter at åpne invitasjoner kan merkes som
 * 'gratisforetak' eller 'foretak'. Ved konvertering av et Gratisforetak til
 * Foretak (krav 24-v4 Unit 7) oppdateres alle åpne invitasjoner som var sendt
 * mens foretaket var Gratisforetak — slik at mottakere som aksepterer etter
 * konvertering blir Tilleggskontakter i Foretaket istedenfor Gratisbrukere.
 *
 * Idempotent:
 * - Skjema-endringen kjøres kun hvis kolonnen mangler.
 * - Backfill skipper rader som allerede har invitasjons_type satt.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'bimverdi_invitations_maybe_add_invitasjons_type_column', 20);

/**
 * Idempotent: kjør én gang når kolonnen mangler.
 */
function bimverdi_invitations_maybe_add_invitasjons_type_column() {
    if (get_option('bimverdi_invitations_invitasjons_type_added') === 'yes') {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'bimverdi_invitations';

    // Sjekk om tabellen finnes (mu-plugin kan kjøres før company-invitations har opprettet tabellen)
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if (!$table_exists) {
        return; // Vent til tabellen finnes
    }

    $col_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'invitasjons_type'",
        DB_NAME,
        $table
    ));

    if ((int) $col_exists === 0) {
        // Legg til kolonnen med default 'foretak' (eksisterende invitasjoner antas å være Foretak per krav 21).
        $wpdb->query("ALTER TABLE $table ADD COLUMN invitasjons_type VARCHAR(20) NOT NULL DEFAULT 'foretak' AFTER role");
        $wpdb->query("ALTER TABLE $table ADD INDEX idx_invitasjons_type (invitasjons_type)");
        error_log('[bimverdi-migration] Added invitasjons_type column + index to ' . $table);
    }

    // Backfill: åpne invitasjoner der company_id peker til Gratisforetak.
    // Vi bruker post_status='publish' AND meta bv_foretakstype='gratisforetak'.
    $gratis_ids = get_posts([
        'post_type'      => 'foretak',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            ['key' => 'bv_foretakstype', 'value' => 'gratisforetak'],
        ],
    ]);

    if (!empty($gratis_ids)) {
        $placeholders = implode(',', array_fill(0, count($gratis_ids), '%d'));
        $sql = "UPDATE $table SET invitasjons_type = 'gratisforetak'
                WHERE company_id IN ($placeholders)
                  AND status = 'pending'
                  AND invitasjons_type = 'foretak'";
        $updated = $wpdb->query($wpdb->prepare($sql, $gratis_ids));
        if ($updated > 0) {
            error_log("[bimverdi-migration] Backfilled invitasjons_type='gratisforetak' on $updated open invitations");
        }
    }

    update_option('bimverdi_invitations_invitasjons_type_added', 'yes');
}

/**
 * Helper: marker alle åpne invitasjoner for et foretak som 'foretak'-typed.
 * Kalles av Unit 7-konverterings-handleren etter at foretaket er konvertert.
 *
 * @param int $foretak_id
 * @return int Antall rader oppdatert.
 */
function bimverdi_invitations_mark_as_foretak($foretak_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'bimverdi_invitations';

    return (int) $wpdb->query($wpdb->prepare(
        "UPDATE $table SET invitasjons_type = 'foretak'
         WHERE company_id = %d AND status = 'pending'",
        (int) $foretak_id
    ));
}
