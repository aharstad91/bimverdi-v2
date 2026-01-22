<?php
/**
 * Dummy Data Generator for Temagrupper
 *
 * Creates test content for theme group pages with "[Claude - dummydata]" prefix.
 * Run this once via WP Admin or WP-CLI, then remove/disable.
 *
 * Usage:
 * 1. Include this file in functions.php: require_once get_template_directory() . '/inc/dummy-data-temagrupper.php';
 * 2. Visit: yoursite.com/wp-admin/?generate_temagruppe_dummydata=1
 * 3. Remove the require_once line after data is created
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Generate dummy data when admin visits special URL
 */
add_action('admin_init', function() {
    if (!isset($_GET['generate_temagruppe_dummydata']) || !current_user_can('manage_options')) {
        return;
    }

    // Prevent timeout
    set_time_limit(300);

    // Get all temagruppe terms
    $temagrupper = get_terms([
        'taxonomy' => 'temagruppe',
        'hide_empty' => false,
    ]);

    if (empty($temagrupper) || is_wp_error($temagrupper)) {
        wp_die('Ingen temagrupper funnet. Opprett temagruppe-terms først.');
    }

    $results = [
        'kunnskapskilder' => 0,
        'arrangementer' => 0,
        'verktoy' => 0,
        'artikler' => 0,
    ];

    foreach ($temagrupper as $temagruppe) {
        $temagruppe_navn = $temagruppe->name;

        // ============================================
        // KUNNSKAPSKILDER (5-8 per temagruppe)
        // ============================================
        $kunnskapskilder_data = bimverdi_get_kunnskapskilder_for_temagruppe($temagruppe_navn);

        foreach ($kunnskapskilder_data as $kilde) {
            $post_id = wp_insert_post([
                'post_title'   => '[Claude - dummydata] ' . $kilde['title'],
                'post_content' => '',
                'post_status'  => 'publish',
                'post_type'    => 'kunnskapskilde',
            ]);

            if ($post_id && !is_wp_error($post_id)) {
                // Set taxonomy
                wp_set_object_terms($post_id, $temagruppe->term_id, 'temagruppe');

                // Set kategori taxonomy if exists
                if (!empty($kilde['kategori'])) {
                    wp_set_object_terms($post_id, $kilde['kategori'], 'kunnskapskildekategori');
                }

                // Set ACF fields
                if (function_exists('update_field')) {
                    update_field('kort_beskrivelse', $kilde['beskrivelse'], $post_id);
                    update_field('utgiver', $kilde['utgiver'], $post_id);
                    update_field('ekstern_lenke', $kilde['lenke'], $post_id);
                    update_field('spraak', $kilde['spraak'] ?? 'norsk', $post_id);
                    if (!empty($kilde['kildetype'])) {
                        update_field('kildetype', $kilde['kildetype'], $post_id);
                    }
                }

                $results['kunnskapskilder']++;
            }
        }

        // ============================================
        // ARRANGEMENTER (3-5 per temagruppe)
        // ============================================
        $arrangementer_data = bimverdi_get_arrangementer_for_temagruppe($temagruppe_navn);

        foreach ($arrangementer_data as $arrangement) {
            $post_id = wp_insert_post([
                'post_title'   => '[Claude - dummydata] ' . $arrangement['title'],
                'post_content' => $arrangement['beskrivelse'] ?? '',
                'post_status'  => 'publish',
                'post_type'    => 'arrangement',
            ]);

            if ($post_id && !is_wp_error($post_id)) {
                // Set taxonomy
                wp_set_object_terms($post_id, $temagruppe->term_id, 'temagruppe');

                // Set arrangementstype
                if (!empty($arrangement['type'])) {
                    wp_set_object_terms($post_id, $arrangement['type'], 'arrangementstype');
                }

                // Set ACF fields
                if (function_exists('update_field')) {
                    update_field('dato', $arrangement['dato'], $post_id);
                    update_field('tidspunkt_start', $arrangement['tid_start'] ?? '09:00', $post_id);
                    update_field('tidspunkt_slutt', $arrangement['tid_slutt'] ?? '16:00', $post_id);
                    update_field('sted', $arrangement['sted'] ?? '', $post_id);
                    update_field('er_digitalt', $arrangement['digitalt'] ?? false, $post_id);
                }

                $results['arrangementer']++;
            }
        }

        // ============================================
        // VERKTOY (5-8 per temagruppe via formaalstema)
        // ============================================
        $verktoy_data = bimverdi_get_verktoy_for_temagruppe($temagruppe_navn);

        foreach ($verktoy_data as $verktoy) {
            $post_id = wp_insert_post([
                'post_title'   => '[Claude - dummydata] ' . $verktoy['title'],
                'post_content' => '',
                'post_status'  => 'publish',
                'post_type'    => 'verktoy',
            ]);

            if ($post_id && !is_wp_error($post_id)) {
                // Set kategori taxonomy
                if (!empty($verktoy['kategori'])) {
                    wp_set_object_terms($post_id, $verktoy['kategori'], 'verktoykategori');
                }

                // Set ACF fields - importantly formaalstema to match temagruppe
                if (function_exists('update_field')) {
                    update_field('formaalstema', $temagruppe_navn, $post_id);
                    update_field('detaljert_beskrivelse', $verktoy['beskrivelse'], $post_id);
                    if (!empty($verktoy['lenke'])) {
                        update_field('verktoy_lenke', $verktoy['lenke'], $post_id);
                    }
                }

                $results['verktoy']++;
            }
        }

        // ============================================
        // ARTIKLER (3-5 per temagruppe)
        // ============================================
        $artikler_data = bimverdi_get_artikler_for_temagruppe($temagruppe_navn);

        foreach ($artikler_data as $artikkel) {
            $post_id = wp_insert_post([
                'post_title'   => '[Claude - dummydata] ' . $artikkel['title'],
                'post_content' => $artikkel['innhold'] ?? '',
                'post_status'  => 'publish',
                'post_type'    => 'artikkel',
            ]);

            if ($post_id && !is_wp_error($post_id)) {
                // Set temagruppe taxonomy
                wp_set_object_terms($post_id, $temagruppe->term_id, 'temagruppe');

                // Set kategori
                if (!empty($artikkel['kategori'])) {
                    wp_set_object_terms($post_id, $artikkel['kategori'], 'artikkelkategori');
                }

                // Set ACF fields
                if (function_exists('update_field')) {
                    update_field('artikkel_ingress', $artikkel['ingress'], $post_id);
                }

                $results['artikler']++;
            }
        }
    }

    // Show results
    $message = sprintf(
        'Dummydata opprettet: %d kunnskapskilder, %d arrangementer, %d verktoy, %d artikler',
        $results['kunnskapskilder'],
        $results['arrangementer'],
        $results['verktoy'],
        $results['artikler']
    );

    wp_die($message . '<br><br><a href="' . admin_url() . '">Tilbake til admin</a>');
});

/**
 * Get kunnskapskilder data for a specific temagruppe
 */
function bimverdi_get_kunnskapskilder_for_temagruppe($temagruppe_navn) {
    $data = [
        'ByggesaksBIM' => [
            ['title' => 'NS-EN ISO 19650-1:2018 - Informasjonsstyring', 'beskrivelse' => 'Internasjonal standard for informasjonsstyring i bygge- og anleggsprosjekter med BIM.', 'utgiver' => 'Standard Norge', 'lenke' => 'https://www.standard.no', 'kategori' => 'Standard', 'kildetype' => 'standard'],
            ['title' => 'buildingSMART IFC 4.3 Spesifikasjon', 'beskrivelse' => 'Seneste versjon av Industry Foundation Classes for apenBIM-utveksling.', 'utgiver' => 'buildingSMART International', 'lenke' => 'https://www.buildingsmart.org', 'kategori' => 'Standard', 'kildetype' => 'standard'],
            ['title' => 'DiBK Veileder for digital byggesak', 'beskrivelse' => 'Veileder fra Direktoratet for byggkvalitet om digital byggesaksbehandling.', 'utgiver' => 'Direktoratet for byggkvalitet', 'lenke' => 'https://dibk.no', 'kategori' => 'Veileder', 'kildetype' => 'veileder'],
            ['title' => 'KMD Digitalt planregister krav', 'beskrivelse' => 'Krav og spesifikasjoner for digitale planregistre fra Kommunal- og distriktsdepartementet.', 'utgiver' => 'KMD', 'lenke' => 'https://www.regjeringen.no', 'kategori' => 'Veileder', 'kildetype' => 'veileder'],
            ['title' => 'BIM-manual for prosjektering', 'beskrivelse' => 'Beste praksis for BIM i prosjekteringsfasen med fokus pa byggesak.', 'utgiver' => 'Standard Norge', 'lenke' => 'https://www.standard.no', 'kategori' => 'Veileder', 'kildetype' => 'veileder'],
        ],
        'ProsjektBIM' => [
            ['title' => 'ISO 19650-2:2018 - Leveransefase', 'beskrivelse' => 'Standard for informasjonsstyring i leveransefasen av bygg og anlegg.', 'utgiver' => 'Standard Norge', 'lenke' => 'https://www.standard.no', 'kategori' => 'Standard', 'kildetype' => 'standard'],
            ['title' => 'BCF - BIM Collaboration Format', 'beskrivelse' => 'apenBIM-standard for kommunikasjon av modellfeil og endringsforesporsler.', 'utgiver' => 'buildingSMART', 'lenke' => 'https://www.buildingsmart.org', 'kategori' => 'Standard', 'kildetype' => 'standard'],
            ['title' => 'Koordineringsmal for BIM-prosjekter', 'beskrivelse' => 'Mal for tverrfaglig koordinering i BIM-prosjekter.', 'utgiver' => 'BIM Verdi', 'lenke' => '#', 'kategori' => 'Mal/Template', 'kildetype' => 'mal'],
            ['title' => 'SINTEF Rapport: BIM i byggefasen', 'beskrivelse' => 'Forskningsrapport om BIM-bruk i byggefasen og erfaringer fra norske prosjekter.', 'utgiver' => 'SINTEF', 'lenke' => 'https://www.sintef.no', 'kategori' => 'Forskningsrapport', 'kildetype' => 'forskningsrapport'],
            ['title' => 'LOD-spesifikasjon Norge', 'beskrivelse' => 'Norsk tilpasning av Level of Development for BIM-objekter.', 'utgiver' => 'buildingSMART Norge', 'lenke' => 'https://buildingsmart.no', 'kategori' => 'Standard', 'kildetype' => 'standard'],
        ],
        'EiendomsBIM' => [
            ['title' => 'ISO 19650-3:2020 - Driftsfase', 'beskrivelse' => 'Standard for informasjonsstyring i driftsfasen av bygninger.', 'utgiver' => 'Standard Norge', 'lenke' => 'https://www.standard.no', 'kategori' => 'Standard', 'kildetype' => 'standard'],
            ['title' => 'COBie - Construction Operations Building Information Exchange', 'beskrivelse' => 'Spesifikasjon for overlevering av bygningsinformasjon til drift.', 'utgiver' => 'buildingSMART', 'lenke' => 'https://www.buildingsmart.org', 'kategori' => 'Standard', 'kildetype' => 'standard'],
            ['title' => 'Digital tvilling for bygg - Veileder', 'beskrivelse' => 'Veileder for implementering av digitale tvillinger i eiendomsforvaltning.', 'utgiver' => 'Norsk Eiendom', 'lenke' => '#', 'kategori' => 'Veileder', 'kildetype' => 'veileder'],
            ['title' => 'FM-BIM integrasjon beste praksis', 'beskrivelse' => 'Beste praksis for integrasjon mellom BIM og Facility Management-systemer.', 'utgiver' => 'NBEF', 'lenke' => '#', 'kategori' => 'Veileder', 'kildetype' => 'veileder'],
            ['title' => 'Energimerking og BIM', 'beskrivelse' => 'Hvordan bruke BIM-data for energimerking og -oppfolging.', 'utgiver' => 'Enova', 'lenke' => 'https://www.enova.no', 'kategori' => 'Veileder', 'kildetype' => 'veileder'],
        ],
        'MiljoBIM' => [
            ['title' => 'NS 3720:2018 Klimagassberegninger for bygg', 'beskrivelse' => 'Norsk standard for beregning av klimagassutslipp fra bygninger.', 'utgiver' => 'Standard Norge', 'lenke' => 'https://www.standard.no', 'kategori' => 'Standard', 'kildetype' => 'standard'],
            ['title' => 'EN 15978 Miljodeklarasjoner', 'beskrivelse' => 'Europeisk standard for miljoytelse av bygninger.', 'utgiver' => 'CEN', 'lenke' => '#', 'kategori' => 'Standard', 'kildetype' => 'standard'],
            ['title' => 'BREEAM-NOR Manual', 'beskrivelse' => 'Norsk tilpasning av BREEAM miljoklassifiseringssystem.', 'utgiver' => 'Gront Punkt', 'lenke' => 'https://byggalliansen.no', 'kategori' => 'Veileder', 'kildetype' => 'veileder'],
            ['title' => 'EPD-generator veileder', 'beskrivelse' => 'Veileder for bruk av Environmental Product Declarations i BIM.', 'utgiver' => 'EPD-Norge', 'lenke' => 'https://www.epd-norge.no', 'kategori' => 'Veileder', 'kildetype' => 'veileder'],
            ['title' => 'FutureBuilt Kriteriehandbok', 'beskrivelse' => 'Kriterier for nullutslippsbygg og -omrader.', 'utgiver' => 'FutureBuilt', 'lenke' => 'https://www.futurebuilt.no', 'kategori' => 'Veileder', 'kildetype' => 'veileder'],
        ],
        'SirkBIM' => [
            ['title' => 'NS 3451 Bygningsdelstabell', 'beskrivelse' => 'Norsk standard for klassifisering av bygningsdeler.', 'utgiver' => 'Standard Norge', 'lenke' => 'https://www.standard.no', 'kategori' => 'Standard', 'kildetype' => 'standard'],
            ['title' => 'Materialpass for bygg', 'beskrivelse' => 'Spesifikasjon for digitale materialpass i bygninger.', 'utgiver' => 'EU', 'lenke' => '#', 'kategori' => 'Standard', 'kildetype' => 'standard'],
            ['title' => 'Ombrukskartlegging veileder', 'beskrivelse' => 'Veileder for kartlegging av ombrukspotensial i eksisterende bygg.', 'utgiver' => 'Gront Punkt', 'lenke' => '#', 'kategori' => 'Veileder', 'kildetype' => 'veileder'],
            ['title' => 'Sirkulaer okonomi i BAE-naeringen', 'beskrivelse' => 'Rapport om muligheter for sirkulaer okonomi i bygg, anlegg og eiendom.', 'utgiver' => 'SINTEF', 'lenke' => 'https://www.sintef.no', 'kategori' => 'Forskningsrapport', 'kildetype' => 'forskningsrapport'],
            ['title' => 'DiBK Veileder for avfallsplan', 'beskrivelse' => 'Krav og veiledning for avfallsplan i byggeprosjekter.', 'utgiver' => 'DiBK', 'lenke' => 'https://dibk.no', 'kategori' => 'Veileder', 'kildetype' => 'veileder'],
        ],
        'BIMtech' => [
            ['title' => 'IFC-JS Library dokumentasjon', 'beskrivelse' => 'Dokumentasjon for JavaScript-bibliotek for IFC-parsing og visualisering.', 'utgiver' => 'IFC.js', 'lenke' => 'https://ifcjs.github.io', 'kategori' => 'Verktøydokumentasjon', 'kildetype' => 'dokumentasjon', 'spraak' => 'engelsk'],
            ['title' => 'Open BIM Components', 'beskrivelse' => 'Open source komponenter for BIM-webapplikasjoner.', 'utgiver' => 'That Open Company', 'lenke' => '#', 'kategori' => 'Verktøydokumentasjon', 'kildetype' => 'dokumentasjon', 'spraak' => 'engelsk'],
            ['title' => 'AI i BIM - State of the Art', 'beskrivelse' => 'Oversikt over kunstig intelligens-anvendelser i BIM-domenet.', 'utgiver' => 'NTNU', 'lenke' => '#', 'kategori' => 'Forskningsrapport', 'kildetype' => 'forskningsrapport'],
            ['title' => 'Python for BIM Automation', 'beskrivelse' => 'Kurs og ressurser for automatisering av BIM-arbeidsflyter med Python.', 'utgiver' => 'BIM Verdi', 'lenke' => '#', 'kategori' => 'Opplæring', 'kildetype' => 'opplaering'],
            ['title' => 'Linked Building Data', 'beskrivelse' => 'Semantiske webteknologier for bygningsinformasjon.', 'utgiver' => 'W3C', 'lenke' => 'https://www.w3.org', 'kategori' => 'Standard', 'kildetype' => 'standard', 'spraak' => 'engelsk'],
        ],
    ];

    return $data[$temagruppe_navn] ?? [];
}

/**
 * Get arrangementer data for a specific temagruppe
 */
function bimverdi_get_arrangementer_for_temagruppe($temagruppe_navn) {
    // Future dates (2026)
    $base_dates = [
        date('Ymd', strtotime('+2 weeks')),
        date('Ymd', strtotime('+1 month')),
        date('Ymd', strtotime('+6 weeks')),
        date('Ymd', strtotime('+2 months')),
    ];

    $data = [
        'ByggesaksBIM' => [
            ['title' => 'ByggesaksBIM Gruppemote Q1', 'dato' => $base_dates[0], 'tid_start' => '09:00', 'tid_slutt' => '12:00', 'type' => 'Temagruppemøte', 'sted' => 'Oslo', 'digitalt' => false],
            ['title' => 'Workshop: IFC-krav for byggesak', 'dato' => $base_dates[1], 'tid_start' => '10:00', 'tid_slutt' => '15:00', 'type' => 'Workshop', 'digitalt' => true],
            ['title' => 'Webinar: Digitalt planregister demo', 'dato' => $base_dates[2], 'tid_start' => '13:00', 'tid_slutt' => '14:30', 'type' => 'Webinar', 'digitalt' => true],
            ['title' => 'Erfaringsdeling: Digital byggesak i praksis', 'dato' => $base_dates[3], 'tid_start' => '09:00', 'tid_slutt' => '16:00', 'type' => 'Seminar', 'sted' => 'Trondheim', 'digitalt' => false],
        ],
        'ProsjektBIM' => [
            ['title' => 'ProsjektBIM Gruppemote Q1', 'dato' => $base_dates[0], 'tid_start' => '09:00', 'tid_slutt' => '12:00', 'type' => 'Temagruppemøte', 'digitalt' => true],
            ['title' => 'Workshop: BCF-arbeidsflyt', 'dato' => $base_dates[1], 'tid_start' => '10:00', 'tid_slutt' => '15:00', 'type' => 'Workshop', 'sted' => 'Bergen', 'digitalt' => false],
            ['title' => 'Koordineringsmotet: Beste praksis', 'dato' => $base_dates[2], 'tid_start' => '13:00', 'tid_slutt' => '16:00', 'type' => 'Seminar', 'digitalt' => true],
        ],
        'EiendomsBIM' => [
            ['title' => 'EiendomsBIM Gruppemote Q1', 'dato' => $base_dates[0], 'tid_start' => '09:00', 'tid_slutt' => '12:00', 'type' => 'Temagruppemøte', 'digitalt' => true],
            ['title' => 'Webinar: Digital tvilling i drift', 'dato' => $base_dates[1], 'tid_start' => '10:00', 'tid_slutt' => '11:30', 'type' => 'Webinar', 'digitalt' => true],
            ['title' => 'Workshop: COBie-overlevering', 'dato' => $base_dates[2], 'tid_start' => '09:00', 'tid_slutt' => '16:00', 'type' => 'Workshop', 'sted' => 'Oslo', 'digitalt' => false],
        ],
        'MiljoBIM' => [
            ['title' => 'MiljoBIM Gruppemote Q1', 'dato' => $base_dates[0], 'tid_start' => '09:00', 'tid_slutt' => '12:00', 'type' => 'Temagruppemøte', 'digitalt' => true],
            ['title' => 'Seminar: Klimagassberegninger med BIM', 'dato' => $base_dates[1], 'tid_start' => '10:00', 'tid_slutt' => '15:00', 'type' => 'Seminar', 'sted' => 'Oslo', 'digitalt' => false],
            ['title' => 'Webinar: EPD-data i BIM', 'dato' => $base_dates[2], 'tid_start' => '13:00', 'tid_slutt' => '14:30', 'type' => 'Webinar', 'digitalt' => true],
        ],
        'SirkBIM' => [
            ['title' => 'SirkBIM Gruppemote Q1', 'dato' => $base_dates[0], 'tid_start' => '09:00', 'tid_slutt' => '12:00', 'type' => 'Temagruppemøte', 'digitalt' => true],
            ['title' => 'Workshop: Materialpass i praksis', 'dato' => $base_dates[1], 'tid_start' => '10:00', 'tid_slutt' => '16:00', 'type' => 'Workshop', 'sted' => 'Trondheim', 'digitalt' => false],
            ['title' => 'Hackathon: Ombruksverktoy', 'dato' => $base_dates[2], 'tid_start' => '09:00', 'tid_slutt' => '17:00', 'type' => 'Hackathon', 'sted' => 'Oslo', 'digitalt' => false],
        ],
        'BIMtech' => [
            ['title' => 'BIMtech Gruppemote Q1', 'dato' => $base_dates[0], 'tid_start' => '09:00', 'tid_slutt' => '12:00', 'type' => 'BIMtech møte', 'digitalt' => true],
            ['title' => 'Workshop: IFC.js Introduksjon', 'dato' => $base_dates[1], 'tid_start' => '10:00', 'tid_slutt' => '16:00', 'type' => 'Workshop', 'digitalt' => true],
            ['title' => 'Hackathon: AI for BIM', 'dato' => $base_dates[2], 'tid_start' => '09:00', 'tid_slutt' => '18:00', 'type' => 'Hackathon', 'sted' => 'Oslo', 'digitalt' => false],
            ['title' => 'Webinar: Python automatisering', 'dato' => $base_dates[3], 'tid_start' => '13:00', 'tid_slutt' => '15:00', 'type' => 'Webinar', 'digitalt' => true],
        ],
    ];

    return $data[$temagruppe_navn] ?? [];
}

/**
 * Get verktoy data for a specific temagruppe
 */
function bimverdi_get_verktoy_for_temagruppe($temagruppe_navn) {
    $data = [
        'ByggesaksBIM' => [
            ['title' => 'Solibri Model Checker', 'beskrivelse' => 'Programvare for modellsjekk og kvalitetskontroll av BIM-modeller.', 'kategori' => 'Quality Control/Validation', 'lenke' => 'https://www.solibri.com'],
            ['title' => 'SMC ByggSok', 'beskrivelse' => 'Spesialtilpasset regelsjekk for norsk byggesak.', 'kategori' => 'Quality Control/Validation'],
            ['title' => 'Simplebim', 'beskrivelse' => 'Verktoy for IFC-redigering og klargjoring for byggesak.', 'kategori' => 'Other', 'lenke' => 'https://www.simplebim.com'],
            ['title' => 'BIMcollab', 'beskrivelse' => 'Skybasert plattform for BCF-basert samarbeid.', 'kategori' => 'Collaboration/Communication', 'lenke' => 'https://www.bimcollab.com'],
            ['title' => 'IFC Viewer Pro', 'beskrivelse' => 'Lettvekts IFC-viewer for gjennomgang av modeller.', 'kategori' => 'Visualization/VR/AR'],
        ],
        'ProsjektBIM' => [
            ['title' => 'Autodesk Revit', 'beskrivelse' => 'BIM-modelleringsverktoy for arkitekter og ingenirer.', 'kategori' => 'BIM Authoring/Modelling', 'lenke' => 'https://www.autodesk.com/revit'],
            ['title' => 'Navisworks', 'beskrivelse' => 'Programvare for 4D/5D-simulering og kollisjonskontroll.', 'kategori' => 'Collaboration/Communication', 'lenke' => 'https://www.autodesk.com/navisworks'],
            ['title' => 'Trimble Connect', 'beskrivelse' => 'Cloud-plattform for BIM-samarbeid og koordinering.', 'kategori' => 'Collaboration/Communication', 'lenke' => 'https://connect.trimble.com'],
            ['title' => 'BIM Track', 'beskrivelse' => 'Issue-tracking og koordineringsverktoy for BIM-prosjekter.', 'kategori' => 'Collaboration/Communication'],
            ['title' => 'Dalux', 'beskrivelse' => 'Mobil BIM-plattform for byggeplass og prosjektoppfolging.', 'kategori' => 'Project Management', 'lenke' => 'https://www.dalux.com'],
        ],
        'EiendomsBIM' => [
            ['title' => 'Planon', 'beskrivelse' => 'IWMS-plattform med BIM-integrasjon for eiendomsforvaltning.', 'kategori' => 'Project Management', 'lenke' => 'https://www.planonsoftware.com'],
            ['title' => 'Archibus', 'beskrivelse' => 'FM-system med stotte for BIM-data og digital tvilling.', 'kategori' => 'Project Management'],
            ['title' => 'Autodesk Tandem', 'beskrivelse' => 'Digital tvilling-plattform fra Autodesk.', 'kategori' => 'Visualization/VR/AR', 'lenke' => 'https://www.autodesk.com/tandem'],
            ['title' => 'EcoDomus', 'beskrivelse' => 'BIM-basert plattform for livssyklusforvaltning.', 'kategori' => 'Project Management'],
            ['title' => 'Spacewell', 'beskrivelse' => 'Smart building-plattform med BIM-integrasjon.', 'kategori' => 'Analysis & Simulation'],
        ],
        'MiljoBIM' => [
            ['title' => 'One Click LCA', 'beskrivelse' => 'Livslopsvurdering og klimagassberegning fra BIM-modeller.', 'kategori' => 'Climate/Environmental Calculation', 'lenke' => 'https://www.oneclicklca.com'],
            ['title' => 'Simien', 'beskrivelse' => 'Norsk programvare for energiberegning i bygg.', 'kategori' => 'Climate/Environmental Calculation'],
            ['title' => 'IDA ICE', 'beskrivelse' => 'Avansert bygningssimulering for energi og inneklima.', 'kategori' => 'Analysis & Simulation'],
            ['title' => 'Lesosai', 'beskrivelse' => 'Programvare for energi- og miljosertifisering.', 'kategori' => 'Climate/Environmental Calculation'],
            ['title' => 'Design Builder', 'beskrivelse' => 'Energisimulering med BIM-import.', 'kategori' => 'Analysis & Simulation', 'lenke' => 'https://www.designbuilder.co.uk'],
        ],
        'SirkBIM' => [
            ['title' => 'Madaster', 'beskrivelse' => 'Plattform for materialpass og sirkulaeritetsvurdering.', 'kategori' => 'Material Management', 'lenke' => 'https://www.madaster.com'],
            ['title' => 'Circular IQ', 'beskrivelse' => 'Verktoy for sirkulaeritetsmaling i verdikjeder.', 'kategori' => 'Analysis & Simulation'],
            ['title' => 'Loop Rocks', 'beskrivelse' => 'Markedsplass for ombruk av byggematerialer.', 'kategori' => 'Material Management', 'lenke' => 'https://www.looprocks.no'],
            ['title' => 'Circular BIM', 'beskrivelse' => 'Plugin for sirkulaer design i Revit.', 'kategori' => 'BIM Authoring/Modelling'],
            ['title' => 'Resirkel', 'beskrivelse' => 'Norsk plattform for ombrukskartlegging.', 'kategori' => 'Material Management'],
        ],
        'BIMtech' => [
            ['title' => 'IFC.js', 'beskrivelse' => 'Open source JavaScript-bibliotek for IFC-visualisering.', 'kategori' => 'Visualization/VR/AR', 'lenke' => 'https://ifcjs.github.io'],
            ['title' => 'Speckle', 'beskrivelse' => 'Open source dataplattform for AEC.', 'kategori' => 'Collaboration/Communication', 'lenke' => 'https://speckle.systems'],
            ['title' => 'BlenderBIM', 'beskrivelse' => 'Open source BIM-addon for Blender.', 'kategori' => 'BIM Authoring/Modelling', 'lenke' => 'https://blenderbim.org'],
            ['title' => 'IfcOpenShell', 'beskrivelse' => 'Python-bibliotek for IFC-manipulasjon.', 'kategori' => 'Other', 'lenke' => 'http://ifcopenshell.org'],
            ['title' => 'xBIM Toolkit', 'beskrivelse' => '.NET-bibliotek for IFC-utvikling.', 'kategori' => 'Other', 'lenke' => 'https://docs.xbim.net'],
            ['title' => 'Hypar', 'beskrivelse' => 'Skybasert generativ design-plattform.', 'kategori' => 'AI/Machine Learning', 'lenke' => 'https://hypar.io'],
        ],
    ];

    return $data[$temagruppe_navn] ?? [];
}

/**
 * Get artikler data for a specific temagruppe
 */
function bimverdi_get_artikler_for_temagruppe($temagruppe_navn) {
    $data = [
        'ByggesaksBIM' => [
            ['title' => 'Slik forbereder du deg til digital byggesak', 'ingress' => 'En praktisk guide for a komme i gang med digital byggesaksbehandling.', 'kategori' => 'Ressurs', 'innhold' => 'Lorem ipsum dolor sit amet...'],
            ['title' => 'Erfaringer fra pilotprosjekt i Trondheim kommune', 'ingress' => 'Trondheim kommune deler sine erfaringer fra tidlig digital byggesak.', 'kategori' => 'Case study'],
            ['title' => 'IFC i byggesak - hva kreves av modellen?', 'ingress' => 'Oversikt over krav til IFC-modeller for digital byggesak.', 'kategori' => 'Ressurs'],
        ],
        'ProsjektBIM' => [
            ['title' => 'BCF-arbeidsflyt som faktisk fungerer', 'ingress' => 'Tips for effektiv bruk av BCF i prosjektkoordinering.', 'kategori' => 'Medlemsinnlegg'],
            ['title' => 'Fra modell til byggeplass: BIM i praksis', 'ingress' => 'Hvordan bruke BIM-modellen aktivt pa byggeplassen.', 'kategori' => 'Case study'],
            ['title' => 'Tverrfaglig koordinering med BIM', 'ingress' => 'Beste praksis for koordinering mellom faggrupper.', 'kategori' => 'Ressurs'],
        ],
        'EiendomsBIM' => [
            ['title' => 'Digital tvilling i eiendomsforvaltning', 'ingress' => 'Hvordan digitale tvillinger endrer eiendomsforvaltning.', 'kategori' => 'Nyhet'],
            ['title' => 'Fra BIM til FM - overleveringsutfordringer', 'ingress' => 'Vanlige utfordringer og losninger ved BIM-til-FM-overlevering.', 'kategori' => 'Ressurs'],
            ['title' => 'Energioppfolging med BIM-data', 'ingress' => 'Bruk av BIM for kontinuerlig energioppfolging i drift.', 'kategori' => 'Case study'],
        ],
        'MiljoBIM' => [
            ['title' => 'Klimagassberegning fra BIM - kom i gang', 'ingress' => 'Innforing i klimagassberegninger direkte fra BIM-modeller.', 'kategori' => 'Ressurs'],
            ['title' => 'BREEAM og BIM - en praktisk guide', 'ingress' => 'Hvordan BIM stotter BREEAM-sertifisering.', 'kategori' => 'Ressurs'],
            ['title' => 'EPD-data i BIM-modeller', 'ingress' => 'Integrering av Environmental Product Declarations i BIM.', 'kategori' => 'Medlemsinnlegg'],
        ],
        'SirkBIM' => [
            ['title' => 'Materialpass - fremtidens krav?', 'ingress' => 'Om kommende krav til digitale materialpass for bygninger.', 'kategori' => 'Nyhet'],
            ['title' => 'Ombrukskartlegging med BIM', 'ingress' => 'Hvordan bruke BIM for a kartlegge ombrukspotensial.', 'kategori' => 'Ressurs'],
            ['title' => 'Fra avfall til ressurs: Sirkulaer tankegang i praksis', 'ingress' => 'Case study fra et prosjekt med hoy ombruksandel.', 'kategori' => 'Case study'],
        ],
        'BIMtech' => [
            ['title' => 'IFC.js - BIM i nettleseren', 'ingress' => 'Introduksjon til open source BIM-visualisering pa web.', 'kategori' => 'Ressurs'],
            ['title' => 'Python for BIM-automatisering', 'ingress' => 'Kom i gang med Python-skripting for BIM-arbeidsflyter.', 'kategori' => 'Ressurs'],
            ['title' => 'AI i BIM - hva er status?', 'ingress' => 'Oversikt over kunstig intelligens-anvendelser i BIM-domenet.', 'kategori' => 'Nyhet'],
            ['title' => 'Open source BIM-verktoy du bor kjenne til', 'ingress' => 'De viktigste open source-verktoyene for BIM-utvikling.', 'kategori' => 'Medlemsinnlegg'],
        ],
    ];

    return $data[$temagruppe_navn] ?? [];
}
