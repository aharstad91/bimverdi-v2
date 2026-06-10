<?php
/**
 * AEC AI Hub — delte hjelpefunksjoner.
 *
 * `bv_aec_normalize_url()` er LOAD-BEARING: den samme funksjonen brukes av
 * fixture-generatoren (som produserte `identity_key` i `data/aec-ai-hub-tools.json`)
 * og av upserteren (som matcher eksisterende poster på `_bv_aec_source_key`). Avviker
 * de selv på ett tegn, lager upserteren duplikater i stedet for å treffe eksisterende
 * post. En full-fixture paritets-assert (alle 475 rader) i selftesten verifiserer at
 * `bv_aec_normalize_url(url) === identity_key` for hver rad.
 *
 * Verifisert 2026-06-08: denne regelen reproduserer alle 475 `identity_key` i fixturen
 * uten avvik.
 *
 * @package BIMVerdiCore
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bv_aec_normalize_url')) {
    /**
     * Normaliser en URL til AEC AI Hub-identitetsnøkkelen.
     *
     * Regel (matcher fixturens `identity_key` 1:1):
     *   1. Strip scheme (https://, http://).
     *   2. Strip ledende «www.».
     *   3. Drop query (?…) + fragment (#…).
     *   4. Drop trailing slash.
     *   5. Lowercase KUN hostname. PATH BEVARES med original case.
     *
     * Path bevares fordi GitHub-paths er case-sensitive:
     * `github.com/SHL-Digital-Practice/ai.sthetic` ≠ `…/shl-digital-practice/ai.sthetic`,
     * `SpeckleLCA` ≠ `specklelca`. Blind lowercasing ville kollapset ≥5 distinkte
     * github.com-verktøy (≥2 Champions) til ett.
     *
     * @param string $url Rå-URL.
     * @return string Normalisert identitetsnøkkel, eller tom streng hvis URL er ubrukelig.
     */
    function bv_aec_normalize_url($url) {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        // wp_parse_url krever et scheme for å finne host pålitelig; legg på ett midlertidig
        // hvis det mangler (kilden har alltid scheme, men vær robust).
        $parse_target = (strpos($url, '//') !== false) ? $url : 'http://' . ltrim($url, '/');
        $parts = wp_parse_url($parse_target);

        if (empty($parts['host'])) {
            return '';
        }

        // 5a: lowercase KUN hostname.
        $host = strtolower($parts['host']);

        // 2: strip ledende «www.».
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        // Drop eventuell port (ikke forventet i kilden, men deterministisk uansett).
        if (strpos($host, ':') !== false) {
            $host = substr($host, 0, strpos($host, ':'));
        }

        // 5b: PATH bevares med original case; 3: query + fragment droppes (ikke føyd på);
        // 4: trailing slash droppes.
        $path = isset($parts['path']) ? rtrim($parts['path'], '/') : '';

        return $host . $path;
    }
}

if (!function_exists('bv_aec_name_key')) {
    /**
     * Svak sekundær reconciliation-HINT basert på verktøynavn.
     *
     * IKKE en hard merge-nøkkel. Ved URL-miss + name_key-treff skal upserteren LOGGE
     * «mulig URL-endring, manuell vurdering» — aldri auto-merge.
     *
     * Normalisering: lowercase, trim, kollaps whitespace, strip parentetiske suffikser
     * (f.eks. «YouAi (MindStudio)» → «youai»), deretter sha1.
     *
     * @param string $name Verktøynavn.
     * @return string sha1-hash, eller tom streng hvis navnet er tomt.
     */
    function bv_aec_name_key($name) {
        $name = (string) $name;

        // Strip parentetiske suffikser: «Navn (noe)» → «Navn».
        $name = preg_replace('/\s*\([^)]*\)\s*$/u', '', $name);

        $name = strtolower(trim($name));

        // Kollaps all whitespace til ett mellomrom.
        $name = preg_replace('/\s+/u', ' ', $name);

        if ($name === '') {
            return '';
        }

        return sha1($name);
    }
}
