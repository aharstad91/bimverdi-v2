<?php
/**
 * ACF Field Group: Temagruppe-felter
 *
 * Registers custom fields for the theme_group CPT (Temagrupper).
 * Fields include group info, fagansvarlig (responsible person),
 * visual assets, and resources.
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Register ACF field group for Temagruppe
 */
function bimverdi_register_temagruppe_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_temagruppe_fields',
        'title' => 'Temagruppe-felter',
        'fields' => [
            // === GRUNNINFO TAB ===
            [
                'key' => 'field_temagruppe_tab_grunninfo',
                'label' => 'Grunninfo',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_temagruppe_kort_beskrivelse',
                'label' => 'Kort beskrivelse',
                'name' => 'kort_beskrivelse',
                'type' => 'textarea',
                'instructions' => 'Kort oppsummering av temagrupppen (vises i header). Maks 300 tegn.',
                'maxlength' => 300,
                'rows' => 3,
            ],
            [
                'key' => 'field_temagruppe_status',
                'label' => 'Status',
                'name' => 'status',
                'type' => 'select',
                'instructions' => 'Nåværende status for temagruppen.',
                'choices' => [
                    'aktiv' => 'Aktiv',
                    'planlegging' => 'Planlegging',
                    'pause' => 'Pause',
                ],
                'default_value' => 'aktiv',
            ],
            [
                'key' => 'field_temagruppe_motefrekvens',
                'label' => 'Møtefrekvens',
                'name' => 'motefrekvens',
                'type' => 'text',
                'instructions' => 'F.eks. "Annenhver måned", "Etter behov", "Månedlig" etc.',
                'placeholder' => 'Annenhver måned',
            ],

            // === FAGANSVARLIG TAB ===
            [
                'key' => 'field_temagruppe_tab_fagansvarlig',
                'label' => 'Fagansvarlig',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_temagruppe_fagansvarlig_navn',
                'label' => 'Navn',
                'name' => 'fagansvarlig_navn',
                'type' => 'text',
                'instructions' => 'Fullt navn på fagansvarlig.',
            ],
            [
                'key' => 'field_temagruppe_fagansvarlig_tittel',
                'label' => 'Tittel',
                'name' => 'fagansvarlig_tittel',
                'type' => 'text',
                'instructions' => 'Stilling/tittel.',
                'placeholder' => 'Seniorrådgiver',
            ],
            [
                'key' => 'field_temagruppe_fagansvarlig_bedrift',
                'label' => 'Bedrift',
                'name' => 'fagansvarlig_bedrift',
                'type' => 'post_object',
                'instructions' => 'Velg bedriften fagansvarlig jobber i.',
                'post_type' => ['foretak'],
                'return_format' => 'id',
                'allow_null' => 1,
            ],
            [
                'key' => 'field_temagruppe_fagansvarlig_bilde',
                'label' => 'Profilbilde',
                'name' => 'fagansvarlig_bilde',
                'type' => 'image',
                'instructions' => 'Profilbilde av fagansvarlig (kvadratisk, min 200x200px).',
                'return_format' => 'id',
                'preview_size' => 'thumbnail',
            ],
            [
                'key' => 'field_temagruppe_fagansvarlig_linkedin',
                'label' => 'LinkedIn',
                'name' => 'fagansvarlig_linkedin',
                'type' => 'url',
                'instructions' => 'Lenke til LinkedIn-profil.',
                'placeholder' => 'https://linkedin.com/in/...',
            ],

            // === VISUELT TAB ===
            [
                'key' => 'field_temagruppe_tab_visuelt',
                'label' => 'Visuelt',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_temagruppe_ikon',
                'label' => 'Ikon',
                'name' => 'ikon',
                'type' => 'image',
                'instructions' => 'Ikon for bruk i oversiktslister (64x64px, PNG med transparent bakgrunn).',
                'return_format' => 'id',
                'preview_size' => 'thumbnail',
            ],
            [
                'key' => 'field_temagruppe_hero_illustrasjon',
                'label' => 'Hero-illustrasjon',
                'name' => 'hero_illustrasjon',
                'type' => 'image',
                'instructions' => 'Stor illustrasjon for hero-seksjon (dekorativ, plasseres til høyre).',
                'return_format' => 'id',
                'preview_size' => 'medium',
            ],

            // === RESSURSER TAB ===
            [
                'key' => 'field_temagruppe_tab_ressurser',
                'label' => 'Ressurser',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_temagruppe_ressurser',
                'label' => 'Ressurser',
                'name' => 'ressurser',
                'type' => 'repeater',
                'instructions' => 'Legg til relevante lenker og ressurser.',
                'layout' => 'table',
                'button_label' => 'Legg til ressurs',
                'sub_fields' => [
                    [
                        'key' => 'field_temagruppe_ressurs_tittel',
                        'label' => 'Tittel',
                        'name' => 'ressurs_tittel',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_temagruppe_ressurs_url',
                        'label' => 'URL',
                        'name' => 'ressurs_url',
                        'type' => 'url',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_temagruppe_ressurs_type',
                        'label' => 'Type',
                        'name' => 'ressurs_type',
                        'type' => 'select',
                        'choices' => [
                            'dokument' => 'Dokument',
                            'verktoy' => 'Verktøy',
                            'ekstern' => 'Ekstern lenke',
                        ],
                        'default_value' => 'dokument',
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'theme_group',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
    ]);
}

add_action('acf/init', 'bimverdi_register_temagruppe_acf_fields');
