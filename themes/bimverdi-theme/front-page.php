<?php
/**
 * Front Page Template
 *
 * Homepage for BIM Verdi - hybrid av prod og v2
 * Variant B "oatmeal" design med varm palett
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<!-- Hero Section -->
<section class="bg-[#F7F5EF] text-[#1A1A1A] pt-16 pb-20">
    <div class="max-w-6xl mx-auto px-4 md:px-8">
        <div class="max-w-4xl mx-auto text-center">

            <!-- Badge -->
            <div class="mb-6">
                <span class="inline-block bg-[#EFE9DE] px-4 py-2 rounded-full text-sm font-semibold text-[#5A5A5A]">
                    NORGES LEDENDE NETTVERK FOR PRAKTISK BRUK AV BIM OG AI
                </span>
            </div>

            <!-- Main Headline -->
            <h1 class="text-5xl md:text-6xl font-bold mb-8 leading-tight text-[#1A1A1A]">
                Vi digitaliserer sammen<br>i praktiske prosjekter
            </h1>

            <!-- Subtitle -->
            <p class="text-lg md:text-xl text-[#5A5A5A] mb-12 max-w-2xl mx-auto leading-relaxed">
                BIM Verdi er et bransjenettverk som kobler sammen aktører i byggenæringen for å dele kunnskap, erfaringer og verktøy. Vi jobber praktisk med digitalisering gjennom temagrupper og pilotprosjekter.
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-wrap gap-4 justify-center mb-16">
                <a href="<?php echo esc_url(home_url('/bli-medlem/')); ?>" class="px-8 py-3 bg-[#FF8B5E] text-white rounded font-semibold hover:bg-[#e67a4d] transition-colors">
                    Bli med i nettverket
                </a>
                <a href="<?php echo esc_url(home_url('/medlemmer/')); ?>" class="px-8 py-3 bg-[#EFE9DE] text-[#1A1A1A] rounded font-semibold hover:bg-[#E5DFD0] transition-colors">
                    Se medlemmer
                </a>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 pt-12 border-t border-[#D6D1C6]">

                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-[#1A1A1A] mb-2">70+</div>
                    <p class="text-sm text-[#5A5A5A] font-semibold">MEDLEMSBEDRIFTER</p>
                </div>

                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-[#1A1A1A] mb-2">7</div>
                    <p class="text-sm text-[#5A5A5A] font-semibold">TEMAGRUPPER</p>
                </div>

                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-[#1A1A1A] mb-2">10+</div>
                    <p class="text-sm text-[#5A5A5A] font-semibold">ÅRS ERFARING</p>
                </div>

                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-[#1A1A1A] mb-2">50+</div>
                    <p class="text-sm text-[#5A5A5A] font-semibold">ARRANGEMENTER</p>
                </div>

            </div>

        </div>
    </div>
</section>

<main class="bg-[#F7F5EF]">

    <!-- Theme Groups Section -->
    <section class="py-20 border-t border-[#D6D1C6]">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="flex justify-between items-center mb-12">
                <h2 class="text-4xl font-bold text-[#1A1A1A]">Våre Temagrupper</h2>
                <a href="<?php echo esc_url(home_url('/temagrupper/')); ?>" class="text-[#1A1A1A] font-semibold hover:underline">Se alle →</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                <?php
                $theme_groups = array(
                    array(
                        'title' => 'Modellkvalitet',
                        'description' => 'Fokus på bestpraksis for BIM-modellering og datakvalitet',
                        'icon' => '📐',
                        'slug' => 'modellkvalitet',
                    ),
                    array(
                        'title' => 'ByggesaksBIM',
                        'description' => 'Digitalisering av byggesaksflyt og offentlig dialog',
                        'icon' => '📋',
                        'slug' => 'byggesaksbim',
                    ),
                    array(
                        'title' => 'ProsjektBIM',
                        'description' => 'BIM i prosjektstyring og samarbeid',
                        'icon' => '🚀',
                        'slug' => 'prosjektbim',
                    ),
                    array(
                        'title' => 'EiendomsBIM',
                        'description' => 'BIM for eiendomsforvaltning og drift',
                        'icon' => '🏢',
                        'slug' => 'eiendomsbim',
                    ),
                    array(
                        'title' => 'MiljøBIM',
                        'description' => 'BIM for miljø- og bærekraftanalyse',
                        'icon' => '🌱',
                        'slug' => 'miljobim',
                    ),
                    array(
                        'title' => 'SirkBIM',
                        'description' => 'Sirkulærøkonomi og gjenbruk i BIM',
                        'icon' => '♻️',
                        'slug' => 'sirkbim',
                    ),
                    array(
                        'title' => 'BIMtech',
                        'description' => 'Teknologi, API-er og integrasjoner',
                        'icon' => '⚙️',
                        'slug' => 'bimtech',
                    ),
                );

                foreach ($theme_groups as $group):
                    ?>
                    <div class="bg-white rounded-lg p-6 border border-[#D6D1C6] hover:shadow-md transition-all">
                        <div class="text-4xl mb-4"><?php echo $group['icon']; ?></div>
                        <h3 class="text-xl font-bold mb-3 text-[#1A1A1A]"><?php echo esc_html($group['title']); ?></h3>
                        <p class="text-[#5A5A5A] text-sm mb-4"><?php echo esc_html($group['description']); ?></p>
                        <a href="<?php echo esc_url(home_url('/temagrupper/' . $group['slug'] . '/')); ?>"
                           class="text-[#1A1A1A] font-semibold hover:underline">Les mer →</a>
                    </div>
                    <?php
                endforeach;
                ?>
            </div>
        </div>
    </section>

    <!-- Recent Events Section -->
    <section class="py-20 border-t border-[#D6D1C6]">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="flex justify-between items-center mb-12">
                <h2 class="text-4xl font-bold text-[#1A1A1A]">Kommende Arrangementer</h2>
                <a href="<?php echo esc_url(home_url('/arrangementer/')); ?>" class="text-[#1A1A1A] font-semibold hover:underline">Se alle →</a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
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

                // Fallback if no events with 'kommende' status, get any upcoming events
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
                    foreach ($events as $event):
                        $event_date = get_field('arrangement_dato', $event->ID) ?: get_field('dato', $event->ID) ?: date('Y-m-d', strtotime($event->post_date));
                        $event_type = wp_get_post_terms($event->ID, 'arrangementstype', array('fields' => 'names'));
                        $thumbnail = get_the_post_thumbnail_url($event->ID, 'medium');
                        ?>
                        <div class="bg-white rounded-lg overflow-hidden border border-[#D6D1C6] hover:shadow-md transition-all">
                            <?php if ($thumbnail): ?>
                                <div class="aspect-video bg-[#EFE9DE]">
                                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($event->post_title); ?>" class="w-full h-full object-cover">
                                </div>
                            <?php else: ?>
                                <div class="aspect-video bg-[#EFE9DE] flex items-center justify-center">
                                    <span class="text-4xl">📅</span>
                                </div>
                            <?php endif; ?>

                            <div class="p-6">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="inline-block bg-[#1A1A1A] text-white text-xs font-bold px-3 py-1 rounded">
                                        <?php
                                        $date_obj = DateTime::createFromFormat('Y-m-d', $event_date) ?: DateTime::createFromFormat('Ymd', $event_date);
                                        if ($date_obj) {
                                            echo strtoupper($date_obj->format('d. M'));
                                        } else {
                                            echo esc_html($event_date);
                                        }
                                        ?>
                                    </div>
                                    <?php if (!empty($event_type)): ?>
                                        <span class="text-xs bg-[#EFE9DE] text-[#5A5A5A] px-2 py-1 rounded"><?php echo esc_html($event_type[0]); ?></span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="font-bold text-[#1A1A1A] mb-2 text-lg"><?php echo esc_html($event->post_title); ?></h3>
                                <p class="text-sm text-[#5A5A5A] mb-4"><?php echo wp_trim_words($event->post_excerpt ?: strip_tags($event->post_content), 20); ?></p>
                                <a href="<?php echo esc_url(get_permalink($event)); ?>" class="text-[#1A1A1A] font-semibold text-sm hover:underline">
                                    Meld deg på →
                                </a>
                            </div>
                        </div>
                        <?php
                    endforeach;
                else:
                    ?>
                    <div class="md:col-span-3 text-center text-[#5A5A5A] py-12">
                        <span class="text-5xl mb-4 block">📅</span>
                        <p class="text-lg">Ingen arrangementer planlagt akkurat nå.</p>
                        <p class="text-sm mt-2">Sjekk tilbake snart for kommende arrangementer.</p>
                    </div>
                    <?php
                endif;
                ?>
            </div>
        </div>
    </section>

    <!-- Seneste Nytt Section -->
    <section class="py-20 border-t border-[#D6D1C6]">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="flex justify-between items-center mb-12">
                <h2 class="text-4xl font-bold text-[#1A1A1A]">Seneste Nytt</h2>
                <a href="<?php echo esc_url(home_url('/artikler/')); ?>" class="text-[#1A1A1A] font-semibold hover:underline">Se alle →</a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php
                // Try artikkel CPT first, fallback to posts
                $articles = get_posts(array(
                    'post_type' => 'artikkel',
                    'posts_per_page' => 4,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC',
                ));

                // Fallback to regular posts if no articles
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
                                    <span class="text-4xl">📰</span>
                                </div>
                            <?php endif; ?>

                            <div class="text-sm text-[#5A5A5A] mb-2"><?php echo esc_html($date); ?></div>
                            <h3 class="font-bold text-[#1A1A1A] mb-2 group-hover:text-[#5A5A5A] transition-colors"><?php echo esc_html($article->post_title); ?></h3>
                            <p class="text-sm text-[#5A5A5A] mb-3"><?php echo wp_trim_words($article->post_excerpt ?: strip_tags($article->post_content), 15); ?></p>
                            <a href="<?php echo esc_url(get_permalink($article)); ?>" class="text-[#1A1A1A] font-semibold text-sm hover:underline">
                                Les mer →
                            </a>
                        </div>
                        <?php
                    endforeach;
                else:
                    ?>
                    <div class="md:col-span-4 text-center text-[#5A5A5A] py-12">
                        <span class="text-5xl mb-4 block">📰</span>
                        <p class="text-lg">Ingen artikler publisert ennå.</p>
                    </div>
                    <?php
                endif;
                ?>
            </div>
        </div>
    </section>

    <!-- Nye i Nettverket Section -->
    <section class="py-20 border-t border-[#D6D1C6]">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <h2 class="text-4xl font-bold text-[#1A1A1A] mb-12">Nye i Nettverket</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-16">

                <!-- Nye deltakere -->
                <div>
                    <h3 class="text-xl font-bold text-[#1A1A1A] mb-6 flex items-center gap-2">
                        <span class="text-2xl">🏢</span>
                        Nye deltakere
                    </h3>
                    <?php
                    $companies = get_posts(array(
                        'post_type' => 'foretak',
                        'posts_per_page' => 5,
                        'post_status' => 'publish',
                        'orderby' => 'date',
                        'order' => 'DESC',
                    ));

                    if (!empty($companies)):
                        ?>
                        <div class="bg-white rounded-lg border border-[#D6D1C6] overflow-hidden">
                            <table class="w-full">
                                <tbody>
                                    <?php foreach ($companies as $index => $company): ?>
                                        <tr class="<?php echo $index < count($companies) - 1 ? 'border-b border-[#D6D1C6]' : ''; ?>">
                                            <td class="px-4 py-3">
                                                <a href="<?php echo esc_url(get_permalink($company)); ?>" class="text-[#1A1A1A] font-medium hover:underline">
                                                    <?php echo esc_html($company->post_title); ?>
                                                </a>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <a href="<?php echo esc_url(get_permalink($company)); ?>" class="text-[#5A5A5A] hover:text-[#1A1A1A]">
                                                    →
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                    else:
                        ?>
                        <div class="bg-white rounded-lg border border-[#D6D1C6] p-6 text-center text-[#5A5A5A]">
                            <p>Ingen nye deltakere å vise.</p>
                        </div>
                        <?php
                    endif;
                    ?>
                </div>

                <!-- Nyeste verktøy -->
                <div>
                    <h3 class="text-xl font-bold text-[#1A1A1A] mb-6 flex items-center gap-2">
                        <span class="text-2xl">🔧</span>
                        Nyeste verktøy
                    </h3>
                    <?php
                    // Get tools, excluding drafts and posts with "test" in title
                    $tools = get_posts(array(
                        'post_type' => 'verktoy',
                        'posts_per_page' => 10, // Get more to filter
                        'post_status' => 'publish',
                        'orderby' => 'date',
                        'order' => 'DESC',
                    ));

                    // Filter out tools with "test" in title
                    $filtered_tools = array_filter($tools, function($tool) {
                        return stripos($tool->post_title, 'test') === false;
                    });

                    // Take only first 5
                    $filtered_tools = array_slice($filtered_tools, 0, 5);

                    if (!empty($filtered_tools)):
                        ?>
                        <div class="bg-white rounded-lg border border-[#D6D1C6] overflow-hidden">
                            <table class="w-full">
                                <tbody>
                                    <?php
                                    $tool_count = count($filtered_tools);
                                    $i = 0;
                                    foreach ($filtered_tools as $tool):
                                        $i++;
                                    ?>
                                        <tr class="<?php echo $i < $tool_count ? 'border-b border-[#D6D1C6]' : ''; ?>">
                                            <td class="px-4 py-3">
                                                <a href="<?php echo esc_url(get_permalink($tool)); ?>" class="text-[#1A1A1A] font-medium hover:underline">
                                                    <?php echo esc_html($tool->post_title); ?>
                                                </a>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <a href="<?php echo esc_url(get_permalink($tool)); ?>" class="text-[#5A5A5A] hover:text-[#1A1A1A]">
                                                    →
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                    else:
                        ?>
                        <div class="bg-white rounded-lg border border-[#D6D1C6] p-6 text-center text-[#5A5A5A]">
                            <p>Ingen verktøy å vise.</p>
                        </div>
                        <?php
                    endif;
                    ?>
                </div>

            </div>
        </div>
    </section>

    <!-- LinkedIn Placeholder Section -->
    <section class="py-20 border-t border-[#D6D1C6]">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="max-w-2xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-[#1A1A1A] mb-6">Følg oss på LinkedIn</h2>

                <div class="bg-white rounded-lg border border-[#D6D1C6] p-12">
                    <div class="text-6xl mb-4">
                        <svg class="w-16 h-16 mx-auto text-[#0077B5]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                        </svg>
                    </div>
                    <p class="text-[#5A5A5A] text-lg mb-6">LinkedIn-feed kommer snart</p>
                    <a href="https://www.linkedin.com/company/bim-verdi/" target="_blank" rel="noopener noreferrer" class="inline-block px-6 py-3 bg-[#0077B5] text-white rounded font-semibold hover:bg-[#005885] transition-colors">
                        Følg BIM Verdi på LinkedIn
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="py-20 border-t border-[#D6D1C6]">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="bg-[#1A1A1A] rounded-2xl py-16 px-8 text-center">
                <h2 class="text-4xl font-bold mb-4 text-white">Klar til å bli medlem?</h2>
                <p class="text-lg text-gray-300 mb-8 max-w-2xl mx-auto">
                    Bli del av et fagnettverk som jobber for bedre og mer effektiv digitalisering av byggenæringen. Få tilgang til temagrupper, arrangementer, verktøyoversikt og et aktivt nettverk av fagfolk.
                </p>
                <a href="<?php echo esc_url(home_url('/bli-medlem/')); ?>" class="inline-block px-8 py-3 bg-[#FF8B5E] text-white rounded font-semibold hover:bg-[#e67a4d] transition-colors">
                    Bli med i nettverket
                </a>
            </div>
        </div>
    </section>

</main>

<?php get_footer();
