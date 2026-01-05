<?php
/**
 * WP-CLI Migration Command: Formidable Forms til Gravity Forms + v2 struktur
 * 
 * Bruk:
 * wp bimverdi migrate-users /path/to/wp_usermeta_export.sql --dry-run
 * wp bimverdi migrate-users /path/to/wp_usermeta_export.sql --execute
 * 
 * @package BIM_Verdi_Core
 */

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

class BIM_Verdi_Migration_Command {
    
    /**
     * Migrer brukere fra Formidable Forms til v2 struktur
     * 
     * ## OPTIONS
     * 
     * <file>
     * : Sti til SQL-eksport fil (wp_usermeta_export.sql)
     * 
     * [--dry-run]
     * : Kjør uten å gjøre endringer (preview)
     * 
     * [--execute]
     * : Kjør faktisk migrasjon
     * 
     * [--media-path=<path>]
     * : Sti til nedlastede Formidable bilder
     * 
     * [--limit=<number>]
     * : Test kun X antall brukere
     * 
     * ## EXAMPLES
     * 
     *     wp bimverdi migrate-users migration-data/wp_usermeta_export.sql --dry-run
     *     wp bimverdi migrate-users migration-data/wp_usermeta_export.sql --execute --media-path=shared-context/migration-media/
     * 
     * @when after_wp_load
     */
    public function migrate_users($args, $assoc_args) {
        
        $file = $args[0];
        $dry_run = isset($assoc_args['dry-run']);
        $execute = isset($assoc_args['execute']);
        $media_path = isset($assoc_args['media-path']) ? $assoc_args['media-path'] : WP_CONTENT_DIR . '/shared-context/migration-media/';
        $limit = isset($assoc_args['limit']) ? intval($assoc_args['limit']) : 0;
        
        // Valider parametere
        if (!$dry_run && !$execute) {
            WP_CLI::error('Du må spesifisere enten --dry-run eller --execute');
        }
        
        if (!file_exists($file)) {
            WP_CLI::error("Fil ikke funnet: $file");
        }
        
        WP_CLI::log(WP_CLI::colorize('%Y=== BIM Verdi Brukermigrasjon ===%n'));
        WP_CLI::log('Modus: ' . ($dry_run ? 'DRY RUN (ingen endringer)' : 'EXECUTE (faktisk migrasjon)'));
        WP_CLI::log('Fil: ' . $file);
        WP_CLI::log('Media-path: ' . $media_path);
        WP_CLI::log('');
        
        // Parse SQL-fil og bygg usermeta array
        WP_CLI::log('Parser SQL-fil...');
        $usermeta_data = $this->parse_usermeta_sql($file);
        WP_CLI::success(sprintf('Lastet %d usermeta records', count($usermeta_data)));
        
        // Grupper usermeta per bruker
        $users_meta = array();
        foreach ($usermeta_data as $row) {
            $user_id = $row['user_id'];
            if (!isset($users_meta[$user_id])) {
                $users_meta[$user_id] = array();
            }
            $users_meta[$user_id][$row['meta_key']] = $row['meta_value'];
        }
        
        WP_CLI::log(sprintf('Funnet %d unike brukere med metadata', count($users_meta)));
        WP_CLI::log('');
        
        // Statistikk
        $stats = array(
            'total' => 0,
            'with_ff_data' => 0,
            'foretak_created' => 0,
            'foretak_linked' => 0,
            'images_imported' => 0,
            'errors' => 0
        );
        
        // Progress bar
        $progress = WP_CLI\Utils\make_progress_bar('Migrerer brukere', count($users_meta));
        
        $counter = 0;
        foreach ($users_meta as $old_user_id => $meta) {
            
            $counter++;
            if ($limit > 0 && $counter > $limit) {
                break;
            }
            
            $stats['total']++;
            
            try {
                // Sjekk om bruker har FF-data
                $has_ff_data = $this->has_formidable_data($meta);
                
                if ($has_ff_data) {
                    $stats['with_ff_data']++;
                    
                    // Migrer bruker med FF-data
                    $result = $this->migrate_user_with_ff_data($old_user_id, $meta, $media_path, $dry_run);
                    
                    if ($result['foretak_created']) {
                        $stats['foretak_created']++;
                    }
                    if ($result['foretak_linked']) {
                        $stats['foretak_linked']++;
                    }
                    if ($result['images_imported']) {
                        $stats['images_imported'] += $result['images_imported'];
                    }
                    
                } else {
                    // Migrer bruker uten FF-data (basic import)
                    $this->migrate_user_basic($old_user_id, $meta, $dry_run);
                }
                
            } catch (Exception $e) {
                $stats['errors']++;
                WP_CLI::warning(sprintf('Feil ved migrasjon av bruker %d: %s', $old_user_id, $e->getMessage()));
            }
            
            $progress->tick();
        }
        
        $progress->finish();
        
        // Vis statistikk
        WP_CLI::log('');
        WP_CLI::log(WP_CLI::colorize('%G=== Migrasjonsstatistikk ===%n'));
        WP_CLI::log(sprintf('Totalt brukere prosessert: %d', $stats['total']));
        WP_CLI::log(sprintf('  - Med FF-data: %d', $stats['with_ff_data']));
        WP_CLI::log(sprintf('  - Uten FF-data: %d', $stats['total'] - $stats['with_ff_data']));
        WP_CLI::log(sprintf('Foretak opprettet: %d', $stats['foretak_created']));
        WP_CLI::log(sprintf('Foretak-koblinger satt: %d', $stats['foretak_linked']));
        WP_CLI::log(sprintf('Bilder importert: %d', $stats['images_imported']));
        WP_CLI::log(sprintf('Feil: %d', $stats['errors']));
        
        if ($dry_run) {
            WP_CLI::success('DRY RUN fullført - ingen endringer gjort');
        } else {
            WP_CLI::success('Migrasjon fullført!');
        }
    }
    
    /**
     * Parse SQL-fil med usermeta
     */
    private function parse_usermeta_sql($file) {
        $content = file_get_contents($file);
        $data = array();
        
        // Enkel CSV parsing (hvis fil er CSV)
        if (pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
            $handle = fopen($file, 'r');
            $headers = fgetcsv($handle); // Skip header row
            
            while (($row = fgetcsv($handle)) !== false) {
                $data[] = array(
                    'umeta_id' => $row[0],
                    'user_id' => $row[1],
                    'meta_key' => $row[2],
                    'meta_value' => $row[3]
                );
            }
            fclose($handle);
        }
        
        // SQL parsing (hvis fil er .sql)
        else if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            // Parse INSERT INTO statements - støtter både wp_usermeta og blgrd_sdfsusermeta
            preg_match_all("/INSERT INTO `?(?:wp_usermeta|blgrd_sdfsusermeta)`? .*? VALUES\s*\((.*?)\);/is", $content, $matches);
            
            foreach ($matches[1] as $values_string) {
                // Split på komma, men respekter quotes
                if (preg_match_all("/\(([^)]+)\)/", $values_string, $row_matches)) {
                    foreach ($row_matches[1] as $row) {
                        $values = str_getcsv($row, ',', "'");
                        if (count($values) >= 4) {
                            $data[] = array(
                                'umeta_id' => isset($values[0]) ? trim($values[0], " '\"\t\n\r\0\x0B") : '',
                                'user_id' => isset($values[1]) ? trim($values[1], " '\"\t\n\r\0\x0B") : '',
                                'meta_key' => isset($values[2]) ? trim($values[2], " '\"\t\n\r\0\x0B") : '',
                                'meta_value' => isset($values[3]) ? trim($values[3], " '\"\t\n\r\0\x0B") : ''
                            );
                        }
                    }
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Sjekk om bruker har Formidable Forms data
     */
    private function has_formidable_data($meta) {
        $ff_keys = array(
            'foretak_navn',
            'user_foretak',
            'org_nummer',
            'foretak_orgnr',
            'fornavn_kontakt',
            'etternavn_kontakt',
            'epost_kontakt'
        );
        
        foreach ($ff_keys as $key) {
            if (isset($meta[$key]) && !empty($meta[$key])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Migrer bruker MED Formidable Forms data
     */
    private function migrate_user_with_ff_data($old_user_id, $meta, $media_path, $dry_run) {
        
        $result = array(
            'foretak_created' => false,
            'foretak_linked' => false,
            'images_imported' => 0
        );
        
        // Ekstraher FF-data (støtter både gamle og nye felt-navn)
        $org_nummer = isset($meta['org_nummer']) ? $meta['org_nummer'] : (isset($meta['foretak_orgnr']) ? $meta['foretak_orgnr'] : '');
        $foretak_navn = isset($meta['foretak_navn']) ? $meta['foretak_navn'] : (isset($meta['user_foretak']) ? $meta['user_foretak'] : '');
        $fornavn = isset($meta['fornavn_kontakt']) ? $meta['fornavn_kontakt'] : '';
        $etternavn = isset($meta['etternavn_kontakt']) ? $meta['etternavn_kontakt'] : '';
        $mellomnavn = isset($meta['mellomnavn_kontakt']) ? $meta['mellomnavn_kontakt'] : '';
        $epost = isset($meta['epost_kontakt']) ? $meta['epost_kontakt'] : '';
        $mobil = isset($meta['mobilnr_kontakt']) ? $meta['mobilnr_kontakt'] : '';
        $tittel = isset($meta['tittel_kontakt']) ? $meta['tittel_kontakt'] : '';
        $linkedin = isset($meta['linkedin_kontakt']) ? $meta['linkedin_kontakt'] : (isset($meta['linkedin_foretak_url']) ? $meta['linkedin_foretak_url'] : '');
        $hjemmeside = isset($meta['hjemmeside_kontakt']) ? $meta['hjemmeside_kontakt'] : (isset($meta['foretak_url']) ? $meta['foretak_url'] : '');
        
        if (empty($org_nummer) || empty($epost)) {
            throw new Exception('Mangler org.nummer eller e-post');
        }
        
        WP_CLI::log(sprintf('  Bruker %d: %s %s (%s) - Foretak: %s (%s)', 
            $old_user_id, $fornavn, $etternavn, $epost, $foretak_navn, $org_nummer));
        
        if ($dry_run) {
            WP_CLI::log('    [DRY RUN] Ville opprettet/linket foretak og oppdatert user meta');
            return $result;
        }
        
        // 1. Finn eller opprett WordPress-bruker
        $user = get_user_by('email', $epost);
        if (!$user) {
            // Opprett ny bruker
            $username = sanitize_user($epost);
            $user_id = wp_create_user($username, wp_generate_password(), $epost);
            
            if (is_wp_error($user_id)) {
                throw new Exception('Kunne ikke opprette bruker: ' . $user_id->get_error_message());
            }
            
            // Sett navn
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $fornavn,
                'last_name' => $etternavn,
                'display_name' => trim($fornavn . ' ' . $etternavn)
            ));
            
        } else {
            $user_id = $user->ID;
        }
        
        // 2. Oppdater ACF user fields
        update_field('phone', $mobil, 'user_' . $user_id);
        update_field('job_title', $tittel, 'user_' . $user_id);
        update_field('linkedin_url', $linkedin, 'user_' . $user_id);
        
        // 3. Finn eller opprett Foretak CPT
        $foretak_id = $this->find_or_create_foretak($org_nummer, $foretak_navn, $meta, $media_path);
        
        if ($foretak_id) {
            $result['foretak_created'] = true;
            
            // 4. Link user til foretak
            update_field('tilknyttet_foretak', $foretak_id, 'user_' . $user_id);
            update_field('foretak_rolle', 'eier', 'user_' . $user_id);
            $result['foretak_linked'] = true;
            
            // 5. Sett user som hovedkontakt på foretak
            update_field('hovedkontaktperson', $user_id, $foretak_id);
        }
        
        return $result;
    }
    
    /**
     * Migrer bruker UTEN FF-data (basic)
     */
    private function migrate_user_basic($old_user_id, $meta, $dry_run) {
        // Bare basic usermeta import
        // Implementeres kun hvis nødvendig
    }
    
    /**
     * Finn eller opprett Foretak CPT
     */
    private function find_or_create_foretak($org_nummer, $foretak_navn, $meta, $media_path) {
        
        // Sjekk om foretak finnes
        $existing_query = new WP_Query(array(
            'post_type' => 'foretak',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'organisasjonsnummer',
                    'value' => $org_nummer,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if ($existing_query->have_posts()) {
            return $existing_query->posts[0]->ID;
        }
        
        // Opprett nytt foretak
        $foretak_id = wp_insert_post(array(
            'post_type' => 'foretak',
            'post_title' => $foretak_navn,
            'post_status' => 'publish',
            'post_name' => sanitize_title($foretak_navn)
        ));
        
        if (is_wp_error($foretak_id)) {
            throw new Exception('Kunne ikke opprette foretak: ' . $foretak_id->get_error_message());
        }
        
        // Sett ACF-felt
        update_field('organisasjonsnummer', $org_nummer, $foretak_id);
        update_field('bedriftsnavn', $foretak_navn, $foretak_id);
        update_field('bedriftsbeskrivelse', isset($meta['Virksomhetsbeskrivelse']) ? $meta['Virksomhetsbeskrivelse'] : '', $foretak_id);
        update_field('adresse', isset($meta['Adresse']) ? $meta['Adresse'] : '', $foretak_id);
        update_field('webside', isset($meta['Nettside_For_Foretaket']) ? $meta['Nettside_For_Foretaket'] : '', $foretak_id);
        update_field('kontakt_epost', isset($meta['Epost_kontakt']) ? $meta['Epost_kontakt'] : '', $foretak_id);
        update_field('telefon', isset($meta['Mobilnr_kontakt']) ? $meta['Mobilnr_kontakt'] : '', $foretak_id);
        update_field('medlemsstatus', 'deltaker', $foretak_id);
        
        // Importer logo (hvis finnes)
        if (isset($meta['Logo_foretak']) && !empty($meta['Logo_foretak'])) {
            $logo_id = $this->import_image($meta['Logo_foretak'], $media_path);
            if ($logo_id) {
                update_field('logo', $logo_id, $foretak_id);
            }
        }
        
        return $foretak_id;
    }
    
    /**
     * Importer bilde fra URL til Media Library
     */
    private function import_image($url, $media_path) {
        
        // Ekstraher filename fra URL
        // https://bimverdi.no/wp-content/uploads/formidable/9/logo.png
        preg_match('/formidable\/(\d+)\/(.+)$/', $url, $matches);
        
        if (!$matches) {
            return false;
        }
        
        $form_id = $matches[1];
        $filename = $matches[2];
        $local_path = $media_path . 'formidable/' . $form_id . '/' . $filename;
        
        if (!file_exists($local_path)) {
            WP_CLI::warning("Bilde ikke funnet: $local_path");
            return false;
        }
        
        // Importer til WordPress
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $upload_dir = wp_upload_dir();
        $new_file = $upload_dir['path'] . '/' . $filename;
        
        copy($local_path, $new_file);
        
        $attachment = array(
            'post_mime_type' => mime_content_type($new_file),
            'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attach_id = wp_insert_attachment($attachment, $new_file);
        $attach_data = wp_generate_attachment_metadata($attach_id, $new_file);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        return $attach_id;
    }
}

WP_CLI::add_command('bimverdi', 'BIM_Verdi_Migration_Command');
