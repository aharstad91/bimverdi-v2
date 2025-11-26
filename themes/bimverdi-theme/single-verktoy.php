<?php
/**
 * Single Verktøy Template
 * 
 * Displays detailed information about a single tool/software
 * Uses Web Awesome components for modern UI
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

$kategori_terms = wp_get_post_terms(get_the_ID(), 'verktoykategori');

// Helper function for readable labels
function bimverdi_readable_label($value) {
    $labels = [
        // Formålstema
        'ByggesaksBIM' => 'ByggesaksBIM',
        'ProsjektBIM' => 'ProsjektBIM',
        'EiendomsBIM' => 'EiendomsBIM',
        'MiljøBIM' => 'MiljøBIM',
        'SirkBIM' => 'SirkBIM',
        'Opplæring' => 'Opplæring',
        // BIM-kompatibilitet
        'IFC_kompatibel' => 'IFC-kompatibel (fullt støtte)',
        'IFC_eksport' => 'IFC-eksport',
        'IFC_import' => 'IFC-import',
        'IFC_kobling' => 'IFC-kobling (via plugin)',
        'Planlagt_IFC' => 'Planlagt IFC-støtte',
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
        'Simulering_analyse' => 'Simulering og analyse',
        'FDVU' => 'Fasilitetsstyring (FDVU)',
        'Feltarbeid' => 'Feltarbeid',
        'Bærekraft' => 'Bærekraft',
        'Kommunikasjon' => 'Kommunikasjon',
        'Logistikk' => 'Logistikk',
        'Kompetanse' => 'Kompetanse',
    ];
    return $labels[$value] ?? str_replace('_', ' ', $value);
}

// Get icon for tema
function bimverdi_tema_icon($tema) {
    $icons = [
        'ByggesaksBIM' => 'building',
        'ProsjektBIM' => 'diagram-project',
        'EiendomsBIM' => 'house-building',
        'MiljøBIM' => 'leaf',
        'SirkBIM' => 'recycle',
        'Opplæring' => 'graduation-cap',
    ];
    return $icons[$tema] ?? 'cube';
}

// Get variant color for badges
function bimverdi_tema_variant($tema) {
    $variants = [
        'ByggesaksBIM' => 'brand',
        'ProsjektBIM' => 'success',
        'EiendomsBIM' => 'warning',
        'MiljøBIM' => 'success',
        'SirkBIM' => 'brand',
        'Opplæring' => 'neutral',
    ];
    return $variants[$tema] ?? 'neutral';
}
?>

<div class="min-h-screen bg-gradient-to-b from-gray-50 to-white">
    
    <!-- Breadcrumbs -->
    <div class="container mx-auto px-4 max-w-6xl pt-6">
        <wa-breadcrumb>
            <wa-breadcrumb-item href="<?php echo home_url(); ?>">
                <wa-icon slot="prefix" name="house" library="fa"></wa-icon>
                Hjem
            </wa-breadcrumb-item>
            <wa-breadcrumb-item href="<?php echo home_url('/verktoy'); ?>">Verktøykatalog</wa-breadcrumb-item>
            <wa-breadcrumb-item><?php the_title(); ?></wa-breadcrumb-item>
        </wa-breadcrumb>
    </div>

    <!-- Hero Section -->
    <div class="container mx-auto px-4 max-w-6xl py-8">
        <wa-card class="overflow-hidden">
            <div class="flex flex-col md:flex-row gap-6 p-6 md:p-8">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <?php if ($logo_url): ?>
                    <wa-avatar 
                        image="<?php echo esc_url($logo_url); ?>" 
                        label="<?php the_title(); ?>"
                        style="--size: 8rem;">
                    </wa-avatar>
                    <?php else: ?>
                    <wa-avatar 
                        initials="<?php echo esc_attr(substr(get_the_title(), 0, 2)); ?>"
                        style="--size: 8rem; font-size: 2rem;">
                    </wa-avatar>
                    <?php endif; ?>
                </div>
                
                <!-- Info -->
                <div class="flex-1 space-y-4">
                    <!-- Badges row -->
                    <div class="flex flex-wrap gap-2">
                        <?php if ($formaalstema): ?>
                        <wa-tag variant="<?php echo bimverdi_tema_variant($formaalstema); ?>" size="medium">
                            <wa-icon slot="prefix" name="<?php echo bimverdi_tema_icon($formaalstema); ?>" library="fa"></wa-icon>
                            <?php echo esc_html(bimverdi_readable_label($formaalstema)); ?>
                        </wa-tag>
                        <?php endif; ?>
                        
                        <?php if ($type_ressurs): ?>
                        <wa-tag variant="neutral" size="medium">
                            <?php echo esc_html(bimverdi_readable_label($type_ressurs)); ?>
                        </wa-tag>
                        <?php endif; ?>
                        
                        <?php if ($type_teknologi === 'Bruker_KI'): ?>
                        <wa-tag variant="brand" size="medium">
                            <wa-icon slot="prefix" name="microchip" library="fa"></wa-icon>
                            KI-drevet
                        </wa-tag>
                        <?php endif; ?>
                        
                        <?php if (!empty($kategori_terms)): ?>
                            <?php foreach ($kategori_terms as $term): ?>
                            <wa-tag variant="neutral" size="medium"><?php echo esc_html($term->name); ?></wa-tag>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900">
                        <?php the_title(); ?>
                    </h1>
                    
                    <?php if ($eier): ?>
                    <p class="text-lg text-gray-600">
                        Levert av 
                        <a href="<?php echo get_permalink($eier_id); ?>" class="text-orange-600 hover:underline font-semibold">
                            <?php echo esc_html($eier->post_title); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <!-- Action buttons -->
                    <div class="flex flex-wrap gap-3 pt-2">
                        <?php if (!empty($lenke)): ?>
                        <wa-button variant="brand" size="large" href="<?php echo esc_url($lenke); ?>" target="_blank">
                            <wa-icon slot="prefix" name="globe" library="fa"></wa-icon>
                            Besøk nettside
                            <wa-icon slot="suffix" name="arrow-up-right-from-square" library="fa"></wa-icon>
                        </wa-button>
                        <?php endif; ?>
                        
                        <?php if ($eier): ?>
                        <wa-button variant="neutral" size="large" outline href="<?php echo get_permalink($eier_id); ?>">
                            <wa-icon slot="prefix" name="envelope" library="fa"></wa-icon>
                            Kontakt leverandør
                        </wa-button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </wa-card>
    </div>

    <div class="container mx-auto px-4 max-w-6xl pb-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Description -->
                <wa-card>
                    <div slot="header" class="flex items-center gap-2">
                        <wa-icon name="file-lines" library="fa" style="font-size: 1.25rem;"></wa-icon>
                        <strong>Om verktøyet</strong>
                    </div>
                    <div class="prose max-w-none text-gray-700">
                        <?php if (!empty($detaljert_beskrivelse)): ?>
                            <?php echo wpautop($detaljert_beskrivelse); ?>
                        <?php else: ?>
                            <p><?php the_excerpt(); ?></p>
                        <?php endif; ?>
                    </div>
                </wa-card>

                <!-- Anvendelser (Use Cases) -->
                <?php if (!empty($anvendelser) && is_array($anvendelser)): ?>
                <wa-card>
                    <div slot="header" class="flex items-center gap-2">
                        <wa-icon name="layer-group" library="fa" style="font-size: 1.25rem;"></wa-icon>
                        <strong>Bruksområder</strong>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($anvendelser as $anvendelse): ?>
                        <wa-tag variant="brand" size="medium">
                            <?php echo esc_html(bimverdi_readable_label($anvendelse)); ?>
                        </wa-tag>
                        <?php endforeach; ?>
                    </div>
                </wa-card>
                <?php endif; ?>

                <!-- Technical Details -->
                <wa-card>
                    <div slot="header" class="flex items-center gap-2">
                        <wa-icon name="gears" library="fa" style="font-size: 1.25rem;"></wa-icon>
                        <strong>Tekniske detaljer</strong>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- BIM Compatibility -->
                        <?php if ($bim_kompatibilitet): ?>
                        <div class="flex items-start gap-3">
                            <wa-icon name="cube" library="fa" style="font-size: 1.5rem; color: var(--wa-color-brand-600);"></wa-icon>
                            <div>
                                <h4 class="font-semibold text-gray-900">BIM-kompatibilitet</h4>
                                <p class="text-sm text-gray-600"><?php echo esc_html(bimverdi_readable_label($bim_kompatibilitet)); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Type Technology -->
                        <?php if ($type_teknologi): ?>
                        <div class="flex items-start gap-3">
                            <wa-icon name="microchip" library="fa" style="font-size: 1.5rem; color: var(--wa-color-brand-600);"></wa-icon>
                            <div>
                                <h4 class="font-semibold text-gray-900">Teknologi</h4>
                                <p class="text-sm text-gray-600"><?php echo esc_html(bimverdi_readable_label($type_teknologi)); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Type Resource -->
                        <?php if ($type_ressurs): ?>
                        <div class="flex items-start gap-3">
                            <wa-icon name="box" library="fa" style="font-size: 1.5rem; color: var(--wa-color-brand-600);"></wa-icon>
                            <div>
                                <h4 class="font-semibold text-gray-900">Ressurstype</h4>
                                <p class="text-sm text-gray-600"><?php echo esc_html(bimverdi_readable_label($type_ressurs)); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Tema -->
                        <?php if ($formaalstema): ?>
                        <div class="flex items-start gap-3">
                            <wa-icon name="bullseye" library="fa" style="font-size: 1.5rem; color: var(--wa-color-brand-600);"></wa-icon>
                            <div>
                                <h4 class="font-semibold text-gray-900">Formålstema</h4>
                                <p class="text-sm text-gray-600"><?php echo esc_html(bimverdi_readable_label($formaalstema)); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </wa-card>

                <!-- Related Tools (future) -->
                <wa-card>
                    <div slot="header" class="flex items-center gap-2">
                        <wa-icon name="link" library="fa" style="font-size: 1.25rem;"></wa-icon>
                        <strong>Relaterte standarder</strong>
                    </div>
                    <p class="text-gray-600 mb-4">Dette verktøyet er relevant for følgende standarder og krav:</p>
                    <div class="flex flex-wrap gap-2">
                        <wa-button variant="neutral" size="small" outline href="<?php echo home_url('/begrep-tek-17/'); ?>">
                            TEK17
                        </wa-button>
                        <wa-button variant="neutral" size="small" outline>
                            IFC 4
                        </wa-button>
                        <wa-button variant="neutral" size="small" outline>
                            NS 3451
                        </wa-button>
                        <wa-button variant="neutral" size="small" outline>
                            BIM-koordinering
                        </wa-button>
                    </div>
                </wa-card>

            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Quick Info -->
                <wa-card>
                    <div slot="header" class="flex items-center gap-2">
                        <wa-icon name="circle-info" library="fa" style="font-size: 1.25rem;"></wa-icon>
                        <strong>Rask info</strong>
                    </div>
                    
                    <dl class="space-y-4">
                        <!-- Status -->
                        <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                            <dt class="text-sm text-gray-600">Status</dt>
                            <dd>
                                <wa-badge variant="success">
                                    <wa-icon slot="prefix" name="check" library="fa"></wa-icon>
                                    Aktiv
                                </wa-badge>
                            </dd>
                        </div>
                        
                        <!-- Type -->
                        <?php if ($type_ressurs): ?>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                            <dt class="text-sm text-gray-600">Type</dt>
                            <dd class="font-semibold text-gray-900"><?php echo esc_html(bimverdi_readable_label($type_ressurs)); ?></dd>
                        </div>
                        <?php endif; ?>
                        
                        <!-- BIM -->
                        <?php if ($bim_kompatibilitet): ?>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                            <dt class="text-sm text-gray-600">IFC-støtte</dt>
                            <dd class="font-semibold text-gray-900 text-right text-sm"><?php echo esc_html(bimverdi_readable_label($bim_kompatibilitet)); ?></dd>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Price -->
                        <?php if (!empty($pris)): ?>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                            <dt class="text-sm text-gray-600">Pris</dt>
                            <dd class="font-semibold text-gray-900"><?php echo esc_html($pris); ?></dd>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Website -->
                        <?php if (!empty($lenke)): ?>
                        <div class="pb-3">
                            <dt class="text-sm text-gray-600 mb-1">Nettside</dt>
                            <dd>
                                <a href="<?php echo esc_url($lenke); ?>" 
                                   target="_blank" 
                                   rel="noopener"
                                   class="text-orange-600 hover:underline text-sm break-all inline-flex items-center gap-1">
                                    <?php echo esc_html(parse_url($lenke, PHP_URL_HOST)); ?>
                                    <wa-icon name="arrow-up-right-from-square" library="fa" style="font-size: 0.75rem;"></wa-icon>
                                </a>
                            </dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </wa-card>

                <!-- Contact Card -->
                <?php if ($eier): ?>
                <wa-card class="bg-gradient-to-br from-orange-50 to-orange-100">
                    <div slot="header" class="flex items-center gap-2">
                        <wa-icon name="building" library="fa" style="font-size: 1.25rem;"></wa-icon>
                        <strong>Leverandør</strong>
                    </div>
                    
                    <div class="flex items-center gap-3 mb-4">
                        <wa-avatar initials="<?php echo esc_attr(substr($eier->post_title, 0, 2)); ?>" style="--size: 3rem;"></wa-avatar>
                        <div>
                            <p class="font-semibold text-gray-900"><?php echo esc_html($eier->post_title); ?></p>
                            <p class="text-sm text-gray-600">BIM Verdi-medlem</p>
                        </div>
                    </div>
                    
                    <div slot="footer">
                        <wa-button variant="brand" class="w-full" href="<?php echo get_permalink($eier_id); ?>">
                            <wa-icon slot="prefix" name="envelope" library="fa"></wa-icon>
                            Kontakt leverandør
                        </wa-button>
                    </div>
                </wa-card>
                <?php endif; ?>

                <!-- Back button -->
                <wa-button variant="neutral" outline class="w-full" href="<?php echo home_url('/verktoy'); ?>">
                    <wa-icon slot="prefix" name="arrow-left" library="fa"></wa-icon>
                    Tilbake til katalog
                </wa-button>

            </div>

        </div>
    </div>
</div>

<?php 
endwhile; 
endif; 

get_footer();
?>
