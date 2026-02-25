<?php
/**
 * Part: Registrer verktøy
 *
 * Plain HTML form for registering a new tool.
 * Replaces Gravity Forms Form #1.
 * Brukes på /min-side/verktoy/registrer/
 *
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_id = get_user_meta($user_id, 'bimverdi_company_id', true)
           ?: get_user_meta($user_id, 'bim_verdi_company_id', true);

// Redirect if not connected to a company
if (!$company_id) {
    wp_redirect(home_url('/min-side/'));
    exit;
}

$company = get_post($company_id);

// Error handling
$error = isset($_GET['bv_error']) ? sanitize_text_field($_GET['bv_error']) : '';
$error_messages = [
    'nonce'              => 'Skjemaet utløp. Vennligst prøv igjen.',
    'rate_limit'         => 'For mange forsøk. Vennligst vent litt.',
    'no_company'         => 'Du må ha et foretak tilknyttet kontoen din.',
    'missing_name'       => 'Verktøynavn er påkrevd.',
    'missing_kort_beskrivelse' => 'Kort beskrivelse er påkrevd.',
    'missing_anvendelser'=> 'Du må velge minst én anvendelse.',
    'invalid_file_type'  => 'Ugyldig filtype. Tillatte: jpg, png, gif, webp, svg.',
    'file_too_large'     => 'Filen er for stor. Maks 2 MB.',
    'upload_failed'      => 'Kunne ikke laste opp logo. Vennligst prøv igjen.',
    'system'             => 'En teknisk feil oppstod. Vennligst prøv igjen.',
];
$error_text = $error_messages[$error] ?? '';

// Radio options
$formaalstema_options = [
    'byggesak' => 'ByggesaksBIM',
    'prosjekt' => 'ProsjektBIM',
    'eiendom'  => 'EiendomsBIM',
    'miljo'    => 'MiljøBIM',
    'sirk'     => 'SirkBIM',
    'validering'  => 'Validering',
    'opplaering'  => 'Opplæring',
    'samhandling' => 'Samhandling',
    'prosjektutvikling' => 'Prosjektutvikling',
];

$bim_options = [
    'ifc_kompatibel' => 'IFC/BIM-kompatibel',
    'ifc_eksport'    => 'IFC-eksport',
    'ifc_import'     => 'IFC-import',
    'kobling_ifc'    => 'Kobling mot IFC',
    'planlagt'       => 'Planlagt/under utvikling',
    'vet_ikke'       => 'Ikke oppgitt',
];

$ressurs_options = [
    'programvare'     => 'Programvare',
    'standard'        => 'Standard',
    'metodikk'        => 'Metodikk',
    'veileder'        => 'Veileder',
    'nettside'        => 'Nettside',
    'digital_tjeneste'=> 'Digital tjeneste',
    'saas'            => 'SaaS',
    'kurs'            => 'Kurs og opplæring',
];

$teknologi_options = [
    'bruker_ki'       => 'Bruker KI',
    'ikke_ki'         => 'Bruker ikke KI',
    'planlegger_ki'   => 'Planlegger KI',
    'under_avklaring' => 'Under avklaring',
];

$anvendelser_options = [
    'design'          => 'Design og modellering',
    'gis'             => 'GIS/kart',
    'dokumenter'      => 'Dokumenter og innhold',
    'prosjektledelse' => 'Prosjektledelse',
    'kostnad'         => 'Kostnadsanalyse',
    'simulering'      => 'Simulering og analyse',
    'feltarbeid'      => 'Feltarbeid',
    'fasilitets'      => 'Fasilitetsstyring (FDVU)',
    'barekraft'       => 'Bærekraft og miljø',
    'kommunikasjon'   => 'Kommunikasjon',
    'logistikk'       => 'Logistikk',
    'kompetanse'      => 'Kompetanse',
];
?>

<!-- Breadcrumb -->
<nav class="mb-6" aria-label="Brødsmulesti">
    <ol class="flex items-center gap-2 text-sm text-[#57534E]">
        <li><a href="<?php echo esc_url(home_url('/min-side/')); ?>" class="hover:text-[#111827] transition-colors">Min side</a></li>
        <li><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></li>
        <li><a href="<?php echo esc_url(home_url('/min-side/verktoy/')); ?>" class="hover:text-[#111827] transition-colors">Mine verktøy</a></li>
        <li><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></li>
        <li class="text-[#111827] font-medium" aria-current="page">Registrer verktøy</li>
    </ol>
</nav>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => 'Registrer verktøy',
    'description' => 'Del verktøy, programvare og ressurser med BIM Verdi-nettverket'
]); ?>

<!-- Form Container -->
<div class="max-w-3xl">

    <!-- Company Info -->
    <?php if ($company): ?>
    <div class="mb-8 flex items-center gap-3 text-sm text-[#57534E]">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
        <span>Registrerer for:</span>
        <strong class="text-[#111827]"><?php echo esc_html($company->post_title); ?></strong>
    </div>
    <?php endif; ?>

    <div class="border-t border-[#E7E5E4] mb-8"></div>

    <?php if ($error_text): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <p class="text-red-800 text-sm"><?php echo esc_html($error_text); ?></p>
    </div>
    <?php endif; ?>

    <form method="post" action="" enctype="multipart/form-data" class="space-y-6">
        <?php wp_nonce_field('bimverdi_register_tool'); ?>
        <input type="hidden" name="bimverdi_register_tool" value="1">
        <!-- Honeypot -->
        <div style="position:absolute;left:-9999px;" aria-hidden="true">
            <input type="text" name="bv_website_url" tabindex="-1" autocomplete="off">
        </div>

        <h2 class="text-lg font-semibold text-[#111827]">Verktøydetaljer</h2>

        <!-- Verktøynavn -->
        <div>
            <label for="tool_name" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                Verktøynavn <span class="text-red-500">*</span>
            </label>
            <input type="text" id="tool_name" name="tool_name" required
                   placeholder="Navn på verktøyet"
                   class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
        </div>

        <!-- Kort beskrivelse -->
        <div>
            <label for="kort_beskrivelse" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                Kort beskrivelse <span class="text-red-500">*</span>
            </label>
            <input type="text" id="kort_beskrivelse" name="kort_beskrivelse" required maxlength="100"
                   placeholder="Kort oppsummering av verktøyet (maks 100 tegn)"
                   class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
            <p class="mt-1 text-xs text-[#888888]">Vises i liste- og kortvisning. Maks 100 tegn.</p>
        </div>

        <!-- Beskrivelse -->
        <div>
            <label for="description" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                Detaljert beskrivelse
            </label>
            <textarea id="description" name="description" rows="5"
                      placeholder="Beskriv verktøyet, hva det gjør og hvordan det brukes"
                      class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent resize-y"></textarea>
            <p class="mt-1 text-xs text-[#888888]">HTML-formatering er tillatt.</p>
        </div>

        <!-- Versjon -->
        <div>
            <label for="versjon" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Versjon</label>
            <input type="text" id="versjon" name="versjon"
                   placeholder="f.eks. 2024.1, v3.0"
                   class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
        </div>

        <!-- Lenke til verktøy -->
        <div>
            <label for="tool_url" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                Lenke til verktøy
            </label>
            <input type="url" id="tool_url" name="tool_url"
                   placeholder="https://eksempel.no"
                   class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
        </div>

        <!-- Link til produktbeskrivelse -->
        <div>
            <label for="produktbeskrivelse_url" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Link til produktbeskrivelse</label>
            <input type="url" id="produktbeskrivelse_url" name="produktbeskrivelse_url"
                   placeholder="https://leverandor.no/produkt"
                   class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
        </div>

        <!-- Link til nedlasting -->
        <div>
            <label for="nedlasting_url" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Link til nedlasting</label>
            <input type="url" id="nedlasting_url" name="nedlasting_url"
                   placeholder="https://leverandor.no/nedlasting"
                   class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
        </div>

        <!-- Logo -->
        <div>
            <label class="block text-sm font-semibold text-[#1A1A1A] mb-2">Logo/bilde</label>
            <input type="file" name="tool_logo" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml"
                   class="w-full text-sm text-[#5A5A5A] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border file:border-[#E5E0D5] file:text-sm file:font-medium file:bg-gray-100 file:text-[#1A1A1A] hover:file:bg-gray-200 file:cursor-pointer file:transition-colors">
            <p class="mt-1 text-xs text-[#888888]">Tillatte formater: jpg, png, gif, webp, svg. Maks 2 MB.</p>
        </div>

        <hr class="border-[#E5E0D5]">

        <!-- Formålstema -->
        <fieldset>
            <legend class="text-sm font-semibold text-[#1A1A1A] mb-3">Formålstema</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($formaalstema_options as $value => $label): ?>
                <label class="flex items-center gap-3 p-3 rounded-lg border border-[#E5E0D5] hover:border-[#FF8B5E] hover:bg-[#FFF8F5] transition-colors cursor-pointer has-[:checked]:border-[#FF8B5E] has-[:checked]:bg-[#FFF8F5]">
                    <input type="radio" name="formaalstema" value="<?php echo esc_attr($value); ?>"
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
                           class="w-4 h-4 border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($label); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <hr class="border-[#E5E0D5]">

        <!-- Anvendelser (checkbox) -->
        <fieldset>
            <legend class="text-sm font-semibold text-[#1A1A1A] mb-1">
                Anvendelser <span class="text-red-500">*</span>
            </legend>
            <p class="text-xs text-[#888888] mb-3">Velg minst én anvendelse.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($anvendelser_options as $value => $label): ?>
                <label class="flex items-start gap-3 p-3 rounded-lg border border-[#E5E0D5] hover:border-[#FF8B5E] hover:bg-[#FFF8F5] transition-colors cursor-pointer has-[:checked]:border-[#FF8B5E] has-[:checked]:bg-[#FFF8F5]">
                    <input type="checkbox" name="anvendelser[]" value="<?php echo esc_attr($value); ?>"
                           class="mt-0.5 w-4 h-4 rounded border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($label); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <hr class="border-[#E5E0D5]">

        <!-- Samtykke -->
        <div>
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="samtykke" value="1" required
                       class="mt-0.5 w-4 h-4 rounded border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                <span class="text-sm text-[#5A5A5A]">
                    Jeg bekrefter at informasjonen er korrekt og samtykker til at den deles med BIM Verdi-nettverket.
                </span>
            </label>
        </div>

        <!-- Submit -->
        <div class="pt-4">
            <button type="submit"
                    class="bv-btn bv-btn--primary px-6 py-2.5 text-sm font-semibold rounded-lg inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Registrer verktøy
            </button>
        </div>
    </form>

    <div class="border-t border-[#E7E5E4] mt-8 mb-6"></div>

    <!-- Back Link -->
    <div class="mb-8">
        <a href="<?php echo esc_url(home_url('/min-side/verktoy/')); ?>" class="inline-flex items-center gap-2 text-[#57534E] hover:text-[#111827] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Tilbake til mine verktøy
        </a>
    </div>
</div>
