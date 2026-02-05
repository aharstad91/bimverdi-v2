<?php
/**
 * Template Name: Registrer Foretak
 * 
 * Registration page for new companies using Gravity Forms with BRreg autocomplete.
 * Uses Min Side layout without sidebar.
 * 
 * @package BimVerdi_Theme
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/logg-inn/?redirect_to=' . urlencode(get_permalink())));
    exit;
}

get_header();
?>

<style>
/* Gravity Forms Submit Button Styling */
.gform_wrapper .gform_button,
.gform_wrapper input[type="submit"] {
    background: #F97316 !important;
    color: white !important;
    padding: 0.875rem 2rem !important;
    border: none !important;
    border-radius: 0.5rem !important;
    font-size: 1rem !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    width: 100% !important;
    margin-top: 1rem !important;
}

.gform_wrapper .gform_button:hover,
.gform_wrapper input[type="submit"]:hover {
    background: #ea580c !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3) !important;
}

.gform_wrapper .gform_button:active,
.gform_wrapper input[type="submit"]:active {
    transform: translateY(0) !important;
}

/* Gravity Forms Field Styling */
.gform_wrapper .gfield_label {
    font-weight: 600 !important;
    color: #1f2937 !important;
    margin-bottom: 0.5rem !important;
}

.gform_wrapper input[type="text"],
.gform_wrapper input[type="url"],
.gform_wrapper textarea,
.gform_wrapper select {
    width: 100% !important;
    padding: 0.75rem 1rem !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 0.5rem !important;
    font-size: 1rem !important;
    transition: border-color 0.2s, box-shadow 0.2s !important;
}

.gform_wrapper input[type="text"]:focus,
.gform_wrapper input[type="url"]:focus,
.gform_wrapper textarea:focus,
.gform_wrapper select:focus {
    border-color: #F97316 !important;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1) !important;
    outline: none !important;
}

.gform_wrapper .gfield_description {
    font-size: 0.875rem !important;
    color: #6b7280 !important;
    margin-top: 0.25rem !important;
}

.gform_wrapper .gform_footer {
    margin-top: 1.5rem !important;
    padding-top: 1rem !important;
    border-top: 1px solid #e5e7eb !important;
}
</style>

<?php
$current_user = wp_get_current_user();

// Check if user already has a company
$existing_foretak_id = get_user_meta($current_user->ID, 'tilknyttet_foretak', true);
if ($existing_foretak_id && get_post_status($existing_foretak_id) === 'publish') {
    // Redirect to foretak page if already registered
    wp_redirect(home_url('/min-side/foretak/'));
    exit;
}
?>

<main class="bg-gray-50 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-2xl">
        
        <!-- Back navigation -->
        <div class="mb-6">
            <a href="<?php echo home_url('/min-side/'); ?>" class="inline-flex items-center gap-2 text-gray-600 hover:text-orange-600 transition-colors">
                <wa-icon name="arrow-left" library="fa"></wa-icon>
                <span>Tilbake til Min side</span>
            </a>
        </div>
        
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center text-white shadow-lg">
                    <wa-icon name="building" library="fa" style="font-size: 1.5rem;"></wa-icon>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Registrer foretak</h1>
                    <p class="text-gray-600">Koble ditt foretak til BIM Verdi nettverksportalen</p>
                </div>
            </div>
        </div>
        
        <!-- Registration Form Card -->
        <wa-card class="shadow-lg">
            <div slot="header" class="flex items-center gap-2 bg-gradient-to-r from-orange-50 to-white">
                <wa-icon name="search" library="fa" class="text-orange-500"></wa-icon>
                <strong>Søk i Brønnøysundregistrene</strong>
            </div>
            
            <div class="p-2">
                <!-- Info Alert -->
                <wa-alert variant="primary" open class="mb-6">
                    <wa-icon slot="icon" name="circle-info" library="fa"></wa-icon>
                    <strong>Tips:</strong> Start å skrive foretaksnavnet, så henter vi automatisk informasjon fra Brønnøysundregistrene.
                </wa-alert>
                
                <?php 
                if (function_exists('gravity_form')) {
                    // Form ID 2 = [Bruker] - Registrering av foretak
                    gravity_form(2, false, false, false, null, true, 12);
                } else {
                    echo '<wa-alert variant="danger" open>Gravity Forms er ikke aktivert.</wa-alert>';
                }
                ?>
            </div>
        </wa-card>
        
        <!-- Help Section -->
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>Har du allerede et registrert foretak? 
                <a href="<?php echo home_url('/koble-foretak/'); ?>" class="text-orange-600 hover:underline">
                    Koble til eksisterende foretak
                </a>
            </p>
        </div>
        
    </div>
</main>

<?php get_footer(); ?>
