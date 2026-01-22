<?php
/**
 * Min Side - Artikler List Part
 * 
 * Shows user's articles and options to create new ones.
 * Used by template-minside-universal.php
 * 
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Query user's articles
$articles = get_posts([
    'post_type'      => 'artikkel',
    'posts_per_page' => -1,
    'author'         => $user_id,
    'post_status'    => ['publish', 'pending', 'draft'],
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
?>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => __('Mine artikler', 'bimverdi'),
    'description' => __('Skriv og del fagartikler med nettverket', 'bimverdi'),
    'actions' => [
        ['text' => __('Skriv ny artikkel', 'bimverdi'), 'url' => bimverdi_minside_url('artikler/skriv'), 'variant' => 'primary', 'icon' => 'plus'],
    ],
]); ?>

<?php if (empty($articles)): ?>
    <!-- Empty State -->
    <?php get_template_part('parts/components/empty-state', null, [
        'icon' => 'file-text',
        'title' => __('Du har ingen artikler ennå', 'bimverdi'),
        'description' => __('Del din kunnskap og erfaring med BIM Verdi-nettverket. Skriv om prosjekter, tips, eller faglige innsikter.', 'bimverdi'),
        'cta_text' => __('Skriv din første artikkel', 'bimverdi'),
        'cta_url' => bimverdi_minside_url('artikler/skriv'),
        'cta_icon' => 'pencil',
    ]); ?>

<?php else: ?>
    <!-- Articles Table -->
    <div class="bg-white rounded-lg border border-[#E5E0D8] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-[#F2F0EB]">
                    <tr>
                        <th class="text-left text-xs font-semibold text-[#5A5A5A] uppercase tracking-wide px-4 py-3">
                            <?php _e('Tittel', 'bimverdi'); ?>
                        </th>
                        <th class="text-left text-xs font-semibold text-[#5A5A5A] uppercase tracking-wide px-4 py-3 hidden md:table-cell">
                            <?php _e('Publisert', 'bimverdi'); ?>
                        </th>
                        <th class="text-left text-xs font-semibold text-[#5A5A5A] uppercase tracking-wide px-4 py-3">
                            <?php _e('Status', 'bimverdi'); ?>
                        </th>
                        <th class="text-right text-xs font-semibold text-[#5A5A5A] uppercase tracking-wide px-4 py-3">
                            <?php _e('Handlinger', 'bimverdi'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5E0D8]">
                    <?php foreach ($articles as $article): ?>
                        <tr class="hover:bg-[#FDFBF7]">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <?php 
                                    $thumbnail = get_the_post_thumbnail_url($article->ID, 'thumbnail');
                                    if ($thumbnail): 
                                    ?>
                                        <img src="<?php echo esc_url($thumbnail); ?>" alt="" class="w-10 h-10 rounded object-cover hidden sm:block">
                                    <?php else: ?>
                                        <div class="w-10 h-10 rounded bg-[#F2F0EB] flex items-center justify-center hidden sm:flex">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="text-sm font-medium text-[#1A1A1A]"><?php echo esc_html($article->post_title); ?></p>
                                        <?php 
                                        $excerpt = wp_trim_words($article->post_excerpt ?: $article->post_content, 10, '...');
                                        if ($excerpt): 
                                        ?>
                                            <p class="text-xs text-[#5A5A5A] hidden sm:block"><?php echo esc_html($excerpt); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 hidden md:table-cell">
                                <span class="text-sm text-[#5A5A5A]">
                                    <?php echo $article->post_status === 'publish' 
                                        ? get_the_date('j. M Y', $article->ID) 
                                        : '—'; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <?php
                                $status_classes = [
                                    'publish' => 'bg-green-100 text-green-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'draft'   => 'bg-gray-100 text-gray-800',
                                ];
                                $status_labels = [
                                    'publish' => __('Publisert', 'bimverdi'),
                                    'pending' => __('Venter', 'bimverdi'),
                                    'draft'   => __('Kladd', 'bimverdi'),
                                ];
                                $status = $article->post_status;
                                ?>
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium <?php echo esc_attr($status_classes[$status] ?? 'bg-gray-100 text-gray-800'); ?>">
                                    <?php echo esc_html($status_labels[$status] ?? ucfirst($status)); ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <?php if ($article->post_status === 'publish'): ?>
                                        <a href="<?php echo get_permalink($article->ID); ?>" class="p-2 rounded hover:bg-[#F2F0EB] transition-colors" title="<?php esc_attr_e('Se artikkel', 'bimverdi'); ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?php echo esc_url(bimverdi_minside_url('artikler/rediger') . '?id=' . $article->ID); ?>" class="p-2 rounded hover:bg-[#F2F0EB] transition-colors" title="<?php esc_attr_e('Rediger', 'bimverdi'); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
