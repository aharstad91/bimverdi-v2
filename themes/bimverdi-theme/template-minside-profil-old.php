<?php
/**
 * Template Name: Min Profil
 * 
 * Edit company profile page for BIM Verdi members
 * Allows company owner to edit company information, categories, and theme groups
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

// Demo mode
$is_demo = empty($company_id);
if ($is_demo) {
    $company = (object) bimverdi_get_mock_company();
} else {
    $company = get_post($company_id);
}

$user_roles = $current_user->roles;
$is_company_owner = in_array('company_owner', $user_roles);

// Check if user has permission to edit this company
if (!$is_demo && !$is_company_owner && current_user_can('manage_options')) {
    // Allow admins
} elseif (!$is_demo && !$is_company_owner) {
    wp_die('Du har ikke tillatelse til å redigere denne profilen.');
}

// Handle form submission
$message = '';
$message_type = 'success'; // success or error

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bim_verdi_update_company'])) {
    
    // Verify nonce
    if (!isset($_POST['bim_verdi_nonce']) || !wp_verify_nonce($_POST['bim_verdi_nonce'], 'bim_verdi_edit_company')) {
        wp_die('Sikkerhetskontroll feilet');
    }

    // Get ACF field values to update
    $bedriftsnavn = sanitize_text_field($_POST['bedriftsnavn'] ?? '');
    $beskrivelse = wp_kses_post($_POST['beskrivelse'] ?? '');
    $adresse = sanitize_text_field($_POST['adresse'] ?? '');
    $postnummer = sanitize_text_field($_POST['postnummer'] ?? '');
    $poststed = sanitize_text_field($_POST['poststed'] ?? '');
    $telefon = sanitize_text_field($_POST['telefon'] ?? '');
    $nettside = esc_url_raw($_POST['nettside'] ?? '');
    $medlemstype = sanitize_text_field($_POST['medlemstype'] ?? '');

    // Update post title and content
    wp_update_post(array(
        'ID' => $company_id,
        'post_title' => $bedriftsnavn,
        'post_content' => $beskrivelse,
    ));

    // Update ACF fields
    update_field('bedriftsnavn', $bedriftsnavn, $company_id);
    update_field('beskrivelse', $beskrivelse, $company_id);
    update_field('adresse', $adresse, $company_id);
    update_field('postnummer', $postnummer, $company_id);
    update_field('poststed', $poststed, $company_id);
    update_field('telefon', $telefon, $company_id);
    update_field('nettside', $nettside, $company_id);
    update_field('medlemstype', $medlemstype, $company_id);

    // Handle logo upload
    if (!empty($_FILES['logo']['tmp_name'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('logo', $company_id);
        if (!is_wp_error($attachment_id)) {
            update_field('logo', $attachment_id, $company_id);
        }
    }

    // Update taxonomies
    $bransjekategorier = isset($_POST['bransjekategorier']) ? array_map('intval', (array)$_POST['bransjekategorier']) : array();
    $kundetyper = isset($_POST['kundetyper']) ? array_map('intval', (array)$_POST['kundetyper']) : array();
    $temagrupper = isset($_POST['temagrupper']) ? array_map('intval', (array)$_POST['temagrupper']) : array();

    wp_set_post_terms($company_id, $bransjekategorier, 'bransjekategori');
    wp_set_post_terms($company_id, $kundetyper, 'kundetype');
    wp_set_post_terms($company_id, $temagrupper, 'temagruppe');

    $message = 'Bedriftsprofilen ble oppdatert!';
    $message_type = 'success';
}

// Get current values
$bedriftsnavn = get_field('bedriftsnavn', $company_id) ?: $company->post_title;
$beskrivelse = get_field('beskrivelse', $company_id) ?: $company->post_content;
$adresse = get_field('adresse', $company_id) ?: '';
$postnummer = get_field('postnummer', $company_id) ?: '';
$poststed = get_field('poststed', $company_id) ?: '';
$telefon = get_field('telefon', $company_id) ?: '';
$nettside = get_field('nettside', $company_id) ?: '';
$medlemstype = get_field('medlemstype', $company_id) ?: 'deltaker';
$logo_id = get_field('logo', $company_id);
$logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';

// Get taxonomy terms
$selected_bransjekategorier = wp_get_post_terms($company_id, 'bransjekategori', array('fields' => 'ids'));
$selected_kundetyper = wp_get_post_terms($company_id, 'kundetype', array('fields' => 'ids'));
$selected_temagrupper = wp_get_post_terms($company_id, 'temagruppe', array('fields' => 'ids'));

// Get all terms for dropdowns
$all_bransjekategorier = get_terms(array('taxonomy' => 'bransjekategori', 'hide_empty' => false));
$all_kundetyper = get_terms(array('taxonomy' => 'kundetype', 'hide_empty' => false));
$all_temagrupper = get_terms(array('taxonomy' => 'temagruppe', 'hide_empty' => false));
?>

<!-- Min Side Horizontal Tab Navigation -->
<?php 
$current_tab = 'profil';
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
            <h1 class="text-3xl font-bold text-bim-black-900">Min Profil</h1>
        </div>

        <!-- Main Content (Full Width) -->
        <div>
                            </li>
                        </ul>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="lg:col-span-3">
                
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?> mb-6">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><?php echo esc_html($message); ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <?php wp_nonce_field('bim_verdi_edit_company', 'bim_verdi_nonce'); ?>
                    <input type="hidden" name="bim_verdi_update_company" value="1">

                    <!-- Bedriftsinfo Section -->
                    <div class="card-hjem">
                        <div class="card-body p-6">
                            <h2 class="text-2xl font-bold text-bim-black-900 mb-6">Bedriftsinformasjon</h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <!-- Bedriftsnavn -->
                                <div>
                                    <label class="block text-sm font-semibold text-bim-black-900 mb-2">Bedriftsnavn *</label>
                                    <input 
                                        type="text" 
                                        name="bedriftsnavn" 
                                        value="<?php echo esc_attr($bedriftsnavn); ?>"
                                        required
                                        class="input-hjem w-full"
                                        placeholder="Bedriftsnavn"
                                    >
                                </div>

                                <!-- Telefon -->
                                <div>
                                    <label class="block text-sm font-semibold text-bim-black-900 mb-2">Telefon</label>
                                    <input 
                                        type="tel" 
                                        name="telefon" 
                                        value="<?php echo esc_attr($telefon); ?>"
                                        class="input-hjem w-full"
                                        placeholder="+47 XX XX XX XX"
                                    >
                                </div>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-semibold text-bim-black-900 mb-2">Nettside</label>
                                <input 
                                    type="url" 
                                    name="nettside" 
                                    value="<?php echo esc_attr($nettside); ?>"
                                    class="input-hjem w-full"
                                    placeholder="https://bedrift.no"
                                >
                            </div>

                            <!-- Beskrivelse -->
                            <div class="mb-6">
                                <label class="block text-sm font-semibold text-bim-black-900 mb-2">Bedriftsbeskrivelse</label>
                                <textarea 
                                    name="beskrivelse" 
                                    class="input-hjem w-full h-32"
                                    placeholder="Beskriv din bedrift, spesialiseringer, og verdier..."
                                ><?php echo esc_textarea($beskrivelse); ?></textarea>
                            </div>

                            <!-- Logo -->
                            <div class="mb-6">
                                <label class="block text-sm font-semibold text-bim-black-900 mb-2">Bedriftslogo</label>
                                <?php if ($logo_url): ?>
                                <div class="mb-4 flex items-center gap-4">
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($bedriftsnavn); ?>" class="h-24 w-auto">
                                    <a href="<?php echo esc_url(add_query_arg('remove_logo', '1')); ?>" class="btn btn-sm btn-outline">Fjern logo</a>
                                </div>
                                <?php endif; ?>
                                <input 
                                    type="file" 
                                    name="logo" 
                                    accept="image/*"
                                    class="input input-bordered w-full"
                                >
                                <p class="text-sm text-bim-black-600 mt-2">Maksimal filstørrelse: 5MB. Formater: JPG, PNG</p>
                            </div>

                            <!-- Adresse -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-bim-black-900 mb-2">Adresse</label>
                                    <input 
                                        type="text" 
                                        name="adresse" 
                                        value="<?php echo esc_attr($adresse); ?>"
                                        class="input-hjem w-full"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-bim-black-900 mb-2">Postnummer</label>
                                    <input 
                                        type="text" 
                                        name="postnummer" 
                                        value="<?php echo esc_attr($postnummer); ?>"
                                        class="input-hjem w-full"
                                        maxlength="4"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-bim-black-900 mb-2">Poststed</label>
                                    <input 
                                        type="text" 
                                        name="poststed" 
                                        value="<?php echo esc_attr($poststed); ?>"
                                        class="input-hjem w-full"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kategorisering Section -->
                    <div class="card-hjem">
                        <div class="card-body p-6">
                            <h2 class="text-2xl font-bold text-bim-black-900 mb-6">Kategorisering</h2>

                            <!-- Bransjekategori -->
                            <div class="mb-6">
                                <label class="block text-sm font-semibold text-bim-black-900 mb-3">Bransjekategori</label>
                                <div class="space-y-2">
                                    <?php foreach ($all_bransjekategorier as $term): ?>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            name="bransjekategorier[]" 
                                            value="<?php echo $term->term_id; ?>"
                                            <?php checked(in_array($term->term_id, $selected_bransjekategorier)); ?>
                                            class="checkbox checkbox-hjem"
                                        >
                                        <span><?php echo esc_html($term->name); ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Kundetype -->
                            <div class="mb-6">
                                <label class="block text-sm font-semibold text-bim-black-900 mb-3">Kundetype</label>
                                <div class="space-y-2">
                                    <?php foreach ($all_kundetyper as $term): ?>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            name="kundetyper[]" 
                                            value="<?php echo $term->term_id; ?>"
                                            <?php checked(in_array($term->term_id, $selected_kundetyper)); ?>
                                            class="checkbox checkbox-hjem"
                                        >
                                        <span><?php echo esc_html($term->name); ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Medlemstype -->
                            <div>
                                <label class="block text-sm font-semibold text-bim-black-900 mb-3">Medlemstype</label>
                                <select name="medlemstype" class="input-hjem w-full">
                                    <option value="deltaker" <?php selected($medlemstype, 'deltaker'); ?>>Deltaker</option>
                                    <option value="partner" <?php selected($medlemstype, 'partner'); ?>>Partner</option>
                                    <option value="hovedpartner" <?php selected($medlemstype, 'hovedpartner'); ?>>Hovedpartner</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Temagrupper Section -->
                    <div class="card-hjem">
                        <div class="card-body p-6">
                            <h2 class="text-2xl font-bold text-bim-black-900 mb-3">Temagrupper</h2>
                            <p class="text-bim-black-700 mb-4">Velg hvilke temagrupper din bedrift deltar i:</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach ($all_temagrupper as $term): ?>
                                <label class="flex items-start gap-3 p-4 bg-bim-beige-100 rounded-lg cursor-pointer hover:bg-bim-beige-200 transition-colors">
                                    <input 
                                        type="checkbox" 
                                        name="temagrupper[]" 
                                        value="<?php echo $term->term_id; ?>"
                                        <?php checked(in_array($term->term_id, $selected_temagrupper)); ?>
                                        class="checkbox checkbox-hjem mt-1"
                                    >
                                    <div>
                                        <div class="font-semibold text-bim-black-900"><?php echo esc_html($term->name); ?></div>
                                        <?php if ($term->description): ?>
                                        <div class="text-sm text-bim-black-700"><?php echo esc_html($term->description); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex gap-3">
                        <button type="submit" class="btn btn-hjem-primary">
                            💾 Lagre endringer
                        </button>
                        <a href="<?php echo home_url('/min-side/'); ?>" class="btn btn-hjem-outline">
                            Avbryt
                        </a>
                    </div>
                </form>

            </main>
        </div>
    </div>
</div>

<?php get_footer(); ?>
