<?php
/**
 * ACF User Profile Fields
 * 
 * Registers ACF field group for user profiles.
 * Fields appear in wp-admin/user-edit.php automatically.
 * Also used by acf_form() on frontend for "Min Profil" page.
 * 
 * Single source of truth for user profile data:
 * - Synchronized from Gravity Forms registration
 * - Editable from WordPress Admin user page
 * - Editable from frontend via acf_form()
 * - Queryable via ACF and WP_Query
 * 
 * @package BIM_Verdi_Core
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('acf_add_local_field_group')) {
    return;
}

/**
 * Register ACF field group on ACF init hook
 */
add_action('acf/init', function() {
    
    acf_add_local_field_group(array(
        'key' => 'group_bim_verdi_user_profile',
        'title' => 'BIM Verdi - Bruker Profil',
        'fields' => array(
            // Phone Number
            array(
                'key' => 'field_user_phone',
                'label' => 'Telefon',
                'name' => 'phone',
                'type' => 'text',
                'instructions' => 'Mobilnummer eller fasttelefon',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '33.33',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => '+47 91 23 45 67',
                'prepend' => '📞',
                'append' => '',
            ),
            
            // Job Title
            array(
                'key' => 'field_user_job_title',
                'label' => 'Tittel/Stilling',
                'name' => 'job_title',
                'type' => 'text',
                'instructions' => 'Din stilling eller rolle i bedriften',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '33.33',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => 'f.eks. Prosjektleder, Arkitekt',
                'prepend' => '💼',
                'append' => '',
            ),
            
            // LinkedIn URL
            array(
                'key' => 'field_user_linkedin',
                'label' => 'LinkedIn URL',
                'name' => 'linkedin_url',
                'type' => 'url',
                'instructions' => 'Link til din LinkedIn profil (valgfritt)',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '33.33',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => 'https://linkedin.com/in/yourprofile',
                'prepend' => '🔗',
                'append' => '',
            ),

            // Middle Name
            array(
                'key' => 'field_user_middle_name',
                'label' => 'Mellomnavn',
                'name' => 'middle_name',
                'type' => 'text',
                'instructions' => 'Valgfritt mellomnavn',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '33.33',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => '',
            ),

            // Profile Image
            array(
                'key' => 'field_user_profile_image',
                'label' => 'Personbilde',
                'name' => 'profile_image',
                'type' => 'image',
                'instructions' => 'Last opp et profilbilde (maks 2 MB, jpg/png/webp)',
                'required' => 0,
                'conditional_logic' => 0,
                'return_format' => 'id',
                'preview_size' => 'thumbnail',
                'library' => 'all',
                'min_width' => 0,
                'min_height' => 0,
                'max_width' => 0,
                'max_height' => 0,
                'min_size' => '',
                'max_size' => 2,
                'mime_types' => 'jpg, jpeg, png, webp',
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
            ),

            // Registration Background
            array(
                'key' => 'field_user_registration_background',
                'label' => 'Bakgrunn for registrering',
                'name' => 'registration_background',
                'type' => 'checkbox',
                'instructions' => 'Hva er bakgrunnen for at du registrerte deg?',
                'required' => 0,
                'conditional_logic' => 0,
                'choices' => array(
                    'oppdatering' => 'Dette er en oppdatering - jeg er allerede registrert',
                    'tilleggskontakt' => 'Min arbeidsgiver er deltaker og jeg er ny tilleggskontakt',
                    'arrangement' => 'Gjelder registrering for arrangement-deltakelse',
                    'nyhetsbrev' => 'Jeg ønsker å motta nyhetsbrev fra BIM Verdi',
                    'deltaker_verktoy' => 'Deltakerregistrering og digitale verktøy',
                    'mote' => 'Ønsker å avtale et møte',
                ),
                'default_value' => array(),
                'layout' => 'vertical',
                'toggle' => 0,
                'return_format' => 'value',
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),

            // Topic Interests
            array(
                'key' => 'field_user_topic_interests',
                'label' => 'Interesse for temaene',
                'name' => 'topic_interests',
                'type' => 'checkbox',
                'instructions' => 'Velg de temagruppene du er interessert i',
                'required' => 0,
                'conditional_logic' => 0,
                'choices' => array(
                    'byggesaksbim' => 'ByggesaksBIM',
                    'prosjektbim' => 'ProsjektBIM',
                    'eiendomsbim' => 'EiendomsBIM',
                    'miljobim' => 'MiljøBIM',
                    'sirkbim' => 'SirkBIM',
                    'bimtech' => 'BIMtech',
                ),
                'default_value' => array(),
                'layout' => 'vertical',
                'toggle' => 0,
                'return_format' => 'value',
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'user_form',
                    'operator' => '==',
                    'value' => 'edit',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => 'Brukerprofilinformasjon - synkroniseres automatisk fra Gravity Forms registrering. Redigerbar fra admin og frontend.',
    ));
    
});
