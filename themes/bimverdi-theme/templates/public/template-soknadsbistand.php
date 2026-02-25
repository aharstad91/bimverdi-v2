<?php
/**
 * Template Name: Søknadsbistand
 *
 * Public page for submitting project ideas (leads generator)
 * Based on Innovasjon Norge "Rask avklaring" structure
 *
 * @package BIMVerdi
 */

get_header();

// Check if user is logged in (for pre-population)
$is_logged_in = is_user_logged_in();
$current_user = $is_logged_in ? wp_get_current_user() : null;

// Success/error handling
$submitted = isset($_GET['submitted']) && $_GET['submitted'] === '1';
$error = isset($_GET['bv_error']) ? sanitize_text_field($_GET['bv_error']) : '';
$error_messages = [
    'nonce'           => 'Skjemaet utløp. Vennligst prøv igjen.',
    'rate_limit'      => 'For mange forsøk. Vennligst vent litt før du prøver igjen.',
    'missing_fields'  => 'Vennligst fyll ut alle obligatoriske felt.',
    'invalid_email'   => 'Vennligst oppgi en gyldig e-postadresse.',
    'missing_consent' => 'Du må samtykke til personvern for å sende inn skjemaet.',
];
$error_text = $error_messages[$error] ?? '';
?>

<div class="bg-gradient-to-b from-orange-50 to-white">
    <!-- Hero section -->
    <div class="container mx-auto px-4 py-12 lg:py-16">
        <div class="max-w-3xl mx-auto text-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#F97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg>
            <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                Søknadsbistand for prosjektidéer
            </h1>
            <p class="text-lg text-gray-600 mb-8">
                Har du en innovativ prosjektidé innen BIM og digitalisering? Vi hjelper deg med å vurdere potensialet og søke støtte fra virkemiddelapparatet.
            </p>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">

        <div class="grid lg:grid-cols-3 gap-8">

            <!-- Main form column -->
            <div class="lg:col-span-2">
                <div class="bg-white border border-[#E7E5E4] rounded-xl p-6 lg:p-8">

                    <?php if ($submitted): ?>
                        <!-- Success message -->
                        <div class="text-center py-8">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">Takk for din prosjektidé!</h2>
                            <p class="text-gray-600 mb-6">Vi har mottatt skjemaet ditt og tar kontakt innen 5 virkedager for en uforpliktende vurdering.</p>
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#FF8B5E] text-white text-sm font-semibold rounded-lg hover:bg-[#e87a4f] transition-colors">
                                Tilbake til forsiden
                            </a>
                        </div>
                    <?php else: ?>
                        <h2 class="text-xl font-semibold text-gray-900 mb-2">Send inn din prosjektidé</h2>
                        <p class="text-gray-600 mb-6">Fyll ut skjemaet under, så tar vi kontakt for en uforpliktende vurdering.</p>

                        <?php if ($error_text): ?>
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <p class="text-red-800 text-sm"><?php echo esc_html($error_text); ?></p>
                        </div>
                        <?php endif; ?>

                        <form method="post" action="" class="space-y-5">
                            <?php wp_nonce_field('bimverdi_soknadsbistand'); ?>
                            <input type="hidden" name="bimverdi_soknadsbistand" value="1">
                            <!-- Honeypot -->
                            <div style="position:absolute;left:-9999px;" aria-hidden="true">
                                <input type="text" name="bv_website_url" tabindex="-1" autocomplete="off">
                            </div>

                            <!-- Kontaktinformasjon -->
                            <div class="border-b border-[#E7E5E4] pb-2 mb-4">
                                <h3 class="text-base font-semibold text-gray-900">Kontaktinformasjon</h3>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <!-- Navn -->
                                <div>
                                    <label for="navn" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                                        Navn <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="navn" name="navn" required
                                           value="<?php echo $current_user ? esc_attr($current_user->display_name) : ''; ?>"
                                           class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                                </div>

                                <!-- E-post -->
                                <div>
                                    <label for="epost" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                                        E-post <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" id="epost" name="epost" required
                                           value="<?php echo $current_user ? esc_attr($current_user->user_email) : ''; ?>"
                                           class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <!-- Telefon -->
                                <div>
                                    <label for="telefon" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                                        Telefon
                                    </label>
                                    <input type="tel" id="telefon" name="telefon"
                                           placeholder="+47 123 45 678"
                                           class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                                </div>

                                <!-- Bedrift -->
                                <div>
                                    <label for="bedrift" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                                        Bedrift
                                    </label>
                                    <input type="text" id="bedrift" name="bedrift"
                                           class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                                </div>
                            </div>

                            <!-- Prosjektidé -->
                            <div class="border-b border-[#E7E5E4] pb-2 mb-4 mt-8">
                                <h3 class="text-base font-semibold text-gray-900">Om prosjektidéen</h3>
                            </div>

                            <!-- Prosjekttittel -->
                            <div>
                                <label for="prosjekt_tittel" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                                    Prosjekttittel <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="prosjekt_tittel" name="prosjekt_tittel" required
                                       placeholder="Gi prosjektidéen en kort tittel"
                                       class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                            </div>

                            <!-- Beskrivelse -->
                            <div>
                                <label for="prosjekt_beskrivelse" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                                    Beskrivelse <span class="text-red-500">*</span>
                                </label>
                                <textarea id="prosjekt_beskrivelse" name="prosjekt_beskrivelse" rows="6" required
                                          placeholder="Beskriv prosjektidéen din. Hva ønsker du å oppnå? Hvilken utfordring løser det?"
                                          class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent resize-y"></textarea>
                                <p class="mt-1 text-xs text-[#888888]">Beskriv kort hva prosjektet handler om, mål og forventet effekt.</p>
                            </div>

                            <!-- Samtykke -->
                            <div class="mt-6">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="samtykke" value="1" required
                                           class="mt-0.5 w-4 h-4 rounded border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                                    <span class="text-sm text-[#5A5A5A]">
                                        Jeg samtykker til at BIM Verdi behandler mine opplysninger for å vurdere prosjektidéen og ta kontakt.
                                        <a href="<?php echo esc_url(home_url('/personvern/')); ?>" target="_blank" class="text-[#FF8B5E] hover:underline">Les personvernerklæringen</a>.
                                    </span>
                                </label>
                            </div>

                            <!-- Submit -->
                            <div class="pt-4">
                                <button type="submit"
                                        class="w-full sm:w-auto px-8 py-3 bg-[#FF8B5E] text-white text-base font-semibold rounded-lg hover:bg-[#e87a4f] transition-colors">
                                    Send inn prosjektidé
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if ($is_logged_in && !$submitted) : ?>
                    <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <p class="text-green-800 text-sm">
                            <strong>Du er innlogget!</strong> Dine kontaktopplysninger er forhåndsutfylt.
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">

                <!-- What we help with -->
                <div class="bg-white border border-[#E7E5E4] rounded-xl p-5">
                    <h3 class="font-semibold text-gray-900 mb-4">Hva vi hjelper med</h3>
                    <ul class="space-y-3 text-sm text-gray-600">
                        <li class="flex items-start gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Vurdering av prosjektidéens innovasjonspotensial</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Identifisering av relevante støtteordninger</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Kobling til potensielle samarbeidspartnere</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Bistand med søknadsprosessen</span>
                        </li>
                    </ul>
                </div>

                <!-- Funding sources -->
                <div class="bg-white border border-[#E7E5E4] rounded-xl p-5">
                    <h3 class="font-semibold text-gray-900 mb-4">Støtteordninger</h3>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            Innovasjon Norge
                        </li>
                        <li class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            Forskningsrådet
                        </li>
                        <li class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            SkatteFUNN
                        </li>
                        <li class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            Enova
                        </li>
                        <li class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            EU Horizon Europe
                        </li>
                        <li class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            Regionale fond
                        </li>
                    </ul>
                </div>

                <!-- Process -->
                <div class="bg-white border border-[#E7E5E4] rounded-xl p-5">
                    <h3 class="font-semibold text-gray-900 mb-4">Prosessen</h3>
                    <ol class="space-y-4 text-sm">
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-semibold text-xs">1</span>
                            <div>
                                <strong class="text-gray-900">Send inn idé</strong>
                                <p class="text-gray-600">Beskriv prosjektet ditt kort</p>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-semibold text-xs">2</span>
                            <div>
                                <strong class="text-gray-900">Vi tar kontakt</strong>
                                <p class="text-gray-600">Innen 5 virkedager</p>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-semibold text-xs">3</span>
                            <div>
                                <strong class="text-gray-900">Uforpliktende samtale</strong>
                                <p class="text-gray-600">Vurderer muligheter sammen</p>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-semibold text-xs">4</span>
                            <div>
                                <strong class="text-gray-900">Videre samarbeid</strong>
                                <p class="text-gray-600">Søknad og gjennomføring</p>
                            </div>
                        </li>
                    </ol>
                </div>

                <!-- Contact -->
                <div class="bg-white border border-[#E7E5E4] rounded-xl p-5 text-center">
                    <h3 class="font-semibold text-gray-900">Spørsmål?</h3>
                    <p class="text-sm text-gray-600 mb-3">Ta gjerne kontakt direkte</p>
                    <a href="mailto:post@bimverdi.no" class="text-[#FF8B5E] hover:underline text-sm font-medium">
                        post@bimverdi.no
                    </a>
                </div>

            </div>
        </div>

    </div>
</div>

<?php get_footer(); ?>
