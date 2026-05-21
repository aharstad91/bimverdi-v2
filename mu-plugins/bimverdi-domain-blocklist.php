<?php
/**
 * Plugin Name: BIM Verdi - Domain Blocklist
 * Description: Generelle og engangs-e-post-domener — admin Settings-side + helpers.
 * Version: 1.0.0
 *
 * Krav 20 / v3:
 * - bv_generelle_domener: gmail.com, hotmail.com, outlook.com, etc.
 * - bv_engangsdomener_override: per-domene override (synker månedlig fra disposable-email-domains i Fase 2/3)
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bårds startverdi for generelle e-post-domener (Krav 20, v3, kap. "Lister over avviste domener").
 */
function bimverdi_default_general_domains() {
    return [
        'gmail.com', 'googlemail.com',
        'hotmail.com', 'hotmail.no',
        'outlook.com', 'outlook.no',
        'live.com', 'msn.com',
        'yahoo.com', 'yahoo.no',
        'icloud.com', 'me.com', 'mac.com',
        'protonmail.com', 'proton.me',
        'aol.com', 'online.no',
    ];
}

/**
 * Initialiser default-verdiene hvis de ikke finnes.
 */
add_action('init', function () {
    if (get_option('bv_generelle_domener', null) === null) {
        update_option('bv_generelle_domener', bimverdi_default_general_domains(), false);
    }
    if (get_option('bv_engangsdomener_override', null) === null) {
        update_option('bv_engangsdomener_override', [], false);
    }
}, 5);

/**
 * Helper: er domenet i blocklisten for generelle e-poster?
 *
 * @param string $domain Lowercase domene, eller hele e-postadressen.
 * @return bool
 */
function bimverdi_is_general_domain($domain) {
    if (!is_string($domain) || $domain === '') {
        return false;
    }
    $domain = strtolower(trim($domain));

    // Hvis det ble sendt inn en e-postadresse, hent ut bare domenedelen
    if (strpos($domain, '@') !== false) {
        $extracted = bimverdi_extract_root_domain($domain);
        if (!$extracted) {
            return false;
        }
        $domain = $extracted;
    }

    $list = (array) get_option('bv_generelle_domener', []);
    return in_array($domain, array_map('strtolower', $list), true);
}

/**
 * Helper: er domenet i blocklisten for engangs-e-poster (admin override)?
 *
 * Note: I Fase 2/3 vil dette utvides til å sjekke en større liste fra
 * disposable-email-domains-prosjektet via månedlig cron-sync. Inntil videre
 * brukes kun admin-redigerbar override.
 *
 * @param string $domain Lowercase domene, eller hele e-postadressen.
 * @return bool
 */
function bimverdi_is_disposable_domain($domain) {
    if (!is_string($domain) || $domain === '') {
        return false;
    }
    $domain = strtolower(trim($domain));

    if (strpos($domain, '@') !== false) {
        $extracted = bimverdi_extract_root_domain($domain);
        if (!$extracted) {
            return false;
        }
        $domain = $extracted;
    }

    $list = (array) get_option('bv_engangsdomener_override', []);
    return in_array($domain, array_map('strtolower', $list), true);
}

/**
 * Settings-page registrering — under "Innstillinger" toppmenyen.
 */
add_action('admin_menu', function () {
    add_options_page(
        'BIM Verdi - Domene-blocklist',
        'BIM Verdi domener',
        'manage_options',
        'bv-domene-blocklist',
        'bimverdi_render_domain_blocklist_page'
    );
});

add_action('admin_init', function () {
    register_setting('bv_domene_blocklist', 'bv_generelle_domener', [
        'type' => 'array',
        'sanitize_callback' => 'bimverdi_sanitize_domain_list',
        'default' => bimverdi_default_general_domains(),
    ]);
    register_setting('bv_domene_blocklist', 'bv_engangsdomener_override', [
        'type' => 'array',
        'sanitize_callback' => 'bimverdi_sanitize_domain_list',
        'default' => [],
    ]);
});

/**
 * Sanitiser textarea-input til ren liste over lowercase domener.
 *
 * @param mixed $input Tekst fra textarea (én linje per domene) eller array.
 * @return array Renset domeneliste, sortert og uten duplikater.
 */
function bimverdi_sanitize_domain_list($input) {
    if (is_array($input)) {
        $lines = $input;
    } else {
        $lines = preg_split('/[\r\n,]+/', (string) $input);
    }

    $cleaned = [];
    foreach ($lines as $line) {
        $d = strtolower(trim($line));
        if ($d === '') {
            continue;
        }
        if (!preg_match('/^[a-z0-9.\-]+$/', $d)) {
            continue;
        }
        $cleaned[$d] = true;
    }

    $list = array_keys($cleaned);
    sort($list);
    return $list;
}

/**
 * Render Settings-side.
 */
function bimverdi_render_domain_blocklist_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $generelle = (array) get_option('bv_generelle_domener', []);
    $engangs = (array) get_option('bv_engangsdomener_override', []);

    ?>
    <div class="wrap">
        <h1>BIM Verdi - Domene-blocklist</h1>
        <p>
            Vedlikehold av domener som <em>ikke</em> aksepteres ved registrering.
            Disse listene brukes av onboarding-flyten for å avvise generelle e-poster (gmail, hotmail, etc.)
            og kjente engangs-e-post-tjenester.
        </p>

        <form method="post" action="options.php">
            <?php settings_fields('bv_domene_blocklist'); ?>

            <h2>Generelle e-post-domener</h2>
            <p class="description">
                Én linje per domene. Eksempel: <code>gmail.com</code>. Bårds startliste populeres automatisk
                ved første aktivering.
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="bv_generelle_domener">Domener</label></th>
                    <td>
                        <textarea
                            id="bv_generelle_domener"
                            name="bv_generelle_domener"
                            rows="12"
                            cols="50"
                            class="large-text code"
                        ><?php echo esc_textarea(implode("\n", $generelle)); ?></textarea>
                        <p class="description">
                            <?php echo count($generelle); ?> domener i listen.
                        </p>
                    </td>
                </tr>
            </table>

            <h2>Engangs-e-post (override)</h2>
            <p class="description">
                Egne domener du vil legge til eller fjerne fra det automatiske disposable-feed-et.
                I Fase 2/3 supplereres denne med månedlig synk fra <code>disposable-email-domains</code>-prosjektet.
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="bv_engangsdomener_override">Domener</label></th>
                    <td>
                        <textarea
                            id="bv_engangsdomener_override"
                            name="bv_engangsdomener_override"
                            rows="6"
                            cols="50"
                            class="large-text code"
                        ><?php echo esc_textarea(implode("\n", $engangs)); ?></textarea>
                        <p class="description">
                            <?php echo count($engangs); ?> domener i listen.
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button('Lagre listene'); ?>
        </form>
    </div>
    <?php
}
