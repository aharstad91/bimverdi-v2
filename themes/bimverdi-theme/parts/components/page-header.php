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
$center_link = $args['center_link'] ?? null; // ['text' => '...', 'url' => 'https://...']
?>

<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8 pb-6 border-b border-[#E5E0D8]"<?php echo $center_link ? ' style="position:relative;"' : ''; ?>>
    <?php if ($center_link && !empty($center_link['url'])): ?>
        <style>
            .bv-header-center-link{color:#FF8B5E;font-weight:700;font-size:1.125rem;text-decoration:underline;white-space:nowrap;}
            .bv-header-center-link:hover{color:#E67A4E;}
            @media (min-width:640px){.bv-header-center-link{position:absolute;left:50%;top:0.75rem;transform:translateX(-50%);}}
        </style>
        <a class="bv-header-center-link" href="<?php echo esc_url($center_link['url']); ?>" target="_blank" rel="noopener"><?php echo esc_html($center_link['text'] ?? ''); ?></a>
    <?php endif; ?>
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
                // Support both full URLs (from bimverdi_minside_url) and relative paths
                $url = $action['url'];
                if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://') && !str_starts_with($url, '#')) {
                    $url = home_url($url);
                }
                bimverdi_button([
                    'text'    => $action['text'],
                    'variant' => $variant,
                    'icon'    => $action['icon'] ?? null,
                    'href'    => $url,
                    'size'    => $action['size'] ?? 'medium',
                    'target'  => $action['target'] ?? '',
                ]);
            endforeach; ?>
        </div>
    <?php endif; ?>
</div>
