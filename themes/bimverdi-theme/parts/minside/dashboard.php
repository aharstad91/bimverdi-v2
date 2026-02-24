<?php
/**
 * Min Side - Dashboard Part
 *
 * Action-first layout: quick actions up top, stats + company as secondary info.
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

// Get events the user is registered for
$my_events_count = 0;
$registrations = get_posts([
    'post_type'      => 'pamelding',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'fields'         => 'ids',
    'meta_query'     => [
        'relation' => 'OR',
        ['key' => 'bruker', 'value' => $user_id],
        ['key' => 'pamelding_bruker', 'value' => $user_id],
    ],
]);
$my_events_count = count($registrations);

// Company logo
$logo_url = '';
if ($company_id) {
    $logo = get_field('logo', $company_id);
    if ($logo) {
        if (is_array($logo)) {
            $logo_url = $logo['sizes']['thumbnail'] ?? $logo['url'] ?? '';
        } else {
            $logo_url = wp_get_attachment_image_url($logo, 'thumbnail') ?: '';
        }
    }
}

// Company role
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

<!-- Account Layout with Sidenav -->
<?php get_template_part('parts/components/account-layout', null, [
    'title' => sprintf(__('Hei, %s', 'bimverdi'), $current_user->first_name ?: $current_user->display_name),
    'description' => $company ? esc_html(get_the_title($company_id)) . ($user_role_label ? ' · ' . esc_html($user_role_label) : '') : __('Velkommen til Min Side', 'bimverdi'),
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

<!-- Quick Actions — 2x2 grid -->
<div class="grid grid-cols-2 gap-3 mb-10">

    <a href="<?php echo esc_url(home_url('/min-side/registrer-verktoy/')); ?>" class="group flex items-center gap-4 p-5 rounded-xl border border-[#E7E5E4] hover:border-[#FF8B5E] hover:shadow-sm transition-all">
        <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center flex-shrink-0 group-hover:bg-orange-100 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#EA580C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        </div>
        <div>
            <span class="text-sm font-semibold text-[#111827] group-hover:text-[#EA580C] transition-colors"><?php _e('Registrer verktøy', 'bimverdi'); ?></span>
            <p class="text-xs text-[#78716C] mt-0.5"><?php _e('Legg til nytt BIM-verktøy', 'bimverdi'); ?></p>
        </div>
    </a>

    <a href="<?php echo esc_url(home_url('/min-side/kunnskapskilder/registrer/')); ?>" class="group flex items-center gap-4 p-5 rounded-xl border border-[#E7E5E4] hover:border-[#005898] hover:shadow-sm transition-all">
        <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-100 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#005898" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        </div>
        <div>
            <span class="text-sm font-semibold text-[#111827] group-hover:text-[#005898] transition-colors"><?php _e('Registrer kunnskapskilde', 'bimverdi'); ?></span>
            <p class="text-xs text-[#78716C] mt-0.5"><?php _e('Del en veiledning eller standard', 'bimverdi'); ?></p>
        </div>
    </a>

    <a href="<?php echo esc_url(home_url('/min-side/foretak/')); ?>" class="group flex items-center gap-4 p-5 rounded-xl border border-[#E7E5E4] hover:border-[#57534E] hover:shadow-sm transition-all">
        <div class="w-10 h-10 rounded-lg bg-stone-100 flex items-center justify-center flex-shrink-0 group-hover:bg-stone-200 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#57534E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/></svg>
        </div>
        <div>
            <span class="text-sm font-semibold text-[#111827]"><?php _e('Mitt foretak', 'bimverdi'); ?></span>
            <p class="text-xs text-[#78716C] mt-0.5"><?php _e('Foretaksprofil og kolleger', 'bimverdi'); ?></p>
        </div>
    </a>

    <a href="<?php echo esc_url(home_url('/min-side/profil/')); ?>" class="group flex items-center gap-4 p-5 rounded-xl border border-[#E7E5E4] hover:border-[#57534E] hover:shadow-sm transition-all">
        <div class="w-10 h-10 rounded-lg bg-stone-100 flex items-center justify-center flex-shrink-0 group-hover:bg-stone-200 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#57534E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
            <span class="text-sm font-semibold text-[#111827]"><?php _e('Min profil', 'bimverdi'); ?></span>
            <p class="text-xs text-[#78716C] mt-0.5"><?php _e('Rediger personlig informasjon', 'bimverdi'); ?></p>
        </div>
    </a>

</div>

<!-- Stats + Company — compact secondary row -->
<div class="flex items-center gap-6 text-sm text-[#57534E] pt-6 border-t border-[#E7E5E4]">

    <!-- Stats inline -->
    <a href="<?php echo esc_url(home_url('/min-side/verktoy/')); ?>" class="flex items-center gap-2 hover:text-[#111827] transition-colors">
        <span class="font-semibold text-[#111827] text-base"><?php echo esc_html($my_tools_count); ?></span>
        <?php _e('verktøy', 'bimverdi'); ?>
    </a>

    <span class="text-[#D6D3D1]">&middot;</span>

    <a href="<?php echo esc_url(home_url('/min-side/kunnskapskilder/')); ?>" class="flex items-center gap-2 hover:text-[#111827] transition-colors">
        <span class="font-semibold text-[#111827] text-base"><?php echo esc_html($my_kunnskapskilder_count); ?></span>
        <?php _e('kunnskapskilder', 'bimverdi'); ?>
    </a>

    <span class="text-[#D6D3D1]">&middot;</span>

    <a href="<?php echo esc_url(home_url('/min-side/arrangementer/')); ?>" class="flex items-center gap-2 hover:text-[#111827] transition-colors">
        <span class="font-semibold text-[#111827] text-base"><?php echo esc_html($my_events_count); ?></span>
        <?php _e('arrangementer', 'bimverdi'); ?>
    </a>

    <?php if ($company): ?>
    <span class="text-[#D6D3D1]">&middot;</span>

    <a href="<?php echo esc_url(home_url('/min-side/foretak/')); ?>" class="flex items-center gap-2 hover:text-[#111827] transition-colors">
        <?php if ($logo_url): ?>
            <img src="<?php echo esc_url($logo_url); ?>" alt="" class="w-5 h-5 rounded object-cover">
        <?php endif; ?>
        <?php echo esc_html(get_the_title($company_id)); ?>
    </a>
    <?php endif; ?>

</div>

<?php if (!$company): ?>
<!-- No company connected -->
<div class="mt-8 pt-6 border-t border-[#E7E5E4]">
    <p class="text-sm text-[#57534E] mb-4"><?php _e('Du er ikke koblet til et foretak ennå.', 'bimverdi'); ?></p>
    <?php bimverdi_button([
        'text'    => __('Registrer foretak', 'bimverdi'),
        'variant' => 'primary',
        'href'    => home_url('/min-side/registrer-foretak/'),
        'icon'    => 'plus',
    ]); ?>
</div>
<?php endif; ?>

<?php get_template_part('parts/components/account-layout-end'); ?>
