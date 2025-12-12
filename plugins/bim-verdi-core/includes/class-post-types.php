<?php
/**
 * Custom Post Types for BIM Verdi
 *
 * @package BIMVerdiCore
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Post_Types {
    
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
        add_action('init', array($this, 'register_post_types'));
    }
    
    /**
     * Register all custom post types
     */
    public function register_post_types() {
        $this->register_foretak();
        $this->register_verktoy();
        $this->register_arrangement();
        $this->register_pamelding();
        $this->register_case();
        $this->register_prosjekt();
        $this->register_artikkel();
    }
    
    /**
     * Register Foretak CPT (Member Organization)
     */
    private function register_foretak() {
        $labels = array(
            'name'                  => _x('Foretak', 'Post Type General Name', 'bim-verdi-core'),
            'singular_name'         => _x('Foretak', 'Post Type Singular Name', 'bim-verdi-core'),
            'menu_name'             => __('Foretak', 'bim-verdi-core'),
            'all_items'             => __('Alle foretak', 'bim-verdi-core'),
            'add_new_item'          => __('Legg til nytt foretak', 'bim-verdi-core'),
            'edit_item'             => __('Rediger foretak', 'bim-verdi-core'),
            'view_item'             => __('Vis foretak', 'bim-verdi-core'),
            'search_items'          => __('Søk foretak', 'bim-verdi-core'),
            'not_found'             => __('Ingen foretak funnet', 'bim-verdi-core'),
        );
        
        $args = array(
            'label'                 => __('Foretak', 'bim-verdi-core'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-building',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'rewrite'               => array('slug' => 'foretak'),
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        
        register_post_type('foretak', $args);
    }
    
    /**
     * Register Verktøy CPT
     */
    private function register_verktoy() {
        $labels = array(
            'name'                  => _x('Verktøy', 'Post Type General Name', 'bim-verdi-core'),
            'singular_name'         => _x('Verktøy', 'Post Type Singular Name', 'bim-verdi-core'),
            'menu_name'             => __('Verktøy', 'bim-verdi-core'),
            'all_items'             => __('Alle verktøy', 'bim-verdi-core'),
            'add_new_item'          => __('Legg til nytt verktøy', 'bim-verdi-core'),
            'edit_item'             => __('Rediger verktøy', 'bim-verdi-core'),
        );
        
        $args = array(
            'label'                 => __('Verktøy', 'bim-verdi-core'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 6,
            'menu_icon'             => 'dashicons-admin-tools',
            'has_archive'           => false,
            'rewrite'               => array('slug' => 'verktoy'),
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        
        register_post_type('verktoy', $args);
    }
    
    /**
     * Register Arrangement CPT
     */
    private function register_arrangement() {
        $labels = array(
            'name'                  => _x('Arrangementer', 'Post Type General Name', 'bim-verdi-core'),
            'singular_name'         => _x('Arrangement', 'Post Type Singular Name', 'bim-verdi-core'),
            'menu_name'             => __('Arrangementer', 'bim-verdi-core'),
            'all_items'             => __('Alle arrangementer', 'bim-verdi-core'),
            'add_new_item'          => __('Legg til nytt arrangement', 'bim-verdi-core'),
        );
        
        $args = array(
            'label'                 => __('Arrangement', 'bim-verdi-core'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'custom-fields'),
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 7,
            'menu_icon'             => 'dashicons-calendar-alt',
            'has_archive'           => true,
            'rewrite'               => array('slug' => 'arrangementer'),
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        
        register_post_type('arrangement', $args);
    }
    
    /**
     * Register Påmelding CPT (Event Registration)
     */
    private function register_pamelding() {
        $labels = array(
            'name'                  => _x('Påmeldinger', 'Post Type General Name', 'bim-verdi-core'),
            'singular_name'         => _x('Påmelding', 'Post Type Singular Name', 'bim-verdi-core'),
            'menu_name'             => __('Påmeldinger', 'bim-verdi-core'),
        );
        
        $args = array(
            'label'                 => __('Påmelding', 'bim-verdi-core'),
            'labels'                => $labels,
            'supports'              => array('custom-fields'),
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => 'edit.php?post_type=arrangement',
            'capability_type'       => 'post',
            'show_in_rest'          => false,
        );
        
        register_post_type('pamelding', $args);
    }
    
    /**
     * Register Case/Prosjektidé CPT
     */
    private function register_case() {
        $labels = array(
            'name'                  => _x('Prosjektidéer', 'Post Type General Name', 'bim-verdi-core'),
            'singular_name'         => _x('Prosjektidé', 'Post Type Singular Name', 'bim-verdi-core'),
            'menu_name'             => __('Prosjektidéer', 'bim-verdi-core'),
            'all_items'             => __('Alle idéer', 'bim-verdi-core'),
        );
        
        $args = array(
            'label'                 => __('Prosjektidé', 'bim-verdi-core'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'custom-fields'),
            'public'                => false,  // Private - only visible to owner and admin
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 8,
            'menu_icon'             => 'dashicons-lightbulb',
            'capability_type'       => 'post',
            'show_in_rest'          => false,
        );
        
        register_post_type('case', $args);
    }
    
    /**
     * Register Prosjekt CPT (Pilot Projects)
     */
    private function register_prosjekt() {
        $labels = array(
            'name'                  => _x('Prosjekter', 'Post Type General Name', 'bim-verdi-core'),
            'singular_name'         => _x('Prosjekt', 'Post Type Singular Name', 'bim-verdi-core'),
            'menu_name'             => __('Prosjekter', 'bim-verdi-core'),
            'all_items'             => __('Alle prosjekter', 'bim-verdi-core'),
            'add_new_item'          => __('Legg til nytt prosjekt', 'bim-verdi-core'),
        );
        
        $args = array(
            'label'                 => __('Prosjekt', 'bim-verdi-core'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 9,
            'menu_icon'             => 'dashicons-portfolio',
            'has_archive'           => true,
            'rewrite'               => array('slug' => 'prosjekter'),
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        
        register_post_type('prosjekt', $args);
    }
    
    /**
     * Register Artikkel CPT (Member Articles)
     * For knowledge sharing and competence showcase
     */
    private function register_artikkel() {
        $labels = array(
            'name'                  => _x('Artikler', 'Post Type General Name', 'bim-verdi-core'),
            'singular_name'         => _x('Artikkel', 'Post Type Singular Name', 'bim-verdi-core'),
            'menu_name'             => __('Artikler', 'bim-verdi-core'),
            'all_items'             => __('Alle artikler', 'bim-verdi-core'),
            'add_new_item'          => __('Skriv ny artikkel', 'bim-verdi-core'),
            'edit_item'             => __('Rediger artikkel', 'bim-verdi-core'),
            'view_item'             => __('Vis artikkel', 'bim-verdi-core'),
            'search_items'          => __('Søk artikler', 'bim-verdi-core'),
            'not_found'             => __('Ingen artikler funnet', 'bim-verdi-core'),
        );
        
        $args = array(
            'label'                 => __('Artikkel', 'bim-verdi-core'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields'),
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 10,
            'menu_icon'             => 'dashicons-media-document',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'rewrite'               => array('slug' => 'artikler'),
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        
        register_post_type('artikkel', $args);
    }
}
