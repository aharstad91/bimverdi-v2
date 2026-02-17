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

// Get user's kunnskapskilder count
$my_kunnskapskilder_count = 0;
$kilde_query_args = [
    'post_type' => 'kunnskapskilde',
    'posts_per_page' => -1,
    'post_status' => ['publish', 'draft', 'pending'],
    'fields' => 'ids',
    'meta_query' => [
        'relation' => 'OR',
        ['key' => 'registrert_av', 'value' => $user_id],
        ['key' => 'tilknyttet_bedrift', 'value' => $company_id ?: 0],
    ],
];
$kilde_posts = get_posts($kilde_query_args);
if (empty($kilde_posts)) {
    $kilde_posts = get_posts([
        'post_type' => 'kunnskapskilde',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'draft', 'pending'],
        'fields' => 'ids',
        'author' => $user_id,
    ]);
}
$my_kunnskapskilder_count = count($kilde_posts);

// Get upcoming events the user is registered for
$my_events_count = 0;
$registrations = get_posts([
    'post_type' => 'pamelding',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'fields' => 'ids',
    'meta_query' => [['key' => 'bruker', 'value' => $user_id]],
]);
$my_events_count = count($registrations);

// Get company role info for welcome message
$user_role_label = '';
$company_role_label = '';
if ($company) {
    $user_role_label = bimverdi_is_hovedkontakt($user_id) ? 'hovedkontakt' : 'tilleggskontakt';
    $bv_rolle = get_field('bv_rolle', $company_id);
    if ($bv_rolle && $bv_rolle !== 'Ikke deltaker') {
        $company_role_label = mb_strtolower($bv_rolle);
    }
}

?>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => __('Dashbord', 'bimverdi'),
    'description' => sprintf(__('Velkommen tilbake, %s', 'bimverdi'), $current_user->display_name),
]); ?>

<?php if ($company && $user_role_label): ?>
<p class="text-sm text-[#57534E] -mt-4 mb-6">
    <?php
    $role_text = sprintf(
        'Du er %s i %s',
        esc_html($user_role_label),
        '<a href="' . esc_url(home_url('/min-side/foretak/')) . '" class="text-[#111827] underline hover:no-underline">' . esc_html(get_the_title($company_id)) . '</a>'
    );
    if ($company_role_label) {
        $role_text .= sprintf(', som er %s i BIM Verdi', esc_html($company_role_label));
    }
    echo $role_text . '.';
    ?>
</p>
<?php endif; ?>

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

<?php if (isset($_GET['invitation_accepted']) && $_GET['invitation_accepted'] == '1'): ?>
    <div class="mb-6 px-4 py-3 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <p class="text-sm text-green-800">
            <strong><?php _e('Invitasjon akseptert!', 'bimverdi'); ?></strong>
            <?php if ($company): ?>
                <?php printf(__('Du er nå koblet til %s.', 'bimverdi'), esc_html($company->post_title)); ?>
            <?php else: ?>
                <?php _e('Du er nå koblet til foretaket.', 'bimverdi'); ?>
            <?php endif; ?>
        </p>
    </div>
<?php endif; ?>

<!-- Quick Stats -->
<div class="grid grid-cols-3 mb-12">

    <!-- Mine verktøy -->
    <div class="py-4 pr-6 border-r border-[#E7E5E4]">
        <div class="flex items-center gap-2 mb-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#888]"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
            <h3 class="text-sm text-[#57534E]"><?php _e('Mine verktøy', 'bimverdi'); ?></h3>
        </div>
        <p class="text-2xl font-semibold text-[#111827] mb-1"><?php echo esc_html($my_tools_count); ?></p>
        <a href="<?php echo esc_url(home_url('/min-side/verktoy/')); ?>" class="text-sm text-[#57534E] hover:text-[#111827]">
            <?php _e('Se alle', 'bimverdi'); ?> →
        </a>
    </div>

    <!-- Mine kunnskapskilder -->
    <div class="py-4 px-6 border-r border-[#E7E5E4]">
        <div class="flex items-center gap-2 mb-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#888]"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
            <h3 class="text-sm text-[#57534E]"><?php _e('Mine kunnskapskilder', 'bimverdi'); ?></h3>
        </div>
        <p class="text-2xl font-semibold text-[#111827] mb-1"><?php echo esc_html($my_kunnskapskilder_count); ?></p>
        <a href="<?php echo esc_url(home_url('/min-side/kunnskapskilder/')); ?>" class="text-sm text-[#57534E] hover:text-[#111827]">
            <?php _e('Se alle', 'bimverdi'); ?> →
        </a>
    </div>

    <!-- Mine arrangementer -->
    <div class="py-4 pl-6">
        <div class="flex items-center gap-2 mb-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#888]"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <h3 class="text-sm text-[#57534E]"><?php _e('Mine arrangementer', 'bimverdi'); ?></h3>
        </div>
        <p class="text-2xl font-semibold text-[#111827] mb-1"><?php echo esc_html($my_events_count); ?></p>
        <a href="<?php echo esc_url(home_url('/min-side/arrangementer/')); ?>" class="text-sm text-[#57534E] hover:text-[#111827]">
            <?php _e('Se alle', 'bimverdi'); ?> →
        </a>
    </div>
</div>

<!-- Snarveier Section (Subtle action links) -->
<div class="mb-12">
    <h2 class="text-lg font-semibold text-[#111827] mb-4"><?php _e('Snarveier', 'bimverdi'); ?></h2>
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">

        <a href="<?php echo home_url('/min-side/registrer-verktoy/'); ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#F5F5F4] transition-colors group">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#888] group-hover:text-[#111827]"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span class="text-sm font-medium text-[#111827]"><?php _e('Registrer verktøy', 'bimverdi'); ?></span>
        </a>

        <a href="<?php echo home_url('/min-side/kunnskapskilder/registrer/'); ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#F5F5F4] transition-colors group">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#888] group-hover:text-[#111827]"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span class="text-sm font-medium text-[#111827]"><?php _e('Registrer kunnskapskilde', 'bimverdi'); ?></span>
        </a>

        <a href="<?php echo home_url('/min-side/foretak/'); ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#F5F5F4] transition-colors group">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#888] group-hover:text-[#111827]"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path></svg>
            <span class="text-sm font-medium text-[#111827]"><?php _e('Mitt foretak', 'bimverdi'); ?></span>
        </a>

        <a href="<?php echo home_url('/min-side/profil/'); ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#F5F5F4] transition-colors group">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#888] group-hover:text-[#111827]"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span class="text-sm font-medium text-[#111827]"><?php _e('Min profil', 'bimverdi'); ?></span>
        </a>

    </div>
</div>

<!-- Foretak Info (Borderless section with divider) -->
<?php if ($company): ?>
<div class="pt-8 border-t border-[#E7E5E4]">
    <h2 class="text-lg font-semibold text-[#111827] mb-4"><?php _e('Mitt foretak', 'bimverdi'); ?></h2>
    <div class="flex items-start gap-4">
        <?php
        $logo = get_field('logo', $company_id);
        $logo_url = '';
        if ($logo) {
            if (is_array($logo)) {
                $logo_url = $logo['sizes']['thumbnail'] ?? $logo['url'] ?? '';
            } else {
                $logo_url = wp_get_attachment_image_url($logo, 'thumbnail') ?: '';
            }
        }
        if ($logo_url): ?>
            <img src="<?php echo esc_url($logo_url); ?>" alt="" class="w-14 h-14 rounded-lg object-cover">
        <?php else: ?>
            <div class="w-14 h-14 rounded-lg bg-[#F5F5F4] flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888]"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path></svg>
            </div>
        <?php endif; ?>

        <div class="flex-1 min-w-0">
            <h3 class="text-base font-semibold text-[#111827]"><?php echo esc_html(get_the_title($company_id)); ?></h3>
            <?php $org_nr = get_field('organisasjonsnummer', $company_id); ?>
            <?php if ($org_nr): ?>
                <p class="text-sm text-[#57534E]"><?php _e('Org.nr:', 'bimverdi'); ?> <?php echo esc_html($org_nr); ?></p>
            <?php endif; ?>
            <a href="<?php echo home_url('/min-side/foretak/'); ?>" class="inline-block mt-2 text-sm text-[#57534E] hover:text-[#111827]">
                <?php _e('Vis detaljer', 'bimverdi'); ?> →
            </a>
        </div>
    </div>
</div>
<?php else: ?>
<!-- No company connected -->
<div class="pt-8 border-t border-[#E7E5E4]">
    <h2 class="text-lg font-semibold text-[#111827] mb-4"><?php _e('Mitt foretak', 'bimverdi'); ?></h2>
    <p class="text-[#57534E] mb-4"><?php _e('Du er ikke koblet til et foretak ennå.', 'bimverdi'); ?></p>
    <?php bimverdi_button([
        'text'    => __('Registrer foretak', 'bimverdi'),
        'variant' => 'primary',
        'href'    => home_url('/min-side/registrer-foretak/'),
        'icon'    => 'plus',
    ]); ?>
</div>
<?php endif; ?>
