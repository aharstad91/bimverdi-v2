<?php
/**
 * Part: Rediger profil
 *
 * Skjema for redigering av brukerprofil via Gravity Forms (ID 4).
 * Brukes på /min-side/profil/rediger/
 *
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user data for display
$first_name = get_user_meta($user_id, 'first_name', true);
$last_name = get_user_meta($user_id, 'last_name', true);
$display_name = trim($first_name . ' ' . $last_name) ?: $current_user->display_name;
$avatar_url = get_avatar_url($user_id, ['size' => 80]);
?>

<!-- Account Layout with Sidenav -->
<?php get_template_part('parts/components/account-layout', null, [
    'title' => __('Rediger profil', 'bimverdi'),
    'description' => __('Oppdater din personlige informasjon', 'bimverdi'),
]); ?>

    <!-- Form Container (constrained width) -->
    <div class="max-w-2xl">

        <!-- User Info Badge -->
        <div class="mb-8 p-4 bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg flex items-center gap-4">
            <img src="<?php echo esc_url($avatar_url); ?>" alt="" class="w-14 h-14 rounded-full object-cover flex-shrink-0">
            <div>
                <p class="font-semibold text-[#1A1A1A]"><?php echo esc_html($display_name); ?></p>
                <p class="text-sm text-[#5A5A5A]"><?php echo esc_html($current_user->user_email); ?></p>
            </div>
        </div>

        <!-- Gravity Form -->
        <div class="bg-white border border-[#E5E0D5] rounded-lg p-8">
            <h2 class="text-xl font-bold text-[#1A1A1A] mb-6 flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <?php _e('Din informasjon', 'bimverdi'); ?>
            </h2>

            <?php
            // Display Gravity Form ID 4 [Bruker] - Redigering av profil
            if (function_exists('gravity_form')) {
                gravity_form(4, false, false, false, null, true);
            } else {
                echo '<div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">';
                echo '<strong>Feil:</strong> Skjema er ikke tilgjengelig. Vennligst kontakt administrator.';
                echo '</div>';
            }
            ?>
        </div>

    </div>

<?php get_template_part('parts/components/account-layout-end'); ?>
