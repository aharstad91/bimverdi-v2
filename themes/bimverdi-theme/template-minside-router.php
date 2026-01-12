<?php
/**
 * Template Name: Min Side Router
 * 
 * Single template that routes all /min-side/* URLs to the appropriate parts file.
 * Replaces multiple template-minside-*.php files.
 * 
 * URL Structure:
 * - /min-side/                  → Dashboard
 * - /min-side/profil/           → Profile view
 * - /min-side/profil/rediger/   → Edit profile
 * - /min-side/verktoy/          → Tools list
 * - /min-side/verktoy/registrer/ → Register new tool
 * - /min-side/verktoy/rediger/?id=123 → Edit tool
 * 
 * @package BimVerdi_Theme
 * @since 2.0.0
 */

// Ensure helpers are loaded
if (!function_exists('bimverdi_get_current_route')) {
    require_once get_template_directory() . '/inc/minside-helpers.php';
}

// Authentication check (also done in helpers, but belt-and-braces)
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(bimverdi_minside_url()));
    exit;
}

// Get current route
$current_route = bimverdi_get_current_route();

// Find the matching part file
$part_file = bimverdi_get_route_part($current_route);

// Debug mode (only for admins)
$debug_mode = current_user_can('administrator') && isset($_GET['debug_router']);

if ($debug_mode) {
    echo '<div style="background:#f0f0f0;padding:20px;margin:20px;font-family:monospace;font-size:12px;">';
    echo '<strong>Router Debug:</strong><br>';
    echo 'Request URI: ' . esc_html($_SERVER['REQUEST_URI'] ?? 'N/A') . '<br>';
    echo 'Current Route: ' . esc_html($current_route) . '<br>';
    echo 'Part File: ' . esc_html($part_file ?? 'NULL') . '<br>';
    echo 'Query Var: ' . esc_html(get_query_var('minside_route', 'not set')) . '<br>';
    echo '</div>';
}

// Load header
get_header('minside');
?>

<!-- Main content wrapper -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
<?php

if ($part_file) {
    // Load the matching part file
    $template_path = get_template_directory() . '/parts/minside/' . $part_file . '.php';
    
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        // Part file not found - development error
        get_template_part('parts/components/page-header', null, [
            'title' => 'Under utvikling',
            'description' => 'Denne delen er under utvikling.',
            'icon' => 'construction',
        ]);
        ?>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-6 text-center">
            <p class="text-amber-800 mb-4">
                <strong>Del ikke funnet:</strong> <?php echo esc_html($part_file); ?>.php
            </p>
            <a href="<?php echo esc_url(bimverdi_minside_url('')); ?>" 
               class="inline-flex items-center gap-2 text-[#F97316] hover:underline">
                ← Tilbake til dashboard
            </a>
        </div>
        <?php
    }
} else {
    // No matching route - 404 within Min Side
    get_template_part('parts/components/page-header', null, [
        'title' => 'Siden finnes ikke',
        'description' => 'Vi finner ikke siden du leter etter.',
        'icon' => 'alert-circle',
    ]);
    ?>
    <div class="bg-white border border-[#E5E2DB] rounded-lg p-6 text-center">
        <p class="text-[#5A5A5A] mb-4">
            URL-en <code class="bg-[#F7F5EF] px-2 py-1 rounded text-sm">/min-side/<?php echo esc_html($current_route); ?>/</code> 
            finnes ikke.
        </p>
        <a href="<?php echo esc_url(bimverdi_minside_url('')); ?>" 
           class="inline-flex items-center gap-2 text-[#F97316] hover:underline font-medium">
            ← Tilbake til dashboard
        </a>
    </div>
    <?php
}
?>

</main>

<?php
// Load footer
get_footer('minside');
