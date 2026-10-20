<?php
/**
 * Innlogging: velkomstkort med bilde og tekst (Halvor og Bård)
 *
 * Venstre side av /logg-inn/ viser sitatkort med bilde, navn og rolle, i stedet
 * for den generelle verdi-teksten. Bård (UKE42 / #360 pkt 4.3, bestilt 13.10.2026)
 * redigerer selv under Innstillinger → Innlogging: velkomst.
 *
 * Kortene lagres i ACF-repeateren `innlogging_velkomst_kort` (options). Er den
 * tom, vises standardkortene under, så siden aldri står blank.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('acf/init', function () {
    if (!function_exists('acf_add_options_page') || !function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_options_page([
        'page_title'  => 'Velkomst på innloggingssiden',
        'menu_title'  => 'Innlogging: velkomst',
        'menu_slug'   => 'bimverdi-innlogging-velkomst',
        'capability'  => 'manage_options',
        'parent_slug' => 'options-general.php',
        'redirect'    => false,
    ]);

    acf_add_local_field_group([
        'key'    => 'group_bv_innlogging_velkomst',
        'title'  => 'Velkomstkort',
        'fields' => [
            [
                'key'          => 'field_bv_innlogging_kort',
                'label'        => 'Kort',
                'name'         => 'innlogging_velkomst_kort',
                'type'         => 'repeater',
                'instructions' => 'Kortene vises til venstre på innloggingssiden, i rekkefølgen under. Maks fire. Er listen tom, vises standardtekstene.',
                'min'          => 0,
                'max'          => 4,
                'layout'       => 'row',
                'button_label' => 'Legg til kort',
                'sub_fields'   => [
                    [
                        'key'   => 'field_bv_innlogging_kort_sitat',
                        'label' => 'Tekst',
                        'name'  => 'sitat',
                        'type'  => 'textarea',
                        'rows'  => 5,
                    ],
                    [
                        'key'   => 'field_bv_innlogging_kort_navn',
                        'label' => 'Navn',
                        'name'  => 'navn',
                        'type'  => 'text',
                    ],
                    [
                        'key'   => 'field_bv_innlogging_kort_rolle',
                        'label' => 'Rolle',
                        'name'  => 'rolle',
                        'type'  => 'text',
                    ],
                    [
                        'key'          => 'field_bv_innlogging_kort_bilde',
                        'label'        => 'Bilde',
                        'name'         => 'bilde',
                        'type'         => 'image',
                        'return_format' => 'id',
                        'preview_size' => 'thumbnail',
                        'library'      => 'all',
                        'instructions' => 'Kvadratisk bilde fungerer best. Vises som liten rund avatar.',
                    ],
                ],
            ],
        ],
        'location' => [[[
            'param'    => 'options_page',
            'operator' => '==',
            'value'    => 'bimverdi-innlogging-velkomst',
        ]]],
        'menu_order' => 0,
        'position'   => 'normal',
        'style'      => 'default',
        'active'     => true,
    ]);
});

/** Standardkort hvis ingenting er lagt inn i admin. */
function bimverdi_innlogging_velkomst_standard() {
    return [
        [
            'sitat' => 'Jeg har brukt store deler av karrieren på å hjelpe byggenæringen med å ta i bruk ny teknologi og jobbe smartere. Med ByggChat som satsing får vi en spennende mulighet til å gjøre kunnskap og standarder lettere tilgjengelig, og bruke dem aktivt i praktiske problemstillinger. Det er nettopp i møtet mellom fagkunnskap, data og teknologi jeg tror vi kan skape stor verdi.',
            'navn'  => 'Halvor Jensen',
            'rolle' => 'Fagrådgiver BIM Verdi',
            'bilde' => 0,
        ],
        [
            'sitat' => 'Læring skjer best i praktiske prosjekter - gjerne sammen med nettverkskollegaer. Vi er alle en liten del av en helhetlig løsning i en stadig mer sammensatt og kompleks verden. Min rolle er å forstå behov og utfordringer og finne kunder og samarbeidspartnere som ønsker å spille på lag - i nettverk.',
            'navn'  => 'Bård Krogshus',
            'rolle' => 'Nettverksleder i BIM Verdi og bygger av verdinettverk',
            'bilde' => 0,
        ],
    ];
}

/** Kortene som skal vises: fra admin, ellers standard. Rader uten tekst hoppes over. */
function bimverdi_innlogging_velkomst_kort() {
    $rader = function_exists('get_field') ? get_field('innlogging_velkomst_kort', 'option') : [];
    $kort  = [];
    if (is_array($rader)) {
        foreach ($rader as $rad) {
            if (!empty($rad['sitat'])) {
                $kort[] = $rad;
            }
        }
    }
    return $kort ?: bimverdi_innlogging_velkomst_standard();
}

/** Skriver ut kortene (brukes av parts/auth/login.php). Alt escapes her. */
function bimverdi_innlogging_velkomst_render() {
    echo '<div class="velkomst-kort-liste">';
    foreach (bimverdi_innlogging_velkomst_kort() as $i => $k) {
        $navn  = trim((string) ($k['navn'] ?? ''));
        $rolle = trim((string) ($k['rolle'] ?? ''));
        $bilde = !empty($k['bilde']) ? wp_get_attachment_image_url((int) $k['bilde'], 'medium') : '';

        echo '<figure class="velkomst-kort velkomst-kort--' . ($i % 2 === 0 ? 'a' : 'b') . '">';
        echo '<blockquote>' . nl2br(esc_html((string) $k['sitat'])) . '</blockquote>';
        if ($navn !== '' || $rolle !== '') {
            echo '<figcaption class="velkomst-person">';
            if ($bilde) {
                echo '<img src="' . esc_url($bilde) . '" alt="' . esc_attr($navn) . '" width="44" height="44" loading="lazy">';
            } elseif ($navn !== '') {
                $ord      = preg_split('/\s+/', $navn);
                $initialer = mb_strtoupper(mb_substr($ord[0], 0, 1) . (count($ord) > 1 ? mb_substr(end($ord), 0, 1) : ''));
                echo '<span class="velkomst-initialer" aria-hidden="true">' . esc_html($initialer) . '</span>';
            }
            echo '<span class="velkomst-tekst">';
            if ($navn !== '') {
                echo '<strong>' . esc_html($navn) . '</strong>';
            }
            if ($rolle !== '') {
                echo '<span>' . esc_html($rolle) . '</span>';
            }
            echo '</span></figcaption>';
        }
        echo '</figure>';
    }
    echo '</div>';
}
