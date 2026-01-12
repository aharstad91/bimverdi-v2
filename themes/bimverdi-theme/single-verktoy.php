<?php
/**
 * Single Verktøy Template v3
 * 
 * Displays detailed information about a single tool/software.
 * Design based on UI Contract v1 - Variant B (Dividers/Whitespace)
 * 
 * @package BimVerdi_Theme
 */

get_header('minside');

if (have_posts()) : while (have_posts()) : the_post();

// Get ACF fields
$eier_id = get_field('eier_leverandor');
$eier = $eier_id ? get_post($eier_id) : null;
$detaljert_beskrivelse = get_field('detaljert_beskrivelse');
$lenke = get_field('verktoy_lenke');
$logo_id = get_field('verktoy_logo');
$logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';

// Get new ACF fields
$formaalstema = get_field('formaalstema');
$bim_kompatibilitet = get_field('bim_kompatibilitet');
$type_ressurs = get_field('type_ressurs');
$type_teknologi = get_field('type_teknologi');
$anvendelser = get_field('anvendelser');
$plattform = get_field('plattform');
$lisensmodell = get_field('lisensmodell');
$versjon = get_field('versjon');
$integrasjoner = get_field('integrasjoner');

// Check if current user can edit this tool
$current_user_id = get_current_user_id();
$can_edit = false;
if ($current_user_id) {
    $user_company_id = get_user_meta($current_user_id, 'bim_verdi_company_id', true);
    if (current_user_can('manage_options')) {
        $can_edit = true;
    } elseif ($user_company_id && $eier_id && $user_company_id == $eier_id) {
        $can_edit = true;
    } elseif (get_post_field('post_author', get_the_ID()) == $current_user_id) {
        $can_edit = true;
    }
}

// Helper function for readable labels
function bimverdi_v3_readable_label($value) {
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
        // Lisensmodell
        'Gratis' => 'Gratis',
        'Freemium' => 'Freemium',
        'Abonnement' => 'Abonnement',
        'Engangskjøp' => 'Engangskjøp',
        'Enterprise' => 'Flytende lisens (Enterprise)',
    ];
    return $labels[$value] ?? str_replace('_', ' ', $value);
}

$tool_updated = get_the_modified_date('d.m.Y');
?>

<main class="min-h-screen bg-[#FAFAF8]">
    <div class="max-w-7xl mx-auto px-6 py-8">

        <!-- Breadcrumb -->
        <nav class="mb-6" aria-label="Brødsmulesti">
            <ol class="flex items-center gap-2 text-sm text-[#5A5A5A]">
                <li>
                    <a href="<?php echo esc_url(home_url('/min-side/mine-verktoy/')); ?>" class="hover:text-[#1A1A1A] transition-colors flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        Mine Verktøy
                    </a>
                </li>
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </li>
                <li class="text-[#1A1A1A] font-medium" aria-current="page"><?php the_title(); ?></li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-6 mb-10">
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-[#1A1A1A] mb-1"><?php the_title(); ?></h1>
                <?php if ($eier): ?>
                <p class="text-[#5A5A5A]"><?php echo esc_html($eier->post_title); ?></p>
                <?php endif; ?>
            </div>
            
            <?php if ($can_edit): ?>
            <div class="flex items-center gap-3 flex-shrink-0">
                <?php bimverdi_button([
                    'text' => 'Rediger',
                    'variant' => 'secondary',
                    'icon' => 'square-pen',
                    'href' => home_url('/min-side/rediger-verktoy/?id=' . get_the_ID())
                ]); ?>
                <?php bimverdi_button([
                    'text' => 'Administrer tilgang',
                    'variant' => 'secondary',
                    'icon' => 'shield-check',
                    'href' => '#'
                ]); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Two-Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            
            <!-- Left Column: Main Content -->
            <div class="lg:col-span-2 space-y-10">
                
                <!-- Oversikt Section -->
                <section>
                    <h2 class="text-lg font-bold text-[#1A1A1A] mb-4">Oversikt</h2>
                    
                    <div class="prose prose-sm max-w-none text-[#5A5A5A] mb-6">
                        <?php if (!empty($detaljert_beskrivelse)): ?>
                            <?php echo wpautop($detaljert_beskrivelse); ?>
                        <?php elseif (has_excerpt()): ?>
                            <p><?php echo get_the_excerpt(); ?></p>
                        <?php else: ?>
                            <p class="italic">Ingen beskrivelse tilgjengelig.</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Tags -->
                    <?php if (!empty($anvendelser) || $formaalstema || $bim_kompatibilitet || $type_teknologi): ?>
                    <div class="flex flex-wrap gap-2 pt-4 border-t border-[#E5E0D8]">
                        <?php if (!empty($anvendelser) && is_array($anvendelser)): ?>
                            <?php foreach ($anvendelser as $anvendelse): ?>
                            <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-3 py-1.5 rounded">
                                <?php echo esc_html(bimverdi_v3_readable_label($anvendelse)); ?>
                            </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if ($bim_kompatibilitet && $bim_kompatibilitet !== 'vet_ikke'): ?>
                        <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-3 py-1.5 rounded">
                            <?php echo esc_html(bimverdi_v3_readable_label($bim_kompatibilitet)); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($formaalstema): ?>
                        <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-3 py-1.5 rounded">
                            <?php echo esc_html(bimverdi_v3_readable_label($formaalstema)); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($type_teknologi && $type_teknologi !== 'Under_avklaring'): ?>
                        <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-3 py-1.5 rounded">
                            <?php echo esc_html(bimverdi_v3_readable_label($type_teknologi)); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </section>

                <!-- Detaljer Section (Definition List) -->
                <section class="border-t border-[#E5E0D8] pt-10">
                    <h2 class="text-lg font-bold text-[#1A1A1A] mb-6">Detaljer</h2>
                    
                    <dl class="space-y-0 divide-y divide-[#E5E0D8]">
                        <!-- Type -->
                        <?php if ($type_ressurs): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Type</dt>
                            <dd class="text-sm">
                                <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-3 py-1.5 rounded">
                                    <?php echo esc_html(bimverdi_v3_readable_label($type_ressurs)); ?>
                                </span>
                            </dd>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Plattform -->
                        <?php if (!empty($plattform)): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Plattform</dt>
                            <dd class="text-sm text-[#1A1A1A]">
                                <?php 
                                if (is_array($plattform)) {
                                    $platform_labels = array_map(function($p) {
                                        return '<span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-3 py-1.5 rounded mr-2 mb-2">' . esc_html($p) . '</span>';
                                    }, $plattform);
                                    echo implode('', $platform_labels);
                                } else {
                                    echo '<span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-3 py-1.5 rounded">' . esc_html($plattform) . '</span>';
                                }
                                ?>
                            </dd>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Lisensmodell -->
                        <?php if ($lisensmodell): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Lisensmodell</dt>
                            <dd class="text-sm text-[#1A1A1A]"><?php echo esc_html(bimverdi_v3_readable_label($lisensmodell)); ?></dd>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Versjon -->
                        <?php if ($versjon): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Versjon</dt>
                            <dd class="text-sm text-[#1A1A1A]"><?php echo esc_html($versjon); ?></dd>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Integrasjoner -->
                        <?php if ($integrasjoner): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Integrasjoner</dt>
                            <dd class="text-sm text-[#1A1A1A]"><?php echo esc_html($integrasjoner); ?></dd>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Nettside -->
                        <?php if (!empty($lenke)): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Nettside</dt>
                            <dd class="text-sm">
                                <a href="<?php echo esc_url($lenke); ?>" 
                                   target="_blank" 
                                   rel="noopener"
                                   class="text-[#1A1A1A] hover:underline inline-flex items-center gap-1">
                                    <?php echo esc_html(parse_url($lenke, PHP_URL_HOST) ?: $lenke); ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                </a>
                            </dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </section>

            </div>

            <!-- Right Column: Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- STATUS Section -->
                <section class="bg-[#F7F5EF] rounded-lg p-5">
                    <h3 class="text-xs font-bold text-[#5A5A5A] uppercase tracking-wider mb-6">Status</h3>
                    
                    <dl class="space-y-6">
                        <div class="flex justify-between items-center">
                            <dt class="text-sm text-[#5A5A5A]">Status</dt>
                            <dd>
                                <span class="inline-block text-xs font-medium bg-[#DCFCE7] text-[#166534] px-2.5 py-1 rounded">
                                    Aktiv
                                </span>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm text-[#5A5A5A] flex items-center gap-2 mb-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                Sist oppdatert
                            </dt>
                            <dd class="text-sm text-[#1A1A1A] pl-[22px]"><?php echo esc_html($tool_updated); ?></dd>
                        </div>
                        
                        <?php if ($eier): ?>
                        <div>
                            <dt class="text-sm text-[#5A5A5A] flex items-center gap-2 mb-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                Eier
                            </dt>
                            <dd class="text-sm text-[#1A1A1A] pl-[22px]"><?php echo esc_html($eier->post_title); ?></dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </section>

                <!-- SNARVEIER Section -->
                <section class="bg-[#F7F5EF] rounded-lg p-5">
                    <h3 class="text-xs font-bold text-[#5A5A5A] uppercase tracking-wider mb-4">Snarveier</h3>
                    
                    <nav class="space-y-0 divide-y divide-[#E5E0D8]">
                        <?php if (!empty($lenke)): ?>
                        <a href="<?php echo esc_url($lenke); ?>" 
                           target="_blank"
                           rel="noopener"
                           class="block py-3 text-sm text-[#1A1A1A] hover:text-[#F97316] transition-colors">
                            Åpne dokumentasjon
                        </a>
                        <?php endif; ?>
                        
                        <a href="#" 
                           class="block py-3 text-sm text-[#1A1A1A] hover:text-[#F97316] transition-colors">
                            Rapporter feil
                        </a>
                        
                        <a href="#" 
                           class="block py-3 text-sm text-[#1A1A1A] hover:text-[#F97316] transition-colors">
                            Se endringslogg
                        </a>
                    </nav>
                </section>

            </div>

        </div>

    </div>
</main>

<?php 
endwhile; 
endif; 

get_footer();
?>
