<?php
/**
 * One-time script: Create Newsletter Gravity Form on production
 * DELETE THIS FILE after running once.
 * Visit any page as admin to trigger.
 */
if (!defined('ABSPATH')) exit;

add_action('init', function() {
    if (!current_user_can('manage_options')) return;
    if (get_option('bimverdi_newsletter_gf_created')) return;
    if (!class_exists('GFAPI')) return;

    $form = array(
        'title'       => '[Public] - Nyhetsbrev',
        'description' => 'Footer newsletter signup',
        'fields'      => array(
            new GF_Field_Email(array(
                'id'          => 1,
                'label'       => 'E-postadresse',
                'isRequired'  => true,
                'placeholder' => 'Din e-postadresse',
                'size'        => 'large',
            )),
        ),
        'button'       => array('type' => 'text', 'text' => 'Meld på'),
        'confirmations' => array(
            array(
                'id'      => 'default',
                'name'    => 'Standard bekreftelse',
                'type'    => 'message',
                'message' => 'Takk for din påmelding! Du vil motta nyheter og invitasjoner fra oss.',
                'isDefault' => true,
            ),
        ),
        'notifications' => array(
            array(
                'id'       => 'admin_notification',
                'name'     => 'Admin-varsel',
                'event'    => 'form_submission',
                'to'       => '{admin_email}',
                'toType'   => 'email',
                'subject'  => 'Ny nyhetsbrev-påmelding: {E-postadresse:1}',
                'message'  => "Ny påmelding til BIM Verdi nyhetsbrev:\n\nE-post: {E-postadresse:1}\nDato: {date_mdy}",
                'isActive' => true,
            ),
        ),
    );

    $form_id = GFAPI::add_form($form);

    if (!is_wp_error($form_id)) {
        update_option('bimverdi_newsletter_gf_created', $form_id);
    }
});
