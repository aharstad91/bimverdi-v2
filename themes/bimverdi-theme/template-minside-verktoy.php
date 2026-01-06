<?php
/**
 * Template Name: Min Side - Verktøy
 * 
 * Template for displaying user's registered tools
 * Implements Variant B (Dividers/Whitespace) design from UI-CONTRACT.md
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

// Get user's company
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

// Get tools owned by user's company (not by author!)
$user_tools = array();

if ($company_id) {
    // Check if user is hovedkontakt for their company
    $hovedkontakt = get_field('hovedkontaktperson', $company_id);
    $is_hovedkontakt = ($hovedkontakt == $user_id);
    
    // Only show tools if user is hovedkontakt
    if ($is_hovedkontakt) {
        $args = array(
            'post_type' => 'verktoy',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'draft', 'pending'),
            'meta_query' => array(
                array(
                    'key' => 'eier_leverandor',
                    'value' => $company_id,
                    'compare' => '='
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $user_tools = get_posts($args);
    }
}
?>

<!-- Main Content Container -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

<!-- PageHeader: Title, Subtitle, Primary Action (Variant B Style) -->
<div class="mb-12">
    <div class="flex flex-col md:flex-row md:items-start justify-between gap-6 md:gap-8">
        <div class="flex-1">
            <h1 class="text-4xl font-bold tracking-tight text-[#1A1A1A] mb-2">Mine Verktøy</h1>
            <p class="text-lg text-[#5A5A5A] max-w-2xl">
                Oversikt over verktøy, lisenser og tilganger for ditt foretak.
            </p>
        </div>
        <div class="flex-shrink-0">
            <wa-button variant="brand" size="large" href="<?php echo esc_url(home_url('/min-side/registrer-verktoy/')); ?>" class="flex items-center gap-2">
                <wa-icon slot="prefix" name="plus" library="fa"></wa-icon>
                Nytt verktøy
            </wa-button>
        </div>
    </div>
</div>

<?php if (empty($user_tools)): ?>
    <!-- Empty State: Borderless, Centered -->
    <div class="py-20 text-center">
        <div class="flex flex-col items-center max-w-md mx-auto">
            <div class="w-24 h-24 bg-[#F7F5EF] rounded-lg flex items-center justify-center mb-6">
                <wa-icon name="wrench" library="fa" style="font-size: 3rem; color: #9D8F7F;"></wa-icon>
            </div>
            <h2 class="text-2xl font-bold text-[#1A1A1A] mb-3">Ingen verktøy registrert ennå</h2>
            <p class="text-[#5A5A5A] mb-8 leading-relaxed">
                Del dine favorittverktøy med BIM Verdi-medlemmer! Registrer programvare, plugins, 
                eller andre nyttige verktøy du bruker i ditt daglige arbeid.
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <wa-button variant="brand" size="large" href="<?php echo esc_url(home_url('/min-side/registrer-verktoy/')); ?>">
                    <wa-icon slot="prefix" name="plus" library="fa"></wa-icon>
                    Registrer ditt første verktøy
                </wa-button>
                <wa-button variant="neutral" outline size="large" href="<?php echo esc_url(home_url('/verktoy/')); ?>">
                    <wa-icon slot="prefix" name="eye" library="fa"></wa-icon>
                    Se verktøykatalog
                </wa-button>
            </div>
        </div>
    </div>
    
    <!-- Placeholder Template - Mal / Placeholder (Variant B Requirement) -->
    <div class="border-t border-[#D6D1C6] mt-20 pt-12">
        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#5A5A5A] mb-8">
            <wa-icon name="rectangle-list" library="fa"></wa-icon>
            Mal / Placeholder
        </div>
        
        <div class="space-y-0 max-w-4xl">
            <!-- Sample Tool Row 1 -->
            <div class="py-6 border-b border-[#D6D1C6] group hover:bg-[#F7F5EF] transition-colors">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-center">
                    <div class="md:col-span-2">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-[#EFE9DE] rounded-lg flex items-center justify-center flex-shrink-0">
                                <wa-icon name="cube" library="fa" style="color: #9D8F7F;"></wa-icon>
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-[#1A1A1A] text-base">Solibri Office</div>
                                <div class="text-xs text-[#5A5A5A]">Solibri, Inc.</div>
                            </div>
                        </div>
                    </div>
                    <div class="hidden sm:block">
                        <span class="inline-block text-xs font-medium bg-[#E5E0D8] text-[#5A5A5A] px-2.5 py-1 rounded">
                            Programvare
                        </span>
                    </div>
                    <div class="hidden md:block">
                        <span class="inline-block text-xs font-medium text-[#5A5A5A]">Windows</span>
                    </div>
                    <div>
                        <wa-badge variant="success" size="small">Aktiv</wa-badge>
                    </div>
                    <div class="text-right flex items-center justify-end gap-1">
                        <button class="p-2 hover:bg-[#EFE9DE] rounded transition-colors text-[#5A5A5A] hover:text-[#1A1A1A]">
                            <wa-icon name="eye" library="fa" size="sm"></wa-icon>
                        </button>
                        <button class="p-2 hover:bg-[#EFE9DE] rounded transition-colors text-[#5A5A5A] hover:text-[#1A1A1A]">
                            <wa-icon name="pen" library="fa" size="sm"></wa-icon>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Sample Tool Row 2 -->
            <div class="py-6 border-b border-[#D6D1C6] group hover:bg-[#F7F5EF] transition-colors">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-center">
                    <div class="md:col-span-2">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-[#EFE9DE] rounded-lg flex items-center justify-center flex-shrink-0">
                                <wa-icon name="cube" library="fa" style="color: #9D8F7F;"></wa-icon>
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-[#1A1A1A] text-base">Dalux</div>
                                <div class="text-xs text-[#5A5A5A]">Dalux AS</div>
                            </div>
                        </div>
                    </div>
                    <div class="hidden sm:block">
                        <span class="inline-block text-xs font-medium bg-[#E5E0D8] text-[#5A5A5A] px-2.5 py-1 rounded">
                            Tjeneste
                        </span>
                    </div>
                    <div class="hidden md:block">
                        <span class="text-xs font-medium text-[#5A5A5A]">Web, iOS</span>
                    </div>
                    <div>
                        <wa-badge variant="success" size="small">Aktiv</wa-badge>
                    </div>
                    <div class="text-right flex items-center justify-end gap-1">
                        <button class="p-2 hover:bg-[#EFE9DE] rounded transition-colors text-[#5A5A5A] hover:text-[#1A1A1A]">
                            <wa-icon name="eye" library="fa" size="sm"></wa-icon>
                        </button>
                        <button class="p-2 hover:bg-[#EFE9DE] rounded transition-colors text-[#5A5A5A] hover:text-[#1A1A1A]">
                            <wa-icon name="pen" library="fa" size="sm"></wa-icon>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Tools Table (Variant B - Clean, Divider-based Layout) -->
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-[#D6D1C6]">
                    <th class="px-0 py-4 font-medium text-[#5A5A5A] text-sm md:w-auto">Verktøy</th>
                    <th class="px-4 py-4 font-medium text-[#5A5A5A] text-sm hidden sm:table-cell">Type</th>
                    <th class="px-4 py-4 font-medium text-[#5A5A5A] text-sm hidden md:table-cell">Plattform</th>
                    <th class="px-4 py-4 font-medium text-[#5A5A5A] text-sm">Status</th>
                    <th class="px-4 py-4 font-medium text-[#5A5A5A] text-sm hidden lg:table-cell">Oppdatert</th>
                    <th class="px-4 py-4 font-medium text-[#5A5A5A] text-sm text-right pr-0">Handlinger</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($user_tools as $tool): 
                    $logo = get_field('logo', $tool->ID);
                    $beskrivelse = get_field('beskrivelse', $tool->ID);
                    $nettside = get_field('nettside', $tool->ID);
                    $kategori_terms = get_the_terms($tool->ID, 'verktoy_kategori');
                    $tool_status = get_post_status($tool->ID);
                    $status_variant = $tool_status === 'publish' ? 'success' : 'warning';
                    $status_label = $tool_status === 'publish' ? 'Publisert' : ($tool_status === 'pending' ? 'Venter' : 'Kladd');
                    $updated_date = get_the_modified_date('d.m.Y', $tool->ID);
                ?>
                <tr class="border-b border-[#D6D1C6] hover:bg-[#F7F5EF] transition-colors group">
                    <!-- Verktøy Name & Vendor -->
                    <td class="px-0 py-6">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-[#EFE9DE] rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden">
                                <?php if ($logo): ?>
                                    <img src="<?php echo esc_url($logo['url']); ?>" alt="<?php echo esc_attr($tool->post_title); ?>" class="w-full h-full object-cover" />
                                <?php else: ?>
                                    <wa-icon name="cube" library="fa" style="color: #9D8F7F;"></wa-icon>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0">
                                <a href="<?php echo esc_url(get_permalink($tool->ID)); ?>" class="font-semibold text-[#1A1A1A] text-base hover:text-[#FF8B5E] transition-colors block truncate">
                                    <?php echo esc_html($tool->post_title); ?>
                                </a>
                                <div class="text-xs text-[#5A5A5A]">
                                    <?php echo esc_html(get_field('vendor', $tool->ID) ?: 'Leverandør ikke angitt'); ?>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Type -->
                    <td class="px-4 py-6 hidden sm:table-cell text-[#5A5A5A] align-middle">
                        <?php 
                            $type = get_field('type_verktoey', $tool->ID);
                            if ($type):
                        ?>
                            <span class="inline-block text-xs font-medium bg-[#E5E0D8] text-[#5A5A5A] px-2.5 py-1 rounded">
                                <?php echo esc_html($type); ?>
                            </span>
                        <?php endif; ?>
                    </td>

                    <!-- Plattform -->
                    <td class="px-4 py-6 hidden md:table-cell align-middle">
                        <div class="flex flex-wrap gap-1 text-xs">
                            <?php 
                                $plattformer = get_field('plattform', $tool->ID);
                                if ($plattformer):
                                    if (is_array($plattformer)):
                                        foreach (array_slice($plattformer, 0, 2) as $plat):
                            ?>
                                <span class="text-[#5A5A5A]"><?php echo esc_html($plat); ?></span>
                            <?php 
                                        endforeach;
                                        if (count($plattformer) > 2):
                            ?>
                                <span class="text-[#5A5A5A]">+<?php echo count($plattformer) - 2; ?></span>
                            <?php 
                                        endif;
                                    else:
                            ?>
                                <span class="text-[#5A5A5A]"><?php echo esc_html($plattformer); ?></span>
                            <?php 
                                    endif;
                                endif;
                            ?>
                        </div>
                    </td>

                    <!-- Status -->
                    <td class="px-4 py-6 align-middle">
                        <wa-badge variant="<?php echo $status_variant; ?>" size="small"><?php echo $status_label; ?></wa-badge>
                    </td>

                    <!-- Oppdatert -->
                    <td class="px-4 py-6 hidden lg:table-cell text-[#5A5A5A] align-middle text-sm">
                        <?php echo $updated_date; ?>
                    </td>

                    <!-- Handlinger -->
                    <td class="px-4 py-6 text-right pr-0 align-middle">
                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="<?php echo esc_url(get_permalink($tool->ID)); ?>" title="Se verktøy" class="p-2 hover:bg-[#EFE9DE] rounded transition-colors text-[#5A5A5A] hover:text-[#1A1A1A]">
                                <wa-icon name="eye" library="fa" size="sm"></wa-icon>
                            </a>
                            <a href="<?php echo esc_url(home_url('/min-side/rediger-verktoy/?tool_id=' . $tool->ID)); ?>" title="Rediger verktøy" class="p-2 hover:bg-[#EFE9DE] rounded transition-colors text-[#5A5A5A] hover:text-[#1A1A1A]">
                                <wa-icon name="pen" library="fa" size="sm"></wa-icon>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>


<style>
/* Variant B (Dividers/Whitespace) - Normative Design */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Hairline dividers - subtle border */
:where(table) tr {
    transition: background-color 0.2s ease;
}

/* Row action visibility - only show on hover */
:where(table) tbody tr:not(:hover) .opacity-0 {
    pointer-events: none;
}
</style>

</main>

<?php get_footer(); ?>

