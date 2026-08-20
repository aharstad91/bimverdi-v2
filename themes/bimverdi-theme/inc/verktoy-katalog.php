<?php
/**
 * Verktøykatalog — delt spørre-, filter- og tellelogikk + AJAX-endepunkt.
 *
 * BAKGRUNN (19.08.2026): `archive-verktoy.php` hentet ALLE publiserte verktøy
 * (`posts_per_page => -1`), rendret hvert enkelt kort i HTML og filtrerte klient-side ved
 * å skjule kort. Med 223 verktøy var siden ~1 MB. Etter AEC AI Hub-importen (1564
 * publiserte verktøy) ble den **7,9 MB** og ubrukelig på mobil.
 *
 * Nå: serveren filtrerer og paginerer (24 per side), og filterendringer henter én ny side
 * via AJAX. Samme kodevei brukes av arkivmalen og AJAX-endepunktet, så markup og tall er
 * garantert identiske uansett hvordan siden ble til.
 *
 * DESIGNVALG — hvorfor et forhåndsbygget indeks-sett i stedet for meta_query/tax_query:
 *
 *   1. Temagruppe er DUAL-SOURCE. AEC-importerte verktøy har ekte `temagruppe`-termer;
 *      deltakerregistrerte har (ofte kun) ACF-feltet `formaalstema` med gamle kortnøkler.
 *      Filteret må treffe unionen, og WP_Query kan ikke uttrykke «taksonomi ELLER meta»
 *      i én spørring.
 *   2. Fasett-tellingene MÅ stemme med hva filteret faktisk viser. Den gamle koden løkket
 *      over alle publiserte verktøy og kalte `wp_get_post_terms()` + `get_field()` per post
 *      — ~3000 spørringer per sidevisning. Indekset gjør det i fire.
 *   3. Søket skal fortsatt treffe tittel + leverandørnavn (som klient-side-søket gjorde),
 *      ikke WordPress' `s` (som leter i post_content — tomt på synkede verktøy — og ikke
 *      kjenner leverandørnavnet i det hele tatt).
 *
 * Indekset bygges én gang per request og caches ikke: fire spørringer + array-bygging over
 * ~1500 rader er raskere enn transient-invalidering er verdt, og kan ikke bli utdatert.
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Verktøy per side/pulje. */
if (!defined('BV_VERKTOY_PER_PAGE')) {
    define('BV_VERKTOY_PER_PAGE', 24);
}

/**
 * Temagruppe-fasetten: Bårds 6 offisielle termer i fast merkevare-rekkefølge, med
 * «Andre kategorier» sist. Navn hentes fra taksonomien (ett sannhetspunkt).
 *
 * «Andre kategorier» samler AEC AI Hub-verktøy hvis kildekategori ikke finnes i
 * matrisen (Assistant, Learning, AR/VR/MR, News). Bård besluttet 20.08.2026 at de
 * skal være en synlig gruppe; sist i rekkefølgen fordi den ikke er en merkevare-
 * temagruppe. Fasetten faller stille bort hvis termen ikke finnes i miljøet.
 *
 * @return array slug => visningsnavn
 */
function bv_verktoy_temagruppe_options() {
    static $options = null;
    if ($options !== null) {
        return $options;
    }

    $order   = array('byggesaksbim', 'prosjektbim', 'eiendomsbim', 'miljobim', 'sirkbim', 'bimtech', 'andre-kategorier');
    $by_slug = array();
    $terms   = get_terms(array('taxonomy' => 'temagruppe', 'hide_empty' => false));
    if (!is_wp_error($terms)) {
        foreach ($terms as $t) {
            $by_slug[$t->slug] = $t->name;
        }
    }

    $options = array();
    foreach ($order as $slug) {
        if (isset($by_slug[$slug])) {
            $options[$slug] = $by_slug[$slug];
        }
    }

    return $options;
}

/**
 * Type-fasetten. Verdiene er ACF-etiketter; nøkkelen brukes i URL-en.
 *
 * Gjelder hele katalogen. Hub-verktøyene hadde ingen Type-verdi fram til 20.08.2026, da
 * Bård bestemte at alle skal stå som «Programvare» — se BV_AIHUB_Tool_Upserter::set_type_ressurs().
 * Det opphever avgjørelsen 19.08 om å avgrense fasetten til deltakerverktøy
 * (docs/plans/2026-08-19-001-…-plan.md punkt 6).
 *
 * @return array key => label
 */
function bv_verktoy_type_options() {
    return array(
        'Programvare'      => 'Programvare',
        'Standard'         => 'Standard',
        'Metodikk'         => 'Metodikk',
        'Veileder'         => 'Veileder',
        'Nettside'         => 'Nettside',
        'Digital_tjeneste' => 'Digital tjeneste',
    );
}

/**
 * Kilde-fasetten (to synlige pills). Intern nøkkel «medlem» beholdes for URL-kompatibilitet.
 *
 * @return array key => label
 */
function bv_verktoy_kilde_options() {
    return array(
        'aec_ai_hub' => 'AEC AI Hub',
        'medlem'     => 'Deltakerregistrerte verktøy',
    );
}

/**
 * Legacy `formaalstema`-kortnøkkel → temagruppe-slug.
 * «bimtech» har ingen legacy-nøkkel; deltakerverktøy kan bare få den via taksonomien.
 *
 * @return array
 */
function bv_verktoy_legacy_formaal_map() {
    return array(
        'byggesak'          => 'byggesaksbim',
        'prosjekt'          => 'prosjektbim',
        'prosjektutvikling' => 'prosjektbim',
        'eiendom'           => 'eiendomsbim',
        'miljo'             => 'miljobim',
        'sirk'              => 'sirkbim',
    );
}

/**
 * Bygg indekset over alle publiserte verktøy (fire spørringer).
 *
 * @return array {
 *     ids    => int[]                      alle publiserte verktøy-ID-er, tittelsortert
 *     title  => [pid => tittel]
 *     eier   => [pid => leverandørnavn]    tom streng når ukjent
 *     tema   => [pid => slug[]]            union av taksonomi + legacy-meta
 *     type   => [pid => key[]]
 *     kilde  => [pid => 'aec_ai_hub'|'medlem']
 * }
 */
function bv_verktoy_index() {
    static $index = null;
    if ($index !== null) {
        return $index;
    }

    global $wpdb;

    $valid_slugs = array_keys(bv_verktoy_temagruppe_options());
    $legacy_map  = bv_verktoy_legacy_formaal_map();
    $type_opts   = bv_verktoy_type_options();

    // 1) Alle publiserte verktøy, tittelsortert (rekkefølgen som fasetten og sidene bruker).
    $rows = $wpdb->get_results(
        "SELECT ID, post_title FROM {$wpdb->posts}
         WHERE post_type = 'verktoy' AND post_status = 'publish'
         ORDER BY post_title ASC",
        ARRAY_A
    );

    $ids   = array();
    $title = array();
    foreach ($rows as $r) {
        $pid           = (int) $r['ID'];
        $ids[]         = $pid;
        $title[$pid]   = (string) $r['post_title'];
    }

    $index = array(
        'ids'   => $ids,
        'title' => $title,
        'eier'  => array(),
        'tema'  => array(),
        'type'  => array(),
        'kilde' => array(),
    );

    if (empty($ids)) {
        return $index;
    }

    $in = implode(',', array_map('intval', $ids));

    // 2) Temagruppe fra taksonomien.
    $tax_rows = $wpdb->get_results(
        "SELECT tr.object_id AS pid, t.slug AS slug
         FROM {$wpdb->term_relationships} tr
         INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
         INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
         WHERE tt.taxonomy = 'temagruppe' AND tr.object_id IN ({$in})",
        ARRAY_A
    );
    foreach ($tax_rows as $r) {
        $pid  = (int) $r['pid'];
        $slug = (string) $r['slug'];
        if (!in_array($slug, $valid_slugs, true)) {
            continue; // «Ukategorisert» og ukjente termer er ikke fasett-verdier
        }
        if (!isset($index['tema'][$pid])) {
            $index['tema'][$pid] = array();
        }
        if (!in_array($slug, $index['tema'][$pid], true)) {
            $index['tema'][$pid][] = $slug;
        }
    }

    // 3) Meta i én spørring: legacy tema, type, kilde-markør og eier-referanse.
    $meta_rows = $wpdb->get_results(
        "SELECT post_id AS pid, meta_key AS mkey, meta_value AS mval
         FROM {$wpdb->postmeta}
         WHERE post_id IN ({$in})
           AND meta_key IN ('formaalstema', 'type_ressurs', '_bv_aec_source', 'eier_leverandor')",
        ARRAY_A
    );

    $eier_refs = array(); // pid => foretak-post-id
    foreach ($meta_rows as $r) {
        $pid  = (int) $r['pid'];
        $key  = (string) $r['mkey'];
        $val  = maybe_unserialize($r['mval']);

        if ($key === 'formaalstema') {
            foreach ((is_array($val) ? $val : array($val)) as $v) {
                $k = strtolower(trim((string) $v));
                if (!isset($legacy_map[$k])) {
                    continue;
                }
                $slug = $legacy_map[$k];
                if (!isset($index['tema'][$pid])) {
                    $index['tema'][$pid] = array();
                }
                if (!in_array($slug, $index['tema'][$pid], true)) {
                    $index['tema'][$pid][] = $slug;
                }
            }
            continue;
        }

        if ($key === 'type_ressurs') {
            // Samme substring-matching som malen brukte før, slik at tellingene ikke flytter seg.
            $str = is_array($val) ? implode(', ', $val) : (string) $val;
            foreach ($type_opts as $opt_key => $label) {
                if (stripos($str, $label) !== false || stripos($str, str_replace('_', ' ', $opt_key)) !== false) {
                    if (!isset($index['type'][$pid])) {
                        $index['type'][$pid] = array();
                    }
                    if (!in_array($opt_key, $index['type'][$pid], true)) {
                        $index['type'][$pid][] = $opt_key;
                    }
                }
            }
            continue;
        }

        if ($key === '_bv_aec_source') {
            if ((string) $val !== '') {
                $index['kilde'][$pid] = 'aec_ai_hub';
            }
            continue;
        }

        if ($key === 'eier_leverandor' && (int) $val > 0) {
            $eier_refs[$pid] = (int) $val;
        }
    }

    // Alt uten AEC-markør er deltakerregistrert.
    foreach ($ids as $pid) {
        if (!isset($index['kilde'][$pid])) {
            $index['kilde'][$pid] = 'medlem';
        }
    }

    // 4) Leverandørnavn (søket treffer både tittel og leverandør, som før).
    if (!empty($eier_refs)) {
        $eier_in    = implode(',', array_map('intval', array_unique(array_values($eier_refs))));
        $eier_rows  = $wpdb->get_results(
            "SELECT ID, post_title FROM {$wpdb->posts} WHERE ID IN ({$eier_in})",
            ARRAY_A
        );
        $eier_names = array();
        foreach ($eier_rows as $r) {
            $eier_names[(int) $r['ID']] = (string) $r['post_title'];
        }
        foreach ($eier_refs as $pid => $fid) {
            if (isset($eier_names[$fid])) {
                $index['eier'][$pid] = $eier_names[$fid];
            }
        }
    }

    return $index;
}

/**
 * Les og saniter filterparametere.
 *
 * @param array|null $src Kilde (default $_GET). AJAX sender samme nøkler.
 * @return array {search:string, temagruppe:string[], type:string[], kilde:string[], paged:int}
 */
function bv_verktoy_katalog_filters($src = null) {
    $src = is_array($src) ? $src : $_GET;

    $pick = function ($key, array $allowed) use ($src) {
        if (!isset($src[$key])) {
            return array();
        }
        $vals = is_array($src[$key]) ? $src[$key] : array($src[$key]);
        $out  = array();
        foreach ($vals as $v) {
            $v = sanitize_text_field((string) $v);
            if (in_array($v, $allowed, true) && !in_array($v, $out, true)) {
                $out[] = $v;
            }
        }
        return $out;
    };

    $paged = isset($src['paged']) ? (int) $src['paged'] : (int) get_query_var('paged');

    return array(
        'search'     => isset($src['s']) ? sanitize_text_field((string) $src['s']) : '',
        'temagruppe' => $pick('temagruppe', array_keys(bv_verktoy_temagruppe_options())),
        'type'       => $pick('type_ressurs', array_keys(bv_verktoy_type_options())),
        'kilde'      => $pick('kilde', array_keys(bv_verktoy_kilde_options())),
        'paged'      => max(1, $paged),
    );
}

/**
 * ID-ene som matcher filtrene, i tittelrekkefølge.
 *
 * Innenfor én fasett er logikken ELLER (som klient-side-filteret), mellom fasetter OG.
 *
 * @param array $filters Fra bv_verktoy_katalog_filters().
 * @return int[]
 */
function bv_verktoy_matching_ids(array $filters) {
    $index  = bv_verktoy_index();
    $search = mb_strtolower(trim($filters['search']));

    $out = array();
    foreach ($index['ids'] as $pid) {
        if (!empty($filters['temagruppe'])) {
            $tema = isset($index['tema'][$pid]) ? $index['tema'][$pid] : array();
            if (empty(array_intersect($filters['temagruppe'], $tema))) {
                continue;
            }
        }
        if (!empty($filters['type'])) {
            $type = isset($index['type'][$pid]) ? $index['type'][$pid] : array();
            if (empty(array_intersect($filters['type'], $type))) {
                continue;
            }
        }
        if (!empty($filters['kilde'])) {
            $kilde = isset($index['kilde'][$pid]) ? $index['kilde'][$pid] : 'medlem';
            if (!in_array($kilde, $filters['kilde'], true)) {
                continue;
            }
        }
        if ($search !== '') {
            $hay = mb_strtolower(
                (isset($index['title'][$pid]) ? $index['title'][$pid] : '')
                . ' ' . (isset($index['eier'][$pid]) ? $index['eier'][$pid] : '')
            );
            if (mb_strpos($hay, $search) === false) {
                continue;
            }
        }
        $out[] = $pid;
    }

    return $out;
}

/**
 * Hent én side med verktøy.
 *
 * @param array $filters
 * @param int   $per_page
 * @return array {items:array[], total:int, paged:int, max_pages:int, has_more:bool}
 */
function bv_verktoy_katalog_page(array $filters, $per_page = 0) {
    $per_page = $per_page > 0 ? (int) $per_page : BV_VERKTOY_PER_PAGE;
    $ids      = bv_verktoy_matching_ids($filters);
    $total    = count($ids);
    $paged    = max(1, (int) $filters['paged']);
    $offset   = ($paged - 1) * $per_page;
    $slice    = array_slice($ids, $offset, $per_page);

    $items = array();
    if (!empty($slice)) {
        // post__in bevarer ikke rekkefølge; vi har allerede tittelsortert ID-listen, så
        // 'post__in' som orderby gir samme rekkefølge som fasett-tellingene.
        $q = new WP_Query(array(
            'post_type'              => 'verktoy',
            'post_status'            => 'publish',
            'post__in'               => $slice,
            'orderby'                => 'post__in',
            'posts_per_page'         => count($slice),
            'no_found_rows'          => true,
            'ignore_sticky_posts'    => true,
            'update_post_term_cache' => false,
        ));
        foreach ($q->posts as $post) {
            $items[] = bv_verktoy_katalog_item($post);
        }
        wp_reset_postdata();
    }

    return array(
        'items'     => $items,
        'total'     => $total,
        'paged'     => $paged,
        'max_pages' => (int) ceil($total / $per_page),
        'has_more'  => ($offset + count($slice)) < $total,
    );
}

/**
 * Bygg visningsdata for ett verktøy.
 *
 * @param WP_Post $post
 * @return array
 */
function bv_verktoy_katalog_item($post) {
    $pid       = (int) $post->ID;
    $index     = bv_verktoy_index();
    $tg_opts   = bv_verktoy_temagruppe_options();

    $tg_slugs = isset($index['tema'][$pid]) ? $index['tema'][$pid] : array();
    $tg_names = array();
    foreach ($tg_slugs as $slug) {
        if (isset($tg_opts[$slug])) {
            $tg_names[] = $tg_opts[$slug];
        }
    }

    // Logo: ACF-bildefelt først, deretter URL-fallback (synkede verktøy har ingen logo).
    $logo_url = '';
    $logo     = function_exists('get_field') ? get_field('verktoy_logo', $pid) : null;
    if ($logo) {
        $logo_url = is_array($logo) ? (isset($logo['url']) ? $logo['url'] : '') : wp_get_attachment_url($logo);
    }
    if (!$logo_url) {
        $logo_url = (string) get_post_meta($pid, 'verktoy_logo_url', true);
    }

    return array(
        'id'        => $pid,
        'title'     => get_the_title($post),
        'permalink' => get_permalink($post),
        'eier_name' => isset($index['eier'][$pid]) ? $index['eier'][$pid] : '',
        'logo_url'  => $logo_url,
        'tg_slugs'  => $tg_slugs,
        'tg_names'  => $tg_names,
        'type_tags' => isset($index['type'][$pid]) ? $index['type'][$pid] : array(),
        'is_synced' => function_exists('bv_aec_is_synced') ? bv_aec_is_synced($pid) : false,
        'is_ai'     => function_exists('bv_aec_is_ai_driven') ? bv_aec_is_ai_driven($pid) : false,
        'kilde'     => isset($index['kilde'][$pid]) ? $index['kilde'][$pid] : 'medlem',
    );
}

/**
 * Rendre en liste med verktøy som HTML.
 *
 * Samme funksjon brukes av arkivmalen og AJAX-endepunktet — det er dette som garanterer at
 * en AJAX-hentet side er identisk med en server-rendret side.
 *
 * @param array  $items Fra bv_verktoy_katalog_page().
 * @param string $view  'grid' | 'list'
 * @return string
 */
function bv_verktoy_katalog_render(array $items, $view = 'grid') {
    $view = ($view === 'list') ? 'list' : 'grid';

    ob_start();
    foreach ($items as $item) {
        get_template_part('parts/components/verktoy-kort', null, array(
            'item' => $item,
            'view' => $view,
        ));
    }
    return (string) ob_get_clean();
}

/**
 * AJAX: hent én filtrert side. Offentlig, kun lesing.
 */
function bv_verktoy_katalog_ajax() {
    $filters = bv_verktoy_katalog_filters($_REQUEST);
    $page    = bv_verktoy_katalog_page($filters);

    wp_send_json_success(array(
        'grid'      => bv_verktoy_katalog_render($page['items'], 'grid'),
        'list'      => bv_verktoy_katalog_render($page['items'], 'list'),
        'total'     => $page['total'],
        'paged'     => $page['paged'],
        'max_pages' => $page['max_pages'],
        'has_more'  => $page['has_more'],
    ));
}
add_action('wp_ajax_bv_verktoy_filter', 'bv_verktoy_katalog_ajax');
add_action('wp_ajax_nopriv_bv_verktoy_filter', 'bv_verktoy_katalog_ajax');

/**
 * Hold hovedspørringen på verktøy-arkivet i takt med vår egen sidestørrelse.
 *
 * Malen bruker sin egen spørring, men de pene side-URL-ene (`/verktoy/page/3/`) valideres av
 * HOVEDspørringen: har den færre sider enn vi lenker til, svarer WordPress 404 før malen
 * kjører. Med lesestørrelsen i Innstillinger (ofte 10) går det tilfeldigvis bra i dag, men
 * skrus den opp til 50 ville side 33 og oppover 404-et. Å sette samme antall her gjør
 * pagineringen uavhengig av den innstillingen.
 *
 * @param WP_Query $query
 */
function bv_verktoy_katalog_main_query($query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }
    if (!$query->is_post_type_archive('verktoy')) {
        return;
    }
    $query->set('posts_per_page', BV_VERKTOY_PER_PAGE);
}
add_action('pre_get_posts', 'bv_verktoy_katalog_main_query');

/**
 * Fasett-tellinger over ALLE publiserte verktøy (ikke filterkontekst), som før.
 *
 * @return array {temagruppe:array, type:array, kilde:array, total:int}
 */
function bv_verktoy_katalog_counts() {
    $index = bv_verktoy_index();

    $tema = array_fill_keys(array_keys(bv_verktoy_temagruppe_options()), 0);
    $type = array_fill_keys(array_keys(bv_verktoy_type_options()), 0);
    $kilde = array_fill_keys(array_keys(bv_verktoy_kilde_options()), 0);

    foreach ($index['ids'] as $pid) {
        foreach ((isset($index['tema'][$pid]) ? $index['tema'][$pid] : array()) as $slug) {
            if (isset($tema[$slug])) {
                $tema[$slug]++;
            }
        }
        foreach ((isset($index['type'][$pid]) ? $index['type'][$pid] : array()) as $key) {
            if (isset($type[$key])) {
                $type[$key]++;
            }
        }
        $k = isset($index['kilde'][$pid]) ? $index['kilde'][$pid] : 'medlem';
        if (isset($kilde[$k])) {
            $kilde[$k]++;
        }
    }

    return array(
        'temagruppe' => $tema,
        'type'       => $type,
        'kilde'      => $kilde,
        'total'      => count($index['ids']),
    );
}
