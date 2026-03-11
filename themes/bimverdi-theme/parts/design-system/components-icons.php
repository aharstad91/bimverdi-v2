<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Ikoner</h2>
<p class="ds-section__desc">Lucide-ikoner via <code>bimverdi_icon('name')</code>. <?php
$icon_names = [
    'square-pen', 'pencil', 'shield', 'shield-check', 'plus', 'x', 'check',
    'arrow-right', 'arrow-left', 'chevron-right', 'chevron-left', 'chevron-down', 'chevron-up',
    'external-link', 'download', 'upload',
    'eye', 'eye-off', 'copy', 'trash-2', 'save', 'settings', 'search', 'filter',
    'wrench', 'building-2', 'user', 'users', 'file-text', 'lightbulb', 'calendar', 'mail', 'phone', 'globe', 'link',
    'info', 'alert-circle', 'check-circle', 'x-circle', 'loader',
    'linkedin', 'share-2',
    'layout-dashboard', 'menu', 'more-vertical', 'more-horizontal',
    'log-in', 'log-out',
];
echo count($icon_names);
?> ikoner tilgjengelig.</p>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 8px; margin-top: 24px;">
<?php foreach ($icon_names as $name) : ?>
    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 16px 8px; border-radius: 8px; border: 1px solid transparent; cursor: default; transition: border-color 0.15s;" onmouseover="this.style.borderColor='#E7E5E4'" onmouseout="this.style.borderColor='transparent'">
        <?php echo bimverdi_icon($name, 24); ?>
        <span style="font-size: 11px; color: #888; font-family: monospace;"><?php echo esc_html($name); ?></span>
    </div>
<?php endforeach; ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 32px 0 12px;">Kode-eksempel</h3>
<div style="background: #1A1A1A; color: #E7E5E4; padding: 16px; border-radius: 8px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto; margin-top: 24px;">&lt;?php echo bimverdi_icon('wrench', 24); ?&gt;

// Inline i tekst (16px default)
&lt;?php echo bimverdi_icon('check'); ?&gt;

// Med ekstra CSS-klasse
&lt;?php echo bimverdi_icon('calendar', 20, 'my-class'); ?&gt;</div>
