<?php
/**
 * Header for Min Side pages
 * Authenticated user header with back link and logout
 * Plus secondary navigation with icons
 * 
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Count items for badges
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
$tool_count = 0;

if ($company_id) {
    $tools = get_posts(array(
        'post_type' => 'verktoy',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'eier_leverandor',
                'value' => $company_id,
                'compare' => '='
            )
        ),
    ));
    $tool_count = count($tools);
}

// Get current page for active state
$current_template = basename(get_page_template());
$current_page = '';
if (strpos($current_template, 'dashboard') !== false) {
    $current_page = 'dashboard';
} elseif (strpos($current_template, 'verktoy') !== false) {
    $current_page = 'verktoy';
} elseif (strpos($current_template, 'artikler') !== false) {
    $current_page = 'artikler';
} elseif (strpos($current_template, 'prosjektideer') !== false) {
    $current_page = 'prosjektideer';
} elseif (strpos($current_template, 'arrangementer') !== false) {
    $current_page = 'arrangementer';
}
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
<header class="bg-white border-b border-[#E5E0D5] sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            
            <!-- Logo -->
            <div class="flex-shrink-0">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="text-xl font-bold text-[#1A1A1A]">
                    BIM Verdi
                </a>
            </div>
            
            <!-- Right side: Back link + Logout -->
            <div class="flex items-center gap-6">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="flex items-center gap-2 text-sm text-[#FF8B5E] hover:text-[#FF6B3E] transition-colors">
                    <wa-icon name="arrow-left" library="fa" style="font-size: 0.875rem;"></wa-icon>
                    Til forsiden
                </a>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center gap-2 text-sm text-[#FF8B5E] hover:text-[#FF6B3E] transition-colors">
                    Logg ut
                    <wa-icon name="arrow-right" library="fa" style="font-size: 0.875rem;"></wa-icon>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Secondary Navigation: Min Side menu with icons -->
<nav class="bg-white border-b border-[#E5E0D5]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">
            
            <!-- Main navigation items -->
            <div class="flex items-center gap-1">
                
                <!-- Dashboard -->
                <a href="<?php echo esc_url(home_url('/min-side/')); ?>" 
                   class="flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors <?php echo $current_page === 'dashboard' ? 'text-[#FF8B5E] border-b-2 border-[#FF8B5E]' : 'text-[#5A5A5A] hover:text-[#1A1A1A]'; ?>">
                    <wa-icon name="grid-2" library="fa" style="font-size: 1rem;"></wa-icon>
                    Dashboard
                </a>
                
                <!-- Verktøy -->
                <a href="<?php echo esc_url(home_url('/min-side/verktoy/')); ?>" 
                   class="flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors relative <?php echo $current_page === 'verktoy' ? 'text-[#FF8B5E] border-b-2 border-[#FF8B5E]' : 'text-[#5A5A5A] hover:text-[#1A1A1A]'; ?>">
                    <wa-icon name="wrench" library="fa" style="font-size: 1rem;"></wa-icon>
                    Verktøy
                    <?php if ($tool_count > 0): ?>
                        <wa-badge variant="neutral" size="small" pill><?php echo $tool_count; ?></wa-badge>
                    <?php endif; ?>
                </a>
                
                <!-- Artikler -->
                <a href="<?php echo esc_url(home_url('/min-side/artikler/')); ?>" 
                   class="flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors <?php echo $current_page === 'artikler' ? 'text-[#FF8B5E] border-b-2 border-[#FF8B5E]' : 'text-[#5A5A5A] hover:text-[#1A1A1A]'; ?>">
                    <wa-icon name="file-lines" library="fa" style="font-size: 1rem;"></wa-icon>
                    Artikler
                </a>
                
                <!-- Prosjektidéer -->
                <a href="<?php echo esc_url(home_url('/min-side/prosjektideer/')); ?>" 
                   class="flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors <?php echo $current_page === 'prosjektideer' ? 'text-[#FF8B5E] border-b-2 border-[#FF8B5E]' : 'text-[#5A5A5A] hover:text-[#1A1A1A]'; ?>">
                    <wa-icon name="lightbulb" library="fa" style="font-size: 1rem;"></wa-icon>
                    Prosjektidéer
                </a>
                
                <!-- Arrangementer -->
                <a href="<?php echo esc_url(home_url('/min-side/arrangementer/')); ?>" 
                   class="flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors <?php echo $current_page === 'arrangementer' ? 'text-[#FF8B5E] border-b-2 border-[#FF8B5E]' : 'text-[#5A5A5A] hover:text-[#1A1A1A]'; ?>">
                    <wa-icon name="calendar" library="fa" style="font-size: 1rem;"></wa-icon>
                    Arrangementer
                </a>
            </div>
            
            <!-- Account menu on right -->
            <div class="flex items-center">
                <a href="<?php echo esc_url(home_url('/min-side/profil/')); ?>" 
                   class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
                    <wa-icon name="user" library="fa" style="font-size: 1rem;"></wa-icon>
                    Min konto
                </a>
            </div>
        </div>
    </div>
</nav>

<div id="content" class="site-content">
