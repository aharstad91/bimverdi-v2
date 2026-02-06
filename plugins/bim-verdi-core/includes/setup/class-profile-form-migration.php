<?php
/**
 * Profile Form Migration
 *
 * Adds new fields (7-10) to the existing profile edit form (Form ID 4).
 * Idempotent: checks if fields already exist before adding.
 *
 * @package BIM_Verdi_Core
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Profile_Form_Migration {

    const FORM_ID = 4;
    const VERSION_OPTION = 'bim_verdi_profile_form_version';
    const CURRENT_VERSION = '2.0.0';

    /**
     * Run migration if needed
     */
    public static function maybe_migrate() {
        if (!class_exists('GFAPI')) {
            return;
        }

        $current_version = get_option(self::VERSION_OPTION, '1.0.0');

        if (version_compare($current_version, self::CURRENT_VERSION, '>=')) {
            return;
        }

        self::migrate();
    }

    /**
     * Add new fields to Form 4
     */
    private static function migrate() {
        $form = GFAPI::get_form(self::FORM_ID);

        if (!$form || is_wp_error($form)) {
            error_log('BIM Verdi Profile Migration: Form ' . self::FORM_ID . ' not found');
            return;
        }

        // Build a map of existing field IDs
        $existing_ids = array();
        foreach ($form['fields'] as $field) {
            $existing_ids[] = (int) $field->id;
        }

        $fields_added = 0;

        // Field 7: Mellomnavn
        if (!in_array(7, $existing_ids)) {
            $form['fields'][] = new GF_Field_Text(array(
                'id' => 7,
                'formId' => self::FORM_ID,
                'label' => 'Mellomnavn',
                'type' => 'text',
                'isRequired' => false,
                'placeholder' => '',
                'cssClass' => '',
                'inputName' => 'middle_name',
            ));
            $fields_added++;
        }

        // Field 8: Personbilde (file upload)
        if (!in_array(8, $existing_ids)) {
            $form['fields'][] = new GF_Field_FileUpload(array(
                'id' => 8,
                'formId' => self::FORM_ID,
                'label' => 'Personbilde',
                'type' => 'fileupload',
                'isRequired' => false,
                'description' => 'Last opp et profilbilde (maks 2 MB, jpg/png/webp)',
                'descriptionPlacement' => 'below',
                'allowedExtensions' => 'jpg, jpeg, png, webp',
                'maxFileSize' => 2,
                'inputName' => 'profile_image',
            ));
            $fields_added++;
        }

        // Field 9: Bakgrunn for registrering (checkbox)
        if (!in_array(9, $existing_ids)) {
            $form['fields'][] = new GF_Field_Checkbox(array(
                'id' => 9,
                'formId' => self::FORM_ID,
                'label' => 'Bakgrunn for registrering',
                'type' => 'checkbox',
                'isRequired' => false,
                'description' => 'Hva er bakgrunnen for at du registrerte deg?',
                'descriptionPlacement' => 'above',
                'inputName' => 'registration_background',
                'choices' => array(
                    array('text' => 'Dette er en oppdatering - jeg er allerede registrert', 'value' => 'oppdatering'),
                    array('text' => 'Min arbeidsgiver er deltaker og jeg er ny tilleggskontakt', 'value' => 'tilleggskontakt'),
                    array('text' => 'Gjelder registrering for arrangement-deltakelse', 'value' => 'arrangement'),
                    array('text' => 'Jeg ønsker å motta nyhetsbrev fra BIM Verdi', 'value' => 'nyhetsbrev'),
                    array('text' => 'Deltakerregistrering og digitale verktøy', 'value' => 'deltaker_verktoy'),
                    array('text' => 'Ønsker å avtale et møte', 'value' => 'mote'),
                ),
                'inputs' => array(
                    array('id' => '9.1', 'label' => 'Dette er en oppdatering - jeg er allerede registrert', 'name' => ''),
                    array('id' => '9.2', 'label' => 'Min arbeidsgiver er deltaker og jeg er ny tilleggskontakt', 'name' => ''),
                    array('id' => '9.3', 'label' => 'Gjelder registrering for arrangement-deltakelse', 'name' => ''),
                    array('id' => '9.4', 'label' => 'Jeg ønsker å motta nyhetsbrev fra BIM Verdi', 'name' => ''),
                    array('id' => '9.5', 'label' => 'Deltakerregistrering og digitale verktøy', 'name' => ''),
                    array('id' => '9.6', 'label' => 'Ønsker å avtale et møte', 'name' => ''),
                ),
            ));
            $fields_added++;
        }

        // Field 10: Interesse for temaene (checkbox)
        if (!in_array(10, $existing_ids)) {
            $form['fields'][] = new GF_Field_Checkbox(array(
                'id' => 10,
                'formId' => self::FORM_ID,
                'label' => 'Interesse for temaene',
                'type' => 'checkbox',
                'isRequired' => false,
                'description' => 'Velg de temagruppene du er interessert i',
                'descriptionPlacement' => 'above',
                'inputName' => 'topic_interests',
                'choices' => array(
                    array('text' => 'ByggesaksBIM', 'value' => 'byggesaksbim'),
                    array('text' => 'ProsjektBIM', 'value' => 'prosjektbim'),
                    array('text' => 'EiendomsBIM', 'value' => 'eiendomsbim'),
                    array('text' => 'MiljøBIM', 'value' => 'miljobim'),
                    array('text' => 'SirkBIM', 'value' => 'sirkbim'),
                    array('text' => 'BIMtech', 'value' => 'bimtech'),
                ),
                'inputs' => array(
                    array('id' => '10.1', 'label' => 'ByggesaksBIM', 'name' => ''),
                    array('id' => '10.2', 'label' => 'ProsjektBIM', 'name' => ''),
                    array('id' => '10.3', 'label' => 'EiendomsBIM', 'name' => ''),
                    array('id' => '10.4', 'label' => 'MiljøBIM', 'name' => ''),
                    array('id' => '10.5', 'label' => 'SirkBIM', 'name' => ''),
                    array('id' => '10.6', 'label' => 'BIMtech', 'name' => ''),
                ),
            ));
            $fields_added++;
        }

        if ($fields_added > 0) {
            $result = GFAPI::update_form($form);

            if (is_wp_error($result)) {
                error_log('BIM Verdi Profile Migration: Failed to update form - ' . $result->get_error_message());
                return;
            }

            error_log('BIM Verdi Profile Migration: Added ' . $fields_added . ' new fields to Form ' . self::FORM_ID);
        }

        update_option(self::VERSION_OPTION, self::CURRENT_VERSION);
        error_log('BIM Verdi Profile Migration: Updated to version ' . self::CURRENT_VERSION);
    }
}
