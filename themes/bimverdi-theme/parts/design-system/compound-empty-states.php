<?php
/**
 * Design System: Tomme tilstander (Empty States) - Compound patterns
 * Shows empty state patterns for when lists or sections have no content.
 */
if (!defined('ABSPATH')) exit;
?>
<h2 class="ds-section__title">Tomme tilstander</h2>
<p class="ds-section__desc">Vises nar en liste eller seksjon er tom. Sentrert innhold med ikon, tittel, beskrivelse og valgfri CTA.</p>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-top: 24px;">

    <!-- Empty state 1: With CTA -->
    <div>
        <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Med CTA-knapp</h3>
        <div style="background: #FAFAF9; border: 1px dashed #E7E5E4; border-radius: 12px; padding: 48px 32px; text-align: center;">
            <div style="width: 48px; height: 48px; background: #F5F5F4; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #A8A29E; margin: 0 auto 16px;">
                <?php echo bimverdi_icon('wrench', 24); ?>
            </div>
            <h4 style="font-size: 16px; font-weight: 500; color: #1A1A1A; margin: 0 0 8px 0;">Ingen verktoy registrert</h4>
            <p style="font-size: 14px; color: #888; margin: 0 0 20px 0; max-width: 280px; margin-left: auto; margin-right: auto;">Foretaket ditt har ikke registrert noen verktoy enna. Kom i gang ved a registrere det forste.</p>
            <?php bimverdi_button([
                'text' => 'Registrer verktoy',
                'variant' => 'primary',
                'icon' => 'plus',
                'href' => '#',
            ]); ?>
        </div>
    </div>

    <!-- Empty state 2: Informational only -->
    <div>
        <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Kun informasjon</h3>
        <div style="background: #FAFAF9; border: 1px dashed #E7E5E4; border-radius: 12px; padding: 48px 32px; text-align: center;">
            <div style="width: 48px; height: 48px; background: #F5F5F4; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #A8A29E; margin: 0 auto 16px;">
                <?php echo bimverdi_icon('calendar', 24); ?>
            </div>
            <h4 style="font-size: 16px; font-weight: 500; color: #1A1A1A; margin: 0 0 8px 0;">Ingen kommende arrangementer</h4>
            <p style="font-size: 14px; color: #888; margin: 0; max-width: 280px; margin-left: auto; margin-right: auto;">Det er ingen planlagte arrangementer akkurat na. Sjekk tilbake snart.</p>
        </div>
    </div>

</div>

<!-- Compact inline variant -->
<div style="margin-top: 32px; padding-top: 32px; border-top: 1px solid #E7E5E4;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Kompakt (inline)</h3>
    <div style="background: #FAFAF9; border-radius: 8px; padding: 24px; display: flex; align-items: center; gap: 16px; max-width: 500px;">
        <div style="width: 36px; height: 36px; background: #F5F5F4; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #A8A29E; flex-shrink: 0;">
            <?php echo bimverdi_icon('file-text', 18); ?>
        </div>
        <div>
            <div style="font-size: 14px; font-weight: 500; color: #1A1A1A;">Ingen artikler enna</div>
            <div style="font-size: 13px; color: #888; margin-top: 2px;">Du har ikke skrevet noen artikler.</div>
        </div>
    </div>
</div>
