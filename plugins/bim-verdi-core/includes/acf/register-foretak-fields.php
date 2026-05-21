<?php
/**
 * ACF Foretak Fields
 *
 * Registers the bv_hoveddomene field group on the foretak CPT.
 *
 * Per krav 20 (v3) / B-027: hoveddomene caches automatisk fra hovedkontaktens
 * e-post. Feltet er admin-synlig men readonly for å markere at det styres
 * automatisk. Manuell endring tillates (for spesialtilfeller / admin override).
 *
 * @package BIM_Verdi_Core
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('acf_add_local_field_group')) {
    return;
}

add_action('acf/init', function () {

    acf_add_local_field_group([
        'key' => 'group_bim_verdi_foretak_domain',
        'title' => 'BIM Verdi - Domene',
        'fields' => [
            [
                'key' => 'field_foretak_hoveddomene',
                'label' => 'Hoveddomene',
                'name' => 'bv_hoveddomene',
                'type' => 'text',
                'instructions' => 'Caches automatisk fra hovedkontaktens e-postdomene (PSL-strippet). Brukes for automatisk Tilleggskontakt-matching. Endre kun manuelt for spesialtilfeller.',
                'required' => 0,
                'wrapper' => [
                    'width' => '50',
                ],
                'placeholder' => 'f.eks. firma.no',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => defined('BV_CPT_COMPANY') ? BV_CPT_COMPANY : 'foretak',
                ],
            ],
        ],
        'menu_order' => 5,
        'position' => 'side',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
    ]);
});
