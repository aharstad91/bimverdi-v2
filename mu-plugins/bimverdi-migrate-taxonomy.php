<?php
/**
 * BIM Verdi - Migrate ACF bransje_rolle/kundetyper to WordPress taxonomies
 *
 * One-time migration script. Reads existing ACF post_meta data (originally
 * imported from Formidable Forms) and creates/assigns WordPress taxonomy terms.
 *
 * Usage: Visit /min-side/?bv_migrate_tax=1 as admin, or run via WP-CLI:
 *   wp eval 'do_action("bimverdi_migrate_taxonomies");'
 *
 * Safe to run multiple times (idempotent). ACF data is preserved.
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Migration function — syncs ACF fields → taxonomy terms for all foretak
 */
function bimverdi_migrate_acf_to_taxonomies($dry_run = false) {
    $cpt = defined('BV_CPT_COMPANY') ? BV_CPT_COMPANY : 'foretak';
    $tax_industry = defined('BV_TAX_INDUSTRY') ? BV_TAX_INDUSTRY : 'bransjekategori';
    $tax_customer = defined('BV_TAX_CUSTOMER_TYPE') ? BV_TAX_CUSTOMER_TYPE : 'kundetype';

    // Label map for creating terms with readable names
    $bransje_labels = [
        'bestiller_byggherre'    => 'Bestiller/byggherre',
        'boligutvikler'          => 'Boligutvikler',
        'arkitekt_radgiver'      => 'Arkitekt/rådgiver',
        'radgivende_ingenior'    => 'Rådgivende ingeniør',
        'entreprenor_byggmester'  => 'Entreprenør/byggmester',
        'byggevareprodusent'     => 'Byggevareprodusent',
        'byggevarehandel'        => 'Byggevarehandel',
        'eiendom_drift'          => 'Eiendom/drift',
        'digital_leverandor'     => 'Leverandør av digitale verktøy, innhold og løsninger',
        'organisasjon'           => 'Organisasjon, nettverk m.m.',
        'tjenesteleverandor'     => 'Tjenesteleverandør',
        'offentlig'              => 'Offentlig instans',
        'utdanning'              => 'Utdanningsinstitusjon',
        'annet'                  => 'Annet',
    ];

    $kundetype_labels = [
        'bestiller_byggherre'    => 'Bestiller/byggherre',
        'arkitekt_radgiver'      => 'Arkitekt/rådgiver',
        'entreprenor_byggmester'  => 'Entreprenør/byggmester',
        'byggevareprodusent'     => 'Byggevareprodusent',
        'byggevarehandel'        => 'Byggevarehandel',
        'eiendom_drift'          => 'Eiendom/drift',
        'digital_leverandor'     => 'Leverandør av digitale verktøy',
        'organisasjon'           => 'Organisasjon',
        'tjenesteleverandor'     => 'Tjenesteleverandør',
        'offentlig'              => 'Offentlig instans',
        'utdanning'              => 'Utdanningsinstitusjon',
        'annet'                  => 'Annet',
    ];

    // Step 1: Pre-create all taxonomy terms with proper names
    if (!$dry_run) {
        foreach ($bransje_labels as $slug => $name) {
            if (!term_exists($slug, $tax_industry)) {
                wp_insert_term($name, $tax_industry, ['slug' => $slug]);
            }
        }
        foreach ($kundetype_labels as $slug => $name) {
            if (!term_exists($slug, $tax_customer)) {
                wp_insert_term($name, $tax_customer, ['slug' => $slug]);
            }
        }
    }

    // Step 2: Get all foretak
    $foretak = get_posts([
        'post_type'      => $cpt,
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'fields'         => 'ids',
    ]);

    $results = [
        'total'             => count($foretak),
        'bransje_migrated'  => 0,
        'kundetype_migrated'=> 0,
        'bransje_skipped'   => 0,
        'kundetype_skipped' => 0,
        'errors'            => [],
        'details'           => [],
    ];

    foreach ($foretak as $company_id) {
        $title = get_the_title($company_id);
        $detail = ['id' => $company_id, 'title' => $title];

        // Read ACF bransje_rolle
        $bransje_raw = get_post_meta($company_id, 'bransje_rolle', true);
        $bransje_slugs = [];
        if (!empty($bransje_raw) && is_array($bransje_raw)) {
            // Deduplicate and filter to known slugs
            $bransje_slugs = array_unique(array_filter($bransje_raw, function($s) use ($bransje_labels) {
                return isset($bransje_labels[$s]);
            }));
        }

        // Read ACF kundetyper
        $kundetyper_raw = get_post_meta($company_id, 'kundetyper', true);
        $kundetype_slugs = [];
        if (!empty($kundetyper_raw) && is_array($kundetyper_raw)) {
            $kundetype_slugs = array_unique(array_filter($kundetyper_raw, function($s) use ($kundetype_labels) {
                return isset($kundetype_labels[$s]);
            }));
        }

        $detail['bransje'] = $bransje_slugs;
        $detail['kundetyper'] = $kundetype_slugs;

        // Set taxonomy terms
        if (!empty($bransje_slugs)) {
            if (!$dry_run) {
                $result = wp_set_object_terms($company_id, array_values($bransje_slugs), $tax_industry);
                if (is_wp_error($result)) {
                    $results['errors'][] = "$company_id ($title): bransje error: " . $result->get_error_message();
                } else {
                    $results['bransje_migrated']++;
                }
            } else {
                $results['bransje_migrated']++;
            }
        } else {
            $results['bransje_skipped']++;
        }

        if (!empty($kundetype_slugs)) {
            if (!$dry_run) {
                $result = wp_set_object_terms($company_id, array_values($kundetype_slugs), $tax_customer);
                if (is_wp_error($result)) {
                    $results['errors'][] = "$company_id ($title): kundetype error: " . $result->get_error_message();
                } else {
                    $results['kundetype_migrated']++;
                }
            } else {
                $results['kundetype_migrated']++;
            }
        } else {
            $results['kundetype_skipped']++;
        }

        $results['details'][] = $detail;
    }

    return $results;
}

// Admin-triggered migration via URL parameter
add_action('template_redirect', function () {
    if (!isset($_GET['bv_migrate_tax'])) {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die('Admin only.');
    }

    $dry_run = isset($_GET['dry_run']);
    $results = bimverdi_migrate_acf_to_taxonomies($dry_run);

    header('Content-Type: text/plain; charset=utf-8');

    echo $dry_run ? "=== DRY RUN (no changes made) ===\n\n" : "=== MIGRATION COMPLETE ===\n\n";
    echo "Total foretak: {$results['total']}\n";
    echo "Bransje migrated: {$results['bransje_migrated']}\n";
    echo "Bransje skipped (no data): {$results['bransje_skipped']}\n";
    echo "Kundetyper migrated: {$results['kundetype_migrated']}\n";
    echo "Kundetyper skipped (no data): {$results['kundetype_skipped']}\n";

    if (!empty($results['errors'])) {
        echo "\nERRORS:\n";
        foreach ($results['errors'] as $err) {
            echo "  - $err\n";
        }
    }

    echo "\n--- Details ---\n";
    foreach ($results['details'] as $d) {
        echo "\n{$d['id']}: {$d['title']}\n";
        echo "  bransje: " . (empty($d['bransje']) ? '(none)' : implode(', ', $d['bransje'])) . "\n";
        echo "  kundetyper: " . (empty($d['kundetyper']) ? '(none)' : implode(', ', $d['kundetyper'])) . "\n";
    }

    exit;
});

// WP-CLI hook
add_action('bimverdi_migrate_taxonomies', function () {
    $results = bimverdi_migrate_acf_to_taxonomies(false);
    WP_CLI::success("Migrated {$results['bransje_migrated']} bransje and {$results['kundetype_migrated']} kundetyper.");
    if (!empty($results['errors'])) {
        foreach ($results['errors'] as $err) {
            WP_CLI::warning($err);
        }
    }
});
