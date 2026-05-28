<?php
/**
 * BIM Verdi — Pris-helper for årspriser (krav 24-v4, oppdatert 2026-05-28)
 *
 * Per møte 28. mai 2026: kvartalsvis avregnings-automatikk er fjernet. Vi
 * fakturerer årspris uten dynamisk pro-rata-beregning. Eventuell rabatt for
 * sen-innmelding kommuniseres som statisk disclaimer-tekst på onboarding/
 * oppgraderings-siden, ikke som dynamisk beløp.
 *
 * Sannhetskilden for priser er https://bimverdi.no/betingelser/ (parsing
 * parkert per B-031). Bård kan også redigere visningsprisene i ACF Options
 * (Innstillinger → Deltakeravgift). Funksjonene under returnerer hardkodede
 * årspriser som matcher betingelse-siden.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Returnerer årspriser (NOK eks mva) per nivå.
 *
 * @return array<string,int> ['deltaker' => int, 'prosjektdeltaker' => int, 'partner' => int]
 */
function bimverdi_get_betingelser_prices() {
    return apply_filters('bimverdi_betingelser_prices', [
        'deltaker'         => 8000,
        'prosjektdeltaker' => 24000,
        'partner'          => 48000,
    ]);
}

/**
 * Returnerer faktura-data for en oppgradering (kun årspris, ingen kvartal-logikk).
 *
 * @param string $nivaa 'deltaker' | 'prosjektdeltaker' | 'partner'
 * @return array|null {
 *     @type string $nivaa
 *     @type int    $aarspris
 *     @type int    $totalbeloep   Lik aarspris — beholdt som felt for callers
 *                                  som fortsatt refererer "totalbeloep".
 *     @type int    $aar           Inneværende år (Europe/Oslo).
 * }
 * Returnerer null hvis $nivaa er ugyldig.
 */
function bimverdi_calculate_oppgrader_invoice($nivaa, DateTime $today = null) {
    $priser = bimverdi_get_betingelser_prices();
    if (!isset($priser[$nivaa])) {
        return null;
    }

    if ($today === null) {
        $today = new DateTime('now', new DateTimeZone('Europe/Oslo'));
    } else {
        $today = clone $today;
        $today->setTimezone(new DateTimeZone('Europe/Oslo'));
    }

    $aarspris = (int) $priser[$nivaa];

    return [
        'nivaa'       => $nivaa,
        'aarspris'    => $aarspris,
        'totalbeloep' => $aarspris,
        'aar'         => (int) $today->format('Y'),
    ];
}
