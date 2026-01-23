<?php
/**
 * Header Template with Navigation
 * 
 * Displays main header with logo, navigation menu, and mobile menu toggle
 */

if (!defined('ABSPATH')) {
    exit;
}

$site_url = get_bloginfo('url');
$site_name = get_bloginfo('name');
$logo_url = get_theme_mod('custom_logo') ? wp_get_attachment_image_src(get_theme_mod('custom_logo'), 'full')[0] : '';
$user = wp_get_current_user();
?>

<header class="site-header">
    <nav class="navbar navbar-bg-base-100 navbar-shadow sticky top-0 z-40 border-b border-base-300">
        <div class="container mx-auto px-4 md:px-8 flex justify-between items-center">
            
            <!-- Logo -->
            <div class="navbar-start">
                <a href="<?php echo esc_url($site_url); ?>" class="btn btn-ghost normal-case text-xl">
                    <?php if ($logo_url): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" class="h-8 w-auto">
                    <?php else: ?>
                        <span class="text-2xl font-bold text-primary"><?php echo esc_html($site_name); ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <!-- Desktop Navigation -->
            <div class="navbar-center hidden md:flex">
                <ul class="menu menu-horizontal px-1 gap-2">
                    <li>
                        <a href="<?php echo esc_url($site_url); ?>/om-oss/" class="hover:text-primary">
                            Om BIM Verdi
                        </a>
                    </li>
                    <li>
                        <details>
                            <summary class="hover:text-primary">Tjenester</summary>
                            <ul class="p-2 bg-base-100 rounded-t-none shadow-lg min-w-52">
                                <li><a href="<?php echo esc_url($site_url); ?>/digitalt-veikart/">Digitalt veikart</a></li>
                                <li><a href="<?php echo esc_url($site_url); ?>/prosjektutvikling/">Prosjektutvikling</a></li>
                                <li><a href="<?php echo esc_url($site_url); ?>/ressurser/">Ressurser</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary class="hover:text-primary">Temagrupper</summary>
                            <ul class="p-2 bg-base-100 rounded-t-none shadow-lg min-w-52">
                                <li><a href="<?php echo esc_url($site_url); ?>/temagrupper/modellkvalitet/">Modellkvalitet</a></li>
                                <li><a href="<?php echo esc_url($site_url); ?>/temagrupper/byggesksbim/">ByggesaksBIM</a></li>
                                <li><a href="<?php echo esc_url($site_url); ?>/temagrupper/prosjektbim/">ProsjektBIM</a></li>
                                <li><a href="<?php echo esc_url($site_url); ?>/temagrupper/eiendomsbim/">EiendomsBIM</a></li>
                                <li><a href="<?php echo esc_url($site_url); ?>/temagrupper/miljobim/">MiljøBIM</a></li>
                                <li><a href="<?php echo esc_url($site_url); ?>/temagrupper/sirkbim/">SirkBIM</a></li>
                                <li><a href="<?php echo esc_url($site_url); ?>/temagrupper/bimtech/">BIMtech</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <a href="<?php echo esc_url($site_url); ?>/arrangementer/" class="hover:text-primary">
                            Arrangementer
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url($site_url); ?>/deltakere/" class="hover:text-primary">
                            Deltakere
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url($site_url); ?>/verktoy/" class="hover:text-primary">
                            Verktøy
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Right Side (Login/Min Side) -->
            <div class="navbar-end gap-2">
                <?php if (is_user_logged_in()): ?>
                    <div class="dropdown dropdown-end">
                        <button class="btn btn-sm btn-outline" type="button">
                            <?php echo esc_html($user->first_name ?: $user->user_login); ?> ▼
                        </button>
                        <ul class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52 z-50">
                            <li><a href="<?php echo esc_url($site_url); ?>/min-side/">Min Side</a></li>
                            <li><a href="<?php echo esc_url($site_url); ?>/min-profil/">Min Profil</a></li>
                            <li><a href="<?php echo esc_url(wp_logout_url($site_url)); ?>">Logg ut</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo esc_url(home_url('/logg-inn/')); ?>" class="btn btn-sm btn-outline">
                        Logg inn
                    </a>
                    <a href="<?php echo esc_url($site_url); ?>/registrer-bruker/" class="btn btn-sm btn-primary">
                        Bli medlem
                    </a>
                <?php endif; ?>
                
                <!-- Mobile Menu Toggle -->
                <div class="dropdown dropdown-end md:hidden">
                    <button class="btn btn-ghost btn-circle" type="button" id="mobile-menu-toggle">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-5 h-5 stroke-current">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <ul class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52 z-50">
                        <li><a href="<?php echo esc_url($site_url); ?>/om-oss/">Om BIM Verdi</a></li>
                        <li><a href="<?php echo esc_url($site_url); ?>/tjenester/">Tjenester</a></li>
                        <li>
                            <details>
                                <summary>Temagrupper</summary>
                                <ul class="p-2">
                                    <li><a href="<?php echo esc_url($site_url); ?>/temagrupper/modellkvalitet/">Modellkvalitet</a></li>
                                    <li><a href="<?php echo esc_url($site_url); ?>/temagrupper/byggesksbim/">ByggesaksBIM</a></li>
                                    <li><a href="<?php echo esc_url($site_url); ?>/temagrupper/prosjektbim/">ProsjektBIM</a></li>
                                    <li><a href="<?php echo esc_url($site_url); ?>/temagrupper/eiendomsbim/">EiendomsBIM</a></li>
                                    <li><a href="<?php echo esc_url($site_url); ?>/temagrupper/miljobim/">MiljøBIM</a></li>
                                    <li><a href="<?php echo esc_url($site_url); ?>/temagrupper/sirkbim/">SirkBIM</a></li>
                                    <li><a href="<?php echo esc_url($site_url); ?>/temagrupper/bimtech/">BIMtech</a></li>
                                </ul>
                            </details>
                        </li>
                        <li><a href="<?php echo esc_url($site_url); ?>/arrangementer/">Arrangementer</a></li>
                        <li><a href="<?php echo esc_url($site_url); ?>/deltakere/">Deltakere</a></li>
                        <li><a href="<?php echo esc_url($site_url); ?>/verktoy/">Verktøy</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</header>

<style>
    .navbar-shadow {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    
    @media (max-width: 768px) {
        .navbar {
            padding: 0.5rem 1rem;
        }
    }
</style>
