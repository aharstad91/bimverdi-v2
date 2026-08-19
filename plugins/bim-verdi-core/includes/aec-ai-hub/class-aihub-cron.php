<?php
/**
 * AEC AI Hub — ukentlig synk (WP-Cron) + rapport-e-post.
 *
 * Bård godkjente 18.08.2026: «bare å sette opp ukentlig synk». Denne klassen er selve
 * planleggeren. Den er AV SOM STANDARD (`bv_aihub_cron_enabled` = 0) og gjør ingenting
 * før noen slår den på med `wp bimverdi aihub-cron enable`. Grunnen: første ekte kjøring
 * mot produksjon skal skje overvåket, ikke fordi et cron-treff kom først.
 *
 * Invarianter:
 *   - Synken publiserer ALDRI selv. Nye verktøy kommer inn som utkast (Decision 6);
 *     publisering er fortsatt en manuell batch-handling.
 *   - Rapport-e-posten går til ÉN adresse som er hardkodet allowlistet rett før wp_mail().
 *     Ingen medlems- eller kontaktadresse kan havne som mottaker via option/filter.
 *   - Mutexen i BV_AIHUB_Sync hindrer at cron og en manuell CLI-kjøring overlapper.
 *
 * @package BIMVerdiCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class BV_AIHUB_Cron {

    /** Cron-hook. */
    const HOOK = 'bv_aihub_weekly_sync';

    /** Option: 1 = ukentlig synk aktiv. Default 0 (av). */
    const OPTION_ENABLED = 'bv_aihub_cron_enabled';

    /** Option: siste kjøringstidspunkt + kortfattet resultat (adminrapport/feilsøk). */
    const OPTION_LAST_RUN = 'bv_aihub_cron_last_run';

    /**
     * Adresser som FÅR motta synk-rapporten. Hard allowlist — sjekkes rett før wp_mail().
     * Utvid bevisst; aldri fra option eller brukerdata.
     */
    const ALLOWED_RECIPIENTS = array(
        'andreas@aharstad.no',
        'andreas.harstad@initialforce.com',
    );

    /**
     * Hekt opp hooks. Kalles fra plugin-bootstrap.
     */
    public static function init() {
        add_action(self::HOOK, array(__CLASS__, 'run'));
        add_action('init', array(__CLASS__, 'sync_schedule'), 20);
    }

    /**
     * Er ukentlig synk slått på?
     *
     * @return bool
     */
    public static function is_enabled() {
        return (bool) get_option(self::OPTION_ENABLED, 0);
    }

    /**
     * Slå på/av. Planlegger eller avplanlegger umiddelbart.
     *
     * @param bool $enabled
     * @return array{enabled:bool,next_run:int}
     */
    public static function set_enabled($enabled) {
        update_option(self::OPTION_ENABLED, $enabled ? 1 : 0, false);
        self::sync_schedule();

        return array(
            'enabled'  => self::is_enabled(),
            'next_run' => (int) wp_next_scheduled(self::HOOK),
        );
    }

    /**
     * Hold cron-planen i tråd med option-en (idempotent, trygg å kalle på hver init).
     */
    public static function sync_schedule() {
        $next = wp_next_scheduled(self::HOOK);

        if (self::is_enabled()) {
            if (!$next) {
                // Første treff en time fram, deretter ukentlig ('weekly' finnes i WP ≥ 5.4).
                wp_schedule_event(time() + HOUR_IN_SECONDS, 'weekly', self::HOOK);
                error_log('[BV_AIHUB] Ukentlig synk planlagt (weekly).');
            }
            return;
        }

        if ($next) {
            wp_unschedule_event($next, self::HOOK);
            error_log('[BV_AIHUB] Ukentlig synk avplanlagt.');
        }
    }

    /**
     * Cron-handler: kjør synken og send rapport.
     *
     * @return array Stats-kontrakten fra BV_AIHUB_Sync::run().
     */
    public static function run() {
        if (!class_exists('BV_AIHUB_Sync')) {
            error_log('[BV_AIHUB] Cron: BV_AIHUB_Sync mangler — hopper over.');
            return array();
        }

        $stats = BV_AIHUB_Sync::run(false);

        update_option(self::OPTION_LAST_RUN, array(
            'at'       => current_time('mysql'),
            'ok'       => !empty($stats['ok']),
            'error'    => isset($stats['error']) ? $stats['error'] : null,
            'source'   => isset($stats['source']) ? $stats['source'] : '',
            'counts'   => isset($stats['counts']) ? $stats['counts'] : array(),
            'floor'    => !empty($stats['floor']),
        ), false);

        self::send_report($stats);

        return $stats;
    }

    /**
     * Send rapport-e-post om kjøringen.
     *
     * @param array $stats Stats fra BV_AIHUB_Sync::run().
     * @return bool Sendt?
     */
    public static function send_report(array $stats) {
        $to = defined('BV_AIHUB_REPORT_EMAIL') && BV_AIHUB_REPORT_EMAIL
            ? (string) BV_AIHUB_REPORT_EMAIL
            : self::ALLOWED_RECIPIENTS[0];

        // HARD ALLOWLIST rett før utsending: kun de eksplisitt godkjente adressene.
        if (!in_array(strtolower(trim($to)), array_map('strtolower', self::ALLOWED_RECIPIENTS), true)) {
            error_log('[BV_AIHUB] Rapport IKKE sendt: «' . $to . '» er ikke i allowlisten.');
            return false;
        }

        $c    = isset($stats['counts']) ? $stats['counts'] : array();
        $get  = function ($key) use ($c) {
            return isset($c[$key]) ? (int) $c[$key] : 0;
        };
        $host = wp_parse_url(home_url(), PHP_URL_HOST);

        if (empty($stats['ok'])) {
            $subject = sprintf('[BIM Verdi] AEC AI Hub-synk FEILET (%s)', $host);
        } else {
            $subject = sprintf(
                '[BIM Verdi] AEC AI Hub-synk: %d nye, %d oppdatert (%s)',
                $get('inserted'),
                $get('updated'),
                $host
            );
        }

        $lines = array(
            'AEC AI Hub — ukentlig synk',
            'Tidspunkt: ' . current_time('mysql'),
            'Nettsted:  ' . home_url(),
            'Kilde:     ' . (isset($stats['source']) ? $stats['source'] : 'ukjent'),
            '',
        );

        if (empty($stats['ok'])) {
            $lines[] = 'STATUS: FEILET — ingen poster ble endret.';
            $lines[] = 'Grunn:  ' . (isset($stats['error']) ? $stats['error'] : 'ukjent');
        } else {
            $lines[] = 'STATUS: OK';
            $lines[] = sprintf('Hentet fra kilden:     %d', $get('fetched_total'));
            $lines[] = sprintf('Unike etter dedup:     %d (droppet %d)', $get('unique_champions'), $get('dedup_dropped'));
            $lines[] = sprintf('Nye utkast:            %d', $get('inserted'));
            $lines[] = sprintf('Oppdatert:             %d', $get('updated'));
            $lines[] = sprintf('Hoppet over:           %d', $get('skipped'));
            $lines[] = sprintf('Umappet kategori:      %d  (ligger som utkast under «Ukategorisert»)', $get('unmapped'));
            $lines[] = sprintf('Forsvunnet fra kilden: %d', $get('orphaned'));

            if (!empty($stats['floor'])) {
                $lines[] = '';
                $lines[] = 'ADVARSEL: FLOOR-vakten slo inn (kilden ga uventet få rader).';
                $lines[] = 'Opprydding av forsvunne verktøy ble hoppet over. Sjekk kilden manuelt.';
            }
            if (!empty($stats['warnings']['status_divergence'])) {
                $lines[] = '';
                $lines[] = sprintf(
                    '%d verktøy har fått status endret manuelt og ble derfor ikke rørt.',
                    count($stats['warnings']['status_divergence'])
                );
            }
            if ($get('inserted') > 0) {
                $lines[] = '';
                $lines[] = 'Nye verktøy ligger som UTKAST og er usynlige på nettsiden til de godkjennes:';
                $lines[] = admin_url('edit.php?post_status=draft&post_type=' . (defined('BV_CPT_TOOL') ? BV_CPT_TOOL : 'verktoy'));
            }
        }

        $lines[] = '';
        $lines[] = 'Rapport: ' . admin_url('edit.php?post_type=' . (defined('BV_CPT_TOOL') ? BV_CPT_TOOL : 'verktoy') . '&page=bv-aihub-report');

        $sent = wp_mail($to, $subject, implode("\n", $lines));
        error_log(sprintf('[BV_AIHUB] Rapport til %s: %s', $to, $sent ? 'sendt' : 'FEILET'));

        return (bool) $sent;
    }

    /**
     * Status for CLI/adminvisning.
     *
     * @return array{enabled:bool,next_run:int,last_run:array}
     */
    public static function status() {
        return array(
            'enabled'  => self::is_enabled(),
            'next_run' => (int) wp_next_scheduled(self::HOOK),
            'last_run' => (array) get_option(self::OPTION_LAST_RUN, array()),
        );
    }
}
