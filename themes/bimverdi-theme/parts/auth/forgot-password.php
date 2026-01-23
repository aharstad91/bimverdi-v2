<?php
/**
 * Auth Template: Forgot Password
 *
 * Request password reset link.
 * URL: /glemt-passord/
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
                <span class="text-2xl font-bold text-[#1A1A1A]">BIM Verdi</span>
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
                        Sjekk e-posten din
                    </h1>

                    <p class="text-[#5A5A5A] mb-6">
                        Hvis det finnes en konto med denne e-postadressen, har vi sendt en lenke
                        for å tilbakestille passordet.
                    </p>

                    <div class="p-4 bg-[#F7F5EF] border border-[#E5E0D5] rounded-lg text-left mb-6">
                        <p class="text-sm text-[#5A5A5A]">
                            <strong class="text-[#1A1A1A]">Tips:</strong> Sjekk søppelpost/spam-mappen
                            hvis du ikke finner e-posten i innboksen.
                        </p>
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
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                            <path d="M11 8a3 3 0 0 0-3 3"/>
                        </svg>
                    </div>

                    <h1 class="text-2xl font-bold text-[#1A1A1A]">
                        Glemt passord?
                    </h1>
                    <p class="text-[#5A5A5A] mt-2">
                        Oppgi e-postadressen din, så sender vi deg en lenke for å tilbakestille passordet.
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
                    <?php wp_nonce_field('bimverdi_forgot_password'); ?>

                    <div>
                        <label for="email" class="block text-sm font-medium text-[#1A1A1A] mb-2">
                            E-postadresse
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               required
                               autocomplete="email"
                               autofocus
                               placeholder="din@epost.no"
                               class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1A1A1A] focus:border-transparent text-[#1A1A1A]">
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                name="bimverdi_forgot_password"
                                value="1"
                                class="w-full px-6 py-3 bg-[#1A1A1A] text-white font-medium rounded-lg hover:bg-[#333333] transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1A1A1A]">
                            Send tilbakestillingslenke
                        </button>
                    </div>
                </form>

                <!-- Back Link -->
                <div class="mt-6 text-center">
                    <a href="<?php echo home_url('/logg-inn/'); ?>" class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A] hover:underline inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m12 19-7-7 7-7"/>
                            <path d="M19 12H5"/>
                        </svg>
                        Tilbake til innlogging
                    </a>
                </div>

            <?php endif; ?>

        </div>

        <!-- Footer Links -->
        <div class="text-center mt-8 text-sm text-[#888888]">
            <p>
                Har du ikke en konto?
                <a href="<?php echo home_url('/registrer/'); ?>" class="text-[#5A5A5A] hover:text-[#1A1A1A] hover:underline">
                    Opprett konto
                </a>
            </p>
        </div>

    </div>
</main>

<?php get_footer(); ?>
