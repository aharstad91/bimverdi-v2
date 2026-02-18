</div><!-- #content -->

<!-- Footer -->
<footer class="bg-[#FAFAF9] border-t border-[#E7E5E4]">
    <div class="max-w-6xl mx-auto px-4 md:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-12">

            <!-- Newsletter Column -->
            <div class="bv-footer-newsletter">
                <h3 class="text-lg font-bold text-[#111827] mb-4">Hold deg oppdatert</h3>
                <p class="text-[#5A5A5A] text-sm mb-4">
                    Motta nyheter og invitasjoner til våre arrangement.
                </p>
                <?php
                if (function_exists('gravity_form')) {
                    $newsletter_form_id = get_option('bimverdi_newsletter_gf_created', 17);
                    gravity_form($newsletter_form_id, false, false, false, null, true);
                }
                ?>
            </div>

            <!-- Organisering Column -->
            <div>
                <h3 class="text-lg font-bold text-[#111827] mb-4">Organisering</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="<?php echo esc_url(home_url('/om-oss/')); ?>" class="text-[#57534E] hover:text-[#111827] transition-colors">Om oss</a></li>
                    <li><a href="<?php echo esc_url(home_url('/deltakere/')); ?>" class="text-[#57534E] hover:text-[#111827] transition-colors">Deltakere</a></li>
                    <li><a href="<?php echo esc_url(home_url('/styringsgruppe/')); ?>" class="text-[#57534E] hover:text-[#111827] transition-colors">Styringsgruppe</a></li>
                    <li><a href="<?php echo esc_url(home_url('/vedtekter/')); ?>" class="text-[#57534E] hover:text-[#111827] transition-colors">Vedtekter</a></li>
                </ul>
            </div>

            <!-- Ressurser Column -->
            <div>
                <h3 class="text-lg font-bold text-[#111827] mb-4">Ressurser</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="<?php echo esc_url(home_url('/artikler/')); ?>" class="text-[#57534E] hover:text-[#111827] transition-colors">Artikler</a></li>
                    <li><a href="<?php echo esc_url(home_url('/verktoy/')); ?>" class="text-[#57534E] hover:text-[#111827] transition-colors">Verktøy</a></li>
                    <li><a href="<?php echo esc_url(home_url('/kunnskapskilder/')); ?>" class="text-[#57534E] hover:text-[#111827] transition-colors">Kunnskapskilder</a></li>
                    <li><a href="<?php echo esc_url(home_url('/temagrupper/')); ?>" class="text-[#57534E] hover:text-[#111827] transition-colors">Temagrupper</a></li>
                </ul>
            </div>

        </div>

        <!-- Bottom Bar -->
        <div class="border-t border-[#E7E5E4] mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-sm text-[#57534E]">&copy; <?php echo date('Y'); ?> BIM Verdi. Alle rettigheter reservert.</p>

            <!-- Social Icons -->
            <div class="flex items-center gap-4">
                <a href="https://www.linkedin.com/company/bim-verdi/" target="_blank" rel="noopener noreferrer"
                   class="text-[#57534E] hover:text-[#111827] transition-colors" aria-label="LinkedIn">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                </a>
                <a href="https://twitter.com/bimverdi" target="_blank" rel="noopener noreferrer"
                   class="text-[#57534E] hover:text-[#111827] transition-colors" aria-label="Twitter/X">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                </a>
                <a href="https://www.facebook.com/bimverdi" target="_blank" rel="noopener noreferrer"
                   class="text-[#57534E] hover:text-[#111827] transition-colors" aria-label="Facebook">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </a>
            </div>
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
