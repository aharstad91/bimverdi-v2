<?php
/**
 * Plugin Name: BIM Verdi - Diskusjon: e-postvarsler
 * Description: Mention- og svar-varsler for diskusjonstråden (pilot: Byggchat, plan docs/plans/2026-08-11-001). Sendes via Resend (wp_mail) bak en hard, fail-closed sikkerhetsgate.
 * Version: 1.0.0
 *
 * 🔒 SIKKERHETSGATE (R12, synk 11.08): I MOTSETNING til avlyst-gaten
 * (bimverdi-arrangement-avlyst.php, miljøstyrt) er denne gaten LÅST OVERALT
 * — også på prod — til Bårds eksplisitte go via Teams (R12b). Mens gaten er
 * låst sendes varsler KUN til allowlisten (andreas@aharstad.no); alle andre
 * mottakere hoppes over med logglinje.
 *
 * Åpning etter go er en bevisst énlinjes endring i wp-config.php PÅ PROD:
 *     define('BIMVERDI_DISKUSJON_VARSLER_APEN', true);
 * Konstant (ikke option) er valgt med vilje: databasen kopieres mellom prod
 * og localhost — lokal DB inneholder ekte medlemmer med ekte e-postadresser —
 * så en option ville fulgt med på DB-kopi. wp-config gjør ikke det.
 * Fail-closed: udefinert eller alt annet enn boolsk true = låst.
 *
 * Varsler blokkerer aldri publisering (hele hooken er feilisolert), og går
 * bevisst utenom WPs native kommentarvarsel-pipeline (som holdes tom av
 * bimverdi-still-sporsmal.php).
 *
 * Logglinjer bruker prefikset [bv-varsler] — det er BEVISET for gate-atferd:
 * lokalt maskerer _local-email-blocker.php leveranser (returnerer true), så
 * gate-skipp verifiseres via disse linjene, aldri via fravær av levert e-post.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Allowlist: de eneste adressene som mottar varsler mens gaten er låst.
 * @return string[] små bokstaver
 */
function bimverdi_diskusjon_varsler_allowlist() {
    $list = apply_filters('bimverdi_diskusjon_varsler_allowlist', ['andreas@aharstad.no']);
    $list = array_map(function ($e) { return strtolower(trim((string) $e)); }, (array) $list);
    return array_values(array_unique(array_filter($list)));
}

/**
 * Er gaten åpnet (Bårds go mottatt)? Fail-closed: kun eksplisitt boolsk true
 * i konstanten (eller via filteret) åpner — udefinert, 1, 'ja' osv. = låst.
 */
function bimverdi_diskusjon_varsler_gate_apen() {
    $apen = defined('BIMVERDI_DISKUSJON_VARSLER_APEN')
        && true === BIMVERDI_DISKUSJON_VARSLER_APEN;
    return true === apply_filters('bimverdi_diskusjon_varsler_gate_apen', $apen);
}

/**
 * Kan denne adressen motta varsel akkurat nå? Åpen gate → alle; låst gate →
 * kun allowlisten. All utsending MÅ gjennom denne.
 */
function bimverdi_diskusjon_varsel_mottaker_tillatt($epost) {
    if (bimverdi_diskusjon_varsler_gate_apen()) {
        return true;
    }
    return in_array(strtolower(trim((string) $epost)), bimverdi_diskusjon_varsler_allowlist(), true);
}

/**
 * Varsle mottakere når en kommentar publiseres i diskusjonskonteksten.
 *
 * comment_post fyrer i wp_new_comment ETTER wp_insert_comment-hooken som
 * binder _bv_mention_user_ids (bimverdi-diskusjon-mentions.php) — metaen er
 * derfor alltid klar her. Feilisolert med Throwable-fangst: en varselfeil
 * skal aldri gi brukeren feilside etter at kommentaren faktisk er lagret.
 */
add_action('comment_post', 'bimverdi_diskusjon_varsle', 20, 2);
function bimverdi_diskusjon_varsle($comment_id, $approved) {
    try {
        if (1 !== (int) $approved) {
            return; // 0/'spam'/hold — motoren auto-godkjenner, alt annet er utenfor scope.
        }
        $comment = get_comment($comment_id);
        if (!$comment) {
            return;
        }
        $post = get_post($comment->comment_post_ID);
        if (!$post || !function_exists('bimverdi_diskusjon_aktiv') || !bimverdi_diskusjon_aktiv($post)) {
            return;
        }

        $avsender_id   = (int) $comment->user_id;
        $avsender      = $avsender_id ? get_userdata($avsender_id) : false;
        $avsender_navn = $avsender ? $avsender->display_name : $comment->comment_author;

        // Mottaker-oppløsning (R10): map user_id → varseltype, der mention
        // overskriver svar — én e-post per person, mention-malen vinner.
        $mottakere = [];
        if ($comment->comment_parent) {
            $forelder = get_comment($comment->comment_parent);
            if ($forelder && (int) $forelder->user_id > 0) {
                $mottakere[(int) $forelder->user_id] = 'svar';
            }
        }
        $mention_ids = get_comment_meta($comment_id, '_bv_mention_user_ids', true);
        if (is_array($mention_ids)) {
            foreach ($mention_ids as $uid) {
                if ((int) $uid > 0) {
                    $mottakere[(int) $uid] = 'mention';
                }
            }
        }
        unset($mottakere[$avsender_id]); // Aldri varsel til seg selv.

        if (!$mottakere) {
            return;
        }

        // R15b: tak på varselmottakere per time per avsender. Sjekkes ETTER
        // gaten, så gate-skipp ikke spiser av kvoten.
        $timetak  = (int) apply_filters('bimverdi_diskusjon_varsel_timetak', 30);
        $rate_key = 'bv_diskusjon_varsel_rate_' . $avsender_id;

        foreach ($mottakere as $uid => $type) {
            $mottaker = get_userdata($uid);
            if (!$mottaker || !is_email($mottaker->user_email)) {
                error_log(sprintf('[bv-varsler] Hoppet over %s-varsel: bruker %d mangler/har ugyldig e-post (kommentar %d).', $type, $uid, $comment_id));
                continue;
            }

            // GATE (R12) — hard, fail-closed. Denne logglinjen er selve
            // beviset i gated test (leveransefravær beviser ingenting lokalt).
            if (!bimverdi_diskusjon_varsel_mottaker_tillatt($mottaker->user_email)) {
                error_log(sprintf('[bv-varsler] GATE LÅST — hoppet over %s-varsel til bruker %d (%s) for kommentar %d.', $type, $uid, $mottaker->user_email, $comment_id));
                continue;
            }

            $antall = (int) get_transient($rate_key);
            if ($antall >= $timetak) {
                error_log(sprintf('[bv-varsler] TIMETAK (%d/t) — hoppet over %s-varsel til bruker %d for kommentar %d (avsender %d).', $timetak, $type, $uid, $comment_id, $avsender_id));
                continue;
            }
            set_transient($rate_key, $antall + 1, HOUR_IN_SECONDS);

            bimverdi_diskusjon_varsel_send($type, $mottaker, $avsender_navn, $comment, $post);
        }
    } catch (\Throwable $e) {
        error_log(sprintf('[bv-varsler] Uventet feil (kommentar %d): %s', $comment_id, $e->getMessage()));
    }
}

/**
 * Kontekst-flagg: er vi midt i en varsel-utsending? Brukes av BCC-filteret
 * under til å identifisere VÅRE utsendinger — bevisst valgt fremfor
 * subject-matching, som ville koblet konfidensialiteten til mal-tekst som
 * kan endres under pussing (R12c).
 */
function bimverdi_diskusjon_varsel_kontekst($sett = null) {
    static $pagar = false;
    if (null !== $sett) {
        $pagar = (bool) $sett;
    }
    return $pagar;
}

/**
 * R12c: mens gaten er låst skal kommentarutdrag IKke i delt postkasse — den
 * globale prod-BCC-en til post@bimverdi.no (bimverdi-resend-mail.php)
 * undertrykkes for varselutsendinger. Når gaten åpnes etter go, gjenopptas
 * global BCC automatisk (betingelsen faller bort av seg selv).
 */
add_filter('bimverdi_resend_global_bcc_aktiv', function ($aktiv, $to = null, $subject = null) {
    if (bimverdi_diskusjon_varsel_kontekst() && !bimverdi_diskusjon_varsler_gate_apen()) {
        return false;
    }
    return $aktiv;
}, 10, 3);

/**
 * Bygg HTML-innholdet i varselet (mønster: bimverdi_avlyst_email_html).
 *
 * @param string     $type          'mention' | 'svar'
 * @param WP_User    $mottaker
 * @param string     $avsender_navn
 * @param WP_Comment $comment
 * @param WP_Post    $post
 * @param string     $lenke         Anker-URL (?bvk={id}#comment-{id})
 */
function bimverdi_diskusjon_varsel_html($type, $mottaker, $avsender_navn, $comment, $post, $lenke) {
    $tittel = html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8');
    $utdrag = wp_trim_words(wp_strip_all_tags($comment->comment_content), 30, ' …');

    if ('mention' === $type) {
        $lead   = sprintf('<strong>%s</strong> nevnte deg i en kommentar i diskusjonen på <strong>«%s»</strong>:', esc_html($avsender_navn), esc_html($tittel));
        $grunn  = sprintf('Du mottar denne e-posten fordi %s nevnte deg med @navn i en diskusjon på bimverdi.no.', esc_html($avsender_navn));
    } else {
        $lead   = sprintf('<strong>%s</strong> svarte på kommentaren din i diskusjonen på <strong>«%s»</strong>:', esc_html($avsender_navn), esc_html($tittel));
        $grunn  = 'Du mottar denne e-posten fordi noen svarte på kommentaren din i en diskusjon på bimverdi.no.';
    }

    $test_banner = '';
    if (!bimverdi_diskusjon_varsler_gate_apen()) {
        $test_banner =
            '<div style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px;padding:12px 16px;margin:0 0 20px;font-size:13px;color:#92400E;">'
            . '<strong>Testkopi — sikkerhetsgate aktiv.</strong> Varsler går kun til allowlisten; ingen andre mottakere er varslet.'
            . '</div>';
    }

    ob_start();
    // Speiler husets medlems-e-postmal (bimverdi-email-verification.php,
    // get_verification_email_html): fullt HTML-dokument med tabell-layout og
    // hvitt kort på #F5F3EE-bakgrunn. Klienter (bl.a. Spark) rendrer nakne
    // div-fragmenter som «personlig post» med egen typografi — da strippes
    // knappe- og boks-styling.
    ?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($tittel); ?></title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #F5F3EE; color: #1A1A1A;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F5F3EE;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 520px; margin: 0 auto;">

                    <!-- Logo -->
                    <tr>
                        <td style="text-align: center; padding-bottom: 32px;">
                            <span style="font-size: 20px; font-weight: 700; color: #1A1A1A;">BIM Verdi</span>
                        </td>
                    </tr>

                    <!-- Hovedkort -->
                    <tr>
                        <td>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
                                <tr>
                                    <td style="padding: 40px;">

                                        <?php echo $test_banner; ?>

                                        <p style="margin: 0 0 24px 0; color: #1A1A1A; font-size: 16px; line-height: 1.6;">
                                            Hei <?php echo esc_html($mottaker->display_name); ?>,
                                        </p>

                                        <p style="margin: 0 0 24px 0; color: #1A1A1A; font-size: 16px; line-height: 1.6;">
                                            <?php echo $lead; ?>
                                        </p>

                                        <!-- Kommentar-utdrag -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0 0 8px 0;">
                                            <tr>
                                                <td style="background-color: #F5F5F4; border-left: 3px solid #FF8B5E; border-radius: 0 8px 8px 0; padding: 14px 18px; color: #3A3A3A; font-size: 15px; line-height: 1.6;">
                                                    <?php echo esc_html($utdrag); ?>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- CTA-knapp -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 32px 0 8px 0;">
                                            <tr>
                                                <td align="center">
                                                    <a href="<?php echo esc_url($lenke); ?>"
                                                       style="display: inline-block; background-color: #FF8B5E; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 16px; font-weight: 500;">
                                                        Les og svar i diskusjonen
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 0; text-align: center;">
                            <p style="margin: 0 0 16px 0; color: #9B9B9B; font-size: 12px;">
                                Fungerer ikke knappen?
                                <a href="<?php echo esc_url($lenke); ?>" style="color: #6B6B6B;">Klikk her</a>
                            </p>
                            <p style="margin: 0; color: #9B9B9B; font-size: 11px; line-height: 1.6;">
                                <?php echo $grunn; ?><br>
                                Varselet er sendt til e-postadressen som er registrert på brukerkontoen din.
                                Les mer i vår <a href="<?php echo esc_url(home_url('/personvern/')); ?>" style="color: #6B6B6B;">personvernerklæring</a>.<br>
                                <?php // GDPR art. 21(4): varslene sendes på legitim interesse, som krever en
                                      // reell innsigelsesmulighet. Avsender er noreply@, så ruten er den
                                      // bemannede postkassen — inntil profil-toggelen (fase 2) finnes. ?>
                                Vil du ikke motta slike varsler? Gi beskjed til
                                <a href="mailto:post@bimverdi.no?subject=<?php echo rawurlencode('Avmelding: varsler om diskusjoner'); ?>" style="color: #6B6B6B;">post@bimverdi.no</a>,
                                så skrur vi dem av for deg.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
    <?php
    return trim(ob_get_clean());
}

/**
 * Send ett varsel. Kalles KUN etter at gate + timetak har sluppet mottakeren
 * gjennom (bimverdi_diskusjon_varsle over). Synkron sending; Resend-feil
 * logges med kommentar-ID og mottaker, ingen retry i v1.
 */
function bimverdi_diskusjon_varsel_send($type, $mottaker, $avsender_navn, $comment, $post) {
    $lenke = add_query_arg('bvk', (int) $comment->comment_ID, get_permalink($post))
        . '#comment-' . (int) $comment->comment_ID;

    // Emnefelt: dekod entiteter + strip CR/LF som barriere mot header-injection
    // (avsendernavn er brukerstyrt display_name) — speiler avlyst-mønsteret.
    $ren = function ($s) {
        return trim(preg_replace('/[\r\n]+/', ' ', html_entity_decode((string) $s, ENT_QUOTES, 'UTF-8')));
    };
    if ('mention' === $type) {
        $emne = sprintf('Du ble nevnt av %s på BIM Verdi', $ren($avsender_navn));
    } else {
        $emne = sprintf('%s svarte på kommentaren din på BIM Verdi', $ren($avsender_navn));
    }

    $html = bimverdi_diskusjon_varsel_html($type, $mottaker, $avsender_navn, $comment, $post, $lenke);

    bimverdi_diskusjon_varsel_kontekst(true);
    $ok = wp_mail($mottaker->user_email, $emne, $html, ['Content-Type: text/html; charset=UTF-8']);
    bimverdi_diskusjon_varsel_kontekst(false);

    if ($ok) {
        error_log(sprintf('[bv-varsler] SENDT %s-varsel til bruker %d (%s) for kommentar %d.', $type, $mottaker->ID, $mottaker->user_email, $comment->comment_ID));
    } else {
        error_log(sprintf('[bv-varsler] FEILET %s-varsel til bruker %d (%s) for kommentar %d — kommentaren er publisert uansett.', $type, $mottaker->ID, $mottaker->user_email, $comment->comment_ID));
    }
    return $ok;
}
