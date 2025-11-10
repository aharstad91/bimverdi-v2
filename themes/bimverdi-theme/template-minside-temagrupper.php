<?php
/**
 * Template Name: Min Side - Temagrupper
 * 
 * Allows members to manage their theme group participation
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

// Demo mode if no company
$is_demo = empty($company_id);
if ($is_demo) {
    $company_id = 0; // Use dummy ID for demo
}

if (!$is_demo && !$company_id) {
    echo '<div class="min-h-screen bg-bim-beige-100 py-8"><div class="container mx-auto px-4"><div class="alert alert-error">Du er ikke tilknyttet en bedrift.</div></div></div>';
    get_footer();
    exit;
}

// Handle form submission
$message = '';
if (!$is_demo && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bim_verdi_update_temagrupper'])) {
    if (!isset($_POST['bim_verdi_nonce']) || !wp_verify_nonce($_POST['bim_verdi_nonce'], 'bim_verdi_temagrupper')) {
        wp_die('Sikkerhetskontroll feilet');
    }

    $temagrupper = isset($_POST['temagrupper']) ? array_map('intval', (array)$_POST['temagrupper']) : array();
    wp_set_post_terms($company_id, $temagrupper, 'temagruppe');
    $message = 'Temagruppene ble oppdatert!';
}

// Get current theme groups
if ($is_demo) {
    // Use mock data for demo
    $all_temagrupper = array_map(function($group) {
        $term = new stdClass();
        $term->term_id = 0;
        $term->name = $group['name'];
        $term->slug = sanitize_title($group['name']);
        return $term;
    }, bimverdi_get_mock_theme_groups());
    
    $selected_temagrupper = array_keys(array_filter(bimverdi_get_mock_theme_groups(), function($g) {
        return $g['selected'];
    }));
} else {
    $all_temagrupper = get_terms(array('taxonomy' => 'temagruppe', 'hide_empty' => false));
    $selected_temagrupper = wp_get_post_terms($company_id, 'temagruppe', array('fields' => 'ids'));
}

// Theme group descriptions
$temagruppe_info = array(
    'ByggesaksBIM' => 'Fokus på byggesaksprosesser og kommunal saksbehandling med digitale verktøy',
    'ProsjektBIM' => 'Koordinering i design- og byggefase med 3D-modeller og samarbeid',
    'EiendomsBIM' => 'Drift, forvaltning og livssyklus-management av eiendom',
    'MiljøBIM' => 'Miljø- og klimaberegninger, energieffektivitet og bærekraft',
    'SirkBIM' => 'Sirkulær økonomi, materialpass og gjenbruk',
    'BIMtech' => 'Teknologi, AI, machine learning og nye digitale løsninger',
);
?>

<!-- Min Side Horizontal Tab Navigation -->
<?php 
$current_tab = 'temagrupper';
get_template_part('template-parts/minside-tabs', null, array('current_tab' => $current_tab));
?>

<div class="min-h-screen bg-bim-beige-100 py-8">
    <div class="container mx-auto px-4">
        
        <!-- Demo Banner -->
        <?php if ($is_demo): ?>
            <div class="alert alert-warning shadow-lg mb-6 bg-alert text-black">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0-11l6.364 3.682a2 2 0 010 3.464L12 21l-6.364-3.682a2 2 0 010-3.464L12 2z"></path>
                    </svg>
                    <div>
                        <h3 class="font-bold">Demo-data vises</h3>
                        <div class="text-sm">Du ser demo-data fordi ingen faktisk bedrift er opprettet ennå. Endringer lagres ikke i demo-modus.</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-bim-black-900">Temagrupper</h1>
        </div>

        <!-- Main Content (Full Width) -->
        <div>

            
            <!-- Main Content -->
            
                
                <?php if ($message): ?>
                <div class="alert alert-success mb-6">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><?php echo esc_html($message); ?></span>
                </div>
                <?php endif; ?>

                <div class="card-hjem">
                    <div class="card-body p-6">
                        <h1 class="text-3xl font-bold text-bim-black-900 mb-2">Temagrupper</h1>
                        <p class="text-bim-black-700 mb-6">Velg hvilke temagrupper din bedrift deltar i. Du får da tilgang til ressurser, arrangementer og nettverk innen disse områdene.</p>

                        <form method="POST">
                            <?php wp_nonce_field('bim_verdi_temagrupper', 'bim_verdi_nonce'); ?>
                            <input type="hidden" name="bim_verdi_update_temagrupper" value="1">

                            <div class="grid grid-cols-1 gap-4 mb-6">
                                <?php foreach ($all_temagrupper as $term): 
                                    $is_selected = in_array($term->term_id, $selected_temagrupper);
                                    $info = $temagruppe_info[$term->name] ?? $term->description;
                                ?>
                                <label class="flex items-start gap-4 p-5 bg-bim-beige-100 rounded-lg hover:bg-bim-beige-200 transition-colors cursor-pointer border-2 <?php echo $is_selected ? 'border-bim-orange' : 'border-transparent'; ?>">
                                    <input 
                                        type="checkbox" 
                                        name="temagrupper[]" 
                                        value="<?php echo $term->term_id; ?>"
                                        <?php checked($is_selected); ?>
                                        class="checkbox checkbox-hjem mt-1 flex-shrink-0"
                                    >
                                    <div class="flex-grow">
                                        <div class="text-lg font-bold text-bim-black-900"><?php echo esc_html($term->name); ?></div>
                                        <div class="text-sm text-bim-black-700 mt-1"><?php echo esc_html($info); ?></div>
                                        <?php 
                                        // Show member count in this group
                                        $group_members = get_posts(array(
                                            'post_type' => 'foretak',
                                            'tax_query' => array(
                                                array(
                                                    'taxonomy' => 'temagruppe',
                                                    'field' => 'term_id',
                                                    'terms' => $term->term_id,
                                                )
                                            ),
                                            'posts_per_page' => -1,
                                        ));
                                        ?>
                                        <div class="text-xs text-bim-black-600 mt-2">
                                            👥 <?php echo count($group_members); ?> bedrift(er) i gruppen
                                        </div>
                                    </div>
                                    <?php if ($is_selected): ?>
                                    <div class="flex-shrink-0 mt-1">
                                        <span class="inline-block px-2 py-1 text-xs font-bold text-bim-orange bg-bim-orange bg-opacity-10 rounded-full">Aktiv</span>
                                    </div>
                                    <?php endif; ?>
                                </label>
                                <?php endforeach; ?>
                            </div>

                            <div class="flex gap-3 pt-6 border-t border-bim-black-200">
                                <button type="submit" class="btn btn-hjem-primary">
                                    ✓ Lagre valg
                                </button>
                                <a href="<?php echo home_url('/min-side/'); ?>" class="btn btn-hjem-outline">
                                    Avbryt
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="card-hjem mt-6 bg-gradient-to-br from-bim-orange from-opacity-5 to-bim-purple to-opacity-5">
                    <div class="card-body p-6">
                        <h3 class="font-bold text-bim-black-900 mb-2">💡 Tips:</h3>
                        <ul class="text-sm text-bim-black-700 space-y-2">
                            <li>• Medlemskap i temagrupper er frivillig</li>
                            <li>• Du kan endre valg når som helst</li>
                            <li>• Du mottar e-post når det er nye arrangementer i dine grupper</li>
                            <li>• Nettverking med andre medlemmer i samme gruppe</li>
                        </ul>
                    </div>
                </div>

            </main>
        </div>
    </div>
</div>

<?php get_footer(); ?>
