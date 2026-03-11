<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Knapper</h2>
<p class="ds-section__desc">Bruk <code>bimverdi_button()</code> for alle knapper. 4 varianter &times; 3 st&oslash;rrelser.</p>

<h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 32px 0 12px;">Varianter</h3>
<div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_button(['text' => 'Primary', 'variant' => 'primary']); ?>
    <?php bimverdi_button(['text' => 'Secondary', 'variant' => 'secondary']); ?>
    <?php bimverdi_button(['text' => 'Tertiary', 'variant' => 'tertiary']); ?>
    <?php bimverdi_button(['text' => 'Danger', 'variant' => 'danger']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 32px 0 12px;">St&oslash;rrelser</h3>
<div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_button(['text' => 'Small', 'variant' => 'primary', 'size' => 'small']); ?>
    <?php bimverdi_button(['text' => 'Medium', 'variant' => 'primary', 'size' => 'medium']); ?>
    <?php bimverdi_button(['text' => 'Large', 'variant' => 'primary', 'size' => 'large']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 32px 0 12px;">Med ikon</h3>
<div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_button(['text' => 'Rediger', 'variant' => 'primary', 'icon' => 'square-pen']); ?>
    <?php bimverdi_button(['text' => 'Slett', 'variant' => 'danger', 'icon' => 'trash-2']); ?>
    <?php bimverdi_button(['text' => 'Last ned', 'variant' => 'secondary', 'icon' => 'download']); ?>
    <?php bimverdi_button(['text' => 'Neste', 'variant' => 'primary', 'icon' => 'arrow-right', 'icon_position' => 'right']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 32px 0 12px;">States</h3>
<div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_button(['text' => 'Disabled', 'variant' => 'primary', 'disabled' => true]); ?>
    <?php bimverdi_button(['text' => 'Disabled', 'variant' => 'secondary', 'disabled' => true]); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 32px 0 12px;">Full bredde</h3>
<div style="margin-bottom: 32px;">
    <?php bimverdi_button(['text' => 'Full bredde', 'variant' => 'primary', 'full_width' => true]); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 32px 0 12px;">Kode-eksempel</h3>
<div style="background: #1A1A1A; color: #E7E5E4; padding: 16px; border-radius: 8px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto; margin-top: 24px;">bimverdi_button([
    'text'    => 'Lagre endringer',
    'variant' => 'primary',
    'icon'    => 'save',
    'type'    => 'submit',
]);</div>
