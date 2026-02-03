<?php
/**
 * WP-CLI Command: Import Foretak data from Formidable Forms CSV
 *
 * Usage:
 *   wp bimverdi foretak-import <csv-file> [--dry-run]
 *
 * @package BIMVerdiCore
 */

if (!defined('WP_CLI')) {
    return;
}

/**
 * BIM Verdi CLI Commands
 */
class BIM_Verdi_CLI_Commands {

    /**
     * Import Foretak data from Formidable Forms CSV export
     *
     * ## OPTIONS
     *
     * <csv-file>
     * : Path to the Formidable Forms CSV export file
     *
     * [--dry-run]
     * : Run without making changes, just show what would be imported
     *
     * [--force]
     * : Overwrite existing field values (default: only update empty fields)
     *
     * ## EXAMPLES
     *
     *     wp bimverdi foretak-import /path/to/foretak.csv --dry-run
     *     wp bimverdi foretak-import /path/to/foretak.csv
     *
     * @param array $args       Positional arguments
     * @param array $assoc_args Named arguments
     */
    public function foretak_import($args, $assoc_args) {
        $csv_file = $args[0];
        $dry_run = isset($assoc_args['dry-run']);
        $force = isset($assoc_args['force']);

        if (!file_exists($csv_file)) {
            WP_CLI::error("CSV file not found: {$csv_file}");
        }

        WP_CLI::log("=== BIM Verdi Foretak Import ===");
        WP_CLI::log("CSV: {$csv_file}");
        WP_CLI::log("Mode: " . ($dry_run ? "DRY RUN" : "LIVE"));
        WP_CLI::log("Overwrite: " . ($force ? "Yes" : "No (empty fields only)"));
        WP_CLI::log("");

        // Read CSV
        $csv_data = $this->read_csv($csv_file);
        if (empty($csv_data)) {
            WP_CLI::error("No data found in CSV");
        }

        WP_CLI::log("Found " . count($csv_data) . " entries in CSV");
        WP_CLI::log("");

        // Stats
        $stats = [
            'processed' => 0,
            'matched' => 0,
            'updated' => 0,
            'skipped' => 0,
            'not_found' => 0,
            'fields_updated' => 0,
        ];

        // Process each entry
        foreach ($csv_data as $row) {
            $stats['processed']++;

            // Skip draft/trashed entries
            $entry_status = $row['Entry Status'] ?? '0';
            $entry_id = $row['ID'] ?? $row['Entry Id'] ?? '';
            if ($entry_status !== '0') {
                WP_CLI::log("[SKIP] Entry {$entry_id} - Draft/trashed (status: {$entry_status})");
                $stats['skipped']++;
                continue;
            }

            // Get org number
            $org_nr = $this->clean_org_number($row['Organisasjonsnummer'] ?? '');
            $company_name = trim($row['Foretaksnavn'] ?? '');

            if (empty($org_nr) && empty($company_name)) {
                WP_CLI::log("[SKIP] Entry {$entry_id} - No org number or name");
                $stats['skipped']++;
                continue;
            }

            // Find matching foretak post
            $foretak_id = $this->find_foretak($org_nr, $company_name);

            if (!$foretak_id) {
                WP_CLI::warning("[NOT FOUND] \"{$company_name}\" (org: {$org_nr})");
                $stats['not_found']++;
                continue;
            }

            $stats['matched']++;
            $foretak_title = get_the_title($foretak_id);
            WP_CLI::log("[MATCH] Entry {$entry_id} → Post {$foretak_id} \"{$foretak_title}\"");

            // Prepare field updates
            $updates = $this->prepare_updates($row, $foretak_id, $force);

            if (empty($updates)) {
                WP_CLI::log("  → No fields to update");
                continue;
            }

            // Apply updates
            $updated_count = 0;
            foreach ($updates as $field_name => $value) {
                $display_value = is_array($value) ? implode(', ', $value) : $value;
                $display_value = mb_strlen($display_value) > 50 ? mb_substr($display_value, 0, 47) . '...' : $display_value;
                WP_CLI::log("  → {$field_name}: {$display_value}");

                if (!$dry_run) {
                    update_field($field_name, $value, $foretak_id);
                }
                $updated_count++;
            }

            if ($updated_count > 0) {
                $stats['updated']++;
                $stats['fields_updated'] += $updated_count;
            }
        }

        // Print summary
        WP_CLI::log("");
        WP_CLI::log("=== Import Summary ===");
        WP_CLI::log("Processed: {$stats['processed']}");
        WP_CLI::log("Matched:   {$stats['matched']}");
        WP_CLI::log("Updated:   {$stats['updated']}");
        WP_CLI::log("Fields:    {$stats['fields_updated']}");
        WP_CLI::log("Skipped:   {$stats['skipped']}");
        WP_CLI::log("Not found: {$stats['not_found']}");

        if ($dry_run) {
            WP_CLI::warning("DRY RUN - No changes were made");
        } else {
            WP_CLI::success("Import completed!");
        }
    }

    /**
     * Read CSV file into associative array
     */
    private function read_csv($file) {
        $data = [];
        $handle = fopen($file, 'r');

        if (!$handle) {
            return [];
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return [];
        }

        // Clean headers (remove BOM if present)
        $headers[0] = preg_replace('/^\x{FEFF}/u', '', $headers[0]);

        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $data[] = array_combine($headers, $row);
            }
        }

        fclose($handle);
        return $data;
    }

    /**
     * Clean organization number
     */
    private function clean_org_number($org_nr) {
        // Remove spaces and non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $org_nr);
        // Must be 9 digits
        return strlen($cleaned) === 9 ? $cleaned : '';
    }

    /**
     * Find foretak post by org number or name
     */
    private function find_foretak($org_nr, $company_name) {
        // 1. Try org number first (most reliable)
        if (!empty($org_nr)) {
            $args = [
                'post_type' => 'foretak',
                'posts_per_page' => 1,
                'post_status' => 'any',
                'meta_query' => [
                    [
                        'key' => 'organisasjonsnummer',
                        'value' => $org_nr,
                        'compare' => '='
                    ]
                ]
            ];
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                return $query->posts[0]->ID;
            }
        }

        // 2. Try exact title match
        if (!empty($company_name)) {
            $args = [
                'post_type' => 'foretak',
                'posts_per_page' => 1,
                'post_status' => 'any',
                'title' => $company_name,
            ];
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                return $query->posts[0]->ID;
            }

            // 3. Try fuzzy match (sanitized title)
            $args = [
                'post_type' => 'foretak',
                'posts_per_page' => 1,
                'post_status' => 'any',
                'name' => sanitize_title($company_name),
            ];
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                return $query->posts[0]->ID;
            }
        }

        return null;
    }

    /**
     * Prepare field updates from CSV row
     */
    private function prepare_updates($row, $foretak_id, $force) {
        $updates = [];

        // Field mapping configurations
        // CSV column names from Formidable Forms export (2026-02-03)
        $field_configs = [
            'kort_beskrivelse' => [
                'csv_columns' => [
                    'Virksomhetsbeskrivelse - maks. 800 tegn og mellomrom (publiseres i din deltakerprofil)',
                ],
                'transform' => 'kort_beskrivelse',
            ],
            'bransje_rolle' => [
                'csv_columns' => ['Vår rolle/fag/bransje er (du kan krysse av flere):'],
                'transform' => 'bransje_rolle',
            ],
            'interesseomrader' => [
                'csv_columns' => ['Angi interesse for prosjekt og/eller temagruppe'],
                'transform' => 'interesseomrader',
            ],
            'kundetyper' => [
                'csv_columns' => ['Hvem er våre kunder?'],
                'transform' => 'kundetyper',
            ],
            'linkedin_url' => [
                'csv_columns' => ['LinkedIn - foretak'],
                'transform' => 'url',
            ],
            'facebook_url' => [
                'csv_columns' => ['Facebook - foretak'],
                'transform' => 'url',
            ],
            'youtube_url' => [
                'csv_columns' => ['YouTube-kanal  - foretak'], // Note: two spaces before dash
                'transform' => 'url',
            ],
            'twitter_url' => [
                'csv_columns' => ['X- foretak'],
                'transform' => 'url',
            ],
            'artikkel_lenke' => [
                'csv_columns' => ['Link til artikkel etc. om bedriften'],
                'transform' => 'url',
            ],
            'hashtag' => [
                'csv_columns' => ['# (hashtag) som du ønsker brukt på Linkedin etc.'],
                'transform' => 'text',
            ],
            'bv_rolle' => [
                'csv_columns' => ['Angi type deltakelse'],
                'transform' => 'bv_rolle',
            ],
        ];

        foreach ($field_configs as $acf_field => $config) {
            // Check if field should be updated
            if (!$force) {
                $existing_value = get_field($acf_field, $foretak_id);
                if (!empty($existing_value)) {
                    continue; // Skip - field already has value
                }
            }

            // Get value from CSV columns (try each in order)
            $csv_value = '';
            foreach ($config['csv_columns'] as $col) {
                if (!empty($row[$col])) {
                    $csv_value = $row[$col];
                    break;
                }
            }

            if (empty($csv_value)) {
                continue; // No value in CSV
            }

            // Transform value
            $transformed = $this->transform_value($csv_value, $config['transform']);

            if (!empty($transformed)) {
                $updates[$acf_field] = $transformed;
            }
        }

        return $updates;
    }

    /**
     * Transform CSV value to ACF format
     */
    private function transform_value($value, $transform_type) {
        // Skip placeholder values
        $skip_values = ['kommer', 'x', 'xx', 'xxx', 'N/A', '-', '.'];
        if (in_array(strtolower(trim($value)), array_map('strtolower', $skip_values))) {
            return null;
        }

        switch ($transform_type) {
            case 'kort_beskrivelse':
                return $this->transform_kort_beskrivelse($value);

            case 'bransje_rolle':
                return $this->transform_bransje_rolle($value);

            case 'interesseomrader':
                return $this->transform_interesseomrader($value);

            case 'kundetyper':
                return $this->transform_kundetyper($value);

            case 'bv_rolle':
                return $this->transform_bv_rolle($value);

            case 'url':
                return $this->transform_url($value);

            case 'text':
            default:
                return trim($value);
        }
    }

    /**
     * Transform kort_beskrivelse
     */
    private function transform_kort_beskrivelse($value) {
        $value = trim($value);

        // Skip empty/placeholder values
        if (empty($value) || in_array(strtolower($value), ['kommer', 'x', 'xx'])) {
            return null;
        }

        // Truncate to 300 chars if needed
        if (mb_strlen($value) > 300) {
            $value = mb_substr($value, 0, 297) . '...';
        }

        return $value;
    }

    /**
     * Transform bransje_rolle checkbox values
     */
    private function transform_bransje_rolle($value) {
        $mapping = [
            'Bestiller/byggherre' => 'bestiller_byggherre',
            'Arkitekt/rådgiver' => 'arkitekt_radgiver',
            'Enterprenør/byggmester' => 'entreprenor_byggmester', // Typo in FF
            'Entreprenør/byggmester' => 'entreprenor_byggmester',
            'Byggevareprodusent' => 'byggevareprodusent',
            'Byggevarehandel' => 'byggevarehandel',
            'Eiendom/drift' => 'eiendom_drift',
            'Leverandør av digitale verktøy, innhold og løsninger' => 'digital_leverandor',
            'Leverandør av digitale verktøy og løsninger, rådgivning og opplæring' => 'digital_leverandor',
            'Organisasjon, nettverk m.m.' => 'organisasjon',
            'Tjenesteleverandør' => 'tjenesteleverandor',
            'Offentlig instans' => 'offentlig',
            'Utdanningsinstitusjon' => 'utdanning',
            'Brukere av bygg' => 'eiendom_drift',
        ];

        return $this->map_checkbox_values($value, $mapping);
    }

    /**
     * Transform interesseomrader checkbox values
     */
    private function transform_interesseomrader($value) {
        $mapping = [
            'ByggesaksBIM (byggesøknader, GIS, visualisering)' => 'byggesak',
            'ProsjektBIM (prosjektering, bygging, logistikk, egenskaper i IFC)' => 'prosjekt',
            'ProsjektBIM (prosjektering, bygging, logistikk)' => 'prosjekt',
            'EiendomsBIM (digital tvilling, drift)' => 'eiendom',
            'MiljøBIM (klimagassberegninger fra BIM)' => 'miljo',
            'MiljøBIM (klimagassberegninger)' => 'miljo',
            'SirkBIM (ombruk, gjenbruk, verdibank etc.)' => 'sirk',
            'SirkBIM (ombruk, gjenbruk, verdibank)' => 'sirk',
            'BIMtech (muliggjørende teknologier som KI, skann til BIM, IDS m.m.)' => 'tech',
            'BIMtech (KI, skann til BIM, IDS)' => 'tech',
        ];

        return $this->map_checkbox_values($value, $mapping);
    }

    /**
     * Transform kundetyper checkbox values
     */
    private function transform_kundetyper($value) {
        $mapping = [
            'Bestiller/byggherre' => 'bestiller',
            'Arkitekt/rådgiver' => 'arkitekt',
            'Entreprenør/byggmester' => 'entreprenor',
            'Enterprenør/byggmester' => 'entreprenor', // Typo variant
            'Byggevareprodusent' => 'produsent',
            'Byggevarehandel' => 'handel',
            'Eiendom/drift' => 'eiendom',
            'Leverandør av digitale verktøy' => 'digital',
            'Leverandør av digitale verktøy, innhold og løsninger' => 'digital',
            'Organisasjon, nettverk m.m.' => 'organisasjon',
            'Tjenesteleverandør' => 'tjeneste',
            'Utdanningsinstitusjon' => 'utdanning',
            'Brukere av bygg' => 'brukere',
        ];

        return $this->map_checkbox_values($value, $mapping);
    }

    /**
     * Transform bv_rolle select value
     */
    private function transform_bv_rolle($value) {
        $mapping = [
            'Deltaker (D)' => 'Deltaker',
            'Deltaker' => 'Deltaker',
            'Partner (P)' => 'Partner',
            'Prosjektdeltaker (PD)' => 'Prosjektdeltaker',
            'Egen avtale' => 'Deltaker',
        ];

        $value = trim($value);
        return $mapping[$value] ?? 'Deltaker';
    }

    /**
     * Transform URL value
     */
    private function transform_url($value) {
        $value = trim($value);

        if (empty($value)) {
            return null;
        }

        // Add https:// if missing
        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . $value;
        }

        // Validate URL
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $value;
    }

    /**
     * Map comma-separated values to ACF checkbox array
     */
    private function map_checkbox_values($csv_value, $mapping) {
        $parts = array_map('trim', explode(',', $csv_value));
        $acf_values = [];

        foreach ($parts as $part) {
            if (isset($mapping[$part])) {
                $acf_values[] = $mapping[$part];
            }
        }

        // Remove duplicates
        $acf_values = array_unique($acf_values);

        return !empty($acf_values) ? array_values($acf_values) : null;
    }

    /**
     * Import Kunnskapskilde data from Formidable Forms CSV export
     *
     * Creates new kunnskapskilde CPT posts from CSV entries.
     * All posts are owned by Verdinettverk AS.
     *
     * ## OPTIONS
     *
     * <csv-file>
     * : Path to the Formidable Forms CSV export file
     *
     * [--dry-run]
     * : Run without making changes, just show what would be imported
     *
     * [--status=<status>]
     * : Post status for imported entries (default: publish)
     *
     * ## EXAMPLES
     *
     *     wp bimverdi kunnskapskilde-import /path/to/kunnskapskilder.csv --dry-run
     *     wp bimverdi kunnskapskilde-import /path/to/kunnskapskilder.csv
     *
     * @param array $args       Positional arguments
     * @param array $assoc_args Named arguments
     */
    public function kunnskapskilde_import($args, $assoc_args) {
        $csv_file = $args[0];
        $dry_run = isset($assoc_args['dry-run']);
        $post_status = $assoc_args['status'] ?? 'publish';

        if (!file_exists($csv_file)) {
            WP_CLI::error("CSV file not found: {$csv_file}");
        }

        WP_CLI::log("=== BIM Verdi Kunnskapskilde Import ===");
        WP_CLI::log("CSV: {$csv_file}");
        WP_CLI::log("Mode: " . ($dry_run ? "DRY RUN" : "LIVE"));
        WP_CLI::log("Post status: {$post_status}");
        WP_CLI::log("");

        // Find Verdinettverk AS foretak
        $verdinettverk_id = $this->find_verdinettverk();
        if (!$verdinettverk_id) {
            WP_CLI::error("Could not find 'Verdinettverk AS' foretak. Please create it first.");
        }
        WP_CLI::log("Owner: Verdinettverk AS (post ID: {$verdinettverk_id})");
        WP_CLI::log("");

        // Read CSV
        $csv_data = $this->read_csv($csv_file);
        if (empty($csv_data)) {
            WP_CLI::error("No data found in CSV");
        }

        WP_CLI::log("Found " . count($csv_data) . " entries in CSV");
        WP_CLI::log("");

        // Stats
        $stats = [
            'processed' => 0,
            'created' => 0,
            'skipped_duplicate' => 0,
            'skipped_empty' => 0,
            'logos_attached' => 0,
            'errors' => 0,
        ];

        foreach ($csv_data as $row) {
            $stats['processed']++;

            $navn = trim($row['Kunnskapskilde-navn- maks 100 tegn'] ?? '');
            $url = trim($row['Link'] ?? '');

            if (empty($navn)) {
                WP_CLI::log("[SKIP] Row {$stats['processed']} - Empty name");
                $stats['skipped_empty']++;
                continue;
            }

            // Duplicate check via URL
            if (!empty($url)) {
                $existing = get_posts([
                    'post_type' => 'kunnskapskilde',
                    'post_status' => 'any',
                    'meta_query' => [
                        'relation' => 'OR',
                        ['key' => 'ekstern_lenke', 'value' => $url, 'compare' => '='],
                        ['key' => 'ekstern_lenke', 'value' => trailingslashit($url), 'compare' => '='],
                        ['key' => 'ekstern_lenke', 'value' => untrailingslashit($url), 'compare' => '='],
                    ],
                    'posts_per_page' => 1,
                    'fields' => 'ids',
                ]);

                if (!empty($existing)) {
                    WP_CLI::log("[DUPLICATE] \"{$navn}\" - URL already exists (post {$existing[0]})");
                    $stats['skipped_duplicate']++;
                    continue;
                }
            }

            // Lookup user
            $user_login = trim($row['User ID'] ?? '');
            $wp_user = $user_login ? get_user_by('login', $user_login) : null;
            $author_id = $wp_user ? $wp_user->ID : 1; // Fallback to admin

            WP_CLI::log("[CREATE] \"{$navn}\"");

            if (!$dry_run) {
                $post_id = wp_insert_post([
                    'post_type' => 'kunnskapskilde',
                    'post_title' => sanitize_text_field($navn),
                    'post_content' => '',
                    'post_status' => $post_status,
                    'post_author' => $author_id,
                    'post_date' => $row['Timestamp'] ?? current_time('mysql'),
                ]);

                if (is_wp_error($post_id)) {
                    WP_CLI::warning("  Failed to create: " . $post_id->get_error_message());
                    $stats['errors']++;
                    continue;
                }

                // Save ACF fields
                $this->save_kunnskapskilde_acf($post_id, $row, $verdinettverk_id, $wp_user);

                // Save temagruppe taxonomy
                $this->save_kunnskapskilde_temagrupper($post_id, $row);

                // Download and set logo as featured image
                $logo_url = trim($row['Evnt. logo for kunnskapskilden'] ?? '');
                if (!empty($logo_url)) {
                    $this->attach_logo($post_id, $logo_url, $navn, $dry_run);
                    $stats['logos_attached']++;
                }

                WP_CLI::log("  → Post ID: {$post_id}");
            }

            $stats['created']++;
        }

        // Summary
        WP_CLI::log("");
        WP_CLI::log("=== Import Summary ===");
        WP_CLI::log("Processed:  {$stats['processed']}");
        WP_CLI::log("Created:    {$stats['created']}");
        WP_CLI::log("Duplicates: {$stats['skipped_duplicate']}");
        WP_CLI::log("Empty:      {$stats['skipped_empty']}");
        WP_CLI::log("Logos:      {$stats['logos_attached']}");
        WP_CLI::log("Errors:     {$stats['errors']}");

        if ($dry_run) {
            WP_CLI::warning("DRY RUN - No changes were made");
        } else {
            WP_CLI::success("Import completed!");
        }
    }

    /**
     * Find Verdinettverk AS foretak post
     */
    private function find_verdinettverk() {
        // Try exact title
        $args = [
            'post_type' => 'foretak',
            'posts_per_page' => 1,
            'post_status' => 'any',
            'title' => 'Verdinettverk AS',
        ];
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            return $query->posts[0]->ID;
        }

        // Try partial match
        $args['s'] = 'Verdinettverk';
        unset($args['title']);
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            return $query->posts[0]->ID;
        }

        return null;
    }

    /**
     * Save ACF fields for kunnskapskilde
     */
    private function save_kunnskapskilde_acf($post_id, $row, $verdinettverk_id, $wp_user) {
        // Name
        $navn = trim($row['Kunnskapskilde-navn- maks 100 tegn'] ?? '');
        if (!empty($navn)) {
            update_field('kunnskapskilde_navn', sanitize_text_field($navn), $post_id);
        }

        // Short description
        $beskrivelse = trim($row['Kunnskapskilde - kort beskrivelse - maks 250 tegn'] ?? '');
        if (!empty($beskrivelse)) {
            update_field('kort_beskrivelse', sanitize_textarea_field($beskrivelse), $post_id);
        }

        // External link
        $link = trim($row['Link'] ?? '');
        if (!empty($link)) {
            update_field('ekstern_lenke', esc_url_raw($link), $post_id);
        }

        // SharePoint link
        $sp_link = trim($row['Link til lukket datarom, Sharepoint etc'] ?? '');
        if (!empty($sp_link)) {
            update_field('sharepoint_lenke', esc_url_raw($sp_link), $post_id);
        }

        // Publisher
        $utgiver = trim($row['Utgiver'] ?? '');
        if (!empty($utgiver)) {
            update_field('utgiver', sanitize_text_field($utgiver), $post_id);
        }

        // Version
        $versjon = trim($row['Versjon'] ?? '');
        if (!empty($versjon)) {
            update_field('versjon', sanitize_text_field($versjon), $post_id);
        }

        // Language
        $spraak = trim($row['Språk'] ?? '');
        $spraak_map = [
            'Norsk' => 'norsk',
            'Engelsk' => 'engelsk',
            'Svensk' => 'svensk',
            'Dansk' => 'dansk',
        ];
        if (!empty($spraak)) {
            update_field('spraak', $spraak_map[$spraak] ?? 'norsk', $post_id);
        }

        // Year
        $aar = trim($row['År (antatt)'] ?? '');
        $aar_map = [
            'eldre enn 2022' => 'eldre',
        ];
        if (!empty($aar)) {
            $aar_value = $aar_map[strtolower($aar)] ?? $aar;
            update_field('utgivelsesaar', sanitize_text_field($aar_value), $post_id);
        }

        // Kildetype
        $kildetype = trim($row['Type kilde/ressurs'] ?? '');
        $kildetype_map = [
            'Veiledning/metodikk' => 'veiledning',
            'Forskrift (norsk lov)' => 'forskrift_norsk',
            'Forordning (EU/EØS)' => 'forordning_eu',
            'Annet (tjeneste, webside etc.)' => 'annet',
            'Standard (ISO, NS, etc.)' => 'standard',
            'Mal/Template' => 'mal',
            'Forskningsrapport' => 'forskningsrapport',
            'Casestudie' => 'casestudie',
            'Opplæringsmateriell' => 'opplaering',
            'Verktøydokumentasjon' => 'dokumentasjon',
            'Nettressurs/Database' => 'nettressurs',
        ];
        if (!empty($kildetype)) {
            update_field('kildetype', $kildetype_map[$kildetype] ?? 'annet', $post_id);
        }

        // Geografisk gyldighet
        $geo = trim($row['Geografisk gyldighet'] ?? '');
        $geo_map = [
            'Nasjonalt/Norsk' => 'nasjonalt',
            'Nordisk' => 'nordisk',
            'Europeisk' => 'europeisk',
            'Internasjonalt' => 'internasjonalt',
        ];
        if (!empty($geo)) {
            update_field('geografisk_gyldighet', $geo_map[$geo] ?? 'nasjonalt', $post_id);
        }

        // Dataformat
        $dataformat = trim($row['Dataform(at)'] ?? '');
        $dataformat_map = [
            'PDF-dokument' => 'pdf',
            'Web-innhold - åpent' => 'web_aapent',
            'Web-innhold - lukket/betalt' => 'web_lukket',
            'Åpent API' => 'api',
            'IFC-fil' => 'ifc',
            'Database/register' => 'database',
        ];
        if (!empty($dataformat)) {
            update_field('dataformat', $dataformat_map[$dataformat] ?? 'annet', $post_id);
        }

        // Registered by user
        if ($wp_user) {
            update_field('registrert_av', $wp_user->ID, $post_id);
        }

        // Owner: Verdinettverk AS
        update_field('tilknyttet_bedrift', $verdinettverk_id, $post_id);
    }

    /**
     * Save temagruppe taxonomy terms for kunnskapskilde
     */
    private function save_kunnskapskilde_temagrupper($post_id, $row) {
        $relevant_for = trim($row['Relevant for (flervalg)'] ?? '');
        if (empty($relevant_for)) {
            return;
        }

        // Map FF values to taxonomy slugs
        $temagruppe_map = [
            'ByggesaksBIM' => 'byggesaksbim',
            'ProsjektBIM (prosjektering og bygging)' => 'prosjektbim',
            'EiendomsBIM' => 'eiendomsbim',
            'SirkBIM (ombruk)' => 'sirkbim',
            'BIMTech (KI m.m.)' => 'bimtech',
            'MiljøBIM' => 'miljobim',
        ];

        $parts = array_map('trim', explode(',', $relevant_for));
        $slugs = [];

        foreach ($parts as $part) {
            if (isset($temagruppe_map[$part])) {
                $slug = $temagruppe_map[$part];

                // Ensure term exists
                $term = get_term_by('slug', $slug, 'temagruppe');
                if (!$term) {
                    wp_insert_term($part, 'temagruppe', ['slug' => $slug]);
                }

                $slugs[] = $slug;
            }
        }

        if (!empty($slugs)) {
            wp_set_object_terms($post_id, $slugs, 'temagruppe');
            WP_CLI::log("  → Temagrupper: " . implode(', ', $slugs));
        }
    }

    /**
     * Download and attach logo as featured image
     */
    private function attach_logo($post_id, $logo_url, $title, $dry_run) {
        if ($dry_run) {
            WP_CLI::log("  → Logo: {$logo_url} (dry run)");
            return;
        }

        // Download image
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url($logo_url);
        if (is_wp_error($tmp)) {
            WP_CLI::warning("  → Logo download failed: " . $tmp->get_error_message());
            return;
        }

        // Get filename from URL
        $filename = basename(parse_url($logo_url, PHP_URL_PATH));
        if (empty($filename)) {
            $filename = sanitize_file_name($title) . '.png';
        }

        $file_array = [
            'name' => $filename,
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload($file_array, $post_id, $title);

        if (is_wp_error($attachment_id)) {
            WP_CLI::warning("  → Logo attach failed: " . $attachment_id->get_error_message());
            @unlink($tmp);
            return;
        }

        set_post_thumbnail($post_id, $attachment_id);
        WP_CLI::log("  → Logo attached (attachment {$attachment_id})");
    }

    /**
     * Import Arrangement data from Formidable Forms CSV export
     *
     * Creates new arrangement CPT posts from historical FF entries.
     *
     * ## OPTIONS
     *
     * <csv-file>
     * : Path to the Formidable Forms CSV export file
     *
     * [--dry-run]
     * : Run without making changes, just show what would be imported
     *
     * [--status=<status>]
     * : Post status for imported entries (default: publish)
     *
     * ## EXAMPLES
     *
     *     wp bimverdi arrangement-import /path/to/arrangementer.csv --dry-run
     *     wp bimverdi arrangement-import /path/to/arrangementer.csv
     *
     * @param array $args       Positional arguments
     * @param array $assoc_args Named arguments
     */
    public function arrangement_import($args, $assoc_args) {
        $csv_file = $args[0];
        $dry_run = isset($assoc_args['dry-run']);
        $post_status = $assoc_args['status'] ?? 'publish';

        if (!file_exists($csv_file)) {
            WP_CLI::error("CSV file not found: {$csv_file}");
        }

        WP_CLI::log("=== BIM Verdi Arrangement Import (Formidable Forms) ===");
        WP_CLI::log("CSV: {$csv_file}");
        WP_CLI::log("Mode: " . ($dry_run ? "DRY RUN" : "LIVE"));
        WP_CLI::log("Post status: {$post_status}");
        WP_CLI::log("");

        // Read CSV
        $csv_data = $this->read_csv($csv_file);
        if (empty($csv_data)) {
            WP_CLI::error("No data found in CSV");
        }

        WP_CLI::log("Found " . count($csv_data) . " entries in CSV");
        WP_CLI::log("");

        // Stats
        $stats = [
            'processed' => 0,
            'created' => 0,
            'skipped_duplicate' => 0,
            'skipped_invalid' => 0,
            'skipped_not_publishable' => 0,
            'errors' => 0,
        ];

        // Mapping tables
        $type_mapping = [
            'Fysisk' => 'fysisk',
            'Fysisk med video-overføring' => 'hybrid',
            'Nettbasert' => 'digitalt',
        ];

        $arrangementstype_mapping = [
            'Digital Arena på Bygg Reis Deg' => null,
            'Deltakerforum (åpent)' => 'deltakerforum',
            'Deltakerforum (lukket)' => 'deltakerforum',
            'Partnerarrangement' => 'partnerarrangement',
            'PilotVerksted' => 'workshop',
            'PilotSeminar' => 'seminar',
            'Nytt & Nyttig' => 'webinar',
        ];

        $temagruppe_mapping = [
            'Varelogistikk (TG01)' => 'prosjektbim',
            'Industrialisering og avfallsforebygging (TG03)' => 'sirkbim',
            'Digital Tvilling og drift (TG02)' => 'eiendomsbim',
            'MiljøBIM (klimagassberegninger fra BIM)' => 'miljobim',
            'EiendomsBIM (digital tvilling, drift)' => 'eiendomsbim',
            'ProsjektBIM (logistikk, egenskaper i IFC)' => 'prosjektbim',
            'ByggesaksBIM (byggesøknader, GIS, visualisering)' => 'byggesaksbim',
            'ResirkuleringsBIM (BIM som verdibank etc.)' => 'sirkbim',
            'BIMtech (muliggjørende teknologier...)' => 'bimtech',
            // Partial matches for corrupted data
            'drift)' => 'eiendomsbim',
            'logistikk' => 'prosjektbim',
        ];

        foreach ($csv_data as $row) {
            $stats['processed']++;

            // Skip if not active entry
            $entry_status = trim($row['Entry Status'] ?? '0');
            if ($entry_status !== '0') {
                $stats['skipped_invalid']++;
                continue;
            }

            // Skip if not publishable
            $can_publish = trim($row['Kan arrangementet publiseres slik det er beskrevet?'] ?? '');
            if ($can_publish === 'Nei - ikke enda') {
                $stats['skipped_not_publishable']++;
                continue;
            }

            $title = trim($row['Tittel på innlegg eller arrangement'] ?? '');
            $entry_id = trim($row['Entry ID'] ?? $row['Entry Id'] ?? '');

            // Skip if empty title
            if (empty($title)) {
                $stats['skipped_invalid']++;
                continue;
            }

            // Duplicate check via ff_entry_id
            if (!empty($entry_id)) {
                $existing = get_posts([
                    'post_type' => 'arrangement',
                    'post_status' => 'any',
                    'meta_key' => 'ff_entry_id',
                    'meta_value' => $entry_id,
                    'posts_per_page' => 1,
                    'fields' => 'ids',
                ]);

                if (!empty($existing)) {
                    WP_CLI::log("[DUPLICATE] Entry ID {$entry_id}: \"{$title}\"");
                    $stats['skipped_duplicate']++;
                    continue;
                }
            }

            // Date validation
            $fra_dato = trim($row['Fra dato'] ?? '');
            if (empty($fra_dato) || !strtotime($fra_dato)) {
                WP_CLI::warning("[INVALID DATE] \"{$title}\" - Fra dato: {$fra_dato}");
                $stats['skipped_invalid']++;
                continue;
            }

            // Determine status based on date
            $status_toggle = (strtotime($fra_dato) < strtotime('today')) ? 'tidligere' : 'kommende';

            // Map arrangement_type
            $fysisk_digital = trim($row['Fysisk eller nettbasert gjennomføring'] ?? '');
            $arrangement_type = $type_mapping[$fysisk_digital] ?? 'digitalt';

            // Build post_content
            $content_parts = [];
            $kort_beskrivelse = trim($row['Kort beskrivelse - for kalenderoppføring'] ?? '');
            $agenda = trim($row['Agenda'] ?? '');
            if (!empty($kort_beskrivelse)) {
                $content_parts[] = wp_kses_post($kort_beskrivelse);
            }
            if (!empty($agenda)) {
                $content_parts[] = "\n\n<!-- wp:heading -->\n<h2>Agenda</h2>\n<!-- /wp:heading -->\n\n" . wp_kses_post($agenda);
            }
            $post_content = implode('', $content_parts);

            // Build sted
            $sted_parts = array_filter([
                trim($row['Konferansested og møterom'] ?? ''),
                trim($row['Adresse - Line 1'] ?? ''),
            ]);
            $sted_adresse = implode(', ', $sted_parts);
            $sted_by = trim($row['Adresse - By'] ?? '');

            // Opptak URL (try Teams folder first, then external)
            $opptak_url = trim($row['Link til Teams-folder med opptak og presentasjoner (for deltakere i BIM Verdi)'] ?? '');
            if (empty($opptak_url)) {
                $opptak_url = trim($row['Link til opptak-del 1  - eksterne kilder'] ?? '');
            }

            WP_CLI::log("[CREATE] \"{$title}\" ({$fra_dato}) [{$status_toggle}]");

            if ($dry_run) {
                $stats['created']++;
                continue;
            }

            // Create post
            $post_id = wp_insert_post([
                'post_type' => 'arrangement',
                'post_title' => sanitize_text_field($title),
                'post_content' => $post_content,
                'post_status' => $post_status,
                'post_date' => $row['Timestamp'] ?? current_time('mysql'),
            ]);

            if (is_wp_error($post_id)) {
                WP_CLI::warning("  Failed to create: " . $post_id->get_error_message());
                $stats['errors']++;
                continue;
            }

            // Save ACF fields
            update_field('arrangement_status_toggle', $status_toggle, $post_id);
            update_field('arrangement_type', $arrangement_type, $post_id);
            update_field('arrangement_dato', $fra_dato, $post_id);
            update_field('arrangement_status', 'planlagt', $post_id);

            $tidspunkt_start = trim($row['Starter kl'] ?? '');
            if (!empty($tidspunkt_start)) {
                update_field('tidspunkt_start', $tidspunkt_start, $post_id);
            }

            $til_dato = trim($row['Til dato'] ?? '');
            if (!empty($til_dato) && $til_dato !== $fra_dato) {
                update_field('slutt_dato', $til_dato, $post_id);
            }

            $tidspunkt_slutt = trim($row['Slutter kl.'] ?? '');
            if (!empty($tidspunkt_slutt)) {
                update_field('tidspunkt_slutt', $tidspunkt_slutt, $post_id);
            }

            $formal = trim($row['Formål med innlegget/arrangementet'] ?? '');
            if (!empty($formal)) {
                update_field('formal_tema', sanitize_textarea_field($formal), $post_id);
            }

            $malgruppe = trim($row['Målgrupper for arrangementet'] ?? '');
            if (!empty($malgruppe)) {
                update_field('passer_for', sanitize_text_field($malgruppe), $post_id);
            }

            if (!empty($sted_adresse)) {
                update_field('sted_adresse', sanitize_text_field($sted_adresse), $post_id);
            }

            if (!empty($sted_by)) {
                update_field('sted_by', sanitize_text_field($sted_by), $post_id);
            }

            // Arrangør
            $arrangor_parts = array_filter([
                trim($row['Teknisk arrangør'] ?? ''),
                trim($row['Med-arrangør/partner(e)'] ?? ''),
            ]);
            if (!empty($arrangor_parts)) {
                update_field('arrangor', sanitize_text_field(implode(' / ', $arrangor_parts)), $post_id);
            }

            // Resources (only for past events)
            if ($status_toggle === 'tidligere') {
                if (!empty($opptak_url)) {
                    update_field('opptak_url', esc_url_raw($opptak_url), $post_id);
                }
                $dok_url = trim($row['Link til pres., PPT etc.'] ?? '');
                if (!empty($dok_url)) {
                    update_field('dokumentasjon_url', esc_url_raw($dok_url), $post_id);
                }
            }

            // Påmelding
            $pamelding_url = trim($row['Ekstern link til påmelding'] ?? '');
            if (!empty($pamelding_url)) {
                update_field('pamelding_url', esc_url_raw($pamelding_url), $post_id);
            }

            // Store FF Entry ID for duplicate tracking
            if (!empty($entry_id)) {
                update_post_meta($post_id, 'ff_entry_id', $entry_id);
            }

            // Taxonomies: arrangementstype
            $type_arr = trim($row['Type arrangement'] ?? '');
            if (!empty($type_arr)) {
                $type_parts = array_map('trim', explode(',', $type_arr));
                $type_slugs = [];
                foreach ($type_parts as $part) {
                    if (isset($arrangementstype_mapping[$part]) && $arrangementstype_mapping[$part] !== null) {
                        $slug = $arrangementstype_mapping[$part];
                        $term = get_term_by('slug', $slug, 'arrangementstype');
                        if ($term) {
                            $type_slugs[] = $slug;
                        }
                    }
                }
                if (!empty($type_slugs)) {
                    wp_set_object_terms($post_id, $type_slugs, 'arrangementstype');
                }
            }

            // Taxonomies: temagruppe
            $temagrupper = trim($row['Temagrupper som har størst relevans til arrangementet'] ?? '');
            if (!empty($temagrupper)) {
                $tg_parts = array_map('trim', explode(',', $temagrupper));
                $tg_slugs = [];
                foreach ($tg_parts as $part) {
                    // Try exact match first
                    if (isset($temagruppe_mapping[$part])) {
                        $tg_slugs[] = $temagruppe_mapping[$part];
                    } else {
                        // Try partial match for corrupted data
                        foreach ($temagruppe_mapping as $key => $slug) {
                            if (stripos($part, $key) !== false || stripos($key, $part) !== false) {
                                $tg_slugs[] = $slug;
                                break;
                            }
                        }
                    }
                }
                $tg_slugs = array_unique($tg_slugs);
                if (!empty($tg_slugs)) {
                    wp_set_object_terms($post_id, $tg_slugs, 'temagruppe');
                }
            }

            WP_CLI::log("  → Post ID: {$post_id}");
            $stats['created']++;
        }

        // Summary
        WP_CLI::log("");
        WP_CLI::log("=== Import Summary ===");
        WP_CLI::log("Processed:      {$stats['processed']}");
        WP_CLI::log("Created:        {$stats['created']}");
        WP_CLI::log("Duplicates:     {$stats['skipped_duplicate']}");
        WP_CLI::log("Invalid:        {$stats['skipped_invalid']}");
        WP_CLI::log("Not publishable:{$stats['skipped_not_publishable']}");
        WP_CLI::log("Errors:         {$stats['errors']}");

        if ($dry_run) {
            WP_CLI::warning("DRY RUN - No changes were made");
        } else {
            WP_CLI::success("Import completed!");
        }
    }

    /**
     * Import Arrangement data from live CPT CSV export
     *
     * Imports events from the live bimverdi.no CPT export.
     * Handles YYYYMMDD date format and PHP serialized arrays.
     *
     * ## OPTIONS
     *
     * <csv-file>
     * : Path to the live CPT CSV export file
     *
     * [--dry-run]
     * : Run without making changes, just show what would be imported
     *
     * [--status=<status>]
     * : Post status for imported entries (default: publish)
     *
     * ## EXAMPLES
     *
     *     wp bimverdi arrangement-import-cpt /path/to/cpt-export.csv --dry-run
     *     wp bimverdi arrangement-import-cpt /path/to/cpt-export.csv
     *
     * @param array $args       Positional arguments
     * @param array $assoc_args Named arguments
     */
    public function arrangement_import_cpt($args, $assoc_args) {
        $csv_file = $args[0];
        $dry_run = isset($assoc_args['dry-run']);
        $post_status = $assoc_args['status'] ?? 'publish';

        if (!file_exists($csv_file)) {
            WP_CLI::error("CSV file not found: {$csv_file}");
        }

        WP_CLI::log("=== BIM Verdi Arrangement Import (Live CPT) ===");
        WP_CLI::log("CSV: {$csv_file}");
        WP_CLI::log("Mode: " . ($dry_run ? "DRY RUN" : "LIVE"));
        WP_CLI::log("Post status: {$post_status}");
        WP_CLI::log("");

        // Read CSV
        $csv_data = $this->read_csv($csv_file);
        if (empty($csv_data)) {
            WP_CLI::error("No data found in CSV");
        }

        WP_CLI::log("Found " . count($csv_data) . " entries in CSV");
        WP_CLI::log("");

        // Stats
        $stats = [
            'processed' => 0,
            'created' => 0,
            'skipped_duplicate' => 0,
            'skipped_test' => 0,
            'skipped_invalid' => 0,
            'errors' => 0,
        ];

        // Skip test entries
        $skip_titles = ['map test', 'NEW-EVENT', 'test', 'KLADD:'];

        foreach ($csv_data as $row) {
            $stats['processed']++;

            $live_id = trim($row['ID'] ?? '');
            $title = trim($row['post_title'] ?? '');

            // Skip test entries
            $is_test = false;
            foreach ($skip_titles as $skip) {
                if (stripos($title, $skip) !== false) {
                    $is_test = true;
                    break;
                }
            }
            if ($is_test) {
                WP_CLI::log("[SKIP TEST] \"{$title}\"");
                $stats['skipped_test']++;
                continue;
            }

            // Skip empty title
            if (empty($title)) {
                $stats['skipped_invalid']++;
                continue;
            }

            // Parse date (YYYYMMDD format)
            $dato_start_raw = trim($row['dato_start'] ?? '');
            if (empty($dato_start_raw) || strlen($dato_start_raw) !== 8) {
                WP_CLI::warning("[INVALID DATE] \"{$title}\" - dato_start: {$dato_start_raw}");
                $stats['skipped_invalid']++;
                continue;
            }

            // Convert YYYYMMDD to Y-m-d
            $fra_dato = substr($dato_start_raw, 0, 4) . '-' . substr($dato_start_raw, 4, 2) . '-' . substr($dato_start_raw, 6, 2);

            // Clean title (remove date prefix like "20251211 ")
            $clean_title = preg_replace('/^\d{8}\s+/', '', $title);

            // Duplicate check: title + date
            $existing = get_posts([
                'post_type' => 'arrangement',
                'post_status' => 'any',
                'title' => $clean_title,
                'meta_query' => [
                    [
                        'key' => 'arrangement_dato',
                        'value' => $fra_dato,
                    ],
                ],
                'posts_per_page' => 1,
                'fields' => 'ids',
            ]);

            if (!empty($existing)) {
                WP_CLI::log("[DUPLICATE] \"{$clean_title}\" ({$fra_dato})");
                $stats['skipped_duplicate']++;
                continue;
            }

            // Also check by live_cpt_id
            $existing_by_id = get_posts([
                'post_type' => 'arrangement',
                'post_status' => 'any',
                'meta_key' => 'live_cpt_id',
                'meta_value' => $live_id,
                'posts_per_page' => 1,
                'fields' => 'ids',
            ]);

            if (!empty($existing_by_id)) {
                WP_CLI::log("[DUPLICATE] Live ID {$live_id}: \"{$clean_title}\"");
                $stats['skipped_duplicate']++;
                continue;
            }

            // Determine status
            $avsluttet = trim($row['avsluttet'] ?? '0');
            $status_toggle = ($avsluttet === '1') ? 'tidligere' : 'kommende';

            // Parse sted (PHP serialized Google Maps array)
            $sted_raw = trim($row['sted'] ?? '');
            $sted_adresse = '';
            if (!empty($sted_raw) && $sted_raw !== 'NULL') {
                $sted_data = @unserialize($sted_raw);
                if (is_array($sted_data) && isset($sted_data['address'])) {
                    $sted_adresse = $sted_data['address'];
                }
            }

            // Parse adgang (PHP serialized array)
            $adgang_raw = trim($row['adgang'] ?? '');
            $adgang = 'alle';
            if (!empty($adgang_raw) && $adgang_raw !== 'NULL') {
                $adgang_data = @unserialize($adgang_raw);
                if (is_array($adgang_data)) {
                    // Map live values to v2 ACF values
                    if (in_array('åpent', $adgang_data)) {
                        $adgang = 'alle';
                    } elseif (in_array('deltakere i BIM Verdi og arrangementspartnere', $adgang_data)) {
                        $adgang = 'deltakere';
                    } elseif (in_array('registrerte personkontakter', $adgang_data)) {
                        $adgang = 'medlemmer';
                    }
                }
            }

            // Arrangør
            $arrangor = trim($row['arrangor'] ?? '');

            // Påmelding URL (may contain shortcode)
            $pamelding = trim($row['pamelding'] ?? '');
            $pamelding_url = '';
            if (!empty($pamelding) && strpos($pamelding, '[formidable') === false) {
                // Only use if it's an actual URL, not a shortcode
                if (filter_var($pamelding, FILTER_VALIDATE_URL)) {
                    $pamelding_url = $pamelding;
                }
            }

            WP_CLI::log("[CREATE] \"{$clean_title}\" ({$fra_dato}) [{$status_toggle}]");

            if ($dry_run) {
                $stats['created']++;
                continue;
            }

            // Create post
            $post_id = wp_insert_post([
                'post_type' => 'arrangement',
                'post_title' => sanitize_text_field($clean_title),
                'post_content' => '',
                'post_status' => $post_status,
            ]);

            if (is_wp_error($post_id)) {
                WP_CLI::warning("  Failed to create: " . $post_id->get_error_message());
                $stats['errors']++;
                continue;
            }

            // Save ACF fields
            update_field('arrangement_status_toggle', $status_toggle, $post_id);
            update_field('arrangement_type', 'digitalt', $post_id); // Default, type column often empty
            update_field('arrangement_dato', $fra_dato, $post_id);
            update_field('arrangement_status', 'planlagt', $post_id);
            update_field('adgang', $adgang, $post_id);

            // Time
            $tid_start = trim($row['tid_start'] ?? '');
            if (!empty($tid_start) && $tid_start !== 'NULL') {
                // May be H:i or full datetime
                if (preg_match('/^\d{2}:\d{2}$/', $tid_start)) {
                    update_field('tidspunkt_start', $tid_start, $post_id);
                }
            }

            $tid_slutt = trim($row['tid_slutt'] ?? '');
            if (!empty($tid_slutt) && $tid_slutt !== 'NULL') {
                if (preg_match('/^\d{2}:\d{2}$/', $tid_slutt)) {
                    update_field('tidspunkt_slutt', $tid_slutt, $post_id);
                }
            }

            // End date
            $dato_slutt_raw = trim($row['dato_slutt'] ?? '');
            if (!empty($dato_slutt_raw) && $dato_slutt_raw !== 'NULL' && strlen($dato_slutt_raw) === 8 && $dato_slutt_raw !== $dato_start_raw) {
                $slutt_dato = substr($dato_slutt_raw, 0, 4) . '-' . substr($dato_slutt_raw, 4, 2) . '-' . substr($dato_slutt_raw, 6, 2);
                update_field('slutt_dato', $slutt_dato, $post_id);
            }

            if (!empty($sted_adresse)) {
                update_field('sted_adresse', sanitize_text_field($sted_adresse), $post_id);
            }

            if (!empty($arrangor)) {
                update_field('arrangor', sanitize_text_field($arrangor), $post_id);
            }

            if (!empty($pamelding_url)) {
                update_field('pamelding_url', esc_url_raw($pamelding_url), $post_id);
            }

            // Store live CPT ID for tracking
            update_post_meta($post_id, 'live_cpt_id', $live_id);

            // Taxonomies: temagruppe (PHP serialized term IDs - skip for now, IDs don't match)
            // Would need term ID mapping from live to local

            WP_CLI::log("  → Post ID: {$post_id}");
            $stats['created']++;
        }

        // Summary
        WP_CLI::log("");
        WP_CLI::log("=== Import Summary ===");
        WP_CLI::log("Processed:  {$stats['processed']}");
        WP_CLI::log("Created:    {$stats['created']}");
        WP_CLI::log("Duplicates: {$stats['skipped_duplicate']}");
        WP_CLI::log("Test posts: {$stats['skipped_test']}");
        WP_CLI::log("Invalid:    {$stats['skipped_invalid']}");
        WP_CLI::log("Errors:     {$stats['errors']}");

        if ($dry_run) {
            WP_CLI::warning("DRY RUN - No changes were made");
        } else {
            WP_CLI::success("Import completed!");
        }
    }
}

// Register WP-CLI command
WP_CLI::add_command('bimverdi', 'BIM_Verdi_CLI_Commands');
