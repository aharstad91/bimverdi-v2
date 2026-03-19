<?php
/**
 * Single Artikkel Template
 *
 * Displays a single article with author byline and company info
 *
 * @package BIMVerdi
 */

get_header();

// Get article data
$artikkel_bedrift = get_field('artikkel_bedrift');
$artikkel_ingress = get_field('artikkel_ingress');
$author_id = (int) get_post_field('post_author', get_the_ID());
$author_name = get_the_author_meta('display_name', $author_id);
if (empty($author_name)) {
    $first = get_the_author_meta('first_name', $author_id);
    $last  = get_the_author_meta('last_name', $author_id);
    $author_name = trim($first . ' ' . $last);
}
if (empty($author_name)) {
    $author_name = get_the_author_meta('user_login', $author_id);
}
$author_avatar = get_avatar_url($author_id, array('size' => 80));
$medforfattere_ids = get_field('artikkel_medforfattere');

// Category from taxonomy (artikkelkategori) instead of ACF field
$artikkel_kategorier = wp_get_post_terms(get_the_ID(), 'artikkelkategori');
$artikkel_kategori_name = !empty($artikkel_kategorier) ? $artikkel_kategorier[0]->name : '';

// If no explicit company on article, derive from author's user meta
if (empty($artikkel_bedrift) && $author_id) {
    $artikkel_bedrift = get_user_meta($author_id, 'bimverdi_company_id', true);
    if (empty($artikkel_bedrift)) {
        $artikkel_bedrift = get_user_meta($author_id, 'bim_verdi_company_id', true);
    }
    if (empty($artikkel_bedrift)) {
        $artikkel_bedrift = get_field('tilknyttet_foretak', 'user_' . $author_id);
    }
}

// Get company info
$company_name = '';
$company_url = '';
if ($artikkel_bedrift) {
    $company_name = get_the_title($artikkel_bedrift);
    $company_url = get_permalink($artikkel_bedrift);
}

// Get temagrupper
$temagrupper = get_the_terms(get_the_ID(), 'temagruppe');
?>

<article class="bg-white">

    <!-- Back link -->
    <div class="container mx-auto px-4 pt-6">
        <a href="<?php echo esc_url(get_post_type_archive_link('artikkel')); ?>" class="inline-flex items-center gap-1.5 text-sm text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Artikler
        </a>
    </div>

    <!-- Hero section -->
    <div class="bg-gradient-to-b from-gray-50 to-white py-12 lg:py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto">

                <!-- Category badge -->
                <?php if ($artikkel_kategori_name) : ?>
                    <div class="mb-4">
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full bg-[#F5F5F4] text-[#57534E]">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                            <?php echo esc_html($artikkel_kategori_name); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <!-- Title -->
                <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                    <?php the_title(); ?>
                    <?php echo bimverdi_admin_id_badge(); ?>
                </h1>

                <!-- Ingress -->
                <?php if ($artikkel_ingress) : ?>
                    <p class="text-xl text-gray-600 leading-relaxed mb-6">
                        <?php echo esc_html($artikkel_ingress); ?>
                    </p>
                <?php endif; ?>

                <!-- Author & meta -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 pt-4 border-t border-gray-200">

                    <!-- Author -->
                    <div class="flex items-center gap-3">
                        <img src="<?php echo esc_url($author_avatar); ?>" alt="<?php echo esc_attr($author_name); ?>" class="w-12 h-12 rounded-full bg-[#F5F5F4] object-cover">
                        <div>
                            <div class="font-semibold text-gray-900"><?php echo esc_html($author_name); ?></div>
                            <?php if ($company_name) : ?>
                                <a href="<?php echo esc_url($company_url); ?>" class="text-sm text-orange-600 hover:underline">
                                    <?php echo esc_html($company_name); ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($medforfattere_ids)) : ?>
                                <div class="text-sm text-gray-500 mt-0.5">
                                    med <?php
                                    $names = array();
                                    foreach ($medforfattere_ids as $uid) {
                                        $u = get_userdata($uid);
                                        if ($u) {
                                            $names[] = esc_html($u->display_name);
                                        }
                                    }
                                    echo implode(', ', $names);
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Date & Reading time -->
                    <div class="sm:ml-auto flex items-center gap-1 text-sm text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                        <?php echo bimverdi_format_date(); ?>
                        <span class="mx-1">&middot;</span>
                        <?php echo esc_html(bimverdi_reading_time()); ?>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- Featured image -->
    <?php if (has_post_thumbnail()) : ?>
        <div class="container mx-auto px-4 -mt-4">
            <div class="max-w-4xl mx-auto">
                <img src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>"
                     alt="<?php the_title_attribute(); ?>"
                     class="w-full h-auto rounded-lg shadow-sm">
            </div>
        </div>
    <?php endif; ?>

    <!-- Content -->
    <div class="container mx-auto px-4 py-8 lg:py-12">
        <div class="max-w-3xl mx-auto">

            <!-- Article content -->
            <div class="prose prose-lg max-w-none">
                <?php the_content(); ?>
            </div>

            <!-- Temagrupper -->
            <?php if ($temagrupper && !is_wp_error($temagrupper)) : ?>
                <div class="mt-8 pt-6 border-t border-[#E5E0D8]">
                    <span class="text-sm text-[#5A5A5A] mr-2">Temagrupper:</span>
                    <?php foreach ($temagrupper as $temagruppe) : ?>
                        <span class="inline-flex items-center text-xs font-medium px-2.5 py-1 rounded-full bg-[#F5F5F4] text-[#57534E] mr-2">
                            <?php echo esc_html($temagruppe->name); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Share & actions -->
            <div class="mt-8 pt-6 border-t border-[#E5E0D8]">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm text-[#5A5A5A]">Del artikkelen:</span>
                    <button onclick="window.open('https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(window.location.href), '_blank')" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[#57534E] bg-white border border-[#E5E0D8] rounded-lg hover:bg-[#FAFAF9] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        LinkedIn
                    </button>
                    <button onclick="window.location.href='mailto:?subject=' + encodeURIComponent(document.title) + '&body=' + encodeURIComponent(window.location.href)" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[#57534E] bg-white border border-[#E5E0D8] rounded-lg hover:bg-[#FAFAF9] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        E-post
                    </button>
                    <button id="copy-link-btn" onclick="navigator.clipboard.writeText(window.location.href).then(function(){ var btn=document.getElementById('copy-link-btn'); var orig=btn.innerHTML; btn.innerHTML='<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; width=&quot;14&quot; height=&quot;14&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;2&quot;><path d=&quot;M20 6 9 17l-5-5&quot;/></svg> Kopiert!'; setTimeout(function(){ btn.innerHTML=orig; }, 2000); })" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[#57534E] bg-white border border-[#E5E0D8] rounded-lg hover:bg-[#FAFAF9] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        Kopier lenke
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Author box -->
    <div class="container mx-auto px-4 py-8 lg:py-12">
        <div class="max-w-3xl mx-auto">
            <div class="border-t border-[#E5E0D8] pt-8">
                <div class="flex flex-col sm:flex-row gap-4">
                    <img src="<?php echo esc_url($author_avatar); ?>" alt="<?php echo esc_attr($author_name); ?>" class="w-20 h-20 rounded-full bg-[#F5F5F4] object-cover">
                    <div class="flex-1">
                        <h3 class="font-semibold text-[#1A1A1A] mb-1">Om forfatteren</h3>
                        <p class="text-[#1A1A1A] font-medium"><?php echo esc_html($author_name); ?></p>
                        <?php
                        $author_bio = get_the_author_meta('description', $author_id);
                        if ($author_bio) : ?>
                            <p class="text-[#5A5A5A] text-sm mt-1 mb-3"><?php echo esc_html($author_bio); ?></p>
                        <?php endif; ?>
                        <?php if ($company_name) : ?>
                            <p class="text-[#5A5A5A] text-sm mb-3">
                                <?php echo esc_html($company_name); ?>
                            </p>
                            <a href="<?php echo esc_url($company_url); ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-[#FF8B5E] hover:underline">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                Se foretakets profil
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($medforfattere_ids)) : ?>
                            <div class="mt-4 pt-4 border-t border-[#E5E0D8]">
                                <h4 class="text-sm font-semibold text-[#57534E] mb-2">Medforfattere</h4>
                                <div class="flex flex-wrap gap-3">
                                    <?php foreach ($medforfattere_ids as $uid) :
                                        $u = get_userdata($uid);
                                        if (!$u) continue;
                                        $u_avatar = get_avatar_url($uid, array('size' => 40));
                                    ?>
                                    <div class="flex items-center gap-2">
                                        <img src="<?php echo esc_url($u_avatar); ?>" alt="<?php echo esc_attr($u->display_name); ?>" class="w-8 h-8 rounded-full bg-[#F5F5F4] object-cover">
                                        <span class="text-sm text-[#57534E]"><?php echo esc_html($u->display_name); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- More articles from same company -->
    <?php
    if ($artikkel_bedrift) :
        $related_articles = new WP_Query(array(
            'post_type' => 'artikkel',
            'post_status' => 'publish',
            'posts_per_page' => 3,
            'post__not_in' => array(get_the_ID()),
            'meta_query' => array(
                array(
                    'key' => 'artikkel_bedrift',
                    'value' => $artikkel_bedrift,
                ),
            ),
        ));

        if ($related_articles->have_posts()) :
    ?>
    <div class="container mx-auto px-4 py-8 lg:py-12 border-t border-[#E5E0D8]">
        <div class="max-w-5xl mx-auto">
            <h2 class="text-xl font-bold text-[#1A1A1A] mb-6">
                Flere artikler fra <?php echo esc_html($company_name); ?>
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <?php while ($related_articles->have_posts()) : $related_articles->the_post(); ?>
                    <div class="bg-white rounded-lg border border-[#E5E0D8] overflow-hidden">
                        <div class="p-5">
                            <h3 class="font-semibold text-[#1A1A1A] mb-2">
                                <a href="<?php the_permalink(); ?>" class="hover:text-[#FF8B5E] transition-colors">
                                    <?php the_title(); ?>
                                </a>
                            </h3>
                            <?php if (has_excerpt() || get_field('artikkel_ingress')) : ?>
                                <p class="text-sm text-[#5A5A5A] mb-3"><?php echo wp_trim_words(get_field('artikkel_ingress') ?: get_the_excerpt(), 20); ?></p>
                            <?php endif; ?>
                            <span class="text-xs text-[#78716C]"><?php echo bimverdi_format_date(get_the_ID()); ?></span>
                        </div>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </div>
    <?php
        endif;
    endif;
    ?>

    <!-- Related articles from same temagruppe(s) -->
    <?php
    if ($temagrupper && !is_wp_error($temagrupper)) :
        $exclude_ids = array(get_the_ID());
        // Collect IDs already shown from company section
        if (isset($related_articles) && $related_articles->have_posts()) {
            $exclude_ids = array_merge($exclude_ids, wp_list_pluck($related_articles->posts, 'ID'));
        }

        $temagruppe_articles = new WP_Query(array(
            'post_type' => 'artikkel',
            'post_status' => 'publish',
            'posts_per_page' => 3,
            'post__not_in' => $exclude_ids,
            'tax_query' => array(array(
                'taxonomy' => 'temagruppe',
                'field' => 'term_id',
                'terms' => wp_list_pluck($temagrupper, 'term_id'),
            )),
        ));

        if ($temagruppe_articles->have_posts()) :
    ?>
    <div class="container mx-auto px-4 py-8 lg:py-12 border-t border-[#E5E0D8]">
        <div class="max-w-5xl mx-auto">
            <h2 class="text-xl font-bold text-[#1A1A1A] mb-6">
                Relaterte artikler
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <?php while ($temagruppe_articles->have_posts()) : $temagruppe_articles->the_post(); ?>
                    <div class="bg-white rounded-lg border border-[#E5E0D8] overflow-hidden">
                        <div class="p-5">
                            <h3 class="font-semibold text-[#1A1A1A] mb-2">
                                <a href="<?php the_permalink(); ?>" class="hover:text-[#FF8B5E] transition-colors">
                                    <?php the_title(); ?>
                                </a>
                            </h3>
                            <?php if (has_excerpt() || get_field('artikkel_ingress')) : ?>
                                <p class="text-sm text-[#5A5A5A] mb-3"><?php echo wp_trim_words(get_field('artikkel_ingress') ?: get_the_excerpt(), 20); ?></p>
                            <?php endif; ?>
                            <span class="text-xs text-[#78716C]"><?php echo bimverdi_format_date(); ?></span>
                        </div>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </div>
    <?php
        endif;
    endif;
    ?>

</article>

<?php get_footer(); ?>
