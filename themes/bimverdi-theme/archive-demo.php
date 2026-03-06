<?php
/**
 * Archive template for Demo CPT
 * Gallery page showing all visual prototypes / "Connecting the Dots" demos
 */

// Simple password gate for demo pages
$demo_pass = 'dv30';
$demo_cookie = 'bv_demo_access';

if (isset($_POST['demo_password']) && $_POST['demo_password'] === $demo_pass) {
    setcookie($demo_cookie, hash('sha256', $demo_pass), time() + 30 * DAY_IN_SECONDS, '/');
    $_COOKIE[$demo_cookie] = hash('sha256', $demo_pass);
}

if (!current_user_can('manage_options') && (!isset($_COOKIE[$demo_cookie]) || $_COOKIE[$demo_cookie] !== hash('sha256', $demo_pass))) {
    get_header();
    ?>
    <main class="bg-[#FAFAF9] min-h-screen flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-sm border border-[#E7E5E4] p-8 max-w-sm w-full mx-4 text-center">
            <div class="w-12 h-12 rounded-full bg-[#FFF5F0] flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-[#FF8B5E]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <h1 class="text-xl font-bold text-[#1A1A1A] mb-2">Prototyper</h1>
            <p class="text-sm text-[#5A5A5A] mb-6">Skriv inn passord for tilgang til demoer.</p>
            <form method="post">
                <input type="password" name="demo_password" placeholder="Passord" required
                    class="w-full px-4 py-2.5 rounded-lg border border-[#E7E5E4] text-sm focus:outline-none focus:border-[#FF8B5E] mb-3">
                <button type="submit" class="w-full bg-[#FF8B5E] text-white font-medium text-sm py-2.5 rounded-lg hover:bg-[#E07A50] transition-colors">
                    Logg inn
                </button>
            </form>
        </div>
    </main>
    <?php
    get_footer();
    return;
}

get_header();
?>

<main class="bg-[#FAFAF9] min-h-screen">

    <!-- Hero -->
    <div class="bg-white border-b border-[#E7E5E4]">
        <div class="max-w-[1280px] mx-auto px-4 py-12 lg:py-16">
            <p class="text-sm font-medium text-[#FF8B5E] uppercase tracking-wider mb-2">Connecting the Dots</p>
            <h1 class="text-3xl lg:text-4xl font-bold text-[#1A1A1A] mb-4">Visuelle prototyper</h1>
            <p class="text-lg text-[#5A5A5A] max-w-2xl">
                Utforskning av hvordan vi kan visualisere sammenhenger mellom temagrupper, deltakere, verktoy, kunnskapskilder og arrangementer.
            </p>
        </div>
    </div>

    <!-- Demo Grid -->
    <div class="max-w-[1280px] mx-auto px-4 pt-16 pb-10">
        <?php if (have_posts()) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while (have_posts()) : the_post(); ?>
            <a href="<?php the_permalink(); ?>" class="group block bg-white rounded-lg border border-[#E7E5E4] overflow-hidden hover:shadow-lg hover:border-[#FF8B5E]/30 transition-all duration-300">

                <div class="p-5">
                    <h2 class="text-lg font-semibold text-[#1A1A1A] group-hover:text-[#FF8B5E] transition-colors mb-2">
                        <?php the_title(); ?>
                    </h2>
                    <?php if (has_excerpt()) : ?>
                        <p class="text-sm text-[#5A5A5A] line-clamp-2"><?php echo get_the_excerpt(); ?></p>
                    <?php endif; ?>
                    <div class="mt-4 flex items-center text-xs text-[#FF8B5E] font-medium">
                        <span>Se demo</span>
                        <svg style="width:14px;height:14px;flex-shrink:0" class="ml-1 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
        <?php else : ?>
        <p class="text-[#5A5A5A] text-center py-16">Ingen demoer publisert enda.</p>
        <?php endif; ?>
    </div>

</main>

<?php get_footer(); ?>
