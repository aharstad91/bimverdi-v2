<?php
/**
 * BIM Verdi - E-postlogg (hendelser fra Resend)
 *
 * Resend sender en webhook til nettstedet for hver hendelse på en e-post:
 * sendt, levert, forsinket, avvist (bounce), klage (spam), åpnet, klikket,
 * feilet. Denne pluginen tar imot hendelsene, kontrollerer signaturen og
 * lagrer siste status per e-post i egen tabell.
 *
 * Nyhetsbrevet viser loggen per mottaker i en egen boks i redigeringen
 * (Bård, Trello #361 pkt 1.1: «logg for brukere som åpner, leser, bouncer,
 * returnerer, melder av»).
 *
 * OPPSETT
 *   1. Webhook i Resend → https://bimverdi.no/wp-json/bimverdi/v1/resend-hendelse
 *   2. Signeringsnøkkelen (whsec_…) fra Resend → wp-config:
 *        define('BIMVERDI_RESEND_WEBHOOK_SECRET', 'whsec_…');
 *   Uten nøkkelen avvises alle kall (fail-closed).
 *
 * Åpnet/klikket kommer bare når sporing er slått på for domenet i Resend.
 *
 * Eldre utsendelser: `wp bv-epostlogg importer <fil.json>` legger inn status
 * hentet fra Resend i ettertid (liste av {id, to, last_event, created_at}).
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BV_EPOSTLOGG_DB_VERSJON', '1');

/** Tabellnavnet. */
function bimverdi_epostlogg_tabell() {
    global $wpdb;
    return $wpdb->prefix . 'bv_epost_logg';
}

/* -------------------------------------------------------------------------
 * 1. Tabell
 * ---------------------------------------------------------------------- */

add_action('init', function () {
    if (get_option('bv_epostlogg_db_versjon') === BV_EPOSTLOGG_DB_VERSJON) {
        return;
    }
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Én rad per e-post (Resend-ID). hendelser = JSON-liste over alt som har skjedd.
    dbDelta(sprintf(
        "CREATE TABLE %s (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email_id varchar(64) NOT NULL,
            epost varchar(190) NOT NULL DEFAULT '',
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            nyhetsbrev_id bigint(20) unsigned NOT NULL DEFAULT 0,
            emne varchar(255) NOT NULL DEFAULT '',
            status varchar(32) NOT NULL DEFAULT '',
            detalj text NULL,
            hendelser longtext NULL,
            sendt datetime NULL,
            oppdatert datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY email_id (email_id),
            KEY epost (epost),
            KEY nyhetsbrev_id (nyhetsbrev_id)
        ) %s;",
        bimverdi_epostlogg_tabell(),
        $wpdb->get_charset_collate()
    ));

    update_option('bv_epostlogg_db_versjon', BV_EPOSTLOGG_DB_VERSJON, false);
});

/* -------------------------------------------------------------------------
 * 2. Status
 * ---------------------------------------------------------------------- */

/**
 * Statusene i rekkefølge etter vekt. En e-post beholder den tyngste
 * statusen den har fått: en åpnet e-post blir ikke «levert» igjen om
 * levert-hendelsen kommer etterpå.
 *
 * @return array<string,string> status => norsk visningstekst
 */
function bimverdi_epostlogg_statuser() {
    return [
        'sendt'     => 'Sendt',
        'forsinket' => 'Forsinket',
        'levert'    => 'Levert',
        'apnet'     => 'Åpnet',
        'klikket'   => 'Klikket',
        'feilet'    => 'Feilet',
        'stoppet'   => 'Stoppet (sperreliste)',
        'avvist'    => 'Avvist',
        'klage'     => 'Merket som spam',
    ];
}

/** Resend-hendelse → vår status. Null for hendelser vi ikke bryr oss om. */
function bimverdi_epostlogg_status_fra_resend($type) {
    $kart = [
        'email.sent'             => 'sendt',
        'email.delivery_delayed' => 'forsinket',
        'email.delivered'        => 'levert',
        'email.opened'           => 'apnet',
        'email.clicked'          => 'klikket',
        'email.failed'           => 'feilet',
        'email.suppressed'       => 'stoppet',
        'email.bounced'          => 'avvist',
        'email.complained'       => 'klage',
        // last_event fra Resend-API-et (import) bruker korte navn.
        'sent'             => 'sendt',
        'delivery_delayed' => 'forsinket',
        'delivered'        => 'levert',
        'opened'           => 'apnet',
        'clicked'          => 'klikket',
        'failed'           => 'feilet',
        'suppressed'       => 'stoppet',
        'bounced'          => 'avvist',
        'complained'       => 'klage',
    ];
    return $kart[$type] ?? null;
}

/** Den tyngste av to statuser. */
function bimverdi_epostlogg_tyngst($a, $b) {
    $rekke = array_keys(bimverdi_epostlogg_statuser());
    $ia    = array_search($a, $rekke, true);
    $ib    = array_search($b, $rekke, true);
    return ($ia === false ? -1 : $ia) >= ($ib === false ? -1 : $ib) ? $a : $b;
}

/**
 * Registrer én hendelse på en e-post. Oppretter raden første gang.
 *
 * @param string $email_id Resend-ID.
 * @param string $status   Vår status (se bimverdi_epostlogg_statuser()).
 * @param array  $info     epost, user_id, nyhetsbrev_id, emne, tid (Y-m-d H:i:s, UTC), detalj.
 */
function bimverdi_epostlogg_registrer($email_id, $status, array $info = []) {
    global $wpdb;
    $tabell = bimverdi_epostlogg_tabell();
    $tid    = !empty($info['tid']) ? $info['tid'] : current_time('mysql', true);

    $rad = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabell} WHERE email_id = %s", $email_id), ARRAY_A);

    $hendelse = array_filter([
        'status' => $status,
        'tid'    => $tid,
        'detalj' => $info['detalj'] ?? '',
    ]);

    if (!$rad) {
        $wpdb->insert($tabell, [
            'email_id'      => $email_id,
            'epost'         => strtolower((string) ($info['epost'] ?? '')),
            'user_id'       => (int) ($info['user_id'] ?? 0),
            'nyhetsbrev_id' => (int) ($info['nyhetsbrev_id'] ?? 0),
            'emne'          => (string) ($info['emne'] ?? ''),
            'status'        => $status,
            'detalj'        => (string) ($info['detalj'] ?? ''),
            'hendelser'     => wp_json_encode([$hendelse]),
            'sendt'         => $status === 'sendt' ? $tid : null,
            'oppdatert'     => current_time('mysql', true),
        ]);
        return;
    }

    $hendelser   = json_decode((string) $rad['hendelser'], true) ?: [];
    $hendelser[] = $hendelse;
    $ny_status   = bimverdi_epostlogg_tyngst($rad['status'], $status);

    $endring = [
        'status'    => $ny_status,
        'hendelser' => wp_json_encode(array_slice($hendelser, -50)),
        'oppdatert' => current_time('mysql', true),
    ];
    // Detaljen følger statusen som vises (f.eks. avvisningsgrunnen).
    if ($ny_status === $status && !empty($info['detalj'])) {
        $endring['detalj'] = (string) $info['detalj'];
    }
    foreach (['epost', 'user_id', 'nyhetsbrev_id', 'emne'] as $felt) {
        if (empty($rad[$felt]) && !empty($info[$felt])) {
            $endring[$felt] = $felt === 'epost' ? strtolower($info[$felt]) : $info[$felt];
        }
    }
    if ($status === 'sendt' && empty($rad['sendt'])) {
        $endring['sendt'] = $tid;
    }

    $wpdb->update($tabell, $endring, ['id' => (int) $rad['id']]);
}

/* -------------------------------------------------------------------------
 * 3. Webhook fra Resend
 * ---------------------------------------------------------------------- */

/**
 * Kontroller Svix-signaturen Resend bruker. Signert innhold er
 * "{svix-id}.{svix-timestamp}.{body}", HMAC-SHA256 med den base64-dekodede
 * nøkkelen etter «whsec_». Tidsstempelet må være innenfor 5 minutter.
 */
function bimverdi_epostlogg_signatur_ok($body, $id, $ts, $signaturer) {
    if (!defined('BIMVERDI_RESEND_WEBHOOK_SECRET') || !BIMVERDI_RESEND_WEBHOOK_SECRET) {
        return false;
    }
    if (!$id || !ctype_digit((string) $ts) || abs(time() - (int) $ts) > 300) {
        return false;
    }

    $hemmelig = base64_decode(preg_replace('/^whsec_/', '', BIMVERDI_RESEND_WEBHOOK_SECRET), true);
    if ($hemmelig === false) {
        return false;
    }
    $forventet = base64_encode(hash_hmac('sha256', $id . '.' . $ts . '.' . $body, $hemmelig, true));

    foreach (preg_split('/\s+/', trim((string) $signaturer)) as $del) {
        $deler = explode(',', $del, 2);
        if (count($deler) === 2 && $deler[0] === 'v1' && hash_equals($forventet, $deler[1])) {
            return true;
        }
    }
    return false;
}

/** Tags fra Resend kommer som objekt ({navn: verdi}) eller liste ([{name, value}]). */
function bimverdi_epostlogg_tag($tags, $navn) {
    if (!is_array($tags)) {
        return '';
    }
    if (isset($tags[$navn]) && !is_array($tags[$navn])) {
        return (string) $tags[$navn];
    }
    foreach ($tags as $tag) {
        if (is_array($tag) && ($tag['name'] ?? '') === $navn) {
            return (string) ($tag['value'] ?? '');
        }
    }
    return '';
}

add_action('rest_api_init', function () {
    register_rest_route('bimverdi/v1', '/resend-hendelse', [
        'methods'             => 'POST',
        'permission_callback' => '__return_true', // Signaturen er tilgangskontrollen.
        'callback'            => function (WP_REST_Request $req) {
            $body = $req->get_body();

            if (!bimverdi_epostlogg_signatur_ok(
                $body,
                $req->get_header('svix-id'),
                $req->get_header('svix-timestamp'),
                $req->get_header('svix-signature')
            )) {
                return new WP_REST_Response(['ok' => false], 401);
            }

            $hendelse = json_decode($body, true);
            $data     = is_array($hendelse['data'] ?? null) ? $hendelse['data'] : [];
            $status   = bimverdi_epostlogg_status_fra_resend((string) ($hendelse['type'] ?? ''));

            // Ukjente hendelser kvitteres med 200, så Resend ikke prøver igjen.
            if (!$status || empty($data['email_id'])) {
                return new WP_REST_Response(['ok' => true, 'ignorert' => true], 200);
            }

            $detalj = '';
            if (!empty($data['bounce']['message'])) {
                $detalj = $data['bounce']['message'];
            } elseif (!empty($data['failed']['reason'])) {
                $detalj = $data['failed']['reason'];
            } elseif (!empty($data['click']['link'])) {
                $detalj = $data['click']['link'];
            }

            $tid = !empty($hendelse['created_at']) ? strtotime($hendelse['created_at']) : false;

            bimverdi_epostlogg_registrer((string) $data['email_id'], $status, [
                'epost'         => is_array($data['to'] ?? null) ? (string) reset($data['to']) : (string) ($data['to'] ?? ''),
                'user_id'       => (int) bimverdi_epostlogg_tag($data['tags'] ?? null, 'bruker'),
                'nyhetsbrev_id' => (int) bimverdi_epostlogg_tag($data['tags'] ?? null, 'nyhetsbrev'),
                'emne'          => (string) ($data['subject'] ?? ''),
                'tid'           => $tid ? gmdate('Y-m-d H:i:s', $tid) : '',
                'detalj'        => mb_substr((string) $detalj, 0, 500),
            ]);

            return new WP_REST_Response(['ok' => true], 200);
        },
    ]);
});

/* -------------------------------------------------------------------------
 * 4. Loggen for ett nyhetsbrev
 * ---------------------------------------------------------------------- */

/**
 * Én rad per mottaker i utsendelsen, med siste status fra Resend.
 * Mottakerne og Resend-ID-ene hentes fra batch-radene (ID nr. i hører til
 * mottaker nr. i). Mottakere uten ID er ikke sendt ennå.
 *
 * @return array[] epost, user_id, navn, status, detalj, avmeldt
 */
function bimverdi_epostlogg_for_nyhetsbrev($post_id) {
    global $wpdb;
    if (!function_exists('bimverdi_nyhetsbrev_batch_rader')) {
        return [];
    }

    $mottakere = [];
    foreach (bimverdi_nyhetsbrev_batch_rader($post_id) as $rad) {
        if (!in_array($rad['status'] ?? '', ['sendt', 'delvis_usikker'], true)) {
            continue;
        }
        foreach (array_values((array) ($rad['mottakere'] ?? [])) as $i => $m) {
            $mottakere[] = [
                'epost'    => strtolower((string) $m['email']),
                'user_id'  => (int) ($m['id'] ?? 0),
                'email_id' => (string) ($rad['resend_ids'][$i] ?? ''),
            ];
        }
    }
    if (!$mottakere) {
        return [];
    }

    $ider   = array_filter(wp_list_pluck($mottakere, 'email_id'));
    $logg   = [];
    $tabell = bimverdi_epostlogg_tabell();
    foreach (array_chunk($ider, 200) as $bit) {
        $plass = implode(',', array_fill(0, count($bit), '%s'));
        $rader = $wpdb->get_results($wpdb->prepare(
            "SELECT email_id, status, detalj FROM {$tabell} WHERE email_id IN ({$plass})",
            $bit
        ), ARRAY_A);
        foreach ($rader as $r) {
            $logg[$r['email_id']] = $r;
        }
    }

    $ut = [];
    foreach ($mottakere as $m) {
        $bruker  = $m['user_id'] ? get_userdata($m['user_id']) : false;
        $avmeldt = $m['user_id'] ? (string) get_user_meta($m['user_id'], 'bv_nyhetsbrev_avmeldt', true) : '';
        $treff   = $logg[$m['email_id']] ?? null;
        $ut[] = [
            'epost'   => $m['epost'],
            'user_id' => $m['user_id'],
            'navn'    => $bruker ? $bruker->display_name : '',
            'status'  => $treff ? $treff['status'] : ($m['email_id'] ? 'ukjent' : 'ikke_sendt'),
            'detalj'  => $treff ? (string) $treff['detalj'] : '',
            'avmeldt' => $avmeldt,
        ];
    }
    return $ut;
}

add_action('add_meta_boxes_nyhetsbrev', function ($post) {
    if (!function_exists('bimverdi_nyhetsbrev_utsendelse_startet') || !bimverdi_nyhetsbrev_utsendelse_startet($post->ID)) {
        return;
    }
    add_meta_box('bv_epostlogg', 'Utsendingslogg', 'bimverdi_epostlogg_metaboks', 'nyhetsbrev', 'normal', 'default');
});

/** Teksten for statuser som ikke kommer fra Resend. */
function bimverdi_epostlogg_status_tekst($status) {
    $alle = bimverdi_epostlogg_statuser() + [
        'ukjent'     => 'Sendt, ingen melding fra Resend',
        'ikke_sendt' => 'Ikke sendt',
    ];
    return $alle[$status] ?? $status;
}

function bimverdi_epostlogg_metaboks($post) {
    $rader = bimverdi_epostlogg_for_nyhetsbrev($post->ID);
    if (!$rader) {
        echo '<p>Ingen mottakere er sendt ennå.</p>';
        return;
    }

    $telling = [];
    $avmeldt = 0;
    foreach ($rader as $r) {
        $telling[$r['status']] = ($telling[$r['status']] ?? 0) + 1;
        $avmeldt += $r['avmeldt'] ? 1 : 0;
    }

    $csv_url = wp_nonce_url(admin_url('admin-post.php?action=bv_epostlogg_csv&post_id=' . (int) $post->ID), 'bv_epostlogg_csv_' . (int) $post->ID);

    echo '<p style="margin:0 0 8px;">' . sprintf('%d mottakere.', count($rader)) . ' ';
    $deler = [];
    foreach ($telling as $status => $antall) {
        $deler[] = esc_html(bimverdi_epostlogg_status_tekst($status)) . ': <strong>' . (int) $antall . '</strong>';
    }
    $deler[] = 'Meldt av siden: <strong>' . (int) $avmeldt . '</strong>';
    echo implode(' · ', $deler) . '</p>';

    echo '<p style="margin:0 0 12px;font-size:12px;color:#5A5A5A;">Status kommer fra Resend. «Åpnet» og «Klikket» vises bare når sporing er slått på i Resend. '
       . '<a href="' . esc_url($csv_url) . '">Last ned som CSV</a></p>';

    // Avvist/klage/feilet øverst — det er dem Bård må rydde i.
    $vekt = ['klage' => 0, 'avvist' => 1, 'feilet' => 2, 'stoppet' => 3, 'forsinket' => 4, 'ukjent' => 5, 'ikke_sendt' => 6];
    usort($rader, function ($a, $b) use ($vekt) {
        return [$vekt[$a['status']] ?? 9, $a['epost']] <=> [$vekt[$b['status']] ?? 9, $b['epost']];
    });

    echo '<div style="max-height:420px;overflow:auto;border:1px solid #D6D1C6;">';
    echo '<table class="widefat striped" style="border:0;"><thead><tr><th>Mottaker</th><th>E-post</th><th>Status</th><th>Meldt av</th></tr></thead><tbody>';
    foreach ($rader as $r) {
        $navn = $r['navn'];
        if ($r['user_id']) {
            $navn = '<a href="' . esc_url(get_edit_user_link($r['user_id'])) . '">' . esc_html($navn ?: '(uten navn)') . '</a>';
        } else {
            $navn = '—';
        }
        echo '<tr><td>' . $navn . '</td><td>' . esc_html($r['epost']) . '</td><td>'
           . esc_html(bimverdi_epostlogg_status_tekst($r['status']))
           . ($r['detalj'] ? '<br><span style="font-size:11px;color:#5A5A5A;">' . esc_html($r['detalj']) . '</span>' : '')
           . '</td><td>' . esc_html($r['avmeldt'] ? mysql2date('d.m.Y', $r['avmeldt']) : '') . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

add_action('admin_post_bv_epostlogg_csv', function () {
    $post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
    if (!current_user_can('manage_options')) {
        wp_die('Ingen tilgang.');
    }
    check_admin_referer('bv_epostlogg_csv_' . $post_id);

    nocache_headers();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="utsendingslogg-' . $post_id . '.csv"');

    $ut = fopen('php://output', 'w');
    fwrite($ut, "\xEF\xBB\xBF"); // BOM, så Excel leser æøå riktig.
    fputcsv($ut, ['Navn', 'E-post', 'Status', 'Detalj', 'Meldt av'], ';');
    foreach (bimverdi_epostlogg_for_nyhetsbrev($post_id) as $r) {
        fputcsv($ut, [$r['navn'], $r['epost'], bimverdi_epostlogg_status_tekst($r['status']), $r['detalj'], $r['avmeldt']], ';');
    }
    fclose($ut);
    exit;
});

/* -------------------------------------------------------------------------
 * 5. Import av eldre status (WP-CLI)
 * ---------------------------------------------------------------------- */

if (defined('WP_CLI') && WP_CLI) {
    /**
     * Legg inn status for e-poster sendt før webhooken fantes.
     *
     * ## OPTIONS
     *
     * <fil>
     * : JSON-liste av {id, to, last_event, created_at, subject} fra Resend.
     *
     * [--nyhetsbrev=<id>]
     * : Knytt e-postene til dette nyhetsbrevet.
     *
     * ## EXAMPLES
     *     wp bv-epostlogg importer resend.json --nyhetsbrev=6314
     */
    WP_CLI::add_command('bv-epostlogg importer', function ($args, $assoc) {
        $liste = json_decode((string) file_get_contents($args[0]), true);
        if (!is_array($liste)) {
            WP_CLI::error('Fila er ikke en JSON-liste.');
        }
        $antall = 0;
        foreach ($liste as $e) {
            $status = bimverdi_epostlogg_status_fra_resend((string) ($e['last_event'] ?? ''));
            if (!$status || empty($e['id'])) {
                continue;
            }
            $til = is_array($e['to'] ?? null) ? reset($e['to']) : ($e['to'] ?? '');
            $tid = !empty($e['created_at']) ? strtotime($e['created_at']) : false;
            bimverdi_epostlogg_registrer((string) $e['id'], $status, [
                'epost'         => (string) $til,
                'nyhetsbrev_id' => (int) ($assoc['nyhetsbrev'] ?? 0),
                'emne'          => (string) ($e['subject'] ?? ''),
                'tid'           => $tid ? gmdate('Y-m-d H:i:s', $tid) : '',
            ]);
            $antall++;
        }
        WP_CLI::success(sprintf('%d e-poster lagt inn.', $antall));
    });
}
