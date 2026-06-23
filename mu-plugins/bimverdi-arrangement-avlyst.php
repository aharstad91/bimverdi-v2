<?php
/**
 * Plugin Name: BIM Verdi – Avlyst-varsel for arrangementer
 * Description: Manuell knapp på arrangement-edit som sender «avlyst»-e-post til
 *              påmeldte deltakere. Kun e-post (ingen SMS). Jf. Teams/synk 22.06.
 *
 * 🔒 SIKKERHETSGATE (Andreas 22.06 → live på prod 23.06 etter godkjent testkopi):
 *    MILJØSTYRT. På PROD (home_url = https://bimverdi.no) er gaten AV → varselet
 *    går til de ekte påmeldte. I ALLE andre miljøer (localhost/dev — som sender
 *    ekte e-post via Resend! — staging, WP-CLI uten korrekt home_url) er gaten PÅ
 *    → hele utsendingen redirigeres til allowlisten (andreas@aharstad.no).
 *    Nød-regating på prod: add_filter('bimverdi_avlyst_gate_active', '__return_true').
 *    Utsending er uansett ALDRI automatisk — en admin må trykke knappen i metaboksen.
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Allowlist: de eneste adressene som faktisk får avlyst-varsel mens gaten er på.
 * @return string[] små bokstaver
 */
function bimverdi_avlyst_allowlist() {
    $list = apply_filters('bimverdi_avlyst_allowlist', ['andreas@aharstad.no']);
    return array_values(array_unique(array_map('strtolower', array_filter((array) $list))));
}

/**
 * Er vi på produksjon? DB-basert (home_url), positiv + fail-closed — speiler
 * bimverdi_nyhetsbrev_er_prod(). Alt som IKKE er nøyaktig prod-domenet
 * (localhost — som sender ekte e-post via Resend — staging, LAN-IP, WP-CLI uten
 * korrekt home_url) regnes som utvikling og holder gaten PÅ.
 *
 * MERK: wp_get_environment_type() er ubrukelig her — den defaulter til
 * 'production' når WP_ENVIRONMENT_TYPE ikke er satt, så også localhost rapporterer
 * 'production'. home_url er det eneste signalet som faktisk skiller miljøene.
 */
function bimverdi_avlyst_er_prod() {
    return untrailingslashit(home_url()) === 'https://bimverdi.no';
}

/**
 * Er sikkerhetsgaten aktiv? true = send KUN til allowlist (ikke ekte deltakere).
 *
 * MILJØSTYRT: LIVE på prod (gate AV → sender til ekte påmeldte), TESTMODUS i alle
 * andre miljøer (gate PÅ → kun allowlist). Fortsatt overstyrbar via filter —
 * nød-regating på prod: add_filter('bimverdi_avlyst_gate_active', '__return_true').
 */
function bimverdi_avlyst_gate_active() {
    $default_gated = !bimverdi_avlyst_er_prod(); // prod → av (live); ellers → på (test)
    return (bool) apply_filters('bimverdi_avlyst_gate_active', $default_gated);
}

/**
 * Hent bekreftede påmeldte for et arrangement.
 * Håndterer dual ACF-feltnavn (arrangement/pamelding_arrangement,
 * bruker/pamelding_bruker) for bakoverkompatibilitet.
 *
 * @return array[] liste med ['user_id','name','email']
 */
function bimverdi_avlyst_get_participants($arrangement_id) {
    $arrangement_id = (int) $arrangement_id;
    if (!$arrangement_id) {
        return [];
    }

    $reg_ids = get_posts([
        'post_type'        => 'pamelding',
        'post_status'      => 'publish',
        'posts_per_page'   => -1,
        'fields'           => 'ids',
        'no_found_rows'    => true,
        'suppress_filters' => false,
        'meta_query'       => [
            'relation' => 'AND',
            [
                'relation' => 'OR',
                ['key' => 'arrangement', 'value' => $arrangement_id],
                ['key' => 'pamelding_arrangement', 'value' => $arrangement_id],
            ],
            ['key' => 'pamelding_status', 'value' => 'bekreftet'],
        ],
    ]);

    $participants = [];
    $seen_emails  = [];

    foreach ($reg_ids as $reg_id) {
        $user_id = get_field('bruker', $reg_id);
        if (!$user_id) {
            $user_id = get_field('pamelding_bruker', $reg_id);
        }
        if (is_array($user_id)) {
            $user_id = $user_id['ID'] ?? 0;
        }
        $user_id = (int) $user_id;
        if (!$user_id) {
            continue;
        }

        $user = get_userdata($user_id);
        if (!$user || !is_email($user->user_email)) {
            continue;
        }

        $email_lc = strtolower($user->user_email);
        if (isset($seen_emails[$email_lc])) {
            continue;
        }
        $seen_emails[$email_lc] = true;

        $participants[] = [
            'user_id' => $user_id,
            'name'    => $user->display_name ?: $user->user_login,
            'email'   => $user->user_email,
        ];
    }

    return $participants;
}

/**
 * Bygg HTML-innholdet i avlyst-e-posten.
 *
 * @param int    $arrangement_id
 * @param string $deltaker_navn  Mottakerens navn (kan være tomt).
 * @param bool   $is_gated       Sant når sikkerhetsgaten er på → vis test-banner.
 * @param int    $reell_antall   Antall ekte påmeldte (for test-banner).
 */
function bimverdi_avlyst_email_html($arrangement_id, $deltaker_navn, $is_gated, $reell_antall) {
    // Dekod HTML-entiteter (wptexturize gjør ' → &#8217;). Brødtekst esc_html'es
    // senere; emnefeltet (ren tekst) trenger ekte tegn for å unngå rå «&#8217;».
    $tittel = html_entity_decode(get_the_title($arrangement_id), ENT_QUOTES, 'UTF-8');
    $url    = get_permalink($arrangement_id);

    // Dato (ACF arrangement_dato lagret som Ymd) + tidspunkt.
    $dato_raw = get_post_meta($arrangement_id, 'arrangement_dato', true);
    $dato_str = '';
    if ($dato_raw) {
        $d = DateTime::createFromFormat('Ymd', $dato_raw);
        if ($d) {
            $dato_str = wp_date('j. F Y', $d->getTimestamp());
        }
    }
    $tid = get_post_meta($arrangement_id, 'tidspunkt_start', true);

    $hilsen = $deltaker_navn ? ('Hei ' . esc_html($deltaker_navn) . ',') : 'Hei,';

    $when = '';
    if ($dato_str) {
        $when = ' ' . esc_html($dato_str) . ($tid ? ' kl. ' . esc_html($tid) : '');
    }

    $test_banner = '';
    if ($is_gated) {
        $test_banner =
            '<div style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px;padding:12px 16px;margin:0 0 20px;font-size:13px;color:#92400E;">'
            . '<strong>Testkopi — sikkerhetsgate aktiv.</strong> Dette varselet er kun sendt til deg (andreas@aharstad.no). '
            . (int) $reell_antall . ' faktiske påmeldte ble IKKE varslet.'
            . '</div>';
    }

    ob_start();
    ?>
    <div style="font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;max-width:560px;margin:0 auto;color:#1A1A1A;line-height:1.6;">
        <?php echo $test_banner; ?>
        <p style="font-size:15px;"><?php echo $hilsen; ?></p>
        <p style="font-size:15px;">
            Vi må dessverre informere om at arrangementet
            <strong><?php echo esc_html($tittel); ?></strong><?php echo $when; ?>
            er <strong style="color:#772015;">avlyst</strong>.
        </p>
        <p style="font-size:15px;">
            Din påmelding er dermed annullert. Vi beklager eventuelle ulemper dette medfører,
            og håper å se deg på et senere arrangement.
        </p>
        <p style="font-size:15px;">
            Har du spørsmål, ta gjerne kontakt på
            <a href="mailto:post@bimverdi.no" style="color:#FF8B5E;">post@bimverdi.no</a>.
        </p>
        <p style="font-size:15px;margin-top:24px;">Vennlig hilsen<br>BIM Verdi</p>
        <p style="font-size:12px;color:#9B9B9B;margin-top:28px;border-top:1px solid #E8E8E8;padding-top:12px;">
            <a href="<?php echo esc_url($url); ?>" style="color:#9B9B9B;">Se arrangementet</a>
        </p>
    </div>
    <?php
    return trim(ob_get_clean());
}

/**
 * Send avlyst-varsel for et arrangement.
 *
 * @return array ['ok'=>bool,'sent'=>int,'reell_antall'=>int,'gated'=>bool,'mottakere'=>string[]]
 */
function bimverdi_avlyst_send($arrangement_id) {
    $arrangement_id = (int) $arrangement_id;
    $participants   = bimverdi_avlyst_get_participants($arrangement_id);
    $reell_antall   = count($participants);
    $gated          = bimverdi_avlyst_gate_active();

    // SIKKERHETSGATE: når på, redirigeres ALT til allowlisten.
    // Når av (eksplisitt åpnet), sendes til faktiske påmeldte.
    if ($gated) {
        $recipients = [];
        foreach (bimverdi_avlyst_allowlist() as $addr) {
            $recipients[] = ['name' => '', 'email' => $addr];
        }
    } else {
        $recipients = array_map(function ($p) {
            return ['name' => $p['name'], 'email' => $p['email']];
        }, $participants);
    }

    // Fail-closed: gate aktiv men ingen gyldige mottakere i allowlisten → IKKE
    // utfør og IKKE rapporter suksess (ellers maskeres en feilkonfigurert gate).
    if ($gated && empty($recipients)) {
        error_log('[bimverdi-avlyst] AVBRUTT: sikkerhetsgate aktiv men allowlist tom (arrangement ' . $arrangement_id . ')');
        return ['ok' => false, 'sent' => 0, 'reell_antall' => $reell_antall, 'gated' => true, 'empty_allowlist' => true];
    }

    // Dekod entiteter så emnefeltet (ren tekst) viser ekte ' i stedet for «&#8217;».
    // Strip CR/LF etter dekoding som egen barriere mot e-post-header-injection
    // (ikke bare stole på WP-/PHPMailer-sanitering). Beholder apostrof/anførselstegn.
    $subject = 'Avlyst: ' . trim(preg_replace('/[\r\n]+/', ' ', html_entity_decode(get_the_title($arrangement_id), ENT_QUOTES, 'UTF-8')));
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $sent    = 0;
    $failed  = 0;

    foreach ($recipients as $r) {
        if (!is_email($r['email'])) {
            continue;
        }
        $body = bimverdi_avlyst_email_html($arrangement_id, $r['name'], $gated, $reell_antall);
        $ok = wp_mail($r['email'], $subject, $body, $headers);
        if ($ok) {
            $sent++;
        } else {
            $failed++;
            error_log(sprintf('[bimverdi-avlyst] wp_mail FEILET for %s (arrangement %d)', $r['email'], $arrangement_id));
        }
    }

    update_post_meta($arrangement_id, '_bv_avlyst_varsel_sent_at', current_time('mysql'));
    update_post_meta($arrangement_id, '_bv_avlyst_varsel_sent_count', $sent);

    error_log(sprintf(
        '[bimverdi-avlyst] Arrangement %d: sendt=%d, feilet=%d, gate=%s, ekte_paameldte=%d (ikke varslet pga gate=%s)',
        $arrangement_id, $sent, $failed, $gated ? 'PÅ' : 'AV', $reell_antall, $gated ? 'ja' : 'nei'
    ));

    return [
        'ok'           => ($failed === 0),
        'sent'         => $sent,
        'reell_antall' => $reell_antall,
        'gated'        => $gated,
    ];
}

// ════════════════════════════════════════════════════════════════════
// ADMIN UI — metabox på arrangement-edit
// ════════════════════════════════════════════════════════════════════

add_action('add_meta_boxes_arrangement', function () {
    add_meta_box(
        'bv_arrangement_avlyst',
        'Avlyst-varsel',
        'bimverdi_avlyst_render_metabox',
        'arrangement',
        'side',
        'high'
    );
});

function bimverdi_avlyst_render_metabox($post) {
    $status   = get_field('arrangement_status', $post->ID) ?: get_post_meta($post->ID, 'arrangement_status', true);
    $sent_at  = get_post_meta($post->ID, '_bv_avlyst_varsel_sent_at', true);
    $allow    = bimverdi_avlyst_allowlist();
    $gated    = bimverdi_avlyst_gate_active();

    echo '<div style="font-size:13px;line-height:1.55;color:#1d2327;">';

    if ($status !== 'avlyst') {
        echo '<p style="margin:0;color:#646970;">Arrangementet er ikke <strong>avlyst</strong> ennå. '
           . 'Sett <em>Status&nbsp;→&nbsp;Avlyst</em> og oppdater, så dukker send-knappen opp her.</p>';
        echo '</div>';
        return;
    }

    $antall = count(bimverdi_avlyst_get_participants($post->ID));

    echo '<p style="margin:0 0 12px;"><strong>' . (int) $antall . '</strong> bekreftede påmeldte</p>';

    if ($gated) {
        echo '<div style="background:#f6f7f7;border-left:3px solid #c3c4c7;border-radius:0 4px 4px 0;padding:9px 12px;margin:0 0 12px;color:#50575e;">'
           . '<strong style="font-weight:600;">🔒 Testmodus.</strong> Varselet sendes kun til '
           . '<code style="background:#fff;padding:1px 5px;border-radius:3px;font-size:11px;">' . esc_html($allow[0] ?? '') . '</code>'
           . ' — de ' . (int) $antall . ' påmeldte varsles ikke.'
           . '</div>';
    } else {
        echo '<div style="background:#fcf0ef;border-left:3px solid #d63638;border-radius:0 4px 4px 0;padding:9px 12px;margin:0 0 12px;color:#8a2424;">'
           . '<strong style="font-weight:600;">Live.</strong> Varselet sendes til alle ' . (int) $antall . ' påmeldte deltakere.'
           . '</div>';
    }

    if ($sent_at) {
        $count = (int) get_post_meta($post->ID, '_bv_avlyst_varsel_sent_count', true);
        echo '<p style="margin:0 0 12px;color:#1a7f37;">'
           . '<span class="dashicons dashicons-yes" style="font-size:16px;width:16px;height:16px;vertical-align:text-bottom;"></span> '
           . 'Sendt ' . esc_html(wp_date('j. M Y H:i', strtotime($sent_at)))
           . ' (' . $count . ' e-post' . ($count === 1 ? '' : 'er') . ')</p>';
    }

    $action_url = admin_url('admin-post.php');
    echo '<form method="post" action="' . esc_url($action_url) . '" onsubmit="return confirm(\'Sende avlyst-varsel nå?\');">';
    echo '<input type="hidden" name="action" value="bimverdi_send_avlyst_varsel">';
    echo '<input type="hidden" name="arrangement_id" value="' . esc_attr($post->ID) . '">';
    if ($sent_at) {
        // Allerede sendt → ny innsending er en BEVISST re-send (handleren krever flagget).
        echo '<input type="hidden" name="bv_resend" value="1">';
    }
    wp_nonce_field('bimverdi_send_avlyst_varsel_' . $post->ID);
    $btn_label = $sent_at
        ? 'Send på nytt'
        : ($gated ? 'Send testkopi' : 'Send til ' . (int) $antall . ' deltakere');
    // Rolig sekundærknapp for test/re-send; primær kun ved live førstegangs-utsending.
    $btn_class = (!$gated && !$sent_at) ? 'button button-primary' : 'button';
    echo '<button type="submit" class="' . $btn_class . '">' . esc_html($btn_label) . '</button>';
    echo '</form>';

    echo '</div>';
}

// ════════════════════════════════════════════════════════════════════
// HANDLER — admin_post
// ════════════════════════════════════════════════════════════════════

add_action('admin_post_bimverdi_send_avlyst_varsel', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Ingen tilgang.');
    }

    $arrangement_id = isset($_POST['arrangement_id']) ? (int) $_POST['arrangement_id'] : 0;
    check_admin_referer('bimverdi_send_avlyst_varsel_' . $arrangement_id);

    $tilbake = admin_url('post.php?post=' . $arrangement_id . '&action=edit');

    if (!$arrangement_id || get_post_type($arrangement_id) !== 'arrangement') {
        wp_safe_redirect(add_query_arg('bv_avlyst', 'invalid', $tilbake));
        exit;
    }

    $status = get_field('arrangement_status', $arrangement_id) ?: get_post_meta($arrangement_id, 'arrangement_status', true);
    if ($status !== 'avlyst') {
        wp_safe_redirect(add_query_arg('bv_avlyst', 'not_avlyst', $tilbake));
        exit;
    }

    // Audit: logg hvem som trigget denne sensitive masseutsendingen.
    $bv_user = wp_get_current_user();
    error_log(sprintf('[bimverdi-avlyst] Handler trigget av bruker %d (%s) for arrangement %d',
        get_current_user_id(), $bv_user ? $bv_user->user_login : '?', $arrangement_id));

    // Idempotens: blokker utilsiktet re-send (back-knapp / reload av et gammelt skjema).
    // Bevisst re-send fra metaboksen sender bv_resend=1.
    if (get_post_meta($arrangement_id, '_bv_avlyst_varsel_sent_at', true) && empty($_POST['bv_resend'])) {
        wp_safe_redirect(add_query_arg('bv_avlyst', 'already_sent', $tilbake));
        exit;
    }

    $result = bimverdi_avlyst_send($arrangement_id);

    if (!empty($result['empty_allowlist'])) {
        wp_safe_redirect(add_query_arg('bv_avlyst', 'empty_allowlist', $tilbake));
        exit;
    }

    $notice = $result['ok'] ? 'sent_ok' : 'sent_partial';
    $tilbake = add_query_arg([
        'bv_avlyst'       => $notice,
        'bv_avlyst_n'     => (int) $result['sent'],
        'bv_avlyst_gated' => $result['gated'] ? '1' : '0',
    ], $tilbake);

    wp_safe_redirect($tilbake);
    exit;
});

// ════════════════════════════════════════════════════════════════════
// ADMIN NOTICE — resultat etter redirect
// ════════════════════════════════════════════════════════════════════

add_action('admin_notices', function () {
    if (empty($_GET['bv_avlyst'])) {
        return;
    }
    $code  = sanitize_text_field($_GET['bv_avlyst']);
    $n     = isset($_GET['bv_avlyst_n']) ? (int) $_GET['bv_avlyst_n'] : 0;
    $gated = !empty($_GET['bv_avlyst_gated']);

    $map = [
        'invalid'      => ['error',   'Avlyst-varsel: ugyldig arrangement.'],
        'not_avlyst'   => ['warning', 'Avlyst-varsel: arrangementet er ikke markert som avlyst.'],
        'sent_ok'      => ['success', sprintf('Avlyst-varsel sendt (%d e-post)%s.', $n, $gated ? ' — sikkerhetsgate aktiv, kun til allowlist' : '')],
        'sent_partial' => ['warning', sprintf('Avlyst-varsel sendt med noen feil (%d levert). Se feillogg.', $n)],
        'already_sent' => ['warning', 'Avlyst-varsel er allerede sendt for dette arrangementet. Bruk «Send på nytt» i metaboksen hvis du faktisk vil sende igjen.'],
        'empty_allowlist' => ['error', 'Avlyst-varsel IKKE sendt: sikkerhetsgaten er aktiv, men allowlisten er tom (feilkonfigurert filter). Ingenting ble sendt.'],
    ];
    if (!isset($map[$code])) {
        return;
    }
    [$type, $msg] = $map[$code];
    printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr($type), esc_html($msg));
});
