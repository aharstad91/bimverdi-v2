<?php
/**
 * Success Banner Component
 *
 * Grønn bekreftelses-banner brukt på Min Side for å bekrefte at en handling
 * er fullført (konto aktivert, foretak koblet, invitasjon akseptert, osv.).
 *
 * Usage:
 * get_template_part('parts/components/success-banner', null, [
 *     'title' => 'Velkommen, Claude!',
 *     'message' => 'Kontoen din er nå aktivert.',
 * ]);
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$title   = $args['title'] ?? '';
$message = $args['message'] ?? '';
?>
<div class="mb-6 px-4 py-3 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
         fill="none" stroke="#16a34a" stroke-width="2"
         stroke-linecap="round" stroke-linejoin="round"
         class="flex-shrink-0 block">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
        <polyline points="22 4 12 14.01 9 11.01"/>
    </svg>
    <p class="text-sm text-green-800 leading-snug" style="margin:0;">
        <?php if ($title): ?><strong><?php echo esc_html($title); ?></strong> <?php endif; ?>
        <?php echo wp_kses_post($message); ?>
    </p>
</div>
