<?php
/**
 * Template Name: Medlemskatalog
 * 
 * Public member directory - browse all member companies
 * Filterable by industry, customer type, and theme groups
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
$args = array(
    'post_type' => 'foretak',
    'posts_per_page' => 12,
    'paged' => get_query_var('paged') ?: 1,
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
?>

<div class="min-h-screen bg-bim-beige-100 py-12">
    <div class="container mx-auto px-4">
        
        <!-- Header -->
        <div class="mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-bim-black-900 mb-3">
                Medlemmer av BIM Verdi
            </h1>
            <p class="text-xl text-bim-black-700">
                Utforsk nettverket av <?php echo wp_count_posts('foretak')->publish; ?> bedrifter
            </p>
        </div>

        <!-- Search & Filters -->
        <div class="card-hjem mb-8">
            <div class="card-body p-6">
                <form method="GET" class="space-y-4">
                    
                    <!-- Search Bar -->
                    <div class="relative">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-bim-black-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input 
                            type="text" 
                            name="s" 
                            value="<?php echo esc_attr($search); ?>"
                            placeholder="Søk etter bedriftsnavn..."
                            class="input-hjem-search w-full"
                        >
                    </div>

                    <!-- Filters Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        
                        <!-- Bransjekategori Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-bim-black-900 mb-2">Bransje</label>
                            <select name="bransje" class="input-hjem w-full">
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
                            <label class="block text-sm font-semibold text-bim-black-900 mb-2">Kundetype</label>
                            <select name="kundetype" class="input-hjem w-full">
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
                            <label class="block text-sm font-semibold text-bim-black-900 mb-2">Temagruppe</label>
                            <select name="temagruppe" class="input-hjem w-full">
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
                    <div class="flex gap-3 pt-4 border-t border-bim-black-200">
                        <button type="submit" class="btn btn-hjem-primary">
                            🔍 Søk
                        </button>
                        <a href="<?php echo get_permalink(); ?>" class="btn btn-hjem-outline">
                            Nullstill
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Active Filters Display -->
        <?php 
        $active_filters = array();
        if (!empty($search)) $active_filters[] = "Søk: <strong>" . esc_html($search) . "</strong>";
        if ($bransjekategori) {
            $term = get_term($bransjekategori, 'bransjekategori');
            $active_filters[] = "Bransje: <strong>" . esc_html($term->name) . "</strong>";
        }
        if ($kundetype) {
            $term = get_term($kundetype, 'kundetype');
            $active_filters[] = "Kundetype: <strong>" . esc_html($term->name) . "</strong>";
        }
        if ($temagruppe) {
            $term = get_term($temagruppe, 'temagruppe');
            $active_filters[] = "Temagruppe: <strong>" . esc_html($term->name) . "</strong>";
        }
        
        if (!empty($active_filters)): ?>
        <div class="mb-6 flex flex-wrap gap-2 items-center">
            <span class="text-sm font-semibold text-bim-black-900">Aktive filter:</span>
            <?php foreach ($active_filters as $filter): ?>
                <span class="badge badge-hjem">
                    <?php echo $filter; ?>
                </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Results Count -->
        <div class="mb-6">
            <p class="text-lg text-bim-black-700">
                Viser <strong><?php echo $members_query->found_posts; ?></strong> 
                <?php echo $members_query->found_posts === 1 ? 'medlem' : 'medlemmer'; ?>
            </p>
        </div>

        <!-- Member Grid -->
        <?php if ($members_query->have_posts()): ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php while ($members_query->have_posts()): $members_query->the_post();
                $logo_id = get_field('logo');
                $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
                $bedrift_beskrivelse = wp_trim_words(get_the_content(), 20);
                $bransjekategorier = wp_get_post_terms(get_the_ID(), 'bransjekategori', array('fields' => 'names'));
                $medlemstype = get_field('medlemstype');
            ?>
            
            <div class="card-hjem group hover:shadow-lg transition-shadow">
                
                <!-- Logo/Image -->
                <?php if ($logo_url): ?>
                <div class="h-48 bg-bim-beige-200 overflow-hidden flex items-center justify-center">
                    <img src="<?php echo esc_url($logo_url); ?>" 
                         alt="<?php the_title(); ?>" 
                         class="h-full w-full object-contain p-4">
                </div>
                <?php else: ?>
                <div class="h-48 bg-gradient-to-br from-bim-orange to-bim-purple flex items-center justify-center text-white text-4xl font-bold">
                    <?php echo strtoupper(substr(get_the_title(), 0, 1)); ?>
                </div>
                <?php endif; ?>

                <!-- Content -->
                <div class="card-body p-5">
                    
                    <!-- Member Status Badge -->
                    <?php if ($medlemstype === 'hovedpartner'): ?>
                        <span class="badge badge-hjem-orange mb-2">⭐ Hovedpartner</span>
                    <?php elseif ($medlemstype === 'partner'): ?>
                        <span class="badge badge-hjem mb-2">Partner</span>
                    <?php endif; ?>

                    <!-- Title -->
                    <h3 class="card-title text-lg text-bim-black-900 mb-2">
                        <?php the_title(); ?>
                    </h3>

                    <!-- Categories -->
                    <?php if (!empty($bransjekategorier)): ?>
                    <div class="flex flex-wrap gap-1 mb-3">
                        <?php foreach (array_slice($bransjekategorier, 0, 2) as $cat): ?>
                        <span class="text-xs bg-bim-purple bg-opacity-10 text-bim-purple px-2 py-1 rounded">
                            <?php echo esc_html($cat); ?>
                        </span>
                        <?php endforeach; ?>
                        <?php if (count($bransjekategorier) > 2): ?>
                        <span class="text-xs text-bim-black-600">+<?php echo count($bransjekategorier) - 2; ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <?php if (!empty($bedrift_beskrivelse)): ?>
                    <p class="text-sm text-bim-black-700 mb-4 line-clamp-3">
                        <?php echo esc_html($bedrift_beskrivelse); ?>
                    </p>
                    <?php endif; ?>

                    <!-- CTA -->
                    <div class="card-actions justify-end">
                        <a href="<?php the_permalink(); ?>" class="btn btn-sm btn-hjem-primary">
                            Se profil
                        </a>
                    </div>
                </div>
            </div>

            <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <!-- Pagination -->
        <?php 
        $pagination = paginate_links(array(
            'base' => get_pagenum_link(1) . '%_%',
            'format' => 'page/%#%/',
            'current' => max(1, get_query_var('paged')),
            'total' => $members_query->max_num_pages,
            'type' => 'array',
        ));
        
        if (!empty($pagination)): ?>
        <div class="flex justify-center gap-2 flex-wrap">
            <?php foreach ($pagination as $link): ?>
                <div><?php echo str_replace('page-numbers', 'btn btn-hjem-outline', $link); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        
        <!-- No Results -->
        <div class="card-hjem text-center py-16">
            <div class="text-6xl mb-4">🔍</div>
            <h3 class="text-2xl font-bold text-bim-black-900 mb-2">Ingen medlemmer funnet</h3>
            <p class="text-bim-black-700 mb-6">Prøv å justere filterene dine eller søket</p>
            <a href="<?php echo get_permalink(); ?>" class="btn btn-hjem-primary">
                Vis alle medlemmer
            </a>
        </div>

        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>
