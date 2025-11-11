<?php
/**
 * Template Name: Verktøykatalog
 * 
 * Public tools/software catalog - browse all registered tools
 * Filterable by category and company
 * 
 * @package BimVerdi_Theme
 */

get_header();

// Get filter parameters
$search = sanitize_text_field($_GET['s'] ?? '');
$kategori = isset($_GET['kategori']) ? intval($_GET['kategori']) : 0;
$bedrift = isset($_GET['bedrift']) ? intval($_GET['bedrift']) : 0;

// Get all taxonomy terms and companies
$all_kategorier = get_terms(array('taxonomy' => 'verktoykategori', 'hide_empty' => false));
$all_bedrifter = get_posts(array(
    'post_type' => 'foretak',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
));

// Build query
$args = array(
    'post_type' => 'verktoy',
    'posts_per_page' => 12,
    'paged' => get_query_var('paged') ?: 1,
    'orderby' => 'title',
    'order' => 'ASC',
    'post_status' => 'publish', // Only show published tools
    'tax_query' => array('relation' => 'AND'),
);

// Add search
if (!empty($search)) {
    $args['s'] = $search;
}

// Add category filter
if ($kategori) {
    $args['tax_query'][] = array(
        'taxonomy' => 'verktoykategori',
        'field' => 'term_id',
        'terms' => $kategori,
    );
}

// Add company filter
if ($bedrift) {
    $args['meta_query'] = array(
        array(
            'key' => 'verktoy_eier',
            'value' => $bedrift,
        )
    );
}

$tools_query = new WP_Query($args);
?>

<div class="min-h-screen bg-bim-beige-100">
    
    <!-- Hero Header -->
    <div class="bg-gradient-to-r from-purple-600 to-orange-600 text-white py-16">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between flex-wrap gap-6">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="bg-white/20 px-4 py-2 rounded-full text-sm font-bold">🔧 VERKTØY</span>
                        <span class="bg-white/20 px-4 py-2 rounded-full text-sm font-bold"><?php echo $tools_query->found_posts; ?> Løsninger</span>
                    </div>
                    <h1 class="text-5xl font-bold mb-4">Verktøy & Løsninger</h1>
                    <p class="text-xl opacity-95">Utforsk digitale verktøy og løsninger fra BIM Verdi medlemmer</p>
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
            <form method="GET" class="space-y-6">
                
                <!-- Search Bar -->
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input 
                        type="text" 
                        name="s" 
                        value="<?php echo esc_attr($search); ?>"
                        placeholder="Søk etter verktøy..."
                        class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    >
                </div>

                <!-- Filters Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Category Filter -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">📂 Kategori</label>
                        <select name="kategori" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">Alle kategorier</option>
                            <?php foreach ($all_kategorier as $term): ?>
                                <option value="<?php echo $term->term_id; ?>" <?php selected($kategori, $term->term_id); ?>>
                                    <?php echo esc_html($term->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Company Filter -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">🏢 Bedrift</label>
                        <select name="bedrift" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">Alle bedrifter</option>
                            <?php foreach ($all_bedrifter as $post): ?>
                                <option value="<?php echo $post->ID; ?>" <?php selected($bedrift, $post->ID); ?>>
                                    <?php echo esc_html($post->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button type="submit" class="px-6 py-3 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                        🔍 Søk
                    </button>
                    <a href="<?php echo get_permalink(); ?>" class="px-6 py-3 bg-gray-100 text-gray-900 rounded-lg font-semibold hover:bg-gray-200 transition-colors">
                        Nullstill
                    </a>
                </div>
            </form>
        </div>

        <!-- Active Filters -->
        <?php 
        $active_filters = array();
        if (!empty($search)) $active_filters[] = "Søk: <strong>" . esc_html($search) . "</strong>";
        if ($kategori) {
            $term = get_term($kategori, 'verktoykategori');
            $active_filters[] = "Kategori: <strong>" . esc_html($term->name) . "</strong>";
        }
        if ($bedrift) {
            $bedrift_post = get_post($bedrift);
            $active_filters[] = "Bedrift: <strong>" . esc_html($bedrift_post->post_title) . "</strong>";
        }
        
        if (!empty($active_filters)): ?>
        <div class="mb-6 flex flex-wrap gap-3 items-center">
            <span class="text-sm font-semibold text-gray-900">🏷️ Aktive filter:</span>
            <?php foreach ($active_filters as $filter): ?>
                <span class="bg-purple-100 text-purple-800 px-4 py-2 rounded-full text-sm font-semibold">
                    <?php echo $filter; ?>
                </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Results Count -->
        <div class="mb-8">
            <p class="text-2xl font-bold text-gray-900">
                📊 Viser <span class="text-purple-600"><?php echo $tools_query->found_posts; ?></span> 
                <?php echo $tools_query->found_posts === 1 ? 'verktøy' : 'verktøy'; ?>
            </p>
        </div>

        <!-- Tools Grid -->
        <?php if ($tools_query->have_posts()): ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php while ($tools_query->have_posts()): $tools_query->the_post();
                $eier_id = get_post_meta(get_the_ID(), 'verktoy_eier', true);
                $eier = $eier_id ? get_post($eier_id) : null;
                $lenke = get_post_meta(get_the_ID(), 'verktoy_lenke', true);
                $logo_id = get_post_meta(get_the_ID(), 'verktoy_logo', true);
                $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
                $kategori_terms = wp_get_post_terms(get_the_ID(), 'verktoykategori', array('fields' => 'names'));
            ?>
            
            <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all overflow-hidden group">
                
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
                    <?php if (!empty($kategori_terms)): ?>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <?php foreach (array_slice($kategori_terms, 0, 2) as $cat): ?>
                        <span class="text-xs bg-purple-100 text-purple-800 px-3 py-1 rounded-full font-semibold">
                            <?php echo esc_html($cat); ?>
                        </span>
                        <?php endforeach; ?>
                        <?php if (count($kategori_terms) > 2): ?>
                        <span class="text-xs text-gray-500 font-semibold">+<?php echo count($kategori_terms) - 2; ?></span>
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
                        <a href="<?php echo get_permalink($eier_id); ?>" class="text-orange-600 hover:underline font-semibold">
                            <?php echo esc_html($eier->post_title); ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <p class="text-sm text-gray-600 mb-6 line-clamp-3">
                        <?php echo esc_html(wp_trim_words(get_the_excerpt(), 25)); ?>
                    </p>

                    <!-- CTA -->
                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <?php if (!empty($lenke)): ?>
                            <a href="<?php echo esc_url($lenke); ?>" target="_blank" rel="noopener" class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition-colors text-center text-sm">
                                🔗 Besøk
                            </a>
                        <?php endif; ?>
                        <a href="<?php the_permalink(); ?>" class="flex-1 px-4 py-2 bg-gray-100 text-gray-900 rounded-lg font-semibold hover:bg-gray-200 transition-colors text-center text-sm">
                            📄 Detaljer
                        </a>
                    </div>
                </div>
            </div>

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

<?php get_footer(); ?>
