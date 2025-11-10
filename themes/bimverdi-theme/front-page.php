<?php
/**
 * Front Page Template
 * 
 * Homepage for BIM Verdi with hero, stats, highlights, and CTA
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<!-- Hero Section -->
<?php
$hero_args = array(
    'title' => 'Velkommen til BIM Verdi (BIM Value)',
    'subtitle' => 'Et fagnettverk som bidrar til bedre og mer effektiv digitalisering av byggenæringen',
    'height' => 'min-h-screen',
    'buttons' => array(
        array(
            'label' => 'Bli medlem',
            'url' => home_url('/registrer/'),
            'class' => 'btn-primary text-white',
        ),
        array(
            'label' => 'Utforsk medlemmer',
            'url' => home_url('/medlemmer/'),
            'class' => 'btn-outline text-white border-white',
        ),
    ),
);
get_template_part('template-parts/hero', null, $hero_args);
?>

<main>
    <!-- Stats Section -->
    <section class="bg-white py-16 border-b border-gray-200">
        <div class="container mx-auto px-4 md:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                
                <div class="text-center">
                    <div class="text-4xl font-bold text-primary mb-2">70+</div>
                    <p class="text-gray-600">Bedrifter</p>
                </div>
                
                <div class="text-center">
                    <div class="text-4xl font-bold text-secondary mb-2">7</div>
                    <p class="text-gray-600">Temagrupper</p>
                </div>
                
                <div class="text-center">
                    <div class="text-4xl font-bold text-primary mb-2">30+</div>
                    <p class="text-gray-600">Pilotprosjekter</p>
                </div>
                
                <div class="text-center">
                    <div class="text-4xl font-bold text-secondary mb-2">50+</div>
                    <p class="text-gray-600">Arrangementer</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Theme Groups Section -->
    <section class="bg-gray-50 py-16">
        <div class="container mx-auto px-4 md:px-8">
            <h2 class="text-4xl font-bold text-center mb-12">Våre Temagrupper</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <?php
                $theme_groups = array(
                    array(
                        'title' => 'Modellkvalitet',
                        'description' => 'Fokus på bestpraksis for BIM-modellering og datakvalitet',
                        'icon' => '📐',
                    ),
                    array(
                        'title' => 'ByggesaksBIM',
                        'description' => 'Digitalisering av byggesaksflyt og offentlig dialog',
                        'icon' => '📋',
                    ),
                    array(
                        'title' => 'ProsjektBIM',
                        'description' => 'BIM i prosjektstyring og samarbeid',
                        'icon' => '🚀',
                    ),
                    array(
                        'title' => 'EiendomsBIM',
                        'description' => 'BIM for eiendomsforvaltning og drift',
                        'icon' => '🏢',
                    ),
                    array(
                        'title' => 'MiljøBIM',
                        'description' => 'BIM for miljø- og bærekraftanalyse',
                        'icon' => '🌱',
                    ),
                    array(
                        'title' => 'BIMtech',
                        'description' => 'Teknologi, API-er og integrasjoner',
                        'icon' => '⚙️',
                    ),
                );
                
                foreach ($theme_groups as $group):
                    ?>
                    <div class="card bg-white shadow-md hover:shadow-lg transition-all">
                        <div class="card-body p-6">
                            <div class="text-4xl mb-4"><?php echo $group['icon']; ?></div>
                            <h3 class="card-title text-xl font-bold mb-2"><?php echo esc_html($group['title']); ?></h3>
                            <p class="text-gray-600 mb-4"><?php echo esc_html($group['description']); ?></p>
                            <a href="<?php echo esc_url(home_url('/temagrupper/' . sanitize_title($group['title']) . '/')); ?>" 
                               class="link link-primary">Lær mer →</a>
                        </div>
                    </div>
                    <?php
                endforeach;
                ?>
            </div>
        </div>
    </section>
    
    <!-- Recent Events Section -->
    <section class="bg-white py-16">
        <div class="container mx-auto px-4 md:px-8">
            <div class="flex justify-between items-center mb-12">
                <h2 class="text-4xl font-bold">Kommende Arrangementer</h2>
                <a href="<?php echo esc_url(home_url('/arrangementer/')); ?>" class="btn btn-outline">Se alle</a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php
                $events = get_posts(array(
                    'post_type' => 'arrangement',
                    'posts_per_page' => 3,
                    'orderby' => 'meta_value',
                    'meta_key' => 'dato',
                    'order' => 'ASC',
                ));
                
                if (!empty($events)):
                    foreach ($events as $event):
                        bimverdi_display_event_card($event->ID);
                    endforeach;
                else:
                    ?>
                    <div class="md:col-span-3 text-center text-gray-600 py-8">
                        <p>Ingen arrangementer planlagt akkurat nå.</p>
                    </div>
                    <?php
                endif;
                ?>
            </div>
        </div>
    </section>
    
    <!-- Call to Action Section -->
    <section class="bg-gradient-to-r from-primary to-secondary py-16 text-white">
        <div class="container mx-auto px-4 md:px-8 text-center">
            <h2 class="text-4xl font-bold mb-4">Klar til å bli medlem?</h2>
            <p class="text-xl mb-8 max-w-2xl mx-auto">
                Bli del av et fagnettverk som jobber for bedre og mer effektiv digitalisering av byggenæringen.
            </p>
            <a href="<?php echo esc_url(home_url('/registrer/')); ?>" class="btn btn-lg bg-white text-primary hover:bg-gray-100">
                Registrer deg nå
            </a>
        </div>
    </section>
    
</main>

<?php get_footer();
