<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Navigation Menu</h2>
<p class="ds-section__desc">Hovednavigasjon via WordPress <code>wp_nav_menu()</code> med shadcn-inspirert styling. Bruker <code>.bv-nav</code> og <code>.bv-nav__list</code> klasser.</p>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Live navigasjon</h3>
<div style="background: #FAFAFA; border: 1px solid #E4E4E7; border-radius: 8px; padding: 24px 32px; margin-bottom: 32px;">
    <nav class="bv-nav">
        <ul class="bv-nav__list">
            <li class="menu-item"><a href="#">Deltakere</a></li>
            <li class="menu-item menu-item-has-children">
                <a href="#">Verkt&oslash;y</a>
                <ul class="sub-menu">
                    <li class="menu-item"><a href="#">Alle verkt&oslash;y</a></li>
                    <li class="menu-item"><a href="#">Kategorier</a></li>
                    <li class="menu-item"><a href="#">Nylig lagt til</a></li>
                </ul>
            </li>
            <li class="menu-item menu-item-has-children">
                <a href="#">Kunnskap</a>
                <ul class="sub-menu">
                    <li class="menu-item"><a href="#">Artikler</a></li>
                    <li class="menu-item"><a href="#">Kunnskapskilder</a></li>
                    <li class="menu-item"><a href="#">Temagrupper</a></li>
                </ul>
            </li>
            <li class="menu-item"><a href="#">Arrangementer</a></li>
        </ul>
    </nav>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Funksjoner</h3>
<ul style="font-size: 14px; color: #71717A; line-height: 2; margin-left: 20px; list-style: disc;">
    <li>Hover-bakgrunn p&aring; nav-items (#F4F4F5, 6px radius)</li>
    <li>SVG chevron p&aring; dropdown-triggers (roterer 180&deg; ved hover)</li>
    <li>Dropdown-panel: hvit bg, 1px border, shadow, 4px padding, 8px radius</li>
    <li>Dropdown-items: 6px radius, hover-bakgrunn</li>
    <li>Focus ring: hvit + m&oslash;rk ring (som resten av design systemet)</li>
    <li>Animasjon: 150ms fade + translateY</li>
</ul>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Kode</h3>
<div style="background: #18181B; color: #E4E4E7; padding: 16px; border-radius: 6px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto;">wp_nav_menu([
    'theme_location'  => 'primary',
    'container'       => 'nav',
    'container_class' => 'bv-nav',
    'menu_class'      => 'bv-nav__list',
    'depth'           => 2,
]);</div>
