<?php
/**
 * Min Side - Profil Part
 *
 * Shows user profile information with account sidenav.
 * Follows Variant B design system (dividers, not boxes).
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user profile via helper
$profile = bim_get_user_profile($user_id);
$phone = $profile['phone'];
$position = $profile['job_title'];
$bio = get_user_meta($user_id, 'description', true);

// Get company info
$company_data = bimverdi_get_user_company($user_id);
$company_id = $company_data ? (is_array($company_data) ? $company_data['id'] : $company_data) : false;
$company = $company_id ? get_post($company_id) : null;

// Get temagrupper from profile (ACF is source of truth, legacy fallback)
$temagrupper = !empty($profile['topic_interests']) ? $profile['topic_interests'] : [];
if (empty($temagrupper)) {
    $legacy = get_user_meta($user_id, 'bim_verdi_temagrupper', true);
    if (is_array($legacy)) $temagrupper = $legacy;
}

// Registration background
$registration_background = $profile['registration_background'];

// Get avatar via helper
$avatar_url = bim_get_user_profile_image_url($user_id, 'medium');

// Display name (includes middle name)
$display_name = bim_get_user_display_name($user_id);
?>

<!-- Account Layout with Sidenav -->
<?php get_template_part('parts/components/account-layout', null, [
    'title' => __('Min profil', 'bimverdi'),
    'description' => __('Dine personlige innstillinger og informasjon', 'bimverdi'),
    'actions' => [
        ['text' => __('Rediger profil', 'bimverdi'), 'url' => bimverdi_minside_url('profil/rediger'), 'variant' => 'primary'],
    ],
]); ?>

    <div class="space-y-8">

        <!-- Profile Header (Variant B: no box, divider below) -->
        <div class="pb-8 border-b border-[#E7E5E4]">
            <div class="flex items-start gap-4">
                <img src="<?php echo esc_url($avatar_url); ?>" alt="" class="w-20 h-20 rounded-full object-cover flex-shrink-0">

                <div class="flex-1 min-w-0">
                    <h2 class="text-xl font-bold text-[#111827] mb-1">
                        <?php echo esc_html($display_name); ?>
                    </h2>
                    <?php if ($position): ?>
                        <p class="text-sm text-[#57534E] mb-2"><?php echo esc_html($position); ?></p>
                    <?php endif; ?>

                    <?php if ($company): ?>
                        <a href="<?php echo esc_url(bimverdi_minside_url('foretak')); ?>" class="inline-flex items-center gap-1.5 text-sm text-[#57534E] hover:text-[#111827] transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path></svg>
                            <?php echo esc_html(get_the_title($company_id)); ?>
                        </a>
                    <?php endif; ?>

                    <!-- Account Status inline -->
                    <div class="flex items-center gap-2 mt-3">
                        <span class="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"></span>
                        <span class="text-xs text-[#57534E]">
                            <?php _e('Aktiv bruker', 'bimverdi'); ?> &middot;
                            <?php _e('Registrert', 'bimverdi'); ?> <?php echo date_i18n(get_option('date_format'), strtotime($current_user->user_registered)); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bio Section (if exists) -->
        <?php if ($bio): ?>
        <div class="pb-8 border-b border-[#E7E5E4]">
            <h3 class="text-lg font-semibold text-[#111827] mb-3"><?php _e('Om meg', 'bimverdi'); ?></h3>
            <div class="text-sm text-[#57534E] leading-relaxed prose prose-sm max-w-none">
                <?php echo wp_kses_post(wpautop($bio)); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contact Info Section (Variant B: stacked items with dividers, no box) -->
        <div class="<?php echo (!empty($temagrupper) || !empty($registration_background)) ? 'pb-8 border-b border-[#E7E5E4]' : ''; ?>">
            <h3 class="text-lg font-semibold text-[#111827] mb-4"><?php _e('Kontaktinformasjon', 'bimverdi'); ?></h3>
            <div class="divide-y divide-[#E7E5E4]">

                <!-- Email -->
                <div class="flex items-start gap-3 py-4 first:pt-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888] flex-shrink-0 mt-0.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    <div>
                        <p class="text-xs font-medium text-[#57534E] uppercase tracking-wide mb-1"><?php _e('E-post', 'bimverdi'); ?></p>
                        <p class="text-sm text-[#111827]"><?php echo esc_html($current_user->user_email); ?></p>
                    </div>
                </div>

                <?php if ($phone): ?>
                <div class="flex items-start gap-3 py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888] flex-shrink-0 mt-0.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    <div>
                        <p class="text-xs font-medium text-[#57534E] uppercase tracking-wide mb-1"><?php _e('Telefon', 'bimverdi'); ?></p>
                        <p class="text-sm text-[#111827]"><?php echo esc_html($phone); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($position): ?>
                <div class="flex items-start gap-3 py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888] flex-shrink-0 mt-0.5"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    <div>
                        <p class="text-xs font-medium text-[#57534E] uppercase tracking-wide mb-1"><?php _e('Stilling', 'bimverdi'); ?></p>
                        <p class="text-sm text-[#111827]"><?php echo esc_html($position); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($profile['linkedin_url'])): ?>
                <div class="flex items-start gap-3 py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888] flex-shrink-0 mt-0.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    <div>
                        <p class="text-xs font-medium text-[#57534E] uppercase tracking-wide mb-1"><?php _e('LinkedIn', 'bimverdi'); ?></p>
                        <a href="<?php echo esc_url($profile['linkedin_url']); ?>" target="_blank" rel="noopener" class="text-sm text-[#111827] hover:text-[#FF8B5E] transition-colors"><?php _e('Se profil', 'bimverdi'); ?> &rarr;</a>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Bakgrunn for registrering (if exists) -->
        <?php if (!empty($registration_background)): ?>
        <div class="pb-8 border-b border-[#E7E5E4]">
            <h3 class="text-lg font-semibold text-[#111827] mb-4"><?php _e('Bakgrunn for registrering', 'bimverdi'); ?></h3>
            <?php
            $background_labels = [
                'oppdatering' => 'Oppdatering - allerede registrert',
                'tilleggskontakt' => 'Ny tilleggskontakt',
                'arrangement' => 'Arrangement-deltakelse',
                'nyhetsbrev' => 'Nyhetsbrev',
                'deltaker_verktoy' => 'Deltakerregistrering og digitale verktøy',
                'mote' => 'Ønsker å avtale et møte',
            ];
            ?>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($registration_background as $bg):
                    $label = $background_labels[$bg] ?? ucfirst($bg);
                ?>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-[#FFF4EE] text-[#A0522D]">
                        <?php echo esc_html($label); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Temagrupper Section (if exists) -->
        <?php if (!empty($temagrupper)): ?>
        <div>
            <h3 class="text-lg font-semibold text-[#111827] mb-4"><?php _e('Mine temagrupper', 'bimverdi'); ?></h3>
            <div class="flex flex-wrap gap-2">
                <?php
                $temagruppe_labels = [
                    'byggesaksbim' => 'ByggesaksBIM',
                    'prosjektbim' => 'ProsjektBIM',
                    'eiendomsbim' => 'EiendomsBIM',
                    'miljobim' => 'MiljøBIM',
                    'sirkbim' => 'SirkBIM',
                    'bimtech' => 'BIMtech',
                ];
                foreach ($temagrupper as $temagruppe):
                    $label = $temagruppe_labels[$temagruppe] ?? ucfirst($temagruppe);
                ?>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-[#F5F5F4] text-[#57534E]">
                        <?php echo esc_html($label); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

<?php get_template_part('parts/components/account-layout-end'); ?>
