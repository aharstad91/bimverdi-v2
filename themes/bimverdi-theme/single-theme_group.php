<?php
/**
 * Single Temagruppe Template — Kompakt oversiktsmatrise (#309)
 *
 * Topp: redigerbar Gutenberg-intro + fagansvarlig.
 * Innhold: oversiktsmatrise — én blokk per ressurstype med
 *          overskrift + totalt antall + 3 nyeste + «Se alle →» (filtrert arkiv).
 *
 * Full liste per type bor på det filtrerte arkivet (?temagruppe[]=<slug>),
 * ikke på denne siden.
 *
 * @package BimVerdi_Theme
 */

get_header();

if (have_posts()) : while (have_posts()) : the_post();

$post_id = get_the_ID();

// ── ACF Fields ──
$kort_beskrivelse = get_field('kort_beskrivelse', $post_id);

// Fagansvarlig
$fagansvarlig_navn       = get_field('fagansvarlig_navn', $post_id);
$fagansvarlig_tittel     = get_field('fagansvarlig_tittel', $post_id);
$fagansvarlig_bedrift_id = get_field('fagansvarlig_bedrift', $post_id);
$fagansvarlig_bilde_id   = get_field('fagansvarlig_bilde', $post_id);
$fagansvarlig_linkedin   = get_field('fagansvarlig_linkedin', $post_id);

$bedrift_navn = $fagansvarlig_bedrift_id ? get_the_title($fagansvarlig_bedrift_id) : '';
$bedrift_url  = $fagansvarlig_bedrift_id ? get_permalink($fagansvarlig_bedrift_id) : '';
$bilde_url    = $fagansvarlig_bilde_id ? wp_get_attachment_image_url($fagansvarlig_bilde_id, 'thumbnail') : null;

// ── Temagruppe identification ──
$temagruppe_navn = get_the_title();
$temagruppe_term = get_term_by('name', $temagruppe_navn, 'temagruppe');
$temagruppe_slug = $temagruppe_term ? $temagruppe_term->slug : '';

// ── Norwegian months ──
$months_no = [
    'januar', 'februar', 'mars', 'april', 'mai', 'juni',
    'juli', 'august', 'september', 'oktober', 'november', 'desember'
];

// ── Membership status (for "Registrer foretaket" button) ──
$current_user_id = get_current_user_id();
$is_logged_in    = (bool) $current_user_id;
$is_hovedkontakt = $is_logged_in && function_exists('bimverdi_is_hovedkontakt') && bimverdi_is_hovedkontakt($current_user_id);
$user_foretak_id = $is_logged_in && function_exists('bimverdi_user_has_foretak') ? bimverdi_user_has_foretak($current_user_id) : null;
$is_member = false;
if ($user_foretak_id && $temagruppe_term) {
    $is_member = has_term($temagruppe_term->term_id, 'temagruppe', $user_foretak_id);
}
$foretak_name = $user_foretak_id ? get_the_title($user_foretak_id) : '';

// ══════════════════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════════════════

/**
 * Bygg «Se alle»-URL: arkivside + temagruppe-filter som ARRAY-param.
 * Arkivene krever is_array($_GET['temagruppe']) → ?temagruppe[]=<slug>.
 */
function bv_tg_arkiv_url($post_type, $slug) {
    if (!$slug) return '';
    $base = get_post_type_archive_link($post_type);
    if (!$base) return '';
    return add_query_arg('temagruppe', [$slug], $base);
}

/** Initialer fra navn (logo-fallback). */
function bv_tg_initials($name) {
    $w = preg_split('/\s+/', trim((string) $name));
    if (count($w) >= 2) {
        return strtoupper(mb_substr($w[0], 0, 1) . mb_substr($w[1], 0, 1));
    }
    return strtoupper(mb_substr((string) $name, 0, 2));
}

/** Formater arrangement-dato (ACF-felt arrangement_dato, lagret som Ymd). */
function bv_tg_format_dato($event_id, $months_no) {
    $event_date = get_post_meta($event_id, 'arrangement_dato', true);
    if (!$event_date) return '';
    $date_obj = DateTime::createFromFormat('Ymd', $event_date);
    if (!$date_obj) return '';
    return $date_obj->format('j') . '. ' . $months_no[(int) $date_obj->format('n') - 1] . ' ' . $date_obj->format('Y');
}

/** Tax_query-fragment for denne temagruppen. */
$tg_tax = $temagruppe_term ? [[
    'taxonomy' => 'temagruppe',
    'field'    => 'term_id',
    'terms'    => $temagruppe_term->term_id,
]] : null;

// ══════════════════════════════════════════════════════════════════════
// DATA — 3 nyeste + total per ressurstype
// ══════════════════════════════════════════════════════════════════════

$blocks = [];

// ── Kunnskapskilder ──
if ($tg_tax) {
    $q = new WP_Query([
        'post_type' => 'kunnskapskilde', 'posts_per_page' => 3,
        'orderby' => 'date', 'order' => 'DESC', 'tax_query' => $tg_tax,
    ]);
    $items = [];
    foreach ($q->posts as $ks) {
        $ekstern  = get_field('ekstern_lenke', $ks->ID);
        $kildetype = get_field('kildetype', $ks->ID);
        $items[] = [
            'title'    => get_the_title($ks->ID),
            'href'     => $ekstern ?: get_permalink($ks->ID),
            'external' => !empty($ekstern),
            'meta'     => $kildetype ? ucfirst($kildetype) : '',
            'icon'     => 'file-text',
        ];
    }
    if ($q->found_posts > 0) {
        $blocks[] = [
            'key' => 'kunnskapskilder', 'heading' => 'Kunnskapskilder', 'icon' => 'lightbulb',
            'total' => $q->found_posts, 'items' => $items,
            'arkiv_url' => bv_tg_arkiv_url('kunnskapskilde', $temagruppe_slug),
        ];
    }
    wp_reset_postdata();
}

// ── Verktøy (dual-source: taxonomy ∪ ACF formaalstema) ──
$verktoy_ids = [];
if ($tg_tax) {
    $q = new WP_Query([
        'post_type' => 'verktoy', 'posts_per_page' => -1, 'fields' => 'ids', 'tax_query' => $tg_tax,
    ]);
    $verktoy_ids = $q->posts;
    wp_reset_postdata();
}
$q = new WP_Query([
    'post_type' => 'verktoy', 'posts_per_page' => -1, 'fields' => 'ids',
    'meta_query' => [['key' => 'formaalstema', 'value' => $temagruppe_navn, 'compare' => '=']],
]);
$verktoy_ids = array_unique(array_merge($verktoy_ids, $q->posts));
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
            'title'   => get_the_title($vt->ID),
            'href'    => get_permalink($vt->ID),
            'meta'    => !empty($cats) ? $cats[0] : '',
            'avatar'  => ['src' => $logo_url, 'initials' => bv_tg_initials(get_the_title($vt->ID))],
        ];
    }
    $blocks[] = [
        'key' => 'verktoy', 'heading' => 'Verktøy', 'icon' => 'wrench',
        'total' => count($verktoy_ids), 'items' => $items,
        'arkiv_url' => bv_tg_arkiv_url('verktoy', $temagruppe_slug),
    ];
    wp_reset_postdata();
}

// ── Artikler ──
if ($tg_tax) {
    $q = new WP_Query([
        'post_type' => 'artikkel', 'posts_per_page' => 3,
        'orderby' => 'date', 'order' => 'DESC', 'tax_query' => $tg_tax,
    ]);
    $items = [];
    foreach ($q->posts as $art) {
        $items[] = [
            'title' => get_the_title($art->ID),
            'href'  => get_permalink($art->ID),
            'meta'  => get_the_date('j. M Y', $art->ID),
            'icon'  => 'file-text',
        ];
    }
    if ($q->found_posts > 0) {
        $blocks[] = [
            'key' => 'artikler', 'heading' => 'Artikler', 'icon' => 'newspaper',
            'total' => $q->found_posts, 'items' => $items,
            'arkiv_url' => bv_tg_arkiv_url('artikkel', $temagruppe_slug),
        ];
    }
    wp_reset_postdata();
}

// ── Deltakere (foretak) ──
if ($tg_tax) {
    $q = new WP_Query([
        'post_type' => 'foretak', 'posts_per_page' => 3,
        'orderby' => 'date', 'order' => 'DESC', 'tax_query' => $tg_tax,
        'meta_query' => [['key' => 'bv_rolle', 'value' => ['Deltaker', 'Prosjektdeltaker', 'Partner'], 'compare' => 'IN']],
    ]);
    $items = [];
    foreach ($q->posts as $f) {
        $logo = get_field('logo', $f->ID);
        $logo_url = $logo ? (is_array($logo) ? ($logo['url'] ?? '') : wp_get_attachment_url($logo)) : '';
        $bransje = wp_get_post_terms($f->ID, 'bransjekategori', ['fields' => 'names']);
        $items[] = [
            'title'  => get_the_title($f->ID),
            'href'   => get_permalink($f->ID),
            'meta'   => !empty($bransje) ? $bransje[0] : '',
            'avatar' => ['src' => $logo_url, 'initials' => bv_tg_initials(get_the_title($f->ID))],
        ];
    }
    if ($q->found_posts > 0) {
        $blocks[] = [
            'key' => 'deltakere', 'heading' => 'Deltakere', 'icon' => 'building-2',
            'total' => $q->found_posts, 'items' => $items,
            'arkiv_url' => bv_tg_arkiv_url('foretak', $temagruppe_slug),
        ];
    }
    wp_reset_postdata();
}

// ── Arrangementer ──
// Total + klassifisering må matche archive-arrangement.php EKSAKT, så «Se alle N»
// treffer riktig: kommende = toggle=kommende AND status=planlagt; tidligere =
// toggle=tidligere OR status=avlyst. Arrangementer uten denne meta vises ingen
// steder (heller ikke på arkivet), så de holdes utenfor totalen.
if ($tg_tax) {
    $arr_up_mq = ['relation' => 'AND',
        ['key' => 'arrangement_status_toggle', 'value' => 'kommende'],
        ['key' => 'arrangement_status', 'value' => 'planlagt']];
    $arr_past_mq = ['relation' => 'OR',
        ['key' => 'arrangement_status_toggle', 'value' => 'tidligere'],
        ['key' => 'arrangement_status', 'value' => 'avlyst']];

    // Kommende først (dato ASC)
    $up = new WP_Query([
        'post_type' => 'arrangement', 'posts_per_page' => 3,
        'meta_key' => 'arrangement_dato', 'orderby' => 'meta_value', 'order' => 'ASC',
        'meta_query' => $arr_up_mq, 'tax_query' => $tg_tax,
    ]);
    $arr_posts = $up->posts;
    $arr_up_count = $up->found_posts;
    wp_reset_postdata();

    // Fyll med nyeste tidligere (dato DESC); hent også total-antallet tidligere
    $past = new WP_Query([
        'post_type' => 'arrangement', 'posts_per_page' => max(1, 3 - count($arr_posts)),
        'post__not_in' => wp_list_pluck($arr_posts, 'ID') ?: [0],
        'meta_key' => 'arrangement_dato', 'orderby' => 'meta_value', 'order' => 'DESC',
        'meta_query' => $arr_past_mq, 'tax_query' => $tg_tax,
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
            $dato  = bv_tg_format_dato($ev->ID, $months_no);
            $items[] = [
                'title' => get_the_title($ev->ID),
                'href'  => get_permalink($ev->ID),
                'meta'  => $dato ?: (!empty($types) ? $types[0] : ''),
                'icon'  => 'calendar',
            ];
        }
        $blocks[] = [
            'key' => 'arrangementer', 'heading' => 'Arrangementer', 'icon' => 'calendar',
            'total' => $arr_total, 'items' => $items,
            'arkiv_url' => bv_tg_arkiv_url('arrangement', $temagruppe_slug),
        ];
    }
}

$total_items = 0;
foreach ($blocks as $b) { $total_items += (int) $b['total']; }

require_once get_template_directory() . '/parts/components/button.php';
?>

<style>
:root {
    --tg-accent: var(--color-primary, #FF8B5E);
    --tg-accent-dark: var(--color-primary-dark, #E67A4E);
    --tg-text: #1A1A1A;
    --tg-text-secondary: #57534E;
    --tg-text-muted: #A8A29E;
    --tg-border: #E7E5E4;
    --tg-divider: #F0EDEA;
    --tg-surface: #FAFAF9;
}

/* ── Header / topp ── */
.tg-header { background: #fff; }
.tg-header-inner {
    max-width: 1280px; margin: 0 auto; padding: 32px 24px 8px;
}
.tg-hero-title {
    font-family: 'Familjen Grotesk', sans-serif;
    font-size: 30px; font-weight: 700; color: var(--tg-text);
    margin: 14px 0 14px; line-height: 1.2;
}
/* Gutenberg-redigerbar intro */
.tg-intro {
    font-size: 16px; line-height: 1.7; color: var(--tg-text-secondary);
    max-width: 760px;
}
.tg-intro > *:first-child { margin-top: 0; }
.tg-intro > *:last-child { margin-bottom: 0; }
.tg-intro p { margin: 0 0 12px; }
.tg-intro h2, .tg-intro h3 { color: var(--tg-text); font-family: 'Familjen Grotesk', sans-serif; }
.tg-intro img { max-width: 100%; height: auto; border-radius: 10px; }
.tg-intro a { color: var(--tg-accent); }

/* Fagansvarlig */
.tg-fagansvarlig {
    display: flex; align-items: center; gap: 14px;
    margin-top: 22px; padding: 14px 18px;
    border: 1px solid var(--tg-border); border-radius: 12px;
    background: var(--tg-surface); max-width: 460px;
}
.tg-fag-avatar {
    width: 52px; height: 52px; border-radius: 50%; flex-shrink: 0;
    object-fit: cover; display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 600; font-size: 16px;
}
.tg-fag-body { min-width: 0; }
.tg-fag-eyebrow { font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--tg-text-muted); }
.tg-fag-name { font-size: 15px; font-weight: 600; color: var(--tg-text); margin: 1px 0; }
.tg-fag-role { font-size: 13px; color: var(--tg-text-secondary); }
.tg-fag-links { margin-top: 4px; display: flex; gap: 14px; }
.tg-fag-links a { font-size: 13px; font-weight: 500; color: var(--tg-accent); text-decoration: none; }
.tg-fag-links a:hover { text-decoration: underline; }

/* Summary-rad */
.tg-summary {
    display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
    margin-top: 24px;
}
.tg-summary-count { font-size: 14px; color: var(--tg-text-secondary); }
.tg-member-badge {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 13px; font-weight: 500; color: #16A085;
    background: #E8F8F5; padding: 6px 14px; border-radius: 100px; white-space: nowrap;
}

/* ── Innhold / matrise ── */
.tg-content-inner {
    max-width: 1280px; margin: 0 auto; padding: 28px 24px 80px;
}
.tg-matrix {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
}
@media (max-width: 1024px) { .tg-matrix { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  { .tg-matrix { grid-template-columns: 1fr; } }

.tg-block {
    display: flex; flex-direction: column;
    border: 1px solid var(--tg-border); border-radius: 14px;
    background: #fff; padding: 20px;
    min-width: 0; /* la 1fr-sporet krympe under innholdsbredde → like kolonner + ellipsis */
}
.tg-block-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.tg-block-icon {
    width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    color: var(--tg-accent); background: color-mix(in srgb, var(--tg-accent) 12%, #fff);
}
.tg-block-title { font-family: 'Familjen Grotesk', sans-serif; font-size: 16px; font-weight: 700; color: var(--tg-text); margin: 0; }
.tg-block-count {
    margin-left: auto; font-size: 12px; font-weight: 600; color: var(--tg-text-secondary);
    background: var(--tg-divider); padding: 3px 10px; border-radius: 100px; white-space: nowrap;
}

.tg-items { display: flex; flex-direction: column; flex: 1; }
.tg-item {
    display: flex; align-items: center; gap: 10px; padding: 9px 0;
    border-top: 1px solid var(--tg-divider); text-decoration: none; color: inherit;
}
a.tg-item:hover { text-decoration: none; } /* overstyr global a:hover-underline */
.tg-item:first-child { border-top: none; }
.tg-item-fig {
    width: 32px; height: 32px; border-radius: 7px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: var(--tg-surface); color: var(--tg-text-muted);
    font-size: 11px; font-weight: 600; overflow: hidden;
}
.tg-item-fig img { width: 100%; height: 100%; object-fit: contain; }
.tg-item-body { min-width: 0; flex: 1; }
.tg-item-title {
    font-size: 14px; font-weight: 500; color: var(--tg-text);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;
}
.tg-item:hover .tg-item-title { color: var(--tg-text-secondary); }
.tg-item-meta { font-size: 12px; color: var(--tg-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
.tg-item-ext { flex-shrink: 0; color: var(--tg-text-muted); }

.tg-block-more {
    margin-top: 14px; font-size: 13px; font-weight: 600; color: var(--tg-accent);
    text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
}
a.tg-block-more:hover { gap: 8px; text-decoration: none; color: var(--tg-accent-dark); }

.tg-empty-state { text-align: center; padding: 56px 24px; color: var(--tg-text-muted); font-size: 15px; }
.tg-empty-state a { color: var(--tg-accent); text-decoration: none; }
.tg-empty-state a:hover { text-decoration: underline; }

@media (max-width: 768px) {
    .tg-header-inner { padding: 20px 16px 4px; }
    .tg-content-inner { padding: 20px 16px 64px; }
    .tg-hero-title { font-size: 24px; }
}

/* ── Join modal ── */
.tg-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 9999; align-items: center; justify-content: center; }
.tg-modal-overlay.active { display: flex; }
.tg-modal { background: #fff; border-radius: 12px; padding: 32px; max-width: 480px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
.tg-modal h3 { font-size: 18px; font-weight: 600; color: #18181B; margin: 0 0 8px; }
.tg-modal p { font-size: 14px; color: #57534E; line-height: 1.6; margin: 0 0 24px; }
.tg-modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
.tg-modal .bv-btn--loading { opacity: 0.6; pointer-events: none; }
</style>

<main>
    <!-- ══ Topp ══ -->
    <div class="tg-header">
        <div class="tg-header-inner">

            <?php
            require_once get_template_directory() . '/parts/components/breadcrumb.php';
            bimverdi_breadcrumb([
                ['label' => 'Hjem', 'href' => home_url('/')],
                ['label' => 'Temagrupper', 'href' => home_url('/temagruppe/')],
                ['label' => get_the_title()],
            ]);
            ?>

            <h1 class="tg-hero-title"><?php the_title(); ?></h1>

            <?php
            // Redigerbar topp: Gutenberg-innhold → kort_beskrivelse → excerpt
            $content = trim(get_the_content());
            if ($content) : ?>
                <div class="tg-intro"><?php echo apply_filters('the_content', $content); ?></div>
            <?php elseif ($kort_beskrivelse) : ?>
                <div class="tg-intro"><p><?php echo esc_html($kort_beskrivelse); ?></p></div>
            <?php elseif (has_excerpt()) : ?>
                <div class="tg-intro"><p><?php echo esc_html(get_the_excerpt()); ?></p></div>
            <?php endif; ?>

            <?php if ($fagansvarlig_navn) : ?>
            <div class="tg-fagansvarlig">
                <?php if ($bilde_url) : ?>
                    <img class="tg-fag-avatar" src="<?php echo esc_url($bilde_url); ?>" alt="<?php echo esc_attr($fagansvarlig_navn); ?>">
                <?php else : ?>
                    <span class="tg-fag-avatar" style="background: var(--tg-accent);"><?php echo esc_html(bv_tg_initials($fagansvarlig_navn)); ?></span>
                <?php endif; ?>
                <div class="tg-fag-body">
                    <div class="tg-fag-eyebrow">Faglig ansvarlig</div>
                    <div class="tg-fag-name"><?php echo esc_html($fagansvarlig_navn); ?></div>
                    <?php if ($fagansvarlig_tittel || $bedrift_navn) : ?>
                        <div class="tg-fag-role">
                            <?php echo esc_html($fagansvarlig_tittel); ?><?php if ($fagansvarlig_tittel && $bedrift_navn) echo ', '; echo esc_html($bedrift_navn); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($bedrift_url || $fagansvarlig_linkedin) : ?>
                        <div class="tg-fag-links">
                            <?php if ($bedrift_url) : ?><a href="<?php echo esc_url($bedrift_url); ?>"><?php echo esc_html($bedrift_navn ?: 'Foretak'); ?></a><?php endif; ?>
                            <?php if ($fagansvarlig_linkedin) : ?><a href="<?php echo esc_url($fagansvarlig_linkedin); ?>" target="_blank" rel="noopener">LinkedIn</a><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="tg-summary">
                <?php if ($total_items > 0) : ?>
                    <span class="tg-summary-count"><strong><?php echo (int) $total_items; ?></strong> ressurser i denne temagruppen</span>
                <?php endif; ?>

                <?php if ($is_hovedkontakt && $temagruppe_term) :
                    if ($is_member) : ?>
                        <span class="tg-member-badge">
                            <?php echo bimverdi_get_icon_svg('check-circle', 14); ?>
                            <?php echo esc_html($foretak_name); ?> er med
                        </span>
                    <?php else : ?>
                        <button type="button" class="bv-btn bv-btn--primary bv-btn--sm" id="tg-join-btn"
                            data-term-id="<?php echo esc_attr($temagruppe_term->term_id); ?>"
                            data-foretak-name="<?php echo esc_attr($foretak_name); ?>"
                            data-temagruppe-name="<?php echo esc_attr($temagruppe_navn); ?>">
                            <span>Registrer foretaket</span>
                            <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
                        </button>
                    <?php endif;
                endif; ?>
            </div>
        </div>
    </div>

    <!-- ══ Oversiktsmatrise ══ -->
    <div class="tg-content">
        <div class="tg-content-inner">

            <?php if (!empty($blocks)) : ?>
            <div class="tg-matrix">
                <?php foreach ($blocks as $b) : ?>
                <section class="tg-block" data-block="<?php echo esc_attr($b['key']); ?>">
                    <div class="tg-block-head">
                        <span class="tg-block-icon"><?php echo bimverdi_get_icon_svg($b['icon'], 18); ?></span>
                        <h2 class="tg-block-title"><?php echo esc_html($b['heading']); ?></h2>
                        <span class="tg-block-count"><?php echo (int) $b['total']; ?></span>
                    </div>

                    <div class="tg-items">
                        <?php foreach ($b['items'] as $it) : ?>
                        <a class="tg-item" href="<?php echo esc_url($it['href']); ?>"
                           <?php if (!empty($it['external'])) echo 'target="_blank" rel="noopener"'; ?>>
                            <span class="tg-item-fig">
                                <?php if (!empty($it['avatar']) && !empty($it['avatar']['src'])) : ?>
                                    <img src="<?php echo esc_url($it['avatar']['src']); ?>" alt="">
                                <?php elseif (!empty($it['avatar'])) : ?>
                                    <?php echo esc_html($it['avatar']['initials']); ?>
                                <?php else : ?>
                                    <?php echo bimverdi_get_icon_svg($it['icon'] ?? 'file-text', 15); ?>
                                <?php endif; ?>
                            </span>
                            <span class="tg-item-body">
                                <span class="tg-item-title"><?php echo esc_html($it['title']); ?></span>
                                <?php if (!empty($it['meta'])) : ?>
                                    <span class="tg-item-meta"><?php echo esc_html($it['meta']); ?></span>
                                <?php endif; ?>
                            </span>
                            <?php if (!empty($it['external'])) : ?>
                                <span class="tg-item-ext"><?php echo bimverdi_get_icon_svg('external-link', 14); ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($b['arkiv_url'])) : ?>
                        <a class="tg-block-more" href="<?php echo esc_url($b['arkiv_url']); ?>">
                            Se alle <?php echo (int) $b['total']; ?> <?php echo bimverdi_get_icon_svg('arrow-right', 14); ?>
                        </a>
                    <?php endif; ?>
                </section>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <div class="tg-empty-state">
                <p>Denne temagruppen har ikke noe innhold ennå.</p>
                <p style="margin-top: 8px;"><a href="<?php echo esc_url(home_url('/min-side/')); ?>">Bli med og bidra via Min Side</a></p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<?php if ($is_hovedkontakt && $temagruppe_term && !$is_member) : ?>
<!-- ══ Join Temagruppe Modal ══ -->
<div class="tg-modal-overlay" id="tg-join-modal">
    <div class="tg-modal">
        <h3>Registrer foretaket i <?php echo esc_html($temagruppe_navn); ?></h3>
        <p>
            Ved å registrere <strong><?php echo esc_html($foretak_name); ?></strong> i denne temagruppen
            blir foretaket synlig i deltakerlisten og får tilgang til gruppens aktiviteter.
        </p>
        <div class="tg-modal-actions">
            <?php
            bimverdi_button(['text' => 'Avbryt', 'variant' => 'secondary', 'size' => 'sm', 'id' => 'tg-join-cancel']);
            bimverdi_button(['text' => 'Bekreft', 'variant' => 'primary', 'size' => 'sm', 'icon' => 'check', 'id' => 'tg-join-confirm']);
            ?>
        </div>
    </div>
</div>

<script>
(function() {
    var joinBtn = document.getElementById('tg-join-btn');
    var modal = document.getElementById('tg-join-modal');
    if (!joinBtn || !modal) return;

    var cancelBtn = document.getElementById('tg-join-cancel');
    var confirmBtn = document.getElementById('tg-join-confirm');
    var termId = joinBtn.dataset.termId;

    joinBtn.addEventListener('click', function() { modal.classList.add('active'); });
    cancelBtn.addEventListener('click', function() { modal.classList.remove('active'); });
    modal.addEventListener('click', function(e) { if (e.target === modal) modal.classList.remove('active'); });

    confirmBtn.addEventListener('click', function() {
        confirmBtn.classList.add('bv-btn--loading');
        confirmBtn.textContent = 'Registrerer...';

        var formData = new FormData();
        formData.append('action', 'bimverdi_join_temagruppe');
        formData.append('nonce', '<?php echo wp_create_nonce('bimverdi_temagruppe_membership'); ?>');
        formData.append('term_id', termId);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST', credentials: 'same-origin', body: formData,
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                modal.classList.remove('active');
                joinBtn.outerHTML = '<span class="tg-member-badge">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg> ' +
                    data.data.foretak_name + ' er med</span>';
                setTimeout(function() { location.reload(); }, 1200);
            } else {
                alert(data.data.message || 'Noe gikk galt.');
                confirmBtn.classList.remove('bv-btn--loading');
                confirmBtn.textContent = 'Bekreft';
            }
        })
        .catch(function() {
            alert('Noe gikk galt. Prøv igjen.');
            confirmBtn.classList.remove('bv-btn--loading');
            confirmBtn.textContent = 'Bekreft';
        });
    });
})();
</script>
<?php endif; ?>

<?php
endwhile; endif;

get_footer();
