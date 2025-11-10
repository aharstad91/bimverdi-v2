<?php
/**
 * Mock Data Helper for Development
 * 
 * Provides demo data for Min Side templates when no real data exists
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get mock company data
 */
function bimverdi_get_mock_company() {
    return array(
        'ID' => 0,
        'post_title' => 'DEMO: Arkitektur AS',
        'post_content' => 'En moderne arkitekturBedrift som spesialiserer seg på BIM-drevet design og planlegging av komplekse byggeprosjekter.',
        'post_excerpt' => 'Arkitektur AS',
        'logo' => null,
        'organisasjonsnummer' => '123456789',
        'adresse' => 'Stortingsgaten 1, 0161 Oslo',
        'telefon' => '+47 XX XXX XXX',
        'nettside' => 'https://arkitektur.example.no',
        'kontaktperson' => 'Anders Andersen',
        'epost' => 'anders@arkitektur.example.no',
        'medlemsstatus' => 'Partner',
        'bransjekategorier' => array('Arkitektur', 'Konsultasjon'),
        'kundetyper' => array('Offentlig', 'Privat'),
    );
}

/**
 * Get mock tools
 */
function bimverdi_get_mock_tools() {
    return array(
        array(
            'ID' => 0,
            'post_title' => 'DEMO: BIM Collaborate Pro',
            'post_content' => 'Avansert samarbeidsplattform for BIM-modeller med real-time synkronisering og versjonering.',
            'category' => 'Samarbeid',
            'owner' => 'DEMO: Arkitektur AS',
            'url' => 'https://bim-collaborate.example.com',
            'icon' => '🤝',
        ),
        array(
            'ID' => 0,
            'post_title' => 'DEMO: Model Analyzer',
            'post_content' => 'AI-drevet verktøy for analyse av BIM-modellkvalitet og datakonsistens.',
            'category' => 'Analyse',
            'owner' => 'DEMO: Arkitektur AS',
            'url' => 'https://model-analyzer.example.com',
            'icon' => '📊',
        ),
        array(
            'ID' => 0,
            'post_title' => 'DEMO: BIM Standards Validator',
            'post_content' => 'Automatisk validering av BIM-modeller mot norske og internasjonale standarder.',
            'category' => 'Validering',
            'owner' => 'DEMO: Arkitektur AS',
            'url' => 'https://validator.example.com',
            'icon' => '✓',
        ),
    );
}

/**
 * Get mock articles
 */
function bimverdi_get_mock_articles() {
    return array(
        array(
            'ID' => 0,
            'post_title' => 'DEMO: Hvordan vi implementerte BIM på store prosjekter',
            'excerpt' => 'Erfaringer og lærdommer fra vår implementering av BIM på nasjonale byggeprosjekter.',
            'date' => date('d.m.Y', strtotime('-5 days')),
            'status' => 'Publisert',
            'status_color' => 'success',
        ),
        array(
            'ID' => 0,
            'post_title' => 'DEMO: Best practices for modellkvalitet',
            'excerpt' => 'En guide til å oppnå høy BIM-modellkvalitet gjennom hele prosjektets livssyklus.',
            'date' => date('d.m.Y', strtotime('-10 days')),
            'status' => 'Publisert',
            'status_color' => 'success',
        ),
    );
}

/**
 * Get mock theme groups
 */
function bimverdi_get_mock_theme_groups() {
    return array(
        array(
            'name' => 'Modellkvalitet',
            'description' => 'Fokus på bestpraksis for BIM-modellering og datakvalitet',
            'members' => 24,
            'selected' => true,
        ),
        array(
            'name' => 'ByggesaksBIM',
            'description' => 'Digitalisering av byggesaksflyt og offentlig dialog',
            'members' => 18,
            'selected' => true,
        ),
        array(
            'name' => 'ProsjektBIM',
            'description' => 'BIM i prosjektstyring og samarbeid',
            'members' => 31,
            'selected' => false,
        ),
        array(
            'name' => 'EiendomsBIM',
            'description' => 'BIM for eiendomsforvaltning og drift',
            'members' => 12,
            'selected' => false,
        ),
        array(
            'name' => 'MiljøBIM',
            'description' => 'BIM for miljø- og bærekraftanalyse',
            'members' => 16,
            'selected' => true,
        ),
        array(
            'name' => 'BIMtech',
            'description' => 'Teknologi, API-er og integrasjoner',
            'members' => 22,
            'selected' => false,
        ),
    );
}

/**
 * Get mock events
 */
function bimverdi_get_mock_events() {
    return array(
        array(
            'ID' => 0,
            'post_title' => 'DEMO: BIM Masterclass - Modellkvalitet',
            'date' => date('d.m.Y', strtotime('+7 days')),
            'time' => '09:00 - 12:00',
            'location' => 'Oslo - Teknisk Museum',
            'capacity' => 50,
            'registered' => 28,
            'user_registered' => false,
            'description' => 'Dyppdykk i best practices for BIM-modellering og kvalitetssikring.',
        ),
        array(
            'ID' => 0,
            'post_title' => 'DEMO: Nettverksmøte - Temagruppe Modellkvalitet',
            'date' => date('d.m.Y', strtotime('+14 days')),
            'time' => '13:00 - 15:30',
            'location' => 'Teams/Zoom',
            'capacity' => 100,
            'registered' => 42,
            'user_registered' => true,
            'description' => 'Månedlig møte for medlemmer av Modellkvalitet-gruppen. Diskusjon og erfaringsutveksling.',
        ),
        array(
            'ID' => 0,
            'post_title' => 'DEMO: Workshop - BIM og Bærekraft',
            'date' => date('d.m.Y', strtotime('+21 days')),
            'time' => '10:00 - 16:00',
            'location' => 'Bergen - Svartlamon',
            'capacity' => 30,
            'registered' => 28,
            'user_registered' => false,
            'description' => 'Praktisk workshop om hvordan bruke BIM for miljø- og bærekraftanalyse.',
        ),
    );
}

/**
 * Get mock ideas/projects
 */
function bimverdi_get_mock_ideas() {
    return array(
        array(
            'ID' => 0,
            'post_title' => 'DEMO: API for automatisk modellvalidering',
            'submitted_date' => date('d.m.Y', strtotime('-15 days')),
            'status' => 'Under vurdering',
            'status_color' => 'warning',
            'description' => 'Forslag om API som kan validere BIM-modeller automatisk mot standarder.',
            'feedback' => '',
        ),
        array(
            'ID' => 0,
            'post_title' => 'DEMO: Sertifiseringsprogram for BIM-eksperter',
            'submitted_date' => date('d.m.Y', strtotime('-30 days')),
            'status' => 'Godkjent',
            'status_color' => 'success',
            'description' => 'Program for sertifisering av BIM-eksperter i Norge - tilsvarende andre land.',
            'feedback' => 'Flott forslag! Vi ser stort potensial her. Foreslår vi starter planlegging Q1 2026.',
        ),
    );
}

/**
 * Check if user has real data (for conditional rendering)
 */
function bimverdi_has_real_user_data() {
    if (!is_user_logged_in()) {
        return false;
    }
    
    $user_id = get_current_user_id();
    $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
    
    return !empty($company_id);
}
