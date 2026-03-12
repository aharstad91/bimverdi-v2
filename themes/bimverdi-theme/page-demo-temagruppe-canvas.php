<?php
/**
 * Template Name: Demo - Temagruppe Canvas
 *
 * DEMO/PROTOTYPE - Utforsker canvas-basert layout for temagrupper.
 * Dot grid bakgrunn med drag-to-pan (Figma/Miro/Zapier-stil).
 * Strukturert vertikal layout med left-alignment.
 *
 * @package BimVerdi_Theme
 */

get_header();

// ── Fiktiv demo-data ──
$group = [
    'name'        => 'ByggesaksBIM',
    'icon'        => '🏗️',
    'color'       => '#3B82F6',
    'description' => 'Digitalisering av byggesaksprosessen gjennom bruk av åpne BIM-standarder, automatisert regelsjekk og samhandling mellom kommuner og utbyggere.',
    'status'      => 'Aktiv',
    'fagradgiver' => [
        'name'     => 'Anders Johansen',
        'title'    => 'Senior utvikler / BIM-teknologi',
        'initials' => 'AJ',
    ],
];

$arrangementer = [
    ['title' => 'Fagmote: IFC og byggesak', 'date' => '14. mars 2026', 'type' => 'Fagmote', 'location' => 'Teams'],
    ['title' => 'Workshop: Automatisk regelsjekk', 'date' => '28. mars 2026', 'type' => 'Workshop', 'location' => 'Oslo'],
    ['title' => 'Seminar: Digital tvilling i kommuner', 'date' => '11. april 2026', 'type' => 'Seminar', 'location' => 'Bergen'],
    ['title' => 'Webinar: BIM i plan og byggesak', 'date' => '22. april 2026', 'type' => 'Webinar', 'location' => 'Teams'],
];

$artikler = [
    ['title' => 'Hvordan BIM endrer byggesak', 'author' => 'Kari Nordmann', 'date' => '2. feb 2026', 'excerpt' => 'En gjennomgang av hvordan BIM-modeller kan erstatte tradisjonelle tegninger i byggesaker.'],
    ['title' => 'IFC 4.3 - Hva er nytt?', 'author' => 'Ole Hansen', 'date' => '18. jan 2026', 'excerpt' => 'De viktigste endringene i den nye IFC-standarden og hva det betyr for bransjen.'],
    ['title' => 'Regelsjekk med Solibri', 'author' => 'Lisa Berg', 'date' => '5. jan 2026', 'excerpt' => 'Praktisk guide til oppsett av automatiske regelsjekker for TEK17-krav.'],
    ['title' => 'Kommunenes digitale modenhet', 'author' => 'Erik Vik', 'date' => '12. des 2025', 'excerpt' => 'Kartlegging av norske kommuners evne til a motta og behandle BIM-leveranser.'],
    ['title' => 'Fra tegning til modell', 'author' => 'Marte Dahl', 'date' => '28. nov 2025', 'excerpt' => 'Overgangen fra 2D-tegninger til 3D-modeller i byggesaksprosessen.'],
    ['title' => 'Apen BIM i praksis', 'author' => 'Jonas Lie', 'date' => '15. nov 2025', 'excerpt' => 'Erfaringer fra prosjekter som har tatt i bruk apne BIM-standarder fullt ut.'],
    ['title' => 'Automatisering av TEK17', 'author' => 'Silje Holm', 'date' => '1. nov 2025', 'excerpt' => 'Hvordan maskinlesbare krav kan akselerere byggesaksbehandlingen.'],
    ['title' => 'Digital samhandling', 'author' => 'Per Strand', 'date' => '18. okt 2025', 'excerpt' => 'Nye modeller for samarbeid mellom utbyggere og kommuner i byggesak.'],
];

$kunnskapskilder = [
    ['title' => 'buildingSMART - IFC', 'type' => 'Standard', 'icon' => '📐'],
    ['title' => 'TEK17 Digital', 'type' => 'Regelverk', 'icon' => '📋'],
    ['title' => 'KS Digitale byggesaker', 'type' => 'Rapport', 'icon' => '📊'],
    ['title' => 'DiBK Veikart', 'type' => 'Strategi', 'icon' => '🗺️'],
    ['title' => 'Solibri Regelsjekk Guide', 'type' => 'Verktoy', 'icon' => '🔧'],
    ['title' => 'ISO 19650 Norsk', 'type' => 'Standard', 'icon' => '📐'],
    ['title' => 'Statsbygg BIM-manual', 'type' => 'Veiledning', 'icon' => '📘'],
];

$deltakere = [
    ['name' => 'Multiconsult', 'role' => 'Radgivende ingenior'],
    ['name' => 'Norconsult', 'role' => 'Radgivende ingenior'],
    ['name' => 'Statsbygg', 'role' => 'Byggherre'],
    ['name' => 'Oslo Kommune', 'role' => 'Kommune'],
    ['name' => 'Veidekke', 'role' => 'Entreprenor'],
    ['name' => 'AF Gruppen', 'role' => 'Entreprenor'],
    ['name' => 'Ramboll', 'role' => 'Radgivende ingenior'],
    ['name' => 'Asplan Viak', 'role' => 'Radgivende ingenior'],
    ['name' => 'COWI', 'role' => 'Radgivende ingenior'],
    ['name' => 'Skanska', 'role' => 'Entreprenor'],
];

// Tab config with counts
$tabs = [
    'oversikt'        => ['label' => 'Oversikt',        'count' => null],
    'deltakere'       => ['label' => 'Deltakere',       'count' => count($deltakere)],
    'arrangementer'   => ['label' => 'Arrangementer',   'count' => count($arrangementer)],
    'kunnskapskilder' => ['label' => 'Kunnskapskilder', 'count' => count($kunnskapskilder)],
    'artikler'        => ['label' => 'Artikler',        'count' => count($artikler)],
];
?>

<style>
/* ── Canvas Demo Styles ── */
:root {
    --canvas-dot-color: #E0DDD6;
    --canvas-bg: #FAF9F7;
    --canvas-card-bg: #FFFFFF;
    --canvas-card-border: #E7E5E4;
    --canvas-card-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
    --canvas-card-shadow-hover: 0 4px 12px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.04);
    --canvas-text: #1A1A1A;
    --canvas-text-secondary: #57534E;
    --canvas-text-muted: #A8A29E;
    --canvas-accent: #FF8B5E;
    --canvas-radius: 12px;
    --canvas-left-pad: 48px;
}

/* ── Header (pinned above canvas) ── */
.tg-header {
    background: #FFFFFF;
    border-bottom: 1px solid var(--canvas-card-border);
}
.tg-header-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 32px 24px 0;
}

.tg-hero {
    display: flex;
    gap: 24px;
    align-items: flex-start;
    flex-wrap: wrap;
}
.tg-hero-main {
    flex: 1;
    min-width: 300px;
}
.tg-hero-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    background: #EFF6FF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-bottom: 16px;
}
.tg-hero-title {
    font-family: 'Familjen Grotesk', sans-serif;
    font-size: 28px;
    font-weight: 700;
    color: var(--canvas-text);
    margin: 0 0 8px;
    line-height: 1.2;
}
.tg-hero-desc {
    font-size: 15px;
    line-height: 1.6;
    color: var(--canvas-text-secondary);
    max-width: 600px;
    margin: 0;
}

.tg-hero-sidebar { width: 280px; flex-shrink: 0; }
.tg-fagradgiver {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #FAFAF9;
    border-radius: var(--canvas-radius);
    border: 1px solid var(--canvas-card-border);
}
.tg-fagradgiver-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: var(--canvas-accent); color: white;
    display: flex; align-items: center; justify-content: center;
    font-weight: 600; font-size: 14px; flex-shrink: 0;
}
.tg-fagradgiver-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--canvas-text-muted); font-weight: 600; }
.tg-fagradgiver-name { font-size: 14px; font-weight: 600; color: var(--canvas-text); }
.tg-fagradgiver-title { font-size: 13px; color: var(--canvas-text-secondary); }

.tg-cta-btn {
    display: block; width: 100%; margin-top: 12px; padding: 12px 20px;
    background: var(--canvas-text); color: white; border: none; border-radius: 8px;
    font-size: 14px; font-weight: 600; cursor: pointer; text-align: center;
    text-decoration: none; transition: background 0.15s;
}
.tg-cta-btn:hover { background: #333; }
.tg-cta-btn span { margin-left: 6px; }
.tg-cta-sub { text-align: center; font-size: 12px; color: var(--canvas-text-muted); margin-top: 6px; }

/* ── Tabs ── */
.tg-tabs {
    display: flex; gap: 0; margin-top: 24px;
    overflow-x: auto; -webkit-overflow-scrolling: touch;
}
.tg-tab {
    padding: 12px 20px; font-size: 14px; font-weight: 500;
    color: var(--canvas-text-secondary); cursor: pointer;
    border: none; background: none; border-bottom: 2px solid transparent;
    white-space: nowrap; transition: color 0.15s, border-color 0.15s;
    font-family: 'Familjen Grotesk', sans-serif;
}
.tg-tab:hover { color: var(--canvas-text); }
.tg-tab.active { color: var(--canvas-accent); border-bottom-color: var(--canvas-accent); font-weight: 600; }
.tg-tab .count { font-size: 12px; color: var(--canvas-text-muted); margin-left: 4px; font-weight: 400; }
.tg-tab.active .count { color: var(--canvas-accent); opacity: 0.7; }

/* ── Canvas viewport ── */
.canvas-viewport {
    position: relative;
    width: 100%;
    height: calc(100vh - 260px);
    min-height: 500px;
    overflow: hidden;
    cursor: grab;
    background-color: var(--canvas-bg);
    background-image: radial-gradient(circle, var(--canvas-dot-color) 0.75px, transparent 0.75px);
    background-size: 22px 22px;
    background-position: 0px 0px;
}
.canvas-viewport.is-dragging { cursor: grabbing; }
.canvas-viewport.is-dragging .canvas-card { pointer-events: none; }

/* The pannable surface - uses natural flow, not absolute positioning */
.canvas-surface {
    position: absolute;
    top: 0;
    left: 0;
    will-change: transform;
    /* Width is large to allow horizontal card rows to extend */
    min-width: 3200px;
    padding: 40px var(--canvas-left-pad) 120px;
}

/* ── Sections (vertical stack) ── */
.canvas-section {
    margin-bottom: 52px;
}
.canvas-section:last-child { margin-bottom: 0; }

/* Section toolbar - stacked: title row, then controls row */
.section-toolbar {
    margin-bottom: 16px;
    max-width: 1200px;
}

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

.section-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* Filter chips */
.section-filters {
    display: flex;
    gap: 6px;
    align-items: center;
    flex-wrap: nowrap;
}
.filter-chip {
    font-family: 'Familjen Grotesk', sans-serif;
    font-size: 12px;
    font-weight: 500;
    padding: 5px 12px;
    border-radius: 100px;
    border: 1px solid var(--canvas-card-border);
    background: white;
    color: var(--canvas-text-secondary);
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s;
}
.filter-chip:hover {
    border-color: var(--canvas-text-muted);
    color: var(--canvas-text);
}
.filter-chip.active {
    background: var(--canvas-text);
    color: white;
    border-color: var(--canvas-text);
}

/* Sort button */
.section-sort {
    font-family: 'Familjen Grotesk', sans-serif;
    font-size: 12px;
    font-weight: 500;
    padding: 5px 12px;
    border-radius: 8px;
    border: 1px solid var(--canvas-card-border);
    background: white;
    color: var(--canvas-text-secondary);
    cursor: pointer;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s;
}
.section-sort:hover { border-color: var(--canvas-text-muted); color: var(--canvas-text); }
.section-sort svg { width: 14px; height: 14px; }

/* Search input */
.section-search {
    position: relative;
    display: flex;
    align-items: center;
}
.section-search input {
    font-family: 'Familjen Grotesk', sans-serif;
    font-size: 12px;
    padding: 5px 10px 5px 28px;
    border-radius: 8px;
    border: 1px solid var(--canvas-card-border);
    background: white;
    color: var(--canvas-text);
    width: 140px;
    outline: none;
    transition: border-color 0.15s, width 0.2s;
}
.section-search input:focus {
    border-color: var(--canvas-accent);
    width: 180px;
}
.section-search input::placeholder { color: var(--canvas-text-muted); }
.section-search svg {
    position: absolute;
    left: 8px;
    width: 14px;
    height: 14px;
    color: var(--canvas-text-muted);
    pointer-events: none;
}

/* Hidden card (filtered out) */
.canvas-card.filtered-out {
    display: none;
}

/* Card row - horizontal flex, no wrapping */
.card-row {
    display: flex;
    gap: 16px;
    align-items: stretch;
}

/* ── Cards ── */
.canvas-card {
    background: var(--canvas-card-bg);
    border: 1px solid var(--canvas-card-border);
    border-radius: var(--canvas-radius);
    padding: 20px;
    box-shadow: var(--canvas-card-shadow);
    transition: box-shadow 0.2s, transform 0.2s;
    cursor: default;
    flex-shrink: 0;
}
.canvas-card:hover {
    box-shadow: var(--canvas-card-shadow-hover);
    transform: translateY(-1px);
}

/* Card widths by type */
.card--event { width: 260px; }
.card--article { width: 280px; }
.card--ks { width: 220px; }
.card--deltaker { width: 200px; }

/* Event card */
.cc-event-type {
    font-size: 11px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.05em; color: var(--canvas-accent); margin-bottom: 6px;
}
.cc-event-title {
    font-size: 15px; font-weight: 600; color: var(--canvas-text);
    margin-bottom: 8px; line-height: 1.3;
}
.cc-event-meta {
    font-size: 13px; color: var(--canvas-text-secondary);
    display: flex; flex-direction: column; gap: 2px;
}

/* Article card */
.cc-article-title {
    font-size: 15px; font-weight: 600; color: var(--canvas-text);
    margin-bottom: 6px; line-height: 1.3;
}
.cc-article-excerpt {
    font-size: 13px; color: var(--canvas-text-secondary); line-height: 1.5;
    margin-bottom: 8px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.cc-article-author { font-size: 12px; color: var(--canvas-text-muted); }

/* Knowledge source card */
.cc-ks-icon { font-size: 24px; margin-bottom: 8px; }
.cc-ks-title { font-size: 14px; font-weight: 600; color: var(--canvas-text); margin-bottom: 4px; }
.cc-ks-type { font-size: 12px; color: var(--canvas-text-muted); }

/* Participant card */
.cc-deltaker-avatar {
    width: 36px; height: 36px; border-radius: 8px; background: #F0EDEA;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 600; color: var(--canvas-text-secondary);
    margin-bottom: 10px;
}
.cc-deltaker-name { font-size: 14px; font-weight: 600; color: var(--canvas-text); margin-bottom: 2px; }
.cc-deltaker-role { font-size: 12px; color: var(--canvas-text-muted); }

/* ── Tab content visibility ── */
.canvas-section[data-tab] { display: none; }
.canvas-section[data-tab].visible { display: block; }
.canvas-section[data-tab="all"] { display: block; }

/* ── View toggle ── */
.view-toggle {
    display: flex;
    gap: 2px;
    background: #F0EDEA;
    border-radius: 8px;
    padding: 2px;
    margin-left: auto;
}
.view-toggle-btn {
    font-family: 'Familjen Grotesk', sans-serif;
    font-size: 12px;
    font-weight: 500;
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: var(--canvas-text-secondary);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.15s;
    white-space: nowrap;
}
.view-toggle-btn:hover { color: var(--canvas-text); }
.view-toggle-btn.active {
    background: white;
    color: var(--canvas-text);
    box-shadow: 0 1px 2px rgba(0,0,0,0.08);
}
.view-toggle-btn svg { width: 14px; height: 14px; }

/* ── Grid mode overrides ── */
.canvas-viewport.grid-mode {
    height: auto;
    min-height: auto;
    overflow: visible;
    cursor: default;
    background-image: none;
    background-color: white;
}
.canvas-viewport.grid-mode .canvas-surface {
    position: relative;
    min-width: 0;
    max-width: 1280px;
    margin: 0 auto;
    padding: 32px 24px 80px;
    transform: none !important;
    will-change: auto;
}
.canvas-viewport.grid-mode .card-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 16px;
}
.canvas-viewport.grid-mode .canvas-card {
    width: auto;
    flex-shrink: 1;
}
.canvas-viewport.grid-mode .card-row:has(.card--deltaker) {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
}
.canvas-viewport.grid-mode .card-row:has(.card--ks) {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
}
.canvas-viewport.grid-mode .section-toolbar { max-width: none; }
.canvas-viewport.grid-mode .canvas-hint { display: none; }

/* ── Canvas hint ── */
.canvas-hint {
    position: absolute; bottom: 24px; left: 50%; transform: translateX(-50%);
    background: rgba(0,0,0,0.65); color: white; padding: 8px 16px;
    border-radius: 100px; font-size: 13px; font-weight: 500;
    z-index: 10; pointer-events: none; opacity: 1; transition: opacity 0.5s;
    display: flex; align-items: center; gap: 8px;
}
.canvas-hint.hidden { opacity: 0; }
.canvas-hint svg { opacity: 0.7; }


/* ── Responsive ── */
@media (max-width: 768px) {
    .tg-hero { flex-direction: column; }
    .tg-hero-sidebar { width: 100%; }
    .canvas-viewport { height: calc(100vh - 380px); }
    .tg-header-inner { padding: 20px 16px 0; }
    :root { --canvas-left-pad: 16px; }
}
</style>

<main>
    <!-- ── Pinned Header ── -->
    <div class="tg-header">
        <div class="tg-header-inner">
            <div class="tg-hero">
                <div class="tg-hero-main">
                    <div class="tg-hero-icon"><?php echo $group['icon']; ?></div>
                    <h1 class="tg-hero-title"><?php echo esc_html($group['name']); ?></h1>
                    <p class="tg-hero-desc"><?php echo esc_html($group['description']); ?></p>
                </div>
                <div class="tg-hero-sidebar">
                    <div class="tg-fagradgiver">
                        <div class="tg-fagradgiver-avatar"><?php echo esc_html($group['fagradgiver']['initials']); ?></div>
                        <div>
                            <div class="tg-fagradgiver-label">Fagradgiver</div>
                            <div class="tg-fagradgiver-name"><?php echo esc_html($group['fagradgiver']['name']); ?></div>
                            <div class="tg-fagradgiver-title"><?php echo esc_html($group['fagradgiver']['title']); ?></div>
                        </div>
                    </div>
                    <a href="#" class="tg-cta-btn">Bli med i gruppen <span>&rarr;</span></a>
                    <div class="tg-cta-sub">Via din bedriftsprofil i Min Side</div>
                </div>
            </div>

            <div style="display: flex; align-items: flex-end; gap: 12px;">
                <div class="tg-tabs" role="tablist" style="flex: 1;">
                    <?php foreach ($tabs as $key => $tab) : ?>
                        <button class="tg-tab<?php echo $key === 'oversikt' ? ' active' : ''; ?>"
                                role="tab" data-tab="<?php echo esc_attr($key); ?>">
                            <?php echo esc_html($tab['label']); ?>
                            <?php if ($tab['count'] !== null) : ?>
                                <span class="count">(<?php echo $tab['count']; ?>)</span>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="view-toggle" id="viewToggle">
                    <button class="view-toggle-btn active" data-view="canvas" title="Canvas (dra for a panorere)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 9l4-4 4 4M5 15l4 4 4-4M15 5l4 4-4 4M9 5l-4 4 4 4" opacity="0"/><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><path d="M10 7h4M10 17h4M7 10v4M17 10v4"/></svg>
                        Canvas
                    </button>
                    <button class="view-toggle-btn" data-view="grid" title="Grid (standard visning)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                        Grid
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Canvas Area ── -->
    <div class="canvas-viewport" id="canvasViewport">

        <div class="canvas-hint" id="canvasHint">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M8 2v12M2 8h12M4 4l-2 4 2 4M12 4l2 4-2 4"/>
            </svg>
            Dra for a panorere
        </div>

        <!-- ── Pannable surface (structured vertical layout) ── -->
        <div class="canvas-surface" id="canvasSurface">

            <?php
            // ── Reusable SVG icons ──
            $icon_search = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';
            $icon_sort = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h12M3 18h6"/></svg>';

            // Unique filter values
            $event_types = array_unique(array_column($arrangementer, 'type'));
            $ks_types = array_unique(array_column($kunnskapskilder, 'type'));
            $deltaker_roles = array_unique(array_column($deltakere, 'role'));
            ?>

            <!-- ═══ OVERSIKT: Arrangementer ═══ -->
            <div class="canvas-section" data-tab="all" data-section="arrangementer">
                <div class="section-toolbar">
                    <div class="section-title-row">
                        <h3 class="section-title">Arrangementer</h3>
                        <span class="section-count"><?php echo count($arrangementer); ?></span>
                    </div>
                    <div class="section-controls">
                        <div class="section-filters" data-target="arrangementer">
                            <button class="filter-chip active" data-filter="*">Alle</button>
                            <?php foreach ($event_types as $type) : ?>
                            <button class="filter-chip" data-filter="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></button>
                            <?php endforeach; ?>
                        </div>
                        <button class="section-sort" data-sort="arrangementer" title="Sorter"><?php echo $icon_sort; ?> Dato</button>
                    </div>
                </div>
                <div class="card-row" data-row="arrangementer">
                    <?php foreach ($arrangementer as $event) : ?>
                    <div class="canvas-card card--event" data-type="<?php echo esc_attr($event['type']); ?>" data-searchable="<?php echo esc_attr(strtolower($event['title'] . ' ' . $event['type'] . ' ' . $event['location'])); ?>">
                        <div class="cc-event-type"><?php echo esc_html($event['type']); ?></div>
                        <div class="cc-event-title"><?php echo esc_html($event['title']); ?></div>
                        <div class="cc-event-meta">
                            <span><?php echo esc_html($event['date']); ?></span>
                            <span><?php echo esc_html($event['location']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ═══ OVERSIKT: Artikler ═══ -->
            <div class="canvas-section" data-tab="all" data-section="artikler">
                <div class="section-toolbar">
                    <div class="section-title-row">
                        <h3 class="section-title">Artikler</h3>
                        <span class="section-count"><?php echo count($artikler); ?></span>
                    </div>
                    <div class="section-controls">
                        <div class="section-search">
                            <?php echo $icon_search; ?>
                            <input type="text" placeholder="Sok i artikler..." data-search="artikler">
                        </div>
                        <button class="section-sort" data-sort="artikler" title="Sorter"><?php echo $icon_sort; ?> Nyeste</button>
                    </div>
                </div>
                <div class="card-row" data-row="artikler">
                    <?php foreach ($artikler as $article) : ?>
                    <div class="canvas-card card--article" data-searchable="<?php echo esc_attr(strtolower($article['title'] . ' ' . $article['author'] . ' ' . $article['excerpt'])); ?>">
                        <div class="cc-article-title"><?php echo esc_html($article['title']); ?></div>
                        <div class="cc-article-excerpt"><?php echo esc_html($article['excerpt']); ?></div>
                        <div class="cc-article-author"><?php echo esc_html($article['author']); ?> &middot; <?php echo esc_html($article['date']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ═══ OVERSIKT: Kunnskapskilder ═══ -->
            <div class="canvas-section" data-tab="all" data-section="kunnskapskilder">
                <div class="section-toolbar">
                    <div class="section-title-row">
                        <h3 class="section-title">Kunnskapskilder</h3>
                        <span class="section-count"><?php echo count($kunnskapskilder); ?></span>
                    </div>
                    <div class="section-controls">
                        <div class="section-filters" data-target="kunnskapskilder">
                            <button class="filter-chip active" data-filter="*">Alle</button>
                            <?php foreach ($ks_types as $type) : ?>
                            <button class="filter-chip" data-filter="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="card-row" data-row="kunnskapskilder">
                    <?php foreach ($kunnskapskilder as $ks) : ?>
                    <div class="canvas-card card--ks" data-type="<?php echo esc_attr($ks['type']); ?>" data-searchable="<?php echo esc_attr(strtolower($ks['title'] . ' ' . $ks['type'])); ?>">
                        <div class="cc-ks-icon"><?php echo $ks['icon']; ?></div>
                        <div class="cc-ks-title"><?php echo esc_html($ks['title']); ?></div>
                        <div class="cc-ks-type"><?php echo esc_html($ks['type']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ═══ OVERSIKT: Deltakere ═══ -->
            <div class="canvas-section" data-tab="all" data-section="deltakere">
                <div class="section-toolbar">
                    <div class="section-title-row">
                        <h3 class="section-title">Deltakende foretak</h3>
                        <span class="section-count"><?php echo count($deltakere); ?></span>
                    </div>
                    <div class="section-controls">
                        <div class="section-filters" data-target="deltakere">
                            <button class="filter-chip active" data-filter="*">Alle</button>
                            <?php foreach ($deltaker_roles as $role) : ?>
                            <button class="filter-chip" data-filter="<?php echo esc_attr($role); ?>"><?php echo esc_html($role); ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="section-search">
                            <?php echo $icon_search; ?>
                            <input type="text" placeholder="Sok..." data-search="deltakere">
                        </div>
                    </div>
                </div>
                <div class="card-row" data-row="deltakere">
                    <?php foreach ($deltakere as $d) : ?>
                    <div class="canvas-card card--deltaker" data-type="<?php echo esc_attr($d['role']); ?>" data-searchable="<?php echo esc_attr(strtolower($d['name'] . ' ' . $d['role'])); ?>">
                        <div class="cc-deltaker-avatar"><?php echo esc_html(mb_substr($d['name'], 0, 1)); ?></div>
                        <div class="cc-deltaker-name"><?php echo esc_html($d['name']); ?></div>
                        <div class="cc-deltaker-role"><?php echo esc_html($d['role']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ═══ TAB: Deltakere ═══ -->
            <div class="canvas-section" data-tab="deltakere" data-section="deltakere-tab">
                <div class="section-toolbar">
                    <div class="section-title-row">
                        <h3 class="section-title">Deltakende foretak</h3>
                        <span class="section-count"><?php echo count($deltakere); ?></span>
                    </div>
                    <div class="section-controls">
                        <div class="section-filters" data-target="deltakere-tab">
                            <button class="filter-chip active" data-filter="*">Alle</button>
                            <?php foreach ($deltaker_roles as $role) : ?>
                            <button class="filter-chip" data-filter="<?php echo esc_attr($role); ?>"><?php echo esc_html($role); ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="section-search">
                            <?php echo $icon_search; ?>
                            <input type="text" placeholder="Sok..." data-search="deltakere-tab">
                        </div>
                    </div>
                </div>
                <div class="card-row" data-row="deltakere-tab">
                    <?php foreach ($deltakere as $d) : ?>
                    <div class="canvas-card card--deltaker" data-type="<?php echo esc_attr($d['role']); ?>" data-searchable="<?php echo esc_attr(strtolower($d['name'] . ' ' . $d['role'])); ?>">
                        <div class="cc-deltaker-avatar"><?php echo esc_html(mb_substr($d['name'], 0, 1)); ?></div>
                        <div class="cc-deltaker-name"><?php echo esc_html($d['name']); ?></div>
                        <div class="cc-deltaker-role"><?php echo esc_html($d['role']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ═══ TAB: Arrangementer ═══ -->
            <div class="canvas-section" data-tab="arrangementer" data-section="arrangementer-tab">
                <div class="section-toolbar">
                    <div class="section-title-row">
                        <h3 class="section-title">Arrangementer</h3>
                        <span class="section-count"><?php echo count($arrangementer); ?></span>
                    </div>
                    <div class="section-controls">
                        <div class="section-filters" data-target="arrangementer-tab">
                            <button class="filter-chip active" data-filter="*">Alle</button>
                            <?php foreach ($event_types as $type) : ?>
                            <button class="filter-chip" data-filter="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></button>
                            <?php endforeach; ?>
                        </div>
                        <button class="section-sort" data-sort="arrangementer-tab" title="Sorter"><?php echo $icon_sort; ?> Dato</button>
                    </div>
                </div>
                <div class="card-row" data-row="arrangementer-tab">
                    <?php foreach ($arrangementer as $event) : ?>
                    <div class="canvas-card card--event" data-type="<?php echo esc_attr($event['type']); ?>" data-searchable="<?php echo esc_attr(strtolower($event['title'] . ' ' . $event['type'] . ' ' . $event['location'])); ?>">
                        <div class="cc-event-type"><?php echo esc_html($event['type']); ?></div>
                        <div class="cc-event-title"><?php echo esc_html($event['title']); ?></div>
                        <div class="cc-event-meta">
                            <span><?php echo esc_html($event['date']); ?></span>
                            <span><?php echo esc_html($event['location']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ═══ TAB: Kunnskapskilder ═══ -->
            <div class="canvas-section" data-tab="kunnskapskilder" data-section="kunnskapskilder-tab">
                <div class="section-toolbar">
                    <div class="section-title-row">
                        <h3 class="section-title">Kunnskapskilder</h3>
                        <span class="section-count"><?php echo count($kunnskapskilder); ?></span>
                    </div>
                    <div class="section-controls">
                        <div class="section-filters" data-target="kunnskapskilder-tab">
                            <button class="filter-chip active" data-filter="*">Alle</button>
                            <?php foreach ($ks_types as $type) : ?>
                            <button class="filter-chip" data-filter="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="card-row" data-row="kunnskapskilder-tab">
                    <?php foreach ($kunnskapskilder as $ks) : ?>
                    <div class="canvas-card card--ks" data-type="<?php echo esc_attr($ks['type']); ?>" data-searchable="<?php echo esc_attr(strtolower($ks['title'] . ' ' . $ks['type'])); ?>">
                        <div class="cc-ks-icon"><?php echo $ks['icon']; ?></div>
                        <div class="cc-ks-title"><?php echo esc_html($ks['title']); ?></div>
                        <div class="cc-ks-type"><?php echo esc_html($ks['type']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ═══ TAB: Artikler ═══ -->
            <div class="canvas-section" data-tab="artikler" data-section="artikler-tab">
                <div class="section-toolbar">
                    <div class="section-title-row">
                        <h3 class="section-title">Artikler</h3>
                        <span class="section-count"><?php echo count($artikler); ?></span>
                    </div>
                    <div class="section-controls">
                        <div class="section-search">
                            <?php echo $icon_search; ?>
                            <input type="text" placeholder="Sok i artikler..." data-search="artikler-tab">
                        </div>
                        <button class="section-sort" data-sort="artikler-tab" title="Sorter"><?php echo $icon_sort; ?> Nyeste</button>
                    </div>
                </div>
                <div class="card-row" data-row="artikler-tab">
                    <?php foreach ($artikler as $article) : ?>
                    <div class="canvas-card card--article" data-searchable="<?php echo esc_attr(strtolower($article['title'] . ' ' . $article['author'] . ' ' . $article['excerpt'])); ?>">
                        <div class="cc-article-title"><?php echo esc_html($article['title']); ?></div>
                        <div class="cc-article-excerpt"><?php echo esc_html($article['excerpt']); ?></div>
                        <div class="cc-article-author"><?php echo esc_html($article['author']); ?> &middot; <?php echo esc_html($article['date']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div><!-- .canvas-surface -->

    </div><!-- .canvas-viewport -->
</main>

<script>
(function() {
    'use strict';

    const viewport = document.getElementById('canvasViewport');
    const surface = document.getElementById('canvasSurface');
    const hint = document.getElementById('canvasHint');

    let panX = 0, panY = 0;
    let currentView = 'canvas';
    let isDragging = false;
    let startX, startY;
    let velocityX = 0, velocityY = 0;
    let lastMoveTime = 0;
    let animFrame = null;
    let hintDismissed = false;

    // Align canvas left-padding with header content edge
    function alignWithHeader() {
        const heroIcon = document.querySelector('.tg-hero-icon');
        if (heroIcon) {
            const contentLeft = heroIcon.getBoundingClientRect().left;
            surface.style.paddingLeft = contentLeft + 'px';
        }
    }
    alignWithHeader();
    window.addEventListener('resize', alignWithHeader);

    function render() {
        surface.style.transform = `translate(${panX}px, ${panY}px)`;
        surface.style.transformOrigin = '0 0';
        viewport.style.backgroundPosition = `${panX % 22}px ${panY % 22}px`;
    }

    // ── Mouse drag ──
    viewport.addEventListener('mousedown', (e) => {
        if (currentView === 'grid') return;
        if (e.target.closest('.canvas-hint')) return;
        isDragging = true;
        startX = e.clientX - panX;
        startY = e.clientY - panY;
        velocityX = 0; velocityY = 0;
        lastMoveTime = Date.now();
        viewport.classList.add('is-dragging');
        if (!hintDismissed) { hint.classList.add('hidden'); hintDismissed = true; }
        e.preventDefault();
    });

    window.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        const now = Date.now();
        const dt = now - lastMoveTime || 1;
        const newX = e.clientX - startX;
        const newY = e.clientY - startY;
        velocityX = (newX - panX) / dt * 16;
        velocityY = (newY - panY) / dt * 16;
        panX = newX; panY = newY;
        lastMoveTime = now;
        render();
    });

    window.addEventListener('mouseup', () => {
        if (!isDragging) return;
        isDragging = false;
        viewport.classList.remove('is-dragging');
        startInertia();
    });

    // ── Touch drag ──
    viewport.addEventListener('touchstart', (e) => {
        if (currentView === 'grid') return;
        if (e.touches.length !== 1) return;
        if (e.target.closest('.canvas-hint')) return;
        const t = e.touches[0];
        isDragging = true;
        startX = t.clientX - panX; startY = t.clientY - panY;
        velocityX = 0; velocityY = 0;
        lastMoveTime = Date.now();
        viewport.classList.add('is-dragging');
        if (!hintDismissed) { hint.classList.add('hidden'); hintDismissed = true; }
    }, { passive: true });

    viewport.addEventListener('touchmove', (e) => {
        if (!isDragging || e.touches.length !== 1) return;
        const t = e.touches[0];
        const now = Date.now();
        const dt = now - lastMoveTime || 1;
        const newX = t.clientX - startX;
        const newY = t.clientY - startY;
        velocityX = (newX - panX) / dt * 16;
        velocityY = (newY - panY) / dt * 16;
        panX = newX; panY = newY;
        lastMoveTime = now;
        render();
    }, { passive: true });

    viewport.addEventListener('touchend', () => {
        if (!isDragging) return;
        isDragging = false;
        viewport.classList.remove('is-dragging');
        startInertia();
    });

    // ── Inertia ──
    function startInertia() {
        if (animFrame) cancelAnimationFrame(animFrame);
        function step() {
            if (Math.abs(velocityX) < 0.5 && Math.abs(velocityY) < 0.5) return;
            velocityX *= 0.92; velocityY *= 0.92;
            panX += velocityX; panY += velocityY;
            render();
            animFrame = requestAnimationFrame(step);
        }
        animFrame = requestAnimationFrame(step);
    }

    // ── Filter chips ──
    document.querySelectorAll('.section-filters').forEach(filterGroup => {
        const target = filterGroup.dataset.target;
        const row = document.querySelector(`[data-row="${target}"]`);
        if (!row) return;

        filterGroup.querySelectorAll('.filter-chip').forEach(chip => {
            chip.addEventListener('click', (e) => {
                e.stopPropagation();
                const filter = chip.dataset.filter;

                // Update active chip
                filterGroup.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');

                // Filter cards
                row.querySelectorAll('.canvas-card').forEach(card => {
                    if (filter === '*' || card.dataset.type === filter) {
                        card.classList.remove('filtered-out');
                    } else {
                        card.classList.add('filtered-out');
                    }
                });

                // Update count
                const section = filterGroup.closest('.canvas-section');
                const countEl = section.querySelector('.section-count');
                if (countEl) {
                    const visible = row.querySelectorAll('.canvas-card:not(.filtered-out)').length;
                    countEl.textContent = visible;
                }
            });
        });
    });

    // ── Search inputs ──
    document.querySelectorAll('.section-search input').forEach(input => {
        const target = input.dataset.search;
        const row = document.querySelector(`[data-row="${target}"]`);
        if (!row) return;

        input.addEventListener('input', (e) => {
            e.stopPropagation();
            const q = input.value.toLowerCase().trim();

            row.querySelectorAll('.canvas-card').forEach(card => {
                if (!q || (card.dataset.searchable && card.dataset.searchable.includes(q))) {
                    card.classList.remove('filtered-out');
                } else {
                    card.classList.add('filtered-out');
                }
            });

            const section = input.closest('.canvas-section');
            const countEl = section.querySelector('.section-count');
            if (countEl) {
                const visible = row.querySelectorAll('.canvas-card:not(.filtered-out)').length;
                countEl.textContent = visible;
            }
        });

        // Prevent drag when typing
        input.addEventListener('mousedown', (e) => e.stopPropagation());
        input.addEventListener('touchstart', (e) => e.stopPropagation());
    });

    // ── Sort buttons (toggle reverse) ──
    document.querySelectorAll('.section-sort').forEach(btn => {
        let reversed = false;
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const target = btn.dataset.sort;
            const row = document.querySelector(`[data-row="${target}"]`);
            if (!row) return;

            const cards = Array.from(row.querySelectorAll('.canvas-card'));
            cards.reverse();
            cards.forEach(card => row.appendChild(card));
            reversed = !reversed;
            btn.style.opacity = reversed ? '0.6' : '1';
        });
    });

    // Prevent drag when clicking toolbar buttons
    document.querySelectorAll('.filter-chip, .section-sort').forEach(el => {
        el.addEventListener('mousedown', (e) => e.stopPropagation());
    });

    // ── Tab switching ──
    const tabButtons = document.querySelectorAll('.tg-tab');
    const sections = document.querySelectorAll('.canvas-section[data-tab]');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;

            tabButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            sections.forEach(section => {
                const sectionTab = section.dataset.tab;
                if (tab === 'oversikt') {
                    section.style.display = sectionTab === 'all' ? 'block' : 'none';
                    section.classList.toggle('visible', sectionTab === 'all');
                } else {
                    const show = sectionTab === tab;
                    section.style.display = show ? 'block' : 'none';
                    section.classList.toggle('visible', show);
                }
            });

            // Reset pan
            panX = 0; panY = 0;
            render();
        });
    });

    // ── View toggle (canvas ↔ grid) ──
    const toggleBtns = document.querySelectorAll('.view-toggle-btn');

    toggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const view = btn.dataset.view;
            if (view === currentView) return;
            currentView = view;

            toggleBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            if (view === 'grid') {
                // Reset pan position before switching
                panX = 0; panY = 0;
                render();
                viewport.classList.add('grid-mode');
            } else {
                viewport.classList.remove('grid-mode');
                alignWithHeader();
                render();
            }
        });
    });

    // ── Init ──
    render();
    setTimeout(() => { if (!hintDismissed) { hint.classList.add('hidden'); hintDismissed = true; } }, 4000);

})();
</script>

<?php get_footer(); ?>
