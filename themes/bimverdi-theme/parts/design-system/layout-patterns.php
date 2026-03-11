<?php
/**
 * Design System: Layout Patterns
 * Shows grid variants, section patterns, and max-width conventions.
 */
if (!defined('ABSPATH')) exit;
?>
<h2 class="ds-section__title">Layout</h2>
<p class="ds-section__desc">Grid-varianter, seksjonsmonstre og max-width-konvensjoner brukt i designet.</p>

<!-- Grid variants -->
<div style="margin-top: 24px;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">2-kolonne grid</h3>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; max-width: 800px;">
        <div style="background: #F5F5F4; border: 1px solid #E7E5E4; border-radius: 8px; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #888;">Kolonne 1</div>
        <div style="background: #F5F5F4; border: 1px solid #E7E5E4; border-radius: 8px; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #888;">Kolonne 2</div>
    </div>
</div>

<div style="margin-top: 32px;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">3-kolonne grid</h3>
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px;">
        <div style="background: #F5F5F4; border: 1px solid #E7E5E4; border-radius: 8px; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #888;">Kolonne 1</div>
        <div style="background: #F5F5F4; border: 1px solid #E7E5E4; border-radius: 8px; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #888;">Kolonne 2</div>
        <div style="background: #F5F5F4; border: 1px solid #E7E5E4; border-radius: 8px; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #888;">Kolonne 3</div>
    </div>
</div>

<div style="margin-top: 32px;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">4-kolonne grid</h3>
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 24px;">
        <div style="background: #F5F5F4; border: 1px solid #E7E5E4; border-radius: 8px; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #888;">1</div>
        <div style="background: #F5F5F4; border: 1px solid #E7E5E4; border-radius: 8px; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #888;">2</div>
        <div style="background: #F5F5F4; border: 1px solid #E7E5E4; border-radius: 8px; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #888;">3</div>
        <div style="background: #F5F5F4; border: 1px solid #E7E5E4; border-radius: 8px; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #888;">4</div>
    </div>
</div>

<!-- Alternating sections -->
<div style="margin-top: 40px; padding-top: 32px; border-top: 1px solid #E7E5E4;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Alternerende seksjonsbakgrunner</h3>
    <p style="font-size: 14px; color: #5A5A5A; margin-bottom: 16px;">Bytt mellom hvit og <code>#F5F5F4</code> (stone-100) for visuell separasjon mellom seksjoner.</p>
    <div style="border-radius: 8px; overflow: hidden; border: 1px solid #E7E5E4;">
        <div style="background: #FFFFFF; padding: 24px; text-align: center;">
            <span style="font-size: 13px; color: #888;">Seksjon 1 &mdash; background: #FFFFFF</span>
        </div>
        <div style="background: #F5F5F4; padding: 24px; text-align: center;">
            <span style="font-size: 13px; color: #888;">Seksjon 2 &mdash; background: #F5F5F4</span>
        </div>
        <div style="background: #FFFFFF; padding: 24px; text-align: center;">
            <span style="font-size: 13px; color: #888;">Seksjon 3 &mdash; background: #FFFFFF</span>
        </div>
        <div style="background: #F5F5F4; padding: 24px; text-align: center;">
            <span style="font-size: 13px; color: #888;">Seksjon 4 &mdash; background: #F5F5F4</span>
        </div>
    </div>
</div>

<!-- Max-widths -->
<div style="margin-top: 40px; padding-top: 32px; border-top: 1px solid #E7E5E4;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Max-bredder</h3>
    <p style="font-size: 14px; color: #5A5A5A; margin-bottom: 16px;">Konsistente max-width-verdier for ulike sidetyper.</p>

    <div style="display: flex; flex-direction: column; gap: 12px;">
        <!-- 960px -->
        <div>
            <div style="font-size: 12px; font-weight: 600; color: #888; margin-bottom: 4px;">960px &mdash; Skjema-sider</div>
            <div style="background: #FFF3ED; border: 1px solid #FFD4BF; border-radius: 4px; height: 24px; max-width: 960px;"></div>
        </div>

        <!-- 1200px -->
        <div>
            <div style="font-size: 12px; font-weight: 600; color: #888; margin-bottom: 4px;">1200px &mdash; Innholdssider og lister</div>
            <div style="background: #DBEAFE; border: 1px solid #93C5FD; border-radius: 4px; height: 24px; max-width: 1200px;"></div>
        </div>

        <!-- 1280px -->
        <div>
            <div style="font-size: 12px; font-weight: 600; color: #888; margin-bottom: 4px;">1280px &mdash; Bred layout (kataloger)</div>
            <div style="background: #DCFCE7; border: 1px solid #86EFAC; border-radius: 4px; height: 24px; max-width: 1280px;"></div>
        </div>
    </div>
</div>

<!-- Spacing scale -->
<div style="margin-top: 40px; padding-top: 32px; border-top: 1px solid #E7E5E4;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Spacing-skala</h3>
    <p style="font-size: 14px; color: #5A5A5A; margin-bottom: 16px;">8px-basert skala. Bruk Tailwind-klasser eller CSS-variabler.</p>
    <div style="display: flex; flex-direction: column; gap: 8px;">
        <?php
        $spacings = [
            ['4px', '--space-1', 'Minimal gap'],
            ['8px', '--space-2', 'Tight gap'],
            ['12px', '--space-3', 'Small gap'],
            ['16px', '--space-4', 'Default gap'],
            ['24px', '--space-6', 'Medium gap'],
            ['32px', '--space-8', 'Large gap'],
            ['48px', '--space-12', 'Section padding'],
            ['64px', '--space-16', 'Section spacing'],
        ];
        foreach ($spacings as $s) : ?>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: <?php echo $s[0]; ?>; height: 16px; background: #FF8B5E; border-radius: 2px; flex-shrink: 0;"></div>
                <span style="font-size: 13px; color: #1A1A1A; font-weight: 500; width: 40px;"><?php echo $s[0]; ?></span>
                <code style="font-size: 12px; color: #5A5A5A;"><?php echo $s[1]; ?></code>
                <span style="font-size: 12px; color: #888;"><?php echo $s[2]; ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
