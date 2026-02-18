<?php
/**
 * Archive template for Foretak (Deltakere)
 *
 * Public listing of all member companies.
 * Design from Figma: node 15-35
 *
 * @package BimVerdi_Theme
 */

get_header();

// Get all foretak in random order
$args = array(
    'post_type' => 'foretak',
    'posts_per_page' => -1, // All posts
    'orderby' => 'rand',
);

$members_query = new WP_Query($args);
$total_foretak = $members_query->found_posts;

/**
 * Get membership level for a foretak
 * Returns 'Partner', 'Prosjektdeltaker', 'Deltaker', or empty string
 */
if (!function_exists('bimverdi_get_membership_level')) {
    function bimverdi_get_membership_level($post_id) {
        $bv_rolle = get_field('bv_rolle', $post_id);

        if ($bv_rolle && $bv_rolle !== 'Ikke deltaker') {
            return $bv_rolle;
        }
        return '';
    }
}

/**
 * Get initials from company name
 */
if (!function_exists('bimverdi_get_initials')) {
    function bimverdi_get_initials($name) {
        $words = explode(' ', $name);
        if (count($words) >= 2) {
            return strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
        }
        return strtoupper(mb_substr($name, 0, 2));
    }
}
?>

<div class="min-h-screen bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

        <!-- Page Header -->
        <div class="mb-10">
            <h1 class="text-4xl font-bold text-[#111827] mb-3">Deltakere</h1>
            <p class="text-[#57534E] text-lg">
                Utforsk nettverket av <?php echo intval($total_foretak); ?> foretak som <strong>samarbeider for økt produktivitet i byggenæringen med BIM og KI</strong>.
            </p>
        </div>

        <!-- Member Grid -->
        <?php if ($members_query->have_posts()): ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($members_query->have_posts()): $members_query->the_post();
                $logo_id = get_field('logo');
                $logo_url = $logo_id ? (is_array($logo_id) ? $logo_id['sizes']['medium'] : wp_get_attachment_url($logo_id)) : '';
                $bransjekategorier_terms = wp_get_post_terms(get_the_ID(), 'bransjekategori', array('fields' => 'names'));
                $poststed = get_field('poststed');
                $adresse = get_field('adresse');
                $postnummer = get_field('postnummer');
                $membership_level = bimverdi_get_membership_level(get_the_ID());
                $initials = bimverdi_get_initials(get_the_title());
                $bransje_display = !empty($bransjekategorier_terms) ? $bransjekategorier_terms[0] : '';

                // Build map URL for Google Maps
                $map_query_parts = array_filter([$adresse, $postnummer, $poststed, 'Norge']);
                $map_url = !empty($map_query_parts) ? 'https://www.google.com/maps/search/' . urlencode(implode(', ', $map_query_parts)) : '';
            ?>

            <!-- Card -->
            <div class="bg-white border border-[#E7E5E4] rounded-xl shadow-sm hover:shadow-md hover:border-[#D6D3D1] transition-all p-6 flex flex-col justify-between h-[285px]">

                <!-- Top Section -->
                <div>
                    <!-- Header: Logo + Badge -->
                    <div class="flex items-start justify-between mb-6">
                        <!-- Logo Circle -->
                        <div class="w-12 h-12 rounded-full bg-[#F5F5F4] shadow-sm flex items-center justify-center overflow-hidden flex-shrink-0">
                            <?php if ($logo_url): ?>
                                <img src="<?php echo esc_url($logo_url); ?>" alt="" class="w-10 h-10 object-contain">
                            <?php else: ?>
                                <span class="text-sm font-bold text-[#111827] tracking-tight"><?php echo esc_html($initials); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Membership Badge -->
                        <?php if ($membership_level === 'Partner'): ?>
                        <span class="inline-flex items-center text-xs font-medium text-white bg-[#111827] px-2.5 py-0.5 rounded-full">
                            Partner
                        </span>
                        <?php elseif ($membership_level === 'Deltaker'): ?>
                        <span class="inline-flex items-center text-xs font-medium text-[#111827] border border-[#111827] px-2.5 py-0.5 rounded-full">
                            Deltaker
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Company Name -->
                    <h2 class="text-xl font-bold text-[#111827] mb-2 leading-tight tracking-tight">
                        <?php the_title(); ?>
                        <?php echo bimverdi_admin_id_badge(); ?>
                    </h2>

                    <!-- Location -->
                    <?php if ($poststed): ?>
                    <div class="flex items-center gap-1 text-sm text-[#57534E]">
                        <?php if ($map_url): ?>
                        <a href="<?php echo esc_url($map_url); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1 hover:text-[#111827] transition-colors" title="Vis på kart">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span><?php echo esc_html($poststed); ?></span>
                        </a>
                        <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span><?php echo esc_html($poststed); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Footer Section -->
                <div class="flex items-center justify-between pt-4 border-t border-[#E7E5E4]">
                    <!-- Industry Label -->
                    <?php if ($bransje_display): ?>
                    <span class="text-xs font-medium text-[#57534E] uppercase tracking-wider">
                        <?php echo esc_html($bransje_display); ?>
                    </span>
                    <?php else: ?>
                    <span></span>
                    <?php endif; ?>

                    <!-- Link -->
                    <a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-1 text-sm font-bold text-[#111827] hover:opacity-70 transition-opacity">
                        Se profil
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </div>
            </div>

            <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <?php else: ?>

        <!-- No Results -->
        <div class="py-16 text-center">
            <div class="w-16 h-16 bg-[#F5F5F4] rounded-lg flex items-center justify-center mx-auto mb-5">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-[#A8A29E]"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-[#111827] mb-2">Ingen foretak funnet</h2>
            <p class="text-sm text-[#57534E] mb-6">Prøv å justere filterene dine eller søket</p>
            <a href="<?php echo get_post_type_archive_link('foretak'); ?>" class="inline-flex items-center gap-2 px-5 py-3 bg-[#111827] text-white text-sm font-medium rounded-lg hover:bg-[#1F2937] transition-colors">
                Vis alle foretak
            </a>
        </div>

        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>
