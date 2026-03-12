<?php
/**
 * Demo: Temagruppe-graf (alle temagrupper)
 *
 * D3.js force-directed graph showing ALL temagrupper and their
 * connected entities: foretak, verktøy, kunnskapskilder, arrangementer, artikler.
 *
 * Entities shared between temagrupper naturally create cross-links,
 * revealing the full BIM Verdi ecosystem.
 */

if (!defined('ABSPATH')) {
    exit;
}

// ─── Temagruppe colors (unique per group) ────────────────────────────────────

$tg_colors = [
    'BIMtech'       => '#FF8B5E',
    'ByggesaksBIM'  => '#2E86DE',
    'ProsjektBIM'   => '#27AE60',
    'EiendomsBIM'   => '#8E44AD',
    'MiljøBIM'      => '#16A085',
    'SirkBIM'       => '#E74C3C',
];

// ─── Data Loading ───────────────────────────────────────────────────────────

$nodes = [];
$links = [];
$node_ids = []; // Track existing node IDs to deduplicate
$stats = [
    'temagruppe'     => 0,
    'foretak'        => 0,
    'verktoy'        => 0,
    'kunnskapskilde' => 0,
    'arrangement'    => 0,
    'artikkel'       => 0,
];

$all_foretak_ids = [];
$all_verktoy_ids = [];

// Get all temagruppe taxonomy terms
$tg_terms = get_terms([
    'taxonomy'   => 'temagruppe',
    'hide_empty' => false,
]);

// Get all theme_group posts for URLs/descriptions
$tg_posts = get_posts([
    'post_type'      => 'theme_group',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
]);
$tg_post_map = [];
foreach ($tg_posts as $p) {
    $tg_post_map[$p->post_title] = $p;
}

$has_real_data = false;

if (!empty($tg_terms) && !is_wp_error($tg_terms)) {
    foreach ($tg_terms as $term) {
        $tg_id = 'tg_' . $term->term_id;
        $tg_post = $tg_post_map[$term->name] ?? null;
        $short_desc = '';
        if ($tg_post) {
            $short_desc = get_field('kort_beskrivelse', $tg_post->ID) ?: wp_trim_words(get_the_excerpt($tg_post), 20);
        }

        // Determine color — use name match, fallback to default
        $color = $tg_colors[$term->name] ?? '#999';

        $nodes[] = [
            'id'       => $tg_id,
            'label'    => $term->name,
            'type'     => 'temagruppe',
            'tg'       => $term->name,
            'url'      => $tg_post ? get_permalink($tg_post) : '#',
            'desc'     => $short_desc ?: $term->name,
            'tgColor'  => $color,
        ];
        $node_ids[$tg_id] = true;
        $stats['temagruppe']++;

        // ── Query each entity type for this temagruppe ──

        $entity_types = [
            'foretak'        => ['post_type' => 'foretak',        'prefix' => 'foretak_'],
            'verktoy'        => ['post_type' => 'verktoy',        'prefix' => 'verktoy_'],
            'kunnskapskilde' => ['post_type' => 'kunnskapskilde', 'prefix' => 'kk_'],
            'arrangement'    => ['post_type' => 'arrangement',    'prefix' => 'arr_'],
            'artikkel'       => ['post_type' => 'artikkel',       'prefix' => 'art_'],
        ];

        foreach ($entity_types as $etype => $conf) {
            $query = new WP_Query([
                'post_type'      => $conf['post_type'],
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'tax_query'      => [['taxonomy' => 'temagruppe', 'field' => 'term_id', 'terms' => $term->term_id]],
            ]);

            if ($query->have_posts()) {
                $has_real_data = true;
                while ($query->have_posts()) {
                    $query->the_post();
                    $pid = get_the_ID();
                    $nid = $conf['prefix'] . $pid;

                    // Only add node once (dedup), but always add link
                    if (!isset($node_ids[$nid])) {
                        $desc = '';
                        if ($etype === 'kunnskapskilde') {
                            $desc = get_field('utgiver', $pid) ?: get_field('kildetype', $pid) ?: '';
                        } elseif ($etype === 'arrangement') {
                            $dato = get_field('dato', $pid) ?: get_field('arrangement_dato', $pid) ?: '';
                            $desc = $dato ? 'Dato: ' . $dato : '';
                        } elseif ($etype === 'artikkel') {
                            $desc = get_field('artikkel_ingress', $pid) ?: '';
                        } elseif ($etype === 'verktoy') {
                            $terms_v = get_the_terms($pid, 'verktoykategori');
                            $desc = ($terms_v && !is_wp_error($terms_v)) ? $terms_v[0]->name : '';
                        }
                        if (!$desc) {
                            $desc = wp_trim_words(get_the_excerpt(), 12) ?: '';
                        }

                        $nodes[] = [
                            'id'    => $nid,
                            'label' => get_the_title(),
                            'type'  => $etype,
                            'url'   => get_permalink(),
                            'desc'  => $desc,
                        ];
                        $node_ids[$nid] = true;
                        $stats[$etype]++;

                        if ($etype === 'foretak') $all_foretak_ids[] = $pid;
                        if ($etype === 'verktoy') $all_verktoy_ids[] = $pid;
                    }

                    // Link entity → temagruppe (always, creates multi-temagruppe bridges)
                    $links[] = ['source' => $tg_id, 'target' => $nid];
                }
            }
            wp_reset_postdata();
        }

        // Also try ACF field "formaalstema" for verktøy
        $verktoy_acf_query = new WP_Query([
            'post_type'      => 'verktoy',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [['key' => 'formaalstema', 'value' => $term->name, 'compare' => 'LIKE']],
        ]);
        if ($verktoy_acf_query->have_posts()) {
            $has_real_data = true;
            while ($verktoy_acf_query->have_posts()) {
                $verktoy_acf_query->the_post();
                $pid = get_the_ID();
                $nid = 'verktoy_' . $pid;

                if (!isset($node_ids[$nid])) {
                    $terms_v = get_the_terms($pid, 'verktoykategori');
                    $desc = ($terms_v && !is_wp_error($terms_v)) ? $terms_v[0]->name : '';
                    if (!$desc) $desc = wp_trim_words(get_the_excerpt(), 12) ?: '';

                    $nodes[] = [
                        'id'    => $nid,
                        'label' => get_the_title(),
                        'type'  => 'verktoy',
                        'url'   => get_permalink(),
                        'desc'  => $desc,
                    ];
                    $node_ids[$nid] = true;
                    $stats['verktoy']++;
                    $all_verktoy_ids[] = $pid;
                }

                $links[] = ['source' => $tg_id, 'target' => $nid];
            }
        }
        wp_reset_postdata();
    }

    // ── Cross-links: foretak ↔ verktøy (via tilknyttet_foretak) ──
    foreach (array_unique($all_verktoy_ids) as $vid) {
        $foretak_id = get_field('tilknyttet_foretak', $vid);
        if ($foretak_id && in_array($foretak_id, $all_foretak_ids)) {
            $links[] = [
                'source' => 'foretak_' . $foretak_id,
                'target' => 'verktoy_' . $vid,
            ];
        }
    }

    // Deduplicate links
    $link_keys = [];
    $unique_links = [];
    foreach ($links as $l) {
        $key = $l['source'] . '|' . $l['target'];
        $key2 = $l['target'] . '|' . $l['source'];
        if (!isset($link_keys[$key]) && !isset($link_keys[$key2])) {
            $link_keys[$key] = true;
            $unique_links[] = $l;
        }
    }
    $links = $unique_links;
}

// ─── Decode HTML entities ───────────────────────────────────────────────────

foreach ($nodes as &$n) {
    $n['label'] = html_entity_decode($n['label'], ENT_QUOTES, 'UTF-8');
    if (!empty($n['desc'])) {
        $n['desc'] = html_entity_decode($n['desc'], ENT_QUOTES, 'UTF-8');
    }
}
unset($n);

$graph_json = wp_json_encode(['nodes' => $nodes, 'links' => $links], JSON_UNESCAPED_UNICODE);
$total_entities = $stats['foretak'] + $stats['verktoy'] + $stats['kunnskapskilde'] + $stats['arrangement'] + $stats['artikkel'];
$tg_colors_json = wp_json_encode($tg_colors);
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
    /* ═══ Full-screen dark constellation layout ═══ */
    #content { display: contents; }
    body footer, .site-header, #wpadminbar { /* hide chrome for immersive mode */ }

    .tg-wrap {
        display: flex;
        height: calc(100vh - 64px - 40px);
        font-family: 'DM Sans', system-ui, sans-serif;
        background: #0D0F12;
    }

    /* ═══ Sidebar — editorial, spacious ═══ */
    .tg-sidebar {
        width: 380px;
        background: #111318;
        border-right: 1px solid rgba(255,255,255,0.06);
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        color: #C8C5BD;
    }
    .tg-sidebar-inner {
        padding: 32px 28px;
        flex: 1;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: rgba(255,255,255,0.1) transparent;
    }
    .tg-sidebar-inner::-webkit-scrollbar { width: 4px; }
    .tg-sidebar-inner::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

    .tg-eyebrow {
        font-family: 'JetBrains Mono', monospace;
        font-size: 10px;
        font-weight: 500;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #FF8B5E;
        margin: 0 0 12px 0;
    }
    .tg-sidebar h2 {
        font-size: 22px;
        font-weight: 300;
        color: #F0EDE6;
        margin: 0;
        line-height: 1.3;
        letter-spacing: -0.02em;
    }
    .tg-sidebar h2 strong {
        font-weight: 600;
    }
    .tg-subtitle {
        font-size: 13px;
        color: #7A776E;
        margin: 8px 0 0 0;
        line-height: 1.6;
        font-weight: 300;
    }

    /* Stats — horizontal pills */
    .tg-stats {
        display: flex;
        gap: 6px;
        margin: 24px 0;
        flex-wrap: wrap;
    }
    .tg-stat-pill {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 20px;
        font-size: 12px;
        color: #8A8780;
    }
    .tg-stat-pill strong {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 500;
        font-size: 13px;
        color: #F0EDE6;
    }

    .tg-section-label {
        font-family: 'JetBrains Mono', monospace;
        font-size: 9px;
        font-weight: 500;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #5A574F;
        margin: 28px 0 10px 0;
    }

    /* Filter chips */
    .tg-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .tg-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        border: 1.5px solid transparent;
        transition: all 0.2s ease;
        user-select: none;
        background: rgba(255,255,255,0.04);
    }
    .tg-chip input { display: none; }
    .tg-chip .chip-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .tg-chip .chip-dot--rect { border-radius: 2px; }
    .tg-chip--active {
        border-color: currentColor;
        background: color-mix(in srgb, currentColor 8%, transparent);
    }
    .tg-chip--inactive {
        opacity: 0.3;
        background: rgba(255,255,255,0.02) !important;
        border-color: transparent !important;
    }

    /* Search */
    .tg-search {
        width: 100%;
        padding: 10px 12px 10px 36px;
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 10px;
        font-size: 13px;
        font-family: 'DM Sans', system-ui, sans-serif;
        background: rgba(255,255,255,0.03) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23555' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") 10px center no-repeat;
        color: #F0EDE6;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .tg-search::placeholder { color: #5A574F; }
    .tg-search:focus {
        outline: none;
        border-color: #FF8B5E;
        box-shadow: 0 0 0 3px rgba(255, 139, 94, 0.1);
    }

    .tg-hint {
        font-size: 11px;
        color: #3D3B36;
        line-height: 1.5;
        margin: 10px 0 0 0;
    }
    .tg-hr {
        border: none;
        border-top: 1px solid rgba(255,255,255,0.05);
        margin: 24px 0;
    }

    /* Detail card */
    .tg-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 14px;
        padding: 20px;
        transition: all 0.3s ease;
    }
    .tg-card--highlight {
        border-color: rgba(255, 139, 94, 0.3);
        background: rgba(255, 139, 94, 0.04);
    }
    .tg-card h3 {
        font-size: 15px;
        font-weight: 600;
        color: #F0EDE6;
        margin: 0 0 6px 0;
        line-height: 1.4;
    }
    .tg-card-type {
        display: inline-flex;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 600;
        font-family: 'JetBrains Mono', monospace;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #fff;
        margin-bottom: 12px;
    }
    .tg-card-desc {
        font-size: 13px;
        color: #7A776E;
        line-height: 1.6;
        margin: 0 0 16px 0;
        font-weight: 300;
    }
    .tg-card-meta {
        display: flex;
        align-items: center;
        gap: 6px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 12px;
        color: #5A574F;
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .tg-card-meta strong {
        color: #FF8B5E;
        font-size: 16px;
    }
    .tg-card a {
        color: #FF8B5E;
        text-decoration: none;
        font-size: 12px;
        font-weight: 500;
        transition: color 0.15s;
    }
    .tg-card a:hover { color: #ffaa85; text-decoration: underline; }
    .tg-card-link-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 7px 14px;
        border-radius: 8px;
        background: rgba(255, 139, 94, 0.1);
        border: 1px solid rgba(255, 139, 94, 0.2);
        margin-top: 4px;
        font-size: 12px;
    }
    .tg-card-neighbors-label {
        font-family: 'JetBrains Mono', monospace;
        font-size: 9px;
        font-weight: 500;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #5A574F;
        margin: 16px 0 8px 0;
        padding-top: 16px;
        border-top: 1px solid rgba(255,255,255,0.05);
    }
    .tg-card-neighbors .tg-neighbor {
        display: inline-block;
        padding: 3px 8px;
        margin: 2px 3px 2px 0;
        border-radius: 6px;
        font-size: 11px;
        background: rgba(255,255,255,0.05);
        color: #8A8780;
        cursor: pointer;
        transition: all 0.15s;
        border: 1px solid transparent;
    }
    .tg-card-neighbors .tg-neighbor:hover {
        background: rgba(255, 139, 94, 0.15);
        border-color: rgba(255, 139, 94, 0.3);
        color: #FF8B5E;
    }

    /* ═══ Viz area — dark canvas ═══ */
    .tg-viz {
        flex: 1;
        position: relative;
        background: radial-gradient(ellipse at 50% 50%, #151820 0%, #0D0F12 70%);
        overflow: hidden;
    }

    /* D3 node styling */
    .tg-viz svg { display: block; }
    .tg-node { cursor: pointer; }
    .tg-node circle, .tg-node rect, .tg-node polygon {
        transition: opacity 0.4s ease, stroke-width 0.25s ease, filter 0.4s ease;
    }
    .tg-node circle { stroke: rgba(255,255,255,0.15); stroke-width: 1.5px; }
    .tg-node rect { rx: 3; ry: 3; stroke: rgba(255,255,255,0.12); stroke-width: 1px; }
    .tg-node polygon { stroke: rgba(255,255,255,0.12); stroke-width: 1px; }

    /* Labels — hidden by default, shown for temagrupper */
    .tg-node text {
        font-family: 'DM Sans', system-ui, sans-serif;
        fill: rgba(240, 237, 230, 0.9);
        pointer-events: none;
        transition: opacity 0.4s ease;
    }
    .tg-node--entity text { opacity: 0; } /* Hidden for entities */
    .tg-node--entity:hover text { opacity: 1; } /* Show on hover */

    .tg-link {
        transition: opacity 0.4s ease, stroke-width 0.3s ease;
    }

    /* Dimmed state */
    .tg-node--dimmed circle,
    .tg-node--dimmed rect,
    .tg-node--dimmed polygon { opacity: 0.06; }
    .tg-node--dimmed text { opacity: 0 !important; }
    .tg-link--dimmed { opacity: 0.02 !important; }

    /* Highlighted state — glow but NO bulk labels (hover-only for entities) */
    .tg-link--highlighted { stroke-width: 2.5px !important; opacity: 0.7 !important; }
    .tg-node--highlighted circle,
    .tg-node--highlighted rect,
    .tg-node--highlighted polygon {
        stroke: rgba(255,255,255,0.5);
        stroke-width: 2px;
        filter: drop-shadow(0 0 8px currentColor);
        opacity: 1 !important;
    }
    /* Only temagruppe labels auto-show; entity labels only on hover */
    .tg-node--highlighted.tg-node--tg text { opacity: 1 !important; }
    .tg-node--highlighted.tg-node--entity text { opacity: 0; }
    .tg-node--highlighted.tg-node--entity:hover text { opacity: 1 !important; }

    /* Selected state */
    .tg-node--selected circle,
    .tg-node--selected rect,
    .tg-node--selected polygon {
        stroke: #FF8B5E;
        stroke-width: 3px;
        filter: drop-shadow(0 0 16px rgba(255,139,94,0.5));
    }
    .tg-node--selected text { opacity: 1 !important; }

    /* Temagruppe glow */
    .tg-node--tg circle {
        filter: drop-shadow(0 0 12px currentColor);
        stroke-width: 2px;
        stroke: rgba(255,255,255,0.2);
    }

    .hidden { display: none; }

    /* Hover tooltip */
    .tg-tooltip {
        position: absolute;
        pointer-events: none;
        background: rgba(20, 22, 28, 0.95);
        color: #F0EDE6;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 400;
        opacity: 0;
        transition: opacity 0.15s ease;
        z-index: 10;
        max-width: 300px;
        white-space: normal;
        border: 1px solid rgba(255,255,255,0.08);
        backdrop-filter: blur(12px);
        box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        line-height: 1.4;
    }
    .tg-tooltip strong { color: #FF8B5E; font-weight: 600; }
    .tg-tooltip--visible { opacity: 1; }

    /* Reset button */
    .tg-reset {
        position: absolute;
        bottom: 24px;
        right: 24px;
        padding: 10px 20px;
        background: rgba(255,255,255,0.08);
        color: #C8C5BD;
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        font-size: 12px;
        font-family: 'DM Sans', system-ui, sans-serif;
        font-weight: 500;
        cursor: pointer;
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 5;
        backdrop-filter: blur(8px);
    }
    .tg-reset--visible { opacity: 1; }
    .tg-reset:hover { background: rgba(255,255,255,0.12); color: #F0EDE6; }

    /* Legend */
    .tg-legend {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(17, 19, 24, 0.85);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 12px;
        padding: 16px 20px;
        font-size: 11px;
        z-index: 5;
        backdrop-filter: blur(12px);
    }
    .tg-legend-title {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 500;
        font-size: 9px;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #5A574F;
        margin-bottom: 10px;
    }
    .tg-legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 6px 0;
        color: #8A8780;
        font-size: 11px;
    }
    .tg-legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
        box-shadow: 0 0 6px currentColor;
    }
    .tg-legend-sep {
        border: none;
        border-top: 1px solid rgba(255,255,255,0.05);
        margin: 10px 0;
    }

    /* Node count badge */
    .tg-count-badge {
        position: absolute;
        bottom: 24px;
        left: 24px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 11px;
        color: #3D3B36;
        z-index: 5;
    }
    .tg-count-badge span { color: #5A574F; }

    /* Mobile */
    @media (max-width: 768px) {
        .tg-wrap { flex-direction: column; height: auto; }
        .tg-sidebar { width: 100%; max-height: 300px; border-right: none; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .tg-viz { height: 70vh; }
        .tg-legend { display: none; }
    }
</style>

<div class="tg-wrap">
    <aside class="tg-sidebar">
        <div class="tg-sidebar-inner">
            <p class="tg-eyebrow">Okosystem</p>
            <h2><strong>BIM Verdi</strong> Nettverksvisning</h2>
            <p class="tg-subtitle">Alle temagrupper og tilknyttede entities. Klikk en node for aa utforske koblinger. Entities som deles mellom grupper skaper broer.</p>

            <div class="tg-stats">
                <div class="tg-stat-pill"><strong id="tg-count-nodes">0</strong> noder</div>
                <div class="tg-stat-pill"><strong id="tg-count-links">0</strong> koblinger</div>
                <div class="tg-stat-pill"><strong><?php echo $stats['temagruppe']; ?></strong> tema</div>
            </div>

            <div class="tg-section-label">Temagrupper</div>
            <div class="tg-filters" id="tg-filters-tg"></div>

            <div class="tg-section-label">Entitytyper</div>
            <div class="tg-filters" id="tg-filters-type"></div>

            <div class="tg-section-label">Sok</div>
            <input type="search" class="tg-search" id="tg-search" placeholder="Foretak, verktoy, artikkel...">
            <p class="tg-hint">Klikk node = vis koblinger &middot; Rull = zoom &middot; Dra = flytt</p>

            <hr class="tg-hr">

            <div id="tg-details">
                <div class="tg-card">
                    <h3>Utforsk nettverket</h3>
                    <p class="tg-card-desc">Klikk en farget temagruppe-node for aa se alle tilknyttede foretak, arrangementer, artikler og kunnskapskilder. Klikk en liten entity-node for aa se hvilke temagrupper den tilhorer.</p>
                </div>
            </div>
        </div>
    </aside>

    <main class="tg-viz" id="tg-viz">
        <div class="tg-tooltip" id="tg-tooltip"></div>
        <button class="tg-reset" id="tg-reset">Tilbakestill visning</button>

        <div class="tg-legend">
            <div class="tg-legend-title">Temagrupper</div>
            <?php foreach ($tg_colors as $name => $color): ?>
            <div class="tg-legend-item"><span class="tg-legend-dot" style="background:<?php echo $color; ?>; color:<?php echo $color; ?>"></span> <?php echo esc_html($name); ?></div>
            <?php endforeach; ?>
            <hr class="tg-legend-sep">
            <div class="tg-legend-title">Entities</div>
            <div class="tg-legend-item"><svg width="8" height="8"><circle cx="4" cy="4" r="3.5" fill="#5B8DBE"/></svg> Foretak</div>
            <div class="tg-legend-item"><svg width="8" height="8"><rect width="7" height="7" x="0.5" y="0.5" rx="1.5" fill="#6AAF6A"/></svg> Verktoy</div>
            <div class="tg-legend-item"><svg width="10" height="10"><polygon points="5,1 9,5 5,9 1,5" fill="#D4915E"/></svg> Arrangement</div>
            <div class="tg-legend-item"><svg width="8" height="8"><circle cx="4" cy="4" r="3.5" fill="#9B72B0"/></svg> Kunnskapskilde</div>
            <div class="tg-legend-item"><svg width="8" height="8"><rect width="7" height="7" x="0.5" y="0.5" rx="1.5" fill="#C96B6B"/></svg> Artikkel</div>
        </div>

        <div class="tg-count-badge">
            <span id="tg-badge-nodes">0</span> noder &middot; <span id="tg-badge-links">0</span> koblinger
        </div>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.9.0/d3.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function() {
    'use strict';

    var DATA = <?php echo $graph_json; ?>;
    var TG_COLORS = <?php echo $tg_colors_json; ?>;

    var TYPE_COLORS = {
        foretak: '#5B8DBE', verktoy: '#6AAF6A',
        kunnskapskilde: '#9B72B0', arrangement: '#D4915E', artikkel: '#C96B6B'
    };
    var TYPE_LABELS = {
        temagruppe: 'Temagruppe', foretak: 'Foretak', verktoy: 'Verktoy',
        kunnskapskilde: 'Kunnskapskilde', arrangement: 'Arrangement', artikkel: 'Artikkel'
    };
    var TYPE_SHAPES = {
        temagruppe: 'circle', foretak: 'circle', verktoy: 'rect',
        kunnskapskilde: 'circle', arrangement: 'diamond', artikkel: 'rect'
    };

    function getNodeColor(d) {
        if (d.type === 'temagruppe') return d.tgColor || TG_COLORS[d.label] || '#FF8B5E';
        return TYPE_COLORS[d.type] || '#666';
    }

    /* Stats */
    document.getElementById('tg-count-nodes').textContent = DATA.nodes.length;
    document.getElementById('tg-count-links').textContent = DATA.links.length;
    document.getElementById('tg-badge-nodes').textContent = DATA.nodes.length;
    document.getElementById('tg-badge-links').textContent = DATA.links.length;

    /* Connection counts */
    var connCount = {};
    DATA.nodes.forEach(function(n) { connCount[n.id] = 0; });
    DATA.links.forEach(function(l) {
        connCount[l.source] = (connCount[l.source] || 0) + 1;
        connCount[l.target] = (connCount[l.target] || 0) + 1;
    });
    DATA.nodes.forEach(function(n) { n.connCount = connCount[n.id] || 0; });

    /* Adjacency map */
    var neighbors = {};
    DATA.nodes.forEach(function(n) { neighbors[n.id] = new Set(); });
    DATA.links.forEach(function(l) {
        neighbors[l.source].add(l.target);
        neighbors[l.target].add(l.source);
    });

    /* ═══ Filter state ═══ */
    var activeTGs = new Set();
    var activeTypes = new Set(Object.keys(TYPE_COLORS));
    activeTypes.add('temagruppe');

    /* Temagruppe filter chips */
    var tgFilterEl = document.getElementById('tg-filters-tg');
    var tgNames = DATA.nodes.filter(function(n) { return n.type === 'temagruppe'; }).map(function(n) { return n.label; });
    tgNames.forEach(function(name) {
        activeTGs.add(name);
        var color = TG_COLORS[name] || '#999';
        var chip = document.createElement('label');
        chip.className = 'tg-chip tg-chip--active';
        chip.style.color = color;
        chip.innerHTML = '<input type="checkbox" value="' + name + '" checked><span class="chip-dot" style="background:' + color + '"></span>' + name;
        chip.querySelector('input').addEventListener('change', function(e) {
            if (e.target.checked) { activeTGs.add(name); chip.className = 'tg-chip tg-chip--active'; }
            else { activeTGs.delete(name); chip.className = 'tg-chip tg-chip--inactive'; }
            updateVisibility();
        });
        tgFilterEl.appendChild(chip);
    });

    /* Entity type filter chips */
    var typeFilterEl = document.getElementById('tg-filters-type');
    Object.keys(TYPE_COLORS).forEach(function(type) {
        if (!DATA.nodes.some(function(n) { return n.type === type; })) return;
        var color = TYPE_COLORS[type];
        var chip = document.createElement('label');
        chip.className = 'tg-chip tg-chip--active';
        chip.style.color = color;
        var dotClass = TYPE_SHAPES[type] === 'rect' ? 'chip-dot chip-dot--rect' : 'chip-dot';
        chip.innerHTML = '<input type="checkbox" value="' + type + '" checked><span class="' + dotClass + '" style="background:' + color + '"></span>' + TYPE_LABELS[type];
        chip.querySelector('input').addEventListener('change', function(e) {
            if (e.target.checked) { activeTypes.add(type); chip.className = 'tg-chip tg-chip--active'; }
            else { activeTypes.delete(type); chip.className = 'tg-chip tg-chip--inactive'; }
            updateVisibility();
        });
        typeFilterEl.appendChild(chip);
    });

    /* ═══ D3 Setup ═══ */
    var vizEl = document.getElementById('tg-viz');
    var width = vizEl.clientWidth;
    var height = vizEl.clientHeight;
    var svg = d3.select('#tg-viz').append('svg').attr('width', width).attr('height', height);

    var defs = svg.append('defs');

    /* Subtle glow filter for temagruppe nodes */
    var glowFilter = defs.append('filter').attr('id', 'tg-glow').attr('x', '-50%').attr('y', '-50%').attr('width', '200%').attr('height', '200%');
    glowFilter.append('feGaussianBlur').attr('stdDeviation', '4').attr('result', 'coloredBlur');
    var feMerge = glowFilter.append('feMerge');
    feMerge.append('feMergeNode').attr('in', 'coloredBlur');
    feMerge.append('feMergeNode').attr('in', 'SourceGraphic');

    svg.call(d3.zoom().scaleExtent([0.15, 5]).on('zoom', function(e) { g.attr('transform', e.transform); }));

    var g = svg.append('g');

    var link = g.selectAll('.tg-link')
        .data(DATA.links).enter().append('line')
        .attr('class', 'tg-link')
        .attr('stroke', function(d) {
            var sNode = DATA.nodes.find(function(n) { return n.id === (typeof d.source === 'object' ? d.source.id : d.source); });
            return sNode ? getNodeColor(sNode) : '#333';
        })
        .attr('stroke-width', 0.8)
        .attr('stroke-opacity', 0.12);

    var node = g.selectAll('.tg-node')
        .data(DATA.nodes).enter().append('g')
        .attr('class', function(d) {
            return 'tg-node' + (d.type === 'temagruppe' ? ' tg-node--tg' : ' tg-node--entity');
        })
        .call(d3.drag().on('start', dragstarted).on('drag', dragged).on('end', dragended));

    function nodeRadius(d) {
        if (d.type === 'temagruppe') return Math.max(22, 18 + d.connCount * 0.35);
        return Math.max(3.5, 3 + d.connCount * 0.8);
    }

    /* Draw shapes */
    node.each(function(d) {
        var el = d3.select(this);
        var color = getNodeColor(d);
        var shape = TYPE_SHAPES[d.type] || 'circle';

        if (d.type === 'temagruppe') {
            el.append('circle')
                .attr('r', nodeRadius(d))
                .attr('fill', color)
                .attr('filter', 'url(#tg-glow)')
                .attr('opacity', 0.9);
        } else if (shape === 'rect') {
            var size = Math.max(6, 5 + d.connCount * 1.5);
            el.append('rect')
                .attr('width', size).attr('height', size)
                .attr('x', -size / 2).attr('y', -size / 2)
                .attr('fill', color)
                .attr('opacity', 0.7);
        } else if (shape === 'diamond') {
            var r = Math.max(4, 3 + d.connCount * 1);
            el.append('polygon')
                .attr('points', '0,' + (-r) + ' ' + r + ',0 0,' + r + ' ' + (-r) + ',0')
                .attr('fill', color)
                .attr('opacity', 0.7);
        } else {
            el.append('circle')
                .attr('r', nodeRadius(d))
                .attr('fill', color)
                .attr('opacity', 0.7);
        }
    });

    /* Labels */
    node.append('text')
        .text(function(d) { return d.label; })
        .attr('x', function(d) {
            if (d.type === 'temagruppe') return nodeRadius(d) + 6;
            if (TYPE_SHAPES[d.type] === 'rect') return Math.max(6, 5 + d.connCount * 1.5) / 2 + 4;
            return nodeRadius(d) + 4;
        })
        .attr('y', function(d) { return d.type === 'temagruppe' ? 5 : 3; })
        .attr('font-size', function(d) { return d.type === 'temagruppe' ? '13px' : '9px'; })
        .attr('font-weight', function(d) { return d.type === 'temagruppe' ? '600' : '400'; })
        .attr('fill', function(d) { return d.type === 'temagruppe' ? '#F0EDE6' : 'rgba(240,237,230,0.8)'; });

    /* ═══ Tooltip ═══ */
    var tooltipEl = document.getElementById('tg-tooltip');
    node.on('mouseenter', function(event, d) {
        var html = '<strong>' + d.label + '</strong><br>' + TYPE_LABELS[d.type] + ' &middot; ' + d.connCount + ' koblinger';
        if (d.desc && d.desc !== d.label) html += '<br><span style="color:#7A776E">' + d.desc + '</span>';
        tooltipEl.innerHTML = html;
        tooltipEl.classList.add('tg-tooltip--visible');
    }).on('mousemove', function(event) {
        var rect = vizEl.getBoundingClientRect();
        tooltipEl.style.left = (event.clientX - rect.left + 16) + 'px';
        tooltipEl.style.top = (event.clientY - rect.top - 10) + 'px';
    }).on('mouseleave', function() {
        tooltipEl.classList.remove('tg-tooltip--visible');
    });

    /* ═══ Click: Neighbor Highlighting ═══ */
    var selectedNode = null;
    var resetBtn = document.getElementById('tg-reset');

    node.on('click', function(event, d) {
        event.stopPropagation();
        selectedNode = d;
        highlightNeighbors(d);
        showDetails(d);
        resetBtn.classList.add('tg-reset--visible');
    });

    svg.on('click', function() { clearHighlight(); });
    resetBtn.addEventListener('click', function() { clearHighlight(); });

    function highlightNeighbors(d) {
        var neighborSet = neighbors[d.id];
        node.classed('tg-node--dimmed', function(n) { return n.id !== d.id && !neighborSet.has(n.id); });
        node.classed('tg-node--highlighted', function(n) { return neighborSet.has(n.id); });
        node.classed('tg-node--selected', function(n) { return n.id === d.id; });
        link.classed('tg-link--dimmed', function(l) { return l.source.id !== d.id && l.target.id !== d.id; });
        link.classed('tg-link--highlighted', function(l) { return l.source.id === d.id || l.target.id === d.id; });
    }

    function clearHighlight() {
        selectedNode = null;
        node.classed('tg-node--dimmed', false).classed('tg-node--highlighted', false).classed('tg-node--selected', false);
        link.classed('tg-link--dimmed', false).classed('tg-link--highlighted', false);
        resetBtn.classList.remove('tg-reset--visible');
        document.getElementById('tg-details').innerHTML =
            '<div class="tg-card"><h3>Utforsk nettverket</h3>' +
            '<p class="tg-card-desc">Klikk en farget temagruppe-node for aa se alle tilknyttede entities.</p></div>';
    }

    /* ═══ Simulation ═══ */
    var tgNodes = DATA.nodes.filter(function(n) { return n.type === 'temagruppe'; });
    var tgPositions = {};
    tgNodes.forEach(function(n, i) {
        var angle = (i / tgNodes.length) * 2 * Math.PI - Math.PI / 2;
        tgPositions[n.id] = {
            x: width / 2 + width * 0.32 * Math.cos(angle),
            y: height / 2 + height * 0.32 * Math.sin(angle)
        };
    });

    var entityTG = {};
    DATA.links.forEach(function(l) {
        var src = typeof l.source === 'object' ? l.source.id : l.source;
        var tgt = typeof l.target === 'object' ? l.target.id : l.target;
        if (src.startsWith('tg_') && !entityTG[tgt]) entityTG[tgt] = src;
        if (tgt.startsWith('tg_') && !entityTG[src]) entityTG[src] = tgt;
    });

    var sim = d3.forceSimulation(DATA.nodes)
        .force('link', d3.forceLink(DATA.links).id(function(d) { return d.id; }).distance(function(d) {
            var isTG = d.source.type === 'temagruppe' || d.target.type === 'temagruppe';
            return isTG ? 100 : 40;
        }).strength(0.2))
        .force('charge', d3.forceManyBody().strength(function(d) {
            return d.type === 'temagruppe' ? -800 : -60;
        }))
        .force('center', d3.forceCenter(width / 2, height / 2))
        .force('collide', d3.forceCollide().radius(function(d) {
            if (d.type === 'temagruppe') return nodeRadius(d) + 40;
            return nodeRadius(d) + 4;
        }))
        .force('tgX', d3.forceX(function(d) {
            if (d.type === 'temagruppe') return tgPositions[d.id] ? tgPositions[d.id].x : width / 2;
            var tgId = entityTG[d.id];
            return tgId && tgPositions[tgId] ? tgPositions[tgId].x : width / 2;
        }).strength(function(d) {
            return d.type === 'temagruppe' ? 0.2 : 0.04;
        }))
        .force('tgY', d3.forceY(function(d) {
            if (d.type === 'temagruppe') return tgPositions[d.id] ? tgPositions[d.id].y : height / 2;
            var tgId = entityTG[d.id];
            return tgId && tgPositions[tgId] ? tgPositions[tgId].y : height / 2;
        }).strength(function(d) {
            return d.type === 'temagruppe' ? 0.2 : 0.04;
        }));

    sim.on('tick', function() {
        link.attr('x1', function(d) { return d.source.x; }).attr('y1', function(d) { return d.source.y; })
            .attr('x2', function(d) { return d.target.x; }).attr('y2', function(d) { return d.target.y; });
        node.attr('transform', function(d) { return 'translate(' + d.x + ',' + d.y + ')'; });
    });

    function dragstarted(event, d) { if (!event.active) sim.alphaTarget(0.3).restart(); d.fx = d.x; d.fy = d.y; }
    function dragged(event, d) { d.fx = event.x; d.fy = event.y; }
    function dragended(event, d) { if (!event.active) sim.alphaTarget(0); }

    /* ═══ Filter ═══ */
    function updateVisibility() {
        var activeTGIds = new Set();
        DATA.nodes.forEach(function(n) {
            if (n.type === 'temagruppe' && activeTGs.has(n.label)) activeTGIds.add(n.id);
        });

        node.classed('hidden', function(d) {
            if (d.type === 'temagruppe') return !activeTGs.has(d.label);
            if (!activeTypes.has(d.type)) return true;
            var neighborSet = neighbors[d.id];
            var hasActiveTG = false;
            neighborSet.forEach(function(nId) { if (activeTGIds.has(nId)) hasActiveTG = true; });
            return !hasActiveTG;
        });

        link.classed('hidden', function(d) {
            var sNode = d.source; var tNode = d.target;
            if (sNode.type === 'temagruppe' && !activeTGs.has(sNode.label)) return true;
            if (tNode.type === 'temagruppe' && !activeTGs.has(tNode.label)) return true;
            if (sNode.type !== 'temagruppe' && !activeTypes.has(sNode.type)) return true;
            if (tNode.type !== 'temagruppe' && !activeTypes.has(tNode.type)) return true;
            return false;
        });
    }

    /* Search */
    document.getElementById('tg-search').addEventListener('input', function() {
        var q = this.value.toLowerCase();
        if (!q) { clearHighlight(); return; }
        node.classed('tg-node--dimmed', function(d) {
            return (d.label.toLowerCase() + ' ' + (d.desc || '').toLowerCase()).indexOf(q) === -1;
        });
        node.classed('tg-node--highlighted', function(d) {
            return (d.label.toLowerCase() + ' ' + (d.desc || '').toLowerCase()).indexOf(q) > -1;
        });
        link.classed('tg-link--dimmed', true);
    });

    /* ═══ Detail card ═══ */
    function showDetails(d) {
        var wrap = document.getElementById('tg-details');
        var neighborSet = neighbors[d.id];
        var neighborArr = Array.from(neighborSet);

        var urlHtml = d.url && d.url !== '#'
            ? '<a class="tg-card-link-btn" href="' + d.url + '" target="_blank">Se side &rarr;</a>'
            : '';

        /* Group neighbors by type */
        var grouped = {};
        neighborArr.forEach(function(nId) {
            var nNode = DATA.nodes.find(function(n) { return n.id === nId; });
            if (!nNode) return;
            if (!grouped[nNode.type]) grouped[nNode.type] = [];
            grouped[nNode.type].push(nNode);
        });

        var neighborsHtml = '';
        Object.keys(grouped).forEach(function(type) {
            neighborsHtml += '<div class="tg-card-neighbors-label">' + TYPE_LABELS[type] + ' (' + grouped[type].length + ')</div><div class="tg-card-neighbors">';
            grouped[type].forEach(function(nNode) {
                neighborsHtml += '<span class="tg-neighbor" data-id="' + nNode.id + '">' + nNode.label + '</span>';
            });
            neighborsHtml += '</div>';
        });

        wrap.innerHTML =
            '<div class="tg-card tg-card--highlight">' +
            '<span class="tg-card-type" style="background:' + getNodeColor(d) + '">' + TYPE_LABELS[d.type] + '</span>' +
            '<h3>' + d.label + '</h3>' +
            (d.desc && d.desc !== d.label ? '<p class="tg-card-desc">' + d.desc + '</p>' : '') +
            '<div class="tg-card-meta"><strong>' + d.connCount + '</strong> koblinger</div>' +
            urlHtml +
            neighborsHtml +
            '</div>';

        wrap.querySelectorAll('.tg-neighbor').forEach(function(el) {
            el.addEventListener('click', function() {
                var targetNode = DATA.nodes.find(function(n) { return n.id === el.getAttribute('data-id'); });
                if (targetNode) { highlightNeighbors(targetNode); showDetails(targetNode); }
            });
        });
    }

    /* Resize */
    window.addEventListener('resize', function() {
        var w = vizEl.clientWidth, h = vizEl.clientHeight;
        svg.attr('width', w).attr('height', h);
        sim.force('center', d3.forceCenter(w / 2, h / 2));
        sim.alpha(0.3).restart();
    });
})();
</script>
