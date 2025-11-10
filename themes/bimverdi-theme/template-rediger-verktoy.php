<?php
/**
 * Template Name: Rediger Verktøy
 * 
 * Tool editing page - reuses Gravity Forms with pre-populated data
 * Allows users to edit their registered tools from Min Side
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

// Get tool ID from URL parameter
$tool_id = isset($_GET['tool_id']) ? intval($_GET['tool_id']) : 0;

// Redirect if no tool ID
if (!$tool_id) {
    wp_redirect(home_url('/min-side/mine-verktoy/'));
    exit;
}

// Get the tool post
$tool = get_post($tool_id);

// Verify tool exists and user has permission to edit
if (!$tool || $tool->post_type !== 'verktoy') {
    wp_redirect(home_url('/min-side/mine-verktoy/'));
    exit;
}

// Check if user owns this tool (either author or company owner)
$tool_author = $tool->post_author;
$tool_company = get_field('eier_leverandor', $tool_id);

$can_edit = false;

// Check if user is the author
if ($tool_author == $user_id) {
    $can_edit = true;
}

// Check if user's company owns the tool
if ($company_id && $tool_company && $tool_company == $company_id) {
    $can_edit = true;
}

// Admin can always edit
if (current_user_can('manage_options')) {
    $can_edit = true;
}

// Redirect if user doesn't have permission
if (!$can_edit) {
    wp_redirect(home_url('/min-side/mine-verktoy/'));
    exit;
}

get_header();

$company = $company_id ? get_post($company_id) : null;

// Get tool data for display
$tool_name = $tool->post_title;
$tool_category_terms = wp_get_post_terms($tool_id, 'verktoykategori');
$tool_category = !empty($tool_category_terms) ? $tool_category_terms[0]->name : 'Ingen kategori';
?>

<!-- Min Side Horizontal Tab Navigation -->
<?php 
$current_tab = 'verktoy';
get_template_part('template-parts/minside-tabs', null, array('current_tab' => $current_tab));
?>

<div class="min-h-screen bg-bim-beige-100 py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        
        <!-- Back Button -->
        <div class="mb-6">
            <a href="<?php echo esc_url(home_url('/min-side/mine-verktoy/')); ?>" class="text-bim-orange hover:underline flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Tilbake til Mine Verktøy
            </a>
        </div>

        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-start gap-4">
                <div class="bg-bim-orange text-white rounded-lg p-4 flex items-center justify-center" style="min-width: 80px; min-height: 80px;">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </div>
                <div class="flex-grow">
                    <h1 class="text-3xl font-bold text-bim-black-900 mb-2">
                        Rediger Verktøy
                    </h1>
                    <p class="text-lg text-bim-black-700 mb-1">
                        <?php echo esc_html($tool_name); ?>
                    </p>
                    <div class="flex items-center gap-4 text-sm text-bim-black-600">
                        <?php if ($company): ?>
                            <span>Bedrift: <?php echo esc_html($company->post_title); ?></span>
                        <?php endif; ?>
                        <span>Kategori: <?php echo esc_html($tool_category); ?></span>
                        <span class="badge badge-sm bg-yellow-500 text-white border-0">
                            <?php echo $tool->post_status === 'publish' ? 'Publisert' : 'Utkast'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tool Edit Form -->
        <div class="card bg-white shadow-lg">
            <div class="card-body p-6">
                <h2 class="text-2xl font-bold text-bim-black-900 mb-6">Rediger Informasjon</h2>

                <?php
                // Display Gravity Form ID 1 with tool_id parameter for pre-population
                if (function_exists('gravity_form')) {
                    // Pass tool_id as parameter to pre-populate the form
                    gravity_form(
                        1,                    // Form ID
                        false,                // Display title
                        false,                // Display description
                        false,                // Display inactive
                        array(
                            'tool_id' => $tool_id  // Pre-populate tool_id hidden field
                        ),
                        false,                // Use AJAX (DISABLED for better pre-population)
                        0,                    // Tabindex
                        true                  // Echo (display immediately)
                    );
                } else {
                    echo '<div class="alert alert-error">';
                    echo '<p>Gravity Forms er ikke tilgjengelig. Vennligst kontakt administrator.</p>';
                    echo '</div>';
                }
                ?>

                <!-- Info Box -->
                <div class="bg-bim-beige-100 p-6 rounded-lg mt-6">
                    <h3 class="text-lg font-bold text-bim-black-900 mb-3">Om redigering</h3>
                    <ul class="space-y-2 text-bim-black-700">
                        <li class="flex items-start gap-2">
                            <span class="text-bim-orange-500 font-bold">•</span>
                            <span>Endringer blir lagret som utkast og må godkjennes av administrator</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-bim-orange-500 font-bold">•</span>
                            <span>Du mottar e-post når endringene er godkjent</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-bim-orange-500 font-bold">•</span>
                            <span>Alle felt kan redigeres, inkludert bilder og kategorier</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<?php get_footer(); ?>
