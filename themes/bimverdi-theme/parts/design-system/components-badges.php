<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Badges</h2>
<p class="ds-section__desc">Bruk <code>bimverdi_badge()</code> for statusmerker, kategorier og temagrupper.</p>

<h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 32px 0 12px;">Status</h3>
<div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_badge(['text' => 'Aktiv', 'color' => 'green']); ?>
    <?php bimverdi_badge(['text' => 'Venter', 'color' => 'yellow']); ?>
    <?php bimverdi_badge(['text' => 'Feil', 'color' => 'red']); ?>
    <?php bimverdi_badge(['text' => 'Inaktiv', 'color' => 'gray']); ?>
    <?php bimverdi_badge(['text' => 'Info', 'color' => 'blue']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 32px 0 12px;">St&oslash;rrelser</h3>
<div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_badge(['text' => 'Small (default)', 'color' => 'blue', 'size' => 'small']); ?>
    <?php bimverdi_badge(['text' => 'Medium', 'color' => 'blue', 'size' => 'medium']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 32px 0 12px;">Kategori</h3>
<div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_badge(['text' => 'Verkt&oslash;y', 'variant' => 'category', 'icon' => 'wrench']); ?>
    <?php bimverdi_badge(['text' => 'Artikkel', 'variant' => 'category', 'icon' => 'file-text']); ?>
    <?php bimverdi_badge(['text' => 'Kunnskap', 'variant' => 'category', 'icon' => 'lightbulb']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 32px 0 12px;">Temagrupper</h3>
<div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_badge(['text' => 'BIMtech', 'variant' => 'temagruppe', 'color' => 'blue']); ?>
    <?php bimverdi_badge(['text' => 'Gr&oslash;nn BIM', 'variant' => 'temagruppe', 'color' => 'green']); ?>
    <?php bimverdi_badge(['text' => 'Digital samhandling', 'variant' => 'temagruppe', 'color' => 'purple']); ?>
    <?php bimverdi_badge(['text' => 'BIM i forvaltning', 'variant' => 'temagruppe', 'color' => 'teal']); ?>
    <?php bimverdi_badge(['text' => 'B&aelig;rekraft', 'variant' => 'temagruppe', 'color' => 'amber']); ?>
    <?php bimverdi_badge(['text' => 'Standard', 'variant' => 'temagruppe', 'color' => 'orange']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 32px 0 12px;">Kode-eksempel</h3>
<div style="background: #1A1A1A; color: #E7E5E4; padding: 16px; border-radius: 8px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto; margin-top: 24px;">bimverdi_badge([
    'text'    => 'Aktiv',
    'variant' => 'status',
    'color'   => 'green',
    'size'    => 'small',
]);

bimverdi_badge([
    'text'    => 'BIMtech',
    'variant' => 'temagruppe',
    'color'   => 'blue',
]);</div>
