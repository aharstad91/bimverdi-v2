<?php
/**
 * Single Temagruppe Template (Data Table Layout)
 *
 * Displays a theme group with tabbed content and data tables.
 *
 * Uses live WordPress data.
 *
 * @package BimVerdi_Theme
 */

get_header();

if (have_posts()) : while (have_posts()) : the_post();

$post_id = get_the_ID();

// ── ACF Fields ──
$kort_beskrivelse = get_field('kort_beskrivelse', $post_id);
$status = get_field('status', $post_id) ?: 'aktiv';
$motefrekvens = get_field('motefrekvens', $post_id);
$hero_illustrasjon = get_field('hero_illustrasjon', $post_id);

// Fagansvarlig
$fagansvarlig_navn = get_field('fagansvarlig_navn', $post_id);
$fagansvarlig_tittel = get_field('fagansvarlig_tittel', $post_id);
$fagansvarlig_bedrift_id = get_field('fagansvarlig_bedrift', $post_id);
$fagansvarlig_bilde_id = get_field('fagansvarlig_bilde', $post_id);
$fagansvarlig_linkedin = get_field('fagansvarlig_linkedin', $post_id);

$bedrift_navn = $fagansvarlig_bedrift_id ? get_the_title($fagansvarlig_bedrift_id) : '';
$bilde_url = $fagansvarlig_bilde_id ? wp_get_attachment_image_url($fagansvarlig_bilde_id, 'thumbnail') : null;

$fagansvarlig_initials = '';
if ($fagansvarlig_navn) {
    $name_parts = explode(' ', $fagansvarlig_navn);
    if (count($name_parts) >= 2) {
        $fagansvarlig_initials = strtoupper(substr($name_parts[0], 0, 1) . substr(end($name_parts), 0, 1));
    } else {
        $fagansvarlig_initials = strtoupper(substr($fagansvarlig_navn, 0, 2));
    }
}

// ── Temagruppe identification ──
$temagruppe_navn = get_the_title();
$temagruppe_term = get_term_by('name', $temagruppe_navn, 'temagruppe');

// ── Color map for temagrupper ──
$tg_colors = [
    'BIMtech'       => '#FF8B5E',
    'ByggesaksBIM'  => '#2E86DE',
    'ProsjektBIM'   => '#27AE60',
    'EiendomsBIM'   => '#8E44AD',
    'MiljoBIM'      => '#16A085',
    'MiljoeBIM'     => '#16A085',
    'SirkBIM'       => '#E74C3C',
];
// Find color: try exact match, then normalized (strip accents/spaces)
$tg_color = '#FF8B5E'; // default
$tg_slug = sanitize_title($temagruppe_navn);
foreach ($tg_colors as $name => $color) {
    if (strcasecmp($name, $temagruppe_navn) === 0 || sanitize_title($name) === $tg_slug) {
        $tg_color = $color;
        break;
    }
}

// ── Emoji map for temagrupper ──
$tg_emojis = [
    'BIMtech'       => "\xF0\x9F\x94\xA7",
    'ByggesaksBIM'  => "\xF0\x9F\x8F\x97\xEF\xB8\x8F",
    'ProsjektBIM'   => "\xF0\x9F\x93\x90",
    'EiendomsBIM'   => "\xF0\x9F\x8F\xA2",
    'MiljoBIM'      => "\xF0\x9F\x8C\xB1",
    'MiljoeBIM'     => "\xF0\x9F\x8C\xB1",
    'SirkBIM'       => "\xE2\x99\xBB\xEF\xB8\x8F",
];
$tg_emoji = "\xF0\x9F\x93\x8C"; // default pin
foreach ($tg_emojis as $name => $emoji) {
    if (strcasecmp($name, $temagruppe_navn) === 0 || sanitize_title($name) === $tg_slug) {
        $tg_emoji = $emoji;
        break;
    }
}

// ── Norwegian months ──
$months_no = [
    'januar', 'februar', 'mars', 'april', 'mai', 'juni',
    'juli', 'august', 'september', 'oktober', 'november', 'desember'
];

// ── Membership status (for "Bli med" button) ──
$current_user_id = get_current_user_id();
$is_logged_in = (bool) $current_user_id;
$is_hovedkontakt = $is_logged_in && function_exists('bimverdi_is_hovedkontakt') && bimverdi_is_hovedkontakt($current_user_id);
$user_foretak_id = $is_logged_in && function_exists('bimverdi_get_user_company') ? bimverdi_get_user_company($current_user_id) : null;
$is_member = false;
if ($user_foretak_id && $temagruppe_term) {
    $is_member = has_term($temagruppe_term->term_id, 'temagruppe', $user_foretak_id);
}
$foretak_name = $user_foretak_id ? get_the_title($user_foretak_id) : '';

// ══════════════════════════════════════════════════════════════════════
// DATA QUERIES
// ══════════════════════════════════════════════════════════════════════

// ── Deltakere (foretak) ──
$deltakere = [];
$deltakere_count = 0;
if ($temagruppe_term) {
    $member_query = new WP_Query([
        'post_type'      => 'foretak',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'tax_query'      => [[
            'taxonomy' => 'temagruppe',
            'field'    => 'term_id',
            'terms'    => $temagruppe_term->term_id,
        ]],
    ]);
    $deltakere = $member_query->posts;
    $deltakere_count = $member_query->found_posts;
    wp_reset_postdata();
}

// ── Arrangementer ──
$arrangementer = [];
$arrangementer_count = 0;
if ($temagruppe_term) {
    $today = date('Y-m-d');
    $event_query = new WP_Query([
        'post_type'      => 'arrangement',
        'posts_per_page' => -1,
        'meta_key'       => 'dato',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => [[
            'key'     => 'dato',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'DATE',
        ]],
        'tax_query' => [[
            'taxonomy' => 'temagruppe',
            'field'    => 'term_id',
            'terms'    => $temagruppe_term->term_id,
        ]],
    ]);
    $arrangementer = $event_query->posts;
    $arrangementer_count = $event_query->found_posts;
    wp_reset_postdata();
}

// ── Kunnskapskilder ──
$kunnskapskilder = [];
$kunnskapskilder_count = 0;
if ($temagruppe_term) {
    $ks_query = new WP_Query([
        'post_type'      => 'kunnskapskilde',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'tax_query'      => [[
            'taxonomy' => 'temagruppe',
            'field'    => 'term_id',
            'terms'    => $temagruppe_term->term_id,
        ]],
    ]);
    $kunnskapskilder = $ks_query->posts;
    $kunnskapskilder_count = $ks_query->found_posts;
    wp_reset_postdata();

    // Collect unique kildetype values (ACF field)
    $kildetype_config = [
        'standard'         => ['label' => 'Standard',       'icon' => "\xF0\x9F\x93\x90"],
        'veileder'         => ['label' => 'Veileder',       'icon' => "\xF0\x9F\x93\x98"],
        'mal'              => ['label' => 'Mal',            'icon' => "\xF0\x9F\x93\x84"],
        'forskningsrapport'=> ['label' => 'Forskning',      'icon' => "\xF0\x9F\x94\xAC"],
        'casestudie'       => ['label' => 'Case',           'icon' => "\xF0\x9F\x92\xBC"],
        'opplaering'       => ['label' => 'Opplaering',     'icon' => "\xF0\x9F\x8E\x93"],
        'dokumentasjon'    => ['label' => 'Dokumentasjon',  'icon' => "\xF0\x9F\x93\x91"],
        'nettressurs'      => ['label' => 'Nettressurs',    'icon' => "\xF0\x9F\x8C\x90"],
        'regelverk'        => ['label' => 'Regelverk',      'icon' => "\xF0\x9F\x93\x8B"],
        'rapport'          => ['label' => 'Rapport',        'icon' => "\xF0\x9F\x93\x8A"],
        'strategi'         => ['label' => 'Strategi',       'icon' => "\xF0\x9F\x97\xBA\xEF\xB8\x8F"],
        'verktoy'          => ['label' => 'Verktoy',        'icon' => "\xF0\x9F\x94\xA7"],
        'veiledning'       => ['label' => 'Veiledning',     'icon' => "\xF0\x9F\x93\x98"],
        'annet'            => ['label' => 'Annet',          'icon' => "\xF0\x9F\x93\x84"],
    ];
}

// ── Verktoy (dual approach: taxonomy + ACF formaalstema) ──
$verktoy = [];
$verktoy_count = 0;

// Approach 1: taxonomy
$verktoy_ids = [];
if ($temagruppe_term) {
    $vt_query = new WP_Query([
        'post_type'      => 'verktoy',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => [[
            'taxonomy' => 'temagruppe',
            'field'    => 'term_id',
            'terms'    => $temagruppe_term->term_id,
        ]],
    ]);
    $verktoy_ids = $vt_query->posts;
    wp_reset_postdata();
}

// Approach 2: ACF formaalstema field
$vt_acf_query = new WP_Query([
    'post_type'      => 'verktoy',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_query'     => [[
        'key'     => 'formaalstema',
        'value'   => $temagruppe_navn,
        'compare' => '=',
    ]],
]);
$verktoy_ids = array_unique(array_merge($verktoy_ids, $vt_acf_query->posts));
wp_reset_postdata();

if (!empty($verktoy_ids)) {
    $vt_full_query = new WP_Query([
        'post_type'      => 'verktoy',
        'posts_per_page' => -1,
        'post__in'       => $verktoy_ids,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
    $verktoy = $vt_full_query->posts;
    $verktoy_count = count($verktoy);
    wp_reset_postdata();
}

// ── Artikler ──
$artikler = [];
$artikler_count = 0;
if ($temagruppe_term) {
    $art_query = new WP_Query([
        'post_type'      => 'artikkel',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'tax_query'      => [[
            'taxonomy' => 'temagruppe',
            'field'    => 'term_id',
            'terms'    => $temagruppe_term->term_id,
        ]],
    ]);
    $artikler = $art_query->posts;
    $artikler_count = $art_query->found_posts;
    wp_reset_postdata();
}

// ══════════════════════════════════════════════════════════════════════
// BUILD TABS (only show tabs with content)
// ══════════════════════════════════════════════════════════════════════

$tabs = [
    'oversikt' => ['label' => 'Oversikt', 'count' => null],
];
if ($arrangementer_count > 0) {
    $tabs['arrangementer'] = ['label' => 'Arrangementer', 'count' => $arrangementer_count];
}
if ($kunnskapskilder_count > 0) {
    $tabs['kunnskapskilder'] = ['label' => 'Kunnskapskilder', 'count' => $kunnskapskilder_count];
}
if ($verktoy_count > 0) {
    $tabs['verktoy'] = ['label' => 'Verktoy', 'count' => $verktoy_count];
}
if ($artikler_count > 0) {
    $tabs['artikler'] = ['label' => 'Artikler', 'count' => $artikler_count];
}
if ($deltakere_count > 0) {
    $tabs['deltakere'] = ['label' => 'Deltakere', 'count' => $deltakere_count];
}

// ══════════════════════════════════════════════════════════════════════
// TABLE ROW BUILDER FUNCTIONS
// ══════════════════════════════════════════════════════════════════════

/**
 * Build table rows for arrangementer
 */
function bv_tg_build_event_rows($events, $months_no) {
    $rows = [];
    foreach ($events as $event) {
        $event_id = $event->ID;
        $event_date = get_field('dato', $event_id);
        $is_digital = get_field('er_digitalt', $event_id);
        $location = get_field('sted', $event_id);
        $event_types = wp_get_post_terms($event_id, 'arrangementstype', ['fields' => 'names']);
        $event_type = !empty($event_types) ? $event_types[0] : '';
        $permalink = get_permalink($event_id);

        $formatted_date = '';
        if ($event_date) {
            $date_obj = DateTime::createFromFormat('Ymd', $event_date);
            if ($date_obj) {
                $formatted_date = $date_obj->format('j') . '. ' . $months_no[(int)$date_obj->format('n') - 1] . ' ' . $date_obj->format('Y');
            }
        }

        $location_text = $is_digital ? 'Digitalt' : ($location ?: '');

        $rows[] = [
            'navn' => ['label' => $event->post_title, 'href' => $permalink],
            'type' => $event_type ? ['label' => $event_type] : '',
            'dato' => $formatted_date ?: '—',
            'sted' => $location_text ?: '—',
        ];
    }
    return $rows;
}

/**
 * Build table rows for artikler
 */
function bv_tg_build_article_rows($artikler, $months_no) {
    $rows = [];
    foreach ($artikler as $artikkel) {
        $artikkel_id = $artikkel->ID;
        $permalink = get_permalink($artikkel_id);
        $author_name = get_the_author_meta('display_name', $artikkel->post_author);
        $tilknyttet_foretak = get_field('tilknyttet_foretak', $artikkel_id);
        $company_name = $tilknyttet_foretak ? get_the_title($tilknyttet_foretak) : '';
        $author_display = $company_name ?: $author_name;

        $date_obj = new DateTime($artikkel->post_date);
        $formatted_date = $date_obj->format('j') . '. ' . $months_no[(int)$date_obj->format('n') - 1] . ' ' . $date_obj->format('Y');

        $rows[] = [
            'tittel' => ['label' => $artikkel->post_title, 'href' => $permalink],
            'forfatter' => $author_display,
            'dato' => $formatted_date,
        ];
    }
    return $rows;
}

/**
 * Build table rows for kunnskapskilder
 */
function bv_tg_build_ks_rows($kunnskapskilder, $kildetype_config) {
    $rows = [];
    foreach ($kunnskapskilder as $ks) {
        $ks_id = $ks->ID;
        $kildetype = get_field('kildetype', $ks_id);
        $ekstern_lenke = get_field('ekstern_lenke', $ks_id);
        $resource_url = $ekstern_lenke ?: get_permalink($ks_id);
        $is_external = !empty($ekstern_lenke);

        $type_conf = isset($kildetype_config[$kildetype]) ? $kildetype_config[$kildetype] : ['label' => 'Annet'];

        $rows[] = [
            'icon'      => ['icon' => 'file-text'],
            'navn'      => ['label' => $ks->post_title, 'href' => $resource_url, 'external' => $is_external],
            'kildetype' => $type_conf['label'] ? ['label' => $type_conf['label']] : '',
        ];
    }
    return $rows;
}

/**
 * Build table rows for deltakere (foretak)
 */
function bv_tg_build_deltaker_rows($deltakere) {
    $rows = [];
    foreach ($deltakere as $foretak) {
        $foretak_id = $foretak->ID;
        $company_name = get_the_title($foretak_id);
        $permalink = get_permalink($foretak_id);
        $bransje_terms = wp_get_post_terms($foretak_id, 'bransjekategori', ['fields' => 'names']);
        $bransje = !empty($bransje_terms) ? $bransje_terms[0] : '';
        $bv_rolle = get_field('bv_rolle', $foretak_id);
        $rolle_display = ($bv_rolle && $bv_rolle !== 'Ikke deltaker') ? $bv_rolle : '';

        // Build initials from company name
        $words = explode(' ', $company_name);
        $initials = count($words) >= 2
            ? strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1))
            : strtoupper(mb_substr($company_name, 0, 2));

        // Check for company logo (ACF field name: 'logo')
        $logo = get_field('logo', $foretak_id);
        $logo_url = '';
        if ($logo) {
            $logo_url = is_array($logo) ? ($logo['url'] ?? '') : wp_get_attachment_url($logo);
        }

        $rows[] = [
            'logo'    => ['src' => $logo_url, 'initials' => $initials],
            'foretak' => ['label' => $company_name, 'href' => $permalink],
            'bransje' => $bransje ? ['label' => $bransje] : '—',
            'rolle'   => $rolle_display ? ['label' => $rolle_display] : '—',
        ];
    }
    return $rows;
}

/**
 * Build table rows for verktoy
 */
function bv_tg_build_verktoy_rows($verktoy) {
    $rows = [];
    foreach ($verktoy as $tool) {
        $tool_id = $tool->ID;
        $tool_name = get_the_title($tool_id);
        $permalink = get_permalink($tool_id);
        $eier_id = get_field('eier_leverandor', $tool_id);
        $eier_navn = $eier_id ? get_the_title($eier_id) : '';
        $categories = wp_get_post_terms($tool_id, 'verktoykategori', ['fields' => 'names']);
        $category = !empty($categories) ? $categories[0] : '';

        // Tool logo
        $logo = get_field('verktoy_logo', $tool_id);
        $logo_url = '';
        if ($logo) {
            $logo_url = is_array($logo) ? ($logo['url'] ?? '') : wp_get_attachment_url($logo);
        }
        if (!$logo_url) {
            $logo_url = get_post_meta($tool_id, 'verktoy_logo_url', true);
        }

        // Initials fallback
        $words = explode(' ', $tool_name);
        $initials = count($words) >= 2
            ? strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1))
            : strtoupper(mb_substr($tool_name, 0, 2));

        $rows[] = [
            'logo'      => ['src' => $logo_url, 'initials' => $initials],
            'verktoy'   => ['label' => $tool_name, 'href' => $permalink],
            'kategori'  => $category ? ['label' => $category] : '—',
            'leverandor'=> $eier_navn ?: '—',
        ];
    }
    return $rows;
}

?>

<style>
/* ══════════════════════════════════════════════════════════════════════
   Temagruppe Single - Data Table Template Styles
   ══════════════════════════════════════════════════════════════════════ */

:root {
    --canvas-bg: #FAF9F7;
    --canvas-card-bg: #FFFFFF;
    --canvas-card-border: #E7E5E4;
    --canvas-text: #1A1A1A;
    --canvas-text-secondary: #57534E;
    --canvas-text-muted: #A8A29E;
    --canvas-accent: <?php echo esc_attr($tg_color); ?>;
    --canvas-radius: 12px;
}

/* ── Header (pinned above content) ── */
.tg-header {
    background: #FFFFFF;
    border-bottom: none;
}
.tg-header-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 32px 24px 0;
}

/* Breadcrumb */


.tg-hero-title {
    font-family: 'Familjen Grotesk', sans-serif;
    font-size: 28px;
    font-weight: 700;
    color: var(--canvas-text);
    margin: 0 0 16px;
    line-height: 1.2;
}
.tg-hero-desc {
    font-size: 15px;
    line-height: 1.6;
    color: var(--canvas-text-secondary);
    max-width: 720px;
    margin: 0;
}
.tg-description {
    font-size: 15px;
    line-height: 1.7;
    color: var(--canvas-text-secondary);
    max-width: 720px;
}
.tg-description p { margin: 0 0 12px; }
.tg-description p:last-child { margin-bottom: 0; }

.tg-fagradgiver {
    margin-top: 16px;
}

.tg-tabs-row .bv-btn {
    margin-left: auto;
    flex-shrink: 0;
}
.tg-member-badge {
    margin-left: auto;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #16A085;
    background: #E8F8F5;
    padding: 6px 14px;
    border-radius: 100px;
    white-space: nowrap;
}

/* ── Join Modal ── */
.tg-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.4);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.tg-modal-overlay.active { display: flex; }
.tg-modal {
    background: #fff;
    border-radius: 12px;
    padding: 32px;
    max-width: 480px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}
.tg-modal h3 {
    font-size: 18px;
    font-weight: 600;
    color: #18181B;
    margin: 0 0 8px;
}
.tg-modal p {
    font-size: 14px;
    color: #57534E;
    line-height: 1.6;
    margin: 0 0 24px;
}
.tg-modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
.tg-modal .bv-btn--loading {
    opacity: 0.6;
    pointer-events: none;
}

/* ── Tabs ── */
.tg-tabs-row {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    margin-top: 24px;
}
.tg-tabs-component {
    flex: 1;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.tg-tabs-component .bv-tabs__list {
    margin-bottom: 0;
}

/* ── Content area ── */
.tg-content {
    background: white;
}
.tg-content-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 32px 24px 80px;
}

/* ── Sections ── */
.canvas-section {
    margin-bottom: 52px;
}
.canvas-section:last-child { margin-bottom: 0; }

.section-title-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.section-title {
    font-family: 'Familjen Grotesk', sans-serif;
    font-size: 18px;
    font-weight: 700;
    color: var(--canvas-text);
    margin: 0;
    white-space: nowrap;
}

.section-count {
    font-size: 13px;
    font-weight: 500;
    color: var(--canvas-text-muted);
    background: #F0EDEA;
    padding: 2px 10px;
    border-radius: 100px;
    white-space: nowrap;
}

/* ── Tab content visibility ── */
.canvas-section[data-tab] { display: none; }
.canvas-section[data-tab].visible { display: block; }
.canvas-section[data-tab="all"] { display: block; }

/* ── Empty state ── */
.tg-empty-state {
    text-align: center;
    padding: 48px 24px;
    color: var(--canvas-text-muted);
    font-size: 15px;
}
.tg-empty-state a {
    color: var(--canvas-accent);
    text-decoration: none;
}
.tg-empty-state a:hover {
    text-decoration: underline;
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .tg-tabs-row .bv-btn { margin-left: 0; }
    .tg-header-inner { padding: 20px 16px 0; }
    .tg-tabs-row { flex-direction: column; gap: 8px; }
}
</style>

<main>
    <!-- ══ Pinned Header ══ -->
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
            $content = get_the_content();
            if ($content) :
                $content = apply_filters('the_content', $content);
            ?>
            <div class="tg-description"><?php echo $content; ?></div>
            <?php elseif ($kort_beskrivelse) : ?>
            <p class="tg-hero-desc"><?php echo esc_html($kort_beskrivelse); ?></p>
            <?php elseif (has_excerpt()) : ?>
            <p class="tg-hero-desc"><?php echo esc_html(get_the_excerpt()); ?></p>
            <?php endif; ?>

            <?php if ($fagansvarlig_navn) : ?>
            <div class="tg-fagradgiver">
                <?php
                require_once get_template_directory() . '/parts/components/item.php';
                bimverdi_item([
                    'avatar'       => $fagansvarlig_initials,
                    'avatar_src'   => $bilde_url ?: '',
                    'avatar_color' => $tg_color,
                    'title'        => 'Fagradgiver: ' . esc_html($fagansvarlig_navn),
                    'description'  => $fagansvarlig_tittel ?: '',
                    'size'         => 'sm',
                    'href'         => $fagansvarlig_linkedin ?: '',
                ]);
                ?>
            </div>
            <?php endif; ?>

            <div class="tg-tabs-row">
                <?php
                // Build tab labels with counts
                $tab_labels = [];
                foreach ($tabs as $key => $tab) {
                    $label = $tab['label'];
                    if ($tab['count'] !== null) {
                        $label .= ' (' . $tab['count'] . ')';
                    }
                    $tab_labels[$key] = $label;
                }

                require_once get_template_directory() . '/parts/components/tabs.php';
                bimverdi_tabs([
                    'id'      => 'tg-tabs',
                    'variant' => 'default',
                    'default' => 'oversikt',
                    'tabs'    => $tab_labels,
                    'class'   => 'tg-tabs-component',
                ]);
                bimverdi_tabs_end();

                require_once get_template_directory() . '/parts/components/button.php';

                if ($is_hovedkontakt && $temagruppe_term) :
                    if ($is_member) : ?>
                        <span class="tg-member-badge">
                            <i data-lucide="check-circle" style="width:14px;height:14px;"></i>
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
                endif;
                ?>
            </div>
        </div>
    </div>

    <!-- ══ Content Area ══ -->
    <div class="tg-content">

        <div class="tg-content-inner">

            <?php
            // ══════════════════════════════════════════════════════════
            // OVERSIKT TAB: Shows all sections with data-tab="all"
            // ══════════════════════════════════════════════════════════
            ?>

            <?php if ($arrangementer_count > 0) : ?>
            <!-- ═══ OVERSIKT: Arrangementer ═══ -->
            <div class="canvas-section" data-tab="all" data-section="arrangementer">
                <div class="section-title-row">
                    <h3 class="section-title">Arrangementer</h3>
                    <span class="section-count"><?php echo $arrangementer_count; ?></span>
                </div>
                <?php get_template_part('parts/components/data-table', null, [
                    'columns' => [
                        ['key' => 'navn', 'label' => 'Arrangement', 'type' => 'link'],
                        ['key' => 'type', 'label' => 'Type', 'type' => 'badge'],
                        ['key' => 'dato', 'label' => 'Dato'],
                        ['key' => 'sted', 'label' => 'Sted'],
                    ],
                    'rows' => bv_tg_build_event_rows($arrangementer, $months_no),
                ]); ?>
            </div>
            <?php endif; ?>

            <?php if ($kunnskapskilder_count > 0) : ?>
            <!-- ═══ OVERSIKT: Kunnskapskilder ═══ -->
            <div class="canvas-section" data-tab="all" data-section="kunnskapskilder">
                <div class="section-title-row">
                    <h3 class="section-title">Kunnskapskilder</h3>
                    <span class="section-count"><?php echo $kunnskapskilder_count; ?></span>
                </div>
                <?php get_template_part('parts/components/data-table', null, [
                    'columns' => [
                        ['key' => 'icon', 'label' => '', 'type' => 'avatar', 'width' => '48px'],
                        ['key' => 'navn', 'label' => 'Kunnskapskilde', 'type' => 'link'],
                        ['key' => 'kildetype', 'label' => 'Type', 'type' => 'badge'],
                    ],
                    'rows' => bv_tg_build_ks_rows($kunnskapskilder, $kildetype_config),
                ]); ?>
            </div>
            <?php endif; ?>

            <?php if ($verktoy_count > 0) : ?>
            <!-- ═══ OVERSIKT: Verktoy ═══ -->
            <div class="canvas-section" data-tab="all" data-section="verktoy">
                <div class="section-title-row">
                    <h3 class="section-title">Verktoy</h3>
                    <span class="section-count"><?php echo $verktoy_count; ?></span>
                </div>
                <?php get_template_part('parts/components/data-table', null, [
                    'columns' => [
                        ['key' => 'logo', 'label' => '', 'type' => 'avatar', 'width' => '48px'],
                        ['key' => 'verktoy', 'label' => 'Verktøy', 'type' => 'link'],
                        ['key' => 'kategori', 'label' => 'Kategori', 'type' => 'badge'],
                        ['key' => 'leverandor', 'label' => 'Leverandør'],
                    ],
                    'rows' => bv_tg_build_verktoy_rows($verktoy),
                ]); ?>
            </div>
            <?php endif; ?>

            <?php if ($artikler_count > 0) : ?>
            <!-- ═══ OVERSIKT: Artikler ═══ -->
            <div class="canvas-section" data-tab="all" data-section="artikler">
                <div class="section-title-row">
                    <h3 class="section-title">Artikler</h3>
                    <span class="section-count"><?php echo $artikler_count; ?></span>
                </div>
                <?php get_template_part('parts/components/data-table', null, [
                    'columns' => [
                        ['key' => 'tittel', 'label' => 'Artikkel', 'type' => 'link'],
                        ['key' => 'forfatter', 'label' => 'Forfatter'],
                        ['key' => 'dato', 'label' => 'Publisert'],
                    ],
                    'rows' => bv_tg_build_article_rows($artikler, $months_no),
                ]); ?>
            </div>
            <?php endif; ?>

            <?php if ($deltakere_count > 0) : ?>
            <!-- ═══ OVERSIKT: Deltakere ═══ -->
            <div class="canvas-section" data-tab="all" data-section="deltakere">
                <div class="section-title-row">
                    <h3 class="section-title">Deltakende foretak</h3>
                    <span class="section-count"><?php echo $deltakere_count; ?></span>
                </div>
                <?php get_template_part('parts/components/data-table', null, [
                    'columns' => [
                        ['key' => 'logo', 'label' => '', 'type' => 'avatar', 'width' => '48px'],
                        ['key' => 'foretak', 'label' => 'Foretak', 'type' => 'link'],
                        ['key' => 'bransje', 'label' => 'Bransje', 'type' => 'badge'],
                        ['key' => 'rolle', 'label' => 'Rolle', 'type' => 'badge'],
                    ],
                    'rows' => bv_tg_build_deltaker_rows($deltakere),
                ]); ?>
            </div>
            <?php endif; ?>


            <?php
            // ══════════════════════════════════════════════════════════
            // INDIVIDUAL TABS: Each section duplicated for independent
            // display when that tab is active
            // ══════════════════════════════════════════════════════════
            ?>

            <?php if ($arrangementer_count > 0) : ?>
            <!-- ═══ TAB: Arrangementer ═══ -->
            <div class="canvas-section" data-tab="arrangementer" data-section="arrangementer-tab">
                <div class="section-title-row">
                    <h3 class="section-title">Arrangementer</h3>
                    <span class="section-count"><?php echo $arrangementer_count; ?></span>
                </div>
                <?php get_template_part('parts/components/data-table', null, [
                    'columns' => [
                        ['key' => 'navn', 'label' => 'Arrangement', 'type' => 'link'],
                        ['key' => 'type', 'label' => 'Type', 'type' => 'badge'],
                        ['key' => 'dato', 'label' => 'Dato'],
                        ['key' => 'sted', 'label' => 'Sted'],
                    ],
                    'rows' => bv_tg_build_event_rows($arrangementer, $months_no),
                ]); ?>
            </div>
            <?php endif; ?>

            <?php if ($kunnskapskilder_count > 0) : ?>
            <!-- ═══ TAB: Kunnskapskilder ═══ -->
            <div class="canvas-section" data-tab="kunnskapskilder" data-section="kunnskapskilder-tab">
                <div class="section-title-row">
                    <h3 class="section-title">Kunnskapskilder</h3>
                    <span class="section-count"><?php echo $kunnskapskilder_count; ?></span>
                </div>
                <?php get_template_part('parts/components/data-table', null, [
                    'columns' => [
                        ['key' => 'icon', 'label' => '', 'type' => 'avatar', 'width' => '48px'],
                        ['key' => 'navn', 'label' => 'Kunnskapskilde', 'type' => 'link'],
                        ['key' => 'kildetype', 'label' => 'Type', 'type' => 'badge'],
                    ],
                    'rows' => bv_tg_build_ks_rows($kunnskapskilder, $kildetype_config),
                ]); ?>
            </div>
            <?php endif; ?>

            <?php if ($verktoy_count > 0) : ?>
            <!-- ═══ TAB: Verktoy ═══ -->
            <div class="canvas-section" data-tab="verktoy" data-section="verktoy-tab">
                <div class="section-title-row">
                    <h3 class="section-title">Verktoy</h3>
                    <span class="section-count"><?php echo $verktoy_count; ?></span>
                </div>
                <?php get_template_part('parts/components/data-table', null, [
                    'columns' => [
                        ['key' => 'logo', 'label' => '', 'type' => 'avatar', 'width' => '48px'],
                        ['key' => 'verktoy', 'label' => 'Verktøy', 'type' => 'link'],
                        ['key' => 'kategori', 'label' => 'Kategori', 'type' => 'badge'],
                        ['key' => 'leverandor', 'label' => 'Leverandør'],
                    ],
                    'rows' => bv_tg_build_verktoy_rows($verktoy),
                ]); ?>
            </div>
            <?php endif; ?>

            <?php if ($artikler_count > 0) : ?>
            <!-- ═══ TAB: Artikler ═══ -->
            <div class="canvas-section" data-tab="artikler" data-section="artikler-tab">
                <div class="section-title-row">
                    <h3 class="section-title">Artikler</h3>
                    <span class="section-count"><?php echo $artikler_count; ?></span>
                </div>
                <?php get_template_part('parts/components/data-table', null, [
                    'columns' => [
                        ['key' => 'tittel', 'label' => 'Artikkel', 'type' => 'link'],
                        ['key' => 'forfatter', 'label' => 'Forfatter'],
                        ['key' => 'dato', 'label' => 'Publisert'],
                    ],
                    'rows' => bv_tg_build_article_rows($artikler, $months_no),
                ]); ?>
            </div>
            <?php endif; ?>

            <?php if ($deltakere_count > 0) : ?>
            <!-- ═══ TAB: Deltakere ═══ -->
            <div class="canvas-section" data-tab="deltakere" data-section="deltakere-tab">
                <div class="section-title-row">
                    <h3 class="section-title">Deltakende foretak</h3>
                    <span class="section-count"><?php echo $deltakere_count; ?></span>
                </div>
                <?php get_template_part('parts/components/data-table', null, [
                    'columns' => [
                        ['key' => 'logo', 'label' => '', 'type' => 'avatar', 'width' => '48px'],
                        ['key' => 'foretak', 'label' => 'Foretak', 'type' => 'link'],
                        ['key' => 'bransje', 'label' => 'Bransje', 'type' => 'badge'],
                        ['key' => 'rolle', 'label' => 'Rolle', 'type' => 'badge'],
                    ],
                    'rows' => bv_tg_build_deltaker_rows($deltakere),
                ]); ?>
            </div>
            <?php endif; ?>

            <?php
            // If no content at all, show empty state
            $total_items = $deltakere_count + $arrangementer_count + $kunnskapskilder_count + $verktoy_count + $artikler_count;
            if ($total_items === 0) : ?>
            <div class="canvas-section" data-tab="all">
                <div class="tg-empty-state">
                    <p>Denne temagruppen har ikke noe innhold enna.</p>
                    <p style="margin-top: 8px;"><a href="<?php echo esc_url(home_url('/min-side/')); ?>">Bli med og bidra via Min Side</a></p>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- .tg-content-inner -->

    </div><!-- .tg-content -->
</main>

<script>
(function() {
    'use strict';

    // ── Tab switching (hooks into bimverdi_tabs component) ──
    var tabButtons = document.querySelectorAll('.tg-tabs-component .bv-tabs__trigger');
    var sections = document.querySelectorAll('.canvas-section[data-tab]');

    tabButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tab = btn.getAttribute('data-bv-tab');

            sections.forEach(function(section) {
                var sectionTab = section.dataset.tab;
                if (tab === 'oversikt') {
                    section.style.display = sectionTab === 'all' ? 'block' : 'none';
                    section.classList.toggle('visible', sectionTab === 'all');
                } else {
                    var show = sectionTab === tab;
                    section.style.display = show ? 'block' : 'none';
                    section.classList.toggle('visible', show);
                }
            });
        });
    });

})();
</script>

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
            require_once get_template_directory() . '/parts/components/button.php';
            bimverdi_button([
                'text' => 'Avbryt',
                'variant' => 'secondary',
                'size' => 'sm',
                'attrs' => ['id' => 'tg-join-cancel'],
            ]);
            bimverdi_button([
                'text' => 'Bekreft',
                'variant' => 'primary',
                'size' => 'sm',
                'icon' => 'check',
                'attrs' => ['id' => 'tg-join-confirm'],
            ]);
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

    joinBtn.addEventListener('click', function() {
        modal.classList.add('active');
    });

    cancelBtn.addEventListener('click', function() {
        modal.classList.remove('active');
    });

    modal.addEventListener('click', function(e) {
        if (e.target === modal) modal.classList.remove('active');
    });

    confirmBtn.addEventListener('click', function() {
        confirmBtn.classList.add('bv-btn--loading');
        confirmBtn.textContent = 'Registrerer...';

        var formData = new FormData();
        formData.append('action', 'bimverdi_join_temagruppe');
        formData.append('nonce', '<?php echo wp_create_nonce('bimverdi_temagruppe_membership'); ?>');
        formData.append('term_id', termId);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                modal.classList.remove('active');
                // Replace button with member badge
                joinBtn.outerHTML = '<span class="tg-member-badge">' +
                    '<i data-lucide="check-circle" style="width:14px;height:14px;"></i> ' +
                    data.data.foretak_name + ' er med</span>';
                // Re-init Lucide icons
                if (window.lucide) lucide.createIcons();
                // Reload page after brief delay to update deltakerliste
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
