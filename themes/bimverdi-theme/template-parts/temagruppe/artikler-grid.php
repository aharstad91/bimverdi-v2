<?php
/**
 * Temagruppe Artikler Grid
 *
 * Full-width grid of articles associated with this theme group.
 * Uses the temagruppe taxonomy (artikkel was added to the taxonomy).
 *
 * @package BimVerdi_Theme
 *
 * @param array $args {
 *     @type WP_Term|false $temagruppe_term The taxonomy term for this temagruppe
 * }
 */

if (!defined('ABSPATH')) exit;

$temagruppe_term = $args['temagruppe_term'] ?? null;
$max_visible = 6;

// Query articles
$artikler = [];
$total_count = 0;
if ($temagruppe_term) {
    $query = new WP_Query([
        'post_type' => 'artikkel',
        'posts_per_page' => $max_visible,
        'orderby' => 'date',
        'order' => 'DESC',
        'tax_query' => [
            [
                'taxonomy' => 'temagruppe',
                'field' => 'term_id',
                'terms' => $temagruppe_term->term_id,
            ],
        ],
    ]);

    if ($query->have_posts()) {
        $artikler = $query->posts;
        $total_count = $query->found_posts;
    }
    wp_reset_postdata();
}

// If no items, don't render section
if (empty($artikler)) {
    return;
}

// Norwegian months for date formatting
$months_no = [
    'januar', 'februar', 'mars', 'april', 'mai', 'juni',
    'juli', 'august', 'september', 'oktober', 'november', 'desember'
];
?>

<section>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-[#1A1A1A]">
            Artikler
            <?php if ($total_count > 0) : ?>
            <span class="text-base font-normal text-[#5A5A5A]">(<?php echo esc_html($total_count); ?>)</span>
            <?php endif; ?>
        </h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($artikler as $artikkel) :
            $artikkel_id = $artikkel->ID;
            $permalink = get_permalink($artikkel_id);
            $thumbnail_id = get_post_thumbnail_id($artikkel_id);
            $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium_large') : null;
            $ingress = get_field('artikkel_ingress', $artikkel_id);

            // Get category
            $categories = wp_get_post_terms($artikkel_id, 'artikkelkategori', ['fields' => 'names']);
            $category = !empty($categories) ? $categories[0] : '';

            // Format date
            $date_obj = new DateTime($artikkel->post_date);
            $day = $date_obj->format('j');
            $month_idx = (int)$date_obj->format('n') - 1;
            $year = $date_obj->format('Y');
            $formatted_date = $day . '. ' . $months_no[$month_idx] . ' ' . $year;

            // Get author or company
            $author_name = get_the_author_meta('display_name', $artikkel->post_author);
            $tilknyttet_foretak = get_field('tilknyttet_foretak', $artikkel_id);
            $company_name = $tilknyttet_foretak ? get_the_title($tilknyttet_foretak) : '';
        ?>
        <article class="bg-white rounded-lg border border-[#E5E0D8] overflow-hidden flex flex-col">
            <!-- Thumbnail -->
            <?php if ($thumbnail_url) : ?>
            <div class="aspect-[16/9] overflow-hidden bg-[#F7F5EF]">
                <img
                    src="<?php echo esc_url($thumbnail_url); ?>"
                    alt=""
                    class="w-full h-full object-cover"
                    loading="lazy"
                >
            </div>
            <?php else : ?>
            <div class="aspect-[16/9] bg-gradient-to-br from-[#F7F5EF] to-[#EFE9DE] flex items-center justify-center">
                <?php echo bimverdi_icon('file-text', 32, 'text-[#C4BFB3]'); ?>
            </div>
            <?php endif; ?>

            <!-- Content -->
            <div class="p-5 flex flex-col flex-1">
                <!-- Category Badge -->
                <?php if ($category) : ?>
                <div class="mb-2">
                    <span class="px-2 py-1 bg-[#F7F5EF] rounded text-xs font-medium text-[#5A5A5A]">
                        <?php echo esc_html($category); ?>
                    </span>
                </div>
                <?php endif; ?>

                <!-- Title -->
                <h3 class="text-base font-semibold text-[#1A1A1A] mb-2 line-clamp-2">
                    <?php echo esc_html($artikkel->post_title); ?>
                </h3>

                <!-- Ingress -->
                <?php if ($ingress) : ?>
                <p class="text-sm text-[#5A5A5A] mb-3 line-clamp-2 flex-1">
                    <?php echo esc_html($ingress); ?>
                </p>
                <?php else : ?>
                <div class="flex-1"></div>
                <?php endif; ?>

                <!-- Meta -->
                <div class="text-xs text-[#5A5A5A] mb-4">
                    <span><?php echo esc_html($formatted_date); ?></span>
                    <?php if ($company_name) : ?>
                    <span class="mx-1">·</span>
                    <span><?php echo esc_html($company_name); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Footer -->
                <div class="pt-4 border-t border-[#E5E0D8]">
                    <a
                        href="<?php echo esc_url($permalink); ?>"
                        class="text-sm font-medium text-[#5A5A5A] hover:text-[#FF8B5E] inline-flex items-center gap-1"
                    >
                        Les mer
                        <?php echo bimverdi_icon('chevron-right', 14); ?>
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- See all link -->
    <?php if ($total_count > $max_visible) : ?>
    <div class="mt-6 text-center">
        <a
            href="<?php echo esc_url(home_url('/artikler/')); ?>"
            class="text-sm text-[#FF8B5E] hover:underline inline-flex items-center gap-1"
        >
            Se alle <?php echo esc_html($total_count); ?> artikler
            <?php echo bimverdi_icon('arrow-right', 14); ?>
        </a>
    </div>
    <?php endif; ?>
</section>
