<?php
/**
 * Foretak Import Runner - Kjør via: /wp-admin/admin.php?page=foretak-import-runner
 *
 * SLETT DENNE FILEN ETTER BRUK!
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function() {
    add_submenu_page(
        null, // Skjult fra meny
        'Foretak Import',
        'Foretak Import',
        'manage_options',
        'foretak-import-runner',
        'bimverdi_foretak_import_runner_page'
    );
});

function bimverdi_foretak_import_runner_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Ingen tilgang');
    }

    $dry_run = !isset($_POST['run_import']);
    $results = [];
    $csv_data = null;

    // CSV-data hardkodet fra eksporten
    if (isset($_POST['run_import']) || isset($_POST['dry_run'])) {
        $csv_data = bimverdi_get_foretak_csv_data();
        $results = bimverdi_run_foretak_import($csv_data, $dry_run);
    }

    ?>
    <div class="wrap">
        <h1>Foretak Import fra Formidable Forms</h1>

        <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <strong>⚠️ Viktig:</strong> Slett denne filen (<code>mu-plugins/bimverdi-foretak-import-runner.php</code>) etter bruk!
        </div>

        <form method="post" style="margin: 20px 0;">
            <p>
                <button type="submit" name="dry_run" class="button button-secondary">
                    🔍 Dry Run (vis hva som vil importeres)
                </button>
                <button type="submit" name="run_import" class="button button-primary" onclick="return confirm('Er du sikker på at du vil kjøre importen?');">
                    ✅ Kjør Import
                </button>
            </p>
        </form>

        <?php if (!empty($results)): ?>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-top: 20px;">
            <h2><?php echo $dry_run ? '🔍 Dry Run Resultat' : '✅ Import Resultat'; ?></h2>

            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Foretak</th>
                        <th>Status</th>
                        <th>Felt oppdatert</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                    <tr>
                        <td><strong><?php echo esc_html($result['name']); ?></strong></td>
                        <td>
                            <?php if ($result['status'] === 'updated'): ?>
                                <span style="color: green;">✓ <?php echo $dry_run ? 'Vil oppdateres' : 'Oppdatert'; ?></span>
                            <?php elseif ($result['status'] === 'skipped'): ?>
                                <span style="color: orange;">⏭ Hoppet over</span>
                            <?php else: ?>
                                <span style="color: red;">✗ Ikke funnet</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($result['fields'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            $updated = count(array_filter($results, fn($r) => $r['status'] === 'updated'));
            $not_found = count(array_filter($results, fn($r) => $r['status'] === 'not_found'));
            $skipped = count(array_filter($results, fn($r) => $r['status'] === 'skipped'));
            ?>
            <p style="margin-top: 15px;">
                <strong>Oppsummering:</strong>
                <?php echo $updated; ?> oppdatert,
                <?php echo $not_found; ?> ikke funnet,
                <?php echo $skipped; ?> hoppet over
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

function bimverdi_run_foretak_import($csv_data, $dry_run = true) {
    $results = [];

    // Felt-konfigurasjon - mapper CSV-verdier til ACF checkbox keys
    $bransje_map = [
        'Bestiller/byggherre' => 'bestiller_byggherre',
        'Arkitekt/rådgiver' => 'arkitekt_radgiver',
        'Arkitekt' => 'arkitekt_radgiver',
        'Rådgivende ingeniør' => 'arkitekt_radgiver',
        'Entreprenør/byggmester' => 'entreprenor_byggmester',
        'Enterprenør/byggmester' => 'entreprenor_byggmester',
        'Byggevareprodusent' => 'byggevareprodusent',
        'Byggevarehandel' => 'byggevarehandel',
        'Eiendom/drift' => 'eiendom_drift',
        'Leverandør av digitale verktøy' => 'digital_leverandor',
        'innhold og løsninger' => 'digital_leverandor', // Del av "Leverandør av digitale verktøy, innhold og løsninger"
        'Organisasjon' => 'organisasjon',
        'nettverk m.m.' => 'organisasjon', // Del av "Organisasjon, nettverk m.m."
        'Tjenesteleverandør' => 'tjenesteleverandor',
        'Offentlig instans' => 'offentlig',
        'Utdanningsinstitusjon' => 'utdanning',
    ];

    $kunde_map = [
        'Bestiller/byggherre' => 'bestiller_byggherre',
        'Bestillere/byggherrer' => 'bestiller_byggherre',
        'Arkitekt/rådgiver' => 'arkitekt_radgiver',
        'Arkitekter/rådgivere' => 'arkitekt_radgiver',
        'Entreprenør/byggmester' => 'entreprenor_byggmester',
        'Enterprenør/byggmester' => 'entreprenor_byggmester',
        'Byggevareprodusent' => 'byggevareprodusent',
        'Byggevarehandel' => 'byggevarehandel',
        'Eiendom/drift' => 'eiendom_drift',
        'Eiendomsforvaltere' => 'eiendom_drift',
        'Leverandør av digitale verktøy' => 'digital_leverandor',
        'innhold og løsninger' => 'digital_leverandor',
        'Organisasjon' => 'organisasjon',
        'nettverk m.m.' => 'organisasjon',
        'Tjenesteleverandør' => 'tjenesteleverandor',
        'Offentlig instans' => 'offentlig',
        'Utdanningsinstitusjon' => 'utdanning',
        'Brukere av bygg' => 'eiendom_drift',
    ];

    foreach ($csv_data as $row) {
        $company_name = $row['company_name'] ?? '';
        if (empty($company_name)) continue;

        // Finn foretak-post
        $posts = get_posts([
            'post_type' => 'foretak',
            'title' => $company_name,
            'post_status' => 'publish',
            'numberposts' => 1,
        ]);

        if (empty($posts)) {
            // Prøv fuzzy match
            $all_foretak = get_posts([
                'post_type' => 'foretak',
                'post_status' => 'publish',
                'numberposts' => -1,
            ]);

            foreach ($all_foretak as $foretak) {
                if (strcasecmp(trim($foretak->post_title), trim($company_name)) === 0) {
                    $posts = [$foretak];
                    break;
                }
            }
        }

        if (empty($posts)) {
            $results[] = ['name' => $company_name, 'status' => 'not_found', 'fields' => null];
            continue;
        }

        $post = $posts[0];
        $fields_updated = [];

        // Kort beskrivelse
        if (!empty($row['beskrivelse'])) {
            if (!$dry_run) {
                update_field('kort_beskrivelse', sanitize_textarea_field($row['beskrivelse']), $post->ID);
            }
            $fields_updated[] = 'kort_beskrivelse';
        }

        // Bransje/rolle
        if (!empty($row['bransje_rolle'])) {
            $values = array_map('trim', explode(',', $row['bransje_rolle']));
            $mapped = [];
            foreach ($values as $v) {
                if (isset($bransje_map[$v])) {
                    $mapped[] = $bransje_map[$v];
                }
            }
            if (!empty($mapped) && !$dry_run) {
                update_field('bransje_rolle', $mapped, $post->ID);
            }
            if (!empty($mapped)) $fields_updated[] = 'bransje_rolle';
        }

        // Kundetyper
        if (!empty($row['kundetyper'])) {
            $values = array_map('trim', explode(',', $row['kundetyper']));
            $mapped = [];
            foreach ($values as $v) {
                if (isset($kunde_map[$v])) {
                    $mapped[] = $kunde_map[$v];
                }
            }
            if (!empty($mapped) && !$dry_run) {
                update_field('kundetyper', $mapped, $post->ID);
            }
            if (!empty($mapped)) $fields_updated[] = 'kundetyper';
        }

        // YouTube URL
        if (!empty($row['youtube_url'])) {
            if (!$dry_run) {
                update_field('youtube_url', esc_url_raw($row['youtube_url']), $post->ID);
            }
            $fields_updated[] = 'youtube_url';
        }

        // Hashtag
        if (!empty($row['hashtag'])) {
            if (!$dry_run) {
                update_field('hashtag', sanitize_text_field($row['hashtag']), $post->ID);
            }
            $fields_updated[] = 'hashtag';
        }

        // Artikkel lenke
        if (!empty($row['artikkel_lenke'])) {
            if (!$dry_run) {
                update_field('artikkel_lenke', esc_url_raw($row['artikkel_lenke']), $post->ID);
            }
            $fields_updated[] = 'artikkel_lenke';
        }

        if (!empty($fields_updated)) {
            $results[] = [
                'name' => $company_name,
                'status' => 'updated',
                'fields' => implode(', ', $fields_updated),
            ];
        } else {
            $results[] = ['name' => $company_name, 'status' => 'skipped', 'fields' => 'Ingen nye data'];
        }
    }

    return $results;
}

function bimverdi_get_foretak_csv_data() {
    // CSV-data fra Formidable Forms eksport
    return include __DIR__ . '/foretak-import-data.php';
}
