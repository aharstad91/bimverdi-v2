<?php
/**
 * Template Name: Web Awesome Test
 * 
 * Testside for å verifisere at Web Awesome er riktig installert
 */

get_header();
?>

<main class="container mx-auto px-4 py-12">
    <h1 class="text-4xl font-bold mb-8">Web Awesome Test</h1>
    
    <p class="mb-8 text-gray-600">Hvis komponentene under vises riktig, er Web Awesome installert korrekt.</p>
    
    <!-- Buttons -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Buttons</h2>
        <div class="flex gap-4 flex-wrap">
            <wa-button>Default</wa-button>
            <wa-button variant="brand">Brand</wa-button>
            <wa-button variant="success">Success</wa-button>
            <wa-button variant="warning">Warning</wa-button>
            <wa-button variant="danger">Danger</wa-button>
        </div>
    </section>
    
    <!-- Input -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Input</h2>
        <wa-input label="Navn" placeholder="Skriv ditt navn"></wa-input>
    </section>
    
    <!-- Card -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Card</h2>
        <wa-card class="max-w-md">
            <div slot="header">
                <strong>Testkort</strong>
            </div>
            Dette er et Web Awesome kort. Hvis du ser dette med styling, fungerer alt!
            <div slot="footer">
                <wa-button variant="brand">Les mer</wa-button>
            </div>
        </wa-card>
    </section>
    
    <!-- Badge -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Badge</h2>
        <div class="flex gap-2">
            <wa-badge>Default</wa-badge>
            <wa-badge variant="brand">Brand</wa-badge>
            <wa-badge variant="success">Success</wa-badge>
            <wa-badge variant="warning">Warning</wa-badge>
            <wa-badge variant="danger">Danger</wa-badge>
        </div>
    </section>
    
    <!-- Alert/Callout -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Callout</h2>
        <wa-callout variant="brand">
            <strong>Info:</strong> Web Awesome er nå installert i BIM Verdi!
        </wa-callout>
    </section>
    
    <!-- Spinner -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Spinner</h2>
        <wa-spinner></wa-spinner>
    </section>
    
    <!-- Switch -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Switch</h2>
        <wa-switch>Aktiver funksjon</wa-switch>
    </section>
    
    <!-- Tab Group -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Tab Group</h2>
        <wa-tab-group>
            <wa-tab slot="nav" panel="general">Generelt</wa-tab>
            <wa-tab slot="nav" panel="innstillinger">Innstillinger</wa-tab>
            <wa-tab slot="nav" panel="avansert">Avansert</wa-tab>
            
            <wa-tab-panel name="general">Generelt innhold her.</wa-tab-panel>
            <wa-tab-panel name="innstillinger">Innstillinger innhold her.</wa-tab-panel>
            <wa-tab-panel name="avansert">Avansert innhold her.</wa-tab-panel>
        </wa-tab-group>
    </section>
    
</main>

<?php get_footer(); ?>
