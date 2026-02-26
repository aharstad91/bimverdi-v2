<?php
/**
 * Shared Archive Intro Section
 *
 * Consistent header for all public archive pages.
 * Content editable via ACF Options Page (Innstillinger → Arkivsider).
 *
 * Usage:
 *   get_template_part('parts/components/archive-intro', null, [
 *       'acf_prefix'       => 'verktoy',
 *       'fallback_title'   => 'Verktøykatalog',
 *       'fallback_ingress' => 'Digitale verktøy og løsninger.',
 *       'count'            => 36,          // optional
 *       'count_label'      => 'verktøy',   // optional
 *   ]);
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$prefix           = $args['acf_prefix'] ?? '';
$fallback_title   = $args['fallback_title'] ?? '';
$fallback_ingress = $args['fallback_ingress'] ?? '';
$count            = $args['count'] ?? null;
$count_label      = $args['count_label'] ?? '';

// Get from ACF Options Page, fall back to hardcoded defaults
$title   = '';
$ingress = '';

if ($prefix && function_exists('get_field')) {
    $title   = get_field("{$prefix}_tittel", 'option') ?: '';
    $ingress = get_field("{$prefix}_ingress", 'option') ?: '';
}

if (!$title) {
    $title = $fallback_title;
}
if (!$ingress) {
    $ingress = $fallback_ingress;
}
?>

<section class="bg-white border-b border-[#E7E5E4]">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div>
                <h1 class="text-3xl lg:text-4xl font-bold text-[#111827] mb-3">
                    <?php echo esc_html($title); ?>
                </h1>
                <?php if ($ingress): ?>
                    <p class="text-lg text-[#57534E] leading-relaxed">
                        <?php echo esc_html($ingress); ?>
                    </p>
                <?php endif; ?>
                <?php if ($count !== null && $count_label): ?>
                    <p class="text-sm text-[#78716C] mt-3">
                        <?php echo esc_html($count); ?> <?php echo esc_html($count_label); ?>
                    </p>
                <?php endif; ?>
            </div>
            <div>
                <!-- Reservert for tag cloud / visuelt element -->
            </div>
        </div>
    </div>
</section>
