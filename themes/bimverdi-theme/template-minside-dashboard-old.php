<!-- 
Template Name: Min Side Dashboard
Description: Dashboard for logged-in members using Web Awesome components
-->
<?php
/**
 * Template Name: Min Side Dashboard
 * 
 * Dashboard template for BIM Verdi members
 * Shows profile info, quick actions, upcoming events, and navigation
 * Uses Web Awesome components for modern UI
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

// Get user's tools
$my_tools = get_posts(array(
    'post_type' => 'verktoy',
    'author' => $user_id,
    'posts_per_page' => -1,
    'post_status' => array('publish', 'draft', 'pending'),
));
$my_tools_count = count($my_tools);

// Get user's ideas
$my_ideas = get_posts(array(
    'post_type' => 'case',
    'author' => $user_id,
    'posts_per_page' => -1,
));
$my_ideas_count = count($my_ideas);

// Get user's event registrations
$my_registrations = get_posts(array(
    'post_type' => 'pamelding',
    'meta_key' => 'pamelding_bruker',
    'meta_value' => $user_id,
    'posts_per_page' => -1,
));
$registrations_count = count($my_registrations);
?>

<div class="min-h-screen bg-gradient-to-b from-gray-50 to-white py-8">
    <div class="container mx-auto px-4 max-w-7xl">
        
        <!-- Success Message for Company Connection -->
        <?php if (isset($_GET['foretak_koblet']) && $_GET['foretak_koblet'] == '1'): ?>
            <wa-alert variant="success" open closable class="mb-6">
                <wa-icon slot="icon" name="circle-check" library="fa"></wa-icon>
                <strong>Du er nå koblet til et foretak!</strong><br>
                Du har full tilgang til alle funksjoner i BIM Verdi medlemsportal.
            </wa-alert>
        <?php endif; ?>

        <!-- Demo Banner -->
        <?php if ($is_demo): ?>
            <wa-alert variant="warning" open class="mb-6">
                <wa-icon slot="icon" name="triangle-exclamation" library="fa"></wa-icon>
                <strong>Demo-data vises</strong><br>
                Du ser demo-data fordi ingen faktisk bedrift er koblet ennå.
            </wa-alert>
        <?php endif; ?>
        
        <!-- Welcome Header -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">
                        Velkommen, <?php echo esc_html($current_user->first_name ?: $current_user->display_name); ?>!
                    </h1>
                    <?php if ($company_id): ?>
                        <?php $user_rolle = get_field('foretak_rolle', 'user_' . $user_id); ?>
                        <div class="flex flex-wrap items-center gap-2">
                            <wa-icon name="building" library="fa" class="text-orange-500"></wa-icon>
                            <span class="font-semibold text-gray-700"><?php echo esc_html($company->post_title); ?></span>
                            <?php if ($user_rolle): ?>
                                <wa-badge variant="brand"><?php echo esc_html($user_rolle); ?></wa-badge>
                            <?php endif; ?>
                            <?php if ($is_company_owner): ?>
                                <wa-badge variant="warning">Bedriftseier</wa-badge>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($company_id): ?>
                <div class="flex gap-2">
                    <wa-button variant="brand" href="<?php echo esc_url(home_url('/min-side/registrer-verktoy/')); ?>">
                        <wa-icon slot="prefix" name="plus" library="fa"></wa-icon>
                        Nytt verktøy
                    </wa-button>
                    <wa-button variant="neutral" outline href="<?php echo esc_url(get_permalink($company_id)); ?>">
                        <wa-icon slot="prefix" name="eye" library="fa"></wa-icon>
                        Se bedriftsprofil
                    </wa-button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Min Side Horizontal Tab Navigation -->
        <?php 
        $current_tab = 'dashboard';
        get_template_part('template-parts/minside-tabs', null, array('current_tab' => $current_tab));
        ?>

        <div class="mt-8">

            <!-- Welcome - Next Step: Connect to Company -->
            <?php if (!$company_id): ?>
            <wa-card class="mb-8 border-2 border-orange-200 bg-gradient-to-r from-orange-50 to-amber-50">
                <div class="flex flex-col md:flex-row md:items-center gap-6 p-2">
                    <div class="flex-shrink-0">
                        <wa-icon name="bolt" library="fa" style="font-size: 3rem; color: var(--wa-color-warning-600);"></wa-icon>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Velkommen til BIM Verdi! 🎉</h3>
                        <p class="text-gray-700 mb-1">
                            Din bruker er opprettet. Neste steg er å koble deg til et foretak for å få full tilgang til medlemsportalen.
                        </p>
                        <p class="text-sm text-gray-600">
                            💼 Velg ditt foretak fra listen, eller registrer et nytt hvis det ikke finnes ennå.
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <wa-button variant="warning" size="large" href="<?php echo esc_url(home_url('/koble-foretak/')); ?>">
                            <wa-icon slot="prefix" name="link" library="fa"></wa-icon>
                            Koble til Foretak
                            <wa-icon slot="suffix" name="arrow-right" library="fa"></wa-icon>
                        </wa-button>
                    </div>
                </div>
            </wa-card>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                
                <!-- Verktøy -->
                <wa-card class="text-center hover:shadow-lg transition-shadow">
                    <div class="p-4">
                        <wa-icon name="wrench" library="fa" style="font-size: 2rem; color: var(--wa-color-brand-600); margin-bottom: 0.5rem;"></wa-icon>
                        <div class="text-4xl font-bold text-gray-900 mb-1"><?php echo $my_tools_count; ?></div>
                        <div class="text-sm text-gray-600 mb-3">Verktøy registrert</div>
                        <wa-button variant="brand" size="small" href="<?php echo esc_url(home_url('/min-side/registrer-verktoy/')); ?>">
                            <wa-icon slot="prefix" name="plus" library="fa"></wa-icon>
                            Legg til
                        </wa-button>
                    </div>
                </wa-card>
                
                <!-- Idéer -->
                <wa-card class="text-center hover:shadow-lg transition-shadow">
                    <div class="p-4">
                        <wa-icon name="lightbulb" library="fa" style="font-size: 2rem; color: var(--wa-color-warning-500); margin-bottom: 0.5rem;"></wa-icon>
                        <div class="text-4xl font-bold text-gray-900 mb-1"><?php echo $my_ideas_count; ?></div>
                        <div class="text-sm text-gray-600 mb-3">Prosjektidéer</div>
                        <wa-button variant="warning" size="small">
                            <wa-icon slot="prefix" name="plus" library="fa"></wa-icon>
                            Send inn
                        </wa-button>
                    </div>
                </wa-card>
                
                <!-- Arrangementer -->
                <wa-card class="text-center hover:shadow-lg transition-shadow">
                    <div class="p-4">
                        <wa-icon name="calendar-check" library="fa" style="font-size: 2rem; color: var(--wa-color-success-600); margin-bottom: 0.5rem;"></wa-icon>
                        <div class="text-4xl font-bold text-gray-900 mb-1"><?php echo $registrations_count; ?></div>
                        <div class="text-sm text-gray-600 mb-3">Påmeldinger</div>
                        <wa-button variant="success" size="small" href="<?php echo esc_url(home_url('/min-side/arrangementer/')); ?>">
                            <wa-icon slot="prefix" name="eye" library="fa"></wa-icon>
                            Se alle
                        </wa-button>
                    </div>
                </wa-card>
                
                <!-- Profil -->
                <wa-card class="text-center hover:shadow-lg transition-shadow">
                    <div class="p-4">
                        <wa-icon name="building" library="fa" style="font-size: 2rem; color: var(--wa-color-neutral-600); margin-bottom: 0.5rem;"></wa-icon>
                        <div class="text-4xl font-bold text-gray-900 mb-1"><?php echo $profile_completion; ?>%</div>
                        <div class="text-sm text-gray-600 mb-3">Profil komplett</div>
                        <wa-button variant="neutral" size="small" outline href="<?php echo esc_url(home_url('/koble-foretak/')); ?>">
                            <wa-icon slot="prefix" name="pen" library="fa"></wa-icon>
                            Rediger
                        </wa-button>
                    </div>
                </wa-card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Quick Actions -->
                    <wa-card>
                        <div slot="header" class="flex items-center gap-2">
                            <wa-icon name="bolt" library="fa"></wa-icon>
                            <strong>Hurtighandlinger</strong>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <a href="<?php echo esc_url(home_url('/min-side/registrer-verktoy/')); ?>" class="group flex items-center gap-4 p-4 rounded-lg border border-gray-200 hover:border-orange-300 hover:bg-orange-50 transition-all">
                                <div class="w-12 h-12 rounded-lg bg-orange-100 flex items-center justify-center group-hover:bg-orange-200 transition-colors">
                                    <wa-icon name="wrench" library="fa" style="font-size: 1.5rem; color: var(--wa-color-brand-600);"></wa-icon>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900">Registrer verktøy</div>
                                    <div class="text-sm text-gray-600">Del programvare med nettverket</div>
                                </div>
                            </a>
                            
                            <a href="<?php echo esc_url(home_url('/verktoy/')); ?>" class="group flex items-center gap-4 p-4 rounded-lg border border-gray-200 hover:border-purple-300 hover:bg-purple-50 transition-all">
                                <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                                    <wa-icon name="magnifying-glass" library="fa" style="font-size: 1.5rem; color: var(--wa-color-brand-600);"></wa-icon>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900">Utforsk verktøy</div>
                                    <div class="text-sm text-gray-600">Finn nye BIM-verktøy</div>
                                </div>
                            </a>
                            
                            <a href="<?php echo esc_url(home_url('/min-side/temagrupper/')); ?>" class="group flex items-center gap-4 p-4 rounded-lg border border-gray-200 hover:border-green-300 hover:bg-green-50 transition-all">
                                <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center group-hover:bg-green-200 transition-colors">
                                    <wa-icon name="users" library="fa" style="font-size: 1.5rem; color: var(--wa-color-success-600);"></wa-icon>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900">Temagrupper</div>
                                    <div class="text-sm text-gray-600">Bli med i faggrupper</div>
                                </div>
                            </a>
                            
                            <a href="<?php echo esc_url(home_url('/rediger-bruker/')); ?>" class="group flex items-center gap-4 p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all">
                                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                                    <wa-icon name="user-pen" library="fa" style="font-size: 1.5rem; color: #3b82f6;"></wa-icon>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900">Rediger profil</div>
                                    <div class="text-sm text-gray-600">Oppdater din informasjon</div>
                                </div>
                            </a>
                        </div>
                    </wa-card>

                    <!-- My Recent Tools -->
                    <?php if ($my_tools_count > 0): ?>
                    <wa-card>
                        <div slot="header" class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <wa-icon name="wrench" library="fa"></wa-icon>
                                <strong>Mine verktøy</strong>
                            </div>
                            <wa-button variant="text" size="small" href="<?php echo esc_url(home_url('/min-side/mine-verktoy/')); ?>">
                                Se alle
                                <wa-icon slot="suffix" name="arrow-right" library="fa"></wa-icon>
                            </wa-button>
                        </div>
                        
                        <div class="space-y-3">
                            <?php 
                            $recent_tools = array_slice($my_tools, 0, 3);
                            foreach ($recent_tools as $tool): 
                                $tool_status = get_post_status($tool->ID);
                                $status_variant = $tool_status === 'publish' ? 'success' : 'warning';
                                $status_label = $tool_status === 'publish' ? 'Publisert' : 'Kladd';
                            ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <wa-avatar initials="<?php echo esc_attr(substr($tool->post_title, 0, 2)); ?>" style="--size: 2.5rem;"></wa-avatar>
                                    <div>
                                        <div class="font-semibold text-gray-900"><?php echo esc_html($tool->post_title); ?></div>
                                        <wa-badge variant="<?php echo $status_variant; ?>" size="small"><?php echo $status_label; ?></wa-badge>
                                    </div>
                                </div>
                                <wa-button variant="text" size="small" href="<?php echo get_permalink($tool->ID); ?>">
                                    <wa-icon name="eye" library="fa"></wa-icon>
                                </wa-button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </wa-card>
                    <?php endif; ?>

                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    
                    <!-- Upcoming Events -->
                    <wa-card>
                        <div slot="header" class="flex items-center gap-2">
                            <wa-icon name="calendar-days" library="fa"></wa-icon>
                            <strong>Kommende arrangementer</strong>
                        </div>
                        
                        <?php if (!empty($upcoming_events)): ?>
                        <div class="space-y-4">
                            <?php foreach ($upcoming_events as $event): 
                                $dato = get_field('arrangement_dato', $event->ID);
                                $tid = get_field('arrangement_tid', $event->ID);
                                $sted = get_field('arrangement_sted', $event->ID);
                            ?>
                            <a href="<?php echo get_permalink($event->ID); ?>" class="block p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex gap-3">
                                    <div class="flex-shrink-0 w-14 h-14 bg-orange-500 text-white rounded-lg flex flex-col items-center justify-center">
                                        <div class="text-lg font-bold leading-none"><?php echo date('d', strtotime($dato)); ?></div>
                                        <div class="text-xs uppercase"><?php echo date('M', strtotime($dato)); ?></div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-gray-900 truncate"><?php echo esc_html($event->post_title); ?></div>
                                        <div class="text-sm text-gray-600">
                                            <?php if ($tid): ?>
                                                <wa-icon name="clock" library="fa" style="font-size: 0.75rem;"></wa-icon>
                                                <?php echo esc_html($tid); ?>
                                            <?php endif; ?>
                                            <?php if ($sted): ?>
                                                <span class="ml-2">
                                                    <wa-icon name="location-dot" library="fa" style="font-size: 0.75rem;"></wa-icon>
                                                    <?php echo esc_html($sted); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <div slot="footer">
                            <wa-button variant="neutral" outline class="w-full" href="<?php echo esc_url(home_url('/min-side/arrangementer/')); ?>">
                                Se alle arrangementer
                            </wa-button>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-6 text-gray-500">
                            <wa-icon name="calendar-xmark" library="fa" style="font-size: 2rem; margin-bottom: 0.5rem;"></wa-icon>
                            <p>Ingen kommende arrangementer</p>
                        </div>
                        <?php endif; ?>
                    </wa-card>

                    <!-- Company Card -->
                    <?php if ($company): ?>
                    <wa-card class="bg-gradient-to-br from-gray-50 to-gray-100">
                        <div slot="header" class="flex items-center gap-2">
                            <wa-icon name="building" library="fa"></wa-icon>
                            <strong>Ditt foretak</strong>
                        </div>
                        
                        <div class="flex items-center gap-4 mb-4">
                            <wa-avatar initials="<?php echo esc_attr(substr($company->post_title, 0, 2)); ?>" style="--size: 4rem; font-size: 1.25rem;"></wa-avatar>
                            <div>
                                <div class="font-bold text-lg text-gray-900"><?php echo esc_html($company->post_title); ?></div>
                                <wa-badge variant="success" size="small">
                                    <wa-icon slot="prefix" name="check" library="fa"></wa-icon>
                                    BIM Verdi-medlem
                                </wa-badge>
                            </div>
                        </div>
                        
                        <div slot="footer" class="flex gap-2">
                            <wa-button variant="brand" class="flex-1" href="<?php echo esc_url(get_permalink($company_id)); ?>">
                                <wa-icon slot="prefix" name="eye" library="fa"></wa-icon>
                                Se profil
                            </wa-button>
                            <wa-button variant="neutral" outline href="<?php echo esc_url(home_url('/koble-foretak/')); ?>">
                                <wa-icon name="pen" library="fa"></wa-icon>
                            </wa-button>
                        </div>
                    </wa-card>
                    <?php endif; ?>

                    <!-- Help Card -->
                    <wa-card>
                        <div slot="header" class="flex items-center gap-2">
                            <wa-icon name="circle-question" library="fa"></wa-icon>
                            <strong>Trenger du hjelp?</strong>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">
                            Kontakt oss hvis du har spørsmål om medlemsportalen eller BIM Verdi-nettverket.
                        </p>
                        <wa-button variant="neutral" outline class="w-full" href="mailto:post@bimverdi.no">
                            <wa-icon slot="prefix" name="envelope" library="fa"></wa-icon>
                            Kontakt support
                        </wa-button>
                    </wa-card>

                </div>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>
