<?php
/**
 * Archive Artikkel Template
 *
 * Redesigned: Featured hero article, native HTML cards, filter-bar,
 * colored temagruppe badges, grid/list toggle.
 *
 * @package BIMVerdi
 */

get_header();

// Include filter bar component
require_once get_template_directory() . '/parts/components/filter-bar.php';

// Get filter parameters
$search = sanitize_text_field($_GET['s'] ?? '');
$temagruppe_filter = isset($_GET['temagruppe']) && is_array($_GET['temagruppe'])
    ? array_map('sanitize_text_field', $_GET['temagruppe'])
    : array();
$kategori_filter = isset($_GET['kategori']) && is_array($_GET['kategori'])
    ? array_map('sanitize_text_field', $_GET['kategori'])
    : array();

// Temagruppe color map
$tg_colors = [
    'SirkBIM'      => '#FF8B5E',
    'ByggesaksBIM' => '#005898',
    'ProsjektBIM'  => '#6B9B37',
    'EiendomsBIM'  => '#5E36FE',
    'MiljøBIM'     => '#0D9488',
    'BIMtech'      => '#D97706',
];

// Get temagrupper and artikkelkategorier for filters
$temagruppe_terms = get_terms(['taxonomy' => 'temagruppe', 'hide_empty' => false]);
$temagruppe_options = [];
$temagruppe_counts = [];
if (!empty($temagruppe_terms) && !is_wp_error($temagruppe_terms)) {
    foreach ($temagruppe_terms as $term) {
        $temagruppe_options[$term->slug] = $term->name;
        $count_query = new WP_Query([
            'post_type' => 'artikkel',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [['taxonomy' => 'temagruppe', 'field' => 'slug', 'terms' => $term->slug]],
        ]);
        $temagruppe_counts[$term->slug] = $count_query->found_posts;
    }
}

$kategori_terms = get_terms(['taxonomy' => 'artikkelkategori', 'hide_empty' => false]);
$kategori_options = [];
$kategori_counts = [];
if (!empty($kategori_terms) && !is_wp_error($kategori_terms)) {
    foreach ($kategori_terms as $term) {
        $kategori_options[$term->slug] = $term->name;
        $count_query = new WP_Query([
            'post_type' => 'artikkel',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [['taxonomy' => 'artikkelkategori', 'field' => 'slug', 'terms' => $term->slug]],
        ]);
        $kategori_counts[$term->slug] = $count_query->found_posts;
    }
}

// Build query — load all for client-side filtering
$args = [
    'post_type'      => 'artikkel',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
];

if (!empty($search)) {
    $args['s'] = $search;
}

$tax_query = [];
if (!empty($temagruppe_filter)) {
    $tax_query[] = ['taxonomy' => 'temagruppe', 'field' => 'slug', 'terms' => $temagruppe_filter];
}
if (!empty($kategori_filter)) {
    $tax_query[] = ['taxonomy' => 'artikkelkategori', 'field' => 'slug', 'terms' => $kategori_filter];
}
if (!empty($tax_query)) {
    $tax_query['relation'] = 'AND';
    $args['tax_query'] = $tax_query;
}

$articles = new WP_Query($args);

// Collect items
$items = [];
while ($articles->have_posts()): $articles->the_post();
    $temagruppe_post_terms = wp_get_post_terms(get_the_ID(), 'temagruppe');
    $kategori_post_terms = wp_get_post_terms(get_the_ID(), 'artikkelkategori');
    $ingress = get_field('artikkel_ingress', get_the_ID());
    $bedrift_id = get_field('artikkel_bedrift', get_the_ID());

    $items[] = [
        'id'               => get_the_ID(),
        'title'            => get_the_title(),
        'permalink'        => get_the_permalink(),
        'date'             => get_the_date('j. M Y'),
        'date_raw'         => get_the_date('Y-m-d'),
        'thumbnail_url'    => has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'large') : '',
        'ingress'          => $ingress ?: (has_excerpt() ? get_the_excerpt() : wp_trim_words(get_the_content(), 30, '...')),
        'author_name'      => get_the_author(),
        'author_avatar'    => get_avatar_url(get_the_author_meta('ID'), ['size' => 64]),
        'bedrift_name'     => $bedrift_id ? get_the_title($bedrift_id) : '',
        'bedrift_url'      => $bedrift_id ? get_permalink($bedrift_id) : '',
        'temagruppe_terms' => $temagruppe_post_terms,
        'temagruppe_slugs' => !empty($temagruppe_post_terms) ? implode(' ', wp_list_pluck($temagruppe_post_terms, 'slug')) : '',
        'kategori_terms'   => $kategori_post_terms,
        'kategori_slugs'   => !empty($kategori_post_terms) ? implode(' ', wp_list_pluck($kategori_post_terms, 'slug')) : '',
    ];
endwhile; wp_reset_postdata();

$featured = !empty($items) ? $items[0] : null;
$rest_items = array_slice($items, 1);
?>

<div class="min-h-screen bg-white">

    <?php get_template_part('parts/components/archive-intro', null, [
        'acf_prefix'       => 'artikler',
        'fallback_title'   => 'Artikler',
        'fallback_ingress' => 'Fagstoff og erfaringer fra deltakere i nettverket. Les om prosjekter, metoder og nye løsninger.',
        'count'            => $articles->found_posts,
        'count_label'      => 'artikler',
    ]); ?>

    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Compact Filter Bar -->
        <?php
        $dropdowns = [];
        if (!empty($temagruppe_options)) {
            $dropdowns[] = [
                'name'         => 'temagruppe[]',
                'label'        => 'Temagruppe',
                'options'      => $temagruppe_options,
                'selected'     => $temagruppe_filter,
                'counts'       => $temagruppe_counts,
                'filter_class' => 'filter-temagruppe',
            ];
        }
        if (!empty($kategori_options)) {
            $dropdowns[] = [
                'name'         => 'kategori[]',
                'label'        => 'Kategori',
                'options'      => $kategori_options,
                'selected'     => $kategori_filter,
                'counts'       => $kategori_counts,
                'filter_class' => 'filter-kategori',
            ];
        }

        bimverdi_filter_bar([
            'form_id'            => 'artikkel-filter-form',
            'search_name'        => 's',
            'search_value'       => $search,
            'search_placeholder' => 'Søk etter artikler...',
            'dropdowns'          => $dropdowns,
            'result_count'       => $articles->found_posts,
            'total_count'        => $articles->found_posts,
            'result_label'       => 'artikler',
            'reset_id'           => 'reset-filters',
            'view_toggle'        => [
                'storage_key' => 'bv-view-artikkel',
                'grid_id'     => 'artikkel-grid',
                'list_id'     => 'artikkel-list',
            ],
        ]);
        ?>

        <?php if (!empty($items)): ?>

        <!-- Featured Article (first article, full width) -->
        <?php if ($featured): ?>
        <div id="featured-article" class="artikkel-card mb-8"
             data-title="<?php echo esc_attr(strtolower($featured['title'])); ?>"
             data-author="<?php echo esc_attr(strtolower($featured['author_name'])); ?>"
             data-bedrift="<?php echo esc_attr(strtolower($featured['bedrift_name'])); ?>"
             data-temagruppe="<?php echo esc_attr($featured['temagruppe_slugs']); ?>"
             data-kategori="<?php echo esc_attr($featured['kategori_slugs']); ?>">
            <a href="<?php echo esc_url($featured['permalink']); ?>" class="group block">
                <div class="bg-white rounded-xl border border-[#E7E5E4] overflow-hidden hover:shadow-lg hover:border-[#D6D3D1] transition-all">
                    <div class="grid md:grid-cols-2">
                        <!-- Image -->
                        <div class="aspect-[4/3] md:aspect-auto bg-[#FAFAF9] overflow-hidden">
                            <?php if ($featured['thumbnail_url']): ?>
                            <img src="<?php echo esc_url($featured['thumbnail_url']); ?>"
                                 alt="<?php echo esc_attr($featured['title']); ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <?php else: ?>
                            <div class="w-full h-full min-h-[280px] flex items-center justify-center">
                                <svg class="w-16 h-16 text-[#E7E5E4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                </svg>
                            </div>
                            <?php endif; ?>
                        </div>
                        <!-- Content -->
                        <div class="p-8 flex flex-col justify-center">
                            <div class="flex flex-wrap items-center gap-2 mb-4">
                                <?php foreach ($featured['temagruppe_terms'] as $tg):
                                    $color = isset($tg_colors[$tg->name]) ? $tg_colors[$tg->name] : '#57534E';
                                ?>
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background: <?php echo esc_attr($color); ?>15; color: <?php echo esc_attr($color); ?>">
                                    <?php echo esc_html($tg->name); ?>
                                </span>
                                <?php endforeach; ?>
                                <?php foreach ($featured['kategori_terms'] as $kat): ?>
                                <span class="text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-2.5 py-1 rounded-full">
                                    <?php echo esc_html($kat->name); ?>
                                </span>
                                <?php endforeach; ?>
                                <span class="text-xs text-[#78716C]"><?php echo esc_html($featured['date']); ?></span>
                            </div>

                            <h2 class="text-2xl font-bold text-[#111827] mb-3 group-hover:text-[#57534E] transition-colors leading-tight">
                                <?php echo esc_html($featured['title']); ?>
                            </h2>

                            <p class="text-[#57534E] mb-6 line-clamp-3">
                                <?php echo esc_html(wp_trim_words($featured['ingress'], 40)); ?>
                            </p>

                            <div class="flex items-center gap-3 mt-auto">
                                <img src="<?php echo esc_url($featured['author_avatar']); ?>" alt="" class="w-8 h-8 rounded-full bg-[#F5F5F4]">
                                <div class="text-sm">
                                    <span class="font-medium text-[#111827]"><?php echo esc_html($featured['author_name']); ?></span>
                                    <?php if ($featured['bedrift_name']): ?>
                                    <span class="text-[#A8A29E] mx-1">&middot;</span>
                                    <span class="text-[#57534E]"><?php echo esc_html($featured['bedrift_name']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>

        <!-- Grid View -->
        <div id="artikkel-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php foreach ($rest_items as $item): ?>

            <div class="artikkel-card bg-white rounded-xl border border-[#E7E5E4] shadow-sm overflow-hidden hover:shadow-md hover:border-[#D6D3D1] transition-all group flex flex-col"
                 data-title="<?php echo esc_attr(strtolower($item['title'])); ?>"
                 data-author="<?php echo esc_attr(strtolower($item['author_name'])); ?>"
                 data-bedrift="<?php echo esc_attr(strtolower($item['bedrift_name'])); ?>"
                 data-temagruppe="<?php echo esc_attr($item['temagruppe_slugs']); ?>"
                 data-kategori="<?php echo esc_attr($item['kategori_slugs']); ?>">

                <!-- Image -->
                <div class="aspect-[16/9] bg-[#FAFAF9] overflow-hidden">
                    <?php if ($item['thumbnail_url']): ?>
                    <img src="<?php echo esc_url($item['thumbnail_url']); ?>"
                         alt="<?php echo esc_attr($item['title']); ?>"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center">
                        <svg class="w-12 h-12 text-[#E7E5E4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                        </svg>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Content -->
                <div class="p-5 flex flex-col flex-1">
                    <!-- Badges & Date -->
                    <div class="flex flex-wrap items-center gap-1.5 mb-3">
                        <?php foreach ($item['temagruppe_terms'] as $tg):
                            $color = isset($tg_colors[$tg->name]) ? $tg_colors[$tg->name] : '#57534E';
                        ?>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" style="background: <?php echo esc_attr($color); ?>15; color: <?php echo esc_attr($color); ?>">
                            <?php echo esc_html($tg->name); ?>
                        </span>
                        <?php endforeach; ?>
                        <?php foreach ($item['kategori_terms'] as $kat): ?>
                        <span class="text-[10px] font-medium bg-[#F5F5F4] text-[#57534E] px-2 py-0.5 rounded-full">
                            <?php echo esc_html($kat->name); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>

                    <h3 class="text-lg font-bold text-[#111827] mb-2 group-hover:text-[#57534E] transition-colors line-clamp-2 leading-snug">
                        <a href="<?php echo esc_url($item['permalink']); ?>" class="hover:text-[#57534E]">
                            <?php echo esc_html($item['title']); ?>
                        </a>
                    </h3>

                    <p class="text-sm text-[#57534E] mb-4 flex-1 line-clamp-2">
                        <?php echo esc_html(wp_trim_words($item['ingress'], 20)); ?>
                    </p>

                    <!-- Author footer -->
                    <div class="flex items-center justify-between pt-4 border-t border-[#E7E5E4] mt-auto">
                        <div class="flex items-center gap-2 min-w-0">
                            <img src="<?php echo esc_url($item['author_avatar']); ?>" alt="" class="w-6 h-6 rounded-full bg-[#F5F5F4] flex-shrink-0">
                            <span class="text-xs text-[#57534E] truncate">
                                <?php echo esc_html($item['author_name']); ?>
                                <?php if ($item['bedrift_name']): ?>
                                <span class="text-[#A8A29E]">&middot;</span> <?php echo esc_html($item['bedrift_name']); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <span class="text-xs text-[#A8A29E] flex-shrink-0 ml-2"><?php echo esc_html($item['date']); ?></span>
                    </div>
                </div>
            </div>

            <?php endforeach; ?>
        </div>

        <!-- List View (hidden by default) -->
        <div id="artikkel-list" style="display:none" class="mb-8">
            <div class="bg-white rounded-xl border border-[#E7E5E4] overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-[#FAFAF9] border-b border-[#E7E5E4]">
                        <tr>
                            <th class="px-4 py-3 font-medium text-[#57534E]">Artikkel</th>
                            <th class="px-4 py-3 font-medium text-[#57534E] hidden md:table-cell">Temagruppe</th>
                            <th class="px-4 py-3 font-medium text-[#57534E] hidden md:table-cell">Forfatter</th>
                            <th class="px-4 py-3 font-medium text-[#57534E] hidden sm:table-cell w-28">Dato</th>
                            <th class="px-4 py-3 font-medium text-[#57534E] w-16">Lenke</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4]">
                        <?php foreach ($items as $item): ?>
                        <tr class="artikkel-card hover:bg-[#FAFAF9] transition-colors"
                            data-title="<?php echo esc_attr(strtolower($item['title'])); ?>"
                            data-author="<?php echo esc_attr(strtolower($item['author_name'])); ?>"
                            data-bedrift="<?php echo esc_attr(strtolower($item['bedrift_name'])); ?>"
                            data-temagruppe="<?php echo esc_attr($item['temagruppe_slugs']); ?>"
                            data-kategori="<?php echo esc_attr($item['kategori_slugs']); ?>">
                            <td class="px-4 py-3">
                                <div class="font-medium text-[#111827]"><?php echo esc_html($item['title']); ?></div>
                                <?php if ($item['ingress']): ?>
                                <div class="text-xs text-[#57534E] mt-0.5 line-clamp-1"><?php echo esc_html(wp_trim_words($item['ingress'], 12)); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach ($item['temagruppe_terms'] as $tg):
                                        $color = isset($tg_colors[$tg->name]) ? $tg_colors[$tg->name] : '#57534E';
                                    ?>
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full whitespace-nowrap" style="background: <?php echo esc_attr($color); ?>15; color: <?php echo esc_attr($color); ?>">
                                        <?php echo esc_html($tg->name); ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-[#57534E] hidden md:table-cell">
                                <div class="flex items-center gap-2">
                                    <img src="<?php echo esc_url($item['author_avatar']); ?>" alt="" class="w-5 h-5 rounded-full bg-[#F5F5F4]">
                                    <span class="truncate"><?php echo esc_html($item['author_name']); ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-[#78716C] hidden sm:table-cell"><?php echo esc_html($item['date']); ?></td>
                            <td class="px-4 py-3">
                                <a href="<?php echo esc_url($item['permalink']); ?>" class="text-[#111827] hover:text-[#57534E] transition-colors" title="Les artikkel">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                                </a>
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
            <h3 class="text-xl font-bold text-[#111827] mb-2">Ingen artikler funnet</h3>
            <p class="text-[#57534E] mb-6 max-w-md mx-auto">Prøv å justere filtrene eller søket for å finne det du leter etter</p>
            <a href="<?php echo get_post_type_archive_link('artikkel'); ?>" class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-lg text-white bg-[#111827] hover:bg-[#1F2937] transition-colors">
                Vis alle artikler
            </a>
        </div>

        <?php endif; ?>

        <?php get_template_part('parts/components/archive-cta', null, [
            'title'       => 'Har du noe å dele?',
            'description' => 'Logg inn for å skrive artikler og dele erfaringer med nettverket.',
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
    var searchInput = document.getElementById('artikkel-filter-form-search');
    var checkboxes = document.querySelectorAll('.filter-checkbox');
    var gridEl = document.getElementById('artikkel-grid');
    var listEl = document.getElementById('artikkel-list');
    var featuredEl = document.getElementById('featured-article');
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

    function updateURL() {
        var params = new URLSearchParams();
        var searchTerm = searchInput ? searchInput.value.trim() : '';
        if (searchTerm) params.set('s', searchTerm);

        var filterMap = {
            'temagruppe': '.filter-temagruppe:checked',
            'kategori': '.filter-kategori:checked'
        };
        Object.keys(filterMap).forEach(function(key) {
            var checked = document.querySelectorAll('[data-multiselect] ' + filterMap[key]);
            checked.forEach(function(cb) { params.append(key, cb.value); });
        });

        var newURL = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        history.replaceState(null, '', newURL);
    }

    function restoreFromURL() {
        var params = new URLSearchParams(window.location.search);
        if (!params.toString()) return false;

        var hasFilters = false;

        var s = params.get('s');
        if (s && searchInput) {
            searchInput.value = s;
            hasFilters = true;
        }

        var filterMap = {
            'temagruppe': '.filter-temagruppe',
            'kategori': '.filter-kategori'
        };
        Object.keys(filterMap).forEach(function(key) {
            var values = params.getAll(key);
            if (values.length > 0) {
                hasFilters = true;
                values.forEach(function(val) {
                    var cb = document.querySelector(filterMap[key] + '[value="' + CSS.escape(val) + '"]');
                    if (cb) {
                        cb.checked = true;
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                    }
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
        var selectedKategori = Array.from(document.querySelectorAll('.filter-kategori:checked')).map(function(cb) { return cb.value; });

        var hasActiveFilters = searchTerm || selectedTemagruppe.length > 0 || selectedKategori.length > 0;

        // Hide featured when filters are active (it would be confusing)
        if (featuredEl) {
            featuredEl.style.display = hasActiveFilters ? 'none' : '';
        }

        var allCards = document.querySelectorAll('.artikkel-card');
        allCards.forEach(function(card) {
            var title = card.dataset.title || '';
            var author = card.dataset.author || '';
            var bedrift = card.dataset.bedrift || '';
            var cardTemagruppe = card.dataset.temagruppe || '';
            var cardKategori = card.dataset.kategori || '';

            var matchesSearch = !searchTerm || title.includes(searchTerm) || author.includes(searchTerm) || bedrift.includes(searchTerm);
            var matchesTemagruppe = selectedTemagruppe.length === 0 || selectedTemagruppe.some(function(t) { return cardTemagruppe.includes(t); });
            var matchesKategori = selectedKategori.length === 0 || selectedKategori.some(function(k) { return cardKategori.includes(k); });

            card.style.display = (matchesSearch && matchesTemagruppe && matchesKategori) ? '' : 'none';
        });

        // Count visible cards in active container
        var visibleInFeatured = (featuredEl && featuredEl.style.display !== 'none') ? 1 : 0;
        var activeContainer = (listEl && !listEl.classList.contains('hidden')) ? listEl : gridEl;
        var visibleInContainer = activeContainer ? activeContainer.querySelectorAll('.artikkel-card:not([style*="display: none"])').length : 0;
        // In list view, featured is included in the table, so don't double count
        var totalVisible = (listEl && !listEl.classList.contains('hidden')) ? visibleInContainer : visibleInFeatured + visibleInContainer;

        updateVisibleCount(totalVisible);
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
            checkboxes.forEach(function(cb) { cb.checked = false; });
            document.querySelectorAll('[data-multiselect] [data-count]').forEach(function(badge) {
                badge.textContent = '0';
                badge.classList.remove('opacity-100');
                badge.classList.add('opacity-0');
                badge.setAttribute('aria-hidden', 'true');
            });
            var mobileCount = document.querySelector('[data-mobile-count]');
            if (mobileCount) {
                mobileCount.textContent = '0';
                mobileCount.classList.remove('opacity-100');
                mobileCount.classList.add('opacity-0');
            }
            applyFilters();
        });
    }

    restoreFromURL();
    applyFilters();
});
</script>

<?php get_footer(); ?>
