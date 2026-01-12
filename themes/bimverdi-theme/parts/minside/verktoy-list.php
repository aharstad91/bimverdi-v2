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
        ['text' => __('Nytt verktøy', 'bimverdi'), 'url' => '/min-side/registrer-verktoy/', 'variant' => 'primary'],
    ],
]); ?>

<?php if (empty($user_tools)): ?>
    <!-- Empty State -->
    <?php get_template_part('parts/components/empty-state', null, [
        'icon' => 'wrench',
        'title' => __('Ingen verktøy registrert ennå', 'bimverdi'),
        'description' => __('Del dine favorittverktøy med BIM Verdi-medlemmer! Registrer programvare, plugins, eller andre nyttige verktøy du bruker i ditt daglige arbeid.', 'bimverdi'),
        'cta_text' => __('Registrer ditt første verktøy', 'bimverdi'),
        'cta_url' => '/min-side/registrer-verktoy/',
    ]); ?>

<?php else: ?>
    <!-- Tools Table -->
    <div class="bg-white rounded-lg border border-[#E5E0D8] overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#F7F5EF]">
                <tr>
                    <th class="px-6 py-4 font-medium text-[#5A5A5A] text-sm"><?php _e('Verktøy', 'bimverdi'); ?></th>
                    <th class="px-4 py-4 font-medium text-[#5A5A5A] text-sm hidden sm:table-cell"><?php _e('Type', 'bimverdi'); ?></th>
                    <th class="px-4 py-4 font-medium text-[#5A5A5A] text-sm hidden md:table-cell"><?php _e('Plattform', 'bimverdi'); ?></th>
                    <th class="px-4 py-4 font-medium text-[#5A5A5A] text-sm"><?php _e('Status', 'bimverdi'); ?></th>
                    <th class="px-6 py-4 font-medium text-[#5A5A5A] text-sm text-right"><?php _e('Handlinger', 'bimverdi'); ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#E5E0D8]">
                <?php foreach ($user_tools as $tool): 
                    $logo = get_field('logo', $tool->ID);
                    $tool_status = get_post_status($tool->ID);
                    $status_class = $tool_status === 'publish' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                    $status_label = $tool_status === 'publish' ? __('Publisert', 'bimverdi') : ($tool_status === 'pending' ? __('Venter', 'bimverdi') : __('Kladd', 'bimverdi'));
                ?>
                <tr class="hover:bg-[#F7F5EF] transition-colors">
                    <!-- Verktøy Name -->
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#F2F0EB] rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden">
                                <?php if ($logo): ?>
                                    <img src="<?php echo esc_url($logo['sizes']['thumbnail']); ?>" alt="" class="w-full h-full object-cover" />
                                <?php else: ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a href="<?php echo get_permalink($tool->ID); ?>" class="font-medium text-[#1A1A1A] hover:text-[#5A5A5A]">
                                    <?php echo esc_html($tool->post_title); ?>
                                </a>
                                <p class="text-xs text-[#5A5A5A]"><?php echo esc_html(get_field('vendor', $tool->ID) ?: __('Leverandør ikke angitt', 'bimverdi')); ?></p>
                            </div>
                        </div>
                    </td>

                    <!-- Type -->
                    <td class="px-4 py-4 hidden sm:table-cell">
                        <?php $type = get_field('type_verktoey', $tool->ID); ?>
                        <?php if ($type): ?>
                            <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-2 py-1 rounded">
                                <?php echo esc_html($type); ?>
                            </span>
                        <?php endif; ?>
                    </td>

                    <!-- Plattform -->
                    <td class="px-4 py-4 hidden md:table-cell text-sm text-[#5A5A5A]">
                        <?php 
                        $plattformer = get_field('plattform', $tool->ID);
                        if ($plattformer && is_array($plattformer)) {
                            echo esc_html(implode(', ', array_slice($plattformer, 0, 2)));
                            if (count($plattformer) > 2) echo ' +' . (count($plattformer) - 2);
                        }
                        ?>
                    </td>

                    <!-- Status -->
                    <td class="px-4 py-4">
                        <span class="inline-block text-xs font-medium <?php echo $status_class; ?> px-2 py-1 rounded">
                            <?php echo $status_label; ?>
                        </span>
                    </td>

                    <!-- Actions -->
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="<?php echo get_permalink($tool->ID); ?>" class="p-2 text-[#5A5A5A] hover:text-[#1A1A1A] hover:bg-[#F2F0EB] rounded transition-colors" title="<?php _e('Se', 'bimverdi'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </a>
                            <a href="<?php echo home_url('/min-side/rediger-verktoy/?id=' . $tool->ID); ?>" class="p-2 text-[#5A5A5A] hover:text-[#1A1A1A] hover:bg-[#F2F0EB] rounded transition-colors" title="<?php _e('Rediger', 'bimverdi'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
