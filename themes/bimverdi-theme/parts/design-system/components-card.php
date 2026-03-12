<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Card</h2>
<p class="ds-section__desc">Kort med header, innhold og footer via <code>bimverdi_card()</code> eller composable helpers.</p>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Standard</h3>
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <?php bimverdi_card([
        'title'       => 'Medlemskap',
        'description' => 'Oversikt over ditt medlemskap og tilgang.',
        'content'     => '<p style="margin:0;">Du er registrert som <strong>Partner</strong> gjennom Rambøll Norge AS. Medlemskapet fornyes automatisk hvert kvartal.</p>',
        'footer'      => '<span style="font-size:13px;color:#71717A;">Sist oppdatert: 1. mars 2026</span>',
    ]); ?>

    <?php
    // Composable variant
    bimverdi_card_start();
        bimverdi_card_header([
            'title'       => 'Prosjektideer',
            'description' => 'Del og stem på ideer til pilotprosjekter.',
            'action'      => '<a href="#" style="font-size:13px;font-weight:500;color:#18181B;text-decoration:underline;">Se alle</a>',
        ]);
        bimverdi_card_content();
        ?>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span>Automatisk IFC-sjekk</span>
                    <span style="font-size:13px;color:#71717A;">12 stemmer</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span>Felles BIM-krav database</span>
                    <span style="font-size:13px;color:#71717A;">8 stemmer</span>
                </div>
            </div>
        <?php
        bimverdi_card_content_end();
        bimverdi_card_footer();
    ?>
        <?php bimverdi_button(['text' => 'Send inn idé', 'variant' => 'primary', 'size' => 'small']); ?>
    <?php
        bimverdi_card_footer_end();
    bimverdi_card_end();
    ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Liten (sm)</h3>
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <?php bimverdi_card([
        'title'       => 'Lite kort',
        'description' => 'Mer kompakt padding for tettere layout.',
        'content'     => '<p style="margin:0;">Innholdet bruker sm-varianten med 16px padding i stedet for 24px.</p>',
        'size'        => 'sm',
    ]); ?>

    <?php bimverdi_card([
        'title'   => 'Statistikk',
        'content' => '<div style="display:flex;gap:24px;"><div><div style="font-size:24px;font-weight:600;">40</div><div style="font-size:13px;color:#71717A;">Verktøy</div></div><div><div style="font-size:24px;font-weight:600;">64</div><div style="font-size:13px;color:#71717A;">Foretak</div></div><div><div style="font-size:24px;font-weight:600;">29</div><div style="font-size:13px;color:#71717A;">Kilder</div></div></div>',
        'size'    => 'sm',
    ]); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Med bilde</h3>
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <?php bimverdi_card([
        'image'       => ['src' => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=640&h=360&fit=crop', 'alt' => 'Byggeprosjekt'],
        'title'       => 'Design systems meetup',
        'description' => 'En praktisk workshop om komponent-APIer, tilgjengelighet og raskere leveranse.',
        'action'      => '<span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:12px;font-weight:600;background:#F4F4F5;color:#18181B;">Anbefalt</span>',
        'footer'      => '<span style="font-size:13px;color:#71717A;">12. mars 2026</span>',
    ]); ?>

    <?php
    bimverdi_card_start();
        bimverdi_card_image(['src' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=640&h=360&fit=crop', 'alt' => 'Kontor']);
        bimverdi_card_header([
            'title'       => 'BIM i praksis',
            'description' => 'Hvordan norske foretak bruker BIM-verktøy i dag.',
        ]);
        bimverdi_card_footer();
    ?>
        <?php bimverdi_button(['text' => 'Les artikkelen', 'variant' => 'primary', 'size' => 'small']); ?>
        <?php bimverdi_button(['text' => 'Del', 'variant' => 'secondary', 'size' => 'small']); ?>
    <?php
        bimverdi_card_footer_end();
    bimverdi_card_end();
    ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Kode</h3>
<div style="background: #18181B; color: #E4E4E7; padding: 16px; border-radius: 6px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto;">// Enkel — alt-i-ett
bimverdi_card([
    'title'       => 'Tittel',
    'description' => 'Beskrivelse',
    'content'     => '&lt;p&gt;Innhold&lt;/p&gt;',
    'footer'      => '&lt;button&gt;OK&lt;/button&gt;',
    'size'        => 'sm',
]);

// Composable — full kontroll
bimverdi_card_start();
    bimverdi_card_image(['src' => '/img.jpg', 'alt' => '...']);
    bimverdi_card_header([
        'title'       => 'Tittel',
        'description' => 'Beskrivelse',
        'action'      => '&lt;a href="#"&gt;Se alle&lt;/a&gt;',
    ]);
    bimverdi_card_content();
        echo 'Fri HTML her';
    bimverdi_card_content_end();
    bimverdi_card_footer();
        bimverdi_button(['text' => 'Lagre']);
    bimverdi_card_footer_end();
bimverdi_card_end();</div>
