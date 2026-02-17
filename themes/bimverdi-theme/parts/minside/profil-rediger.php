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

// Get user data via helper
$display_name = bim_get_user_display_name($user_id);
$avatar_url = bim_get_user_profile_image_url($user_id, 'thumbnail');

// Check for welcome redirect (new colleague after email verification)
$is_welcome = isset($_GET['welcome']) && $_GET['welcome'] === '1';
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

        <!-- User Info Badge -->
        <div class="mb-8 p-4 bg-[#F5F5F4] border border-[#E7E5E4] rounded-lg flex items-center gap-4">
            <img src="<?php echo esc_url($avatar_url); ?>" alt="" class="w-14 h-14 rounded-full object-cover flex-shrink-0">
            <div>
                <p class="font-semibold text-[#111827]"><?php echo esc_html($display_name); ?></p>
                <p class="text-sm text-[#57534E]"><?php echo esc_html($current_user->user_email); ?></p>
            </div>
        </div>

        <!-- Gravity Form -->
        <div class="bg-white border border-[#E7E5E4] rounded-lg p-8">
            <h2 class="text-xl font-bold text-[#111827] mb-6 flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#57534E]">
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
