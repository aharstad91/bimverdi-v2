<?php
/**
 * Foretak-pending-godkjenning
 *
 * T5/D fra synk 2026-05-06: enhver bruker kunne tidligere registrere et
 * hvilket som helst foretak (NTNU, Statkraft, etc.) som BIM Verdi-deltaker
 * uten autorisasjonssjekk. Etter Bårds avgjørelse 2026-05-07 går alle nye
 * foretak-registreringer inn i pending-status og må godkjennes manuelt.
 *
 * Denne filen håndterer transition-hooks:
 *   - pending → publish: aktiver bruker, send velkomst-e-post
 *   - pending → trash:   send avvisning-e-post
 *
 * Form-handler (bimverdi-foretak-registration.php) lagrer foretaket som
 * pending med deltakertype i post-meta `_bv_pending_deltakertype` og venter
 * på Bårds beslutning.
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hent pending-foretak hvor brukeren er hovedkontakt.
 *
 * Brukes av dashboard for å vise pending-banner og skjule konkurrerende UI
 * (registrer-CTA, pricing-velger) mens forespørselen behandles.
 *
 * @param int|null $user_id
 * @return WP_Post|null Pending-foretak eller null hvis ingen.
 */
function bimverdi_get_user_pending_foretak($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    if (!$user_id) {
        return null;
    }

    $cpt = defined('BV_CPT_COMPANY') ? BV_CPT_COMPANY : 'foretak';
    $found = get_posts([
        'post_type'   => $cpt,
        'post_status' => 'pending',
        'numberposts' => 1,
        'meta_query'  => [
            [
                'key'   => 'hovedkontaktperson',
                'value' => $user_id,
            ],
        ],
    ]);

    return !empty($found) ? $found[0] : null;
}

add_action('transition_post_status', 'bimverdi_foretak_pending_transition', 10, 3);

/**
 * Reagerer på status-overganger på foretak-CPT.
 *
 * @param string  $new_status
 * @param string  $old_status
 * @param WP_Post $post
 */
function bimverdi_foretak_pending_transition($new_status, $old_status, $post) {
    if (!($post instanceof WP_Post)) {
        return;
    }

    $cpt = defined('BV_CPT_COMPANY') ? BV_CPT_COMPANY : 'foretak';
    if ($post->post_type !== $cpt) {
        return;
    }

    // Vi reagerer kun på pending-overganger — vanlig admin-redigering av
    // allerede-publiserte foretak skal ikke trigge bruker-aktivering eller
    // avvisning på nytt.
    if ($old_status !== 'pending') {
        return;
    }

    if ($new_status === 'publish') {
        bimverdi_foretak_pending_approve($post);
    } elseif ($new_status === 'trash') {
        bimverdi_foretak_pending_reject($post);
    }
}

/**
 * Godkjenn pending-foretak: aktiver bruker, send velkomst-e-post.
 *
 * @param WP_Post $foretak
 */
function bimverdi_foretak_pending_approve(WP_Post $foretak) {
    $foretak_id = $foretak->ID;

    // Hent registrerende bruker fra ACF hovedkontaktperson (satt av form-
    // handler ved registrering). Fallback til post_author.
    $hovedkontakt_id = function_exists('get_field')
        ? (int) get_field('hovedkontaktperson', $foretak_id)
        : 0;
    if (!$hovedkontakt_id) {
        $hovedkontakt_id = (int) $foretak->post_author;
    }
    $user = $hovedkontakt_id ? get_userdata($hovedkontakt_id) : null;
    if (!$user) {
        error_log("BIMVerdi: pending-godkjenning av foretak $foretak_id feilet — fant ikke hovedkontakt-user");
        return;
    }

    $deltakertype = (string) get_post_meta($foretak_id, '_bv_pending_deltakertype', true);
    if (!in_array($deltakertype, ['gratis', 'deltaker', 'prosjektdeltaker', 'partner'], true)) {
        // Manuelt opprettet foretak via wp-admin (ikke pending-flyt) — skip
        // bruker-aktivering siden vi ikke vet hvilken rolle bruker skal ha.
        return;
    }

    // 1. Koble bruker til foretak via user-meta
    update_user_meta($user->ID, 'bimverdi_company_id', $foretak_id);
    update_user_meta($user->ID, 'bim_verdi_company_id', $foretak_id);
    update_user_meta($user->ID, 'bimverdi_account_type', 'foretak');
    if (function_exists('update_field')) {
        update_field('tilknyttet_foretak', $foretak_id, 'user_' . $user->ID);
    }

    // 2. Slett bruker_foretak-meta (BRREG-search-state)
    delete_user_meta($user->ID, 'bimverdi_bruker_foretak_orgnr');
    delete_user_meta($user->ID, 'bimverdi_bruker_foretak_navn');
    delete_user_meta($user->ID, 'bimverdi_bruker_foretak_source');

    // 3. Endre rolle for paid-tier (gratis = behold subscriber/medlem)
    if ($deltakertype !== 'gratis') {
        $basic_roles = ['subscriber', 'medlem', 'tilleggskontakt'];
        if (!empty(array_intersect($basic_roles, $user->roles))) {
            $user_obj = new WP_User($user->ID);
            $user_obj->set_role($deltakertype);
        }
    }

    // 4. Fjern pending-meta (rydd opp — registreringen er ferdig)
    delete_post_meta($foretak_id, '_bv_pending_deltakertype');

    // 5. Send velkomst-e-post til bruker
    $bv_rolle_map = [
        'gratis' => 'Gratis brukerforetak',
        'deltaker' => 'Deltaker',
        'prosjektdeltaker' => 'Prosjektdeltaker',
        'partner' => 'Partner',
    ];
    $personer_map = ['gratis' => 1, 'deltaker' => 3, 'prosjektdeltaker' => 4, 'partner' => 5];
    $organisasjonsnummer = function_exists('get_field')
        ? (string) get_field('organisasjonsnummer', $foretak_id)
        : '';

    if ($deltakertype === 'gratis') {
        $email_subject = 'Foretaket ditt er godkjent — ' . $foretak->post_title;
        $email_body = bimverdi_foretak_pending_approved_email_html(
            $foretak->post_title,
            $organisasjonsnummer,
            $bv_rolle_map[$deltakertype],
            $user->display_name,
            $personer_map[$deltakertype]
        );
    } else {
        // Paid-tier: bruk eksisterende velkomst-e-post-mal som inkluderer
        // faktura-info og videre flyt.
        $email_subject = 'Velkommen til BIM Verdi — ' . $foretak->post_title . ' er godkjent';
        if (function_exists('bimverdi_get_foretak_registered_email_html')) {
            $email_body = bimverdi_get_foretak_registered_email_html(
                $foretak->post_title,
                $organisasjonsnummer,
                $bv_rolle_map[$deltakertype],
                $user->display_name,
                $personer_map[$deltakertype]
            );
        } else {
            $email_body = bimverdi_foretak_pending_approved_email_html(
                $foretak->post_title,
                $organisasjonsnummer,
                $bv_rolle_map[$deltakertype],
                $user->display_name,
                $personer_map[$deltakertype]
            );
        }
    }

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $sent = wp_mail($user->user_email, $email_subject, $email_body, $headers);
    if (!$sent) {
        error_log("BIMVerdi: failed to send approval email for foretak $foretak_id to {$user->user_email}");
    }

    error_log("BIMVerdi: foretak $foretak_id approved — user {$user->ID} activated as $deltakertype");
}

/**
 * Avvis pending-foretak: send avvisning-e-post til bruker.
 * Foretak er allerede flyttet til trash av WP — vi rydder kun opp og
 * varsler. Bruker er ikke aktivert (siden registrering aldri kom forbi
 * pending), så vi trenger ikke å reverse meta eller rolle.
 *
 * @param WP_Post $foretak
 */
function bimverdi_foretak_pending_reject(WP_Post $foretak) {
    $foretak_id = $foretak->ID;

    $hovedkontakt_id = function_exists('get_field')
        ? (int) get_field('hovedkontaktperson', $foretak_id)
        : 0;
    if (!$hovedkontakt_id) {
        $hovedkontakt_id = (int) $foretak->post_author;
    }
    $user = $hovedkontakt_id ? get_userdata($hovedkontakt_id) : null;
    if (!$user) {
        return;
    }

    $deltakertype = (string) get_post_meta($foretak_id, '_bv_pending_deltakertype', true);
    if (!$deltakertype) {
        // Ikke en pending-flyt-foretak — sannsynligvis manuelt opprettet
        // og slettet. Skip avvisnings-e-post.
        return;
    }

    $email_subject = 'Foretaks-registrering avvist — ' . $foretak->post_title;
    $email_body = bimverdi_foretak_pending_rejected_email_html(
        $foretak->post_title,
        $user->display_name
    );

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $sent = wp_mail($user->user_email, $email_subject, $email_body, $headers);
    if (!$sent) {
        error_log("BIMVerdi: failed to send rejection email for foretak $foretak_id to {$user->user_email}");
    }

    error_log("BIMVerdi: foretak $foretak_id rejected — user {$user->ID} notified");
}

/**
 * E-post-mal for godkjent registrering (gratis-tier eller fallback).
 */
function bimverdi_foretak_pending_approved_email_html($foretak_navn, $orgnr, $rolle_label, $user_navn, $personer) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="nb-NO"><body style="font-family: -apple-system, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #1A1A1A;">
        <h1 style="color: #FF8B5E; font-size: 24px; margin-bottom: 16px;">Foretaket ditt er godkjent!</h1>

        <p>Hei <?php echo esc_html($user_navn); ?>,</p>

        <p>Din registrering av <strong><?php echo esc_html($foretak_navn); ?></strong> er nå godkjent.</p>

        <table style="border-collapse:collapse; font-size:14px; margin: 16px 0;">
            <tr><td style="padding:4px 12px 4px 0; color:#666;">Foretak</td><td><strong><?php echo esc_html($foretak_navn); ?></strong></td></tr>
            <?php if ($orgnr): ?>
            <tr><td style="padding:4px 12px 4px 0; color:#666;">Org.nr</td><td><?php echo esc_html($orgnr); ?></td></tr>
            <?php endif; ?>
            <tr><td style="padding:4px 12px 4px 0; color:#666;">Deltakernivå</td><td><strong><?php echo esc_html($rolle_label); ?></strong></td></tr>
            <tr><td style="padding:4px 12px 4px 0; color:#666;">Inkluderte personer</td><td><?php echo (int) $personer; ?></td></tr>
        </table>

        <p style="margin-top: 24px;">
            <a href="<?php echo esc_url(home_url('/min-side/')); ?>" style="background: #FF8B5E; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: 600;">
                Gå til Min side
            </a>
        </p>

        <p style="font-size: 13px; color: #666; margin-top: 32px;">
            Har du spørsmål? Kontakt oss på <a href="mailto:post@bimverdi.no">post@bimverdi.no</a>.
        </p>
    </body></html>
    <?php
    return (string) ob_get_clean();
}

/**
 * E-post-mal for avvist registrering.
 */
function bimverdi_foretak_pending_rejected_email_html($foretak_navn, $user_navn) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="nb-NO"><body style="font-family: -apple-system, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #1A1A1A;">
        <h1 style="font-size: 22px; margin-bottom: 16px;">Registrering ikke godkjent</h1>

        <p>Hei <?php echo esc_html($user_navn); ?>,</p>

        <p>Vi har dessverre ikke godkjent din registrering av <strong><?php echo esc_html($foretak_navn); ?></strong> i BIM Verdi.</p>

        <p>Det kan være flere grunner — for eksempel at vi trenger å verifisere at du har fullmakt til å registrere foretaket, eller at e-postadressen din ikke matcher foretaket. Hvis du tror dette er en feil, ta gjerne kontakt så ser vi på det.</p>

        <p style="margin-top: 24px;">
            <a href="mailto:post@bimverdi.no" style="background: #FF8B5E; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: 600;">
                Kontakt BIM Verdi
            </a>
        </p>

        <p style="font-size: 13px; color: #666; margin-top: 32px;">
            Med vennlig hilsen,<br>
            BIM Verdi
        </p>
    </body></html>
    <?php
    return (string) ob_get_clean();
}
