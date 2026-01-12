<?php
/**
 * Reusable Empty State Component
 * 
 * Usage: get_template_part('parts/components/empty-state', null, [
 *     'icon'        => 'wrench',     // Lucide icon name
 *     'title'       => 'Ingen verktøy',
 *     'description' => 'Du har ikke registrert noen verktøy ennå.',
 *     'cta_text'    => 'Registrer verktøy',  // optional
 *     'cta_url'     => '/min-side/registrer-verktoy/',  // optional
 * ]);
 * 
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$icon = $args['icon'] ?? 'inbox';
$title = $args['title'] ?? __('Ingen elementer ennå', 'bimverdi');
$description = $args['description'] ?? '';
$cta_text = $args['cta_text'] ?? null;
$cta_url = $args['cta_url'] ?? null;

// Lucide icon SVGs
$icons = [
    'inbox' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path></svg>',
    'wrench' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
    'building-2' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg>',
    'file-text' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
    'lightbulb' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"></path><path d="M9 18h6"></path><path d="M10 22h4"></path></svg>',
    'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
    'user' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
];
?>

<div class="text-center py-16 px-4">
    <!-- Icon -->
    <div class="inline-flex items-center justify-center w-16 h-16 mb-4 rounded-full bg-[#F2F0EB]">
        <span class="text-[#5A5A5A]">
            <?php echo $icons[$icon] ?? $icons['inbox']; ?>
        </span>
    </div>
    
    <!-- Title -->
    <h3 class="text-lg font-semibold text-[#1A1A1A] mb-2"><?php echo esc_html($title); ?></h3>
    
    <!-- Description -->
    <?php if ($description): ?>
        <p class="text-sm text-[#5A5A5A] mb-6 max-w-md mx-auto"><?php echo esc_html($description); ?></p>
    <?php endif; ?>
    
    <!-- CTA Button -->
    <?php if ($cta_text && $cta_url): ?>
        <?php bimverdi_button([
            'text'    => $cta_text,
            'variant' => 'primary',
            'href'    => home_url($cta_url),
            'icon'    => $args['cta_icon'] ?? null,
        ]); ?>
    <?php endif; ?>
</div>
