<?php
/**
 * Reusable Page Header Component
 * 
 * Usage: get_template_part('parts/components/page-header', null, [
 *     'title'       => 'Mine verktøy',
 *     'description' => 'Administrer verktøyene du har registrert.',  // optional
 *     'actions'     => [  // optional
 *         ['text' => 'Nytt verktøy', 'url' => '/registrer-verktoy/', 'variant' => 'primary'],
 *         ['text' => 'Eksporter', 'url' => '#', 'variant' => 'secondary'],
 *     ],
 * ]);
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
                $is_primary = ($action['variant'] ?? 'primary') === 'primary';
            ?>
                <a href="<?php echo esc_url(home_url($action['url'])); ?>" 
                   class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors
                          <?php echo $is_primary 
                              ? 'text-white bg-[#1A1A1A] hover:bg-[#333]' 
                              : 'text-[#1A1A1A] bg-transparent border border-[#E5E0D8] hover:bg-[#F2F0EB]'; ?>">
                    <?php echo esc_html($action['text']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
