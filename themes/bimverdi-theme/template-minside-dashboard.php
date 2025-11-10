<!-- 
Template Name: Min Side Dashboard
Description: Dashboard for logged-in members
-->
<?php
/**
 * Template Name: Min Side Dashboard
 * 
 * Dashboard template for BIM Verdi members
 * Shows profile info, quick actions, upcoming events, and navigation
 * 
 * @package BimVerdi_Theme
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
$company = $company_id ? get_post($company_id) : null;

// Use mock data if no real company exists (for demo)
$is_demo = empty($company);
if ($is_demo) {
    $company = (object) bimverdi_get_mock_company();
}

// Get user role
$user_roles = $current_user->roles;
$is_company_owner = in_array('company_owner', $user_roles);
$is_member_coordinator = in_array('member_coordinator', $user_roles);

// Profile completion percentage
$profile_completion = 0;
if ($company) {
    $total_fields = 10;
    $filled_fields = 0;
    
    if (get_field('organisasjonsnummer', $company_id)) $filled_fields++;
    if (get_field('bedriftsnavn', $company_id)) $filled_fields++;
    if (get_field('beskrivelse', $company_id)) $filled_fields++;
    if (get_field('logo', $company_id)) $filled_fields++;
    if (get_field('adresse', $company_id)) $filled_fields++;
    if (get_field('kontaktperson', $company_id)) $filled_fields++;
    if (has_term('', 'bransjekategori', $company_id)) $filled_fields++;
    if (has_term('', 'kundetype', $company_id)) $filled_fields++;
    if (get_field('nettside', $company_id)) $filled_fields++;
    if (get_field('telefon', $company_id)) $filled_fields++;
    
    $profile_completion = round(($filled_fields / $total_fields) * 100);
}

// Get upcoming events
$upcoming_events = get_posts(array(
    'post_type' => 'arrangement',
    'posts_per_page' => 3,
    'meta_key' => 'arrangement_dato',
    'orderby' => 'meta_value',
    'order' => 'ASC',
    'meta_query' => array(
        array(
            'key' => 'arrangement_dato',
            'value' => date('Y-m-d'),
            'compare' => '>=',
            'type' => 'DATE'
        )
    )
));
?>

<div class="min-h-screen bg-bim-beige-100 py-8">
    <div class="container mx-auto px-4">
        
        <!-- Success Message for Company Connection -->
        <?php if (isset($_GET['foretak_koblet']) && $_GET['foretak_koblet'] == '1'): ?>
            <div class="alert alert-success bg-green-50 border-2 border-green-500 mb-6 shadow-lg">
                <div>
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h3 class="font-bold text-lg text-green-900">Du er nå koblet til et foretak!</h3>
                        <div class="text-sm text-green-800">
                            Du har full tilgang til alle funksjoner i BIM Verdi medlemsportal.
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Demo Banner -->
        <?php if ($is_demo): ?>
            <div class="alert alert-warning shadow-lg mb-6 bg-alert text-black">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0-11l6.364 3.682a2 2 0 010 3.464L12 21l-6.364-3.682a2 2 0 010-3.464L12 2z"></path>
                    </svg>
                    <div>
                        <h3 class="font-bold">Demo-data vises</h3>
                        <div class="text-sm">Du ser demo-data fordi ingen faktisk bedrift er opprettet ennå. Dette viser hvordan Min Side vil se ut når du har registrert deg.</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Welcome Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-bim-black-900 mb-2">
                Velkommen, <?php echo esc_html($current_user->first_name ?: $current_user->display_name); ?>!
            </h1>
            <?php if ($company_id): ?>
                <?php 
                $user_rolle = get_field('foretak_rolle', 'user_' . $user_id);
                ?>
                <div class="flex items-center gap-2 text-lg">
                    <p class="text-bim-black-700">
                        <svg class="w-5 h-5 inline-block mr-1 text-bim-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <strong><?php echo esc_html($company->post_title); ?></strong>
                    </p>
                    <?php if ($user_rolle): ?>
                        <span class="badge bg-bim-purple text-white border-0"><?php echo esc_html($user_rolle); ?></span>
                    <?php endif; ?>
                    <?php if ($is_company_owner): ?>
                        <span class="badge bg-bim-orange text-white border-0">Bedriftseier</span>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(home_url('/koble-foretak/')); ?>" class="text-sm text-bim-orange hover:underline ml-2">
                        Se detaljer →
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Min Side Horizontal Tab Navigation -->
        <?php 
        $current_tab = 'dashboard';
        get_template_part('template-parts/minside-tabs', null, array('current_tab' => $current_tab));
        ?>

        <!-- Main Content (Full Width) -->
        <div>

                <!-- Welcome - Next Step: Connect to Company -->
                <?php if (!$company_id): ?>
                <div class="alert bg-gradient-to-r from-bim-orange to-bim-purple text-white mb-6 rounded-lg shadow-lg border-0">
                    <div>
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <div>
                            <h3 class="font-bold text-xl mb-2">Velkommen til BIM Verdi! 🎉</h3>
                            <div class="text-white text-opacity-95 mb-2">
                                Din bruker er opprettet. Neste steg er å koble deg til et foretak for å få full tilgang til medlemsportalen.
                            </div>
                            <div class="text-sm text-white text-opacity-90">
                                💼 Velg ditt foretak fra listen, eller registrer et nytt hvis det ikke finnes ennå.
                            </div>
                        </div>
                    </div>
                    <div class="flex-none">
                        <a href="<?php echo esc_url(home_url('/koble-foretak/')); ?>" class="btn btn-lg bg-white text-bim-orange hover:bg-bim-beige-100 border-0">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                            Koble til Foretak
                        </a>
                    </div>
                </div>
                <?php endif; ?>


                <!-- Quick Actions -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-bim-black-900 mb-4">Hurtighandlinger</h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        
                        <!-- Profil Card -->
                        <div class="card bg-base-100 shadow-xl border border-base-200">
                            <div class="card-body">
                                <h2 class="card-title text-bim-black-900">Profil</h2>
                                <p class="text-sm text-bim-black-700">Administrer din brukerprofil</p>
                                <div class="card-actions justify-end mt-4">
                                    <a href="<?php echo esc_url(home_url('/rediger-bruker/')); ?>" class="btn btn-primary btn-sm">Rediger</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Foretak Card -->
                        <div class="card bg-base-100 shadow-xl border border-base-200">
                            <div class="card-body">
                                <h2 class="card-title text-bim-black-900">Foretak</h2>
                                <p class="text-sm text-bim-black-700"><?php echo $company_id ? 'Se foretak status' : 'Koble til foretak'; ?></p>
                                <div class="card-actions justify-end mt-4">
                                    <a href="<?php echo esc_url(home_url('/koble-foretak/')); ?>" class="btn btn-primary btn-sm">
                                        <?php echo $company_id ? 'Se Status' : 'Koble Til'; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Verktøy Card -->
                        <?php 
                        $my_tools = get_posts(array(
                            'post_type' => 'verktoy',
                            'author' => $user_id,
                            'posts_per_page' => -1,
                            'post_status' => array('publish', 'draft', 'pending'),
                        ));
                        $my_tools_count = count($my_tools);
                        ?>
                        <div class="card bg-base-100 shadow-xl border border-base-200">
                            <div class="card-body">
                                <h2 class="card-title text-bim-black-900">Verktøy</h2>
                                <p class="text-3xl font-bold text-bim-purple my-2"><?php echo $my_tools_count; ?></p>
                                <div class="card-actions justify-end mt-4">
                                    <a href="<?php echo esc_url(home_url('/registrer-verktoy/')); ?>" class="btn btn-primary btn-sm">Lag Nytt</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Idéer Card -->
                        <?php 
                        $my_ideas = get_posts(array(
                            'post_type' => 'case',
                            'author' => $user_id,
                            'posts_per_page' => -1,
                        ));
                        $my_ideas_count = count($my_ideas);
                        ?>
                        <div class="card bg-base-100 shadow-xl border border-base-200">
                            <div class="card-body">
                                <h2 class="card-title text-bim-black-900">Idéer</h2>
                                <p class="text-3xl font-bold text-bim-purple my-2"><?php echo $my_ideas_count; ?></p>
                                <div class="card-actions justify-end mt-4">
                                    <a href="#" class="btn btn-primary btn-sm">Send Inn</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Arrangementer Card -->
                        <?php 
                        $my_registrations = get_posts(array(
                            'post_type' => 'pamelding',
                            'meta_key' => 'pamelding_bruker',
                            'meta_value' => $user_id,
                            'posts_per_page' => -1,
                        ));
                        $registrations_count = count($my_registrations);
                        ?>
                        <div class="card bg-base-100 shadow-xl border border-base-200">
                            <div class="card-body">
                                <h2 class="card-title text-bim-black-900">Arrangementer</h2>
                                <p class="text-3xl font-bold text-bim-orange my-2"><?php echo $registrations_count; ?></p>
                                <div class="card-actions justify-end mt-4">
                                    <a href="#arrangementer" class="btn btn-primary btn-sm">Se Events</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <?php if (!empty($upcoming_events)): ?>
                <div class="card-hjem mb-6">
                    <div class="card-body p-6">
                        <h2 class="text-2xl font-bold text-bim-black-900 mb-4">Kommende Arrangementer</h2>
                        <div class="space-y-4">
                            <?php foreach ($upcoming_events as $event): 
                                $dato = get_field('arrangement_dato', $event->ID);
                                $tid = get_field('arrangement_tid', $event->ID);
                                $sted = get_field('arrangement_sted', $event->ID);
                            ?>
                            <div class="flex items-start gap-4 p-4 bg-bim-beige-100 rounded-lg hover:bg-bim-beige-200 transition-colors">
                                <div class="flex-shrink-0 w-16 h-16 bg-bim-orange text-white rounded-lg flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold"><?php echo date('d', strtotime($dato)); ?></div>
                                    <div class="text-xs uppercase"><?php echo date('M', strtotime($dato)); ?></div>
                                </div>
                                <div class="flex-grow">
                                    <h3 class="font-bold text-lg text-bim-black-900"><?php echo esc_html($event->post_title); ?></h3>
                                    <div class="text-sm text-bim-black-700 mt-1">
                                        <?php if ($tid): ?>
                                            <span class="mr-4">🕐 <?php echo esc_html($tid); ?></span>
                                        <?php endif; ?>
                                        <?php if ($sted): ?>
                                            <span>📍 <?php echo esc_html($sted); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <a href="<?php echo get_permalink($event->ID); ?>" class="btn btn-sm btn-hjem-primary">Se detaljer</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>
</div>

<?php get_footer(); ?>
