<?php
/**
 * Plugin Name: BIM Verdi - Delingslogg
 * Description: Logger når en side deles via del-knappen, og viser statistikken under Innstillinger → Delingslogg.
 * Version: 1.0.0
 *
 * Bård, Trello #347 punkt 1: en «mailto»-knapp på alle sider som åpner
 * e-postklienten med lenke og en ferdig tekst. Innlogging ikke nødvendig,
 * «men vi bør ha en logg som viser når/hvordan dette blir brukt».
 *
 * En ren mailto-lenke kan ikke logges — nettleseren gir serveren ingenting å
 * ta imot. Klikket sender derfor et lite beacon-kall hit før default-handlingen
 * fortsetter, og lenken er fortsatt en ekte mailto: klarer JS-en ikke å kjøre,
 * åpner e-postklienten uansett — vi mister bare loggraden.
 *
 * PERSONVERN: vi lagrer ikke IP-adresse. IP brukes bare i en transient for
 * rate-limit, og forsvinner av seg selv. Loggen svarer på Bårds spørsmål
 * (når, hvilken side, innlogget eller ikke) uten å bygge et
 * atferdsregister over navngitte besøkende.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BIMVERDI_DEL_LOGG_DB_VERSION')) {
    define('BIMVERDI_DEL_LOGG_DB_VERSION', '1.0.0');
}

/**
 * Tabellnavn med prefiks.
 *
 * @return string
 */
function bimverdi_del_logg_tabell() {
    global $wpdb;
    return $wpdb->prefix . 'bimverdi_del_logg';
}

/**
 * Innholdstyper der del-knappen finnes, og som derfor kan logges.
 *
 * @return string[]
 */
function bimverdi_del_logg_post_types() {
    return apply_filters('bimverdi_del_logg_post_types', array(
        'artikkel', 'verktoy', 'arrangement', 'foretak', 'theme_group', 'kunnskapskilde', 'page',
    ));
}

/**
 * Kanaler vi godtar. «epost» er den Bård ba om; de andre er der for at
 * «Kopier lenke» og LinkedIn kan logges senere uten en ny tabellversjon.
 *
 * @return string[]
 */
function bimverdi_del_logg_kanaler() {
    return array('epost', 'kopier', 'linkedin');
}

/**
 * Opprett tabellen. Kjøres bare når versjonsnøkkelen mangler eller er gammel.
 *
 * @return void
 */
function bimverdi_del_logg_opprett_tabell() {
    global $wpdb;

    $tabell   = bimverdi_del_logg_tabell();
    $collate  = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$tabell} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        post_id bigint(20) unsigned NOT NULL,
        post_type varchar(32) NOT NULL,
        kanal varchar(16) NOT NULL,
        user_id bigint(20) unsigned DEFAULT NULL,
        innlogget tinyint(1) NOT NULL DEFAULT 0,
        referer varchar(255) DEFAULT NULL,
        opprettet datetime NOT NULL,
        PRIMARY KEY (id),
        KEY post_id (post_id),
        KEY opprettet (opprettet),
        KEY kanal (kanal)
    ) {$collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    update_option('bimverdi_del_logg_db_version', BIMVERDI_DEL_LOGG_DB_VERSION);
}

add_action('admin_init', function () {
    if (get_option('bimverdi_del_logg_db_version') !== BIMVERDI_DEL_LOGG_DB_VERSION) {
        bimverdi_del_logg_opprett_tabell();
    }
});

// =============================================================================
// REST: mottak av loggkall
// =============================================================================

add_action('rest_api_init', function () {
    register_rest_route('bimverdi/v1', '/del-logg', array(
        'methods'  => 'POST',
        // Utloggede skal kunne dele — det var et uttrykt krav. Vakten ligger i
        // callbacken: bare eksisterende, publiserte poster av kjente typer
        // godtas, og rate-limit stopper misbruk.
        'permission_callback' => '__return_true',
        'args' => array(
            'post_id' => array(
                'required'          => true,
                'sanitize_callback' => 'absint',
            ),
            'kanal' => array(
                'required'          => false,
                'sanitize_callback' => 'sanitize_key',
            ),
        ),
        'callback' => 'bimverdi_del_logg_motta',
    ));
});

/**
 * Klientens IP, kun til rate-limit. Lagres aldri.
 *
 * @return string
 */
function bimverdi_del_logg_ip() {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    return $ip !== '' ? $ip : 'ukjent';
}

/**
 * Ta imot ett loggkall.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function bimverdi_del_logg_motta($request) {
    global $wpdb;

    $post_id = (int) $request->get_param('post_id');
    $kanal   = (string) $request->get_param('kanal');
    if ($kanal === '') {
        $kanal = 'epost';
    }

    if (!in_array($kanal, bimverdi_del_logg_kanaler(), true)) {
        return new WP_Error('bv_del_ugyldig_kanal', 'Ukjent kanal.', array('status' => 400));
    }

    $post = $post_id ? get_post($post_id) : null;
    if (!$post || $post->post_status !== 'publish'
        || !in_array($post->post_type, bimverdi_del_logg_post_types(), true)) {
        return new WP_Error('bv_del_ukjent_side', 'Ukjent side.', array('status' => 400));
    }

    // Rate-limit: 20 delinger per IP per time. Nøkkelen er hashet, så selve
    // IP-en ligger ikke i klartekst i options-/cache-laget.
    $nokkel  = 'bv_del_rate_' . md5(bimverdi_del_logg_ip());
    $antall  = (int) get_transient($nokkel);
    if ($antall >= 20) {
        return new WP_Error('bv_del_for_mange', 'For mange delinger på kort tid.', array('status' => 429));
    }
    set_transient($nokkel, $antall + 1, HOUR_IN_SECONDS);

    $bruker_id = get_current_user_id();
    $referer   = (string) $request->get_header('referer');

    $ok = $wpdb->insert(
        bimverdi_del_logg_tabell(),
        array(
            'post_id'   => $post->ID,
            'post_type' => $post->post_type,
            'kanal'     => $kanal,
            'user_id'   => $bruker_id ?: null,
            'innlogget' => $bruker_id ? 1 : 0,
            'referer'   => $referer !== '' ? substr(esc_url_raw($referer), 0, 255) : null,
            'opprettet' => current_time('mysql'),
        ),
        array('%d', '%s', '%s', '%d', '%d', '%s', '%s')
    );

    if ($ok === false) {
        return new WP_Error('bv_del_lagring', 'Kunne ikke lagre.', array('status' => 500));
    }

    return new WP_REST_Response(array('lagret' => true), 201);
}

// =============================================================================
// Admin: Innstillinger → Delingslogg
// =============================================================================

add_action('admin_menu', function () {
    add_options_page(
        'Delingslogg',
        'Delingslogg',
        'manage_options',
        'bimverdi-del-logg',
        'bimverdi_del_logg_admin_side'
    );
});

/**
 * Read-only oversikt. Ingen skjemaer og ingen handlinger, altså ingen
 * CSRF-flate å beskytte.
 *
 * @return void
 */
function bimverdi_del_logg_admin_side() {
    global $wpdb;

    if (!current_user_can('manage_options')) {
        wp_die('Du har ikke tilgang til denne siden.', '', array('response' => 403));
    }

    $tabell = bimverdi_del_logg_tabell();
    if (!$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabell))) {
        bimverdi_del_logg_opprett_tabell();
    }

    $totalt   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabell}");
    $siste_30 = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabell} WHERE opprettet >= %s",
        gmdate('Y-m-d H:i:s', strtotime(current_time('mysql')) - 30 * DAY_IN_SECONDS)
    ));
    $innlogget = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabell} WHERE innlogget = 1");

    $topp = $wpdb->get_results(
        "SELECT post_id, COUNT(*) AS antall FROM {$tabell} GROUP BY post_id ORDER BY antall DESC LIMIT 10"
    );
    $siste = $wpdb->get_results(
        "SELECT * FROM {$tabell} ORDER BY opprettet DESC, id DESC LIMIT 50"
    );
    ?>
    <div class="wrap">
        <h1>Delingslogg</h1>
        <p class="description" style="max-width:60em">
            Hver rad er ett klikk på «Del via e-post» ute på nettsiden. Vi lagrer dato,
            hvilken side det gjaldt, og om den som delte var innlogget — ikke IP-adresse.
            Loggen sier altså hvor ofte og hvor delingen brukes, ikke hvem som mottok e-posten.
        </p>

        <div style="display:flex;gap:32px;margin:24px 0 32px;flex-wrap:wrap">
            <div><div style="font-size:28px;font-weight:600"><?php echo esc_html(number_format_i18n($totalt)); ?></div><div style="color:#646970">delinger totalt</div></div>
            <div><div style="font-size:28px;font-weight:600"><?php echo esc_html(number_format_i18n($siste_30)); ?></div><div style="color:#646970">siste 30 dager</div></div>
            <div><div style="font-size:28px;font-weight:600"><?php echo esc_html(number_format_i18n($innlogget)); ?></div><div style="color:#646970">av innloggede</div></div>
        </div>

        <?php if (!$totalt) : ?>
            <p><em>Ingen delinger er registrert ennå.</em></p>
        <?php else : ?>

            <h2>Mest delte sider</h2>
            <table class="widefat striped" style="max-width:60em">
                <thead><tr><th>Side</th><th style="width:8em">Delinger</th></tr></thead>
                <tbody>
                <?php foreach ($topp as $rad) : ?>
                    <tr>
                        <td>
                            <?php
                            $tittel = get_the_title((int) $rad->post_id);
                            $lenke  = get_permalink((int) $rad->post_id);
                            if ($tittel && $lenke) {
                                printf('<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url($lenke), esc_html($tittel));
                            } else {
                                printf('<em>Slettet side (#%d)</em>', (int) $rad->post_id);
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html(number_format_i18n((int) $rad->antall)); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h2 style="margin-top:32px">Siste 50 delinger</h2>
            <table class="widefat striped">
                <thead><tr><th style="width:14em">Tidspunkt</th><th>Side</th><th style="width:8em">Kanal</th><th style="width:16em">Delt av</th></tr></thead>
                <tbody>
                <?php foreach ($siste as $rad) : ?>
                    <tr>
                        <td><?php echo esc_html(mysql2date('j. M Y H:i', $rad->opprettet)); ?></td>
                        <td>
                            <?php
                            $tittel = get_the_title((int) $rad->post_id);
                            $lenke  = get_permalink((int) $rad->post_id);
                            if ($tittel && $lenke) {
                                printf('<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url($lenke), esc_html($tittel));
                            } else {
                                printf('<em>Slettet side (#%d)</em>', (int) $rad->post_id);
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($rad->kanal); ?></td>
                        <td>
                            <?php
                            if ((int) $rad->innlogget && $rad->user_id) {
                                $bruker = get_userdata((int) $rad->user_id);
                                echo $bruker ? esc_html($bruker->display_name) : esc_html__('Slettet bruker', 'bimverdi');
                            } else {
                                echo '<span style="color:#646970">Ikke innlogget</span>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </div>
    <?php
}
