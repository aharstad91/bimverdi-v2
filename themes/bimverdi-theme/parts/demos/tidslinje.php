<?php
/**
 * Demo: Temagruppe Tidslinje
 *
 * Interactive horizontal timeline showing events, milestones and activities
 * for temagrupper. Uses D3.js v7 for time scales, axes, zoom and pan.
 *
 * Loaded via single-demo.php -> get_template_part('parts/demos/tidslinje')
 * Header is already loaded by the parent template.
 */

if (!defined('ABSPATH')) {
    exit;
}

// ─── Data Loading ───────────────────────────────────────────────────────────

$timeline_events = [];
$theme_groups_data = [];
$has_real_data = false;

// Lane colors for temagrupper
$lane_colors = ['#FF8B5E', '#3B82F6', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444', '#06B6D4', '#EC4899'];

// 1. Load all theme_groups
$tg_posts = get_posts([
    'post_type'      => 'theme_group',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
]);

$tg_map = []; // term_id => index
$tg_index = 0;

foreach ($tg_posts as $tg) {
    // Find matching taxonomy term
    $term = get_term_by('name', $tg->post_title, 'temagruppe');
    if (!$term) {
        $term = get_term_by('slug', sanitize_title($tg->post_title), 'temagruppe');
    }

    $term_id = $term ? $term->term_id : 'tg_' . $tg->ID;

    $theme_groups_data[] = [
        'id'    => $term_id,
        'name'  => $tg->post_title,
        'color' => $lane_colors[$tg_index % count($lane_colors)],
        'index' => $tg_index,
        'url'   => get_permalink($tg),
    ];

    $tg_map[$term_id] = $tg_index;
    $tg_index++;
}

// 2. Load all arrangementer with dates and temagruppe taxonomy
$events_query = new WP_Query([
    'post_type'      => 'arrangement',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
]);

if ($events_query->have_posts()) {
    while ($events_query->have_posts()) {
        $events_query->the_post();
        $event_id = get_the_ID();

        // Get event date from ACF
        $date_raw = get_field('arrangement_dato', $event_id);
        if (!$date_raw) {
            $date_raw = get_field('dato', $event_id);
        }
        if (!$date_raw) {
            $date_raw = get_field('startdato', $event_id);
        }
        // Fallback to post date
        if (!$date_raw) {
            $date_raw = get_the_date('Y-m-d', $event_id);
        }

        // Normalize date format
        if ($date_raw) {
            // ACF might return d/m/Y or Y-m-d or Ymd
            $timestamp = strtotime(str_replace('/', '-', $date_raw));
            if (!$timestamp) {
                // Try Ymd format
                $timestamp = strtotime(substr($date_raw, 0, 4) . '-' . substr($date_raw, 4, 2) . '-' . substr($date_raw, 6, 2));
            }
            $date_formatted = $timestamp ? date('Y-m-d', $timestamp) : null;
        } else {
            $date_formatted = null;
        }

        if (!$date_formatted) continue;

        // Get temagruppe terms
        $terms = get_the_terms($event_id, 'temagruppe');
        $event_tg_ids = [];
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $t) {
                if (isset($tg_map[$t->term_id])) {
                    $event_tg_ids[] = $t->term_id;
                }
            }
        }

        // Get event type
        $event_types = get_the_terms($event_id, 'arrangementstype');
        $event_type = ($event_types && !is_wp_error($event_types)) ? $event_types[0]->name : 'Arrangement';

        // Get location
        $location = get_field('sted', $event_id) ?: get_field('lokasjon', $event_id) ?: '';

        $timeline_events[] = [
            'id'            => $event_id,
            'title'         => get_the_title(),
            'date'          => $date_formatted,
            'type'          => $event_type,
            'url'           => get_permalink(),
            'excerpt'       => wp_trim_words(get_the_excerpt(), 25) ?: '',
            'location'      => $location,
            'temagruppe_ids' => $event_tg_ids,
        ];

        if (!empty($event_tg_ids)) {
            $has_real_data = true;
        }
    }
}
wp_reset_postdata();

// 3. Fallback mock data if no real data
if (!$has_real_data || empty($timeline_events)) {
    $mock_tg_names = ['BIM i drift', 'Digital tvilling', 'openBIM standarder', 'Baerekraft & LCA'];
    $theme_groups_data = [];
    $tg_map = [];

    foreach ($mock_tg_names as $i => $name) {
        $tid = 'mock_' . ($i + 1);
        $theme_groups_data[] = [
            'id'    => $tid,
            'name'  => $name,
            'color' => $lane_colors[$i],
            'index' => $i,
            'url'   => '#',
        ];
        $tg_map[$tid] = $i;
    }

    $mock_event_types = ['Webinar', 'Workshop', 'Fagdag', 'Konferanse', 'Nettverksmote'];
    $mock_locations = ['Oslo', 'Bergen', 'Trondheim', 'Digitalt', 'Stavanger'];
    $timeline_events = [];

    // Generate 15 mock events over 14 months (7 past, 7 future)
    $base_time = time();
    $mock_events_raw = [
        [-210, 'Kickoff: BIM i drift-gruppen',            'mock_1', 'Workshop',    'Oslo'],
        [-180, 'openBIM standarder - introduksjon',       'mock_3', 'Fagdag',      'Trondheim'],
        [-150, 'Digital tvilling pilotstudie',             'mock_2', 'Workshop',    'Bergen'],
        [-120, 'Baerekraft i byggeprosjekter',             'mock_4', 'Webinar',     'Digitalt'],
        [-100, 'Drift og forvaltning med BIM',            'mock_1', 'Fagdag',      'Oslo'],
        [-75,  'IFC og buildingSMART-oppdatering',        'mock_3', 'Konferanse',  'Stavanger'],
        [-50,  'Tvillingdata i praksis',                   'mock_2', 'Workshop',    'Digitalt'],
        [-30,  'LCA-beregninger for medlemmer',            'mock_4', 'Webinar',     'Digitalt'],
        [-10,  'BIM i drift: Erfaringsdeling',            'mock_1', 'Nettverksmote','Oslo'],
        [15,   'Digital tvilling demo-dag',                'mock_2', 'Fagdag',      'Bergen'],
        [40,   'openBIM verktoy-gjennomgang',             'mock_3', 'Workshop',    'Trondheim'],
        [65,   'Baerekraft-workshop: EPD og LCA',          'mock_4', 'Workshop',    'Oslo'],
        [90,   'FM og BIM: veien videre',                  'mock_1', 'Konferanse',  'Stavanger'],
        [130,  'Digital tvilling arskonferanse',            'mock_2', 'Konferanse',  'Oslo'],
        [170,  'openBIM standarder: IDS & IFC5',          'mock_3', 'Fagdag',      'Digitalt'],
    ];

    foreach ($mock_events_raw as $idx => $ev) {
        $event_date = date('Y-m-d', $base_time + ($ev[0] * 86400));
        $timeline_events[] = [
            'id'            => 'mock_event_' . ($idx + 1),
            'title'         => $ev[1],
            'date'          => $event_date,
            'type'          => $ev[3],
            'url'           => '#',
            'excerpt'       => 'Demo-arrangement for temagruppen ' . str_replace('mock_', '', $ev[2]) . '. Dette er eksempeldata som vises nar det ikke finnes ekte arrangementer i databasen.',
            'location'      => $ev[4],
            'temagruppe_ids' => [$ev[2]],
        ];
    }

    $has_real_data = false;
}

// 4. Calculate stats
$today = date('Y-m-d');
$total_events = count($timeline_events);
$upcoming_events = 0;
$past_events = 0;
foreach ($timeline_events as $ev) {
    if ($ev['date'] >= $today) {
        $upcoming_events++;
    } else {
        $past_events++;
    }
}
$total_tg = count($theme_groups_data);

// ─── Encode data for JS ─────────────────────────────────────────────────────

$js_events = json_encode($timeline_events, JSON_UNESCAPED_UNICODE);
$js_theme_groups = json_encode($theme_groups_data, JSON_UNESCAPED_UNICODE);
$js_today = json_encode($today);
?>

<main class="bv-tidslinje-demo" style="background:#FAFAF9; min-height:100vh;">

<!-- ─── Inline Styles ──────────────────────────────────────────────────── -->
<style>
/* Reset & base */
.bv-tidslinje-demo {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #1A1A1A;
}
.bv-tidslinje-demo *, .bv-tidslinje-demo *::before, .bv-tidslinje-demo *::after {
    box-sizing: border-box;
}

/* Header */
.tl-header {
    max-width: 1280px;
    margin: 0 auto;
    padding: 32px 24px 0;
}
.tl-back {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 14px;
    color: #FF8B5E;
    text-decoration: none;
    transition: opacity 0.2s;
}
.tl-back:hover { opacity: 0.7; }
.tl-back svg { width: 16px; height: 16px; }

.tl-title-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-top: 16px;
    margin-bottom: 8px;
}
.tl-title {
    font-size: 32px;
    font-weight: 700;
    color: #1A1A1A;
    margin: 0;
    line-height: 1.2;
}
.tl-subtitle {
    font-size: 15px;
    color: #5A5A5A;
    margin: 0 0 24px;
    max-width: 640px;
}

/* Stats */
.tl-stats {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}
.tl-stat {
    text-align: center;
}
.tl-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1A1A1A;
    line-height: 1;
}
.tl-stat-label {
    font-size: 12px;
    color: #5A5A5A;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 2px;
}

/* Mock banner */
.tl-mock-banner {
    background: #FFF7ED;
    border: 1px solid #FDBA74;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 13px;
    color: #9A3412;
    max-width: 1280px;
    margin: 16px auto 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.tl-mock-banner svg { flex-shrink: 0; }

/* Filter bar */
.tl-filters {
    max-width: 1280px;
    margin: 20px auto 0;
    padding: 0 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.tl-filter-label {
    font-size: 13px;
    font-weight: 600;
    color: #5A5A5A;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.tl-filter-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    border: 1.5px solid;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
    background: white;
}
.tl-filter-chip:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.tl-filter-chip.active {
    color: white;
}
.tl-filter-chip .chip-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.tl-filter-chip .chip-count {
    font-size: 11px;
    opacity: 0.7;
    margin-left: 2px;
}

/* Timeline container */
.tl-container {
    max-width: 1280px;
    margin: 24px auto 0;
    padding: 0 24px 48px;
}
.tl-viewport {
    position: relative;
    background: white;
    border: 1px solid #E7E5E4;
    border-radius: 12px;
    overflow: hidden;
    cursor: grab;
    min-height: 400px;
}
.tl-viewport:active { cursor: grabbing; }
.tl-viewport svg {
    display: block;
    width: 100%;
}

/* D3 axis styles */
.tl-viewport .tick line {
    stroke: #E7E5E4;
    stroke-dasharray: 2,3;
}
.tl-viewport .tick text {
    fill: #5A5A5A;
    font-size: 12px;
    font-family: inherit;
}
.tl-viewport .domain {
    stroke: #E7E5E4;
}

/* Lane labels */
.tl-lane-label {
    font-size: 13px;
    font-weight: 600;
    pointer-events: none;
}

/* Event markers */
.tl-event-marker {
    cursor: pointer;
    transition: filter 0.2s;
}
.tl-event-marker:hover {
    filter: brightness(1.1);
}
.tl-event-marker.past {
    opacity: 0.45;
}
.tl-event-marker.past:hover {
    opacity: 0.75;
}
.tl-event-marker.future .marker-glow {
    animation: tl-glow 2s ease-in-out infinite;
}

@keyframes tl-glow {
    0%, 100% { opacity: 0; r: 14; }
    50% { opacity: 0.3; r: 20; }
}

/* Today line */
.tl-today-line {
    stroke: #EF4444;
    stroke-width: 2;
    stroke-dasharray: 6,4;
}
.tl-today-pulse {
    animation: tl-pulse 2.5s ease-in-out infinite;
}
@keyframes tl-pulse {
    0%, 100% { opacity: 0.15; }
    50% { opacity: 0.4; }
}
.tl-today-label {
    fill: #EF4444;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Tooltip */
.tl-tooltip {
    position: absolute;
    pointer-events: none;
    background: white;
    border: 1px solid #E7E5E4;
    border-radius: 10px;
    padding: 14px 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    z-index: 100;
    max-width: 280px;
    opacity: 0;
    transform: translateY(4px);
    transition: opacity 0.15s, transform 0.15s;
    font-size: 13px;
    line-height: 1.5;
}
.tl-tooltip.visible {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}
.tl-tooltip-title {
    font-weight: 600;
    font-size: 14px;
    color: #1A1A1A;
    margin-bottom: 4px;
}
.tl-tooltip-date,
.tl-tooltip-type,
.tl-tooltip-location {
    font-size: 12px;
    color: #5A5A5A;
    display: flex;
    align-items: center;
    gap: 4px;
}
.tl-tooltip-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    color: white;
    margin-top: 6px;
}
.tl-tooltip-excerpt {
    margin-top: 8px;
    font-size: 12px;
    color: #5A5A5A;
    border-top: 1px solid #E7E5E4;
    padding-top: 8px;
}

/* Detail panel */
.tl-detail-panel {
    position: fixed;
    top: 0;
    right: -420px;
    width: 400px;
    max-width: 90vw;
    height: 100vh;
    background: white;
    border-left: 1px solid #E7E5E4;
    box-shadow: -8px 0 32px rgba(0,0,0,0.1);
    z-index: 200;
    transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow-y: auto;
    padding: 32px 24px;
}
.tl-detail-panel.open {
    right: 0;
}
.tl-detail-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.2);
    z-index: 199;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s;
}
.tl-detail-overlay.open {
    opacity: 1;
    pointer-events: auto;
}
.tl-detail-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1px solid #E7E5E4;
    background: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #5A5A5A;
    transition: background 0.2s;
}
.tl-detail-close:hover {
    background: #F5F5F4;
}
.tl-detail-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    color: white;
    margin-bottom: 12px;
}
.tl-detail-title {
    font-size: 22px;
    font-weight: 700;
    color: #1A1A1A;
    margin: 0 0 16px;
    line-height: 1.3;
}
.tl-detail-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 20px;
}
.tl-detail-meta-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #5A5A5A;
}
.tl-detail-meta-row svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    color: #A8A29E;
}
.tl-detail-excerpt {
    font-size: 14px;
    color: #5A5A5A;
    line-height: 1.6;
    border-top: 1px solid #E7E5E4;
    padding-top: 16px;
    margin-bottom: 24px;
}
.tl-detail-tg-list {
    list-style: none;
    padding: 0;
    margin: 0 0 24px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.tl-detail-tg-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 500;
    background: #F5F5F4;
    color: #1A1A1A;
}
.tl-detail-tg-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}
.tl-detail-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    background: #FF8B5E;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.2s;
}
.tl-detail-link:hover {
    background: #F97316;
}

/* Zoom controls */
.tl-zoom-controls {
    position: absolute;
    bottom: 16px;
    right: 16px;
    display: flex;
    gap: 4px;
    z-index: 10;
}
.tl-zoom-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid #E7E5E4;
    background: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #5A5A5A;
    font-size: 18px;
    font-weight: 500;
    transition: background 0.2s, color 0.2s;
}
.tl-zoom-btn:hover {
    background: #F5F5F4;
    color: #1A1A1A;
}

/* Legend */
.tl-legend {
    position: absolute;
    bottom: 16px;
    left: 16px;
    display: flex;
    gap: 16px;
    font-size: 11px;
    color: #5A5A5A;
    z-index: 10;
    background: rgba(255,255,255,0.9);
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid #E7E5E4;
}
.tl-legend-item {
    display: flex;
    align-items: center;
    gap: 4px;
}
.tl-legend-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

/* Mobile vertical timeline */
@media (max-width: 768px) {
    .tl-header { padding: 20px 16px 0; }
    .tl-title { font-size: 24px; }
    .tl-filters { padding: 0 16px; }
    .tl-container { padding: 0 16px 32px; }
    .tl-stats { gap: 16px; }
    .tl-stat-value { font-size: 20px; }

    /* Hide desktop timeline on mobile */
    .tl-viewport { display: none; }
    .tl-mobile-timeline { display: block !important; }
}

/* Mobile timeline */
.tl-mobile-timeline {
    display: none;
    padding-top: 8px;
}
.tl-mobile-month {
    margin-bottom: 24px;
}
.tl-mobile-month-label {
    font-size: 13px;
    font-weight: 600;
    color: #5A5A5A;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding-bottom: 8px;
    border-bottom: 1px solid #E7E5E4;
    margin-bottom: 12px;
}
.tl-mobile-event {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #F5F5F4;
    cursor: pointer;
}
.tl-mobile-event:last-child { border-bottom: none; }
.tl-mobile-dot-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 4px;
}
.tl-mobile-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}
.tl-mobile-line {
    width: 2px;
    flex: 1;
    margin-top: 4px;
    border-radius: 1px;
}
.tl-mobile-content { flex: 1; min-width: 0; }
.tl-mobile-event-title {
    font-size: 14px;
    font-weight: 600;
    color: #1A1A1A;
    margin-bottom: 2px;
}
.tl-mobile-event-meta {
    font-size: 12px;
    color: #5A5A5A;
}
.tl-mobile-event.past {
    opacity: 0.5;
}
.tl-mobile-today {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
    margin-bottom: 16px;
}
.tl-mobile-today-line {
    flex: 1;
    height: 2px;
    background: #EF4444;
    border-radius: 1px;
}
.tl-mobile-today-label {
    font-size: 11px;
    font-weight: 600;
    color: #EF4444;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
}
</style>

<!-- ─── Header ─────────────────────────────────────────────────────────── -->
<div class="tl-header">
    <a href="<?php echo get_post_type_archive_link('demo'); ?>" class="tl-back">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Alle demoer
    </a>

    <div class="tl-title-row">
        <div>
            <h1 class="tl-title">Tidslinje</h1>
        </div>
        <div class="tl-stats">
            <div class="tl-stat">
                <div class="tl-stat-value"><?php echo $total_events; ?></div>
                <div class="tl-stat-label">Arrangementer</div>
            </div>
            <div class="tl-stat">
                <div class="tl-stat-value"><?php echo $total_tg; ?></div>
                <div class="tl-stat-label">Temagrupper</div>
            </div>
            <div class="tl-stat">
                <div class="tl-stat-value"><?php echo $upcoming_events; ?></div>
                <div class="tl-stat-label">Kommende</div>
            </div>
        </div>
    </div>
    <p class="tl-subtitle">Aktivitet og arrangementer over tid for BIM Verdis temagrupper. Dra for a navigere, scroll for a zoome.</p>
</div>

<?php if (!$has_real_data) : ?>
<div class="tl-mock-banner">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    Viser eksempeldata. Koble arrangementer til temagrupper via <em>temagruppe</em>-taksonomi for a se ekte data.
</div>
<?php endif; ?>

<!-- ─── Filter Bar ─────────────────────────────────────────────────────── -->
<div class="tl-filters" id="tlFilters">
    <span class="tl-filter-label">Temagrupper:</span>
    <!-- Populated by JS -->
</div>

<!-- ─── Timeline ───────────────────────────────────────────────────────── -->
<div class="tl-container">
    <div class="tl-viewport" id="tlViewport">
        <svg id="tlSvg"></svg>
        <div class="tl-zoom-controls">
            <button class="tl-zoom-btn" id="tlZoomIn" title="Zoom inn">+</button>
            <button class="tl-zoom-btn" id="tlZoomOut" title="Zoom ut">&minus;</button>
            <button class="tl-zoom-btn" id="tlZoomReset" title="Tilbakestill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 1 3 6.7"/><path d="M3 21v-6h6"/></svg>
            </button>
        </div>
        <div class="tl-legend" id="tlLegend">
            <div class="tl-legend-item">
                <div class="tl-legend-dot" style="background:#EF4444"></div>
                I dag
            </div>
            <div class="tl-legend-item">
                <div class="tl-legend-dot" style="background:#1A1A1A; opacity:0.4"></div>
                Tidligere
            </div>
            <div class="tl-legend-item">
                <div class="tl-legend-dot" style="background:#1A1A1A"></div>
                Kommende
            </div>
        </div>
    </div>

    <!-- Mobile timeline -->
    <div class="tl-mobile-timeline" id="tlMobile"></div>

    <!-- Tooltip -->
    <div class="tl-tooltip" id="tlTooltip"></div>
</div>

<!-- ─── Detail Panel ───────────────────────────────────────────────────── -->
<div class="tl-detail-overlay" id="tlOverlay"></div>
<div class="tl-detail-panel" id="tlPanel">
    <button class="tl-detail-close" id="tlPanelClose">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <div id="tlPanelContent"></div>
</div>

<!-- ─── D3.js v7 ───────────────────────────────────────────────────────── -->
<script src="https://d3js.org/d3.v7.min.js"></script>

<script>
(function() {
    'use strict';

    // ─── Data ─────────────────────────────────────────────────────────────
    const rawEvents = <?php echo $js_events; ?>;
    const themeGroups = <?php echo $js_theme_groups; ?>;
    const todayStr = <?php echo $js_today; ?>;
    const today = new Date(todayStr + 'T12:00:00');

    // Parse dates
    const events = rawEvents.map(e => ({
        ...e,
        dateObj: new Date(e.date + 'T12:00:00'),
        isPast: e.date < todayStr,
    }));

    // Assign each event to its first temagruppe lane (for positioning)
    // Events with multiple temagrupper appear in each lane
    const eventsByLane = {};
    themeGroups.forEach(tg => { eventsByLane[tg.id] = []; });

    events.forEach(ev => {
        if (!ev.temagruppe_ids || ev.temagruppe_ids.length === 0) return;
        ev.temagruppe_ids.forEach(tid => {
            if (eventsByLane[tid]) {
                eventsByLane[tid].push(ev);
            }
        });
    });

    // Track active temagrupper (all active by default)
    const activeGroups = new Set(themeGroups.map(tg => tg.id));

    // ─── Filter Chips ─────────────────────────────────────────────────────
    const filtersEl = document.getElementById('tlFilters');

    themeGroups.forEach(tg => {
        const count = (eventsByLane[tg.id] || []).length;
        const chip = document.createElement('button');
        chip.className = 'tl-filter-chip active';
        chip.dataset.tgId = tg.id;
        chip.style.borderColor = tg.color;
        chip.style.background = tg.color;
        chip.style.color = 'white';
        chip.innerHTML = `<span class="chip-dot" style="background:white"></span>${tg.name}<span class="chip-count">(${count})</span>`;

        chip.addEventListener('click', () => {
            if (activeGroups.has(tg.id)) {
                activeGroups.delete(tg.id);
                chip.classList.remove('active');
                chip.style.background = 'white';
                chip.style.color = tg.color;
                chip.querySelector('.chip-dot').style.background = tg.color;
            } else {
                activeGroups.add(tg.id);
                chip.classList.add('active');
                chip.style.background = tg.color;
                chip.style.color = 'white';
                chip.querySelector('.chip-dot').style.background = 'white';
            }
            renderTimeline();
            renderMobileTimeline();
        });

        filtersEl.appendChild(chip);
    });

    // ─── SVG Setup ────────────────────────────────────────────────────────
    const margin = { top: 50, right: 40, bottom: 40, left: 170 };
    const laneHeight = 80;
    const markerRadius = 10;

    const viewport = document.getElementById('tlViewport');
    const svg = d3.select('#tlSvg');
    const tooltip = document.getElementById('tlTooltip');

    let width, height, innerWidth, innerHeight;
    let xScale, xAxis, zoom;

    function calcDimensions() {
        const activeArr = themeGroups.filter(tg => activeGroups.has(tg.id));
        width = viewport.clientWidth;
        const lanes = Math.max(activeArr.length, 1);
        height = margin.top + lanes * laneHeight + margin.bottom;
        innerWidth = width - margin.left - margin.right;
        innerHeight = lanes * laneHeight;
    }

    // ─── Render ───────────────────────────────────────────────────────────
    function renderTimeline() {
        svg.selectAll('*').remove();
        calcDimensions();

        svg.attr('viewBox', `0 0 ${width} ${height}`)
           .attr('width', width)
           .attr('height', height);

        const activeArr = themeGroups.filter(tg => activeGroups.has(tg.id));

        // Compute time domain
        let allDates = events
            .filter(e => e.temagruppe_ids.some(id => activeGroups.has(id)))
            .map(e => e.dateObj);
        allDates.push(today);

        if (allDates.length < 2) {
            allDates = [d3.timeMonth.offset(today, -6), d3.timeMonth.offset(today, 6)];
        }

        const minDate = d3.timeMonth.offset(d3.min(allDates), -1);
        const maxDate = d3.timeMonth.offset(d3.max(allDates), 1);

        xScale = d3.scaleTime()
            .domain([minDate, maxDate])
            .range([0, innerWidth]);

        // Clip path
        svg.append('defs').append('clipPath')
            .attr('id', 'tl-clip')
            .append('rect')
            .attr('x', 0).attr('y', 0)
            .attr('width', innerWidth)
            .attr('height', innerHeight + margin.top);

        // Main group
        const g = svg.append('g')
            .attr('transform', `translate(${margin.left},${margin.top})`);

        // Clip group for zoomable content
        const clipG = g.append('g')
            .attr('clip-path', 'url(#tl-clip)');

        // Zoomable group
        const zoomG = clipG.append('g').attr('class', 'zoom-group');

        // ─── Lanes ────────────────────────────────────────────────────────
        activeArr.forEach((tg, i) => {
            const y = i * laneHeight;

            // Lane background
            zoomG.append('rect')
                .attr('x', -margin.left)
                .attr('y', y)
                .attr('width', width * 3)
                .attr('height', laneHeight)
                .attr('fill', tg.color)
                .attr('opacity', 0.04);

            // Lane divider
            if (i > 0) {
                zoomG.append('line')
                    .attr('x1', -margin.left)
                    .attr('x2', width * 3)
                    .attr('y1', y)
                    .attr('y2', y)
                    .attr('stroke', '#E7E5E4')
                    .attr('stroke-width', 1);
            }

            // Lane label (fixed position, outside clip)
            const labelG = svg.append('g')
                .attr('transform', `translate(16, ${margin.top + y + laneHeight / 2})`);

            labelG.append('rect')
                .attr('x', -4).attr('y', -10)
                .attr('width', margin.left - 24).attr('height', 20)
                .attr('fill', '#FAFAF9')
                .attr('rx', 4)
                .attr('opacity', 0);

            labelG.append('circle')
                .attr('cx', 0).attr('cy', 0).attr('r', 5)
                .attr('fill', tg.color);

            labelG.append('text')
                .attr('x', 14).attr('y', 4)
                .attr('class', 'tl-lane-label')
                .attr('fill', '#1A1A1A')
                .text(tg.name.length > 18 ? tg.name.substring(0, 16) + '...' : tg.name);
        });

        // ─── Time Axis ───────────────────────────────────────────────────
        xAxis = d3.axisTop(xScale)
            .ticks(d3.timeMonth.every(1))
            .tickSize(-innerHeight)
            .tickFormat(d => {
                const months = ['jan', 'feb', 'mar', 'apr', 'mai', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'des'];
                const m = months[d.getMonth()];
                return d.getMonth() === 0 ? `${m} ${d.getFullYear()}` : m;
            });

        const axisG = zoomG.append('g')
            .attr('class', 'x-axis')
            .call(xAxis);

        // Year dividers
        axisG.selectAll('.tick').each(function(d) {
            if (d.getMonth() === 0) {
                d3.select(this).select('line')
                    .attr('stroke', '#D6D1C6')
                    .attr('stroke-width', 1.5)
                    .attr('stroke-dasharray', 'none');
            }
        });

        // ─── Today Line ──────────────────────────────────────────────────
        const todayX = xScale(today);

        // Pulse bg
        zoomG.append('rect')
            .attr('class', 'tl-today-pulse')
            .attr('x', todayX - 12)
            .attr('y', 0)
            .attr('width', 24)
            .attr('height', innerHeight)
            .attr('fill', '#EF4444')
            .attr('rx', 4);

        // Line
        zoomG.append('line')
            .attr('class', 'tl-today-line')
            .attr('x1', todayX).attr('x2', todayX)
            .attr('y1', -8).attr('y2', innerHeight);

        // Label
        zoomG.append('text')
            .attr('class', 'tl-today-label')
            .attr('x', todayX).attr('y', -14)
            .attr('text-anchor', 'middle')
            .text('I dag');

        // ─── Event Markers ────────────────────────────────────────────────
        activeArr.forEach((tg, laneIdx) => {
            const laneEvents = (eventsByLane[tg.id] || []).filter(e =>
                e.temagruppe_ids.some(id => activeGroups.has(id))
            );
            const laneY = laneIdx * laneHeight + laneHeight / 2;

            laneEvents.forEach((ev, evIdx) => {
                const cx = xScale(ev.dateObj);
                const cy = laneY;

                const markerG = zoomG.append('g')
                    .attr('class', `tl-event-marker ${ev.isPast ? 'past' : 'future'}`)
                    .attr('transform', `translate(${cx}, ${cy})`)
                    .style('transform-origin', `${cx}px ${cy}px`);

                // Glow for future
                if (!ev.isPast) {
                    markerG.append('circle')
                        .attr('class', 'marker-glow')
                        .attr('r', 14)
                        .attr('fill', tg.color)
                        .attr('opacity', 0);
                }

                // Main circle
                markerG.append('circle')
                    .attr('r', markerRadius)
                    .attr('fill', tg.color)
                    .attr('stroke', 'white')
                    .attr('stroke-width', 2.5);

                // Type icon (first letter)
                markerG.append('text')
                    .attr('text-anchor', 'middle')
                    .attr('dy', '0.35em')
                    .attr('fill', 'white')
                    .attr('font-size', '9px')
                    .attr('font-weight', '700')
                    .attr('pointer-events', 'none')
                    .text(ev.type ? ev.type.charAt(0).toUpperCase() : 'A');

                // Entry animation
                markerG.attr('opacity', 0)
                    .transition()
                    .delay(evIdx * 60 + laneIdx * 100)
                    .duration(400)
                    .ease(d3.easeCubicOut)
                    .attr('opacity', ev.isPast ? 0.45 : 1);

                // Interactions
                markerG.on('mouseenter', function(event) {
                    showTooltip(event, ev, tg);
                    d3.select(this).select('circle:not(.marker-glow)')
                        .transition().duration(150)
                        .attr('r', markerRadius + 3);
                })
                .on('mouseleave', function() {
                    hideTooltip();
                    d3.select(this).select('circle:not(.marker-glow)')
                        .transition().duration(150)
                        .attr('r', markerRadius);
                })
                .on('click', function(event) {
                    event.stopPropagation();
                    hideTooltip();
                    openDetailPanel(ev);
                });
            });
        });

        // ─── Zoom Behavior ────────────────────────────────────────────────
        zoom = d3.zoom()
            .scaleExtent([0.3, 8])
            .translateExtent([[-innerWidth, -100], [innerWidth * 3, height + 100]])
            .on('zoom', (event) => {
                const newX = event.transform.rescaleX(xScale);
                axisG.call(xAxis.scale(newX));

                // Update year dividers
                axisG.selectAll('.tick').each(function(d) {
                    if (d.getMonth() === 0) {
                        d3.select(this).select('line')
                            .attr('stroke', '#D6D1C6')
                            .attr('stroke-width', 1.5)
                            .attr('stroke-dasharray', 'none');
                    }
                });

                // Update today line
                const newTodayX = newX(today);
                zoomG.select('.tl-today-line')
                    .attr('x1', newTodayX).attr('x2', newTodayX);
                zoomG.select('.tl-today-label')
                    .attr('x', newTodayX);
                zoomG.select('.tl-today-pulse')
                    .attr('x', newTodayX - 12);

                // Update event positions
                let markerIdx = 0;
                activeArr.forEach((tg, laneIdx) => {
                    const laneEvents = (eventsByLane[tg.id] || []).filter(e =>
                        e.temagruppe_ids.some(id => activeGroups.has(id))
                    );
                    laneEvents.forEach(ev => {
                        const newCx = newX(ev.dateObj);
                        const newCy = laneIdx * laneHeight + laneHeight / 2;
                        d3.selectAll('.tl-event-marker')
                            .filter(function(d, i) { return i === markerIdx; })
                            .attr('transform', `translate(${newCx}, ${newCy})`);
                        markerIdx++;
                    });
                });
            });

        svg.call(zoom);

        // Zoom buttons
        document.getElementById('tlZoomIn').onclick = () => svg.transition().duration(300).call(zoom.scaleBy, 1.5);
        document.getElementById('tlZoomOut').onclick = () => svg.transition().duration(300).call(zoom.scaleBy, 0.67);
        document.getElementById('tlZoomReset').onclick = () => svg.transition().duration(500).call(zoom.transform, d3.zoomIdentity);
    }

    // ─── Tooltip ──────────────────────────────────────────────────────────
    function showTooltip(event, ev, tg) {
        const dateStr = formatDate(ev.dateObj);
        let html = `
            <div class="tl-tooltip-title">${escHtml(ev.title)}</div>
            <div class="tl-tooltip-date">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                ${dateStr}
            </div>`;

        if (ev.type) {
            html += `<div class="tl-tooltip-type">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                ${escHtml(ev.type)}</div>`;
        }
        if (ev.location) {
            html += `<div class="tl-tooltip-location">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                ${escHtml(ev.location)}</div>`;
        }

        html += `<div class="tl-tooltip-badge" style="background:${tg.color}">${escHtml(tg.name)}</div>`;

        tooltip.innerHTML = html;
        tooltip.classList.add('visible');

        // Position
        const rect = viewport.getBoundingClientRect();
        let x = event.clientX - rect.left + 16;
        let y = event.clientY - rect.top - 10;

        if (x + 280 > rect.width) x = event.clientX - rect.left - 290;
        if (y + tooltip.offsetHeight > rect.height) y = rect.height - tooltip.offsetHeight - 10;

        tooltip.style.left = x + 'px';
        tooltip.style.top = y + 'px';
    }

    function hideTooltip() {
        tooltip.classList.remove('visible');
    }

    // ─── Detail Panel ─────────────────────────────────────────────────────
    const panel = document.getElementById('tlPanel');
    const overlay = document.getElementById('tlOverlay');
    const panelContent = document.getElementById('tlPanelContent');

    function openDetailPanel(ev) {
        const dateStr = formatDate(ev.dateObj);
        const evTgs = themeGroups.filter(tg => ev.temagruppe_ids.includes(tg.id));
        const mainTg = evTgs[0] || themeGroups[0];

        let html = '';

        if (mainTg) {
            html += `<div class="tl-detail-badge" style="background:${mainTg.color}">${escHtml(ev.type || 'Arrangement')}</div>`;
        }

        html += `<h2 class="tl-detail-title">${escHtml(ev.title)}</h2>`;
        html += `<div class="tl-detail-meta">`;
        html += `<div class="tl-detail-meta-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            ${dateStr}${ev.isPast ? ' <span style="color:#A8A29E; margin-left:4px">(gjennomfort)</span>' : ''}
        </div>`;

        if (ev.location) {
            html += `<div class="tl-detail-meta-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                ${escHtml(ev.location)}
            </div>`;
        }

        if (ev.type) {
            html += `<div class="tl-detail-meta-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                ${escHtml(ev.type)}
            </div>`;
        }

        html += `</div>`;

        // Temagrupper tags
        if (evTgs.length > 0) {
            html += `<ul class="tl-detail-tg-list">`;
            evTgs.forEach(tg => {
                html += `<li class="tl-detail-tg-item">
                    <span class="tl-detail-tg-dot" style="background:${tg.color}"></span>
                    ${escHtml(tg.name)}
                </li>`;
            });
            html += `</ul>`;
        }

        if (ev.excerpt) {
            html += `<div class="tl-detail-excerpt">${escHtml(ev.excerpt)}</div>`;
        }

        if (ev.url && ev.url !== '#') {
            html += `<a href="${ev.url}" class="tl-detail-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                Se arrangement
            </a>`;
        }

        panelContent.innerHTML = html;
        panel.classList.add('open');
        overlay.classList.add('open');
    }

    function closeDetailPanel() {
        panel.classList.remove('open');
        overlay.classList.remove('open');
    }

    document.getElementById('tlPanelClose').addEventListener('click', closeDetailPanel);
    overlay.addEventListener('click', closeDetailPanel);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDetailPanel(); });

    // ─── Mobile Timeline ──────────────────────────────────────────────────
    function renderMobileTimeline() {
        const container = document.getElementById('tlMobile');
        container.innerHTML = '';

        // Collect visible events
        const visibleEvents = events
            .filter(e => e.temagruppe_ids.some(id => activeGroups.has(id)))
            .sort((a, b) => a.dateObj - b.dateObj);

        if (visibleEvents.length === 0) {
            container.innerHTML = '<p style="color:#5A5A5A; padding:16px 0;">Ingen arrangementer a vise.</p>';
            return;
        }

        // Group by month
        const months = {};
        let insertedToday = false;

        visibleEvents.forEach(ev => {
            const key = ev.dateObj.getFullYear() + '-' + String(ev.dateObj.getMonth() + 1).padStart(2, '0');
            if (!months[key]) months[key] = [];
            months[key].push(ev);
        });

        const monthNames = ['januar', 'februar', 'mars', 'april', 'mai', 'juni', 'juli', 'august', 'september', 'oktober', 'november', 'desember'];
        const todayKey = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0');

        Object.keys(months).sort().forEach(key => {
            const [y, m] = key.split('-').map(Number);
            const monthDiv = document.createElement('div');
            monthDiv.className = 'tl-mobile-month';

            const label = document.createElement('div');
            label.className = 'tl-mobile-month-label';
            label.textContent = monthNames[m - 1] + ' ' + y;
            monthDiv.appendChild(label);

            // Insert today marker before first future event in today's month
            if (!insertedToday && key >= todayKey) {
                const todayDiv = document.createElement('div');
                todayDiv.className = 'tl-mobile-today';
                todayDiv.innerHTML = `
                    <span class="tl-mobile-today-line"></span>
                    <span class="tl-mobile-today-label">I dag ${formatDate(today)}</span>
                    <span class="tl-mobile-today-line"></span>`;
                monthDiv.appendChild(todayDiv);
                insertedToday = true;
            }

            months[key].forEach(ev => {
                const tg = themeGroups.find(t => ev.temagruppe_ids.includes(t.id)) || themeGroups[0];
                const row = document.createElement('div');
                row.className = `tl-mobile-event ${ev.isPast ? 'past' : ''}`;
                row.innerHTML = `
                    <div class="tl-mobile-dot-col">
                        <div class="tl-mobile-dot" style="background:${tg ? tg.color : '#999'}"></div>
                        <div class="tl-mobile-line" style="background:${tg ? tg.color : '#999'}; opacity:0.2"></div>
                    </div>
                    <div class="tl-mobile-content">
                        <div class="tl-mobile-event-title">${escHtml(ev.title)}</div>
                        <div class="tl-mobile-event-meta">${formatDate(ev.dateObj)}${ev.location ? ' &middot; ' + escHtml(ev.location) : ''}${ev.type ? ' &middot; ' + escHtml(ev.type) : ''}</div>
                    </div>`;
                row.addEventListener('click', () => openDetailPanel(ev));
                monthDiv.appendChild(row);
            });

            container.appendChild(monthDiv);
        });
    }

    // ─── Helpers ──────────────────────────────────────────────────────────
    function formatDate(d) {
        const day = d.getDate();
        const months = ['jan', 'feb', 'mar', 'apr', 'mai', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'des'];
        return `${day}. ${months[d.getMonth()]} ${d.getFullYear()}`;
    }

    function escHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ─── Init ─────────────────────────────────────────────────────────────
    renderTimeline();
    renderMobileTimeline();

    // Resize handler
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            renderTimeline();
        }, 200);
    });
})();
</script>

</main>
<?php
