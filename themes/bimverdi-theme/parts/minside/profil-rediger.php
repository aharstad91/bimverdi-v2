<?php
/**
 * Part: Rediger profil
 *
 * Plain HTML form for editing user profile.
 * Replaces Gravity Forms Form #4.
 * Brukes på /min-side/profil/rediger/
 *
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user data via helper
$profile = bim_get_user_profile($user_id);
$display_name = bim_get_user_display_name($user_id);
$avatar_url = bim_get_user_profile_image_url($user_id, 'thumbnail');

// Check for welcome redirect (new colleague after email verification)
$is_welcome = isset($_GET['welcome']) && $_GET['welcome'] === '1';

// Error handling
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

// Current checkbox values
$registration_background = $profile['registration_background'] ?: [];
$topic_interests = $profile['topic_interests'] ?: [];

// Checkbox options
$background_options = [
    'oppdatering'      => 'Oppdatering - allerede registrert',
    'tilleggskontakt'  => 'Ny tilleggskontakt',
    'arrangement'      => 'Arrangement-deltakelse',
    'nyhetsbrev'       => 'Nyhetsbrev',
    'deltaker_verktoy' => 'Deltakerregistrering og digitale verktøy',
    'mote'             => 'Ønsker å avtale et møte',
];

$topic_options = [
    'byggesaksbim' => 'ByggesaksBIM',
    'prosjektbim'  => 'ProsjektBIM',
    'eiendomsbim'  => 'EiendomsBIM',
    'miljobim'     => 'MiljøBIM',
    'sirkbim'      => 'SirkBIM',
    'bimtech'      => 'BIMtech',
];

// Current profile image
$profile_image_id = $profile['profile_image'];
$profile_image_url = '';
if ($profile_image_id) {
    $profile_image_url = wp_get_attachment_image_url($profile_image_id, 'thumbnail') ?: '';
}
?>

<!-- Account Layout with Sidenav -->
<?php get_template_part('parts/components/account-layout', null, [
    'title' => $is_welcome ? __('Velkommen til BIM Verdi!', 'bimverdi') : __('Rediger profil', 'bimverdi'),
    'description' => $is_welcome ? __('Fyll ut profilen din for å komme i gang', 'bimverdi') : __('Oppdater din personlige informasjon', 'bimverdi'),
]); ?>

    <!-- Form Container (constrained width) -->
    <div class="max-w-2xl">

        <?php if ($is_welcome): ?>
        <!-- Welcome Banner -->
        <div class="mb-8 p-5 bg-[#FFF4EE] border border-[#FFD4BD] rounded-lg">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#FF8B5E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <div>
                    <p class="font-semibold text-[#111827] mb-1"><?php _e('Kontoen din er opprettet!', 'bimverdi'); ?></p>
                    <p class="text-sm text-[#57534E]"><?php _e('Fyll ut informasjonen under for å fullføre profilen din. Du kan alltid oppdatere dette senere.', 'bimverdi'); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($error_text): ?>
        <!-- Error Message -->
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <p class="text-red-800 text-sm"><?php echo esc_html($error_text); ?></p>
        </div>
        <?php endif; ?>

        <!-- User Info Badge -->
        <div class="mb-8 p-4 bg-[#F5F5F4] border border-[#E7E5E4] rounded-lg flex items-center gap-4">
            <img src="<?php echo esc_url($avatar_url); ?>" alt="" class="w-14 h-14 rounded-full object-cover flex-shrink-0">
            <div>
                <p class="font-semibold text-[#111827]"><?php echo esc_html($display_name); ?></p>
                <p class="text-sm text-[#57534E]"><?php echo esc_html($current_user->user_email); ?></p>
            </div>
        </div>

        <form method="post" action="" enctype="multipart/form-data" class="space-y-6">
            <?php wp_nonce_field('bimverdi_edit_profile'); ?>
            <input type="hidden" name="bimverdi_edit_profile" value="1">

            <!-- Personal Info Section -->
            <div>
                <h3 class="text-base font-semibold text-[#1A1A1A] mb-4"><?php _e('Personlig informasjon', 'bimverdi'); ?></h3>
                <div class="space-y-4">
                    <!-- Fornavn -->
                    <div>
                        <label for="first_name" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                            <?php _e('Fornavn', 'bimverdi'); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="first_name" name="first_name" required
                               value="<?php echo esc_attr($profile['first_name']); ?>"
                               class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    </div>

                    <!-- Mellomnavn -->
                    <div>
                        <label for="middle_name" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                            <?php _e('Mellomnavn', 'bimverdi'); ?>
                        </label>
                        <input type="text" id="middle_name" name="middle_name"
                               value="<?php echo esc_attr($profile['middle_name']); ?>"
                               class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    </div>

                    <!-- Etternavn -->
                    <div>
                        <label for="last_name" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                            <?php _e('Etternavn', 'bimverdi'); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="last_name" name="last_name" required
                               value="<?php echo esc_attr($profile['last_name']); ?>"
                               class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    </div>

                    <!-- E-post (disabled) -->
                    <div>
                        <label class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                            <?php _e('E-post', 'bimverdi'); ?>
                        </label>
                        <input type="email" value="<?php echo esc_attr($current_user->user_email); ?>" disabled
                               class="w-full px-4 py-3 border border-[#E7E5E4] rounded-lg bg-[#EEECE9] text-[#78716C] cursor-not-allowed opacity-60">
                        <p class="mt-1 text-xs text-[#888888]"><?php _e('E-postadressen kan ikke endres', 'bimverdi'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <hr class="border-[#E5E0D5]">

            <!-- Contact Info Section -->
            <div>
                <h3 class="text-base font-semibold text-[#1A1A1A] mb-4"><?php _e('Kontakt og jobb', 'bimverdi'); ?></h3>
                <div class="space-y-4">
                    <!-- Telefon -->
                    <div>
                        <label for="phone" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                            <?php _e('Telefon', 'bimverdi'); ?>
                        </label>
                        <input type="tel" id="phone" name="phone"
                               value="<?php echo esc_attr($profile['phone']); ?>"
                               placeholder="<?php esc_attr_e('+47 123 45 678', 'bimverdi'); ?>"
                               class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    </div>

                    <!-- Stilling -->
                    <div>
                        <label for="job_title" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                            <?php _e('Stilling', 'bimverdi'); ?>
                        </label>
                        <input type="text" id="job_title" name="job_title"
                               value="<?php echo esc_attr($profile['job_title']); ?>"
                               placeholder="<?php esc_attr_e('F.eks. BIM-koordinator', 'bimverdi'); ?>"
                               class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    </div>

                    <!-- LinkedIn URL -->
                    <div>
                        <label for="linkedin_url" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                            <?php _e('LinkedIn URL', 'bimverdi'); ?>
                        </label>
                        <input type="url" id="linkedin_url" name="linkedin_url"
                               value="<?php echo esc_attr($profile['linkedin_url']); ?>"
                               placeholder="https://linkedin.com/in/..."
                               autocomplete="url"
                               class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <hr class="border-[#E5E0D5]">

            <!-- Profile Image -->
            <div>
                <label class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                    <?php _e('Profilbilde', 'bimverdi'); ?>
                </label>
                <?php if ($profile_image_url): ?>
                <div class="mb-3 flex items-center gap-4">
                    <img src="<?php echo esc_url($profile_image_url); ?>" alt="" class="w-16 h-16 rounded-full object-cover border border-[#E7E5E4]">
                    <span class="text-xs text-[#57534E]"><?php _e('Nåværende profilbilde', 'bimverdi'); ?></span>
                </div>
                <?php endif; ?>
                <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif,image/webp"
                       class="w-full text-sm text-[#5A5A5A] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border file:border-[#E5E0D5] file:text-sm file:font-medium file:bg-gray-100 file:text-[#1A1A1A] hover:file:bg-gray-200 file:cursor-pointer file:transition-colors">
                <p class="mt-1 text-xs text-[#888888]"><?php _e('Last opp nytt profilbilde (valgfritt). Tillatte formater: jpg, png, gif, webp. Maks 5 MB.', 'bimverdi'); ?></p>
            </div>

            <!-- Divider -->
            <hr class="border-[#E5E0D5]">

            <!-- Bakgrunn for registrering -->
            <fieldset>
                <legend class="text-sm font-semibold text-[#1A1A1A] mb-1">
                    <?php _e('Bakgrunn for registrering', 'bimverdi'); ?>
                </legend>
                <p class="text-xs text-[#888888] mb-3"><?php _e('Hva er grunnen til at du registrerte deg? Du kan velge flere.', 'bimverdi'); ?></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <?php foreach ($background_options as $value => $label): ?>
                    <label class="flex items-start gap-3 p-3 rounded-lg border border-[#E5E0D5] hover:border-[#FF8B5E] hover:bg-[#FFF8F5] transition-colors cursor-pointer has-[:checked]:border-[#FF8B5E] has-[:checked]:bg-[#FFF8F5]">
                        <input type="checkbox"
                               name="registration_background[]"
                               value="<?php echo esc_attr($value); ?>"
                               <?php checked(in_array($value, $registration_background)); ?>
                               class="mt-0.5 w-4 h-4 rounded border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                        <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($label); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <!-- Interesse for temaer -->
            <fieldset>
                <legend class="text-sm font-semibold text-[#1A1A1A] mb-1">
                    <?php _e('Interesse for temaer', 'bimverdi'); ?>
                </legend>
                <p class="text-xs text-[#888888] mb-3"><?php _e('Hvilke temagrupper er du interessert i? Du kan velge flere.', 'bimverdi'); ?></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <?php foreach ($topic_options as $value => $label): ?>
                    <label class="flex items-start gap-3 p-3 rounded-lg border border-[#E5E0D5] hover:border-[#FF8B5E] hover:bg-[#FFF8F5] transition-colors cursor-pointer has-[:checked]:border-[#FF8B5E] has-[:checked]:bg-[#FFF8F5]">
                        <input type="checkbox"
                               name="topic_interests[]"
                               value="<?php echo esc_attr($value); ?>"
                               <?php checked(in_array($value, $topic_interests)); ?>
                               class="mt-0.5 w-4 h-4 rounded border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                        <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($label); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <!-- Submit -->
            <div class="pt-4">
                <button type="submit"
                        class="bv-btn bv-btn--primary px-6 py-2.5 text-sm font-semibold rounded-lg">
                    <?php _e('Lagre endringer', 'bimverdi'); ?>
                </button>
            </div>
        </form>

    </div>

<?php get_template_part('parts/components/account-layout-end'); ?>
