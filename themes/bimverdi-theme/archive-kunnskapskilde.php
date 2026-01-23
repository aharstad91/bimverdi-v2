<?php
/**
 * Archive template for Kunnskapskilde (Knowledge Sources)
 *
 * Public knowledge source catalog with BIM Verdi design.
 * Filter on temagruppe and kategori as per requirements.
 * URL: /kunnskapskilder (from CPT rewrite slug)
 *
 * @package BimVerdi_Theme
 */

get_header();

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
    'ByggesaksBIM' => 'ByggesaksBIM',
    'ProsjektBIM' => 'ProsjektBIM',
    'EiendomsBIM' => 'EiendomsBIM',
    'MiljøBIM' => 'MiljøBIM',
    'SirkBIM' => 'SirkBIM',
    'BIMtech' => 'BIMtech',
);

$kildetype_options = array(
    'standard' => 'Standard',
    'veileder' => 'Veileder',
    'mal' => 'Mal/Template',
    'forskningsrapport' => 'Forskningsrapport',
    'casestudie' => 'Casestudie',
    'opplaering' => 'Opplæring',
    'dokumentasjon' => 'Dokumentasjon',
    'annet' => 'Annet',
);

// Get kategori terms from taxonomy
$kategori_terms = get_terms(array(
    'taxonomy' => 'kunnskapskildekategori',
    'hide_empty' => false,
));

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
?>

<div class="min-h-screen bg-[#F7F5EF]">

    <!-- Page Header -->
    <div class="bg-white border-b border-[#E5E0D8]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-[#1A1A1A] mb-2">Kunnskapskilder</h1>
                    <p class="text-[#5A5A5A]">
                        Utforsk <?php echo $kunnskapskilder_query->found_posts; ?> standarder, veiledere og ressurser fra BIM Verdi-nettverket
                    </p>
                </div>
                <?php if ($is_logged_in): ?>
                <a href="<?php echo esc_url(bimverdi_minside_url('kunnskapskilder/registrer')); ?>"
                   class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-lg text-white bg-[#1A1A1A] hover:bg-[#333] transition-colors flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Registrer kunnskapskilde
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Search & Filters -->
        <div class="bg-white rounded-lg border border-[#E5E0D8] p-6 mb-8">
            <form method="GET" id="kunnskapskilde-filter-form">

                <!-- Search Bar -->
                <div class="relative mb-6">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#5A5A5A]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="text"
                        name="s"
                        id="kunnskapskilde-search"
                        value="<?php echo esc_attr($search); ?>"
                        placeholder="Søk etter kunnskapskilder..."
                        class="w-full pl-12 pr-4 py-3 border border-[#E5E0D8] rounded-lg focus:ring-2 focus:ring-[#1A1A1A] focus:border-transparent text-[#1A1A1A] placeholder-[#9A9A9A]"
                    >
                </div>

                <!-- Filter Sections -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

                    <!-- Temagruppe Filter -->
                    <div>
                        <label class="block text-sm font-medium text-[#1A1A1A] mb-3">Temagruppe</label>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <?php foreach ($temagruppe_options as $value => $label): ?>
                            <label class="flex items-center gap-2 cursor-pointer p-1 rounded hover:bg-[#F7F5EF]">
                                <input type="checkbox"
                                       name="temagruppe[]"
                                       value="<?php echo esc_attr(sanitize_title($value)); ?>"
                                       class="filter-checkbox filter-temagruppe w-4 h-4 text-[#1A1A1A] rounded border-[#E5E0D8] focus:ring-[#1A1A1A]"
                                       <?php echo in_array(sanitize_title($value), $temagruppe) ? 'checked' : ''; ?>>
                                <span class="text-sm text-[#5A5A5A]"><?php echo esc_html($label); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Kildetype Filter -->
                    <div>
                        <label class="block text-sm font-medium text-[#1A1A1A] mb-3">Kildetype</label>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <?php foreach ($kildetype_options as $value => $label): ?>
                            <label class="flex items-center gap-2 cursor-pointer p-1 rounded hover:bg-[#F7F5EF]">
                                <input type="checkbox"
                                       name="kildetype[]"
                                       value="<?php echo esc_attr($value); ?>"
                                       class="filter-checkbox filter-kildetype w-4 h-4 text-[#1A1A1A] rounded border-[#E5E0D8] focus:ring-[#1A1A1A]"
                                       <?php echo in_array($value, $kildetype) ? 'checked' : ''; ?>>
                                <span class="text-sm text-[#5A5A5A]"><?php echo esc_html($label); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Kategori Filter (from taxonomy) -->
                    <?php if (!empty($kategori_terms) && !is_wp_error($kategori_terms)): ?>
                    <div>
                        <label class="block text-sm font-medium text-[#1A1A1A] mb-3">Kategori</label>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <?php foreach ($kategori_terms as $term): ?>
                            <label class="flex items-center gap-2 cursor-pointer p-1 rounded hover:bg-[#F7F5EF]">
                                <input type="checkbox"
                                       name="kategori[]"
                                       value="<?php echo esc_attr($term->slug); ?>"
                                       class="filter-checkbox filter-kategori w-4 h-4 text-[#1A1A1A] rounded border-[#E5E0D8] focus:ring-[#1A1A1A]"
                                       <?php echo in_array($term->slug, $kategori) ? 'checked' : ''; ?>>
                                <span class="text-sm text-[#5A5A5A]"><?php echo esc_html($term->name); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Results count and Reset -->
                <div class="flex items-center justify-between pt-4 border-t border-[#E5E0D8]">
                    <p class="text-sm text-[#5A5A5A]">
                        Viser <span id="visible-count" class="font-medium text-[#1A1A1A]"><?php echo $kunnskapskilder_query->found_posts; ?></span>
                        av <?php echo $kunnskapskilder_query->found_posts; ?> kunnskapskilder
                    </p>
                    <button type="button" id="reset-filters" class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
                        Nullstill filter
                    </button>
                </div>
            </form>
        </div>

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

            <div class="kunnskapskilde-card bg-white rounded-lg border border-[#E5E0D8] overflow-hidden hover:border-[#1A1A1A] transition-colors group"
               data-title="<?php echo esc_attr(strtolower($navn)); ?>"
               data-temagruppe="<?php echo esc_attr($temagruppe_slugs); ?>"
               data-kildetype="<?php echo esc_attr($kildetype_val); ?>"
               data-kategori="<?php echo esc_attr($kategori_slugs); ?>">

                <!-- Icon Header -->
                <div class="h-32 bg-[#F7F5EF] overflow-hidden flex items-center justify-center p-6">
                    <div class="w-16 h-16 bg-white rounded-lg flex items-center justify-center shadow-sm">
                        <?php
                        // Icon based on kildetype
                        $icon_map = [
                            'standard' => '<path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path><rect x="9" y="3" width="6" height="4" rx="2"></rect><path d="m9 14 2 2 4-4"></path>',
                            'veileder' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>',
                            'mal' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line>',
                            'forskningsrapport' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>',
                            'opplaering' => '<path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path>',
                        ];
                        $icon_path = isset($icon_map[$kildetype_val]) ? $icon_map[$kildetype_val] : '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>';
                        ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]"><?php echo $icon_path; ?></svg>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-5">

                    <!-- Tags -->
                    <div class="flex flex-wrap gap-2 mb-3">
                        <?php if (!empty($temagruppe_terms_post)): ?>
                        <span class="text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-2 py-1 rounded">
                            <?php echo esc_html($temagruppe_terms_post[0]->name); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($kildetype_val && isset($kildetype_options[$kildetype_val])): ?>
                        <span class="text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-2 py-1 rounded">
                            <?php echo esc_html($kildetype_options[$kildetype_val]); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Title -->
                    <h3 class="text-lg font-bold text-[#1A1A1A] mb-1 group-hover:text-[#5A5A5A] transition-colors line-clamp-2">
                        <?php echo esc_html($navn); ?>
                    </h3>

                    <!-- Publisher & Year -->
                    <?php if ($utgiver || $utgivelsesaar): ?>
                    <p class="text-sm text-[#5A5A5A] mb-3">
                        <?php
                        $meta_parts = array();
                        if ($utgiver) $meta_parts[] = $utgiver;
                        if ($utgivelsesaar) $meta_parts[] = $utgivelsesaar;
                        echo esc_html(implode(' - ', $meta_parts));
                        ?>
                    </p>
                    <?php endif; ?>

                    <!-- Description -->
                    <?php if ($kort_beskrivelse): ?>
                    <p class="text-sm text-[#5A5A5A] mb-4 line-clamp-2">
                        <?php echo esc_html(wp_trim_words($kort_beskrivelse, 15)); ?>
                    </p>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="flex gap-2 pt-4 border-t border-[#E5E0D8]">
                        <a href="<?php the_permalink(); ?>"
                           class="flex-1 px-4 py-2 text-sm font-medium text-center text-[#1A1A1A] bg-[#F2F0EB] rounded-lg hover:bg-[#E5E0D8] transition-colors">
                            Se detaljer
                        </a>
                        <?php if (!empty($ekstern_lenke)): ?>
                        <a href="<?php echo esc_url($ekstern_lenke); ?>"
                           target="_blank"
                           rel="noopener"
                           class="px-4 py-2 text-sm font-medium text-center text-white bg-[#1A1A1A] rounded-lg hover:bg-[#333] transition-colors">
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
        <div class="bg-white rounded-lg border border-[#E5E0D8] text-center py-16 px-6">
            <div class="w-16 h-16 bg-[#F2F0EB] rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </div>
            <h3 class="text-xl font-bold text-[#1A1A1A] mb-2">Ingen kunnskapskilder funnet</h3>
            <p class="text-[#5A5A5A] mb-6 max-w-md mx-auto">Prøv å justere filtrene eller søket for å finne det du leter etter</p>
            <a href="<?php echo get_post_type_archive_link('kunnskapskilde'); ?>" class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-lg text-white bg-[#1A1A1A] hover:bg-[#333] transition-colors">
                Vis alle kunnskapskilder
            </a>
        </div>

        <?php endif; ?>

        <?php if (!$is_logged_in): ?>
        <!-- Login Prompt for non-logged-in users -->
        <div class="bg-white rounded-lg border border-[#E5E0D8] p-8 text-center mt-8">
            <div class="w-12 h-12 bg-[#F2F0EB] rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
            </div>
            <h3 class="text-lg font-bold text-[#1A1A1A] mb-2">Vil du bidra?</h3>
            <p class="text-[#5A5A5A] mb-4">Logg inn for å registrere egne kunnskapskilder og få tilgang til deltakerinnhold</p>
            <a href="<?php echo home_url('/logg-inn/?redirect_to=' . urlencode(get_post_type_archive_link('kunnskapskilde'))); ?>" class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-lg text-white bg-[#1A1A1A] hover:bg-[#333] transition-colors">
                Logg inn
            </a>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Live Filter Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('kunnskapskilde-search');
    const checkboxes = document.querySelectorAll('.filter-checkbox');
    const cards = document.querySelectorAll('.kunnskapskilde-card');
    const visibleCount = document.getElementById('visible-count');
    const resetBtn = document.getElementById('reset-filters');

    let debounceTimer;

    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedTemagruppe = Array.from(document.querySelectorAll('.filter-temagruppe:checked')).map(cb => cb.value);
        const selectedKildetype = Array.from(document.querySelectorAll('.filter-kildetype:checked')).map(cb => cb.value);
        const selectedKategori = Array.from(document.querySelectorAll('.filter-kategori:checked')).map(cb => cb.value);

        let visibleCards = 0;

        cards.forEach(card => {
            const title = card.dataset.title || '';
            const cardTemagruppe = card.dataset.temagruppe || '';
            const cardKildetype = card.dataset.kildetype || '';
            const cardKategori = card.dataset.kategori || '';

            const matchesSearch = !searchTerm || title.includes(searchTerm);
            const matchesTemagruppe = selectedTemagruppe.length === 0 || selectedTemagruppe.some(t => cardTemagruppe.includes(t));
            const matchesKildetype = selectedKildetype.length === 0 || selectedKildetype.includes(cardKildetype);
            const matchesKategori = selectedKategori.length === 0 || selectedKategori.some(k => cardKategori.includes(k));

            const isVisible = matchesSearch && matchesTemagruppe && matchesKildetype && matchesKategori;

            card.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCards++;
        });

        visibleCount.textContent = visibleCards;
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(applyFilters, 200);
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', applyFilters);
    });

    resetBtn.addEventListener('click', function() {
        searchInput.value = '';
        checkboxes.forEach(cb => cb.checked = false);
        applyFilters();
    });

    if (window.location.search) {
        applyFilters();
    }
});
</script>

<?php get_footer(); ?>
