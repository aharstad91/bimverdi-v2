<?php
/**
 * Plugin Name: BIM Verdi - Diskusjon: @-mentions
 * Description: Autocomplete-endepunkt, server-validert ID-binding og render-tid-markering for @-mentions i diskusjonstråden (pilot: Byggchat, synk 11.08, plan docs/plans/2026-08-11-001).
 * Version: 1.0.0
 *
 * Tre deler:
 * 1) wp_ajax-endepunkt for autocomplete (kun innloggede — ingen nopriv):
 *    navn + foretak, aldri e-post. Dagsgrense per konto (R15a) fordi
 *    endepunktet bevisst senker view_members_full-grensen (origin-beslutning).
 * 2) Innsendingsvalidering: skjult felt {id, navn} fra autocompleten bindes
 *    kun når (a) bruker-ID finnes, (b) innsendt navn matcher ID-ens faktiske
 *    display_name, og (c) «@Navn» står i teksten med ordgrense. Uten (b) kan
 *    et manipulert felt vise «@Person A» mens varselet går til bruker B.
 * 3) Rendering: filter på comment_text etter kses — markeringen lagres aldri
 *    i comment_content (kses ville strippet den for vanlige brukere), og
 *    bruker alltid server-hentet display_name.
 *
 * Meta-nøkkelen _bv_mention_user_ids er sannhetskilden for varslene
 * (mu-plugins/bimverdi-diskusjon-varsler.php). Underscore-prefiks + ingen
 * register_meta(show_in_rest) = ikke eksponert via REST.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Maks antall mentions som bindes per kommentar (misbruksvern). */
const BV_MENTIONS_MAKS_PER_KOMMENTAR = 10;

/**
 * Regex-mønster for «@Navn» med ordgrense — hindrer at «@Anna Berg» matcher
 * inne i «@Anna Bergström» (prefiks-kollisjon).
 *
 * Krav 34: uten forskjell på store og små bokstaver, og med fleksible
 * mellomrom, slik at navn som er skrevet for hånd («@morten gustavsen»,
 * «@Dag  Fjeld») markeres likt med navn valgt fra lista.
 */
function bv_mention_pattern($navn) {
    $ord = preg_split('/\s+/u', trim((string) $navn), -1, PREG_SPLIT_NO_EMPTY);
    $ord = array_map(function ($o) { return preg_quote($o, '/'); }, $ord);
    return '/@' . implode('\s+', $ord) . '(?![\p{L}\p{N}])/iu';
}

/** Normaliser et navn for sammenligning: små bokstaver, ett mellomrom. */
function bv_mention_normaliser($navn) {
    return mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $navn)));
}

/** Fjern tegnsetting og mellomrom på slutten («Jensen,» → «Jensen»). */
function bv_mention_rens_slutt($tekst) {
    return preg_replace('/[\s,.:;!?)]+$/u', '', (string) $tekst);
}

/**
 * Alle brukere som kan tagges, indeksert på normalisert visningsnavn.
 * Samme utvalg som autocompleten (alle brukere). Hentes én gang per
 * forespørsel — med noen hundre brukere er det billigere og mer robust
 * (kollasjon, doble mellomrom) enn én SQL-spørring per kandidat.
 *
 * @return array<string, int[]> normalisert navn → bruker-ID-er
 */
function bv_mention_kandidatindeks() {
    static $indeks = null;
    if (null !== $indeks) {
        return $indeks;
    }
    global $wpdb;
    $indeks = [];
    foreach ($wpdb->get_results("SELECT ID, display_name FROM {$wpdb->users}") as $rad) {
        $nokkel = bv_mention_normaliser($rad->display_name);
        if ($nokkel !== '') {
            $indeks[$nokkel][] = (int) $rad->ID;
        }
    }
    return $indeks;
}

/**
 * Krav 34: finn @-navn i en tekst og avgjør om hvert navn passer med én
 * person. Brukes både ved lagring (R1, automatisk tagging) og av sjekken før
 * publisering (R2, AJAX). Funksjonen skriver ingenting, sender ingenting og
 * teller ikke mot dagsgrensen.
 *
 * Regler:
 * - «@» må stå først eller etter blank/«(» (som i bv-mentions.js), så
 *   e-postadresser og URL-er ikke treffer.
 * - Opptil fem ord etter «@», lengste kombinasjon prøves først.
 * - Tegnsetting på slutten fjernes; sammenligning uten forskjell på store og
 *   små bokstaver og med mellomrom slått sammen.
 *
 * @param string $tekst
 * @return array<int, array{tekst:string, navn:string, status:string, id:?int}>
 *   status: 'unik' (id satt, navn = kontoens display_name) | 'flere' | 'ingen'
 */
function bimverdi_mention_finn_i_tekst($tekst) {
    $tekst = (string) $tekst;
    if (strpos($tekst, '@') === false) {
        return [];
    }
    if (!preg_match_all('/(?<=^|[\s(])@(?=[\p{L}\p{N}])/u', $tekst, $m, PREG_OFFSET_CAPTURE)) {
        return [];
    }

    $indeks   = bv_mention_kandidatindeks();
    $resultat = [];

    foreach ($m[0] as $treff) {
        $rest = substr($tekst, $treff[1] + 1, 400);
        // Opptil fem ord på samme linje (linjeskift avslutter et navn).
        if (!preg_match('/^\S+(?:[^\S\r\n]+\S+){0,4}/u', $rest, $mm)) {
            continue;
        }
        $ord = preg_split('/[^\S\r\n]+/u', $mm[0], -1, PREG_SPLIT_NO_EMPTY);

        $funnet = null;
        for ($k = count($ord); $k >= 1; $k--) {
            $kandidat = bv_mention_rens_slutt(implode(' ', array_slice($ord, 0, $k)));
            if ($kandidat === '') {
                continue;
            }
            $nokkel = bv_mention_normaliser($kandidat);
            if (isset($indeks[$nokkel])) {
                $ids = array_values(array_unique($indeks[$nokkel]));
                if (count($ids) === 1) {
                    $bruker = get_userdata($ids[0]);
                    $navn   = $bruker ? $bruker->display_name : $kandidat;
                    $funnet = ['tekst' => '@' . $kandidat, 'navn' => $navn, 'status' => 'unik', 'id' => $ids[0]];
                } else {
                    $funnet = ['tekst' => '@' . $kandidat, 'navn' => $kandidat, 'status' => 'flere', 'id' => null];
                }
                break;
            }
        }

        if (!$funnet) {
            // Ingen treff: vis «@» + ord med stor forbokstav (maks tre) i
            // advarselen. Bare til visning — ingenting bindes.
            $vis = [];
            foreach ($ord as $o) {
                if (count($vis) >= 3 || !preg_match('/^\p{Lu}/u', $o)) {
                    break;
                }
                $vis[] = $o;
                if (bv_mention_rens_slutt($o) !== $o) {
                    break; // «Testesen,» avslutter navnet.
                }
            }
            if (!$vis) {
                $vis = [$ord[0]];
            }
            $navn   = bv_mention_rens_slutt(implode(' ', $vis));
            if ($navn === '') {
                continue;
            }
            $funnet = ['tekst' => '@' . $navn, 'navn' => $navn, 'status' => 'ingen', 'id' => null];
        }

        $resultat[] = $funnet;
    }

    return $resultat;
}

/**
 * 1) Autocomplete: admin-ajax, kun innloggede (ingen wp_ajax_nopriv).
 * Svar: {id, navn, foretak} — aldri e-postadresser.
 */
add_action('wp_ajax_bimverdi_mention_sok', function () {
    if (!check_ajax_referer('bv_mentions', 'nonce', false)) {
        wp_send_json_error(['code' => 'ugyldig_nonce'], 403);
    }

    // Dagsgrense per konto (R15a): satt lavt fordi rate-limit bare gjør
    // enumerering langsom, ikke umulig — 50/dag er fortsatt romslig for folk.
    $bruker_id = get_current_user_id();
    $grense    = (int) apply_filters('bimverdi_mention_sok_dagsgrense', 50);
    $rate_key  = 'bv_mention_sok_' . $bruker_id;
    $antall    = (int) get_transient($rate_key);
    if ($antall >= $grense) {
        wp_send_json_error(['code' => 'rate_limit'], 429);
    }
    set_transient($rate_key, $antall + 1, DAY_IN_SECONDS);

    $term = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));
    if (mb_strlen($term) < 2) {
        wp_send_json_error(['code' => 'for_kort'], 400);
    }

    global $wpdb;
    $like = '%' . $wpdb->esc_like($term) . '%';
    $rader = $wpdb->get_results($wpdb->prepare(
        "SELECT ID, display_name FROM {$wpdb->users}
         WHERE display_name LIKE %s
         ORDER BY display_name ASC
         LIMIT 8",
        $like
    ));

    $treff = [];
    foreach ($rader as $rad) {
        $foretak = bimverdi_get_user_company((int) $rad->ID);
        $treff[] = [
            'id'      => (int) $rad->ID,
            'navn'    => $rad->display_name,
            'foretak' => ($foretak && !empty($foretak['name'])) ? $foretak['name'] : '',
        ];
    }

    wp_send_json_success($treff);
});

/**
 * 2) Bind validerte mentions til kommentaren som comment-meta.
 * Hektes på wp_insert_comment så innhold og ID er endelige; $_POST fra
 * wp-comments-post.php er fortsatt tilgjengelig her. Forkastede bindinger
 * logges med årsak (feilsøkbart uten å blokkere publisering).
 */
add_action('wp_insert_comment', function ($comment_id, $comment) {
    $post = get_post($comment->comment_post_ID);
    if (!$post || !function_exists('bimverdi_diskusjon_aktiv') || !bimverdi_diskusjon_aktiv($post)) {
        return;
    }

    // Krav 34: ingen tidlig retur når lista er tom — R1 under skal kjøre
    // uansett. Ugyldig/manglende felt = ingen bindinger fra lista.
    $raa = empty($_POST['bv_mentions']) ? [] : json_decode(wp_unslash($_POST['bv_mentions']), true);
    if (!is_array($raa)) {
        $raa = [];
    }

    $bundne = [];
    foreach (array_slice($raa, 0, BV_MENTIONS_MAKS_PER_KOMMENTAR) as $kandidat) {
        $uid  = isset($kandidat['id']) ? (int) $kandidat['id'] : 0;
        $navn = isset($kandidat['navn']) ? trim((string) $kandidat['navn']) : '';
        if (!$uid || $navn === '') {
            continue;
        }
        $bruker = get_userdata($uid);
        if (!$bruker) {
            error_log("BIM Verdi diskusjon: mention forkastet (kommentar $comment_id) — bruker $uid finnes ikke.");
            continue;
        }
        // Navn↔ID-integritet: klientens navnestreng må matche kontoens
        // faktiske display_name — ellers kan teksten vise én person mens
        // varselet går til en annen.
        if (mb_strtolower(trim($bruker->display_name)) !== mb_strtolower($navn)) {
            error_log("BIM Verdi diskusjon: mention forkastet (kommentar $comment_id) — navn matcher ikke display_name for bruker $uid.");
            continue;
        }
        // «@Navn» må fortsatt stå i teksten (ordgrense) — redigert bort = forkastet.
        if (!preg_match(bv_mention_pattern($bruker->display_name), $comment->comment_content)) {
            continue;
        }
        $bundne[$uid] = $uid; // dedupe
    }

    // Krav 34 R1: navn som er skrevet helt og bare passer med én person,
    // tagges som om de var valgt fra lista. Et tillegg til bindingene over,
    // ikke en erstatning. Feilisolert: tagging skal aldri gi feilside etter
    // at innlegget er lagret (R7).
    try {
        $auto = 0;
        foreach (bimverdi_mention_finn_i_tekst($comment->comment_content) as $treff) {
            if ('unik' === $treff['status'] && !isset($bundne[$treff['id']])) {
                $bundne[$treff['id']] = $treff['id'];
                $auto++;
            }
        }
        if ($auto) {
            error_log(sprintf('[bv-mentions] Auto-tagget %d navn i kommentar %d.', $auto, $comment_id));
        }
    } catch (\Throwable $e) {
        error_log(sprintf('[bv-mentions] Auto-tagging feilet (kommentar %d): %s', $comment_id, $e->getMessage()));
    }

    $bundne = array_slice($bundne, 0, BV_MENTIONS_MAKS_PER_KOMMENTAR, true);
    if ($bundne) {
        update_comment_meta($comment_id, '_bv_mention_user_ids', array_values($bundne));
    }
}, 10, 2);

/**
 * Krav 34: redigering i wp-admin. Nye navn i teksten tagges, og BARE de nye
 * får varsel. Tagger som var der fra før, gir ikke nytt varsel og fjernes
 * aldri. Varselet går gjennom samme hjelper som ved publisering (samme gate,
 * avmelding og tak).
 */
add_action('edit_comment', function ($comment_id) {
    try {
        $comment = get_comment($comment_id);
        if (!$comment || '1' !== (string) $comment->comment_approved) {
            return;
        }
        $post = get_post($comment->comment_post_ID);
        if (!$post || !function_exists('bimverdi_diskusjon_aktiv') || !bimverdi_diskusjon_aktiv($post)) {
            return;
        }

        $eksisterende = get_comment_meta($comment_id, '_bv_mention_user_ids', true);
        $eksisterende = is_array($eksisterende) ? array_map('intval', $eksisterende) : [];

        $nye = [];
        foreach (bimverdi_mention_finn_i_tekst($comment->comment_content) as $treff) {
            if ('unik' !== $treff['status'] || in_array($treff['id'], $eksisterende, true)) {
                continue;
            }
            if (count($eksisterende) + count($nye) >= BV_MENTIONS_MAKS_PER_KOMMENTAR) {
                break;
            }
            $nye[$treff['id']] = 'mention';
        }
        if (!$nye) {
            return;
        }

        update_comment_meta($comment_id, '_bv_mention_user_ids', array_values(array_merge($eksisterende, array_keys($nye))));
        error_log(sprintf('[bv-mentions] Redigering: %d nye tagger i kommentar %d.', count($nye), $comment_id));

        if (function_exists('bimverdi_diskusjon_varsle_mottakere')) {
            bimverdi_diskusjon_varsle_mottakere($comment, $post, $nye);
        }
    } catch (\Throwable $e) {
        error_log(sprintf('[bv-mentions] Redigering feilet (kommentar %d): %s', $comment_id, $e->getMessage()));
    }
});

/**
 * Krav 34 R2: sjekk før publisering. Ett kall for hele teksten, og det teller
 * som ETT søk mot dagsgrensen uansett hvor mange navn teksten har. Svaret har
 * aldri bruker-ID for 'flere' eller 'ingen'.
 */
add_action('wp_ajax_bimverdi_mention_sjekk', function () {
    if (!check_ajax_referer('bv_mentions', 'nonce', false)) {
        wp_send_json_error(['code' => 'ugyldig_nonce'], 403);
    }

    $bruker_id = get_current_user_id();
    $grense    = (int) apply_filters('bimverdi_mention_sok_dagsgrense', 50);
    $rate_key  = 'bv_mention_sok_' . $bruker_id;
    $antall    = (int) get_transient($rate_key);
    if ($antall >= $grense) {
        wp_send_json_error(['code' => 'rate_limit'], 429);
    }
    set_transient($rate_key, $antall + 1, DAY_IN_SECONDS);

    $tekst = isset($_POST['tekst']) ? mb_substr((string) wp_unslash($_POST['tekst']), 0, 20000) : '';

    $treff = array_map(function ($t) {
        return [
            'tekst'  => $t['tekst'],
            'navn'   => $t['navn'],
            'status' => $t['status'],
            'id'     => 'unik' === $t['status'] ? (int) $t['id'] : null,
        ];
    }, bimverdi_mention_finn_i_tekst($tekst));

    wp_send_json_success([
        'treff' => $treff,
        'maks'  => BV_MENTIONS_MAKS_PER_KOMMENTAR,
    ]);
});

/**
 * Krav 34 R3: etter publisering sendes skribenten tilbake med
 * ?bv_varslet={id}, slik at innlegget kan vise «Varslet: …». Legges bare på
 * når noen faktisk fikk mention-varsel (meta skrives av varsel-pluginen).
 */
add_filter('comment_post_redirect', function ($location, $comment) {
    if (!$comment || !get_comment_meta($comment->comment_ID, '_bv_mention_varslet', true)) {
        return $location;
    }
    $deler = explode('#', $location, 2);
    $url   = add_query_arg('bv_varslet', (int) $comment->comment_ID, $deler[0]);
    return $url . (isset($deler[1]) ? '#' . $deler[1] : '');
}, 10, 2);

/**
 * 3) Marker bundne mentions ved visning. Kjører etter kses/wpautop (prio 20).
 *
 * Innlogget-vakten er fjernet 10.09.2026: fra Trello #348 punkt 6 vises hele
 * tråden også for utloggede, og markeringen avslører ingenting nytt — navnet
 * står allerede i innleggsteksten, vi gir det bare farge.
 */
add_filter('comment_text', function ($text, $comment = null) {
    if (!$comment) {
        return $text;
    }
    $ids = get_comment_meta($comment->comment_ID, '_bv_mention_user_ids', true);
    if (!$ids || !is_array($ids)) {
        return $text;
    }
    foreach ($ids as $uid) {
        $bruker = get_userdata((int) $uid);
        if (!$bruker) {
            continue; // Slettet konto → teksten står som ren tekst.
        }
        $navn = $bruker->display_name; // Alltid server-hentet navn.
        $text = preg_replace(
            bv_mention_pattern($navn),
            '<span class="bv-mention">@' . esc_html($navn) . '</span>',
            $text
        );
    }
    return $text;
}, 20, 2);
