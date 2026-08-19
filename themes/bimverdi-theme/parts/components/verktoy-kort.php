<?php
/**
 * Verktøy-kort — én kilde til markup for begge visningene i verktøykatalogen.
 *
 * Trukket ut av `archive-verktoy.php` 19.08.2026 fordi markupen nå rendres fra to steder:
 * arkivmalen (første side) og AJAX-endepunktet (filtrering + «Vis flere»). Skal de to noen
 * gang divergere, ser brukeren kort som endrer utseende når de blar — derfor én fil.
 *
 * @param array $args {
 *     item => array fra bv_verktoy_katalog_item()
 *     view => 'grid' | 'list'
 * }
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$item = isset($args['item']) && is_array($args['item']) ? $args['item'] : null;
$view = (isset($args['view']) && $args['view'] === 'list') ? 'list' : 'grid';

if (!$item) {
    return;
}

$type_options = function_exists('bv_verktoy_type_options') ? bv_verktoy_type_options() : array();

// Type-badge: første treff, med ACF-etiketten som visningsnavn.
$type_badge = '';
if (!empty($item['type_tags'])) {
    $first      = $item['type_tags'][0];
    $type_badge = isset($type_options[$first]) ? $type_options[$first] : $first;
}

// Initialer som logo-fallback (synkede verktøy har ingen logo).
$words     = preg_split('/\s+/u', trim((string) $item['title']));
$initials  = (is_array($words) && count($words) >= 2)
    ? mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1))
    : mb_strtoupper(mb_substr((string) $item['title'], 0, 2));

if ($view === 'grid') : ?>

<div class="verktoy-card bg-white border border-[#E7E5E4] rounded-xl shadow-sm hover:shadow-md hover:border-[#D6D3D1] transition-all p-6 flex flex-col justify-between h-[285px]">
    <div>
        <div class="flex items-start justify-between mb-6">
            <div class="w-14 h-14 rounded-md bg-[#F5F5F4] flex items-center justify-center overflow-hidden flex-shrink-0 p-2">
                <?php if ($item['logo_url']) : ?>
                    <img src="<?php echo esc_url($item['logo_url']); ?>" alt="<?php echo esc_attr($item['title']); ?>" referrerpolicy="no-referrer" loading="lazy" class="w-full h-full object-contain">
                <?php else : ?>
                    <span class="text-base font-bold text-[#111827] tracking-tight"><?php echo esc_html($initials); ?></span>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-2">
                <?php if (!empty($item['is_ai']) && function_exists('bv_aec_ai_badge_markup')) { echo bv_aec_ai_badge_markup(); } ?>
                <?php if ($type_badge) : ?>
                    <span class="inline-flex items-center text-xs font-medium text-[#57534E] bg-[#F5F5F4] px-2.5 py-0.5 rounded-full"><?php echo esc_html($type_badge); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <h2 class="text-xl font-bold text-[#111827] mb-2 leading-tight tracking-tight line-clamp-2">
            <?php echo esc_html($item['title']); ?>
        </h2>

        <?php if (!empty($item['eier_name'])) : ?>
            <div class="flex items-center gap-1 text-sm text-[#57534E]">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0" aria-hidden="true"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                <span><?php echo esc_html($item['eier_name']); ?></span>
            </div>
        <?php elseif (!empty($item['is_synced']) && function_exists('bv_aec_attribution_html')) : ?>
            <div class="text-sm text-[#57534E] truncate"><?php echo bv_aec_attribution_html('compact'); ?></div>
        <?php endif; ?>
    </div>

    <div class="flex items-center justify-between pt-4 border-t border-[#E7E5E4]">
        <?php if (!empty($item['tg_names'])) : ?>
            <span class="text-xs font-medium text-[#57534E] uppercase tracking-wider"><?php echo esc_html($item['tg_names'][0]); ?></span>
        <?php else : ?>
            <span></span>
        <?php endif; ?>

        <a href="<?php echo esc_url($item['permalink']); ?>" class="inline-flex items-center gap-1 text-sm font-bold text-[#111827] hover:opacity-70 transition-opacity">
            Se detaljer
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        </a>
    </div>
</div>

<?php else : ?>

<tr class="verktoy-card hover:bg-[#FAFAF9] transition-colors">
    <td class="px-4 py-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-md bg-[#F5F5F4] flex items-center justify-center overflow-hidden flex-shrink-0 p-1.5">
                <?php if ($item['logo_url']) : ?>
                    <img src="<?php echo esc_url($item['logo_url']); ?>" alt="<?php echo esc_attr($item['title']); ?>" referrerpolicy="no-referrer" loading="lazy" class="w-full h-full object-contain">
                <?php else : ?>
                    <span class="text-xs font-bold text-[#111827]"><?php echo esc_html($initials); ?></span>
                <?php endif; ?>
            </div>
            <span class="font-medium text-[#111827]"><?php echo esc_html($item['title']); ?></span>
            <?php if (!empty($item['is_ai']) && function_exists('bv_aec_ai_badge_markup')) { echo bv_aec_ai_badge_markup(); } ?>
        </div>
    </td>
    <td class="px-4 py-3 text-[#57534E]"><?php
        if (!empty($item['eier_name'])) {
            echo esc_html($item['eier_name']);
        } elseif (!empty($item['is_synced']) && function_exists('bv_aec_attribution_html')) {
            echo bv_aec_attribution_html('compact');
        }
    ?></td>
    <td class="px-4 py-3">
        <?php if ($type_badge) : ?>
            <span class="text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-2 py-0.5 rounded-full"><?php echo esc_html($type_badge); ?></span>
        <?php endif; ?>
    </td>
    <td class="px-4 py-3">
        <div class="flex flex-wrap gap-1">
            <?php foreach ($item['tg_names'] as $tag) : ?>
                <span class="text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-2 py-0.5 rounded"><?php echo esc_html($tag); ?></span>
            <?php endforeach; ?>
        </div>
    </td>
    <td class="px-4 py-3">
        <a href="<?php echo esc_url($item['permalink']); ?>" class="text-[#111827] hover:text-[#57534E] transition-colors" title="Se detaljer">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        </a>
    </td>
</tr>

<?php endif; ?>
