<?php
/**
 * Template Name: Policy-graf
 *
 * Interactive D3.js force-directed graph showing relationships between
 * TEK17, SAK10, NS/ISO standards, and Digitalt Veikart (kap.4 & 6).
 *
 * Prototype by Bård (Microsoft Copilot), adapted for BIM Verdi.
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<style>
    /* Hide the #content wrapper from header.php and the footer for full-screen layout */
    #content { display: contents; }
    body footer { display: none; }

    /* Policy graph layout - full width below header */
    .policy-graf-wrap {
        display: flex;
        height: calc(100vh - 64px - 40px); /* subtract header + announcement bar */
    }
    .policy-graf-sidebar {
        width: 360px;
        border-right: 1px solid #E7E5E4;
        padding: 20px 24px;
        overflow-y: auto;
        background: #FAFAF9;
        flex-shrink: 0;
    }
    .policy-graf-viz {
        flex: 1;
        position: relative;
        background: #fff;
    }

    /* Sidebar elements */
    .policy-graf-sidebar h2 {
        font-size: 18px;
        font-weight: 700;
        color: #1A1A1A;
        margin: 0 0 4px 0;
    }
    .policy-graf-sidebar .subtitle {
        font-size: 13px;
        color: #5A5A5A;
        margin: 0 0 20px 0;
    }
    .policy-graf-sidebar label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #1A1A1A;
        padding: 6px 0;
        cursor: pointer;
    }
    .policy-graf-sidebar input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: #FF8B5E;
    }
    .policy-graf-sidebar input[type="search"] {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #D6D1C6;
        border-radius: 8px;
        font-size: 14px;
        background: #fff;
        color: #1A1A1A;
    }
    .policy-graf-sidebar input[type="search"]:focus {
        outline: none;
        border-color: #FF8B5E;
        box-shadow: 0 0 0 2px rgba(255, 139, 94, 0.15);
    }
    .policy-graf-sidebar .section-label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #888;
        margin: 20px 0 8px 0;
    }
    .policy-graf-sidebar .tips {
        font-size: 12px;
        color: #888;
        line-height: 1.5;
        margin: 12px 0;
    }
    .policy-graf-sidebar hr {
        border: none;
        border-top: 1px solid #E7E5E4;
        margin: 16px 0;
    }

    /* Legend */
    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #5A5A5A;
        padding: 3px 0;
    }
    .legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    /* Node detail card */
    .node-card {
        background: #fff;
        border: 1px solid #E7E5E4;
        border-radius: 10px;
        padding: 16px;
        margin-top: 12px;
    }
    .node-card h3 {
        font-size: 15px;
        font-weight: 600;
        color: #1A1A1A;
        margin: 0 0 8px 0;
        line-height: 1.4;
    }
    .node-card p {
        font-size: 13px;
        color: #5A5A5A;
        margin: 6px 0;
        line-height: 1.5;
    }
    .node-card ul {
        list-style: none;
        padding: 0;
        margin: 8px 0 0 0;
    }
    .node-card ul li {
        padding: 4px 0;
    }
    .node-card a {
        color: #FF8B5E;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
    }
    .node-card a:hover {
        text-decoration: underline;
    }
    .node-pill {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        color: #fff;
        margin-left: 6px;
        vertical-align: middle;
    }

    /* D3 graph styling */
    .node circle { stroke: #fff; stroke-width: 1.5px; cursor: pointer; }
    .node text { font-family: system-ui, -apple-system, sans-serif; fill: #1A1A1A; pointer-events: none; }
    .link { stroke: #D6D1C6; stroke-opacity: 0.5; }
    .hidden { display: none; }

    /* Mobile: stack sidebar on top */
    @media (max-width: 768px) {
        .policy-graf-wrap {
            flex-direction: column;
            height: auto;
        }
        .policy-graf-sidebar {
            width: 100%;
            max-height: 300px;
            border-right: none;
            border-bottom: 1px solid #E7E5E4;
        }
        .policy-graf-viz {
            height: 60vh;
        }
    }
</style>

<div class="policy-graf-wrap">
    <aside class="policy-graf-sidebar">
        <h2>Policy-graf</h2>
        <p class="subtitle">Koblinger mellom forskrifter, standarder og Digitalt Veikart</p>

        <p class="section-label">Filter</p>
        <label><input type="checkbox" class="grp" value="TEK17" checked> TEK17</label>
        <label><input type="checkbox" class="grp" value="SAK10" checked> SAK10</label>
        <label><input type="checkbox" class="grp" value="NS/ISO" checked> NS/ISO</label>
        <label><input type="checkbox" class="grp" value="DV" checked> Digitalt Veikart (kap.4/6)</label>

        <p class="section-label">Sok</p>
        <input type="search" id="policy-search" placeholder="Sok etter node... (f.eks. API, NS 19650)">

        <p class="tips">Klikk en node for detaljer og kildelenker. Dobbeltklikk for a feste/lose. Rull for zoom. Dra for a flytte.</p>

        <hr>

        <p class="section-label">Forklaring</p>
        <div class="legend-item"><span class="legend-dot" style="background:#2E86DE"></span> TEK17</div>
        <div class="legend-item"><span class="legend-dot" style="background:#FF8C00"></span> SAK10</div>
        <div class="legend-item"><span class="legend-dot" style="background:#27AE60"></span> NS/ISO</div>
        <div class="legend-item"><span class="legend-dot" style="background:#8E44AD"></span> Digitalt Veikart (kap.4/6)</div>

        <hr>

        <div id="policy-details">
            <div class="node-card">
                <h3>Velg en node i grafen</h3>
                <p>Visualiserer hvilke forskrifter og standarder som relaterer til behov, anbefalinger og felleskomponenter i Digitalt Veikart kapittel 4 og 6.</p>
                <p><strong>Kilder:</strong> DiBK (TEK/SAK), Standard Norge (NS/ISO), BNL: <a href="https://www.bnl.no/siteassets/bilder/generelle-bilder/digitaltveikart_2020.pdf" target="_blank" rel="noopener">Digitalt veikart 2.0</a>.</p>
            </div>
        </div>
    </aside>

    <main class="policy-graf-viz" id="policy-viz"></main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.9.0/d3.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function() {
    'use strict';

    var DATA = <?php
    // Graph data - nodes and links
    // Could be moved to ACF/options page later for admin editability
    $graph_data = array(
        'nodes' => array(
            array('id' => 'TEK17 kap.1 Felles bestemmelser', 'group' => 'TEK17', 'desc' => 'Overordnede bestemmelser, formal og definisjoner i TEK17.', 'links' => array(array('label' => 'DiBK: TEK17 (oversikt)', 'url' => 'https://www.dibk.no/regelverk/byggteknisk-forskrift-tek17/'))),
            array('id' => 'TEK17 kap.2 Dokumentasjon av oppfyllelse', 'group' => 'TEK17', 'desc' => 'Krav til dokumentasjon av oppfyllelse av funksjons-/ytelseskrav.', 'links' => array(array('label' => 'DiBK: TEK17', 'url' => 'https://www.dibk.no/regelverk/byggteknisk-forskrift-tek17/'), array('label' => 'Veiledning TEK17 (PDF)', 'url' => 'https://www.regjeringen.no/contentassets/20503ddfe0664fac9e2185c1a6c80716/veiledning-til-byggteknisk-forskrift-tek17_01_07_2017.pdf'))),
            array('id' => 'TEK17 kap.3 Byggevarer (dokumentasjon)', 'group' => 'TEK17', 'desc' => 'Dokumentasjon av byggevarer.', 'links' => array(array('label' => 'DiBK: TEK17', 'url' => 'https://www.dibk.no/regelverk/byggteknisk-forskrift-tek17/'))),
            array('id' => 'TEK17 kap.4 FDV-dokumentasjon', 'group' => 'TEK17', 'desc' => 'Dokumentasjon for forvaltning, drift og vedlikehold (FDV).', 'links' => array(array('label' => 'DiBK: TEK17', 'url' => 'https://www.dibk.no/regelverk/byggteknisk-forskrift-tek17/'))),
            array('id' => 'TEK17 kap.6 Beregnings- og maleregler', 'group' => 'TEK17', 'desc' => 'Maleregler for areal/hoyde/avstand m.m.', 'links' => array(array('label' => 'DiBK: TEK17', 'url' => 'https://www.dibk.no/regelverk/byggteknisk-forskrift-tek17/'), array('label' => 'Standard Norge: NS 3940', 'url' => 'https://standard.no/fagomrader/bygg-anlegg-og-eiendom/areal-og-volumberegninger-av-bygg-ns-3940/'))),
            array('id' => 'TEK17 kap.9 Ytre miljo', 'group' => 'TEK17', 'desc' => 'Miljokrav, avfall/ombruk (kap. 9).', 'links' => array(array('label' => 'DiBK: TEK17', 'url' => 'https://www.dibk.no/regelverk/byggteknisk-forskrift-tek17/'))),
            array('id' => 'TEK17 kap.11 Sikkerhet ved brann', 'group' => 'TEK17', 'desc' => 'Overordnede brannkrav og preaksepterte ytelser.', 'links' => array(array('label' => 'DiBK: SS 11-1', 'url' => 'https://www.dibk.no/regelverk/byggteknisk-forskrift-tek17/11/i/11-1/'))),
            array('id' => 'TEK17 kap.12 Planlosning/bygningsdeler', 'group' => 'TEK17', 'desc' => 'Krav til planlosning og bygningsdeler.', 'links' => array(array('label' => 'DiBK: TEK17', 'url' => 'https://www.dibk.no/regelverk/byggteknisk-forskrift-tek17/'))),
            array('id' => 'TEK17 kap.13 Inneklima og helse', 'group' => 'TEK17', 'desc' => 'Krav til inneklima/ventilasjon/helse.', 'links' => array(array('label' => 'DiBK: TEK17', 'url' => 'https://www.dibk.no/regelverk/byggteknisk-forskrift-tek17/'))),
            array('id' => 'TEK17 kap.14 Energi', 'group' => 'TEK17', 'desc' => 'Krav til energiytelse/forsyning (henviser inntil videre til NS 3031:2014).', 'links' => array(array('label' => 'DiBK: spesifikasjon/henvisning', 'url' => 'https://www.dibk.no/byggtekniske-omrader/ny-spesifikasjon-om-beregning-av-energibehov-og-energiforsyning/'), array('label' => 'Standard Norge: NS 3031 (2025)', 'url' => 'https://standard.no/fagomrader/energi-og-klima-i-bygg/bygningsenergi/beregning-av-bygningers-energi--og-effektbehov/'))),
            array('id' => 'TEK17 kap.15 Installasjoner og anlegg', 'group' => 'TEK17', 'desc' => 'Tekniske installasjoner og anlegg.', 'links' => array(array('label' => 'DiBK: TEK17', 'url' => 'https://www.dibk.no/regelverk/byggteknisk-forskrift-tek17/'))),
            array('id' => 'SAK10 Kap.5 Soknad og dokumentasjon', 'group' => 'SAK10', 'desc' => 'Soknad og dokumentasjon i byggesak.', 'links' => array(array('label' => 'DiBK: SAK10', 'url' => 'https://www.dibk.no/regelverk/sak/'), array('label' => 'Lovdata: SAK10', 'url' => 'https://lovdata.no/nav/forskrift/2010-03-26-488'))),
            array('id' => 'SAK10 Kap.6 Kommunens saksbehandling', 'group' => 'SAK10', 'desc' => 'Kommunens saksbehandling.', 'links' => array(array('label' => 'Lovdata: SAK10', 'url' => 'https://lovdata.no/nav/forskrift/2010-03-26-488'))),
            array('id' => 'SAK10 Kap.7 Tidsfrister', 'group' => 'SAK10', 'desc' => 'Tidsfrister for saksbehandling.', 'links' => array(array('label' => 'Lovdata: SAK10', 'url' => 'https://lovdata.no/nav/forskrift/2010-03-26-488'))),
            array('id' => 'SAK10 Kap.8 Ferdigstillelse', 'group' => 'SAK10', 'desc' => 'Ferdigstillelse/avslutning av tiltak.', 'links' => array(array('label' => 'Lovdata: SAK10', 'url' => 'https://lovdata.no/nav/forskrift/2010-03-26-488'))),
            array('id' => 'SAK10 Kap.9 Foretak og tiltaksklasser', 'group' => 'SAK10', 'desc' => 'Foretak/tiltaksklasser.', 'links' => array(array('label' => 'Lovdata: SAK10', 'url' => 'https://lovdata.no/nav/forskrift/2010-03-26-488'))),
            array('id' => 'SAK10 Kap.10 Kvalitetssikring', 'group' => 'SAK10', 'desc' => 'Kvalitetssikring i byggesak.', 'links' => array(array('label' => 'DiBK: SAK10', 'url' => 'https://www.dibk.no/regelverk/sak/'))),
            array('id' => 'SAK10 Kap.12 Ansvar (soker/prosjekterende/utforende)', 'group' => 'SAK10', 'desc' => 'Ansvarsroller.', 'links' => array(array('label' => 'DiBK: SAK10', 'url' => 'https://www.dibk.no/regelverk/sak/'))),
            array('id' => 'SAK10 Kap.14-15 Kontroll og tilsyn', 'group' => 'SAK10', 'desc' => 'Uavhengig kontroll og tilsyn.', 'links' => array(array('label' => 'DiBK: SAK10', 'url' => 'https://www.dibk.no/regelverk/sak/'))),
            array('id' => 'NS-EN ISO 19650 (informasjon/BIM)', 'group' => 'NS/ISO', 'desc' => 'Informasjonsforvaltning med BIM (PIM/AIM, OIR/PIR/EIR, CDE).', 'links' => array(array('label' => 'Standard Norge: ISO 19650', 'url' => 'https://standard.no/fagomrader/bygg-anlegg-og-eiendom/digital-byggeprosess/iso-19650-serien/'))),
            array('id' => 'NS-EN ISO 16739-1 (IFC)', 'group' => 'NS/ISO', 'desc' => 'IFC 4.3 - apen BIM-datastruktur.', 'links' => array(array('label' => 'SN: NS-EN ISO 16739-1:2024', 'url' => 'https://online.standard.no/ns-en-iso-16739-1-2024'), array('label' => 'ISO: 16739-1:2024', 'url' => 'https://www.iso.org/standard/84123.html'))),
            array('id' => 'NS 8360-serien (BIM-objekter)', 'group' => 'NS/ISO', 'desc' => 'Modellpraksis, typekoding, egenskaper og kobling av dokumentasjon.', 'links' => array(array('label' => 'SN: NS 8360-serien', 'url' => 'https://standard.no/fagomrader/bygg-anlegg-og-eiendom/digital-byggeprosess/ns-8360-bim-objekter/'))),
            array('id' => 'NS 3451 (Bygningsdelstabell)', 'group' => 'NS/ISO', 'desc' => 'Bygningsdels- og systemkoder (2022).', 'links' => array(array('label' => 'SN: NS 3451', 'url' => 'https://standard.no/fagomrader/bygg-anlegg-og-eiendom/ns-3420-/ns-3450----ns-3451---ns-3459-2/'))),
            array('id' => 'NS 3940 (Areal-/volumberegning)', 'group' => 'NS/ISO', 'desc' => 'Areal- og volumberegninger (revidert 2023/2024).', 'links' => array(array('label' => 'SN: NS 3940', 'url' => 'https://standard.no/fagomrader/bygg-anlegg-og-eiendom/areal-og-volumberegninger-av-bygg-ns-3940/'))),
            array('id' => 'NS 3031 (Energi- og effektbehov)', 'group' => 'NS/ISO', 'desc' => 'Beregning av energi-/effektbehov (ny 2025, TEK viser til 2014 inntil endring).', 'links' => array(array('label' => 'SN: NS 3031 (2025)', 'url' => 'https://standard.no/fagomrader/energi-og-klima-i-bygg/bygningsenergi/beregning-av-bygningers-energi--og-effektbehov/'), array('label' => 'DiBK: TEK-henvisning', 'url' => 'https://www.dibk.no/byggtekniske-omrader/ny-spesifikasjon-om-beregning-av-energibehov-og-energiforsyning/'))),
            array('id' => 'NS 3424 (Tilstandsanalyse)', 'group' => 'NS/ISO', 'desc' => 'Gjennomforing av tilstandsanalyse (TG/risiko).', 'links' => array(array('label' => 'SN: NS 3424 (omtale)', 'url' => 'https://standard.no/fagomrader/bygg-anlegg-og-eiendom/teknisk-tilstandsanalyse-av-bolig---ns-3600/bedre-tilstandsanalyser-med-ns-3424/'))),
            array('id' => 'DV kap.4 - Felles rammeverk: Internasjonale rammer', 'group' => 'DV', 'desc' => 'Behov for omforent rammeverk for informasjonsforvaltning - internasjonale rammer.', 'links' => array(array('label' => 'BNL: Digitalt veikart 2.0 (PDF)', 'url' => 'https://www.bnl.no/siteassets/bilder/generelle-bilder/digitaltveikart_2020.pdf'))),
            array('id' => 'DV kap.4 - Felles rammeverk: Nasjonale rammer', 'group' => 'DV', 'desc' => 'Nasjonale rammer for informasjonsforvaltning.', 'links' => array(array('label' => 'BNL: Digitalt veikart 2.0 (PDF)', 'url' => 'https://www.bnl.no/siteassets/bilder/generelle-bilder/digitaltveikart_2020.pdf'))),
            array('id' => 'DV kap.4 - Felles spesifikasjoner og komponenter', 'group' => 'DV', 'desc' => 'Felles spesifikasjoner/komponenter for datadeling og interoperabilitet.', 'links' => array(array('label' => 'BNL: Digitalt veikart 2.0 (PDF)', 'url' => 'https://www.bnl.no/siteassets/bilder/generelle-bilder/digitaltveikart_2020.pdf'))),
            array('id' => 'DV kap.4 - Standarder for API', 'group' => 'DV', 'desc' => 'Etablere standarder for API.', 'links' => array(array('label' => 'BNL: Digitalt veikart 2.0 (PDF)', 'url' => 'https://www.bnl.no/siteassets/bilder/generelle-bilder/digitaltveikart_2020.pdf'))),
            array('id' => 'DV kap.4 - Sluttbrukerlosninger i markedet', 'group' => 'DV', 'desc' => 'Sluttbrukerlosninger basert pa fellesrammeverket.', 'links' => array(array('label' => 'BNL: Digitalt veikart 2.0 (PDF)', 'url' => 'https://www.bnl.no/siteassets/bilder/generelle-bilder/digitaltveikart_2020.pdf'))),
            array('id' => 'DV kap.4 - Forvaltning av felles rammeverk', 'group' => 'DV', 'desc' => 'Forvaltning/forvaltningsmodell for felles rammeverk.', 'links' => array(array('label' => 'BNL: Digitalt veikart 2.0 (PDF)', 'url' => 'https://www.bnl.no/siteassets/bilder/generelle-bilder/digitaltveikart_2020.pdf'))),
            array('id' => 'DV kap.6 - Fremtidig bruk av ny digital teknologi', 'group' => 'DV', 'desc' => 'Overblikk: hvordan ny teknologi kan utnyttes i byggenaeringen.', 'links' => array(array('label' => 'BNL: Digitalt veikart 2.0 (PDF)', 'url' => 'https://www.bnl.no/siteassets/bilder/generelle-bilder/digitaltveikart_2020.pdf'))),
            array('id' => 'DV kap.6 - Digital tvilling og sensorer/IoT', 'group' => 'DV', 'desc' => 'Digital tvilling og sensorer/IoT for livslopsinformasjon.', 'links' => array(array('label' => 'BNL: Digitalt veikart 2.0 (PDF)', 'url' => 'https://www.bnl.no/siteassets/bilder/generelle-bilder/digitaltveikart_2020.pdf'))),
            array('id' => 'DV kap.6 - Industriell produksjon/prefab', 'group' => 'DV', 'desc' => 'Industriell produksjon, prefabrikasjon og modulbyggeri.', 'links' => array(array('label' => 'BNL: Digitalt veikart 2.0 (PDF)', 'url' => 'https://www.bnl.no/siteassets/bilder/generelle-bilder/digitaltveikart_2020.pdf'))),
            array('id' => 'DV kap.6 - Dataanalyse og KI', 'group' => 'DV', 'desc' => 'Dataanalyse og kunstig intelligens for beslutningsstotte.', 'links' => array(array('label' => 'BNL: Digitalt veikart 2.0 (PDF)', 'url' => 'https://www.bnl.no/siteassets/bilder/generelle-bilder/digitaltveikart_2020.pdf'))),
        ),
        'links' => array(
            array('source' => 'TEK17 kap.6 Beregnings- og maleregler', 'target' => 'NS 3940 (Areal-/volumberegning)'),
            array('source' => 'TEK17 kap.14 Energi', 'target' => 'NS 3031 (Energi- og effektbehov)'),
            array('source' => 'TEK17 kap.2 Dokumentasjon av oppfyllelse', 'target' => 'NS-EN ISO 19650 (informasjon/BIM)'),
            array('source' => 'TEK17 kap.4 FDV-dokumentasjon', 'target' => 'NS-EN ISO 19650 (informasjon/BIM)'),
            array('source' => 'TEK17 kap.12 Planlosning/bygningsdeler', 'target' => 'NS 3451 (Bygningsdelstabell)'),
            array('source' => 'TEK17 kap.12 Planlosning/bygningsdeler', 'target' => 'NS 8360-serien (BIM-objekter)'),
            array('source' => 'TEK17 kap.3 Byggevarer (dokumentasjon)', 'target' => 'NS-EN ISO 19650 (informasjon/BIM)'),
            array('source' => 'TEK17 kap.13 Inneklima og helse', 'target' => 'NS 3031 (Energi- og effektbehov)'),
            array('source' => 'TEK17 kap.9 Ytre miljo', 'target' => 'NS 3424 (Tilstandsanalyse)'),
            array('source' => 'SAK10 Kap.5 Soknad og dokumentasjon', 'target' => 'NS-EN ISO 19650 (informasjon/BIM)'),
            array('source' => 'SAK10 Kap.10 Kvalitetssikring', 'target' => 'NS-EN ISO 19650 (informasjon/BIM)'),
            array('source' => 'SAK10 Kap.12 Ansvar (soker/prosjekterende/utforende)', 'target' => 'NS-EN ISO 19650 (informasjon/BIM)'),
            array('source' => 'SAK10 Kap.9 Foretak og tiltaksklasser', 'target' => 'TEK17 kap.2 Dokumentasjon av oppfyllelse'),
            array('source' => 'SAK10 Kap.14-15 Kontroll og tilsyn', 'target' => 'TEK17 kap.2 Dokumentasjon av oppfyllelse'),
            array('source' => 'NS-EN ISO 16739-1 (IFC)', 'target' => 'NS 8360-serien (BIM-objekter)'),
            array('source' => 'NS-EN ISO 16739-1 (IFC)', 'target' => 'NS 3451 (Bygningsdelstabell)'),
            array('source' => 'NS-EN ISO 19650 (informasjon/BIM)', 'target' => 'NS-EN ISO 16739-1 (IFC)'),
            array('source' => 'DV kap.4 - Felles rammeverk: Internasjonale rammer', 'target' => 'NS-EN ISO 19650 (informasjon/BIM)'),
            array('source' => 'DV kap.4 - Felles rammeverk: Internasjonale rammer', 'target' => 'NS-EN ISO 16739-1 (IFC)'),
            array('source' => 'DV kap.4 - Felles rammeverk: Nasjonale rammer', 'target' => 'TEK17 kap.2 Dokumentasjon av oppfyllelse'),
            array('source' => 'DV kap.4 - Felles rammeverk: Nasjonale rammer', 'target' => 'SAK10 Kap.5 Soknad og dokumentasjon'),
            array('source' => 'DV kap.4 - Felles spesifikasjoner og komponenter', 'target' => 'NS 8360-serien (BIM-objekter)'),
            array('source' => 'DV kap.4 - Felles spesifikasjoner og komponenter', 'target' => 'NS 3451 (Bygningsdelstabell)'),
            array('source' => 'DV kap.4 - Standarder for API', 'target' => 'NS-EN ISO 19650 (informasjon/BIM)'),
            array('source' => 'DV kap.4 - Sluttbrukerlosninger i markedet', 'target' => 'NS-EN ISO 19650 (informasjon/BIM)'),
            array('source' => 'DV kap.4 - Forvaltning av felles rammeverk', 'target' => 'TEK17 kap.4 FDV-dokumentasjon'),
            array('source' => 'DV kap.6 - Fremtidig bruk av ny digital teknologi', 'target' => 'TEK17 kap.4 FDV-dokumentasjon'),
            array('source' => 'DV kap.6 - Digital tvilling og sensorer/IoT', 'target' => 'TEK17 kap.4 FDV-dokumentasjon'),
            array('source' => 'DV kap.6 - Digital tvilling og sensorer/IoT', 'target' => 'NS-EN ISO 19650 (informasjon/BIM)'),
            array('source' => 'DV kap.6 - Digital tvilling og sensorer/IoT', 'target' => 'NS-EN ISO 16739-1 (IFC)'),
            array('source' => 'DV kap.6 - Industriell produksjon/prefab', 'target' => 'TEK17 kap.12 Planlosning/bygningsdeler'),
            array('source' => 'DV kap.6 - Industriell produksjon/prefab', 'target' => 'NS 3451 (Bygningsdelstabell)'),
            array('source' => 'DV kap.6 - Dataanalyse og KI', 'target' => 'NS-EN ISO 19650 (informasjon/BIM)'),
            array('source' => 'DV kap.6 - Dataanalyse og KI', 'target' => 'SAK10 Kap.10 Kvalitetssikring'),
        ),
    );
    echo wp_json_encode($graph_data);
    ?>;

    var COLORS = { TEK17: '#2E86DE', SAK10: '#FF8C00', 'NS/ISO': '#27AE60', DV: '#8E44AD' };

    var vizEl = document.getElementById('policy-viz');
    var width = vizEl.clientWidth;
    var height = vizEl.clientHeight;

    var svg = d3.select('#policy-viz').append('svg')
        .attr('width', width)
        .attr('height', height);

    var zoomBehavior = d3.zoom().scaleExtent([0.2, 4]).on('zoom', function(e) {
        g.attr('transform', e.transform);
    });
    svg.call(zoomBehavior);

    var g = svg.append('g');

    var link = g.selectAll('.link')
        .data(DATA.links)
        .enter().append('line')
        .attr('class', 'link')
        .attr('stroke-width', 1.8);

    var node = g.selectAll('.node')
        .data(DATA.nodes)
        .enter().append('g')
        .attr('class', 'node')
        .call(d3.drag()
            .on('start', dragstarted)
            .on('drag', dragged)
            .on('end', dragended));

    node.append('circle')
        .attr('r', function(d) { return d.group === 'DV' ? 12 : 9; })
        .attr('fill', function(d) { return COLORS[d.group] || '#999'; })
        .on('click', function(event, d) { showDetails(d); });

    node.append('title').text(function(d) { return d.id; });

    node.append('text')
        .text(function(d) { return d.id; })
        .attr('x', 14)
        .attr('y', 4)
        .attr('font-size', '11px');

    node.on('dblclick', function(event, d) {
        d.fx = (d.fx == null ? d.x : null);
        d.fy = (d.fy == null ? d.y : null);
    });

    var sim = d3.forceSimulation(DATA.nodes)
        .force('link', d3.forceLink(DATA.links).id(function(d) { return d.id; }).distance(120).strength(0.4))
        .force('charge', d3.forceManyBody().strength(-280))
        .force('center', d3.forceCenter(width / 2, height / 2))
        .force('collide', d3.forceCollide().radius(28));

    sim.on('tick', function() {
        link.attr('x1', function(d) { return d.source.x; })
            .attr('y1', function(d) { return d.source.y; })
            .attr('x2', function(d) { return d.target.x; })
            .attr('y2', function(d) { return d.target.y; });
        node.attr('transform', function(d) { return 'translate(' + d.x + ',' + d.y + ')'; });
    });

    function dragstarted(event, d) {
        if (!event.active) sim.alphaTarget(0.3).restart();
        d.fx = d.x; d.fy = d.y;
    }
    function dragged(event, d) { d.fx = event.x; d.fy = event.y; }
    function dragended(event, d) { if (!event.active) sim.alphaTarget(0); }

    // Filter by group
    var checkboxes = document.querySelectorAll('.grp');
    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', updateVisibility);
    });

    function updateVisibility() {
        var on = Array.from(checkboxes).filter(function(c) { return c.checked; }).map(function(c) { return c.value; });
        node.classed('hidden', function(d) { return on.indexOf(d.group) === -1; });
        link.classed('hidden', function(d) { return !(on.indexOf(d.source.group) > -1 && on.indexOf(d.target.group) > -1); });
    }
    updateVisibility();

    // Search
    var searchInput = document.getElementById('policy-search');
    searchInput.addEventListener('input', function() {
        var q = searchInput.value.toLowerCase();
        node.selectAll('circle')
            .attr('stroke-width', function(d) { return d.id.toLowerCase().indexOf(q) > -1 && q ? 3 : 1.5; })
            .attr('stroke', function(d) { return d.id.toLowerCase().indexOf(q) > -1 && q ? '#1A1A1A' : '#fff'; });
    });

    function showDetails(d) {
        var wrap = document.getElementById('policy-details');
        var pillColor = COLORS[d.group] || '#999';
        var linksHtml = (d.links || []).map(function(l) {
            return '<li><a href="' + l.url + '" target="_blank" rel="noopener">' + l.label + '</a></li>';
        }).join('');
        wrap.innerHTML =
            '<div class="node-card">' +
            '<h3>' + d.id + ' <span class="node-pill" style="background:' + pillColor + '">' + d.group + '</span></h3>' +
            '<p>' + (d.desc || '') + '</p>' +
            (linksHtml ? '<p style="font-size:12px;font-weight:600;color:#888;margin-top:12px;">Lenker</p><ul>' + linksHtml + '</ul>' : '') +
            '<p style="font-size:11px;color:#aaa;margin-top:12px;">Dobbeltklikk noden for a feste posisjonen.</p>' +
            '</div>';
    }

    // Handle resize
    window.addEventListener('resize', function() {
        var newWidth = vizEl.clientWidth;
        var newHeight = vizEl.clientHeight;
        svg.attr('width', newWidth).attr('height', newHeight);
        sim.force('center', d3.forceCenter(newWidth / 2, newHeight / 2));
        sim.alpha(0.3).restart();
    });
})();
</script>

<?php get_footer(); ?>
