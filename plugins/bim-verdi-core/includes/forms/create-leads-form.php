<?php
/**
 * Create Leads Generator Form - Søknadsbistand
 * 
 * Creates the Gravity Forms "[Public] - Søknadsbistand prosjektidé" form
 * Based on Innovasjon Norge "Rask avklaring" structure
 * 
 * Run once via: /wp-admin/?bimverdi_create_leads_form=1
 * 
 * @package BIM_Verdi_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if we should create the leads form
 */
add_action('admin_init', function() {
    if (!isset($_GET['bimverdi_create_leads_form']) || $_GET['bimverdi_create_leads_form'] !== '1') {
        return;
    }
    
    if (!current_user_can('manage_options')) {
        wp_die('Ingen tilgang');
    }
    
    if (!class_exists('GFAPI')) {
        add_settings_error('bimverdi', 'gf_missing', 'Gravity Forms er ikke installert.', 'error');
        return;
    }
    
    $result = bimverdi_create_leads_form();
    
    if ($result['success']) {
        set_transient('bimverdi_leads_form_created', $result['form_id'], 60);
        wp_redirect(admin_url('admin.php?page=gf_edit_forms&id=' . $result['form_id']));
        exit;
    } else {
        wp_die('Feil ved oppretting: ' . $result['message']);
    }
});

/**
 * Show admin notice after form creation
 */
add_action('admin_notices', function() {
    $form_id = get_transient('bimverdi_leads_form_created');
    if ($form_id) {
        delete_transient('bimverdi_leads_form_created');
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>Leads-skjema opprettet!</strong> Form ID: ' . esc_html($form_id) . '</p>';
        echo '</div>';
    }
});

/**
 * Create the leads/søknadsbistand form
 * 
 * @return array Result with success status and message/form_id
 */
function bimverdi_create_leads_form() {
    // Check if form already exists (by title search)
    $existing_forms = GFAPI::get_forms();
    foreach ($existing_forms as $form) {
        if (strpos($form['title'], 'Søknadsbistand') !== false) {
            return array(
                'success' => false,
                'message' => 'Skjema for søknadsbistand eksisterer allerede (ID: ' . $form['id'] . ')',
            );
        }
    }
    
    $form = array(
        'title' => '[Public] - Søknadsbistand prosjektidé',
        'description' => 'Offentlig tilgjengelig skjema for innsending av prosjektidéer. Basert på Innovasjon Norge "Rask avklaring".',
        'labelPlacement' => 'top_label',
        'descriptionPlacement' => 'below',
        'button' => array(
            'type' => 'text',
            'text' => 'Send inn prosjektidé',
            'imageUrl' => '',
        ),
        'fields' => array(
            // Section: Kontaktinformasjon
            array(
                'type' => 'section',
                'id' => 100,
                'label' => 'Kontaktinformasjon',
                'description' => 'Fyll inn dine kontaktdetaljer så vi kan nå deg.',
            ),
            
            // Field 1: Kontaktnavn
            array(
                'type' => 'text',
                'id' => 1,
                'label' => 'Navn',
                'inputName' => 'lead_name',
                'isRequired' => true,
                'placeholder' => 'Ola Nordmann',
                'allowsPrepopulate' => true,
                'cssClass' => 'medium',
            ),
            
            // Field 2: E-post
            array(
                'type' => 'email',
                'id' => 2,
                'label' => 'E-post',
                'inputName' => 'lead_email',
                'isRequired' => true,
                'placeholder' => 'ola@bedrift.no',
                'allowsPrepopulate' => true,
                'cssClass' => 'medium',
            ),
            
            // Field 3: Foretak/bedrift
            array(
                'type' => 'text',
                'id' => 3,
                'label' => 'Foretak / Bedrift',
                'inputName' => 'lead_company',
                'isRequired' => true,
                'placeholder' => 'Bedriftsnavn AS',
                'allowsPrepopulate' => true,
                'cssClass' => 'medium',
            ),
            
            // Field 4: Telefon
            array(
                'type' => 'phone',
                'id' => 4,
                'label' => 'Telefon',
                'inputName' => 'lead_phone',
                'isRequired' => false,
                'placeholder' => '123 45 678',
                'phoneFormat' => 'standard',
                'allowsPrepopulate' => true,
                'cssClass' => 'medium',
            ),
            
            // Section: Prosjektidé
            array(
                'type' => 'section',
                'id' => 101,
                'label' => 'Om prosjektidéen',
                'description' => 'Beskriv din prosjektidé. Jo mer informasjon, jo bedre kan vi vurdere og bistå deg.',
            ),
            
            // Field 5: Prosjekttittel
            array(
                'type' => 'text',
                'id' => 5,
                'label' => 'Prosjekttittel',
                'isRequired' => true,
                'placeholder' => 'Kort og beskrivende tittel på prosjektet',
                'cssClass' => 'large',
                'description' => 'Gi prosjektet et kort og beskrivende navn.',
            ),
            
            // Field 6: Kort beskrivelse av idé
            array(
                'type' => 'textarea',
                'id' => 6,
                'label' => 'Kort beskrivelse av idé',
                'isRequired' => true,
                'placeholder' => 'Beskriv prosjektidéen din kort og konsist...',
                'maxLength' => 500,
                'cssClass' => 'large',
                'description' => 'Maks 500 tegn. Hva ønsker du å oppnå?',
            ),
            
            // Field 7: Nytte for bedrift
            array(
                'type' => 'textarea',
                'id' => 7,
                'label' => 'Nytte for bedriften',
                'isRequired' => true,
                'placeholder' => 'Hvordan vil dette prosjektet bidra til din bedrifts utvikling?',
                'cssClass' => 'large',
                'description' => 'Beskriv forventet verdi for din bedrift (økonomisk, kompetanse, markedsposisjon, etc.)',
            ),
            
            // Field 8: Nytte for samfunn
            array(
                'type' => 'textarea',
                'id' => 8,
                'label' => 'Nytte for samfunnet',
                'isRequired' => true,
                'placeholder' => 'Hvordan kan prosjektet bidra til samfunnet utover din egen bedrift?',
                'cssClass' => 'large',
                'description' => 'Tenk bærekraft, verdiskaping, effektivisering i bransjen, etc.',
            ),
            
            // Field 9: Behov for ny kunnskap/FoU
            array(
                'type' => 'textarea',
                'id' => 9,
                'label' => 'Behov for ny kunnskap / FoU',
                'isRequired' => true,
                'placeholder' => 'Hva må utvikles av ny kunnskap eller teknologi for å gjennomføre prosjektet?',
                'cssClass' => 'large',
                'description' => 'Beskriv hva som er nytt eller innovativt i prosjektet.',
            ),
            
            // Field 10: Mulige partnere
            array(
                'type' => 'textarea',
                'id' => 10,
                'label' => 'Mulige partnere',
                'isRequired' => false,
                'placeholder' => 'Hvem kan være aktuelle samarbeidspartnere? (Bedrifter, forskningsmiljøer, etc.)',
                'cssClass' => 'large',
                'description' => 'Valgfritt. Liste over potensielle partnere som kan være interessert.',
            ),
            
            // Field 11: Tidsramme
            array(
                'type' => 'select',
                'id' => 11,
                'label' => 'Tidsramme',
                'isRequired' => true,
                'choices' => array(
                    array('text' => '-- Velg tidsramme --', 'value' => ''),
                    array('text' => 'Under 6 måneder', 'value' => 'under_6_mnd'),
                    array('text' => '6-12 måneder', 'value' => '6_12_mnd'),
                    array('text' => '1-2 år', 'value' => '1_2_ar'),
                    array('text' => 'Over 2 år', 'value' => 'over_2_ar'),
                    array('text' => 'Usikker', 'value' => 'usikker'),
                ),
                'cssClass' => 'medium',
                'description' => 'Anslått tidshorisont for prosjektet.',
            ),
            
            // Field 12: Hidden - User ID (if logged in)
            array(
                'type' => 'hidden',
                'id' => 12,
                'label' => 'User ID',
                'inputName' => 'user_id',
                'allowsPrepopulate' => true,
            ),
            
            // Section: CTA for non-members
            array(
                'type' => 'html',
                'id' => 102,
                'label' => 'Medlemskap CTA',
                'content' => '<div class="gf-member-cta" style="background: #FDF6E3; border: 1px solid #F97316; border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                    <p style="margin: 0; color: #1F2937;">
                        <strong>💡 Visste du?</strong> Som medlem av BIM Verdi får du tilgang til:
                    </p>
                    <ul style="margin: 0.5rem 0; padding-left: 1.5rem; color: #4B5563;">
                        <li>Prioritert behandling av prosjektidéer</li>
                        <li>Nettverksbygging med andre BIM-aktører</li>
                        <li>Deltakelse i pilotprosjekter</li>
                        <li>Tilgang til verktøykatalog og faglige ressurser</li>
                    </ul>
                    <p style="margin: 0; margin-top: 0.5rem;">
                        <a href="/bli-medlem/" style="color: #F97316; font-weight: 600;">Les mer om medlemskap →</a>
                    </p>
                </div>',
                'cssClass' => 'member-cta-section',
            ),
        ),
        'cssClass' => 'bimverdi-leads-form',
        'enableHoneypot' => true,
        'enableAnimation' => false,
        'confirmations' => array(
            array(
                'id' => '1',
                'name' => 'Standard bekreftelse',
                'isDefault' => true,
                'type' => 'message',
                'message' => 'Takk for din henvendelse! Vi behandler idéen din og tar kontakt innen 5 virkedager.',
            ),
        ),
        'notifications' => array(), // Custom notifications handled by PHP
    );
    
    $result = GFAPI::add_form($form);
    
    if (is_wp_error($result)) {
        return array(
            'success' => false,
            'message' => $result->get_error_message(),
        );
    }
    
    return array(
        'success' => true,
        'message' => 'Skjema opprettet!',
        'form_id' => $result,
    );
}
