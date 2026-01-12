<?php
/**
 * Part: Registrer verktøy
 * 
 * Skjema for registrering av nytt verktøy via Gravity Forms.
 * Brukes på /min-side/registrer-verktoy/
 * 
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

// Redirect if not connected to a company
if (!$company_id) {
    wp_redirect(home_url('/min-side/'));
    exit;
}

$company = get_post($company_id);
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
            <a href="<?php echo esc_url(home_url('/min-side/mine-verktoy/')); ?>" class="hover:text-[#1A1A1A] transition-colors">
                Mine verktøy
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li class="text-[#1A1A1A] font-medium" aria-current="page">Registrer verktøy</li>
    </ol>
</nav>

<!-- Page Header -->
<?php
get_template_part('parts/components/page-header', null, [
    'title' => 'Registrer verktøy',
    'description' => 'Del verktøy, programvare og ressurser med BIM Verdi-nettverket'
]);
?>

<!-- Form Container (960px centered per UI-CONTRACT.md) -->
<div class="max-w-3xl mx-auto">
    
    <!-- Company Info Badge -->
    <?php if ($company): ?>
    <div class="mb-8 p-4 bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg inline-flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
            <rect x="2" y="2" width="20" height="8" rx="2" ry="2"/>
            <rect x="2" y="14" width="20" height="8" rx="2" ry="2"/>
            <line x1="6" y1="6" x2="6.01" y2="6"/>
            <line x1="6" y1="18" x2="6.01" y2="18"/>
        </svg>
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-[#5A5A5A]">Din bedrift</span>
            <p class="text-[#1A1A1A] font-semibold"><?php echo esc_html($company->post_title); ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Gravity Form -->
    <div class="bg-white border border-[#E5E0D5] rounded-lg p-8">
        <h2 class="text-xl font-bold text-[#1A1A1A] mb-6 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
            Fyll inn verktøydetaljer
        </h2>
        
        <?php
        // Display Gravity Form ID 1 [Bruker] - Registrering av verktøy
        if (function_exists('gravity_form')) {
            gravity_form(1, false, false, false, null, true);
        } else {
            echo '<div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">';
            echo '<strong>Feil:</strong> Skjema er ikke tilgjengelig. Vennligst kontakt administrator.';
            echo '</div>';
        }
        ?>
    </div>
    
    <!-- Information Section -->
    <div class="mt-12 pt-8 border-t border-[#D6D1C6]">
        <h3 class="text-sm font-bold text-[#1A1A1A] uppercase tracking-wider mb-6 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 16v-4"/>
                <path d="M12 8h.01"/>
            </svg>
            Om verktøyregistrering
        </h3>
        
        <div class="grid gap-4 md:grid-cols-2">
            <div class="flex gap-4 items-start">
                <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-[#1A1A1A] mb-1">Registrer verktøy</p>
                    <p class="text-sm text-[#5A5A5A]">Legg til programvare, plugins eller andre ressurser</p>
                </div>
            </div>
            
            <div class="flex gap-4 items-start">
                <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-[#1A1A1A] mb-1">Synlig for alle medlemmer</p>
                    <p class="text-sm text-[#5A5A5A]">Verktøyet vises i den felles verktøykatalogen</p>
                </div>
            </div>
            
            <div class="flex gap-4 items-start">
                <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-[#1A1A1A] mb-1">Legg til detaljer</p>
                    <p class="text-sm text-[#5A5A5A]">Beskrivelse, lenke og hvilke plattformer som støttes</p>
                </div>
            </div>
            
            <div class="flex gap-4 items-start">
                <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-[#1A1A1A] mb-1">Administrer senere</p>
                    <p class="text-sm text-[#5A5A5A]">Rediger eller slett verktøy når som helst</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Back Link -->
    <div class="mt-8 pt-6 border-t border-[#E5E0D5]">
        <a href="<?php echo esc_url(home_url('/min-side/mine-verktoy/')); ?>" class="inline-flex items-center gap-2 text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Tilbake til mine verktøy
        </a>
    </div>
</div>
