<?php
/**
 * Single Demo template
 * Routes to specific demo visualization based on post slug
 */

// Password gate — same as archive-demo.php
$demo_pass = 'dv30';
$demo_cookie = 'bv_demo_access';
if (isset($_POST['demo_password']) && $_POST['demo_password'] === $demo_pass) {
    setcookie($demo_cookie, hash('sha256', $demo_pass), time() + 30 * DAY_IN_SECONDS, '/');
    $_COOKIE[$demo_cookie] = hash('sha256', $demo_pass);
}
if (!current_user_can('manage_options') && (!isset($_COOKIE[$demo_cookie]) || $_COOKIE[$demo_cookie] !== hash('sha256', $demo_pass))) {
    wp_redirect(get_post_type_archive_link('demo'));
    exit;
}

get_header();

if (have_posts()) : while (have_posts()) : the_post();

$slug = get_post_field('post_name', get_the_ID());

// Map slugs to demo template parts
$demo_templates = [
    'temagruppe-nettverksgraf'     => 'parts/demos/nettverksgraf',
    'entity-relasjonskort'         => 'parts/demos/relasjonskort',
    'okosystem-flyt'               => 'parts/demos/okosystem-flyt',
    'foretak-verktoy-matrise'      => 'parts/demos/matrise',
    'temagruppe-tidslinje'         => 'parts/demos/tidslinje',
    'okosystem-vertikal'           => 'parts/demos/okosystem-vertikal',
    'veikart-graf'                 => 'parts/demos/veikart-graf',
    'veikart-matrise'              => 'parts/demos/veikart-matrise',
    'veikart-kort'                 => 'parts/demos/veikart-kort',
    'veikart-kolonner'             => 'parts/demos/veikart-kolonner',
    'veikart-orbital'              => 'parts/demos/veikart-orbital',
];

if (isset($demo_templates[$slug])) {
    get_template_part($demo_templates[$slug]);
} else {
    // Fallback: show post content with a note
    ?>
    <main class="bg-[#FAFAF9] min-h-screen">
        <div class="max-w-[1280px] mx-auto px-4 py-12">
            <a href="<?php echo get_post_type_archive_link('demo'); ?>" class="text-sm text-[#FF8B5E] hover:underline mb-4 inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Alle demoer
            </a>
            <h1 class="text-3xl font-bold text-[#1A1A1A] mt-4 mb-6"><?php the_title(); ?></h1>
            <div class="prose max-w-none text-[#5A5A5A]">
                <?php the_content(); ?>
            </div>
        </div>
    </main>
    <?php
}

endwhile; endif;

get_footer();
?>
