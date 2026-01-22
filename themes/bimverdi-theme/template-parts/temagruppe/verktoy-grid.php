<?php
/**
 * Temagruppe Verktoy Grid
 *
 * Full-width grid of tools associated with this theme group.
 * Uses ACF field 'formaalstema' to match tools to the temagruppe name.
 *
 * @package BimVerdi_Theme
 *
 * @param array $args {
 *     @type int $post_id Post ID of the temagruppe
 * }
 */

if (!defined('ABSPATH')) exit;

$post_id = $args['post_id'] ?? get_the_ID();
$temagruppe_navn = get_the_title($post_id);
$max_visible = 8;

// Query tools where formaalstema matches the temagruppe name
$verktoy_query = new WP_Query([
    'post_type' => 'verktoy',
    'posts_per_page' => $max_visible,
    'orderby' => 'title',
    'order' => 'ASC',
    'meta_query' => [
        [
            'key' => 'formaalstema',
            'value' => $temagruppe_navn,
            'compare' => '=',
        ],
    ],
]);

$verktoy = $verktoy_query->posts;
$total_count = $verktoy_query->found_posts;
wp_reset_postdata();

// If no items, don't render section
if (empty($verktoy)) {
    return;
}
?>

<section>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-[#1A1A1A]">
            Relaterte verktoy
            <?php if ($total_count > 0) : ?>
            <span class="text-base font-normal text-[#5A5A5A]">(<?php echo esc_html($total_count); ?>)</span>
            <?php endif; ?>
        </h2>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php foreach ($verktoy as $tool) :
            $tool_id = $tool->ID;
            $logo_id = get_field('verktoy_logo', $tool_id);
            $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : null;
            $eier_id = get_field('eier_leverandor', $tool_id);
            $eier_navn = $eier_id ? get_the_title($eier_id) : '';
            $tool_name = get_the_title($tool_id);
            $permalink = get_permalink($tool_id);

            // Get category
            $categories = wp_get_post_terms($tool_id, 'verktoykategori', ['fields' => 'names']);
            $category = !empty($categories) ? $categories[0] : '';

            // Generate initials for fallback
            $initials = strtoupper(substr($tool_name, 0, 2));
        ?>
        <article class="bg-white rounded-lg border border-[#E5E0D8] p-4 flex flex-col">
            <!-- Logo or Initials -->
            <div class="w-12 h-12 mb-3 flex items-center justify-center">
                <?php if ($logo_url) : ?>
                <img
                    src="<?php echo esc_url($logo_url); ?>"
                    alt="<?php echo esc_attr($tool_name); ?>"
                    class="max-w-full max-h-full object-contain"
                    loading="lazy"
                >
                <?php else : ?>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-[#FF8B5E] to-[#E07A50] flex items-center justify-center text-white font-bold text-sm">
                    <?php echo esc_html($initials); ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Title -->
            <h3 class="text-sm font-semibold text-[#1A1A1A] mb-1 line-clamp-2">
                <?php echo esc_html($tool_name); ?>
            </h3>

            <!-- Owner company -->
            <?php if ($eier_navn) : ?>
            <p class="text-xs text-[#5A5A5A] mb-3 line-clamp-1">
                <?php echo esc_html($eier_navn); ?>
            </p>
            <?php else : ?>
            <div class="mb-3"></div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="mt-auto pt-3 border-t border-[#E5E0D8]">
                <a
                    href="<?php echo esc_url($permalink); ?>"
                    class="text-xs font-medium text-[#5A5A5A] hover:text-[#FF8B5E] inline-flex items-center gap-1"
                >
                    Se detaljer
                    <?php echo bimverdi_icon('chevron-right', 12); ?>
                </a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- See all link -->
    <?php if ($total_count > $max_visible) : ?>
    <div class="mt-6 text-center">
        <a
            href="<?php echo esc_url(home_url('/verktoy/')); ?>"
            class="text-sm text-[#FF8B5E] hover:underline inline-flex items-center gap-1"
        >
            Se alle <?php echo esc_html($total_count); ?> verktoy
            <?php echo bimverdi_icon('arrow-right', 14); ?>
        </a>
    </div>
    <?php endif; ?>
</section>
