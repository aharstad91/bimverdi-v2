<?php
/**
 * Demo: Vertical Ecosystem Flow
 *
 * Temagruppe at top center, four columns below:
 * Foretak | Verktoy | Kunnskapskilder | Arrangementer
 * with flowing stream connections from temagruppe down to each entity.
 *
 * Loaded via get_template_part() from single-demo.php
 */

if (!defined('ABSPATH')) exit;

// ─── Data Loading (identical to okosystem-flyt.php) ────────────────────────

$ecosystem_data = [];
$using_mock = false;

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
    $term = get_term_by('name', $tg_title, 'temagruppe');
    if (!$term) {
        $term = get_term_by('slug', sanitize_title($tg_title), 'temagruppe');
    }

    if ($term) {
        $foretak_posts = get_posts([
            'post_type' => 'foretak', 'posts_per_page' => 20, 'post_status' => 'publish',
            'tax_query' => [['taxonomy' => 'temagruppe', 'field' => 'term_id', 'terms' => $term->term_id]],
        ]);
        $verktoy_posts = get_posts([
            'post_type' => 'verktoy', 'posts_per_page' => 30, 'post_status' => 'publish',
            'tax_query' => [['taxonomy' => 'temagruppe', 'field' => 'term_id', 'terms' => $term->term_id]],
        ]);
        $kunnskap_posts = get_posts([
            'post_type' => 'kunnskapskilde', 'posts_per_page' => 15, 'post_status' => 'publish',
            'tax_query' => [['taxonomy' => 'temagruppe', 'field' => 'term_id', 'terms' => $term->term_id]],
        ]);
        $arrangement_posts = get_posts([
            'post_type' => 'arrangement', 'posts_per_page' => 15, 'post_status' => 'publish',
            'tax_query' => [['taxonomy' => 'temagruppe', 'field' => 'term_id', 'terms' => $term->term_id]],
        ]);

        $foretak_data = [];
        foreach ($foretak_posts as $f) {
            $foretak_data[] = ['id' => 'foretak-' . $f->ID, 'label' => $f->post_title, 'url' => get_permalink($f->ID)];
        }
        $verktoy_data = [];
        foreach ($verktoy_posts as $v) {
            $verktoy_data[] = ['id' => 'verktoy-' . $v->ID, 'label' => $v->post_title, 'url' => get_permalink($v->ID)];
        }
        $kunnskap_data = [];
        foreach ($kunnskap_posts as $k) {
            $kunnskap_data[] = ['id' => 'kunnskap-' . $k->ID, 'label' => $k->post_title, 'url' => get_permalink($k->ID)];
        }
        $arrangement_data = [];
        foreach ($arrangement_posts as $a) {
            $arrangement_data[] = ['id' => 'arrangement-' . $a->ID, 'label' => $a->post_title, 'url' => get_permalink($a->ID)];
        }

        $total = count($foretak_data) + count($verktoy_data) + count($kunnskap_data) + count($arrangement_data);
        if ($total > 0) {
            $ecosystem_data = [
                'temagruppe'      => ['id' => 'tg-' . $tg->ID, 'label' => $tg_title, 'url' => get_permalink($tg->ID)],
                'foretak'         => $foretak_data,
                'verktoy'         => $verktoy_data,
                'kunnskapskilder' => $kunnskap_data,
                'arrangementer'   => $arrangement_data,
            ];
        }
    }
}

if (empty($ecosystem_data)) {
    $using_mock = true;
    $ecosystem_data = [
        'temagruppe' => ['id' => 'tg-1', 'label' => 'ByggesaksBIM', 'url' => '#'],
        'foretak' => [
            ['id' => 'foretak-1', 'label' => 'Multiconsult', 'url' => '#'],
            ['id' => 'foretak-2', 'label' => 'Norconsult', 'url' => '#'],
            ['id' => 'foretak-3', 'label' => 'Ramboll', 'url' => '#'],
            ['id' => 'foretak-4', 'label' => 'Sweco', 'url' => '#'],
            ['id' => 'foretak-5', 'label' => 'Asplan Viak', 'url' => '#'],
        ],
        'verktoy' => [
            ['id' => 'verktoy-1', 'label' => 'Solibri', 'url' => '#'],
            ['id' => 'verktoy-2', 'label' => 'Revit', 'url' => '#'],
            ['id' => 'verktoy-3', 'label' => 'BIMcollab', 'url' => '#'],
        ],
        'kunnskapskilder' => [
            ['id' => 'kunnskap-1', 'label' => 'ISO 19650 Veileder', 'url' => '#'],
            ['id' => 'kunnskap-2', 'label' => 'BIM-manual 2.0', 'url' => '#'],
            ['id' => 'kunnskap-3', 'label' => 'IFC-standarden', 'url' => '#'],
            ['id' => 'kunnskap-4', 'label' => 'POFIN', 'url' => '#'],
        ],
        'arrangementer' => [
            ['id' => 'arrangement-1', 'label' => 'ByggesaksBIM Workshop', 'url' => '#'],
            ['id' => 'arrangement-2', 'label' => 'BIM i saksbehandling', 'url' => '#'],
            ['id' => 'arrangement-3', 'label' => 'Digitalt temagruppemote', 'url' => '#'],
        ],
    ];
}

$json_data = wp_json_encode($ecosystem_data);

// ─── Hero data from temagruppe ──────────────────────────────────────────────
$hero = ['title' => 'BIMtech', 'description' => '', 'fagansvarlig' => null, 'tg_url' => '#'];
if (!empty($theme_groups)) {
    $tg_id = $theme_groups[0]->ID;
    $hero['title'] = $theme_groups[0]->post_title;
    $hero['tg_url'] = get_permalink($tg_id);

    // Description: kort_beskrivelse ACF field or post content
    $kort = get_field('kort_beskrivelse', $tg_id);
    if ($kort) {
        $hero['description'] = $kort;
    } else {
        $content = get_the_content(null, false, $tg_id);
        if ($content) {
            $hero['description'] = wp_strip_all_tags(apply_filters('the_content', $content));
        }
    }

    // Fagansvarlig
    $fa_navn = get_field('fagansvarlig_navn', $tg_id);
    if ($fa_navn) {
        $fa_tittel = get_field('fagansvarlig_tittel', $tg_id);
        $fa_bilde_id = get_field('fagansvarlig_bilde', $tg_id);
        $fa_linkedin = get_field('fagansvarlig_linkedin', $tg_id);
        $fa_bilde = $fa_bilde_id ? wp_get_attachment_image_url($fa_bilde_id, 'thumbnail') : null;
        $parts = explode(' ', $fa_navn);
        $initials = count($parts) >= 2
            ? strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1))
            : strtoupper(substr($fa_navn, 0, 2));

        $hero['fagansvarlig'] = [
            'navn' => $fa_navn,
            'tittel' => $fa_tittel,
            'bilde' => $fa_bilde,
            'linkedin' => $fa_linkedin,
            'initials' => $initials,
        ];
    }
}
?>

<main class="bv-ev" style="background:#FAFAF9;min-height:100vh">

<style>
.bv-ev { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1A1A1A; }

/* Header */
.bv-ev-header { max-width: 1400px; margin: 0 auto; padding: 2rem 1.5rem 0; }
.bv-ev-back { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.875rem; color: #FF8B5E; text-decoration: none; }
.bv-ev-back:hover { opacity: 0.7; }
.bv-ev-back svg { width: 1rem; height: 1rem; }
.bv-ev-title { font-size: 2rem; font-weight: 700; margin: 1rem 0 0.25rem; line-height: 1.2; }
.bv-ev-subtitle { font-size: 1rem; color: #5A5A5A; margin: 0 0 0.75rem; max-width: 700px; }
.bv-ev-mock { display: inline-block; background: #FFF3CD; color: #856404; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.75rem; border-radius: 9999px; margin-bottom: 0.5rem; }

/* SVG area */
.bv-ev-viz { max-width: 1400px; margin: 0 auto; padding: 1rem 1.5rem 0; position: relative; }
#bv-ev-svg { display: block; width: 100%; }

/* Nodes */
.bv-ev-node { cursor: pointer; }
.bv-ev-node rect { rx: 10; ry: 10; transition: filter 0.3s, opacity 0.3s; }
.bv-ev-node rect:hover { filter: brightness(1.08) drop-shadow(0 4px 16px rgba(0,0,0,0.15)); }
.bv-ev-node text { font-size: 12px; font-weight: 500; fill: #fff; pointer-events: none; }
.bv-ev-node.t-tg rect { fill: #FF8B5E; }
.bv-ev-node.t-foretak rect { fill: #3B82F6; }
.bv-ev-node.t-verktoy rect { fill: #8B5CF6; }
.bv-ev-node.t-kunnskap rect { fill: #10B981; }
.bv-ev-node.t-arrangement rect { fill: #F59E0B; }

/* Temagruppe hero node */
.bv-ev-node.t-tg rect { filter: drop-shadow(0 4px 20px rgba(255,139,94,0.35)); }

/* Column headers */
.bv-ev-col-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }

/* Links */
.bv-ev-link { fill: none; stroke-linecap: round; transition: opacity 0.4s, stroke-width 0.3s; }
.bv-ev-link-flow { fill: none; stroke-linecap: round; stroke-dasharray: 6 10; animation: bv-ev-flow 1.5s linear infinite; pointer-events: none; }
@keyframes bv-ev-flow { to { stroke-dashoffset: -32; } }

/* Dim/highlight states */
.bv-ev-dimmed .bv-ev-link { opacity: 0.04; }
.bv-ev-dimmed .bv-ev-link-flow { opacity: 0; }
.bv-ev-dimmed .bv-ev-node rect { opacity: 0.12; }
.bv-ev-dimmed .bv-ev-node text { opacity: 0.12; }
.bv-ev-dimmed .bv-ev-col-label { opacity: 0.2; }
.bv-ev-dimmed .bv-ev-link.hl { opacity: 0.6; stroke-width: 4px !important; }
.bv-ev-dimmed .bv-ev-link-flow.hl { opacity: 0.4; }
.bv-ev-dimmed .bv-ev-node.hl rect { opacity: 1; filter: drop-shadow(0 4px 16px rgba(0,0,0,0.18)); }
.bv-ev-dimmed .bv-ev-node.hl text { opacity: 1; }

/* Detail panel */
.bv-ev-detail {
    position: absolute; top: 80px; right: 1.5rem; width: 280px;
    background: #fff; border: 1px solid #E7E5E4; border-radius: 12px;
    padding: 1.25rem; box-shadow: 0 8px 32px rgba(0,0,0,0.08);
    opacity: 0; transform: translateY(8px) scale(0.97);
    pointer-events: none; transition: opacity 0.3s, transform 0.3s; z-index: 10;
}
.bv-ev-detail.open { opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }
.bv-ev-detail-x { position: absolute; top: 0.75rem; right: 0.75rem; width: 24px; height: 24px; border: none; background: none; cursor: pointer; color: #5A5A5A; padding: 0; display: flex; align-items: center; justify-content: center; }
.bv-ev-detail-x:hover { color: #1A1A1A; }
.bv-ev-badge { display: inline-block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; padding: 0.15rem 0.5rem; border-radius: 4px; color: #fff; margin-bottom: 0.5rem; }
.bv-ev-badge.b-tg { background: #FF8B5E; }
.bv-ev-badge.b-foretak { background: #3B82F6; }
.bv-ev-badge.b-verktoy { background: #8B5CF6; }
.bv-ev-badge.b-kunnskap { background: #10B981; }
.bv-ev-badge.b-arrangement { background: #F59E0B; }
.bv-ev-detail h3 { font-size: 1.125rem; font-weight: 700; margin: 0 0 0.5rem; line-height: 1.3; padding-right: 1.5rem; }
.bv-ev-detail-conn { font-size: 13px; color: #5A5A5A; margin: 0.75rem 0; padding-top: 0.75rem; border-top: 1px solid #E7E5E4; }
.bv-ev-detail-conn ul { list-style: none; padding: 0; margin: 0.5rem 0 0; }
.bv-ev-detail-conn li { padding: 0.2rem 0; font-size: 13px; color: #1A1A1A; display: flex; align-items: center; gap: 6px; }
.bv-ev-detail-conn li .d { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.bv-ev-detail-go { display: inline-flex; align-items: center; gap: 4px; font-size: 13px; font-weight: 600; color: #FF8B5E; text-decoration: none; margin-top: 0.75rem; }
.bv-ev-detail-go:hover { opacity: 0.7; }

/* Stats */
.bv-ev-stats { max-width: 1400px; margin: 0 auto; padding: 1.5rem 1.5rem 3rem; display: flex; gap: 2rem; flex-wrap: wrap; }
.bv-ev-stat { display: flex; align-items: baseline; gap: 0.5rem; font-size: 0.875rem; color: #5A5A5A; }
.bv-ev-stat b { font-size: 1.75rem; font-weight: 800; line-height: 1; }
.bv-ev-stat b.c0 { color: #FF8B5E; }
.bv-ev-stat b.c1 { color: #3B82F6; }
.bv-ev-stat b.c2 { color: #8B5CF6; }
.bv-ev-stat b.c3 { color: #10B981; }
.bv-ev-stat b.c4 { color: #F59E0B; }

/* Mobile */
@media (max-width: 768px) {
    .bv-ev-title { font-size: 1.5rem; }
    .bv-ev-detail {
        position: fixed; top: auto; bottom: 0; left: 0; right: 0;
        width: 100%; border-radius: 16px 16px 0 0; max-height: 50vh; overflow-y: auto;
    }
    .bv-ev-stats { gap: 1rem; }
    .bv-ev-stat b { font-size: 1.375rem; }
}
</style>

<!-- Hero (matches /temagruppe/ layout) -->
<section style="background:#FAFAF8">
    <div style="max-width:1280px;margin:0 auto;padding:2rem 1rem 0">

        <!-- Breadcrumb -->
        <nav style="margin-bottom:1.5rem;font-size:0.875rem;color:#5A5A5A;display:flex;align-items:center;gap:0.5rem">
            <a href="<?php echo esc_url(home_url('/temagruppe/')); ?>" style="color:#FF8B5E;text-decoration:none">Temagrupper</a>
            <svg style="width:14px;height:14px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span style="color:#1A1A1A;font-weight:500"><?php echo esc_html($hero['title']); ?></span>
        </nav>

        <div style="display:flex;flex-wrap:wrap;gap:2rem 4rem;align-items:flex-start">

            <!-- Left: Title + Description -->
            <div style="flex:2;min-width:280px">
                <h1 style="font-size:2.25rem;font-weight:700;color:#1A1A1A;margin:0 0 1rem;line-height:1.2">
                    <?php echo esc_html($hero['title']); ?>
                </h1>
                <?php if ($hero['description']) : ?>
                <p style="font-size:1.125rem;color:#5A5A5A;line-height:1.7;max-width:640px">
                    <?php echo esc_html($hero['description']); ?>
                </p>
                <?php endif; ?>
            </div>

            <!-- Right: Fagradgiver + CTA -->
            <div style="flex:1;min-width:240px;max-width:360px">
                <?php if ($hero['fagansvarlig']) : $fa = $hero['fagansvarlig']; ?>
                <div style="display:flex;align-items:flex-start;gap:0.75rem;margin-bottom:1.5rem">
                    <?php if ($fa['bilde']) : ?>
                    <img src="<?php echo esc_url($fa['bilde']); ?>" alt="" style="width:48px;height:48px;border-radius:50%;object-fit:cover">
                    <?php else : ?>
                    <div style="width:48px;height:48px;border-radius:50%;background:#FF8B5E;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;font-size:0.875rem;flex-shrink:0">
                        <?php echo esc_html($fa['initials']); ?>
                    </div>
                    <?php endif; ?>
                    <div>
                        <p style="font-size:0.6875rem;font-weight:500;color:#888;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 0.2rem">Fagradgiver</p>
                        <p style="font-size:0.875rem;font-weight:600;color:#1A1A1A;margin:0">
                            <?php echo esc_html($fa['navn']); ?>
                            <?php if ($fa['linkedin']) : ?>
                            <a href="<?php echo esc_url($fa['linkedin']); ?>" target="_blank" rel="noopener" style="color:#0A66C2;margin-left:4px;vertical-align:middle">
                                <svg style="width:14px;height:14px;display:inline" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                            <?php endif; ?>
                        </p>
                        <?php if ($fa['tittel']) : ?>
                        <p style="font-size:0.875rem;color:#5A5A5A;margin:0.15rem 0 0"><?php echo esc_html($fa['tittel']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <a href="<?php echo esc_url(home_url('/min-side/')); ?>" style="display:block;background:#1A1A1A;color:#fff;text-align:center;padding:0.75rem 1.5rem;border-radius:8px;font-size:0.875rem;font-weight:600;text-decoration:none;transition:background 0.2s">
                    Bli med i gruppen &rarr;
                </a>
                <p style="font-size:0.75rem;color:#888;margin:0.5rem 0 0">Via din bedriftsprofil i Min Side</p>
            </div>

        </div>

    </div>
</section>

<?php if ($using_mock) : ?>
<div style="max-width:1280px;margin:0 auto;padding:1rem 1rem 0">
    <span class="bv-ev-mock">Viser demo-data</span>
</div>
<?php endif; ?>

<!-- Visualization -->
<div class="bv-ev-viz" id="bv-ev-viz">
    <svg id="bv-ev-svg"></svg>
    <div class="bv-ev-detail" id="bv-ev-detail">
        <button class="bv-ev-detail-x" id="bv-ev-close" aria-label="Lukk">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div id="bv-ev-detail-body"></div>
    </div>
</div>

<!-- Stats -->
<div class="bv-ev-stats" id="bv-ev-stats"></div>

<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
(function() {
    'use strict';

    const DATA = <?php echo $json_data; ?>;

    const COLORS = {
        tg: '#FF8B5E', foretak: '#3B82F6', verktoy: '#8B5CF6',
        kunnskap: '#10B981', arrangement: '#F59E0B'
    };
    const TYPE_LABEL = {
        tg: 'Temagruppe', foretak: 'Foretak', verktoy: 'Verktoy',
        kunnskap: 'Kunnskapskilde', arrangement: 'Arrangement'
    };
    const BADGE_CLS = { tg:'b-tg', foretak:'b-foretak', verktoy:'b-verktoy', kunnskap:'b-kunnskap', arrangement:'b-arrangement' };
    const NODE_CLS = { tg:'t-tg', foretak:'t-foretak', verktoy:'t-verktoy', kunnskap:'t-kunnskap', arrangement:'t-arrangement' };

    // ─── Build columns ───────────────────────────────────────────────
    // 4 equal columns below the temagruppe
    const columns = [
        { key: 'foretak', label: 'Foretak', color: COLORS.foretak, items: DATA.foretak },
        { key: 'verktoy', label: 'Verktoy', color: COLORS.verktoy, items: DATA.verktoy },
        { key: 'kunnskap', label: 'Kunnskapskilder', color: COLORS.kunnskap, items: DATA.kunnskapskilder },
        { key: 'arrangement', label: 'Arrangementer', color: COLORS.arrangement, items: DATA.arrangementer },
    ];

    // ─── Layout ──────────────────────────────────────────────────────
    const svg = d3.select('#bv-ev-svg');
    const container = document.getElementById('bv-ev-viz');
    const cw = container.offsetWidth || 1200;

    const pad = { top: 20, right: 20, bottom: 40, left: 20 };
    const nodeH = 38;
    const nodeGap = 10;
    const colGap = 24;
    const tgNodeW = 220;
    const tgNodeH = 48;
    const rowGap = 80; // vertical gap between temagruppe and columns

    // Usable width for 4 columns
    const usable = cw - pad.left - pad.right;
    const colW = (usable - colGap * 3) / 4;
    const nodeW = Math.min(colW - 8, 200);

    // Calculate tallest column
    const maxItems = Math.max(1, ...columns.map(c => c.items.length));
    const colHeight = maxItems * (nodeH + nodeGap) - nodeGap;

    // Total SVG height
    const tgY = pad.top;
    const colTopY = tgY + tgNodeH + rowGap;
    const colLabelY = colTopY - 16;
    const totalH = colTopY + colHeight + pad.bottom;

    svg.attr('viewBox', `0 0 ${cw} ${totalH}`)
       .attr('preserveAspectRatio', 'xMidYMid meet')
       .style('height', totalH + 'px');

    // Temagruppe position (centered)
    const tgX = (cw - tgNodeW) / 2;
    const tgCx = tgX + tgNodeW / 2;
    const tgCy = tgY + tgNodeH / 2;
    const tgBottom = tgY + tgNodeH;

    // ─── Defs ────────────────────────────────────────────────────────
    const defs = svg.append('defs');
    const shadow = defs.append('filter').attr('id', 'ev-sh').attr('x', '-20%').attr('y', '-20%').attr('width', '140%').attr('height', '140%');
    shadow.append('feDropShadow').attr('dx', 0).attr('dy', 2).attr('stdDeviation', 4).attr('flood-color', 'rgba(0,0,0,0.08)');

    // ─── Nodes & Links data ─────────────────────────────────────────
    const allNodes = [];
    const allLinks = [];
    const nodeMap = {};

    // Temagruppe node
    const tgNode = {
        id: DATA.temagruppe.id, label: DATA.temagruppe.label, url: DATA.temagruppe.url,
        type: 'tg', x: tgX, y: tgY, w: tgNodeW, h: tgNodeH,
        cx: tgCx, cy: tgCy
    };
    allNodes.push(tgNode);
    nodeMap[tgNode.id] = tgNode;

    // Column nodes
    columns.forEach((col, ci) => {
        const colX = pad.left + ci * (colW + colGap);
        const nodeX = colX + (colW - nodeW) / 2;

        col.items.forEach((item, i) => {
            const y = colTopY + i * (nodeH + nodeGap);
            const node = {
                id: item.id, label: item.label, url: item.url,
                type: col.key === 'kunnskapskilder' ? 'kunnskap' : (col.key === 'arrangementer' ? 'arrangement' : col.key),
                x: nodeX, y: y, w: nodeW, h: nodeH,
                cx: nodeX + nodeW / 2, cy: y + nodeH / 2,
                colIdx: ci
            };
            allNodes.push(node);
            nodeMap[node.id] = node;
            allLinks.push({ source: tgNode.id, target: node.id, type: node.type });
        });
    });

    // Connection map for highlight lookups
    const connMap = {};
    allLinks.forEach(l => {
        if (!connMap[l.source]) connMap[l.source] = new Set();
        if (!connMap[l.target]) connMap[l.target] = new Set();
        connMap[l.source].add(l.target);
        connMap[l.target].add(l.source);
    });

    // ─── Draw column headers ─────────────────────────────────────────
    columns.forEach((col, ci) => {
        const colX = pad.left + ci * (colW + colGap);
        svg.append('text')
            .attr('class', 'bv-ev-col-label')
            .attr('x', colX + colW / 2)
            .attr('y', colLabelY)
            .attr('text-anchor', 'middle')
            .attr('fill', col.color)
            .text(col.label + ' (' + col.items.length + ')');
    });

    // ─── Draw links ──────────────────────────────────────────────────
    const linkGroup = svg.append('g');
    const linkEls = [];

    allLinks.forEach(l => {
        const src = nodeMap[l.source];
        const tgt = nodeMap[l.target];
        if (!src || !tgt) return;

        const color = COLORS[l.type] || COLORS.tg;

        // From bottom-center of temagruppe to top-center of target
        const x0 = src.cx;
        const y0 = src.y + src.h;
        const x1 = tgt.cx;
        const y1 = tgt.y;

        // Vertical bezier: control points pull down from source and up from target
        const cpDist = (y1 - y0) * 0.45;
        const pathD = `M${x0},${y0} C${x0},${y0 + cpDist} ${x1},${y1 - cpDist} ${x1},${y1}`;

        const path = linkGroup.append('path')
            .attr('class', 'bv-ev-link')
            .attr('d', pathD)
            .attr('stroke', color)
            .attr('stroke-width', 2)
            .attr('opacity', 0.2)
            .datum(l);

        const flow = linkGroup.append('path')
            .attr('class', 'bv-ev-link-flow')
            .attr('d', pathD)
            .attr('stroke', color)
            .attr('stroke-width', 1.5)
            .attr('opacity', 0.12)
            .datum(l);

        linkEls.push({ path, flow, data: l });
    });

    // ─── Draw nodes ──────────────────────────────────────────────────
    const nodeGroup = svg.append('g');

    const nodeEls = nodeGroup.selectAll('.bv-ev-node')
        .data(allNodes)
        .join('g')
        .attr('class', d => 'bv-ev-node ' + (NODE_CLS[d.type] || ''))
        .attr('transform', d => `translate(${d.x},${d.y})`);

    nodeEls.append('rect')
        .attr('width', d => d.w)
        .attr('height', d => d.h)
        .attr('filter', 'url(#ev-sh)');

    nodeEls.append('text')
        .attr('x', d => d.w / 2)
        .attr('y', d => d.h / 2)
        .attr('text-anchor', 'middle')
        .attr('dominant-baseline', 'central')
        .text(d => {
            const max = Math.floor(d.w / 8);
            return d.label.length > max ? d.label.substring(0, max - 1) + '\u2026' : d.label;
        });

    // ─── Interaction ─────────────────────────────────────────────────
    let active = null;

    nodeEls.on('mouseenter', function(e, d) {
        if (active) return;
        highlight(d.id);
    }).on('mouseleave', function() {
        if (active) return;
        clearHL();
    }).on('click', function(e, d) {
        e.stopPropagation();
        if (active === d.id) { active = null; clearHL(); hideDetail(); return; }
        active = d.id;
        highlight(d.id);
        showDetail(d);
    });

    svg.on('click', () => { active = null; clearHL(); hideDetail(); });

    function highlight(id) {
        const conn = connMap[id] || new Set();
        const all = new Set([id, ...conn]);
        svg.classed('bv-ev-dimmed', true);
        nodeEls.classed('hl', d => all.has(d.id));
        linkEls.forEach(le => {
            const hit = le.data.source === id || le.data.target === id;
            le.path.classed('hl', hit);
            le.flow.classed('hl', hit);
        });
    }
    function clearHL() {
        svg.classed('bv-ev-dimmed', false);
        nodeEls.classed('hl', false);
        linkEls.forEach(le => { le.path.classed('hl', false); le.flow.classed('hl', false); });
    }

    // ─── Detail panel ────────────────────────────────────────────────
    const panel = document.getElementById('bv-ev-detail');
    const body = document.getElementById('bv-ev-detail-body');
    document.getElementById('bv-ev-close').addEventListener('click', e => {
        e.stopPropagation(); active = null; clearHL(); hideDetail();
    });

    function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    function showDetail(node) {
        const conn = connMap[node.id] || new Set();
        const cNodes = [...conn].map(id => nodeMap[id]).filter(Boolean);
        let html = `<span class="bv-ev-badge ${BADGE_CLS[node.type]}">${TYPE_LABEL[node.type]}</span>`;
        html += `<h3>${esc(node.label)}</h3>`;
        if (cNodes.length) {
            html += `<div class="bv-ev-detail-conn"><strong>${cNodes.length} forbindelse${cNodes.length !== 1 ? 'r' : ''}</strong><ul>`;
            cNodes.forEach(cn => {
                html += `<li><span class="d" style="background:${COLORS[cn.type]}"></span>${esc(cn.label)}</li>`;
            });
            html += '</ul></div>';
        }
        if (node.url && node.url !== '#') {
            html += `<a href="${esc(node.url)}" class="bv-ev-detail-go">Se detaljer <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>`;
        }
        body.innerHTML = html;
        panel.classList.add('open');
    }
    function hideDetail() { panel.classList.remove('open'); }

    // ─── Entry animation ─────────────────────────────────────────────
    // Temagruppe fades in first
    nodeEls.style('opacity', 0);
    nodeEls.filter(d => d.type === 'tg')
        .transition().delay(100).duration(400).ease(d3.easeCubicOut).style('opacity', 1);

    // Column nodes stagger by column then by index
    nodeEls.filter(d => d.type !== 'tg').each(function(d) {
        const colDelay = (d.colIdx || 0) * 120;
        d3.select(this)
            .transition()
            .delay(400 + colDelay + Math.random() * 150)
            .duration(450)
            .ease(d3.easeCubicOut)
            .style('opacity', 1);
    });

    // Links draw downward
    linkEls.forEach((le, i) => {
        const len = le.path.node().getTotalLength();
        le.path
            .attr('stroke-dasharray', len)
            .attr('stroke-dashoffset', len)
            .transition()
            .delay(500 + i * 25)
            .duration(700)
            .ease(d3.easeCubicOut)
            .attr('stroke-dashoffset', 0)
            .on('end', function() { d3.select(this).attr('stroke-dasharray', 'none'); });

        le.flow.style('opacity', 0)
            .transition().delay(500 + i * 25 + 500).duration(400).style('opacity', 0.12);
    });

    // ─── Stats ───────────────────────────────────────────────────────
    document.getElementById('bv-ev-stats').innerHTML = [
        { n: 1, l: 'temagruppe', c: 'c0' },
        { n: DATA.foretak.length, l: 'foretak', c: 'c1' },
        { n: DATA.verktoy.length, l: 'verktoy', c: 'c2' },
        { n: DATA.kunnskapskilder.length, l: 'kunnskapskilder', c: 'c3' },
        { n: DATA.arrangementer.length, l: 'arrangementer', c: 'c4' },
    ].map(s => `<div class="bv-ev-stat"><b class="${s.c}">${s.n}</b><span>${s.l}</span></div>`).join('');

})();
</script>

</main>
<?php get_footer(); ?>
