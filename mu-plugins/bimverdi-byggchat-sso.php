<?php
/**
 * BIM Verdi - Innlogging til ByggChat (SSO)
 *
 * Medlemmet trykker «Åpne ByggChat» på bimverdi.no. Lenken går til
 * https://bimverdi.no/?byggchat_go=1. Her lager WordPress et kortvarig,
 * signert adgangskort (JWT) som sier hvem medlemmet er, og sender
 * medlemmet videre til ByggChat med kortet i adressen. ByggChat kontrollerer
 * kortet, oppretter brukeren første gang og logger den inn.
 *
 * Trello #361 pkt 3 (Bårds skisse). Vi bruker RS256 i stedet for HS256:
 * WordPress signerer med en privat nøkkel som aldri forlater serveren, og
 * ByggChat trenger bare den offentlige nøkkelen. Da finnes ingen felles
 * hemmelighet som må deles med Alexander.
 *
 * Kortet lages i det medlemmet klikker, ikke når siden vises. Servebolt kan
 * mellomlagre sider, og et kort som lå i siden kunne bli gammelt eller vist
 * til feil person.
 *
 * OPPSETT (wp-config.php)
 *   define('BYGGCHAT_PRIVATE_KEY_FILE', '/sti/utenfor/webroten/byggchat-private.pem');
 *   define('BYGGCHAT_SSO_URL', 'https://bim-mvp.onrender.com/sso');   // valgfri
 *
 * Offentlig nøkkel for ByggChat (JWKS):
 *   https://bimverdi.no/wp-json/bimverdi/v1/byggchat/jwks
 *
 * Kortets innhold: iss, aud, sub (bruker-ID), email, name, given_name,
 * family_name, foretak {id, navn, orgnr} eller null, nivaa, iat, exp
 * (60 sekunder), jti (unik ID, så kortet bare kan brukes én gang).
 *
 * Beskrivelsen for ByggChat-siden: docs/plans/2026-10-27-byggchat-sso-for-alexander.md
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Hvor lenge kortet er gyldig, i sekunder. */
const BV_BYGGCHAT_TOKEN_LEVETID = 60;

/** Adressen på ByggChat-siden som tar imot kortet. */
function bimverdi_byggchat_sso_url() {
    return defined('BYGGCHAT_SSO_URL') && BYGGCHAT_SSO_URL ? BYGGCHAT_SSO_URL : 'https://bim-mvp.onrender.com/sso';
}

/** Utsteder (iss): prod-domenet, ikke localhost, så ByggChat ser én fast verdi. */
function bimverdi_byggchat_utsteder() {
    return 'https://bimverdi.no';
}

/** Den private nøkkelen, eller null hvis den mangler. */
function bimverdi_byggchat_privat_nokkel() {
    if (!defined('BYGGCHAT_PRIVATE_KEY_FILE') || !is_readable(BYGGCHAT_PRIVATE_KEY_FILE)) {
        return null;
    }
    $nokkel = openssl_pkey_get_private((string) file_get_contents(BYGGCHAT_PRIVATE_KEY_FILE));
    return $nokkel ?: null;
}

/** base64url uten utfylling (JWT-formatet). */
function bimverdi_byggchat_b64($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Nøkkel-ID (kid): de første 16 tegnene av SHA-256 av den offentlige
 * nøkkelen. Bytter vi nøkkel, endres kid, og ByggChat ser hvilken som gjelder.
 */
function bimverdi_byggchat_kid($offentlig_pem) {
    return substr(hash('sha256', $offentlig_pem), 0, 16);
}

/**
 * Innholdet i kortet for én bruker.
 *
 * @return array
 */
function bimverdi_byggchat_claims(WP_User $u) {
    $foretak = null;
    $nivaa   = 'Gratisbruker';
    $data    = function_exists('bimverdi_get_user_company') ? bimverdi_get_user_company($u->ID) : false;
    if ($data) {
        $foretak = [
            'id'    => (int) $data['id'],
            'navn'  => wp_specialchars_decode((string) $data['name'], ENT_QUOTES),
            'orgnr' => (string) get_post_meta((int) $data['id'], 'organisasjonsnummer', true),
        ];
        $rolle = (string) get_post_meta((int) $data['id'], 'bv_rolle', true);
        if (in_array($rolle, ['Deltaker', 'Prosjektdeltaker', 'Partner'], true)) {
            $nivaa = $rolle;
        }
    }

    $na = time();
    return [
        'iss'         => bimverdi_byggchat_utsteder(),
        'aud'         => 'byggchat',
        'sub'         => (string) $u->ID,
        'email'       => $u->user_email,
        'name'        => $u->display_name,
        'given_name'  => (string) get_user_meta($u->ID, 'first_name', true),
        'family_name' => (string) get_user_meta($u->ID, 'last_name', true),
        'foretak'     => $foretak,
        'nivaa'       => $nivaa,
        'iat'         => $na,
        'exp'         => $na + BV_BYGGCHAT_TOKEN_LEVETID,
        'jti'         => wp_generate_uuid4(),
    ];
}

/**
 * Lag et signert kort (RS256) for en bruker.
 *
 * @return string|WP_Error
 */
function bimverdi_byggchat_token(WP_User $u) {
    $nokkel = bimverdi_byggchat_privat_nokkel();
    if (!$nokkel) {
        return new WP_Error('mangler_nokkel', 'ByggChat-nøkkelen mangler på serveren.');
    }
    $offentlig = openssl_pkey_get_details($nokkel)['key'];

    $header  = bimverdi_byggchat_b64(wp_json_encode(['alg' => 'RS256', 'typ' => 'JWT', 'kid' => bimverdi_byggchat_kid($offentlig)]));
    $payload = bimverdi_byggchat_b64(wp_json_encode(bimverdi_byggchat_claims($u), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $signatur = '';
    if (!openssl_sign($header . '.' . $payload, $signatur, $nokkel, OPENSSL_ALGO_SHA256)) {
        return new WP_Error('signering_feilet', 'Kunne ikke signere adgangskortet.');
    }
    return $header . '.' . $payload . '.' . bimverdi_byggchat_b64($signatur);
}

/* -------------------------------------------------------------------------
 * Klikket: /?byggchat_go=1
 * ---------------------------------------------------------------------- */

add_action('template_redirect', function () {
    if (!isset($_GET['byggchat_go'])) {
        return;
    }
    nocache_headers();

    if (!is_user_logged_in()) {
        wp_safe_redirect(wp_login_url(home_url('/?byggchat_go=1')));
        exit;
    }

    $token = bimverdi_byggchat_token(wp_get_current_user());
    if (is_wp_error($token)) {
        error_log('[bimverdi-byggchat-sso] ' . $token->get_error_message());
        wp_die(
            'Vi får ikke åpnet ByggChat akkurat nå. Prøv igjen senere, eller kontakt post@bimverdi.no.',
            'ByggChat er ikke tilgjengelig',
            ['response' => 503, 'back_link' => true]
        );
    }

    // Ekstern adresse: wp_redirect, ikke wp_safe_redirect (som bare tillater eget domene).
    wp_redirect(add_query_arg('token', rawurlencode($token), bimverdi_byggchat_sso_url()));
    exit;
});

/* -------------------------------------------------------------------------
 * Offentlig nøkkel (JWKS) for ByggChat
 * ---------------------------------------------------------------------- */

add_action('rest_api_init', function () {
    register_rest_route('bimverdi/v1', '/byggchat/jwks', [
        'methods'             => 'GET',
        'permission_callback' => '__return_true', // Offentlig nøkkel, ikke hemmelig.
        'callback'            => function () {
            $nokkel = bimverdi_byggchat_privat_nokkel();
            if (!$nokkel) {
                return new WP_REST_Response(['keys' => []], 503);
            }
            $detaljer = openssl_pkey_get_details($nokkel);
            return new WP_REST_Response(['keys' => [[
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => 'RS256',
                'kid' => bimverdi_byggchat_kid($detaljer['key']),
                'n'   => bimverdi_byggchat_b64($detaljer['rsa']['n']),
                'e'   => bimverdi_byggchat_b64($detaljer['rsa']['e']),
            ]]], 200);
        },
    ]);
});
