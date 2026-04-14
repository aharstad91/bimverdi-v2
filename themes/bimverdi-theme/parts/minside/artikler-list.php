<?php
/**
 * Min Side - Artikler List Part
 *
 * Shows user's own articles with status badges and actions.
 * Used by template-minside-universal.php
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user's own articles
$user_articles = get_posts([
    'post_type'      => 'artikkel',
    'post_status'    => ['publish', 'pending'],
    'author'         => $user_id,
    'posts_per_page' => -1,
    'orderby'        => 'modified',
    'order'          => 'DESC',
]);
?>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => __('Mine artikler', 'bimverdi'),
    'description' => __('Oversikt over artikler du har sendt inn.', 'bimverdi'),
    'actions' => bimverdi_can_access('write_article') ? [
        ['text' => __('Skriv ny artikkel', 'bimverdi'), 'url' => bimverdi_minside_url('artikler/skriv'), 'variant' => 'primary', 'icon' => 'plus'],
    ] : [],
]); ?>

<!-- Success: submitted -->
<?php if (isset($_GET['submitted']) && $_GET['submitted'] === '1'): ?>
<div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </svg>
    <p class="text-green-800 text-sm"><?php _e('Artikkelen er sendt inn! Den vil bli gjennomgått før publisering.', 'bimverdi'); ?></p>
</div>
<?php endif; ?>

<!-- Success: updated -->
<?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
<div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </svg>
    <p class="text-green-800 text-sm"><?php _e('Artikkelen er oppdatert!', 'bimverdi'); ?></p>
</div>
<?php endif; ?>

<!-- Success: deleted -->
<?php if (isset($_GET['deleted']) && $_GET['deleted'] === '1'): ?>
<div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </svg>
    <p class="text-green-800 text-sm"><?php _e('Artikkelen er slettet.', 'bimverdi'); ?></p>
</div>
<?php endif; ?>

<!-- Error messages -->
<?php if (!empty($_GET['bv_error'])):
    $error_code = sanitize_text_field($_GET['bv_error']);
    $error_messages = [
        'nonce'             => __('Lenken har utløpt. Prøv igjen.', 'bimverdi'),
        'not_owner'         => __('Du har ikke tilgang til denne artikkelen.', 'bimverdi'),
        'not_found'         => __('Artikkelen ble ikke funnet.', 'bimverdi'),
        'already_published' => __('Artikkelen er allerede publisert og kan ikke endres herfra.', 'bimverdi'),
        'system'            => __('En teknisk feil oppstod. Prøv igjen.', 'bimverdi'),
    ];
    $error_text = $error_messages[$error_code] ?? '';
    if ($error_text):
?>
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    <p class="text-red-800 text-sm"><?php echo esc_html($error_text); ?></p>
</div>
<?php endif; endif; ?>

<?php if (!empty($user_articles)): ?>
<!-- Articles Table -->
<div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead>
            <tr class="border-b border-[#E7E5E4]">
                <th class="py-3 pr-4 text-xs font-medium text-[#57534E]"><?php _e('Artikkel', 'bimverdi'); ?></th>
                <th class="px-4 py-3 text-xs font-medium text-[#57534E]"><?php _e('Status', 'bimverdi'); ?></th>
                <th class="px-4 py-3 text-xs font-medium text-[#57534E] hidden sm:table-cell"><?php _e('Dato', 'bimverdi'); ?></th>
                <th class="pl-4 py-3 text-xs font-medium text-[#57534E] text-right"><?php _e('Handlinger', 'bimverdi'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($user_articles as $article):
                $status = get_post_status($article->ID);
                $status_class = $status === 'publish'
                    ? 'bg-[#DCFCE7] text-[#166534]'
                    : 'bg-[#FEF9C3] text-[#854D0E]';
                $status_label = $status === 'publish'
                    ? __('Publisert', 'bimverdi')
                    : __('Venter på godkjenning', 'bimverdi');
                $date = get_the_date('d.m.Y', $article->ID);
            ?>
            <tr class="border-b border-[#E7E5E4] hover:bg-[#F5F5F4] transition-colors">
                <!-- Tittel -->
                <td class="py-4 pr-4">
                    <div class="min-w-0">
                        <?php if ($status === 'publish'): ?>
                            <a href="<?php echo get_permalink($article->ID); ?>" class="font-medium text-[#111827] hover:text-[#FF8B5E] transition-colors block leading-tight">
                                <?php echo esc_html($article->post_title); ?>
                            </a>
                        <?php else: ?>
                            <span class="font-medium text-[#111827] block leading-tight">
                                <?php echo esc_html($article->post_title); ?>
                            </span>
                        <?php endif; ?>
                        <?php
                        $temagrupper = get_the_terms($article->ID, 'temagruppe');
                        if ($temagrupper && !is_wp_error($temagrupper)):
                            $names = wp_list_pluck($temagrupper, 'name');
                        ?>
                            <p class="text-xs text-[#57534E] mt-0.5"><?php echo esc_html(implode(', ', $names)); ?></p>
                        <?php endif; ?>
                    </div>
                </td>

                <!-- Status -->
                <td class="px-4 py-4 align-middle">
                    <span class="inline-block text-xs font-medium <?php echo $status_class; ?> px-2.5 py-1 rounded-full whitespace-nowrap">
                        <?php echo $status_label; ?>
                    </span>
                </td>

                <!-- Dato -->
                <td class="px-4 py-4 hidden sm:table-cell align-middle text-sm text-[#57534E]">
                    <?php echo $date; ?>
                </td>

                <!-- Handlinger -->
                <td class="pl-4 py-4 text-right align-middle">
                    <div class="flex items-center justify-end gap-1">
                        <?php if ($status === 'publish'): ?>
                            <!-- Vis artikkel -->
                            <a href="<?php echo get_permalink($article->ID); ?>" class="p-2 text-[#57534E] hover:text-[#111827] hover:bg-[#F5F5F4] rounded transition-colors" title="<?php esc_attr_e('Vis artikkel', 'bimverdi'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                        <?php else: ?>
                            <!-- Rediger -->
                            <a href="<?php echo esc_url(bimverdi_minside_url('artikler/rediger') . '?id=' . $article->ID); ?>" class="p-2 text-[#57534E] hover:text-[#111827] hover:bg-[#F5F5F4] rounded transition-colors" title="<?php esc_attr_e('Rediger', 'bimverdi'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                            </a>
                            <!-- Slett -->
                            <a href="<?php echo esc_url(wp_nonce_url(
                                add_query_arg([
                                    'action' => 'delete_artikkel',
                                    'artikkel_id' => $article->ID,
                                ], home_url('/min-side/artikler/')),
                                'delete_artikkel_' . $article->ID
                            )); ?>"
                               class="p-2 text-[#57534E] hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                               title="<?php esc_attr_e('Slett', 'bimverdi'); ?>"
                               onclick="return confirm('<?php esc_attr_e('Er du sikker på at du vil slette denne artikkelen?', 'bimverdi'); ?>');">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Article count -->
    <div class="flex justify-end py-3 text-xs text-[#57534E]">
        <?php printf(__('Viser %d artikler', 'bimverdi'), count($user_articles)); ?>
    </div>
</div>
<?php endif; ?>

<?php if (empty($user_articles)): ?>
<!-- Empty state -->
<div class="mt-8">
    <div class="max-w-lg mx-auto text-center py-12 px-6">
        <div class="w-16 h-16 bg-[#F5F5F4] rounded-lg flex items-center justify-center mx-auto mb-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-[#A8A29E]"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-[#111827] mb-2"><?php _e('Du har ikke skrevet noen artikler ennå', 'bimverdi'); ?></h3>
        <p class="text-sm text-[#57534E] mb-6 leading-relaxed">
            <?php _e('Del erfaringer, prosjektrapporter eller verktøy-tips med andre BIM Verdi-medlemmer.', 'bimverdi'); ?>
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <?php if (bimverdi_can_access('write_article')): ?>
            <?php bimverdi_button([
                'text'    => __('Skriv din første artikkel', 'bimverdi'),
                'variant' => 'primary',
                'href'    => bimverdi_minside_url('artikler/skriv'),
                'icon'    => 'pencil',
            ]); ?>
            <?php endif; ?>
            <?php bimverdi_button([
                'text'    => __('Utforsk artikler', 'bimverdi'),
                'variant' => 'secondary',
                'href'    => home_url('/artikler/'),
                'icon'    => 'search',
            ]); ?>
        </div>
    </div>
</div>
<?php endif; ?>
