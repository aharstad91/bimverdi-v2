<?php
/**
 * Plugin Name: BIM Verdi - Diskusjon (kommentarer)
 * Description: WordPress-kommentarer som diskusjonstråd mellom aktørene i nettverket. Pilot: kun Byggchat-siden. Kun innloggede kan delta, innlegg publiseres direkte, tråding dybde 3.
 * Version: 2.0.0
 *
 * Historikk:
 * - 03.08 (synk m/ Bård): innebygd kommentarmotor rammet inn som «Still spørsmål»
 *   på fire CPT-er (kunnskapskilde, artikkel, verktoy, arrangement).
 * - 11.08 (synk m/ Bård): reframet til aktør-til-aktør-diskusjon, pilotert på
 *   /prosjekter/byggchat/. CPT-aktiveringen er gatet AV (R17 i
 *   docs/plans/2026-08-11-001) — reaktivering er en egen, senere beslutning
 *   etter pilot-evalueringen (~1. sept, ødemark-risikoen styrer). Koden og
 *   filteret står klare.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CPT-er der kommentarer er aktive. Tom i piloten (R17) — prototypens fire
 * typer reaktiveres eventuelt via filteret når evalueringen sier utvid.
 * Filter: bimverdi_sporsmal_post_types
 */
function bimverdi_sporsmal_post_types() {
    return apply_filters('bimverdi_sporsmal_post_types', []);
}

/**
 * Sider (slugs) med aktiv diskusjonstråd. Slug, ikke post-ID: prod-siden
 * (ID 3354) finnes ikke i lokal DB, så en lokal testside med samme slug gir
 * identisk oppførsel begge steder.
 * Filter: bimverdi_diskusjon_sider
 */
function bimverdi_diskusjon_sider() {
    return apply_filters('bimverdi_diskusjon_sider', ['byggchat']);
}

/**
 * Er diskusjonen aktiv for denne posten? Sentral vakt — brukes av
 * comments_open-filteret, publiserings-/rate-vakten, sidemalen (page.php)
 * og comments.php sin template-vakt.
 */
function bimverdi_diskusjon_aktiv($post = null) {
    $post = get_post($post);
    if (!$post) {
        return false;
    }
    if ($post->post_type === 'page') {
        return in_array($post->post_name, bimverdi_diskusjon_sider(), true);
    }
    return in_array($post->post_type, bimverdi_sporsmal_post_types(), true);
}

/**
 * Comments-support på aktiverte CPT-er (registreres uten i bim-verdi-core).
 * Sider har comments-support i core fra før.
 */
add_action('init', function () {
    foreach (bimverdi_sporsmal_post_types() as $post_type) {
        if (post_type_exists($post_type)) {
            add_post_type_support($post_type, 'comments');
        }
    }
}, 20);

/**
 * Åpne kommentarfeltet for aktive poster uavhengig av lagret comment_status —
 * eksisterende sider står som 'closed' i DB, og dette sparer oss for
 * masse-oppdatering (samme grep som prototypen brukte for CPT-ene).
 */
add_filter('comments_open', function ($open, $post_id) {
    if (bimverdi_diskusjon_aktiv($post_id)) {
        return true;
    }
    return $open;
}, 10, 2);

/**
 * Kun innloggede kan kommentere — gjelder hele nettstedet.
 * (Uinnloggede får login-CTA i comments.php i stedet for skjema.)
 */
add_filter('pre_option_comment_registration', '__return_true');

/**
 * Innloggedes innlegg publiseres direkte, uten forhåndsgodkjenning.
 * Uinnloggede innsendinger (direkte POST mot wp-comments-post.php) avvises
 * hardt. Rate-limit per bruker (R15b): stopper at én konto masse-poster og
 * masse-tagger nettverket. WP_Error herfra rendres på WPs feilskjerm med
 * vennlig melding — valgt over redirect+banner (Andreas 11.08): stien rammer
 * i praksis bare misbruk, og utkastet ligger igjen via tilbake-knappen.
 */
add_filter('pre_comment_approved', function ($approved, $commentdata) {
    $post = get_post($commentdata['comment_post_ID'] ?? 0);
    if (!$post || !bimverdi_diskusjon_aktiv($post)) {
        return $approved;
    }

    $user_id = (int) ($commentdata['user_id'] ?? 0);
    if (!$user_id) {
        return new WP_Error('bimverdi_login_required', __('Du må være innlogget for å delta i diskusjonen.'), 403);
    }

    $maks_per_time = (int) apply_filters('bimverdi_diskusjon_maks_kommentarer_per_time', 15);
    $rate_key      = 'bv_diskusjon_rate_' . $user_id;
    $antall        = (int) get_transient($rate_key);

    if ($antall >= $maks_per_time) {
        return new WP_Error(
            'bimverdi_rate_limited',
            __('Du har publisert mange innlegg på kort tid. Vent en liten stund og prøv igjen — teksten din kan hentes tilbake med nettleserens tilbake-knapp.'),
            429
        );
    }
    set_transient($rate_key, $antall + 1, HOUR_IN_SECONDS);

    return 1;
}, 10, 2);

/**
 * WPs native varselpipeline holdes avslått — diskusjonens egne mention-/svar-
 * varsler går direkte mot Resend via mu-plugins/bimverdi-diskusjon-varsler.php,
 * utenom denne pipelinen.
 */
add_filter('comment_notification_recipients', '__return_empty_array', 10);
add_filter('comment_moderation_recipients', '__return_empty_array', 10);

/**
 * Tråding på, dybde 3 — svar på innlegg, og oppfølging av svaret.
 */
add_filter('pre_option_thread_comments', '__return_true');
add_filter('pre_option_thread_comments_depth', function () {
    return 3;
});

/**
 * Lekkasjetetting (forutsetning for R4-bluren): utloggede skal ikke kunne
 * lese kommentarinnhold via sidekanaler — server-side blur i malen er
 * verdiløs hvis REST eller feeds serverer råteksten.
 */

// REST: /wp/v2/comments (både liste og enkeltkommentar) krever innlogging.
add_filter('rest_request_before_callbacks', function ($response, $handler, $request) {
    if (!empty($response) || is_user_logged_in()) {
        return $response;
    }
    if (strpos($request->get_route(), '/wp/v2/comments') === 0) {
        return new WP_Error(
            'bimverdi_login_required',
            __('Innlogging kreves for å lese kommentarer.'),
            ['status' => 401]
        );
    }
    return $response;
}, 10, 3);

// Kommentar-feeds (/comments/feed/, ?withcomments=1 osv.) slås helt av —
// ingen bruker dem, og de ville lekket innhold forbi bluren.
add_action('template_redirect', function () {
    if (is_comment_feed()) {
        wp_die(__('Kommentar-feeds er deaktivert.'), '', ['response' => 403]);
    }
});
add_filter('feed_links_show_comments_feed', '__return_false');
