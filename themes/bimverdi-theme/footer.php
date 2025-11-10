</div><!-- #content -->

<footer class="bg-bim-black-900 text-white mt-16">
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            
            <!-- About Column -->
            <div>
                <h3 class="text-lg font-bold mb-4">Om BIM Verdi</h3>
                <p class="text-bim-black-300 text-sm">
                    Norges ledende nettverk for bærekraftig bygg og digitalisering i byggenæringen.
                </p>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h3 class="text-lg font-bold mb-4">Hurtiglenker</h3>
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'footer',
                    'container' => false,
                    'menu_class' => 'space-y-2 text-sm',
                    'fallback_cb' => false,
                ));
                ?>
            </div>
            
            <!-- Contact -->
            <div>
                <h3 class="text-lg font-bold mb-4">Kontakt</h3>
                <div class="text-bim-black-300 text-sm space-y-2">
                    <p>E-post: post@bimverdi.no</p>
                    <p>Telefon: +47 XXX XX XXX</p>
                </div>
            </div>
            
            <!-- Widget Area -->
            <div>
                <?php if (is_active_sidebar('footer-1')) : ?>
                    <?php dynamic_sidebar('footer-1'); ?>
                <?php endif; ?>
            </div>
            
        </div>
        
        <!-- Bottom Bar -->
        <div class="border-t border-bim-black-700 mt-8 pt-8 text-center text-sm text-bim-black-400">
            <p>&copy; <?php echo date('Y'); ?> BIM Verdi. Alle rettigheter reservert.</p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>

<script>
    // Simple mobile menu toggle
    document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
        document.getElementById('mobile-menu')?.classList.toggle('hidden');
    });
</script>

</body>
</html>
