<?php
/**
 * Archive template for Foretak (Member companies)
 * 
 * Displays a grid of all member companies with search and filtering
 * 
 * @package BimVerdi_Theme
 */

get_header();

// Get filter parameters from URL
$search = sanitize_text_field($_GET['s'] ?? '');
$bransjekategori = isset($_GET['bransje']) ? intval($_GET['bransje']) : 0;
$kundetype = isset($_GET['kundetype']) ? intval($_GET['kundetype']) : 0;
$temagruppe = isset($_GET['temagruppe']) ? intval($_GET['temagruppe']) : 0;

// Get all taxonomy terms for filters
$all_bransjekategorier = get_terms(array('taxonomy' => 'bransjekategori', 'hide_empty' => false));
$all_kundetyper = get_terms(array('taxonomy' => 'kundetype', 'hide_empty' => false));
$all_temagrupper = get_terms(array('taxonomy' => 'temagruppe', 'hide_empty' => false));

// Build query
$paged = get_query_var('paged') ?: 1;
$args = array(
    'post_type' => 'foretak',
    'posts_per_page' => 12,
    'paged' => $paged,
    'orderby' => 'title',
    'order' => 'ASC',
    'tax_query' => array('relation' => 'AND'),
);

// Add search
if (!empty($search)) {
    $args['s'] = $search;
}

// Add taxonomy filters
if ($bransjekategori) {
    $args['tax_query'][] = array(
        'taxonomy' => 'bransjekategori',
        'field' => 'term_id',
        'terms' => $bransjekategori,
    );
}

if ($kundetype) {
    $args['tax_query'][] = array(
        'taxonomy' => 'kundetype',
        'field' => 'term_id',
        'terms' => $kundetype,
    );
}

if ($temagruppe) {
    $args['tax_query'][] = array(
        'taxonomy' => 'temagruppe',
        'field' => 'term_id',
        'terms' => $temagruppe,
    );
}

$members_query = new WP_Query($args);
$total_foretak = wp_count_posts('foretak')->publish;
?>

<div class="min-h-screen bg-bim-beige-100">
    
    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-bim-purple to-bim-purple-700 text-white py-16">
        <div class="container mx-auto px-4">
            <div class="flex items-center gap-2 text-purple-200 mb-4">
                <a href="<?php echo home_url(); ?>" class="hover:text-white transition-colors">Hjem</a>
                <wa-icon library="fa" name="sharp-solid-chevron-right" class="text-xs"></wa-icon>
                <span>Medlemmer</span>
            </div>
            
            <h1 class="text-4xl md:text-5xl font-bold mb-4">
                Våre medlemmer
            </h1>
            <p class="text-xl text-purple-100 max-w-2xl">
                Utforsk nettverket av <?php echo intval($total_foretak); ?> foretak som sammen utvikler norsk BIM-bransje
            </p>
            
            <!-- Quick stats -->
            <div class="flex gap-8 mt-8">
                <div class="text-center">
                    <div class="text-3xl font-bold"><?php echo intval($total_foretak); ?></div>
                    <div class="text-purple-200 text-sm">Foretak</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold"><?php 
                        echo count(get_terms(array('taxonomy' => 'bransjekategori', 'hide_empty' => true))); 
                    ?></div>
                    <div class="text-purple-200 text-sm">Bransjer</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold">6</div>
                    <div class="text-purple-200 text-sm">Temagrupper</div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-12">

        <!-- Search & Filters -->
        <wa-card class="mb-8">
            <div class="p-6">
                <form method="GET" action="<?php echo get_post_type_archive_link('foretak'); ?>" class="space-y-4">
                    
                    <!-- Search Bar -->
                    <div class="relative">
                        <wa-icon library="fa" name="sharp-solid-magnifying-glass" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></wa-icon>
                        <input 
                            type="text" 
                            name="s" 
                            value="<?php echo esc_attr($search); ?>"
                            placeholder="Søk etter bedriftsnavn..."
                            class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bim-purple focus:border-bim-purple"
                        >
                    </div>

                    <!-- Filters Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        
                        <!-- Bransjekategori Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Bransje</label>
                            <select name="bransje" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bim-purple focus:border-bim-purple">
                                <option value="">Alle bransjer</option>
                                <?php foreach ($all_bransjekategorier as $term): ?>
                                    <option value="<?php echo $term->term_id; ?>" <?php selected($bransjekategori, $term->term_id); ?>>
                                        <?php echo esc_html($term->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Kundetype Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Kundetype</label>
                            <select name="kundetype" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bim-purple focus:border-bim-purple">
                                <option value="">Alle kundetyper</option>
                                <?php foreach ($all_kundetyper as $term): ?>
                                    <option value="<?php echo $term->term_id; ?>" <?php selected($kundetype, $term->term_id); ?>>
                                        <?php echo esc_html($term->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Temagruppe Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Temagruppe</label>
                            <select name="temagruppe" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bim-purple focus:border-bim-purple">
                                <option value="">Alle temagrupper</option>
                                <?php foreach ($all_temagrupper as $term): ?>
                                    <option value="<?php echo $term->term_id; ?>" <?php selected($temagruppe, $term->term_id); ?>>
                                        <?php echo esc_html($term->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <wa-button type="submit" variant="brand">
                            <wa-icon library="fa" name="sharp-solid-magnifying-glass" slot="prefix"></wa-icon>
                            Søk
                        </wa-button>
                        <wa-button variant="neutral" outline href="<?php echo get_post_type_archive_link('foretak'); ?>">
                            Nullstill
                        </wa-button>
                    </div>
                </form>
            </div>
        </wa-card>

        <!-- Active Filters Display -->
        <?php 
        $active_filters = array();
        if (!empty($search)) $active_filters[] = array('type' => 'Søk', 'value' => $search);
        if ($bransjekategori) {
            $term = get_term($bransjekategori, 'bransjekategori');
            if ($term) $active_filters[] = array('type' => 'Bransje', 'value' => $term->name);
        }
        if ($kundetype) {
            $term = get_term($kundetype, 'kundetype');
            if ($term) $active_filters[] = array('type' => 'Kundetype', 'value' => $term->name);
        }
        if ($temagruppe) {
            $term = get_term($temagruppe, 'temagruppe');
            if ($term) $active_filters[] = array('type' => 'Temagruppe', 'value' => $term->name);
        }
        
        if (!empty($active_filters)): ?>
        <div class="mb-6 flex flex-wrap gap-2 items-center">
            <span class="text-sm font-semibold text-gray-700">Aktive filter:</span>
            <?php foreach ($active_filters as $filter): ?>
                <wa-tag variant="primary" size="small">
                    <?php echo esc_html($filter['type']); ?>: <?php echo esc_html($filter['value']); ?>
                </wa-tag>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Results Count -->
        <div class="mb-6">
            <p class="text-lg text-gray-700">
                Viser <strong><?php echo $members_query->found_posts; ?></strong> 
                <?php echo $members_query->found_posts === 1 ? 'foretak' : 'foretak'; ?>
            </p>
        </div>

        <!-- Member Grid -->
        <?php if ($members_query->have_posts()): ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php while ($members_query->have_posts()): $members_query->the_post();
                $logo_id = get_field('logo');
                $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
                $beskrivelse = get_field('beskrivelse');
                $bedrift_beskrivelse = $beskrivelse ? wp_trim_words($beskrivelse, 20) : wp_trim_words(get_the_content(), 20);
                $bransjekategorier_terms = wp_get_post_terms(get_the_ID(), 'bransjekategori', array('fields' => 'names'));
                $temagrupper_terms = wp_get_post_terms(get_the_ID(), 'temagruppe');
                $er_aktiv = get_field('er_aktiv_deltaker');
                $poststed = get_field('poststed');
                
                // Get employee count
                $ansatte_args = array(
                    'meta_key' => 'tilknyttet_foretak',
                    'meta_value' => get_the_ID(),
                    'count_total' => true,
                );
                $ansatte_count = count(get_users($ansatte_args));
            ?>
            
            <wa-card class="group hover:shadow-lg transition-all duration-200">
                
                <!-- Logo/Image -->
                <div class="h-48 bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden flex items-center justify-center relative">
                    <?php if ($logo_url): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" 
                             alt="<?php the_title(); ?>" 
                             class="h-full w-full object-contain p-6">
                    <?php else: ?>
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-bim-orange to-bim-purple flex items-center justify-center text-white text-4xl font-bold shadow-lg">
                            <?php echo strtoupper(mb_substr(get_the_title(), 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Status badge -->
                    <?php if ($er_aktiv): ?>
                    <div class="absolute top-3 right-3">
                        <wa-badge variant="success">Aktiv</wa-badge>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Content -->
                <div class="p-5">
                    
                    <!-- Title and location -->
                    <h3 class="text-lg font-bold text-gray-900 mb-1 group-hover:text-bim-purple transition-colors">
                        <?php the_title(); ?>
                    </h3>
                    
                    <?php if ($poststed): ?>
                    <p class="text-sm text-gray-500 mb-3 flex items-center gap-1">
                        <wa-icon library="fa" name="sharp-solid-location-dot" class="text-xs"></wa-icon>
                        <?php echo esc_html($poststed); ?>
                    </p>
                    <?php endif; ?>

                    <!-- Categories -->
                    <?php if (!empty($bransjekategorier_terms)): ?>
                    <div class="flex flex-wrap gap-1 mb-3">
                        <?php foreach (array_slice($bransjekategorier_terms, 0, 2) as $cat): ?>
                        <wa-tag size="small" variant="neutral">
                            <?php echo esc_html($cat); ?>
                        </wa-tag>
                        <?php endforeach; ?>
                        <?php if (count($bransjekategorier_terms) > 2): ?>
                        <wa-tag size="small" variant="neutral">+<?php echo count($bransjekategorier_terms) - 2; ?></wa-tag>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Temagrupper icons -->
                    <?php if (!empty($temagrupper_terms)): ?>
                    <div class="flex flex-wrap gap-1 mb-3">
                        <?php foreach (array_slice($temagrupper_terms, 0, 3) as $tg): 
                            $icon = get_field('ikon', 'temagruppe_' . $tg->term_id) ?: 'users';
                        ?>
                        <span class="text-xs bg-bim-purple bg-opacity-10 text-bim-purple px-2 py-1 rounded flex items-center gap-1" title="<?php echo esc_attr($tg->name); ?>">
                            <wa-icon library="fa" name="sharp-solid-<?php echo esc_attr($icon); ?>" class="text-xs"></wa-icon>
                            <?php echo esc_html($tg->name); ?>
                        </span>
                        <?php endforeach; ?>
                        <?php if (count($temagrupper_terms) > 3): ?>
                        <span class="text-xs text-gray-500">+<?php echo count($temagrupper_terms) - 3; ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <?php if (!empty($bedrift_beskrivelse)): ?>
                    <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                        <?php echo esc_html($bedrift_beskrivelse); ?>
                    </p>
                    <?php endif; ?>

                    <!-- Stats and CTA -->
                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        <div class="flex items-center gap-3 text-sm text-gray-500">
                            <?php if ($ansatte_count > 0): ?>
                            <span class="flex items-center gap-1">
                                <wa-icon library="fa" name="sharp-solid-users" class="text-xs"></wa-icon>
                                <?php echo $ansatte_count; ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($temagrupper_terms)): ?>
                            <span class="flex items-center gap-1">
                                <wa-icon library="fa" name="sharp-solid-layer-group" class="text-xs"></wa-icon>
                                <?php echo count($temagrupper_terms); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <wa-button size="small" variant="brand" href="<?php the_permalink(); ?>">
                            Se profil
                        </wa-button>
                    </div>
                </div>
            </wa-card>

            <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <!-- Pagination -->
        <?php 
        $pagination = paginate_links(array(
            'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
            'format' => '?paged=%#%',
            'current' => max(1, $paged),
            'total' => $members_query->max_num_pages,
            'type' => 'array',
            'prev_text' => '&laquo; Forrige',
            'next_text' => 'Neste &raquo;',
        ));
        
        if (!empty($pagination)): ?>
        <div class="flex justify-center gap-2 flex-wrap mt-8">
            <?php foreach ($pagination as $link): 
                // Add Tailwind classes to pagination links
                $link = str_replace('page-numbers', 'px-4 py-2 rounded-lg text-sm font-medium transition-colors', $link);
                $link = str_replace('current', 'bg-bim-purple text-white', $link);
                if (strpos($link, 'current') === false) {
                    $link = str_replace('transition-colors', 'transition-colors bg-white border border-gray-300 hover:bg-gray-50 text-gray-700', $link);
                }
            ?>
                <?php echo $link; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        
        <!-- No Results -->
        <wa-card class="text-center py-16">
            <div class="text-6xl mb-4">🔍</div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Ingen foretak funnet</h3>
            <p class="text-gray-600 mb-6">Prøv å justere filterene dine eller søket</p>
            <wa-button variant="brand" href="<?php echo get_post_type_archive_link('foretak'); ?>">
                Vis alle foretak
            </wa-button>
        </wa-card>

        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>
