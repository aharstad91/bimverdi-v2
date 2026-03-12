<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Switch</h2>
<p class="ds-section__desc">Toggle-kontroll via <code>bimverdi_switch()</code>. Ren CSS &mdash; ingen JavaScript.</p>

<div style="max-width: 480px;">

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Default (av)</h3>
<?php bimverdi_switch([
    'label' => 'Airplane Mode',
    'name'  => 'demo_airplane',
]); ?>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">P&aring; (checked)</h3>
<?php bimverdi_switch([
    'label'   => 'Notifications',
    'name'    => 'demo_notifications',
    'checked' => true,
]); ?>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Med beskrivelse</h3>
<?php bimverdi_switch([
    'label'       => 'Marketing emails',
    'name'        => 'demo_marketing',
    'description' => 'Motta e-post om nye produkter og oppdateringer.',
]); ?>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Deaktivert</h3>
<?php bimverdi_switch([
    'label'    => 'Maintenance mode',
    'name'     => 'demo_maintenance',
    'disabled' => true,
]); ?>
<?php bimverdi_switch([
    'label'    => 'Always on',
    'name'     => 'demo_always_on',
    'checked'  => true,
    'disabled' => true,
]); ?>

</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Kode</h3>
<div style="background: #18181B; color: #E4E4E7; padding: 16px; border-radius: 6px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto;">bimverdi_switch([
    'label'   => 'Airplane Mode',
    'name'    => 'airplane_mode',
]);

bimverdi_switch([
    'label'       => 'Notifications',
    'name'        => 'notifications',
    'checked'     => true,
    'description' => 'Motta push-varsler.',
]);</div>
