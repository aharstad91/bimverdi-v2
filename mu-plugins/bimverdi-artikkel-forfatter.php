<?php
/**
 * Plugin Name: BIM Verdi - Forfattervelger for deltakerbrukere
 * Description: Gjør deltakerbrukere valgbare i «Forfatter»-feltet i wp-admin. Uten dette viser velgeren bare de tre administratorene.
 * Version: 1.0.0
 *
 * PROBLEMET (Bård, Trello #347 punkt 7.2, møte 03.09.2026): han ville sette
 * Einar Gudmundsson som FORFATTER av en artikkel Dag Fjeld Edvardsen skrev,
 * men fant bare ACF-feltet «Medforfatter». Løsningen han endte på var å døpe
 * om Dags konto til «Einar Gudmundsson» — altså et alias, som er nettopp det
 * Catenda ba om å slippe.
 *
 * ÅRSAKEN (målt 03.09.2026): Forfatter-feltet finnes, men det er tomt for
 * alle andre enn administratorer. Både wp-admins nedtrekksliste og
 * blokkeditorens velger spør etter brukere med `who=authors`. WordPress
 * oversetter det til den gamle metaverdien `wp_user_level != 0`, ikke til en
 * capability-sjekk. BIM Verdis egne roller (medlem, tilleggskontakt,
 * deltaker, prosjektdeltaker, partner) ble opprettet med add_role() uten de
 * eldgamle `level_N`-capabilitiene, så alle 589 medlemsbrukere har
 * wp_user_level = 0 og faller ut. Målt på ekte data: `who=authors` gir 3
 * treff (kun administratorene), mens `capability=edit_posts` gir 192.
 * Både Dag (131) og Einar (217) er `tilleggskontakt` og HAR edit_posts.
 *
 * FIKSEN: bytt `who=authors` for `capability=edit_posts` i de to spørringene
 * som mater forfattervelgeren. Vi rører ikke rollene — å legge `level_N` inn
 * i dem ville endret oppførsel i alt annet som fortsatt leser user_level.
 * Ingen bruker får nye rettigheter av dette; det er utvalget i en
 * nedtrekksliste som utvides, ikke hva noen kan gjøre.
 *
 * MERK at `medlem` (403 brukere) mangler edit_posts og derfor fortsatt ikke
 * er valgbar. Det er riktig: en artikkelforfatter må kunne eie et innlegg.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Får den innloggede lov å se den utvidede forfatterlista?
 *
 * `/wp/v2/users?who=authors` er lesbar for alle i WordPress — det er slik
 * forfatterarkiv fungerer. Utvider vi den uten vakt, publiserer vi navnene
 * på 192 medlemsbrukere til hvem som helst. Kravet er derfor
 * `edit_others_posts`: redaktør og administrator, altså de som faktisk skal
 * kunne tilskrive en artikkel til noen andre.
 *
 * @return bool
 */
function bimverdi_kan_velge_andres_forfatter() {
    return is_user_logged_in() && current_user_can('edit_others_posts');
}

/**
 * Blokkeditorens forfattervelger (REST: /wp/v2/users?who=authors).
 *
 * Filteret fyrer bare på REST-spørringer mot brukere, så det kan ikke treffe
 * WordPress' interne brukeroppslag. Vi rører kun spørringer som eksplisitt
 * ba om `who=authors` — alt annet går urørt videre.
 *
 * @param array           $args    Argumenter til WP_User_Query.
 * @param WP_REST_Request $request Forespørselen.
 * @return array
 */
function bimverdi_rest_forfatterliste($args, $request) {
    if (empty($args['who']) || $args['who'] !== 'authors') {
        return $args;
    }
    if (!bimverdi_kan_velge_andres_forfatter()) {
        return $args;
    }

    unset($args['who']);
    $args['capability'] = 'edit_posts';
    $args['orderby']    = 'display_name';
    $args['order']      = 'ASC';

    return $args;
}
add_filter('rest_user_query', 'bimverdi_rest_forfatterliste', 10, 2);

/**
 * Klassisk nedtrekksliste — Hurtigredigering, massevalg og Forfatter-boksen
 * i editorer som ikke bruker blokker.
 *
 * @param array $args Argumenter til wp_dropdown_users()/WP_User_Query.
 * @return array
 */
function bimverdi_dropdown_forfatterliste($args) {
    if (empty($args['who']) || $args['who'] !== 'authors') {
        return $args;
    }
    if (!bimverdi_kan_velge_andres_forfatter()) {
        return $args;
    }

    unset($args['who']);
    $args['capability'] = 'edit_posts';
    $args['orderby']    = 'display_name';
    $args['order']      = 'ASC';

    return $args;
}
add_filter('wp_dropdown_users_args', 'bimverdi_dropdown_forfatterliste', 10, 1);
