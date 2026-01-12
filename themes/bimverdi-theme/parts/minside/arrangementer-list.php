<?php
/**
 * Min Side - Arrangementer List Part
 * 
 * Shows upcoming events and user's registrations.
 * Used by template-minside-universal.php
 * 
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user's event registrations
$user_registrations = get_posts([
    'post_type'      => 'pamelding',
    'posts_per_page' => -1,
    'meta_query'     => [
        [
            'key'   => 'bruker',
            'value' => $user_id,
        ],
    ],
    'fields' => 'ids',
]);

// Get registered event IDs
$registered_event_ids = [];
foreach ($user_registrations as $reg_id) {
    $event_id = get_field('arrangement', $reg_id);
    if ($event_id) {
        $registered_event_ids[] = is_array($event_id) ? $event_id[0] : $event_id;
    }
}

// Query upcoming events
$upcoming_events = get_posts([
    'post_type'      => 'arrangement',
    'posts_per_page' => 10,
    'post_status'    => 'publish',
    'meta_key'       => 'dato',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
    'meta_query'     => [
        [
            'key'     => 'dato',
            'value'   => date('Y-m-d'),
            'compare' => '>=',
            'type'    => 'DATE',
        ],
    ],
]);
?>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => __('Arrangementer', 'bimverdi'),
    'description' => __('Kommende møter, workshops og nettverkssamlinger', 'bimverdi'),
]); ?>

<?php if (empty($upcoming_events)): ?>
    <!-- Empty State -->
    <?php get_template_part('parts/components/empty-state', null, [
        'icon' => 'calendar',
        'title' => __('Ingen kommende arrangementer', 'bimverdi'),
        'description' => __('Det er ingen planlagte arrangementer akkurat nå. Sjekk tilbake senere for oppdateringer.', 'bimverdi'),
    ]); ?>

<?php else: ?>
    <!-- Events Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($upcoming_events as $event): 
            $event_id = $event->ID;
            $dato = get_field('dato', $event_id);
            $tidspunkt = get_field('tidspunkt', $event_id);
            $sted = get_field('sted', $event_id);
            $temagruppe = get_field('temagruppe', $event_id);
            $is_registered = in_array($event_id, $registered_event_ids);
            $max_deltakere = get_field('maks_deltakere', $event_id);
            $antall_pameldt = get_field('antall_pameldt', $event_id) ?: 0;
            $is_full = $max_deltakere && $antall_pameldt >= $max_deltakere;
        ?>
            <div class="bg-white rounded-lg border border-[#E5E0D8] overflow-hidden">
                <!-- Event Image -->
                <?php 
                $image = get_the_post_thumbnail_url($event_id, 'medium_large');
                if ($image): 
                ?>
                    <div class="aspect-video bg-[#F2F0EB]">
                        <img src="<?php echo esc_url($image); ?>" alt="" class="w-full h-full object-cover">
                    </div>
                <?php else: ?>
                    <div class="aspect-video bg-[#F2F0EB] flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-[#B8B0A0]"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                <?php endif; ?>
                
                <!-- Event Content -->
                <div class="p-5">
                    <!-- Date Badge & Status -->
                    <div class="flex items-center justify-between mb-3">
                        <?php if ($dato): 
                            $date_obj = DateTime::createFromFormat('Y-m-d', $dato);
                            if ($date_obj):
                        ?>
                            <div class="flex items-center gap-2 text-sm text-[#5A5A5A]">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                <?php echo $date_obj->format('j. M Y'); ?>
                                <?php if ($tidspunkt): ?>
                                    <span class="text-[#B8B0A0]">•</span>
                                    <?php echo esc_html($tidspunkt); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; endif; ?>
                        
                        <?php if ($is_registered): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                <?php _e('Påmeldt', 'bimverdi'); ?>
                            </span>
                        <?php elseif ($is_full): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                <?php _e('Fullt', 'bimverdi'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Title -->
                    <h3 class="text-lg font-semibold text-[#1A1A1A] mb-2"><?php echo esc_html($event->post_title); ?></h3>
                    
                    <!-- Excerpt -->
                    <?php 
                    $excerpt = wp_trim_words($event->post_excerpt ?: $event->post_content, 20, '...');
                    if ($excerpt): 
                    ?>
                        <p class="text-sm text-[#5A5A5A] mb-4"><?php echo esc_html($excerpt); ?></p>
                    <?php endif; ?>
                    
                    <!-- Meta -->
                    <div class="flex flex-wrap items-center gap-3 mb-4 text-xs text-[#5A5A5A]">
                        <?php if ($sted): ?>
                            <span class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <?php echo esc_html($sted); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($temagruppe): ?>
                            <span class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                <?php echo esc_html($temagruppe); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex items-center gap-3">
                        <?php bimverdi_button([
                            'text'    => __('Les mer', 'bimverdi'),
                            'variant' => 'secondary',
                            'href'    => get_permalink($event_id),
                            'class'   => 'flex-1',
                        ]); ?>
                        <?php if (!$is_registered && !$is_full): ?>
                            <?php bimverdi_button([
                                'text'    => __('Meld på', 'bimverdi'),
                                'variant' => 'primary',
                                'href'    => home_url('/min-side/meld-pa/?arrangement=' . $event_id),
                                'class'   => 'flex-1',
                            ]); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
