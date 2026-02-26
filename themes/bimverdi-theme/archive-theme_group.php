<?php
/**
 * Archive Temagruppe Template
 *
 * Displays all theme groups in a grid layout.
 * URL: /temagruppe/
 *
 * Each card shows icon, name, short description, status badge, and member count.
 *
 * @package BimVerdi_Theme
 */

get_header();
?>

<main class="bg-white min-h-screen">

    <?php get_template_part('parts/components/archive-intro', null, [
        'acf_prefix'       => 'temagrupper',
        'fallback_title'   => 'Temagrupper',
        'fallback_ingress' => 'BIM Verdis temagrupper arbeider med ulike fokusområder innen BIM og digitalisering.',
    ]); ?>

    <!-- Grid Content -->
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">

        <?php if (have_posts()) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while (have_posts()) : the_post();
                get_template_part('template-parts/temagruppe/card', null, ['post_id' => get_the_ID()]);
            endwhile; ?>
        </div>

        <!-- Pagination if needed -->
        <?php
        $pagination = paginate_links([
            'type' => 'array',
            'prev_text' => '&larr; Forrige',
            'next_text' => 'Neste &rarr;',
        ]);

        if ($pagination) : ?>
        <nav class="mt-12 flex justify-center" aria-label="Pagination">
            <ul class="flex items-center gap-2">
                <?php foreach ($pagination as $link) : ?>
                <li class="<?php echo strpos($link, 'current') !== false ? 'font-bold' : ''; ?>">
                    <?php echo $link; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <?php else : ?>
        <div class="text-center py-16">
            <p class="text-lg text-[#57534E]">
                Ingen temagrupper funnet.
            </p>
        </div>
        <?php endif; ?>

    </div>

</main>

<?php
get_footer();
