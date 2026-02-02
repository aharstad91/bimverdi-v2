<?php
/**
 * Min Side - Dashboard Part
 * 
 * Shows quick stats, shortcuts, and profile overview.
 * Used by template-minside-universal.php
 * 
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get company ID from various sources
$company_id = get_user_meta($user_id, 'bimverdi_company_id', true);
if (empty($company_id)) {
    $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
}
if (empty($company_id) && function_exists('get_field')) {
    $acf_company = get_field('tilknyttet_foretak', 'user_' . $user_id);
    $company_id = is_object($acf_company) ? $acf_company->ID : $acf_company;
}

$company = $company_id ? get_post($company_id) : null;

// Get user's tools count
$my_tools_count = 0;
if ($company_id) {
    $hovedkontakt = get_field('hovedkontaktperson', $company_id);
    if ($hovedkontakt == $user_id) {
        $tools = get_posts([
            'post_type' => 'verktoy',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'pending'],
            'meta_query' => [['key' => 'eier_leverandor', 'value' => $company_id]]
        ]);
        $my_tools_count = count($tools);
    }
}

// Get upcoming events count
$upcoming_events = get_posts([
    'post_type' => 'arrangement',
    'posts_per_page' => -1,
    'meta_key' => 'arrangement_dato',
    'meta_query' => [[
        'key' => 'arrangement_dato',
        'value' => date('Y-m-d'),
        'compare' => '>=',
        'type' => 'DATE'
    ]]
]);
$events_count = count($upcoming_events);

?>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => __('Dashbord', 'bimverdi'),
    'description' => sprintf(__('Velkommen tilbake, %s', 'bimverdi'), $current_user->display_name),
]); ?>

<!-- Success Messages -->
<?php if (isset($_GET['welcome']) && $_GET['welcome'] == '1'):
    $first_name = $current_user->first_name ?: $current_user->display_name;
?>
    <div class="mb-6 px-4 py-3 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <p class="text-sm text-green-800">
            <strong><?php printf(__('Velkommen, %s!', 'bimverdi'), esc_html($first_name)); ?></strong>
            <?php _e('Kontoen din er nå aktivert.', 'bimverdi'); ?>
        </p>
    </div>
<?php endif; ?>

<!-- Quick Stats -->
<div class="grid grid-cols-2 mb-12">

    <!-- Mine verktøy -->
    <div class="py-4 pr-6 border-r border-[#D6D1C6]">
        <div class="flex items-center gap-2 mb-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#888]"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
            <h3 class="text-sm text-[#5A5A5A]"><?php _e('Mine verktøy', 'bimverdi'); ?></h3>
        </div>
        <p class="text-2xl font-semibold text-[#1A1A1A] mb-1"><?php echo $my_tools_count; ?></p>
        <a href="<?php echo home_url('/min-side/mine-verktoy/'); ?>" class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A]">
            <?php _e('Se alle', 'bimverdi'); ?> →
        </a>
    </div>

    <!-- Kommende arrangementer -->
    <div class="py-4 pl-6">
        <div class="flex items-center gap-2 mb-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#888]"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <h3 class="text-sm text-[#5A5A5A]"><?php _e('Arrangementer', 'bimverdi'); ?></h3>
        </div>
        <p class="text-2xl font-semibold text-[#1A1A1A] mb-1"><?php echo $events_count; ?></p>
        <a href="<?php echo home_url('/min-side/arrangementer/'); ?>" class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A]">
            <?php _e('Se alle', 'bimverdi'); ?> →
        </a>
    </div>
</div>

<!-- Snarveier Section (Subtle action links) -->
<div class="mb-12">
    <h2 class="text-lg font-semibold text-[#1A1A1A] mb-4"><?php _e('Snarveier', 'bimverdi'); ?></h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">

        <a href="<?php echo home_url('/min-side/registrer-verktoy/'); ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#F2F0EB] transition-colors group">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#888] group-hover:text-[#1A1A1A]"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span class="text-sm font-medium text-[#1A1A1A]"><?php _e('Registrer verktøy', 'bimverdi'); ?></span>
        </a>

        <a href="<?php echo home_url('/min-side/foretak/'); ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#F2F0EB] transition-colors group">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#888] group-hover:text-[#1A1A1A]"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path></svg>
            <span class="text-sm font-medium text-[#1A1A1A]"><?php _e('Mitt foretak', 'bimverdi'); ?></span>
        </a>

        <a href="<?php echo home_url('/min-side/profil/'); ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#F2F0EB] transition-colors group">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#888] group-hover:text-[#1A1A1A]"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span class="text-sm font-medium text-[#1A1A1A]"><?php _e('Min profil', 'bimverdi'); ?></span>
        </a>

    </div>
</div>

<!-- Foretak Info (Borderless section with divider) -->
<?php if ($company): ?>
<div class="pt-8 border-t border-[#D6D1C6]">
    <h2 class="text-lg font-semibold text-[#1A1A1A] mb-4"><?php _e('Mitt foretak', 'bimverdi'); ?></h2>
    <div class="flex items-start gap-4">
        <?php
        $logo = get_field('logo', $company_id);
        if ($logo): ?>
            <img src="<?php echo esc_url($logo['sizes']['thumbnail']); ?>" alt="" class="w-14 h-14 rounded-lg object-cover">
        <?php else: ?>
            <div class="w-14 h-14 rounded-lg bg-[#F2F0EB] flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888]"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path></svg>
            </div>
        <?php endif; ?>

        <div class="flex-1 min-w-0">
            <h3 class="text-base font-semibold text-[#1A1A1A]"><?php echo esc_html(get_the_title($company_id)); ?></h3>
            <?php $org_nr = get_field('organisasjonsnummer', $company_id); ?>
            <?php if ($org_nr): ?>
                <p class="text-sm text-[#5A5A5A]"><?php _e('Org.nr:', 'bimverdi'); ?> <?php echo esc_html($org_nr); ?></p>
            <?php endif; ?>
            <a href="<?php echo home_url('/min-side/foretak/'); ?>" class="inline-block mt-2 text-sm text-[#5A5A5A] hover:text-[#1A1A1A]">
                <?php _e('Vis detaljer', 'bimverdi'); ?> →
            </a>
        </div>
    </div>
</div>
<?php else: ?>
<!-- No company connected -->
<div class="pt-8 border-t border-[#D6D1C6]">
    <h2 class="text-lg font-semibold text-[#1A1A1A] mb-4"><?php _e('Mitt foretak', 'bimverdi'); ?></h2>
    <p class="text-[#5A5A5A] mb-4"><?php _e('Du er ikke koblet til et foretak ennå.', 'bimverdi'); ?></p>
    <?php bimverdi_button([
        'text'    => __('Registrer foretak', 'bimverdi'),
        'variant' => 'primary',
        'href'    => home_url('/min-side/registrer-foretak/'),
        'icon'    => 'plus',
    ]); ?>
</div>
<?php endif; ?>
