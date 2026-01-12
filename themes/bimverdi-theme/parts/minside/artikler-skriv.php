<?php
/**
 * Part: Skriv artikkel
 * 
 * Skjema for å skrive ny artikkel via Gravity Forms.
 * Brukes på /min-side/skriv-artikkel/
 * 
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user's company
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
$company_name = $company_id ? get_the_title($company_id) : '';

// Get article form ID
$article_form_id = 0;
if (class_exists('GFAPI')) {
    $forms = GFAPI::get_forms();
    foreach ($forms as $form) {
        if (strpos($form['title'], 'Skriv artikkel') !== false || strpos($form['title'], 'artikkel') !== false) {
            $article_form_id = $form['id'];
            break;
        }
    }
}
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
            <a href="<?php echo esc_url(home_url('/min-side/artikler/')); ?>" class="hover:text-[#1A1A1A] transition-colors">
                Mine artikler
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li class="text-[#1A1A1A] font-medium" aria-current="page">Skriv artikkel</li>
    </ol>
</nav>

<!-- Page Header -->
<?php
get_template_part('parts/components/page-header', null, [
    'title' => 'Skriv artikkel',
    'description' => 'Del din kunnskap og erfaring med BIM-nettverket'
]);
?>

<!-- Form Container (960px centered per UI-CONTRACT.md) -->
<div class="max-w-3xl mx-auto">
    
    <!-- Author Info Badge -->
    <?php if ($company_name): ?>
    <div class="mb-8 p-4 bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg inline-flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-[#5A5A5A]">Forfatter</span>
            <p class="text-[#1A1A1A] font-semibold">
                <?php echo esc_html($current_user->display_name); ?>
                <span class="text-[#5A5A5A] font-normal">fra <?php echo esc_html($company_name); ?></span>
            </p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Gravity Form -->
    <div class="bg-white border border-[#E5E0D5] rounded-lg p-8">
        <h2 class="text-xl font-bold text-[#1A1A1A] mb-6 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
                <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.375 2.625a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4Z"/>
            </svg>
            Skriv din artikkel
        </h2>
        
        <?php
        if ($article_form_id && function_exists('gravity_form')) {
            gravity_form($article_form_id, false, false, false, null, true);
        } else {
            ?>
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <p class="text-amber-800 font-semibold mb-2">Skjema ikke tilgjengelig</p>
                <p class="text-amber-700 text-sm">
                    Artikkelskjemaet er ikke satt opp ennå.
                    <?php if (current_user_can('manage_options')): ?>
                        <a href="<?php echo admin_url('admin.php?page=gf_new_form'); ?>" class="text-amber-900 underline">Opprett skjema i Gravity Forms</a>
                    <?php else: ?>
                        Ta kontakt med administrator.
                    <?php endif; ?>
                </p>
            </div>
            <?php
        }
        ?>
    </div>
    
    <!-- Writing Tips -->
    <div class="mt-12 pt-8 border-t border-[#D6D1C6]">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="p-5 bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg">
                <h3 class="font-semibold text-[#1A1A1A] mb-3 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#eab308" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/>
                        <path d="M9 18h6"/>
                        <path d="M10 22h4"/>
                    </svg>
                    Tips for gode artikler
                </h3>
                <ul class="space-y-2 text-sm text-[#5A5A5A]">
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Velg en tydelig og beskrivende tittel
                    </li>
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Del praktisk erfaring og konkrete eksempler
                    </li>
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Inkluder bilder eller skjermbilder hvis relevant
                    </li>
                </ul>
            </div>
            
            <div class="p-5 bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg">
                <h3 class="font-semibold text-[#1A1A1A] mb-3 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4"/>
                        <path d="M12 8h.01"/>
                    </svg>
                    Publiseringsregler
                </h3>
                <ul class="space-y-2 text-sm text-[#5A5A5A]">
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Artikkelen går til gjennomgang før publisering
                    </li>
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Du kan redigere artikkelen etter at den er publisert
                    </li>
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Artikler med lenker til din bedrift er velkomne
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Back Link -->
    <div class="mt-8 pt-6 border-t border-[#E5E0D5]">
        <a href="<?php echo esc_url(home_url('/min-side/artikler/')); ?>" class="inline-flex items-center gap-2 text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Tilbake til mine artikler
        </a>
    </div>
</div>
