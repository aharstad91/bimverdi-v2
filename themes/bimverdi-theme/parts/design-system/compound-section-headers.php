<?php
/**
 * Design System: Section Headers (compound component)
 * Uses bimverdi_section_header() from parts/components/section-header.php
 */
if (!defined('ABSPATH')) exit;
?>
<h2 class="ds-section__title">Section Headers</h2>
<p class="ds-section__desc">Bruk <code>bimverdi_section_header()</code> for seksjonsoverskrifter med valgfri eyebrow, heading og subtitle.</p>

<div style="padding: 24px 0; border-bottom: 1px solid #E7E5E4;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Med eyebrow + subtitle</h3>
    <?php bimverdi_section_header([
        'eyebrow'  => 'Temagrupper',
        'heading'  => 'Utforsk gruppene',
        'subtitle' => 'Finn temagruppen som passer for ditt fagomrade',
    ]); ?>
</div>

<div style="padding: 24px 0; border-bottom: 1px solid #E7E5E4;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Kun heading</h3>
    <?php bimverdi_section_header([
        'heading' => 'Nytt i nettverket',
        'tag'     => 'h3',
    ]); ?>
</div>

<div style="padding: 24px 0; border-bottom: 1px solid #E7E5E4;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Med eyebrow, uten subtitle</h3>
    <?php bimverdi_section_header([
        'eyebrow' => 'Verktoy',
        'heading'  => 'Registrerte verktoy',
    ]); ?>
</div>

<div style="padding: 24px 0; border-bottom: 1px solid #E7E5E4;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Sentrert</h3>
    <?php bimverdi_section_header([
        'eyebrow'  => 'BIM Verdi',
        'heading'  => 'Nettverket for norsk byggenaring',
        'subtitle' => 'Sammen gjor vi bransjen mer digital',
        'align'    => 'center',
    ]); ?>
</div>

<div style="padding: 24px 0;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">h4 tag</h3>
    <?php bimverdi_section_header([
        'heading' => 'Mindre seksjon',
        'subtitle' => 'Brukes for underseksjoner inni en side.',
        'tag'      => 'h4',
    ]); ?>
</div>

<!-- Code example -->
<div style="margin-top: 16px; padding-top: 24px; border-top: 1px solid #E7E5E4;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Bruk</h3>
    <pre style="background: #F5F5F4; padding: 16px; border-radius: 8px; font-size: 13px; overflow-x: auto; color: #1A1A1A;"><code>&lt;?php bimverdi_section_header([
    'eyebrow'  => 'Temagrupper',
    'heading'  => 'Utforsk gruppene',
    'subtitle' => 'Finn temagruppen som passer for ditt fagomrade',
    'align'    => 'left',   // 'left' | 'center'
    'tag'      => 'h2',     // 'h1' | 'h2' | 'h3' | 'h4'
]); ?&gt;</code></pre>
</div>
