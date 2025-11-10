<?php
/**
 * Template Name: Rediger Bruker
 * 
 * User profile editing page with Gravity Forms
 * Allows users to edit their profile information
 * 
 * @package BimVerdi_Theme
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$profile = bim_get_user_profile($user_id);

// Load Gravity Forms populate script
wp_register_script(
    'bim-gform-populate',
    get_template_directory_uri() . '/assets/js/gform-populate.js',
    array('jquery'),
    '1.0.0',
    true
);

wp_localize_script('bim-gform-populate', 'bimGformData', array(
    'userEmail' => $current_user->user_email ?? '',
    'firstName' => $current_user->first_name ?? '',
    'lastName' => $current_user->last_name ?? '',
    'phone' => $profile['phone'] ?? '',
    'jobTitle' => $profile['job_title'] ?? '',
    'linkedinUrl' => $profile['linkedin_url'] ?? '',
));

wp_enqueue_script('bim-gform-populate');

?>

<!-- Min Side Horizontal Tab Navigation -->
<?php 
$current_tab = 'rediger';
if (function_exists('get_template_part')) {
    get_template_part('template-parts/minside-tabs', null, array('current_tab' => $current_tab));
}
?>

<div class="min-h-screen bg-bim-beige-100 py-8">
    <div class="container mx-auto px-4 max-w-2xl">
        
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-bim-black-900 mb-2">Rediger Profil</h1>
            <p class="text-bim-black-700">Oppdater din personlige informasjon</p>
        </div>

        <!-- Profile Summary Card -->
        <div class="card bg-white shadow-lg mb-8">
            <div class="card-body p-6">
                <div class="flex items-center gap-6">
                    <div class="flex-shrink-0">
                        <?php echo get_avatar($current_user->user_email, 80, '', bim_get_user_display_name($user_id), array('class' => 'rounded-full w-20 h-20')); ?>
                    </div>
                    <div class="flex-grow">
                        <h2 class="text-2xl font-bold text-bim-black-900">
                            <?php echo esc_html(bim_get_user_display_name($user_id)); ?>
                        </h2>
                        <p class="text-bim-black-600 mb-2"><?php echo esc_html($current_user->user_email); ?></p>
                        
                        <?php if (!empty($profile['job_title'])): ?>
                            <p class="text-bim-orange-500 font-semibold">
                                <?php echo esc_html($profile['job_title']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($profile['phone'])): ?>
                            <p class="text-bim-black-600">
                                📞 <a href="tel:<?php echo esc_attr($profile['phone']); ?>" class="text-bim-orange-500 hover:underline">
                                    <?php echo esc_html($profile['phone']); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Profile Form with Gravity Forms -->
        <div class="card bg-white shadow-lg">
            <div class="card-body p-6">
                <h2 class="text-2xl font-bold text-bim-black-900 mb-6">Rediger Informasjon</h2>

                <?php
                // Display Gravity Form ID 4
                if (function_exists('gravity_form')) {
                    gravity_form(4, false, false, false, null, true);
                } else {
                    echo '<p class="text-red-600">Skjema er ikke tilgjengelig.</p>';
                }
                ?>

            </div>
        </div>

        <!-- Back to Profile Link -->
        <div class="mt-8 text-center">
            <a href="<?php echo esc_url(home_url('/min-profil/')); ?>" class="text-bim-orange-500 hover:underline font-semibold">
                ← Tilbake til Min Profil
            </a>
        </div>

    </div>
</div>

<?php get_footer(); ?>
