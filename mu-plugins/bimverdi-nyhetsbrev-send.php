<?php
/**
 * BIM Verdi - Nyhetsbrev send-motor (FASE: KUN INTERN TESTING)
 *
 * Sender det fryste øyeblikksbildet (_bv_nyhetsbrev_html) som e-post via
 * wp_mail() → Resend. I DENNE fasen finnes KUN test-utsendelse:
 *
 *   ⛔ MASSEUTSENDELSE TIL MEDLEMMER ER IKKE IMPLEMENTERT.
 *      Det finnes ingen kodevei som sender til mottakerlisten. Seksjon 5
 *      definerer kun TILSTANDSMODELLEN (fryst manifest + batch-rader) —
 *      den gjør null API-kall. Selve senderen bygges som eget steg bak
 *      fail-closed miljøgate (se docs/plans/2026-06-10-001-*).
 *
 *   ✅ Test-send er HARDT begrenset: kun til innlogget admins egen e-post
 *      og adresser i BIMVERDI_NYHETSBREV_TEST_MOTTAKERE (wp-config.php,
 *      kommaseparert streng). Maks 5 mottakere per forsøk. Gjelder også prod.
 *
 *   🔒 Sperren håndheves INNERST i bimverdi_nyhetsbrev_send_en() — ikke bare
 *      i UI-handleren. Ingen kodevei kan levere utenfor allowlisten før
 *      BIMVERDI_NYHETSBREV_MASSESEND_AKTIV settes til true i wp-config
 *      (bevisst opt-in når massesend-steget bygges).
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
 *
 * Støtter to inngangsveier mot SAMME URL (?bv_nb_avmeld=<uid>&bvt=<token>):
 *   - GET: bruker klikker lenken i brevet → HTML-bekreftelsesside.
 *   - POST: e-postklientens One-Click-knapp (RFC 8058, List-Unsubscribe-Post
 *     = One-Click). Query-parameterne ligger i URL-en (så $_GET er fylt selv
 *     ved POST); vi svarer da 200 uten HTML-krav. Ingen nonce/CSRF her — det
 *     er bevisst: HMAC-tokenet ER autentiseringen, og RFC 8058-POST-en kommer
 *     fra mottakerens mailserver, ikke en innlogget økt.
 */
add_action('template_redirect', function () {
    if (!isset($_GET['bv_nb_avmeld'])) {
        return;
    }

    $er_post = (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST');

    $user_id = (int) $_GET['bv_nb_avmeld'];
    $token   = isset($_GET['bvt']) ? (string) $_GET['bvt'] : '';

    $gyldig = $user_id > 0
        && $token !== ''
        && hash_equals(bimverdi_nyhetsbrev_avmelding_token($user_id), $token)
        && get_userdata($user_id) !== false;

    if ($gyldig && !bimverdi_nyhetsbrev_er_avmeldt($user_id)) {
        update_user_meta($user_id, 'bv_nyhetsbrev_avmeldt', current_time('mysql'));
    }

    // One-Click-POST: mailserveren forventer en rask 2xx, ikke en nettside.
    // Svar minimalt og avslutt (idempotent — gjentatt POST gir også 200).
    if ($er_post) {
        nocache_headers();
        status_header($gyldig ? 200 : 400);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $gyldig ? 'OK' : 'Invalid';
        exit;
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
 * Personaliser øyeblikksbilde-HTML for én mottaker. Delt mellom test-send
 * (send_en) og massesend-elementbyggeren — samme substitusjon begge steder.
 *
 * MERK: Deterministisk for samme (html, user_id) på samme miljø — det er en
 * forutsetning for idempotency-vernet (re-send av usikker batch må gi
 * byte-identisk payload).
 *
 * @param string $html    Øyeblikksbildet med %%BV_UID%%/%%BV_TOKEN%%-placeholdere.
 * @param int    $user_id WP-bruker-ID, eller 0 for ikke-bruker (testadresse).
 */
function bimverdi_nyhetsbrev_personaliser_html($html, $user_id) {
    $user_id = (int) $user_id;

    if ($user_id > 0 && get_userdata($user_id)) {
        $html = str_replace(
            ['%%BV_UID%%', '%%BV_TOKEN%%'],
            [(string) $user_id, bimverdi_nyhetsbrev_avmelding_token($user_id)],
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

    // Localhost-URL-er virker kun på utviklerens egen maskin — hos andre
    // mottakere blir bilder knuste og lenker døde (verifisert hos Bård,
    // Outlook 10.06). Ved utsendelse fra lokalt miljø skrives alle URL-er om
    // til produksjonsdomenet, der innholdet faktisk ligger. (Gjelder også
    // avmeldings-lenken — den virker først når koden er deployet til prod.)
    $hjemme = home_url();
    if (stripos($hjemme, 'localhost') !== false || stripos($hjemme, '127.0.0.1') !== false) {
        $prod = defined('BIMVERDI_NYHETSBREV_PROD_URL') ? BIMVERDI_NYHETSBREV_PROD_URL : 'https://bimverdi.no';
        $html = str_replace($hjemme, untrailingslashit($prod), $html);
    }

    return $html;
}

/**
 * Ferdig avmeldings-URL for en mottaker, alltid mot PROD-domenet (lenken i
 * List-Unsubscribe-headeren må virke hos mottakeren, ikke på localhost).
 */
function bimverdi_nyhetsbrev_avmelding_url_prod($user_id) {
    $url    = bimverdi_nyhetsbrev_avmelding_url($user_id);
    $hjemme = home_url();
    if (stripos($hjemme, 'localhost') !== false || stripos($hjemme, '127.0.0.1') !== false) {
        $prod = defined('BIMVERDI_NYHETSBREV_PROD_URL') ? BIMVERDI_NYHETSBREV_PROD_URL : 'https://bimverdi.no';
        $url  = str_replace($hjemme, untrailingslashit($prod), $url);
    }
    return $url;
}

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
    // ⛔ INNERSTE SPERRE (gjelder også prod, også direkte kall utenom UI-et):
    // test-send leverer KUN til adresser på test-allowlisten — ALLTID, også
    // etter at massesend-konstanten er satt på prod. (Review-funn 10.06: et
    // tidligere $massesend_aktiv-unntak her ville permanent kortsluttet
    // vernet fra go-live. Massesend-stien har sin EGEN gate og rører aldri
    // denne funksjonen.)
    if (!in_array(strtolower(trim($email)), bimverdi_nyhetsbrev_test_allowlist(), true)) {
        error_log(sprintf(
            '[bimverdi-nyhetsbrev-send] NEKTET send_en til "%s" — ikke på test-allowlist.',
            $email
        ));
        return new WP_Error('ikke_tillatt', 'Adressen er ikke på test-allowlisten. Test-send går aldri utenfor allowlisten.');
    }

    $html = get_post_meta($post_id, '_bv_nyhetsbrev_html', true);
    if ($html === '') {
        return new WP_Error('mangler_snapshot', 'Nyhetsbrevet har ikke noe lagret øyeblikksbilde.');
    }

    $bruker = get_user_by('email', $email);
    $html   = bimverdi_nyhetsbrev_personaliser_html($html, $bruker ? $bruker->ID : 0);

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

/* -------------------------------------------------------------------------
 * 5. Masseutsendelse: fryst manifest + per-batch-tilstand (Unit 1).
 *
 * ⛔ DENNE SEKSJONEN SENDER INGENTING. Den definerer kun tilstandsmodellen
 *    for masseutsendelsen: et IMMUTABELT manifest (fryst mottakerliste,
 *    emne, HTML-hash) og MUTERBARE per-batch-rader for checkpointing.
 *    Selve API-kallene bygges i eget steg bak fail-closed miljøgate
 *    (BIMVERDI_NYHETSBREV_MASSESEND_AKTIV + home_url === prod).
 *
 * Tilstandsmodell (post meta på nyhetsbrev-posten):
 *
 *   _bv_nyhetsbrev_utsendelse   IMMUTABELT manifest, skrevet atomisk ved
 *                               start (add_post_meta unique). Fryser ALT:
 *                               mottakerliste, chunking, emne, from/reply_to,
 *                               SHA-256 av øyeblikksbilde-HTML-en. Endres
 *                               ALDRI etter start.
 *
 *   _bv_nb_batch_{i}            Én meta-rad PER batch (aldri én stor array —
 *                               checkpoints skal ikke kunne read-modify-
 *                               write-clobbre hverandre). Status:
 *                               pending → sendt | usikker | delvis_usikker
 *                               | superseded. Rebuilds (avmeldte/karantene)
 *                               legger NYE rader og markerer gamle
 *                               superseded — sletter aldri (audit-trail).
 *
 * Krasj-robusthet: dør prosessen mellom manifest-skriv og batch-rad-skriv,
 * rekonstruerer bimverdi_nyhetsbrev_batch_rad() raden fra manifestets fryste
 * chunk (pending-default) — manifestet er alltid nok til å gjenoppta.
 * ---------------------------------------------------------------------- */

/** Meta-nøkkel for det immutable utsendelses-manifestet. */
const BV_NB_META_MANIFEST = '_bv_nyhetsbrev_utsendelse';

/** Prefiks for per-batch meta-rader (_bv_nb_batch_0, _bv_nb_batch_1, …). */
const BV_NB_META_BATCH_PREFIX = '_bv_nb_batch_';

/** Batch-størrelse: ~26 KB HTML × 50 ≈ 1,3 MB payload — trygt under grensene. */
function bimverdi_nyhetsbrev_batch_storrelse() {
    $std = defined('BIMVERDI_NYHETSBREV_BATCH_STORRELSE') ? (int) BIMVERDI_NYHETSBREV_BATCH_STORRELSE : 50;
    return max(1, min(100, $std)); // Resend batch-API: maks 100 per kall.
}

/**
 * Start en utsendelse: frys ALT i ett immutabelt manifest og opprett
 * pending-rader per batch. Sender INGENTING — gjør kun brevet klart for
 * kjøringsmotoren.
 *
 * Atomisk start-guard: add_post_meta(..., true) (unique) gjør at nøyaktig
 * ett av to samtidige start-kall vinner; taperen får WP_Error uten å ha
 * skrevet noe.
 *
 * @return array|WP_Error Manifestet ved suksess.
 */
function bimverdi_nyhetsbrev_utsendelse_start($post_id) {
    $post_id = (int) $post_id;

    if (!$post_id || get_post_type($post_id) !== BV_NYHETSBREV_CPT) {
        return new WP_Error('ugyldig_post', 'Ikke et nyhetsbrev.');
    }
    if (bimverdi_nyhetsbrev_er_sendt($post_id)) {
        return new WP_Error('allerede_sendt', 'Nyhetsbrevet er allerede sendt.');
    }
    if (get_post_meta($post_id, BV_NB_META_MANIFEST, true)) {
        return new WP_Error('utsendelse_finnes', 'En utsendelse er allerede startet for dette brevet.');
    }

    $html = get_post_meta($post_id, '_bv_nyhetsbrev_html', true);
    if ($html === '') {
        return new WP_Error('mangler_snapshot', 'Nyhetsbrevet har ikke noe lagret øyeblikksbilde.');
    }

    // Frys mottakerlisten NÅ — kun id+email (navn trengs ikke i send-stien).
    $mottakere = [];
    foreach (bimverdi_nyhetsbrev_mottakere() as $m) {
        $mottakere[] = ['id' => $m['id'], 'email' => $m['email']];
    }
    if (!$mottakere) {
        return new WP_Error('ingen_mottakere', 'Mottakerlisten er tom — ingenting å sende.');
    }

    $bruker = function_exists('wp_get_current_user') ? wp_get_current_user() : null;

    $manifest = [
        'versjon'         => 1,
        'startet'         => current_time('mysql'),
        'startet_av'      => ($bruker && $bruker->exists()) ? $bruker->user_login : 'system/cli',
        // Emne fryses HER (post-tittelen er redigerbar frem til sent_at) —
        // send-stien leser ALDRI get_the_title(). Entitets-dekodet for e-post.
        'subject'         => wp_specialchars_decode(get_the_title($post_id), ENT_QUOTES),
        'from'            => sprintf(
            '%s <%s>',
            defined('BIMVERDI_RESEND_FROM_NAME') ? BIMVERDI_RESEND_FROM_NAME : 'BIM Verdi',
            defined('BIMVERDI_RESEND_FROM_EMAIL') ? BIMVERDI_RESEND_FROM_EMAIL : 'noreply@bimverdi.no'
        ),
        'reply_to'        => defined('BIMVERDI_RESEND_REPLY_TO') ? BIMVERDI_RESEND_REPLY_TO : 'post@bimverdi.no',
        // Vern mot ENHVER regenererings-kodevei: kjøringsmotoren nekter å
        // sende hvis den lagrede HTML-en ikke lenger matcher denne hashen.
        'html_hash'       => hash('sha256', $html),
        'antall'          => count($mottakere),
        'batch_storrelse' => bimverdi_nyhetsbrev_batch_storrelse(),
        // Fryst chunking: batch-indeks → liste av {id, email}. Immutabel
        // audit-kopi; operative medlemslister bor i batch-radene.
        'chunks'          => array_chunk($mottakere, bimverdi_nyhetsbrev_batch_storrelse()),
    ];

    // Atomisk: unique-flagget gir set-if-not-exists. To samtidige start-kall
    // → nøyaktig én true; den andre har ikke skrevet noe som helst.
    if (!add_post_meta($post_id, BV_NB_META_MANIFEST, $manifest, true)) {
        return new WP_Error('utsendelse_finnes', 'En annen prosess startet utsendelsen samtidig.');
    }

    // Pending-rader per batch. Dør vi her, rekonstruerer batch_rad() dem
    // fra manifestet — manifestet alene er nok til å gjenoppta.
    foreach (array_keys($manifest['chunks']) as $i) {
        add_post_meta($post_id, BV_NB_META_BATCH_PREFIX . $i, bimverdi_nyhetsbrev_batch_rad_standard($post_id, $manifest, $i), true);
    }

    error_log(sprintf(
        '[bimverdi-nyhetsbrev-send] Utsendelse fryst for post %d: %d mottakere i %d batcher (av %s). Ingenting sendt ennå.',
        $post_id,
        $manifest['antall'],
        count($manifest['chunks']),
        $manifest['startet_av']
    ));

    return $manifest;
}

/** Hent det immutable manifestet, eller null hvis utsendelse ikke er startet. */
function bimverdi_nyhetsbrev_utsendelse_manifest($post_id) {
    $manifest = get_post_meta((int) $post_id, BV_NB_META_MANIFEST, true);
    return is_array($manifest) ? $manifest : null;
}

/** Er en utsendelse startet (manifest finnes)? Styrer redigeringssperren. */
function bimverdi_nyhetsbrev_utsendelse_startet($post_id) {
    return bimverdi_nyhetsbrev_utsendelse_manifest($post_id) !== null;
}

/** Standard pending-rad for batch $i, avledet av manifestets fryste chunk. */
function bimverdi_nyhetsbrev_batch_rad_standard($post_id, array $manifest, $i) {
    return [
        'indeks'        => (int) $i,
        'status'        => 'pending',
        'mottakere'     => $manifest['chunks'][$i] ?? [],
        // Idempotency-nøkkel (Resend husker den i 24 t): stabil per batch.
        // Rebuild-rader får egne nøkler med /retry-{n}-suffiks.
        'nokkel'        => sprintf('nyhetsbrev-%d/batch-%d', (int) $post_id, (int) $i),
        'forsok_tid'    => null, // current_time('mysql') ved siste forsøk
        'forsok_ts'     => 0,    // unix-tid for 24-timers-vaktens regnestykke
        'resend_ids'    => [],
        'superseded_by' => null, // indeks på erstatnings-raden ved rebuild
        'notat'         => '',
    ];
}

/**
 * Hent én batch-rad. Mangler meta-raden (krasj mellom manifest- og
 * rad-skriv), rekonstrueres pending-standarden fra manifestet — uten å
 * skrive; første marker_batch() persisterer den.
 */
function bimverdi_nyhetsbrev_batch_rad($post_id, $i) {
    $rad = get_post_meta((int) $post_id, BV_NB_META_BATCH_PREFIX . (int) $i, true);
    if (is_array($rad)) {
        return $rad;
    }
    $manifest = bimverdi_nyhetsbrev_utsendelse_manifest($post_id);
    if ($manifest && isset($manifest['chunks'][(int) $i])) {
        return bimverdi_nyhetsbrev_batch_rad_standard($post_id, $manifest, (int) $i);
    }
    return null;
}

/**
 * Alle batch-rader for utsendelsen, nøklet på indeks — manifestets
 * originale chunks PLUSS eventuelle rebuild-rader lagt til senere.
 */
function bimverdi_nyhetsbrev_batch_rader($post_id) {
    $post_id  = (int) $post_id;
    $manifest = bimverdi_nyhetsbrev_utsendelse_manifest($post_id);
    if (!$manifest) {
        return [];
    }

    $rader = [];

    // Originale batcher (rekonstruert fra manifest hvis meta-raden mangler).
    foreach (array_keys($manifest['chunks']) as $i) {
        $rader[(int) $i] = bimverdi_nyhetsbrev_batch_rad($post_id, $i);
    }

    // Rebuild-rader med indeks utenfor originalsettet (lagt til ved
    // avmeldings-filtrering/karantene i kjøringsmotoren).
    foreach (get_post_meta($post_id) as $nokkel => $verdier) {
        if (strpos($nokkel, BV_NB_META_BATCH_PREFIX) !== 0) {
            continue;
        }
        $i = (int) substr($nokkel, strlen(BV_NB_META_BATCH_PREFIX));
        if (!isset($rader[$i])) {
            $rad = maybe_unserialize($verdier[0]);
            if (is_array($rad)) {
                $rader[$i] = $rad;
            }
        }
    }

    // Selvreparasjon (persist-før-send-invarianten): ombygging skriver først
    // gammel rad (superseded + innebygd kopi av erstatningen), så ny rad.
    // Dør prosessen mellom de to skrivene, gjenskapes den manglende nye
    // raden her fra kopien — pending, ingenting var sendt.
    foreach ($rader as $rad) {
        if (($rad['status'] ?? '') !== 'superseded' || empty($rad['erstatning']) || !is_array($rad['erstatning'])) {
            continue;
        }
        $ny = $rad['erstatning'];
        $ny_indeks = (int) ($ny['indeks'] ?? -1);
        if ($ny_indeks >= 0 && !isset($rader[$ny_indeks])) {
            add_post_meta($post_id, BV_NB_META_BATCH_PREFIX . $ny_indeks, $ny, true);
            $rader[$ny_indeks] = $ny;
            error_log(sprintf(
                '[bimverdi-nyhetsbrev-send] Reparerte manglende ombyggings-rad %d for post %d (krasj mellom skriv).',
                $ny_indeks, $post_id
            ));
        }
    }

    ksort($rader);
    return $rader;
}

/**
 * Oppdater én batch-rad (merge av endringer). Skriver KUN denne radens
 * meta-nøkkel — andre batchers checkpoints kan aldri overskrives herfra.
 */
function bimverdi_nyhetsbrev_marker_batch($post_id, $i, array $endringer) {
    $rad = bimverdi_nyhetsbrev_batch_rad($post_id, $i);
    if ($rad === null) {
        return new WP_Error('ukjent_batch', sprintf('Batch %d finnes ikke for post %d.', $i, $post_id));
    }
    $rad = array_merge($rad, $endringer);
    update_post_meta((int) $post_id, BV_NB_META_BATCH_PREFIX . (int) $i, $rad);
    return $rad;
}

/**
 * Avledet totalstatus — telles ALLTID opp fra batch-radene, lagres aldri
 * separat (kan dermed ikke drifte fra virkeligheten).
 */
function bimverdi_nyhetsbrev_utsendelse_status($post_id) {
    $manifest = bimverdi_nyhetsbrev_utsendelse_manifest($post_id);
    if (!$manifest) {
        return null;
    }

    $teller = ['pending' => 0, 'sendt' => 0, 'usikker' => 0, 'delvis_usikker' => 0, 'superseded' => 0];
    $mottakere_sendt = 0;

    foreach (bimverdi_nyhetsbrev_batch_rader($post_id) as $rad) {
        $status = $rad['status'] ?? 'pending';
        if (!isset($teller[$status])) {
            $teller[$status] = 0;
        }
        $teller[$status]++;
        if ($status === 'sendt') {
            $mottakere_sendt += count($rad['mottakere'] ?? []);
        }
    }

    $aktive   = array_sum($teller) - $teller['superseded'];
    $fullfort = $aktive > 0 && $teller['sendt'] === $aktive;

    return [
        'batcher'         => $teller,
        'mottakere_total' => (int) $manifest['antall'],
        'mottakere_sendt' => $mottakere_sendt,
        'fullfort'        => $fullfort,
    ];
}

/**
 * Sett _bv_nyhetsbrev_sent_at hvis (og bare hvis) alle batcher er
 * sendt/superseded. Idempotent — overskriver aldri et eksisterende stempel.
 *
 * @return bool True hvis utsendelsen er (eller nettopp ble) fullført.
 */
function bimverdi_nyhetsbrev_utsendelse_fullfor_hvis_ferdig($post_id) {
    $post_id = (int) $post_id;

    if (bimverdi_nyhetsbrev_er_sendt($post_id)) {
        return true;
    }

    $status = bimverdi_nyhetsbrev_utsendelse_status($post_id);
    if (!$status || !$status['fullfort']) {
        return false;
    }

    // add_post_meta unique: to samtidige fullføringer gir nøyaktig ett stempel.
    add_post_meta($post_id, '_bv_nyhetsbrev_sent_at', current_time('mysql'), true);

    error_log(sprintf(
        '[bimverdi-nyhetsbrev-send] Utsendelse FULLFØRT for post %d: %d mottakere markert sendt.',
        $post_id,
        $status['mottakere_sendt']
    ));

    return true;
}

/* -------------------------------------------------------------------------
 * 6. Masseutsendelse: fail-closed miljøgate + batch-sender (Unit 2).
 *
 * ⛔ SIKKERHETSMODELL: Massesend går UTENOM wp_mail() — verken localhost-
 *    blockeren eller wp_mail-filtre ser disse e-postene. Det ENESTE vernet
 *    er gaten under, og den er fail-closed med to uavhengige låser:
 *
 *      1. BIMVERDI_NYHETSBREV_MASSESEND_AKTIV === true  (settes ALDRI lokalt)
 *      2. home_url() === https://bimverdi.no            (DB-basert — dekker
 *         WP-CLI uten SERVER_NAME, LAN-IP, og staging med kopiert config)
 *
 *    Mangler API-nøkkelen nektes det også — det finnes INGEN fallback til
 *    PHP mail() i denne stien (bevisst brudd med bimverdi-resend-mail.php).
 *
 *    Kall ALDRI bv_is_localhost_environment() herfra — den bor i den
 *    gitignorede _local-email-blocker.php og finnes ikke på prod.
 * ---------------------------------------------------------------------- */

/**
 * Fail-closed miljøgate for masseutsendelse. Returnerer true KUN når alle
 * tre låsene er åpne; alt annet gir WP_Error og logges.
 */
function bimverdi_nyhetsbrev_massesend_gate() {
    if (!defined('BIMVERDI_NYHETSBREV_MASSESEND_AKTIV') || BIMVERDI_NYHETSBREV_MASSESEND_AKTIV !== true) {
        error_log('[bimverdi-nyhetsbrev-send] Massesend NEKTET: BIMVERDI_NYHETSBREV_MASSESEND_AKTIV er ikke true.');
        return new WP_Error('massesend_deaktivert', 'Masseutsendelse er ikke aktivert (wp-config-konstanten mangler).');
    }

    if (untrailingslashit(home_url()) !== 'https://bimverdi.no') {
        error_log(sprintf(
            '[bimverdi-nyhetsbrev-send] Massesend NEKTET: feil miljø (home_url = %s). Konstanten var satt — undersøk hvorfor!',
            home_url()
        ));
        return new WP_Error('feil_miljo', 'Masseutsendelse kjører kun på https://bimverdi.no.');
    }

    if (!defined('BIMVERDI_RESEND_API_KEY') || empty(BIMVERDI_RESEND_API_KEY)) {
        // ALDRI fallback til PHP mail() her.
        error_log('[bimverdi-nyhetsbrev-send] Massesend NEKTET: BIMVERDI_RESEND_API_KEY mangler.');
        return new WP_Error('mangler_api_nokkel', 'Resend API-nøkkel mangler — massesend har ingen fallback.');
    }

    return true;
}

/**
 * Bygg Resend batch-elementene for ÉN batch-rad. Ren funksjon uten HTTP —
 * testbar lokalt uten å passere gaten. Verifiserer HTML-hashen mot
 * manifestet FØR bygging (vern mot enhver regenererings-kodevei).
 *
 * @return array|WP_Error Liste av Resend-elementer, eller payload_drift-feil.
 */
function bimverdi_nyhetsbrev_bygg_batch_elementer($post_id, array $manifest, array $rad) {
    $html = get_post_meta((int) $post_id, '_bv_nyhetsbrev_html', true);

    if (!is_string($html) || $html === '' || hash('sha256', $html) !== $manifest['html_hash']) {
        error_log(sprintf(
            '[bimverdi-nyhetsbrev-send] PAYLOAD-DRIFT for post %d: lagret HTML matcher ikke manifest-hashen. Alt stoppes.',
            $post_id
        ));
        return new WP_Error('payload_drift', 'Øyeblikksbildet er endret etter at utsendelsen startet. Utsendelsen er stoppet — dette skal ikke kunne skje.');
    }

    $elementer = [];
    foreach ($rad['mottakere'] as $mottaker) {
        $uid = (int) ($mottaker['id'] ?? 0);

        $elementer[] = [
            'from'     => $manifest['from'],
            'to'       => [$mottaker['email']],
            'subject'  => $manifest['subject'],
            'html'     => bimverdi_nyhetsbrev_personaliser_html($html, $uid),
            'reply_to' => $manifest['reply_to'],
            // One-Click-avmelding (RFC 8058): samme lenke som i brev-bunnen.
            'headers'  => [
                'List-Unsubscribe'      => '<' . bimverdi_nyhetsbrev_avmelding_url_prod($uid) . '>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        ];
    }

    return $elementer;
}

/**
 * Send ÉN batch via Resend /emails/batch. Forutsetter at kalleren (kjørings-
 * motoren, Unit 3) eier kjøre-låsen og har valgt hvilken batch som skal gå.
 *
 * Tilstandsdisiplin (krasj-sikkerhet):
 *   - FØR HTTP-kallet persisteres status 'usikker' + forsøks-tidsstempel.
 *     Dør prosessen midt i kallet, behandles batchen ved gjenopptak som
 *     usikker (re-send m/ samme Idempotency-Key < 20 t) — ALDRI som pending
 *     (som ville gitt blind dobbel-sending med ny payload-bygging).
 *   - Etter svar oppgraderes raden: sendt / delvis_usikker / pending (4xx,
 *     beviselig avvist) — eller forblir usikker (5xx/timeout/nettverk).
 *
 * @return array|WP_Error Oppdatert batch-rad ved suksess; WP_Error med kode
 *                        usikker | avvist_4xx | rate_limit | payload_drift |
 *                        massesend_deaktivert | feil_miljo | ... ellers.
 */
function bimverdi_nyhetsbrev_send_batch($post_id, $batch_indeks) {
    // ⛔ GATE FØRST — før noe annet, hver eneste gang.
    $gate = bimverdi_nyhetsbrev_massesend_gate();
    if (is_wp_error($gate)) {
        return $gate;
    }

    $post_id  = (int) $post_id;
    $manifest = bimverdi_nyhetsbrev_utsendelse_manifest($post_id);
    if (!$manifest) {
        return new WP_Error('mangler_manifest', 'Ingen utsendelse er startet for dette brevet.');
    }

    $rad = bimverdi_nyhetsbrev_batch_rad($post_id, $batch_indeks);
    if ($rad === null) {
        return new WP_Error('ukjent_batch', sprintf('Batch %d finnes ikke.', $batch_indeks));
    }
    if (in_array($rad['status'], ['sendt', 'superseded'], true)) {
        return new WP_Error('batch_ferdig', sprintf('Batch %d er allerede %s.', $batch_indeks, $rad['status']));
    }

    $elementer = bimverdi_nyhetsbrev_bygg_batch_elementer($post_id, $manifest, $rad);
    if (is_wp_error($elementer)) {
        return $elementer;
    }
    if (!$elementer) {
        // Tom batch (alle filtrert bort) — marker superseded uten API-kall.
        return bimverdi_nyhetsbrev_marker_batch($post_id, $batch_indeks, [
            'status' => 'superseded',
            'notat'  => 'Tom etter filtrering — ingenting å sende.',
        ]);
    }

    // Persist-FØR-send: fra nå av er batchen 'usikker' til Resend sier noe annet.
    bimverdi_nyhetsbrev_marker_batch($post_id, $batch_indeks, [
        'status'     => 'usikker',
        'forsok_tid' => current_time('mysql'),
        'forsok_ts'  => time(),
        'notat'      => 'Forsøk startet.',
    ]);

    $respons = wp_remote_post('https://api.resend.com/emails/batch', [
        'headers' => [
            'Authorization'   => 'Bearer ' . BIMVERDI_RESEND_API_KEY,
            'Content-Type'    => 'application/json',
            'Idempotency-Key' => $rad['nokkel'],
        ],
        'body'    => wp_json_encode($elementer),
        'timeout' => 15, // binder worst case mot FPM/proxy-timeout
    ]);

    // Nettverksfeil/timeout: kan ha nådd Resend — forblir usikker.
    if (is_wp_error($respons)) {
        bimverdi_nyhetsbrev_marker_batch($post_id, $batch_indeks, [
            'notat' => 'Nettverksfeil/timeout: ' . $respons->get_error_message(),
        ]);
        return new WP_Error('usikker', sprintf('Batch %d: nettverksfeil — status usikker.', $batch_indeks));
    }

    $kode = (int) wp_remote_retrieve_response_code($respons);
    $body = json_decode(wp_remote_retrieve_body($respons), true);

    if ($kode >= 200 && $kode < 300) {
        $ids = [];
        foreach ((array) ($body['data'] ?? []) as $element_svar) {
            if (!empty($element_svar['id'])) {
                $ids[] = $element_svar['id'];
            }
        }

        // ID-telling: vern mot stille under-sending. Bare eksakt match = sendt.
        if (count($ids) === count($elementer)) {
            return bimverdi_nyhetsbrev_marker_batch($post_id, $batch_indeks, [
                'status'     => 'sendt',
                'resend_ids' => $ids,
                'notat'      => sprintf('%d sendt.', count($ids)),
            ]);
        }

        error_log(sprintf(
            '[bimverdi-nyhetsbrev-send] Batch %d for post %d: 2xx men %d av %d ID-er — delvis_usikker.',
            $batch_indeks, $post_id, count($ids), count($elementer)
        ));
        return bimverdi_nyhetsbrev_marker_batch($post_id, $batch_indeks, [
            'status'     => 'delvis_usikker',
            'resend_ids' => $ids,
            'notat'      => sprintf('2xx, men kun %d av %d ID-er returnert.', count($ids), count($elementer)),
        ]);
    }

    $api_melding = is_array($body) && !empty($body['message']) ? $body['message'] : wp_remote_retrieve_body($respons);

    // 409 = samme Idempotency-Key med ANNEN payload. Skal være umulig gitt
    // frysingen — inntreffer det, er noe fundamentalt galt: stopp alt.
    if ($kode === 409) {
        bimverdi_nyhetsbrev_marker_batch($post_id, $batch_indeks, [
            'notat' => 'FATAL 409 payload-drift: ' . $api_melding,
        ]);
        error_log(sprintf('[bimverdi-nyhetsbrev-send] FATAL 409 for post %d batch %d: %s', $post_id, $batch_indeks, $api_melding));
        return new WP_Error('payload_drift', 'Resend meldte 409 (payload-drift) — utsendelsen er stoppet.');
    }

    // 429: rate-limit. Kalleren (Unit 3) sover og prøver én gang til.
    if ($kode === 429) {
        bimverdi_nyhetsbrev_marker_batch($post_id, $batch_indeks, ['notat' => 'Rate-limit (429).']);
        return new WP_Error('rate_limit', 'Rate-limit fra Resend.', [
            'retry_after' => max(1, (int) wp_remote_retrieve_header($respons, 'retry-after')),
        ]);
    }

    // Øvrige 4xx: deterministisk avvist — batchen ble beviselig IKKE sendt.
    // Tilbake til pending (ærlig tilstand) + notat; kalleren kjører
    // karantene-analyse og ombygging. ALDRI blind retry på 4xx.
    if ($kode >= 400 && $kode < 500) {
        bimverdi_nyhetsbrev_marker_batch($post_id, $batch_indeks, [
            'status' => 'pending',
            'notat'  => sprintf('Avvist %d: %s', $kode, $api_melding),
        ]);
        return new WP_Error('avvist_4xx', sprintf('Batch %d avvist (%d).', $batch_indeks, $kode), [
            'http_kode'   => $kode,
            'api_melding' => $api_melding,
        ]);
    }

    // 5xx: kan være levert internt — forblir usikker (retry-bar m/ samme nøkkel).
    bimverdi_nyhetsbrev_marker_batch($post_id, $batch_indeks, [
        'notat' => sprintf('Serverfeil %d: %s', $kode, $api_melding),
    ]);
    return new WP_Error('usikker', sprintf('Batch %d: serverfeil %d — status usikker.', $batch_indeks, $kode));
}

/* -------------------------------------------------------------------------
 * 7. Masseutsendelse: kjøringsmotor (Unit 3).
 *
 * Orkestrerer batch-sendingen: atomisk kjøre-lås m/ heartbeat, usikre
 * batcher først (24-timers-vakt), avmeldings-/karantene-filtrering med
 * persist-før-send-ombygging, checkpoint per batch, fullføring + sent_at.
 *
 * ⛔ Motoren har INGEN egen miljøgate — vernet bor innerst i send_batch
 *    (Unit 2) og håndheves per API-kall. Gate-feil er FATALE for kjøringen:
 *    motoren stopper umiddelbart i stedet for å loope mot en stengt dør.
 *    Lokalt betyr det: motoren kan ta lås og lese tilstand, men første
 *    send-forsøk får massesend_deaktivert og alt stopper — null HTTP.
 * ---------------------------------------------------------------------- */

/** Meta-nøkkel for kjøre-låsen. */
const BV_NB_META_LAS = '_bv_nb_kjorer';

/** Meta-nøkkel for karantenelisten (mottakere avvist av Resend med 4xx). */
const BV_NB_META_KARANTENE = '_bv_nb_karantene';

/** Lås regnes som stale uten heartbeat i så mange sekunder. */
const BV_NB_LAS_STALE_SEK = 180;

/**
 * Blind re-send av usikker batch tillates kun innenfor dette vinduet —
 * Resend husker Idempotency-Keys i 24 t; etter 20 t krever vi manuell
 * verifisering mot Resend-dashboardet i stedet (24-timers-vakten).
 */
const BV_NB_RESEND_VINDU_SEK = 20 * HOUR_IN_SECONDS;

/**
 * Ta kjøre-låsen atomisk (add_post_meta unique = set-if-not-exists).
 * Fersk heartbeat → nekt. Stale lås kan kun overtas eksplisitt.
 *
 * @return string|WP_Error Eier-token ved suksess.
 */
function bimverdi_nyhetsbrev_las_ta($post_id, $overta_stale = false) {
    $post_id = (int) $post_id;
    $bruker  = function_exists('wp_get_current_user') ? wp_get_current_user() : null;
    $verdi   = [
        'token'     => wp_generate_uuid4(),
        'heartbeat' => time(),
        'startet'   => current_time('mysql'),
        'av'        => ($bruker && $bruker->exists()) ? $bruker->user_login : 'system/cli',
    ];

    if (add_post_meta($post_id, BV_NB_META_LAS, $verdi, true)) {
        return $verdi['token'];
    }

    $eksisterende = get_post_meta($post_id, BV_NB_META_LAS, true);
    $alder        = time() - (int) ($eksisterende['heartbeat'] ?? 0);

    if ($alder < BV_NB_LAS_STALE_SEK) {
        return new WP_Error('kjoring_pagar', sprintf(
            'En kjøring ser ut til å pågå fortsatt (heartbeat for %d s siden, startet av %s).',
            $alder, $eksisterende['av'] ?? 'ukjent'
        ));
    }

    if (!$overta_stale) {
        return new WP_Error('stale_las', sprintf(
            'Forrige kjøring etterlot en stale lås (%d s uten heartbeat). Overta eksplisitt for å fortsette.',
            $alder
        ));
    }

    error_log(sprintf(
        '[bimverdi-nyhetsbrev-send] Stale lås (%d s) for post %d OVERTAS av %s.',
        $alder, $post_id, $verdi['av']
    ));
    delete_post_meta($post_id, BV_NB_META_LAS);
    if (add_post_meta($post_id, BV_NB_META_LAS, $verdi, true)) {
        return $verdi['token'];
    }
    return new WP_Error('las_kapret', 'En annen prosess tok låsen i samme øyeblikk.');
}

/** Oppdater heartbeat — kun hvis vi fortsatt eier låsen. */
function bimverdi_nyhetsbrev_las_heartbeat($post_id, $token) {
    $las = get_post_meta((int) $post_id, BV_NB_META_LAS, true);
    if (is_array($las) && hash_equals((string) $las['token'], (string) $token)) {
        $las['heartbeat'] = time();
        update_post_meta((int) $post_id, BV_NB_META_LAS, $las);
        return true;
    }
    return false;
}

/** Slipp låsen — kun hvis vi eier den (en overtatt lås røres ikke). */
function bimverdi_nyhetsbrev_las_slipp($post_id, $token) {
    $las = get_post_meta((int) $post_id, BV_NB_META_LAS, true);
    if (is_array($las) && hash_equals((string) $las['token'], (string) $token)) {
        delete_post_meta((int) $post_id, BV_NB_META_LAS);
        return true;
    }
    return false;
}

/** Lås-info for UI: null hvis ulåst, ellers alder/ferskhet/eier. */
function bimverdi_nyhetsbrev_las_info($post_id) {
    $las = get_post_meta((int) $post_id, BV_NB_META_LAS, true);
    if (!is_array($las)) {
        return null;
    }
    $alder = time() - (int) ($las['heartbeat'] ?? 0);
    return [
        'alder' => $alder,
        'fersk' => $alder < BV_NB_LAS_STALE_SEK,
        'av'    => $las['av'] ?? 'ukjent',
    ];
}

/** Karanteneliste: mottakere Resend har avvist deterministisk (4xx). */
function bimverdi_nyhetsbrev_karantene_liste($post_id) {
    $liste = get_post_meta((int) $post_id, BV_NB_META_KARANTENE, true);
    return is_array($liste) ? $liste : [];
}

/**
 * Sett en mottaker i karantene. Read-modify-write er trygt her: skjer kun
 * under kjøre-låsen (én skriver av gangen).
 */
function bimverdi_nyhetsbrev_sett_karantene($post_id, array $mottaker, $arsak, $batch_indeks) {
    $liste   = bimverdi_nyhetsbrev_karantene_liste($post_id);
    $liste[] = [
        'id'    => (int) ($mottaker['id'] ?? 0),
        'email' => $mottaker['email'] ?? '',
        'arsak' => $arsak,
        'batch' => (int) $batch_indeks,
        'tid'   => current_time('mysql'),
    ];
    update_post_meta((int) $post_id, BV_NB_META_KARANTENE, $liste);
    error_log(sprintf(
        '[bimverdi-nyhetsbrev-send] KARANTENE for post %d: %s (batch %d) — %s',
        $post_id, $mottaker['email'] ?? '?', $batch_indeks, $arsak
    ));
}

/**
 * Filtrer en pending-batch sine mottakere: fjern nylig avmeldte og
 * karantenesatte. Brukes KUN på pending-batcher (aldri usikre — re-send
 * krever byte-identisk payload for idempotency-vernet; avveiningen er
 * dokumentert i planen).
 */
function bimverdi_nyhetsbrev_filtrer_mottakere($post_id, array $mottakere) {
    $karantene = [];
    foreach (bimverdi_nyhetsbrev_karantene_liste($post_id) as $k) {
        $karantene[strtolower($k['email'])] = true;
    }

    $beholdt = [];
    foreach ($mottakere as $m) {
        if (isset($karantene[strtolower($m['email'] ?? '')])) {
            continue;
        }
        if (!empty($m['id']) && bimverdi_nyhetsbrev_er_avmeldt((int) $m['id'])) {
            continue;
        }
        $beholdt[] = $m;
    }
    return $beholdt;
}

/**
 * Bygg om en batch med ny mottakerliste (persist-FØR-send-invarianten):
 *
 *   1. Gammel rad skrives som superseded MED innebygd kopi av den nye raden.
 *   2. Ny rad skrives som egen meta-rad.
 *
 * Dør prosessen mellom 1 og 2, gjenskaper batch_rader() den nye raden fra
 * kopien. Ingen rekkefølge etterlater en tilstand der gammel OG ny chunk
 * begge kan sendes. Gamle rader slettes ALDRI (audit-trail).
 *
 * @return array|null Den nye raden, eller null hvis alle ble filtrert bort.
 */
function bimverdi_nyhetsbrev_ombygg_batch($post_id, $i, array $nye_mottakere, $arsak) {
    $post_id = (int) $post_id;
    $rad     = bimverdi_nyhetsbrev_batch_rad($post_id, $i);
    if ($rad === null || in_array($rad['status'], ['sendt', 'superseded'], true)) {
        return null;
    }

    if (!$nye_mottakere) {
        bimverdi_nyhetsbrev_marker_batch($post_id, $i, [
            'status'        => 'superseded',
            'superseded_by' => null,
            'notat'         => 'Alle mottakere filtrert bort (' . $arsak . ') — ingenting å sende.',
        ]);
        return null;
    }

    $rader     = bimverdi_nyhetsbrev_batch_rader($post_id);
    $ny_indeks = max(array_keys($rader)) + 1;
    $opphav    = (int) ($rad['opphav'] ?? $i);
    $retry_nr  = (int) ($rad['retry_nr'] ?? 0) + 1;

    $ny_rad = [
        'indeks'        => $ny_indeks,
        'status'        => 'pending',
        'mottakere'     => array_values($nye_mottakere),
        // Ny payload krever NY idempotency-nøkkel (gammel nøkkel + annen
        // payload ville gitt 409 hos Resend).
        'nokkel'        => sprintf('nyhetsbrev-%d/batch-%d/retry-%d', $post_id, $opphav, $retry_nr),
        'forsok_tid'    => null,
        'forsok_ts'     => 0,
        'resend_ids'    => [],
        'superseded_by' => null,
        'notat'         => sprintf('Ombygd fra batch %d: %s', $i, $arsak),
        'opphav'        => $opphav,
        'retry_nr'      => $retry_nr,
    ];

    // Skriv 1: gammel rad superseded med innebygd erstatning (repair-kilde).
    bimverdi_nyhetsbrev_marker_batch($post_id, $i, [
        'status'        => 'superseded',
        'superseded_by' => $ny_indeks,
        'erstatning'    => $ny_rad,
        'notat'         => sprintf('Superseded av batch %d: %s', $ny_indeks, $arsak),
    ]);
    // Skriv 2: den nye raden selv.
    add_post_meta($post_id, BV_NB_META_BATCH_PREFIX . $ny_indeks, $ny_rad, true);

    return $ny_rad;
}

/**
 * KJØRINGSMOTOREN: send alle gjenstående batcher for en startet utsendelse.
 *
 * Forutsetter at bimverdi_nyhetsbrev_utsendelse_start() er kjørt. Kan trygt
 * kalles på nytt etter avbrudd («Fortsett») — sendte/superseded batcher
 * hoppes over, usikre re-sendes innenfor 20-timers-vinduet med samme
 * Idempotency-Key, eldre usikre flagges for manuell verifisering.
 *
 * @param int      $post_id      Nyhetsbrev-posten.
 * @param bool     $overta_stale Overta en stale kjøre-lås eksplisitt.
 * @param callable $sender       Test-søm: erstattes KUN av lokale tester med
 *                               en stub. Produksjonskoden (UI-handleren)
 *                               sender aldri dette argumentet. Den ekte
 *                               senderen bærer selv den fail-closed gaten,
 *                               så sømmen kan ikke brukes til å omgå den —
 *                               en stub sender ingenting i utgangspunktet.
 * @return array|WP_Error Rapport: status, tellere, ev. stoppårsak.
 */
function bimverdi_nyhetsbrev_utsendelse_kjor($post_id, $overta_stale = false, $sender = 'bimverdi_nyhetsbrev_send_batch') {
    $post_id  = (int) $post_id;
    $manifest = bimverdi_nyhetsbrev_utsendelse_manifest($post_id);
    if (!$manifest) {
        return new WP_Error('mangler_manifest', 'Ingen utsendelse er startet for dette brevet.');
    }
    if (bimverdi_nyhetsbrev_er_sendt($post_id)) {
        return new WP_Error('allerede_sendt', 'Utsendelsen er allerede fullført.');
    }

    // Hash-sjekk før låsen tas — nekter alt ved drift (send_batch sjekker
    // også, per kall, under låsen).
    $html = get_post_meta($post_id, '_bv_nyhetsbrev_html', true);
    if (!is_string($html) || hash('sha256', $html) !== $manifest['html_hash']) {
        return new WP_Error('payload_drift', 'Øyeblikksbildet matcher ikke manifestet — utsendelsen er stoppet.');
    }

    $las = bimverdi_nyhetsbrev_las_ta($post_id, $overta_stale);
    if (is_wp_error($las)) {
        return $las;
    }

    ignore_user_abort(true); // kjøringen fullfører selv om fanen lukkes

    // Backup-slipp ved fatal error/timeout-død der finally ikke rekker å
    // kjøre. Stale-overtagelse (3 min) dekker hard prosessdrap.
    register_shutdown_function(function () use ($post_id, $las) {
        bimverdi_nyhetsbrev_las_slipp($post_id, $las);
    });

    $fatale  = ['massesend_deaktivert', 'feil_miljo', 'mangler_api_nokkel', 'payload_drift', 'mangler_manifest'];
    $stoppet = null;
    $forsokt = []; // per-kjøring forsøksteller per batch — hindrer evig løkke
    $vakt    = 0;

    try {
        while (true) {
            if (++$vakt > 200) {
                $stoppet = 'vaktgrense';
                error_log(sprintf('[bimverdi-nyhetsbrev-send] Vaktgrense (200 runder) nådd for post %d — undersøk.', $post_id));
                break;
            }

            // Velg neste handling fra ferske rader: usikre re-send-kandidater
            // FØRST (24-timers-vakten avgjør), deretter pending.
            $rader = bimverdi_nyhetsbrev_batch_rader($post_id);
            $neste = null;
            $type  = null;

            foreach (['resend', 'pending'] as $fase) {
                foreach ($rader as $i => $rad) {
                    $status = $rad['status'] ?? 'pending';
                    if (in_array($status, ['sendt', 'superseded'], true)) {
                        continue;
                    }
                    if (!empty($rad['sperret']) || !empty($rad['manuell_verifisering'])) {
                        continue;
                    }
                    if (($forsokt[$i] ?? 0) >= 2) {
                        continue; // to forsøk per kjøring — så Fortsett senere
                    }

                    if ($fase === 'resend' && in_array($status, ['usikker', 'delvis_usikker'], true)) {
                        if (time() - (int) $rad['forsok_ts'] < BV_NB_RESEND_VINDU_SEK) {
                            $neste = $i;
                            $type  = 'resend';
                            break 2;
                        }
                        // 24-timers-vakten: for gammel til blind re-send.
                        bimverdi_nyhetsbrev_marker_batch($post_id, $i, [
                            'manuell_verifisering' => true,
                            'notat'                => 'Usikker > 20 t: krever manuell verifisering mot Resend-dashboardet.',
                        ]);
                        continue;
                    }

                    if ($fase === 'pending' && $status === 'pending') {
                        $neste = $i;
                        $type  = 'pending';
                        break 2;
                    }
                }
            }

            if ($neste === null) {
                break; // ingenting mer å gjøre i denne kjøringen
            }

            // Pending-batcher filtreres (avmeldte/karantene) FØR første send.
            // Usikre re-sendes uendret (idempotency krever identisk payload).
            if ($type === 'pending') {
                $rad      = $rader[$neste];
                $filtrert = bimverdi_nyhetsbrev_filtrer_mottakere($post_id, $rad['mottakere']);
                if (count($filtrert) !== count($rad['mottakere'])) {
                    bimverdi_nyhetsbrev_ombygg_batch($post_id, $neste, $filtrert, 'avmeldt/karantene-filtrering');
                    bimverdi_nyhetsbrev_las_heartbeat($post_id, $las);
                    continue; // ny rad plukkes opp i neste runde
                }
            }

            $forsokt[$neste] = ($forsokt[$neste] ?? 0) + 1;
            $res = call_user_func($sender, $post_id, $neste);
            bimverdi_nyhetsbrev_las_heartbeat($post_id, $las);

            if (is_wp_error($res)) {
                $kode = $res->get_error_code();

                if (in_array($kode, $fatale, true)) {
                    $stoppet = $kode;
                    break;
                }

                if ($kode === 'rate_limit') {
                    $data = $res->get_error_data();
                    sleep(min(30, max(1, (int) ($data['retry_after'] ?? 1))));
                    $forsokt[$neste]++;
                    $res2 = call_user_func($sender, $post_id, $neste);
                    bimverdi_nyhetsbrev_las_heartbeat($post_id, $las);
                    if (is_wp_error($res2) && in_array($res2->get_error_code(), $fatale, true)) {
                        $stoppet = $res2->get_error_code();
                        break;
                    }
                    // Fortsatt feil → raden står som usikker/pending; videre.
                } elseif ($kode === 'avvist_4xx') {
                    // Karantene-analyse: finn utløsende mottaker i API-meldingen.
                    $data    = $res->get_error_data();
                    $melding = strtolower((string) ($data['api_melding'] ?? ''));
                    $rad     = bimverdi_nyhetsbrev_batch_rad($post_id, $neste);
                    $treff   = [];
                    foreach ($rad['mottakere'] as $m) {
                        if ($melding !== '' && strpos($melding, strtolower($m['email'])) !== false) {
                            $treff[] = $m;
                        }
                    }
                    if (count($treff) === 1) {
                        bimverdi_nyhetsbrev_sett_karantene($post_id, $treff[0], $data['api_melding'] ?? 'Avvist av Resend (4xx)', $neste);
                        $gjenstaende = bimverdi_nyhetsbrev_filtrer_mottakere($post_id, $rad['mottakere']);
                        bimverdi_nyhetsbrev_ombygg_batch($post_id, $neste, $gjenstaende, 'karantene etter 4xx');
                        // Ombygd rad er fersk → nullstill forsøkstelleren for den nye.
                    } else {
                        // Uidentifiserbar 4xx: sperr raden — ALDRI blind retry.
                        bimverdi_nyhetsbrev_marker_batch($post_id, $neste, [
                            'sperret' => true,
                            'notat'   => 'Avvist (4xx) uten identifiserbar mottaker — krever manuell håndtering: ' . ($data['api_melding'] ?? ''),
                        ]);
                    }
                }
                // 'usikker' / 'batch_ferdig': raden er riktig merket — videre.
            }

            usleep(250000); // 4 kall/s — godt under Resends 5 req/s
        }
    } finally {
        bimverdi_nyhetsbrev_las_slipp($post_id, $las);
    }

    bimverdi_nyhetsbrev_utsendelse_fullfor_hvis_ferdig($post_id);

    $status = bimverdi_nyhetsbrev_utsendelse_status($post_id);
    return [
        'status'    => bimverdi_nyhetsbrev_er_sendt($post_id) ? 'fullfort' : ($stoppet ? 'stoppet' : 'delvis'),
        'stoppet'   => $stoppet,
        'batcher'   => $status['batcher'],
        'sendt'     => $status['mottakere_sendt'],
        'total'     => $status['mottakere_total'],
        'karantene' => count(bimverdi_nyhetsbrev_karantene_liste($post_id)),
    ];
}

/* -------------------------------------------------------------------------
 * 8. Masseutsendelse: admin-handlere (Unit 4).
 *
 * Bårds røde knapp: bekreftelses-mellomside → kjøring → rapport. Alle
 * handlere: manage_options + nonce. UI-en (metaboks + notiser) bor i
 * bimverdi-nyhetsbrev-cpt.php; her ligger kun POST-logikken.
 *
 * ⛔ Ingen av disse kan sende på localhost: kjøringsmotoren stopper på den
 *    fail-closed gaten i send_batch (massesend_deaktivert/feil_miljo) lenge
 *    før noe HTTP skjer. Mellomsiden og rapport-tilstandene er derfor fullt
 *    testbare lokalt uten risiko for ekte utsendelse.
 * ---------------------------------------------------------------------- */

/** Felles: kjør (eller gjenoppta) utsendelsen og redirect tilbake med notis. */
function bimverdi_nyhetsbrev_kjor_og_redirect($post_id, $overta_stale = false) {
    $tilbake = admin_url('post.php?post=' . (int) $post_id . '&action=edit');
    $rapport = bimverdi_nyhetsbrev_utsendelse_kjor($post_id, $overta_stale);

    if (is_wp_error($rapport)) {
        wp_safe_redirect(add_query_arg('bv_nb_notice', 'massesend_' . $rapport->get_error_code(), $tilbake));
        exit;
    }
    wp_safe_redirect(add_query_arg('bv_nb_notice', 'massesend_' . $rapport['status'], $tilbake));
    exit;
}

/**
 * Steg 1: bekreftelses-mellomside (plain HTML + nonce + PRG). Viser antall
 * mottakere, fryst emne og forhåndsvisnings-lenke, og krever eksplisitt
 * avkryssing før den endelige POST-en.
 */
add_action('admin_post_bimverdi_nyhetsbrev_bekreft', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Ingen tilgang.');
    }
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    check_admin_referer('bimverdi_nyhetsbrev_bekreft_' . $post_id);

    $tilbake = admin_url('post.php?post=' . $post_id . '&action=edit');

    if (!$post_id || get_post_type($post_id) !== BV_NYHETSBREV_CPT) {
        wp_die('Ugyldig nyhetsbrev.');
    }
    if (bimverdi_nyhetsbrev_er_sendt($post_id)) {
        wp_safe_redirect(add_query_arg('bv_nb_notice', 'massesend_allerede_sendt', $tilbake));
        exit;
    }
    if (!get_post_meta($post_id, '_bv_nyhetsbrev_html', true)) {
        wp_safe_redirect(add_query_arg('bv_nb_notice', 'massesend_mangler_snapshot', $tilbake));
        exit;
    }

    // Gaten må være åpen FØR vi viser bekreftelsen — ellers er knappen død.
    $gate = bimverdi_nyhetsbrev_massesend_gate();
    if (is_wp_error($gate)) {
        wp_safe_redirect(add_query_arg('bv_nb_notice', 'massesend_' . $gate->get_error_code(), $tilbake));
        exit;
    }

    $antall  = count(bimverdi_nyhetsbrev_mottakere());
    $emne    = wp_specialchars_decode(get_the_title($post_id), ENT_QUOTES);
    $preview = add_query_arg('bimverdi_nyhetsbrev_preview', $post_id, home_url('/'));

    nocache_headers();
    header('Content-Type: text/html; charset=UTF-8');
    ?><!DOCTYPE html>
<html lang="nb">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>Bekreft utsendelse – BIM Verdi</title>
</head>
<body style="margin:0;padding:0;background:#F7F5EF;font-family:Inter,system-ui,-apple-system,sans-serif;">
<div style="max-width:560px;margin:64px auto;padding:0 24px;">
    <p style="color:#FF8B5E;font-size:12px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;margin:0 0 12px;">BIM Verdi · Nyhetsbrev</p>
    <h1 style="color:#1A1A1A;font-size:26px;font-weight:500;margin:0 0 20px;">Bekreft utsendelse</h1>

    <div style="background:#fff;border:1px solid #D6D1C6;border-radius:10px;padding:24px;margin:0 0 24px;">
        <p style="margin:0 0 16px;color:#1A1A1A;font-size:15px;line-height:1.6;">
            Du er i ferd med å sende nyhetsbrevet til
            <strong style="font-size:18px;"><?php echo (int) $antall; ?> mottakere</strong>.
            Dette kan <strong>ikke angres</strong>.
        </p>
        <table style="width:100%;border-collapse:collapse;font-size:14px;color:#5A5A5A;">
            <tr><td style="padding:6px 0;width:90px;">Emne</td><td style="padding:6px 0;color:#1A1A1A;"><?php echo esc_html($emne); ?></td></tr>
            <tr><td style="padding:6px 0;border-top:1px solid #EFE9DE;">Mottakere</td><td style="padding:6px 0;border-top:1px solid #EFE9DE;color:#1A1A1A;"><?php echo (int) $antall; ?> medlemmer (avmeldte trukket fra)</td></tr>
            <tr><td style="padding:6px 0;border-top:1px solid #EFE9DE;">Avsender</td><td style="padding:6px 0;border-top:1px solid #EFE9DE;color:#1A1A1A;"><?php echo esc_html(defined('BIMVERDI_RESEND_FROM_EMAIL') ? BIMVERDI_RESEND_FROM_EMAIL : 'noreply@bimverdi.no'); ?></td></tr>
        </table>
        <p style="margin:16px 0 0;">
            <a href="<?php echo esc_url($preview); ?>" target="_blank" style="color:#1A1A1A;font-size:14px;">Forhåndsvis brevet i ny fane →</a>
        </p>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:0;">
        <input type="hidden" name="action" value="bimverdi_nyhetsbrev_massesend">
        <input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>">
        <?php wp_nonce_field('bimverdi_nyhetsbrev_massesend_' . $post_id); ?>
        <label style="display:flex;align-items:flex-start;gap:10px;margin:0 0 24px;color:#1A1A1A;font-size:15px;cursor:pointer;">
            <input type="checkbox" name="bekreft_avkrysset" value="1" style="margin-top:3px;width:18px;height:18px;">
            <span>Ja, send nyhetsbrevet til <?php echo (int) $antall; ?> mottakere nå.</span>
        </label>
        <div style="display:flex;gap:12px;align-items:center;">
            <button type="submit" style="background:#FF8B5E;color:#1A1A1A;border:none;border-radius:8px;padding:13px 28px;font-size:15px;font-weight:600;cursor:pointer;">
                Send nyhetsbrevet
            </button>
            <a href="<?php echo esc_url($tilbake); ?>" style="color:#5A5A5A;font-size:14px;">Avbryt</a>
        </div>
    </form>

    <p style="border-top:1px solid #D6D1C6;padding-top:20px;margin:40px 0 0;color:#5A5A5A;font-size:13px;line-height:1.6;">
        Lukker du fanen mens utsendelsen kjører, fortsetter den i bakgrunnen. Får du en
        feilside, betyr det <strong>ikke</strong> at utsendelsen feilet — last editoren på
        nytt og les statusrapporten før du eventuelt trykker «Fortsett».
    </p>
</div>
</body>
</html><?php
    exit;
});

/**
 * Steg 2: endelig kjøring. Krever avkryssing. Fryser manifestet (hvis ikke
 * allerede gjort) og kjører motoren synkront.
 */
add_action('admin_post_bimverdi_nyhetsbrev_massesend', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Ingen tilgang.');
    }
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    check_admin_referer('bimverdi_nyhetsbrev_massesend_' . $post_id);

    $tilbake = admin_url('post.php?post=' . $post_id . '&action=edit');

    if (!$post_id || get_post_type($post_id) !== BV_NYHETSBREV_CPT) {
        wp_die('Ugyldig nyhetsbrev.');
    }
    if (empty($_POST['bekreft_avkrysset'])) {
        wp_safe_redirect(add_query_arg('bv_nb_notice', 'massesend_ikke_bekreftet', $tilbake));
        exit;
    }
    if (bimverdi_nyhetsbrev_er_sendt($post_id)) {
        wp_safe_redirect(add_query_arg('bv_nb_notice', 'massesend_allerede_sendt', $tilbake));
        exit;
    }

    // Frys manifestet hvis dette er første kjøring (idempotent ved gjenopptak).
    if (!bimverdi_nyhetsbrev_utsendelse_startet($post_id)) {
        $start = bimverdi_nyhetsbrev_utsendelse_start($post_id);
        if (is_wp_error($start)) {
            wp_safe_redirect(add_query_arg('bv_nb_notice', 'massesend_' . $start->get_error_code(), $tilbake));
            exit;
        }
    }

    bimverdi_nyhetsbrev_kjor_og_redirect($post_id);
});

/** «Fortsett utsendelse» (gjenopptak). Ingen avkryssing — manifest finnes alt. */
add_action('admin_post_bimverdi_nyhetsbrev_fortsett', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Ingen tilgang.');
    }
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    check_admin_referer('bimverdi_nyhetsbrev_fortsett_' . $post_id);

    $tilbake = admin_url('post.php?post=' . $post_id . '&action=edit');

    if (!$post_id || get_post_type($post_id) !== BV_NYHETSBREV_CPT || !bimverdi_nyhetsbrev_utsendelse_startet($post_id)) {
        wp_safe_redirect(add_query_arg('bv_nb_notice', 'massesend_mangler_manifest', $tilbake));
        exit;
    }

    $overta_stale = !empty($_POST['overta_stale']);
    bimverdi_nyhetsbrev_kjor_og_redirect($post_id, $overta_stale);
});

/**
 * Manuell verifisering av en usikker batch (24-timers-vakten). Bård har
 * sjekket Resend-dashboardet og velger:
 *   - «marker_sendt»: brevet ER levert → marker sendt uten nye API-kall.
 *   - «resend»: brevet er IKKE levert → bygg om batchen med ny
 *     idempotency-nøkkel, klar for «Fortsett».
 * Begge valg logges på batch-raden med bruker + tid.
 */
add_action('admin_post_bimverdi_nyhetsbrev_verifiser', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Ingen tilgang.');
    }
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    check_admin_referer('bimverdi_nyhetsbrev_verifiser_' . $post_id);

    $tilbake = admin_url('post.php?post=' . $post_id . '&action=edit');
    $batch   = isset($_POST['batch_indeks']) ? (int) $_POST['batch_indeks'] : -1;
    $valg    = isset($_POST['valg']) ? sanitize_key($_POST['valg']) : '';
    $bruker  = wp_get_current_user()->user_login;

    if (!$post_id || get_post_type($post_id) !== BV_NYHETSBREV_CPT || $batch < 0) {
        wp_die('Ugyldig forespørsel.');
    }
    $rad = bimverdi_nyhetsbrev_batch_rad($post_id, $batch);
    if ($rad === null || empty($rad['manuell_verifisering'])) {
        wp_safe_redirect(add_query_arg('bv_nb_notice', 'massesend_verifisering_ugyldig', $tilbake));
        exit;
    }

    if ($valg === 'marker_sendt') {
        bimverdi_nyhetsbrev_marker_batch($post_id, $batch, [
            'status'               => 'sendt',
            'manuell_verifisering' => false,
            'notat'                => sprintf('Manuelt verifisert LEVERT i Resend-dashboardet av %s (%s).', $bruker, current_time('mysql')),
        ]);
        bimverdi_nyhetsbrev_utsendelse_fullfor_hvis_ferdig($post_id);
    } elseif ($valg === 'resend') {
        // Ny idempotency-nøkkel (ombygging) — gammel nøkkel kan være utløpt
        // ELLER fortsatt bundet til et forsøk vi nettopp avkreftet.
        bimverdi_nyhetsbrev_ombygg_batch(
            $post_id,
            $batch,
            $rad['mottakere'],
            sprintf('manuell re-send (verifisert IKKE levert) av %s', $bruker)
        );
    } else {
        wp_safe_redirect(add_query_arg('bv_nb_notice', 'massesend_verifisering_ugyldig', $tilbake));
        exit;
    }

    wp_safe_redirect(add_query_arg('bv_nb_notice', 'massesend_verifisert', $tilbake));
    exit;
});
