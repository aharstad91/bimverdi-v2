<?php
/**
 * Custom Taxonomies for BIM Verdi
 *
 * @package BIMVerdiCore
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Taxonomies {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'register_taxonomies'));
        add_action('init', array($this, 'insert_default_terms'), 20);
    }
    
    /**
     * Register all taxonomies
     */
    public function register_taxonomies() {
        $this->register_bransjekategori();
        $this->register_kundetype();
        $this->register_temagruppe();
        $this->register_verktoykategori();
        $this->register_kunnskapskildekategori();
        $this->register_arrangementstype();
        $this->register_artikkelkategori();
    }
    
    /**
     * Register Bransjekategori taxonomy (hierarchical)
     */
    private function register_bransjekategori() {
        $labels = array(
            'name'              => _x('Bransjekategorier', 'taxonomy general name', 'bim-verdi-core'),
            'singular_name'     => _x('Bransjekategori', 'taxonomy singular name', 'bim-verdi-core'),
            'search_items'      => __('Søk kategorier', 'bim-verdi-core'),
            'all_items'         => __('Alle kategorier', 'bim-verdi-core'),
            'parent_item'       => __('Overordnet kategori', 'bim-verdi-core'),
            'edit_item'         => __('Rediger kategori', 'bim-verdi-core'),
            'add_new_item'      => __('Legg til ny kategori', 'bim-verdi-core'),
        );
        
        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => false,
            'rewrite'           => array('slug' => 'bransje'),
            'show_in_rest'      => true,
        );
        
        register_taxonomy('bransjekategori', array('foretak'), $args);
    }
    
    /**
     * Register Kundetype taxonomy
     */
    private function register_kundetype() {
        $labels = array(
            'name'              => _x('Kundetyper', 'taxonomy general name', 'bim-verdi-core'),
            'singular_name'     => _x('Kundetype', 'taxonomy singular name', 'bim-verdi-core'),
            'all_items'         => __('Alle kundetyper', 'bim-verdi-core'),
        );
        
        $args = array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
        );
        
        register_taxonomy('kundetype', array('foretak'), $args);
    }
    
    /**
     * Register Temagruppe taxonomy
     */
    private function register_temagruppe() {
        $labels = array(
            'name'              => _x('Temagrupper', 'taxonomy general name', 'bim-verdi-core'),
            'singular_name'     => _x('Temagruppe', 'taxonomy singular name', 'bim-verdi-core'),
            'all_items'         => __('Alle temagrupper', 'bim-verdi-core'),
        );
        
        $args = array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
        );
        
        register_taxonomy('temagruppe', array('foretak', 'arrangement', 'prosjekt', 'kunnskapskilde', 'artikkel'), $args);
    }
    
    /**
     * Register Verktøykategori taxonomy
     */
    private function register_verktoykategori() {
        $labels = array(
            'name'              => _x('Verktøykategorier', 'taxonomy general name', 'bim-verdi-core'),
            'singular_name'     => _x('Verktøykategori', 'taxonomy singular name', 'bim-verdi-core'),
        );
        
        $args = array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
        );
        
        register_taxonomy('verktoykategori', array('verktoy'), $args);
    }

    /**
     * Register Kunnskapskildekategori taxonomy
     */
    private function register_kunnskapskildekategori() {
        $labels = array(
            'name'              => _x('Kunnskapskildekategorier', 'taxonomy general name', 'bim-verdi-core'),
            'singular_name'     => _x('Kunnskapskildekategori', 'taxonomy singular name', 'bim-verdi-core'),
            'all_items'         => __('Alle kategorier', 'bim-verdi-core'),
            'search_items'      => __('Søk kategorier', 'bim-verdi-core'),
            'edit_item'         => __('Rediger kategori', 'bim-verdi-core'),
            'add_new_item'      => __('Legg til ny kategori', 'bim-verdi-core'),
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => false,
            'rewrite'           => array('slug' => 'kunnskapskilde-kategori'),
            'show_in_rest'      => true,
        );

        register_taxonomy('kunnskapskildekategori', array('kunnskapskilde'), $args);
    }

    /**
     * Register Arrangementstype taxonomy
     */
    private function register_arrangementstype() {
        $labels = array(
            'name'              => _x('Arrangementstyper', 'taxonomy general name', 'bim-verdi-core'),
            'singular_name'     => _x('Arrangementstype', 'taxonomy singular name', 'bim-verdi-core'),
        );
        
        $args = array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
        );
        
        register_taxonomy('arrangementstype', array('arrangement'), $args);
    }
    
    /**
     * Register Artikkelkategori taxonomy
     */
    private function register_artikkelkategori() {
        $labels = array(
            'name'              => _x('Artikkelkategorier', 'taxonomy general name', 'bim-verdi-core'),
            'singular_name'     => _x('Artikkelkategori', 'taxonomy singular name', 'bim-verdi-core'),
        );
        
        $args = array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
        );
        
        register_taxonomy('artikkelkategori', array('post'), $args);
    }
    
    /**
     * Insert default taxonomy terms
     */
    public function insert_default_terms() {
        // Only run once
        if (get_option('bim_verdi_default_terms_inserted')) {
            return;
        }
        
        // Temagrupper (exactly 6 fixed groups)
        $temagrupper = array(
            'ByggesaksBIM' => 'Byggesaksprosesser og kommunal saksbehandling',
            'ProsjektBIM' => 'Design- og byggefase koordinering',
            'EiendomsBIM' => 'Drift og forvaltning',
            'MiljøBIM' => 'Miljø- og klimaberegninger',
            'SirkBIM' => 'Sirkulær økonomi og materialgjenbruk',
            'BIMtech' => 'Teknologi, AI og digitale verktøy'
        );
        
        foreach ($temagrupper as $name => $description) {
            if (!term_exists($name, 'temagruppe')) {
                wp_insert_term($name, 'temagruppe', array('description' => $description));
            }
        }
        
        // Verktøykategorier
        $verktoykategorier = array(
            'BIM Authoring/Modelling',
            'Visualization/VR/AR',
            'Analysis & Simulation',
            'Collaboration/Communication',
            'Quality Control/Validation',
            'Project Management',
            'Climate/Environmental Calculation',
            'AI/Machine Learning',
            'Material Management',
            'Other'
        );
        
        foreach ($verktoykategorier as $cat) {
            if (!term_exists($cat, 'verktoykategori')) {
                wp_insert_term($cat, 'verktoykategori');
            }
        }
        
        // Arrangementstyper
        $arrangementstyper = array(
            'BIMtech møte',
            'Temagruppemøte',
            'Seminar',
            'Workshop',
            'Kompetansedugnad',
            'Konferanse',
            'Webinar',
            'Hackathon'
        );
        
        foreach ($arrangementstyper as $type) {
            if (!term_exists($type, 'arrangementstype')) {
                wp_insert_term($type, 'arrangementstype');
            }
        }
        
        // Artikkelkategorier
        $artikkelkategorier = array(
            'Nyhet' => 'Nyheter fra BIM Verdi',
            'Medlemsinnlegg' => 'Historier fra medlemmer',
            'Case study' => 'Casestudier og erfaringer',
            'Ressurs' => 'Utdanningsressurser'
        );
        
        foreach ($artikkelkategorier as $name => $description) {
            if (!term_exists($name, 'artikkelkategori')) {
                wp_insert_term($name, 'artikkelkategori', array('description' => $description));
            }
        }

        // Kunnskapskildekategorier
        $kunnskapskildekategorier = array(
            'Standard' => 'Standarder og spesifikasjoner (ISO, NS, buildingSMART)',
            'Veileder' => 'Veiledere og retningslinjer',
            'Mal/Template' => 'Maler og templates for bruk i prosjekter',
            'Forskningsrapport' => 'Forskningsrapporter og akademiske artikler',
            'Casestudie' => 'Casestudier og erfaringsrapporter',
            'Opplæring' => 'Opplæringsmateriell og kursressurser',
            'Verktøydokumentasjon' => 'Dokumentasjon for verktøy og programvare',
            'Annet' => 'Andre typer kunnskapskilder'
        );

        foreach ($kunnskapskildekategorier as $name => $description) {
            if (!term_exists($name, 'kunnskapskildekategori')) {
                wp_insert_term($name, 'kunnskapskildekategori', array('description' => $description));
            }
        }

        // Mark as inserted
        update_option('bim_verdi_default_terms_inserted', true);
    }
}
