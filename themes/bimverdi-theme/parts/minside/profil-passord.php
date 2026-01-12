<?php
/**
 * Part: Endre passord
 * 
 * Skjema for å endre passord.
 * Brukes på /min-side/endre-passord/
 * 
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Handle password change
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bimverdi_change_password'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'bimverdi_change_password')) {
        $error = 'Ugyldig forespørsel. Prøv igjen.';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Alle felt må fylles ut.';
        } elseif (!wp_check_password($current_password, $current_user->user_pass, $user_id)) {
            $error = 'Nåværende passord er feil.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'De nye passordene stemmer ikke overens.';
        } elseif (strlen($new_password) < 8) {
            $error = 'Passordet må være minst 8 tegn.';
        } else {
            // Update password
            wp_set_password($new_password, $user_id);
            
            // Re-login the user
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            
            $message = 'Passordet er oppdatert!';
        }
    }
}

// Get user display info
$first_name = get_user_meta($user_id, 'first_name', true);
$last_name = get_user_meta($user_id, 'last_name', true);
$display_name = trim($first_name . ' ' . $last_name) ?: $current_user->display_name;
?>

<!-- Breadcrumb -->
<nav class="mb-6" aria-label="Brødsmulesti">
    <ol class="flex items-center gap-2 text-sm text-[#5A5A5A]">
        <li>
            <a href="<?php echo esc_url(home_url('/min-side/')); ?>" class="hover:text-[#1A1A1A] transition-colors">
                Min side
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li>
            <a href="<?php echo esc_url(home_url('/min-side/profil/')); ?>" class="hover:text-[#1A1A1A] transition-colors">
                Profil
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li class="text-[#1A1A1A] font-medium" aria-current="page">Endre passord</li>
    </ol>
</nav>

<!-- Page Header -->
<?php
get_template_part('parts/components/page-header', null, [
    'title' => 'Endre passord',
    'description' => 'Oppdater passordet til kontoen din'
]);
?>

<!-- Form Container (960px centered per UI-CONTRACT.md) -->
<div class="max-w-xl mx-auto">
    
    <!-- Messages -->
    <?php if ($message): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <p class="text-green-800"><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <p class="text-red-800"><?php echo esc_html($error); ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Password Form -->
    <div class="bg-white border border-[#E5E0D5] rounded-lg p-8">
        <h2 class="text-xl font-bold text-[#1A1A1A] mb-6 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            Nytt passord
        </h2>
        
        <form method="post" class="space-y-6">
            <?php wp_nonce_field('bimverdi_change_password'); ?>
            
            <div>
                <label for="current_password" class="block text-sm font-medium text-[#1A1A1A] mb-2">
                    Nåværende passord <span class="text-red-500">*</span>
                </label>
                <input type="password" 
                       id="current_password" 
                       name="current_password" 
                       required
                       class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1A1A1A] focus:border-transparent">
            </div>
            
            <div class="border-t border-[#E5E0D5] pt-6">
                <label for="new_password" class="block text-sm font-medium text-[#1A1A1A] mb-2">
                    Nytt passord <span class="text-red-500">*</span>
                </label>
                <input type="password" 
                       id="new_password" 
                       name="new_password" 
                       required
                       minlength="8"
                       class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1A1A1A] focus:border-transparent">
                <p class="mt-2 text-xs text-[#5A5A5A]">Minst 8 tegn</p>
            </div>
            
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-[#1A1A1A] mb-2">
                    Bekreft nytt passord <span class="text-red-500">*</span>
                </label>
                <input type="password" 
                       id="confirm_password" 
                       name="confirm_password" 
                       required
                       minlength="8"
                       class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1A1A1A] focus:border-transparent">
            </div>
            
            <div class="pt-4">
                <?php bimverdi_button([
                    'text'       => __('Oppdater passord', 'bimverdi'),
                    'variant'    => 'primary',
                    'type'       => 'submit',
                    'full_width' => true,
                    'size'       => 'large',
                    'icon'       => 'check',
                ]); ?>
                <input type="hidden" name="bimverdi_change_password" value="1">
            </div>
        </form>
    </div>
    
    <!-- Security Tips -->
    <div class="mt-8 p-4 bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg">
        <h3 class="text-sm font-semibold text-[#1A1A1A] mb-3 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
            </svg>
            Sikkerhetstips
        </h3>
        <ul class="space-y-2 text-sm text-[#5A5A5A]">
            <li class="flex items-start gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Bruk en kombinasjon av bokstaver, tall og spesialtegn
            </li>
            <li class="flex items-start gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Unngå å bruke samme passord på flere tjenester
            </li>
            <li class="flex items-start gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Bruk gjerne en passordbehandler
            </li>
        </ul>
    </div>
    
    <!-- Back Link -->
    <div class="mt-8 pt-6 border-t border-[#E5E0D5]">
        <a href="<?php echo esc_url(home_url('/min-side/profil/')); ?>" class="inline-flex items-center gap-2 text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Tilbake til profil
        </a>
    </div>
</div>
