<?php
/**
 * Part: Registrer kunnskapskilde
 *
 * Skjema for registrering av ny kunnskapskilde via Gravity Forms.
 * Brukes på /min-side/kunnskapskilder/registrer/
 *
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

// Company is optional for kunnskapskilder
$company = $company_id ? get_post($company_id) : null;
?>

<!-- Breadcrumb -->
<nav class="mb-6" aria-label="Brødsmulesti">
    <ol class="flex items-center gap-2 text-sm text-[#5A5A5A]">
        <li>
            <a href="<?php echo esc_url(bimverdi_minside_url('')); ?>" class="hover:text-[#1A1A1A] transition-colors">
                Min side
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li>
            <a href="<?php echo esc_url(bimverdi_minside_url('kunnskapskilder')); ?>" class="hover:text-[#1A1A1A] transition-colors">
                Kunnskapskilder
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li class="text-[#1A1A1A] font-medium" aria-current="page">Registrer kunnskapskilde</li>
    </ol>
</nav>

<!-- Page Header -->
<?php
get_template_part('parts/components/page-header', null, [
    'title' => 'Registrer kunnskapskilde',
    'description' => 'Del standarder, veiledere og andre ressurser med BIM Verdi-nettverket'
]);
?>

<!-- Form Container (960px centered per _claude/ui-contract.md) -->
<div class="max-w-3xl mx-auto">

    <!-- Company Info - Borderless Section -->
    <?php if ($company): ?>
    <div class="mb-12">
        <div class="flex items-center gap-3 text-sm text-[#5A5A5A]">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="2" width="20" height="8" rx="2" ry="2"/>
                <rect x="2" y="14" width="20" height="8" rx="2" ry="2"/>
                <line x1="6" y1="6" x2="6.01" y2="6"/>
                <line x1="6" y1="18" x2="6.01" y2="18"/>
            </svg>
            <span>Registrerer for:</span>
            <strong class="text-[#1A1A1A]"><?php echo esc_html($company->post_title); ?></strong>
        </div>
    </div>
    <?php endif; ?>

    <!-- Divider -->
    <div class="border-t border-[#E5E0D8] mb-8"></div>

    <!-- Form Section - Borderless -->
    <div class="mb-12">
        <h2 class="text-lg font-semibold text-[#1A1A1A] mb-6">Kunnskapskildedetaljer</h2>

        <?php
        // Display Gravity Form - dynamically gets/creates form ID
        $form_id = function_exists('bim_verdi_get_kunnskapskilde_form_id')
            ? bim_verdi_get_kunnskapskilde_form_id()
            : null;

        if ($form_id && function_exists('gravity_form')) {
            gravity_form($form_id, false, false, false, null, true);
        } else {
            echo '<div class="p-4 bg-red-50 border border-red-200 rounded text-red-700">';
            echo '<strong>Feil:</strong> Skjema er ikke tilgjengelig. Vennligst kontakt administrator.';
            echo '</div>';
        }
        ?>
    </div>

    <!-- Divider -->
    <div class="border-t border-[#E5E0D8] mb-8"></div>

    <!-- Information Section - Borderless -->
    <div class="mb-12">
        <h3 class="text-sm font-semibold text-[#1A1A1A] uppercase tracking-wider mb-6">Om kunnskapskilderegistrering</h3>

        <div class="space-y-4">
            <div class="flex gap-3 items-start">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A] flex-shrink-0 mt-0.5">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <div>
                    <p class="font-medium text-[#1A1A1A]">Del kunnskap</p>
                    <p class="text-sm text-[#5A5A5A] mt-0.5">Legg til standarder, veiledere, maler og andre nyttige ressurser</p>
                </div>
            </div>

            <div class="flex gap-3 items-start">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A] flex-shrink-0 mt-0.5">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <div>
                    <p class="font-medium text-[#1A1A1A]">Unike lenker</p>
                    <p class="text-sm text-[#5A5A5A] mt-0.5">Hver ekstern lenke kan kun registreres én gang i systemet</p>
                </div>
            </div>

            <div class="flex gap-3 items-start">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A] flex-shrink-0 mt-0.5">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <div>
                    <p class="font-medium text-[#1A1A1A]">Synlig etter godkjenning</p>
                    <p class="text-sm text-[#5A5A5A] mt-0.5">Kunnskapskilden vises i katalogen etter administratorgodkjenning</p>
                </div>
            </div>

            <div class="flex gap-3 items-start">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A] flex-shrink-0 mt-0.5">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <div>
                    <p class="font-medium text-[#1A1A1A]">Administrer senere</p>
                    <p class="text-sm text-[#5A5A5A] mt-0.5">Rediger informasjonen når som helst fra Min side</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Divider -->
    <div class="border-t border-[#E5E0D8] mb-6"></div>

    <!-- Back Link -->
    <div class="mb-8">
        <a href="<?php echo esc_url(bimverdi_minside_url('kunnskapskilder')); ?>" class="inline-flex items-center gap-2 text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Tilbake til kunnskapskilder
        </a>
    </div>
</div>
