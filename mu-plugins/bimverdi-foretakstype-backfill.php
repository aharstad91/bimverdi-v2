<?php
/**
 * BIM Verdi — Backfill av bv_foretakstype + bv_nivaa fra legacy bv_rolle
 *
 * Krav 24-v4: Nye felter må ha riktig verdi på alle eksisterende foretak før
 * Unit 7 (konverterings-handler) begynner å lese dem.
 *
 * Mapping (basert på dagens bv_rolle-verdier i bimverdi-foretak-registration.php):
 *   bv_rolle = 'Ikke deltaker' (eller tom) → bv_foretakstype = 'gratisforetak', bv_nivaa = ''
 *   bv_rolle = 'Deltaker'         → bv_foretakstype = 'foretak', bv_nivaa = 'deltaker'
 *   bv_rolle = 'Prosjektdeltaker' → bv_foretakstype = 'foretak', bv_nivaa = 'prosjektdeltaker'
 *   bv_rolle = 'Partner'          → bv_foretakstype = 'foretak', bv_nivaa = 'partner'
 *
 * Kjøres via WP-admin (admin-only) eller WP-CLI:
 *   wp eval-file wp-content/mu-plugins/bimverdi-foretakstype-backfill.php
 *
 * Idempotent: kjøres flere ganger uten effekt etter første gang.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Kjør backfill av bv_foretakstype + bv_nivaa.
 *
 * @param bool $dry_run Hvis true: rapporter hva som ville blitt endret, men ikke endre.
 * @return array Summary av kjøringen.
 */
function bimverdi_run_foretakstype_backfill($dry_run = false) {
    if (!function_exists('get_field') || !function_exists('update_field')) {
        return ['error' => 'ACF Pro må være aktivert.'];
    }

    $foretak_ids = get_posts([
        'post_type'      => 'foretak',
        'post_status'    => ['publish', 'pending', 'draft', 'private'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    $summary = [
        'dry_run'    => (bool) $dry_run,
        'total'      => count($foretak_ids),
        'skipped'    => 0,
        'updated'    => 0,
        'unknown'    => 0,
        'by_type'    => ['gratisforetak' => 0, 'foretak' => 0],
        'by_nivaa'   => ['' => 0, 'deltaker' => 0, 'prosjektdeltaker' => 0, 'partner' => 0],
        'log'        => [],
    ];

    foreach ($foretak_ids as $foretak_id) {
        $foretak_id = (int) $foretak_id;
        $existing_type = (string) get_field('bv_foretakstype', $foretak_id);
        $bv_rolle = (string) get_field('bv_rolle', $foretak_id);

        switch ($bv_rolle) {
            case 'Deltaker':
                $new_type = 'foretak';
                $new_nivaa = 'deltaker';
                break;
            case 'Prosjektdeltaker':
                $new_type = 'foretak';
                $new_nivaa = 'prosjektdeltaker';
                break;
            case 'Partner':
                $new_type = 'foretak';
                $new_nivaa = 'partner';
                break;
            case 'Ikke deltaker':
            case '':
                $new_type = 'gratisforetak';
                $new_nivaa = '';
                break;
            default:
                $summary['unknown']++;
                $summary['log'][] = sprintf('Foretak %d: ukjent bv_rolle="%s" — hopper over.', $foretak_id, $bv_rolle);
                error_log(sprintf('[bimverdi-backfill] Foretak %d har ukjent bv_rolle="%s"', $foretak_id, $bv_rolle));
                continue 2;
        }

        $summary['by_type'][$new_type]++;
        $summary['by_nivaa'][$new_nivaa]++;

        if ($existing_type === $new_type && (string) get_field('bv_nivaa', $foretak_id) === $new_nivaa) {
            $summary['skipped']++;
            continue;
        }

        if (!$dry_run) {
            update_field('bv_foretakstype', $new_type, $foretak_id);
            update_field('bv_nivaa', $new_nivaa, $foretak_id);
        }
        $summary['updated']++;
        $summary['log'][] = sprintf(
            'Foretak %d (bv_rolle="%s") → bv_foretakstype=%s, bv_nivaa=%s',
            $foretak_id,
            $bv_rolle,
            $new_type,
            $new_nivaa === '' ? '(tom)' : $new_nivaa
        );
    }

    return $summary;
}

/**
 * WP-admin trigger: ?bimverdi_backfill_foretakstype=1 (admin-only)
 *
 * Lar admin kjøre backfill uten WP-CLI fra hvilken som helst wp-admin-side.
 */
add_action('admin_init', 'bimverdi_maybe_run_foretakstype_backfill');

function bimverdi_maybe_run_foretakstype_backfill() {
    if (empty($_GET['bimverdi_backfill_foretakstype'])) {
        return;
    }
    if (!current_user_can('manage_options')) {
        return;
    }

    $dry_run = !empty($_GET['dry_run']);
    $summary = bimverdi_run_foretakstype_backfill($dry_run);

    add_action('admin_notices', function() use ($summary, $dry_run) {
        $title = $dry_run ? 'Backfill (DRY RUN)' : 'Backfill kjørt';
        ?>
        <div class="notice notice-info">
            <h3><?php echo esc_html($title); ?> — bv_foretakstype + bv_nivaa</h3>
            <p>
                <strong>Totalt:</strong> <?php echo (int) $summary['total']; ?> foretak |
                <strong>Oppdatert:</strong> <?php echo (int) $summary['updated']; ?> |
                <strong>Allerede korrekt (skipped):</strong> <?php echo (int) $summary['skipped']; ?> |
                <strong>Ukjent bv_rolle:</strong> <?php echo (int) $summary['unknown']; ?>
            </p>
            <p>
                <strong>Fordeling:</strong>
                gratisforetak=<?php echo (int) $summary['by_type']['gratisforetak']; ?>,
                foretak=<?php echo (int) $summary['by_type']['foretak']; ?>
                (deltaker=<?php echo (int) $summary['by_nivaa']['deltaker']; ?>,
                prosjektdeltaker=<?php echo (int) $summary['by_nivaa']['prosjektdeltaker']; ?>,
                partner=<?php echo (int) $summary['by_nivaa']['partner']; ?>)
            </p>
            <?php if (!empty($summary['log'])): ?>
                <details>
                    <summary>Detalj-logg (<?php echo count($summary['log']); ?> linjer)</summary>
                    <pre style="max-height: 400px; overflow: auto; font-size: 11px;"><?php
                        echo esc_html(implode("\n", $summary['log']));
                    ?></pre>
                </details>
            <?php endif; ?>
        </div>
        <?php
    });
}
