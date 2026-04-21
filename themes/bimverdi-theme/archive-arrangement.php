<?php
/**
 * Archive Arrangement Template
 *
 * Displays events - upcoming in grid, past in list format
 * Design based on /deltakere/ archive
 *
 * @package BimVerdi_Theme
 */

get_header();

// Query upcoming events (based on toggle, not date)
$upcoming_args = array(
    'post_type' => 'arrangement',
    'posts_per_page' => -1,
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key' => 'arrangement_status_toggle',
            'value' => 'kommende',
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
$upcoming_events = new WP_Query($upcoming_args);

// Query past events (based on toggle)
$past_args = array(
    'post_type' => 'arrangement',
    'posts_per_page' => -1,
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => 'arrangement_status_toggle',
            'value' => 'tidligere',
        ),
        array(
            'key' => 'arrangement_status',
            'value' => 'avlyst',
        ),
    ),
    'meta_key' => 'arrangement_dato',
    'orderby' => 'meta_value',
    'order' => 'DESC',
);
$past_events = new WP_Query($past_args);

/**
 * Get arrangement type icon (Lucide)
 */
function bimverdi_get_type_icon($type) {
    $icons = array(
        'fysisk' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
        'digitalt' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><path d="m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5"/><rect x="2" y="6" width="14" height="12" rx="2"/></svg>',
        'hybrid' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>',
    );
    return $icons[$type] ?? $icons['digitalt'];
}

/**
 * Get arrangement type label
 */
function bimverdi_get_type_label($type) {
    $labels = array(
        'fysisk' => 'Fysisk',
        'digitalt' => 'Digitalt',
        'hybrid' => 'Hybrid',
    );
    return $labels[$type] ?? 'Digitalt';
}
?>

<div class="min-h-screen bg-white">

    <?php get_template_part('parts/components/archive-intro', null, [
        'acf_prefix'       => 'arrangement',
        'fallback_title'   => 'Arrangementer',
        'fallback_ingress' => 'Kurs, seminarer, workshops og nettverksmøter i BIM Verdi.',
    ]); ?>

    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- ============================================ -->
        <!-- KOMMENDE ARRANGEMENTER (GRID) -->
        <!-- ============================================ -->
        <section class="mb-16">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-[#111827] flex items-center gap-3">
                    Kommende arrangementer
                    <?php if ($upcoming_events->have_posts()): ?>
                        <span class="inline-flex items-center text-sm font-medium text-white bg-[#FF8B5E] px-2.5 py-0.5 rounded">
                            <?php echo $upcoming_events->found_posts; ?>
                        </span>
                    <?php endif; ?>
                </h2>
            </div>

            <?php if ($upcoming_events->have_posts()): ?>
            <?php update_post_thumbnail_cache($upcoming_events); // Avoid N+1 on has_post_thumbnail() inside the loop ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($upcoming_events->have_posts()): $upcoming_events->the_post(); ?>
                    <?php get_template_part('parts/components/arrangement-card', null, [
                        'post' => get_post(),
                    ]); ?>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>

            <?php else: ?>

            <!-- Empty state for upcoming -->
            <div class="py-16 text-center">
                <div class="w-16 h-16 bg-[#F5F5F4] rounded-lg flex items-center justify-center mx-auto mb-5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-[#A8A29E]"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <h2 class="text-lg font-semibold text-[#111827] mb-2">Ingen kommende arrangementer</h2>
                <p class="text-sm text-[#57534E]">Det er ikke planlagt noen arrangementer for øyeblikket. Sjekk tilbake snart!</p>
            </div>

            <?php endif; ?>
        </section>

        <!-- ============================================ -->
        <!-- TIDLIGERE ARRANGEMENTER (LISTE) -->
        <!-- ============================================ -->
        <?php if ($past_events->have_posts()): ?>
        <section>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-[#57534E] flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#A8A29E]"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                    Tidligere arrangementer
                    <span class="text-sm font-normal text-[#A8A29E]">(<?php echo $past_events->found_posts; ?>)</span>
                </h2>
            </div>

            <!-- Divider -->
            <div class="border-t border-[#E7E5E4] mb-4"></div>

            <div class="space-y-0 divide-y divide-[#D6D1C6]">
                <?php while ($past_events->have_posts()): $past_events->the_post();
                    $event_id = get_the_ID();
                    $dato = get_field('arrangement_dato');
                    $tid_start = get_field('tidspunkt_start');
                    $arrangement_type = get_field('arrangement_type') ?: 'digitalt';
                    $status = get_field('arrangement_status');
                    $opptak_url = get_field('opptak_url');
                    $dokumentasjon_url = get_field('dokumentasjon_url');

                    // Get temagrupper
                    $temagrupper = wp_get_post_terms($event_id, 'temagruppe', array('fields' => 'all'));
                ?>

                <div class="py-4 flex flex-col md:flex-row md:items-center gap-4">

                    <!-- Date -->
                    <div class="flex-shrink-0 text-center text-[#A8A29E] w-14">
                        <div class="text-lg font-semibold"><?php echo date('d', strtotime($dato)); ?></div>
                        <div class="text-xs uppercase"><?php echo wp_date('M Y', strtotime($dato)); ?></div>
                    </div>

                    <!-- Title and meta -->
                    <div class="flex-1 min-w-0">
                        <a href="<?php the_permalink(); ?>" class="font-semibold text-[#111827] hover:opacity-70 transition-opacity block truncate">
                            <?php the_title(); ?>
                        </a><?php echo bimverdi_admin_id_badge(); ?>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-[#57534E] mt-1">
                            <span class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <?php echo esc_html($tid_start); ?>
                            </span>
                            <span class="flex items-center gap-1">
                                <?php echo bimverdi_get_type_icon($arrangement_type); ?>
                                <?php echo bimverdi_get_type_label($arrangement_type); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Temagruppe tags -->
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach ($temagrupper as $gruppe): ?>
                            <a href="<?php echo get_term_link($gruppe); ?>" class="text-xs font-medium text-[#57534E] bg-[#F5F5F4] px-2 py-0.5 rounded hover:bg-[#E7E5E4] transition-colors">
                                <?php echo esc_html($gruppe->name); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Status or Actions -->
                    <div class="flex-shrink-0 flex items-center gap-2">
                        <?php if ($status === 'avlyst'): ?>
                            <span class="text-xs font-medium text-[#772015] bg-red-50 px-2 py-0.5 rounded">
                                Avlyst
                            </span>
                        <?php else: ?>
                            <?php if ($opptak_url): ?>
                                <a href="<?php echo esc_url($opptak_url); ?>" target="_blank" class="inline-flex items-center gap-1 text-xs font-medium text-[#FF8B5E] hover:opacity-70 transition-opacity">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                    Opptak
                                </a>
                            <?php endif; ?>
                            <?php if ($dokumentasjon_url): ?>
                                <a href="<?php echo esc_url($dokumentasjon_url); ?>" target="_blank" class="inline-flex items-center gap-1 text-xs font-medium text-[#57534E] hover:opacity-70 transition-opacity">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                    Dokumenter
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Link -->
                    <div class="flex-shrink-0">
                        <a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-1 text-sm font-bold text-[#111827] hover:opacity-70 transition-opacity">
                            Se
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </div>

                </div>

                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </section>
        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>
