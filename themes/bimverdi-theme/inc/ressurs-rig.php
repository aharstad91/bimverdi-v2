<?php
/**
 * Ressurs-rig — delt oversiktsmatrise (#309 / Teams 22.06)
 *
 * Samme 5-blokks matrise som temagruppe-siden (single-theme_group.php), men
 * pakket som gjenbrukbar builder + renderer slik at den også kan vises nederst
 * på arrangementsmalen, drevet av arrangementets temagruppe(r).
 *
 * Bevisst egne `bv_rr_*`-navn + `rr-`-CSS-prefiks for å være FULLSTENDIG
 * frikoblet fra single-theme_group.php (som definerer egne bv_tg_*-helpere på
 * template-scope). Da unngår vi redeclare-fatal og regresjon på den
 * Bård-kritiske temagruppe-siden. En framtidig DRY-refaktor av begge er valgfri.
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * «Se alle»-URL: arkivside + temagruppe-filter som ARRAY-param.
 * Arkivene krever is_array($_GET['temagruppe']) → ?temagruppe[]=<slug>.
 * Flere slugs gir union, som matcher matrisens union-tellinger.
 */
if (!function_exists('bv_rr_arkiv_url')) {
    function bv_rr_arkiv_url($post_type, array $slugs) {
        $slugs = array_values(array_filter(array_map('strval', $slugs)));
        if (empty($slugs)) return '';
        $base = get_post_type_archive_link($post_type);
        if (!$base) return '';
        return add_query_arg('temagruppe', $slugs, $base);
    }
}

/** Initialer fra navn (logo-fallback). */
if (!function_exists('bv_rr_initials')) {
    function bv_rr_initials($name) {
        $w = preg_split('/\s+/', trim((string) $name));
        if (count($w) >= 2) {
            return strtoupper(mb_substr($w[0], 0, 1) . mb_substr($w[1], 0, 1));
        }
        return strtoupper(mb_substr((string) $name, 0, 2));
    }
}

/** Formater arrangement-dato (ACF-felt arrangement_dato, lagret som Ymd). */
if (!function_exists('bv_rr_format_dato')) {
    function bv_rr_format_dato($event_id) {
        static $months_no = [
            'januar', 'februar', 'mars', 'april', 'mai', 'juni',
            'juli', 'august', 'september', 'oktober', 'november', 'desember',
        ];
        $event_date = get_post_meta($event_id, 'arrangement_dato', true);
        if (!$event_date) return '';
        $date_obj = DateTime::createFromFormat('Ymd', $event_date);
        if (!$date_obj) return '';
        return $date_obj->format('j') . '. ' . $months_no[(int) $date_obj->format('n') - 1] . ' ' . $date_obj->format('Y');
    }
}

/**
 * Bygg matrise-blokkene for et sett temagruppe-termer (union).
 *
 * @param array $terms WP_Term-objekter (f.eks. fra wp_get_post_terms).
 * @param array $opts  ['exclude_event_id' => int]  ekskluder dette arrangementet fra arrangementer-blokken.
 * @return array ['blocks' => array, 'total' => int, 'slugs' => array]
 */
if (!function_exists('bv_ressurs_rig_build')) {
    function bv_ressurs_rig_build(array $terms, array $opts = []) {
        $terms = array_filter($terms, function ($t) {
            return is_object($t) && !is_wp_error($t) && !empty($t->term_id);
        });
        if (empty($terms)) {
            return ['blocks' => [], 'total' => 0, 'slugs' => []];
        }

        $term_ids = array_map(function ($t) { return (int) $t->term_id; }, $terms);
        $slugs    = array_map(function ($t) { return $t->slug; }, $terms);
        $names    = array_map(function ($t) { return $t->name; }, $terms);
        $exclude  = (int) ($opts['exclude_event_id'] ?? 0);
        $not_in   = $exclude ? [$exclude] : [];

        $tax = [[
            'taxonomy' => 'temagruppe',
            'field'    => 'term_id',
            'terms'    => $term_ids,
        ]];

        $blocks = [];

        // ── Kunnskapskilder ──
        $q = new WP_Query([
            'post_type' => 'kunnskapskilde', 'posts_per_page' => 3,
            'orderby' => 'date', 'order' => 'DESC', 'tax_query' => $tax,
            'no_found_rows' => false,
        ]);
        if ($q->found_posts > 0) {
            $items = [];
            foreach ($q->posts as $ks) {
                $ekstern   = get_field('ekstern_lenke', $ks->ID);
                $kildetype = get_field('kildetype', $ks->ID);
                $items[] = [
                    'title'    => get_the_title($ks->ID),
                    'href'     => $ekstern ?: get_permalink($ks->ID),
                    'external' => !empty($ekstern),
                    'meta'     => $kildetype ? ucfirst($kildetype) : '',
                    'icon'     => 'file-text',
                ];
            }
            $blocks[] = [
                'key' => 'kunnskapskilder', 'heading' => 'Kunnskapskilder', 'icon' => 'lightbulb',
                'total' => $q->found_posts, 'items' => $items,
                'arkiv_url' => bv_rr_arkiv_url('kunnskapskilde', $slugs),
            ];
        }
        wp_reset_postdata();

        // ── Verktøy (dual-source: taxonomy ∪ ACF formaalstema (NAVN)) ──
        $q = new WP_Query([
            'post_type' => 'verktoy', 'posts_per_page' => -1, 'fields' => 'ids', 'tax_query' => $tax,
        ]);
        $verktoy_ids = $q->posts;
        wp_reset_postdata();

        $q = new WP_Query([
            'post_type' => 'verktoy', 'posts_per_page' => -1, 'fields' => 'ids',
            'meta_query' => [['key' => 'formaalstema', 'value' => $names, 'compare' => 'IN']],
        ]);
        $verktoy_ids = array_values(array_unique(array_merge($verktoy_ids, $q->posts)));
        wp_reset_postdata();

        if (!empty($verktoy_ids)) {
            $q = new WP_Query([
                'post_type' => 'verktoy', 'post__in' => $verktoy_ids, 'posts_per_page' => 3,
                'orderby' => 'date', 'order' => 'DESC',
            ]);
            $items = [];
            foreach ($q->posts as $vt) {
                $logo = get_field('verktoy_logo', $vt->ID);
                $logo_url = $logo ? (is_array($logo) ? ($logo['url'] ?? '') : wp_get_attachment_url($logo)) : '';
                if (!$logo_url) { $logo_url = get_post_meta($vt->ID, 'verktoy_logo_url', true); }
                $cats = wp_get_post_terms($vt->ID, 'verktoykategori', ['fields' => 'names']);
                $items[] = [
                    'title'  => get_the_title($vt->ID),
                    'href'   => get_permalink($vt->ID),
                    'meta'   => !empty($cats) && !is_wp_error($cats) ? $cats[0] : '',
                    'avatar' => ['src' => $logo_url, 'initials' => bv_rr_initials(get_the_title($vt->ID))],
                ];
            }
            $blocks[] = [
                'key' => 'verktoy', 'heading' => 'Verktøy', 'icon' => 'wrench',
                'total' => count($verktoy_ids), 'items' => $items,
                'arkiv_url' => bv_rr_arkiv_url('verktoy', $slugs),
            ];
            wp_reset_postdata();
        }

        // ── Artikler ──
        $q = new WP_Query([
            'post_type' => 'artikkel', 'posts_per_page' => 3,
            'orderby' => 'date', 'order' => 'DESC', 'tax_query' => $tax,
        ]);
        if ($q->found_posts > 0) {
            $items = [];
            foreach ($q->posts as $art) {
                $items[] = [
                    'title' => get_the_title($art->ID),
                    'href'  => get_permalink($art->ID),
                    'meta'  => get_the_date('j. M Y', $art->ID),
                    'icon'  => 'file-text',
                ];
            }
            $blocks[] = [
                'key' => 'artikler', 'heading' => 'Artikler', 'icon' => 'newspaper',
                'total' => $q->found_posts, 'items' => $items,
                'arkiv_url' => bv_rr_arkiv_url('artikkel', $slugs),
            ];
        }
        wp_reset_postdata();

        // ── Deltakere (foretak m/ betalende rolle) ──
        $q = new WP_Query([
            'post_type' => 'foretak', 'posts_per_page' => 3,
            'orderby' => 'date', 'order' => 'DESC', 'tax_query' => $tax,
            'meta_query' => [['key' => 'bv_rolle', 'value' => ['Deltaker', 'Prosjektdeltaker', 'Partner'], 'compare' => 'IN']],
        ]);
        if ($q->found_posts > 0) {
            $items = [];
            foreach ($q->posts as $f) {
                $logo = get_field('logo', $f->ID);
                $logo_url = $logo ? (is_array($logo) ? ($logo['url'] ?? '') : wp_get_attachment_url($logo)) : '';
                $bransje = wp_get_post_terms($f->ID, 'bransjekategori', ['fields' => 'names']);
                $items[] = [
                    'title'  => get_the_title($f->ID),
                    'href'   => get_permalink($f->ID),
                    'meta'   => !empty($bransje) && !is_wp_error($bransje) ? $bransje[0] : '',
                    'avatar' => ['src' => $logo_url, 'initials' => bv_rr_initials(get_the_title($f->ID))],
                ];
            }
            $blocks[] = [
                'key' => 'deltakere', 'heading' => 'Deltakere', 'icon' => 'building-2',
                'total' => $q->found_posts, 'items' => $items,
                'arkiv_url' => bv_rr_arkiv_url('foretak', $slugs),
            ];
        }
        wp_reset_postdata();

        // ── Arrangementer (kommende først, fyll med tidligere; ekskluder dette) ──
        // Klassifisering må matche archive-arrangement.php EKSAKT.
        $arr_up_mq = ['relation' => 'AND',
            ['key' => 'arrangement_status_toggle', 'value' => 'kommende'],
            ['key' => 'arrangement_status', 'value' => 'planlagt']];
        $arr_past_mq = ['relation' => 'OR',
            ['key' => 'arrangement_status_toggle', 'value' => 'tidligere'],
            ['key' => 'arrangement_status', 'value' => 'avlyst']];

        $up = new WP_Query([
            'post_type' => 'arrangement', 'posts_per_page' => 3,
            'post__not_in' => $not_in ?: [0],
            'meta_key' => 'arrangement_dato', 'orderby' => 'meta_value', 'order' => 'ASC',
            'meta_query' => $arr_up_mq, 'tax_query' => $tax,
        ]);
        $arr_posts = $up->posts;
        $arr_up_count = $up->found_posts;
        wp_reset_postdata();

        $past = new WP_Query([
            'post_type' => 'arrangement', 'posts_per_page' => max(1, 3 - count($arr_posts)),
            'post__not_in' => array_merge($not_in, wp_list_pluck($arr_posts, 'ID')) ?: [0],
            'meta_key' => 'arrangement_dato', 'orderby' => 'meta_value', 'order' => 'DESC',
            'meta_query' => $arr_past_mq, 'tax_query' => $tax,
        ]);
        $arr_past_count = $past->found_posts;
        if (count($arr_posts) < 3) {
            $arr_posts = array_merge($arr_posts, array_slice($past->posts, 0, 3 - count($arr_posts)));
        }
        wp_reset_postdata();

        $arr_total = $arr_up_count + $arr_past_count;
        if ($arr_total > 0) {
            $items = [];
            foreach ($arr_posts as $ev) {
                $types = wp_get_post_terms($ev->ID, 'arrangementstype', ['fields' => 'names']);
                $dato  = bv_rr_format_dato($ev->ID);
                $items[] = [
                    'title' => get_the_title($ev->ID),
                    'href'  => get_permalink($ev->ID),
                    'meta'  => $dato ?: (!empty($types) && !is_wp_error($types) ? $types[0] : ''),
                    'icon'  => 'calendar',
                ];
            }
            $blocks[] = [
                'key' => 'arrangementer', 'heading' => 'Arrangementer', 'icon' => 'calendar',
                'total' => $arr_total, 'items' => $items,
                'arkiv_url' => bv_rr_arkiv_url('arrangement', $slugs),
            ];
        }

        $total = 0;
        foreach ($blocks as $b) { $total += (int) $b['total']; }

        return ['blocks' => $blocks, 'total' => $total, 'slugs' => $slugs];
    }
}

/**
 * Render hele ressurs-rig-seksjonen (overskrift + matrise + CSS én gang).
 * Tom hvis ingen temagruppe / ingen innhold.
 *
 * @param array $terms WP_Term-objekter.
 * @param array $opts  ['exclude_event_id' => int, 'heading' => string|null]
 */
if (!function_exists('bv_ressurs_rig_render')) {
    function bv_ressurs_rig_render(array $terms, array $opts = []) {
        require_once get_template_directory() . '/parts/components/button.php';

        $data = bv_ressurs_rig_build($terms, $opts);
        if (empty($data['blocks'])) {
            return; // P4: vis kun det som finnes
        }

        $total   = (int) $data['total'];
        $heading = $opts['heading']
            ?? sprintf('%d ressurser som er kategorisert med samme tema som dette arrangementet', $total);

        // CSS kun én gang per request.
        static $css_printed = false;
        if (!$css_printed) {
            $css_printed = true;
            ?>
<style>
.rr-section { border-top: 1px solid #E7E5E4; margin-top: 48px; padding-top: 48px; }
.rr-heading { font-family: 'Familjen Grotesk', sans-serif; font-size: 24px; font-weight: 700; color: #111827; margin: 0 0 24px; line-height: 1.25; }
.rr-matrix { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
@media (max-width: 1024px) { .rr-matrix { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  { .rr-matrix { grid-template-columns: 1fr; } }
.rr-block { display: flex; flex-direction: column; border: 1px solid #E7E5E4; border-radius: 14px; background: #fff; padding: 20px; min-width: 0; }
.rr-block-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.rr-block-icon { width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #FF8B5E; background: color-mix(in srgb, #FF8B5E 12%, #fff); }
.rr-block-title { font-family: 'Familjen Grotesk', sans-serif; font-size: 16px; font-weight: 700; color: #111827; margin: 0; }
.rr-block-count { margin-left: auto; font-size: 12px; font-weight: 600; color: #57534E; background: #F0EDEA; padding: 3px 10px; border-radius: 100px; white-space: nowrap; }
.rr-items { display: flex; flex-direction: column; flex: 1; }
.rr-item { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-top: 1px solid #F0EDEA; text-decoration: none; color: inherit; }
a.rr-item:hover { text-decoration: none; }
.rr-item:first-child { border-top: none; }
.rr-item-fig { width: 32px; height: 32px; border-radius: 7px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #FAFAF9; color: #A8A29E; font-size: 11px; font-weight: 600; overflow: hidden; }
.rr-item-fig img { width: 100%; height: 100%; object-fit: contain; }
.rr-item-body { min-width: 0; flex: 1; }
.rr-item-title { font-size: 14px; font-weight: 500; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
.rr-item:hover .rr-item-title { color: #57534E; }
.rr-item-meta { font-size: 12px; color: #A8A29E; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
.rr-item-ext { flex-shrink: 0; color: #A8A29E; }
.rr-block-more { margin-top: 14px; font-size: 13px; font-weight: 600; color: #FF8B5E; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
a.rr-block-more:hover { gap: 8px; text-decoration: none; color: #E67A4E; }
</style>
            <?php
        }
        ?>
        <section class="rr-section">
            <h2 class="rr-heading"><?php echo esc_html($heading); ?></h2>
            <div class="rr-matrix">
                <?php foreach ($data['blocks'] as $b) : ?>
                <div class="rr-block" data-block="<?php echo esc_attr($b['key']); ?>">
                    <div class="rr-block-head">
                        <span class="rr-block-icon"><?php echo bimverdi_get_icon_svg($b['icon'], 18); ?></span>
                        <h3 class="rr-block-title"><?php echo esc_html($b['heading']); ?></h3>
                        <span class="rr-block-count"><?php echo (int) $b['total']; ?></span>
                    </div>

                    <div class="rr-items">
                        <?php foreach ($b['items'] as $it) : ?>
                        <a class="rr-item" href="<?php echo esc_url($it['href']); ?>"
                           <?php if (!empty($it['external'])) echo 'target="_blank" rel="noopener"'; ?>>
                            <span class="rr-item-fig">
                                <?php if (!empty($it['avatar']) && !empty($it['avatar']['src'])) : ?>
                                    <img src="<?php echo esc_url($it['avatar']['src']); ?>" alt="">
                                <?php elseif (!empty($it['avatar'])) : ?>
                                    <?php echo esc_html($it['avatar']['initials']); ?>
                                <?php else : ?>
                                    <?php echo bimverdi_get_icon_svg($it['icon'] ?? 'file-text', 15); ?>
                                <?php endif; ?>
                            </span>
                            <span class="rr-item-body">
                                <span class="rr-item-title"><?php echo esc_html($it['title']); ?></span>
                                <?php if (!empty($it['meta'])) : ?>
                                    <span class="rr-item-meta"><?php echo esc_html($it['meta']); ?></span>
                                <?php endif; ?>
                            </span>
                            <?php if (!empty($it['external'])) : ?>
                                <span class="rr-item-ext"><?php echo bimverdi_get_icon_svg('external-link', 14); ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($b['arkiv_url'])) : ?>
                        <a class="rr-block-more" href="<?php echo esc_url($b['arkiv_url']); ?>">
                            Se alle <?php echo (int) $b['total']; ?> <?php echo bimverdi_get_icon_svg('arrow-right', 14); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
}
