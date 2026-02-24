<?php
/**
 * Temagruppe Arrangementer Grid
 *
 * Full-width grid of upcoming events associated with this theme group.
 * Dashboard-style card layout for the theme group page.
 *
 * @package BimVerdi_Theme
 *
 * @param array $args {
 *     @type WP_Term|false $temagruppe_term The taxonomy term for this temagruppe
 *     @type int           $event_count     Total upcoming events count
 * }
 */

if (!defined('ABSPATH')) exit;

$temagruppe_term = $args['temagruppe_term'] ?? null;
$total_event_count = $args['event_count'] ?? 0;
$max_visible = 6;

// Query upcoming events
$events = [];
if ($temagruppe_term) {
    $today = date('Y-m-d');
    $event_query = new WP_Query([
        'post_type' => 'arrangement',
        'posts_per_page' => $max_visible,
        'meta_key' => 'dato',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_query' => [
            [
                'key' => 'dato',
                'value' => $today,
                'compare' => '>=',
                'type' => 'DATE',
            ],
        ],
        'tax_query' => [
            [
                'taxonomy' => 'temagruppe',
                'field' => 'term_id',
                'terms' => $temagruppe_term->term_id,
            ],
        ],
    ]);

    if ($event_query->have_posts()) {
        $events = $event_query->posts;
    }
    wp_reset_postdata();
}

// Norwegian months
$months_no = [
    'januar', 'februar', 'mars', 'april', 'mai', 'juni',
    'juli', 'august', 'september', 'oktober', 'november', 'desember'
];
?>

<section>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-[#1A1A1A]">
            Kommende arrangementer
            <?php if ($total_event_count > 0) : ?>
            <span class="text-base font-normal text-[#5A5A5A]">(<?php echo esc_html($total_event_count); ?>)</span>
            <?php endif; ?>
        </h2>
    </div>

    <?php if (!empty($events)) : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($events as $event) :
            $event_id = $event->ID;
            $event_date = get_field('dato', $event_id);
            $event_time_start = get_field('tidspunkt_start', $event_id);
            $event_time_end = get_field('tidspunkt_slutt', $event_id);
            $location = get_field('sted', $event_id);
            $is_digital = get_field('er_digitalt', $event_id);
            $permalink = get_permalink($event_id);

            // Get event type
            $event_types = wp_get_post_terms($event_id, 'arrangementstype', ['fields' => 'names']);
            $event_type = !empty($event_types) ? $event_types[0] : '';

            // Format date
            $formatted_date = '';
            $day = '';
            $month = '';
            if ($event_date) {
                $date_obj = DateTime::createFromFormat('Ymd', $event_date);
                if ($date_obj) {
                    $day = $date_obj->format('j');
                    $month_idx = (int)$date_obj->format('n') - 1;
                    $month = $months_no[$month_idx];
                    $year = $date_obj->format('Y');
                    $formatted_date = $day . '. ' . $month . ' ' . $year;
                }
            }

            // Format time
            $time_str = '';
            if ($event_time_start) {
                $time_str = $event_time_start;
                if ($event_time_end) {
                    $time_str .= ' - ' . $event_time_end;
                }
            }
        ?>
        <article class="bg-white rounded-lg border border-[#E5E0D8] p-5 flex flex-col">
            <!-- Date and Type -->
            <div class="flex items-center gap-2 mb-3">
                <!-- Date Badge -->
                <div class="flex items-center gap-1.5 text-sm text-[#5A5A5A]">
                    <?php echo bimverdi_icon('calendar', 16); ?>
                    <span><?php echo esc_html($formatted_date ?: 'Dato ikke satt'); ?></span>
                </div>

                <?php if ($event_type) : ?>
                <span class="px-2 py-0.5 bg-gray-100 rounded text-xs font-medium text-[#5A5A5A]">
                    <?php echo esc_html($event_type); ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Title -->
            <h3 class="text-base font-semibold text-[#1A1A1A] mb-2 line-clamp-2">
                <?php echo esc_html($event->post_title); ?>
            </h3>

            <!-- Time and Location -->
            <div class="space-y-1 mb-4 flex-1">
                <?php if ($time_str) : ?>
                <p class="text-sm text-[#5A5A5A] flex items-center gap-1.5">
                    <?php echo bimverdi_icon('clock', 14); ?>
                    <?php echo esc_html($time_str); ?>
                </p>
                <?php endif; ?>

                <?php if ($is_digital) : ?>
                <p class="text-sm text-[#5A5A5A] flex items-center gap-1.5">
                    <?php echo bimverdi_icon('video', 14); ?>
                    Digitalt
                </p>
                <?php elseif ($location) : ?>
                <p class="text-sm text-[#5A5A5A] flex items-center gap-1.5">
                    <?php echo bimverdi_icon('map-pin', 14); ?>
                    <?php echo esc_html($location); ?>
                </p>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="pt-4 border-t border-[#E5E0D8]">
                <a
                    href="<?php echo esc_url($permalink); ?>"
                    class="text-sm font-medium text-[#5A5A5A] hover:text-[#FF8B5E] inline-flex items-center gap-1"
                >
                    Se detaljer
                    <?php echo bimverdi_icon('chevron-right', 14); ?>
                </a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- See all link -->
    <?php if ($total_event_count > $max_visible) : ?>
    <div class="mt-6 text-center">
        <a
            href="<?php echo esc_url(home_url('/arrangement/')); ?>"
            class="text-sm text-[#FF8B5E] hover:underline inline-flex items-center gap-1"
        >
            Se alle <?php echo esc_html($total_event_count); ?> arrangementer
            <?php echo bimverdi_icon('arrow-right', 14); ?>
        </a>
    </div>
    <?php endif; ?>

    <?php else : ?>
    <div class="text-center py-8">
        <p class="text-[#5A5A5A]">
            Ingen planlagte arrangementer for denne temagruppen enna.
        </p>
    </div>
    <?php endif; ?>
</section>
