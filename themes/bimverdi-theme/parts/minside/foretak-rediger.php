<?php
/**
 * Part: Rediger foretak
 *
 * Skjema for redigering av foretaksprofil via Gravity Forms.
 * Brukes på /min-side/foretak/rediger/
 * Kun tilgjengelig for hovedkontakt eller admin.
 *
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_data = bimverdi_get_user_company($user_id);

// Check if user has a company
if (!$company_data) {
    wp_redirect(bimverdi_minside_url('foretak') . '?ikke_koblet=1');
    exit;
}

// bimverdi_get_user_company() returns an array with 'id' key
$company_id = is_array($company_data) ? $company_data['id'] : $company_data;

$company = get_post($company_id);
if (!$company || $company->post_type !== 'foretak') {
    wp_redirect(bimverdi_minside_url('foretak') . '?ugyldig=1');
    exit;
}

// Check if user is hovedkontakt or admin
$is_hovedkontakt = bimverdi_is_hovedkontakt($user_id, $company_id);
$is_admin = current_user_can('manage_options');

if (!$is_hovedkontakt && !$is_admin) {
    wp_redirect(bimverdi_minside_url('foretak') . '?ikke_hovedkontakt=1');
    exit;
}

// Get company data
$bedriftsnavn = get_field('bedriftsnavn', $company_id) ?: $company->post_title;
$org_nummer = get_field('organisasjonsnummer', $company_id);
$logo_id = get_field('logo', $company_id);
$logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'thumbnail') : '';

// Get Brreg-synced fields (locked)
$adresse = get_field('adresse', $company_id);
$postnummer = get_field('postnummer', $company_id);
$poststed = get_field('poststed', $company_id);
$land = get_field('land', $company_id);
?>

<!-- Account Layout with Sidenav -->
<?php get_template_part('parts/components/account-layout', null, [
    'title' => __('Rediger foretak', 'bimverdi'),
    'description' => sprintf(__('Oppdater informasjon om %s', 'bimverdi'), esc_html($bedriftsnavn)),
]); ?>

    <!-- Form Container (constrained width) -->
    <div class="max-w-2xl">

        <!-- Company Info Badge -->
        <div class="mb-8 p-4 bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg flex items-center gap-4">
            <?php if ($logo_url): ?>
                <img src="<?php echo esc_url($logo_url); ?>"
                     alt="<?php echo esc_attr($bedriftsnavn); ?>"
                     class="w-16 h-16 rounded-lg object-contain bg-white border border-[#E5E0D5] flex-shrink-0">
            <?php else: ?>
                <div class="w-16 h-16 rounded-lg bg-white border border-[#E5E0D5] flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/>
                        <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/>
                        <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/>
                    </svg>
                </div>
            <?php endif; ?>
            <div>
                <p class="font-semibold text-[#1A1A1A] text-lg"><?php echo esc_html($bedriftsnavn); ?></p>
                <?php if ($org_nummer): ?>
                    <p class="text-sm text-[#5A5A5A]"><?php _e('Org.nr:', 'bimverdi'); ?> <?php echo esc_html($org_nummer); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Locked Brreg Data Section -->
        <div class="mb-8 p-6 bg-[#F9F9F9] border border-[#E5E0D5] rounded-lg">
            <div class="flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <h3 class="font-semibold text-[#1A1A1A]"><?php _e('Offisielle data fra Brønnøysundregistrene', 'bimverdi'); ?></h3>
            </div>
            <p class="text-sm text-[#5A5A5A] mb-4">
                <?php _e('Disse opplysningene hentes automatisk fra Brønnøysundregistrene og kan ikke redigeres her. For å oppdatere disse må du melde endring til Brønnøysundregistrene.', 'bimverdi'); ?>
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-medium text-[#888] uppercase tracking-wide mb-1"><?php _e('Bedriftsnavn', 'bimverdi'); ?></p>
                    <p class="text-sm text-[#1A1A1A] bg-white border border-[#E5E0D5] rounded px-3 py-2"><?php echo esc_html($bedriftsnavn); ?></p>
                </div>
                <div>
                    <p class="text-xs font-medium text-[#888] uppercase tracking-wide mb-1"><?php _e('Organisasjonsnummer', 'bimverdi'); ?></p>
                    <p class="text-sm text-[#1A1A1A] bg-white border border-[#E5E0D5] rounded px-3 py-2"><?php echo esc_html($org_nummer); ?></p>
                </div>
                <?php if ($adresse): ?>
                <div>
                    <p class="text-xs font-medium text-[#888] uppercase tracking-wide mb-1"><?php _e('Adresse', 'bimverdi'); ?></p>
                    <p class="text-sm text-[#1A1A1A] bg-white border border-[#E5E0D5] rounded px-3 py-2"><?php echo esc_html($adresse); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($postnummer || $poststed): ?>
                <div>
                    <p class="text-xs font-medium text-[#888] uppercase tracking-wide mb-1"><?php _e('Poststed', 'bimverdi'); ?></p>
                    <p class="text-sm text-[#1A1A1A] bg-white border border-[#E5E0D5] rounded px-3 py-2"><?php echo esc_html(trim($postnummer . ' ' . $poststed)); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($land && $land !== 'Norge'): ?>
                <div>
                    <p class="text-xs font-medium text-[#888] uppercase tracking-wide mb-1"><?php _e('Land', 'bimverdi'); ?></p>
                    <p class="text-sm text-[#1A1A1A] bg-white border border-[#E5E0D5] rounded px-3 py-2"><?php echo esc_html($land); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Gravity Form -->
        <div class="bg-white border border-[#E5E0D5] rounded-lg p-8">
            <h2 class="text-xl font-bold text-[#1A1A1A] mb-6 flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
                    <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.375 2.625a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4Z"/>
                </svg>
                <?php _e('Redigerbar informasjon', 'bimverdi'); ?>
            </h2>
            <p class="text-sm text-[#5A5A5A] mb-6">
                <?php _e('Oppdater beskrivelse, logo, kontaktinformasjon og nettside for foretaket.', 'bimverdi'); ?>
            </p>

            <?php
            // Display Gravity Form ID 7 [System] - Redigering av foretak
            if (function_exists('gravity_form')) {
                gravity_form(7, false, false, false, array(
                    'company_id' => $company_id,
                ), true);
            } else {
                echo '<div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">';
                echo '<strong>Feil:</strong> Skjema er ikke tilgjengelig. Vennligst kontakt administrator.';
                echo '</div>';
            }
            ?>
        </div>

        <!-- Help Section -->
        <div class="mt-12 pt-8 border-t border-[#D6D1C6]">
            <h3 class="text-sm font-bold text-[#1A1A1A] uppercase tracking-wider mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                    <path d="M12 17h.01"/>
                </svg>
                <?php _e('Hjelp', 'bimverdi'); ?>
            </h3>

            <div class="space-y-4 text-sm text-[#5A5A5A]">
                <div class="flex gap-3 items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4"/>
                        <path d="M12 8h.01"/>
                    </svg>
                    <p><?php _e('Endringer lagres automatisk når du sender inn skjemaet.', 'bimverdi'); ?></p>
                </div>
                <div class="flex gap-3 items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4"/>
                        <path d="M12 8h.01"/>
                    </svg>
                    <p><?php _e('Kun hovedkontakt kan redigere foretaksinformasjon.', 'bimverdi'); ?></p>
                </div>
                <div class="flex gap-3 items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <p><strong><?php _e('Låste felt:', 'bimverdi'); ?></strong> <?php _e('Bedriftsnavn, org.nr, adresse og poststed hentes fra Brønnøysundregistrene og kan ikke redigeres her.', 'bimverdi'); ?></p>
                </div>
                <div class="flex gap-3 items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4"/>
                        <path d="M12 8h.01"/>
                    </svg>
                    <p><?php _e('Trenger du å endre hovedkontakt?', 'bimverdi'); ?> <a href="mailto:post@bimverdi.no" class="text-[#1A1A1A] underline"><?php _e('Kontakt oss', 'bimverdi'); ?></a>.</p>
                </div>
            </div>
        </div>

    </div>

<?php get_template_part('parts/components/account-layout-end'); ?>
