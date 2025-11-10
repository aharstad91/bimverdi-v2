<?php
/**
 * The template for displaying pages
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
                <?php while (have_posts()) : the_post(); ?>
                    
                    <article id="post-<?php the_ID(); ?>" <?php post_class('card-hjem'); ?>>
                        
                        <div class="card-hjem-body">
                            <h1 class="card-hjem-title">
                                <?php the_title(); ?>
                            </h1>
                            
                            <?php if (get_the_date()) : ?>
                                <div class="text-sm text-bim-black-400 mb-3">
                                    <time datetime="<?php echo get_the_date('c'); ?>">
                                        <?php echo get_the_date(); ?>
                                    </time>
                                </div>
                            <?php endif; ?>
                            
                            <!-- FULL PAGE CONTENT -->
                            <div class="card-hjem-text prose prose-sm max-w-none">
                                <?php the_content(); ?>
                            </div>
                        </div>
                    </article>
                    
                    <!-- Comments if enabled -->
                    <?php comments_template(); ?>
                    
                <?php endwhile; ?>
            <?php else : ?>
                
                <div class="card-hjem">
                    <div class="card-hjem-body">
                        <h1 class="card-hjem-title">Side ikke funnet</h1>
                        <p class="card-hjem-text">Beklager, siden ble ikke funnet.</p>
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
