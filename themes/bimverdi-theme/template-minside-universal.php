<?php
/**
 * Template Name: Min Side (Universal)
 * 
 * Universal wrapper for all /min-side/* pages.
 * Routes to correct part based on page slug (WPML-proof).
 * 
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header('minside');

// Get current page slug (WPML-safe - uses actual page slug, not translated)
$page_slug = get_post_field('post_name', get_the_ID());

// Also check parent slug for nested pages
$parent_id = wp_get_post_parent_id(get_the_ID());
$parent_slug = $parent_id ? get_post_field('post_name', $parent_id) : '';

// Slug to part mapping
// Key = page slug, Value = part file name (without .php)
$slug_map = [
    // Main dashboard
    'min-side'           => 'dashboard',
    
    // Tool pages
    'mine-verktoy'       => 'verktoy-list',
    'registrer-verktoy'  => 'verktoy-registrer',
    'rediger-verktoy'    => 'verktoy-rediger',
    
    // Company pages
    'foretak'            => 'foretak-detail',
    'rediger-foretak'    => 'foretak-rediger',
    
    // User pages
    'profil'             => 'profil',
    'rediger-profil'     => 'profil-rediger',
    'endre-passord'      => 'profil-passord',
    
    // Content pages
    'artikler'           => 'artikler-list',
    'skriv-artikkel'     => 'artikler-skriv',
    'prosjektideer'      => 'prosjektideer-list',
    
    // Event pages
    'arrangementer'      => 'arrangementer-list',
];

// Determine which part to load
$part_name = $slug_map[$page_slug] ?? null;

// If not found directly, check if it's a child of min-side
if (!$part_name && $parent_slug === 'min-side') {
    $part_name = $slug_map[$page_slug] ?? 'dashboard';
}

// Fallback to dashboard
if (!$part_name) {
    $part_name = 'dashboard';
}

$part_path = get_template_directory() . '/parts/minside/' . $part_name . '.php';
?>

<main class="min-h-screen bg-[#FBF9F5]">
    <div class="max-w-7xl mx-auto px-6 py-8">
        <?php
        // Load the appropriate part
        if (file_exists($part_path)) {
            include $part_path;
        } else {
            // Fallback: show page content or error
            ?>
            <div class="bg-white rounded-lg border border-[#E5E0D8] p-8">
                <?php
                // Try to show page content if available
                if (have_posts()) {
                    while (have_posts()) {
                        the_post();
                        the_content();
                    }
                } else {
                    ?>
                    <div class="text-center py-8">
                        <p class="text-[#5A5A5A]">
                            <?php _e('Siden kunne ikke lastes. Kontakt support hvis problemet vedvarer.', 'bimverdi'); ?>
                        </p>
                        <p class="text-xs text-[#999] mt-2">
                            Debug: slug=<?php echo esc_html($page_slug); ?>, part=<?php echo esc_html($part_name); ?>
                        </p>
                    </div>
                    <?php
                }
                ?>
            </div>
            <?php
        }
        ?>
    </div>
</main>

<?php get_footer(); ?>
