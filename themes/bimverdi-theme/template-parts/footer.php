<?php
/**
 * Footer Template
 * 
 * Displays footer with links, contact info, and copyright
 */

if (!defined('ABSPATH')) {
    exit;
}

$site_url = get_bloginfo('url');
$site_name = get_bloginfo('name');
$footer_email = get_option('admin_email');
?>

<footer class="bg-neutral text-neutral-content">
    <div class="footer p-10 gap-8 max-w-7xl mx-auto">
        
        <!-- About Section -->
        <div>
            <span class="footer-title text-lg font-bold mb-4"><?php echo esc_html($site_name); ?></span>
            <p class="text-sm max-w-xs">
                BIM Verdi er et fagnettverk som bidrar til bedre og mer effektiv digitalisering av byggenæringen.
            </p>
            <div class="flex gap-4 mt-4">
                <a href="#" class="text-primary hover:underline">LinkedIn</a>
                <a href="#" class="text-primary hover:underline">Twitter</a>
                <a href="#" class="text-primary hover:underline">Facebook</a>
            </div>
        </div>
        
        <!-- Quick Links -->
        <div>
            <span class="footer-title text-lg font-bold mb-4">Navigasjon</span>
            <a href="<?php echo esc_url($site_url); ?>" class="link link-hover">Hjem</a>
            <a href="<?php echo esc_url($site_url); ?>/om-oss/" class="link link-hover">Om BIM Verdi</a>
            <a href="<?php echo esc_url($site_url); ?>/deltakere/" class="link link-hover">Deltakere</a>
            <a href="<?php echo esc_url($site_url); ?>/arrangementer/" class="link link-hover">Arrangementer</a>
            <a href="<?php echo esc_url($site_url); ?>/verktoy/" class="link link-hover">Verktøy</a>
        </div>
        
        <!-- Temagrupper -->
        <div>
            <span class="footer-title text-lg font-bold mb-4">Temagrupper</span>
            <a href="<?php echo esc_url($site_url); ?>/temagrupper/modellkvalitet/" class="link link-hover">Modellkvalitet</a>
            <a href="<?php echo esc_url($site_url); ?>/temagrupper/byggesksbim/" class="link link-hover">ByggesaksBIM</a>
            <a href="<?php echo esc_url($site_url); ?>/temagrupper/prosjektbim/" class="link link-hover">ProsjektBIM</a>
            <a href="<?php echo esc_url($site_url); ?>/temagrupper/eiendomsbim/" class="link link-hover">EiendomsBIM</a>
            <a href="<?php echo esc_url($site_url); ?>/temagrupper/miljobim/" class="link link-hover">MiljøBIM</a>
            <a href="<?php echo esc_url($site_url); ?>/temagrupper/sirkbim/" class="link link-hover">SirkBIM</a>
        </div>
        
        <!-- Legal -->
        <div>
            <span class="footer-title text-lg font-bold mb-4">Legal</span>
            <a href="<?php echo esc_url($site_url); ?>/personvernerklaering/" class="link link-hover">Personvern</a>
            <a href="<?php echo esc_url($site_url); ?>/vilkar/" class="link link-hover">Vilkår</a>
            <a href="<?php echo esc_url($site_url); ?>/kontakt/" class="link link-hover">Kontakt</a>
        </div>
        
    </div>
    
    <!-- Bottom Bar -->
    <div class="border-t border-neutral-focus">
        <div class="footer footer-center p-6 bg-neutral text-neutral-content max-w-7xl mx-auto">
            <div class="flex gap-8 flex-col md:flex-row justify-between w-full items-center">
                <p class="text-sm">
                    © <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. Alle rettigheter forbeholdt.
                </p>
                <p class="text-sm">
                    Kontakt: <a href="mailto:<?php echo esc_attr($footer_email); ?>" class="link link-primary"><?php echo esc_html($footer_email); ?></a>
                </p>
            </div>
        </div>
    </div>
</footer>

<style>
    .link-primary {
        color: #FF8B5E;
    }
    
    .link-primary:hover {
        color: #E67A4E;
    }
</style>
