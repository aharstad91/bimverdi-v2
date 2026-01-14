<?php
/**
 * Part: Rediger verktøy
 * 
 * Skjema for redigering av eksisterende verktøy.
 * Brukes på /min-side/rediger-verktoy/?tool_id=XX
 * 
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

// Get tool ID from URL parameter (support both 'id' and 'tool_id' for compatibility)
$tool_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['tool_id']) ? intval($_GET['tool_id']) : 0);

// Redirect if no tool ID
if (!$tool_id) {
    wp_redirect(home_url('/min-side/mine-verktoy/'));
    exit;
}

// Get the tool post
$tool = get_post($tool_id);

// Verify tool exists
if (!$tool || $tool->post_type !== 'verktoy') {
    wp_redirect(home_url('/min-side/mine-verktoy/'));
    exit;
}

// Check if user has permission to edit
$tool_author = $tool->post_author;
$tool_company = get_field('eier_leverandor', $tool_id);

$can_edit = false;
if ($tool_author == $user_id) {
    $can_edit = true;
}
if ($company_id && $tool_company && $tool_company == $company_id) {
    $can_edit = true;
}
if (current_user_can('manage_options')) {
    $can_edit = true;
}

if (!$can_edit) {
    wp_redirect(home_url('/min-side/mine-verktoy/'));
    exit;
}

// Get tool data
$tool_name = $tool->post_title;
$tool_category_terms = wp_get_post_terms($tool_id, 'verktoykategori');
$tool_category = !empty($tool_category_terms) ? $tool_category_terms[0]->name : 'Ukategorisert';
$tool_status = get_post_status($tool_id);
$tool_updated = get_the_modified_date('d.m.Y', $tool_id);
?>

<!-- Breadcrumb -->
<nav class="mb-6" aria-label="Brødsmulesti">
    <ol class="flex items-center gap-2 text-sm text-[#5A5A5A]">
        <li>
            <a href="<?php echo esc_url(home_url('/min-side/')); ?>" class="hover:text-[#1A1A1A] transition-colors">
                Min side
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li>
            <a href="<?php echo esc_url(home_url('/min-side/mine-verktoy/')); ?>" class="hover:text-[#1A1A1A] transition-colors">
                Mine verktøy
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li class="text-[#1A1A1A] font-medium" aria-current="page"><?php echo esc_html($tool_name); ?></li>
    </ol>
</nav>

<!-- Page Header -->
<?php
get_template_part('parts/components/page-header', null, [
    'title' => 'Rediger verktøy',
    'description' => 'Oppdater informasjon om ' . esc_html($tool_name)
]);
?>

<!-- Form Container (960px centered per _claude/ui-contract.md) -->
<div class="max-w-3xl mx-auto">
    
    <!-- Tool Info Badge -->
    <div class="mb-8 p-4 bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg flex items-center justify-between">
        <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
            <div>
                <p class="font-semibold text-[#1A1A1A]"><?php echo esc_html($tool_name); ?></p>
                <p class="text-sm text-[#5A5A5A]"><?php echo esc_html($tool_category); ?></p>
            </div>
        </div>
        <div class="text-right">
            <?php 
            $status_label = $tool_status === 'publish' ? 'Publisert' : 'Utkast';
            $status_class = $tool_status === 'publish' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800';
            ?>
            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?php echo $status_class; ?>">
                <?php echo $status_label; ?>
            </span>
            <p class="text-xs text-[#5A5A5A] mt-1">Oppdatert <?php echo $tool_updated; ?></p>
        </div>
    </div>
    
    <!-- Gravity Form -->
    <div class="bg-white border border-[#E5E0D5] rounded-lg p-8">
        <h2 class="text-xl font-bold text-[#1A1A1A] mb-6 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
                <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.375 2.625a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4Z"/>
            </svg>
            Oppdater verktøydetaljer
        </h2>
        
        <?php
        // Display Gravity Form ID 1 with pre-populated data
        // Using entry_id or field population
        if (function_exists('gravity_form')) {
            // Pass tool_id to form for pre-population
            gravity_form(1, false, false, false, array(
                'tool_id' => $tool_id,
            ), true);
        } else {
            echo '<div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">';
            echo '<strong>Feil:</strong> Skjema er ikke tilgjengelig. Vennligst kontakt administrator.';
            echo '</div>';
        }
        ?>
    </div>
    
    <!-- Danger Zone -->
    <div class="mt-12 pt-8 border-t border-[#D6D1C6]">
        <h3 class="text-sm font-bold text-[#1A1A1A] uppercase tracking-wider mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/>
                <path d="M12 9v4"/>
                <path d="M12 17h.01"/>
            </svg>
            Faresone
        </h3>
        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-800 mb-3">
                Vil du slette dette verktøyet permanent? Denne handlingen kan ikke angres.
            </p>
            <button type="button" 
                    onclick="if(confirm('Er du sikker på at du vil slette dette verktøyet?')) { window.location.href='<?php echo esc_url(add_query_arg(['action' => 'delete_tool', 'tool_id' => $tool_id, '_wpnonce' => wp_create_nonce('delete_tool_' . $tool_id)], home_url('/min-side/mine-verktoy/'))); ?>'; }"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"/>
                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                </svg>
                Slett verktøy
            </button>
        </div>
    </div>
    
    <!-- Back Link -->
    <div class="mt-8 pt-6 border-t border-[#E5E0D5]">
        <a href="<?php echo esc_url(home_url('/min-side/mine-verktoy/')); ?>" class="inline-flex items-center gap-2 text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Tilbake til mine verktøy
        </a>
    </div>
</div>
