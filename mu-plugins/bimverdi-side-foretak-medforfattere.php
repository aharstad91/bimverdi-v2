<?php
/**
 * Plugin Name: BIM Verdi - Foretak og medforfattere på sider
 * Description: Feltene «Tilknyttet foretak» og «Medforfattere» på sider i wp-admin, med valg fra deltakerforetak og deres kontakter. Vises under sidetittelen når de er fylt ut.
 * Version: 1.0.0
 *
 * Bård 05.11.2026: samme valg som på artikler, nå også for sider
 * (f.eks. /wp-admin/post-new.php?post_type=page).
 *
 * Utvalget er deltakerforetak og brukerne knyttet til dem — samme regel som
 * artikkelskjemaet på Min side (bimverdi_afm_* i
 * bimverdi-artikkel-foretak-medforfattere.php). Medforfattere på sider får
 * INGEN redigeringstilgang; sider redigeres bare i wp-admin.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    acf_add_local_field_group([
        'key'    => 'group_bv_side_foretak_medforfattere',
        'title'  => 'Foretak og medforfattere',
        'fields' => [
            [
                'key'           => 'field_side_bedrift',
                'label'         => 'Tilknyttet foretak',
                'name'          => 'side_bedrift',
                'type'          => 'post_object',
                'instructions'  => 'Deltakerforetaket siden er tilknyttet. Vises under sidetittelen.',
                'post_type'     => ['foretak'],
                'allow_null'    => 1,
                'multiple'      => 0,
                'return_format' => 'id',
                'ui'            => 1,
            ],
            [
                'key'           => 'field_side_medforfattere',
                'label'         => 'Medforfattere',
                'name'          => 'side_medforfattere',
                'type'          => 'user',
                'instructions'  => 'Kontakter i deltakerforetak som har bidratt. Vises under sidetittelen. Gir ingen redigeringstilgang.',
                'role'          => '',
                'multiple'      => 1,
                'allow_null'    => 1,
                'return_format' => 'id',
            ],
        ],
        'location' => [[
            ['param' => 'post_type', 'operator' => '==', 'value' => 'page'],
        ]],
        'position' => 'side',
        'active'   => true,
    ]);
});

/** Deltakerforetak-ID-er (gjenbruker artikkelskjemaets utvalg). */
function bimverdi_side_deltakerforetak_ids() {
    static $ids = null;
    if ($ids === null) {
        $ids = function_exists('bimverdi_afm_foretak_valg') ? array_keys(bimverdi_afm_foretak_valg()) : [];
    }
    return $ids;
}

// Foretak-velgeren: bare deltakerforetak.
add_filter('acf/fields/post_object/query/key=field_side_bedrift', function ($args) {
    $ids = bimverdi_side_deltakerforetak_ids();
    $args['post__in'] = $ids ?: [0];
    return $args;
});

// Medforfatter-velgeren: bare kontakter i deltakerforetak.
add_filter('acf/fields/user/query/key=field_side_medforfattere', function ($args) {
    $foretak = array_map('strval', bimverdi_side_deltakerforetak_ids());
    if (empty($foretak)) {
        $args['include'] = [0];
        return $args;
    }
    $args['meta_query'] = [
        'relation' => 'OR',
        ['key' => 'bimverdi_company_id', 'value' => $foretak, 'compare' => 'IN'],
        ['key' => 'bim_verdi_company_id', 'value' => $foretak, 'compare' => 'IN'],
    ];
    return $args;
});

/**
 * Byline under sidetittelen: «Foretak · med A, B». Tom hvis ingen felt er fylt ut.
 */
function bimverdi_side_byline($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    $foretak = (int) get_post_meta($post_id, 'side_bedrift', true);
    $raa     = get_post_meta($post_id, 'side_medforfattere', true);

    $navn = [];
    foreach ((array) maybe_unserialize($raa) as $uid) {
        $u = is_numeric($uid) ? get_userdata((int) $uid) : null;
        if ($u) {
            $navn[] = esc_html($u->display_name);
        }
    }
    $har_foretak = $foretak && get_post_type($foretak) === 'foretak' && get_post_status($foretak) === 'publish';
    if (!$har_foretak && empty($navn)) {
        return;
    }
    ?>
    <p class="bv-page__byline" style="margin:16px 0 0;font-size:0.95rem;color:#5A5A5A;">
        <?php if ($har_foretak) : ?>
            <a href="<?php echo esc_url(get_permalink($foretak)); ?>" style="color:#E67A4E;font-weight:600;"><?php echo esc_html(get_the_title($foretak)); ?></a>
        <?php endif; ?>
        <?php if (!empty($navn)) : ?>
            <?php echo $har_foretak ? ' · ' : ''; ?>med <?php echo implode(', ', $navn); ?>
        <?php endif; ?>
    </p>
    <?php
}
