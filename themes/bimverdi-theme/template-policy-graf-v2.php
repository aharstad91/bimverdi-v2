<?php
/**
 * Template Name: Policy-graf v2
 *
 * Enhanced version with neighbor highlighting, dynamic node sizing,
 * gradient links, animated transitions, and BIM Verdi styling.
 *
 * Original prototype by Bard. Enhanced by BIM Verdi.
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<style>
    /* Full-screen layout overrides */
    #content { display: contents; }
    body footer { display: none; }

    .pg2-wrap {
        display: flex;
        height: calc(100vh - 64px - 40px);
    }

    /* --- Sidebar --- */
    .pg2-sidebar {
        width: 380px;
        border-right: 1px solid #E7E5E4;
        overflow-y: auto;
        background: #FAFAF9;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
    }
    .pg2-sidebar-inner {
        padding: 24px;
        flex: 1;
        overflow-y: auto;
    }
    .pg2-sidebar h2 {
        font-size: 20px;
        font-weight: 700;
        color: #1A1A1A;
        margin: 0;
        line-height: 1.3;
    }
    .pg2-subtitle {
        font-size: 13px;
        color: #5A5A5A;
        margin: 4px 0 0 0;
        line-height: 1.5;
    }
    .pg2-stats-bar {
        display: flex;
        gap: 16px;
        margin: 16px 0;
        padding: 12px 16px;
        background: #fff;
        border: 1px solid #E7E5E4;
        border-radius: 10px;
    }
    .pg2-stat {
        text-align: center;
        flex: 1;
    }
    .pg2-stat-num {
        font-size: 22px;
        font-weight: 700;
        color: #FF8B5E;
        line-height: 1;
    }
    .pg2-stat-label {
        font-size: 10px;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 2px;
    }

    .pg2-section-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #A8A29E;
        margin: 20px 0 8px 0;
    }

    /* Filter chips */
    .pg2-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .pg2-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.2s ease;
        user-select: none;
    }
    .pg2-chip input { display: none; }
    .pg2-chip .chip-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    .pg2-chip--active {
        border-color: currentColor;
    }
    .pg2-chip--inactive {
        opacity: 0.4;
        background: #f0f0f0;
    }

    /* Search */
    .pg2-search {
        width: 100%;
        padding: 10px 12px 10px 36px;
        border: 1px solid #D6D1C6;
        border-radius: 8px;
        font-size: 14px;
        background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") 10px center no-repeat;
        color: #1A1A1A;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .pg2-search:focus {
        outline: none;
        border-color: #FF8B5E;
        box-shadow: 0 0 0 3px rgba(255, 139, 94, 0.12);
    }

    .pg2-tips {
        font-size: 12px;
        color: #A8A29E;
        line-height: 1.5;
        margin: 12px 0 0 0;
    }
    .pg2-hr {
        border: none;
        border-top: 1px solid #E7E5E4;
        margin: 16px 0;
    }

    /* Detail card */
    .pg2-card {
        background: #fff;
        border: 1px solid #E7E5E4;
        border-radius: 12px;
        padding: 20px;
        transition: all 0.3s ease;
    }
    .pg2-card--highlight {
        border-color: #FF8B5E;
        box-shadow: 0 4px 20px rgba(255, 139, 94, 0.1);
    }
    .pg2-card h3 {
        font-size: 15px;
        font-weight: 600;
        color: #1A1A1A;
        margin: 0 0 4px 0;
        line-height: 1.4;
    }
    .pg2-card-group {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        color: #fff;
        margin-bottom: 10px;
    }
    .pg2-card-desc {
        font-size: 13px;
        color: #5A5A5A;
        line-height: 1.6;
        margin: 0 0 12px 0;
    }
    .pg2-card-connections {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #888;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f0f0f0;
    }
    .pg2-card-connections strong {
        color: #FF8B5E;
        font-size: 16px;
    }
    .pg2-card-links-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #A8A29E;
        margin: 0 0 6px 0;
    }
    .pg2-card ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .pg2-card li {
        padding: 4px 0;
    }
    .pg2-card a {
        color: #FF8B5E;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: color 0.15s;
    }
    .pg2-card a:hover { color: #e87a4f; text-decoration: underline; }

    .pg2-card-neighbors {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #f0f0f0;
    }
    .pg2-card-neighbors .pg2-neighbor {
        display: inline-block;
        padding: 3px 8px;
        margin: 2px 3px 2px 0;
        border-radius: 6px;
        font-size: 11px;
        background: #f5f5f0;
        color: #5A5A5A;
        cursor: pointer;
        transition: all 0.15s;
    }
    .pg2-card-neighbors .pg2-neighbor:hover {
        background: #FF8B5E;
        color: #fff;
    }

    /* --- Viz area --- */
    .pg2-viz {
        flex: 1;
        position: relative;
        background: #FCFBF9;
        overflow: hidden;
    }

    /* D3 styling */
    .pg2-viz svg { display: block; }
    .pg2-node { cursor: pointer; }
    .pg2-node circle {
        stroke: #fff;
        stroke-width: 2px;
        transition: opacity 0.3s ease, stroke-width 0.2s ease, r 0.3s ease;
    }
    .pg2-node text {
        font-family: system-ui, -apple-system, sans-serif;
        fill: #1A1A1A;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    .pg2-link {
        transition: opacity 0.3s ease, stroke-width 0.3s ease;
    }
    .pg2-node--dimmed circle { opacity: 0.12; }
    .pg2-node--dimmed text { opacity: 0.08; }
    .pg2-link--dimmed { opacity: 0.04 !important; }
    .pg2-link--highlighted { stroke-width: 3px !important; opacity: 0.8 !important; }
    .pg2-node--highlighted circle {
        stroke: #1A1A1A;
        stroke-width: 3px;
        filter: drop-shadow(0 0 6px rgba(0,0,0,0.15));
    }
    .pg2-node--selected circle {
        stroke: #FF8B5E;
        stroke-width: 4px;
        filter: drop-shadow(0 0 10px rgba(255,139,94,0.4));
    }
    .hidden { display: none; }

    /* Hover tooltip */
    .pg2-tooltip {
        position: absolute;
        pointer-events: none;
        background: #1A1A1A;
        color: #fff;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
        opacity: 0;
        transition: opacity 0.15s ease;
        z-index: 10;
    }
    .pg2-tooltip--visible { opacity: 1; }

    /* Reset button */
    .pg2-reset {
        position: absolute;
        bottom: 20px;
        right: 20px;
        padding: 8px 16px;
        background: #1A1A1A;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 5;
    }
    .pg2-reset--visible { opacity: 1; }
    .pg2-reset:hover { background: #333; }

    /* Mobile */
    @media (max-width: 768px) {
        .pg2-wrap { flex-direction: column; height: auto; }
        .pg2-sidebar { width: 100%; max-height: 280px; border-right: none; border-bottom: 1px solid #E7E5E4; }
        .pg2-viz { height: 60vh; }
    }
</style>

<div class="pg2-wrap">
    <aside class="pg2-sidebar">
        <div class="pg2-sidebar-inner">
            <h2>Koblinger i regelverket</h2>
            <p class="pg2-subtitle">Utforsk hvordan forskrifter, standarder og Digitalt Veikart henger sammen</p>

            <div class="pg2-stats-bar">
                <div class="pg2-stat"><div class="pg2-stat-num" id="pg2-count-nodes">37</div><div class="pg2-stat-label">Noder</div></div>
                <div class="pg2-stat"><div class="pg2-stat-num" id="pg2-count-links">33</div><div class="pg2-stat-label">Koblinger</div></div>
                <div class="pg2-stat"><div class="pg2-stat-num">4</div><div class="pg2-stat-label">Kategorier</div></div>
            </div>

            <p class="pg2-section-label">Filter</p>
            <div class="pg2-filters" id="pg2-filters"></div>

            <p class="pg2-section-label">Sok</p>
            <input type="search" class="pg2-search" id="pg2-search" placeholder="Sok etter node...">

            <p class="pg2-tips">Klikk en node for a se koblinger. Dobbeltklikk for a feste. Rull for zoom.</p>

            <hr class="pg2-hr">

            <div id="pg2-details">
                <div class="pg2-card">
                    <h3>Velg en node i grafen</h3>
                    <p class="pg2-card-desc">Klikk pa en node for a se beskrivelse, kildelenker og alle koblede noder. Nabonoder fremheves automatisk.</p>
                </div>
            </div>
        </div>
    </aside>

    <main class="pg2-viz" id="pg2-viz">
        <div class="pg2-tooltip" id="pg2-tooltip"></div>
        <button class="pg2-reset" id="pg2-reset">Tilbakestill visning</button>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.9.0/d3.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function() {
    'use strict';

    /* ── Data ── */
    var DATA = <?php
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
    var GROUP_LABELS = { TEK17: 'TEK17', SAK10: 'SAK10', 'NS/ISO': 'NS/ISO', DV: 'Digitalt Veikart' };

    /* ── Pre-compute connection counts ── */
    var connCount = {};
    DATA.nodes.forEach(function(n) { connCount[n.id] = 0; });
    DATA.links.forEach(function(l) {
        connCount[l.source] = (connCount[l.source] || 0) + 1;
        connCount[l.target] = (connCount[l.target] || 0) + 1;
    });
    DATA.nodes.forEach(function(n) { n.connCount = connCount[n.id] || 0; });

    /* ── Build adjacency map ── */
    var neighbors = {};
    DATA.nodes.forEach(function(n) { neighbors[n.id] = new Set(); });
    DATA.links.forEach(function(l) {
        neighbors[l.source].add(l.target);
        neighbors[l.target].add(l.source);
    });

    /* ── Filter chips ── */
    var filterEl = document.getElementById('pg2-filters');
    var activeGroups = new Set(Object.keys(COLORS));
    Object.keys(COLORS).forEach(function(grp) {
        var chip = document.createElement('label');
        chip.className = 'pg2-chip pg2-chip--active';
        chip.style.color = COLORS[grp];
        chip.style.background = COLORS[grp] + '15';
        chip.innerHTML = '<input type="checkbox" value="' + grp + '" checked><span class="chip-dot" style="background:' + COLORS[grp] + '"></span>' + GROUP_LABELS[grp];
        chip.querySelector('input').addEventListener('change', function(e) {
            if (e.target.checked) { activeGroups.add(grp); chip.className = 'pg2-chip pg2-chip--active'; chip.style.background = COLORS[grp] + '15'; }
            else { activeGroups.delete(grp); chip.className = 'pg2-chip pg2-chip--inactive'; chip.style.background = '#f0f0f0'; }
            updateVisibility();
        });
        filterEl.appendChild(chip);
    });

    /* ── D3 Setup ── */
    var vizEl = document.getElementById('pg2-viz');
    var width = vizEl.clientWidth;
    var height = vizEl.clientHeight;

    var svg = d3.select('#pg2-viz').append('svg').attr('width', width).attr('height', height);

    /* Gradient defs for links */
    var defs = svg.append('defs');
    DATA.links.forEach(function(l, i) {
        var grad = defs.append('linearGradient').attr('id', 'lg-' + i).attr('gradientUnits', 'userSpaceOnUse');
        grad.append('stop').attr('offset', '0%').attr('stop-color', COLORS[typeof l.source === 'object' ? l.source.group : DATA.nodes.find(function(n){ return n.id === l.source; }).group] || '#999');
        grad.append('stop').attr('offset', '100%').attr('stop-color', COLORS[typeof l.target === 'object' ? l.target.group : DATA.nodes.find(function(n){ return n.id === l.target; }).group] || '#999');
    });

    svg.call(d3.zoom().scaleExtent([0.2, 4]).on('zoom', function(e) { g.attr('transform', e.transform); }));

    var g = svg.append('g');

    var link = g.selectAll('.pg2-link')
        .data(DATA.links).enter().append('line')
        .attr('class', 'pg2-link')
        .attr('stroke', function(d, i) { return 'url(#lg-' + i + ')'; })
        .attr('stroke-width', 1.5)
        .attr('stroke-opacity', 0.3);

    var node = g.selectAll('.pg2-node')
        .data(DATA.nodes).enter().append('g')
        .attr('class', 'pg2-node')
        .call(d3.drag().on('start', dragstarted).on('drag', dragged).on('end', dragended));

    /* Size nodes by connection count: min 7, max 20 */
    function nodeRadius(d) {
        return Math.max(7, Math.min(20, 6 + d.connCount * 2));
    }

    node.append('circle')
        .attr('r', nodeRadius)
        .attr('fill', function(d) { return COLORS[d.group] || '#999'; });

    node.append('text')
        .text(function(d) { return d.id; })
        .attr('x', function(d) { return nodeRadius(d) + 4; })
        .attr('y', 4)
        .attr('font-size', '11px');

    /* ── Tooltip ── */
    var tooltipEl = document.getElementById('pg2-tooltip');
    node.on('mouseenter', function(event, d) {
        tooltipEl.textContent = d.id + ' (' + d.connCount + ' koblinger)';
        tooltipEl.classList.add('pg2-tooltip--visible');
    }).on('mousemove', function(event) {
        var rect = vizEl.getBoundingClientRect();
        tooltipEl.style.left = (event.clientX - rect.left + 12) + 'px';
        tooltipEl.style.top = (event.clientY - rect.top - 30) + 'px';
    }).on('mouseleave', function() {
        tooltipEl.classList.remove('pg2-tooltip--visible');
    });

    /* ── Click: Neighbor Highlighting ── */
    var selectedNode = null;
    var resetBtn = document.getElementById('pg2-reset');

    node.on('click', function(event, d) {
        event.stopPropagation();
        selectedNode = d;
        highlightNeighbors(d);
        showDetails(d);
        resetBtn.classList.add('pg2-reset--visible');
    });

    node.on('dblclick', function(event, d) {
        d.fx = (d.fx == null ? d.x : null);
        d.fy = (d.fy == null ? d.y : null);
    });

    svg.on('click', function() { clearHighlight(); });
    resetBtn.addEventListener('click', function() { clearHighlight(); });

    function highlightNeighbors(d) {
        var neighborSet = neighbors[d.id];
        node.classed('pg2-node--dimmed', function(n) { return n.id !== d.id && !neighborSet.has(n.id); });
        node.classed('pg2-node--highlighted', function(n) { return neighborSet.has(n.id); });
        node.classed('pg2-node--selected', function(n) { return n.id === d.id; });
        link.classed('pg2-link--dimmed', function(l) { return l.source.id !== d.id && l.target.id !== d.id; });
        link.classed('pg2-link--highlighted', function(l) { return l.source.id === d.id || l.target.id === d.id; });
    }

    function clearHighlight() {
        selectedNode = null;
        node.classed('pg2-node--dimmed', false).classed('pg2-node--highlighted', false).classed('pg2-node--selected', false);
        link.classed('pg2-link--dimmed', false).classed('pg2-link--highlighted', false);
        resetBtn.classList.remove('pg2-reset--visible');
    }

    /* ── Simulation ── */
    var sim = d3.forceSimulation(DATA.nodes)
        .force('link', d3.forceLink(DATA.links).id(function(d) { return d.id; }).distance(140).strength(0.35))
        .force('charge', d3.forceManyBody().strength(-350))
        .force('center', d3.forceCenter(width / 2, height / 2))
        .force('collide', d3.forceCollide().radius(function(d) { return nodeRadius(d) + 8; }));

    sim.on('tick', function() {
        link.attr('x1', function(d) { return d.source.x; })
            .attr('y1', function(d) { return d.source.y; })
            .attr('x2', function(d) { return d.target.x; })
            .attr('y2', function(d) { return d.target.y; });
        node.attr('transform', function(d) { return 'translate(' + d.x + ',' + d.y + ')'; });

        /* Update gradient positions */
        DATA.links.forEach(function(l, i) {
            var grad = d3.select('#lg-' + i);
            grad.attr('x1', l.source.x).attr('y1', l.source.y).attr('x2', l.target.x).attr('y2', l.target.y);
        });
    });

    function dragstarted(event, d) { if (!event.active) sim.alphaTarget(0.3).restart(); d.fx = d.x; d.fy = d.y; }
    function dragged(event, d) { d.fx = event.x; d.fy = event.y; }
    function dragended(event, d) { if (!event.active) sim.alphaTarget(0); }

    /* ── Filter ── */
    function updateVisibility() {
        node.classed('hidden', function(d) { return !activeGroups.has(d.group); });
        link.classed('hidden', function(d) { return !(activeGroups.has(d.source.group) && activeGroups.has(d.target.group)); });
    }

    /* ── Search ── */
    document.getElementById('pg2-search').addEventListener('input', function() {
        var q = this.value.toLowerCase();
        if (!q) { clearHighlight(); return; }
        node.classed('pg2-node--dimmed', function(d) { return d.id.toLowerCase().indexOf(q) === -1; });
        node.classed('pg2-node--highlighted', function(d) { return d.id.toLowerCase().indexOf(q) > -1; });
        link.classed('pg2-link--dimmed', true);
    });

    /* ── Detail card ── */
    function showDetails(d) {
        var wrap = document.getElementById('pg2-details');
        var neighborSet = neighbors[d.id];
        var neighborArr = Array.from(neighborSet);
        var linksHtml = (d.links || []).map(function(l) {
            return '<li><a href="' + l.url + '" target="_blank" rel="noopener">' + l.label + '</a></li>';
        }).join('');
        var neighborsHtml = neighborArr.map(function(nId) {
            return '<span class="pg2-neighbor" data-id="' + nId + '">' + nId.replace(/^(TEK17|SAK10|NS-EN ISO|NS |DV kap\.\d+)\s*[-–]\s*/, '') + '</span>';
        }).join('');

        wrap.innerHTML =
            '<div class="pg2-card pg2-card--highlight">' +
            '<span class="pg2-card-group" style="background:' + (COLORS[d.group] || '#999') + '">' + d.group + '</span>' +
            '<h3>' + d.id + '</h3>' +
            '<p class="pg2-card-desc">' + (d.desc || '') + '</p>' +
            '<div class="pg2-card-connections"><strong>' + d.connCount + '</strong> koblinger til andre noder</div>' +
            (linksHtml ? '<p class="pg2-card-links-label">Kilder</p><ul>' + linksHtml + '</ul>' : '') +
            (neighborsHtml ? '<div class="pg2-card-neighbors"><p class="pg2-card-links-label">Koblede noder</p>' + neighborsHtml + '</div>' : '') +
            '</div>';

        /* Make neighbor chips clickable */
        wrap.querySelectorAll('.pg2-neighbor').forEach(function(el) {
            el.addEventListener('click', function() {
                var targetId = el.getAttribute('data-id');
                var targetNode = DATA.nodes.find(function(n) { return n.id === targetId; });
                if (targetNode) {
                    highlightNeighbors(targetNode);
                    showDetails(targetNode);
                }
            });
        });
    }

    /* ── Resize ── */
    window.addEventListener('resize', function() {
        var w = vizEl.clientWidth, h = vizEl.clientHeight;
        svg.attr('width', w).attr('height', h);
        sim.force('center', d3.forceCenter(w / 2, h / 2)).alpha(0.3).restart();
    });
})();
</script>

<?php get_footer(); ?>
