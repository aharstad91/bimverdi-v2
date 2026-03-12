<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Breadcrumb</h2>
<p class="ds-section__desc">Navigasjonssti via <code>bimverdi_breadcrumb()</code>. Erstatter hardkodet HTML i single-templates.</p>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Standard (chevron)</h3>
<div style="margin-bottom: 32px;">
    <?php bimverdi_breadcrumb([
        ['label' => 'Hjem', 'href' => '/'],
        ['label' => 'Verkt&oslash;y', 'href' => '/verktoy/'],
        ['label' => 'Solibri Office'],
    ]); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Med slash-separator</h3>
<div style="margin-bottom: 32px;">
    <?php bimverdi_breadcrumb([
        ['label' => 'Hjem', 'href' => '/'],
        ['label' => 'Deltakere', 'href' => '/foretak/'],
        ['label' => 'Ramb&oslash;ll'],
    ], ['separator' => 'slash']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">To niv&aring;er</h3>
<div style="margin-bottom: 32px;">
    <?php bimverdi_breadcrumb([
        ['label' => 'Arrangementer', 'href' => '/arrangement/'],
        ['label' => 'BIM-konferansen 2026'],
    ]); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Fire niv&aring;er</h3>
<div style="margin-bottom: 32px;">
    <?php bimverdi_breadcrumb([
        ['label' => 'Hjem', 'href' => '/'],
        ['label' => 'Temagrupper', 'href' => '/temagruppe/'],
        ['label' => 'BIMtech', 'href' => '/temagruppe/bimtech/'],
        ['label' => 'Arrangement'],
    ]); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Kode</h3>
<div style="background: #18181B; color: #E4E4E7; padding: 16px; border-radius: 6px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto;">bimverdi_breadcrumb([
    ['label' => 'Verkt&oslash;y', 'href' => '/verktoy/'],
    ['label' => get_the_title()],
]);</div>
