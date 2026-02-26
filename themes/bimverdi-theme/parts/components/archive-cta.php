<?php
/**
 * Shared Archive Bottom CTA
 *
 * Call-to-action section shown at the bottom of archive pages.
 * Encourages visitors to log in or contribute.
 *
 * Usage:
 *   get_template_part('parts/components/archive-cta', null, [
 *       'title'       => 'Vil du bidra?',
 *       'description' => 'Logg inn for å registrere egne verktøy.',
 *       'cta_text'    => 'Logg inn',
 *       'cta_url'     => '/logg-inn/',
 *       'icon'        => 'log-in',          // Lucide icon name
 *       'show_for'    => 'logged_out',       // 'logged_out', 'logged_in', 'all'
 *   ]);
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$title       = $args['title'] ?? 'Vil du bidra?';
$description = $args['description'] ?? '';
$cta_text    = $args['cta_text'] ?? 'Logg inn';
$cta_url     = $args['cta_url'] ?? '/logg-inn/';
$icon        = $args['icon'] ?? 'log-in';
$show_for    = $args['show_for'] ?? 'logged_out';

// Visibility check
if ($show_for === 'logged_out' && is_user_logged_in()) {
    return;
}
if ($show_for === 'logged_in' && !is_user_logged_in()) {
    return;
}

// Build redirect URL for login links
if (strpos($cta_url, '/logg-inn/') !== false && strpos($cta_url, 'redirect_to') === false) {
    $cta_url = home_url('/logg-inn/?redirect_to=' . urlencode($_SERVER['REQUEST_URI']));
} else {
    $cta_url = home_url($cta_url);
}
?>

<div class="bg-white rounded-lg border border-[#E7E5E4] p-8 text-center mt-8">
    <?php if ($icon && function_exists('bimverdi_icon')): ?>
        <div class="w-12 h-12 bg-[#F5F5F4] rounded-full flex items-center justify-center mx-auto mb-4">
            <?php echo bimverdi_icon($icon, 24, 'text-[#57534E]'); ?>
        </div>
    <?php endif; ?>
    <h3 class="text-lg font-bold text-[#111827] mb-2"><?php echo esc_html($title); ?></h3>
    <?php if ($description): ?>
        <p class="text-[#57534E] mb-4"><?php echo esc_html($description); ?></p>
    <?php endif; ?>
    <a href="<?php echo esc_url($cta_url); ?>"
       class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-lg text-white bg-[#111827] hover:bg-[#1F2937] transition-colors">
        <?php echo esc_html($cta_text); ?>
    </a>
</div>
