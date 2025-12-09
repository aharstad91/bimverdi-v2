<?php
/**
 * Template Name: Temagrupper - Oversikt
 * 
 * Viser alle 6 temagrupper med deltakertall og lenker til hver gruppe
 * 
 * @package BimVerdi_Theme
 */

get_header();

// Hent alle temagrupper
$temagrupper = get_terms(array(
    'taxonomy' => 'temagruppe',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
));

// Temagruppe-info med farger og ikoner
$temagruppe_info = array(
    'ByggesaksBIM' => array(
        'description' => 'Byggesaksprosesser og kommunal saksbehandling med digitale verktøy',
        'icon' => 'file-signature',
        'gradient' => 'from-blue-600 to-indigo-600',
        'bg_color' => 'bg-blue-100',
        'text_color' => 'text-blue-700',
        'slug' => 'byggesaksbim',
    ),
    'ProsjektBIM' => array(
        'description' => 'Koordinering i design- og byggefase med 3D-modeller og samarbeid',
        'icon' => 'cubes',
        'gradient' => 'from-purple-600 to-pink-600',
        'bg_color' => 'bg-purple-100',
        'text_color' => 'text-purple-700',
        'slug' => 'prosjektbim',
    ),
    'EiendomsBIM' => array(
        'description' => 'Drift, forvaltning og livssyklus-management av eiendom',
        'icon' => 'building',
        'gradient' => 'from-green-600 to-teal-600',
        'bg_color' => 'bg-green-100',
        'text_color' => 'text-green-700',
        'slug' => 'eiendomsbim',
    ),
    'MiljøBIM' => array(
        'description' => 'Miljø- og klimaberegninger, energieffektivitet og bærekraft',
        'icon' => 'leaf',
        'gradient' => 'from-emerald-600 to-green-600',
        'bg_color' => 'bg-emerald-100',
        'text_color' => 'text-emerald-700',
        'slug' => 'miljobim',
    ),
    'SirkBIM' => array(
        'description' => 'Sirkulær økonomi, materialpass og gjenbruk',
        'icon' => 'recycle',
        'gradient' => 'from-teal-600 to-cyan-600',
        'bg_color' => 'bg-teal-100',
        'text_color' => 'text-teal-700',
        'slug' => 'sirkbim',
    ),
    'BIMtech' => array(
        'description' => 'Teknologi, AI, machine learning og nye digitale løsninger',
        'icon' => 'microchip',
        'gradient' => 'from-orange-600 to-red-600',
        'bg_color' => 'bg-orange-100',
        'text_color' => 'text-orange-700',
        'slug' => 'bimtech',
    ),
);

// Sjekk brukerens temagrupper
$user_groups = array();
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $user_company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
    if ($user_company_id) {
        $user_groups = wp_get_post_terms($user_company_id, 'temagruppe', array('fields' => 'ids'));
    }
}

// Tell totalt antall deltakere
$total_deltakere = 0;
?>

<div class="min-h-screen bg-bim-beige-100">
    
    <!-- Hero Header -->
    <div class="bg-gradient-to-r from-orange-500 to-purple-600 text-white py-20">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-5xl font-bold mb-4">Temagrupper</h1>
            <p class="text-xl opacity-95 max-w-2xl mx-auto mb-6">
                BIM Verdi-nettverket er organisert i 6 temagrupper som dekker hele bredden av digital bygg og eiendom. 
                Finn ditt fagfelt og bli med i diskusjonen!
            </p>
            <?php if (is_user_logged_in()): ?>
                <wa-button variant="neutral" size="large" href="<?php echo esc_url(home_url('/min-side/temagrupper/')); ?>">
                    <wa-icon slot="prefix" name="cog" library="fa"></wa-icon>
                    Administrer dine temagrupper
                </wa-button>
            <?php else: ?>
                <wa-button variant="neutral" size="large" href="<?php echo esc_url(home_url('/registrer/')); ?>">
                    <wa-icon slot="prefix" name="user-plus" library="fa"></wa-icon>
                    Bli medlem
                </wa-button>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="container mx-auto px-4 py-12">
        
        <!-- Temagruppe-grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            <?php foreach ($temagrupper as $term):
                $info = $temagruppe_info[$term->name] ?? array(
                    'description' => $term->description ?: 'Ingen beskrivelse',
                    'icon' => 'users',
                    'gradient' => 'from-gray-600 to-gray-700',
                    'bg_color' => 'bg-gray-100',
                    'text_color' => 'text-gray-700',
                    'slug' => $term->slug,
                );
                
                // Tell deltakere
                $deltakere = get_posts(array(
                    'post_type' => 'foretak',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'temagruppe',
                            'field' => 'term_id',
                            'terms' => $term->term_id,
                        ),
                    ),
                ));
                $deltaker_count = count($deltakere);
                $total_deltakere += $deltaker_count;
                
                // Sjekk om bruker er medlem
                $is_member = in_array($term->term_id, $user_groups);
                
                // Hent neste arrangement
                $next_event = get_posts(array(
                    'post_type' => 'arrangement',
                    'posts_per_page' => 1,
                    'post_status' => 'publish',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'temagruppe',
                            'field' => 'term_id',
                            'terms' => $term->term_id,
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
                
                // Generer URL for temagruppe-siden
                $group_url = home_url('/temagruppe-' . $info['slug'] . '/');
            ?>
            
            <a href="<?php echo esc_url($group_url); ?>" class="block group">
                <wa-card class="h-full hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <!-- Gradient header -->
                    <div class="bg-gradient-to-r <?php echo esc_attr($info['gradient']); ?> p-6 text-white">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-14 h-14 rounded-xl bg-white/20 flex items-center justify-center">
                                <wa-icon name="<?php echo esc_attr($info['icon']); ?>" library="fa" style="font-size: 1.75rem;"></wa-icon>
                            </div>
                            <?php if ($is_member): ?>
                                <wa-badge variant="success">
                                    <wa-icon name="check" library="fa"></wa-icon>
                                    Medlem
                                </wa-badge>
                            <?php endif; ?>
                        </div>
                        <h2 class="text-2xl font-bold"><?php echo esc_html($term->name); ?></h2>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-6">
                        <p class="text-gray-600 mb-6"><?php echo esc_html($info['description']); ?></p>
                        
                        <!-- Stats -->
                        <div class="flex items-center justify-between text-sm mb-4">
                            <div class="flex items-center gap-2 text-gray-500">
                                <wa-icon name="building" library="fa"></wa-icon>
                                <span><?php echo $deltaker_count; ?> bedrifter</span>
                            </div>
                            <?php if (!empty($next_event)): 
                                $event_date = get_field('arrangement_dato', $next_event[0]->ID);
                            ?>
                                <div class="flex items-center gap-2 <?php echo esc_attr($info['text_color']); ?>">
                                    <wa-icon name="calendar" library="fa"></wa-icon>
                                    <span><?php echo $event_date ? date_i18n('j. M', strtotime($event_date)) : 'Kommer'; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- CTA -->
                        <div class="flex items-center justify-between">
                            <span class="text-sm <?php echo esc_attr($info['text_color']); ?> font-semibold group-hover:underline">
                                Se temagruppe →
                            </span>
                        </div>
                    </div>
                </wa-card>
            </a>
            
            <?php endforeach; ?>
        </div>
        
        <!-- Info-seksjon -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
            <wa-card>
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                        <wa-icon name="question-circle" library="fa" class="text-orange-600"></wa-icon>
                        Hva er temagrupper?
                    </h3>
                    <p class="text-gray-600 mb-4">
                        Temagruppene er faglige nettverk innen BIM Verdi. Her møtes bedrifter med felles interesser 
                        for å dele erfaringer, diskutere utfordringer og utvikle beste praksis.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2">
                            <wa-icon name="check" library="fa" class="text-green-600"></wa-icon>
                            Jevnlige digitale og fysiske møter
                        </li>
                        <li class="flex items-center gap-2">
                            <wa-icon name="check" library="fa" class="text-green-600"></wa-icon>
                            Deling av caser og erfaringer
                        </li>
                        <li class="flex items-center gap-2">
                            <wa-icon name="check" library="fa" class="text-green-600"></wa-icon>
                            Utvikling av veiledere og ressurser
                        </li>
                        <li class="flex items-center gap-2">
                            <wa-icon name="check" library="fa" class="text-green-600"></wa-icon>
                            Nettverksbygging på tvers av bedrifter
                        </li>
                    </ul>
                </div>
            </wa-card>
            
            <wa-card>
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                        <wa-icon name="user-plus" library="fa" class="text-purple-600"></wa-icon>
                        Hvordan bli med?
                    </h3>
                    <p class="text-gray-600 mb-4">
                        Som BIM Verdi-medlem kan du fritt melde deg på én eller flere temagrupper. 
                        Du vil da motta invitasjoner til arrangementer og få tilgang til gruppens ressurser.
                    </p>
                    
                    <?php if (is_user_logged_in()): ?>
                        <?php if (!empty($user_groups)): ?>
                            <wa-alert variant="success" open>
                                <wa-icon slot="icon" name="circle-check" library="fa"></wa-icon>
                                Du er medlem av <?php echo count($user_groups); ?> temagruppe(r).
                                <a href="<?php echo esc_url(home_url('/min-side/temagrupper/')); ?>" class="underline font-semibold">
                                    Administrer
                                </a>
                            </wa-alert>
                        <?php else: ?>
                            <wa-button variant="brand" href="<?php echo esc_url(home_url('/min-side/temagrupper/')); ?>">
                                <wa-icon slot="prefix" name="cog" library="fa"></wa-icon>
                                Velg dine temagrupper
                            </wa-button>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="flex gap-3">
                            <wa-button variant="brand" href="<?php echo esc_url(home_url('/registrer/')); ?>">
                                <wa-icon slot="prefix" name="user-plus" library="fa"></wa-icon>
                                Bli medlem
                            </wa-button>
                            <wa-button variant="neutral" outline href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">
                                Logg inn
                            </wa-button>
                        </div>
                    <?php endif; ?>
                </div>
            </wa-card>
        </div>
        
    </div>
</div>

<?php get_footer(); ?>
