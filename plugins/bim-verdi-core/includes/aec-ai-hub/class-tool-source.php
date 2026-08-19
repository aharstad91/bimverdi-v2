<?php
/**
 * AEC AI Hub — datakilde (Trinn 1: committet fixture; Trinn 2: live Notion).
 *
 * Ett inngangspunkt `BV_AIHUB_Tool_Source::fetch_tools()` som ruter på kildevalget
 * (se `is_live()`):
 *   - Fixture (default): les den committede `data/aec-ai-hub-tools.json` (475-snapshot
 *     fra juni 2026), valider HELE settet, og returner ALLE rader normalisert.
 *     Champion-filter + dedup skjer i upserteren (Decision 2, 7), ikke her.
 *   - Live (Trinn 2): les hele hub-databasen direkte fra notion.site via
 *     BV_AIHUB_Notion_Client — 1921 rader per 19.08.2026.
 *
 * CHAMPION-GATE: fixturen har et `Champion`-felt (238 av 475 = import-kandidatene).
 * Stefan FJERNET den kolonnen fra hub-en i august 2026, og Bård har godkjent at hele
 * basen importeres. Live-kontrakten setter derfor `champion_gate = false`, som slår av
 * champion-filteret i upserteren; fixture-kontrakten beholder `true` slik at gammel
 * oppførsel (og selftesten) er uendret.
 *
 * To-fase-kontrakt (Decision 7): denne klassen er FASE 1 («hent + valider HELE settet»).
 * Hard feil (fil mangler/uleselig, malformert JSON, ugyldig struktur, manglende Notion-
 * schema, TRUNKERT live-svar, ELLER brudd på G4-paritet
 * `bv_aec_normalize_url(url) === identity_key`) gir `ok=false` → orkestratoren aborterer
 * FØR noen post røres. Dup-er er IKKE en hard feil — de rapporteres som warning og løses
 * av merge-collapse i upserteren.
 *
 * @package BIMVerdiCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class BV_AIHUB_Tool_Source {

    /** Option som slår på live-kilden uten å redigere wp-config (CLI/cron-vennlig). */
    const OPTION_LIVE = 'bv_aihub_live_source';

    /** Maks lengde på `short_desc` (matcher fixturens kutt). */
    const SHORT_DESC_MAX = 160;

    /**
     * Skal vi lese live Notion-kilden?
     *
     * Tre lag, i økende presedens: konstanten `BV_AIHUB_LIVE` (wp-config/plugin-default),
     * option-en `bv_aihub_live_source` (settes av `wp bimverdi aihub-source live`), og
     * filteret `bimverdi_aihub_live` (brukes av CLI-flagget `--live`/`--fixture` og av tester).
     *
     * @return bool
     */
    public static function is_live() {
        $live = defined('BV_AIHUB_LIVE') && BV_AIHUB_LIVE;

        if (!$live && function_exists('get_option') && get_option(self::OPTION_LIVE, 0)) {
            $live = true;
        }

        /**
         * Overstyr kildevalget for én kjøring.
         *
         * @param bool $live
         */
        return (bool) apply_filters('bimverdi_aihub_live', $live);
    }

    /**
     * Inngangspunkt. Ruter på is_live().
     *
     * @return array Resultatkontrakt (samme form for fixture og live).
     */
    public static function fetch_tools() {
        if (self::is_live()) {
            return self::fetch_from_live();
        }

        $path = defined('BV_AIHUB_FIXTURE_PATH') ? BV_AIHUB_FIXTURE_PATH : '';
        return self::read_fixture($path);
    }

    /**
     * Les og valider en fixture-fil. Public så selftesten kan peke den mot `data/test/*`.
     *
     * Resultatkontrakt:
     *   [
     *     'ok'            => bool,            // false ved hard feil (Fase 1-abort)
     *     'error'         => string|null,     // grunn ved ok=false
     *     'source'        => 'fixture',
     *     'champion_gate' => true,            // fixturen har Champion-kolonne → filtrer på den
     *     'meta'          => array,           // _meta-blokken
     *     'tools'         => array,           // ALLE validerte, normaliserte rader (ikke champion-filtrert)
     *     'counts'        => ['total','champion','ai_driven','champion_and_ai'],
     *     'warnings'      => ['dup_identity_keys' => [key => count], ...],
     *   ]
     *
     * @param string $path Absolutt sti til JSON-fixturen.
     * @return array
     */
    public static function read_fixture($path) {
        $result = array(
            'ok'            => false,
            'error'         => null,
            'source'        => 'fixture',
            'champion_gate' => true,
            'meta'          => array(),
            'tools'         => array(),
            'counts'        => array('total' => 0, 'champion' => 0, 'ai_driven' => 0, 'champion_and_ai' => 0),
            'warnings'      => array(),
        );

        if (!function_exists('bv_aec_normalize_url')) {
            return self::fail($result, 'bv_aec_normalize_url() er ikke lastet (helpers.php mangler) — kan ikke validere paritet.');
        }

        if (empty($path) || !file_exists($path) || !is_readable($path)) {
            return self::fail($result, sprintf('Fixture-fil mangler eller er uleselig: %s', $path));
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return self::fail($result, sprintf('Klarte ikke å lese fixture-fil: %s', $path));
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return self::fail($result, sprintf('Malformert JSON i %s: %s', $path, json_last_error_msg()));
        }
        if (!is_array($data) || !isset($data['tools']) || !is_array($data['tools'])) {
            return self::fail($result, 'Ugyldig fixture-struktur: forventet { _meta, tools[] }.');
        }

        $result['meta'] = isset($data['_meta']) && is_array($data['_meta']) ? $data['_meta'] : array();

        $tools        = array();
        $row_errors   = array();
        $seen_keys    = array();
        $dup_counts   = array();
        $n_champion   = 0;
        $n_ai         = 0;
        $n_champ_ai   = 0;

        foreach ($data['tools'] as $i => $row) {
            $problems = self::validate_row($row, $i);
            if (!empty($problems)) {
                $row_errors = array_merge($row_errors, $problems);
                continue; // ikke normaliser en ugyldig rad; vi aborterer uansett nedenfor
            }

            $norm = self::normalize_row($row);
            $tools[] = $norm;

            // Dup-telling (warning, ikke abort).
            $key = $norm['identity_key'];
            if (isset($seen_keys[$key])) {
                $dup_counts[$key] = isset($dup_counts[$key]) ? $dup_counts[$key] + 1 : 2;
            } else {
                $seen_keys[$key] = true;
            }

            if ($norm['champion']) {
                $n_champion++;
                if ($norm['ai_driven']) {
                    $n_champ_ai++;
                }
            }
            if ($norm['ai_driven']) {
                $n_ai++;
            }
        }

        // Hard feil: enhver ugyldig rad (inkl. paritetsbrudd) → Fase 1-abort.
        if (!empty($row_errors)) {
            $preview = array_slice($row_errors, 0, 10);
            $msg = sprintf(
                '%d rad(er) feilet validering (Fase 1-abort). Første: %s',
                count($row_errors),
                implode(' | ', $preview)
            );
            return self::fail($result, $msg);
        }

        // Dup-warnings (de 2 kolliderende champions løses av merge-collapse i upserteren).
        if (!empty($dup_counts)) {
            $result['warnings']['dup_identity_keys'] = $dup_counts;
        }

        $result['ok']     = true;
        $result['tools']  = $tools;
        $result['counts'] = array(
            'total'           => count($tools),
            'champion'        => $n_champion,
            'ai_driven'       => $n_ai,
            'champion_and_ai' => $n_champ_ai,
        );

        return $result;
    }

    /**
     * Valider én rad mot normalisert form + G4-paritet.
     *
     * @param mixed $row Rå rad.
     * @param int   $i   Indeks (for feilmelding).
     * @return string[] Liste av problemer (tom = gyldig).
     */
    private static function validate_row($row, $i) {
        $problems = array();

        if (!is_array($row)) {
            return array(sprintf('rad %d: ikke et objekt', $i));
        }

        // Påkrevde ikke-tomme strenger.
        foreach (array('identity_key', 'name', 'url') as $req) {
            if (!isset($row[$req]) || !is_string($row[$req]) || trim($row[$req]) === '') {
                $problems[] = sprintf('rad %d: mangler/ugyldig «%s»', $i, $req);
            }
        }

        // categories må være array.
        if (!isset($row['categories']) || !is_array($row['categories'])) {
            $problems[] = sprintf('rad %d: «categories» må være array', $i);
        }

        // champion + ai_driven må være ekte boolean (ikke "Yes"-streng).
        foreach (array('champion', 'ai_driven') as $flag) {
            if (!array_key_exists($flag, $row) || !is_bool($row[$flag])) {
                $problems[] = sprintf('rad %d: «%s» må være boolean', $i, $flag);
            }
        }

        // G4-paritet: bare meningsfull når url + identity_key er gyldige strenger.
        if (empty($problems) && function_exists('bv_aec_normalize_url')) {
            $derived = bv_aec_normalize_url($row['url']);
            if ($derived !== $row['identity_key']) {
                $problems[] = sprintf(
                    'rad %d: PARITETSBRUDD normalize(%s)=«%s» ≠ identity_key=«%s»',
                    $i,
                    $row['url'],
                    $derived,
                    $row['identity_key']
                );
            }
        }

        return $problems;
    }

    /**
     * Bygg en ren, typesikker rad. Optionelle tekstfelt defaultes til tom streng.
     *
     * @param array $row Validert rå rad.
     * @return array
     */
    private static function normalize_row($row) {
        $norm = array(
            'identity_key' => (string) $row['identity_key'],
            'name'         => (string) $row['name'],
            'short_desc'   => isset($row['short_desc']) && is_string($row['short_desc']) ? $row['short_desc'] : '',
            'long_desc'    => isset($row['long_desc']) && is_string($row['long_desc']) ? $row['long_desc'] : '',
            'url'          => (string) $row['url'],
            'logo_url'     => isset($row['logo_url']) && is_string($row['logo_url']) ? $row['logo_url'] : '',
            'categories'   => array_values($row['categories']),
            'champion'     => (bool) $row['champion'],
            'ai_driven'    => (bool) $row['ai_driven'],
        );

        // Notion page-id: SEKUNDÆR korrelasjon (aldri primæridentitet — URL er nøkkelen).
        // Bare med når live-kilden har levert den; fixturen har ingen.
        if (isset($row['notion_id']) && is_string($row['notion_id']) && $row['notion_id'] !== '') {
            $norm['notion_id'] = $row['notion_id'];
        }

        return $norm;
    }

    /**
     * Sett ok=false + feilmelding og logg.
     *
     * @param array  $result
     * @param string $error
     * @return array
     */
    private static function fail($result, $error) {
        $result['ok']    = false;
        $result['error'] = $error;
        error_log('[BV_AIHUB] Tool_Source Fase 1-abort: ' . $error);
        return $result;
    }

    /**
     * Trinn 2: hent HELE hub-databasen live fra notion.site.
     *
     * Sekvens:
     *   1. discover() → spaceId, collection, view og schema (property-ID-er slås opp på NAVN).
     *   2. Schema-kontrakt: Name/URL/Category/Description må finnes. Mangler én → abort
     *      (bedre å stoppe enn å importere 1900 tomme felt). AI-Driven er valgfri (kun badge).
     *   3. Partisjonér på Category (+ én «tom kategori»-partisjon). Årsak: recordMap-en er
     *      kappet på ~1000 blokker, og hele basen er 1921 rader — se læring B i klienten.
     *   4. Trunkeringsvakt per partisjon; sub-partisjonér på AI-Driven hvis en kategori
     *      alene sprenger limit-en. Fortsatt trunkert → abort.
     *   5. Totalvakt: union av alle partisjoner må være >= `sizeHint` (hele collection-
     *      størrelsen). Færre rader enn kilden selv oppgir = trunkert svar → abort.
     *   6. Normalisér til samme rad-kontrakt som fixturen og kjør SAMME validering
     *      (inkl. G4-paritet). champion=false på alle rader; `champion_gate=false` slår
     *      av champion-filteret nedstrøms.
     *
     * Ingen token: hub-en er publisert offentlig og v3-endepunktene svarer uautentisert.
     * URL er fortsatt primæridentitet (`_bv_aec_source_key`); Notion page-id følger med
     * som `notion_id` og lagres kun som sekundær korrelasjon.
     *
     * @return array Samme resultatkontrakt som read_fixture(), med source='live'.
     */
    private static function fetch_from_live() {
        $result = array(
            'ok'            => false,
            'error'         => null,
            'source'        => 'live',
            'champion_gate' => false, // Champion-kolonnen er fjernet fra kilden (Bård, 18.08.2026)
            'meta'          => array(),
            'tools'         => array(),
            'counts'        => array('total' => 0, 'champion' => 0, 'ai_driven' => 0, 'champion_and_ai' => 0),
            'warnings'      => array(),
        );

        if (!function_exists('bv_aec_normalize_url')) {
            return self::fail($result, 'bv_aec_normalize_url() er ikke lastet (helpers.php mangler) — kan ikke validere paritet.');
        }
        if (!class_exists('BV_AIHUB_Notion_Client')) {
            return self::fail($result, 'BV_AIHUB_Notion_Client er ikke lastet — kan ikke lese live-kilden.');
        }

        $page_id = defined('BV_AIHUB_NOTION_PAGE_ID') && BV_AIHUB_NOTION_PAGE_ID
            ? BV_AIHUB_NOTION_PAGE_ID
            : 'b6e6eebe-8809-4e0e-9b49-95da38e96768';

        // 1) Oppdag struktur + schema.
        $ctx = BV_AIHUB_Notion_Client::discover($page_id);
        if (empty($ctx['ok'])) {
            return self::fail($result, 'Notion-oppdagelse feilet: ' . $ctx['error']);
        }

        // 2) Schema-kontrakt.
        $props    = $ctx['props'];
        $required = array('Name', 'URL', 'Category', 'Description');
        $missing  = array();
        foreach ($required as $name) {
            if (empty($props[$name])) {
                $missing[] = $name;
            }
        }
        if (!empty($missing)) {
            return self::fail($result, sprintf(
                'Notion-schemaet mangler påkrevd(e) kolonne(r): %s. Funnet: %s. (Har Stefan døpt om noe? Ingen poster er rørt.)',
                implode(', ', $missing),
                implode(', ', array_keys($props))
            ));
        }

        $p_name     = $props['Name'];
        $p_url      = $props['URL'];
        $p_category = $props['Category'];
        $p_desc     = $props['Description'];
        $p_ai       = isset($props['AI-Driven']) ? $props['AI-Driven'] : '';

        if ($p_ai === '') {
            $result['warnings']['schema'][] = 'Kolonnen «AI-Driven» finnes ikke i kilden — alle rader importeres uten AI-badge.';
        }

        // 3) Partisjoner: hver Category-option + én for rader uten kategori.
        $options = $ctx['category_options'];
        if (empty($options)) {
            return self::fail($result, 'Category-kolonnen har ingen select-options — kan ikke partisjonere (recordMap-kappen gjør ufiltrert henting utrygg).');
        }

        $partitions = array();
        foreach ($options as $opt) {
            $partitions[] = array(
                'label'   => $opt,
                'filters' => array(BV_AIHUB_Notion_Client::filter_select($p_category, $opt)),
            );
        }
        $partitions[] = array(
            'label'   => '(uten kategori)',
            'filters' => array(BV_AIHUB_Notion_Client::filter_empty($p_category)),
        );

        $raw          = array(); // block_id => Notion-blokkverdi (dedupliserer på tvers av partisjoner)
        $size_hint    = 0;
        $part_counts  = array();

        foreach ($partitions as $part) {
            $q = BV_AIHUB_Notion_Client::query_partition($ctx, $part['filters']);
            if (empty($q['ok'])) {
                return self::fail($result, sprintf('Notion-spørring for «%s» feilet: %s', $part['label'], $q['error']));
            }
            $size_hint = max($size_hint, (int) $q['size_hint']);

            // 4) Trunkeringsvakt → sub-partisjonér på AI-Driven (2 bøtter) hvis mulig.
            if (!empty($q['truncated'])) {
                if ($p_ai === '') {
                    return self::fail($result, sprintf(
                        'Partisjonen «%s» er trunkert (%d ID-er, %d oppløste rader) og kan ikke deles finere (ingen AI-Driven-kolonne). Utvid partisjoneringen i class-tool-source.php.',
                        $part['label'],
                        count($q['block_ids']),
                        count($q['rows'])
                    ));
                }

                $result['warnings']['partition'][] = sprintf(
                    'Partisjonen «%s» sprengte rad-limiten — deler på AI-Driven.',
                    $part['label']
                );

                $sub_rows = array();
                foreach (array(true, false) as $checked) {
                    $sub_filters   = $part['filters'];
                    $sub_filters[] = BV_AIHUB_Notion_Client::filter_checkbox($p_ai, $checked);
                    $sq            = BV_AIHUB_Notion_Client::query_partition($ctx, $sub_filters);

                    if (empty($sq['ok'])) {
                        return self::fail($result, sprintf(
                            'Sub-partisjonen «%s» / AI-Driven=%s feilet: %s',
                            $part['label'],
                            $checked ? 'ja' : 'nei',
                            $sq['error']
                        ));
                    }
                    if (!empty($sq['truncated'])) {
                        return self::fail($result, sprintf(
                            'Sub-partisjonen «%s» / AI-Driven=%s er FORTSATT trunkert (%d ID-er, %d rader) — partisjoneringen må gjøres finere før synken kan kjøre.',
                            $part['label'],
                            $checked ? 'ja' : 'nei',
                            count($sq['block_ids']),
                            count($sq['rows'])
                        ));
                    }
                    foreach ($sq['rows'] as $bid => $row) {
                        $sub_rows[$bid] = $row;
                    }
                }
                $q['rows'] = $sub_rows;
            }

            foreach ($q['rows'] as $bid => $row) {
                $raw[$bid] = $row;
            }
            $part_counts[$part['label']] = count($q['rows']);
        }

        // 5) Totalvakt: aldri importér færre rader enn kilden selv oppgir.
        if ($size_hint > 0 && count($raw) < $size_hint) {
            return self::fail($result, sprintf(
                'TRUNKERT KILDE: partisjonene ga %d rader, men Notion oppgir %d i basen. Ingen poster er rørt (orphan-rydding ville ellers avpublisert differansen).',
                count($raw),
                $size_hint
            ));
        }

        // 6) Normalisér + valider (samme regler som fixturen).
        $tools      = array();
        $row_errors = array();
        $skipped    = array();
        $seen_keys  = array();
        $dup_counts = array();
        $n_ai       = 0;

        $i = 0;
        foreach ($raw as $block_id => $block) {
            $i++;
            $properties = isset($block['properties']) && is_array($block['properties']) ? $block['properties'] : array();

            $name = BV_AIHUB_Notion_Client::text_value($properties, $p_name);
            $url  = BV_AIHUB_Notion_Client::text_value($properties, $p_url);
            $desc = BV_AIHUB_Notion_Client::text_value($properties, $p_desc);
            $cat  = BV_AIHUB_Notion_Client::text_value($properties, $p_category);
            $ai   = $p_ai !== '' && BV_AIHUB_Notion_Client::checkbox_value($properties, $p_ai);

            // Rader uten navn eller uten brukbar URL kan ikke få identitet → hopp over med
            // merknad (ikke abort: én skadet rad skal ikke stoppe 1900 gode).
            $identity = bv_aec_normalize_url($url);
            if ($name === '' || $identity === '') {
                $skipped[] = sprintf(
                    'Notion-rad %s hoppet over (navn=«%s», url=«%s»)',
                    $block_id,
                    $name,
                    $url
                );
                continue;
            }

            $row = array(
                'identity_key' => $identity,
                'name'         => $name,
                'short_desc'   => self::excerpt($desc, self::SHORT_DESC_MAX),
                'long_desc'    => $desc,
                'url'          => $url,
                'logo_url'     => '', // hub-en har ingen logo-kolonne
                'categories'   => $cat !== '' ? array($cat) : array(),
                'champion'     => false, // kolonnen finnes ikke lenger i kilden
                'ai_driven'    => $ai,
                'notion_id'    => (string) $block_id,
            );

            $problems = self::validate_row($row, $i);
            if (!empty($problems)) {
                $row_errors = array_merge($row_errors, $problems);
                continue;
            }

            $norm    = self::normalize_row($row);
            $tools[] = $norm;

            if (isset($seen_keys[$identity])) {
                $dup_counts[$identity] = isset($dup_counts[$identity]) ? $dup_counts[$identity] + 1 : 2;
            } else {
                $seen_keys[$identity] = true;
            }
            if ($norm['ai_driven']) {
                $n_ai++;
            }
        }

        // Ugyldige rader etter normalisering betyr at normaliseringen selv er ute av sync
        // med valideringen (f.eks. paritetsregelen) → hard abort, samme som fixturen.
        if (!empty($row_errors)) {
            return self::fail($result, sprintf(
                '%d live-rad(er) feilet validering (Fase 1-abort). Første: %s',
                count($row_errors),
                implode(' | ', array_slice($row_errors, 0, 5))
            ));
        }

        if (!empty($skipped)) {
            $result['warnings']['skipped_rows'] = $skipped;
            error_log(sprintf('[BV_AIHUB] Live-kilde: %d rad(er) hoppet over (mangler navn/URL).', count($skipped)));
        }
        if (!empty($dup_counts)) {
            $result['warnings']['dup_identity_keys'] = $dup_counts;
        }

        $result['ok']     = true;
        $result['tools']  = $tools;
        $result['counts'] = array(
            'total'           => count($tools),
            'champion'        => 0,
            'ai_driven'       => $n_ai,
            'champion_and_ai' => 0,
        );
        $result['meta']   = array(
            'source'           => 'AEC AI Hub (live, notion.site)',
            'page_id'          => BV_AIHUB_Notion_Client::dashed_id($page_id),
            'collection_id'    => $ctx['collection_id'],
            'view_id'          => $ctx['view_id'],
            'fetched_at'       => current_time('mysql'),
            'size_hint'        => $size_hint,
            'raw_rows'         => count($raw),
            'total_tools'      => count($tools),
            'champion_gate'    => false,
            'partition_counts' => $part_counts,
            'schema_columns'   => array_keys($props),
            'note'             => 'Champion-kolonnen er fjernet fra kilden; hele basen importeres (Bård 18.08.2026). Identitet = normalisert URL; notion_id er kun sekundær korrelasjon.',
        );

        error_log(sprintf(
            '[BV_AIHUB] Live-kilde OK: %d rader hentet (sizeHint %d) fordelt på %d partisjoner, %d validerte verktøy, %d AI-drevne.',
            count($raw),
            $size_hint,
            count($part_counts),
            count($tools),
            $n_ai
        ));

        return $result;
    }

    /**
     * Kutt en beskrivelse til `short_desc` (samme form som fixturen: maks 160 tegn,
     * kuttet på ordgrense med «…»).
     *
     * @param string $text
     * @param int    $max
     * @return string
     */
    private static function excerpt($text, $max) {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $text));
        if ($text === '' || mb_strlen($text) <= $max) {
            return $text;
        }

        $cut  = mb_substr($text, 0, $max - 1);
        $last = mb_strrpos($cut, ' ');
        if ($last !== false && $last > (int) (($max - 1) * 0.6)) {
            $cut = mb_substr($cut, 0, $last);
        }

        return rtrim($cut, " ,.;:-") . '…';
    }
}
