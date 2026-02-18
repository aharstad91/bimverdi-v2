<?php
/**
 * Company Edit Form Handler
 *
 * Handles company profile editing via Gravity Forms (Form ID 7).
 * - Prepopulates fields with current company data
 * - Locks BRREG-sourced fields (org.nr, bedriftsnavn, adresse) as readonly
 * - Saves editable fields to ACF on submission
 *
 * @package BIM_Verdi_Core
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Company_Edit_Form_Handler {

    const FORM_ID = 7;

    // Fields sourced from BRREG (should be readonly for Norwegian companies)
    const BRREG_FIELD_IDS = array(1, 2, 7, 8, 9); // org.nr, bedriftsnavn, gateadresse, postnummer, poststed

    /**
     * Initialize the handler
     */
    public function __construct() {
        add_filter('gform_pre_render_' . self::FORM_ID, array($this, 'populate_form_fields'));
        add_filter('gform_field_css_class_' . self::FORM_ID, array($this, 'add_brreg_locked_class'), 10, 3);
        add_action('gform_after_submission_' . self::FORM_ID, array($this, 'handle_submission'), 10, 2);
    }

    /**
     * Get the company ID from the current user context
     *
     * @return int|false Company post ID or false
     */
    private function get_current_company_id() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return false;
        }

        $company_data = bimverdi_get_user_company($user_id);
        if (!$company_data) {
            return false;
        }

        return is_array($company_data) ? $company_data['id'] : $company_data;
    }

    /**
     * Populate form fields with current company data before rendering
     *
     * @param array $form The form object
     * @return array Modified form object
     */
    public function populate_form_fields($form) {
        $company_id = $this->get_current_company_id();
        if (!$company_id) {
            return $form;
        }

        // Map field IDs to ACF field names
        $field_mapping = array(
            1  => 'organisasjonsnummer',
            2  => 'bedriftsnavn',
            3  => 'kort_beskrivelse',
            5  => 'telefon',
            6  => 'webside',
            7  => 'adresse',
            8  => 'postnummer',
            9  => 'poststed',
            10 => null, // Company ID - special handling
        );

        foreach ($form['fields'] as &$field) {
            $field_id = (int) $field->id;

            if ($field_id === 10) {
                // Hidden company ID field
                $field->defaultValue = $company_id;
                continue;
            }

            if (isset($field_mapping[$field_id]) && $field_mapping[$field_id]) {
                $acf_key = $field_mapping[$field_id];
                $value = get_field($acf_key, $company_id);

                // Fallback for bedriftsnavn
                if ($field_id === 2 && empty($value)) {
                    $value = get_the_title($company_id);
                }

                if (!empty($value)) {
                    $field->defaultValue = $value;
                }
            }
        }

        return $form;
    }

    /**
     * Add CSS class to BRREG-sourced fields to mark them as locked
     *
     * @param string $classes Current CSS classes
     * @param object $field The field object
     * @param array $form The form object
     * @return string Modified CSS classes
     */
    public function add_brreg_locked_class($classes, $field, $form) {
        $field_id = (int) $field->id;

        if (!in_array($field_id, self::BRREG_FIELD_IDS)) {
            return $classes;
        }

        // Only lock for Norwegian companies
        $company_id = $this->get_current_company_id();
        if ($company_id && function_exists('bimverdi_is_norwegian_foretak') && bimverdi_is_norwegian_foretak($company_id)) {
            $classes .= ' gf-brreg-locked';
        }

        return $classes;
    }

    /**
     * Handle form submission - save editable fields to ACF
     *
     * @param array $entry The submitted form entry
     * @param array $form The form object
     */
    public function handle_submission($entry, $form) {
        $company_id = absint(rgar($entry, '10'));

        if (!$company_id) {
            $company_id = $this->get_current_company_id();
        }

        if (!$company_id) {
            error_log('BIM Verdi Company Edit: No company ID found in submission');
            return;
        }

        // Verify user is hovedkontakt or admin
        $user_id = get_current_user_id();
        $is_hovedkontakt = bimverdi_is_hovedkontakt($user_id, $company_id);
        $is_admin = current_user_can('manage_options');

        if (!$is_hovedkontakt && !$is_admin) {
            error_log('BIM Verdi Company Edit: Unauthorized edit attempt by user ' . $user_id);
            return;
        }

        // Save editable fields only (NOT BRREG fields)
        if (function_exists('update_field')) {
            // Beskrivelse
            $beskrivelse = sanitize_textarea_field(rgar($entry, '3'));
            update_field('kort_beskrivelse', $beskrivelse, $company_id);

            // Telefon
            $telefon = sanitize_text_field(rgar($entry, '5'));
            update_field('telefon', $telefon, $company_id);

            // Nettside
            $nettside = esc_url_raw(rgar($entry, '6'));
            update_field('webside', $nettside, $company_id);
        }

        // Handle logo upload
        $logo_url = rgar($entry, '4');
        if (!empty($logo_url)) {
            $this->handle_logo_upload($logo_url, $company_id);
        }

        error_log('BIM Verdi Company Edit: Updated company ' . $company_id . ' by user ' . $user_id);
        gform_update_meta($entry['id'], 'updated_company_id', $company_id);
    }

    /**
     * Handle logo upload from Gravity Forms
     *
     * @param string $logo_url URL of uploaded logo
     * @param int $company_id Company post ID
     */
    private function handle_logo_upload($logo_url, $company_id) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        // Delete existing logo
        $existing_logo = get_field('logo', $company_id);
        if ($existing_logo) {
            $existing_id = is_array($existing_logo) ? $existing_logo['ID'] : $existing_logo;
            if ($existing_id) {
                wp_delete_attachment($existing_id, true);
            }
        }

        $tmp = download_url($logo_url);
        if (is_wp_error($tmp)) {
            error_log('BIM Verdi Company Edit: Logo download failed - ' . $tmp->get_error_message());
            return;
        }

        $file_array = array(
            'name'     => basename($logo_url),
            'tmp_name' => $tmp,
        );

        $attachment_id = media_handle_sideload($file_array, $company_id);

        if (file_exists($tmp)) {
            @unlink($tmp);
        }

        if (is_wp_error($attachment_id)) {
            error_log('BIM Verdi Company Edit: Logo sideload failed - ' . $attachment_id->get_error_message());
            return;
        }

        if (function_exists('update_field')) {
            update_field('logo', $attachment_id, $company_id);
        }
    }
}
