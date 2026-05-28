<?php
/**
 * BIM Verdi — Foretakstype + nivå-felter på CPT foretak
 *
 * Krav 24-v4 datamodell:
 * - bv_foretakstype:  'gratisforetak' | 'foretak'
 * - bv_nivaa:         '' | 'deltaker' | 'prosjektdeltaker' | 'partner'
 * - bv_pending_nivaa: '' | 'deltaker' | 'prosjektdeltaker' | 'partner' (krav 25)
 * - bv_pending_fra_dato: date_picker (krav 25)
 *
 * Idempotent registrering via acf_add_local_field_group.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('acf/init', 'bimverdi_register_foretakstype_fields');

function bimverdi_register_foretakstype_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key'      => 'group_bv_foretakstype',
        'title'    => 'Foretakstype og nivå (krav 24-v4)',
        'fields'   => [
            [
                'key'          => 'field_bv_foretakstype',
                'label'        => 'Foretakstype',
                'name'         => 'bv_foretakstype',
                'type'         => 'select',
                'instructions' => 'Gratisforetak = uten betalt medlemskap. Foretak = Deltaker+.',
                'required'     => 0,
                'choices'      => [
                    'gratisforetak' => 'Gratisforetak',
                    'foretak'       => 'Foretak (Deltaker+)',
                ],
                'default_value' => 'gratisforetak',
                'allow_null'    => 0,
                'return_format' => 'value',
            ],
            [
                'key'          => 'field_bv_nivaa',
                'label'        => 'Nivå',
                'name'         => 'bv_nivaa',
                'type'         => 'select',
                'instructions' => 'Settes når foretakstype = foretak. Tom for gratisforetak.',
                'required'     => 0,
                'choices'      => [
                    ''                  => '(ingen — gratisforetak)',
                    'deltaker'          => 'Deltaker',
                    'prosjektdeltaker'  => 'Prosjektdeltaker',
                    'partner'           => 'Partner',
                ],
                'default_value' => '',
                'allow_null'    => 1,
                'return_format' => 'value',
            ],
            [
                'key'          => 'field_bv_pending_nivaa',
                'label'        => 'Pending nivå (krav 25)',
                'name'         => 'bv_pending_nivaa',
                'type'         => 'select',
                'instructions' => 'Settes 15. nov – 1. des av Hovedkontakt ved ønsket nivå-endring. Kopieres til bv_nivaa 1. januar via cron.',
                'required'     => 0,
                'choices'      => [
                    ''                  => '(ingen)',
                    'deltaker'          => 'Deltaker',
                    'prosjektdeltaker'  => 'Prosjektdeltaker',
                    'partner'           => 'Partner',
                ],
                'default_value' => '',
                'allow_null'    => 1,
                'return_format' => 'value',
            ],
            [
                'key'          => 'field_bv_pending_fra_dato',
                'label'        => 'Pending fra-dato (krav 25)',
                'name'         => 'bv_pending_fra_dato',
                'type'         => 'date_picker',
                'instructions' => 'Typisk 1. januar [neste år] når bv_pending_nivaa er satt.',
                'required'     => 0,
                'display_format' => 'd.m.Y',
                'return_format'  => 'Y-m-d',
                'first_day'      => 1,
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'foretak',
                ],
            ],
        ],
        'menu_order'            => 5,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,
    ]);
}

/**
 * Helper: returnerer foretakstype for et foretak.
 *
 * @param int $foretak_id
 * @return string 'gratisforetak' | 'foretak' (default 'gratisforetak' hvis tom)
 */
function bimverdi_get_foretakstype($foretak_id) {
    if (!function_exists('get_field') || !$foretak_id) {
        return 'gratisforetak';
    }
    $type = (string) get_field('bv_foretakstype', $foretak_id);
    return in_array($type, ['gratisforetak', 'foretak'], true) ? $type : 'gratisforetak';
}

/**
 * Helper: returnerer nivå for et foretak.
 *
 * @param int $foretak_id
 * @return string '' | 'deltaker' | 'prosjektdeltaker' | 'partner'
 */
function bimverdi_get_foretak_nivaa($foretak_id) {
    if (!function_exists('get_field') || !$foretak_id) {
        return '';
    }
    $nivaa = (string) get_field('bv_nivaa', $foretak_id);
    return in_array($nivaa, ['deltaker', 'prosjektdeltaker', 'partner'], true) ? $nivaa : '';
}

/**
 * Helper: sjekker om en bruker er Gratisbruker.
 *
 * Per krav 02-v5: Gratisbruker = bruker koblet til et foretak med
 * bv_foretakstype = 'gratisforetak'. WP-rolle er irrelevant.
 *
 * @param int|null $user_id
 * @return bool
 */
function bimverdi_is_gratisbruker($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    if (!$user_id) {
        return false;
    }
    $foretak_id = bimverdi_resolve_user_foretak_id($user_id);
    if (!$foretak_id) {
        return false;
    }
    return bimverdi_get_foretakstype($foretak_id) === 'gratisforetak';
}

/**
 * Helper: returnerer foretak-id som int (eller false).
 *
 * Eksisterende bimverdi_get_user_company() returnerer enten int eller array
 * (avhengig av hvilken definisjon som vinner). Denne helperen gir alltid en
 * int og er trygg å sende inn til get_field/get_the_title/etc.
 *
 * @param int|null $user_id
 * @return int|false
 */
function bimverdi_resolve_user_foretak_id($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    if (!$user_id) {
        return false;
    }
    if (class_exists('BIMVerdi_Access_Control') && method_exists('BIMVerdi_Access_Control', 'lookup_company_id')) {
        $id = BIMVerdi_Access_Control::lookup_company_id($user_id);
        if ($id) {
            return (int) $id;
        }
    }
    $id = get_user_meta($user_id, 'bimverdi_company_id', true);
    if (!$id) {
        $id = get_user_meta($user_id, 'bim_verdi_company_id', true);
    }
    if (!$id && function_exists('get_field')) {
        $acf = get_field('tilknyttet_foretak', 'user_' . $user_id);
        $id = is_object($acf) ? $acf->ID : (is_array($acf) ? ($acf['ID'] ?? 0) : $acf);
    }
    return $id ? (int) $id : false;
}
