<?php
/**
 * AEC AI Hub — frontend-hjelpere (AI-badge, attribusjon, Kilde-verdi).
 *
 * Rene presentasjons-funksjoner kalt fra temaets verktøy-maler (archive-verktoy.php,
 * single-verktoy.php). Definerer ingen hooks og har ingen side-effekter.
 *
 * Sentrale invarianter (Decision 2/3, R12):
 *   - AI-badge vises KUN når `(string) _bv_aec_ai_driven === '1'` — aldri utledet fra
 *     `_bv_aec_source` (gir alle 238) eller kategori. Managed ikke-AI = `'0'` → ingen badge;
 *     deltaker-verktøy = nøkkel fraværende → ingen badge (distinkte tilstander, samme utfall).
 *   - Attribusjon vises når `_bv_aec_source` er satt → ALLE synkede (238/236), uavhengig av ai_driven.
 *   - Kilde-filteret filtrerer på `_bv_aec_source`-tilstedeværelse (klient-side), ikke ai_driven.
 *
 * @package BIMVerdiCore
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Attribusjonslenke (offisiell hub-URL). */
if (!defined('BV_AIHUB_ATTRIBUTION_URL')) {
    define('BV_AIHUB_ATTRIBUTION_URL', 'https://aiinaec.com');
}

if (!function_exists('bv_aec_is_ai_driven')) {
    /**
     * Er verktøyet AI-drevet? String-sjekk mot `'1'` (get_post_meta gir aldri PHP-bool).
     *
     * @param int $post_id
     * @return bool
     */
    function bv_aec_is_ai_driven($post_id) {
        return (string) get_post_meta($post_id, '_bv_aec_ai_driven', true) === '1';
    }
}

if (!function_exists('bv_aec_source_label')) {
    /**
     * Rå `_bv_aec_source`-etikett (tom hvis ikke synket).
     *
     * @param int $post_id
     * @return string
     */
    function bv_aec_source_label($post_id) {
        return (string) get_post_meta($post_id, '_bv_aec_source', true);
    }
}

if (!function_exists('bv_aec_is_synced')) {
    /**
     * Er verktøyet eksternt synket (har `_bv_aec_source`)? Driver attribusjon + Kilde-filter.
     *
     * @param int $post_id
     * @return bool
     */
    function bv_aec_is_synced($post_id) {
        return bv_aec_source_label($post_id) !== '';
    }
}

if (!function_exists('bv_aec_kilde_value')) {
    /**
     * Klient-side Kilde-filterverdi: 'aec_ai_hub' for synkede, ellers 'medlem'.
     *
     * @param int $post_id
     * @return string
     */
    function bv_aec_kilde_value($post_id) {
        return bv_aec_is_synced($post_id) ? 'aec_ai_hub' : 'medlem';
    }
}

if (!function_exists('bv_aec_ai_badge_markup')) {
    /**
     * AI-badge-markup (bruker bimverdi_badge hvis tilgjengelig, ellers en CSS-ekvivalent span).
     *
     * @return string
     */
    function bv_aec_ai_badge_markup() {
        if (function_exists('bimverdi_badge')) {
            ob_start();
            bimverdi_badge(array('text' => 'AI', 'color' => 'blue'));
            return trim((string) ob_get_clean());
        }
        return '<span class="inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full bg-[#DBEAFE] text-[#1E40AF]">AI</span>';
    }
}

if (!function_exists('bv_aec_attribution_html')) {
    /**
     * Attribusjons-HTML: «Kilde: AEC AI Hub by Stjepan Mikulić» (UTF-8 «ć»), lenket med
     * rel="nofollow noreferrer noopener". `compact` viser kort navn (kort-/listevisning).
     *
     * @param string $context 'full' | 'compact'
     * @return string
     */
    function bv_aec_attribution_html($context = 'full') {
        $name = ($context === 'compact') ? 'AEC AI Hub' : 'AEC AI Hub by Stjepan Mikulić';
        return 'Kilde: <a href="' . esc_url(BV_AIHUB_ATTRIBUTION_URL) . '"'
            . ' target="_blank" rel="nofollow noreferrer noopener"'
            . ' title="AEC AI Hub by Stjepan Mikulić" class="hover:underline">'
            . esc_html($name) . '</a>';
    }
}
