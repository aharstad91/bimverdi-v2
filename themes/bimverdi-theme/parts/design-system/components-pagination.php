<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Pagination</h2>
<p class="ds-section__desc">Sidenavigasjon via <code>bimverdi_pagination()</code>. Integrerer med WordPress sin <code>paginate_links()</code>.</p>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Standard</h3>
<div style="margin-bottom: 32px;">
    <?php
    // Simulate pagination (can't rely on WP query in design system page)
    $current = 2;
    $total = 5;
    ?>
    <nav class="bv-pagination" role="navigation" aria-label="Pagination demo">
        <ul class="bv-pagination__list">
            <li>
                <a href="#" class="bv-pagination__link bv-pagination__prev" onclick="return false;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    <span>Previous</span>
                </a>
            </li>
            <li><a href="#" class="bv-pagination__link" onclick="return false;">1</a></li>
            <li><span class="bv-pagination__link bv-pagination__link--active" aria-current="page">2</span></li>
            <li><a href="#" class="bv-pagination__link" onclick="return false;">3</a></li>
            <li><span class="bv-pagination__ellipsis" aria-hidden="true">&hellip;</span></li>
            <li><a href="#" class="bv-pagination__link" onclick="return false;">5</a></li>
            <li>
                <a href="#" class="bv-pagination__link bv-pagination__next" onclick="return false;">
                    <span>Next</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            </li>
        </ul>
    </nav>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">F&oslash;rste side (Previous deaktivert)</h3>
<div style="margin-bottom: 32px;">
    <nav class="bv-pagination" role="navigation">
        <ul class="bv-pagination__list">
            <li>
                <span class="bv-pagination__link bv-pagination__prev bv-pagination__link--disabled" aria-disabled="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    <span>Previous</span>
                </span>
            </li>
            <li><span class="bv-pagination__link bv-pagination__link--active" aria-current="page">1</span></li>
            <li><a href="#" class="bv-pagination__link" onclick="return false;">2</a></li>
            <li><a href="#" class="bv-pagination__link" onclick="return false;">3</a></li>
            <li>
                <a href="#" class="bv-pagination__link bv-pagination__next" onclick="return false;">
                    <span>Next</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            </li>
        </ul>
    </nav>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Kode</h3>
<div style="background: #18181B; color: #E4E4E7; padding: 16px; border-radius: 6px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto;">// Automatisk fra WP query
bimverdi_pagination();

// Med norsk tekst
bimverdi_pagination([
    'prev_text' => 'Forrige',
    'next_text' => 'Neste',
]);

// Manuelt
bimverdi_pagination([
    'current' => 2,
    'total'   => 10,
]);</div>
