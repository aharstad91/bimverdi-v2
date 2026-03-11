<?php
/**
 * Design System: Listerader (List Items) - Compound patterns
 * Shows common list row patterns used in Min Side and catalogs.
 */
if (!defined('ABSPATH')) exit;
?>
<h2 class="ds-section__title">Listerader</h2>
<p class="ds-section__desc">Vanlige listerad-monstre for lister og tabeller. Bruker <code>divide-y</code>-monster med hairline-dividers.</p>

<!-- Tool list items -->
<div style="margin-top: 24px;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Verktoy-rad</h3>
    <div style="border-top: 1px solid #E7E5E4;">

        <!-- Tool item 1 -->
        <a href="#" style="display: flex; align-items: center; gap: 16px; padding: 16px 0; border-bottom: 1px solid #E7E5E4; text-decoration: none; color: inherit; transition: background 0.1s;" onmouseenter="this.style.background='#FAFAF9'" onmouseleave="this.style.background='transparent'">
            <div style="width: 40px; height: 40px; background: #FFF3ED; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #FF8B5E; flex-shrink: 0;">
                <?php echo bimverdi_icon('wrench', 18); ?>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 15px; font-weight: 500; color: #1A1A1A;">Solibri Office</div>
                <div style="font-size: 13px; color: #888; margin-top: 2px;">Modellsjekk og kvalitetskontroll</div>
            </div>
            <?php bimverdi_badge(['text' => 'Modellsjekk', 'variant' => 'category', 'size' => 'small']); ?>
            <div style="color: #A8A29E; flex-shrink: 0;">
                <?php echo bimverdi_icon('chevron-right', 16); ?>
            </div>
        </a>

        <!-- Tool item 2 -->
        <a href="#" style="display: flex; align-items: center; gap: 16px; padding: 16px 0; border-bottom: 1px solid #E7E5E4; text-decoration: none; color: inherit; transition: background 0.1s;" onmouseenter="this.style.background='#FAFAF9'" onmouseleave="this.style.background='transparent'">
            <div style="width: 40px; height: 40px; background: #FFF3ED; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #FF8B5E; flex-shrink: 0;">
                <?php echo bimverdi_icon('wrench', 18); ?>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 15px; font-weight: 500; color: #1A1A1A;">Revit</div>
                <div style="font-size: 13px; color: #888; margin-top: 2px;">BIM-modellering for arkitektur og konstruksjon</div>
            </div>
            <?php bimverdi_badge(['text' => 'Modellering', 'variant' => 'category', 'size' => 'small']); ?>
            <div style="color: #A8A29E; flex-shrink: 0;">
                <?php echo bimverdi_icon('chevron-right', 16); ?>
            </div>
        </a>

    </div>
</div>

<!-- Person list items -->
<div style="margin-top: 40px;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Person-rad</h3>
    <div style="border-top: 1px solid #E7E5E4;">

        <!-- Person item 1 -->
        <div style="display: flex; align-items: center; gap: 16px; padding: 16px 0; border-bottom: 1px solid #E7E5E4;">
            <div style="width: 40px; height: 40px; background: #1A1A1A; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; font-weight: 600; flex-shrink: 0;">AH</div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 15px; font-weight: 500; color: #1A1A1A;">Andreas Harstad</div>
                <div style="font-size: 13px; color: #888; margin-top: 2px;">Hovedkontakt</div>
            </div>
            <?php bimverdi_badge(['text' => 'Partner', 'color' => 'purple', 'size' => 'small']); ?>
        </div>

        <!-- Person item 2 -->
        <div style="display: flex; align-items: center; gap: 16px; padding: 16px 0; border-bottom: 1px solid #E7E5E4;">
            <div style="width: 40px; height: 40px; background: #3B82F6; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; font-weight: 600; flex-shrink: 0;">KN</div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 15px; font-weight: 500; color: #1A1A1A;">Kari Nordmann</div>
                <div style="font-size: 13px; color: #888; margin-top: 2px;">Tilleggskontakt</div>
            </div>
            <?php bimverdi_badge(['text' => 'Deltaker', 'color' => 'blue', 'size' => 'small']); ?>
        </div>

    </div>
</div>

<!-- Article list items -->
<div style="margin-top: 40px;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Artikkel-rad</h3>
    <div style="border-top: 1px solid #E7E5E4;">

        <!-- Article item 1 -->
        <a href="#" style="display: flex; align-items: center; gap: 16px; padding: 16px 0; border-bottom: 1px solid #E7E5E4; text-decoration: none; color: inherit; transition: background 0.1s;" onmouseenter="this.style.background='#FAFAF9'" onmouseleave="this.style.background='transparent'">
            <div style="width: 64px; height: 48px; background: #F5F5F4; border-radius: 6px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #A8A29E;">
                <?php echo bimverdi_icon('file-text', 20); ?>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 15px; font-weight: 500; color: #1A1A1A;">Slik kommer du i gang med BIM</div>
                <div style="font-size: 13px; color: #888; margin-top: 2px;">14. mars 2026</div>
            </div>
            <div style="color: #A8A29E; flex-shrink: 0;">
                <?php echo bimverdi_icon('chevron-right', 16); ?>
            </div>
        </a>

        <!-- Article item 2 -->
        <a href="#" style="display: flex; align-items: center; gap: 16px; padding: 16px 0; border-bottom: 1px solid #E7E5E4; text-decoration: none; color: inherit; transition: background 0.1s;" onmouseenter="this.style.background='#FAFAF9'" onmouseleave="this.style.background='transparent'">
            <div style="width: 64px; height: 48px; background: #F5F5F4; border-radius: 6px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #A8A29E;">
                <?php echo bimverdi_icon('file-text', 20); ?>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 15px; font-weight: 500; color: #1A1A1A;">Erfaringer fra pilotprosjekt Gronn BIM</div>
                <div style="font-size: 13px; color: #888; margin-top: 2px;">8. mars 2026</div>
            </div>
            <div style="color: #A8A29E; flex-shrink: 0;">
                <?php echo bimverdi_icon('chevron-right', 16); ?>
            </div>
        </a>

    </div>
</div>
