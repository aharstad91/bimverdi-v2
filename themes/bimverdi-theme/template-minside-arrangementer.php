<?php
/**
 * Template Name: Min Side - Arrangementer
 * 
 * Events page for members - browse and register for events
 * 
 * @package BimVerdi_Theme
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

// Demo mode
$is_demo = empty($company_id);

// Get all upcoming events
if ($is_demo) {
    $upcoming_events = function_exists('bimverdi_get_mock_events') ? bimverdi_get_mock_events() : array();
    $registered_events = array(1);
} else {
    $upcoming_events = get_posts(array(
        'post_type' => 'arrangement',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'arrangement_dato',
                'value' => date('Y-m-d'),
                'compare' => '>=',
                'type' => 'DATE'
            )
        ),
        'meta_key' => 'arrangement_dato',
        'orderby' => 'meta_value',
        'order' => 'ASC',
    ));
    
    // Get user's current registrations
    $my_registrations = get_posts(array(
        'post_type' => 'pamelding',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'pamelding_bruker',
                'value' => $user_id,
            )
        ),
    ));

    $registered_events = array();
    foreach ($my_registrations as $reg) {
        $event_id = get_post_meta($reg->ID, 'pamelding_arrangement', true);
        if ($event_id) {
            $registered_events[] = $event_id;
        }
    }
}

// Start Min Side layout
get_template_part('template-parts/minside-layout-start', null, array(
    'current_page' => 'arrangementer',
    'page_title' => 'Arrangementer',
    'page_icon' => 'calendar-days',
    'page_description' => 'Kommende kurs, seminarer og møter i BIM Verdi nettverket',
));
?>

<!-- Demo Banner -->
<?php if ($is_demo): ?>
    <wa-alert variant="warning" open class="mb-6">
        <wa-icon slot="icon" name="triangle-exclamation" library="fa"></wa-icon>
        <strong>Demo-data vises</strong><br>
        Du ser demo-data fordi ingen faktisk bedrift er opprettet ennå.
    </wa-alert>
<?php endif; ?>

<!-- My Registrations -->
<?php if (!empty($registered_events)): ?>
<wa-card class="mb-6 border-l-4 border-l-orange-500">
    <div slot="header" class="flex items-center gap-2">
        <wa-icon name="circle-check" library="fa" style="color: var(--wa-color-success-600);"></wa-icon>
        <strong>Påmeldinger</strong>
        <wa-badge variant="success"><?php echo count($registered_events); ?></wa-badge>
    </div>
    <div class="space-y-3">
        <?php 
        foreach ($registered_events as $event_id):
            $event = get_post($event_id);
            if (!$event) continue;
            
            $dato = get_field('arrangement_dato', $event_id);
            $tid = get_field('arrangement_tid', $event_id);
            $sted = get_field('arrangement_sted', $event_id);
        ?>
        <div class="flex items-start gap-4 p-4 bg-orange-50 rounded-lg">
            <div class="flex-shrink-0 w-14 h-14 bg-orange-500 text-white rounded-lg flex flex-col items-center justify-center">
                <div class="text-lg font-bold leading-none"><?php echo date('d', strtotime($dato)); ?></div>
                <div class="text-xs uppercase"><?php echo date('M', strtotime($dato)); ?></div>
            </div>
            <div class="flex-grow">
                <h3 class="font-bold text-gray-900"><?php echo esc_html($event->post_title); ?></h3>
                <div class="text-sm text-gray-600 mt-1 flex flex-wrap gap-3">
                    <?php if ($tid): ?>
                        <span class="flex items-center gap-1">
                            <wa-icon name="clock" library="fa" style="font-size: 0.75rem;"></wa-icon>
                            <?php echo esc_html($tid); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($sted): ?>
                        <span class="flex items-center gap-1">
                            <wa-icon name="location-dot" library="fa" style="font-size: 0.75rem;"></wa-icon>
                            <?php echo esc_html($sted); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <wa-button variant="brand" size="small" href="<?php echo get_permalink($event_id); ?>">
                Se detaljer
            </wa-button>
        </div>
        <?php endforeach; ?>
    </div>
</wa-card>
<?php endif; ?>

<!-- Upcoming Events -->
<?php if (!empty($upcoming_events)): ?>
<div class="space-y-4">
    <h2 class="text-xl font-bold text-gray-900">Alle arrangementer</h2>
    
    <?php foreach ($upcoming_events as $event):
        $dato = get_field('arrangement_dato', $event->ID);
        $tid = get_field('arrangement_tid', $event->ID);
        $sted = get_field('arrangement_sted', $event->ID);
        $maks_deltakere = get_field('arrangement_maks_deltakere', $event->ID);
        $is_registered = in_array($event->ID, $registered_events);
        
        // Count registrations for this event
        $registrations = new WP_Query(array(
            'post_type' => 'pamelding',
            'meta_query' => array(
                array(
                    'key' => 'pamelding_arrangement',
                    'value' => $event->ID,
                )
            ),
            'posts_per_page' => -1,
        ));
        $registration_count = $registrations->found_posts;
    ?>
    <wa-card class="hover:shadow-lg transition-shadow <?php echo $is_registered ? 'border-2 border-orange-500' : ''; ?>">
        <div class="p-6">
            <div class="flex gap-6 items-start">
                
                <div class="flex-shrink-0 w-20 h-20 bg-purple-600 text-white rounded-lg flex flex-col items-center justify-center">
                    <div class="text-2xl font-bold"><?php echo date('d', strtotime($dato)); ?></div>
                    <div class="text-xs uppercase font-semibold"><?php echo date('M', strtotime($dato)); ?></div>
                </div>

                <div class="flex-grow">
                    <div class="flex items-center gap-2 mb-2">
                        <h3 class="text-xl font-bold text-gray-900">
                            <?php echo esc_html($event->post_title); ?>
                        </h3>
                        <?php if ($is_registered): ?>
                            <wa-badge variant="success">
                                <wa-icon slot="prefix" name="check" library="fa"></wa-icon>
                                Påmeldt
                            </wa-badge>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-sm text-gray-600 space-y-1 mb-3">
                        <?php if ($tid): ?>
                            <div class="flex items-center gap-2">
                                <wa-icon name="clock" library="fa" style="font-size: 0.875rem;"></wa-icon>
                                <strong><?php echo esc_html($tid); ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if ($sted): ?>
                            <div class="flex items-center gap-2">
                                <wa-icon name="location-dot" library="fa" style="font-size: 0.875rem;"></wa-icon>
                                <?php echo esc_html($sted); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($maks_deltakere): ?>
                            <div class="flex items-center gap-2">
                                <wa-icon name="users" library="fa" style="font-size: 0.875rem;"></wa-icon>
                                <?php echo $registration_count; ?>/<?php echo intval($maks_deltakere); ?> deltakere
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($event->post_excerpt)): ?>
                    <p class="text-sm text-gray-600">
                        <?php echo wp_trim_words($event->post_excerpt, 25); ?>
                    </p>
                    <?php endif; ?>
                </div>

                <div class="flex-shrink-0">
                    <?php if ($is_registered): ?>
                        <wa-button variant="success" outline disabled>
                            <wa-icon slot="prefix" name="check" library="fa"></wa-icon>
                            Påmeldt
                        </wa-button>
                    <?php else: ?>
                        <form method="POST" style="display:inline;">
                            <wa-button type="submit" name="register_event" value="<?php echo $event->ID; ?>" variant="brand">
                                Meld meg på
                            </wa-button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </wa-card>
    <?php endforeach; ?>
</div>
<?php else: ?>

<wa-card class="text-center py-12">
    <div class="flex flex-col items-center">
        <wa-icon name="calendar-xmark" library="fa" style="font-size: 4rem; color: var(--wa-color-neutral-300); margin-bottom: 1rem;"></wa-icon>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Ingen kommende arrangementer</h3>
        <p class="text-gray-600 mb-4">Nye arrangementer annonseres på BIM Verdi siden</p>
        <wa-button variant="brand" href="<?php echo home_url('/arrangementer/'); ?>">
            Se alle arrangementer
        </wa-button>
    </div>
</wa-card>

<?php endif; ?>

<?php 
get_template_part('template-parts/minside-layout-end');
get_footer(); 
?>
