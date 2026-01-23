<?php
/**
 * Template Name: Aktiver Konto
 *
 * Side for å fullføre brukerregistrering etter email-verifisering.
 * URL-format: /aktiver-konto/?email=xxx&token=xxx
 *
 * UI Contract: Variant B (beige bakgrunn, ingen gradient)
 *
 * @package BIMVerdi
 */

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
    if (class_exists('BIMVerdi_Email_Verification')) {
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

get_header();
?>

<main class="min-h-screen bg-[#F7F5EF] py-12 px-4">
    <div class="max-w-md mx-auto">

        <!-- Logo/Brand -->
        <div class="text-center mb-8">
            <a href="<?php echo home_url('/'); ?>" class="inline-block">
                <span class="text-2xl font-bold text-[#1A1A1A]">BIM Verdi</span>
            </a>
        </div>

        <!-- Main Card -->
        <div class="bg-white border border-[#E5E0D5] rounded-lg">
            <div class="p-8">

                <?php if ($is_valid): ?>
                    <!-- Valid Token - Show Form -->

                    <div class="text-center mb-6">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-[#1A1A1A]">
                            Fullfør registreringen
                        </h1>
                        <p class="text-[#5A5A5A] mt-2">
                            Fortell oss litt om deg selv og velg et passord.
                        </p>
                    </div>

                    <!-- Email Display (locked) -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-[#1A1A1A] mb-2">E-postadresse</label>
                        <div class="flex items-center gap-3 p-3 bg-[#F7F5EF] rounded-lg border border-[#E5E0D5]">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#5A5A5A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="5" width="18" height="14" rx="2"/>
                                <polyline points="3 7 12 13 21 7"/>
                            </svg>
                            <span class="text-[#1A1A1A]"><?php echo esc_html($email); ?></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#888888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-auto">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
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
                            echo '<div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">';
                            echo 'Gravity Forms er ikke aktivert. Kontakt administrator.';
                            echo '</div>';
                        }
                        ?>
                    </div>

                <?php else: ?>
                    <!-- Invalid Token - Show Error -->

                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-6">
                            <?php if ($error_code === 'expired'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            <?php elseif ($error_code === 'already_used'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="8" x2="12" y2="12"/>
                                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                            <?php endif; ?>
                        </div>

                        <h1 class="text-2xl font-bold text-[#1A1A1A] mb-4">
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

                        <p class="text-[#5A5A5A] mb-6">
                            <?php echo esc_html($error_message); ?>
                        </p>

                        <!-- Action buttons based on error type -->
                        <div class="space-y-3">
                            <?php if ($error_code === 'already_used'): ?>
                                <a href="<?php echo home_url('/logg-inn/'); ?>"
                                   class="w-full block text-center px-6 py-3 bg-[#1A1A1A] text-white font-medium rounded-lg hover:bg-[#333333] transition-colors">
                                    Logg inn
                                </a>
                            <?php else: ?>
                                <a href="<?php echo home_url('/registrer/'); ?>"
                                   class="w-full block text-center px-6 py-3 bg-[#1A1A1A] text-white font-medium rounded-lg hover:bg-[#333333] transition-colors">
                                    Registrer deg på nytt
                                </a>
                            <?php endif; ?>

                            <a href="<?php echo home_url('/'); ?>"
                               class="w-full block text-center px-6 py-3 border border-[#E5E0D5] text-[#1A1A1A] font-medium rounded-lg hover:bg-[#F7F5EF] transition-colors">
                                Gå til forsiden
                            </a>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </div>

        <!-- Help Links -->
        <div class="text-center mt-6 space-y-2">
            <p class="text-sm text-[#5A5A5A]">
                Har du allerede en konto?
                <a href="<?php echo home_url('/logg-inn/'); ?>" class="text-[#1A1A1A] hover:underline font-medium">
                    Logg inn
                </a>
            </p>
            <p class="text-sm text-[#888888]">
                Trenger du hjelp?
                <a href="<?php echo home_url('/kontakt/'); ?>" class="text-[#5A5A5A] hover:text-[#1A1A1A] hover:underline">
                    Kontakt oss
                </a>
            </p>
        </div>

    </div>
</main>

<style>
/* Custom styles for the verification form - UI Contract compliant */
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
    color: #1A1A1A;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.bimverdi-verify-form-wrapper .gform_wrapper input[type="text"],
.bimverdi-verify-form-wrapper .gform_wrapper input[type="password"] {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #E5E0D5;
    border-radius: 0.5rem;
    font-size: 1rem;
    color: #1A1A1A;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.bimverdi-verify-form-wrapper .gform_wrapper input[type="text"]:focus,
.bimverdi-verify-form-wrapper .gform_wrapper input[type="password"]:focus {
    outline: none;
    border-color: #1A1A1A;
    box-shadow: 0 0 0 2px rgba(26, 26, 26, 0.1);
}

.bimverdi-verify-form-wrapper .gform_wrapper .gfield_description {
    font-size: 0.75rem;
    color: #888888;
    margin-top: 0.25rem;
}

.bimverdi-verify-form-wrapper .gform_wrapper .gform_footer {
    margin-top: 1.5rem;
    padding: 0;
}

.bimverdi-verify-form-wrapper .gform_wrapper .gform_button {
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

.bimverdi-verify-form-wrapper .gform_wrapper .gform_button:hover {
    background-color: #333333;
}

.bimverdi-verify-form-wrapper .gform_wrapper .gform_button:focus {
    outline: none;
    box-shadow: 0 0 0 2px #F7F5EF, 0 0 0 4px #1A1A1A;
}

/* Password visibility toggle */
.bimverdi-verify-form-wrapper .gform_wrapper .gfield_password_visibility {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #888888;
}

.bimverdi-verify-form-wrapper .gform_wrapper .ginput_container_password {
    position: relative;
}

/* Validation errors */
.bimverdi-verify-form-wrapper .gform_wrapper .gfield_error input {
    border-color: #dc2626;
}

.bimverdi-verify-form-wrapper .gform_wrapper .validation_message {
    color: #dc2626;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

/* Hide hidden fields completely */
.bimverdi-verify-form-wrapper .gform_wrapper .gfield_visibility_hidden {
    display: none !important;
}
</style>

<?php get_footer(); ?>
