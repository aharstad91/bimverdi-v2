<?php
/**
 * Temagruppe Hero Section
 *
 * Displays breadcrumb, title, short description, and optional hero illustration.
 *
 * @package BimVerdi_Theme
 *
 * @param array $args {
 *     @type int    $post_id           Post ID
 *     @type string $kort_beskrivelse  Short description
 *     @type int    $hero_illustrasjon Image attachment ID
 * }
 */

if (!defined('ABSPATH')) exit;

$post_id = $args['post_id'] ?? get_the_ID();
$kort_beskrivelse = $args['kort_beskrivelse'] ?? '';
$hero_illustrasjon = $args['hero_illustrasjon'] ?? null;

$hero_image_url = $hero_illustrasjon ? wp_get_attachment_image_url($hero_illustrasjon, 'large') : null;
?>

<section class="bg-[#FAFAF8]">
    <div class="max-w-[1280px] mx-auto px-4 pt-8 lg:pt-12">

        <!-- Breadcrumb -->
        <nav class="mb-6" aria-label="Breadcrumb">
            <ol class="flex items-center gap-2 text-sm text-[#5A5A5A]">
                <li>
                    <a href="<?php echo esc_url(home_url('/temagruppe/')); ?>" class="hover:text-[#1A1A1A] hover:underline">
                        Temagrupper
                    </a>
                </li>
                <li aria-hidden="true">
                    <?php echo bimverdi_icon('chevron-right', 14); ?>
                </li>
                <li class="text-[#1A1A1A] font-medium" aria-current="page">
                    <?php the_title(); ?>
                </li>
            </ol>
        </nav>

        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-8">

            <!-- Text Content -->
            <div class="lg:max-w-2xl">
                <h1 class="text-3xl lg:text-4xl font-bold text-[#1A1A1A] mb-4">
                    <?php the_title(); ?>
                </h1>

                <?php if ($kort_beskrivelse) : ?>
                <p class="text-lg text-[#5A5A5A] leading-relaxed">
                    <?php echo esc_html($kort_beskrivelse); ?>
                </p>
                <?php elseif (has_excerpt()) : ?>
                <p class="text-lg text-[#5A5A5A] leading-relaxed">
                    <?php echo esc_html(get_the_excerpt()); ?>
                </p>
                <?php endif; ?>
            </div>

            <!-- Hero Illustration (decorative) -->
            <?php if ($hero_image_url) : ?>
            <div class="flex-shrink-0 lg:w-64 xl:w-80">
                <img
                    src="<?php echo esc_url($hero_image_url); ?>"
                    alt=""
                    class="w-full h-auto max-h-48 lg:max-h-64 object-contain"
                    aria-hidden="true"
                    loading="lazy"
                >
            </div>
            <?php endif; ?>

        </div>

    </div>
</section>
