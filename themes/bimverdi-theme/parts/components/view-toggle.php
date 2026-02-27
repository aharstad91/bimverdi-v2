<?php
/**
 * View Toggle Component (Grid/List)
 *
 * Renders two icon buttons to switch between grid and list views.
 * Hidden on mobile — cards work best on small screens.
 *
 * Usage:
 *   bimverdi_view_toggle([
 *       'storage_key' => 'bv-view-kunnskapskilde',
 *       'grid_id'     => 'kunnskapskilde-grid',
 *       'list_id'     => 'kunnskapskilde-list',
 *   ]);
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

function bimverdi_view_toggle($args = []) {
    $defaults = [
        'storage_key' => 'bv-view-default',
        'grid_id'     => 'grid-view',
        'list_id'     => 'list-view',
    ];
    $args = wp_parse_args($args, $defaults);
    ?>
    <div class="bv-view-toggle"
         data-storage-key="<?php echo esc_attr($args['storage_key']); ?>"
         data-grid-id="<?php echo esc_attr($args['grid_id']); ?>"
         data-list-id="<?php echo esc_attr($args['list_id']); ?>"
         role="group"
         aria-label="Velg visning">
        <button type="button"
                class="bv-view-toggle__btn bv-view-toggle__btn--grid"
                aria-label="Rutenettvisning"
                aria-pressed="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        </button>
        <button type="button"
                class="bv-view-toggle__btn bv-view-toggle__btn--list"
                aria-label="Listevisning"
                aria-pressed="false">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        </button>
    </div>
    <?php
}
