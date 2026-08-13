<?php
/**
 * Shared Archive Intro Section
 *
 * Consistent header for all public archive pages.
 *
 * Innholdet redigeres i Gutenberg via arkivside-CPT-en (Innstillinger →
 * Arkivsider): H1 = postens tittel, brødinnholdet rendres i full bredde
 * under tittel-raden med samme helper som single-sidene bruker
 * (inc/redigerbar-topp.php). Count og tag cloud styres fortsatt av malen.
 *
 * Finnes ingen arkivside-post (f.eks. rett etter deploy, før seedingen i
 * mu-plugins/bimverdi-arkivsider.php har kjørt), faller vi tilbake til
 * verdiene fra den gamle options-siden (rå wp_options-rader — selve
 * admin-siden er fjernet) og til slutt malens hardkodede tekster.
 *
 * Usage:
 *   get_template_part('parts/components/archive-intro', null, [
 *       'acf_prefix'       => 'verktoy',  // = arkivside-slug, se bv_arkivside_definisjoner()
 *       'fallback_title'   => 'Verktøykatalog',
 *       'fallback_ingress' => 'Digitale verktøy og løsninger.',
 *       'count'            => 36,          // optional
 *       'count_label'      => 'verktøy',   // optional
 *       'tag_cloud'        => [             // optional - tag cloud in right column
 *           'taxonomies'   => [['taxonomy' => 'temagruppe', 'filter_class' => 'filter-temagruppe']],
 *           'max_tags'     => 20,
 *       ],
 *   ]);
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$prefix           = $args['acf_prefix'] ?? '';
$fallback_title   = $args['fallback_title'] ?? '';
$fallback_ingress = $args['fallback_ingress'] ?? '';
$count            = $args['count'] ?? null;
$count_label      = $args['count_label'] ?? '';
$tag_cloud        = $args['tag_cloud'] ?? null;

$title        = '';
$ingress      = '';
$innhold_html = '';

$arkivside = ($prefix && function_exists('bv_arkivside_post'))
    ? bv_arkivside_post($prefix)
    : null;

if ($arkivside) {
    $title = $arkivside->post_title;
    // Full Gutenberg-frihet: rendres via samme helper som single-sidene,
    // inkl. legacy-vakten mot innhold som bare speiler tittelen.
    $innhold_html = function_exists('bv_redigerbar_topp_html')
        ? bv_redigerbar_topp_html($arkivside->ID)
        : apply_filters('the_content', $arkivside->post_content);
} elseif ($prefix) {
    // Overgangs-fallback: verdier fra den fjernede options-siden.
    $title   = get_option("options_{$prefix}_tittel") ?: '';
    $ingress = get_option("options_{$prefix}_ingress") ?: '';
}

if (!is_string($title) || !$title) {
    $title = $fallback_title;
}
if (!$arkivside && (!is_string($ingress) || !$ingress)) {
    $ingress = $fallback_ingress;
}
?>

<section class="bg-white border-b border-[#E7E5E4]">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div>
                <h1 class="text-3xl lg:text-4xl font-bold text-[#111827] mb-3">
                    <?php echo esc_html($title); ?>
                </h1>
                <?php if (!$arkivside && $ingress): ?>
                    <p class="text-lg text-[#57534E] leading-relaxed">
                        <?php echo esc_html($ingress); ?>
                    </p>
                <?php endif; ?>
                <?php if ($count !== null && $count_label): ?>
                    <p class="text-sm text-[#78716C] mt-3">
                        <?php echo esc_html($count); ?> <?php echo esc_html($count_label); ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="flex items-center">
                <?php if ($tag_cloud): ?>
                    <?php get_template_part('parts/components/tag-cloud', null, $tag_cloud); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($innhold_html): ?>
            <div class="bv-redigerbar-topp prose max-w-none text-[#57534E] mt-4">
                <?php echo $innhold_html; // Rendret via the_content-filteret, som temagruppe-sidene. ?>
            </div>
        <?php endif; ?>
    </div>
</section>
