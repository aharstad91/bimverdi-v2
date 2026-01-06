<?php
/**
 * Template Name: Rediger Verktøy
 * 
 * Tool editing page - uses Gravity Forms with pre-populated data
 * Implements Variant B (Dividers/Whitespace) design from UI-CONTRACT.md
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

// Start Min Side layout
get_template_part('template-parts/minside-layout-start', null, array(
    'current_page' => 'verktoy',
    'page_title' => 'Rediger Verktøy',
    'page_icon' => 'pen',
    'page_description' => 'Oppdater informasjon om ' . esc_html($tool->post_title),
));

$company = $company_id ? get_post($company_id) : null;

// Get tool data for display
$tool_name = $tool->post_title;
$tool_category_terms = wp_get_post_terms($tool_id, 'verktoykategori');
$tool_category = !empty($tool_category_terms) ? $tool_category_terms[0]->name : 'Ingen kategori';
$tool_status = get_post_status($tool_id);
$tool_updated = get_the_modified_date('d.m.Y H:i', $tool_id);
?>

<!-- Breadcrumb & Header (Variant B Style) -->
<div class="mb-12">
    <nav class="flex items-center text-sm text-[#5A5A5A] mb-6">
        <a href="<?php echo esc_url(home_url('/min-side/mine-verktoy/')); ?>" class="hover:text-[#1A1A1A] transition-colors flex items-center gap-1">
            <wa-icon name="chevron-left" library="fa" style="font-size: 0.875rem;"></wa-icon>
            Mine Verktøy
        </a>
        <span class="mx-2 text-[#E5E0D8]">/</span>
        <span class="text-[#1A1A1A] font-medium"><?php echo esc_html($tool_name); ?></span>
    </nav>

    <div class="flex flex-col md:flex-row md:items-start justify-between gap-6 md:gap-8">
        <div class="flex-1">
            <h1 class="text-4xl font-bold tracking-tight text-[#1A1A1A] mb-2">Rediger Verktøy</h1>
            <p class="text-lg text-[#5A5A5A] max-w-2xl">
                Oppdater informasjon om <?php echo esc_html($tool_name); ?>
            </p>
        </div>
        <div class="flex-shrink-0 flex flex-col sm:flex-row gap-3">
            <a href="<?php echo esc_url(home_url('/min-side/mine-verktoy/')); ?>" class="inline-flex items-center justify-center px-4 py-2 border border-[#1A1A1A] text-[#1A1A1A] rounded-lg hover:bg-[#F7F5EF] transition-colors font-medium text-sm">
                <wa-icon name="x" library="fa" style="font-size: 0.875rem; margin-right: 0.5rem;"></wa-icon>
                Avbryt
            </a>
            <a href="<?php echo esc_url(get_permalink($tool_id)); ?>" target="_blank" class="inline-flex items-center justify-center px-4 py-2 bg-[#F7F5EF] text-[#1A1A1A] rounded-lg hover:bg-[#EFE9DE] transition-colors font-medium text-sm">
                <wa-icon name="eye" library="fa" style="font-size: 0.875rem; margin-right: 0.5rem;"></wa-icon>
                Se verktøy
            </a>
        </div>
    </div>
</div>

<!-- Info Box (Borderless Section) -->
<div class="mb-12 pb-8 border-b border-[#D6D1C6]">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <!-- Status -->
        <div>
            <div class="text-xs font-bold uppercase tracking-wider text-[#5A5A5A] mb-2">Status</div>
            <div class="flex items-center gap-2">
                <?php 
                    $status_badge = $tool_status === 'publish' ? 'success' : 'warning';
                    $status_label = $tool_status === 'publish' ? 'Publisert' : ($tool_status === 'pending' ? 'Venter' : 'Kladd');
                ?>
                <wa-badge variant="<?php echo $status_badge; ?>" size="small"><?php echo $status_label; ?></wa-badge>
            </div>
        </div>

        <!-- Last Updated -->
        <div>
            <div class="text-xs font-bold uppercase tracking-wider text-[#5A5A5A] mb-2">Sist oppdatert</div>
            <div class="text-[#1A1A1A] text-sm"><?php echo $tool_updated; ?></div>
        </div>

        <!-- Category -->
        <div>
            <div class="text-xs font-bold uppercase tracking-wider text-[#5A5A5A] mb-2">Kategori</div>
            <div class="text-[#1A1A1A] text-sm"><?php echo esc_html($tool_category); ?></div>
        </div>

        <!-- Company -->
        <?php if ($company): ?>
        <div>
            <div class="text-xs font-bold uppercase tracking-wider text-[#5A5A5A] mb-2">Bedrift</div>
            <div class="text-[#1A1A1A] text-sm"><?php echo esc_html($company->post_title); ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Form Section with Dividers -->
<div class="mb-12">
    <h2 class="text-xl font-bold text-[#1A1A1A] mb-8">Rediger Informasjon</h2>

    <div class="max-w-4xl">
        <?php
        // Display Gravity Form ID 1 with tool_id parameter for pre-population
        if (function_exists('gravity_form')) {
            // Wrap form with Variant B styling
            echo '<div class="gform-wrapper">';
            gravity_form(
                1,                    // Form ID [Bruker] - Registrering av verktøy
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
            echo '</div>';
        } else {
            echo '<wa-alert variant="error" open>';
            echo '<wa-icon slot="icon" name="exclamation-circle" library="fa"></wa-icon>';
            echo '<strong>Feil:</strong> Gravity Forms er ikke tilgjengelig. Vennligst kontakt administrator.';
            echo '</wa-alert>';
        }
        ?>
    </div>
</div>

<!-- Information Box (Borderless, top border divider) -->
<div class="pt-8 border-t border-[#D6D1C6]">
    <div class="bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg p-6 max-w-2xl">
        <h3 class="text-sm font-bold text-[#1A1A1A] uppercase tracking-wider mb-4 flex items-center gap-2">
            <wa-icon name="circle-info" library="fa"></wa-icon>
            Viktig informasjon
        </h3>
        <ul class="space-y-3 text-sm text-[#5A5A5A]">
            <li class="flex gap-3">
                <span class="text-[#FF8B5E] flex-shrink-0 pt-1">•</span>
                <span>Endringer blir lagret som utkast og må godkjennes av administrator før de publiseres</span>
            </li>
            <li class="flex gap-3">
                <span class="text-[#FF8B5E] flex-shrink-0 pt-1">•</span>
                <span>Du mottar e-post når endringene er godkjent</span>
            </li>
            <li class="flex gap-3">
                <span class="text-[#FF8B5E] flex-shrink-0 pt-1">•</span>
                <span>Alle felt kan redigeres, inkludert bilder, beskrivelse og kategorier</span>
            </li>
        </ul>
    </div>
</div>

<style>
/* Gravity Form styling for Variant B (Dividers/Whitespace) */
.gform-wrapper .gform_wrapper {
    background: transparent;
    padding: 0;
    border: none;
    box-shadow: none;
}

.gform-wrapper .gform_wrapper input[type="text"],
.gform-wrapper .gform_wrapper input[type="email"],
.gform-wrapper .gform_wrapper input[type="url"],
.gform-wrapper .gform_wrapper textarea,
.gform-wrapper .gform_wrapper select {
    border: 1px solid #D6D1C6;
    border-radius: 0.5rem;
    padding: 0.75rem;
    font-size: 1rem;
    color: #1A1A1A;
}

.gform-wrapper .gform_wrapper input:focus,
.gform-wrapper .gform_wrapper textarea:focus,
.gform-wrapper .gform_wrapper select:focus {
    outline: none;
    border-color: #FF8B5E;
    box-shadow: 0 0 0 3px rgba(255, 139, 94, 0.1);
}

.gform-wrapper .gform_wrapper .gfield {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #D6D1C6;
}

.gform-wrapper .gform_wrapper .gfield:last-child {
    border-bottom: none;
}

.gform-wrapper .gform_wrapper label {
    display: block;
    font-weight: 500;
    color: #5A5A5A;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.gform-wrapper .gform_wrapper .gform_footer {
    padding: 0;
    background: transparent;
    border: none;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #D6D1C6;
}

.gform-wrapper .gform_wrapper .gform_footer input[type="submit"] {
    background: #1A1A1A;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 500;
    cursor: pointer;
}

.gform-wrapper .gform_wrapper .gform_footer input[type="submit"]:hover {
    background: #333;
}
</style>

<?php 
get_template_part('template-parts/minside-layout-end');
get_footer(); 
?>
