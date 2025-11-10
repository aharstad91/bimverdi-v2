<?php
/**
 * Single Member Profile
 * 
 * Display detailed company profile with logo, description, tools, and articles
 * 
 * @package BimVerdi_Theme
 */

get_header();

$company_id = get_the_ID();
$bedriftsnavn = get_field('bedriftsnavn', $company_id);
$adresse = get_field('adresse', $company_id);
$postnummer = get_field('postnummer', $company_id);
$poststed = get_field('poststed', $company_id);
$telefon = get_field('telefon', $company_id);
$nettside = get_field('nettside', $company_id);
$logo_id = get_field('logo', $company_id);
$logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
$medlemstype = get_field('medlemstype', $company_id);

// Get associated taxonomies
$bransjekategorier = wp_get_post_terms($company_id, 'bransjekategori', array('fields' => 'names'));
$kundetyper = wp_get_post_terms($company_id, 'kundetype', array('fields' => 'names'));
$temagrupper = wp_get_post_terms($company_id, 'temagruppe', array('fields' => 'names'));

// Get company's tools
$company_tools = get_posts(array(
    'post_type' => 'verktoy',
    'meta_query' => array(
        array(
            'key' => 'verktoy_eier',
            'value' => $company_id,
        )
    ),
    'posts_per_page' => 6,
    'post_status' => 'publish',
));

// Get company's articles (by users from this company)
$company_users = get_users(array(
    'meta_key' => 'bim_verdi_company_id',
    'meta_value' => $company_id,
));
$user_ids = wp_list_pluck($company_users, 'ID');

$company_articles = !empty($user_ids) ? get_posts(array(
    'post_type' => 'post',
    'author__in' => $user_ids,
    'posts_per_page' => 3,
)) : array();

?>

<div class="min-h-screen bg-bim-beige-100 py-12">
    <div class="container mx-auto px-4">
        
        <!-- Breadcrumb -->
        <div class="mb-6">
            <a href="<?php echo home_url('/medlemmer/'); ?>" class="text-bim-orange hover:text-bim-orange-700">← Tilbake til medlemmer</a>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Sidebar -->
            <aside class="lg:col-span-1">
                
                <!-- Company Info Card -->
                <div class="card-hjem mb-6">
                    <div class="card-body p-6">
                        
                        <!-- Logo -->
                        <div class="mb-4 text-center">
                            <?php if ($logo_url): ?>
                            <img src="<?php echo esc_url($logo_url); ?>" 
                                 alt="<?php echo esc_attr($bedriftsnavn); ?>" 
                                 class="w-full h-auto max-h-32 object-contain mx-auto">
                            <?php else: ?>
                            <div class="w-32 h-32 bg-gradient-to-br from-bim-orange to-bim-purple text-white flex items-center justify-center text-4xl font-bold rounded-lg mx-auto">
                                <?php echo strtoupper(substr(get_the_title(), 0, 1)); ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Status Badge -->
                        <?php if ($medlemstype === 'hovedpartner'): ?>
                            <div class="text-center mb-4">
                                <span class="badge badge-hjem-orange">⭐ Hovedpartner</span>
                            </div>
                        <?php elseif ($medlemstype === 'partner'): ?>
                            <div class="text-center mb-4">
                                <span class="badge badge-hjem">Partner</span>
                            </div>
                        <?php endif; ?>

                        <!-- Contact Info -->
                        <h2 class="text-2xl font-bold text-bim-black-900 mb-4">Kontaktinfo</h2>
                        
                        <div class="space-y-3 text-sm">
                            <?php if ($adresse): ?>
                            <div>
                                <span class="font-semibold text-bim-black-900">Adresse:</span>
                                <div class="text-bim-black-700">
                                    <?php echo esc_html($adresse); ?><br>
                                    <?php echo esc_html($postnummer); ?> <?php echo esc_html($poststed); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($telefon): ?>
                            <div>
                                <span class="font-semibold text-bim-black-900">Telefon:</span>
                                <div class="text-bim-black-700">
                                    <a href="tel:<?php echo esc_attr($telefon); ?>" class="text-bim-orange hover:underline">
                                        <?php echo esc_html($telefon); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($nettside): ?>
                            <div>
                                <span class="font-semibold text-bim-black-900">Nettside:</span>
                                <div class="text-bim-black-700">
                                    <a href="<?php echo esc_url($nettside); ?>" target="_blank" rel="noopener" class="text-bim-orange hover:underline">
                                        Besøk →
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Categories -->
                <?php if (!empty($bransjekategorier) || !empty($kundetyper) || !empty($temagrupper)): ?>
                <div class="card-hjem">
                    <div class="card-body p-6">
                        <h3 class="text-lg font-bold text-bim-black-900 mb-4">Kategorier</h3>

                        <?php if (!empty($bransjekategorier)): ?>
                        <div class="mb-4">
                            <span class="text-sm font-semibold text-bim-black-900 block mb-2">Bransje:</span>
                            <div class="flex flex-wrap gap-1">
                                <?php foreach ($bransjekategorier as $cat): ?>
                                <span class="text-xs bg-bim-purple bg-opacity-10 text-bim-purple px-2 py-1 rounded">
                                    <?php echo esc_html($cat); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($kundetyper)): ?>
                        <div class="mb-4">
                            <span class="text-sm font-semibold text-bim-black-900 block mb-2">Kundetyper:</span>
                            <div class="flex flex-wrap gap-1">
                                <?php foreach ($kundetyper as $type): ?>
                                <span class="text-xs bg-bim-orange bg-opacity-10 text-bim-orange px-2 py-1 rounded">
                                    <?php echo esc_html($type); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($temagrupper)): ?>
                        <div>
                            <span class="text-sm font-semibold text-bim-black-900 block mb-2">Temagrupper:</span>
                            <div class="flex flex-wrap gap-1">
                                <?php foreach ($temagrupper as $tema): ?>
                                <span class="text-xs bg-bim-orange bg-opacity-20 text-bim-orange px-2 py-1 rounded">
                                    <?php echo esc_html($tema); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            </aside>

            <!-- Main Content -->
            <main class="lg:col-span-2">
                
                <!-- Title & Description -->
                <div class="card-hjem mb-8">
                    <div class="card-body p-6">
                        <h1 class="text-4xl font-bold text-bim-black-900 mb-4">
                            <?php the_title(); ?>
                        </h1>
                        
                        <?php if (has_excerpt()): ?>
                        <p class="text-lg text-bim-black-700 mb-4">
                            <?php echo esc_html(get_the_excerpt()); ?>
                        </p>
                        <?php endif; ?>

                        <?php the_content(); ?>
                    </div>
                </div>

                <!-- Company Tools -->
                <?php if (!empty($company_tools)): ?>
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-bim-black-900 mb-4">Verktøy fra denne bedriften</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($company_tools as $tool): ?>
                        <div class="card-hjem">
                            <div class="card-body p-4">
                                <h3 class="font-bold text-bim-black-900 mb-2">
                                    <?php echo esc_html($tool->post_title); ?>
                                </h3>
                                <p class="text-sm text-bim-black-700 mb-3">
                                    <?php echo esc_html(wp_trim_words($tool->post_excerpt, 15)); ?>
                                </p>
                                <a href="<?php echo get_permalink($tool->ID); ?>" class="btn btn-sm btn-hjem-outline">
                                    Se verktøy
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Company Articles -->
                <?php if (!empty($company_articles)): ?>
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-bim-black-900 mb-4">Artikler fra denne bedriften</h2>
                    <div class="space-y-4">
                        <?php foreach ($company_articles as $article): ?>
                        <div class="card-hjem">
                            <div class="card-body p-6">
                                <h3 class="font-bold text-bim-black-900 mb-2">
                                    <a href="<?php echo get_permalink($article->ID); ?>" class="text-bim-orange hover:underline">
                                        <?php echo esc_html($article->post_title); ?>
                                    </a>
                                </h3>
                                <p class="text-sm text-bim-black-700 mb-3">
                                    <?php echo esc_html(wp_trim_words($article->post_excerpt ?: $article->post_content, 30)); ?>
                                </p>
                                <div class="text-xs text-bim-black-600">
                                    📅 <?php echo date_i18n('j. F Y', strtotime($article->post_date)); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </main>
        </div>

    </div>
</div>

<?php get_footer(); ?>
