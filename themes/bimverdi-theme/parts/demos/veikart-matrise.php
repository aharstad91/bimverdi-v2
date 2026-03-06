<?php // Demo: Veikart-matrise ?>
<main class="veikart-matrise-demo" style="min-height:100vh;background:#FAFAF9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#1A1A1A;">

<style>
/* ── Reset & Base ── */
.veikart-matrise-demo * { box-sizing: border-box; margin: 0; padding: 0; }
.veikart-matrise-demo a { color: inherit; text-decoration: none; }

/* ── Layout ── */
.vm-container { max-width: 1400px; margin: 0 auto; padding: 24px 20px 64px; }
.vm-back { display: inline-flex; align-items: center; gap: 6px; font-size: 14px; color: #5A5A5A; margin-bottom: 24px; transition: color .2s; }
.vm-back:hover { color: #FF8B5E; }
.vm-back svg { width: 16px; height: 16px; }

/* ── Header ── */
.vm-header { margin-bottom: 24px; }
.vm-header h1 { font-size: 28px; font-weight: 700; line-height: 1.2; color: #1A1A1A; }
.vm-header p { font-size: 15px; color: #5A5A5A; margin-top: 6px; max-width: 640px; line-height: 1.5; }

/* ── Stats bar ── */
.vm-stats { display: flex; gap: 32px; margin-bottom: 20px; padding: 14px 0; border-top: 1px solid #E7E5E4; border-bottom: 1px solid #E7E5E4; }
.vm-stat { font-size: 14px; color: #5A5A5A; }
.vm-stat strong { color: #FF8B5E; font-weight: 700; font-size: 18px; margin-right: 4px; }

/* ── Search ── */
.vm-search-wrap { margin-bottom: 16px; position: relative; max-width: 360px; }
.vm-search-wrap svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #5A5A5A; pointer-events: none; }
.vm-search { width: 100%; padding: 10px 12px 10px 36px; border: 1px solid #E7E5E4; border-radius: 8px; font-size: 14px; background: #fff; outline: none; transition: border-color .2s; }
.vm-search:focus { border-color: #FF8B5E; }

/* ── Legend ── */
.vm-legend { display: flex; gap: 20px; margin-bottom: 16px; font-size: 13px; color: #5A5A5A; align-items: center; flex-wrap: wrap; }
.vm-legend-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: 5px; vertical-align: middle; }
.vm-legend-item { display: inline-flex; align-items: center; gap: 4px; }

/* ── Matrix wrapper ── */
.vm-matrix-wrap { overflow-x: auto; border: 1px solid #E7E5E4; border-radius: 10px; background: #fff; position: relative; }
.vm-matrix-wrap::-webkit-scrollbar { height: 8px; }
.vm-matrix-wrap::-webkit-scrollbar-track { background: #f0f0f0; border-radius: 0 0 10px 10px; }
.vm-matrix-wrap::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }

/* ── Table ── */
.vm-table { border-collapse: separate; border-spacing: 0; width: max-content; min-width: 100%; }
.vm-table th, .vm-table td { padding: 0; text-align: center; }

/* ── Header row ── */
.vm-table thead th { position: sticky; top: 0; z-index: 10; background: #fff; border-bottom: 2px solid #E7E5E4; }
.vm-table thead th.vm-col-corner { position: sticky; left: 0; z-index: 20; background: #fff; min-width: 260px; width: 260px; }

/* ── Column headers (rotated) ── */
.vm-col-header { width: 52px; min-width: 52px; height: 160px; vertical-align: bottom; padding-bottom: 10px !important; cursor: pointer; transition: background .15s; }
.vm-col-header:hover { background: #f5f5f4 !important; }
.vm-col-header.vm-col-dv4 { background: rgba(142,68,173,0.06); }
.vm-col-header.vm-col-dv6 { background: rgba(46,134,222,0.06); }
.vm-col-header.vm-col-active.vm-col-dv4 { background: rgba(142,68,173,0.15) !important; }
.vm-col-header.vm-col-active.vm-col-dv6 { background: rgba(46,134,222,0.15) !important; }

.vm-col-label { writing-mode: vertical-rl; transform: rotate(180deg); font-size: 12px; font-weight: 600; color: #1A1A1A; white-space: nowrap; display: inline-block; max-height: 140px; overflow: hidden; text-overflow: ellipsis; line-height: 1.3; }
.vm-col-dv4 .vm-col-label { color: #8E44AD; }
.vm-col-dv6 .vm-col-label { color: #2E86DE; }

/* ── Sticky first column ── */
.vm-row-label { position: sticky; left: 0; z-index: 5; background: inherit; min-width: 260px; width: 260px; text-align: left !important; padding: 8px 14px !important; border-right: 2px solid #E7E5E4; cursor: pointer; transition: background .15s; }
.vm-row-label:hover { background: #f9f8f6 !important; }

.vm-project-name { font-size: 13px; font-weight: 600; color: #1A1A1A; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 230px; display: block; }
.vm-project-org { font-size: 11px; color: #5A5A5A; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 230px; display: block; }

/* ── Data rows ── */
.vm-table tbody tr { transition: background .15s; }
.vm-table tbody tr:nth-child(even) { background: #FAFAF9; }
.vm-table tbody tr:nth-child(odd) { background: #fff; }
.vm-table tbody tr.vm-row-active { background: #FFF7ED !important; }
.vm-table tbody tr.vm-row-active .vm-row-label { background: #FFF7ED !important; }
.vm-table tbody tr.vm-row-hidden { display: none; }

/* ── Cells ── */
.vm-cell { width: 52px; min-width: 52px; height: 40px; padding: 0 !important; position: relative; border-bottom: 1px solid #f0eeea; }
.vm-cell.vm-col-highlight { background: rgba(255,139,94,0.06) !important; }

/* ── Dots ── */
.vm-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; cursor: pointer; transition: transform .15s, box-shadow .15s; position: relative; }
.vm-dot:hover { transform: scale(1.5); box-shadow: 0 0 0 3px rgba(0,0,0,0.08); }
.vm-dot-dv4 { background: #8E44AD; }
.vm-dot-dv6 { background: #2E86DE; }

/* ── Summary row ── */
.vm-table tfoot td { position: sticky; bottom: 0; background: #fff; border-top: 2px solid #E7E5E4; font-size: 13px; font-weight: 700; color: #5A5A5A; height: 40px; }
.vm-table tfoot td.vm-row-label { font-size: 13px; text-align: right !important; padding-right: 14px !important; color: #5A5A5A; }
.vm-summary-count { font-size: 14px; font-weight: 700; }
.vm-col-dv4-count { color: #8E44AD; }
.vm-col-dv6-count { color: #2E86DE; }

/* ── Tooltip ── */
.vm-tooltip { position: fixed; z-index: 100; background: #1A1A1A; color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 12px; line-height: 1.4; max-width: 280px; pointer-events: none; opacity: 0; transition: opacity .15s; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.vm-tooltip.vm-tooltip-visible { opacity: 1; }
.vm-tooltip-project { font-weight: 600; }
.vm-tooltip-heading { color: #ccc; margin-top: 2px; }

/* ── Detail card ── */
.vm-detail { margin-top: 20px; background: #fff; border: 1px solid #E7E5E4; border-radius: 10px; padding: 24px; display: none; animation: vm-slideIn .25s ease; }
.vm-detail.vm-detail-visible { display: block; }
@keyframes vm-slideIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.vm-detail-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; gap: 16px; }
.vm-detail-title { font-size: 18px; font-weight: 700; }
.vm-detail-org { font-size: 14px; color: #5A5A5A; margin-top: 2px; }
.vm-detail-close { background: none; border: 1px solid #E7E5E4; border-radius: 6px; padding: 6px 12px; font-size: 13px; cursor: pointer; color: #5A5A5A; transition: border-color .2s, color .2s; white-space: nowrap; }
.vm-detail-close:hover { border-color: #FF8B5E; color: #FF8B5E; }
.vm-detail-desc { font-size: 14px; color: #5A5A5A; line-height: 1.5; margin-bottom: 16px; }
.vm-detail-section { margin-bottom: 12px; }
.vm-detail-section-title { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #888; margin-bottom: 6px; }
.vm-detail-tags { display: flex; flex-wrap: wrap; gap: 6px; }
.vm-detail-tag { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; }
.vm-detail-tag-dv4 { background: rgba(142,68,173,0.1); color: #8E44AD; }
.vm-detail-tag-dv6 { background: rgba(46,134,222,0.1); color: #2E86DE; }
.vm-detail-links { display: flex; flex-wrap: wrap; gap: 8px; }
.vm-detail-link { display: inline-flex; align-items: center; gap: 4px; padding: 5px 12px; border: 1px solid #E7E5E4; border-radius: 6px; font-size: 13px; color: #5A5A5A; transition: border-color .2s, color .2s; }
.vm-detail-link:hover { border-color: #FF8B5E; color: #FF8B5E; }
.vm-detail-link svg { width: 14px; height: 14px; }

/* ── Column detail ── */
.vm-col-detail-title { display: flex; align-items: center; gap: 8px; }
.vm-col-detail-badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.vm-col-detail-badge-dv4 { background: rgba(142,68,173,0.1); color: #8E44AD; }
.vm-col-detail-badge-dv6 { background: rgba(46,134,222,0.1); color: #2E86DE; }
.vm-col-detail-list { list-style: none; margin-top: 8px; }
.vm-col-detail-list li { font-size: 14px; padding: 6px 0; border-bottom: 1px solid #f0eeea; color: #1A1A1A; }
.vm-col-detail-list li:last-child { border-bottom: none; }
.vm-col-detail-list li span { color: #5A5A5A; font-size: 12px; margin-left: 6px; }

/* ── Responsive ── */
@media (max-width: 768px) {
    .vm-container { padding: 16px 12px 48px; }
    .vm-header h1 { font-size: 22px; }
    .vm-stats { gap: 16px; flex-wrap: wrap; }
    .vm-row-label { min-width: 180px; width: 180px; }
    .vm-project-name, .vm-project-org { max-width: 160px; }
    .vm-col-header { width: 44px; min-width: 44px; height: 130px; }
    .vm-cell { width: 44px; min-width: 44px; }
    .vm-detail { padding: 16px; }
}
</style>

<div class="vm-container">

    <!-- Back link -->
    <a href="/bimverdi-v2/demo/" class="vm-back">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        Tilbake til demo
    </a>

    <!-- Header -->
    <div class="vm-header">
        <h1>Veikart-matrise</h1>
        <p>Hvilke prosjekter jobber mot hvilke maal i Digitalt Veikart 2.0?</p>
    </div>

    <!-- Stats -->
    <div class="vm-stats" id="vmStats"></div>

    <!-- Search -->
    <div class="vm-search-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="text" class="vm-search" id="vmSearch" placeholder="Sok i prosjekter...">
    </div>

    <!-- Legend -->
    <div class="vm-legend">
        <span class="vm-legend-item"><span class="vm-legend-dot" style="background:#8E44AD;"></span> DV Kap 4 &ndash; Rammeverk</span>
        <span class="vm-legend-item"><span class="vm-legend-dot" style="background:#2E86DE;"></span> DV Kap 6 &ndash; Teknologi</span>
    </div>

    <!-- Matrix -->
    <div class="vm-matrix-wrap" id="vmMatrixWrap">
        <table class="vm-table" id="vmTable">
            <thead id="vmThead"></thead>
            <tbody id="vmTbody"></tbody>
            <tfoot id="vmTfoot"></tfoot>
        </table>
    </div>

    <!-- Detail card -->
    <div class="vm-detail" id="vmDetail"></div>

    <!-- Tooltip -->
    <div class="vm-tooltip" id="vmTooltip"></div>

</div>

<script>
(function() {
    'use strict';

    // ── DATA ──

    const dvHeadings = [
        { id: '4.1', label: '4.1 Info.forvaltning', full: 'DV 4.1 Felles rammeverk for informasjonsforvaltning', chapter: 4 },
        { id: '4.2', label: '4.2 Intl. rammer', full: 'DV 4.2 Internasjonale rammer', chapter: 4 },
        { id: '4.3', label: '4.3 Nasj. rammer', full: 'DV 4.3 Nasjonale rammer', chapter: 4 },
        { id: '4.4', label: '4.4 Spesifikasjoner', full: 'DV 4.4 Felles spesifikasjoner og komponenter', chapter: 4 },
        { id: '4.5', label: '4.5 API-standarder', full: 'DV 4.5 Etablere standarder for API', chapter: 4 },
        { id: '4.6', label: '4.6 Sluttbrukerlosn.', full: 'DV 4.6 Sluttbrukerlosninger i markedet', chapter: 4 },
        { id: '4.7', label: '4.7 Forvaltning', full: 'DV 4.7 Forvaltning av felles rammeverk', chapter: 4 },
        { id: '6.1', label: '6.1 AI/ML', full: 'DV 6.1 Kunstig intelligens / maskinlaering', chapter: 6 },
        { id: '6.2', label: '6.2 Algoritmer', full: 'DV 6.2 Algoritmer', chapter: 6 },
        { id: '6.3', label: '6.3 Sensorteknologi', full: 'DV 6.3 Sensorteknologi', chapter: 6 },
        { id: '6.4', label: '6.4 RFiD', full: 'DV 6.4 RFiD', chapter: 6 },
        { id: '6.5', label: '6.5 VR', full: 'DV 6.5 Virtual Reality (VR)', chapter: 6 },
        { id: '6.6', label: '6.6 Roboter', full: 'DV 6.6 Roboter', chapter: 6 },
        { id: '6.7', label: '6.7 3D-printing', full: 'DV 6.7 3D-printing', chapter: 6 },
        { id: '6.8', label: '6.8 Droner', full: 'DV 6.8 Droner', chapter: 6 }
    ];

    const projects = [
        { id: 'POFIN', org: 'buildingSMART Norge', desc: 'buildingSMART Norges rammeverk for Project and Facilities in Norway.', links: [{ label: 'POFIN - buildingSMART', url: 'https://buildingsmart.no/pofin' }] },
        { id: 'PDT Norge', org: 'Cobuilder / BNL', desc: 'Offisiell distribusjonsplattform for produktdatamaler.', links: [{ label: 'PDT Norge - Cobuilder', url: 'https://cobuilder.com/nb/pdt-norge-en-offisiell-distribusjonsplattform-for-produktdatamaler/' }] },
        { id: 'IFC 4.3 ISO-standard', org: 'buildingSMART International', desc: 'IFC 4.3 vedtatt som ISO 16739-1:2024.', links: [{ label: 'IFC 4.3 ISO', url: 'https://buildingsmart.no/nyheter/ifc-iso' }] },
        { id: 'Statsbygg BIM-manual 2.0', org: 'Statsbygg', desc: 'Krav til BIM i alle Statsbygg-prosjekter.', links: [{ label: 'SBM2', url: 'https://arkiv.buildingsmart.no/nyhetsbrev/2019-11/statsbyggs-bim-manual-20-sbm2-et-konkret-eksempel-pa-hvordan-apne-standarder-kan' }] },
        { id: 'BIM for alle (Forsvarsbygg)', org: 'Forsvarsbygg', desc: 'Digitalisering av 4 mill. kvm paa openBIM.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/bim/sintef-og-buildingsmart-norge-inngar-samarbeidsavtale/1747497' }] },
        { id: 'boligBIM-prosjektet', org: 'Boligprodusentene / bSN', desc: 'Standardisering av BIM-praksis for boligbygging.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/bolig-boligbygging/full-fart-i-boligbim-prosjektet/2069485' }] },
        { id: 'ISO 19650-serien (norsk)', org: 'Standard Norge', desc: 'NS-EN ISO 19650 oversatt til norsk.', links: [{ label: 'Standard Norge', url: 'https://standard.no/fagomrader/bygg-anlegg-og-eiendom/digital-byggeprosess/iso-19650-serien/' }] },
        { id: 'Digibygg (Statsbygg)', org: 'Statsbygg', desc: 'Digitalisering og smart teknologi i Statsbyggs byggeprosjekter.', links: [{ label: 'Statsbygg', url: 'https://www.statsbygg.no/nyheter/digitale-tvillinger-i-2023' }] },
        { id: 'Statsbygg droneinspeksjon', org: 'Statsbygg', desc: 'Inspeksjon av tak og fasader med droner og AI.', links: [{ label: 'Byggfakta', url: 'https://nyheter.byggfakta.no/statsbygg-inngar-avtaler-om-droneinspeksjon-175538/nyhet.html' }] },
        { id: 'SINTEF AI i bygg', org: 'SINTEF', desc: 'Forskning paa AI i byggenaeringen.', links: [{ label: 'SINTEF', url: 'https://www.sintef.no/en/sintef-research-areas/artificial-intelligence/great-effect-using-ai-in-the-construction-industry/' }] },
        { id: 'Veidekke 3D-print betong', org: 'Veidekke', desc: 'Tester 3D-printing av betong.', links: [{ label: 'Digital Norway', url: 'https://digitalnorway.com/aktuelt/dette-er-tech-trendene-som-vil-prege-2026' }] },
        { id: 'Vegvesen bro-inspeksjon', org: 'Statens vegvesen', desc: 'Dronebilder og AI for bro-inspeksjon.', links: [{ label: 'norskbyggebransje.no', url: 'https://norskbyggebransje.no/ai/bygg-og-anlegg-i-2026' }] },
        { id: 'Digital Product Passport (DPP)', org: 'Cobuilder / EU', desc: 'Digitale produktpass fra 2026-2029.', links: [{ label: 'Cobuilder', url: 'https://cobuilder.com/en/digital-product-passport-dpp/digital-product-passports-for-construction-what-the-eus-2026-2029-cpr-working-plan-means-for-stakeholders/' }] },
        { id: 'Prosjekt Norge digitaliseringsmaaling', org: 'Prosjekt Norge / NTNU', desc: 'Maaling av effekter av digitalisering.', links: [{ label: 'Prosjekt Norge', url: 'https://www.prosjektnorge.no/maling-av-effekter-av-digitalisering-i-den-norske-byggenaeringen/' }] },
        { id: 'Nordic BIM Digital Twin', org: 'Nordic BIM Group', desc: 'Digitale tvillinger for bygg- og eiendomsbransjen.', links: [{ label: 'Nordic BIM', url: 'https://www.nordicbim.com/no/digital-tvilling' }] },
        { id: 'VA-objekter (Vann og avlop)', org: 'buildingSMART Norge', desc: 'Felles retningslinjer for VA med BIM.', links: [{ label: 'buildingSMART', url: 'https://buildingsmart.no/' }] },
        { id: 'Spacemaker / Autodesk Forma', org: 'Autodesk (tidl. Spacemaker)', desc: 'AI-plattform for tidligfase arealplanlegging.', links: [{ label: 'NTI', url: 'https://www.nti-group.com/no/produkter/autodesk-software/forma/' }] },
        { id: 'Catenda Hub (BIMsync)', org: 'Catenda', desc: 'Norsk openBIM-plattform for samhandling.', links: [{ label: 'Catenda', url: 'https://catenda.com/' }] },
        { id: 'Novorender', org: 'Novorender (Stavanger)', desc: '3D/BIM-viewer som visualiserer >100 GB i nettleseren.', links: [{ label: 'Novorender', url: 'https://novorender.com/' }] },
        { id: 'Dimension10 VR', org: 'Dimension10 / Catenda', desc: 'VR-plattform for byggebransjen.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/bim-teknologi/norske-vr-og-bim-selskap-inngar-samarbeid/2365941' }] },
        { id: 'Nordisk BIM-samarbeid (infra)', org: 'SVV / Nye Veier / Bane NOR', desc: 'Nordisk samarbeid om openBIM i infra.', links: [{ label: 'Nye Veier', url: 'https://www.nyeveier.no/nyheter/nyheter/statens-vegvesen-nye-veier-og-bane-nor-inngar-nordisk-bim-samarbeid/' }] },
        { id: 'Fellestjenester BYGG (DiBK)', org: 'DiBK', desc: 'Altinn-basert plattform for digitale byggesoknader.', links: [{ label: 'DiBK', url: 'https://www.dibk.no/verktoy-og-veivisere/andre-fagomrader/fellestjenester-bygg' }] },
        { id: 'Airthings inneklima-sensorer', org: 'Airthings', desc: '500 sensorer styrer 20 aar gammelt bygg.', links: [{ label: 'Estate Vest', url: 'https://www.estatevest.no/500-airthings-sensorer-styrer-20-ar-gammelt-bygg/' }] },
        { id: 'Disruptive Technologies sensorer', org: 'Disruptive Technologies', desc: 'Verdens minste tradlose sensorer.', links: [{ label: 'Digital Norway', url: 'https://digitalnorway.com/proptech-bergen/' }] },
        { id: 'Statsbygg + GS1 RFID-pilot', org: 'Statsbygg / GS1 Norge', desc: 'RFID-sporing av byggevarer paa byggeplass.', links: [{ label: 'ITBaktuelt', url: 'https://www.itbaktuelt.no/2017/09/12/rfdi-teknologi-statsbygg-digitalisering-173/' }] },
        { id: 'Dalux Field', org: 'Dalux', desc: 'Digital byggeplass med BIM og AR.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/af-gruppen-ikt/af-gruppen-utvider-konsernavtale-med-dalux/2190158' }] },
        { id: 'nLink borrerbot', org: 'nLink', desc: 'Mobile roboter for byggeplass.', links: [{ label: 'nLink', url: 'https://nlink.no/' }] },
        { id: 'Moelven Byggmodul', org: 'Moelven', desc: 'Industrialisert modulbygging i tre.', links: [{ label: 'Moelven', url: 'https://www.moelven.com/no/produkter-og-tjenester/modulbygg/' }] },
        { id: 'Autility Twin (FDV)', org: 'Autility', desc: 'Digital tvilling for drift og vedlikehold.', links: [{ label: 'norskbyggebransje.no', url: 'https://norskbyggebransje.no/nyheter/digital-tvilling-optimaliserer-drift-og-vedlikehold' }] },
        { id: 'Spot robot paa byggeplass', org: 'Diverse entreprenorer', desc: 'Boston Dynamics Spot for autonom inspeksjon.', links: [{ label: 'Tekna', url: 'https://www.tekna.no/fag-og-nettverk/bygg-og-anlegg/byggbloggen/roboter-pa-byggeplass/' }] },
        { id: 'Fornebubanen BIM', org: 'Fornebubanen / Oslo', desc: 'En av verdens storste BIM-modeller.', links: [{ label: 'Novorender', url: 'https://novorender.com/hvordan-novorender-hjelper-radgiver-og-entreprenor-mote-bim-krav-i-store-norske-infrastrukturprosjekter' }] },
        { id: 'OsloMet digital tvilling energi', org: 'OsloMet', desc: 'Digital tvilling for energieffektive bygg.', links: [{ label: 'OsloMet', url: 'https://www.oslomet.no/forskning/forskningsnyheter/hvordan-spare-mer-energi-i-bygg-digital-tvilling' }] },
        { id: 'Norconsult VR/AR-tjenester', org: 'Norconsult', desc: 'VR og AR for prosjektering og visualisering.', links: [{ label: 'Norconsult', url: 'https://norconsult.no/tjenester/digitalisering/vrar/' }] },
        { id: 'Imerso (3D-skanning + AI)', org: 'Imerso', desc: '3D-skanning og BIM for fremdriftskontroll.', links: [{ label: 'tu.no', url: 'https://www.tu.no/artikler/snart-blir-laserskannere-sa-billige-at-alle-byggeplasser-vil-skannes-24-7-br/458842' }] },
        { id: 'StreamBIM (Rendra)', org: 'Rendra / JDM Technology', desc: 'Skybasert BIM-viewer med streaming-teknologi.', links: [{ label: 'StreamBIM', url: 'https://streambim.com/' }] },
        { id: 'bSDD (Data Dictionary)', org: 'buildingSMART International', desc: 'Internasjonal dataordbok for klassifikasjoner.', links: [{ label: 'buildingSMART', url: 'https://www.buildingsmart.org/users/services/buildingsmart-data-dictionary/' }] },
        { id: 'BREEAM-NOR (miljoesertifisering)', org: 'Gronn Byggallianse', desc: 'Miljoesertifisering for bygg.', links: [{ label: 'Byggalliansen', url: 'https://byggalliansen.no/sertifisering/om-breeam/' }] },
        { id: 'Statsbygg 3D-printet bygg', org: 'Statsbygg', desc: 'Norges forste 3D-printede bygg.', links: [{ label: 'Statsbygg', url: 'https://www.statsbygg.no/nyheter/statsbygg-skal-lage-norges-forste-3d-printede-bygg' }] },
        { id: 'SIMBA modellsjekk (NTI/Statsbygg)', org: 'NTI / Statsbygg', desc: 'Automatisk skybasert kontroll av BIM-modeller.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/kommer-med-automatisk-kontroll-av-statsbyggs-bim-krav/1446769!/' }] },
        { id: 'Digital byggesoeknad med BIM (DiBK)', org: 'DiBK', desc: 'BIM som dokumentasjon i byggesoknader.', links: [{ label: 'DiBK', url: 'https://www.dibk.no/soknad-og-skjema/vil-du-bruke-bim-i-byggesoknaden' }] },
        { id: 'Oslobygg kommunal eiendom', org: 'Oslo kommune', desc: 'Forvalter ca. 1800 kommunale bygg i Oslo.', links: [{ label: 'Oslo kommune', url: 'https://www.oslo.kommune.no/etater-foretak-og-ombud/oslobygg-kf/' }] },
        { id: 'MIL 3D-printing betong (Grimstad)', org: 'Mechatronics Innovation Lab', desc: 'Norges forste robotiserte betong-3D-printer.', links: [{ label: 'MIL', url: 'https://mil-as.no/index.php/2024/01/25/3d-printe-betong/' }] },
        { id: 'Digital Product Passport EU (CPR)', org: 'EU-kommisjonen', desc: 'EUs nye byggevareforordning med digitale produktpass.', links: [{ label: 'Cobuilder', url: 'https://cobuilder.com/en/digital-product-passport-dpp/digital-product-passports-for-construction-what-the-eus-2026-2029-cpr-working-plan-means-for-stakeholders/' }] },
        { id: 'Sweco 3D-skanning FDV', org: 'Sweco', desc: '3D-skannere for drift og vedlikehold.', links: [{ label: 'Sweco', url: 'https://www.sweco.no/aktuelt/nyheter/3d-scanner-for-effektiv-drift-og-vedlikehold-av-bygningsmasser/' }] }
    ];

    // Links: project id → array of DV heading ids
    const connections = {
        'POFIN': ['4.1', '4.3', '4.7'],
        'PDT Norge': ['4.4', '4.5'],
        'IFC 4.3 ISO-standard': ['4.2', '4.4', '4.5'],
        'Statsbygg BIM-manual 2.0': ['4.1', '4.3'],
        'BIM for alle (Forsvarsbygg)': ['4.6', '4.1'],
        'boligBIM-prosjektet': ['4.4', '4.6'],
        'ISO 19650-serien (norsk)': ['4.1', '4.2'],
        'Digibygg (Statsbygg)': ['6.1', '6.3', '6.8', '4.6'],
        'Statsbygg droneinspeksjon': ['6.8', '6.1'],
        'SINTEF AI i bygg': ['6.1', '6.2'],
        'Veidekke 3D-print betong': ['6.7', '6.6'],
        'Vegvesen bro-inspeksjon': ['6.8', '6.1', '6.2'],
        'Digital Product Passport (DPP)': ['4.4', '4.2', '4.5'],
        'Prosjekt Norge digitaliseringsmaaling': ['4.7', '4.1'],
        'Nordic BIM Digital Twin': ['6.3', '6.5'],
        'VA-objekter (Vann og avlop)': ['4.4', '4.3'],
        'Spacemaker / Autodesk Forma': ['6.1', '6.2', '4.6'],
        'Catenda Hub (BIMsync)': ['4.1', '4.5', '4.6'],
        'Novorender': ['4.6', '4.5', '6.5'],
        'Dimension10 VR': ['6.5', '4.6'],
        'Nordisk BIM-samarbeid (infra)': ['4.2', '4.3', '4.1'],
        'Fellestjenester BYGG (DiBK)': ['4.3', '4.5', '4.7'],
        'Airthings inneklima-sensorer': ['6.3', '6.1'],
        'Disruptive Technologies sensorer': ['6.3', '6.4'],
        'Statsbygg + GS1 RFID-pilot': ['6.4', '4.4'],
        'Dalux Field': ['6.5', '4.6'],
        'nLink borrerbot': ['6.6', '6.2'],
        'Moelven Byggmodul': ['6.6', '6.7'],
        'Autility Twin (FDV)': ['6.3', '4.4', '4.1'],
        'Spot robot paa byggeplass': ['6.6', '6.1'],
        'Fornebubanen BIM': ['4.6', '4.1'],
        'OsloMet digital tvilling energi': ['6.3', '6.1'],
        'Norconsult VR/AR-tjenester': ['6.5', '4.6'],
        'Imerso (3D-skanning + AI)': ['6.1', '6.2', '6.3', '4.6'],
        'StreamBIM (Rendra)': ['4.6', '4.5', '4.1'],
        'bSDD (Data Dictionary)': ['4.2', '4.4', '4.5'],
        'BREEAM-NOR (miljoesertifisering)': ['4.3', '4.4'],
        'Statsbygg 3D-printet bygg': ['6.7', '6.6'],
        'SIMBA modellsjekk (NTI/Statsbygg)': ['6.2', '4.6', '4.3'],
        'Digital byggesoeknad med BIM (DiBK)': ['4.3', '4.5', '6.2'],
        'Oslobygg kommunal eiendom': ['4.1', '6.3', '4.7'],
        'MIL 3D-printing betong (Grimstad)': ['6.7', '6.6'],
        'Digital Product Passport EU (CPR)': ['4.2', '4.4'],
        'Sweco 3D-skanning FDV': ['6.3', '4.1']
    };

    // ── Sort projects by connection count (most first) ──
    const sortedProjects = [...projects].sort((a, b) => {
        const ca = (connections[a.id] || []).length;
        const cb = (connections[b.id] || []).length;
        return cb - ca;
    });

    // ── Compute stats ──
    const totalConnections = Object.values(connections).reduce((s, arr) => s + arr.length, 0);
    const totalNodes = projects.length + dvHeadings.length;

    // ── Render stats ──
    document.getElementById('vmStats').innerHTML =
        '<div class="vm-stat"><strong>' + totalNodes + '</strong> noder</div>' +
        '<div class="vm-stat"><strong>' + totalConnections + '</strong> koblinger</div>' +
        '<div class="vm-stat"><strong>' + projects.length + '</strong> prosjekter</div>' +
        '<div class="vm-stat"><strong>' + dvHeadings.length + '</strong> DV-maal</div>';

    // ── Build table header ──
    var theadHTML = '<tr><th class="vm-col-corner"></th>';
    dvHeadings.forEach(function(h) {
        var cls = h.chapter === 4 ? 'vm-col-dv4' : 'vm-col-dv6';
        theadHTML += '<th class="vm-col-header ' + cls + '" data-dv="' + h.id + '" title="' + h.full + '">';
        theadHTML += '<span class="vm-col-label">' + h.label + '</span>';
        theadHTML += '</th>';
    });
    theadHTML += '</tr>';
    document.getElementById('vmThead').innerHTML = theadHTML;

    // ── Build table body ──
    var tbodyHTML = '';
    sortedProjects.forEach(function(p) {
        var conns = connections[p.id] || [];
        tbodyHTML += '<tr data-project="' + p.id + '">';
        tbodyHTML += '<td class="vm-row-label" data-project="' + p.id + '">';
        tbodyHTML += '<span class="vm-project-name">' + p.id + '</span>';
        tbodyHTML += '<span class="vm-project-org">' + p.org + '</span>';
        tbodyHTML += '</td>';
        dvHeadings.forEach(function(h) {
            var hasConn = conns.indexOf(h.id) !== -1;
            var chapterCls = h.chapter === 4 ? 'dv4' : 'dv6';
            tbodyHTML += '<td class="vm-cell" data-dv="' + h.id + '" data-project="' + p.id + '">';
            if (hasConn) {
                tbodyHTML += '<span class="vm-dot vm-dot-' + chapterCls + '" data-dv="' + h.id + '" data-project="' + p.id + '"></span>';
            }
            tbodyHTML += '</td>';
        });
        tbodyHTML += '</tr>';
    });
    document.getElementById('vmTbody').innerHTML = tbodyHTML;

    // ── Build summary footer ──
    var tfootHTML = '<tr><td class="vm-row-label">Antall prosjekter</td>';
    dvHeadings.forEach(function(h) {
        var count = 0;
        sortedProjects.forEach(function(p) {
            var conns = connections[p.id] || [];
            if (conns.indexOf(h.id) !== -1) count++;
        });
        var cls = h.chapter === 4 ? 'vm-col-dv4-count' : 'vm-col-dv6-count';
        tfootHTML += '<td class="vm-cell"><span class="vm-summary-count ' + cls + '">' + count + '</span></td>';
    });
    tfootHTML += '</tr>';
    document.getElementById('vmTfoot').innerHTML = tfootHTML;

    // ── State ──
    var activeRow = null;
    var activeCol = null;
    var tooltip = document.getElementById('vmTooltip');
    var detail = document.getElementById('vmDetail');

    // ── Tooltip ──
    document.getElementById('vmTbody').addEventListener('mouseover', function(e) {
        var dot = e.target.closest('.vm-dot');
        if (!dot) return;
        var pid = dot.getAttribute('data-project');
        var dvId = dot.getAttribute('data-dv');
        var heading = dvHeadings.find(function(h) { return h.id === dvId; });
        tooltip.innerHTML = '<div class="vm-tooltip-project">' + pid + '</div><div class="vm-tooltip-heading">' + (heading ? heading.full : dvId) + '</div>';
        tooltip.classList.add('vm-tooltip-visible');
    });

    document.getElementById('vmTbody').addEventListener('mouseout', function(e) {
        var dot = e.target.closest('.vm-dot');
        if (!dot) return;
        tooltip.classList.remove('vm-tooltip-visible');
    });

    document.addEventListener('mousemove', function(e) {
        if (tooltip.classList.contains('vm-tooltip-visible')) {
            tooltip.style.left = (e.clientX + 12) + 'px';
            tooltip.style.top = (e.clientY - 10) + 'px';
        }
    });

    // ── Row click (project detail) ──
    document.getElementById('vmTbody').addEventListener('click', function(e) {
        var row = e.target.closest('tr[data-project]');
        if (!row) return;
        var pid = row.getAttribute('data-project');

        // Clear column highlight
        clearColHighlight();
        activeCol = null;

        // Toggle active row
        if (activeRow === pid) {
            clearRowHighlight();
            activeRow = null;
            detail.classList.remove('vm-detail-visible');
            return;
        }

        clearRowHighlight();
        activeRow = pid;
        row.classList.add('vm-row-active');

        // Show detail
        var proj = projects.find(function(p) { return p.id === pid; });
        var conns = connections[pid] || [];

        var html = '<div class="vm-detail-header">';
        html += '<div><div class="vm-detail-title">' + proj.id + '</div>';
        html += '<div class="vm-detail-org">' + proj.org + '</div></div>';
        html += '<button class="vm-detail-close" id="vmDetailClose">Lukk</button>';
        html += '</div>';
        html += '<div class="vm-detail-desc">' + proj.desc + '</div>';

        html += '<div class="vm-detail-section"><div class="vm-detail-section-title">Koblinger til Digitalt Veikart 2.0</div><div class="vm-detail-tags">';
        conns.forEach(function(dvId) {
            var heading = dvHeadings.find(function(h) { return h.id === dvId; });
            var cls = dvId.startsWith('4') ? 'vm-detail-tag-dv4' : 'vm-detail-tag-dv6';
            html += '<span class="vm-detail-tag ' + cls + '">' + (heading ? heading.full : dvId) + '</span>';
        });
        html += '</div></div>';

        if (proj.links && proj.links.length > 0) {
            html += '<div class="vm-detail-section"><div class="vm-detail-section-title">Kilder</div><div class="vm-detail-links">';
            proj.links.forEach(function(link) {
                html += '<a href="' + link.url + '" target="_blank" rel="noopener" class="vm-detail-link">';
                html += '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>';
                html += link.label + '</a>';
            });
            html += '</div></div>';
        }

        detail.innerHTML = html;
        detail.classList.add('vm-detail-visible');

        // Close button
        document.getElementById('vmDetailClose').addEventListener('click', function(ev) {
            ev.stopPropagation();
            clearRowHighlight();
            activeRow = null;
            detail.classList.remove('vm-detail-visible');
        });
    });

    // ── Column header click ──
    document.getElementById('vmThead').addEventListener('click', function(e) {
        var th = e.target.closest('.vm-col-header');
        if (!th) return;
        var dvId = th.getAttribute('data-dv');

        // Clear row highlight
        clearRowHighlight();
        activeRow = null;

        // Toggle column
        if (activeCol === dvId) {
            clearColHighlight();
            activeCol = null;
            detail.classList.remove('vm-detail-visible');
            return;
        }

        clearColHighlight();
        activeCol = dvId;

        // Highlight column header
        th.classList.add('vm-col-active');

        // Highlight column cells
        var cells = document.querySelectorAll('.vm-cell[data-dv="' + dvId + '"]');
        cells.forEach(function(cell) { cell.classList.add('vm-col-highlight'); });

        // Show detail with projects for this heading
        var heading = dvHeadings.find(function(h) { return h.id === dvId; });
        var chapterCls = dvId.startsWith('4') ? 'dv4' : 'dv6';
        var connectedProjects = sortedProjects.filter(function(p) {
            var conns = connections[p.id] || [];
            return conns.indexOf(dvId) !== -1;
        });

        var html = '<div class="vm-detail-header">';
        html += '<div class="vm-col-detail-title"><span class="vm-col-detail-badge vm-col-detail-badge-' + chapterCls + '">Kap ' + (dvId.startsWith('4') ? '4' : '6') + '</span>';
        html += '<span class="vm-detail-title">' + (heading ? heading.full : dvId) + '</span></div>';
        html += '<button class="vm-detail-close" id="vmDetailClose">Lukk</button>';
        html += '</div>';
        html += '<div class="vm-detail-desc">' + connectedProjects.length + ' prosjekter kobles til dette maalet:</div>';
        html += '<ul class="vm-col-detail-list">';
        connectedProjects.forEach(function(p) {
            html += '<li>' + p.id + ' <span>' + p.org + '</span></li>';
        });
        html += '</ul>';

        detail.innerHTML = html;
        detail.classList.add('vm-detail-visible');

        document.getElementById('vmDetailClose').addEventListener('click', function(ev) {
            ev.stopPropagation();
            clearColHighlight();
            activeCol = null;
            detail.classList.remove('vm-detail-visible');
        });
    });

    // ── Search ──
    document.getElementById('vmSearch').addEventListener('input', function() {
        var query = this.value.toLowerCase().trim();
        var rows = document.querySelectorAll('#vmTbody tr[data-project]');
        rows.forEach(function(row) {
            var pid = row.getAttribute('data-project').toLowerCase();
            var proj = projects.find(function(p) { return p.id === row.getAttribute('data-project'); });
            var org = proj ? proj.org.toLowerCase() : '';
            if (!query || pid.indexOf(query) !== -1 || org.indexOf(query) !== -1) {
                row.classList.remove('vm-row-hidden');
            } else {
                row.classList.add('vm-row-hidden');
            }
        });
    });

    // ── Helpers ──
    function clearRowHighlight() {
        var active = document.querySelectorAll('.vm-row-active');
        active.forEach(function(el) { el.classList.remove('vm-row-active'); });
    }

    function clearColHighlight() {
        var headers = document.querySelectorAll('.vm-col-active');
        headers.forEach(function(el) { el.classList.remove('vm-col-active'); });
        var cells = document.querySelectorAll('.vm-col-highlight');
        cells.forEach(function(el) { el.classList.remove('vm-col-highlight'); });
    }

})();
</script>

</main>
