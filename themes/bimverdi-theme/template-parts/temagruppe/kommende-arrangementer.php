<?php
/**
 * Temagruppe Kommende Arrangementer
 *
 * Lists upcoming events (max 3) associated with this theme group.
 * Shows fallback message if no events are scheduled.
 *
 * @package BimVerdi_Theme
 *
 * @param array $args {
 *     @type WP_Term|false $temagruppe_term The taxonomy term for this temagruppe
 * }
 */

if (!defined('ABSPATH')) exit;

$temagruppe_term = $args['temagruppe_term'] ?? null;

// Query upcoming events
$events = [];
if ($temagruppe_term) {
    $today = date('Y-m-d');
    $event_query = new WP_Query([
        'post_type' => 'arrangement',
        'posts_per_page' => 3,
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
?>

<section class="pb-6">
    <h3 class="text-sm font-semibold text-[#5A5A5A] uppercase tracking-wide mb-4">
        Kommende arrangementer
    </h3>

    <?php if (!empty($events)) : ?>
    <ul class="space-y-3">
        <?php foreach ($events as $event) :
            $event_date = get_field('dato', $event->ID);
            $formatted_date = '';
            if ($event_date) {
                $date_obj = DateTime::createFromFormat('Ymd', $event_date);
                if ($date_obj) {
                    // Norwegian date format: "15. jan"
                    $months_no = [
                        'jan', 'feb', 'mar', 'apr', 'mai', 'jun',
                        'jul', 'aug', 'sep', 'okt', 'nov', 'des'
                    ];
                    $day = $date_obj->format('j');
                    $month_idx = (int)$date_obj->format('n') - 1;
                    $formatted_date = $day . '. ' . $months_no[$month_idx];
                }
            }
        ?>
        <li class="flex items-start gap-3">
            <!-- Date badge -->
            <div class="flex-shrink-0 w-14 text-center py-1 px-2 bg-gray-100 rounded text-xs font-medium text-[#5A5A5A]">
                <?php echo esc_html($formatted_date ?: 'TBA'); ?>
            </div>

            <!-- Event title -->
            <a
                href="<?php echo esc_url(get_permalink($event->ID)); ?>"
                class="text-sm text-[#1A1A1A] hover:text-[#FF8B5E] hover:underline leading-snug"
            >
                <?php echo esc_html($event->post_title); ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <!-- View all link -->
    <div class="mt-4">
        <a
            href="<?php echo esc_url(home_url('/arrangement/')); ?>"
            class="text-sm text-[#FF8B5E] hover:underline inline-flex items-center gap-1"
        >
            Se alle arrangementer
            <?php echo bimverdi_icon('arrow-right', 14); ?>
        </a>
    </div>

    <?php else : ?>
    <p class="text-sm text-[#5A5A5A] italic">
        Ingen planlagte moter for oyeblikket.
    </p>
    <?php endif; ?>
</section>
