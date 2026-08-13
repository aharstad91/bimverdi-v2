<?php
/**
 * Arkivsider — Gutenberg-redigerbar topp på offentlige arkivsider.
 *
 * Bård, synk 11.08: toppen på arkivsidene (verktøy, kunnskapskilder osv.) skal
 * kunne redigeres like fritt som temagruppe-sidene — full Gutenberg, ikke
 * tekstfelt. CPT-en 'arkivside' (bim-verdi-core/includes/class-post-types.php)
 * holder én post per arkiv; denne fila seeder postene og eier mappingen
 * arkivside-slug → CPT-arkivet den vises på.
 *
 * Erstatter den utgåtte ACF options-siden «Arkivsider»
 * (mu-plugins/bimverdi-archive-options.php, slettet 11.08). Seedingen leser
 * de gamle verdiene rett fra wp_options (options_{slug}_tittel/_ingress) slik
 * at innhold redigert der overlever flyttingen — også på prod, der verdiene
 * kan avvike fra localhost.
 *
 * Visning: parts/components/archive-intro.php slår opp posten via
 * bv_arkivside_post() og rendrer post_content med bv_redigerbar_topp_html().
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * De seks faste arkivsidene.
 *
 * Nøkkel = post-slug for arkivside-posten OG 'acf_prefix' malene allerede
 * sender til archive-intro (samme navn som feltene på den gamle options-siden).
 * 'cpt' = post type hvis arkiv toppen vises på. 'tittel'/'ingress' = standard
 * når wp_options ikke har noen gammel verdi — holdes i synk med fallbackene i
 * archive-*.php-malene.
 */
function bv_arkivside_definisjoner() {
    return [
        'deltakere' => [
            'cpt'     => 'foretak',
            'tittel'  => 'Deltakere',
            'ingress' => 'Utforsk nettverket av foretak som samarbeider for økt produktivitet i byggenæringen.',
        ],
        'verktoy' => [
            'cpt'     => 'verktoy',
            'tittel'  => 'Verktøykatalog',
            'ingress' => 'Digitale verktøy og løsninger fra BIM Verdi-nettverket.',
        ],
        'kunnskapskilder' => [
            'cpt'     => 'kunnskapskilde',
            'tittel'  => 'Kunnskapskilder',
            'ingress' => 'Standarder, veiledere og ressurser fra BIM Verdi-nettverket.',
        ],
        'arrangement' => [
            'cpt'     => 'arrangement',
            'tittel'  => 'Arrangementer',
            'ingress' => 'Kurs, seminarer, workshops og nettverksmøter i BIM Verdi.',
        ],
        'artikler' => [
            'cpt'     => 'artikkel',
            'tittel'  => 'Artikler',
            'ingress' => 'Fagstoff og erfaringer fra deltakere i nettverket. Les om prosjekter, metoder og nye løsninger.',
        ],
        'temagrupper' => [
            'cpt'     => 'theme_group',
            'tittel'  => 'Temagrupper',
            'ingress' => 'BIM Verdis temagrupper arbeider med ulike fokusområder innen BIM og digitalisering.',
        ],
    ];
}

/**
 * Hent den publiserte arkivside-posten for en gitt slug, eller null.
 */
function bv_arkivside_post($slug) {
    if (!isset(bv_arkivside_definisjoner()[$slug])) {
        return null;
    }
    $post = get_page_by_path($slug, OBJECT, 'arkivside');
    if (!$post || $post->post_status !== 'publish') {
        return null;
    }
    return $post;
}

/**
 * Gjør en ren tekst-ingress om til Gutenberg-blokker.
 *
 * Options-feltet var et textarea, så verdien kan ha flere avsnitt skilt med
 * blank linje — prod-ingressen for `arrangement` har det. Pakket alt i én <p>
 * ville avsnittsbruddet forsvunnet i migreringen (teksten består, formen ikke),
 * så vi lager én paragraph-blokk per avsnitt og gjør enkle linjeskift til <br>.
 *
 * @param string $ingress Rå options-verdi.
 * @return string Blokk-markup, eller '' for tom ingress.
 */
function bv_arkivside_ingress_til_blokker($ingress) {
    if (!is_string($ingress) || trim($ingress) === '') {
        return '';
    }

    $ingress = str_replace(["\r\n", "\r"], "\n", $ingress);
    $avsnitt = preg_split('/\n[ \t]*\n+/', trim($ingress));

    $blokker = [];
    foreach ($avsnitt as $tekst) {
        $tekst = trim($tekst);
        if ($tekst === '') {
            continue;
        }
        // Enkelt linjeskift inne i et avsnitt beholdes som <br>.
        $tekst = str_replace("\n", '<br>', wp_kses_post($tekst));
        $blokker[] = '<!-- wp:paragraph --><p>' . $tekst . '</p><!-- /wp:paragraph -->';
    }

    return implode("\n\n", $blokker);
}

/**
 * Engangs-seeding: opprett de seks postene med innhold fra den gamle
 * options-siden (eller standardtekstene). Oppretter kun manglende slugs og
 * oppdaterer aldri en post som finnes, så den er trygg å kjøre om igjen —
 * også manuelt etter deploy: `wp eval 'bv_arkivsider_seed();'`.
 *
 * @return array Slugs som ble opprettet i denne kjøringen.
 */
function bv_arkivsider_seed() {
    $opprettet   = [];
    $alle_finnes = true;

    foreach (bv_arkivside_definisjoner() as $slug => $def) {
        if (get_page_by_path($slug, OBJECT, 'arkivside')) {
            continue;
        }

        // Gamle ACF options-verdier ligger som rene wp_options-rader og
        // trenger ikke ACF for å leses.
        $tittel  = get_option("options_{$slug}_tittel") ?: $def['tittel'];
        $ingress = get_option("options_{$slug}_ingress") ?: $def['ingress'];

        $post_id = wp_insert_post([
            'post_type'    => 'arkivside',
            'post_status'  => 'publish',
            'post_name'    => $slug,
            'post_title'   => is_string($tittel) ? $tittel : $def['tittel'],
            'post_content' => bv_arkivside_ingress_til_blokker($ingress),
        ], true);

        if (is_wp_error($post_id)) {
            $alle_finnes = false;
            continue;
        }

        $opprettet[] = $slug;
    }

    if ($alle_finnes) {
        update_option('bimverdi_arkivsider_seeded', 1);
    }

    return $opprettet;
}

/**
 * Kjøres i admin til alt finnes, deretter kortslutter flagget.
 */
add_action('admin_init', function () {
    if (get_option('bimverdi_arkivsider_seeded')) {
        return;
    }
    if (!current_user_can('edit_posts')) {
        return;
    }
    bv_arkivsider_seed();
});
