<?php
/**
 * 404 Page Template
 *
 * @package BimVerdi_Theme
 */

get_header();
?>

<main class="min-h-[80vh] flex items-center justify-center px-6 py-24">
    <div class="text-center max-w-md">
        <p class="text-6xl font-bold text-[#FF8B5E] mb-4">404</p>
        <h1 class="text-2xl font-semibold text-[#1A1A1A] mb-3">Siden finnes ikke</h1>
        <p class="text-[#5A5A5A] mb-8">Beklager, vi fant ikke siden du leter etter. Den kan ha blitt flyttet eller slettet.</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <?php
            require_once get_template_directory() . '/parts/components/button.php';
            bimverdi_button([
                'text' => 'Til forsiden',
                'variant' => 'default',
                'icon' => 'arrow-left',
                'href' => home_url('/'),
            ]);
            bimverdi_button([
                'text' => 'Kontakt oss',
                'variant' => 'outline',
                'icon' => 'mail',
                'href' => 'mailto:post@bimverdi.no',
            ]);
            ?>
        </div>
    </div>
</main>

<?php get_footer(); ?>
