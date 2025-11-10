<?php
/**
 * The main template file
 *
 * @package BIMVerdi
 * @version 2.0.0
 */

get_header(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- Main Content -->
        <main class="md:col-span-2">
            <?php if (have_posts()) : ?>
                <div class="space-y-6">
                    <?php while (have_posts()) : the_post(); ?>
                        
                        <!-- Article Card (using daisyUI + hjem.no style) -->
                        <article id="post-<?php the_ID(); ?>" <?php post_class('card-hjem'); ?>>
                            
                            <?php if (has_post_thumbnail()) : ?>
                                <figure>
                                    <?php the_post_thumbnail('large', array('class' => 'card-hjem-image')); ?>
                                </figure>
                            <?php endif; ?>
                            
                            <div class="card-hjem-body">
                                <h2 class="card-hjem-title">
                                    <a href="<?php the_permalink(); ?>" class="hover:text-bim-orange transition-colors">
                                        <?php the_title(); ?>
                                    </a>
                                </h2>
                                
                                <div class="text-sm text-bim-black-400 mb-3">
                                    <time datetime="<?php echo get_the_date('c'); ?>">
                                        <?php echo get_the_date(); ?>
                                    </time>
                                </div>
                                
                                <div class="card-hjem-text">
                                    <?php the_excerpt(); ?>
                                </div>
                                
                                <a href="<?php the_permalink(); ?>" class="btn-hjem-primary">
                                    Les mer
                                </a>
                            </div>
                        </article>
                        
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                <div class="mt-8">
                    <?php
                    the_posts_pagination(array(
                        'prev_text' => __('Forrige', 'bimverdi'),
                        'next_text' => __('Neste', 'bimverdi'),
                        'class' => 'pagination'
                    ));
                    ?>
                </div>
                
            <?php else : ?>
                
                <div class="card-hjem">
                    <div class="card-hjem-body">
                        <h2 class="card-hjem-title">Ingen innlegg funnet</h2>
                        <p class="card-hjem-text">Beklager, vi fant ingen innlegg.</p>
                    </div>
                </div>
                
            <?php endif; ?>
        </main>
        
        <!-- Sidebar -->
        <aside class="md:col-span-1">
            <?php get_sidebar(); ?>
        </aside>
        
    </div>
</div>

<?php get_footer(); ?>
