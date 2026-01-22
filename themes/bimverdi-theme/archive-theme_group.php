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

<main class="bg-[#FAFAF8] min-h-screen">

    <!-- Page Header -->
    <section class="bg-[#F7F5EF] border-b border-[#D6D1C6]">
        <div class="max-w-[1280px] mx-auto px-4 py-8 lg:py-12">
            <h1 class="text-3xl lg:text-4xl font-bold text-[#1A1A1A] mb-4">
                Temagrupper
            </h1>
            <p class="text-lg text-[#5A5A5A] max-w-3xl">
                BIM Verdi har seks temagrupper som arbeider med ulike fokusomrader innen BIM og digitalisering.
                Meld deg inn i en eller flere grupper for a delta i faglige diskusjoner og samarbeid.
            </p>
        </div>
    </section>

    <!-- Grid Content -->
    <div class="max-w-[1280px] mx-auto px-4 py-8 lg:py-12">

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
            <p class="text-lg text-[#5A5A5A]">
                Ingen temagrupper funnet.
            </p>
        </div>
        <?php endif; ?>

    </div>

</main>

<?php
get_footer();
