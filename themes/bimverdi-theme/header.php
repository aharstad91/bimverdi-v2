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

<?php get_template_part('parts/components/announcement-bar'); ?>

<!-- Public Header: Logo + Main Nav + Login button -->
<header class="bg-white border-b border-[#E7E5E4] relative z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            
            <!-- Logo -->
            <div class="flex-shrink-0">
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/bimverdi-logo.png'); ?>" alt="BIM Verdi" style="height: 36px; width: auto;">
                </a>
            </div>
            
            <!-- Main Navigation -->
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'container' => 'nav',
                'container_class' => 'bv-nav hidden md:flex items-center ml-8',
                'menu_class' => 'bv-nav__list',
                'fallback_cb' => false,
                'depth' => 2,
            ));
            ?>
            
            <!-- Right side: Login/Min side + Mobile menu button -->
            <div class="flex items-center gap-4">
                <?php if (is_user_logged_in()) : ?>
                    <a href="<?php echo esc_url(home_url('/min-side/')); ?>"
                       class="hidden md:inline-flex items-center gap-2 px-4 py-2 bg-[#1A1A1A] text-white text-sm font-medium rounded-md hover:bg-[#333333] transition-colors">
                        Min side
                    </a>
                <?php else : ?>
                    <a href="<?php echo home_url('/logg-inn/'); ?>"
                       class="hidden md:inline-flex items-center gap-2 px-6 py-2.5 bg-[#1A1A1A] text-white text-sm font-medium rounded-md hover:bg-[#333333] transition-colors">
                        Logg inn
                    </a>
                <?php endif; ?>

                <!-- Mobile menu button -->
                <button type="button" class="md:hidden text-[#1A1A1A] hover:text-[#5A5A5A] focus:outline-none p-1" id="mobile-menu-button" aria-expanded="false" aria-label="Meny">
                    <!-- Hamburger icon -->
                    <svg class="bv-mobile-icon-open h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <!-- Close icon (hidden by default) -->
                    <svg class="bv-mobile-icon-close h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</header>

<!-- Mobile Menu (fullscreen dropdown, hidden by default) -->
<div id="mobile-menu" class="hidden md:hidden fixed left-0 right-0 bottom-0 z-40 bg-white overflow-y-auto" style="top: 4rem;">
    <div class="px-6 pt-10 pb-12">
        <?php
        wp_nav_menu(array(
            'theme_location' => 'primary',
            'container'      => false,
            'menu_class'     => 'bv-mobile-nav space-y-1',
            'fallback_cb'    => false,
            'depth'          => 2,
        ));
        ?>

        <hr class="border-[#E7E5E4] my-4">

        <?php if (is_user_logged_in()) : ?>
            <a href="<?php echo esc_url(home_url('/min-side/')); ?>"
               class="flex items-center gap-3 px-3 py-3 text-base font-semibold text-[#1A1A1A] rounded-lg hover:bg-[#F5F5F4] transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                Min side
            </a>
        <?php else : ?>
            <a href="<?php echo home_url('/logg-inn/'); ?>"
               class="flex items-center justify-center gap-2 px-6 py-3 bg-[#1A1A1A] text-white text-base font-medium rounded-lg hover:bg-[#333333] transition-colors">
                Logg inn
            </a>
        <?php endif; ?>
    </div>
</div>

<div id="content" class="site-content">
