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
    $company_id = 0;
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
    $all_temagrupper = function_exists('bimverdi_get_mock_theme_groups') 
        ? array_map(function($group) {
            $term = new stdClass();
            $term->term_id = 0;
            $term->name = $group['name'];
            $term->slug = sanitize_title($group['name']);
            return $term;
        }, bimverdi_get_mock_theme_groups())
        : array();
    
    $selected_temagrupper = array();
} else {
    $all_temagrupper = get_terms(array('taxonomy' => 'temagruppe', 'hide_empty' => false));
    $selected_temagrupper = wp_get_post_terms($company_id, 'temagruppe', array('fields' => 'ids'));
}

// Theme group descriptions and icons
$temagruppe_info = array(
    'ByggesaksBIM' => array(
        'description' => 'Fokus på byggesaksprosesser og kommunal saksbehandling med digitale verktøy',
        'icon' => 'file-signature',
        'color' => 'bg-blue-100 text-blue-700',
    ),
    'ProsjektBIM' => array(
        'description' => 'Koordinering i design- og byggefase med 3D-modeller og samarbeid',
        'icon' => 'cubes',
        'color' => 'bg-purple-100 text-purple-700',
    ),
    'EiendomsBIM' => array(
        'description' => 'Drift, forvaltning og livssyklus-management av eiendom',
        'icon' => 'building',
        'color' => 'bg-green-100 text-green-700',
    ),
    'MiljøBIM' => array(
        'description' => 'Miljø- og klimaberegninger, energieffektivitet og bærekraft',
        'icon' => 'leaf',
        'color' => 'bg-emerald-100 text-emerald-700',
    ),
    'SirkBIM' => array(
        'description' => 'Sirkulær økonomi, materialpass og gjenbruk',
        'icon' => 'recycle',
        'color' => 'bg-teal-100 text-teal-700',
    ),
    'BIMtech' => array(
        'description' => 'Teknologi, AI, machine learning og nye digitale løsninger',
        'icon' => 'microchip',
        'color' => 'bg-orange-100 text-orange-700',
    ),
);

// Start Min Side layout
get_template_part('template-parts/minside-layout-start', null, array(
    'current_page' => 'temagrupper',
    'page_title' => 'Temagrupper',
    'page_icon' => 'layer-group',
    'page_description' => 'Velg hvilke temagrupper din bedrift deltar i',
));
?>

<!-- Demo Banner -->
<?php if ($is_demo): ?>
    <wa-alert variant="warning" open class="mb-6">
        <wa-icon slot="icon" name="triangle-exclamation" library="fa"></wa-icon>
        <strong>Demo-data vises</strong><br>
        Du ser demo-data fordi ingen faktisk bedrift er opprettet ennå. Endringer lagres ikke i demo-modus.
    </wa-alert>
<?php endif; ?>

<!-- Success Message -->
<?php if ($message): ?>
    <wa-alert variant="success" open closable class="mb-6">
        <wa-icon slot="icon" name="circle-check" library="fa"></wa-icon>
        <?php echo esc_html($message); ?>
    </wa-alert>
<?php endif; ?>

<form method="POST">
    <?php wp_nonce_field('bim_verdi_temagrupper', 'bim_verdi_nonce'); ?>
    <input type="hidden" name="bim_verdi_update_temagrupper" value="1">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <?php foreach ($all_temagrupper as $term): 
            $is_selected = in_array($term->term_id, $selected_temagrupper);
            $info = $temagruppe_info[$term->name] ?? array(
                'description' => $term->description ?: 'Ingen beskrivelse tilgjengelig',
                'icon' => 'users',
                'color' => 'bg-gray-100 text-gray-700',
            );
            
            // Count members in this group
            $group_members = get_posts(array(
                'post_type' => 'medlemsbedrift',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'temagruppe',
                        'field' => 'term_id',
                        'terms' => $term->term_id,
                    )
                ),
                'posts_per_page' => -1,
            ));
            $member_count = count($group_members);
        ?>
        <label class="block cursor-pointer">
            <wa-card class="h-full transition-all <?php echo $is_selected ? 'ring-2 ring-orange-500' : 'hover:shadow-lg'; ?>">
                <div class="p-5">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <input 
                                type="checkbox" 
                                name="temagrupper[]" 
                                value="<?php echo $term->term_id; ?>"
                                <?php checked($is_selected); ?>
                                class="sr-only peer"
                            >
                            <div class="w-12 h-12 rounded-lg <?php echo $info['color']; ?> flex items-center justify-center peer-checked:ring-2 peer-checked:ring-orange-500">
                                <wa-icon name="<?php echo esc_attr($info['icon']); ?>" library="fa" style="font-size: 1.5rem;"></wa-icon>
                            </div>
                        </div>
                        <div class="flex-grow">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-bold text-gray-900"><?php echo esc_html($term->name); ?></h3>
                                <?php if ($is_selected): ?>
                                    <wa-badge variant="success" size="small">Aktiv</wa-badge>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-600 mb-2"><?php echo esc_html($info['description']); ?></p>
                            <div class="text-xs text-gray-500 flex items-center gap-1">
                                <wa-icon name="users" library="fa" style="font-size: 0.75rem;"></wa-icon>
                                <?php echo $member_count; ?> bedrift<?php echo $member_count !== 1 ? 'er' : ''; ?> i gruppen
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <?php if ($is_selected): ?>
                                <wa-icon name="circle-check" library="fa" style="font-size: 1.5rem; color: var(--wa-color-success-600);"></wa-icon>
                            <?php else: ?>
                                <wa-icon name="circle" library="fa" style="font-size: 1.5rem; color: var(--wa-color-neutral-300);"></wa-icon>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </wa-card>
        </label>
        <?php endforeach; ?>
    </div>

    <div class="flex gap-3 pt-6 border-t border-gray-200">
        <wa-button type="submit" variant="brand">
            <wa-icon slot="prefix" name="check" library="fa"></wa-icon>
            Lagre valg
        </wa-button>
        <wa-button variant="neutral" outline href="<?php echo home_url('/min-side/'); ?>">
            Avbryt
        </wa-button>
    </div>
</form>

<!-- Info Box -->
<wa-card class="mt-8 bg-gradient-to-br from-orange-50 to-purple-50">
    <div class="p-5">
        <div class="flex items-start gap-3">
            <wa-icon name="lightbulb" library="fa" style="font-size: 1.25rem; color: var(--wa-color-warning-600);"></wa-icon>
            <div>
                <h3 class="font-bold text-gray-900 mb-2">Tips om temagrupper</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Medlemskap i temagrupper er frivillig</li>
                    <li>• Du kan endre valg når som helst</li>
                    <li>• Du mottar e-post når det er nye arrangementer i dine grupper</li>
                    <li>• Nettverking med andre medlemmer i samme gruppe</li>
                </ul>
            </div>
        </div>
    </div>
</wa-card>

<?php 
get_template_part('template-parts/minside-layout-end');
get_footer(); 
?>
