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
            if ($entry_status !== '0') {
                WP_CLI::log("[SKIP] Entry {$row['Entry Id']} - Draft/trashed (status: {$entry_status})");
                $stats['skipped']++;
                continue;
            }

            // Get org number
            $org_nr = $this->clean_org_number($row['Organisasjonsnummer'] ?? '');
            $company_name = trim($row['Foretaksnavn'] ?? '');

            if (empty($org_nr) && empty($company_name)) {
                WP_CLI::log("[SKIP] Entry {$row['Entry Id']} - No org number or name");
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
            WP_CLI::log("[MATCH] Entry {$row['Entry Id']} → Post {$foretak_id} \"{$foretak_title}\"");

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
        $field_configs = [
            'kort_beskrivelse' => [
                'csv_columns' => ['Kort om hvorfor vi deltar i BIM Verdi', 'Virksomhetsbeskrivelse - maks. 800 tegn'],
                'transform' => 'kort_beskrivelse',
            ],
            'bransje_rolle' => [
                'csv_columns' => ['Vår rolle/fag/bransje'],
                'transform' => 'bransje_rolle',
            ],
            'interesseomrader' => [
                'csv_columns' => ['Angi interesse for prosjekt og/eller temagruppe'],
                'transform' => 'interesseomrader',
            ],
            'kundetyper' => [
                'csv_columns' => ['Hvem er våre kunder'],
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
                'csv_columns' => ['YouTube-kanal - foretak'],
                'transform' => 'url',
            ],
            'twitter_url' => [
                'csv_columns' => ['X- foretak'],
                'transform' => 'url',
            ],
            'artikkel_lenke' => [
                'csv_columns' => ['Link til artikkel'],
                'transform' => 'url',
            ],
            'hashtag' => [
                'csv_columns' => ['Hashtag for LinkedIn'],
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
}

// Register WP-CLI command
WP_CLI::add_command('bimverdi', 'BIM_Verdi_CLI_Commands');
