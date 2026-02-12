<?php
/**
 * Auth Template: Resend Verification
 *
 * Request a new verification email for pending registrations.
 * URL: /send-verifisering/
 *
 * @package BIMVerdi
 */

// Redirect logged-in users
if (is_user_logged_in()) {
    wp_redirect(home_url('/min-side/'));
    exit;
}

get_header();

// Get messages from URL params
$error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
$success = isset($_GET['success']);
$email = isset($_GET['email']) ? sanitize_email($_GET['email']) : '';

// Error messages
$error_messages = [
    'invalid_email' => 'Vennligst oppgi en gyldig e-postadresse.',
    'nonce'         => 'Noe gikk galt. Vennligst prøv igjen.',
];

$error_message = $error_messages[$error] ?? '';
?>

<main class="min-h-screen bg-[#F7F5EF] py-12 px-4">
    <div class="max-w-md mx-auto">

        <!-- Logo/Brand -->
        <div class="text-center mb-8">
            <a href="<?php echo home_url('/'); ?>" class="inline-block">
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/bimverdi-logo.png'); ?>" alt="BIM Verdi" style="height: 58px; width: auto;">
            </a>
        </div>

        <!-- Main Card -->
        <div class="bg-white border border-[#E5E0D5] rounded-lg p-8">

            <?php if ($success): ?>
                <!-- Success State -->
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <polyline points="3 7 12 13 21 7"/>
                        </svg>
                    </div>

                    <h1 class="text-2xl font-bold text-[#1A1A1A] mb-4">
                        E-post sendt!
                    </h1>

                    <p class="text-[#5A5A5A] mb-6">
                        Hvis det finnes en ventende registrering med denne e-postadressen,
                        har vi sendt en ny verifiseringslenke.
                    </p>

                    <div class="p-4 bg-[#F7F5EF] border border-[#E5E0D5] rounded-lg text-left mb-6">
                        <p class="text-sm text-[#5A5A5A]">
                            <strong class="text-[#1A1A1A]">Finner du ikke e-posten?</strong>
                        </p>
                        <ul class="mt-2 space-y-1 text-sm text-[#5A5A5A]">
                            <li>• Sjekk søppelpost/spam-mappen</li>
                            <li>• Vent noen minutter og prøv igjen</li>
                        </ul>
                    </div>

                    <a href="<?php echo home_url('/logg-inn/'); ?>"
                       class="inline-block px-6 py-3 bg-[#1A1A1A] text-white font-medium rounded-lg hover:bg-[#333333] transition-colors">
                        Tilbake til innlogging
                    </a>
                </div>

            <?php else: ?>
                <!-- Form State -->
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-[#F7F5EF] rounded-full mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5A5A5A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.2 8.4c.5.38.8.97.8 1.6v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V10c0-.63.3-1.22.8-1.6"/>
                            <path d="m22 10-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 10"/>
                            <path d="M12 2v8"/>
                            <path d="m8 6 4 4 4-4"/>
                        </svg>
                    </div>

                    <h1 class="text-2xl font-bold text-[#1A1A1A]">
                        Send verifisering på nytt
                    </h1>
                    <p class="text-[#5A5A5A] mt-2">
                        Fikk du ikke verifiserings-e-posten? Oppgi e-postadressen din så sender vi en ny.
                    </p>
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

                <!-- Form -->
                <form method="post" action="" class="space-y-5">
                    <?php wp_nonce_field('bimverdi_resend_verification'); ?>

                    <div>
                        <label for="email" class="block text-sm font-medium text-[#1A1A1A] mb-2">
                            E-postadresse
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               value="<?php echo esc_attr($email); ?>"
                               required
                               autocomplete="email"
                               autofocus
                               placeholder="din@epost.no"
                               class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1A1A1A] focus:border-transparent text-[#1A1A1A]">
                        <p class="mt-2 text-xs text-[#888888]">
                            Bruk samme e-postadresse som du registrerte deg med.
                        </p>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                name="bimverdi_resend_verification"
                                value="1"
                                class="w-full px-6 py-3 bg-[#1A1A1A] text-white font-medium rounded-lg hover:bg-[#333333] transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1A1A1A]">
                            Send verifiserings-e-post
                        </button>
                    </div>
                </form>

                <!-- Divider -->
                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-[#E5E0D5]"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-[#888888]">eller</span>
                    </div>
                </div>

                <!-- Alternative Actions -->
                <div class="space-y-3">
                    <a href="<?php echo home_url('/registrer/'); ?>"
                       class="w-full block text-center px-6 py-3 border border-[#E5E0D5] text-[#1A1A1A] font-medium rounded-lg hover:bg-[#F7F5EF] transition-colors">
                        Registrer deg på nytt
                    </a>

                    <p class="text-center">
                        <a href="<?php echo home_url('/logg-inn/'); ?>" class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A] hover:underline inline-flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m12 19-7-7 7-7"/>
                                <path d="M19 12H5"/>
                            </svg>
                            Tilbake til innlogging
                        </a>
                    </p>
                </div>

            <?php endif; ?>

        </div>

        <!-- Info Box -->
        <div class="mt-6 p-4 bg-white border border-[#E5E0D5] rounded-lg">
            <h3 class="text-sm font-semibold text-[#1A1A1A] mb-2 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#5A5A5A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4"/>
                    <path d="M12 8h.01"/>
                </svg>
                Allerede verifisert?
            </h3>
            <p class="text-sm text-[#5A5A5A]">
                Hvis du allerede har fullført registreringen, kan du
                <a href="<?php echo home_url('/logg-inn/'); ?>" class="text-[#1A1A1A] underline">logge inn her</a>.
                Glemt passordet? <a href="<?php echo home_url('/glemt-passord/'); ?>" class="text-[#1A1A1A] underline">Tilbakestill det</a>.
            </p>
        </div>

    </div>
</main>

<?php get_footer(); ?>
