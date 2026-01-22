<?php
/**
 * Temagruppe Fagansvarlig
 *
 * Displays the responsible person for the theme group with photo, name, title, and company.
 * Only rendered if fagansvarlig_navn is set.
 *
 * @package BimVerdi_Theme
 *
 * @param array $args {
 *     @type int $post_id Post ID of the temagruppe
 * }
 */

if (!defined('ABSPATH')) exit;

$post_id = $args['post_id'] ?? get_the_ID();

// Get fagansvarlig fields
$navn = get_field('fagansvarlig_navn', $post_id);
$tittel = get_field('fagansvarlig_tittel', $post_id);
$bedrift_id = get_field('fagansvarlig_bedrift', $post_id);
$bilde_id = get_field('fagansvarlig_bilde', $post_id);
$linkedin = get_field('fagansvarlig_linkedin', $post_id);

// Don't render if no name
if (!$navn) {
    return;
}

// Get company info
$bedrift_navn = '';
$bedrift_url = '';
if ($bedrift_id) {
    $bedrift_navn = get_the_title($bedrift_id);
    $bedrift_url = get_permalink($bedrift_id);
}

// Get image URL
$bilde_url = $bilde_id ? wp_get_attachment_image_url($bilde_id, 'thumbnail') : null;

// Generate initials for fallback
$initials = '';
$name_parts = explode(' ', $navn);
if (count($name_parts) >= 2) {
    $initials = strtoupper(substr($name_parts[0], 0, 1) . substr(end($name_parts), 0, 1));
} else {
    $initials = strtoupper(substr($navn, 0, 2));
}
?>

<section class="pb-6 border-b border-[#D6D1C6]">
    <h3 class="text-sm font-semibold text-[#5A5A5A] uppercase tracking-wide mb-4">
        Fagansvarlig
    </h3>

    <div class="flex items-start gap-4">
        <!-- Profile Image -->
        <div class="flex-shrink-0">
            <?php if ($bilde_url) : ?>
            <img
                src="<?php echo esc_url($bilde_url); ?>"
                alt="<?php echo esc_attr($navn); ?>"
                class="w-16 h-16 rounded-full object-cover"
                loading="lazy"
            >
            <?php else : ?>
            <div class="w-16 h-16 rounded-full bg-[#FF8B5E] flex items-center justify-center text-white font-semibold text-lg">
                <?php echo esc_html($initials); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="min-w-0">
            <!-- Name (with LinkedIn link if available) -->
            <?php if ($linkedin) : ?>
            <a
                href="<?php echo esc_url($linkedin); ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="text-base font-semibold text-[#1A1A1A] hover:text-[#FF8B5E] hover:underline inline-flex items-center gap-1.5"
            >
                <?php echo esc_html($navn); ?>
                <?php echo bimverdi_icon('linkedin', 14); ?>
            </a>
            <?php else : ?>
            <p class="text-base font-semibold text-[#1A1A1A]">
                <?php echo esc_html($navn); ?>
            </p>
            <?php endif; ?>

            <!-- Title -->
            <?php if ($tittel) : ?>
            <p class="text-sm text-[#5A5A5A] mt-0.5">
                <?php echo esc_html($tittel); ?>
            </p>
            <?php endif; ?>

            <!-- Company -->
            <?php if ($bedrift_navn) : ?>
            <p class="text-sm text-[#5A5A5A] mt-0.5">
                <?php if ($bedrift_url) : ?>
                <a href="<?php echo esc_url($bedrift_url); ?>" class="hover:text-[#1A1A1A] hover:underline">
                    <?php echo esc_html($bedrift_navn); ?>
                </a>
                <?php else : ?>
                <?php echo esc_html($bedrift_navn); ?>
                <?php endif; ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
</section>
