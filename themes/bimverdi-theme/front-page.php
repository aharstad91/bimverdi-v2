<?php
/**
 * Front Page Template
 *
 * Homepage for BIM Verdi - komprimert design
 * Variant B "oatmeal" design med varm palett
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<!-- Hero Section - Left aligned -->
<section class="bg-[#F7F5EF] text-[#1A1A1A] pt-16 pb-20">
    <div class="max-w-6xl mx-auto px-4 md:px-8">
        <div class="max-w-3xl">

            <!-- Badge -->
            <div class="mb-6">
                <span class="inline-flex items-center gap-2 bg-[#EFE9DE] pl-4 pr-3 py-2 rounded-full text-sm text-[#5A5A5A]">
                    Norges ledende nettverk for praktisk bruk av BIM og AI
                    <a href="<?php echo esc_url(home_url('/om-oss/')); ?>" class="font-semibold text-[#1A1A1A] hover:underline flex items-center gap-1">Les mer <span>&rsaquo;</span></a>
                </span>
            </div>

            <!-- Main Headline -->
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight text-[#1A1A1A]">
                Vi digitaliserer sammen i praktiske prosjekter.
            </h1>

            <!-- Subtitle -->
            <p class="text-lg md:text-xl text-[#5A5A5A] mb-10 max-w-2xl leading-relaxed">
                BIM Verdi er et bransjenettverk som kobler sammen aktører i byggenæringen for å dele kunnskap, erfaringer og verktøy.
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-wrap items-center gap-6">
                <a href="<?php echo esc_url(home_url('/logg-inn/')); ?>" class="px-6 py-3 bg-[#1A1A1A] text-white rounded-full font-medium hover:bg-[#333] transition-colors">
                    Bli med i nettverket
                </a>
                <a href="<?php echo esc_url(home_url('/om-oss/')); ?>" class="text-[#1A1A1A] font-medium hover:underline flex items-center gap-1">
                    Se hvordan det fungerer <span>&rarr;</span>
                </a>
            </div>

        </div>
    </div>
</section>

<main class="bg-[#F7F5EF]">

    <!-- Nytt i Nettverket Section - 2-column -->
    <section class="py-16 border-t border-[#D6D1C6]">
        <div class="max-w-6xl mx-auto px-4 md:px-8">

            <!-- Section header -->
            <div class="mb-10">
                <h2 class="text-3xl font-bold text-[#1A1A1A] mb-2">Nytt i nettverket</h2>
                <p class="text-[#5A5A5A]">De siste tilskuddene til deltakernettverket og verktøykatalogen.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-20">

                <!-- Medlemsbedrifter -->
                <?php
                $companies = get_posts(array(
                    'post_type' => 'foretak',
                    'posts_per_page' => 5,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC',
                ));
                $total_companies = wp_count_posts('foretak')->publish;
                ?>
                <div>
                    <div class="flex justify-between items-baseline mb-2">
                        <h3 class="text-lg font-bold text-[#1A1A1A]">Deltakere</h3>
                        <a href="<?php echo esc_url(home_url('/deltakere/')); ?>" class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A] flex items-center gap-1">Se alle (<?php echo $total_companies; ?>) <span>&rarr;</span></a>
                    </div>
                    <?php if (!empty($companies)): ?>
                    <div>
                        <?php foreach ($companies as $index => $company): ?>
                        <a href="<?php echo esc_url(get_permalink($company)); ?>"
                           class="flex items-center justify-between py-3 hover:bg-[#EFE9DE] transition-colors border-b border-[#D6D1C6]">
                            <span class="text-[#1A1A1A]"><?php echo esc_html($company->post_title); ?></span>
                            <span class="text-[#D6D1C6]">&rarr;</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-[#5A5A5A] py-4">Ingen deltakere ennå.</p>
                    <?php endif; ?>
                </div>

                <!-- Verktøy -->
                <?php
                $tools = get_posts(array(
                    'post_type' => 'verktoy',
                    'posts_per_page' => 5,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC',
                ));
                $total_tools = wp_count_posts('verktoy')->publish;
                ?>
                <div>
                    <div class="flex justify-between items-baseline mb-2">
                        <h3 class="text-lg font-bold text-[#1A1A1A]">Verktøy</h3>
                        <a href="<?php echo esc_url(home_url('/verktoy/')); ?>" class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A] flex items-center gap-1">Se alle (<?php echo $total_tools; ?>) <span>&rarr;</span></a>
                    </div>
                    <?php if (!empty($tools)): ?>
                    <div>
                        <?php foreach ($tools as $index => $tool): ?>
                        <a href="<?php echo esc_url(get_permalink($tool)); ?>"
                           class="flex items-center justify-between py-3 hover:bg-[#EFE9DE] transition-colors border-b border-[#D6D1C6]">
                            <span class="text-[#1A1A1A]"><?php echo esc_html($tool->post_title); ?></span>
                            <span class="text-[#D6D1C6]">&rarr;</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-[#5A5A5A] py-4">Ingen verktøy ennå.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>

    <!-- Kunnskapskilder Section - 50/50 split -->
    <section class="py-16 border-t border-[#D6D1C6]">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-20 items-start">

                <!-- Left: Text content -->
                <div class="md:pr-8">
                    <h2 class="text-3xl font-bold text-[#1A1A1A] mb-4">Se helheten i regelverket</h2>
                    <p class="text-lg text-[#5A5A5A] mb-6 leading-relaxed">
                        Vi samler og strukturerer kunnskap fra TEK17, standarder og veiledere. Se hvordan kravene henger sammen med verktøy og bransjepraksis.
                    </p>
                    <a href="<?php echo esc_url(home_url('/kunnskapskilder/')); ?>" class="inline-block px-6 py-3 bg-[#1A1A1A] text-white rounded-full font-medium hover:bg-[#333] transition-colors">
                        Utforsk kunnskapskilder
                    </a>
                </div>

                <!-- Right: List with card -->
                <?php
                $sources = get_posts(array(
                    'post_type' => 'kunnskapskilde',
                    'posts_per_page' => 5,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC',
                ));
                $total_sources = wp_count_posts('kunnskapskilde')->publish;
                ?>
                <div>
                    <div class="flex justify-between items-baseline mb-2">
                        <h3 class="text-lg font-bold text-[#1A1A1A]">Kunnskapskilder</h3>
                        <a href="<?php echo esc_url(home_url('/kunnskapskilder/')); ?>" class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A] flex items-center gap-1">Se alle (<?php echo $total_sources; ?>) <span>&rarr;</span></a>
                    </div>
                    <?php if (!empty($sources)): ?>
                    <div>
                        <?php foreach ($sources as $index => $source): ?>
                        <a href="<?php echo esc_url(get_permalink($source)); ?>"
                           class="flex items-center justify-between py-3 hover:bg-[#EFE9DE] -mx-2 px-2 rounded transition-colors <?php echo $index < count($sources) - 1 ? 'border-b border-[#D6D1C6]' : ''; ?>">
                            <span class="text-[#1A1A1A]"><?php echo esc_html($source->post_title); ?></span>
                            <span class="text-[#D6D1C6]">&rarr;</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-[#5A5A5A] py-4">Ingen kunnskapskilder ennå.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>

    <!-- Seneste Nytt Section -->
    <section class="py-16 border-t border-[#D6D1C6]">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-[#1A1A1A]">Seneste Nytt</h2>
                <a href="<?php echo esc_url(home_url('/artikler/')); ?>" class="text-[#1A1A1A] font-medium hover:underline flex items-center gap-1">Se alle <span>&rarr;</span></a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php
                $articles = get_posts(array(
                    'post_type' => 'artikkel',
                    'posts_per_page' => 4,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC',
                ));

                if (empty($articles)) {
                    $articles = get_posts(array(
                        'post_type' => 'post',
                        'posts_per_page' => 4,
                        'post_status' => 'publish',
                        'orderby' => 'date',
                        'order' => 'DESC',
                    ));
                }

                if (!empty($articles)):
                    foreach ($articles as $article):
                        $thumbnail = get_the_post_thumbnail_url($article->ID, 'medium');
                        $date = get_the_date('d. M Y', $article->ID);
                        ?>
                        <div class="group">
                            <?php if ($thumbnail): ?>
                                <div class="aspect-video bg-[#EFE9DE] rounded-lg overflow-hidden mb-4">
                                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($article->post_title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                </div>
                            <?php else: ?>
                                <div class="aspect-video bg-[#EFE9DE] rounded-lg flex items-center justify-center mb-4">
                                    <svg class="w-12 h-12 text-[#D6D1C6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                    </svg>
                                </div>
                            <?php endif; ?>

                            <div class="text-sm text-[#5A5A5A] mb-2"><?php echo esc_html($date); ?></div>
                            <h3 class="font-bold text-[#1A1A1A] mb-2 group-hover:text-[#5A5A5A] transition-colors"><?php echo esc_html($article->post_title); ?></h3>
                            <p class="text-sm text-[#5A5A5A] mb-3 line-clamp-2"><?php echo wp_trim_words($article->post_excerpt ?: strip_tags($article->post_content), 15); ?></p>
                            <a href="<?php echo esc_url(get_permalink($article)); ?>" class="text-[#1A1A1A] font-medium text-sm hover:underline flex items-center gap-1">
                                Les mer <span>&rarr;</span>
                            </a>
                        </div>
                        <?php
                    endforeach;
                else:
                    ?>
                    <div class="md:col-span-4 text-center text-[#5A5A5A] py-12">
                        <svg class="w-16 h-16 mx-auto text-[#D6D1C6] mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                        </svg>
                        <p class="text-lg">Ingen artikler publisert ennå.</p>
                    </div>
                    <?php
                endif;
                ?>
            </div>
        </div>
    </section>

    <!-- Våre Temagrupper Section - Redesigned -->
    <section class="py-16 border-t border-[#D6D1C6]">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <h2 class="text-3xl font-bold text-[#1A1A1A] text-center mb-12">Våre Temagrupper</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <?php
                $theme_groups = array(
                    array(
                        'title' => 'Modellkvalitet',
                        'description' => 'Fokus på MMI, standarder og kvalitetssikring av modeller for bedre samhandling.',
                        'icon' => 'layers',
                        'slug' => 'modellkvalitet',
                    ),
                    array(
                        'title' => 'ByggesaksBIM',
                        'description' => 'Digitalisering av byggesaksprosessen og bruk av BIM mot offentlige myndigheter.',
                        'icon' => 'building',
                        'slug' => 'byggesaksbim',
                    ),
                    array(
                        'title' => 'ProsjektBIM',
                        'description' => 'Beste praksis for BIM-koordinering og ledelse i store byggeprosjekter.',
                        'icon' => 'mountain',
                        'slug' => 'prosjektbim',
                    ),
                    array(
                        'title' => 'EiendomsBIM',
                        'description' => 'FDV-dokumentasjon og bruk av BIM i driftsfasen for eiendomsforvaltere.',
                        'icon' => 'box',
                        'slug' => 'eiendomsbim',
                    ),
                    array(
                        'title' => 'MiljøBIM',
                        'description' => 'Bruk av BIM for klimagassregnskap, ombruk og bærekraftige materialvalg.',
                        'icon' => 'refresh',
                        'slug' => 'miljobim',
                    ),
                    array(
                        'title' => 'BIMtech',
                        'description' => 'Utforsking av ny teknologi, API-er, skripting og innovasjon i bransjen.',
                        'icon' => 'zap',
                        'slug' => 'bimtech',
                    ),
                );

                foreach ($theme_groups as $group):
                    // Icon SVGs
                    $icons = array(
                        'layers' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>',
                        'building' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
                        'mountain' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 21l6-6 4 4 8-8M17 21h4v-4"/>',
                        'box' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>',
                        'refresh' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>',
                        'zap' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>',
                    );
                ?>
                <a href="<?php echo esc_url(home_url('/temagrupper/' . $group['slug'] . '/')); ?>"
                   class="block border border-[#D6D1C6] rounded-lg p-6 hover:bg-[#EFE9DE] transition-colors group">

                    <!-- Header row: icon left, arrow right -->
                    <div class="flex justify-between items-start mb-6">
                        <div class="w-12 h-12 rounded-full bg-[#EFE9DE] flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#1A1A1A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <?php echo $icons[$group['icon']]; ?>
                            </svg>
                        </div>
                        <span class="text-[#D6D1C6] group-hover:text-[#5A5A5A] transition-colors">&rarr;</span>
                    </div>

                    <!-- Title -->
                    <h3 class="text-xl font-bold text-[#1A1A1A] mb-2"><?php echo esc_html($group['title']); ?></h3>

                    <!-- Description -->
                    <p class="text-[#5A5A5A] text-sm mb-4 line-clamp-2"><?php echo esc_html($group['description']); ?></p>

                    <!-- Read more link -->
                    <span class="text-[#1A1A1A] font-medium text-sm">Les mer</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Kommende Arrangementer Section - List format -->
    <section class="py-16 border-t border-[#D6D1C6]">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-[#1A1A1A]">Kommende Arrangementer</h2>
                <a href="<?php echo esc_url(home_url('/arrangementer/')); ?>" class="text-[#5A5A5A] hover:text-[#1A1A1A] font-medium">Se alle</a>
            </div>

            <?php
            $events = get_posts(array(
                'post_type' => 'arrangement',
                'posts_per_page' => 3,
                'meta_query' => array(
                    array(
                        'key' => 'arrangement_status_toggle',
                        'value' => 'kommende',
                        'compare' => '=',
                    ),
                ),
                'meta_key' => 'arrangement_dato',
                'orderby' => 'meta_value',
                'order' => 'ASC',
            ));

            if (empty($events)) {
                $events = get_posts(array(
                    'post_type' => 'arrangement',
                    'posts_per_page' => 3,
                    'meta_key' => 'arrangement_dato',
                    'orderby' => 'meta_value',
                    'order' => 'ASC',
                ));
            }

            if (!empty($events)):
            ?>
            <div class="border border-[#D6D1C6] rounded-lg overflow-hidden">
                <?php
                $event_count = count($events);
                $e = 0;
                foreach ($events as $event):
                    $e++;
                    $event_date = get_field('arrangement_dato', $event->ID) ?: get_field('dato', $event->ID) ?: date('Y-m-d', strtotime($event->post_date));
                    $event_time_start = get_field('arrangement_tid_start', $event->ID) ?: get_field('tid_start', $event->ID) ?: '';
                    $event_time_end = get_field('arrangement_tid_slutt', $event->ID) ?: get_field('tid_slutt', $event->ID) ?: '';
                    $event_type = wp_get_post_terms($event->ID, 'arrangementstype', array('fields' => 'names'));
                    $event_format = get_field('arrangement_format', $event->ID) ?: get_field('format', $event->ID) ?: '';

                    // Parse date
                    $date_obj = DateTime::createFromFormat('Y-m-d', $event_date) ?: DateTime::createFromFormat('Ymd', $event_date);
                    $day = $date_obj ? $date_obj->format('d') : '';
                    $month = $date_obj ? strtoupper($date_obj->format('M')) : '';

                    // Norwegian month names
                    $month_map = array('JAN' => 'JAN', 'FEB' => 'FEB', 'MAR' => 'MAR', 'APR' => 'APR', 'MAY' => 'MAI', 'JUN' => 'JUN', 'JUL' => 'JUL', 'AUG' => 'AUG', 'SEP' => 'SEP', 'OCT' => 'OKT', 'NOV' => 'NOV', 'DEC' => 'DES');
                    $month = isset($month_map[$month]) ? $month_map[$month] : $month;
                ?>
                <div class="flex flex-col md:flex-row md:items-center gap-4 md:gap-8 px-6 py-5 <?php echo $e < $event_count ? 'border-b border-[#D6D1C6]' : ''; ?>">

                    <!-- Date -->
                    <div class="flex-shrink-0 w-20">
                        <div class="text-sm font-bold text-[#5A5A5A]"><?php echo esc_html($day . '. ' . $month); ?></div>
                    </div>

                    <!-- Event details -->
                    <div class="flex-grow">
                        <h3 class="font-bold text-[#1A1A1A] mb-1"><?php echo esc_html($event->post_title); ?></h3>
                        <div class="flex flex-wrap items-center gap-4 text-sm text-[#5A5A5A]">
                            <?php if ($event_format || !empty($event_type)): ?>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <?php echo esc_html($event_format ?: (!empty($event_type) ? $event_type[0] : '')); ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($event_time_start): ?>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <?php echo esc_html($event_time_start); ?><?php echo $event_time_end ? ' - ' . esc_html($event_time_end) : ''; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- CTA button -->
                    <div class="flex-shrink-0">
                        <a href="<?php echo esc_url(get_permalink($event)); ?>"
                           class="inline-block px-5 py-2 border border-[#1A1A1A] text-[#1A1A1A] rounded font-medium text-sm hover:bg-[#1A1A1A] hover:text-white transition-colors">
                            Meld deg på
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="border border-[#D6D1C6] rounded-lg p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-[#D6D1C6] mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-lg text-[#5A5A5A]">Ingen arrangementer planlagt akkurat nå.</p>
                <p class="text-sm text-[#5A5A5A] mt-2">Sjekk tilbake snart for kommende arrangementer.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Hvorfor dette er viktig Section -->
    <section class="py-16 border-t border-[#D6D1C6]">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <h2 class="text-3xl font-bold text-[#1A1A1A] text-center mb-12">Hvorfor dette er viktig</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 text-center">

                <!-- Ett søk -->
                <div>
                    <div class="w-16 h-16 rounded-full bg-[#EFE9DE] flex items-center justify-center mx-auto mb-6">
                        <svg class="w-7 h-7 text-[#1A1A1A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-[#1A1A1A] mb-3">Ett søk</h3>
                    <p class="text-[#5A5A5A]">Samler informasjon fra spredte kilder i ett enkelt grensesnitt. Spar tid på å lete etter dokumentasjon.</p>
                </div>

                <!-- Alltid oppdatert -->
                <div>
                    <div class="w-16 h-16 rounded-full bg-[#EFE9DE] flex items-center justify-center mx-auto mb-6">
                        <svg class="w-7 h-7 text-[#1A1A1A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-[#1A1A1A] mb-3">Alltid oppdatert</h3>
                    <p class="text-[#5A5A5A]">Vi overvåker endringer i regelverk og standarder, slik at du alltid jobber med gyldige versjoner.</p>
                </div>

                <!-- Smart kobling -->
                <div>
                    <div class="w-16 h-16 rounded-full bg-[#EFE9DE] flex items-center justify-center mx-auto mb-6">
                        <svg class="w-7 h-7 text-[#1A1A1A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-[#1A1A1A] mb-3">Smart kobling</h3>
                    <p class="text-[#5A5A5A]">Vi kobler teori med praksis ved å vise hvilke verktøy som støtter hvilke krav og prosesser.</p>
                </div>

            </div>
        </div>
    </section>

</main>

<?php get_footer();
