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
 */
function bv_mention_pattern($navn) {
    return '/@' . preg_quote($navn, '/') . '(?![\p{L}\p{N}])/u';
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
    if (empty($_POST['bv_mentions'])) {
        return;
    }
    $post = get_post($comment->comment_post_ID);
    if (!$post || !function_exists('bimverdi_diskusjon_aktiv') || !bimverdi_diskusjon_aktiv($post)) {
        return;
    }

    $raa = json_decode(wp_unslash($_POST['bv_mentions']), true);
    if (!is_array($raa)) {
        return;
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

    if ($bundne) {
        update_comment_meta($comment_id, '_bv_mention_user_ids', array_values($bundne));
    }
}, 10, 2);

/**
 * 3) Marker bundne mentions ved visning. Kjører etter kses/wpautop (prio 20)
 * og kun i innlogget sti — utloggede får aldri innholdet uansett
 * (comments.php rendrer placeholder).
 */
add_filter('comment_text', function ($text, $comment = null) {
    if (!$comment || !is_user_logged_in()) {
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
