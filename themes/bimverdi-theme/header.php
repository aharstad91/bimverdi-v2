<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class('bg-bim-beige-100'); ?>>
<?php wp_body_open(); ?>

<header class="bg-white shadow-sm sticky top-0 z-50">
    <nav class="container mx-auto px-4">
        <div class="flex items-center justify-between h-20">
            
            <!-- Logo -->
            <div class="flex-shrink-0">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="text-2xl font-bold text-bim-black-900">
                        <?php bloginfo('name'); ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Primary Navigation -->
            <div class="hidden md:block">
                <?php
                wp_nav_menu(array(
                    'menu' => 31,
                    'container' => false,
                    'menu_class' => 'flex space-x-8',
                    'fallback_cb' => false,
                ));
                ?>
            </div>
            
            <!-- CTA Buttons -->
            <div class="flex items-center space-x-4">
                <?php if (is_user_logged_in()) : ?>
                    <a href="<?php echo esc_url(home_url('/min-side/')); ?>" class="btn-hjem-outline">
                        Min side
                    </a>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="text-bim-black-500 hover:text-bim-orange">
                        Logg ut
                    </a>
                <?php else : ?>
                    <a href="<?php echo wp_login_url(); ?>" class="btn-hjem-outline">
                        Logg inn
                    </a>
                    <a href="<?php echo esc_url(home_url('/registrer-bruker/')); ?>" class="btn-hjem-primary">
                        Bli medlem
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button type="button" class="text-bim-black-900 hover:text-bim-orange focus:outline-none" id="mobile-menu-button">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu (hidden by default) -->
        <div class="md:hidden hidden" id="mobile-menu">
            <?php
            wp_nav_menu(array(
                'menu' => 31,
                'container' => false,
                'menu_class' => 'py-4 space-y-2',
                'fallback_cb' => false,
            ));
            ?>
        </div>
    </nav>
</header>

<div id="content" class="site-content">
