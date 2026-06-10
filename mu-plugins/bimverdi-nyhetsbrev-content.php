<?php
/**
 * BIM Verdi - Nyhetsbrev innholds-spørringer (Fase 1, temanøytral)
 *
 * Henter de 6 innholdsseksjonene til nyhetsbrevet «Nytt & Nyttig fra BIM Verdi»:
 *   1. Siste 3 publiserte artikler
 *   2. Neste (kommende) arrangement
 *   3. Siste 3 verktøy/tjenester
 *   4. Siste 3 kunnskapskilder
 *   5. Siste 3 deltakere (foretak)
 *
 * Fase 1 er TEMANØYTRAL — ingen filtrering på temagrupper. Fase 2 vil filtrere
 * hver seksjon på mottakerens valgte `topic_interests`.
 *
 * Hver seksjon returnerer en normalisert array av items:
 *   [ 'tittel', 'av', 'av_url', 'utdrag', 'lenke', 'meta' ]
 *
 * Plan: docs/plans/2026-06-03-001-feat-nyhetsbrev-mal-og-utsendelse-plan.md (1B)
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Formater en dato med norske månedsnavn — uavhengig av installert språkpakke.
 *
 * Siten kjører get_locale()='en_US' (ingen nb_NO-pakke), så date_i18n('j. F Y')
 * gir engelske måneder («9. June 2026»). Dette gir alltid bokmål («9. juni 2026»).
 *
 * @param int|string|null $timestamp Unix-ts, dato-streng (strtotime), eller null = nå (lokal tid).
 * @param bool            $med_tid   Legg på « H:i».
 * @return string
 */
function bimverdi_nyhetsbrev_dato_nb($timestamp = null, $med_tid = false) {
    if ($timestamp === null) {
        $timestamp = current_time('timestamp');
    } elseif (!is_numeric($timestamp)) {
        $timestamp = strtotime((string) $timestamp);
    }
    if (!$timestamp) {
        return '';
    }
    $mnd = [
        1 => 'januar', 2 => 'februar', 3 => 'mars', 4 => 'april',
        5 => 'mai', 6 => 'juni', 7 => 'juli', 8 => 'august',
        9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'desember',
    ];
    $ut = (int) date('j', $timestamp) . '. ' . $mnd[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
    if ($med_tid) {
        $ut .= ' ' . date('H:i', $timestamp);
    }
    return $ut;
}

/**
 * Trim fritekst til en kort, e-postvennlig ingress (≈3 linjer).
 */
function bimverdi_nyhetsbrev_tekst($raw, $words = 28) {
    if (empty($raw)) {
        return '';
    }
    $clean = wp_strip_all_tags($raw);
    // Avkod HTML-entiteter (f.eks. &amp;, &#8211;) — malen re-escaper med esc_html().
    $clean = html_entity_decode($clean, ENT_QUOTES, 'UTF-8');
    $clean = trim(preg_replace('/\s+/', ' ', $clean));
    return wp_trim_words($clean, $words, '…');
}

/**
 * Plain-text-utgave av en tittel/navn. get_the_title() / ACF kan returnere
 * entiteter (wptexturize: «–» → &#8211;); vi avkoder her og lar malen escape,
 * for å unngå dobbel-escaping (&amp;#8211;).
 */
function bimverdi_nyhetsbrev_plain($str) {
    return trim(html_entity_decode(wp_strip_all_tags((string) $str), ENT_QUOTES, 'UTF-8'));
}

/**
 * Hent visningsnavn for en bruker — aldri user_login (jf. B-020: brukernavn
 * vises ikke offentlig). Returnerer tomt hvis vi bare har et brukernavn.
 */
function bimverdi_nyhetsbrev_person_navn($user_id) {
    $user_id = (int) $user_id;
    if (!$user_id) {
        return '';
    }
    $first = get_the_author_meta('first_name', $user_id);
    $last  = get_the_author_meta('last_name', $user_id);
    $navn  = trim($first . ' ' . $last);
    if ($navn !== '') {
        return $navn;
    }
    // display_name kan være satt til et ekte navn — men bruk det kun hvis det
    // ikke er identisk med user_login (som ikke skal eksponeres).
    $display = get_the_author_meta('display_name', $user_id);
    $login   = get_the_author_meta('user_login', $user_id);
    if ($display && strcasecmp($display, $login) !== 0) {
        return $display;
    }
    return '';
}

/**
 * Normaliser et ACF post_object / ID til [navn, url] for et foretak.
 */
function bimverdi_nyhetsbrev_foretak($foretak) {
    if (is_object($foretak) && isset($foretak->ID)) {
        $foretak = $foretak->ID;
    }
    $foretak_id = (int) $foretak;
    if (!$foretak_id || get_post_type($foretak_id) !== 'foretak') {
        return ['navn' => '', 'url' => ''];
    }
    return [
        'navn' => bimverdi_nyhetsbrev_plain(get_the_title($foretak_id)),
        'url'  => get_permalink($foretak_id),
    ];
}

/**
 * Bygg en «skrevet av»-byline: «Navn, Foretak» / «Foretak» / «Navn» / ''.
 */
function bimverdi_nyhetsbrev_byline($person_navn, $foretak_navn) {
    $deler = array_filter([trim($person_navn), trim($foretak_navn)]);
    return implode(', ', $deler);
}

/**
 * Normaliser en ACF-bildeverdi (array / attachment-ID / URL) til en absolutt URL.
 * Tåler alle ACF return_format-varianter.
 */
function bimverdi_nyhetsbrev_acf_bilde_url($val, $size = 'medium') {
    if (empty($val)) {
        return '';
    }
    if (is_array($val)) {
        if (!empty($val['sizes'][$size])) {
            return $val['sizes'][$size];
        }
        return !empty($val['url']) ? $val['url'] : '';
    }
    if (is_numeric($val)) {
        $url = wp_get_attachment_image_url((int) $val, $size);
        return $url ?: '';
    }
    if (is_string($val)) {
        return $val; // Allerede en URL.
    }
    return '';
}

/**
 * Finn beste tilgjengelige bilde for en post, med CPT-spesifikk fallback-kjede.
 * Returnerer ['url' => absolutt URL|'', 'type' => 'featured'|'logo'|'none'].
 *
 * Datadekning (localhost juni 2026): artikkel ~97% featured, foretak 0% featured
 * men 73% ACF-logo, verktøy/kunnskapskilde/arrangement sparsomt → «vis hvis finnes».
 */
function bimverdi_nyhetsbrev_bilde($post_id, $cpt, $size = 'medium') {
    // 1. Fremhevet bilde (alle CPT-er).
    $url = get_the_post_thumbnail_url($post_id, $size);
    if ($url) {
        return ['url' => $url, 'type' => 'featured'];
    }

    // 2. CPT-spesifikke fallbacks.
    if ($cpt === 'verktoy') {
        $url = bimverdi_nyhetsbrev_acf_bilde_url(get_field('verktoy_logo', $post_id), $size);
        if (!$url) {
            $eier = get_field('eier_leverandor', $post_id);
            $eier_id = is_object($eier) ? ($eier->ID ?? 0) : (int) $eier;
            if ($eier_id) {
                $url = bimverdi_nyhetsbrev_acf_bilde_url(get_field('logo', $eier_id), $size);
            }
        }
        if ($url) {
            return ['url' => $url, 'type' => 'logo'];
        }
    }

    if ($cpt === 'foretak') {
        $url = bimverdi_nyhetsbrev_acf_bilde_url(get_field('logo', $post_id), $size);
        if ($url) {
            return ['url' => $url, 'type' => 'logo'];
        }
    }

    return ['url' => '', 'type' => 'none'];
}

/**
 * Hent nye OG sist oppdaterte poster per seksjon (Bård 10.06):
 *
 * Én spørring sortert på post_modified DESC — en fersk oppdatering er like
 * nyhetsverdig som en ny registrering (seksjonene heter «Nye og sist
 * oppdaterte …»). Nypubliserte poster har post_modified = post_date og
 * sorteres dermed naturlig inn.
 *
 * Badge per post (->bv_nb_status):
 *   'ny'        — post_date ≈ post_modified (endret < 24 t etter publisering,
 *                 dvs. nyregistrert og ikke vesentlig redigert siden)
 *   'oppdatert' — redigert senere enn det (eksisterende post med ny aktivitet)
 *
 * @return WP_Post[]
 */
function bimverdi_nyhetsbrev_hent_nytt_og_oppdatert($post_type, $limit, $extra = []) {
    $poster = get_posts(array_merge([
        'post_type'           => $post_type,
        'post_status'         => 'publish',
        'posts_per_page'      => $limit,
        'orderby'             => 'modified',
        'order'               => 'DESC',
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
        'suppress_filters'    => false,
    ], $extra));

    foreach ($poster as $p) {
        $alder_for_endring = strtotime($p->post_modified) - strtotime($p->post_date);
        $p->bv_nb_status = ($alder_for_endring < DAY_IN_SECONDS) ? 'ny' : 'oppdatert';
    }

    return $poster;
}

/**
 * Totaltall til topp-headeren (Bård-krav 09.06: ressurs-oversikt som
 * publiseringsstimulering — «de siste 3 av X»).
 */
function bimverdi_nyhetsbrev_totaler() {
    $typer = [
        ['cpt' => 'verktoy',        'label' => 'verktøy'],
        ['cpt' => 'kunnskapskilde', 'label' => 'kunnskapskilder'],
        ['cpt' => 'artikkel',       'label' => 'artikler'],
        ['cpt' => 'foretak',        'label' => 'deltakere'],
    ];
    $ut = ['sum' => 0, 'typer' => []];
    foreach ($typer as $t) {
        $antall = (int) wp_count_posts($t['cpt'])->publish;
        $ut['typer'][] = ['label' => $t['label'], 'antall' => $antall];
        $ut['sum']    += $antall;
    }
    return $ut;
}

/**
 * 1. Siste N publiserte artikler.
 */
function bimverdi_nyhetsbrev_artikler($limit = 3) {
    $poster = bimverdi_nyhetsbrev_hent_nytt_og_oppdatert('artikkel', $limit);

    $items = [];
    foreach ($poster as $idx => $post) {
        $id = $post->ID;
        $is_hero = ($idx === 0); // Toppartikkel = hero (stort bilde øverst).

        // Foretak: eksplisitt felt, ellers utledet fra forfatterens user meta.
        $bedrift = get_field('artikkel_bedrift', $id);
        $author_id = (int) $post->post_author;
        if (empty($bedrift) && $author_id) {
            $bedrift = get_user_meta($author_id, 'bimverdi_company_id', true)
                ?: get_user_meta($author_id, 'bim_verdi_company_id', true)
                ?: get_field('tilknyttet_foretak', 'user_' . $author_id);
        }
        $foretak = bimverdi_nyhetsbrev_foretak($bedrift);

        $ingress = get_field('artikkel_ingress', $id);
        if (empty($ingress)) {
            $ingress = has_excerpt($id) ? get_the_excerpt($id) : $post->post_content;
        }

        $bilde = bimverdi_nyhetsbrev_bilde($id, 'artikkel', $is_hero ? 'large' : 'medium');

        $items[] = [
            'tittel' => bimverdi_nyhetsbrev_plain(get_the_title($id)),
            'av'     => bimverdi_nyhetsbrev_byline(
                bimverdi_nyhetsbrev_person_navn($author_id),
                $foretak['navn']
            ),
            'av_url'     => $foretak['url'],
            'utdrag'     => bimverdi_nyhetsbrev_tekst($ingress, $is_hero ? 30 : 18),
            'lenke'      => get_permalink($id),
            'meta'       => '',
            'bilde'      => $bilde['url'],
            'bilde_type' => $bilde['type'],
            'hero'       => $is_hero,
            'status'     => $post->bv_nb_status ?? '',
        ];
    }
    wp_reset_postdata();
    return $items;
}

/**
 * 2. Neste (kommende) arrangement — nærmeste fremtidige dato.
 */
function bimverdi_nyhetsbrev_neste_arrangement() {
    $today = current_time('Y-m-d');

    $q = new WP_Query([
        'post_type'      => 'arrangement',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_key'       => 'arrangement_dato',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'no_found_rows'  => true,
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => 'arrangement_status_toggle',
                'value'   => 'kommende',
                'compare' => '=',
            ],
            [
                'key'     => 'arrangement_dato',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ],
        ],
    ]);

    if (empty($q->posts)) {
        wp_reset_postdata();
        return [];
    }

    $post = $q->posts[0];
    $id   = $post->ID;

    $dato = get_field('arrangement_dato', $id);
    $sted = get_field('sted_by', $id);
    $arrangor = get_field('arrangor', $id);
    $beskrivelse = get_field('formal_tema', $id) ?: $post->post_content;

    $meta_deler = array_filter([
        $dato ? bimverdi_nyhetsbrev_dato_nb($dato) : '',
        $sted,
    ]);

    // Arrangement er noe av det viktigste i brevet — vis som hero (stort
    // bilde + fokus) når bilde finnes. Uten bilde: kompakt rad m/ dato.
    $bilde = bimverdi_nyhetsbrev_bilde($id, 'arrangement', 'large');

    wp_reset_postdata();

    return [[
        'tittel'     => bimverdi_nyhetsbrev_plain(get_the_title($id)),
        'av'         => $arrangor ? bimverdi_nyhetsbrev_plain($arrangor) : '',
        'av_url'     => '',
        'utdrag'     => bimverdi_nyhetsbrev_tekst($beskrivelse),
        'lenke'      => get_permalink($id),
        'meta'       => implode(' · ', $meta_deler),
        'bilde'      => $bilde['url'],
        'bilde_type' => $bilde['type'],
        'hero'       => !empty($bilde['url']),
    ]];
}

/**
 * 3. Siste N verktøy/tjenester.
 */
function bimverdi_nyhetsbrev_verktoy($limit = 3) {
    $poster = bimverdi_nyhetsbrev_hent_nytt_og_oppdatert('verktoy', $limit);

    $items = [];
    foreach ($poster as $post) {
        $id = $post->ID;
        $foretak = bimverdi_nyhetsbrev_foretak(get_field('eier_leverandor', $id));
        $bilde   = bimverdi_nyhetsbrev_bilde($id, 'verktoy', 'medium');
        $items[] = [
            'tittel'     => bimverdi_nyhetsbrev_plain(get_the_title($id) ?: get_field('verktoy_navn', $id)),
            'av'         => $foretak['navn'],
            'av_url'     => $foretak['url'],
            'utdrag'     => bimverdi_nyhetsbrev_tekst(get_field('kort_beskrivelse', $id), 18),
            'lenke'      => get_permalink($id),
            'meta'       => '',
            'bilde'      => $bilde['url'],
            'bilde_type' => $bilde['type'],
            'hero'       => false,
            'status'     => $post->bv_nb_status ?? '',
        ];
    }
    wp_reset_postdata();
    return $items;
}

/**
 * 4. Siste N kunnskapskilder.
 */
function bimverdi_nyhetsbrev_kunnskapskilder($limit = 3) {
    $poster = bimverdi_nyhetsbrev_hent_nytt_og_oppdatert('kunnskapskilde', $limit);

    $items = [];
    foreach ($poster as $post) {
        $id = $post->ID;
        $foretak = bimverdi_nyhetsbrev_foretak(get_field('tilknyttet_bedrift', $id));
        $person  = bimverdi_nyhetsbrev_person_navn(get_field('registrert_av', $id));
        $bilde   = bimverdi_nyhetsbrev_bilde($id, 'kunnskapskilde', 'medium');
        $items[] = [
            'tittel'     => bimverdi_nyhetsbrev_plain(get_the_title($id) ?: get_field('kunnskapskilde_navn', $id)),
            'av'         => bimverdi_nyhetsbrev_byline($person, $foretak['navn']),
            'av_url'     => $foretak['url'],
            'utdrag'     => bimverdi_nyhetsbrev_tekst(get_field('kort_beskrivelse', $id), 18),
            'lenke'      => get_permalink($id),
            'meta'       => '',
            'bilde'      => $bilde['url'],
            'bilde_type' => $bilde['type'],
            'hero'       => false,
            'status'     => $post->bv_nb_status ?? '',
        ];
    }
    wp_reset_postdata();
    return $items;
}

/**
 * 5. Siste N deltakere (foretak).
 */
function bimverdi_nyhetsbrev_deltakere($limit = 3) {
    $poster = bimverdi_nyhetsbrev_hent_nytt_og_oppdatert('foretak', $limit);

    $items = [];
    foreach ($poster as $post) {
        $id = $post->ID;
        $bilde = bimverdi_nyhetsbrev_bilde($id, 'foretak', 'medium');
        $items[] = [
            'tittel'     => bimverdi_nyhetsbrev_plain(get_the_title($id)),
            'av'         => '',
            'av_url'     => '',
            'utdrag'     => bimverdi_nyhetsbrev_tekst(get_field('kort_beskrivelse', $id), 18),
            'lenke'      => get_permalink($id),
            'meta'       => '',
            'bilde'      => $bilde['url'],
            'bilde_type' => $bilde['type'],
            'hero'       => false,
            'status'     => $post->bv_nb_status ?? '',
        ];
    }
    wp_reset_postdata();
    return $items;
}

/**
 * Samle alle seksjonene i én struktur til malen.
 */
function bimverdi_nyhetsbrev_collect() {
    // «Se alle»-lenker per seksjon (Bård-krav 09.06: «dette er nyeste — se også
    // våre X andre [posttype]»). Arkivlenke + totalantall publiserte.
    $arkiv = function ($cpt, $enhet) {
        return [
            'total'     => (int) wp_count_posts($cpt)->publish,
            'arkiv_url' => get_post_type_archive_link($cpt) ?: '',
            'enhet'     => $enhet,
        ];
    };

    return [
        'generert'  => bimverdi_nyhetsbrev_dato_nb(),
        'totaler'   => bimverdi_nyhetsbrev_totaler(),
        'seksjoner' => [
            array_merge([
                'noekkel' => 'artikler',
                'tittel'  => 'Siste artikler',
                'items'   => bimverdi_nyhetsbrev_artikler(3),
            ], $arkiv('artikkel', 'artikler')),
            array_merge([
                'noekkel' => 'arrangement',
                'tittel'  => 'Neste arrangement',
                'items'   => bimverdi_nyhetsbrev_neste_arrangement(),
            ], $arkiv('arrangement', 'arrangementer')),
            array_merge([
                'noekkel' => 'verktoy',
                'tittel'  => 'Nye og sist oppdaterte verktøy og tjenester',
                'items'   => bimverdi_nyhetsbrev_verktoy(3),
            ], $arkiv('verktoy', 'verktøy')),
            array_merge([
                'noekkel' => 'kunnskapskilder',
                'tittel'  => 'Nye og sist oppdaterte kunnskapskilder',
                'items'   => bimverdi_nyhetsbrev_kunnskapskilder(3),
            ], $arkiv('kunnskapskilde', 'kunnskapskilder')),
            array_merge([
                'noekkel' => 'deltakere',
                'tittel'  => 'Nye og sist oppdaterte deltakere',
                'items'   => bimverdi_nyhetsbrev_deltakere(3),
            ], $arkiv('foretak', 'deltakere')),
        ],
    ];
}

/**
 * Rendre nyhetsbrev-malen til en HTML-streng.
 *
 * Gjenbrukes av forhåndsvisningen (fase 1) og send-motoren (senere).
 * Malen ligger i temaet: parts/email/nyhetsbrev.php og leser $data + $context.
 *
 * @param array|null $data    Innhold fra bimverdi_nyhetsbrev_collect().
 * @param array      $context Per-mottaker-kontekst (lenker, navn) — fylles av send-motoren.
 * @return string HTML.
 */
function bimverdi_render_nyhetsbrev($data = null, $context = []) {
    if ($data === null) {
        $data = bimverdi_nyhetsbrev_collect();
    }

    $context = wp_parse_args($context, [
        'profil_url'      => home_url('/min-side/profil/rediger/'),
        'avmelding_url'   => '#',
        'avsender_navn'   => 'Bård Krogshus',
        'avsender_tittel' => 'BIM Verdi',
        'nettsted_url'    => home_url('/'),
    ]);

    $tpl = locate_template('parts/email/nyhetsbrev.php');
    if (!$tpl) {
        return '';
    }

    ob_start();
    include $tpl; // $data og $context er i scope i malen.
    return ob_get_clean();
}
