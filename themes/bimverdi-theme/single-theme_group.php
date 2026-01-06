<?php
/**
 * Single Temagruppe Template
 * 
 * Displays detailed information about a single theme group
 * (ByggesaksBIM, ProsjektBIM, EiendomsBIM, MiljøBIM, SirkBIM, BIMtech)
 * 
 * @package BimVerdi_Theme
 */

get_header();

if (have_posts()) : while (have_posts()) : the_post();
?>

<main class="max-w-7xl mx-auto px-4 py-8 lg:py-12">
    
    <!-- Page Header -->
    <header class="mb-12">
        <h1 class="text-4xl lg:text-5xl font-bold text-[#1A1A1A] mb-4">
            <?php the_title(); ?>
        </h1>
        
        <?php if (has_excerpt()) : ?>
            <div class="text-xl text-[#5A5A5A] max-w-3xl">
                <?php the_excerpt(); ?>
            </div>
        <?php endif; ?>
    </header>

    <!-- Main Content -->
    <article class="prose prose-lg max-w-none">
        <?php 
        if (has_post_thumbnail()) : ?>
            <div class="mb-8">
                <?php the_post_thumbnail('large', ['class' => 'w-full h-auto rounded-lg']); ?>
            </div>
        <?php endif; ?>
        
        <?php the_content(); ?>
    </article>

    <!-- Meta Information -->
    <footer class="mt-12 pt-8 border-t border-[#D6D1C6]">
        <div class="flex flex-wrap gap-4 text-sm text-[#5A5A5A]">
            <span>Publisert: <?php echo get_the_date('j. F Y'); ?></span>
            <?php if (get_the_modified_date() !== get_the_date()) : ?>
                <span>|</span>
                <span>Sist oppdatert: <?php echo get_the_modified_date('j. F Y'); ?></span>
            <?php endif; ?>
        </div>
    </footer>

</main>

<?php
endwhile; endif;

get_footer();
