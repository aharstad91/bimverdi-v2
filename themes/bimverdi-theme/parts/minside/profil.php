<?php
/**
 * Min Side - Profil Part
 * 
 * Shows user profile information and edit options.
 * Used by template-minside-universal.php
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
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
if (empty($company_id)) {
    $company_id = get_user_meta($user_id, 'bimverdi_company_id', true);
}
$company = $company_id ? get_post($company_id) : null;

// Get temagrupper
$temagrupper = get_user_meta($user_id, 'bim_verdi_temagrupper', true);
if (!is_array($temagrupper)) $temagrupper = [];

// Get avatar
$avatar_url = get_avatar_url($user_id, ['size' => 200]);
?>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => __('Min profil', 'bimverdi'),
    'description' => __('Dine personlige innstillinger og informasjon', 'bimverdi'),
    'actions' => [
        ['text' => __('Rediger profil', 'bimverdi'), 'url' => '/min-side/rediger-profil/', 'variant' => 'primary'],
    ],
]); ?>

<!-- Two Column Layout -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Main Content (2/3) -->
    <div class="lg:col-span-2 space-y-8">
        
        <!-- Profile Header Card -->
        <div class="bg-white rounded-lg border border-[#E5E0D8] p-6">
            <div class="flex items-start gap-4">
                <img src="<?php echo esc_url($avatar_url); ?>" alt="" class="w-20 h-20 rounded-full object-cover">
                
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-[#1A1A1A] mb-1">
                        <?php 
                        $display_name = trim($first_name . ' ' . $last_name);
                        echo esc_html($display_name ?: $current_user->display_name); 
                        ?>
                    </h2>
                    <?php if ($position): ?>
                        <p class="text-sm text-[#5A5A5A] mb-2"><?php echo esc_html($position); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($company): ?>
                        <a href="<?php echo home_url('/min-side/foretak/'); ?>" class="inline-flex items-center gap-1 text-sm text-[#5A5A5A] hover:text-[#1A1A1A]">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path></svg>
                            <?php echo esc_html(get_the_title($company_id)); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Bio Section -->
        <?php if ($bio): ?>
        <div>
            <h3 class="text-lg font-semibold text-[#1A1A1A] mb-3"><?php _e('Om meg', 'bimverdi'); ?></h3>
            <div class="text-sm text-[#5A5A5A] leading-relaxed">
                <?php echo wp_kses_post(wpautop($bio)); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Contact Info Section -->
        <div>
            <h3 class="text-lg font-semibold text-[#1A1A1A] mb-4"><?php _e('Kontaktinformasjon', 'bimverdi'); ?></h3>
            <div class="bg-white rounded-lg border border-[#E5E0D8] divide-y divide-[#E5E0D8]">
                
                <!-- Email -->
                <div class="flex items-start gap-3 p-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A] flex-shrink-0 mt-0.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    <div>
                        <p class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide mb-1"><?php _e('E-post', 'bimverdi'); ?></p>
                        <p class="text-sm text-[#1A1A1A]"><?php echo esc_html($current_user->user_email); ?></p>
                    </div>
                </div>
                
                <?php if ($phone): ?>
                <div class="flex items-start gap-3 p-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A] flex-shrink-0 mt-0.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    <div>
                        <p class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide mb-1"><?php _e('Telefon', 'bimverdi'); ?></p>
                        <p class="text-sm text-[#1A1A1A]"><?php echo esc_html($phone); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
        
        <!-- Temagrupper Section -->
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
    
    <!-- Sidebar (1/3) -->
    <div class="space-y-6">
        
        <!-- Account Status -->
        <div>
            <h3 class="text-sm font-semibold text-[#5A5A5A] uppercase tracking-wide mb-3"><?php _e('Konto', 'bimverdi'); ?></h3>
            <div class="bg-white rounded-lg border border-[#E5E0D8] p-4 space-y-3">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <span class="text-sm font-medium text-[#1A1A1A]"><?php _e('Aktiv bruker', 'bimverdi'); ?></span>
                </div>
                <div class="text-xs text-[#5A5A5A]">
                    <?php _e('Registrert:', 'bimverdi'); ?> <?php echo date_i18n(get_option('date_format'), strtotime($current_user->user_registered)); ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div>
            <h3 class="text-sm font-semibold text-[#5A5A5A] uppercase tracking-wide mb-3"><?php _e('Snarveier', 'bimverdi'); ?></h3>
            <div class="space-y-2">
                <a href="<?php echo home_url('/min-side/rediger-profil/'); ?>" class="flex items-center gap-3 p-3 bg-white rounded-lg border border-[#E5E0D8] hover:border-[#1A1A1A] transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    <span class="text-sm font-medium text-[#1A1A1A]"><?php _e('Rediger profil', 'bimverdi'); ?></span>
                </a>
                <a href="<?php echo home_url('/min-side/endre-passord/'); ?>" class="flex items-center gap-3 p-3 bg-white rounded-lg border border-[#E5E0D8] hover:border-[#1A1A1A] transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <span class="text-sm font-medium text-[#1A1A1A]"><?php _e('Endre passord', 'bimverdi'); ?></span>
                </a>
            </div>
        </div>
        
    </div>
</div>
