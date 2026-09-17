<?php
/**
 * Front Page Template
 *
 * Erstattet 17.09.2026. Den forrige versjonen ligger i git-historikken —
 * `git log --follow front-page.php` — hvis noe skal hentes tilbake.
 *
 * Bakgrunnen er Bårds innvending 15. og 17.09: forsiden endret seg ikke fra
 * uke til uke uten at man scrollet. «Jeg ville bare lansere det der, og så
 * får vi justere ting litt senere.» Endringene mot den gamle:
 *
 *  1. Heroens nettverks-SVG er byttet mot NESTE ARRANGEMENT — alene, med hele
 *     kolonnen, og uten bilde. Arrangementsbildene er skjermbilder med egen
 *     tekst i, og i det første man møter på siden er de støy. Kortet er dato,
 *     tittel, formål, tidspunkt og lenke, med en dempet lenke til arkivet under.
 *     Finnes ingen kommende arrangement, vises det siste som var.
 *  2. ARTIKLENE er flyttet fra bunnen til rett under logostripa, som tre
 *     likeverdige kort. Det er artiklene som faktisk kommer til fra uke til
 *     uke — arrangementet bytter sjelden — så det er de som svarer på
 *     innvendingen over.
 *  3. Arrangementskortet i kortraden er borte (det står nå øverst), og plassen
 *     er tatt av et kort for den dynamiske tema-grafen.
 *  4. Vertikal rytme strammet inn ett hakk gjennom hele siden.
 *
 * Levå en periode som egen sidemal på /forside-forslag/ mens Bård så på den.
 * Den malen og den siden er fjernet nå som dette ER forsiden — to identiske
 * sider på samme nettsted konkurrerer med hverandre i søk.
 */

if (!defined('ABSPATH')) exit;


get_header();

// === DATA ===
$total_companies = (new WP_Query([
    'post_type'      => 'foretak',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'meta_query'     => [
        [
            'key'     => 'bv_rolle',
            'value'   => ['Deltaker', 'Prosjektdeltaker', 'Partner'],
            'compare' => 'IN',
        ],
    ],
]))->found_posts;
$total_tools     = wp_count_posts('verktoy')->publish;
$total_events    = wp_count_posts('arrangement')->publish;
$total_sources   = wp_count_posts('kunnskapskilde')->publish;
$total_articles  = wp_count_posts('artikkel')->publish;

// Foretak for logo bar (prefer those with thumbnails, fallback to any)
$logo_companies = get_posts([
    'post_type'      => 'foretak',
    'posts_per_page' => 18,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => [
        [
            'key'     => 'bv_rolle',
            'value'   => ['Deltaker', 'Prosjektdeltaker', 'Partner'],
            'compare' => 'IN',
        ],
    ],
]);

// Upcoming events
$events = get_posts([
    'post_type'      => 'arrangement',
    'posts_per_page' => 4,
    'meta_query'     => [['key' => 'arrangement_status_toggle', 'value' => 'kommende']],
    'meta_key'       => 'arrangement_dato',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
]);
if (empty($events)) {
    // Ingen kommende arrangement: vis det SISTE som var, ikke det første vi har.
    // Sorteringen må derfor snus — med ASC her ville toppen av forsiden vist
    // det eldste arrangementet i basen, som er verre enn ingenting.
    $events = get_posts([
        'post_type'      => 'arrangement',
        'posts_per_page' => 4,
        'post_status'    => 'publish',
        'meta_key'       => 'arrangement_dato',
        'orderby'        => 'meta_value',
        'order'          => 'DESC',
    ]);
}

// Latest articles
$articles = get_posts([
    'post_type'      => 'artikkel',
    'posts_per_page' => 4,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
if (empty($articles)) {
    $articles = get_posts([
        'post_type'      => 'post',
        'posts_per_page' => 4,
        'post_status'    => 'publish',
    ]);
}

// Temagruppe colors
$tg_colors = [
    'SirkBIM'      => '#FF8B5E',
    'ByggesaksBIM' => '#005898',
    'ProsjektBIM'  => '#6B9B37',
    'EiendomsBIM'  => '#5E36FE',
    'MiljøBIM'     => '#0D9488',
    'BIMtech'      => '#D97706',
];

// Temagruppe data
$theme_groups = [
    ['title' => 'SirkBIM',      'desc' => 'Sirkulær økonomi, materialgjenbruk og ombruk i byggenæringen med digitale verktøy.', 'slug' => 'sirkbim',      'color' => '#FF8B5E'],
    ['title' => 'ByggesaksBIM', 'desc' => 'Digitalisering av byggesaksprosessen og bruk av BIM mot offentlige myndigheter.',    'slug' => 'byggesaksbim', 'color' => '#005898'],
    ['title' => 'ProsjektBIM',  'desc' => 'Beste praksis for BIM-koordinering og ledelse i store byggeprosjekter.',              'slug' => 'prosjektbim',  'color' => '#6B9B37'],
    ['title' => 'EiendomsBIM',  'desc' => 'FDV-dokumentasjon og bruk av BIM i driftsfasen for eiendomsforvaltere.',              'slug' => 'eiendomsbim',  'color' => '#5E36FE'],
    ['title' => 'MiljøBIM',     'desc' => 'Bruk av BIM for klimagassregnskap, ombruk og bærekraftige materialvalg.',             'slug' => 'miljobim',     'color' => '#0D9488'],
    ['title' => 'BIMtech',      'desc' => 'Utforsking av ny teknologi, API-er, skripting og innovasjon i bransjen.',             'slug' => 'bimtech',      'color' => '#D97706'],
];
?>

<!-- ============================================
     V3 DESIGN SYSTEM — Proof of Concept
     ============================================ -->
<style>
    /* ---- TOKENS ---- */
    :root {
        --bv3-orange: #FF8B5E;
        --bv3-dark: #111827;
        --bv3-text: #1A1A1A;
        --bv3-text-secondary: #57534E;
        --bv3-text-muted: #A8A29E;
        --bv3-bg-alt: #F5F5F4;
        --bv3-bg-section: #FAFAF9;
        --bv3-border: #E7E5E4;
        --bv3-radius: 16px;
    }

    /* ---- LAYOUT ---- */
    .bv3-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
    }
    /* Vertikal rytme (Andreas 17.09): ett hakk strammere enn før. Mellom
       hero-kortet og artikkeloverskriften lå det 208 px død luft — 48 under
       heroen, 80 rundt logostripa og 80 over seksjonen. Alle verdier holder
       seg på 8px-skalaen. */
    .bv3-section { padding: 3.5rem 0; }
    .bv3-section--alt { background: var(--bv3-bg-section); }

    /* ---- EYEBROW ---- */
    .bv3-eyebrow {
        display: block;
        color: var(--bv3-orange);
        font-size: 0.8125rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        margin-bottom: 0.75rem;
        line-height: 1;
    }

    /* ---- HEADINGS ---- */
    .bv3-h1 {
        font-size: clamp(2.5rem, 5vw, 3.5rem);
        font-weight: 800;
        line-height: 1.08;
        letter-spacing: -0.025em;
        color: var(--bv3-dark);
        margin: 0 0 1.25rem;
    }
    .bv3-h2 {
        font-size: clamp(2rem, 4vw, 2.75rem);
        font-weight: 700;
        line-height: 1.12;
        letter-spacing: -0.02em;
        color: var(--bv3-dark);
        margin: 0 0 1rem;
    }
    .bv3-subtitle {
        font-size: 1.125rem;
        line-height: 1.6;
        color: var(--bv3-text-secondary);
        margin: 0;
        max-width: 540px;
    }

    /* ---- BUTTONS (pill, GitBook-style) ---- */
    .bv3-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.75rem;
        border-radius: 100px;
        font-weight: 500;
        font-size: 0.9375rem;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1.5px solid transparent;
        cursor: pointer;
        font-family: inherit;
    }
    .bv3-btn--dark {
        background: var(--bv3-dark);
        color: #fff;
        border-color: var(--bv3-dark);
    }
    .bv3-btn--dark:hover {
        background: #1F2937;
        color: #fff;
        text-decoration: none;
    }
    .bv3-btn--outline {
        background: transparent;
        color: var(--bv3-text);
        border-color: #D6D3D1;
    }
    .bv3-btn--outline:hover {
        border-color: var(--bv3-dark);
        text-decoration: none;
    }
    .bv3-btn--white {
        background: #fff;
        color: var(--bv3-dark);
        border-color: #fff;
    }
    .bv3-btn--white:hover {
        background: #F5F5F4;
        text-decoration: none;
        color: var(--bv3-dark);
    }
    .bv3-btn--ghost-white {
        background: transparent;
        color: rgba(255,255,255,.8);
        border-color: rgba(255,255,255,.25);
    }
    .bv3-btn--ghost-white:hover {
        border-color: rgba(255,255,255,.6);
        color: #fff;
        text-decoration: none;
    }

    /* ---- HERO (merged with Connecting the Dots) ---- */
    .bv3-hero {
        padding: 4rem 0 2.5rem;
        position: relative;
        overflow: hidden;
        background: #fff;
    }
    .bv3-hero__inner {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3rem;
        align-items: center;
    }
    .bv3-hero__actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 2rem;
        flex-wrap: wrap;
    }
    .bv3-hero__visual {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    /* Entity stat pills in the network SVG */
    .bv3-hero__stats {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 1.75rem;
    }
    .bv3-hero__stat {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.375rem 0.75rem;
        background: var(--bv3-bg-alt);
        border-radius: 100px;
        font-size: 0.8125rem;
        color: var(--bv3-text-secondary);
        font-weight: 500;
    }
    .bv3-hero__stat-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .bv3-hero__stat strong {
        font-weight: 700;
        color: var(--bv3-dark);
    }
    @media (max-width: 768px) {
        .bv3-hero__inner { grid-template-columns: 1fr; }
        .bv3-hero__visual { display: none; }
    }

    /* ---- LOGO BAR ---- */
    .bv3-logobar {
        padding: 1.75rem 0;
        border-top: 1px solid var(--bv3-border);
        background: #fff;
        overflow: hidden;
    }
    .bv3-logobar__scroll {
        overflow: hidden;
        position: relative;
        -webkit-mask-image: linear-gradient(to right, transparent 0%, black 8%, black 92%, transparent 100%);
        mask-image: linear-gradient(to right, transparent 0%, black 8%, black 92%, transparent 100%);
    }
    .bv3-logobar__label {
        text-align: center;
        font-size: 0.8125rem;
        color: var(--bv3-text-muted);
        margin-bottom: 1rem;
        font-weight: 500;
    }
    .bv3-logobar__track {
        display: flex;
        align-items: center;
        gap: 3rem;
        animation: bv3-scroll 50s linear infinite;
        width: max-content;
    }
    /* Pause scroll only when hovering a specific item */
    .bv3-logobar__track:has(.bv3-logobar__item:hover) {
        animation-play-state: paused;
    }
    .bv3-logobar__item {
        font-size: 0.9375rem;
        font-weight: 600;
        color: #A8A29E;
        white-space: nowrap;
        flex-shrink: 0;
        transition: color 0.35s ease, opacity 0.35s ease;
        text-decoration: none;
        cursor: pointer;
    }
    .bv3-logobar__item:hover {
        color: var(--bv3-dark);
        text-decoration: none;
    }
    /* Fade siblings on hover */
    .bv3-logobar__track:hover .bv3-logobar__item {
        opacity: 0.3;
    }
    .bv3-logobar__track:hover .bv3-logobar__item:hover {
        opacity: 1;
    }
    .bv3-logobar__item img {
        height: 28px;
        width: auto;
        opacity: 0.45;
        filter: grayscale(100%);
        transition: all 0.35s ease;
    }
    .bv3-logobar__item:hover img {
        opacity: 1;
        filter: grayscale(0%);
    }
    @keyframes bv3-scroll {
        from { transform: translateX(0); }
        to { transform: translateX(-50%); }
    }

    /* (Connecting the Dots merged into hero) */

    /* ---- FEATURE CARDS ---- */
    .bv3-features__grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }
    @media (max-width: 1024px) {
        .bv3-features__grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 640px) {
        .bv3-features__grid { grid-template-columns: 1fr; }
    }
    .bv3-fcard {
        background: var(--bv3-bg-alt);
        border-radius: var(--bv3-radius);
        overflow: hidden;
        transition: box-shadow 0.3s ease;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .bv3-fcard:hover {
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        text-decoration: none;
        color: inherit;
    }
    .bv3-fcard__visual {
        height: 240px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    /* Arrangement feature card */
    .bv3-fcard--event .bv3-fcard__visual {
        background: var(--bv3-dark);
        background-size: cover;
        background-position: center;
        flex-direction: column;
        gap: 0.75rem;
        padding: 1.5rem 1.75rem;
        align-items: flex-start;
        justify-content: flex-end;
        position: relative;
    }
    .bv3-fcard--event .bv3-fcard__visual::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.75) 0%, rgba(0,0,0,0.2) 60%, rgba(0,0,0,0.1) 100%);
        z-index: 0;
    }
    .bv3-fcard--event .bv3-fcard__visual > * {
        position: relative;
        z-index: 1;
    }
    .bv3-fcard--event__date-block {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
    }
    .bv3-fcard--event__day {
        font-size: 2.75rem;
        font-weight: 800;
        color: #fff;
        line-height: 1;
    }
    .bv3-fcard--event__monthyear {
        font-size: 0.875rem;
        font-weight: 600;
        color: rgba(255,255,255,0.6);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .bv3-fcard--event__title-preview {
        font-size: 1rem;
        font-weight: 600;
        color: #fff;
        line-height: 1.35;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .bv3-fcard--event__tag {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.2rem 0.625rem;
        border-radius: 100px;
    }
    /* No-event state */
    .bv3-fcard--event-archive .bv3-fcard__visual {
        background: var(--bv3-bg-alt);
        flex-direction: column;
        gap: 0.5rem;
        align-items: center;
        justify-content: center;
    }
    .bv3-fcard__content {
        padding: 2rem;
    }
    .bv3-fcard__title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--bv3-dark);
        margin: 0 0 0.5rem;
        line-height: 1.2;
    }
    .bv3-fcard__desc {
        font-size: 0.9375rem;
        color: var(--bv3-text-secondary);
        line-height: 1.6;
        margin: 0 0 1.25rem;
    }
    .bv3-fcard__link {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--bv3-dark);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 1rem;
        border-radius: 100px;
        border: 1.5px solid var(--bv3-border);
        transition: all 0.2s;
    }
    .bv3-fcard:hover .bv3-fcard__link {
        border-color: var(--bv3-dark);
    }

    /* ---- VISUAL COMPOSITIONS INSIDE CARDS ---- */
    .bv3-visual-grid {
        display: grid;
        grid-template-columns: repeat(3, 64px);
        gap: 12px;
    }
    .bv3-visual-grid__item {
        width: 64px;
        height: 64px;
        background: #fff;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        position: relative;
    }
    .bv3-visual-grid__item svg {
        width: 28px;
        height: 28px;
        color: var(--bv3-text-secondary);
    }
    .bv3-visual-grid__item--accent {
        background: var(--bv3-orange);
    }
    .bv3-visual-grid__item--accent svg {
        color: #fff;
    }

    /* floating badge on feature card */
    .bv3-fcard__badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: var(--bv3-orange);
        color: #fff;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        border-radius: 100px;
    }

    /* ---- TEMAGRUPPER ---- */
    .bv3-tg-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.25rem;
    }
    @media (max-width: 768px) {
        .bv3-tg-grid { grid-template-columns: 1fr; }
    }
    @media (min-width: 769px) and (max-width: 1024px) {
        .bv3-tg-grid { grid-template-columns: repeat(2, 1fr); }
    }
    .bv3-tg-card {
        background: #fff;
        border-radius: var(--bv3-radius);
        padding: 1.75rem;
        border: 1px solid var(--bv3-border);
        transition: all 0.2s ease;
        text-decoration: none;
        color: inherit;
        display: block;
        position: relative;
        overflow: hidden;
    }
    .bv3-tg-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }
    .bv3-tg-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border-color: #D6D3D1;
        text-decoration: none;
        color: inherit;
    }
    .bv3-tg-card__icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }
    .bv3-tg-card__icon svg {
        width: 22px;
        height: 22px;
    }
    .bv3-tg-card__title {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--bv3-dark);
        margin: 0 0 0.375rem;
    }
    .bv3-tg-card__desc {
        font-size: 0.875rem;
        color: var(--bv3-text-secondary);
        line-height: 1.55;
        margin: 0;
    }

    /* ---- EVENTS ---- */
    .bv3-event-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .bv3-event {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        padding: 1.25rem 1.5rem;
        background: #fff;
        border-radius: var(--bv3-radius);
        border: 1px solid var(--bv3-border);
        transition: all 0.2s;
        text-decoration: none;
        color: inherit;
    }
    .bv3-event:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        border-color: #D6D3D1;
        text-decoration: none;
        color: inherit;
    }
    .bv3-event__date {
        flex-shrink: 0;
        width: 56px;
        height: 56px;
        border-radius: 12px;
        background: var(--bv3-dark);
        color: #fff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .bv3-event__day {
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1;
    }
    .bv3-event__month {
        font-size: 0.625rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        margin-top: 2px;
    }
    .bv3-event__info { flex-grow: 1; min-width: 0; }
    .bv3-event__title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--bv3-dark);
        margin: 0 0 0.25rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .bv3-event__meta {
        font-size: 0.8125rem;
        color: var(--bv3-text-muted);
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
    }
    .bv3-event__tag {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.125rem 0.625rem;
        border-radius: 100px;
    }
    .bv3-event__arrow {
        flex-shrink: 0;
        color: var(--bv3-text-muted);
        transition: color 0.2s;
    }
    .bv3-event:hover .bv3-event__arrow { color: var(--bv3-dark); }

    /* ---- ARTICLES ---- */
    /* Tre likeverdige kort, i samme drakt som kortraden lenger nede: samme
       bakgrunn, samme radius, samme hover. Seksjonen står derfor på hvitt og
       ikke på --bv3-bg-section — kortfargen ligger for nær seksjonsfargen til
       at kortene ville lest som kort.
       Tidligere var dette étt stort «featured»-kort pluss tre små rader, og da
       leste man i praksis bare det første. Andreas 17.09: alle skal komme like
       bra ut — samme grep som Bård ba om for arrangementene i nyhetsbrevet.
       Tre og ikke fire: samme rytme som kortraden under, og det fjerde kortet
       er uansett den eldste artikkelen. */
    .bv3-articles__grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }
    @media (max-width: 1024px) {
        .bv3-articles__grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 640px) {
        .bv3-articles__grid { grid-template-columns: 1fr; }
    }

    .bv3-article-card {
        display: flex;
        flex-direction: column;
        background: var(--bv3-bg-alt);
        border-radius: var(--bv3-radius);
        overflow: hidden;
        transition: box-shadow 0.3s ease;
        text-decoration: none;
        color: inherit;
        min-width: 0;
    }
    .bv3-article-card:hover {
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        text-decoration: none;
        color: inherit;
    }
    .bv3-article-card__img {
        aspect-ratio: 16 / 10;
        overflow: hidden;
        background: #EDECEA;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .bv3-article-card__img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.3s ease;
    }
    .bv3-article-card:hover .bv3-article-card__img img { transform: scale(1.03); }

    /* Kortene holdes like høye av faste linjeklipp på tittel og ingress, ikke
       av en fast høyde — da blir de riktige også når teksten er kort. */
    .bv3-article-card__body {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    /* Deltakermerket er det eneste som skiller kortene fra hverandre, og det
       skal det gjøre — Bård vil se hvilke artikler som kommer fra deltakerne.
       Plassen holdes av selv når merket mangler, ellers hopper datoene i
       forhold til hverandre. */
    .bv3-article-card__deltaker {
        display: block;
        min-height: 1.125rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #1D4ED8;
        margin-bottom: 0.375rem;
    }
    .bv3-article-card__date {
        font-size: 0.75rem;
        color: var(--bv3-text-muted);
        margin-bottom: 0.375rem;
    }
    .bv3-article-card__title {
        font-size: 1.0625rem;
        font-weight: 700;
        color: var(--bv3-dark);
        line-height: 1.3;
        margin: 0 0 0.5rem;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .bv3-article-card__desc {
        font-size: 0.875rem;
        line-height: 1.55;
        color: var(--bv3-text-secondary);
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* ---- CTA / DARK SECTION ---- */
    .bv3-cta {
        background: var(--bv3-dark);
        padding: 4.5rem 0;
        text-align: center;
    }
    .bv3-cta .bv3-h2 { color: #fff; }
    .bv3-cta .bv3-subtitle {
        color: rgba(255,255,255,.6);
        margin: 0 auto 2.5rem;
        max-width: 560px;
    }
    .bv3-cta__stats {
        display: flex;
        justify-content: center;
        gap: 3rem;
        margin-top: 3rem;
        flex-wrap: wrap;
    }
    .bv3-cta__stat-num {
        font-size: 2rem;
        font-weight: 800;
        color: var(--bv3-orange);
    }
    .bv3-cta__stat-label {
        font-size: 0.8125rem;
        color: rgba(255,255,255,.5);
        margin-top: 0.25rem;
    }

    /* ---- NETWORK SVG ILLUSTRATION ---- */
    .bv3-network-svg {
        width: 100%;
        max-width: 460px;
        height: auto;
    }
    .bv3-network-svg .node-pulse {
        animation: bv3-pulse 3s ease-in-out infinite;
    }
    @keyframes bv3-pulse {
        0%, 100% { opacity: 0.7; }
        50% { opacity: 1; }
    }
    .bv3-network-svg .dash-line {
        stroke-dasharray: 6 4;
        animation: bv3-dash 20s linear infinite;
    }
    @keyframes bv3-dash {
        to { stroke-dashoffset: -200; }
    }

    /* ---- SECTION HEADER CENTERED ---- */
    .bv3-section-header {
        margin-bottom: 2rem;
    }
    .bv3-section-header--center {
        text-align: center;
    }
    .bv3-section-header--center .bv3-subtitle {
        margin-left: auto;
        margin-right: auto;
    }

    /* ---- SECTION HEADER WITH LINK ---- */
    .bv3-section-header--split {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 2rem;
    }
    .bv3-section-header__link {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--bv3-text-secondary);
        text-decoration: none;
        white-space: nowrap;
        padding-bottom: 0.25rem;
    }
    .bv3-section-header__link:hover {
        color: var(--bv3-dark);
        text-decoration: none;
    }

    /* ---- SCROLL REVEAL ---- */
    .bv3-reveal {
        opacity: 0;
        transform: translateY(24px);
        transition: opacity 0.6s ease, transform 0.6s ease;
    }
    .bv3-reveal.visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* Stagger children */
    .bv3-reveal-children > * {
        opacity: 0;
        transform: translateY(16px);
        transition: opacity 0.5s ease, transform 0.5s ease;
    }
    .bv3-reveal-children.visible > * {
        opacity: 1;
        transform: translateY(0);
    }
    .bv3-reveal-children.visible > *:nth-child(1) { transition-delay: 0s; }
    .bv3-reveal-children.visible > *:nth-child(2) { transition-delay: 0.08s; }
    .bv3-reveal-children.visible > *:nth-child(3) { transition-delay: 0.16s; }
    .bv3-reveal-children.visible > *:nth-child(4) { transition-delay: 0.24s; }
    .bv3-reveal-children.visible > *:nth-child(5) { transition-delay: 0.32s; }
    .bv3-reveal-children.visible > *:nth-child(6) { transition-delay: 0.4s; }

    /* ---- DOT GRID CANVAS ---- */
    .bv3-dotgrid {
        background-color: #FAF9F7;
        background-image: radial-gradient(circle, #D1CEC8 0.75px, transparent 0.75px);
        background-size: 20px 20px;
    }

    /* ---- TEMAGRUPPE HIERARCHY ---- */
    .bv3-tg-hierarchy {
        position: relative;
        max-width: 960px;
        margin: 0 auto;
    }

    /* SVG connections layer */
    .bv3-tg-hierarchy__svg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
    }
    .bv3-tg-hierarchy__svg line,
    .bv3-tg-hierarchy__svg path {
        stroke-dasharray: 5 4;
        animation: bv3-dash 25s linear infinite;
    }

    /* Hub row */
    .bv3-tg-hub {
        display: flex;
        justify-content: center;
        margin-bottom: 2.5rem;
        position: relative;
        z-index: 2;
    }
    .bv3-tg-hub__node {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: var(--bv3-orange);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 800;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        box-shadow: 0 6px 24px rgba(255,139,94,0.3);
        text-decoration: none;
        position: relative;
    }
    .bv3-tg-hub__node::before {
        content: '';
        position: absolute;
        inset: -10px;
        border-radius: 50%;
        background: rgba(255,139,94,0.08);
        animation: bv3-pulse 3s ease-in-out infinite;
        z-index: -1;
    }
    .bv3-tg-hub__logo {
        font-size: 1rem;
        font-weight: 900;
        line-height: 1;
    }

    /* Row label */
    .bv3-tg-row-label {
        text-align: center;
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--bv3-text-muted);
        margin: 1.5rem 0 1rem;
        position: relative;
        z-index: 2;
    }
    .bv3-tg-row-label span {
        background: #FAF9F7;
        padding: 0 1rem;
        position: relative;
    }
    .bv3-tg-row-label::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 10%;
        right: 10%;
        height: 1px;
        background: var(--bv3-border);
    }

    /* Lifecycle row (4 cards) */
    .bv3-tg-lifecycle {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        position: relative;
        z-index: 2;
    }

    /* Card style (shared) */
    .bv3-tg-card2 {
        background: #fff;
        border-radius: 14px;
        padding: 1.25rem 1rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        border: 1px solid rgba(231,229,228,0.8);
        text-decoration: none;
        color: inherit;
        text-align: center;
        transition: all 0.25s ease;
        position: relative;
    }
    .bv3-tg-card2:hover {
        box-shadow: 0 8px 28px rgba(0,0,0,0.1);
        transform: translateY(-3px);
        text-decoration: none;
        color: inherit;
    }
    .bv3-tg-card2__dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin: 0 auto 0.5rem;
    }
    .bv3-tg-card2__num {
        position: absolute;
        top: -8px;
        left: -8px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        font-size: 0.6875rem;
        font-weight: 700;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .bv3-tg-card2__title {
        font-size: 0.9375rem;
        font-weight: 700;
        color: var(--bv3-dark);
        margin: 0 0 0.25rem;
        line-height: 1.2;
    }
    .bv3-tg-card2__desc {
        font-size: 0.75rem;
        color: var(--bv3-text-muted);
        line-height: 1.45;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Lifecycle arrows between cards */
    .bv3-tg-lifecycle__arrows {
        display: flex;
        align-items: center;
        justify-content: space-around;
        margin: 0.75rem 0;
        position: relative;
        z-index: 2;
        padding: 0 4%;
    }
    .bv3-tg-lifecycle__arrow {
        display: flex;
        align-items: center;
        gap: 0;
        color: var(--bv3-text-muted);
        font-size: 0.6875rem;
    }
    .bv3-tg-lifecycle__arrow svg {
        width: 20px;
        height: 20px;
    }

    /* Return arrow (SirkBIM back to ByggesaksBIM) */
    .bv3-tg-lifecycle__return {
        display: flex;
        justify-content: center;
        margin: -0.25rem 0 0;
        position: relative;
        z-index: 2;
    }

    /* Support row (2 cards) */
    .bv3-tg-support {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        max-width: 540px;
        margin: 0 auto;
        position: relative;
        z-index: 2;
    }
    .bv3-tg-card2--support {
        border-style: dashed;
    }
    .bv3-tg-card2--support .bv3-tg-card2__badge {
        font-size: 0.625rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--bv3-text-muted);
        margin-bottom: 0.375rem;
    }

    /* Mobile */
    .bv3-tg-hierarchy { display: block; }
    .bv3-tg-mobile { display: none; }
    @media (max-width: 768px) {
        .bv3-tg-hierarchy { display: none; }
        .bv3-tg-mobile {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .bv3-tg-mobile__label {
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--bv3-text-muted);
            margin: 1rem 0 0.25rem;
            padding-left: 0.25rem;
        }
    }

    /* ---- HERO-FEED: levende topp (Trello #352) ----
       Erstatter nettverks-SVG-en i heroen. To kort under hverandre, ikke ved
       siden av: høyrekolonnen er ca. 560 px på 1280, og to kort i bredden gir
       under 280 px hver — for trangt til bilde, dato, tittel og ingress.
       I motsetning til .bv3-hero__visual skjules dette ALDRI på mobil; det er
       hele poenget med å flytte det opp. */
    /* Kortet står alene i kolonnen og sentreres mot overskriften. Det skal
       IKKE strekkes til full høyde lenger — uten bilde er det bare tekst, og
       en tvunget høyde ville bare blitt luft. */
    .bv3-hero__feed {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        min-width: 0;
    }

    /* Arrangementskortet følger samme språk som artikkelkortene og kortraden:
       lys flate, samme radius, samme hover.
       UTEN BILDE (Andreas 17.09). Det startet som én stor mørk boks med teksten
       lagt OPPÅ bildet bak et gradient-overlay — vi måtte mørkne overlayet én
       gang bare for å redde lesbarheten. Bildene på arrangementene er uansett
       skjermbilder med egen tekst i, og i det første man møter på siden er de
       støy. Da er det bedre å la dato, tittel og tidspunkt stå alene. */
    .bv3-feedcard {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 2rem;
        background: var(--bv3-bg-alt);
        border-radius: var(--bv3-radius);
        text-decoration: none;
        color: inherit;
        transition: box-shadow 0.3s ease;
    }
    .bv3-feedcard:hover,
    .bv3-feedcard:focus-visible {
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        text-decoration: none;
        color: inherit;
    }

    .bv3-feedcard__flag {
        display: inline-block;
        margin-bottom: 1.25rem;
        padding: 0.3125rem 0.75rem;
        border-radius: 100px;
        background: var(--bv3-orange);
        color: #fff;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    /* Datoen er det som gjør dette til et arrangement og ikke en artikkel, og
       nå som bildet er borte er den også det eneste blikkfanget. Derfor større
       enn før. */
    .bv3-feedcard__date {
        display: flex;
        align-items: baseline;
        gap: 0.625rem;
        margin-bottom: 0.875rem;
    }
    .bv3-feedcard__day {
        font-size: 3rem;
        font-weight: 800;
        line-height: 1;
        color: var(--bv3-dark);
    }
    .bv3-feedcard__monthyear {
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.2;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--bv3-text-muted);
    }
    .bv3-feedcard__title {
        font-size: 1.375rem;
        font-weight: 700;
        line-height: 1.3;
        color: var(--bv3-dark);
        margin: 0 0 0.625rem;
    }
    .bv3-feedcard__lead {
        font-size: 1rem;
        line-height: 1.55;
        color: var(--bv3-text-secondary);
        margin: 0 0 0.75rem;
    }
    .bv3-feedcard__fakta {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--bv3-text-muted);
        margin-bottom: 1.25rem;
    }
    .bv3-feedcard__link {
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--bv3-orange);
    }

    /* Lenka til arkivet: dempet, så den ikke konkurrerer med «Meld deg på»
       inne i kortet. .bv3-hero__feed har gap: 1rem, så avstanden kommer derfra. */
    .bv3-hero__feed-mer {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--bv3-text-secondary);
        text-decoration: none;
        transition: color 0.2s ease;
    }
    .bv3-hero__feed-mer:hover,
    .bv3-hero__feed-mer:focus-visible {
        color: var(--bv3-orange);
        text-decoration: none;
    }

    @media (max-width: 768px) {
        .bv3-hero__feed { margin-top: 2rem; }
        .bv3-feedcard { padding: 1.5rem; }
        .bv3-feedcard__day { font-size: 2.5rem; }
        .bv3-feedcard__title { font-size: 1.1875rem; }
    }

    /* Tema-grafkortet: bildet fyller hele visual-flaten. */
    .bv3-fcard__visual--graf { padding: 0; }
    .bv3-fcard__visual--graf img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
    }
</style>


<main>

<!-- =============================================
     1. HERO (merged with Connecting the Dots)
     ============================================= -->
<section class="bv3-hero">
    <div class="bv3-container">
        <div class="bv3-hero__inner">
            <div>
                <span class="bv3-eyebrow">Norges bransjenettverk for BIM</span>
                <h1 class="bv3-h1">Kobler sammen byggenæringens digitale&nbsp;økosystem</h1>
                <p class="bv3-subtitle">BIM Verdi kartlegger koblingene mellom verktøy, standarder, foretak og temagrupper. Forstå det digitale økosystemet — ikke bare enkeltdelene.</p>
                <div class="bv3-hero__stats">
                    <span class="bv3-hero__stat"><span class="bv3-hero__stat-dot" style="background:#FF8B5E;"></span><strong><?php echo esc_html($total_tools); ?></strong> verktøy</span>
                    <span class="bv3-hero__stat"><span class="bv3-hero__stat-dot" style="background:#005898;"></span><strong><?php echo esc_html($total_companies); ?></strong> foretak</span>
                    <span class="bv3-hero__stat"><span class="bv3-hero__stat-dot" style="background:#6B9B37;"></span><strong><?php echo esc_html($total_sources); ?></strong> kilder</span>
                    <span class="bv3-hero__stat"><span class="bv3-hero__stat-dot" style="background:#0D9488;"></span><strong><?php echo esc_html($total_events); ?></strong> arrangementer</span>
                    <span class="bv3-hero__stat"><span class="bv3-hero__stat-dot" style="background:#EC4899;"></span><strong><?php echo esc_html($total_articles); ?></strong> artikler</span>
                </div>
                <div class="bv3-hero__actions">
                    <?php if (is_user_logged_in()): ?>
                        <a href="<?php echo esc_url(home_url('/koblinger/')); ?>" class="bv3-btn bv3-btn--dark">Utforsk koblingene <span aria-hidden="true">&rarr;</span></a>
                    <?php else: ?>
                        <a href="<?php echo esc_url(home_url('/logg-inn/?redirect_to=' . urlencode(home_url('/koblinger/')))); ?>" class="bv3-btn bv3-btn--dark">Logg inn for å utforske <span aria-hidden="true">&rarr;</span></a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(home_url('/registrer/')); ?>" class="bv3-btn bv3-btn--outline">Bli deltaker</a>
                </div>
            </div>

            <!-- Levende topp (Trello #352): arrangementet ALENE, med hele
                 kolonnen. Andreas 17.09: to kort side om side delte
                 oppmerksomheten på det første man møter, og arrangementet er
                 det eneste elementet her som har en frist. Artikkelen er flyttet
                 ned rett under logostripa, der «Fra nettverket» nå ligger — det
                 gir Bård endringen-fra-uke-til-uke han er ute etter, uten at
                 toppen blir to ting som konkurrerer.
                 Finnes ingen kommende arrangement, viser vi det siste som var.
                 En tom topp er verre enn et arrangement som nettopp har vært. -->
            <div class="bv3-hero__feed">
                <?php
                // Samme kilde ($events) og samme «kommende»-regel som resten av
                // forsiden, så toppen og kortraden aldri viser hver sin «neste».
                $hero_event      = !empty($events) ? $events[0] : null;
                $hero_event_obj  = null;
                $hero_kommende   = false;
                if ($hero_event) {
                    $hero_dato_raw  = get_field('arrangement_dato', $hero_event->ID) ?: get_field('dato', $hero_event->ID) ?: '';
                    $hero_event_obj = DateTime::createFromFormat('Y-m-d', $hero_dato_raw) ?: DateTime::createFromFormat('Ymd', $hero_dato_raw);
                    $hero_kommende  = (get_field('arrangement_status_toggle', $hero_event->ID) === 'kommende')
                        || ($hero_event_obj && $hero_event_obj->getTimestamp() >= strtotime('today'));
                }

                // Måned på norsk. Sidens locale er en_US, så wp_date('F') gir
                // «September» midt i en norsk forside — samme felle som
                // påminnelses-e-posten gikk i (bimverdi_paminnelse_dato_norsk).
                $bv_maneder = array('januar', 'februar', 'mars', 'april', 'mai', 'juni',
                                    'juli', 'august', 'september', 'oktober', 'november', 'desember');
                ?>

                <?php if ($hero_event): ?>
                    <?php
                    $he_ts    = $hero_event_obj ? $hero_event_obj->getTimestamp() : 0;
                    $he_dag   = $he_ts ? date('j', $he_ts) : '';
                    $he_maned = $he_ts ? $bv_maneder[(int) date('n', $he_ts) - 1] : '';
                    $he_ar    = $he_ts ? date('Y', $he_ts) : '';
                    $he_tema  = get_field('formal_tema', $hero_event->ID);

                    // Én linje med det praktiske. Nå som kortet har plassen, er
                    // klokkeslett og digitalt/fysisk det folk faktisk trenger for
                    // å avgjøre om de kan delta.
                    $he_fakta = array();
                    $he_start = get_field('tidspunkt_start', $hero_event->ID);
                    $he_slutt = get_field('tidspunkt_slutt', $hero_event->ID);
                    if ($he_start) {
                        $he_fakta[] = $he_slutt ? $he_start . '–' . $he_slutt : 'Fra ' . $he_start;
                    }
                    $he_type = get_field('arrangement_type', $hero_event->ID);
                    if ($he_type) {
                        $he_fakta[] = ucfirst((string) $he_type);
                    }
                    ?>
                    <a href="<?php echo esc_url(get_permalink($hero_event)); ?>" class="bv3-feedcard">
                        <span class="bv3-feedcard__flag"><?php echo $hero_kommende ? 'Neste arrangement' : 'Siste arrangement'; ?></span>
                        <div class="bv3-feedcard__date">
                            <span class="bv3-feedcard__day"><?php echo esc_html($he_dag); ?></span>
                            <span class="bv3-feedcard__monthyear"><?php echo esc_html($he_maned); ?><br><?php echo esc_html($he_ar); ?></span>
                        </div>
                        <h2 class="bv3-feedcard__title"><?php echo esc_html($hero_event->post_title); ?></h2>
                        <?php if ($he_tema): ?>
                            <p class="bv3-feedcard__lead"><?php echo esc_html($he_tema); ?></p>
                        <?php endif; ?>
                        <?php if ($he_fakta): ?>
                            <span class="bv3-feedcard__fakta"><?php echo esc_html(implode(' · ', $he_fakta)); ?></span>
                        <?php endif; ?>
                        <span class="bv3-feedcard__link"><?php echo $hero_kommende ? 'Meld deg på' : 'Se arrangementet'; ?> <span aria-hidden="true">&rarr;</span></span>
                    </a>
                <?php endif; ?>

                <?php // Arkivlenka står utenfor kortet med vilje: kortet er étt arrangement,
                      // og en lenke inni det ville konkurrert med «Meld deg på».
                      // get_post_type_archive_link fremfor en hardkodet /arrangement/, så
                      // den følger permalenke-oppsettet og ikke brekker hvis slug-en endres. ?>
                <a href="<?php echo esc_url(get_post_type_archive_link('arrangement') ?: home_url('/arrangement/')); ?>" class="bv3-hero__feed-mer">Se flere arrangement her <span aria-hidden="true">&rarr;</span></a>
            </div>
        </div>
    </div>
</section>


<!-- =============================================
     2. LOGO BAR (Trust)
     ============================================= -->
<?php if (!empty($logo_companies)): ?>
<section class="bv3-logobar">
    <div class="bv3-container">
        <div class="bv3-logobar__label">Disse foretakene er med i nettverket</div>
        <div class="bv3-logobar__scroll">
            <div class="bv3-logobar__track">
                <?php
                // Render twice for seamless loop
                for ($loop = 0; $loop < 2; $loop++):
                    foreach ($logo_companies as $company):
                        $logo_url = get_the_post_thumbnail_url($company->ID, 'medium');
                ?>
                    <a href="<?php echo esc_url(get_permalink($company->ID)); ?>" class="bv3-logobar__item">
                        <?php if ($logo_url): ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($company->post_title); ?>" loading="lazy">
                        <?php else: ?>
                            <?php echo esc_html($company->post_title); ?>
                        <?php endif; ?>
                    </a>
                <?php
                    endforeach;
                endfor;
                ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>


<!-- =============================================
     3. ARTIKLER
     Flyttet hit fra bunnen (Andreas 17.09). Bårds innvending mot den
     gamle forsiden var at en gjentakende besøkende må scrolle for å se at
     noe har endret seg. Arrangementet alene svarer bare halvt på det —
     det bytter sjelden. Artiklene er det som faktisk kommer til, og her
     ligger de rett under logostripa, altså første ting under heroen.
     ============================================= -->
<?php if (!empty($articles)): ?>
<section class="bv3-section">
    <div class="bv3-container">
        <div class="bv3-section-header bv3-section-header--split bv3-reveal">
            <div>
                <span class="bv3-eyebrow">Fra nettverket</span>
                <h2 class="bv3-h2" style="margin-bottom:0;">Siste artikler og innsikt</h2>
            </div>
            <a href="<?php echo esc_url(home_url('/artikler/')); ?>" class="bv3-section-header__link">Se alle &rarr;</a>
        </div>

        <?php
        // Norske månedsnavn. Sidens locale er en_US, så get_the_date('d. M Y')
        // ga «17. Sep 2026» midt i en norsk forside. Samme felle som heroen og
        // påminnelses-e-posten gikk i.
        if (!isset($bv_maneder)) {
            $bv_maneder = array('januar', 'februar', 'mars', 'april', 'mai', 'juni',
                                'juli', 'august', 'september', 'oktober', 'november', 'desember');
        }
        ?>

        <div class="bv3-articles__grid bv3-reveal">
            <?php foreach (array_slice($articles, 0, 3) as $bv_art): ?>
                <?php
                $ba_thumb = get_the_post_thumbnail_url($bv_art->ID, 'medium_large');
                $ba_ts    = get_post_timestamp($bv_art);
                $ba_dato  = $ba_ts ? sprintf('%d. %s %d', (int) date('j', $ba_ts), $bv_maneder[(int) date('n', $ba_ts) - 1], (int) date('Y', $ba_ts)) : '';
                $ba_desc  = get_field('artikkel_ingress', $bv_art->ID)
                    ?: wp_trim_words(strip_tags($bv_art->post_excerpt ?: $bv_art->post_content), 22);

                // Deltakermerket avgjøres av samme funksjon som artikkellista og
                // nyhetsbrevet — den ser kun på foretaksfeltet på artikkelen og
                // holder Verdinettverk AS utenfor, så redaksjonens egne artikler
                // ikke merkes som om de kom fra en deltaker.
                $ba_foretak_id = 0;
                if (function_exists('bimverdi_artikkel_er_deltakerartikkel')
                    && bimverdi_artikkel_er_deltakerartikkel($bv_art->ID)) {
                    $ba_foretak_id = (int) bimverdi_artikkel_foretak_id($bv_art->ID, false);
                }
                ?>
                <a href="<?php echo esc_url(get_permalink($bv_art)); ?>" class="bv3-article-card">
                    <div class="bv3-article-card__img">
                        <?php if ($ba_thumb): ?>
                            <?php // Tom alt med vilje: tittelen står rett under og er lenketeksten,
                                  // så bildet er dekorativt. Det hindrer også at alt-teksten renner
                                  // utover ruta hvis bildefila mangler — slik som 3326 lokalt. ?>
                            <img src="<?php echo esc_url($ba_thumb); ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <svg style="width:40px;height:40px;color:#A8A29E;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                        <?php endif; ?>
                    </div>
                    <div class="bv3-article-card__body">
                        <span class="bv3-article-card__deltaker"><?php if ($ba_foretak_id): ?>Fra deltakerforetaket <?php echo esc_html(get_the_title($ba_foretak_id)); ?><?php endif; ?></span>
                        <div class="bv3-article-card__date"><?php echo esc_html($ba_dato); ?></div>
                        <h3 class="bv3-article-card__title"><?php echo esc_html($bv_art->post_title); ?></h3>
                        <p class="bv3-article-card__desc"><?php echo esc_html($ba_desc); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>


<!-- =============================================
     4. FEATURE CARDS
     ============================================= -->
<section class="bv3-section">
    <div class="bv3-container">
        <div class="bv3-features__grid">

            <!-- Card: Verktøykatalogen -->
            <a href="<?php echo esc_url(home_url('/verktoy/')); ?>" class="bv3-fcard">
                <div class="bv3-fcard__visual">
                    <span class="bv3-fcard__badge"><?php echo esc_html($total_tools); ?> verktøy</span>
                    <div class="bv3-visual-grid">
                        <div class="bv3-visual-grid__item bv3-visual-grid__item--accent">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
                        </div>
                        <div class="bv3-visual-grid__item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="bv3-visual-grid__item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div class="bv3-visual-grid__item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        </div>
                        <div class="bv3-visual-grid__item bv3-visual-grid__item--accent">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                        </div>
                        <div class="bv3-visual-grid__item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                    </div>
                </div>
                <div class="bv3-fcard__content">
                    <span class="bv3-eyebrow">Verktøykatalogen</span>
                    <h3 class="bv3-fcard__title">Programvare og digitale tjenester for byggenæringen</h3>
                    <p class="bv3-fcard__desc">Utforsk <?php echo esc_html($total_tools); ?>+ verktøy kategorisert etter bruksområde, med koblinger til foretak og standarder.</p>
                    <span class="bv3-fcard__link">Se alle verktøy <span aria-hidden="true">&rarr;</span></span>
                </div>
            </a>

            <!-- Card: Kunnskapsbiblioteket -->
            <a href="<?php echo esc_url(home_url('/kunnskapskilder/')); ?>" class="bv3-fcard">
                <div class="bv3-fcard__visual">
                    <span class="bv3-fcard__badge"><?php echo esc_html($total_sources); ?> kilder</span>
                    <!-- Document stack illustration -->
                    <div style="display:flex;flex-direction:column;gap:8px;align-items:center;">
                        <div style="display:flex;gap:8px;">
                            <span style="display:inline-block;padding:4px 12px;border-radius:100px;font-size:11px;font-weight:600;background:#005898;color:#fff;">Standard</span>
                            <span style="display:inline-block;padding:4px 12px;border-radius:100px;font-size:11px;font-weight:600;background:#6B9B37;color:#fff;">Veiledning</span>
                            <span style="display:inline-block;padding:4px 12px;border-radius:100px;font-size:11px;font-weight:600;background:#D97706;color:#fff;">Forskrift</span>
                        </div>
                        <div style="display:flex;gap:8px;margin-top:4px;">
                            <span style="display:inline-block;padding:4px 12px;border-radius:100px;font-size:11px;font-weight:600;background:#5E36FE;color:#fff;">EU-forordning</span>
                            <span style="display:inline-block;padding:4px 12px;border-radius:100px;font-size:11px;font-weight:600;background:#0D9488;color:#fff;">Nettressurs</span>
                        </div>
                        <!-- Document shapes -->
                        <div style="display:flex;gap:10px;margin-top:16px;">
                            <div style="width:70px;height:90px;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.06);position:relative;padding:10px;">
                                <div style="width:100%;height:6px;background:#E7E5E4;border-radius:3px;margin-bottom:6px;"></div>
                                <div style="width:80%;height:6px;background:#E7E5E4;border-radius:3px;margin-bottom:6px;"></div>
                                <div style="width:60%;height:6px;background:#E7E5E4;border-radius:3px;"></div>
                                <div style="position:absolute;top:6px;right:6px;width:16px;height:16px;background:#005898;border-radius:4px;opacity:0.2;"></div>
                            </div>
                            <div style="width:70px;height:90px;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.06);transform:translateY(12px);padding:10px;">
                                <div style="width:100%;height:6px;background:#E7E5E4;border-radius:3px;margin-bottom:6px;"></div>
                                <div style="width:70%;height:6px;background:#E7E5E4;border-radius:3px;margin-bottom:6px;"></div>
                                <div style="width:90%;height:6px;background:#E7E5E4;border-radius:3px;"></div>
                            </div>
                            <div style="width:70px;height:90px;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.06);transform:translateY(-4px);padding:10px;">
                                <div style="width:100%;height:6px;background:#E7E5E4;border-radius:3px;margin-bottom:6px;"></div>
                                <div style="width:65%;height:6px;background:#E7E5E4;border-radius:3px;margin-bottom:6px;"></div>
                                <div style="width:85%;height:6px;background:#E7E5E4;border-radius:3px;"></div>
                                <div style="position:absolute;top:6px;right:6px;width:16px;height:16px;background:#6B9B37;border-radius:4px;opacity:0.2;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bv3-fcard__content">
                    <span class="bv3-eyebrow">Kunnskapsbiblioteket</span>
                    <h3 class="bv3-fcard__title">Standarder, veiledere og forskrifter på ett sted</h3>
                    <p class="bv3-fcard__desc">Kuratert samling av <?php echo esc_html($total_sources); ?> kunnskapskilder — alltid oppdatert og koblet til relevante verktøy.</p>
                    <span class="bv3-fcard__link">Utforsk kunnskapskilder <span aria-hidden="true">&rarr;</span></span>
                </div>
            </a>

            <!-- Card: Dynamisk tema-graf (Trello #352) -->
            <?php
            // Bildet er et fast skjermbilde av /demo/temagruppe-graf/, ikke grafen
            // selv: den er tung å kjøre, og Andreas avgjorde 15.09 at den uansett
            // er for liten til å utforskes her — «det må bli et bilde av den
            // nettverksvisningen og så en klikk inn der i stedet». Bildet ligger i
            // temaet, ikke i mediebiblioteket, så det følger med deploy og kan tas
            // på nytt når grafen endrer seg.
            ?>
            <a href="<?php echo esc_url(home_url('/demo/temagruppe-graf/')); ?>" class="bv3-fcard">
                <div class="bv3-fcard__visual bv3-fcard__visual--graf">
                    <span class="bv3-fcard__badge">6 temagrupper</span>
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/temagruppe-graf.jpg'); ?>"
                         alt="Nettverksgraf der de seks temagruppene er knyttet til foretak, verktøy, kunnskapskilder, arrangementer og artikler"
                         loading="lazy" width="820" height="620">
                </div>
                <div class="bv3-fcard__content">
                    <span class="bv3-eyebrow">Tema-graf</span>
                    <h3 class="bv3-fcard__title">Dynamisk tema-graf med semantiske koblinger</h3>
                    <p class="bv3-fcard__desc">Se hvordan temagrupper, foretak, verktøy og kunnskapskilder henger sammen. Klikk en node for å følge koblingene videre.</p>
                    <span class="bv3-fcard__link">Utforsk grafen <span aria-hidden="true">&rarr;</span></span>
                </div>
            </a>


        </div>
    </div>
</section>


<!-- =============================================
     5. TEMAGRUPPER — Hierarchy Layout
     ============================================= -->
<?php
// Lifecycle groups (building lifecycle order)
$lifecycle = [
    ['title' => 'ByggesaksBIM', 'desc' => 'Digitalisering av byggesaksprosessen og bruk av BIM mot offentlige myndigheter.',    'slug' => 'byggesaksbim', 'color' => '#005898', 'num' => '1', 'phase' => 'Byggesak'],
    ['title' => 'ProsjektBIM',  'desc' => 'Beste praksis for BIM-koordinering og ledelse i store byggeprosjekter.',              'slug' => 'prosjektbim',  'color' => '#6B9B37', 'num' => '2', 'phase' => 'Prosjekt'],
    ['title' => 'EiendomsBIM',  'desc' => 'FDV-dokumentasjon og bruk av BIM i driftsfasen for eiendomsforvaltere.',              'slug' => 'eiendomsbim',  'color' => '#5E36FE', 'num' => '3', 'phase' => 'Drift'],
    ['title' => 'SirkBIM',      'desc' => 'Sirkulær økonomi, materialgjenbruk og ombruk i byggenæringen med digitale verktøy.', 'slug' => 'sirkbim',      'color' => '#FF8B5E', 'num' => '4', 'phase' => 'Gjenbruk'],
];
// Support groups
$support = [
    ['title' => 'BIMtech',  'desc' => 'Kunstig intelligens, digitale verktøy og IDS for maskinvalidering av modeller.',  'slug' => 'bimtech',  'color' => '#D97706'],
    ['title' => 'MiljøBIM', 'desc' => 'Datamaler, begrepsordbøker (bSDD) og Digital Link fra GS1.', 'slug' => 'miljobim', 'color' => '#0D9488'],
];
?>
<section class="bv3-section bv3-dotgrid" style="padding-top:4rem;padding-bottom:4rem;">
    <div class="bv3-container">
        <div class="bv3-section-header bv3-section-header--center bv3-reveal">
            <span class="bv3-eyebrow">Temagrupper</span>
            <h2 class="bv3-h2">Byggets livsløp — fire faser, to støttegrupper</h2>
            <p class="bv3-subtitle">Hvert tema dekker en fase i byggets livssyklus. Støttegruppene leverer kompetanse på tvers.</p>
        </div>

        <!-- Desktop: Hierarchy layout -->
        <div class="bv3-tg-hierarchy bv3-reveal">

            <!-- Row 1: BIM Verdi hub -->
            <div class="bv3-tg-hub">
                <a href="<?php echo esc_url(home_url('/temagrupper/')); ?>" class="bv3-tg-hub__node">
                    <span class="bv3-tg-hub__logo">BIM</span>
                    <span style="font-size:0.5625rem;opacity:0.8;font-weight:600;">VERDI</span>
                </a>
            </div>

            <!-- Row label: Livssyklus -->
            <div class="bv3-tg-row-label"><span>Byggets livssyklus</span></div>

            <!-- Row 2: 4 Lifecycle temagrupper -->
            <div class="bv3-tg-lifecycle">
                <?php foreach ($lifecycle as $group): ?>
                <a href="<?php echo esc_url(home_url('/temagrupper/' . $group['slug'] . '/')); ?>"
                   class="bv3-tg-card2">
                    <span class="bv3-tg-card2__num" style="background:<?php echo esc_attr($group['color']); ?>;"><?php echo esc_html($group['num']); ?></span>
                    <div class="bv3-tg-card2__dot" style="background:<?php echo esc_attr($group['color']); ?>;"></div>
                    <div class="bv3-tg-card2__title"><?php echo esc_html($group['title']); ?></div>
                    <div class="bv3-tg-card2__desc"><?php echo esc_html($group['desc']); ?></div>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Lifecycle flow arrows -->
            <div class="bv3-tg-lifecycle__arrows">
                <svg width="100%" height="40" viewBox="0 0 960 40" preserveAspectRatio="xMidYMid meet" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Forward arrows: 1→2→3→4 -->
                    <line x1="145" y1="20" x2="335" y2="20" stroke="#A8A29E" stroke-width="1.5" stroke-dasharray="4 3"/>
                    <polygon points="335,16 343,20 335,24" fill="#A8A29E"/>
                    <line x1="385" y1="20" x2="575" y2="20" stroke="#A8A29E" stroke-width="1.5" stroke-dasharray="4 3"/>
                    <polygon points="575,16 583,20 575,24" fill="#A8A29E"/>
                    <line x1="625" y1="20" x2="815" y2="20" stroke="#A8A29E" stroke-width="1.5" stroke-dasharray="4 3"/>
                    <polygon points="815,16 823,20 815,24" fill="#A8A29E"/>

                    <!-- Return arrow: 4 back to 1 (curved above) -->
                    <path d="M 860 20 C 860 -30, 100 -30, 100 20" stroke="#FF8B5E" stroke-width="1.5" stroke-dasharray="5 3" fill="none" opacity="0.5"/>
                    <polygon points="100,16 92,20 100,24" fill="#FF8B5E" opacity="0.5"/>
                </svg>
            </div>

            <!-- Row label: Støttegrupper -->
            <div class="bv3-tg-row-label" style="margin-top:1rem;"><span>Støttegrupper — leverer kompetanse på tvers</span></div>

            <!-- Row 3: 2 Support groups -->
            <div class="bv3-tg-support">
                <?php foreach ($support as $group): ?>
                <a href="<?php echo esc_url(home_url('/temagrupper/' . $group['slug'] . '/')); ?>"
                   class="bv3-tg-card2 bv3-tg-card2--support">
                    <div class="bv3-tg-card2__dot" style="background:<?php echo esc_attr($group['color']); ?>;"></div>
                    <div class="bv3-tg-card2__title"><?php echo esc_html($group['title']); ?></div>
                    <div class="bv3-tg-card2__desc"><?php echo esc_html($group['desc']); ?></div>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- SVG connections: hub→lifecycle, support→lifecycle -->
            <svg class="bv3-tg-hierarchy__svg" viewBox="0 0 960 600" preserveAspectRatio="xMidYMid meet">
                <!-- Hub to lifecycle cards (4 lines down) -->
                <line x1="480" y1="72" x2="120" y2="145" stroke="#005898" stroke-width="1" opacity="0.2"/>
                <line x1="480" y1="72" x2="360" y2="145" stroke="#6B9B37" stroke-width="1" opacity="0.2"/>
                <line x1="480" y1="72" x2="600" y2="145" stroke="#5E36FE" stroke-width="1" opacity="0.2"/>
                <line x1="480" y1="72" x2="840" y2="145" stroke="#FF8B5E" stroke-width="1" opacity="0.2"/>

                <!-- Support to lifecycle (dashed upward lines) -->
                <!-- BIMtech connects to all 4 -->
                <line x1="340" y1="500" x2="120" y2="310" stroke="#D97706" stroke-width="0.75" opacity="0.2"/>
                <line x1="340" y1="500" x2="360" y2="310" stroke="#D97706" stroke-width="0.75" opacity="0.2"/>
                <line x1="340" y1="500" x2="600" y2="310" stroke="#D97706" stroke-width="0.75" opacity="0.15"/>
                <line x1="340" y1="500" x2="840" y2="310" stroke="#D97706" stroke-width="0.75" opacity="0.12"/>
                <!-- MiljøBIM connects to all 4 -->
                <line x1="620" y1="500" x2="120" y2="310" stroke="#0D9488" stroke-width="0.75" opacity="0.12"/>
                <line x1="620" y1="500" x2="360" y2="310" stroke="#0D9488" stroke-width="0.75" opacity="0.15"/>
                <line x1="620" y1="500" x2="600" y2="310" stroke="#0D9488" stroke-width="0.75" opacity="0.2"/>
                <line x1="620" y1="500" x2="840" y2="310" stroke="#0D9488" stroke-width="0.75" opacity="0.2"/>
            </svg>
        </div>

        <!-- Mobile: Structured list fallback -->
        <div class="bv3-tg-mobile">
            <span class="bv3-tg-mobile__label">Byggets livssyklus</span>
            <?php foreach ($lifecycle as $group): ?>
            <a href="<?php echo esc_url(home_url('/temagrupper/' . $group['slug'] . '/')); ?>"
               class="bv3-tg-card" style="border-top: 3px solid <?php echo esc_attr($group['color']); ?>;">
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
                    <span style="width:20px;height:20px;border-radius:50%;background:<?php echo esc_attr($group['color']); ?>;color:#fff;font-size:0.6875rem;font-weight:700;display:flex;align-items:center;justify-content:center;"><?php echo esc_html($group['num']); ?></span>
                    <span style="font-size:0.6875rem;color:var(--bv3-text-muted);font-weight:500;"><?php echo esc_html($group['phase']); ?></span>
                </div>
                <h3 class="bv3-tg-card__title"><?php echo esc_html($group['title']); ?></h3>
                <p class="bv3-tg-card__desc"><?php echo esc_html($group['desc']); ?></p>
            </a>
            <?php endforeach; ?>
            <span class="bv3-tg-mobile__label">Støttegrupper</span>
            <?php foreach ($support as $group): ?>
            <a href="<?php echo esc_url(home_url('/temagrupper/' . $group['slug'] . '/')); ?>"
               class="bv3-tg-card" style="border-top: 3px dashed <?php echo esc_attr($group['color']); ?>;">
                <div style="width:10px;height:10px;border-radius:50%;background:<?php echo esc_attr($group['color']); ?>;margin-bottom:0.5rem;"></div>
                <h3 class="bv3-tg-card__title"><?php echo esc_html($group['title']); ?></h3>
                <p class="bv3-tg-card__desc"><?php echo esc_html($group['desc']); ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- =============================================
     8. CTA (Dark section)
     ============================================= -->
<section class="bv3-cta">
    <div class="bv3-container bv3-reveal">
        <span class="bv3-eyebrow" style="color:rgba(255,255,255,.4);">Bli med</span>
        <h2 class="bv3-h2">Klar for å koble deg<br>på nettverket?</h2>
        <p class="bv3-subtitle">Bli deltaker i BIM Verdi og få tilgang til verktøykatalogen, kunnskapsbiblioteket, temagrupper og et nettverk av over <?php echo esc_html($total_companies); ?> foretak.</p>

        <div style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;">
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo esc_url(home_url('/koblinger/')); ?>" class="bv3-btn bv3-btn--white">Utforsk koblingene <span aria-hidden="true">&rarr;</span></a>
            <?php else: ?>
                <a href="<?php echo esc_url(home_url('/registrer/')); ?>" class="bv3-btn bv3-btn--white">Bli deltaker <span aria-hidden="true">&rarr;</span></a>
            <?php endif; ?>
            <a href="<?php echo esc_url(home_url('/registrer/')); ?>" class="bv3-btn bv3-btn--ghost-white">Les mer om deltakelse</a>
        </div>

        <div class="bv3-cta__stats">
            <div>
                <div class="bv3-cta__stat-num"><?php echo esc_html($total_companies); ?></div>
                <div class="bv3-cta__stat-label">Deltakere</div>
            </div>
            <div>
                <div class="bv3-cta__stat-num"><?php echo esc_html($total_tools); ?></div>
                <div class="bv3-cta__stat-label">Verktøy</div>
            </div>
            <div>
                <div class="bv3-cta__stat-num"><?php echo esc_html($total_sources); ?></div>
                <div class="bv3-cta__stat-label">Kunnskapskilder</div>
            </div>
            <div>
                <div class="bv3-cta__stat-num">6</div>
                <div class="bv3-cta__stat-label">Temagrupper</div>
            </div>
            <div>
                <div class="bv3-cta__stat-num">2012</div>
                <div class="bv3-cta__stat-label">Etablert</div>
            </div>
        </div>
    </div>
</section>


</main>

<!-- Scroll reveal -->
<script>
(function() {
    const els = document.querySelectorAll('.bv3-reveal, .bv3-reveal-children');

    // Immediately reveal elements already in viewport
    requestAnimationFrame(() => {
        els.forEach(el => {
            const rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight + 100) {
                el.classList.add('visible');
            }
        });
    });

    // Observer for elements below fold
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -20px 0px' });

    els.forEach(el => observer.observe(el));
})();
</script>

<?php get_footer(); ?>
