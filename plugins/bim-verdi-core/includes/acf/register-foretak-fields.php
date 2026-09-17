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

    /**
     * Fylke på foretaket (Bård, Trello uke 38 / møte 17.09.2026).
     *
     * AVGJORT I MØTET: feltet fylles ut MANUELT av redaksjonen, det hentes
     * ikke automatisk. Bakgrunnen er at automatisk henting uansett bare ville
     * virket for nye tilkoblinger — de 61 foretakene som allerede ligger inne
     * måtte etterfylles i en egen kjøring — og Bård tar den jobben selv.
     * Derfor er feltet en vanlig select uten skrivelås.
     *
     * MERK til en senere runde: BRREG-svaret inneholder allerede
     * `kommunenummer` (se bimverdi_brreg_parse_company), og fylket er de to
     * første sifrene der. Automatisk utfylling for NYE foretak er altså langt
     * billigere enn det så ut som i møtet, hvis det blir aktuelt.
     *
     * Select og ikke fritekst: verdiene skal kunne filtreres og telles på
     * («deltakere i Trøndelag»), og da må de være identiske hver gang.
     * Listen er de 15 fylkene slik de ble etter inndelingen 01.01.2024.
     */
    acf_add_local_field_group([
        'key' => 'group_bim_verdi_foretak_geografi',
        'title' => 'BIM Verdi - Geografi',
        'fields' => [
            [
                'key' => 'field_foretak_fylke',
                'label' => 'Fylke',
                'name' => 'bv_fylke',
                'type' => 'select',
                'instructions' => 'Fylles ut manuelt. Vises på foretaksprofilen sammen med adressen.',
                'required' => 0,
                'allow_null' => 1,
                'ui' => 1,
                'return_format' => 'value',
                'choices' => [
                    'Akershus'         => 'Akershus',
                    'Agder'            => 'Agder',
                    'Buskerud'         => 'Buskerud',
                    'Finnmark'         => 'Finnmark',
                    'Innlandet'        => 'Innlandet',
                    'Møre og Romsdal' => 'Møre og Romsdal',
                    'Nordland'         => 'Nordland',
                    'Oslo'             => 'Oslo',
                    'Rogaland'         => 'Rogaland',
                    'Telemark'         => 'Telemark',
                    'Troms'            => 'Troms',
                    'Trøndelag'       => 'Trøndelag',
                    'Vestfold'         => 'Vestfold',
                    'Vestland'         => 'Vestland',
                    'Østfold'         => 'Østfold',
                    'Utenfor Norge'    => 'Utenfor Norge',
                ],
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
        'menu_order' => 6,
        'position' => 'side',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
    ]);
});
