<?php
/**
 * BIM Verdi — Selvbetjent konverterings-handler (krav 24-v4)
 *
 * Mottar POST fra /min-side/oppgrader/ og utfører de 6 atomiske operasjonene
 * spesifisert i krav 24-v4 § "Konverterings-handlingen ved bekreftelse":
 *
 *   1. bv_foretakstype: 'gratisforetak' → 'foretak'
 *   2. bv_nivaa: '' → valgt nivå
 *   3. hovedkontaktperson settes til konverterende bruker
 *   4. bv_rolle = 'tilleggskontakt' på alle andre koblede brukere (user-meta)
 *   5. bimverdi_purge_foretak_cache() + eksplisitt purge av /medlemmer/
 *   6. Varslingsjobb (e-post + Min Side-notifikasjon) — STUB i prototype-runden
 *
 * Operasjon 1-4 kjøres i én $wpdb-transaksjon. 5-6 kjøres etter COMMIT.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

// Vi kjører på template_redirect (ikke admin-post.php) fordi
// bimverdi_block_wp_admin_for_non_admins() i bimverdi-custom-roles.php
// kicker ut alle ikke-admins fra admin_init før admin_post-hooken kjører.
add_action('template_redirect', 'bimverdi_handle_oppgrader_submission', 1);

function bimverdi_handle_oppgrader_submission() {
    // Bare når denne sidens POST kommer inn.
    if (empty($_POST['bimverdi_oppgrader_action']) || $_POST['bimverdi_oppgrader_action'] !== 'submit') {
        return;
    }

    $redirect_form = home_url('/min-side/oppgrader/');

    if (!is_user_logged_in()) {
        wp_safe_redirect(wp_login_url($redirect_form));
        exit;
    }

    // Nonce-sjekk
    if (!isset($_POST['_bv_oppgrader_nonce']) || !wp_verify_nonce($_POST['_bv_oppgrader_nonce'], 'bimverdi_oppgrader')) {
        wp_safe_redirect(add_query_arg('bv_error', 'invalid_nonce', $redirect_form));
        exit;
    }

    // Honeypot — bot-deteksjon (krav 21 pattern).
    if (!empty($_POST['hp_navn'])) {
        wp_safe_redirect(add_query_arg('bv_error', 'spam_detected', $redirect_form));
        exit;
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_safe_redirect(wp_login_url($redirect_form));
        exit;
    }

    // Access-gate: bare Gratisbrukere kan oppgradere
    if (!bimverdi_is_gratisbruker($user_id)) {
        wp_safe_redirect(add_query_arg('bv_error', 'not_gratisbruker', bimverdi_minside_url('')));
        exit;
    }

    $foretak_id = bimverdi_resolve_user_foretak_id($user_id);
    if (!$foretak_id) {
        wp_safe_redirect(add_query_arg('bv_error', 'no_foretak', bimverdi_minside_url('')));
        exit;
    }

    // Valider input
    $nivaa = isset($_POST['nivaa']) ? sanitize_text_field($_POST['nivaa']) : '';
    if (!in_array($nivaa, ['deltaker', 'prosjektdeltaker', 'partner'], true)) {
        wp_safe_redirect(add_query_arg('bv_error', 'invalid_nivaa', $redirect_form));
        exit;
    }
    if (empty($_POST['vilkar_godtatt'])) {
        wp_safe_redirect(add_query_arg('bv_error', 'missing_acceptance', $redirect_form));
        exit;
    }

    $bruk_egen_adresse = !empty($_POST['bruk_egen_fakturaadresse']);
    $ehf_orgnr_raw     = isset($_POST['ehf_orgnr']) ? sanitize_text_field($_POST['ehf_orgnr']) : '';
    $ehf_orgnr         = preg_replace('/\s+/', '', $ehf_orgnr_raw);
    $faktura_epost_raw = isset($_POST['faktura_epost']) ? trim((string) $_POST['faktura_epost']) : '';
    $faktura_epost     = $faktura_epost_raw !== '' ? sanitize_email($faktura_epost_raw) : '';

    // Validering: minst én av EHF eller faktura-e-post må fylles ut.
    if ($ehf_orgnr === '' && $faktura_epost_raw === '') {
        wp_safe_redirect(add_query_arg('bv_error', 'missing_invoice_kanal', $redirect_form));
        exit;
    }
    if ($faktura_epost_raw !== '' && !is_email($faktura_epost)) {
        wp_safe_redirect(add_query_arg('bv_error', 'invalid_invoice_email', $redirect_form));
        exit;
    }

    $form_data = [
        'po_nummer'           => isset($_POST['po_nummer']) ? sanitize_text_field($_POST['po_nummer']) : '',
        'avdeling'            => isset($_POST['avdeling']) ? sanitize_text_field($_POST['avdeling']) : '',
        'ehf_orgnr'           => $ehf_orgnr,
        'faktura_epost'       => $faktura_epost,
        'egen_fakturaadresse' => ($bruk_egen_adresse && isset($_POST['egen_fakturaadresse']))
            ? sanitize_textarea_field($_POST['egen_fakturaadresse'])
            : '',
    ];

    $invoice_data = bimverdi_calculate_oppgrader_invoice($nivaa, new DateTime('now', new DateTimeZone('Europe/Oslo')));
    if (!$invoice_data) {
        wp_safe_redirect(add_query_arg('bv_error', 'pris_kunne_ikke_hentes', $redirect_form));
        exit;
    }

    $result = bimverdi_convert_gratisforetak_to_foretak((int) $foretak_id, (int) $user_id, $nivaa, $invoice_data, $form_data);

    if (is_wp_error($result)) {
        error_log('[bimverdi-oppgrader] Konvertering feilet: ' . $result->get_error_message());
        wp_safe_redirect(add_query_arg('bv_error', $result->get_error_code(), $redirect_form));
        exit;
    }

    // Redirect til bekreftelses-side. Bekreftelsen viser IKKE pris-tall
    // (per møte 2026-05-28) — admin sender faktura manuelt etterpå.
    $fullfort_url = add_query_arg([
        'nivaa' => $nivaa,
    ], home_url('/min-side/oppgrader/fullfort/'));

    wp_safe_redirect($fullfort_url);
    exit;
}

/**
 * Konverterer et Gratisforetak til Foretak (Deltaker+) i én DB-transaksjon.
 *
 * @param int    $foretak_id            CPT foretak post-ID
 * @param int    $hovedkontakt_user_id  Brukeren som blir Hovedkontakt
 * @param string $nivaa                 'deltaker' | 'prosjektdeltaker' | 'partner'
 * @param array  $invoice_data          Output fra bimverdi_calculate_oppgrader_invoice()
 * @param array  $form_data             Faktura-input fra skjema
 * @return true|WP_Error
 */
function bimverdi_convert_gratisforetak_to_foretak($foretak_id, $hovedkontakt_user_id, $nivaa, $invoice_data, $form_data) {
    global $wpdb;

    // Pre-flight: verifiser at foretaket eksisterer og er Gratisforetak.
    // Skiller mellom "finnes ikke" og "allerede konvertert" for tydeligere
    // feilmeldinger og bedre debugging.
    $foretak_post = get_post($foretak_id);
    if (!$foretak_post || $foretak_post->post_type !== 'foretak') {
        return new WP_Error('foretak_ikke_funnet', 'Foretak med id ' . (int) $foretak_id . ' finnes ikke.');
    }
    $current_type = (string) get_field('bv_foretakstype', $foretak_id);
    if ($current_type !== 'gratisforetak') {
        return new WP_Error('already_foretak', 'Foretaket er allerede konvertert (foretakstype=' . esc_html($current_type ?: '(tom)') . ').');
    }

    // Finn alle andre brukere koblet til foretaket FØR vi muterer noe.
    $alle_brukere = get_users([
        'meta_query' => [
            'relation' => 'OR',
            ['key' => 'bimverdi_company_id', 'value' => $foretak_id],
            ['key' => 'bim_verdi_company_id', 'value' => $foretak_id],
        ],
        'fields' => ['ID', 'user_email', 'display_name'],
    ]);

    $andre_brukere = array_filter($alle_brukere, function($u) use ($hovedkontakt_user_id) {
        return (int) $u->ID !== (int) $hovedkontakt_user_id;
    });

    // ACF's update_field returnerer false både ved feil OG når verdien er
    // uendret. Vi kan derfor ikke avbryte på false alene — vi verifiserer
    // i stedet at de nye verdiene faktisk er lagret etter alle operasjonene.
    $wpdb->query('START TRANSACTION');

    try {
        // Operasjon 1: bv_foretakstype-flip
        update_field('bv_foretakstype', 'foretak', $foretak_id);

        // Operasjon 2: bv_nivaa-set
        update_field('bv_nivaa', $nivaa, $foretak_id);

        // Operasjon 3: hovedkontaktperson
        update_field('hovedkontaktperson', $hovedkontakt_user_id, $foretak_id);

        // Operasjon 3b: oppdater legacy bv_rolle på CPT foretak (mapping)
        // Holder gammel kode konsistent inntil bv_rolle avskaffes (Unit 11).
        $bv_rolle_map = [
            'deltaker'         => 'Deltaker',
            'prosjektdeltaker' => 'Prosjektdeltaker',
            'partner'          => 'Partner',
        ];
        update_field('bv_rolle', $bv_rolle_map[$nivaa], $foretak_id);

        // Operasjon 4: bv_rolle = 'tilleggskontakt' på andre brukere (user-meta)
        foreach ($andre_brukere as $kollega) {
            update_user_meta($kollega->ID, 'bv_rolle', 'tilleggskontakt');
        }

        // Verifiser at de nye verdiene faktisk er lagret før vi committer.
        // (ACF cacher i prosess-minnet, så vi rydder cache først.)
        if (function_exists('acf_flush_value_cache')) {
            acf_flush_value_cache($foretak_id);
        }
        wp_cache_delete($foretak_id, 'post_meta');

        $verify_type  = (string) get_field('bv_foretakstype', $foretak_id, false);
        $verify_nivaa = (string) get_field('bv_nivaa', $foretak_id, false);
        if ($verify_type !== 'foretak') {
            throw new Exception('Verifisering feilet: bv_foretakstype lagret ikke (fikk "' . $verify_type . '")');
        }
        if ($verify_nivaa !== $nivaa) {
            throw new Exception('Verifisering feilet: bv_nivaa lagret ikke (fikk "' . $verify_nivaa . '")');
        }

        $wpdb->query('COMMIT');
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('conversion_failed', $e->getMessage());
    }

    // Etter COMMIT — operasjon 5 og 6 (cache + varsling) er IKKE en del av
    // transaksjonen per krav 24-v4 (de skal kjøre uten å kunne rulle tilbake DB).

    // Operasjon 5: cache-purge
    if (function_exists('bimverdi_purge_foretak_cache')) {
        bimverdi_purge_foretak_cache($foretak_id);
    }
    // Eksplisitt purge av medlemslisten + foretaksprofilen (krav 24-v4 § Cache-håndtering)
    if (function_exists('wp_cache_delete')) {
        wp_cache_delete('medlemmer_list', 'bimverdi');
        wp_cache_delete('foretak_' . $foretak_id, 'bimverdi');
    }
    do_action('bimverdi_after_foretak_conversion', $foretak_id, $hovedkontakt_user_id, $nivaa);

    // Bonus per AK-17: oppdater åpne invitasjoner sendt mens foretaket var
    // Gratisforetak slik at mottakere som aksepterer etter konvertering blir
    // Tilleggskontakter (ikke Gratisbrukere). Per krav 23-v3 forblir den
    // allerede sendte e-posten uendret — kun DB-meta oppdateres slik at
    // UI/påminnelser bruker riktig invitasjons-tekst.
    if (function_exists('bimverdi_invitations_mark_as_foretak')) {
        $updated_invitations = bimverdi_invitations_mark_as_foretak($foretak_id);
        if ($updated_invitations > 0) {
            error_log("[bimverdi-oppgrader] Oppdaterte $updated_invitations åpne invitasjoner til invitasjons_type='foretak' for foretak $foretak_id");
        }
    }

    // Operasjon 6: varslingsjobb — e-post per krav 24-v4 (B-030).
    // Min Side-notifikasjon (B-029) er fortsatt egen delt infrastruktur (GAP-2).
    bimverdi_oppgrader_send_notifications($foretak_id, $hovedkontakt_user_id, $nivaa, $invoice_data, $form_data, $andre_brukere);

    return true;
}

/**
 * Brukervennlig nivå-label.
 *
 * @param string $nivaa
 * @return string
 */
function bimverdi_oppgrader_nivaa_label($nivaa) {
    $map = [
        'deltaker'         => 'Deltaker',
        'prosjektdeltaker' => 'Prosjektdeltaker',
        'partner'          => 'Partner',
    ];
    return $map[$nivaa] ?? ucfirst($nivaa);
}

/**
 * Felles e-post-headers (HTML + BV-avsender) per B-030.
 *
 * @return array
 */
function bimverdi_oppgrader_mail_headers() {
    return [
        'Content-Type: text/html; charset=UTF-8',
        'From: BIM Verdi <noreply@bimverdi.no>',
    ];
}

/**
 * Sender de tre oppgraderings-e-postene per krav 24-v4 (B-030):
 *   1. Bekreftelse til ny Hovedkontakt        (oppgrader-bekreftelse)
 *   2. Varsel til hver Tilleggskontakt         (oppgrader-varsel-kolleger)
 *   3. Faktura-oppsummering til administrator   (oppgrader-admin-faktura)
 *
 * Bygget på felles wp_mail-mønster (bimverdi-shared-helpers.php). Blokkerer
 * aldri kalleren — feil logges, men konverteringen er allerede committet.
 *
 * @param int    $foretak_id
 * @param int    $hovedkontakt_user_id
 * @param string $nivaa
 * @param array  $invoice_data
 * @param array  $form_data
 * @param array  $andre_brukere
 */
function bimverdi_oppgrader_send_notifications($foretak_id, $hovedkontakt_user_id, $nivaa, $invoice_data, $form_data, $andre_brukere) {
    $foretak_navn = get_the_title($foretak_id);
    $orgnr        = function_exists('get_field') ? (string) get_field('organisasjonsnummer', $foretak_id) : '';
    $hovedkontakt = get_userdata($hovedkontakt_user_id);
    $nivaa_label  = bimverdi_oppgrader_nivaa_label($nivaa);
    $headers      = bimverdi_oppgrader_mail_headers();
    $footer       = function_exists('bimverdi_render_terms_footer_html') ? bimverdi_render_terms_footer_html() : '';
    $aarspris_fmt = number_format((int) $invoice_data['totalbeloep'], 0, ',', ' ');

    // --- 1. Bekreftelse til ny Hovedkontakt ---
    // Per møte 2026-05-28: vi viser IKKE pris-tall i denne e-posten. Bård
    // sender selve fakturaen manuelt etterpå.
    if ($hovedkontakt && is_email($hovedkontakt->user_email)) {
        $subject = sprintf('%s er nå %s i BIM Verdi', $foretak_navn, $nivaa_label);
        $body  = '<p>Hei ' . esc_html($hovedkontakt->display_name) . ',</p>';
        $body .= '<p><strong>' . esc_html($foretak_navn) . '</strong> er nå <strong>' . esc_html($nivaa_label) . '</strong> i BIM Verdi. Du er hovedkontakt for foretaket, og alle andre gratisbrukere er lagt til som tilleggskontakter.</p>';
        $body .= '<p>BIM Verdi-administrasjonen oppretter faktura manuelt og sender den til foretaket' . ($form_data['faktura_epost'] ? ' (eller til ' . esc_html($form_data['faktura_epost']) . ')' : '') . '.</p>';
        $body .= '<p>Du har nå tilgang til alle medlemsfunksjoner: foretaksprofil, registrering av verktøy, artikler, temagrupper og mer.</p>';
        $body .= $footer;
        if (!wp_mail($hovedkontakt->user_email, $subject, $body, $headers)) {
            error_log('[bimverdi-oppgrader] Bekreftelses-e-post feilet til ' . $hovedkontakt->user_email);
        }
    }

    // --- 2. Varsel til hver Tilleggskontakt (eksakt tekst per krav 24-v4) ---
    $avsendernavn = $hovedkontakt ? $hovedkontakt->display_name : '';
    foreach ($andre_brukere as $kollega) {
        if (!is_email($kollega->user_email)) {
            continue;
        }
        $subject = sprintf('%s er nå %s i BIM Verdi', $foretak_navn, $nivaa_label);
        $body  = '<p>Hei ' . esc_html($kollega->display_name) . ',</p>';
        $body .= '<p>' . esc_html($avsendernavn) . ' har oppgradert <strong>' . esc_html($foretak_navn) . '</strong> til <strong>' . esc_html($nivaa_label) . '</strong>. Foretaket er nå Deltaker+, og du er automatisk lagt til som tilleggskontakt. Hovedkontakt er ' . esc_html($avsendernavn) . '.</p>';
        $body .= '<p>Du har nå tilgang til alle medlemsfunksjoner: foretaksprofilen, registrering av verktøy, skrive artikler, temagrupper og mer.</p>';
        $body .= '<p>Spørsmål? Bruk <a href="https://bimverdi.no/tilbakemelding/">bimverdi.no/tilbakemelding</a>.</p>';
        $body .= $footer;
        if (!wp_mail($kollega->user_email, $subject, $body, $headers)) {
            error_log('[bimverdi-oppgrader] Kollega-varsel feilet til ' . $kollega->user_email);
        }
    }

    // --- 3. Faktura-oppsummering til administrator (post@bimverdi.no) ---
    $tilleggs_liste = '';
    foreach ($andre_brukere as $kollega) {
        $tilleggs_liste .= '<li>' . esc_html($kollega->display_name) . ' (' . esc_html($kollega->user_email) . ')</li>';
    }
    if ($tilleggs_liste === '') {
        $tilleggs_liste = '<li>(ingen)</li>';
    }

    $admin_body  = '<h2>Ny oppgradering — opprett faktura</h2>';
    $admin_body .= '<p><strong>Foretak:</strong> ' . esc_html($foretak_navn) . ' (orgnr ' . esc_html($orgnr ?: '—') . ')</p>';
    $admin_body .= '<p><strong>Valgt nivå:</strong> ' . esc_html($nivaa_label) . '</p>';
    $admin_body .= '<p><strong>Årsavgift:</strong> ' . esc_html($aarspris_fmt) . ' kr</p>';
    $admin_body .= '<p style="font-size:12px;color:#666;">Husk å beregne rabatt fra inneværende kvartal hvis oppgraderingen skjer midt i året, og rabatter for oppstartbedrifter, utdanningsinstitusjoner og foretak med omsetning &lt; 5 MNOK der det er relevant.</p>';
    $admin_body .= '<hr>';
    $admin_body .= '<p><strong>Vår referanse / PO:</strong> ' . esc_html($form_data['po_nummer'] ?: '—') . '</p>';
    $admin_body .= '<p><strong>Avdeling / kostnadssted:</strong> ' . esc_html($form_data['avdeling'] ?: '—') . '</p>';
    $admin_body .= '<p><strong>EHF-orgnr:</strong> ' . esc_html(!empty($form_data['ehf_orgnr']) ? $form_data['ehf_orgnr'] : '—') . '</p>';
    $admin_body .= '<p><strong>Fakturamottakers e-post:</strong> ' . esc_html($form_data['faktura_epost'] ?: '—') . '</p>';
    if (!empty($form_data['egen_fakturaadresse'])) {
        $admin_body .= '<p><strong>Egen fakturaadresse:</strong><br>' . nl2br(esc_html($form_data['egen_fakturaadresse'])) . '</p>';
    }
    $admin_body .= '<hr>';
    $admin_body .= '<p><strong>Ny hovedkontakt:</strong> ' . esc_html($hovedkontakt ? $hovedkontakt->display_name : '') . ' (' . esc_html($hovedkontakt ? $hovedkontakt->user_email : '') . ')</p>';
    $admin_body .= '<p><strong>Nye tilleggskontakter:</strong></p><ul>' . $tilleggs_liste . '</ul>';
    $admin_body .= '<p><strong>Tidspunkt:</strong> ' . esc_html(current_time('d.m.Y H:i')) . '</p>';

    $admin_subject = sprintf('[Oppgradering] %s → %s (%s kr/år)', $foretak_navn, $nivaa_label, $aarspris_fmt);
    if (function_exists('bimverdi_send_admin_notification_email')) {
        bimverdi_send_admin_notification_email($admin_subject, $admin_body, $headers);
    } else {
        wp_mail(get_option('admin_email'), $admin_subject, $admin_body, $headers);
    }

    error_log(sprintf('[bimverdi-oppgrader] Varsler sendt: foretak=%d nivaa=%s hovedkontakt=%d kolleger=%d total=%d',
        $foretak_id, $nivaa, $hovedkontakt_user_id, count($andre_brukere), (int) $invoice_data['totalbeloep']));
}
