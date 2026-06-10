<?php
/**
 * AEC AI Hub — Tool_Upserter (managed-markør-vaktet, URL-keyet upsert).
 *
 * FASE 2 av to-fase-kontrakten (Decision 7): kjører KUN etter at Tool_Source
 * (Fase 1) har validert HELE settet. Ansvar:
 *   - Champion-filter (238 av 475) + deterministisk dedup-collapse på source_key
 *     (236 unike — youai/superhuman kolliderer; dronedeploy gjør IKKE, se Decision 7).
 *   - Idempotent upsert mot `_bv_aec_source_key` (normalisert URL, host-lowercase/path-bevart).
 *   - Eierskaps-invariant: enhver UPDATE/term/status-operasjon krever `_bv_aec_managed=1`
 *     OG `post_type=verktoy`. Et deltaker-verktøy uten markøren røres ALDRI (Decision 3).
 *   - AI-flagg lagret eksplisitt som string `'1'`/`'0'` på ALLE managed-poster (Decision 4/R12).
 *   - Sanitering ved inntak; logo-URL host-allowlist (Decision 11 — i praksis tom for alle 236).
 *   - Livssyklus: INSERT = draft (AUTOPUBLISH alltid false i Trinn 1); UPDATE rører ALDRI
 *     `post_status` (Decision 8); orphan = soft-unpublish KUN av sync sin egen handling.
 *
 * Eierskaps-identiteter (Decision 10) er KONFIGURERBARE, ikke auto-opprettede:
 *   - `post_author` → BV_AIHUB_AUTHOR_ID (konstant/filter) | innloggende admin | første admin.
 *   - `eier_leverandor` (ACF post_object→foretak) → BV_AIHUB_OWNER_FORETAK_ID (konstant/filter).
 *     Settes KUN hvis den peker på et ekte `foretak`; ellers står feltet tomt og attribusjonen
 *     bæres av `_bv_aec_source` (Unit 7). Upserteren oppretter ALDRI et foretak/bruker selv —
 *     det ville injisert en ikke-medlems-bedrift i medlemskatalogen (blast-radius).
 *
 * @package BIMVerdiCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class BV_AIHUB_Tool_Upserter {

    /** Attribusjonsetikett (Decision 3) — driver `_bv_aec_source`, attribusjon + Kilde-filter. */
    const SOURCE_LABEL = 'AEC AI Hub by Stjepan Mikulić / aiinaec.com';

    /** ACF `kilde`-select-verdi for synkede verktøy. */
    const KILDE_VALUE = 'aec_ai_hub';

    /** Statuser som teller som «eksisterende managed post» ved oppslag (alt unntatt trash/auto-draft). */
    private static function lookup_statuses() {
        return array('draft', 'pending', 'publish', 'future', 'private');
    }

    private static function tool_cpt() {
        return defined('BV_CPT_TOOL') ? BV_CPT_TOOL : 'verktoy';
    }

    /**
     * Champion-filter (behold `champion=true`) + deterministisk dedup-collapse på source_key.
     *
     * Dedup-regel (Decision 7), ETTER champion-filter:
     *   - gruppestørrelse 1 → uendret.
     *   - identiske rader (superhuman) → stille tap-fri collapse, INGEN warning.
     *   - navnekonflikt (youai) → behold lengste navn (deterministisk), OR-union kategorier,
     *     OR ai_driven, og LOGG warning (redaksjonell avgjørelse — kan overstyres).
     *   - dronedeploy kolliderer IKKE etter filter (kun én Champion-rad) → ingen collapse.
     *
     * @param array $tools Alle validerte rader fra Tool_Source (475).
     * @return array{tools:array,warnings:array,dropped:int} unike champions (236) + dedup-warnings.
     */
    public static function champion_filter_and_dedup(array $tools) {
        $groups = array();
        foreach ($tools as $t) {
            if (empty($t['champion'])) {
                continue; // ikke-Champion → hopp over (237 av 475)
            }
            $key = isset($t['identity_key']) ? (string) $t['identity_key'] : '';
            if ($key === '') {
                continue;
            }
            $groups[$key][] = $t;
        }

        $unique   = array();
        $warnings = array();
        $dropped  = 0;

        foreach ($groups as $key => $rows) {
            if (count($rows) === 1) {
                $unique[] = $rows[0];
                continue;
            }

            $dropped += count($rows) - 1;

            // Vinner = lengste navn (deterministisk; stabil tie-break = første forekomst).
            $winner = $rows[0];
            foreach ($rows as $r) {
                if (mb_strlen((string) $r['name']) > mb_strlen((string) $winner['name'])) {
                    $winner = $r;
                }
            }

            $cats  = array();
            $ai    = false;
            $names = array();
            foreach ($rows as $r) {
                foreach ((array) $r['categories'] as $c) {
                    if (!in_array($c, $cats, true)) {
                        $cats[] = $c;
                    }
                }
                $ai      = $ai || !empty($r['ai_driven']);
                $names[] = (string) $r['name'];
            }

            $merged               = $winner;
            $merged['categories'] = $cats;
            $merged['ai_driven']  = $ai;
            $merged['champion']   = true;
            $unique[]             = $merged;

            // Warning KUN ved reell navnekonflikt (youai) — identiske rader (superhuman) er stille.
            if (count(array_unique($names)) > 1) {
                $warnings[] = array(
                    'key'       => $key,
                    'kept_name' => (string) $winner['name'],
                    'all_names' => $names,
                    'count'     => count($rows),
                );
                error_log(sprintf(
                    '[BV_AIHUB] dedup navnekonflikt på %s: beholdt «%s» av {%s}',
                    $key,
                    $winner['name'],
                    implode(', ', $names)
                ));
            }
        }

        return array('tools' => $unique, 'warnings' => $warnings, 'dropped' => $dropped);
    }

    /**
     * Finn ID-en til en eksisterende managed verktøy-post på source_key.
     * Krever BÅDE `_bv_aec_source_key`-treff OG `_bv_aec_managed=1` (eierskaps-vakt).
     *
     * @param string $source_key Normalisert URL.
     * @param bool   $multi      (out) settes true hvis >1 managed post deler nøkkelen (datafeil).
     * @return int Post-ID, eller 0.
     */
    public static function find_managed_post_id($source_key, &$multi = false) {
        $multi = false;
        if ($source_key === '') {
            return 0;
        }
        $q = new WP_Query(array(
            'post_type'      => self::tool_cpt(),
            'post_status'    => self::lookup_statuses(),
            'posts_per_page' => 2,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => array(
                'relation' => 'AND',
                array('key' => '_bv_aec_source_key', 'value' => $source_key),
                array('key' => '_bv_aec_managed', 'value' => '1'),
            ),
        ));
        $ids   = $q->posts;
        $multi = count($ids) > 1;
        return !empty($ids) ? (int) $ids[0] : 0;
    }

    /**
     * Svak sekundær reconciliation-HINT: finn en managed post med samme name_key men ANNEN
     * source_key (mulig URL-endring). Brukes ALDRI som hard merge-nøkkel (Decision 4) — kun flagg.
     *
     * @param string $name_key
     * @param string $exclude_source_key
     * @return int Post-ID eller 0.
     */
    private static function find_managed_by_name_key($name_key, $exclude_source_key) {
        if ($name_key === '') {
            return 0;
        }
        $q = new WP_Query(array(
            'post_type'      => self::tool_cpt(),
            'post_status'    => self::lookup_statuses(),
            'posts_per_page' => 5,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => array(
                'relation' => 'AND',
                array('key' => '_bv_aec_name_key', 'value' => $name_key),
                array('key' => '_bv_aec_managed', 'value' => '1'),
            ),
        ));
        foreach ($q->posts as $pid) {
            if ((string) get_post_meta($pid, '_bv_aec_source_key', true) !== $exclude_source_key) {
                return (int) $pid;
            }
        }
        return 0;
    }

    /**
     * Upsert én champion-rad. Idempotent på source_key. Skriver IKKE i dry-run.
     *
     * @param array $row     Normalisert (deduplisert) champion-rad.
     * @param bool  $dry_run Beregn handling uten å skrive.
     * @return array{action:string,post_id:int,unmapped:bool,source_key:string,warnings:string[]}
     */
    public static function upsert_one(array $row, $dry_run = false) {
        $result = array(
            'action'     => 'skip',
            'post_id'    => 0,
            'unmapped'   => false,
            'source_key' => '',
            'warnings'   => array(),
        );

        $name    = isset($row['name']) ? (string) $row['name'] : '';
        $raw_url = isset($row['url']) ? (string) $row['url'] : '';

        // Manglende påkrevd felt → skip (Tool_Source aborterer egentlig før dette; defensivt).
        if (trim($name) === '' || trim($raw_url) === '') {
            $result['warnings'][] = 'skip: mangler navn eller url';
            return $result;
        }

        $source_key = (isset($row['identity_key']) && $row['identity_key'] !== '')
            ? (string) $row['identity_key']
            : (function_exists('bv_aec_normalize_url') ? bv_aec_normalize_url($raw_url) : '');
        if ($source_key === '') {
            $result['warnings'][] = 'skip: kunne ikke utlede source_key';
            return $result;
        }
        $result['source_key'] = $source_key;

        // Kategori → temagruppe-termer (umappbar → «Ukategorisert» + unmapped=true).
        $cats = isset($row['categories']) ? $row['categories'] : array();
        $map  = BV_AIHUB_Category_Mapper::map_tool_categories($cats);
        $result['unmapped'] = !empty($map['unmapped']);
        $term_names         = $map['term_names'];

        // Saniter ved inntak (Decision 11).
        $title    = sanitize_text_field($name);
        $kort     = sanitize_text_field(isset($row['short_desc']) ? $row['short_desc'] : '');
        $lang     = wp_kses_post(isset($row['long_desc']) ? $row['long_desc'] : '');
        $lenke    = esc_url_raw($raw_url);
        $logo     = self::sanitize_logo_url(isset($row['logo_url']) ? $row['logo_url'] : '');
        $ai_flag  = !empty($row['ai_driven']) ? '1' : '0';
        $name_key = function_exists('bv_aec_name_key') ? bv_aec_name_key($name) : '';
        $raw_cat  = self::raw_category_str(isset($map['raw_categories']) ? $map['raw_categories'] : array());

        $multi   = false;
        $post_id = self::find_managed_post_id($source_key, $multi);
        if ($multi) {
            $result['warnings'][] = sprintf('multi-match: >1 managed post for source_key «%s» — bruker første', $source_key);
            error_log('[BV_AIHUB] multi-match (datafeil) for source_key ' . $source_key);
        }

        if ($dry_run) {
            $result['action']  = $post_id ? 'update' : 'insert';
            $result['post_id'] = $post_id;
            return $result;
        }

        if ($post_id) {
            // ── UPDATE: kun managed innholdsfelt + per-kjøring-flagg. ALDRI post_status (Decision 8).
            $existing = get_post($post_id);
            if ($existing && $existing->post_title !== $title) {
                // Omdøp: oppdater tittel uten å sende post_status (bevares).
                wp_update_post(array('ID' => $post_id, 'post_title' => $title));
            }
            self::write_content_fields($post_id, $title, $kort, $lang, $lenke, $logo, $term_names);

            update_post_meta($post_id, '_bv_aec_ai_driven', $ai_flag);
            update_post_meta($post_id, '_bv_aec_source', self::SOURCE_LABEL);
            update_post_meta($post_id, '_bv_aec_name_key', $name_key);
            update_post_meta($post_id, '_bv_aec_canonical_url', $raw_url);
            update_post_meta($post_id, '_bv_aec_raw_category', $raw_cat);
            update_post_meta($post_id, '_bv_unmapped', $result['unmapped'] ? '1' : '0');
            update_post_meta($post_id, '_bv_orphaned', '0'); // re-treff: tøm evt. orphan-flagg
            update_post_meta($post_id, '_bv_aec_synced_at', current_time('mysql'));
            // NB: `_bv_aec_last_sync_status` settes ALDRI på update (kun ved insert) — ellers ville
            // synken «adoptere» en menneske-publisering og senere kunne avpublisere den ved orphan.
            self::set_kilde($post_id);

            $result['action']  = 'update';
            $result['post_id'] = $post_id;
            return $result;
        }

        // ── INSERT: ny managed draft. Svak name_key-hint før vi lager duplikat-mistanke.
        $hint_id = self::find_managed_by_name_key($name_key, $source_key);
        if ($hint_id) {
            $result['warnings'][] = sprintf(
                'name_key-treff (mulig URL-endring) på post %d for «%s» — manuell vurdering, ikke auto-merge',
                $hint_id,
                $name
            );
            error_log(sprintf('[BV_AIHUB] mulig URL-endring: name_key «%s» finnes på post %d (ny source_key %s)', $name_key, $hint_id, $source_key));
        }

        $new_id = wp_insert_post(array(
            'post_type'    => self::tool_cpt(),
            'post_title'   => $title,
            'post_content' => '',
            'post_status'  => 'draft', // AUTOPUBLISH alltid false i Trinn 1 (Decision 6)
            'post_author'  => self::resolve_author_id(),
        ), true);

        if (is_wp_error($new_id) || !$new_id) {
            $msg = is_wp_error($new_id) ? $new_id->get_error_message() : 'ukjent feil';
            $result['warnings'][] = 'insert-feil: ' . $msg;
            error_log('[BV_AIHUB] INSERT feilet for ' . $source_key . ': ' . $msg);
            return $result;
        }

        // Identitet + eierskaps-markør ATOMISK før andre skriv (eierskaps-invariant).
        update_post_meta($new_id, '_bv_aec_managed', '1');
        update_post_meta($new_id, '_bv_aec_source_key', $source_key);
        update_post_meta($new_id, '_bv_aec_canonical_url', $raw_url);
        update_post_meta($new_id, '_bv_aec_name_key', $name_key);
        update_post_meta($new_id, '_bv_aec_ai_driven', $ai_flag);
        update_post_meta($new_id, '_bv_aec_source', self::SOURCE_LABEL);
        update_post_meta($new_id, '_bv_aec_raw_category', $raw_cat);
        update_post_meta($new_id, '_bv_unmapped', $result['unmapped'] ? '1' : '0');
        update_post_meta($new_id, '_bv_orphaned', '0');
        update_post_meta($new_id, '_bv_aec_last_sync_status', 'draft'); // KUN ved insert
        update_post_meta($new_id, '_bv_aec_synced_at', current_time('mysql'));

        // Innholdsfelt + termer + kilde + (valgfri) eier-foretak.
        self::write_content_fields($new_id, $title, $kort, $lang, $lenke, $logo, $term_names);
        self::set_kilde($new_id);
        self::set_owner_foretak($new_id);

        $result['action']  = 'insert';
        $result['post_id'] = $new_id;
        return $result;
    }

    /**
     * Orphan-rekonsiliering: managed-poster hvis source_key IKKE er i settet → soft-unpublish.
     * Kalles KUN når Fase 1 var komplett og floor IKKE er trigget (orkestratoren vokter dette).
     *
     * Status-divergens-vakt (Decision 8): synken setter draft KUN hvis nåværende status ==
     * `_bv_aec_last_sync_status` (angrer kun sin egen handling). Har et menneske endret status
     * (f.eks. bulk-publisert) → la stå + flagg. `_bv_aec_manual_override=1` fryser posten helt.
     * Aldri hard-delete.
     *
     * @param string[] $present_keys source_keys i gjeldende sett.
     * @param bool     $dry_run
     * @return array{orphaned:int,flagged_divergent:array,skipped_override:int}
     */
    public static function reconcile_orphans(array $present_keys, $dry_run = false) {
        $present = array_fill_keys($present_keys, true);
        $out     = array('orphaned' => 0, 'flagged_divergent' => array(), 'skipped_override' => 0);

        $q = new WP_Query(array(
            'post_type'      => self::tool_cpt(),
            'post_status'    => self::lookup_statuses(),
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => array(
                array('key' => '_bv_aec_managed', 'value' => '1'),
            ),
        ));

        foreach ($q->posts as $pid) {
            $sk = (string) get_post_meta($pid, '_bv_aec_source_key', true);
            if ($sk !== '' && isset($present[$sk])) {
                continue; // fortsatt i settet
            }

            // Manuell override fryser posten helt.
            if ((string) get_post_meta($pid, '_bv_aec_manual_override', true) === '1') {
                $out['skipped_override']++;
                continue;
            }

            $out['orphaned']++;
            if ($dry_run) {
                continue;
            }

            update_post_meta($pid, '_bv_orphaned', '1');

            $current = get_post_status($pid);
            $last    = (string) get_post_meta($pid, '_bv_aec_last_sync_status', true);

            if ($last !== '' && $current === $last) {
                // Synk angrer kun egen handling → soft-unpublish til draft.
                if ($current !== 'draft') {
                    wp_update_post(array('ID' => $pid, 'post_status' => 'draft'));
                    update_post_meta($pid, '_bv_aec_last_sync_status', 'draft');
                }
            } else {
                // Menneske har endret status → la stå + flagg (ingen auto-avpublisering).
                $out['flagged_divergent'][] = array(
                    'post_id'   => (int) $pid,
                    'status'    => $current,
                    'last_sync' => $last,
                );
                error_log(sprintf('[BV_AIHUB] orphan med status-divergens på post %d (status=%s, last_sync=%s) — urørt + flagget', $pid, $current, $last));
            }
        }

        return $out;
    }

    // ── Private hjelpere ────────────────────────────────────────────────────

    /**
     * Skriv managed innholdsfelt (ACF med update_post_meta-fallback) + temagruppe-termer + logo-meta.
     * Brukes av både INSERT og UPDATE.
     */
    private static function write_content_fields($post_id, $title, $kort, $lang, $lenke, $logo, array $term_names) {
        if (function_exists('update_field')) {
            update_field('verktoy_navn', $title, $post_id);
            update_field('kort_beskrivelse', $kort, $post_id);
            update_field('detaljert_beskrivelse', $lang, $post_id);
            update_field('verktoy_lenke', $lenke, $post_id);
        } else {
            update_post_meta($post_id, 'verktoy_navn', $title);
            update_post_meta($post_id, 'kort_beskrivelse', $kort);
            update_post_meta($post_id, 'detaljert_beskrivelse', $lang);
            update_post_meta($post_id, 'verktoy_lenke', $lenke);
        }

        // Temagruppe er autoritativ og primær (Decision 5); replace (append=false).
        if (!empty($term_names)) {
            wp_set_object_terms($post_id, $term_names, 'temagruppe', false);
        }

        // Logo: i praksis tom for alle 236. Lagre kun allowlistet HTTPS-URL som meta (Unit 7 rendrer
        // via <img referrerpolicy="no-referrer">); verktoy_logo (attachment-ID) settes IKKE her.
        if ($logo !== '') {
            update_post_meta($post_id, '_bv_aec_logo_url', $logo);
        }
    }

    /** ACF `kilde`-select → 'aec_ai_hub' (read-only i UI, men skrivbar programmatisk). */
    private static function set_kilde($post_id) {
        if (function_exists('update_field')) {
            update_field('kilde', self::KILDE_VALUE, $post_id);
        } else {
            update_post_meta($post_id, 'kilde', self::KILDE_VALUE);
        }
    }

    /**
     * Sett `eier_leverandor` (post_object→foretak) KUN hvis en ekte foretak-ID er konfigurert.
     * Settes kun ved INSERT (ikke på update) for ikke å klobbe en kuratert/menneske-satt eier.
     */
    private static function set_owner_foretak($post_id) {
        $fid = self::resolve_owner_foretak_id();
        if (!$fid) {
            return; // tomt felt; attribusjon bæres av `_bv_aec_source` (Decision 10/11)
        }
        if (function_exists('update_field')) {
            update_field('eier_leverandor', $fid, $post_id);
        } else {
            update_post_meta($post_id, 'eier_leverandor', $fid);
        }
    }

    /**
     * Logo-URL host-allowlist (Decision 11): kun HTTPS + kjent CDN-host. Ellers tom streng.
     *
     * @param mixed $url
     * @return string
     */
    private static function sanitize_logo_url($url) {
        $url = is_string($url) ? trim($url) : '';
        if ($url === '') {
            return '';
        }
        $clean = esc_url_raw($url, array('https'));
        if ($clean === '') {
            return '';
        }
        $host = strtolower((string) wp_parse_url($clean, PHP_URL_HOST));
        if ($host === '') {
            return '';
        }
        foreach (self::logo_host_allowlist() as $allowed) {
            $allowed = strtolower($allowed);
            if ($host === $allowed || substr($host, -strlen('.' . $allowed)) === '.' . $allowed) {
                return $clean;
            }
        }
        return '';
    }

    private static function logo_host_allowlist() {
        return apply_filters('bimverdi_aec_logo_host_allowlist', array(
            'notion.so',
            'amazonaws.com',     // S3 (Notion-filer ligger på prod-files-secure.s3.*.amazonaws.com)
            'notionusercontent.com',
        ));
    }

    /** Saniter + slå sammen rå-kategorier til én streng for `_bv_aec_raw_category` (enkeltverdi-eksport). */
    private static function raw_category_str(array $raw) {
        $raw = array_values(array_filter(array_map('sanitize_text_field', $raw)));
        return implode(', ', $raw);
    }

    /**
     * Resolve `post_author`: BV_AIHUB_AUTHOR_ID (konstant/filter) → innloggende bruker → første admin → 0.
     * Oppretter ALDRI en bruker (Decision 10 «dedikert bruker» wires via konstanten ved behov).
     */
    private static function resolve_author_id() {
        $id = defined('BV_AIHUB_AUTHOR_ID') ? (int) BV_AIHUB_AUTHOR_ID : 0;
        $id = (int) apply_filters('bimverdi_aec_author_id', $id);
        if ($id && get_user_by('id', $id)) {
            return $id;
        }
        $cur = get_current_user_id();
        if ($cur) {
            return (int) $cur;
        }
        $admins = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ID'));
        return !empty($admins) ? (int) $admins[0] : 0;
    }

    /**
     * Resolve `eier_leverandor`: BV_AIHUB_OWNER_FORETAK_ID (konstant/filter) hvis den peker på et
     * ekte `foretak`; ellers 0 (tomt felt). Oppretter ALDRI et foretak.
     */
    private static function resolve_owner_foretak_id() {
        $id = defined('BV_AIHUB_OWNER_FORETAK_ID') ? (int) BV_AIHUB_OWNER_FORETAK_ID : 0;
        $id = (int) apply_filters('bimverdi_aec_owner_foretak_id', $id);
        if ($id && get_post_type($id) === 'foretak') {
            return $id;
        }
        return 0;
    }
}
