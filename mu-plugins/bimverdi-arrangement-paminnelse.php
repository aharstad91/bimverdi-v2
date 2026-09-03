<?php
/**
 * Plugin Name: BIM Verdi - Påminnelse dagen før arrangement
 * Description: Sender e-post kl. 10:00 dagen før til bekreftede påmeldte. Låst til allowlist til gaten åpnes eksplisitt.
 * Version: 1.0.0
 *
 * Bård, Trello #347 punkt 4, bekreftet muntlig 03.09.2026: «vi hadde et
 * arrangement hvor folk ikke fikk påminnelse». Påmeldte skal varsles kl. 10
 * dagen før.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * SIKKERHETSGATE — LES DETTE FØR DU SLÅR PÅ
 * ═══════════════════════════════════════════════════════════════════════════
 * Denne utsendingen går én-til-mange til folk som ikke er nevnt ved navn, og
 * den utløses av en cron uten at et menneske trykker på noe. Den er derfor
 * FAIL-CLOSED: uten et eksplisitt `define('BIMVERDI_PAMINNELSE_APEN', true)`
 * i wp-config går ALT til allowlisten (andreas@aharstad.no), og de ekte
 * påmeldte røres ikke.
 *
 * Merk at gaten IKKE er miljøstyrt, slik den er i
 * bimverdi-arrangement-avlyst.php. Avlyst-varselet utløses av at et menneske
 * avlyser et arrangement i admin; dette utløses av en klokke. Da skal prod
 * ikke være «live» bare fordi den er prod — Bård skal ha sett en e-post og
 * gitt go først, og det gjøres ved å legge inn define-en på serveren.
 * wp-config følger ikke autodeploy, så det er et bevisst manuelt steg.
 *
 * Slik åpnes den, når Bård har gitt go:
 *   define('BIMVERDI_PAMINNELSE_APEN', true);   // i wp-config.php på prod
 * Nød-stenging uten å redigere wp-config:
 *   add_filter('bimverdi_paminnelse_gate_apen', '__return_false');
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * CRON PÅ PROD: WP-cron er trafikkdrevet (ingen DISABLE_WP_CRON, ingen
 * system-crontab). «Kl. 10» blir i praksis «første forespørsel etter 10:00».
 * På en side med daglig trafikk betyr det noen minutter over. Sagt til Bård.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

const BIMVERDI_PAMINNELSE_HOOK      = 'bimverdi_arrangement_paminnelse';
const BIMVERDI_PAMINNELSE_META_SENT = '_bv_paminnelse_sendt';
const BIMVERDI_PAMINNELSE_META_LOGG = '_bv_paminnelse_logg';
const BIMVERDI_PAMINNELSE_MAKS      = 300;

// =============================================================================
// Gate og allowlist
// =============================================================================

/**
 * Adresser som mottar utsendingen så lenge gaten er lukket.
 *
 * @return string[] små bokstaver
 */
function bimverdi_paminnelse_allowlist() {
    $liste = apply_filters('bimverdi_paminnelse_allowlist', array('andreas@aharstad.no'));
    $liste = array_map(function ($e) {
        return strtolower(trim((string) $e));
    }, (array) $liste);

    return array_values(array_unique(array_filter($liste, 'is_email')));
}

/**
 * Er gaten åpnet? Fail-closed: kun eksplisitt boolsk true i konstanten (eller
 * via filteret) åpner. Udefinert, 1, '1', 'ja' → fortsatt låst.
 *
 * @return bool
 */
function bimverdi_paminnelse_gate_apen() {
    $apen = defined('BIMVERDI_PAMINNELSE_APEN') && true === BIMVERDI_PAMINNELSE_APEN;

    return true === apply_filters('bimverdi_paminnelse_gate_apen', $apen);
}

// =============================================================================
// Planlegging
// =============================================================================

/**
 * Planlegg hooken til neste kl. 10:00 norsk tid, og hold den der.
 *
 * wp_schedule_event med 'daily' regner 24 timer fra første kjøring, så
 * starttidspunktet må treffe 10:00 lokalt. wp_timezone() gir sidens
 * tidssone (Europe/Oslo), ikke serverens — bruker vi strtotime() direkte
 * havner vi på serverens UTC og sender kl. 12.
 *
 * Sommer-/vintertid: 'daily' er et fast 24-timers intervall, så etter en
 * tidsomstilling glir kjøringen én time. Vi retter opp ved å reschedulere
 * når neste kjøring ikke lenger står på 10:00 lokalt.
 */
add_action('init', function () {
    $neste = wp_next_scheduled(BIMVERDI_PAMINNELSE_HOOK);

    if ($neste) {
        $lokal_time = (int) wp_date('G', $neste);
        if ($lokal_time === 10) {
            return;
        }
        // Tidsomstilling har flyttet kjøringen — legg den tilbake på 10:00.
        wp_unschedule_event($neste, BIMVERDI_PAMINNELSE_HOOK);
    }

    $start = new DateTimeImmutable('tomorrow 10:00', wp_timezone());
    wp_schedule_event($start->getTimestamp(), 'daily', BIMVERDI_PAMINNELSE_HOOK);
});

add_action(BIMVERDI_PAMINNELSE_HOOK, 'bimverdi_paminnelse_kjor');

// =============================================================================
// Kjøring
// =============================================================================

/**
 * Finn arrangementene som går i morgen, og send påminnelse for hvert.
 *
 * @return array{arrangementer: int, sendt: int, hoppet_over: int}
 */
function bimverdi_paminnelse_kjor() {
    $i_morgen = (new DateTimeImmutable('tomorrow', wp_timezone()))->format('Ymd');

    // arrangement_dato lagres som Ymd-streng (f.eks. 20260623), så en
    // likhetssammenligning på den strengen er både riktig og indeksvennlig.
    // 'type' => 'DATE' ville tvunget MySQL til å tolke '20260623' som dato,
    // noe den gjør inkonsistent på tvers av versjoner.
    $arrangementer = get_posts(array(
        'post_type'        => 'arrangement',
        'post_status'      => 'publish',
        'posts_per_page'   => -1,
        'fields'           => 'ids',
        'no_found_rows'    => true,
        'meta_query'       => array(
            array(
                'key'     => 'arrangement_dato',
                'value'   => $i_morgen,
                'compare' => '=',
            ),
        ),
    ));

    $sendt_totalt = 0;
    $hoppet_over  = 0;

    foreach ($arrangementer as $arrangement_id) {
        $res = bimverdi_paminnelse_send($arrangement_id, $i_morgen);
        if ($res['hoppet_over']) {
            $hoppet_over++;
            continue;
        }
        $sendt_totalt += $res['sendt'];
    }

    if ($arrangementer) {
        error_log(sprintf(
            '[bv-paminnelse] Kjoert for %s: %d arrangement, %d e-post sendt, %d hoppet over. Gate=%s',
            $i_morgen,
            count($arrangementer),
            $sendt_totalt,
            $hoppet_over,
            bimverdi_paminnelse_gate_apen() ? 'AAPEN' : 'LUKKET'
        ));
    }

    return array(
        'arrangementer' => count($arrangementer),
        'sendt'         => $sendt_totalt,
        'hoppet_over'   => $hoppet_over,
    );
}

/**
 * Send påminnelse for ett arrangement.
 *
 * @param int    $arrangement_id
 * @param string $dato_ymd       Datoen påminnelsen gjelder, Ymd.
 * @return array{sendt: int, hoppet_over: bool, aarsak: string, ekte_antall: int}
 */
function bimverdi_paminnelse_send($arrangement_id, $dato_ymd = '') {
    $arrangement_id = (int) $arrangement_id;
    $svar = array('sendt' => 0, 'hoppet_over' => true, 'aarsak' => '', 'ekte_antall' => 0);

    $post = get_post($arrangement_id);
    if (!$post || $post->post_type !== 'arrangement' || $post->post_status !== 'publish') {
        $svar['aarsak'] = 'ukjent eller upublisert arrangement';
        return $svar;
    }

    if ($dato_ymd === '') {
        $dato_ymd = (string) get_post_meta($arrangement_id, 'arrangement_dato', true);
    }

    // Avlyst → ingen påminnelse. Deltakerne har fått avlyst-varselet i stedet.
    if (get_post_meta($arrangement_id, 'arrangement_status', true) === 'avlyst') {
        $svar['aarsak'] = 'avlyst';
        return $svar;
    }

    // IDEMPOTENS: metaverdien er datoen påminnelsen gjaldt, ikke bare et flagg.
    // Da blokkerer den ikke et arrangement som flyttes til en ny dato.
    if ((string) get_post_meta($arrangement_id, BIMVERDI_PAMINNELSE_META_SENT, true) === $dato_ymd) {
        $svar['aarsak'] = 'allerede sendt for ' . $dato_ymd;
        return $svar;
    }

    $pameldte            = bimverdi_paminnelse_pameldte($arrangement_id);
    $svar['ekte_antall'] = count($pameldte);
    $gate_apen           = bimverdi_paminnelse_gate_apen();

    if (!$pameldte) {
        // Ingen påmeldte: ikke sett sendt-merket. Melder noen seg på senere
        // i dag, skal de fortsatt kunne få påminnelsen ved neste kjøring.
        $svar['aarsak'] = 'ingen bekreftede paameldte';
        return $svar;
    }

    if ($gate_apen) {
        $mottakere = $pameldte;
    } else {
        $mottakere = array();
        foreach (bimverdi_paminnelse_allowlist() as $adresse) {
            $mottakere[] = array('user_id' => 0, 'name' => '', 'email' => $adresse);
        }
        // Fail-closed: lukket gate uten gyldig allowlist skal IKKE rapportere
        // suksess — da maskeres en feilkonfigurasjon.
        if (!$mottakere) {
            error_log('[bv-paminnelse] AVBRUTT: gate lukket og allowlist tom (arrangement ' . $arrangement_id . ')');
            $svar['aarsak'] = 'tom allowlist';
            return $svar;
        }
    }

    if (count($mottakere) > BIMVERDI_PAMINNELSE_MAKS) {
        error_log(sprintf(
            '[bv-paminnelse] Arrangement %d har %d mottakere, taket er %d. Kutter.',
            $arrangement_id, count($mottakere), BIMVERDI_PAMINNELSE_MAKS
        ));
        $mottakere = array_slice($mottakere, 0, BIMVERDI_PAMINNELSE_MAKS);
    }

    // Merket settes FØR utsendingen. To samtidige cron-kjøringer kan ellers
    // begge se «ikke sendt» og dobbeltsende. Heller miste en påminnelse enn
    // å sende den to ganger.
    update_post_meta($arrangement_id, BIMVERDI_PAMINNELSE_META_SENT, $dato_ymd);

    $emne = 'Påminnelse: ' . bimverdi_paminnelse_ren_tittel($arrangement_id) . ' i morgen';
    $tid  = bimverdi_paminnelse_tid($arrangement_id);
    if ($tid) {
        $emne .= ' kl. ' . $tid;
    }

    $headers = array('Content-Type: text/html; charset=UTF-8');
    $sendt   = 0;
    $feilet  = 0;

    foreach ($mottakere as $m) {
        if (!is_email($m['email'])) {
            continue;
        }

        // SISTE SKANSE: hard allowlist-sjekk rett før wp_mail. Skulle en feil
        // over her slippe gjennom en ekte deltaker mens gaten er lukket,
        // stoppes den her.
        if (!$gate_apen && !in_array(strtolower($m['email']), bimverdi_paminnelse_allowlist(), true)) {
            error_log('[bv-paminnelse] skip (gate lukket): ' . $m['email'] . ' arrangement ' . $arrangement_id);
            continue;
        }

        $body = bimverdi_paminnelse_html($arrangement_id, $m['name'], !$gate_apen, $svar['ekte_antall']);
        if (wp_mail($m['email'], $emne, $body, $headers)) {
            $sendt++;
        } else {
            $feilet++;
            error_log(sprintf('[bv-paminnelse] wp_mail FEILET for %s (arrangement %d)', $m['email'], $arrangement_id));
        }
    }

    $logg = get_post_meta($arrangement_id, BIMVERDI_PAMINNELSE_META_LOGG, true);
    $logg = is_array($logg) ? $logg : array();
    $logg[] = array(
        'tidspunkt'   => current_time('mysql'),
        'gjelder'     => $dato_ymd,
        'sendt'       => $sendt,
        'feilet'      => $feilet,
        'ekte_antall' => $svar['ekte_antall'],
        'gate'        => $gate_apen ? 'apen' : 'lukket',
    );
    update_post_meta($arrangement_id, BIMVERDI_PAMINNELSE_META_LOGG, array_slice($logg, -10));

    $svar['sendt']       = $sendt;
    $svar['hoppet_over'] = false;
    $svar['aarsak']      = '';

    return $svar;
}

// =============================================================================
// Mottakere
// =============================================================================

/**
 * Bekreftede påmeldte, deduplisert på e-post.
 *
 * Samme oppslag som bimverdi_avlyst_get_participants(): begge ACF-feltnavnene
 * må dekkes (arrangement/pamelding_arrangement, bruker/pamelding_bruker) fordi
 * gamle og nye påmeldinger bruker hver sin. Vi kaller ikke avlyst-funksjonen
 * direkte — de to pluginene skal kunne endres uavhengig, og en delt hjelper
 * hører hjemme i en egen opprydding, ikke i denne leveransen.
 *
 * @param int $arrangement_id
 * @return array[] liste med ['user_id','name','email']
 */
function bimverdi_paminnelse_pameldte($arrangement_id) {
    $arrangement_id = (int) $arrangement_id;
    if (!$arrangement_id) {
        return array();
    }

    $reg_ids = get_posts(array(
        'post_type'      => 'pamelding',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'relation' => 'OR',
                array('key' => 'arrangement', 'value' => $arrangement_id),
                array('key' => 'pamelding_arrangement', 'value' => $arrangement_id),
            ),
            // Kun 'bekreftet'. Venteliste har ingen plass å møte opp på, og
            // avmeldte skal ikke minnes om noe de har meldt seg av.
            array('key' => 'pamelding_status', 'value' => 'bekreftet'),
        ),
    ));

    $pameldte = array();
    $sett     = array();

    foreach ($reg_ids as $reg_id) {
        $user_id = get_field('bruker', $reg_id);
        if (!$user_id) {
            $user_id = get_field('pamelding_bruker', $reg_id);
        }
        if (is_array($user_id)) {
            $user_id = $user_id['ID'] ?? 0;
        }
        $user_id = (int) $user_id;
        if (!$user_id) {
            continue;
        }

        $bruker = get_userdata($user_id);
        if (!$bruker || !is_email($bruker->user_email)) {
            continue;
        }

        $nokkel = strtolower($bruker->user_email);
        if (isset($sett[$nokkel])) {
            continue;
        }
        $sett[$nokkel] = true;

        $pameldte[] = array(
            'user_id' => $user_id,
            'name'    => $bruker->display_name,
            'email'   => $bruker->user_email,
        );
    }

    return $pameldte;
}

// =============================================================================
// Innhold
// =============================================================================

/**
 * Tittel som ren tekst, trygg i et emnefelt.
 *
 * wptexturize gjør ' til &#8217;, så entitetene må dekodes for at emnet ikke
 * skal vise rå «&#8217;». CR/LF strippes etter dekodingen som egen barriere
 * mot header-injection — samme grep som avlyst-varselet.
 *
 * @param int $arrangement_id
 * @return string
 */
function bimverdi_paminnelse_ren_tittel($arrangement_id) {
    $tittel = html_entity_decode(get_the_title($arrangement_id), ENT_QUOTES, 'UTF-8');

    return trim(preg_replace('/[\r\n]+/', ' ', $tittel));
}

/**
 * Starttidspunkt som HH:MM, eller tom streng.
 *
 * @param int $arrangement_id
 * @return string
 */
function bimverdi_paminnelse_tid($arrangement_id) {
    $tid = (string) get_post_meta($arrangement_id, 'tidspunkt_start', true);
    if ($tid === '') {
        return '';
    }

    // Lagret som H:i:s — vis H:i.
    return substr($tid, 0, 5);
}

/**
 * Dato på norsk: «fredag 4. september».
 *
 * Sidens locale er en_US (verifisert 03.09.2026), så wp_date('l j. F') gir
 * «Friday 4. September» — engelsk ukedag og måned midt i en norsk setning.
 * Vi slår derfor opp navnene selv, slik single-foretak.php:720 allerede gjør,
 * i stedet for å endre sidens locale (som ville flyttet alle datoer overalt).
 *
 * MERK: samme engelske datoer vises i dag på arrangementssider
 * (single-arrangement.php:96) og i avlyst-varselet. Egen sak.
 *
 * @param string $ymd Dato som Ymd.
 * @return string Tom streng hvis datoen ikke kan tolkes.
 */
function bimverdi_paminnelse_dato_norsk($ymd) {
    $d = DateTime::createFromFormat('Ymd', (string) $ymd);
    if (!$d) {
        return '';
    }

    $ukedager = array('søndag', 'mandag', 'tirsdag', 'onsdag', 'torsdag', 'fredag', 'lørdag');
    $maneder  = array(
        'januar', 'februar', 'mars', 'april', 'mai', 'juni',
        'juli', 'august', 'september', 'oktober', 'november', 'desember',
    );

    return sprintf(
        '%s %d. %s',
        $ukedager[(int) $d->format('w')],
        (int) $d->format('j'),
        $maneder[(int) $d->format('n') - 1]
    );
}

/**
 * Bygg HTML-innholdet i påminnelsen.
 *
 * @param int    $arrangement_id
 * @param string $navn        Mottakerens navn (kan være tomt).
 * @param bool   $er_testkopi Sant når gaten er lukket → vis test-banner.
 * @param int    $ekte_antall Antall ekte påmeldte (til test-banneret).
 * @return string
 */
function bimverdi_paminnelse_html($arrangement_id, $navn, $er_testkopi, $ekte_antall) {
    $tittel = bimverdi_paminnelse_ren_tittel($arrangement_id);
    $url    = get_permalink($arrangement_id);
    $tid    = bimverdi_paminnelse_tid($arrangement_id);

    $dato_raw = (string) get_post_meta($arrangement_id, 'arrangement_dato', true);
    $dato_str = bimverdi_paminnelse_dato_norsk($dato_raw);

    $type       = (string) get_post_meta($arrangement_id, 'arrangement_type', true);
    $adresse    = (string) get_post_meta($arrangement_id, 'sted_adresse', true);
    $stedsnavn  = (string) get_post_meta($arrangement_id, 'sted_navn', true);
    $online     = (string) get_post_meta($arrangement_id, 'online_lenke', true);
    $er_digitalt = in_array($type, array('digitalt', 'hybrid'), true);

    $sted_linje = '';
    if ($er_digitalt && $online !== '') {
        $sted_linje = 'Digitalt — <a href="' . esc_url($online) . '" style="color:#FF8B5E;">lenke til møtet</a>';
    } elseif ($er_digitalt) {
        // Hybrid uten lenke skal fortsatt nevne adressen om den finnes.
        $sted_linje = $adresse !== ''
            ? 'Digitalt og på ' . esc_html(trim($stedsnavn . ' ' . $adresse))
            : 'Digitalt — lenken kommer fra arrangøren';
    } elseif ($adresse !== '' || $stedsnavn !== '') {
        $sted_linje = esc_html(trim($stedsnavn . ($stedsnavn && $adresse ? ', ' : '') . $adresse));
    }

    $ics = function_exists('bimverdi_get_ics_url') ? bimverdi_get_ics_url($arrangement_id) : '';
    $kan_melde_av = function_exists('bimverdi_can_cancel_registration')
        && bimverdi_can_cancel_registration($arrangement_id);

    $hilsen = $navn !== '' ? ('Hei ' . esc_html($navn) . ',') : 'Hei,';

    $banner = '';
    if ($er_testkopi) {
        $banner =
            '<div style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px;padding:12px 16px;margin:0 0 20px;font-size:13px;color:#92400E;">'
            . '<strong>Testkopi — sikkerhetsgaten er lukket.</strong> Denne påminnelsen er kun sendt til deg. '
            . (int) $ekte_antall . ' faktiske påmeldte ble IKKE varslet.'
            . '</div>';
    }

    ob_start();
    ?>
    <div style="font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;max-width:560px;margin:0 auto;color:#1A1A1A;line-height:1.6;">
        <?php echo $banner; ?>
        <p style="font-size:15px;"><?php echo $hilsen; ?></p>
        <p style="font-size:15px;">
            Dette er en påminnelse om at du er påmeldt
            <strong><?php echo esc_html($tittel); ?></strong> i morgen<?php
            echo $dato_str !== '' ? ', ' . esc_html($dato_str) : '';
            echo $tid !== '' ? ' kl. ' . esc_html($tid) : '';
            ?>.
        </p>

        <?php if ($sted_linje !== '') : ?>
        <p style="font-size:15px;margin:0 0 4px;"><strong>Sted:</strong> <?php echo $sted_linje; ?></p>
        <?php endif; ?>

        <p style="font-size:15px;margin-top:24px;">
            <a href="<?php echo esc_url($url); ?>" style="display:inline-block;background:#FF8B5E;color:#fff;text-decoration:none;padding:10px 20px;border-radius:8px;font-weight:600;">
                Se arrangementet
            </a>
        </p>

        <?php if ($ics !== '') : ?>
        <p style="font-size:14px;">
            <a href="<?php echo esc_url($ics); ?>" style="color:#FF8B5E;">Legg i kalenderen</a>
        </p>
        <?php endif; ?>

        <?php if ($kan_melde_av) : ?>
        <p style="font-size:14px;color:#5A5A5A;">
            Passer det ikke likevel? Du kan melde deg av under
            <a href="<?php echo esc_url(home_url('/min-side/arrangementer/')); ?>" style="color:#FF8B5E;">Mine arrangementer</a>.
        </p>
        <?php endif; ?>

        <p style="font-size:15px;margin-top:24px;">Vi ses!<br>BIM Verdi</p>
        <p style="font-size:12px;color:#9B9B9B;margin-top:28px;border-top:1px solid #E8E8E8;padding-top:12px;">
            Du får denne e-posten fordi du er påmeldt arrangementet.
        </p>
    </div>
    <?php

    return trim(ob_get_clean());
}
