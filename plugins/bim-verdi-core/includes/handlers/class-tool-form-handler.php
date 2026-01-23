<?php
/**
 * Tool Form Handler
 * 
 * Handles "Registrer verktøy" Gravity Forms submissions
 * Creates Verktøy (Tool) posts linked to the user's company
 * 
 * @package BIM_Verdi_Core
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Tool_Form_Handler {
    
    const FORM_ID = 1; // "Registrer verktøy" form ID in Gravity Forms
    
    /**
     * Initialize the handler
     */
    public function __construct() {
        add_action('gform_after_submission_' . self::FORM_ID, array($this, 'handle_submission'), 10, 2);
        
        // Pre-populate fields using Gravity Forms parameter system
        add_filter('gform_field_value_company_id', array($this, 'populate_company_id'));
        add_filter('gform_field_value_user_id', array($this, 'populate_user_id'));
        add_filter('gform_field_value_tool_id', array($this, 'populate_tool_id'));
        add_filter('gform_field_value_tool_name', array($this, 'populate_tool_name'));
        add_filter('gform_field_value_tool_description', array($this, 'populate_tool_description'));
        add_filter('gform_field_value_tool_url', array($this, 'populate_tool_url'));
        
        // Pre-render filters to update field properties
        add_filter('gform_pre_render_' . self::FORM_ID, array($this, 'prepopulate_company_field'));
        add_filter('gform_pre_validation_' . self::FORM_ID, array($this, 'prepopulate_company_field'));
        add_filter('gform_pre_submission_filter_' . self::FORM_ID, array($this, 'prepopulate_company_field'));
        add_filter('gform_admin_pre_render_' . self::FORM_ID, array($this, 'prepopulate_company_field'));
        
        // Pre-populate tool data for editing
        add_filter('gform_pre_render_' . self::FORM_ID, array($this, 'prepopulate_tool_fields'));
        add_filter('gform_pre_validation_' . self::FORM_ID, array($this, 'prepopulate_tool_fields'));
        
        // Add validation to inject company ID into submission
        add_filter('gform_validation_' . self::FORM_ID, array($this, 'inject_company_id'));
        
        // Add custom checkbox validation for "Anvendelser" field
        add_filter('gform_validation_' . self::FORM_ID, array($this, 'validate_checkbox_fields'));
        
        // Customize submit button text based on edit/create mode
        add_filter('gform_submit_button_' . self::FORM_ID, array($this, 'customize_submit_button'), 10, 2);
    }
    
    /**
     * Populate company_id parameter with user's company
     * 
     * @return int Company ID
     */
    public function populate_company_id() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '';
        }
        
        return get_user_meta($user_id, 'bim_verdi_company_id', true);
    }
    
    /**
     * Populate user_id parameter with current user ID
     * 
     * @return int User ID
     */
    public function populate_user_id() {
        return get_current_user_id();
    }
    
    /**
     * Populate tool_id parameter from URL
     * 
     * @return int Tool ID (0 if new tool)
     */
    public function populate_tool_id() {
        return isset($_GET['tool_id']) ? intval($_GET['tool_id']) :
               (isset($_GET['id']) ? intval($_GET['id']) : 0);
    }
    
    /**
     * Populate tool_name parameter
     */
    public function populate_tool_name() {
        $tool_id = isset($_GET['tool_id']) ? intval($_GET['tool_id']) :
                   (isset($_GET['id']) ? intval($_GET['id']) : 0);
        if (!$tool_id) return '';

        $tool = get_post($tool_id);
        return $tool ? $tool->post_title : '';
    }
    
    /**
     * Populate tool_description parameter
     */
    public function populate_tool_description() {
        $tool_id = isset($_GET['tool_id']) ? intval($_GET['tool_id']) :
                   (isset($_GET['id']) ? intval($_GET['id']) : 0);
        if (!$tool_id) return '';

        $description = get_field('detaljert_beskrivelse', $tool_id);
        if (empty($description)) {
            $tool = get_post($tool_id);
            $description = $tool ? $tool->post_content : '';
        }
        return wp_strip_all_tags($description);
    }
    
    /**
     * Populate tool_url parameter
     */
    public function populate_tool_url() {
        $tool_id = isset($_GET['tool_id']) ? intval($_GET['tool_id']) :
                   (isset($_GET['id']) ? intval($_GET['id']) : 0);
        if (!$tool_id) return '';

        return get_field('verktoy_lenke', $tool_id);
    }
    
    /**
     * Pre-populate tool fields when editing existing tool
     * 
     * @param array $form The form object
     * @return array Modified form object
     */
    public function prepopulate_tool_fields($form) {
        
        // Check if we're editing (tool_id or id present)
        $tool_id = isset($_GET['tool_id']) ? intval($_GET['tool_id']) :
                   (isset($_GET['id']) ? intval($_GET['id']) : 0);

        if (!$tool_id) {
            return $form; // Not editing, return form as-is
        }
        
        // Get the tool post
        $tool = get_post($tool_id);
        if (!$tool || $tool->post_type !== 'verktoy') {
            return $form;
        }
        
        // Get tool data
        $tool_name = $tool->post_title;
        $description = get_field('detaljert_beskrivelse', $tool_id);
        $tool_url = get_field('verktoy_lenke', $tool_id);
        $tool_price = get_field('verktoy_pris', $tool_id);
        
        // Get BIM-specific fields (add null checks)
        $formaalstema = get_field('formaalstema', $tool_id);
        $bim_kompatibilitet = get_field('bim_kompatibilitet', $tool_id);
        $type_ressurs = get_field('type_ressurs', $tool_id);
        $type_teknologi = get_field('type_teknologi', $tool_id);
        $anvendelser = get_field('anvendelser', $tool_id); // This is an array of selected values
        
        // Ensure anvendelser is always an array (even if empty)
        if (!is_array($anvendelser)) {
            $anvendelser = array();
        }
        
        // Debug logging
        error_log("BIM Verdi - Prepopulating tool {$tool_id}: description = " . ($description ? substr($description, 0, 100) : 'EMPTY'));
        error_log("BIM Verdi - Anvendelser saved: " . print_r($anvendelser, true));
        
        // Fallback: if ACF field is empty, try post content
        if (empty($description)) {
            $description = $tool->post_content;
            error_log("BIM Verdi - Using fallback post_content: " . substr($description, 0, 100));
        }
        
        // Keep HTML since Gravity Forms Field 3 has Rich Text Editor enabled
        // No need to strip tags
        
        // Get category
        $category_terms = wp_get_post_terms($tool_id, 'verktoykategori');
        $category = !empty($category_terms) ? $category_terms[0]->name : '';
        
        // Pre-populate fields based on field IDs
        // Field mapping (CORRECT FIELD IDS from list-gf-fields.php):
        // 1 = Verktøynavn (Single Line Text)
        // 3 = Detaljert beskrivelse (Paragraph Text with RTE)
        // 4 = Lenke til verktøy (Single Line Text)
        // 7 = Logo (File Upload)
        // 12 = Formålstema (Radio) ← CORRECTED
        // 13 = BIM-kompatibilitet (Radio) ← CORRECTED
        // 14 = Type ressurs (Radio) ← CORRECTED
        // 15 = Type teknologi (Radio) ← CORRECTED
        // 16 = Anvendelser (Checkbox) ← CORRECTED
        // 11 = Tool id (Hidden)
        // 9 = Bruker-ID (Hidden)
        // 8 = Foretak (Hidden)
        // 10 = Personvern & Vilkår (Consent)
        
        foreach ($form['fields'] as &$field) {
            
            // Get current value from $_POST or GET
            $field_value = GFFormsModel::get_field_value($field, array());
            
            switch ($field->id) {
                case 1: // Verktøynavn
                    if (empty($field_value)) {
                        $field->defaultValue = $tool_name;
                    }
                    break;
                    
                case 3: // Detaljert beskrivelse (textarea/paragraph with RTE)
                    if (empty($field_value)) {
                        $field->defaultValue = $description;
                        error_log("BIM Verdi - Setting field 3 defaultValue to: " . substr($description, 0, 100));
                    }
                    break;
                    
                case 4: // Lenke til verktøy
                    if (empty($field_value)) {
                        $field->defaultValue = $tool_url;
                    }
                    break;
                    
                case 12: // Formålstema (radio) - CORRECTED from 5!
                    if ($formaalstema && empty($field_value)) {
                        if (!empty($field->choices)) {
                            foreach ($field->choices as &$choice) {
                                $choice['isSelected'] = ($choice['value'] === $formaalstema);
                                if ($choice['isSelected']) {
                                    error_log("BIM Verdi - Formålstema: Selected '{$choice['value']}'");
                                }
                            }
                        }
                    }
                    break;
                    
                case 13: // BIM-kompatibilitet (radio) - CORRECTED from 6!
                    if ($bim_kompatibilitet && empty($field_value)) {
                        if (!empty($field->choices)) {
                            foreach ($field->choices as &$choice) {
                                $choice['isSelected'] = ($choice['value'] === $bim_kompatibilitet);
                                if ($choice['isSelected']) {
                                    error_log("BIM Verdi - BIM-kompatibilitet: Selected '{$choice['value']}'");
                                }
                            }
                        }
                    }
                    break;
                    
                case 14: // Type ressurs (radio) - CORRECTED from 7!
                    if ($type_ressurs && empty($field_value)) {
                        if (!empty($field->choices)) {
                            foreach ($field->choices as &$choice) {
                                $choice['isSelected'] = ($choice['value'] === $type_ressurs);
                                if ($choice['isSelected']) {
                                    error_log("BIM Verdi - Type ressurs: Selected '{$choice['value']}'");
                                }
                            }
                        }
                    }
                    break;
                    
                case 15: // Type teknologi (radio) - CORRECTED from 8!
                    if ($type_teknologi && empty($field_value)) {
                        if (!empty($field->choices)) {
                            foreach ($field->choices as &$choice) {
                                $choice['isSelected'] = ($choice['value'] === $type_teknologi);
                                if ($choice['isSelected']) {
                                    error_log("BIM Verdi - Type teknologi: Selected '{$choice['value']}'");
                                }
                            }
                        }
                    }
                    break;
                    
                case 16: // Anvendelser (CHECKBOX) - CORRECTED from 9!
                    if (!empty($anvendelser) && is_array($anvendelser) && $field->type === 'checkbox') {
                        error_log("BIM Verdi - Prepopulating Anvendelser checkbox with " . count($anvendelser) . " values: " . print_r($anvendelser, true));
                        
                        // Checkbox fields have multiple inputs
                        if (!empty($field->inputs)) {
                            foreach ($field->inputs as &$input) {
                                // Each input represents one checkbox choice
                                // The input label contains the text, check against saved values
                                $input_label = isset($input['label']) ? $input['label'] : '';
                                
                                // Check if this choice is in the saved anvendelser array
                                if (in_array($input_label, $anvendelser)) {
                                    // Mark this checkbox as checked by setting defaultValue
                                    $input['defaultValue'] = $input_label;
                                    error_log("BIM Verdi - Checkbox input {$input['id']} defaultValue set to: '{$input_label}'");
                                }
                            }
                        }
                        
                        // Also set choices as selected (for rendering)
                        if (!empty($field->choices)) {
                            foreach ($field->choices as &$choice) {
                                if (in_array($choice['text'], $anvendelser) || in_array($choice['value'], $anvendelser)) {
                                    $choice['isSelected'] = true;
                                    error_log("BIM Verdi - Checkbox choice marked as selected: '{$choice['text']}'");
                                }
                            }
                        }
                    }
                    break;
            }
        }
        
        return $form;
    }
    
    /**
     * Pre-populate company field with user's company ID
     * 
     * @param array $form The form object
     * @return array Modified form object
     */
    public function prepopulate_company_field($form) {
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return $form;
        }
        
        $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
        if (!$company_id) {
            return $form;
        }
        
        // Populate both company and user ID fields
        foreach ($form['fields'] as &$field) {
            
            // FORETAK FIELD: Look for field with "Foretak" in label
            if (stripos($field->label, 'Foretak') !== false) {
                // Set default value to company ID
                $field->defaultValue = $company_id;
                $field->isRequired = false;
                
                // If it's a dropdown/select, populate with user's company
                if (in_array($field->type, array('select', 'multiselect', 'checkbox', 'radio'))) {
                    $company = get_post($company_id);
                    if ($company) {
                        $field->choices = array(
                            array(
                                'text' => $company->post_title,
                                'value' => $company_id,
                                'isSelected' => true
                            )
                        );
                    }
                }
            }
            
            // BRUKER-ID FIELD: Look for field with "Bruker" in label
            if (stripos($field->label, 'Bruker-ID') !== false || stripos($field->label, 'Bruker ID') !== false) {
                // Set default value to user ID
                $field->defaultValue = $user_id;
                $field->isRequired = false;
            }
        }
        
        return $form;
    }
    
    /**
     * Inject company ID into form submission
     * This ensures company_id is always set even if field doesn't exist
     * 
     * @param array $validation_result Validation result array
     * @return array Modified validation result
     */
    public function inject_company_id($validation_result) {
        
        $form = $validation_result['form'];
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return $validation_result;
        }
        
        $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
        if (!$company_id) {
            // User has no company - fail validation
            $validation_result['is_valid'] = false;
            return $validation_result;
        }
        
        // Find company field (Field ID 8 - CORRECTED from 13!)
        $company_field_id = null;
        foreach ($form['fields'] as $field) {
            if (
                $field->id == 8 ||
                (isset($field->inputName) && $field->inputName === 'company_id') ||
                (stripos($field->label, 'Foretak') !== false)
            ) {
                $company_field_id = $field->id;
                // Mark field as valid and set value
                $field->failed_validation = false;
                $field->validation_message = '';
                break;
            }
        }
        
        // Inject company_id into $_POST so it's saved with entry
        if ($company_field_id) {
            $_POST["input_{$company_field_id}"] = $company_id;
        }
        
        $validation_result['form'] = $form;
        return $validation_result;
    }
    
    /**
     * Custom validation for checkbox fields
     * 
     * Gravity Forms has a bug where required checkbox fields don't validate properly
     * This method manually validates that at least one checkbox is selected
     * 
     * @param array $validation_result Validation result array
     * @return array Modified validation result
     */
    public function validate_checkbox_fields($validation_result) {
        
        $form = $validation_result['form'];
        
        // Loop through all fields in the form
        foreach ($form['fields'] as &$field) {
            
            // Validate SPECIFIC checkbox field: "Anvendelser"
            // We check by label instead of isRequired because GF bug requires isRequired=false
            if ($field->type === 'checkbox' && $field->label === 'Anvendelser') {
                
                // Check if any checkbox is selected
                $has_selection = false;
                
                // Checkbox fields have multiple inputs (one per choice)
                // Check each input ID
                if (!empty($field->inputs) && is_array($field->inputs)) {
                    foreach ($field->inputs as $input) {
                        $input_id = str_replace('.', '_', $input['id']);
                        $value = rgpost("input_{$input_id}");
                        
                        if (!empty($value)) {
                            $has_selection = true;
                            error_log("BIM Verdi - Found checked checkbox: input_{$input_id} = {$value}");
                            break;
                        }
                    }
                }
                
                // If no checkbox is selected, mark field as invalid
                if (!$has_selection) {
                    $field->failed_validation = true;
                    $field->validation_message = 'Du må velge minst én anvendelse.';
                    $validation_result['is_valid'] = false;
                    
                    error_log("BIM Verdi - Checkbox validation FAILED: No anvendelser selected");
                } else {
                    error_log("BIM Verdi - Checkbox validation PASSED: At least one anvendelse selected");
                }
            }
        }
        
        $validation_result['form'] = $form;
        return $validation_result;
    }
    
    /**
     * Customize submit button text based on edit/create mode
     * 
     * Changes button text to "Oppdater verktøy" when editing,
     * or "Registrer verktøy" when creating new
     * 
     * @param string $button The button HTML
     * @param array $form The form object
     * @return string Modified button HTML
     */
    public function customize_submit_button($button, $form) {
        
        // Check if we're in edit mode (tool_id present in URL)
        $tool_id = isset($_GET['tool_id']) ? intval($_GET['tool_id']) : 0;
        
        if ($tool_id) {
            // EDIT MODE: Change button text to "Oppdater verktøy"
            $button = '<button type="submit" class="gform_button button btn btn-hjem-primary btn-lg" id="gform_submit_button_' . $form['id'] . '" style="display: inline-flex; align-items: center; justify-content: center;">';
            $button .= '<svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $button .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>';
            $button .= '</svg>';
            $button .= '<span>Oppdater verktøy</span>';
            $button .= '</button>';
        } else {
            // CREATE MODE: Default text "Registrer verktøy"
            $button = '<button type="submit" class="gform_button button btn btn-hjem-primary btn-lg" id="gform_submit_button_' . $form['id'] . '" style="display: inline-flex; align-items: center; justify-content: center;">';
            $button .= '<svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $button .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>';
            $button .= '</svg>';
            $button .= '<span>Registrer verktøy</span>';
            $button .= '</button>';
        }
        
        return $button;
    }
    
    /**
     * Handle tool registration form submission
     * 
     * Creates or updates a Verktøy (Tool) post linked to the user's company
     * 
     * @param array $entry The submitted form entry
     * @param array $form The form object
     */
    public function handle_submission($entry, $form) {
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            GFCommon::log_debug('Tool registration: User not logged in');
            return;
        }
        
        // Get user's company
        $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
        if (!$company_id) {
            GFCommon::log_debug('Tool registration: No company linked to user');
            error_log("BIM Verdi Tool Handler - User {$user_id} has no company linked");
            return;
        }
        
        // Log company info for debugging
        $company = get_post($company_id);
        error_log("BIM Verdi Tool Handler - User {$user_id} company: {$company_id} (" . ($company ? $company->post_title : 'NOT FOUND') . ")");
        
        // Extract form data
        $tool_data = $this->extract_form_data($entry);
        
        // Check if we're editing an existing tool (tool_id will be in field 10 or via GET parameter)
        $tool_id = !empty($tool_data['tool_id']) ? intval($tool_data['tool_id']) : 0;
        
        if ($tool_id) {
            // EDIT MODE: Update existing tool
            $existing_tool = get_post($tool_id);
            
            // Verify tool exists and user has permission to edit
            if ($existing_tool && $existing_tool->post_type === 'verktoy') {
                $can_edit = false;
                
                // Check if user is author
                if ($existing_tool->post_author == $user_id) {
                    $can_edit = true;
                }
                
                // Check if user's company owns the tool
                $tool_company = get_field('eier_leverandor', $tool_id);
                if ($company_id && $tool_company && $tool_company == $company_id) {
                    $can_edit = true;
                }
                
                if ($can_edit) {
                    // Update the post
                    $post_id = $this->update_tool_post($tool_id, $tool_data, $user_id);
                    
                    if (!is_wp_error($post_id) && $post_id) {
                        // Save ACF fields
                        $this->save_acf_fields($post_id, $tool_data, $company_id);
                        
                        // Handle image upload
                        if (!empty($tool_data['image_url'])) {
                            $this->handle_image_upload($tool_data['image_url'], $post_id);
                        }
                        
                        // Set taxonomy
                        if (!empty($tool_data['category'])) {
                            wp_set_object_terms($post_id, sanitize_text_field($tool_data['category']), 'verktoykategori');
                        }
                        
                        GFCommon::log_debug('Tool updated successfully: ' . $post_id);
                        error_log("BIM Verdi Tool Handler - Tool {$post_id} updated by user {$user_id}");
                    }
                } else {
                    error_log("BIM Verdi Tool Handler - User {$user_id} does not have permission to edit tool {$tool_id}");
                }
            }
        } else {
            // CREATE MODE: Create new tool
            $post_id = $this->create_tool_post($tool_data, $user_id, $company_id);
            
            if (!is_wp_error($post_id) && $post_id) {
                // Save ACF fields
                $this->save_acf_fields($post_id, $tool_data, $company_id);
                
                // Handle image upload
                if (!empty($tool_data['image_url'])) {
                    $this->handle_image_upload($tool_data['image_url'], $post_id);
                }
                
                // Set taxonomy
                if (!empty($tool_data['category'])) {
                    wp_set_object_terms($post_id, sanitize_text_field($tool_data['category']), 'verktoykategori');
                }
                
                GFCommon::log_debug('Tool created successfully: ' . $post_id);
            }
        }
    }
    
    /**
     * Extract data from form entry
     * 
     * @param array $entry The form entry
     * @return array Extracted form data
     */
    private function extract_form_data($entry) {
        // Get company_id - either from form field or from user meta
        $company_id_from_form = rgar($entry, '8'); // Field ID 8: Foretak - CORRECTED!
        
        // If form sends user_id instead of company_id, get company from user meta
        $user_id = get_current_user_id();
        $company_id_from_user = get_user_meta($user_id, 'bim_verdi_company_id', true);
        
        // Use company_id from user meta if form value is missing or equals user_id
        $company_id = $company_id_from_form;
        if (empty($company_id) || $company_id == $user_id) {
            $company_id = $company_id_from_user;
            error_log("BIM Verdi Tool Handler - Field 13 had wrong value (user_id?), using company from user meta: {$company_id}");
        }
        
        // Get tool_id if editing (Field ID 11 - the hidden field we added)
        $tool_id = rgar($entry, '11'); // Field ID 11: tool_id (hidden) - CORRECTED!
        if (empty($tool_id)) {
            // Also check GET parameter as backup
            $tool_id = isset($_GET['tool_id']) ? intval($_GET['tool_id']) : 0;
        }
        
        // Get Anvendelser checkbox field (Field ID 16 - CORRECTED!)
        // Gravity Forms stores checkbox values as separate input IDs
        $anvendelser = array();
        foreach ($entry as $key => $value) {
            // Checkbox inputs are stored as 16.1, 16.2, 16.3, etc.
            if (strpos($key, '16.') === 0 && !empty($value)) {
                $anvendelser[] = $value;
            }
        }
        
        // Debug: Log all entry values
        error_log("BIM Verdi - Entry data: Field 1=" . rgar($entry, '1') . ", Field 3=" . rgar($entry, '3') . ", Field 4=" . rgar($entry, '4'));
        error_log("BIM Verdi - Anvendelser: " . print_r($anvendelser, true));
        error_log("BIM Verdi - Formålstema (Field 12): " . rgar($entry, '12'));
        error_log("BIM Verdi - BIM-kompatibilitet (Field 13): " . rgar($entry, '13'));
        error_log("BIM Verdi - Type ressurs (Field 14): " . rgar($entry, '14'));
        error_log("BIM Verdi - Type teknologi (Field 15): " . rgar($entry, '15'));
        
        return array(
            'name' => rgar($entry, '1'),            // Field ID 1: Verktøynavn
            'description' => rgar($entry, '3'),     // Field ID 3: Detaljert beskrivelse (textarea/RTE)
            'url' => rgar($entry, '4'),             // Field ID 4: Lenke
            'image_url' => rgar($entry, '7'),       // Field ID 7: Logo/bilde (file upload)
            'formaalstema' => rgar($entry, '12'),   // Field ID 12: Formålstema (radio) - CORRECTED!
            'bim_kompatibilitet' => rgar($entry, '13'), // Field ID 13: BIM-kompatibilitet (radio) - CORRECTED!
            'type_ressurs' => rgar($entry, '14'),   // Field ID 14: Type ressurs (radio) - CORRECTED!
            'type_teknologi' => rgar($entry, '15'), // Field ID 15: Type teknologi (radio) - CORRECTED!
            'anvendelser' => $anvendelser,          // Field ID 16: Anvendelser (checkbox) - CORRECTED!
            'tool_id' => $tool_id,                  // Field ID 11: tool_id (for editing, hidden)
            'company_id' => $company_id,            // Field ID 8: Foretak (hidden) - CORRECTED!
            'price' => '',                          // Pris field removed from form
            'category' => '',                       // Kategori field removed from form
        );
    }
    
    /**
     * Create tool post in WordPress
     * 
     * @param array $tool_data Tool data from form
     * @param int $user_id The user creating the tool
     * @param int $company_id The company this tool belongs to
     * @return int|WP_Error Post ID or error object
     */
    private function create_tool_post($tool_data, $user_id, $company_id) {
        
        $post_id = wp_insert_post(array(
            'post_type' => 'verktoy',
            'post_title' => sanitize_text_field($tool_data['name']),
            'post_content' => '', // Keep empty - use ACF fields instead
            'post_excerpt' => '', // Keep empty - use ACF fields instead
            'post_status' => 'draft', // Requires admin approval
            'post_author' => $user_id,
        ));
        
        return $post_id;
    }
    
    /**
     * Update existing tool post in WordPress
     * 
     * @param int $post_id The post ID to update
     * @param array $tool_data Tool data from form
     * @param int $user_id The user updating the tool
     * @return int|WP_Error Post ID or error object
     */
    private function update_tool_post($post_id, $tool_data, $user_id) {
        
        // Get current post to preserve its status
        $current_post = get_post($post_id);
        $current_status = $current_post ? $current_post->post_status : 'draft';
        
        $updated = wp_update_post(array(
            'ID' => $post_id,
            'post_title' => sanitize_text_field($tool_data['name']),
            'post_content' => '', // Keep empty - use ACF fields instead
            'post_excerpt' => '', // Keep empty - use ACF fields instead
            'post_status' => $current_status, // Preserve existing status (don't change to draft)
        ));
        
        return $updated;
    }
    
    /**
     * Save ACF fields for tool post
     * 
     * @param int $post_id The post ID
     * @param array $tool_data Tool data
     * @param int $company_id Company ID from user meta (ALWAYS use this, not from form)
     */
    private function save_acf_fields($post_id, $tool_data, $company_id) {
        
        if (!function_exists('update_field')) {
            error_log("BIM Verdi Tool Handler - ACF not available!");
            return;
        }
        
        // Verify company exists before saving
        $company = get_post($company_id);
        if (!$company || $company->post_type !== 'foretak') {
            error_log("BIM Verdi Tool Handler - INVALID COMPANY ID: {$company_id}");
            return;
        }
        
        update_field('verktoy_navn', $tool_data['name'], $post_id);
        
        // Log what we're trying to save
        error_log("BIM Verdi - Saving description for tool {$post_id}: " . substr($tool_data['description'], 0, 100));
        
        // Save description - allow HTML since both Gravity Forms and ACF support it
        // Use wp_kses_post to allow safe HTML tags
        $clean_description = wp_kses_post($tool_data['description']);
        $description_saved = update_field('detaljert_beskrivelse', $clean_description, $post_id);
        error_log("BIM Verdi - Description saved: " . ($description_saved ? 'YES' : 'NO') . " (length: " . strlen($clean_description) . ")");
        
        update_field('verktoy_lenke', $tool_data['url'], $post_id);
        
        // CRITICAL: Set company/owner - ALWAYS use $company_id parameter (from user meta)
        // Field name is 'eier_leverandor' according to ACF JSON
        // Field type is 'post_object' so it expects a post ID (integer)
        // DO NOT use $tool_data['company_id'] as it might contain user_id by mistake
        $company_saved = update_field('eier_leverandor', intval($company_id), $post_id);
        
        if (!empty($tool_data['price'])) {
            update_field('verktoy_pris', $tool_data['price'], $post_id);
        }
        
        // Save BIM-specific fields (NEW FIELDS)
        if (!empty($tool_data['formaalstema'])) {
            $formaalstema_saved = update_field('formaalstema', sanitize_text_field($tool_data['formaalstema']), $post_id);
            error_log("BIM Verdi - Formålstema saved: " . ($formaalstema_saved ? 'YES' : 'NO') . " - Value: " . $tool_data['formaalstema']);
        }
        
        if (!empty($tool_data['bim_kompatibilitet'])) {
            $bim_saved = update_field('bim_kompatibilitet', sanitize_text_field($tool_data['bim_kompatibilitet']), $post_id);
            error_log("BIM Verdi - BIM-kompatibilitet saved: " . ($bim_saved ? 'YES' : 'NO') . " - Value: " . $tool_data['bim_kompatibilitet']);
        }
        
        if (!empty($tool_data['type_ressurs'])) {
            $ressurs_saved = update_field('type_ressurs', sanitize_text_field($tool_data['type_ressurs']), $post_id);
            error_log("BIM Verdi - Type ressurs saved: " . ($ressurs_saved ? 'YES' : 'NO') . " - Value: " . $tool_data['type_ressurs']);
        }
        
        if (!empty($tool_data['type_teknologi'])) {
            $teknologi_saved = update_field('type_teknologi', sanitize_text_field($tool_data['type_teknologi']), $post_id);
            error_log("BIM Verdi - Type teknologi saved: " . ($teknologi_saved ? 'YES' : 'NO') . " - Value: " . $tool_data['type_teknologi']);
        }
        
        // Save Anvendelser (checkbox field - array of values)
        if (!empty($tool_data['anvendelser']) && is_array($tool_data['anvendelser'])) {
            // Sanitize array values
            $clean_anvendelser = array_map('sanitize_text_field', $tool_data['anvendelser']);
            $anvendelser_saved = update_field('anvendelser', $clean_anvendelser, $post_id);
            error_log("BIM Verdi - Anvendelser saved: " . ($anvendelser_saved ? 'YES' : 'NO') . " - Count: " . count($clean_anvendelser));
            error_log("BIM Verdi - Anvendelser values: " . print_r($clean_anvendelser, true));
        }
        
        // Log for debugging with more details
        error_log(sprintf(
            "BIM Verdi Tool Handler - Tool %d: eier_leverandor=%d (%s), saved=%s, tool_data_company=%s",
            $post_id,
            $company_id,
            $company->post_title,
            $company_saved ? 'YES' : 'NO',
            isset($tool_data['company_id']) ? $tool_data['company_id'] : 'NULL'
        ));
    }
    
    /**
     * Handle image upload and attachment
     * 
     * @param string $image_url The image URL from Gravity Forms
     * @param int $post_id The post ID to attach image to
     * @return int|false Attachment ID or false
     */
    private function handle_image_upload($image_url, $post_id) {
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        // Download file to temp location
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            error_log('BIM Verdi Tool Handler: Failed to download image - ' . $tmp->get_error_message());
            return false;
        }
        
        $file_array = array(
            'name' => basename($image_url),
            'tmp_name' => $tmp
        );
        
        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, $post_id);
        
        // Cleanup
        if (file_exists($tmp)) {
            @unlink($tmp);
        }
        
        if (is_wp_error($attachment_id)) {
            error_log('BIM Verdi Tool Handler: Failed to create attachment - ' . $attachment_id->get_error_message());
            return false;
        }
        
        // Set as featured image and save to ACF
        set_post_thumbnail($post_id, $attachment_id);
        
        if (function_exists('update_field')) {
            update_field('verktoy_logo', $attachment_id, $post_id);
        }
        
        return $attachment_id;
    }
}
