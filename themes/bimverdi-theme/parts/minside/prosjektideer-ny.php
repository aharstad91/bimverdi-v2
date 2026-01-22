<?php
/**
 * Min Side - Send inn ny prosjektidé
 *
 * Skjema for å sende inn prosjektidé via Gravity Forms.
 * Merk: Prosjektidéer kan IKKE redigeres etter innsending (per PRD).
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

// Access control - require company
if (!bimverdi_can_access('submit_case')) {
    wp_redirect(bimverdi_minside_url());
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user's company (check both meta keys for legacy compatibility)
$company_id = get_user_meta($user_id, 'bimverdi_company_id', true);
if (!$company_id) {
    $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
}
$company_name = $company_id ? get_the_title($company_id) : '';

// Find project idea form (search for "prosjektidé" or "case" in form title)
$idea_form_id = 0;
if (class_exists('GFAPI')) {
    $forms = GFAPI::get_forms();
    foreach ($forms as $form) {
        $title_lower = strtolower($form['title']);
        if (strpos($title_lower, 'prosjektid') !== false ||
            strpos($title_lower, 'prosjekt id') !== false ||
            strpos($title_lower, 'case') !== false) {
            $idea_form_id = $form['id'];
            break;
        }
    }
}
?>

<!-- Breadcrumb -->
<nav class="mb-6" aria-label="Brødsmulesti">
    <ol class="flex items-center gap-2 text-sm text-[#5A5A5A]">
        <li>
            <a href="<?php echo esc_url(bimverdi_minside_url()); ?>" class="hover:text-[#1A1A1A] transition-colors">
                Min side
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li>
            <a href="<?php echo esc_url(bimverdi_minside_url('prosjektideer')); ?>" class="hover:text-[#1A1A1A] transition-colors">
                <?php _e('Mine prosjektidéer', 'bimverdi'); ?>
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li class="text-[#1A1A1A] font-medium" aria-current="page"><?php _e('Ny idé', 'bimverdi'); ?></li>
    </ol>
</nav>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => __('Send inn prosjektidé', 'bimverdi'),
    'description' => __('Foreslå et pilotprosjekt eller samarbeidsinitiativ for BIM Verdi-nettverket', 'bimverdi'),
]); ?>

<!-- Form Container (960px centered per UI-CONTRACT.md) -->
<div class="max-w-3xl mx-auto">

    <!-- Important Notice -->
    <div class="mb-8 p-4 bg-amber-50 border border-amber-200 rounded-lg">
        <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-amber-600 flex-shrink-0 mt-0.5">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                <path d="M12 9v4"/>
                <path d="M12 17h.01"/>
            </svg>
            <div>
                <p class="font-semibold text-amber-800 mb-1"><?php _e('Viktig', 'bimverdi'); ?></p>
                <p class="text-sm text-amber-700">
                    <?php _e('Prosjektidéer kan ikke redigeres etter innsending. Sørg for at informasjonen er komplett før du sender inn.', 'bimverdi'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Author Info Badge -->
    <?php if ($company_name): ?>
    <div class="mb-8 p-4 bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg inline-flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-[#5A5A5A]"><?php _e('Forslagsstiller', 'bimverdi'); ?></span>
            <p class="text-[#1A1A1A] font-semibold">
                <?php echo esc_html($current_user->display_name); ?>
                <span class="text-[#5A5A5A] font-normal"><?php _e('fra', 'bimverdi'); ?> <?php echo esc_html($company_name); ?></span>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Gravity Form -->
    <div class="bg-white border border-[#E5E0D5] rounded-lg p-8">
        <h2 class="text-xl font-bold text-[#1A1A1A] mb-6 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
                <path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/>
                <path d="M9 18h6"/>
                <path d="M10 22h4"/>
            </svg>
            <?php _e('Beskriv din idé', 'bimverdi'); ?>
        </h2>

        <?php
        if ($idea_form_id && function_exists('gravity_form')) {
            gravity_form($idea_form_id, false, false, false, null, true);
        } else {
            ?>
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <p class="text-amber-800 font-semibold mb-2"><?php _e('Skjema ikke tilgjengelig', 'bimverdi'); ?></p>
                <p class="text-amber-700 text-sm">
                    <?php _e('Prosjektidé-skjemaet er ikke satt opp ennå.', 'bimverdi'); ?>
                    <?php if (current_user_can('manage_options')): ?>
                        <a href="<?php echo admin_url('admin.php?page=gf_new_form'); ?>" class="text-amber-900 underline"><?php _e('Opprett skjema i Gravity Forms', 'bimverdi'); ?></a>
                    <?php else: ?>
                        <?php _e('Ta kontakt med administrator.', 'bimverdi'); ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php
        }
        ?>
    </div>

    <!-- Submission Info -->
    <div class="mt-12 pt-8 border-t border-[#D6D1C6]">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="p-5 bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg">
                <h3 class="font-semibold text-[#1A1A1A] mb-3 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#eab308" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/>
                        <path d="M9 18h6"/>
                        <path d="M10 22h4"/>
                    </svg>
                    <?php _e('Hva er en god idé?', 'bimverdi'); ?>
                </h3>
                <ul class="space-y-2 text-sm text-[#5A5A5A]">
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <?php _e('Løser et reelt problem for bransjen', 'bimverdi'); ?>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <?php _e('Krever samarbeid mellom flere aktører', 'bimverdi'); ?>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <?php _e('Har konkret og målbart utfall', 'bimverdi'); ?>
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
                    <?php _e('Hva skjer videre?', 'bimverdi'); ?>
                </h3>
                <ul class="space-y-2 text-sm text-[#5A5A5A]">
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <?php _e('Idéen vurderes av BIM Verdi-administrasjonen', 'bimverdi'); ?>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <?php _e('Du får tilbakemelding på statusendringer', 'bimverdi'); ?>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <?php _e('Godkjente idéer kan bli pilotprosjekter', 'bimverdi'); ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Back Link -->
    <div class="mt-8 pt-6 border-t border-[#E5E0D5]">
        <a href="<?php echo esc_url(bimverdi_minside_url('prosjektideer')); ?>" class="inline-flex items-center gap-2 text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            <?php _e('Tilbake til mine prosjektidéer', 'bimverdi'); ?>
        </a>
    </div>
</div>
