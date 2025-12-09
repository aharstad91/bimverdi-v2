<?php
/**
 * Archive Arrangement Template
 * 
 * Displays all events - upcoming in grid, past in list format
 * Uses Web Awesome components for modern UI
 * 
 * @package BimVerdi_Theme
 */

get_header();

// Get filters from URL
$type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$temagruppe_filter = isset($_GET['temagruppe']) ? sanitize_text_field($_GET['temagruppe']) : '';

// Build tax query if filters active
$tax_query = array();
if ($type_filter) {
    $tax_query[] = array(
        'taxonomy' => 'arrangementstype',
        'field' => 'slug',
        'terms' => $type_filter,
    );
}
if ($temagruppe_filter) {
    $tax_query[] = array(
        'taxonomy' => 'temagruppe',
        'field' => 'slug',
        'terms' => $temagruppe_filter,
    );
}

// Query upcoming events
$upcoming_args = array(
    'post_type' => 'arrangement',
    'posts_per_page' => -1,
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key' => 'arrangement_dato',
            'value' => date('Y-m-d'),
            'compare' => '>=',
            'type' => 'DATE'
        ),
        array(
            'key' => 'arrangement_status',
            'value' => 'planlagt',
        ),
    ),
    'meta_key' => 'arrangement_dato',
    'orderby' => 'meta_value',
    'order' => 'ASC',
);
if (!empty($tax_query)) {
    $upcoming_args['tax_query'] = $tax_query;
}
$upcoming_events = new WP_Query($upcoming_args);

// Query past events
$past_args = array(
    'post_type' => 'arrangement',
    'posts_per_page' => 20,
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => 'arrangement_dato',
            'value' => date('Y-m-d'),
            'compare' => '<',
            'type' => 'DATE'
        ),
        array(
            'key' => 'arrangement_status',
            'value' => 'avholdt',
        ),
    ),
    'meta_key' => 'arrangement_dato',
    'orderby' => 'meta_value',
    'order' => 'DESC',
);
if (!empty($tax_query)) {
    $past_args['tax_query'] = $tax_query;
}
$past_events = new WP_Query($past_args);

// Get all terms for filters
$arrangementstyper = get_terms(array(
    'taxonomy' => 'arrangementstype',
    'hide_empty' => false,
));

$temagrupper = get_terms(array(
    'taxonomy' => 'temagruppe',
    'hide_empty' => false,
));

// Get filter names for display
$active_filters = array();
if ($type_filter) {
    $type_term = get_term_by('slug', $type_filter, 'arrangementstype');
    if ($type_term) {
        $active_filters[] = $type_term->name;
    }
}
if ($temagruppe_filter) {
    $temagruppe_term = get_term_by('slug', $temagruppe_filter, 'temagruppe');
    if ($temagruppe_term) {
        $active_filters[] = $temagruppe_term->name;
    }
}
$has_filters = !empty($active_filters);

// Helper functions
function bimverdi_format_badge_icon($format) {
    $icons = [
        'fysisk' => 'location-dot',
        'digitalt' => 'video',
        'hybrid' => 'laptop-house',
    ];
    return $icons[$format] ?? 'calendar';
}

function bimverdi_format_short_label($format) {
    $labels = [
        'fysisk' => 'Fysisk',
        'digitalt' => 'Digitalt',
        'hybrid' => 'Hybrid',
    ];
    return $labels[$format] ?? $format;
}

?>

<div class="min-h-screen bg-gradient-to-b from-bim-beige-50 to-white py-8">
    <div class="container mx-auto px-4 max-w-6xl">
        
        <?php // Show unregistration success message ?>
        <?php if (isset($_GET['avmelding_success']) && $_GET['avmelding_success'] == '1'): ?>
            <div class="mb-6">
                <wa-alert variant="success" open closable>
                    <wa-icon slot="icon" name="circle-check" library="fa"></wa-icon>
                    <strong>Du er nå avmeldt!</strong> Du kan melde deg på igjen når som helst.
                </wa-alert>
            </div>
        <?php endif; ?>
        
        <!-- Hero Section -->
        <div class="text-center mb-10">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Arrangementer</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Kurs, seminarer, workshops og nettverksmøter i BIM Verdi. 
                Meld deg på og få faglig påfyll sammen med andre i nettverket.
            </p>
        </div>

        <!-- Filter Section -->
        <div class="mb-8">
            <wa-card>
                <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                    
                    <!-- Stats -->
                    <div class="flex items-center gap-4 text-sm text-gray-600">
                        <span class="flex items-center gap-2">
                            <wa-icon library="fa" name="solid/calendar-plus" class="text-bim-orange"></wa-icon>
                            <strong><?php echo $upcoming_events->found_posts; ?></strong> kommende
                        </span>
                        <span class="flex items-center gap-2">
                            <wa-icon library="fa" name="solid/clock-rotate-left" class="text-gray-400"></wa-icon>
                            <strong><?php echo $past_events->found_posts; ?></strong> tidligere
                        </span>
                    </div>
                    
                    <!-- Dropdown Filters -->
                    <div class="flex flex-wrap gap-3 items-center">
                        <?php if ($has_filters): ?>
                            <a href="<?php echo remove_query_arg(array('type', 'temagruppe')); ?>" 
                               class="text-sm text-red-600 hover:text-red-700 flex items-center gap-1">
                                <wa-icon library="fa" name="solid/xmark"></wa-icon>
                                Nullstill filter
                            </a>
                        <?php endif; ?>
                        
                        <select class="rounded-lg border-gray-300 text-sm" onchange="window.location.href=this.value;">
                            <option value="<?php echo remove_query_arg('type'); ?>">Alle typer</option>
                            <?php foreach ($arrangementstyper as $type): ?>
                                <option value="<?php echo add_query_arg('type', $type->slug); ?>" <?php selected($type_filter, $type->slug); ?>>
                                    <?php echo esc_html($type->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select class="rounded-lg border-gray-300 text-sm" onchange="window.location.href=this.value;">
                            <option value="<?php echo remove_query_arg('temagruppe'); ?>">Alle temagrupper</option>
                            <?php foreach ($temagrupper as $gruppe): ?>
                                <option value="<?php echo add_query_arg('temagruppe', $gruppe->slug); ?>" <?php selected($temagruppe_filter, $gruppe->slug); ?>>
                                    <?php echo esc_html($gruppe->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                </div>
            </wa-card>
        </div>

        <!-- ============================================ -->
        <!-- KOMMENDE ARRANGEMENTER (GRID) -->
        <!-- ============================================ -->
        <section class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                <wa-icon library="fa" name="solid/calendar-plus" class="text-bim-orange"></wa-icon>
                Kommende arrangementer
                <?php if ($upcoming_events->have_posts()): ?>
                    <wa-badge variant="brand"><?php echo $upcoming_events->found_posts; ?></wa-badge>
                <?php endif; ?>
            </h2>
            
            <?php if ($upcoming_events->have_posts()): ?>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while ($upcoming_events->have_posts()): $upcoming_events->the_post(); 
                        $event_id = get_the_ID();
                        $dato = get_field('arrangement_dato');
                        $tid_start = get_field('tidspunkt_start');
                        $tid_slutt = get_field('tidspunkt_slutt');
                        $format = get_field('arrangement_format');
                        $fysisk_adresse = get_field('fysisk_adresse');
                        $maks_deltakere = get_field('maks_deltakere');
                        $status = get_field('arrangement_status');
                        $featured_image = get_the_post_thumbnail_url($event_id, 'medium');
                        
                        // Count registrations
                        $registrations = get_posts(array(
                            'post_type' => 'pamelding',
                            'posts_per_page' => -1,
                            'fields' => 'ids',
                            'meta_query' => array(
                                array('key' => 'pamelding_arrangement', 'value' => $event_id),
                                array('key' => 'pamelding_status', 'value' => 'aktiv'),
                            ),
                        ));
                        $antall_pameldte = count($registrations);
                        $er_fullt = $maks_deltakere && $antall_pameldte >= $maks_deltakere;
                        
                        // Check if user is registered
                        $bruker_er_pameldt = false;
                        if (is_user_logged_in()) {
                            $current_user_id = get_current_user_id();
                            foreach ($registrations as $reg_id) {
                                if (get_field('pamelding_bruker', $reg_id) == $current_user_id) {
                                    $bruker_er_pameldt = true;
                                    break;
                                }
                            }
                        }
                        
                        // Get terms
                        $event_types = wp_get_post_terms($event_id, 'arrangementstype');
                        $event_temagrupper = wp_get_post_terms($event_id, 'temagruppe');
                        
                        // Time formatting
                        $time_str = $tid_start;
                        if ($tid_slutt) $time_str .= ' – ' . $tid_slutt;
                    ?>
                    
                    <wa-card class="overflow-hidden hover:shadow-lg transition-shadow">
                        <!-- Card Image -->
                        <div class="relative h-40 bg-gradient-to-br from-bim-orange-100 to-bim-purple-100">
                            <?php if ($featured_image): ?>
                                <img src="<?php echo esc_url($featured_image); ?>" alt="" class="w-full h-full object-cover">
                            <?php endif; ?>
                            
                            <!-- Date badge -->
                            <div class="absolute top-3 left-3 bg-white rounded-lg shadow px-3 py-2 text-center">
                                <div class="text-xl font-bold text-bim-orange leading-none"><?php echo date('d', strtotime($dato)); ?></div>
                                <div class="text-xs uppercase text-gray-500"><?php echo date_i18n('M', strtotime($dato)); ?></div>
                            </div>
                            
                            <!-- Status badges -->
                            <div class="absolute top-3 right-3 flex flex-col gap-1">
                                <?php if ($status === 'avlyst'): ?>
                                    <wa-badge variant="danger">Avlyst</wa-badge>
                                <?php elseif ($er_fullt): ?>
                                    <wa-badge variant="warning">Fulltegnet</wa-badge>
                                <?php endif; ?>
                                
                                <wa-badge variant="neutral">
                                    <wa-icon name="<?php echo bimverdi_format_badge_icon($format); ?>" library="fa" style="margin-right: 0.25rem; font-size: 0.75rem;"></wa-icon>
                                    <?php echo bimverdi_format_short_label($format); ?>
                                </wa-badge>
                            </div>
                        </div>
                        
                        <!-- Card Content -->
                        <div class="p-4">
                            <!-- Tags -->
                            <div class="flex flex-wrap gap-1 mb-2">
                                <?php foreach ($event_temagrupper as $gruppe): ?>
                                    <span class="text-xs bg-bim-orange-100 text-bim-orange-700 px-2 py-0.5 rounded">
                                        <?php echo esc_html($gruppe->name); ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php foreach ($event_types as $type): ?>
                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                                        <?php echo esc_html($type->name); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Title -->
                            <h3 class="font-bold text-gray-900 mb-2 line-clamp-2">
                                <a href="<?php the_permalink(); ?>" class="hover:text-bim-orange transition-colors">
                                    <?php the_title(); ?>
                                </a>
                            </h3>
                            
                            <!-- Meta -->
                            <div class="text-sm text-gray-500 space-y-1">
                                <div class="flex items-center gap-2">
                                    <wa-icon name="clock" library="fa" style="font-size: 0.75rem;"></wa-icon>
                                    <?php echo esc_html($time_str); ?>
                                </div>
                                
                                <?php if (($format === 'fysisk' || $format === 'hybrid') && $fysisk_adresse): ?>
                                    <div class="flex items-center gap-2">
                                        <wa-icon name="location-dot" library="fa" style="font-size: 0.75rem;"></wa-icon>
                                        <span class="truncate"><?php echo esc_html($fysisk_adresse); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Card Footer -->
                        <div slot="footer" class="flex justify-between items-center">
                            <a href="<?php the_permalink(); ?>" class="text-bim-orange hover:text-bim-orange-700 font-medium text-sm flex items-center gap-1">
                                Les mer
                                <wa-icon name="arrow-right" library="fa" style="font-size: 0.75rem;"></wa-icon>
                            </a>
                            
                            <?php if ($status !== 'avlyst'): ?>
                                <?php if ($bruker_er_pameldt): ?>
                                    <wa-badge variant="success">
                                        <wa-icon name="check" library="fa" style="margin-right: 0.25rem;"></wa-icon>
                                        Påmeldt
                                    </wa-badge>
                                <?php elseif (!$er_fullt): ?>
                                    <wa-button variant="brand" size="small" href="<?php the_permalink(); ?>#pamelding-skjema">
                                        Meld på
                                    </wa-button>
                                <?php else: ?>
                                    <wa-badge variant="warning">Fulltegnet</wa-badge>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </wa-card>
                    
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            <?php else: ?>
                <!-- Empty state for upcoming -->
                <wa-card class="text-center py-12">
                    <wa-icon name="calendar-xmark" library="fa" style="font-size: 4rem; color: var(--wa-color-gray-300);"></wa-icon>
                    <h3 class="text-xl font-bold text-gray-700 mt-4 mb-2">
                        <?php if ($has_filters): ?>
                            Ingen kommende arrangementer funnet
                        <?php else: ?>
                            Ingen kommende arrangementer
                        <?php endif; ?>
                    </h3>
                    <p class="text-gray-500 mb-4">
                        <?php if ($has_filters): ?>
                            Ingen kommende arrangementer matcher filteret: <strong><?php echo esc_html(implode(' + ', $active_filters)); ?></strong>
                        <?php else: ?>
                            Det er ikke planlagt noen arrangementer for øyeblikket. Sjekk tilbake snart!
                        <?php endif; ?>
                    </p>
                    <?php if ($has_filters): ?>
                        <a href="<?php echo remove_query_arg(array('type', 'temagruppe')); ?>">
                            <wa-button variant="neutral" outline>
                                <wa-icon library="fa" name="solid/xmark" slot="prefix"></wa-icon>
                                Nullstill filter
                            </wa-button>
                        </a>
                    <?php endif; ?>
                </wa-card>
            <?php endif; ?>
        </section>

        <!-- ============================================ -->
        <!-- TIDLIGERE ARRANGEMENTER (LISTE) -->
        <!-- ============================================ -->
        <?php if ($past_events->have_posts()): ?>
        <section>
            <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center gap-3">
                <wa-icon library="fa" name="solid/clock-rotate-left" class="text-gray-400"></wa-icon>
                Tidligere arrangementer
                <wa-badge variant="neutral"><?php echo $past_events->found_posts; ?></wa-badge>
            </h2>
            
            <div class="space-y-3">
                <?php while ($past_events->have_posts()): $past_events->the_post(); 
                    $event_id = get_the_ID();
                    $dato = get_field('arrangement_dato');
                    $tid_start = get_field('tidspunkt_start');
                    $format = get_field('arrangement_format');
                    $fysisk_adresse = get_field('fysisk_adresse');
                    
                    // Get terms
                    $event_types = wp_get_post_terms($event_id, 'arrangementstype');
                    $event_temagrupper = wp_get_post_terms($event_id, 'temagruppe');
                ?>
                
                <wa-card class="overflow-hidden opacity-75 hover:opacity-100 transition-opacity">
                    <div class="p-4">
                        <div class="flex flex-col md:flex-row md:items-center gap-4">
                            
                            <!-- Dato -->
                            <div class="flex-shrink-0 text-center text-gray-400 w-14">
                                <div class="text-lg font-semibold"><?php echo date('d', strtotime($dato)); ?></div>
                                <div class="text-xs uppercase"><?php echo date_i18n('M Y', strtotime($dato)); ?></div>
                            </div>
                            
                            <!-- Tittel og info -->
                            <div class="flex-1 min-w-0">
                                <a href="<?php the_permalink(); ?>" class="font-semibold text-gray-700 hover:text-bim-orange transition-colors block truncate">
                                    <?php the_title(); ?>
                                </a>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500 mt-1">
                                    <span class="flex items-center gap-1">
                                        <wa-icon library="fa" name="regular/clock" style="font-size: 0.7rem;"></wa-icon>
                                        <?php echo esc_html($tid_start); ?>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <wa-icon library="fa" name="<?php echo bimverdi_format_badge_icon($format); ?>" style="font-size: 0.7rem;"></wa-icon>
                                        <?php echo bimverdi_format_short_label($format); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Tags -->
                            <div class="flex flex-wrap gap-1">
                                <?php foreach ($event_temagrupper as $gruppe): ?>
                                    <wa-tag variant="neutral" size="small"><?php echo esc_html($gruppe->name); ?></wa-tag>
                                <?php endforeach; ?>
                                <?php foreach ($event_types as $type): ?>
                                    <wa-tag variant="neutral" size="small"><?php echo esc_html($type->name); ?></wa-tag>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Status -->
                            <div class="flex-shrink-0">
                                <wa-tag variant="neutral" size="small">
                                    <wa-icon library="fa" name="solid/check" slot="prefix"></wa-icon>
                                    Avholdt
                                </wa-tag>
                            </div>
                            
                            <!-- Link -->
                            <div class="flex-shrink-0">
                                <a href="<?php the_permalink(); ?>">
                                    <wa-button variant="neutral" size="small" outline>
                                        <wa-icon library="fa" name="solid/arrow-right" slot="prefix"></wa-icon>
                                        Se
                                    </wa-button>
                                </a>
                            </div>
                            
                        </div>
                    </div>
                </wa-card>
                
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
            
            <?php if ($past_events->found_posts > 20): ?>
                <div class="text-center mt-6">
                    <p class="text-sm text-gray-500">Viser de 20 siste av <?php echo $past_events->found_posts; ?> tidligere arrangementer</p>
                </div>
            <?php endif; ?>
        </section>
        <?php elseif ($has_filters): ?>
            <!-- Empty state for past with filters -->
            <section>
                <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center gap-3">
                    <wa-icon library="fa" name="solid/clock-rotate-left" class="text-gray-400"></wa-icon>
                    Tidligere arrangementer
                </h2>
                <wa-card class="text-center py-8">
                    <p class="text-gray-500">
                        Ingen tidligere arrangementer matcher filteret: <strong><?php echo esc_html(implode(' + ', $active_filters)); ?></strong>
                    </p>
                </wa-card>
            </section>
        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>
