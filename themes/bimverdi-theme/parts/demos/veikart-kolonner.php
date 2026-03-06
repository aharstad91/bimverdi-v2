<?php
/**
 * Demo: Veikart-kolonner
 *
 * Grouped columns visualization for Digitalt Veikart 2.0.
 * Two columns — Kap. 4 (left) and Kap. 6 (right).
 * Each DV heading is an expandable accordion with connected projects.
 *
 * Loaded via single-demo.php -> get_template_part('parts/demos/veikart-kolonner')
 * Header is already loaded by the parent template.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<style>
/* ─── Base / Reset ──────────────────────────────────────────────────────── */
:root {
    --vk-primary: #FF8B5E;
    --vk-bg: #FAFAF9;
    --vk-white: #FFFFFF;
    --vk-text: #1A1A1A;
    --vk-text-sec: #5A5A5A;
    --vk-border: #E7E5E4;
    --vk-dv4: #8E44AD;
    --vk-dv6: #2E86DE;
    --vk-project: #27AE60;
    --vk-dv4-bg: #F5EEF8;
    --vk-dv6-bg: #EBF5FB;
    --vk-dv4-light: #D2B4DE;
    --vk-dv6-light: #85C1E9;
}

.vk-page {
    background: var(--vk-bg);
    min-height: 100vh;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: var(--vk-text);
    padding-bottom: 80px;
}

/* ─── Header ────────────────────────────────────────────────────────────── */
.vk-header {
    max-width: 1280px;
    margin: 0 auto;
    padding: 32px 24px 0;
}

.vk-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--vk-text-sec);
    text-decoration: none;
    font-size: 14px;
    margin-bottom: 24px;
    transition: color 0.15s;
}

.vk-back:hover {
    color: var(--vk-text);
}

.vk-back svg {
    width: 16px;
    height: 16px;
}

.vk-title {
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 8px;
    color: var(--vk-text);
    line-height: 1.2;
}

.vk-subtitle {
    font-size: 16px;
    color: var(--vk-text-sec);
    margin: 0 0 24px;
}

/* ─── Stats Bar ─────────────────────────────────────────────────────────── */
.vk-stats {
    display: flex;
    gap: 32px;
    padding: 16px 0;
    border-top: 1px solid var(--vk-border);
    border-bottom: 1px solid var(--vk-border);
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.vk-stat {
    display: flex;
    align-items: baseline;
    gap: 6px;
}

.vk-stat-num {
    font-size: 24px;
    font-weight: 700;
    color: var(--vk-primary);
}

.vk-stat-label {
    font-size: 14px;
    color: var(--vk-text-sec);
}

/* ─── Search ────────────────────────────────────────────────────────────── */
.vk-search-wrap {
    position: relative;
    margin-bottom: 32px;
    max-width: 480px;
}

.vk-search {
    width: 100%;
    padding: 12px 16px 12px 44px;
    border: 1px solid var(--vk-border);
    border-radius: 8px;
    font-size: 15px;
    background: var(--vk-white);
    color: var(--vk-text);
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.vk-search:focus {
    border-color: var(--vk-primary);
    box-shadow: 0 0 0 3px rgba(255, 139, 94, 0.15);
}

.vk-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: var(--vk-text-sec);
    pointer-events: none;
}

.vk-search-clear {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    border: none;
    background: none;
    color: var(--vk-text-sec);
    cursor: pointer;
    padding: 0;
    display: none;
    align-items: center;
    justify-content: center;
}

.vk-search-clear.visible {
    display: flex;
}

/* ─── Columns Layout ────────────────────────────────────────────────────── */
.vk-columns {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
}

.vk-column {
    min-width: 0;
}

/* ─── Column Header ─────────────────────────────────────────────────────── */
.vk-col-header {
    padding: 16px 20px;
    border-radius: 10px;
    margin-bottom: 16px;
}

.vk-col-header--kap4 {
    background: var(--vk-dv4-bg);
    border: 1px solid var(--vk-dv4-light);
}

.vk-col-header--kap6 {
    background: var(--vk-dv6-bg);
    border: 1px solid var(--vk-dv6-light);
}

.vk-col-title {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 4px;
}

.vk-col-header--kap4 .vk-col-title {
    color: var(--vk-dv4);
}

.vk-col-header--kap6 .vk-col-title {
    color: var(--vk-dv6);
}

.vk-col-summary {
    font-size: 13px;
    color: var(--vk-text-sec);
    margin: 0;
}

/* ─── Accordion ─────────────────────────────────────────────────────────── */
.vk-accordion {
    background: var(--vk-white);
    border: 1px solid var(--vk-border);
    border-radius: 8px;
    margin-bottom: 8px;
    overflow: hidden;
    transition: box-shadow 0.2s;
}

.vk-accordion:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.vk-accordion.hidden-by-search {
    display: none;
}

.vk-accordion-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border: none;
    background: none;
    cursor: pointer;
    text-align: left;
    font-family: inherit;
    transition: background 0.15s;
}

.vk-accordion-toggle:hover {
    background: rgba(0, 0, 0, 0.02);
}

.vk-accordion-bar {
    width: 4px;
    height: 24px;
    border-radius: 2px;
    flex-shrink: 0;
}

.vk-accordion-bar--kap4 {
    background: var(--vk-dv4);
}

.vk-accordion-bar--kap6 {
    background: var(--vk-dv6);
}

.vk-accordion-label {
    flex: 1;
    font-size: 14px;
    font-weight: 600;
    color: var(--vk-text);
    line-height: 1.3;
    min-width: 0;
}

.vk-accordion-count {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 26px;
    height: 22px;
    padding: 0 7px;
    border-radius: 11px;
    font-size: 12px;
    font-weight: 600;
    color: var(--vk-white);
}

.vk-accordion-count--kap4 {
    background: var(--vk-dv4);
}

.vk-accordion-count--kap6 {
    background: var(--vk-dv6);
}

.vk-accordion-chevron {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    color: var(--vk-text-sec);
    transition: transform 0.25s ease;
}

.vk-accordion.open .vk-accordion-chevron {
    transform: rotate(180deg);
}

/* ─── Accordion Content ─────────────────────────────────────────────────── */
.vk-accordion-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.35s ease;
}

.vk-accordion.open .vk-accordion-content {
    /* max-height set by JS */
}

.vk-accordion-inner {
    padding: 0 16px 12px;
    border-top: 1px solid var(--vk-border);
}

/* ─── Project List Items ────────────────────────────────────────────────── */
.vk-project-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.12s;
}

.vk-project-item:hover {
    background: rgba(0, 0, 0, 0.03);
}

.vk-project-item.hidden-by-search {
    display: none;
}

.vk-project-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--vk-project);
    flex-shrink: 0;
}

.vk-project-info {
    flex: 1;
    min-width: 0;
    line-height: 1.4;
}

.vk-project-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--vk-text);
}

.vk-project-org {
    font-size: 13px;
    color: var(--vk-text-sec);
}

.vk-project-org::before {
    content: ' — ';
}

.vk-project-badge {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    color: var(--vk-text-sec);
    background: #F0EFED;
    white-space: nowrap;
}

.vk-project-arrow {
    flex-shrink: 0;
    width: 16px;
    height: 16px;
    color: #C0BDB8;
    transition: color 0.12s;
}

.vk-project-item:hover .vk-project-arrow {
    color: var(--vk-primary);
}

/* ─── Detail Overlay ────────────────────────────────────────────────────── */
.vk-overlay-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 9998;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
}

.vk-overlay-backdrop.active {
    opacity: 1;
    pointer-events: auto;
}

.vk-overlay {
    position: fixed;
    top: 0;
    right: 0;
    width: 480px;
    max-width: 100vw;
    height: 100vh;
    background: var(--vk-white);
    z-index: 9999;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    overflow-y: auto;
    box-shadow: -4px 0 24px rgba(0, 0, 0, 0.12);
}

.vk-overlay.active {
    transform: translateX(0);
}

.vk-overlay-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 24px 24px 0;
    gap: 16px;
}

.vk-overlay-close {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    border: 1px solid var(--vk-border);
    border-radius: 8px;
    background: var(--vk-white);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.12s;
}

.vk-overlay-close:hover {
    background: #F5F5F4;
}

.vk-overlay-close svg {
    width: 18px;
    height: 18px;
    color: var(--vk-text-sec);
}

.vk-overlay-body {
    padding: 24px;
}

.vk-overlay-title {
    font-size: 22px;
    font-weight: 700;
    color: var(--vk-text);
    margin: 0 0 4px;
    line-height: 1.3;
}

.vk-overlay-org {
    font-size: 14px;
    color: var(--vk-text-sec);
    margin: 0 0 20px;
}

.vk-overlay-desc {
    font-size: 15px;
    line-height: 1.6;
    color: var(--vk-text);
    margin: 0 0 24px;
}

.vk-overlay-section-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--vk-text-sec);
    margin: 0 0 10px;
}

.vk-overlay-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 24px;
}

.vk-overlay-tag {
    display: inline-flex;
    align-items: center;
    padding: 5px 12px;
    border-radius: 16px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: opacity 0.12s;
    border: none;
    font-family: inherit;
}

.vk-overlay-tag:hover {
    opacity: 0.8;
}

.vk-overlay-tag--kap4 {
    background: var(--vk-dv4-bg);
    color: var(--vk-dv4);
}

.vk-overlay-tag--kap6 {
    background: var(--vk-dv6-bg);
    color: var(--vk-dv6);
}

.vk-overlay-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 8px;
    background: var(--vk-primary);
    color: var(--vk-white);
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: opacity 0.15s;
    margin-bottom: 8px;
    margin-right: 8px;
}

.vk-overlay-link:hover {
    opacity: 0.85;
    color: var(--vk-white);
}

.vk-overlay-link svg {
    width: 14px;
    height: 14px;
}

/* ─── No Results ────────────────────────────────────────────────────────── */
.vk-no-results {
    text-align: center;
    padding: 48px 24px;
    color: var(--vk-text-sec);
    font-size: 15px;
    display: none;
}

.vk-no-results.visible {
    display: block;
}

/* ─── Responsive ────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .vk-columns {
        grid-template-columns: 1fr;
        gap: 24px;
    }

    .vk-title {
        font-size: 24px;
    }

    .vk-stats {
        gap: 20px;
    }

    .vk-stat-num {
        font-size: 20px;
    }

    .vk-overlay {
        width: 100vw;
    }

    .vk-search-wrap {
        max-width: 100%;
    }
}
</style>

<div class="vk-page">

    <!-- Header -->
    <div class="vk-header">
        <a href="/bimverdi-v2/demo/" class="vk-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
            Tilbake til demo-oversikt
        </a>

        <h1 class="vk-title">Veikart-kolonner</h1>
        <p class="vk-subtitle">Digitalt Veikart 2.0 &mdash; kapittel for kapittel med tilknyttede prosjekter</p>

        <!-- Stats -->
        <div class="vk-stats">
            <div class="vk-stat">
                <span class="vk-stat-num" id="vk-stat-projects">44</span>
                <span class="vk-stat-label">prosjekter</span>
            </div>
            <div class="vk-stat">
                <span class="vk-stat-num" id="vk-stat-links">107</span>
                <span class="vk-stat-label">koblinger</span>
            </div>
            <div class="vk-stat">
                <span class="vk-stat-num" id="vk-stat-goals">15</span>
                <span class="vk-stat-label">DV-maal</span>
            </div>
        </div>

        <!-- Search -->
        <div class="vk-search-wrap">
            <svg class="vk-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" class="vk-search" id="vk-search" placeholder="Sok i prosjekter og maalomraader...">
            <button class="vk-search-clear" id="vk-search-clear" title="Tøm søk">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
    </div>

    <!-- Columns -->
    <div class="vk-columns">

        <!-- Kap 4 Column -->
        <div class="vk-column" id="vk-col-kap4">
            <div class="vk-col-header vk-col-header--kap4">
                <h2 class="vk-col-title">Kapittel 4 &mdash; Felleskomponenter</h2>
                <p class="vk-col-summary" id="vk-summary-kap4"></p>
            </div>
            <div id="vk-accordions-kap4"></div>
        </div>

        <!-- Kap 6 Column -->
        <div class="vk-column" id="vk-col-kap6">
            <div class="vk-col-header vk-col-header--kap6">
                <h2 class="vk-col-title">Kapittel 6 &mdash; Ny teknologi</h2>
                <p class="vk-col-summary" id="vk-summary-kap6"></p>
            </div>
            <div id="vk-accordions-kap6"></div>
        </div>

    </div>

    <div class="vk-no-results" id="vk-no-results">
        Ingen treff. Prov et annet sokeord.
    </div>

    <!-- Detail Overlay -->
    <div class="vk-overlay-backdrop" id="vk-backdrop"></div>
    <div class="vk-overlay" id="vk-overlay">
        <div class="vk-overlay-header">
            <div>
                <h2 class="vk-overlay-title" id="vk-overlay-title"></h2>
                <p class="vk-overlay-org" id="vk-overlay-org"></p>
            </div>
            <button class="vk-overlay-close" id="vk-overlay-close" title="Lukk">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="vk-overlay-body">
            <p class="vk-overlay-desc" id="vk-overlay-desc"></p>
            <p class="vk-overlay-section-title">DV-tilknytninger</p>
            <div class="vk-overlay-tags" id="vk-overlay-tags"></div>
            <p class="vk-overlay-section-title">Kilder</p>
            <div id="vk-overlay-links"></div>
        </div>
    </div>

</div>

<script>
(function() {
    'use strict';

    // ─── Data ──────────────────────────────────────────────────────────────

    var dvHeadings = {
        '4.1': 'DV 4.1 Felles rammeverk for informasjonsforvaltning',
        '4.2': 'DV 4.2 Internasjonale rammer',
        '4.3': 'DV 4.3 Nasjonale rammer',
        '4.4': 'DV 4.4 Felles spesifikasjoner og komponenter',
        '4.5': 'DV 4.5 Etablere standarder for API',
        '4.6': 'DV 4.6 Sluttbrukerlosninger i markedet',
        '4.7': 'DV 4.7 Forvaltning av felles rammeverk',
        '6.1': 'DV 6.1 Kunstig intelligens / maskinlaering',
        '6.2': 'DV 6.2 Algoritmer',
        '6.3': 'DV 6.3 Sensorteknologi',
        '6.4': 'DV 6.4 RFiD',
        '6.5': 'DV 6.5 Virtual Reality (VR)',
        '6.6': 'DV 6.6 Roboter',
        '6.7': 'DV 6.7 3D-printing',
        '6.8': 'DV 6.8 Droner'
    };

    var kap4Keys = ['4.1', '4.2', '4.3', '4.4', '4.5', '4.6', '4.7'];
    var kap6Keys = ['6.1', '6.2', '6.3', '6.4', '6.5', '6.6', '6.7', '6.8'];

    var projects = [
        { id: 'POFIN', org: 'buildingSMART Norge', desc: 'buildingSMART Norges rammeverk for Project and Facilities in Norway. Forbedre og standardisere bruk av openBIM.', links: [{ label: 'POFIN - buildingSMART', url: 'https://buildingsmart.no/pofin' }], dv: ['4.1', '4.3', '4.7'] },
        { id: 'PDT Norge', org: 'Cobuilder / BNL', desc: 'Offisiell distribusjonsplattform for produktdatamaler.', links: [{ label: 'PDT Norge', url: 'https://cobuilder.com/nb/pdt-norge-en-offisiell-distribusjonsplattform-for-produktdatamaler/' }], dv: ['4.4', '4.5'] },
        { id: 'IFC 4.3 ISO-standard', org: 'buildingSMART International', desc: 'IFC 4.3 vedtatt som ISO 16739-1:2024.', links: [{ label: 'IFC 4.3 ISO', url: 'https://buildingsmart.no/nyheter/ifc-iso' }], dv: ['4.2', '4.4', '4.5'] },
        { id: 'Statsbygg BIM-manual 2.0', org: 'Statsbygg', desc: 'Krav til BIM i alle Statsbygg-prosjekter.', links: [{ label: 'SBM2', url: 'https://arkiv.buildingsmart.no/nyhetsbrev/2019-11/statsbyggs-bim-manual-20-sbm2-et-konkret-eksempel-pa-hvordan-apne-standarder-kan' }], dv: ['4.1', '4.3'] },
        { id: 'BIM for alle (Forsvarsbygg)', org: 'Forsvarsbygg', desc: 'Digitalisering av 4 mill. kvm paa openBIM.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/bim/sintef-og-buildingsmart-norge-inngar-samarbeidsavtale/1747497' }], dv: ['4.6', '4.1'] },
        { id: 'boligBIM-prosjektet', org: 'Boligprodusentene / bSN', desc: 'Standardisering av BIM-praksis for boligbygging.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/bolig-boligbygging/full-fart-i-boligbim-prosjektet/2069485' }], dv: ['4.4', '4.6'] },
        { id: 'ISO 19650-serien (norsk)', org: 'Standard Norge', desc: 'NS-EN ISO 19650 oversatt til norsk. Internasjonal standard for informasjonsforvaltning med BIM.', links: [{ label: 'Standard Norge', url: 'https://standard.no/fagomrader/bygg-anlegg-og-eiendom/digital-byggeprosess/iso-19650-serien/' }], dv: ['4.1', '4.2'] },
        { id: 'Digibygg (Statsbygg)', org: 'Statsbygg', desc: 'Digitalisering og smart teknologi i Statsbyggs prosjekter. Digital tvilling, droner, sensorteknologi.', links: [{ label: 'Statsbygg', url: 'https://www.statsbygg.no/nyheter/digitale-tvillinger-i-2023' }], dv: ['6.1', '6.3', '6.8', '4.6'] },
        { id: 'Statsbygg droneinspeksjon', org: 'Statsbygg', desc: 'Inspeksjon av tak og fasader med droner og AI.', links: [{ label: 'Byggfakta', url: 'https://nyheter.byggfakta.no/statsbygg-inngar-avtaler-om-droneinspeksjon-175538/nyhet.html' }], dv: ['6.8', '6.1'] },
        { id: 'SINTEF AI i bygg', org: 'SINTEF', desc: 'Forskning paa AI i byggenaeringen.', links: [{ label: 'SINTEF', url: 'https://www.sintef.no/en/sintef-research-areas/artificial-intelligence/great-effect-using-ai-in-the-construction-industry/' }], dv: ['6.1', '6.2'] },
        { id: 'Veidekke 3D-print betong', org: 'Veidekke', desc: 'Tester 3D-printing av betong.', links: [{ label: 'Digital Norway', url: 'https://digitalnorway.com/aktuelt/dette-er-tech-trendene-som-vil-prege-2026' }], dv: ['6.7', '6.6'] },
        { id: 'Vegvesen bro-inspeksjon', org: 'Statens vegvesen', desc: 'Dronebilder og AI for bro-inspeksjon.', links: [{ label: 'norskbyggebransje.no', url: 'https://norskbyggebransje.no/ai/bygg-og-anlegg-i-2026' }], dv: ['6.8', '6.1', '6.2'] },
        { id: 'Digital Product Passport (DPP)', org: 'Cobuilder / EU', desc: 'Digitale produktpass fra 2026-2029.', links: [{ label: 'Cobuilder', url: 'https://cobuilder.com/en/digital-product-passport-dpp/digital-product-passports-for-construction-what-the-eus-2026-2029-cpr-working-plan-means-for-stakeholders/' }], dv: ['4.4', '4.2', '4.5'] },
        { id: 'Prosjekt Norge digitaliseringsmaaling', org: 'Prosjekt Norge / NTNU', desc: 'Maaling av effekter av digitalisering.', links: [{ label: 'Prosjekt Norge', url: 'https://www.prosjektnorge.no/maling-av-effekter-av-digitalisering-i-den-norske-byggenaeringen/' }], dv: ['4.7', '4.1'] },
        { id: 'Nordic BIM Digital Twin', org: 'Nordic BIM Group', desc: 'Digitale tvillinger for bygg.', links: [{ label: 'Nordic BIM', url: 'https://www.nordicbim.com/no/digital-tvilling' }], dv: ['6.3', '6.5'] },
        { id: 'VA-objekter (Vann og avlop)', org: 'buildingSMART Norge', desc: 'Felles retningslinjer for VA med BIM.', links: [{ label: 'buildingSMART', url: 'https://buildingsmart.no/' }], dv: ['4.4', '4.3'] },
        { id: 'Spacemaker / Autodesk Forma', org: 'Autodesk (tidl. Spacemaker)', desc: 'AI-plattform for tidligfase arealplanlegging.', links: [{ label: 'NTI', url: 'https://www.nti-group.com/no/produkter/autodesk-software/forma/' }], dv: ['6.1', '6.2', '4.6'] },
        { id: 'Catenda Hub (BIMsync)', org: 'Catenda', desc: 'Norsk openBIM-plattform for samhandling.', links: [{ label: 'Catenda', url: 'https://catenda.com/' }], dv: ['4.1', '4.5', '4.6'] },
        { id: 'Novorender', org: 'Novorender (Stavanger)', desc: '3D/BIM-viewer for >100 GB i nettleseren.', links: [{ label: 'Novorender', url: 'https://novorender.com/' }], dv: ['4.6', '4.5', '6.5'] },
        { id: 'Dimension10 VR', org: 'Dimension10 / Catenda', desc: 'VR-plattform for byggebransjen.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/bim-teknologi/norske-vr-og-bim-selskap-inngar-samarbeid/2365941' }], dv: ['6.5', '4.6'] },
        { id: 'Nordisk BIM-samarbeid (infra)', org: 'SVV / Nye Veier / Bane NOR', desc: 'Nordisk samarbeid om openBIM i infra.', links: [{ label: 'Nye Veier', url: 'https://www.nyeveier.no/nyheter/nyheter/statens-vegvesen-nye-veier-og-bane-nor-inngar-nordisk-bim-samarbeid/' }], dv: ['4.2', '4.3', '4.1'] },
        { id: 'Fellestjenester BYGG (DiBK)', org: 'DiBK', desc: 'Altinn-basert plattform for digitale byggesoknader.', links: [{ label: 'DiBK', url: 'https://www.dibk.no/verktoy-og-veivisere/andre-fagomrader/fellestjenester-bygg' }], dv: ['4.3', '4.5', '4.7'] },
        { id: 'Airthings inneklima-sensorer', org: 'Airthings', desc: '500 sensorer styrer 20 aar gammelt bygg.', links: [{ label: 'Estate Vest', url: 'https://www.estatevest.no/500-airthings-sensorer-styrer-20-ar-gammelt-bygg/' }], dv: ['6.3', '6.1'] },
        { id: 'Disruptive Technologies sensorer', org: 'Disruptive Technologies', desc: 'Verdens minste tradlose sensorer.', links: [{ label: 'Digital Norway', url: 'https://digitalnorway.com/proptech-bergen/' }], dv: ['6.3', '6.4'] },
        { id: 'Statsbygg + GS1 RFID-pilot', org: 'Statsbygg / GS1 Norge', desc: 'RFID-sporing av byggevarer.', links: [{ label: 'ITBaktuelt', url: 'https://www.itbaktuelt.no/2017/09/12/rfdi-teknologi-statsbygg-digitalisering-173/' }], dv: ['6.4', '4.4'] },
        { id: 'Dalux Field', org: 'Dalux', desc: 'Digital byggeplass med BIM og AR.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/af-gruppen-ikt/af-gruppen-utvider-konsernavtale-med-dalux/2190158' }], dv: ['6.5', '4.6'] },
        { id: 'nLink borrerbot', org: 'nLink', desc: 'Mobile roboter for byggeplass.', links: [{ label: 'nLink', url: 'https://nlink.no/' }], dv: ['6.6', '6.2'] },
        { id: 'Moelven Byggmodul', org: 'Moelven', desc: 'Industrialisert modulbygging i tre.', links: [{ label: 'Moelven', url: 'https://www.moelven.com/no/produkter-og-tjenester/modulbygg/' }], dv: ['6.6', '6.7'] },
        { id: 'Autility Twin (FDV)', org: 'Autility', desc: 'Digital tvilling for drift og vedlikehold.', links: [{ label: 'norskbyggebransje.no', url: 'https://norskbyggebransje.no/nyheter/digital-tvilling-optimaliserer-drift-og-vedlikehold' }], dv: ['6.3', '4.4', '4.1'] },
        { id: 'Spot robot paa byggeplass', org: 'Diverse entreprenorer', desc: 'Boston Dynamics Spot for autonom inspeksjon.', links: [{ label: 'Tekna', url: 'https://www.tekna.no/fag-og-nettverk/bygg-og-anlegg/byggbloggen/roboter-pa-byggeplass/' }], dv: ['6.6', '6.1'] },
        { id: 'Fornebubanen BIM', org: 'Fornebubanen / Oslo', desc: 'En av verdens storste BIM-modeller.', links: [{ label: 'Novorender', url: 'https://novorender.com/hvordan-novorender-hjelper-radgiver-og-entreprenor-mote-bim-krav-i-store-norske-infrastrukturprosjekter' }], dv: ['4.6', '4.1'] },
        { id: 'OsloMet digital tvilling energi', org: 'OsloMet', desc: 'Digital tvilling for energieffektive bygg.', links: [{ label: 'OsloMet', url: 'https://www.oslomet.no/forskning/forskningsnyheter/hvordan-spare-mer-energi-i-bygg-digital-tvilling' }], dv: ['6.3', '6.1'] },
        { id: 'Norconsult VR/AR-tjenester', org: 'Norconsult', desc: 'VR og AR for prosjektering.', links: [{ label: 'Norconsult', url: 'https://norconsult.no/tjenester/digitalisering/vrar/' }], dv: ['6.5', '4.6'] },
        { id: 'Imerso (3D-skanning + AI)', org: 'Imerso', desc: '3D-skanning og BIM for fremdriftskontroll med AI.', links: [{ label: 'tu.no', url: 'https://www.tu.no/artikler/snart-blir-laserskannere-sa-billige-at-alle-byggeplasser-vil-skannes-24-7-br/458842' }], dv: ['6.1', '6.2', '6.3', '4.6'] },
        { id: 'StreamBIM (Rendra)', org: 'Rendra / JDM Technology', desc: 'Skybasert BIM-viewer med streaming.', links: [{ label: 'StreamBIM', url: 'https://streambim.com/' }], dv: ['4.6', '4.5', '4.1'] },
        { id: 'bSDD (Data Dictionary)', org: 'buildingSMART International', desc: 'Internasjonal dataordbok for klassifikasjoner.', links: [{ label: 'buildingSMART', url: 'https://www.buildingsmart.org/users/services/buildingsmart-data-dictionary/' }], dv: ['4.2', '4.4', '4.5'] },
        { id: 'BREEAM-NOR (miljoesertifisering)', org: 'Gronn Byggallianse', desc: 'Miljoesertifisering for bygg.', links: [{ label: 'Byggalliansen', url: 'https://byggalliansen.no/sertifisering/om-breeam/' }], dv: ['4.3', '4.4'] },
        { id: 'Statsbygg 3D-printet bygg', org: 'Statsbygg', desc: 'Norges forste 3D-printede bygg.', links: [{ label: 'Statsbygg', url: 'https://www.statsbygg.no/nyheter/statsbygg-skal-lage-norges-forste-3d-printede-bygg' }], dv: ['6.7', '6.6'] },
        { id: 'SIMBA modellsjekk (NTI/Statsbygg)', org: 'NTI / Statsbygg', desc: 'Automatisk kontroll av BIM-modeller.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/kommer-med-automatisk-kontroll-av-statsbyggs-bim-krav/1446769!/' }], dv: ['6.2', '4.6', '4.3'] },
        { id: 'Digital byggesoeknad med BIM (DiBK)', org: 'DiBK', desc: 'BIM som dokumentasjon i byggesoknader.', links: [{ label: 'DiBK', url: 'https://www.dibk.no/soknad-og-skjema/vil-du-bruke-bim-i-byggesoknaden' }], dv: ['4.3', '4.5', '6.2'] },
        { id: 'Oslobygg kommunal eiendom', org: 'Oslo kommune', desc: 'Forvalter ca. 1800 kommunale bygg i Oslo.', links: [{ label: 'Oslo kommune', url: 'https://www.oslo.kommune.no/etater-foretak-og-ombud/oslobygg-kf/' }], dv: ['4.1', '6.3', '4.7'] },
        { id: 'MIL 3D-printing betong (Grimstad)', org: 'Mechatronics Innovation Lab', desc: 'Norges forste robotiserte betong-3D-printer.', links: [{ label: 'MIL', url: 'https://mil-as.no/index.php/2024/01/25/3d-printe-betong/' }], dv: ['6.7', '6.6'] },
        { id: 'Digital Product Passport EU (CPR)', org: 'EU-kommisjonen', desc: 'EUs nye byggevareforordning.', links: [{ label: 'Cobuilder', url: 'https://cobuilder.com/en/digital-product-passport-dpp/digital-product-passports-for-construction-what-the-eus-2026-2029-cpr-working-plan-means-for-stakeholders/' }], dv: ['4.2', '4.4'] },
        { id: 'Sweco 3D-skanning FDV', org: 'Sweco', desc: '3D-skannere for drift og vedlikehold.', links: [{ label: 'Sweco', url: 'https://www.sweco.no/aktuelt/nyheter/3d-scanner-for-effektiv-drift-og-vedlikehold-av-bygningsmasser/' }], dv: ['6.3', '4.1'] }
    ];

    // ─── Build lookup maps ─────────────────────────────────────────────────

    // Map: dvKey -> [project indices]
    var dvToProjects = {};
    Object.keys(dvHeadings).forEach(function(k) { dvToProjects[k] = []; });

    projects.forEach(function(p, idx) {
        p.dv.forEach(function(dvKey) {
            if (dvToProjects[dvKey]) {
                dvToProjects[dvKey].push(idx);
            }
        });
    });

    // For each project, count OTHER headings (for cross-reference badge)
    // Badge shows per-column context: when shown under heading X, show how many OTHER headings this project links to
    function getOtherCount(project, currentDv) {
        return project.dv.length - 1;
    }

    // ─── Render ────────────────────────────────────────────────────────────

    function chevronSVG() {
        return '<svg class="vk-accordion-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
    }

    function arrowSVG() {
        return '<svg class="vk-project-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>';
    }

    function renderColumn(containerID, keys, kap) {
        var container = document.getElementById(containerID);
        var totalProjects = 0;
        var html = '';

        keys.forEach(function(dvKey) {
            var projIndices = dvToProjects[dvKey];
            var count = projIndices.length;
            totalProjects += count;

            html += '<div class="vk-accordion" data-dv="' + dvKey + '">';
            html += '<button class="vk-accordion-toggle" data-dv-toggle="' + dvKey + '">';
            html += '<span class="vk-accordion-bar vk-accordion-bar--' + kap + '"></span>';
            html += '<span class="vk-accordion-label">' + dvHeadings[dvKey] + '</span>';
            html += '<span class="vk-accordion-count vk-accordion-count--' + kap + '">' + count + '</span>';
            html += chevronSVG();
            html += '</button>';
            html += '<div class="vk-accordion-content">';
            html += '<div class="vk-accordion-inner">';

            projIndices.forEach(function(idx) {
                var p = projects[idx];
                var otherCount = getOtherCount(p, dvKey);
                html += '<div class="vk-project-item" data-project-idx="' + idx + '">';
                html += '<span class="vk-project-dot"></span>';
                html += '<span class="vk-project-info">';
                html += '<span class="vk-project-name">' + p.id + '</span>';
                html += '<span class="vk-project-org">' + p.org + '</span>';
                html += '</span>';
                if (otherCount > 0) {
                    html += '<span class="vk-project-badge">+' + otherCount + ' andre maal</span>';
                }
                html += arrowSVG();
                html += '</div>';
            });

            html += '</div></div></div>';
        });

        container.innerHTML = html;

        // Update summary
        var summaryEl = document.getElementById('vk-summary-' + kap);
        // Count unique projects in this column
        var uniqueSet = {};
        keys.forEach(function(dvKey) {
            dvToProjects[dvKey].forEach(function(idx) {
                uniqueSet[idx] = true;
            });
        });
        var uniqueCount = Object.keys(uniqueSet).length;
        summaryEl.textContent = keys.length + ' maalomraader \u00B7 ' + uniqueCount + ' prosjekter';
    }

    renderColumn('vk-accordions-kap4', kap4Keys, 'kap4');
    renderColumn('vk-accordions-kap6', kap6Keys, 'kap6');

    // ─── Accordion Toggle ──────────────────────────────────────────────────

    document.addEventListener('click', function(e) {
        var toggle = e.target.closest('.vk-accordion-toggle');
        if (!toggle) return;

        var accordion = toggle.closest('.vk-accordion');
        var content = accordion.querySelector('.vk-accordion-content');

        if (accordion.classList.contains('open')) {
            // Close
            content.style.maxHeight = content.scrollHeight + 'px';
            // Force reflow
            content.offsetHeight;
            content.style.maxHeight = '0px';
            accordion.classList.remove('open');
        } else {
            // Open
            accordion.classList.add('open');
            content.style.maxHeight = content.scrollHeight + 'px';
            // After transition, set auto so inner content can grow
            var handler = function() {
                if (accordion.classList.contains('open')) {
                    content.style.maxHeight = 'none';
                }
                content.removeEventListener('transitionend', handler);
            };
            content.addEventListener('transitionend', handler);
        }
    });

    // ─── Project Click -> Detail Overlay ───────────────────────────────────

    var overlay = document.getElementById('vk-overlay');
    var backdrop = document.getElementById('vk-backdrop');
    var overlayTitle = document.getElementById('vk-overlay-title');
    var overlayOrg = document.getElementById('vk-overlay-org');
    var overlayDesc = document.getElementById('vk-overlay-desc');
    var overlayTags = document.getElementById('vk-overlay-tags');
    var overlayLinks = document.getElementById('vk-overlay-links');
    var overlayClose = document.getElementById('vk-overlay-close');

    function openOverlay(idx) {
        var p = projects[idx];

        overlayTitle.textContent = p.id;
        overlayOrg.textContent = p.org;
        overlayDesc.textContent = p.desc;

        // DV Tags
        var tagsHtml = '';
        p.dv.forEach(function(dvKey) {
            var kap = dvKey.charAt(0) === '4' ? 'kap4' : 'kap6';
            tagsHtml += '<button class="vk-overlay-tag vk-overlay-tag--' + kap + '" data-scroll-dv="' + dvKey + '">' + dvHeadings[dvKey] + '</button>';
        });
        overlayTags.innerHTML = tagsHtml;

        // Source links
        var linksHtml = '';
        p.links.forEach(function(link) {
            linksHtml += '<a class="vk-overlay-link" href="' + link.url + '" target="_blank" rel="noopener">';
            linksHtml += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>';
            linksHtml += link.label;
            linksHtml += '</a>';
        });
        overlayLinks.innerHTML = linksHtml;

        overlay.classList.add('active');
        backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeOverlay() {
        overlay.classList.remove('active');
        backdrop.classList.remove('active');
        document.body.style.overflow = '';
    }

    document.addEventListener('click', function(e) {
        var item = e.target.closest('.vk-project-item');
        if (item) {
            var idx = parseInt(item.getAttribute('data-project-idx'), 10);
            openOverlay(idx);
            return;
        }
    });

    overlayClose.addEventListener('click', closeOverlay);
    backdrop.addEventListener('click', closeOverlay);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && overlay.classList.contains('active')) {
            closeOverlay();
        }
    });

    // Tag click -> scroll to accordion and open it
    document.addEventListener('click', function(e) {
        var tag = e.target.closest('.vk-overlay-tag[data-scroll-dv]');
        if (!tag) return;

        var dvKey = tag.getAttribute('data-scroll-dv');
        closeOverlay();

        setTimeout(function() {
            var accordion = document.querySelector('.vk-accordion[data-dv="' + dvKey + '"]');
            if (!accordion) return;

            // Open if closed
            if (!accordion.classList.contains('open')) {
                var toggle = accordion.querySelector('.vk-accordion-toggle');
                if (toggle) toggle.click();
            }

            // Scroll into view
            setTimeout(function() {
                accordion.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        }, 350);
    });

    // ─── Search ────────────────────────────────────────────────────────────

    var searchInput = document.getElementById('vk-search');
    var searchClear = document.getElementById('vk-search-clear');
    var noResults = document.getElementById('vk-no-results');

    function normalizeStr(s) {
        return s.toLowerCase()
            .replace(/[æ]/g, 'ae')
            .replace(/[ø]/g, 'o')
            .replace(/[å]/g, 'aa');
    }

    function doSearch() {
        var query = normalizeStr(searchInput.value.trim());

        // Toggle clear button
        if (query.length > 0) {
            searchClear.classList.add('visible');
        } else {
            searchClear.classList.remove('visible');
        }

        var allAccordions = document.querySelectorAll('.vk-accordion');
        var anyVisible = false;

        allAccordions.forEach(function(acc) {
            var dvKey = acc.getAttribute('data-dv');
            var headingMatch = query === '' || normalizeStr(dvHeadings[dvKey]).indexOf(query) !== -1;

            var projectItems = acc.querySelectorAll('.vk-project-item');
            var anyProjectVisible = false;

            projectItems.forEach(function(item) {
                var idx = parseInt(item.getAttribute('data-project-idx'), 10);
                var p = projects[idx];
                var projectMatch = query === '' ||
                    normalizeStr(p.id).indexOf(query) !== -1 ||
                    normalizeStr(p.org).indexOf(query) !== -1 ||
                    normalizeStr(p.desc).indexOf(query) !== -1;

                if (headingMatch || projectMatch) {
                    item.classList.remove('hidden-by-search');
                    anyProjectVisible = true;
                } else {
                    item.classList.add('hidden-by-search');
                }
            });

            if (headingMatch || anyProjectVisible) {
                acc.classList.remove('hidden-by-search');
                anyVisible = true;

                // Auto-open accordions when searching
                if (query.length > 0 && anyProjectVisible && !acc.classList.contains('open')) {
                    var content = acc.querySelector('.vk-accordion-content');
                    acc.classList.add('open');
                    content.style.maxHeight = 'none';
                }
            } else {
                acc.classList.remove('hidden-by-search');
                // Only hide if no match at all
                if (query.length > 0) {
                    acc.classList.add('hidden-by-search');
                }
            }
        });

        // Show/hide no results
        if (query.length > 0 && !anyVisible) {
            noResults.classList.add('visible');
        } else {
            noResults.classList.remove('visible');
        }
    }

    searchInput.addEventListener('input', doSearch);

    searchClear.addEventListener('click', function() {
        searchInput.value = '';
        doSearch();
        searchInput.focus();
    });

})();
</script>
