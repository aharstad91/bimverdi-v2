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

<body <?php body_class('bg-white'); ?>>
<?php wp_body_open(); ?>

<!-- Primary Header: Logo + Back Link + Logout -->
<header class="bg-white border-b border-[#E7E5E4] sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex items-center justify-between h-16">
            
            <!-- Logo -->
            <div>
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/bimverdi-logo.png'); ?>" alt="BIM Verdi" style="height: 36px; width: auto;">
                </a>
            </div>
            
            <!-- Right side: Back link + Logout -->
            <div class="flex items-center gap-6">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="flex items-center gap-2 text-sm font-medium text-[#57534E] hover:text-[#111827] transition-colors">
                    <?php echo bimverdi_icon('arrow-left', 16); ?>
                    Til forsiden
                </a>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center gap-2 text-sm font-medium text-[#111827] hover:text-[#57534E] transition-colors">
                    Logg ut
                    <?php echo bimverdi_icon('log-out', 16); ?>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Secondary Navigation: Min Side menu with icons -->
<nav class="bg-white border-b border-[#E7E5E4]">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex items-center justify-between h-12">
            
            <!-- Main navigation items (dynamic from helper) -->
            <div class="flex items-center h-12 gap-x-4 sm:gap-x-8 overflow-x-auto">
                <?php foreach ($nav_items as $key => $item):
                    $is_active = bimverdi_is_minside_route($item['routes']);
                ?>
                <a href="<?php echo esc_url($item['url']); ?>"
                   class="relative flex items-center gap-2 h-12 text-sm whitespace-nowrap <?php echo $is_active ? 'font-semibold text-[#111827]' : 'font-medium text-[#57534E] hover:text-[#111827]'; ?> transition-colors">
                    <?php echo bimverdi_icon($item['icon'], 20); ?>
                    <?php echo esc_html($item['label']); ?>
                    
                    <?php if (!empty($item['badge'])): ?>
                        <span class="inline-flex items-center justify-center px-2 h-5 text-[10px] font-bold text-[#111827] bg-[#F5F5F4] border border-[#E7E5E4] rounded-full">
                            <?php echo (int) $item['badge']; ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($is_active): ?>
                        <span class="absolute bottom-0 left-0 right-0 h-[2px] bg-[#111827]"></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Account dropdown on right with left border -->
            <?php
            $has_company = bimverdi_user_has_company($user_id);
            $is_hovedkontakt = bimverdi_is_hovedkontakt($user_id);
            $current_route = bimverdi_get_current_route();
            $account_active = bimverdi_is_minside_route(['profil', 'foretak']);

            // Build same nav structure as account-sidenav
            $account_sections = [
                'profil' => [
                    'label' => __('Profil', 'bimverdi'),
                    'items' => [
                        ['route' => 'profil', 'label' => __('Min profil', 'bimverdi'), 'icon' => 'user'],
                        ['route' => 'profil/rediger', 'label' => __('Rediger profil', 'bimverdi'), 'icon' => 'square-pen'],
                        ['route' => 'profil/passord', 'label' => __('Endre passord', 'bimverdi'), 'icon' => 'shield'],
                    ],
                ],
            ];
            if ($has_company) {
                $foretak_items = [
                    ['route' => 'foretak', 'label' => __('Mitt foretak', 'bimverdi'), 'icon' => 'building-2'],
                ];
                if ($is_hovedkontakt) {
                    $foretak_items[] = ['route' => 'foretak/rediger', 'label' => __('Rediger foretak', 'bimverdi'), 'icon' => 'square-pen'];
                }
                $foretak_items[] = ['route' => 'foretak/kolleger', 'label' => __('Kolleger', 'bimverdi'), 'icon' => 'users'];
                $account_sections['foretak'] = [
                    'label' => __('Foretak', 'bimverdi'),
                    'items' => $foretak_items,
                ];
            }
            ?>
            <div class="relative flex items-center h-12 border-l border-[#E7E5E4] pl-4" id="account-dropdown">
                <button id="account-dropdown-btn"
                        class="flex items-center gap-2 text-sm font-medium <?php echo $account_active ? 'text-[#F97316]' : 'text-[#111827] hover:text-[#57534E]'; ?> transition-colors"
                        aria-expanded="false" aria-haspopup="true">
                    Min konto
                    <?php echo bimverdi_icon('user', 20); ?>
                    <svg id="account-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-150"><path d="m6 9 6 6 6-6"/></svg>
                </button>

                <!-- Dropdown panel -->
                <div id="account-dropdown-panel"
                     class="absolute right-0 top-full mt-1 w-56 bg-white rounded-lg border border-[#E7E5E4] shadow-lg py-2 z-50 opacity-0 translate-y-1 pointer-events-none transition-all duration-150"
                     role="menu">

                    <?php $first_section = true; ?>
                    <?php foreach ($account_sections as $section_key => $section): ?>
                        <?php if (!$first_section): ?>
                            <div class="border-t border-[#E7E5E4] my-2"></div>
                        <?php endif; ?>
                        <div class="px-3 pt-1 pb-1">
                            <span class="text-[10px] font-semibold text-[#5A5A5A] uppercase tracking-wider"><?php echo esc_html($section['label']); ?></span>
                        </div>
                        <?php foreach ($section['items'] as $item):
                            $is_item_active = ($item['route'] === $current_route) || ($item['route'] === '' && ($current_route === 'dashboard' || $current_route === ''));
                        ?>
                            <a href="<?php echo esc_url(bimverdi_minside_url($item['route'])); ?>" role="menuitem"
                               class="flex items-center gap-3 px-3 py-2 text-sm transition-colors <?php echo $is_item_active ? 'bg-gray-100 text-[#1A1A1A] font-medium' : 'text-[#5A5A5A] hover:bg-gray-50 hover:text-[#1A1A1A]'; ?>">
                                <span class="flex-shrink-0 <?php echo $is_item_active ? 'text-[#1A1A1A]' : 'text-[#888888]'; ?>">
                                    <?php echo bimverdi_icon($item['icon'], 16); ?>
                                </span>
                                <?php echo esc_html($item['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <script>
            (function() {
                var btn = document.getElementById('account-dropdown-btn');
                var panel = document.getElementById('account-dropdown-panel');
                var chevron = document.getElementById('account-chevron');
                var open = false;
                function toggle(show) {
                    open = typeof show === 'boolean' ? show : !open;
                    btn.setAttribute('aria-expanded', open);
                    if (open) {
                        panel.classList.remove('opacity-0', 'translate-y-1', 'pointer-events-none');
                        panel.classList.add('opacity-100', 'translate-y-0');
                        chevron.classList.add('rotate-180');
                    } else {
                        panel.classList.add('opacity-0', 'translate-y-1', 'pointer-events-none');
                        panel.classList.remove('opacity-100', 'translate-y-0');
                        chevron.classList.remove('rotate-180');
                    }
                }
                btn.addEventListener('click', function(e) { e.stopPropagation(); toggle(); });
                document.addEventListener('click', function(e) {
                    if (open && !panel.contains(e.target)) toggle(false);
                });
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && open) toggle(false);
                });
            })();
            </script>
        </div>
    </div>
</nav>

<div id="content" class="site-content">
