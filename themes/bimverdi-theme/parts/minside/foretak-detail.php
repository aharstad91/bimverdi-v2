<?php
/**
 * Min Side - Foretak Detail Part
 *
 * Shows company information with account sidenav.
 * Follows Variant B design system (dividers, not boxes).
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get company ID
$company_id = bimverdi_get_user_company($user_id);
$company = $company_id ? get_post($company_id) : null;

// Check if user is hovedkontakt
$is_hovedkontakt = bimverdi_is_hovedkontakt($user_id, $company_id);

// Check if company is active
$is_active = $company_id ? bimverdi_is_company_active($company_id) : false;
?>

<!-- Account Layout with Sidenav -->
<?php get_template_part('parts/components/account-layout', null, [
    'title' => __('Mitt foretak', 'bimverdi'),
    'description' => $company ? get_the_title($company_id) : __('Administrer foretaksinformasjon', 'bimverdi'),
    'actions' => $company && $is_hovedkontakt ? [
        ['text' => __('Rediger foretak', 'bimverdi'), 'url' => bimverdi_minside_url('foretak/rediger'), 'variant' => 'primary'],
    ] : [],
]); ?>

<?php if (!$company): ?>
    <!-- No Company Connected -->
    <?php get_template_part('parts/components/empty-state', null, [
        'icon' => 'building-2',
        'title' => __('Ikke koblet til et foretak', 'bimverdi'),
        'description' => __('Du må være tilknyttet et foretak for å se informasjon her. Registrer et nytt foretak eller be om invitasjon fra et eksisterende.', 'bimverdi'),
        'cta_text' => __('Registrer foretak', 'bimverdi'),
        'cta_url' => '/min-side/registrer-foretak/',
    ]); ?>

<?php else: ?>

    <div class="space-y-8">

        <!-- Company Header (Variant B: no box, divider below) -->
        <div class="pb-8 border-b border-[#D6D1C6]">
            <div class="flex items-start gap-4">
                <?php $logo = get_field('logo', $company_id); ?>
                <?php if ($logo): ?>
                    <img src="<?php echo esc_url($logo['sizes']['medium']); ?>" alt="" class="w-20 h-20 rounded-lg object-cover flex-shrink-0">
                <?php else: ?>
                    <div class="w-20 h-20 rounded-lg bg-[#F2F0EB] flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888]"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path></svg>
                    </div>
                <?php endif; ?>

                <div class="flex-1 min-w-0">
                    <h2 class="text-xl font-bold text-[#1A1A1A] mb-1"><?php echo esc_html(get_the_title($company_id)); ?></h2>

                    <?php $org_nr = get_field('organisasjonsnummer', $company_id); ?>
                    <?php if ($org_nr): ?>
                        <p class="text-sm text-[#5A5A5A] mb-2"><?php _e('Org.nr:', 'bimverdi'); ?> <?php echo esc_html($org_nr); ?></p>
                    <?php endif; ?>

                    <?php
                    $bransjer = get_the_terms($company_id, 'bransjekategori');
                    if ($bransjer && !is_wp_error($bransjer)):
                    ?>
                        <div class="flex flex-wrap gap-2 mb-3">
                            <?php foreach ($bransjer as $bransje): ?>
                                <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-2 py-1 rounded">
                                    <?php echo esc_html($bransje->name); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Membership Status inline -->
                    <div class="flex items-center gap-2 mt-3">
                        <?php if ($is_active): ?>
                            <span class="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"></span>
                            <span class="text-xs text-[#5A5A5A]"><?php _e('Aktiv deltaker', 'bimverdi'); ?></span>
                        <?php else: ?>
                            <span class="w-2 h-2 rounded-full bg-amber-500 flex-shrink-0"></span>
                            <span class="text-xs text-[#5A5A5A]"><?php _e('Inaktiv deltaker', 'bimverdi'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description Section (if exists) -->
        <?php $beskrivelse = get_field('beskrivelse', $company_id); ?>
        <?php if ($beskrivelse): ?>
        <div class="pb-8 border-b border-[#D6D1C6]">
            <h3 class="text-lg font-semibold text-[#1A1A1A] mb-3"><?php _e('Om foretaket', 'bimverdi'); ?></h3>
            <div class="text-sm text-[#5A5A5A] leading-relaxed prose prose-sm max-w-none">
                <?php echo wp_kses_post($beskrivelse); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contact Info Section (Variant B: stacked items with dividers, no box) -->
        <div>
            <h3 class="text-lg font-semibold text-[#1A1A1A] mb-4"><?php _e('Kontaktinformasjon', 'bimverdi'); ?></h3>
            <div class="divide-y divide-[#E5E0D8]">

                <?php
                $adresse = get_field('adresse', $company_id);
                $postnummer = get_field('postnummer', $company_id);
                $poststed = get_field('poststed', $company_id);
                $land = get_field('land', $company_id);
                ?>
                <?php if ($adresse || $postnummer || $poststed): ?>
                <div class="flex items-start gap-3 py-4 first:pt-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888] flex-shrink-0 mt-0.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <div>
                        <p class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide mb-1"><?php _e('Adresse', 'bimverdi'); ?></p>
                        <?php if ($adresse): ?>
                            <p class="text-sm text-[#1A1A1A]"><?php echo esc_html($adresse); ?></p>
                        <?php endif; ?>
                        <?php if ($postnummer || $poststed): ?>
                            <p class="text-sm text-[#1A1A1A]"><?php echo esc_html(trim($postnummer . ' ' . $poststed)); ?></p>
                        <?php endif; ?>
                        <?php if ($land && $land !== 'Norge'): ?>
                            <p class="text-sm text-[#1A1A1A]"><?php echo esc_html($land); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php $telefon = get_field('telefon', $company_id); ?>
                <?php if ($telefon): ?>
                <div class="flex items-start gap-3 py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888] flex-shrink-0 mt-0.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    <div>
                        <p class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide mb-1"><?php _e('Telefon', 'bimverdi'); ?></p>
                        <p class="text-sm text-[#1A1A1A]"><?php echo esc_html($telefon); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php $epost = get_field('epost', $company_id); ?>
                <?php if ($epost): ?>
                <div class="flex items-start gap-3 py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888] flex-shrink-0 mt-0.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    <div>
                        <p class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide mb-1"><?php _e('E-post', 'bimverdi'); ?></p>
                        <p class="text-sm text-[#1A1A1A]"><?php echo esc_html($epost); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php $nettside = get_field('hjemmeside', $company_id); ?>
                <?php if ($nettside): ?>
                <div class="flex items-start gap-3 py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888] flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                    <div>
                        <p class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide mb-1"><?php _e('Nettside', 'bimverdi'); ?></p>
                        <a href="<?php echo esc_url($nettside); ?>" target="_blank" rel="noopener" class="text-sm text-[#1A1A1A] hover:text-[#FF8B5E] transition-colors">
                            <?php echo esc_html($nettside); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>

    </div>

<?php endif; ?>

<?php get_template_part('parts/components/account-layout-end'); ?>
