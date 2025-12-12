<?php
/**
 * Create Article Form
 * 
 * Creates the Gravity Form for writing/submitting articles
 * 
 * @package BIM_Verdi_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create the article submission form
 */
function bimverdi_create_article_form() {
    if (!class_exists('GFAPI')) {
        return false;
    }
    
    // Check if form already exists
    $forms = GFAPI::get_forms();
    foreach ($forms as $form) {
        if (strpos($form['title'], 'Skriv artikkel') !== false) {
            return $form['id'];
        }
    }
    
    $form = array(
        'title' => '[Bruker] - Skriv artikkel',
        'description' => 'Skriv og del artikler for kunnskapsdeling i BIM Verdi',
        'labelPlacement' => 'top_label',
        'button' => array(
            'type' => 'text',
            'text' => 'Send til godkjenning',
        ),
        'fields' => array(
            // Section: Article content
            array(
                'type' => 'section',
                'id' => 1,
                'label' => 'Artikkelinnhold',
                'description' => 'Skriv din artikkel nedenfor',
            ),
            // Title
            array(
                'type' => 'text',
                'id' => 2,
                'label' => 'Tittel',
                'isRequired' => true,
                'size' => 'large',
                'placeholder' => 'En beskrivende tittel for artikkelen',
                'maxLength' => 150,
            ),
            // Ingress
            array(
                'type' => 'textarea',
                'id' => 3,
                'label' => 'Ingress',
                'description' => 'Kort oppsummering som vises i lister og som intro (maks 300 tegn)',
                'isRequired' => true,
                'size' => 'medium',
                'maxLength' => 300,
                'placeholder' => 'Oppsummer artikkelen i 1-2 setninger...',
            ),
            // Content with WYSIWYG
            array(
                'type' => 'post_content',
                'id' => 4,
                'label' => 'Innhold',
                'description' => 'Skriv artikkelen her. Du kan formatere tekst, legge til lenker og lister.',
                'isRequired' => true,
                'size' => 'large',
                'useRichTextEditor' => true,
            ),
            // Section: Kategorisering
            array(
                'type' => 'section',
                'id' => 5,
                'label' => 'Kategorisering',
                'description' => 'Hjelp oss å kategorisere artikkelen',
            ),
            // Category
            array(
                'type' => 'select',
                'id' => 6,
                'label' => 'Type artikkel',
                'isRequired' => true,
                'choices' => array(
                    array('text' => 'Fagartikkel', 'value' => 'fagartikkel'),
                    array('text' => 'Case / Prosjekthistorie', 'value' => 'case'),
                    array('text' => 'Tips og triks', 'value' => 'tips'),
                    array('text' => 'Nyhet', 'value' => 'nyhet'),
                    array('text' => 'Kommentar / Kronikk', 'value' => 'kommentar'),
                ),
                'placeholder' => 'Velg type...',
            ),
            // Temagruppe
            array(
                'type' => 'checkbox',
                'id' => 7,
                'label' => 'Relevant temagruppe',
                'description' => 'Velg temagrupper artikkelen er relevant for (valgfritt)',
                'isRequired' => false,
                'choices' => array(
                    array('text' => 'ByggesaksBIM', 'value' => 'byggesaksbim'),
                    array('text' => 'ProsjektBIM', 'value' => 'prosjektbim'),
                    array('text' => 'EiendomsBIM', 'value' => 'eiendomsbim'),
                    array('text' => 'MiljøBIM', 'value' => 'miljobim'),
                    array('text' => 'SirkBIM', 'value' => 'sirkbim'),
                    array('text' => 'BIMtech', 'value' => 'bimtech'),
                ),
            ),
            // Hidden: User ID
            array(
                'type' => 'hidden',
                'id' => 8,
                'label' => 'Bruker ID',
                'defaultValue' => '{user:id}',
            ),
            // Hidden: Company ID
            array(
                'type' => 'hidden',
                'id' => 9,
                'label' => 'Foretak ID',
                'defaultValue' => '{user:bim_verdi_company_id}',
            ),
            // Save as draft option
            array(
                'type' => 'checkbox',
                'id' => 10,
                'label' => 'Lagre som utkast?',
                'description' => 'Kryss av for å lagre som utkast i stedet for å sende til godkjenning',
                'isRequired' => false,
                'choices' => array(
                    array('text' => 'Ja, lagre som utkast (jeg vil redigere mer senere)', 'value' => 'utkast'),
                ),
            ),
        ),
        'confirmations' => array(
            array(
                'id' => 'default',
                'name' => 'Standard bekreftelse',
                'isDefault' => true,
                'type' => 'message',
                'message' => '<div class="gform-confirmation-success">
                    <h3>✅ Artikkelen er sendt!</h3>
                    <p>Takk for bidraget! Artikkelen er sendt til godkjenning og vil bli vurdert av BIM Verdi-teamet.</p>
                    <p>Du kan følge status på <a href="/min-side/artikler/">dine artikler</a>.</p>
                </div>',
                'queryString' => '',
                'pageId' => '',
                'url' => '',
            ),
        ),
        'notifications' => array(
            // Admin notification
            array(
                'id' => 'admin_notification',
                'name' => 'Ny artikkel til godkjenning',
                'isActive' => true,
                'to' => '{admin_email}',
                'toType' => 'email',
                'subject' => 'Ny artikkel til godkjenning: {Tittel:2}',
                'message' => 'En ny artikkel er sendt til godkjenning.

Tittel: {Tittel:2}
Innsendt av: {user:display_name}
Foretak ID: {Foretak ID:9}
Type: {Type artikkel:6}

Se artikkelen i admin: {admin_url}

Ingress:
{Ingress:3}',
            ),
            // User confirmation
            array(
                'id' => 'user_notification',
                'name' => 'Bekreftelse til forfatter',
                'isActive' => true,
                'to' => '{user:user_email}',
                'toType' => 'email',
                'subject' => 'Artikkelen din er mottatt - {Tittel:2}',
                'message' => 'Hei {user:display_name}!

Takk for artikkelen "{Tittel:2}".

Den er nå sendt til gjennomgang av BIM Verdi-teamet. Du vil få beskjed når artikkelen er publisert eller om vi har tilbakemeldinger.

Du kan følge status på artiklene dine her:
{site_url}/min-side/artikler/

Med vennlig hilsen,
BIM Verdi',
            ),
        ),
        'requireLogin' => true,
        'requireLoginMessage' => 'Du må være innlogget for å skrive artikler.',
    );
    
    $result = GFAPI::add_form($form);
    
    if (is_wp_error($result)) {
        error_log('BIM Verdi: Kunne ikke opprette artikkelskjema - ' . $result->get_error_message());
        return false;
    }
    
    return $result;
}

// Auto-create form on admin init
add_action('admin_init', function() {
    if (isset($_GET['bimverdi_create_article_form']) && current_user_can('manage_options')) {
        $form_id = bimverdi_create_article_form();
        if ($form_id) {
            wp_redirect(admin_url('admin.php?page=gf_edit_forms&id=' . $form_id));
            exit;
        }
    }
});
