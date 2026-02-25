<?php
/**
 * Part: Rediger verktøy
 *
 * Plain HTML form for editing an existing tool.
 * Replaces Gravity Forms Form #1.
 * Brukes på /min-side/verktoy/rediger/?id=XX
 *
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_id = get_user_meta($user_id, 'bimverdi_company_id', true)
           ?: get_user_meta($user_id, 'bim_verdi_company_id', true);

// Get tool ID from URL
$tool_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['tool_id']) ? intval($_GET['tool_id']) : 0);

if (!$tool_id) {
    wp_redirect(home_url('/min-side/verktoy/'));
    exit;
}

$tool = get_post($tool_id);

if (!$tool || $tool->post_type !== 'verktoy') {
    wp_redirect(home_url('/min-side/verktoy/'));
    exit;
}

// Check permission
$tool_company = get_field('eier_leverandor', $tool_id);
$can_edit = ($tool->post_author == $user_id)
         || ($company_id && $tool_company && $tool_company == $company_id)
         || current_user_can('manage_options');

if (!$can_edit) {
    wp_redirect(home_url('/min-side/verktoy/'));
    exit;
}

// Get tool data
$tool_name = $tool->post_title;
$description = get_field('detaljert_beskrivelse', $tool_id) ?: $tool->post_content;
$tool_url = get_field('verktoy_lenke', $tool_id);
$current_formaalstema = get_field('formaalstema', $tool_id);
$current_bim = get_field('bim_kompatibilitet', $tool_id);
$current_ressurs = get_field('type_ressurs', $tool_id);
$current_teknologi = get_field('type_teknologi', $tool_id);
$current_anvendelser = get_field('anvendelser', $tool_id);
if (!is_array($current_anvendelser)) $current_anvendelser = [];

// Handle array values for formaalstema (can be string or array)
if (is_array($current_formaalstema)) $current_formaalstema = reset($current_formaalstema);

$tool_category_terms = wp_get_post_terms($tool_id, 'verktoykategori');
$tool_category = !empty($tool_category_terms) ? $tool_category_terms[0]->name : 'Ukategorisert';
$tool_status = get_post_status($tool_id);
$tool_updated = get_the_modified_date('d.m.Y', $tool_id);

// Current logo
$logo_id = get_post_thumbnail_id($tool_id);
$logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'thumbnail') : '';

// Error handling
$error = isset($_GET['bv_error']) ? sanitize_text_field($_GET['bv_error']) : '';
$error_messages = [
    'nonce'              => 'Skjemaet utløp. Vennligst prøv igjen.',
    'rate_limit'         => 'For mange forsøk. Vennligst vent litt.',
    'no_company'         => 'Du må ha et foretak tilknyttet kontoen din.',
    'missing_name'       => 'Verktøynavn er påkrevd.',
    'missing_anvendelser'=> 'Du må velge minst én anvendelse.',
    'invalid_file_type'  => 'Ugyldig filtype. Tillatte: jpg, png, gif, webp, svg.',
    'file_too_large'     => 'Filen er for stor. Maks 2 MB.',
    'upload_failed'      => 'Kunne ikke laste opp logo. Vennligst prøv igjen.',
    'not_found'          => 'Verktøyet ble ikke funnet.',
    'permission'         => 'Du har ikke tilgang til å redigere dette verktøyet.',
    'system'             => 'En teknisk feil oppstod. Vennligst prøv igjen.',
];
$error_text = $error_messages[$error] ?? '';

// Options (same as registrer)
$formaalstema_options = [
    'byggesak' => 'ByggesaksBIM', 'prosjekt' => 'ProsjektBIM', 'eiendom' => 'EiendomsBIM',
    'miljo' => 'MiljøBIM', 'sirk' => 'SirkBIM', 'validering' => 'Validering',
    'opplaering' => 'Opplæring', 'samhandling' => 'Samhandling', 'prosjektutvikling' => 'Prosjektutvikling',
];
$bim_options = [
    'ifc_kompatibel' => 'IFC/BIM-kompatibel', 'ifc_eksport' => 'IFC-eksport',
    'ifc_import' => 'IFC-import', 'kobling_ifc' => 'Kobling mot IFC',
    'planlagt' => 'Planlagt/under utvikling', 'vet_ikke' => 'Ikke oppgitt',
];
$ressurs_options = [
    'programvare' => 'Programvare', 'standard' => 'Standard', 'metodikk' => 'Metodikk',
    'veileder' => 'Veileder', 'nettside' => 'Nettside', 'digital_tjeneste' => 'Digital tjeneste',
    'saas' => 'SaaS', 'kurs' => 'Kurs og opplæring',
];
$teknologi_options = [
    'bruker_ki' => 'Bruker KI', 'ikke_ki' => 'Bruker ikke KI',
    'planlegger_ki' => 'Planlegger KI', 'under_avklaring' => 'Under avklaring',
];
$anvendelser_options = [
    'design' => 'Design og modellering', 'gis' => 'GIS/kart', 'dokumenter' => 'Dokumenter og innhold',
    'prosjektledelse' => 'Prosjektledelse', 'kostnad' => 'Kostnadsanalyse',
    'simulering' => 'Simulering og analyse', 'feltarbeid' => 'Feltarbeid',
    'fasilitets' => 'Fasilitetsstyring (FDVU)', 'barekraft' => 'Bærekraft og miljø',
    'kommunikasjon' => 'Kommunikasjon', 'logistikk' => 'Logistikk', 'kompetanse' => 'Kompetanse',
];
?>

<!-- Breadcrumb -->
<nav class="mb-6" aria-label="Brødsmulesti">
    <ol class="flex items-center gap-2 text-sm text-[#57534E]">
        <li><a href="<?php echo esc_url(home_url('/min-side/')); ?>" class="hover:text-[#111827] transition-colors">Min side</a></li>
        <li><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></li>
        <li><a href="<?php echo esc_url(home_url('/min-side/verktoy/')); ?>" class="hover:text-[#111827] transition-colors">Mine verktøy</a></li>
        <li><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></li>
        <li class="text-[#111827] font-medium" aria-current="page"><?php echo esc_html($tool_name); ?></li>
    </ol>
</nav>

<?php get_template_part('parts/components/page-header', null, [
    'title' => 'Rediger verktøy',
    'description' => 'Oppdater informasjon om ' . esc_html($tool_name)
]); ?>

<div class="max-w-3xl mx-auto">

    <!-- Tool Info Badge -->
    <div class="mb-8 p-4 bg-[#F5F5F4] border border-[#E7E5E4] rounded-lg flex items-center justify-between">
        <div class="flex items-center gap-3">
            <?php if ($logo_url): ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="" class="w-10 h-10 rounded object-cover">
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#57534E]"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            <?php endif; ?>
            <div>
                <p class="font-semibold text-[#111827]"><?php echo esc_html($tool_name); ?></p>
                <p class="text-sm text-[#57534E]"><?php echo esc_html($tool_category); ?></p>
            </div>
        </div>
        <div class="text-right">
            <?php
            $status_label = $tool_status === 'publish' ? 'Publisert' : 'Utkast';
            $status_class = $tool_status === 'publish' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800';
            ?>
            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
            <p class="text-xs text-[#57534E] mt-1">Oppdatert <?php echo $tool_updated; ?></p>
        </div>
    </div>

    <?php if ($error_text): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <p class="text-red-800 text-sm"><?php echo esc_html($error_text); ?></p>
    </div>
    <?php endif; ?>

    <form method="post" action="" enctype="multipart/form-data" class="space-y-6">
        <?php wp_nonce_field('bimverdi_edit_tool'); ?>
        <input type="hidden" name="bimverdi_edit_tool" value="1">
        <input type="hidden" name="tool_id" value="<?php echo esc_attr($tool_id); ?>">
        <div style="position:absolute;left:-9999px;" aria-hidden="true">
            <input type="text" name="bv_website_url" tabindex="-1" autocomplete="off">
        </div>

        <h2 class="text-lg font-semibold text-[#111827]">Oppdater verktøydetaljer</h2>

        <!-- Verktøynavn -->
        <div>
            <label for="tool_name" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                Verktøynavn <span class="text-red-500">*</span>
            </label>
            <input type="text" id="tool_name" name="tool_name" required
                   value="<?php echo esc_attr($tool_name); ?>"
                   class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
        </div>

        <!-- Beskrivelse -->
        <div>
            <label for="description" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Detaljert beskrivelse</label>
            <textarea id="description" name="description" rows="5"
                      class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent resize-y"><?php echo esc_textarea($description); ?></textarea>
            <p class="mt-1 text-xs text-[#888888]">HTML-formatering er tillatt.</p>
        </div>

        <!-- Lenke -->
        <div>
            <label for="tool_url" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Lenke til verktøy</label>
            <input type="url" id="tool_url" name="tool_url"
                   value="<?php echo esc_attr($tool_url); ?>"
                   class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
        </div>

        <!-- Logo -->
        <div>
            <label class="block text-sm font-semibold text-[#1A1A1A] mb-2">Logo/bilde</label>
            <?php if ($logo_url): ?>
            <div class="mb-3 flex items-center gap-4">
                <img src="<?php echo esc_url($logo_url); ?>" alt="" class="w-16 h-16 rounded object-cover border border-[#E7E5E4]">
                <span class="text-xs text-[#57534E]">Nåværende logo</span>
            </div>
            <?php endif; ?>
            <input type="file" name="tool_logo" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml"
                   class="w-full text-sm text-[#5A5A5A] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border file:border-[#E5E0D5] file:text-sm file:font-medium file:bg-gray-100 file:text-[#1A1A1A] hover:file:bg-gray-200 file:cursor-pointer file:transition-colors">
            <p class="mt-1 text-xs text-[#888888]">Last opp ny logo for å erstatte nåværende. Maks 2 MB.</p>
        </div>

        <hr class="border-[#E5E0D5]">

        <!-- Formålstema -->
        <fieldset>
            <legend class="text-sm font-semibold text-[#1A1A1A] mb-3">Formålstema</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($formaalstema_options as $value => $label): ?>
                <label class="flex items-center gap-3 p-3 rounded-lg border border-[#E5E0D5] hover:border-[#FF8B5E] hover:bg-[#FFF8F5] transition-colors cursor-pointer has-[:checked]:border-[#FF8B5E] has-[:checked]:bg-[#FFF8F5]">
                    <input type="radio" name="formaalstema" value="<?php echo esc_attr($value); ?>"
                           <?php checked($current_formaalstema, $value); ?>
                           class="w-4 h-4 border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($label); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <!-- BIM-kompatibilitet -->
        <fieldset>
            <legend class="text-sm font-semibold text-[#1A1A1A] mb-3">BIM-kompatibilitet</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($bim_options as $value => $label): ?>
                <label class="flex items-center gap-3 p-3 rounded-lg border border-[#E5E0D5] hover:border-[#FF8B5E] hover:bg-[#FFF8F5] transition-colors cursor-pointer has-[:checked]:border-[#FF8B5E] has-[:checked]:bg-[#FFF8F5]">
                    <input type="radio" name="bim_kompatibilitet" value="<?php echo esc_attr($value); ?>"
                           <?php checked($current_bim, $value); ?>
                           class="w-4 h-4 border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($label); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <!-- Type ressurs -->
        <fieldset>
            <legend class="text-sm font-semibold text-[#1A1A1A] mb-3">Type ressurs</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($ressurs_options as $value => $label): ?>
                <label class="flex items-center gap-3 p-3 rounded-lg border border-[#E5E0D5] hover:border-[#FF8B5E] hover:bg-[#FFF8F5] transition-colors cursor-pointer has-[:checked]:border-[#FF8B5E] has-[:checked]:bg-[#FFF8F5]">
                    <input type="radio" name="type_ressurs" value="<?php echo esc_attr($value); ?>"
                           <?php checked($current_ressurs, $value); ?>
                           class="w-4 h-4 border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($label); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <!-- Type teknologi -->
        <fieldset>
            <legend class="text-sm font-semibold text-[#1A1A1A] mb-3">Type teknologi</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($teknologi_options as $value => $label): ?>
                <label class="flex items-center gap-3 p-3 rounded-lg border border-[#E5E0D5] hover:border-[#FF8B5E] hover:bg-[#FFF8F5] transition-colors cursor-pointer has-[:checked]:border-[#FF8B5E] has-[:checked]:bg-[#FFF8F5]">
                    <input type="radio" name="type_teknologi" value="<?php echo esc_attr($value); ?>"
                           <?php checked($current_teknologi, $value); ?>
                           class="w-4 h-4 border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($label); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <hr class="border-[#E5E0D5]">

        <!-- Anvendelser -->
        <fieldset>
            <legend class="text-sm font-semibold text-[#1A1A1A] mb-1">
                Anvendelser <span class="text-red-500">*</span>
            </legend>
            <p class="text-xs text-[#888888] mb-3">Velg minst én anvendelse.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($anvendelser_options as $value => $label): ?>
                <label class="flex items-start gap-3 p-3 rounded-lg border border-[#E5E0D5] hover:border-[#FF8B5E] hover:bg-[#FFF8F5] transition-colors cursor-pointer has-[:checked]:border-[#FF8B5E] has-[:checked]:bg-[#FFF8F5]">
                    <input type="checkbox" name="anvendelser[]" value="<?php echo esc_attr($value); ?>"
                           <?php checked(in_array($value, $current_anvendelser)); ?>
                           class="mt-0.5 w-4 h-4 rounded border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($label); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <!-- Submit -->
        <div class="pt-4">
            <button type="submit"
                    class="bv-btn bv-btn--primary px-6 py-2.5 text-sm font-semibold rounded-lg inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Oppdater verktøy
            </button>
        </div>
    </form>

    <!-- Danger Zone -->
    <div class="mt-12 pt-8 border-t border-[#E7E5E4]">
        <h3 class="text-sm font-bold text-[#111827] uppercase tracking-wider mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
            Faresone
        </h3>
        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-800 mb-3">Vil du slette dette verktøyet permanent? Denne handlingen kan ikke angres.</p>
            <button type="button"
                    onclick="if(confirm('Er du sikker på at du vil slette dette verktøyet?')) { window.location.href='<?php echo esc_url(add_query_arg(['action' => 'delete_tool', 'tool_id' => $tool_id, '_wpnonce' => wp_create_nonce('delete_tool_' . $tool_id)], home_url('/min-side/verktoy/'))); ?>'; }"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                Slett verktøy
            </button>
        </div>
    </div>

    <!-- Back Link -->
    <div class="mt-8 pt-6 border-t border-[#E7E5E4]">
        <a href="<?php echo esc_url(home_url('/min-side/verktoy/')); ?>" class="inline-flex items-center gap-2 text-[#57534E] hover:text-[#111827] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Tilbake til mine verktøy
        </a>
    </div>
</div>
