<?php
/**
 * Template Name: Registrer (Email Signup)
 * 
 * Første steg i registrering - kun e-post.
 * Sender verifiseringslenke til brukerens e-post.
 * 
 * @package BIMVerdi
 */

get_header();

// If user is already logged in, redirect to min-side
if (is_user_logged_in()) {
    wp_redirect(home_url('/min-side/'));
    exit;
}
?>

<main class="min-h-screen bg-gradient-to-br from-amber-50 to-orange-50 py-12 px-4">
    <div class="max-w-md mx-auto">
        
        <!-- Logo/Brand -->
        <div class="text-center mb-8">
            <a href="<?php echo home_url('/'); ?>" class="inline-block">
                <span class="text-3xl font-bold text-gray-900">🏗️ BIM Verdi</span>
            </a>
            <p class="text-gray-600 mt-2">Bli en del av Norges BIM-nettverk</p>
        </div>
        
        <!-- Main Card -->
        <wa-card class="shadow-lg">
            <div class="p-8">
                
                <div class="text-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">
                        Kom i gang
                    </h1>
                    <p class="text-gray-600 mt-2">
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
                        echo '<wa-alert variant="danger" open>';
                        echo '<wa-icon slot="icon" name="exclamation-triangle" library="fa"></wa-icon>';
                        echo 'Gravity Forms er ikke aktivert. Kontakt administrator.';
                        echo '</wa-alert>';
                    }
                    ?>
                </div>
                
                <!-- Divider -->
                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                </div>
                
                <!-- Help Links -->
                <div class="space-y-3 text-sm">
                    <p class="flex items-center gap-2 text-gray-600">
                        <wa-icon name="user" library="fa" class="text-gray-400"></wa-icon>
                        Har du allerede en konto? 
                        <a href="<?php echo wp_login_url(home_url('/min-side/')); ?>" class="text-orange-600 hover:text-orange-700 font-medium">
                            Logg inn
                        </a>
                    </p>
                    
                    <p class="flex items-center gap-2 text-gray-600">
                        <wa-icon name="key" library="fa" class="text-gray-400"></wa-icon>
                        Glemt passord? 
                        <a href="<?php echo wp_lostpassword_url(); ?>" class="text-orange-600 hover:text-orange-700 font-medium">
                            Tilbakestill
                        </a>
                    </p>
                    
                    <p class="flex items-center gap-2 text-gray-600">
                        <wa-icon name="envelope" library="fa" class="text-gray-400"></wa-icon>
                        Fikk du ikke e-post? 
                        <a href="#" class="text-orange-600 hover:text-orange-700 font-medium" onclick="document.querySelector('.gform_wrapper form').reset(); return false;">
                            Send på nytt
                        </a>
                    </p>
                </div>
                
            </div>
        </wa-card>
        
        <!-- Benefits Section -->
        <div class="mt-8 grid grid-cols-1 gap-4">
            <div class="flex items-start gap-3 p-4 bg-white/60 rounded-lg">
                <div class="flex-shrink-0 w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                    <wa-icon name="users" library="fa" class="text-orange-600"></wa-icon>
                </div>
                <div>
                    <h3 class="font-medium text-gray-900">Nettverk</h3>
                    <p class="text-sm text-gray-600">Bli kjent med andre BIM-aktører i Norge</p>
                </div>
            </div>
            
            <div class="flex items-start gap-3 p-4 bg-white/60 rounded-lg">
                <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                    <wa-icon name="tools" library="fa" class="text-purple-600"></wa-icon>
                </div>
                <div>
                    <h3 class="font-medium text-gray-900">Verktøy</h3>
                    <p class="text-sm text-gray-600">Utforsk og del BIM-verktøy</p>
                </div>
            </div>
            
            <div class="flex items-start gap-3 p-4 bg-white/60 rounded-lg">
                <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                    <wa-icon name="calendar-alt" library="fa" class="text-green-600"></wa-icon>
                </div>
                <div>
                    <h3 class="font-medium text-gray-900">Arrangementer</h3>
                    <p class="text-sm text-gray-600">Delta på workshops og meetups</p>
                </div>
            </div>
        </div>
        
        <!-- Footer Links -->
        <div class="text-center mt-8 text-sm text-gray-500">
            <p>
                Ved å registrere deg godtar du våre 
                <a href="<?php echo home_url('/vilkar/'); ?>" class="text-orange-600 hover:underline">vilkår</a>
                og 
                <a href="<?php echo home_url('/personvern/'); ?>" class="text-orange-600 hover:underline">personvernerklæring</a>.
            </p>
        </div>
        
    </div>
</main>

<style>
/* Custom styles for the email signup form */
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
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.bimverdi-email-signup-wrapper .gform_wrapper input[type="email"],
.bimverdi-email-signup-wrapper .gform_wrapper input[type="text"] {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 1px solid #D1D5DB;
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.bimverdi-email-signup-wrapper .gform_wrapper input[type="email"]:focus,
.bimverdi-email-signup-wrapper .gform_wrapper input[type="text"]:focus {
    outline: none;
    border-color: #F97316;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
}

.bimverdi-email-signup-wrapper .gform_wrapper .gfield_description {
    font-size: 0.75rem;
    color: #6B7280;
    margin-top: 0.375rem;
}

.bimverdi-email-signup-wrapper .gform_wrapper .gform_footer {
    margin-top: 1.25rem;
    padding: 0;
}

.bimverdi-email-signup-wrapper .gform_wrapper .gform_button {
    width: 100%;
    padding: 0.875rem 1.5rem;
    background-color: #F97316;
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s, transform 0.1s;
}

.bimverdi-email-signup-wrapper .gform_wrapper .gform_button:hover {
    background-color: #EA580C;
}

.bimverdi-email-signup-wrapper .gform_wrapper .gform_button:active {
    transform: scale(0.98);
}

/* Validation errors */
.bimverdi-email-signup-wrapper .gform_wrapper .gfield_error input {
    border-color: #EF4444;
}

.bimverdi-email-signup-wrapper .gform_wrapper .validation_message {
    color: #EF4444;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.bimverdi-email-signup-wrapper .gform_wrapper .gform_validation_errors {
    background-color: #FEF2F2;
    border: 1px solid #FECACA;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.bimverdi-email-signup-wrapper .gform_wrapper .gform_validation_errors h2 {
    color: #DC2626;
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
.bimverdi-email-signup-wrapper .gform_wrapper.gform_validation_error .gform_ajax_spinner,
.bimverdi-email-signup-wrapper .gform_wrapper .gform_ajax_spinner {
    margin-left: 10px;
}
</style>

<?php get_footer(); ?>
