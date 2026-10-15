<?php
/**
 * BIM Verdi - Fyll tomme BRREG-felt på foretak
 *
 * Bakgrunn (#360 punkt 1.1, IFE): BRREG-fanen i wp-admin var tom for rundt
 * 45 av 104 foretak. To årsaker:
 *  1. Synken ved opprettelse (bimverdi-brreg-api.php) lagret bare navn, adresse
 *     og antall ansatte — aldri organisasjonsform, næringskode, kommune eller
 *     stiftelsesdato.
 *  2. Registreringsflytene (gratis og betalt) oppretter foretaket FØR org.nr
 *     settes, så synken på save_post_foretak fant aldri et org.nr.
 *
 * Løsning: én funksjon som henter enheten fra BRREG og fyller KUN tomme felt.
 * Den overskriver aldri noe som allerede står der, og rører ikke post_title.
 * Den kjøres:
 *  - automatisk når org.nr legges til eller endres på et foretak. Kjøringen
 *    skjer på shutdown, etter at ACF/skjemaet er ferdig med å lagre, ellers
 *    kunne ACF skrive tomme readonly-felt tilbake over verdiene våre.
 *  - som engangsutfylling via WP-CLI:
 *      wp bv-brreg-fyll --dry-run          (vis hva som ville blitt fylt)
 *      wp bv-brreg-fyll --id=2955          (ett eller flere foretak, kommaseparert)
 *      wp bv-brreg-fyll                    (alle foretak)
 *
 * Lagringsformat følger det de eksisterende foretakene har: organisasjonsform
 * som kode («AS»), kommune med store bokstaver slik BRREG gir den, og
 * stiftelsesdato som Ymd (ACF date_picker).
 *
 * @package BIM_Verdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adressefeltene fylles bare som en blokk, og bare når ALLE er tomme.
 * Betalt registrering skriver «gateadresse», BRREG-synken skriver «adresse»;
 * vi skal ikke blande en BRREG-adresse inn i en adresse brukeren har oppgitt.
 */
function bimverdi_brreg_adressefelt() {
    return array('adresse', 'gateadresse', 'postnummer', 'poststed');
}

/**
 * Hent rå enhetsdata fra Enhetsregisteret.
 *
 * @param string $orgnr 9 siffer.
 * @return array|WP_Error
 */
function bimverdi_brreg_hent_enhet($orgnr) {
    $response = wp_remote_get(
        'https://data.brreg.no/enhetsregisteret/api/enheter/' . rawurlencode($orgnr),
        array(
            'timeout' => 10,
            'headers' => array('Accept' => 'application/json'),
        )
    );

    if (is_wp_error($response)) {
        return $response;
    }

    $kode = wp_remote_retrieve_response_code($response);
    if ($kode === 404 || $kode === 410) {
        return new WP_Error('brreg_finnes_ikke', 'Finnes ikke i Enhetsregisteret (HTTP ' . $kode . ')');
    }
    if ($kode !== 200) {
        return new WP_Error('brreg_http', 'BRREG svarte HTTP ' . $kode);
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($data) || empty($data['organisasjonsnummer'])) {
        return new WP_Error('brreg_ugyldig', 'Uventet svar fra BRREG');
    }

    return $data;
}

/**
 * Oversett BRREG-data til feltverdier i samme format som eksisterende foretak.
 * Tomme verdier utelates, så de aldri skrives.
 *
 * @param array $data Rå enhetsdata fra BRREG.
 * @return array felt => verdi (uten adresseblokken)
 */
function bimverdi_brreg_feltverdier($data) {
    $addr = $data['forretningsadresse'] ?? ($data['postadresse'] ?? array());

    $verdier = array(
        'bedriftsnavn'             => (string) ($data['navn'] ?? ''),
        'organisasjonsform'        => (string) ($data['organisasjonsform']['kode'] ?? ''),
        'naeringskode'             => (string) ($data['naeringskode1']['kode'] ?? ''),
        'naeringskode_beskrivelse' => (string) ($data['naeringskode1']['beskrivelse'] ?? ''),
        'kommune'                  => (string) ($addr['kommune'] ?? ''),
        // ACF date_picker lagrer Ymd; BRREG gir Y-m-d.
        'stiftelsesdato'           => str_replace('-', '', (string) ($data['stiftelsesdato'] ?? '')),
    );

    // BRREG gir antallAnsatte = 0 også når antall ikke er registrert. Da lar vi
    // feltet stå tomt i stedet for å vise «0 ansatte».
    if (!empty($data['harRegistrertAntallAnsatte']) || !empty($data['antallAnsatte'])) {
        $verdier['antall_ansatte'] = (string) intval($data['antallAnsatte'] ?? 0);
    }

    return array_filter($verdier, function ($v) {
        return $v !== '';
    });
}

/**
 * Adresseblokken fra BRREG.
 *
 * @param array $data Rå enhetsdata fra BRREG.
 * @return array felt => verdi, tom hvis BRREG ikke har adresse.
 */
function bimverdi_brreg_adresseverdier($data) {
    $addr = $data['forretningsadresse'] ?? ($data['postadresse'] ?? array());
    if (empty($addr['adresse']) && empty($addr['postnummer'])) {
        return array();
    }

    return array_filter(array(
        'adresse'    => implode(', ', (array) ($addr['adresse'] ?? array())),
        'postnummer' => (string) ($addr['postnummer'] ?? ''),
        'poststed'   => (string) ($addr['poststed'] ?? ''),
        'land'       => (string) ($addr['land'] ?? 'Norge'),
    ), function ($v) {
        return $v !== '';
    });
}

/**
 * Er post-meta tom? Leser rå meta, ikke get_field(): acf/load_value-filteret
 * i bimverdi-admin-enhancements.php fyller tomme adressefelt fra BRREG ved
 * visning, så get_field() ville sagt at feltet har innhold.
 */
function bimverdi_brreg_meta_tom($post_id, $felt) {
    $verdi = get_post_meta($post_id, $felt, true);
    return $verdi === '' || $verdi === null || $verdi === false;
}

/**
 * Fyll tomme BRREG-felt på ett foretak. Overskriver aldri.
 *
 * @param int        $post_id Foretak-ID.
 * @param array|null $data    Rå BRREG-data hvis kalleren allerede har hentet dem.
 * @param bool       $dry_run Bare rapporter, ikke skriv.
 * @return array status (fylt|ingen_endring|hoppet_over|feil), felt (felt => verdi), melding
 */
function bimverdi_brreg_fyll_tomme_felt($post_id, $data = null, $dry_run = false) {
    $resultat = array('status' => 'ingen_endring', 'felt' => array(), 'melding' => '');

    if (get_post_type($post_id) !== 'foretak') {
        $resultat['status'] = 'hoppet_over';
        $resultat['melding'] = 'Ikke et foretak';
        return $resultat;
    }

    $land = trim((string) get_post_meta($post_id, 'land', true));
    if ($land !== '' && !in_array(strtolower($land), array('norge', 'norway', 'no', 'nor'), true)) {
        $resultat['status'] = 'hoppet_over';
        $resultat['melding'] = 'Utenlandsk foretak (' . $land . ')';
        return $resultat;
    }

    $orgnr = preg_replace('/\s+/', '', (string) get_post_meta($post_id, 'organisasjonsnummer', true));
    if (!preg_match('/^\d{9}$/', $orgnr)) {
        $resultat['status'] = 'hoppet_over';
        $resultat['melding'] = 'Mangler gyldig org.nr' . ($orgnr !== '' ? ' («' . $orgnr . '»)' : '');
        return $resultat;
    }

    // Spar BRREG-kallet hvis alt allerede er utfylt.
    $tomme = array_filter(
        array('bedriftsnavn', 'organisasjonsform', 'naeringskode', 'naeringskode_beskrivelse', 'kommune', 'stiftelsesdato', 'antall_ansatte'),
        function ($felt) use ($post_id) {
            return bimverdi_brreg_meta_tom($post_id, $felt);
        }
    );
    $adresse_tom = true;
    foreach (bimverdi_brreg_adressefelt() as $felt) {
        if (!bimverdi_brreg_meta_tom($post_id, $felt)) {
            $adresse_tom = false;
            break;
        }
    }
    if (empty($tomme) && !$adresse_tom) {
        return $resultat;
    }

    if ($data === null) {
        $data = bimverdi_brreg_hent_enhet($orgnr);
    }
    if (is_wp_error($data)) {
        $resultat['status'] = 'feil';
        $resultat['melding'] = $data->get_error_message();
        return $resultat;
    }

    $endringer = array_intersect_key(bimverdi_brreg_feltverdier($data), array_flip($tomme));
    if ($adresse_tom) {
        foreach (bimverdi_brreg_adresseverdier($data) as $felt => $verdi) {
            // «land» kan allerede være satt (f.eks. «Norge») selv om adressen mangler.
            if (bimverdi_brreg_meta_tom($post_id, $felt)) {
                $endringer[$felt] = $verdi;
            }
        }
    }

    if (empty($endringer)) {
        return $resultat;
    }

    $resultat['status'] = 'fylt';
    $resultat['felt'] = $endringer;

    if ($dry_run) {
        return $resultat;
    }

    // Skriv innenfor bimverdi_brreg_sync, så bimverdi_protect_brreg_fields slipper
    // gjennom de låste feltene (bedriftsnavn, adresse ...) uten et ekstra BRREG-kall.
    $skriv = function () use ($post_id, $endringer) {
        foreach ($endringer as $felt => $verdi) {
            if (function_exists('update_field')) {
                update_field($felt, $verdi, $post_id);
            } else {
                update_post_meta($post_id, $felt, $verdi);
            }
        }
    };
    add_action('bimverdi_brreg_sync', $skriv);
    do_action('bimverdi_brreg_sync');
    remove_action('bimverdi_brreg_sync', $skriv);

    if (function_exists('bimverdi_purge_foretak_cache')) {
        bimverdi_purge_foretak_cache($post_id);
    }

    return $resultat;
}

/**
 * Ikke la et redigeringsskjema blanke ut BRREG-felt som er fylt.
 *
 * BRREG-fanen i wp-admin er readonly, men readonly-felt sendes likevel med ved
 * lagring. Et skjema som ble åpnet før feltene ble fylt (eller en ny post der
 * shutdown-utfyllingen ennå ikke har kjørt) sender tomme verdier, og ACF ville
 * skrevet dem over dataene. Tom innkommende verdi + utfylt lagret verdi =
 * behold den lagrede. Ikke-tomme verdier slipper gjennom som før.
 */
add_filter('acf/update_value', 'bimverdi_brreg_ikke_blank_ut', 20, 3);

function bimverdi_brreg_ikke_blank_ut($value, $post_id, $field) {
    $vaktede = array('organisasjonsform', 'naeringskode', 'naeringskode_beskrivelse', 'kommune', 'stiftelsesdato', 'antall_ansatte', 'bedriftsnavn');
    if (!in_array($field['name'] ?? '', $vaktede, true) || !is_numeric($post_id) || get_post_type($post_id) !== 'foretak') {
        return $value;
    }
    if ($value !== '' && $value !== null && $value !== false) {
        return $value;
    }
    $lagret = get_post_meta($post_id, $field['name'], true);
    return ($lagret !== '' && $lagret !== null && $lagret !== false) ? $lagret : $value;
}

/**
 * Når org.nr legges til eller endres på et foretak: fyll tomme BRREG-felt
 * på shutdown. Dekker alle opprettelsesveier (gratis- og betalt registrering,
 * wp-admin, import), som alle setter org.nr etter wp_insert_post.
 * update_metadata() fyrer ikke updated_post_meta når verdien er uendret,
 * så vanlige lagringer i wp-admin trigger ikke noe BRREG-kall.
 */
add_action('added_post_meta', 'bimverdi_brreg_koe_ved_orgnr', 10, 3);
add_action('updated_post_meta', 'bimverdi_brreg_koe_ved_orgnr', 10, 3);

function bimverdi_brreg_koe_ved_orgnr($meta_id, $post_id, $meta_key) {
    if ($meta_key !== 'organisasjonsnummer' || get_post_type($post_id) !== 'foretak') {
        return;
    }

    if (empty($GLOBALS['bimverdi_brreg_koe'])) {
        $GLOBALS['bimverdi_brreg_koe'] = array();
        add_action('shutdown', 'bimverdi_brreg_kjor_koe');
    }
    $GLOBALS['bimverdi_brreg_koe'][(int) $post_id] = true;
}

function bimverdi_brreg_kjor_koe() {
    foreach (array_keys($GLOBALS['bimverdi_brreg_koe'] ?? array()) as $post_id) {
        $resultat = bimverdi_brreg_fyll_tomme_felt($post_id);
        if ($resultat['status'] === 'fylt' || $resultat['status'] === 'feil') {
            error_log(sprintf(
                'BIM Verdi BRREG-utfylling foretak %d: %s %s',
                $post_id,
                $resultat['status'],
                $resultat['status'] === 'fylt' ? implode(', ', array_keys($resultat['felt'])) : $resultat['melding']
            ));
        }
    }
    $GLOBALS['bimverdi_brreg_koe'] = array();
}

/**
 * Engangsutfylling: wp bv-brreg-fyll [--dry-run] [--id=<id,id>]
 */
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('bv-brreg-fyll', function ($args, $assoc_args) {
        $dry_run = !empty($assoc_args['dry-run']);

        if (!empty($assoc_args['id'])) {
            $ider = array_map('intval', explode(',', $assoc_args['id']));
        } else {
            $ider = get_posts(array(
                'post_type'      => 'foretak',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'orderby'        => 'ID',
                'order'          => 'ASC',
            ));
        }

        $teller = array('fylt' => 0, 'ingen_endring' => 0, 'hoppet_over' => 0, 'feil' => 0);
        $felt_teller = array();

        foreach ($ider as $post_id) {
            $resultat = bimverdi_brreg_fyll_tomme_felt($post_id, null, $dry_run);
            $teller[$resultat['status']]++;

            if ($resultat['status'] === 'fylt') {
                $deler = array();
                foreach ($resultat['felt'] as $felt => $verdi) {
                    $deler[] = $felt . '=' . $verdi;
                    $felt_teller[$felt] = ($felt_teller[$felt] ?? 0) + 1;
                }
                WP_CLI::log(sprintf('%s %d %s: %s', $dry_run ? 'VILLE FYLT' : 'FYLT', $post_id, get_the_title($post_id), implode(' | ', $deler)));
                usleep(150000); // skån BRREG-API-et
            } elseif ($resultat['status'] !== 'ingen_endring') {
                WP_CLI::log(sprintf('%s %d %s: %s', strtoupper($resultat['status']), $post_id, get_the_title($post_id), $resultat['melding']));
            }
        }

        WP_CLI::log('');
        WP_CLI::log(sprintf(
            'Foretak: %d | %s: %d | allerede komplett: %d | hoppet over: %d | feil: %d',
            count($ider),
            $dry_run ? 'ville blitt fylt' : 'fylt',
            $teller['fylt'],
            $teller['ingen_endring'],
            $teller['hoppet_over'],
            $teller['feil']
        ));
        if ($felt_teller) {
            ksort($felt_teller);
            $deler = array();
            foreach ($felt_teller as $felt => $antall) {
                $deler[] = "$felt: $antall";
            }
            WP_CLI::log('Per felt: ' . implode(', ', $deler));
        }

        if ($dry_run) {
            WP_CLI::success('Prøvekjøring ferdig. Ingenting er skrevet.');
        } else {
            WP_CLI::success('Ferdig.');
        }
    });
}
