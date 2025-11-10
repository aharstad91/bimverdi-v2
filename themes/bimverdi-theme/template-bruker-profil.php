<?php
/**
 * Template Name: Bruker Profil
 * 
 * User profile page for BIM Verdi members
 * Allows users to edit their personal profile information
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
$profile = bim_get_user_profile($user_id);

// Handle form submission if using PHP instead of ACF form
$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bim_verdi_update_user_profile'])) {
    
    // Verify nonce
    if (!isset($_POST['bim_verdi_user_nonce']) || !wp_verify_nonce($_POST['bim_verdi_user_nonce'], 'bim_verdi_edit_user_profile')) {
        wp_die('Sikkerhetskontroll feilet');
    }

    // Get form values
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $job_title = sanitize_text_field($_POST['job_title'] ?? '');
    $linkedin_url = esc_url_raw($_POST['linkedin_url'] ?? '');

    // Update user
    wp_update_user(array(
        'ID' => $user_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
    ));

    // Update ACF fields
    bim_update_user_profile_field('phone', $phone, $user_id);
    bim_update_user_profile_field('job_title', $job_title, $user_id);
    bim_update_user_profile_field('linkedin_url', $linkedin_url, $user_id);

    $message = 'Profilen din har blitt oppdatert!';
    $message_type = 'success';
    
    // Refresh profile data
    $profile = bim_get_user_profile($user_id);
}
?>

<!-- Min Side Horizontal Tab Navigation -->
<?php 
$current_tab = 'profil';
if (function_exists('get_template_part')) {
    get_template_part('template-parts/minside-tabs', null, array('current_tab' => $current_tab));
}
?>

<div class="min-h-screen bg-bim-beige-100 py-8">
    <div class="container mx-auto px-4 max-w-2xl">
        
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-bim-black-900 mb-2">Min Profil</h1>
            <p class="text-bim-black-700">Rediger din personlige informasjon</p>
        </div>

        <!-- Profile Summary Card -->
        <div class="card bg-white shadow-lg mb-8">
            <div class="card-body p-6">
                <div class="flex items-center gap-6">
                    <div class="flex-shrink-0">
                        <?php echo get_avatar($current_user->user_email, 80, '', bim_get_user_display_name($user_id), array('class' => 'rounded-full w-20 h-20')); ?>
                    </div>
                    <div class="flex-grow">
                        <h2 class="text-2xl font-bold text-bim-black-900">
                            <?php echo esc_html(bim_get_user_display_name($user_id)); ?>
                        </h2>
                        <p class="text-bim-black-600 mb-2"><?php echo esc_html($current_user->user_email); ?></p>
                        
                        <?php if (!empty($profile['job_title'])): ?>
                            <p class="text-bim-orange-500 font-semibold">
                                <?php echo esc_html($profile['job_title']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($profile['phone'])): ?>
                            <p class="text-bim-black-600">
                                📞 <a href="tel:<?php echo esc_attr($profile['phone']); ?>" class="text-bim-orange-500 hover:underline">
                                    <?php echo esc_html($profile['phone']); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Message -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?> mb-6 shadow-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span><?php echo esc_html($message); ?></span>
        </div>
        <?php endif; ?>

        <!-- Edit Profile Form Using ACF if available, otherwise PHP form -->
        <div class="card bg-white shadow-lg">
            <div class="card-body p-6">
                <h2 class="text-2xl font-bold text-bim-black-900 mb-6">Rediger Informasjon</h2>

                <?php if (function_exists('acf_form')): ?>
                    <!-- Using ACF Form (Recommended) -->
                    <?php
                    acf_form(array(
                        'id' => 'acf-user-profile-form',
                        'post_id' => 'user_' . $user_id,
                        'field_groups' => array('group_bim_verdi_user_profile'),
                        'form' => true,
                        'return' => add_query_arg('profile_updated', '1', get_permalink()),
                        'html_before_fields' => '<fieldset class="space-y-6">',
                        'html_after_fields' => '</fieldset>',
                        'html_before_submit' => '<div class="flex gap-3 mt-8">',
                        'html_after_submit' => '</div>',
                        'submit_button' => '💾 Lagre endringer',
                        'updated_message' => 'Profilen din har blitt oppdatert! ✓',
                    ));
                    ?>
                    <a href="<?php echo esc_url(get_permalink()); ?>" class="btn btn-hjem-outline">Avbryt</a>
                <?php else: ?>
                    <!-- Fallback PHP Form (if ACF not available) -->
                    <form method="POST" class="space-y-6">
                        <?php wp_nonce_field('bim_verdi_edit_user_profile', 'bim_verdi_user_nonce'); ?>
                        <input type="hidden" name="bim_verdi_update_user_profile" value="1">

                        <!-- First Name -->
                        <div>
                            <label class="block text-sm font-semibold text-bim-black-900 mb-2">Fornavn</label>
                            <input 
                                type="text" 
                                name="first_name" 
                                value="<?php echo esc_attr($profile['first_name']); ?>"
                                class="input-hjem w-full"
                                placeholder="Fornavn"
                            >
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label class="block text-sm font-semibold text-bim-black-900 mb-2">Etternavn</label>
                            <input 
                                type="text" 
                                name="last_name" 
                                value="<?php echo esc_attr($profile['last_name']); ?>"
                                class="input-hjem w-full"
                                placeholder="Etternavn"
                            >
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-semibold text-bim-black-900 mb-2">📞 Telefon</label>
                            <input 
                                type="tel" 
                                name="phone" 
                                value="<?php echo esc_attr($profile['phone']); ?>"
                                class="input-hjem w-full"
                                placeholder="+47 91 23 45 67"
                            >
                            <p class="text-xs text-bim-black-600 mt-1">Mobilnummer eller fasttelefon</p>
                        </div>

                        <!-- Job Title -->
                        <div>
                            <label class="block text-sm font-semibold text-bim-black-900 mb-2">💼 Tittel/Stilling</label>
                            <input 
                                type="text" 
                                name="job_title" 
                                value="<?php echo esc_attr($profile['job_title']); ?>"
                                class="input-hjem w-full"
                                placeholder="f.eks. Prosjektleder, Arkitekt"
                            >
                            <p class="text-xs text-bim-black-600 mt-1">Din stilling eller rolle i bedriften</p>
                        </div>

                        <!-- LinkedIn URL -->
                        <div>
                            <label class="block text-sm font-semibold text-bim-black-900 mb-2">🔗 LinkedIn URL</label>
                            <input 
                                type="url" 
                                name="linkedin_url" 
                                value="<?php echo esc_attr($profile['linkedin_url']); ?>"
                                class="input-hjem w-full"
                                placeholder="https://linkedin.com/in/yourprofile"
                            >
                            <p class="text-xs text-bim-black-600 mt-1">Link til din LinkedIn profil (valgfritt)</p>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex gap-3 pt-6">
                            <button type="submit" class="btn btn-hjem-primary">
                                💾 Lagre endringer
                            </button>
                            <a href="<?php echo esc_url(get_permalink()); ?>" class="btn btn-hjem-outline">
                                Avbryt
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Additional Info Card -->
        <div class="card bg-white shadow-lg mt-8">
            <div class="card-body p-6">
                <h3 class="text-lg font-bold text-bim-black-900 mb-4">Profil Tips</h3>
                <ul class="space-y-3 text-bim-black-700">
                    <li class="flex items-start gap-2">
                        <span class="text-bim-orange-500 font-bold">•</span>
                        <span>Fullfør profilen din med telefonnummer og stilling for bedre nettverksmuligheter</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-bim-orange-500 font-bold">•</span>
                        <span>Legg til LinkedIn-profilen din for å gjøre det enklere for andre medlemmer å finne deg</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-bim-orange-500 font-bold">•</span>
                        <span>E-postadressen kan ikke endres her. Kontakt support hvis du ønsker å bytte e-post</span>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</div>

<?php get_footer(); ?>
