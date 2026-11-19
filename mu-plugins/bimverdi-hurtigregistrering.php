<?php
/**
 * Plugin Name: BIM Verdi — hurtigregistrering for ByggChat
 * Description: Krav 30. Navn og e-post via QR-kode på bimverdi.no/chat, rett inn i ByggChat.
 *              Den ordinære registreringen med foretakskobling gjøres senere.
 * Version: 1.0.0
 *
 * ══════════════════════════════════════════════════════════════════════════
 * HVA DETTE ER
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Krav:       bimverdi-context, bedrift/30-KRAV-hurtigregistrering-byggchat-v2.md
 * Beslutning: B-037 (hurtigbruker er et definert unntak fra foretakskoblingen)
 *             B-035 (generelle e-postdomener avvises, også her)
 *
 * Adresser:
 *   /chat/?k=<kampanjekode>  Inngangsside med skjema (HR-01, HR-02)
 *   /chat/lenke/?u=&t=       Engangslenker fra e-post (HR-05, HR-08)
 *   /chat/fullfor/           Fullføring: passord, mobil, stilling, vilkår (HR-09)
 *   /chat/slett/             Slett kontoen selv (HR-13)
 *
 * Innstillinger og statistikk: wp-admin → Brukere → Hurtigregistrering (HR-10, HR-12, HR-15).
 *
 * ══════════════════════════════════════════════════════════════════════════
 * HURTIGBRUKEREN ER IKKE INNLOGGET PÅ BIMVERDI.NO
 * ══════════════════════════════════════════════════════════════════════════
 *
 * HR-14 sier at en hurtigbruker bare har tilgang til ByggChat og ellers regnes
 * som Gjest. Temaet sjekker is_user_logged_in() på over 70 steder. I stedet for
 * å rette alle dem får hurtigbrukeren ALDRI en WordPress-innlogging:
 *
 *   - Hun får en egen, signert cookie (bv_hurtig) som bare denne filen og
 *     ByggChat-overgangen (/?byggchat_go=1) leser.
 *   - determine_current_user, authenticate og allow_password_reset stenger
 *     alle veier til en vanlig WordPress-sesjon for rollen byggchat_gjest.
 *
 * Resten av nettstedet ser derfor en Gjest, uten at noe annet må endres.
 * Cookien varer like lenge som «Husk meg» (HR-06), så hun slipper å oppgi
 * e-posten igjen på samme mobil.
 *
 * Ved fullføring (HR-09) får brukeren rollen medlem og en vanlig innlogging.
 *
 * ══════════════════════════════════════════════════════════════════════════
 * ENGANGSLENKER OG E-POSTSKANNERE
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Outlook Safe Links og lignende åpner lenkene i e-posten før mottakeren gjør
 * det. En lenke som ble brukt opp ved første GET, ville vært død når brukeren
 * klikket. Derfor gjør GET ingenting: siden sender seg selv videre med POST
 * (automatisk med JavaScript, knapp uten). Lenken brukes opp først ved POST.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

const BV_HURTIG_ROLLE        = 'byggchat_gjest';
const BV_HURTIG_COOKIE       = 'bv_hurtig';
const BV_HURTIG_INNSTILLING  = 'bv_hurtig_innstillinger';
const BV_HURTIG_STATISTIKK   = 'bv_hurtig_statistikk';
const BV_HURTIG_CRON         = 'bimverdi_hurtig_daglig';
const BV_HURTIG_REWRITE_VER  = '1';

/* =========================================================================
 * Innstillinger
 * ====================================================================== */

/**
 * Innstillingene med standardverdier fra kravet.
 *
 * modus: 'av' (stengt), 'test' (bare testkodene), 'pa' (åpent, unntatt sperrede koder).
 * Standard er 'av', så ingenting er synlig før noen skrur det på i wp-admin.
 *
 * @return array
 */
function bimverdi_hurtig_innstillinger() {
    $lagret = get_option(BV_HURTIG_INNSTILLING, []);
    return wp_parse_args(is_array($lagret) ? $lagret : [], [
        'modus'              => 'av',
        'testkoder'          => [],
        'sperrede_koder'     => [],
        'kvote_ubekreftet'   => 10,
        'kvote_bekreftet'    => 50,
        'utlop_dager'        => 30,
        'slett_etter_dager'  => 30,
        'maks_per_ip_time'   => 5,
        'maks_epost_dogn'    => 3,
        'turnstile_nokkel'   => '',
        'turnstile_hemmelig' => '',
        'ga4_api_hemmelig'   => '',
    ]);
}

/**
 * Kampanjekoden fra URL eller skjema: maks 32 tegn, [a-z0-9-]. Ugyldig gir ''.
 */
function bimverdi_hurtig_kode($verdi) {
    $kode = strtolower(trim((string) $verdi));
    return preg_match('/^[a-z0-9-]{1,32}$/', $kode) ? $kode : '';
}

/**
 * Er skjemaet åpent for denne kampanjekoden? (HR-12)
 */
function bimverdi_hurtig_er_apen($kode) {
    $inn = bimverdi_hurtig_innstillinger();
    if ($inn['modus'] === 'test') {
        return $kode !== '' && in_array($kode, (array) $inn['testkoder'], true);
    }
    if ($inn['modus'] === 'pa') {
        return !in_array($kode, (array) $inn['sperrede_koder'], true);
    }
    return false;
}

/* =========================================================================
 * Rolle
 * ====================================================================== */

add_action('init', function () {
    // Ingen rettigheter. Rollen finnes for å skille hurtigbrukere i brukerlisten
    // (HR-15) og for å stenge innlogging (se lenger ned). 'read' er ikke med.
    if (!get_role(BV_HURTIG_ROLLE)) {
        add_role(BV_HURTIG_ROLLE, 'Hurtigbruker (ByggChat)', []);
    }
});

/**
 * Er brukeren en hurtigbruker (ikke fullført)?
 *
 * @param WP_User|int|null $bruker
 */
function bimverdi_hurtig_er_hurtigbruker($bruker) {
    if (is_numeric($bruker)) {
        $bruker = get_userdata((int) $bruker);
    }
    return $bruker instanceof WP_User && in_array(BV_HURTIG_ROLLE, (array) $bruker->roles, true);
}

/* =========================================================================
 * Ingen WordPress-innlogging for hurtigbrukere (HR-14)
 * ====================================================================== */

// Selv om en hurtigbruker skulle ha fått en gyldig auth-cookie, regnes hun ikke som innlogget.
add_filter('determine_current_user', function ($user_id) {
    if ($user_id && bimverdi_hurtig_er_hurtigbruker((int) $user_id)) {
        return 0;
    }
    return $user_id;
}, 99);

// Innloggingsskjemaet: hurtigbrukeren har ikke noe passord hun kjenner, men stopp uansett.
add_filter('authenticate', function ($user) {
    if ($user instanceof WP_User && bimverdi_hurtig_er_hurtigbruker($user)) {
        return new WP_Error('hurtigbruker', 'Fullfør registreringen før du logger inn.');
    }
    return $user;
}, 100);

// Glemt passord: ingen tilbakestilling. Hun får i stedet lenken til fullføring på e-post.
add_filter('allow_password_reset', function ($tillat, $user_id) {
    if (bimverdi_hurtig_er_hurtigbruker((int) $user_id)) {
        $bruker = get_userdata((int) $user_id);
        if ($bruker) {
            bimverdi_hurtig_send_lenke_epost($bruker);
        }
        return false;
    }
    return $tillat;
}, 10, 2);

/* =========================================================================
 * Cookien bv_hurtig
 * ====================================================================== */

/**
 * Hemmeligheten per bruker. Byttes ved fullføring og sletting, så gamle
 * cookier slutter å virke.
 */
function bimverdi_hurtig_okt_hemmelighet($user_id) {
    $hemmelig = (string) get_user_meta($user_id, 'bv_hurtig_okt', true);
    if ($hemmelig === '') {
        $hemmelig = wp_generate_password(32, false);
        update_user_meta($user_id, 'bv_hurtig_okt', $hemmelig);
    }
    return $hemmelig;
}

function bimverdi_hurtig_cookie_signatur($user_id, $utloper) {
    return hash_hmac('sha256', $user_id . '|' . $utloper . '|' . bimverdi_hurtig_okt_hemmelighet($user_id), wp_salt('auth'));
}

/**
 * Sett cookien. Varer like lenge som WordPress' «Husk meg» (14 dager, HR-06).
 */
function bimverdi_hurtig_sett_cookie($user_id) {
    $varighet = (int) apply_filters('auth_cookie_expiration', 14 * DAY_IN_SECONDS, $user_id, true);
    $utloper  = time() + $varighet;
    $verdi    = $user_id . '|' . $utloper . '|' . bimverdi_hurtig_cookie_signatur($user_id, $utloper);

    setcookie(BV_HURTIG_COOKIE, $verdi, [
        'expires'  => $utloper,
        'path'     => COOKIEPATH ?: '/',
        'domain'   => COOKIE_DOMAIN ?: '',
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[BV_HURTIG_COOKIE] = $verdi;
}

function bimverdi_hurtig_fjern_cookie() {
    setcookie(BV_HURTIG_COOKIE, '', [
        'expires'  => time() - YEAR_IN_SECONDS,
        'path'     => COOKIEPATH ?: '/',
        'domain'   => COOKIE_DOMAIN ?: '',
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE[BV_HURTIG_COOKIE]);
}

/**
 * Hurtigbrukeren i cookien, uansett status. Null hvis cookien mangler eller er ugyldig.
 *
 * @return WP_User|null
 */
function bimverdi_hurtig_bruker_fra_cookie() {
    $verdi = isset($_COOKIE[BV_HURTIG_COOKIE]) ? (string) wp_unslash($_COOKIE[BV_HURTIG_COOKIE]) : '';
    $deler = explode('|', $verdi);
    if (count($deler) !== 3) {
        return null;
    }
    [$user_id, $utloper, $signatur] = $deler;
    $user_id = (int) $user_id;
    $utloper = (int) $utloper;
    if ($user_id <= 0 || $utloper < time()) {
        return null;
    }
    $bruker = get_userdata($user_id);
    if (!bimverdi_hurtig_er_hurtigbruker($bruker)) {
        return null;
    }
    if (!hash_equals(bimverdi_hurtig_cookie_signatur($user_id, $utloper), $signatur)) {
        return null;
    }
    return $bruker;
}

function bimverdi_hurtig_status($user_id) {
    return (string) get_user_meta($user_id, 'bv_hurtig_status', true);
}

/* =========================================================================
 * Statistikk per kampanjekode (HR-15)
 *
 * Tellere i en option, ikke tellinger av brukere. Hurtigbrukere slettes etter
 * utløp (HR-11), og da ville tallene ellers forsvunnet med dem.
 * ====================================================================== */

function bimverdi_hurtig_tell($kode, $hendelse) {
    $stat = get_option(BV_HURTIG_STATISTIKK, []);
    if (!is_array($stat)) {
        $stat = [];
    }
    $kode = (string) $kode;
    if (!isset($stat[$kode])) {
        $stat[$kode] = ['registrert' => 0, 'bekreftet' => 0, 'fullfort' => 0, 'utlopt' => 0];
    }
    $stat[$kode][$hendelse] = (int) ($stat[$kode][$hendelse] ?? 0) + 1;
    update_option(BV_HURTIG_STATISTIKK, $stat, false);

    bimverdi_hurtig_ga4($hendelse, $kode);
}

/**
 * Hendelse til GA4 via Measurement Protocol (HR-15).
 *
 * Gjøres på serveren fordi brukeren sendes rett videre til ByggChat og aldri ser
 * en side der gtag kunne kjørt. Krever API-hemmeligheten i innstillingene; uten
 * den sendes ingenting. Ingen personopplysninger: bare hendelse og kampanjekode.
 */
function bimverdi_hurtig_ga4($hendelse, $kode) {
    if (!in_array($hendelse, ['registrert', 'fullfort'], true)) {
        return;
    }
    $hemmelig = (string) bimverdi_hurtig_innstillinger()['ga4_api_hemmelig'];
    if ($hemmelig === '') {
        return;
    }
    $url = add_query_arg([
        'measurement_id' => 'G-QJJEWJBXZ7',
        'api_secret'     => $hemmelig,
    ], 'https://www.google-analytics.com/mp/collect');

    wp_remote_post($url, [
        'blocking' => false,
        'timeout'  => 2,
        'headers'  => ['Content-Type' => 'application/json'],
        'body'     => wp_json_encode([
            'client_id' => wp_generate_uuid4(),
            'events'    => [[
                'name'   => $hendelse === 'registrert' ? 'hurtigregistrering' : 'hurtigregistrering_fullfort',
                'params' => ['kampanje' => $kode !== '' ? $kode : '(ingen)'],
            ]],
        ]),
    ]);
}

/* =========================================================================
 * Engangslenker
 * ====================================================================== */

/**
 * Lag en engangslenke for en bruker.
 *
 * Formål:
 *   'fullfor' — bekrefter e-posten og åpner fullføringen (HR-08), 7 dager
 *   'chat'    — bekrefter e-posten og åpner ByggChat, 7 dager
 *   'slett'   — åpner siden for å slette kontoen (HR-13), 7 dager
 *   'logginn' — vanlig bruker som skrev e-posten sin i skjemaet (HR-05), 24 timer
 *
 * Bare hashen lagres.
 */
function bimverdi_hurtig_lag_lenke($user_id, $formal) {
    $varighet = ($formal === 'logginn') ? DAY_IN_SECONDS : 7 * DAY_IN_SECONDS;
    $token    = wp_generate_password(40, false);

    $lenker = get_user_meta($user_id, 'bv_hurtig_lenker', true);
    $lenker = is_array($lenker) ? $lenker : [];
    foreach ($lenker as $hash => $info) {
        if (($info['utloper'] ?? 0) < time()) {
            unset($lenker[$hash]);
        }
    }
    $lenker[hash('sha256', $token)] = ['formal' => $formal, 'utloper' => time() + $varighet];
    update_user_meta($user_id, 'bv_hurtig_lenker', $lenker);

    return add_query_arg(['u' => $user_id, 't' => $token], home_url('/chat/lenke/'));
}

/**
 * Finn lenken. Bruker den opp når $bruk_opp er sann.
 *
 * @return string|null Formålet, eller null hvis lenken er ugyldig eller utløpt.
 */
function bimverdi_hurtig_sjekk_lenke($user_id, $token, $bruk_opp) {
    $lenker = get_user_meta($user_id, 'bv_hurtig_lenker', true);
    if (!is_array($lenker) || $token === '') {
        return null;
    }
    $hash = hash('sha256', $token);
    if (!isset($lenker[$hash]) || ($lenker[$hash]['utloper'] ?? 0) < time()) {
        return null;
    }
    $formal = (string) $lenker[$hash]['formal'];
    if ($bruk_opp) {
        unset($lenker[$hash]);
        update_user_meta($user_id, 'bv_hurtig_lenker', $lenker);
    }
    return $formal;
}

/* =========================================================================
 * E-post (B-030: felles skjelett)
 * ====================================================================== */

/**
 * Maks e-poster per adresse per døgn (HR-12). Teller opp når sjekken går gjennom.
 */
function bimverdi_hurtig_epostgrense_ok($epost) {
    $nokkel = 'bv_hurtig_epost_' . md5(strtolower($epost));
    $antall = (int) get_transient($nokkel);
    if ($antall >= (int) bimverdi_hurtig_innstillinger()['maks_epost_dogn']) {
        return false;
    }
    set_transient($nokkel, $antall + 1, DAY_IN_SECONDS);
    return true;
}

function bimverdi_hurtig_send($til, $emne, $html) {
    return wp_mail($til, $emne, $html, [
        'Content-Type: text/html; charset=UTF-8',
        'From: BIM Verdi <noreply@bimverdi.no>',
    ]);
}

function bimverdi_hurtig_slett_footer($user_id) {
    return 'Du får denne e-posten fordi adressen ble brukt til å starte ByggChat på bimverdi.no. '
        . 'Vil du ikke ha kontoen? <a href="' . esc_url(bimverdi_hurtig_lag_lenke($user_id, 'slett')) . '" style="color: #6B6B6B;">Slett den her</a>.';
}

/**
 * Velkomst-e-posten etter registrering (HR-08).
 */
function bimverdi_hurtig_send_velkomst(WP_User $bruker) {
    if (!bimverdi_hurtig_epostgrense_ok($bruker->user_email)) {
        return false;
    }
    $fornavn = (string) get_user_meta($bruker->ID, 'first_name', true);

    $innhold = bimverdi_epost_avsnitt('Hei ' . esc_html($fornavn ?: $bruker->display_name) . ',')
        . bimverdi_epost_avsnitt('Velkommen til ByggChat. Du kan stille spørsmål med en gang.')
        . bimverdi_epost_avsnitt('Bekreft e-postadressen din for å få flere spørsmål. Når du er klar, fullfører du registreringen og kobler deg til foretaket ditt. Da får du full tilgang til BIM Verdi. Det tar ett minutt.');

    $etter = bimverdi_epost_avsnitt(
        '<a href="' . esc_url(bimverdi_hurtig_lag_lenke($bruker->ID, 'chat')) . '" style="color: #1A1A1A; font-weight: 500;">Gå rett til ByggChat</a>',
        ['storrelse' => '14px', 'margin' => '16px 0 0 0']
    ) . bimverdi_epost_avsnitt(
        'Lenkene gjelder i 7 dager. Etter det kan du be om en ny lenke på bimverdi.no/chat.',
        ['storrelse' => '13px', 'farge' => '#6B6B6B', 'margin' => '16px 0 0 0']
    );

    $html = bimverdi_epost_dokument([
        'tittel'    => 'Velkommen til ByggChat',
        'innhold'   => $innhold,
        'cta_url'   => bimverdi_hurtig_lag_lenke($bruker->ID, 'fullfor'),
        'cta_tekst' => 'Bekreft og fullfør registreringen',
        'etter_cta' => $etter,
        'footer'    => bimverdi_hurtig_slett_footer($bruker->ID),
    ]);

    return bimverdi_hurtig_send($bruker->user_email, 'Velkommen til ByggChat – fullfør registreringen', $html);
}

/**
 * Påminnelse før utløp (HR-11).
 */
function bimverdi_hurtig_send_paminnelse(WP_User $bruker, $dager_igjen) {
    $innhold = bimverdi_epost_avsnitt('Hei ' . esc_html($bruker->display_name) . ',')
        . bimverdi_epost_avsnitt(sprintf(
            'Tilgangen din til ByggChat stenges om %d dager. Fullfør registreringen og koble deg til foretaket ditt, så beholder du tilgangen og samtalene dine.',
            (int) $dager_igjen
        ));

    $html = bimverdi_epost_dokument([
        'tittel'    => 'Fullfør registreringen',
        'innhold'   => $innhold,
        'cta_url'   => bimverdi_hurtig_lag_lenke($bruker->ID, 'fullfor'),
        'cta_tekst' => 'Fullfør registreringen',
        'footer'    => bimverdi_hurtig_slett_footer($bruker->ID),
    ]);

    return bimverdi_hurtig_send($bruker->user_email, 'Tilgangen din til ByggChat stenges snart', $html);
}

/**
 * E-posten når adressen finnes fra før (HR-05), eller når noen ber om ny lenke.
 *
 * Hurtigbruker: lenke til ByggChat og fullføring. Vanlig bruker: innloggingslenke.
 */
function bimverdi_hurtig_send_lenke_epost(WP_User $bruker) {
    if (!bimverdi_hurtig_epostgrense_ok($bruker->user_email)) {
        return false;
    }

    if (bimverdi_hurtig_er_hurtigbruker($bruker)) {
        $innhold = bimverdi_epost_avsnitt('Hei ' . esc_html($bruker->display_name) . ',')
            . bimverdi_epost_avsnitt('Her er lenken din til ByggChat. Den logger deg inn på denne enheten.');
        $etter = bimverdi_epost_avsnitt(
            '<a href="' . esc_url(bimverdi_hurtig_lag_lenke($bruker->ID, 'fullfor')) . '" style="color: #1A1A1A; font-weight: 500;">Fullfør registreringen</a>',
            ['storrelse' => '14px', 'margin' => '16px 0 0 0']
        );
        $html = bimverdi_epost_dokument([
            'tittel'    => 'Lenke til ByggChat',
            'innhold'   => $innhold,
            'cta_url'   => bimverdi_hurtig_lag_lenke($bruker->ID, 'chat'),
            'cta_tekst' => 'Åpne ByggChat',
            'etter_cta' => $etter,
            'footer'    => bimverdi_hurtig_slett_footer($bruker->ID),
        ]);
        return bimverdi_hurtig_send($bruker->user_email, 'Lenke til ByggChat', $html);
    }

    $innhold = bimverdi_epost_avsnitt('Hei ' . esc_html($bruker->display_name) . ',')
        . bimverdi_epost_avsnitt('Du har allerede en konto hos BIM Verdi. Klikk på knappen for å logge inn og åpne ByggChat. Lenken gjelder i 24 timer.');
    $html = bimverdi_epost_dokument([
        'tittel'    => 'Logg inn på BIM Verdi',
        'innhold'   => $innhold,
        'cta_url'   => bimverdi_hurtig_lag_lenke($bruker->ID, 'logginn'),
        'cta_tekst' => 'Logg inn og åpne ByggChat',
        'footer'    => 'Har du ikke bedt om dette, kan du se bort fra e-posten. Ingen kan logge inn uten lenken.',
    ]);
    return bimverdi_hurtig_send($bruker->user_email, 'Logg inn på BIM Verdi', $html);
}

/* =========================================================================
 * Adresser
 * ====================================================================== */

add_action('init', function () {
    add_rewrite_rule('^chat/?$', 'index.php?bv_hurtig=inngang', 'top');
    add_rewrite_rule('^chat/(lenke|fullfor|slett)/?$', 'index.php?bv_hurtig=$matches[1]', 'top');
});

// Nye regler må skrives til databasen én gang etter utrulling. Det gjøres på
// wp_loaded, når alle andre har lagt inn sine regler, så ingen av dem mistes.
add_action('wp_loaded', function () {
    if (get_option('bv_hurtig_rewrite_versjon') !== BV_HURTIG_REWRITE_VER) {
        flush_rewrite_rules(false);
        update_option('bv_hurtig_rewrite_versjon', BV_HURTIG_REWRITE_VER);
    }
});

add_filter('query_vars', function ($vars) {
    $vars[] = 'bv_hurtig';
    return $vars;
});

add_action('template_redirect', function () {
    $side = get_query_var('bv_hurtig');
    if (!$side) {
        return;
    }
    nocache_headers();

    switch ($side) {
        case 'inngang':
            bimverdi_hurtig_side_inngang();
            break;
        case 'lenke':
            bimverdi_hurtig_side_lenke();
            break;
        case 'fullfor':
            bimverdi_hurtig_side_fullfor();
            break;
        case 'slett':
            bimverdi_hurtig_side_slett();
            break;
    }
    exit;
});

/* =========================================================================
 * Inngangssiden /chat (HR-01–HR-05, HR-12)
 * ====================================================================== */

function bimverdi_hurtig_byggchat_url() {
    return home_url('/?byggchat_go=1');
}

function bimverdi_hurtig_side_inngang() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['bv_hurtig_ny_lenke'])) {
            bimverdi_hurtig_behandle_ny_lenke();
        }
        bimverdi_hurtig_behandle_skjema();
        return;
    }

    // Innlogget, eller hurtigbruker med gyldig cookie: rett til ByggChat (HR-01).
    $hurtig = bimverdi_hurtig_bruker_fra_cookie();
    if (is_user_logged_in() || ($hurtig && bimverdi_hurtig_status($hurtig->ID) !== 'deaktivert')) {
        wp_redirect(bimverdi_hurtig_byggchat_url());
        exit;
    }

    $kode = bimverdi_hurtig_kode($_GET['k'] ?? '');
    $feil = sanitize_key($_GET['feil'] ?? '');

    if (isset($_GET['sendt'])) {
        bimverdi_hurtig_vis('Sjekk e-posten din', bimverdi_hurtig_html_sendt());
        return;
    }

    if (!bimverdi_hurtig_er_apen($kode)) {
        bimverdi_hurtig_vis('ByggChat', '<h1>ByggChat</h1>'
            . '<p>Hurtigregistreringen er ikke åpen akkurat nå.</p>'
            . '<p><a class="knapp" href="' . esc_url(wp_login_url(bimverdi_hurtig_byggchat_url())) . '">Logg inn</a></p>'
            . '<p class="liten">Har du ikke konto? <a href="' . esc_url(home_url('/registrer/')) . '">Registrer deg</a>.</p>');
        return;
    }

    bimverdi_hurtig_vis('Start ByggChat', bimverdi_hurtig_html_skjema($kode, $feil));
}

function bimverdi_hurtig_feiltekst($feil) {
    $tekster = [
        'navn'     => 'Skriv inn navnet ditt.',
        'epost'    => 'Skriv inn en gyldig e-postadresse.',
        'domene'   => 'Vi finner ikke e-postdomenet. Sjekk at adressen er riktig.',
        'vilkar'   => 'Du må godta vilkårene for å fortsette.',
        'grense'   => 'For mange registreringer fra denne tilkoblingen. Prøv igjen om en time.',
        'robot'    => 'Vi kunne ikke bekrefte at du er et menneske. Prøv igjen.',
        'utlopt'   => 'Skjemaet ble for gammelt. Prøv igjen.',
        'system'   => 'Noe gikk galt. Prøv igjen om litt.',
    ];
    return $tekster[$feil] ?? '';
}

/**
 * B-035: samme melding som i den ordinære registreringen (krav 20 v5).
 */
function bimverdi_hurtig_html_generelt_domene() {
    $skjema = esc_url(home_url('/tilbakemelding/'));
    return '<div class="varsel varsel-gronn">'
        . '<p class="varsel-tittel">Du må bruke en annen e-post-adresse.<br><span lang="en">You need to use a different email address.</span></p>'
        . '<p><strong>BIM Verdi er et bedriftsnettverk</strong><br>'
        . 'For å registrere deg må du være tilknyttet et foretak som er registrert i Norge. '
        . 'Bruk e-postadressen fra jobben din. Generelle e-postadresser som Gmail, Hotmail og Outlook kan ikke brukes.<br>'
        . 'Har du ikke en jobb-e-post? Bruk <a href="' . $skjema . '"><strong>SKJEMAET FOR MANUELL BEHANDLING</strong></a>, så hjelper vi deg.</p>'
        . '<p lang="en"><strong>BIM Verdi is a business network</strong><br>'
        . 'To register, you must be connected to a company registered in Norway. '
        . 'Please use your work email address. Personal email addresses such as Gmail, Hotmail and Outlook are not accepted.<br>'
        . 'Don\'t have a work email? Use <a href="' . $skjema . '"><strong>OUR FORM FOR MANUAL PROCESSING</strong></a> and we will help you.</p>'
        . '</div>';
}

function bimverdi_hurtig_html_skjema($kode, $feil) {
    $inn      = bimverdi_hurtig_innstillinger();
    $turnstile = (string) $inn['turnstile_nokkel'];

    $varsel = '';
    if ($feil === 'generelt') {
        $varsel = bimverdi_hurtig_html_generelt_domene();
    } elseif (bimverdi_hurtig_feiltekst($feil) !== '') {
        $varsel = '<p class="varsel" role="alert">' . esc_html(bimverdi_hurtig_feiltekst($feil)) . '</p>';
    }
    $feltfeil = function ($felt) use ($feil) {
        $hvilke = ['navn' => ['navn'], 'epost' => ['epost', 'domene'], 'vilkar' => ['vilkar']];
        return in_array($feil, $hvilke[$felt] ?? [], true)
            ? '<span class="feltfeil">' . esc_html(bimverdi_hurtig_feiltekst($feil)) . '</span>'
            : '';
    };

    ob_start();
    ?>
    <h1>Spør ByggChat</h1>
    <p>Spør ByggChat om BIM, standarder og digitalisering i byggenæringen. Skriv inn navn og e-post – så er du i gang.</p>
    <?php echo ($feil === 'generelt' || in_array($feil, ['grense', 'robot', 'utlopt', 'system'], true)) ? $varsel : ''; ?>
    <form method="post" action="<?php echo esc_url(home_url('/chat/')); ?>">
        <?php wp_nonce_field('bv_hurtig_skjema', '_bv_nonce'); ?>
        <input type="hidden" name="bv_hurtig_skjema" value="1">
        <input type="hidden" name="k" value="<?php echo esc_attr($kode); ?>">
        <div class="hp" aria-hidden="true">
            <label>Nettsted <input type="text" name="nettsted" tabindex="-1" autocomplete="off"></label>
        </div>

        <label for="bv-navn">Navn</label>
        <input id="bv-navn" type="text" name="navn" required autocomplete="name" maxlength="100">
        <?php echo $feltfeil('navn'); ?>

        <label for="bv-epost">E-post på jobben</label>
        <input id="bv-epost" type="email" name="epost" required inputmode="email" autocomplete="email" maxlength="100">
        <?php echo $feltfeil('epost'); ?>

        <label class="avkrysning">
            <input type="checkbox" name="vilkar" value="1" required>
            <span>Jeg godtar vilkårene og <a href="<?php echo esc_url(home_url('/personvern/')); ?>" target="_blank" rel="noopener">personvernerklæringen</a>.</span>
        </label>
        <?php echo $feltfeil('vilkar'); ?>

        <?php if ($turnstile !== '') : ?>
            <div class="cf-turnstile" data-sitekey="<?php echo esc_attr($turnstile); ?>" data-appearance="interaction-only" data-language="nb"></div>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <?php endif; ?>

        <button type="submit" class="knapp">Start ByggChat</button>
    </form>
    <p class="liten">Har du konto? <a href="<?php echo esc_url(wp_login_url(bimverdi_hurtig_byggchat_url())); ?>">Logg inn</a></p>
    <?php
    return ob_get_clean();
}

function bimverdi_hurtig_html_sendt() {
    return '<h1>Sjekk e-posten din</h1>'
        . '<p>Vi har sendt deg en lenke for å logge inn. Finner du den ikke, kan du se i søppelposten.</p>';
}

/**
 * Hent IP-adressen. Servebolt står bak en proxy som setter X-Forwarded-For.
 */
function bimverdi_hurtig_ip() {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $felt) {
        if (!empty($_SERVER[$felt])) {
            $ip = trim(explode(',', (string) $_SERVER[$felt])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

function bimverdi_hurtig_tilbake($kode, $feil) {
    wp_redirect(add_query_arg(array_filter(['k' => $kode, 'feil' => $feil]), home_url('/chat/')));
    exit;
}

function bimverdi_hurtig_til_sendt() {
    wp_redirect(add_query_arg('sendt', '1', home_url('/chat/')));
    exit;
}

/**
 * Usynlig robotsjekk (HR-12). Uten nøkler i innstillingene hoppes den over.
 */
function bimverdi_hurtig_turnstile_ok() {
    $hemmelig = (string) bimverdi_hurtig_innstillinger()['turnstile_hemmelig'];
    if ($hemmelig === '' || (string) bimverdi_hurtig_innstillinger()['turnstile_nokkel'] === '') {
        return true;
    }
    $svar = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
        'timeout' => 5,
        'body'    => [
            'secret'   => $hemmelig,
            'response' => sanitize_text_field(wp_unslash($_POST['cf-turnstile-response'] ?? '')),
            'remoteip' => bimverdi_hurtig_ip(),
        ],
    ]);
    if (is_wp_error($svar)) {
        // Cloudflare svarer ikke: slipp gjennom heller enn å stenge standen. Ratebegrensningen gjelder fortsatt.
        error_log('[bimverdi-hurtig] Turnstile svarte ikke: ' . $svar->get_error_message());
        return true;
    }
    $data = json_decode(wp_remote_retrieve_body($svar), true);
    return !empty($data['success']);
}

/**
 * Ratebegrensning per IP (HR-12). Teller opp når den går gjennom.
 */
function bimverdi_hurtig_ip_ok() {
    $nokkel = 'bv_hurtig_ip_' . md5(bimverdi_hurtig_ip());
    $antall = (int) get_transient($nokkel);
    if ($antall >= (int) bimverdi_hurtig_innstillinger()['maks_per_ip_time']) {
        return false;
    }
    set_transient($nokkel, $antall + 1, HOUR_IN_SECONDS);
    return true;
}

/**
 * Skjemaet er sendt inn (HR-02–HR-06).
 */
function bimverdi_hurtig_behandle_skjema() {
    if (empty($_POST['bv_hurtig_skjema'])) {
        wp_redirect(home_url('/chat/'));
        exit;
    }
    $kode = bimverdi_hurtig_kode(wp_unslash($_POST['k'] ?? ''));

    if (!wp_verify_nonce($_POST['_bv_nonce'] ?? '', 'bv_hurtig_skjema')) {
        bimverdi_hurtig_tilbake($kode, 'utlopt');
    }
    if (!bimverdi_hurtig_er_apen($kode)) {
        bimverdi_hurtig_tilbake($kode, '');
    }
    // Honeypot: late som alt gikk bra.
    if (!empty($_POST['nettsted'])) {
        bimverdi_hurtig_til_sendt();
    }

    $navn  = trim(preg_replace('/\s+/u', ' ', sanitize_text_field(wp_unslash($_POST['navn'] ?? ''))));
    $epost = strtolower(sanitize_email(wp_unslash($_POST['epost'] ?? '')));

    if ($navn === '' || mb_strlen($navn) > 100) {
        bimverdi_hurtig_tilbake($kode, 'navn');
    }
    if ($epost === '' || !is_email($epost)) {
        bimverdi_hurtig_tilbake($kode, 'epost');
    }
    if (empty($_POST['vilkar'])) {
        bimverdi_hurtig_tilbake($kode, 'vilkar');
    }

    // HR-03 og B-035: generelle domener og engangsdomener avvises.
    if (bimverdi_is_general_domain($epost) || bimverdi_is_disposable_domain($epost)) {
        bimverdi_hurtig_tilbake($kode, 'generelt');
    }
    $domene = substr(strrchr($epost, '@'), 1);
    if (!checkdnsrr($domene, 'MX')) {
        bimverdi_hurtig_tilbake($kode, 'domene');
    }

    if (!bimverdi_hurtig_turnstile_ok()) {
        bimverdi_hurtig_tilbake($kode, 'robot');
    }
    if (!bimverdi_hurtig_ip_ok()) {
        bimverdi_hurtig_tilbake($kode, 'grense');
    }

    // HR-05: adressen finnes fra før. Ingen ny bruker, og samme melding uansett.
    $finnes = get_user_by('email', $epost);
    if ($finnes) {
        bimverdi_hurtig_send_lenke_epost($finnes);
        bimverdi_hurtig_til_sendt();
    }

    $user_id = bimverdi_hurtig_opprett($navn, $epost, $kode);
    if (is_wp_error($user_id)) {
        error_log('[bimverdi-hurtig] Kunne ikke opprette bruker: ' . $user_id->get_error_message());
        bimverdi_hurtig_tilbake($kode, 'system');
    }

    bimverdi_hurtig_send_velkomst(get_userdata($user_id));
    bimverdi_hurtig_sett_cookie($user_id);

    wp_redirect(bimverdi_hurtig_byggchat_url());
    exit;
}

/**
 * Opprett hurtigbrukeren (HR-04).
 *
 * @return int|WP_Error
 */
function bimverdi_hurtig_opprett($navn, $epost, $kode) {
    // HR-04: ingen kobling til foretak før fullføring. Invitasjonsmodulen kobler nye
    // brukere til et foretak på user_register hvis økten har et invitasjonstoken.
    // Gjem tokenet mens brukeren opprettes, og legg det tilbake etterpå.
    $gjemt = null;
    if (session_status() === PHP_SESSION_NONE && isset($_COOKIE[session_name()])) {
        @session_start();
    }
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['bimverdi_invitation_token'])) {
        $gjemt = $_SESSION['bimverdi_invitation_token'];
        unset($_SESSION['bimverdi_invitation_token']);
    }

    $deler = explode(' ', $navn, 2);
    $user_id = wp_insert_user([
        'user_login'   => 'hurtig_' . strtolower(wp_generate_password(12, false)),
        'user_email'   => $epost,
        'user_pass'    => wp_generate_password(32, true, true),
        'display_name' => $navn,
        'first_name'   => $deler[0],
        'last_name'    => $deler[1] ?? '',
        'role'         => BV_HURTIG_ROLLE,
    ]);

    if ($gjemt !== null) {
        $_SESSION['bimverdi_invitation_token'] = $gjemt;
    }
    if (is_wp_error($user_id)) {
        return $user_id;
    }

    update_user_meta($user_id, 'bv_hurtig_status', 'ubekreftet');
    update_user_meta($user_id, 'bv_hurtig_opprettet', time());
    update_user_meta($user_id, 'bv_hurtig_kampanje', $kode);

    bimverdi_hurtig_tell($kode, 'registrert');
    return (int) $user_id;
}

/**
 * «Send meg en ny lenke» (fra utløpt lenke, fullføringen eller utløpt tilgang).
 * Samme svar uansett om adressen finnes (HR-05).
 */
function bimverdi_hurtig_behandle_ny_lenke() {
    if (!wp_verify_nonce($_POST['_bv_nonce'] ?? '', 'bv_hurtig_ny_lenke') || !empty($_POST['nettsted'])) {
        bimverdi_hurtig_til_sendt();
    }
    $hurtig = bimverdi_hurtig_bruker_fra_cookie();
    $epost  = strtolower(sanitize_email(wp_unslash($_POST['epost'] ?? '')));
    $bruker = $hurtig ?: ($epost !== '' ? get_user_by('email', $epost) : null);

    if ($bruker && bimverdi_hurtig_ip_ok()) {
        bimverdi_hurtig_send_lenke_epost($bruker);
    }
    bimverdi_hurtig_til_sendt();
}

function bimverdi_hurtig_html_ny_lenke($med_epost) {
    ob_start();
    ?>
    <form method="post" action="<?php echo esc_url(home_url('/chat/')); ?>">
        <?php wp_nonce_field('bv_hurtig_ny_lenke', '_bv_nonce'); ?>
        <input type="hidden" name="bv_hurtig_ny_lenke" value="1">
        <div class="hp" aria-hidden="true"><input type="text" name="nettsted" tabindex="-1" autocomplete="off"></div>
        <?php if ($med_epost) : ?>
            <label for="bv-ny-epost">E-post</label>
            <input id="bv-ny-epost" type="email" name="epost" required inputmode="email" autocomplete="email">
        <?php endif; ?>
        <button type="submit" class="knapp">Send meg en ny lenke</button>
    </form>
    <?php
    return ob_get_clean();
}

/* =========================================================================
 * Engangslenker fra e-post: /chat/lenke/
 * ====================================================================== */

function bimverdi_hurtig_side_lenke() {
    $user_id = (int) ($_REQUEST['u'] ?? 0);
    $token   = sanitize_text_field(wp_unslash($_REQUEST['t'] ?? ''));
    $bruker  = $user_id ? get_userdata($user_id) : false;

    $ugyldig = function () {
        bimverdi_hurtig_vis('Lenken virker ikke', '<h1>Lenken virker ikke</h1>'
            . '<p>Lenken er brukt eller har utløpt. Skriv inn e-posten din, så sender vi en ny.</p>'
            . bimverdi_hurtig_html_ny_lenke(true));
        exit;
    };

    if (!$bruker || !$token) {
        $ugyldig();
    }

    // GET: ikke bruk opp lenken (e-postskannere åpner den først). Send videre med POST.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        if (bimverdi_hurtig_sjekk_lenke($user_id, $token, false) === null) {
            $ugyldig();
        }
        $html = '<h1>Et øyeblikk …</h1>'
            . '<form id="bv-lenke" method="post" action="' . esc_url(home_url('/chat/lenke/')) . '">'
            . '<input type="hidden" name="u" value="' . (int) $user_id . '">'
            . '<input type="hidden" name="t" value="' . esc_attr($token) . '">'
            . '<button type="submit" class="knapp">Fortsett</button>'
            . '</form>'
            . '<script>document.getElementById("bv-lenke").submit();</script>';
        bimverdi_hurtig_vis('Et øyeblikk', $html);
        exit;
    }

    $formal = bimverdi_hurtig_sjekk_lenke($user_id, $token, true);
    if ($formal === null) {
        $ugyldig();
    }

    // Vanlig bruker fra HR-05: logg inn og gå til ByggChat.
    if ($formal === 'logginn') {
        if (bimverdi_hurtig_er_hurtigbruker($bruker)) {
            $ugyldig();
        }
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true, is_ssl());
        do_action('wp_login', $bruker->user_login, $bruker);
        wp_redirect(bimverdi_hurtig_byggchat_url());
        exit;
    }

    if (!bimverdi_hurtig_er_hurtigbruker($bruker)) {
        // Brukeren har fullført siden lenken ble sendt.
        wp_redirect(wp_login_url(bimverdi_hurtig_byggchat_url()));
        exit;
    }

    // Lenken beviser at brukeren eier adressen: e-posten er bekreftet.
    bimverdi_hurtig_bekreft($bruker);
    bimverdi_hurtig_sett_cookie($user_id);

    $mal = [
        'chat'    => bimverdi_hurtig_byggchat_url(),
        'fullfor' => home_url('/chat/fullfor/'),
        'slett'   => home_url('/chat/slett/'),
    ];
    wp_redirect($mal[$formal] ?? $mal['chat']);
    exit;
}

/**
 * Bekreft e-posten. En deaktivert hurtigbruker som bruker en ny lenke, får en ny periode.
 */
function bimverdi_hurtig_bekreft(WP_User $bruker) {
    $status = bimverdi_hurtig_status($bruker->ID);
    if ($status === 'ubekreftet') {
        update_user_meta($bruker->ID, 'bv_hurtig_status', 'bekreftet');
        update_user_meta($bruker->ID, 'bv_hurtig_bekreftet', time());
        bimverdi_hurtig_tell((string) get_user_meta($bruker->ID, 'bv_hurtig_kampanje', true), 'bekreftet');
    } elseif ($status === 'deaktivert') {
        update_user_meta($bruker->ID, 'bv_hurtig_status', 'bekreftet');
        update_user_meta($bruker->ID, 'bv_hurtig_opprettet', time());
        if (!get_user_meta($bruker->ID, 'bv_hurtig_bekreftet', true)) {
            update_user_meta($bruker->ID, 'bv_hurtig_bekreftet', time());
        }
        delete_user_meta($bruker->ID, 'bv_hurtig_deaktivert');
        delete_user_meta($bruker->ID, 'bv_hurtig_paminnelse_1');
        delete_user_meta($bruker->ID, 'bv_hurtig_paminnelse_2');
    }
}

/* =========================================================================
 * Fullføring: /chat/fullfor/ (HR-09)
 *
 * Samme felt og regler som den ordinære aktiveringen (/aktiver-konto/), men
 * navn og e-post er kjent. Etterpå får brukeren rollen medlem og havner på
 * Min side, der foretakskoblingen skjer som for alle andre nye brukere.
 * ====================================================================== */

function bimverdi_hurtig_side_fullfor() {
    if (is_user_logged_in()) {
        wp_redirect(home_url('/min-side/'));
        exit;
    }

    $bruker = bimverdi_hurtig_bruker_fra_cookie();
    if (!$bruker) {
        bimverdi_hurtig_vis('Fullfør registreringen', '<h1>Fullfør registreringen</h1>'
            . '<p>Åpne lenken i e-posten vi sendte deg da du startet ByggChat. Finner du den ikke, sender vi en ny.</p>'
            . bimverdi_hurtig_html_ny_lenke(true));
        return;
    }

    $status = bimverdi_hurtig_status($bruker->ID);
    if ($status !== 'bekreftet') {
        bimverdi_hurtig_vis('Bekreft e-posten', '<h1>Bekreft e-posten din først</h1>'
            . '<p>Vi har sendt en lenke til <strong>' . esc_html($bruker->user_email) . '</strong>. Klikk på den, så kan du fullføre registreringen.</p>'
            . bimverdi_hurtig_html_ny_lenke(false));
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        bimverdi_hurtig_behandle_fullforing($bruker);
        return;
    }

    bimverdi_hurtig_vis('Fullfør registreringen', bimverdi_hurtig_html_fullfor($bruker, sanitize_key($_GET['feil'] ?? '')));
}

function bimverdi_hurtig_html_fullfor(WP_User $bruker, $feil) {
    $tekster = [
        'navn'    => 'Skriv inn navnet ditt.',
        'passord' => 'Passordet må være minst 8 tegn.',
        'profil'  => 'Skriv inn både mobilnummer og stilling.',
        'vilkar'  => 'Du må godta betingelsene for å fullføre.',
        'utlopt'  => 'Skjemaet ble for gammelt. Prøv igjen.',
        'system'  => 'Noe gikk galt. Prøv igjen om litt.',
    ];
    $mobil    = (string) get_user_meta($bruker->ID, 'phone', true);
    $stilling = (string) get_user_meta($bruker->ID, 'job_title', true);

    ob_start();
    ?>
    <h1>Fullfør registreringen</h1>
    <p>Velg passord og fyll inn resten. Etterpå kobler du deg til foretaket ditt, og samtalene dine i ByggChat følger med.</p>
    <?php if (isset($tekster[$feil])) : ?>
        <p class="varsel" role="alert"><?php echo esc_html($tekster[$feil]); ?></p>
    <?php endif; ?>
    <form method="post" action="<?php echo esc_url(home_url('/chat/fullfor/')); ?>">
        <?php wp_nonce_field('bv_hurtig_fullfor_' . $bruker->ID, '_bv_nonce'); ?>

        <label>E-post</label>
        <p class="lest"><?php echo esc_html($bruker->user_email); ?></p>

        <label for="bv-f-navn">Navn</label>
        <input id="bv-f-navn" type="text" name="full_name" required autocomplete="name" value="<?php echo esc_attr($bruker->display_name); ?>">

        <label for="bv-f-mobil">Mobil</label>
        <input id="bv-f-mobil" type="tel" name="phone" required autocomplete="tel" inputmode="tel" value="<?php echo esc_attr($mobil); ?>">

        <label for="bv-f-stilling">Stilling</label>
        <input id="bv-f-stilling" type="text" name="job_title" required autocomplete="organization-title" value="<?php echo esc_attr($stilling); ?>">

        <label for="bv-f-passord">Passord</label>
        <input id="bv-f-passord" type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Minst 8 tegn">

        <label class="avkrysning">
            <input type="checkbox" name="aksept_betingelser" value="1" required>
            <span>Jeg aksepterer <a href="<?php echo esc_url(defined('BV_TERMS_URL') ? BV_TERMS_URL : home_url('/betingelser/')); ?>" target="_blank" rel="noopener">betingelsene for registrering som bruker i BIM Verdi</a>.</span>
        </label>

        <label class="avkrysning">
            <input type="checkbox" name="subscribe_newsletter" value="1">
            <span>Ja takk, jeg vil ha nyhetsbrevet fra BIM Verdi.</span>
        </label>

        <button type="submit" class="knapp">Fullfør registreringen</button>
    </form>
    <?php
    return ob_get_clean();
}

function bimverdi_hurtig_behandle_fullforing(WP_User $bruker) {
    $tilbake = function ($feil) {
        wp_redirect(add_query_arg('feil', $feil, home_url('/chat/fullfor/')));
        exit;
    };

    if (!wp_verify_nonce($_POST['_bv_nonce'] ?? '', 'bv_hurtig_fullfor_' . $bruker->ID)) {
        $tilbake('utlopt');
    }

    $navn     = trim(sanitize_text_field(wp_unslash($_POST['full_name'] ?? '')));
    $passord  = (string) wp_unslash($_POST['password'] ?? '');
    $mobil    = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
    $stilling = sanitize_text_field(wp_unslash($_POST['job_title'] ?? ''));

    // Lagre det som er fylt inn, så det står der hvis noe mangler.
    if ($mobil !== '') {
        update_user_meta($bruker->ID, 'phone', $mobil);
    }
    if ($stilling !== '') {
        update_user_meta($bruker->ID, 'job_title', $stilling);
    }

    if ($navn === '') {
        $tilbake('navn');
    }
    if ($mobil === '' || $stilling === '') {
        $tilbake('profil');
    }
    if (strlen($passord) < 8) {
        $tilbake('passord');
    }
    if (!bimverdi_validate_terms_acceptance($_POST)) {
        $tilbake('vilkar');
    }

    $deler = explode(' ', $navn, 2);
    $svar  = wp_update_user([
        'ID'           => $bruker->ID,
        'user_pass'    => $passord,
        'display_name' => $navn,
        'first_name'   => $deler[0],
        'last_name'    => $deler[1] ?? '',
    ]);
    if (is_wp_error($svar)) {
        error_log('[bimverdi-hurtig] Fullføring feilet for bruker ' . $bruker->ID . ': ' . $svar->get_error_message());
        $tilbake('system');
    }

    // Samme utgangspunkt som en ny bruker fra den ordinære registreringen.
    if (function_exists('update_field')) {
        update_field('phone', $mobil, 'user_' . $bruker->ID);
        update_field('job_title', $stilling, 'user_' . $bruker->ID);
    }
    $bruker = new WP_User($bruker->ID);
    $bruker->set_role('medlem');
    update_user_meta($bruker->ID, 'bimverdi_account_type', 'profil');
    update_user_meta($bruker->ID, 'bimverdi_company_id', '');
    update_user_meta($bruker->ID, 'bimverdi_registered_at', current_time('mysql'));
    update_user_meta($bruker->ID, 'bimverdi_newsletter_subscribed', !empty($_POST['subscribe_newsletter']) ? '1' : '0');

    // Hurtigmeta beholdes for statistikk (HR-09). Lenker og cookie skal ikke virke lenger.
    update_user_meta($bruker->ID, 'bv_hurtig_status', 'fullfort');
    update_user_meta($bruker->ID, 'bv_hurtig_fullfort', time());
    delete_user_meta($bruker->ID, 'bv_hurtig_lenker');
    delete_user_meta($bruker->ID, 'bv_hurtig_okt');
    bimverdi_hurtig_fjern_cookie();
    bimverdi_hurtig_tell((string) get_user_meta($bruker->ID, 'bv_hurtig_kampanje', true), 'fullfort');

    wp_set_current_user($bruker->ID);
    wp_set_auth_cookie($bruker->ID, true, is_ssl());
    do_action('wp_login', $bruker->user_login, $bruker);

    // Admin-kopi som ved ordinær registrering (SuperOffice-dokumentasjon).
    if (function_exists('bimverdi_send_admin_notification_email')) {
        $kode = (string) get_user_meta($bruker->ID, 'bv_hurtig_kampanje', true);
        $body = sprintf(
            '<p>En hurtigbruker fra ByggChat har fullført registreringen og akseptert betingelsene:</p>
            <table style="border-collapse:collapse;font-size:14px;">
                <tr><td style="padding:4px 12px 4px 0;color:#666;">Navn</td><td><strong>%s</strong></td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#666;">E-post</td><td>%s</td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#666;">Kampanjekode</td><td>%s</td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#666;">Tidspunkt</td><td>%s</td></tr>
            </table>
            <p style="margin-top:24px;"><a href="%s">Åpne brukeren i wp-admin</a></p>%s',
            esc_html($navn),
            esc_html($bruker->user_email),
            esc_html($kode !== '' ? $kode : '(ingen)'),
            esc_html(date_i18n('j. F Y \k\l. H:i')),
            esc_url(admin_url('user-edit.php?user_id=' . $bruker->ID)),
            function_exists('bimverdi_render_terms_footer_html') ? bimverdi_render_terms_footer_html() : ''
        );
        bimverdi_send_admin_notification_email('Hurtigbruker fullførte registreringen: ' . $bruker->user_email, $body);
    }

    wp_redirect(add_query_arg('welcome', '1', home_url('/min-side/')));
    exit;
}

/* =========================================================================
 * Slett kontoen selv: /chat/slett/ (HR-13)
 * ====================================================================== */

function bimverdi_hurtig_side_slett() {
    $bruker = bimverdi_hurtig_bruker_fra_cookie();
    if (!$bruker) {
        bimverdi_hurtig_vis('Slett kontoen', '<h1>Slett kontoen</h1>'
            . '<p>Bruk lenken «Slett den her» nederst i e-posten fra oss.</p>');
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && wp_verify_nonce($_POST['_bv_nonce'] ?? '', 'bv_hurtig_slett_' . $bruker->ID)) {
        bimverdi_hurtig_slett_bruker($bruker->ID);
        bimverdi_hurtig_fjern_cookie();
        bimverdi_hurtig_vis('Kontoen er slettet', '<h1>Kontoen er slettet</h1>'
            . '<p>Navnet og e-postadressen din er slettet fra BIM Verdi.</p>');
        return;
    }

    bimverdi_hurtig_vis('Slett kontoen', '<h1>Slett kontoen?</h1>'
        . '<p>Navn og e-post for <strong>' . esc_html($bruker->user_email) . '</strong> slettes fra BIM Verdi, og du mister tilgangen til ByggChat.</p>'
        . '<form method="post" action="' . esc_url(home_url('/chat/slett/')) . '">'
        . wp_nonce_field('bv_hurtig_slett_' . $bruker->ID, '_bv_nonce', true, false)
        . '<button type="submit" class="knapp knapp-fare">Slett kontoen</button></form>'
        . '<p class="liten"><a href="' . esc_url(bimverdi_hurtig_byggchat_url()) . '">Nei, tilbake til ByggChat</a></p>');
}

/**
 * Slett en hurtigbruker. Gir hooken bimverdi_hurtig_slettet, så ByggChat kan få
 * beskjed når det er avtalt hvordan (krav 30, Å-6).
 */
function bimverdi_hurtig_slett_bruker($user_id) {
    $bruker = get_userdata($user_id);
    if (!bimverdi_hurtig_er_hurtigbruker($bruker)) {
        return false;
    }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    $epost = $bruker->user_email;
    $ok = wp_delete_user($user_id);
    if ($ok) {
        do_action('bimverdi_hurtig_slettet', $user_id, $epost);
    }
    return $ok;
}

/* =========================================================================
 * ByggChat-overgangen (/?byggchat_go=1) — se bimverdi-byggchat-sso.php
 * ====================================================================== */

// Ikke innlogget, men gyldig hurtigcookie: lag kortet for hurtigbrukeren.
add_filter('bimverdi_byggchat_sso_bruker', function ($bruker) {
    if ($bruker) {
        return $bruker;
    }
    $hurtig = bimverdi_hurtig_bruker_fra_cookie();
    if ($hurtig && bimverdi_hurtig_status($hurtig->ID) !== 'deaktivert') {
        return $hurtig;
    }
    return null;
});

// Utløpt hurtigbruker: forklar det i stedet for å sende henne til innloggingen.
add_action('template_redirect', function () {
    if (!isset($_GET['byggchat_go']) || is_user_logged_in()) {
        return;
    }
    $hurtig = bimverdi_hurtig_bruker_fra_cookie();
    if ($hurtig && bimverdi_hurtig_status($hurtig->ID) === 'deaktivert') {
        nocache_headers();
        bimverdi_hurtig_vis('Tilgangen er utløpt', '<h1>Tilgangen er utløpt</h1>'
            . '<p>Prøvetilgangen din til ByggChat er utløpt. Vi sender deg en ny lenke, så får du en ny periode og kan fullføre registreringen.</p>'
            . bimverdi_hurtig_html_ny_lenke(false));
        exit;
    }
}, 5);

// Innholdet i kortet for hurtigbrukere (krav 30, HR-07).
add_filter('bimverdi_byggchat_claims', function ($claims, $bruker) {
    if (!bimverdi_hurtig_er_hurtigbruker($bruker)) {
        return $claims;
    }
    $inn    = bimverdi_hurtig_innstillinger();
    $status = bimverdi_hurtig_status($bruker->ID);

    $claims['foretak']       = null;
    $claims['nivaa']         = 'Hurtigbruker';
    $claims['hurtig_status'] = $status === 'bekreftet' ? 'bekreftet' : 'ubekreftet';
    $claims['kvote']         = (int) ($status === 'bekreftet' ? $inn['kvote_bekreftet'] : $inn['kvote_ubekreftet']);
    return $claims;
}, 10, 2);

/* =========================================================================
 * Daglig opprydding (HR-11)
 * ====================================================================== */

add_action('init', function () {
    if (!wp_next_scheduled(BV_HURTIG_CRON)) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', BV_HURTIG_CRON);
    }
});

add_action(BV_HURTIG_CRON, 'bimverdi_hurtig_opprydding');

/**
 * Påminnelser, deaktivering og sletting. Kan kjøres for hånd:
 *   wp eval 'print_r(bimverdi_hurtig_opprydding());'
 *
 * @return array Oppsummering.
 */
function bimverdi_hurtig_opprydding() {
    $inn     = bimverdi_hurtig_innstillinger();
    $utlop   = max(4, (int) $inn['utlop_dager']);
    $slett   = max(1, (int) $inn['slett_etter_dager']);
    $na      = time();
    $logg    = ['paminnelse_1' => 0, 'paminnelse_2' => 0, 'deaktivert' => 0, 'slettet' => 0];

    $brukere = get_users(['role' => BV_HURTIG_ROLLE, 'number' => -1]);
    foreach ($brukere as $bruker) {
        $status    = bimverdi_hurtig_status($bruker->ID);
        $opprettet = (int) get_user_meta($bruker->ID, 'bv_hurtig_opprettet', true) ?: strtotime($bruker->user_registered);
        $dager     = (int) floor(($na - $opprettet) / DAY_IN_SECONDS);

        if ($status === 'deaktivert') {
            $deaktivert = (int) get_user_meta($bruker->ID, 'bv_hurtig_deaktivert', true);
            if ($deaktivert && ($na - $deaktivert) >= $slett * DAY_IN_SECONDS) {
                if (bimverdi_hurtig_slett_bruker($bruker->ID)) {
                    $logg['slettet']++;
                }
            }
            continue;
        }

        if ($dager >= $utlop) {
            update_user_meta($bruker->ID, 'bv_hurtig_status', 'deaktivert');
            update_user_meta($bruker->ID, 'bv_hurtig_deaktivert', $na);
            delete_user_meta($bruker->ID, 'bv_hurtig_lenker');
            bimverdi_hurtig_tell((string) get_user_meta($bruker->ID, 'bv_hurtig_kampanje', true), 'utlopt');
            $logg['deaktivert']++;
            continue;
        }

        if ($dager >= $utlop - 3 && !get_user_meta($bruker->ID, 'bv_hurtig_paminnelse_2', true)) {
            update_user_meta($bruker->ID, 'bv_hurtig_paminnelse_2', $na);
            update_user_meta($bruker->ID, 'bv_hurtig_paminnelse_1', get_user_meta($bruker->ID, 'bv_hurtig_paminnelse_1', true) ?: $na);
            bimverdi_hurtig_send_paminnelse($bruker, max(1, $utlop - $dager));
            $logg['paminnelse_2']++;
        } elseif ($dager >= 7 && !get_user_meta($bruker->ID, 'bv_hurtig_paminnelse_1', true)) {
            update_user_meta($bruker->ID, 'bv_hurtig_paminnelse_1', $na);
            bimverdi_hurtig_send_paminnelse($bruker, $utlop - $dager);
            $logg['paminnelse_1']++;
        }
    }

    $logg['tidspunkt'] = wp_date('j.n.Y H:i');
    update_option('bv_hurtig_siste_opprydding', $logg, false);
    error_log('[bimverdi-hurtig] Opprydding: ' . wp_json_encode($logg));
    return $logg;
}

/* =========================================================================
 * wp-admin: Brukere → Hurtigregistrering (HR-10, HR-12, HR-15)
 * ====================================================================== */

add_action('admin_menu', function () {
    add_users_page('Hurtigregistrering', 'Hurtigregistrering', 'manage_options', 'bv-hurtigregistrering', 'bimverdi_hurtig_admin_side');
});

add_action('admin_post_bv_hurtig_lagre', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Ingen tilgang.');
    }
    check_admin_referer('bv_hurtig_lagre');

    $koder = function ($felt) {
        $linjer = preg_split('/[\s,]+/', strtolower((string) wp_unslash($_POST[$felt] ?? '')));
        return array_values(array_unique(array_filter(array_map('bimverdi_hurtig_kode', $linjer))));
    };
    $tall = function ($felt, $min, $max) {
        return max($min, min($max, (int) ($_POST[$felt] ?? 0)));
    };
    $modus = sanitize_key($_POST['modus'] ?? 'av');

    update_option(BV_HURTIG_INNSTILLING, [
        'modus'              => in_array($modus, ['av', 'test', 'pa'], true) ? $modus : 'av',
        'testkoder'          => $koder('testkoder'),
        'sperrede_koder'     => $koder('sperrede_koder'),
        'kvote_ubekreftet'   => $tall('kvote_ubekreftet', 0, 1000),
        'kvote_bekreftet'    => $tall('kvote_bekreftet', 0, 1000),
        'utlop_dager'        => $tall('utlop_dager', 4, 365),
        'slett_etter_dager'  => $tall('slett_etter_dager', 1, 365),
        'maks_per_ip_time'   => $tall('maks_per_ip_time', 1, 1000),
        'maks_epost_dogn'    => $tall('maks_epost_dogn', 1, 20),
        'turnstile_nokkel'   => sanitize_text_field(wp_unslash($_POST['turnstile_nokkel'] ?? '')),
        'turnstile_hemmelig' => sanitize_text_field(wp_unslash($_POST['turnstile_hemmelig'] ?? '')),
        'ga4_api_hemmelig'   => sanitize_text_field(wp_unslash($_POST['ga4_api_hemmelig'] ?? '')),
    ], false);

    wp_safe_redirect(add_query_arg(['page' => 'bv-hurtigregistrering', 'lagret' => 1], admin_url('users.php')));
    exit;
});

function bimverdi_hurtig_admin_side() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $inn  = bimverdi_hurtig_innstillinger();
    $stat = get_option(BV_HURTIG_STATISTIKK, []);
    $stat = is_array($stat) ? $stat : [];
    ksort($stat);
    $siste = get_option('bv_hurtig_siste_opprydding');

    // Hurtigbrukere nå, per status.
    $na = ['ubekreftet' => 0, 'bekreftet' => 0, 'deaktivert' => 0];
    foreach (get_users(['role' => BV_HURTIG_ROLLE, 'number' => -1, 'fields' => ['ID']]) as $u) {
        $s = bimverdi_hurtig_status((int) $u->ID);
        if (isset($na[$s])) {
            $na[$s]++;
        }
    }
    $tekstfelt = function ($navn, $verdi, $type = 'text') {
        printf('<input type="%s" name="%s" value="%s" class="regular-text">', esc_attr($type), esc_attr($navn), esc_attr($verdi));
    };
    $tallfelt = function ($navn, $verdi) {
        printf('<input type="number" name="%s" value="%d" class="small-text">', esc_attr($navn), (int) $verdi);
    };
    ?>
    <div class="wrap">
        <h1>Hurtigregistrering for ByggChat</h1>
        <p>Krav 30. Skjemaet ligger på <a href="<?php echo esc_url(home_url('/chat/')); ?>" target="_blank"><?php echo esc_html(home_url('/chat/')); ?></a>. QR-koden peker dit med kampanjekode, for eksempel <code><?php echo esc_html(home_url('/chat/?k=rollup1')); ?></code>.</p>
        <?php if (!empty($_GET['lagret'])) : ?>
            <div class="notice notice-success"><p>Lagret.</p></div>
        <?php endif; ?>

        <h2>Statistikk per kampanjekode</h2>
        <table class="widefat striped" style="max-width:720px;">
            <thead><tr><th>Kampanjekode</th><th>Registrert</th><th>Bekreftet</th><th>Fullført</th><th>Utløpt</th></tr></thead>
            <tbody>
            <?php if (!$stat) : ?>
                <tr><td colspan="5">Ingen registreringer ennå.</td></tr>
            <?php endif; ?>
            <?php foreach ($stat as $kode => $rad) : ?>
                <tr>
                    <td><?php echo $kode !== '' ? '<code>' . esc_html($kode) . '</code>' : '(uten kode)'; ?></td>
                    <td><?php echo (int) ($rad['registrert'] ?? 0); ?></td>
                    <td><?php echo (int) ($rad['bekreftet'] ?? 0); ?></td>
                    <td><?php echo (int) ($rad['fullfort'] ?? 0); ?></td>
                    <td><?php echo (int) ($rad['utlopt'] ?? 0); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p>
            Hurtigbrukere nå: <?php echo (int) $na['ubekreftet']; ?> ubekreftet, <?php echo (int) $na['bekreftet']; ?> bekreftet, <?php echo (int) $na['deaktivert']; ?> deaktivert.
            <a href="<?php echo esc_url(admin_url('users.php?role=' . BV_HURTIG_ROLLE)); ?>">Vis dem i brukerlisten</a>.
        </p>
        <?php if (is_array($siste)) : ?>
            <p class="description">Siste opprydding <?php echo esc_html($siste['tidspunkt'] ?? ''); ?>:
                <?php echo (int) $siste['paminnelse_1']; ?> første påminnelser, <?php echo (int) $siste['paminnelse_2']; ?> siste påminnelser,
                <?php echo (int) $siste['deaktivert']; ?> deaktivert, <?php echo (int) $siste['slettet']; ?> slettet.</p>
        <?php endif; ?>

        <h2>Innstillinger</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="bv_hurtig_lagre">
            <?php wp_nonce_field('bv_hurtig_lagre'); ?>
            <table class="form-table" role="presentation">
                <tr><th scope="row">Skjemaet</th><td>
                    <?php foreach (['av' => 'Av — stengt for alle', 'test' => 'Test — bare kampanjekodene under', 'pa' => 'På — åpent for alle, unntatt sperrede koder'] as $verdi => $tekst) : ?>
                        <label style="display:block;margin-bottom:4px;"><input type="radio" name="modus" value="<?php echo esc_attr($verdi); ?>" <?php checked($inn['modus'], $verdi); ?>> <?php echo esc_html($tekst); ?></label>
                    <?php endforeach; ?>
                </td></tr>
                <tr><th scope="row">Testkoder</th><td>
                    <?php $tekstfelt('testkoder', implode(', ', (array) $inn['testkoder'])); ?>
                    <p class="description">Gjelder når skjemaet står på Test. Kommaseparert, bare a–z, 0–9 og bindestrek.</p>
                </td></tr>
                <tr><th scope="row">Sperrede koder</th><td>
                    <?php $tekstfelt('sperrede_koder', implode(', ', (array) $inn['sperrede_koder'])); ?>
                    <p class="description">Gjelder når skjemaet står på På. For eksempel en rollup som er kommet på avveie.</p>
                </td></tr>
                <tr><th scope="row">Kvote i ByggChat</th><td>
                    <?php $tallfelt('kvote_ubekreftet', $inn['kvote_ubekreftet']); ?> spørsmål før e-posten er bekreftet,
                    <?php $tallfelt('kvote_bekreftet', $inn['kvote_bekreftet']); ?> etter.
                    <p class="description">Sendes til ByggChat i adgangskortet. ByggChat teller.</p>
                </td></tr>
                <tr><th scope="row">Utløp</th><td>
                    Deaktiver etter <?php $tallfelt('utlop_dager', $inn['utlop_dager']); ?> dager uten fullføring.
                    Slett <?php $tallfelt('slett_etter_dager', $inn['slett_etter_dager']); ?> dager etter det.
                </td></tr>
                <tr><th scope="row">Ratebegrensning</th><td>
                    Maks <?php $tallfelt('maks_per_ip_time', $inn['maks_per_ip_time']); ?> registreringer per IP per time.
                    Maks <?php $tallfelt('maks_epost_dogn', $inn['maks_epost_dogn']); ?> e-poster per adresse per døgn.
                    <p class="description">På en stand deler mange samme Wi-Fi og dermed samme IP. Øk grensen før arrangementet.</p>
                </td></tr>
                <tr><th scope="row">Cloudflare Turnstile</th><td>
                    <?php $tekstfelt('turnstile_nokkel', $inn['turnstile_nokkel']); ?> Site key<br>
                    <?php $tekstfelt('turnstile_hemmelig', $inn['turnstile_hemmelig'], 'password'); ?> Secret key
                    <p class="description">Usynlig robotsjekk. Tomme felt: ingen robotsjekk, bare honeypot og ratebegrensning.</p>
                </td></tr>
                <tr><th scope="row">GA4</th><td>
                    <?php $tekstfelt('ga4_api_hemmelig', $inn['ga4_api_hemmelig'], 'password'); ?> Measurement Protocol API secret
                    <p class="description">Lages i GA4 under Admin → Datastrømmer → Measurement Protocol. Tomt felt: ingen hendelser til GA4.</p>
                </td></tr>
            </table>
            <?php submit_button('Lagre'); ?>
        </form>
    </div>
    <?php
}

/* =========================================================================
 * Sideoppsett — eget, lett oppsett uten meny (HR-01)
 * ====================================================================== */

function bimverdi_hurtig_vis($tittel, $innhold) {
    status_header(200);
    $logo     = get_template_directory_uri() . '/assets/img/bimverdi-logo.png';
    $byggchat = get_template_directory_uri() . '/assets/img/byggchat-alfa-logo.png';
    ?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?php echo esc_html($tittel); ?> – BIM Verdi</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; background: #F5F3EE; color: #1A1A1A; font: 16px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
        main { max-width: 440px; margin: 0 auto; padding: 24px 16px 48px; }
        .logoer { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .logoer img { height: 32px; width: auto; }
        .kort { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        h1 { font-size: 24px; line-height: 1.25; margin: 0 0 12px; }
        p { margin: 0 0 16px; }
        a { color: #1A1A1A; }
        label { display: block; font-weight: 600; font-size: 15px; margin: 16px 0 6px; }
        input[type=text], input[type=email], input[type=tel], input[type=password] {
            width: 100%; font-size: 17px; padding: 12px 14px; border: 1px solid #D6D1C6; border-radius: 8px; background: #fff; color: inherit;
        }
        input:focus { outline: 2px solid #1A1A1A; outline-offset: 1px; }
        .avkrysning { display: flex; gap: 10px; align-items: flex-start; font-weight: 400; font-size: 15px; }
        .avkrysning input { width: 20px; height: 20px; margin: 2px 0 0; flex-shrink: 0; accent-color: #FF8B5E; }
        .knapp { display: block; width: 100%; margin-top: 20px; padding: 14px 20px; font-size: 17px; font-weight: 600; text-align: center;
            color: #fff; background: #1A1A1A; border: 0; border-radius: 8px; text-decoration: none; cursor: pointer; }
        .knapp-fare { background: #B42318; }
        .liten { font-size: 14px; color: #6B6B6B; margin-top: 16px; text-align: center; }
        .lest { margin: 0; color: #6B6B6B; }
        .feltfeil { display: block; color: #B42318; font-size: 14px; margin-top: 4px; }
        .varsel { background: #FEF3F2; color: #B42318; border-radius: 8px; padding: 12px 14px; }
        .varsel-gronn { background: #F0FDF4; color: #1A1A1A; }
        .varsel-gronn p { font-size: 14px; }
        .varsel-tittel { color: #166534; font-weight: 700; font-size: 17px !important; }
        .hp { position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden; }
    </style>
</head>
<body>
<main>
    <div class="logoer">
        <a href="<?php echo esc_url(home_url('/')); ?>"><img src="<?php echo esc_url($logo); ?>" alt="BIM Verdi"></a>
        <img src="<?php echo esc_url($byggchat); ?>" alt="ByggChat">
    </div>
    <div class="kort">
        <?php echo $innhold; ?>
    </div>
</main>
</body>
</html>
    <?php
}
