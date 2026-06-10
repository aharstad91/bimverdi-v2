<?php
/**
 * AEC AI Hub — datakilde (Trinn 1: committet fixture; Trinn 2: live Notion-stub).
 *
 * Ett inngangspunkt `BV_AIHUB_Tool_Source::fetch_tools()` med `BV_AIHUB_LIVE`-bryter:
 *   - Trinn 1 (LIVE=false): les den committede `data/aec-ai-hub-tools.json` (full
 *     475-snapshot), valider HELE settet, og returner ALLE rader normalisert.
 *     Champion-filter + dedup skjer i upserteren (Decision 2, 7), ikke her.
 *   - Trinn 2 (LIVE=true): dokumentert stub som kaster — full Notion-klient er utenfor scope.
 *
 * To-fase-kontrakt (Decision 7): denne klassen er FASE 1 («hent + valider HELE settet»).
 * Hard feil (fil mangler/uleselig, malformert JSON, ugyldig struktur, ELLER brudd på
 * G4-paritet `bv_aec_normalize_url(url) === identity_key`) gir `ok=false` → orkestratoren
 * aborterer FØR noen post røres. Dup-er er IKKE en hard feil — de rapporteres som warning
 * og løses av merge-collapse i upserteren.
 *
 * @package BIMVerdiCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class BV_AIHUB_Tool_Source {

    /**
     * Inngangspunkt. Ruter på `BV_AIHUB_LIVE`.
     *
     * @return array Resultatkontrakt fra read_fixture() (Trinn 1).
     * @throws RuntimeException Fra live-stubben i Trinn 2 (LIVE=true).
     */
    public static function fetch_tools() {
        if (defined('BV_AIHUB_LIVE') && BV_AIHUB_LIVE) {
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
     *     'ok'       => bool,                 // false ved hard feil (Fase 1-abort)
     *     'error'    => string|null,          // grunn ved ok=false
     *     'source'   => 'fixture',
     *     'meta'     => array,                // _meta-blokken
     *     'tools'    => array,                // ALLE validerte, normaliserte rader (ikke champion-filtrert)
     *     'counts'   => ['total','champion','ai_driven','champion_and_ai'],
     *     'warnings' => ['dup_identity_keys' => [key => count], ...],
     *   ]
     *
     * @param string $path Absolutt sti til JSON-fixturen.
     * @return array
     */
    public static function read_fixture($path) {
        $result = array(
            'ok'       => false,
            'error'    => null,
            'source'   => 'fixture',
            'meta'     => array(),
            'tools'    => array(),
            'counts'   => array('total' => 0, 'champion' => 0, 'ai_driven' => 0, 'champion_and_ai' => 0),
            'warnings' => array(),
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
        return array(
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
     * Trinn 2 live-kilde — IKKE implementert i Trinn 1.
     *
     * Når denne bygges (live hub `b6e6eebe…`) må den dekke:
     *   - data_source-resolusjon: Notion database↔data_source-split (fra 2025-09-03);
     *     resolve `data_source_id` før spørring, ikke rå database-id.
     *   - paginering (`start_cursor`/`has_more`) + `Retry-After`-respektering.
     *   - token-håndtering: `ntn_…`-token leses fra en wp-config-konstant
     *     (f.eks. BV_AIHUB_NOTION_TOKEN) — ALDRI committet i repo.
     *   - schema-/parser-mapping fra Notion-properties → samme normaliserte rad-form
     *     som fixturen ({identity_key, name, short_desc, long_desc, url, logo_url,
     *     categories[], champion, ai_driven}).
     *   - IDENTITETSMIGRERING: Notion gir nå en page-id. URL forblir den stabile
     *     primærnøkkelen (`_bv_aec_source_key`); page-id mappes som SEKUNDÆR korrelasjon
     *     til eksisterende URL-nøkler — page-id blir ALDRI ny primæridentitet.
     *
     * Mønster: mu-plugins/bimverdi-brreg-api.php (wp_remote_*, transient, error_log).
     *
     * @throws RuntimeException Alltid i Trinn 1.
     */
    private static function fetch_from_live() {
        error_log('[BV_AIHUB] fetch_from_live() kalt med BV_AIHUB_LIVE=true — ikke implementert i Trinn 1.');
        throw new RuntimeException(
            'AEC AI Hub live-kilde (Trinn 2) er ikke implementert. Trinn 1 bruker committet fixture; sett BV_AIHUB_LIVE=false.'
        );
    }
}
