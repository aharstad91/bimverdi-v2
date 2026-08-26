<?php
/**
 * Plugin Name: BIM Verdi - Diskusjon: abonnement på tråd
 * Description: «Abonnér på aktivitet her» — varsel på ALLE nye innlegg i en tråd, ikke bare @-mentions og direkte svar. Med ekte, innloggingsfri avmeldingsvei.
 * Version: 1.0.0
 *
 * Bakgrunn: Arnstein Skinnarland etterspurte varsel på alle innlegg (vurdert og
 * nedprioritert 20.08), Bård løftet det til krav 24.08 på kort #337: «Legg inn
 * en knapp for "abonnér på aktivitet her" i samme mal, slik at de som ikke blir
 * tagget med @navn får varsling på epost? De må kunne slå av abonnementet.»
 *
 * PÅMELDINGSMODELL (besluttet m/ Andreas 26.08): opt-in, men med
 * avkrysningsboksen synlig og forhåndshuket i selve skjemaet. Ren opt-in bak en
 * egen knapp gir døde tråder — folk skriver et innlegg, går videre og får aldri
 * vite at noen svarte. Stille påmelding av alle som kommenterer er på den andre
 * siden noe Bård ikke ba om, og den varianten skaper klager. Boksen over
 * Publiser-knappen er et aktivt, informert valg i øyeblikket, uten at terskelen
 * blir så høy at ingen abonnerer. Knappen øverst i tråden finnes i tillegg, for
 * dem som vil følge med uten å skrive selv.
 *
 * TRE TILSTANDER, ikke to. «Aldri tatt stilling» må skilles fra «meldt seg av»,
 * ellers ville boksen komme forhåndshuket tilbake til noen som nettopp trykket
 * «meld meg av» i e-posten — og da er avmeldingen ikke reell. Derfor to
 * meta-nøkler: BV_ABONNENT_META (påmeldt) og BV_AVMELDT_META (eksplisitt av).
 * Begge er NON-UNIQUE meta med én rad per bruker — ikke ett serialisert array —
 * slik at to samtidige påmeldinger ikke overskriver hverandre (les-endre-skriv-
 * kappløp), og slik at av-/påmelding er én atomisk rad-operasjon.
 *
 * AVMELDING UTEN INNLOGGING: lenken i e-posten må virke rett fra postkassen, så
 * den kan ikke bygge på cookies eller nonce. I stedet HMAC-token over
 * (bruker, post, omfang) med wp_salt('auth') som nøkkel. Omfanget er med i
 * signaturen med vilje: en trådtoken kan ikke spilles av som «skru av alt».
 *
 * GET AVMELDER ALDRI. E-postklienter og sikkerhetsskannere (Outlook Safe Links,
 * antivirus-proxyer) forhåndshenter lenker i e-post; en GET-avmelding ville
 * derfor meldt folk av i det stille, uten at de selv rørte noe. GET viser en
 * bekreftelsesside, POST utfører. RFC 8058-veien (List-Unsubscribe-Post fra
 * Gmail/Apple Mail) er POST og virker derfor som ett klikk, uten mellomside.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Påmeldte brukere på en tråd. Non-unique post meta, én rad per bruker. */
const BV_ABONNENT_META = '_bv_diskusjon_abonnent';

/** Eksplisitt avmeldte. Skiller «nei takk» fra «har aldri tatt stilling». */
const BV_AVMELDT_META = '_bv_diskusjon_avmeldt';

/** Global bryter per bruker: av = ingen diskusjonsvarsler i det hele tatt. */
const BV_VARSLER_AV_META = 'bv_diskusjon_varsler_av';

/** Query-parameter som utløser avmeldingsendepunktet. */
const BV_AVMELD_PARAM = 'bv_avmeld';

/**
 * Egen gate for abonnementsvarsler, i tillegg til hovedgaten i
 * bimverdi-diskusjon-varsler.php. Grunnen til at den ikke bare arver hovedgaten:
 * hovedgaten ble åpnet på prod for mention-/svarvarsler, som har én-til-få
 * mottakere. Abonnement er én-til-mange og treffer folk som ikke er nevnt ved
 * navn — den utsendingen skal Bård se med egne øyne før den slippes løs.
 *
 * Åpnes med én linje i wp-config.php PÅ PROD, etter go:
 *     define('BIMVERDI_DISKUSJON_ABONNEMENT_APEN', true);
 *
 * Fail-closed: udefinert eller alt annet enn boolsk true = låst, og da går
 * abonnementsvarsler kun til allowlisten (andreas@aharstad.no).
 * Selve påmeldingen — knapp, avkrysning, avmelding — virker uansett; det er
 * bare utsendingen til andre enn allowlisten gaten holder igjen.
 */
function bimverdi_diskusjon_abonnement_gate_apen() {
    $apen = defined('BIMVERDI_DISKUSJON_ABONNEMENT_APEN')
        && true === BIMVERDI_DISKUSJON_ABONNEMENT_APEN;
    return true === apply_filters('bimverdi_diskusjon_abonnement_gate_apen', $apen);
}

/* -------------------------------------------------------------------------
 * Lagring
 * ---------------------------------------------------------------------- */

/**
 * Bruker-ID-ene som abonnerer på tråden.
 * @return int[]
 */
function bimverdi_diskusjon_abonnenter($post_id) {
    $rader = get_post_meta((int) $post_id, BV_ABONNENT_META, false);
    if (!is_array($rader)) {
        return [];
    }
    return array_values(array_unique(array_filter(array_map('intval', $rader))));
}

/** Abonnerer denne brukeren på tråden? */
function bimverdi_diskusjon_abonnerer($post_id, $user_id) {
    return in_array((int) $user_id, bimverdi_diskusjon_abonnenter($post_id), true);
}

/** Har brukeren eksplisitt meldt seg av denne tråden? */
function bimverdi_diskusjon_avmeldt($post_id, $user_id) {
    $rader = get_post_meta((int) $post_id, BV_AVMELDT_META, false);
    if (!is_array($rader)) {
        return false;
    }
    return in_array((int) $user_id, array_map('intval', $rader), true);
}

/**
 * Meld på. Fjerner samtidig et eventuelt eksplisitt «nei takk», slik at de to
 * tilstandene aldri kan stå samtidig.
 */
function bimverdi_diskusjon_abonner($post_id, $user_id) {
    $post_id = (int) $post_id;
    $user_id = (int) $user_id;
    if ($post_id <= 0 || $user_id <= 0) {
        return false;
    }
    delete_post_meta($post_id, BV_AVMELDT_META, $user_id);
    if (bimverdi_diskusjon_abonnerer($post_id, $user_id)) {
        return true; // Allerede påmeldt — ingen duplikatrad.
    }
    return (bool) add_post_meta($post_id, BV_ABONNENT_META, $user_id, false);
}

/** Meld av, og registrer valget som eksplisitt. */
function bimverdi_diskusjon_avmeld($post_id, $user_id) {
    $post_id = (int) $post_id;
    $user_id = (int) $user_id;
    if ($post_id <= 0 || $user_id <= 0) {
        return false;
    }
    delete_post_meta($post_id, BV_ABONNENT_META, $user_id);
    if (!bimverdi_diskusjon_avmeldt($post_id, $user_id)) {
        add_post_meta($post_id, BV_AVMELDT_META, $user_id, false);
    }
    return true;
}

/** Har brukeren slått av alle diskusjonsvarsler? */
function bimverdi_diskusjon_varsler_av_globalt($user_id) {
    return '1' === (string) get_user_meta((int) $user_id, BV_VARSLER_AV_META, true);
}

/**
 * Skal avkrysningsboksen i skjemaet stå huket av?
 * Påmeldt → ja. Eksplisitt avmeldt → nei. Har ikke tatt stilling → ja.
 */
function bimverdi_diskusjon_avkrysning_default($post_id, $user_id) {
    if (bimverdi_diskusjon_varsler_av_globalt($user_id)) {
        return false;
    }
    if (bimverdi_diskusjon_abonnerer($post_id, $user_id)) {
        return true;
    }
    return !bimverdi_diskusjon_avmeldt($post_id, $user_id);
}

/* -------------------------------------------------------------------------
 * Påmelding fra skjemaet (avkrysningsboksen over Publiser-knappen)
 * ---------------------------------------------------------------------- */

/**
 * comment_post fyrer etter at kommentaren er lagret. Kjører bevisst uansett om
 * kommentaren ble godkjent: brukerens ønske om å følge tråden er uavhengig av
 * om innlegget havnet i moderasjonskø.
 */
add_action('comment_post', function ($comment_id, $approved, $commentdata) {
    try {
        $user_id = (int) ($commentdata['user_id'] ?? 0);
        $post_id = (int) ($commentdata['comment_post_ID'] ?? 0);
        if ($user_id <= 0 || $post_id <= 0) {
            return;
        }
        if (!function_exists('bimverdi_diskusjon_aktiv') || !bimverdi_diskusjon_aktiv($post_id)) {
            return;
        }
        // Feltet finnes bare i vårt eget skjema. Uten det (API, wp-admin,
        // tredjepart) rører vi ikke abonnementet — verken på eller av.
        if (!isset($_POST['bv_abonner_sendt'])) {
            return;
        }
        if (!empty($_POST['bv_abonner'])) {
            bimverdi_diskusjon_abonner($post_id, $user_id);
        } else {
            bimverdi_diskusjon_avmeld($post_id, $user_id);
        }
    } catch (\Throwable $e) {
        // Abonnementet skal aldri velte en publisering som allerede er lagret.
        error_log(sprintf('[bv-abonnement] Feil ved skjema-påmelding (kommentar %d): %s', $comment_id, $e->getMessage()));
    }
}, 5, 3);

/* -------------------------------------------------------------------------
 * Knappen øverst i tråden (admin-post, fungerer uten JS)
 * ---------------------------------------------------------------------- */

add_action('admin_post_bv_diskusjon_abonnement', 'bimverdi_diskusjon_abonnement_toggle');
function bimverdi_diskusjon_abonnement_toggle() {
    $post_id = absint($_POST['post_id'] ?? 0);
    $user_id = get_current_user_id();

    if (!$user_id || !$post_id) {
        wp_safe_redirect(home_url('/'));
        exit;
    }
    check_admin_referer('bv_diskusjon_abonnement_' . $post_id);

    if (!function_exists('bimverdi_diskusjon_aktiv') || !bimverdi_diskusjon_aktiv($post_id)) {
        wp_safe_redirect(home_url('/'));
        exit;
    }

    $pa       = !bimverdi_diskusjon_abonnerer($post_id, $user_id);
    $kvittering = $pa ? 'pa' : 'av';

    if ($pa) {
        bimverdi_diskusjon_abonner($post_id, $user_id);
        // Knappen er et bevisst «ja». Hadde brukeren tidligere slått av ALLE
        // diskusjonsvarsler, ville knappen ellers ikke gjort noe som helst — så
        // den globale bryteren skrus på igjen. Det er en større endring enn
        // knappeteksten lover, derfor sier kvitteringen fra om det (bv_ab=pa_alle).
        if (bimverdi_diskusjon_varsler_av_globalt($user_id)) {
            delete_user_meta($user_id, BV_VARSLER_AV_META);
            $kvittering = 'pa_alle';
        }
    } else {
        bimverdi_diskusjon_avmeld($post_id, $user_id);
    }

    $retur = add_query_arg('bv_ab', $kvittering, get_permalink($post_id)) . '#diskusjon';
    wp_safe_redirect($retur);
    exit;
}

/* -------------------------------------------------------------------------
 * Avmeldingslenke i e-post: token, endepunkt og bekreftelsesside
 * ---------------------------------------------------------------------- */

/**
 * HMAC over (bruker, post, omfang). Omfanget signeres med, slik at en
 * trådtoken ikke kan gjenbrukes som «skru av alle varsler» eller omvendt.
 */
function bimverdi_diskusjon_avmeldingstoken($user_id, $post_id, $omfang) {
    return hash_hmac(
        'sha256',
        (int) $user_id . '|' . (int) $post_id . '|' . $omfang,
        wp_salt('auth')
    );
}

/**
 * @param string $omfang 'trad' = denne tråden, 'alle' = alle diskusjonsvarsler
 */
function bimverdi_diskusjon_avmeldingslenke($user_id, $post_id, $omfang = 'trad') {
    return add_query_arg([
        BV_AVMELD_PARAM => $omfang,
        'u'             => (int) $user_id,
        'p'             => (int) $post_id,
        't'             => bimverdi_diskusjon_avmeldingstoken($user_id, $post_id, $omfang),
    ], home_url('/'));
}

/**
 * Endepunktet. Hooket på init så det svarer uansett hvilken URL parameteren
 * henger på, og før WP bruker tid på å løse opp en spørring vi uansett kaster.
 */
add_action('init', function () {
    // is_string-vaktene er ikke pedanteri: ?bv_avmeld[]=trad gjør parameteren til
    // et array, og da ville sanitize_key/streng-cast kastet PHP-warning i stedet
    // for å svare pent. Alt som ikke er en streng behandles som «ikke vår URL».
    $omfang = isset($_REQUEST[BV_AVMELD_PARAM]) && is_string($_REQUEST[BV_AVMELD_PARAM])
        ? sanitize_key(wp_unslash($_REQUEST[BV_AVMELD_PARAM]))
        : '';
    if ('trad' !== $omfang && 'alle' !== $omfang) {
        return;
    }

    $user_id = absint($_REQUEST['u'] ?? 0);
    $post_id = absint($_REQUEST['p'] ?? 0);
    $token   = isset($_REQUEST['t']) && is_string($_REQUEST['t']) ? wp_unslash($_REQUEST['t']) : '';
    $fasit   = bimverdi_diskusjon_avmeldingstoken($user_id, $post_id, $omfang);

    if (!$user_id || !hash_equals($fasit, $token)) {
        bimverdi_diskusjon_avmelding_side('ugyldig', $omfang, null);
    }

    $bruker = get_userdata($user_id);
    if (!$bruker) {
        bimverdi_diskusjon_avmelding_side('ugyldig', $omfang, null);
    }

    $post = $post_id ? get_post($post_id) : null;

    // GET viser bekreftelse, POST utfører. Se filhodet: forhåndshenting av
    // lenker i e-post ville ellers meldt folk av uten at de rørte noe.
    if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET')) {
        bimverdi_diskusjon_avmelding_side('bekreft', $omfang, $post);
    }

    if ('alle' === $omfang) {
        update_user_meta($user_id, BV_VARSLER_AV_META, '1');
        error_log(sprintf('[bv-abonnement] AVMELDT ALT — bruker %d.', $user_id));
    } else {
        bimverdi_diskusjon_avmeld($post_id, $user_id);
        error_log(sprintf('[bv-abonnement] AVMELDT TRÅD — bruker %d, post %d.', $user_id, $post_id));
    }

    bimverdi_diskusjon_avmelding_side('utfort', $omfang, $post);
}, 20);

/**
 * Frittstående svarside — bevisst uten temaet. Siden treffes fra en postkasse,
 * ofte utlogget, og skal svare raskt og likt uansett hvilken URL parameteren
 * hang på. Avslutter requesten.
 *
 * @param string       $tilstand 'bekreft' | 'utfort' | 'ugyldig'
 * @param string       $omfang   'trad' | 'alle'
 * @param WP_Post|null $post
 */
function bimverdi_diskusjon_avmelding_side($tilstand, $omfang, $post) {
    $tittel = $post ? html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8') : '';

    if ('ugyldig' === $tilstand) {
        status_header(400);
        $overskrift = 'Lenken virker ikke';
        $tekst      = 'Avmeldingslenken er ugyldig eller utdatert. Du kan slå av varsler selv under diskusjonen på nettsiden, eller gi beskjed til post@bimverdi.no.';
        $knapp      = '';
    } elseif ('bekreft' === $tilstand) {
        status_header(200);
        $overskrift = 'alle' === $omfang ? 'Slå av alle diskusjonsvarsler?' : 'Slutte å følge denne diskusjonen?';
        $tekst      = 'alle' === $omfang
            ? 'Du vil ikke lenger få e-post om diskusjoner på bimverdi.no &mdash; verken når noen nevner deg med @navn, svarer deg eller skriver i en tråd du følger. Du kan slå dem på igjen når som helst under en diskusjon på nettsiden.'
            : ($tittel
                ? sprintf('Du vil ikke lenger få e-post når noen skriver et nytt innlegg i diskusjonen på &laquo;%s&raquo;. Du får fortsatt varsel hvis noen nevner deg med @navn eller svarer direkte på innlegget ditt.', esc_html($tittel))
                : 'Du vil ikke lenger få e-post når noen skriver et nytt innlegg i denne diskusjonen. Du får fortsatt varsel hvis noen nevner deg med @navn eller svarer direkte på innlegget ditt.');
        // Skjemaet poster tilbake til nøyaktig samme URL — tokenet er
        // legitimasjonen, så ingen nonce (avsenderen er ikke innlogget).
        $knapp = sprintf(
            '<form method="post" action="%s"><button type="submit">%s</button></form>',
            esc_url(bimverdi_diskusjon_avmeldingslenke(absint($_REQUEST['u'] ?? 0), absint($_REQUEST['p'] ?? 0), $omfang)),
            'alle' === $omfang ? 'Ja, slå av alle varsler' : 'Ja, slutt å følge'
        );
    } else {
        status_header(200);
        $overskrift = 'alle' === $omfang ? 'Varslene er slått av' : 'Du følger ikke denne diskusjonen lenger';
        $tekst      = 'alle' === $omfang
            ? 'Du får ikke flere e-poster om diskusjoner på bimverdi.no. Vil du ha dem tilbake, kan du slå dem på igjen under en diskusjon på nettsiden.'
            : 'Du får fortsatt varsel hvis noen nevner deg med @navn eller svarer direkte på innlegget ditt.';
        $knapp = $post
            ? sprintf('<a class="bv-knapp" href="%s">Tilbake til diskusjonen</a>', esc_url(get_permalink($post) . '#diskusjon'))
            : sprintf('<a class="bv-knapp" href="%s">Til bimverdi.no</a>', esc_url(home_url('/')));
    }

    nocache_headers();
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    ?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo esc_html($overskrift); ?> &ndash; BIM Verdi</title>
    <style>
        body { margin: 0; padding: 40px 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: #F5F3EE; color: #1A1A1A; }
        .bv-kort { max-width: 520px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.06); padding: 40px; }
        .bv-logo { text-align: center; font-size: 20px; font-weight: 700; margin: 0 auto 32px; max-width: 520px; }
        h1 { font-size: 20px; margin: 0 0 16px; }
        p { font-size: 15px; line-height: 1.6; color: #3A3A3A; margin: 0 0 24px; }
        button, .bv-knapp { display: inline-block; background: #FF8B5E; color: #fff; border: 0; text-decoration: none; padding: 13px 28px; border-radius: 8px; font-size: 15px; font-weight: 500; cursor: pointer; font-family: inherit; }
        form { margin: 0; }
    </style>
</head>
<body>
    <div class="bv-logo">BIM Verdi</div>
    <div class="bv-kort">
        <h1><?php echo esc_html($overskrift); ?></h1>
        <p><?php echo wp_kses($tekst, ['strong' => []]); ?></p>
        <?php echo wp_kses($knapp, [
            'form'   => ['method' => [], 'action' => []],
            'button' => ['type' => []],
            'a'      => ['href' => [], 'class' => []],
        ]); ?>
    </div>
</body>
</html>
    <?php
    exit;
}
