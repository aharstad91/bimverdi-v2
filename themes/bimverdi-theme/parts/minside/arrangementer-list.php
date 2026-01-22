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
        'relation' => 'OR',
        [
            'key'   => 'bruker',
            'value' => $user_id,
        ],
        [
            'key'   => 'pamelding_bruker',
            'value' => $user_id,
        ],
    ],
    'fields' => 'ids',
]);

// Get registered event IDs
$registered_event_ids = [];
foreach ($user_registrations as $reg_id) {
    // Try both field names for backwards compatibility
    $event_id = get_field('arrangement', $reg_id) ?: get_field('pamelding_arrangement', $reg_id);
    if ($event_id) {
        $registered_event_ids[] = is_array($event_id) ? $event_id[0] : $event_id;
    }
}

// Build array of user's registrations with event details
$user_registered_events = [];
foreach ($user_registrations as $reg_id) {
    $event_id = get_field('arrangement', $reg_id) ?: get_field('pamelding_arrangement', $reg_id);
    if ($event_id) {
        $event_id = is_array($event_id) ? $event_id[0] : $event_id;
        $event = get_post($event_id);
        if ($event && $event->post_status === 'publish') {
            $user_registered_events[] = [
                'registration_id' => $reg_id,
                'event_id'        => $event_id,
                'event'           => $event,
                'status'          => get_field('pamelding_status', $reg_id) ?: 'bekreftet',
            ];
        }
    }
}

// Sort registered events by date
usort($user_registered_events, function($a, $b) {
    $date_a = get_field('arrangement_dato', $a['event_id']) ?: '9999-99-99';
    $date_b = get_field('arrangement_dato', $b['event_id']) ?: '9999-99-99';
    return strcmp($date_a, $date_b);
});

// Query upcoming events (using toggle-based system)
$upcoming_events = get_posts([
    'post_type'      => 'arrangement',
    'posts_per_page' => 10,
    'post_status'    => 'publish',
    'meta_key'       => 'arrangement_dato',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
    'meta_query'     => [
        'relation' => 'AND',
        [
            'key'     => 'arrangement_status_toggle',
            'value'   => 'kommende',
        ],
        [
            'key'     => 'arrangement_status',
            'value'   => 'planlagt',
        ],
    ],
]);

// Handle unregister action
if (isset($_POST['action']) && $_POST['action'] === 'unregister' && isset($_POST['registration_id'])) {
    if (wp_verify_nonce($_POST['nonce'] ?? '', 'bimverdi_event_nonce')) {
        $reg_id = intval($_POST['registration_id']);
        // Verify ownership
        $reg_user = get_field('bruker', $reg_id) ?: get_field('pamelding_bruker', $reg_id);
        if ($reg_user == $user_id) {
            wp_delete_post($reg_id, true);
            wp_redirect(bimverdi_minside_url('arrangementer') . '?avmeldt=1');
            exit;
        }
    }
}

/**
 * Get arrangement type icon
 */
function bimverdi_minside_type_icon($type) {
    $icons = [
        'fysisk' => '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
        'digitalt' => '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5"/><rect x="2" y="6" width="14" height="12" rx="2"/></svg>',
        'hybrid' => '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>',
    ];
    return $icons[$type] ?? $icons['digitalt'];
}

function bimverdi_minside_type_label($type) {
    $labels = [
        'fysisk' => 'Fysisk',
        'digitalt' => 'Digitalt',
        'hybrid' => 'Hybrid',
    ];
    return $labels[$type] ?? 'Digitalt';
}
?>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => __('Arrangementer', 'bimverdi'),
    'description' => __('Kommende møter, workshops og nettverkssamlinger', 'bimverdi'),
]); ?>

<!-- Success Message -->
<?php if (isset($_GET['avmeldt'])): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
        <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-green-600">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <p class="text-green-800"><?php _e('Du er nå avmeldt arrangementet.', 'bimverdi'); ?></p>
        </div>
    </div>
<?php endif; ?>

<!-- My Registrations Section -->
<?php if (!empty($user_registered_events)): ?>
<section class="mb-10">
    <h2 class="text-lg font-semibold text-[#1A1A1A] mb-4 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <?php _e('Mine påmeldinger', 'bimverdi'); ?>
        <span class="text-sm font-normal text-[#5A5A5A]">(<?php echo count($user_registered_events); ?>)</span>
    </h2>

    <div class="bg-white rounded-lg border border-[#E5E0D8] divide-y divide-[#E5E0D8]">
        <?php foreach ($user_registered_events as $reg_data):
            $event = $reg_data['event'];
            $event_id = $reg_data['event_id'];
            $reg_id = $reg_data['registration_id'];
            $reg_status = $reg_data['status'];
            $dato = get_field('arrangement_dato', $event_id);
            $tid_start = get_field('tidspunkt_start', $event_id);
            $arrangement_type = get_field('arrangement_type', $event_id) ?: 'digitalt';
            $sted_by = get_field('sted_by', $event_id);

            $location_display = '';
            if ($arrangement_type === 'fysisk' || $arrangement_type === 'hybrid') {
                $location_display = $sted_by ?: '';
            } elseif ($arrangement_type === 'digitalt') {
                $location_display = 'Online';
            }
        ?>
            <div class="p-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    <!-- Date Badge -->
                    <?php if ($dato):
                        $date_obj = DateTime::createFromFormat('Y-m-d', $dato);
                        if ($date_obj):
                    ?>
                        <div class="flex-shrink-0 w-14 h-14 bg-[#F2F0EB] rounded-lg flex flex-col items-center justify-center">
                            <span class="text-xs font-medium text-[#5A5A5A] uppercase"><?php echo $date_obj->format('M'); ?></span>
                            <span class="text-xl font-bold text-[#1A1A1A]"><?php echo $date_obj->format('j'); ?></span>
                        </div>
                    <?php endif; endif; ?>

                    <!-- Event Info -->
                    <div class="min-w-0">
                        <h3 class="font-medium text-[#1A1A1A] truncate"><?php echo esc_html($event->post_title); ?></h3>
                        <div class="flex flex-wrap items-center gap-2 text-sm text-[#5A5A5A]">
                            <?php if ($tid_start): ?>
                                <span><?php echo esc_html($tid_start); ?></span>
                                <span class="text-[#D6D1C6]">•</span>
                            <?php endif; ?>
                            <span class="flex items-center gap-1">
                                <?php echo bimverdi_minside_type_icon($arrangement_type); ?>
                                <?php echo bimverdi_minside_type_label($arrangement_type); ?>
                            </span>
                            <?php if ($location_display): ?>
                                <span class="text-[#D6D1C6]">•</span>
                                <span><?php echo esc_html($location_display); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-shrink-0">
                    <!-- Registration Status -->
                    <?php
                    $status_classes = [
                        'bekreftet' => 'bg-green-100 text-green-800',
                        'venteliste' => 'bg-yellow-100 text-yellow-800',
                        'avmeldt' => 'bg-gray-100 text-gray-800',
                    ];
                    $status_labels = [
                        'bekreftet' => __('Påmeldt', 'bimverdi'),
                        'venteliste' => __('Venteliste', 'bimverdi'),
                        'avmeldt' => __('Avmeldt', 'bimverdi'),
                    ];
                    ?>
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium <?php echo esc_attr($status_classes[$reg_status] ?? 'bg-green-100 text-green-800'); ?>">
                        <?php echo esc_html($status_labels[$reg_status] ?? __('Påmeldt', 'bimverdi')); ?>
                    </span>

                    <!-- Actions -->
                    <a href="<?php echo get_permalink($event_id); ?>" class="p-2 rounded hover:bg-[#F2F0EB] transition-colors" title="<?php esc_attr_e('Se arrangement', 'bimverdi'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                            <polyline points="15 3 21 3 21 9"/>
                            <line x1="10" y1="14" x2="21" y2="3"/>
                        </svg>
                    </a>

                    <!-- Unregister Button -->
                    <?php if ($reg_status !== 'avmeldt'): ?>
                        <button type="button"
                                onclick="confirmUnregister(<?php echo $reg_id; ?>, '<?php echo esc_js($event->post_title); ?>')"
                                class="p-2 rounded hover:bg-red-50 transition-colors text-[#5A5A5A] hover:text-red-600"
                                title="<?php esc_attr_e('Meld av', 'bimverdi'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Hidden form for unregistration -->
<form method="post" id="unregister-form" style="display: none;">
    <?php wp_nonce_field('bimverdi_event_nonce', 'nonce'); ?>
    <input type="hidden" name="action" value="unregister">
    <input type="hidden" name="registration_id" id="unregister-reg-id" value="">
</form>

<script>
function confirmUnregister(regId, eventTitle) {
    if (confirm('<?php echo esc_js(__('Er du sikker på at du vil melde deg av', 'bimverdi')); ?> "' + eventTitle + '"?')) {
        document.getElementById('unregister-reg-id').value = regId;
        document.getElementById('unregister-form').submit();
    }
}
</script>

<!-- Upcoming Events Section -->
<section>
    <h2 class="text-lg font-semibold text-[#1A1A1A] mb-4 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
        <?php _e('Kommende arrangementer', 'bimverdi'); ?>
    </h2>
<?php endif; ?>

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
            $dato = get_field('arrangement_dato', $event_id);
            $tid_start = get_field('tidspunkt_start', $event_id);
            $tid_slutt = get_field('tidspunkt_slutt', $event_id);
            $arrangement_type = get_field('arrangement_type', $event_id) ?: 'digitalt';
            $sted_by = get_field('sted_by', $event_id);
            $sted_adresse = get_field('sted_adresse', $event_id);
            $pamelding_url = get_field('pamelding_url', $event_id);
            $is_registered = in_array($event_id, $registered_event_ids);
            $max_deltakere = get_field('maks_deltakere', $event_id);

            // Get temagrupper
            $temagrupper = wp_get_post_terms($event_id, 'temagruppe', ['fields' => 'names']);
            $temagruppe_display = !empty($temagrupper) ? $temagrupper[0] : '';

            // Count registrations
            $registrations_count = count(get_posts([
                'post_type' => 'pamelding',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => [
                    'relation' => 'OR',
                    ['key' => 'arrangement', 'value' => $event_id],
                    ['key' => 'pamelding_arrangement', 'value' => $event_id],
                ],
            ]));
            $is_full = $max_deltakere && $registrations_count >= $max_deltakere;

            // Format time
            $time_display = $tid_start;
            if ($tid_slutt) $time_display .= ' – ' . $tid_slutt;

            // Location display
            $location_display = '';
            if ($arrangement_type === 'fysisk' || $arrangement_type === 'hybrid') {
                $location_display = $sted_by ?: $sted_adresse;
            } elseif ($arrangement_type === 'digitalt') {
                $location_display = 'Online';
            }
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
                                <?php if ($time_display): ?>
                                    <span class="text-[#B8B0A0]">•</span>
                                    <?php echo esc_html($time_display); ?>
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
                        <!-- Type -->
                        <span class="flex items-center gap-1">
                            <?php echo bimverdi_minside_type_icon($arrangement_type); ?>
                            <?php echo bimverdi_minside_type_label($arrangement_type); ?>
                        </span>

                        <?php if ($location_display): ?>
                            <span class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <?php echo esc_html($location_display); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($temagruppe_display): ?>
                            <span class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                <?php echo esc_html($temagruppe_display); ?>
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
                        <?php if (!$is_registered && !$is_full && $pamelding_url): ?>
                            <?php bimverdi_button([
                                'text'    => __('Meld på', 'bimverdi'),
                                'variant' => 'primary',
                                'href'    => $pamelding_url,
                                'target'  => '_blank',
                                'class'   => 'flex-1',
                            ]); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($user_registered_events)): ?>
</section>
<?php endif; ?>
