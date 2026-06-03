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
 * UI-contract-farger: orange #FF8B5E, beige #F7F5EF, tekst #1A1A1A / #5A5A5A,
 * divider #D6D1C6.
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

// Inline stil-snutter (gjenbrukt under).
$s_link    = 'color:#1A1A1A;text-decoration:none;';
$s_muted   = 'color:#5A5A5A;';
?><!DOCTYPE html>
<html lang="nb">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light">
<title>Nytt &amp; Nyttig fra BIM Verdi</title>
</head>
<body style="margin:0;padding:0;background-color:#F7F5EF;-webkit-font-smoothing:antialiased;">

<!-- Preheader (skjult i innboksen) -->
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">
    Det ferskeste fra nettverket — artikler, arrangementer, verktøy, kunnskapskilder og deltakere.
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F7F5EF;">
<tr>
<td align="center" style="padding:24px 12px;">

    <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background-color:#FFFFFF;border:1px solid #D6D1C6;border-radius:12px;overflow:hidden;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;">

        <!-- Header -->
        <tr>
            <td style="padding:32px 32px 24px 32px;border-bottom:3px solid #FF8B5E;">
                <div style="font-size:12px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#FF8B5E;margin-bottom:8px;">
                    BIM Verdi
                </div>
                <h1 style="margin:0;font-size:26px;line-height:1.25;font-weight:600;color:#1A1A1A;">
                    Nytt &amp; Nyttig fra BIM Verdi
                </h1>
                <p style="margin:12px 0 0 0;font-size:15px;line-height:1.6;<?php echo $s_muted; ?>">
                    Her er det ferskeste fra nettverket — artikler, arrangementer, verktøy,
                    kunnskapskilder og nye deltakere.
                </p>
            </td>
        </tr>

        <?php if (empty($seksjoner)): ?>
        <tr>
            <td style="padding:40px 32px;text-align:center;<?php echo $s_muted; ?>font-size:15px;">
                Ingen nytt innhold å vise akkurat nå.
            </td>
        </tr>
        <?php else: ?>
            <?php foreach ($seksjoner as $seksjon): ?>
            <!-- Seksjon: <?php echo esc_html($seksjon['noekkel']); ?> -->
            <tr>
                <td style="padding:28px 32px 8px 32px;">
                    <h2 style="margin:0;font-size:13px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:#FF8B5E;">
                        <?php echo esc_html($seksjon['tittel']); ?>
                    </h2>
                </td>
            </tr>
            <?php foreach ($seksjon['items'] as $i => $item): ?>
            <tr>
                <td style="padding:16px 32px;<?php echo ($i > 0) ? 'border-top:1px solid #EFE9DE;' : ''; ?>">
                    <a href="<?php echo esc_url($item['lenke']); ?>" style="<?php echo $s_link; ?>font-size:17px;font-weight:600;line-height:1.35;">
                        <?php echo esc_html($item['tittel']); ?>
                    </a>

                    <?php if (!empty($item['meta'])): ?>
                    <div style="margin-top:4px;font-size:13px;font-weight:600;color:#FF8B5E;">
                        <?php echo esc_html($item['meta']); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($item['av'])): ?>
                    <div style="margin-top:4px;font-size:13px;<?php echo $s_muted; ?>">
                        <?php echo esc_html($item['av']); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($item['utdrag'])): ?>
                    <p style="margin:8px 0 0 0;font-size:14px;line-height:1.6;color:#3A3A3A;">
                        <?php echo esc_html($item['utdrag']); ?>
                    </p>
                    <?php endif; ?>

                    <div style="margin-top:10px;">
                        <a href="<?php echo esc_url($item['lenke']); ?>" style="font-size:13px;font-weight:600;color:#FF8B5E;text-decoration:none;">
                            Les mer&nbsp;→
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Avsenderblokk -->
        <tr>
            <td style="padding:28px 32px;border-top:1px solid #D6D1C6;background-color:#F7F5EF;">
                <p style="margin:0;font-size:15px;line-height:1.6;color:#1A1A1A;">
                    Nettverkshilsner fra<br>
                    <strong><?php echo esc_html($context['avsender_navn']); ?></strong>, <?php echo esc_html($context['avsender_tittel']); ?>
                </p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="padding:20px 32px 28px 32px;background-color:#F7F5EF;">
                <p style="margin:0;font-size:12px;line-height:1.6;<?php echo $s_muted; ?>">
                    Du mottar dette nyhetsbrevet som registrert bruker hos BIM Verdi.
                    Ønsker du å endre temaene du følger?
                    <a href="<?php echo esc_url($context['profil_url']); ?>" style="color:#5A5A5A;text-decoration:underline;">Oppdater din profil</a>.
                </p>
                <p style="margin:10px 0 0 0;font-size:12px;line-height:1.6;<?php echo $s_muted; ?>">
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
