<?php
/**
 * Design System: Item (shadcn-inspired)
 */
if (!defined('ABSPATH')) exit;
?>
<h2 class="ds-section__title">Item</h2>
<p class="ds-section__desc">Fleksibel listeradkomponent via <code>bimverdi_item()</code>. St&oslash;tter ikon, avatar, tittel, beskrivelse, badge, meta og action.</p>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Ikon + Action</h3>
<div style="max-width: 520px;">
    <?php bimverdi_item([
        'icon'        => 'shield-alert',
        'title'       => 'Security Alert',
        'description' => 'New login detected from unknown device.',
        'action'      => ['text' => 'Review', 'variant' => 'outline', 'size' => 'sm'],
        'variant'     => 'outline',
    ]); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Klikkbar lenke</h3>
<div style="max-width: 520px;">
    <?php bimverdi_item_group(); ?>
        <?php bimverdi_item([
            'icon'        => 'wrench',
            'title'       => 'Solibri Office',
            'description' => 'Modellsjekk og kvalitetskontroll',
            'badge'       => ['text' => 'Modellsjekk', 'variant' => 'outline'],
            'href'        => '#',
        ]); ?>
        <?php bimverdi_item([
            'icon'        => 'wrench',
            'title'       => 'Revit',
            'description' => 'BIM-modellering for arkitektur og konstruksjon',
            'badge'       => ['text' => 'Modellering', 'variant' => 'outline'],
            'href'        => '#',
        ]); ?>
    <?php bimverdi_item_group_end(); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Avatar</h3>
<div style="max-width: 520px;">
    <?php bimverdi_item_group('outline'); ?>
        <?php bimverdi_item([
            'avatar'       => 'AH',
            'avatar_color' => '#18181B',
            'title'        => 'Andreas Harstad',
            'description'  => 'andreas@aharstad.no',
            'action'       => ['icon' => 'plus', 'variant' => 'ghost', 'size' => 'icon'],
        ]); ?>
        <?php bimverdi_item([
            'avatar'       => 'KN',
            'avatar_color' => '#3B82F6',
            'title'        => 'Kari Nordmann',
            'description'  => 'kari@firma.no',
            'action'       => ['icon' => 'plus', 'variant' => 'ghost', 'size' => 'icon'],
        ]); ?>
    <?php bimverdi_item_group_end(); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Med badge og meta</h3>
<div style="max-width: 520px;">
    <?php bimverdi_item_group(); ?>
        <?php bimverdi_item([
            'icon'        => 'file-text',
            'title'       => 'Slik kommer du i gang med BIM',
            'description' => '14. mars 2026',
            'meta'        => '3 min',
            'href'        => '#',
        ]); ?>
        <?php bimverdi_item([
            'icon'        => 'file-text',
            'title'       => 'Erfaringer fra pilotprosjekt Gr&oslash;nn BIM',
            'description' => '8. mars 2026',
            'meta'        => '5 min',
            'href'        => '#',
        ]); ?>
    <?php bimverdi_item_group_end(); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Sm&aring; (sm)</h3>
<div style="max-width: 520px;">
    <?php bimverdi_item_group('outline'); ?>
        <?php bimverdi_item([
            'icon'        => 'check-circle',
            'title'       => 'E-post bekreftet',
            'description' => 'andreas@aharstad.no',
            'size'        => 'sm',
        ]); ?>
        <?php bimverdi_item([
            'icon'        => 'building-2',
            'title'       => 'Foretak koblet',
            'description' => 'Konsulent Harstad',
            'size'        => 'sm',
        ]); ?>
    <?php bimverdi_item_group_end(); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Kode</h3>
<div style="background: #18181B; color: #E4E4E7; padding: 16px; border-radius: 6px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto;">// Enkel item
bimverdi_item([
    'icon'        => 'shield-alert',
    'title'       => 'Security Alert',
    'description' => 'New login detected.',
    'action'      => ['text' => 'Review', 'variant' => 'outline'],
    'variant'     => 'outline',
]);

// Gruppert med separatorer
bimverdi_item_group('outline');
    bimverdi_item([
        'avatar' => 'AH',
        'title'  => 'Andreas Harstad',
        'description' => 'andreas@aharstad.no',
    ]);
    bimverdi_item([...]);
bimverdi_item_group_end();</div>
