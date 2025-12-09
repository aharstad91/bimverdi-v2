<?php
/**
 * Template Name: Temagruppe - Dynamisk
 * 
 * Dynamisk temagruppe-side som viser deltakerliste basert på term slug
 * Henter temagruppe fra page slug eller query parameter
 * 
 * @package BimVerdi_Theme
 */

get_header();

// Få temagruppe fra side-slug eller query param
$page_slug = get_post_field('post_name', get_post());
$temagruppe_slug = isset($_GET['gruppe']) ? sanitize_title($_GET['gruppe']) : $page_slug;

// Fjern "temagruppe-" prefix hvis det finnes
$temagruppe_slug = preg_replace('/^temagruppe-/', '', $temagruppe_slug);

// Finn temagruppe term
$temagruppe = get_term_by('slug', $temagruppe_slug, 'temagruppe');

// Fallback: prøv å finne basert på navn
if (!$temagruppe) {
    $temagruppe = get_term_by('name', $temagruppe_slug, 'temagruppe');
}

// Temagruppe-info med farger og ikoner
$temagruppe_info = array(
    'byggesaksbim' => array(
        'name' => 'ByggesaksBIM',
        'description' => 'Fokus på byggesaksprosesser og kommunal saksbehandling med digitale verktøy',
        'long_description' => 'Temagruppen jobber med å effektivisere og digitalisere byggesaksprosessen. Vi ser på hvordan BIM kan brukes til automatisk regelsjekk, digital planformidling og bedre samhandling mellom byggherre, rådgivere og kommuner.',
        'icon' => 'file-signature',
        'gradient' => 'from-blue-600 to-indigo-600',
        'bg_color' => 'bg-blue-50',
        'text_color' => 'text-blue-700',
        'border_color' => 'border-blue-500',
    ),
    'prosjektbim' => array(
        'name' => 'ProsjektBIM',
        'description' => 'Koordinering i design- og byggefase med 3D-modeller og samarbeid',
        'long_description' => 'Fokus på BIM-koordinering gjennom hele prosjektfasen. Vi diskuterer beste praksis for modellkoordinering, kollisjonskontroll, arbeidsflyter og samhandling mellom disipliner.',
        'icon' => 'cubes',
        'gradient' => 'from-purple-600 to-pink-600',
        'bg_color' => 'bg-purple-50',
        'text_color' => 'text-purple-700',
        'border_color' => 'border-purple-500',
    ),
    'eiendomsbim' => array(
        'name' => 'EiendomsBIM',
        'description' => 'Drift, forvaltning og livssyklus-management av eiendom',
        'long_description' => 'Temagruppen fokuserer på hvordan BIM-modeller kan brukes til drift og forvaltning av bygg. Vi utforsker Digital Twin-konsepter, FDV-dokumentasjon og integrasjon med driftssystemer.',
        'icon' => 'building',
        'gradient' => 'from-green-600 to-teal-600',
        'bg_color' => 'bg-green-50',
        'text_color' => 'text-green-700',
        'border_color' => 'border-green-500',
    ),
    'miljobim' => array(
        'name' => 'MiljøBIM',
        'description' => 'Miljø- og klimaberegninger, energieffektivitet og bærekraft',
        'long_description' => 'Fokus på klimagassberegninger, EPD-data, energianalyser og bærekraftig bygging. Vi ser på hvordan BIM kan bidra til å nå klimamål og oppfylle TEK17-krav om klimagassregnskap.',
        'icon' => 'leaf',
        'gradient' => 'from-emerald-600 to-green-600',
        'bg_color' => 'bg-emerald-50',
        'text_color' => 'text-emerald-700',
        'border_color' => 'border-emerald-500',
    ),
    'sirkbim' => array(
        'name' => 'SirkBIM',
        'description' => 'Sirkulær økonomi, materialpass og gjenbruk',
        'long_description' => 'Temagruppen jobber med sirkulær økonomi i byggenæringen. Vi utforsker materialpass, gjenbrukskartlegging, rivningsanalyse og hvordan BIM kan støtte sirkulære byggeprosjekter.',
        'icon' => 'recycle',
        'gradient' => 'from-teal-600 to-cyan-600',
        'bg_color' => 'bg-teal-50',
        'text_color' => 'text-teal-700',
        'border_color' => 'border-teal-500',
    ),
    'bimtech' => array(
        'name' => 'BIMtech',
        'description' => 'Teknologi, AI, machine learning og nye digitale løsninger',
        'long_description' => 'Fokus på ny teknologi innen BIM-feltet. Vi diskuterer AI og maskinlæring, automatisering, skyløsninger, API-integrasjoner og fremtidens digitale verktøy for byggenæringen.',
        'icon' => 'microchip',
        'gradient' => 'from-orange-600 to-red-600',
        'bg_color' => 'bg-orange-50',
        'text_color' => 'text-orange-700',
        'border_color' => 'border-orange-500',
    ),
);

// Normaliser slug for lookup
$lookup_slug = strtolower(str_replace(array('ø', 'æ', 'å'), array('o', 'ae', 'a'), $temagruppe_slug));
$info = $temagruppe_info[$lookup_slug] ?? $temagruppe_info['bimtech']; // Fallback

// Hvis vi ikke finner temagruppen, vis feilmelding
if (!$temagruppe): ?>
<div class="min-h-screen bg-gray-100 flex items-center justify-center">
    <wa-card class="max-w-md text-center p-8">
        <wa-icon name="triangle-exclamation" library="fa" style="font-size: 3rem; color: var(--wa-color-warning-600);"></wa-icon>
        <h1 class="text-2xl font-bold text-gray-900 mt-4 mb-2">Temagruppe ikke funnet</h1>
        <p class="text-gray-600 mb-6">Vi fant ikke temagruppen "<?php echo esc_html($temagruppe_slug); ?>".</p>
        <wa-button variant="brand" href="<?php echo home_url('/temagrupper/'); ?>">
            Se alle temagrupper
        </wa-button>
    </wa-card>
</div>
<?php 
get_footer();
return;
endif;

// Hent deltakende bedrifter
$deltakere = get_posts(array(
    'post_type' => 'foretak',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'tax_query' => array(
        array(
            'taxonomy' => 'temagruppe',
            'field' => 'term_id',
            'terms' => $temagruppe->term_id,
        ),
    ),
    'orderby' => 'title',
    'order' => 'ASC',
));
$deltaker_count = count($deltakere);

// Hent kommende arrangementer for denne temagruppen
$arrangementer = get_posts(array(
    'post_type' => 'arrangement',
    'posts_per_page' => 3,
    'post_status' => 'publish',
    'tax_query' => array(
        array(
            'taxonomy' => 'temagruppe',
            'field' => 'term_id',
            'terms' => $temagruppe->term_id,
        ),
    ),
    'meta_key' => 'arrangement_dato',
    'orderby' => 'meta_value',
    'order' => 'ASC',
    'meta_query' => array(
        array(
            'key' => 'arrangement_dato',
            'value' => date('Ymd'),
            'compare' => '>=',
            'type' => 'DATE',
        ),
    ),
));

// Sjekk om innlogget bruker er medlem av denne gruppen
$user_is_member = false;
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $user_company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
    if ($user_company_id) {
        $user_groups = wp_get_post_terms($user_company_id, 'temagruppe', array('fields' => 'ids'));
        $user_is_member = in_array($temagruppe->term_id, $user_groups);
    }
}
?>

<div class="min-h-screen bg-bim-beige-100">
    
    <!-- Hero Header -->
    <div class="bg-gradient-to-r <?php echo esc_attr($info['gradient']); ?> text-white py-16">
        <div class="container mx-auto px-4">
            <div class="flex items-center gap-3 mb-4">
                <a href="<?php echo esc_url(home_url('/temagrupper/')); ?>" class="text-white/80 hover:text-white text-sm flex items-center gap-2">
                    <wa-icon name="arrow-left" library="fa"></wa-icon>
                    Tilbake til temagrupper
                </a>
            </div>
            <div class="flex items-center justify-between flex-wrap gap-6">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="bg-white/20 px-4 py-2 rounded-full text-sm font-bold flex items-center gap-2">
                            <wa-icon name="<?php echo esc_attr($info['icon']); ?>" library="fa"></wa-icon>
                            TEMAGRUPPE
                        </span>
                        <?php if ($user_is_member): ?>
                            <wa-badge variant="success">Du er medlem</wa-badge>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-5xl font-bold mb-4"><?php echo esc_html($temagruppe->name); ?></h1>
                    <p class="text-xl opacity-95"><?php echo esc_html($info['description']); ?></p>
                </div>
                <div class="text-center bg-white/10 rounded-xl p-6">
                    <div class="text-4xl font-bold"><?php echo $deltaker_count; ?></div>
                    <div class="text-sm opacity-80">deltakende bedrifter</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container mx-auto px-4 py-12">
        
        <!-- Om temagruppen -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
            <div class="lg:col-span-2">
                <wa-card>
                    <div class="p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                            <wa-icon name="info-circle" library="fa" class="<?php echo esc_attr($info['text_color']); ?>"></wa-icon>
                            Om temagruppen
                        </h2>
                        <p class="text-gray-700 leading-relaxed mb-6"><?php echo esc_html($info['long_description']); ?></p>
                        
                        <?php if (!$user_is_member && is_user_logged_in()): ?>
                            <wa-button variant="brand" href="<?php echo esc_url(home_url('/min-side/temagrupper/')); ?>">
                                <wa-icon slot="prefix" name="user-plus" library="fa"></wa-icon>
                                Bli med i denne gruppen
                            </wa-button>
                        <?php elseif (!is_user_logged_in()): ?>
                            <wa-button variant="brand" href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">
                                <wa-icon slot="prefix" name="right-to-bracket" library="fa"></wa-icon>
                                Logg inn for å bli medlem
                            </wa-button>
                        <?php else: ?>
                            <wa-alert variant="success" open>
                                <wa-icon slot="icon" name="circle-check" library="fa"></wa-icon>
                                Ditt foretak er medlem av denne temagruppen. Du vil motta invitasjoner til møter og arrangementer.
                            </wa-alert>
                        <?php endif; ?>
                    </div>
                </wa-card>
            </div>
            
            <!-- Neste arrangement -->
            <div>
                <wa-card>
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <wa-icon name="calendar" library="fa" class="<?php echo esc_attr($info['text_color']); ?>"></wa-icon>
                            Neste arrangement
                        </h3>
                        <?php if (!empty($arrangementer)): 
                            $next = $arrangementer[0];
                            $dato = get_field('arrangement_dato', $next->ID);
                            $tid = get_field('arrangement_tid', $next->ID);
                        ?>
                            <div class="<?php echo esc_attr($info['bg_color']); ?> rounded-lg p-4 mb-4">
                                <div class="text-2xl font-bold <?php echo esc_attr($info['text_color']); ?> mb-1">
                                    <?php echo $dato ? date_i18n('j. F Y', strtotime($dato)) : 'Dato kommer'; ?>
                                </div>
                                <div class="text-gray-700"><?php echo $tid ? esc_html($tid) : ''; ?></div>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-2"><?php echo esc_html($next->post_title); ?></h4>
                            <p class="text-sm text-gray-600 mb-4"><?php echo wp_trim_words(get_the_excerpt($next), 15); ?></p>
                            <wa-button variant="brand" size="small" href="<?php echo get_permalink($next); ?>">
                                Se arrangement
                            </wa-button>
                        <?php else: ?>
                            <div class="text-gray-500 text-center py-4">
                                <wa-icon name="calendar-xmark" library="fa" style="font-size: 2rem; opacity: 0.5;"></wa-icon>
                                <p class="mt-2">Ingen planlagte arrangementer</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </wa-card>
            </div>
        </div>
        
        <!-- Deltakende bedrifter -->
        <div class="mb-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                    <wa-icon name="building" library="fa" class="<?php echo esc_attr($info['text_color']); ?>"></wa-icon>
                    Deltakende bedrifter
                </h2>
                <span class="text-gray-500"><?php echo $deltaker_count; ?> bedrifter</span>
            </div>
            
            <?php if (!empty($deltakere)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($deltakere as $bedrift): 
                        $logo = get_field('firma_logo', $bedrift->ID);
                        $bransje = get_the_terms($bedrift->ID, 'bransjekategori');
                        $website = get_field('nettside', $bedrift->ID);
                    ?>
                        <wa-card class="hover:shadow-lg transition-shadow">
                            <div class="p-5">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0">
                                        <?php if ($logo): ?>
                                            <img src="<?php echo esc_url($logo['sizes']['thumbnail']); ?>" 
                                                 alt="<?php echo esc_attr($bedrift->post_title); ?>" 
                                                 class="w-16 h-16 object-contain rounded-lg bg-gray-100">
                                        <?php else: ?>
                                            <div class="w-16 h-16 rounded-lg <?php echo esc_attr($info['bg_color']); ?> flex items-center justify-center">
                                                <wa-icon name="building" library="fa" class="<?php echo esc_attr($info['text_color']); ?>" style="font-size: 1.5rem;"></wa-icon>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow">
                                        <h3 class="font-bold text-gray-900 mb-1">
                                            <a href="<?php echo get_permalink($bedrift); ?>" class="hover:text-orange-600">
                                                <?php echo esc_html($bedrift->post_title); ?>
                                            </a>
                                        </h3>
                                        <?php if ($bransje && !is_wp_error($bransje)): ?>
                                            <div class="text-sm text-gray-600 mb-2">
                                                <?php echo esc_html($bransje[0]->name); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex gap-2">
                                            <wa-button size="small" variant="neutral" outline href="<?php echo get_permalink($bedrift); ?>">
                                                Se profil
                                            </wa-button>
                                            <?php if ($website): ?>
                                                <wa-button size="small" variant="neutral" outline href="<?php echo esc_url($website); ?>" target="_blank">
                                                    <wa-icon name="external-link" library="fa"></wa-icon>
                                                </wa-button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </wa-card>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <wa-card class="text-center py-12">
                    <wa-icon name="users" library="fa" style="font-size: 3rem; color: var(--wa-color-neutral-300);"></wa-icon>
                    <h3 class="text-xl font-bold text-gray-900 mt-4 mb-2">Ingen deltakere ennå</h3>
                    <p class="text-gray-600 mb-6">Bli den første bedriften som melder seg på denne temagruppen!</p>
                    <?php if (is_user_logged_in()): ?>
                        <wa-button variant="brand" href="<?php echo esc_url(home_url('/min-side/temagrupper/')); ?>">
                            <wa-icon slot="prefix" name="user-plus" library="fa"></wa-icon>
                            Meld deg på
                        </wa-button>
                    <?php else: ?>
                        <wa-button variant="brand" href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">
                            Logg inn for å delta
                        </wa-button>
                    <?php endif; ?>
                </wa-card>
            <?php endif; ?>
        </div>
        
        <!-- Alle arrangementer -->
        <?php if (count($arrangementer) > 1): ?>
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                <wa-icon name="calendar-days" library="fa" class="<?php echo esc_attr($info['text_color']); ?>"></wa-icon>
                Kommende arrangementer
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($arrangementer as $arr): 
                    $dato = get_field('arrangement_dato', $arr->ID);
                ?>
                    <wa-card class="hover:shadow-lg transition-shadow">
                        <div class="p-5">
                            <div class="text-sm <?php echo esc_attr($info['text_color']); ?> font-semibold mb-2">
                                <?php echo $dato ? date_i18n('j. F Y', strtotime($dato)) : 'Dato kommer'; ?>
                            </div>
                            <h3 class="font-bold text-gray-900 mb-2"><?php echo esc_html($arr->post_title); ?></h3>
                            <p class="text-sm text-gray-600 mb-4"><?php echo wp_trim_words(get_the_excerpt($arr), 20); ?></p>
                            <wa-button size="small" variant="brand" outline href="<?php echo get_permalink($arr); ?>">
                                Les mer
                            </wa-button>
                        </div>
                    </wa-card>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- CTA for å bli medlem -->
        <?php if (!$user_is_member): ?>
        <wa-card class="bg-gradient-to-r <?php echo esc_attr($info['gradient']); ?> text-white">
            <div class="p-8 text-center">
                <h2 class="text-3xl font-bold mb-4">Bli med i <?php echo esc_html($temagruppe->name); ?>!</h2>
                <p class="text-lg opacity-90 mb-6 max-w-2xl mx-auto">
                    Delta i diskusjoner, møt andre bedrifter med samme interesser, og få tilgang til eksklusive arrangementer og ressurser.
                </p>
                <?php if (is_user_logged_in()): ?>
                    <wa-button variant="neutral" size="large" href="<?php echo esc_url(home_url('/min-side/temagrupper/')); ?>">
                        <wa-icon slot="prefix" name="user-plus" library="fa"></wa-icon>
                        Meld deg på nå
                    </wa-button>
                <?php else: ?>
                    <wa-button variant="neutral" size="large" href="<?php echo esc_url(home_url('/registrer/')); ?>">
                        <wa-icon slot="prefix" name="user-plus" library="fa"></wa-icon>
                        Opprett konto for å delta
                    </wa-button>
                <?php endif; ?>
            </div>
        </wa-card>
        <?php endif; ?>
        
    </div>
</div>

<?php get_footer(); ?>
