<?php
/**
 * Front Page Template
 *
 * Homepage for BIM Verdi - Marketplace-inspired redesign
 * Clean white/gray directory layout inspired by mcpmarket.com
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Pre-fetch counts for stats
$total_companies = wp_count_posts('foretak')->publish;
$total_tools = wp_count_posts('verktoy')->publish;
$total_events = wp_count_posts('arrangement')->publish;
$total_sources = wp_count_posts('kunnskapskilde')->publish;
$total_articles = wp_count_posts('artikkel')->publish;

// Temagruppe color mapping
$tg_colors = array(
    'SirkBIM'        => 'var(--color-tg-orange)',
    'ByggesaksBIM'   => 'var(--color-tg-blue)',
    'ProsjektBIM'    => 'var(--color-tg-green)',
    'EiendomsBIM'    => 'var(--color-tg-purple)',
    'MiljøBIM'       => 'var(--color-tg-teal)',
    'BIMtech'        => 'var(--color-tg-amber)',
);

// Avatar colors cycle
$avatar_colors = array(
    'var(--color-tg-orange)',
    'var(--color-tg-blue)',
    'var(--color-tg-green)',
    'var(--color-tg-purple)',
    'var(--color-tg-teal)',
    'var(--color-tg-amber)',
);

// Kildetype badge config
$kildetype_config = array(
    'standard'        => array('label' => 'Standard',   'color' => 'var(--color-tg-blue)'),
    'veiledning'      => array('label' => 'Veiledning', 'color' => 'var(--color-tg-green)'),
    'forskrift'       => array('label' => 'Forskrift',  'color' => 'var(--color-tg-amber)'),
    'forskrift_norsk' => array('label' => 'Forskrift',  'color' => 'var(--color-tg-amber)'),
    'forordning_eu'   => array('label' => 'EU',         'color' => 'var(--color-tg-purple)'),
    'nettressurs'     => array('label' => 'Ressurs',    'color' => 'var(--color-tg-teal)'),
    'haandbok'        => array('label' => 'Håndbok',    'color' => 'var(--color-primary)'),
);

// Hero slides: title + description pairs
$hero_slides = array(
    array(
        'title' => 'Utforsk verktøykatalogen',
        'desc'  => 'Finn programvare, standarder og digitale tjenester som brukes i norsk byggenæring.',
    ),
    array(
        'title' => 'Les kunnskapskilder',
        'desc'  => 'Standarder, veiledere og forskrifter samlet på ett sted — alltid oppdatert.',
    ),
    array(
        'title' => 'Se våre deltakere',
        'desc'  => 'Over ' . $total_companies . ' foretak fra hele byggenæringen deler erfaringer og verktøy.',
    ),
    array(
        'title' => 'Finn arrangement',
        'desc'  => 'Workshops, webinarer og nettverksmøter for BIM-miljøet i Norge.',
    ),
);

// CPT quick links for hero
$cpt_links = array(
    array('label' => 'Verktøy',          'url' => '/verktoy/',          'count' => $total_tools),
    array('label' => 'Kunnskapskilder',   'url' => '/kunnskapskilder/',  'count' => $total_sources),
    array('label' => 'Deltakere',         'url' => '/deltakere/',        'count' => $total_companies),
    array('label' => 'Arrangement',       'url' => '/arrangement/',      'count' => $total_events),
    array('label' => 'Artikler',          'url' => '/artikler/',         'count' => $total_articles),
);

// "Time ago" for stats badge (Norwegian)
$latest_post = get_posts(array('post_type' => array('verktoy', 'foretak', 'kunnskapskilde', 'artikkel'), 'posts_per_page' => 1, 'post_status' => 'publish'));
if (!empty($latest_post)) {
    $diff = current_time('timestamp') - get_the_time('U', $latest_post[0]);
    $days = (int) floor($diff / DAY_IN_SECONDS);
    if ($days < 1) {
        $updated_ago = 'i dag';
    } elseif ($days === 1) {
        $updated_ago = '1 dag siden';
    } else {
        $updated_ago = $days . ' dager siden';
    }
} else {
    $updated_ago = 'nylig';
}
?>

<!-- =============================================
     HERO SECTION
     ============================================= -->
<section class="pt-16 pb-12 bg-white">
    <div class="max-w-6xl mx-auto px-4 md:px-8">

        <!-- Stats Badge -->
        <div class="mb-8">
            <span class="bv-stats-badge">
                <span class="dot"></span>
                <?php echo esc_html($total_companies); ?> Deltakere &middot;
                <?php echo esc_html($total_tools); ?> Verktøy &middot;
                <?php echo esc_html($total_sources); ?> Kunnskapskilder &middot;
                Oppdatert: <?php echo esc_html($updated_ago); ?>
            </span>
        </div>

        <!-- Main Headline -->
        <h1 class="text-4xl md:text-5xl lg:text-[3.5rem] font-bold text-[#111827] leading-tight mb-2">
            BIM Verdi
        </h1>

        <!-- Rotating content with vertical dots -->
        <div class="flex gap-5 mb-8">
            <!-- Vertical pagination dots -->
            <div class="flex flex-col items-center gap-2 pt-2" id="hero-dots">
                <?php foreach ($hero_slides as $i => $slide): ?>
                <button class="bv-hero-dot<?php echo $i === 0 ? ' active' : ''; ?>" data-index="<?php echo $i; ?>" aria-label="Slide <?php echo $i + 1; ?>"></button>
                <?php endforeach; ?>
            </div>

            <!-- Rotating title + description -->
            <div>
                <div class="mb-3">
                    <span class="bv-hero-rotating text-2xl md:text-3xl lg:text-[2.25rem] font-medium text-[#57534E]" id="hero-rotating">
                        <?php foreach ($hero_slides as $i => $slide): ?>
                        <span<?php echo $i === 0 ? ' class="active"' : ''; ?>><?php echo esc_html($slide['title']); ?></span>
                        <?php endforeach; ?>
                    </span>
                </div>
                <div class="max-w-xl">
                    <span class="bv-hero-rotating text-base text-[#78716C] leading-relaxed" id="hero-desc">
                        <?php foreach ($hero_slides as $i => $slide): ?>
                        <span<?php echo $i === 0 ? ' class="active"' : ''; ?>><?php echo esc_html($slide['desc']); ?></span>
                        <?php endforeach; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- CPT Quick Links -->
        <div class="flex flex-wrap gap-2">
            <?php foreach ($cpt_links as $link): ?>
            <a href="<?php echo esc_url(home_url($link['url'])); ?>"
               class="bv-chip">
                <?php echo esc_html($link['label']); ?>
                <span class="text-[#A8A29E] ml-1"><?php echo esc_html($link['count']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<main>

    <!-- =============================================
         SISTE VERKTØY
         ============================================= -->
    <?php
    $tools = get_posts(array(
        'post_type'      => 'verktoy',
        'posts_per_page' => 6,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    ?>
    <section class="py-14 bg-[#FAFAF9]">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="bv-section-header">
                <h2>Siste Verktøy</h2>
                <a href="<?php echo esc_url(home_url('/verktoy/')); ?>">Se alle (<?php echo esc_html($total_tools); ?>) &rarr;</a>
            </div>

            <?php if (!empty($tools)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($tools as $tool):
                    $tool_cats = wp_get_post_terms($tool->ID, 'verktoykategori', array('fields' => 'names'));
                    $tool_cat = !empty($tool_cats) && !is_wp_error($tool_cats) ? $tool_cats[0] : '';
                    $tool_owner_id = get_field('eier_leverandor', $tool->ID);
                    $tool_owner_name = $tool_owner_id ? get_the_title($tool_owner_id) : '';
                    $tool_desc = wp_trim_words(get_the_excerpt($tool) ?: strip_tags($tool->post_content), 18);
                ?>
                <a href="<?php echo esc_url(get_permalink($tool)); ?>" class="block group">
                    <div class="bv-card">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-lg bg-[#F5F5F4] flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-[#57534E]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-semibold text-[#111827] truncate text-sm"><?php echo esc_html($tool->post_title); ?></h3>
                                <?php if ($tool_owner_name): ?>
                                <p class="text-xs text-[#A8A29E] truncate"><?php echo esc_html($tool_owner_name); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($tool_desc): ?>
                        <p class="text-sm text-[#57534E] line-clamp-2 mb-4"><?php echo esc_html($tool_desc); ?></p>
                        <?php endif; ?>
                        <?php if ($tool_cat): ?>
                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-[#F5F5F4] text-[#57534E] border border-[#E7E5E4]">
                            <?php echo esc_html($tool_cat); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-[#57534E] text-center py-8">Ingen verktøy registrert ennå.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- =============================================
         NYE DELTAKERE
         ============================================= -->
    <?php
    $companies = get_posts(array(
        'post_type'      => 'foretak',
        'posts_per_page' => 6,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    ?>
    <section class="py-14 bg-white">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="bv-section-header">
                <h2>Nye Deltakere</h2>
                <a href="<?php echo esc_url(home_url('/deltakere/')); ?>">Se alle (<?php echo esc_html($total_companies); ?>) &rarr;</a>
            </div>

            <?php if (!empty($companies)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($companies as $index => $company):
                    $color = $avatar_colors[$index % count($avatar_colors)];
                    $initial = mb_strtoupper(mb_substr($company->post_title, 0, 1));
                    $bransje = bimverdi_get_first_term_name($company->ID, 'bransjekategori');
                    $company_desc = wp_trim_words(get_the_excerpt($company) ?: strip_tags($company->post_content), 18);
                ?>
                <a href="<?php echo esc_url(get_permalink($company)); ?>" class="block group">
                    <div class="bv-card">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0" style="background-color: <?php echo esc_attr($color); ?>">
                                <?php echo esc_html($initial); ?>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-semibold text-[#111827] truncate text-sm"><?php echo esc_html($company->post_title); ?></h3>
                                <?php if ($bransje): ?>
                                <p class="text-xs text-[#A8A29E] truncate"><?php echo esc_html($bransje); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($company_desc): ?>
                        <p class="text-sm text-[#57534E] line-clamp-2"><?php echo esc_html($company_desc); ?></p>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-[#57534E] text-center py-8">Ingen deltakere registrert ennå.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- =============================================
         KUNNSKAPSKILDER
         ============================================= -->
    <?php
    $sources = get_posts(array(
        'post_type'      => 'kunnskapskilde',
        'posts_per_page' => 6,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    ?>
    <section class="py-14 bg-[#FAFAF9]">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="bv-section-header">
                <h2>Kunnskapskilder</h2>
                <a href="<?php echo esc_url(home_url('/kunnskapskilder/')); ?>">Se alle (<?php echo esc_html($total_sources); ?>) &rarr;</a>
            </div>

            <?php if (!empty($sources)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($sources as $source):
                    $kildetype = get_field('kildetype', $source->ID) ?: '';
                    $type_cfg = isset($kildetype_config[$kildetype]) ? $kildetype_config[$kildetype] : null;
                    $source_utgiver = get_field('utgiver', $source->ID) ?: '';
                    if (!$source_utgiver) {
                        $source_bedrift_id = get_field('tilknyttet_bedrift', $source->ID);
                        $source_utgiver = $source_bedrift_id ? get_the_title($source_bedrift_id) : '';
                    }
                    $source_desc = wp_trim_words(get_the_excerpt($source) ?: strip_tags($source->post_content), 18);
                ?>
                <a href="<?php echo esc_url(get_permalink($source)); ?>" class="block group">
                    <div class="bv-card">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background-color: <?php echo $type_cfg ? 'color-mix(in srgb, ' . esc_attr($type_cfg['color']) . ' 12%, transparent)' : '#F5F5F4'; ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?php echo $type_cfg ? esc_attr($type_cfg['color']) : '#57534E'; ?>">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-semibold text-[#111827] truncate text-sm"><?php echo esc_html($source->post_title); ?></h3>
                                <?php if ($source_utgiver): ?>
                                <p class="text-xs text-[#A8A29E] truncate"><?php echo esc_html($source_utgiver); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($source_desc): ?>
                        <p class="text-sm text-[#57534E] line-clamp-2 mb-4"><?php echo esc_html($source_desc); ?></p>
                        <?php endif; ?>
                        <?php if ($type_cfg): ?>
                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium border border-[#E7E5E4]" style="background-color: color-mix(in srgb, <?php echo esc_attr($type_cfg['color']); ?> 8%, transparent); color: <?php echo esc_attr($type_cfg['color']); ?>">
                            <?php echo esc_html($type_cfg['label']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-[#57534E] text-center py-8">Ingen kunnskapskilder registrert ennå.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- =============================================
         KOMMENDE ARRANGEMENTER (vertical list)
         ============================================= -->
    <?php
    $events = get_posts(array(
        'post_type'      => 'arrangement',
        'posts_per_page' => 4,
        'meta_query'     => array(
            array(
                'key'     => 'arrangement_status_toggle',
                'value'   => 'kommende',
                'compare' => '=',
            ),
        ),
        'meta_key' => 'arrangement_dato',
        'orderby'  => 'meta_value',
        'order'    => 'ASC',
    ));

    if (empty($events)) {
        $events = get_posts(array(
            'post_type'      => 'arrangement',
            'posts_per_page' => 4,
            'post_status'    => 'publish',
            'meta_key'       => 'arrangement_dato',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
        ));
    }
    ?>
    <section class="py-14 bg-white">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="bv-section-header">
                <h2>Kommende Arrangementer</h2>
                <a href="<?php echo esc_url(home_url('/arrangementer/')); ?>">Se alle (<?php echo esc_html($total_events); ?>) &rarr;</a>
            </div>

            <?php if (!empty($events)): ?>
            <div class="space-y-3">
                <?php
                foreach ($events as $event):
                    $event_date = get_field('arrangement_dato', $event->ID) ?: get_field('dato', $event->ID) ?: date('Y-m-d', strtotime($event->post_date));
                    $event_time_start = get_field('arrangement_tid_start', $event->ID) ?: get_field('tid_start', $event->ID) ?: '';
                    $event_time_end = get_field('arrangement_tid_slutt', $event->ID) ?: get_field('tid_slutt', $event->ID) ?: '';
                    $event_format = get_field('arrangement_format', $event->ID) ?: get_field('format', $event->ID) ?: '';
                    $event_tg_terms = get_the_terms($event->ID, 'temagruppe');
                    $event_tg_name = ($event_tg_terms && !is_wp_error($event_tg_terms)) ? $event_tg_terms[0]->name : '';
                    $event_tg_color = isset($tg_colors[$event_tg_name]) ? $tg_colors[$event_tg_name] : '';

                    $date_obj = DateTime::createFromFormat('Y-m-d', $event_date) ?: DateTime::createFromFormat('Ymd', $event_date);
                    $ts = $date_obj ? $date_obj->getTimestamp() : 0;
                    $day = $ts ? wp_date('d', $ts) : '';
                    $month = $ts ? strtoupper(wp_date('M', $ts)) : '';
                ?>
                <a href="<?php echo esc_url(get_permalink($event)); ?>"
                   class="flex items-center gap-5 rounded-xl p-4 border border-[#E7E5E4] bg-white hover:shadow-md hover:border-[#D6D3D1] transition-all group">

                    <!-- Date badge -->
                    <div class="flex-shrink-0 w-14 h-14 rounded-lg flex flex-col items-center justify-center text-white bg-[#111827]">
                        <span class="text-lg font-bold leading-none"><?php echo esc_html($day); ?></span>
                        <span class="text-[10px] font-semibold tracking-wider leading-none mt-0.5"><?php echo esc_html($month); ?></span>
                    </div>

                    <!-- Event details -->
                    <div class="flex-grow min-w-0">
                        <h3 class="font-semibold text-sm text-[#111827] mb-1 line-clamp-1 group-hover:text-[#57534E] transition-colors"><?php echo esc_html($event->post_title); ?></h3>
                        <div class="flex flex-wrap items-center gap-2 text-xs text-[#A8A29E]">
                            <?php if ($event_tg_name && $event_tg_color): ?>
                            <span class="font-medium px-2 py-0.5 rounded-full" style="background-color: color-mix(in srgb, <?php echo esc_attr($event_tg_color); ?> 12%, transparent); color: <?php echo esc_attr($event_tg_color); ?>"><?php echo esc_html($event_tg_name); ?></span>
                            <?php endif; ?>
                            <?php if ($event_format): ?>
                            <span><?php echo esc_html($event_format); ?></span>
                            <?php endif; ?>
                            <?php if ($event_time_start): ?>
                            <span>&middot; <?php echo esc_html($event_time_start); ?><?php echo $event_time_end ? '–' . esc_html($event_time_end) : ''; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <span class="flex-shrink-0 text-[#A8A29E] group-hover:text-[#111827] transition-colors">&rarr;</span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="rounded-xl border border-[#E7E5E4] p-8 text-center">
                <p class="text-[#57534E]">Ingen arrangementer planlagt akkurat nå.</p>
                <p class="text-sm text-[#A8A29E] mt-1">Sjekk tilbake snart for kommende arrangementer.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- =============================================
         TEMAGRUPPER
         ============================================= -->
    <section class="py-14 bg-[#FAFAF9]">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="bv-section-header">
                <h2>Temagrupper</h2>
                <a href="<?php echo esc_url(home_url('/temagrupper/')); ?>">Se alle &rarr;</a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $theme_groups = array(
                    array('title' => 'SirkBIM', 'desc' => 'Sirkulær økonomi, materialgjenbruk og ombruk i byggenæringen med digitale verktøy.', 'slug' => 'sirkbim', 'color' => 'var(--color-tg-orange)', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>'),
                    array('title' => 'ByggesaksBIM', 'desc' => 'Digitalisering av byggesaksprosessen og bruk av BIM mot offentlige myndigheter.', 'slug' => 'byggesaksbim', 'color' => 'var(--color-tg-blue)', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>'),
                    array('title' => 'ProsjektBIM', 'desc' => 'Beste praksis for BIM-koordinering og ledelse i store byggeprosjekter.', 'slug' => 'prosjektbim', 'color' => 'var(--color-tg-green)', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>'),
                    array('title' => 'EiendomsBIM', 'desc' => 'FDV-dokumentasjon og bruk av BIM i driftsfasen for eiendomsforvaltere.', 'slug' => 'eiendomsbim', 'color' => 'var(--color-tg-purple)', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'),
                    array('title' => 'MiljøBIM', 'desc' => 'Bruk av BIM for klimagassregnskap, ombruk og bærekraftige materialvalg.', 'slug' => 'miljobim', 'color' => 'var(--color-tg-teal)', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>'),
                    array('title' => 'BIMtech', 'desc' => 'Utforsking av ny teknologi, API-er, skripting og innovasjon i bransjen.', 'slug' => 'bimtech', 'color' => 'var(--color-tg-amber)', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>'),
                );

                foreach ($theme_groups as $group):
                ?>
                <a href="<?php echo esc_url(home_url('/temagrupper/' . $group['slug'] . '/')); ?>" class="block group">
                    <div class="bv-card" style="border-left: 3px solid <?php echo esc_attr($group['color']); ?>">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: color-mix(in srgb, <?php echo esc_attr($group['color']); ?> 12%, transparent)">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?php echo esc_attr($group['color']); ?>"><?php echo $group['icon']; ?></svg>
                            </div>
                            <span class="text-[#A8A29E] group-hover:text-[#111827] transition-colors">&rarr;</span>
                        </div>
                        <h3 class="font-semibold text-[#111827] text-base mb-1"><?php echo esc_html($group['title']); ?></h3>
                        <p class="text-sm text-[#57534E] leading-relaxed"><?php echo esc_html($group['desc']); ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- =============================================
         SENESTE ARTIKLER
         ============================================= -->
    <?php
    $articles = get_posts(array(
        'post_type'      => 'artikkel',
        'posts_per_page' => 4,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));

    if (empty($articles)) {
        $articles = get_posts(array(
            'post_type'      => 'post',
            'posts_per_page' => 4,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));
    }

    $art_cat_colors = array(
        'var(--color-tg-blue)', 'var(--color-tg-green)', 'var(--color-tg-orange)',
        'var(--color-tg-purple)', 'var(--color-tg-teal)', 'var(--color-tg-amber)',
    );
    ?>
    <section class="py-14 bg-white">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="bv-section-header">
                <h2>Seneste Artikler</h2>
                <a href="<?php echo esc_url(home_url('/artikler/')); ?>">Se alle &rarr;</a>
            </div>

            <?php if (!empty($articles)):
                $featured = $articles[0];
                $rest = array_slice($articles, 1);
                $featured_thumb = get_the_post_thumbnail_url($featured->ID, 'large');
                $featured_date = get_the_date('d. M Y', $featured->ID);
                $featured_cats = wp_get_post_terms($featured->ID, 'artikkelkategori', array('fields' => 'names'));
                $featured_cat = (!empty($featured_cats) && !is_wp_error($featured_cats)) ? $featured_cats[0] : '';
            ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Featured article -->
                <a href="<?php echo esc_url(get_permalink($featured)); ?>" class="group block">
                    <div class="relative aspect-[4/3] rounded-xl overflow-hidden mb-4">
                        <?php if ($featured_thumb): ?>
                        <img src="<?php echo esc_url($featured_thumb); ?>" alt="<?php echo esc_attr($featured->post_title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <?php else: ?>
                        <div class="w-full h-full bg-[#F5F5F4] flex items-center justify-center">
                            <svg class="w-16 h-16 text-[#A8A29E]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                            </svg>
                        </div>
                        <?php endif; ?>
                        <?php if ($featured_cat): ?>
                        <span class="absolute top-3 left-3 text-xs font-semibold px-2.5 py-1 rounded-full text-white" style="background-color: <?php echo esc_attr($art_cat_colors[0]); ?>">
                            <?php echo esc_html($featured_cat); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-sm text-[#A8A29E] mb-1.5"><?php echo esc_html($featured_date); ?></div>
                    <h3 class="text-xl font-bold text-[#111827] mb-2 group-hover:text-[#57534E] transition-colors"><?php echo esc_html($featured->post_title); ?></h3>
                    <p class="text-sm text-[#57534E] line-clamp-2"><?php echo esc_html(wp_trim_words($featured->post_excerpt ?: strip_tags($featured->post_content), 25)); ?></p>
                </a>

                <!-- Stacked articles -->
                <div class="flex flex-col gap-0">
                    <?php foreach ($rest as $r_index => $article):
                        $thumb = get_the_post_thumbnail_url($article->ID, 'medium');
                        $date = get_the_date('d. M Y', $article->ID);
                        $art_cats = wp_get_post_terms($article->ID, 'artikkelkategori', array('fields' => 'names'));
                        $art_cat = (!empty($art_cats) && !is_wp_error($art_cats)) ? $art_cats[0] : '';
                        $art_color = $art_cat_colors[($r_index + 1) % count($art_cat_colors)];
                    ?>
                    <a href="<?php echo esc_url(get_permalink($article)); ?>"
                       class="group flex gap-4 py-4 <?php echo $r_index < count($rest) - 1 ? 'border-b border-[#E7E5E4]' : ''; ?>">
                        <div class="relative flex-shrink-0 w-28 h-20 rounded-lg overflow-hidden">
                            <?php if ($thumb): ?>
                            <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($article->post_title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <?php else: ?>
                            <div class="w-full h-full bg-[#F5F5F4] flex items-center justify-center">
                                <svg class="w-8 h-8 text-[#A8A29E]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                </svg>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs text-[#A8A29E]"><?php echo esc_html($date); ?></span>
                                <?php if ($art_cat): ?>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full" style="background-color: color-mix(in srgb, <?php echo esc_attr($art_color); ?> 12%, transparent); color: <?php echo esc_attr($art_color); ?>">
                                    <?php echo esc_html($art_cat); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <h4 class="text-sm font-semibold text-[#111827] group-hover:text-[#57534E] transition-colors line-clamp-2"><?php echo esc_html($article->post_title); ?></h4>
                        </div>
                        <span class="text-[#A8A29E] flex-shrink-0 self-center group-hover:text-[#111827] transition-colors">&rarr;</span>
                    </a>
                    <?php endforeach; ?>
                </div>

            </div>
            <?php else: ?>
            <div class="text-center text-[#57534E] py-8">
                <p>Ingen artikler publisert ennå.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- =============================================
         NETTVERKET I TALL
         ============================================= -->
    <section class="py-16 bg-[#FAFAF9]">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <h2 class="text-2xl font-bold text-[#111827] text-center mb-10">Nettverket i tall</h2>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-[#111827]"><?php echo esc_html($total_companies); ?></div>
                    <div class="text-sm text-[#57534E] mt-1">Deltakere</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-[#111827]"><?php echo esc_html($total_tools); ?></div>
                    <div class="text-sm text-[#57534E] mt-1">Verktøy</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-[#111827]"><?php echo esc_html($total_events); ?></div>
                    <div class="text-sm text-[#57534E] mt-1">Arrangementer</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-[#111827]"><?php echo esc_html($total_sources); ?></div>
                    <div class="text-sm text-[#57534E] mt-1">Kunnskapskilder</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-[#111827]">6</div>
                    <div class="text-sm text-[#57534E] mt-1">Temagrupper</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-[#111827]">2012</div>
                    <div class="text-sm text-[#57534E] mt-1">Etablert</div>
                </div>
            </div>
        </div>
    </section>

    <!-- =============================================
         FAQ
         ============================================= -->
    <section class="py-14 bg-white">
        <div class="max-w-3xl mx-auto px-4 md:px-8">
            <h2 class="text-2xl font-bold text-[#111827] text-center mb-10">Ofte stilte spørsmål</h2>

            <div class="bv-faq">
                <details>
                    <summary>Hva er BIM Verdi?</summary>
                    <p>BIM Verdi er et bransjenettverk for aktører i byggenæringen som ønsker å dele kunnskap, erfaringer og verktøy knyttet til BIM (Building Information Modelling) og digitalisering. Nettverket ble etablert i 2012 og samler både offentlige og private aktører.</p>
                </details>

                <details>
                    <summary>Hvem kan bli deltaker?</summary>
                    <p>Alle foretak og organisasjoner som jobber med BIM og digitalisering i byggenæringen kan bli deltaker. Dette inkluderer arkitekter, rådgivende ingeniører, entreprenører, eiendomsforvaltere, programvareleverandører og offentlige myndigheter.</p>
                </details>

                <details>
                    <summary>Hva er en temagruppe?</summary>
                    <p>Temagrupper er faglige arbeidsgrupper som fokuserer på spesifikke tema innen BIM. Eksempler er SirkBIM (sirkulær økonomi), ByggesaksBIM (digitale byggesaker), ProsjektBIM (prosjektledelse), EiendomsBIM (drift og forvaltning), MiljøBIM (bærekraft) og BIMtech (teknologi og innovasjon).</p>
                </details>

                <details>
                    <summary>Hva koster det å delta?</summary>
                    <p>BIM Verdi har ulike deltakernivåer tilpasset ulike behov og organisasjonsstørrelser. Kontakt oss for mer informasjon om priser og vilkår for ditt foretak.</p>
                </details>

                <details>
                    <summary>Hvordan registrerer jeg verktøy?</summary>
                    <p>Etter at du har logget inn på Min Side, kan du registrere verktøy som foretaket ditt bruker eller leverer. Verktøyene blir synlige i verktøykatalogen og hjelper andre deltakere med å finne riktige løsninger.</p>
                </details>

                <details>
                    <summary>Hva er kunnskapskilder?</summary>
                    <p>Kunnskapskilder er en samling av standarder, veiledninger, forskrifter, håndbøker og nettressurser som er relevante for BIM og digitalisering i byggenæringen. Disse er kuratert og kategorisert for å gjøre det enkelt å finne relevant informasjon.</p>
                </details>
            </div>
        </div>
    </section>

</main>

<?php get_footer();
