<?php
/**
 * Card Components Template Part
 * 
 * Reusable card component functions
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display a member card
 */
function bimverdi_display_member_card($post_id) {
    $post = get_post($post_id);
    $logo_id = get_field('logo', $post_id);
    $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
    $description = get_field('beskrivelse', $post_id);
    $member_status = get_field('medlemsstatus', $post_id);
    $contact_email = get_field('kontakt_epost', $post_id);
    $categories = wp_get_post_terms($post_id, 'bransjekategori');
    
    ?>
    <div class="card bg-white shadow-md hover:shadow-lg transition-all hover:scale-105 h-full">
        <!-- Logo -->
        <figure class="relative h-40 bg-gradient-to-br from-bim-orange to-bim-purple overflow-hidden">
            <?php if ($logo_url): ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($post->post_title); ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-white text-2xl font-bold">
                    <?php echo substr($post->post_title, 0, 1); ?>
                </div>
            <?php endif; ?>
        </figure>
        
        <!-- Content -->
        <div class="card-body p-4">
            
            <!-- Status Badge -->
            <?php if ($member_status): ?>
                <div class="mb-2">
                    <span class="badge badge-primary">
                        <?php echo esc_html($member_status); ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <!-- Title -->
            <h3 class="card-title text-lg font-bold line-clamp-2">
                <?php echo esc_html($post->post_title); ?>
            </h3>
            
            <!-- Description -->
            <?php if ($description): ?>
                <p class="text-sm text-gray-600 line-clamp-2">
                    <?php echo wp_kses_post($description); ?>
                </p>
            <?php endif; ?>
            
            <!-- Categories -->
            <?php if (!empty($categories)): ?>
                <div class="flex gap-1 flex-wrap mt-2">
                    <?php foreach (array_slice($categories, 0, 2) as $cat): ?>
                        <span class="badge badge-outline badge-sm">
                            <?php echo esc_html($cat->name); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- View Profile Button -->
            <div class="card-actions justify-end mt-4">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="btn btn-sm btn-primary">
                    Se profil
                </a>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Display a tool card
 */
function bimverdi_display_tool_card($post_id) {
    $post = get_post($post_id);
    $tool_logo_id = get_field('logo', $post_id);
    $tool_logo_url = $tool_logo_id ? wp_get_attachment_image_url($tool_logo_id, 'medium') : '';
    $tool_url = get_field('link', $post_id);
    $description = wp_trim_words($post->post_content, 15, '...');
    $categories = wp_get_post_terms($post_id, 'verktoykategori', array('limit' => 2));
    
    // Get tool owner
    $owner_id = get_field('eier_leverandr', $post_id);
    $owner = $owner_id ? get_post($owner_id) : null;
    
    ?>
    <div class="card bg-white shadow-md hover:shadow-lg transition-all">
        <!-- Header Image -->
        <figure class="relative h-32 bg-gradient-to-br from-purple-400 to-purple-600 overflow-hidden">
            <?php if ($tool_logo_url): ?>
                <img src="<?php echo esc_url($tool_logo_url); ?>" alt="<?php echo esc_attr($post->post_title); ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-white text-lg font-bold">
                    <?php echo substr($post->post_title, 0, 2); ?>
                </div>
            <?php endif; ?>
        </figure>
        
        <!-- Content -->
        <div class="card-body p-4">
            
            <!-- Title -->
            <h3 class="card-title text-lg font-bold line-clamp-2">
                <?php echo esc_html($post->post_title); ?>
            </h3>
            
            <!-- Description -->
            <p class="text-sm text-gray-600 line-clamp-2">
                <?php echo esc_html($description); ?>
            </p>
            
            <!-- Owner -->
            <?php if ($owner): ?>
                <div class="text-xs text-gray-500 mt-2">
                    Fra: <a href="<?php echo esc_url(get_permalink($owner->ID)); ?>" class="link link-primary">
                        <?php echo esc_html($owner->post_title); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- Categories -->
            <?php if (!empty($categories)): ?>
                <div class="flex gap-1 flex-wrap mt-2">
                    <?php foreach ($categories as $cat): ?>
                        <span class="badge badge-outline badge-sm">
                            <?php echo esc_html($cat->name); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Visit Button -->
            <?php if ($tool_url): ?>
                <div class="card-actions justify-end mt-4">
                    <a href="<?php echo esc_url($tool_url); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary">
                        Besøk →
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Display an event card
 */
function bimverdi_display_event_card($post_id, $show_register_button = false) {
    $post = get_post($post_id);
    $event_date = get_field('dato', $post_id);
    $event_time = get_field('tidspunkt', $post_id);
    $event_location = get_field('lokasjon', $post_id);
    $description = wp_trim_words($post->post_content, 20, '...');
    $capacity = get_field('kapasitet', $post_id);
    $registrations_count = bimverdi_count_event_registrations($post_id);
    $is_registered = bimverdi_is_user_registered_to_event($post_id);
    
    ?>
    <div class="card bg-white shadow-md hover:shadow-lg transition-all">
        <!-- Content -->
        <div class="card-body p-4">
            
            <!-- Title -->
            <h3 class="card-title text-lg font-bold">
                <?php echo esc_html($post->post_title); ?>
            </h3>
            
            <!-- Date & Time -->
            <div class="text-sm text-gray-600 space-y-1">
                <?php if ($event_date): ?>
                    <div class="flex items-center gap-2">
                        <span class="text-primary">📅</span>
                        <span><?php echo esc_html(date_i18n('d. F Y', strtotime($event_date))); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($event_time): ?>
                    <div class="flex items-center gap-2">
                        <span class="text-primary">🕐</span>
                        <span><?php echo esc_html($event_time); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($event_location): ?>
                    <div class="flex items-center gap-2">
                        <span class="text-primary">📍</span>
                        <span><?php echo esc_html($event_location); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Description -->
            <p class="text-sm text-gray-600 mt-3">
                <?php echo esc_html($description); ?>
            </p>
            
            <!-- Capacity -->
            <?php if ($capacity): ?>
                <div class="text-xs text-gray-500 mt-2">
                    Påmeldinger: <strong><?php echo esc_html($registrations_count); ?>/<?php echo esc_html($capacity); ?></strong>
                </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="card-actions justify-between mt-4">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="btn btn-sm btn-outline">
                    Detaljer
                </a>
                <?php if ($show_register_button && is_user_logged_in()): ?>
                    <button class="btn btn-sm <?php echo $is_registered ? 'btn-outline' : 'btn-primary'; ?>" 
                            onclick="bimverdi_toggle_event_registration(<?php echo (int) $post_id; ?>)">
                        <?php echo $is_registered ? 'Avmeld' : 'Meld på'; ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Helper: Count event registrations
 */
function bimverdi_count_event_registrations($event_id) {
    $args = array(
        'post_type' => 'pamelding',
        'meta_query' => array(
            array(
                'key' => 'arrangement',
                'value' => $event_id,
            ),
        ),
        'fields' => 'ids',
    );
    return count(get_posts($args));
}

/**
 * Helper: Check if user is registered to event
 */
function bimverdi_is_user_registered_to_event($event_id) {
    if (!is_user_logged_in()) {
        return false;
    }
    
    $user_id = get_current_user_id();
    $args = array(
        'post_type' => 'pamelding',
        'meta_query' => array(
            array(
                'key' => 'bruker',
                'value' => $user_id,
            ),
            array(
                'key' => 'arrangement',
                'value' => $event_id,
            ),
        ),
        'fields' => 'ids',
    );
    
    return count(get_posts($args)) > 0;
}
