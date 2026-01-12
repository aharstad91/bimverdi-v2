<?php
/**
 * Reusable Page Header Component
 * 
 * Usage: get_template_part('parts/components/page-header', null, [
 *     'title'       => 'Mine verktøy',
 *     'description' => 'Administrer verktøyene du har registrert.',  // optional
 *     'actions'     => [  // optional
 *         ['text' => 'Nytt verktøy', 'url' => '/registrer-verktoy/', 'variant' => 'primary', 'icon' => 'plus'],
 *         ['text' => 'Eksporter', 'url' => '#', 'variant' => 'secondary', 'icon' => 'download'],
 *     ],
 * ]);
 * 
 * Action variants: 'primary' (filled black), 'secondary' (outline)
 * 
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$title = $args['title'] ?? get_the_title();
$description = $args['description'] ?? '';
$actions = $args['actions'] ?? [];
?>

<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8 pb-6 border-b border-[#E5E0D8]">
    <div>
        <h1 class="text-2xl font-bold text-[#1A1A1A] mb-1"><?php echo esc_html($title); ?></h1>
        <?php if ($description): ?>
            <p class="text-sm text-[#5A5A5A]"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($actions)): ?>
        <div class="flex gap-3 flex-shrink-0">
            <?php foreach ($actions as $action): 
                $variant = $action['variant'] ?? 'primary';
                bimverdi_button([
                    'text'    => $action['text'],
                    'variant' => $variant,
                    'icon'    => $action['icon'] ?? null,
                    'href'    => home_url($action['url']),
                    'size'    => $action['size'] ?? 'medium',
                    'target'  => $action['target'] ?? '',
                ]);
            endforeach; ?>
        </div>
    <?php endif; ?>
</div>
