<!-- 
Template Name: Koble Foretak
Description: Page for linking user to a company
-->
<?php
/**
 * Template Name: Koble Foretak
 * 
 * Template for users to select and link to an existing company
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
$current_company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

// Handle form submission
if (isset($_POST['koble_foretak_nonce']) && wp_verify_nonce($_POST['koble_foretak_nonce'], 'koble_foretak_action')) {
    $selected_company_id = intval($_POST['selected_company']);
    
    if ($selected_company_id > 0) {
        // Update user meta with company ID
        update_user_meta($user_id, 'bim_verdi_company_id', $selected_company_id);
        
        // Also update ACF field if it exists
        if (function_exists('update_field')) {
            update_field('tilknyttet_foretak', $selected_company_id, 'user_' . $user_id);
        }
        
        // Redirect to dashboard with success message
        wp_redirect(add_query_arg('foretak_koblet', '1', home_url('/min-side/')));
        exit;
    }
}

// Get all published companies
$args = array(
    'post_type' => 'foretak',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
);

$companies = get_posts($args);

// Get search term if exists
$search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
if ($search_term) {
    $args['s'] = $search_term;
    $companies = get_posts($args);
}
?>

<!-- Min Side Horizontal Tab Navigation -->
<?php 
$current_tab = 'foretak';
get_template_part('template-parts/minside-tabs', null, array('current_tab' => $current_tab));
?>

<div class="min-h-screen bg-bim-beige-100 py-12">
    <div class="container mx-auto px-4 max-w-5xl">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-bim-black-900 mb-4">
                <?php if ($current_company_id): ?>
                    Foretak Status
                <?php else: ?>
                    Koble til Foretak
                <?php endif; ?>
            </h1>
            <?php if (!$current_company_id): ?>
                <p class="text-lg text-bim-black-700 max-w-2xl mx-auto">
                    Velg et eksisterende foretak fra listen nedenfor. Hvis ditt foretak ikke er registrert ennå, 
                    kan du opprette det ved å klikke på "Registrer Nytt Foretak".
                </p>
            <?php else: ?>
                <p class="text-lg text-bim-black-700 max-w-2xl mx-auto">
                    Her ser du hvilken bedrift du er tilknyttet i BIM Verdi medlemsportal.
                </p>
            <?php endif; ?>
        </div>

        <!-- Current Status -->
        <?php if ($current_company_id): ?>
            <?php $current_company = get_post($current_company_id); ?>
            <div class="card-hjem mb-6">
                <div class="card-body p-8">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-bim-black-900 mb-2">Du er koblet til et foretak</h2>
                        <p class="text-lg text-bim-black-700 mb-4">
                            Du er for øyeblikket koblet til: <strong><?php echo esc_html($current_company->post_title); ?></strong>
                        </p>
                        <div class="flex gap-4 justify-center mt-6">
                            <a href="<?php echo esc_url(home_url('/min-side/')); ?>" class="btn btn-hjem-primary">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                                Gå til Min Side
                            </a>
                            <a href="<?php echo esc_url(get_permalink(33)); ?>" class="btn btn-outline">
                                Se Foretaksinformasjon
                            </a>
                        </div>
                        <p class="text-sm text-bim-black-600 mt-6">
                            Hvis du ønsker å bytte foretak, kontakt support.
                        </p>
                    </div>
                </div>
            </div>
        <?php else: ?>

        <!-- Register New Company CTA -->
        <div class="card-hjem mb-8 bg-gradient-to-r from-bim-orange to-bim-purple text-white">
            <div class="card-body p-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">Finner du ikke ditt foretak?</h2>
                        <p class="text-white text-opacity-90">
                            Registrer et nytt foretak og bli hovedkontaktperson for din organisasjon.
                        </p>
                    </div>
                    <a href="<?php echo esc_url(get_permalink(33)); ?>" class="btn btn-lg bg-white text-bim-orange hover:bg-bim-beige-100 border-0 whitespace-nowrap">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Registrer Nytt Foretak
                    </a>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="card-hjem mb-6">
            <div class="card-body p-6">
                <form method="get" class="flex gap-4">
                    <input 
                        type="text" 
                        name="s" 
                        value="<?php echo esc_attr($search_term); ?>" 
                        placeholder="Søk etter foretak..." 
                        class="input input-bordered flex-grow"
                    />
                    <button type="submit" class="btn btn-hjem-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Søk
                    </button>
                    <?php if ($search_term): ?>
                        <a href="<?php echo esc_url(get_permalink()); ?>" class="btn btn-outline">Tilbakestill</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Companies List -->
        <?php endif; // End of else for current_company_id check ?>
        
        <?php if (!$current_company_id): // Only show list if not connected ?>
        <?php if (empty($companies)): ?>
            <div class="card-hjem">
                <div class="card-body p-12 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-bim-black-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <h3 class="text-xl font-bold text-bim-black-700 mb-2">Ingen foretak funnet</h3>
                    <p class="text-bim-black-600 mb-4">
                        <?php if ($search_term): ?>
                            Ingen foretak samsvarer med søket "<?php echo esc_html($search_term); ?>".
                        <?php else: ?>
                            Det finnes ingen foretak registrert ennå.
                        <?php endif; ?>
                    </p>
                    <a href="<?php echo esc_url(get_permalink(33)); ?>" class="btn btn-hjem-primary">
                        Registrer Første Foretak
                    </a>
                </div>
            </div>
        <?php else: ?>
            <form method="post" id="select-company-form">
                <?php wp_nonce_field('koble_foretak_action', 'koble_foretak_nonce'); ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <?php foreach ($companies as $company): 
                        $logo = get_field('logo', $company->ID);
                        $beskrivelse = get_field('beskrivelse', $company->ID);
                        $org_nummer = get_field('organisasjonsnummer', $company->ID);
                        $nettside = get_field('nettside', $company->ID);
                        $is_current = ($company->ID == $current_company_id);
                    ?>
                    <label class="cursor-pointer">
                        <input 
                            type="radio" 
                            name="selected_company" 
                            value="<?php echo $company->ID; ?>" 
                            class="hidden company-radio"
                            <?php checked($is_current); ?>
                        />
                        <div class="card-hjem company-card transition-all duration-200 hover:shadow-xl <?php echo $is_current ? 'ring-4 ring-bim-orange' : ''; ?>">
                            <div class="card-body p-6">
                                <div class="flex items-start gap-4">
                                    <!-- Logo -->
                                    <div class="flex-shrink-0 w-20 h-20 bg-bim-beige-200 rounded-lg flex items-center justify-center overflow-hidden">
                                        <?php if ($logo): ?>
                                            <img src="<?php echo esc_url($logo['url']); ?>" alt="<?php echo esc_attr($company->post_title); ?>" class="w-full h-full object-cover" />
                                        <?php else: ?>
                                            <svg class="w-10 h-10 text-bim-black-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Content -->
                                    <div class="flex-grow">
                                        <h3 class="text-xl font-bold text-bim-black-900 mb-2">
                                            <?php echo esc_html($company->post_title); ?>
                                            <?php if ($is_current): ?>
                                                <span class="badge badge-sm bg-bim-orange text-white border-0 ml-2">Nåværende</span>
                                            <?php endif; ?>
                                        </h3>
                                        
                                        <?php if ($org_nummer): ?>
                                            <p class="text-sm text-bim-black-600 mb-2">
                                                Org.nr: <?php echo esc_html($org_nummer); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if ($beskrivelse): ?>
                                            <p class="text-sm text-bim-black-700 mb-3 line-clamp-2">
                                                <?php echo esc_html(wp_trim_words($beskrivelse, 20)); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if ($nettside): ?>
                                            <a href="<?php echo esc_url($nettside); ?>" target="_blank" class="text-sm text-bim-orange hover:underline" onclick="event.stopPropagation();">
                                                Besøk nettside →
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Checkmark -->
                                    <div class="flex-shrink-0 company-checkmark <?php echo $is_current ? '' : 'hidden'; ?>">
                                        <svg class="w-8 h-8 text-bim-orange" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <!-- Submit Button -->
                <div class="text-center">
                    <button type="submit" class="btn btn-hjem-primary btn-lg" id="submit-btn" disabled>
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Koble til Valgt Foretak
                    </button>
                    <p class="text-sm text-bim-black-600 mt-4">
                        <a href="<?php echo esc_url(home_url('/min-side/')); ?>" class="text-bim-orange hover:underline">
                            ← Tilbake til Min Side
                        </a>
                    </p>
                </div>
            </form>
        <?php endif; ?>
        <?php endif; // End of if (!$current_company_id) check ?>

    </div>
</div>

<?php if (!$current_company_id): // Only load JS if company list is shown ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('.company-radio');
    const submitBtn = document.getElementById('submit-btn');
    const companyCards = document.querySelectorAll('.company-card');
    
    radios.forEach(function(radio, index) {
        radio.addEventListener('change', function() {
            // Enable submit button
            submitBtn.disabled = false;
            
            // Update visual state of all cards
            companyCards.forEach(function(card) {
                card.classList.remove('ring-4', 'ring-bim-orange');
                card.querySelector('.company-checkmark').classList.add('hidden');
            });
            
            // Highlight selected card
            if (radio.checked) {
                companyCards[index].classList.add('ring-4', 'ring-bim-orange');
                companyCards[index].querySelector('.company-checkmark').classList.remove('hidden');
            }
        });
        
        // Make the entire card clickable
        companyCards[index].addEventListener('click', function() {
            radio.checked = true;
            radio.dispatchEvent(new Event('change'));
        });
    });
});
</script>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
<?php endif; // End of JavaScript/CSS section ?>

<?php get_footer(); ?>
