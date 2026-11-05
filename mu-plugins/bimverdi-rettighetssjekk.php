<?php
/**
 * BIM Verdi - Ukentlig automatisk sjekk av maskinell bruk for kunnskapskilder
 *
 * Bård 05.11.2026: alle kunnskapskilder sjekkes hver søndag kl. 07:00 (Europe/Oslo),
 * en time etter BV lenkevakt (kl. 06:00). Da treffer ikke de to sjekkene de samme
 * nettstedene samtidig, og flyttede lenker er allerede rettet av lenkevakten.
 *
 * Sjekken leser det som er maskinlesbart hos kilden:
 * - robots.txt (om AI-crawlere eller alle roboter er stengt for kildens sti)
 * - /.well-known/tdmrep.json og tdm-reservation i meta/HTTP-header (TDMRep)
 * - noai i robots-meta eller X-Robots-Tag
 * - om lenken svarer, og lisenshint på siden (CC, NLOD)
 *
 * Den leser IKKE bruksvilkår. «Uttrykkelig tillatt» og «Forbudt i bruksvilkår» settes
 * bare av et menneske, og sjekken endrer aldri disse to verdiene.
 *
 * Felt (ACF, post meta på kunnskapskilde):
 * - maskinell_bruk          oppdateres til reservert / ikke_reservert
 * - maskinell_bruk_sjekket  dato (Ymd) for siste vellykkede sjekk
 * - maskinell_bruk_funn     tekst med funnene fra siste sjekk
 * Hvis kilden ikke kan leses, beholdes forrige verdi og dato. Funnet sier hvorfor.
 *
 * Kjøring: WP-Cron i puljer på 15 kilder hvert minutt, så ingen enkeltforespørsel
 * blir for lang. Manuelt: `wp bv-rettighetssjekk` (kjører alle med en gang).
 */

if (!defined('ABSPATH')) {
    exit;
}

const BIMVERDI_RS_START  = 'bimverdi_rettighetssjekk_start';
const BIMVERDI_RS_PULJE  = 'bimverdi_rettighetssjekk_pulje';
const BIMVERDI_RS_KO     = 'bimverdi_rettighetssjekk_ko';
const BIMVERDI_RS_SIST   = 'bimverdi_rettighetssjekk_sist';
const BIMVERDI_RS_STR    = 15;
const BIMVERDI_RS_TIME   = 7;

/** AI-crawlere vi ser etter i robots.txt. */
function bimverdi_rs_roboter() {
    return array('GPTBot', 'ClaudeBot', 'anthropic-ai', 'CCBot', 'Google-Extended', 'PerplexityBot', 'Bytespider', 'Applebot-Extended', 'meta-externalagent');
}

/** Neste søndag kl. 07:00 norsk tid, som Unix-tid. */
function bimverdi_rs_neste_sondag() {
    $tz  = wp_timezone();
    $naa = new DateTimeImmutable('now', $tz);
    $t   = $naa->modify('sunday')->setTime(BIMVERDI_RS_TIME, 0);
    if ($t <= $naa) {
        $t = $t->modify('+7 days');
    }
    return $t->getTimestamp();
}

/**
 * Planlegg neste kjøring. Engangshendelse hver gang, så klokkeslettet holder over sommertid.
 * En hendelse som står på feil klokkeslett (f.eks. gamle 06:00) flyttes.
 */
add_action('init', function () {
    $neste = wp_next_scheduled(BIMVERDI_RS_START);
    if ($neste && (int) wp_date('G', $neste) !== BIMVERDI_RS_TIME) {
        wp_unschedule_event($neste, BIMVERDI_RS_START);
        $neste = false;
    }
    if (!$neste) {
        wp_schedule_single_event(bimverdi_rs_neste_sondag(), BIMVERDI_RS_START);
    }
});

add_action(BIMVERDI_RS_START, function () {
    wp_schedule_single_event(bimverdi_rs_neste_sondag(), BIMVERDI_RS_START);
    update_option(BIMVERDI_RS_KO, bimverdi_rs_alle_kilder(), false);
    bimverdi_rs_nullstill_teller();
    wp_schedule_single_event(time() + 5, BIMVERDI_RS_PULJE);
});

add_action(BIMVERDI_RS_PULJE, function () {
    $ko = get_option(BIMVERDI_RS_KO, array());
    if (empty($ko) || !is_array($ko)) {
        return;
    }
    $pulje = array_splice($ko, 0, BIMVERDI_RS_STR);
    update_option(BIMVERDI_RS_KO, $ko, false);
    foreach ($pulje as $id) {
        bimverdi_rs_tell(bimverdi_rs_sjekk_kilde((int) $id));
    }
    if (!empty($ko)) {
        wp_schedule_single_event(time() + 60, BIMVERDI_RS_PULJE);
    } else {
        delete_option(BIMVERDI_RS_KO);
        bimverdi_rs_avslutt_teller();
    }
});

function bimverdi_rs_alle_kilder() {
    return get_posts(array(
        'post_type'   => 'kunnskapskilde',
        'post_status' => array('publish', 'pending', 'draft', 'private'),
        'numberposts' => -1,
        'fields'      => 'ids',
        'orderby'     => 'ID',
        'order'       => 'ASC',
    ));
}

function bimverdi_rs_nullstill_teller() {
    update_option(BIMVERDI_RS_SIST, array('startet' => current_time('mysql'), 'ferdig' => '', 'resultat' => array()), false);
}

function bimverdi_rs_tell($resultat) {
    $s = get_option(BIMVERDI_RS_SIST, array());
    $s['resultat'][$resultat] = ($s['resultat'][$resultat] ?? 0) + 1;
    update_option(BIMVERDI_RS_SIST, $s, false);
}

function bimverdi_rs_avslutt_teller() {
    $s = get_option(BIMVERDI_RS_SIST, array());
    $s['ferdig'] = current_time('mysql');
    update_option(BIMVERDI_RS_SIST, $s, false);
}

/** Enkel HTTP-henting. Returnerer [statuskode, kropp, headere]; kode 0 ved nettverksfeil. */
function bimverdi_rs_hent($url, $maks = 400000) {
    $svar = wp_remote_get($url, array(
        'timeout'             => 15,
        'redirection'         => 5,
        'user-agent'          => 'Mozilla/5.0 (compatible; BIMVerdi-kildesjekk/1.0; +https://bimverdi.no)',
        'limit_response_size' => $maks,
    ));
    if (is_wp_error($svar)) {
        return array(0, '', array());
    }
    return array((int) wp_remote_retrieve_response_code($svar), (string) wp_remote_retrieve_body($svar), wp_remote_retrieve_headers($svar));
}

/** robots.txt og tdmrep.json per vertsnavn, mellomlagret i 6 timer. */
function bimverdi_rs_vert($base) {
    $nokkel = 'bv_rs_' . md5($base);
    $data   = get_transient($nokkel);
    if (is_array($data)) {
        return $data;
    }
    list($rkode, $robots) = bimverdi_rs_hent($base . '/robots.txt', 200000);
    if ($rkode === 200 && stripos($robots, '<html') !== false) {
        $robots = ''; // HTML-feilside i stedet for robots.txt
    }
    list($tkode, $tdm) = bimverdi_rs_hent($base . '/.well-known/tdmrep.json', 100000);
    $data = array(
        'robots_kode' => $rkode,
        'robots'      => $rkode === 200 ? $robots : '',
        'tdm'         => $tkode === 200 ? $tdm : '',
    );
    set_transient($nokkel, $data, 6 * HOUR_IN_SECONDS);
    return $data;
}

/** Del robots.txt i grupper: [['ua' => [...], 'regler' => [['disallow', '/x'], ...]], ...]. */
function bimverdi_rs_robots_grupper($tekst) {
    $grupper = array();
    $gjeldende = null;
    $sist_ua = false;
    foreach (preg_split('/\r?\n/', $tekst) as $linje) {
        $linje = trim(preg_replace('/#.*/', '', $linje));
        if ($linje === '' || !preg_match('/^([A-Za-z-]+)\s*:\s*(.*)$/', $linje, $m)) {
            continue;
        }
        $felt = strtolower($m[1]);
        $verdi = trim($m[2]);
        if ($felt === 'user-agent') {
            if ($gjeldende === null || !$sist_ua) {
                $grupper[] = array('ua' => array(), 'regler' => array());
                $gjeldende = count($grupper) - 1;
            }
            $grupper[$gjeldende]['ua'][] = strtolower($verdi);
            $sist_ua = true;
        } elseif (($felt === 'allow' || $felt === 'disallow') && $gjeldende !== null) {
            $grupper[$gjeldende]['regler'][] = array($felt, $verdi);
            $sist_ua = false;
        } else {
            $sist_ua = false;
        }
    }
    return $grupper;
}

/**
 * Er stien stengt for roboten? Returnerer [stengt, egen_gruppe].
 * egen_gruppe = true når robots.txt har en gruppe for akkurat denne roboten.
 */
function bimverdi_rs_stengt($grupper, $robot, $sti) {
    $robot = strtolower($robot);
    $valgt = array();
    foreach ($grupper as $g) {
        foreach ($g['ua'] as $ua) {
            $navn = preg_replace('#[/;\s].*$#', '', $ua);
            if ($navn !== '*' && $navn === $robot) {
                $valgt[] = $g;
                break;
            }
        }
    }
    $egen = !empty($valgt);
    if (!$egen) {
        foreach ($grupper as $g) {
            if (in_array('*', $g['ua'], true)) {
                $valgt[] = $g;
            }
        }
    }
    $beste = '';
    $lengde = -1;
    foreach ($valgt as $g) {
        foreach ($g['regler'] as $r) {
            list($type, $monster) = $r;
            if ($type === 'disallow' && $monster === '') {
                continue;
            }
            $slutt = '';
            if (substr($monster, -1) === '$') {
                $slutt = '$';
                $monster = substr($monster, 0, -1);
            }
            $regex = '#^' . implode('.*', array_map(function ($del) { return preg_quote($del, '#'); }, explode('*', $monster))) . $slutt . '#';
            if (!preg_match($regex, $sti)) {
                continue;
            }
            $l = strlen($r[1]);
            if ($l > $lengde || ($l === $lengde && $type === 'allow')) {
                $lengde = $l;
                $beste = $type;
            }
        }
    }
    return array($beste === 'disallow', $egen);
}

/**
 * Sjekk én kilde og lagre resultatet.
 * Returnerer reservert / ikke_reservert / ikke_lest / mangler_lenke.
 */
function bimverdi_rs_sjekk_kilde($id) {
    $url = trim((string) get_post_meta($id, 'ekstern_lenke', true));
    $dato = wp_date('d.m.Y');
    if (!preg_match('#^(https?://[^/]+)(/[^\#]*)?#i', $url, $m)) {
        update_field('maskinell_bruk_funn', "Automatisk sjekk $dato: kilden mangler gyldig lenke.", $id);
        return 'mangler_lenke';
    }
    $base = $m[1];
    $sti  = !empty($m[2]) ? $m[2] : '/';
    $vert = bimverdi_rs_vert($base);
    list($skode, $html, $hdr) = bimverdi_rs_hent($url);

    $funn = array();
    $robots_ok = in_array($vert['robots_kode'], array(200, 404, 410), true);
    $side_ok   = $skode >= 200 && $skode < 400;

    if (!$robots_ok || !$side_ok) {
        $grunn = array();
        if (!$side_ok) {
            $grunn[] = $skode === 0 ? 'siden svarte ikke' : ($skode === 404 ? 'lenken svarer 404 (finnes ikke)' : "siden svarte $skode");
        }
        if (!$robots_ok) {
            $grunn[] = $vert['robots_kode'] === 0 ? 'robots.txt svarte ikke' : 'robots.txt svarte ' . $vert['robots_kode'];
        }
        update_field('maskinell_bruk_funn', "Automatisk sjekk $dato: kunne ikke lese kilden (" . implode(', ', $grunn) . '). Forrige resultat er beholdt.', $id);
        return 'ikke_lest';
    }

    $grupper = bimverdi_rs_robots_grupper($vert['robots']);
    $ai = array();
    $alle = false;
    foreach (bimverdi_rs_roboter() as $robot) {
        list($stengt, $egen) = bimverdi_rs_stengt($grupper, $robot, $sti);
        if ($stengt && $egen) {
            $ai[] = $robot;
        } elseif ($stengt) {
            $alle = true;
        }
    }
    $hdr_tekst = '';
    foreach ((array) (is_object($hdr) && method_exists($hdr, 'getAll') ? $hdr->getAll() : $hdr) as $k => $v) {
        $hdr_tekst .= $k . ': ' . (is_array($v) ? implode(', ', $v) : $v) . "\n";
    }
    $tdm  = preg_match('/"tdm-reservation"\s*:\s*1/', $vert['tdm'])
        || preg_match('/<meta[^>]+name=["\']tdm-reservation["\'][^>]+content=["\']1/i', $html)
        || preg_match('/^tdm-reservation:\s*1/im', $hdr_tekst);
    $noai = preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\'][^"\']*no(?:ai|imageai)/i', $html)
        || preg_match('/^x-robots-tag:.*\bnoai\b/im', $hdr_tekst);

    if ($ai)   { $funn[] = 'robots.txt stenger: ' . implode(', ', $ai); }
    if ($alle) { $funn[] = 'robots.txt stenger alle roboter (*) for denne stien'; }
    if ($tdm)  { $funn[] = 'TDMRep: tdm-reservation=1'; }
    if ($noai) { $funn[] = 'noai-merking'; }

    $ny = ($ai || $alle || $tdm || $noai) ? 'reservert' : 'ikke_reservert';
    if ($ny === 'ikke_reservert') {
        $funn[] = $vert['robots_kode'] === 200 ? 'robots.txt uten AI-reservasjon' : 'ingen robots.txt';
    }

    $lisens = array();
    if (preg_match('#creativecommons\.org/(?:licenses|publicdomain)/([a-z-]+)#i', $html, $cc)) {
        $lisens[] = 'CC (' . $cc[1] . ')';
    }
    if (preg_match('#\bNLOD\b|data\.norge\.no/nlod#i', $html)) {
        $lisens[] = 'NLOD';
    }
    if ($lisens) {
        $funn[] = 'lisenshint på siden: ' . implode(', ', $lisens);
    }

    // Manuelle vurderinger av bruksvilkår overstyres aldri.
    $naa = (string) get_post_meta($id, 'maskinell_bruk', true);
    if (in_array($naa, array('tillatt', 'forbudt_vilkaar'), true)) {
        $funn[] = 'verdien er satt manuelt og er ikke endret';
    } else {
        update_field('maskinell_bruk', $ny, $id);
    }
    update_field('maskinell_bruk_sjekket', wp_date('Ymd'), $id);
    update_field('maskinell_bruk_funn', "Automatisk sjekk $dato: " . implode('. ', $funn) . '.', $id);
    return $ny;
}

if (defined('WP_CLI') && WP_CLI) {
    /**
     * Kjør rettighetssjekken nå for alle kunnskapskilder (eller én med --id=<id>).
     */
    WP_CLI::add_command('bv-rettighetssjekk', function ($args, $assoc) {
        $ider = !empty($assoc['id']) ? array((int) $assoc['id']) : bimverdi_rs_alle_kilder();
        bimverdi_rs_nullstill_teller();
        foreach ($ider as $id) {
            $r = bimverdi_rs_sjekk_kilde((int) $id);
            bimverdi_rs_tell($r);
            WP_CLI::log($id . "\t" . $r);
        }
        bimverdi_rs_avslutt_teller();
        $s = get_option(BIMVERDI_RS_SIST, array());
        WP_CLI::success('Ferdig: ' . wp_json_encode($s['resultat'] ?? array()));
    });
}

/**
 * Fargekode for en kunnskapskilde i katalogen (Bård 05.11.2026).
 *
 * - rod:   lenken er død (lenkevakten: brutt eller mangler lenke).
 * - gronn: lenken virker, og verken lisens eller maskinell bruk har kjente begrensninger.
 * - gul:   alt annet, dvs. noen begrensninger eller ikke ferdig avklart.
 *
 * Ukjent lisens regnes ikke som en begrensning, fordi nesten ingen kilder har lisens
 * satt ennå. Ukjent maskinell bruk eller en lenke som ikke er sjekket gir gul.
 *
 * @return array{farge:string, tekst:string, grunner:string[]}
 */
/** Er pluginen BV lenkevakt aktiv? Klassen finnes bare når pluginen er lastet. */
function bimverdi_lenkevakt_aktiv() {
    return class_exists('BV_Lenkevakt');
}

function bimverdi_kilde_fargekode($id) {
    $lenke    = trim((string) get_post_meta($id, 'ekstern_lenke', true));
    $lv_aktiv = bimverdi_lenkevakt_aktiv();
    // Når lenkevakten er av, er de lagrede resultatene foreldet og brukes ikke.
    $lv       = $lv_aktiv ? (string) get_post_meta($id, '_lenkevakt_status', true) : '';
    $maskinell = (string) get_post_meta($id, 'maskinell_bruk', true);
    $lisens   = (string) get_post_meta($id, 'bruksrettigheter', true);

    if ($lenke === '' || in_array($lv, array('brutt', 'mangler_lenke'), true)) {
        return array('farge' => 'rod', 'tekst' => 'Lenken er død', 'grunner' => array('Lenken virker ikke'));
    }

    $grunner = array();
    if (!$lv_aktiv) {
        $grunner[] = 'Lenkesjekk er ikke aktiv';
    } elseif (!in_array($lv, array('ok', 'oppdatert'), true)) {
        $grunner[] = $lv === '' ? 'Lenken er ikke sjekket ennå' : 'Lenken svarer med advarsel (f.eks. sperret eller betalingsmur)';
    }
    if (in_array($maskinell, array('reservert', 'forbudt_vilkaar'), true)) {
        $grunner[] = 'Scraping/AI-bruk er reservert eller forbudt';
    } elseif (!in_array($maskinell, array('tillatt', 'ikke_reservert'), true)) {
        $grunner[] = 'Maskinell bruk er ikke avklart';
    }
    if (in_array($lisens, array('aapen_ikke_kommers', 'aapen_ingen_bearb', 'vernet_egne_vilkaar', 'lisensiert'), true)) {
        $grunner[] = 'Lisensen begrenser bruken';
    }

    if (empty($grunner)) {
        return array('farge' => 'gronn', 'tekst' => 'Lenken virker, ingen kjente begrensninger', 'grunner' => array());
    }
    return array('farge' => 'gul', 'tekst' => 'Noen begrensninger eller ikke avklart', 'grunner' => $grunner);
}

/** Liten farget firkant med forklaring i title og for skjermlesere. */
function bimverdi_kilde_fargekode_firkant($id) {
    $k = bimverdi_kilde_fargekode($id);
    $farger = array('gronn' => '#16A34A', 'gul' => '#EAB308', 'rod' => '#DC2626');
    $tittel = $k['tekst'] . ($k['grunner'] && $k['farge'] === 'gul' ? ': ' . implode('. ', $k['grunner']) : '');
    return sprintf(
        '<span class="inline-block rounded-sm self-center" style="width:12px;height:12px;flex-shrink:0;background:%s" title="%s" role="img" aria-label="%s"></span>',
        esc_attr($farger[$k['farge']]),
        esc_attr($tittel),
        esc_attr($tittel)
    );
}

/** Forklaring av fargene, til toppen av katalogen. */
function bimverdi_kilde_fargekode_forklaring() {
    $rader = array(
        array('#16A34A', 'Lenken virker, ingen kjente begrensninger i bruk eller scraping'),
        array('#EAB308', 'Noen begrensninger (f.eks. scraping) eller ikke avklart'),
        array('#DC2626', 'Lenken er død'),
    );
    $ut = '<div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-[#57534E] mb-4">';
    foreach ($rader as $r) {
        $ut .= sprintf(
            '<span class="inline-flex items-center gap-1"><span class="inline-block rounded-sm" style="width:12px;height:12px;flex-shrink:0;background:%s" aria-hidden="true"></span>%s</span>',
            esc_attr($r[0]),
            esc_html($r[1])
        );
    }
    return $ut . '</div>';
}
