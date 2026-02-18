<?php
/**
 * BIM Verdi Theme Functions
 * 
 * @package BIMVerdi
 * @version 2.0.1
 * @updated 2025-11-10 - Auto-deploy active
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Setup
 */
function bimverdi_theme_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
    add_theme_support('custom-logo');
    add_theme_support('customize-selective-refresh-widgets');
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'bimverdi'),
        'footer' => __('Footer Menu', 'bimverdi'),
    ));
}
add_action('after_setup_theme', 'bimverdi_theme_setup');

/**
 * Enqueue Styles and Scripts
 */
function bimverdi_enqueue_assets() {
    // Google Fonts: Inter (body) + Crimson Text (hero accent)
    wp_enqueue_style(
        'google-fonts',
        'https://fonts.googleapis.com/css2?family=Crimson+Text:ital,wght@1,600;1,700&family=Inter:wght@400;500;600;700&display=swap',
        array(),
        null
    );

    // Enqueue compiled Tailwind CSS with daisyUI
    wp_enqueue_style(
        'bimverdi-styles',
        get_template_directory_uri() . '/dist/style.css',
        array('google-fonts'),
        '2.0.0'
    );
    
    // Web Awesome CSS (default theme)
    wp_enqueue_style(
        'webawesome-theme',
        get_template_directory_uri() . '/dist/webawesome/styles/themes/default.css',
        array(),
        '3.0.0'
    );
    
    // Enqueue custom JavaScript (if needed)
    wp_enqueue_script(
        'bimverdi-scripts',
        get_template_directory_uri() . '/js/main.js',
        array(),
        '2.1.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'bimverdi_enqueue_assets');

/**
 * Add Web Awesome loader script to head
 */
function bimverdi_add_webawesome_loader() {
    $webawesome_path = get_template_directory_uri() . '/dist/webawesome';
    ?>
    <script type="module" data-webawesome="<?php echo esc_url($webawesome_path); ?>" src="<?php echo esc_url($webawesome_path . '/webawesome.loader.js'); ?>"></script>
    <?php
}
add_action('wp_head', 'bimverdi_add_webawesome_loader', 5);

/**
 * Load Min Side Helpers
 * Provides routing, auth, and navigation helpers for /min-side/*
 */
require_once get_template_directory() . '/inc/minside-helpers.php';

/**
 * Load Design System
 */
require_once get_template_directory() . '/inc/design-system.php';

/**
 * Load Button Component
 * Provides bimverdi_button() and bimverdi_icon() functions
 */
require_once get_template_directory() . '/parts/components/button.php';

/**
 * Load Card Components
 */
require_once get_template_directory() . '/template-parts/cards.php';

/**
 * Load Mock Data Helper (for development/demo)
 */
require_once get_template_directory() . '/inc/mock-data.php';

/**
 * Load Admin Helpers
 * Provides bimverdi_admin_id_badge() for frontend admin tools
 */
require_once get_template_directory() . '/inc/admin-helpers.php';

/**
 * Load ACF Field Groups (with graceful fallback if file missing)
 */
$acf_temagruppe_fields = get_template_directory() . '/inc/acf/temagruppe-fields.php';
if (file_exists($acf_temagruppe_fields)) {
    require_once $acf_temagruppe_fields;
}

/**
 * Dummy Data Generator for Temagrupper (temporary - remove after use)
 * Usage: Visit /wp-admin/?generate_temagruppe_dummydata=1 as admin
 */
$dummy_data_file = get_template_directory() . '/inc/dummy-data-temagrupper.php';
if (file_exists($dummy_data_file)) {
    require_once $dummy_data_file;
}

/**
 * Register Widget Areas
 */
function bimverdi_widgets_init() {
    register_sidebar(array(
        'name'          => __('Sidebar', 'bimverdi'),
        'id'            => 'sidebar-1',
        'description'   => __('Add widgets here.', 'bimverdi'),
        'before_widget' => '<section id="%1$s" class="widget %2$s mb-6">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title text-2xl font-bold mb-4">',
        'after_title'   => '</h2>',
    ));
    
    register_sidebar(array(
        'name'          => __('Footer', 'bimverdi'),
        'id'            => 'footer-1',
        'description'   => __('Footer widget area.', 'bimverdi'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title text-lg font-bold mb-3">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'bimverdi_widgets_init');

/**
 * NOTE: Login redirect and Min Side protection are now in inc/minside-helpers.php
 * The functions bimverdi_login_redirect() and bimverdi_protect_minside() are defined there.
 */

/**
 * Custom excerpt length
 */
function bimverdi_excerpt_length($length) {
    return 30;
}
add_filter('excerpt_length', 'bimverdi_excerpt_length');

/**
 * Custom excerpt more
 */
function bimverdi_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'bimverdi_excerpt_more');

/**
 * Add /deltakere/ rewrite to foretak archive
 * This allows both /foretak/ and /deltakere/ to show the same archive
 */
function bimverdi_add_deltakere_rewrite() {
    // Base deltakere URL
    add_rewrite_rule('^deltakere/?$', 'index.php?post_type=foretak', 'top');
    // Paged deltakere URL
    add_rewrite_rule('^deltakere/page/([0-9]+)/?$', 'index.php?post_type=foretak&paged=$matches[1]', 'top');

    // Legacy /medlemmer/ redirect support (can be removed after transition)
    add_rewrite_rule('^medlemmer/?$', 'index.php?post_type=foretak', 'top');
    add_rewrite_rule('^medlemmer/page/([0-9]+)/?$', 'index.php?post_type=foretak&paged=$matches[1]', 'top');
}
add_action('init', 'bimverdi_add_deltakere_rewrite');

/**
 * Flush rewrite rules on theme activation
 */
function bimverdi_flush_rewrites() {
    bimverdi_add_deltakere_rewrite();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'bimverdi_flush_rewrites');

/**
 * Add custom class to menu items + fix relative URLs for subdirectory installs
 */
function bimverdi_nav_menu_link_attributes($atts, $item, $args, $depth) {
    // Add custom link class if specified
    if (isset($args->link_class)) {
        $atts['class'] = $atts['class'] ?? '';
        $atts['class'] .= ' ' . $args->link_class;
    }

    // Fix relative URLs (starting with /) for subdirectory installs
    // Converts /deltakere to /bimverdi-v2/deltakere (or full home_url)
    if (isset($atts['href']) && strpos($atts['href'], '/') === 0 && strpos($atts['href'], '//') !== 0) {
        $atts['href'] = home_url($atts['href']);
    }

    return $atts;
}
add_filter('nav_menu_link_attributes', 'bimverdi_nav_menu_link_attributes', 10, 4);

/**
 * Add custom classes to menu items for dropdown styling
 */
function bimverdi_nav_menu_css_class($classes, $item, $args, $depth) {
    if ($depth === 0 && in_array('menu-item-has-children', $classes)) {
        $classes[] = 'dropdown-parent';
    }
    return $classes;
}
add_filter('nav_menu_css_class', 'bimverdi_nav_menu_css_class', 10, 4);

/**
 * Enqueue dropdown menu styles
 */
function bimverdi_enqueue_menu_styles() {
    ?>
    <style>
    /* Dropdown menu styling - Variant B */
    .menu-item {
        position: relative;
    }
    
    .menu-item.dropdown-parent > a::after {
        content: '';
        display: inline-block;
        width: 0;
        height: 0;
        margin-left: 6px;
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 4px solid currentColor;
        opacity: 0.5;
        vertical-align: middle;
    }
    
    .sub-menu {
        position: absolute;
        top: 100%;
        left: 0;
        min-width: 240px;
        margin-top: 0.5rem;
        padding: 0.5rem 0;
        background: white;
        border: 1px solid #E7E5E4;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-8px);
        transition: all 0.2s ease;
        z-index: 100;
    }
    
    .menu-item:hover > .sub-menu,
    .menu-item:focus-within > .sub-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .sub-menu .menu-item {
        display: block;
    }
    
    .sub-menu a {
        display: block;
        padding: 0.625rem 1rem;
        font-size: 0.875rem;
        color: #57534E !important;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    
    .sub-menu a:hover,
    .sub-menu a:focus {
        background-color: #F5F5F4;
        color: #111827 !important;
    }
    
    /* Submenu item styling */
    .sub-menu .menu-item {
        padding: 0;
    }
    </style>
    <?php
}
add_action('wp_head', 'bimverdi_enqueue_menu_styles');
