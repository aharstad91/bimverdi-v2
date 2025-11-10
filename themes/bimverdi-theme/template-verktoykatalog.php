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

<div class="min-h-screen bg-bim-beige-100 py-12">
    <div class="container mx-auto px-4">
        
        <!-- Header -->
        <div class="mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-bim-black-900 mb-3">
                Verktøy & Løsninger
            </h1>
            <p class="text-xl text-bim-black-700">
                Utforsk digitale verktøy og løsninger fra BIM Verdi medlemmer
            </p>
        </div>

        <!-- Search & Filters -->
        <div class="card-hjem mb-8">
            <div class="card-body p-6">
                <form method="GET" class="space-y-4">
                    
                    <!-- Search Bar -->
                    <div class="relative">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-bim-black-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input 
                            type="text" 
                            name="s" 
                            value="<?php echo esc_attr($search); ?>"
                            placeholder="Søk etter verktøy..."
                            class="input-hjem-search w-full"
                        >
                    </div>

                    <!-- Filters Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        <!-- Category Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-bim-black-900 mb-2">Kategori</label>
                            <select name="kategori" class="input-hjem w-full">
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
                            <label class="block text-sm font-semibold text-bim-black-900 mb-2">Bedrift</label>
                            <select name="bedrift" class="input-hjem w-full">
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
                    <div class="flex gap-3 pt-4 border-t border-bim-black-200">
                        <button type="submit" class="btn btn-hjem-primary">
                            🔍 Søk
                        </button>
                        <a href="<?php echo get_permalink(); ?>" class="btn btn-hjem-outline">
                            Nullstill
                        </a>
                    </div>
                </form>
            </div>
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
        <div class="mb-6 flex flex-wrap gap-2 items-center">
            <span class="text-sm font-semibold text-bim-black-900">Aktive filter:</span>
            <?php foreach ($active_filters as $filter): ?>
                <span class="badge badge-hjem">
                    <?php echo $filter; ?>
                </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Results Count -->
        <div class="mb-6">
            <p class="text-lg text-bim-black-700">
                Viser <strong><?php echo $tools_query->found_posts; ?></strong> 
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
            
            <div class="card-hjem group hover:shadow-lg transition-shadow">
                
                <!-- Logo/Image -->
                <?php if ($logo_url): ?>
                <div class="h-40 bg-bim-beige-200 overflow-hidden flex items-center justify-center">
                    <img src="<?php echo esc_url($logo_url); ?>" 
                         alt="<?php the_title(); ?>" 
                         class="h-full w-full object-contain p-4">
                </div>
                <?php else: ?>
                <div class="h-40 bg-gradient-to-br from-bim-purple to-bim-orange flex items-center justify-center text-white text-3xl font-bold">
                    ⚙️
                </div>
                <?php endif; ?>

                <!-- Content -->
                <div class="card-body p-5">
                    
                    <!-- Title -->
                    <h3 class="card-title text-lg text-bim-black-900 mb-2">
                        <?php the_title(); ?>
                    </h3>

                    <!-- Company -->
                    <?php if ($eier): ?>
                    <div class="text-sm text-bim-black-700 mb-3">
                        <span class="font-semibold">Fra:</span> 
                        <a href="<?php echo get_permalink($eier_id); ?>" class="text-bim-orange hover:underline">
                            <?php echo esc_html($eier->post_title); ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Categories -->
                    <?php if (!empty($kategori_terms)): ?>
                    <div class="flex flex-wrap gap-1 mb-3">
                        <?php foreach (array_slice($kategori_terms, 0, 2) as $cat): ?>
                        <span class="text-xs bg-bim-orange bg-opacity-10 text-bim-orange px-2 py-1 rounded">
                            <?php echo esc_html($cat); ?>
                        </span>
                        <?php endforeach; ?>
                        <?php if (count($kategori_terms) > 2): ?>
                        <span class="text-xs text-bim-black-600">+<?php echo count($kategori_terms) - 2; ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <p class="text-sm text-bim-black-700 mb-4 line-clamp-2">
                        <?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?>
                    </p>

                    <!-- CTA -->
                    <div class="card-actions justify-between">
                        <?php if (!empty($lenke)): ?>
                            <a href="<?php echo esc_url($lenke); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-hjem-primary">
                                Besøk →
                            </a>
                        <?php endif; ?>
                        <a href="<?php the_permalink(); ?>" class="btn btn-sm btn-hjem-outline">
                            Detaljer
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
        ));
        
        if (!empty($pagination)): ?>
        <div class="flex justify-center gap-2 flex-wrap">
            <?php foreach ($pagination as $link): ?>
                <div><?php echo str_replace('page-numbers', 'btn btn-hjem-outline', $link); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        
        <!-- No Results -->
        <div class="card-hjem text-center py-16">
            <div class="text-6xl mb-4">🔍</div>
            <h3 class="text-2xl font-bold text-bim-black-900 mb-2">Ingen verktøy funnet</h3>
            <p class="text-bim-black-700 mb-6">Prøv å justere filterene dine eller søket</p>
            <a href="<?php echo get_permalink(); ?>" class="btn btn-hjem-primary">
                Vis alle verktøy
            </a>
        </div>

        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>
