<?php
/**
 * BIM Verdi - Geografisk gyldighet for kunnskapskilder (regionalt nivå)
 *
 * Bård (Trello): ny verdi «Regionalt» i feltet «Geografisk gyldighet», slik at en
 * kilde kan gjelde ett eller flere fylker og eventuelt utvalgte kommuner i dem.
 * Eksempel: byggeregler for Oslo kommune og Akershus fylkeskommune.
 *
 * Lagring (ACF, post meta på kunnskapskilde):
 * - geografisk_gyldighet  streng, f.eks. «regionalt» (uendret felt, nytt valg)
 * - geografisk_fylker     liste med fylkesnummer som strenger, f.eks. ["03","32"]
 * - geografisk_kommuner   liste med kommunenummer som strenger, f.eks. ["0301","3201"]
 * Vi lagrer nummer, ikke navn, så navnebytter ikke ødelegger data. Navnene kommer
 * fra datafila bim-verdi-core/data/fylker-kommuner.json (Kartverket, 2024-inndelingen).
 *
 * Regler (bimverdi_geo_normaliser):
 * - Et fylke uten valgte kommuner betyr «hele fylket».
 * - En valgt kommune tar alltid med fylket sitt.
 * - Et fylke med bare én kommune (Oslo) tar alltid med den kommunen.
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Nivåene i «Geografisk gyldighet», fra smalest til videst.
 * Samme liste står i ACF-JSON-en (group_kunnskapskilde_info.json).
 */
function bimverdi_geo_gyldighet_valg() {
    return [
        'regionalt'      => 'Regionalt (fylke/kommune)',
        'nasjonalt'      => 'Nasjonalt/Norsk',
        'nordisk'        => 'Nordisk',
        'europeisk'      => 'Europeisk',
        'internasjonalt' => 'Internasjonalt',
        'annet'          => 'Annet',
    ];
}

/** Kort etikett for visning (uten forklaringen i parentes). */
function bimverdi_geo_niva_label($slug) {
    if ($slug === 'regionalt') {
        return 'Regionalt';
    }
    $valg = bimverdi_geo_gyldighet_valg();
    return $valg[$slug] ?? (string) $slug;
}

/**
 * Fylker og kommuner fra datafila. Bufres per forespørsel.
 *
 * @return array{fylker: array, kommuner: array}
 *   fylker:   nr => ['nr', 'navn', 'kommuner' => [kommunenr, ...]]  (alfabetisk)
 *   kommuner: nr => ['nr', 'navn', 'andre_navn' => [...], 'fylke' => fylkesnr]
 *   Merk: PHP gjør nøkler som «32» om til heltall. Les alltid 'nr' fra verdien.
 */
function bimverdi_geo_data() {
    static $data = null;
    if ($data !== null) {
        return $data;
    }

    $data = ['fylker' => [], 'kommuner' => []];
    $fil  = WP_PLUGIN_DIR . '/bim-verdi-core/data/fylker-kommuner.json';
    if (!is_readable($fil)) {
        error_log('BIMVerdi: fant ikke ' . $fil);
        return $data;
    }

    $json = json_decode((string) file_get_contents($fil), true);
    foreach (($json['fylker'] ?? []) as $fylke) {
        $fnr = (string) $fylke['nr'];
        $data['fylker'][$fnr] = ['nr' => $fnr, 'navn' => $fylke['navn'], 'kommuner' => []];
        foreach (($fylke['kommuner'] ?? []) as $kommune) {
            $knr = (string) $kommune['nr'];
            $data['kommuner'][$knr] = [
                'nr'         => $knr,
                'navn'       => $kommune['navn'],
                'andre_navn' => (array) ($kommune['andre_navn'] ?? []),
                'fylke'      => $fnr,
            ];
            $data['fylker'][$fnr]['kommuner'][] = $knr;
        }
    }

    return $data;
}

function bimverdi_geo_fylke_navn($nr) {
    $data = bimverdi_geo_data();
    return $data['fylker'][(string) $nr]['navn'] ?? '';
}

function bimverdi_geo_kommune_navn($nr) {
    $data = bimverdi_geo_data();
    return $data['kommuner'][(string) $nr]['navn'] ?? '';
}

/**
 * Rydd og fullfør et valg av fylker og kommuner (se reglene øverst i fila).
 * Ukjente nummer forkastes. Resultatet følger rekkefølgen i datafila.
 *
 * @return array{fylker: string[], kommuner: string[]}
 */
function bimverdi_geo_normaliser($fylker, $kommuner) {
    $data = bimverdi_geo_data();

    $valgte_fylker = [];
    foreach ((array) $fylker as $nr) {
        $nr = trim((string) $nr);
        if ($nr !== '' && isset($data['fylker'][$nr])) {
            $valgte_fylker[$nr] = true;
        }
    }

    $valgte_kommuner = [];
    foreach ((array) $kommuner as $nr) {
        $nr = trim((string) $nr);
        if ($nr !== '' && isset($data['kommuner'][$nr])) {
            $valgte_kommuner[$nr] = true;
            $valgte_fylker[$data['kommuner'][$nr]['fylke']] = true;
        }
    }

    $ut_fylker   = [];
    $ut_kommuner = [];
    foreach ($data['fylker'] as $fylke) {
        if (empty($valgte_fylker[$fylke['nr']])) {
            continue;
        }
        $ut_fylker[] = $fylke['nr'];
        if (count($fylke['kommuner']) === 1) {
            $valgte_kommuner[$fylke['kommuner'][0]] = true;
        }
        foreach ($fylke['kommuner'] as $knr) {
            if (!empty($valgte_kommuner[$knr])) {
                $ut_kommuner[] = (string) $knr;
            }
        }
    }

    return ['fylker' => $ut_fylker, 'kommuner' => $ut_kommuner];
}

/**
 * Geografisk gyldighet for en kilde.
 *
 * @return array{niva: string, fylker: string[], kommuner: string[]}
 */
function bimverdi_geo_get($post_id) {
    $hent = function ($navn) use ($post_id) {
        return function_exists('get_field') ? get_field($navn, $post_id, false) : get_post_meta($post_id, $navn, true);
    };

    $niva = $hent('geografisk_gyldighet');
    if (is_array($niva)) {
        $niva = reset($niva);
    }
    $niva = (string) $niva;

    if ($niva !== 'regionalt') {
        return ['niva' => $niva, 'fylker' => [], 'kommuner' => []];
    }

    $valg = bimverdi_geo_normaliser((array) $hent('geografisk_fylker'), (array) $hent('geografisk_kommuner'));
    return ['niva' => $niva] + $valg;
}

/**
 * Regionene som tekstlinjer, én per fylke:
 * «Oslo», «Akershus (hele fylket)», «Akershus: Asker, Bærum».
 *
 * @return string[]
 */
function bimverdi_geo_region_linjer($fylker, $kommuner) {
    $data   = bimverdi_geo_data();
    $linjer = [];
    foreach ((array) $fylker as $fnr) {
        $fylke = $data['fylker'][(string) $fnr] ?? null;
        if (!$fylke) {
            continue;
        }
        if (count($fylke['kommuner']) === 1) {
            $linjer[] = $fylke['navn'];
            continue;
        }
        $navn = [];
        foreach ($fylke['kommuner'] as $knr) {
            if (in_array((string) $knr, array_map('strval', (array) $kommuner), true)) {
                $navn[] = $data['kommuner'][$knr]['navn'];
            }
        }
        $linjer[] = $navn
            ? $fylke['navn'] . ': ' . implode(', ', $navn)
            : $fylke['navn'] . ' (hele fylket)';
    }
    return $linjer;
}

/**
 * Filter-nøkler for arkivet: nivået, og for regionale kilder «fylke-NN» per fylke.
 *
 * @return string[]
 */
function bimverdi_geo_filter_tokens($geo) {
    if (empty($geo['niva'])) {
        return [];
    }
    $tokens = [$geo['niva']];
    foreach ($geo['fylker'] as $fnr) {
        $tokens[] = 'fylke-' . $fnr;
    }
    return $tokens;
}

/* -------------------------------------------------------------------------
 * ACF (wp-admin): valgene i fylke- og kommunefeltene fylles fra datafila.
 * Vi bruker prepare_field (kun ved visning), så valgene ikke skrives inn i
 * ACF-JSON-en hvis noen lagrer feltgruppen.
 * ---------------------------------------------------------------------- */

add_filter('acf/prepare_field/name=geografisk_fylker', function ($field) {
    if (!$field) {
        return $field;
    }
    $valg = [];
    foreach (bimverdi_geo_data()['fylker'] as $fylke) {
        $valg[$fylke['nr']] = $fylke['navn'];
    }
    $field['choices'] = $valg;
    return $field;
});

add_filter('acf/prepare_field/name=geografisk_kommuner', function ($field) {
    if (!$field) {
        return $field;
    }
    $data = bimverdi_geo_data();
    $valg = [];
    foreach ($data['fylker'] as $fylke) {
        foreach ($fylke['kommuner'] as $knr) {
            $kommune = $data['kommuner'][$knr];
            $navn    = $kommune['navn'];
            if ($kommune['andre_navn']) {
                $navn .= ' (' . implode(', ', $kommune['andre_navn']) . ')';
            }
            $valg[$fylke['navn']][$kommune['nr']] = $navn;
        }
    }
    $field['choices'] = $valg;
    return $field;
});

/**
 * Etter lagring i wp-admin: rydd valget etter samme regler som Min Side,
 * og tøm fylker/kommuner når nivået ikke er «regionalt».
 */
add_action('acf/save_post', function ($post_id) {
    if (!is_numeric($post_id) || get_post_type($post_id) !== 'kunnskapskilde') {
        return;
    }

    $niva = get_field('geografisk_gyldighet', $post_id, false);
    if (is_array($niva)) {
        $niva = reset($niva);
    }

    if ($niva !== 'regionalt') {
        if (get_post_meta($post_id, 'geografisk_fylker', true) || get_post_meta($post_id, 'geografisk_kommuner', true)) {
            update_field('geografisk_fylker', [], $post_id);
            update_field('geografisk_kommuner', [], $post_id);
        }
        return;
    }

    $valg = bimverdi_geo_normaliser(
        (array) get_field('geografisk_fylker', $post_id, false),
        (array) get_field('geografisk_kommuner', $post_id, false)
    );
    update_field('geografisk_fylker', $valg['fylker'], $post_id);
    update_field('geografisk_kommuner', $valg['kommuner'], $post_id);
}, 20);
