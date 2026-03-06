<?php // Demo: Veikart-orbital ?>
<?php
/**
 * Demo: Veikart-orbital
 *
 * Orbital / planetary diagram for Digitalt Veikart 2.0.
 * Elements orbit around a center node on concentric rings.
 * Click any element to re-center and show its connections.
 *
 * Loaded via single-demo.php -> get_template_part('parts/demos/veikart-orbital')
 * Header is already loaded by the parent template.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<style>
/* --- Full-screen override --- */
#content { display: contents; }
body footer { display: none; }

/* --- Base --- */
:root {
    --vo-primary: #FF8B5E;
    --vo-bg: #FAFAF9;
    --vo-white: #FFFFFF;
    --vo-text: #1A1A1A;
    --vo-text-sec: #5A5A5A;
    --vo-border: #E7E5E4;
    --vo-dv4: #8E44AD;
    --vo-dv6: #2E86DE;
    --vo-project: #27AE60;
    --vo-node-shadow: rgba(0,0,0,0.08);
}

*, *::before, *::after { box-sizing: border-box; }

.vo-page {
    background: var(--vo-bg);
    min-height: 100vh;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: var(--vo-text);
    position: relative;
    overflow: hidden;
}

/* --- Header --- */
.vo-header {
    position: relative;
    z-index: 10;
    max-width: 1280px;
    margin: 0 auto;
    padding: 24px 24px 0;
}

.vo-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--vo-text-sec);
    text-decoration: none;
    font-size: 14px;
    margin-bottom: 16px;
    transition: color 0.15s;
}

.vo-back:hover { color: var(--vo-text); }
.vo-back svg { width: 16px; height: 16px; }

.vo-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 4px;
    color: var(--vo-text);
    line-height: 1.2;
}

.vo-subtitle {
    font-size: 15px;
    color: var(--vo-text-sec);
    margin: 0 0 16px;
}

/* --- Stats Bar --- */
.vo-stats {
    display: flex;
    gap: 28px;
    padding: 12px 0;
    border-top: 1px solid var(--vo-border);
    border-bottom: 1px solid var(--vo-border);
    flex-wrap: wrap;
}

.vo-stat {
    display: flex;
    align-items: baseline;
    gap: 5px;
}

.vo-stat-num {
    font-size: 20px;
    font-weight: 700;
    color: var(--vo-text);
}

.vo-stat-label {
    font-size: 13px;
    color: var(--vo-text-sec);
}

/* --- SVG Container --- */
.vo-viz {
    width: 100%;
    height: calc(100vh - 180px);
    min-height: 500px;
    position: relative;
}

.vo-viz svg {
    width: 100%;
    height: 100%;
    display: block;
}

/* --- Info Panel --- */
.vo-info {
    position: fixed;
    bottom: 24px;
    left: 24px;
    width: 360px;
    max-width: calc(100vw - 48px);
    background: var(--vo-white);
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.10), 0 1px 4px rgba(0,0,0,0.06);
    padding: 20px;
    z-index: 20;
    opacity: 0;
    transform: translateY(12px);
    transition: opacity 0.25s ease, transform 0.25s ease;
    pointer-events: none;
}

.vo-info.visible {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}

.vo-info-name {
    font-size: 17px;
    font-weight: 700;
    color: var(--vo-text);
    margin: 0 0 6px;
    line-height: 1.3;
}

.vo-info-badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 10px;
    border-radius: 100px;
    color: #fff;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.vo-info-badge.dv4 { background: var(--vo-dv4); }
.vo-info-badge.dv6 { background: var(--vo-dv6); }
.vo-info-badge.project { background: var(--vo-project); }
.vo-info-badge.center { background: var(--vo-primary); }

.vo-info-desc {
    font-size: 14px;
    color: var(--vo-text-sec);
    margin: 0 0 12px;
    line-height: 1.5;
}

.vo-info-meta {
    font-size: 12px;
    color: var(--vo-text-sec);
    margin: 0 0 12px;
}

.vo-info-meta strong { color: var(--vo-text); }

.vo-info-links-title {
    font-size: 12px;
    font-weight: 600;
    color: var(--vo-text-sec);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 0 8px;
}

.vo-info-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.vo-info-chip {
    font-size: 12px;
    padding: 4px 10px;
    border-radius: 100px;
    border: 1px solid var(--vo-border);
    color: var(--vo-text-sec);
    cursor: pointer;
    background: var(--vo-bg);
    transition: all 0.15s;
    text-decoration: none;
}

.vo-info-chip:hover {
    border-color: var(--vo-primary);
    color: var(--vo-primary);
    background: #FFF5F0;
}

.vo-info-source {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: var(--vo-primary);
    text-decoration: none;
    margin-top: 8px;
    transition: opacity 0.15s;
}

.vo-info-source:hover { opacity: 0.7; }
.vo-info-source svg { width: 12px; height: 12px; }

/* --- Breadcrumb --- */
.vo-breadcrumb {
    position: fixed;
    top: 100px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 6px;
    background: var(--vo-white);
    border-radius: 100px;
    padding: 6px 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    z-index: 20;
    font-size: 13px;
    color: var(--vo-text-sec);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.vo-breadcrumb.visible {
    opacity: 1;
    pointer-events: auto;
}

.vo-breadcrumb-item {
    cursor: pointer;
    color: var(--vo-primary);
    transition: opacity 0.15s;
}

.vo-breadcrumb-item:hover { opacity: 0.7; }

.vo-breadcrumb-item.current {
    color: var(--vo-text);
    font-weight: 600;
    cursor: default;
}

.vo-breadcrumb-sep {
    color: #ccc;
    font-size: 11px;
}

/* --- Back Button (in viz) --- */
.vo-back-btn {
    position: fixed;
    top: 100px;
    right: 24px;
    display: flex;
    align-items: center;
    gap: 6px;
    background: var(--vo-white);
    border: 1px solid var(--vo-border);
    border-radius: 100px;
    padding: 8px 16px;
    font-size: 13px;
    color: var(--vo-text-sec);
    cursor: pointer;
    z-index: 20;
    opacity: 0;
    transition: all 0.2s ease;
    pointer-events: none;
}

.vo-back-btn.visible {
    opacity: 1;
    pointer-events: auto;
}

.vo-back-btn:hover {
    border-color: var(--vo-primary);
    color: var(--vo-primary);
}

.vo-back-btn svg { width: 14px; height: 14px; }

/* --- Legend --- */
.vo-legend {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: var(--vo-white);
    border-radius: 12px;
    padding: 14px 18px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    z-index: 20;
    display: flex;
    flex-direction: column;
    gap: 8px;
    font-size: 12px;
    color: var(--vo-text-sec);
}

.vo-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.vo-legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* --- Pulse animation for center --- */
@keyframes vo-pulse {
    0%, 100% { opacity: 0.18; transform: scale(1); }
    50% { opacity: 0.06; transform: scale(1.15); }
}

@keyframes vo-pulse-ring {
    0%, 100% { r: var(--pulse-r); opacity: 0.2; }
    50% { r: calc(var(--pulse-r) + 6px); opacity: 0.06; }
}

/* --- Mobile --- */
@media (max-width: 768px) {
    .vo-header { padding: 16px 16px 0; }
    .vo-title { font-size: 22px; }
    .vo-stats { gap: 16px; }
    .vo-info {
        bottom: 12px;
        left: 12px;
        width: calc(100vw - 24px);
        padding: 16px;
    }
    .vo-breadcrumb {
        top: 80px;
        font-size: 11px;
        padding: 4px 12px;
        max-width: calc(100vw - 32px);
        overflow-x: auto;
    }
    .vo-back-btn {
        top: 80px;
        right: 12px;
    }
    .vo-legend { display: none; }
}
</style>

<div class="vo-page">
    <div class="vo-header">
        <a href="/bimverdi-v2/demo/" class="vo-back">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Alle demoer
        </a>
        <h1 class="vo-title">Veikart-orbital</h1>
        <p class="vo-subtitle">Utforsk Digitalt Veikart 2.0 som et planetarisk system. Klikk paa en node for aa se dens koblinger.</p>
        <div class="vo-stats">
            <div class="vo-stat"><span class="vo-stat-num">59</span><span class="vo-stat-label">noder</span></div>
            <div class="vo-stat"><span class="vo-stat-num">107</span><span class="vo-stat-label">koblinger</span></div>
            <div class="vo-stat"><span class="vo-stat-num">44</span><span class="vo-stat-label">prosjekter</span></div>
        </div>
    </div>

    <div class="vo-viz" id="vo-viz"></div>

    <div class="vo-info" id="vo-info">
        <div class="vo-info-name" id="vo-info-name"></div>
        <div id="vo-info-badge-wrap"></div>
        <div class="vo-info-desc" id="vo-info-desc"></div>
        <div class="vo-info-meta" id="vo-info-meta"></div>
        <div id="vo-info-connections">
            <div class="vo-info-links-title">Koblinger</div>
            <div class="vo-info-chips" id="vo-info-chips"></div>
        </div>
        <div id="vo-info-source-wrap"></div>
    </div>

    <div class="vo-breadcrumb" id="vo-breadcrumb"></div>

    <button class="vo-back-btn" id="vo-back-btn">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Tilbake
    </button>

    <div class="vo-legend">
        <div class="vo-legend-item"><div class="vo-legend-dot" style="background:#8E44AD"></div> Kap. 4 &mdash; Rammeverk</div>
        <div class="vo-legend-item"><div class="vo-legend-dot" style="background:#2E86DE"></div> Kap. 6 &mdash; Teknologi</div>
        <div class="vo-legend-item"><div class="vo-legend-dot" style="background:#27AE60"></div> Prosjekter</div>
        <div class="vo-legend-item"><div class="vo-legend-dot" style="background:#FF8B5E"></div> Digitalt Veikart</div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.9.0/d3.min.js"></script>
<script>
requestAnimationFrame(function() {
(function() {
'use strict';

/* =========================================================================
   DATA
   ========================================================================= */

const dvHeadings = [
    { id: 'DV 4.1', full: 'DV 4.1 Felles rammeverk for informasjonsforvaltning', group: 'dv4', abbr: '4.1' },
    { id: 'DV 4.2', full: 'DV 4.2 Internasjonale rammer', group: 'dv4', abbr: '4.2' },
    { id: 'DV 4.3', full: 'DV 4.3 Nasjonale rammer', group: 'dv4', abbr: '4.3' },
    { id: 'DV 4.4', full: 'DV 4.4 Felles spesifikasjoner og komponenter', group: 'dv4', abbr: '4.4' },
    { id: 'DV 4.5', full: 'DV 4.5 Etablere standarder for API', group: 'dv4', abbr: '4.5' },
    { id: 'DV 4.6', full: 'DV 4.6 Sluttbrukerlosninger i markedet', group: 'dv4', abbr: '4.6' },
    { id: 'DV 4.7', full: 'DV 4.7 Forvaltning av felles rammeverk', group: 'dv4', abbr: '4.7' },
    { id: 'DV 6.1', full: 'DV 6.1 Kunstig intelligens / maskinlaering', group: 'dv6', abbr: '6.1' },
    { id: 'DV 6.2', full: 'DV 6.2 Algoritmer', group: 'dv6', abbr: '6.2' },
    { id: 'DV 6.3', full: 'DV 6.3 Sensorteknologi', group: 'dv6', abbr: '6.3' },
    { id: 'DV 6.4', full: 'DV 6.4 RFiD', group: 'dv6', abbr: '6.4' },
    { id: 'DV 6.5', full: 'DV 6.5 Virtual Reality (VR)', group: 'dv6', abbr: '6.5' },
    { id: 'DV 6.6', full: 'DV 6.6 Roboter', group: 'dv6', abbr: '6.6' },
    { id: 'DV 6.7', full: 'DV 6.7 3D-printing', group: 'dv6', abbr: '6.7' },
    { id: 'DV 6.8', full: 'DV 6.8 Droner', group: 'dv6', abbr: '6.8' }
];

const projects = [
    { id: 'POFIN', org: 'buildingSMART Norge', desc: 'Rammeverk for openBIM i byggeprosjekter og forvaltning.', links: [{ label: 'buildingSMART', url: 'https://buildingsmart.no/pofin' }] },
    { id: 'PDT Norge', org: 'Cobuilder / BNL', desc: 'Distribusjonsplattform for produktdatamaler.', links: [{ label: 'Cobuilder', url: 'https://cobuilder.com/nb/pdt-norge-en-offisiell-distribusjonsplattform-for-produktdatamaler/' }] },
    { id: 'IFC 4.3 ISO-standard', org: 'buildingSMART Intl', desc: 'IFC 4.3 vedtatt som ISO 16739-1:2024.', links: [{ label: 'buildingSMART', url: 'https://buildingsmart.no/nyheter/ifc-iso' }] },
    { id: 'Statsbygg BIM-manual 2.0', org: 'Statsbygg', desc: 'Krav til BIM i alle Statsbygg-prosjekter.', links: [{ label: 'buildingSMART', url: 'https://arkiv.buildingsmart.no/nyhetsbrev/2019-11/statsbyggs-bim-manual-20-sbm2-et-konkret-eksempel-pa-hvordan-apne-standarder-kan' }] },
    { id: 'BIM for alle', org: 'Forsvarsbygg', desc: 'Digitalisering av 4 mill. kvm paa openBIM.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/bim/sintef-og-buildingsmart-norge-inngar-samarbeidsavtale/1747497' }] },
    { id: 'boligBIM', org: 'Boligprodusentene / bSN', desc: 'Standardisering av BIM-praksis for boligbygging.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/bolig-boligbygging/full-fart-i-boligbim-prosjektet/2069485' }] },
    { id: 'ISO 19650 (norsk)', org: 'Standard Norge', desc: 'NS-EN ISO 19650 oversatt til norsk.', links: [{ label: 'Standard Norge', url: 'https://standard.no/fagomrader/bygg-anlegg-og-eiendom/digital-byggeprosess/iso-19650-serien/' }] },
    { id: 'Digibygg', org: 'Statsbygg', desc: 'Digital tvilling, droner, sensorteknologi.', links: [{ label: 'Statsbygg', url: 'https://www.statsbygg.no/nyheter/digitale-tvillinger-i-2023' }] },
    { id: 'Droneinspeksjon', org: 'Statsbygg', desc: 'Inspeksjon med droner og AI.', links: [{ label: 'Byggfakta', url: 'https://nyheter.byggfakta.no/statsbygg-inngar-avtaler-om-droneinspeksjon-175538/nyhet.html' }] },
    { id: 'SINTEF AI', org: 'SINTEF', desc: 'Forskning paa AI i byggenaeringen.', links: [{ label: 'SINTEF', url: 'https://www.sintef.no/en/sintef-research-areas/artificial-intelligence/great-effect-using-ai-in-the-construction-industry/' }] },
    { id: 'Veidekke 3D-print', org: 'Veidekke', desc: 'Tester 3D-printing av betong.', links: [{ label: 'Digital Norway', url: 'https://digitalnorway.com/aktuelt/dette-er-tech-trendene-som-vil-prege-2026' }] },
    { id: 'Bro-inspeksjon', org: 'Statens vegvesen', desc: 'Dronebilder og AI for bro-inspeksjon.', links: [{ label: 'norskbyggebransje.no', url: 'https://norskbyggebransje.no/ai/bygg-og-anlegg-i-2026' }] },
    { id: 'DPP', org: 'Cobuilder / EU', desc: 'Digitale produktpass 2026-2029.', links: [{ label: 'Cobuilder', url: 'https://cobuilder.com/en/digital-product-passport-dpp/digital-product-passports-for-construction-what-the-eus-2026-2029-cpr-working-plan-means-for-stakeholders/' }] },
    { id: 'Digitaliseringsmaaling', org: 'Prosjekt Norge / NTNU', desc: 'Maaling av effekter av digitalisering.', links: [{ label: 'Prosjekt Norge', url: 'https://www.prosjektnorge.no/maling-av-effekter-av-digitalisering-i-den-norske-byggenaeringen/' }] },
    { id: 'Nordic BIM Twin', org: 'Nordic BIM Group', desc: 'Digitale tvillinger for bygg.', links: [{ label: 'Nordic BIM', url: 'https://www.nordicbim.com/no/digital-tvilling' }] },
    { id: 'VA-objekter', org: 'buildingSMART Norge', desc: 'Felles retningslinjer for VA med BIM.', links: [{ label: 'buildingSMART', url: 'https://buildingsmart.no/' }] },
    { id: 'Spacemaker', org: 'Autodesk', desc: 'AI-plattform for arealplanlegging.', links: [{ label: 'NTI', url: 'https://www.nti-group.com/no/produkter/autodesk-software/forma/' }] },
    { id: 'Catenda Hub', org: 'Catenda', desc: 'openBIM-plattform for samhandling.', links: [{ label: 'Catenda', url: 'https://catenda.com/' }] },
    { id: 'Novorender', org: 'Novorender', desc: '3D/BIM-viewer for >100 GB.', links: [{ label: 'Novorender', url: 'https://novorender.com/' }] },
    { id: 'Dimension10 VR', org: 'Dimension10 / Catenda', desc: 'VR-plattform for byggebransjen.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/bim-teknologi/norske-vr-og-bim-selskap-inngar-samarbeid/2365941' }] },
    { id: 'Nordisk BIM infra', org: 'SVV / Nye Veier / Bane NOR', desc: 'Nordisk openBIM-samarbeid.', links: [{ label: 'Nye Veier', url: 'https://www.nyeveier.no/nyheter/nyheter/statens-vegvesen-nye-veier-og-bane-nor-inngar-nordisk-bim-samarbeid/' }] },
    { id: 'Fellestjenester BYGG', org: 'DiBK', desc: 'Digitale byggesoknader.', links: [{ label: 'DiBK', url: 'https://www.dibk.no/verktoy-og-veivisere/andre-fagomrader/fellestjenester-bygg' }] },
    { id: 'Airthings', org: 'Airthings', desc: '500 sensorer styrer gammelt bygg.', links: [{ label: 'Estate Vest', url: 'https://www.estatevest.no/500-airthings-sensorer-styrer-20-ar-gammelt-bygg/' }] },
    { id: 'Disruptive Tech', org: 'Disruptive Technologies', desc: 'Verdens minste sensorer.', links: [{ label: 'Digital Norway', url: 'https://digitalnorway.com/proptech-bergen/' }] },
    { id: 'RFID-pilot', org: 'Statsbygg / GS1', desc: 'RFID-sporing av byggevarer.', links: [{ label: 'ITBaktuelt', url: 'https://www.itbaktuelt.no/2017/09/12/rfdi-teknologi-statsbygg-digitalisering-173/' }] },
    { id: 'Dalux Field', org: 'Dalux', desc: 'Digital byggeplass med AR.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/af-gruppen-ikt/af-gruppen-utvider-konsernavtale-med-dalux/2190158' }] },
    { id: 'nLink', org: 'nLink', desc: 'Mobile roboter for byggeplass.', links: [{ label: 'nLink', url: 'https://nlink.no/' }] },
    { id: 'Moelven Byggmodul', org: 'Moelven', desc: 'Industrialisert modulbygging.', links: [{ label: 'Moelven', url: 'https://www.moelven.com/no/produkter-og-tjenester/modulbygg/' }] },
    { id: 'Autility Twin', org: 'Autility', desc: 'Digital tvilling for FDV.', links: [{ label: 'norskbyggebransje.no', url: 'https://norskbyggebransje.no/nyheter/digital-tvilling-optimaliserer-drift-og-vedlikehold' }] },
    { id: 'Spot robot', org: 'Diverse', desc: 'Boston Dynamics Spot paa byggeplass.', links: [{ label: 'Tekna', url: 'https://www.tekna.no/fag-og-nettverk/bygg-og-anlegg/byggbloggen/roboter-pa-byggeplass/' }] },
    { id: 'Fornebubanen', org: 'Oslo', desc: 'Verdens storste BIM-modell.', links: [{ label: 'Novorender', url: 'https://novorender.com/hvordan-novorender-hjelper-radgiver-og-entreprenor-mote-bim-krav-i-store-norske-infrastrukturprosjekter' }] },
    { id: 'OsloMet Tvilling', org: 'OsloMet', desc: 'Digital tvilling for energi.', links: [{ label: 'OsloMet', url: 'https://www.oslomet.no/forskning/forskningsnyheter/hvordan-spare-mer-energi-i-bygg-digital-tvilling' }] },
    { id: 'Norconsult VR', org: 'Norconsult', desc: 'VR og AR for prosjektering.', links: [{ label: 'Norconsult', url: 'https://norconsult.no/tjenester/digitalisering/vrar/' }] },
    { id: 'Imerso', org: 'Imerso', desc: '3D-skanning + AI for kontroll.', links: [{ label: 'tu.no', url: 'https://www.tu.no/artikler/snart-blir-laserskannere-sa-billige-at-alle-byggeplasser-vil-skannes-24-7-br/458842' }] },
    { id: 'StreamBIM', org: 'Rendra', desc: 'Skybasert BIM-viewer.', links: [{ label: 'StreamBIM', url: 'https://streambim.com/' }] },
    { id: 'bSDD', org: 'buildingSMART Intl', desc: 'Internasjonal dataordbok.', links: [{ label: 'buildingSMART', url: 'https://www.buildingsmart.org/users/services/buildingsmart-data-dictionary/' }] },
    { id: 'BREEAM-NOR', org: 'Gronn Byggallianse', desc: 'Miljoesertifisering for bygg.', links: [{ label: 'Byggalliansen', url: 'https://byggalliansen.no/sertifisering/om-breeam/' }] },
    { id: 'Statsbygg 3D-print', org: 'Statsbygg', desc: 'Norges forste 3D-printede bygg.', links: [{ label: 'Statsbygg', url: 'https://www.statsbygg.no/nyheter/statsbygg-skal-lage-norges-forste-3d-printede-bygg' }] },
    { id: 'SIMBA', org: 'NTI / Statsbygg', desc: 'Automatisk BIM-modellsjekk.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/kommer-med-automatisk-kontroll-av-statsbyggs-bim-krav/1446769!/' }] },
    { id: 'Digital byggesoeknad', org: 'DiBK', desc: 'BIM i byggesoknader.', links: [{ label: 'DiBK', url: 'https://www.dibk.no/soknad-og-skjema/vil-du-bruke-bim-i-byggesoknaden' }] },
    { id: 'Oslobygg', org: 'Oslo kommune', desc: '1800 kommunale bygg.', links: [{ label: 'Oslo kommune', url: 'https://www.oslo.kommune.no/etater-foretak-og-ombud/oslobygg-kf/' }] },
    { id: 'MIL 3D-print', org: 'MIL Grimstad', desc: 'Robotisert betong-3D-printer.', links: [{ label: 'MIL', url: 'https://mil-as.no/index.php/2024/01/25/3d-printe-betong/' }] },
    { id: 'DPP EU', org: 'EU-kommisjonen', desc: 'EU byggevareforordning DPP.', links: [{ label: 'Cobuilder', url: 'https://cobuilder.com/en/digital-product-passport-dpp/digital-product-passports-for-construction-what-the-eus-2026-2029-cpr-working-plan-means-for-stakeholders/' }] },
    { id: 'Sweco 3D-skanning', org: 'Sweco', desc: '3D-skannere for FDV.', links: [{ label: 'Sweco', url: 'https://www.sweco.no/aktuelt/nyheter/3d-scanner-for-effektiv-drift-og-vedlikehold-av-bygningsmasser/' }] }
];

const connectionMap = {
    'POFIN': ['DV 4.1', 'DV 4.3', 'DV 4.7'],
    'PDT Norge': ['DV 4.4', 'DV 4.5'],
    'IFC 4.3 ISO-standard': ['DV 4.2', 'DV 4.4', 'DV 4.5'],
    'Statsbygg BIM-manual 2.0': ['DV 4.1', 'DV 4.3'],
    'BIM for alle': ['DV 4.6', 'DV 4.1'],
    'boligBIM': ['DV 4.4', 'DV 4.6'],
    'ISO 19650 (norsk)': ['DV 4.1', 'DV 4.2'],
    'Digibygg': ['DV 6.1', 'DV 6.3', 'DV 6.8', 'DV 4.6'],
    'Droneinspeksjon': ['DV 6.8', 'DV 6.1'],
    'SINTEF AI': ['DV 6.1', 'DV 6.2'],
    'Veidekke 3D-print': ['DV 6.7', 'DV 6.6'],
    'Bro-inspeksjon': ['DV 6.8', 'DV 6.1', 'DV 6.2'],
    'DPP': ['DV 4.4', 'DV 4.2', 'DV 4.5'],
    'Digitaliseringsmaaling': ['DV 4.7', 'DV 4.1'],
    'Nordic BIM Twin': ['DV 6.3', 'DV 6.5'],
    'VA-objekter': ['DV 4.4', 'DV 4.3'],
    'Spacemaker': ['DV 6.1', 'DV 6.2', 'DV 4.6'],
    'Catenda Hub': ['DV 4.1', 'DV 4.5', 'DV 4.6'],
    'Novorender': ['DV 4.6', 'DV 4.5', 'DV 6.5'],
    'Dimension10 VR': ['DV 6.5', 'DV 4.6'],
    'Nordisk BIM infra': ['DV 4.2', 'DV 4.3', 'DV 4.1'],
    'Fellestjenester BYGG': ['DV 4.3', 'DV 4.5', 'DV 4.7'],
    'Airthings': ['DV 6.3', 'DV 6.1'],
    'Disruptive Tech': ['DV 6.3', 'DV 6.4'],
    'RFID-pilot': ['DV 6.4', 'DV 4.4'],
    'Dalux Field': ['DV 6.5', 'DV 4.6'],
    'nLink': ['DV 6.6', 'DV 6.2'],
    'Moelven Byggmodul': ['DV 6.6', 'DV 6.7'],
    'Autility Twin': ['DV 6.3', 'DV 4.4', 'DV 4.1'],
    'Spot robot': ['DV 6.6', 'DV 6.1'],
    'Fornebubanen': ['DV 4.6', 'DV 4.1'],
    'OsloMet Tvilling': ['DV 6.3', 'DV 6.1'],
    'Norconsult VR': ['DV 6.5', 'DV 4.6'],
    'Imerso': ['DV 6.1', 'DV 6.2', 'DV 6.3', 'DV 4.6'],
    'StreamBIM': ['DV 4.6', 'DV 4.5', 'DV 4.1'],
    'bSDD': ['DV 4.2', 'DV 4.4', 'DV 4.5'],
    'BREEAM-NOR': ['DV 4.3', 'DV 4.4'],
    'Statsbygg 3D-print': ['DV 6.7', 'DV 6.6'],
    'SIMBA': ['DV 6.2', 'DV 4.6', 'DV 4.3'],
    'Digital byggesoeknad': ['DV 4.3', 'DV 4.5', 'DV 6.2'],
    'Oslobygg': ['DV 4.1', 'DV 6.3', 'DV 4.7'],
    'MIL 3D-print': ['DV 6.7', 'DV 6.6'],
    'DPP EU': ['DV 4.2', 'DV 4.4'],
    'Sweco 3D-skanning': ['DV 6.3', 'DV 4.1']
};

// Build node lookup
const nodeMap = {};
const ROOT_ID = 'Digitalt Veikart 2.0';

nodeMap[ROOT_ID] = {
    id: ROOT_ID, full: 'Digitalt Veikart 2.0', group: 'center', abbr: 'DV',
    desc: 'Digitalt Veikart 2.0 for den norske byggenaeringen. Rammeverk, standarder og teknologitrender.',
    org: 'BIM Verdi / buildingSMART Norge', links: []
};

dvHeadings.forEach(h => { nodeMap[h.id] = { ...h, desc: '', org: '', links: [] }; });
projects.forEach(p => { nodeMap[p.id] = { ...p, full: p.id, group: 'project', abbr: p.id.substring(0, 2).toUpperCase() }; });

// Adjacency
const adjacency = {};
function addEdge(a, b) {
    if (!adjacency[a]) adjacency[a] = new Set();
    if (!adjacency[b]) adjacency[b] = new Set();
    adjacency[a].add(b);
    adjacency[b].add(a);
}

dvHeadings.forEach(h => addEdge(ROOT_ID, h.id));
Object.entries(connectionMap).forEach(([projId, dvIds]) => {
    dvIds.forEach(dvId => addEdge(projId, dvId));
});

function getConnections(nodeId) { return adjacency[nodeId] ? Array.from(adjacency[nodeId]) : []; }
function getConnectionCount(nodeId) { return getConnections(nodeId).length; }


/* =========================================================================
   COLORS
   ========================================================================= */

const colors = {
    primary: '#FF8B5E',
    bg: '#FAFAF9',
    text: '#1A1A1A',
    textSec: '#5A5A5A',
    border: '#E7E5E4',
    dv4: '#8E44AD',
    dv6: '#2E86DE',
    project: '#27AE60',
    center: '#FF8B5E',
    nodeBg: '#FFFFFF',
};

function nodeColor(group) {
    if (group === 'dv4') return colors.dv4;
    if (group === 'dv6') return colors.dv6;
    if (group === 'project') return colors.project;
    return colors.center;
}

function nodeColorLight(group) {
    if (group === 'dv4') return '#F5EEF8';
    if (group === 'dv6') return '#EBF5FB';
    if (group === 'project') return '#EAFAE4';
    return '#FFF5F0';
}

function nodeColorMid(group) {
    if (group === 'dv4') return '#D2B4DE';
    if (group === 'dv6') return '#AED6F1';
    if (group === 'project') return '#A9DFBF';
    return '#FADBD8';
}


/* =========================================================================
   SVG SETUP
   ========================================================================= */

const container = document.getElementById('vo-viz');
const width = container.clientWidth;
const height = container.clientHeight;
const cx = width / 2;
const cy = height / 2;
const maxRadius = Math.min(cx, cy) - 40;

const svg = d3.select('#vo-viz')
    .append('svg')
    .attr('width', width)
    .attr('height', height)
    .attr('viewBox', `0 0 ${width} ${height}`);

// --- DEFS: filters and gradients ---
const defs = svg.append('defs');

// Drop shadow
const filter = defs.append('filter')
    .attr('id', 'vo-shadow')
    .attr('x', '-50%').attr('y', '-50%')
    .attr('width', '200%').attr('height', '200%');
filter.append('feDropShadow')
    .attr('dx', 0).attr('dy', 2)
    .attr('stdDeviation', 4)
    .attr('flood-color', 'rgba(0,0,0,0.08)');

// Center shadow (bigger)
const filterCenter = defs.append('filter')
    .attr('id', 'vo-shadow-center')
    .attr('x', '-50%').attr('y', '-50%')
    .attr('width', '200%').attr('height', '200%');
filterCenter.append('feDropShadow')
    .attr('dx', 0).attr('dy', 3)
    .attr('stdDeviation', 10)
    .attr('flood-color', 'rgba(0,0,0,0.12)');

// Glow filter (for hover and center pulse)
function makeGlowFilter(id, color, stdDev) {
    const f = defs.append('filter')
        .attr('id', id)
        .attr('x', '-100%').attr('y', '-100%')
        .attr('width', '300%').attr('height', '300%');
    f.append('feGaussianBlur').attr('in', 'SourceGraphic').attr('stdDeviation', stdDev).attr('result', 'blur');
    f.append('feFlood').attr('flood-color', color).attr('flood-opacity', 0.4).attr('result', 'color');
    f.append('feComposite').attr('in', 'color').attr('in2', 'blur').attr('operator', 'in').attr('result', 'glow');
    const merge = f.append('feMerge');
    merge.append('feMergeNode').attr('in', 'glow');
    merge.append('feMergeNode').attr('in', 'SourceGraphic');
}

makeGlowFilter('vo-glow-orange', '#FF8B5E', 8);
makeGlowFilter('vo-glow-purple', '#8E44AD', 6);
makeGlowFilter('vo-glow-blue', '#2E86DE', 6);
makeGlowFilter('vo-glow-green', '#27AE60', 6);

function glowFilterFor(group) {
    if (group === 'dv4') return 'url(#vo-glow-purple)';
    if (group === 'dv6') return 'url(#vo-glow-blue)';
    if (group === 'project') return 'url(#vo-glow-green)';
    return 'url(#vo-glow-orange)';
}

// Connection line gradient template
function getOrCreateGradient(sourceColor, targetColor, id) {
    if (defs.select('#' + id).empty()) {
        const grad = defs.append('linearGradient').attr('id', id)
            .attr('gradientUnits', 'userSpaceOnUse');
        grad.append('stop').attr('offset', '0%').attr('stop-color', sourceColor).attr('stop-opacity', 0.6);
        grad.append('stop').attr('offset', '100%').attr('stop-color', targetColor).attr('stop-opacity', 0.6);
    }
    return `url(#${id})`;
}

// Layers
const gRings = svg.append('g').attr('class', 'vo-rings');
const gParticles = svg.append('g').attr('class', 'vo-particles');
const gConnLines = svg.append('g').attr('class', 'vo-conn-lines');
const gNodes = svg.append('g').attr('class', 'vo-nodes');
const gCenter = svg.append('g').attr('class', 'vo-center');


/* =========================================================================
   ORBITAL RINGS
   ========================================================================= */

// Dynamic ring radii — computed per render based on active rings
let activeRingRadii = {}; // ringIndex -> radius

function computeRingRadii(layout) {
    activeRingRadii = {};
    const activeIndices = layout.rings.map(r => r.ringIndex).sort((a, b) => a - b);
    if (!activeIndices.length) return;

    const minR = 80; // minimum distance from center
    const step = (maxRadius - minR) / (activeIndices.length);
    activeIndices.forEach((idx, i) => {
        activeRingRadii[idx] = minR + step * (i + 1);
    });
}

function drawRings() {
    gRings.selectAll('*').remove();

    const radii = Object.values(activeRingRadii);
    radii.forEach((r, i) => {
        gRings.append('circle')
            .attr('cx', cx)
            .attr('cy', cy)
            .attr('r', r)
            .attr('fill', 'none')
            .attr('stroke', '#B8B0A4')
            .attr('stroke-opacity', 1)
            .attr('stroke-width', i % 2 === 0 ? 1.2 : 0.6)
            .attr('stroke-dasharray', i % 2 === 0 ? '6 6' : '2 6');
    });
}


/* =========================================================================
   ORBIT PARTICLES (small dots moving along rings)
   ========================================================================= */

let particleData = [];

function spawnParticles() {
    gParticles.selectAll('*').remove();
    particleData = [];

    Object.entries(activeRingRadii).forEach(([idx, r], i) => {
        const count = 2 + (i % 3);
        for (let j = 0; j < count; j++) {
            particleData.push({
                ringIndex: i,
                radius: r,
                angle: (Math.PI * 2 / count) * j + Math.random() * Math.PI,
                speed: (0.15 + Math.random() * 0.15) / (i + 1),
                direction: i % 2 === 0 ? 1 : -1,
                size: 1.5 + Math.random() * 1.5,
                opacity: 0.2 + Math.random() * 0.25
            });
        }
    });

    particleData.forEach((p, idx) => {
        gParticles.append('circle')
            .attr('class', 'vo-particle')
            .attr('r', p.size)
            .attr('fill', colors.primary)
            .attr('opacity', p.opacity)
            .attr('data-idx', idx);
    });
}


/* =========================================================================
   STATE
   ========================================================================= */

let currentCenter = ROOT_ID;
let navigationStack = [];
let isAnimating = false;
let animationFrame;
let animTime = 0;


/* =========================================================================
   LAYOUT
   ========================================================================= */

function computeLayout(centerId) {
    const connections = getConnections(centerId);
    const centerNode = nodeMap[centerId];

    if (!connections.length) return { center: centerNode, rings: [] };

    const connectedNodes = connections.map(id => nodeMap[id]).filter(Boolean);
    const dvNodes = connectedNodes.filter(n => n.group === 'dv4' || n.group === 'dv6');
    const projectNodes = connectedNodes.filter(n => n.group === 'project');
    const otherNodes = connectedNodes.filter(n => n.group === 'center');

    if (centerId === ROOT_ID) {
        return {
            center: centerNode,
            rings: [
                { nodes: dvNodes.filter(n => n.group === 'dv4'), ringIndex: 1, label: 'Kap. 4' },
                { nodes: dvNodes.filter(n => n.group === 'dv6'), ringIndex: 3, label: 'Kap. 6' }
            ]
        };
    }

    if (centerNode.group === 'dv4' || centerNode.group === 'dv6') {
        const sorted = projectNodes.sort((a, b) => getConnectionCount(b.id) - getConnectionCount(a.id));
        const ring1 = [], ring2 = [], ring3 = [], ring4 = [];

        sorted.forEach(n => {
            const c = getConnectionCount(n.id);
            if (c >= 4) ring1.push(n);
            else if (c === 3) ring2.push(n);
            else if (c === 2) ring3.push(n);
            else ring4.push(n);
        });

        const otherDv = dvNodes.filter(n => n.id !== centerId);
        const rings = [];
        if (ring1.length) rings.push({ nodes: ring1, ringIndex: 1 });
        if (ring2.length) rings.push({ nodes: ring2, ringIndex: 2 });
        if (ring3.length) rings.push({ nodes: ring3, ringIndex: 3 });
        if (ring4.length) rings.push({ nodes: ring4, ringIndex: 4 });
        if (otherNodes.length || otherDv.length) rings.push({ nodes: [...otherNodes, ...otherDv], ringIndex: 5 });
        return { center: centerNode, rings };
    }

    if (centerNode.group === 'project') {
        const rings = [];
        if (dvNodes.length) rings.push({ nodes: dvNodes, ringIndex: 1 });
        if (otherNodes.length) rings.push({ nodes: otherNodes, ringIndex: 3 });
        return { center: centerNode, rings };
    }

    return { center: centerNode, rings: [{ nodes: connectedNodes, ringIndex: 2 }] };
}


/* =========================================================================
   RENDER
   ========================================================================= */

function getAbbr(node) { return node.abbr || node.id.substring(0, 2).toUpperCase(); }

function getShortLabel(node) {
    const name = node.full || node.id;
    return name.length <= 16 ? name : name.substring(0, 14) + '...';
}

function nodeRadius(node, isCenter) {
    if (isCenter) return 46;
    if (node.group === 'center') return 32;
    return 26;
}

function startAnimation() {
    if (animationFrame) cancelAnimationFrame(animationFrame);

    function tick() {
        animTime += 0.0004;

        // Move orbit nodes
        gNodes.selectAll('.vo-orbit-group').each(function() {
            const el = d3.select(this);
            const ringIdx = +el.attr('data-ring');
            const baseAngle = +el.attr('data-base-angle');
            const r = +el.attr('data-radius');
            const speed = 1 / (ringIdx + 1);
            const direction = ringIdx % 2 === 0 ? 1 : -1;
            const angle = baseAngle + animTime * speed * direction;
            el.attr('transform', `translate(${cx + r * Math.cos(angle)}, ${cy + r * Math.sin(angle)})`);
        });

        // Move particles
        particleData.forEach((p, idx) => {
            p.angle += p.speed * p.direction * 0.016;
            const x = cx + p.radius * Math.cos(p.angle);
            const y = cy + p.radius * Math.sin(p.angle);
            gParticles.select(`[data-idx="${idx}"]`)
                .attr('cx', x).attr('cy', y);
        });

        // Pulse center glow rings (very subtle breathing)
        gCenter.selectAll('.vo-center-pulse').each(function(d, i) {
            const el = d3.select(this);
            const baseR = +el.attr('data-base-r');
            const t = animTime * 150 + i * 0.8; // slow cycle, offset per ring
            el.attr('r', baseR + 1.5 * Math.sin(t))
              .attr('stroke-opacity', 0.06 + 0.03 * Math.sin(t));
        });

        animationFrame = requestAnimationFrame(tick);
    }
    tick();
}

function render(centerId, animate = true) {
    if (isAnimating) return;
    isAnimating = animate;

    const layout = computeLayout(centerId);
    const duration = animate ? 900 : 0;
    const ease = d3.easeBackOut.overshoot(0.7);

    computeRingRadii(layout);
    drawRings();
    spawnParticles();

    // --- CENTER NODE ---
    gCenter.selectAll('*').remove();
    const cNode = layout.center;
    const cR = nodeRadius(cNode, true);
    const cColor = nodeColor(cNode.group);

    const centerG = gCenter.append('g')
        .attr('transform', `translate(${cx}, ${cy})`)
        .style('cursor', 'default');

    // Pulsating glow rings (3 concentric, animated in tick)
    [1, 2].forEach(i => {
        centerG.append('circle')
            .attr('class', 'vo-center-pulse')
            .attr('data-base-r', cR + 8 + i * 12)
            .attr('r', cR + 8 + i * 12)
            .attr('fill', 'none')
            .attr('stroke', cColor)
            .attr('stroke-opacity', 0.07)
            .attr('stroke-width', 1);
    });

    // Soft colored fill behind white
    centerG.append('circle')
        .attr('r', animate ? 0 : cR + 3)
        .attr('fill', nodeColorLight(cNode.group))
        .attr('opacity', 0.6)
        .transition().duration(duration).ease(ease)
        .attr('r', cR + 3);

    // Main circle
    centerG.append('circle')
        .attr('r', animate ? 0 : cR)
        .attr('fill', colors.nodeBg)
        .attr('stroke', cColor)
        .attr('stroke-width', 2.5)
        .attr('filter', 'url(#vo-shadow-center)')
        .transition().duration(duration).ease(ease)
        .attr('r', cR);

    // Center abbreviation
    const centerAbbr = getAbbr(cNode);
    centerG.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', centerAbbr.length > 3 ? '-0.1em' : '0.1em')
        .attr('font-size', centerAbbr.length > 3 ? 11 : 15)
        .attr('font-weight', 700)
        .attr('fill', cColor)
        .attr('opacity', animate ? 0 : 1)
        .text(centerAbbr)
        .transition().duration(duration).attr('opacity', 1);

    // Full name below
    const centerName = cNode.full || cNode.id;
    const nameLines = wrapText(centerName, 18);
    nameLines.forEach((line, i) => {
        centerG.append('text')
            .attr('text-anchor', 'middle')
            .attr('y', cR + 20 + i * 16)
            .attr('font-size', 12)
            .attr('font-weight', 600)
            .attr('fill', colors.text)
            .attr('opacity', animate ? 0 : 1)
            .text(line)
            .transition().delay(duration * 0.3).duration(duration * 0.7).attr('opacity', 1);
    });

    // Connection count
    centerG.append('text')
        .attr('text-anchor', 'middle')
        .attr('y', cR + 20 + nameLines.length * 16 + 4)
        .attr('font-size', 11)
        .attr('fill', colors.textSec)
        .attr('opacity', animate ? 0 : 1)
        .text(getConnectionCount(cNode.id) + ' koblinger')
        .transition().delay(duration * 0.5).duration(duration * 0.5).attr('opacity', 1);

    showInfo(cNode);

    // --- ORBITING NODES ---
    gNodes.selectAll('*').remove();
    gConnLines.selectAll('*').remove();

    layout.rings.forEach((ring, ri) => {
        const r = activeRingRadii[ring.ringIndex] || 200;
        const count = ring.nodes.length;

        ring.nodes.forEach((node, ni) => {
            const baseAngle = (2 * Math.PI / count) * ni - Math.PI / 2 + (ri * 0.3);
            const nR = nodeRadius(node, false);
            const nColor = nodeColor(node.group);

            // FLY-IN: start from outside viewport at the same angle
            const flyDist = maxRadius + 200;
            const startX = animate ? cx + flyDist * Math.cos(baseAngle) : cx + r * Math.cos(baseAngle);
            const startY = animate ? cy + flyDist * Math.sin(baseAngle) : cy + r * Math.sin(baseAngle);
            const endX = cx + r * Math.cos(baseAngle);
            const endY = cy + r * Math.sin(baseAngle);

            // Connection line (gradient)
            const gradId = 'grad-' + ri + '-' + ni;
            const gradUrl = getOrCreateGradient(cColor, nColor, gradId);

            // Update gradient coordinates
            defs.select('#' + gradId)
                .attr('x1', cx).attr('y1', cy)
                .attr('x2', endX).attr('y2', endY);

            const line = gConnLines.append('line')
                .attr('x1', cx).attr('y1', cy)
                .attr('x2', animate ? cx : endX)
                .attr('y2', animate ? cy : endY)
                .attr('stroke', gradUrl)
                .attr('stroke-opacity', 0.12)
                .attr('stroke-width', 1)
                .attr('stroke-linecap', 'round')
                .attr('data-node-id', node.id);

            if (animate) {
                line.transition()
                    .delay(150 + ri * 60 + ni * 25)
                    .duration(duration)
                    .attr('x2', endX).attr('y2', endY)
                    .attr('stroke-opacity', 0.12);
            }

            // Node group
            const g = gNodes.append('g')
                .attr('class', 'vo-orbit-group')
                .attr('data-ring', ring.ringIndex)
                .attr('data-base-angle', baseAngle)
                .attr('data-radius', r)
                .attr('data-node-id', node.id)
                .attr('transform', `translate(${startX}, ${startY})`)
                .style('cursor', 'pointer')
                .style('opacity', animate ? 0 : 1);

            if (animate) {
                g.transition()
                    .delay(150 + ri * 60 + ni * 25)
                    .duration(duration)
                    .ease(ease)
                    .style('opacity', 1)
                    .attr('transform', `translate(${endX}, ${endY})`);
            }

            // Hover glow ring (hidden by default)
            g.append('circle')
                .attr('class', 'vo-hover-glow')
                .attr('r', nR + 6)
                .attr('fill', nodeColorLight(node.group))
                .attr('opacity', 0);

            // Colored background ring
            g.append('circle')
                .attr('r', nR + 2)
                .attr('fill', nodeColorLight(node.group))
                .attr('opacity', 0.5);

            // Main node circle
            g.append('circle')
                .attr('class', 'vo-node-main')
                .attr('r', nR)
                .attr('fill', colors.nodeBg)
                .attr('stroke', nColor)
                .attr('stroke-width', 2)
                .attr('filter', 'url(#vo-shadow)');

            // Connection count badge (top-right)
            const connCount = getConnectionCount(node.id);
            if (connCount > 1) {
                g.append('circle')
                    .attr('cx', nR - 2).attr('cy', -(nR - 2))
                    .attr('r', 7)
                    .attr('fill', nColor);
                g.append('text')
                    .attr('x', nR - 2).attr('y', -(nR - 2))
                    .attr('text-anchor', 'middle').attr('dy', '0.35em')
                    .attr('font-size', 8).attr('font-weight', 700)
                    .attr('fill', '#fff')
                    .text(connCount);
            }

            // Abbreviation inside
            const abbr = getAbbr(node);
            g.append('text')
                .attr('text-anchor', 'middle')
                .attr('dy', '0.35em')
                .attr('font-size', abbr.length > 3 ? 9 : 11)
                .attr('font-weight', 600)
                .attr('fill', nColor)
                .text(abbr);

            // Label below
            g.append('text')
                .attr('text-anchor', 'middle')
                .attr('y', nR + 14)
                .attr('font-size', 10)
                .attr('fill', colors.textSec)
                .attr('font-weight', 500)
                .text(getShortLabel(node));

            // --- HOVER & CLICK ---
            g.on('mouseenter', function() {
                // Glow effect on hovered node
                d3.select(this).select('.vo-hover-glow')
                    .transition().duration(200)
                    .attr('opacity', 0.8)
                    .attr('r', nR + 10);

                d3.select(this).select('.vo-node-main')
                    .transition().duration(200)
                    .attr('r', nR + 3)
                    .attr('stroke-width', 3);

                // Highlight its connection line, dim others
                gConnLines.selectAll('line').each(function() {
                    const lineEl = d3.select(this);
                    const isThis = lineEl.attr('data-node-id') === node.id;
                    lineEl.transition().duration(200)
                        .attr('stroke-opacity', isThis ? 0.5 : 0.04)
                        .attr('stroke-width', isThis ? 2.5 : 0.5);
                });

                // Dim other orbit nodes
                gNodes.selectAll('.vo-orbit-group').each(function() {
                    const el = d3.select(this);
                    const isThis = el.attr('data-node-id') === node.id;
                    el.transition().duration(200)
                        .style('opacity', isThis ? 1 : 0.35);
                });

                showInfo(node);
            })
            .on('mouseleave', function() {
                d3.select(this).select('.vo-hover-glow')
                    .transition().duration(300)
                    .attr('opacity', 0)
                    .attr('r', nR + 6);

                d3.select(this).select('.vo-node-main')
                    .transition().duration(300)
                    .attr('r', nR)
                    .attr('stroke-width', 2);

                gConnLines.selectAll('line')
                    .transition().duration(300)
                    .attr('stroke-opacity', 0.12)
                    .attr('stroke-width', 1);

                gNodes.selectAll('.vo-orbit-group')
                    .transition().duration(300)
                    .style('opacity', 1);

                showInfo(nodeMap[currentCenter]);
            })
            .on('click', function(event) {
                event.stopPropagation();
                navigateTo(node.id);
            });
        });
    });

    // After animation, start orbiting
    setTimeout(() => {
        isAnimating = false;
        startAnimation();
    }, animate ? duration + 300 : 50);

    updateBreadcrumb();
    updateBackButton();
}


/* =========================================================================
   NAVIGATION
   ========================================================================= */

function navigateTo(nodeId) {
    if (nodeId === currentCenter || isAnimating) return;
    if (!nodeMap[nodeId]) return;
    navigationStack.push(currentCenter);
    currentCenter = nodeId;
    if (animationFrame) cancelAnimationFrame(animationFrame);
    render(currentCenter, true);
}

function navigateBack() {
    if (!navigationStack.length || isAnimating) return;
    currentCenter = navigationStack.pop();
    if (animationFrame) cancelAnimationFrame(animationFrame);
    render(currentCenter, true);
}

function navigateToIndex(index) {
    if (isAnimating) return;
    if (index === navigationStack.length) return;
    const targetId = index === 0 ? navigationStack[0] : navigationStack[index];
    navigationStack = navigationStack.slice(0, index);
    currentCenter = targetId;
    if (animationFrame) cancelAnimationFrame(animationFrame);
    render(currentCenter, true);
}


/* =========================================================================
   BREADCRUMB
   ========================================================================= */

function updateBreadcrumb() {
    const el = document.getElementById('vo-breadcrumb');
    if (navigationStack.length === 0) { el.classList.remove('visible'); return; }
    el.classList.add('visible');
    el.innerHTML = '';

    const fullPath = [...navigationStack, currentCenter];
    fullPath.forEach((id, i) => {
        const node = nodeMap[id];
        if (!node) return;
        const span = document.createElement('span');
        const shortName = (node.full || node.id);
        span.textContent = shortName.length > 20 ? shortName.substring(0, 18) + '...' : shortName;

        if (i === fullPath.length - 1) {
            span.className = 'vo-breadcrumb-item current';
        } else {
            span.className = 'vo-breadcrumb-item';
            span.onclick = () => navigateToIndex(i);
        }
        el.appendChild(span);

        if (i < fullPath.length - 1) {
            const sep = document.createElement('span');
            sep.className = 'vo-breadcrumb-sep';
            sep.innerHTML = '&#8250;';
            el.appendChild(sep);
        }
    });
}

function updateBackButton() {
    const btn = document.getElementById('vo-back-btn');
    btn.classList.toggle('visible', navigationStack.length > 0);
}

document.getElementById('vo-back-btn').addEventListener('click', navigateBack);


/* =========================================================================
   INFO PANEL
   ========================================================================= */

function showInfo(node) {
    const panel = document.getElementById('vo-info');
    document.getElementById('vo-info-name').textContent = node.full || node.id;

    let badgeClass = 'center', badgeText = 'Sentrum';
    if (node.group === 'dv4') { badgeClass = 'dv4'; badgeText = 'Kap. 4 - Rammeverk'; }
    else if (node.group === 'dv6') { badgeClass = 'dv6'; badgeText = 'Kap. 6 - Teknologi'; }
    else if (node.group === 'project') { badgeClass = 'project'; badgeText = 'Prosjekt'; }
    document.getElementById('vo-info-badge-wrap').innerHTML = `<span class="vo-info-badge ${badgeClass}">${badgeText}</span>`;

    const descEl = document.getElementById('vo-info-desc');
    descEl.textContent = node.desc || '';
    descEl.style.display = node.desc ? 'block' : 'none';

    let metaHtml = '';
    if (node.org) metaHtml += `<strong>Organisasjon:</strong> ${node.org}<br>`;
    metaHtml += `<strong>Koblinger:</strong> ${getConnectionCount(node.id)}`;
    document.getElementById('vo-info-meta').innerHTML = metaHtml;

    const connections = getConnections(node.id);
    const connectionsEl = document.getElementById('vo-info-connections');
    const chipsEl = document.getElementById('vo-info-chips');
    if (connections.length) {
        connectionsEl.style.display = 'block';
        chipsEl.innerHTML = '';
        connections.forEach(connId => {
            const connNode = nodeMap[connId];
            if (!connNode) return;
            const chip = document.createElement('span');
            chip.className = 'vo-info-chip';
            chip.textContent = connNode.full || connId;
            chip.onclick = () => navigateTo(connId);
            chipsEl.appendChild(chip);
        });
    } else {
        connectionsEl.style.display = 'none';
    }

    const sourceWrap = document.getElementById('vo-info-source-wrap');
    if (node.links && node.links.length) {
        sourceWrap.innerHTML = node.links.map(l =>
            `<a href="${l.url}" target="_blank" rel="noopener" class="vo-info-source">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                ${l.label}
            </a>`
        ).join('');
    } else {
        sourceWrap.innerHTML = '';
    }

    panel.classList.add('visible');
}


/* =========================================================================
   HELPERS
   ========================================================================= */

function wrapText(text, maxLen) {
    if (text.length <= maxLen) return [text];
    const words = text.split(' ');
    const lines = [];
    let current = '';
    words.forEach(w => {
        if ((current + ' ' + w).trim().length > maxLen && current) {
            lines.push(current.trim());
            current = w;
        } else {
            current = (current + ' ' + w).trim();
        }
    });
    if (current) lines.push(current.trim());
    return lines.slice(0, 3);
}


/* =========================================================================
   RESIZE
   ========================================================================= */

let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        if (animationFrame) cancelAnimationFrame(animationFrame);
        location.reload();
    }, 300);
});


/* =========================================================================
   CLICK BACKGROUND
   ========================================================================= */

svg.on('click', function() {
    showInfo(nodeMap[currentCenter]);
});


/* =========================================================================
   INIT
   ========================================================================= */

render(ROOT_ID, true);

})();
}); // end requestAnimationFrame
</script>
