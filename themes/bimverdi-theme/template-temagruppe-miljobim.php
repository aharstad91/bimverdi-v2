<?php
/**
 * Template Name: Temagruppe - MiljøBIM
 * 
 * MiljøBIM temagruppe med semantisk integrasjon
 * Demo av hvordan begreper kobles til temagrupper
 * 
 * @package BimVerdi_Theme
 */

get_header();
?>

<div class="min-h-screen bg-bim-beige-100">
    
    <!-- Hero Header -->
    <div class="bg-gradient-to-r from-green-600 to-teal-600 text-white py-16">
        <div class="container mx-auto px-4">
            <div class="flex items-center gap-3 mb-4">
                <a href="<?php echo esc_url(home_url('/temagrupper/')); ?>" class="text-white/80 hover:text-white text-sm">
                    ← Tilbake til temagrupper
                </a>
            </div>
            <div class="flex items-center justify-between flex-wrap gap-6">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="bg-white/20 px-4 py-2 rounded-full text-sm font-bold">🌱 TEMAGRUPPE</span>
                        <span class="bg-white/20 px-4 py-2 rounded-full text-sm font-bold">Aktiv</span>
                    </div>
                    <h1 class="text-5xl font-bold mb-4">MiljøBIM</h1>
                    <p class="text-xl opacity-95">Tema: Miljø og klima i BIM – klimagassberegning, EPD-data, sirkulæritet</p>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold">34</div>
                    <div class="text-sm opacity-80">medlemsbedrifter</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container mx-auto px-4 py-12">
        
        <!-- Quick Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
            
            <!-- Next Meeting -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">📊 Neste Møte</h3>
                <div class="mb-4">
                    <div class="text-3xl font-bold text-green-600 mb-1">14. november 2024</div>
                    <div class="text-lg text-gray-700">14:00-16:00 (Teams)</div>
                </div>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded mb-6">
                    <div class="font-semibold text-gray-900 mb-1">Tema: "EPD-data i praksis"</div>
                    <div class="text-sm text-gray-600">Hvordan implementere Environmental Product Declarations i BIM-prosesser</div>
                </div>
                <a href="#" class="btn btn-primary w-full">
                    Meld meg på →
                </a>
            </div>
            
            <!-- Members -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">👥 Medlemmer</h3>
                <div class="mb-4">
                    <div class="text-3xl font-bold text-purple-600 mb-1">34 bedrifter</div>
                    <div class="text-lg text-gray-700">Fra hele verdikjeden</div>
                </div>
                <div class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded mb-6">
                    <div class="text-sm text-gray-700">
                        <strong>Representert:</strong> Arkitekter, rådgivere, entreprenører, leverandører
                    </div>
                </div>
                <a href="#" class="btn btn-outline w-full">
                    Se alle medlemmer →
                </a>
            </div>
        </div>
        
        <!-- Related Concepts & Sources -->
        <div class="mb-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-3xl font-bold text-gray-900">🔗 Relaterte Begreper & Kilder</h2>
                <a href="<?php echo esc_url(home_url('/begreper/')); ?>" class="text-green-600 hover:underline font-semibold">
                    Se alle begreper →
                </a>
            </div>
            <p class="text-lg text-gray-700 mb-6">MiljøBIM jobber med disse temaene:</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Klimagassberegning -->
                <a href="#" class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all p-6 border-l-4 border-green-500">
                    <div class="flex items-start justify-between mb-3">
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-bold">FAGTERM</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Klimagassberegning</h3>
                    <p class="text-sm text-gray-600 mb-4">TEK17 §17-1 krav</p>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span>📝 9 artikler</span>
                        <span>👥 31 medlemmer</span>
                    </div>
                </a>
                
                <!-- EPD-data -->
                <a href="#" class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all p-6 border-l-4 border-blue-500">
                    <div class="flex items-start justify-between mb-3">
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-bold">FAGTERM</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">EPD-data</h3>
                    <p class="text-sm text-gray-600 mb-4">Environmental Product Declaration</p>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span>📝 6 artikler</span>
                        <span>👥 22 medlemmer</span>
                    </div>
                </a>
                
                <!-- TEK17 -->
                <a href="#" class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all p-6 border-l-4 border-purple-500">
                    <div class="flex items-start justify-between mb-3">
                        <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-xs font-bold">FORSKRIFT</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">TEK17 §17-1 & §17-2</h3>
                    <p class="text-sm text-gray-600 mb-4">Klimakrav fra Lovdata</p>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span>⚖️ Lovdata</span>
                        <span>👥 23 medlemmer</span>
                    </div>
                </a>
                
                <!-- Materialpass -->
                <a href="#" class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all p-6 border-l-4 border-orange-500">
                    <div class="flex items-start justify-between mb-3">
                        <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-xs font-bold">FAGTERM</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Materialpass</h3>
                    <p class="text-sm text-gray-600 mb-4">Dokumentasjon av materialer</p>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span>📝 4 artikler</span>
                        <span>👥 18 medlemmer</span>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- Latest Content -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">📰 Siste Innhold</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Case Study -->
                <a href="#" class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-teal-500 p-4">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-bold text-white">💼 CASE</span>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Ferner Bryggen – Klimagass -23%</h3>
                        <p class="text-sm text-gray-600 mb-4">Case fra Visjon Arkitekter om systematisk bruk av EPD-data og BIM</p>
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>📅 Sep 2024</span>
                            <span class="text-green-600 font-semibold">Les case →</span>
                        </div>
                    </div>
                </a>
                
                <!-- Guide -->
                <a href="#" class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-500 p-4">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-bold text-white">📖 GUIDE</span>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">EPD-data: Hvor finner du det?</h3>
                        <p class="text-sm text-gray-600 mb-4">Praktisk guide til å finne og bruke Environmental Product Declarations</p>
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>📅 Okt 2024</span>
                            <span class="text-blue-600 font-semibold">Les guide →</span>
                        </div>
                    </div>
                </a>
                
                <!-- Article -->
                <a href="#" class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-500 to-red-500 p-4">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-bold text-white">📘 ARTIKKEL</span>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Klimagassdeklarasjon i 5 steg</h3>
                        <p class="text-sm text-gray-600 mb-4">Fra TEK17-krav til ferdig rapport – steg for steg guide</p>
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>📅 Nov 2024</span>
                            <span class="text-orange-600 font-semibold">Les artikkel →</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- Tools Used by Group -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">🔧 Verktøy som Brukes i MiljøBIM</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                
                <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-all">
                    <div class="text-4xl mb-3">🔷</div>
                    <h3 class="font-bold text-gray-900 mb-2">Revit</h3>
                    <p class="text-sm text-gray-600 mb-3">BIM Authoring med EPD-støtte</p>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">👥 28 brukere</span>
                        <a href="#" class="text-blue-600 font-semibold hover:underline">Se →</a>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-all">
                    <div class="text-4xl mb-3">☁️</div>
                    <h3 class="font-bold text-gray-900 mb-2">Catenda</h3>
                    <p class="text-sm text-gray-600 mb-3">Cloud BIM med klimaanalyse</p>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">👥 23 brukere</span>
                        <a href="#" class="text-blue-600 font-semibold hover:underline">Se →</a>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-all">
                    <div class="text-4xl mb-3">⚡</div>
                    <h3 class="font-bold text-gray-900 mb-2">Riuska</h3>
                    <p class="text-sm text-gray-600 mb-3">Klimagassberegninger</p>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">👥 18 brukere</span>
                        <a href="#" class="text-blue-600 font-semibold hover:underline">Se →</a>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-all">
                    <div class="text-4xl mb-3">📊</div>
                    <h3 class="font-bold text-gray-900 mb-2">OneClick LCA</h3>
                    <p class="text-sm text-gray-600 mb-3">Livssyklusanalyse</p>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">👥 14 brukere</span>
                        <a href="#" class="text-blue-600 font-semibold hover:underline">Se →</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Past Meetings -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">📅 Tidligere Møter & Ressurser</h2>
            
            <div class="space-y-4">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-start gap-4">
                        <div class="bg-green-100 rounded-lg p-4 text-center min-w-[80px]">
                            <div class="text-2xl font-bold text-green-600">05</div>
                            <div class="text-xs text-green-800">OKT 2024</div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Introduksjon til TEK17 klimakrav</h3>
                            <p class="text-gray-600 mb-3">Gjennomgang av §17-1 og §17-2 med eksempler fra praksis</p>
                            <div class="flex items-center gap-4 text-sm">
                                <a href="#" class="text-blue-600 hover:underline font-semibold">📹 Se opptak</a>
                                <a href="#" class="text-green-600 hover:underline font-semibold">📄 Last ned slides</a>
                                <span class="text-gray-500">28 deltakere</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-start gap-4">
                        <div class="bg-blue-100 rounded-lg p-4 text-center min-w-[80px]">
                            <div class="text-2xl font-bold text-blue-600">12</div>
                            <div class="text-xs text-blue-800">SEP 2024</div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">EPD-databaser og integrasjoner</h3>
                            <p class="text-gray-600 mb-3">Oversikt over tilgjengelige EPD-kilder og hvordan bruke dem i BIM</p>
                            <div class="flex items-center gap-4 text-sm">
                                <a href="#" class="text-blue-600 hover:underline font-semibold">📹 Se opptak</a>
                                <a href="#" class="text-green-600 hover:underline font-semibold">📄 Last ned slides</a>
                                <span class="text-gray-500">32 deltakere</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-start gap-4">
                        <div class="bg-purple-100 rounded-lg p-4 text-center min-w-[80px]">
                            <div class="text-2xl font-bold text-purple-600">15</div>
                            <div class="text-xs text-purple-800">AUG 2024</div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Ferner Bryggen case study</h3>
                            <p class="text-gray-600 mb-3">Visjon Arkitekter delte erfaringer fra prosjektet med 23% CO2-reduksjon</p>
                            <div class="flex items-center gap-4 text-sm">
                                <a href="#" class="text-blue-600 hover:underline font-semibold">📹 Se opptak</a>
                                <a href="#" class="text-green-600 hover:underline font-semibold">📄 Last ned slides</a>
                                <span class="text-gray-500">34 deltakere</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Members Showcase -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">👥 Aktive Medlemmer</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php
                $members = array(
                    'Statsbygg', 'Evolutia AS', 'Visjon Arkitekter', 'Multiconsult',
                    'Norconsult', 'Skanska', 'AF Gruppen', 'Veidekke',
                    'Sweco', 'Asplan Viak', 'COWI', 'Rambøll'
                );
                
                foreach ($members as $member):
                ?>
                <a href="#" class="bg-white rounded-lg shadow hover:shadow-lg transition-all p-4 text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-teal-500 rounded-full mx-auto mb-2 flex items-center justify-center text-white font-bold text-xl">
                        <?php echo substr($member, 0, 1); ?>
                    </div>
                    <div class="text-sm font-semibold text-gray-900"><?php echo esc_html($member); ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Join CTA -->
        <div class="bg-gradient-to-r from-green-600 to-teal-600 rounded-2xl shadow-2xl p-12 text-center text-white">
            <h2 class="text-4xl font-bold text-white mb-4">Vil du bli med i MiljøBIM?</h2>
            <p class="text-xl text-white mb-8 opacity-90 max-w-2xl mx-auto">
                Vi møtes 6-8 ganger i året for å dele kunnskap, erfaringer og utvikle beste praksis for miljø og klima i BIM.
            </p>
            <div class="flex gap-4 justify-center flex-wrap">
                <a href="#" class="px-8 py-4 bg-white text-green-600 rounded-lg font-bold hover:bg-gray-100 transition-colors text-lg">
                    Bli medlem av gruppen
                </a>
                <a href="<?php echo esc_url(home_url('/kontakt/')); ?>" class="px-8 py-4 bg-white/20 text-white rounded-lg font-bold hover:bg-white/30 transition-colors text-lg">
                    Kontakt gruppeleder
                </a>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
