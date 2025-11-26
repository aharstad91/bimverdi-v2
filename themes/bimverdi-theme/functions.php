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
    // Enqueue compiled Tailwind CSS with daisyUI
    wp_enqueue_style(
        'bimverdi-styles',
        get_template_directory_uri() . '/dist/style.css',
        array(),
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
        '2.0.0',
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
 * Load Design System
 */
require_once get_template_directory() . '/inc/design-system.php';

/**
 * Load Card Components
 */
require_once get_template_directory() . '/template-parts/cards.php';

/**
 * Load Mock Data Helper (for development/demo)
 */
require_once get_template_directory() . '/inc/mock-data.php';

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
 * Redirect to Min Side after login
 */
function bimverdi_login_redirect($redirect_to, $request, $user) {
    // Check if user has company_owner or company_user role
    if (isset($user->roles) && (in_array('company_owner', $user->roles) || in_array('company_user', $user->roles))) {
        return home_url('/min-side/');
    }
    return $redirect_to;
}
add_filter('login_redirect', 'bimverdi_login_redirect', 10, 3);

/**
 * Restrict Min Side access to logged-in members only
 */
function bimverdi_protect_minside() {
    // Check if we're on a Min Side page
    if (is_page_template('template-minside-dashboard.php') || is_page('min-side')) {
        if (!is_user_logged_in()) {
            // Redirect to login with return URL
            auth_redirect();
        }
    }
}
add_action('template_redirect', 'bimverdi_protect_minside');

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
