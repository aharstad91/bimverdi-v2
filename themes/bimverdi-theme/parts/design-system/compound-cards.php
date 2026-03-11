<?php
/**
 * Design System: Kort (Cards) - All card patterns used across the site
 * "Maxed out" versions — every field populated to show full potential.
 */
if (!defined('ABSPATH')) exit;

// Shared styles for card labels
$label_style = 'font-size: 11px; font-weight: 600; color: #A1A1AA; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;';
$card_base = 'background: #fff; border: 1px solid #E7E5E4; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);';
?>
<h2 class="ds-section__title">Kort</h2>
<p class="ds-section__desc">Alle kort-varianter, "maxed out" med all data utfylt. Brukes som grunnlag for a standardisere header, body, footer og CTA-monster.</p>

<!-- ===== ANATOMY REFERENCE ===== -->
<div style="background: #FAFAF9; border: 1px solid #E7E5E4; border-radius: 12px; padding: 24px; margin-bottom: 40px;">
    <h3 style="font-size: 14px; font-weight: 600; color: #1A1A1A; margin: 0 0 12px;">Kort-anatomi (forslag til standard)</h3>
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; font-size: 13px; color: #5A5A5A;">
        <div>
            <strong style="color: #1A1A1A;">Header</strong><br>
            Logo/ikon (venstre) + Badge (hoyre)<br>
            <span style="color: #A1A1AA;">Sporsmal: Rund vs firkantet? Storrelse?</span>
        </div>
        <div>
            <strong style="color: #1A1A1A;">Body</strong><br>
            Tittel + subtittel/metadata + beskrivelse<br>
            <span style="color: #A1A1AA;">Sporsmal: Badges over eller under tittel?</span>
        </div>
        <div>
            <strong style="color: #1A1A1A;">Footer</strong><br>
            Divider + metadata (venstre) + CTA (hoyre)<br>
            <span style="color: #A1A1AA;">Sporsmal: Lenke vs knapp? Tekst?</span>
        </div>
    </div>
</div>

<!-- ===== ROW 1: Archive entity cards — MAXED OUT ===== -->
<h3 style="<?php echo $label_style; ?> margin: 0 0 8px;">Arkiv-kort — maxed out</h3>
<p style="font-size: 13px; color: #71717A; margin-bottom: 16px;">Hvert kort med all tilgjengelig data utfylt. Slik ser de ut nar alt er pa plass.</p>

<div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 48px;">

    <!-- FORETAK — maxed out -->
    <div>
        <div style="<?php echo $label_style; ?>">Foretak</div>
        <div style="<?php echo $card_base; ?> padding: 20px; display: flex; flex-direction: column;">
            <!-- Header: Logo + membership badge -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                <div style="width: 56px; height: 56px; background: #F5F5F4; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #5A5A5A; font-size: 13px; border: 1px solid #E7E5E4;">NKF</div>
                <span style="font-size: 11px; padding: 3px 10px; background: #FFF3ED; color: #FF8B5E; border-radius: 999px; font-weight: 600;">Partner</span>
            </div>
            <!-- Body: Name + location + industry -->
            <h4 style="font-size: 15px; font-weight: 600; color: #111827; margin: 0 0 6px; line-height: 1.3;">Norsk Kommunalteknisk Forening</h4>
            <p style="font-size: 13px; color: #6B7280; margin: 0 0 4px; display: flex; align-items: center; gap: 4px;">
                <?php echo bimverdi_icon('map-pin', 12); ?> Oslo
            </p>
            <p style="font-size: 12px; color: #A1A1AA; margin: 0;">3 temagrupper &middot; 12 ansatte</p>
            <!-- Spacer -->
            <div style="flex: 1; min-height: 16px;"></div>
            <!-- Footer: Industry + CTA -->
            <div style="border-top: 1px solid #E7E5E4; padding-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 11px; color: #A1A1AA; text-transform: uppercase; letter-spacing: 0.03em;">Organisasjon, nettverk m.m.</span>
                <a href="#" style="font-size: 13px; color: #1A1A1A; font-weight: 500; text-decoration: none; display: flex; align-items: center; gap: 2px;">Se profil <?php echo bimverdi_icon('chevron-right', 14); ?></a>
            </div>
        </div>
    </div>

    <!-- VERKTØY — maxed out -->
    <div>
        <div style="<?php echo $label_style; ?>">Verktoy</div>
        <div style="<?php echo $card_base; ?> padding: 20px; display: flex; flex-direction: column;">
            <!-- Header: Logo + type badge -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                <div style="width: 56px; height: 56px; background: #F0FDF4; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #16A34A; font-weight: 700; font-size: 11px; border: 1px solid #BBF7D0;">CB</div>
                <span style="font-size: 11px; padding: 3px 10px; background: #F3F4F6; color: #6B7280; border-radius: 999px; font-weight: 500;">Programvare</span>
            </div>
            <!-- Body: Name + owner + temagruppe -->
            <h4 style="font-size: 15px; font-weight: 600; color: #111827; margin: 0 0 6px; line-height: 1.3;">Catenda Boost</h4>
            <p style="font-size: 13px; color: #6B7280; margin: 0 0 4px; display: flex; align-items: center; gap: 4px;">
                <?php echo bimverdi_icon('building-2', 12); ?> Catenda AS
            </p>
            <p style="font-size: 12px; color: #A1A1AA; margin: 0;">BIMtech &middot; ProsjektBIM</p>
            <!-- Spacer -->
            <div style="flex: 1; min-height: 16px;"></div>
            <!-- Footer -->
            <div style="border-top: 1px solid #E7E5E4; padding-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 11px; color: #A1A1AA; text-transform: uppercase; letter-spacing: 0.03em;">Programvare</span>
                <a href="#" style="font-size: 13px; color: #1A1A1A; font-weight: 500; text-decoration: none; display: flex; align-items: center; gap: 2px;">Se detaljer <?php echo bimverdi_icon('chevron-right', 14); ?></a>
            </div>
        </div>
    </div>

    <!-- ARTIKKEL — maxed out -->
    <div>
        <div style="<?php echo $label_style; ?>">Artikkel</div>
        <div style="<?php echo $card_base; ?> overflow: hidden; display: flex; flex-direction: column;">
            <!-- Header: Featured image -->
            <div style="height: 140px; background: linear-gradient(135deg, #D4C5B0 0%, #B8A990 100%); display: flex; align-items: center; justify-content: center; position: relative;">
                <div style="color: rgba(255,255,255,0.5);"><?php echo bimverdi_icon('image', 32); ?></div>
                <!-- Temagruppe badge overlay -->
                <span style="position: absolute; top: 10px; left: 10px; font-size: 11px; padding: 2px 8px; background: rgba(255,139,94,0.9); color: white; border-radius: 4px; font-weight: 500;">BIMtech</span>
            </div>
            <!-- Body -->
            <div style="padding: 16px; flex: 1; display: flex; flex-direction: column;">
                <div style="display: flex; gap: 4px; margin-bottom: 8px;">
                    <span style="font-size: 11px; padding: 2px 6px; background: #F3F4F6; color: #6B7280; border-radius: 4px;">Fagartikkel</span>
                </div>
                <h4 style="font-size: 14px; font-weight: 600; color: #111827; margin: 0 0 6px; line-height: 1.3;">Mestergruppen onsker seg en modell- og byggeplassdrevet logistikk</h4>
                <p style="font-size: 12px; color: #6B7280; margin: 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">Er tiden moden for full digitalisering i alle ledd? Mestergruppen er av den oppfatning.</p>
                <!-- Spacer -->
                <div style="flex: 1; min-height: 12px;"></div>
                <!-- Footer: Author + date -->
                <div style="display: flex; align-items: center; gap: 8px; padding-top: 10px; border-top: 1px solid #E7E5E4;">
                    <div style="width: 28px; height: 28px; background: #E7E5E4; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; color: #5A5A5A;">BK</div>
                    <div>
                        <span style="font-size: 12px; color: #3F3F46; font-weight: 500;">Bard Krogshus</span>
                        <span style="font-size: 11px; color: #A1A1AA; display: block;">9. des 2021</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KUNNSKAPSKILDE — maxed out -->
    <div>
        <div style="<?php echo $label_style; ?>">Kunnskapskilde</div>
        <div style="<?php echo $card_base; ?> overflow: hidden; display: flex; flex-direction: column;">
            <!-- Header: Icon area -->
            <div style="height: 80px; background: #FAFAF9; display: flex; align-items: center; justify-content: center;">
                <div style="width: 48px; height: 48px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #1A1A1A; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                    <?php echo bimverdi_icon('shield', 24); ?>
                </div>
            </div>
            <!-- Body -->
            <div style="padding: 16px; flex: 1; display: flex; flex-direction: column;">
                <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px;">
                    <span style="font-size: 11px; padding: 2px 6px; background: #FFF3ED; color: #FF8B5E; border-radius: 4px;">BIMtech</span>
                    <span style="font-size: 11px; padding: 2px 6px; background: #EFF6FF; color: #2563EB; border-radius: 4px;">ByggesaksBIM</span>
                    <span style="font-size: 11px; padding: 2px 6px; background: #F3F4F6; color: #6B7280; border-radius: 4px;">Forskrift (norsk lov)</span>
                </div>
                <h4 style="font-size: 14px; font-weight: 600; color: #111827; margin: 0 0 4px; line-height: 1.3;">Anskaffelsesforskriften - DFO</h4>
                <p style="font-size: 12px; color: #6B7280; margin: 0 0 2px;">Direktoratet for forvaltning og okonomistyring</p>
                <p style="font-size: 11px; color: #A1A1AA; margin: 0;">2024 &middot; Forskrift (norsk lov)</p>
                <!-- Spacer -->
                <div style="flex: 1; min-height: 12px;"></div>
                <!-- Footer: Dual CTA -->
                <div style="display: flex; gap: 8px; padding-top: 12px; border-top: 1px solid #E7E5E4;">
                    <a href="#" style="flex: 1; text-align: center; font-size: 12px; padding: 7px; border: 1px solid #E7E5E4; border-radius: 6px; color: #5A5A5A; text-decoration: none; font-weight: 500;">Se detaljer</a>
                    <a href="#" style="flex: 1; text-align: center; font-size: 12px; padding: 7px; background: #1A1A1A; border-radius: 6px; color: white; text-decoration: none; font-weight: 500;">Besok</a>
                </div>
            </div>
        </div>
    </div>

    <!-- ARRANGEMENT — maxed out -->
    <div>
        <div style="<?php echo $label_style; ?>">Arrangement</div>
        <div style="<?php echo $card_base; ?> padding: 20px; display: flex; flex-direction: column;">
            <!-- Header: Date badge + type badge -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                <div style="background: #1A1A1A; color: #fff; border-radius: 10px; padding: 10px 14px; text-align: center; min-width: 52px;">
                    <div style="font-size: 22px; font-weight: 700; line-height: 1;">15</div>
                    <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.7;">Mar</div>
                </div>
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                    <span style="font-size: 11px; padding: 3px 10px; background: #DCFCE7; color: #166534; border-radius: 999px; font-weight: 500;">Fysisk</span>
                    <span style="font-size: 11px; padding: 3px 10px; background: #DBEAFE; color: #1E40AF; border-radius: 999px; font-weight: 500;">Fagmote</span>
                </div>
            </div>
            <!-- Body: Title + time + location + description -->
            <h4 style="font-size: 15px; font-weight: 600; color: #111827; margin: 0 0 8px; line-height: 1.3;">BIMtech Fagmote: Digital tvilling i praksis</h4>
            <p style="font-size: 13px; color: #6B7280; margin: 0 0 3px; display: flex; align-items: center; gap: 4px;">
                <?php echo bimverdi_icon('clock', 12); ?> 10:00–12:00
            </p>
            <p style="font-size: 13px; color: #6B7280; margin: 0 0 3px; display: flex; align-items: center; gap: 4px;">
                <?php echo bimverdi_icon('map-pin', 12); ?> Rebel, Universitetsgata 2, Oslo
            </p>
            <p style="font-size: 13px; color: #6B7280; margin: 0; display: flex; align-items: center; gap: 4px;">
                <?php echo bimverdi_icon('users', 12); ?> 24 pameldt
            </p>
            <!-- Spacer -->
            <div style="flex: 1; min-height: 16px;"></div>
            <!-- Footer: Temagruppe + CTA -->
            <div style="border-top: 1px solid #E7E5E4; padding-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 11px; color: #FF8B5E; font-weight: 600;">BIMtech</span>
                <a href="#" style="font-size: 13px; color: #1A1A1A; font-weight: 500; text-decoration: none; display: flex; align-items: center; gap: 2px;">Se arrangement <?php echo bimverdi_icon('chevron-right', 14); ?></a>
            </div>
        </div>
    </div>

</div>

<!-- ===== STRUCTURAL COMPARISON TABLE ===== -->
<div style="padding: 24px 0 32px; border-top: 1px solid #E7E5E4;">
    <h3 style="<?php echo $label_style; ?> margin-bottom: 16px;">Strukturell sammenligning</h3>
    <div style="overflow-x: auto;">
        <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #E7E5E4; text-align: left;">
                    <th style="padding: 8px 12px; color: #888; font-weight: 600;">Element</th>
                    <th style="padding: 8px 12px; color: #888; font-weight: 600;">Foretak</th>
                    <th style="padding: 8px 12px; color: #888; font-weight: 600;">Verktoy</th>
                    <th style="padding: 8px 12px; color: #888; font-weight: 600;">Artikkel</th>
                    <th style="padding: 8px 12px; color: #888; font-weight: 600;">Kunnskapskilde</th>
                    <th style="padding: 8px 12px; color: #888; font-weight: 600;">Arrangement</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px solid #F3F4F6;">
                    <td style="padding: 8px 12px; font-weight: 500; color: #1A1A1A;">Header visuell</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Logo (rund)</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Logo (firkantet)</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Bilde (full bredde)</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Ikon (sentrert boks)</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Dato-badge (mork)</td>
                </tr>
                <tr style="border-bottom: 1px solid #F3F4F6;">
                    <td style="padding: 8px 12px; font-weight: 500; color: #1A1A1A;">Header badge</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Medlemstype</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Verktoytype</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Temagruppe (overlay)</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Temagrupper + type</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Format + type</td>
                </tr>
                <tr style="border-bottom: 1px solid #F3F4F6;">
                    <td style="padding: 8px 12px; font-weight: 500; color: #1A1A1A;">Subtittel</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Sted</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Foretak (eier)</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Kategori-badge</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Utgiver + ar</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Tid + sted + pameldt</td>
                </tr>
                <tr style="border-bottom: 1px solid #F3F4F6;">
                    <td style="padding: 8px 12px; font-weight: 500; color: #1A1A1A;">Ekstra metadata</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Temagrupper, ansatte</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Temagrupper</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Beskrivelse (2 linjer)</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">—</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">—</td>
                </tr>
                <tr style="border-bottom: 1px solid #F3F4F6;">
                    <td style="padding: 8px 12px; font-weight: 500; color: #1A1A1A;">Footer venstre</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Bransje (uppercase)</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Kategori (uppercase)</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Forfatter + dato</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">—</td>
                    <td style="padding: 8px 12px; color: #5A5A5A;">Temagruppe-navn</td>
                </tr>
                <tr style="border-bottom: 1px solid #F3F4F6; background: #FFFBEB;">
                    <td style="padding: 8px 12px; font-weight: 500; color: #92400E;">Footer CTA ⚠️</td>
                    <td style="padding: 8px 12px; color: #92400E;">Tekstlenke + ikon</td>
                    <td style="padding: 8px 12px; color: #92400E;">Tekstlenke + ikon</td>
                    <td style="padding: 8px 12px; color: #92400E;">Ingen (kort er lenke)</td>
                    <td style="padding: 8px 12px; color: #92400E;">2 knapper (!)</td>
                    <td style="padding: 8px 12px; color: #92400E;">Tekstlenke + ikon</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== ROW 2: Temagruppe card variants ===== -->
<h3 style="<?php echo $label_style; ?> margin: 0 0 8px; padding-top: 32px; border-top: 1px solid #E7E5E4;">Temagruppe-kort — varianter</h3>
<p style="font-size: 13px; color: #71717A; margin-bottom: 16px;">3 ulike varianter i bruk. Samme data, helt ulik presentasjon.</p>

<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 48px;">

    <!-- Temagruppe detail card — maxed out -->
    <div>
        <div style="<?php echo $label_style; ?>">Detalj-kort (single-temagruppe)</div>
        <div style="background: #fff; border: 1px solid #E5E0D5; border-radius: 8px; padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                <div style="width: 48px; height: 48px; background: #FFF3ED; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #FF8B5E;">
                    <?php echo bimverdi_icon('cpu', 20); ?>
                </div>
                <span style="font-size: 11px; padding: 3px 8px; background: #DCFCE7; color: #166534; border-radius: 999px; font-weight: 500; display: flex; align-items: center; gap: 4px;">
                    <span style="width: 6px; height: 6px; background: #22C55E; border-radius: 50%; display: inline-block;"></span> Aktiv
                </span>
            </div>
            <h4 style="font-size: 16px; font-weight: 600; color: #1A1A1A; margin: 0 0 6px;">BIMtech</h4>
            <p style="font-size: 14px; color: #5A5A5A; line-height: 1.5; margin: 0 0 16px;">Teknisk BIM-kompetanse og samhandling. Fokus pa interoperabilitet, apne standarder og modellbasert arbeidsflyt.</p>
            <div style="border-top: 1px solid #E5E0D5; padding-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 13px; color: #6B7280;">12 medlemmer &middot; 4 foretak</span>
                <a href="#" style="font-size: 13px; color: #FF8B5E; font-weight: 500; text-decoration: none;">Les mer &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Forside mobile temagruppe card -->
    <div>
        <div style="<?php echo $label_style; ?>">Forside-kort (bv3-tg-card)</div>
        <div style="background: #fff; border: 1px solid #E7E5E4; border-radius: 12px; padding: 20px; border-top: 3px solid #FF8B5E;">
            <div style="width: 44px; height: 44px; background: #FFF3ED; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #FF8B5E; margin-bottom: 12px;">
                <?php echo bimverdi_icon('cpu', 20); ?>
            </div>
            <h4 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 0 0 6px;">BIMtech</h4>
            <p style="font-size: 13px; color: #5A5A5A; line-height: 1.5; margin: 0;">Teknisk BIM-kompetanse og samhandling.</p>
        </div>
    </div>

    <!-- Livssyklus card -->
    <div>
        <div style="<?php echo $label_style; ?>">Livssyklus-kort (bv3-tg-card2)</div>
        <div style="background: #fff; border: 1px solid #E7E5E4; border-radius: 12px; padding: 20px; position: relative;">
            <div style="position: absolute; top: -1px; left: 20px; width: 32px; height: 32px; background: #FF8B5E; color: white; border-radius: 0 0 8px 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">1</div>
            <div style="margin-top: 24px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <span style="width: 8px; height: 8px; background: #FF8B5E; border-radius: 50%; display: inline-block;"></span>
                    <h4 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 0;">BIMtech</h4>
                </div>
                <p style="font-size: 13px; color: #5A5A5A; line-height: 1.5; margin: 0;">Teknisk BIM-kompetanse og samhandling.</p>
            </div>
        </div>
    </div>

</div>

<!-- ===== ROW 3: Feature cards ===== -->
<h3 style="<?php echo $label_style; ?> margin: 0 0 8px; padding-top: 32px; border-top: 1px solid #E7E5E4;">Forside feature-kort</h3>
<p style="font-size: 13px; color: #71717A; margin-bottom: 16px;">Todelt kort (visuell sone + innhold). Brukt kun pa forsiden.</p>

<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 48px;">

    <div>
        <div style="<?php echo $label_style; ?>">Standard</div>
        <div style="<?php echo $card_base; ?> border-radius: 16px; overflow: hidden;">
            <div style="height: 160px; background: #F5F5F4; display: flex; align-items: center; justify-content: center; color: #A8A29E; position: relative;">
                <?php echo bimverdi_icon('wrench', 44); ?>
                <span style="position: absolute; top: 12px; right: 12px; font-size: 11px; padding: 3px 10px; background: #FF8B5E; color: white; border-radius: 999px; font-weight: 600;">36 stk</span>
            </div>
            <div style="padding: 24px;">
                <h4 style="font-size: 18px; font-weight: 700; color: #1A1A1A; margin: 0 0 8px;">Verktoy</h4>
                <p style="font-size: 14px; color: #5A5A5A; line-height: 1.5; margin: 0 0 16px;">Utforsk programvare og tjenester for BIM-arbeid.</p>
                <a href="#" style="display: inline-block; font-size: 13px; padding: 8px 20px; border: 1px solid #E7E5E4; border-radius: 999px; color: #1A1A1A; text-decoration: none; font-weight: 500;">Se alle verktoy &rarr;</a>
            </div>
        </div>
    </div>

    <div>
        <div style="<?php echo $label_style; ?>">Arrangement-variant (mork)</div>
        <div style="background: #1A1A1A; border-radius: 16px; overflow: hidden; color: white;">
            <div style="height: 160px; display: flex; align-items: center; justify-content: center; position: relative;">
                <div style="text-align: center;">
                    <div style="font-size: 44px; font-weight: 700; line-height: 1;">15</div>
                    <div style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.1em; opacity: 0.6;">Mars 2026</div>
                </div>
                <span style="position: absolute; top: 12px; right: 12px; font-size: 11px; padding: 3px 10px; background: rgba(255,255,255,0.15); color: white; border-radius: 999px; font-weight: 500;">Fysisk</span>
            </div>
            <div style="padding: 24px;">
                <h4 style="font-size: 18px; font-weight: 700; margin: 0 0 8px;">BIMtech Fagmote: Digital tvilling</h4>
                <p style="font-size: 14px; opacity: 0.6; margin: 0 0 16px;">10:00–12:00 &middot; Rebel, Oslo &middot; 24 pameldt</p>
                <a href="#" style="display: inline-block; font-size: 13px; padding: 8px 20px; border: 1px solid rgba(255,255,255,0.2); border-radius: 999px; color: white; text-decoration: none; font-weight: 500;">Meld deg pa &rarr;</a>
            </div>
        </div>
    </div>

    <div>
        <div style="<?php echo $label_style; ?>">Tomt (ingen data)</div>
        <div style="background: #FAFAF9; border: 1px solid #E7E5E4; border-radius: 16px; overflow: hidden;">
            <div style="height: 160px; display: flex; align-items: center; justify-content: center; color: #D6D3D1;">
                <?php echo bimverdi_icon('calendar', 44); ?>
            </div>
            <div style="padding: 24px;">
                <h4 style="font-size: 18px; font-weight: 700; color: #A8A29E; margin: 0 0 8px;">Ingen arrangementer</h4>
                <p style="font-size: 14px; color: #D6D3D1; margin: 0 0 16px;">Ingen kommende arrangementer akkurat na.</p>
                <a href="#" style="display: inline-block; font-size: 13px; padding: 8px 20px; border: 1px solid #E7E5E4; border-radius: 999px; color: #A8A29E; text-decoration: none; font-weight: 500;">Se tidligere &rarr;</a>
            </div>
        </div>
    </div>

</div>

<!-- ===== CONSISTENCY NOTES ===== -->
<div style="padding: 24px; border: 1px solid #FDE68A; background: #FFFBEB; border-radius: 12px;">
    <h3 style="font-size: 14px; font-weight: 600; color: #92400E; margin: 0 0 12px;">Inkonsistens-oppsummering</h3>
    <div style="font-size: 13px; color: #78350F; line-height: 1.8;">
        <strong>CTA-monster:</strong> Foretak/Verktoy/Arrangement bruker tekstlenke + chevron. Kunnskapskilde bruker 2 knapper. Artikkel har ingen CTA (hele kortet er lenke).<br>
        <strong>Border-farge:</strong> <code>#E7E5E4</code> (4 kort) vs <code>#E5E0D5</code> (temagruppe-detalj)<br>
        <strong>Border-radius:</strong> <code>8px</code> (temagruppe) vs <code>12px</code> (arkiv) vs <code>16px</code> (forside)<br>
        <strong>Hover:</strong> 3 ulike implementasjoner<br>
        <strong>Badge-plassering:</strong> Top-right (foretak/verktoy), overlay pa bilde (artikkel), under ikon (kunnskapskilde), top-right stacked (arrangement)<br>
        <strong>Footer-divider:</strong> Alle har <code>border-top</code>, men ulik padding og innhold
    </div>
</div>
