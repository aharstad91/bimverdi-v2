<?php
/**
 * Part: Rediger profil
 * 
 * Skjema for redigering av brukerprofil via Gravity Forms (ID 4).
 * Brukes på /min-side/rediger-profil/
 * 
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user data for display
$first_name = get_user_meta($user_id, 'first_name', true);
$last_name = get_user_meta($user_id, 'last_name', true);
$display_name = trim($first_name . ' ' . $last_name) ?: $current_user->display_name;
$avatar_url = get_avatar_url($user_id, ['size' => 80]);
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
            <a href="<?php echo esc_url(home_url('/min-side/profil/')); ?>" class="hover:text-[#1A1A1A] transition-colors">
                Profil
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
    'title' => 'Rediger profil',
    'description' => 'Oppdater din personlige informasjon'
]);
?>

<!-- Form Container (960px centered per _claude/ui-contract.md) -->
<div class="max-w-3xl mx-auto">
    
    <!-- User Info Badge -->
    <div class="mb-8 p-4 bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg flex items-center gap-4">
        <img src="<?php echo esc_url($avatar_url); ?>" alt="" class="w-14 h-14 rounded-full object-cover">
        <div>
            <p class="font-semibold text-[#1A1A1A]"><?php echo esc_html($display_name); ?></p>
            <p class="text-sm text-[#5A5A5A]"><?php echo esc_html($current_user->user_email); ?></p>
        </div>
    </div>
    
    <!-- Gravity Form -->
    <div class="bg-white border border-[#E5E0D5] rounded-lg p-8">
        <h2 class="text-xl font-bold text-[#1A1A1A] mb-6 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            Din informasjon
        </h2>
        
        <?php
        // Display Gravity Form ID 4 [Bruker] - Redigering av profil
        if (function_exists('gravity_form')) {
            gravity_form(4, false, false, false, null, true);
        } else {
            echo '<div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">';
            echo '<strong>Feil:</strong> Skjema er ikke tilgjengelig. Vennligst kontakt administrator.';
            echo '</div>';
        }
        ?>
    </div>
    
    <!-- Additional Actions -->
    <div class="mt-8 pt-8 border-t border-[#D6D1C6]">
        <h3 class="text-sm font-bold text-[#1A1A1A] uppercase tracking-wider mb-4">Andre innstillinger</h3>
        
        <div class="grid gap-4 md:grid-cols-2">
            <a href="<?php echo esc_url(home_url('/min-side/endre-passord/')); ?>" class="flex items-center gap-3 p-4 bg-white border border-[#E5E0D5] rounded-lg hover:border-[#1A1A1A] transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <div>
                    <p class="font-medium text-[#1A1A1A]">Endre passord</p>
                    <p class="text-xs text-[#5A5A5A]">Oppdater ditt kontopassord</p>
                </div>
            </a>
            
            <a href="<?php echo esc_url(home_url('/min-side/foretak/')); ?>" class="flex items-center gap-3 p-4 bg-white border border-[#E5E0D5] rounded-lg hover:border-[#1A1A1A] transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
                    <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/>
                    <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/>
                    <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/>
                </svg>
                <div>
                    <p class="font-medium text-[#1A1A1A]">Mitt foretak</p>
                    <p class="text-xs text-[#5A5A5A]">Administrer foretaksprofilen</p>
                </div>
            </a>
        </div>
    </div>
    
    <!-- Back Link -->
    <div class="mt-8 pt-6 border-t border-[#E5E0D5]">
        <a href="<?php echo esc_url(home_url('/min-side/profil/')); ?>" class="inline-flex items-center gap-2 text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Tilbake til profil
        </a>
    </div>
</div>
