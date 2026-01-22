<?php
/**
 * Temagruppe Deltakerliste
 *
 * Grid display of member companies (foretak) associated with this theme group.
 * Shows max 12 companies with "See all" link if more exist.
 *
 * @package BimVerdi_Theme
 *
 * @param array $args {
 *     @type WP_Term|false $temagruppe_term The taxonomy term for this temagruppe
 *     @type int           $member_count    Total number of members
 * }
 */

if (!defined('ABSPATH')) exit;

$temagruppe_term = $args['temagruppe_term'] ?? null;
$total_member_count = $args['member_count'] ?? 0;
$max_visible = 12;

// Query member companies
$members = [];
if ($temagruppe_term) {
    $member_query = new WP_Query([
        'post_type' => 'foretak',
        'posts_per_page' => $max_visible,
        'orderby' => 'title',
        'order' => 'ASC',
        'tax_query' => [
            [
                'taxonomy' => 'temagruppe',
                'field' => 'term_id',
                'terms' => $temagruppe_term->term_id,
            ],
        ],
    ]);

    if ($member_query->have_posts()) {
        $members = $member_query->posts;
    }
    wp_reset_postdata();
}

// Archive URL for "see all"
$archive_url = '';
if ($temagruppe_term) {
    $archive_url = add_query_arg('temagruppe', $temagruppe_term->slug, home_url('/deltakere/'));
}
?>

<section>
    <h2 class="text-xl font-bold text-[#1A1A1A] mb-6">
        Deltakende bedrifter
        <?php if ($total_member_count > 0) : ?>
        <span class="text-base font-normal text-[#5A5A5A]">(<?php echo esc_html($total_member_count); ?>)</span>
        <?php endif; ?>
    </h2>

    <?php if (!empty($members)) : ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php foreach ($members as $member) :
            $logo_id = get_field('logo', $member->ID);
            $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : null;
            $company_name = get_the_title($member->ID);
            $permalink = get_permalink($member->ID);

            // Generate initials for fallback
            $initials = strtoupper(substr($company_name, 0, 2));
        ?>
        <a
            href="<?php echo esc_url($permalink); ?>"
            class="group block p-4 bg-white border border-[#E5E0D5] rounded-lg hover:border-[#FF8B5E] transition-colors"
        >
            <div class="flex flex-col items-center text-center">
                <!-- Logo or Initials -->
                <div class="w-16 h-16 mb-3 flex items-center justify-center">
                    <?php if ($logo_url) : ?>
                    <img
                        src="<?php echo esc_url($logo_url); ?>"
                        alt="<?php echo esc_attr($company_name); ?>"
                        class="max-w-full max-h-full object-contain"
                        loading="lazy"
                    >
                    <?php else : ?>
                    <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-[#FF8B5E] to-[#5E36FE] flex items-center justify-center text-white font-bold text-lg">
                        <?php echo esc_html($initials); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Company Name -->
                <span class="text-sm font-medium text-[#1A1A1A] group-hover:text-[#FF8B5E] line-clamp-2">
                    <?php echo esc_html($company_name); ?>
                </span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- See all link -->
    <?php if ($total_member_count > $max_visible && $archive_url) : ?>
    <div class="mt-6 text-center">
        <a
            href="<?php echo esc_url($archive_url); ?>"
            class="text-sm text-[#FF8B5E] hover:underline inline-flex items-center gap-1"
        >
            Se alle <?php echo esc_html($total_member_count); ?> deltakere
            <?php echo bimverdi_icon('arrow-right', 14); ?>
        </a>
    </div>
    <?php endif; ?>

    <?php else : ?>
    <div class="text-center py-8">
        <p class="text-[#5A5A5A]">
            Ingen bedrifter er registrert i denne temagruppen enna.
        </p>
        <p class="text-sm text-[#5A5A5A] mt-2">
            <a href="<?php echo esc_url(home_url('/min-side/')); ?>" class="text-[#FF8B5E] hover:underline">
                Bli den forste - meld deg pa via Min Side
            </a>
        </p>
    </div>
    <?php endif; ?>
</section>
