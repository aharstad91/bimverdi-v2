<?php
/**
 * Single Arrangement Template
 *
 * Displays detailed information about a single event
 * Design follows ui-contract.md - Variant B (dividers/whitespace)
 *
 * @package BimVerdi_Theme
 */

get_header();

if (have_posts()) : while (have_posts()) : the_post();

$arrangement_id = get_the_ID();

// Get ACF fields - Basic info
$arrangement_status_toggle = get_field('arrangement_status_toggle') ?: 'kommende';
$arrangement_type = get_field('arrangement_type') ?: 'digitalt';
$dato = get_field('arrangement_dato');
$slutt_dato = get_field('slutt_dato');
$tid_start = get_field('tidspunkt_start');
$tid_slutt = get_field('tidspunkt_slutt');
$pameldingsfrist = get_field('pameldingsfrist');
$status = get_field('arrangement_status') ?: 'planlagt';

// Location fields
$sted_adresse = get_field('sted_adresse');
$sted_by = get_field('sted_by');
$online_lenke = get_field('online_lenke');

// Registration
$pamelding_url = get_field('pamelding_url');
$maks_deltakere = get_field('maks_deltakere');

// Metadata
$formal_tema = get_field('formal_tema');
$malsetting = get_field('malsetting');
$passer_for = get_field('passer_for');
$arrangor = get_field('arrangor');
$adgang = get_field('adgang');
$prosjektleder_id = get_field('prosjektleder');

// Post-event resources
$opptak_url = get_field('opptak_url');
$dokumentasjon_url = get_field('dokumentasjon_url');

// Get featured image
$featured_image = get_the_post_thumbnail_url($arrangement_id, 'large');

// Get taxonomies
$temagrupper = wp_get_post_terms($arrangement_id, 'temagruppe', array('fields' => 'all'));
$arrangementstyper = wp_get_post_terms($arrangement_id, 'arrangementstype', array('fields' => 'all'));

// Check if event is past
$is_past = ($arrangement_status_toggle === 'tidligere');

// Check registration deadline
$frist_passert = false;
if ($pameldingsfrist) {
    $frist_passert = strtotime($pameldingsfrist) < time();
} elseif ($dato) {
    $frist_passert = strtotime($dato) < strtotime('today');
}

// Helper functions
function bimverdi_arr_get_type_icon($type) {
    $icons = array(
        'fysisk' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
        'digitalt' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5"/><rect x="2" y="6" width="14" height="12" rx="2"/></svg>',
        'hybrid' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>',
    );
    return $icons[$type] ?? $icons['digitalt'];
}

function bimverdi_arr_get_type_label($type) {
    $labels = array(
        'fysisk' => 'Fysisk arrangement',
        'digitalt' => 'Digitalt arrangement',
        'hybrid' => 'Hybrid (fysisk + digitalt)',
    );
    return $labels[$type] ?? 'Digitalt';
}

function bimverdi_arr_get_adgang_label($adgang) {
    $labels = array(
        'alle' => 'Åpent for alle',
        'registrerte' => 'Registrerte brukere',
        'deltakere' => 'Deltakere i BIM Verdi',
        'medlemmer' => 'Deltakere i BIM Verdi', // Legacy
    );
    return $labels[$adgang] ?? '';
}

// Format date display
$date_display = date_i18n('j. F Y', strtotime($dato));
if ($slutt_dato && $slutt_dato !== $dato) {
    $date_display = date_i18n('j.', strtotime($dato)) . ' – ' . date_i18n('j. F Y', strtotime($slutt_dato));
}

// Format time display
$time_display = $tid_start;
if ($tid_slutt) {
    $time_display .= ' – ' . $tid_slutt;
}

// Generate Google Maps embed URL
$maps_embed_url = '';
if ($sted_adresse && ($arrangement_type === 'fysisk' || $arrangement_type === 'hybrid')) {
    $maps_embed_url = 'https://www.google.com/maps/embed/v1/place?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&q=' . urlencode($sted_adresse . ', ' . $sted_by);
}
?>

<div class="min-h-screen bg-[#FAFAF8]">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <!-- Breadcrumb -->
        <nav class="mb-8">
            <ol class="flex items-center gap-2 text-sm text-[#5A5A5A]">
                <li><a href="<?php echo home_url(); ?>" class="hover:text-[#1A1A1A]">Hjem</a></li>
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </li>
                <li><a href="<?php echo home_url('/arrangement/'); ?>" class="hover:text-[#1A1A1A]">Arrangementer</a></li>
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </li>
                <li class="text-[#1A1A1A] font-medium truncate max-w-xs"><?php the_title(); ?></li>
            </ol>
        </nav>

        <!-- Status Banner -->
        <?php if ($status === 'avlyst'): ?>
        <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#772015" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <span class="font-semibold text-[#772015]">Dette arrangementet er avlyst</span>
        </div>
        <?php elseif ($is_past): ?>
        <div class="mb-6 px-4 py-3 bg-[#F2F0EB] border border-[#D6D1C6] rounded-lg flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5A5A5A" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
            <span class="font-semibold text-[#5A5A5A]">Dette arrangementet er gjennomført</span>
        </div>
        <?php endif; ?>

        <!-- Hero Section -->
        <?php if ($featured_image): ?>
        <div class="relative mb-8 rounded-xl overflow-hidden h-64 md:h-80">
            <img src="<?php echo esc_url($featured_image); ?>" alt="" class="w-full h-full object-cover">

            <!-- Date badge -->
            <div class="absolute top-4 left-4 bg-white rounded-lg px-4 py-3 text-center">
                <div class="text-2xl font-bold text-[#FF8B5E] leading-none"><?php echo date('d', strtotime($dato)); ?></div>
                <div class="text-xs uppercase text-[#5A5A5A]"><?php echo date_i18n('M', strtotime($dato)); ?></div>
            </div>

            <!-- Status badge -->
            <?php if ($status === 'avlyst'): ?>
            <div class="absolute top-4 right-4">
                <span class="inline-flex items-center text-xs font-semibold text-white bg-[#772015] px-3 py-1 rounded">Avlyst</span>
            </div>
            <?php elseif ($is_past): ?>
            <div class="absolute top-4 right-4">
                <span class="inline-flex items-center text-xs font-semibold text-white bg-[#5A5A5A] px-3 py-1 rounded">Avholdt</span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="grid md:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="md:col-span-2 space-y-8">

                <!-- Title and Badges -->
                <header>
                    <!-- Temagruppe badges -->
                    <?php if (!empty($temagrupper)): ?>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php foreach ($temagrupper as $gruppe): ?>
                            <a href="<?php echo get_term_link($gruppe); ?>" class="inline-flex items-center text-xs font-medium text-[#FF8B5E] bg-[#FFF5F0] border border-[#FFBFA8] px-2.5 py-1 rounded hover:bg-[#FFECE4] transition-colors">
                                <?php echo esc_html($gruppe->name); ?>
                            </a>
                        <?php endforeach; ?>
                        <?php foreach ($arrangementstyper as $type): ?>
                            <span class="inline-flex items-center text-xs font-medium text-[#5A5A5A] bg-[#F2F0EB] px-2.5 py-1 rounded">
                                <?php echo esc_html($type->name); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <h1 class="text-3xl md:text-4xl font-bold text-[#1A1A1A] mb-4"><?php the_title(); ?><?php echo bimverdi_admin_id_badge(); ?></h1>

                    <!-- Quick info row -->
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-[#5A5A5A]">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <span><?php echo esc_html($date_display); ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span><?php echo esc_html($time_display); ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php echo bimverdi_arr_get_type_icon($arrangement_type); ?>
                            <span><?php echo bimverdi_arr_get_type_label($arrangement_type); ?></span>
                        </div>
                    </div>
                </header>

                <!-- Divider -->
                <div class="border-t border-[#D6D1C6]"></div>

                <!-- Main Content (Gutenberg) -->
                <section>
                    <div class="prose prose-lg max-w-none text-[#1A1A1A]">
                        <?php the_content(); ?>
                    </div>
                </section>

                <!-- Location Section (Fysisk/Hybrid) -->
                <?php if (($arrangement_type === 'fysisk' || $arrangement_type === 'hybrid') && $sted_adresse): ?>
                <section>
                    <div class="border-t border-[#D6D1C6] pt-8">
                        <h2 class="text-xl font-bold text-[#1A1A1A] mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            Sted
                        </h2>

                        <div class="space-y-4">
                            <div>
                                <p class="font-semibold text-[#1A1A1A]"><?php echo esc_html($sted_adresse); ?></p>
                                <?php if ($sted_by): ?>
                                <p class="text-[#5A5A5A]"><?php echo esc_html($sted_by); ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- Google Maps Embed -->
                            <div class="rounded-lg overflow-hidden border border-[#D6D1C6]">
                                <iframe
                                    src="https://maps.google.com/maps?q=<?php echo urlencode($sted_adresse . ($sted_by ? ', ' . $sted_by : '')); ?>&output=embed"
                                    width="100%"
                                    height="300"
                                    style="border:0;"
                                    allowfullscreen=""
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade">
                                </iframe>
                            </div>

                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($sted_adresse . ($sted_by ? ', ' . $sted_by : '')); ?>" target="_blank" class="inline-flex items-center gap-2 text-sm font-medium text-[#FF8B5E] hover:opacity-70 transition-opacity">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                Åpne i Google Maps
                            </a>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Digital Meeting Link (Digitalt/Hybrid) -->
                <?php if (($arrangement_type === 'digitalt' || $arrangement_type === 'hybrid') && $online_lenke && !$is_past): ?>
                <section>
                    <div class="border-t border-[#D6D1C6] pt-8">
                        <h2 class="text-xl font-bold text-[#1A1A1A] mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5"/><rect x="2" y="6" width="14" height="12" rx="2"/></svg>
                            Digital deltakelse
                        </h2>

                        <?php bimverdi_button([
                            'text'    => 'Åpne møtelenke',
                            'variant' => 'primary',
                            'href'    => $online_lenke,
                            'target'  => '_blank',
                        ]); ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Post-event Resources (Opptak/Dokumentasjon) -->
                <?php if ($is_past && ($opptak_url || $dokumentasjon_url)): ?>
                <section>
                    <div class="border-t border-[#D6D1C6] pt-8">
                        <h2 class="text-xl font-bold text-[#1A1A1A] mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Ressurser
                        </h2>

                        <div class="flex flex-wrap gap-4">
                            <?php if ($opptak_url): ?>
                                <?php bimverdi_button([
                                    'text'    => 'Se opptak',
                                    'variant' => 'primary',
                                    'href'    => $opptak_url,
                                    'target'  => '_blank',
                                ]); ?>
                            <?php endif; ?>

                            <?php if ($dokumentasjon_url): ?>
                                <?php bimverdi_button([
                                    'text'    => 'Last ned dokumentasjon',
                                    'variant' => 'secondary',
                                    'icon'    => 'download',
                                    'href'    => $dokumentasjon_url,
                                    'target'  => '_blank',
                                ]); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

            </div>

            <!-- Sidebar -->
            <div class="space-y-6">

                <!-- Registration Card -->
                <?php if (!$is_past && $status !== 'avlyst'): ?>
                <?php
                    // Registration state
                    $user_id = get_current_user_id();
                    $existing_registration = $user_id ? (function_exists('bimverdi_get_user_registration') ? bimverdi_get_user_registration($user_id, $arrangement_id) : false) : false;
                    $registration_count = function_exists('bimverdi_get_registration_count') ? bimverdi_get_registration_count($arrangement_id) : 0;
                    $is_full = $maks_deltakere && $registration_count >= intval($maks_deltakere);

                    // Access check for soft messaging
                    $access_check = ['allowed' => true, 'message' => ''];
                    if ($user_id && function_exists('bimverdi_check_event_access')) {
                        $access_check = bimverdi_check_event_access($user_id, $adgang ?: 'alle');
                    }
                ?>
                <div class="bg-[#F2F0EB] rounded-xl p-6" id="bv-registration-card">
                    <h3 class="font-bold text-[#1A1A1A] mb-4">Påmelding</h3>

                    <?php if ($frist_passert): ?>
                        <p class="text-[#5A5A5A] text-sm mb-4">Påmeldingsfristen har gått ut.</p>

                    <?php elseif ($existing_registration): ?>
                        <?php $reg_status = get_field('pamelding_status', $existing_registration); ?>
                        <div class="flex items-center gap-2 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4a7c29" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            <span class="text-sm font-medium text-[#4a7c29]">
                                <?php echo $reg_status === 'venteliste' ? 'Du står på venteliste' : 'Du er påmeldt'; ?>
                            </span>
                        </div>
                        <?php
                        $can_cancel = !function_exists('bimverdi_can_cancel_registration') || bimverdi_can_cancel_registration($arrangement_id);
                        if ($can_cancel): ?>
                        <button type="button"
                                class="bv-btn bv-btn--secondary bv-btn--small w-full"
                                id="bv-unregister-btn"
                                data-arrangement-id="<?php echo $arrangement_id; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            Meld deg av
                        </button>
                        <?php else: ?>
                        <p class="text-xs text-[#5A5A5A]">Avmeldingsfristen har passert.</p>
                        <?php endif; ?>

                    <?php elseif ($pamelding_url): ?>
                        <?php bimverdi_button([
                            'text'       => 'Meld deg på',
                            'variant'    => 'primary',
                            'icon'       => 'external-link',
                            'href'       => $pamelding_url,
                            'target'     => '_blank',
                            'full_width' => true,
                        ]); ?>
                        <p class="text-xs text-[#5A5A5A] mt-2">Åpner ekstern påmelding</p>

                    <?php elseif (!$user_id): ?>
                        <p class="text-[#5A5A5A] text-sm mb-3">Logg inn for å melde deg på.</p>
                        <?php bimverdi_button([
                            'text'       => 'Logg inn',
                            'variant'    => 'primary',
                            'icon'       => 'log-in',
                            'href'       => wp_login_url(get_permalink()),
                            'full_width' => true,
                        ]); ?>

                    <?php elseif (!$access_check['allowed']): ?>
                        <p class="text-[#5A5A5A] text-sm mb-3"><?php echo esc_html($access_check['message']); ?></p>

                    <?php else: ?>
                        <button type="button"
                                class="bv-btn bv-btn--primary w-full"
                                id="bv-register-btn"
                                data-arrangement-id="<?php echo $arrangement_id; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                            <?php echo $is_full ? 'Sett meg på venteliste' : 'Meld deg på'; ?>
                        </button>
                    <?php endif; ?>

                    <?php // Capacity info ?>
                    <?php if ($maks_deltakere && !$frist_passert): ?>
                    <p class="text-xs text-[#5A5A5A] mt-3">
                        <?php echo $registration_count; ?>/<?php echo intval($maks_deltakere); ?> plasser fylt
                        <?php if ($is_full): ?><span class="text-[#B8860B] font-medium"> – fullt, venteliste</span><?php endif; ?>
                    </p>
                    <?php endif; ?>

                    <?php if ($pameldingsfrist && !$frist_passert): ?>
                    <p class="text-xs text-[#5A5A5A] mt-2 flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Frist: <?php echo date_i18n('j. F Y H:i', strtotime($pameldingsfrist)); ?>
                    </p>
                    <?php endif; ?>

                    <div id="bv-reg-message" class="hidden mt-3 text-sm p-3 rounded-lg"></div>
                </div>
                <?php endif; ?>

                <!-- Details Section -->
                <div class="space-y-4">
                    <h3 class="font-bold text-[#1A1A1A]">Detaljer</h3>

                    <div class="space-y-3 text-sm">
                        <!-- Dato -->
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#5A5A5A" stroke-width="2" class="flex-shrink-0 mt-0.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <div>
                                <div class="text-[#5A5A5A]">Dato</div>
                                <div class="text-[#1A1A1A] font-medium"><?php echo esc_html($date_display); ?></div>
                            </div>
                        </div>

                        <!-- Tid -->
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#5A5A5A" stroke-width="2" class="flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <div>
                                <div class="text-[#5A5A5A]">Tidspunkt</div>
                                <div class="text-[#1A1A1A] font-medium"><?php echo esc_html($time_display); ?></div>
                            </div>
                        </div>

                        <!-- Type -->
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-0.5 text-[#5A5A5A]"><?php echo bimverdi_arr_get_type_icon($arrangement_type); ?></div>
                            <div>
                                <div class="text-[#5A5A5A]">Format</div>
                                <div class="text-[#1A1A1A] font-medium"><?php echo bimverdi_arr_get_type_label($arrangement_type); ?></div>
                            </div>
                        </div>

                        <!-- Adgang -->
                        <?php if ($adgang): ?>
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#5A5A5A" stroke-width="2" class="flex-shrink-0 mt-0.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <div>
                                <div class="text-[#5A5A5A]">Adgang</div>
                                <div class="text-[#1A1A1A] font-medium"><?php echo bimverdi_arr_get_adgang_label($adgang); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Arrangør -->
                        <?php if ($arrangor): ?>
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#5A5A5A" stroke-width="2" class="flex-shrink-0 mt-0.5"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/><path d="M9 9v.01"/><path d="M9 12v.01"/><path d="M9 15v.01"/><path d="M9 18v.01"/></svg>
                            <div>
                                <div class="text-[#5A5A5A]">Arrangør</div>
                                <div class="text-[#1A1A1A] font-medium"><?php echo esc_html($arrangor); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Målgrupper -->
                        <?php
                        $malgrupper_display = '';
                        if ($passer_for) {
                            if (is_array($passer_for)) {
                                $malgrupper_display = implode(', ', $passer_for);
                            } else {
                                $malgrupper_display = $passer_for;
                            }
                        }
                        ?>
                        <?php if ($malgrupper_display): ?>
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#5A5A5A" stroke-width="2" class="flex-shrink-0 mt-0.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                            <div>
                                <div class="text-[#5A5A5A]">Målgrupper</div>
                                <div class="text-[#1A1A1A] font-medium"><?php echo esc_html($malgrupper_display); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-[#D6D1C6]"></div>

                <!-- Formål og Målsetting -->
                <?php if ($formal_tema || $malsetting): ?>
                <div class="space-y-4">
                    <?php if ($formal_tema): ?>
                    <div>
                        <h4 class="font-semibold text-[#1A1A1A] mb-2">Formål</h4>
                        <p class="text-sm text-[#5A5A5A]"><?php echo nl2br(esc_html($formal_tema)); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($malsetting): ?>
                    <div>
                        <h4 class="font-semibold text-[#1A1A1A] mb-2">Målsetting</h4>
                        <p class="text-sm text-[#5A5A5A]"><?php echo nl2br(esc_html($malsetting)); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Divider -->
                <div class="border-t border-[#D6D1C6]"></div>
                <?php endif; ?>

                <!-- Share -->
                <div>
                    <h4 class="font-semibold text-[#1A1A1A] mb-3">Del arrangement</h4>
                    <div class="flex gap-2">
                        <?php bimverdi_button([
                            'text'    => 'Kopier lenke',
                            'variant' => 'secondary',
                            'size'    => 'small',
                            'icon'    => 'link',
                            'onclick' => "navigator.clipboard.writeText(window.location.href); alert('Lenke kopiert!');",
                        ]); ?>
                        <?php bimverdi_button([
                            'text'    => 'E-post',
                            'variant' => 'secondary',
                            'size'    => 'small',
                            'icon'    => 'mail',
                            'href'    => 'mailto:?subject=' . rawurlencode(get_the_title()) . '&body=' . rawurlencode(get_permalink()),
                        ]); ?>
                    </div>
                </div>

            </div>
        </div>

        <?php
        // Related Content Section - based on temagruppe
        if (!empty($temagrupper)):
            $temagruppe_ids = wp_list_pluck($temagrupper, 'term_id');
            $temagruppe_names = wp_list_pluck($temagrupper, 'name');

            // Query related content
            $related_args = array(
                'posts_per_page' => 6,
                'post__not_in'   => array($arrangement_id),
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'temagruppe',
                        'field'    => 'term_id',
                        'terms'    => $temagruppe_ids,
                    ),
                ),
            );

            // Get related tools
            $related_tools = new WP_Query(array_merge($related_args, array('post_type' => 'verktoy')));

            // Get related knowledge sources
            $related_kilder = new WP_Query(array_merge($related_args, array('post_type' => 'kunnskapskilde')));

            // Get related articles
            $related_artikler = new WP_Query(array_merge($related_args, array('post_type' => 'artikkel')));

            // Get related companies (deltakere)
            $related_foretak = new WP_Query(array_merge($related_args, array('post_type' => 'foretak')));

            // Check if we have any related content
            $has_related = $related_tools->have_posts() || $related_kilder->have_posts() || $related_artikler->have_posts() || $related_foretak->have_posts();

            if ($has_related):
        ?>
        <!-- Related Content Section -->
        <div class="border-t border-[#D6D1C6] mt-12 pt-12">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-[#1A1A1A] mb-2">Relatert innhold</h2>
                <p class="text-[#5A5A5A]">
                    Innhold tagget med
                    <?php foreach ($temagrupper as $i => $gruppe): ?>
                        <a href="<?php echo get_term_link($gruppe); ?>" class="text-[#FF8B5E] hover:underline"><?php echo esc_html($gruppe->name); ?></a><?php echo ($i < count($temagrupper) - 1) ? ', ' : ''; ?>
                    <?php endforeach; ?>
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">

                <?php if ($related_tools->have_posts()): ?>
                <!-- Related Tools -->
                <div>
                    <h3 class="text-sm font-bold text-[#5A5A5A] uppercase tracking-wider mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        Verktøy
                    </h3>
                    <ul class="space-y-2">
                        <?php while ($related_tools->have_posts()): $related_tools->the_post(); ?>
                        <li>
                            <a href="<?php the_permalink(); ?>" class="text-sm text-[#1A1A1A] hover:text-[#FF8B5E] transition-colors block truncate">
                                <?php the_title(); ?>
                            </a>
                        </li>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </ul>
                    <a href="<?php echo home_url('/verktoy/'); ?>" class="inline-flex items-center gap-1 text-xs text-[#FF8B5E] mt-3 hover:underline">
                        Se alle verktøy
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </div>
                <?php endif; ?>

                <?php if ($related_kilder->have_posts()): ?>
                <!-- Related Knowledge Sources -->
                <div>
                    <h3 class="text-sm font-bold text-[#5A5A5A] uppercase tracking-wider mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                        Kunnskapskilder
                    </h3>
                    <ul class="space-y-2">
                        <?php while ($related_kilder->have_posts()): $related_kilder->the_post(); ?>
                        <li>
                            <a href="<?php the_permalink(); ?>" class="text-sm text-[#1A1A1A] hover:text-[#FF8B5E] transition-colors block truncate">
                                <?php the_title(); ?>
                            </a>
                        </li>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </ul>
                    <a href="<?php echo home_url('/kunnskapskilder/'); ?>" class="inline-flex items-center gap-1 text-xs text-[#FF8B5E] mt-3 hover:underline">
                        Se alle kilder
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </div>
                <?php endif; ?>

                <?php if ($related_artikler->have_posts()): ?>
                <!-- Related Articles -->
                <div>
                    <h3 class="text-sm font-bold text-[#5A5A5A] uppercase tracking-wider mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        Artikler
                    </h3>
                    <ul class="space-y-2">
                        <?php while ($related_artikler->have_posts()): $related_artikler->the_post(); ?>
                        <li>
                            <a href="<?php the_permalink(); ?>" class="text-sm text-[#1A1A1A] hover:text-[#FF8B5E] transition-colors block truncate">
                                <?php the_title(); ?>
                            </a>
                        </li>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </ul>
                    <a href="<?php echo home_url('/artikler/'); ?>" class="inline-flex items-center gap-1 text-xs text-[#FF8B5E] mt-3 hover:underline">
                        Se alle artikler
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </div>
                <?php endif; ?>

                <?php if ($related_foretak->have_posts()): ?>
                <!-- Related Companies -->
                <div>
                    <h3 class="text-sm font-bold text-[#5A5A5A] uppercase tracking-wider mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>
                        Deltakere
                    </h3>
                    <ul class="space-y-2">
                        <?php while ($related_foretak->have_posts()): $related_foretak->the_post(); ?>
                        <li>
                            <a href="<?php the_permalink(); ?>" class="text-sm text-[#1A1A1A] hover:text-[#FF8B5E] transition-colors block truncate">
                                <?php the_title(); ?>
                            </a>
                        </li>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </ul>
                    <a href="<?php echo home_url('/deltakere/'); ?>" class="inline-flex items-center gap-1 text-xs text-[#FF8B5E] mt-3 hover:underline">
                        Se alle deltakere
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php
            endif; // has_related
        endif; // !empty($temagrupper)
        ?>

    </div>
</div>

<?php endwhile; endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var regConfig = typeof bimverdiEventReg !== 'undefined' ? bimverdiEventReg : null;
    if (!regConfig) return;

    var msgEl = document.getElementById('bv-reg-message');
    var registerBtn = document.getElementById('bv-register-btn');
    var unregisterBtn = document.getElementById('bv-unregister-btn');

    function showMessage(text, isError) {
        if (!msgEl) return;
        msgEl.textContent = text;
        msgEl.className = 'mt-3 text-sm p-3 rounded-lg ' +
            (isError ? 'bg-red-50 text-[#772015]' : 'bg-green-50 text-[#4a7c29]');
    }

    function setLoading(btn, loading) {
        if (!btn) return;
        btn.disabled = loading;
        btn.style.opacity = loading ? '0.6' : '1';
    }

    if (registerBtn) {
        registerBtn.addEventListener('click', function() {
            var btn = this;
            var arrangementId = btn.getAttribute('data-arrangement-id');
            setLoading(btn, true);

            var formData = new FormData();
            formData.append('action', 'bimverdi_register_event');
            formData.append('arrangement_id', arrangementId);
            formData.append('nonce', regConfig.nonce);

            fetch(regConfig.ajaxUrl, { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        showMessage(res.data.message, false);
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        showMessage(res.data.message || 'Noe gikk galt.', true);
                        setLoading(btn, false);
                    }
                })
                .catch(function() {
                    showMessage('Noe gikk galt. Prøv igjen.', true);
                    setLoading(btn, false);
                });
        });
    }

    if (unregisterBtn) {
        unregisterBtn.addEventListener('click', function() {
            if (!confirm('Er du sikker på at du vil melde deg av?')) return;
            var btn = this;
            var arrangementId = btn.getAttribute('data-arrangement-id');
            setLoading(btn, true);

            var formData = new FormData();
            formData.append('action', 'bimverdi_unregister_event');
            formData.append('arrangement_id', arrangementId);
            formData.append('nonce', regConfig.nonce);

            fetch(regConfig.ajaxUrl, { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        showMessage(res.data.message, false);
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        showMessage(res.data.message || 'Noe gikk galt.', true);
                        setLoading(btn, false);
                    }
                })
                .catch(function() {
                    showMessage('Noe gikk galt. Prøv igjen.', true);
                    setLoading(btn, false);
                });
        });
    }
});
</script>

<?php get_footer(); ?>
