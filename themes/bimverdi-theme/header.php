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

<!-- Public Header: Logo + Main Nav + Login button -->
<header class="bg-white border-b border-[#E5E0D5]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            
            <!-- Logo -->
            <div class="flex-shrink-0">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="text-xl font-bold text-[#1A1A1A]">
                    BIM Verdi
                </a>
            </div>
            
            <!-- Main Navigation -->
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'container' => 'nav',
                'container_class' => 'hidden md:flex items-center gap-8 ml-12',
                'menu_class' => 'flex items-center gap-8',
                'fallback_cb' => false,
                'depth' => 2,
                'link_class' => 'text-sm font-medium text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors',
            ));
            ?>
            
            <!-- Right side: Login button or My Account -->
            <div class="flex items-center gap-4">
                <?php if (is_user_logged_in()) : ?>
                    <a href="<?php echo esc_url(home_url('/min-side/')); ?>" 
                       class="inline-flex items-center gap-2 px-4 py-2 bg-[#1A1A1A] text-white text-sm font-medium rounded-md hover:bg-[#333333] transition-colors">
                        Min side
                    </a>
                <?php else : ?>
                    <a href="<?php echo wp_login_url(); ?>" 
                       class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#1A1A1A] text-white text-sm font-medium rounded-md hover:bg-[#333333] transition-colors">
                        Logg inn
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button type="button" class="text-[#1A1A1A] hover:text-[#5A5A5A] focus:outline-none" id="mobile-menu-button">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</header>

<div id="content" class="site-content">
