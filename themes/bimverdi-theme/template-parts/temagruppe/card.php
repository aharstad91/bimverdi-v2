<?php
/**
 * Temagruppe Card
 *
 * Card component for archive/listing pages showing theme group overview.
 * Displays icon, name, short description (truncated), status badge, and member count.
 *
 * The card itself is NOT clickable per UI contract - includes explicit "Les mer" link.
 *
 * @package BimVerdi_Theme
 *
 * @param array $args {
 *     @type int $post_id Post ID of the temagruppe
 * }
 */

if (!defined('ABSPATH')) exit;

$post_id = $args['post_id'] ?? get_the_ID();

// Get ACF fields
$kort_beskrivelse = get_field('kort_beskrivelse', $post_id);
$status = get_field('status', $post_id) ?: 'aktiv';
$ikon_id = get_field('ikon', $post_id);

// Fallback to excerpt if no short description
if (!$kort_beskrivelse && has_excerpt($post_id)) {
    $kort_beskrivelse = get_the_excerpt($post_id);
}

// Truncate description
$max_chars = 120;
if (strlen($kort_beskrivelse) > $max_chars) {
    $kort_beskrivelse = substr($kort_beskrivelse, 0, $max_chars) . '...';
}

// Get icon URL
$ikon_url = $ikon_id ? wp_get_attachment_image_url($ikon_id, 'thumbnail') : null;

// Get member count via taxonomy
$temagruppe_term = get_term_by('name', get_the_title($post_id), 'temagruppe');
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

// Status configuration
$status_config = [
    'aktiv' => [
        'label' => 'Aktiv',
        'color' => '#22C55E',
        'bg' => '#DCFCE7',
    ],
    'planlegging' => [
        'label' => 'Planlegging',
        'color' => '#EAB308',
        'bg' => '#FEF9C3',
    ],
    'pause' => [
        'label' => 'Pause',
        'color' => '#6B7280',
        'bg' => '#F3F4F6',
    ],
];
$current_status = $status_config[$status] ?? $status_config['aktiv'];

$permalink = get_permalink($post_id);
$title = get_the_title($post_id);

// Generate fallback icon initials
$initials = strtoupper(substr($title, 0, 2));
?>

<article class="bg-white border border-[#E5E0D5] rounded-lg p-6 flex flex-col">

    <!-- Header: Icon + Status -->
    <div class="flex items-start justify-between mb-4">
        <!-- Icon -->
        <div class="flex-shrink-0">
            <?php if ($ikon_url) : ?>
            <img
                src="<?php echo esc_url($ikon_url); ?>"
                alt=""
                class="w-12 h-12 object-contain"
                aria-hidden="true"
                loading="lazy"
            >
            <?php else : ?>
            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-[#FF8B5E] to-[#5E36FE] flex items-center justify-center text-white font-bold text-sm">
                <?php echo esc_html($initials); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Status Badge -->
        <span
            class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium"
            style="background-color: <?php echo esc_attr($current_status['bg']); ?>; color: <?php echo esc_attr($current_status['color']); ?>;"
        >
            <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?php echo esc_attr($current_status['color']); ?>;"></span>
            <?php echo esc_html($current_status['label']); ?>
        </span>
    </div>

    <!-- Title -->
    <h2 class="text-lg font-bold text-[#1A1A1A] mb-2">
        <?php echo esc_html($title); ?>
        <?php echo bimverdi_admin_id_badge($post_id); ?>
    </h2>

    <!-- Description -->
    <?php if ($kort_beskrivelse) : ?>
    <p class="text-sm text-[#5A5A5A] mb-4 flex-grow">
        <?php echo esc_html($kort_beskrivelse); ?>
    </p>
    <?php else : ?>
    <div class="flex-grow"></div>
    <?php endif; ?>

    <!-- Footer: Member count + Link -->
    <div class="flex items-center justify-between pt-4 border-t border-[#E5E0D5] mt-auto">
        <span class="text-sm text-[#5A5A5A]">
            <?php echo esc_html($member_count); ?> deltakere
        </span>

        <a
            href="<?php echo esc_url($permalink); ?>"
            class="text-sm font-medium text-[#FF8B5E] hover:underline inline-flex items-center gap-1"
        >
            Les mer
            <?php echo bimverdi_icon('chevron-right', 14); ?>
        </a>
    </div>

</article>
