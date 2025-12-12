<?php
/**
 * Single Artikkel Template
 * 
 * Displays a single article with author byline and company info
 * 
 * @package BIMVerdi
 */

get_header();

// Get article data
$artikkel_bedrift = get_field('artikkel_bedrift');
$artikkel_kategori = get_field('artikkel_kategori');
$artikkel_ingress = get_field('artikkel_ingress');
$author_id = get_the_author_meta('ID');
$author_name = get_the_author();
$author_avatar = get_avatar_url($author_id, array('size' => 80));

// Category labels
$category_labels = array(
    'fagartikkel' => array('label' => 'Fagartikkel', 'icon' => 'book', 'color' => 'blue'),
    'case' => array('label' => 'Case', 'icon' => 'briefcase', 'color' => 'green'),
    'tips' => array('label' => 'Tips og triks', 'icon' => 'lightbulb', 'color' => 'yellow'),
    'nyhet' => array('label' => 'Nyhet', 'icon' => 'newspaper', 'color' => 'purple'),
    'kommentar' => array('label' => 'Kommentar', 'icon' => 'comment', 'color' => 'orange'),
);

$category_info = isset($category_labels[$artikkel_kategori]) ? $category_labels[$artikkel_kategori] : null;

// Get company info
$company_name = '';
$company_url = '';
if ($artikkel_bedrift) {
    $company_name = get_the_title($artikkel_bedrift);
    $company_url = get_permalink($artikkel_bedrift);
}

// Get temagrupper
$temagrupper = get_the_terms(get_the_ID(), 'temagruppe');
?>

<article class="bg-white">
    
    <!-- Hero section -->
    <div class="bg-gradient-to-b from-gray-50 to-white py-12 lg:py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto">
                
                <!-- Category badge -->
                <?php if ($category_info) : ?>
                    <div class="mb-4">
                        <wa-tag variant="neutral">
                            <wa-icon library="fa" name="fas-<?php echo esc_attr($category_info['icon']); ?>" slot="prefix"></wa-icon>
                            <?php echo esc_html($category_info['label']); ?>
                        </wa-tag>
                    </div>
                <?php endif; ?>
                
                <!-- Title -->
                <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                    <?php the_title(); ?>
                </h1>
                
                <!-- Ingress -->
                <?php if ($artikkel_ingress) : ?>
                    <p class="text-xl text-gray-600 leading-relaxed mb-6">
                        <?php echo esc_html($artikkel_ingress); ?>
                    </p>
                <?php endif; ?>
                
                <!-- Author & meta -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 pt-4 border-t border-gray-200">
                    
                    <!-- Author -->
                    <div class="flex items-center gap-3">
                        <wa-avatar 
                            image="<?php echo esc_url($author_avatar); ?>" 
                            label="<?php echo esc_attr($author_name); ?>"
                            style="--size: 48px;">
                        </wa-avatar>
                        <div>
                            <div class="font-semibold text-gray-900"><?php echo esc_html($author_name); ?></div>
                            <?php if ($company_name) : ?>
                                <a href="<?php echo esc_url($company_url); ?>" class="text-sm text-orange-600 hover:underline">
                                    <?php echo esc_html($company_name); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Date -->
                    <div class="sm:ml-auto text-sm text-gray-500">
                        <wa-icon library="fa" name="far-calendar" class="mr-1"></wa-icon>
                        <?php echo get_the_date('j. F Y'); ?>
                    </div>
                    
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Content -->
    <div class="container mx-auto px-4 py-8 lg:py-12">
        <div class="max-w-3xl mx-auto">
            
            <!-- Article content -->
            <div class="prose prose-lg max-w-none">
                <?php the_content(); ?>
            </div>
            
            <!-- Temagrupper -->
            <?php if ($temagrupper && !is_wp_error($temagrupper)) : ?>
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <span class="text-sm text-gray-500 mr-2">Temagrupper:</span>
                    <?php foreach ($temagrupper as $temagruppe) : ?>
                        <wa-tag variant="neutral" class="mr-2">
                            <?php echo esc_html($temagruppe->name); ?>
                        </wa-tag>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Share & actions -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="flex flex-wrap items-center gap-4">
                    <span class="text-sm text-gray-500">Del artikkelen:</span>
                    <wa-button variant="neutral" size="small" outline onclick="window.open('https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(window.location.href), '_blank')">
                        <wa-icon library="fa" name="fab-linkedin" slot="prefix"></wa-icon>
                        LinkedIn
                    </wa-button>
                    <wa-button variant="neutral" size="small" outline onclick="navigator.clipboard.writeText(window.location.href); alert('Lenke kopiert!')">
                        <wa-icon library="fa" name="fas-link" slot="prefix"></wa-icon>
                        Kopier lenke
                    </wa-button>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- Author box -->
    <div class="bg-gray-50 py-8 lg:py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto">
                <wa-card>
                    <div class="p-6">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <wa-avatar 
                                image="<?php echo esc_url($author_avatar); ?>" 
                                label="<?php echo esc_attr($author_name); ?>"
                                style="--size: 80px;">
                            </wa-avatar>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 mb-1">Om forfatteren</h3>
                                <p class="text-gray-900 font-medium"><?php echo esc_html($author_name); ?></p>
                                <?php if ($company_name) : ?>
                                    <p class="text-gray-600 text-sm mb-3">
                                        <?php echo esc_html($company_name); ?>
                                    </p>
                                    <wa-button variant="neutral" size="small" outline href="<?php echo esc_url($company_url); ?>">
                                        <wa-icon library="fa" name="fas-building" slot="prefix"></wa-icon>
                                        Se foretakets profil
                                    </wa-button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </wa-card>
            </div>
        </div>
    </div>
    
    <!-- More articles from same company -->
    <?php 
    if ($artikkel_bedrift) :
        $related_articles = new WP_Query(array(
            'post_type' => 'artikkel',
            'post_status' => 'publish',
            'posts_per_page' => 3,
            'post__not_in' => array(get_the_ID()),
            'meta_query' => array(
                array(
                    'key' => 'artikkel_bedrift',
                    'value' => $artikkel_bedrift,
                ),
            ),
        ));
        
        if ($related_articles->have_posts()) :
    ?>
    <div class="container mx-auto px-4 py-8 lg:py-12">
        <div class="max-w-5xl mx-auto">
            <h2 class="text-xl font-bold text-gray-900 mb-6">
                Flere artikler fra <?php echo esc_html($company_name); ?>
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <?php while ($related_articles->have_posts()) : $related_articles->the_post(); ?>
                    <wa-card>
                        <div class="p-5">
                            <h3 class="font-semibold text-gray-900 mb-2">
                                <a href="<?php the_permalink(); ?>" class="hover:text-orange-600">
                                    <?php the_title(); ?>
                                </a>
                            </h3>
                            <?php if (has_excerpt()) : ?>
                                <p class="text-sm text-gray-600 mb-3"><?php echo wp_trim_words(get_the_excerpt(), 20); ?></p>
                            <?php endif; ?>
                            <span class="text-xs text-gray-500"><?php echo get_the_date('j. F Y'); ?></span>
                        </div>
                    </wa-card>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </div>
    <?php 
        endif;
    endif; 
    ?>
    
</article>

<!-- Prose styles for article content -->
<style>
    .prose h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-top: 2rem;
        margin-bottom: 1rem;
        color: #1f2937;
    }
    
    .prose h3 {
        font-size: 1.25rem;
        font-weight: 600;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        color: #1f2937;
    }
    
    .prose p {
        margin-bottom: 1.25rem;
        line-height: 1.75;
    }
    
    .prose ul, .prose ol {
        margin-bottom: 1.25rem;
        padding-left: 1.5rem;
    }
    
    .prose li {
        margin-bottom: 0.5rem;
    }
    
    .prose a {
        color: #F97316;
        text-decoration: underline;
    }
    
    .prose a:hover {
        color: #ea580c;
    }
    
    .prose blockquote {
        border-left: 4px solid #F97316;
        padding-left: 1rem;
        margin: 1.5rem 0;
        font-style: italic;
        color: #4b5563;
    }
    
    .prose img {
        border-radius: 0.5rem;
        margin: 1.5rem 0;
    }
    
    .prose code {
        background: #f3f4f6;
        padding: 0.125rem 0.25rem;
        border-radius: 0.25rem;
        font-size: 0.875em;
    }
</style>

<?php get_footer(); ?>
