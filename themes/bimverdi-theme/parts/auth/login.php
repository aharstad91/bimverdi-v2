<?php
/**
 * Auth Template: Login
 *
 * Custom login page following UI Contract (Variant B - beige, no gradients).
 * URL: /logg-inn/
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
$logged_out = isset($_GET['logged_out']);
$reset_success = isset($_GET['reset']) && $_GET['reset'] === 'success';
$username = isset($_GET['username']) ? sanitize_user(urldecode($_GET['username'])) : '';
$redirect_to = isset($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : home_url('/min-side/');

// Error messages
$error_messages = [
    'empty'            => 'Vennligst fyll ut brukernavn og passord.',
    'invalid'          => 'Ugyldig brukernavn eller passord.',
    'invalid_user'     => 'Denne brukeren finnes ikke.',
    'invalid_password' => 'Feil passord. Prøv igjen.',
    'nonce'            => 'Noe gikk galt. Vennligst prøv igjen.',
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

            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-[#1A1A1A]">
                    Logg inn
                </h1>
                <p class="text-[#5A5A5A] mt-2">
                    Velkommen tilbake til BIM Verdi
                </p>
            </div>

            <!-- Success Message: Reset Password -->
            <?php if ($reset_success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <p class="text-green-800 text-sm">Passordet er oppdatert. Du kan nå logge inn med ditt nye passord.</p>
            </div>
            <?php endif; ?>

            <!-- Info Message: Logged Out -->
            <?php if ($logged_out): ?>
            <div class="mb-6 p-4 bg-[#F7F5EF] border border-[#E5E0D5] rounded-lg flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5A5A5A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <p class="text-[#5A5A5A] text-sm">Du er nå logget ut.</p>
            </div>
            <?php endif; ?>

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

            <!-- Login Form -->
            <form method="post" action="" class="space-y-5">
                <?php wp_nonce_field('bimverdi_login'); ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

                <div>
                    <label for="username" class="block text-sm font-medium text-[#1A1A1A] mb-2">
                        E-post eller brukernavn
                    </label>
                    <input type="text"
                           id="username"
                           name="username"
                           value="<?php echo esc_attr($username); ?>"
                           required
                           autocomplete="username"
                           autofocus
                           class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1A1A1A] focus:border-transparent text-[#1A1A1A]">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-sm font-medium text-[#1A1A1A]">
                            Passord
                        </label>
                        <a href="<?php echo home_url('/glemt-passord/'); ?>" class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A] hover:underline">
                            Glemt passord?
                        </a>
                    </div>
                    <input type="password"
                           id="password"
                           name="password"
                           required
                           autocomplete="current-password"
                           class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1A1A1A] focus:border-transparent text-[#1A1A1A]">
                </div>

                <div class="flex items-center">
                    <input type="checkbox"
                           id="remember"
                           name="remember"
                           class="h-4 w-4 border-[#E5E0D5] rounded text-[#1A1A1A] focus:ring-[#1A1A1A]">
                    <label for="remember" class="ml-2 text-sm text-[#5A5A5A]">
                        Husk meg på denne enheten
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            name="bimverdi_login"
                            value="1"
                            class="w-full px-6 py-3 bg-[#1A1A1A] text-white font-medium rounded-lg hover:bg-[#333333] transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1A1A1A]">
                        Logg inn
                    </button>
                </div>
            </form>

            <!-- Divider -->
            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-[#E5E0D5]"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-white text-[#888888]">Ny bruker?</span>
                </div>
            </div>

            <!-- Register Link -->
            <a href="<?php echo home_url('/registrer/'); ?>"
               class="w-full block text-center px-6 py-3 border border-[#E5E0D5] text-[#1A1A1A] font-medium rounded-lg hover:bg-[#F7F5EF] transition-colors">
                Opprett konto
            </a>

        </div>

        <!-- Footer Links -->
        <div class="text-center mt-8 text-sm text-[#888888]">
            <p>
                <a href="<?php echo home_url('/'); ?>" class="hover:text-[#5A5A5A] hover:underline">
                    Tilbake til forsiden
                </a>
            </p>
        </div>

    </div>
</main>

<?php get_footer(); ?>
