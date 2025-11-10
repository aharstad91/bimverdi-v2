<?php
/**
 * Template Name: Demo - Min Side
 * 
 * Demo page showing all Min Side features with mock data
 * No login required - for preview purposes only
 */

get_header();
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        
        <!-- Demo Info -->
        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-8" role="alert">
            <p class="font-bold">Demo Mode</p>
            <p>Dette er en demo av Min Side-funksjonaliteten. Alle data vises som eksempler.</p>
        </div>
        
        <!-- Min Side Dashboard -->
        <div class="mb-16">
            <h2 class="text-3xl font-bold mb-6">🏠 Min Side Dashboard</h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <aside class="lg:col-span-1">
                    <div class="card bg-white shadow-md sticky top-8">
                        <div class="card-body p-4">
                            <h3 class="text-xl font-bold mb-4">Min Side</h3>
                            <ul class="space-y-1">
                                <li><a href="#dashboard" class="block px-3 py-2 text-white bg-primary rounded-lg font-semibold">Dashboard</a></li>
                                <li><a href="#profil" class="block px-3 py-2 text-gray-800 hover:bg-gray-100 rounded-lg">Min Profil</a></li>
                                <li><a href="#temagrupper" class="block px-3 py-2 text-gray-800 hover:bg-gray-100 rounded-lg">Temagrupper</a></li>
                                <li><a href="#arrangementer" class="block px-3 py-2 text-gray-800 hover:bg-gray-100 rounded-lg">Arrangementer</a></li>
                            </ul>
                        </div>
                    </div>
                </aside>
                
                <main class="lg:col-span-3">
                    <div class="card bg-white shadow-md mb-6" id="dashboard">
                        <div class="card-body p-6">
                            <h3 class="text-2xl font-bold mb-4">Velkommen, Anders!</h3>
                            <p class="text-gray-600 mb-6">DEMO: Arkitektur AS</p>
                            
                            <!-- Profile Progress -->
                            <div class="mb-8">
                                <h4 class="font-semibold mb-2">Profilkomplettering</h4>
                                <div class="w-full bg-gray-200 rounded-full h-4">
                                    <div class="bg-primary h-4 rounded-full" style="width: 65%;"></div>
                                </div>
                                <p class="text-sm text-gray-600 mt-2">65% fullført</p>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="mb-8">
                                <h4 class="font-semibold mb-3">Hurtigkoblinger</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <a href="#" class="btn btn-primary">Registrer verktøy</a>
                                    <a href="#" class="btn btn-primary">Skriv artikkel</a>
                                    <a href="#" class="btn btn-primary">Send idé</a>
                                </div>
                            </div>
                            
                            <!-- Stats -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-primary">3</div>
                                    <div class="text-sm text-gray-600">Verktøy</div>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-secondary">2</div>
                                    <div class="text-sm text-gray-600">Artikler</div>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-success">2</div>
                                    <div class="text-sm text-gray-600">Idéer</div>
                                </div>
                                <div class="bg-orange-50 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-primary">1</div>
                                    <div class="text-sm text-gray-600">Påmeldinger</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upcoming Events -->
                    <div class="card bg-white shadow-md">
                        <div class="card-body p-6">
                            <h4 class="text-xl font-bold mb-4">📅 Kommende arrangementer (3)</h4>
                            <div class="space-y-3">
                                <?php
                                $events = bimverdi_get_mock_events();
                                foreach (array_slice($events, 0, 3) as $event):
                                    ?>
                                    <div class="border rounded-lg p-4 hover:bg-gray-50">
                                        <div class="font-semibold"><?php echo esc_html($event['post_title']); ?></div>
                                        <div class="text-sm text-gray-600 mt-1">📅 <?php echo esc_html($event['date']); ?> | 🕐 <?php echo esc_html($event['time']); ?> | 📍 <?php echo esc_html($event['location']); ?></div>
                                    </div>
                                    <?php
                                endforeach;
                                ?>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        
        <!-- Min Profil Preview -->
        <div class="mb-16">
            <h2 class="text-3xl font-bold mb-6" id="profil">👤 Min Profil</h2>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="card bg-white shadow-md">
                    <div class="card-body p-6">
                        <h3 class="text-xl font-bold mb-3">DEMO: Arkitektur AS</h3>
                        <div class="space-y-3 text-sm text-gray-600">
                            <div><strong>Org.nr:</strong> 123456789</div>
                            <div><strong>Adresse:</strong> Stortingsgaten 1, 0161 Oslo</div>
                            <div><strong>Telefon:</strong> +47 XX XXX XXX</div>
                            <div><strong>Nettside:</strong> arkitektur.example.no</div>
                            <div><strong>Medlem:</strong> <span class="badge badge-primary">Partner</span></div>
                        </div>
                        <div class="mt-6 flex gap-2">
                            <button class="btn btn-primary btn-sm">Rediger profil</button>
                            <button class="btn btn-outline btn-sm">Skjul logo</button>
                        </div>
                    </div>
                </div>
                
                <div class="lg:col-span-2">
                    <div class="card bg-white shadow-md">
                        <div class="card-body p-6">
                            <h4 class="font-bold mb-3">📋 Bedriftsbeskrivelse</h4>
                            <p class="text-gray-600 mb-4">En moderne arkitekturBedrift som spesialiserer seg på BIM-drevet design og planlegging av komplekse byggeprosjekter.</p>
                            
                            <h4 class="font-bold mb-3">🏢 Kategorier</h4>
                            <div class="flex gap-2 flex-wrap mb-4">
                                <span class="badge badge-outline">Arkitektur</span>
                                <span class="badge badge-outline">Konsultasjon</span>
                            </div>
                            
                            <h4 class="font-bold mb-3">👥 Kundetyper</h4>
                            <div class="flex gap-2 flex-wrap">
                                <span class="badge badge-outline">Offentlig</span>
                                <span class="badge badge-outline">Privat</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Temagrupper -->
        <div class="mb-16">
            <h2 class="text-3xl font-bold mb-6" id="temagrupper">🎯 Temagrupper</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                foreach (bimverdi_get_mock_theme_groups() as $group):
                    $selected = $group['selected'] ? 'border-2 border-primary bg-blue-50' : 'border border-gray-200';
                    ?>
                    <div class="card bg-white shadow-md <?php echo $selected; ?>">
                        <div class="card-body p-4">
                            <h4 class="font-bold"><?php echo esc_html($group['name']); ?></h4>
                            <p class="text-sm text-gray-600 my-2"><?php echo esc_html($group['description']); ?></p>
                            <div class="text-xs text-gray-500">👥 <?php echo esc_html($group['members']); ?> medlemmer</div>
                            <?php if ($group['selected']): ?>
                                <div class="mt-3 font-semibold text-sm text-primary">✓ Du er medlem</div>
                            <?php else: ?>
                                <button class="btn btn-outline btn-sm mt-3 w-full">Bli medlem</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                endforeach;
                ?>
            </div>
        </div>
        
        <!-- Arrangementer -->
        <div class="mb-16">
            <h2 class="text-3xl font-bold mb-6" id="arrangementer">📅 Arrangementer</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <h3 class="font-bold mb-4">Kommende arrangementer</h3>
                    <div class="space-y-3">
                        <?php
                        foreach (bimverdi_get_mock_events() as $event):
                            ?>
                            <div class="card bg-white shadow-sm">
                                <div class="card-body p-4">
                                    <h4 class="font-semibold"><?php echo esc_html($event['post_title']); ?></h4>
                                    <div class="text-sm text-gray-600 mt-2">
                                        <div>📅 <?php echo esc_html($event['date']); ?></div>
                                        <div>🕐 <?php echo esc_html($event['time']); ?></div>
                                        <div>📍 <?php echo esc_html($event['location']); ?></div>
                                        <div>👥 <?php echo esc_html($event['registered']); ?>/<?php echo esc_html($event['capacity']); ?> påmeldt</div>
                                    </div>
                                    <button class="btn btn-sm <?php echo $event['user_registered'] ? 'btn-outline' : 'btn-primary'; ?> mt-3 w-full">
                                        <?php echo $event['user_registered'] ? 'Avmeld' : 'Meld på'; ?>
                                    </button>
                                </div>
                            </div>
                            <?php
                        endforeach;
                        ?>
                    </div>
                </div>
                
                <div>
                    <h3 class="font-bold mb-4">Mine påmeldinger</h3>
                    <div class="space-y-3">
                        <?php
                        foreach (array_filter(bimverdi_get_mock_events(), function($e) { return $e['user_registered']; }) as $event):
                            ?>
                            <div class="card bg-green-50 shadow-sm border border-green-200">
                                <div class="card-body p-4">
                                    <h4 class="font-semibold"><?php echo esc_html($event['post_title']); ?></h4>
                                    <div class="text-sm text-gray-600 mt-2">
                                        <div>📅 <?php echo esc_html($event['date']); ?></div>
                                        <div>🕐 <?php echo esc_html($event['time']); ?></div>
                                    </div>
                                    <div class="text-green-700 font-semibold mt-3">✓ Du er påmeldt</div>
                                </div>
                            </div>
                            <?php
                        endforeach;
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php get_footer();
