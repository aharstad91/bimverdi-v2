<?php
/**
 * Template Name: Rediger Bruker
 *
 * Admin-accessible user profile editing page.
 * Uses plain HTML form with existing bimverdi-profile-edit.php handler.
 *
 * @package BimVerdi_Theme
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/logg-inn/?redirect_to=' . urlencode(get_permalink())));
    exit;
}

get_header();

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$profile = bim_get_user_profile($user_id);
$display_name = bim_get_user_display_name($user_id);

// Error/success handling
$error = isset($_GET['bv_error']) ? sanitize_text_field($_GET['bv_error']) : '';
$error_messages = [
    'nonce'            => 'Skjemaet utløp. Vennligst prøv igjen.',
    'rate_limit'       => 'For mange forsøk. Vennligst vent litt før du prøver igjen.',
    'required_fields'  => 'Fornavn og etternavn er påkrevd.',
    'invalid_file_type'=> 'Ugyldig filtype. Tillatte formater: jpg, png, gif, webp.',
    'file_too_large'   => 'Filen er for stor. Maks størrelse er 5 MB.',
    'upload_failed'    => 'Kunne ikke laste opp bilde. Vennligst prøv igjen.',
    'system'           => 'En teknisk feil oppstod. Vennligst prøv igjen.',
];
$error_text = $error_messages[$error] ?? '';

// Current profile image
$profile_image_id = $profile['profile_image'] ?? 0;
$profile_image_url = '';
if ($profile_image_id) {
    $profile_image_url = wp_get_attachment_image_url($profile_image_id, 'thumbnail') ?: '';
}
?>

<div class="min-h-screen bg-white py-8">
    <div class="container mx-auto px-4 max-w-2xl">

        <div class="mb-8">
            <h1 class="text-4xl font-bold text-[#111827] mb-2">Rediger Profil</h1>
            <p class="text-[#57534E]">Oppdater din personlige informasjon</p>
        </div>

        <!-- Profile Summary Card -->
        <div class="mb-8 p-4 bg-[#F5F5F4] border border-[#E7E5E4] rounded-lg flex items-center gap-4">
            <?php echo get_avatar($current_user->user_email, 80, '', $display_name, ['class' => 'rounded-full w-16 h-16']); ?>
            <div>
                <p class="text-lg font-semibold text-[#111827]"><?php echo esc_html($display_name); ?></p>
                <p class="text-sm text-[#57534E]"><?php echo esc_html($current_user->user_email); ?></p>
                <?php if (!empty($profile['job_title'])): ?>
                    <p class="text-sm text-[#FF8B5E] font-medium"><?php echo esc_html($profile['job_title']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($error_text): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <p class="text-red-800 text-sm"><?php echo esc_html($error_text); ?></p>
        </div>
        <?php endif; ?>

        <!-- Edit Profile Form -->
        <div class="bg-white border border-[#E7E5E4] rounded-lg p-6">
            <h2 class="text-xl font-bold text-[#111827] mb-6">Rediger Informasjon</h2>

            <form method="post" action="" enctype="multipart/form-data" class="space-y-5">
                <?php wp_nonce_field('bimverdi_edit_profile'); ?>
                <input type="hidden" name="bimverdi_edit_profile" value="1">

                <!-- Fornavn -->
                <div>
                    <label for="first_name" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                        Fornavn <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="first_name" name="first_name" required
                           value="<?php echo esc_attr($profile['first_name']); ?>"
                           class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                </div>

                <!-- Etternavn -->
                <div>
                    <label for="last_name" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                        Etternavn <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="last_name" name="last_name" required
                           value="<?php echo esc_attr($profile['last_name']); ?>"
                           class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                </div>

                <!-- E-post (disabled) -->
                <div>
                    <label class="block text-sm font-semibold text-[#1A1A1A] mb-2">E-post</label>
                    <input type="email" value="<?php echo esc_attr($current_user->user_email); ?>" disabled
                           class="w-full px-4 py-3 border border-[#E7E5E4] rounded-lg bg-[#EEECE9] text-[#78716C] cursor-not-allowed opacity-60">
                    <p class="mt-1 text-xs text-[#888888]">E-postadressen kan ikke endres</p>
                </div>

                <hr class="border-[#E5E0D5]">

                <!-- Telefon -->
                <div>
                    <label for="phone" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Telefon</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?php echo esc_attr($profile['phone']); ?>"
                           placeholder="+47 123 45 678"
                           class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                </div>

                <!-- Stilling -->
                <div>
                    <label for="job_title" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Stilling</label>
                    <input type="text" id="job_title" name="job_title"
                           value="<?php echo esc_attr($profile['job_title']); ?>"
                           placeholder="F.eks. BIM-koordinator"
                           class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                </div>

                <!-- LinkedIn URL -->
                <div>
                    <label for="linkedin_url" class="block text-sm font-semibold text-[#1A1A1A] mb-2">LinkedIn URL</label>
                    <input type="url" id="linkedin_url" name="linkedin_url"
                           value="<?php echo esc_attr($profile['linkedin_url']); ?>"
                           placeholder="https://linkedin.com/in/..."
                           class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                </div>

                <hr class="border-[#E5E0D5]">

                <!-- Profile Image -->
                <div>
                    <label class="block text-sm font-semibold text-[#1A1A1A] mb-2">Profilbilde</label>
                    <?php if ($profile_image_url): ?>
                    <div class="mb-3 flex items-center gap-4">
                        <img src="<?php echo esc_url($profile_image_url); ?>" alt="" class="w-16 h-16 rounded-full object-cover border border-[#E7E5E4]">
                        <span class="text-xs text-[#57534E]">Nåværende profilbilde</span>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="profile_image" accept="image/jpeg,image/png,image/gif,image/webp"
                           class="w-full text-sm text-[#5A5A5A] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border file:border-[#E5E0D5] file:text-sm file:font-medium file:bg-gray-100 file:text-[#1A1A1A] hover:file:bg-gray-200 file:cursor-pointer file:transition-colors">
                    <p class="mt-1 text-xs text-[#888888]">Tillatte formater: jpg, png, gif, webp. Maks 5 MB.</p>
                </div>

                <!-- Submit -->
                <div class="pt-4">
                    <button type="submit"
                            class="bv-btn bv-btn--primary px-6 py-2.5 text-sm font-semibold rounded-lg">
                        Lagre endringer
                    </button>
                </div>
            </form>
        </div>

        <!-- Back to Profile Link -->
        <div class="mt-8 text-center">
            <a href="<?php echo esc_url(home_url('/min-side/profil/')); ?>" class="text-[#FF8B5E] hover:underline font-semibold">
                &larr; Tilbake til profil
            </a>
        </div>

    </div>
</div>

<?php get_footer(); ?>
