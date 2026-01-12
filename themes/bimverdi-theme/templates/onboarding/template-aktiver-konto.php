<?php
/**
 * Template Name: Aktiver Konto
 * 
 * Side for å fullføre brukerregistrering etter email-verifisering.
 * URL-format: /aktiver-konto/?email=xxx&token=xxx
 * 
 * @package BIMVerdi
 */

get_header();

// Get parameters from URL
$email = isset($_GET['email']) ? sanitize_email(urldecode($_GET['email'])) : '';
$token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

// Validate token
$is_valid = false;
$error_message = '';
$error_code = '';

if (empty($email) || empty($token)) {
    $error_message = 'Ugyldig lenke. Vennligst bruk lenken fra e-posten eller registrer deg på nytt.';
    $error_code = 'missing_params';
} else {
    // Use our verification system
    if (function_exists('BIMVerdi_Email_Verification')) {
        $verifier = new BIMVerdi_Email_Verification();
        $result = $verifier->verify_token($token, $email);
        $is_valid = $result['valid'];
        if (!$is_valid) {
            $error_message = $result['message'];
            $error_code = $result['code'];
        }
    } else {
        // Fallback validation
        global $wpdb;
        $table_name = $wpdb->prefix . 'bimverdi_pending_registrations';
        
        $pending = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE token = %s AND email = %s",
            $token, $email
        ));
        
        if (!$pending) {
            $error_message = 'Ugyldig verifiseringslenke. Vennligst registrer deg på nytt.';
            $error_code = 'invalid_token';
        } elseif ($pending->status !== 'pending') {
            $error_message = 'Denne lenken er allerede brukt. Vennligst logg inn eller registrer deg på nytt.';
            $error_code = 'already_used';
        } elseif (strtotime($pending->expires_at) < time()) {
            $error_message = 'Verifiseringslenken har utløpt. Vennligst registrer deg på nytt.';
            $error_code = 'expired';
        } else {
            $is_valid = true;
        }
    }
}

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
        </div>
        
        <!-- Main Card -->
        <wa-card class="shadow-lg">
            <div class="p-8">
                
                <?php if ($is_valid): ?>
                    <!-- Valid Token - Show Form -->
                    
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                            <wa-icon name="check-circle" library="fa" class="text-green-600 text-3xl"></wa-icon>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            Fullfør registreringen
                        </h1>
                        <p class="text-gray-600 mt-2">
                            Fortell oss litt om deg selv og velg et passord.
                        </p>
                    </div>
                    
                    <!-- Email Display (locked) -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">E-postadresse</label>
                        <div class="flex items-center gap-3 p-3 bg-gray-100 rounded-lg border border-gray-200">
                            <wa-icon name="envelope" library="fa" class="text-gray-500"></wa-icon>
                            <span class="text-gray-700"><?php echo esc_html($email); ?></span>
                            <wa-icon name="lock" library="fa" class="text-gray-400 ml-auto text-sm"></wa-icon>
                        </div>
                    </div>
                    
                    <!-- Gravity Form -->
                    <div class="bimverdi-verify-form-wrapper">
                        <?php 
                        if (function_exists('gravity_form')) {
                            // Get form ID from settings (default: 6)
                            $verify_form_id = (int) get_option('bimverdi_verify_form_id', 6);
                            
                            // Form with pre-populated email and token
                            gravity_form(
                                $verify_form_id,       // Form ID
                                false,                 // Display title
                                false,                 // Display description
                                false,                 // Display inactive
                                array(                 // Field values
                                    'email' => $email,
                                    'token' => $token,
                                ),
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
                    
                <?php else: ?>
                    <!-- Invalid Token - Show Error -->
                    
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-4">
                            <?php if ($error_code === 'expired'): ?>
                                <wa-icon name="clock" library="fa" class="text-red-600 text-3xl"></wa-icon>
                            <?php elseif ($error_code === 'already_used'): ?>
                                <wa-icon name="check-double" library="fa" class="text-amber-600 text-3xl"></wa-icon>
                            <?php else: ?>
                                <wa-icon name="exclamation-triangle" library="fa" class="text-red-600 text-3xl"></wa-icon>
                            <?php endif; ?>
                        </div>
                        
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">
                            <?php 
                            if ($error_code === 'expired') {
                                echo 'Lenken har utløpt';
                            } elseif ($error_code === 'already_used') {
                                echo 'Allerede verifisert';
                            } else {
                                echo 'Noe gikk galt';
                            }
                            ?>
                        </h1>
                        
                        <p class="text-gray-600 mb-6">
                            <?php echo esc_html($error_message); ?>
                        </p>
                        
                        <!-- Action buttons based on error type -->
                        <div class="space-y-3">
                            <?php if ($error_code === 'already_used'): ?>
                                <wa-button variant="brand" href="<?php echo wp_login_url(home_url('/min-side/')); ?>" class="w-full">
                                    <wa-icon slot="prefix" name="sign-in-alt" library="fa"></wa-icon>
                                    Logg inn
                                </wa-button>
                            <?php else: ?>
                                <wa-button variant="brand" href="<?php echo home_url('/registrer/'); ?>" class="w-full">
                                    <wa-icon slot="prefix" name="user-plus" library="fa"></wa-icon>
                                    Registrer deg på nytt
                                </wa-button>
                            <?php endif; ?>
                            
                            <wa-button variant="neutral" outline href="<?php echo home_url('/'); ?>" class="w-full">
                                <wa-icon slot="prefix" name="home" library="fa"></wa-icon>
                                Gå til forsiden
                            </wa-button>
                        </div>
                    </div>
                    
                <?php endif; ?>
                
            </div>
        </wa-card>
        
        <!-- Help Links -->
        <div class="text-center mt-6 space-y-2">
            <p class="text-sm text-gray-600">
                Har du allerede en konto? 
                <a href="<?php echo wp_login_url(); ?>" class="text-orange-600 hover:text-orange-700 font-medium">
                    Logg inn
                </a>
            </p>
            <p class="text-sm text-gray-500">
                Trenger du hjelp? 
                <a href="<?php echo home_url('/kontakt/'); ?>" class="text-orange-600 hover:text-orange-700">
                    Kontakt oss
                </a>
            </p>
        </div>
        
    </div>
</main>

<style>
/* Custom styles for the verification form */
.bimverdi-verify-form-wrapper .gform_wrapper {
    margin: 0;
    padding: 0;
}

.bimverdi-verify-form-wrapper .gform_wrapper .gform_body {
    padding: 0;
}

.bimverdi-verify-form-wrapper .gform_wrapper .gfield {
    margin-bottom: 1.25rem;
}

.bimverdi-verify-form-wrapper .gform_wrapper .gfield_label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.bimverdi-verify-form-wrapper .gform_wrapper input[type="text"],
.bimverdi-verify-form-wrapper .gform_wrapper input[type="password"] {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #D1D5DB;
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.bimverdi-verify-form-wrapper .gform_wrapper input[type="text"]:focus,
.bimverdi-verify-form-wrapper .gform_wrapper input[type="password"]:focus {
    outline: none;
    border-color: #F97316;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
}

.bimverdi-verify-form-wrapper .gform_wrapper .gfield_description {
    font-size: 0.75rem;
    color: #6B7280;
    margin-top: 0.25rem;
}

.bimverdi-verify-form-wrapper .gform_wrapper .gform_footer {
    margin-top: 1.5rem;
    padding: 0;
}

.bimverdi-verify-form-wrapper .gform_wrapper .gform_button {
    width: 100%;
    padding: 0.875rem 1.5rem;
    background-color: #F97316;
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.bimverdi-verify-form-wrapper .gform_wrapper .gform_button:hover {
    background-color: #EA580C;
}

/* Password visibility toggle */
.bimverdi-verify-form-wrapper .gform_wrapper .gfield_password_visibility {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #9CA3AF;
}

.bimverdi-verify-form-wrapper .gform_wrapper .ginput_container_password {
    position: relative;
}

/* Validation errors */
.bimverdi-verify-form-wrapper .gform_wrapper .gfield_error input {
    border-color: #EF4444;
}

.bimverdi-verify-form-wrapper .gform_wrapper .validation_message {
    color: #EF4444;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

/* Hide hidden fields completely */
.bimverdi-verify-form-wrapper .gform_wrapper .gfield_visibility_hidden {
    display: none !important;
}
</style>

<?php get_footer(); ?>
