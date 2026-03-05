<?php
/**
 * Demo: Ecosystem Flow (Okosystem-flyt)
 *
 * Sankey/flow diagram showing how a Temagruppe connects to its ecosystem:
 * Temagruppe -> Foretak -> Verktoy, and Temagruppe -> Kunnskapskilder / Arrangementer
 *
 * Loaded via get_template_part() from single-demo.php
 * Header is already loaded by the parent template.
 */

if (!defined('ABSPATH')) exit;

// ─── Data Loading ───────────────────────────────────────────────────────────

$ecosystem_data = [];
$using_mock = false;

// Try to load real data from WordPress
$theme_groups = get_posts([
    'post_type'      => 'theme_group',
    'posts_per_page' => 1,
    'post_status'    => 'publish',
    'orderby'        => 'title',
    'order'          => 'ASC',
]);

if (!empty($theme_groups)) {
    $tg = $theme_groups[0];
    $tg_title = $tg->post_title;

    // Find the matching taxonomy term
    $term = get_term_by('name', $tg_title, 'temagruppe');
    if (!$term) {
        // Try slug-based lookup
        $term = get_term_by('slug', sanitize_title($tg_title), 'temagruppe');
    }

    if ($term) {
        // Foretak connected to this temagruppe
        $foretak_posts = get_posts([
            'post_type'      => 'foretak',
            'posts_per_page' => 20,
            'post_status'    => 'publish',
            'tax_query'      => [[
                'taxonomy' => 'temagruppe',
                'field'    => 'term_id',
                'terms'    => $term->term_id,
            ]],
        ]);

        // Verktoy connected to this temagruppe
        $verktoy_posts = get_posts([
            'post_type'      => 'verktoy',
            'posts_per_page' => 30,
            'post_status'    => 'publish',
            'tax_query'      => [[
                'taxonomy' => 'temagruppe',
                'field'    => 'term_id',
                'terms'    => $term->term_id,
            ]],
        ]);

        // Kunnskapskilder connected to this temagruppe
        $kunnskap_posts = get_posts([
            'post_type'      => 'kunnskapskilde',
            'posts_per_page' => 15,
            'post_status'    => 'publish',
            'tax_query'      => [[
                'taxonomy' => 'temagruppe',
                'field'    => 'term_id',
                'terms'    => $term->term_id,
            ]],
        ]);

        // Arrangementer connected to this temagruppe
        $arrangement_posts = get_posts([
            'post_type'      => 'arrangement',
            'posts_per_page' => 15,
            'post_status'    => 'publish',
            'tax_query'      => [[
                'taxonomy' => 'temagruppe',
                'field'    => 'term_id',
                'terms'    => $term->term_id,
            ]],
        ]);

        // Build foretak array
        $foretak_data = [];
        foreach ($foretak_posts as $f) {
            $foretak_data[] = [
                'id'    => 'foretak-' . $f->ID,
                'label' => $f->post_title,
                'url'   => get_permalink($f->ID),
                'wp_id' => $f->ID,
            ];
        }

        // Build verktoy array with company links
        $verktoy_data = [];
        foreach ($verktoy_posts as $v) {
            $foretak_id = get_field('tilknyttet_foretak', $v->ID);
            $linked_foretak = null;
            if ($foretak_id) {
                // Check if this foretak is in our list
                foreach ($foretak_data as $fd) {
                    if ($fd['wp_id'] == $foretak_id) {
                        $linked_foretak = $fd['id'];
                        break;
                    }
                }
            }
            $verktoy_data[] = [
                'id'             => 'verktoy-' . $v->ID,
                'label'          => $v->post_title,
                'url'            => get_permalink($v->ID),
                'linked_foretak' => $linked_foretak,
            ];
        }

        // Build kunnskapskilder array
        $kunnskap_data = [];
        foreach ($kunnskap_posts as $k) {
            $kunnskap_data[] = [
                'id'    => 'kunnskap-' . $k->ID,
                'label' => $k->post_title,
                'url'   => get_permalink($k->ID),
            ];
        }

        // Build arrangementer array
        $arrangement_data = [];
        foreach ($arrangement_posts as $a) {
            $arrangement_data[] = [
                'id'    => 'arrangement-' . $a->ID,
                'label' => $a->post_title,
                'url'   => get_permalink($a->ID),
            ];
        }

        // Only use real data if we have at least some content
        $total = count($foretak_data) + count($verktoy_data) + count($kunnskap_data) + count($arrangement_data);
        if ($total > 0) {
            $ecosystem_data = [
                'temagruppe'      => [
                    'id'    => 'tg-' . $tg->ID,
                    'label' => $tg_title,
                    'url'   => get_permalink($tg->ID),
                ],
                'foretak'         => $foretak_data,
                'verktoy'         => $verktoy_data,
                'kunnskapskilder' => $kunnskap_data,
                'arrangementer'   => $arrangement_data,
            ];
        }
    }
}

// Fallback mock data
if (empty($ecosystem_data)) {
    $using_mock = true;
    $ecosystem_data = [
        'temagruppe' => [
            'id'    => 'tg-1',
            'label' => 'ByggesaksBIM',
            'url'   => '#',
        ],
        'foretak' => [
            ['id' => 'foretak-1', 'label' => 'Multiconsult', 'url' => '#', 'wp_id' => 1],
            ['id' => 'foretak-2', 'label' => 'Norconsult', 'url' => '#', 'wp_id' => 2],
            ['id' => 'foretak-3', 'label' => 'Ramboll', 'url' => '#', 'wp_id' => 3],
            ['id' => 'foretak-4', 'label' => 'Sweco', 'url' => '#', 'wp_id' => 4],
            ['id' => 'foretak-5', 'label' => 'Asplan Viak', 'url' => '#', 'wp_id' => 5],
        ],
        'verktoy' => [
            ['id' => 'verktoy-1', 'label' => 'Solibri', 'url' => '#', 'linked_foretak' => 'foretak-1'],
            ['id' => 'verktoy-2', 'label' => 'Revit', 'url' => '#', 'linked_foretak' => 'foretak-1'],
            ['id' => 'verktoy-3', 'label' => 'BIMcollab', 'url' => '#', 'linked_foretak' => 'foretak-2'],
            ['id' => 'verktoy-4', 'label' => 'Navisworks', 'url' => '#', 'linked_foretak' => 'foretak-3'],
            ['id' => 'verktoy-5', 'label' => 'Tekla', 'url' => '#', 'linked_foretak' => 'foretak-4'],
            ['id' => 'verktoy-6', 'label' => 'ArchiCAD', 'url' => '#', 'linked_foretak' => 'foretak-5'],
            ['id' => 'verktoy-7', 'label' => 'dRofus', 'url' => '#', 'linked_foretak' => 'foretak-2'],
            ['id' => 'verktoy-8', 'label' => 'Dalux', 'url' => '#', 'linked_foretak' => 'foretak-4'],
        ],
        'kunnskapskilder' => [
            ['id' => 'kunnskap-1', 'label' => 'ISO 19650 Veileder', 'url' => '#'],
            ['id' => 'kunnskap-2', 'label' => 'BIM-manual 2.0', 'url' => '#'],
            ['id' => 'kunnskap-3', 'label' => 'IFC-standarden', 'url' => '#'],
        ],
        'arrangementer' => [
            ['id' => 'arrangement-1', 'label' => 'ByggesaksBIM Workshop', 'url' => '#'],
            ['id' => 'arrangement-2', 'label' => 'BIM i kommunal saksbehandling', 'url' => '#'],
            ['id' => 'arrangement-3', 'label' => 'Digitalt temagruppemote', 'url' => '#'],
        ],
    ];
}

$json_data = wp_json_encode($ecosystem_data);
?>

<main class="bv-ecosystem-flow" style="background:#FAFAF9;min-height:100vh;">

<style>
/* ─── Base Layout ─────────────────────────────────────────────────────── */
.bv-ecosystem-flow {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #1A1A1A;
}
.bv-eco-header {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 1.5rem 0;
}
.bv-eco-back {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: #FF8B5E;
    text-decoration: none;
    transition: opacity 0.2s;
}
.bv-eco-back:hover { opacity: 0.7; }
.bv-eco-back svg { width: 1rem; height: 1rem; }
.bv-eco-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1A1A1A;
    margin: 1rem 0 0.25rem;
    line-height: 1.2;
}
.bv-eco-subtitle {
    font-size: 1rem;
    color: #5A5A5A;
    margin: 0 0 0.5rem;
    max-width: 700px;
}
.bv-eco-mock-badge {
    display: inline-block;
    background: #FFF3CD;
    color: #856404;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    margin-bottom: 1rem;
}

/* ─── Visualization Container ─────────────────────────────────────────── */
.bv-eco-viz {
    position: relative;
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem 1.5rem 2rem;
}
.bv-eco-svg-wrap {
    position: relative;
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
#bv-eco-svg {
    display: block;
    width: 100%;
    min-width: 900px;
}

/* ─── Column Headers ──────────────────────────────────────────────────── */
.bv-eco-col-header {
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    fill: #5A5A5A;
}

/* ─── Nodes ───────────────────────────────────────────────────────────── */
.bv-eco-node rect {
    rx: 8;
    ry: 8;
    cursor: pointer;
    transition: filter 0.3s ease, transform 0.15s ease;
    stroke-width: 0;
}
.bv-eco-node rect:hover {
    filter: brightness(1.08) drop-shadow(0 4px 12px rgba(0,0,0,0.12));
}
.bv-eco-node text {
    font-size: 0.8125rem;
    font-weight: 500;
    pointer-events: none;
    fill: #fff;
}
.bv-eco-node.type-temagruppe rect { fill: #FF8B5E; }
.bv-eco-node.type-foretak rect { fill: #3B82F6; }
.bv-eco-node.type-verktoy rect { fill: #8B5CF6; }
.bv-eco-node.type-kunnskap rect { fill: #10B981; }
.bv-eco-node.type-arrangement rect { fill: #F59E0B; }

/* Active / selected node */
.bv-eco-node.active rect {
    stroke: #1A1A1A;
    stroke-width: 2;
}

/* ─── Paths / Links ───────────────────────────────────────────────────── */
.bv-eco-link {
    fill: none;
    stroke-linecap: round;
    transition: opacity 0.4s ease, stroke-width 0.3s ease;
}
.bv-eco-link.source-temagruppe { stroke: #FF8B5E; }
.bv-eco-link.source-foretak    { stroke: #3B82F6; }

/* Animated flow particles */
.bv-eco-link-animated {
    fill: none;
    stroke-linecap: round;
    stroke-dasharray: 6 10;
    animation: bv-eco-dash 1.5s linear infinite;
    pointer-events: none;
}
@keyframes bv-eco-dash {
    to { stroke-dashoffset: -32; }
}

/* Dimmed state when hovering/selecting */
.bv-eco-dimmed .bv-eco-link { opacity: 0.06; }
.bv-eco-dimmed .bv-eco-link-animated { opacity: 0; }
.bv-eco-dimmed .bv-eco-node rect { opacity: 0.15; }
.bv-eco-dimmed .bv-eco-node text { opacity: 0.15; }
.bv-eco-dimmed .bv-eco-col-header { opacity: 0.3; }

/* Highlighted elements override dimmed */
.bv-eco-dimmed .bv-eco-link.highlighted { opacity: 0.7; stroke-width: 4px !important; }
.bv-eco-dimmed .bv-eco-link-animated.highlighted { opacity: 0.5; }
.bv-eco-dimmed .bv-eco-node.highlighted rect { opacity: 1; filter: drop-shadow(0 4px 16px rgba(0,0,0,0.15)); }
.bv-eco-dimmed .bv-eco-node.highlighted text { opacity: 1; }

/* ─── Detail Panel ────────────────────────────────────────────────────── */
.bv-eco-detail {
    position: absolute;
    top: 80px;
    right: 1.5rem;
    width: 280px;
    background: #fff;
    border: 1px solid #E7E5E4;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.08);
    opacity: 0;
    transform: translateY(8px) scale(0.97);
    pointer-events: none;
    transition: opacity 0.3s ease, transform 0.3s ease;
    z-index: 10;
}
.bv-eco-detail.visible {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: auto;
}
.bv-eco-detail-close {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    width: 24px;
    height: 24px;
    border: none;
    background: none;
    cursor: pointer;
    color: #5A5A5A;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.bv-eco-detail-close:hover { color: #1A1A1A; }
.bv-eco-detail-badge {
    display: inline-block;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    color: #fff;
    margin-bottom: 0.5rem;
}
.bv-eco-detail-badge.type-temagruppe { background: #FF8B5E; }
.bv-eco-detail-badge.type-foretak { background: #3B82F6; }
.bv-eco-detail-badge.type-verktoy { background: #8B5CF6; }
.bv-eco-detail-badge.type-kunnskap { background: #10B981; }
.bv-eco-detail-badge.type-arrangement { background: #F59E0B; }
.bv-eco-detail-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1A1A1A;
    margin: 0 0 0.5rem;
    line-height: 1.3;
    padding-right: 1.5rem;
}
.bv-eco-detail-connections {
    font-size: 0.8125rem;
    color: #5A5A5A;
    margin: 0.75rem 0;
    padding-top: 0.75rem;
    border-top: 1px solid #E7E5E4;
}
.bv-eco-detail-connections ul {
    list-style: none;
    padding: 0;
    margin: 0.5rem 0 0;
}
.bv-eco-detail-connections li {
    padding: 0.25rem 0;
    font-size: 0.8125rem;
    color: #1A1A1A;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}
.bv-eco-detail-connections li .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}
.bv-eco-detail-link {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #FF8B5E;
    text-decoration: none;
    margin-top: 0.75rem;
    transition: opacity 0.2s;
}
.bv-eco-detail-link:hover { opacity: 0.7; }

/* ─── Stats Bar ───────────────────────────────────────────────────────── */
.bv-eco-stats {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1.5rem 3rem;
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}
.bv-eco-stat {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #5A5A5A;
}
.bv-eco-stat-num {
    font-size: 1.75rem;
    font-weight: 800;
    line-height: 1;
}
.bv-eco-stat-num.c-orange { color: #FF8B5E; }
.bv-eco-stat-num.c-blue   { color: #3B82F6; }
.bv-eco-stat-num.c-purple { color: #8B5CF6; }
.bv-eco-stat-num.c-green  { color: #10B981; }
.bv-eco-stat-num.c-amber  { color: #F59E0B; }

/* ─── Legend ───────────────────────────────────────────────────────────── */
.bv-eco-legend {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1.5rem 2rem;
    display: flex;
    gap: 1.25rem;
    flex-wrap: wrap;
    align-items: center;
}
.bv-eco-legend-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: #5A5A5A;
}
.bv-eco-legend-swatch {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

/* ─── Entry Animation ─────────────────────────────────────────────────── */
.bv-eco-link { stroke-dasharray: 1000; stroke-dashoffset: 1000; }
.bv-eco-link.entered { stroke-dasharray: none; stroke-dashoffset: 0; transition: stroke-dashoffset 1.2s cubic-bezier(0.4,0,0.2,1); }

/* ─── Mobile ──────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .bv-eco-title { font-size: 1.5rem; }
    .bv-eco-subtitle { font-size: 0.875rem; }
    .bv-eco-detail {
        position: fixed;
        top: auto;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        border-radius: 16px 16px 0 0;
        max-height: 50vh;
        overflow-y: auto;
    }
    .bv-eco-stats { gap: 1rem; }
    .bv-eco-stat-num { font-size: 1.375rem; }
}
</style>

<!-- ─── Header ────────────────────────────────────────────────────────── -->
<div class="bv-eco-header">
    <a href="<?php echo esc_url(get_post_type_archive_link('demo')); ?>" class="bv-eco-back">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Alle demoer
    </a>
    <h1 class="bv-eco-title">Okosystem-flyt</h1>
    <p class="bv-eco-subtitle">
        Visualisering av hvordan en temagruppe kobler sammen foretak, verktoy, kunnskapskilder og arrangementer i et sammenhengende okosystem.
    </p>
    <?php if ($using_mock) : ?>
        <span class="bv-eco-mock-badge">Viser demo-data (ingen publiserte temagrupper funnet)</span>
    <?php endif; ?>
</div>

<!-- ─── Legend ─────────────────────────────────────────────────────────── -->
<div class="bv-eco-legend">
    <div class="bv-eco-legend-item"><span class="bv-eco-legend-swatch" style="background:#FF8B5E"></span> Temagruppe</div>
    <div class="bv-eco-legend-item"><span class="bv-eco-legend-swatch" style="background:#3B82F6"></span> Foretak</div>
    <div class="bv-eco-legend-item"><span class="bv-eco-legend-swatch" style="background:#8B5CF6"></span> Verktoy</div>
    <div class="bv-eco-legend-item"><span class="bv-eco-legend-swatch" style="background:#10B981"></span> Kunnskapskilder</div>
    <div class="bv-eco-legend-item"><span class="bv-eco-legend-swatch" style="background:#F59E0B"></span> Arrangementer</div>
</div>

<!-- ─── Visualization ─────────────────────────────────────────────────── -->
<div class="bv-eco-viz" id="bv-eco-viz">
    <div class="bv-eco-svg-wrap">
        <svg id="bv-eco-svg"></svg>
    </div>
    <div class="bv-eco-detail" id="bv-eco-detail">
        <button class="bv-eco-detail-close" id="bv-eco-detail-close" aria-label="Lukk">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div id="bv-eco-detail-content"></div>
    </div>
</div>

<!-- ─── Stats ─────────────────────────────────────────────────────────── -->
<div class="bv-eco-stats" id="bv-eco-stats"></div>

<!-- ─── D3.js v7 ──────────────────────────────────────────────────────── -->
<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
(function() {
    'use strict';

    const DATA = <?php echo $json_data; ?>;

    // ─── Color Map ────────────────────────────────────────────────────
    const COLORS = {
        temagruppe:  '#FF8B5E',
        foretak:     '#3B82F6',
        verktoy:     '#8B5CF6',
        kunnskap:    '#10B981',
        arrangement: '#F59E0B',
    };

    const TYPE_LABELS = {
        temagruppe:  'Temagruppe',
        foretak:     'Foretak',
        verktoy:     'Verktoy',
        kunnskap:    'Kunnskapskilde',
        arrangement: 'Arrangement',
    };

    // ─── Build Nodes & Links ──────────────────────────────────────────
    const nodes = [];
    const links = [];

    // Column assignments: 0=temagruppe, 1=foretak, 2=verktoy, 1.5 (mapped to separate Y band)=kunnskap, arrangement
    // Layout: temagruppe(col0) -> foretak(col1) -> verktoy(col2)
    //         temagruppe(col0) -> kunnskapskilder(col3)
    //         temagruppe(col0) -> arrangementer(col4)
    // We use 5 columns visually.

    // Temagruppe node
    const tgNode = { ...DATA.temagruppe, type: 'temagruppe', col: 0 };
    nodes.push(tgNode);

    // Foretak nodes
    DATA.foretak.forEach(f => {
        nodes.push({ ...f, type: 'foretak', col: 1 });
        links.push({ source: tgNode.id, target: f.id, sourceType: 'temagruppe' });
    });

    // Verktoy nodes
    DATA.verktoy.forEach(v => {
        nodes.push({ ...v, type: 'verktoy', col: 2 });
        if (v.linked_foretak) {
            links.push({ source: v.linked_foretak, target: v.id, sourceType: 'foretak' });
        } else {
            // Link directly from temagruppe if no foretak link
            links.push({ source: tgNode.id, target: v.id, sourceType: 'temagruppe' });
        }
    });

    // Kunnskapskilder nodes
    DATA.kunnskapskilder.forEach(k => {
        nodes.push({ ...k, type: 'kunnskap', col: 3 });
        links.push({ source: tgNode.id, target: k.id, sourceType: 'temagruppe' });
    });

    // Arrangementer nodes
    DATA.arrangementer.forEach(a => {
        nodes.push({ ...a, type: 'arrangement', col: 4 });
        links.push({ source: tgNode.id, target: a.id, sourceType: 'temagruppe' });
    });

    // ─── Layout Calculation ───────────────────────────────────────────
    const svg = d3.select('#bv-eco-svg');
    const container = document.getElementById('bv-eco-viz');

    const margin = { top: 50, right: 30, bottom: 30, left: 30 };
    const nodeWidth = 160;
    const nodeHeight = 40;
    const nodePadding = 12;

    // Group nodes by column
    const columns = {};
    nodes.forEach(n => {
        if (!columns[n.col]) columns[n.col] = [];
        columns[n.col].push(n);
    });

    const numCols = 5;
    const colKeys = [0, 1, 2, 3, 4];

    // Calculate column heights
    const colCounts = colKeys.map(c => (columns[c] || []).length);
    const maxColNodes = Math.max(...colCounts, 1);

    // SVG dimensions
    const colSpacing = 60;
    const totalWidth = margin.left + numCols * nodeWidth + (numCols - 1) * colSpacing + margin.right;
    const minHeight = maxColNodes * (nodeHeight + nodePadding) + margin.top + margin.bottom + 20;
    const svgHeight = Math.max(420, minHeight);

    svg.attr('viewBox', `0 0 ${totalWidth} ${svgHeight}`)
       .attr('preserveAspectRatio', 'xMidYMid meet')
       .style('height', svgHeight + 'px');

    // Column header labels
    const colLabels = ['Temagruppe', 'Foretak', 'Verktoy', 'Kunnskapskilder', 'Arrangementer'];
    const colHeaderColors = [COLORS.temagruppe, COLORS.foretak, COLORS.verktoy, COLORS.kunnskap, COLORS.arrangement];

    // Position nodes
    const nodeMap = {};
    colKeys.forEach((colIdx, ci) => {
        const col = columns[colIdx] || [];
        const x = margin.left + ci * (nodeWidth + colSpacing);
        const totalColHeight = col.length * nodeHeight + (col.length - 1) * nodePadding;
        const startY = margin.top + (svgHeight - margin.top - margin.bottom - totalColHeight) / 2;

        col.forEach((node, i) => {
            node.x = x;
            node.y = Math.max(margin.top + 10, startY + i * (nodeHeight + nodePadding));
            node.cx = x + nodeWidth / 2;
            node.cy = node.y + nodeHeight / 2;
            nodeMap[node.id] = node;
        });
    });

    // ─── Defs: Gradients & Filters ─────────────────────────────────────
    const defs = svg.append('defs');

    // Drop shadow filter
    const filter = defs.append('filter')
        .attr('id', 'bv-eco-shadow')
        .attr('x', '-20%').attr('y', '-20%')
        .attr('width', '140%').attr('height', '140%');
    filter.append('feDropShadow')
        .attr('dx', 0).attr('dy', 2)
        .attr('stdDeviation', 4)
        .attr('flood-color', 'rgba(0,0,0,0.08)');

    // ─── Draw Column Headers ──────────────────────────────────────────
    colKeys.forEach((colIdx, ci) => {
        const x = margin.left + ci * (nodeWidth + colSpacing) + nodeWidth / 2;
        svg.append('text')
            .attr('class', 'bv-eco-col-header')
            .attr('x', x)
            .attr('y', margin.top - 12)
            .attr('text-anchor', 'middle')
            .attr('fill', colHeaderColors[ci])
            .text(colLabels[ci]);
    });

    // ─── Draw Links ───────────────────────────────────────────────────
    const linkGroup = svg.append('g').attr('class', 'bv-eco-links-group');

    // Build connection index for hover lookups
    const connectionMap = {}; // nodeId -> Set of connected nodeIds
    const linkElements = [];

    links.forEach(l => {
        if (!connectionMap[l.source]) connectionMap[l.source] = new Set();
        if (!connectionMap[l.target]) connectionMap[l.target] = new Set();
        connectionMap[l.source].add(l.target);
        connectionMap[l.target].add(l.source);
    });

    function getConnectedNodes(nodeId) {
        const visited = new Set();
        const queue = [nodeId];
        visited.add(nodeId);
        while (queue.length > 0) {
            const current = queue.shift();
            const neighbors = connectionMap[current] || new Set();
            neighbors.forEach(n => {
                if (!visited.has(n)) {
                    visited.add(n);
                    queue.push(n);
                }
            });
        }
        return visited;
    }

    links.forEach((l, i) => {
        const src = nodeMap[l.source];
        const tgt = nodeMap[l.target];
        if (!src || !tgt) return;

        const color = l.sourceType === 'foretak' ? COLORS.foretak : COLORS.temagruppe;

        // Source on right edge, target on left edge
        const x0 = src.x + nodeWidth;
        const y0 = src.cy;
        const x1 = tgt.x;
        const y1 = tgt.cy;

        // Cubic bezier control points for smooth curve
        const cpx = (x0 + x1) / 2;

        const pathD = `M${x0},${y0} C${cpx},${y0} ${cpx},${y1} ${x1},${y1}`;

        // Main path (semi-transparent)
        const path = linkGroup.append('path')
            .attr('class', `bv-eco-link source-${l.sourceType}`)
            .attr('d', pathD)
            .attr('stroke', color)
            .attr('stroke-width', 2.5)
            .attr('opacity', 0.25)
            .datum(l);

        // Animated flow overlay
        const animPath = linkGroup.append('path')
            .attr('class', `bv-eco-link-animated source-${l.sourceType}`)
            .attr('d', pathD)
            .attr('stroke', color)
            .attr('stroke-width', 1.5)
            .attr('opacity', 0.15)
            .datum(l);

        linkElements.push({ path, animPath, data: l });
    });

    // ─── Draw Nodes ───────────────────────────────────────────────────
    const nodeGroup = svg.append('g').attr('class', 'bv-eco-nodes-group');

    const nodeEls = nodeGroup.selectAll('.bv-eco-node')
        .data(nodes)
        .join('g')
        .attr('class', d => `bv-eco-node type-${d.type}`)
        .attr('transform', d => `translate(${d.x},${d.y})`)
        .style('cursor', 'pointer');

    // Node rectangle
    nodeEls.append('rect')
        .attr('width', nodeWidth)
        .attr('height', nodeHeight)
        .attr('filter', 'url(#bv-eco-shadow)');

    // Node label (truncated)
    nodeEls.append('text')
        .attr('x', nodeWidth / 2)
        .attr('y', nodeHeight / 2)
        .attr('text-anchor', 'middle')
        .attr('dominant-baseline', 'central')
        .text(d => {
            const maxChars = 18;
            return d.label.length > maxChars ? d.label.substring(0, maxChars - 1) + '\u2026' : d.label;
        });

    // ─── Interaction: Hover ───────────────────────────────────────────
    let activeNode = null;

    nodeEls.on('mouseenter', function(event, d) {
        if (activeNode) return; // Don't hover-highlight if a node is clicked
        highlightNode(d.id);
    });

    nodeEls.on('mouseleave', function(event, d) {
        if (activeNode) return;
        clearHighlight();
    });

    // ─── Interaction: Click ───────────────────────────────────────────
    nodeEls.on('click', function(event, d) {
        event.stopPropagation();
        if (activeNode === d.id) {
            activeNode = null;
            clearHighlight();
            hideDetail();
            return;
        }
        activeNode = d.id;
        highlightNode(d.id);
        showDetail(d);
    });

    // Click on background to deselect
    svg.on('click', () => {
        activeNode = null;
        clearHighlight();
        hideDetail();
    });

    function highlightNode(nodeId) {
        const connected = connectionMap[nodeId] || new Set();
        const allConnected = new Set([nodeId, ...connected]);

        svg.classed('bv-eco-dimmed', true);

        // Highlight connected nodes
        nodeEls.classed('highlighted', d => allConnected.has(d.id));

        // Highlight connected links
        linkElements.forEach(le => {
            const isConnected = (le.data.source === nodeId || le.data.target === nodeId);
            le.path.classed('highlighted', isConnected);
            le.animPath.classed('highlighted', isConnected);
        });
    }

    function clearHighlight() {
        svg.classed('bv-eco-dimmed', false);
        nodeEls.classed('highlighted', false);
        linkElements.forEach(le => {
            le.path.classed('highlighted', false);
            le.animPath.classed('highlighted', false);
        });
    }

    // ─── Detail Panel ─────────────────────────────────────────────────
    const detailPanel = document.getElementById('bv-eco-detail');
    const detailContent = document.getElementById('bv-eco-detail-content');
    const detailClose = document.getElementById('bv-eco-detail-close');

    detailClose.addEventListener('click', (e) => {
        e.stopPropagation();
        activeNode = null;
        clearHighlight();
        hideDetail();
    });

    function showDetail(node) {
        const connected = connectionMap[node.id] || new Set();
        const connectedNodes = [...connected].map(id => nodeMap[id]).filter(Boolean);

        let connectionsHTML = '';
        if (connectedNodes.length > 0) {
            const items = connectedNodes.map(cn => {
                const color = COLORS[cn.type] || '#888';
                return `<li><span class="dot" style="background:${color}"></span>${escapeHTML(cn.label)}</li>`;
            }).join('');
            connectionsHTML = `
                <div class="bv-eco-detail-connections">
                    <strong>${connectedNodes.length} forbindelse${connectedNodes.length !== 1 ? 'r' : ''}</strong>
                    <ul>${items}</ul>
                </div>
            `;
        }

        const urlHTML = node.url && node.url !== '#'
            ? `<a href="${escapeHTML(node.url)}" class="bv-eco-detail-link">
                   Se detaljer
                   <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
               </a>`
            : '';

        detailContent.innerHTML = `
            <span class="bv-eco-detail-badge type-${node.type}">${TYPE_LABELS[node.type] || node.type}</span>
            <h3 class="bv-eco-detail-title">${escapeHTML(node.label)}</h3>
            ${connectionsHTML}
            ${urlHTML}
        `;

        detailPanel.classList.add('visible');
    }

    function hideDetail() {
        detailPanel.classList.remove('visible');
    }

    function escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ─── Entry Animation ──────────────────────────────────────────────
    // Animate nodes fading in column by column
    nodeEls.style('opacity', 0);

    colKeys.forEach((colIdx, ci) => {
        nodeEls.filter(d => d.col === colIdx)
            .transition()
            .delay(ci * 200 + 100)
            .duration(500)
            .ease(d3.easeCubicOut)
            .style('opacity', 1);
    });

    // Animate links drawing in
    linkElements.forEach((le, i) => {
        const src = nodeMap[le.data.source];
        if (!src) return;
        const colDelay = (src.col + 1) * 200 + 300;

        const totalLength = le.path.node().getTotalLength();

        le.path
            .attr('stroke-dasharray', totalLength)
            .attr('stroke-dashoffset', totalLength)
            .transition()
            .delay(colDelay + i * 30)
            .duration(800)
            .ease(d3.easeCubicOut)
            .attr('stroke-dashoffset', 0)
            .on('end', function() {
                d3.select(this).attr('stroke-dasharray', 'none');
            });

        le.animPath
            .style('opacity', 0)
            .transition()
            .delay(colDelay + i * 30 + 600)
            .duration(400)
            .style('opacity', 0.15);
    });

    // ─── Stats Bar ────────────────────────────────────────────────────
    const statsEl = document.getElementById('bv-eco-stats');
    const stats = [
        { num: 1, label: 'temagruppe', cls: 'c-orange' },
        { num: DATA.foretak.length, label: 'foretak', cls: 'c-blue' },
        { num: DATA.verktoy.length, label: 'verktoy', cls: 'c-purple' },
        { num: DATA.kunnskapskilder.length, label: 'kunnskapskilder', cls: 'c-green' },
        { num: DATA.arrangementer.length, label: 'arrangementer', cls: 'c-amber' },
    ];
    statsEl.innerHTML = stats.map(s =>
        `<div class="bv-eco-stat">
            <span class="bv-eco-stat-num ${s.cls}">${s.num}</span>
            <span>${s.label}</span>
        </div>`
    ).join('');

})();
</script>

</main>
<?php get_footer(); ?>
