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
$formaalstema = isset($_GET['formaalstema']) && is_array($_GET['formaalstema'])
    ? array_map('sanitize_text_field', $_GET['formaalstema'])
    : array();
$type_ressurs = isset($_GET['type_ressurs']) && is_array($_GET['type_ressurs'])
    ? array_map('sanitize_text_field', $_GET['type_ressurs'])
    : array();

// Define filter options
$formaalstema_options = array(
    'ByggesaksBIM' => 'ByggesaksBIM',
    'ProsjektBIM' => 'ProsjektBIM',
    'EiendomsBIM' => 'EiendomsBIM',
    'MiljøBIM' => 'MiljøBIM',
    'SirkBIM' => 'SirkBIM',
    'Opplæring' => 'Opplæring',
);

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

if (!empty($formaalstema) || !empty($type_ressurs)) {
    $args['meta_query'] = array('relation' => 'AND');

    if (!empty($formaalstema)) {
        $args['meta_query'][] = array(
            'key' => 'formaalstema',
            'value' => $formaalstema,
            'compare' => 'IN',
        );
    }

    if (!empty($type_ressurs)) {
        $args['meta_query'][] = array(
            'key' => 'type_ressurs',
            'value' => $type_ressurs,
            'compare' => 'IN',
        );
    }
}

$tools_query = new WP_Query($args);
$is_logged_in = is_user_logged_in();

// Calculate counts for each filter option (static counts - total items per value)
// Uses LIKE because values can be stored as comma-separated strings
$formaalstema_counts = array();
foreach (array_keys($formaalstema_options) as $value) {
    $count_query = new WP_Query([
        'post_type' => 'verktoy',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [[
            'key' => 'formaalstema',
            'value' => $value,
            'compare' => 'LIKE',
        ]],
    ]);
    $formaalstema_counts[$value] = $count_query->found_posts;
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
                ['options' => $formaalstema_options, 'filter_class' => 'filter-formaal'],
                ['options' => $type_ressurs_options, 'filter_class' => 'filter-type'],
            ],
            'max_tags' => 15,
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
                    'name'         => 'formaalstema[]',
                    'label'        => 'Temagruppe',
                    'options'      => $formaalstema_options,
                    'selected'     => $formaalstema,
                    'counts'       => $formaalstema_counts,
                    'filter_class' => 'filter-formaal',
                ],
                [
                    'name'         => 'type_ressurs[]',
                    'label'        => 'Type',
                    'options'      => $type_ressurs_options,
                    'selected'     => $type_ressurs,
                    'counts'       => $type_ressurs_counts,
                    'filter_class' => 'filter-type',
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
            $formaal_raw = get_field('formaalstema', get_the_ID());
            $type_raw = get_field('type_ressurs', get_the_ID());

            // Normalize formaalstema
            $formaal_tags = [];
            $formaal_str = is_array($formaal_raw) ? implode(', ', $formaal_raw) : (string) $formaal_raw;
            foreach (array_keys($formaalstema_options) as $tag) {
                if (stripos($formaal_str, $tag) !== false) {
                    $formaal_tags[] = $tag;
                }
            }
            if (stripos($formaal_str, 'Opplæring') !== false || stripos($formaal_str, 'opplæring') !== false) {
                if (!in_array('Opplæring', $formaal_tags)) $formaal_tags[] = 'Opplæring';
            }

            // Normalize type_ressurs
            $type_tags = [];
            $type_str = is_array($type_raw) ? implode(', ', $type_raw) : (string) $type_raw;
            foreach ($type_ressurs_options as $key => $label) {
                if (stripos($type_str, $label) !== false || stripos($type_str, str_replace('_', ' ', $key)) !== false) {
                    $type_tags[] = $key;
                }
            }

            $items[] = [
                'title'        => get_the_title(),
                'permalink'    => get_the_permalink(),
                'eier_name'    => $eier ? $eier->post_title : '',
                'formaal_tags' => $formaal_tags,
                'type_tags'    => $type_tags,
            ];
        endwhile; wp_reset_postdata();
        ?>

        <!-- Grid View -->
        <div id="verktoy-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
            <?php foreach ($items as $item): ?>

            <a href="<?php echo esc_url($item['permalink']); ?>"
               class="verktoy-card group block bg-white border border-[#E7E5E4] rounded-xl shadow-sm hover:shadow-md hover:border-[#D6D3D1] transition-all p-6"
               data-title="<?php echo esc_attr(strtolower($item['title'])); ?>"
               data-formaal="<?php echo esc_attr(implode(',', $item['formaal_tags'])); ?>"
               data-type="<?php echo esc_attr(implode(',', $item['type_tags'])); ?>">

                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mb-5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#A8A29E" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                </div>

                <h3 class="text-base font-semibold text-[#111827] mb-2 line-clamp-2 group-hover:text-[#1F2937]">
                    <?php echo esc_html($item['title']); ?>
                </h3>

                <div class="flex items-center justify-between pt-4 mt-4 border-t border-[#E7E5E4]">
                    <span class="text-xs text-[#57534E]"><?php echo esc_html($item['eier_name']); ?></span>
                    <span class="inline-flex items-center gap-1 text-sm font-medium text-[#111827] group-hover:gap-2 transition-all">
                        Se detaljer
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                </div>
            </a>

            <?php endforeach; ?>
        </div>

        <!-- List View (hidden by default) -->
        <div id="verktoy-list" class="hidden mb-8">
            <div class="bg-white rounded-xl border border-[#E7E5E4] overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-[#FAFAF9] border-b border-[#E7E5E4]">
                        <tr>
                            <th class="px-4 py-3 font-medium text-[#57534E]">Navn</th>
                            <th class="px-4 py-3 font-medium text-[#57534E]">Type</th>
                            <th class="px-4 py-3 font-medium text-[#57534E]">Tema</th>
                            <th class="px-4 py-3 font-medium text-[#57534E]">Leverandør</th>
                            <th class="px-4 py-3 font-medium text-[#57534E] w-16">Lenke</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4]">
                        <?php foreach ($items as $item): ?>
                        <tr class="verktoy-card hover:bg-[#FAFAF9] transition-colors"
                            data-title="<?php echo esc_attr(strtolower($item['title'])); ?>"
                            data-formaal="<?php echo esc_attr(implode(',', $item['formaal_tags'])); ?>"
                            data-type="<?php echo esc_attr(implode(',', $item['type_tags'])); ?>">
                            <td class="px-4 py-3 font-medium text-[#111827]"><?php echo esc_html($item['title']); ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach ($item['type_tags'] as $tag):
                                        $label = isset($type_ressurs_options[$tag]) ? $type_ressurs_options[$tag] : $tag;
                                    ?>
                                    <span class="text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-2 py-0.5 rounded"><?php echo esc_html($label); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach ($item['formaal_tags'] as $tag): ?>
                                    <span class="text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-2 py-0.5 rounded"><?php echo esc_html($tag); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-[#57534E]"><?php echo esc_html($item['eier_name']); ?></td>
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
            'formaalstema': '.filter-formaal:checked',
            'type_ressurs': '.filter-type:checked'
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
            'formaalstema': '.filter-formaal',
            'type_ressurs': '.filter-type'
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
        var selectedFormaal = Array.from(document.querySelectorAll('.filter-formaal:checked')).map(function(cb) { return cb.value; });
        var selectedType = Array.from(document.querySelectorAll('.filter-type:checked')).map(function(cb) { return cb.value; });

        // Filter all cards in both grid and list
        var allCards = document.querySelectorAll('.verktoy-card');
        allCards.forEach(function(card) {
            var title = card.dataset.title || '';
            var cardFormaals = (card.dataset.formaal || '').split(',').filter(Boolean);
            var cardTypes = (card.dataset.type || '').split(',').filter(Boolean);

            var matchesSearch = !searchTerm || title.includes(searchTerm);
            var matchesFormaal = selectedFormaal.length === 0 || selectedFormaal.some(function(f) { return cardFormaals.indexOf(f) !== -1; });
            var matchesType = selectedType.length === 0 || selectedType.some(function(t) { return cardTypes.indexOf(t) !== -1; });

            card.style.display = (matchesSearch && matchesFormaal && matchesType) ? '' : 'none';
        });

        // Count only from active container
        var activeContainer = (listEl && !listEl.classList.contains('hidden')) ? listEl : gridEl;
        var visibleCards = activeContainer ? activeContainer.querySelectorAll('.verktoy-card:not([style*="display: none"])').length : 0;

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
