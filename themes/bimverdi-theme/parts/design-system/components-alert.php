<?php
// Section: Alert (shadcn-inspired)
if (!defined('ABSPATH')) exit;
?>

<h2 class="ds-section__title">Alert</h2>
<p class="ds-section__desc">Viser viktig informasjon, advarsler og feilmeldinger. Bruker ikon, tittel og beskrivelse.</p>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Default</h3>

<div style="padding: 24px 0; border-bottom: 1px solid #E4E4E7;">
    <?php bimverdi_alert([
        'title'       => 'Heads up!',
        'description' => 'You can add components and dependencies to your app using the CLI.',
    ]); ?>
    <div style="font-size: 12px; color: #71717A; font-family: monospace; margin-top: 12px;">
        variant: default &middot; auto icon: info &middot; <code style="background: #F4F4F5; padding: 1px 4px; border-radius: 3px; font-size: 11px;">bimverdi_alert()</code>
    </div>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Destructive</h3>

<div style="padding: 24px 0; border-bottom: 1px solid #E4E4E7;">
    <?php bimverdi_alert([
        'title'       => 'Feil oppstod',
        'description' => 'Foretaket ditt kunne ikke oppdateres. Vennligst pr&oslash;v igjen.',
        'variant'     => 'destructive',
    ]); ?>
    <div style="font-size: 12px; color: #71717A; font-family: monospace; margin-top: 12px;">
        variant: destructive &middot; auto icon: circle-alert
    </div>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Success</h3>

<div style="padding: 24px 0; border-bottom: 1px solid #E4E4E7;">
    <?php bimverdi_alert([
        'title'       => 'Kontoen din er aktivert!',
        'description' => 'Velkommen til BIM Verdi. Du kan n&aring; registrere verkt&oslash;y og delta p&aring; arrangementer.',
        'variant'     => 'success',
    ]); ?>
    <div style="font-size: 12px; color: #71717A; font-family: monospace; margin-top: 12px;">
        variant: success &middot; auto icon: circle-check
    </div>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Warning</h3>

<div style="padding: 24px 0; border-bottom: 1px solid #E4E4E7;">
    <?php bimverdi_alert([
        'title'       => 'Foretak mangler',
        'description' => 'Du m&aring; koble et foretak til kontoen din for &aring; f&aring; full tilgang.',
        'variant'     => 'warning',
    ]); ?>
    <div style="font-size: 12px; color: #71717A; font-family: monospace; margin-top: 12px;">
        variant: warning &middot; auto icon: triangle-alert
    </div>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Dismissible</h3>

<div style="padding: 24px 0; border-bottom: 1px solid #E4E4E7;">
    <?php bimverdi_alert([
        'title'       => 'Invitasjon akseptert!',
        'description' => 'Du er n&aring; koblet til Demo Konsulenter AS.',
        'variant'     => 'success',
        'dismissible' => true,
    ]); ?>
    <div style="font-size: 12px; color: #71717A; font-family: monospace; margin-top: 12px;">
        dismissible: true &middot; klikk X for &aring; fjerne
    </div>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Description only (no title)</h3>

<div style="padding: 24px 0; border-bottom: 1px solid #E4E4E7;">
    <?php bimverdi_alert([
        'description' => 'Endringene dine er lagret.',
        'variant'     => 'success',
    ]); ?>
    <div style="font-size: 12px; color: #71717A; font-family: monospace; margin-top: 12px;">
        Kun description, ingen tittel
    </div>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Custom icon</h3>

<div style="padding: 24px 0; border-bottom: 1px solid #E4E4E7;">
    <?php bimverdi_alert([
        'title'       => 'Ny versjon tilgjengelig',
        'description' => 'Oppdater til v2.0 for &aring; f&aring; tilgang til nye funksjoner.',
        'icon'        => 'rocket',
    ]); ?>
    <div style="font-size: 12px; color: #71717A; font-family: monospace; margin-top: 12px;">
        icon: rocket &middot; overstyrer auto-ikon
    </div>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Kode</h3>
<div style="background: #18181B; color: #E4E4E7; padding: 16px; border-radius: 6px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto;">&lt;?php bimverdi_alert([
    'title'       =&gt; 'Heads up!',
    'description' =&gt; 'Important message here.',
    'variant'     =&gt; 'default',    // default | destructive | success | warning
    'icon'        =&gt; 'info',       // null = auto per variant
    'dismissible' =&gt; false,
]); ?&gt;</div>
