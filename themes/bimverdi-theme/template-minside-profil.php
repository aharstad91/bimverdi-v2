<?php
/**
 * Template Name: Min Side - Profil
 * 
 * User profile editing page
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

// Handle form submission
$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bim_verdi_update_profile'])) {
    if (!isset($_POST['bim_verdi_nonce']) || !wp_verify_nonce($_POST['bim_verdi_nonce'], 'bim_verdi_edit_profile')) {
        wp_die('Sikkerhetskontroll feilet');
    }

    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $display_name = sanitize_text_field($_POST['display_name'] ?? '');
    $user_email = sanitize_email($_POST['user_email'] ?? '');
    $stilling = sanitize_text_field($_POST['stilling'] ?? '');
    $telefon = sanitize_text_field($_POST['telefon'] ?? '');
    $bio = wp_kses_post($_POST['bio'] ?? '');

    // Update user data
    wp_update_user(array(
        'ID' => $user_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'display_name' => $display_name,
        'user_email' => $user_email,
        'description' => $bio,
    ));

    // Update user meta
    update_user_meta($user_id, 'stilling', $stilling);
    update_user_meta($user_id, 'telefon', $telefon);

    $message = 'Profilen din ble oppdatert!';
    
    // Refresh user data
    $current_user = wp_get_current_user();
}

// Get current values
$first_name = $current_user->first_name;
$last_name = $current_user->last_name;
$display_name = $current_user->display_name;
$user_email = $current_user->user_email;
$stilling = get_user_meta($user_id, 'stilling', true);
$telefon = get_user_meta($user_id, 'telefon', true);
$bio = $current_user->description;

// Start Min Side layout
get_template_part('template-parts/minside-layout-start', null, array(
    'current_page' => 'profil',
    'page_title' => 'Profil',
    'page_icon' => 'user',
    'page_description' => 'Oppdater din personlige informasjon',
));
?>

<!-- Success Message -->
<?php if ($message): ?>
    <wa-alert variant="<?php echo $message_type; ?>" open closable class="mb-6">
        <wa-icon slot="icon" name="circle-check" library="fa"></wa-icon>
        <?php echo esc_html($message); ?>
    </wa-alert>
<?php endif; ?>

<form method="POST" class="space-y-6">
    <?php wp_nonce_field('bim_verdi_edit_profile', 'bim_verdi_nonce'); ?>
    <input type="hidden" name="bim_verdi_update_profile" value="1">

    <!-- Personal Information -->
    <wa-card>
        <div slot="header" class="flex items-center gap-2">
            <wa-icon name="user" library="fa"></wa-icon>
            <strong>Personlig informasjon</strong>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-1">
            <div>
                <label class="block text-sm font-semibold text-gray-900 mb-2">Fornavn</label>
                <input 
                    type="text" 
                    name="first_name" 
                    value="<?php echo esc_attr($first_name); ?>"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="Ditt fornavn"
                >
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-900 mb-2">Etternavn</label>
                <input 
                    type="text" 
                    name="last_name" 
                    value="<?php echo esc_attr($last_name); ?>"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="Ditt etternavn"
                >
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-900 mb-2">Visningsnavn</label>
                <input 
                    type="text" 
                    name="display_name" 
                    value="<?php echo esc_attr($display_name); ?>"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="Hvordan ønsker du å bli vist?"
                >
                <p class="text-xs text-gray-500 mt-1">Dette navnet vises til andre medlemmer</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-900 mb-2">E-postadresse</label>
                <input 
                    type="email" 
                    name="user_email" 
                    value="<?php echo esc_attr($user_email); ?>"
                    required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="din@epost.no"
                >
            </div>
        </div>
    </wa-card>

    <!-- Work Information -->
    <wa-card>
        <div slot="header" class="flex items-center gap-2">
            <wa-icon name="briefcase" library="fa"></wa-icon>
            <strong>Arbeidsinformasjon</strong>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-1">
            <div>
                <label class="block text-sm font-semibold text-gray-900 mb-2">Stilling</label>
                <input 
                    type="text" 
                    name="stilling" 
                    value="<?php echo esc_attr($stilling); ?>"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="F.eks. BIM-koordinator"
                >
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-900 mb-2">Telefon</label>
                <input 
                    type="tel" 
                    name="telefon" 
                    value="<?php echo esc_attr($telefon); ?>"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="+47 XXX XX XXX"
                >
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-900 mb-2">Om meg</label>
                <textarea 
                    name="bio" 
                    rows="4"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="Kort beskrivelse av deg selv, din bakgrunn og interesseområder..."
                ><?php echo esc_textarea($bio); ?></textarea>
            </div>
        </div>
    </wa-card>

    <!-- Account Info (Read-only) -->
    <wa-card>
        <div slot="header" class="flex items-center gap-2">
            <wa-icon name="shield" library="fa"></wa-icon>
            <strong>Kontoinformasjon</strong>
        </div>

        <div class="p-1">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Brukernavn:</span>
                    <span class="font-medium ml-2"><?php echo esc_html($current_user->user_login); ?></span>
                </div>
                <div>
                    <span class="text-gray-500">Medlem siden:</span>
                    <span class="font-medium ml-2"><?php echo date('d.m.Y', strtotime($current_user->user_registered)); ?></span>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <wa-button variant="neutral" outline size="small" href="<?php echo wp_lostpassword_url(get_permalink()); ?>">
                    <wa-icon slot="prefix" name="key" library="fa"></wa-icon>
                    Endre passord
                </wa-button>
            </div>
        </div>
    </wa-card>

    <!-- Submit Buttons -->
    <div class="flex gap-3 pt-4">
        <wa-button type="submit" variant="brand">
            <wa-icon slot="prefix" name="check" library="fa"></wa-icon>
            Lagre endringer
        </wa-button>
        <wa-button variant="neutral" outline href="<?php echo home_url('/min-side/'); ?>">
            Avbryt
        </wa-button>
    </div>
</form>

<?php 
get_template_part('template-parts/minside-layout-end');
get_footer(); 
?>
