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

    <!-- Page Header -->
    <div class="bg-white border-b border-[#E7E5E4]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-[#111827] mb-2">Kunnskapskilder</h1>
                    <p class="text-[#57534E]">
                        Utforsk <?php echo $kunnskapskilder_query->found_posts; ?> standarder, veiledere og ressurser fra BIM Verdi-nettverket
                    </p>
                </div>
                <?php if ($is_logged_in): ?>
                <a href="<?php echo esc_url(bimverdi_minside_url('kunnskapskilder/registrer')); ?>"
                   class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-lg text-white bg-[#111827] hover:bg-[#1F2937] transition-colors flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Registrer kunnskapskilde
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

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
        ]);
        ?>

        <!-- Kunnskapskilder Grid -->
        <?php if ($kunnskapskilder_query->have_posts()): ?>

        <div id="kunnskapskilde-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php while ($kunnskapskilder_query->have_posts()): $kunnskapskilder_query->the_post();
                $navn = get_field('kunnskapskilde_navn', get_the_ID()) ?: get_the_title();
                $kort_beskrivelse = get_field('kort_beskrivelse', get_the_ID());
                $ekstern_lenke = get_field('ekstern_lenke', get_the_ID());
                $utgiver = get_field('utgiver', get_the_ID());
                $kildetype_val = get_field('kildetype', get_the_ID());
                $spraak = get_field('spraak', get_the_ID());
                $utgivelsesaar = get_field('utgivelsesaar', get_the_ID());

                // Get temagrupper
                $temagruppe_terms_post = wp_get_post_terms(get_the_ID(), 'temagruppe');
                $temagruppe_slugs = !empty($temagruppe_terms_post) ? implode(' ', wp_list_pluck($temagruppe_terms_post, 'slug')) : '';

                // Get kategori
                $kategori_terms_post = wp_get_post_terms(get_the_ID(), 'kunnskapskildekategori');
                $kategori_slugs = !empty($kategori_terms_post) ? implode(' ', wp_list_pluck($kategori_terms_post, 'slug')) : '';
            ?>

            <div class="kunnskapskilde-card bg-white rounded-xl border border-[#E7E5E4] shadow-sm overflow-hidden hover:shadow-md hover:border-[#D6D3D1] transition-all group"
               data-title="<?php echo esc_attr(strtolower($navn)); ?>"
               data-temagruppe="<?php echo esc_attr($temagruppe_slugs); ?>"
               data-kildetype="<?php echo esc_attr($kildetype_val); ?>"
               data-kategori="<?php echo esc_attr($kategori_slugs); ?>">

                <!-- Icon Header -->
                <div class="h-32 bg-[#FAFAF9] overflow-hidden flex items-center justify-center p-6">
                    <div class="w-16 h-16 bg-white rounded-lg flex items-center justify-center shadow-sm">
                        <?php
                        // Icon based on kildetype
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
                        $icon_path = isset($icon_map[$kildetype_val]) ? $icon_map[$kildetype_val] : '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>';
                        ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-[#57534E]"><?php echo $icon_path; ?></svg>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-5">

                    <!-- Tags -->
                    <div class="flex flex-wrap gap-2 mb-3">
                        <?php if (!empty($temagruppe_terms_post)): ?>
                        <span class="text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-2 py-1 rounded">
                            <?php echo esc_html($temagruppe_terms_post[0]->name); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($kildetype_val && isset($kildetype_options[$kildetype_val])): ?>
                        <span class="text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-2 py-1 rounded">
                            <?php echo esc_html($kildetype_options[$kildetype_val]); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Title -->
                    <h3 class="text-lg font-bold text-[#111827] mb-1 group-hover:text-[#57534E] transition-colors line-clamp-2">
                        <?php echo esc_html($navn); ?>
                        <?php echo bimverdi_admin_id_badge(); ?>
                    </h3>

                    <!-- Publisher & Year -->
                    <?php if ($utgiver || $utgivelsesaar): ?>
                    <p class="text-sm text-[#57534E] mb-3">
                        <?php
                        $meta_parts = array();
                        if ($utgiver) $meta_parts[] = $utgiver;
                        if ($utgivelsesaar) {
                            $aar_display = ($utgivelsesaar === 'eldre') ? 'Eldre enn 2022' : $utgivelsesaar;
                            $meta_parts[] = $aar_display;
                        }
                        echo esc_html(implode(' - ', $meta_parts));
                        ?>
                    </p>
                    <?php endif; ?>

                    <!-- Description -->
                    <?php if ($kort_beskrivelse): ?>
                    <p class="text-sm text-[#57534E] mb-4 line-clamp-2">
                        <?php echo esc_html(wp_trim_words($kort_beskrivelse, 15)); ?>
                    </p>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="flex gap-2 pt-4 border-t border-[#E7E5E4]">
                        <a href="<?php the_permalink(); ?>"
                           class="flex-1 px-4 py-2 text-sm font-medium text-center text-[#111827] bg-[#F5F5F4] rounded-lg hover:bg-[#E7E5E4] transition-colors">
                            Se detaljer
                        </a>
                        <?php if (!empty($ekstern_lenke)): ?>
                        <a href="<?php echo esc_url($ekstern_lenke); ?>"
                           target="_blank"
                           rel="noopener"
                           class="px-4 py-2 text-sm font-medium text-center text-white bg-[#111827] rounded-lg hover:bg-[#1F2937] transition-colors">
                            Besøk
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php endwhile; wp_reset_postdata(); ?>
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

        <?php if (!$is_logged_in): ?>
        <!-- Login Prompt for non-logged-in users -->
        <div class="bg-white rounded-lg border border-[#E7E5E4] p-8 text-center mt-8">
            <div class="w-12 h-12 bg-[#F5F5F4] rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#57534E]"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
            </div>
            <h3 class="text-lg font-bold text-[#111827] mb-2">Vil du bidra?</h3>
            <p class="text-[#57534E] mb-4">Logg inn for å registrere egne kunnskapskilder og få tilgang til deltakerinnhold</p>
            <a href="<?php echo home_url('/logg-inn/?redirect_to=' . urlencode(get_post_type_archive_link('kunnskapskilde'))); ?>" class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-lg text-white bg-[#111827] hover:bg-[#1F2937] transition-colors">
                Logg inn
            </a>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Live Filter Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('kunnskapskilde-filter-form-search');
    var checkboxes = document.querySelectorAll('.filter-checkbox');
    var cards = document.querySelectorAll('.kunnskapskilde-card');
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
        var selectedTemagruppe = Array.from(document.querySelectorAll('.filter-temagruppe:checked')).map(function(cb) { return cb.value; });
        var selectedKildetype = Array.from(document.querySelectorAll('.filter-kildetype:checked')).map(function(cb) { return cb.value; });
        var selectedKategori = Array.from(document.querySelectorAll('.filter-kategori:checked')).map(function(cb) { return cb.value; });

        var visibleCards = 0;

        cards.forEach(function(card) {
            var title = card.dataset.title || '';
            var cardTemagruppe = card.dataset.temagruppe || '';
            var cardKildetype = card.dataset.kildetype || '';
            var cardKategori = card.dataset.kategori || '';

            var matchesSearch = !searchTerm || title.includes(searchTerm);
            var matchesTemagruppe = selectedTemagruppe.length === 0 || selectedTemagruppe.some(function(t) { return cardTemagruppe.includes(t); });
            var matchesKildetype = selectedKildetype.length === 0 || selectedKildetype.includes(cardKildetype);
            var matchesKategori = selectedKategori.length === 0 || selectedKategori.some(function(k) { return cardKategori.includes(k); });

            var isVisible = matchesSearch && matchesTemagruppe && matchesKildetype && matchesKategori;

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
