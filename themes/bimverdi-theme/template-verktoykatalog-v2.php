<?php
/**
 * Template Name: Verktøykatalog v2
 * 
 * Public tools/software catalog with new BIM Verdi design.
 * Clean, minimal styling following UI Contract v1.
 * 
 * @package BimVerdi_Theme
 */

get_header();

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
                <a href="<?php echo esc_url(home_url('/min-side/registrer-verktoy/')); ?>" 
                   class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-lg text-white bg-[#1A1A1A] hover:bg-[#333] transition-colors flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Registrer verktøy
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Search & Filters -->
        <div class="bg-white rounded-lg border border-[#E5E0D8] p-6 mb-8">
            <form method="GET" id="verktoy-filter-form">
                
                <!-- Search Bar -->
                <div class="relative mb-6">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#5A5A5A]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input 
                        type="text" 
                        name="s" 
                        id="verktoy-search"
                        value="<?php echo esc_attr($search); ?>"
                        placeholder="Søk etter verktøy..."
                        class="w-full pl-12 pr-4 py-3 border border-[#E5E0D8] rounded-lg focus:ring-2 focus:ring-[#1A1A1A] focus:border-transparent text-[#1A1A1A] placeholder-[#9A9A9A]"
                    >
                </div>

                <!-- Filter Sections -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    
                    <!-- Formålstema Filter -->
                    <div>
                        <label class="block text-sm font-medium text-[#1A1A1A] mb-3">Temagruppe</label>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <?php foreach ($formaalstema_options as $value => $label): ?>
                            <label class="flex items-center gap-2 cursor-pointer p-1 rounded hover:bg-[#F7F5EF]">
                                <input type="checkbox" 
                                       name="formaalstema[]" 
                                       value="<?php echo esc_attr($value); ?>"
                                       class="filter-checkbox filter-formaal w-4 h-4 text-[#1A1A1A] rounded border-[#E5E0D8] focus:ring-[#1A1A1A]"
                                       <?php echo in_array($value, $formaalstema) ? 'checked' : ''; ?>>
                                <span class="text-sm text-[#5A5A5A]"><?php echo esc_html($label); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Type Ressurs Filter -->
                    <div>
                        <label class="block text-sm font-medium text-[#1A1A1A] mb-3">Type ressurs</label>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <?php foreach ($type_ressurs_options as $value => $label): ?>
                            <label class="flex items-center gap-2 cursor-pointer p-1 rounded hover:bg-[#F7F5EF]">
                                <input type="checkbox" 
                                       name="type_ressurs[]" 
                                       value="<?php echo esc_attr($value); ?>"
                                       class="filter-checkbox filter-type w-4 h-4 text-[#1A1A1A] rounded border-[#E5E0D8] focus:ring-[#1A1A1A]"
                                       <?php echo in_array($value, $type_ressurs) ? 'checked' : ''; ?>>
                                <span class="text-sm text-[#5A5A5A]"><?php echo esc_html($label); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Results count and Reset -->
                <div class="flex items-center justify-between pt-4 border-t border-[#E5E0D8]">
                    <p class="text-sm text-[#5A5A5A]">
                        Viser <span id="visible-count" class="font-medium text-[#1A1A1A]"><?php echo $tools_query->found_posts; ?></span> 
                        av <?php echo $tools_query->found_posts; ?> verktøy
                    </p>
                    <button type="button" id="reset-filters" class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
                        ✕ Nullstill filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Tools Grid -->
        <?php if ($tools_query->have_posts()): ?>
        
        <div id="verktoy-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php while ($tools_query->have_posts()): $tools_query->the_post();
                $eier_id = get_post_meta(get_the_ID(), 'eier_leverandor', true);
                $eier = $eier_id ? get_post($eier_id) : null;
                $lenke = get_post_meta(get_the_ID(), 'verktoy_lenke', true);
                $logo_id = get_post_meta(get_the_ID(), 'verktoy_logo', true);
                $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
                $formaal = get_post_meta(get_the_ID(), 'formaalstema', true);
                $type = get_post_meta(get_the_ID(), 'type_ressurs', true);
            ?>
            
            <div class="verktoy-card bg-white rounded-lg border border-[#E5E0D8] overflow-hidden hover:border-[#1A1A1A] transition-colors group"
               data-title="<?php echo esc_attr(strtolower(get_the_title())); ?>"
               data-formaal="<?php echo esc_attr($formaal); ?>"
               data-type="<?php echo esc_attr($type); ?>">
                
                <!-- Logo/Image -->
                <div class="h-40 bg-[#F7F5EF] overflow-hidden flex items-center justify-center p-6">
                    <?php if ($logo_url): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" 
                             alt="<?php the_title(); ?>" 
                             class="max-h-full max-w-full object-contain">
                    <?php else: ?>
                        <div class="w-16 h-16 bg-[#E5E0D8] rounded-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Content -->
                <div class="p-5">
                    
                    <!-- Tags -->
                    <div class="flex flex-wrap gap-2 mb-3">
                        <?php if ($formaal): ?>
                        <span class="text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-2 py-1 rounded">
                            <?php echo esc_html($formaal); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($type): ?>
                        <span class="text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-2 py-1 rounded">
                            <?php echo esc_html(str_replace('_', ' ', $type)); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Title -->
                    <h3 class="text-lg font-bold text-[#1A1A1A] mb-1 group-hover:text-[#5A5A5A] transition-colors">
                        <?php the_title(); ?>
                    </h3>

                    <!-- Company -->
                    <?php if ($eier): ?>
                    <p class="text-sm text-[#5A5A5A] mb-3">
                        <?php echo esc_html($eier->post_title); ?>
                    </p>
                    <?php endif; ?>

                    <!-- Description -->
                    <p class="text-sm text-[#5A5A5A] mb-4 line-clamp-2">
                        <?php echo esc_html(wp_trim_words(get_the_excerpt(), 15)); ?>
                    </p>

                    <!-- Actions -->
                    <div class="flex gap-2 pt-4 border-t border-[#E5E0D8]">
                        <a href="<?php the_permalink(); ?>" 
                           class="flex-1 px-4 py-2 text-sm font-medium text-center text-[#1A1A1A] bg-[#F2F0EB] rounded-lg hover:bg-[#E5E0D8] transition-colors">
                            Se detaljer
                        </a>
                        <?php if (!empty($lenke)): ?>
                        <a href="<?php echo esc_url($lenke); ?>" 
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
            <h3 class="text-xl font-bold text-[#1A1A1A] mb-2">Ingen verktøy funnet</h3>
            <p class="text-[#5A5A5A] mb-6 max-w-md mx-auto">Prøv å justere filtrene eller søket for å finne det du leter etter</p>
            <a href="<?php echo get_permalink(); ?>" class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-lg text-white bg-[#1A1A1A] hover:bg-[#333] transition-colors">
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
    
    let debounceTimer;
    
    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedFormaal = Array.from(document.querySelectorAll('.filter-formaal:checked')).map(cb => cb.value);
        const selectedType = Array.from(document.querySelectorAll('.filter-type:checked')).map(cb => cb.value);
        
        let visibleCards = 0;
        
        cards.forEach(card => {
            const title = card.dataset.title || '';
            const cardFormaal = card.dataset.formaal || '';
            const cardType = card.dataset.type || '';
            
            const matchesSearch = !searchTerm || title.includes(searchTerm);
            const matchesFormaal = selectedFormaal.length === 0 || selectedFormaal.includes(cardFormaal);
            const matchesType = selectedType.length === 0 || selectedType.includes(cardType);
            
            const isVisible = matchesSearch && matchesFormaal && matchesType;
            
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
