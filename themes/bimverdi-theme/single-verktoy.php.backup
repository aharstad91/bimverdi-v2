<?php
/**
 * Single Verktøy Template
 * 
 * Displays detailed information about a single tool/software
 * 
 * @package BimVerdi_Theme
 */

get_header();

if (have_posts()) : while (have_posts()) : the_post();

// Get ACF fields - use get_field() for ACF fields
$eier_id = get_field('eier_leverandor'); // This is a post_object field returning ID
$eier = $eier_id ? get_post($eier_id) : null;
$verktoy_navn = get_field('verktoy_navn');
$detaljert_beskrivelse = get_field('detaljert_beskrivelse');
$lenke = get_field('verktoy_lenke');
$pris = get_field('verktoy_pris');
$logo_id = get_field('verktoy_logo');
$logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';

// Get all post meta fields to see what else might be available
$all_meta = get_post_meta(get_the_ID());

$kategori_terms = wp_get_post_terms(get_the_ID(), 'verktoykategori');
?>

<div class="min-h-screen bg-bim-beige-100 py-8">
    <div class="container mx-auto px-4 max-w-6xl">

        <!-- Breadcrumbs -->
        <div class="mb-6">
            <nav class="text-sm text-bim-black-600">
                <a href="<?php echo home_url(); ?>" class="hover:text-bim-orange">Hjem</a>
                <span class="mx-2">/</span>
                <a href="<?php echo home_url('/verktoy'); ?>" class="hover:text-bim-orange">Verktøy</a>
                <span class="mx-2">/</span>
                <span class="text-bim-black-900"><?php the_title(); ?></span>
            </nav>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Header Card -->
                <div class="card-hjem">
                    <div class="p-6">
                        
                        <!-- Logo and Title -->
                        <div class="flex items-start gap-6 mb-6">
                            <?php if ($logo_url): ?>
                            <div class="w-24 h-24 flex-shrink-0 bg-bim-beige-200 rounded-lg overflow-hidden flex items-center justify-center">
                                <img src="<?php echo esc_url($logo_url); ?>" 
                                     alt="<?php the_title(); ?>" 
                                     class="w-full h-full object-contain p-2">
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex-grow">
                                <h1 class="text-3xl md:text-4xl font-bold text-bim-black-900 mb-3">
                                    <?php the_title(); ?>
                                </h1>
                                
                                <!-- Categories -->
                                <?php if (!empty($kategori_terms)): ?>
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <?php foreach ($kategori_terms as $term): ?>
                                    <span class="badge badge-hjem-orange">
                                        <?php echo esc_html($term->name); ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Company -->
                                <?php if ($eier): ?>
                                <p class="text-bim-black-700">
                                    <span class="font-semibold">Levert av:</span>
                                    <a href="<?php echo get_permalink($eier_id); ?>" 
                                       class="text-bim-orange hover:underline">
                                        <?php echo esc_html($eier->post_title); ?>
                                    </a>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Description -->
                        <?php if (!empty($detaljert_beskrivelse)): ?>
                        <div class="prose max-w-none text-bim-black-700">
                            <?php echo wpautop($detaljert_beskrivelse); ?>
                        </div>
                        <?php endif; ?>

                        <!-- CTA Buttons -->
                        <div class="flex flex-wrap gap-3 mt-6 pt-6 border-t border-bim-black-200">
                            <?php if (!empty($lenke)): ?>
                            <a href="<?php echo esc_url($lenke); ?>" 
                               target="_blank" 
                               rel="noopener" 
                               class="btn-hjem-primary">
                                🌐 Besøk nettside
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($eier): ?>
                            <a href="<?php echo get_permalink($eier_id); ?>" 
                               class="btn-hjem-outline">
                                📧 Kontakt leverandør
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="sticky top-24 space-y-6">
                    
                    <!-- Quick Info Card (fjernet debug-seksjon) -->
                <?php if (current_user_can('administrator')): ?>
                <div class="card-hjem bg-yellow-50 border-yellow-200">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-bim-black-900 mb-4">
                            � Debug - Tilgjengelige felter (kun for admin)
                        </h3>
                        <div class="text-xs space-y-2 font-mono">
                            <div><strong>ACF Fields:</strong></div>
                            <div>verktoy_navn: <?php echo esc_html($verktoy_navn ?? 'N/A'); ?></div>
                            <div>detaljert_beskrivelse: <?php echo esc_html(substr($detaljert_beskrivelse ?? '', 0, 100)); ?>...</div>
                            <div>verktoy_lenke: <?php echo esc_html($lenke ?? 'N/A'); ?></div>
                            <div>verktoy_pris: <?php echo esc_html($pris ?? 'N/A'); ?></div>
                            <div>eier_leverandor: <?php echo esc_html($eier_id ?? 'N/A'); ?> <?php echo $eier ? '(' . esc_html($eier->post_title) . ')' : ''; ?></div>
                            <div>verktoy_logo: <?php echo esc_html($logo_id ?? 'N/A'); ?></div>
                            
                            <div class="mt-4"><strong>All Post Meta:</strong></div>
                            <?php 
                            if (!empty($all_meta)):
                                foreach ($all_meta as $key => $values): 
                                    if (substr($key, 0, 1) !== '_'): // Skip hidden meta
                            ?>
                            <div><?php echo esc_html($key); ?>: <?php echo esc_html(is_array($values) ? json_encode($values) : $values[0]); ?></div>
                            <?php 
                                    endif;
                                endforeach; 
                            endif;
                            ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="sticky top-24 space-y-6">
                    
                    <!-- Quick Info Card -->
                    <div class="card-hjem">
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-bim-black-900 mb-4">
                                ℹ️ Informasjon
                            </h3>
                            
                            <dl class="space-y-3">
                                
                                <!-- Verktøynavn -->
                                <?php if (!empty($verktoy_navn)): ?>
                                <div>
                                    <dt class="text-sm font-semibold text-bim-black-900">Verktøynavn</dt>
                                    <dd class="text-bim-black-700"><?php echo esc_html($verktoy_navn); ?></dd>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Price -->
                                <?php if (!empty($pris)): ?>
                                <div>
                                    <dt class="text-sm font-semibold text-bim-black-900">Pris</dt>
                                    <dd class="text-bim-black-700"><?php echo esc_html($pris); ?></dd>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Status -->
                                <div>
                                    <dt class="text-sm font-semibold text-bim-black-900">Status</dt>
                                    <dd>
                                        <span class="badge badge-hjem-success">
                                            <?php echo get_post_status() === 'publish' ? 'Aktiv' : 'Inaktiv'; ?>
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Contact Card -->
                    <?php if ($eier): ?>
                    <div class="card-hjem bg-gradient-orange text-white">
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-3">
                                📞 Kontakt leverandør
                            </h3>
                            <p class="mb-4 text-sm opacity-90">
                                Vil du vite mer om <?php the_title(); ?>? Ta kontakt med leverandøren.
                            </p>
                            <a href="<?php echo get_permalink($eier_id); ?>" 
                               class="btn bg-white text-bim-orange hover:bg-bim-beige-100 w-full">
                                Kontakt <?php echo esc_html($eier->post_title); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Back to Catalog -->
                    <a href="<?php echo home_url('/verktoy'); ?>" 
                       class="btn-hjem-outline w-full text-center block">
                        ← Tilbake til katalog
                    </a>

                </div>
            </div>

        </div>

    </div>
</div>

<?php 
endwhile; 
endif; 

get_footer();
?>
