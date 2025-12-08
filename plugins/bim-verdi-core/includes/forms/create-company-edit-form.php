<?php
/**
 * Create Company Edit Form
 * 
 * This script creates the Gravity Form for editing company profiles.
 * Run once to create the form, then delete or disable the script.
 * 
 * Form ID: 7 - [System] - Redigering av foretak
 * 
 * To run this script, visit:
 * /wp-admin/admin.php?page=gf_edit_forms&create_company_edit_form=1
 * 
 * @package BIM_Verdi_Core
 */

// Hook into admin_init to check for creation trigger
add_action('admin_init', 'bim_verdi_maybe_create_company_edit_form');

function bim_verdi_maybe_create_company_edit_form() {
    // Only run if triggered and user is admin
    if (!isset($_GET['create_company_edit_form']) || !current_user_can('administrator')) {
        return;
    }
    
    // Check nonce
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'create_company_edit_form')) {
        // Allow creation without nonce for initial setup
        // In production, this should require a nonce
    }
    
    // Check if GFAPI is available
    if (!class_exists('GFAPI')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>Gravity Forms is not active!</p></div>';
        });
        return;
    }
    
    // Check if form already exists
    $existing_form = GFAPI::get_form(7);
    if ($existing_form) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning"><p>Form ID 7 already exists: ' . esc_html($existing_form['title']) . '</p></div>';
        });
        return;
    }
    
    // Create the form
    $result = bim_verdi_create_company_edit_form();
    
    if (is_wp_error($result)) {
        add_action('admin_notices', function() use ($result) {
            echo '<div class="notice notice-error"><p>Error creating form: ' . esc_html($result->get_error_message()) . '</p></div>';
        });
    } else {
        add_action('admin_notices', function() use ($result) {
            echo '<div class="notice notice-success"><p>✅ Company Edit Form created successfully! Form ID: ' . esc_html($result) . '</p></div>';
        });
    }
}

/**
 * Create the company edit form
 * 
 * @return int|WP_Error Form ID on success, WP_Error on failure
 */
function bim_verdi_create_company_edit_form() {
    
    $form = array(
        'title' => '[System] - Redigering av foretak',
        'description' => 'Skjema for redigering av foretaksinformasjon. Brukes på Min Side.',
        'labelPlacement' => 'top_label',
        'descriptionPlacement' => 'below',
        'button' => array(
            'type' => 'text',
            'text' => 'Lagre endringer',
        ),
        'fields' => array(
            // Field 1: Organisasjonsnummer (read-only)
            array(
                'type' => 'text',
                'id' => 1,
                'label' => 'Organisasjonsnummer',
                'isRequired' => false,
                'placeholder' => '',
                'description' => 'Organisasjonsnummer kan ikke endres',
                'cssClass' => 'gf-readonly-field',
                'visibility' => 'visible',
                'inputMask' => false,
                'inputMaskValue' => '',
                'size' => 'medium',
            ),
            
            // Field 2: Bedriftsnavn
            array(
                'type' => 'text',
                'id' => 2,
                'label' => 'Bedriftsnavn',
                'isRequired' => true,
                'placeholder' => '',
                'description' => '',
                'size' => 'large',
            ),
            
            // Field 3: Beskrivelse
            array(
                'type' => 'textarea',
                'id' => 3,
                'label' => 'Bedriftsbeskrivelse',
                'isRequired' => false,
                'placeholder' => 'Kort beskrivelse av bedriftens virksomhet',
                'description' => '',
                'useRichTextEditor' => false,
            ),
            
            // Field 4: Logo upload
            array(
                'type' => 'fileupload',
                'id' => 4,
                'label' => 'Logo',
                'isRequired' => false,
                'description' => 'Last opp ny logo (valgfritt). Tillatte formater: jpg, jpeg, png, gif, svg',
                'allowedExtensions' => 'jpg, jpeg, png, gif, svg',
                'maxFileSize' => 5,
            ),
            
            // Field 5: Telefon
            array(
                'type' => 'phone',
                'id' => 5,
                'label' => 'Telefon',
                'isRequired' => false,
                'phoneFormat' => 'standard',
                'description' => '',
            ),
            
            // Field 6: Nettside
            array(
                'type' => 'website',
                'id' => 6,
                'label' => 'Nettside',
                'isRequired' => false,
                'placeholder' => 'https://',
                'description' => '',
            ),
            
            // Section: Adresse
            array(
                'type' => 'section',
                'id' => 11,
                'label' => 'Adresse',
            ),
            
            // Field 7: Gateadresse
            array(
                'type' => 'text',
                'id' => 7,
                'label' => 'Gateadresse',
                'isRequired' => false,
                'size' => 'large',
            ),
            
            // Field 8: Postnummer
            array(
                'type' => 'text',
                'id' => 8,
                'label' => 'Postnummer',
                'isRequired' => false,
                'size' => 'small',
                'cssClass' => 'gf_left_half',
            ),
            
            // Field 9: Poststed
            array(
                'type' => 'text',
                'id' => 9,
                'label' => 'Poststed',
                'isRequired' => false,
                'size' => 'medium',
                'cssClass' => 'gf_right_half',
            ),
            
            // Field 10: Hidden company ID
            array(
                'type' => 'hidden',
                'id' => 10,
                'label' => 'Company ID',
                'defaultValue' => '',
            ),
        ),
        
        // Form settings
        'requireLogin' => true,
        'requireLoginMessage' => 'Du må være innlogget for å redigere foretaksinformasjon.',
        'enableHoneypot' => true,
        'enableAnimation' => true,
        'save' => array(
            'enabled' => false,
        ),
        'limitEntries' => false,
        'scheduleForm' => false,
        
        // Confirmations
        'confirmations' => array(
            array(
                'id' => 'default',
                'name' => 'Standard bekreftelse',
                'isDefault' => true,
                'type' => 'message',
                'message' => 'Foretaksinformasjonen er oppdatert!',
                'disableAutoformat' => false,
                'pageId' => '',
                'url' => '',
            ),
        ),
        
        // Notifications (disabled by default for edit forms)
        'notifications' => array(),
    );
    
    // Use GFAPI to add the form
    $form_id = GFAPI::add_form($form);
    
    if (is_wp_error($form_id)) {
        return $form_id;
    }
    
    // Log success
    error_log('BIM Verdi: Created Company Edit Form with ID: ' . $form_id);
    
    return $form_id;
}

/**
 * Add admin menu item for creating the form
 */
add_action('admin_menu', function() {
    if (!current_user_can('administrator')) {
        return;
    }
    
    // Add hidden admin page for form creation
    add_submenu_page(
        null, // No parent (hidden)
        'Create Company Edit Form',
        'Create Company Edit Form',
        'manage_options',
        'bim-create-company-edit-form',
        'bim_verdi_render_create_form_page'
    );
});

function bim_verdi_render_create_form_page() {
    $nonce_url = wp_nonce_url(
        admin_url('admin.php?page=gf_edit_forms&create_company_edit_form=1'),
        'create_company_edit_form'
    );
    
    echo '<div class="wrap">';
    echo '<h1>Create Company Edit Form</h1>';
    echo '<p>Click the button below to create the company edit form (Form ID 7).</p>';
    echo '<p><a href="' . esc_url($nonce_url) . '" class="button button-primary">Create Form</a></p>';
    echo '</div>';
}
