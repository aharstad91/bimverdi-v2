<?php
/**
 * Template Name: Min Side - Rediger Foretak
 * 
 * Edit company profile page for BIM Verdi members (hovedkontakt only)
 * Uses Gravity Forms for form handling (Form ID 7)
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
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

// Also check ACF field
if (!$company_id && function_exists('get_field')) {
    $company_id = get_field('tilknyttet_foretak', 'user_' . $user_id);
    if (is_object($company_id)) {
        $company_id = $company_id->ID;
    }
}

// Check if user has a company
if (!$company_id) {
    wp_redirect(home_url('/min-side/foretak/?ikke_koblet=1'));
    exit;
}

$company = get_post($company_id);
if (!$company || $company->post_type !== 'foretak') {
    wp_redirect(home_url('/min-side/foretak/?ugyldig=1'));
    exit;
}

// Check if user is hovedkontakt or admin
$hovedkontakt_id = get_field('hovedkontaktperson', $company_id);
$is_hovedkontakt = ($hovedkontakt_id == $user_id);
$is_admin = current_user_can('manage_options');

if (!$is_hovedkontakt && !$is_admin) {
    wp_redirect(home_url('/min-side/foretak/?ikke_hovedkontakt=1'));
    exit;
}

// Get company name for display
$bedriftsnavn = get_field('bedriftsnavn', $company_id) ?: $company->post_title;
$logo_id = get_field('logo', $company_id);
$logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'thumbnail') : '';

// Start Min Side layout
get_template_part('template-parts/minside-layout-start', null, array(
    'current_page' => 'foretak',
    'page_title' => 'Rediger foretak',
    'page_icon' => 'pen-to-square',
    'page_description' => 'Oppdater informasjon om ' . esc_html($bedriftsnavn),
));
?>

        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="<?php echo home_url('/min-side/foretak/'); ?>" class="hover:text-orange-600">Foretak</a>
            <wa-icon library="fa" name="fas-chevron-right" style="font-size: 10px;"></wa-icon>
            <span>Rediger</span>
        </div>

        <!-- Company header with logo -->
        <wa-card class="p-6 mb-6">
            <div class="flex items-center gap-4">
                <?php if ($logo_url): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($bedriftsnavn); ?>" 
                         class="w-16 h-16 rounded-lg object-contain bg-gray-100">
                <?php else: ?>
                    <div class="w-16 h-16 rounded-lg bg-gray-100 flex items-center justify-center">
                        <wa-icon library="fa" name="fas-building" class="text-gray-400 text-2xl"></wa-icon>
                    </div>
                <?php endif; ?>
                <div>
                    <h2 class="text-xl font-bold text-gray-900"><?php echo esc_html($bedriftsnavn); ?></h2>
                    <?php 
                    $org_nummer = get_field('organisasjonsnummer', $company_id);
                    if ($org_nummer): 
                    ?>
                        <p class="text-sm text-gray-500">Org.nr: <?php echo esc_html($org_nummer); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </wa-card>

        <!-- Gravity Form for editing -->
        <wa-card class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                <wa-icon library="fa" name="fas-edit" class="text-orange-500"></wa-icon>
                Oppdater foretaksinformasjon
            </h2>
            
            <?php
            // Check if Gravity Forms is active
            if (function_exists('gravity_form')) {
                // Form ID 7: [System] - Redigering av foretak
                gravity_form(
                    7,                  // Form ID
                    false,              // Display title
                    false,              // Display description
                    false,              // Do not display inactive message
                    null,               // Field values (populated via gform_pre_render filter)
                    true,               // AJAX enabled
                    1                   // Tab index
                );
            } else {
                echo '<wa-alert variant="warning" open>';
                echo '<wa-icon library="fa" name="fas-exclamation-triangle" slot="icon"></wa-icon>';
                echo 'Gravity Forms er ikke aktivert. Kontakt administrator.';
                echo '</wa-alert>';
            }
            ?>
        </wa-card>

        <!-- Additional info about taxonomies -->
        <wa-card class="p-6 mt-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <wa-icon library="fa" name="fas-tags" class="text-orange-500"></wa-icon>
                Kategorier og temagrupper
            </h2>
            <p class="text-gray-600 text-sm mb-4">
                For å endre bransjekategori, kundetype eller temagrupper, kontakt BIM Verdi administrator.
            </p>
            
            <?php
            // Show current categories
            $bransjekategorier = get_the_terms($company_id, 'bransjekategori');
            $kundetyper = get_the_terms($company_id, 'kundetype');
            $temagrupper = get_the_terms($company_id, 'temagruppe');
            ?>
            
            <div class="space-y-4">
                <?php if ($bransjekategorier && !is_wp_error($bransjekategorier)): ?>
                <div>
                    <span class="text-sm font-medium text-gray-700">Bransjekategori:</span>
                    <div class="flex flex-wrap gap-2 mt-1">
                        <?php foreach ($bransjekategorier as $term): ?>
                            <wa-tag size="small" variant="primary"><?php echo esc_html($term->name); ?></wa-tag>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($kundetyper && !is_wp_error($kundetyper)): ?>
                <div>
                    <span class="text-sm font-medium text-gray-700">Kundetype:</span>
                    <div class="flex flex-wrap gap-2 mt-1">
                        <?php foreach ($kundetyper as $term): ?>
                            <wa-tag size="small" variant="neutral"><?php echo esc_html($term->name); ?></wa-tag>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($temagrupper && !is_wp_error($temagrupper)): ?>
                <div>
                    <span class="text-sm font-medium text-gray-700">Temagrupper:</span>
                    <div class="flex flex-wrap gap-2 mt-1">
                        <?php foreach ($temagrupper as $term): ?>
                            <wa-tag size="small" variant="success"><?php echo esc_html($term->name); ?></wa-tag>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </wa-card>

        <!-- Back button -->
        <div class="mt-6">
            <a href="<?php echo home_url('/min-side/foretak/'); ?>" class="inline-flex items-center gap-2 text-gray-600 hover:text-orange-600">
                <wa-icon library="fa" name="fas-arrow-left"></wa-icon>
                Tilbake til foretaksoversikt
            </a>
        </div>

<?php
// End Min Side layout
get_template_part('template-parts/minside-layout-end', null, array(
    'current_page' => 'foretak',
));

get_footer();
?>
