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
    'title' => 'Velkommen til BIM Verdi',
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
    
    <!-- Demo: Semantic Concept System -->
    <section class="bg-gradient-to-br from-blue-50 to-purple-50 py-20 border-t-4 border-blue-500">
        <div class="container mx-auto px-4 md:px-8">
            <div class="text-center mb-12">
                <div class="inline-block bg-blue-500 text-white px-4 py-2 rounded-full text-sm font-bold mb-4">
                    🚀 NYT: Semantisk Begrepssystem
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-4 text-gray-900">
                    Finn Alt Du Trenger på Ett Sted
                </h2>
                <p class="text-xl text-gray-700 max-w-3xl mx-auto">
                    Søk på "TEK17" og få både lovtekst fra Lovdata, praktiske guider, medlems-cases og relevante verktøy – alt koblet sammen.
                </p>
            </div>
            
            <!-- Live Search Demo -->
            <div class="max-w-4xl mx-auto mb-16">
                <div class="bg-white rounded-lg shadow-2xl p-8">
                    <div class="flex gap-3 mb-6">
                        <input type="text" 
                               value="TEK17" 
                               readonly
                               class="flex-1 px-6 py-4 text-lg border-2 border-blue-300 rounded-lg bg-blue-50 font-semibold text-blue-900">
                        <button class="px-8 py-4 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition-colors">
                            Søk
                        </button>
                    </div>
                    
                    <!-- Search Results Preview -->
                    <div class="space-y-6">
                        <!-- Begrep Result -->
                        <div>
                            <h3 class="text-sm font-bold text-blue-600 uppercase mb-3">📚 Begreper (1)</h3>
                            <a href="#" class="block p-4 bg-gray-50 border-l-4 border-blue-500 rounded hover:bg-blue-50 transition-colors">
                                <div class="font-bold text-blue-700 mb-1">TEK17 – Tekniske krav til byggverk</div>
                                <div class="text-sm text-gray-600">Norges gjeldende byggenorm med krav til energi, sikkerhet og klima</div>
                            </a>
                        </div>
                        
                        <!-- Sources Result -->
                        <div>
                            <h3 class="text-sm font-bold text-blue-600 uppercase mb-3">⚖️ Kilder (3)</h3>
                            <div class="space-y-2">
                                <a href="#" class="block p-3 bg-gray-50 border-l-4 border-green-500 rounded hover:bg-green-50 transition-colors">
                                    <div class="font-bold text-green-700 mb-1">TEK17 §17-1 – Klimagassdeklarasjon</div>
                                    <div class="text-sm text-gray-600"><span class="bg-green-100 px-2 py-1 rounded text-xs mr-2">Lovdata</span>Miljø & Klima</div>
                                </a>
                                <a href="#" class="block p-3 bg-gray-50 border-l-4 border-green-500 rounded hover:bg-green-50 transition-colors">
                                    <div class="font-bold text-green-700 mb-1">NS 3451 – BIM samarbeid</div>
                                    <div class="text-sm text-gray-600"><span class="bg-blue-100 px-2 py-1 rounded text-xs mr-2">Standard</span>Kobling til TEK17</div>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Articles Result -->
                        <div>
                            <h3 class="text-sm font-bold text-blue-600 uppercase mb-3">📰 Artikler (2)</h3>
                            <div class="space-y-2">
                                <a href="#" class="block p-3 bg-gray-50 border-l-4 border-purple-500 rounded hover:bg-purple-50 transition-colors">
                                    <div class="font-bold text-purple-700 mb-1">Slik implementerte vi TEK17 i Ferner Bryggen</div>
                                    <div class="text-sm text-gray-600">Case fra Visjon Arkitekter – klimagassreduksjon på 23%</div>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Tools Result -->
                        <div>
                            <h3 class="text-sm font-bold text-blue-600 uppercase mb-3">🔧 Verktøy (2)</h3>
                            <div class="space-y-2">
                                <a href="#" class="block p-3 bg-gray-50 border-l-4 border-orange-500 rounded hover:bg-orange-50 transition-colors">
                                    <div class="font-bold text-orange-700 mb-1">Revit – BIM Authoring</div>
                                    <div class="text-sm text-gray-600">Med TEK17-validering – brukt av 45 medlemmer</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Feature Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="text-5xl mb-4">🔗</div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">Auto-linking i Artikler</h3>
                    <p class="text-gray-600 mb-4">Når medlemmer skriver "TEK17" i en artikkel, lager systemet automatisk lenke til begrep-siden.</p>
                    <div class="text-sm text-gray-500 italic">Ingen manuelt arbeid nødvendig</div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="text-5xl mb-4">⚡</div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">Lovdata API Integrering</h3>
                    <p class="text-gray-600 mb-4">Offisielle lovtekster hentes automatisk og holdes oppdatert fra Lovdata sitt API.</p>
                    <div class="text-sm text-gray-500 italic">Alltid korrekt og oppdatert</div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="text-5xl mb-4">🎯</div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">Smart Kontekst</h3>
                    <p class="text-gray-600 mb-4">Se hvilke verktøy, cases, arrangementer og eksperter som er koblet til hvert begrep.</p>
                    <div class="text-sm text-gray-500 italic">Alt på ett sted</div>
                </div>
            </div>
            
            <!-- Example Concept Page Preview -->
            <div class="max-w-5xl mx-auto">
                <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-8">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-3xl font-bold">TEK17</h3>
                            <span class="bg-white/20 px-4 py-2 rounded-full text-sm">Forskrift</span>
                        </div>
                        <p class="text-lg opacity-95">Tekniske krav til byggverk – Norges gjeldende byggenorm</p>
                    </div>
                    
                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div>
                                <h4 class="text-sm font-bold text-gray-500 uppercase mb-3">📚 Relaterte Begreper</h4>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2 p-3 bg-gray-50 rounded">
                                        <span class="text-blue-600">→</span>
                                        <span class="font-semibold">Energieffektivitet</span>
                                    </div>
                                    <div class="flex items-center gap-2 p-3 bg-gray-50 rounded">
                                        <span class="text-blue-600">→</span>
                                        <span class="font-semibold">Klimagassberegning</span>
                                    </div>
                                    <div class="flex items-center gap-2 p-3 bg-gray-50 rounded">
                                        <span class="text-blue-600">→</span>
                                        <span class="font-semibold">IFC Standard</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="text-sm font-bold text-gray-500 uppercase mb-3">🔧 Verktøy & Praksis</h4>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2 p-3 bg-gray-50 rounded">
                                        <span class="text-orange-600">→</span>
                                        <span class="font-semibold">Revit</span>
                                        <span class="text-xs text-gray-500 ml-auto">45 brukere</span>
                                    </div>
                                    <div class="flex items-center gap-2 p-3 bg-gray-50 rounded">
                                        <span class="text-orange-600">→</span>
                                        <span class="font-semibold">Catenda</span>
                                        <span class="text-xs text-gray-500 ml-auto">23 brukere</span>
                                    </div>
                                    <div class="flex items-center gap-2 p-3 bg-purple-50 rounded">
                                        <span class="text-purple-600">→</span>
                                        <span class="font-semibold">3 Cases fra medlemmer</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border-t pt-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">👥</span>
                                    <div>
                                        <div class="font-bold">23 medlemmer</div>
                                        <div class="text-sm text-gray-600">jobber aktivt med TEK17</div>
                                    </div>
                                </div>
                                <button class="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                                    Utforsk begrep →
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Value Proposition -->
            <div class="mt-16 text-center">
                <div class="inline-block bg-white rounded-lg shadow-xl p-8 max-w-3xl">
                    <h3 class="text-2xl font-bold mb-4 text-gray-900">Hvorfor dette er viktig</h3>
                    <p class="text-lg text-gray-700 leading-relaxed mb-6">
                        I dag må medlemmer søke på mange steder for å finne både <strong>lovtekster</strong>, 
                        <strong>praktiske erfaringer</strong>, <strong>verktøy</strong> og <strong>eksperter</strong>. 
                        Med det semantiske begrepssystemet samler vi alt på ett sted – automatisk koblet sammen.
                    </p>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-3xl font-bold text-blue-600">1 søk</div>
                            <div class="text-sm text-gray-600">Istedenfor 5-10</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-green-600">Auto-oppdatert</div>
                            <div class="text-sm text-gray-600">Fra Lovdata API</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-purple-600">Smart kobling</div>
                            <div class="text-sm text-gray-600">Alt relevant samlet</div>
                        </div>
                    </div>
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
