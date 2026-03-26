<?php
/**
 * BIM Verdi - Påmelding Admin Enhancements
 *
 * Forbedrer admin-visningen for påmeldinger:
 * - Nyttige kolonner (deltaker, e-post, arrangement, status, foretak, dato)
 * - Filtrering per arrangement
 * - CSV-eksport av deltakerliste
 * - Påmeldt-teller på arrangement-listen
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. CUSTOM COLUMNS FOR PÅMELDING LIST
// ─────────────────────────────────────────────────────────────────────────────

add_filter('manage_pamelding_posts_columns', 'bimverdi_pamelding_columns');

function bimverdi_pamelding_columns($columns) {
    $new = [];

    if (isset($columns['cb'])) {
        $new['cb'] = $columns['cb'];
    }

    // Keep ID column if it exists (from admin-enhancements)
    if (isset($columns['post_id'])) {
        $new['post_id'] = $columns['post_id'];
    }

    $new['pamelding_deltaker']    = 'Deltaker';
    $new['pamelding_epost']       = 'E-post';
    $new['pamelding_arrangement'] = 'Arrangement';
    $new['pamelding_status']      = 'Status';
    $new['pamelding_foretak']     = 'Foretak';
    $new['pamelding_dato']        = 'Påmeldt';

    return $new;
}

add_action('manage_pamelding_posts_custom_column', 'bimverdi_pamelding_column_content', 10, 2);

function bimverdi_pamelding_column_content($column, $post_id) {
    // Get user from ACF fields
    $user_id = get_field('bruker', $post_id) ?: get_field('pamelding_bruker', $post_id);
    if (is_array($user_id)) {
        $user_id = $user_id['ID'] ?? 0;
    }
    $user = $user_id ? get_userdata($user_id) : null;

    switch ($column) {
        case 'pamelding_deltaker':
            if ($user) {
                printf(
                    '<a href="%s"><strong>%s</strong></a>',
                    esc_url(get_edit_user_link($user->ID)),
                    esc_html($user->display_name)
                );
            } else {
                // Fallback: extract name from title
                $title = get_the_title($post_id);
                $name = preg_match('/–\s*(.+)$/', $title, $m) ? $m[1] : '—';
                echo esc_html($name);
            }
            break;

        case 'pamelding_epost':
            echo $user ? sprintf('<a href="mailto:%1$s">%1$s</a>', esc_html($user->user_email)) : '—';
            break;

        case 'pamelding_arrangement':
            $arr_id = get_field('arrangement', $post_id) ?: get_field('pamelding_arrangement', $post_id);
            if (is_array($arr_id)) {
                $arr_id = $arr_id['ID'] ?? 0;
            }
            if ($arr_id && get_post($arr_id)) {
                $short_title = wp_trim_words(get_the_title($arr_id), 6, '…');
                printf(
                    '<a href="%s">%s</a>',
                    esc_url(get_edit_post_link($arr_id)),
                    esc_html($short_title)
                );
            } else {
                echo '—';
            }
            break;

        case 'pamelding_status':
            $status = get_field('pamelding_status', $post_id) ?: 'ukjent';
            $labels = [
                'bekreftet'   => ['Påmeldt', '#46b450', '#f0faf0'],
                'aktiv'       => ['Påmeldt', '#46b450', '#f0faf0'],
                'venteliste'  => ['Venteliste', '#dba617', '#fef8e7'],
                'avmeldt'     => ['Avmeldt', '#a00', '#fef0f0'],
                'gjennomfort' => ['Gjennomført', '#826eb4', '#f5f0ff'],
            ];
            $label = $labels[$status] ?? [$status, '#666', '#f0f0f0'];
            printf(
                '<span style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:12px;font-weight:500;color:%s;background:%s;">%s</span>',
                esc_attr($label[1]),
                esc_attr($label[2]),
                esc_html($label[0])
            );
            break;

        case 'pamelding_foretak':
            if ($user) {
                $company_id = get_user_meta($user->ID, 'bimverdi_company_id', true)
                    ?: get_user_meta($user->ID, 'bim_verdi_company_id', true)
                    ?: get_field('tilknyttet_foretak', 'user_' . $user->ID);
                if ($company_id && get_post($company_id)) {
                    printf(
                        '<a href="%s">%s</a>',
                        esc_url(get_edit_post_link($company_id)),
                        esc_html(get_the_title($company_id))
                    );
                } else {
                    echo '<span style="color:#999;">—</span>';
                }
            } else {
                echo '—';
            }
            break;

        case 'pamelding_dato':
            $post_date = get_the_date('d.m.Y H:i', $post_id);
            echo esc_html($post_date);
            break;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. FILTER BY ARRANGEMENT DROPDOWN
// ─────────────────────────────────────────────────────────────────────────────

add_action('restrict_manage_posts', 'bimverdi_pamelding_filter_dropdown');

function bimverdi_pamelding_filter_dropdown($post_type) {
    if ($post_type !== 'pamelding') {
        return;
    }

    // Get all arrangements that have registrations
    global $wpdb;
    $arrangement_ids = $wpdb->get_col(
        "SELECT DISTINCT pm.meta_value
         FROM {$wpdb->postmeta} pm
         JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE p.post_type = 'pamelding'
           AND p.post_status = 'publish'
           AND pm.meta_key IN ('arrangement', 'pamelding_arrangement')
           AND pm.meta_value > 0
         ORDER BY pm.meta_value DESC"
    );

    $selected = isset($_GET['filter_arrangement']) ? intval($_GET['filter_arrangement']) : 0;

    echo '<select name="filter_arrangement">';
    echo '<option value="">Alle arrangementer</option>';

    foreach ($arrangement_ids as $arr_id) {
        if (!get_post($arr_id)) continue;
        $title = wp_trim_words(get_the_title($arr_id), 8, '…');
        printf(
            '<option value="%d" %s>%s</option>',
            $arr_id,
            selected($selected, $arr_id, false),
            esc_html($title)
        );
    }

    echo '</select>';
}

add_action('pre_get_posts', 'bimverdi_pamelding_filter_query');

function bimverdi_pamelding_filter_query($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->get('post_type') !== 'pamelding') {
        return;
    }

    $filter = isset($_GET['filter_arrangement']) ? intval($_GET['filter_arrangement']) : 0;

    if ($filter > 0) {
        $meta_query = $query->get('meta_query') ?: [];
        $meta_query[] = [
            'relation' => 'OR',
            [
                'key'   => 'arrangement',
                'value' => $filter,
            ],
            [
                'key'   => 'pamelding_arrangement',
                'value' => $filter,
            ],
        ];
        $query->set('meta_query', $meta_query);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. CSV EXPORT
// ─────────────────────────────────────────────────────────────────────────────

add_action('restrict_manage_posts', 'bimverdi_pamelding_export_button');

function bimverdi_pamelding_export_button($post_type) {
    if ($post_type !== 'pamelding') {
        return;
    }

    // Pass all current filters to the export URL
    $export_params = [
        'action'   => 'bimverdi_export_pamelding_csv',
        '_wpnonce' => wp_create_nonce('export_pamelding_csv'),
    ];
    if (!empty($_GET['filter_arrangement'])) {
        $export_params['filter_arrangement'] = intval($_GET['filter_arrangement']);
    }
    if (!empty($_GET['s'])) {
        $export_params['s'] = sanitize_text_field($_GET['s']);
    }
    if (!empty($_GET['m'])) {
        $export_params['m'] = intval($_GET['m']);
    }

    $export_url = add_query_arg($export_params, admin_url('admin-ajax.php'));

    printf(
        '<a href="%s" class="button" style="margin-left:8px;">⬇ Last ned CSV</a>',
        esc_url($export_url)
    );
}

add_action('wp_ajax_bimverdi_export_pamelding_csv', 'bimverdi_export_pamelding_csv');

function bimverdi_export_pamelding_csv() {
    if (!current_user_can('edit_posts')) {
        wp_die('Ingen tilgang.');
    }

    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'export_pamelding_csv')) {
        wp_die('Ugyldig forespørsel.');
    }

    $filter = intval($_GET['filter_arrangement'] ?? 0);
    $search = sanitize_text_field($_GET['s'] ?? '');
    $month  = intval($_GET['m'] ?? 0);

    $args = [
        'post_type'      => 'pamelding',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'ASC',
    ];

    if ($filter > 0) {
        $args['meta_query'] = [
            'relation' => 'OR',
            ['key' => 'arrangement', 'value' => $filter],
            ['key' => 'pamelding_arrangement', 'value' => $filter],
        ];
    }

    if ($search) {
        $args['s'] = $search;
    }

    if ($month > 0) {
        $args['m'] = $month;
    }

    $registrations = get_posts($args);

    // Build filename
    $filename = 'pamelding-eksport';
    if ($filter > 0) {
        $arr_slug = sanitize_title(wp_trim_words(get_the_title($filter), 5, ''));
        $filename .= '-' . $arr_slug;
    }
    $filename .= '-' . date('Y-m-d') . '.csv';

    // Headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // CSV header
    fputcsv($output, [
        'Navn',
        'E-post',
        'Arrangement',
        'Arrangement-dato',
        'Status',
        'Foretak',
        'Rolle',
        'Påmeldt dato',
    ], ';');

    foreach ($registrations as $reg) {
        $user_id = get_field('bruker', $reg->ID) ?: get_field('pamelding_bruker', $reg->ID);
        if (is_array($user_id)) {
            $user_id = $user_id['ID'] ?? 0;
        }
        $user = $user_id ? get_userdata($user_id) : null;

        $arr_id = get_field('arrangement', $reg->ID) ?: get_field('pamelding_arrangement', $reg->ID);
        if (is_array($arr_id)) {
            $arr_id = $arr_id['ID'] ?? 0;
        }

        // Get company
        $foretak_name = '';
        if ($user) {
            $company_id = get_user_meta($user->ID, 'bimverdi_company_id', true)
                ?: get_user_meta($user->ID, 'bim_verdi_company_id', true)
                ?: get_field('tilknyttet_foretak', 'user_' . $user->ID);
            if ($company_id && get_post($company_id)) {
                $foretak_name = get_the_title($company_id);
            }
        }

        // Get arrangement date
        $arr_dato = $arr_id ? (get_field('arrangement_dato', $arr_id) ?: '') : '';
        if ($arr_dato) {
            $arr_dato = date('d.m.Y', strtotime($arr_dato));
        }

        $status_map = [
            'bekreftet'   => 'Påmeldt',
            'aktiv'       => 'Påmeldt',
            'venteliste'  => 'Venteliste',
            'avmeldt'     => 'Avmeldt',
            'gjennomfort' => 'Gjennomført',
        ];
        $raw_status = get_field('pamelding_status', $reg->ID) ?: 'ukjent';

        // User role
        $rolle = '';
        if ($user) {
            $role_map = [
                'partner'           => 'Partner',
                'prosjektdeltaker'  => 'Prosjektdeltaker',
                'deltaker'          => 'Deltaker',
                'tilleggskontakt'   => 'Tilleggskontakt',
                'medlem'            => 'Medlem',
                'administrator'     => 'Administrator',
            ];
            $user_roles = $user->roles;
            foreach ($role_map as $role_key => $role_label) {
                if (in_array($role_key, $user_roles)) {
                    $rolle = $role_label;
                    break;
                }
            }
        }

        // Fallback name from title
        $name = $user ? $user->display_name : '';
        if (!$name) {
            $name = preg_match('/–\s*(.+)$/', $reg->post_title, $m) ? $m[1] : '';
        }

        fputcsv($output, [
            $name,
            $user ? $user->user_email : '',
            $arr_id ? get_the_title($arr_id) : '',
            $arr_dato,
            $status_map[$raw_status] ?? $raw_status,
            $foretak_name,
            $rolle,
            get_the_date('d.m.Y H:i', $reg->ID),
        ], ';');
    }

    fclose($output);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. REGISTRATION COUNT ON ARRANGEMENT LIST
// ─────────────────────────────────────────────────────────────────────────────

add_filter('manage_arrangement_posts_columns', 'bimverdi_arrangement_add_pamelding_column');

function bimverdi_arrangement_add_pamelding_column($columns) {
    // Insert before date column
    $new = [];
    foreach ($columns as $key => $label) {
        if ($key === 'date') {
            $new['pamelding_count'] = 'Påmeldte';
        }
        $new[$key] = $label;
    }
    // If date column didn't exist, add at end
    if (!isset($new['pamelding_count'])) {
        $new['pamelding_count'] = 'Påmeldte';
    }
    return $new;
}

add_action('manage_arrangement_posts_custom_column', 'bimverdi_arrangement_pamelding_count', 10, 2);

function bimverdi_arrangement_pamelding_count($column, $post_id) {
    if ($column !== 'pamelding_count') {
        return;
    }

    global $wpdb;
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT p.ID)
         FROM {$wpdb->posts} p
         JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE p.post_type = 'pamelding'
           AND p.post_status = 'publish'
           AND pm.meta_key IN ('arrangement', 'pamelding_arrangement')
           AND pm.meta_value = %d",
        $post_id
    ));

    if ($count > 0) {
        $filter_url = add_query_arg([
            'post_type'          => 'pamelding',
            'filter_arrangement' => $post_id,
        ], admin_url('edit.php'));

        printf(
            '<a href="%s" style="display:inline-block;min-width:28px;text-align:center;padding:2px 8px;background:#f0f6fc;border-radius:10px;font-weight:600;color:#2271b1;text-decoration:none;">%d</a>',
            esc_url($filter_url),
            $count
        );
    } else {
        echo '<span style="color:#999;">0</span>';
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 5. COLUMN WIDTHS
// ─────────────────────────────────────────────────────────────────────────────

add_action('admin_head', 'bimverdi_pamelding_admin_styles');

function bimverdi_pamelding_admin_styles() {
    $screen = get_current_screen();
    if (!$screen) return;

    if ($screen->base === 'edit' && $screen->post_type === 'pamelding') {
        echo '<style>
            .column-pamelding_deltaker { width: 15%; }
            .column-pamelding_epost { width: 18%; }
            .column-pamelding_arrangement { width: 22%; }
            .column-pamelding_status { width: 10%; }
            .column-pamelding_foretak { width: 15%; }
            .column-pamelding_dato { width: 12%; }

            /* Hide default title column */
            .column-title { display: none !important; }

            /* Compact row actions: collapse height, show on hover */
            #the-list .row-actions {
                position: absolute;
                margin: 0;
                padding: 0;
            }
            #the-list tr:not(:hover) .row-actions { display: none; }
        </style>';
    }

    if ($screen->base === 'edit' && $screen->post_type === 'arrangement') {
        echo '<style>
            .column-pamelding_count { width: 80px; text-align: center; }
        </style>';
    }
}
