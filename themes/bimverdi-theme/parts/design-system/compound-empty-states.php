<?php
/**
 * Design System: Empty States (shadcn-inspired)
 */
if (!defined('ABSPATH')) exit;
?>
<h2 class="ds-section__title">Empty States</h2>
<p class="ds-section__desc">Vises n&aring;r en liste eller seksjon er tom. Bruk <code>bimverdi_empty_state()</code>.</p>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-top: 24px;">

    <div>
        <h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 0 0 12px;">Med CTA-knapp</h3>
        <?php bimverdi_empty_state([
            'icon'        => 'wrench',
            'title'       => 'Ingen verkt&oslash;y registrert',
            'description' => 'Foretaket ditt har ikke registrert noen verkt&oslash;y enn&aring;. Kom i gang ved &aring; registrere det f&oslash;rste.',
            'action'      => ['text' => 'Registrer verkt&oslash;y', 'variant' => 'default', 'icon' => 'plus', 'href' => '#'],
            'variant'     => 'outline',
        ]); ?>
    </div>

    <div>
        <h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 0 0 12px;">Kun informasjon</h3>
        <?php bimverdi_empty_state([
            'icon'        => 'calendar',
            'title'       => 'Ingen kommende arrangementer',
            'description' => 'Det er ingen planlagte arrangementer akkurat n&aring;. Sjekk tilbake snart.',
            'variant'     => 'outline',
        ]); ?>
    </div>

</div>

<div style="margin-top: 32px; padding-top: 32px; border-top: 1px solid #E4E4E7;">
    <h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 0 0 12px;">Kompakt (inline)</h3>
    <div style="max-width: 420px;">
        <?php bimverdi_empty_state([
            'icon'        => 'file-text',
            'title'       => 'Ingen artikler enn&aring;',
            'description' => 'Du har ikke skrevet noen artikler.',
            'variant'     => 'compact',
        ]); ?>
    </div>
</div>

<div style="margin-top: 24px;">
    <div style="max-width: 420px;">
        <?php bimverdi_empty_state([
            'icon'        => 'inbox',
            'title'       => 'Ingen meldinger',
            'description' => 'Innboksen din er tom.',
            'variant'     => 'compact',
        ]); ?>
    </div>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Kode</h3>
<div style="background: #18181B; color: #E4E4E7; padding: 16px; border-radius: 6px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto;">bimverdi_empty_state([
    'icon'        => 'wrench',
    'title'       => 'Ingen verkt&oslash;y registrert',
    'description' => 'Kom i gang ved &aring; registrere det f&oslash;rste.',
    'action'      => [
        'text' => 'Registrer verkt&oslash;y',
        'href' => '/min-side/verktoy/registrer/',
        'icon' => 'plus',
    ],
    'variant' => 'outline',  // 'default', 'outline', 'compact'
]);</div>
