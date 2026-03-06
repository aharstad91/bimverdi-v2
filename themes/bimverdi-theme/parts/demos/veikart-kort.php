<?php // Demo: Veikart-kort ?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veikart-kort | BIM Verdi</title>
    <style>
        :root {
            --color-primary: #FF8B5E;
            --bg: #FAFAF9;
            --text: #1A1A1A;
            --text-secondary: #5A5A5A;
            --border: #E7E5E4;
            --dv4: #8E44AD;
            --dv6: #2E86DE;
            --project: #27AE60;
            --dv4-light: #F3E8F9;
            --dv6-light: #E8F1FB;
            --sidebar-w: 320px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }

        /* Top bar */
        .top-bar {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
        }
        .top-bar a {
            color: var(--color-primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .top-bar a:hover { text-decoration: underline; }

        /* Header */
        .page-header {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 32px 32px 24px;
        }
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 4px;
        }
        .page-header p {
            font-size: 15px;
            color: var(--text-secondary);
            max-width: 640px;
        }

        /* Stats bar */
        .stats-bar {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 12px 32px;
            display: flex;
            gap: 32px;
            flex-wrap: wrap;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-secondary);
        }
        .stat-value {
            font-weight: 700;
            font-size: 18px;
            color: var(--text);
        }
        .stat-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        .stat-dot--project { background: var(--project); }
        .stat-dot--link { background: var(--color-primary); }
        .stat-dot--dv { background: var(--dv4); }

        /* Main layout */
        .main-layout {
            display: flex;
            min-height: calc(100vh - 200px);
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-w);
            min-width: var(--sidebar-w);
            background: #fff;
            border-right: 1px solid var(--border);
            padding: 16px 0;
            overflow-y: auto;
            position: sticky;
            top: 0;
            height: calc(100vh - 200px);
        }
        .sidebar-section-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-secondary);
            padding: 16px 20px 8px;
        }
        .sidebar-section-label:first-child { padding-top: 8px; }

        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 13px;
            color: var(--text);
            border-left: 3px solid transparent;
            transition: all 0.15s ease;
            user-select: none;
        }
        .sidebar-item:hover {
            background: #F7F6F4;
        }
        .sidebar-item.active {
            background: #F7F6F4;
            font-weight: 600;
        }
        .sidebar-item.active[data-chapter="4"] {
            border-left-color: var(--dv4);
        }
        .sidebar-item.active[data-chapter="6"] {
            border-left-color: var(--dv6);
        }
        .sidebar-item.active[data-chapter="all"] {
            border-left-color: var(--color-primary);
        }

        .sidebar-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .sidebar-dot--4 { background: var(--dv4); }
        .sidebar-dot--6 { background: var(--dv6); }
        .sidebar-dot--all { background: var(--color-primary); }

        .sidebar-text {
            flex: 1;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 10px;
            flex-shrink: 0;
        }
        .sidebar-badge--4 {
            background: var(--dv4-light);
            color: var(--dv4);
        }
        .sidebar-badge--6 {
            background: var(--dv6-light);
            color: var(--dv6);
        }
        .sidebar-badge--all {
            background: #FFF3ED;
            color: var(--color-primary);
        }

        .sidebar-divider {
            height: 1px;
            background: var(--border);
            margin: 8px 20px;
        }

        /* Content area */
        .content-area {
            flex: 1;
            padding: 24px 32px;
            min-width: 0;
        }

        /* Search */
        .search-box {
            position: relative;
            margin-bottom: 24px;
            max-width: 480px;
        }
        .search-box input {
            width: 100%;
            padding: 10px 16px 10px 40px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            background: #fff;
            color: var(--text);
            outline: none;
            transition: border-color 0.15s;
        }
        .search-box input:focus {
            border-color: var(--color-primary);
        }
        .search-box input::placeholder {
            color: #A8A29E;
        }
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #A8A29E;
            pointer-events: none;
        }

        /* Active filter label */
        .active-filter {
            margin-bottom: 20px;
            font-size: 14px;
            color: var(--text-secondary);
            display: none;
        }
        .active-filter.visible { display: block; }
        .active-filter strong {
            color: var(--text);
            font-weight: 600;
        }

        /* Card grid */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        /* Card */
        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }
        .card-header {
            padding: 20px 20px 0;
        }
        .card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 2px;
            line-height: 1.3;
        }
        .card-org {
            font-size: 13px;
            color: var(--text-secondary);
        }
        .card-body {
            padding: 12px 20px;
            flex: 1;
        }
        .card-desc {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.55;
        }
        .card-footer {
            padding: 0 20px 16px;
        }
        .card-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
        }
        .tag {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 12px;
            white-space: nowrap;
        }
        .tag--4 {
            background: var(--dv4-light);
            color: var(--dv4);
        }
        .tag--6 {
            background: var(--dv6-light);
            color: var(--dv6);
        }
        .card-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            font-weight: 500;
            color: var(--color-primary);
            text-decoration: none;
        }
        .card-link:hover { text-decoration: underline; }
        .card-link svg { flex-shrink: 0; }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            display: none;
        }
        .empty-state.visible { display: block; }
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }
        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }
        .empty-state p {
            font-size: 14px;
            color: var(--text-secondary);
        }

        /* Card enter animation */
        .card {
            animation: cardIn 0.25s ease both;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Mobile */
        @media (max-width: 1100px) {
            .card-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                min-width: 100%;
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1px solid var(--border);
                padding: 12px 0;
                overflow-x: auto;
                overflow-y: hidden;
                display: flex;
                flex-wrap: nowrap;
                gap: 0;
            }
            .main-layout { flex-direction: column; }
            .sidebar-section-label { display: none; }
            .sidebar-divider { display: none; }
            .sidebar-item {
                white-space: nowrap;
                padding: 8px 14px;
                border-left: none;
                border-bottom: 3px solid transparent;
                font-size: 12px;
                flex-shrink: 0;
            }
            .sidebar-item.active {
                border-left: none;
            }
            .sidebar-item.active[data-chapter="4"] {
                border-left-color: transparent;
                border-bottom-color: var(--dv4);
            }
            .sidebar-item.active[data-chapter="6"] {
                border-left-color: transparent;
                border-bottom-color: var(--dv6);
            }
            .sidebar-item.active[data-chapter="all"] {
                border-left-color: transparent;
                border-bottom-color: var(--color-primary);
            }
            .sidebar-dot { width: 6px; height: 6px; }
            .content-area { padding: 16px; }
            .card-grid { grid-template-columns: 1fr; }
            .page-header { padding: 20px 16px 16px; }
            .page-header h1 { font-size: 22px; }
            .stats-bar { padding: 10px 16px; gap: 20px; }
            .top-bar { padding: 12px 16px; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="/bimverdi-v2/demo/">&larr; Tilbake til demo-oversikt</a>
</div>

<div class="page-header">
    <h1>Veikart-kort</h1>
    <p>Utforsk prosjekter knyttet til hvert maalomraade i Digitalt Veikart 2.0</p>
</div>

<div class="stats-bar">
    <div class="stat-item">
        <div class="stat-dot stat-dot--project"></div>
        <span class="stat-value" id="stat-projects">44</span> prosjekter
    </div>
    <div class="stat-item">
        <div class="stat-dot stat-dot--link"></div>
        <span class="stat-value" id="stat-links">107</span> koblinger
    </div>
    <div class="stat-item">
        <div class="stat-dot stat-dot--dv"></div>
        <span class="stat-value">15</span> DV-maal
    </div>
</div>

<div class="main-layout">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar"></div>

    <!-- Content -->
    <div class="content-area">
        <div class="search-box">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="search-input" placeholder="Sok etter prosjekt, organisasjon eller beskrivelse...">
        </div>
        <div class="active-filter" id="active-filter"></div>
        <div class="card-grid" id="card-grid"></div>
        <div class="empty-state" id="empty-state">
            <div class="empty-state-icon">&#128269;</div>
            <h3>Ingen prosjekter funnet</h3>
            <p>Prov et annet sokeord eller velg en annen kategori.</p>
        </div>
    </div>
</div>

<script>
(function() {
    // DV Headings
    const dvHeadings = [
        { id: '4.1', chapter: 4, label: 'Felles rammeverk for informasjonsforvaltning' },
        { id: '4.2', chapter: 4, label: 'Internasjonale rammer' },
        { id: '4.3', chapter: 4, label: 'Nasjonale rammer' },
        { id: '4.4', chapter: 4, label: 'Felles spesifikasjoner og komponenter' },
        { id: '4.5', chapter: 4, label: 'Etablere standarder for API' },
        { id: '4.6', chapter: 4, label: 'Sluttbrukerlosninger i markedet' },
        { id: '4.7', chapter: 4, label: 'Forvaltning av felles rammeverk' },
        { id: '6.1', chapter: 6, label: 'Kunstig intelligens / maskinlaering' },
        { id: '6.2', chapter: 6, label: 'Algoritmer' },
        { id: '6.3', chapter: 6, label: 'Sensorteknologi' },
        { id: '6.4', chapter: 6, label: 'RFiD' },
        { id: '6.5', chapter: 6, label: 'Virtual Reality (VR)' },
        { id: '6.6', chapter: 6, label: 'Roboter' },
        { id: '6.7', chapter: 6, label: '3D-printing' },
        { id: '6.8', chapter: 6, label: 'Droner' }
    ];

    // All 44 projects
    const projects = [
        { id: 'POFIN', org: 'buildingSMART Norge', desc: 'buildingSMART Norges rammeverk for Project and Facilities in Norway. Forbedre og standardisere bruk av openBIM.', links: [{ label: 'POFIN - buildingSMART', url: 'https://buildingsmart.no/pofin' }], dv: ['4.1','4.3','4.7'] },
        { id: 'PDT Norge', org: 'Cobuilder / BNL', desc: 'Offisiell distribusjonsplattform for produktdatamaler (Product Data Templates).', links: [{ label: 'PDT Norge', url: 'https://cobuilder.com/nb/pdt-norge-en-offisiell-distribusjonsplattform-for-produktdatamaler/' }], dv: ['4.4','4.5'] },
        { id: 'IFC 4.3 ISO-standard', org: 'buildingSMART International', desc: 'IFC 4.3 vedtatt som ISO 16739-1:2024. Aapen BIM-datastruktur.', links: [{ label: 'IFC 4.3 ISO', url: 'https://buildingsmart.no/nyheter/ifc-iso' }], dv: ['4.2','4.4','4.5'] },
        { id: 'Statsbygg BIM-manual 2.0', org: 'Statsbygg', desc: 'Krav til BIM i alle Statsbygg-prosjekter.', links: [{ label: 'SBM2', url: 'https://arkiv.buildingsmart.no/nyhetsbrev/2019-11/statsbyggs-bim-manual-20-sbm2-et-konkret-eksempel-pa-hvordan-apne-standarder-kan' }], dv: ['4.1','4.3'] },
        { id: 'BIM for alle (Forsvarsbygg)', org: 'Forsvarsbygg', desc: 'Digitalisering av 4 mill. kvm paa openBIM.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/bim/sintef-og-buildingsmart-norge-inngar-samarbeidsavtale/1747497' }], dv: ['4.6','4.1'] },
        { id: 'boligBIM-prosjektet', org: 'Boligprodusentene / bSN', desc: 'Standardisering av BIM-praksis for boligbygging.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/bolig-boligbygging/full-fart-i-boligbim-prosjektet/2069485' }], dv: ['4.4','4.6'] },
        { id: 'ISO 19650-serien (norsk)', org: 'Standard Norge', desc: 'NS-EN ISO 19650 oversatt til norsk.', links: [{ label: 'Standard Norge', url: 'https://standard.no/fagomrader/bygg-anlegg-og-eiendom/digital-byggeprosess/iso-19650-serien/' }], dv: ['4.1','4.2'] },
        { id: 'Digibygg (Statsbygg)', org: 'Statsbygg', desc: 'Digitalisering og smart teknologi i Statsbyggs prosjekter.', links: [{ label: 'Statsbygg', url: 'https://www.statsbygg.no/nyheter/digitale-tvillinger-i-2023' }], dv: ['6.1','6.3','6.8','4.6'] },
        { id: 'Statsbygg droneinspeksjon', org: 'Statsbygg', desc: 'Inspeksjon av tak og fasader med droner og AI.', links: [{ label: 'Byggfakta', url: 'https://nyheter.byggfakta.no/statsbygg-inngar-avtaler-om-droneinspeksjon-175538/nyhet.html' }], dv: ['6.8','6.1'] },
        { id: 'SINTEF AI i bygg', org: 'SINTEF', desc: 'Forskning paa AI i byggenaeringen.', links: [{ label: 'SINTEF', url: 'https://www.sintef.no/en/sintef-research-areas/artificial-intelligence/great-effect-using-ai-in-the-construction-industry/' }], dv: ['6.1','6.2'] },
        { id: 'Veidekke 3D-print betong', org: 'Veidekke', desc: 'Tester 3D-printing av betong.', links: [{ label: 'Digital Norway', url: 'https://digitalnorway.com/aktuelt/dette-er-tech-trendene-som-vil-prege-2026' }], dv: ['6.7','6.6'] },
        { id: 'Vegvesen bro-inspeksjon', org: 'Statens vegvesen', desc: 'Dronebilder og AI for bro-inspeksjon.', links: [{ label: 'norskbyggebransje.no', url: 'https://norskbyggebransje.no/ai/bygg-og-anlegg-i-2026' }], dv: ['6.8','6.1','6.2'] },
        { id: 'Digital Product Passport (DPP)', org: 'Cobuilder / EU', desc: 'Digitale produktpass fra 2026-2029.', links: [{ label: 'Cobuilder', url: 'https://cobuilder.com/en/digital-product-passport-dpp/digital-product-passports-for-construction-what-the-eus-2026-2029-cpr-working-plan-means-for-stakeholders/' }], dv: ['4.4','4.2','4.5'] },
        { id: 'Prosjekt Norge digitaliseringsmaaling', org: 'Prosjekt Norge / NTNU', desc: 'Maaling av effekter av digitalisering.', links: [{ label: 'Prosjekt Norge', url: 'https://www.prosjektnorge.no/maling-av-effekter-av-digitalisering-i-den-norske-byggenaeringen/' }], dv: ['4.7','4.1'] },
        { id: 'Nordic BIM Digital Twin', org: 'Nordic BIM Group', desc: 'Digitale tvillinger for bygg- og eiendomsbransjen.', links: [{ label: 'Nordic BIM', url: 'https://www.nordicbim.com/no/digital-tvilling' }], dv: ['6.3','6.5'] },
        { id: 'VA-objekter (Vann og avlop)', org: 'buildingSMART Norge', desc: 'Felles retningslinjer for VA med BIM.', links: [{ label: 'buildingSMART', url: 'https://buildingsmart.no/' }], dv: ['4.4','4.3'] },
        { id: 'Spacemaker / Autodesk Forma', org: 'Autodesk (tidl. Spacemaker)', desc: 'AI-plattform for tidligfase arealplanlegging. Kjopt av Autodesk for 2,2 mrd kr.', links: [{ label: 'NTI', url: 'https://www.nti-group.com/no/produkter/autodesk-software/forma/' }], dv: ['6.1','6.2','4.6'] },
        { id: 'Catenda Hub (BIMsync)', org: 'Catenda', desc: 'Norsk openBIM-plattform for samhandling.', links: [{ label: 'Catenda', url: 'https://catenda.com/' }], dv: ['4.1','4.5','4.6'] },
        { id: 'Novorender', org: 'Novorender (Stavanger)', desc: '3D/BIM-viewer for >100 GB i nettleseren.', links: [{ label: 'Novorender', url: 'https://novorender.com/' }], dv: ['4.6','4.5','6.5'] },
        { id: 'Dimension10 VR', org: 'Dimension10 / Catenda', desc: 'VR-plattform for byggebransjen.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/bim-teknologi/norske-vr-og-bim-selskap-inngar-samarbeid/2365941' }], dv: ['6.5','4.6'] },
        { id: 'Nordisk BIM-samarbeid (infra)', org: 'SVV / Nye Veier / Bane NOR', desc: 'Nordisk samarbeid om openBIM i infra.', links: [{ label: 'Nye Veier', url: 'https://www.nyeveier.no/nyheter/nyheter/statens-vegvesen-nye-veier-og-bane-nor-inngar-nordisk-bim-samarbeid/' }], dv: ['4.2','4.3','4.1'] },
        { id: 'Fellestjenester BYGG (DiBK)', org: 'DiBK', desc: 'Altinn-basert plattform for digitale byggesoknader.', links: [{ label: 'DiBK', url: 'https://www.dibk.no/verktoy-og-veivisere/andre-fagomrader/fellestjenester-bygg' }], dv: ['4.3','4.5','4.7'] },
        { id: 'Airthings inneklima-sensorer', org: 'Airthings', desc: '500 sensorer styrer 20 aar gammelt bygg.', links: [{ label: 'Estate Vest', url: 'https://www.estatevest.no/500-airthings-sensorer-styrer-20-ar-gammelt-bygg/' }], dv: ['6.3','6.1'] },
        { id: 'Disruptive Technologies sensorer', org: 'Disruptive Technologies', desc: 'Verdens minste tradlose sensorer.', links: [{ label: 'Digital Norway', url: 'https://digitalnorway.com/proptech-bergen/' }], dv: ['6.3','6.4'] },
        { id: 'Statsbygg + GS1 RFID-pilot', org: 'Statsbygg / GS1 Norge', desc: 'RFID-sporing av byggevarer paa byggeplass.', links: [{ label: 'ITBaktuelt', url: 'https://www.itbaktuelt.no/2017/09/12/rfdi-teknologi-statsbygg-digitalisering-173/' }], dv: ['6.4','4.4'] },
        { id: 'Dalux Field', org: 'Dalux', desc: 'Digital byggeplass med BIM og AR.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/af-gruppen-ikt/af-gruppen-utvider-konsernavtale-med-dalux/2190158' }], dv: ['6.5','4.6'] },
        { id: 'nLink borrerbot', org: 'nLink', desc: 'Mobile roboter for byggeplass.', links: [{ label: 'nLink', url: 'https://nlink.no/' }], dv: ['6.6','6.2'] },
        { id: 'Moelven Byggmodul', org: 'Moelven', desc: 'Industrialisert modulbygging i tre.', links: [{ label: 'Moelven', url: 'https://www.moelven.com/no/produkter-og-tjenester/modulbygg/' }], dv: ['6.6','6.7'] },
        { id: 'Autility Twin (FDV)', org: 'Autility', desc: 'Digital tvilling for drift og vedlikehold.', links: [{ label: 'norskbyggebransje.no', url: 'https://norskbyggebransje.no/nyheter/digital-tvilling-optimaliserer-drift-og-vedlikehold' }], dv: ['6.3','4.4','4.1'] },
        { id: 'Spot robot paa byggeplass', org: 'Diverse entreprenorer', desc: 'Boston Dynamics Spot for autonom inspeksjon.', links: [{ label: 'Tekna', url: 'https://www.tekna.no/fag-og-nettverk/bygg-og-anlegg/byggbloggen/roboter-pa-byggeplass/' }], dv: ['6.6','6.1'] },
        { id: 'Fornebubanen BIM', org: 'Fornebubanen / Oslo', desc: 'En av verdens storste BIM-modeller.', links: [{ label: 'Novorender', url: 'https://novorender.com/hvordan-novorender-hjelper-radgiver-og-entreprenor-mote-bim-krav-i-store-norske-infrastrukturprosjekter' }], dv: ['4.6','4.1'] },
        { id: 'OsloMet digital tvilling energi', org: 'OsloMet', desc: 'Digital tvilling for energieffektive bygg.', links: [{ label: 'OsloMet', url: 'https://www.oslomet.no/forskning/forskningsnyheter/hvordan-spare-mer-energi-i-bygg-digital-tvilling' }], dv: ['6.3','6.1'] },
        { id: 'Norconsult VR/AR-tjenester', org: 'Norconsult', desc: 'VR og AR for prosjektering og visualisering.', links: [{ label: 'Norconsult', url: 'https://norconsult.no/tjenester/digitalisering/vrar/' }], dv: ['6.5','4.6'] },
        { id: 'Imerso (3D-skanning + AI)', org: 'Imerso', desc: '3D-skanning og BIM for fremdriftskontroll med AI.', links: [{ label: 'tu.no', url: 'https://www.tu.no/artikler/snart-blir-laserskannere-sa-billige-at-alle-byggeplasser-vil-skannes-24-7-br/458842' }], dv: ['6.1','6.2','6.3','4.6'] },
        { id: 'StreamBIM (Rendra)', org: 'Rendra / JDM Technology', desc: 'Skybasert BIM-viewer med streaming.', links: [{ label: 'StreamBIM', url: 'https://streambim.com/' }], dv: ['4.6','4.5','4.1'] },
        { id: 'bSDD (Data Dictionary)', org: 'buildingSMART International', desc: 'Internasjonal dataordbok for klassifikasjoner.', links: [{ label: 'buildingSMART', url: 'https://www.buildingsmart.org/users/services/buildingsmart-data-dictionary/' }], dv: ['4.2','4.4','4.5'] },
        { id: 'BREEAM-NOR (miljoesertifisering)', org: 'Gronn Byggallianse', desc: 'Miljoesertifisering for bygg.', links: [{ label: 'Byggalliansen', url: 'https://byggalliansen.no/sertifisering/om-breeam/' }], dv: ['4.3','4.4'] },
        { id: 'Statsbygg 3D-printet bygg', org: 'Statsbygg', desc: 'Norges forste 3D-printede bygg.', links: [{ label: 'Statsbygg', url: 'https://www.statsbygg.no/nyheter/statsbygg-skal-lage-norges-forste-3d-printede-bygg' }], dv: ['6.7','6.6'] },
        { id: 'SIMBA modellsjekk (NTI/Statsbygg)', org: 'NTI / Statsbygg', desc: 'Automatisk skybasert kontroll av BIM-modeller.', links: [{ label: 'bygg.no', url: 'https://www.bygg.no/kommer-med-automatisk-kontroll-av-statsbyggs-bim-krav/1446769!/' }], dv: ['6.2','4.6','4.3'] },
        { id: 'Digital byggesoeknad med BIM (DiBK)', org: 'DiBK', desc: 'BIM som dokumentasjon i byggesoknader.', links: [{ label: 'DiBK', url: 'https://www.dibk.no/soknad-og-skjema/vil-du-bruke-bim-i-byggesoknaden' }], dv: ['4.3','4.5','6.2'] },
        { id: 'Oslobygg kommunal eiendom', org: 'Oslo kommune', desc: 'Forvalter ca. 1800 kommunale bygg i Oslo.', links: [{ label: 'Oslo kommune', url: 'https://www.oslo.kommune.no/etater-foretak-og-ombud/oslobygg-kf/' }], dv: ['4.1','6.3','4.7'] },
        { id: 'MIL 3D-printing betong (Grimstad)', org: 'Mechatronics Innovation Lab', desc: 'Norges forste robotiserte betong-3D-printer.', links: [{ label: 'MIL', url: 'https://mil-as.no/index.php/2024/01/25/3d-printe-betong/' }], dv: ['6.7','6.6'] },
        { id: 'Digital Product Passport EU (CPR)', org: 'EU-kommisjonen', desc: 'EUs nye byggevareforordning med digitale produktpass.', links: [{ label: 'Cobuilder', url: 'https://cobuilder.com/en/digital-product-passport-dpp/digital-product-passports-for-construction-what-the-eus-2026-2029-cpr-working-plan-means-for-stakeholders/' }], dv: ['4.2','4.4'] },
        { id: 'Sweco 3D-skanning FDV', org: 'Sweco', desc: '3D-skannere for drift og vedlikehold.', links: [{ label: 'Sweco', url: 'https://www.sweco.no/aktuelt/nyheter/3d-scanner-for-effektiv-drift-og-vedlikehold-av-bygningsmasser/' }], dv: ['6.3','4.1'] }
    ];

    // Build heading-to-project count map
    const headingCounts = {};
    dvHeadings.forEach(h => { headingCounts[h.id] = 0; });
    projects.forEach(p => {
        p.dv.forEach(dvId => {
            if (headingCounts[dvId] !== undefined) headingCounts[dvId]++;
        });
    });

    // Total links count
    let totalLinks = 0;
    projects.forEach(p => { totalLinks += p.dv.length; });

    // State
    let activeHeading = null; // null = show all
    let searchQuery = '';

    // Build sidebar
    function buildSidebar() {
        const sidebar = document.getElementById('sidebar');
        let html = '';

        // "Vis alle" item
        html += '<div class="sidebar-item active" data-heading="all" data-chapter="all">';
        html += '<div class="sidebar-dot sidebar-dot--all"></div>';
        html += '<span class="sidebar-text">Vis alle</span>';
        html += '<span class="sidebar-badge sidebar-badge--all">' + projects.length + '</span>';
        html += '</div>';

        html += '<div class="sidebar-divider"></div>';

        // Kap 4
        html += '<div class="sidebar-section-label">Kapittel 4 &mdash; Felleskomponenter</div>';
        dvHeadings.filter(h => h.chapter === 4).forEach(h => {
            html += '<div class="sidebar-item" data-heading="' + h.id + '" data-chapter="4">';
            html += '<div class="sidebar-dot sidebar-dot--4"></div>';
            html += '<span class="sidebar-text">DV ' + h.id + ' ' + h.label + '</span>';
            html += '<span class="sidebar-badge sidebar-badge--4">' + headingCounts[h.id] + '</span>';
            html += '</div>';
        });

        html += '<div class="sidebar-divider"></div>';

        // Kap 6
        html += '<div class="sidebar-section-label">Kapittel 6 &mdash; Ny teknologi</div>';
        dvHeadings.filter(h => h.chapter === 6).forEach(h => {
            html += '<div class="sidebar-item" data-heading="' + h.id + '" data-chapter="6">';
            html += '<div class="sidebar-dot sidebar-dot--6"></div>';
            html += '<span class="sidebar-text">DV ' + h.id + ' ' + h.label + '</span>';
            html += '<span class="sidebar-badge sidebar-badge--6">' + headingCounts[h.id] + '</span>';
            html += '</div>';
        });

        sidebar.innerHTML = html;

        // Click handlers
        sidebar.querySelectorAll('.sidebar-item').forEach(item => {
            item.addEventListener('click', function() {
                const heading = this.getAttribute('data-heading');
                if (heading === 'all') {
                    activeHeading = null;
                } else {
                    activeHeading = heading;
                }
                updateSidebarActive();
                renderCards();
            });
        });
    }

    function updateSidebarActive() {
        document.querySelectorAll('.sidebar-item').forEach(item => {
            const heading = item.getAttribute('data-heading');
            if (activeHeading === null && heading === 'all') {
                item.classList.add('active');
            } else if (activeHeading === heading) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    // Get heading info by id
    function getHeading(id) {
        return dvHeadings.find(h => h.id === id);
    }

    // Render cards
    function renderCards() {
        const grid = document.getElementById('card-grid');
        const emptyState = document.getElementById('empty-state');
        const filterLabel = document.getElementById('active-filter');
        const query = searchQuery.toLowerCase().trim();

        // Filter projects
        let filtered = projects;

        if (activeHeading) {
            filtered = filtered.filter(p => p.dv.includes(activeHeading));
        }

        if (query) {
            filtered = filtered.filter(p => {
                return p.id.toLowerCase().includes(query) ||
                       p.org.toLowerCase().includes(query) ||
                       p.desc.toLowerCase().includes(query);
            });
        }

        // Update filter label
        if (activeHeading) {
            const h = getHeading(activeHeading);
            filterLabel.innerHTML = 'Filtrert paa <strong>DV ' + h.id + ' ' + h.label + '</strong> &mdash; ' + filtered.length + ' prosjekter';
            filterLabel.classList.add('visible');
        } else {
            filterLabel.classList.remove('visible');
        }

        // Update stats
        document.getElementById('stat-projects').textContent = filtered.length;
        let filteredLinks = 0;
        filtered.forEach(p => { filteredLinks += p.dv.length; });
        document.getElementById('stat-links').textContent = filteredLinks;

        // Build cards
        if (filtered.length === 0) {
            grid.innerHTML = '';
            emptyState.classList.add('visible');
            return;
        }

        emptyState.classList.remove('visible');

        let html = '';
        filtered.forEach((p, i) => {
            html += '<div class="card" style="animation-delay:' + (i * 0.03) + 's">';
            html += '<div class="card-header">';
            html += '<div class="card-title">' + escapeHtml(p.id) + '</div>';
            html += '<div class="card-org">' + escapeHtml(p.org) + '</div>';
            html += '</div>';
            html += '<div class="card-body">';
            html += '<div class="card-desc">' + escapeHtml(p.desc) + '</div>';
            html += '</div>';
            html += '<div class="card-footer">';
            html += '<div class="card-tags">';
            p.dv.forEach(dvId => {
                const h = getHeading(dvId);
                if (h) {
                    const cls = h.chapter === 4 ? 'tag--4' : 'tag--6';
                    const isActive = activeHeading === dvId;
                    const style = isActive ? ' style="outline:2px solid ' + (h.chapter === 4 ? 'var(--dv4)' : 'var(--dv6)') + ';outline-offset:-1px"' : '';
                    html += '<span class="tag ' + cls + '"' + style + '>DV ' + h.id + '</span>';
                }
            });
            html += '</div>';
            p.links.forEach(link => {
                html += '<a class="card-link" href="' + escapeHtml(link.url) + '" target="_blank" rel="noopener">';
                html += escapeHtml(link.label);
                html += ' <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7"/><path d="M7 7h10v10"/></svg>';
                html += '</a>';
            });
            html += '</div>';
            html += '</div>';
        });

        grid.innerHTML = html;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Search handler
    let searchTimeout;
    document.getElementById('search-input').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const val = this.value;
        searchTimeout = setTimeout(function() {
            searchQuery = val;
            renderCards();
        }, 150);
    });

    // Init
    buildSidebar();
    renderCards();
})();
</script>

</body>
</html>
