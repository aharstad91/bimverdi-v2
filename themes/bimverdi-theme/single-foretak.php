<?php
/**
 * Single Foretak Profile
 * 
 * Offentlig visning av foretaksprofil med logo, beskrivelse, verktøy og kontaktinfo
 * 
 * @package BimVerdi_Theme
 */

get_header();

$company_id = get_the_ID();
$company_title = get_the_title();

// Hent ACF-felter
$logo_id = get_field('logo', $company_id);
$logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
$beskrivelse = get_field('beskrivelse', $company_id);
$org_nummer = get_field('organisasjonsnummer', $company_id);
$adresse = get_field('adresse', $company_id);
$postnummer = get_field('postnummer', $company_id);
$poststed = get_field('poststed', $company_id);
$telefon = get_field('telefon', $company_id);
$nettside = get_field('nettside', $company_id);
$kontakt_epost = get_field('kontakt_epost', $company_id);
$er_aktiv_deltaker = get_field('er_aktiv_deltaker', $company_id);

// Hent taxonomier
$bransjekategorier = wp_get_post_terms($company_id, 'bransjekategori', array('fields' => 'all'));
$kundetyper = wp_get_post_terms($company_id, 'kundetype', array('fields' => 'all'));
$temagrupper = wp_get_post_terms($company_id, 'temagruppe', array('fields' => 'all'));

// Hent foretakets verktøy
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

// Hent ansatte/brukere i foretaket
$company_users = get_users(array(
    'meta_key' => 'bim_verdi_company_id',
    'meta_value' => $company_id,
));

// Antall verktøy
$tool_count = count($company_tools);
$user_count = count($company_users);

// Sjekk om besøkende bruker tilhører dette foretaket
$current_user_company_id = get_user_meta(get_current_user_id(), 'bim_verdi_company_id', true);
$is_own_company = ($current_user_company_id == $company_id);

?>

<div class="min-h-screen bg-gradient-to-b from-bim-beige-50 to-white py-8">
    <div class="container mx-auto px-4 max-w-6xl">
        
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <wa-breadcrumb>
                <wa-breadcrumb-item href="<?php echo home_url(); ?>">Hjem</wa-breadcrumb-item>
                <wa-breadcrumb-item href="<?php echo home_url('/medlemmer/'); ?>">Medlemmer</wa-breadcrumb-item>
                <wa-breadcrumb-item><?php echo esc_html($company_title); ?></wa-breadcrumb-item>
            </wa-breadcrumb>
        </nav>

        <!-- Hero Section -->
        <wa-card class="mb-8">
            <div class="p-8">
                <div class="flex flex-col md:flex-row gap-8 items-start">
                    
                    <!-- Logo -->
                    <div class="flex-shrink-0">
                        <?php if ($logo_url): ?>
                            <div class="w-32 h-32 bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center justify-center">
                                <img src="<?php echo esc_url($logo_url); ?>" 
                                     alt="<?php echo esc_attr($company_title); ?>" 
                                     class="max-w-full max-h-full object-contain">
                            </div>
                        <?php else: ?>
                            <wa-avatar 
                                initials="<?php echo esc_attr(strtoupper(substr($company_title, 0, 2))); ?>"
                                shape="rounded"
                                style="--size: 8rem; font-size: 2rem;">
                            </wa-avatar>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Company Info -->
                    <div class="flex-1">
                        <div class="flex flex-wrap items-start gap-3 mb-4">
                            <h1 class="text-3xl md:text-4xl font-bold text-gray-900">
                                <?php echo esc_html($company_title); ?>
                            </h1>
                            
                            <?php if ($er_aktiv_deltaker): ?>
                                <wa-tag variant="success" size="small">
                                    <wa-icon library="fa" name="fas-check-circle" slot="prefix"></wa-icon>
                                    Aktiv deltaker
                                </wa-tag>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($beskrivelse): ?>
                            <p class="text-lg text-gray-600 mb-6 leading-relaxed">
                                <?php echo esc_html($beskrivelse); ?>
                            </p>
                        <?php elseif (has_excerpt()): ?>
                            <p class="text-lg text-gray-600 mb-6 leading-relaxed">
                                <?php echo get_the_excerpt(); ?>
                            </p>
                        <?php endif; ?>
                        
                        <!-- Stats -->
                        <div class="flex flex-wrap gap-6">
                            <?php if ($tool_count > 0): ?>
                                <div class="flex items-center gap-2 text-gray-600">
                                    <wa-icon library="fa" name="fas-tools" class="text-bim-orange"></wa-icon>
                                    <span><strong><?php echo $tool_count; ?></strong> verktøy</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($user_count > 0): ?>
                                <div class="flex items-center gap-2 text-gray-600">
                                    <wa-icon library="fa" name="fas-users" class="text-bim-purple"></wa-icon>
                                    <span><strong><?php echo $user_count; ?></strong> ansatte</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($temagrupper) && !is_wp_error($temagrupper)): ?>
                                <div class="flex items-center gap-2 text-gray-600">
                                    <wa-icon library="fa" name="fas-layer-group" class="text-green-600"></wa-icon>
                                    <span><strong><?php echo count($temagrupper); ?></strong> temagrupper</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($is_own_company): ?>
                            <div class="mt-6">
                                <wa-button variant="brand" href="<?php echo home_url('/min-side/foretak/'); ?>">
                                    <wa-icon library="fa" name="fas-edit" slot="prefix"></wa-icon>
                                    Rediger foretak
                                </wa-button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </wa-card>

        <!-- Main Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Main Content (2 columns) -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Verktøy Section -->
                <?php if (!empty($company_tools)): ?>
                <section>
                    <div class="flex items-center gap-3 mb-4">
                        <wa-icon library="fa" name="fas-tools" class="text-2xl text-bim-orange"></wa-icon>
                        <h2 class="text-2xl font-bold text-gray-900">Verktøy fra <?php echo esc_html($company_title); ?></h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($company_tools as $tool): 
                            $tool_excerpt = $tool->post_excerpt ?: wp_trim_words($tool->post_content, 20, '...');
                            $tool_categories = wp_get_post_terms($tool->ID, 'verktoykategori', array('fields' => 'names'));
                        ?>
                        <wa-card class="transition-all hover:shadow-lg">
                            <div class="p-5">
                                <h3 class="font-bold text-gray-900 mb-2 text-lg">
                                    <?php echo esc_html($tool->post_title); ?>
                                </h3>
                                
                                <?php if (!empty($tool_categories)): ?>
                                    <div class="mb-3">
                                        <?php foreach (array_slice($tool_categories, 0, 2) as $cat): ?>
                                            <wa-tag size="small" variant="neutral" class="mr-1">
                                                <?php echo esc_html($cat); ?>
                                            </wa-tag>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="text-gray-600 text-sm mb-4">
                                    <?php echo esc_html($tool_excerpt); ?>
                                </p>
                                
                                <wa-button variant="neutral" outline size="small" href="<?php echo get_permalink($tool->ID); ?>">
                                    Se verktøy
                                    <wa-icon library="fa" name="fas-arrow-right" slot="suffix"></wa-icon>
                                </wa-button>
                            </div>
                        </wa-card>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Artikler Section -->
                <?php 
                $company_articles = get_posts(array(
                    'post_type' => 'artikkel',
                    'post_status' => 'publish',
                    'posts_per_page' => 6,
                    'meta_query' => array(
                        array(
                            'key' => 'artikkel_bedrift',
                            'value' => $company_id,
                        ),
                    ),
                ));
                
                if (!empty($company_articles)): 
                    $category_labels = array(
                        'fagartikkel' => 'Fagartikkel',
                        'case' => 'Case',
                        'tips' => 'Tips',
                        'nyhet' => 'Nyhet',
                        'kommentar' => 'Kommentar',
                    );
                ?>
                <section>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <wa-icon library="fa" name="fas-newspaper" class="text-2xl text-purple-600"></wa-icon>
                            <h2 class="text-2xl font-bold text-gray-900">Artikler fra <?php echo esc_html($company_title); ?></h2>
                        </div>
                        <wa-badge variant="neutral"><?php echo count($company_articles); ?> artikler</wa-badge>
                    </div>
                    
                    <div class="grid gap-4">
                        <?php foreach ($company_articles as $article): 
                            $kategori = get_field('artikkel_kategori', $article->ID);
                            $ingress = get_field('artikkel_ingress', $article->ID);
                            $author = get_the_author_meta('display_name', $article->post_author);
                        ?>
                        <wa-card>
                            <div class="p-5">
                                <div class="flex flex-col md:flex-row md:items-start gap-4">
                                    <div class="flex-1">
                                        <?php if ($kategori && isset($category_labels[$kategori])): ?>
                                            <wa-tag variant="neutral" class="mb-2"><?php echo esc_html($category_labels[$kategori]); ?></wa-tag>
                                        <?php endif; ?>
                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                            <a href="<?php echo get_permalink($article->ID); ?>" class="hover:text-orange-600">
                                                <?php echo esc_html($article->post_title); ?>
                                            </a>
                                        </h3>
                                        <?php if ($ingress): ?>
                                            <p class="text-gray-600 text-sm mb-3"><?php echo wp_trim_words(esc_html($ingress), 25); ?></p>
                                        <?php endif; ?>
                                        <div class="flex items-center gap-4 text-sm text-gray-500">
                                            <span>
                                                <wa-icon library="fa" name="far-user" class="mr-1"></wa-icon>
                                                <?php echo esc_html($author); ?>
                                            </span>
                                            <span>
                                                <wa-icon library="fa" name="far-calendar" class="mr-1"></wa-icon>
                                                <?php echo get_the_date('j. M Y', $article->ID); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <wa-button variant="neutral" outline size="small" href="<?php echo get_permalink($article->ID); ?>">
                                        Les artikkel
                                    </wa-button>
                                </div>
                            </div>
                        </wa-card>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Temagrupper Section -->
                <?php if (!empty($temagrupper) && !is_wp_error($temagrupper)): ?>
                <section>
                    <div class="flex items-center gap-3 mb-4">
                        <wa-icon library="fa" name="fas-layer-group" class="text-2xl text-green-600"></wa-icon>
                        <h2 class="text-2xl font-bold text-gray-900">Aktive temagrupper</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <?php 
                        // Temagruppe beskrivelser
                        $temagruppe_info = array(
                            'bimtech' => array('icon' => 'fas-microchip', 'color' => 'purple'),
                            'byggesaksbim' => array('icon' => 'fas-file-contract', 'color' => 'blue'),
                            'eiendomsbim' => array('icon' => 'fas-building', 'color' => 'green'),
                            'miljobim' => array('icon' => 'fas-leaf', 'color' => 'teal'),
                            'prosjektbim' => array('icon' => 'fas-project-diagram', 'color' => 'orange'),
                            'sirkbim' => array('icon' => 'fas-recycle', 'color' => 'cyan'),
                        );
                        
                        foreach ($temagrupper as $gruppe): 
                            $slug = $gruppe->slug;
                            $info = isset($temagruppe_info[$slug]) ? $temagruppe_info[$slug] : array('icon' => 'fas-layer-group', 'color' => 'gray');
                            $page_url = home_url('/temagruppe-' . $slug . '/');
                        ?>
                        <a href="<?php echo esc_url($page_url); ?>" class="block">
                            <wa-card class="transition-all hover:shadow-lg hover:-translate-y-1">
                                <div class="p-4 text-center">
                                    <wa-icon library="fa" name="<?php echo esc_attr($info['icon']); ?>" 
                                             class="text-3xl text-<?php echo esc_attr($info['color']); ?>-600 mb-2"></wa-icon>
                                    <h3 class="font-semibold text-gray-900"><?php echo esc_html($gruppe->name); ?></h3>
                                </div>
                            </wa-card>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Ansatte Section -->
                <?php if (!empty($company_users) && count($company_users) > 0): ?>
                <section>
                    <div class="flex items-center gap-3 mb-4">
                        <wa-icon library="fa" name="fas-users" class="text-2xl text-bim-purple"></wa-icon>
                        <h2 class="text-2xl font-bold text-gray-900">Ansatte</h2>
                    </div>
                    
                    <wa-card>
                        <div class="p-4">
                            <div class="flex flex-wrap gap-4">
                                <?php foreach ($company_users as $user): 
                                    $user_name = $user->display_name;
                                    $user_title = get_user_meta($user->ID, 'stillingstittel', true);
                                    $avatar_url = get_avatar_url($user->ID, array('size' => 80));
                                ?>
                                <div class="flex items-center gap-3 bg-gray-50 rounded-lg p-3 pr-5">
                                    <wa-avatar 
                                        image="<?php echo esc_url($avatar_url); ?>"
                                        label="<?php echo esc_attr($user_name); ?>"
                                        style="--size: 3rem;">
                                    </wa-avatar>
                                    <div>
                                        <div class="font-medium text-gray-900"><?php echo esc_html($user_name); ?></div>
                                        <?php if ($user_title): ?>
                                            <div class="text-sm text-gray-500"><?php echo esc_html($user_title); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </wa-card>
                </section>
                <?php endif; ?>
                
            </div>
            
            <!-- Sidebar (1 column) -->
            <aside class="space-y-6">
                
                <!-- Kontaktinfo -->
                <wa-card>
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <wa-icon library="fa" name="fas-address-card"></wa-icon>
                            Kontaktinformasjon
                        </h3>
                        
                        <div class="space-y-4">
                            <?php if ($adresse || $postnummer || $poststed): ?>
                            <div class="flex gap-3">
                                <wa-icon library="fa" name="fas-map-marker-alt" class="text-gray-400 mt-1"></wa-icon>
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Adresse</div>
                                    <div class="text-gray-900">
                                        <?php if ($adresse): echo esc_html($adresse) . '<br>'; endif; ?>
                                        <?php echo esc_html(trim($postnummer . ' ' . $poststed)); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($telefon): ?>
                            <div class="flex gap-3">
                                <wa-icon library="fa" name="fas-phone" class="text-gray-400 mt-1"></wa-icon>
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Telefon</div>
                                    <a href="tel:<?php echo esc_attr($telefon); ?>" class="text-bim-orange hover:underline">
                                        <?php echo esc_html($telefon); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($kontakt_epost): ?>
                            <div class="flex gap-3">
                                <wa-icon library="fa" name="fas-envelope" class="text-gray-400 mt-1"></wa-icon>
                                <div>
                                    <div class="text-sm font-medium text-gray-500">E-post</div>
                                    <a href="mailto:<?php echo esc_attr($kontakt_epost); ?>" class="text-bim-orange hover:underline break-all">
                                        <?php echo esc_html($kontakt_epost); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($nettside): ?>
                            <div class="flex gap-3">
                                <wa-icon library="fa" name="fas-globe" class="text-gray-400 mt-1"></wa-icon>
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Nettside</div>
                                    <a href="<?php echo esc_url($nettside); ?>" target="_blank" rel="noopener" class="text-bim-orange hover:underline">
                                        Besøk nettside →
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($org_nummer): ?>
                            <div class="flex gap-3">
                                <wa-icon library="fa" name="fas-building" class="text-gray-400 mt-1"></wa-icon>
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Org.nummer</div>
                                    <div class="text-gray-900 font-mono"><?php echo esc_html($org_nummer); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </wa-card>
                
                <!-- Kategorier -->
                <?php if (!empty($bransjekategorier) || !empty($kundetyper)): ?>
                <wa-card>
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <wa-icon library="fa" name="fas-tags"></wa-icon>
                            Kategorier
                        </h3>
                        
                        <?php if (!empty($bransjekategorier) && !is_wp_error($bransjekategorier)): ?>
                        <div class="mb-4">
                            <div class="text-sm font-medium text-gray-500 mb-2">Bransje</div>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($bransjekategorier as $cat): ?>
                                    <wa-tag variant="primary" size="small">
                                        <?php echo esc_html($cat->name); ?>
                                    </wa-tag>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($kundetyper) && !is_wp_error($kundetyper)): ?>
                        <div>
                            <div class="text-sm font-medium text-gray-500 mb-2">Kundetyper</div>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($kundetyper as $type): ?>
                                    <wa-tag variant="neutral" size="small">
                                        <?php echo esc_html($type->name); ?>
                                    </wa-tag>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </wa-card>
                <?php endif; ?>
                
                <!-- CTA: Bli medlem -->
                <?php if (!is_user_logged_in()): ?>
                <wa-card class="bg-gradient-to-br from-bim-orange to-orange-600 text-white">
                    <div class="p-6 text-center">
                        <wa-icon library="fa" name="fas-handshake" class="text-4xl mb-3 opacity-90"></wa-icon>
                        <h3 class="text-lg font-bold mb-2">Bli medlem i BIM Verdi</h3>
                        <p class="text-sm opacity-90 mb-4">
                            Få tilgang til nettverket og verktøy fra alle medlemsbedrifter.
                        </p>
                        <wa-button variant="neutral" href="<?php echo home_url('/registrer/'); ?>">
                            Registrer deg gratis
                        </wa-button>
                    </div>
                </wa-card>
                <?php endif; ?>
                
            </aside>
        </div>
        
    </div>
</div>

<?php get_footer(); ?>
