<?php
/**
 * Min Side - Verktøy List Part
 * 
 * Shows user's registered tools in a table layout.
 * Used by template-minside-universal.php
 * 
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user's company
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

// Get tools owned by user's company
$user_tools = [];

if ($company_id) {
    $hovedkontakt = get_field('hovedkontaktperson', $company_id);
    $is_hovedkontakt = ($hovedkontakt == $user_id);
    
    if ($is_hovedkontakt) {
        $user_tools = get_posts([
            'post_type' => 'verktoy',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'pending'],
            'meta_query' => [['key' => 'eier_leverandor', 'value' => $company_id]],
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }
}
?>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => __('Mine verktøy', 'bimverdi'),
    'description' => __('Oversikt over verktøy, lisenser og tilganger for ditt foretak.', 'bimverdi'),
    'actions' => [
        ['text' => __('Nytt verktøy', 'bimverdi'), 'url' => '/min-side/registrer-verktoy/', 'variant' => 'primary', 'icon' => 'plus'],
    ],
]); ?>

<!-- Tools Table -->
<div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead>
            <tr class="border-b border-[#E7E5E4]">
                <th class="py-3 pr-4 text-xs font-medium text-[#57534E]"><?php _e('Verktøy', 'bimverdi'); ?></th>
                <th class="px-4 py-3 text-xs font-medium text-[#57534E] hidden sm:table-cell"><?php _e('Type', 'bimverdi'); ?></th>
                <th class="px-4 py-3 text-xs font-medium text-[#57534E] hidden md:table-cell"><?php _e('Plattform', 'bimverdi'); ?></th>
                <th class="px-4 py-3 text-xs font-medium text-[#57534E]"><?php _e('Status', 'bimverdi'); ?></th>
                <th class="px-4 py-3 text-xs font-medium text-[#57534E] hidden lg:table-cell"><?php _e('Sist oppdatert', 'bimverdi'); ?></th>
                <th class="pl-4 py-3 text-xs font-medium text-[#57534E] text-right"><?php _e('Handlinger', 'bimverdi'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($user_tools)): ?>
            <!-- Empty row with message -->
            <tr>
                <td colspan="6" class="py-8 text-center text-[#57534E]">
                    <?php _e('Ingen verktøy registrert ennå.', 'bimverdi'); ?>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($user_tools as $tool): 
                $logo = get_field('logo', $tool->ID);
                $tool_status = get_post_status($tool->ID);
                $status_class = $tool_status === 'publish' ? 'bg-[#DCFCE7] text-[#166534]' : ($tool_status === 'pending' ? 'bg-[#FEF9C3] text-[#854D0E]' : 'bg-[#FEE2E2] text-[#991B1B]');
                $status_label = $tool_status === 'publish' ? __('Aktiv', 'bimverdi') : ($tool_status === 'pending' ? __('Venter', 'bimverdi') : __('Kladd', 'bimverdi'));
                $updated_date = get_the_modified_date('d.m.Y', $tool->ID);
            ?>
            <tr class="border-b border-[#E7E5E4] hover:bg-[#F5F5F4] transition-colors group">
                <!-- Verktøy Name & Vendor -->
                <td class="py-4 pr-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-[#F5F5F4] rounded flex items-center justify-center flex-shrink-0 overflow-hidden self-center">
                            <?php if ($logo): ?>
                                <img src="<?php echo esc_url(is_array($logo) ? ($logo['sizes']['thumbnail'] ?? $logo['url']) : $logo); ?>" alt="" class="w-full h-full object-cover" />
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#A8A29E]"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="<?php echo get_permalink($tool->ID); ?>" class="font-medium text-[#111827] hover:text-[#F97316] transition-colors block leading-tight">
                                <?php echo esc_html($tool->post_title); ?>
                            </a>
                            <p class="text-xs text-[#57534E] mt-0.5"><?php echo esc_html(get_field('vendor', $tool->ID) ?: ''); ?></p>
                        </div>
                    </div>
                </td>

                <!-- Type -->
                <td class="px-4 py-4 hidden sm:table-cell align-middle">
                    <?php $type = get_field('type_verktoey', $tool->ID); ?>
                    <?php if ($type): ?>
                        <span class="inline-block text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-2.5 py-1 rounded">
                            <?php echo esc_html($type); ?>
                        </span>
                    <?php endif; ?>
                </td>

                <!-- Plattform -->
                <td class="px-4 py-4 hidden md:table-cell align-middle text-sm text-[#57534E]">
                    <?php 
                    $plattformer = get_field('plattform', $tool->ID);
                    if ($plattformer && is_array($plattformer)) {
                        echo esc_html(implode(', ', array_slice($plattformer, 0, 3)));
                        if (count($plattformer) > 3) echo ' +' . (count($plattformer) - 3);
                    }
                    ?>
                </td>

                <!-- Status -->
                <td class="px-4 py-4 align-middle">
                    <span class="inline-block text-xs font-medium <?php echo $status_class; ?> px-2.5 py-1 rounded-full">
                        <?php echo $status_label; ?>
                    </span>
                </td>

                <!-- Sist oppdatert -->
                <td class="px-4 py-4 hidden lg:table-cell align-middle text-sm text-[#57534E]">
                    <?php echo $updated_date; ?>
                </td>

                <!-- Actions -->
                <td class="pl-4 py-4 text-right align-middle">
                    <div class="flex items-center justify-end gap-1">
                        <a href="<?php echo get_permalink($tool->ID); ?>" class="p-2 text-[#57534E] hover:text-[#111827] hover:bg-[#F5F5F4] rounded transition-colors" title="<?php esc_attr_e('Se', 'bimverdi'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        <a href="<?php echo home_url('/min-side/rediger-verktoy/?id=' . $tool->ID); ?>" class="p-2 text-[#57534E] hover:text-[#111827] hover:bg-[#F5F5F4] rounded transition-colors" title="<?php esc_attr_e('Rediger', 'bimverdi'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if (!empty($user_tools)): ?>
    <!-- Tool count -->
    <div class="flex justify-end py-3 text-xs text-[#57534E]">
        <?php printf(_n('Viser %d av %d verktøy', 'Viser %d av %d verktøy', count($user_tools), 'bimverdi'), count($user_tools), count($user_tools)); ?>
    </div>
    <?php endif; ?>
</div>

<?php if (empty($user_tools)): ?>
<!-- MAL / PLACEHOLDER Section (Per UI-CONTRACT) -->
<div class="border-t border-[#E7E5E4] mt-16 pt-10">
    <div class="text-center mb-8">
        <span class="inline-block text-xs font-bold uppercase tracking-wider text-[#A8A29E] bg-[#F5F5F4] px-3 py-1 rounded">
            <?php _e('MAL / PLACEHOLDER', 'bimverdi'); ?>
        </span>
    </div>
    
    <div class="max-w-lg mx-auto text-center py-12 px-6 bg-[#F5F5F4] rounded-lg border border-dashed border-[#E7E5E4]">
        <div class="w-16 h-16 bg-[#F5F5F4] rounded-lg flex items-center justify-center mx-auto mb-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-[#A8A29E]"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
        </div>
        <h3 class="text-lg font-semibold text-[#111827] mb-2"><?php _e('Ingen verktøy aktivert', 'bimverdi'); ?></h3>
        <p class="text-sm text-[#57534E] mb-6 leading-relaxed">
            <?php _e('Du har foreløpig ikke tilgang til noen verktøy. Kom i gang ved å opprette et nytt prosjekt eller søk i katalogen.', 'bimverdi'); ?>
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <?php bimverdi_button([
                'text'    => __('Utforsk verktøykatalogen', 'bimverdi'),
                'variant' => 'primary',
                'href'    => home_url('/verktoy/'),
                'icon'    => 'search',
            ]); ?>
            <?php bimverdi_button([
                'text'    => __('Les mer', 'bimverdi'),
                'variant' => 'secondary',
                'href'    => home_url('/min-side/registrer-verktoy/'),
                'icon'    => 'arrow-right',
                'icon_position' => 'right',
            ]); ?>
        </div>
    </div>
</div>
<?php endif; ?>
