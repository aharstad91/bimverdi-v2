<?php
/**
 * Part: Registrer foretak
 *
 * Plain HTML form for company registration with BRreg autocomplete.
 * POST handler in mu-plugins/bimverdi-foretak-registration.php
 *
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Check if user already has a company - redirect to foretak page
$existing_foretak_id = bimverdi_user_has_foretak($user_id);
if ($existing_foretak_id && get_post_status($existing_foretak_id) === 'publish') {
    wp_redirect(home_url('/min-side/foretak/'));
    exit;
}

// Error messages
$bv_error = isset($_GET['bv_error']) ? sanitize_text_field($_GET['bv_error']) : '';
$error_messages = [
    'nonce'             => 'Noe gikk galt. Vennligst prøv igjen.',
    'rate_limit'        => 'For mange forsøk. Vennligst vent litt før du prøver igjen.',
    'missing_name'      => 'Bedriftsnavn er påkrevd.',
    'invalid_orgnr'     => 'Organisasjonsnummer må være 9 siffer.',
    'missing_description' => 'Bedriftsbeskrivelse er påkrevd.',
    'missing_bransje'   => 'Velg minst én bransje/rolle.',
    'orgnr_exists'      => 'Dette organisasjonsnummeret er allerede registrert i BIM Verdi.',
    'invalid_file_type' => 'Ugyldig filtype. Bruk JPG, PNG, GIF, WebP eller SVG.',
    'file_too_large'    => 'Filen er for stor. Maks 2 MB.',
    'upload_failed'     => 'Opplasting av logo feilet. Prøv igjen.',
    'system'            => 'En teknisk feil oppstod. Vennligst prøv igjen senere.',
];
$error_message = $error_messages[$bv_error] ?? '';

// Bransje/rolle options
$bransje_options = [
    'bestiller_byggherre'    => 'Bestiller/byggherre',
    'boligutvikler'          => 'Boligutvikler',
    'arkitekt_radgiver'      => 'Arkitekt/rådgiver',
    'radgivende_ingenior'    => 'Rådgivende ingeniør',
    'entreprenor_byggmester'  => 'Entreprenør/byggmester',
    'byggevareprodusent'     => 'Byggevareprodusent',
    'byggevarehandel'        => 'Byggevarehandel',
    'eiendom_drift'          => 'Eiendom/drift',
    'digital_leverandor'     => 'Leverandør av digitale verktøy, innhold og løsninger',
    'organisasjon'           => 'Organisasjon, nettverk m.m.',
    'tjenesteleverandor'     => 'Tjenesteleverandør',
    'offentlig'              => 'Offentlig instans',
    'utdanning'              => 'Utdanningsinstitusjon',
    'annet'                  => 'Annet',
];

// Kundetype options
$kundetype_options = [
    'bestiller_byggherre'    => 'Bestiller/byggherre',
    'arkitekt_radgiver'      => 'Arkitekt/rådgiver',
    'entreprenor_byggmester'  => 'Entreprenør/byggmester',
    'byggevareprodusent'     => 'Byggevareprodusent',
    'byggevarehandel'        => 'Byggevarehandel',
    'eiendom_drift'          => 'Eiendom/drift',
    'digital_leverandor'     => 'Leverandør av digitale verktøy',
    'organisasjon'           => 'Organisasjon',
    'tjenesteleverandor'     => 'Tjenesteleverandør',
    'offentlig'              => 'Offentlig instans',
    'utdanning'              => 'Utdanningsinstitusjon',
    'annet'                  => 'Annet',
];
?>

<!-- Breadcrumb -->
<nav class="mb-6" aria-label="Brødsmulesti">
    <ol class="flex items-center gap-2 text-sm text-[#57534E]">
        <li>
            <a href="<?php echo esc_url(home_url('/min-side/')); ?>" class="hover:text-[#111827] transition-colors">
                Min side
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li class="text-[#111827] font-medium" aria-current="page">Registrer foretak</li>
    </ol>
</nav>

<!-- Page Header -->
<?php
get_template_part('parts/components/page-header', null, [
    'title' => 'Registrer foretak',
    'description' => 'Koble ditt foretak til BIM Verdi nettverksportalen'
]);
?>

<!-- Form Container (960px centered per UI Contract) -->
<div class="max-w-3xl mx-auto">

    <!-- Info Section -->
    <div class="mb-8 p-4 bg-[#F5F5F4] rounded-lg">
        <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FF8B5E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            <p class="text-sm text-[#57534E]">
                <strong class="text-[#111827]">Tips:</strong> Start å skrive foretaksnavnet, så henter vi automatisk informasjon fra Brønnøysundregistrene.
            </p>
        </div>
    </div>

    <!-- Error Message -->
    <?php if ($error_message): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <p class="text-red-800 text-sm"><?php echo esc_html($error_message); ?></p>
    </div>
    <?php endif; ?>

    <!-- Registration Form -->
    <form method="post" action="" enctype="multipart/form-data" class="space-y-6">
        <?php wp_nonce_field('bimverdi_register_foretak'); ?>

        <!-- Honeypot -->
        <div style="position: absolute; left: -9999px;" aria-hidden="true">
            <label for="bv_website_url">Ikke fyll ut dette feltet</label>
            <input type="text" name="bv_website_url" id="bv_website_url" value="" tabindex="-1" autocomplete="off">
        </div>

        <!-- Company Name -->
        <div>
            <label for="bedriftsnavn" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                Bedriftsnavn <span class="text-red-600">*</span>
            </label>
            <input type="text"
                   id="bedriftsnavn"
                   name="bedriftsnavn"
                   required
                   autocomplete="organization"
                   placeholder="Søk etter foretaksnavn..."
                   class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent text-[#1A1A1A] placeholder:text-[#A8A29E]">
        </div>

        <!-- Org Number -->
        <div>
            <label for="organisasjonsnummer" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                Organisasjonsnummer <span class="text-red-600">*</span>
            </label>
            <input type="text"
                   id="organisasjonsnummer"
                   name="organisasjonsnummer"
                   required
                   pattern="\d{9}"
                   maxlength="9"
                   inputmode="numeric"
                   autocomplete="off"
                   placeholder="9 siffer"
                   class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent text-[#1A1A1A] placeholder:text-[#A8A29E]">
            <p class="mt-1 text-xs text-[#888888]">Fylles inn automatisk fra Brønnøysundregistrene</p>
        </div>

        <!-- Description -->
        <div>
            <label for="beskrivelse" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                Bedriftsbeskrivelse <span class="text-red-600">*</span>
            </label>
            <textarea id="beskrivelse"
                      name="beskrivelse"
                      required
                      rows="4"
                      placeholder="Kort beskrivelse av foretaket..."
                      class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent text-[#1A1A1A] placeholder:text-[#A8A29E] resize-y"></textarea>
        </div>

        <!-- Logo -->
        <div>
            <label for="logo" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                Logo
            </label>
            <input type="file"
                   id="logo"
                   name="logo"
                   accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml"
                   class="w-full text-sm text-[#5A5A5A] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border file:border-[#E5E0D5] file:text-sm file:font-medium file:bg-[#F7F5EF] file:text-[#1A1A1A] hover:file:bg-[#EFE9DE] file:cursor-pointer file:transition-colors">
            <p class="mt-1 text-xs text-[#888888]">JPG, PNG, GIF, WebP eller SVG. Maks 2 MB.</p>
        </div>

        <!-- Divider -->
        <hr class="border-[#E5E0D5]">

        <!-- Address Section -->
        <div>
            <h3 class="text-base font-semibold text-[#1A1A1A] mb-4">Adresse</h3>
            <div class="space-y-4">
                <div>
                    <label for="gateadresse" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                        Gateadresse
                    </label>
                    <input type="text"
                           id="gateadresse"
                           name="gateadresse"
                           autocomplete="street-address"
                           class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent text-[#1A1A1A]">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="postnummer" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                            Postnummer
                        </label>
                        <input type="text"
                               id="postnummer"
                               name="postnummer"
                               maxlength="4"
                               inputmode="numeric"
                               autocomplete="postal-code"
                               class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent text-[#1A1A1A]">
                    </div>

                    <div>
                        <label for="poststed" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                            Poststed
                        </label>
                        <input type="text"
                               id="poststed"
                               name="poststed"
                               autocomplete="address-level2"
                               class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent text-[#1A1A1A]">
                    </div>
                </div>
            </div>
            <p class="mt-1 text-xs text-[#888888]">Fylles inn automatisk fra Brønnøysundregistrene</p>
        </div>

        <!-- Website -->
        <div>
            <label for="nettside" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                Nettside
            </label>
            <input type="url"
                   id="nettside"
                   name="nettside"
                   placeholder="https://"
                   autocomplete="url"
                   class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent text-[#1A1A1A] placeholder:text-[#A8A29E]">
        </div>

        <!-- Divider -->
        <hr class="border-[#E5E0D5]">

        <!-- Bransje / Rolle -->
        <fieldset>
            <legend class="text-sm font-semibold text-[#1A1A1A] mb-1">
                Vår rolle/fag/bransje <span class="text-red-600">*</span>
            </legend>
            <p class="text-xs text-[#888888] mb-3">Du kan velge flere</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($bransje_options as $value => $label): ?>
                <label class="flex items-start gap-3 p-3 rounded-lg border border-[#E5E0D5] hover:border-[#FF8B5E] hover:bg-[#FFF8F5] transition-colors cursor-pointer has-[:checked]:border-[#FF8B5E] has-[:checked]:bg-[#FFF8F5]">
                    <input type="checkbox"
                           name="bransje_rolle[]"
                           value="<?php echo esc_attr($value); ?>"
                           class="mt-0.5 w-4 h-4 rounded border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($label); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <!-- Kundetype -->
        <fieldset>
            <legend class="text-sm font-semibold text-[#1A1A1A] mb-1">
                Kundetyper
            </legend>
            <p class="text-xs text-[#888888] mb-3">Hvem er dine kunder? Du kan velge flere</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($kundetype_options as $value => $label): ?>
                <label class="flex items-start gap-3 p-3 rounded-lg border border-[#E5E0D5] hover:border-[#FF8B5E] hover:bg-[#FFF8F5] transition-colors cursor-pointer has-[:checked]:border-[#FF8B5E] has-[:checked]:bg-[#FFF8F5]">
                    <input type="checkbox"
                           name="kundetyper[]"
                           value="<?php echo esc_attr($value); ?>"
                           class="mt-0.5 w-4 h-4 rounded border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($label); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <!-- Divider -->
        <hr class="border-[#E5E0D5]">

        <!-- Submit -->
        <div class="pt-2">
            <button type="submit"
                    name="bimverdi_register_foretak"
                    value="1"
                    class="w-full px-6 py-3.5 bg-[#FF8B5E] text-white font-semibold rounded-lg hover:bg-[#E07A52] transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#FF8B5E]">
                Registrer foretak
            </button>
        </div>
    </form>

    <!-- Help Link -->
    <div class="mt-6 text-center text-sm text-[#57534E]">
        <p>Trenger du hjelp? Kontakt oss på
            <a href="mailto:post@bimverdi.no" class="text-[#FF8B5E] hover:underline">post@bimverdi.no</a>
        </p>
    </div>

</div>
