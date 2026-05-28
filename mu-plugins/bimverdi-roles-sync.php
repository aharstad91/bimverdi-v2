<?php
/**
 * BIM Verdi — Sync av WP-rolle fra foretak-data
 *
 * Setter brukerens WP-rolle (medlem/tilleggskontakt/deltaker/
 * prosjektdeltaker/partner) basert på sannhetskilden i foretak-data:
 *
 *   Kontakttype = hovedkontakt + bv_nivaa = deltaker         → 'deltaker'
 *   Kontakttype = hovedkontakt + bv_nivaa = prosjektdeltaker → 'prosjektdeltaker'
 *   Kontakttype = hovedkontakt + bv_nivaa = partner          → 'partner'
 *   Kontakttype = tilleggskontakt (uansett nivå)             → 'tilleggskontakt'
 *   Kontakttype = gratisbruker (hovedkontakt eller ikke)     → 'medlem'
 *   Ingen foretak                                            → ikke rørt
 *   WP-rolle = administrator                                 → ALDRI rørt
 *
 * Idempotent: skipper users hvor WP-rollen allerede er korrekt.
 *
 * Trigger:
 *   - WP-admin: /wp-admin/?bimverdi_sync_roles=1[&dry_run=1]
 *   - WP-CLI:   wp eval 'print_r(bimverdi_run_roles_sync(true));'  (dry-run)
 *               wp eval 'print_r(bimverdi_run_roles_sync(false));' (kjør)
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Returnerer ønsket WP-rolle for en bruker basert på foretak-data.
 *
 * @param int $user_id
 * @return string|null  WP-rolle-slug eller null hvis ingen endring skal gjøres.
 */
function bimverdi_compute_target_wp_role($user_id) {
    if (!function_exists('bimverdi_get_kontakttype')) {
        return null;
    }
    $type = bimverdi_get_kontakttype($user_id);
    if ($type === null) {
        return null;
    }
    if ($type === 'gratisbruker') {
        return 'medlem';
    }
    if ($type === 'tilleggskontakt') {
        return 'tilleggskontakt';
    }
    if ($type === 'hovedkontakt') {
        $nivaa = bimverdi_get_deltakernivaa($user_id);
        if (in_array($nivaa, ['deltaker', 'prosjektdeltaker', 'partner'], true)) {
            return $nivaa;
        }
        return 'medlem';
    }
    return null;
}

/**
 * Synk én bruker.
 *
 * @param int  $user_id
 * @param bool $dry_run
 * @return array {string $from, string $to, bool $changed, string $reason}
 */
function bimverdi_sync_user_wp_role($user_id, $dry_run = false) {
    $user = get_userdata($user_id);
    if (!$user) {
        return ['from' => '', 'to' => '', 'changed' => false, 'reason' => 'user_not_found'];
    }

    if (in_array('administrator', $user->roles, true)) {
        return ['from' => implode(',', $user->roles), 'to' => '', 'changed' => false, 'reason' => 'admin_skipped'];
    }

    $target = bimverdi_compute_target_wp_role($user_id);
    if ($target === null) {
        return ['from' => implode(',', $user->roles), 'to' => '', 'changed' => false, 'reason' => 'no_foretak'];
    }

    $current = $user->roles[0] ?? '';
    if ($current === $target) {
        return ['from' => $current, 'to' => $target, 'changed' => false, 'reason' => 'already_correct'];
    }

    if (!$dry_run) {
        $user->set_role($target);
    }
    return ['from' => $current, 'to' => $target, 'changed' => true, 'reason' => 'updated'];
}

/**
 * Kjør sync mot alle brukere.
 *
 * @param bool $dry_run
 * @return array
 */
function bimverdi_run_roles_sync($dry_run = true) {
    $users = get_users(['fields' => ['ID']]);
    $summary = [
        'dry_run'         => (bool) $dry_run,
        'total'           => count($users),
        'updated'         => 0,
        'already_correct' => 0,
        'no_foretak'      => 0,
        'admin_skipped'   => 0,
        'changes'         => [], // [user_id, login, from, to]
    ];

    foreach ($users as $u) {
        $uid = (int) $u->ID;
        $res = bimverdi_sync_user_wp_role($uid, $dry_run);
        if ($res['changed']) {
            $summary['updated']++;
            $userdata = get_userdata($uid);
            $summary['changes'][] = [
                'id'    => $uid,
                'login' => $userdata ? $userdata->user_login : '',
                'from'  => $res['from'],
                'to'    => $res['to'],
            ];
        } else {
            $bucket = $res['reason'];
            if (isset($summary[$bucket])) {
                $summary[$bucket]++;
            }
        }
    }

    return $summary;
}

/**
 * Admin-trigger: ?bimverdi_sync_roles=1 (admin-only)
 */
add_action('admin_init', 'bimverdi_maybe_run_roles_sync');
function bimverdi_maybe_run_roles_sync() {
    if (empty($_GET['bimverdi_sync_roles'])) {
        return;
    }
    if (!current_user_can('manage_options')) {
        return;
    }
    $dry_run = !empty($_GET['dry_run']);
    $summary = bimverdi_run_roles_sync($dry_run);

    add_action('admin_notices', function () use ($summary, $dry_run) {
        $title = $dry_run ? 'Sync av WP-roller (DRY RUN)' : 'Sync av WP-roller kjørt';
        ?>
        <div class="notice notice-info">
            <h3><?php echo esc_html($title); ?></h3>
            <p>
                <strong>Totalt brukere:</strong> <?php echo (int) $summary['total']; ?> |
                <strong>Endret:</strong> <?php echo (int) $summary['updated']; ?> |
                <strong>Allerede korrekt:</strong> <?php echo (int) $summary['already_correct']; ?> |
                <strong>Uten foretak (urørt):</strong> <?php echo (int) $summary['no_foretak']; ?> |
                <strong>Admin (urørt):</strong> <?php echo (int) $summary['admin_skipped']; ?>
            </p>
            <?php if (!empty($summary['changes'])): ?>
                <details>
                    <summary><strong><?php echo count($summary['changes']); ?> endringer</strong> (klikk for detaljer)</summary>
                    <table class="widefat striped" style="margin-top:8px;">
                        <thead>
                            <tr><th>ID</th><th>Login</th><th>Fra</th><th>Til</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summary['changes'] as $c): ?>
                                <tr>
                                    <td><?php echo (int) $c['id']; ?></td>
                                    <td><?php echo esc_html($c['login']); ?></td>
                                    <td><code><?php echo esc_html($c['from']); ?></code></td>
                                    <td><code><?php echo esc_html($c['to']); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </details>
            <?php endif; ?>
            <?php if ($dry_run): ?>
                <p><em>Dette var en dry-run. <a href="<?php echo esc_url(add_query_arg(['bimverdi_sync_roles' => '1'], remove_query_arg('dry_run'))); ?>">Klikk her for å kjøre på ekte</a>.</em></p>
            <?php endif; ?>
        </div>
        <?php
    });
}
