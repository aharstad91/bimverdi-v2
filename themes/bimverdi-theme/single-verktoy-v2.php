<?php
/**
 * Single Verktøy Template v2
 * 
 * Displays detailed information about a single tool/software.
 * Clean, minimal styling following UI Contract v1.
 * 
 * @package BimVerdi_Theme
 */

get_header();

if (have_posts()) : while (have_posts()) : the_post();

// Get ACF fields
$eier_id = get_field('eier_leverandor');
$eier = $eier_id ? get_post($eier_id) : null;
$verktoy_navn = get_field('verktoy_navn');
$detaljert_beskrivelse = get_field('detaljert_beskrivelse');
$lenke = get_field('verktoy_lenke');
$pris = get_field('verktoy_pris');
$logo_id = get_field('verktoy_logo');
$logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';

// Get new ACF fields from form
$formaalstema = get_field('formaalstema');
$bim_kompatibilitet = get_field('bim_kompatibilitet');
$type_ressurs = get_field('type_ressurs');
$type_teknologi = get_field('type_teknologi');
$anvendelser = get_field('anvendelser');

// Helper function for readable labels
function bimverdi_v2_readable_label($value) {
    $labels = [
        // Formålstema
        'ByggesaksBIM' => 'ByggesaksBIM',
        'ProsjektBIM' => 'ProsjektBIM',
        'EiendomsBIM' => 'EiendomsBIM',
        'MiljøBIM' => 'MiljøBIM',
        'SirkBIM' => 'SirkBIM',
        'Opplæring' => 'Opplæring',
        // BIM-kompatibilitet
        'IFC_kompatibel' => 'IFC-kompatibel (full støtte)',
        'IFC_eksport' => 'IFC-eksport',
        'IFC_import' => 'IFC-import',
        'IFC_kobling' => 'IFC-kobling (via plugin)',
        'planlagt' => 'Planlagt IFC-støtte',
        'vet_ikke' => 'Ikke oppgitt',
        // Type ressurs
        'Programvare' => 'Programvare',
        'Standard' => 'Standard',
        'Metodikk' => 'Metodikk',
        'Veileder' => 'Veileder',
        'Nettside' => 'Nettside',
        'Digital_tjeneste' => 'Digital tjeneste',
        // Type teknologi
        'Bruker_KI' => 'Bruker KI',
        'Ikke_KI' => 'Ikke KI',
        'Under_avklaring' => 'Under avklaring',
        // Anvendelser
        'Design_modellering' => 'Design og modellering',
        'GIS_kart' => 'GIS og kart',
        'Digitalt_innhold' => 'Digitalt innhold',
        'Prosjektledelse' => 'Prosjektledelse',
        'Kostnadsanalyse' => 'Kostnadsanalyse',
        'Simulering' => 'Simulering og analyse',
        'Fasilitetsstyring' => 'Fasilitetsstyring (FDVU)',
        'Feltarbeid' => 'Feltarbeid',
        'Baerekraft' => 'Bærekraft',
        'Kommunikasjon' => 'Kommunikasjon',
        'Logistikk' => 'Logistikk',
        'Kompetanse' => 'Kompetanse',
    ];
    return $labels[$value] ?? str_replace('_', ' ', $value);
}
?>

<div class="min-h-screen bg-[#F7F5EF]">
    
    <!-- Breadcrumb -->
    <div class="bg-white border-b border-[#E5E0D8]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="flex items-center text-sm text-[#5A5A5A]">
                <a href="<?php echo home_url(); ?>" class="hover:text-[#1A1A1A]">Hjem</a>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mx-2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                <a href="<?php echo home_url('/verktoy/'); ?>" class="hover:text-[#1A1A1A]">Verktøykatalog</a>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mx-2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                <span class="text-[#1A1A1A] font-medium"><?php the_title(); ?></span>
            </nav>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="bg-white border-b border-[#E5E0D8]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <?php if ($logo_url): ?>
                    <div class="w-24 h-24 bg-[#F7F5EF] rounded-lg overflow-hidden flex items-center justify-center p-3">
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php the_title(); ?>" class="max-w-full max-h-full object-contain">
                    </div>
                    <?php else: ?>
                    <div class="w-24 h-24 bg-[#F7F5EF] rounded-lg flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Info -->
                <div class="flex-1">
                    <!-- Tags -->
                    <div class="flex flex-wrap gap-2 mb-3">
                        <?php if ($formaalstema): ?>
                        <span class="text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-2 py-1 rounded">
                            <?php echo esc_html(bimverdi_v2_readable_label($formaalstema)); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($type_ressurs): ?>
                        <span class="text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-2 py-1 rounded">
                            <?php echo esc_html(bimverdi_v2_readable_label($type_ressurs)); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($type_teknologi === 'Bruker_KI'): ?>
                        <span class="text-xs font-medium bg-[#1A1A1A] text-white px-2 py-1 rounded">
                            KI-drevet
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="text-3xl font-bold text-[#1A1A1A] mb-2"><?php the_title(); ?></h1>
                    
                    <?php if ($eier): ?>
                    <p class="text-[#5A5A5A] mb-4">
                        Levert av 
                        <a href="<?php echo get_permalink($eier_id); ?>" class="font-medium text-[#1A1A1A] hover:underline">
                            <?php echo esc_html($eier->post_title); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <!-- Action buttons -->
                    <div class="flex flex-wrap gap-3">
                        <?php if (!empty($lenke)): ?>
                        <a href="<?php echo esc_url($lenke); ?>" 
                           target="_blank" 
                           rel="noopener"
                           class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-lg text-white bg-[#1A1A1A] hover:bg-[#333] transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                            Besøk nettside
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="ml-2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($eier): ?>
                        <a href="<?php echo get_permalink($eier_id); ?>" 
                           class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-lg text-[#1A1A1A] bg-transparent border border-[#E5E0D8] hover:bg-[#F2F0EB] transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            Kontakt leverandør
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Description -->
                <div class="bg-white rounded-lg border border-[#E5E0D8] p-6">
                    <h2 class="text-lg font-bold text-[#1A1A1A] mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        Om verktøyet
                    </h2>
                    <div class="prose prose-sm max-w-none text-[#5A5A5A]">
                        <?php if (!empty($detaljert_beskrivelse)): ?>
                            <?php echo wpautop($detaljert_beskrivelse); ?>
                        <?php else: ?>
                            <p><?php the_excerpt(); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bruksområder (Use Cases) -->
                <?php if (!empty($anvendelser) && is_array($anvendelser)): ?>
                <div class="bg-white rounded-lg border border-[#E5E0D8] p-6">
                    <h2 class="text-lg font-bold text-[#1A1A1A] mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        Bruksområder
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($anvendelser as $anvendelse): ?>
                        <span class="inline-block text-sm font-medium bg-[#F2F0EB] text-[#5A5A5A] px-3 py-1.5 rounded-lg">
                            <?php echo esc_html(bimverdi_v2_readable_label($anvendelse)); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Technical Details -->
                <div class="bg-white rounded-lg border border-[#E5E0D8] p-6">
                    <h2 class="text-lg font-bold text-[#1A1A1A] mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        Tekniske detaljer
                    </h2>
                    
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php if ($bim_kompatibilitet): ?>
                        <div class="flex items-start gap-3 p-3 bg-[#F7F5EF] rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A] flex-shrink-0 mt-0.5"><path d="m21 16-4 4-4-4"/><path d="M17 20V4"/><path d="m3 8 4-4 4 4"/><path d="M7 4v16"/></svg>
                            <div>
                                <dt class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide">BIM-kompatibilitet</dt>
                                <dd class="text-sm font-medium text-[#1A1A1A] mt-0.5"><?php echo esc_html(bimverdi_v2_readable_label($bim_kompatibilitet)); ?></dd>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($type_teknologi): ?>
                        <div class="flex items-start gap-3 p-3 bg-[#F7F5EF] rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A] flex-shrink-0 mt-0.5"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg>
                            <div>
                                <dt class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide">Teknologi</dt>
                                <dd class="text-sm font-medium text-[#1A1A1A] mt-0.5"><?php echo esc_html(bimverdi_v2_readable_label($type_teknologi)); ?></dd>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($type_ressurs): ?>
                        <div class="flex items-start gap-3 p-3 bg-[#F7F5EF] rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A] flex-shrink-0 mt-0.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                            <div>
                                <dt class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide">Ressurstype</dt>
                                <dd class="text-sm font-medium text-[#1A1A1A] mt-0.5"><?php echo esc_html(bimverdi_v2_readable_label($type_ressurs)); ?></dd>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($formaalstema): ?>
                        <div class="flex items-start gap-3 p-3 bg-[#F7F5EF] rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A] flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="6"></circle><circle cx="12" cy="12" r="2"></circle></svg>
                            <div>
                                <dt class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide">Formålstema</dt>
                                <dd class="text-sm font-medium text-[#1A1A1A] mt-0.5"><?php echo esc_html(bimverdi_v2_readable_label($formaalstema)); ?></dd>
                            </div>
                        </div>
                        <?php endif; ?>
                    </dl>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Quick Info -->
                <div class="bg-white rounded-lg border border-[#E5E0D8] p-6">
                    <h3 class="text-sm font-bold text-[#1A1A1A] mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        Rask info
                    </h3>
                    
                    <dl class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-[#E5E0D8]">
                            <dt class="text-sm text-[#5A5A5A]">Status</dt>
                            <dd>
                                <span class="inline-block text-xs font-medium bg-green-100 text-green-800 px-2 py-1 rounded">
                                    Aktiv
                                </span>
                            </dd>
                        </div>
                        
                        <?php if ($type_ressurs): ?>
                        <div class="flex justify-between items-center py-2 border-b border-[#E5E0D8]">
                            <dt class="text-sm text-[#5A5A5A]">Type</dt>
                            <dd class="text-sm font-medium text-[#1A1A1A]"><?php echo esc_html(bimverdi_v2_readable_label($type_ressurs)); ?></dd>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($bim_kompatibilitet): ?>
                        <div class="flex justify-between items-center py-2 border-b border-[#E5E0D8]">
                            <dt class="text-sm text-[#5A5A5A]">IFC-støtte</dt>
                            <dd class="text-sm font-medium text-[#1A1A1A] text-right"><?php echo esc_html(bimverdi_v2_readable_label($bim_kompatibilitet)); ?></dd>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($pris)): ?>
                        <div class="flex justify-between items-center py-2 border-b border-[#E5E0D8]">
                            <dt class="text-sm text-[#5A5A5A]">Pris</dt>
                            <dd class="text-sm font-medium text-[#1A1A1A]"><?php echo esc_html($pris); ?></dd>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($lenke)): ?>
                        <div class="py-2">
                            <dt class="text-sm text-[#5A5A5A] mb-1">Nettside</dt>
                            <dd>
                                <a href="<?php echo esc_url($lenke); ?>" 
                                   target="_blank" 
                                   rel="noopener"
                                   class="text-sm text-[#1A1A1A] hover:underline break-all inline-flex items-center gap-1">
                                    <?php echo esc_html(parse_url($lenke, PHP_URL_HOST)); ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                </a>
                            </dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </div>

                <!-- Leverandør Card -->
                <?php if ($eier): ?>
                <div class="bg-[#F7F5EF] rounded-lg border border-[#E5E0D8] p-6">
                    <h3 class="text-sm font-bold text-[#1A1A1A] mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg>
                        Leverandør
                    </h3>
                    
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center font-bold text-[#5A5A5A] text-lg">
                            <?php echo esc_html(substr($eier->post_title, 0, 2)); ?>
                        </div>
                        <div>
                            <p class="font-medium text-[#1A1A1A]"><?php echo esc_html($eier->post_title); ?></p>
                            <p class="text-xs text-[#5A5A5A]">BIM Verdi-medlem</p>
                        </div>
                    </div>
                    
                    <a href="<?php echo get_permalink($eier_id); ?>" 
                       class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg text-white bg-[#1A1A1A] hover:bg-[#333] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        Kontakt leverandør
                    </a>
                </div>
                <?php endif; ?>

                <!-- Back button -->
                <a href="<?php echo home_url('/verktoy/'); ?>" 
                   class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg text-[#1A1A1A] bg-transparent border border-[#E5E0D8] hover:bg-[#F2F0EB] transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Tilbake til katalog
                </a>

            </div>

        </div>
    </div>
</div>

<?php 
endwhile; 
endif; 

get_footer();
?>
