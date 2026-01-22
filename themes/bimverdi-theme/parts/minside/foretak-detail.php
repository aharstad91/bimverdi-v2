<?php
/**
 * Min Side - Foretak Detail Part
 * 
 * Shows company information and edit options.
 * Used by template-minside-universal.php
 * 
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get company ID
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
if (empty($company_id)) {
    $company_id = get_user_meta($user_id, 'bimverdi_company_id', true);
}

$company = $company_id ? get_post($company_id) : null;

// Check if user is hovedkontakt
$is_hovedkontakt = false;
if ($company_id) {
    $hovedkontakt = get_field('hovedkontaktperson', $company_id);
    $is_hovedkontakt = ($hovedkontakt == $user_id);
}
?>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => __('Mitt foretak', 'bimverdi'),
    'description' => $company ? get_the_title($company_id) : __('Administrer foretaksinformasjon', 'bimverdi'),
    'actions' => $company && $is_hovedkontakt ? [
        ['text' => __('Rediger foretak', 'bimverdi'), 'url' => '/min-side/rediger-foretak/', 'variant' => 'primary'],
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
    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Main Content (2/3) -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Company Header Card -->
            <div class="bg-white rounded-lg border border-[#E5E0D8] p-6">
                <div class="flex items-start gap-4">
                    <?php $logo = get_field('logo', $company_id); ?>
                    <?php if ($logo): ?>
                        <img src="<?php echo esc_url($logo['sizes']['medium']); ?>" alt="" class="w-20 h-20 rounded-lg object-cover">
                    <?php else: ?>
                        <div class="w-20 h-20 rounded-lg bg-[#F2F0EB] flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path></svg>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex-1">
                        <h2 class="text-xl font-bold text-[#1A1A1A] mb-1"><?php echo esc_html(get_the_title($company_id)); ?></h2>
                        <?php $org_nr = get_field('organisasjonsnummer', $company_id); ?>
                        <?php if ($org_nr): ?>
                            <p class="text-sm text-[#5A5A5A] mb-2"><?php _e('Org.nr:', 'bimverdi'); ?> <?php echo esc_html($org_nr); ?></p>
                        <?php endif; ?>
                        
                        <?php 
                        $bransjer = get_the_terms($company_id, 'bransjekategori');
                        if ($bransjer && !is_wp_error($bransjer)): 
                        ?>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($bransjer as $bransje): ?>
                                    <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-2 py-1 rounded">
                                        <?php echo esc_html($bransje->name); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Description Section -->
            <?php $beskrivelse = get_field('beskrivelse', $company_id); ?>
            <?php if ($beskrivelse): ?>
            <div>
                <h3 class="text-lg font-semibold text-[#1A1A1A] mb-3"><?php _e('Om foretaket', 'bimverdi'); ?></h3>
                <div class="text-sm text-[#5A5A5A] leading-relaxed">
                    <?php echo wp_kses_post($beskrivelse); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Contact Info Section -->
            <div>
                <h3 class="text-lg font-semibold text-[#1A1A1A] mb-4"><?php _e('Kontaktinformasjon', 'bimverdi'); ?></h3>
                <div class="bg-white rounded-lg border border-[#E5E0D8] divide-y divide-[#E5E0D8]">
                    
                    <?php
                    $adresse = get_field('adresse', $company_id);
                    $postnummer = get_field('postnummer', $company_id);
                    $poststed = get_field('poststed', $company_id);
                    $land = get_field('land', $company_id);
                    ?>
                    <?php if ($adresse || $postnummer || $poststed): ?>
                    <div class="flex items-start gap-3 p-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A] flex-shrink-0 mt-0.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
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
                    <div class="flex items-start gap-3 p-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A] flex-shrink-0 mt-0.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        <div>
                            <p class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide mb-1"><?php _e('Telefon', 'bimverdi'); ?></p>
                            <p class="text-sm text-[#1A1A1A]"><?php echo esc_html($telefon); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php $epost = get_field('epost', $company_id); ?>
                    <?php if ($epost): ?>
                    <div class="flex items-start gap-3 p-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A] flex-shrink-0 mt-0.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        <div>
                            <p class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide mb-1"><?php _e('E-post', 'bimverdi'); ?></p>
                            <p class="text-sm text-[#1A1A1A]"><?php echo esc_html($epost); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php $nettside = get_field('nettside', $company_id); ?>
                    <?php if ($nettside): ?>
                    <div class="flex items-start gap-3 p-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A] flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                        <div>
                            <p class="text-xs font-medium text-[#5A5A5A] uppercase tracking-wide mb-1"><?php _e('Nettside', 'bimverdi'); ?></p>
                            <a href="<?php echo esc_url($nettside); ?>" target="_blank" class="text-sm text-[#1A1A1A] hover:text-[#5A5A5A]">
                                <?php echo esc_html($nettside); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
            
        </div>
        
        <!-- Sidebar (1/3) -->
        <div class="space-y-6">
            
            <!-- Status -->
            <div>
                <h3 class="text-sm font-semibold text-[#5A5A5A] uppercase tracking-wide mb-3"><?php _e('Status', 'bimverdi'); ?></h3>
                <div class="bg-white rounded-lg border border-[#E5E0D8] p-4">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        <span class="text-sm font-medium text-[#1A1A1A]"><?php _e('Aktivt medlemskap', 'bimverdi'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <?php if ($is_hovedkontakt): ?>
            <div>
                <h3 class="text-sm font-semibold text-[#5A5A5A] uppercase tracking-wide mb-3"><?php _e('Snarveier', 'bimverdi'); ?></h3>
                <div class="space-y-2">
                    <a href="<?php echo home_url('/min-side/rediger-foretak/'); ?>" class="flex items-center gap-3 p-3 bg-white rounded-lg border border-[#E5E0D8] hover:border-[#1A1A1A] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        <span class="text-sm font-medium text-[#1A1A1A]"><?php _e('Rediger foretak', 'bimverdi'); ?></span>
                    </a>
                    <a href="<?php echo home_url('/min-side/mine-verktoy/'); ?>" class="flex items-center gap-3 p-3 bg-white rounded-lg border border-[#E5E0D8] hover:border-[#1A1A1A] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                        <span class="text-sm font-medium text-[#1A1A1A]"><?php _e('Administrer verktøy', 'bimverdi'); ?></span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
<?php endif; ?>
