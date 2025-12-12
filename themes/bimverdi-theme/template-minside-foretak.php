<?php
/**
 * Template Name: Min Side - Foretak
 * 
 * Template for users to view/link to a company
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

// Get company ID - try multiple sources
$current_company_id = get_user_meta($user_id, 'bimverdi_company_id', true);
if (empty($current_company_id)) {
    $current_company_id = get_user_meta($user_id, 'bim_verdi_company_id', true); // Legacy
}
if (empty($current_company_id) && function_exists('get_field')) {
    $current_company_id = get_field('tilknyttet_foretak', 'user_' . $user_id); // ACF
}

// Check if user is hovedkontakt for their company
$is_hovedkontakt = false;
if ($current_company_id) {
    $hovedkontakt_id = get_field('hovedkontaktperson', $current_company_id);
    $is_hovedkontakt = ($hovedkontakt_id == $user_id);
}

// Get all published companies (medlemsbedrifter)
$companies = get_posts(array(
    'post_type' => 'medlemsbedrift',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
));

// Fallback to 'foretak' post type if no medlemsbedrift found
if (empty($companies)) {
    $companies = get_posts(array(
        'post_type' => 'foretak',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
    ));
}

// Get search term if exists
$search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Start Min Side layout
get_template_part('template-parts/minside-layout-start', null, array(
    'current_page' => 'foretak',
    'page_title' => 'Foretak',
    'page_icon' => 'building',
    'page_description' => $current_company_id 
        ? 'Din foretakstilknytning i BIM Verdi' 
        : 'Finn ditt foretak og be om invitasjon',
));
?>

<?php if ($current_company_id): ?>
    <!-- Current Company Status -->
    <?php $current_company = get_post($current_company_id); ?>
    <?php if ($current_company): ?>
    <wa-card class="mb-6 border-2 border-green-200">
        <div class="p-6">
            <div class="flex items-start gap-6">
                <!-- Company Logo -->
                <div class="flex-shrink-0">
                    <?php $logo = get_field('logo', $current_company_id); ?>
                    <?php if ($logo): ?>
                        <img src="<?php echo esc_url($logo['url']); ?>" alt="<?php echo esc_attr($current_company->post_title); ?>" class="w-24 h-24 object-cover rounded-lg" />
                    <?php else: ?>
                        <div class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center">
                            <wa-icon name="building" library="fa" style="font-size: 2.5rem; color: var(--wa-color-neutral-400);"></wa-icon>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Company Info -->
                <div class="flex-grow">
                    <div class="flex items-center gap-3 mb-2">
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo esc_html($current_company->post_title); ?></h2>
                        <wa-badge variant="success">
                            <wa-icon slot="prefix" name="check" library="fa"></wa-icon>
                            Tilkoblet
                        </wa-badge>
                    </div>
                    
                    <?php $org_nummer = get_field('organisasjonsnummer', $current_company_id); ?>
                    <?php if ($org_nummer): ?>
                        <p class="text-sm text-gray-600 mb-2">Org.nr: <?php echo esc_html($org_nummer); ?></p>
                    <?php endif; ?>
                    
                    <?php $beskrivelse = get_field('beskrivelse', $current_company_id); ?>
                    <?php if ($beskrivelse): ?>
                        <p class="text-gray-700 mb-4"><?php echo esc_html(wp_trim_words($beskrivelse, 30)); ?></p>
                    <?php endif; ?>
                    
                    <div class="flex gap-3">
                        <wa-button variant="brand" href="<?php echo esc_url(get_permalink($current_company_id)); ?>">
                            <wa-icon slot="prefix" name="eye" library="fa"></wa-icon>
                            Se foretaksprofil
                        </wa-button>
                        <wa-button variant="neutral" outline href="<?php echo esc_url(home_url('/min-side/rediger-foretak/')); ?>">
                            <wa-icon slot="prefix" name="pen" library="fa"></wa-icon>
                            Rediger
                        </wa-button>
                    </div>
                </div>
            </div>
        </div>
    </wa-card>

    <!-- Company Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Contact Info -->
        <wa-card>
            <div slot="header" class="flex items-center gap-2">
                <wa-icon name="address-card" library="fa"></wa-icon>
                <strong>Kontaktinformasjon</strong>
            </div>
            <div class="space-y-3 p-1">
                <?php $adresse = get_field('adresse', $current_company_id); ?>
                <?php $postnummer = get_field('postnummer', $current_company_id); ?>
                <?php $poststed = get_field('poststed', $current_company_id); ?>
                <?php if ($adresse || $postnummer || $poststed): ?>
                <div class="flex items-start gap-3">
                    <wa-icon name="location-dot" library="fa" class="text-gray-400 mt-1"></wa-icon>
                    <div>
                        <?php if ($adresse): ?><div><?php echo esc_html($adresse); ?></div><?php endif; ?>
                        <?php if ($postnummer || $poststed): ?>
                            <div><?php echo esc_html($postnummer); ?> <?php echo esc_html($poststed); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php $telefon = get_field('telefon', $current_company_id); ?>
                <?php if ($telefon): ?>
                <div class="flex items-center gap-3">
                    <wa-icon name="phone" library="fa" class="text-gray-400"></wa-icon>
                    <span><?php echo esc_html($telefon); ?></span>
                </div>
                <?php endif; ?>
                
                <?php $nettside = get_field('nettside', $current_company_id); ?>
                <?php if ($nettside): ?>
                <div class="flex items-center gap-3">
                    <wa-icon name="globe" library="fa" class="text-gray-400"></wa-icon>
                    <a href="<?php echo esc_url($nettside); ?>" target="_blank" class="text-orange-600 hover:underline">
                        <?php echo esc_html(preg_replace('#^https?://#', '', $nettside)); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </wa-card>

        <!-- Categories -->
        <wa-card>
            <div slot="header" class="flex items-center gap-2">
                <wa-icon name="tags" library="fa"></wa-icon>
                <strong>Kategorier</strong>
            </div>
            <div class="space-y-4 p-1">
                <?php $bransjekategorier = get_the_terms($current_company_id, 'bransjekategori'); ?>
                <?php if ($bransjekategorier && !is_wp_error($bransjekategorier)): ?>
                <div>
                    <div class="text-sm text-gray-500 mb-2">Bransje</div>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($bransjekategorier as $term): ?>
                            <wa-tag><?php echo esc_html($term->name); ?></wa-tag>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php $kundetyper = get_the_terms($current_company_id, 'kundetype'); ?>
                <?php if ($kundetyper && !is_wp_error($kundetyper)): ?>
                <div>
                    <div class="text-sm text-gray-500 mb-2">Kundetype</div>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($kundetyper as $term): ?>
                            <wa-tag variant="neutral"><?php echo esc_html($term->name); ?></wa-tag>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </wa-card>
        
    </div>

    <div class="mt-6 pt-6 border-t border-gray-200">
        <p class="text-sm text-gray-500">
            <wa-icon name="circle-info" library="fa" class="mr-1"></wa-icon>
            Hvis du ønsker å bytte foretak, kontakt <a href="mailto:post@bimverdi.no" class="text-orange-600 hover:underline">support</a>.
        </p>
    </div>

    <?php endif; ?>

<?php else: ?>
    <!-- No Company Connected - Show Selection -->
    
    <!-- Register New Company CTA -->
    <wa-card class="mb-6 bg-gradient-to-r from-orange-500 to-purple-600 text-white">
        <div class="p-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold mb-2">Finner du ikke ditt foretak?</h2>
                    <p class="text-white/90">
                        Registrer et nytt foretak og bli hovedkontaktperson for din organisasjon.
                    </p>
                </div>
                <wa-button variant="neutral" size="large" href="<?php echo esc_url(home_url('/registrer-foretak/')); ?>" style="background: white; color: #ea580c;">
                    <wa-icon slot="prefix" name="plus" library="fa"></wa-icon>
                    Registrer nytt foretak
                </wa-button>
            </div>
        </div>
    </wa-card>

    <!-- Search Bar -->
    <wa-card class="mb-6">
        <div class="p-4">
            <form method="get" class="flex gap-4">
                <input 
                    type="text" 
                    name="s" 
                    value="<?php echo esc_attr($search_term); ?>" 
                    placeholder="Søk etter foretak..." 
                    class="flex-grow px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                />
                <wa-button type="submit" variant="brand">
                    <wa-icon slot="prefix" name="magnifying-glass" library="fa"></wa-icon>
                    Søk
                </wa-button>
                <?php if ($search_term): ?>
                    <wa-button variant="neutral" outline href="<?php echo esc_url(get_permalink()); ?>">
                        Nullstill
                    </wa-button>
                <?php endif; ?>
            </form>
        </div>
    </wa-card>

    <!-- Companies List -->
    <?php if (empty($companies)): ?>
        <wa-card class="text-center py-12">
            <div class="flex flex-col items-center">
                <wa-icon name="building" library="fa" style="font-size: 4rem; color: var(--wa-color-neutral-300); margin-bottom: 1rem;"></wa-icon>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Ingen foretak funnet</h3>
                <p class="text-gray-600 mb-4">
                    <?php if ($search_term): ?>
                        Ingen foretak samsvarer med søket "<?php echo esc_html($search_term); ?>".
                    <?php else: ?>
                        Det finnes ingen foretak registrert ennå.
                    <?php endif; ?>
                </p>
                <wa-button variant="brand" href="<?php echo esc_url(home_url('/registrer-foretak/')); ?>">
                    Registrer første foretak
                </wa-button>
            </div>
        </wa-card>
    <?php else: ?>
        <!-- Info Box about invite-only -->
        <wa-alert variant="primary" class="mb-6">
            <wa-icon slot="icon" name="circle-info" library="fa"></wa-icon>
            <strong>Slik blir du koblet til et foretak:</strong><br>
            For å bli koblet til et foretak må du kontakte hovedkontaktpersonen og be om en invitasjon. 
            Når du mottar invitasjonen på e-post, følger du lenken for å fullføre koblingen.
        </wa-alert>

        <!-- Companies List (view-only) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <?php foreach ($companies as $company): 
                $logo = get_field('logo', $company->ID);
                $beskrivelse = get_field('beskrivelse', $company->ID);
                $org_nummer = get_field('organisasjonsnummer', $company->ID);
                $hovedkontakt_id = get_field('hovedkontaktperson', $company->ID);
                $hovedkontakt = $hovedkontakt_id ? get_userdata($hovedkontakt_id) : null;
                $kontakt_epost = get_field('kontakt_epost', $company->ID);
                $er_aktiv_deltaker = get_field('er_aktiv_deltaker', $company->ID);
            ?>
            <wa-card class="h-full">
                <div class="p-5">
                    <div class="flex items-start gap-4">
                        <!-- Logo -->
                        <div class="flex-shrink-0 w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                            <?php if ($logo): ?>
                                <img src="<?php echo esc_url($logo['url']); ?>" alt="<?php echo esc_attr($company->post_title); ?>" class="w-full h-full object-cover" />
                            <?php else: ?>
                                <wa-icon name="building" library="fa" style="font-size: 1.5rem; color: var(--wa-color-neutral-400);"></wa-icon>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Content -->
                        <div class="flex-grow min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-lg font-bold text-gray-900 truncate">
                                    <?php echo esc_html($company->post_title); ?>
                                </h3>
                                <?php if ($er_aktiv_deltaker): ?>
                                    <wa-badge variant="success" size="small">Deltaker</wa-badge>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($org_nummer): ?>
                                <p class="text-sm text-gray-500 mb-1">Org.nr: <?php echo esc_html($org_nummer); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($beskrivelse): ?>
                                <p class="text-sm text-gray-600 line-clamp-2 mb-3">
                                    <?php echo esc_html(wp_trim_words($beskrivelse, 15)); ?>
                                </p>
                            <?php endif; ?>
                            
                            <!-- Hovedkontakt info and invite request -->
                            <?php if ($hovedkontakt): ?>
                                <div class="mt-3 pt-3 border-t border-gray-100">
                                    <p class="text-xs text-gray-500 mb-2">
                                        <wa-icon name="user" library="fa" class="mr-1"></wa-icon>
                                        Hovedkontakt: <?php echo esc_html($hovedkontakt->display_name); ?>
                                    </p>
                                    <?php 
                                    $invite_email = $hovedkontakt->user_email;
                                    $subject = rawurlencode('Forespørsel om invitasjon til ' . $company->post_title . ' i BIM Verdi');
                                    $body = rawurlencode("Hei,\n\nJeg ønsker å bli koblet til {$company->post_title} i BIM Verdi-portalen.\n\nMitt navn: {$current_user->display_name}\nMin e-post: {$current_user->user_email}\n\nKan du sende meg en invitasjon?\n\nMed vennlig hilsen,\n{$current_user->display_name}");
                                    ?>
                                    <wa-button variant="brand" size="small" href="mailto:<?php echo esc_attr($invite_email); ?>?subject=<?php echo $subject; ?>&body=<?php echo $body; ?>">
                                        <wa-icon slot="prefix" name="envelope" library="fa"></wa-icon>
                                        Be om invitasjon
                                    </wa-button>
                                </div>
                            <?php elseif ($kontakt_epost): ?>
                                <div class="mt-3 pt-3 border-t border-gray-100">
                                    <?php 
                                    $subject = rawurlencode('Forespørsel om invitasjon til ' . $company->post_title . ' i BIM Verdi');
                                    $body = rawurlencode("Hei,\n\nJeg ønsker å bli koblet til {$company->post_title} i BIM Verdi-portalen.\n\nMitt navn: {$current_user->display_name}\nMin e-post: {$current_user->user_email}\n\nKan dere sende meg en invitasjon?\n\nMed vennlig hilsen,\n{$current_user->display_name}");
                                    ?>
                                    <wa-button variant="brand" size="small" href="mailto:<?php echo esc_attr($kontakt_epost); ?>?subject=<?php echo $subject; ?>&body=<?php echo $body; ?>">
                                        <wa-icon slot="prefix" name="envelope" library="fa"></wa-icon>
                                        Be om invitasjon
                                    </wa-button>
                                </div>
                            <?php else: ?>
                                <div class="mt-3 pt-3 border-t border-gray-100">
                                    <p class="text-xs text-gray-400 italic">
                                        <wa-icon name="circle-info" library="fa" class="mr-1"></wa-icon>
                                        Kontaktinformasjon ikke tilgjengelig
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </wa-card>
            <?php endforeach; ?>
        </div>

        <!-- Back button -->
        <div class="flex gap-3 pt-4 border-t border-gray-200">
            <wa-button variant="neutral" outline href="<?php echo esc_url(home_url('/min-side/')); ?>">
                <wa-icon slot="prefix" name="arrow-left" library="fa"></wa-icon>
                Tilbake til Min Side
            </wa-button>
        </div>
    <?php endif; ?>

<?php endif; ?>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php 
get_template_part('template-parts/minside-layout-end');
get_footer(); 
?>
