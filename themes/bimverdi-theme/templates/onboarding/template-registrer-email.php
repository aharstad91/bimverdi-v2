<?php
/**
 * Template Name: Registrer (Email Signup)
 *
 * Første steg i registrering - kun e-post.
 * Sender verifiseringslenke til brukerens e-post.
 *
 * UI Contract: Variant B (beige bakgrunn, ingen gradient)
 *
 * @package BIMVerdi
 */

// If user is already logged in, redirect to min-side
if (is_user_logged_in()) {
    wp_redirect(home_url('/min-side/'));
    exit;
}

get_header();
?>

<main class="min-h-screen bg-[#F7F5EF] py-12 px-4">
    <div class="max-w-md mx-auto">

        <!-- Logo/Brand -->
        <div class="text-center mb-8">
            <a href="<?php echo home_url('/'); ?>" class="inline-block">
                <span class="text-2xl font-bold text-[#1A1A1A]">BIM Verdi</span>
            </a>
            <p class="text-[#5A5A5A] mt-2">Bli en del av Norges BIM-nettverk</p>
        </div>

        <!-- Main Card -->
        <div class="bg-white border border-[#E5E0D5] rounded-lg p-8">

            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-[#1A1A1A]">
                    Opprett konto
                </h1>
                <p class="text-[#5A5A5A] mt-2">
                    Oppgi e-postadressen din for å starte registreringen.
                </p>
            </div>

            <!-- Gravity Form -->
            <div class="bimverdi-email-signup-wrapper">
                <?php
                if (function_exists('gravity_form')) {
                    // Get form ID from settings (default: 5)
                    $email_form_id = (int) get_option('bimverdi_email_form_id', 5);

                    gravity_form(
                        $email_form_id,        // Form ID (Email Signup)
                        false,                 // Display title
                        false,                 // Display description
                        false,                 // Display inactive
                        null,                  // Field values
                        true,                  // AJAX
                        0,                     // Tab index
                        true                   // Echo
                    );
                } else {
                    echo '<div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">';
                    echo 'Gravity Forms er ikke aktivert. Kontakt administrator.';
                    echo '</div>';
                }
                ?>
            </div>

            <!-- Divider -->
            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-[#E5E0D5]"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-white text-[#888888]">Har du konto?</span>
                </div>
            </div>

            <!-- Login Link -->
            <a href="<?php echo home_url('/logg-inn/'); ?>"
               class="w-full block text-center px-6 py-3 border border-[#E5E0D5] text-[#1A1A1A] font-medium rounded-lg hover:bg-[#F7F5EF] transition-colors">
                Logg inn
            </a>

            <!-- Help Links -->
            <div class="mt-6 pt-6 border-t border-[#E5E0D5] space-y-3 text-sm">
                <p class="flex items-center gap-2 text-[#5A5A5A]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#888888]">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    Glemt passord?
                    <a href="<?php echo home_url('/glemt-passord/'); ?>" class="text-[#1A1A1A] hover:underline font-medium">
                        Tilbakestill
                    </a>
                </p>

                <p class="flex items-center gap-2 text-[#5A5A5A]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#888888]">
                        <rect x="3" y="5" width="18" height="14" rx="2"/>
                        <polyline points="3 7 12 13 21 7"/>
                    </svg>
                    Fikk du ikke e-post?
                    <a href="<?php echo home_url('/send-verifisering/'); ?>" class="text-[#1A1A1A] hover:underline font-medium">
                        Send på nytt
                    </a>
                </p>
            </div>

        </div>

        <!-- Benefits Section -->
        <div class="mt-8 grid grid-cols-1 gap-3">
            <div class="flex items-start gap-3 p-4 bg-white border border-[#E5E0D5] rounded-lg">
                <div class="flex-shrink-0 w-10 h-10 bg-[#F7F5EF] rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5A5A5A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-medium text-[#1A1A1A]">Nettverk</h3>
                    <p class="text-sm text-[#5A5A5A]">Bli kjent med andre BIM-aktører i Norge</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 bg-white border border-[#E5E0D5] rounded-lg">
                <div class="flex-shrink-0 w-10 h-10 bg-[#F7F5EF] rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5A5A5A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-medium text-[#1A1A1A]">Verktøy</h3>
                    <p class="text-sm text-[#5A5A5A]">Utforsk og del BIM-verktøy</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 bg-white border border-[#E5E0D5] rounded-lg">
                <div class="flex-shrink-0 w-10 h-10 bg-[#F7F5EF] rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5A5A5A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-medium text-[#1A1A1A]">Arrangementer</h3>
                    <p class="text-sm text-[#5A5A5A]">Delta på workshops og meetups</p>
                </div>
            </div>
        </div>

        <!-- Footer Links -->
        <div class="text-center mt-8 text-sm text-[#888888]">
            <p>
                Ved å registrere deg godtar du våre
                <a href="<?php echo home_url('/vilkar/'); ?>" class="text-[#5A5A5A] hover:text-[#1A1A1A] hover:underline">vilkår</a>
                og
                <a href="<?php echo home_url('/personvern/'); ?>" class="text-[#5A5A5A] hover:text-[#1A1A1A] hover:underline">personvernerklæring</a>.
            </p>
        </div>

    </div>
</main>

<style>
/* Custom styles for the email signup form - UI Contract compliant */
.bimverdi-email-signup-wrapper .gform_wrapper {
    margin: 0;
    padding: 0;
}

.bimverdi-email-signup-wrapper .gform_wrapper .gform_body {
    padding: 0;
}

.bimverdi-email-signup-wrapper .gform_wrapper .gfield {
    margin-bottom: 1rem;
}

.bimverdi-email-signup-wrapper .gform_wrapper .gfield_label {
    font-weight: 500;
    color: #1A1A1A;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.bimverdi-email-signup-wrapper .gform_wrapper input[type="email"],
.bimverdi-email-signup-wrapper .gform_wrapper input[type="text"] {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #E5E0D5;
    border-radius: 0.5rem;
    font-size: 1rem;
    color: #1A1A1A;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.bimverdi-email-signup-wrapper .gform_wrapper input[type="email"]:focus,
.bimverdi-email-signup-wrapper .gform_wrapper input[type="text"]:focus {
    outline: none;
    border-color: #1A1A1A;
    box-shadow: 0 0 0 2px rgba(26, 26, 26, 0.1);
}

.bimverdi-email-signup-wrapper .gform_wrapper .gfield_description {
    font-size: 0.75rem;
    color: #888888;
    margin-top: 0.375rem;
}

.bimverdi-email-signup-wrapper .gform_wrapper .gform_footer {
    margin-top: 1.5rem;
    padding: 0;
}

.bimverdi-email-signup-wrapper .gform_wrapper .gform_button {
    width: 100%;
    padding: 0.75rem 1.5rem;
    background-color: #1A1A1A;
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.bimverdi-email-signup-wrapper .gform_wrapper .gform_button:hover {
    background-color: #333333;
}

.bimverdi-email-signup-wrapper .gform_wrapper .gform_button:focus {
    outline: none;
    box-shadow: 0 0 0 2px #F7F5EF, 0 0 0 4px #1A1A1A;
}

/* Validation errors */
.bimverdi-email-signup-wrapper .gform_wrapper .gfield_error input {
    border-color: #dc2626;
}

.bimverdi-email-signup-wrapper .gform_wrapper .validation_message {
    color: #dc2626;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.bimverdi-email-signup-wrapper .gform_wrapper .gform_validation_errors {
    background-color: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.bimverdi-email-signup-wrapper .gform_wrapper .gform_validation_errors h2 {
    color: #dc2626;
    font-size: 0.875rem;
    font-weight: 600;
    margin: 0;
}

/* Success confirmation styles */
.bimverdi-signup-confirmation {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Loading state */
.bimverdi-email-signup-wrapper .gform_wrapper .gform_ajax_spinner {
    margin-left: 10px;
}
</style>

<?php get_footer(); ?>
