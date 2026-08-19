<?php
/**
 * AEC AI Hub — Notion-klient (Trinn 2: live-kilde).
 *
 * Leser Stjepan Mikulić' offentlige AEC AI Hub-database direkte fra
 * `aiinaec.notion.site` sine v3-endepunkter. Ingen offisiell Notion-integrasjon og
 * ingen token: databasen er publisert offentlig, og de samme to endepunktene som
 * nettleseren bruker svarer uautentisert.
 *
 * To endepunkter:
 *   1. `POST /api/v3/loadCachedPageChunkV2`  → sidestruktur: spaceId, collection_id,
 *      view_id og hele schemaet (property-ID → navn/type/select-options).
 *   2. `POST /api/v3/queryCollection`        → radene, med filter.
 *
 * TRE HARDE LÆRINGER (verifisert mot live-API 18.–19.08.2026) — ikke «forenkle» bort:
 *
 *   A. NON-BROWSER USER-AGENT BLIR 403. Notion avviser python-urllib/curl-default.
 *      Vi MÅ sende en nettleser-UA (self::UA). wp_remote_post sender WordPress' egen
 *      UA som default, så headeren settes eksplisitt på hver request.
 *
 *   B. recordMap-en er kappet på ~1000 blokker per kall. Et ufiltrert kall mot 1921
 *      rader gir 1921 blockIds men bare 1000 oppløste rader — 921 rader forsvinner
 *      STILLE. Derfor partisjonerer vi på Category (største bøtte i dag: Platform 737)
 *      og validerer hver partisjon (`hasMore=false` + blockIds === oppløste rader).
 *
 *   C. `result.sizeHint` er ALLTID hele collection-størrelsen (1921), også for et
 *      filtrert kall som returnerer 24 rader. Den er derfor ubrukelig som «antall
 *      treff», men perfekt som totalorakel: union av alle partisjoner må være >=
 *      sizeHint, ellers er kilden trunkert og synken skal aborteres.
 *
 * Klienten skriver INGENTING og kjenner ikke WordPress-datamodellen — den leverer
 * rå rader `{name, url, category, description, ai_driven, notion_id}` til
 * BV_AIHUB_Tool_Source, som normaliserer og validerer (Fase 1).
 *
 * @package BIMVerdiCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class BV_AIHUB_Notion_Client {

    /** Nettleser-UA. Læring A: uten denne svarer Notion 403. */
    const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

    /** Timeout per request (sekunder). Største partisjon (737 rader) svarer på ~2–4 s. */
    const TIMEOUT = 45;

    /** Antall forsøk per request (transporte-/5xx-feil retryes med backoff). */
    const MAX_ATTEMPTS = 3;

    /** Rad-limit per queryCollection-kall. Under ~1000-blokk-kappen (læring B). */
    const RESULT_LIMIT = 900;

    /** Notion-property-typer vi kan lese. */
    const SUPPORTED_TYPES = array('title', 'text', 'url', 'select', 'checkbox', 'created_time');

    /**
     * Base-URL for hub-en (overstyrbar via konstant hvis Stefan flytter siden).
     *
     * @return string Uten trailing slash.
     */
    public static function base_url() {
        $base = defined('BV_AIHUB_NOTION_BASE') && BV_AIHUB_NOTION_BASE
            ? BV_AIHUB_NOTION_BASE
            : 'https://aiinaec.notion.site';
        return rtrim($base, '/');
    }

    /**
     * Steg 1: finn collection, view, space og schema for hub-siden.
     *
     * Vi hardkoder ALDRI property-ID-er (`Bt>z`, `iqZN`, …) — de slås opp fra schemaet
     * på NAVN («Name», «URL», «Category», «Description», «AI-Driven»). Da overlever
     * synken at Stefan legger til/flytter kolonner, og feiler tydelig hvis han fjerner
     * eller døper om en vi er avhengig av (i stedet for å importere tomme felt).
     *
     * @param string $page_id Notion page-id (med eller uten bindestreker).
     * @return array {
     *     ok, error, space_id, collection_id, view_id,
     *     schema        => [prop_id => ['name'=>…, 'type'=>…, 'options'=>[…]]],
     *     props         => [navn => prop_id],
     *     category_options => string[]  (select-verdier på Category)
     * }
     */
    public static function discover($page_id) {
        $out = array(
            'ok'               => false,
            'error'            => null,
            'space_id'         => '',
            'collection_id'    => '',
            'view_id'          => '',
            'schema'           => array(),
            'props'            => array(),
            'category_options' => array(),
        );

        $res = self::post('loadCachedPageChunkV2', array(
            'page'            => array('id' => self::dashed_id($page_id)),
            'limit'           => 50,
            'cursor'          => array('stack' => array()),
            'chunkNumber'     => 0,
            'verticalColumns' => false,
        ));

        if (empty($res['ok'])) {
            $out['error'] = 'Kunne ikke laste hub-siden: ' . $res['error'];
            return $out;
        }

        $data      = $res['data'];
        $recordmap = isset($data['recordMap']) && is_array($data['recordMap']) ? $data['recordMap'] : array();
        $blocks    = isset($recordmap['block']) && is_array($recordmap['block']) ? $recordmap['block'] : array();

        if (empty($blocks)) {
            $out['error'] = 'Hub-siden svarte uten blokker (recordMap.block tom) — er siden fortsatt publisert?';
            return $out;
        }

        // spaceId ligger på toppnivå i V2-svaret; fall tilbake til blokkens space_id.
        $space_id = isset($data['spaceId']) ? (string) $data['spaceId'] : '';

        // Finn databasen: første collection-blokk som har BÅDE collection_id og view_ids.
        // (Lenkede visninger har view_ids uten collection_id — de skal hoppes over.)
        $collection_id = '';
        $view_ids      = array();
        foreach ($blocks as $entry) {
            $b = self::unwrap($entry);
            if (!is_array($b)) {
                continue;
            }
            if ($space_id === '' && !empty($b['space_id'])) {
                $space_id = (string) $b['space_id'];
            }
            $type = isset($b['type']) ? $b['type'] : '';
            if ($type !== 'collection_view' && $type !== 'collection_view_page') {
                continue;
            }
            if (empty($b['collection_id']) || empty($b['view_ids']) || !is_array($b['view_ids'])) {
                continue;
            }
            $collection_id = (string) $b['collection_id'];
            $view_ids      = array_values($b['view_ids']);
            break;
        }

        if ($collection_id === '' || empty($view_ids)) {
            $out['error'] = 'Fant ingen database-blokk (collection_view med collection_id) på hub-siden.';
            return $out;
        }
        if ($space_id === '') {
            $out['error'] = 'Fant ingen spaceId — queryCollection svarer 500 (DatastoreInfraError) uten korrekt spaceId.';
            return $out;
        }

        // Foretrekk en tabellvisning (stabil sortering, ingen gruppering); ellers første view.
        $views   = isset($recordmap['collection_view']) && is_array($recordmap['collection_view'])
            ? $recordmap['collection_view'] : array();
        $view_id = (string) $view_ids[0];
        foreach ($view_ids as $vid) {
            if (!isset($views[$vid])) {
                continue;
            }
            $v = self::unwrap($views[$vid]);
            if (is_array($v) && isset($v['type']) && $v['type'] === 'table') {
                $view_id = (string) $vid;
                break;
            }
        }

        // Schema fra collection-recorden.
        $collections = isset($recordmap['collection']) && is_array($recordmap['collection'])
            ? $recordmap['collection'] : array();
        if (!isset($collections[$collection_id])) {
            $out['error'] = sprintf('Fant ingen schema for collection %s i recordMap.', $collection_id);
            return $out;
        }
        $collection = self::unwrap($collections[$collection_id]);
        $schema     = isset($collection['schema']) && is_array($collection['schema']) ? $collection['schema'] : array();
        if (empty($schema)) {
            $out['error'] = 'Collection-recorden har tomt schema.';
            return $out;
        }

        $props            = array();
        $clean_schema     = array();
        $category_options = array();
        foreach ($schema as $prop_id => $prop) {
            if (!is_array($prop) || empty($prop['name'])) {
                continue;
            }
            $name   = (string) $prop['name'];
            $type   = isset($prop['type']) ? (string) $prop['type'] : '';
            $opts   = array();
            if (!empty($prop['options']) && is_array($prop['options'])) {
                foreach ($prop['options'] as $o) {
                    if (is_array($o) && isset($o['value']) && $o['value'] !== '') {
                        $opts[] = (string) $o['value'];
                    }
                }
            }
            $clean_schema[$prop_id] = array('name' => $name, 'type' => $type, 'options' => $opts);
            // Første forekomst av et navn vinner (Notion tillater duplikatnavn; vi vil determinisme).
            if (!isset($props[$name])) {
                $props[$name] = (string) $prop_id;
            }
        }

        $out['ok']            = true;
        $out['space_id']      = $space_id;
        $out['collection_id'] = $collection_id;
        $out['view_id']       = $view_id;
        $out['schema']        = $clean_schema;
        $out['props']         = $props;

        // Category-options (partisjonsnøklene) hentes fra det oppdagede Category-feltet.
        $cat_prop = isset($props['Category']) ? $props['Category'] : '';
        if ($cat_prop !== '' && !empty($clean_schema[$cat_prop]['options'])) {
            $category_options = $clean_schema[$cat_prop]['options'];
        }
        $out['category_options'] = $category_options;

        return $out;
    }

    /**
     * Steg 2: hent rader for ett filter (én partisjon).
     *
     * @param array $ctx     Resultatet fra discover().
     * @param array $filters Notion-filterobjekter (AND-et sammen). Tom = hele basen.
     * @param int   $limit   Rad-limit (default RESULT_LIMIT).
     * @return array {ok, error, block_ids, rows (rå Notion-blokkverdier), size_hint, has_more, truncated}
     */
    public static function query_partition(array $ctx, array $filters = array(), $limit = 0) {
        $limit = $limit > 0 ? (int) $limit : self::RESULT_LIMIT;

        $out = array(
            'ok'        => false,
            'error'     => null,
            'block_ids' => array(),
            'rows'      => array(),
            'size_hint' => 0,
            'has_more'  => false,
            'truncated' => false,
        );

        $loader = array(
            'type'         => 'reducer',
            'reducers'     => array(
                'collection_group_results' => array('type' => 'results', 'limit' => $limit),
            ),
            'sort'         => array(array('property' => 'title', 'direction' => 'ascending')),
            'searchQuery'  => '',
            'userTimeZone' => 'Europe/Oslo',
        );
        if (!empty($filters)) {
            $loader['filter'] = array('operator' => 'and', 'filters' => array_values($filters));
        }

        $res = self::post('queryCollection?src=initial_load', array(
            'source'         => array(
                'type'    => 'collection',
                'id'      => $ctx['collection_id'],
                'spaceId' => $ctx['space_id'],
            ),
            'collectionView' => array(
                'id'      => $ctx['view_id'],
                'spaceId' => $ctx['space_id'],
            ),
            'loader'         => $loader,
        ));

        if (empty($res['ok'])) {
            $out['error'] = $res['error'];
            return $out;
        }

        $data   = $res['data'];
        $result = isset($data['result']) && is_array($data['result']) ? $data['result'] : array();
        $group  = isset($result['reducerResults']['collection_group_results'])
            ? $result['reducerResults']['collection_group_results']
            : array();

        $block_ids = isset($group['blockIds']) && is_array($group['blockIds']) ? $group['blockIds'] : array();
        $has_more  = !empty($group['hasMore']);
        // Læring C: sizeHint = hele collection-størrelsen, ikke antall treff for filteret.
        $size_hint = isset($result['sizeHint']) ? (int) $result['sizeHint'] : 0;

        // Plukk radblokkene ut av recordMap (parent_table=collection, alive!=false).
        $rows   = array();
        $blocks = isset($data['recordMap']['block']) && is_array($data['recordMap']['block'])
            ? $data['recordMap']['block'] : array();
        foreach ($block_ids as $bid) {
            if (!isset($blocks[$bid])) {
                continue; // ikke oppløst → kappet recordMap (fanges av truncated nedenfor)
            }
            $b = self::unwrap($blocks[$bid]);
            if (!is_array($b) || !isset($b['parent_table']) || $b['parent_table'] !== 'collection') {
                continue;
            }
            if (array_key_exists('alive', $b) && $b['alive'] === false) {
                continue; // slettet rad
            }
            $rows[$bid] = $b;
        }

        $out['ok']        = true;
        $out['block_ids'] = $block_ids;
        $out['rows']      = $rows;
        $out['size_hint'] = $size_hint;
        $out['has_more']  = $has_more;
        // Trunkering: enten sa API-et «det finnes mer», eller recordMap-en manglet rader
        // vi fikk ID for (læring B — den stille varianten).
        $out['truncated'] = $has_more || (count($block_ids) > count($rows));

        return $out;
    }

    /**
     * Bygg et Category-filter (select = eksakt verdi).
     *
     * @param string $prop_id Property-ID for Category.
     * @param string $value   Select-verdi.
     * @return array
     */
    public static function filter_select($prop_id, $value) {
        return array(
            'property' => $prop_id,
            'filter'   => array(
                'operator' => 'enum_is',
                'value'    => array('type' => 'exact', 'value' => (string) $value),
            ),
        );
    }

    /**
     * Bygg et «feltet er tomt»-filter (fanger rader uten Category).
     *
     * @param string $prop_id
     * @return array
     */
    public static function filter_empty($prop_id) {
        return array(
            'property' => $prop_id,
            'filter'   => array('operator' => 'is_empty'),
        );
    }

    /**
     * Bygg et checkbox-filter (brukes som sub-partisjon hvis en kategori sprenger limit-en).
     *
     * @param string $prop_id
     * @param bool   $checked
     * @return array
     */
    public static function filter_checkbox($prop_id, $checked) {
        return array(
            'property' => $prop_id,
            'filter'   => array(
                'operator' => 'checkbox_is',
                'value'    => array('type' => 'exact', 'value' => (bool) $checked),
            ),
        );
    }

    // ---------------------------------------------------------------- verdilesing

    /**
     * Les en property-verdi som ren tekst.
     *
     * Notion-format: `[[ "tekst", [dekorasjoner…] ], …]`. Vi konkatenerer segmentene og
     * hopper over mention-artefakter («‣», som bærer innholdet i dekorasjonen).
     *
     * @param array  $properties Blokkens properties.
     * @param string $prop_id
     * @return string
     */
    public static function text_value(array $properties, $prop_id) {
        if ($prop_id === '' || !isset($properties[$prop_id]) || !is_array($properties[$prop_id])) {
            return '';
        }
        $parts = array();
        foreach ($properties[$prop_id] as $segment) {
            if (!is_array($segment) || !isset($segment[0]) || !is_string($segment[0])) {
                continue;
            }
            if ($segment[0] === '‣') {
                continue; // mention/dato-referanse — ingen lesbar tekst
            }
            $parts[] = $segment[0];
        }
        return trim(implode('', $parts));
    }

    /**
     * Les en checkbox: Notion serialiserer avkrysset som «Yes». Fraværende nøkkel = av.
     *
     * @param array  $properties
     * @param string $prop_id
     * @return bool
     */
    public static function checkbox_value(array $properties, $prop_id) {
        return self::text_value($properties, $prop_id) === 'Yes';
    }

    // ---------------------------------------------------------------- internt

    /**
     * POST mot notion.site v3 med nettleser-UA, retry og Retry-After-respekt.
     *
     * @param string $path Endepunkt etter /api/v3/ (kan inneholde query-string).
     * @param array  $body JSON-body.
     * @return array {ok, error, data, code}
     */
    private static function post($path, array $body) {
        $url  = self::base_url() . '/api/v3/' . ltrim($path, '/');
        $json = wp_json_encode($body);

        $last_error = 'ukjent feil';

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $response = wp_remote_post($url, array(
                'timeout'     => self::TIMEOUT,
                'redirection' => 3,
                'headers'     => array(
                    'Content-Type' => 'application/json',
                    'User-Agent'   => self::UA, // læring A
                    'Accept'       => 'application/json',
                ),
                'body'        => $json,
            ));

            if (is_wp_error($response)) {
                $last_error = 'transportfeil: ' . $response->get_error_message();
            } else {
                $code = (int) wp_remote_retrieve_response_code($response);
                $raw  = (string) wp_remote_retrieve_body($response);

                if ($code === 200) {
                    $data = json_decode($raw, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $last_error = 'malformert JSON fra Notion: ' . json_last_error_msg();
                    } elseif (!is_array($data)) {
                        $last_error = 'uventet JSON-type fra Notion';
                    } else {
                        return array('ok' => true, 'error' => null, 'data' => $data, 'code' => 200);
                    }
                } elseif ($code === 429 || $code >= 500) {
                    $retry_after = (int) wp_remote_retrieve_header($response, 'retry-after');
                    $last_error  = sprintf('HTTP %d fra Notion: %s', $code, substr($raw, 0, 200));
                    if ($attempt < self::MAX_ATTEMPTS) {
                        $wait = $retry_after > 0 ? min($retry_after, 30) : $attempt * 3;
                        error_log(sprintf('[BV_AIHUB] Notion %s → HTTP %d, venter %ds (forsøk %d/%d).', $path, $code, $wait, $attempt, self::MAX_ATTEMPTS));
                        sleep($wait);
                        continue;
                    }
                } else {
                    // 4xx (403 = blokkert UA, 404 = siden avpublisert) → ikke retry-verdig.
                    return array(
                        'ok'    => false,
                        'error' => sprintf('HTTP %d fra Notion (%s): %s', $code, $path, substr($raw, 0, 200)),
                        'data'  => array(),
                        'code'  => $code,
                    );
                }
            }

            if ($attempt < self::MAX_ATTEMPTS) {
                sleep($attempt * 2);
            }
        }

        return array('ok' => false, 'error' => $last_error, 'data' => array(), 'code' => 0);
    }

    /**
     * Pakk ut recordMap-innslag. Notion pakker verdier som {value:{value:{…}}} i V2 og
     * {role, value:{…}} i eldre svar — begge håndteres.
     *
     * @param mixed $entry
     * @return array|mixed
     */
    private static function unwrap($entry) {
        $v = $entry;
        if (is_array($v) && isset($v['value'])) {
            $v = $v['value'];
        }
        if (is_array($v) && isset($v['value']) && is_array($v['value'])) {
            $v = $v['value'];
        }
        return $v;
    }

    /**
     * Normaliser en Notion-id til bindestrek-form (API-et krever UUID med bindestreker).
     *
     * @param string $id
     * @return string
     */
    public static function dashed_id($id) {
        $hex = preg_replace('/[^0-9a-f]/i', '', (string) $id);
        if (strlen($hex) !== 32) {
            return (string) $id; // la API-et si fra hvis den er ugyldig
        }
        return strtolower(sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        ));
    }
}
