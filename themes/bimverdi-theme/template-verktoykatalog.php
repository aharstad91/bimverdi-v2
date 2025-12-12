<?php
/**
 * Template Name: Verktøykatalog
 * 
 * Public tools/software catalog - browse all registered tools
 * Multi-filter by category, formålstema, and type ressurs
 * 
 * @package BimVerdi_Theme
 */

get_header();

// Get filter parameters - support multiple values for multi-select
$search = sanitize_text_field($_GET['s'] ?? '');
$kategorier = isset($_GET['kategorier']) && is_array($_GET['kategorier']) 
    ? array_map('intval', $_GET['kategorier']) 
    : array();
$formaalstema = isset($_GET['formaalstema']) && is_array($_GET['formaalstema']) 
    ? array_map('sanitize_text_field', $_GET['formaalstema']) 
    : array();
$type_ressurs = isset($_GET['type_ressurs']) && is_array($_GET['type_ressurs']) 
    ? array_map('sanitize_text_field', $_GET['type_ressurs']) 
    : array();

// Get all taxonomy terms
$all_kategorier = get_terms(array('taxonomy' => 'verktoykategori', 'hide_empty' => true));

// Define filter options (matching ACF field choices)
$formaalstema_options = array(
    'ByggesaksBIM' => 'ByggesaksBIM',
    'ProsjektBIM' => 'ProsjektBIM',
    'EiendomsBIM' => 'EiendomsBIM',
    'MiljøBIM' => 'MiljøBIM',
    'SirkBIM' => 'SirkBIM',
    'Opplæring' => 'Opplæring',
    'Annet' => 'Annet',
);

$type_ressurs_options = array(
    'Programvare' => 'Programvare',
    'Standard' => 'Standard',
    'Metodikk' => 'Metodikk',
    'Veileder' => 'Veileder',
    'Nettside' => 'Nettside',
    'Digital_tjeneste' => 'Digital tjeneste',
    'Annet' => 'Annet',
);

// Build query
$args = array(
    'post_type' => 'verktoy',
    'posts_per_page' => -1, // Get all for JS filtering, paginate client-side
    'orderby' => 'title',
    'order' => 'ASC',
    'post_status' => 'publish',
);

// Server-side filtering for initial load (supports direct URL params)
if (!empty($search)) {
    $args['s'] = $search;
}

if (!empty($kategorier)) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'verktoykategori',
            'field' => 'term_id',
            'terms' => $kategorier,
        ),
    );
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
?>

<div class="min-h-screen bg-bim-beige-100">
    
    <!-- Hero Header -->
    <div class="bg-gradient-to-r from-purple-600 to-orange-600 text-white py-16">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between flex-wrap gap-6">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4 text-black">
                        <span class="bg-white/20 px-4 py-2 rounded-full text-sm font-bold">🔧 VERKTØY</span>
                        <span class="bg-white/20 px-4 py-2 rounded-full text-sm font-bold"><?php echo $tools_query->found_posts; ?> Løsninger</span>
                    </div>
                    <h1 class="text-5xl font-bold mb-4">Verktøy & Løsninger</h1>
                    <p class="text-xl opacity-95 text-black">Utforsk digitale verktøy og løsninger fra BIM Verdi medlemmer</p>
                </div>
                <div class="text-center">
                    <a href="<?php echo esc_url(home_url('/registrer-verktoy/')); ?>" class="px-8 py-4 bg-white text-purple-600 rounded-lg font-bold hover:bg-gray-100 transition-colors text-lg inline-block">
                        + Legg til verktøy
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container mx-auto px-4 py-12">

        <!-- Search & Filters -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">🔍 Søk & Filtrer</h2>
            <form method="GET" id="verktoy-filter-form">
                
                <!-- Search Bar -->
                <div class="relative mb-6">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input 
                        type="text" 
                        name="s" 
                        id="verktoy-search"
                        value="<?php echo esc_attr($search); ?>"
                        placeholder="Søk etter verktøy..."
                        class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    >
                </div>

                <!-- Multi-Select Filters -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    
                    <!-- Category Filter (Checkboxes) -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <label class="block text-sm font-bold text-gray-900 mb-3">📂 Kategori</label>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <?php if (!empty($all_kategorier) && !is_wp_error($all_kategorier)): ?>
                                <?php foreach ($all_kategorier as $term): ?>
                                <label class="flex items-center gap-2 cursor-pointer hover:bg-white p-1 rounded">
                                    <input type="checkbox" 
                                           name="kategorier[]" 
                                           value="<?php echo esc_attr($term->term_id); ?>"
                                           class="filter-checkbox filter-kategori w-4 h-4 text-purple-600 rounded focus:ring-purple-500"
                                           <?php echo in_array($term->term_id, $kategorier) ? 'checked' : ''; ?>>
                                    <span class="text-sm text-gray-700"><?php echo esc_html($term->name); ?></span>
                                    <span class="text-xs text-gray-400 ml-auto">(<?php echo $term->count; ?>)</span>
                                </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-sm text-gray-500 italic">Ingen kategorier</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Formålstema Filter (Checkboxes) -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <label class="block text-sm font-bold text-gray-900 mb-3">🎯 Formålstema</label>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <?php foreach ($formaalstema_options as $value => $label): ?>
                            <label class="flex items-center gap-2 cursor-pointer hover:bg-white p-1 rounded">
                                <input type="checkbox" 
                                       name="formaalstema[]" 
                                       value="<?php echo esc_attr($value); ?>"
                                       class="filter-checkbox filter-formaal w-4 h-4 text-purple-600 rounded focus:ring-purple-500"
                                       <?php echo in_array($value, $formaalstema) ? 'checked' : ''; ?>>
                                <span class="text-sm text-gray-700"><?php echo esc_html($label); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Type Ressurs Filter (Checkboxes) -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <label class="block text-sm font-bold text-gray-900 mb-3">📦 Type ressurs</label>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <?php foreach ($type_ressurs_options as $value => $label): ?>
                            <label class="flex items-center gap-2 cursor-pointer hover:bg-white p-1 rounded">
                                <input type="checkbox" 
                                       name="type_ressurs[]" 
                                       value="<?php echo esc_attr($value); ?>"
                                       class="filter-checkbox filter-type w-4 h-4 text-purple-600 rounded focus:ring-purple-500"
                                       <?php echo in_array($value, $type_ressurs) ? 'checked' : ''; ?>>
                                <span class="text-sm text-gray-700"><?php echo esc_html($label); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Results count and Reset -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <p class="text-lg font-bold text-gray-900">
                        📊 Viser <span id="visible-count" class="text-purple-600"><?php echo $tools_query->found_posts; ?></span> 
                        av <span class="text-gray-600"><?php echo $tools_query->found_posts; ?></span> verktøy
                    </p>
                    <div class="flex gap-3">
                        <button type="button" id="reset-filters" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition-colors text-sm">
                            ✕ Nullstill filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Active Filters Tags -->
        <div id="active-filters" class="mb-6 flex flex-wrap gap-2 items-center" style="display: none;">
            <span class="text-sm font-semibold text-gray-900">🏷️ Aktive filter:</span>
            <div id="filter-tags"></div>
        </div>

        <!-- Tools Grid -->
        <?php if ($tools_query->have_posts()): ?>
        
        <div id="verktoy-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php while ($tools_query->have_posts()): $tools_query->the_post();
                $eier_id = get_post_meta(get_the_ID(), 'verktoy_eier', true);
                if (!$eier_id) $eier_id = get_post_meta(get_the_ID(), 'eier_leverandor', true);
                $eier = $eier_id ? get_post($eier_id) : null;
                $lenke = get_post_meta(get_the_ID(), 'verktoy_lenke', true);
                $logo_id = get_post_meta(get_the_ID(), 'verktoy_logo', true);
                $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
                $kategori_terms = wp_get_post_terms(get_the_ID(), 'verktoykategori', array('fields' => 'all'));
                $kategori_ids = wp_list_pluck($kategori_terms, 'term_id');
                $kategori_names = wp_list_pluck($kategori_terms, 'name');
                $formaal = get_post_meta(get_the_ID(), 'formaalstema', true);
                $type = get_post_meta(get_the_ID(), 'type_ressurs', true);
            ?>
            
            <a href="<?php the_permalink(); ?>" 
               class="verktoy-card block bg-white rounded-lg shadow-lg hover:shadow-xl transition-all overflow-hidden group"
               data-title="<?php echo esc_attr(strtolower(get_the_title())); ?>"
               data-kategorier="<?php echo esc_attr(implode(',', $kategori_ids)); ?>"
               data-formaal="<?php echo esc_attr($formaal); ?>"
               data-type="<?php echo esc_attr($type); ?>">
                
                <!-- Logo/Image -->
                <?php if ($logo_url): ?>
                <div class="h-48 bg-gradient-to-br from-purple-50 to-orange-50 overflow-hidden flex items-center justify-center p-6">
                    <img src="<?php echo esc_url($logo_url); ?>" 
                         alt="<?php the_title(); ?>" 
                         class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform">
                </div>
                <?php else: ?>
                <div class="h-48 bg-gradient-to-br from-purple-600 to-orange-600 flex items-center justify-center text-white text-5xl">
                    🔧
                </div>
                <?php endif; ?>

                <!-- Content -->
                <div class="p-6">
                    
                    <!-- Categories -->
                    <?php if (!empty($kategori_names)): ?>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <?php foreach (array_slice($kategori_names, 0, 2) as $cat): ?>
                        <span class="text-xs bg-purple-100 text-purple-800 px-3 py-1 rounded-full font-semibold">
                            <?php echo esc_html($cat); ?>
                        </span>
                        <?php endforeach; ?>
                        <?php if (count($kategori_names) > 2): ?>
                        <span class="text-xs text-gray-500 font-semibold">+<?php echo count($kategori_names) - 2; ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Title -->
                    <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-purple-600 transition-colors">
                        <?php the_title(); ?>
                    </h3>

                    <!-- Company -->
                    <?php if ($eier): ?>
                    <div class="text-sm text-gray-700 mb-4 flex items-center gap-2">
                        <span>🏢</span>
                        <span class="text-orange-600 font-semibold"><?php echo esc_html($eier->post_title); ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <p class="text-sm text-gray-600 mb-6 line-clamp-3">
                        <?php echo esc_html(wp_trim_words(get_the_excerpt(), 25)); ?>
                    </p>

                    <!-- CTA -->
                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <?php if (!empty($lenke)): ?>
                            <span onclick="event.preventDefault(); event.stopPropagation(); window.open('<?php echo esc_url($lenke); ?>', '_blank');" class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition-colors text-center text-sm cursor-pointer">
                                🔗 Besøk
                            </span>
                        <?php endif; ?>
                        <span class="flex-1 px-4 py-2 bg-gray-100 text-gray-900 rounded-lg font-semibold hover:bg-gray-200 transition-colors text-center text-sm">
                            📄 Detaljer
                        </span>
                    </div>
                </div>
            </a>

            <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <!-- Pagination -->
        <?php 
        $pagination = paginate_links(array(
            'base' => get_pagenum_link(1) . '%_%',
            'format' => 'page/%#%/',
            'current' => max(1, get_query_var('paged')),
            'total' => $tools_query->max_num_pages,
            'type' => 'array',
            'prev_text' => '← Forrige',
            'next_text' => 'Neste →',
        ));
        
        if (!empty($pagination)): ?>
        <div class="flex justify-center gap-2 flex-wrap">
            <?php foreach ($pagination as $link): ?>
                <div><?php echo str_replace(
                    array('page-numbers', 'current'),
                    array('px-4 py-2 rounded-lg font-semibold transition-colors', 'bg-purple-600 text-white'),
                    str_replace('page-numbers', 'bg-gray-100 text-gray-900 hover:bg-gray-200', $link)
                ); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        
        <!-- No Results -->
        <div class="bg-white rounded-lg shadow-lg text-center py-20 px-6">
            <div class="text-8xl mb-6">🔍</div>
            <h3 class="text-3xl font-bold text-gray-900 mb-3">Ingen verktøy funnet</h3>
            <p class="text-lg text-gray-700 mb-8 max-w-md mx-auto">Prøv å justere filterene dine eller søket for å finne det du leter etter</p>
            <a href="<?php echo get_permalink(); ?>" class="px-8 py-4 bg-purple-600 text-white rounded-lg font-bold hover:bg-purple-700 transition-colors text-lg inline-block">
                Vis alle verktøy
            </a>
        </div>

        <?php endif; ?>

    </div>
</div>

<!-- Live Filter Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('verktoy-search');
    const checkboxes = document.querySelectorAll('.filter-checkbox');
    const cards = document.querySelectorAll('.verktoy-card');
    const visibleCount = document.getElementById('visible-count');
    const resetBtn = document.getElementById('reset-filters');
    const activeFiltersDiv = document.getElementById('active-filters');
    const filterTagsDiv = document.getElementById('filter-tags');
    
    let debounceTimer;
    
    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        
        // Get all checked values for each filter type
        const selectedKategorier = Array.from(document.querySelectorAll('.filter-kategori:checked')).map(cb => cb.value);
        const selectedFormaal = Array.from(document.querySelectorAll('.filter-formaal:checked')).map(cb => cb.value);
        const selectedType = Array.from(document.querySelectorAll('.filter-type:checked')).map(cb => cb.value);
        
        let visibleCards = 0;
        
        cards.forEach(card => {
            const title = card.dataset.title || '';
            const cardKategorier = card.dataset.kategorier ? card.dataset.kategorier.split(',') : [];
            const cardFormaal = card.dataset.formaal || '';
            const cardType = card.dataset.type || '';
            
            // Search filter
            const matchesSearch = !searchTerm || title.includes(searchTerm);
            
            // Category filter (OR logic within category)
            const matchesKategori = selectedKategorier.length === 0 || 
                selectedKategorier.some(k => cardKategorier.includes(k));
            
            // Formålstema filter (OR logic)
            const matchesFormaal = selectedFormaal.length === 0 || 
                selectedFormaal.includes(cardFormaal);
            
            // Type ressurs filter (OR logic)
            const matchesType = selectedType.length === 0 || 
                selectedType.includes(cardType);
            
            // All filters must match (AND logic between filter groups)
            const isVisible = matchesSearch && matchesKategori && matchesFormaal && matchesType;
            
            card.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCards++;
        });
        
        // Update count
        visibleCount.textContent = visibleCards;
        
        // Update active filter tags
        updateFilterTags(searchTerm, selectedKategorier, selectedFormaal, selectedType);
    }
    
    function updateFilterTags(search, kategorier, formaal, type) {
        const hasFilters = search || kategorier.length || formaal.length || type.length;
        activeFiltersDiv.style.display = hasFilters ? 'flex' : 'none';
        
        if (!hasFilters) return;
        
        let tags = '';
        
        if (search) {
            tags += `<span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-semibold">🔍 "${search}"</span>`;
        }
        
        kategorier.forEach(id => {
            const label = document.querySelector(`.filter-kategori[value="${id}"]`)?.parentElement?.querySelector('span')?.textContent || id;
            tags += `<span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">📂 ${label}</span>`;
        });
        
        formaal.forEach(val => {
            tags += `<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">🎯 ${val}</span>`;
        });
        
        type.forEach(val => {
            const label = val.replace('_', ' ');
            tags += `<span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-sm font-semibold">📦 ${label}</span>`;
        });
        
        filterTagsDiv.innerHTML = tags;
    }
    
    // Event listeners
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
    
    // Initial apply if URL has params
    if (window.location.search) {
        applyFilters();
    }
});
</script>

<?php get_footer(); ?>
