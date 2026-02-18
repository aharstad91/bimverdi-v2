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

// Check if this is a Norwegian company (BRREG fields should be locked)
$is_norwegian = function_exists('bimverdi_is_norwegian_foretak') ? bimverdi_is_norwegian_foretak($company_id) : true;
?>

<!-- Account Layout with Sidenav -->
<?php get_template_part('parts/components/account-layout', null, [
    'title' => __('Rediger foretak', 'bimverdi'),
    'description' => sprintf(__('Oppdater informasjon om %s', 'bimverdi'), esc_html($bedriftsnavn)),
]); ?>

    <!-- Form Container (constrained width) -->
    <div class="max-w-2xl">

        <?php if ($is_norwegian && $org_nummer): ?>
        <!-- BRREG info notice -->
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

        <!-- Gravity Form -->
        <div>
            <?php
            // Display Gravity Form ID 7 [System] - Redigering av foretak
            if (function_exists('gravity_form')) {
                gravity_form(7, false, false, false, array(
                    'company_id' => $company_id,
                ), true);
            } else {
                echo '<div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">';
                echo '<strong>' . esc_html__('Feil:', 'bimverdi') . '</strong> ' . esc_html__('Skjema er ikke tilgjengelig. Vennligst kontakt administrator.', 'bimverdi');
                echo '</div>';
            }
            ?>
        </div>

        <!-- CSS for BRREG locked fields -->
        <style>
            .gf-brreg-locked .ginput_container input,
            .gf-brreg-locked .ginput_container textarea {
                background-color: #F5F5F4 !important;
                color: #6B7280 !important;
                cursor: not-allowed !important;
                border-color: #E7E5E4 !important;
            }
            .gf-brreg-locked .gfield_label::after {
                content: 'Brreg';
                display: inline-flex;
                align-items: center;
                margin-left: 6px;
                padding: 1px 6px;
                background: #E7E5E4;
                border-radius: 4px;
                font-size: 0.65em;
                font-weight: 500;
                color: #57534E;
                vertical-align: middle;
                letter-spacing: 0.02em;
            }
            .gf-brreg-locked .gfield_description {
                color: #9CA3AF;
                font-style: italic;
            }
            /* Also handle the existing gf-readonly-field class */
            .gf-readonly-field .ginput_container input {
                background-color: #F5F5F4 !important;
                color: #6B7280 !important;
                cursor: not-allowed !important;
                border-color: #E7E5E4 !important;
            }
        </style>

        <!-- JS to enforce readonly on BRREG fields -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.gf-brreg-locked input, .gf-brreg-locked textarea').forEach(function(el) {
                el.readOnly = true;
                el.tabIndex = -1;
            });
        });
        </script>

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
                    <p><?php _e('Trenger du å endre hovedkontakt?', 'bimverdi'); ?> <a href="mailto:post@bimverdi.no" class="text-[#111827] underline"><?php _e('Kontakt oss', 'bimverdi'); ?></a>.</p>
                </div>
            </div>
        </div>

    </div>

<?php get_template_part('parts/components/account-layout-end'); ?>
