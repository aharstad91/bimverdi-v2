<?php
/**
 * Archive template for Kunnskapskilde (Knowledge Sources)
 *
 * Public knowledge source catalog with BIM Verdi design.
 * Filter on temagruppe, kildetype and kategori.
 * URL: /kunnskapskilder (from CPT rewrite slug)
 * Updated 2026-02-03: Replaced checkbox filters with compact dropdown filter bar.
 *
 * @package BimVerdi_Theme
 */

get_header();

// Include filter bar component
require_once get_template_directory() . '/parts/components/filter-bar.php';

// Get filter parameters
$search = sanitize_text_field($_GET['s'] ?? '');
$temagruppe = isset($_GET['temagruppe']) && is_array($_GET['temagruppe'])
    ? array_map('sanitize_text_field', $_GET['temagruppe'])
    : array();
$kategori = isset($_GET['kategori']) && is_array($_GET['kategori'])
    ? array_map('sanitize_text_field', $_GET['kategori'])
    : array();
$kildetype = isset($_GET['kildetype']) && is_array($_GET['kildetype'])
    ? array_map('sanitize_text_field', $_GET['kildetype'])
    : array();

// Define filter options
$temagruppe_options = array(
    'byggesaksbim' => 'ByggesaksBIM',
    'prosjektbim' => 'ProsjektBIM',
    'eiendomsbim' => 'EiendomsBIM',
    'miljobim' => 'MiljøBIM',
    'sirkbim' => 'SirkBIM',
    'bimtech' => 'BIMtech',
);

$kildetype_options = array(
    'standard' => 'Standard (ISO, NS, etc.)',
    'veiledning' => 'Veiledning/metodikk',
    'forskrift_norsk' => 'Forskrift (norsk lov)',
    'forordning_eu' => 'Forordning (EU/EØS)',
    'mal' => 'Mal/Template',
    'forskningsrapport' => 'Forskningsrapport',
    'casestudie' => 'Casestudie',
    'opplaering' => 'Opplæring',
    'dokumentasjon' => 'Verktøydokumentasjon',
    'nettressurs' => 'Nettressurs/Database',
    'annet' => 'Annet',
);

// Get kategori terms from taxonomy
$kategori_terms = get_terms(array(
    'taxonomy' => 'kunnskapskildekategori',
    'hide_empty' => false,
));
$kategori_options = array();
if (!empty($kategori_terms) && !is_wp_error($kategori_terms)) {
    foreach ($kategori_terms as $term) {
        $kategori_options[$term->slug] = $term->name;
    }
}

// Build query
$args = array(
    'post_type' => 'kunnskapskilde',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'post_status' => 'publish',
);

if (!empty($search)) {
    $args['s'] = $search;
}

// Tax query for temagruppe and kategori
$tax_query = array();
if (!empty($temagruppe)) {
    $tax_query[] = array(
        'taxonomy' => 'temagruppe',
        'field' => 'slug',
        'terms' => $temagruppe,
    );
}
if (!empty($kategori)) {
    $tax_query[] = array(
        'taxonomy' => 'kunnskapskildekategori',
        'field' => 'slug',
        'terms' => $kategori,
    );
}
if (!empty($tax_query)) {
    $tax_query['relation'] = 'AND';
    $args['tax_query'] = $tax_query;
}

// Meta query for kildetype
if (!empty($kildetype)) {
    $args['meta_query'] = array(
        array(
            'key' => 'kildetype',
            'value' => $kildetype,
            'compare' => 'IN',
        ),
    );
}

$kunnskapskilder_query = new WP_Query($args);
$is_logged_in = is_user_logged_in();

// Calculate counts for each filter option (static counts - total items per value)
$temagruppe_counts = array();
foreach (array_keys($temagruppe_options) as $slug) {
    $count_query = new WP_Query([
        'post_type' => 'kunnskapskilde',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => [[
            'taxonomy' => 'temagruppe',
            'field' => 'slug',
            'terms' => $slug,
        ]],
    ]);
    $temagruppe_counts[$slug] = $count_query->found_posts;
}

$kildetype_counts = array();
foreach (array_keys($kildetype_options) as $value) {
    $count_query = new WP_Query([
        'post_type' => 'kunnskapskilde',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [[
            'key' => 'kildetype',
            'value' => $value,
            'compare' => '=',
        ]],
    ]);
    $kildetype_counts[$value] = $count_query->found_posts;
}

$kategori_counts = array();
foreach (array_keys($kategori_options) as $slug) {
    $count_query = new WP_Query([
        'post_type' => 'kunnskapskilde',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => [[
            'taxonomy' => 'kunnskapskildekategori',
            'field' => 'slug',
            'terms' => $slug,
        ]],
    ]);
    $kategori_counts[$slug] = $count_query->found_posts;
}
?>

<div class="min-h-screen bg-white">

    <?php get_template_part('parts/components/archive-intro', null, [
        'acf_prefix'       => 'kunnskapskilder',
        'fallback_title'   => 'Kunnskapskilder',
        'fallback_ingress' => 'Standarder, veiledere og ressurser fra BIM Verdi-nettverket.',
        'count'            => $kunnskapskilder_query->found_posts,
        'count_label'      => 'kunnskapskilder',
    ]); ?>

    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Compact Filter Bar -->
        <?php
        $dropdowns = [
            [
                'name'         => 'temagruppe[]',
                'label'        => 'Temagruppe',
                'options'      => $temagruppe_options,
                'selected'     => $temagruppe,
                'counts'       => $temagruppe_counts,
                'filter_class' => 'filter-temagruppe',
            ],
            [
                'name'         => 'kildetype[]',
                'label'        => 'Kildetype',
                'options'      => $kildetype_options,
                'selected'     => $kildetype,
                'counts'       => $kildetype_counts,
                'filter_class' => 'filter-kildetype',
            ],
        ];
        // Only add kategori dropdown if there are terms
        if (!empty($kategori_options)) {
            $dropdowns[] = [
                'name'         => 'kategori[]',
                'label'        => 'Kategori',
                'options'      => $kategori_options,
                'selected'     => $kategori,
                'counts'       => $kategori_counts,
                'filter_class' => 'filter-kategori',
            ];
        }

        bimverdi_filter_bar([
            'form_id'            => 'kunnskapskilde-filter-form',
            'search_name'        => 's',
            'search_value'       => $search,
            'search_placeholder' => 'Søk etter kunnskapskilder...',
            'dropdowns'          => $dropdowns,
            'result_count'       => $kunnskapskilder_query->found_posts,
            'total_count'        => $kunnskapskilder_query->found_posts,
            'result_label'       => 'kilder',
            'reset_id'           => 'reset-filters',
            'view_toggle'        => [
                'storage_key' => 'bv-view-kunnskapskilde',
                'grid_id'     => 'kunnskapskilde-grid',
                'list_id'     => 'kunnskapskilde-list',
            ],
        ]);
        ?>

        <!-- Kunnskapskilder Grid & List -->
        <?php if ($kunnskapskilder_query->have_posts()):

        // Collect post data into array for dual rendering
        $items = [];
        while ($kunnskapskilder_query->have_posts()): $kunnskapskilder_query->the_post();
            $temagruppe_terms_post = wp_get_post_terms(get_the_ID(), 'temagruppe');
            $kategori_terms_post = wp_get_post_terms(get_the_ID(), 'kunnskapskildekategori');
            $kildetype_val = get_field('kildetype', get_the_ID());
            $items[] = [
                'id'               => get_the_ID(),
                'navn'             => get_field('kunnskapskilde_navn', get_the_ID()) ?: get_the_title(),
                'kort_beskrivelse' => get_field('kort_beskrivelse', get_the_ID()),
                'ekstern_lenke'    => get_field('ekstern_lenke', get_the_ID()),
                'utgiver'          => get_field('utgiver', get_the_ID()),
                'kildetype'        => $kildetype_val,
                'utgivelsesaar'    => get_field('utgivelsesaar', get_the_ID()),
                'permalink'        => get_the_permalink(),
                'temagruppe_terms' => $temagruppe_terms_post,
                'temagruppe_slugs' => !empty($temagruppe_terms_post) ? implode(' ', wp_list_pluck($temagruppe_terms_post, 'slug')) : '',
                'kategori_slugs'   => !empty($kategori_terms_post) ? implode(' ', wp_list_pluck($kategori_terms_post, 'slug')) : '',
            ];
        endwhile; wp_reset_postdata();

        // Icon map for kildetype
        $icon_map = [
            'standard' => '<path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path><rect x="9" y="3" width="6" height="4" rx="2"></rect><path d="m9 14 2 2 4-4"></path>',
            'veiledning' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>',
            'veileder' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>',
            'forskrift_norsk' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>',
            'forordning_eu' => '<circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>',
            'mal' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line>',
            'forskningsrapport' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>',
            'opplaering' => '<path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path>',
            'nettressurs' => '<circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>',
        ];
        $default_icon = '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>';
        ?>

        <!-- Grid View -->
        <div id="kunnskapskilde-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php foreach ($items as $item):
                $icon_path = isset($icon_map[$item['kildetype']]) ? $icon_map[$item['kildetype']] : $default_icon;
            ?>

            <div class="kunnskapskilde-card bg-white rounded-xl border border-[#E7E5E4] shadow-sm overflow-hidden hover:shadow-md hover:border-[#D6D3D1] transition-all group"
               data-title="<?php echo esc_attr(strtolower($item['navn'])); ?>"
               data-temagruppe="<?php echo esc_attr($item['temagruppe_slugs']); ?>"
               data-kildetype="<?php echo esc_attr($item['kildetype']); ?>"
               data-kategori="<?php echo esc_attr($item['kategori_slugs']); ?>">

                <!-- Icon Header -->
                <div class="h-32 bg-[#FAFAF9] overflow-hidden flex items-center justify-center p-6">
                    <div class="w-16 h-16 bg-white rounded-lg flex items-center justify-center shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-[#57534E]"><?php echo $icon_path; ?></svg>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-5">
                    <div class="flex flex-wrap gap-2 mb-3">
                        <?php if (!empty($item['temagruppe_terms'])): ?>
                        <span class="text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-2 py-1 rounded">
                            <?php echo esc_html($item['temagruppe_terms'][0]->name); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($item['kildetype'] && isset($kildetype_options[$item['kildetype']])): ?>
                        <span class="text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-2 py-1 rounded">
                            <?php echo esc_html($kildetype_options[$item['kildetype']]); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <h3 class="text-lg font-bold text-[#111827] mb-1 group-hover:text-[#57534E] transition-colors line-clamp-2">
                        <?php echo esc_html($item['navn']); ?>
                    </h3>

                    <?php if ($item['utgiver'] || $item['utgivelsesaar']): ?>
                    <p class="text-sm text-[#57534E] mb-3">
                        <?php
                        $meta_parts = [];
                        if ($item['utgiver']) $meta_parts[] = $item['utgiver'];
                        if ($item['utgivelsesaar']) {
                            $meta_parts[] = ($item['utgivelsesaar'] === 'eldre') ? 'Eldre enn 2022' : $item['utgivelsesaar'];
                        }
                        echo esc_html(implode(' - ', $meta_parts));
                        ?>
                    </p>
                    <?php endif; ?>

                    <?php if ($item['kort_beskrivelse']): ?>
                    <p class="text-sm text-[#57534E] mb-4 line-clamp-2">
                        <?php echo esc_html(wp_trim_words($item['kort_beskrivelse'], 15)); ?>
                    </p>
                    <?php endif; ?>

                    <div class="flex gap-2 pt-4 border-t border-[#E7E5E4]">
                        <a href="<?php echo esc_url($item['permalink']); ?>"
                           class="flex-1 px-4 py-2 text-sm font-medium text-center text-[#111827] bg-[#F5F5F4] rounded-lg hover:bg-[#E7E5E4] transition-colors">
                            Se detaljer
                        </a>
                        <?php if (!empty($item['ekstern_lenke'])): ?>
                        <a href="<?php echo esc_url($item['ekstern_lenke']); ?>"
                           target="_blank"
                           rel="noopener"
                           class="px-4 py-2 text-sm font-medium text-center text-white bg-[#111827] rounded-lg hover:bg-[#1F2937] transition-colors">
                            Besøk
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php endforeach; ?>
        </div>

        <!-- List View (hidden by default) -->
        <div id="kunnskapskilde-list" class="hidden mb-8">
            <div class="bg-white rounded-xl border border-[#E7E5E4] overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-[#FAFAF9] border-b border-[#E7E5E4]">
                        <tr>
                            <th class="px-4 py-3 font-medium text-[#57534E]">Navn</th>
                            <th class="px-4 py-3 font-medium text-[#57534E]">Type</th>
                            <th class="px-4 py-3 font-medium text-[#57534E]">Utgiver</th>
                            <th class="px-4 py-3 font-medium text-[#57534E] w-20">År</th>
                            <th class="px-4 py-3 font-medium text-[#57534E] w-28">Lenker</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4]">
                        <?php foreach ($items as $item): ?>
                        <tr class="kunnskapskilde-card hover:bg-[#FAFAF9] transition-colors"
                            data-title="<?php echo esc_attr(strtolower($item['navn'])); ?>"
                            data-temagruppe="<?php echo esc_attr($item['temagruppe_slugs']); ?>"
                            data-kildetype="<?php echo esc_attr($item['kildetype']); ?>"
                            data-kategori="<?php echo esc_attr($item['kategori_slugs']); ?>">
                            <td class="px-4 py-3">
                                <div class="font-medium text-[#111827]"><?php echo esc_html($item['navn']); ?></div>
                                <?php if ($item['kort_beskrivelse']): ?>
                                <div class="text-xs text-[#57534E] mt-0.5 line-clamp-1"><?php echo esc_html(wp_trim_words($item['kort_beskrivelse'], 12)); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($item['kildetype'] && isset($kildetype_options[$item['kildetype']])): ?>
                                <span class="text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-2 py-1 rounded whitespace-nowrap">
                                    <?php echo esc_html($kildetype_options[$item['kildetype']]); ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-[#57534E]"><?php echo esc_html($item['utgiver']); ?></td>
                            <td class="px-4 py-3 text-[#57534E]">
                                <?php
                                if ($item['utgivelsesaar']) {
                                    echo esc_html($item['utgivelsesaar'] === 'eldre' ? '<2022' : $item['utgivelsesaar']);
                                }
                                ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="<?php echo esc_url($item['permalink']); ?>" class="text-[#111827] hover:text-[#57534E] transition-colors" title="Se detaljer">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                                    </a>
                                    <?php if (!empty($item['ekstern_lenke'])): ?>
                                    <a href="<?php echo esc_url($item['ekstern_lenke']); ?>" target="_blank" rel="noopener" class="text-[#111827] hover:text-[#57534E] transition-colors" title="Besøk ekstern kilde">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php else: ?>

        <!-- Empty State -->
        <div class="bg-white rounded-lg border border-[#E7E5E4] text-center py-16 px-6">
            <div class="w-16 h-16 bg-[#F5F5F4] rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#57534E]"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </div>
            <h3 class="text-xl font-bold text-[#111827] mb-2">Ingen kunnskapskilder funnet</h3>
            <p class="text-[#57534E] mb-6 max-w-md mx-auto">Prøv å justere filtrene eller søket for å finne det du leter etter</p>
            <a href="<?php echo get_post_type_archive_link('kunnskapskilde'); ?>" class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-lg text-white bg-[#111827] hover:bg-[#1F2937] transition-colors">
                Vis alle kunnskapskilder
            </a>
        </div>

        <?php endif; ?>

        <?php get_template_part('parts/components/archive-cta', null, [
            'title'       => 'Vil du bidra?',
            'description' => 'Logg inn for å registrere egne kunnskapskilder og få tilgang til deltakerinnhold.',
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
    var searchInput = document.getElementById('kunnskapskilde-filter-form-search');
    var checkboxes = document.querySelectorAll('.filter-checkbox');
    var gridEl = document.getElementById('kunnskapskilde-grid');
    var listEl = document.getElementById('kunnskapskilde-list');
    var visibleCountEl = document.getElementById('visible-count');
    var visibleCountMobile = document.getElementById('visible-count-mobile');
    var resetBtn = document.getElementById('reset-filters');
    var sheetResultCount = document.querySelector('.bv-filter-sheet__result-count');

    var debounceTimer;

    function updateVisibleCount(count) {
        if (visibleCountEl) visibleCountEl.textContent = count;
        if (visibleCountMobile) visibleCountMobile.textContent = count;
        if (sheetResultCount) sheetResultCount.textContent = count;
    }

    // Build URL from current filter state (read only from desktop dropdowns to avoid duplicates)
    function updateURL() {
        var params = new URLSearchParams();
        var searchTerm = searchInput ? searchInput.value.trim() : '';
        if (searchTerm) params.set('s', searchTerm);

        var filterMap = {
            'temagruppe': '.filter-temagruppe:checked',
            'kildetype': '.filter-kildetype:checked',
            'kategori': '.filter-kategori:checked'
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
            'kildetype': '.filter-kildetype',
            'kategori': '.filter-kategori'
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
        var selectedKildetype = Array.from(document.querySelectorAll('.filter-kildetype:checked')).map(function(cb) { return cb.value; });
        var selectedKategori = Array.from(document.querySelectorAll('.filter-kategori:checked')).map(function(cb) { return cb.value; });

        // Filter all cards in both grid and list
        var allCards = document.querySelectorAll('.kunnskapskilde-card');
        allCards.forEach(function(card) {
            var title = card.dataset.title || '';
            var cardTemagruppe = card.dataset.temagruppe || '';
            var cardKildetype = card.dataset.kildetype || '';
            var cardKategori = card.dataset.kategori || '';

            var matchesSearch = !searchTerm || title.includes(searchTerm);
            var matchesTemagruppe = selectedTemagruppe.length === 0 || selectedTemagruppe.some(function(t) { return cardTemagruppe.includes(t); });
            var matchesKildetype = selectedKildetype.length === 0 || selectedKildetype.includes(cardKildetype);
            var matchesKategori = selectedKategori.length === 0 || selectedKategori.some(function(k) { return cardKategori.includes(k); });

            card.style.display = (matchesSearch && matchesTemagruppe && matchesKildetype && matchesKategori) ? '' : 'none';
        });

        // Count only from active (visible) container to avoid double-counting
        var activeContainer = (listEl && !listEl.classList.contains('hidden')) ? listEl : gridEl;
        var visibleCards = activeContainer ? activeContainer.querySelectorAll('.kunnskapskilde-card:not([style*="display: none"])').length : 0;

        updateVisibleCount(visibleCards);
        updateURL();
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(applyFilters, 200);
        });
    }

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', applyFilters);
    });

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
            applyFilters();
        });
    }

    // Restore filters from URL and apply
    restoreFromURL();
    applyFilters();
});
</script>

<?php get_footer(); ?>
