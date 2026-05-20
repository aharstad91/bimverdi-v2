<?php
/**
 * Plugin Name: BIM Verdi - Pending Oppgave (Krav 22)
 * Description: "Tilbake til oppgaven" — husk hvilken registrerings-oppgave en bruker prøvde å gjøre før blokk, og resume etter foretaks-kobling. 30-min sesjon.
 * Version: 1.0.0
 *
 * Brukes av arrangement-, nyhetsbrev- og kunnskapskilde-handlere når en innlogget
 * bruker uten foretak forsøker registrering. Lagrer oppgaven i $_SESSION og lar
 * brukeren resume etter at foretaks-kobling er fullført.
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

const BIMVERDI_PENDING_OPPGAVE_TTL = 30 * MINUTE_IN_SECONDS;
const BIMVERDI_PENDING_OPPGAVE_KEY = 'bv_pending_oppgave';

/**
 * Start sesjon hvis nødvendig. Trygt på WP: vi cacher ikke innloggede sider,
 * og admin-sider trenger ikke denne mekanikken.
 */
function bimverdi_maybe_start_session() {
    if (is_admin() || (defined('DOING_CRON') && DOING_CRON) || (defined('REST_REQUEST') && REST_REQUEST)) {
        return false;
    }
    if (!is_user_logged_in()) {
        // Sesjon kan også settes opp for gjester som skal logge inn etterpå,
        // men de fleste retry-flytene er for innloggede. Holde det enkelt: kun innloggede.
        return false;
    }
    if (headers_sent()) {
        return false;
    }
    if (session_status() === PHP_SESSION_NONE) {
        // Begrens sesjonens TTL på server-side i tillegg til vår manuelle timestamp-check.
        @ini_set('session.gc_maxlifetime', BIMVERDI_PENDING_OPPGAVE_TTL);
        @session_start([
            'cookie_lifetime' => BIMVERDI_PENDING_OPPGAVE_TTL,
            'cookie_httponly' => true,
            'cookie_secure'   => is_ssl(),
            'cookie_samesite' => 'Lax',
        ]);
    }
    return session_status() === PHP_SESSION_ACTIVE;
}

/**
 * Lagre en pending oppgave i sesjonen.
 *
 * @param array $oppgave  Forventet keys:
 *   - 'url'   (string) Mål-URL å redirecte tilbake til etter foretaks-kobling.
 *   - 'label' (string) Menneskelig label, brukes i block-vegg ("arrangement-påmelding", "nyhetsbrev-påmelding", ...)
 *   - 'context' (array, optional) Tilleggsdata (f.eks. arrangement-ID), hvis handler vil bruke det ved resume.
 */
function bimverdi_remember_pending_oppgave(array $oppgave) {
    if (!bimverdi_maybe_start_session()) {
        return;
    }
    $_SESSION[BIMVERDI_PENDING_OPPGAVE_KEY] = [
        'url'       => isset($oppgave['url']) ? esc_url_raw($oppgave['url']) : '',
        'label'     => isset($oppgave['label']) ? sanitize_text_field($oppgave['label']) : 'registreringen',
        'context'   => isset($oppgave['context']) && is_array($oppgave['context']) ? $oppgave['context'] : [],
        'timestamp' => time(),
    ];
}

/**
 * Hent pending oppgave hvis innenfor TTL. Sletter automatisk ved utløp.
 *
 * @return array|null
 */
function bimverdi_get_pending_oppgave() {
    if (!bimverdi_maybe_start_session()) {
        return null;
    }
    if (!isset($_SESSION[BIMVERDI_PENDING_OPPGAVE_KEY])) {
        return null;
    }
    $oppgave = $_SESSION[BIMVERDI_PENDING_OPPGAVE_KEY];
    if (!is_array($oppgave) || empty($oppgave['timestamp'])) {
        bimverdi_clear_pending_oppgave();
        return null;
    }
    if (time() - (int) $oppgave['timestamp'] > BIMVERDI_PENDING_OPPGAVE_TTL) {
        bimverdi_clear_pending_oppgave();
        return null;
    }
    return $oppgave;
}

/**
 * Fjern pending oppgave fra sesjonen.
 */
function bimverdi_clear_pending_oppgave() {
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION[BIMVERDI_PENDING_OPPGAVE_KEY])) {
        unset($_SESSION[BIMVERDI_PENDING_OPPGAVE_KEY]);
    }
}

/**
 * Hent retry-URL hvis pending oppgave finnes og er gyldig, ellers null.
 *
 * @return string|null
 */
function bimverdi_resume_pending_oppgave_url() {
    $oppgave = bimverdi_get_pending_oppgave();
    if (!$oppgave || empty($oppgave['url'])) {
        return null;
    }
    return $oppgave['url'];
}

/**
 * Etter at brukeren har koblet seg til et foretak: redirect tilbake til pending oppgave hvis satt.
 *
 * Kalles eksplisitt fra foretak-link-handlere når kobling er bekreftet.
 */
function bimverdi_redirect_to_pending_oppgave_if_any() {
    $url = bimverdi_resume_pending_oppgave_url();
    if (!$url) {
        return;
    }
    bimverdi_clear_pending_oppgave();
    wp_safe_redirect($url);
    exit;
}

/**
 * Når en bruker treffer foretaks-kobling-siden med ?retry=1, vis info hvis sesjonen er utløpt.
 *
 * Brukes av template-part som rendrer welcome-foretak-kobling.php / dashboardet.
 */
function bimverdi_pending_oppgave_status() {
    if (!isset($_GET['retry']) || $_GET['retry'] !== '1') {
        return null;
    }
    $oppgave = bimverdi_get_pending_oppgave();
    if (!$oppgave) {
        return [
            'state' => 'expired',
            'message' => 'Oppgaven du startet på har utløpt. Start på nytt fra forrige side.',
        ];
    }
    return [
        'state'   => 'active',
        'oppgave' => $oppgave,
        'message' => sprintf(
            'Etter at du har koblet til foretak, sender vi deg tilbake til %s.',
            $oppgave['label']
        ),
    ];
}
