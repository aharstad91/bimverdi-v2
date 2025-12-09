<?php
/**
 * Template Name: DEPRECATED - Min Side Arrangementer
 * 
 * OLD TEMPLATE - DO NOT USE
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
    $upcoming_events = bimverdi_get_mock_events();
    $my_registrations_ids = array(1); // Second event is registered
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

$registered_event_ids = wp_list_pluck($my_registrations, 'ID');
$registered_events = array();
foreach ($my_registrations as $reg) {
    $event_id = get_post_meta($reg->ID, 'pamelding_arrangement', true);
    if ($event_id) {
        $registered_events[] = $event_id;
    }
}
?>

<!-- Min Side Horizontal Tab Navigation -->
<?php 
$current_tab = 'arrangementer';
get_template_part('template-parts/minside-tabs', null, array('current_tab' => $current_tab));
?>

<div class="min-h-screen bg-bim-beige-100 py-8">
    <div class="container mx-auto px-4">
        
        <!-- Demo Banner -->
        <?php if ($is_demo): ?>
            <div class="alert alert-warning shadow-lg mb-6 bg-alert text-black">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0-11l6.364 3.682a2 2 0 010 3.464L12 21l-6.364-3.682a2 2 0 010-3.464L12 2z"></path>
                    </svg>
                    <div>
                        <h3 class="font-bold">Demo-data vises</h3>
                        <div class="text-sm">Du ser demo-data fordi ingen faktisk bedrift er opprettet ennå. Påmeldinger registreres ikke i demo-modus.</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-bim-black-900">Arrangementer</h1>
        </div>

        <!-- Main Content (Full Width) -->
        <div>
                            </li>
                        </ul>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="lg:col-span-3">
                
                <div class="mb-6">
                    <p class="text-bim-black-700">Kommende kurs, seminarer og møter i BIM Verdi nettverket</p>
                </div>

                <!-- My Registrations -->
                <?php if (!empty($registered_events)): ?>
                <div class="card-hjem mb-6 border-l-4 border-l-bim-orange">
                    <div class="card-body p-6">
                        <h2 class="text-xl font-bold text-bim-black-900 mb-4">✓ Mine Påmeldinger</h2>
                        <div class="space-y-3">
                            <?php 
                            foreach ($registered_events as $event_id) {
                                $event = get_post($event_id);
                                if (!$event) continue;
                                
                                $dato = get_field('arrangement_dato', $event_id);
                                $tid = get_field('arrangement_tid', $event_id);
                                $sted = get_field('arrangement_sted', $event_id);
                            ?>
                            <div class="flex items-start gap-4 p-4 bg-bim-orange bg-opacity-5 rounded-lg">
                                <div class="flex-shrink-0 w-14 h-14 bg-bim-orange text-white rounded-lg flex flex-col items-center justify-center">
                                    <div class="text-lg font-bold"><?php echo date('d', strtotime($dato)); ?></div>
                                    <div class="text-xs uppercase"><?php echo date('M', strtotime($dato)); ?></div>
                                </div>
                                <div class="flex-grow">
                                    <h3 class="font-bold text-lg text-bim-black-900"><?php echo esc_html($event->post_title); ?></h3>
                                    <div class="text-sm text-bim-black-700 mt-1">
                                        <?php if ($tid): ?>
                                            <span class="mr-4">🕐 <?php echo esc_html($tid); ?></span>
                                        <?php endif; ?>
                                        <?php if ($sted): ?>
                                            <span>📍 <?php echo esc_html($sted); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <a href="<?php echo get_permalink($event_id); ?>" class="btn btn-sm btn-hjem-primary flex-shrink-0">
                                    Se detaljer
                                </a>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Upcoming Events -->
                <?php if (!empty($upcoming_events)): ?>
                <div class="space-y-4">
                    <h2 class="text-2xl font-bold text-bim-black-900">Alle Arrangementer</h2>
                    
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
                    <div class="card-hjem hover:shadow-lg transition-shadow <?php echo $is_registered ? 'border-2 border-bim-orange' : ''; ?>">
                        <div class="card-body p-6">
                            <div class="flex gap-6 items-start">
                                
                                <div class="flex-shrink-0 w-20 h-20 bg-bim-purple text-white rounded-lg flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold"><?php echo date('d', strtotime($dato)); ?></div>
                                    <div class="text-xs uppercase font-semibold"><?php echo date('M', strtotime($dato)); ?></div>
                                </div>

                                <div class="flex-grow">
                                    <h3 class="text-xl font-bold text-bim-black-900 mb-2">
                                        <?php echo esc_html($event->post_title); ?>
                                        <?php if ($is_registered): ?>
                                            <span class="badge badge-hjem-orange text-xs ml-2">Påmeldt</span>
                                        <?php endif; ?>
                                    </h3>
                                    
                                    <div class="text-sm text-bim-black-700 space-y-1 mb-3">
                                        <?php if ($tid): ?>
                                            <div>🕐 <strong><?php echo esc_html($tid); ?></strong></div>
                                        <?php endif; ?>
                                        <?php if ($sted): ?>
                                            <div>📍 <?php echo esc_html($sted); ?></div>
                                        <?php endif; ?>
                                        <?php if ($maks_deltakere): ?>
                                            <div>👥 <?php echo $registration_count; ?>/<?php echo intval($maks_deltakere); ?> deltakere</div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($event->post_excerpt)): ?>
                                    <p class="text-sm text-bim-black-700 mb-3">
                                        <?php echo wp_trim_words($event->post_excerpt, 25); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>

                                <div class="flex-shrink-0">
                                    <?php if ($is_registered): ?>
                                        <button class="btn btn-outline border-bim-orange text-bim-orange" onclick="alert('Du er allerede påmeldt!')">
                                            ✓ Påmeldt
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline;">
                                            <button type="submit" name="register_event" value="<?php echo $event->ID; ?>" class="btn btn-hjem-primary">
                                                Meld meg på
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                
                <div class="card-hjem text-center py-12">
                    <div class="text-6xl mb-4">📅</div>
                    <h3 class="text-xl font-bold text-bim-black-900 mb-2">Ingen arrangementer kommende</h3>
                    <p class="text-bim-black-700">Nye arrangementer annonseres på BIM Verdi siden</p>
                    <a href="<?php echo home_url('/arrangementer/'); ?>" class="btn btn-hjem-primary mt-4">
                        Se alle arrangementer
                    </a>
                </div>

                <?php endif; ?>

            </main>
        </div>
    </div>
</div>

<?php get_footer(); ?>
