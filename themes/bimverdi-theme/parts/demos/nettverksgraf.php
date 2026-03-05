<?php
/**
 * Demo: Temagruppe Nettverksgraf
 *
 * D3.js force-directed network graph showing a temagruppe
 * and all its connected entities (foretak, verktoy, kunnskapskilder, arrangementer).
 *
 * Loaded via single-demo.php -> get_template_part('parts/demos/nettverksgraf')
 * Header is already loaded by the parent template.
 */

if (!defined('ABSPATH')) {
    exit;
}

// ─── Data Loading ───────────────────────────────────────────────────────────

$nodes = [];
$links = [];
$stats = ['foretak' => 0, 'verktoy' => 0, 'kunnskapskilde' => 0, 'arrangement' => 0];

// Find a theme_group post (prefer "BIMtech")
$tg_posts = get_posts([
    'post_type'      => 'theme_group',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
]);

$tg_post = null;
$tg_name = 'BIMtech';

if (!empty($tg_posts)) {
    // Prefer BIMtech if available
    foreach ($tg_posts as $p) {
        if (stripos($p->post_title, 'BIMtech') !== false) {
            $tg_post = $p;
            $tg_name = $p->post_title;
            break;
        }
    }
    // Fallback to first theme_group
    if (!$tg_post) {
        $tg_post = $tg_posts[0];
        $tg_name = $tg_post->post_title;
    }
}

// Get matching taxonomy term
$term = get_term_by('name', $tg_name, 'temagruppe');
// Also try slug-based lookup
if (!$term) {
    $term = get_term_by('slug', sanitize_title($tg_name), 'temagruppe');
}

$has_real_data = false;

if ($term && !is_wp_error($term)) {
    $center_id = 'tg_' . $term->term_id;

    // Central temagruppe node
    $nodes[] = [
        'id'    => $center_id,
        'label' => $tg_name,
        'type'  => 'temagruppe',
        'url'   => $tg_post ? get_permalink($tg_post) : '#',
        'desc'  => $term->description ?: ($tg_post ? wp_trim_words(get_the_excerpt($tg_post), 20) : ''),
    ];

    // Query connected foretak
    $foretak_query = new WP_Query([
        'post_type'      => 'foretak',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'tax_query'      => [['taxonomy' => 'temagruppe', 'field' => 'term_id', 'terms' => $term->term_id]],
    ]);
    if ($foretak_query->have_posts()) {
        $has_real_data = true;
        while ($foretak_query->have_posts()) {
            $foretak_query->the_post();
            $nid = 'foretak_' . get_the_ID();
            $nodes[] = [
                'id'    => $nid,
                'label' => get_the_title(),
                'type'  => 'foretak',
                'url'   => get_permalink(),
                'desc'  => wp_trim_words(get_the_excerpt(), 15) ?: '',
            ];
            $links[] = ['source' => $center_id, 'target' => $nid];
            $stats['foretak']++;
        }
    }
    wp_reset_postdata();

    // Query connected verktoy (via taxonomy)
    $verktoy_query = new WP_Query([
        'post_type'      => 'verktoy',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'tax_query'      => [['taxonomy' => 'temagruppe', 'field' => 'term_id', 'terms' => $term->term_id]],
    ]);
    if ($verktoy_query->have_posts()) {
        $has_real_data = true;
        while ($verktoy_query->have_posts()) {
            $verktoy_query->the_post();
            $nid = 'verktoy_' . get_the_ID();
            $nodes[] = [
                'id'    => $nid,
                'label' => get_the_title(),
                'type'  => 'verktoy',
                'url'   => get_permalink(),
                'desc'  => wp_trim_words(get_the_excerpt(), 15) ?: '',
            ];
            $links[] = ['source' => $center_id, 'target' => $nid];
            $stats['verktoy']++;
        }
    }
    wp_reset_postdata();

    // Also try ACF field "formaalstema" for verktoy
    if ($stats['verktoy'] === 0) {
        $verktoy_acf_query = new WP_Query([
            'post_type'      => 'verktoy',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [['key' => 'formaalstema', 'value' => $tg_name, 'compare' => 'LIKE']],
        ]);
        if ($verktoy_acf_query->have_posts()) {
            $has_real_data = true;
            while ($verktoy_acf_query->have_posts()) {
                $verktoy_acf_query->the_post();
                $nid = 'verktoy_' . get_the_ID();
                $nodes[] = [
                    'id'    => $nid,
                    'label' => get_the_title(),
                    'type'  => 'verktoy',
                    'url'   => get_permalink(),
                    'desc'  => wp_trim_words(get_the_excerpt(), 15) ?: '',
                ];
                $links[] = ['source' => $center_id, 'target' => $nid];
                $stats['verktoy']++;
            }
        }
        wp_reset_postdata();
    }

    // Query connected kunnskapskilder
    $kk_query = new WP_Query([
        'post_type'      => 'kunnskapskilde',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'tax_query'      => [['taxonomy' => 'temagruppe', 'field' => 'term_id', 'terms' => $term->term_id]],
    ]);
    if ($kk_query->have_posts()) {
        $has_real_data = true;
        while ($kk_query->have_posts()) {
            $kk_query->the_post();
            $nid = 'kunnskapskilde_' . get_the_ID();
            $nodes[] = [
                'id'    => $nid,
                'label' => get_the_title(),
                'type'  => 'kunnskapskilde',
                'url'   => get_permalink(),
                'desc'  => wp_trim_words(get_the_excerpt(), 15) ?: '',
            ];
            $links[] = ['source' => $center_id, 'target' => $nid];
            $stats['kunnskapskilde']++;
        }
    }
    wp_reset_postdata();

    // Query connected arrangementer
    $arr_query = new WP_Query([
        'post_type'      => 'arrangement',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'tax_query'      => [['taxonomy' => 'temagruppe', 'field' => 'term_id', 'terms' => $term->term_id]],
    ]);
    if ($arr_query->have_posts()) {
        $has_real_data = true;
        while ($arr_query->have_posts()) {
            $arr_query->the_post();
            $nid = 'arrangement_' . get_the_ID();
            $dato = get_field('arrangement_dato', get_the_ID());
            $nodes[] = [
                'id'    => $nid,
                'label' => get_the_title(),
                'type'  => 'arrangement',
                'url'   => get_permalink(),
                'desc'  => $dato ? 'Dato: ' . $dato : (wp_trim_words(get_the_excerpt(), 15) ?: ''),
            ];
            $links[] = ['source' => $center_id, 'target' => $nid];
            $stats['arrangement']++;
        }
    }
    wp_reset_postdata();
}

// ─── Fallback Mock Data ─────────────────────────────────────────────────────

if (!$has_real_data) {
    $nodes = [];
    $links = [];

    // Central node
    $nodes[] = ['id' => 'tg_center', 'label' => $tg_name, 'type' => 'temagruppe', 'url' => '#', 'desc' => 'Teknologi, AI og digitale verktoey'];

    // Mock foretak
    $mock_foretak = ['Multiconsult', 'Norconsult', 'Ramboll', 'COWI', 'Sweco', 'Asplan Viak', 'Dark Arkitekter', 'HENT', 'AF Gruppen', 'Statsbygg', 'Bane NOR', 'Nye Veier'];
    foreach ($mock_foretak as $i => $name) {
        $nid = 'foretak_mock_' . $i;
        $nodes[] = ['id' => $nid, 'label' => $name, 'type' => 'foretak', 'url' => '#', 'desc' => 'Deltaker i BIM Verdi'];
        $links[] = ['source' => 'tg_center', 'target' => $nid];
    }
    $stats['foretak'] = count($mock_foretak);

    // Mock verktoy
    $mock_verktoy = ['Solibri', 'Revit', 'ArchiCAD', 'Navisworks', 'Tekla Structures', 'SimpleBIM', 'BIMcollab', 'Dalux'];
    foreach ($mock_verktoy as $i => $name) {
        $nid = 'verktoy_mock_' . $i;
        $nodes[] = ['id' => $nid, 'label' => $name, 'type' => 'verktoy', 'url' => '#', 'desc' => 'BIM-verktoey'];
        $links[] = ['source' => 'tg_center', 'target' => $nid];
    }
    $stats['verktoy'] = count($mock_verktoy);

    // Mock kunnskapskilder
    $mock_kk = ['ISO 19650', 'NS 8360', 'bSN Veileder', 'IFC4 Dokumentasjon', 'BIM Manual 2.0', 'openBIM Standarder'];
    foreach ($mock_kk as $i => $name) {
        $nid = 'kk_mock_' . $i;
        $nodes[] = ['id' => $nid, 'label' => $name, 'type' => 'kunnskapskilde', 'url' => '#', 'desc' => 'Standard / veileder'];
        $links[] = ['source' => 'tg_center', 'target' => $nid];
    }
    $stats['kunnskapskilde'] = count($mock_kk);

    // Mock arrangementer
    $mock_arr = ['BIMtech Q1 2025', 'AI i BIM Workshop', 'Digitaliseringskonferansen', 'openBIM Hackathon'];
    foreach ($mock_arr as $i => $name) {
        $nid = 'arr_mock_' . $i;
        $nodes[] = ['id' => $nid, 'label' => $name, 'type' => 'arrangement', 'url' => '#', 'desc' => 'Arrangement'];
        $links[] = ['source' => 'tg_center', 'target' => $nid];
    }
    $stats['arrangement'] = count($mock_arr);

    // Add some cross-connections for visual interest (foretak <-> verktoy)
    $cross_links = [
        ['foretak_mock_0', 'verktoy_mock_0'], // Multiconsult -> Solibri
        ['foretak_mock_1', 'verktoy_mock_1'], // Norconsult -> Revit
        ['foretak_mock_2', 'verktoy_mock_2'], // Ramboll -> ArchiCAD
        ['foretak_mock_3', 'verktoy_mock_3'], // COWI -> Navisworks
        ['foretak_mock_4', 'verktoy_mock_4'], // Sweco -> Tekla
        ['foretak_mock_0', 'verktoy_mock_1'], // Multiconsult -> Revit
        ['foretak_mock_1', 'verktoy_mock_0'], // Norconsult -> Solibri
        ['foretak_mock_5', 'verktoy_mock_5'], // Asplan Viak -> SimpleBIM
        ['foretak_mock_6', 'verktoy_mock_2'], // Dark -> ArchiCAD
        ['foretak_mock_7', 'verktoy_mock_4'], // HENT -> Tekla
        ['foretak_mock_9', 'kk_mock_0'],      // Statsbygg -> ISO 19650
        ['foretak_mock_10', 'kk_mock_1'],     // Bane NOR -> NS 8360
        ['foretak_mock_11', 'kk_mock_2'],     // Nye Veier -> bSN Veileder
        ['foretak_mock_0', 'arr_mock_0'],     // Multiconsult -> BIMtech Q1
        ['foretak_mock_3', 'arr_mock_1'],     // COWI -> AI Workshop
        ['verktoy_mock_6', 'arr_mock_3'],     // BIMcollab -> Hackathon
    ];
    foreach ($cross_links as $cl) {
        $links[] = ['source' => $cl[0], 'target' => $cl[1]];
    }
}

// Encode for JS
$graph_json = wp_json_encode(['nodes' => $nodes, 'links' => $links], JSON_UNESCAPED_UNICODE);
$total_entities = $stats['foretak'] + $stats['verktoy'] + $stats['kunnskapskilde'] + $stats['arrangement'];
?>

<style>
    /* ─── Layout ─────────────────────────────────── */
    .nv-container {
        position: relative;
        width: 100%;
        height: calc(100vh - 80px);
        min-height: 600px;
        background: #FAFAF9;
        overflow: hidden;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .nv-svg {
        width: 100%;
        height: 100%;
        display: block;
    }

    /* ─── Back link ──────────────────────────────── */
    .nv-back {
        position: absolute;
        top: 24px;
        left: 24px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 500;
        color: #FF8B5E;
        text-decoration: none;
        z-index: 10;
        transition: color 0.15s;
    }
    .nv-back:hover {
        color: #E67A4E;
        text-decoration: none;
    }
    .nv-back svg {
        width: 16px;
        height: 16px;
    }

    /* ─── Title overlay ──────────────────────────── */
    .nv-title-block {
        position: absolute;
        top: 56px;
        left: 24px;
        z-index: 10;
        pointer-events: none;
    }
    .nv-title-block h1 {
        margin: 0 0 4px 0;
        font-size: 28px;
        font-weight: 700;
        color: #1A1A1A;
        letter-spacing: -0.5px;
        line-height: 1.1;
    }
    .nv-title-block p {
        margin: 0;
        font-size: 14px;
        color: #5A5A5A;
        font-weight: 400;
    }

    /* ─── Stats bar ──────────────────────────────── */
    .nv-stats {
        position: absolute;
        top: 24px;
        right: 24px;
        display: flex;
        gap: 6px;
        z-index: 10;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .nv-stat {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        background: rgba(255,255,255,0.92);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid #E7E5E4;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 500;
        color: #1A1A1A;
        white-space: nowrap;
    }
    .nv-stat__dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .nv-stat__count {
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }

    /* ─── Legend ──────────────────────────────────── */
    .nv-legend {
        position: absolute;
        bottom: 24px;
        left: 24px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        z-index: 10;
        background: rgba(255,255,255,0.92);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid #E7E5E4;
        border-radius: 12px;
        padding: 16px 20px;
    }
    .nv-legend__title {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #A8A29E;
        margin-bottom: 2px;
    }
    .nv-legend__item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        color: #5A5A5A;
        cursor: pointer;
        transition: opacity 0.15s;
    }
    .nv-legend__item:hover {
        color: #1A1A1A;
    }
    .nv-legend__item.dimmed {
        opacity: 0.35;
    }
    .nv-legend__dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        flex-shrink: 0;
        border: 2px solid rgba(255,255,255,0.8);
        box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    }

    /* ─── Detail panel ───────────────────────────── */
    .nv-detail {
        position: absolute;
        top: 80px;
        right: 24px;
        width: 320px;
        background: rgba(255,255,255,0.96);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid #E7E5E4;
        border-radius: 16px;
        padding: 0;
        z-index: 20;
        box-shadow: 0 8px 32px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04);
        transform: translateX(calc(100% + 40px));
        opacity: 0;
        transition: transform 0.35s cubic-bezier(0.16,1,0.3,1), opacity 0.25s ease;
        overflow: hidden;
    }
    .nv-detail.visible {
        transform: translateX(0);
        opacity: 1;
    }
    .nv-detail__header {
        padding: 20px 20px 16px;
        border-bottom: 1px solid #E7E5E4;
        position: relative;
    }
    .nv-detail__type-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #fff;
        margin-bottom: 10px;
    }
    .nv-detail__title {
        font-size: 18px;
        font-weight: 700;
        color: #1A1A1A;
        line-height: 1.25;
        margin: 0;
    }
    .nv-detail__close {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: none;
        background: #F5F5F4;
        color: #5A5A5A;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s;
        padding: 0;
    }
    .nv-detail__close:hover {
        background: #E7E5E4;
        color: #1A1A1A;
    }
    .nv-detail__body {
        padding: 16px 20px 20px;
    }
    .nv-detail__desc {
        font-size: 14px;
        color: #5A5A5A;
        line-height: 1.6;
        margin: 0 0 16px;
    }
    .nv-detail__connections-title {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #A8A29E;
        margin: 0 0 8px;
    }
    .nv-detail__connection-list {
        list-style: none;
        padding: 0;
        margin: 0 0 16px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .nv-detail__connection-item {
        font-size: 13px;
        color: #5A5A5A;
        padding: 6px 10px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background 0.1s;
    }
    .nv-detail__connection-item:hover {
        background: #F5F5F4;
    }
    .nv-detail__connection-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .nv-detail__link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: #1A1A1A;
        color: #fff;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        transition: background 0.15s;
    }
    .nv-detail__link:hover {
        background: #333;
        color: #fff;
        text-decoration: none;
    }

    /* ─── Zoom controls ──────────────────────────── */
    .nv-zoom-controls {
        position: absolute;
        bottom: 24px;
        right: 24px;
        display: flex;
        flex-direction: column;
        gap: 2px;
        z-index: 10;
        background: rgba(255,255,255,0.92);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid #E7E5E4;
        border-radius: 10px;
        overflow: hidden;
    }
    .nv-zoom-btn {
        width: 36px;
        height: 36px;
        border: none;
        background: transparent;
        color: #5A5A5A;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 300;
        padding: 0;
        transition: background 0.1s, color 0.1s;
    }
    .nv-zoom-btn:hover {
        background: #F5F5F4;
        color: #1A1A1A;
    }
    .nv-zoom-btn + .nv-zoom-btn {
        border-top: 1px solid #E7E5E4;
    }

    /* ─── Data source indicator ──────────────────── */
    .nv-data-badge {
        position: absolute;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        background: rgba(255,255,255,0.92);
        backdrop-filter: blur(12px);
        border: 1px solid #E7E5E4;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 500;
        color: #A8A29E;
        z-index: 10;
    }
    .nv-data-badge__dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }
    .nv-data-badge__dot--live { background: #10B981; }
    .nv-data-badge__dot--mock { background: #F59E0B; }

    /* ─── SVG Styles ─────────────────────────────── */
    .nv-link {
        stroke: #D6D1C6;
        stroke-opacity: 0.5;
        fill: none;
    }
    .nv-link--cross {
        stroke-dasharray: 4 3;
        stroke-opacity: 0.3;
    }
    .nv-node-circle {
        stroke: #fff;
        stroke-width: 2.5;
        cursor: pointer;
        transition: filter 0.2s;
    }
    .nv-node-label {
        font-family: 'Inter', system-ui, sans-serif;
        fill: #1A1A1A;
        pointer-events: none;
        font-weight: 500;
    }
    .nv-node-sublabel {
        font-family: 'Inter', system-ui, sans-serif;
        fill: #A8A29E;
        pointer-events: none;
        font-weight: 400;
    }

    /* ─── Center node pulse animation ────────────── */
    @keyframes nv-pulse {
        0%   { r: 38; opacity: 0.35; }
        100% { r: 58; opacity: 0; }
    }
    .nv-pulse-ring {
        fill: none;
        stroke: #FF8B5E;
        stroke-width: 2;
        opacity: 0;
        animation: nv-pulse 2.5s ease-out infinite;
    }
    .nv-pulse-ring:nth-child(2) {
        animation-delay: 0.8s;
    }

    /* ─── Responsive ─────────────────────────────── */
    @media (max-width: 768px) {
        .nv-title-block h1 { font-size: 22px; }
        .nv-stats { top: auto; bottom: 80px; right: 12px; left: 12px; justify-content: center; }
        .nv-stat { font-size: 11px; padding: 4px 10px; }
        .nv-legend { bottom: 12px; left: 12px; padding: 12px 14px; }
        .nv-detail { width: calc(100% - 24px); right: 12px; top: 60px; }
        .nv-back { top: 16px; left: 16px; }
        .nv-title-block { top: 44px; left: 16px; }
        .nv-zoom-controls { bottom: 80px; right: 12px; }
    }
</style>

<main class="nv-container" id="nv-container">

    <!-- Back link -->
    <a href="<?php echo esc_url(get_post_type_archive_link('demo')); ?>" class="nv-back">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Alle demoer
    </a>

    <!-- Title overlay -->
    <div class="nv-title-block">
        <h1>Nettverksgraf</h1>
        <p><?php echo esc_html($tg_name); ?> &mdash; Koblinger i okosystemet</p>
    </div>

    <!-- Stats -->
    <div class="nv-stats">
        <div class="nv-stat">
            <span class="nv-stat__dot" style="background:#3B82F6"></span>
            <span class="nv-stat__count"><?php echo $stats['foretak']; ?></span> Foretak
        </div>
        <div class="nv-stat">
            <span class="nv-stat__dot" style="background:#8B5CF6"></span>
            <span class="nv-stat__count"><?php echo $stats['verktoy']; ?></span> Verktoey
        </div>
        <div class="nv-stat">
            <span class="nv-stat__dot" style="background:#10B981"></span>
            <span class="nv-stat__count"><?php echo $stats['kunnskapskilde']; ?></span> Kunnskapskilder
        </div>
        <div class="nv-stat">
            <span class="nv-stat__dot" style="background:#F59E0B"></span>
            <span class="nv-stat__count"><?php echo $stats['arrangement']; ?></span> Arrangementer
        </div>
    </div>

    <!-- Legend -->
    <div class="nv-legend" id="nv-legend">
        <div class="nv-legend__title">Entitetstyper</div>
        <div class="nv-legend__item" data-type="temagruppe">
            <span class="nv-legend__dot" style="background:#FF8B5E"></span> Temagruppe
        </div>
        <div class="nv-legend__item" data-type="foretak">
            <span class="nv-legend__dot" style="background:#3B82F6"></span> Foretak
        </div>
        <div class="nv-legend__item" data-type="verktoy">
            <span class="nv-legend__dot" style="background:#8B5CF6"></span> Verktoey
        </div>
        <div class="nv-legend__item" data-type="kunnskapskilde">
            <span class="nv-legend__dot" style="background:#10B981"></span> Kunnskapskilder
        </div>
        <div class="nv-legend__item" data-type="arrangement">
            <span class="nv-legend__dot" style="background:#F59E0B"></span> Arrangementer
        </div>
    </div>

    <!-- Detail panel -->
    <div class="nv-detail" id="nv-detail">
        <div class="nv-detail__header">
            <div class="nv-detail__type-badge" id="nv-detail-badge">Type</div>
            <h3 class="nv-detail__title" id="nv-detail-title">Title</h3>
            <button class="nv-detail__close" id="nv-detail-close" aria-label="Lukk">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="nv-detail__body">
            <p class="nv-detail__desc" id="nv-detail-desc"></p>
            <div class="nv-detail__connections-title">Koblinger</div>
            <ul class="nv-detail__connection-list" id="nv-detail-connections"></ul>
            <a class="nv-detail__link" id="nv-detail-link" href="#">
                Ga til side
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            </a>
        </div>
    </div>

    <!-- Zoom controls -->
    <div class="nv-zoom-controls">
        <button class="nv-zoom-btn" id="nv-zoom-in" aria-label="Zoom inn">+</button>
        <button class="nv-zoom-btn" id="nv-zoom-out" aria-label="Zoom ut">&minus;</button>
        <button class="nv-zoom-btn" id="nv-zoom-reset" aria-label="Tilbakestill zoom" style="font-size:14px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg>
        </button>
    </div>

    <!-- Data source badge -->
    <div class="nv-data-badge">
        <span class="nv-data-badge__dot <?php echo $has_real_data ? 'nv-data-badge__dot--live' : 'nv-data-badge__dot--mock'; ?>"></span>
        <?php echo $has_real_data ? 'Live data fra WordPress' : 'Demonstrasjonsdata'; ?>
    </div>

    <!-- SVG will be injected here by D3 -->
</main>

<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
(function() {
    'use strict';

    // ─── Configuration ──────────────────────────────────────────────────
    const COLORS = {
        temagruppe:     '#FF8B5E',
        foretak:        '#3B82F6',
        verktoy:        '#8B5CF6',
        kunnskapskilde: '#10B981',
        arrangement:    '#F59E0B',
    };

    const TYPE_LABELS = {
        temagruppe:     'Temagruppe',
        foretak:        'Foretak',
        verktoy:        'Verktoey',
        kunnskapskilde: 'Kunnskapskilde',
        arrangement:    'Arrangement',
    };

    const TYPE_ICONS = {
        temagruppe:     '\u{1F3AF}',
        foretak:        '\u{1F3E2}',
        verktoy:        '\u{1F527}',
        kunnskapskilde: '\u{1F4DA}',
        arrangement:    '\u{1F4C5}',
    };

    // ─── Graph Data ─────────────────────────────────────────────────────
    const graphData = <?php echo $graph_json; ?>;

    // Compute connection counts
    const connectionCount = {};
    graphData.links.forEach(l => {
        connectionCount[l.source] = (connectionCount[l.source] || 0) + 1;
        connectionCount[l.target] = (connectionCount[l.target] || 0) + 1;
    });

    // Node radius based on type and connections
    function nodeRadius(d) {
        if (d.type === 'temagruppe') return 36;
        const count = connectionCount[d.id] || 1;
        return Math.max(10, Math.min(24, 8 + count * 3));
    }

    // ─── SVG Setup ──────────────────────────────────────────────────────
    const container = document.getElementById('nv-container');
    const width = container.clientWidth;
    const height = container.clientHeight;

    const svg = d3.select('#nv-container')
        .append('svg')
        .attr('class', 'nv-svg')
        .attr('viewBox', [0, 0, width, height]);

    // Defs for gradients and filters
    const defs = svg.append('defs');

    // Glow filter for center node
    const glowFilter = defs.append('filter')
        .attr('id', 'nv-glow')
        .attr('x', '-50%').attr('y', '-50%')
        .attr('width', '200%').attr('height', '200%');
    glowFilter.append('feGaussianBlur')
        .attr('stdDeviation', '4')
        .attr('result', 'glow');
    glowFilter.append('feMerge')
        .selectAll('feMergeNode')
        .data(['glow', 'SourceGraphic'])
        .enter().append('feMergeNode')
        .attr('in', d => d);

    // Shadow filter for nodes
    const shadowFilter = defs.append('filter')
        .attr('id', 'nv-shadow')
        .attr('x', '-30%').attr('y', '-30%')
        .attr('width', '160%').attr('height', '160%');
    shadowFilter.append('feDropShadow')
        .attr('dx', 0).attr('dy', 2)
        .attr('stdDeviation', 3)
        .attr('flood-color', 'rgba(0,0,0,0.12)');

    // Zoom group
    const g = svg.append('g').attr('class', 'nv-graph');

    // Zoom behavior
    const zoom = d3.zoom()
        .scaleExtent([0.2, 4])
        .on('zoom', (event) => {
            g.attr('transform', event.transform);
        });
    svg.call(zoom);

    // ─── Force Simulation ───────────────────────────────────────────────
    const simulation = d3.forceSimulation(graphData.nodes)
        .force('link', d3.forceLink(graphData.links)
            .id(d => d.id)
            .distance(d => {
                const src = typeof d.source === 'object' ? d.source : graphData.nodes.find(n => n.id === d.source);
                const tgt = typeof d.target === 'object' ? d.target : graphData.nodes.find(n => n.id === d.target);
                if (src && tgt && src.type === 'temagruppe' || tgt && tgt.type === 'temagruppe') return 180;
                return 120;
            })
            .strength(d => {
                const src = typeof d.source === 'object' ? d.source : graphData.nodes.find(n => n.id === d.source);
                const tgt = typeof d.target === 'object' ? d.target : graphData.nodes.find(n => n.id === d.target);
                if (src && src.type === 'temagruppe' || tgt && tgt.type === 'temagruppe') return 0.7;
                return 0.3;
            })
        )
        .force('charge', d3.forceManyBody()
            .strength(d => d.type === 'temagruppe' ? -600 : -200)
        )
        .force('center', d3.forceCenter(width / 2, height / 2))
        .force('collide', d3.forceCollide()
            .radius(d => nodeRadius(d) + 12)
            .strength(0.8)
        )
        .force('x', d3.forceX(width / 2).strength(0.03))
        .force('y', d3.forceY(height / 2).strength(0.03));

    // ─── Links ──────────────────────────────────────────────────────────
    const link = g.append('g')
        .attr('class', 'nv-links')
        .selectAll('line')
        .data(graphData.links)
        .enter().append('line')
        .attr('class', d => {
            // Cross-connections (non-center links) get dashed style
            const src = typeof d.source === 'string' ? d.source : d.source.id;
            const tgt = typeof d.target === 'string' ? d.target : d.target.id;
            const isCross = !src.startsWith('tg_') && !tgt.startsWith('tg_');
            return 'nv-link' + (isCross ? ' nv-link--cross' : '');
        })
        .attr('stroke-width', d => {
            const src = typeof d.source === 'string' ? d.source : d.source.id;
            const tgt = typeof d.target === 'string' ? d.target : d.target.id;
            const isCross = !src.startsWith('tg_') && !tgt.startsWith('tg_');
            return isCross ? 1 : 1.5;
        });

    // ─── Nodes ──────────────────────────────────────────────────────────
    const node = g.append('g')
        .attr('class', 'nv-nodes')
        .selectAll('g')
        .data(graphData.nodes)
        .enter().append('g')
        .attr('class', 'nv-node')
        .style('cursor', 'pointer')
        .call(d3.drag()
            .on('start', dragStarted)
            .on('drag', dragged)
            .on('end', dragEnded)
        );

    // Pulse rings for center node
    node.filter(d => d.type === 'temagruppe').each(function(d) {
        const g = d3.select(this);
        g.append('circle').attr('class', 'nv-pulse-ring').attr('r', 38);
        g.append('circle').attr('class', 'nv-pulse-ring').attr('r', 38);
    });

    // Node circles
    node.append('circle')
        .attr('class', 'nv-node-circle')
        .attr('r', d => nodeRadius(d))
        .attr('fill', d => COLORS[d.type])
        .attr('filter', d => d.type === 'temagruppe' ? 'url(#nv-glow)' : 'url(#nv-shadow)');

    // Icon text inside node
    node.append('text')
        .attr('text-anchor', 'middle')
        .attr('dominant-baseline', 'central')
        .attr('font-size', d => d.type === 'temagruppe' ? 20 : 14)
        .attr('pointer-events', 'none')
        .text(d => TYPE_ICONS[d.type] || '');

    // Label below node
    node.append('text')
        .attr('class', 'nv-node-label')
        .attr('text-anchor', 'middle')
        .attr('dy', d => nodeRadius(d) + 16)
        .attr('font-size', d => d.type === 'temagruppe' ? 14 : 11)
        .text(d => {
            const maxLen = d.type === 'temagruppe' ? 30 : 18;
            return d.label.length > maxLen ? d.label.substring(0, maxLen) + '...' : d.label;
        });

    // ─── Simulation tick ────────────────────────────────────────────────
    simulation.on('tick', () => {
        link
            .attr('x1', d => d.source.x)
            .attr('y1', d => d.source.y)
            .attr('x2', d => d.target.x)
            .attr('y2', d => d.target.y);

        node.attr('transform', d => `translate(${d.x},${d.y})`);
    });

    // ─── Drag Behavior ──────────────────────────────────────────────────
    function dragStarted(event, d) {
        if (!event.active) simulation.alphaTarget(0.3).restart();
        d.fx = d.x;
        d.fy = d.y;
    }

    function dragged(event, d) {
        d.fx = event.x;
        d.fy = event.y;
    }

    function dragEnded(event, d) {
        if (!event.active) simulation.alphaTarget(0);
        // Keep center node pinned, release others
        if (d.type !== 'temagruppe') {
            d.fx = null;
            d.fy = null;
        }
    }

    // ─── Hover Interactions ─────────────────────────────────────────────
    const linkedByIndex = {};
    graphData.links.forEach(d => {
        const src = typeof d.source === 'object' ? d.source.id : d.source;
        const tgt = typeof d.target === 'object' ? d.target.id : d.target;
        linkedByIndex[src + ',' + tgt] = true;
        linkedByIndex[tgt + ',' + src] = true;
    });

    function isConnected(a, b) {
        return linkedByIndex[a.id + ',' + b.id] || a.id === b.id;
    }

    node.on('mouseenter', function(event, d) {
        // Dim non-connected nodes
        node.style('opacity', o => isConnected(d, o) ? 1 : 0.15);
        link.style('stroke-opacity', l => {
            const src = typeof l.source === 'object' ? l.source.id : l.source;
            const tgt = typeof l.target === 'object' ? l.target.id : l.target;
            return (src === d.id || tgt === d.id) ? 0.8 : 0.05;
        }).style('stroke-width', l => {
            const src = typeof l.source === 'object' ? l.source.id : l.source;
            const tgt = typeof l.target === 'object' ? l.target.id : l.target;
            return (src === d.id || tgt === d.id) ? 2.5 : 1;
        });

        // Enlarge hovered node
        d3.select(this).select('.nv-node-circle')
            .transition().duration(200)
            .attr('r', nodeRadius(d) * 1.2);
    });

    node.on('mouseleave', function(event, d) {
        node.style('opacity', 1);
        link.style('stroke-opacity', l => {
            const src = typeof l.source === 'object' ? l.source.id : l.source;
            const tgt = typeof l.target === 'object' ? l.target.id : l.target;
            const isCross = !src.startsWith('tg_') && !tgt.startsWith('tg_');
            return isCross ? 0.3 : 0.5;
        }).style('stroke-width', l => {
            const src = typeof l.source === 'object' ? l.source.id : l.source;
            const tgt = typeof l.target === 'object' ? l.target.id : l.target;
            const isCross = !src.startsWith('tg_') && !tgt.startsWith('tg_');
            return isCross ? 1 : 1.5;
        });

        d3.select(this).select('.nv-node-circle')
            .transition().duration(200)
            .attr('r', nodeRadius(d));
    });

    // ─── Click → Detail Panel ───────────────────────────────────────────
    const detailPanel = document.getElementById('nv-detail');
    const detailBadge = document.getElementById('nv-detail-badge');
    const detailTitle = document.getElementById('nv-detail-title');
    const detailDesc = document.getElementById('nv-detail-desc');
    const detailConnections = document.getElementById('nv-detail-connections');
    const detailLink = document.getElementById('nv-detail-link');
    let selectedNodeId = null;

    node.on('click', function(event, d) {
        event.stopPropagation();
        selectedNodeId = d.id;

        // Badge
        detailBadge.style.background = COLORS[d.type];
        detailBadge.textContent = TYPE_LABELS[d.type];

        // Title
        detailTitle.textContent = d.label;

        // Description
        detailDesc.textContent = d.desc || 'Ingen beskrivelse tilgjengelig.';

        // Connections list
        detailConnections.innerHTML = '';
        const neighbors = graphData.links
            .filter(l => {
                const src = typeof l.source === 'object' ? l.source.id : l.source;
                const tgt = typeof l.target === 'object' ? l.target.id : l.target;
                return src === d.id || tgt === d.id;
            })
            .map(l => {
                const src = typeof l.source === 'object' ? l.source.id : l.source;
                const tgt = typeof l.target === 'object' ? l.target.id : l.target;
                const neighborId = src === d.id ? tgt : src;
                return graphData.nodes.find(n => n.id === neighborId);
            })
            .filter(Boolean)
            .slice(0, 10); // Max 10 connections shown

        neighbors.forEach(n => {
            const li = document.createElement('li');
            li.className = 'nv-detail__connection-item';
            li.innerHTML = `<span class="nv-detail__connection-dot" style="background:${COLORS[n.type]}"></span>${n.label}`;
            li.style.cursor = 'pointer';
            li.addEventListener('click', () => {
                // Simulate click on that node
                const nodeEl = node.filter(nd => nd.id === n.id);
                if (!nodeEl.empty()) {
                    nodeEl.dispatch('click');
                }
            });
            detailConnections.appendChild(li);
        });

        if (neighbors.length === 0) {
            detailConnections.innerHTML = '<li class="nv-detail__connection-item" style="color:#A8A29E">Ingen koblinger</li>';
        }

        // Link
        if (d.url && d.url !== '#') {
            detailLink.href = d.url;
            detailLink.style.display = 'inline-flex';
        } else {
            detailLink.style.display = 'none';
        }

        // Show panel
        detailPanel.classList.add('visible');

        // Highlight selected node
        node.select('.nv-node-circle')
            .transition().duration(200)
            .attr('stroke-width', nd => nd.id === d.id ? 4 : 2.5)
            .attr('stroke', nd => nd.id === d.id ? COLORS[nd.type] : '#fff');
    });

    // Close detail panel
    document.getElementById('nv-detail-close').addEventListener('click', closeDetail);
    svg.on('click', closeDetail);

    function closeDetail() {
        selectedNodeId = null;
        detailPanel.classList.remove('visible');
        node.select('.nv-node-circle')
            .transition().duration(200)
            .attr('stroke-width', 2.5)
            .attr('stroke', '#fff');
    }

    // ─── Legend Filtering ───────────────────────────────────────────────
    const activeTypes = new Set(Object.keys(COLORS));

    document.querySelectorAll('#nv-legend .nv-legend__item').forEach(item => {
        item.addEventListener('click', () => {
            const type = item.dataset.type;
            if (activeTypes.has(type)) {
                activeTypes.delete(type);
                item.classList.add('dimmed');
            } else {
                activeTypes.add(type);
                item.classList.remove('dimmed');
            }

            // Update visibility
            node.style('opacity', d => activeTypes.has(d.type) ? 1 : 0.08);
            node.style('pointer-events', d => activeTypes.has(d.type) ? 'all' : 'none');
            link.style('stroke-opacity', l => {
                const src = typeof l.source === 'object' ? l.source : { type: '' };
                const tgt = typeof l.target === 'object' ? l.target : { type: '' };
                return (activeTypes.has(src.type) && activeTypes.has(tgt.type)) ? 0.5 : 0.03;
            });
        });
    });

    // ─── Zoom Controls ──────────────────────────────────────────────────
    document.getElementById('nv-zoom-in').addEventListener('click', () => {
        svg.transition().duration(300).call(zoom.scaleBy, 1.4);
    });
    document.getElementById('nv-zoom-out').addEventListener('click', () => {
        svg.transition().duration(300).call(zoom.scaleBy, 0.7);
    });
    document.getElementById('nv-zoom-reset').addEventListener('click', () => {
        svg.transition().duration(500).call(zoom.transform, d3.zoomIdentity);
    });

    // ─── Keyboard shortcuts ─────────────────────────────────────────────
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDetail();
        if (e.key === '+' || e.key === '=') svg.transition().duration(200).call(zoom.scaleBy, 1.2);
        if (e.key === '-') svg.transition().duration(200).call(zoom.scaleBy, 0.8);
        if (e.key === '0') svg.transition().duration(400).call(zoom.transform, d3.zoomIdentity);
    });

    // ─── Resize handling ────────────────────────────────────────────────
    window.addEventListener('resize', () => {
        const w = container.clientWidth;
        const h = container.clientHeight;
        svg.attr('viewBox', [0, 0, w, h]);
        simulation.force('center', d3.forceCenter(w / 2, h / 2));
        simulation.force('x', d3.forceX(w / 2).strength(0.03));
        simulation.force('y', d3.forceY(h / 2).strength(0.03));
        simulation.alpha(0.3).restart();
    });

    // ─── Initial entrance animation ─────────────────────────────────────
    // Start with nodes at center, then let simulation spread them
    graphData.nodes.forEach(d => {
        if (d.type !== 'temagruppe') {
            d.x = width / 2 + (Math.random() - 0.5) * 50;
            d.y = height / 2 + (Math.random() - 0.5) * 50;
        } else {
            d.x = width / 2;
            d.y = height / 2;
            d.fx = width / 2;
            d.fy = height / 2;
        }
    });

    // Unpin center node after simulation stabilizes
    setTimeout(() => {
        const centerNode = graphData.nodes.find(n => n.type === 'temagruppe');
        if (centerNode) {
            centerNode.fx = null;
            centerNode.fy = null;
        }
    }, 3000);

})();
</script>
