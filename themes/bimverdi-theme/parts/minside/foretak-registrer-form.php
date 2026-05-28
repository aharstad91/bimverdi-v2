<?php
/**
 * Part: Foretak-registreringsskjema (form-only)
 *
 * Brukes både på den dedikerte /min-side/foretak/registrer/-siden
 * og inline på dashboardet når bruker har valgt foretak via Brreg-søk.
 *
 * Args:
 *   - preselected: ['orgnr' => string, 'navn' => string]  (optional)
 *     Når satt: foretaksnavn + orgnr vises som lest tekst, inputfeltene
 *     blir hidden og søkebanner-områdene skjules.
 *
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$preselected = $args['preselected'] ?? null;
$has_preselected = is_array($preselected) && !empty($preselected['orgnr']) && !empty($preselected['navn']);

// Når kalleren (f.eks. dashboard) allerede rendrer en pricing-tabell over skjemaet,
// skal vi ikke duplisere nivåvelgeren inni skjemaet. Default false → frittstående
// registrer-side fortsetter å vise en inline pricing-tabell når nivå ikke er valgt.
$level_picker_external = !empty($args['level_picker_external']);

// Two-step-flyt: ?nivaa=X velger deltakernivå før resten av skjemaet vises.
// Validér mot ACF-pricing-data så ukjente verdier ignoreres.
$valid_plan_keys = function_exists('bimverdi_pricing_valid_plan_keys')
    ? bimverdi_pricing_valid_plan_keys()
    : [];
$selected_nivaa = isset($_GET['nivaa']) ? sanitize_key($_GET['nivaa']) : '';
if ($selected_nivaa && !in_array($selected_nivaa, $valid_plan_keys, true)) {
    $selected_nivaa = '';
}
// Pricing-velger vises når brukeren ankommer den dedikerte registrer-siden
// uten valgt nivå. Inline-bruk fra dashboard (preselected fra BRREG-søk)
// beholder eksisterende radio-grid for å bevare sin egen kontekst.
$show_pricing_picker = !$has_preselected && empty($selected_nivaa);

// Error messages
$bv_error = isset($_GET['bv_error']) ? sanitize_text_field($_GET['bv_error']) : '';
$error_messages = [
    'nonce'             => 'Noe gikk galt. Vennligst prøv igjen.',
    'rate_limit'        => 'For mange forsøk. Vennligst vent litt før du prøver igjen.',
    'missing_name'      => 'Bedriftsnavn er påkrevd.',
    'invalid_orgnr'     => 'Organisasjonsnummer må være 9 siffer.',
    'missing_description' => 'Kort beskrivelse er påkrevd.',
    'missing_bransje'   => 'Velg minst én bransje/rolle.',
    'orgnr_exists'      => 'Dette organisasjonsnummeret er allerede registrert i BIM Verdi.',
    'invalid_file_type' => 'Ugyldig filtype. Bruk JPG, PNG, GIF, WebP eller SVG.',
    'file_too_large'    => 'Filen er for stor. Maks 2 MB.',
    'upload_failed'     => 'Opplasting av logo feilet. Prøv igjen.',
    'invalid_type'      => 'Ugyldig deltakertype. Vennligst velg et abonnement.',
    'missing_terms'     => 'Du må akseptere betingelsene for å registrere foretaket.',
    'missing_invoice_email' => 'Faktura-e-post er påkrevd når EHF-faktura ikke brukes.',
    'missing_invoice_ref'   => 'Faktura-referanse er påkrevd.',
    'invalid_invoice_email' => 'Faktura-e-postadressen er ikke gyldig.',
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
?>

<?php if ($show_pricing_picker): ?>
<!-- Step 1 av two-step-flyt: bruker velger deltakernivå via pricing-blokka.
     Klikk «Velg» lander på samme URL med ?nivaa={plan_key} → step 2. -->
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <h2 class="text-base font-semibold text-[#1A1A1A] mb-2"><?php _e('Velg deltakernivå', 'bimverdi'); ?></h2>
        <p class="text-sm text-[#5A5A5A]">
            <?php _e('Klikk «Velg» for det nivået som passer foretaket ditt. Du fyller inn detaljene i neste steg.', 'bimverdi'); ?>
        </p>
    </div>
    <?php
    if (function_exists('bimverdi_pricing_table')) {
        echo bimverdi_pricing_table(null, [
            'cta_url_template' => '/min-side/foretak/registrer/?nivaa={plan_key}',
        ]);
    }
    ?>
</div>
<?php return; ?>
<?php endif; ?>

<!-- Form Container (960px centered per UI Contract) -->
<div class="max-w-3xl<?php echo $has_preselected ? '' : ' mx-auto'; ?>">

    <?php if (!$has_preselected): ?>
    <!-- Info Section (kun når bruker ikke har valgt foretak ennå) -->
    <div class="mb-8 p-4 bg-[#F5F5F4] rounded-lg">
        <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FF8B5E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            <p class="text-sm text-[#57534E]">
                <strong class="text-[#111827]">Tips:</strong> Start å skrive foretaksnavnet, så sjekker vi om dere allerede er deltaker.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($selected_nivaa): ?>
    <!-- Step 2 av two-step-flyt: bekreft valgt nivå + tilbud om endring. -->
    <div class="mb-6 flex items-start justify-between gap-4 p-4 bg-[#FFF8F5] border border-[#FF8B5E]/30 rounded-lg">
        <div>
            <p class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide mb-1"><?php _e('Valgt deltakernivå', 'bimverdi'); ?></p>
            <p class="text-base font-semibold text-[#1A1A1A]">
                <?php echo esc_html(function_exists('bimverdi_pricing_plan_title') ? bimverdi_pricing_plan_title($selected_nivaa) : $selected_nivaa); ?>
            </p>
        </div>
        <a href="<?php echo esc_url(home_url('/min-side/foretak/registrer/')); ?>" class="text-sm text-[#FF8B5E] hover:underline whitespace-nowrap">
            <?php _e('Endre nivå', 'bimverdi'); ?>
        </a>
    </div>
    <?php endif; ?>

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
    <form method="post" action="<?php echo esc_url(home_url('/min-side/foretak/registrer/')); ?>" enctype="multipart/form-data" class="space-y-6">
        <?php wp_nonce_field('bimverdi_register_foretak'); ?>

        <!-- Honeypot -->
        <div style="position: absolute; left: -9999px;" aria-hidden="true">
            <label for="bv_website_url">Ikke fyll ut dette feltet</label>
            <input type="text" name="bv_website_url" id="bv_website_url" value="" tabindex="-1" autocomplete="off">
        </div>

        <?php if ($has_preselected): ?>
        <!-- Pre-selected foretak (fra Brreg-søk i forrige steg) -->
        <div class="p-4 bg-[#F7F5EF] border border-[#D6D1C6] rounded-lg">
            <p class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide mb-1"><?php _e('Valgt foretak', 'bimverdi'); ?></p>
            <p class="text-base font-semibold text-[#1A1A1A]"><?php echo esc_html($preselected['navn']); ?></p>
            <p class="text-xs text-[#888888] mt-0.5"><?php printf(__('Org.nr: %s', 'bimverdi'), esc_html($preselected['orgnr'])); ?></p>
        </div>
        <input type="hidden" name="bedriftsnavn" value="<?php echo esc_attr($preselected['navn']); ?>">
        <input type="hidden" name="organisasjonsnummer" value="<?php echo esc_attr($preselected['orgnr']); ?>">
        <?php else: ?>
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

        <!-- Existing deltaker check message (hidden by default, shown via JS) -->
        <div id="bv-foretak-exists-msg" class="hidden p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16A085" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                <div>
                    <p class="text-sm font-semibold text-green-800">Gode nyheter!</p>
                    <p class="text-sm text-green-700 mt-1">Ditt foretak er allerede deltaker i BIM Verdi og du er lagt inn som tilleggskontakt. Hovedkontakten <strong id="bv-hovedkontakt-name"></strong> er den som kan redigere foretaksprofilen.</p>
                    <a id="bv-foretak-link" href="#" class="inline-flex items-center gap-1 mt-3 text-sm font-medium text-[#FF8B5E] hover:underline">
                        Gå til foretaksprofilen →
                    </a>
                </div>
            </div>
        </div>

        <div id="bv-foretak-not-exists-msg" class="hidden p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2E86DE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                <div>
                    <p class="text-sm text-blue-800"><strong id="bv-foretak-check-name"></strong> er ikke registrert som deltaker i BIM Verdi.</p>
                    <p class="text-sm text-blue-700 mt-1"><strong>Du kan velge «Gratis brukerforetak» under dersom du ønsker å melde deg på et åpent arrangement, registrere kunnskap etc. Eller du kan velge blant deltakernivåene under og vi registrerer deg som hovedkontakt. Da vil du fritt kunne registrere kollegaer som tilleggskontakter. Velg «Avslutt foretaksregistrering» hvis du ikke ønsker å gå videre.</strong></p>
                </div>
            </div>
            <div class="mt-3 ml-8">
                <a href="<?php echo esc_url(home_url('/min-side/')); ?>"
                   class="inline-flex items-center gap-2 text-sm font-medium text-[#57534E] hover:text-[#1A1A1A] transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    Avslutt foretaksregistrering
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Del 2: Registration fields (hidden until org check, ALLTID synlig når preselected) -->
        <div id="bv-registration-fields" class="space-y-6"<?php echo $has_preselected ? '' : ''; ?>>

        <?php if ($selected_nivaa): ?>
        <!-- Two-step-flyt: nivå er allerede valgt fra pricing-blokka. Hidden input
             erstatter radio-griden; eksisterende JS leser denne ved page-load. -->
        <input type="hidden" name="deltakertype" value="<?php echo esc_attr($selected_nivaa); ?>">
        <?php elseif ($level_picker_external): ?>
        <!-- Dashboard rendrer pricing-tabellen over skjemaet; vi minner bare brukeren på å bruke den. -->
        <div class="p-4 bg-[#FFF8F5] border border-[#FF8B5E]/30 rounded-lg flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FF8B5E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            <p class="text-sm text-[#1A1A1A]">
                <strong><?php _e('Velg deltakernivå i tabellen over', 'bimverdi'); ?></strong> <?php _e('— klikk «Velg» for å fortsette registreringen.', 'bimverdi'); ?>
            </p>
        </div>
        <?php else: ?>
        <!-- Frittstående registrer-side: render pricing-tabellen inline så bruker kan velge nivå. -->
        <fieldset>
            <legend class="text-sm font-semibold text-[#1A1A1A] mb-1">
                <?php _e('Velg deltakernivå', 'bimverdi'); ?> <span class="text-red-600">*</span>
            </legend>
            <p class="text-xs text-[#888888] mb-3"><?php _e('Klikk «Velg» for nivået som passer foretaket ditt.', 'bimverdi'); ?></p>
            <?php
            if (function_exists('bimverdi_pricing_table')) {
                echo bimverdi_pricing_table(null, [
                    'cta_url_template' => '/min-side/foretak/registrer/?nivaa={plan_key}',
                ]);
            }
            ?>
        </fieldset>
        <?php endif; ?>

        <!-- Seksjoner som skjules for gratis brukerforetak -->
        <div id="bv-section-beskrivelse">
        <!-- Divider -->
        <hr class="border-[#E5E0D5]">

        <!-- Description -->
        <div>
            <label for="beskrivelse" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                Virksomhetsbeskrivelse <span class="text-red-600">*</span>
            </label>
            <textarea id="beskrivelse"
                      name="beskrivelse"
                      required
                      rows="4"
                      placeholder="Kort beskrivelse av foretaket..."
                      class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent text-[#1A1A1A] placeholder:text-[#A8A29E] resize-y"></textarea>
        </div>
        </div>

        <div id="bv-section-logo">
        <!-- Logo -->
        <div>
            <label for="logo" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                Logo
            </label>
            <input type="file"
                   id="logo"
                   name="logo"
                   accept="image/jpeg,image/png,image/gif,image/webp"
                   class="w-full text-sm text-[#5A5A5A] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border file:border-[#E5E0D5] file:text-sm file:font-medium file:bg-gray-100 file:text-[#1A1A1A] hover:file:bg-gray-200 file:cursor-pointer file:transition-colors">
            <p class="mt-1 text-xs text-[#888888]">JPG, PNG, GIF eller WebP. Maks 2 MB.</p>
        </div>
        </div>

        <div id="bv-section-adresse">
        <!-- Divider -->
        <hr class="border-[#E5E0D5]">

        <!-- Adresse hentes nå automatisk fra Brønnøysundregistrene server-side
             (T6 — Bård 2026-05-06: «adresse er allerede i BRREG»). Skjemaet
             trenger ikke å spørre brukeren. Hovedkontakt kan justere via
             Rediger foretak hvis BRREG har feil verdi. -->

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
        </div>

        <div id="bv-section-bransje">
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
        </div>

        <div id="bv-section-faktura">
        <!-- Fakturainformasjon -->
        <div>
            <h3 class="text-base font-semibold text-[#1A1A1A] mb-4">Fakturainformasjon</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                        Bruker foretaket EHF-faktura? <span class="text-red-600">*</span>
                    </label>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="ehf_faktura" value="ja"
                                   class="w-4 h-4 border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                            <span class="text-sm text-[#1A1A1A]">Ja</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="ehf_faktura" value="nei" checked
                                   class="w-4 h-4 border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                            <span class="text-sm text-[#1A1A1A]">Nei</span>
                        </label>
                    </div>
                </div>
                <div id="bv-faktura-epost-wrapper">
                    <label for="faktura_epost" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                        Faktura-e-post <span class="text-red-600">*</span>
                    </label>
                    <input type="email"
                           id="faktura_epost"
                           name="faktura_epost"
                           placeholder="faktura@firma.no"
                           class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent text-[#1A1A1A] placeholder:text-[#A8A29E]">
                    <p class="mt-1 text-xs text-[#888888]">E-postadresse som faktura sendes til. Påkrevd når EHF ikke brukes.</p>
                </div>
                <div>
                    <label for="faktura_referanse" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                        Faktura-referanse / prosjektnummer <span class="text-red-600">*</span>
                    </label>
                    <input type="text"
                           id="faktura_referanse"
                           name="faktura_referanse"
                           maxlength="100"
                           placeholder=""
                           class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent text-[#1A1A1A] placeholder:text-[#A8A29E]">
                    <p class="mt-1 text-xs text-[#888888]">Brukes for fakturaadressering. Kan være prosjektnummer eller intern referanse.</p>
                </div>
            </div>
        </div>
        </div>

        <div id="bv-section-betingelser">
        <!-- Divider -->
        <hr class="border-[#E5E0D5]">

        <!-- Aksept av betingelser -->
        <div>
            <?php if (function_exists('bimverdi_render_terms_acceptance_field')) {
                echo bimverdi_render_terms_acceptance_field('aksept_betingelser', $selected_nivaa);
            } else {
                ?>
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="aksept_betingelser" value="1" required
                           class="mt-0.5 w-4 h-4 rounded border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]">
                        Jeg aksepterer <a href="https://www.bimverdi.no/betingelser" target="_blank" class="text-[#FF8B5E] hover:underline">betingelsene</a> for deltakelse i BIM Verdi <span class="text-red-600">*</span>
                    </span>
                </label>
                <?php
            } ?>
        </div>
        </div>

        </div><!-- /bv-registration-fields -->

        <div id="bv-submit-section">
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
        </div><!-- /bv-submit-section -->
    </form>

    <!-- Help Link -->
    <div class="mt-6 text-center text-sm text-[#57534E]">
        <p>Trenger du hjelp? Kontakt oss på
            <a href="mailto:post@bimverdi.no" class="text-[#FF8B5E] hover:underline">post@bimverdi.no</a>
        </p>
    </div>

</div>

<script>
(function() {
  'use strict';
  document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('form[enctype]');
    if (!form) return;

    var gratisHiddenSectionIds = [
      'bv-section-beskrivelse', 'bv-section-logo', 'bv-section-adresse',
      'bv-section-bransje', 'bv-section-faktura'
      // bv-section-betingelser holdes synlig for ALLE (Bårds krav 2026-04-28)
    ];

    var conditionallyRequiredFields = form.querySelectorAll(
      '#beskrivelse, #faktura_referanse'
      // aksept_betingelser holdes required for ALLE (gratis + paid)
      // faktura_epost håndteres separat (conditional på EHF-state)
      // bransje_rolle[] håndteres separat (minst én av flere — håndteres
      // via syncBransjeRequired så vi ikke setter required på alle)
    );
    var bransjeCheckboxes = form.querySelectorAll('input[name="bransje_rolle[]"]');
    var submitButton = form.querySelector('button[type="submit"]');
    var originalButtonText = submitButton ? submitButton.textContent : '';
    var currentTier = null;

    // Faktura-epost wrapper + input (conditional på EHF=Nei)
    var fakturaEpostWrapper = document.getElementById('bv-faktura-epost-wrapper');
    var fakturaEpostInput = document.getElementById('faktura_epost');

    // Bransje-gruppa er "minst én" — sett required kun på første checkbox så
    // browseren stopper submit, og fjern når en hvilken som helst i gruppa
    // hukes av. (Hvis vi setter required på alle blir hver enkelt obligatorisk.)
    function syncBransjeRequired() {
      if (!bransjeCheckboxes.length) return;
      var first = bransjeCheckboxes[0];
      var isPaid = currentTier === 'paid';
      var anyChecked = false;
      for (var i = 0; i < bransjeCheckboxes.length; i++) {
        if (bransjeCheckboxes[i].checked) { anyChecked = true; break; }
      }
      if (isPaid && !anyChecked) {
        first.setAttribute('required', '');
      } else {
        first.removeAttribute('required');
      }
    }

    function syncFakturaEpostRequired() {
      if (!fakturaEpostInput || !fakturaEpostWrapper) return;
      var ehfChecked = form.querySelector('input[name="ehf_faktura"]:checked');
      var ehfNei = ehfChecked && ehfChecked.value === 'nei';
      var isGratis = currentTier === 'gratis';
      if (isGratis) {
        fakturaEpostInput.removeAttribute('required');
        fakturaEpostWrapper.style.display = '';
        return;
      }
      fakturaEpostWrapper.style.display = ehfNei ? '' : 'none';
      if (ehfNei) {
        fakturaEpostInput.setAttribute('required', '');
      } else {
        fakturaEpostInput.removeAttribute('required');
      }
    }

    function setTier(tier) {
      if (tier === currentTier) return;
      currentTier = tier;
      // 'pristine' = ingen valg gjort ennå. Skjuler paid-only-felter (faktura,
      // beskrivelse, logo, adresse, bransje) inntil bruker velger nivå.
      // 'gratis' = bruker har eksplisitt valgt gratis brukerforetak.
      // 'paid' = bruker har valgt deltaker/prosjektdeltaker/partner.
      var isGratis = tier === 'gratis';
      var isPristine = tier === 'pristine';
      var hideExtras = isGratis || isPristine;

      conditionallyRequiredFields.forEach(function(f) {
        if (hideExtras) {
          f.removeAttribute('required');
        } else {
          f.setAttribute('required', '');
        }
      });

      gratisHiddenSectionIds.forEach(function(id) {
        var s = document.getElementById(id);
        if (s) s.style.display = hideExtras ? 'none' : '';
      });

      if (submitButton) {
        // Endre kun knapp-tekst når bruker har gjort eksplisitt gratis-valg.
        // Pristine beholder original tekst så vi ikke pre-loader brukerens
        // valg via UI.
        submitButton.textContent = isGratis ? 'Registrer gratis foretak' : originalButtonText;
      }

      syncFakturaEpostRequired();
      syncBransjeRequired();
    }

    form.addEventListener('change', function(e) {
      if (e.target.name === 'deltakertype') {
        setTier(e.target.value === 'gratis' ? 'gratis' : 'paid');
      } else if (e.target.name === 'ehf_faktura') {
        syncFakturaEpostRequired();
      } else if (e.target.name === 'bransje_rolle[]') {
        syncBransjeRequired();
      }
    });

    window.addEventListener('pageshow', function(e) {
      if (e.persisted) {
        var checked = form.querySelector('input[name="deltakertype"]:checked');
        if (checked) setTier(checked.value === 'gratis' ? 'gratis' : 'paid');
      }
    });

    document.addEventListener('bv:registration-fields-shown', function() {
      if (currentTier) {
        var prev = currentTier;
        currentTier = null;
        setTier(prev);
      }
    });

    form.addEventListener('submit', function() {
      var checked = form.querySelector('input[name="deltakertype"]:checked')
                 || form.querySelector('input[type="hidden"][name="deltakertype"]');
      if (checked && checked.value === 'gratis') {
        conditionallyRequiredFields.forEach(function(f) { f.removeAttribute('required'); });
        if (bransjeCheckboxes.length) bransjeCheckboxes[0].removeAttribute('required');
      }
    });

    // Initialiser tier ved page-load:
    // - Hidden input (two-step-flyt fra pricing-blokka): bruker har valgt nivå.
    // - Allerede checket radio (pageshow fra back-cache, server-side error
    //   som rendrer skjemaet på nytt): bruker har valgt nivå.
    // - Verken/eller (dashboard inline BRREG-flyt): pristine — skjul paid-only-
    //   felter (faktura, beskrivelse, logo, adresse, bransje) til bruker velger.
    var hiddenTier = form.querySelector('input[type="hidden"][name="deltakertype"]');
    var checkedRadio = form.querySelector('input[type="radio"][name="deltakertype"]:checked');
    if (hiddenTier) {
      setTier(hiddenTier.value === 'gratis' ? 'gratis' : 'paid');
    } else if (checkedRadio) {
      setTier(checkedRadio.value === 'gratis' ? 'gratis' : 'paid');
    } else {
      setTier('pristine');
    }
  });
})();
</script>
