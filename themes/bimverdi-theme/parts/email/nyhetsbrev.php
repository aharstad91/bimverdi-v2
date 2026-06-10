<?php
/**
 * Nyhetsbrev-mal «Nytt & Nyttig fra BIM Verdi» (e-post-HTML)
 *
 * Tabellbasert, e-postklient-vennlig HTML med inline CSS. Rendres via
 * bimverdi_render_nyhetsbrev() i mu-plugins/bimverdi-nyhetsbrev-content.php.
 *
 * Forventer i scope:
 *   $data    — fra bimverdi_nyhetsbrev_collect()  (generert, totaler, seksjoner[])
 *   $context — lenker + avsender (profil_url, avmelding_url, avsender_navn, ...)
 *
 * Designvalg v2 (jf. Bård-synk 09.06 + Spark-referanse 10.06):
 *   - KORT-basert: hvert innholdsområde er et hvitt avrundet kort på beige
 *     canvas, med luft mellom — scanning fremfor lesing.
 *   - TEKST-MINIMAL: kun hero-artikkelen har utdrag. Alle listerader er
 *     thumb + badge + tittel + byline. (Bård: «alt for tekst-tungt».)
 *   - Topp-header med ressurs-oversikt («de siste av X») + gulrot-linje
 *     for publiseringsstimulering.
 *   - NY/OPPDATERT-piller per item (nytt-vs-oppdatert-logikk i motoren).
 *   - «Se alle N [enhet] →» per seksjonskort.
 *   - Initial-bokstav-placeholder der bilde mangler (aldri «nakne» rader).
 *   - 60/30/10: orange #FF8B5E kun til eyebrow/CTA/NY-pille.
 *
 * @package BIMVerdi
 * @version 2.0.0
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

$totaler = isset($data['totaler']) ? $data['totaler'] : ['sum' => 0, 'typer' => []];

// Preheader: tease toppsaken (første item i første seksjon), ellers generisk.
$preheader = 'Det ferskeste fra nettverket — artikler, arrangementer, verktøy og mer.';
if (!empty($seksjoner[0]['items'][0]['tittel'])) {
    $preheader = 'Siste: ' . $seksjoner[0]['items'][0]['tittel'];
}

$muted = 'color:#8A8578;';

/**
 * NY/OPPDATERT-pille. Returnerer ferdig escaped HTML eller ''.
 */
$bv_nb_badge = function ($status) {
    if ($status === 'ny') {
        return '<span style="display:inline-block;padding:2px 8px;border-radius:10px;background-color:#FF8B5E;color:#ffffff;font-size:10px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;vertical-align:middle;">Ny</span>';
    }
    if ($status === 'oppdatert') {
        return '<span style="display:inline-block;padding:2px 8px;border-radius:10px;background-color:#EFE9DE;color:#6B6557;font-size:10px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;vertical-align:middle;">Oppdatert</span>';
    }
    return '';
};

/**
 * Thumb-celle: bilde hvis det finnes, ellers initial-bokstav-placeholder.
 * 64×64, avrundet. Returnerer ferdig escaped HTML for innholdet i cellen.
 */
$bv_nb_thumb = function ($item) {
    if (!empty($item['bilde'])) {
        return '<a href="' . esc_url($item['lenke']) . '" style="display:block;">'
             . '<img src="' . esc_url($item['bilde']) . '" width="64" height="64" alt=""'
             . ' style="width:64px;height:64px;display:block;border:0;border-radius:8px;background-color:#F7F5EF;object-fit:cover;">'
             . '</a>';
    }
    $initial = function_exists('mb_substr') ? mb_substr($item['tittel'], 0, 1) : substr($item['tittel'], 0, 1);
    $initial = function_exists('mb_strtoupper') ? mb_strtoupper($initial) : strtoupper($initial);
    return '<a href="' . esc_url($item['lenke']) . '" style="display:block;width:64px;height:64px;border-radius:8px;background-color:#EFE9DE;text-align:center;text-decoration:none;">'
         . '<span style="display:inline-block;font-family:Inter,Arial,sans-serif;font-size:24px;font-weight:600;color:#B3AB9B;line-height:64px;">' . esc_html($initial) . '</span>'
         . '</a>';
};

/**
 * Seksjonskort-header: eyebrow venstre + «Se alle N →» høyre.
 */
$bv_nb_kort_header = function ($seksjon) {
    $vist = count($seksjon['items']);
    $se_alle = '';
    if (!empty($seksjon['arkiv_url']) && !empty($seksjon['total']) && $seksjon['total'] > $vist) {
        $se_alle = '<a href="' . esc_url($seksjon['arkiv_url']) . '" style="font-size:12px;font-weight:600;color:#FF8B5E;text-decoration:none;white-space:nowrap;">'
                 . 'Se alle ' . esc_html($seksjon['total']) . '&nbsp;→</a>';
    }
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
         . '<td class="nb-title" style="font-size:16px;font-weight:700;color:#1A1A1A;font-family:Inter,Arial,sans-serif;">'
         . esc_html($seksjon['tittel']) . '</td>'
         . '<td align="right">' . $se_alle . '</td>'
         . '</tr></table>';
};
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
    @media only screen and (max-width:480px) {
        .nb-pad { padding-left:16px !important; padding-right:16px !important; }
        .nb-h1 { font-size:26px !important; }
    }
    /* Dark mode: behold lesbarhet (beige er en trygg midtone) */
    @media (prefers-color-scheme: dark) {
        .nb-canvas { background-color:#1f1d1a !important; }
        .nb-card { background-color:#26241f !important; }
        .nb-title, .nb-h1 { color:#f5f2ec !important; }
        .nb-text { color:#d8d2c7 !important; }
        .nb-muted { color:#a39c8d !important; }
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
<td align="center" class="nb-pad" style="padding:32px 16px 40px 16px;">

    <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;">

        <!-- ============ HEADER (sentrert, på canvas) ============ -->
        <tr>
            <td align="center" style="padding:16px 24px 8px 24px;">
                <div class="nb-title" style="font-size:16px;font-weight:700;color:#1A1A1A;margin-bottom:14px;">
                    BIM Verdi
                </div>
                <h1 class="nb-h1" style="margin:0;font-size:30px;line-height:1.2;font-weight:600;color:#1A1A1A;">
                    Nytt &amp; Nyttig
                </h1>
                <p class="nb-text" style="margin:12px 0 0 0;font-size:15px;line-height:1.6;color:#5A5A5A;">
                    Det ferskeste fra nettverket — utvalgt fra
                    <strong style="color:#1A1A1A;"><?php echo esc_html($totaler['sum']); ?> ressurser</strong>.
                </p>
                <?php if (!empty($totaler['typer'])): ?>
                <p class="nb-muted" style="margin:8px 0 0 0;font-size:13px;line-height:1.6;<?php echo $muted; ?>">
                    <?php
                    $deler = [];
                    foreach ($totaler['typer'] as $t) {
                        $deler[] = '<strong style="color:#3A3A3A;font-weight:600;">' . esc_html($t['antall']) . '</strong>&nbsp;' . esc_html($t['label']);
                    }
                    echo implode(' &nbsp;·&nbsp; ', $deler);
                    ?>
                </p>
                <?php endif; ?>
                <p class="nb-muted" style="margin:14px 0 0 0;font-size:13px;line-height:1.6;<?php echo $muted; ?>">
                    Som gratisbruker kan du logge deg inn og registrere kunnskapskilder og delta
                    på åpne arrangement. Som aktiv Deltaker kan du skrive artikler, registrere
                    verktøy m.m.
                    <a href="<?php echo esc_url(home_url('/logg-inn/')); ?>" style="color:#FF8B5E;font-weight:600;text-decoration:none;white-space:nowrap;">Logg inn her</a>
                    og bli med å bygge &amp; bruke økosystemet.
                </p>
            </td>
        </tr>
        <tr><td style="height:24px;line-height:24px;font-size:0;">&nbsp;</td></tr>

        <?php if (empty($seksjoner)): ?>
        <tr>
            <td class="nb-card nb-muted" style="padding:40px 24px;text-align:center;background-color:#FFFFFF;border-radius:14px;<?php echo $muted; ?>font-size:15px;">
                Ingen nytt innhold å vise akkurat nå.
            </td>
        </tr>
        <?php else: ?>

            <?php foreach ($seksjoner as $seksjon): ?>
            <!-- ============ KORT: <?php echo esc_html($seksjon['noekkel']); ?> ============ -->
            <tr>
                <td class="nb-card" style="background-color:#FFFFFF;border-radius:14px;padding:22px 24px;">

                    <?php echo $bv_nb_kort_header($seksjon); ?>

                    <?php foreach ($seksjon['items'] as $i => $item):
                        $badge    = $bv_nb_badge($item['status'] ?? '');
                        $er_hero  = !empty($item['hero']) && !empty($item['bilde']);
                        $cta_tekst = ($seksjon['noekkel'] === 'arrangement') ? 'Se arrangementet' : 'Les hele saken';
                    ?>

                    <?php if ($er_hero): /* === HERO: full-bredde bilde + utdrag + CTA === */ ?>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:16px;">
                        <tr>
                            <td>
                                <a href="<?php echo esc_url($item['lenke']); ?>" style="display:block;">
                                    <img src="<?php echo esc_url($item['bilde']); ?>" width="552" alt="<?php echo esc_attr($item['tittel']); ?>"
                                         style="width:100%;max-width:552px;height:auto;display:block;border:0;border-radius:10px;background-color:#F7F5EF;color:#FF8B5E;font-size:14px;font-weight:600;">
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-top:16px;">
                                <?php if ($badge): ?><div style="margin-bottom:8px;"><?php echo $badge; ?></div><?php endif; ?>
                                <?php if (!empty($item['meta'])): ?>
                                <div style="margin-bottom:6px;font-size:14px;font-weight:700;color:#FF8B5E;"><?php echo esc_html($item['meta']); ?></div>
                                <?php endif; ?>
                                <a href="<?php echo esc_url($item['lenke']); ?>" class="nb-title" style="color:#1A1A1A;text-decoration:none;font-size:21px;font-weight:600;line-height:1.3;">
                                    <?php echo esc_html($item['tittel']); ?>
                                </a>
                                <?php if (!empty($item['av'])): ?>
                                <div class="nb-muted" style="margin-top:6px;font-size:13px;<?php echo $muted; ?>"><?php echo esc_html($item['av']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item['utdrag'])): ?>
                                <p class="nb-text" style="margin:10px 0 0 0;font-size:15px;line-height:1.6;color:#3A3A3A;"><?php echo esc_html($item['utdrag']); ?></p>
                                <?php endif; ?>
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:14px;">
                                    <tr>
                                        <td bgcolor="#FF8B5E" style="background-color:#FF8B5E;border-radius:8px;">
                                            <a href="<?php echo esc_url($item['lenke']); ?>" style="display:inline-block;padding:11px 24px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">
                                                <?php echo esc_html($cta_tekst); ?>&nbsp;→
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <?php elseif ($seksjon['noekkel'] === 'arrangement'): /* === ARRANGEMENT: dato-fokusert === */ ?>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:16px;">
                        <tr>
                            <td width="64" valign="top"><?php echo $bv_nb_thumb($item); ?></td>
                            <td valign="top" style="padding-left:14px;">
                                <?php if (!empty($item['meta'])): ?>
                                <div style="font-size:13px;font-weight:700;color:#FF8B5E;"><?php echo esc_html($item['meta']); ?></div>
                                <?php endif; ?>
                                <a href="<?php echo esc_url($item['lenke']); ?>" class="nb-title" style="display:inline-block;margin-top:4px;color:#1A1A1A;text-decoration:none;font-size:16px;font-weight:600;line-height:1.4;">
                                    <?php echo esc_html($item['tittel']); ?>
                                </a>
                                <?php if (!empty($item['av'])): ?>
                                <div class="nb-muted" style="margin-top:3px;font-size:13px;<?php echo $muted; ?>"><?php echo esc_html($item['av']); ?></div>
                                <?php endif; ?>
                                <div style="margin-top:10px;">
                                    <a href="<?php echo esc_url($item['lenke']); ?>" style="font-size:13px;font-weight:600;color:#FF8B5E;text-decoration:none;">Se arrangementet&nbsp;→</a>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <?php else: /* === KOMPAKT RAD: thumb + badge + tittel + byline === */ ?>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:16px;<?php echo ($i > 0 || !empty($seksjon['items'][0]['hero'])) ? 'border-top:1px solid #F3EFE7;padding-top:16px;' : ''; ?>">
                        <tr>
                            <td width="64" valign="top" style="padding-top:<?php echo ($i > 0 || !empty($seksjon['items'][0]['hero'])) ? '16px' : '0'; ?>;"><?php echo $bv_nb_thumb($item); ?></td>
                            <td valign="middle" style="padding-left:14px;padding-top:<?php echo ($i > 0 || !empty($seksjon['items'][0]['hero'])) ? '16px' : '0'; ?>;">
                                <?php if ($badge): ?><div style="margin-bottom:5px;"><?php echo $badge; ?></div><?php endif; ?>
                                <a href="<?php echo esc_url($item['lenke']); ?>" class="nb-title" style="color:#1A1A1A;text-decoration:none;font-size:16px;font-weight:600;line-height:1.4;">
                                    <?php echo esc_html($item['tittel']); ?>
                                </a>
                                <?php if (!empty($item['av'])): ?>
                                <div class="nb-muted" style="margin-top:3px;font-size:13px;<?php echo $muted; ?>"><?php echo esc_html($item['av']); ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    <?php endif; ?>

                    <?php endforeach; ?>

                </td>
            </tr>
            <tr><td style="height:16px;line-height:16px;font-size:0;">&nbsp;</td></tr>
            <?php endforeach; ?>

        <?php endif; ?>

        <!-- ============ AVSENDER + FOOTER (på canvas) ============ -->
        <tr>
            <td align="center" style="padding:20px 24px 0 24px;">
                <p class="nb-text" style="margin:0;font-size:15px;line-height:1.6;color:#3A3A3A;">
                    Nettverkshilsner fra<br>
                    <strong style="color:#1A1A1A;"><?php echo esc_html($context['avsender_navn']); ?></strong>, <?php echo esc_html($context['avsender_tittel']); ?>
                </p>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding:24px 24px 0 24px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr><td style="border-top:1px solid #E3DDD0;height:1px;line-height:1px;font-size:0;">&nbsp;</td></tr>
                </table>
                <p class="nb-muted" style="margin:18px 0 0 0;font-size:12px;line-height:1.7;<?php echo $muted; ?>">
                    Du mottar dette nyhetsbrevet som registrert bruker hos BIM Verdi.<br>
                    <a href="<?php echo esc_url($context['profil_url']); ?>" style="color:#8A8578;text-decoration:underline;">Oppdater din profil</a>
                    &nbsp;·&nbsp;
                    <a href="<?php echo esc_url($context['avmelding_url']); ?>" style="color:#8A8578;text-decoration:underline;">Meld deg av</a>
                    &nbsp;·&nbsp;
                    <a href="<?php echo esc_url($context['nettsted_url']); ?>" style="color:#8A8578;text-decoration:underline;">bimverdi.no</a>
                </p>
            </td>
        </tr>

    </table>

</td>
</tr>
</table>

</body>
</html>
