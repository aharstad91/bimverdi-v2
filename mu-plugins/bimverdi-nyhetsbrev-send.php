<?php
/**
 * BIM Verdi - Nyhetsbrev send-motor (FASE: KUN INTERN TESTING)
 *
 * Sender det fryste øyeblikksbildet (_bv_nyhetsbrev_html) som e-post via
 * wp_mail() → Resend. I DENNE fasen finnes KUN test-utsendelse:
 *
 *   ⛔ MASSEUTSENDELSE TIL MEDLEMMER ER IKKE IMPLEMENTERT.
 *      Det finnes ingen kodevei som sender til mottakerlisten — kun en
 *      telle-funksjon (bimverdi_nyhetsbrev_mottakere) for forhåndsvisning
 *      av antall. Massesend bygges som eget, eksplisitt steg senere.
 *
 *   ✅ Test-send er HARDT begrenset: kun til innlogget admins egen e-post
 *      og adresser i BIMVERDI_NYHETSBREV_TEST_MOTTAKERE (wp-config.php,
 *      kommaseparert streng). Maks 5 mottakere per forsøk. Gjelder også prod.
 *
 * I tillegg: GDPR-avmelding. Øyeblikksbildet lagres med per-mottaker-
 * placeholdere (%%BV_UID%% / %%BV_TOKEN%%) i avmeldings-lenken; denne motoren
 * bytter dem ut per mottaker. Endepunktet /?bv_nb_avmeld=<uid>&bvt=<token>
 * setter user_meta `bv_nyhetsbrev_avmeldt` og viser en bekreftelsesside.
 *
 * Lokalt finnes dessuten _local-email-blocker.php som whitelister kun
 * Andreas' adresser — dobbelt sikkerhetsnett under utvikling.
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* -------------------------------------------------------------------------
 * 1. Avmelding (GDPR): token, URL, endepunkt.
 * ---------------------------------------------------------------------- */

/**
 * Stabil, hemmelig-basert avmeldings-token for en bruker. Lenken i e-posten
 * må virke uten innlogging; tokenet hindrer at andre kan melde av brukeren.
 */
function bimverdi_nyhetsbrev_avmelding_token($user_id) {
    return substr(hash_hmac('sha256', 'bv-nyhetsbrev-avmelding|' . (int) $user_id, wp_salt('auth')), 0, 20);
}

/** Ferdig avmeldings-URL for en bruker. */
function bimverdi_nyhetsbrev_avmelding_url($user_id) {
    return add_query_arg(
        ['bv_nb_avmeld' => (int) $user_id, 'bvt' => bimverdi_nyhetsbrev_avmelding_token($user_id)],
        home_url('/')
    );
}

/** Har brukeren meldt seg av nyhetsbrevet? */
function bimverdi_nyhetsbrev_er_avmeldt($user_id) {
    return (bool) get_user_meta($user_id, 'bv_nyhetsbrev_avmeldt', true);
}

/**
 * Avmeldings-endepunkt. Token-verifisert (hash_equals), idempotent, krever
 * ikke innlogging. Ugyldig lenke gir en nøytral feilside uten å avsløre om
 * bruker-ID-en finnes.
 */
add_action('template_redirect', function () {
    if (!isset($_GET['bv_nb_avmeld'])) {
        return;
    }

    $user_id = (int) $_GET['bv_nb_avmeld'];
    $token   = isset($_GET['bvt']) ? (string) $_GET['bvt'] : '';

    $gyldig = $user_id > 0
        && $token !== ''
        && hash_equals(bimverdi_nyhetsbrev_avmelding_token($user_id), $token)
        && get_userdata($user_id) !== false;

    if ($gyldig && !bimverdi_nyhetsbrev_er_avmeldt($user_id)) {
        update_user_meta($user_id, 'bv_nyhetsbrev_avmeldt', current_time('mysql'));
    }

    nocache_headers();
    status_header($gyldig ? 200 : 400);
    header('Content-Type: text/html; charset=UTF-8');

    $tittel  = $gyldig ? 'Du er meldt av nyhetsbrevet' : 'Ugyldig avmeldingslenke';
    $melding = $gyldig
        ? 'Du vil ikke lenger motta «Nytt &amp; Nyttig fra BIM Verdi». Ombestemmer du deg, ta kontakt på <a href="mailto:post@bimverdi.no" style="color:#1A1A1A;">post@bimverdi.no</a>.'
        : 'Lenken er ugyldig eller utløpt. Ta kontakt på <a href="mailto:post@bimverdi.no" style="color:#1A1A1A;">post@bimverdi.no</a> hvis du ønsker å melde deg av nyhetsbrevet.';
    ?><!DOCTYPE html>
<html lang="nb">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title><?php echo esc_html($tittel); ?> – BIM Verdi</title>
</head>
<body style="margin:0;padding:0;background:#F7F5EF;font-family:Inter,system-ui,-apple-system,sans-serif;">
<div style="max-width:480px;margin:80px auto;padding:0 24px;">
    <p style="color:#FF8B5E;font-size:12px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;margin:0 0 12px;">BIM Verdi</p>
    <h1 style="color:#1A1A1A;font-size:24px;font-weight:500;margin:0 0 16px;"><?php echo esc_html($tittel); ?></h1>
    <p style="color:#5A5A5A;font-size:15px;line-height:1.6;margin:0 0 32px;"><?php echo wp_kses($melding, ['a' => ['href' => true, 'style' => true]]); ?></p>
    <p style="border-top:1px solid #D6D1C6;padding-top:24px;margin:0;">
        <a href="<?php echo esc_url(home_url('/')); ?>" style="color:#1A1A1A;font-size:14px;">Til forsiden →</a>
    </p>
</div>
</body>
</html><?php
    exit;
});

/* -------------------------------------------------------------------------
 * 2. Mottaker-resolusjon — KUN telling i denne fasen.
 * ---------------------------------------------------------------------- */

/**
 * Mottakerlisten for masseutsendelse (senere): WP-registrerte medlemmer med
 * gyldig e-post som ikke har meldt seg av.
 *
 * ⚠️ Brukes i denne fasen KUN til å vise antall i admin. Ingen kode sender
 * til denne listen ennå.
 *
 * @return array[] Liste av ['id' => int, 'email' => string, 'navn' => string].
 */
function bimverdi_nyhetsbrev_mottakere() {
    $brukere = get_users([
        'role__in' => ['medlem', 'tilleggskontakt', 'deltaker', 'prosjektdeltaker', 'partner'],
        'fields'   => 'all',
    ]);

    $mottakere = [];
    foreach ($brukere as $bruker) {
        if (!is_email($bruker->user_email)) {
            continue;
        }
        if (bimverdi_nyhetsbrev_er_avmeldt($bruker->ID)) {
            continue;
        }
        $mottakere[] = [
            'id'    => (int) $bruker->ID,
            'email' => $bruker->user_email,
            'navn'  => $bruker->display_name,
        ];
    }

    return $mottakere;
}

/* -------------------------------------------------------------------------
 * 3. Send ÉN e-post fra øyeblikksbildet (per-mottaker-substitusjon).
 * ---------------------------------------------------------------------- */

/**
 * Send øyeblikksbildet til én adresse. Bytter ut avmeldings-placeholderne
 * per mottaker: er adressen en WP-bruker får hen sin ekte token-lenke,
 * ellers pekes lenken til forsiden (gjelder bare interne test-adresser).
 *
 * @param int    $post_id        Nyhetsbrev-post med lagret øyeblikksbilde.
 * @param string $email          Mottakeradresse (validert av kalleren).
 * @param string $subject_prefix F.eks. '[TEST] '.
 * @return true|WP_Error
 */
function bimverdi_nyhetsbrev_send_en($post_id, $email, $subject_prefix = '') {
    $html = get_post_meta($post_id, '_bv_nyhetsbrev_html', true);
    if ($html === '') {
        return new WP_Error('mangler_snapshot', 'Nyhetsbrevet har ikke noe lagret øyeblikksbilde.');
    }

    $bruker = get_user_by('email', $email);
    if ($bruker) {
        $html = str_replace(
            ['%%BV_UID%%', '%%BV_TOKEN%%'],
            [(string) $bruker->ID, bimverdi_nyhetsbrev_avmelding_token($bruker->ID)],
            $html
        );
    } else {
        // Ikke WP-bruker (intern testadresse): pek avmeldings-lenken til forsiden.
        // Regex tåler esc_url-ampersand (&#038;) i den lagrede HTML-en.
        $html = preg_replace(
            '/href="[^"]*bv_nb_avmeld=%%BV_UID%%[^"]*"/',
            'href="' . esc_url(home_url('/')) . '"',
            $html
        );
    }

    // E-postemner er ren tekst — dekod HTML-entiteter fra get_the_title (&#038; → &).
    $subject = $subject_prefix . wp_specialchars_decode(get_the_title($post_id), ENT_QUOTES);
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    $ok = wp_mail($email, $subject, $html, $headers);

    return $ok ? true : new WP_Error('send_feilet', 'wp_mail() returnerte false for ' . $email . ' — sjekk error_log.');
}

/* -------------------------------------------------------------------------
 * 4. Test-send: admin_post-handler med hard allowlist.
 * ---------------------------------------------------------------------- */

/**
 * Adresser som test-send kan gå til: innlogget brukers egen e-post + adresser
 * i BIMVERDI_NYHETSBREV_TEST_MOTTAKERE (wp-config, kommaseparert). Små bokstaver.
 */
function bimverdi_nyhetsbrev_test_allowlist() {
    $tillatt = [];

    $meg = wp_get_current_user();
    if ($meg && is_email($meg->user_email)) {
        $tillatt[] = strtolower($meg->user_email);
    }

    if (defined('BIMVERDI_NYHETSBREV_TEST_MOTTAKERE') && BIMVERDI_NYHETSBREV_TEST_MOTTAKERE) {
        foreach (explode(',', BIMVERDI_NYHETSBREV_TEST_MOTTAKERE) as $adresse) {
            $adresse = strtolower(trim($adresse));
            if (is_email($adresse)) {
                $tillatt[] = $adresse;
            }
        }
    }

    return array_values(array_unique($tillatt));
}

add_action('admin_post_bimverdi_nyhetsbrev_send_test', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Ingen tilgang.');
    }
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    check_admin_referer('bimverdi_nyhetsbrev_send_test_' . $post_id);

    $tilbake = admin_url('post.php?post=' . $post_id . '&action=edit');

    if (!$post_id || get_post_type($post_id) !== BV_NYHETSBREV_CPT) {
        wp_die('Ugyldig nyhetsbrev.');
    }
    if (!get_post_meta($post_id, '_bv_nyhetsbrev_html', true)) {
        wp_safe_redirect(add_query_arg('bv_nb_notice', 'test_mangler_snapshot', $tilbake));
        exit;
    }

    // Parse og valider oppgitte adresser.
    $raa      = isset($_POST['test_epost']) ? sanitize_text_field(wp_unslash($_POST['test_epost'])) : '';
    $adresser = array_values(array_filter(array_map('trim', preg_split('/[,\s]+/', $raa))));

    if (!$adresser) {
        wp_safe_redirect(add_query_arg('bv_nb_notice', 'test_tom', $tilbake));
        exit;
    }
    if (count($adresser) > 5) {
        wp_safe_redirect(add_query_arg('bv_nb_notice', 'test_for_mange', $tilbake));
        exit;
    }

    // ⛔ HARD GATE: alle adresser må stå på test-allowlisten. Én ugyldig →
    // ingenting sendes. Dette er vernet mot å nå ekte medlemmer, også på prod.
    $allowlist = bimverdi_nyhetsbrev_test_allowlist();
    foreach ($adresser as $adresse) {
        if (!is_email($adresse) || !in_array(strtolower($adresse), $allowlist, true)) {
            error_log(sprintf(
                '[bimverdi-nyhetsbrev-send] AVVIST test-send til "%s" (ikke på allowlist) av %s',
                $adresse,
                wp_get_current_user()->user_login
            ));
            wp_safe_redirect(add_query_arg('bv_nb_notice', 'test_ikke_tillatt', $tilbake));
            exit;
        }
    }

    // Send — én e-post per adresse (egen avmeldings-substitusjon per mottaker).
    $feil = [];
    foreach ($adresser as $adresse) {
        $res = bimverdi_nyhetsbrev_send_en($post_id, $adresse, '[TEST] ');
        if (is_wp_error($res)) {
            $feil[] = $adresse;
        }
    }

    // Logg testforsøket på posten (siste 10).
    $logg   = get_post_meta($post_id, '_bv_nyhetsbrev_test_log', true);
    $logg   = is_array($logg) ? $logg : [];
    $logg[] = [
        'tid' => current_time('mysql'),
        'til' => $adresser,
        'av'  => wp_get_current_user()->user_login,
        'ok'  => !$feil,
    ];
    update_post_meta($post_id, '_bv_nyhetsbrev_test_log', array_slice($logg, -10));

    $notice = $feil ? 'test_feil' : 'test_sendt';
    wp_safe_redirect(add_query_arg('bv_nb_notice', $notice, $tilbake));
    exit;
});
