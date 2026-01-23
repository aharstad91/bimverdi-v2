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

// Get user meta
$first_name = get_user_meta($user_id, 'first_name', true);
$last_name = get_user_meta($user_id, 'last_name', true);
$phone = get_user_meta($user_id, 'bim_verdi_phone', true);
$position = get_user_meta($user_id, 'bim_verdi_position', true);
$bio = get_user_meta($user_id, 'description', true);

// Get company info
$company_data = bimverdi_get_user_company($user_id);
$company_id = $company_data ? (is_array($company_data) ? $company_data['id'] : $company_data) : false;
$company = $company_id ? get_post($company_id) : null;

// Get temagrupper
$temagrupper = get_user_meta($user_id, 'bim_verdi_temagrupper', true);
if (!is_array($temagrupper)) $temagrupper = [];

// Get avatar
$avatar_url = get_avatar_url($user_id, ['size' => 200]);

// Display name
$display_name = trim($first_name . ' ' . $last_name);
if (empty($display_name)) {
    $display_name = $current_user->display_name;
}
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
        <div class="pb-8 border-b border-[#D6D1C6]">
            <div class="flex items-start gap-4">
                <img src="<?php echo esc_url($avatar_url); ?>" alt="" class="w-20 h-20 rounded-full object-cover flex-shrink-0">

                <div class="flex-1 min-w-0">
                    <h2 class="text-xl font-bold text-[#1A1A1A] mb-1">
                        <?php echo esc_html($display_name); ?>
                    </h2>
                    <?php if ($position): ?>
                        <p class="text-sm text-[#5A5A5A] mb-2"><?php echo esc_html($position); ?></p>
                    <?php endif; ?>

                    <?php if ($company): ?>
                        <a href="<?php echo esc_url(bimverdi_minside_url('foretak')); ?>" class="inline-flex items-center gap-1.5 text-sm text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path></svg>
                            <?php echo esc_html(get_the_title($company_id)); ?>
                        </a>
                    <?php endif; ?>

                    <!-- Account Status inline -->
                    <div class="flex items-center gap-2 mt-3">
                        <span class="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"></span>
                        <span class="text-xs text-[#5A5A5A]">
                            <?php _e('Aktiv bruker', 'bimverdi'); ?> &middot;
                            <?php _e('Registrert', 'bimverdi'); ?> <?php echo date_i18n(get_option('date_format'), strtotime($current_user->user_registered)); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bio Section (if exists) -->
        <?php if ($bio): ?>
        <div class="pb-8 border-b border-[#D6D1C6]">
            <h3 class="text-lg font-semibold text-[#1A1A1A] mb-3"><?php _e('Om meg', 'bimverdi'); ?></h3>
            <div class="text-sm text-[#5A5A5A] leading-relaxed prose prose-sm max-w-none">
                <?php echo wp_kses_post(wpautop($bio)); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contact Info Section (Variant B: stacked items with dividers, no box) -->
        <div class="<?php echo !empty($temagrupper) ? 'pb-8 border-b border-[#D6D1C6]' : ''; ?>">
            <h3 class="text-lg font-semibold text-[#1A1A1A] mb-4"><?php _e('Kontaktinformasjon', 'bimverdi'); ?></h3>
            <div class="divide-y divide-[#E5E0D8]">

                <!-- Email -->
                <div class="flex items-start gap-3 py-4 first:pt-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888] flex-shrink-0 mt-0.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    <div>
                        <p class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide mb-1"><?php _e('E-post', 'bimverdi'); ?></p>
                        <p class="text-sm text-[#1A1A1A]"><?php echo esc_html($current_user->user_email); ?></p>
                    </div>
                </div>

                <?php if ($phone): ?>
                <div class="flex items-start gap-3 py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888] flex-shrink-0 mt-0.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    <div>
                        <p class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide mb-1"><?php _e('Telefon', 'bimverdi'); ?></p>
                        <p class="text-sm text-[#1A1A1A]"><?php echo esc_html($phone); ?></p>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Temagrupper Section (if exists) -->
        <?php if (!empty($temagrupper)): ?>
        <div>
            <h3 class="text-lg font-semibold text-[#1A1A1A] mb-4"><?php _e('Mine temagrupper', 'bimverdi'); ?></h3>
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
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-[#F2F0EB] text-[#5A5A5A]">
                        <?php echo esc_html($label); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

<?php get_template_part('parts/components/account-layout-end'); ?>
