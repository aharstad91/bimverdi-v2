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
<section class="bg-white text-gray-900 pt-16 pb-20">
    <div class="container mx-auto px-4 md:px-8">
        <div class="max-w-4xl mx-auto text-center">

            <!-- Badge -->
            <div class="mb-6">
                <span class="inline-block bg-gray-100 px-4 py-2 rounded-full text-sm font-semibold text-gray-700">
                    NORGES LEDENDE VERDINETTVERK FOR PRAKTISK BRUK AV BIM
                </span>
            </div>

            <!-- Main Headline -->
            <h1 class="text-5xl md:text-6xl font-bold mb-8 leading-tight">
                Kunnskap. Nettverk.<br>Innovasjon. Markedsmuligheter.
            </h1>

            <!-- Subtitle -->
            <p class="text-lg md:text-xl text-gray-700 mb-12 max-w-2xl mx-auto leading-relaxed">
                Vi kobler bransjen sammen med oppdatert kunnskap, erfaringer, verktøy og ekspertise fra 3 ågebrukes i fagnettverk.
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-wrap gap-4 justify-center mb-16">
                <a href="<?php echo esc_url(home_url('/registrer/')); ?>" class="px-8 py-3 bg-black text-white rounded font-semibold hover:bg-gray-800 transition-colors">
                    Utforsk begreper
                </a>
                <a href="<?php echo esc_url(home_url('/min-side/')); ?>" class="px-8 py-3 bg-gray-200 text-black rounded font-semibold hover:bg-gray-300 transition-colors">
                    Se medlemmer
                </a>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 pt-12 border-t border-gray-200">

                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-gray-900 mb-2">70+</div>
                    <p class="text-sm text-gray-600 font-semibold">MEDLEMSBEDRIFTER</p>
                </div>

                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-gray-900 mb-2">7</div>
                    <p class="text-sm text-gray-600 font-semibold">TEMAGRUPPER</p>
                </div>

                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-gray-900 mb-2">200+</div>
                    <p class="text-sm text-gray-600 font-semibold">BEGREPER</p>
                </div>

                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-gray-900 mb-2">50+</div>
                    <p class="text-sm text-gray-600 font-semibold">ARRANGEMENTER</p>
                </div>

            </div>

        </div>
    </div>
</section>

<main>

    <!-- Finn Alt Du Trenger Section -->
    <section class="bg-gray-50 py-20 border-t border-gray-200">
        <div class="container mx-auto px-4 md:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-6 text-gray-900">
                    Finn Alt Du Trenger på Ett Sted
                </h2>
                <p class="text-xl text-gray-700 max-w-3xl mx-auto">
                    Søk på "TEK17" og få både lovtekst fra Lovdata, praktiske guider, medlems-cases og relevante verktøy – alt koblet sammen.
                </p>
            </div>

            <!-- Live Search Demo -->
            <div class="max-w-4xl mx-auto mb-16">
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <div class="flex gap-3 mb-6">
                        <input type="text"
                               value="TEK17"
                               readonly
                               class="flex-1 px-6 py-4 text-lg border border-gray-300 rounded bg-gray-50 font-semibold text-gray-900">
                        <button class="px-8 py-4 bg-black text-white rounded font-bold hover:bg-gray-800 transition-colors">
                            Søk
                        </button>
                    </div>

                    <!-- Search Results Preview -->
                    <div class="space-y-4">
                        <!-- Results section -->
                        <div class="text-sm font-bold text-gray-600 uppercase mb-4">Søkeresultater</div>

                        <!-- Result Items -->
                        <a href="#" class="block p-4 bg-gray-50 border-l-4 border-gray-900 rounded hover:bg-gray-100 transition-colors">
                            <div class="font-bold text-gray-900 mb-1">TEK17 – Tekniske krav til byggverk</div>
                            <div class="text-sm text-gray-600">Norges gjeldende byggenorm med krav til energi, sikkerhet og klima</div>
                        </a>

                        <a href="#" class="block p-4 bg-gray-50 border-l-4 border-gray-400 rounded hover:bg-gray-100 transition-colors">
                            <div class="font-bold text-gray-900 mb-1">NS 3451 – BIM samarbeid</div>
                            <div class="text-sm text-gray-600">Standard for BIM samarbeid – kobling til TEK17</div>
                        </a>

                        <a href="#" class="block p-4 bg-gray-50 border-l-4 border-gray-400 rounded hover:bg-gray-100 transition-colors">
                            <div class="font-bold text-gray-900 mb-1">Slik implementerte vi TEK17 i Ferner Bryggen</div>
                            <div class="text-sm text-gray-600">Case fra Visjon Arkitekter – klimagassreduksjon på 23%</div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Feature Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <div class="text-5xl mb-4">🔗</div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">Auto-linking i Artikler</h3>
                    <p class="text-gray-600 text-sm">Når medlemmer skriver "TEK17" i en artikkel, lager systemet automatisk lenke til begrep-siden.</p>
                </div>

                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <div class="text-5xl mb-4">⚡</div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">Lovdata API Integrering</h3>
                    <p class="text-gray-600 text-sm">Offisielle lovtekster hentes automatisk og holdes oppdatert fra Lovdata sitt API.</p>
                </div>

                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <div class="text-5xl mb-4">🎯</div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">Smart Kontekst</h3>
                    <p class="text-gray-600 text-sm">Se hvilke verktøy, cases, arrangementer og eksperter som er koblet til hvert begrep.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Hvorfor Dette Er Viktig Section -->
    <section class="bg-white py-20 border-t border-gray-200">
        <div class="container mx-auto px-4 md:px-8">
            <div class="max-w-3xl mx-auto">
                <h2 class="text-4xl font-bold mb-8 text-gray-900">Hvorfor dette er viktig</h2>

                <p class="text-lg text-gray-700 mb-8 leading-relaxed">
                    Vi visualiserer semantikk-relasjoner mellom TEKST19, IFC-standarden, praktisk læring fra medlemmer og markedsmuligheter. Alt koblet gjennom enkle søk fra samme sted.
                </p>

                <div class="grid grid-cols-3 gap-6 mb-8">
                    <div class="text-center p-4">
                        <div class="text-3xl font-bold text-gray-900 mb-2">1 søk</div>
                        <div class="text-sm text-gray-600">Istedenfor 5-10</div>
                    </div>
                    <div class="text-center p-4">
                        <div class="text-3xl font-bold text-gray-900 mb-2">Auto-oppdatert</div>
                        <div class="text-sm text-gray-600">Fra Lovdata API</div>
                    </div>
                    <div class="text-center p-4">
                        <div class="text-3xl font-bold text-gray-900 mb-2">Smart kobling</div>
                        <div class="text-sm text-gray-600">Alt relevant samlet</div>
                    </div>
                </div>

                <a href="<?php echo esc_url(home_url('/registrer/')); ?>" class="inline-block px-8 py-3 bg-black text-white rounded font-semibold hover:bg-gray-800 transition-colors">
                    Registrer deg nå
                </a>
            </div>
        </div>
    </section>

    <!-- Theme Groups Section -->
    <section class="bg-white py-20 border-t border-gray-200">
        <div class="container mx-auto px-4 md:px-8">
            <h2 class="text-4xl font-bold mb-12 text-gray-900">Våre Temagrupper</h2>

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
                    <div class="bg-gray-50 rounded-lg p-6 hover:shadow-lg transition-all">
                        <div class="text-4xl mb-4"><?php echo $group['icon']; ?></div>
                        <h3 class="text-xl font-bold mb-3 text-gray-900"><?php echo esc_html($group['title']); ?></h3>
                        <p class="text-gray-600 text-sm mb-4"><?php echo esc_html($group['description']); ?></p>
                        <a href="<?php echo esc_url(home_url('/temagrupper/' . sanitize_title($group['title']) . '/')); ?>"
                           class="text-black font-semibold hover:underline">Les mer →</a>
                    </div>
                    <?php
                endforeach;
                ?>
            </div>
        </div>
    </section>

    <!-- Recent Events Section -->
    <section class="bg-gray-50 py-20 border-t border-gray-200">
        <div class="container mx-auto px-4 md:px-8">
            <div class="flex justify-between items-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900">Kommende Arrangementer</h2>
                <a href="<?php echo esc_url(home_url('/arrangementer/')); ?>" class="text-black font-semibold hover:underline">Se alle</a>
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
                        // Simple event display
                        $event_date = get_field('dato', $event->ID) ?: date('d.m.Y', strtotime($event->post_date));
                        $event_type = wp_get_post_terms($event->ID, 'arrangementtype', array('fields' => 'names'));
                        ?>
                        <div class="bg-white rounded-lg p-6 border border-gray-200 hover:shadow-lg transition-all">
                            <div class="flex justify-between items-start mb-3">
                                <div class="text-sm text-gray-600 font-semibold"><?php echo date('d. M', strtotime($event_date)); ?></div>
                                <?php if (!empty($event_type)): ?>
                                    <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded"><?php echo esc_html($event_type[0]); ?></span>
                                <?php endif; ?>
                            </div>
                            <h3 class="font-bold text-gray-900 mb-2"><?php echo esc_html($event->post_title); ?></h3>
                            <p class="text-sm text-gray-600"><?php echo wp_trim_words($event->post_excerpt ?: $event->post_content, 15); ?></p>
                            <a href="<?php echo esc_url(get_permalink($event)); ?>" class="text-black font-semibold text-sm mt-4 inline-block hover:underline">
                                Meld deg på →
                            </a>
                        </div>
                        <?php
                    endforeach;
                else:
                    ?>
                    <div class="md:col-span-3 text-center text-gray-600 py-12">
                        <p>Ingen arrangementer planlagt akkurat nå.</p>
                    </div>
                    <?php
                endif;
                ?>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="bg-white py-20 border-t border-gray-200">
        <div class="container mx-auto px-4 md:px-8 text-center">
            <h2 class="text-4xl font-bold mb-4 text-gray-900">Klar til å bli medlem?</h2>
            <p class="text-lg text-gray-700 mb-8 max-w-2xl mx-auto">
                Bli del av et fagnettverk som jobber for bedre og mer effektiv digitalisering av byggenæringen.
            </p>
            <a href="<?php echo esc_url(home_url('/registrer/')); ?>" class="inline-block px-8 py-3 bg-black text-white rounded font-semibold hover:bg-gray-800 transition-colors">
                Registrer deg nå
            </a>
        </div>
    </section>

</main>

<?php get_footer();
