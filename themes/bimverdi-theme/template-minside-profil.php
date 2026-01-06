<?php
/**
 * Template Name: Min Side - Profil
 * 
 * User profile editing page using Gravity Forms
 * 
 * @package BimVerdi_Theme
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header('minside');

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Start Min Side layout
get_template_part('template-parts/minside-layout-start', null, array(
    'current_page' => 'profil',
    'page_title' => 'Profil',
    'page_icon' => 'user',
    'page_description' => 'Oppdater din personlige informasjon',
));
?>

<!-- Profile Form via Gravity Forms -->
<div class="space-y-6">
    
    <!-- Account Info (Read-only) -->
    <wa-card>
        <div slot="header" class="flex items-center gap-2">
            <wa-icon name="shield" library="fa"></wa-icon>
            <strong>Kontoinformasjon</strong>
        </div>

        <div class="p-1">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Brukernavn:</span>
                    <span class="font-medium ml-2"><?php echo esc_html($current_user->user_login); ?></span>
                </div>
                <div>
                    <span class="text-gray-500">Medlem siden:</span>
                    <span class="font-medium ml-2"><?php echo date('d.m.Y', strtotime($current_user->user_registered)); ?></span>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <wa-button variant="neutral" outline size="small" href="<?php echo wp_lostpassword_url(get_permalink()); ?>">
                    <wa-icon slot="prefix" name="key" library="fa"></wa-icon>
                    Endre passord
                </wa-button>
            </div>
        </div>
    </wa-card>

    <!-- Gravity Forms Profile Edit Form (ID 4) -->
    <wa-card>
        <div slot="header" class="flex items-center gap-2">
            <wa-icon name="user-pen" library="fa"></wa-icon>
            <strong>Rediger profil</strong>
        </div>
        
        <div class="p-1">
            <?php 
            if (function_exists('gravity_form')) {
                gravity_form(4, false, false, false, null, true, 12);
            } else {
                echo '<p class="text-red-500">Gravity Forms er ikke aktivert.</p>';
            }
            ?>
        </div>
    </wa-card>

</div>

<?php 
get_template_part('template-parts/minside-layout-end');
get_footer(); 
?>
