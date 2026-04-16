<?php
/**
 * Front Page Template - v3 Redesign
 *
 * GitBook-inspired: network flow motif, eyebrow labels,
 * visual feature cards, layout variation, trust bar.
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
    $events = get_posts([
        'post_type'      => 'arrangement',
        'posts_per_page' => 4,
        'post_status'    => 'publish',
        'meta_key'       => 'arrangement_dato',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
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
    .bv3-section { padding: 5rem 0; }
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
        padding: 5rem 0 3rem;
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
        padding: 2.5rem 0;
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
        margin-bottom: 1.5rem;
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
    .bv3-articles__grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }
    @media (max-width: 768px) {
        .bv3-articles__grid { grid-template-columns: 1fr; }
    }
    .bv3-article-featured {
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .bv3-article-featured:hover { text-decoration: none; color: inherit; }
    .bv3-article-featured__img {
        aspect-ratio: 4/3;
        border-radius: var(--bv3-radius);
        overflow: hidden;
        margin-bottom: 1.25rem;
        background: var(--bv3-bg-alt);
    }
    .bv3-article-featured__img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }
    .bv3-article-featured:hover .bv3-article-featured__img img {
        transform: scale(1.03);
    }
    .bv3-article-stacked {
        display: flex;
        flex-direction: column;
        gap: 0;
    }
    .bv3-article-row {
        display: flex;
        gap: 1rem;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid var(--bv3-border);
        text-decoration: none;
        color: inherit;
        transition: background 0.15s;
    }
    .bv3-article-row:last-child { border-bottom: none; }
    .bv3-article-row:hover { text-decoration: none; color: inherit; }
    .bv3-article-row__thumb {
        width: 100px;
        height: 68px;
        border-radius: 10px;
        overflow: hidden;
        flex-shrink: 0;
        background: var(--bv3-bg-alt);
    }
    .bv3-article-row__thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .bv3-article-row__info { flex-grow: 1; min-width: 0; }
    .bv3-article-row__date {
        font-size: 0.75rem;
        color: var(--bv3-text-muted);
        margin-bottom: 0.25rem;
    }
    .bv3-article-row__title {
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--bv3-dark);
        line-height: 1.35;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* ---- CTA / DARK SECTION ---- */
    .bv3-cta {
        background: var(--bv3-dark);
        padding: 6rem 0;
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
        margin-bottom: 3rem;
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

            <!-- Network illustration with entity count cards -->
            <div class="bv3-hero__visual">
                <svg class="bv3-network-svg" viewBox="0 0 480 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Orbital rings -->
                    <circle cx="240" cy="200" r="150" stroke="#E7E5E4" stroke-width="1" stroke-dasharray="4 4" opacity="0.4"/>
                    <circle cx="240" cy="200" r="85" stroke="#E7E5E4" stroke-width="1" stroke-dasharray="4 4" opacity="0.25"/>

                    <!-- Connection lines from center to nodes -->
                    <line x1="240" y1="200" x2="105" y2="80" stroke="#FF8B5E" stroke-width="1.5" class="dash-line" opacity="0.3"/>
                    <line x1="240" y1="200" x2="390" y2="95" stroke="#005898" stroke-width="1.5" class="dash-line" opacity="0.3"/>
                    <line x1="240" y1="200" x2="85" y2="310" stroke="#6B9B37" stroke-width="1.5" class="dash-line" opacity="0.3"/>
                    <line x1="240" y1="200" x2="395" y2="310" stroke="#0D9488" stroke-width="1.5" class="dash-line" opacity="0.3"/>
                    <line x1="240" y1="200" x2="340" y2="50" stroke="#D97706" stroke-width="1.5" class="dash-line" opacity="0.3"/>
                    <line x1="240" y1="200" x2="155" y2="365" stroke="#5E36FE" stroke-width="1.5" class="dash-line" opacity="0.3"/>
                    <line x1="240" y1="200" x2="440" y2="195" stroke="#EC4899" stroke-width="1.5" class="dash-line" opacity="0.3"/>

                    <!-- Cross-connections (ecosystem links) -->
                    <line x1="105" y1="80" x2="340" y2="50" stroke="#E7E5E4" stroke-width="0.75" class="dash-line" opacity="0.2"/>
                    <line x1="390" y1="95" x2="395" y2="310" stroke="#E7E5E4" stroke-width="0.75" class="dash-line" opacity="0.2"/>
                    <line x1="85" y1="310" x2="155" y2="365" stroke="#E7E5E4" stroke-width="0.75" class="dash-line" opacity="0.2"/>
                    <line x1="105" y1="80" x2="85" y2="310" stroke="#E7E5E4" stroke-width="0.75" class="dash-line" opacity="0.15"/>

                    <!-- Decorative scatter dots -->
                    <circle cx="170" cy="140" r="2.5" fill="#E7E5E4"/>
                    <circle cx="310" cy="150" r="2" fill="#E7E5E4"/>
                    <circle cx="180" cy="270" r="2" fill="#E7E5E4"/>
                    <circle cx="305" cy="260" r="2.5" fill="#E7E5E4"/>
                    <circle cx="255" cy="105" r="2" fill="#E7E5E4"/>
                    <circle cx="145" cy="205" r="2" fill="#E7E5E4"/>
                    <circle cx="345" cy="205" r="2" fill="#E7E5E4"/>
                    <circle cx="220" cy="315" r="2" fill="#E7E5E4"/>

                    <!-- Entity card: Verktøy (top-left) -->
                    <rect x="48" y="48" width="114" height="56" rx="10" fill="#fff" stroke="#E7E5E4" stroke-width="1"/>
                    <circle cx="72" cy="72" r="8" fill="#FF8B5E" opacity="0.15"/>
                    <circle cx="72" cy="72" r="4" fill="#FF8B5E"/>
                    <text x="86" y="69" fill="#111827" font-size="12" font-weight="700"><?php echo esc_html($total_tools); ?></text>
                    <text x="86" y="82" fill="#A8A29E" font-size="9" font-weight="500">verktøy</text>

                    <!-- Entity card: Foretak (top-right) -->
                    <rect x="330" y="58" width="114" height="56" rx="10" fill="#fff" stroke="#E7E5E4" stroke-width="1"/>
                    <circle cx="354" cy="82" r="8" fill="#005898" opacity="0.15"/>
                    <circle cx="354" cy="82" r="4" fill="#005898"/>
                    <text x="368" y="79" fill="#111827" font-size="12" font-weight="700"><?php echo esc_html($total_companies); ?></text>
                    <text x="368" y="92" fill="#A8A29E" font-size="9" font-weight="500">foretak</text>

                    <!-- Entity card: Kilder (bottom-left) -->
                    <rect x="30" y="278" width="114" height="56" rx="10" fill="#fff" stroke="#E7E5E4" stroke-width="1"/>
                    <circle cx="54" cy="302" r="8" fill="#6B9B37" opacity="0.15"/>
                    <circle cx="54" cy="302" r="4" fill="#6B9B37"/>
                    <text x="68" y="299" fill="#111827" font-size="12" font-weight="700"><?php echo esc_html($total_sources); ?></text>
                    <text x="68" y="312" fill="#A8A29E" font-size="9" font-weight="500">kilder</text>

                    <!-- Entity card: Arrangementer (bottom-right) -->
                    <rect x="338" y="278" width="114" height="56" rx="10" fill="#fff" stroke="#E7E5E4" stroke-width="1"/>
                    <circle cx="362" cy="302" r="8" fill="#0D9488" opacity="0.15"/>
                    <circle cx="362" cy="302" r="4" fill="#0D9488"/>
                    <text x="376" y="299" fill="#111827" font-size="12" font-weight="700"><?php echo esc_html($total_events); ?></text>
                    <text x="376" y="312" fill="#A8A29E" font-size="9" font-weight="500">eventer</text>

                    <!-- Small node: Temagrupper (top) -->
                    <circle cx="340" cy="50" r="14" fill="#D97706" opacity="0.12"/>
                    <circle cx="340" cy="50" r="8" fill="#D97706"/>
                    <text x="340" y="36" text-anchor="middle" fill="#A8A29E" font-size="9" font-weight="500">Temagrupper</text>

                    <!-- Small node: Standarder (bottom) -->
                    <circle cx="155" cy="365" r="14" fill="#5E36FE" opacity="0.12"/>
                    <circle cx="155" cy="365" r="8" fill="#5E36FE"/>
                    <text x="155" y="390" text-anchor="middle" fill="#A8A29E" font-size="9" font-weight="500">Standarder</text>

                    <!-- Small node: Artikler (right) -->
                    <circle cx="440" cy="195" r="14" fill="#EC4899" opacity="0.12"/>
                    <circle cx="440" cy="195" r="8" fill="#EC4899"/>
                    <text x="440" y="180" text-anchor="middle" fill="#A8A29E" font-size="9" font-weight="500">Artikler</text>

                    <!-- Central BIM Verdi node -->
                    <circle cx="240" cy="200" r="36" fill="#FF8B5E" opacity="0.08" class="node-pulse"/>
                    <circle cx="240" cy="200" r="24" fill="#FF8B5E" opacity="0.15"/>
                    <circle cx="240" cy="200" r="16" fill="#FF8B5E"/>
                    <text x="240" y="204" text-anchor="middle" fill="#fff" font-size="9" font-weight="700">BV</text>
                </svg>
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
     3. FEATURE CARDS (was 4)
     ============================================= -->
<section class="bv3-section">
    <div class="bv3-container">
        <div class="bv3-features__grid">

            <!-- Card: Arrangementer -->
            <?php
            // Get the next upcoming event specifically
            $next_event = !empty($events) ? $events[0] : null;
            $has_upcoming = false;
            if ($next_event) {
                $next_date_raw = get_field('arrangement_dato', $next_event->ID) ?: get_field('dato', $next_event->ID) ?: '';
                $next_date_obj = DateTime::createFromFormat('Y-m-d', $next_date_raw) ?: DateTime::createFromFormat('Ymd', $next_date_raw);
                $next_status = get_field('arrangement_status_toggle', $next_event->ID);
                $has_upcoming = ($next_status === 'kommende') || ($next_date_obj && $next_date_obj->getTimestamp() >= strtotime('today'));
            }
            ?>
            <?php if ($has_upcoming && $next_event): ?>
                <?php
                $ne_ts = $next_date_obj ? $next_date_obj->getTimestamp() : 0;
                $ne_day = $ne_ts ? wp_date('d', $ne_ts) : '';
                $ne_month = $ne_ts ? wp_date('F', $ne_ts) : '';
                $ne_year = $ne_ts ? wp_date('Y', $ne_ts) : '';
                $ne_tg_terms = get_the_terms($next_event->ID, 'temagruppe');
                $ne_tg_name = ($ne_tg_terms && !is_wp_error($ne_tg_terms)) ? $ne_tg_terms[0]->name : '';
                $ne_tg_color = isset($tg_colors[$ne_tg_name]) ? $tg_colors[$ne_tg_name] : 'var(--bv3-orange)';
                $ne_format = get_field('arrangement_type', $next_event->ID) ?: '';
                ?>
                <?php $ne_featured_img = get_the_post_thumbnail_url($next_event->ID, 'large'); ?>
                <a href="<?php echo esc_url(get_permalink($next_event)); ?>" class="bv3-fcard bv3-fcard--event">
                    <div class="bv3-fcard__visual"<?php if ($ne_featured_img): ?> style="background-image:url('<?php echo esc_url($ne_featured_img); ?>')"<?php endif; ?>>
                        <span class="bv3-fcard__badge" style="background:var(--bv3-orange);">Kommende</span>
                        <div class="bv3-fcard--event__date-block">
                            <span class="bv3-fcard--event__day"><?php echo esc_html($ne_day); ?></span>
                            <span class="bv3-fcard--event__monthyear"><?php echo esc_html($ne_month); ?><br><?php echo esc_html($ne_year); ?></span>
                        </div>
                        <div class="bv3-fcard--event__title-preview"><?php echo esc_html($next_event->post_title); ?></div>
                        <?php if ($ne_tg_name): ?>
                        <span class="bv3-fcard--event__tag" style="background:color-mix(in srgb, <?php echo esc_attr($ne_tg_color); ?> 20%, transparent);color:<?php echo esc_attr($ne_tg_color); ?>;"><?php echo esc_html($ne_tg_name); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="bv3-fcard__content">
                        <span class="bv3-eyebrow">Neste arrangement</span>
                        <h3 class="bv3-fcard__title"><?php echo esc_html($next_event->post_title); ?></h3>
                        <p class="bv3-fcard__desc"><?php echo esc_html(wp_trim_words(strip_tags($next_event->post_content), 18)); ?></p>
                        <span class="bv3-fcard__link">Meld deg på <span aria-hidden="true">&rarr;</span></span>
                    </div>
                </a>
            <?php else: ?>
                <a href="<?php echo esc_url(home_url('/arrangement/')); ?>" class="bv3-fcard bv3-fcard--event-archive">
                    <div class="bv3-fcard__visual">
                        <span class="bv3-fcard__badge"><?php echo esc_html($total_events); ?> arrangementer</span>
                        <svg style="width:64px;height:64px;color:var(--bv3-text-muted);opacity:0.5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span style="font-size:0.875rem;color:var(--bv3-text-secondary);font-weight:500;">Ingen kommende arrangementer</span>
                    </div>
                    <div class="bv3-fcard__content">
                        <span class="bv3-eyebrow">Arrangementer</span>
                        <h3 class="bv3-fcard__title">Workshops, seminarer og nettverks&shy;møter</h3>
                        <p class="bv3-fcard__desc">Se hva som har skjedd — <?php echo esc_html($total_events); ?> arrangementer med presentasjoner, opptak og materiell.</p>
                        <span class="bv3-fcard__link">Se alle arrangementer <span aria-hidden="true">&rarr;</span></span>
                    </div>
                </a>
            <?php endif; ?>

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
     6. ARTIKLER
     ============================================= -->
<?php if (!empty($articles)): ?>
<section class="bv3-section bv3-section--alt">
    <div class="bv3-container">
        <div class="bv3-section-header bv3-section-header--split bv3-reveal">
            <div>
                <span class="bv3-eyebrow">Fra nettverket</span>
                <h2 class="bv3-h2" style="margin-bottom:0;">Siste artikler og innsikt</h2>
            </div>
            <a href="<?php echo esc_url(home_url('/artikler/')); ?>" class="bv3-section-header__link">Se alle &rarr;</a>
        </div>

        <?php
        $featured = $articles[0];
        $rest = array_slice($articles, 1);
        $featured_thumb = get_the_post_thumbnail_url($featured->ID, 'large');
        $featured_date = get_the_date('d. M Y', $featured->ID);
        ?>

        <div class="bv3-articles__grid bv3-reveal">
            <!-- Featured article -->
            <a href="<?php echo esc_url(get_permalink($featured)); ?>" class="bv3-article-featured">
                <div class="bv3-article-featured__img">
                    <?php if ($featured_thumb): ?>
                    <img src="<?php echo esc_url($featured_thumb); ?>" alt="<?php echo esc_attr($featured->post_title); ?>" loading="lazy">
                    <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                        <svg style="width:48px;height:48px;color:#A8A29E;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                    </div>
                    <?php endif; ?>
                </div>
                <div style="font-size:0.8125rem;color:var(--bv3-text-muted);margin-bottom:0.5rem;"><?php echo esc_html($featured_date); ?></div>
                <h3 style="font-size:1.375rem;font-weight:700;color:var(--bv3-dark);margin:0 0 0.5rem;line-height:1.25;"><?php echo esc_html($featured->post_title); ?></h3>
                <p style="font-size:0.9375rem;color:var(--bv3-text-secondary);line-height:1.6;margin:0;"><?php echo esc_html(wp_trim_words($featured->post_excerpt ?: strip_tags($featured->post_content), 25)); ?></p>
            </a>

            <!-- Stacked articles -->
            <div class="bv3-article-stacked">
                <?php foreach ($rest as $article):
                    $thumb = get_the_post_thumbnail_url($article->ID, 'medium');
                    $date = get_the_date('d. M Y', $article->ID);
                ?>
                <a href="<?php echo esc_url(get_permalink($article)); ?>" class="bv3-article-row">
                    <div class="bv3-article-row__thumb">
                        <?php if ($thumb): ?>
                        <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($article->post_title); ?>" loading="lazy">
                        <?php endif; ?>
                    </div>
                    <div class="bv3-article-row__info">
                        <div class="bv3-article-row__date"><?php echo esc_html($date); ?></div>
                        <div class="bv3-article-row__title"><?php echo esc_html($article->post_title); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>


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
