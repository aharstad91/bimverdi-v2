<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Badges</h2>
<p class="ds-section__desc">Bruk <code>bimverdi_badge()</code> for statusmerker, kategorier og etiketter.</p>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Varianter</h3>
<div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_badge(['text' => 'Badge']); ?>
    <?php bimverdi_badge(['text' => 'Secondary', 'variant' => 'secondary']); ?>
    <?php bimverdi_badge(['text' => 'Destructive', 'variant' => 'destructive']); ?>
    <?php bimverdi_badge(['text' => 'Outline', 'variant' => 'outline']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Med ikon</h3>
<div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_badge(['text' => 'Verkt&oslash;y', 'variant' => 'outline', 'icon' => 'wrench']); ?>
    <?php bimverdi_badge(['text' => 'Artikkel', 'variant' => 'outline', 'icon' => 'file-text']); ?>
    <?php bimverdi_badge(['text' => 'Kunnskap', 'variant' => 'secondary', 'icon' => 'lightbulb']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Status (semantiske farger)</h3>
<div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_badge(['text' => 'Aktiv', 'color' => 'green']); ?>
    <?php bimverdi_badge(['text' => 'Venter', 'color' => 'yellow']); ?>
    <?php bimverdi_badge(['text' => 'Feil', 'color' => 'red']); ?>
    <?php bimverdi_badge(['text' => 'Inaktiv', 'color' => 'gray']); ?>
    <?php bimverdi_badge(['text' => 'Info', 'color' => 'blue']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Temagrupper</h3>
<div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_badge(['text' => 'BIMtech', 'color' => 'blue']); ?>
    <?php bimverdi_badge(['text' => 'Gr&oslash;nn BIM', 'color' => 'green']); ?>
    <?php bimverdi_badge(['text' => 'Digital samhandling', 'color' => 'purple']); ?>
    <?php bimverdi_badge(['text' => 'B&aelig;rekraft', 'color' => 'amber']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Kode</h3>
<div style="background: #18181B; color: #E4E4E7; padding: 16px; border-radius: 6px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto;">bimverdi_badge(['text' => 'Badge']);
bimverdi_badge(['text' => 'Secondary', 'variant' => 'secondary']);
bimverdi_badge(['text' => 'Destructive', 'variant' => 'destructive']);
bimverdi_badge(['text' => 'Outline', 'variant' => 'outline']);

// Med semantisk farge
bimverdi_badge(['text' => 'Aktiv', 'color' => 'green']);

// Med ikon
bimverdi_badge(['text' => 'Verkt&oslash;y', 'variant' => 'outline', 'icon' => 'wrench']);</div>
