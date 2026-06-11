<?php
/**
 * BIM Verdi - Nyhetsbrev CPT (utkast → forhåndsvisning → utsendelse)
 *
 * Hvert nyhetsbrev lagres som en egen post av typen `nyhetsbrev`. Når Bård
 * trykker «Generer nyhetsbrev nå» tas et FRYST øyeblikksbilde av det ferskeste
 * innholdet (bimverdi_nyhetsbrev_collect()) og den rendrede e-post-HTML-en
 * (bimverdi_render_nyhetsbrev()) lagres på posten. Forhåndsvisningen viser da
 * nøyaktig det som vil bli sendt — innholdet «drifter» ikke mellom preview og send.
 *
 * Admin-only (manage_options). Selve Resend-utsendelsen kobles på i neste steg
 * (send-motor + mottakerkilde-avklaring); «Send»-knappen er forberedt her.
 *
 * Bakgrunn: Bård-synk 2026-06-09 reverserte trigger fra cron → CPT + manuell
 * send-knapp, så han kan kvalitetssikre nyhetsverdien før utsendelse.
 * Plan: docs/plans/2026-06-03-001-feat-nyhetsbrev-mal-og-utsendelse-plan.md
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BV_NYHETSBREV_CPT')) {
    define('BV_NYHETSBREV_CPT', 'nyhetsbrev');
}

/* -------------------------------------------------------------------------
 * 1. Registrer CPT — admin-intern, kun for administratorer (manage_options).
 * ---------------------------------------------------------------------- */
add_action('init', function () {
    $labels = array(
        'name'               => 'Nyhetsbrev',
        'singular_name'      => 'Nyhetsbrev',
        'menu_name'          => 'Nyhetsbrev',
        'all_items'          => 'Alle nyhetsbrev',
        'add_new'            => 'Generer nytt',
        'add_new_item'       => 'Nytt nyhetsbrev',
        'edit_item'          => 'Nyhetsbrev',
        'view_item'          => 'Forhåndsvis nyhetsbrev',
        'search_items'       => 'Søk nyhetsbrev',
        'not_found'          => 'Ingen nyhetsbrev ennå',
        'not_found_in_trash' => 'Ingen nyhetsbrev i papirkurven',
    );

    register_post_type(BV_NYHETSBREV_CPT, array(
        'labels'          => $labels,
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'show_in_rest'    => false,
        'menu_position'   => 26,
        'menu_icon'       => 'dashicons-email-alt',
        'supports'        => array('title'),
        'has_archive'     => false,
        'rewrite'         => false,
        'query_var'       => false,
        // Lås hele CPT-en til administratorer. Betalende roller har edit_posts=true
        // (de skriver artikler), så vi MÅ mappe til manage_options her.
        //
        // ⚠️ KRITISK: IKKE remap de SINGULÆRE meta-cap-ene (edit_post/read_post/
        // delete_post) til en primitiv cap. WordPress (_post_type_meta_capabilities)
        // registrerer da MÅL-cap-en (manage_options) selv som en meta-cap, slik at
        // map_meta_cap('manage_options') rekurserer til read_post → do_not_allow og
        // bryter manage_options GLOBALT for alle brukere. Vi remapper derfor kun de
        // plurale/primitive cap-ene; per-post-redigering arver gating via map_meta_cap
        // (edit_post → edit_posts/edit_others_posts → manage_options).
        'capability_type' => 'post',
        'map_meta_cap'    => true,
        'capabilities'    => array(
            'edit_posts'         => 'manage_options',
            'edit_others_posts'  => 'manage_options',
            'publish_posts'      => 'manage_options',
            'read_private_posts' => 'manage_options',
            'create_posts'       => 'manage_options',
            'delete_posts'       => 'manage_options',
        ),
    ));
});

/* -------------------------------------------------------------------------
 * 2. Øyeblikksbilde-logikk: bygg + lagre innhold på en nyhetsbrev-post.
 * ---------------------------------------------------------------------- */

/**
 * Bygg og lagre et frosset øyeblikksbilde på en eksisterende nyhetsbrev-post.
 *
 * Lagrer rendret HTML (det som faktisk sendes) + metadata. Kan kjøres på nytt
 * for et utkast (oppdaterer øyeblikksbildet) så lenge brevet ikke er sendt.
 *
 * @param int   $post_id  Nyhetsbrev-post-ID.
 * @param array $args     Valgfritt: ['context' => [...]] for render-kontekst.
 * @return true|WP_Error
 */
function bimverdi_nyhetsbrev_snapshot($post_id, $args = array()) {
    if (!function_exists('bimverdi_nyhetsbrev_collect') || !function_exists('bimverdi_render_nyhetsbrev')) {
        return new WP_Error('mangler_innholdsmotor', 'Nyhetsbrev-innholdsfunksjonene er ikke lastet (bimverdi-nyhetsbrev-content.php).');
    }

    $data    = bimverdi_nyhetsbrev_collect();
    $context = (isset($args['context']) && is_array($args['context'])) ? $args['context'] : array();
    // Avmeldings-lenken er per mottaker: lagre placeholdere i øyeblikksbildet,
    // send-motoren (bimverdi_nyhetsbrev_send_en) bytter dem ut ved utsendelse.
    $context = wp_parse_args($context, array(
        'avmelding_url' => home_url('/?bv_nb_avmeld=%%BV_UID%%&bvt=%%BV_TOKEN%%'),
    ));
    $html    = bimverdi_render_nyhetsbrev($data, $context);

    if ($html === '') {
        return new WP_Error('tom_mal', 'Nyhetsbrev-malen kunne ikke rendres (fant ikke parts/email/nyhetsbrev.php).');
    }

    // Tell items per seksjon for en rask oversikt i admin.
    $antall = 0;
    if (!empty($data['seksjoner'])) {
        foreach ($data['seksjoner'] as $seksjon) {
            $antall += isset($seksjon['items']) ? count($seksjon['items']) : 0;
        }
    }

    // wp_slash: update_post_meta unslasher internt — JSON/HTML med «\» må
    // slashes for å lagres trofast.
    update_post_meta($post_id, '_bv_nyhetsbrev_html', wp_slash($html));
    update_post_meta($post_id, '_bv_nyhetsbrev_generated_at', current_time('mysql'));
    update_post_meta($post_id, '_bv_nyhetsbrev_item_count', $antall);

    return true;
}

/**
 * Opprett et nytt nyhetsbrev-utkast med frosset øyeblikksbilde.
 *
 * @param array $args  Valgfritt: ['title' => ..., 'context' => [...]].
 * @return int|WP_Error  Post-ID ved suksess.
 */
function bimverdi_nyhetsbrev_generer($args = array()) {
    $title = !empty($args['title'])
        ? $args['title']
        : 'Nytt & Nyttig — ' . bimverdi_nyhetsbrev_dato_nb();

    $post_id = wp_insert_post(array(
        'post_type'   => BV_NYHETSBREV_CPT,
        'post_status' => 'draft',
        'post_title'  => $title,
    ), true);

    if (is_wp_error($post_id)) {
        return $post_id;
    }

    $snapshot = bimverdi_nyhetsbrev_snapshot($post_id, $args);
    if (is_wp_error($snapshot)) {
        // Rull tilbake den tomme posten så vi ikke etterlater søppel-utkast.
        wp_delete_post($post_id, true);
        return $snapshot;
    }

    return $post_id;
}

/** Er nyhetsbrevet allerede sendt? */
function bimverdi_nyhetsbrev_er_sendt($post_id) {
    return (bool) get_post_meta($post_id, '_bv_nyhetsbrev_sent_at', true);
}

/* -------------------------------------------------------------------------
 * 3. admin-post-handlere: generer nytt / oppdater øyeblikksbilde.
 * ---------------------------------------------------------------------- */

add_action('admin_post_bimverdi_generer_nyhetsbrev', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Ingen tilgang.');
    }
    check_admin_referer('bimverdi_generer_nyhetsbrev');

    $post_id = bimverdi_nyhetsbrev_generer();

    if (is_wp_error($post_id)) {
        wp_safe_redirect(add_query_arg(
            array('post_type' => BV_NYHETSBREV_CPT, 'bv_nb_notice' => 'feil'),
            admin_url('edit.php')
        ));
        exit;
    }

    wp_safe_redirect(add_query_arg(
        'bv_nb_notice',
        'generert',
        admin_url('post.php?post=' . $post_id . '&action=edit')
    ));
    exit;
});

add_action('admin_post_bimverdi_oppdater_nyhetsbrev', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Ingen tilgang.');
    }
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    check_admin_referer('bimverdi_oppdater_nyhetsbrev_' . $post_id);

    if (!$post_id || get_post_type($post_id) !== BV_NYHETSBREV_CPT) {
        wp_die('Ugyldig nyhetsbrev.');
    }
    if (bimverdi_nyhetsbrev_er_sendt($post_id)) {
        wp_safe_redirect(add_query_arg('bv_nb_notice', 'allerede_sendt', admin_url('post.php?post=' . $post_id . '&action=edit')));
        exit;
    }
    // Redigeringssperre: så snart en utsendelse er startet (manifest finnes)
    // er øyeblikksbildet FRYST — å regenerere det her ville brutt manifestets
    // HTML-hash og stoppet kjøringsmotoren (payload-drift). Nektes uansett status.
    if (function_exists('bimverdi_nyhetsbrev_utsendelse_startet') && bimverdi_nyhetsbrev_utsendelse_startet($post_id)) {
        wp_safe_redirect(add_query_arg('bv_nb_notice', 'utsendelse_laast', admin_url('post.php?post=' . $post_id . '&action=edit')));
        exit;
    }

    $res = bimverdi_nyhetsbrev_snapshot($post_id);
    $notice = is_wp_error($res) ? 'feil' : 'oppdatert';
    wp_safe_redirect(add_query_arg('bv_nb_notice', $notice, admin_url('post.php?post=' . $post_id . '&action=edit')));
    exit;
});

/* -------------------------------------------------------------------------
 * 4. Admin-UI: «Generer»-knapp + notiser på listevisningen.
 * ---------------------------------------------------------------------- */

add_action('admin_notices', function () {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== BV_NYHETSBREV_CPT) {
        return;
    }

    // Statusnotiser etter en handling.
    if (!empty($_GET['bv_nb_notice'])) {
        $map = array(
            'generert'       => array('success', 'Nytt nyhetsbrev-utkast generert med dagens innhold.'),
            'oppdatert'      => array('success', 'Øyeblikksbildet er oppdatert med dagens innhold.'),
            'allerede_sendt' => array('warning', 'Nyhetsbrevet er allerede sendt og kan ikke endres.'),
            'utsendelse_laast' => array('warning', 'Utsendelsen er startet — øyeblikksbildet er låst og kan ikke regenereres.'),
            'feil'           => array('error', 'Kunne ikke generere nyhetsbrev. Sjekk at innholdsmotoren og malen er på plass.'),
            // Massesend-notiser (Unit 4).
            'massesend_fullfort'   => array('success', 'Nyhetsbrevet er sendt til alle mottakere. 🎉 Sjekk Resend-dashboardet for leveringsbekreftelse.'),
            'massesend_delvis'     => array('warning', 'Utsendelsen er delvis fullført. Se statusrapporten i sidepanelet og trykk «Fortsett utsendelse».'),
            'massesend_stoppet'    => array('error', 'Utsendelsen stoppet (miljø- eller konfigurasjonssperre). Ingenting ble sendt fra dette miljøet — se error_log.'),
            'massesend_ikke_bekreftet' => array('warning', 'Du må krysse av for bekreftelse. Ingenting ble sendt.'),
            'massesend_allerede_sendt' => array('warning', 'Nyhetsbrevet er allerede sendt.'),
            'massesend_mangler_snapshot' => array('error', 'Nyhetsbrevet har ikke noe øyeblikksbilde å sende — generer først.'),
            'massesend_mangler_manifest' => array('error', 'Ingen utsendelse er startet for dette brevet.'),
            'massesend_kjoring_pagar'  => array('warning', 'En utsendelse pågår allerede (fersk heartbeat). Vent til den er ferdig.'),
            'massesend_stale_las'      => array('warning', 'Forrige kjøring etterlot en lås uten heartbeat. Bruk «Overta stale lås og fortsett».'),
            'massesend_massesend_deaktivert' => array('error', 'Masseutsendelse er ikke aktivert i dette miljøet (wp-config-konstant mangler). Ingenting ble sendt.'),
            'massesend_feil_miljo'     => array('error', 'Masseutsendelse kjører kun på produksjon (bimverdi.no). Ingenting ble sendt.'),
            'massesend_mangler_api_nokkel' => array('error', 'Resend API-nøkkel mangler. Ingenting ble sendt.'),
            'massesend_payload_drift'  => array('error', 'STOPP: øyeblikksbildet er endret etter at utsendelsen startet. Utsendelsen er stanset — kontakt utvikler.'),
            'massesend_verifisert'     => array('success', 'Batch-statusen er oppdatert etter manuell verifisering.'),
            'massesend_verifisering_ugyldig' => array('error', 'Ugyldig verifiserings-forespørsel — batchen krever ikke manuell verifisering.'),
            'test_sendt'           => array('success', 'Test-e-post sendt. Sjekk innboksen (emne starter med [TEST]).'),
            'test_feil'            => array('error', 'Test-utsendelsen feilet for én eller flere adresser — se error_log.'),
            'test_tom'             => array('warning', 'Oppgi minst én e-postadresse for test-utsendelse.'),
            'test_for_mange'       => array('warning', 'Maks 5 adresser per test-utsendelse.'),
            'test_ikke_tillatt'    => array('error', 'Én eller flere adresser er ikke på test-allowlisten (din egen e-post + BIMVERDI_NYHETSBREV_TEST_MOTTAKERE i wp-config). Ingenting ble sendt.'),
            'test_mangler_snapshot' => array('error', 'Nyhetsbrevet har ikke noe øyeblikksbilde å sende — generer først.'),
        );
        $key = sanitize_key($_GET['bv_nb_notice']);
        if (isset($map[$key])) {
            list($type, $melding) = $map[$key];
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($type),
                esc_html($melding)
            );
        }
    }

    // Generer-knapp øverst på LISTEvisningen.
    if ($screen->base === 'edit') {
        $action = esc_url(admin_url('admin-post.php'));
        echo '<div class="notice notice-info" style="padding:14px 16px;">';
        echo '<p style="margin:0 0 10px 0;font-size:13px;">Nyhetsbrev genereres automatisk fra det ferskeste innholdet i nettverket. '
           . 'Du forhåndsviser og kvalitetssikrer, og sender selv når du er fornøyd.</p>';
        echo '<form method="post" action="' . $action . '" style="margin:0;">';
        echo '<input type="hidden" name="action" value="bimverdi_generer_nyhetsbrev">';
        wp_nonce_field('bimverdi_generer_nyhetsbrev');
        echo '<button type="submit" class="button button-primary"><span class="dashicons dashicons-email-alt" style="margin:4px 4px 0 -2px;"></span> Generer nyhetsbrev nå</button>';
        echo '</form></div>';
    }
});

// Skjul «Legg til ny» — nyhetsbrev skal genereres, ikke skrives for hånd.
add_action('admin_menu', function () {
    remove_submenu_page('edit.php?post_type=' . BV_NYHETSBREV_CPT, 'post-new.php?post_type=' . BV_NYHETSBREV_CPT);
}, 999);

add_action('admin_head', function () {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === BV_NYHETSBREV_CPT && $screen->base === 'edit') {
        echo '<style>.page-title-action{display:none !important;}</style>';
    }
});

/* -------------------------------------------------------------------------
 * 5. Listekolonner: status + innhold + forhåndsvisning.
 * ---------------------------------------------------------------------- */

add_filter('manage_' . BV_NYHETSBREV_CPT . '_posts_columns', function ($columns) {
    $ny = array();
    foreach ($columns as $key => $label) {
        $ny[$key] = $label;
        if ($key === 'title') {
            $ny['bv_status']  = 'Status';
            $ny['bv_innhold'] = 'Innhold';
            $ny['bv_preview'] = 'Forhåndsvis';
        }
    }
    return $ny;
});

add_action('manage_' . BV_NYHETSBREV_CPT . '_posts_custom_column', function ($column, $post_id) {
    if ($column === 'bv_status') {
        $sent = get_post_meta($post_id, '_bv_nyhetsbrev_sent_at', true);
        if ($sent) {
            echo '<span style="color:#1a7d3c;font-weight:600;">Sendt</span><br><span style="color:#5A5A5A;font-size:12px;">'
               . esc_html(bimverdi_nyhetsbrev_dato_nb($sent, true)) . '</span>';
        } else {
            echo '<span style="color:#8a6d00;font-weight:600;">Kladd</span>';
        }
    } elseif ($column === 'bv_innhold') {
        $count = (int) get_post_meta($post_id, '_bv_nyhetsbrev_item_count', true);
        echo esc_html($count) . ' element' . ($count === 1 ? '' : 'er');
    } elseif ($column === 'bv_preview') {
        if (get_post_meta($post_id, '_bv_nyhetsbrev_html', true)) {
            $url = add_query_arg('bimverdi_nyhetsbrev_preview', $post_id, home_url('/'));
            echo '<a href="' . esc_url($url) . '" target="_blank" class="button button-small">Åpne</a>';
        } else {
            echo '<span style="color:#999;">—</span>';
        }
    }
}, 10, 2);

/* -------------------------------------------------------------------------
 * 6. Meta-boks på redigeringsskjermen: status, forhåndsvis, send (forberedt).
 * ---------------------------------------------------------------------- */

add_action('add_meta_boxes_' . BV_NYHETSBREV_CPT, function () {
    add_meta_box(
        'bv_nyhetsbrev_handlinger',
        'Nyhetsbrev',
        'bimverdi_nyhetsbrev_metaboks',
        BV_NYHETSBREV_CPT,
        'side',
        'high'
    );
});

function bimverdi_nyhetsbrev_metaboks($post) {
    $generated = get_post_meta($post->ID, '_bv_nyhetsbrev_generated_at', true);
    $count     = (int) get_post_meta($post->ID, '_bv_nyhetsbrev_item_count', true);
    $has_html  = (bool) get_post_meta($post->ID, '_bv_nyhetsbrev_html', true);
    $sent_at   = get_post_meta($post->ID, '_bv_nyhetsbrev_sent_at', true);
    $preview   = add_query_arg('bimverdi_nyhetsbrev_preview', $post->ID, home_url('/'));

    echo '<div style="font-size:13px;line-height:1.6;">';

    if ($sent_at) {
        echo '<p><strong style="color:#1a7d3c;">Sendt</strong> ' . esc_html(bimverdi_nyhetsbrev_dato_nb($sent_at, true)) . '</p>';
    } elseif ($has_html) {
        echo '<p><strong style="color:#8a6d00;">Kladd</strong> — ikke sendt ennå</p>';
    } else {
        echo '<p style="color:#b32d2e;">Ingen øyeblikksbilde generert ennå.</p>';
    }

    if ($generated) {
        echo '<p style="color:#5A5A5A;">Generert ' . esc_html(bimverdi_nyhetsbrev_dato_nb($generated, true))
           . '<br>' . esc_html($count) . ' innholdselement' . ($count === 1 ? '' : 'er') . '</p>';
    }

    if ($has_html) {
        echo '<p><a href="' . esc_url($preview) . '" target="_blank" class="button button-secondary" style="width:100%;text-align:center;box-sizing:border-box;">Forhåndsvis i nettleser</a></p>';
    }

    // Oppdater øyeblikksbildet (kun kladd, og kun før en utsendelse er startet
    // — etter start er øyeblikksbildet låst mot manifestets HTML-hash).
    $utsendelse_startet = function_exists('bimverdi_nyhetsbrev_utsendelse_startet') && bimverdi_nyhetsbrev_utsendelse_startet($post->ID);
    if (!$sent_at && !$utsendelse_startet) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin:8px 0 0 0;">';
        echo '<input type="hidden" name="action" value="bimverdi_oppdater_nyhetsbrev">';
        echo '<input type="hidden" name="post_id" value="' . esc_attr($post->ID) . '">';
        wp_nonce_field('bimverdi_oppdater_nyhetsbrev_' . $post->ID);
        echo '<button type="submit" class="button button-secondary" style="width:100%;">Oppdater øyeblikksbilde</button>';
        echo '<span style="display:block;color:#5A5A5A;font-size:12px;margin-top:4px;">Henter inn dagens ferskeste innhold på nytt.</span>';
        echo '</form>';
    } elseif (!$sent_at && $utsendelse_startet) {
        echo '<p style="color:#5A5A5A;font-size:12px;margin:8px 0 0;">🔒 Øyeblikksbildet er låst — en utsendelse er startet.</p>';
    }

    // Test-utsendelse (intern): kun til egen e-post + wp-config-allowlist.
    if ($has_html) {
        $min_epost = wp_get_current_user()->user_email;
        echo '<hr style="margin:14px 0;border:none;border-top:1px solid #e0e0e0;">';
        echo '<p style="margin:0 0 6px 0;"><strong>Test-utsendelse (intern)</strong></p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin:0;">';
        echo '<input type="hidden" name="action" value="bimverdi_nyhetsbrev_send_test">';
        echo '<input type="hidden" name="post_id" value="' . esc_attr($post->ID) . '">';
        wp_nonce_field('bimverdi_nyhetsbrev_send_test_' . $post->ID);
        echo '<input type="text" name="test_epost" value="' . esc_attr($min_epost) . '" '
           . 'style="width:100%;margin-bottom:6px;" placeholder="navn@adresse.no">';
        echo '<button type="submit" class="button button-secondary" style="width:100%;">Send test</button>';
        echo '<span style="display:block;color:#5A5A5A;font-size:12px;margin-top:4px;">Kun din egen e-post '
           . 'og adresser i <code>BIMVERDI_NYHETSBREV_TEST_MOTTAKERE</code> (wp-config) er tillatt. '
           . 'Emnet prefikses med [TEST].</span>';
        echo '</form>';

        // Siste testutsendelser.
        $logg = get_post_meta($post->ID, '_bv_nyhetsbrev_test_log', true);
        if (is_array($logg) && $logg) {
            echo '<p style="margin:10px 0 2px 0;color:#5A5A5A;font-size:12px;"><strong>Siste tester:</strong></p>';
            foreach (array_slice(array_reverse($logg), 0, 3) as $rad) {
                echo '<span style="display:block;color:#5A5A5A;font-size:12px;">'
                   . esc_html(bimverdi_nyhetsbrev_dato_nb($rad['tid'], true)) . ' → '
                   . esc_html(implode(', ', (array) $rad['til']))
                   . ($rad['ok'] ? '' : ' <span style="color:#b32d2e;">(feilet)</span>')
                   . '</span>';
            }
        }
    }

    // Massesend (Unit 4): rød knapp / rapport / Fortsett / manuell verifisering.
    echo '<hr style="margin:14px 0;border:none;border-top:1px solid #e0e0e0;">';
    bimverdi_nyhetsbrev_metaboks_massesend($post);

    echo '</div>';
}

/**
 * Massesend-seksjonen i metaboksen. Tilstandene (a)–(f) fra planen:
 *   (a) gate ikke klar (konstant/miljø)  → død knapp + forklaring
 *   (b) klar, ikke startet               → rød «Send til N mottakere…»
 *   (c) kjøring pågår (fersk heartbeat)  → rapport + deaktivert Fortsett
 *   (d) delvis                           → rapport + «Fortsett utsendelse»
 *   (e) usikre batcher > 20 t            → per-batch manuell verifisering
 *   (f) fullført                         → «Sendt <dato>»
 */
function bimverdi_nyhetsbrev_metaboks_massesend($post) {
    $post_id = $post->ID;
    $admin_post = esc_url(admin_url('admin-post.php'));

    echo '<p style="margin:0 0 8px 0;"><strong>Masseutsendelse</strong></p>';

    // (f) Fullført.
    if (bimverdi_nyhetsbrev_er_sendt($post_id)) {
        $status = bimverdi_nyhetsbrev_utsendelse_status($post_id);
        $sent   = get_post_meta($post_id, '_bv_nyhetsbrev_sent_at', true);
        echo '<p style="color:#1a7d3c;font-weight:600;margin:0 0 4px;">✓ Sendt ' . esc_html(bimverdi_nyhetsbrev_dato_nb($sent, true)) . '</p>';
        if ($status) {
            echo '<p style="color:#5A5A5A;font-size:12px;margin:0;">' . (int) $status['mottakere_sendt'] . ' mottakere.</p>';
        }
        return;
    }

    $manifest = function_exists('bimverdi_nyhetsbrev_utsendelse_manifest')
        ? bimverdi_nyhetsbrev_utsendelse_manifest($post_id) : null;

    // Ikke startet → (a) eller (b).
    if (!$manifest) {
        if (!get_post_meta($post_id, '_bv_nyhetsbrev_html', true)) {
            echo '<p style="color:#5A5A5A;font-size:12px;margin:0;">Generer et øyeblikksbilde før du kan sende.</p>';
            return;
        }

        $antall = count(bimverdi_nyhetsbrev_mottakere());
        echo '<p style="margin:0 0 6px 0;color:#5A5A5A;font-size:12px;">' . (int) $antall
           . ' medlemmer står klare som mottakere (avmeldte er trukket fra).</p>';

        $gate = function_exists('bimverdi_nyhetsbrev_massesend_gate')
            ? bimverdi_nyhetsbrev_massesend_gate() : new WP_Error('mangler', '');

        if (is_wp_error($gate)) {
            // (a) Død knapp + forklaring på hvorfor gaten er stengt.
            echo '<button type="button" class="button" style="width:100%;" disabled>Send til alle mottakere</button>';
            echo '<span style="display:block;color:#5A5A5A;font-size:12px;margin-top:6px;">🔒 '
               . esc_html($gate->get_error_message())
               . ' Masseutsendelse kjører kun på produksjon med <code>BIMVERDI_NYHETSBREV_MASSESEND_AKTIV</code> satt.</span>';
            return;
        }

        // (b) Klar → rød knapp som går til bekreftelses-mellomsiden.
        echo '<form method="post" action="' . $admin_post . '" style="margin:0;">';
        echo '<input type="hidden" name="action" value="bimverdi_nyhetsbrev_bekreft">';
        echo '<input type="hidden" name="post_id" value="' . esc_attr($post_id) . '">';
        wp_nonce_field('bimverdi_nyhetsbrev_bekreft_' . $post_id);
        echo '<button type="submit" class="button button-primary" style="width:100%;background:#FF8B5E;border-color:#FF8B5E;color:#1A1A1A;">'
           . 'Send til ' . (int) $antall . ' mottakere…</button>';
        echo '<span style="display:block;color:#5A5A5A;font-size:12px;margin-top:4px;">Du får en bekreftelsesside før noe sendes.</span>';
        echo '</form>';
        return;
    }

    // Manifest finnes → vis rapport + neste handling.
    $status = bimverdi_nyhetsbrev_utsendelse_status($post_id);
    $las    = function_exists('bimverdi_nyhetsbrev_las_info') ? bimverdi_nyhetsbrev_las_info($post_id) : null;
    $b      = $status['batcher'];
    $karantene = function_exists('bimverdi_nyhetsbrev_karantene_liste') ? count(bimverdi_nyhetsbrev_karantene_liste($post_id)) : 0;

    // Rapport.
    echo '<div style="background:#F7F5EF;border-radius:6px;padding:10px 12px;font-size:12px;color:#1A1A1A;line-height:1.7;margin:0 0 10px;">';
    echo '<strong>' . (int) $status['mottakere_sendt'] . '</strong> av ' . (int) $status['mottakere_total'] . ' sendt<br>';
    $deler = array();
    if ($b['sendt'])          { $deler[] = $b['sendt'] . ' batch sendt'; }
    if ($b['pending'])        { $deler[] = '<span style="color:#8a6d00;">' . $b['pending'] . ' venter</span>'; }
    if ($b['usikker'] + $b['delvis_usikker']) { $deler[] = '<span style="color:#b32d2e;">' . ($b['usikker'] + $b['delvis_usikker']) . ' usikre</span>'; }
    if ($karantene)           { $deler[] = $karantene . ' i karantene'; }
    echo implode(' · ', $deler);
    echo '</div>';

    // (c) Kjøring pågår — fersk heartbeat → deaktivert Fortsett.
    if ($las && $las['fersk']) {
        echo '<button type="button" class="button" style="width:100%;" disabled>Kjøring pågår…</button>';
        echo '<span style="display:block;color:#5A5A5A;font-size:12px;margin-top:4px;">Startet av ' . esc_html($las['av'])
           . '. Last siden på nytt om litt.</span>';
        return;
    }

    // (e) Batcher som krever manuell verifisering (24-timers-vakten).
    $til_verifisering = array();
    $sperret = array();
    foreach (bimverdi_nyhetsbrev_batch_rader($post_id) as $i => $rad) {
        if (!empty($rad['manuell_verifisering'])) { $til_verifisering[$i] = $rad; }
        elseif (!empty($rad['sperret']))          { $sperret[$i] = $rad; }
    }

    if ($til_verifisering) {
        echo '<div style="border:1px solid #e0b400;background:#fdf6e3;border-radius:6px;padding:10px 12px;margin:0 0 10px;">';
        echo '<p style="margin:0 0 6px;font-size:12px;color:#1A1A1A;"><strong>Krever manuell verifisering</strong><br>'
           . 'Disse batchene er eldre enn 20 t og kan ikke re-sendes blindt. '
           . 'Sjekk <a href="https://resend.com/emails" target="_blank">Resend-dashboardet</a> og velg per batch:</p>';
        foreach ($til_verifisering as $i => $rad) {
            $antall_b = count($rad['mottakere']);
            echo '<div style="border-top:1px solid #f0e3b0;padding-top:8px;margin-top:8px;">';
            echo '<p style="margin:0 0 4px;font-size:12px;color:#5A5A5A;">Batch ' . (int) $i . ' (' . (int) $antall_b . ' mottakere)</p>';
            foreach (array('marker_sendt' => 'Marker som sendt (verifisert levert)', 'resend' => 'Re-send (verifisert IKKE levert)') as $valg => $tekst) {
                echo '<form method="post" action="' . $admin_post . '" style="display:inline-block;margin:0 4px 4px 0;">';
                echo '<input type="hidden" name="action" value="bimverdi_nyhetsbrev_verifiser">';
                echo '<input type="hidden" name="post_id" value="' . esc_attr($post_id) . '">';
                echo '<input type="hidden" name="batch_indeks" value="' . esc_attr($i) . '">';
                echo '<input type="hidden" name="valg" value="' . esc_attr($valg) . '">';
                wp_nonce_field('bimverdi_nyhetsbrev_verifiser_' . $post_id);
                $stil = $valg === 'resend' ? 'color:#b32d2e;' : '';
                echo '<button type="submit" class="button button-small" style="' . $stil . '">' . esc_html($tekst) . '</button>';
                echo '</form>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    if ($sperret) {
        echo '<p style="color:#b32d2e;font-size:12px;margin:0 0 10px;">⚠ ' . count($sperret)
           . ' batch avvist (4xx) uten identifiserbar mottaker — krever manuell håndtering av utvikler.</p>';
    }

    // (d) Fortsett-knapp (også for å overta en stale lås).
    $stale = $las && !$las['fersk'];
    echo '<form method="post" action="' . $admin_post . '" style="margin:0;">';
    echo '<input type="hidden" name="action" value="bimverdi_nyhetsbrev_fortsett">';
    echo '<input type="hidden" name="post_id" value="' . esc_attr($post_id) . '">';
    if ($stale) {
        echo '<input type="hidden" name="overta_stale" value="1">';
    }
    wp_nonce_field('bimverdi_nyhetsbrev_fortsett_' . $post_id);
    $etikett = $stale ? 'Overta stale lås og fortsett' : 'Fortsett utsendelse';
    echo '<button type="submit" class="button button-primary" style="width:100%;">' . esc_html($etikett) . '</button>';
    if ($stale) {
        echo '<span style="display:block;color:#b32d2e;font-size:12px;margin-top:4px;">Forrige kjøring stoppet uten å slippe låsen ('
           . (int) $las['alder'] . ' s siden).</span>';
    }
    echo '</form>';
}
