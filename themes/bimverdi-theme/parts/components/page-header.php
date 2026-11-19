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
// Valgfri lenkeblokk midt i sidetoppen:
// ['text' => '...', 'url' => 'https://...', 'logo' => 'https://.../logo.png', 'logo_alt' => '...']
$center_link = (!empty($args['center_link']['url'])) ? $args['center_link'] : null;
?>

<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8 pb-6 border-b border-[#E5E0D8]">
    <?php if ($center_link): ?>
        <style>
            .bv-ph-title{order:1;}
            .bv-ph-center{order:2;display:flex;flex-direction:column;align-items:center;text-align:center;gap:0.25rem;color:#FF8B5E;font-weight:700;text-decoration:underline;max-width:20rem;}
            .bv-ph-center:hover{color:#E67A4E;}
            .bv-ph-center img{height:80px;width:auto;}
            .bv-ph-actions{order:3;}
            @media (min-width:640px){
                .bv-ph-title,.bv-ph-actions{flex:1 1 0;}
                .bv-ph-actions{display:flex;justify-content:flex-end;}
                .bv-ph-center{flex:0 1 20rem;}
            }
        </style>
    <?php endif; ?>
    <div class="bv-ph-title">
        <h1 class="text-2xl font-bold text-[#1A1A1A] mb-1"><?php echo esc_html($title); ?></h1>
        <?php if ($description): ?>
            <p class="text-sm text-[#5A5A5A]"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
    </div>

    <?php if ($center_link): ?>
        <a class="bv-ph-center" href="<?php echo esc_url($center_link['url']); ?>" target="_blank" rel="noopener">
            <?php if (!empty($center_link['logo'])): ?>
                <img src="<?php echo esc_url($center_link['logo']); ?>" alt="<?php echo esc_attr($center_link['logo_alt'] ?? ''); ?>">
            <?php endif; ?>
            <?php if (!empty($center_link['text'])): ?>
                <span><?php echo esc_html($center_link['text']); ?></span>
            <?php endif; ?>
        </a>
    <?php endif; ?>

    <?php if (!empty($actions) || $center_link): ?>
        <div class="bv-ph-actions">
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
        </div>
    <?php endif; ?>
</div>
