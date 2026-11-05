<?php
/**
 * Plugin Name: BIM Verdi - SEO-maler for titler og metabeskrivelser
 * Description: Titler og metabeskrivelser per sidetype (Rank Math), med reservetekst når feltet er tomt.
 * Version: 1.0.0
 *
 * SEO fase 2, Trello «SEO STATUS OG TILTAK» (Bård 05.11.2026).
 *
 * Gjelder bare enkeltsider av typene under. Har en side fått egen tittel
 * eller beskrivelse i Rank Math-boksen (meta rank_math_title /
 * rank_math_description), brukes den — malene overstyrer aldri manuelt arbeid.
 *
 *   Artikkel        «Tittel – BIM Verdi»                                  ingress → utdrag → brødtekst
 *   Verktøy         «Navn – digitalt verktøy for byggenæringen | BIM Verdi» kort → detaljert beskrivelse
 *   Kunnskapskilde  «Navn (utgiver) – BIM Verdi»                          kort beskrivelse → brødtekst
 *   Foretak         «Navn – deltaker/partner i BIM Verdi»                 kort beskrivelse → beskrivelse
 *   Arrangement     «Tittel – dato | BIM Verdi»                           dato, sted, formål → brødtekst
 *   Begrep          «Begrep – definisjon | BIM Verdi»                     kort definisjon → brødtekst
 *   Temagruppe      «Navn – BIM Verdi»                                    brødtekst
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Maks lengde på metabeskrivelse (Google viser ca. 155–160 tegn). */
const BV_SEO_MAKS_BESKRIVELSE = 155;

/** Gjeldende enkeltside av en type malene gjelder, ellers null. */
function bv_seo_aktuell_post() {
    static $typer = ['artikkel', 'verktoy', 'kunnskapskilde', 'foretak', 'arrangement', 'bv_begrep', 'theme_group'];
    if (!is_singular($typer)) {
        return null;
    }
    $post = get_queried_object();
    return ($post instanceof WP_Post) ? $post : null;
}

/** Ren tekst fra felt/HTML, kuttet ved ordgrense. */
function bv_seo_kort_tekst($tekst, $maks = BV_SEO_MAKS_BESKRIVELSE) {
    $tekst = wp_strip_all_tags(strip_shortcodes((string) $tekst), true);
    $tekst = trim(preg_replace('/\s+/u', ' ', html_entity_decode($tekst, ENT_QUOTES, 'UTF-8')));
    if ($tekst === '' || mb_strlen($tekst) <= $maks) {
        return $tekst;
    }
    $kutt = mb_substr($tekst, 0, $maks - 1);
    $mellomrom = mb_strrpos($kutt, ' ');
    if ($mellomrom !== false && $mellomrom > $maks * 0.6) {
        $kutt = mb_substr($kutt, 0, $mellomrom);
    }
    return rtrim($kutt, " ,.;:–-") . '…';
}

/** Første ikke-tomme metaverdi av nøklene. */
function bv_seo_forste_felt($post_id, array $nokler) {
    foreach ($nokler as $n) {
        $v = get_post_meta($post_id, $n, true);
        if (is_string($v) && trim(wp_strip_all_tags($v)) !== '') {
            return $v;
        }
    }
    return '';
}

/**
 * Arrangementsdato som «11. november 2026».
 * Feltet er lagret både som Ymd (nyere) og Y-m-d (eldre import).
 */
function bv_seo_arrangement_dato($post_id) {
    static $mnd = ['januar', 'februar', 'mars', 'april', 'mai', 'juni', 'juli', 'august', 'september', 'oktober', 'november', 'desember'];
    $raa = trim((string) get_post_meta($post_id, 'arrangement_dato', true));
    $d = DateTime::createFromFormat('!Ymd', $raa) ?: DateTime::createFromFormat('!Y-m-d', $raa);
    return $d ? $d->format('j') . '. ' . $mnd[(int) $d->format('n') - 1] . ' ' . $d->format('Y') : '';
}

/** Ren titteltekst uten HTML-entiteter (Rank Math escaper selv ved utskrift). */
function bv_seo_tittel(WP_Post $p) {
    return trim(html_entity_decode(wp_strip_all_tags($p->post_title), ENT_QUOTES, 'UTF-8'));
}

/** Har tittelen allerede en dato? (f.eks. «11.11.26:», «27. august '26», «2026») */
function bv_seo_tittel_har_dato($tittel) {
    return (bool) preg_match('/\d{1,2}\.\s?\d{1,2}\.|\d{1,2}\.\s?(jan|feb|mar|apr|mai|jun|jul|aug|sep|okt|nov|des)|\x27\d{2}|\b20\d{2}\b/iu', $tittel);
}

/** Tittel etter mal, eller null når siden ikke dekkes. */
function bv_seo_mal_tittel(WP_Post $p) {
    $t = bv_seo_tittel($p);
    switch ($p->post_type) {
        case 'artikkel':
        case 'theme_group':
            return "$t – BIM Verdi";
        case 'verktoy':
            return "$t – digitalt verktøy for byggenæringen | BIM Verdi";
        case 'kunnskapskilde':
            $utgiver = trim((string) get_post_meta($p->ID, 'utgiver', true));
            // Ikke gjenta utgiveren når den allerede står i tittelen.
            return ($utgiver !== '' && mb_stripos($t, $utgiver) === false)
                ? "$t ($utgiver) – BIM Verdi"
                : "$t – BIM Verdi";
        case 'foretak':
            $rolle = (string) get_post_meta($p->ID, 'bv_rolle', true);
            if (in_array($rolle, ['Deltaker', 'Prosjektdeltaker'], true)) {
                return "$t – deltaker i BIM Verdi";
            }
            if ($rolle === 'Partner') {
                return "$t – partner i BIM Verdi";
            }
            return "$t – BIM Verdi";
        case 'arrangement':
            $dato = bv_seo_arrangement_dato($p->ID);
            return ($dato && !bv_seo_tittel_har_dato($t)) ? "$t – $dato | BIM Verdi" : "$t | BIM Verdi";
        case 'bv_begrep':
            return "$t – definisjon | BIM Verdi";
    }
    return null;
}

/** Metabeskrivelse etter mal (tom streng = ingen). */
function bv_seo_mal_beskrivelse(WP_Post $p) {
    $innhold = $p->post_content;
    switch ($p->post_type) {
        case 'artikkel':
            $kilde = bv_seo_forste_felt($p->ID, ['artikkel_ingress']) ?: ($p->post_excerpt ?: $innhold);
            break;
        case 'verktoy':
            $kilde = bv_seo_forste_felt($p->ID, ['kort_beskrivelse', 'detaljert_beskrivelse']);
            break;
        case 'kunnskapskilde':
            $kilde = bv_seo_forste_felt($p->ID, ['kort_beskrivelse']) ?: $innhold;
            break;
        case 'foretak':
            $kilde = bv_seo_forste_felt($p->ID, ['kort_beskrivelse', 'beskrivelse']);
            break;
        case 'arrangement':
            $deler = array_filter([
                bv_seo_arrangement_dato($p->ID),
                trim((string) get_post_meta($p->ID, 'sted_by', true)),
            ]);
            $tema  = bv_seo_forste_felt($p->ID, ['formal_tema']) ?: $innhold;
            $kilde = ($deler ? 'Arrangement ' . implode(', ', $deler) . '. ' : '') . wp_strip_all_tags($tema);
            break;
        case 'bv_begrep':
            $kilde = bv_seo_forste_felt($p->ID, ['bvb_kort']) ?: $innhold;
            if (trim(wp_strip_all_tags($kilde)) === '') {
                // Begreper uten definisjonstekst: kort, sann reserve.
                $navn  = trim((string) get_post_meta($p->ID, 'bvb_fullt_navn', true)) ?: bv_seo_tittel($p);
                $kilde = "$navn – begrep i BIM Verdis begrepsliste for byggenæringen.";
            }
            break;
        case 'theme_group':
            $kilde = $innhold;
            break;
        default:
            return '';
    }
    return bv_seo_kort_tekst($kilde);
}

add_filter('rank_math/frontend/title', function ($title) {
    $p = bv_seo_aktuell_post();
    if (!$p || trim((string) get_post_meta($p->ID, 'rank_math_title', true)) !== '') {
        return $title;
    }
    return bv_seo_mal_tittel($p) ?? $title;
}, 20);

add_filter('rank_math/frontend/description', function ($desc) {
    $p = bv_seo_aktuell_post();
    if (!$p || trim((string) get_post_meta($p->ID, 'rank_math_description', true)) !== '') {
        return $desc;
    }
    $mal = bv_seo_mal_beskrivelse($p);
    return $mal !== '' ? $mal : $desc;
}, 20);
