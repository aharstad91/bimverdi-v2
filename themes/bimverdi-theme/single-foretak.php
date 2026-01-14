<?php
/**
 * Single Foretak Profile
 *
 * Offentlig visning av foretaksprofil med logo, beskrivelse, verktøy og kontaktinfo.
 * Design based on UI Contract v1 - Variant B (Dividers/Whitespace)
 * Follows same pattern as single-verktoy.php
 *
 * @package BimVerdi_Theme
 */

get_header();

$company_id = get_the_ID();
$company_title = get_the_title();

// Hent ACF-felter
$logo_id = get_field('logo', $company_id);
$logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
$beskrivelse = get_field('beskrivelse', $company_id);
$org_nummer = get_field('organisasjonsnummer', $company_id);
$adresse = get_field('adresse', $company_id);
$postnummer = get_field('postnummer', $company_id);
$poststed = get_field('poststed', $company_id);
$telefon = get_field('telefon', $company_id);
$nettside = get_field('nettside', $company_id);
$kontakt_epost = get_field('kontakt_epost', $company_id);
$er_aktiv_deltaker = get_field('er_aktiv_deltaker', $company_id);

// Hent BRREG-data
$organisasjonsform = get_field('organisasjonsform', $company_id);
$naeringskode = get_field('naeringskode', $company_id);
$naeringskode_beskrivelse = get_field('naeringskode_beskrivelse', $company_id);
$antall_ansatte = get_field('antall_ansatte', $company_id);
$kommune = get_field('kommune', $company_id);

// Hent taxonomier
$bransjekategorier = wp_get_post_terms($company_id, 'bransjekategori', array('fields' => 'all'));
$kundetyper = wp_get_post_terms($company_id, 'kundetype', array('fields' => 'all'));
$temagrupper = wp_get_post_terms($company_id, 'temagruppe', array('fields' => 'all'));

// Hent foretakets verktøy
$company_tools = get_posts(array(
    'post_type' => 'verktoy',
    'meta_query' => array(
        array(
            'key' => 'verktoy_eier',
            'value' => $company_id,
        )
    ),
    'posts_per_page' => 6,
    'post_status' => 'publish',
));

// Hent ansatte/brukere i foretaket
$company_users = get_users(array(
    'meta_key' => 'bim_verdi_company_id',
    'meta_value' => $company_id,
));

// Antall verktøy
$tool_count = count($company_tools);
$user_count = count($company_users);

// Sjekk om besøkende bruker tilhører dette foretaket
$current_user_company_id = get_user_meta(get_current_user_id(), 'bim_verdi_company_id', true);
$is_own_company = ($current_user_company_id == $company_id);

// Hent artikler
$company_articles = get_posts(array(
    'post_type' => 'artikkel',
    'post_status' => 'publish',
    'posts_per_page' => 6,
    'meta_query' => array(
        array(
            'key' => 'artikkel_bedrift',
            'value' => $company_id,
        ),
    ),
));

// Hent kunnskapskilder
$company_kunnskapskilder = get_posts(array(
    'post_type' => 'kunnskapskilde',
    'post_status' => 'publish',
    'posts_per_page' => 6,
    'meta_query' => array(
        array(
            'key' => 'tilknyttet_bedrift',
            'value' => $company_id,
        ),
    ),
));
?>

<main class="min-h-screen bg-[#FAFAF8]">
    <div class="max-w-7xl mx-auto px-6 py-8">

        <!-- Breadcrumb -->
        <nav class="mb-6" aria-label="Brødsmulesti">
            <ol class="flex items-center gap-2 text-sm text-[#5A5A5A]">
                <li>
                    <a href="<?php echo esc_url(get_post_type_archive_link('foretak')); ?>" class="hover:text-[#1A1A1A] transition-colors flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        Deltakere
                    </a>
                </li>
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </li>
                <li class="text-[#1A1A1A] font-medium" aria-current="page"><?php echo esc_html($company_title); ?></li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-6 mb-10">
            <div class="flex items-start gap-5">
                <!-- Logo -->
                <?php if ($logo_url): ?>
                    <div class="w-16 h-16 rounded-lg bg-[#F2F0EB] flex items-center justify-center overflow-hidden flex-shrink-0 border border-[#E5E0D8]">
                        <img src="<?php echo esc_url($logo_url); ?>" alt="" class="w-full h-full object-contain p-1.5">
                    </div>
                <?php else: ?>
                    <div class="w-16 h-16 rounded-lg bg-[#F2F0EB] flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl font-bold text-[#9D8F7F]"><?php echo esc_html(strtoupper(substr($company_title, 0, 2))); ?></span>
                    </div>
                <?php endif; ?>

                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h1 class="text-3xl font-bold text-[#1A1A1A]"><?php echo esc_html($company_title); ?></h1>
                        <?php if ($er_aktiv_deltaker): ?>
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-[#166534] bg-[#DCFCE7] px-2.5 py-1 rounded-full">
                                <span class="w-1.5 h-1.5 bg-[#166534] rounded-full"></span>
                                Aktiv deltaker
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($poststed): ?>
                        <p class="text-[#5A5A5A]"><?php echo esc_html($poststed); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($is_own_company): ?>
            <div class="flex items-center gap-3 flex-shrink-0">
                <?php bimverdi_button([
                    'text' => 'Rediger',
                    'variant' => 'secondary',
                    'icon' => 'square-pen',
                    'href' => home_url('/min-side/foretak/')
                ]); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Two-Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

            <!-- Left Column: Main Content -->
            <div class="lg:col-span-2 space-y-10">

                <!-- Om foretaket Section -->
                <section>
                    <h2 class="text-lg font-bold text-[#1A1A1A] mb-4">Om foretaket</h2>

                    <?php if ($beskrivelse): ?>
                        <div class="prose prose-sm max-w-none text-[#5A5A5A] mb-6">
                            <?php echo wpautop(esc_html($beskrivelse)); ?>
                        </div>
                    <?php elseif (has_excerpt()): ?>
                        <div class="prose prose-sm max-w-none text-[#5A5A5A] mb-6">
                            <p><?php echo get_the_excerpt(); ?></p>
                        </div>
                    <?php else: ?>
                        <p class="text-[#5A5A5A] italic mb-6">Ingen beskrivelse tilgjengelig.</p>
                    <?php endif; ?>

                    <!-- Tags: Bransje og Kundetyper -->
                    <?php if ((!empty($bransjekategorier) && !is_wp_error($bransjekategorier)) || (!empty($kundetyper) && !is_wp_error($kundetyper)) || (!empty($temagrupper) && !is_wp_error($temagrupper))): ?>
                    <div class="flex flex-wrap gap-2 pt-4 border-t border-[#E5E0D8]">
                        <?php if (!empty($bransjekategorier) && !is_wp_error($bransjekategorier)): ?>
                            <?php foreach ($bransjekategorier as $cat): ?>
                            <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-3 py-1.5 rounded">
                                <?php echo esc_html($cat->name); ?>
                            </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (!empty($kundetyper) && !is_wp_error($kundetyper)): ?>
                            <?php foreach ($kundetyper as $type): ?>
                            <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-3 py-1.5 rounded">
                                <?php echo esc_html($type->name); ?>
                            </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (!empty($temagrupper) && !is_wp_error($temagrupper)): ?>
                            <?php foreach ($temagrupper as $gruppe): ?>
                            <span class="inline-block text-xs font-medium bg-[#ECFDF5] text-[#059669] px-3 py-1.5 rounded">
                                <?php echo esc_html($gruppe->name); ?>
                            </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </section>

                <!-- Verktøy Section -->
                <?php if (!empty($company_tools)): ?>
                <section class="border-t border-[#E5E0D8] pt-10">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-[#1A1A1A]">Verktøy</h2>
                        <span class="text-sm text-[#5A5A5A]"><?php echo $tool_count; ?> verktøy</span>
                    </div>

                    <div class="space-y-0 divide-y divide-[#E5E0D8]">
                        <?php foreach ($company_tools as $tool):
                            $tool_excerpt = $tool->post_excerpt ?: wp_trim_words($tool->post_content, 20, '...');
                            $tool_categories = wp_get_post_terms($tool->ID, 'verktoykategori', array('fields' => 'names'));
                        ?>
                        <a href="<?php echo get_permalink($tool->ID); ?>" class="block py-5 hover:bg-[#F7F5EF] -mx-2 px-2 rounded transition-colors">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-medium text-[#1A1A1A] mb-1"><?php echo esc_html($tool->post_title); ?></h3>

                                    <?php if (!empty($tool_categories)): ?>
                                    <div class="flex flex-wrap gap-1.5 mb-2">
                                        <?php foreach (array_slice($tool_categories, 0, 2) as $cat): ?>
                                        <span class="text-xs bg-[#F2F0EB] text-[#5A5A5A] px-2 py-0.5 rounded">
                                            <?php echo esc_html($cat); ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($tool_excerpt): ?>
                                    <p class="text-sm text-[#5A5A5A] line-clamp-2"><?php echo esc_html($tool_excerpt); ?></p>
                                    <?php endif; ?>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#9D8F7F] flex-shrink-0 mt-1"><path d="m9 18 6-6-6-6"/></svg>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Artikler Section -->
                <?php if (!empty($company_articles)):
                    $category_labels = array(
                        'fagartikkel' => 'Fagartikkel',
                        'case' => 'Case',
                        'tips' => 'Tips',
                        'nyhet' => 'Nyhet',
                        'kommentar' => 'Kommentar',
                    );
                ?>
                <section class="border-t border-[#E5E0D8] pt-10">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-[#1A1A1A]">Artikler</h2>
                        <span class="text-sm text-[#5A5A5A]"><?php echo count($company_articles); ?> artikler</span>
                    </div>

                    <div class="space-y-0 divide-y divide-[#E5E0D8]">
                        <?php foreach ($company_articles as $article):
                            $kategori = get_field('artikkel_kategori', $article->ID);
                            $ingress = get_field('artikkel_ingress', $article->ID);
                            $author = get_the_author_meta('display_name', $article->post_author);
                        ?>
                        <a href="<?php echo get_permalink($article->ID); ?>" class="block py-5 hover:bg-[#F7F5EF] -mx-2 px-2 rounded transition-colors">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <?php if ($kategori && isset($category_labels[$kategori])): ?>
                                    <span class="inline-block text-xs bg-[#F2F0EB] text-[#5A5A5A] px-2 py-0.5 rounded mb-2">
                                        <?php echo esc_html($category_labels[$kategori]); ?>
                                    </span>
                                    <?php endif; ?>

                                    <h3 class="font-medium text-[#1A1A1A] mb-1"><?php echo esc_html($article->post_title); ?></h3>

                                    <?php if ($ingress): ?>
                                    <p class="text-sm text-[#5A5A5A] line-clamp-2 mb-2"><?php echo wp_trim_words(esc_html($ingress), 25); ?></p>
                                    <?php endif; ?>

                                    <div class="flex items-center gap-4 text-xs text-[#9D8F7F]">
                                        <span><?php echo esc_html($author); ?></span>
                                        <span><?php echo get_the_date('j. M Y', $article->ID); ?></span>
                                    </div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#9D8F7F] flex-shrink-0 mt-1"><path d="m9 18 6-6-6-6"/></svg>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Kunnskapskilder Section -->
                <?php if (!empty($company_kunnskapskilder)):
                    $kildetype_labels = array(
                        'standard' => 'Standard',
                        'veileder' => 'Veileder',
                        'mal' => 'Mal',
                        'forskningsrapport' => 'Rapport',
                        'casestudie' => 'Case',
                        'opplaering' => 'Opplæring',
                        'dokumentasjon' => 'Dokumentasjon',
                        'nettressurs' => 'Nettressurs',
                        'annet' => 'Annet',
                    );
                ?>
                <section class="border-t border-[#E5E0D8] pt-10">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-[#1A1A1A]">Kunnskapskilder</h2>
                        <span class="text-sm text-[#5A5A5A]"><?php echo count($company_kunnskapskilder); ?> kilder</span>
                    </div>

                    <div class="space-y-0 divide-y divide-[#E5E0D8]">
                        <?php foreach ($company_kunnskapskilder as $kilde):
                            $kildetype = get_field('kildetype', $kilde->ID);
                            $kort_beskrivelse = get_field('kort_beskrivelse', $kilde->ID);
                            $utgiver = get_field('utgiver', $kilde->ID);
                            $ekstern_lenke = get_field('ekstern_lenke', $kilde->ID);
                        ?>
                        <div class="py-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <?php if ($kildetype && isset($kildetype_labels[$kildetype])): ?>
                                    <span class="inline-block text-xs bg-[#F0FDFA] text-[#0D9488] px-2 py-0.5 rounded mb-2">
                                        <?php echo esc_html($kildetype_labels[$kildetype]); ?>
                                    </span>
                                    <?php endif; ?>

                                    <h3 class="font-medium text-[#1A1A1A] mb-1">
                                        <a href="<?php echo get_permalink($kilde->ID); ?>" class="hover:text-[#0D9488] transition-colors">
                                            <?php echo esc_html($kilde->post_title); ?>
                                        </a>
                                    </h3>

                                    <?php if ($kort_beskrivelse): ?>
                                    <p class="text-sm text-[#5A5A5A] line-clamp-2 mb-2"><?php echo wp_trim_words(esc_html($kort_beskrivelse), 25); ?></p>
                                    <?php endif; ?>

                                    <?php if ($utgiver): ?>
                                    <span class="text-xs text-[#9D8F7F]"><?php echo esc_html($utgiver); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <?php if ($ekstern_lenke): ?>
                                    <a href="<?php echo esc_url($ekstern_lenke); ?>" target="_blank" rel="noopener" class="p-2 text-[#5A5A5A] hover:text-[#1A1A1A] hover:bg-[#F2F0EB] rounded transition-colors" title="Åpne ekstern lenke">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                    </a>
                                    <?php endif; ?>
                                    <a href="<?php echo get_permalink($kilde->ID); ?>" class="p-2 text-[#5A5A5A] hover:text-[#1A1A1A] hover:bg-[#F2F0EB] rounded transition-colors" title="Se detaljer">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Ansatte Section -->
                <?php if (!empty($company_users) && count($company_users) > 0): ?>
                <section class="border-t border-[#E5E0D8] pt-10">
                    <h2 class="text-lg font-bold text-[#1A1A1A] mb-6">Ansatte</h2>

                    <div class="flex flex-wrap gap-3">
                        <?php foreach ($company_users as $user):
                            $user_name = $user->display_name;
                            $user_title = get_user_meta($user->ID, 'stillingstittel', true);
                            $initials = strtoupper(substr($user_name, 0, 1));
                        ?>
                        <div class="flex items-center gap-3 bg-[#F7F5EF] rounded-lg px-4 py-3">
                            <div class="w-10 h-10 bg-[#E5E0D8] rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-medium text-[#5A5A5A]"><?php echo esc_html($initials); ?></span>
                            </div>
                            <div>
                                <div class="font-medium text-[#1A1A1A] text-sm"><?php echo esc_html($user_name); ?></div>
                                <?php if ($user_title): ?>
                                <div class="text-xs text-[#5A5A5A]"><?php echo esc_html($user_title); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

            </div>

            <!-- Right Column: Sidebar -->
            <div class="lg:col-span-1 space-y-6">

                <!-- STATUS Section -->
                <section class="bg-[#F7F5EF] rounded-lg p-5">
                    <h3 class="text-xs font-bold text-[#5A5A5A] uppercase tracking-wider mb-6">Status</h3>

                    <dl class="space-y-6">
                        <div class="flex justify-between items-center">
                            <dt class="text-sm text-[#5A5A5A]">Medlemsstatus</dt>
                            <dd>
                                <?php if ($er_aktiv_deltaker): ?>
                                <span class="inline-block text-xs font-medium bg-[#DCFCE7] text-[#166534] px-2.5 py-1 rounded">
                                    Aktiv
                                </span>
                                <?php else: ?>
                                <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-2.5 py-1 rounded">
                                    Ikke aktiv
                                </span>
                                <?php endif; ?>
                            </dd>
                        </div>

                        <?php if ($tool_count > 0): ?>
                        <div>
                            <dt class="text-sm text-[#5A5A5A] flex items-center gap-2 mb-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                                Verktøy
                            </dt>
                            <dd class="text-sm text-[#1A1A1A] pl-[22px]"><?php echo $tool_count; ?> registrerte</dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($user_count > 0): ?>
                        <div>
                            <dt class="text-sm text-[#5A5A5A] flex items-center gap-2 mb-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                Ansatte
                            </dt>
                            <dd class="text-sm text-[#1A1A1A] pl-[22px]"><?php echo $user_count; ?> personer</dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </section>

                <!-- KONTAKTINFO Section -->
                <section class="bg-[#F7F5EF] rounded-lg p-5">
                    <h3 class="text-xs font-bold text-[#5A5A5A] uppercase tracking-wider mb-4">Kontaktinfo</h3>

                    <dl class="space-y-0 divide-y divide-[#E5E0D8]">
                        <?php if ($org_nummer): ?>
                        <div class="py-3 first:pt-0">
                            <dt class="text-xs text-[#9D8F7F] mb-0.5">Org.nummer</dt>
                            <dd class="text-sm text-[#1A1A1A] font-mono"><?php echo esc_html($org_nummer); ?></dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($organisasjonsform): ?>
                        <div class="py-3">
                            <dt class="text-xs text-[#9D8F7F] mb-0.5">Organisasjonsform</dt>
                            <dd class="text-sm text-[#1A1A1A]"><?php echo esc_html($organisasjonsform); ?></dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($naeringskode): ?>
                        <div class="py-3">
                            <dt class="text-xs text-[#9D8F7F] mb-0.5">Næringskode</dt>
                            <dd class="text-sm text-[#1A1A1A]">
                                <span class="font-medium"><?php echo esc_html($naeringskode); ?></span>
                                <?php if ($naeringskode_beskrivelse): ?>
                                <br><span class="text-xs text-[#5A5A5A]"><?php echo esc_html($naeringskode_beskrivelse); ?></span>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($adresse || $postnummer || $poststed): ?>
                        <div class="py-3">
                            <dt class="text-xs text-[#9D8F7F] mb-0.5">Adresse</dt>
                            <dd class="text-sm text-[#1A1A1A]">
                                <?php if ($adresse): echo esc_html($adresse) . '<br>'; endif; ?>
                                <?php echo esc_html(trim($postnummer . ' ' . $poststed)); ?>
                            </dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($telefon): ?>
                        <div class="py-3">
                            <dt class="text-xs text-[#9D8F7F] mb-0.5">Telefon</dt>
                            <dd class="text-sm">
                                <a href="tel:<?php echo esc_attr($telefon); ?>" class="text-[#FF8B5E] hover:underline">
                                    <?php echo esc_html($telefon); ?>
                                </a>
                            </dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($kontakt_epost): ?>
                        <div class="py-3">
                            <dt class="text-xs text-[#9D8F7F] mb-0.5">E-post</dt>
                            <dd class="text-sm">
                                <a href="mailto:<?php echo esc_attr($kontakt_epost); ?>" class="text-[#FF8B5E] hover:underline break-all">
                                    <?php echo esc_html($kontakt_epost); ?>
                                </a>
                            </dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($nettside): ?>
                        <div class="py-3 last:pb-0">
                            <dt class="text-xs text-[#9D8F7F] mb-0.5">Nettside</dt>
                            <dd class="text-sm">
                                <a href="<?php echo esc_url($nettside); ?>" target="_blank" rel="noopener" class="text-[#1A1A1A] hover:underline inline-flex items-center gap-1">
                                    <?php echo esc_html(parse_url($nettside, PHP_URL_HOST) ?: 'Besøk nettside'); ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                </a>
                            </dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </section>

                <!-- CTA: Bli medlem -->
                <?php if (!is_user_logged_in()): ?>
                <section class="bg-[#1A1A1A] rounded-lg p-5 text-center">
                    <h3 class="text-sm font-bold text-white mb-2">Bli medlem i BIM Verdi</h3>
                    <p class="text-xs text-[#9D8F7F] mb-4">
                        Få tilgang til nettverket og verktøy fra alle medlemsbedrifter.
                    </p>
                    <a href="<?php echo home_url('/registrer/'); ?>" class="inline-flex items-center justify-center w-full px-4 py-2.5 bg-[#FF8B5E] text-white text-sm font-medium rounded-lg hover:bg-[#E67A4D] transition-colors">
                        Registrer deg gratis
                    </a>
                </section>
                <?php endif; ?>

            </div>

        </div>

    </div>
</main>

<?php get_footer(); ?>
