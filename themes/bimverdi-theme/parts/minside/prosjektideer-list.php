<?php
/**
 * Min Side - Prosjektidéer List Part
 * 
 * Shows user's project ideas and options to submit new ones.
 * Used by template-minside-universal.php
 * 
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Query user's project ideas
$ideas = get_posts([
    'post_type'      => 'prosjektide',
    'posts_per_page' => -1,
    'author'         => $user_id,
    'post_status'    => ['publish', 'pending', 'draft'],
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
?>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => __('Mine prosjektidéer', 'bimverdi'),
    'description' => __('Forslag til pilotprosjekter og samarbeidsinitiativ', 'bimverdi'),
    'actions' => [
        ['text' => __('Send inn ny idé', 'bimverdi'), 'url' => '/min-side/ny-prosjektide/', 'variant' => 'primary'],
    ],
]); ?>

<!-- Info Banner -->
<div class="bg-[#F2F0EB] rounded-lg p-4 mb-6">
    <div class="flex items-start gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A] flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        <div>
            <p class="text-sm text-[#5A5A5A]">
                <?php _e('Prosjektidéer er kun synlige for deg og BIM Verdi-administrasjonen. Godkjente idéer kan bli til pilotprosjekter.', 'bimverdi'); ?>
            </p>
        </div>
    </div>
</div>

<?php if (empty($ideas)): ?>
    <!-- Empty State -->
    <?php get_template_part('parts/components/empty-state', null, [
        'icon' => 'lightbulb',
        'title' => __('Ingen prosjektidéer ennå', 'bimverdi'),
        'description' => __('Har du en idé til et pilotprosjekt eller samarbeidsinitiativ? Send den inn, så vurderer vi den for videre utvikling.', 'bimverdi'),
        'cta_text' => __('Send inn din første idé', 'bimverdi'),
        'cta_url' => '/min-side/ny-prosjektide/',
    ]); ?>

<?php else: ?>
    <!-- Ideas List -->
    <div class="space-y-4">
        <?php foreach ($ideas as $idea): ?>
            <?php
            $status_info = [
                'publish' => ['label' => __('Godkjent', 'bimverdi'), 'bg' => 'bg-green-100', 'text' => 'text-green-800'],
                'pending' => ['label' => __('Til vurdering', 'bimverdi'), 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'],
                'draft'   => ['label' => __('Kladd', 'bimverdi'), 'bg' => 'bg-gray-100', 'text' => 'text-gray-800'],
            ];
            $status = $idea->post_status;
            $info = $status_info[$status] ?? ['label' => ucfirst($status), 'bg' => 'bg-gray-100', 'text' => 'text-gray-800'];
            ?>
            <div class="bg-white rounded-lg border border-[#E5E0D8] p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-base font-semibold text-[#1A1A1A]"><?php echo esc_html($idea->post_title); ?></h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo esc_attr($info['bg'] . ' ' . $info['text']); ?>">
                                <?php echo esc_html($info['label']); ?>
                            </span>
                        </div>
                        
                        <?php 
                        $excerpt = wp_trim_words($idea->post_content, 30, '...');
                        if ($excerpt): 
                        ?>
                            <p class="text-sm text-[#5A5A5A] mb-3"><?php echo esc_html($excerpt); ?></p>
                        <?php endif; ?>
                        
                        <div class="flex items-center gap-4 text-xs text-[#5A5A5A]">
                            <span class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                <?php _e('Sendt:', 'bimverdi'); ?> <?php echo get_the_date('j. M Y', $idea->ID); ?>
                            </span>
                            
                            <?php 
                            $temagruppe = get_field('temagruppe', $idea->ID);
                            if ($temagruppe): 
                            ?>
                                <span class="flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                                    <?php echo esc_html($temagruppe); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <a href="<?php echo home_url('/min-side/rediger-prosjektide/?id=' . $idea->ID); ?>" class="p-2 rounded hover:bg-[#F2F0EB] transition-colors" title="<?php esc_attr_e('Rediger', 'bimverdi'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
