<?php
/**
 * Plugin Name: BIM Verdi — Artikkel-redigering fra Min Side
 * Description: Hvem kan redigere en deltakerartikkel, hvilke artikler som er
 *              låst for skjemaet på Min Side, og varsler rundt publisering.
 *              Plan: docs/plans/2026-09-24-001-feat-artikkel-user-journey-plan.md
 *
 * Beslutning (Andreas, 24.09 etter møte med Bård): endringer i en publisert
 * artikkel går rett ut — ingen ny godkjenningsrunde — men BIM Verdi får
 * e-post med hva som er endret, og WP-revisjoner gjør at Bård kan rulle
 * tilbake fra wp-admin.
 *
 * 🔒 SIKKERHETSGATE for forfattervarsel («artikkelen din er publisert»):
 * fail-closed, låst til andreas@aharstad.no til Bårds go. Åpnes i wp-config:
 *     define('BIMVERDI_ARTIKKEL_VARSLER_APEN', true);
 * Mens gaten er låst går en testkopi med gult banner til allowlisten i
 * stedet for til forfatteren. Logglinjer: [bv-artikkel-varsel].
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Revisjoner på artikler: gir Bård «angre» i wp-admin nå som forfattere kan
 * endre publiserte artikler uten ny godkjenning.
 */
add_action('init', function () {
    add_post_type_support('artikkel', 'revisions');
}, 20);

// =============================================================================
// TILGANG
// =============================================================================

/**
 * Brukerens foretak-ID (samme rekkefølge som resten av Min Side).
 */
function bimverdi_artikkel_bruker_foretak_id($user_id) {
    return (int) (get_user_meta($user_id, 'bimverdi_company_id', true)
        ?: get_user_meta($user_id, 'bim_verdi_company_id', true));
}

/**
 * Medforfatter-ID-er på en artikkel (ACF user-felt, kan være ID-er,
 * WP_User-objekter eller arrays avhengig av returformat).
 *
 * @return int[]
 */
function bimverdi_artikkel_medforfatter_ids($post_id) {
    $verdi = function_exists('get_field')
        ? get_field('artikkel_medforfattere', $post_id, false)
        : get_post_meta($post_id, 'artikkel_medforfattere', true);

    $ids = array();
    foreach ((array) $verdi as $v) {
        if (is_object($v) && isset($v->ID)) {
            $ids[] = (int) $v->ID;
        } elseif (is_array($v) && isset($v['ID'])) {
            $ids[] = (int) $v['ID'];
        } elseif (is_numeric($v)) {
            $ids[] = (int) $v;
        }
    }
    return array_values(array_filter($ids));
}

/**
 * Kan brukeren redigere artikkelen fra Min Side?
 *
 * - Admin: alltid.
 * - Forfatter og medforfattere: ja.
 * - Kolleger i samme foretak: ja, men KUN når forfatteren selv er et
 *   medlem av det foretaket (ikke admin). Ellers kunne et foretak redigert
 *   redaksjonelle artikler Bård har skrevet OM dem (artikkel_bedrift peker
 *   da på foretaket, men artikkelen er BIM Verdis).
 *
 * Status sjekkes separat (bimverdi_artikkel_kan_redigeres_status).
 */
function bimverdi_artikkel_kan_redigere($post_id, $user_id = null) {
    $user_id = $user_id ? (int) $user_id : get_current_user_id();
    $post    = get_post($post_id);

    if (!$user_id || !$post || $post->post_type !== 'artikkel') {
        return false;
    }
    if (user_can($user_id, 'manage_options')) {
        return true;
    }

    $forfatter_id = (int) $post->post_author;
    if ($forfatter_id === $user_id) {
        return true;
    }
    if (in_array($user_id, bimverdi_artikkel_medforfatter_ids($post->ID), true)) {
        return true;
    }

    $mitt_foretak = bimverdi_artikkel_bruker_foretak_id($user_id);
    if (!$mitt_foretak || user_can($forfatter_id, 'manage_options')) {
        return false;
    }
    $artikkel_foretak = function_exists('bimverdi_artikkel_foretak_id')
        ? (int) bimverdi_artikkel_foretak_id($post->ID, false)
        : (int) get_post_meta($post->ID, 'artikkel_bedrift', true);

    return $artikkel_foretak === $mitt_foretak
        && bimverdi_artikkel_bruker_foretak_id($forfatter_id) === $mitt_foretak;
}

/**
 * Statuser som kan redigeres fra Min Side. Private (skjult av BIM Verdi)
 * og utkast håndteres i wp-admin.
 */
function bimverdi_artikkel_kan_redigeres_status($post_id) {
    return in_array(get_post_status($post_id), array('pending', 'publish'), true);
}

// =============================================================================
// GUTENBERG-LÅS
// =============================================================================

/**
 * Blokktyper skjemaet på Min Side kan redigere uten å ødelegge noe. Når Bård
 * åpner en innsendt artikkel i Gutenberg og lagrer, blir teksten gjerne
 * konvertert til paragraph/heading/list-blokker — de er ren HTML innenfor
 * kommentarmarkørene og tåler en rundtur gjennom TinyMCE.
 */
function bimverdi_artikkel_enkle_blokker() {
    return array(
        'core/paragraph', 'core/heading', 'core/list', 'core/list-item',
        'core/quote', 'core/separator', 'core/freeform',
    );
}

/**
 * Er artikkelen låst for skjemaet på Min Side fordi Bård har satt den opp
 * med blokker skjemaet ikke kan gjengi (bilder, egen HTML, gjenbrukbare
 * blokker, kolonner …)? Redigering da ville slettet oppsettet.
 */
function bimverdi_artikkel_er_laast($post_id) {
    $content = (string) get_post_field('post_content', $post_id);
    if (!function_exists('has_blocks') || !has_blocks($content)) {
        return false;
    }
    return !bimverdi_artikkel_blokker_er_enkle(parse_blocks($content));
}

function bimverdi_artikkel_blokker_er_enkle($blocks) {
    foreach ($blocks as $block) {
        $navn = $block['blockName'];
        if ($navn !== null && !in_array($navn, bimverdi_artikkel_enkle_blokker(), true)) {
            return false;
        }
        if (!empty($block['innerBlocks']) && !bimverdi_artikkel_blokker_er_enkle($block['innerBlocks'])) {
            return false;
        }
    }
    return true;
}

/**
 * Innhold klart for TinyMCE: blokk-kommentarene fjernes, HTML-en beholdes.
 * Lagres det igjen, åpner Gutenberg artikkelen som en klassisk blokk.
 */
function bimverdi_artikkel_innhold_for_redigering($content) {
    if (!function_exists('has_blocks') || !has_blocks($content)) {
        return $content;
    }
    $html = bimverdi_artikkel_blokker_til_html(parse_blocks($content));
    return trim(preg_replace("/\n{3,}/", "\n\n", $html));
}

function bimverdi_artikkel_blokker_til_html($blocks) {
    $html = '';
    foreach ($blocks as $block) {
        $inner = $block['innerBlocks'];
        $i     = 0;
        foreach ($block['innerContent'] as $del) {
            // null i innerContent markerer plassen til neste indre blokk.
            $html .= is_string($del) ? $del : bimverdi_artikkel_blokker_til_html(array($inner[$i++]));
        }
    }
    return $html;
}

// =============================================================================
// LISTE PÅ MIN SIDE
// =============================================================================

/**
 * Artikler brukeren skal se under «Mine artikler»: egne, der brukeren er
 * medforfatter, og kollegers i samme foretak (samme regel som redigering).
 *
 * @return WP_Post[]
 */
function bimverdi_artikler_for_bruker($user_id) {
    $user_id  = (int) $user_id;
    $statuser = array('publish', 'pending', 'private', 'draft');
    $ids      = array();

    $ids = array_merge($ids, get_posts(array(
        'post_type'      => 'artikkel',
        'post_status'    => $statuser,
        'author'         => $user_id,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    )));

    // Medforfatter: ACF lagrer user-feltet serialisert («"123"»).
    $ids = array_merge($ids, get_posts(array(
        'post_type'      => 'artikkel',
        'post_status'    => $statuser,
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(array(
            'key'     => 'artikkel_medforfattere',
            'value'   => '"' . $user_id . '"',
            'compare' => 'LIKE',
        )),
    )));

    $foretak = bimverdi_artikkel_bruker_foretak_id($user_id);
    if ($foretak) {
        $kandidater = get_posts(array(
            'post_type'      => 'artikkel',
            'post_status'    => $statuser,
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(array(
                'key'   => 'artikkel_bedrift',
                'value' => $foretak,
            )),
        ));
        foreach ($kandidater as $id) {
            if (bimverdi_artikkel_kan_redigere($id, $user_id)) {
                $ids[] = $id;
            }
        }
    }

    $ids = array_unique(array_map('intval', $ids));
    if (!$ids) {
        return array();
    }

    return get_posts(array(
        'post_type'      => 'artikkel',
        'post_status'    => $statuser,
        'post__in'       => $ids,
        'posts_per_page' => -1,
        'orderby'        => 'modified',
        'order'          => 'DESC',
    ));
}

// =============================================================================
// VARSEL: ENDRING I PUBLISERT ARTIKKEL → BIM VERDI
// =============================================================================

/**
 * Øyeblikksbilde av feltene skjemaet kan endre, for å vise hva som er endret.
 */
function bimverdi_artikkel_snapshot($post_id) {
    $lenker = get_post_meta($post_id, '_bv_eksterne_lenker', true);
    return array(
        'Tittel'        => get_post_field('post_title', $post_id),
        'Ingress'       => (string) get_post_meta($post_id, 'artikkel_ingress', true),
        'Brødtekst'     => trim(preg_replace('/\s+/', ' ', wp_strip_all_tags(bimverdi_artikkel_innhold_for_redigering(get_post_field('post_content', $post_id))))),
        'Forsidebilde'  => (int) get_post_thumbnail_id($post_id),
        'Temagrupper'   => implode(',', wp_get_object_terms($post_id, 'temagruppe', array('fields' => 'ids'))),
        'Verktøykategorier' => implode(',', wp_get_object_terms($post_id, 'verktoykategori', array('fields' => 'ids'))),
        'Kunnskapskilder'   => implode(',', (array) get_post_meta($post_id, '_bv_kunnskapskilder', true)),
        'Lenker'        => wp_json_encode(is_array($lenker) ? $lenker : array()),
    );
}

/**
 * Send e-post til BIM Verdi (post@bimverdi.no) når en publisert artikkel er
 * endret fra Min Side. Sendes ikke når ingenting er endret.
 */
function bimverdi_artikkel_varsle_endring($post_id, $for, $etter, $user_id) {
    $endret = array();
    foreach ($etter as $felt => $verdi) {
        if ((string) ($for[$felt] ?? '') !== (string) $verdi) {
            $endret[] = $felt;
        }
    }
    if (!$endret || !function_exists('bimverdi_send_admin_notification_email')) {
        return;
    }

    $bruker  = get_userdata($user_id);
    $tittel  = get_post_field('post_title', $post_id);
    $rader   = '';
    foreach (array('Tittel', 'Ingress') as $felt) {
        if (in_array($felt, $endret, true)) {
            $rader .= sprintf(
                '<tr><td style="padding:4px 12px 4px 0;color:#666;vertical-align:top;">%s før</td><td style="color:#666;">%s</td></tr>'
                . '<tr><td style="padding:4px 12px 4px 0;color:#666;vertical-align:top;">%s nå</td><td><strong>%s</strong></td></tr>',
                esc_html($felt), esc_html($for[$felt]), esc_html($felt), esc_html($etter[$felt])
            );
        }
    }

    $body = sprintf(
        '<p>En publisert artikkel er endret fra Min Side. Endringene er allerede synlige på nettsiden.</p>
        <table style="border-collapse:collapse;font-size:14px;">
            <tr><td style="padding:4px 12px 4px 0;color:#666;vertical-align:top;">Artikkel</td><td><strong>%s</strong></td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;vertical-align:top;">Endret av</td><td>%s &lt;%s&gt;</td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;vertical-align:top;">Tidspunkt</td><td>%s</td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#666;vertical-align:top;">Endrede felt</td><td>%s</td></tr>
            %s
        </table>
        <p style="margin-top:16px;font-size:14px;">Vil du angre? Åpne artikkelen i wp-admin og velg en tidligere revisjon.</p>
        <p style="margin-top:24px;">
            <a href="%s" style="background:#FF8B5E;color:#fff;padding:10px 16px;text-decoration:none;border-radius:6px;display:inline-block;">Se artikkelen</a>
            &nbsp; <a href="%s" style="color:#1A1A1A;">Åpne i wp-admin</a>
        </p>%s',
        esc_html($tittel),
        esc_html($bruker ? $bruker->display_name : ''),
        esc_html($bruker ? $bruker->user_email : ''),
        esc_html(date_i18n('j. F Y \k\l. H:i')),
        esc_html(implode(', ', $endret)),
        $rader,
        esc_url(get_permalink($post_id)),
        esc_url(admin_url('post.php?post=' . $post_id . '&action=edit')),
        function_exists('bimverdi_render_terms_footer_html') ? bimverdi_render_terms_footer_html() : ''
    );

    bimverdi_send_admin_notification_email(sprintf('Publisert artikkel endret: %s', $tittel), $body);
}

// =============================================================================
// VARSEL: «ARTIKKELEN DIN ER PUBLISERT» → FORFATTER (bak gate)
// =============================================================================

function bimverdi_artikkel_varsler_allowlist() {
    $list = apply_filters('bimverdi_artikkel_varsler_allowlist', array('andreas@aharstad.no'));
    return array_map('strtolower', array_map('trim', (array) $list));
}

function bimverdi_artikkel_varsler_gate_apen() {
    $apen = defined('BIMVERDI_ARTIKKEL_VARSLER_APEN') && true === BIMVERDI_ARTIKKEL_VARSLER_APEN;
    return true === apply_filters('bimverdi_artikkel_varsler_gate_apen', $apen);
}

add_action('transition_post_status', 'bimverdi_artikkel_publisert_varsel', 10, 3);

function bimverdi_artikkel_publisert_varsel($ny, $gammel, $post) {
    if ($post->post_type !== 'artikkel' || $ny !== 'publish' || $gammel !== 'pending') {
        return;
    }
    $forfatter = get_userdata($post->post_author);
    // Bårds egne artikler (eller andre admins) trenger ikke beskjed.
    if (!$forfatter || user_can($forfatter, 'manage_options') || !is_email($forfatter->user_email)) {
        return;
    }

    $mottaker = $forfatter->user_email;
    $banner   = '';
    if (!bimverdi_artikkel_varsler_gate_apen()) {
        $allow = bimverdi_artikkel_varsler_allowlist();
        if (!in_array(strtolower($mottaker), $allow, true)) {
            $banner   = bimverdi_epost_testbanner(sprintf(
                'Testkopi — varselet er låst. Ekte mottaker ville vært <strong>%s</strong>.',
                esc_html($mottaker)
            ));
            $mottaker = reset($allow);
        }
        error_log(sprintf('[bv-artikkel-varsel] gate låst: artikkel %d, forfatter %s → sendt til %s', $post->ID, $forfatter->user_email, $mottaker));
    }

    $tittel = get_the_title($post);
    $html   = bimverdi_epost_dokument(array(
        'tittel'    => 'Artikkelen din er publisert',
        'banner'    => $banner,
        'innhold'   => sprintf(
            '<h1 style="margin:0 0 16px;font-size:22px;">Artikkelen din er publisert</h1>'
            . '<p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Hei %s,</p>'
            . '<p style="margin:0 0 16px;font-size:15px;line-height:1.6;">«%s» er nå publisert på bimverdi.no.</p>'
            . '<p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Du kan rette eller oppdatere artikkelen selv under Mine artikler på Min Side. '
            . 'Del gjerne artikkelen på LinkedIn — det gir flere lesere.</p>',
            esc_html($forfatter->first_name ?: $forfatter->display_name),
            esc_html($tittel)
        ),
        'cta_url'   => get_permalink($post),
        'cta_tekst' => 'Se artikkelen',
        'etter_cta' => sprintf(
            '<p style="margin:0;font-size:13px;"><a href="%s" style="color:#6B6B6B;">Gå til Mine artikler</a></p>',
            esc_url(home_url('/min-side/artikler/'))
        ),
    ));

    $ok = wp_mail($mottaker, 'Artikkelen din er publisert: ' . $tittel, $html, array(
        'Content-Type: text/html; charset=UTF-8',
        'From: BIM Verdi <noreply@bimverdi.no>',
    ));
    error_log(sprintf('[bv-artikkel-varsel] artikkel %d → %s: %s', $post->ID, $mottaker, $ok ? 'sendt' : 'FEILET'));
}
