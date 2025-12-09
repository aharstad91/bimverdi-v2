<?php
/**
 * Template Name: Min Side - Arrangementer
 * Description: Min Side - Arrangementer oversikt (uten tabs)
 *
 * Viser kommende påmeldte arrangementer og historikk lineært
 */

if (!defined('ABSPATH')) {
    exit;
}

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/logg-inn/'));
    exit;
}

get_header();

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$today = date('Y-m-d');

// Hent brukerens påmeldinger (aktive for fremtidige arrangementer)
$upcoming_registrations = get_posts([
    'post_type' => 'pamelding',
    'posts_per_page' => -1,
    'meta_query' => [
        'relation' => 'AND',
        [
            'key' => 'pamelding_bruker',
            'value' => $user_id,
            'compare' => '='
        ],
        [
            'key' => 'pamelding_status',
            'value' => 'aktiv',
            'compare' => '='
        ]
    ]
]);

// Filtrer til kun kommende arrangementer
$upcoming_active = [];
foreach ($upcoming_registrations as $reg) {
    $event_id = get_field('pamelding_arrangement', $reg->ID);
    if ($event_id) {
        $event_date = get_field('arrangement_dato', $event_id);
        if ($event_date && $event_date >= $today) {
            $upcoming_active[] = [
                'registration' => $reg,
                'event_id' => $event_id,
                'event_date' => $event_date
            ];
        }
    }
}

// Sorter etter dato (nærmeste først)
usort($upcoming_active, function($a, $b) {
    return strcmp($a['event_date'], $b['event_date']);
});

// Hent historikk (avmeldte + deltatt på tidligere)
$all_registrations = get_posts([
    'post_type' => 'pamelding',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => 'pamelding_bruker',
            'value' => $user_id,
            'compare' => '='
        ]
    ]
]);

$history = [];
foreach ($all_registrations as $reg) {
    $event_id = get_field('pamelding_arrangement', $reg->ID);
    $status = get_field('pamelding_status', $reg->ID);
    
    if ($event_id) {
        $event_date = get_field('arrangement_dato', $event_id);
        
        // Historikk = avmeldte ELLER tidligere arrangementer
        $is_past = $event_date && $event_date < $today;
        $is_cancelled = $status === 'avmeldt';
        
        if ($is_past || $is_cancelled) {
            $history[] = [
                'registration' => $reg,
                'event_id' => $event_id,
                'event_date' => $event_date,
                'status' => $status,
                'is_past' => $is_past
            ];
        }
    }
}

// Sorter historikk etter dato (nyeste først)
usort($history, function($a, $b) {
    return strcmp($b['event_date'], $a['event_date']);
});

// Start Min Side layout med sidebar
get_template_part('template-parts/minside-layout-start', null, array(
    'current_page' => 'arrangementer',
    'page_title' => 'Arrangementer',
    'page_icon' => 'calendar',
    'page_description' => 'Se dine påmeldinger og arrangementshistorikk',
));
?>
    <!-- ============================================ -->
    <!-- SEKSJON 1: KOMMENDE PÅMELDTE ARRANGEMENTER -->
    <!-- ============================================ -->
    <section class="mb-8">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <wa-icon library="fa" name="solid/calendar-check" class="text-bim-orange"></wa-icon>
            Kommende påmeldte arrangementer
        </h2>
        
        <?php if (empty($upcoming_active)) : ?>
            <wa-card class="p-6 text-center">
                <wa-icon library="fa" name="regular/calendar" class="text-4xl text-gray-300 mb-4"></wa-icon>
                <p class="text-gray-500 mb-4">Du er ikke påmeldt noen kommende arrangementer.</p>
                <a href="<?php echo home_url('/arrangementer/'); ?>">
                    <wa-button variant="brand">
                        <wa-icon library="fa" name="solid/search" slot="prefix"></wa-icon>
                        Se alle arrangementer
                    </wa-button>
                </a>
            </wa-card>
        <?php else : ?>
            <div class="space-y-4">
                <?php foreach ($upcoming_active as $item) : 
                    $reg = $item['registration'];
                    $event_id = $item['event_id'];
                    $event_date = $item['event_date'];
                    
                    $event_title = get_the_title($event_id);
                    $event_permalink = get_permalink($event_id);
                    $time_start = get_field('tidspunkt_start', $event_id);
                    $time_end = get_field('tidspunkt_slutt', $event_id);
                    $format = get_field('arrangement_format', $event_id);
                    $location = get_field('fysisk_adresse', $event_id);
                    $meeting_link = get_field('motelenke', $event_id);
                    
                    // Hent temagrupper og arrangementstyper
                    $temagrupper = wp_get_post_terms($event_id, 'temagruppe');
                    $arrangementstyper = wp_get_post_terms($event_id, 'arrangementstype');
                    
                    // Avmeldingsfrist
                    $avmelding_frist = get_field('pamelding_frist', $event_id);
                    $can_cancel = $avmelding_frist ? (strtotime($avmelding_frist) > time()) : true;
                    
                    // Formater dato
                    $date_formatted = date_i18n('l j. F Y', strtotime($event_date));
                    $time_formatted = $time_start ? $time_start : '';
                    if ($time_end) {
                        $time_formatted .= ' – ' . $time_end;
                    }
                    
                    // Format badge
                    $format_label = '';
                    $format_icon = '';
                    switch ($format) {
                        case 'fysisk':
                            $format_label = 'Fysisk';
                            $format_icon = 'solid/location-dot';
                            break;
                        case 'digitalt':
                            $format_label = 'Digitalt';
                            $format_icon = 'solid/video';
                            break;
                        case 'hybrid':
                            $format_label = 'Hybrid';
                            $format_icon = 'solid/people-arrows';
                            break;
                    }
                ?>
                    <wa-card class="overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="p-4 md:p-5">
                            <!-- Hovedinnhold - Flexbox row -->
                            <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                                
                                <!-- Dato-boks -->
                                <div class="flex-shrink-0 bg-bim-orange text-white rounded-lg p-3 text-center min-w-[70px]">
                                    <div class="text-2xl font-bold"><?php echo date('j', strtotime($event_date)); ?></div>
                                    <div class="text-xs uppercase"><?php echo date_i18n('M', strtotime($event_date)); ?></div>
                                </div>
                                
                                <!-- Hovedinfo - tittel og metadata -->
                                <div class="flex-1 min-w-0">
                                    <a href="<?php echo esc_url($event_permalink); ?>" class="text-lg font-semibold text-gray-900 hover:text-bim-orange transition-colors block truncate">
                                        <?php echo esc_html($event_title); ?>
                                    </a>
                                    
                                    <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-600">
                                        <?php if ($time_formatted) : ?>
                                            <span class="flex items-center gap-1">
                                                <wa-icon library="fa" name="regular/clock" class="text-gray-400"></wa-icon>
                                                <?php echo esc_html($time_formatted); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($format) : ?>
                                            <span class="flex items-center gap-1">
                                                <wa-icon library="fa" name="<?php echo esc_attr($format_icon); ?>" class="text-gray-400"></wa-icon>
                                                <?php echo esc_html($format_label); ?>
                                                <?php if ($format === 'fysisk' && $location) : ?>
                                                    <span class="text-gray-400">·</span>
                                                    <?php echo esc_html($location); ?>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Tags: Temagrupper og arrangementstyper -->
                                    <?php if (!empty($temagrupper) || !empty($arrangementstyper)) : ?>
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            <?php foreach ($temagrupper as $temagruppe) : ?>
                                                <wa-tag variant="primary" size="small"><?php echo esc_html($temagruppe->name); ?></wa-tag>
                                            <?php endforeach; ?>
                                            <?php foreach ($arrangementstyper as $type) : ?>
                                                <wa-tag variant="neutral" size="small"><?php echo esc_html($type->name); ?></wa-tag>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Status -->
                                <div class="flex-shrink-0">
                                    <wa-tag variant="success" size="small">
                                        <wa-icon library="fa" name="solid/check" slot="prefix"></wa-icon>
                                        Påmeldt
                                    </wa-tag>
                                </div>
                                
                                <!-- Handlinger -->
                                <div class="flex-shrink-0 flex flex-wrap gap-2 lg:flex-col lg:items-end">
                                    <a href="<?php echo esc_url($event_permalink); ?>">
                                        <wa-button variant="neutral" size="small" outline>
                                            <wa-icon library="fa" name="solid/arrow-right" slot="prefix"></wa-icon>
                                            Detaljer
                                        </wa-button>
                                    </a>
                                    
                                    <?php if ($can_cancel) : ?>
                                        <wa-button 
                                            variant="danger" 
                                            size="small" 
                                            outline
                                            onclick="confirmCancel(<?php echo $reg->ID; ?>, '<?php echo esc_js($event_title); ?>')"
                                        >
                                            <wa-icon library="fa" name="solid/xmark" slot="prefix"></wa-icon>
                                            Meld av
                                        </wa-button>
                                    <?php else : ?>
                                        <wa-tag variant="neutral" size="small">
                                            <wa-icon library="fa" name="solid/lock" slot="prefix"></wa-icon>
                                            Stengt
                                        </wa-tag>
                                    <?php endif; ?>
                                    
                                    <?php if (($format === 'digitalt' || $format === 'hybrid') && $meeting_link) : ?>
                                        <a href="<?php echo esc_url($meeting_link); ?>" target="_blank">
                                            <wa-button variant="brand" size="small">
                                                <wa-icon library="fa" name="solid/video" slot="prefix"></wa-icon>
                                                Delta
                                            </wa-button>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </wa-card>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    
    
    <!-- ============================================ -->
    <!-- SEKSJON 2: HISTORIKK -->
    <!-- ============================================ -->
    <section>
        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <wa-icon library="fa" name="solid/clock-rotate-left" class="text-gray-500"></wa-icon>
            Historikk
        </h2>
        
        <?php if (empty($history)) : ?>
            <wa-card class="p-6 text-center">
                <wa-icon library="fa" name="regular/folder-open" class="text-4xl text-gray-300 mb-4"></wa-icon>
                <p class="text-gray-500">Ingen tidligere arrangementer eller avmeldinger.</p>
            </wa-card>
        <?php else : ?>
            <div class="space-y-3">
                <?php foreach ($history as $item) : 
                    $reg = $item['registration'];
                    $event_id = $item['event_id'];
                    $event_date = $item['event_date'];
                    $status = $item['status'];
                    $is_past = $item['is_past'];
                    
                    $event_title = get_the_title($event_id);
                    $event_permalink = get_permalink($event_id);
                    
                    // Formater dato
                    $date_formatted = date_i18n('j. F Y', strtotime($event_date));
                    
                    // Status logic
                    if ($status === 'avmeldt') {
                        $status_label = 'Avmeldt';
                        $status_variant = 'warning';
                        $status_icon = 'solid/xmark';
                    } else {
                        $status_label = 'Deltatt';
                        $status_variant = 'neutral';
                        $status_icon = 'solid/check';
                    }
                ?>
                    <wa-card class="overflow-hidden opacity-75 hover:opacity-100 transition-opacity">
                        <div class="p-4">
                            <div class="flex items-center justify-between gap-4">
                                <!-- Venstre: Info -->
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <!-- Mini dato -->
                                    <div class="flex-shrink-0 text-center text-gray-400 w-12">
                                        <div class="text-lg font-semibold"><?php echo date('j', strtotime($event_date)); ?></div>
                                        <div class="text-xs uppercase"><?php echo date_i18n('M', strtotime($event_date)); ?></div>
                                    </div>
                                    
                                    <div class="min-w-0">
                                        <a href="<?php echo esc_url($event_permalink); ?>" class="font-medium text-gray-700 hover:text-bim-orange transition-colors truncate block">
                                            <?php echo esc_html($event_title); ?>
                                        </a>
                                        <p class="text-sm text-gray-400"><?php echo $date_formatted; ?></p>
                                    </div>
                                </div>
                                
                                <!-- Høyre: Status -->
                                <div class="flex-shrink-0">
                                    <wa-tag variant="<?php echo $status_variant; ?>" size="small">
                                        <wa-icon library="fa" name="<?php echo $status_icon; ?>" slot="prefix"></wa-icon>
                                        <?php echo $status_label; ?>
                                    </wa-tag>
                                </div>
                            </div>
                        </div>
                    </wa-card>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

<?php 
get_template_part('template-parts/minside-layout-end');
?>

<!-- Cancel confirmation dialog -->
<wa-dialog id="cancel-dialog" label="Bekreft avmelding">
    <p id="cancel-dialog-message">Er du sikker på at du vil melde deg av dette arrangementet?</p>
    <div slot="footer" class="flex gap-2 justify-end">
        <wa-button variant="neutral" onclick="document.getElementById('cancel-dialog').hide()">Avbryt</wa-button>
        <wa-button variant="danger" id="confirm-cancel-btn">Meld av</wa-button>
    </div>
</wa-dialog>

<script>
let currentCancelId = null;

function confirmCancel(registrationId, eventTitle) {
    currentCancelId = registrationId;
    document.getElementById('cancel-dialog-message').textContent = 
        'Er du sikker på at du vil melde deg av "' + eventTitle + '"?';
    document.getElementById('cancel-dialog').show();
}

document.getElementById('confirm-cancel-btn').addEventListener('click', function() {
    if (!currentCancelId) return;
    
    const btn = this;
    btn.loading = true;
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'bimverdi_cancel_registration',
            registration_id: currentCancelId,
            nonce: '<?php echo wp_create_nonce('bimverdi_cancel_registration'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.data || 'En feil oppstod');
            btn.loading = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('En feil oppstod');
        btn.loading = false;
    });
});
</script>

<?php get_footer(); ?>
