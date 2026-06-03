<?php
/**
 * Nyhetsbrev-mal «Nytt & Nyttig fra BIM Verdi» (e-post-HTML)
 *
 * Tabellbasert, e-postklient-vennlig HTML med inline CSS. Rendres via
 * bimverdi_render_nyhetsbrev() i mu-plugins/bimverdi-nyhetsbrev-content.php.
 *
 * Forventer i scope:
 *   $data    — fra bimverdi_nyhetsbrev_collect()  (generert, seksjoner[])
 *   $context — lenker + avsender (profil_url, avmelding_url, avsender_navn, ...)
 *
 * Designvalg (jf. research 2026-06-03):
 *   - Enkelt-kolonne 600px, hero-først (toppartikkel stort bilde), liste under.
 *   - «Bilde hvis det finnes, ellers elegant uten» (artikkel ~97% har bilde,
 *     øvrige CPT-er sjelden) — fallback-kjede i bimverdi_nyhetsbrev_bilde().
 *   - Whitespace + eyebrow-labels som seksjonsskille (UI-Contract P1).
 *   - 60/30/10-farger: orange #FF8B5E kun til CTA/eyebrow.
 *   - Dark-mode-meta + off-white #FDFDFD, role=presentation, 16px body,
 *     stilsatt alt-tekst, bgcolor-fallback på bilde-celler.
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

// Defensiv fallback hvis malen inkluderes uten kontekst.
if (!isset($data) || !is_array($data)) {
    $data = function_exists('bimverdi_nyhetsbrev_collect') ? bimverdi_nyhetsbrev_collect() : ['seksjoner' => []];
}
if (!isset($context) || !is_array($context)) {
    $context = [
        'profil_url'      => home_url('/min-side/profil/rediger/'),
        'avmelding_url'   => '#',
        'avsender_navn'   => 'Bård Krogshus',
        'avsender_tittel' => 'BIM Verdi',
        'nettsted_url'    => home_url('/'),
    ];
}

$seksjoner = isset($data['seksjoner']) ? $data['seksjoner'] : [];
// Skjul tomme seksjoner (P4: vis bare det som finnes).
$seksjoner = array_values(array_filter($seksjoner, function ($s) {
    return !empty($s['items']);
}));

// Preheader: tease toppsaken (første item i første seksjon), ellers generisk.
$preheader = 'Det ferskeste fra nettverket — artikler, arrangementer, verktøy og mer.';
if (!empty($seksjoner[0]['items'][0]['tittel'])) {
    $preheader = 'Siste: ' . $seksjoner[0]['items'][0]['tittel'];
}

$muted = 'color:#5A5A5A;';
?><!DOCTYPE html>
<html lang="nb" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<title>Nytt &amp; Nyttig fra BIM Verdi</title>
<style>
    html, body { margin:0 !important; padding:0 !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
    img { -ms-interpolation-mode:bicubic; border:0; outline:none; text-decoration:none; }
    a { text-decoration:none; }
    /* Mobil: stable thumbnail-kolonner til full bredde */
    @media only screen and (max-width:480px) {
        .nb-thumb-cell, .nb-text-cell { display:block !important; width:100% !important; }
        .nb-thumb-cell { padding:0 0 12px 0 !important; }
        .nb-thumb-cell img { width:100% !important; max-width:100% !important; height:auto !important; }
    }
    /* Dark mode: behold lesbarhet (beige er en trygg midtone) */
    @media (prefers-color-scheme: dark) {
        .nb-canvas { background-color:#1f1d1a !important; }
        .nb-body { background-color:#26241f !important; }
        .nb-title, .nb-h1 { color:#f5f2ec !important; }
        .nb-text { color:#d8d2c7 !important; }
        .nb-muted { color:#b3ac9e !important; }
    }
</style>
</head>
<body class="nb-canvas" style="margin:0;padding:0;background-color:#F7F5EF;-webkit-font-smoothing:antialiased;">

<!-- Preheader (skjult i innboksen) -->
<div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all;">
    <?php echo esc_html($preheader); ?>
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="nb-canvas" style="background-color:#F7F5EF;">
<tr>
<td align="center" style="padding:24px 12px;">

    <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" class="nb-body" style="width:600px;max-width:600px;background-color:#FDFDFD;border:1px solid #D6D1C6;border-radius:12px;overflow:hidden;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;">

        <!-- Header -->
        <tr>
            <td style="padding:32px 32px 24px 32px;border-bottom:3px solid #FF8B5E;">
                <div style="font-size:11px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:#FF8B5E;margin-bottom:8px;">
                    BIM Verdi
                </div>
                <h1 class="nb-h1" style="margin:0;font-size:26px;line-height:1.25;font-weight:600;color:#1A1A1A;">
                    Nytt &amp; Nyttig fra BIM Verdi
                </h1>
                <p class="nb-text" style="margin:12px 0 0 0;font-size:16px;line-height:1.6;color:#3A3A3A;">
                    Her er det ferskeste fra nettverket — artikler, arrangementer, verktøy,
                    kunnskapskilder og nye deltakere.
                </p>
            </td>
        </tr>

        <?php if (empty($seksjoner)): ?>
        <tr>
            <td class="nb-muted" style="padding:40px 32px;text-align:center;<?php echo $muted; ?>font-size:16px;">
                Ingen nytt innhold å vise akkurat nå.
            </td>
        </tr>
        <?php else: ?>
            <?php foreach ($seksjoner as $seksjon): ?>
            <!-- Seksjon: <?php echo esc_html($seksjon['noekkel']); ?> -->
            <tr>
                <td style="padding:28px 32px 4px 32px;">
                    <h2 style="margin:0;font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#FF8B5E;">
                        <?php echo esc_html($seksjon['tittel']); ?>
                    </h2>
                </td>
            </tr>

            <?php foreach ($seksjon['items'] as $i => $item):
                $har_bilde = !empty($item['bilde']);
                $er_hero   = !empty($item['hero']) && $har_bilde;
                $alt       = esc_attr($item['tittel']);
                $alt_style = 'color:#FF8B5E;font-size:15px;font-weight:600;font-family:Inter,Arial,sans-serif;';
            ?>

            <?php if ($er_hero): /* === HERO: full-bredde bilde === */ ?>
            <tr>
                <td style="padding:12px 32px 4px 32px;">
                    <a href="<?php echo esc_url($item['lenke']); ?>" style="display:block;">
                        <img src="<?php echo esc_url($item['bilde']); ?>" width="536" alt="<?php echo $alt; ?>"
                             style="width:100%;max-width:536px;height:auto;display:block;border:0;border-radius:8px;<?php echo $alt_style; ?>">
                    </a>
                </td>
            </tr>
            <tr>
                <td style="padding:14px 32px 16px 32px;">
                    <a href="<?php echo esc_url($item['lenke']); ?>" class="nb-title" style="color:#1A1A1A;text-decoration:none;font-size:22px;font-weight:600;line-height:1.3;">
                        <?php echo esc_html($item['tittel']); ?>
                    </a>
                    <?php if (!empty($item['av'])): ?>
                    <div class="nb-muted" style="margin-top:6px;font-size:13px;<?php echo $muted; ?>"><?php echo esc_html($item['av']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($item['utdrag'])): ?>
                    <p class="nb-text" style="margin:10px 0 0 0;font-size:16px;line-height:1.6;color:#3A3A3A;"><?php echo esc_html($item['utdrag']); ?></p>
                    <?php endif; ?>
                    <!-- Bulletproof CTA (kun i hero) -->
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:16px;">
                        <tr>
                            <td bgcolor="#FF8B5E" style="background-color:#FF8B5E;border-radius:8px;">
                                <a href="<?php echo esc_url($item['lenke']); ?>" style="display:inline-block;padding:12px 26px;font-size:16px;font-weight:600;color:#ffffff;text-decoration:none;">
                                    Les hele saken&nbsp;→
                                </a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <?php elseif ($har_bilde): /* === MEDIA-RAD: venstre thumbnail === */ ?>
            <tr>
                <td style="padding:14px 32px;<?php echo ($i > 0) ? 'border-top:1px solid #EFE9DE;' : ''; ?>">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td class="nb-thumb-cell" width="120" valign="top" bgcolor="#F7F5EF" style="width:120px;background-color:#F7F5EF;border-radius:6px;">
                                <a href="<?php echo esc_url($item['lenke']); ?>" style="display:block;">
                                    <img src="<?php echo esc_url($item['bilde']); ?>" width="120" alt="<?php echo $alt; ?>"
                                         style="width:120px;max-width:120px;height:auto;display:block;border:0;border-radius:6px;<?php echo $alt_style; ?>">
                                </a>
                            </td>
                            <td class="nb-text-cell" valign="top" style="padding-left:16px;">
                                <a href="<?php echo esc_url($item['lenke']); ?>" class="nb-title" style="color:#1A1A1A;text-decoration:none;font-size:17px;font-weight:600;line-height:1.35;">
                                    <?php echo esc_html($item['tittel']); ?>
                                </a>
                                <?php if (!empty($item['meta'])): ?>
                                <div style="margin-top:4px;font-size:13px;font-weight:600;color:#FF8B5E;"><?php echo esc_html($item['meta']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item['av'])): ?>
                                <div class="nb-muted" style="margin-top:3px;font-size:13px;<?php echo $muted; ?>"><?php echo esc_html($item['av']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item['utdrag'])): ?>
                                <p class="nb-text" style="margin:6px 0 0 0;font-size:14px;line-height:1.55;color:#3A3A3A;"><?php echo esc_html($item['utdrag']); ?></p>
                                <?php endif; ?>
                                <div style="margin-top:8px;">
                                    <a href="<?php echo esc_url($item['lenke']); ?>" style="font-size:13px;font-weight:600;color:#FF8B5E;text-decoration:none;">Les mer&nbsp;→</a>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <?php else: /* === TEKST: ingen bilde === */ ?>
            <tr>
                <td style="padding:14px 32px;<?php echo ($i > 0) ? 'border-top:1px solid #EFE9DE;' : ''; ?>">
                    <a href="<?php echo esc_url($item['lenke']); ?>" class="nb-title" style="color:#1A1A1A;text-decoration:none;font-size:17px;font-weight:600;line-height:1.35;">
                        <?php echo esc_html($item['tittel']); ?>
                    </a>
                    <?php if (!empty($item['meta'])): ?>
                    <div style="margin-top:4px;font-size:13px;font-weight:600;color:#FF8B5E;"><?php echo esc_html($item['meta']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($item['av'])): ?>
                    <div class="nb-muted" style="margin-top:4px;font-size:13px;<?php echo $muted; ?>"><?php echo esc_html($item['av']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($item['utdrag'])): ?>
                    <p class="nb-text" style="margin:8px 0 0 0;font-size:14px;line-height:1.6;color:#3A3A3A;"><?php echo esc_html($item['utdrag']); ?></p>
                    <?php endif; ?>
                    <div style="margin-top:8px;">
                        <a href="<?php echo esc_url($item['lenke']); ?>" style="font-size:13px;font-weight:600;color:#FF8B5E;text-decoration:none;">Les mer&nbsp;→</a>
                    </div>
                </td>
            </tr>
            <?php endif; ?>

            <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Avsenderblokk -->
        <tr>
            <td style="padding:28px 32px;border-top:1px solid #D6D1C6;background-color:#F7F5EF;">
                <p class="nb-text" style="margin:0;font-size:16px;line-height:1.6;color:#1A1A1A;">
                    Nettverkshilsner fra<br>
                    <strong><?php echo esc_html($context['avsender_navn']); ?></strong>, <?php echo esc_html($context['avsender_tittel']); ?>
                </p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="padding:20px 32px 28px 32px;background-color:#F7F5EF;">
                <p class="nb-muted" style="margin:0;font-size:12px;line-height:1.6;<?php echo $muted; ?>">
                    Du mottar dette nyhetsbrevet som registrert bruker hos BIM Verdi.
                    Ønsker du å endre temaene du følger?
                    <a href="<?php echo esc_url($context['profil_url']); ?>" style="color:#5A5A5A;text-decoration:underline;">Oppdater din profil</a>.
                </p>
                <p class="nb-muted" style="margin:10px 0 0 0;font-size:12px;line-height:1.6;<?php echo $muted; ?>">
                    <a href="<?php echo esc_url($context['avmelding_url']); ?>" style="color:#5A5A5A;text-decoration:underline;">Meld deg av nyhetsbrevet</a>
                    &nbsp;·&nbsp;
                    <a href="<?php echo esc_url($context['nettsted_url']); ?>" style="color:#5A5A5A;text-decoration:underline;">bimverdi.no</a>
                </p>
            </td>
        </tr>

    </table>

</td>
</tr>
</table>

</body>
</html>
