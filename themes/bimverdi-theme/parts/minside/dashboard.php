<?php
/**
 * Min Side - Dashboard Part
 *
 * Rich profile landing page with company info, tools, knowledge sources,
 * and events as distinct sections. Replaces the old action-card layout.
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
$is_hovedkontakt = $company_id ? bimverdi_is_hovedkontakt($user_id, $company_id) : false;
$is_active = $company_id ? bimverdi_is_company_active($company_id) : false;

// Company role labels
$user_role_label = '';
if ($company) {
    $user_role_label = $is_hovedkontakt ? 'hovedkontakt' : 'tilleggskontakt';
}

// --- Data queries (only when company exists) ---
$my_tools = [];
$my_tools_count = 0;
$my_kilder = [];
$my_kilder_count = 0;
$my_events = [];
$my_events_count = 0;

if ($company_id) {
    // Tools query
    $tools_query = get_posts([
        'post_type' => 'verktoy',
        'posts_per_page' => 3,
        'post_status' => ['publish', 'draft', 'pending'],
        'meta_query' => [['key' => 'eier_leverandor', 'value' => $company_id]],
    ]);
    $my_tools = $tools_query;
    // Get total count
    $tools_count_query = new WP_Query([
        'post_type' => 'verktoy',
        'posts_per_page' => 1,
        'post_status' => ['publish', 'draft', 'pending'],
        'fields' => 'ids',
        'meta_query' => [['key' => 'eier_leverandor', 'value' => $company_id]],
    ]);
    $my_tools_count = $tools_count_query->found_posts;

    // Kunnskapskilder query
    $kilde_query_args = [
        'post_type' => 'kunnskapskilde',
        'posts_per_page' => 3,
        'post_status' => ['publish', 'draft', 'pending'],
        'meta_query' => [
            'relation' => 'OR',
            ['key' => 'registrert_av', 'value' => $user_id],
            ['key' => 'tilknyttet_bedrift', 'value' => $company_id],
        ],
    ];
    $my_kilder = get_posts($kilde_query_args);
    if (empty($my_kilder)) {
        $my_kilder = get_posts([
            'post_type' => 'kunnskapskilde',
            'posts_per_page' => 3,
            'post_status' => ['publish', 'draft', 'pending'],
            'author' => $user_id,
        ]);
    }
    // Get total count
    $kilder_count_args = $kilde_query_args;
    $kilder_count_args['posts_per_page'] = 1;
    $kilder_count_args['fields'] = 'ids';
    $kilder_count_query = new WP_Query($kilder_count_args);
    $my_kilder_count = $kilder_count_query->found_posts;
    if ($my_kilder_count === 0) {
        $kilder_fallback_query = new WP_Query([
            'post_type' => 'kunnskapskilde',
            'posts_per_page' => 1,
            'post_status' => ['publish', 'draft', 'pending'],
            'fields' => 'ids',
            'author' => $user_id,
        ]);
        $my_kilder_count = $kilder_fallback_query->found_posts;
    }

    // Events via registrations
    $registrations = get_posts([
        'post_type'      => 'pamelding',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [
            'relation' => 'OR',
            ['key' => 'bruker', 'value' => $user_id],
            ['key' => 'pamelding_bruker', 'value' => $user_id],
        ],
    ]);
    $my_events_count = count($registrations);

    // Get event details for the first 3 registrations
    $event_ids = [];
    foreach ($registrations as $reg) {
        $event_id = get_field('arrangement', $reg->ID) ?: get_field('pamelding_arrangement', $reg->ID);
        if ($event_id) {
            $eid = is_object($event_id) ? $event_id->ID : $event_id;
            if (!in_array($eid, $event_ids)) {
                $event_ids[] = $eid;
            }
        }
        if (count($event_ids) >= 3) break;
    }
    if (!empty($event_ids)) {
        $my_events = get_posts([
            'post_type' => 'arrangement',
            'post__in' => $event_ids,
            'posts_per_page' => 3,
            'post_status' => 'publish',
            'orderby' => 'meta_value',
            'meta_key' => 'arrangement_dato',
            'order' => 'ASC',
        ]);
    }
}

// Company logo (medium size for header)
$logo_url = '';
if ($company_id) {
    $logo = get_field('logo', $company_id);
    if ($logo) {
        $logo_url = is_array($logo) ? ($logo['sizes']['medium'] ?? $logo['url'] ?? '') : (wp_get_attachment_image_url($logo, 'medium') ?: '');
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

<?php if ($company): ?>

    <div class="space-y-0">

        <!-- 1. Company Header -->
        <div class="pb-8 border-b border-[#E7E5E4]">
            <div class="flex items-start gap-4">
                <?php if ($logo_url): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="" class="w-20 h-20 rounded-lg object-cover flex-shrink-0">
                <?php else: ?>
                    <div class="w-20 h-20 rounded-lg bg-[#F5F5F4] flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888]"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path></svg>
                    </div>
                <?php endif; ?>

                <div class="flex-1 min-w-0">
                    <h2 class="text-xl font-bold text-[#111827] mb-1"><?php echo esc_html(get_the_title($company_id)); ?></h2>

                    <?php $org_nr = get_field('organisasjonsnummer', $company_id); ?>
                    <?php if ($org_nr): ?>
                        <p class="text-sm text-[#57534E] mb-2"><?php _e('Org.nr:', 'bimverdi'); ?> <?php echo esc_html($org_nr); ?></p>
                    <?php endif; ?>

                    <?php
                    $bransjer = get_the_terms($company_id, 'bransjekategori');
                    if ($bransjer && !is_wp_error($bransjer)):
                    ?>
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            <?php foreach ($bransjer as $bransje): ?>
                                <span class="inline-block text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-2 py-0.5 rounded">
                                    <?php echo esc_html($bransje->name); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    $kundetyper = get_the_terms($company_id, 'kundetype');
                    if ($kundetyper && !is_wp_error($kundetyper)):
                    ?>
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            <?php foreach ($kundetyper as $kundetype): ?>
                                <span class="inline-block text-xs font-medium bg-[#FFF8F5] text-[#C2613A] px-2 py-0.5 rounded">
                                    <?php echo esc_html($kundetype->name); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Status -->
                    <div class="flex items-center gap-2 mt-2">
                        <?php if ($is_active): ?>
                            <span class="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"></span>
                            <span class="text-xs text-[#57534E]"><?php _e('Aktiv deltaker', 'bimverdi'); ?></span>
                        <?php else: ?>
                            <span class="w-2 h-2 rounded-full bg-amber-500 flex-shrink-0"></span>
                            <span class="text-xs text-[#57534E]"><?php _e('Inaktiv deltaker', 'bimverdi'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Actions row -->
            <div class="flex items-center gap-4 mt-4">
                <a href="<?php echo esc_url(get_permalink($company_id)); ?>" class="text-sm text-[#57534E] hover:text-[#FF8B5E] transition-colors flex items-center gap-1">
                    <?php _e('Se offentlig profil', 'bimverdi'); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 7h10v10"/><path d="M7 17 17 7"/></svg>
                </a>
                <?php if ($is_hovedkontakt): ?>
                    <a href="<?php echo esc_url(bimverdi_minside_url('foretak/rediger')); ?>" class="text-sm text-[#57534E] hover:text-[#FF8B5E] transition-colors flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                        <?php _e('Rediger foretak', 'bimverdi'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Description (if exists) -->
        <?php $beskrivelse = get_field('beskrivelse', $company_id); ?>
        <?php if ($beskrivelse): ?>
        <div class="py-8 border-b border-[#E7E5E4]">
            <h3 class="text-lg font-semibold text-[#111827] mb-3"><?php _e('Om foretaket', 'bimverdi'); ?></h3>
            <div class="text-sm text-[#57534E] leading-relaxed prose prose-sm max-w-none">
                <?php echo wp_kses_post($beskrivelse); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 3. Mine verktøy -->
        <div class="py-8 border-b border-[#E7E5E4]">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-semibold text-[#111827]"><?php _e('Mine verktøy', 'bimverdi'); ?></h3>
                    <?php if ($my_tools_count > 0): ?>
                        <span class="inline-flex items-center justify-center text-xs font-medium bg-[#F5F5F4] text-[#57534E] rounded-full w-6 h-6"><?php echo esc_html($my_tools_count); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($my_tools_count > 0): ?>
                    <a href="<?php echo esc_url(bimverdi_minside_url('verktoy')); ?>" class="text-sm text-[#57534E] hover:text-[#FF8B5E] transition-colors">
                        <?php _e('Se alle', 'bimverdi'); ?> &rarr;
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($my_tools)): ?>
                <div class="divide-y divide-[#E7E5E4]">
                    <?php foreach ($my_tools as $tool):
                        $tool_logo = get_field('logo', $tool->ID);
                        $tool_logo_url = '';
                        if ($tool_logo) {
                            $tool_logo_url = is_array($tool_logo) ? ($tool_logo['sizes']['thumbnail'] ?? $tool_logo['url'] ?? '') : (wp_get_attachment_image_url($tool_logo, 'thumbnail') ?: '');
                        }
                        $tool_cats = get_the_terms($tool->ID, 'verktoykategori');
                        $tool_cat_name = ($tool_cats && !is_wp_error($tool_cats)) ? $tool_cats[0]->name : '';
                        $tool_status = $tool->post_status;
                    ?>
                        <div class="flex items-center gap-3 py-3">
                            <?php if ($tool_logo_url): ?>
                                <img src="<?php echo esc_url($tool_logo_url); ?>" alt="" class="w-8 h-8 rounded object-cover flex-shrink-0">
                            <?php else: ?>
                                <div class="w-8 h-8 rounded bg-[#F5F5F4] flex items-center justify-center flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#888888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                                </div>
                            <?php endif; ?>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-[#111827] truncate"><?php echo esc_html($tool->post_title); ?></p>
                                <?php if ($tool_cat_name): ?>
                                    <p class="text-xs text-[#78716C]"><?php echo esc_html($tool_cat_name); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if ($tool_status === 'draft'): ?>
                                <span class="text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded"><?php _e('Kladd', 'bimverdi'); ?></span>
                            <?php elseif ($tool_status === 'pending'): ?>
                                <span class="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded"><?php _e('Til godkjenning', 'bimverdi'); ?></span>
                            <?php elseif ($tool_status === 'publish'): ?>
                                <span class="text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded"><?php _e('Publisert', 'bimverdi'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-sm text-[#78716C] mb-3"><?php _e('Ingen verktøy registrert ennå.', 'bimverdi'); ?></p>
                <?php bimverdi_button([
                    'text'    => __('Registrer verktøy', 'bimverdi'),
                    'variant' => 'secondary',
                    'size'    => 'small',
                    'href'    => bimverdi_minside_url('registrer-verktoy'),
                    'icon'    => 'plus',
                ]); ?>
            <?php endif; ?>
        </div>

        <!-- 4. Mine kunnskapskilder -->
        <div class="py-8 border-b border-[#E7E5E4]">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-semibold text-[#111827]"><?php _e('Mine kunnskapskilder', 'bimverdi'); ?></h3>
                    <?php if ($my_kilder_count > 0): ?>
                        <span class="inline-flex items-center justify-center text-xs font-medium bg-[#F5F5F4] text-[#57534E] rounded-full w-6 h-6"><?php echo esc_html($my_kilder_count); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($my_kilder_count > 0): ?>
                    <a href="<?php echo esc_url(bimverdi_minside_url('kunnskapskilder')); ?>" class="text-sm text-[#57534E] hover:text-[#FF8B5E] transition-colors">
                        <?php _e('Se alle', 'bimverdi'); ?> &rarr;
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($my_kilder)): ?>
                <div class="divide-y divide-[#E7E5E4]">
                    <?php foreach ($my_kilder as $kilde):
                        $kilde_type = get_field('kildetype', $kilde->ID);
                        $kilde_status = $kilde->post_status;
                    ?>
                        <div class="flex items-center gap-3 py-3">
                            <div class="w-8 h-8 rounded bg-[#F0F4FF] flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#005898" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-[#111827] truncate"><?php echo esc_html($kilde->post_title); ?></p>
                                <?php if ($kilde_type): ?>
                                    <p class="text-xs text-[#78716C]"><?php echo esc_html(is_array($kilde_type) ? implode(', ', $kilde_type) : $kilde_type); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if ($kilde_status === 'draft'): ?>
                                <span class="text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded"><?php _e('Kladd', 'bimverdi'); ?></span>
                            <?php elseif ($kilde_status === 'pending'): ?>
                                <span class="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded"><?php _e('Til godkjenning', 'bimverdi'); ?></span>
                            <?php elseif ($kilde_status === 'publish'): ?>
                                <span class="text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded"><?php _e('Publisert', 'bimverdi'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-sm text-[#78716C] mb-3"><?php _e('Ingen kunnskapskilder registrert ennå.', 'bimverdi'); ?></p>
                <?php bimverdi_button([
                    'text'    => __('Registrer kunnskapskilde', 'bimverdi'),
                    'variant' => 'secondary',
                    'size'    => 'small',
                    'href'    => bimverdi_minside_url('kunnskapskilder/registrer'),
                    'icon'    => 'plus',
                ]); ?>
            <?php endif; ?>
        </div>

        <!-- 5. Mine arrangementer -->
        <div class="py-8 border-b border-[#E7E5E4]">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-semibold text-[#111827]"><?php _e('Mine arrangementer', 'bimverdi'); ?></h3>
                    <?php if ($my_events_count > 0): ?>
                        <span class="inline-flex items-center justify-center text-xs font-medium bg-[#F5F5F4] text-[#57534E] rounded-full w-6 h-6"><?php echo esc_html($my_events_count); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($my_events_count > 0): ?>
                    <a href="<?php echo esc_url(bimverdi_minside_url('arrangementer')); ?>" class="text-sm text-[#57534E] hover:text-[#FF8B5E] transition-colors">
                        <?php _e('Se alle', 'bimverdi'); ?> &rarr;
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($my_events)): ?>
                <div class="divide-y divide-[#E7E5E4]">
                    <?php foreach ($my_events as $event):
                        $event_dato = get_field('arrangement_dato', $event->ID);
                        $event_types = get_the_terms($event->ID, 'arrangementstype');
                        $event_type_name = ($event_types && !is_wp_error($event_types)) ? $event_types[0]->name : '';
                    ?>
                        <div class="flex items-center gap-3 py-3">
                            <div class="w-8 h-8 rounded bg-[#FFF8F5] flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#C2613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-[#111827] truncate"><?php echo esc_html($event->post_title); ?></p>
                                <p class="text-xs text-[#78716C]">
                                    <?php if ($event_dato): ?>
                                        <?php echo esc_html($event_dato); ?>
                                    <?php endif; ?>
                                    <?php if ($event_dato && $event_type_name): ?> · <?php endif; ?>
                                    <?php if ($event_type_name): ?>
                                        <?php echo esc_html($event_type_name); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-sm text-[#78716C] mb-3"><?php _e('Ingen påmeldinger ennå.', 'bimverdi'); ?></p>
                <?php bimverdi_button([
                    'text'    => __('Utforsk arrangementer', 'bimverdi'),
                    'variant' => 'secondary',
                    'size'    => 'small',
                    'href'    => home_url('/arrangementer/'),
                    'icon'    => 'calendar',
                ]); ?>
            <?php endif; ?>
        </div>

        <!-- 6. Kontaktinformasjon -->
        <?php
        $adresse = get_field('adresse', $company_id);
        $postnummer = get_field('postnummer', $company_id);
        $poststed = get_field('poststed', $company_id);
        $land = get_field('land', $company_id);
        $telefon = get_field('telefon', $company_id);
        $epost = get_field('epost', $company_id);
        $nettside = get_field('hjemmeside', $company_id);
        $has_contact = $adresse || $postnummer || $poststed || $telefon || $epost || $nettside;
        ?>

        <?php if ($has_contact): ?>
        <div class="pt-8">
            <h3 class="text-lg font-semibold text-[#111827] mb-4"><?php _e('Kontaktinformasjon', 'bimverdi'); ?></h3>
            <div class="divide-y divide-[#E7E5E4]">

                <?php if ($adresse || $postnummer || $poststed): ?>
                <div class="flex items-start gap-3 py-4 first:pt-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888] flex-shrink-0 mt-0.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <div>
                        <p class="text-xs font-medium text-[#57534E] uppercase tracking-wide mb-1"><?php _e('Adresse', 'bimverdi'); ?></p>
                        <?php if ($adresse): ?>
                            <p class="text-sm text-[#111827]"><?php echo esc_html($adresse); ?></p>
                        <?php endif; ?>
                        <?php if ($postnummer || $poststed): ?>
                            <p class="text-sm text-[#111827]"><?php echo esc_html(trim($postnummer . ' ' . $poststed)); ?></p>
                        <?php endif; ?>
                        <?php if ($land && $land !== 'Norge'): ?>
                            <p class="text-sm text-[#111827]"><?php echo esc_html($land); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($telefon): ?>
                <div class="flex items-start gap-3 py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888] flex-shrink-0 mt-0.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    <div>
                        <p class="text-xs font-medium text-[#57534E] uppercase tracking-wide mb-1"><?php _e('Telefon', 'bimverdi'); ?></p>
                        <p class="text-sm text-[#111827]"><?php echo esc_html($telefon); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($epost): ?>
                <div class="flex items-start gap-3 py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888] flex-shrink-0 mt-0.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    <div>
                        <p class="text-xs font-medium text-[#57534E] uppercase tracking-wide mb-1"><?php _e('E-post', 'bimverdi'); ?></p>
                        <p class="text-sm text-[#111827]"><?php echo esc_html($epost); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($nettside): ?>
                <div class="flex items-start gap-3 py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888] flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                    <div>
                        <p class="text-xs font-medium text-[#57534E] uppercase tracking-wide mb-1"><?php _e('Nettside', 'bimverdi'); ?></p>
                        <a href="<?php echo esc_url($nettside); ?>" target="_blank" rel="noopener" class="text-sm text-[#111827] hover:text-[#FF8B5E] transition-colors">
                            <?php echo esc_html($nettside); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php endif; ?>

    </div>

<?php else: ?>

    <!-- No company connected -->
    <div class="py-8">
        <div class="text-center max-w-md mx-auto">
            <div class="w-16 h-16 rounded-full bg-[#F5F5F4] flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#888888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-[#111827] mb-2"><?php _e('Velkommen til Min Side', 'bimverdi'); ?></h3>
            <p class="text-sm text-[#57534E] mb-6"><?php _e('Du er ikke koblet til et foretak ennå. Registrer foretaket ditt for å få tilgang til alle funksjoner.', 'bimverdi'); ?></p>
            <?php bimverdi_button([
                'text'    => __('Registrer foretak', 'bimverdi'),
                'variant' => 'primary',
                'href'    => home_url('/min-side/registrer-foretak/'),
                'icon'    => 'plus',
            ]); ?>
        </div>
    </div>

<?php endif; ?>

<?php get_template_part('parts/components/account-layout-end'); ?>
