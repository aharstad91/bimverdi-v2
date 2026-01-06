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
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Til forsiden
                </a>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center gap-2 text-sm font-medium text-[#1A1A1A] hover:text-[#5A5A5A] transition-colors">
                    Logg ut
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Secondary Navigation: Min Side menu with icons -->
<nav class="bg-white border-b border-[#E5E0D5]">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex items-center justify-between h-12">
            
            <!-- Main navigation items -->
            <div class="flex items-center h-12">
                
                <!-- Dashboard -->
                <a href="<?php echo esc_url(home_url('/min-side/')); ?>" 
                   class="relative flex items-center gap-2 px-4 h-12 text-sm <?php echo $current_page === 'dashboard' ? 'font-semibold text-[#1A1A1A]' : 'font-medium text-[#5A5A5A] hover:text-[#1A1A1A]'; ?> transition-colors">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Dashboard
                    <?php if ($current_page === 'dashboard'): ?>
                        <span class="absolute bottom-0 left-0 right-0 h-[2px] bg-[#1A1A1A]"></span>
                    <?php endif; ?>
                </a>
                
                <!-- Mine verktøy -->
                <a href="<?php echo esc_url(home_url('/min-side/mine-verktoy/')); ?>" 
                   class="relative flex items-center gap-2 px-4 h-12 text-sm <?php echo $current_page === 'verktoy' ? 'font-semibold text-[#1A1A1A]' : 'font-medium text-[#5A5A5A] hover:text-[#1A1A1A]'; ?> transition-colors">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 0-8.94-8.94l-3.76 3.76a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.76-3.76a2 2 0 0 1 2.83 2.83l-3.75 3.75a1 1 0 0 0 0 1.4z"></path><path d="M9.3 17.7a1 1 0 0 0 0-1.4L7.7 14.7a1 1 0 0 0-1.4 0l-3.77 3.77a6 6 0 0 0 8.94 8.94l3.76-3.76a1 1 0 0 0 0-1.4l-1.6-1.6a1 1 0 0 0-1.4 0l-3.76 3.76a2 2 0 0 1-2.83-2.83l3.75-3.75a1 1 0 0 0 0-1.4z"></path></svg>
                    Mine verktøy
                    <?php if ($tool_count > 0): ?>
                        <span class="inline-flex items-center justify-center px-2 h-5 text-[10px] font-bold text-[#1A1A1A] bg-[#F2F0EB] border border-[#E5E0D8] rounded-full">
                            <?php echo $tool_count; ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($current_page === 'verktoy'): ?>
                        <span class="absolute bottom-0 left-0 right-0 h-[2px] bg-[#1A1A1A]"></span>
                    <?php endif; ?>
                </a>
                
                <!-- Artikler -->
                <a href="<?php echo esc_url(home_url('/min-side/artikler/')); ?>" 
                   class="relative flex items-center gap-2 px-4 h-12 text-sm <?php echo $current_page === 'artikler' ? 'font-semibold text-[#1A1A1A]' : 'font-medium text-[#5A5A5A] hover:text-[#1A1A1A]'; ?> transition-colors">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    Artikler
                    <?php if ($current_page === 'artikler'): ?>
                        <span class="absolute bottom-0 left-0 right-0 h-[2px] bg-[#1A1A1A]"></span>
                    <?php endif; ?>
                </a>
                
                <!-- Prosjektidéer -->
                <a href="<?php echo esc_url(home_url('/min-side/prosjektideer/')); ?>" 
                   class="relative flex items-center gap-2 px-4 h-12 text-sm <?php echo $current_page === 'prosjektideer' ? 'font-semibold text-[#1A1A1A]' : 'font-medium text-[#5A5A5A] hover:text-[#1A1A1A]'; ?> transition-colors">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"></circle><line x1="12" y1="16" x2="12" y2="16.01"></line><line x1="12" y1="8" x2="12" y2="12"></line></svg>
                    Prosjektidéer
                    <?php if ($current_page === 'prosjektideer'): ?>
                        <span class="absolute bottom-0 left-0 right-0 h-[2px] bg-[#1A1A1A]"></span>
                    <?php endif; ?>
                </a>
                
                <!-- Arrangementer -->
                <a href="<?php echo esc_url(home_url('/min-side/arrangementer/')); ?>" 
                   class="relative flex items-center gap-2 px-4 h-12 text-sm <?php echo $current_page === 'arrangementer' ? 'font-semibold text-[#1A1A1A]' : 'font-medium text-[#5A5A5A] hover:text-[#1A1A1A]'; ?> transition-colors">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Arrangementer
                    <?php if ($current_page === 'arrangementer'): ?>
                        <span class="absolute bottom-0 left-0 right-0 h-[2px] bg-[#1A1A1A]"></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <!-- Account menu on right with left border -->
            <div class="flex items-center h-6 border-l border-[#E5E0D8] pl-4">
                <a href="<?php echo esc_url(home_url('/min-side/profil/')); ?>" 
                   class="flex items-center gap-2 text-sm font-medium text-[#1A1A1A] hover:text-[#5A5A5A] transition-colors">
                    Min konto
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </a>
            </div>
        </div>
    </div>
</nav>

<div id="content" class="site-content">
