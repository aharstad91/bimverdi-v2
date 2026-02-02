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

// Check if user is logged in (for pre-population info)
$is_logged_in = is_user_logged_in();
$current_user = $is_logged_in ? wp_get_current_user() : null;

// Form ID for søknadsbistand
$form_id = 9; // "[Public] - Søknadsbistand prosjektidé"
?>

<div class="bg-gradient-to-b from-orange-50 to-white">
    <!-- Hero section -->
    <div class="container mx-auto px-4 py-12 lg:py-16">
        <div class="max-w-3xl mx-auto text-center">
            <wa-icon library="fa" name="fas-lightbulb" style="font-size: 3rem; color: #F97316; margin-bottom: 1rem;"></wa-icon>
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
                <wa-card>
                    <div class="p-6 lg:p-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-2">Send inn din prosjektidé</h2>
                        <p class="text-gray-600 mb-6">Fyll ut skjemaet under, så tar vi kontakt for en uforpliktende vurdering.</p>
                        
                        <?php 
                        // Check if Gravity Forms is available
                        if (function_exists('gravity_form')) {
                            // Check if form exists
                            $form = GFAPI::get_form($form_id);
                            if ($form && !is_wp_error($form)) {
                                gravity_form($form_id, false, false, false, null, true, 12);
                            } else {
                                // Form not created yet - show placeholder
                                ?>
                                <wa-alert variant="warning" open>
                                    <wa-icon library="fa" name="fas-triangle-exclamation" slot="icon"></wa-icon>
                                    <strong>Skjema ikke tilgjengelig</strong><br>
                                    Ta kontakt med oss på <a href="mailto:post@bimverdi.no">post@bimverdi.no</a> for å sende inn din prosjektidé.
                                </wa-alert>
                                
                                <p class="mt-4 text-sm text-gray-500">
                                    <em>Admininstrator: Opprett skjemaet ved å besøke 
                                    <a href="<?php echo admin_url('?bimverdi_create_leads_form=1'); ?>" class="text-orange-600 hover:underline">/wp-admin/?bimverdi_create_leads_form=1</a></em>
                                </p>
                                <?php
                            }
                        } else {
                            ?>
                            <wa-alert variant="danger" open>
                                <wa-icon library="fa" name="fas-circle-exclamation" slot="icon"></wa-icon>
                                Gravity Forms er ikke installert. Kontakt administrator.
                            </wa-alert>
                            <?php
                        }
                        ?>
                    </div>
                </wa-card>
                
                <?php if ($is_logged_in) : ?>
                    <div class="mt-4">
                        <wa-alert variant="success" open>
                            <wa-icon library="fa" name="fas-circle-check" slot="icon"></wa-icon>
                            <strong>Du er innlogget!</strong> Dine kontaktopplysninger er forhåndsutfylt, og du kan følge status på <a href="<?php echo esc_url(home_url('/min-side/')); ?>" class="underline">Min Side</a>.
                        </wa-alert>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- What we help with -->
                <wa-card>
                    <div class="p-5">
                        <h3 class="font-semibold text-gray-900 mb-4">
                            <wa-icon library="fa" name="fas-handshake" class="text-orange-500 mr-2"></wa-icon>
                            Hva vi hjelper med
                        </h3>
                        <ul class="space-y-3 text-sm text-gray-600">
                            <li class="flex items-start gap-2">
                                <wa-icon library="fa" name="fas-check" class="text-green-500 mt-1"></wa-icon>
                                <span>Vurdering av prosjektidéens innovasjonspotensial</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <wa-icon library="fa" name="fas-check" class="text-green-500 mt-1"></wa-icon>
                                <span>Identifisering av relevante støtteordninger</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <wa-icon library="fa" name="fas-check" class="text-green-500 mt-1"></wa-icon>
                                <span>Kobling til potensielle samarbeidspartnere</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <wa-icon library="fa" name="fas-check" class="text-green-500 mt-1"></wa-icon>
                                <span>Bistand med søknadsprosessen</span>
                            </li>
                        </ul>
                    </div>
                </wa-card>
                
                <!-- Funding sources -->
                <wa-card>
                    <div class="p-5">
                        <h3 class="font-semibold text-gray-900 mb-4">
                            <wa-icon library="fa" name="fas-coins" class="text-yellow-500 mr-2"></wa-icon>
                            Støtteordninger
                        </h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-center gap-2">
                                <wa-icon library="fa" name="fas-arrow-right" class="text-gray-400"></wa-icon>
                                Innovasjon Norge
                            </li>
                            <li class="flex items-center gap-2">
                                <wa-icon library="fa" name="fas-arrow-right" class="text-gray-400"></wa-icon>
                                Forskningsrådet
                            </li>
                            <li class="flex items-center gap-2">
                                <wa-icon library="fa" name="fas-arrow-right" class="text-gray-400"></wa-icon>
                                SkatteFUNN
                            </li>
                            <li class="flex items-center gap-2">
                                <wa-icon library="fa" name="fas-arrow-right" class="text-gray-400"></wa-icon>
                                Enova
                            </li>
                            <li class="flex items-center gap-2">
                                <wa-icon library="fa" name="fas-arrow-right" class="text-gray-400"></wa-icon>
                                EU Horizon Europe
                            </li>
                            <li class="flex items-center gap-2">
                                <wa-icon library="fa" name="fas-arrow-right" class="text-gray-400"></wa-icon>
                                Regionale fond
                            </li>
                        </ul>
                    </div>
                </wa-card>
                
                <!-- Process -->
                <wa-card>
                    <div class="p-5">
                        <h3 class="font-semibold text-gray-900 mb-4">
                            <wa-icon library="fa" name="fas-list-ol" class="text-blue-500 mr-2"></wa-icon>
                            Prosessen
                        </h3>
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
                </wa-card>
                
                <!-- Contact -->
                <wa-card>
                    <div class="p-5 text-center">
                        <wa-avatar size="large" initials="BV" style="--size: 60px; margin-bottom: 0.5rem;"></wa-avatar>
                        <h3 class="font-semibold text-gray-900">Spørsmål?</h3>
                        <p class="text-sm text-gray-600 mb-3">Ta gjerne kontakt direkte</p>
                        <a href="mailto:post@bimverdi.no" class="text-orange-600 hover:underline text-sm font-medium">
                            post@bimverdi.no
                        </a>
                    </div>
                </wa-card>
                
            </div>
        </div>
        
    </div>
</div>

<!-- Custom styles for Gravity Forms -->
<style>
    .bimverdi-leads-form .gform_wrapper {
        margin: 0;
    }
    
    .bimverdi-leads-form .gfield {
        margin-bottom: 1.5rem;
    }
    
    .bimverdi-leads-form .gfield_label {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }
    
    .bimverdi-leads-form .gfield_description {
        color: #6b7280;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    
    .bimverdi-leads-form input[type="text"],
    .bimverdi-leads-form input[type="email"],
    .bimverdi-leads-form input[type="tel"],
    .bimverdi-leads-form textarea,
    .bimverdi-leads-form select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 1rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    
    .bimverdi-leads-form input:focus,
    .bimverdi-leads-form textarea:focus,
    .bimverdi-leads-form select:focus {
        outline: none;
        border-color: #F97316;
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
    }
    
    .bimverdi-leads-form .gsection {
        margin: 2rem 0 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #F97316;
    }
    
    .bimverdi-leads-form .gsection_title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1f2937;
    }
    
    .bimverdi-leads-form .gsection_description {
        color: #6b7280;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    
    .bimverdi-leads-form .gform_button {
        background: #F97316 !important;
        color: white !important;
        padding: 0.875rem 2rem !important;
        border: none !important;
        border-radius: 0.5rem !important;
        font-size: 1rem !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        transition: background 0.2s !important;
    }
    
    .bimverdi-leads-form .gform_button:hover {
        background: #ea580c !important;
    }
    
    .bimverdi-leads-form .gf-member-cta {
        margin-top: 1.5rem;
    }
    
    .bimverdi-leads-form .validation_error {
        background: #fee2e2;
        border: 1px solid #fecaca;
        color: #b91c1c;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .bimverdi-leads-form .gfield_error input,
    .bimverdi-leads-form .gfield_error textarea,
    .bimverdi-leads-form .gfield_error select {
        border-color: #ef4444;
    }
    
    .bimverdi-leads-form .validation_message {
        color: #dc2626;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
</style>

<?php get_footer(); ?>
