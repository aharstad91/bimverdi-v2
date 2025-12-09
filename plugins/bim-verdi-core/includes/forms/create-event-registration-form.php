<?php
/**
 * Create Event Registration Form
 * 
 * Creates the Gravity Forms "[Bruker] - Påmelding arrangement" form
 * Run once to install the form, then it can be managed in GF admin
 * 
 * @package BIM_Verdi_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create the event registration form if it doesn't exist
 */
function bimverdi_create_event_registration_form() {
    // Check if GF is active
    if (!class_exists('GFAPI')) {
        return;
    }
    
    // Check if form already exists (ID 8)
    $existing_form = GFAPI::get_form(8);
    if ($existing_form && !is_wp_error($existing_form)) {
        return; // Form already exists
    }
    
    // Form configuration
    $form = array(
        'title' => '[Bruker] - Påmelding arrangement',
        'description' => 'Påmeldingsskjema for arrangementer. Forhåndsutfylles med brukerinfo.',
        'labelPlacement' => 'top_label',
        'descriptionPlacement' => 'below',
        'button' => array(
            'type' => 'text',
            'text' => 'Meld deg på',
            'imageUrl' => '',
        ),
        'fields' => array(
            // Hidden: User ID
            array(
                'type' => 'hidden',
                'id' => 1,
                'label' => 'User ID',
                'inputName' => 'user_id',
                'allowsPrepopulate' => true,
            ),
            // Hidden: Arrangement ID
            array(
                'type' => 'hidden',
                'id' => 2,
                'label' => 'Arrangement ID',
                'inputName' => 'arrangement_id',
                'cssClass' => 'arrangement-id',
                'allowsPrepopulate' => true,
            ),
            // Name (prepopulated)
            array(
                'type' => 'text',
                'id' => 3,
                'label' => 'Navn',
                'inputName' => 'user_name',
                'isRequired' => true,
                'allowsPrepopulate' => true,
                'cssClass' => 'gf_readonly',
            ),
            // Email (prepopulated)
            array(
                'type' => 'email',
                'id' => 4,
                'label' => 'E-post',
                'inputName' => 'user_email',
                'isRequired' => true,
                'allowsPrepopulate' => true,
                'cssClass' => 'gf_readonly',
            ),
            // Company (prepopulated, readonly)
            array(
                'type' => 'text',
                'id' => 5,
                'label' => 'Foretak',
                'inputName' => 'company_name',
                'isRequired' => false,
                'allowsPrepopulate' => true,
                'cssClass' => 'gf_readonly',
            ),
            // Phone (optional)
            array(
                'type' => 'phone',
                'id' => 6,
                'label' => 'Telefon',
                'inputName' => 'user_phone',
                'isRequired' => false,
                'allowsPrepopulate' => true,
                'phoneFormat' => 'standard',
                'description' => 'Valgfritt - brukes kun for å kontakte deg ved endringer',
            ),
            // Comment (optional)
            array(
                'type' => 'textarea',
                'id' => 7,
                'label' => 'Kommentar',
                'isRequired' => false,
                'maxLength' => 500,
                'description' => 'Valgfritt - f.eks. allergier, tilgjengelighet eller spørsmål',
            ),
        ),
        'confirmations' => array(
            array(
                'id' => 'default',
                'name' => 'Standard bekreftelse',
                'isDefault' => true,
                'type' => 'message',
                'message' => '<div class="gform_confirmation_wrapper"><wa-alert variant="success" open><wa-icon slot="icon" name="circle-check" library="fa"></wa-icon><strong>Du er nå påmeldt!</strong><br>Du vil motta en bekreftelse på e-post med alle detaljer.</wa-alert></div>',
            ),
        ),
        'notifications' => array(
            // User notification
            array(
                'id' => 'user_notification',
                'name' => 'Brukerbekreftelse',
                'event' => 'form_submission',
                'to' => '{user_email:4}',
                'toType' => 'field',
                'subject' => 'Bekreftelse: Du er påmeldt arrangementet',
                'message' => 'Hei {Navn:3},

Du er nå påmeldt arrangementet!

Arrangementet vil bli lagt til i kalenderen din via den vedlagte kalenderfilen (.ics).

Vi gleder oss til å se deg!

Med vennlig hilsen,
BIM Verdi',
                'from' => '{admin_email}',
                'fromName' => 'BIM Verdi',
                'replyTo' => '{admin_email}',
                'isActive' => true,
            ),
            // Admin notification
            array(
                'id' => 'admin_notification',
                'name' => 'Admin varsling',
                'event' => 'form_submission',
                'to' => '{admin_email}',
                'toType' => 'email',
                'subject' => 'Ny påmelding til arrangement',
                'message' => 'Ny påmelding til arrangement:

<strong>Navn:</strong> {Navn:3}
<strong>E-post:</strong> {E-post:4}
<strong>Foretak:</strong> {Foretak:5}
<strong>Telefon:</strong> {Telefon:6}
<strong>Kommentar:</strong> {Kommentar:7}

Tidspunkt påmeldt: {date_mdy} {time}',
                'from' => '{admin_email}',
                'fromName' => 'BIM Verdi',
                'isActive' => true,
            ),
        ),
        'requireLogin' => true,
        'requireLoginMessage' => 'Du må være innlogget for å melde deg på arrangementer.',
    );
    
    // Create the form
    $form_id = GFAPI::add_form($form);
    
    if (is_wp_error($form_id)) {
        error_log('BIM Verdi: Failed to create event registration form: ' . $form_id->get_error_message());
        return false;
    }
    
    // Update option to track that form was created
    update_option('bimverdi_event_form_created', true);
    
    error_log('BIM Verdi: Created event registration form with ID: ' . $form_id);
    
    return $form_id;
}

// Run on admin init (only once)
add_action('admin_init', function() {
    if (!get_option('bimverdi_event_form_created')) {
        bimverdi_create_event_registration_form();
    }
});

// Also add manual creation via admin action
add_action('admin_post_bimverdi_create_event_form', function() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    delete_option('bimverdi_event_form_created');
    $result = bimverdi_create_event_registration_form();
    
    if ($result) {
        wp_redirect(admin_url('admin.php?page=gf_edit_forms&id=' . $result));
    } else {
        wp_redirect(admin_url('admin.php?page=gf_edit_forms&error=form_creation_failed'));
    }
    exit;
});
