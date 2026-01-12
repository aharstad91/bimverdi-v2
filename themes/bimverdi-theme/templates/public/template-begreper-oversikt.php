<?php
/**
 * Template Name: Begreper Oversikt
 * 
 * Oversikt over alle begreper i BIM Verdi
 * Semantisk begrepskatalog med søk og filtrering
 * 
 * @package BimVerdi_Theme
 */

get_header();
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-purple-50 py-12">
    <div class="container mx-auto px-4">
        
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="inline-block bg-blue-500 text-white px-4 py-2 rounded-full text-sm font-bold mb-4">
                📚 Begrepskatalog
            </div>
            <h1 class="text-5xl font-bold text-gray-900 mb-4">
                BIM Verdi Begreper
            </h1>
            <p class="text-xl text-gray-700 max-w-3xl mx-auto">
                Utforsk forskrifter, standarder, fagtermer og verktøy – alt koblet til praktiske erfaringer og eksperter i nettverket.
            </p>
        </div>
        
        <!-- Search & Filter -->
        <div class="max-w-4xl mx-auto mb-12">
            <div class="bg-white rounded-lg shadow-xl p-6">
                <div class="flex gap-3 mb-4">
                    <input type="text" 
                           placeholder="Søk etter begreper..." 
                           class="flex-1 px-6 py-4 text-lg border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none">
                    <button class="px-8 py-4 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition-colors">
                        Søk
                    </button>
                </div>
                
                <!-- Filter Tabs -->
                <div class="flex gap-2 flex-wrap">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-full text-sm font-semibold">
                        Alle (47)
                    </button>
                    <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-full text-sm font-semibold hover:bg-gray-300">
                        Forskrifter (12)
                    </button>
                    <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-full text-sm font-semibold hover:bg-gray-300">
                        Standarder (15)
                    </button>
                    <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-full text-sm font-semibold hover:bg-gray-300">
                        Fagtermer (14)
                    </button>
                    <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-full text-sm font-semibold hover:bg-gray-300">
                        Verktøy (6)
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Featured Concepts -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">⭐ Mest søkte begreper</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- TEK17 -->
                <a href="#" class="block bg-white rounded-lg shadow-lg hover:shadow-2xl transition-all p-6 border-l-4 border-blue-500">
                    <div class="flex items-start justify-between mb-3">
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-bold">FORSKRIFT</span>
                        <span class="text-gray-500 text-sm">🔥 156 visninger</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">TEK17</h3>
                    <p class="text-gray-600 text-sm mb-4">Tekniske krav til byggverk – Norges gjeldende byggenorm</p>
                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span>📝 8 artikler</span>
                        <span>🔧 5 verktøy</span>
                        <span>👥 23 medlemmer</span>
                    </div>
                </a>
                
                <!-- IFC -->
                <a href="#" class="block bg-white rounded-lg shadow-lg hover:shadow-2xl transition-all p-6 border-l-4 border-green-500">
                    <div class="flex items-start justify-between mb-3">
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-bold">STANDARD</span>
                        <span class="text-gray-500 text-sm">🔥 142 visninger</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">IFC</h3>
                    <p class="text-gray-600 text-sm mb-4">Industry Foundation Classes – åpent format for BIM-data</p>
                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span>📝 12 artikler</span>
                        <span>🔧 8 verktøy</span>
                        <span>👥 45 medlemmer</span>
                    </div>
                </a>
                
                <!-- BIM-koordinering -->
                <a href="#" class="block bg-white rounded-lg shadow-lg hover:shadow-2xl transition-all p-6 border-l-4 border-purple-500">
                    <div class="flex items-start justify-between mb-3">
                        <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-xs font-bold">FAGTERM</span>
                        <span class="text-gray-500 text-sm">🔥 128 visninger</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">BIM-koordinering</h3>
                    <p class="text-gray-600 text-sm mb-4">Prosess for å sikre konsistens mellom BIM-modeller</p>
                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span>📝 6 artikler</span>
                        <span>🔧 4 verktøy</span>
                        <span>👥 34 medlemmer</span>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- All Concepts by Category -->
        
        <!-- Forskrifter -->
        <div class="mb-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">⚖️ Forskrifter & Regelverk</h2>
                <span class="text-gray-500">12 begreper</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                
                <?php
                $forskrifter = array(
                    array('title' => 'TEK17', 'desc' => 'Teknisk forskrift', 'articles' => 8, 'members' => 23),
                    array('title' => 'Plan- og bygningsloven', 'desc' => 'Hovedlov for planlegging og bygging', 'articles' => 5, 'members' => 18),
                    array('title' => 'SAK10', 'desc' => 'Byggesaksforskriften', 'articles' => 4, 'members' => 15),
                    array('title' => 'Produktforskriften', 'desc' => 'Krav til byggevarer', 'articles' => 3, 'members' => 12),
                    array('title' => 'Brannvernloven', 'desc' => 'Brann- og eksplosjonsvernloven', 'articles' => 2, 'members' => 9),
                    array('title' => 'GDPR', 'desc' => 'Personvernforordningen', 'articles' => 3, 'members' => 11),
                );
                
                foreach ($forskrifter as $item):
                ?>
                <a href="#" class="block bg-white rounded-lg shadow hover:shadow-lg transition-all p-4 border-l-4 border-blue-500">
                    <h3 class="font-bold text-gray-900 mb-1"><?php echo esc_html($item['title']); ?></h3>
                    <p class="text-sm text-gray-600 mb-3"><?php echo esc_html($item['desc']); ?></p>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span>📝 <?php echo $item['articles']; ?></span>
                        <span>👥 <?php echo $item['members']; ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Standarder -->
        <div class="mb-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">📐 Standarder</h2>
                <span class="text-gray-500">15 begreper</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                
                <?php
                $standarder = array(
                    array('title' => 'NS 3940', 'desc' => 'Areal- og volumberegninger', 'articles' => 7, 'members' => 28),
                    array('title' => 'NS 8405', 'desc' => 'Norsk bygge- og anleggskontrakt', 'articles' => 5, 'members' => 22),
                    array('title' => 'IFC', 'desc' => 'Industry Foundation Classes', 'articles' => 12, 'members' => 45),
                    array('title' => 'ISO 19650', 'desc' => 'BIM-standarder', 'articles' => 9, 'members' => 31),
                    array('title' => 'Uniclass', 'desc' => 'Klassifiseringssystem', 'articles' => 4, 'members' => 19),
                    array('title' => 'BCF', 'desc' => 'BIM Collaboration Format', 'articles' => 6, 'members' => 25),
                    array('title' => 'NS 3451', 'desc' => 'BIM samarbeid', 'articles' => 8, 'members' => 27),
                    array('title' => 'COBie', 'desc' => 'Construction Operations Building information exchange', 'articles' => 3, 'members' => 14),
                );
                
                foreach ($standarder as $item):
                ?>
                <a href="#" class="block bg-white rounded-lg shadow hover:shadow-lg transition-all p-4 border-l-4 border-green-500">
                    <h3 class="font-bold text-gray-900 mb-1"><?php echo esc_html($item['title']); ?></h3>
                    <p class="text-sm text-gray-600 mb-3"><?php echo esc_html($item['desc']); ?></p>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span>📝 <?php echo $item['articles']; ?></span>
                        <span>👥 <?php echo $item['members']; ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Fagtermer -->
        <div class="mb-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">💡 Fagtermer & Konsepter</h2>
                <span class="text-gray-500">14 begreper</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                
                <?php
                $fagtermer = array(
                    array('title' => 'LOD', 'desc' => 'Level of Development', 'articles' => 11, 'members' => 38),
                    array('title' => 'BIM-koordinering', 'desc' => 'Prosess for modellkonsistens', 'articles' => 6, 'members' => 34),
                    array('title' => 'Kollisjonskontroll', 'desc' => 'Detektering av konflikter i modell', 'articles' => 8, 'members' => 29),
                    array('title' => 'Digitalt samarbeid', 'desc' => 'Samarbeid via digitale verktøy', 'articles' => 5, 'members' => 24),
                    array('title' => 'Informasjonskrav', 'desc' => 'Krav til BIM-leveranser', 'articles' => 7, 'members' => 26),
                    array('title' => 'BIM-modell', 'desc' => 'Digital bygningsinformasjonsmodell', 'articles' => 15, 'members' => 52),
                    array('title' => 'Klimagassberegning', 'desc' => 'Beregning av CO2-utslipp', 'articles' => 9, 'members' => 31),
                    array('title' => 'EPD-data', 'desc' => 'Environmental Product Declaration', 'articles' => 6, 'members' => 22),
                );
                
                foreach ($fagtermer as $item):
                ?>
                <a href="#" class="block bg-white rounded-lg shadow hover:shadow-lg transition-all p-4 border-l-4 border-purple-500">
                    <h3 class="font-bold text-gray-900 mb-1"><?php echo esc_html($item['title']); ?></h3>
                    <p class="text-sm text-gray-600 mb-3"><?php echo esc_html($item['desc']); ?></p>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span>📝 <?php echo $item['articles']; ?></span>
                        <span>👥 <?php echo $item['members']; ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Verktøy -->
        <div class="mb-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">🔧 Verktøy & Programvare</h2>
                <span class="text-gray-500">6 begreper</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                
                <?php
                $verktoy = array(
                    array('title' => 'Revit', 'desc' => 'BIM Authoring fra Autodesk', 'articles' => 14, 'members' => 45),
                    array('title' => 'ArchiCAD', 'desc' => 'BIM software fra Graphisoft', 'articles' => 8, 'members' => 28),
                    array('title' => 'Solibri', 'desc' => 'Modellsjekk og kvalitetskontroll', 'articles' => 9, 'members' => 32),
                    array('title' => 'Navisworks', 'desc' => 'Koordinering og visualisering', 'articles' => 7, 'members' => 26),
                    array('title' => 'Catenda', 'desc' => 'Cloud BIM samarbeid', 'articles' => 11, 'members' => 34),
                    array('title' => 'Riuska', 'desc' => 'Energi- og klimaberegninger', 'articles' => 5, 'members' => 18),
                );
                
                foreach ($verktoy as $item):
                ?>
                <a href="#" class="block bg-white rounded-lg shadow hover:shadow-lg transition-all p-4 border-l-4 border-orange-500">
                    <h3 class="font-bold text-gray-900 mb-1"><?php echo esc_html($item['title']); ?></h3>
                    <p class="text-sm text-gray-600 mb-3"><?php echo esc_html($item['desc']); ?></p>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span>📝 <?php echo $item['articles']; ?></span>
                        <span>👥 <?php echo $item['members']; ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- CTA Section -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl shadow-2xl p-12 text-center text-white">
            <h2 class="text-3xl font-bold mb-4">Mangler et begrep?</h2>
            <p class="text-xl mb-8 opacity-90">
                Vi bygger begrepskatalogen kontinuerlig. Foreslå nye begreper eller forbedringer.
            </p>
            <div class="flex gap-4 justify-center flex-wrap">
                <a href="#" class="px-8 py-4 bg-white text-blue-600 rounded-lg font-bold hover:bg-gray-100 transition-colors">
                    Foreslå begrep
                </a>
                <a href="<?php echo esc_url(home_url('/kontakt/')); ?>" class="px-8 py-4 bg-white/20 text-white rounded-lg font-bold hover:bg-white/30 transition-colors">
                    Kontakt oss
                </a>
            </div>
        </div>
        
    </div>
</div>

<?php get_footer(); ?>
