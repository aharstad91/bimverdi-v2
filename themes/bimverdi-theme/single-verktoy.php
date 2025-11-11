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

<div class="min-h-screen bg-bim-beige-100">
    
    <!-- Hero Header -->
    <div class="bg-gradient-to-r from-purple-600 to-orange-600 text-white py-12">
        <div class="container mx-auto px-4 max-w-6xl">
            <!-- Breadcrumbs -->
            <div class="mb-6">
                <nav class="text-sm text-white/80">
                    <a href="<?php echo home_url(); ?>" class="hover:text-white">Hjem</a>
                    <span class="mx-2">/</span>
                    <a href="<?php echo home_url('/verktoy'); ?>" class="hover:text-white">Verktøy</a>
                    <span class="mx-2">/</span>
                    <span class="text-white"><?php the_title(); ?></span>
                </nav>
            </div>
            
            <!-- Hero Content -->
            <div class="flex items-start gap-6">
                <?php if ($logo_url): ?>
                <div class="w-32 h-32 flex-shrink-0 bg-white rounded-lg overflow-hidden flex items-center justify-center p-4">
                    <img src="<?php echo esc_url($logo_url); ?>" 
                         alt="<?php the_title(); ?>" 
                         class="w-full h-full object-contain">
                </div>
                <?php endif; ?>
                
                <div class="flex-1">
                    <!-- Categories -->
                    <?php if (!empty($kategori_terms)): ?>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <?php foreach ($kategori_terms as $term): ?>
                        <span class="bg-white/20 px-3 py-1 rounded-full text-sm font-bold">
                            <?php echo esc_html($term->name); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <h1 class="text-4xl md:text-5xl font-bold mb-3">
                        <?php the_title(); ?>
                    </h1>
                    
                    <!-- Company -->
                    <?php if ($eier): ?>
                    <p class="text-xl text-white/90">
                        Levert av <a href="<?php echo get_permalink($eier_id); ?>" 
                           class="text-white hover:underline font-semibold">
                            <?php echo esc_html($eier->post_title); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container mx-auto px-4 max-w-6xl py-12">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Description Card -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">📋 Om verktøyet</h2>
                    
                    <?php if (!empty($detaljert_beskrivelse)): ?>
                    <div class="prose max-w-none text-gray-700">
                        <?php echo wpautop($detaljert_beskrivelse); ?>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-600"><?php the_excerpt(); ?></p>
                    <?php endif; ?>
                </div>

                <!-- CTA Buttons -->
                <div class="flex flex-wrap gap-4">
                    <?php if (!empty($lenke)): ?>
                    <a href="<?php echo esc_url($lenke); ?>" 
                       target="_blank" 
                       rel="noopener" 
                       class="px-8 py-4 bg-purple-600 text-white rounded-lg font-bold hover:bg-purple-700 transition-colors text-lg flex items-center gap-2">
                        🌐 Besøk nettside
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($eier): ?>
                    <a href="<?php echo get_permalink($eier_id); ?>" 
                       class="px-8 py-4 bg-orange-600 text-white rounded-lg font-bold hover:bg-orange-700 transition-colors text-lg flex items-center gap-2">
                        📧 Kontakt leverandør
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Features Section (demo data) -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">✨ Funksjoner</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">🔷</span>
                            <div>
                                <h3 class="font-bold text-gray-900">BIM-integrasjon</h3>
                                <p class="text-sm text-gray-600">Full støtte for IFC og native formater</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">☁️</span>
                            <div>
                                <h3 class="font-bold text-gray-900">Cloud-basert</h3>
                                <p class="text-sm text-gray-600">Samarbeid i sanntid fra hvor som helst</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">📊</span>
                            <div>
                                <h3 class="font-bold text-gray-900">Rapportering</h3>
                                <p class="text-sm text-gray-600">Automatisk generering av rapporter</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">🔗</span>
                            <div>
                                <h3 class="font-bold text-gray-900">API-tilgang</h3>
                                <p class="text-sm text-gray-600">Integrer med andre systemer</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Related Concepts (demo - kobling til semantikk) -->
                <div class="bg-gradient-to-r from-purple-50 to-orange-50 rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">🔗 Relaterte Begreper</h2>
                    <p class="text-gray-700 mb-6">Dette verktøyet støtter følgende standarder og krav:</p>
                    <div class="flex flex-wrap gap-3">
                        <a href="<?php echo home_url('/begrep-tek17/'); ?>" class="bg-white px-4 py-2 rounded-lg shadow hover:shadow-lg transition-all">
                            <span class="font-bold text-purple-600">TEK17</span>
                        </a>
                        <a href="#" class="bg-white px-4 py-2 rounded-lg shadow hover:shadow-lg transition-all">
                            <span class="font-bold text-blue-600">IFC</span>
                        </a>
                        <a href="#" class="bg-white px-4 py-2 rounded-lg shadow hover:shadow-lg transition-all">
                            <span class="font-bold text-green-600">BIM-koordinering</span>
                        </a>
                        <a href="#" class="bg-white px-4 py-2 rounded-lg shadow hover:shadow-lg transition-all">
                            <span class="font-bold text-orange-600">EPD-data</span>
                        </a>
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
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">
                            ℹ️ Rask info
                        </h3>
                        
                        <dl class="space-y-4">
                            
                            <!-- Verktøynavn -->
                            <?php if (!empty($verktoy_navn)): ?>
                            <div class="pb-4 border-b border-gray-200">
                                <dt class="text-sm font-semibold text-gray-600 mb-1">Verktøynavn</dt>
                                <dd class="text-gray-900 font-semibold"><?php echo esc_html($verktoy_navn); ?></dd>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Price -->
                            <?php if (!empty($pris)): ?>
                            <div class="pb-4 border-b border-gray-200">
                                <dt class="text-sm font-semibold text-gray-600 mb-1">💰 Pris</dt>
                                <dd class="text-gray-900 font-semibold"><?php echo esc_html($pris); ?></dd>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Status -->
                            <div class="pb-4 border-b border-gray-200">
                                <dt class="text-sm font-semibold text-gray-600 mb-1">Status</dt>
                                <dd>
                                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-bold">
                                        <?php echo get_post_status() === 'publish' ? '✓ Aktiv' : 'Inaktiv'; ?>
                                    </span>
                                </dd>
                            </div>
                            
                            <!-- Users (demo) -->
                            <div class="pb-4 border-b border-gray-200">
                                <dt class="text-sm font-semibold text-gray-600 mb-1">👥 Medlemmer som bruker</dt>
                                <dd class="text-2xl font-bold text-purple-600">45</dd>
                                <dd class="text-xs text-gray-500">aktive brukere</dd>
                            </div>
                            
                            <!-- Link -->
                            <?php if (!empty($lenke)): ?>
                            <div>
                                <dt class="text-sm font-semibold text-gray-600 mb-2">🌐 Nettside</dt>
                                <dd>
                                    <a href="<?php echo esc_url($lenke); ?>" 
                                       target="_blank" 
                                       rel="noopener"
                                       class="text-purple-600 hover:underline text-sm break-all">
                                        <?php echo esc_html(parse_url($lenke, PHP_URL_HOST)); ?>
                                    </a>
                                </dd>
                            </div>
                            <?php endif; ?>
                        </dl>
                    </div>

                    <!-- Contact Card -->
                    <?php if ($eier): ?>
                    <div class="bg-gradient-to-br from-orange-500 to-red-500 text-white rounded-lg shadow-lg p-6">
                        <h3 class="text-xl font-bold mb-3">
                            📞 Kontakt leverandør
                        </h3>
                        <p class="mb-4 text-sm opacity-90">
                            Vil du vite mer om <?php the_title(); ?>? Ta kontakt med leverandøren.
                        </p>
                        <a href="<?php echo get_permalink($eier_id); ?>" 
                           class="block w-full px-6 py-3 bg-white text-orange-600 rounded-lg font-bold hover:bg-gray-100 transition-colors text-center">
                            Kontakt <?php echo esc_html($eier->post_title); ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Back to Catalog -->
                    <a href="<?php echo home_url('/verktoy'); ?>" 
                       class="block w-full px-6 py-3 bg-gray-100 text-gray-900 rounded-lg font-semibold hover:bg-gray-200 transition-colors text-center">
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
