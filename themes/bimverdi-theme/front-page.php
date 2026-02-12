<?php
/**
 * Front Page Template
 *
 * Homepage for BIM Verdi - v2 redesign
 * Uses Oatmeal design system tokens for consistent theming
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

// Temagruppe color mapping (references CSS custom properties)
$tg_colors = array(
    'SirkBIM'        => 'var(--color-tg-orange)',
    'ByggesaksBIM'   => 'var(--color-tg-blue)',
    'ProsjektBIM'    => 'var(--color-tg-green)',
    'EiendomsBIM'    => 'var(--color-tg-purple)',
    'MiljøBIM'       => 'var(--color-tg-teal)',
    'BIMtech'        => 'var(--color-tg-amber)',
);

// Avatar colors cycle (same palette as temagrupper)
$avatar_colors = array(
    'var(--color-tg-orange)',
    'var(--color-tg-blue)',
    'var(--color-tg-green)',
    'var(--color-tg-purple)',
    'var(--color-tg-teal)',
    'var(--color-tg-amber)',
);

// Kildetype badge config (color + label)
$kildetype_config = array(
    'standard'       => array('label' => 'Standard',   'color' => 'var(--color-tg-blue)'),
    'veiledning'     => array('label' => 'Veiledning',  'color' => 'var(--color-tg-green)'),
    'forskrift'      => array('label' => 'Forskrift',   'color' => 'var(--color-tg-amber)'),
    'forskrift_norsk' => array('label' => 'Forskrift',  'color' => 'var(--color-tg-amber)'),
    'forordning_eu'  => array('label' => 'EU',          'color' => 'var(--color-tg-purple)'),
    'nettressurs'    => array('label' => 'Ressurs',     'color' => 'var(--color-tg-teal)'),
    'haandbok'       => array('label' => 'Håndbok',     'color' => 'var(--color-primary)'),
);
?>

<!-- Hero Section -->
<section class="oat-bg oat-text pt-14 pb-12">
    <div class="max-w-6xl mx-auto px-4 md:px-8">
        <div class="max-w-3xl">

            <!-- Badge -->
            <div class="mb-5">
                <span class="inline-flex items-center gap-2 oat-bg-surface pl-4 pr-3 py-2 rounded-full text-base oat-text-secondary">
                    Norges ledende nettverk for praktisk bruk av BIM og AI
                    <a href="<?php echo esc_url(home_url('/om-oss/')); ?>" class="font-semibold oat-text hover:underline flex items-center gap-1">Les mer <span>&rsaquo;</span></a>
                </span>
            </div>

            <!-- Main Headline -->
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-5 leading-tight oat-text">
                Vi digitaliserer sammen i praktiske prosjekter basert på dagens kunnskap, krav, forskrifter, standarder, veiledninger og verktøy.
            </h1>

            <!-- Subtitle -->
            <p class="text-lg md:text-xl oat-text-secondary mb-8 max-w-2xl leading-relaxed">
                BIM Verdi er et bransjenettverk som kobler sammen aktører i byggenæringen for å dele kunnskap, erfaringer og verktøy.
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-wrap items-center gap-6 mb-10">
                <a href="<?php echo esc_url(home_url('/logg-inn/')); ?>" class="bv-btn bv-btn--primary bv-btn--large" style="border-radius: var(--radius-pill)">
                    Bli med i nettverket
                </a>
                <a href="<?php echo esc_url(home_url('/om-oss/')); ?>" class="oat-text font-medium hover:underline flex items-center gap-1">
                    Se hvordan det fungerer <span>&rarr;</span>
                </a>
            </div>

        </div>

        <!-- Stats Bar -->
        <div class="pt-6">
            <div class="flex flex-wrap items-stretch justify-between gap-y-3">
                <?php
                $stats = array(
                    array('num' => $total_companies, 'label' => 'Deltakere', 'url' => '/deltakere/', 'color' => 'var(--color-tg-blue)', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>'),
                    array('num' => $total_tools, 'label' => 'Verktøy', 'url' => '/verktoy/', 'color' => 'var(--color-tg-orange)', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>'),
                    array('num' => '6', 'label' => 'Temagrupper', 'url' => '#temagrupper', 'color' => 'var(--color-tg-green)', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>'),
                    array('num' => $total_sources, 'label' => 'Kunnskapskilder', 'url' => '/kunnskapskilder/', 'color' => 'var(--color-tg-purple)', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>'),
                    array('num' => $total_events, 'label' => 'Arrangementer', 'url' => '/arrangementer/', 'color' => 'var(--color-tg-teal)', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>'),
                );
                $total = count($stats);
                foreach ($stats as $i => $stat):
                    $href = ($stat['url'][0] === '#') ? $stat['url'] : esc_url(home_url($stat['url']));
                ?>
                <a href="<?php echo $href; ?>" class="group flex items-center gap-3 py-3 px-1">
                    <span class="w-8 h-8 rounded-md flex items-center justify-center flex-shrink-0" style="background-color: color-mix(in srgb, <?php echo esc_attr($stat['color']); ?> 12%, transparent)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color: <?php echo esc_attr($stat['color']); ?>"><?php echo $stat['icon']; ?></svg>
                    </span>
                    <div class="leading-tight">
                        <div class="text-xl font-bold oat-text"><?php echo esc_html($stat['num']); ?></div>
                        <div class="text-xs oat-text-secondary group-hover:oat-text transition-colors"><?php echo esc_html($stat['label']); ?></div>
                    </div>
                </a><?php if ($i < $total - 1): ?><span class="hidden md:block w-px h-8 self-center" style="background-color: var(--color-border-light, #D6D1C6)"></span><?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<main class="oat-bg">

    <!-- Nytt i Nettverket -->
    <section class="py-14 border-t oat-border">
        <div class="max-w-6xl mx-auto px-4 md:px-8">

            <div class="mb-8">
                <h2 class="text-2xl font-bold oat-text mb-1">Nytt i nettverket</h2>
                <p class="text-base oat-text-secondary">Siste tilskudd til kunnskapsbasen og verktøykatalogen.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-16">

                <!-- Kunnskapskilder -->
                <?php
                $sources = get_posts(array(
                    'post_type' => 'kunnskapskilde',
                    'posts_per_page' => 3,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC',
                ));
                ?>
                <div>
                    <div class="flex justify-between items-baseline mb-3">
                        <h3 class="text-xl font-bold oat-text">Kunnskapskilder</h3>
                        <a href="<?php echo esc_url(home_url('/kunnskapskilder/')); ?>" class="text-base oat-text-secondary hover:oat-text flex items-center gap-1">Se alle (<?php echo esc_html($total_sources); ?>) <span>&rarr;</span></a>
                    </div>
                    <?php if (!empty($sources)): ?>
                    <div>
                        <?php
                        foreach ($sources as $index => $source):
                            $kildetype = get_field('kildetype', $source->ID) ?: '';
                            $type_cfg = isset($kildetype_config[$kildetype]) ? $kildetype_config[$kildetype] : null;
                            $source_utgiver = get_field('utgiver', $source->ID) ?: '';
                            if (!$source_utgiver) {
                                $source_bedrift_id = get_field('tilknyttet_bedrift', $source->ID);
                                $source_utgiver = $source_bedrift_id ? get_the_title($source_bedrift_id) : '';
                            }
                        ?>
                        <a href="<?php echo esc_url(get_permalink($source)); ?>"
                           class="flex items-center gap-3 py-3 oat-hover -mx-2 px-2 rounded transition-colors <?php echo $index < count($sources) - 1 ? 'border-b oat-border-light' : ''; ?>">
                            <span class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: <?php echo $type_cfg ? 'color-mix(in srgb, ' . esc_attr($type_cfg['color']) . ' 12%, transparent)' : 'var(--color-bg-surface, #F0EBE0)'; ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?php echo $type_cfg ? esc_attr($type_cfg['color']) : 'var(--color-text-secondary, #888)'; ?>">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </span>
                            <span class="flex-grow min-w-0">
                                <span class="block text-base font-medium oat-text truncate"><?php echo esc_html($source->post_title); ?></span>
                                <span class="block text-base oat-text-muted"><?php echo $source_utgiver ? esc_html($source_utgiver) : ''; ?><?php if ($source_utgiver && $type_cfg) echo ' · '; ?><?php if ($type_cfg) echo esc_html($type_cfg['label']); ?></span>
                            </span>
                            <span class="oat-text-muted flex-shrink-0">&rarr;</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="oat-text-secondary py-4">Ingen kunnskapskilder ennå.</p>
                    <?php endif; ?>
                </div>

                <!-- Verktøy -->
                <?php
                $tools = get_posts(array(
                    'post_type' => 'verktoy',
                    'posts_per_page' => 3,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC',
                ));
                ?>
                <div>
                    <div class="flex justify-between items-baseline mb-3">
                        <h3 class="text-xl font-bold oat-text">Verktøy</h3>
                        <a href="<?php echo esc_url(home_url('/verktoy/')); ?>" class="text-base oat-text-secondary hover:oat-text flex items-center gap-1">Se alle (<?php echo esc_html($total_tools); ?>) <span>&rarr;</span></a>
                    </div>
                    <?php if (!empty($tools)): ?>
                    <div>
                        <?php foreach ($tools as $index => $tool):
                            $tool_cats = wp_get_post_terms($tool->ID, 'verktoykategori', array('fields' => 'names'));
                            $tool_cat = !empty($tool_cats) && !is_wp_error($tool_cats) ? $tool_cats[0] : '';
                            $tool_owner_id = get_field('eier_leverandor', $tool->ID);
                            $tool_owner_name = $tool_owner_id ? get_the_title($tool_owner_id) : '';
                        ?>
                        <a href="<?php echo esc_url(get_permalink($tool)); ?>"
                           class="flex items-center gap-3 py-3 oat-hover -mx-2 px-2 rounded transition-colors border-b oat-border-light">
                            <span class="flex-shrink-0 w-10 h-10 rounded-lg oat-bg-surface flex items-center justify-center">
                                <svg class="w-5 h-5 oat-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>
                                </svg>
                            </span>
                            <span class="flex-grow min-w-0">
                                <span class="block text-base font-medium oat-text truncate"><?php echo esc_html($tool->post_title); ?></span>
                                <?php if ($tool_owner_name): ?>
                                <span class="block text-base oat-text-muted">av <?php echo esc_html($tool_owner_name); ?><?php echo $tool_cat ? ' · ' . esc_html($tool_cat) : ''; ?></span>
                                <?php elseif ($tool_cat): ?>
                                <span class="block text-base oat-text-muted"><?php echo esc_html($tool_cat); ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="oat-text-muted flex-shrink-0">&rarr;</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="oat-text-secondary py-4">Ingen verktøy ennå.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>

    <!-- Deltakere -->
    <section class="py-14 border-t oat-border">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12 items-start">

                <!-- Left: Ingress + CTA -->
                <div>
                    <h2 class="text-2xl font-bold oat-text mb-3">Bli en del av nettverket</h2>
                    <p class="text-base oat-text-secondary mb-5 leading-relaxed">
                        <?php echo esc_html($total_companies); ?> foretak deler verktøy, erfaringer og bransjekunnskap. Registrer ditt foretak og få tilgang til hele nettverket.
                    </p>
                    <a href="<?php echo esc_url(home_url('/registrer/')); ?>" class="bv-btn bv-btn--primary" style="border-radius: var(--radius-pill)">
                        Registrer foretak
                    </a>
                </div>

                <!-- Right: Newest companies -->
                <?php
                $companies = get_posts(array(
                    'post_type' => 'foretak',
                    'posts_per_page' => 3,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC',
                ));
                ?>
                <div>
                    <div class="flex justify-between items-baseline mb-3">
                        <h3 class="text-xl font-bold oat-text">Nyeste deltakere</h3>
                        <a href="<?php echo esc_url(home_url('/deltakere/')); ?>" class="text-base oat-text-secondary hover:oat-text flex items-center gap-1">Se alle (<?php echo esc_html($total_companies); ?>) <span>&rarr;</span></a>
                    </div>
                    <?php if (!empty($companies)): ?>
                    <div>
                        <?php
                        foreach ($companies as $index => $company):
                            $color = $avatar_colors[$index % count($avatar_colors)];
                            $initial = mb_strtoupper(mb_substr($company->post_title, 0, 1));
                            $bransje = '';
                            $terms = wp_get_post_terms($company->ID, 'bransjekategori', array('fields' => 'names'));
                            if (!empty($terms) && !is_wp_error($terms)) {
                                $bransje = $terms[0];
                            }
                            if (!$bransje) {
                                $bransje = get_field('bransje_rolle', $company->ID);
                                if (is_array($bransje)) $bransje = $bransje[0] ?? '';
                            }
                        ?>
                        <a href="<?php echo esc_url(get_permalink($company)); ?>"
                           class="flex items-center gap-3 py-3 oat-hover -mx-2 px-2 rounded transition-colors <?php echo $index < count($companies) - 1 ? 'border-b oat-border-light' : ''; ?>">
                            <span class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white text-xs font-bold" style="background-color: <?php echo esc_attr($color); ?>">
                                <?php echo esc_html($initial); ?>
                            </span>
                            <span class="flex-grow min-w-0">
                                <span class="block text-base font-medium oat-text truncate"><?php echo esc_html($company->post_title); ?></span>
                                <?php if ($bransje): ?>
                                <span class="block text-base oat-text-muted"><?php echo esc_html($bransje); ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="oat-text-muted flex-shrink-0">&rarr;</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="oat-text-secondary py-4">Ingen deltakere ennå.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>

    <!-- Seneste Nytt - Featured + compact -->
    <section class="py-14 border-t oat-border">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold oat-text">Seneste Nytt</h2>
                <a href="<?php echo esc_url(home_url('/artikler/')); ?>" class="text-base oat-text-secondary font-medium flex items-center gap-1">Se alle <span>&rarr;</span></a>
            </div>

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

            // Article category color mapping
            $art_cat_colors = array(
                'var(--color-tg-blue)', 'var(--color-tg-green)', 'var(--color-tg-orange)',
                'var(--color-tg-purple)', 'var(--color-tg-teal)', 'var(--color-tg-amber)',
            );

            if (!empty($articles)):
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
                    <div class="relative aspect-[4/3] rounded-lg overflow-hidden mb-4">
                        <?php if ($featured_thumb): ?>
                        <img src="<?php echo esc_url($featured_thumb); ?>" alt="<?php echo esc_attr($featured->post_title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <?php else: ?>
                        <div class="w-full h-full oat-bg-surface flex items-center justify-center">
                            <svg class="w-16 h-16 oat-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <div class="text-sm oat-text-muted mb-1.5"><?php echo esc_html($featured_date); ?></div>
                    <h3 class="text-xl font-bold oat-text mb-2 group-hover:oat-text-secondary transition-colors"><?php echo esc_html($featured->post_title); ?></h3>
                    <p class="text-base oat-text-secondary line-clamp-2"><?php echo esc_html(wp_trim_words($featured->post_excerpt ?: strip_tags($featured->post_content), 25)); ?></p>
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
                       class="group flex gap-4 py-4 <?php echo $r_index < count($rest) - 1 ? 'border-b oat-border-light' : ''; ?>">
                        <div class="relative flex-shrink-0 w-28 h-20 rounded-lg overflow-hidden">
                            <?php if ($thumb): ?>
                            <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($article->post_title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <?php else: ?>
                            <div class="w-full h-full oat-bg-surface flex items-center justify-center">
                                <svg class="w-8 h-8 oat-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                </svg>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm oat-text-muted"><?php echo esc_html($date); ?></span>
                                <?php if ($art_cat): ?>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full" style="background-color: color-mix(in srgb, <?php echo esc_attr($art_color); ?> 12%, transparent); color: <?php echo esc_attr($art_color); ?>">
                                    <?php echo esc_html($art_cat); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <h4 class="text-base font-bold oat-text group-hover:oat-text-secondary transition-colors line-clamp-2"><?php echo esc_html($article->post_title); ?></h4>
                        </div>
                        <span class="oat-text-muted flex-shrink-0 self-center">&rarr;</span>
                    </a>
                    <?php endforeach; ?>
                </div>

            </div>
            <?php else: ?>
            <div class="text-center oat-text-secondary py-8">
                <p>Ingen artikler publisert ennå.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Våre Temagrupper -->
    <section class="py-14 border-t oat-border">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <h2 class="text-2xl font-bold oat-text mb-6">Temagrupper</h2>

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
                <a href="<?php echo esc_url(home_url('/temagrupper/' . $group['slug'] . '/')); ?>"
                   class="block rounded-xl border oat-border-light p-5 hover:shadow-sm transition-all group oat-bg"
                   style="border-color: color-mix(in srgb, <?php echo esc_attr($group['color']); ?> 25%, var(--color-border-light, #D6D1C6))">
                    <div class="flex items-start justify-between mb-4">
                        <span class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: color-mix(in srgb, <?php echo esc_attr($group['color']); ?> 15%, transparent)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?php echo esc_attr($group['color']); ?>"><?php echo $group['icon']; ?></svg>
                        </span>
                        <span class="oat-text-muted group-hover:oat-text-secondary transition-colors">&rarr;</span>
                    </div>
                    <h3 class="font-bold oat-text text-lg mb-1"><?php echo esc_html($group['title']); ?></h3>
                    <p class="text-sm oat-text-secondary leading-relaxed mb-3"><?php echo esc_html($group['desc']); ?></p>
                    <span class="text-sm font-semibold oat-text">Les mer</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Kommende Arrangementer -->
    <section class="py-14 border-t oat-border">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold oat-text">Kommende Arrangementer</h2>
                <a href="<?php echo esc_url(home_url('/arrangementer/')); ?>" class="text-base oat-text-secondary font-medium flex items-center gap-1">Se alle <span>&rarr;</span></a>
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
                    'post_status' => 'publish',
                    'meta_key' => 'arrangement_dato',
                    'orderby' => 'meta_value',
                    'order' => 'ASC',
                ));
            }

            if (!empty($events)):
            ?>
            <div class="space-y-3">
                <?php
                foreach ($events as $event):
                    $event_date = get_field('arrangement_dato', $event->ID) ?: get_field('dato', $event->ID) ?: date('Y-m-d', strtotime($event->post_date));
                    $event_time_start = get_field('arrangement_tid_start', $event->ID) ?: get_field('tid_start', $event->ID) ?: '';
                    $event_time_end = get_field('arrangement_tid_slutt', $event->ID) ?: get_field('tid_slutt', $event->ID) ?: '';
                    $event_type = wp_get_post_terms($event->ID, 'arrangementstype', array('fields' => 'names'));
                    if (is_wp_error($event_type)) $event_type = array();
                    $event_format = get_field('arrangement_format', $event->ID) ?: get_field('format', $event->ID) ?: '';
                    $event_temagruppe = wp_get_post_terms($event->ID, 'temagruppe', array('fields' => 'names'));
                    if (is_wp_error($event_temagruppe)) $event_temagruppe = array();
                    $event_tg_name = !empty($event_temagruppe) ? $event_temagruppe[0] : '';
                    $event_tg_color = isset($tg_colors[$event_tg_name]) ? $tg_colors[$event_tg_name] : '';

                    $date_obj = DateTime::createFromFormat('Y-m-d', $event_date) ?: DateTime::createFromFormat('Ymd', $event_date);
                    $day = $date_obj ? $date_obj->format('d') : '';
                    $month = $date_obj ? strtoupper($date_obj->format('M')) : '';

                    $month_map = array('JAN' => 'JAN', 'FEB' => 'FEB', 'MAR' => 'MAR', 'APR' => 'APR', 'MAY' => 'MAI', 'JUN' => 'JUN', 'JUL' => 'JUL', 'AUG' => 'AUG', 'SEP' => 'SEP', 'OCT' => 'OKT', 'NOV' => 'NOV', 'DEC' => 'DES');
                    $month = isset($month_map[$month]) ? $month_map[$month] : $month;
                ?>
                <a href="<?php echo esc_url(get_permalink($event)); ?>"
                   class="flex items-center gap-5 rounded-lg p-4 border hover:shadow-sm transition-all group"
                   style="background-color: #FAFAF7; border-color: var(--color-border-light, #D6D1C6)">

                    <!-- Date badge -->
                    <div class="flex-shrink-0 w-14 h-14 rounded-lg flex flex-col items-center justify-center text-white" style="background-color: #1A1A1A">
                        <span class="text-lg font-bold leading-none"><?php echo esc_html($day); ?></span>
                        <span class="text-[10px] font-semibold tracking-wider leading-none mt-0.5"><?php echo esc_html($month); ?></span>
                    </div>

                    <!-- Event details -->
                    <div class="flex-grow min-w-0">
                        <h3 class="font-bold text-base mb-0.5 line-clamp-1 group-hover:opacity-70 transition-opacity" style="color: #1A1A1A"><?php echo esc_html($event->post_title); ?></h3>
                        <div class="flex flex-wrap items-center gap-2 text-sm" style="color: #888">
                            <?php if ($event_tg_name && $event_tg_color): ?>
                            <span class="font-semibold text-xs px-2 py-0.5 rounded-full" style="background-color: color-mix(in srgb, <?php echo esc_attr($event_tg_color); ?> 12%, transparent); color: <?php echo esc_attr($event_tg_color); ?>"><?php echo esc_html($event_tg_name); ?></span>
                            <?php endif; ?>
                            <?php if ($event_format || !empty($event_type)): ?>
                            <span><?php echo esc_html($event_format ?: (!empty($event_type) ? $event_type[0] : '')); ?></span>
                            <?php endif; ?>
                            <?php if ($event_time_start): ?>
                            <span>&middot; <?php echo esc_html($event_time_start); ?><?php echo $event_time_end ? '–' . esc_html($event_time_end) : ''; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Arrow -->
                    <span class="flex-shrink-0 text-sm" style="color: #888">&rarr;</span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="oat-bg-white rounded-lg border oat-border-light p-8 text-center">
                <p class="oat-text-secondary">Ingen arrangementer planlagt akkurat nå.</p>
                <p class="text-base oat-text-muted mt-1">Sjekk tilbake snart for kommende arrangementer.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Nettverket i tall -->
    <section class="py-16 border-t oat-border">
        <div class="max-w-6xl mx-auto px-4 md:px-8">
            <h2 class="text-2xl font-bold oat-text text-center mb-10">Nettverket i tall</h2>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold oat-text"><?php echo esc_html($total_companies); ?></div>
                    <div class="text-base oat-text-secondary mt-1">Deltakere</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold oat-text"><?php echo esc_html($total_tools); ?></div>
                    <div class="text-base oat-text-secondary mt-1">Verktøy</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold oat-text"><?php echo esc_html($total_events); ?></div>
                    <div class="text-base oat-text-secondary mt-1">Arrangementer</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold oat-text"><?php echo esc_html($total_sources); ?></div>
                    <div class="text-base oat-text-secondary mt-1">Kunnskapskilder</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold oat-text">6</div>
                    <div class="text-base oat-text-secondary mt-1">Temagrupper</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold oat-text">2012</div>
                    <div class="text-base oat-text-secondary mt-1">Etablert</div>
                </div>
            </div>
        </div>
    </section>

</main>

<?php get_footer();
