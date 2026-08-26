<?php
/**
 * Statussiden — rene derivasjonsfunksjoner.
 *
 * Port av statusside-skillets lib/mvp-status.ts. Datamodellen og semantikken
 * er identisk på tvers av prosjekter; kun teknologien er byttet. Selve
 * innholdet ligger i data.php og oppdateres via vanlige commits — git-
 * historikken er endringsloggen.
 *
 * Semantikk som ALDRI skal avvike:
 * - Grønn journey = alle kriterier done OG teamet har godkjent eksplisitt.
 * - Rød = ingenting er ferdig. Gul = alt imellom, inkludert «teknisk ferdig,
 *   men ikke godkjent».
 * - Fremdrift = andel done. Partial teller 0. Baren skal kunne gå NED når vi
 *   oppdager nye hull — ærlig er viktigere enn pen.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Grønn = alt done + godkjent av teamet. Rød = alt missing. Ellers gul.
 *
 * @return string 'green'|'yellow'|'red'
 */
function bv_status_journey_farge(array $journey) {
    $statuser = array_column($journey['kriterier'], 'status');
    if (!$statuser) {
        return 'red';
    }

    $alle_done = !array_diff($statuser, ['done']);
    if ($alle_done && !empty($journey['godkjentAvTeam'])) {
        return 'green';
    }

    $ingen_fremdrift = !array_diff($statuser, ['missing']);
    if ($ingen_fremdrift) {
        return 'red';
    }

    return 'yellow';
}

/** Antall done-kriterier i én journey. */
function bv_status_antall_done(array $journey) {
    return count(array_filter($journey['kriterier'], function ($k) {
        return 'done' === $k['status'];
    }));
}

/** Andel done-kriterier i én journey, 0–100 (avrundet). */
function bv_status_journey_progress(array $journey) {
    $antall = count($journey['kriterier']);
    if (!$antall) {
        return 0;
    }
    return (int) round((bv_status_antall_done($journey) / $antall) * 100);
}

/** Andel done-kriterier på tvers av alle journeys, 0–100 (avrundet). */
function bv_status_total_progress(array $journeys) {
    $alle = [];
    foreach ($journeys as $j) {
        $alle = array_merge($alle, $j['kriterier']);
    }
    if (!$alle) {
        return 0;
    }
    $done = count(array_filter($alle, function ($k) {
        return 'done' === $k['status'];
    }));
    return (int) round(($done / count($alle)) * 100);
}

/** Antall journeys per farge. */
function bv_status_farge_fordeling(array $journeys) {
    $fordeling = ['green' => 0, 'yellow' => 0, 'red' => 0];
    foreach ($journeys as $j) {
        $fordeling[bv_status_journey_farge($j)]++;
    }
    return $fordeling;
}

/** Nyeste verifisert-dato på tvers av alle kriterier, eller null. */
function bv_status_sist_verifisert(array $journeys) {
    $datoer = [];
    foreach ($journeys as $j) {
        foreach ($j['kriterier'] as $k) {
            if (!empty($k['verifisert'])) {
                $datoer[] = $k['verifisert'];
            }
        }
    }
    if (!$datoer) {
        return null;
    }
    sort($datoer);
    return end($datoer);
}

/** ISO-dato → «26. august 2026». Ugyldig dato returneres uendret. */
function bv_status_format_dato($iso) {
    if (!$iso) {
        return 'ennå ikke verifisert';
    }
    $ts = strtotime($iso);
    if (!$ts) {
        return $iso;
    }
    $maneder = [
        1 => 'januar', 'februar', 'mars', 'april', 'mai', 'juni',
        'juli', 'august', 'september', 'oktober', 'november', 'desember',
    ];
    return sprintf('%d. %s %d', (int) date('j', $ts), $maneder[(int) date('n', $ts)], (int) date('Y', $ts));
}
