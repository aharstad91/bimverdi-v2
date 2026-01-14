<?php
/**
 * Part: Rediger foretak
 * 
 * Skjema for redigering av foretaksprofil via Gravity Forms.
 * Brukes på /min-side/rediger-foretak/
 * Kun tilgjengelig for hovedkontakt eller admin.
 * 
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

// Also check ACF field
if (!$company_id && function_exists('get_field')) {
    $company_id = get_field('tilknyttet_foretak', 'user_' . $user_id);
    if (is_object($company_id)) {
        $company_id = $company_id->ID;
    }
}

// Check if user has a company
if (!$company_id) {
    wp_redirect(home_url('/min-side/foretak/?ikke_koblet=1'));
    exit;
}

$company = get_post($company_id);
if (!$company || $company->post_type !== 'foretak') {
    wp_redirect(home_url('/min-side/foretak/?ugyldig=1'));
    exit;
}

// Check if user is hovedkontakt or admin
$hovedkontakt_id = get_field('hovedkontaktperson', $company_id);
$is_hovedkontakt = ($hovedkontakt_id == $user_id);
$is_admin = current_user_can('manage_options');

if (!$is_hovedkontakt && !$is_admin) {
    wp_redirect(home_url('/min-side/foretak/?ikke_hovedkontakt=1'));
    exit;
}

// Get company data
$bedriftsnavn = get_field('bedriftsnavn', $company_id) ?: $company->post_title;
$org_nummer = get_field('organisasjonsnummer', $company_id);
$logo_id = get_field('logo', $company_id);
$logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'thumbnail') : '';
?>

<!-- Breadcrumb -->
<nav class="mb-6" aria-label="Brødsmulesti">
    <ol class="flex items-center gap-2 text-sm text-[#5A5A5A]">
        <li>
            <a href="<?php echo esc_url(home_url('/min-side/')); ?>" class="hover:text-[#1A1A1A] transition-colors">
                Min side
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li>
            <a href="<?php echo esc_url(home_url('/min-side/foretak/')); ?>" class="hover:text-[#1A1A1A] transition-colors">
                Foretak
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li class="text-[#1A1A1A] font-medium" aria-current="page">Rediger</li>
    </ol>
</nav>

<!-- Page Header -->
<?php
get_template_part('parts/components/page-header', null, [
    'title' => 'Rediger foretak',
    'description' => 'Oppdater informasjon om ' . esc_html($bedriftsnavn)
]);
?>

<!-- Form Container (960px centered per _claude/ui-contract.md) -->
<div class="max-w-3xl mx-auto">
    
    <!-- Company Info Badge -->
    <div class="mb-8 p-4 bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg flex items-center gap-4">
        <?php if ($logo_url): ?>
            <img src="<?php echo esc_url($logo_url); ?>" 
                 alt="<?php echo esc_attr($bedriftsnavn); ?>" 
                 class="w-16 h-16 rounded-lg object-contain bg-white border border-[#E5E0D5]">
        <?php else: ?>
            <div class="w-16 h-16 rounded-lg bg-white border border-[#E5E0D5] flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"/>
                    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"/>
                    <line x1="6" y1="6" x2="6.01" y2="6"/>
                    <line x1="6" y1="18" x2="6.01" y2="18"/>
                </svg>
            </div>
        <?php endif; ?>
        <div>
            <p class="font-semibold text-[#1A1A1A] text-lg"><?php echo esc_html($bedriftsnavn); ?></p>
            <?php if ($org_nummer): ?>
                <p class="text-sm text-[#5A5A5A]">Org.nr: <?php echo esc_html($org_nummer); ?></p>
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
            Oppdater foretaksinformasjon
        </h2>
        
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
            Hjelp
        </h3>
        
        <div class="space-y-4 text-sm text-[#5A5A5A]">
            <div class="flex gap-3 items-start">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4"/>
                    <path d="M12 8h.01"/>
                </svg>
                <p>Endringer lagres automatisk når du sender inn skjemaet.</p>
            </div>
            <div class="flex gap-3 items-start">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4"/>
                    <path d="M12 8h.01"/>
                </svg>
                <p>Kun hovedkontakt kan redigere foretaksinformasjon.</p>
            </div>
            <div class="flex gap-3 items-start">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4"/>
                    <path d="M12 8h.01"/>
                </svg>
                <p>Trenger du å endre hovedkontakt? <a href="mailto:post@bimverdi.no" class="text-[#1A1A1A] underline">Kontakt oss</a>.</p>
            </div>
        </div>
    </div>
    
    <!-- Back Link -->
    <div class="mt-8 pt-6 border-t border-[#E5E0D5]">
        <a href="<?php echo esc_url(home_url('/min-side/foretak/')); ?>" class="inline-flex items-center gap-2 text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Tilbake til foretak
        </a>
    </div>
</div>
