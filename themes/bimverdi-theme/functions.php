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
        'https://fonts.googleapis.com/css2?family=Crimson+Text:ital,wght@1,600;1,700&family=Familjen+Grotesk:ital,wght@0,400..700;1,400..700&family=Inter:wght@400;500;600;700&display=swap',
        array(),
        null
    );

    // Enqueue compiled Tailwind CSS with daisyUI
    wp_enqueue_style(
        'bimverdi-styles',
        get_template_directory_uri() . '/dist/style.css',
        array('google-fonts'),
        '2.0.1'
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

    // View toggle (grid/list) for archive pages
    wp_enqueue_script(
        'bimverdi-view-toggle',
        get_template_directory_uri() . '/js/view-toggle.js',
        array('bimverdi-scripts'),
        '1.1.0',
        true
    );
    // Add missing Tailwind utilities not in compiled CSS
    wp_add_inline_style('bimverdi-styles', '
        @media (min-width: 768px) {
            .md\:inline-flex { display: inline-flex !important; }
            .md\:hidden { display: none !important; }
            .md\:flex { display: flex !important; }
        }
        .sr-only { position: absolute !important; width: 1px !important; height: 1px !important; padding: 0 !important; margin: -1px !important; overflow: hidden !important; clip: rect(0,0,0,0) !important; white-space: nowrap !important; border-width: 0 !important; }
        .peer:checked ~ div { /* handled by peer-checked below */ }
        .peer-checked\:border-\[\#4a7c29\] { border-color: #4a7c29 !important; }
        .peer-checked\:bg-green-50 { background-color: rgb(240 253 244) !important; }
        .peer-checked\:border-\[\#c53030\] { border-color: #c53030 !important; }
        .peer-checked\:bg-red-50 { background-color: rgb(254 242 242) !important; }
        .peer-checked\:border-\[\#FF8B5E\] { border-color: #FF8B5E !important; }
        .peer-checked\:bg-orange-50 { background-color: rgb(255 247 237) !important; }
    ');
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
 * Load Taxonomy Helpers
 * Provides cached slug-to-name conversion for taxonomy terms
 */
require_once get_template_directory() . '/inc/taxonomy-helpers.php';

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
 * Load Breadcrumb Component
 * Provides bimverdi_breadcrumb() function
 */
require_once get_template_directory() . '/parts/components/breadcrumb.php';

/**
 * Load Field Component
 * Provides bimverdi_field(), bimverdi_field_group(), bimverdi_field_group_end()
 */
require_once get_template_directory() . '/parts/components/field.php';

/**
 * Load Section Header Component
 * Provides bimverdi_section_header() function
 */
require_once get_template_directory() . '/parts/components/section-header.php';

/**
 * Load Badge Component
 * Provides bimverdi_badge() function for status/category badges
 */
require_once get_template_directory() . '/parts/components/badge.php';

/**
 * Load Switch Component
 * Provides bimverdi_switch() function for toggle controls
 */
require_once get_template_directory() . '/parts/components/switch.php';

/**
 * Load Tabs Component
 * Provides bimverdi_tabs(), bimverdi_tab_panel(), bimverdi_tab_panel_end(), bimverdi_tabs_end()
 */
require_once get_template_directory() . '/parts/components/tabs.php';

/**
 * Load Stat Pill Component
 * Provides bimverdi_stat_pill() function
 */
require_once get_template_directory() . '/parts/components/stat-pill.php';

/**
 * Load Empty State Component
 * Provides bimverdi_empty_state() function
 */
require_once get_template_directory() . '/parts/components/empty-state.php';

/**
 * Load Item Component
 * Provides bimverdi_item(), bimverdi_item_group(), bimverdi_item_group_end()
 */
require_once get_template_directory() . '/parts/components/item.php';

/**
 * Load Pagination Component
 * Provides bimverdi_pagination() function
 */
require_once get_template_directory() . '/parts/components/pagination.php';

/**
 * Load Avatar Component
 * Provides bimverdi_avatar() and bimverdi_avatar_group() functions
 */
require_once get_template_directory() . '/parts/components/avatar.php';

/**
 * Load Accordion Component
 * Provides bimverdi_accordion() function
 */
require_once get_template_directory() . '/parts/components/accordion.php';

/**
 * Load Card Component
 * Provides bimverdi_card(), bimverdi_card_start/end(), bimverdi_card_header(), etc.
 */
require_once get_template_directory() . '/parts/components/card.php';

/**
 * Load Alert Component
 * Provides bimverdi_alert() for callout messages (info, success, warning, error).
 */
require_once get_template_directory() . '/parts/components/alert.php';

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
 * Fix relative URLs for subdirectory installs
 */
function bimverdi_nav_menu_link_attributes($atts, $item, $args, $depth) {
    // Converts /deltakere to /bimverdi-v2/deltakere (or full home_url)
    if (isset($atts['href']) && strpos($atts['href'], '/') === 0 && strpos($atts['href'], '//') !== 0) {
        $atts['href'] = home_url($atts['href']);
    }

    return $atts;
}
add_filter('nav_menu_link_attributes', 'bimverdi_nav_menu_link_attributes', 10, 4);

/**
 * Navigation menu dropdown support is now in inc/design-system.php (.bv-nav classes)
 */

/**
 * Format date in Norwegian (e.g. "1. mars 2026")
 *
 * @param int|null $post_id Post ID (defaults to current post)
 * @return string Formatted date string
 */
function bimverdi_format_date($post_id = null) {
    $months_no = ['januar', 'februar', 'mars', 'april', 'mai', 'juni', 'juli', 'august', 'september', 'oktober', 'november', 'desember'];
    $date = get_the_date('Y-m-d', $post_id);
    $dt = new DateTime($date);
    return $dt->format('j') . '. ' . $months_no[(int)$dt->format('n') - 1] . ' ' . $dt->format('Y');
}

/**
 * Estimate reading time for a post
 *
 * @param int|null $post_id Post ID (defaults to current post)
 * @return string e.g. "3 min lesetid"
 */
function bimverdi_reading_time($post_id = null) {
    $content = get_post_field('post_content', $post_id ?: get_the_ID());
    $word_count = str_word_count(strip_tags($content));
    $minutes = max(1, ceil($word_count / 200));
    return $minutes . ' min lesetid';
}

/**
 * Get temagruppe brand color by name
 *
 * Centralizes the color map so it can be reused across templates
 * instead of hardcoding in each file.
 *
 * @param string $name Temagruppe name (e.g. 'SirkBIM')
 * @return string Hex color code, defaults to stone-600
 */
function bimverdi_get_temagruppe_color($name) {
    $colors = [
        'SirkBIM'      => '#FF8B5E',
        'ByggesaksBIM' => '#005898',
        'ProsjektBIM'  => '#6B9B37',
        'EiendomsBIM'  => '#5E36FE',
        'MiljøBIM'     => '#0D9488',
        'BIMtech'      => '#D97706',
    ];
    return $colors[$name] ?? '#57534E';
}
