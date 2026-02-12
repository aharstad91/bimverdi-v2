<?php
/**
 * Kunnskapskilde Form Setup
 *
 * Programmatically creates the Gravity Form for kunnskapskilde registration
 * Runs once on plugin activation or manual trigger
 *
 * @package BIM_Verdi_Core
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Kunnskapskilde_Form_Setup {

    const OPTION_KEY = 'bim_verdi_kunnskapskilde_form_id';

    /**
     * Get the form ID (creates form if it doesn't exist)
     *
     * @return int|false Form ID or false on failure
     */
    public static function get_form_id() {
        $form_id = get_option(self::OPTION_KEY);

        if ($form_id && class_exists('GFAPI')) {
            // Verify form still exists
            $form = GFAPI::get_form($form_id);
            if ($form && !is_wp_error($form)) {
                return (int) $form_id;
            }
        }

        // Form doesn't exist, create it
        return self::create_form();
    }

    /**
     * Create the kunnskapskilde registration form
     *
     * @return int|false Form ID or false on failure
     */
    public static function create_form() {
        if (!class_exists('GFAPI')) {
            error_log('BIM Verdi: GFAPI not available - cannot create kunnskapskilde form');
            return false;
        }

        // Get taxonomy terms for choices
        $temagrupper = get_terms(array(
            'taxonomy' => 'temagruppe',
            'hide_empty' => false,
        ));

        $kategorier = get_terms(array(
            'taxonomy' => 'kunnskapskildekategori',
            'hide_empty' => false,
        ));

        // Build temagruppe choices + inputs (GFAPI requires inputs for checkbox fields)
        $temagruppe_choices = array();
        $temagruppe_inputs = array();
        if (!is_wp_error($temagrupper)) {
            $i = 1;
            foreach ($temagrupper as $term) {
                $temagruppe_choices[] = array(
                    'text' => $term->name,
                    'value' => $term->slug,
                );
                $temagruppe_inputs[] = array(
                    'id' => '12.' . $i,
                    'label' => $term->name,
                    'name' => '',
                );
                $i++;
            }
        }

        // Build kategori choices + inputs
        $kategori_choices = array();
        $kategori_inputs = array();
        if (!is_wp_error($kategorier)) {
            $i = 1;
            foreach ($kategorier as $term) {
                $kategori_choices[] = array(
                    'text' => $term->name,
                    'value' => $term->slug,
                );
                $kategori_inputs[] = array(
                    'id' => '13.' . $i,
                    'label' => $term->name,
                    'name' => '',
                );
                $i++;
            }
        }

        // Kildetype choices
        $kildetype_choices = array(
            array('text' => 'Standard (ISO, NS, etc.)', 'value' => 'standard'),
            array('text' => 'Veiledning/metodikk', 'value' => 'veiledning'),
            array('text' => 'Forskrift (norsk lov)', 'value' => 'forskrift_norsk'),
            array('text' => 'Forordning (EU/EØS)', 'value' => 'forordning_eu'),
            array('text' => 'Mal/Template', 'value' => 'mal'),
            array('text' => 'Forskningsrapport', 'value' => 'forskningsrapport'),
            array('text' => 'Casestudie', 'value' => 'casestudie'),
            array('text' => 'Opplæringsmateriell', 'value' => 'opplaering'),
            array('text' => 'Verktøydokumentasjon', 'value' => 'dokumentasjon'),
            array('text' => 'Nettressurs/Database', 'value' => 'nettressurs'),
            array('text' => 'Annet (tjeneste, webside etc.)', 'value' => 'annet'),
        );

        // Language choices
        $spraak_choices = array(
            array('text' => 'Norsk', 'value' => 'norsk'),
            array('text' => 'Engelsk', 'value' => 'engelsk'),
            array('text' => 'Svensk', 'value' => 'svensk'),
            array('text' => 'Dansk', 'value' => 'dansk'),
            array('text' => 'Flerspråklig', 'value' => 'flerspraklig'),
            array('text' => 'Annet', 'value' => 'annet'),
        );

        // Geografisk gyldighet choices
        $geo_choices = array(
            array('text' => 'Nasjonalt/Norsk', 'value' => 'nasjonalt'),
            array('text' => 'Nordisk', 'value' => 'nordisk'),
            array('text' => 'Europeisk', 'value' => 'europeisk'),
            array('text' => 'Internasjonalt', 'value' => 'internasjonalt'),
            array('text' => 'Annet', 'value' => 'annet'),
        );

        // Dataformat choices
        $dataformat_choices = array(
            array('text' => 'PDF-dokument', 'value' => 'pdf'),
            array('text' => 'Web-innhold - åpent', 'value' => 'web_aapent'),
            array('text' => 'Web-innhold - lukket/betalt', 'value' => 'web_lukket'),
            array('text' => 'Åpent API', 'value' => 'api'),
            array('text' => 'IFC-fil', 'value' => 'ifc'),
            array('text' => 'Database/register', 'value' => 'database'),
            array('text' => 'Annet', 'value' => 'annet'),
        );

        // År choices
        $aar_choices = array(
            array('text' => '2026', 'value' => '2026'),
            array('text' => '2025', 'value' => '2025'),
            array('text' => '2024', 'value' => '2024'),
            array('text' => '2023', 'value' => '2023'),
            array('text' => '2022', 'value' => '2022'),
            array('text' => 'Eldre enn 2022', 'value' => 'eldre'),
        );

        $form = array(
            'title' => 'Registrer kunnskapskilde',
            'description' => 'Registrer en ny kunnskapskilde for BIM Verdi',
            'labelPlacement' => 'top_label',
            'descriptionPlacement' => 'below',
            'button' => array(
                'type' => 'text',
                'text' => 'Registrer kunnskapskilde',
            ),
            'fields' => array(
                // Field 1: Navn
                array(
                    'type' => 'text',
                    'label' => 'Navn på kunnskapskilde',
                    'isRequired' => true,
                    'maxLength' => 100,
                    'placeholder' => 'F.eks. ISO 19650-1:2018',
                    'cssClass' => 'gf-large',
                    'inputName' => 'kunnskapskilde_navn',
                ),
                // Field 2: Kort beskrivelse
                array(
                    'type' => 'textarea',
                    'label' => 'Kort beskrivelse',
                    'isRequired' => false,
                    'maxLength' => 250,
                    'placeholder' => 'Beskriv kunnskapskilden kort (maks 250 tegn)',
                    'inputName' => 'kort_beskrivelse',
                ),
                // Field 3: Detaljert beskrivelse
                array(
                    'type' => 'textarea',
                    'label' => 'Detaljert beskrivelse',
                    'isRequired' => false,
                    'placeholder' => 'Utfyllende beskrivelse (valgfritt)',
                    'useRichTextEditor' => true,
                    'inputName' => 'detaljert_beskrivelse',
                ),
                // Field 4: Ekstern lenke (URL)
                array(
                    'type' => 'website',
                    'label' => 'Ekstern lenke (URL)',
                    'isRequired' => true,
                    'placeholder' => 'https://example.com/dokument',
                    'inputName' => 'ekstern_lenke',
                ),
                // Field 5: Utgiver
                array(
                    'type' => 'text',
                    'label' => 'Utgiver',
                    'isRequired' => false,
                    'placeholder' => 'F.eks. Standard Norge, buildingSMART',
                    'inputName' => 'utgiver',
                ),
                // Field 7: Språk
                array(
                    'type' => 'select',
                    'label' => 'Språk',
                    'isRequired' => false,
                    'choices' => $spraak_choices,
                    'placeholder' => 'Velg språk',
                    'inputName' => 'spraak',
                ),
                // Field 8: Versjon
                array(
                    'type' => 'text',
                    'label' => 'Versjon',
                    'isRequired' => false,
                    'placeholder' => 'F.eks. 2.0, Rev. 3',
                    'inputName' => 'versjon',
                ),
                // Field 9: Utgivelsesår (select)
                array(
                    'type' => 'select',
                    'label' => 'År (antatt)',
                    'isRequired' => false,
                    'choices' => $aar_choices,
                    'placeholder' => 'Velg antatt år',
                    'inputName' => 'utgivelsesaar',
                ),
                // Field: Ant. lovpålagte standarder
                array(
                    'type' => 'number',
                    'label' => 'Ant. lovpålagte standarder',
                    'isRequired' => false,
                    'placeholder' => '0',
                    'inputName' => 'ant_lovpalagte_standarder',
                ),
                // Field: Lovpålagte standarder
                array(
                    'type' => 'text',
                    'label' => 'Lovpålagte standarder',
                    'isRequired' => false,
                    'placeholder' => 'NS 3420, NS-EN 1990, ...',
                    'inputName' => 'lovpalagte_standarder',
                ),
                // Field: Ant. anbefalte standarder
                array(
                    'type' => 'number',
                    'label' => 'Ant. anbefalte standarder',
                    'isRequired' => false,
                    'placeholder' => '0',
                    'inputName' => 'ant_anbefalte_standarder',
                ),
                // Field: Anbefalte standarder
                array(
                    'type' => 'text',
                    'label' => 'Anbefalte standarder',
                    'isRequired' => false,
                    'placeholder' => 'ISO 19650, ISO 16739, ...',
                    'inputName' => 'anbefalte_standarder',
                ),
                // Field: Tilgang
                array(
                    'type' => 'select',
                    'label' => 'Tilgang',
                    'isRequired' => false,
                    'choices' => array(
                        array('text' => 'Gratis', 'value' => 'gratis'),
                        array('text' => 'Betalt', 'value' => 'betalt'),
                        array('text' => 'Abonnement', 'value' => 'abonnement'),
                        array('text' => 'Ukjent', 'value' => 'ukjent'),
                    ),
                    'placeholder' => 'Velg tilgang',
                    'inputName' => 'tilgang',
                ),
                // Field 10: Kildetype
                array(
                    'type' => 'select',
                    'label' => 'Type kilde/ressurs',
                    'isRequired' => true,
                    'choices' => $kildetype_choices,
                    'placeholder' => 'Velg type',
                    'inputName' => 'kildetype',
                ),
                // Field: Geografisk gyldighet
                array(
                    'type' => 'select',
                    'label' => 'Geografisk gyldighet',
                    'isRequired' => false,
                    'choices' => $geo_choices,
                    'placeholder' => 'Velg geografisk gyldighet',
                    'inputName' => 'geografisk_gyldighet',
                ),
                // Field: Dataformat
                array(
                    'type' => 'select',
                    'label' => 'Dataform(at)',
                    'isRequired' => false,
                    'choices' => $dataformat_choices,
                    'placeholder' => 'Velg dataformat',
                    'inputName' => 'dataformat',
                ),
                // Field 11: Temagrupper (checkbox - requires inputs array for GFAPI)
                array(
                    'type' => 'checkbox',
                    'label' => 'Relevante temagrupper',
                    'isRequired' => false,
                    'choices' => $temagruppe_choices,
                    'inputs' => $temagruppe_inputs,
                    'inputName' => 'temagrupper',
                ),
                // Field 12: Kategorier (checkbox - requires inputs array for GFAPI)
                array(
                    'type' => 'checkbox',
                    'label' => 'Kategorier',
                    'isRequired' => false,
                    'choices' => $kategori_choices,
                    'inputs' => $kategori_inputs,
                    'inputName' => 'kategorier',
                ),
                // Field 13: kunnskapskilde_id (hidden - for editing)
                array(
                    'type' => 'hidden',
                    'label' => 'Kunnskapskilde ID',
                    'inputName' => 'kunnskapskilde_id',
                    'allowsPrepopulate' => true,
                ),
                // Field 14: Bruker-ID (hidden)
                array(
                    'type' => 'hidden',
                    'label' => 'Bruker-ID',
                    'inputName' => 'user_id',
                    'allowsPrepopulate' => true,
                ),
                // Field 15: Foretak ID (hidden)
                array(
                    'type' => 'hidden',
                    'label' => 'Foretak',
                    'inputName' => 'company_id',
                    'allowsPrepopulate' => true,
                ),
                // Field 17: GDPR samtykke (NB: input id must match field position = 17)
                array(
                    'type' => 'checkbox',
                    'label' => 'Personvern og vilkår',
                    'isRequired' => true,
                    'description' => 'Ved å registrere en kunnskapskilde godtar du BIM Verdis personvernregler.',
                    'choices' => array(
                        array(
                            'text' => 'Jeg godtar at BIM Verdi lagrer og behandler denne informasjonen i henhold til personvernreglene.',
                            'value' => '1',
                        ),
                    ),
                    'inputs' => array(
                        array(
                            'id' => '17.1',
                            'label' => 'Jeg godtar at BIM Verdi lagrer og behandler denne informasjonen i henhold til personvernreglene.',
                            'name' => 'personvern_samtykke',
                        ),
                    ),
                ),
            ),
            'confirmations' => array(
                array(
                    'id' => 'default',
                    'name' => 'Standard bekreftelse',
                    'isDefault' => true,
                    'type' => 'message',
                    'message' => 'Takk! Kunnskapskilden er registrert og publisert i katalogen.',
                ),
            ),
        );

        $form_id = GFAPI::add_form($form);

        if (is_wp_error($form_id)) {
            error_log('BIM Verdi: Failed to create kunnskapskilde form - ' . $form_id->get_error_message());
            return false;
        }

        // Save the form ID
        update_option(self::OPTION_KEY, $form_id);

        // Get the actual field IDs that were assigned
        $created_form = GFAPI::get_form($form_id);
        if ($created_form) {
            $field_map = self::build_field_map($created_form);
            update_option('bim_verdi_kunnskapskilde_field_map', $field_map);

            error_log('BIM Verdi: Created kunnskapskilde form ID ' . $form_id . ' with field map: ' . print_r($field_map, true));
        }

        return (int) $form_id;
    }

    /**
     * Build a map of inputName => fieldId for the handler to use
     *
     * @param array $form The form object
     * @return array Field map
     */
    private static function build_field_map($form) {
        $map = array();

        if (!empty($form['fields'])) {
            foreach ($form['fields'] as $field) {
                if (!empty($field->inputName)) {
                    $map[$field->inputName] = $field->id;
                }
                // Also map by label for consent fields etc
                $label_key = sanitize_title($field->label);
                $map[$label_key] = $field->id;
            }
        }

        return $map;
    }

    /**
     * Get field ID by input name
     *
     * @param string $input_name The input name
     * @return int|null Field ID or null
     */
    public static function get_field_id($input_name) {
        $field_map = get_option('bim_verdi_kunnskapskilde_field_map', array());
        return isset($field_map[$input_name]) ? (int) $field_map[$input_name] : null;
    }

    /**
     * Delete the form (for cleanup/reset)
     */
    public static function delete_form() {
        $form_id = get_option(self::OPTION_KEY);

        if ($form_id && class_exists('GFAPI')) {
            GFAPI::delete_form($form_id);
        }

        delete_option(self::OPTION_KEY);
        delete_option('bim_verdi_kunnskapskilde_field_map');
    }
}
