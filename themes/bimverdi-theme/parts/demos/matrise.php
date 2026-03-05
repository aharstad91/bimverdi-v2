<?php
/**
 * Demo: Foretak / Verktoy-matrise
 *
 * Interactive matrix/grid visualization showing which companies (foretak)
 * use which tools (verktoy). Rows = foretak, columns = verktoy.
 * Where a company has registered a tool, a colored dot appears.
 *
 * Loaded via single-demo.php -> get_template_part('parts/demos/matrise')
 * Header is already loaded by the parent template.
 */

if (!defined('ABSPATH')) {
    exit;
}

// ─── Data Loading ───────────────────────────────────────────────────────────

$foretak_list = [];
$verktoy_list = [];
$matrix = []; // foretak_id => [verktoy_id => true]
$verktoy_foretak_map = []; // verktoy_id => foretak_id (who registered it)
$has_real_data = false;

// Get all foretak
$foretak_posts = get_posts([
    'post_type'      => 'foretak',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'title',
    'order'          => 'ASC',
]);

// Get all verktoy
$verktoy_posts = get_posts([
    'post_type'      => 'verktoy',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'title',
    'order'          => 'ASC',
]);

// Build the matrix from real data
if (!empty($foretak_posts) && !empty($verktoy_posts)) {
    $has_real_data = true;

    foreach ($foretak_posts as $f) {
        $foretak_list[$f->ID] = [
            'id'    => $f->ID,
            'title' => $f->post_title,
            'url'   => get_permalink($f),
            'desc'  => wp_trim_words(get_the_excerpt($f), 12) ?: '',
        ];
        $matrix[$f->ID] = [];
    }

    foreach ($verktoy_posts as $v) {
        // Get category for color mapping
        $cats = wp_get_post_terms($v->ID, 'verktoykategori', ['fields' => 'names']);
        $category = (!empty($cats) && !is_wp_error($cats)) ? $cats[0] : '';

        $verktoy_list[$v->ID] = [
            'id'       => $v->ID,
            'title'    => $v->post_title,
            'url'      => get_permalink($v),
            'desc'     => wp_trim_words(get_the_excerpt($v), 12) ?: '',
            'category' => $category,
        ];

        // Check ACF field 'tilknyttet_foretak' to find which company registered this tool
        $foretak_id = null;
        if (function_exists('get_field')) {
            $foretak_id = get_field('tilknyttet_foretak', $v->ID);
            // Could be a post object or an ID
            if (is_object($foretak_id)) {
                $foretak_id = $foretak_id->ID;
            }
        }
        if (!$foretak_id) {
            $foretak_id = get_post_meta($v->ID, 'tilknyttet_foretak', true);
        }

        if ($foretak_id && isset($matrix[$foretak_id])) {
            $matrix[$foretak_id][$v->ID] = true;
            $verktoy_foretak_map[$v->ID] = $foretak_id;
        }
    }

    // Remove foretak with zero connections to keep matrix meaningful
    // But keep all if less than 20 foretak total
    if (count($foretak_list) > 20) {
        foreach ($matrix as $fid => $tools) {
            if (empty($tools)) {
                unset($foretak_list[$fid]);
                unset($matrix[$fid]);
            }
        }
    }
}

// ─── Fallback Mock Data ─────────────────────────────────────────────────────

if (!$has_real_data || empty($verktoy_list) || empty($foretak_list)) {
    $has_real_data = false;

    $mock_foretak = [
        101 => ['id' => 101, 'title' => 'Veidekke ASA',            'url' => '#', 'desc' => 'Norges storste entreprenor'],
        102 => ['id' => 102, 'title' => 'Multiconsult ASA',        'url' => '#', 'desc' => 'Radgivende ingeniorer'],
        103 => ['id' => 103, 'title' => 'AF Gruppen',              'url' => '#', 'desc' => 'Entreprenor og industri'],
        104 => ['id' => 104, 'title' => 'Sweco Norge',             'url' => '#', 'desc' => 'Radgivende ingeniorer og arkitekter'],
        105 => ['id' => 105, 'title' => 'HENT AS',                 'url' => '#', 'desc' => 'Totalentreprenor'],
        106 => ['id' => 106, 'title' => 'Norconsult AS',           'url' => '#', 'desc' => 'Tverrfaglig radgivning'],
        107 => ['id' => 107, 'title' => 'Skanska Norge',           'url' => '#', 'desc' => 'Bygg og anlegg'],
        108 => ['id' => 108, 'title' => 'Ramboll Norge',           'url' => '#', 'desc' => 'Engineering og design'],
        109 => ['id' => 109, 'title' => 'Asplan Viak',             'url' => '#', 'desc' => 'Samfunnsradgivere'],
        110 => ['id' => 110, 'title' => 'Cowi AS',                 'url' => '#', 'desc' => 'Radgivende ingeniorer'],
    ];

    $mock_verktoy = [
        201 => ['id' => 201, 'title' => 'Revit',                   'url' => '#', 'desc' => 'BIM-modellering',         'category' => 'Modellering'],
        202 => ['id' => 202, 'title' => 'Solibri',                 'url' => '#', 'desc' => 'Modellkontroll',           'category' => 'Kvalitetssikring'],
        203 => ['id' => 203, 'title' => 'Navisworks',              'url' => '#', 'desc' => 'Kollisjonssjekk',          'category' => 'Kvalitetssikring'],
        204 => ['id' => 204, 'title' => 'ArchiCAD',                'url' => '#', 'desc' => 'BIM for arkitekter',       'category' => 'Modellering'],
        205 => ['id' => 205, 'title' => 'Tekla Structures',        'url' => '#', 'desc' => 'Konstruksjonsmodellering', 'category' => 'Modellering'],
        206 => ['id' => 206, 'title' => 'BIMcollab',               'url' => '#', 'desc' => 'BCF og samhandling',       'category' => 'Samhandling'],
        207 => ['id' => 207, 'title' => 'Dalux',                   'url' => '#', 'desc' => 'Feltbasert BIM',           'category' => 'Samhandling'],
        208 => ['id' => 208, 'title' => 'Trimble Connect',         'url' => '#', 'desc' => 'Skybasert samhandling',    'category' => 'Samhandling'],
    ];

    $mock_matrix = [
        101 => [201 => true, 203 => true, 206 => true, 207 => true],
        102 => [201 => true, 202 => true, 204 => true, 206 => true, 208 => true],
        103 => [201 => true, 203 => true, 205 => true, 207 => true],
        104 => [201 => true, 202 => true, 204 => true, 208 => true],
        105 => [201 => true, 202 => true, 205 => true, 206 => true, 207 => true],
        106 => [201 => true, 202 => true, 203 => true, 204 => true, 205 => true, 208 => true],
        107 => [201 => true, 203 => true, 207 => true],
        108 => [201 => true, 202 => true, 206 => true, 208 => true],
        109 => [204 => true, 206 => true, 208 => true],
        110 => [201 => true, 202 => true, 203 => true, 205 => true],
    ];

    $foretak_list = $mock_foretak;
    $verktoy_list = $mock_verktoy;
    $matrix = $mock_matrix;
}

// ─── Compute Stats ──────────────────────────────────────────────────────────

$total_foretak = count($foretak_list);
$total_verktoy = count($verktoy_list);
$total_connections = 0;
$foretak_tool_count = []; // foretak_id => count
$verktoy_company_count = []; // verktoy_id => count

foreach ($matrix as $fid => $tools) {
    $count = count($tools);
    $total_connections += $count;
    $foretak_tool_count[$fid] = $count;
    foreach ($tools as $vid => $val) {
        if (!isset($verktoy_company_count[$vid])) {
            $verktoy_company_count[$vid] = 0;
        }
        $verktoy_company_count[$vid]++;
    }
}

// Most connected company
$most_connected_foretak_id = !empty($foretak_tool_count) ? array_keys($foretak_tool_count, max($foretak_tool_count))[0] : null;
$most_connected_foretak_name = $most_connected_foretak_id ? $foretak_list[$most_connected_foretak_id]['title'] : '-';
$most_connected_foretak_count = $most_connected_foretak_id ? $foretak_tool_count[$most_connected_foretak_id] : 0;

// Most used tool
$most_used_verktoy_id = !empty($verktoy_company_count) ? array_keys($verktoy_company_count, max($verktoy_company_count))[0] : null;
$most_used_verktoy_name = $most_used_verktoy_id ? $verktoy_list[$most_used_verktoy_id]['title'] : '-';
$most_used_verktoy_count = $most_used_verktoy_id ? $verktoy_company_count[$most_used_verktoy_id] : 0;

// Category color map
$category_colors = [
    'Modellering'      => '#3B82F6',
    'Kvalitetssikring' => '#8B5CF6',
    'Samhandling'      => '#10B981',
    'Beregning'        => '#F59E0B',
    'Visualisering'    => '#EC4899',
    'Prosjektstyring'  => '#6366F1',
];

// Build JSON data for JS
$js_foretak = array_values($foretak_list);
$js_verktoy = array_values($verktoy_list);
$js_matrix = [];
foreach ($matrix as $fid => $tools) {
    foreach ($tools as $vid => $val) {
        $js_matrix[] = ['foretak_id' => $fid, 'verktoy_id' => $vid];
    }
}
?>

<main class="bv-matrise-demo bg-[#FAFAF9] min-h-screen">

<!-- ─── Inline Styles ───────────────────────────────────────────────────── -->
<style>
/* ── Layout ───────────────────────────────────────────────────────────── */
.bv-matrise-wrap {
    max-width: 1280px;
    margin: 0 auto;
    padding: 2rem 1rem 4rem;
}

.bv-matrise-back {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.875rem;
    color: #FF8B5E;
    text-decoration: none;
    transition: color 0.15s;
}
.bv-matrise-back:hover { color: #e07040; text-decoration: underline; }

.bv-matrise-header {
    margin-top: 1.5rem;
    margin-bottom: 2rem;
}
.bv-matrise-header h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #1A1A1A;
    margin: 0 0 0.25rem;
    line-height: 1.2;
}
.bv-matrise-header p {
    color: #5A5A5A;
    font-size: 1rem;
    margin: 0;
}

/* ── Mock data badge ─────────────────────────────────────────────────── */
.bv-matrise-mock-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #FFF7ED;
    border: 1px solid #FDBA74;
    color: #9A3412;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.8125rem;
    margin-bottom: 1.5rem;
}

/* ── Stats row ───────────────────────────────────────────────────────── */
.bv-matrise-stats {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}
.bv-matrise-stat {
    display: flex;
    flex-direction: column;
    background: #fff;
    border: 1px solid #E7E5E4;
    border-radius: 8px;
    padding: 1rem 1.25rem;
    min-width: 140px;
}
.bv-matrise-stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1A1A1A;
    line-height: 1;
}
.bv-matrise-stat-label {
    font-size: 0.8125rem;
    color: #5A5A5A;
    margin-top: 4px;
}

/* ── Controls row ────────────────────────────────────────────────────── */
.bv-matrise-controls {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #E7E5E4;
}
.bv-matrise-controls label {
    font-size: 0.8125rem;
    color: #5A5A5A;
    font-weight: 500;
}
.bv-matrise-controls select,
.bv-matrise-controls input[type="text"] {
    font-size: 0.8125rem;
    padding: 6px 10px;
    border: 1px solid #E7E5E4;
    border-radius: 6px;
    background: #fff;
    color: #1A1A1A;
    outline: none;
    transition: border-color 0.15s;
}
.bv-matrise-controls select:focus,
.bv-matrise-controls input:focus {
    border-color: #FF8B5E;
}

/* ── Matrix container ────────────────────────────────────────────────── */
.bv-matrise-container {
    overflow-x: auto;
    background: #fff;
    border: 1px solid #E7E5E4;
    border-radius: 10px;
    padding: 0;
}

.bv-matrise-table {
    border-collapse: collapse;
    width: auto;
    min-width: 100%;
}

/* ── Column headers (rotated) ────────────────────────────────────────── */
.bv-matrise-table thead th {
    position: relative;
    padding: 0;
    height: 160px;
    min-width: 44px;
    vertical-align: bottom;
    border-bottom: 2px solid #E7E5E4;
    background: #fff;
}
.bv-matrise-table thead th:first-child {
    min-width: 200px;
    position: sticky;
    left: 0;
    z-index: 3;
    background: #fff;
}

.bv-matrise-col-label {
    display: block;
    position: absolute;
    bottom: 10px;
    left: 50%;
    transform-origin: bottom left;
    transform: rotate(-45deg);
    white-space: nowrap;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6D28D9;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 3px;
    transition: background 0.15s, color 0.15s;
    user-select: none;
}
.bv-matrise-col-label:hover {
    background: #EDE9FE;
    color: #5B21B6;
}

/* ── Row headers ─────────────────────────────────────────────────────── */
.bv-matrise-table tbody td:first-child {
    position: sticky;
    left: 0;
    z-index: 2;
    background: #fff;
    border-right: 2px solid #E7E5E4;
    padding: 0 12px;
    white-space: nowrap;
}
.bv-matrise-row-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #1E40AF;
    cursor: pointer;
    padding: 4px 6px;
    border-radius: 4px;
    transition: background 0.15s;
    user-select: none;
}
.bv-matrise-row-label:hover {
    background: #EFF6FF;
}
.bv-matrise-row-label .bv-count-badge {
    font-size: 0.6875rem;
    font-weight: 500;
    color: #5A5A5A;
    background: #F5F5F4;
    border-radius: 9999px;
    padding: 1px 7px;
    margin-left: auto;
}

/* ── Body cells ──────────────────────────────────────────────────────── */
.bv-matrise-table tbody tr {
    border-bottom: 1px solid #F5F5F4;
    transition: background 0.12s;
}
.bv-matrise-table tbody tr:last-child {
    border-bottom: none;
}
.bv-matrise-table tbody td {
    text-align: center;
    vertical-align: middle;
    padding: 6px 0;
    min-width: 44px;
    height: 40px;
    transition: background 0.12s;
}

/* ── Dot ─────────────────────────────────────────────────────────────── */
.bv-matrise-dot {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #FF8B5E;
    cursor: pointer;
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s;
    position: relative;
}
.bv-matrise-dot:hover {
    transform: scale(1.6);
    box-shadow: 0 0 0 4px rgba(255, 139, 94, 0.2);
    z-index: 5;
}
.bv-matrise-dot.bv-dot-active {
    transform: scale(1.6);
    box-shadow: 0 0 0 4px rgba(255, 139, 94, 0.3);
}

/* ── Highlights ──────────────────────────────────────────────────────── */
.bv-matrise-table tbody tr.bv-row-highlight {
    background: #FFF7ED;
}
.bv-matrise-table tbody tr.bv-row-highlight td:first-child {
    background: #FFF7ED;
}
.bv-matrise-table td.bv-col-highlight,
.bv-matrise-table th.bv-col-highlight {
    background: #F5F3FF !important;
}
.bv-matrise-table tbody tr.bv-row-highlight td.bv-col-highlight {
    background: #FDECD4 !important;
}

/* ── Tooltip ─────────────────────────────────────────────────────────── */
.bv-matrise-tooltip {
    position: fixed;
    z-index: 1000;
    background: #1A1A1A;
    color: #fff;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 0.8125rem;
    line-height: 1.5;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.15s;
    max-width: 260px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}
.bv-matrise-tooltip.bv-visible { opacity: 1; }
.bv-matrise-tooltip strong { color: #FF8B5E; }

/* ── Detail Panel ────────────────────────────────────────────────────── */
.bv-matrise-detail {
    position: fixed;
    top: 0;
    right: -400px;
    width: 380px;
    max-width: 90vw;
    height: 100vh;
    background: #fff;
    border-left: 1px solid #E7E5E4;
    box-shadow: -4px 0 24px rgba(0,0,0,0.08);
    z-index: 100;
    transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow-y: auto;
    padding: 2rem;
}
.bv-matrise-detail.bv-open { right: 0; }
.bv-matrise-detail-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: none;
    border: none;
    cursor: pointer;
    color: #5A5A5A;
    padding: 4px;
    border-radius: 6px;
    transition: background 0.15s;
}
.bv-matrise-detail-close:hover { background: #F5F5F4; }
.bv-matrise-detail h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1A1A1A;
    margin: 0 0 0.25rem;
}
.bv-matrise-detail .bv-detail-type {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 1rem;
}
.bv-matrise-detail .bv-detail-type.bv-type-foretak { color: #1E40AF; }
.bv-matrise-detail .bv-detail-type.bv-type-verktoy { color: #6D28D9; }
.bv-matrise-detail .bv-detail-desc {
    color: #5A5A5A;
    font-size: 0.875rem;
    margin-bottom: 1.25rem;
    line-height: 1.6;
}
.bv-matrise-detail .bv-detail-connections h4 {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #5A5A5A;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin: 0 0 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #E7E5E4;
}
.bv-matrise-detail .bv-detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
    border-bottom: 1px solid #F5F5F4;
    font-size: 0.8125rem;
    color: #1A1A1A;
}
.bv-matrise-detail .bv-detail-item:last-child { border-bottom: none; }
.bv-matrise-detail .bv-detail-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.bv-matrise-detail .bv-detail-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 1.25rem;
    color: #FF8B5E;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
}
.bv-matrise-detail .bv-detail-link:hover { text-decoration: underline; }

/* ── Summary footer ──────────────────────────────────────────────────── */
.bv-matrise-summary {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
    margin-top: 2rem;
    padding: 1.25rem 1.5rem;
    background: #fff;
    border: 1px solid #E7E5E4;
    border-radius: 10px;
}
.bv-matrise-summary-item {
    font-size: 0.875rem;
    color: #5A5A5A;
    line-height: 1.6;
}
.bv-matrise-summary-item strong {
    color: #1A1A1A;
    font-weight: 600;
}
.bv-matrise-summary-item .bv-summary-highlight {
    color: #FF8B5E;
    font-weight: 700;
}

/* ── Legend ───────────────────────────────────────────────────────────── */
.bv-matrise-legend {
    display: flex;
    gap: 1.25rem;
    flex-wrap: wrap;
    margin-top: 1rem;
    padding: 0.75rem 0;
}
.bv-matrise-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: #5A5A5A;
}
.bv-matrise-legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

/* ── Mobile list view ────────────────────────────────────────────────── */
.bv-matrise-mobile {
    display: none;
}

@media (max-width: 768px) {
    .bv-matrise-container { display: none; }
    .bv-matrise-mobile { display: block; }
    .bv-matrise-controls { flex-direction: column; align-items: stretch; }
    .bv-matrise-stats { gap: 0.75rem; }
    .bv-matrise-stat { min-width: 100px; padding: 0.75rem 1rem; }
    .bv-matrise-stat-value { font-size: 1.25rem; }
    .bv-matrise-header h1 { font-size: 1.5rem; }
    .bv-matrise-detail { width: 100vw; max-width: 100vw; }
}

.bv-mobile-company {
    background: #fff;
    border: 1px solid #E7E5E4;
    border-radius: 8px;
    padding: 1rem 1.25rem;
    margin-bottom: 0.75rem;
}
.bv-mobile-company-name {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #1E40AF;
    margin-bottom: 0.5rem;
}
.bv-mobile-tools {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.bv-mobile-tool-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    padding: 3px 10px;
    border-radius: 9999px;
    background: #FFF7ED;
    color: #9A3412;
    border: 1px solid #FDBA74;
}
.bv-mobile-tool-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}
</style>

<div class="bv-matrise-wrap">

    <!-- Back link -->
    <a href="<?php echo get_post_type_archive_link('demo'); ?>" class="bv-matrise-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 19l-7-7 7-7"/></svg>
        Alle demoer
    </a>

    <!-- Header -->
    <div class="bv-matrise-header">
        <h1>Foretak / Verktoy-matrise</h1>
        <p>Interaktiv matrise som viser hvilke foretak som bruker hvilke verktoy. Hold over rader, kolonner eller prikker for detaljer.</p>
    </div>

    <!-- Mock data notice -->
    <?php if (!$has_real_data): ?>
    <div class="bv-matrise-mock-badge">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Viser demodata - ingen reelle foretak/verktoy-koblinger funnet i databasen
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="bv-matrise-stats">
        <div class="bv-matrise-stat">
            <span class="bv-matrise-stat-value"><?php echo $total_foretak; ?></span>
            <span class="bv-matrise-stat-label">Foretak</span>
        </div>
        <div class="bv-matrise-stat">
            <span class="bv-matrise-stat-value"><?php echo $total_verktoy; ?></span>
            <span class="bv-matrise-stat-label">Verktoy</span>
        </div>
        <div class="bv-matrise-stat">
            <span class="bv-matrise-stat-value"><?php echo $total_connections; ?></span>
            <span class="bv-matrise-stat-label">Koblinger</span>
        </div>
        <div class="bv-matrise-stat">
            <span class="bv-matrise-stat-value"><?php echo ($total_foretak > 0 ? round($total_connections / $total_foretak, 1) : 0); ?></span>
            <span class="bv-matrise-stat-label">Snitt verktoy/foretak</span>
        </div>
    </div>

    <!-- Controls -->
    <div class="bv-matrise-controls">
        <label for="bv-matrise-search">Sok:</label>
        <input type="text" id="bv-matrise-search" placeholder="Filtrer foretak eller verktoy..." style="min-width: 200px;">

        <label for="bv-matrise-sort">Sorter foretak:</label>
        <select id="bv-matrise-sort">
            <option value="alpha">Alfabetisk</option>
            <option value="count-desc">Flest verktoy forst</option>
            <option value="count-asc">Farrest verktoy forst</option>
        </select>

        <label for="bv-matrise-category">Kategori:</label>
        <select id="bv-matrise-category">
            <option value="">Alle kategorier</option>
            <?php
            $categories = array_unique(array_filter(array_column($verktoy_list, 'category')));
            sort($categories);
            foreach ($categories as $cat):
            ?>
            <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Matrix (desktop) -->
    <div class="bv-matrise-container" id="bv-matrise-container">
        <table class="bv-matrise-table" id="bv-matrise-table">
            <thead>
                <tr>
                    <th></th>
                    <?php foreach ($verktoy_list as $vid => $v): ?>
                    <th data-verktoy-id="<?php echo $vid; ?>" data-category="<?php echo esc_attr($v['category']); ?>">
                        <span class="bv-matrise-col-label"
                              data-verktoy-id="<?php echo $vid; ?>"
                              title="<?php echo esc_attr($v['title'] . ($v['category'] ? ' (' . $v['category'] . ')' : '')); ?>">
                            <?php echo esc_html($v['title']); ?>
                        </span>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($foretak_list as $fid => $f): ?>
                <tr data-foretak-id="<?php echo $fid; ?>" data-tool-count="<?php echo $foretak_tool_count[$fid] ?? 0; ?>">
                    <td>
                        <span class="bv-matrise-row-label" data-foretak-id="<?php echo $fid; ?>">
                            <?php echo esc_html($f['title']); ?>
                            <span class="bv-count-badge"><?php echo $foretak_tool_count[$fid] ?? 0; ?></span>
                        </span>
                    </td>
                    <?php foreach ($verktoy_list as $vid => $v):
                        $has_connection = isset($matrix[$fid][$vid]);
                        $dot_color = '#FF8B5E';
                        if ($has_connection && !empty($v['category']) && isset($category_colors[$v['category']])) {
                            $dot_color = $category_colors[$v['category']];
                        }
                    ?>
                    <td data-verktoy-id="<?php echo $vid; ?>" data-foretak-id="<?php echo $fid; ?>">
                        <?php if ($has_connection): ?>
                        <span class="bv-matrise-dot"
                              style="background: <?php echo $dot_color; ?>"
                              data-foretak-id="<?php echo $fid; ?>"
                              data-verktoy-id="<?php echo $vid; ?>"
                              data-foretak-name="<?php echo esc_attr($f['title']); ?>"
                              data-verktoy-name="<?php echo esc_attr($v['title']); ?>"
                              data-verktoy-category="<?php echo esc_attr($v['category']); ?>">
                        </span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile list view -->
    <div class="bv-matrise-mobile" id="bv-matrise-mobile">
        <?php foreach ($foretak_list as $fid => $f):
            $company_tools = array_keys($matrix[$fid] ?? []);
            if (empty($company_tools)) continue;
        ?>
        <div class="bv-mobile-company" data-foretak-id="<?php echo $fid; ?>">
            <div class="bv-mobile-company-name"><?php echo esc_html($f['title']); ?></div>
            <div class="bv-mobile-tools">
                <?php foreach ($company_tools as $vid):
                    if (!isset($verktoy_list[$vid])) continue;
                    $v = $verktoy_list[$vid];
                    $dot_color = '#FF8B5E';
                    if (!empty($v['category']) && isset($category_colors[$v['category']])) {
                        $dot_color = $category_colors[$v['category']];
                    }
                ?>
                <span class="bv-mobile-tool-tag">
                    <span class="bv-mobile-tool-dot" style="background: <?php echo $dot_color; ?>"></span>
                    <?php echo esc_html($v['title']); ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Legend -->
    <?php if (!empty($categories)): ?>
    <div class="bv-matrise-legend">
        <?php foreach ($category_colors as $cat => $color):
            if (in_array($cat, $categories)):
        ?>
        <span class="bv-matrise-legend-item">
            <span class="bv-matrise-legend-dot" style="background: <?php echo $color; ?>"></span>
            <?php echo esc_html($cat); ?>
        </span>
        <?php endif; endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Summary -->
    <div class="bv-matrise-summary">
        <div class="bv-matrise-summary-item">
            Mest tilkoblet foretak: <strong class="bv-summary-highlight"><?php echo esc_html($most_connected_foretak_name); ?></strong>
            (<?php echo $most_connected_foretak_count; ?> verktoy)
        </div>
        <div class="bv-matrise-summary-item">
            Mest brukte verktoy: <strong class="bv-summary-highlight"><?php echo esc_html($most_used_verktoy_name); ?></strong>
            (<?php echo $most_used_verktoy_count; ?> foretak)
        </div>
        <div class="bv-matrise-summary-item">
            Dekning: <strong><?php echo ($total_foretak * $total_verktoy > 0) ? round(($total_connections / ($total_foretak * $total_verktoy)) * 100, 1) : 0; ?>%</strong>
            av mulige koblinger
        </div>
    </div>

</div><!-- .bv-matrise-wrap -->

<!-- Tooltip element -->
<div class="bv-matrise-tooltip" id="bv-matrise-tooltip"></div>

<!-- Detail panel -->
<div class="bv-matrise-detail" id="bv-matrise-detail">
    <button class="bv-matrise-detail-close" id="bv-matrise-detail-close" aria-label="Lukk">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div id="bv-matrise-detail-content"></div>
</div>

<!-- ─── Inline JavaScript ───────────────────────────────────────────────── -->
<script>
(function() {
    'use strict';

    // ── Data ────────────────────────────────────────────────────────────
    const foretakData = <?php echo json_encode($js_foretak); ?>;
    const verktoyData = <?php echo json_encode($js_verktoy); ?>;
    const matrixData  = <?php echo json_encode($js_matrix); ?>;
    const categoryColors = <?php echo json_encode($category_colors); ?>;
    const foretakToolCount = <?php echo json_encode((object)$foretak_tool_count); ?>;
    const verktoyCompanyCount = <?php echo json_encode((object)$verktoy_company_count); ?>;

    // Build lookup maps
    const foretakMap = {};
    foretakData.forEach(f => foretakMap[f.id] = f);
    const verktoyMap = {};
    verktoyData.forEach(v => verktoyMap[v.id] = v);

    // Connections
    const foretakConnections = {}; // fid => [vid, vid, ...]
    const verktoyConnections = {}; // vid => [fid, fid, ...]
    matrixData.forEach(m => {
        if (!foretakConnections[m.foretak_id]) foretakConnections[m.foretak_id] = [];
        foretakConnections[m.foretak_id].push(m.verktoy_id);
        if (!verktoyConnections[m.verktoy_id]) verktoyConnections[m.verktoy_id] = [];
        verktoyConnections[m.verktoy_id].push(m.foretak_id);
    });

    // ── DOM references ──────────────────────────────────────────────────
    const table     = document.getElementById('bv-matrise-table');
    const tooltip   = document.getElementById('bv-matrise-tooltip');
    const detail    = document.getElementById('bv-matrise-detail');
    const detailContent = document.getElementById('bv-matrise-detail-content');
    const searchInput   = document.getElementById('bv-matrise-search');
    const sortSelect    = document.getElementById('bv-matrise-sort');
    const categorySelect = document.getElementById('bv-matrise-category');

    if (!table) return;

    const tbody = table.querySelector('tbody');
    const headerCells = table.querySelectorAll('thead th');

    // ── Tooltip ─────────────────────────────────────────────────────────
    function showTooltip(html, e) {
        tooltip.innerHTML = html;
        tooltip.classList.add('bv-visible');
        positionTooltip(e);
    }

    function positionTooltip(e) {
        const rect = tooltip.getBoundingClientRect();
        let x = e.clientX + 14;
        let y = e.clientY - 10;
        if (x + rect.width > window.innerWidth - 10) x = e.clientX - rect.width - 14;
        if (y + rect.height > window.innerHeight - 10) y = window.innerHeight - rect.height - 10;
        if (y < 10) y = 10;
        tooltip.style.left = x + 'px';
        tooltip.style.top = y + 'px';
    }

    function hideTooltip() {
        tooltip.classList.remove('bv-visible');
    }

    // ── Row/Column highlighting ─────────────────────────────────────────
    function highlightRow(foretakId) {
        clearHighlights();
        const row = tbody.querySelector('tr[data-foretak-id="' + foretakId + '"]');
        if (row) row.classList.add('bv-row-highlight');
    }

    function highlightCol(verktoyId) {
        clearHighlights();
        // Highlight header
        headerCells.forEach(th => {
            if (th.dataset.verktoyId == verktoyId) th.classList.add('bv-col-highlight');
        });
        // Highlight body cells
        tbody.querySelectorAll('td[data-verktoy-id="' + verktoyId + '"]').forEach(td => {
            td.classList.add('bv-col-highlight');
        });
    }

    function highlightBoth(foretakId, verktoyId) {
        clearHighlights();
        // Row
        const row = tbody.querySelector('tr[data-foretak-id="' + foretakId + '"]');
        if (row) row.classList.add('bv-row-highlight');
        // Column
        headerCells.forEach(th => {
            if (th.dataset.verktoyId == verktoyId) th.classList.add('bv-col-highlight');
        });
        tbody.querySelectorAll('td[data-verktoy-id="' + verktoyId + '"]').forEach(td => {
            td.classList.add('bv-col-highlight');
        });
    }

    function clearHighlights() {
        tbody.querySelectorAll('.bv-row-highlight').forEach(el => el.classList.remove('bv-row-highlight'));
        table.querySelectorAll('.bv-col-highlight').forEach(el => el.classList.remove('bv-col-highlight'));
        table.querySelectorAll('.bv-dot-active').forEach(el => el.classList.remove('bv-dot-active'));
    }

    // ── Detail Panel ────────────────────────────────────────────────────
    function showForetakDetail(foretakId) {
        const f = foretakMap[foretakId];
        if (!f) return;
        const tools = (foretakConnections[foretakId] || []).map(vid => verktoyMap[vid]).filter(Boolean);

        let html = '<div class="bv-detail-type bv-type-foretak">Foretak</div>';
        html += '<h3>' + escHtml(f.title) + '</h3>';
        if (f.desc) html += '<div class="bv-detail-desc">' + escHtml(f.desc) + '</div>';
        html += '<div class="bv-detail-connections">';
        html += '<h4>Registrerte verktoy (' + tools.length + ')</h4>';
        tools.forEach(v => {
            const color = (v.category && categoryColors[v.category]) ? categoryColors[v.category] : '#FF8B5E';
            html += '<div class="bv-detail-item">';
            html += '<span class="bv-detail-dot" style="background:' + color + '"></span>';
            html += escHtml(v.title);
            if (v.category) html += ' <span style="color:#5A5A5A;font-size:0.75rem">(' + escHtml(v.category) + ')</span>';
            html += '</div>';
        });
        if (tools.length === 0) {
            html += '<div class="bv-detail-item" style="color:#5A5A5A">Ingen verktoy registrert</div>';
        }
        html += '</div>';
        if (f.url && f.url !== '#') {
            html += '<a href="' + f.url + '" class="bv-detail-link">Se foretaksprofil <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>';
        }

        detailContent.innerHTML = html;
        detail.classList.add('bv-open');
    }

    function showVerktoyDetail(verktoyId) {
        const v = verktoyMap[verktoyId];
        if (!v) return;
        const companies = (verktoyConnections[verktoyId] || []).map(fid => foretakMap[fid]).filter(Boolean);

        let html = '<div class="bv-detail-type bv-type-verktoy">Verktoy</div>';
        html += '<h3>' + escHtml(v.title) + '</h3>';
        if (v.category) html += '<div style="font-size:0.8125rem;color:#5A5A5A;margin-bottom:0.75rem">Kategori: <strong>' + escHtml(v.category) + '</strong></div>';
        if (v.desc) html += '<div class="bv-detail-desc">' + escHtml(v.desc) + '</div>';
        html += '<div class="bv-detail-connections">';
        html += '<h4>Brukes av (' + companies.length + ' foretak)</h4>';
        companies.forEach(f => {
            html += '<div class="bv-detail-item">';
            html += '<span class="bv-detail-dot" style="background:#3B82F6"></span>';
            html += escHtml(f.title);
            html += '</div>';
        });
        if (companies.length === 0) {
            html += '<div class="bv-detail-item" style="color:#5A5A5A">Ingen foretak registrert</div>';
        }
        html += '</div>';
        if (v.url && v.url !== '#') {
            html += '<a href="' + v.url + '" class="bv-detail-link">Se verktoydetaljer <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>';
        }

        detailContent.innerHTML = html;
        detail.classList.add('bv-open');
    }

    function showDotDetail(foretakId, verktoyId) {
        const f = foretakMap[foretakId];
        const v = verktoyMap[verktoyId];
        if (!f || !v) return;

        const fTools = (foretakConnections[foretakId] || []).length;
        const vCompanies = (verktoyConnections[verktoyId] || []).length;
        const color = (v.category && categoryColors[v.category]) ? categoryColors[v.category] : '#FF8B5E';

        let html = '<div class="bv-detail-type" style="color:#FF8B5E">Kobling</div>';
        html += '<h3>' + escHtml(f.title) + '</h3>';
        html += '<div style="display:flex;align-items:center;gap:8px;margin:0.5rem 0 1.25rem">';
        html += '<span class="bv-detail-dot" style="background:' + color + ';width:12px;height:12px"></span>';
        html += '<span style="font-size:1rem;font-weight:600;color:#6D28D9">' + escHtml(v.title) + '</span>';
        if (v.category) html += '<span style="color:#5A5A5A;font-size:0.8125rem">(' + escHtml(v.category) + ')</span>';
        html += '</div>';
        html += '<div class="bv-detail-desc">';
        html += '<strong>' + escHtml(f.title) + '</strong> har registrert <strong>' + escHtml(v.title) + '</strong> som et av sine ' + fTools + ' verktoy.<br><br>';
        html += '<strong>' + escHtml(v.title) + '</strong> brukes av ' + vCompanies + ' foretak totalt.';
        html += '</div>';
        html += '<div style="display:flex;gap:1rem;flex-wrap:wrap">';
        if (f.url && f.url !== '#') {
            html += '<a href="' + f.url + '" class="bv-detail-link">Se foretak <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>';
        }
        if (v.url && v.url !== '#') {
            html += '<a href="' + v.url + '" class="bv-detail-link" style="color:#6D28D9">Se verktoy <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>';
        }
        html += '</div>';

        detailContent.innerHTML = html;
        detail.classList.add('bv-open');
    }

    function closeDetail() {
        detail.classList.remove('bv-open');
    }

    // ── Event listeners ─────────────────────────────────────────────────

    // Row hover (row label)
    tbody.querySelectorAll('.bv-matrise-row-label').forEach(label => {
        const fid = label.dataset.foretakId;
        label.addEventListener('mouseenter', function(e) {
            highlightRow(fid);
            const f = foretakMap[fid];
            const count = foretakConnections[fid] ? foretakConnections[fid].length : 0;
            const toolNames = (foretakConnections[fid] || []).map(vid => verktoyMap[vid] ? verktoyMap[vid].title : '').filter(Boolean).join(', ');
            showTooltip(
                '<strong>' + escHtml(f ? f.title : '') + '</strong><br>' +
                count + ' verktoy' +
                (toolNames ? '<br><span style="opacity:0.8">' + escHtml(toolNames) + '</span>' : ''),
                e
            );
        });
        label.addEventListener('mousemove', positionTooltip);
        label.addEventListener('mouseleave', function() { clearHighlights(); hideTooltip(); });
        label.addEventListener('click', function() { showForetakDetail(fid); });
    });

    // Column hover (column header labels)
    table.querySelectorAll('.bv-matrise-col-label').forEach(label => {
        const vid = label.dataset.verktoyId;
        label.addEventListener('mouseenter', function(e) {
            highlightCol(vid);
            const v = verktoyMap[vid];
            const count = verktoyConnections[vid] ? verktoyConnections[vid].length : 0;
            showTooltip(
                '<strong>' + escHtml(v ? v.title : '') + '</strong>' +
                (v && v.category ? '<br>Kategori: ' + escHtml(v.category) : '') +
                '<br>' + count + ' foretak bruker dette',
                e
            );
        });
        label.addEventListener('mousemove', positionTooltip);
        label.addEventListener('mouseleave', function() { clearHighlights(); hideTooltip(); });

        // Click column header → sort by that tool (companies with it first)
        label.addEventListener('click', function() {
            sortByVerktoy(vid);
        });
    });

    // Dot hover/click
    table.querySelectorAll('.bv-matrise-dot').forEach(dot => {
        const fid = dot.dataset.foretakId;
        const vid = dot.dataset.verktoyId;

        dot.addEventListener('mouseenter', function(e) {
            highlightBoth(fid, vid);
            dot.classList.add('bv-dot-active');
            showTooltip(
                '<strong>' + escHtml(dot.dataset.foretakName) + '</strong> bruker<br><strong>' + escHtml(dot.dataset.verktoyName) + '</strong>' +
                (dot.dataset.verktoyCategory ? '<br><span style="opacity:0.7">' + escHtml(dot.dataset.verktoyCategory) + '</span>' : ''),
                e
            );
        });
        dot.addEventListener('mousemove', positionTooltip);
        dot.addEventListener('mouseleave', function() { clearHighlights(); hideTooltip(); });
        dot.addEventListener('click', function() { showDotDetail(fid, vid); });
    });

    // Close detail panel
    document.getElementById('bv-matrise-detail-close').addEventListener('click', closeDetail);
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDetail();
    });
    // Close when clicking overlay area (outside panel)
    detail.addEventListener('click', function(e) {
        if (e.target === detail) closeDetail();
    });

    // ── Search ──────────────────────────────────────────────────────────
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const rows = tbody.querySelectorAll('tr[data-foretak-id]');
        const cols = table.querySelectorAll('thead th[data-verktoy-id]');

        if (!query) {
            rows.forEach(r => r.style.display = '');
            cols.forEach(c => { c.style.display = ''; showColumnCells(c.dataset.verktoyId, true); });
            return;
        }

        // Filter rows
        rows.forEach(row => {
            const fid = row.dataset.foretakId;
            const f = foretakMap[fid];
            const match = f && f.title.toLowerCase().includes(query);
            // Also match if any of this company's tools match
            const toolMatch = (foretakConnections[fid] || []).some(vid => {
                const v = verktoyMap[vid];
                return v && v.title.toLowerCase().includes(query);
            });
            row.style.display = (match || toolMatch) ? '' : 'none';
        });

        // Filter columns
        cols.forEach(col => {
            const vid = col.dataset.verktoyId;
            const v = verktoyMap[vid];
            const match = v && (v.title.toLowerCase().includes(query) || (v.category && v.category.toLowerCase().includes(query)));
            col.style.display = match || !query ? '' : '';
            // Don't hide columns on search - just highlight matching ones
        });
    });

    // ── Sort ────────────────────────────────────────────────────────────
    sortSelect.addEventListener('change', function() {
        const mode = this.value;
        const rows = Array.from(tbody.querySelectorAll('tr[data-foretak-id]'));

        rows.sort((a, b) => {
            const aId = a.dataset.foretakId;
            const bId = b.dataset.foretakId;
            const aName = (foretakMap[aId] || {}).title || '';
            const bName = (foretakMap[bId] || {}).title || '';
            const aCount = parseInt(a.dataset.toolCount) || 0;
            const bCount = parseInt(b.dataset.toolCount) || 0;

            if (mode === 'alpha') return aName.localeCompare(bName, 'nb');
            if (mode === 'count-desc') return bCount - aCount || aName.localeCompare(bName, 'nb');
            if (mode === 'count-asc') return aCount - bCount || aName.localeCompare(bName, 'nb');
            return 0;
        });

        rows.forEach(row => tbody.appendChild(row));
    });

    // ── Category filter ─────────────────────────────────────────────────
    categorySelect.addEventListener('change', function() {
        const cat = this.value;
        const cols = table.querySelectorAll('thead th[data-verktoy-id]');

        cols.forEach(col => {
            const vid = col.dataset.verktoyId;
            const show = !cat || col.dataset.category === cat;
            col.style.display = show ? '' : 'none';
            showColumnCells(vid, show);
        });
    });

    function showColumnCells(verktoyId, show) {
        tbody.querySelectorAll('td[data-verktoy-id="' + verktoyId + '"]').forEach(td => {
            td.style.display = show ? '' : 'none';
        });
    }

    // ── Sort by specific verktoy column ─────────────────────────────────
    function sortByVerktoy(verktoyId) {
        const rows = Array.from(tbody.querySelectorAll('tr[data-foretak-id]'));

        rows.sort((a, b) => {
            const aHas = a.querySelector('td[data-verktoy-id="' + verktoyId + '"] .bv-matrise-dot') ? 1 : 0;
            const bHas = b.querySelector('td[data-verktoy-id="' + verktoyId + '"] .bv-matrise-dot') ? 1 : 0;
            if (aHas !== bHas) return bHas - aHas;
            const aName = (foretakMap[a.dataset.foretakId] || {}).title || '';
            const bName = (foretakMap[b.dataset.foretakId] || {}).title || '';
            return aName.localeCompare(bName, 'nb');
        });

        rows.forEach(row => tbody.appendChild(row));

        // Reset the sort select
        sortSelect.value = 'alpha';

        // Visual feedback: briefly highlight the column
        highlightCol(verktoyId);
        setTimeout(clearHighlights, 1200);
    }

    // ── Utility ─────────────────────────────────────────────────────────
    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

})();
</script>

</main>
<?php