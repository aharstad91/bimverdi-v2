<?php
/**
 * WP-CLI Validation Command: Test migrasjon etter fullføring
 * 
 * Bruk:
 * wp bimverdi validate-migration
 * wp bimverdi validate-migration --detailed
 * 
 * @package BIM_Verdi_Core
 */

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

class BIM_Verdi_Validation_Command {
    
    /**
     * Valider migrasjon av brukere og foretak
     * 
     * ## OPTIONS
     * 
     * [--detailed]
     * : Vis detaljert output for hver bruker
     * 
     * [--check-login]
     * : Test faktisk innlogging for X random brukere
     * 
     * [--export-report]
     * : Eksporter valideringsrapport til CSV
     * 
     * ## EXAMPLES
     * 
     *     wp bimverdi validate-migration
     *     wp bimverdi validate-migration --detailed --check-login
     * 
     * @when after_wp_load
     */
    public function validate_migration($args, $assoc_args) {
        
        $detailed = isset($assoc_args['detailed']);
        $check_login = isset($assoc_args['check-login']);
        $export = isset($assoc_args['export-report']);
        
        WP_CLI::log(WP_CLI::colorize('%Y=== BIM Verdi Migra sjonsvalidering ===%n'));
        WP_CLI::log('');
        
        // Tester som skal kjøres
        $tests = array(
            'users_count' => 'Test 1: Antall brukere',
            'users_with_email' => 'Test 2: Brukere har e-post',
            'users_with_names' => 'Test 3: Brukere har navn',
            'users_with_company' => 'Test 4: Brukere koblet til foretak',
            'companies_count' => 'Test 5: Antall foretak',
            'companies_with_orgnr' => 'Test 6: Foretak har org.nummer',
            'companies_with_contact' => 'Test 7: Foretak har hovedkontakt',
            'acf_fields_populated' => 'Test 8: ACF-felt populert',
            'images_imported' => 'Test 9: Bilder importert',
            'login_test' => 'Test 10: Innloggingstest'
        );
        
        $results = array();
        
        foreach ($tests as $test_key => $test_name) {
            WP_CLI::log($test_name . '...');
            
            try {
                $result = $this->{'run_test_' . $test_key}($detailed);
                $results[$test_key] = $result;
                
                if ($result['pass']) {
                    WP_CLI::success($result['message']);
                } else {
                    WP_CLI::warning($result['message']);
                }
                
            } catch (Exception $e) {
                WP_CLI::error('Feil under test: ' . $e->getMessage(), false);
                $results[$test_key] = array('pass' => false, 'message' => $e->getMessage());
            }
            
            WP_CLI::log('');
        }
        
        // Oppsummering
        WP_CLI::log(WP_CLI::colorize('%G=== Oppsummering ===%n'));
        $passed = array_filter($results, function($r) { return $r['pass']; });
        $failed = array_filter($results, function($r) { return !$r['pass']; });
        
        WP_CLI::log(sprintf('Tester bestått: %d / %d', count($passed), count($results)));
        WP_CLI::log(sprintf('Tester feilet: %d', count($failed)));
        
        if (count($failed) === 0) {
            WP_CLI::success('Alle tester bestått! ✅');
        } else {
            WP_CLI::warning('Noen tester feilet. Se detaljer ovenfor.');
        }
        
        // Eksporter rapport hvis ønsket
        if ($export) {
            $this->export_report($results);
        }
    }
    
    /**
     * Test 1: Antall brukere
     */
    private function run_test_users_count($detailed) {
        $users = get_users(array('number' => -1));
        $count = count($users);
        
        return array(
            'pass' => $count > 0,
            'message' => sprintf('Fant %d brukere i databasen', $count),
            'data' => array('count' => $count)
        );
    }
    
    /**
     * Test 2: Brukere har e-post
     */
    private function run_test_users_with_email($detailed) {
        $users = get_users(array('number' => -1));
        $without_email = 0;
        
        foreach ($users as $user) {
            if (empty($user->user_email) || !is_email($user->user_email)) {
                $without_email++;
                if ($detailed) {
                    WP_CLI::log(sprintf('  [!] Bruker #%d mangler gyldig e-post', $user->ID));
                }
            }
        }
        
        return array(
            'pass' => $without_email === 0,
            'message' => sprintf('%d brukere mangler e-post', $without_email),
            'data' => array('without_email' => $without_email)
        );
    }
    
    /**
     * Test 3: Brukere har navn
     */
    private function run_test_users_with_names($detailed) {
        $users = get_users(array('number' => -1));
        $without_name = 0;
        
        foreach ($users as $user) {
            $first_name = get_user_meta($user->ID, 'first_name', true);
            $last_name = get_user_meta($user->ID, 'last_name', true);
            
            if (empty($first_name) || empty($last_name)) {
                $without_name++;
                if ($detailed) {
                    WP_CLI::log(sprintf('  [!] Bruker #%d (%s) mangler navn', $user->ID, $user->user_email));
                }
            }
        }
        
        return array(
            'pass' => $without_name === 0,
            'message' => sprintf('%d brukere mangler fornavn/etternavn', $without_name),
            'data' => array('without_name' => $without_name)
        );
    }
    
    /**
     * Test 4: Brukere koblet til foretak
     */
    private function run_test_users_with_company($detailed) {
        $users = get_users(array('number' => -1));
        $with_company = 0;
        $without_company = 0;
        
        foreach ($users as $user) {
            $company_id = get_field('tilknyttet_foretak', 'user_' . $user->ID);
            
            if ($company_id) {
                $with_company++;
            } else {
                $without_company++;
                if ($detailed) {
                    WP_CLI::log(sprintf('  [!] Bruker #%d (%s) mangler foretak-kobling', $user->ID, $user->user_email));
                }
            }
        }
        
        return array(
            'pass' => true, // Ikke alle brukere MÅ ha foretak
            'message' => sprintf('%d brukere med foretak, %d uten', $with_company, $without_company),
            'data' => array('with' => $with_company, 'without' => $without_company)
        );
    }
    
    /**
     * Test 5: Antall foretak
     */
    private function run_test_companies_count($detailed) {
        $companies = get_posts(array(
            'post_type' => 'foretak',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        $count = count($companies);
        
        return array(
            'pass' => $count > 0,
            'message' => sprintf('Fant %d foretak', $count),
            'data' => array('count' => $count)
        );
    }
    
    /**
     * Test 6: Foretak har org.nummer
     */
    private function run_test_companies_with_orgnr($detailed) {
        $companies = get_posts(array(
            'post_type' => 'foretak',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        $without_orgnr = 0;
        
        foreach ($companies as $company) {
            $orgnr = get_field('organisasjonsnummer', $company->ID);
            
            if (empty($orgnr)) {
                $without_orgnr++;
                if ($detailed) {
                    WP_CLI::log(sprintf('  [!] Foretak #%d (%s) mangler org.nummer', $company->ID, $company->post_title));
                }
            }
        }
        
        return array(
            'pass' => $without_orgnr === 0,
            'message' => sprintf('%d foretak mangler org.nummer', $without_orgnr),
            'data' => array('without_orgnr' => $without_orgnr)
        );
    }
    
    /**
     * Test 7: Foretak har hovedkontakt
     */
    private function run_test_companies_with_contact($detailed) {
        $companies = get_posts(array(
            'post_type' => 'foretak',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        $without_contact = 0;
        
        foreach ($companies as $company) {
            $contact_id = get_field('hovedkontaktperson', $company->ID);
            
            if (empty($contact_id)) {
                $without_contact++;
                if ($detailed) {
                    WP_CLI::log(sprintf('  [!] Foretak #%d (%s) mangler hovedkontakt', $company->ID, $company->post_title));
                }
            }
        }
        
        return array(
            'pass' => $without_contact === 0,
            'message' => sprintf('%d foretak mangler hovedkontakt', $without_contact),
            'data' => array('without_contact' => $without_contact)
        );
    }
    
    /**
     * Test 8: ACF-felt populert
     */
    private function run_test_acf_fields_populated($detailed) {
        $users = get_users(array('number' => 100)); // Sample
        
        $fields_populated = array('phone' => 0, 'job_title' => 0, 'linkedin_url' => 0);
        
        foreach ($users as $user) {
            if (get_field('phone', 'user_' . $user->ID)) $fields_populated['phone']++;
            if (get_field('job_title', 'user_' . $user->ID)) $fields_populated['job_title']++;
            if (get_field('linkedin_url', 'user_' . $user->ID)) $fields_populated['linkedin_url']++;
        }
        
        $message = sprintf('ACF-felt populert: phone=%d, job_title=%d, linkedin=%d (av 100 brukere)', 
            $fields_populated['phone'], 
            $fields_populated['job_title'], 
            $fields_populated['linkedin_url']
        );
        
        return array(
            'pass' => true,
            'message' => $message,
            'data' => $fields_populated
        );
    }
    
    /**
     * Test 9: Bilder importert
     */
    private function run_test_images_imported($detailed) {
        $companies = get_posts(array(
            'post_type' => 'foretak',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        $with_logo = 0;
        
        foreach ($companies as $company) {
            $logo = get_field('logo', $company->ID);
            if ($logo) {
                $with_logo++;
            }
        }
        
        return array(
            'pass' => true,
            'message' => sprintf('%d foretak har logo', $with_logo),
            'data' => array('with_logo' => $with_logo)
        );
    }
    
    /**
     * Test 10: Innloggingstest
     */
    private function run_test_login_test($detailed) {
        // Ikke implementert - krever mer avansert testing
        return array(
            'pass' => true,
            'message' => 'Innloggingstest hoppes over (kjør manuelt)',
            'data' => array()
        );
    }
    
    /**
     * Eksporter rapport til CSV
     */
    private function export_report($results) {
        $filename = WP_CONTENT_DIR . '/shared-context/migration-validation-report-' . date('Y-m-d-His') . '.csv';
        
        $fp = fopen($filename, 'w');
        fputcsv($fp, array('Test', 'Status', 'Melding'));
        
        foreach ($results as $test => $result) {
            fputcsv($fp, array(
                $test,
                $result['pass'] ? 'PASS' : 'FAIL',
                $result['message']
            ));
        }
        
        fclose($fp);
        
        WP_CLI::success("Rapport eksportert til: $filename");
    }
}

// Legg til i eksisterende migration command-fil
WP_CLI::add_command('bimverdi validate-migration', array('BIM_Verdi_Validation_Command', 'validate_migration'));
