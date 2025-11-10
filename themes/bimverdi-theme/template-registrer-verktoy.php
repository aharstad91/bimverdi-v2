<?php
/**
 * Template Name: Registrer Verktøy
 * 
 * Tool registration page with Gravity Forms
 * Allows company owners to register and share tools/resources
 * 
 * @package BimVerdi_Theme
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

// Redirect if not connected to a company
if (!$company_id) {
    wp_redirect(home_url('/min-side/'));
    exit;
}

get_header();

$user_roles = $current_user->roles;
$is_company_owner = in_array('company_owner', $user_roles) || current_user_can('manage_options');
$company = $company_id ? get_post($company_id) : null;

?>

<!-- Min Side Horizontal Tab Navigation -->
<?php 
$current_tab = 'verktoy';
if (function_exists('get_template_part')) {
    get_template_part('template-parts/minside-tabs', null, array('current_tab' => $current_tab));
}
?>

<div class="min-h-screen bg-bim-beige-100 py-8">
    <div class="container mx-auto px-4 max-w-2xl">
        
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-bim-black-900 mb-2">Registrer Verktøy</h1>
            <p class="text-bim-black-700">Del verktøy og ressurser med BIM Verdi-nettverket</p>
        </div>

        <!-- Company Info Card -->
        <?php if ($company): ?>
        <div class="card bg-white shadow-lg mb-8">
            <div class="card-body p-6">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 bg-bim-orange text-white rounded-lg flex items-center justify-center font-bold text-xl">
                            <?php echo strtoupper(substr($company->post_title, 0, 2)); ?>
                        </div>
                    </div>
                    <div class="flex-grow">
                        <h2 class="text-2xl font-bold text-bim-black-900">
                            <?php echo esc_html($company->post_title); ?>
                        </h2>
                        <p class="text-bim-black-600 mt-1">
                            Registrering av verktøy som bedrift
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tool Registration Form -->
        <div class="card bg-white shadow-lg">
            <div class="card-body p-6">
                <h2 class="text-2xl font-bold text-bim-black-900 mb-6">Nytt Verktøy</h2>

                <?php
                // Display Gravity Form ID 1
                if (function_exists('gravity_form')) {
                    gravity_form(1, false, false, false, null, false); // AJAX disabled for better form handling
                } else {
                    echo '<p class="text-red-600">Skjema er ikke tilgjengelig.</p>';
                }
                ?>

            </div>
        </div>

        <!-- Info Card -->
        <div class="card bg-white shadow-lg mt-8">
            <div class="card-body p-6">
                <h3 class="text-lg font-bold text-bim-black-900 mb-4">Om verktøyregistrering</h3>
                <ul class="space-y-3 text-bim-black-700">
                    <li class="flex items-start gap-2">
                        <span class="text-bim-orange-500 font-bold">•</span>
                        <span>Registrer verktøy, programvare eller ressurser som din bedrift tilbyr</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-bim-orange-500 font-bold">•</span>
                        <span>Legg til detaljert beskrivelse, link og kategori for verktøyet</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-bim-orange-500 font-bold">•</span>
                        <span>Verktøyet blir synlig for alle medlemmer i nettverket</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-bim-orange-500 font-bold">•</span>
                        <span>Du kan redigere eller slette verktøy fra din bedriftsprofil</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Back to Dashboard Link -->
        <div class="mt-8 text-center">
            <a href="<?php echo esc_url(home_url('/min-side/')); ?>" class="text-bim-orange-500 hover:underline font-semibold">
                ← Tilbake til Min Side
            </a>
        </div>

    </div>
</div>

<?php get_footer(); ?>
