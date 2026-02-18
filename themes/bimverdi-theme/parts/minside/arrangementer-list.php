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
    <div class="mb-6 p-4 bg-[#E8F5E0] border-l-4 border-[#5A9A3D] rounded-r">
        <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A9A3D]">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <p class="text-[#111827]"><?php _e('Du er nå avmeldt arrangementet.', 'bimverdi'); ?></p>
        </div>
    </div>
<?php endif; ?>

<!-- My Registrations Section -->
<?php if (!empty($user_registered_events)): ?>
<section class="mb-10">
    <!-- Table -->
    <table class="w-full text-sm table-fixed">
        <colgroup>
            <col style="width: 30%">
            <col style="width: 14%">
            <col style="width: 14%">
            <col style="width: 14%">
            <col style="width: 14%">
            <col style="width: 14%">
        </colgroup>
        <thead>
            <tr class="border-b border-[#E7E5E4]">
                <th class="text-left py-3 px-4 text-xs font-medium text-[#57534E]"><?php _e('Arrangement', 'bimverdi'); ?></th>
                <th class="text-left py-3 px-2 text-xs font-medium text-[#57534E]"><?php _e('Dato', 'bimverdi'); ?></th>
                <th class="text-left py-3 px-2 text-xs font-medium text-[#57534E]"><?php _e('Type', 'bimverdi'); ?></th>
                <th class="text-left py-3 px-2 text-xs font-medium text-[#57534E]"><?php _e('Sted', 'bimverdi'); ?></th>
                <th class="text-left py-3 px-2 text-xs font-medium text-[#57534E]"><?php _e('Status', 'bimverdi'); ?></th>
                <th class="text-right py-3 px-4 text-xs font-medium text-[#57534E]"><?php _e('Handlinger', 'bimverdi'); ?></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[#E7E5E4]">
            <?php foreach ($user_registered_events as $reg_data):
                $event = $reg_data['event'];
                $event_id = $reg_data['event_id'];
                $reg_id = $reg_data['registration_id'];
                $reg_status = $reg_data['status'];
                $dato = get_field('arrangement_dato', $event_id);
                $arrangement_type = get_field('arrangement_type', $event_id) ?: 'digitalt';
                $sted_by = get_field('sted_by', $event_id);

                $location_display = ($arrangement_type === 'digitalt') ? 'Online' : ($sted_by ?: '-');

                // Format date
                $date_display = '-';
                if ($dato) {
                    $date_obj = DateTime::createFromFormat('Y-m-d', $dato);
                    if ($date_obj) {
                        $date_display = $date_obj->format('d.m.Y');
                    }
                }

                // Status styling
                $status_classes = [
                    'bekreftet' => 'text-green-700',
                    'venteliste' => 'text-yellow-700',
                    'avmeldt' => 'text-[#57534E]',
                ];
                $status_labels = [
                    'bekreftet' => __('Påmeldt', 'bimverdi'),
                    'venteliste' => __('Venteliste', 'bimverdi'),
                    'avmeldt' => __('Avmeldt', 'bimverdi'),
                ];
            ?>
                <tr>
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-[#F5F5F4] rounded flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#57534E]">
                                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                    <line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/>
                                    <line x1="3" x2="21" y1="10" y2="10"/>
                                </svg>
                            </div>
                            <span class="font-medium text-[#111827]"><?php echo esc_html($event->post_title); ?></span>
                        </div>
                    </td>
                    <td class="py-3 px-2 text-[#57534E]"><?php echo esc_html($date_display); ?></td>
                    <td class="py-3 px-2 text-[#57534E]">
                        <span class="flex items-center gap-1">
                            <?php echo bimverdi_minside_type_icon($arrangement_type); ?>
                            <?php echo bimverdi_minside_type_label($arrangement_type); ?>
                        </span>
                    </td>
                    <td class="py-3 px-2 text-[#57534E]"><?php echo esc_html($location_display); ?></td>
                    <td class="py-3 px-2 <?php echo esc_attr($status_classes[$reg_status] ?? 'text-green-700'); ?>">
                        <?php echo esc_html($status_labels[$reg_status] ?? __('Påmeldt', 'bimverdi')); ?>
                    </td>
                    <td class="py-3 px-4">
                        <div class="flex items-center justify-end gap-1">
                            <a href="<?php echo get_permalink($event_id); ?>" class="p-1.5 rounded hover:bg-[#F5F5F4] transition-colors" title="<?php esc_attr_e('Se', 'bimverdi'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#57534E]">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </a>
                            <?php if ($reg_status !== 'avmeldt'): ?>
                                <button type="button"
                                        onclick="confirmUnregister(<?php echo $reg_id; ?>, '<?php echo esc_js($event->post_title); ?>')"
                                        class="p-1.5 rounded hover:bg-red-50 transition-colors text-[#57534E] hover:text-red-600"
                                        title="<?php esc_attr_e('Meld av', 'bimverdi'); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                        <polyline points="16 17 21 12 16 7"/>
                                        <line x1="21" y1="12" x2="9" y2="12"/>
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Footer -->
    <div class="py-3 px-4 text-right text-sm text-[#57534E] border-t border-[#E7E5E4]">
        <?php printf(__('Viser %d av %d påmeldinger', 'bimverdi'), count($user_registered_events), count($user_registered_events)); ?>
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

<?php endif; ?>

<!-- CTA: Se alle arrangementer -->
<section class="<?php echo !empty($user_registered_events) ? 'mt-8 pt-8 border-t border-[#E7E5E4]' : ''; ?>">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-[#111827] mb-1"><?php _e('Utforsk arrangementer', 'bimverdi'); ?></h2>
            <p class="text-sm text-[#57534E]"><?php _e('Se alle kommende møter, workshops og nettverkssamlinger', 'bimverdi'); ?></p>
        </div>
        <?php bimverdi_button([
            'text'    => __('Se alle arrangementer', 'bimverdi'),
            'variant' => 'primary',
            'href'    => home_url('/arrangement/'),
            'icon'    => 'arrow-right',
        ]); ?>
    </div>
</section>
