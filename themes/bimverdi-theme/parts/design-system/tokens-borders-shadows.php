<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Borders &amp; Shadows</h2>
<p class="ds-section__desc">Border-radius og skygger brukt i komponenter.</p>

<!-- Border Radius -->
<h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 32px 0 16px;">Border Radius</h3>
<div style="display: flex; gap: 24px; flex-wrap: wrap;">
<?php
$radii = [
    ['name' => 'sm', 'px' => 4],
    ['name' => 'md', 'px' => 8],
    ['name' => 'lg', 'px' => 12],
    ['name' => 'xl', 'px' => 16],
    ['name' => 'full', 'px' => 9999],
];

foreach ($radii as $r) : ?>
    <div style="text-align: center;">
        <div style="width: 80px; height: 80px; background: #F5F5F4; border: 1px solid #E7E5E4; border-radius: <?php echo (int) $r['px']; ?>px;"></div>
        <div style="margin-top: 8px; font-size: 13px; font-weight: 500; color: #1A1A1A;"><?php echo esc_html($r['name']); ?></div>
        <div style="font-size: 12px; color: #888; font-family: monospace;"><?php echo $r['px'] === 9999 ? '9999px' : (int) $r['px'] . 'px'; ?></div>
    </div>
<?php endforeach; ?>
</div>

<!-- Border Colors -->
<h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 32px 0 16px;">Border Colors</h3>
<?php
$borders = [
    ['var' => '--color-border', 'color' => '#E7E5E4', 'label' => 'Standard'],
    ['var' => '--color-border-strong', 'color' => '#D6D1C6', 'label' => 'Emphasis'],
];

foreach ($borders as $b) : ?>
    <div style="display: flex; align-items: center; gap: 16px; padding: 8px 0;">
        <div style="width: 160px; font-size: 13px; color: #888; font-family: monospace;"><?php echo esc_html($b['var']); ?></div>
        <div style="flex: 1; height: 0; border-top: 2px solid <?php echo esc_attr($b['color']); ?>;"></div>
        <div style="font-size: 13px; color: #5A5A5A;"><?php echo esc_html($b['label']); ?> <span style="font-family: monospace; color: #888;"><?php echo esc_html($b['color']); ?></span></div>
    </div>
<?php endforeach; ?>

<!-- Shadows -->
<h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 32px 0 16px;">Shadows</h3>
<div style="display: flex; gap: 32px; flex-wrap: wrap;">
<?php
$shadows = [
    ['name' => 'sm', 'value' => '0 1px 2px rgba(0,0,0,0.05)'],
    ['name' => 'md', 'value' => '0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05)'],
    ['name' => 'lg', 'value' => '0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.04)'],
];

foreach ($shadows as $sh) : ?>
    <div style="text-align: center;">
        <div style="width: 120px; height: 80px; background: #fff; border-radius: 8px; box-shadow: <?php echo esc_attr($sh['value']); ?>;"></div>
        <div style="margin-top: 8px; font-size: 13px; font-weight: 500; color: #1A1A1A;"><?php echo esc_html($sh['name']); ?></div>
        <div style="font-size: 11px; color: #888; font-family: monospace; max-width: 120px; word-break: break-all;"><?php echo esc_html($sh['value']); ?></div>
    </div>
<?php endforeach; ?>
</div>
