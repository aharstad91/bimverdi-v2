<?php
/**
 * Redigerbar topp — admin-styrt Gutenberg-blokk øverst på en CPT-side.
 *
 * Bård, synk 06.08: «redigeringsmuligheter i toppen av deltakere, kunnskap,
 * arrangement, artikler og verktøy, på samme måte som det ligger i temagruppa».
 * Temagruppe-siden har dette fra før via post_content (single-theme_group.php,
 * `.tg-intro`). Denne helperen gir samme mulighet på CPT-er der post_content
 * ellers står ubrukt, uten å kopiere logikken inn i hver mal.
 *
 * LEGACY-VAKT — hvorfor denne fila er mer enn `the_content()`:
 * På verktøy og kunnskapskilder ligger det importrester i post_content som er
 * en ren kopi av ACF-feltet malen viser fra før. Verifisert i basen 10.08:
 * verktøy 296 (Loopfront) og 297 (Autodesk) har post_content tegn-for-tegn lik
 * `detaljert_beskrivelse`, og 299 (NOBB) skiller seg bare på tegnsetting.
 * Naiv visning ville derfor vist beskrivelsen TO ganger på de sidene i samme
 * øyeblikk feltet ble skrudd på. Vi sammenligner mot de feltene malen allerede
 * rendrer og hopper over innhold som bare speiler dem. Så snart noen skriver
 * ekte topp-tekst avviker den fra speilet, og blokken vises som forventet.
 *
 * Vakten er altså en overgangsordning, ikke permanent logikk: ryddes
 * importrestene bort, blir den en no-op som ikke skader.
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Normaliser tekst for sammenligning: uten markup, entiteter eller
 * mellomrom-forskjeller. Brukes KUN til å kjenne igjen speilet innhold —
 * aldri til utdata.
 */
if (!function_exists('bv_topp_normaliser')) {
    function bv_topp_normaliser($tekst) {
        $tekst = wp_strip_all_tags((string) $tekst);
        $tekst = html_entity_decode($tekst, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Slå sammen alt whitespace (inkl. nbsp) til enkle mellomrom.
        $tekst = preg_replace('/[\s\x{00A0}]+/u', ' ', $tekst);
        // Fjern tegnsetting som varierer mellom import og ACF-kopi.
        $tekst = preg_replace('/[^\p{L}\p{N} ]/u', '', $tekst);
        return trim(mb_strtolower($tekst, 'UTF-8'));
    }
}

/**
 * Er de to tekstene i praksis samme tekst?
 *
 * Eksakt sammenligning er ikke nok. Importrestene har drevet fra ACF-feltet
 * etter at noen oppdaterte sistnevnte: verktøy 299 (NOBB) har «120 000 unike
 * varer» i post_content mot «180 000» i `detaljert_beskrivelse` — 99,8 % like,
 * men ikke identiske. Vises begge, står det to ulike tall på samme side.
 *
 * Vi krever derfor bare at tekstene er nesten like. Terskelen er satt høyt nok
 * til at to genuint forskjellige tekster aldri treffer den; lengdesjekken
 * først gjør at similar_text() (som er kostbar) sjelden kjøres.
 */
if (!function_exists('bv_topp_er_speil')) {
    function bv_topp_er_speil($a, $b) {
        if ($a === '' || $b === '') {
            return false;
        }
        if ($a === $b) {
            return true;
        }
        $len_a = mb_strlen($a);
        $len_b = mb_strlen($b);
        // Ulik lengde på mer enn 10 % ⇒ ikke samme tekst. Kutter også
        // similar_text() bort for de aller fleste sammenligninger.
        if (abs($len_a - $len_b) > max($len_a, $len_b) * 0.10) {
            return false;
        }
        // Perf-vakt: similar_text er O(n³) i verste fall.
        if (max($len_a, $len_b) > 20000) {
            return false;
        }
        similar_text($a, $b, $prosent);
        return $prosent >= 92.0;
    }
}

/**
 * Hent ferdig rendret HTML for den redigerbare toppen, eller '' hvis den ikke
 * skal vises.
 *
 * @param int|null $post_id        Standard: gjeldende post.
 * @param array    $speilede_felt  ACF-feltnavn malen allerede viser. Er
 *                                 post_content bare en kopi av ett av dem,
 *                                 returneres '' (se legacy-vakten over).
 *                                 Tittelen sjekkes alltid og trenger ikke
 *                                 oppgis — oppgi H1-feltet her hvis malen
 *                                 bruker et ACF-felt som overskrift.
 * @return string
 */
if (!function_exists('bv_redigerbar_topp_html')) {
    function bv_redigerbar_topp_html($post_id = null, array $speilede_felt = []) {
        $post_id = $post_id ?: get_the_ID();
        if (!$post_id) {
            return '';
        }

        $innhold = trim((string) get_post_field('post_content', $post_id));
        if ($innhold === '') {
            return '';
        }

        $normalisert = bv_topp_normaliser($innhold);
        if ($normalisert !== '') {
            // Tittelen står alltid som H1 rett over blokken, så innhold som
            // bare gjentar den er aldri nyttig. Gjelder alle kalleretter og
            // trenger ikke oppgis. (Kunnskapskilde 1110 hadde nøyaktig dette:
            // post_content = overskriften + et tomt avsnitt.)
            // Rå post_title, ikke get_the_title() — sistnevnte prefikser
            // «Private:»/«Protected:» og ville forstyrret sammenligningen.
            $speil = [get_post_field('post_title', $post_id)];

            if (function_exists('get_field')) {
                foreach ($speilede_felt as $felt) {
                    $speil[] = get_field($felt, $post_id);
                }
            }

            foreach ($speil as $eksisterende) {
                if (!is_string($eksisterende) || trim($eksisterende) === '') {
                    continue;
                }
                if (bv_topp_er_speil($normalisert, bv_topp_normaliser($eksisterende))) {
                    return '';   // Speil av noe siden viser fra før.
                }
            }
        }

        return apply_filters('the_content', $innhold);
    }
}

/**
 * Skriv ut den redigerbare toppen i full bredde, over hovedinnholdet.
 * Ingen ramme eller boks — informasjon skal ligge i luft, jf. UI-kontrakten.
 *
 * @param int|null $post_id
 * @param array    $speilede_felt Se bv_redigerbar_topp_html().
 */
if (!function_exists('bv_redigerbar_topp')) {
    function bv_redigerbar_topp($post_id = null, array $speilede_felt = []) {
        $html = bv_redigerbar_topp_html($post_id, $speilede_felt);
        if ($html === '') {
            return;
        }
        echo '<div class="bv-redigerbar-topp prose max-w-none text-[#57534E] mb-10">'
            . $html
            . '</div>';
    }
}
