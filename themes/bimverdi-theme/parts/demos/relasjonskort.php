<?php
/**
 * Entity Relationship Cards Demo
 * Shows how entities in a temagruppe cross-reference each other via interactive cards.
 *
 * Loaded by single-demo.php via get_template_part().
 * Header is already loaded by the parent template.
 */

// ─── Data Loading ───────────────────────────────────────────────────────────

// Get all theme_groups
$theme_groups = get_posts([
    'post_type'      => 'theme_group',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'post_status'    => 'publish',
]);

// Selected temagruppe (from query param or first available)
$selected_id = isset($_GET['temagruppe']) ? intval($_GET['temagruppe']) : 0;
if ($selected_id === 0 && !empty($theme_groups)) {
    $selected_id = $theme_groups[0]->ID;
}

$selected_tg = $selected_id ? get_post($selected_id) : null;

// ─── Query entities for the selected temagruppe ────────────────────────────

$entities = [
    'foretak'        => ['label' => 'Foretak',         'color' => '#3B82F6', 'icon' => 'building-2', 'items' => []],
    'verktoy'        => ['label' => 'Verktoy',         'color' => '#8B5CF6', 'icon' => 'wrench',     'items' => []],
    'kunnskapskilde' => ['label' => 'Kunnskapskilder', 'color' => '#10B981', 'icon' => 'book-open',  'items' => []],
    'arrangement'    => ['label' => 'Arrangementer',   'color' => '#F59E0B', 'icon' => 'calendar',   'items' => []],
];

// Build a map of post_id => [connected post_ids] across ALL entity types
$connection_map = [];

if ($selected_tg) {
    // Find the matching taxonomy term by name
    $term = get_term_by('name', $selected_tg->post_title, 'temagruppe');

    if ($term && !is_wp_error($term)) {
        $term_id = $term->term_id;

        // Query foretak, kunnskapskilde, arrangement via taxonomy
        $tax_post_types = ['foretak', 'kunnskapskilde', 'arrangement'];
        foreach ($tax_post_types as $pt) {
            $posts = get_posts([
                'post_type'      => $pt,
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'tax_query'      => [
                    [
                        'taxonomy' => 'temagruppe',
                        'field'    => 'term_id',
                        'terms'    => $term_id,
                    ],
                ],
            ]);
            foreach ($posts as $p) {
                $entities[$pt]['items'][] = [
                    'id'    => $p->ID,
                    'title' => $p->post_title,
                    'url'   => get_permalink($p->ID),
                    'type'  => $pt,
                ];
            }
        }

        // Query verktoy - try taxonomy first, then fallback to ACF meta
        $verktoy_posts = get_posts([
            'post_type'      => 'verktoy',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'tax_query'      => [
                [
                    'taxonomy' => 'temagruppe',
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                ],
            ],
        ]);

        // Also try ACF field 'formaalstema' as fallback
        if (empty($verktoy_posts)) {
            $verktoy_posts = get_posts([
                'post_type'      => 'verktoy',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'meta_query'     => [
                    [
                        'key'     => 'formaalstema',
                        'value'   => $term_id,
                        'compare' => 'LIKE',
                    ],
                ],
            ]);
        }

        foreach ($verktoy_posts as $p) {
            // Avoid duplicates
            $exists = false;
            foreach ($entities['verktoy']['items'] as $existing) {
                if ($existing['id'] === $p->ID) { $exists = true; break; }
            }
            if (!$exists) {
                $entities['verktoy']['items'][] = [
                    'id'    => $p->ID,
                    'title' => $p->post_title,
                    'url'   => get_permalink($p->ID),
                    'type'  => 'verktoy',
                ];
            }
        }

        // ─── Build connection map ──────────────────────────────────────────
        // Entities that share the same temagruppe term are inherently connected.
        // Additionally, we check for direct ACF relationships.

        // Collect all entity IDs
        $all_ids = [];
        foreach ($entities as $type => $data) {
            foreach ($data['items'] as $item) {
                $all_ids[] = $item['id'];
            }
        }

        // All entities in the same temagruppe are connected to each other
        foreach ($all_ids as $id) {
            $connection_map[$id] = array_filter($all_ids, fn($other) => $other !== $id);
        }

        // Foretak <-> Verktoy: check ACF 'tilknyttet_foretak' on verktoy
        foreach ($entities['verktoy']['items'] as $tool) {
            $foretak_id = get_field('tilknyttet_foretak', $tool['id']);
            if ($foretak_id) {
                $fid = is_object($foretak_id) ? $foretak_id->ID : intval($foretak_id);
                if (!in_array($fid, $connection_map[$tool['id']] ?? [])) {
                    $connection_map[$tool['id']][] = $fid;
                }
                if (!in_array($tool['id'], $connection_map[$fid] ?? [])) {
                    $connection_map[$fid][] = $tool['id'];
                }
            }
        }
    }
}

// ─── Fallback mock data if nothing found ────────────────────────────────────

$has_real_data = false;
foreach ($entities as $data) {
    if (!empty($data['items'])) { $has_real_data = true; break; }
}

if (!$has_real_data) {
    // Generate mock data for demo purposes
    $mock_foretak = [
        ['id' => 'mock-f1', 'title' => 'Multiconsult',         'type' => 'foretak',        'url' => '#'],
        ['id' => 'mock-f2', 'title' => 'Norconsult',           'type' => 'foretak',        'url' => '#'],
        ['id' => 'mock-f3', 'title' => 'Ramboll',              'type' => 'foretak',        'url' => '#'],
        ['id' => 'mock-f4', 'title' => 'COWI',                 'type' => 'foretak',        'url' => '#'],
    ];
    $mock_verktoy = [
        ['id' => 'mock-v1', 'title' => 'Solibri',              'type' => 'verktoy',        'url' => '#'],
        ['id' => 'mock-v2', 'title' => 'Revit',                'type' => 'verktoy',        'url' => '#'],
        ['id' => 'mock-v3', 'title' => 'BIMcollab',            'type' => 'verktoy',        'url' => '#'],
    ];
    $mock_kilder = [
        ['id' => 'mock-k1', 'title' => 'ISO 19650',            'type' => 'kunnskapskilde', 'url' => '#'],
        ['id' => 'mock-k2', 'title' => 'BIM-manual 2.0',       'type' => 'kunnskapskilde', 'url' => '#'],
    ];
    $mock_arr = [
        ['id' => 'mock-a1', 'title' => 'BIMtech april 2025',   'type' => 'arrangement',    'url' => '#'],
        ['id' => 'mock-a2', 'title' => 'Workshop: openBIM',    'type' => 'arrangement',    'url' => '#'],
    ];

    $entities['foretak']['items']        = $mock_foretak;
    $entities['verktoy']['items']        = $mock_verktoy;
    $entities['kunnskapskilde']['items'] = $mock_kilder;
    $entities['arrangement']['items']    = $mock_arr;

    // Mock connections: Multiconsult uses Solibri+Revit, references ISO 19650, attended BIMtech
    $connection_map = [
        'mock-f1' => ['mock-v1', 'mock-v2', 'mock-k1', 'mock-a1'],
        'mock-f2' => ['mock-v2', 'mock-v3', 'mock-k2', 'mock-a1', 'mock-a2'],
        'mock-f3' => ['mock-v1', 'mock-v3', 'mock-k1', 'mock-k2', 'mock-a2'],
        'mock-f4' => ['mock-v2', 'mock-k1', 'mock-a1'],
        'mock-v1' => ['mock-f1', 'mock-f3', 'mock-k1'],
        'mock-v2' => ['mock-f1', 'mock-f2', 'mock-f4', 'mock-k2'],
        'mock-v3' => ['mock-f2', 'mock-f3'],
        'mock-k1' => ['mock-f1', 'mock-f3', 'mock-f4', 'mock-v1'],
        'mock-k2' => ['mock-f2', 'mock-f3', 'mock-v2'],
        'mock-a1' => ['mock-f1', 'mock-f2', 'mock-f4'],
        'mock-a2' => ['mock-f2', 'mock-f3'],
    ];
}

// ─── Stats ─────────────────────────────────────────────────────────────────

$stats = [];
foreach ($entities as $type => $data) {
    $stats[$type] = count($data['items']);
}
$total_connections = 0;
foreach ($connection_map as $conns) {
    $total_connections += count($conns);
}
$total_connections = intval($total_connections / 2); // bidirectional

// Encode data for JS
$js_connections = json_encode($connection_map);

// Build a type lookup for JS
$type_lookup = [];
foreach ($entities as $type => $data) {
    foreach ($data['items'] as $item) {
        $type_lookup[$item['id']] = $type;
    }
}
$js_type_lookup = json_encode($type_lookup);

// Color map for JS
$js_colors = json_encode([
    'foretak'        => '#3B82F6',
    'verktoy'        => '#8B5CF6',
    'kunnskapskilde' => '#10B981',
    'arrangement'    => '#F59E0B',
]);
?>

<main class="bv-relasjon-demo" style="background:#FAFAF9; min-height:100vh;">

<style>
/* ─── Base ──────────────────────────────────────────────────────────────── */
.bv-relasjon-demo {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #1A1A1A;
}
.bv-relasjon-demo *, .bv-relasjon-demo *::before, .bv-relasjon-demo *::after {
    box-sizing: border-box;
}

/* ─── Header ────────────────────────────────────────────────────────────── */
.bvr-header {
    max-width: 1280px;
    margin: 0 auto;
    padding: 2rem 1.5rem 0;
}
.bvr-back {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: #FF8B5E;
    text-decoration: none;
    transition: color 0.15s;
}
.bvr-back:hover { color: #e67a4d; text-decoration: underline; }
.bvr-back svg { width: 1rem; height: 1rem; }

.bvr-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1A1A1A;
    margin: 1rem 0 0.5rem;
    line-height: 1.2;
}
.bvr-subtitle {
    font-size: 1.05rem;
    color: #5A5A5A;
    max-width: 680px;
    line-height: 1.6;
    margin: 0 0 1.5rem;
}

/* ─── Toolbar ───────────────────────────────────────────────────────────── */
.bvr-toolbar {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem 1.5rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 1rem;
}
.bvr-selector {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.bvr-selector label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1A1A1A;
    white-space: nowrap;
}
.bvr-selector select {
    padding: 0.5rem 2rem 0.5rem 0.75rem;
    border: 1px solid #E7E5E4;
    border-radius: 0.5rem;
    background: #fff;
    font-size: 0.875rem;
    color: #1A1A1A;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%235A5A5A' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.5rem center;
    min-width: 200px;
    transition: border-color 0.15s;
}
.bvr-selector select:hover { border-color: #FF8B5E; }
.bvr-selector select:focus { outline: none; border-color: #FF8B5E; box-shadow: 0 0 0 3px rgba(255,139,94,0.15); }

/* ─── Stats bar ─────────────────────────────────────────────────────────── */
.bvr-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-left: auto;
}
.bvr-stat {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 2rem;
    font-size: 0.8rem;
    font-weight: 600;
    background: #fff;
    border: 1px solid #E7E5E4;
}
.bvr-stat .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* ─── Mock data banner ──────────────────────────────────────────────────── */
.bvr-mock-banner {
    max-width: 1280px;
    margin: 0 auto 1rem;
    padding: 0 1.5rem;
}
.bvr-mock-banner-inner {
    background: #FFF7ED;
    border: 1px solid #FDBA74;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-size: 0.85rem;
    color: #9A3412;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* ─── Grid ──────────────────────────────────────────────────────────────── */
.bvr-grid {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem 3rem;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
}

/* ─── Section ───────────────────────────────────────────────────────────── */
.bvr-section {
    min-width: 0;
}
.bvr-section-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    padding-bottom: 0.625rem;
    border-bottom: 2px solid var(--section-color);
}
.bvr-section-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--section-color);
    color: #fff;
    flex-shrink: 0;
}
.bvr-section-icon svg { width: 1rem; height: 1rem; }
.bvr-section-label {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1A1A1A;
}
.bvr-section-count {
    margin-left: auto;
    font-size: 0.75rem;
    font-weight: 600;
    color: #5A5A5A;
    background: #F5F5F4;
    padding: 0.125rem 0.5rem;
    border-radius: 1rem;
}

/* ─── Cards ─────────────────────────────────────────────────────────────── */
.bvr-cards {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.bvr-card {
    background: #fff;
    border: 1px solid #E7E5E4;
    border-left: 3px solid transparent;
    border-radius: 0.625rem;
    padding: 0.875rem 1rem;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}
.bvr-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--section-color);
    opacity: 0;
    transition: opacity 0.25s;
    pointer-events: none;
}
.bvr-card:hover {
    border-color: #D6D3D1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transform: translateY(-1px);
}
.bvr-card-top {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    position: relative;
    z-index: 1;
}
.bvr-card-icon {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.25s;
}
.bvr-card-icon svg { width: 1.125rem; height: 1.125rem; }
.bvr-card-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1A1A1A;
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.bvr-card-badge {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.125rem 0.5rem;
    border-radius: 1rem;
    white-space: nowrap;
    transition: all 0.25s;
}
.bvr-card-connections {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #78716C;
    margin-left: 0.5rem;
    white-space: nowrap;
    flex-shrink: 0;
}
.bvr-card-connections svg { width: 0.875rem; height: 0.875rem; }

/* Card details (expanded) */
.bvr-card-details {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1), padding 0.35s, opacity 0.25s;
    opacity: 0;
    position: relative;
    z-index: 1;
}
.bvr-card-details-inner {
    padding-top: 0.75rem;
    margin-top: 0.75rem;
    border-top: 1px solid #F5F5F4;
}
.bvr-card-details h4 {
    font-size: 0.75rem;
    font-weight: 600;
    color: #78716C;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0 0 0.5rem;
}
.bvr-conn-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
}
.bvr-conn-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.625rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: #1A1A1A;
    background: #F5F5F4;
    border: 1px solid #E7E5E4;
    transition: all 0.2s;
    cursor: pointer;
    text-decoration: none;
}
.bvr-conn-chip .conn-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}
.bvr-conn-chip:hover {
    background: #EDEDED;
    border-color: #D6D3D1;
}
.bvr-card-link {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    margin-top: 0.625rem;
    font-size: 0.8rem;
    font-weight: 500;
    color: #FF8B5E;
    text-decoration: none;
    transition: color 0.15s;
}
.bvr-card-link:hover { color: #e67a4d; text-decoration: underline; }
.bvr-card-link svg { width: 0.875rem; height: 0.875rem; }

/* ─── Card states ───────────────────────────────────────────────────────── */

/* Active card */
.bvr-card.is-active {
    border-left-color: var(--section-color);
    border-color: var(--section-color);
    box-shadow: 0 4px 20px rgba(0,0,0,0.08), 0 0 0 1px var(--section-color);
    transform: translateY(-2px);
}
.bvr-card.is-active::before {
    opacity: 0.04;
}
.bvr-card.is-active .bvr-card-details {
    max-height: 300px;
    opacity: 1;
}

/* Connected card (highlighted) */
.bvr-card.is-connected {
    border-left-color: var(--section-color);
    background: #FEFEFE;
    box-shadow: 0 1px 6px rgba(0,0,0,0.05);
}
.bvr-card.is-connected::before {
    opacity: 0.025;
}

/* Dimmed card (not connected) */
.bvr-card.is-dimmed {
    opacity: 0.35;
    transform: scale(0.98);
    filter: grayscale(0.3);
}
.bvr-card.is-dimmed:hover {
    opacity: 0.6;
    transform: scale(0.99);
}

/* ─── Connection pulse animation ────────────────────────────────────────── */
@keyframes pulse-border {
    0%, 100% { box-shadow: 0 0 0 0 var(--section-color); }
    50% { box-shadow: 0 0 0 3px color-mix(in srgb, var(--section-color) 20%, transparent); }
}
.bvr-card.is-connected {
    animation: pulse-border 2s ease-in-out infinite;
}

/* ─── Empty state ───────────────────────────────────────────────────────── */
.bvr-empty {
    text-align: center;
    padding: 3rem 1.5rem;
    color: #78716C;
    font-size: 0.9rem;
    grid-column: 1 / -1;
}
.bvr-empty svg {
    width: 3rem;
    height: 3rem;
    color: #D6D3D1;
    margin-bottom: 1rem;
}

/* ─── Responsive ────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
    .bvr-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    .bvr-toolbar {
        flex-direction: column;
        align-items: flex-start;
    }
    .bvr-stats {
        margin-left: 0;
    }
    .bvr-title {
        font-size: 1.5rem;
    }
}
@media (max-width: 600px) {
    .bvr-header { padding: 1.25rem 1rem 0; }
    .bvr-toolbar { padding: 0 1rem 1rem; }
    .bvr-grid { padding: 0 1rem 2rem; }
    .bvr-selector select { min-width: 0; width: 100%; }
    .bvr-stats { width: 100%; }
    .bvr-stat { flex: 1; justify-content: center; }
}
</style>

<!-- ─── Header ──────────────────────────────────────────────────────────── -->
<div class="bvr-header">
    <a href="<?php echo esc_url(get_post_type_archive_link('demo')); ?>" class="bvr-back">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Alle demoer
    </a>
    <h1 class="bvr-title">Entity Relasjonskort</h1>
    <p class="bvr-subtitle">
        Utforsk hvordan foretak, verktoy, kunnskapskilder og arrangementer henger sammen innenfor en temagruppe.
        Klikk pa et kort for a se alle tilknytninger.
    </p>
</div>

<!-- ─── Toolbar ─────────────────────────────────────────────────────────── -->
<div class="bvr-toolbar">
    <div class="bvr-selector">
        <label for="bvr-tg-select">Temagruppe:</label>
        <select id="bvr-tg-select" onchange="window.location.search='?temagruppe='+this.value">
            <?php if (empty($theme_groups)) : ?>
                <option value="">Ingen temagrupper funnet</option>
            <?php else : ?>
                <?php foreach ($theme_groups as $tg) : ?>
                    <option value="<?php echo esc_attr($tg->ID); ?>" <?php selected($selected_id, $tg->ID); ?>>
                        <?php echo esc_html($tg->post_title); ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <div class="bvr-stats">
        <?php foreach ($entities as $type => $data) : ?>
            <div class="bvr-stat">
                <span class="dot" style="background:<?php echo esc_attr($data['color']); ?>"></span>
                <?php echo count($data['items']); ?> <?php echo esc_html($data['label']); ?>
            </div>
        <?php endforeach; ?>
        <div class="bvr-stat">
            <span class="dot" style="background:#FF8B5E"></span>
            <?php echo $total_connections; ?> koblinger
        </div>
    </div>
</div>

<!-- ─── Mock data notice ────────────────────────────────────────────────── -->
<?php if (!$has_real_data) : ?>
<div class="bvr-mock-banner">
    <div class="bvr-mock-banner-inner">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Viser demo-data. Opprett temagrupper med tilknyttede foretak, verktoy, kunnskapskilder og arrangementer for a se ekte relasjoner.
    </div>
</div>
<?php endif; ?>

<!-- ─── Entity Grid ─────────────────────────────────────────────────────── -->
<div class="bvr-grid" id="bvr-grid">
    <?php foreach ($entities as $type => $data) : ?>
        <?php if (empty($data['items'])) continue; ?>
        <div class="bvr-section" style="--section-color:<?php echo esc_attr($data['color']); ?>">
            <div class="bvr-section-header">
                <div class="bvr-section-icon">
                    <?php echo bvr_get_icon($data['icon']); ?>
                </div>
                <span class="bvr-section-label"><?php echo esc_html($data['label']); ?></span>
                <span class="bvr-section-count"><?php echo count($data['items']); ?></span>
            </div>
            <div class="bvr-cards">
                <?php foreach ($data['items'] as $item) :
                    $conn_count = count($connection_map[$item['id']] ?? []);
                    $conn_ids = $connection_map[$item['id']] ?? [];
                ?>
                    <div class="bvr-card"
                         data-id="<?php echo esc_attr($item['id']); ?>"
                         data-type="<?php echo esc_attr($type); ?>"
                         data-connections="<?php echo esc_attr(json_encode(array_values($conn_ids))); ?>"
                         style="--section-color:<?php echo esc_attr($data['color']); ?>">
                        <div class="bvr-card-top">
                            <div class="bvr-card-icon" style="background:<?php echo esc_attr($data['color']); ?>12; color:<?php echo esc_attr($data['color']); ?>">
                                <?php echo bvr_get_icon($data['icon']); ?>
                            </div>
                            <span class="bvr-card-name"><?php echo esc_html($item['title']); ?></span>
                            <span class="bvr-card-badge" style="background:<?php echo esc_attr($data['color']); ?>12; color:<?php echo esc_attr($data['color']); ?>">
                                <?php echo esc_html($data['label']); ?>
                            </span>
                            <?php if ($conn_count > 0) : ?>
                                <span class="bvr-card-connections">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                                    <?php echo $conn_count; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="bvr-card-details">
                            <div class="bvr-card-details-inner">
                                <h4>Tilknytninger</h4>
                                <div class="bvr-conn-list" data-conn-list></div>
                                <?php if ($item['url'] && $item['url'] !== '#') : ?>
                                    <a href="<?php echo esc_url($item['url']); ?>" class="bvr-card-link">
                                        Vis detaljer
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php
    $all_empty = true;
    foreach ($entities as $data) { if (!empty($data['items'])) { $all_empty = false; break; } }
    if ($all_empty) :
    ?>
        <div class="bvr-empty">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            <p>Ingen enheter funnet for denne temagruppen.</p>
        </div>
    <?php endif; ?>
</div>

<script>
(function() {
    'use strict';

    // ─── Data from PHP ─────────────────────────────────────────────────────
    var connections = <?php echo $js_connections; ?>;
    var typeLookup  = <?php echo $js_type_lookup; ?>;
    var colors      = <?php echo $js_colors; ?>;

    // ─── Build a title lookup from the DOM ──────────────────────────────────
    var titleLookup = {};
    document.querySelectorAll('.bvr-card').forEach(function(card) {
        var id = card.dataset.id;
        var name = card.querySelector('.bvr-card-name');
        if (name) titleLookup[id] = name.textContent.trim();
    });

    // ─── State ─────────────────────────────────────────────────────────────
    var activeId = null;

    // ─── Card click handler ────────────────────────────────────────────────
    document.querySelectorAll('.bvr-card').forEach(function(card) {
        card.addEventListener('click', function(e) {
            // Don't intercept link clicks
            if (e.target.closest('a')) return;

            var id = card.dataset.id;

            if (activeId === id) {
                // Deselect
                resetAll();
                return;
            }

            activateCard(id);
        });
    });

    // ─── Click background to reset ─────────────────────────────────────────
    document.querySelector('.bvr-grid').addEventListener('click', function(e) {
        if (!e.target.closest('.bvr-card')) {
            resetAll();
        }
    });

    // ─── Keyboard support ──────────────────────────────────────────────────
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') resetAll();
    });

    // ─── Activate a card ───────────────────────────────────────────────────
    function activateCard(id) {
        activeId = id;
        var connectedIds = connections[id] || [];
        var connSet = {};
        connectedIds.forEach(function(c) { connSet[c] = true; });

        document.querySelectorAll('.bvr-card').forEach(function(card) {
            var cid = card.dataset.id;
            card.classList.remove('is-active', 'is-connected', 'is-dimmed');

            if (cid === id) {
                card.classList.add('is-active');
                // Populate connection chips
                populateConnections(card, connectedIds);
                // Smooth scroll on mobile
                if (window.innerWidth <= 900) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            } else if (connSet[cid]) {
                card.classList.add('is-connected');
            } else {
                card.classList.add('is-dimmed');
            }
        });
    }

    // ─── Populate connection chips ─────────────────────────────────────────
    function populateConnections(card, connectedIds) {
        var list = card.querySelector('[data-conn-list]');
        if (!list) return;

        list.innerHTML = '';

        // Group by type
        var grouped = {};
        connectedIds.forEach(function(cid) {
            var type = typeLookup[cid] || 'unknown';
            if (!grouped[type]) grouped[type] = [];
            grouped[type].push(cid);
        });

        // Render chips
        var typeOrder = ['foretak', 'verktoy', 'kunnskapskilde', 'arrangement'];
        typeOrder.forEach(function(type) {
            if (!grouped[type]) return;
            grouped[type].forEach(function(cid) {
                var chip = document.createElement('span');
                chip.className = 'bvr-conn-chip';
                chip.innerHTML = '<span class="conn-dot" style="background:' + (colors[type] || '#999') + '"></span>' +
                    (titleLookup[cid] || cid);
                chip.addEventListener('click', function(e) {
                    e.stopPropagation();
                    activateCard(cid);
                    // Scroll to the target card
                    var target = document.querySelector('.bvr-card[data-id="' + cid + '"]');
                    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
                list.appendChild(chip);
            });
        });

        if (connectedIds.length === 0) {
            list.innerHTML = '<span style="font-size:0.8rem;color:#A8A29E;">Ingen koblinger</span>';
        }
    }

    // ─── Reset all cards ───────────────────────────────────────────────────
    function resetAll() {
        activeId = null;
        document.querySelectorAll('.bvr-card').forEach(function(card) {
            card.classList.remove('is-active', 'is-connected', 'is-dimmed');
        });
    }

})();
</script>

</main>

<?php
// ─── SVG Icon Helper ──────────────────────────────────────────────────────

function bvr_get_icon($name) {
    $icons = [
        'building-2'  => '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 012-2h8a2 2 0 012 2v18Z"/><path d="M6 12H4a2 2 0 00-2 2v6a2 2 0 002 2h2"/><path d="M18 9h2a2 2 0 012 2v9a2 2 0 01-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>',
        'wrench'      => '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>',
        'book-open'   => '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>',
        'calendar'    => '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
    ];
    return $icons[$name] ?? '';
}
