<?php
/**
 * Min Side - Kunnskapskilder List Part
 *
 * Shows user's registered knowledge sources in a table layout.
 * Used by template-minside-universal.php
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user's company
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

// Get kunnskapskilder registered by user or user's company
$user_kunnskapskilder = get_posts([
    'post_type' => 'kunnskapskilde',
    'posts_per_page' => -1,
    'post_status' => ['publish', 'draft', 'pending'],
    'meta_query' => [
        'relation' => 'OR',
        [
            'key' => 'registrert_av',
            'value' => $user_id,
            'compare' => '='
        ],
        [
            'key' => 'tilknyttet_bedrift',
            'value' => $company_id,
            'compare' => '='
        ]
    ],
    'orderby' => 'date',
    'order' => 'DESC',
]);

// If no results from meta query, also check post_author
if (empty($user_kunnskapskilder)) {
    $user_kunnskapskilder = get_posts([
        'post_type' => 'kunnskapskilde',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'draft', 'pending'],
        'author' => $user_id,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
}
?>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => __('Mine kunnskapskilder', 'bimverdi'),
    'description' => __('Oversikt over kunnskapskilder du har registrert.', 'bimverdi'),
    'actions' => [
        ['text' => __('Ny kunnskapskilde', 'bimverdi'), 'url' => bimverdi_minside_url('kunnskapskilder/registrer'), 'variant' => 'primary', 'icon' => 'plus'],
    ],
]); ?>

<!-- Kunnskapskilder Table -->
<div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead>
            <tr class="border-b border-[#E7E5E4]">
                <th class="py-3 pr-4 text-xs font-medium text-[#57534E]"><?php _e('Navn', 'bimverdi'); ?></th>
                <th class="px-4 py-3 text-xs font-medium text-[#57534E] hidden sm:table-cell"><?php _e('Kildetype', 'bimverdi'); ?></th>
                <th class="px-4 py-3 text-xs font-medium text-[#57534E] hidden md:table-cell"><?php _e('Utgiver', 'bimverdi'); ?></th>
                <th class="px-4 py-3 text-xs font-medium text-[#57534E]"><?php _e('Status', 'bimverdi'); ?></th>
                <th class="px-4 py-3 text-xs font-medium text-[#57534E] hidden lg:table-cell"><?php _e('Registrert', 'bimverdi'); ?></th>
                <th class="pl-4 py-3 text-xs font-medium text-[#57534E] text-right"><?php _e('Handlinger', 'bimverdi'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($user_kunnskapskilder)): ?>
            <!-- Empty row with message -->
            <tr>
                <td colspan="6" class="py-8 text-center text-[#57534E]">
                    <?php _e('Ingen kunnskapskilder registrert ennå.', 'bimverdi'); ?>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($user_kunnskapskilder as $kilde):
                $kilde_status = get_post_status($kilde->ID);
                $status_class = $kilde_status === 'publish' ? 'bg-[#DCFCE7] text-[#166534]' : ($kilde_status === 'pending' ? 'bg-[#FEF9C3] text-[#854D0E]' : 'bg-[#FEE2E2] text-[#991B1B]');
                $status_label = $kilde_status === 'publish' ? __('Publisert', 'bimverdi') : ($kilde_status === 'pending' ? __('Venter', 'bimverdi') : __('Kladd', 'bimverdi'));
                $created_date = get_the_date('d.m.Y', $kilde->ID);

                // Get ACF fields
                $kildetype = get_field('kildetype', $kilde->ID);
                $utgiver = get_field('utgiver', $kilde->ID);
                $ekstern_lenke = get_field('ekstern_lenke', $kilde->ID);

                // Kildetype labels
                $kildetype_labels = [
                    'standard' => 'Standard',
                    'veileder' => 'Veileder',
                    'mal' => 'Mal/Template',
                    'forskningsrapport' => 'Forskningsrapport',
                    'casestudie' => 'Casestudie',
                    'opplaering' => 'Opplæring',
                    'dokumentasjon' => 'Dokumentasjon',
                    'nettressurs' => 'Nettressurs',
                    'annet' => 'Annet'
                ];
                $kildetype_label = isset($kildetype_labels[$kildetype]) ? $kildetype_labels[$kildetype] : $kildetype;
            ?>
            <tr class="border-b border-[#E7E5E4] hover:bg-[#F5F5F4] transition-colors group">
                <!-- Navn & Beskrivelse -->
                <td class="py-4 pr-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-[#F5F5F4] rounded flex items-center justify-center flex-shrink-0 self-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#A8A29E]"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="<?php echo get_permalink($kilde->ID); ?>" class="font-medium text-[#111827] hover:text-[#F97316] transition-colors block leading-tight">
                                <?php echo esc_html($kilde->post_title); ?>
                            </a>
                            <?php
                            $kort_beskrivelse = get_field('kort_beskrivelse', $kilde->ID);
                            if ($kort_beskrivelse): ?>
                            <p class="text-xs text-[#57534E] mt-0.5 line-clamp-1"><?php echo esc_html(wp_trim_words($kort_beskrivelse, 10)); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>

                <!-- Kildetype -->
                <td class="px-4 py-4 hidden sm:table-cell align-middle">
                    <?php if ($kildetype_label): ?>
                        <span class="inline-block text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-2.5 py-1 rounded">
                            <?php echo esc_html($kildetype_label); ?>
                        </span>
                    <?php endif; ?>
                </td>

                <!-- Utgiver -->
                <td class="px-4 py-4 hidden md:table-cell align-middle text-sm text-[#57534E]">
                    <?php echo esc_html($utgiver ?: '-'); ?>
                </td>

                <!-- Status -->
                <td class="px-4 py-4 align-middle">
                    <span class="inline-block text-xs font-medium <?php echo $status_class; ?> px-2.5 py-1 rounded-full">
                        <?php echo $status_label; ?>
                    </span>
                </td>

                <!-- Registrert -->
                <td class="px-4 py-4 hidden lg:table-cell align-middle text-sm text-[#57534E]">
                    <?php echo $created_date; ?>
                </td>

                <!-- Actions -->
                <td class="pl-4 py-4 text-right align-middle">
                    <div class="flex items-center justify-end gap-1">
                        <?php if ($ekstern_lenke): ?>
                        <a href="<?php echo esc_url($ekstern_lenke); ?>" target="_blank" rel="noopener" class="p-2 text-[#57534E] hover:text-[#111827] hover:bg-[#F5F5F4] rounded transition-colors" title="<?php esc_attr_e('Åpne lenke', 'bimverdi'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        </a>
                        <?php endif; ?>
                        <a href="<?php echo get_permalink($kilde->ID); ?>" class="p-2 text-[#57534E] hover:text-[#111827] hover:bg-[#F5F5F4] rounded transition-colors" title="<?php esc_attr_e('Se', 'bimverdi'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        <a href="<?php echo bimverdi_minside_url('kunnskapskilder/rediger', ['kunnskapskilde_id' => $kilde->ID]); ?>" class="p-2 text-[#57534E] hover:text-[#111827] hover:bg-[#F5F5F4] rounded transition-colors" title="<?php esc_attr_e('Rediger', 'bimverdi'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($user_kunnskapskilder)): ?>
    <!-- Count -->
    <div class="flex justify-end py-3 text-xs text-[#57534E]">
        <?php printf(_n('Viser %d av %d kunnskapskilde', 'Viser %d av %d kunnskapskilder', count($user_kunnskapskilder), 'bimverdi'), count($user_kunnskapskilder), count($user_kunnskapskilder)); ?>
    </div>
    <?php endif; ?>
</div>

<?php if (empty($user_kunnskapskilder)): ?>
<!-- Empty State -->
<div class="border-t border-[#E7E5E4] mt-16 pt-10">
    <div class="max-w-lg mx-auto text-center py-12 px-6 bg-[#F5F5F4] rounded-lg border border-dashed border-[#E7E5E4]">
        <div class="w-16 h-16 bg-[#F5F5F4] rounded-lg flex items-center justify-center mx-auto mb-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-[#A8A29E]"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
        </div>
        <h3 class="text-lg font-semibold text-[#111827] mb-2"><?php _e('Ingen kunnskapskilder registrert', 'bimverdi'); ?></h3>
        <p class="text-sm text-[#57534E] mb-6 leading-relaxed">
            <?php _e('Del kunnskapskilder som standarder, veiledere og rapporter med andre deltakere. Registrer din første kunnskapskilde nå.', 'bimverdi'); ?>
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <?php bimverdi_button([
                'text'    => __('Registrer kunnskapskilde', 'bimverdi'),
                'variant' => 'primary',
                'href'    => bimverdi_minside_url('kunnskapskilder/registrer'),
                'icon'    => 'plus',
            ]); ?>
            <?php bimverdi_button([
                'text'    => __('Utforsk katalogen', 'bimverdi'),
                'variant' => 'secondary',
                'href'    => home_url('/kunnskapskilder/'),
                'icon'    => 'search',
            ]); ?>
        </div>
    </div>
</div>
<?php endif; ?>
