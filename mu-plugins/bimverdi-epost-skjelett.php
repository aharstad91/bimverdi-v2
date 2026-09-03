<?php
/**
 * Plugin Name: BIM Verdi — felles e-postskjelett
 * Description: Ett sted for utsendingsmalen. Alle e-poster fra siden bygges her,
 *              slik at ingen ny e-post kan bli et nakent <div>-fragment igjen.
 * Version: 1.0.0
 *
 * ══════════════════════════════════════════════════════════════════════════
 * HVORFOR DENNE FILEN FINNES
 * ══════════════════════════════════════════════════════════════════════════
 *
 * CTA-knappen har sett ut som markert tekst i Spark i tre runder nå
 * (WORKLOG 12.08.2026, og igjen 03.09.2026 på arrangement-påminnelsen).
 * Rotårsaken ble funnet empirisk 12.08: HTML-en er intakt hele veien —
 * fanget i payloaden mot Resend og hentet tilbake igjen fra Resend-API-et —
 * så det er ikke et sende- eller escaping-problem. Det er rendering:
 *
 *   En e-post uten <!DOCTYPE>/<html>/<body> blir behandlet som «personlig
 *   post» av flere klienter (bl.a. Spark). Klienten legger på sin egen
 *   typografi og stripper boks- og knappestyling. Et <a> med padding og
 *   bakgrunn kollapser da til farget tekst — presis det skjermbildet viser.
 *
 * Grunnen til at det skjedde HVER gang, er at hver e-post var håndskrevet.
 * 03.09.2026 lå den samme kopierte fragment-knappen i 12 varianter fordelt
 * på 7 filer. Ingen felles mal betyr at neste e-post arver feilen på nytt.
 *
 * REGEL FRA OG MED NÅ: bygg aldri e-post-HTML direkte i en mu-plugin. Kall
 * bimverdi_epost_dokument() med innholdet, og la skjelettet ligge her.
 *
 * Skjelettet speiler husets medlems-e-poster (get_verification_email_html i
 * bimverdi-email-verification.php) og varselmalen som ble bekreftet OK i
 * Spark 12.08 (bimverdi_diskusjon_varsel_html i bimverdi-diskusjon-varsler.php).
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Én CTA-knapp, tabell-innpakket.
 *
 * Tabellen er ikke pynt: Outlook (Word-motoren) ignorerer padding på <a>,
 * og en klient som stripper display:inline-block ville ellers etterlatt
 * knappen som en lenke midt i brødteksten. Cellen gir den egen linje og
 * bakgrunnsflaten sin uavhengig av hva klienten gjør med <a>-en.
 *
 * @param string $url      Lenkemål.
 * @param string $tekst    Knappetekst (escapes her).
 * @param string $variant  'primary' (oransje) eller 'mork' (sort).
 * @param string $justering 'center' eller 'left'.
 * @return string HTML.
 */
function bimverdi_epost_knapp($url, $tekst, $variant = 'primary', $justering = 'center') {
    if ($url === '' || $tekst === '') {
        return '';
    }

    $bakgrunn = ($variant === 'mork') ? '#1A1A1A' : '#FF8B5E';

    return sprintf(
        '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%%" style="margin: 32px 0 8px 0;">'
        . '<tr><td align="%s">'
        . '<a href="%s" style="display: inline-block; background-color: %s; color: #ffffff; text-decoration: none;'
        . ' padding: 14px 32px; border-radius: 8px; font-size: 16px; font-weight: 500;">%s</a>'
        . '</td></tr></table>',
        esc_attr($justering),
        esc_url($url),
        esc_attr($bakgrunn),
        esc_html($tekst)
    );
}

/**
 * Gult testkopi-banner. Brukes av utsendinger som står bak en sikkerhetsgate,
 * slik at en testkopi aldri kan forveksles med den ekte e-posten.
 *
 * @param string $tekst HTML (kaller escaper selv der det trengs).
 * @return string HTML.
 */
function bimverdi_epost_testbanner($tekst) {
    if ($tekst === '') {
        return '';
    }

    return '<div style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px;padding:12px 16px;'
        . 'margin:0 0 20px;font-size:13px;color:#92400E;">' . $tekst . '</div>';
}

/**
 * Pakk innhold i husets e-postskjelett — fullt HTML-dokument.
 *
 * @param array $args {
 *     @type string $tittel      <title>. Vises ikke i innholdet.
 *     @type string $banner      HTML over brødteksten (typisk testbanner).
 *     @type string $innhold     HTML-brødtekst. Avsender escaper selv.
 *     @type string $cta_url     Lenkemål for hovedknappen. Tom = ingen knapp.
 *     @type string $cta_tekst   Knappetekst.
 *     @type string $cta_variant 'primary' | 'mork'.
 *     @type string $etter_cta   HTML under knappen (sekundærlenker o.l.).
 *     @type string $footer      HTML i footeren (småtekst, GDPR-grunn, avmelding).
 *     @type bool   $vis_fallback Vis «Fungerer ikke knappen?» med rå lenke.
 * }
 * @return string Komplett HTML-dokument.
 */
function bimverdi_epost_dokument($args = array()) {
    $a = wp_parse_args($args, array(
        'tittel'       => 'BIM Verdi',
        'banner'       => '',
        'innhold'      => '',
        'cta_url'      => '',
        'cta_tekst'    => '',
        'cta_variant'  => 'primary',
        'etter_cta'    => '',
        'footer'       => '',
        'vis_fallback' => true,
    ));

    $knapp = bimverdi_epost_knapp($a['cta_url'], $a['cta_tekst'], $a['cta_variant']);

    // Fallback-lenken er for klienter som stripper knappen helt. Uten en knapp
    // er den bare støy, så den følger knappen.
    $fallback = '';
    if ($knapp !== '' && $a['vis_fallback']) {
        $fallback = sprintf(
            '<p style="margin: 0 0 16px 0; color: #9B9B9B; font-size: 12px;">Fungerer ikke knappen? '
            . '<a href="%s" style="color: #6B6B6B;">Klikk her</a></p>',
            esc_url($a['cta_url'])
        );
    }

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($a['tittel']); ?></title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #F5F3EE; color: #1A1A1A;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F5F3EE;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 520px; margin: 0 auto;">

                    <!-- Logo -->
                    <tr>
                        <td style="text-align: center; padding-bottom: 32px;">
                            <span style="font-size: 20px; font-weight: 700; color: #1A1A1A;">BIM Verdi</span>
                        </td>
                    </tr>

                    <!-- Hovedkort -->
                    <tr>
                        <td>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
                                <tr>
                                    <td style="padding: 40px;">
                                        <?php echo $a['banner']; ?>
                                        <?php echo $a['innhold']; ?>
                                        <?php echo $knapp; ?>
                                        <?php echo $a['etter_cta']; ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <?php if ($fallback !== '' || $a['footer'] !== '') : ?>
                    <tr>
                        <td style="padding: 24px 0; text-align: center;">
                            <?php echo $fallback; ?>
                            <?php if ($a['footer'] !== '') : ?>
                            <p style="margin: 0; color: #9B9B9B; font-size: 11px; line-height: 1.6;">
                                <?php echo $a['footer']; ?>
                            </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
    <?php
    return trim(ob_get_clean());
}

/**
 * Avsnitt i brødteksten, med husets typografi.
 *
 * Finnes for å slippe å gjenta style-attributtet i hver kallende fil — det er
 * gjentakelsen som over tid gir avvikende e-poster.
 *
 * @param string $html    Innhold (avsender escaper selv).
 * @param array  $stiler  Overstyringer: 'storrelse', 'farge', 'margin'.
 * @return string HTML.
 */
function bimverdi_epost_avsnitt($html, $stiler = array()) {
    $s = wp_parse_args($stiler, array(
        'storrelse' => '16px',
        'farge'     => '#1A1A1A',
        'margin'    => '0 0 24px 0',
    ));

    return sprintf(
        '<p style="margin: %s; color: %s; font-size: %s; line-height: 1.6;">%s</p>',
        esc_attr($s['margin']),
        esc_attr($s['farge']),
        esc_attr($s['storrelse']),
        $html
    );
}
