<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Tabs</h2>
<p class="ds-section__desc">Fanebasert navigasjon via <code>bimverdi_tabs()</code>. To varianter: default (pill) og line (underline).</p>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Default (pill)</h3>
<?php bimverdi_tabs([
    'id'      => 'demo-default',
    'default' => 'account',
    'tabs'    => [
        'account'  => 'Account',
        'password' => 'Password',
        'settings' => 'Settings',
    ],
]); ?>
<?php bimverdi_tab_panel('demo-default', 'account'); ?>
    <p style="font-size: 14px; color: #71717A;">Rediger kontoinformasjonen din her.</p>
<?php bimverdi_tab_panel_end(); ?>
<?php bimverdi_tab_panel('demo-default', 'password'); ?>
    <p style="font-size: 14px; color: #71717A;">Endre passordet ditt her.</p>
<?php bimverdi_tab_panel_end(); ?>
<?php bimverdi_tab_panel('demo-default', 'settings'); ?>
    <p style="font-size: 14px; color: #71717A;">Oppdater innstillingene dine her.</p>
<?php bimverdi_tab_panel_end(); ?>
<?php bimverdi_tabs_end(); ?>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Line</h3>
<?php bimverdi_tabs([
    'id'      => 'demo-line',
    'variant' => 'line',
    'default' => 'overview',
    'tabs'    => [
        'overview'  => 'Overview',
        'analytics' => 'Analytics',
        'reports'   => 'Reports',
    ],
]); ?>
<?php bimverdi_tab_panel('demo-line', 'overview'); ?>
    <p style="font-size: 14px; color: #71717A;">Oversikt over prosjektstatus og n&oslash;kkeltall.</p>
<?php bimverdi_tab_panel_end(); ?>
<?php bimverdi_tab_panel('demo-line', 'analytics'); ?>
    <p style="font-size: 14px; color: #71717A;">Statistikk og analyser for perioden.</p>
<?php bimverdi_tab_panel_end(); ?>
<?php bimverdi_tab_panel('demo-line', 'reports'); ?>
    <p style="font-size: 14px; color: #71717A;">Last ned og vis rapporter.</p>
<?php bimverdi_tab_panel_end(); ?>
<?php bimverdi_tabs_end(); ?>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Kode</h3>
<div style="background: #18181B; color: #E4E4E7; padding: 16px; border-radius: 6px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto;">bimverdi_tabs([
    'id'      => 'my-tabs',
    'variant' => 'line',       // 'default' eller 'line'
    'default' => 'overview',
    'tabs'    => [
        'overview'  => 'Overview',
        'analytics' => 'Analytics',
    ],
]);

bimverdi_tab_panel('my-tabs', 'overview');
// ... innhold ...
bimverdi_tab_panel_end();

bimverdi_tab_panel('my-tabs', 'analytics');
// ... innhold ...
bimverdi_tab_panel_end();

bimverdi_tabs_end();</div>
