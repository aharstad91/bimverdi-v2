<?php
/**
 * AEC AI Hub — committet selftest (kjøres via `wp bimverdi aihub-selftest`).
 *
 * Kodebasen har ingen PHPUnit/test-DB; eneste presedens er WP-CLI mot MAMP-DB-en
 * (Verification Strategy i planen). Denne klassen bootstrapper ekte WP og asserter
 * synk-motorens kontrakter: G4-paritet, Fase-1-validering, champion-filter+dedup,
 * idempotens, deltaker-vakt, AI-flagg `'1'`/`'0'`, mapping, orphan-livssyklus,
 * status-divergens, manuell override, floor og Fase-1-abort.
 *
 * PRODUKSJONSTRYGG: alle skriv skjer mot SYNTETISKE poster (source_key-prefiks
 * «zzselftest-») som ryddes etterpå. Orphan-testene reconcilerer ALLTID med et
 * present-sett som inkluderer alle EKTE managed source_keys — kun den ene syntetiske
 * nøkkelen under test ekskluderes — så en ekte import (236 poster) aldri orphanes
 * av selftesten. Floor/abort-grenene øves via `bimverdi_aihub_fetch_result`-seamen.
 *
 * @package BIMVerdiCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class BV_AIHUB_Selftest {

    /** Prefiks på syntetiske test-source_keys (gjør opprydding deterministisk). */
    const PREFIX = 'zzselftest-';

    /** @var array Resultater: [['name'=>string,'ok'=>bool,'detail'=>string], ...] */
    private $results = array();

    /** @var int[] Post-ID-er laget under kjøringen (force-delete i teardown). */
    private $created = array();

    /**
     * Kjør hele selftesten.
     *
     * @return array{pass:int,fail:int,results:array}
     */
    public static function run() {
        $t = new self();
        try {
            BV_AIHUB_Sync::clear_lock();
            $t->group_parity();
            $t->group_tool_source();
            $t->group_dryrun_full();
            $t->group_dedup_unit();
            $t->group_upsert_idempotency();
            $t->group_orphan_lifecycle();
            $t->group_floor();
            $t->group_abort();
        } catch (\Throwable $e) {
            $t->assert('selftest-rammeverk uten unntak', false, 'UNNTAK: ' . $e->getMessage());
        } finally {
            $t->teardown();
        }

        $pass = 0;
        $fail = 0;
        foreach ($t->results as $r) {
            if ($r['ok']) {
                $pass++;
            } else {
                $fail++;
            }
        }
        return array('pass' => $pass, 'fail' => $fail, 'results' => $t->results);
    }

    // ── Grupper ───────────────────────────────────────────────────────────

    /** G4: bv_aec_normalize_url(url) === identity_key for ALLE 475 rader. */
    private function group_parity() {
        $path = defined('BV_AIHUB_FIXTURE_PATH') ? BV_AIHUB_FIXTURE_PATH : '';
        $raw  = $path && is_readable($path) ? file_get_contents($path) : '';
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['tools'])) {
            $this->assert('G4: fixturen lest', false, 'kunne ikke lese hovedfixturen');
            return;
        }
        $mismatch = 0;
        $total    = 0;
        foreach ($data['tools'] as $row) {
            if (!isset($row['url'], $row['identity_key'])) {
                continue;
            }
            $total++;
            if (bv_aec_normalize_url($row['url']) !== $row['identity_key']) {
                $mismatch++;
            }
        }
        $this->assert('G4: 475 rader lest', $total === 475, "lest $total (forventet 475)");
        $this->assert('G4: 0 paritetsbrudd (normalize===identity_key)', $mismatch === 0, "$mismatch avvik");
    }

    /** Tool_Source-validering mot committede testfixturer. */
    private function group_tool_source() {
        $dir = defined('BIM_VERDI_CORE_PLUGIN_DIR') ? BIM_VERDI_CORE_PLUGIN_DIR . 'data/test/' : '';

        $valid = BV_AIHUB_Tool_Source::read_fixture($dir . 'valid.json');
        $this->assert('Tool_Source: valid.json ok=true', !empty($valid['ok']), $valid['error'] ?? '');

        $miss = BV_AIHUB_Tool_Source::read_fixture($dir . 'missing-url.json');
        $this->assert('Tool_Source: missing-url.json → abort (ok=false)', empty($miss['ok']), 'forventet abort');

        $mal = BV_AIHUB_Tool_Source::read_fixture($dir . 'malformed.json');
        $this->assert('Tool_Source: malformed.json → abort (ok=false)', empty($mal['ok']), 'forventet abort');

        $dup = BV_AIHUB_Tool_Source::read_fixture($dir . 'dup-url.json');
        $this->assert('Tool_Source: dup-url.json ok=true (dup = warning, ikke abort)', !empty($dup['ok']), $dup['error'] ?? '');
        $this->assert('Tool_Source: dup-url.json rapporterer dup-warnings', !empty($dup['warnings']['dup_identity_keys']), 'manglet dup-warning');

        $mix = BV_AIHUB_Tool_Source::read_fixture($dir . 'mixed-case-path.json');
        $this->assert('Tool_Source: mixed-case-path.json ok=true (path-case bevart)', !empty($mix['ok']), $mix['error'] ?? '');
    }

    /** Full dry-run mot ekte fixture (ingen skriv). */
    private function group_dryrun_full() {
        BV_AIHUB_Sync::clear_lock();
        $s = BV_AIHUB_Sync::run(true);
        $c = $s['counts'];
        $this->assert('Dry-run: ok=true', !empty($s['ok']), $s['error'] ?? '');
        $this->assert('Dry-run: fetched_total=475', $c['fetched_total'] === 475, (string) $c['fetched_total']);
        $this->assert('Dry-run: champions=238', $c['champions'] === 238, (string) $c['champions']);
        $this->assert('Dry-run: unique_champions=236', $c['unique_champions'] === 236, (string) $c['unique_champions']);
        $this->assert('Dry-run: dedup_dropped=2', $c['dedup_dropped'] === 2, (string) $c['dedup_dropped']);
        // DB-tilstand-uavhengig: hvert unikt champion er enten insert (ren DB) eller update
        // (allerede importert) — summen skal alltid være 236 uansett om importen er kjørt.
        $this->assert('Dry-run: inserted+updated=236', ($c['inserted'] + $c['updated']) === 236, $c['inserted'] . '+' . $c['updated']);
        $this->assert('Dry-run: unmapped=50 (Bårds matrise + Structural Design→ProsjektBIM)', $c['unmapped'] === 50, (string) $c['unmapped']);

        $keys = array_map(function ($w) {
            return $w['key'];
        }, $s['warnings']['dedup']);
        $this->assert('Dry-run: dedup-warning kun youai.ai', $keys === array('youai.ai'), implode(',', $keys));
    }

    /** Champion-filter + dedup-collapse på dup-url.json (reelle kolliderende nøkler). */
    private function group_dedup_unit() {
        $dir  = defined('BIM_VERDI_CORE_PLUGIN_DIR') ? BIM_VERDI_CORE_PLUGIN_DIR . 'data/test/' : '';
        $dup  = BV_AIHUB_Tool_Source::read_fixture($dir . 'dup-url.json');
        $dd   = BV_AIHUB_Tool_Upserter::champion_filter_and_dedup($dup['tools']);
        $keys = array_map(function ($w) {
            return $w['key'];
        }, $dd['warnings']);
        $this->assert('Dedup: dup-url → 2 unike champions', count($dd['tools']) === 2, (string) count($dd['tools']));
        $this->assert('Dedup: dropped=2', $dd['dropped'] === 2, (string) $dd['dropped']);
        $this->assert('Dedup: warning for youai.ai (navnekonflikt)', in_array('youai.ai', $keys, true), implode(',', $keys));
        $this->assert('Dedup: INGEN warning for superhuman.com (identisk)', !in_array('superhuman.com', $keys, true), implode(',', $keys));
    }

    /** Real-write: idempotens, AI-flagg '1'/'0', mapping, deltaker-vakt, sanitering. */
    private function group_upsert_idempotency() {
        BV_AIHUB_Category_Mapper::ensure_unmapped_term();

        $A = $this->row('a', 'ZZSELFTEST Alpha', array('Design Creation'), true, 'lang <b>ok</b> <script>x</script>');
        $B = $this->row('b', 'ZZSELFTEST Beta', array('PropTech'), false);
        $C = $this->row('c', 'ZZSELFTEST Gamma', array('Assistant'), true);

        // Deltaker-vakt: pre-seed deltaker-verktøy med SAMME url som A, uten managed-markør.
        $delt = wp_insert_post(array('post_type' => 'verktoy', 'post_title' => 'ZZSELFTEST Alpha', 'post_status' => 'publish'), true);
        if (!is_wp_error($delt)) {
            $this->created[] = $delt;
            update_post_meta($delt, 'verktoy_lenke', $A['url']);
        }
        $delt_mod  = get_post_field('post_modified_gmt', $delt);
        $delt_hash = md5(serialize(get_post_meta($delt)));

        $rA = BV_AIHUB_Tool_Upserter::upsert_one($A, false);
        $rB = BV_AIHUB_Tool_Upserter::upsert_one($B, false);
        $rC = BV_AIHUB_Tool_Upserter::upsert_one($C, false);
        foreach (array($rA, $rB, $rC) as $r) {
            if (!empty($r['post_id'])) {
                $this->created[] = $r['post_id'];
            }
        }

        $this->assert('Upsert: A/B/C INSERT', $rA['action'] === 'insert' && $rB['action'] === 'insert' && $rC['action'] === 'insert', '');
        $this->assert('Deltaker-vakt: managed-post ≠ deltaker-post', $rA['post_id'] !== $delt, '');

        clean_post_cache($delt);
        $this->assert('Deltaker-vakt: post_modified uendret', get_post_field('post_modified_gmt', $delt) === $delt_mod, '');
        $this->assert('Deltaker-vakt: meta-hash uendret', md5(serialize(get_post_meta($delt))) === $delt_hash, '');
        $this->assert('Deltaker-vakt: _bv_aec_ai_driven-nøkkel finnes ALDRI', metadata_exists('post', $delt, '_bv_aec_ai_driven') === false, '');
        $this->assert('Deltaker-vakt: _bv_aec_managed finnes ALDRI', metadata_exists('post', $delt, '_bv_aec_managed') === false, '');

        $this->assert('AI-flagg: A ai_driven=true → "1"', (string) get_post_meta($rA['post_id'], '_bv_aec_ai_driven', true) === '1', '');
        $this->assert('AI-flagg: B ai_driven=false → "0"', (string) get_post_meta($rB['post_id'], '_bv_aec_ai_driven', true) === '0', '');
        $this->assert('Insert: A status=draft', get_post_status($rA['post_id']) === 'draft', '');
        $this->assert('Insert: A last_sync_status=draft', (string) get_post_meta($rA['post_id'], '_bv_aec_last_sync_status', true) === 'draft', '');
        $this->assert('Insert: A kilde=aec_ai_hub', (string) get_post_meta($rA['post_id'], 'kilde', true) === 'aec_ai_hub', '');
        $this->assert('Sanitering: A detaljert_beskrivelse uten <script>', strpos((string) get_post_meta($rA['post_id'], 'detaljert_beskrivelse', true), '<script>') === false, '');

        $tA = wp_get_post_terms($rA['post_id'], 'temagruppe', array('fields' => 'names'));
        $tC = wp_get_post_terms($rC['post_id'], 'temagruppe', array('fields' => 'names'));
        $this->assert('Mapping: Design Creation → ProsjektBIM', $tA === array('ProsjektBIM'), implode(',', $tA));
        $this->assert('Mapping: Assistant → ' . BV_AIHUB_Category_Mapper::UNMAPPED_TERM . ' + unmapped', $tC === array(BV_AIHUB_Category_Mapper::UNMAPPED_TERM) && (string) get_post_meta($rC['post_id'], '_bv_unmapped', true) === '1', implode(',', $tC));

        // Idempotens: 2. kjøring → kun update, identisk snapshot.
        $snapA = $this->snapshot($rA['post_id']);
        $rA2   = BV_AIHUB_Tool_Upserter::upsert_one($A, false);
        $this->assert('Idempotens: A 2.kjøring=update (ikke insert)', $rA2['action'] === 'update' && $rA2['post_id'] === $rA['post_id'], $rA2['action']);
        $this->assert('Idempotens: A snapshot identisk', $this->snapshot($rA['post_id']) === $snapA, '');

        $multi = false;
        BV_AIHUB_Tool_Upserter::find_managed_post_id($A['identity_key'], $multi);
        $this->assert('Idempotens: ingen dup managed-post for A', $multi === false, 'multi=true');
    }

    /** Orphan-livssyklus, status-divergens, re-entry, manuell override (produksjonstrygt present-sett). */
    private function group_orphan_lifecycle() {
        $A = $this->row('orph-a', 'ZZSELFTEST Orphan A', array('Design Creation'), true);
        $B = $this->row('orph-b', 'ZZSELFTEST Orphan B', array('PropTech'), true);
        $C = $this->row('orph-c', 'ZZSELFTEST Orphan C', array('Surveying'), true);
        $rA = BV_AIHUB_Tool_Upserter::upsert_one($A, false);
        $rB = BV_AIHUB_Tool_Upserter::upsert_one($B, false);
        $rC = BV_AIHUB_Tool_Upserter::upsert_one($C, false);
        foreach (array($rA, $rB, $rC) as $r) {
            if (!empty($r['post_id'])) {
                $this->created[] = $r['post_id'];
            }
        }

        // present = ALLE managed source_keys (inkl. evt. ekte import) minus C → kun C orphanes.
        $present = $this->all_managed_keys_except(array($C['identity_key']));
        $rec1    = BV_AIHUB_Tool_Upserter::reconcile_orphans($present, false);
        $this->assert('Orphan: C markert _bv_orphaned=1', (string) get_post_meta($rC['post_id'], '_bv_orphaned', true) === '1', '');
        $this->assert('Orphan: C forblir draft (sync angrer egen handling)', get_post_status($rC['post_id']) === 'draft', '');
        $this->assert('Orphan: A urørt (fortsatt i settet)', (string) get_post_meta($rA['post_id'], '_bv_orphaned', true) === '0', '');

        // Status-divergens: menneske-publiser B; orphan B → flagg, IKKE avpubliser.
        wp_update_post(array('ID' => $rB['post_id'], 'post_status' => 'publish'));
        $present2 = $this->all_managed_keys_except(array($B['identity_key']));
        $rec2     = BV_AIHUB_Tool_Upserter::reconcile_orphans($present2, false);
        $flaggedB = false;
        foreach ($rec2['flagged_divergent'] as $fd) {
            if ((int) $fd['post_id'] === (int) $rB['post_id']) {
                $flaggedB = true;
            }
        }
        $this->assert('Status-divergens: B flagget', $flaggedB, '');
        $this->assert('Status-divergens: B forblir publish (ikke auto-avpublisert)', get_post_status($rB['post_id']) === 'publish', '');

        // Re-entry: C tilbake → orphan-flagg tømmes.
        $rC2 = BV_AIHUB_Tool_Upserter::upsert_one($C, false);
        $this->assert('Re-entry: C=update, _bv_orphaned tømt', $rC2['action'] === 'update' && (string) get_post_meta($rC['post_id'], '_bv_orphaned', true) === '0', '');

        // Manuell override fryser orphan.
        update_post_meta($rA['post_id'], '_bv_aec_manual_override', '1');
        $present3 = $this->all_managed_keys_except(array($A['identity_key']));
        $rec3     = BV_AIHUB_Tool_Upserter::reconcile_orphans($present3, false);
        $this->assert('Override: A hoppes over (skipped_override≥1)', $rec3['skipped_override'] >= 1, (string) $rec3['skipped_override']);
        $this->assert('Override: A IKKE orphan-markert', (string) get_post_meta($rA['post_id'], '_bv_orphaned', true) === '0', '');
    }

    /** Floor-vakt: validert sett < 50% av forrige → upsert ja, orphan-purge NEI. */
    private function group_floor() {
        $orig_exists = (get_option(BV_AIHUB_Sync::OPTION_LAST_COUNT, '__none__') !== '__none__');
        $orig        = get_option(BV_AIHUB_Sync::OPTION_LAST_COUNT);
        update_option(BV_AIHUB_Sync::OPTION_LAST_COUNT, 100, false);

        $rows = array(
            $this->row('floor-1', 'ZZSELFTEST Floor 1', array('Design Creation'), true),
            $this->row('floor-2', 'ZZSELFTEST Floor 2', array('PropTech'), false),
            $this->row('floor-3', 'ZZSELFTEST Floor 3', array('Surveying'), true),
        );
        $injected = array(
            'ok' => true, 'error' => null, 'source' => 'selftest', 'meta' => array(),
            'tools' => $rows, 'counts' => array(), 'warnings' => array(),
        );
        $inject = function () use ($injected) {
            return $injected;
        };
        add_filter('bimverdi_aihub_fetch_result', $inject);
        BV_AIHUB_Sync::clear_lock();
        $s = BV_AIHUB_Sync::run(false);
        remove_filter('bimverdi_aihub_fetch_result', $inject);

        // Rydd opp de 3 floor-postene.
        foreach ($rows as $r) {
            $multi = false;
            $pid   = BV_AIHUB_Tool_Upserter::find_managed_post_id($r['identity_key'], $multi);
            if ($pid) {
                $this->created[] = $pid;
            }
        }

        $this->assert('Floor: trigget (3 < 50% av 100)', !empty($s['floor']), 'floor=' . var_export($s['floor'] ?? null, true));
        $this->assert('Floor: orphaned=0 (purge hoppet over)', ($s['counts']['orphaned'] ?? -1) === 0, (string) ($s['counts']['orphaned'] ?? -1));
        $this->assert('Floor: inserted=3 (upsert kjørte likevel)', ($s['counts']['inserted'] ?? -1) === 3, (string) ($s['counts']['inserted'] ?? -1));

        // Floor-kjøring skal IKKE persistere antall (option uendret = 100).
        $this->assert('Floor: antall ikke persistert (option=100)', (int) get_option(BV_AIHUB_Sync::OPTION_LAST_COUNT) === 100, '');

        // Gjenopprett original option-tilstand.
        if ($orig_exists) {
            update_option(BV_AIHUB_Sync::OPTION_LAST_COUNT, $orig, false);
        } else {
            delete_option(BV_AIHUB_Sync::OPTION_LAST_COUNT);
        }
    }

    /** Fase-1-abort: injisert ok=false → aborted, ingen skriv. */
    private function group_abort() {
        $before = count($this->all_managed_keys_except(array()));
        $inject = function () {
            return array('ok' => false, 'error' => 'selftest-injisert abort', 'meta' => array(), 'tools' => array(), 'counts' => array(), 'warnings' => array());
        };
        add_filter('bimverdi_aihub_fetch_result', $inject);
        BV_AIHUB_Sync::clear_lock();
        $s = BV_AIHUB_Sync::run(false);
        remove_filter('bimverdi_aihub_fetch_result', $inject);
        $after = count($this->all_managed_keys_except(array()));

        $this->assert('Abort: aborted=true, ok=false', !empty($s['aborted']) && empty($s['ok']), '');
        $this->assert('Abort: ingen managed-poster opprettet', $before === $after, "$before → $after");
    }

    // ── Hjelpere ─────────────────────────────────────────────────────────

    /** Bygg en syntetisk champion-rad med deterministisk source_key. */
    private function row($suffix, $name, array $categories, $ai_driven, $long_desc = 'lang') {
        $key = self::PREFIX . $suffix . '.test';
        return array(
            'identity_key' => $key,
            'name'         => $name,
            'short_desc'   => 'kort',
            'long_desc'    => $long_desc,
            'url'          => 'https://' . $key . '/',
            'logo_url'     => '',
            'categories'   => $categories,
            'champion'     => true,
            'ai_driven'    => (bool) $ai_driven,
        );
    }

    /** Snapshot av managed felt + termer (ekskl. flyktige felt) for idempotens-sammenligning. */
    private function snapshot($id) {
        $m = get_post_meta($id);
        unset($m['_bv_aec_synced_at']);
        $t = wp_get_post_terms($id, 'temagruppe', array('fields' => 'ids'));
        sort($t);
        return md5(serialize($m) . serialize($t) . get_post_field('post_title', $id) . get_post_status($id));
    }

    /** Alle managed source_keys i DB, minus de oppgitte (produksjonstrygt present-sett). */
    private function all_managed_keys_except(array $exclude) {
        $q = new WP_Query(array(
            'post_type'      => defined('BV_CPT_TOOL') ? BV_CPT_TOOL : 'verktoy',
            'post_status'    => array('draft', 'pending', 'publish', 'future', 'private'),
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => array(array('key' => '_bv_aec_managed', 'value' => '1')),
        ));
        $keys = array();
        foreach ($q->posts as $pid) {
            $sk = (string) get_post_meta($pid, '_bv_aec_source_key', true);
            if ($sk !== '' && !in_array($sk, $exclude, true)) {
                $keys[] = $sk;
            }
        }
        return $keys;
    }

    private function assert($name, $cond, $detail = '') {
        $this->results[] = array('name' => $name, 'ok' => (bool) $cond, 'detail' => (string) $detail);
    }

    /** Force-delete alle syntetiske poster laget under kjøringen. */
    private function teardown() {
        foreach (array_unique($this->created) as $id) {
            if ($id && get_post($id)) {
                wp_delete_post($id, true);
            }
        }
        BV_AIHUB_Sync::clear_lock();
    }
}
