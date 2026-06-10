<?php
/**
 * AEC AI Hub — read-only adminrapport (Unmapped / orphaned / dedup / siste-kjøring).
 *
 * En ren DIAGNOSE-side under «Verktøy» som lar en redaktør se importens tilstand uten å
 * kunne utløse noe. Den MUTERER ALDRI: ingen skjema, ingen «Kjør nå»-knapp → ingen
 * CSRF-flate (sync + batch-publish er bevisst CLI-only). Cap = `manage_options`.
 *
 * Den viser:
 *   1. DB-tilstand: antall managed-poster, AI ('1') vs ikke-AI ('0'), unmapped, orphaned,
 *      manual-override, fordelt på post-status.
 *   2. Siste kjøring: `bv_aihub_last_synced_count`-option + nyeste `_bv_aec_synced_at`.
 *   3. Fixture: sti/eksistens/mtime + `_meta`-blokk.
 *   4. Read-only forhåndsvisning: leser fixturen og kjører champion-filter + dedup +
 *      kategori-mapping IN-MEMORY (ingen skriv) for å forutsi 238→236, dedup-warnings
 *      (youai/superhuman) og de ~54 umappbare — uten å kjøre importen.
 *   5. Advarsler: floor-risiko, dedup-navnekonflikt, status-divergens, multi-key.
 *   6. Tabeller: umappede (~54) og orphaned managed-poster med rå Notion-kategori,
 *      URL-nøkkel, kanonisk URL, name_key, ai_driven, status og edit-lenke.
 *
 * ALLE kilde-/Notion-avledede strenger escapes (`esc_html`/`esc_url`).
 *
 * @package BIMVerdiCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class BV_AIHUB_Admin_Report {

    /** Submeny-slug. */
    const PAGE_SLUG = 'bv-aihub-report';

    /** Post-statuser vi regner som «levende» managed-poster (ekskl. trash/auto-draft). */
    private static function statuses() {
        return array('publish', 'draft', 'pending', 'private', 'future');
    }

    /** Registrer admin-menyen. Kalles én gang ved fil-last (kun aktiv i admin). */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
    }

    /** Legg siden under «Verktøy»-CPT-menyen. */
    public static function register_menu() {
        $cpt = defined('BV_CPT_TOOL') ? BV_CPT_TOOL : 'verktoy';
        add_submenu_page(
            'edit.php?post_type=' . $cpt,
            'AEC AI Hub-synk — rapport',
            'AEC-synk rapport',
            'manage_options',
            self::PAGE_SLUG,
            array(__CLASS__, 'render')
        );
    }

    /** Tell managed-poster som matcher ekstra meta-betingelser (via found_posts). */
    private static function count_managed(array $extra_meta = array(), $status = null) {
        $meta = array('relation' => 'AND', array('key' => '_bv_aec_managed', 'value' => '1'));
        foreach ($extra_meta as $m) {
            $meta[] = $m;
        }
        $q = new WP_Query(array(
            'post_type'      => self::tool_cpt(),
            'post_status'    => $status ? $status : self::statuses(),
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => false,
            'meta_query'     => $meta,
        ));
        return (int) $q->found_posts;
    }

    /** Hent managed-poster (full) som matcher ekstra meta — for tabellene. Avgrenset. */
    private static function get_managed(array $extra_meta, $limit = 300) {
        $meta = array('relation' => 'AND', array('key' => '_bv_aec_managed', 'value' => '1'));
        foreach ($extra_meta as $m) {
            $meta[] = $m;
        }
        $q = new WP_Query(array(
            'post_type'      => self::tool_cpt(),
            'post_status'    => self::statuses(),
            'posts_per_page' => $limit,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
            'meta_query'     => $meta,
        ));
        return $q->posts;
    }

    private static function tool_cpt() {
        return defined('BV_CPT_TOOL') ? BV_CPT_TOOL : 'verktoy';
    }

    /** Nyeste `_bv_aec_synced_at` blant managed-poster (eller '' hvis ingen). */
    private static function latest_synced_at() {
        $q = new WP_Query(array(
            'post_type'      => self::tool_cpt(),
            'post_status'    => self::statuses(),
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_key'       => '_bv_aec_synced_at',
            'orderby'        => 'meta_value',
            'order'          => 'DESC',
            'meta_query'     => array(array('key' => '_bv_aec_managed', 'value' => '1')),
        ));
        if (empty($q->posts)) {
            return '';
        }
        return (string) get_post_meta((int) $q->posts[0], '_bv_aec_synced_at', true);
    }

    /**
     * Read-only fixture-forhåndsvisning: fetch + champion-filter + dedup + mapping, IN-MEMORY.
     * Skriver ALDRI. Returnerer null hvis kilde/klasser ikke er tilgjengelige.
     */
    private static function preview_fixture() {
        if (!class_exists('BV_AIHUB_Tool_Source') || !class_exists('BV_AIHUB_Tool_Upserter')) {
            return null;
        }
        try {
            $fetch = BV_AIHUB_Tool_Source::fetch_tools();
        } catch (\Throwable $e) {
            return array('error' => $e->getMessage());
        }
        if (empty($fetch['ok'])) {
            return array(
                'ok'    => false,
                'error' => isset($fetch['error']) ? (string) $fetch['error'] : 'ukjent feil',
                'meta'  => isset($fetch['meta']) ? $fetch['meta'] : array(),
            );
        }

        $dd       = BV_AIHUB_Tool_Upserter::champion_filter_and_dedup($fetch['tools']);
        $unique   = $dd['tools'];
        $unmapped = 0;
        if (class_exists('BV_AIHUB_Category_Mapper')) {
            foreach ($unique as $t) {
                $cats = isset($t['categories']) ? $t['categories'] : array();
                $map  = BV_AIHUB_Category_Mapper::map_tool_categories($cats);
                if (!empty($map['unmapped'])) {
                    $unmapped++;
                }
            }
        }
        return array(
            'ok'          => true,
            'meta'        => isset($fetch['meta']) ? $fetch['meta'] : array(),
            'counts'      => isset($fetch['counts']) ? $fetch['counts'] : array(),
            'fetch_warn'  => isset($fetch['warnings']) ? $fetch['warnings'] : array(),
            'unique'      => count($unique),
            'dropped'     => (int) $dd['dropped'],
            'dedup_warn'  => $dd['warnings'],
            'unmapped'    => $unmapped,
        );
    }

    /** Render-callback. */
    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Ingen tilgang.', 'bim-verdi-core'));
        }

        // ── DB-tilstand ────────────────────────────────────────────────────────
        $total      = self::count_managed();
        $ai_yes     = self::count_managed(array(array('key' => '_bv_aec_ai_driven', 'value' => '1')));
        $ai_no      = self::count_managed(array(array('key' => '_bv_aec_ai_driven', 'value' => '0')));
        $unmapped_c = self::count_managed(array(array('key' => '_bv_unmapped', 'value' => '1')));
        $orphan_c   = self::count_managed(array(array('key' => '_bv_orphaned', 'value' => '1')));
        $override_c = self::count_managed(array(array('key' => '_bv_aec_manual_override', 'value' => '1')));
        $st_publish = self::count_managed(array(), 'publish');
        $st_draft   = self::count_managed(array(), 'draft');

        // ── Siste kjøring ──────────────────────────────────────────────────────
        $last_count = (int) get_option('bv_aihub_last_synced_count', 0);
        $synced_at  = self::latest_synced_at();
        $locked     = get_transient('bv_aihub_sync_lock');

        // ── Fixture ────────────────────────────────────────────────────────────
        $fix_path   = defined('BV_AIHUB_FIXTURE_PATH') ? BV_AIHUB_FIXTURE_PATH : '';
        $fix_exists = $fix_path && file_exists($fix_path);
        $fix_mtime  = $fix_exists ? gmdate('Y-m-d H:i', filemtime($fix_path)) . ' UTC' : '';

        // ── Read-only forhåndsvisning ─────────────────────────────────────────
        $preview = self::preview_fixture();

        // ── Floor-risiko (forhåndsvisnings-unike < 50 % av forrige) ────────────
        $floor_risk = false;
        if ($preview && !empty($preview['ok']) && $last_count > 0) {
            $floor_risk = ($preview['unique'] < ($last_count * 0.5));
        }

        // ── Status-divergens (menneske endret status etter vår insert) ─────────
        $divergent = self::find_status_divergence();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html('AEC AI Hub-synk — rapport'); ?></h1>

            <div class="notice notice-info inline" style="margin:16px 0;padding:10px 14px;">
                <strong><?php echo esc_html('Kun lesing.'); ?></strong>
                <?php echo esc_html('Selve synkroniseringen og bulk-publisering kjøres bevisst fra WP-CLI '
                    . '(wp bimverdi aihub-sync / aihub-publish-batch) — denne siden har ingen handlingsknapper.'); ?>
                <?php if ($locked): ?>
                    <br><span style="color:#b32d2e;"><strong><?php echo esc_html('⏳ En synk-kjøring holder akkurat nå mutex-låsen.'); ?></strong></span>
                <?php endif; ?>
            </div>

            <h2><?php echo esc_html('Tilstand i databasen'); ?></h2>
            <table class="widefat striped" style="max-width:640px;">
                <tbody>
                    <tr><td><strong><?php echo esc_html('Managed-poster totalt'); ?></strong></td><td><?php echo (int) $total; ?></td></tr>
                    <tr><td><?php echo esc_html('— AI-drevet (ai_driven = "1")'); ?></td><td><?php echo (int) $ai_yes; ?></td></tr>
                    <tr><td><?php echo esc_html('— Ikke AI-drevet (ai_driven = "0")'); ?></td><td><?php echo (int) $ai_no; ?></td></tr>
                    <tr><td><?php echo esc_html('— Publisert / Utkast'); ?></td><td><?php echo (int) $st_publish; ?> / <?php echo (int) $st_draft; ?></td></tr>
                    <tr><td style="color:#996800;"><?php echo esc_html('Umappede (i «Ukategorisert», til remapping)'); ?></td><td><strong><?php echo (int) $unmapped_c; ?></strong></td></tr>
                    <tr><td style="color:#b32d2e;"><?php echo esc_html('Orphaned (forsvunnet fra kilden)'); ?></td><td><strong><?php echo (int) $orphan_c; ?></strong></td></tr>
                    <tr><td><?php echo esc_html('Manuelt overstyrt (fryst fra synk)'); ?></td><td><?php echo (int) $override_c; ?></td></tr>
                </tbody>
            </table>

            <h2 style="margin-top:28px;"><?php echo esc_html('Siste synk'); ?></h2>
            <p>
                <?php echo esc_html('Sist synkroniserte antall (option): '); ?><strong><?php echo (int) $last_count; ?></strong><br>
                <?php echo esc_html('Nyeste _bv_aec_synced_at: '); ?>
                <strong><?php echo $synced_at !== '' ? esc_html($synced_at) : esc_html('— (ingen synk kjørt ennå)'); ?></strong>
            </p>

            <h2 style="margin-top:28px;"><?php echo esc_html('Fixture (datakilde)'); ?></h2>
            <p>
                <?php echo esc_html('Sti: '); ?><code><?php echo esc_html($fix_path); ?></code><br>
                <?php echo esc_html('Status: '); ?>
                <?php if ($fix_exists): ?>
                    <span style="color:#1a7f37;"><?php echo esc_html('finnes'); ?></span>
                    <?php echo esc_html(' — endret ' . $fix_mtime); ?>
                <?php else: ?>
                    <span style="color:#b32d2e;"><?php echo esc_html('MANGLER / uleselig'); ?></span>
                <?php endif; ?>
            </p>

            <?php self::render_preview($preview, $floor_risk, $last_count); ?>

            <?php self::render_warnings($preview, $floor_risk, $divergent); ?>

            <?php
            // ── Tabeller ────────────────────────────────────────────────────────
            self::render_post_table(
                'Umappede managed-poster (rå Notion-kategori dekkes ikke av de 6 temagruppene)',
                self::get_managed(array(array('key' => '_bv_unmapped', 'value' => '1'))),
                'Alle managed-poster er mappet til en temagruppe. Ingen i «Ukategorisert».'
            );

            self::render_post_table(
                'Orphaned managed-poster (URL ikke lenger i kilden)',
                self::get_managed(array(array('key' => '_bv_orphaned', 'value' => '1'))),
                'Ingen orphaned poster — alle managed-URL-er finnes fortsatt i kilden.'
            );
            ?>
        </div>
        <?php
    }

    /** Read-only forhåndsvisnings-blokk. */
    private static function render_preview($preview, $floor_risk, $last_count) {
        echo '<h2 style="margin-top:28px;">' . esc_html('Forhåndsvisning av kilden (kun lesing — ingen skriv)') . '</h2>';

        if ($preview === null) {
            echo '<p>' . esc_html('Kilde-/upserter-klassene er ikke tilgjengelige — kan ikke forhåndsvise.') . '</p>';
            return;
        }
        if (empty($preview['ok'])) {
            echo '<div class="notice notice-error inline" style="padding:10px 14px;"><strong>'
                . esc_html('Fixturen kan ikke leses/valideres: ')
                . '</strong>' . esc_html(isset($preview['error']) ? $preview['error'] : 'ukjent feil') . '</div>';
            return;
        }

        $counts = $preview['counts'];
        echo '<table class="widefat striped" style="max-width:640px;"><tbody>';
        echo '<tr><td>' . esc_html('Rader totalt i fixturen') . '</td><td>' . (int) ($counts['total'] ?? 0) . '</td></tr>';
        echo '<tr><td>' . esc_html('Champions (import-kandidater)') . '</td><td>' . (int) ($counts['champion'] ?? 0) . '</td></tr>';
        echo '<tr><td>' . esc_html('— droppet i dedup (kolliderende URL-er)') . '</td><td>' . (int) $preview['dropped'] . '</td></tr>';
        echo '<tr><td><strong>' . esc_html('= Unike etter dedup (det som importeres)') . '</strong></td><td><strong>' . (int) $preview['unique'] . '</strong></td></tr>';
        echo '<tr><td style="color:#996800;">' . esc_html('— hvorav umappbare → «Ukategorisert» (draft)') . '</td><td><strong>' . (int) $preview['unmapped'] . '</strong></td></tr>';
        echo '<tr><td>' . esc_html('AI-drevet blant champions') . '</td><td>' . (int) ($counts['champion_and_ai'] ?? $counts['ai_driven'] ?? 0) . '</td></tr>';
        echo '</tbody></table>';

        if ($floor_risk) {
            echo '<p style="color:#b32d2e;"><strong>' . esc_html('⚠ Floor-risiko: ')
                . '</strong>' . esc_html(sprintf(
                    'Forhåndsvist unikt antall (%d) er under 50 %% av forrige synkede antall (%d) — '
                    . 'en ekte kjøring ville HOPPE OVER orphan-rekonsiliering (vern mot trunkert fixture).',
                    (int) $preview['unique'],
                    (int) $last_count
                )) . '</p>';
        }
    }

    /** Advarsels-panel: dedup-warnings, fetch-dup-warnings, status-divergens, floor. */
    private static function render_warnings($preview, $floor_risk, $divergent) {
        $rows = array();

        if ($preview && !empty($preview['ok'])) {
            foreach ($preview['dedup_warn'] as $w) {
                $rows[] = sprintf(
                    'Dedup-navnekonflikt på %s: beholdt «%s» av {%s} (%d rader collapset)',
                    $w['key'],
                    $w['kept_name'],
                    implode(', ', (array) $w['all_names']),
                    (int) $w['count']
                );
            }
            if (!empty($preview['fetch_warn']['dup_identity_keys'])) {
                foreach ($preview['fetch_warn']['dup_identity_keys'] as $k => $c) {
                    $rows[] = sprintf('Duplikat identity_key i kilden: %s (%d forekomster)', $k, (int) $c);
                }
            }
        }
        if ($floor_risk) {
            $rows[] = 'Floor-vern aktivt ved neste kjøring (se forhåndsvisning over).';
        }
        foreach ($divergent as $d) {
            $rows[] = sprintf(
                'Status-divergens: «%s» (#%d) står som «%s», men synken satte sist «%s» — synken vil IKKE røre den (menneske har overtatt).',
                $d['title'],
                $d['id'],
                $d['current'],
                $d['last']
            );
        }

        echo '<h2 style="margin-top:28px;">' . esc_html('Advarsler') . '</h2>';
        if (empty($rows)) {
            echo '<p style="color:#1a7f37;">' . esc_html('Ingen advarsler.') . '</p>';
            return;
        }
        echo '<ul style="list-style:disc;margin-left:20px;">';
        foreach ($rows as $r) {
            echo '<li style="margin:4px 0;">' . esc_html($r) . '</li>';
        }
        echo '</ul>';
    }

    /**
     * Finn managed-poster der nåværende post_status avviker fra `_bv_aec_last_sync_status`
     * (kun der last_sync_status er satt) — dvs. et menneske har endret status etter vår insert.
     */
    private static function find_status_divergence() {
        $posts = self::get_managed(
            array(array('key' => '_bv_aec_last_sync_status', 'compare' => 'EXISTS')),
            300
        );
        $out = array();
        foreach ($posts as $p) {
            $last = (string) get_post_meta($p->ID, '_bv_aec_last_sync_status', true);
            if ($last === '') {
                continue;
            }
            if ($p->post_status !== $last) {
                $out[] = array(
                    'id'      => (int) $p->ID,
                    'title'   => (string) $p->post_title,
                    'current' => (string) $p->post_status,
                    'last'    => $last,
                );
            }
        }
        return $out;
    }

    /** Render en widefat-tabell over managed-poster. Alle felt escapes. */
    private static function render_post_table($heading, array $posts, $empty_msg) {
        echo '<h2 style="margin-top:28px;">' . esc_html($heading)
            . ' <span style="font-weight:normal;color:#646970;">(' . (int) count($posts) . ')</span></h2>';

        if (empty($posts)) {
            echo '<p style="color:#1a7f37;">' . esc_html($empty_msg) . '</p>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr>'
            . '<th>' . esc_html('Navn') . '</th>'
            . '<th>' . esc_html('URL-nøkkel (identitet)') . '</th>'
            . '<th>' . esc_html('Rå kategori (Notion)') . '</th>'
            . '<th>' . esc_html('name_key') . '</th>'
            . '<th>' . esc_html('AI') . '</th>'
            . '<th>' . esc_html('Status') . '</th>'
            . '<th>' . esc_html('Handling') . '</th>'
            . '</tr></thead><tbody>';

        foreach ($posts as $p) {
            $id        = (int) $p->ID;
            $source    = (string) get_post_meta($id, '_bv_aec_source_key', true);
            $canonical = (string) get_post_meta($id, '_bv_aec_canonical_url', true);
            $raw_cat   = (string) get_post_meta($id, '_bv_aec_raw_category', true);
            $name_key  = (string) get_post_meta($id, '_bv_aec_name_key', true);
            $ai        = (string) get_post_meta($id, '_bv_aec_ai_driven', true);
            $edit      = get_edit_post_link($id, '');

            echo '<tr>';
            echo '<td><strong>' . esc_html($p->post_title) . '</strong></td>';
            echo '<td><code>' . esc_html($source) . '</code>';
            if ($canonical !== '' && $canonical !== $source) {
                echo '<br><span style="color:#646970;font-size:11px;">' . esc_html($canonical) . '</span>';
            }
            echo '</td>';
            echo '<td>' . ($raw_cat !== '' ? esc_html($raw_cat) : '<span style="color:#b32d2e;">—</span>') . '</td>';
            echo '<td><code>' . esc_html($name_key) . '</code></td>';
            echo '<td>' . esc_html($ai !== '' ? $ai : '—') . '</td>';
            echo '<td>' . esc_html($p->post_status) . '</td>';
            echo '<td>';
            if ($edit) {
                echo '<a href="' . esc_url($edit) . '">' . esc_html('Rediger') . '</a>';
            } else {
                echo esc_html('—');
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}

// Registrer admin-menyen (admin_menu fyrer kun i wp-admin).
BV_AIHUB_Admin_Report::init();
