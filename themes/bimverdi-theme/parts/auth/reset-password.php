<?php
/**
 * Auth Template: Reset Password
 *
 * Set new password using reset token from email.
 * URL: /tilbakestill-passord/?key=xxx&login=xxx
 *
 * @package BIMVerdi
 */

// Redirect logged-in users
if (is_user_logged_in()) {
    wp_redirect(home_url('/min-side/'));
    exit;
}

get_header();

// Get parameters
$key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
$login = isset($_GET['login']) ? sanitize_user($_GET['login']) : '';
$error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';

// Error messages
$error_messages = [
    'weak_password' => 'Passordet må være minst 8 tegn.',
    'mismatch'      => 'Passordene stemmer ikke overens.',
    'invalid_key'   => 'Tilbakestillingslenken er ugyldig eller har utløpt.',
    'nonce'         => 'Noe gikk galt. Vennligst prøv igjen.',
];

$error_message = $error_messages[$error] ?? '';

// Validate key
$is_valid = false;
if (!empty($key) && !empty($login)) {
    $user = check_password_reset_key($key, $login);
    $is_valid = !is_wp_error($user);
}
?>

<main class="min-h-screen bg-[#F7F5EF] py-12 px-4">
    <div class="max-w-md mx-auto">

        <!-- Main Card -->
        <div class="bg-white border border-[#E5E0D5] rounded-lg p-8">

            <?php if ($is_valid): ?>
                <!-- Valid Token - Show Form -->
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-[#F7F5EF] rounded-full mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5A5A5A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>

                    <h1 class="text-2xl font-bold text-[#1A1A1A]">
                        Velg nytt passord
                    </h1>
                    <p class="text-[#5A5A5A] mt-2">
                        Skriv inn ditt nye passord under.
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
                    <?php wp_nonce_field('bimverdi_reset_password'); ?>
                    <input type="hidden" name="key" value="<?php echo esc_attr($key); ?>">
                    <input type="hidden" name="login" value="<?php echo esc_attr($login); ?>">

                    <div>
                        <label for="password" class="block text-sm font-medium text-[#1A1A1A] mb-2">
                            Nytt passord
                        </label>
                        <input type="password"
                               id="password"
                               name="password"
                               required
                               minlength="8"
                               autocomplete="new-password"
                               autofocus
                               class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1A1A1A] focus:border-transparent text-[#1A1A1A]">
                        <p class="mt-2 text-xs text-[#888888]">Minst 8 tegn</p>
                    </div>

                    <div>
                        <label for="password_confirm" class="block text-sm font-medium text-[#1A1A1A] mb-2">
                            Bekreft passord
                        </label>
                        <input type="password"
                               id="password_confirm"
                               name="password_confirm"
                               required
                               minlength="8"
                               autocomplete="new-password"
                               class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1A1A1A] focus:border-transparent text-[#1A1A1A]">
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                name="bimverdi_reset_password"
                                value="1"
                                class="w-full px-6 py-3 bg-[#1A1A1A] text-white font-medium rounded-lg hover:bg-[#333333] transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1A1A1A]">
                            Lagre nytt passord
                        </button>
                    </div>
                </form>

                <!-- Security Tips -->
                <div class="mt-4 p-4 bg-[#F7F5EF] border border-[#E5E0D5] rounded-lg">
                    <h3 class="text-sm font-semibold text-[#1A1A1A] mt-0 mb-2 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
                        </svg>
                        Tips for sterkt passord
                    </h3>
                    <ul class="space-y-1 text-sm text-[#5A5A5A]">
                        <li class="flex items-start gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Bruk bokstaver, tall og spesialtegn
                        </li>
                        <li class="flex items-start gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Unngå å gjenbruke passord
                        </li>
                    </ul>
                </div>

            <?php else: ?>
                <!-- Invalid Token - Show Error -->
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>

                    <h1 class="text-2xl font-bold text-[#1A1A1A] mb-4">
                        Ugyldig lenke
                    </h1>

                    <p class="text-[#5A5A5A] mb-6">
                        <?php if ($error === 'invalid_key'): ?>
                            Tilbakestillingslenken er ugyldig eller har utløpt. Vennligst be om en ny lenke.
                        <?php elseif (empty($key) || empty($login)): ?>
                            Mangler nødvendig informasjon. Vennligst bruk lenken fra e-posten.
                        <?php else: ?>
                            Noe gikk galt. Vennligst prøv igjen eller be om en ny tilbakestillingslenke.
                        <?php endif; ?>
                    </p>

                    <div class="space-y-3">
                        <a href="<?php echo home_url('/glemt-passord/'); ?>"
                           class="w-full block text-center px-6 py-3 bg-[#1A1A1A] text-white font-medium rounded-lg hover:bg-[#333333] transition-colors">
                            Be om ny lenke
                        </a>

                        <a href="<?php echo home_url('/logg-inn/'); ?>"
                           class="w-full block text-center px-6 py-3 border border-[#E5E0D5] text-[#1A1A1A] font-medium rounded-lg hover:bg-[#F7F5EF] transition-colors">
                            Tilbake til innlogging
                        </a>
                    </div>
                </div>

            <?php endif; ?>

        </div>

    </div>
</main>

<?php get_footer(); ?>
