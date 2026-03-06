<?php
/**
 * Template Name: Veikart-prosjekter
 *
 * Interaktiv graf som kobler prosjekter i byggenaeringen
 * til overskrifter fra Digitalt Veikart 2.0 (kap. 4 & 6).
 *
 * Statisk prototype - data hardkodet for demo.
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($GLOBALS['bimverdi_skip_header_footer'])) {
    get_header();
}
?>

<style>
    /* Full-screen layout */
    #content { display: contents; }
    body footer { display: none; }

    .vp-wrap {
        display: flex;
        height: calc(100vh - 64px - 40px);
    }

    /* --- Sidebar --- */
    .vp-sidebar {
        width: 400px;
        border-right: 1px solid #E7E5E4;
        overflow-y: auto;
        background: #FAFAF9;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
    }
    .vp-sidebar-inner {
        padding: 24px;
        flex: 1;
        overflow-y: auto;
    }
    .vp-sidebar h2 {
        font-size: 20px;
        font-weight: 700;
        color: #1A1A1A;
        margin: 0;
        line-height: 1.3;
    }
    .vp-subtitle {
        font-size: 13px;
        color: #5A5A5A;
        margin: 4px 0 0 0;
        line-height: 1.5;
    }

    /* Stats bar */
    .vp-stats-bar {
        display: flex;
        gap: 12px;
        margin: 16px 0;
        padding: 12px 16px;
        background: #fff;
        border: 1px solid #E7E5E4;
        border-radius: 10px;
    }
    .vp-stat {
        text-align: center;
        flex: 1;
    }
    .vp-stat-num {
        font-size: 22px;
        font-weight: 700;
        color: #FF8B5E;
        line-height: 1;
    }
    .vp-stat-label {
        font-size: 10px;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 2px;
    }

    .vp-section-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #A8A29E;
        margin: 20px 0 8px 0;
    }

    /* Filter chips */
    .vp-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .vp-chip {
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
    .vp-chip input { display: none; }
    .vp-chip .chip-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    .vp-chip--active { border-color: currentColor; }
    .vp-chip--inactive { opacity: 0.4; background: #f0f0f0 !important; }

    /* Search */
    .vp-search {
        width: 100%;
        padding: 10px 12px 10px 36px;
        border: 1px solid #D6D1C6;
        border-radius: 8px;
        font-size: 14px;
        background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") 10px center no-repeat;
        color: #1A1A1A;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .vp-search:focus {
        outline: none;
        border-color: #FF8B5E;
        box-shadow: 0 0 0 3px rgba(255, 139, 94, 0.12);
    }

    .vp-tips {
        font-size: 12px;
        color: #A8A29E;
        line-height: 1.5;
        margin: 12px 0 0 0;
    }
    .vp-hr {
        border: none;
        border-top: 1px solid #E7E5E4;
        margin: 16px 0;
    }

    /* Detail card */
    .vp-card {
        background: #fff;
        border: 1px solid #E7E5E4;
        border-radius: 12px;
        padding: 20px;
        transition: all 0.3s ease;
    }
    .vp-card--highlight {
        border-color: #FF8B5E;
        box-shadow: 0 4px 20px rgba(255, 139, 94, 0.1);
    }
    .vp-card h3 {
        font-size: 15px;
        font-weight: 600;
        color: #1A1A1A;
        margin: 0 0 4px 0;
        line-height: 1.4;
    }
    .vp-card-group {
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
    .vp-card-desc {
        font-size: 13px;
        color: #5A5A5A;
        line-height: 1.6;
        margin: 0 0 12px 0;
    }
    .vp-card-org {
        font-size: 12px;
        color: #888;
        margin: 0 0 8px 0;
    }
    .vp-card-connections {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #888;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f0f0f0;
    }
    .vp-card-connections strong {
        color: #FF8B5E;
        font-size: 16px;
    }
    .vp-card-links-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #A8A29E;
        margin: 0 0 6px 0;
    }
    .vp-card ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .vp-card li { padding: 4px 0; }
    .vp-card a {
        color: #FF8B5E;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: color 0.15s;
    }
    .vp-card a:hover { color: #e87a4f; text-decoration: underline; }

    .vp-card-neighbors {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #f0f0f0;
    }
    .vp-card-neighbors .vp-neighbor {
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
    .vp-card-neighbors .vp-neighbor:hover {
        background: #FF8B5E;
        color: #fff;
    }

    /* --- Viz area --- */
    .vp-viz {
        flex: 1;
        position: relative;
        background: #FCFBF9;
        overflow: hidden;
    }

    /* D3 styling */
    .vp-viz svg { display: block; }
    .vp-node { cursor: pointer; }
    .vp-node circle {
        stroke: #fff;
        stroke-width: 2px;
        transition: opacity 0.3s ease, stroke-width 0.2s ease;
    }
    .vp-node rect {
        stroke: #fff;
        stroke-width: 2px;
        rx: 4;
        ry: 4;
        transition: opacity 0.3s ease, stroke-width 0.2s ease;
    }
    .vp-node text {
        font-family: system-ui, -apple-system, sans-serif;
        fill: #1A1A1A;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    .vp-link {
        transition: opacity 0.3s ease, stroke-width 0.3s ease;
    }
    .vp-node--dimmed circle,
    .vp-node--dimmed rect { opacity: 0.12; }
    .vp-node--dimmed text { opacity: 0.08; }
    .vp-link--dimmed { opacity: 0.04 !important; }
    .vp-link--highlighted { stroke-width: 3px !important; opacity: 0.8 !important; }
    .vp-node--highlighted circle,
    .vp-node--highlighted rect {
        stroke: #1A1A1A;
        stroke-width: 3px;
        filter: drop-shadow(0 0 6px rgba(0,0,0,0.15));
    }
    .vp-node--selected circle,
    .vp-node--selected rect {
        stroke: #FF8B5E;
        stroke-width: 4px;
        filter: drop-shadow(0 0 10px rgba(255,139,94,0.4));
    }
    .hidden { display: none; }

    /* Hover tooltip */
    .vp-tooltip {
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
        max-width: 320px;
        white-space: normal;
    }
    .vp-tooltip--visible { opacity: 1; }

    /* Reset button */
    .vp-reset {
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
    .vp-reset--visible { opacity: 1; }
    .vp-reset:hover { background: #333; }

    /* Legend */
    .vp-legend {
        position: absolute;
        top: 16px;
        right: 16px;
        background: rgba(255,255,255,0.95);
        border: 1px solid #E7E5E4;
        border-radius: 10px;
        padding: 12px 16px;
        font-size: 12px;
        z-index: 5;
        backdrop-filter: blur(8px);
    }
    .vp-legend-title {
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #A8A29E;
        margin-bottom: 8px;
    }
    .vp-legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 4px 0;
        color: #5A5A5A;
    }
    .vp-legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .vp-legend-rect {
        width: 12px;
        height: 8px;
        border-radius: 2px;
        flex-shrink: 0;
    }

    /* Mobile */
    @media (max-width: 768px) {
        .vp-wrap { flex-direction: column; height: auto; }
        .vp-sidebar { width: 100%; max-height: 280px; border-right: none; border-bottom: 1px solid #E7E5E4; }
        .vp-viz { height: 60vh; }
        .vp-legend { display: none; }
    }
</style>

<div class="vp-wrap">
    <aside class="vp-sidebar">
        <div class="vp-sidebar-inner">
            <h2>Digitalt Veikart &amp; Prosjekter</h2>
            <p class="vp-subtitle">Kobler prosjekter i byggenaeringen til maalomraader fra Digitalt Veikart 2.0 (kap. 4 &amp; 6)</p>

            <div class="vp-stats-bar">
                <div class="vp-stat"><div class="vp-stat-num" id="vp-count-nodes">0</div><div class="vp-stat-label">Noder</div></div>
                <div class="vp-stat"><div class="vp-stat-num" id="vp-count-links">0</div><div class="vp-stat-label">Koblinger</div></div>
                <div class="vp-stat"><div class="vp-stat-num" id="vp-count-projects">0</div><div class="vp-stat-label">Prosjekter</div></div>
            </div>

            <p class="vp-section-label">Filter</p>
            <div class="vp-filters" id="vp-filters"></div>

            <p class="vp-section-label">Sok</p>
            <input type="search" class="vp-search" id="vp-search" placeholder="Sok etter node eller prosjekt...">

            <p class="vp-tips">Klikk en node for aa se koblinger. Rull for zoom. Dra noder for aa flytte.</p>

            <hr class="vp-hr">

            <div id="vp-details">
                <div class="vp-card">
                    <h3>Velg en node i grafen</h3>
                    <p class="vp-card-desc">Klikk paa en Veikart-overskrift for aa se hvilke prosjekter som jobber mot dette maalet. Klikk paa et prosjekt for aa se hvilke maalomraader det dekker.</p>
                </div>
            </div>
        </div>
    </aside>

    <main class="vp-viz" id="vp-viz">
        <div class="vp-tooltip" id="vp-tooltip"></div>
        <button class="vp-reset" id="vp-reset">Tilbakestill visning</button>
        <div class="vp-legend">
            <div class="vp-legend-title">Nodetyper</div>
            <div class="vp-legend-item"><span class="vp-legend-dot" style="background:#8E44AD"></span> DV Kap. 4 - Felleskomponenter</div>
            <div class="vp-legend-item"><span class="vp-legend-dot" style="background:#2E86DE"></span> DV Kap. 6 - Ny teknologi</div>
            <div class="vp-legend-item"><span class="vp-legend-rect" style="background:#27AE60"></span> Prosjekt / Initiativ</div>
        </div>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.9.0/d3.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function() {
    'use strict';

    /* -- DATA -- */
    var DATA = {
        nodes: [
            /* === DV Kap 4: Felleskomponenter === */
            { id: 'DV 4.1 Felles rammeverk for informasjonsforvaltning', group: 'DV4', desc: 'Felles rammeverk/plattform for samhandling er okosystemet som er nodvendig for at data skal kunne flyte fritt mellom aktorene i byggsektoren. Basert paa EN ISO 19650.', shape: 'circle' },
            { id: 'DV 4.2 Internasjonale rammer', group: 'DV4', desc: 'Standarder, spesifikasjoner og veiledninger i ISO og CEN som et norsk rammeverk maa forholde seg til.', shape: 'circle' },
            { id: 'DV 4.3 Nasjonale rammer', group: 'DV4', desc: 'Nasjonale rammer: begrepsdefinisjoner, regelbaserte vokabularer og felles tjenester innenfor norsk kontekst.', shape: 'circle' },
            { id: 'DV 4.4 Felles spesifikasjoner og komponenter', group: 'DV4', desc: 'Bibliotek med generiske 3D-objekter, nasjonal katalog for produktegenskaper (PDT), felles system for identifikasjon og merking.', shape: 'circle' },
            { id: 'DV 4.5 Etablere standarder for API', group: 'DV4', desc: 'Felles metode for maskin-til-maskin kommunikasjon. Felleskomponenter vil bli levert av flere leverandorer.', shape: 'circle' },
            { id: 'DV 4.6 Sluttbrukerlosninger i markedet', group: 'DV4', desc: 'Kommersielle losninger: modellering, analyse, simulering, visualisering, styring av roboter og automatisering.', shape: 'circle' },
            { id: 'DV 4.7 Forvaltning av felles rammeverk', group: 'DV4', desc: 'Sekretariat/forvaltningsorganisasjon som kan ta eierskap og definere et felles rammeverk for informasjonsforvaltning.', shape: 'circle' },

            /* === DV Kap 6: Ny teknologi === */
            { id: 'DV 6.1 Kunstig intelligens / maskinlaering', group: 'DV6', desc: 'AI brukes effektivt i prosjektering, plassering, design, innkjop, produksjon, selvkjorende enheter, droner osv.', shape: 'circle' },
            { id: 'DV 6.2 Algoritmer', group: 'DV6', desc: 'Algoritmer i mange deler av byggenaeringen: utvikling av plantegninger, bestilling av betong, avfallsfrie byggeplasser.', shape: 'circle' },
            { id: 'DV 6.3 Sensorteknologi', group: 'DV6', desc: 'Sensorer bygges inn i produkter og bygg for alle mulige registreringer: lyd, lys, fukt, vekt, temperatur, CO2, trykk.', shape: 'circle' },
            { id: 'DV 6.4 RFiD', group: 'DV6', desc: 'Tradlos overforing av identifikasjon via radiofrekvenser. Brukes til sporing av byggevarer og kontroll paa byggeplass.', shape: 'circle' },
            { id: 'DV 6.5 Virtual Reality (VR)', group: 'DV6', desc: 'Dataskapt miljo som etterligner virkeligheten. Brukes i prosjektering, salg av bygg og i opplaering.', shape: 'circle' },
            { id: 'DV 6.6 Roboter', group: 'DV6', desc: 'Byggebransjen har fortsatt lav robotiseringsgrad. Borreroboter, elementprodusenter og prefab bruker robotisering.', shape: 'circle' },
            { id: 'DV 6.7 3D-printing', group: 'DV6', desc: 'Bygge en modell i fast materiale via en digital modell. Store "printere" paa larvefoetter som stoper hus.', shape: 'circle' },
            { id: 'DV 6.8 Droner', group: 'DV6', desc: 'Oppmaling av bygg, inspeksjon, frakt av varer, utvikling av tegninger sammen med BIM-modellen.', shape: 'circle' },

            /* === Prosjekter / Initiativer === */
            { id: 'POFIN', group: 'Prosjekt', desc: 'buildingSMART Norges rammeverk for Project and Facilities in Norway. Forbedre og standardisere bruk av openBIM i byggeprosjekter og forvaltning.', org: 'buildingSMART Norge', shape: 'rect', links: [{ label: 'POFIN - buildingSMART', url: 'https://buildingsmart.no/pofin' }] },
            { id: 'PDT Norge', group: 'Prosjekt', desc: 'Offisiell distribusjonsplattform for produktdatamaler (Product Data Templates). Felles digitalt spraak for produktinformasjon i byggenaeringen.', org: 'Cobuilder / BNL', shape: 'rect', links: [{ label: 'PDT Norge - Cobuilder', url: 'https://cobuilder.com/nb/pdt-norge-en-offisiell-distribusjonsplattform-for-produktdatamaler/' }] },
            { id: 'IFC 4.3 ISO-standard', group: 'Prosjekt', desc: 'IFC 4.3 vedtatt som ISO 16739-1:2024. Aapen BIM-datastruktur som dekker bygg, infrastruktur og anlegg.', org: 'buildingSMART International', shape: 'rect', links: [{ label: 'IFC 4.3 ISO - buildingSMART', url: 'https://buildingsmart.no/nyheter/ifc-iso' }] },
            { id: 'Statsbygg BIM-manual 2.0', group: 'Prosjekt', desc: 'Konkret eksempel paa hvordan aapne standarder gir bedre informasjonsflyt. Krav til BIM i alle Statsbygg-prosjekter.', org: 'Statsbygg', shape: 'rect', links: [{ label: 'SBM2 - buildingSMART', url: 'https://arkiv.buildingsmart.no/nyhetsbrev/2019-11/statsbyggs-bim-manual-20-sbm2-et-konkret-eksempel-pa-hvordan-apne-standarder-kan' }] },
            { id: 'BIM for alle (Forsvarsbygg)', group: 'Prosjekt', desc: 'Digitalisering av 4 millioner kvadratmeter paa openBIM. Forsvarsbygg trenger oversikt over kapasitet i eksisterende bygningsmasse.', org: 'Forsvarsbygg', shape: 'rect', links: [{ label: 'BIM for alle - bygg.no', url: 'https://www.bygg.no/bim/sintef-og-buildingsmart-norge-inngar-samarbeidsavtale/1747497' }] },
            { id: 'boligBIM-prosjektet', group: 'Prosjekt', desc: 'Full fart i boligBIM-prosjektet. Standardisering av BIM-praksis for boligbygging med fokus paa effektiv informasjonsflyt.', org: 'Boligprodusentene / bSN', shape: 'rect', links: [{ label: 'boligBIM - bygg.no', url: 'https://www.bygg.no/bolig-boligbygging/full-fart-i-boligbim-prosjektet/2069485' }] },
            { id: 'ISO 19650-serien (norsk)', group: 'Prosjekt', desc: 'NS-EN ISO 19650 oversatt til norsk. Internasjonal standard for informasjonsforvaltning med BIM gjennom hele livssyklusen.', org: 'Standard Norge', shape: 'rect', links: [{ label: 'ISO 19650 - Standard Norge', url: 'https://standard.no/fagomrader/bygg-anlegg-og-eiendom/digital-byggeprosess/iso-19650-serien/' }] },
            { id: 'Digibygg (Statsbygg)', group: 'Prosjekt', desc: 'Overordnet prosjekt for digitalisering og smart teknologi i Statsbyggs byggeprosjekter. Digital tvilling, droner, sensorteknologi.', org: 'Statsbygg', shape: 'rect', links: [{ label: 'Digitale tvillinger - Statsbygg', url: 'https://www.statsbygg.no/nyheter/digitale-tvillinger-i-2023' }] },
            { id: 'Statsbygg droneinspeksjon', group: 'Prosjekt', desc: 'Inspeksjon av tak og fasader med droner, bildegjenkjenning og AI. Forventet aa gi betydelige besparelser i eiendomsdrift.', org: 'Statsbygg', shape: 'rect', links: [{ label: 'Droneinspeksjon - Byggfakta', url: 'https://nyheter.byggfakta.no/statsbygg-inngar-avtaler-om-droneinspeksjon-175538/nyhet.html' }] },
            { id: 'SINTEF AI i bygg', group: 'Prosjekt', desc: 'SINTEF forsker paa bruk av kunstig intelligens i byggenaeringen. Samarbeid med buildingSMART Norge.', org: 'SINTEF', shape: 'rect', links: [{ label: 'AI in construction - SINTEF', url: 'https://www.sintef.no/en/sintef-research-areas/artificial-intelligence/great-effect-using-ai-in-the-construction-industry/' }] },
            { id: 'Veidekke 3D-print betong', group: 'Prosjekt', desc: 'Veidekke tester 3D-printing av betong. Norsk pilotprosjekt for industriell 3D-printing i byggebransjen.', org: 'Veidekke', shape: 'rect', links: [{ label: 'Tech-trender 2026 - Digital Norway', url: 'https://digitalnorway.com/aktuelt/dette-er-tech-trendene-som-vil-prege-2026' }] },
            { id: 'Vegvesen bro-inspeksjon', group: 'Prosjekt', desc: 'Statens vegvesen underseker hvordan dronebilder og automatisert bildegjenkjenning kan brukes til aa inspisere broer.', org: 'Statens vegvesen', shape: 'rect', links: [{ label: 'AI i bygg 2026 - norskbyggebransje.no', url: 'https://norskbyggebransje.no/ai/bygg-og-anlegg-i-2026' }] },
            { id: 'Digital Product Passport (DPP)', group: 'Prosjekt', desc: 'EUs krav om digitale produktpass fra 2026-2029. Cobuilder hjelper byggevareindustrien med aa bli DPP-klar.', org: 'Cobuilder / EU', shape: 'rect', links: [{ label: 'DPP - Cobuilder', url: 'https://cobuilder.com/en/digital-product-passport-dpp/digital-product-passports-for-construction-what-the-eus-2026-2029-cpr-working-plan-means-for-stakeholders/' }] },
            { id: 'Prosjekt Norge digitaliseringsmaaling', group: 'Prosjekt', desc: 'Maaling av effekter av digitalisering i den norske byggenaeringen. Forskning paa hva som virker og hva som ikke virker.', org: 'Prosjekt Norge / NTNU', shape: 'rect', links: [{ label: 'Digitaliseringsmaaling - Prosjekt Norge', url: 'https://www.prosjektnorge.no/maling-av-effekter-av-digitalisering-i-den-norske-byggenaeringen/' }] },
            { id: 'Nordic BIM Digital Twin', group: 'Prosjekt', desc: 'Nordic BIM Group leverer digitale tvillinger for bygg- og eiendomsbransjen. Kommunikasjon og samhandling via 3D-modell.', org: 'Nordic BIM Group', shape: 'rect', links: [{ label: 'Digital Tvilling - Nordic BIM', url: 'https://www.nordicbim.com/no/digital-tvilling' }] },
            { id: 'VA-objekter (Vann og avlop)', group: 'Prosjekt', desc: 'Standardisering og utvikling av felles retningslinjer for modellbasert prosjektgjennomforing i VA-bransjen med fokus paa BIM.', org: 'buildingSMART Norge', shape: 'rect', links: [{ label: 'buildingSMART Norge', url: 'https://buildingsmart.no/' }] },

            /* === Nye prosjekter (runde 2) === */
            { id: 'Spacemaker / Autodesk Forma', group: 'Prosjekt', desc: 'Norskutviklet AI-plattform for tidligfase arealplanlegging. Genererer og evaluerer tusenvis av forslag basert paa sol, stoy og utnyttelse. Kjopt av Autodesk for 2,2 mrd kr.', org: 'Autodesk (tidl. Spacemaker)', shape: 'rect', links: [{ label: 'Autodesk Forma - NTI', url: 'https://www.nti-group.com/no/produkter/autodesk-software/forma/' }] },
            { id: 'Catenda Hub (BIMsync)', group: 'Prosjekt', desc: 'Norsk openBIM-plattform (CDE) for samhandling og prosjektstyring. Stoetter IFC, BCF og COBie. Brukes av Norges storste entreprenorer.', org: 'Catenda', shape: 'rect', links: [{ label: 'Catenda', url: 'https://catenda.com/' }] },
            { id: 'Novorender', group: 'Prosjekt', desc: 'Norsk 3D/BIM-viewer som visualiserer >100 GB data direkte i nettleseren. Brukes av Nye Veier, Bane NOR og Fornebubanen. Reduserte byggetid med over 50%.', org: 'Novorender (Stavanger)', shape: 'rect', links: [{ label: 'Novorender', url: 'https://novorender.com/' }] },
            { id: 'Dimension10 VR', group: 'Prosjekt', desc: 'Norsk VR-plattform for byggebransjen. Prisbelonnet visualisering og samarbeid i VR. Brukt paa E39-prosjektet (AF Gruppen) og av Dark arkitekter.', org: 'Dimension10 / Catenda', shape: 'rect', links: [{ label: 'VR og BIM - bygg.no', url: 'https://www.bygg.no/bim-teknologi/norske-vr-og-bim-selskap-inngar-samarbeid/2365941' }] },
            { id: 'Nordisk BIM-samarbeid (infra)', group: 'Prosjekt', desc: 'Statens vegvesen, Nye Veier og Bane NOR + nordiske veg/jernbanemyndigheter. Felles maal om openBIM og IFC som krav i infrastrukturprosjekter.', org: 'SVV / Nye Veier / Bane NOR', shape: 'rect', links: [{ label: 'Nordisk BIM-samarbeid - Nye Veier', url: 'https://www.nyeveier.no/nyheter/nyheter/statens-vegvesen-nye-veier-og-bane-nor-inngar-nordisk-bim-samarbeid/' }] },
            { id: 'Fellestjenester BYGG (DiBK)', group: 'Prosjekt', desc: 'DiBKs ByggNett-strategi: Altinn-basert plattform for digitale byggesoknader. 17 tjenesteleverandorer, BIM som dokumentasjon. Automatisk regelsjekk.', org: 'DiBK / Digitaliseringsdirektoratet', shape: 'rect', links: [{ label: 'Fellestjenester BYGG - DiBK', url: 'https://www.dibk.no/verktoy-og-veivisere/andre-fagomrader/fellestjenester-bygg' }] },
            { id: 'Airthings inneklima-sensorer', group: 'Prosjekt', desc: 'Norsk IoT-selskap. 500 sensorer styrer 20 aar gammelt bygg. Maaler radon, CO2, fukt, temperatur, lys. Leder innen B2C og B2B inneklima.', org: 'Airthings', shape: 'rect', links: [{ label: '500 sensorer styrer bygg - Estate Vest', url: 'https://www.estatevest.no/500-airthings-sensorer-styrer-20-ar-gammelt-bygg/' }] },
            { id: 'Disruptive Technologies sensorer', group: 'Prosjekt', desc: 'Norsk selskap som lager verdens minste tradlose sensorer (frimerke-storrelse). Brukes i smarte bygg for temperatur, fukt, naervaer og vannlekkasje.', org: 'Disruptive Technologies', shape: 'rect', links: [{ label: 'PropTech Bergen - Digital Norway', url: 'https://digitalnorway.com/proptech-bergen/' }] },
            { id: 'Statsbygg + GS1 RFID-pilot', group: 'Prosjekt', desc: 'Statsbygg samarbeider med GS1, Virke, Byggevareindustrien og buildingSMART om RFID i byggebransjen. Sporing av byggevarer paa byggeplass.', org: 'Statsbygg / GS1 Norge', shape: 'rect', links: [{ label: 'RFID i byggebransjen - ITBaktuelt', url: 'https://www.itbaktuelt.no/2017/09/12/rfdi-teknologi-statsbygg-digitalisering-173/' }] },
            { id: 'Dalux Field', group: 'Prosjekt', desc: 'Digital byggeplass med haandholdt BIM, AR-teknologi og kvalitetskontroll. Konsernavtale med AF Gruppen. Maal: 35 000 norske bygningsarbeidere med AR.', org: 'Dalux', shape: 'rect', links: [{ label: 'AF + Dalux - bygg.no', url: 'https://www.bygg.no/af-gruppen-ikt/af-gruppen-utvider-konsernavtale-med-dalux/2190158' }] },
            { id: 'nLink borrerbot', group: 'Prosjekt', desc: 'Norsk selskap som utvikler mobile roboter for byggeplass. Neste generasjons programvare for modulaer robotprogrammering og -styring.', org: 'nLink', shape: 'rect', links: [{ label: 'nLink', url: 'https://nlink.no/' }] },
            { id: 'Moelven Byggmodul', group: 'Prosjekt', desc: 'Industrialisert modulbygging i tre. 60-70 moduler forlater fabrikken per uke. Skoler, barnehager, omsorgsboliger, hoteller, studentboliger.', org: 'Moelven', shape: 'rect', links: [{ label: 'Modulbygg - Moelven', url: 'https://www.moelven.com/no/produkter-og-tjenester/modulbygg/' }] },
            { id: 'Autility Twin (FDV)', group: 'Prosjekt', desc: 'Digital tvilling for drift og vedlikehold. Kobling til NOBB-databasen for automatisk oppdatering av produktdata. Optimaliserer eiendomsdrift.', org: 'Autility', shape: 'rect', links: [{ label: 'Digital tvilling FDV - norskbyggebransje.no', url: 'https://norskbyggebransje.no/nyheter/digital-tvilling-optimaliserer-drift-og-vedlikehold' }] },
            { id: 'Spot robot paa byggeplass', group: 'Prosjekt', desc: 'Boston Dynamics Spot-robot brukes paa norske byggeplasser for aa gaa rundt og oppdage feil. Autonom inspeksjon og fremdriftskontroll.', org: 'Diverse entreprenorer', shape: 'rect', links: [{ label: 'Roboter paa byggeplass - Tekna', url: 'https://www.tekna.no/fag-og-nettverk/bygg-og-anlegg/byggbloggen/roboter-pa-byggeplass/' }] },
            { id: 'Fornebubanen BIM', group: 'Prosjekt', desc: 'Et av Norges storste infrastrukturprosjekter bruker Novorender for en av verdens storste BIM-modeller. Norsk teknologi effektiviserer byggingen.', org: 'Fornebubanen / Oslo', shape: 'rect', links: [{ label: 'Fornebubanen + Novorender', url: 'https://novorender.com/hvordan-novorender-hjelper-radgiver-og-entreprenor-mote-bim-krav-i-store-norske-infrastrukturprosjekter' }] },
            { id: 'OsloMet digital tvilling energi', group: 'Prosjekt', desc: 'Forskning paa digital tvilling for energieffektive bygg. Bygg staar for 40% av energiforbruket i Norge - digital tvilling kan vaere losningen.', org: 'OsloMet', shape: 'rect', links: [{ label: 'Digital tvilling energi - OsloMet', url: 'https://www.oslomet.no/forskning/forskningsnyheter/hvordan-spare-mer-energi-i-bygg-digital-tvilling' }] },
            { id: 'Norconsult VR/AR-tjenester', group: 'Prosjekt', desc: 'Norges storste raadgivende ingeniorselskap tilbyr VR og AR-tjenester for prosjektering, visualisering og opplaering i byggeprosjekter.', org: 'Norconsult', shape: 'rect', links: [{ label: 'VR og AR - Norconsult', url: 'https://norconsult.no/tjenester/digitalisering/vrar/' }] },

            /* === Nye prosjekter (runde 3) === */
            { id: 'Imerso (3D-skanning + AI)', group: 'Prosjekt', desc: 'Norsk losning som kombinerer 3D-skanning og BIM for fremdriftskontroll. AI sammenligner punktsky med BIM-modell og oppdager avvik automatisk. Statsbygg sparer hundrevis av millioner.', org: 'Imerso', shape: 'rect', links: [{ label: 'Imerso - tu.no', url: 'https://www.tu.no/artikler/snart-blir-laserskannere-sa-billige-at-alle-byggeplasser-vil-skannes-24-7-br/458842' }] },
            { id: 'StreamBIM (Rendra)', group: 'Prosjekt', desc: 'Norsk skybasert BIM-viewer grunnlagt av NTNU-studenter. Streaming-teknologi gir mobiltilgang til store BIM-modeller. Brukes fra design til FDV.', org: 'Rendra / JDM Technology', shape: 'rect', links: [{ label: 'StreamBIM', url: 'https://streambim.com/' }] },
            { id: 'bSDD (Data Dictionary)', group: 'Prosjekt', desc: 'buildingSMARTs internasjonale dataordbok. Gratis tjeneste for klassifikasjoner, egenskaper og produktdata paa tvers av land og spraak. Basert paa ISO 12006-3.', org: 'buildingSMART International', shape: 'rect', links: [{ label: 'bSDD - buildingSMART', url: 'https://www.buildingsmart.org/users/services/buildingsmart-data-dictionary/' }] },
            { id: 'BREEAM-NOR (miljoesertifisering)', group: 'Prosjekt', desc: 'Norges ledende miljoesertifisering for bygg. Krever digital dokumentasjonsflyt og BIM. Ny BREEAM-NOR In-Use for eksisterende bygg lansert.', org: 'Gronn Byggallianse', shape: 'rect', links: [{ label: 'BREEAM-NOR - Byggalliansen', url: 'https://byggalliansen.no/sertifisering/om-breeam/' }] },
            { id: 'Statsbygg 3D-printet bygg', group: 'Prosjekt', desc: 'Statsbygg skal lage Norges forste 3D-printede bygg. Pilotprosjekt for industriell 3D-printing av betong i norsk skala.', org: 'Statsbygg', shape: 'rect', links: [{ label: 'Norges forste 3D-printede bygg - Statsbygg', url: 'https://www.statsbygg.no/nyheter/statsbygg-skal-lage-norges-forste-3d-printede-bygg' }] },
            { id: 'SIMBA modellsjekk (NTI/Statsbygg)', group: 'Prosjekt', desc: 'Automatisk skybasert kontroll av BIM-modeller mot Statsbyggs krav. Laster opp modell, faar BCF-rapport med feil og mangler automatisk.', org: 'NTI / Statsbygg', shape: 'rect', links: [{ label: 'SIMBA modellsjekk - bygg.no', url: 'https://www.bygg.no/kommer-med-automatisk-kontroll-av-statsbyggs-bim-krav/1446769!/' }] },
            { id: 'Digital byggesoeknad med BIM (DiBK)', group: 'Prosjekt', desc: 'BIM som dokumentasjon i byggesoknader. 3D-visualisering gir kommunen bedre grunnlag for visuell og automatisert kontroll.', org: 'DiBK', shape: 'rect', links: [{ label: 'BIM i byggesoknaden - DiBK', url: 'https://www.dibk.no/soknad-og-skjema/vil-du-bruke-bim-i-byggesoknaden' }] },
            { id: 'Oslobygg kommunal eiendom', group: 'Prosjekt', desc: 'Forvalter ca. 1800 kommunale bygg i Oslo. Digital FDV, kravspesifikasjoner og innovasjon. 7-10 mrd kr investering aarlig.', org: 'Oslo kommune', shape: 'rect', links: [{ label: 'Oslobygg - Oslo kommune', url: 'https://www.oslo.kommune.no/etater-foretak-og-ombud/oslobygg-kf/' }] },
            { id: 'MIL 3D-printing betong (Grimstad)', group: 'Prosjekt', desc: 'Norges forste robotiserte betong-3D-printer i Grimstad. Forskning paa on-site og off-site 3D-printing av betongkomponenter.', org: 'Mechatronics Innovation Lab', shape: 'rect', links: [{ label: '3D-printe betong - MIL', url: 'https://mil-as.no/index.php/2024/01/25/3d-printe-betong/' }] },
            { id: 'Digital Product Passport EU (CPR)', group: 'Prosjekt', desc: 'EUs nye byggevareforordning (CPR) krever digitale produktpass 2026-2029. Maskinlesbar produktinformasjon for alle byggevarer i Europa.', org: 'EU-kommisjonen', shape: 'rect', links: [{ label: 'DPP for construction - Cobuilder', url: 'https://cobuilder.com/en/digital-product-passport-dpp/digital-product-passports-for-construction-what-the-eus-2026-2029-cpr-working-plan-means-for-stakeholders/' }] },
            { id: 'Sweco 3D-skanning FDV', group: 'Prosjekt', desc: 'Sweco bruker 3D-skannere for effektiv drift og vedlikehold av bygningsmasser. Digitalisering av eksisterende bygg uten BIM.', org: 'Sweco', shape: 'rect', links: [{ label: '3D-scanner FDV - Sweco', url: 'https://www.sweco.no/aktuelt/nyheter/3d-scanner-for-effektiv-drift-og-vedlikehold-av-bygningsmasser/' }] },
        ],
        links: [
            /* POFIN */
            { source: 'POFIN', target: 'DV 4.1 Felles rammeverk for informasjonsforvaltning' },
            { source: 'POFIN', target: 'DV 4.3 Nasjonale rammer' },
            { source: 'POFIN', target: 'DV 4.7 Forvaltning av felles rammeverk' },

            /* PDT Norge */
            { source: 'PDT Norge', target: 'DV 4.4 Felles spesifikasjoner og komponenter' },
            { source: 'PDT Norge', target: 'DV 4.5 Etablere standarder for API' },

            /* IFC 4.3 */
            { source: 'IFC 4.3 ISO-standard', target: 'DV 4.2 Internasjonale rammer' },
            { source: 'IFC 4.3 ISO-standard', target: 'DV 4.4 Felles spesifikasjoner og komponenter' },
            { source: 'IFC 4.3 ISO-standard', target: 'DV 4.5 Etablere standarder for API' },

            /* Statsbygg BIM-manual */
            { source: 'Statsbygg BIM-manual 2.0', target: 'DV 4.1 Felles rammeverk for informasjonsforvaltning' },
            { source: 'Statsbygg BIM-manual 2.0', target: 'DV 4.3 Nasjonale rammer' },

            /* BIM for alle */
            { source: 'BIM for alle (Forsvarsbygg)', target: 'DV 4.6 Sluttbrukerlosninger i markedet' },
            { source: 'BIM for alle (Forsvarsbygg)', target: 'DV 4.1 Felles rammeverk for informasjonsforvaltning' },

            /* boligBIM */
            { source: 'boligBIM-prosjektet', target: 'DV 4.4 Felles spesifikasjoner og komponenter' },
            { source: 'boligBIM-prosjektet', target: 'DV 4.6 Sluttbrukerlosninger i markedet' },

            /* ISO 19650 */
            { source: 'ISO 19650-serien (norsk)', target: 'DV 4.1 Felles rammeverk for informasjonsforvaltning' },
            { source: 'ISO 19650-serien (norsk)', target: 'DV 4.2 Internasjonale rammer' },

            /* Digibygg */
            { source: 'Digibygg (Statsbygg)', target: 'DV 6.1 Kunstig intelligens / maskinlaering' },
            { source: 'Digibygg (Statsbygg)', target: 'DV 6.3 Sensorteknologi' },
            { source: 'Digibygg (Statsbygg)', target: 'DV 6.8 Droner' },
            { source: 'Digibygg (Statsbygg)', target: 'DV 4.6 Sluttbrukerlosninger i markedet' },

            /* Statsbygg droner */
            { source: 'Statsbygg droneinspeksjon', target: 'DV 6.8 Droner' },
            { source: 'Statsbygg droneinspeksjon', target: 'DV 6.1 Kunstig intelligens / maskinlaering' },

            /* SINTEF AI */
            { source: 'SINTEF AI i bygg', target: 'DV 6.1 Kunstig intelligens / maskinlaering' },
            { source: 'SINTEF AI i bygg', target: 'DV 6.2 Algoritmer' },

            /* Veidekke 3D */
            { source: 'Veidekke 3D-print betong', target: 'DV 6.7 3D-printing' },
            { source: 'Veidekke 3D-print betong', target: 'DV 6.6 Roboter' },

            /* Vegvesen */
            { source: 'Vegvesen bro-inspeksjon', target: 'DV 6.8 Droner' },
            { source: 'Vegvesen bro-inspeksjon', target: 'DV 6.1 Kunstig intelligens / maskinlaering' },
            { source: 'Vegvesen bro-inspeksjon', target: 'DV 6.2 Algoritmer' },

            /* DPP */
            { source: 'Digital Product Passport (DPP)', target: 'DV 4.4 Felles spesifikasjoner og komponenter' },
            { source: 'Digital Product Passport (DPP)', target: 'DV 4.2 Internasjonale rammer' },
            { source: 'Digital Product Passport (DPP)', target: 'DV 4.5 Etablere standarder for API' },

            /* Prosjekt Norge */
            { source: 'Prosjekt Norge digitaliseringsmaaling', target: 'DV 4.7 Forvaltning av felles rammeverk' },
            { source: 'Prosjekt Norge digitaliseringsmaaling', target: 'DV 4.1 Felles rammeverk for informasjonsforvaltning' },

            /* Nordic BIM */
            { source: 'Nordic BIM Digital Twin', target: 'DV 6.3 Sensorteknologi' },
            { source: 'Nordic BIM Digital Twin', target: 'DV 6.5 Virtual Reality (VR)' },

            /* VA-objekter */
            { source: 'VA-objekter (Vann og avlop)', target: 'DV 4.4 Felles spesifikasjoner og komponenter' },
            { source: 'VA-objekter (Vann og avlop)', target: 'DV 4.3 Nasjonale rammer' },

            /* === Nye koblinger (runde 2) === */

            /* Spacemaker / Forma */
            { source: 'Spacemaker / Autodesk Forma', target: 'DV 6.1 Kunstig intelligens / maskinlaering' },
            { source: 'Spacemaker / Autodesk Forma', target: 'DV 6.2 Algoritmer' },
            { source: 'Spacemaker / Autodesk Forma', target: 'DV 4.6 Sluttbrukerlosninger i markedet' },

            /* Catenda Hub */
            { source: 'Catenda Hub (BIMsync)', target: 'DV 4.1 Felles rammeverk for informasjonsforvaltning' },
            { source: 'Catenda Hub (BIMsync)', target: 'DV 4.5 Etablere standarder for API' },
            { source: 'Catenda Hub (BIMsync)', target: 'DV 4.6 Sluttbrukerlosninger i markedet' },

            /* Novorender */
            { source: 'Novorender', target: 'DV 4.6 Sluttbrukerlosninger i markedet' },
            { source: 'Novorender', target: 'DV 4.5 Etablere standarder for API' },
            { source: 'Novorender', target: 'DV 6.5 Virtual Reality (VR)' },

            /* Dimension10 VR */
            { source: 'Dimension10 VR', target: 'DV 6.5 Virtual Reality (VR)' },
            { source: 'Dimension10 VR', target: 'DV 4.6 Sluttbrukerlosninger i markedet' },

            /* Nordisk BIM infra */
            { source: 'Nordisk BIM-samarbeid (infra)', target: 'DV 4.2 Internasjonale rammer' },
            { source: 'Nordisk BIM-samarbeid (infra)', target: 'DV 4.3 Nasjonale rammer' },
            { source: 'Nordisk BIM-samarbeid (infra)', target: 'DV 4.1 Felles rammeverk for informasjonsforvaltning' },

            /* Fellestjenester BYGG */
            { source: 'Fellestjenester BYGG (DiBK)', target: 'DV 4.3 Nasjonale rammer' },
            { source: 'Fellestjenester BYGG (DiBK)', target: 'DV 4.5 Etablere standarder for API' },
            { source: 'Fellestjenester BYGG (DiBK)', target: 'DV 4.7 Forvaltning av felles rammeverk' },

            /* Airthings */
            { source: 'Airthings inneklima-sensorer', target: 'DV 6.3 Sensorteknologi' },
            { source: 'Airthings inneklima-sensorer', target: 'DV 6.1 Kunstig intelligens / maskinlaering' },

            /* Disruptive Technologies */
            { source: 'Disruptive Technologies sensorer', target: 'DV 6.3 Sensorteknologi' },
            { source: 'Disruptive Technologies sensorer', target: 'DV 6.4 RFiD' },

            /* Statsbygg RFID */
            { source: 'Statsbygg + GS1 RFID-pilot', target: 'DV 6.4 RFiD' },
            { source: 'Statsbygg + GS1 RFID-pilot', target: 'DV 4.4 Felles spesifikasjoner og komponenter' },

            /* Dalux */
            { source: 'Dalux Field', target: 'DV 6.5 Virtual Reality (VR)' },
            { source: 'Dalux Field', target: 'DV 4.6 Sluttbrukerlosninger i markedet' },

            /* nLink */
            { source: 'nLink borrerbot', target: 'DV 6.6 Roboter' },
            { source: 'nLink borrerbot', target: 'DV 6.2 Algoritmer' },

            /* Moelven */
            { source: 'Moelven Byggmodul', target: 'DV 6.6 Roboter' },
            { source: 'Moelven Byggmodul', target: 'DV 6.7 3D-printing' },

            /* Autility */
            { source: 'Autility Twin (FDV)', target: 'DV 6.3 Sensorteknologi' },
            { source: 'Autility Twin (FDV)', target: 'DV 4.4 Felles spesifikasjoner og komponenter' },
            { source: 'Autility Twin (FDV)', target: 'DV 4.1 Felles rammeverk for informasjonsforvaltning' },

            /* Spot robot */
            { source: 'Spot robot paa byggeplass', target: 'DV 6.6 Roboter' },
            { source: 'Spot robot paa byggeplass', target: 'DV 6.1 Kunstig intelligens / maskinlaering' },

            /* Fornebubanen */
            { source: 'Fornebubanen BIM', target: 'DV 4.6 Sluttbrukerlosninger i markedet' },
            { source: 'Fornebubanen BIM', target: 'DV 4.1 Felles rammeverk for informasjonsforvaltning' },

            /* OsloMet */
            { source: 'OsloMet digital tvilling energi', target: 'DV 6.3 Sensorteknologi' },
            { source: 'OsloMet digital tvilling energi', target: 'DV 6.1 Kunstig intelligens / maskinlaering' },

            /* Norconsult VR */
            { source: 'Norconsult VR/AR-tjenester', target: 'DV 6.5 Virtual Reality (VR)' },
            { source: 'Norconsult VR/AR-tjenester', target: 'DV 4.6 Sluttbrukerlosninger i markedet' },

            /* === Nye koblinger (runde 3) === */

            /* Imerso */
            { source: 'Imerso (3D-skanning + AI)', target: 'DV 6.1 Kunstig intelligens / maskinlaering' },
            { source: 'Imerso (3D-skanning + AI)', target: 'DV 6.2 Algoritmer' },
            { source: 'Imerso (3D-skanning + AI)', target: 'DV 6.3 Sensorteknologi' },
            { source: 'Imerso (3D-skanning + AI)', target: 'DV 4.6 Sluttbrukerlosninger i markedet' },

            /* StreamBIM */
            { source: 'StreamBIM (Rendra)', target: 'DV 4.6 Sluttbrukerlosninger i markedet' },
            { source: 'StreamBIM (Rendra)', target: 'DV 4.5 Etablere standarder for API' },
            { source: 'StreamBIM (Rendra)', target: 'DV 4.1 Felles rammeverk for informasjonsforvaltning' },

            /* bSDD */
            { source: 'bSDD (Data Dictionary)', target: 'DV 4.2 Internasjonale rammer' },
            { source: 'bSDD (Data Dictionary)', target: 'DV 4.4 Felles spesifikasjoner og komponenter' },
            { source: 'bSDD (Data Dictionary)', target: 'DV 4.5 Etablere standarder for API' },

            /* BREEAM-NOR */
            { source: 'BREEAM-NOR (miljoesertifisering)', target: 'DV 4.3 Nasjonale rammer' },
            { source: 'BREEAM-NOR (miljoesertifisering)', target: 'DV 4.4 Felles spesifikasjoner og komponenter' },

            /* Statsbygg 3D-print */
            { source: 'Statsbygg 3D-printet bygg', target: 'DV 6.7 3D-printing' },
            { source: 'Statsbygg 3D-printet bygg', target: 'DV 6.6 Roboter' },

            /* SIMBA */
            { source: 'SIMBA modellsjekk (NTI/Statsbygg)', target: 'DV 6.2 Algoritmer' },
            { source: 'SIMBA modellsjekk (NTI/Statsbygg)', target: 'DV 4.6 Sluttbrukerlosninger i markedet' },
            { source: 'SIMBA modellsjekk (NTI/Statsbygg)', target: 'DV 4.3 Nasjonale rammer' },

            /* Digital byggesoeknad */
            { source: 'Digital byggesoeknad med BIM (DiBK)', target: 'DV 4.3 Nasjonale rammer' },
            { source: 'Digital byggesoeknad med BIM (DiBK)', target: 'DV 4.5 Etablere standarder for API' },
            { source: 'Digital byggesoeknad med BIM (DiBK)', target: 'DV 6.2 Algoritmer' },

            /* Oslobygg */
            { source: 'Oslobygg kommunal eiendom', target: 'DV 4.1 Felles rammeverk for informasjonsforvaltning' },
            { source: 'Oslobygg kommunal eiendom', target: 'DV 6.3 Sensorteknologi' },
            { source: 'Oslobygg kommunal eiendom', target: 'DV 4.7 Forvaltning av felles rammeverk' },

            /* MIL 3D-print */
            { source: 'MIL 3D-printing betong (Grimstad)', target: 'DV 6.7 3D-printing' },
            { source: 'MIL 3D-printing betong (Grimstad)', target: 'DV 6.6 Roboter' },

            /* DPP EU */
            { source: 'Digital Product Passport EU (CPR)', target: 'DV 4.2 Internasjonale rammer' },
            { source: 'Digital Product Passport EU (CPR)', target: 'DV 4.4 Felles spesifikasjoner og komponenter' },

            /* Sweco */
            { source: 'Sweco 3D-skanning FDV', target: 'DV 6.3 Sensorteknologi' },
            { source: 'Sweco 3D-skanning FDV', target: 'DV 4.1 Felles rammeverk for informasjonsforvaltning' },
        ]
    };

    var COLORS = {
        DV4: '#8E44AD',
        DV6: '#2E86DE',
        Prosjekt: '#27AE60'
    };
    var GROUP_LABELS = {
        DV4: 'DV Kap. 4',
        DV6: 'DV Kap. 6',
        Prosjekt: 'Prosjekter'
    };

    /* -- Update stats -- */
    document.getElementById('vp-count-nodes').textContent = DATA.nodes.length;
    document.getElementById('vp-count-links').textContent = DATA.links.length;
    document.getElementById('vp-count-projects').textContent = DATA.nodes.filter(function(n) { return n.group === 'Prosjekt'; }).length;

    /* -- Connection counts -- */
    var connCount = {};
    DATA.nodes.forEach(function(n) { connCount[n.id] = 0; });
    DATA.links.forEach(function(l) {
        connCount[l.source] = (connCount[l.source] || 0) + 1;
        connCount[l.target] = (connCount[l.target] || 0) + 1;
    });
    DATA.nodes.forEach(function(n) { n.connCount = connCount[n.id] || 0; });

    /* -- Adjacency map -- */
    var neighbors = {};
    DATA.nodes.forEach(function(n) { neighbors[n.id] = new Set(); });
    DATA.links.forEach(function(l) {
        neighbors[l.source].add(l.target);
        neighbors[l.target].add(l.source);
    });

    /* -- Filter chips -- */
    var filterEl = document.getElementById('vp-filters');
    var activeGroups = new Set(Object.keys(COLORS));
    Object.keys(COLORS).forEach(function(grp) {
        var chip = document.createElement('label');
        chip.className = 'vp-chip vp-chip--active';
        chip.style.color = COLORS[grp];
        chip.style.background = COLORS[grp] + '15';
        chip.innerHTML = '<input type="checkbox" value="' + grp + '" checked><span class="chip-dot" style="background:' + COLORS[grp] + '"></span>' + GROUP_LABELS[grp];
        chip.querySelector('input').addEventListener('change', function(e) {
            if (e.target.checked) { activeGroups.add(grp); chip.className = 'vp-chip vp-chip--active'; chip.style.background = COLORS[grp] + '15'; }
            else { activeGroups.delete(grp); chip.className = 'vp-chip vp-chip--inactive'; chip.style.background = '#f0f0f0'; }
            updateVisibility();
        });
        filterEl.appendChild(chip);
    });

    /* -- D3 Setup -- */
    var vizEl = document.getElementById('vp-viz');
    var width = vizEl.clientWidth;
    var height = vizEl.clientHeight;
    var svg = d3.select('#vp-viz').append('svg').attr('width', width).attr('height', height);

    /* Gradient defs */
    var defs = svg.append('defs');
    DATA.links.forEach(function(l, i) {
        var sGroup = DATA.nodes.find(function(n) { return n.id === l.source; });
        var tGroup = DATA.nodes.find(function(n) { return n.id === l.target; });
        var grad = defs.append('linearGradient').attr('id', 'vlg-' + i).attr('gradientUnits', 'userSpaceOnUse');
        grad.append('stop').attr('offset', '0%').attr('stop-color', COLORS[sGroup ? sGroup.group : 'Prosjekt'] || '#999');
        grad.append('stop').attr('offset', '100%').attr('stop-color', COLORS[tGroup ? tGroup.group : 'DV4'] || '#999');
    });

    svg.call(d3.zoom().scaleExtent([0.2, 4]).on('zoom', function(e) { g.attr('transform', e.transform); }));

    var g = svg.append('g');

    var link = g.selectAll('.vp-link')
        .data(DATA.links).enter().append('line')
        .attr('class', 'vp-link')
        .attr('stroke', function(d, i) { return 'url(#vlg-' + i + ')'; })
        .attr('stroke-width', 1.5)
        .attr('stroke-opacity', 0.3);

    var node = g.selectAll('.vp-node')
        .data(DATA.nodes).enter().append('g')
        .attr('class', 'vp-node')
        .call(d3.drag().on('start', dragstarted).on('drag', dragged).on('end', dragended));

    function nodeRadius(d) {
        return d.group === 'Prosjekt' ? Math.max(6, 5 + d.connCount * 1.5) : Math.max(8, 6 + d.connCount * 2);
    }

    /* Draw different shapes */
    node.each(function(d) {
        var el = d3.select(this);
        if (d.shape === 'rect') {
            var size = Math.max(10, 8 + d.connCount * 2.5);
            el.append('rect')
                .attr('width', size)
                .attr('height', size)
                .attr('x', -size / 2)
                .attr('y', -size / 2)
                .attr('fill', COLORS[d.group] || '#999');
        } else {
            el.append('circle')
                .attr('r', nodeRadius(d))
                .attr('fill', COLORS[d.group] || '#999');
        }
    });

    node.append('text')
        .text(function(d) {
            /* Shorter labels for DV nodes */
            if (d.group !== 'Prosjekt') {
                return d.id.replace(/^DV \d\.\d /, '');
            }
            return d.id;
        })
        .attr('x', function(d) { return (d.shape === 'rect' ? Math.max(10, 8 + d.connCount * 2.5) / 2 + 4 : nodeRadius(d) + 4); })
        .attr('y', 4)
        .attr('font-size', function(d) { return d.group === 'Prosjekt' ? '10px' : '11px'; })
        .attr('font-weight', function(d) { return d.group === 'Prosjekt' ? '400' : '600'; });

    /* -- Tooltip -- */
    var tooltipEl = document.getElementById('vp-tooltip');
    node.on('mouseenter', function(event, d) {
        var label = d.id + ' (' + d.connCount + ' koblinger)';
        if (d.org) label = d.id + ' - ' + d.org + ' (' + d.connCount + ' koblinger)';
        tooltipEl.textContent = label;
        tooltipEl.classList.add('vp-tooltip--visible');
    }).on('mousemove', function(event) {
        var rect = vizEl.getBoundingClientRect();
        tooltipEl.style.left = (event.clientX - rect.left + 12) + 'px';
        tooltipEl.style.top = (event.clientY - rect.top - 30) + 'px';
    }).on('mouseleave', function() {
        tooltipEl.classList.remove('vp-tooltip--visible');
    });

    /* -- Click: Neighbor Highlighting -- */
    var selectedNode = null;
    var resetBtn = document.getElementById('vp-reset');

    node.on('click', function(event, d) {
        event.stopPropagation();
        selectedNode = d;
        highlightNeighbors(d);
        showDetails(d);
        resetBtn.classList.add('vp-reset--visible');
    });

    node.on('dblclick', function(event, d) {
        d.fx = (d.fx == null ? d.x : null);
        d.fy = (d.fy == null ? d.y : null);
    });

    svg.on('click', function() { clearHighlight(); });
    resetBtn.addEventListener('click', function() { clearHighlight(); });

    function highlightNeighbors(d) {
        var neighborSet = neighbors[d.id];
        node.classed('vp-node--dimmed', function(n) { return n.id !== d.id && !neighborSet.has(n.id); });
        node.classed('vp-node--highlighted', function(n) { return neighborSet.has(n.id); });
        node.classed('vp-node--selected', function(n) { return n.id === d.id; });
        link.classed('vp-link--dimmed', function(l) { return l.source.id !== d.id && l.target.id !== d.id; });
        link.classed('vp-link--highlighted', function(l) { return l.source.id === d.id || l.target.id === d.id; });
    }

    function clearHighlight() {
        selectedNode = null;
        node.classed('vp-node--dimmed', false).classed('vp-node--highlighted', false).classed('vp-node--selected', false);
        link.classed('vp-link--dimmed', false).classed('vp-link--highlighted', false);
        resetBtn.classList.remove('vp-reset--visible');
    }

    /* -- Simulation -- */
    var sim = d3.forceSimulation(DATA.nodes)
        .force('link', d3.forceLink(DATA.links).id(function(d) { return d.id; }).distance(120).strength(0.3))
        .force('charge', d3.forceManyBody().strength(-400))
        .force('center', d3.forceCenter(width / 2, height / 2))
        .force('collide', d3.forceCollide().radius(function(d) { return (d.shape === 'rect' ? 20 : nodeRadius(d)) + 12; }))
        /* Cluster DV4 left, DV6 right, Projects middle */
        .force('x', d3.forceX(function(d) {
            if (d.group === 'DV4') return width * 0.25;
            if (d.group === 'DV6') return width * 0.75;
            return width * 0.5;
        }).strength(0.08))
        .force('y', d3.forceY(height / 2).strength(0.05));

    sim.on('tick', function() {
        link.attr('x1', function(d) { return d.source.x; })
            .attr('y1', function(d) { return d.source.y; })
            .attr('x2', function(d) { return d.target.x; })
            .attr('y2', function(d) { return d.target.y; });
        node.attr('transform', function(d) { return 'translate(' + d.x + ',' + d.y + ')'; });

        DATA.links.forEach(function(l, i) {
            var grad = d3.select('#vlg-' + i);
            grad.attr('x1', l.source.x).attr('y1', l.source.y).attr('x2', l.target.x).attr('y2', l.target.y);
        });
    });

    function dragstarted(event, d) { if (!event.active) sim.alphaTarget(0.3).restart(); d.fx = d.x; d.fy = d.y; }
    function dragged(event, d) { d.fx = event.x; d.fy = event.y; }
    function dragended(event, d) { if (!event.active) sim.alphaTarget(0); }

    /* -- Filter -- */
    function updateVisibility() {
        node.classed('hidden', function(d) { return !activeGroups.has(d.group); });
        link.classed('hidden', function(d) {
            var sg = typeof d.source === 'object' ? d.source.group : DATA.nodes.find(function(n) { return n.id === d.source; }).group;
            var tg = typeof d.target === 'object' ? d.target.group : DATA.nodes.find(function(n) { return n.id === d.target; }).group;
            return !(activeGroups.has(sg) && activeGroups.has(tg));
        });
    }

    /* -- Search -- */
    document.getElementById('vp-search').addEventListener('input', function() {
        var q = this.value.toLowerCase();
        if (!q) { clearHighlight(); return; }
        node.classed('vp-node--dimmed', function(d) {
            var searchable = d.id.toLowerCase() + ' ' + (d.org || '').toLowerCase() + ' ' + (d.desc || '').toLowerCase();
            return searchable.indexOf(q) === -1;
        });
        node.classed('vp-node--highlighted', function(d) {
            var searchable = d.id.toLowerCase() + ' ' + (d.org || '').toLowerCase() + ' ' + (d.desc || '').toLowerCase();
            return searchable.indexOf(q) > -1;
        });
        link.classed('vp-link--dimmed', true);
    });

    /* -- Detail card -- */
    function showDetails(d) {
        var wrap = document.getElementById('vp-details');
        var neighborSet = neighbors[d.id];
        var neighborArr = Array.from(neighborSet);

        var linksHtml = (d.links || []).map(function(l) {
            return '<li><a href="' + l.url + '" target="_blank" rel="noopener">' + l.label + '</a></li>';
        }).join('');

        var neighborsHtml = neighborArr.map(function(nId) {
            var nNode = DATA.nodes.find(function(n) { return n.id === nId; });
            var label = nId.replace(/^DV \d\.\d /, '');
            if (nNode && nNode.org) label = nId + ' (' + nNode.org + ')';
            return '<span class="vp-neighbor" data-id="' + nId + '">' + label + '</span>';
        }).join('');

        var orgHtml = d.org ? '<p class="vp-card-org">' + d.org + '</p>' : '';

        wrap.innerHTML =
            '<div class="vp-card vp-card--highlight">' +
            '<span class="vp-card-group" style="background:' + (COLORS[d.group] || '#999') + '">' + (GROUP_LABELS[d.group] || d.group) + '</span>' +
            '<h3>' + d.id + '</h3>' +
            orgHtml +
            '<p class="vp-card-desc">' + (d.desc || '') + '</p>' +
            '<div class="vp-card-connections"><strong>' + d.connCount + '</strong> koblinger</div>' +
            (linksHtml ? '<p class="vp-card-links-label">Kilder</p><ul>' + linksHtml + '</ul>' : '') +
            (neighborsHtml ? '<div class="vp-card-neighbors"><p class="vp-card-links-label">Koblede noder</p>' + neighborsHtml + '</div>' : '') +
            '</div>';

        wrap.querySelectorAll('.vp-neighbor').forEach(function(el) {
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

    /* -- Resize -- */
    window.addEventListener('resize', function() {
        var w = vizEl.clientWidth, h = vizEl.clientHeight;
        svg.attr('width', w).attr('height', h);
        sim.force('center', d3.forceCenter(w / 2, h / 2));
        sim.force('x', d3.forceX(function(d) {
            if (d.group === 'DV4') return w * 0.25;
            if (d.group === 'DV6') return w * 0.75;
            return w * 0.5;
        }).strength(0.08));
        sim.alpha(0.3).restart();
    });
})();
</script>

<?php if (empty($GLOBALS['bimverdi_skip_header_footer'])) { get_footer(); } ?>
