<?php
/**
 * Archive template for Verktøy (Tools) CPT
 *
 * Public tools/software catalog with BIM Verdi design.
 * Clean, minimal styling following UI Contract v1.
 * Updated 2026-02-03: Replaced checkbox filters with compact dropdown filter bar.
 *
 * @package BimVerdi_Theme
 */

get_header();

// Include filter bar component
require_once get_template_directory() . '/parts/components/filter-bar.php';

// Get filter parameters
$search = sanitize_text_field($_GET['s'] ?? '');
// Temagruppe-fasetten filtrerer nå på `temagruppe`-TAKSONOMIEN (slugs), ikke `formaalstema`-meta.
$temagruppe_sel = isset($_GET['temagruppe']) && is_array($_GET['temagruppe'])
    ? array_map('sanitize_text_field', $_GET['temagruppe'])
    : array();
$type_ressurs = isset($_GET['type_ressurs']) && is_array($_GET['type_ressurs'])
    ? array_map('sanitize_text_field', $_GET['type_ressurs'])
    : array();

// Temagruppe-fasett = de 6 offisielle taksonomi-termene (ekskl. «Midlertidig»).
// Navn hentes fra taksonomien (ett sannhetspunkt); rekkefølgen er fast (merkevare).
$tg_order = array('byggesaksbim', 'prosjektbim', 'eiendomsbim', 'miljobim', 'sirkbim', 'bimtech');
$tg_terms = get_terms(array('taxonomy' => 'temagruppe', 'hide_empty' => false));
$tg_by_slug = array();
if (!is_wp_error($tg_terms)) {
    foreach ($tg_terms as $t) { $tg_by_slug[$t->slug] = $t->name; }
}
$temagruppe_options = array(); // slug => visningsnavn
foreach ($tg_order as $s) {
    if (isset($tg_by_slug[$s])) { $temagruppe_options[$s] = $tg_by_slug[$s]; }
}
$valid_temagruppe_slugs = array_keys($temagruppe_options);

// Dual-source-shim: legacy `formaalstema`-meta (kort-nøkler) → temagruppe-slug.
// Medlems-verktøy lagrer tema her; AEC-verktøy har ekte taksonomi-termer. Begge slås sammen.
$formaal_meta_map = array(
    'byggesak'          => 'byggesaksbim',
    'prosjekt'          => 'prosjektbim',
    'prosjektutvikling' => 'prosjektbim',
    'eiendom'           => 'eiendomsbim',
    'miljo'             => 'miljobim',
    'sirk'              => 'sirkbim',
);

/**
 * Union av temagruppe-slugs for et verktøy: ekte taksonomi-termer + mappet legacy-meta.
 * Holder Temagruppe-filteret korrekt for BÅDE AEC-importerte (termer) og medlems-verktøy (meta)
 * uten datamutasjon. Ugyldige/ukjente verdier ignoreres (vises ikke i fasetten).
 */
if (!function_exists('bv_verktoy_temagruppe_slugs')) {
    function bv_verktoy_temagruppe_slugs($post_id, array $meta_map, array $valid_slugs) {
        $slugs = array();
        $terms = wp_get_post_terms($post_id, 'temagruppe', array('fields' => 'slugs'));
        if (!is_wp_error($terms)) {
            foreach ($terms as $s) {
                if (in_array($s, $valid_slugs, true) && !in_array($s, $slugs, true)) {
                    $slugs[] = $s;
                }
            }
        }
        $meta = get_field('formaalstema', $post_id);
        if (!empty($meta)) {
            $vals = is_array($meta) ? $meta : array($meta);
            foreach ($vals as $v) {
                $key = strtolower(trim((string) $v));
                if (isset($meta_map[$key]) && !in_array($meta_map[$key], $slugs, true)) {
                    $slugs[] = $meta_map[$key];
                }
            }
        }
        return $slugs;
    }
}

$type_ressurs_options = array(
    'Programvare' => 'Programvare',
    'Standard' => 'Standard',
    'Metodikk' => 'Metodikk',
    'Veileder' => 'Veileder',
    'Nettside' => 'Nettside',
    'Digital_tjeneste' => 'Digital tjeneste',
);

// Build query
$args = array(
    'post_type' => 'verktoy',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'post_status' => 'publish',
);

if (!empty($search)) {
    $args['s'] = $search;
}

// Temagruppe filtreres klient-side (dual-source: taksonomi + legacy-meta), på linje med Kilde.
// Kun type_ressurs pre-filtreres server-side for ev. no-JS / dyplenking.
if (!empty($type_ressurs)) {
    $args['meta_query'] = array(
        array(
            'key' => 'type_ressurs',
            'value' => $type_ressurs,
            'compare' => 'IN',
        ),
    );
}

$tools_query = new WP_Query($args);
$is_logged_in = is_user_logged_in();

// Temagruppe-tellinger: union-slugs (taksonomi + legacy-meta) over ALLE publiserte verktøy,
// så tallene matcher nøyaktig hva klient-side-filteret viser.
$temagruppe_counts = array_fill_keys($valid_temagruppe_slugs, 0);
$tg_count_ids = get_posts(array(
    'post_type'      => 'verktoy',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
));
foreach ($tg_count_ids as $tg_id) {
    foreach (bv_verktoy_temagruppe_slugs($tg_id, $formaal_meta_map, $valid_temagruppe_slugs) as $s) {
        if (isset($temagruppe_counts[$s])) { $temagruppe_counts[$s]++; }
    }
}

$type_ressurs_counts = array();
foreach (array_keys($type_ressurs_options) as $value) {
    $search_value = str_replace('_', ' ', $value); // Digital_tjeneste → Digital tjeneste
    $count_query = new WP_Query([
        'post_type' => 'verktoy',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [[
            'key' => 'type_ressurs',
            'value' => $search_value,
            'compare' => 'LIKE',
        ]],
    ]);
    $type_ressurs_counts[$value] = $count_query->found_posts;
}

// Kilde-fasett (AEC AI Hub-synket vs medlemsregistrert) — klient-side filter på data-kilde.
$kilde_options = [
    'aec_ai_hub' => 'AEC AI Hub',
    'medlem'     => 'Medlemsregistrert',
];
$selected_kilde = isset($_GET['kilde']) ? array_map('sanitize_text_field', (array) $_GET['kilde']) : [];
$aec_count = (new WP_Query([
    'post_type'      => 'verktoy',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'no_found_rows'  => false,
    'meta_query'     => [['key' => '_bv_aec_source', 'compare' => 'EXISTS']],
]))->found_posts;
$total_pub = (new WP_Query([
    'post_type'      => 'verktoy',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
]))->found_posts;
$kilde_counts = [
    'aec_ai_hub' => $aec_count,
    'medlem'     => max(0, $total_pub - $aec_count),
];
?>

<div class="min-h-screen bg-white">

    <?php get_template_part('parts/components/archive-intro', null, [
        'acf_prefix'       => 'verktoy',
        'fallback_title'   => 'Verktøykatalog',
        'fallback_ingress' => 'Digitale verktøy og løsninger fra BIM Verdi-nettverket.',
        'count'            => $tools_query->found_posts,
        'count_label'      => 'verktøy',
        'tag_cloud'        => [
            'meta_filters' => [
                ['options' => $temagruppe_options, 'filter_class' => 'filter-temagruppe'],
                ['options' => $type_ressurs_options, 'filter_class' => 'filter-type'],
            ],
            'max_tags' => 12,
        ],
    ]); ?>

    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Compact Filter Bar -->
        <?php
        bimverdi_filter_bar([
            'form_id'            => 'verktoy-filter-form',
            'search_name'        => 's',
            'search_value'       => $search,
            'search_placeholder' => 'Søk etter verktøy...',
            'dropdowns'          => [
                [
                    'name'         => 'temagruppe[]',
                    'label'        => 'Temagruppe',
                    'options'      => $temagruppe_options,
                    'selected'     => $temagruppe_sel,
                    'counts'       => $temagruppe_counts,
                    'filter_class' => 'filter-temagruppe',
                ],
                [
                    'name'         => 'type_ressurs[]',
                    'label'        => 'Type',
                    'options'      => $type_ressurs_options,
                    'selected'     => $type_ressurs,
                    'counts'       => $type_ressurs_counts,
                    'filter_class' => 'filter-type',
                ],
                [
                    'name'         => 'kilde[]',
                    'label'        => 'Kilde',
                    'options'      => $kilde_options,
                    'selected'     => $selected_kilde,
                    'counts'       => $kilde_counts,
                    'filter_class' => 'filter-kilde',
                ],
            ],
            'result_count'       => $tools_query->found_posts,
            'total_count'        => $tools_query->found_posts,
            'result_label'       => 'verktøy',
            'reset_id'           => 'reset-filters',
            'view_toggle'        => [
                'storage_key' => 'bv-view-verktoy',
                'grid_id'     => 'verktoy-grid',
                'list_id'     => 'verktoy-list',
            ],
        ]);
        ?>

        <!-- Tools Grid & List -->
        <?php if ($tools_query->have_posts()):

        // Collect post data for dual rendering
        $items = [];
        while ($tools_query->have_posts()): $tools_query->the_post();
            $eier_id = get_field('eier_leverandor', get_the_ID());
            $eier = $eier_id ? get_post($eier_id) : null;
            $type_raw = get_field('type_ressurs', get_the_ID());

            // Temagruppe-slugs (dual-source: taksonomi + legacy-meta) + visningsnavn.
            $tg_slugs  = bv_verktoy_temagruppe_slugs(get_the_ID(), $formaal_meta_map, $valid_temagruppe_slugs);
            $tg_names  = array();
            foreach ($tg_slugs as $s) {
                if (isset($temagruppe_options[$s])) { $tg_names[] = $temagruppe_options[$s]; }
            }

            // Normalize type_ressurs
            $type_tags = [];
            $type_str = is_array($type_raw) ? implode(', ', $type_raw) : (string) $type_raw;
            foreach ($type_ressurs_options as $key => $label) {
                if (stripos($type_str, $label) !== false || stripos($type_str, str_replace('_', ' ', $key)) !== false) {
                    $type_tags[] = $key;
                }
            }

            // Get logo URL (check ACF image field first, then URL fallback)
            $logo = get_field('verktoy_logo', get_the_ID());
            $logo_url = '';
            if ($logo) {
                $logo_url = is_array($logo) ? ($logo['url'] ?? '') : wp_get_attachment_url($logo);
            }
            if (!$logo_url) {
                $logo_url = get_post_meta(get_the_ID(), 'verktoy_logo_url', true);
            }

            // AEC AI Hub-status (badge/attribusjon/Kilde-filter).
            $is_synced = function_exists('bv_aec_is_synced') ? bv_aec_is_synced(get_the_ID()) : false;
            $is_ai     = function_exists('bv_aec_is_ai_driven') ? bv_aec_is_ai_driven(get_the_ID()) : false;

            $items[] = [
                'title'        => get_the_title(),
                'permalink'    => get_the_permalink(),
                'eier_name'    => $eier ? $eier->post_title : '',
                'logo_url'     => $logo_url,
                'tg_slugs'     => $tg_slugs,
                'tg_names'     => $tg_names,
                'type_tags'    => $type_tags,
                'is_synced'    => $is_synced,
                'is_ai'        => $is_ai,
                'kilde'        => $is_synced ? 'aec_ai_hub' : 'medlem',
            ];
        endwhile; wp_reset_postdata();
        ?>

        <!-- Grid View -->
        <div id="verktoy-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php foreach ($items as $item):
                // Get first type tag for badge display
                $type_badge = '';
                if (!empty($item['type_tags'])) {
                    $first_type = $item['type_tags'][0];
                    $type_badge = isset($type_ressurs_options[$first_type]) ? $type_ressurs_options[$first_type] : $first_type;
                }
                // Get initials from tool name
                $words = explode(' ', $item['title']);
                $tool_initials = count($words) >= 2
                    ? strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1))
                    : strtoupper(mb_substr($item['title'], 0, 2));
            ?>

            <div class="verktoy-card bg-white border border-[#E7E5E4] rounded-xl shadow-sm hover:shadow-md hover:border-[#D6D3D1] transition-all p-6 flex flex-col justify-between h-[285px]"
                 data-title="<?php echo esc_attr(strtolower($item['title'])); ?>"
                 data-eier="<?php echo esc_attr(strtolower($item['eier_name'])); ?>"
                 data-temagruppe="<?php echo esc_attr(implode(',', $item['tg_slugs'])); ?>"
                 data-type="<?php echo esc_attr(implode(',', $item['type_tags'])); ?>"
                 data-kilde="<?php echo esc_attr($item['kilde']); ?>">
                <div>
                    <div class="flex items-start justify-between mb-6">
                        <div class="w-14 h-14 rounded-md bg-[#F5F5F4] flex items-center justify-center overflow-hidden flex-shrink-0 p-2">
                            <?php if ($item['logo_url']): ?>
                            <img src="<?php echo esc_url($item['logo_url']); ?>" alt="<?php echo esc_attr($item['title']); ?>" referrerpolicy="no-referrer" class="w-full h-full object-contain">
                            <?php else: ?>
                            <span class="text-base font-bold text-[#111827] tracking-tight"><?php echo esc_html($tool_initials); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-2">
                            <?php if ($item['is_ai']) { echo bv_aec_ai_badge_markup(); } ?>
                            <?php if ($type_badge): ?>
                            <span class="inline-flex items-center text-xs font-medium text-[#57534E] bg-[#F5F5F4] px-2.5 py-0.5 rounded-full"><?php echo esc_html($type_badge); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h2 class="text-xl font-bold text-[#111827] mb-2 leading-tight tracking-tight line-clamp-2">
                        <?php echo esc_html($item['title']); ?>
                    </h2>

                    <?php if ($item['eier_name']): ?>
                    <div class="flex items-center gap-1 text-sm text-[#57534E]">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                        <span><?php echo esc_html($item['eier_name']); ?></span>
                    </div>
                    <?php elseif ($item['is_synced']): ?>
                    <div class="text-sm text-[#57534E] truncate"><?php echo bv_aec_attribution_html('compact'); ?></div>
                    <?php endif; ?>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-[#E7E5E4]">
                    <?php if (!empty($item['tg_names'])): ?>
                    <span class="text-xs font-medium text-[#57534E] uppercase tracking-wider"><?php echo esc_html($item['tg_names'][0]); ?></span>
                    <?php else: ?>
                    <span></span>
                    <?php endif; ?>

                    <a href="<?php echo esc_url($item['permalink']); ?>" class="inline-flex items-center gap-1 text-sm font-bold text-[#111827] hover:opacity-70 transition-opacity">
                        Se detaljer
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </div>
            </div>

            <?php endforeach; ?>
        </div>

        <!-- List View (hidden by default) -->
        <div id="verktoy-list" style="display:none" class="mb-8">
            <div class="bg-white rounded-xl border border-[#E7E5E4] overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-[#FAFAF9] border-b border-[#E7E5E4]">
                        <tr>
                            <th class="px-4 py-3 font-medium text-[#57534E]">Verktøy</th>
                            <th class="px-4 py-3 font-medium text-[#57534E]">Leverandør</th>
                            <th class="px-4 py-3 font-medium text-[#57534E]">Type</th>
                            <th class="px-4 py-3 font-medium text-[#57534E]">Tema</th>
                            <th class="px-4 py-3 font-medium text-[#57534E] w-16">Lenke</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4]">
                        <?php foreach ($items as $item):
                            $list_words = explode(' ', $item['title']);
                            $list_initials = count($list_words) >= 2
                                ? strtoupper(mb_substr($list_words[0], 0, 1) . mb_substr($list_words[1], 0, 1))
                                : strtoupper(mb_substr($item['title'], 0, 2));
                        ?>
                        <tr class="verktoy-card hover:bg-[#FAFAF9] transition-colors"
                            data-title="<?php echo esc_attr(strtolower($item['title'])); ?>"
                            data-eier="<?php echo esc_attr(strtolower($item['eier_name'])); ?>"
                            data-temagruppe="<?php echo esc_attr(implode(',', $item['tg_slugs'])); ?>"
                            data-type="<?php echo esc_attr(implode(',', $item['type_tags'])); ?>"
                            data-kilde="<?php echo esc_attr($item['kilde']); ?>">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-md bg-[#F5F5F4] flex items-center justify-center overflow-hidden flex-shrink-0 p-1.5">
                                        <?php if ($item['logo_url']): ?>
                                        <img src="<?php echo esc_url($item['logo_url']); ?>" alt="<?php echo esc_attr($item['title']); ?>" referrerpolicy="no-referrer" class="w-full h-full object-contain">
                                        <?php else: ?>
                                        <span class="text-xs font-bold text-[#111827]"><?php echo esc_html($list_initials); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="font-medium text-[#111827]"><?php echo esc_html($item['title']); ?></span>
                                    <?php if ($item['is_ai']) { echo bv_aec_ai_badge_markup(); } ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-[#57534E]"><?php
                                if ($item['eier_name']) {
                                    echo esc_html($item['eier_name']);
                                } elseif ($item['is_synced']) {
                                    echo bv_aec_attribution_html('compact');
                                }
                            ?></td>
                            <td class="px-4 py-3">
                                <?php if (!empty($item['type_tags'])):
                                    $first_type = $item['type_tags'][0];
                                    $type_label = isset($type_ressurs_options[$first_type]) ? $type_ressurs_options[$first_type] : $first_type;
                                ?>
                                <span class="text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-2 py-0.5 rounded-full"><?php echo esc_html($type_label); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach ($item['tg_names'] as $tag): ?>
                                    <span class="text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-2 py-0.5 rounded"><?php echo esc_html($tag); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <a href="<?php echo esc_url($item['permalink']); ?>" class="text-[#111827] hover:text-[#57534E] transition-colors" title="Se detaljer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Vis flere (klient-side pagination) — skjult som default, styres av JS -->
        <div id="verktoy-load-more-wrap" class="flex justify-center mt-2 mb-8" style="display:none">
            <?php bimverdi_button([
                'text'          => 'Vis flere',
                'variant'       => 'outline',
                'icon'          => 'chevron-down',
                'icon_position' => 'right',
                'id'            => 'verktoy-load-more',
            ]); ?>
        </div>

        <?php else: ?>

        <!-- Empty State -->
        <div class="bg-white rounded-lg border border-[#E7E5E4] text-center py-16 px-6">
            <div class="w-16 h-16 bg-[#F5F5F4] rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#57534E]"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </div>
            <h3 class="text-xl font-bold text-[#111827] mb-2">Ingen verktøy funnet</h3>
            <p class="text-[#57534E] mb-6 max-w-md mx-auto">Prøv å justere filtrene eller søket for å finne det du leter etter</p>
            <a href="<?php echo get_post_type_archive_link('verktoy'); ?>" class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-lg text-white bg-[#111827] hover:bg-[#1F2937] transition-colors">
                Vis alle verktøy
            </a>
        </div>

        <?php endif; ?>

        <?php get_template_part('parts/components/archive-cta', null, [
            'title'       => 'Har du et verktøy å dele?',
            'description' => 'Logg inn for å registrere verktøy og bidra til katalogen.',
            'cta_text'    => 'Logg inn',
            'cta_url'     => '/logg-inn/',
            'icon'        => 'log-in',
            'show_for'    => 'logged_out',
        ]); ?>

    </div>
</div>

<!-- Live Filter Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('verktoy-filter-form-search');
    var checkboxes = document.querySelectorAll('.filter-checkbox');
    var gridEl = document.getElementById('verktoy-grid');
    var listEl = document.getElementById('verktoy-list');
    var visibleCountEl = document.getElementById('visible-count');
    var visibleCountMobile = document.getElementById('visible-count-mobile');
    var resetBtn = document.getElementById('reset-filters');
    var sheetResultCount = document.querySelector('.bv-filter-sheet__result-count');
    var loadMoreWrap = document.getElementById('verktoy-load-more-wrap');
    var loadMoreBtn = document.getElementById('verktoy-load-more');

    var debounceTimer;

    var PAGE_SIZE = 24;        // kort per pulje
    var shownCap = PAGE_SIZE;  // hvor mange MATCHENDE kort som vises nå (kun rendering, ikke telleren)

    function updateVisibleCount(count) {
        // count = antall TREFF (filtrert total), ikke pulje — telleren skal alltid vise treff-totalen.
        if (visibleCountEl) visibleCountEl.textContent = count;
        if (visibleCountMobile) visibleCountMobile.textContent = count;
        if (sheetResultCount) sheetResultCount.textContent = count;
    }

    function updateLoadMore(matchCount) {
        if (!loadMoreWrap) return;
        loadMoreWrap.style.display = (matchCount > shownCap) ? '' : 'none';
    }

    // Build URL from current filter state (read only from desktop dropdowns to avoid duplicates)
    function updateURL() {
        var params = new URLSearchParams();
        var searchTerm = searchInput ? searchInput.value.trim() : '';
        if (searchTerm) params.set('s', searchTerm);

        var filterMap = {
            'temagruppe': '.filter-temagruppe:checked',
            'type_ressurs': '.filter-type:checked',
            'kilde': '.filter-kilde:checked'
        };
        Object.keys(filterMap).forEach(function(key) {
            var checked = document.querySelectorAll('[data-multiselect] ' + filterMap[key]);
            checked.forEach(function(cb) { params.append(key, cb.value); });
        });

        var newURL = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        history.replaceState(null, '', newURL);
    }

    // Restore filters from URL params on page load
    function restoreFromURL() {
        var params = new URLSearchParams(window.location.search);
        if (!params.toString()) return false;

        var hasFilters = false;

        // Restore search
        var s = params.get('s');
        if (s && searchInput) {
            searchInput.value = s;
            hasFilters = true;
        }

        // Restore checkboxes
        var filterMap = {
            'temagruppe': '.filter-temagruppe',
            'type_ressurs': '.filter-type',
            'kilde': '.filter-kilde'
        };
        Object.keys(filterMap).forEach(function(key) {
            var values = params.getAll(key);
            if (values.length > 0) {
                hasFilters = true;
                values.forEach(function(val) {
                    // Check desktop checkbox
                    var cb = document.querySelector(filterMap[key] + '[value="' + CSS.escape(val) + '"]');
                    if (cb) {
                        cb.checked = true;
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    // Check mobile checkbox
                    var mobileCb = document.querySelector('#mobile-filter-sheet input[value="' + CSS.escape(val) + '"]' + filterMap[key].replace('.', '.'));
                    if (mobileCb) mobileCb.checked = true;
                });
            }
        });

        return hasFilters;
    }

    function applyFilters() {
        var searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        var selectedTemagruppe = Array.from(document.querySelectorAll('.filter-temagruppe:checked')).map(function(cb) { return cb.value; });
        var selectedType = Array.from(document.querySelectorAll('.filter-type:checked')).map(function(cb) { return cb.value; });
        var selectedKilde = Array.from(document.querySelectorAll('.filter-kilde:checked')).map(function(cb) { return cb.value; });

        function cardMatches(card) {
            var title = card.dataset.title || '';
            var cardTemagrupper = (card.dataset.temagruppe || '').split(',').filter(Boolean);
            var cardTypes = (card.dataset.type || '').split(',').filter(Boolean);
            var cardKilde = card.dataset.kilde || '';
            var eier = card.dataset.eier || '';
            var mSearch = !searchTerm || title.includes(searchTerm) || eier.includes(searchTerm);
            var mTema = selectedTemagruppe.length === 0 || selectedTemagruppe.some(function(f) { return cardTemagrupper.indexOf(f) !== -1; });
            var mType = selectedType.length === 0 || selectedType.some(function(t) { return cardTypes.indexOf(t) !== -1; });
            var mKilde = selectedKilde.length === 0 || selectedKilde.indexOf(cardKilde) !== -1;
            return mSearch && mTema && mType && mKilde;
        }

        // Render én container med cappen: matchende kort vises kun innenfor shownCap;
        // resten skjules. Returnerer totalt antall TREFF i containeren (uavhengig av cap).
        function renderContainer(container) {
            if (!container) return 0;
            var shown = 0, matched = 0;
            container.querySelectorAll('.verktoy-card').forEach(function(card) {
                if (cardMatches(card)) {
                    matched++;
                    card.style.display = (shown < shownCap) ? '' : 'none';
                    if (shown < shownCap) shown++;
                } else {
                    card.style.display = 'none';
                }
            });
            return matched;
        }

        // Cappen anvendes identisk på begge containere → view-toggle forblir konsistent.
        // Treff-totalen tas fra grid (alltid komplett); telleren viser treff, ikke pulje.
        var matchCount = renderContainer(gridEl);
        renderContainer(listEl);

        updateVisibleCount(matchCount);
        updateLoadMore(matchCount);
        updateURL();
    }

    // Enhver filter-/søke-endring nullstiller cappen til første pulje før re-filtrering.
    function resetCapAndApply() {
        shownCap = PAGE_SIZE;
        applyFilters();
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(resetCapAndApply, 200);
        });
    }

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', resetCapAndApply);
    });

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            shownCap += PAGE_SIZE;  // avslør neste pulje (uten å nullstille)
            applyFilters();
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            checkboxes.forEach(function(cb) {
                cb.checked = false;
            });
            // Also reset dropdown count badges
            document.querySelectorAll('[data-multiselect] [data-count]').forEach(function(badge) {
                badge.textContent = '0';
                badge.classList.remove('opacity-100');
                badge.classList.add('opacity-0');
                badge.setAttribute('aria-hidden', 'true');
            });
            // Reset mobile count badge
            var mobileCount = document.querySelector('[data-mobile-count]');
            if (mobileCount) {
                mobileCount.textContent = '0';
                mobileCount.classList.remove('opacity-100');
                mobileCount.classList.add('opacity-0');
            }
            resetCapAndApply();
        });
    }

    // Restore filters from URL and apply (cappen starter på PAGE_SIZE)
    restoreFromURL();
    applyFilters();
});
</script>

<?php get_footer(); ?>
