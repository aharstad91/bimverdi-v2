<?php
/**
 * Single Verktøy Template v3
 * 
 * Displays detailed information about a single tool/software.
 * Design based on UI Contract v1 - Variant B (Dividers/Whitespace)
 * 
 * @package BimVerdi_Theme
 */

get_header();

if (have_posts()) : while (have_posts()) : the_post();

// Get ACF fields
$eier_id = get_field('eier_leverandor');
$eier = $eier_id ? get_post($eier_id) : null;
$kort_beskrivelse = get_field('kort_beskrivelse');
$detaljert_beskrivelse = get_field('detaljert_beskrivelse');
$lenke = get_field('verktoy_lenke');
$nedlastingslenke = get_field('nedlastingslenke');
$logo = get_field('verktoy_logo');
$logo_url = '';
if ($logo) {
    $logo_url = is_array($logo) ? ($logo['url'] ?? '') : wp_get_attachment_url($logo);
}

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

// Check if current user can edit this tool (only hovedkontakt of owner company)
$current_user_id = get_current_user_id();
$can_edit = false;
if ($current_user_id && $eier_id) {
    // Check if user is hovedkontakt of the company that owns the tool
    $hovedkontakt_id = get_field('hovedkontaktperson', $eier_id);
    if ($hovedkontakt_id && $hovedkontakt_id == $current_user_id) {
        $can_edit = true;
    }
}

// Helper function for readable labels
function bimverdi_v3_readable_label($value) {
    // Handle arrays - return first value's label
    if (is_array($value)) {
        if (empty($value)) return '';
        $value = reset($value); // Get first element
    }

    $labels = [
        // Formålstema (nye keys)
        'byggesak' => 'ByggesaksBIM',
        'prosjekt' => 'ProsjektBIM',
        'eiendom' => 'EiendomsBIM',
        'miljo' => 'MiljøBIM',
        'sirk' => 'SirkBIM',
        'validering' => 'Validering',
        'opplaering' => 'Opplæring',
        'samhandling' => 'Samhandling',
        'prosjektutvikling' => 'Prosjektutvikling',
        // Formålstema (legacy)
        'ByggesaksBIM' => 'ByggesaksBIM',
        'ProsjektBIM' => 'ProsjektBIM',
        'EiendomsBIM' => 'EiendomsBIM',
        'MiljøBIM' => 'MiljøBIM',
        'SirkBIM' => 'SirkBIM',
        'Opplæring' => 'Opplæring',
        // BIM-kompatibilitet (nye keys)
        'ifc_kompatibel' => 'IFC/BIM-kompatibel',
        'ifc_eksport' => 'IFC-eksport',
        'ifc_import' => 'IFC-import',
        'kobling_ifc' => 'Kobling mot IFC',
        'planlagt' => 'Planlagt/under utvikling',
        'vet_ikke' => 'Ikke oppgitt',
        // BIM-kompatibilitet (legacy)
        'IFC_kompatibel' => 'IFC-kompatibel',
        'IFC_eksport' => 'IFC-eksport',
        'IFC_import' => 'IFC-import',
        'IFC_kobling' => 'IFC-kobling',
        // Type ressurs (nye keys)
        'programvare' => 'Programvare',
        'standard' => 'Standard',
        'metodikk' => 'Metodikk',
        'veileder' => 'Veileder',
        'nettside' => 'Nettside',
        'digital_tjeneste' => 'Digital tjeneste',
        'saas' => 'SaaS',
        'kurs' => 'Kurs og opplæring',
        // Type ressurs (legacy)
        'Programvare' => 'Programvare',
        'Standard' => 'Standard',
        'Metodikk' => 'Metodikk',
        'Veileder' => 'Veileder',
        'Nettside' => 'Nettside',
        'Digital_tjeneste' => 'Digital tjeneste',
        // Type teknologi (nye keys)
        'bruker_ki' => 'Bruker KI',
        'ikke_ki' => 'Bruker ikke KI',
        'planlegger_ki' => 'Planlegger KI',
        'under_avklaring' => 'Under avklaring',
        // Type teknologi (legacy)
        'Bruker_KI' => 'Bruker KI',
        'Ikke_KI' => 'Ikke KI',
        'Under_avklaring' => 'Under avklaring',
        // Anvendelser (nye keys)
        'design' => 'Design og modellering',
        'gis' => 'GIS/kart',
        'dokumenter' => 'Dokumenter og innhold',
        'prosjektledelse' => 'Prosjektledelse',
        'kostnad' => 'Kostnadsanalyse',
        'simulering' => 'Simulering og analyse',
        'feltarbeid' => 'Feltarbeid',
        'fasilitets' => 'Fasilitetsstyring (FDVU)',
        'barekraft' => 'Bærekraft og miljø',
        'kommunikasjon' => 'Kommunikasjon',
        'logistikk' => 'Logistikk',
        'kompetanse' => 'Kompetanse',
        // Anvendelser (legacy)
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
                    <a href="<?php echo esc_url(home_url('/verktoy/')); ?>" class="hover:text-[#1A1A1A] transition-colors flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        Verktøy
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
            <div class="flex items-start gap-5 flex-1">
                <?php if ($logo_url): ?>
                <div class="flex-shrink-0 w-20 h-20 bg-white rounded-lg border border-[#E5E0D8] p-2 flex items-center justify-center">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php the_title(); ?> logo" class="max-w-full max-h-full object-contain">
                </div>
                <?php endif; ?>
                <div>
                    <h1 class="text-3xl font-bold text-[#1A1A1A] mb-1"><?php the_title(); ?></h1>
                    <?php if ($eier): ?>
                    <p class="text-[#5A5A5A]">
                        <a href="<?php echo get_permalink($eier->ID); ?>" class="hover:underline">
                            <?php echo esc_html($eier->post_title); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($can_edit): ?>
            <div class="flex items-center gap-3 flex-shrink-0">
                <?php bimverdi_button([
                    'text' => 'Rediger',
                    'variant' => 'secondary',
                    'icon' => 'square-pen',
                    'href' => home_url('/min-side/rediger-verktoy/?id=' . get_the_ID())
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

                    <?php if (!empty($kort_beskrivelse)): ?>
                    <div class="prose prose-sm max-w-none text-[#5A5A5A] mb-6">
                        <?php echo wpautop(esc_html($kort_beskrivelse)); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($detaljert_beskrivelse)): ?>
                    <div class="prose prose-sm max-w-none text-[#5A5A5A] mb-6">
                        <?php echo $detaljert_beskrivelse; ?>
                    </div>
                    <?php elseif (empty($kort_beskrivelse)): ?>
                    <div class="prose prose-sm max-w-none text-[#5A5A5A] mb-6">
                        <?php if (has_excerpt()): ?>
                            <p><?php echo get_the_excerpt(); ?></p>
                        <?php else: ?>
                            <p class="italic">Ingen beskrivelse tilgjengelig.</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
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
                        <?php
                        // Handle bim_kompatibilitet (now array)
                        $bim_values = is_array($bim_kompatibilitet) ? $bim_kompatibilitet : ($bim_kompatibilitet ? [$bim_kompatibilitet] : []);
                        foreach ($bim_values as $bim_val):
                            if ($bim_val && $bim_val !== 'vet_ikke'):
                        ?>
                        <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-3 py-1.5 rounded">
                            <?php echo esc_html(bimverdi_v3_readable_label($bim_val)); ?>
                        </span>
                        <?php endif; endforeach; ?>
                        <?php
                        // Handle formaalstema (now array)
                        $tema_values = is_array($formaalstema) ? $formaalstema : ($formaalstema ? [$formaalstema] : []);
                        foreach ($tema_values as $tema_val):
                            if ($tema_val):
                        ?>
                        <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-3 py-1.5 rounded">
                            <?php echo esc_html(bimverdi_v3_readable_label($tema_val)); ?>
                        </span>
                        <?php endif; endforeach; ?>
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
                        <?php
                        $type_values = is_array($type_ressurs) ? $type_ressurs : ($type_ressurs ? [$type_ressurs] : []);
                        if (!empty($type_values)):
                        ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Type</dt>
                            <dd class="text-sm flex flex-wrap gap-2">
                                <?php foreach ($type_values as $type_val): ?>
                                <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-3 py-1.5 rounded">
                                    <?php echo esc_html(bimverdi_v3_readable_label($type_val)); ?>
                                </span>
                                <?php endforeach; ?>
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
                                   class="text-[#FF8B5E] hover:underline inline-flex items-center gap-1">
                                    <?php echo esc_html(parse_url($lenke, PHP_URL_HOST) ?: $lenke); ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                </a>
                            </dd>
                        </div>
                        <?php endif; ?>

                        <!-- Nedlastingslenke -->
                        <?php if (!empty($nedlastingslenke)): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Nedlasting/App</dt>
                            <dd class="text-sm">
                                <a href="<?php echo esc_url($nedlastingslenke); ?>"
                                   target="_blank"
                                   rel="noopener"
                                   class="text-[#FF8B5E] hover:underline inline-flex items-center gap-1">
                                    <?php echo esc_html(parse_url($nedlastingslenke, PHP_URL_HOST) ?: $nedlastingslenke); ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                </a>
                            </dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </section>

                <!-- BIM-kompatibilitet Section -->
                <?php
                $bim_values = is_array($bim_kompatibilitet) ? $bim_kompatibilitet : ($bim_kompatibilitet ? [$bim_kompatibilitet] : []);
                if (!empty($bim_values)):
                ?>
                <section class="border-t border-[#E5E0D8] pt-10">
                    <h2 class="text-lg font-bold text-[#1A1A1A] mb-4">BIM-kompatibilitet</h2>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($bim_values as $bim_val): ?>
                        <span class="inline-flex items-center gap-2 text-sm font-medium bg-[#ECFDF5] text-[#059669] px-3 py-2 rounded">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            <?php echo esc_html(bimverdi_v3_readable_label($bim_val)); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

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
                        <?php if (!empty($nedlastingslenke)): ?>
                        <a href="<?php echo esc_url($nedlastingslenke); ?>"
                           target="_blank"
                           rel="noopener"
                           class="flex items-center gap-2 py-3 text-sm text-[#1A1A1A] hover:text-[#FF8B5E] transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Åpne verktøyet
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($lenke)): ?>
                        <a href="<?php echo esc_url($lenke); ?>"
                           target="_blank"
                           rel="noopener"
                           class="flex items-center gap-2 py-3 text-sm text-[#1A1A1A] hover:text-[#FF8B5E] transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            Besøk nettside
                        </a>
                        <?php endif; ?>

                        <a href="#"
                           class="flex items-center gap-2 py-3 text-sm text-[#1A1A1A] hover:text-[#FF8B5E] transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            Rapporter feil
                        </a>

                        <a href="#"
                           class="flex items-center gap-2 py-3 text-sm text-[#1A1A1A] hover:text-[#FF8B5E] transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
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
