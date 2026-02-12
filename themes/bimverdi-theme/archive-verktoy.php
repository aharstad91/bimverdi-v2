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
            'compare' => '=',
        ]],
    ]);
    $formaalstema_counts[$value] = $count_query->found_posts;
}

$type_ressurs_counts = array();
foreach (array_keys($type_ressurs_options) as $value) {
    $count_query = new WP_Query([
        'post_type' => 'verktoy',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [[
            'key' => 'type_ressurs',
            'value' => $value,
            'compare' => '=',
        ]],
    ]);
    $type_ressurs_counts[$value] = $count_query->found_posts;
}
?>

<div class="min-h-screen bg-[#F7F5EF]">

    <!-- Page Header -->
    <div class="bg-white border-b border-[#E5E0D8]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-[#1A1A1A] mb-2">Verktøykatalog</h1>
                    <p class="text-[#5A5A5A]">
                        Utforsk <?php echo $tools_query->found_posts; ?> digitale verktøy og løsninger fra BIM Verdi-nettverket
                    </p>
                </div>
                <?php if ($is_logged_in): ?>
                <a href="<?php echo esc_url(home_url('/min-side/verktoy/registrer/')); ?>"
                   class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-lg text-white bg-[#1A1A1A] hover:bg-[#333] transition-colors flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Registrer verktøy
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

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
        ]);
        ?>

        <!-- Tools Grid -->
        <?php if ($tools_query->have_posts()): ?>

        <div id="verktoy-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
            <?php while ($tools_query->have_posts()): $tools_query->the_post();
                $eier_id = get_field('eier_leverandor', get_the_ID());
                $eier = $eier_id ? get_post($eier_id) : null;
                $formaal = get_field('formaalstema', get_the_ID());
                $type = get_field('type_ressurs', get_the_ID());
            ?>

            <a href="<?php the_permalink(); ?>"
               class="verktoy-card group block bg-[#F2F0EB] rounded-xl p-6 hover:bg-[#EBE8E1] transition-colors"
               data-title="<?php echo esc_attr(strtolower(get_the_title())); ?>"
               data-formaal="<?php echo esc_attr($formaal); ?>"
               data-type="<?php echo esc_attr($type); ?>">

                <!-- Icon -->
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mb-5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9D8F7F" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                </div>

                <!-- Title -->
                <h3 class="text-base font-semibold text-[#1A1A1A] mb-2 line-clamp-2 group-hover:text-[#333]">
                    <?php the_title(); ?>
                    <?php echo bimverdi_admin_id_badge(); ?>
                </h3>

                <!-- Footer -->
                <div class="flex items-center justify-between pt-4 mt-4 border-t border-[#D6D1C6]/40">
                    <span class="text-xs text-[#5A5A5A]"><?php echo $eier ? esc_html($eier->post_title) : ''; ?></span>
                    <span class="inline-flex items-center gap-1 text-sm font-medium text-[#1A1A1A] group-hover:gap-2 transition-all">
                        Se detaljer
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                </div>
            </a>

            <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <?php else: ?>

        <!-- Empty State -->
        <div class="bg-white rounded-lg border border-[#E5E0D8] text-center py-16 px-6">
            <div class="w-16 h-16 bg-[#F2F0EB] rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </div>
            <h3 class="text-xl font-bold text-[#1A1A1A] mb-2">Ingen verktøy funnet</h3>
            <p class="text-[#5A5A5A] mb-6 max-w-md mx-auto">Prøv å justere filtrene eller søket for å finne det du leter etter</p>
            <a href="<?php echo get_post_type_archive_link('verktoy'); ?>" class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-lg text-white bg-[#1A1A1A] hover:bg-[#333] transition-colors">
                Vis alle verktøy
            </a>
        </div>

        <?php endif; ?>

    </div>
</div>

<!-- Live Filter Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('verktoy-filter-form-search');
    var checkboxes = document.querySelectorAll('.filter-checkbox');
    var cards = document.querySelectorAll('.verktoy-card');
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

    function applyFilters() {
        var searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        var selectedFormaal = Array.from(document.querySelectorAll('.filter-formaal:checked')).map(function(cb) { return cb.value; });
        var selectedType = Array.from(document.querySelectorAll('.filter-type:checked')).map(function(cb) { return cb.value; });

        var visibleCards = 0;

        cards.forEach(function(card) {
            var title = card.dataset.title || '';
            var cardFormaal = card.dataset.formaal || '';
            var cardType = card.dataset.type || '';

            var matchesSearch = !searchTerm || title.includes(searchTerm);
            var matchesFormaal = selectedFormaal.length === 0 || selectedFormaal.includes(cardFormaal);
            var matchesType = selectedType.length === 0 || selectedType.includes(cardType);

            var isVisible = matchesSearch && matchesFormaal && matchesType;

            card.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCards++;
        });

        updateVisibleCount(visibleCards);
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

    // Apply filters on page load if URL has params
    if (window.location.search) {
        applyFilters();
    }
});
</script>

<?php get_footer(); ?>
