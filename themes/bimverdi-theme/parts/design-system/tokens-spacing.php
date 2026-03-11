<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Spacing</h2>
<p class="ds-section__desc">Spacing-skalaen i 8px-inkremente. Brukes for padding, margin og gap.</p>

<?php
$spacings = [
    ['var' => '--space-1', 'px' => 4],
    ['var' => '--space-2', 'px' => 8],
    ['var' => '--space-3', 'px' => 12],
    ['var' => '--space-4', 'px' => 16],
    ['var' => '--space-6', 'px' => 24],
    ['var' => '--space-8', 'px' => 32],
    ['var' => '--space-12', 'px' => 48],
    ['var' => '--space-16', 'px' => 64],
    ['var' => '--space-20', 'px' => 80],
    ['var' => '--space-24', 'px' => 96],
];

foreach ($spacings as $s) : ?>
    <div style="display: flex; align-items: center; gap: 16px; padding: 8px 0;">
        <div style="width: 100px; font-size: 13px; color: #888; font-family: monospace;"><?php echo esc_html($s['var']); ?></div>
        <div style="height: 12px; border-radius: 4px; background: #FF8B5E; width: <?php echo (int) $s['px']; ?>px;"></div>
        <div style="font-size: 13px; font-weight: 500; color: #1A1A1A;"><?php echo (int) $s['px']; ?>px</div>
    </div>
<?php endforeach; ?>
