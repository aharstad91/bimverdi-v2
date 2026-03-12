<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Knapper</h2>
<p class="ds-section__desc">shadcn-inspirerte knapper via <code>bimverdi_button()</code>. 6 varianter &times; 4 st&oslash;rrelser.</p>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Varianter</h3>
<div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 8px;">
    <?php bimverdi_button(['text' => 'Default', 'variant' => 'default']); ?>
    <?php bimverdi_button(['text' => 'Secondary', 'variant' => 'secondary']); ?>
    <?php bimverdi_button(['text' => 'Outline', 'variant' => 'outline']); ?>
    <?php bimverdi_button(['text' => 'Ghost', 'variant' => 'ghost']); ?>
    <?php bimverdi_button(['text' => 'Destructive', 'variant' => 'destructive']); ?>
    <?php bimverdi_button(['text' => 'Link', 'variant' => 'link']); ?>
</div>
<div style="display: flex; gap: 16px; flex-wrap: wrap; font-size: 12px; color: #71717A; margin-bottom: 32px;">
    <span><code>default</code> &mdash; solid m&oslash;rk</span>
    <span><code>secondary</code> &mdash; dempet fylt</span>
    <span><code>outline</code> &mdash; kun ramme</span>
    <span><code>ghost</code> &mdash; usynlig, hover</span>
    <span><code>destructive</code> &mdash; fare/slett</span>
    <span><code>link</code> &mdash; tekstlenke</span>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">St&oslash;rrelser</h3>
<div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 8px;">
    <?php bimverdi_button(['text' => 'Small', 'variant' => 'default', 'size' => 'sm']); ?>
    <?php bimverdi_button(['text' => 'Default', 'variant' => 'default']); ?>
    <?php bimverdi_button(['text' => 'Large', 'variant' => 'default', 'size' => 'lg']); ?>
</div>
<div style="display: flex; gap: 16px; flex-wrap: wrap; font-size: 12px; color: #71717A; margin-bottom: 32px;">
    <span><code>sm</code> &mdash; 28px</span>
    <span><code>default</code> &mdash; 32px</span>
    <span><code>lg</code> &mdash; 36px</span>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Med ikon</h3>
<div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_button(['text' => 'Rediger', 'variant' => 'default', 'icon' => 'square-pen']); ?>
    <?php bimverdi_button(['text' => 'Slett', 'variant' => 'destructive', 'icon' => 'trash-2']); ?>
    <?php bimverdi_button(['text' => 'Last ned', 'variant' => 'outline', 'icon' => 'download']); ?>
    <?php bimverdi_button(['text' => 'Neste', 'variant' => 'default', 'icon' => 'arrow-right', 'icon_position' => 'right']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Ikon-knapper</h3>
<div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 8px;">
    <?php bimverdi_button(['icon' => 'plus', 'variant' => 'default', 'size' => 'icon', 'aria_label' => 'Legg til']); ?>
    <?php bimverdi_button(['icon' => 'settings', 'variant' => 'outline', 'size' => 'icon', 'aria_label' => 'Innstillinger']); ?>
    <?php bimverdi_button(['icon' => 'search', 'variant' => 'ghost', 'size' => 'icon', 'aria_label' => 'S&oslash;k']); ?>
    <?php bimverdi_button(['icon' => 'trash-2', 'variant' => 'destructive', 'size' => 'icon', 'aria_label' => 'Slett']); ?>
</div>
<div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_button(['icon' => 'plus', 'variant' => 'outline', 'size' => 'icon-sm', 'aria_label' => 'Legg til']); ?>
    <?php bimverdi_button(['icon' => 'plus', 'variant' => 'outline', 'size' => 'icon', 'aria_label' => 'Legg til']); ?>
    <?php bimverdi_button(['icon' => 'plus', 'variant' => 'outline', 'size' => 'icon-lg', 'aria_label' => 'Legg til']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Disabled</h3>
<div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_button(['text' => 'Default', 'variant' => 'default', 'disabled' => true]); ?>
    <?php bimverdi_button(['text' => 'Secondary', 'variant' => 'secondary', 'disabled' => true]); ?>
    <?php bimverdi_button(['text' => 'Outline', 'variant' => 'outline', 'disabled' => true]); ?>
    <?php bimverdi_button(['text' => 'Ghost', 'variant' => 'ghost', 'disabled' => true]); ?>
    <?php bimverdi_button(['text' => 'Destructive', 'variant' => 'destructive', 'disabled' => true]); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Full bredde</h3>
<div style="margin-bottom: 32px;">
    <?php bimverdi_button(['text' => 'Full bredde', 'variant' => 'default', 'full_width' => true]); ?>
    <div style="height: 8px;"></div>
    <?php bimverdi_button(['text' => 'Full bredde outline', 'variant' => 'outline', 'full_width' => true]); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Kode</h3>
<div style="background: #18181B; color: #E4E4E7; padding: 16px; border-radius: 6px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto;">bimverdi_button([
    'text'    => 'Lagre',
    'variant' => 'default',       // default, secondary, outline, ghost, destructive, link
    'size'    => 'default',       // sm, default, lg, icon, icon-sm, icon-lg
    'icon'    => 'save',
]);

// Ikon-knapp
bimverdi_button([
    'icon'       => 'plus',
    'variant'    => 'outline',
    'size'       => 'icon',
    'aria_label' => 'Legg til',
]);</div>
