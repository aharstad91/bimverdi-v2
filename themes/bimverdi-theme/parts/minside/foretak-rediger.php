<?php
/**
 * Part: Rediger foretak
 *
 * Plain HTML form for editing company (foretak) profile.
 * Replaces Gravity Forms Form #7.
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

// Get current field values
$org_nummer  = get_field('organisasjonsnummer', $company_id) ?: '';
$bedriftsnavn = $company->post_title;
$beskrivelse = get_field('beskrivelse', $company_id) ?: $company->post_content;
$telefon     = get_field('telefon', $company_id) ?: '';
$nettside    = get_field('hjemmeside', $company_id) ?: get_field('nettside', $company_id) ?: '';
$gateadresse = get_field('gateadresse', $company_id) ?: get_field('adresse', $company_id) ?: '';
$postnummer  = get_field('postnummer', $company_id) ?: '';
$poststed    = get_field('poststed', $company_id) ?: '';

// Current logo
$logo = get_field('logo', $company_id);
$logo_url = '';
if ($logo) {
    $logo_url = is_array($logo) ? ($logo['sizes']['medium'] ?? $logo['url'] ?? '') : (wp_get_attachment_image_url($logo, 'medium') ?: '');
}

// Error messages
$error = isset($_GET['bv_error']) ? sanitize_text_field($_GET['bv_error']) : '';
$error_messages = [
    'nonce'            => 'Skjemaet utløp. Vennligst prøv igjen.',
    'missing_company'  => 'Foretak ikke funnet.',
    'invalid_company'  => 'Ugyldig foretak.',
    'not_authorized'   => 'Du har ikke tilgang til å redigere dette foretaket.',
    'rate_limit'       => 'For mange forsøk. Vennligst vent litt før du prøver igjen.',
    'invalid_file_type'=> 'Ugyldig filtype. Tillatte formater: jpg, jpeg, png, gif, svg.',
    'file_too_large'   => 'Filen er for stor. Maks størrelse er 5 MB.',
    'upload_failed'    => 'Kunne ikke laste opp logo. Vennligst prøv igjen.',
    'system'           => 'En teknisk feil oppstod. Vennligst prøv igjen.',
];
$error_text = isset($error_messages[$error]) ? $error_messages[$error] : '';
?>

<!-- Account Layout with Sidenav -->
<?php get_template_part('parts/components/account-layout', null, [
    'title' => __('Rediger foretak', 'bimverdi'),
    'description' => sprintf(__('Oppdater informasjon om %s', 'bimverdi'), esc_html($bedriftsnavn)),
]); ?>

    <!-- Form Container -->
    <div class="max-w-2xl">

        <?php if ($error_text): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <p class="text-red-800 text-sm"><?php echo esc_html($error_text); ?></p>
        </div>
        <?php endif; ?>

        <!-- BRreg info notice -->
        <?php if ($org_nummer): ?>
        <div class="mb-6 p-3 bg-[#F5F5F4] border border-[#E7E5E4] rounded-lg flex items-center gap-3 text-sm text-[#57534E]">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <p>
                <?php _e('Felt merket med', 'bimverdi'); ?>
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-[#E7E5E4] rounded text-xs font-medium text-[#57534E]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <?php _e('Brreg', 'bimverdi'); ?>
                </span>
                <?php _e('hentes fra Brønnøysundregistrene og kan ikke redigeres her.', 'bimverdi'); ?>
            </p>
        </div>
        <?php endif; ?>

        <form method="post" action="" enctype="multipart/form-data" class="space-y-6">
            <?php wp_nonce_field('bimverdi_edit_foretak'); ?>
            <input type="hidden" name="bimverdi_edit_foretak" value="1">
            <input type="hidden" name="company_id" value="<?php echo esc_attr($company_id); ?>">

            <!-- Readonly BRreg fields -->
            <?php if ($org_nummer): ?>
            <div>
                <label class="block text-sm font-semibold text-[#111827] mb-1">
                    <?php _e('Organisasjonsnummer', 'bimverdi'); ?>
                    <span class="inline-flex items-center gap-1 ml-2 px-1.5 py-0.5 bg-[#E7E5E4] rounded text-xs font-medium text-[#57534E]">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Brreg
                    </span>
                </label>
                <input type="text" value="<?php echo esc_attr($org_nummer); ?>" readonly
                       class="w-full px-3 py-2 border border-[#E7E5E4] rounded-lg bg-[#F5F5F4] text-[#6B7280] cursor-not-allowed text-sm">
                <p class="mt-1 text-xs text-[#9CA3AF] italic"><?php _e('Hentes fra Brønnøysundregistrene', 'bimverdi'); ?></p>
            </div>
            <?php endif; ?>

            <div>
                <label class="block text-sm font-semibold text-[#111827] mb-1">
                    <?php _e('Bedriftsnavn', 'bimverdi'); ?>
                    <span class="inline-flex items-center gap-1 ml-2 px-1.5 py-0.5 bg-[#E7E5E4] rounded text-xs font-medium text-[#57534E]">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Brreg
                    </span>
                </label>
                <input type="text" value="<?php echo esc_attr($bedriftsnavn); ?>" readonly
                       class="w-full px-3 py-2 border border-[#E7E5E4] rounded-lg bg-[#F5F5F4] text-[#6B7280] cursor-not-allowed text-sm">
                <p class="mt-1 text-xs text-[#9CA3AF] italic"><?php _e('Hentes fra Brønnøysundregistrene', 'bimverdi'); ?></p>
            </div>

            <!-- Editable fields -->
            <div>
                <label for="beskrivelse" class="block text-sm font-semibold text-[#111827] mb-1">
                    <?php _e('Bedriftsbeskrivelse', 'bimverdi'); ?>
                </label>
                <textarea id="beskrivelse" name="beskrivelse" rows="5"
                          placeholder="<?php esc_attr_e('Kort beskrivelse av bedriftens virksomhet', 'bimverdi'); ?>"
                          class="w-full px-3 py-2 border border-[#D6D1C6] rounded-lg text-sm text-[#111827] placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent"
                ><?php echo esc_textarea($beskrivelse); ?></textarea>
            </div>

            <!-- Logo -->
            <div>
                <label class="block text-sm font-semibold text-[#111827] mb-1">
                    <?php _e('Logo', 'bimverdi'); ?>
                </label>
                <?php if ($logo_url): ?>
                <div class="mb-3 flex items-center gap-4">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="" class="w-16 h-16 rounded-lg object-cover border border-[#E7E5E4]">
                    <span class="text-xs text-[#57534E]"><?php _e('Nåværende logo', 'bimverdi'); ?></span>
                </div>
                <?php endif; ?>
                <input type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.gif,.svg,.webp"
                       class="w-full text-sm text-[#57534E] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border file:border-[#D6D1C6] file:text-sm file:font-medium file:bg-white file:text-[#111827] hover:file:bg-[#F5F5F4] file:cursor-pointer">
                <p class="mt-1 text-xs text-[#9CA3AF]"><?php _e('Last opp ny logo (valgfritt). Tillatte formater: jpg, jpeg, png, gif, svg. Maks 5 MB.', 'bimverdi'); ?></p>
            </div>

            <!-- Telefon -->
            <div>
                <label for="telefon" class="block text-sm font-semibold text-[#111827] mb-1">
                    <?php _e('Telefon', 'bimverdi'); ?>
                </label>
                <input type="tel" id="telefon" name="telefon" value="<?php echo esc_attr($telefon); ?>"
                       placeholder="<?php esc_attr_e('F.eks. +47 123 45 678', 'bimverdi'); ?>"
                       class="w-full px-3 py-2 border border-[#D6D1C6] rounded-lg text-sm text-[#111827] placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
            </div>

            <!-- Nettside -->
            <div>
                <label for="nettside" class="block text-sm font-semibold text-[#111827] mb-1">
                    <?php _e('Nettside', 'bimverdi'); ?>
                </label>
                <input type="url" id="nettside" name="nettside" value="<?php echo esc_attr($nettside); ?>"
                       placeholder="https://"
                       class="w-full px-3 py-2 border border-[#D6D1C6] rounded-lg text-sm text-[#111827] placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
            </div>

            <!-- Readonly address section -->
            <?php if ($gateadresse || $postnummer || $poststed): ?>
            <div class="pt-6 border-t border-[#E7E5E4]">
                <h3 class="text-base font-semibold text-[#111827] mb-4"><?php _e('Adresse (fra Brønnøysundregistrene)', 'bimverdi'); ?></h3>

                <?php if ($gateadresse): ?>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-[#111827] mb-1">
                        <?php _e('Gateadresse', 'bimverdi'); ?>
                        <span class="inline-flex items-center gap-1 ml-2 px-1.5 py-0.5 bg-[#E7E5E4] rounded text-xs font-medium text-[#57534E]">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            Brreg
                        </span>
                    </label>
                    <input type="text" value="<?php echo esc_attr($gateadresse); ?>" readonly
                           class="w-full px-3 py-2 border border-[#E7E5E4] rounded-lg bg-[#F5F5F4] text-[#6B7280] cursor-not-allowed text-sm">
                    <p class="mt-1 text-xs text-[#9CA3AF] italic"><?php _e('Hentes fra Brønnøysundregistrene', 'bimverdi'); ?></p>
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-2 gap-4">
                    <?php if ($postnummer): ?>
                    <div>
                        <label class="block text-sm font-semibold text-[#111827] mb-1">
                            <?php _e('Postnummer', 'bimverdi'); ?>
                            <span class="inline-flex items-center gap-1 ml-2 px-1.5 py-0.5 bg-[#E7E5E4] rounded text-xs font-medium text-[#57534E]">
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                Brreg
                            </span>
                        </label>
                        <input type="text" value="<?php echo esc_attr($postnummer); ?>" readonly
                               class="w-full px-3 py-2 border border-[#E7E5E4] rounded-lg bg-[#F5F5F4] text-[#6B7280] cursor-not-allowed text-sm">
                        <p class="mt-1 text-xs text-[#9CA3AF] italic"><?php _e('Hentes fra Brønnøysundregistrene', 'bimverdi'); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($poststed): ?>
                    <div>
                        <label class="block text-sm font-semibold text-[#111827] mb-1">
                            <?php _e('Poststed', 'bimverdi'); ?>
                            <span class="inline-flex items-center gap-1 ml-2 px-1.5 py-0.5 bg-[#E7E5E4] rounded text-xs font-medium text-[#57534E]">
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                Brreg
                            </span>
                        </label>
                        <input type="text" value="<?php echo esc_attr($poststed); ?>" readonly
                               class="w-full px-3 py-2 border border-[#E7E5E4] rounded-lg bg-[#F5F5F4] text-[#6B7280] cursor-not-allowed text-sm">
                        <p class="mt-1 text-xs text-[#9CA3AF] italic"><?php _e('Hentes fra Brønnøysundregistrene', 'bimverdi'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Submit -->
            <div class="pt-4">
                <button type="submit"
                        class="bv-btn bv-btn--primary px-6 py-2.5 text-sm font-semibold rounded-lg">
                    <?php _e('Lagre endringer', 'bimverdi'); ?>
                </button>
            </div>
        </form>

        <!-- Help Section -->
        <div class="mt-12 pt-8 border-t border-[#E7E5E4]">
            <h3 class="text-sm font-bold text-[#111827] uppercase tracking-wider mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                    <path d="M12 17h.01"/>
                </svg>
                <?php _e('Hjelp', 'bimverdi'); ?>
            </h3>

            <div class="space-y-4 text-sm text-[#57534E]">
                <div class="flex gap-3 items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                        <circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>
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
                        <circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>
                    </svg>
                    <p><?php _e('Trenger du å endre hovedkontakt?', 'bimverdi'); ?> <a href="mailto:post@bimverdi.no" class="text-[#111827] underline"><?php _e('Kontakt oss', 'bimverdi'); ?></a>.</p>
                </div>
            </div>
        </div>

    </div>

<?php get_template_part('parts/components/account-layout-end'); ?>
