<?php
/**
 * Single Temagruppe Template (Dashboard Layout)
 *
 * Displays a theme group as a dashboard/collection page with all related content.
 * Full-width sections layout without sidebar.
 *
 * Sections:
 * 1. Hero (breadcrumb + title + description)
 * 2. Info Bar (status + fagansvarlig + CTA)
 * 3. Deltakende foretak
 * 4. Kommende arrangementer
 * 5. Kunnskapskilder
 * 6. Relaterte verktoy (via formaalstema ACF field)
 * 7. Artikler
 *
 * @package BimVerdi_Theme
 */

get_header();

if (have_posts()) : while (have_posts()) : the_post();

$post_id = get_the_ID();

// Get ACF fields
$kort_beskrivelse = get_field('kort_beskrivelse', $post_id);
$status = get_field('status', $post_id) ?: 'aktiv';
$motefrekvens = get_field('motefrekvens', $post_id);
$hero_illustrasjon = get_field('hero_illustrasjon', $post_id);

// Get the temagruppe taxonomy term that matches this CPT title
$temagruppe_term = get_term_by('name', get_the_title(), 'temagruppe');

// Count member companies with this temagruppe taxonomy
$member_count = 0;
if ($temagruppe_term) {
    $member_query = new WP_Query([
        'post_type' => 'foretak',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => [
            [
                'taxonomy' => 'temagruppe',
                'field' => 'term_id',
                'terms' => $temagruppe_term->term_id,
            ],
        ],
    ]);
    $member_count = $member_query->found_posts;
    wp_reset_postdata();
}

// Count upcoming events for this temagruppe
$event_count = 0;
if ($temagruppe_term) {
    $today = date('Y-m-d');
    $event_query = new WP_Query([
        'post_type' => 'arrangement',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => 'dato',
                'value' => $today,
                'compare' => '>=',
                'type' => 'DATE',
            ],
        ],
        'tax_query' => [
            [
                'taxonomy' => 'temagruppe',
                'field' => 'term_id',
                'terms' => $temagruppe_term->term_id,
            ],
        ],
    ]);
    $event_count = $event_query->found_posts;
    wp_reset_postdata();
}

// Make data available to template parts
$temagruppe_data = [
    'post_id' => $post_id,
    'kort_beskrivelse' => $kort_beskrivelse,
    'status' => $status,
    'motefrekvens' => $motefrekvens,
    'member_count' => $member_count,
    'event_count' => $event_count,
    'temagruppe_term' => $temagruppe_term,
    'hero_illustrasjon' => $hero_illustrasjon,
];
?>

<main class="bg-[#FAFAF8] min-h-screen">

    <!-- Hero Section -->
    <?php get_template_part('template-parts/temagruppe/hero', null, $temagruppe_data); ?>

    <!-- Dashboard Content -->
    <div class="max-w-[1280px] mx-auto px-4 py-8 lg:py-12">

        <!-- Info Bar: Status + Fagansvarlig + CTA -->
        <?php get_template_part('template-parts/temagruppe/info-bar', null, $temagruppe_data); ?>

        <!-- Section: Deltakende foretak -->
        <div class="border-t border-[#D6D1C6] pt-10 mb-10">
            <?php get_template_part('template-parts/temagruppe/deltakerliste', null, $temagruppe_data); ?>
        </div>

        <!-- Section: Kommende arrangementer -->
        <?php if ($event_count > 0 || $temagruppe_term) : ?>
        <div class="border-t border-[#D6D1C6] pt-10 mb-10">
            <?php get_template_part('template-parts/temagruppe/arrangementer-grid', null, $temagruppe_data); ?>
        </div>
        <?php endif; ?>

        <!-- Section: Kunnskapskilder -->
        <?php
        // Check if there are any kunnskapskilder before showing section
        $has_kunnskapskilder = false;
        if ($temagruppe_term) {
            $kilde_check = new WP_Query([
                'post_type' => 'kunnskapskilde',
                'posts_per_page' => 1,
                'fields' => 'ids',
                'tax_query' => [
                    [
                        'taxonomy' => 'temagruppe',
                        'field' => 'term_id',
                        'terms' => $temagruppe_term->term_id,
                    ],
                ],
            ]);
            $has_kunnskapskilder = $kilde_check->found_posts > 0;
            wp_reset_postdata();
        }
        if ($has_kunnskapskilder) :
        ?>
        <div class="border-t border-[#D6D1C6] pt-10 mb-10">
            <?php get_template_part('template-parts/temagruppe/kunnskapskilder-grid', null, $temagruppe_data); ?>
        </div>
        <?php endif; ?>

        <!-- Section: Relaterte verktoy -->
        <?php
        // Check if there are any verktoy with matching formaalstema
        $temagruppe_navn = get_the_title();
        $has_verktoy = false;
        $verktoy_check = new WP_Query([
            'post_type' => 'verktoy',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'formaalstema',
                    'value' => $temagruppe_navn,
                    'compare' => '=',
                ],
            ],
        ]);
        $has_verktoy = $verktoy_check->found_posts > 0;
        wp_reset_postdata();

        if ($has_verktoy) :
        ?>
        <div class="border-t border-[#D6D1C6] pt-10 mb-10">
            <?php get_template_part('template-parts/temagruppe/verktoy-grid', null, $temagruppe_data); ?>
        </div>
        <?php endif; ?>

        <!-- Section: Artikler -->
        <?php
        // Check if there are any artikler
        $has_artikler = false;
        if ($temagruppe_term) {
            $artikkel_check = new WP_Query([
                'post_type' => 'artikkel',
                'posts_per_page' => 1,
                'fields' => 'ids',
                'tax_query' => [
                    [
                        'taxonomy' => 'temagruppe',
                        'field' => 'term_id',
                        'terms' => $temagruppe_term->term_id,
                    ],
                ],
            ]);
            $has_artikler = $artikkel_check->found_posts > 0;
            wp_reset_postdata();
        }
        if ($has_artikler) :
        ?>
        <div class="border-t border-[#D6D1C6] pt-10 mb-10">
            <?php get_template_part('template-parts/temagruppe/artikler-grid', null, $temagruppe_data); ?>
        </div>
        <?php endif; ?>

    </div>

</main>

<?php
endwhile; endif;

get_footer();
