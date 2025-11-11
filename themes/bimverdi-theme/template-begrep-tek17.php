<?php
/**
 * Template Name: Begrep - TEK17
 * 
 * Komplett informasjonsside for TEK17 begrep
 * Demo av semantisk begrepssystem
 * 
 * @package BimVerdi_Theme
 */

get_header();
?>

<div class="min-h-screen bg-bim-beige-100">
    
    <!-- Concept Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16">
        <div class="container mx-auto px-4">
            <div class="flex items-center gap-3 mb-4">
                <a href="<?php echo esc_url(home_url('/begreper/')); ?>" class="text-white/80 hover:text-white text-sm">
                    ← Tilbake til begreper
                </a>
            </div>
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex-1 text-black">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="bg-white/20 px-4 py-2 rounded-full text-sm font-bold">FORSKRIFT</span>
                        <span class="bg-white/20 px-4 py-2 rounded-full text-sm font-bold">⚖️ Lovdata</span>
                    </div>
                    <h1 class="text-5xl font-bold mb-4">TEK17</h1>
                    <p class="text-2xl opacity-95">Tekniske krav til byggverk – Norges gjeldende byggenorm</p>
                </div>
                <div class="text-right text-black">
                    <div class="text-4xl font-bold">156</div>
                    <div class="text-sm opacity-80">visninger denne måneden</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Definition -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Hva er TEK17?</h2>
                    <p class="text-lg text-gray-700 mb-4">
                        TEK17 (Tekniske krav til byggverk) er den norske byggenormen som fastsetter krav til: <strong>energieffektivitet</strong>, <strong>sikkerhet ved brann</strong>, <strong>tilgjengelighet</strong>, <strong>akustikk</strong> og <strong>miljø</strong>. Den gjelder fra 1. juli 2017 og erstattet TEK10.
                    </p>
                    <p class="text-lg text-gray-700">
                        <strong>For byggeprosessen betyr det:</strong> Alle prosjekter må dokumenteres og valideres mot TEK17-krav. BIM er den beste måten å gjøre dette på, siden du kan sjekke krav elektronisk i modellen.
                    </p>
                </div>
                
                <!-- Related Concepts -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">🔗 Relaterte Begreper</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        <a href="#" class="p-4 bg-gray-50 rounded-lg border-l-4 border-blue-500 hover:bg-blue-50 transition-colors">
                            <div class="text-xs text-blue-600 font-bold mb-1">FAGTERM</div>
                            <div class="font-bold text-gray-900 mb-1">Energieffektivitet</div>
                            <div class="text-sm text-gray-600">TEK17 §6-3 krever strenge energikrav</div>
                        </a>
                        
                        <a href="#" class="p-4 bg-gray-50 rounded-lg border-l-4 border-green-500 hover:bg-green-50 transition-colors">
                            <div class="text-xs text-green-600 font-bold mb-1">FAGTERM</div>
                            <div class="font-bold text-gray-900 mb-1">Klimagassberegning</div>
                            <div class="text-sm text-gray-600">TEK17 §17-1 krever CO2-dokumentasjon</div>
                        </a>
                        
                        <a href="#" class="p-4 bg-gray-50 rounded-lg border-l-4 border-purple-500 hover:bg-purple-50 transition-colors">
                            <div class="text-xs text-purple-600 font-bold mb-1">FAGTERM</div>
                            <div class="font-bold text-gray-900 mb-1">Modellkvalitet</div>
                            <div class="text-sm text-gray-600">God BIM-kvalitet er nøkkelen til TEK17-samsvar</div>
                        </a>
                        
                        <a href="#" class="p-4 bg-gray-50 rounded-lg border-l-4 border-orange-500 hover:bg-orange-50 transition-colors">
                            <div class="text-xs text-orange-600 font-bold mb-1">STANDARD</div>
                            <div class="font-bold text-gray-900 mb-1">IFC Standard</div>
                            <div class="text-sm text-gray-600">TEK17-validering skjer ofte via IFC-modeller</div>
                        </a>
                    </div>
                </div>
                
                <!-- Sources & Requirements -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">⚖️ Relevante Kilder & Krav</h2>
                    <p class="text-gray-700 mb-4"><strong>Hovedelementer:</strong></p>
                    <ul class="space-y-3 mb-6">
                        <li class="flex items-start gap-3">
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-bold mt-1">§6-3</span>
                            <div>
                                <div class="font-semibold">Energikrav</div>
                                <div class="text-sm text-gray-600">U-verdier, energiberegning</div>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-bold mt-1">§11-7</span>
                            <div>
                                <div class="font-semibold">Sikkerhet ved brann</div>
                                <div class="text-sm text-gray-600">Rømningsveier og utganger</div>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs font-bold mt-1">§13-6</span>
                            <div>
                                <div class="font-semibold">Lydkrav</div>
                                <div class="text-sm text-gray-600">Lydisolasjon</div>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold mt-1">§17-1</span>
                            <div>
                                <div class="font-semibold">Klimagassdeklarasjon</div>
                                <div class="text-sm text-gray-600">Nytt krav fra 2024</div>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="bg-orange-100 text-orange-800 px-2 py-1 rounded text-xs font-bold mt-1">§8-3</span>
                            <div>
                                <div class="font-semibold">Tilgjengelighet</div>
                                <div class="text-sm text-gray-600">Universell utforming</div>
                            </div>
                        </li>
                    </ul>
                    <a href="https://lovdata.no/dokument/SF/forskrift/2010-03-26-489" target="_blank" class="btn btn-primary">
                        📥 Last ned TEK17 fra Lovdata
                    </a>
                </div>
                
                <!-- Practical Guides -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">📖 Praksis Guides</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        <a href="#" class="p-5 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg hover:shadow-lg transition-all border border-blue-200">
                            <div class="text-blue-600 text-2xl mb-3">📘</div>
                            <h3 class="font-bold text-gray-900 mb-2">Hvordan oppfylle energikrav</h3>
                            <p class="text-sm text-gray-600 mb-3">Steg-for-steg implementering av §6-3</p>
                            <div class="text-blue-600 text-sm font-semibold">Les guide →</div>
                        </a>
                        
                        <a href="#" class="p-5 bg-gradient-to-br from-green-50 to-green-100 rounded-lg hover:shadow-lg transition-all border border-green-200">
                            <div class="text-green-600 text-2xl mb-3">🌱</div>
                            <h3 class="font-bold text-gray-900 mb-2">Klimagassdeklarasjon i praksis</h3>
                            <p class="text-sm text-gray-600 mb-3">Fra EPD-data til ferdig rapport</p>
                            <div class="text-green-600 text-sm font-semibold">Les guide →</div>
                        </a>
                        
                        <a href="#" class="p-5 bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg hover:shadow-lg transition-all border border-purple-200">
                            <div class="text-purple-600 text-2xl mb-3">🏗️</div>
                            <h3 class="font-bold text-gray-900 mb-2">TEK17 i BIM-modellen</h3>
                            <p class="text-sm text-gray-600 mb-3">Slik setter du opp Revit/ArchiCAD korrekt</p>
                            <div class="text-purple-600 text-sm font-semibold">Les guide →</div>
                        </a>
                    </div>
                </div>
                
                <!-- Case Studies -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">💼 Case Studies fra Medlemmer</h2>
                    <div class="space-y-4">
                        
                        <a href="#" class="block p-6 bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg hover:shadow-lg transition-all border border-gray-200">
                            <div class="flex items-start gap-4">
                                <div class="text-4xl">🏢</div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-bold">CASE STUDY</span>
                                        <span class="text-gray-500 text-sm">Visjon Arkitekter</span>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-900 mb-2">Ferner Bryggen – Klimagass -23%</h3>
                                    <p class="text-gray-600 mb-3">Visjon Arkitekter reduserte CO2-utslipp ved å bruke BIM + EPD-data systematisk. Prosjektet viser hvordan TEK17 §17-1 kan implementeres effektivt.</p>
                                    <div class="flex items-center gap-4 text-sm text-gray-500">
                                        <span>📅 September 2024</span>
                                        <span>⏱️ 8 min lesing</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                        
                        <a href="#" class="block p-6 bg-gradient-to-r from-orange-50 to-red-50 rounded-lg hover:shadow-lg transition-all border border-gray-200">
                            <div class="flex items-start gap-4">
                                <div class="text-4xl">⚠️</div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="bg-orange-600 text-white px-3 py-1 rounded-full text-xs font-bold">LÆRING</span>
                                        <span class="text-gray-500 text-sm">Statsbygg</span>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-900 mb-2">5 vanlige TEK17-feil vi ser i praksis</h3>
                                    <p class="text-gray-600 mb-3">Statsbygg deler erfaring fra 50+ prosjekter om hva som ofte går galt ved implementering av energi- og sikkerhetskrav.</p>
                                    <div class="flex items-center gap-4 text-sm text-gray-500">
                                        <span>📅 Oktober 2024</span>
                                        <span>⏱️ 6 min lesing</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                
                <!-- Tools -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">🔧 Verktøy som Implementerer TEK17</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        
                        <a href="#" class="p-4 bg-gray-50 rounded-lg hover:shadow-lg transition-all border border-gray-200">
                            <div class="text-3xl mb-3">🔷</div>
                            <h3 class="font-bold text-gray-900 mb-2">Revit</h3>
                            <p class="text-sm text-gray-600 mb-3">Native TEK17-validering med plugins</p>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>👥 45 medlemmer</span>
                                <span class="text-blue-600 font-semibold">Profil →</span>
                            </div>
                        </a>
                        
                        <a href="#" class="p-4 bg-gray-50 rounded-lg hover:shadow-lg transition-all border border-gray-200">
                            <div class="text-3xl mb-3">☁️</div>
                            <h3 class="font-bold text-gray-900 mb-2">Catenda</h3>
                            <p class="text-sm text-gray-600 mb-3">Cloud BIM med EPD-støtte for klimakrav</p>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>👥 23 medlemmer</span>
                                <span class="text-blue-600 font-semibold">Profil →</span>
                            </div>
                        </a>
                        
                        <a href="#" class="p-4 bg-gray-50 rounded-lg hover:shadow-lg transition-all border border-gray-200">
                            <div class="text-3xl mb-3">⚡</div>
                            <h3 class="font-bold text-gray-900 mb-2">Riuska</h3>
                            <p class="text-sm text-gray-600 mb-3">Energi- og klimaberegninger</p>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>👥 18 medlemmer</span>
                                <span class="text-blue-600 font-semibold">Profil →</span>
                            </div>
                        </a>
                    </div>
                </div>
                
                <!-- Network Graph Placeholder -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">🕸️ Semantisk Graf - Hvordan Begreper Henger Sammen</h2>
                    <div class="bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg p-12 text-center min-h-[300px] flex flex-col items-center justify-center">
                        <div class="text-6xl mb-4">🔗</div>
                        <p class="text-gray-600 mb-2">Interaktiv graf-visualisering</p>
                        <p class="text-sm text-gray-500">Viser: TEK17 → Energi → Revit → Klimagass → EPD → Catenda → MiljøBIM</p>
                        <p class="text-xs text-gray-400 mt-4">Implementeres med D3.js eller vis.js</p>
                    </div>
                </div>
                
                <!-- Experts -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">👥 Kontakt Eksperter i Nettverket</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        
                        <div class="p-5 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg border border-blue-200">
                            <div class="text-3xl mb-3">🏛️</div>
                            <h3 class="font-bold text-gray-900 mb-1">Statsbygg</h3>
                            <p class="text-sm text-gray-600 mb-3">Partner – har gjort 50+ TEK17-prosjekter</p>
                            <a href="#" class="text-blue-600 text-sm font-semibold hover:underline">Kontakt →</a>
                        </div>
                        
                        <div class="p-5 bg-gradient-to-br from-green-50 to-green-100 rounded-lg border border-green-200">
                            <div class="text-3xl mb-3">🌱</div>
                            <h3 class="font-bold text-gray-900 mb-1">Evolutia AS</h3>
                            <p class="text-sm text-gray-600 mb-3">Medlem – spesialist på klimagassberegning</p>
                            <a href="#" class="text-green-600 text-sm font-semibold hover:underline">Kontakt →</a>
                        </div>
                        
                        <div class="p-5 bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg border border-purple-200">
                            <div class="text-3xl mb-3">👥</div>
                            <h3 class="font-bold text-gray-900 mb-1">23 andre medlemmer</h3>
                            <p class="text-sm text-gray-600 mb-3">som aktivt jobber med TEK17</p>
                            <a href="#" class="text-purple-600 text-sm font-semibold hover:underline">Se liste →</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- Quick Info -->
                <div class="bg-white rounded-lg shadow-lg p-6 sticky top-24">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">📌 Rask Info</h3>
                    <div class="space-y-3">
                        <div class="pb-3 border-b border-gray-200">
                            <div class="text-xs text-gray-500 uppercase mb-1">Status</div>
                            <div class="font-semibold text-green-600">Gjeldende</div>
                        </div>
                        <div class="pb-3 border-b border-gray-200">
                            <div class="text-xs text-gray-500 uppercase mb-1">Ikrafttreden</div>
                            <div class="font-semibold">1. juli 2017</div>
                        </div>
                        <div class="pb-3 border-b border-gray-200">
                            <div class="text-xs text-gray-500 uppercase mb-1">Utgiver</div>
                            <div class="font-semibold text-sm">Kommunal- og moderniseringsdepartementet</div>
                        </div>
                        <div class="pb-3 border-b border-gray-200">
                            <div class="text-xs text-gray-500 uppercase mb-1">Type</div>
                            <div class="font-semibold">Forskrift</div>
                        </div>
                        <div class="pb-3 border-b border-gray-200">
                            <div class="text-xs text-gray-500 uppercase mb-1">Lovdata ID</div>
                            <div class="text-xs font-mono text-gray-600">FORSKRIFT-2010-03-26-489</div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <h4 class="text-sm font-bold text-gray-900 mb-3">Relevante temagrupper:</h4>
                        <div class="space-y-2">
                            <a href="#" class="block px-3 py-2 bg-blue-50 text-blue-700 rounded hover:bg-blue-100 text-sm font-semibold">
                                ByggesaksBIM
                            </a>
                            <a href="#" class="block px-3 py-2 bg-green-50 text-green-700 rounded hover:bg-green-100 text-sm font-semibold">
                                MiljøBIM
                            </a>
                            <a href="#" class="block px-3 py-2 bg-purple-50 text-purple-700 rounded hover:bg-purple-100 text-sm font-semibold">
                                ProsjektBIM
                            </a>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-2xl">👥</span>
                            <div>
                                <div class="font-bold text-2xl text-blue-600">23</div>
                                <div class="text-xs text-gray-600">medlemmer jobber med dette</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Related Articles -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">📰 Relaterte Artikler</h3>
                    <div class="space-y-3">
                        <a href="#" class="block pb-3 border-b border-gray-200 hover:bg-gray-50 -mx-3 px-3 py-2 rounded">
                            <div class="font-semibold text-sm text-gray-900 mb-1">Slik implementerte vi TEK17</div>
                            <div class="text-xs text-gray-500">Visjon Arkitekter</div>
                        </a>
                        <a href="#" class="block pb-3 border-b border-gray-200 hover:bg-gray-50 -mx-3 px-3 py-2 rounded">
                            <div class="font-semibold text-sm text-gray-900 mb-1">5 vanlige TEK17-feil</div>
                            <div class="text-xs text-gray-500">Statsbygg</div>
                        </a>
                        <a href="#" class="block hover:bg-gray-50 -mx-3 px-3 py-2 rounded">
                            <div class="font-semibold text-sm text-gray-900 mb-1">Klimakrav i praksis</div>
                            <div class="text-xs text-gray-500">BIM Verdi</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
