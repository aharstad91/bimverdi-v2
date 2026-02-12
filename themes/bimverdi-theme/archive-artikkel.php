<?php
/**
 * Archive Artikkel Template
 * 
 * Displays the article archive with filter options
 * 
 * @package BIMVerdi
 */

get_header();

// Get filter parameters
$current_kategori = isset($_GET['kategori']) ? sanitize_text_field($_GET['kategori']) : '';
$current_temagruppe = isset($_GET['temagruppe']) ? sanitize_text_field($_GET['temagruppe']) : '';

// Category labels
$category_labels = array(
    'fagartikkel' => array('label' => 'Fagartikkel', 'icon' => 'book'),
    'case' => array('label' => 'Case', 'icon' => 'briefcase'),
    'tips' => array('label' => 'Tips og triks', 'icon' => 'lightbulb'),
    'nyhet' => array('label' => 'Nyhet', 'icon' => 'newspaper'),
    'kommentar' => array('label' => 'Kommentar', 'icon' => 'comment'),
);

// Build query
$query_args = array(
    'post_type' => 'artikkel',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
    'orderby' => 'date',
    'order' => 'DESC',
);

// Add category filter
if ($current_kategori) {
    $query_args['meta_query'][] = array(
        'key' => 'artikkel_kategori',
        'value' => $current_kategori,
    );
}

// Add temagruppe filter
if ($current_temagruppe) {
    $query_args['tax_query'][] = array(
        'taxonomy' => 'temagruppe',
        'field' => 'slug',
        'terms' => $current_temagruppe,
    );
}

$articles = new WP_Query($query_args);

// Get temagrupper for filter
$temagrupper = get_terms(array(
    'taxonomy' => 'temagruppe',
    'hide_empty' => false,
));
?>

<div class="bg-gradient-to-b from-gray-50 to-white">
    
    <!-- Hero -->
    <div class="container mx-auto px-4 py-12">
        <div class="max-w-3xl">
            <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">Artikler</h1>
            <p class="text-lg text-gray-600">
                Fagartikler, case-studier og kunnskapsdeling fra BIM Verdi-nettverket.
            </p>
        </div>
    </div>
    
</div>

<div class="container mx-auto px-4 py-8">
    
    <!-- Filters -->
    <div class="mb-8 flex flex-wrap gap-4">
        
        <!-- Category filter -->
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600">Type:</span>
            <a href="<?php echo esc_url(remove_query_arg('kategori')); ?>" 
               class="px-3 py-1 rounded-full text-sm <?php echo !$current_kategori ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                Alle
            </a>
            <?php foreach ($category_labels as $slug => $info) : ?>
                <a href="<?php echo esc_url(add_query_arg('kategori', $slug)); ?>" 
                   class="px-3 py-1 rounded-full text-sm <?php echo $current_kategori === $slug ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    <?php echo esc_html($info['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Temagruppe filter -->
        <?php if (!empty($temagrupper) && !is_wp_error($temagrupper)) : ?>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600">Temagruppe:</span>
                <select onchange="window.location.href = this.value" class="text-sm border border-gray-300 rounded px-2 py-1">
                    <option value="<?php echo esc_url(remove_query_arg('temagruppe')); ?>">Alle temagrupper</option>
                    <?php foreach ($temagrupper as $temagruppe) : ?>
                        <option value="<?php echo esc_url(add_query_arg('temagruppe', $temagruppe->slug)); ?>" 
                                <?php selected($current_temagruppe, $temagruppe->slug); ?>>
                            <?php echo esc_html($temagruppe->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        
    </div>
    
    <?php if ($articles->have_posts()) : ?>
        
        <!-- Articles grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6" style="grid-auto-rows: 1fr;">
            <?php while ($articles->have_posts()) : $articles->the_post();
                $kategori = get_field('artikkel_kategori');
                $ingress = get_field('artikkel_ingress');
                $bedrift_id = get_field('artikkel_bedrift');
                $bedrift_name = $bedrift_id ? get_the_title($bedrift_id) : '';
                $author_name = get_the_author();
                $category_info = isset($category_labels[$kategori]) ? $category_labels[$kategori] : null;
            ?>
            
            <wa-card class="flex flex-col h-full">
                <?php
                // Featured image or fallback
                $has_thumbnail = has_post_thumbnail();
                $thumbnail_url = $has_thumbnail ? get_the_post_thumbnail_url(get_the_ID(), 'medium_large') : '';
                ?>
                <div class="aspect-[16/9] bg-[#EFE9DE] overflow-hidden">
                    <?php if ($has_thumbnail) : ?>
                        <img src="<?php echo esc_url($thumbnail_url); ?>"
                             alt="<?php the_title_attribute(); ?>"
                             class="w-full h-full object-cover">
                    <?php else : ?>
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-[#D6D1C6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="p-5 flex flex-col flex-1">

                    <!-- Category & Date -->
                    <div class="flex items-center justify-between mb-3">
                        <?php if ($category_info) : ?>
                            <wa-tag variant="neutral" size="small">
                                <wa-icon library="fa" name="fas-<?php echo esc_attr($category_info['icon']); ?>" slot="prefix"></wa-icon>
                                <?php echo esc_html($category_info['label']); ?>
                            </wa-tag>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>
                        <span class="text-xs text-gray-500"><?php echo get_the_date('j. M Y'); ?></span>
                    </div>
                    
                    <!-- Title -->
                    <h2 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                        <a href="<?php the_permalink(); ?>" class="hover:text-orange-600">
                            <?php the_title(); ?>
                        </a>
                        <?php echo bimverdi_admin_id_badge(); ?>
                    </h2>

                    <!-- Ingress / Excerpt -->
                    <p class="text-gray-600 text-sm mb-4 flex-1 line-clamp-3">
                        <?php
                        if ($ingress) {
                            echo wp_trim_words(esc_html($ingress), 25);
                        } elseif (has_excerpt()) {
                            echo wp_trim_words(get_the_excerpt(), 25);
                        } else {
                            echo wp_trim_words(get_the_content(), 25);
                        }
                        ?>
                    </p>
                    
                    <!-- Author & Company -->
                    <div class="mt-auto pt-4 border-t border-gray-100">
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <wa-avatar 
                                image="<?php echo esc_url(get_avatar_url(get_the_author_meta('ID'), array('size' => 32))); ?>"
                                style="--size: 24px;">
                            </wa-avatar>
                            <span><?php echo esc_html($author_name); ?></span>
                            <?php if ($bedrift_name) : ?>
                                <span class="text-gray-300">|</span>
                                <a href="<?php echo get_permalink($bedrift_id); ?>" class="text-orange-600 hover:underline">
                                    <?php echo esc_html($bedrift_name); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </wa-card>
            
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($articles->max_num_pages > 1) : ?>
            <div class="mt-8 flex justify-center">
                <?php
                echo paginate_links(array(
                    'total' => $articles->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                    'prev_text' => '← Forrige',
                    'next_text' => 'Neste →',
                    'type' => 'list',
                ));
                ?>
            </div>
        <?php endif; ?>
        
        <?php wp_reset_postdata(); ?>
        
    <?php else : ?>
        
        <!-- No articles -->
        <wa-card>
            <div class="p-8 text-center">
                <wa-icon library="fa" name="far-newspaper" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></wa-icon>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Ingen artikler funnet</h3>
                <p class="text-gray-600">
                    <?php if ($current_kategori || $current_temagruppe) : ?>
                        Prøv å fjerne filteret for å se flere artikler.
                    <?php else : ?>
                        Det er ingen publiserte artikler ennå.
                    <?php endif; ?>
                </p>
            </div>
        </wa-card>
        
    <?php endif; ?>
    
</div>

<!-- Pagination styles -->
<style>
    .page-numbers {
        display: flex;
        gap: 0.5rem;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .page-numbers li a,
    .page-numbers li span {
        display: block;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        text-decoration: none;
        font-size: 0.875rem;
    }
    
    .page-numbers li a {
        background: white;
        border: 1px solid #e5e7eb;
        color: #4b5563;
    }
    
    .page-numbers li a:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }
    
    .page-numbers li span.current {
        background: #F97316;
        color: white;
    }
</style>

<?php get_footer(); ?>
