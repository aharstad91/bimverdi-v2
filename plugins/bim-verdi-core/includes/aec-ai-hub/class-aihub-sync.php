<?php
/**
 * AEC AI Hub — Sync (orkestrator: mutex → to-fase fetch → upsert → reconcile).
 *
 * Binder Tool_Source (Fase 1) og Tool_Upserter (Fase 2) sammen under én
 * re-entrans-sikker `run()`. Skriver INGENTING i dry-run. Returnerer en flat
 * stats-kontrakt som CLI (Unit 6) og adminrapport (Unit 8) rendrer.
 *
 * Sekvens:
 *   1. Mutex (transient) — bail-and-log hvis holdt (CLI + selftest + framtidig cron kan overlappe).
 *   2. FASE 1: hent + valider HELE settet; hard feil (fil/JSON/struktur/G4-paritet) → abort, rør ingenting.
 *   3. Champion-filter (238) + dedup-collapse (236) i upserteren.
 *   4. FLOOR-vakt: validert sett < 50% av forrige vellykkede antall → upsert ja, orphan-purge NEI.
 *   5. FASE 2: upsert hver rad (idempotent, deltaker-trygg, draft).
 *   6. Orphan-rekonsiliering (kun hvis ikke floor).
 *   7. Persistér antall (kun ekte, ikke-floor kjøring) for neste floor-beregning.
 *
 * @package BIMVerdiCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class BV_AIHUB_Sync {

    /** Transient-mutex-nøkkel. */
    const LOCK_KEY = 'bv_aihub_sync_lock';

    /** Mutex-TTL i sekunder (selvhelende hvis en kjøring krasjer uten å frigjøre). */
    const LOCK_TTL = 600;

    /** Option som lagrer forrige vellykkede unike-champion-antall (floor-kalibrering, mot 238/236). */
    const OPTION_LAST_COUNT = 'bv_aihub_last_synced_count';

    /** Floor-terskel: validert sett under denne andelen av forrige antall → hopp over orphan-purge. */
    const FLOOR_RATIO = 0.5;

    /**
     * Kjør synken.
     *
     * @param bool $dry_run Beregn alt uten å skrive (insert/update/term/status/orphan/option).
     * @return array Stats-kontrakt (se base_stats()).
     */
    public static function run($dry_run = false) {
        $stats = self::base_stats($dry_run);

        // 1) Mutex.
        if (get_transient(self::LOCK_KEY)) {
            $stats['mutex_bailed'] = true;
            $stats['error']        = 'Synk allerede i gang (mutex holdt) — avbryter.';
            error_log('[BV_AIHUB] ' . $stats['error']);
            return $stats;
        }
        set_transient(self::LOCK_KEY, time(), self::LOCK_TTL);

        try {
            // 2) FASE 1 — hent + valider HELE settet.
            $fetch = BV_AIHUB_Tool_Source::fetch_tools();
            /**
             * Test-/utvidelses-seam: la selftesten (eller Trinn 2-kilder) injisere et alternativt
             * fetch-resultat. Default = passthrough. Brukes til å øve floor- og abort-grenene
             * deterministisk uten å røre den ekte 475-fixturen.
             *
             * @param array $fetch Resultatkontrakt fra Tool_Source.
             */
            $fetch                = apply_filters('bimverdi_aihub_fetch_result', $fetch);
            $stats['source_meta'] = isset($fetch['meta']) ? $fetch['meta'] : array();

            if (empty($fetch['ok'])) {
                $stats['aborted'] = true;
                $stats['error']   = isset($fetch['error']) ? $fetch['error'] : 'Fase 1-abort (ukjent grunn)';
                error_log('[BV_AIHUB] Fase 1-abort → ingen poster røres: ' . $stats['error']);
                return $stats; // finally frigjør låsen
            }
            if (!empty($fetch['warnings'])) {
                $stats['warnings']['fetch'] = $fetch['warnings'];
            }

            // 3) Champion-filter + dedup-collapse.
            $dd        = BV_AIHUB_Tool_Upserter::champion_filter_and_dedup($fetch['tools']);
            $champions = $dd['tools'];

            $stats['warnings']['dedup']             = $dd['warnings'];
            $stats['counts']['fetched_total']       = count($fetch['tools']);
            $stats['counts']['champions']           = count(array_filter($fetch['tools'], function ($t) {
                return !empty($t['champion']);
            }));
            $stats['counts']['unique_champions']    = count($champions);
            $stats['counts']['dedup_dropped']       = $dd['dropped'];

            // 4) FLOOR-vakt.
            $prev = (int) get_option(self::OPTION_LAST_COUNT, 0);
            if ($prev > 0 && count($champions) < (self::FLOOR_RATIO * $prev)) {
                $stats['floor'] = true;
                error_log(sprintf(
                    '[BV_AIHUB] FLOOR trigget: %d unike champions < %d%% av forrige %d — hopper over orphan-rekonsiliering (mistenkt trunkert kilde).',
                    count($champions),
                    (int) (self::FLOOR_RATIO * 100),
                    $prev
                ));
            }

            // Sørg for at «Ukategorisert»-holdetermen finnes (idempotent, sen hekting).
            if (!$dry_run) {
                BV_AIHUB_Category_Mapper::ensure_unmapped_term();
            }

            // 5) FASE 2 — upsert.
            $present_keys = array();
            foreach ($champions as $row) {
                $r = BV_AIHUB_Tool_Upserter::upsert_one($row, $dry_run);

                if ($r['source_key'] !== '') {
                    $present_keys[] = $r['source_key'];
                }
                switch ($r['action']) {
                    case 'insert':
                        $stats['counts']['inserted']++;
                        break;
                    case 'update':
                        $stats['counts']['updated']++;
                        break;
                    default:
                        $stats['counts']['skipped']++;
                        break;
                }
                if (!empty($r['unmapped'])) {
                    $stats['counts']['unmapped']++;
                }
                foreach ($r['warnings'] as $w) {
                    $stats['warnings']['rows'][] = array('key' => $r['source_key'], 'msg' => $w);
                }
            }

            // 6) Orphan-rekonsiliering (kun hvis ikke floor).
            if (!$stats['floor']) {
                $rec = BV_AIHUB_Tool_Upserter::reconcile_orphans($present_keys, $dry_run);
                $stats['counts']['orphaned']                = $rec['orphaned'];
                $stats['counts']['orphan_skipped_override'] = $rec['skipped_override'];
                $stats['warnings']['status_divergence']     = $rec['flagged_divergent'];
            }

            // 7) Persistér antall (kun ekte, ikke-floor kjøring).
            if (!$dry_run && !$stats['floor']) {
                update_option(self::OPTION_LAST_COUNT, count($champions), false);
            }

            $stats['ok'] = true;
            return $stats;
        } catch (\Throwable $e) {
            $stats['aborted'] = true;
            $stats['error']   = 'Unntak under synk: ' . $e->getMessage();
            error_log('[BV_AIHUB] ' . $stats['error']);
            return $stats;
        } finally {
            delete_transient(self::LOCK_KEY);
        }
    }

    /**
     * Tøm en evt. fastlåst mutex (selftest/feilsøk). Vanlig drift trenger ikke dette —
     * TTL-en selvheler — men en eksplisitt frigjører er nyttig før en assertert testkjøring.
     */
    public static function clear_lock() {
        delete_transient(self::LOCK_KEY);
    }

    /**
     * Tom stats-kontrakt.
     *
     * @param bool $dry_run
     * @return array
     */
    private static function base_stats($dry_run) {
        return array(
            'ok'           => false,
            'error'        => null,
            'dry_run'      => (bool) $dry_run,
            'aborted'      => false,
            'mutex_bailed' => false,
            'floor'        => false,
            'counts'       => array(
                'fetched_total'           => 0,
                'champions'               => 0,
                'unique_champions'        => 0,
                'dedup_dropped'           => 0,
                'inserted'                => 0,
                'updated'                 => 0,
                'skipped'                 => 0,
                'unmapped'                => 0,
                'orphaned'                => 0,
                'orphan_skipped_override' => 0,
            ),
            'warnings'     => array(
                'fetch'             => array(),
                'dedup'             => array(),
                'rows'              => array(),
                'status_divergence' => array(),
            ),
            'source_meta'  => array(),
        );
    }
}
