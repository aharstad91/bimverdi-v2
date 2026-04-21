<?php
/**
 * Arrangement Card Component
 *
 * Grid-friendly card for upcoming arrangements, with featured image header.
 * Used on archive-arrangement.php and anywhere else we list arrangements.
 *
 * Usage:
 *   get_template_part('parts/components/arrangement-card', null, [
 *       'post' => $post,              // WP_Post | int
 *   ]);
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$post = isset($args['post']) ? get_post($args['post']) : null;
if (!$post) return;

$event_id         = $post->ID;
$permalink        = get_permalink($post);
$dato             = get_field('arrangement_dato', $event_id);
$tid_start        = get_field('tidspunkt_start', $event_id);
$tid_slutt        = get_field('tidspunkt_slutt', $event_id);
$arrangement_type = get_field('arrangement_type', $event_id) ?: 'digitalt';
$sted_by          = get_field('sted_by', $event_id);

$temagrupper      = wp_get_post_terms($event_id, 'temagruppe', ['fields' => 'all']);
$first_temagruppe = (!is_wp_error($temagrupper) && !empty($temagrupper)) ? $temagrupper[0] : null;
$tg_name          = $first_temagruppe ? $first_temagruppe->name : '';
$tg_color         = $first_temagruppe ? (get_field('temagruppe_farge', $first_temagruppe) ?: '#FF8B5E') : '#E7E5E4';

$time_str = $tid_start;
if ($tid_slutt) $time_str .= ' – ' . $tid_slutt;

$has_image = has_post_thumbnail($event_id);
?>

<a href="<?php echo esc_url($permalink); ?>"
   class="group flex flex-col bg-white border border-[#E7E5E4] rounded-xl shadow-sm hover:shadow-md hover:border-[#D6D3D1] transition-all overflow-hidden h-full">

    <!-- Visual: featured image or temagruppe-color fallback -->
    <div class="relative aspect-[16/9] bg-[#F5F5F4] overflow-hidden">
        <?php if ($has_image): ?>
            <?php echo get_the_post_thumbnail($event_id, 'arrangement_card', [
                'loading' => 'lazy',
                'class'   => 'w-full h-full object-cover group-hover:scale-[1.02] transition-transform duration-300',
                'alt'     => esc_attr(get_the_title($event_id)),
            ]); ?>
        <?php else: ?>
            <div class="absolute inset-0 flex items-center justify-center"
                 style="background: color-mix(in srgb, <?php echo esc_attr($tg_color); ?> 15%, #F5F5F4);">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                     stroke="<?php echo esc_attr($tg_color); ?>" stroke-width="1.5" class="opacity-50">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
        <?php endif; ?>

        <!-- Date badge over visual -->
        <?php if ($dato): ?>
            <div class="absolute top-3 left-3 w-12 h-12 rounded-lg bg-white/95 backdrop-blur flex flex-col items-center justify-center shadow-sm">
                <span class="text-lg font-bold text-[#FF8B5E] leading-none"><?php echo esc_html(date('d', strtotime($dato))); ?></span>
                <span class="text-[10px] uppercase text-[#57534E] leading-none mt-0.5"><?php echo esc_html(wp_date('M', strtotime($dato))); ?></span>
            </div>
        <?php endif; ?>

        <!-- Type badge over visual -->
        <span class="absolute top-3 right-3 inline-flex items-center gap-1.5 text-xs font-medium text-[#111827] bg-white/95 backdrop-blur px-2.5 py-1 rounded shadow-sm">
            <?php echo bimverdi_get_type_icon($arrangement_type); ?>
            <?php echo esc_html(bimverdi_get_type_label($arrangement_type)); ?>
        </span>
    </div>

    <!-- Content -->
    <div class="flex flex-col justify-between flex-1 p-5">
        <div>
            <h3 class="text-lg font-bold text-[#111827] mb-2 leading-tight tracking-tight line-clamp-2">
                <?php echo esc_html(get_the_title($event_id)); ?>
                <?php if (function_exists('bimverdi_admin_id_badge')) echo bimverdi_admin_id_badge($event_id); ?>
            </h3>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-[#57534E]">
                <?php if ($time_str): ?>
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span><?php echo esc_html($time_str); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($sted_by): ?>
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span><?php echo esc_html($sted_by); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer: temagruppe + link -->
        <div class="flex items-center justify-between pt-4 mt-4 border-t border-[#E7E5E4]">
            <?php if ($tg_name): ?>
                <span class="text-xs font-medium text-[#57534E] uppercase tracking-wider truncate max-w-[150px]">
                    <?php echo esc_html($tg_name); ?>
                </span>
            <?php else: ?>
                <span></span>
            <?php endif; ?>

            <span class="inline-flex items-center gap-1 text-sm font-bold text-[#111827] group-hover:opacity-70 transition-opacity">
                Se arrangement
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </span>
        </div>
    </div>
</a>
