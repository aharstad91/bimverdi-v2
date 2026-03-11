<?php
/**
 * Design System: Skjema-elementer (Form Elements)
 * Shows form inputs, textareas, selects, checkboxes, and radios.
 * Styling is defined globally in design-system.php.
 */
if (!defined('ABSPATH')) exit;
?>
<h2 class="ds-section__title">Skjema-elementer</h2>
<p class="ds-section__desc">Standard skjema-elementer. Styling er definert i <code>design-system.php</code> og gjelder automatisk for alle <code>input</code>, <code>textarea</code>, og <code>select</code>-elementer.</p>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 48px; margin-top: 24px;">

    <!-- Column 1: Text inputs -->
    <div>
        <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Tekstfelt</h3>

        <!-- Normal text input -->
        <div class="form-group" style="max-width: 400px;">
            <label>Fornavn</label>
            <input type="text" placeholder="Skriv inn fornavn">
            <div style="font-size: 12px; color: #888; margin-top: 4px;">Hjelpetekst vises her</div>
        </div>

        <!-- Error state -->
        <div class="form-group" style="max-width: 400px;">
            <label>E-post</label>
            <input type="email" value="ugyldig-epost" style="border-color: #DC2626;">
            <div style="font-size: 12px; color: #DC2626; margin-top: 4px;">Vennligst oppgi en gyldig e-postadresse</div>
        </div>

        <!-- Disabled state -->
        <div class="form-group" style="max-width: 400px;">
            <label style="color: #A8A29E;">Organisasjonsnummer</label>
            <input type="text" value="987 654 321" disabled style="opacity: 0.6; cursor: not-allowed;">
            <div style="font-size: 12px; color: #888; margin-top: 4px;">Feltet kan ikke redigeres</div>
        </div>
    </div>

    <!-- Column 2: Other fields -->
    <div>
        <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Andre felt</h3>

        <!-- Textarea -->
        <div class="form-group" style="max-width: 400px;">
            <label>Beskrivelse</label>
            <textarea rows="4" placeholder="Skriv en beskrivelse..."></textarea>
        </div>

        <!-- Select -->
        <div class="form-group" style="max-width: 400px;">
            <label>Kategori</label>
            <select>
                <option value="">Velg kategori...</option>
                <option>Verktøy</option>
                <option>Artikkel</option>
                <option>Arrangement</option>
            </select>
        </div>
    </div>

</div>

<!-- Checkboxes and Radios -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 48px; margin-top: 32px; padding-top: 32px; border-top: 1px solid #E7E5E4;">

    <div>
        <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Checkboxes</h3>

        <div class="checkbox-group" style="gap: 0;">

            <!-- Simple checkbox -->
            <label class="checkbox-item" style="padding: 8px 0;">
                <input type="checkbox"> Standard
            </label>

            <!-- Checked with description -->
            <label class="checkbox-item" style="padding: 8px 0;">
                <input type="checkbox" checked>
                <div>
                    <span style="font-weight: 500; color: #18181B;">Godta vilkår og betingelser</span>
                    <span style="display: block; font-size: 13px; color: #71717A; margin-top: 1px;">Ved å krysse av godtar du vilkårene.</span>
                </div>
            </label>

            <!-- Disabled -->
            <label class="checkbox-item" style="padding: 8px 0; opacity: 0.5; cursor: not-allowed;">
                <input type="checkbox" disabled> Deaktivert
            </label>

            <!-- Card variant -->
            <label class="checkbox-item" style="padding: 12px 16px; border: 1px solid #E4E4E7; border-radius: 8px; margin-top: 8px;">
                <input type="checkbox">
                <div>
                    <span style="font-weight: 500; color: #18181B;">Aktiver varsler</span>
                    <span style="display: block; font-size: 13px; color: #71717A; margin-top: 1px;">Du kan slå av og på varsler når som helst.</span>
                </div>
            </label>

        </div>
    </div>

    <div>
        <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">Radio-knapper</h3>

        <div class="radio-group" style="gap: 0;">

            <!-- Simple radio group -->
            <label class="radio-item" style="padding: 8px 0;">
                <input type="radio" name="ds-demo-radio"> Standard
            </label>

            <label class="radio-item" style="padding: 8px 0;">
                <input type="radio" name="ds-demo-radio" checked>
                <span style="font-weight: 500; color: #18181B;">Komfortabel</span>
            </label>

            <label class="radio-item" style="padding: 8px 0;">
                <input type="radio" name="ds-demo-radio"> Kompakt
            </label>

        </div>

        <!-- Card variant radios -->
        <div class="radio-group" style="gap: 8px; margin-top: 24px;">
            <h4 style="font-size: 12px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Kort-variant</h4>

            <label class="radio-item" style="padding: 12px 16px; border: 1px solid #E4E4E7; border-radius: 8px;">
                <input type="radio" name="ds-demo-radio-card">
                <div>
                    <span style="font-weight: 500; color: #18181B;">Deltaker</span>
                    <span style="display: block; font-size: 13px; color: #71717A; margin-top: 1px;">Standard medlemskap med tilgang til alle verktøy.</span>
                </div>
            </label>

            <label class="radio-item" style="padding: 12px 16px; border: 1px solid #E4E4E7; border-radius: 8px;">
                <input type="radio" name="ds-demo-radio-card" checked>
                <div>
                    <span style="font-weight: 500; color: #18181B;">Partner</span>
                    <span style="display: block; font-size: 13px; color: #71717A; margin-top: 1px;">Utvidet tilgang med prosjektdeltakelse.</span>
                </div>
            </label>

        </div>
    </div>

</div>

<!-- CSS classes reference -->
<div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #E7E5E4;">
    <h3 style="font-size: 13px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">CSS-klasser</h3>
    <div style="font-size: 13px; color: #5A5A5A; line-height: 2;">
        <code>.form-group</code> &mdash; wrapper for label + input + helptext<br>
        <code>.checkbox-group</code> / <code>.radio-group</code> &mdash; flex column container<br>
        <code>.checkbox-item</code> / <code>.radio-item</code> &mdash; flex row with gap<br>
        Custom styling via <code>appearance: none</code> &mdash; ingen browser-defaults<br>
        Kort-variant: legg til border + padding + border-radius på <code>.checkbox-item</code> / <code>.radio-item</code>
    </div>
</div>
