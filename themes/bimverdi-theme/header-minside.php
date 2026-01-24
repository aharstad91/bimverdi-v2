<?php
/**
 * Header for Min Side pages
 * Authenticated user header with back link and logout
 * Plus secondary navigation with icons
 * 
 * Uses route-based navigation from minside-helpers.php
 * 
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get navigation data from helper
$nav_items = bimverdi_get_minside_nav();
$primary_route = bimverdi_get_primary_route();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class('bg-[#F7F5EF]'); ?>>
<?php wp_body_open(); ?>

<!-- Primary Header: Logo + Back Link + Logout -->
<header class="bg-[#FBF9F5] border-b border-[#E5E0D5] sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex items-center justify-between h-16">
            
            <!-- Logo -->
            <div>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="text-[20px] font-bold text-[#1A1A1A] leading-[28px]">
                    BIM Verdi
                </a>
            </div>
            
            <!-- Right side: Back link + Logout -->
            <div class="flex items-center gap-6">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="flex items-center gap-2 text-sm font-medium text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
                    <?php echo bimverdi_icon('arrow-left', 16); ?>
                    Til forsiden
                </a>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center gap-2 text-sm font-medium text-[#1A1A1A] hover:text-[#5A5A5A] transition-colors">
                    Logg ut
                    <?php echo bimverdi_icon('log-out', 16); ?>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Secondary Navigation: Min Side menu with icons -->
<nav class="bg-white border-b border-[#E5E0D5]">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex items-center justify-between h-12">
            
            <!-- Main navigation items (dynamic from helper) -->
            <div class="flex items-center h-12 gap-x-8">
                <?php foreach ($nav_items as $key => $item):
                    $is_active = bimverdi_is_minside_route($item['routes']);
                ?>
                <a href="<?php echo esc_url($item['url']); ?>"
                   class="relative flex items-center gap-2 h-12 text-sm <?php echo $is_active ? 'font-semibold text-[#1A1A1A]' : 'font-medium text-[#5A5A5A] hover:text-[#1A1A1A]'; ?> transition-colors">
                    <?php echo bimverdi_icon($item['icon'], 20); ?>
                    <?php echo esc_html($item['label']); ?>
                    
                    <?php if (!empty($item['badge'])): ?>
                        <span class="inline-flex items-center justify-center px-2 h-5 text-[10px] font-bold text-[#1A1A1A] bg-[#F2F0EB] border border-[#E5E0D8] rounded-full">
                            <?php echo (int) $item['badge']; ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($is_active): ?>
                        <span class="absolute bottom-0 left-0 right-0 h-[2px] bg-[#1A1A1A]"></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Account menu on right with left border -->
            <div class="flex items-center h-6 border-l border-[#E5E0D8] pl-4">
                <a href="<?php echo esc_url(bimverdi_minside_url('profil')); ?>" 
                   class="flex items-center gap-2 text-sm font-medium <?php echo bimverdi_is_minside_route(['profil', 'foretak']) ? 'text-[#F97316]' : 'text-[#1A1A1A] hover:text-[#5A5A5A]'; ?> transition-colors">
                    Min konto
                    <?php echo bimverdi_icon('user', 20); ?>
                    <?php echo bimverdi_icon('chevron-down', 14); ?>
                </a>
            </div>
        </div>
    </div>
</nav>

<div id="content" class="site-content">
