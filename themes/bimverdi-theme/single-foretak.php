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
$nettside = get_field('hjemmeside', $company_id);
$kontakt_epost = get_field('kontakt_epost', $company_id);
$bv_rolle = get_field('bv_rolle', $company_id);
$er_aktiv_deltaker = $bv_rolle && $bv_rolle !== 'Ikke deltaker';

// Hent profil-data
$kort_beskrivelse = get_field('kort_beskrivelse', $company_id);
$bransje_rolle = get_field('bransje_rolle', $company_id);
$interesseomrader = get_field('interesseomrader', $company_id);
$kundetyper_acf = get_field('kundetyper', $company_id);
$artikkel_lenke = get_field('artikkel_lenke', $company_id);
$hashtag = get_field('hashtag', $company_id);
$land = get_field('land', $company_id);

// Hent sosiale medier
$linkedin_url = get_field('linkedin_url', $company_id);
$facebook_url = get_field('facebook_url', $company_id);
$youtube_url = get_field('youtube_url', $company_id);
$twitter_url = get_field('twitter_url', $company_id);

// Hent hovedkontakt
$hovedkontakt_id = get_field('hovedkontaktperson', $company_id);
$hovedkontakt = $hovedkontakt_id ? get_userdata($hovedkontakt_id) : null;

// Hent BRREG-data
$organisasjonsform = get_field('organisasjonsform', $company_id);
$naeringskode = get_field('naeringskode', $company_id);
$naeringskode_beskrivelse = get_field('naeringskode_beskrivelse', $company_id);
$antall_ansatte = get_field('antall_ansatte', $company_id);
$kommune = get_field('kommune', $company_id);
$stiftelsesdato = get_field('stiftelsesdato', $company_id);
$bedriftsnavn = get_field('bedriftsnavn', $company_id);

// Hent taxonomier
$bransjekategorier = wp_get_post_terms($company_id, 'bransjekategori', array('fields' => 'all'));
$kundetyper = wp_get_post_terms($company_id, 'kundetype', array('fields' => 'all'));
$temagrupper = wp_get_post_terms($company_id, 'temagruppe', array('fields' => 'all'));

// Hent foretakets verktøy (ACF post_object lagrer ID som integer)
$company_tools = get_posts(array(
    'post_type' => 'verktoy',
    'meta_query' => array(
        array(
            'key' => 'eier_leverandor',
            'value' => $company_id,
            'compare' => '=',
        ),
    ),
    'posts_per_page' => -1,
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

// Tell tilleggskontakter (alle brukere unntatt hovedkontakt)
$tilleggskontakter_count = 0;
if ($hovedkontakt_id) {
    foreach ($company_users as $user) {
        if ($user->ID != $hovedkontakt_id) {
            $tilleggskontakter_count++;
        }
    }
} else {
    $tilleggskontakter_count = $user_count;
}

// Sjekk om besøkende bruker tilhører dette foretaket
$current_user_company_id = get_user_meta(get_current_user_id(), 'bim_verdi_company_id', true);
$is_own_company = ($current_user_company_id == $company_id);

// Hent artikler (bedrift-tilknyttede + medforfattere fra foretaket)
$company_articles_by_bedrift = get_posts(array(
    'post_type' => 'artikkel',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => array(
        array(
            'key' => 'artikkel_bedrift',
            'value' => $company_id,
        ),
    ),
));

// Finn artikler der ansatte er medforfattere
$company_articles_by_medforfattere = array();
if (!empty($company_users)) {
    $user_ids = wp_list_pluck($company_users, 'ID');
    foreach ($user_ids as $uid) {
        $medforfattere_articles = get_posts(array(
            'post_type' => 'artikkel',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'artikkel_medforfattere',
                    'value' => '"' . $uid . '"',
                    'compare' => 'LIKE',
                ),
            ),
        ));
        $company_articles_by_medforfattere = array_merge($company_articles_by_medforfattere, $medforfattere_articles);
    }
}

// Kombiner og dedupliser
$all_article_ids = array_unique(array_merge($company_articles_by_bedrift, $company_articles_by_medforfattere));
$company_articles = array();
if (!empty($all_article_ids)) {
    $company_articles = get_posts(array(
        'post_type' => 'artikkel',
        'post_status' => 'publish',
        'posts_per_page' => 6,
        'post__in' => $all_article_ids,
        'orderby' => 'date',
        'order' => 'DESC',
    ));
}

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

<main class="min-h-screen bg-white">
    <div class="max-w-7xl mx-auto px-6 py-8">

        <!-- Breadcrumb -->
        <nav class="mb-6" aria-label="Brødsmulesti">
            <ol class="flex items-center gap-2 text-sm text-[#57534E]">
                <li>
                    <a href="<?php echo esc_url(get_post_type_archive_link('foretak')); ?>" class="hover:text-[#111827] transition-colors flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        Deltakere
                    </a>
                </li>
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </li>
                <li class="text-[#111827] font-medium" aria-current="page"><?php echo esc_html($company_title); ?></li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-6 mb-10">
            <div class="flex items-start gap-5">
                <!-- Logo -->
                <?php if ($logo_url): ?>
                    <div class="w-16 h-16 rounded-lg bg-[#F5F5F4] flex items-center justify-center overflow-hidden flex-shrink-0 border border-[#E7E5E4]">
                        <img src="<?php echo esc_url($logo_url); ?>" alt="" class="w-full h-full object-contain p-1.5">
                    </div>
                <?php else: ?>
                    <div class="w-16 h-16 rounded-lg bg-[#F5F5F4] flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl font-bold text-[#A8A29E]"><?php echo esc_html(strtoupper(substr($company_title, 0, 2))); ?></span>
                    </div>
                <?php endif; ?>

                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h1 class="text-3xl font-bold text-[#111827]"><?php echo esc_html($company_title); ?><?php echo bimverdi_admin_id_badge(); ?><?php echo bimverdi_admin_user_badge(); ?></h1>
                        <?php if ($er_aktiv_deltaker): ?>
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-[#166534] bg-[#DCFCE7] px-2.5 py-1 rounded-full">
                                <span class="w-1.5 h-1.5 bg-[#166534] rounded-full"></span>
                                <?php echo esc_html($bv_rolle); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($poststed): ?>
                        <p class="text-[#57534E]"><?php echo esc_html($poststed); ?></p>
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
                <?php
                // Labels for bransje_rolle
                $bransje_labels = array(
                    'bestiller_byggherre' => 'Bestiller/byggherre',
                    'arkitekt_radgiver' => 'Arkitekt/rådgiver',
                    'entreprenor_byggmester' => 'Entreprenør/byggmester',
                    'byggevareprodusent' => 'Byggevareprodusent',
                    'byggevarehandel' => 'Byggevarehandel',
                    'eiendom_drift' => 'Eiendom/drift',
                    'digital_leverandor' => 'Digital leverandør',
                    'organisasjon' => 'Organisasjon/nettverk',
                    'tjenesteleverandor' => 'Tjenesteleverandør',
                    'offentlig' => 'Offentlig instans',
                    'utdanning' => 'Utdanning',
                );

                // Labels for interesseomrader
                $interesse_labels = array(
                    'byggesak' => 'ByggesaksBIM',
                    'prosjekt' => 'ProsjektBIM',
                    'eiendom' => 'EiendomsBIM',
                    'miljo' => 'MiljøBIM',
                    'sirk' => 'SirkBIM',
                    'tech' => 'BIMtech',
                );
                ?>
                <section>
                    <h2 class="text-lg font-bold text-[#111827] mb-4">Om foretaket</h2>

                    <?php
                    // Prefer kort_beskrivelse (imported from FF), fall back to beskrivelse (legacy ACF field)
                    $display_beskrivelse = !empty($kort_beskrivelse) ? $kort_beskrivelse : $beskrivelse;
                    ?>
                    <?php if ($display_beskrivelse): ?>
                        <div class="prose prose-sm max-w-none text-[#57534E] mb-6">
                            <?php echo wpautop(esc_html($display_beskrivelse)); ?>
                        </div>
                    <?php elseif (has_excerpt()): ?>
                        <div class="prose prose-sm max-w-none text-[#57534E] mb-6">
                            <p><?php echo get_the_excerpt(); ?></p>
                        </div>
                    <?php else: ?>
                        <p class="text-[#57534E] italic mb-6">Ingen beskrivelse tilgjengelig.</p>
                    <?php endif; ?>

                    <!-- Bransje/rolle -->
                    <?php if (!empty($bransje_rolle) && is_array($bransje_rolle)): ?>
                    <div class="mb-4">
                        <h3 class="text-xs font-bold text-[#57534E] uppercase tracking-wider mb-2">Bransje/rolle</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($bransje_rolle as $rolle): ?>
                            <span class="inline-block text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-3 py-1.5 rounded">
                                <?php echo esc_html(isset($bransje_labels[$rolle]) ? $bransje_labels[$rolle] : $rolle); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Interesseområder -->
                    <?php if (!empty($interesseomrader) && is_array($interesseomrader)): ?>
                    <div class="mb-4">
                        <h3 class="text-xs font-bold text-[#57534E] uppercase tracking-wider mb-2">Interesseområder</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($interesseomrader as $interesse): ?>
                            <span class="inline-block text-xs font-medium bg-[#ECFDF5] text-[#059669] px-3 py-1.5 rounded">
                                <?php echo esc_html(isset($interesse_labels[$interesse]) ? $interesse_labels[$interesse] : $interesse); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Kundetyper (fra ACF) -->
                    <?php
                    $kundetype_labels = array(
                        'bestiller' => 'Bestillere/byggherrer',
                        'arkitekt' => 'Arkitekter/rådgivere',
                        'entreprenor' => 'Entreprenører',
                        'produsent' => 'Produsenter',
                        'handel' => 'Handel',
                        'eiendom' => 'Eiendomsforvaltere',
                        'digital' => 'Digitale leverandører',
                        'tjeneste' => 'Tjenesteytere',
                        'utdanning' => 'Utdanning',
                        'brukere' => 'Sluttbrukere',
                    );
                    ?>
                    <?php if (!empty($kundetyper_acf) && is_array($kundetyper_acf)): ?>
                    <div class="mb-4">
                        <h3 class="text-xs font-bold text-[#57534E] uppercase tracking-wider mb-2">Kundetyper</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($kundetyper_acf as $kundetype): ?>
                            <span class="inline-block text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-3 py-1.5 rounded">
                                <?php echo esc_html(isset($kundetype_labels[$kundetype]) ? $kundetype_labels[$kundetype] : $kundetype); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Nøkkelord (hashtags) -->
                    <?php if (!empty($hashtag)): ?>
                    <div class="mb-4">
                        <h3 class="text-xs font-bold text-[#57534E] uppercase tracking-wider mb-2">Nøkkelord</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php
                            // Split hashtags by comma and create tags
                            $keywords = array_map('trim', explode(',', $hashtag));
                            foreach ($keywords as $keyword):
                                if (empty($keyword)) continue;
                            ?>
                            <span class="inline-block text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-3 py-1.5 rounded">
                                <?php echo esc_html($keyword); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Tags: Taxonomier -->
                    <?php if ((!empty($bransjekategorier) && !is_wp_error($bransjekategorier)) || (!empty($kundetyper) && !is_wp_error($kundetyper)) || (!empty($temagrupper) && !is_wp_error($temagrupper))): ?>
                    <div class="flex flex-wrap gap-2 pt-4 border-t border-[#E7E5E4]">
                        <?php if (!empty($bransjekategorier) && !is_wp_error($bransjekategorier)): ?>
                            <?php foreach ($bransjekategorier as $cat): ?>
                            <span class="inline-block text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-3 py-1.5 rounded">
                                <?php echo esc_html($cat->name); ?>
                            </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (!empty($kundetyper) && !is_wp_error($kundetyper)): ?>
                            <?php foreach ($kundetyper as $type): ?>
                            <span class="inline-block text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-3 py-1.5 rounded">
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

                    <!-- Ekstern lenke -->
                    <?php if ($artikkel_lenke): ?>
                    <div class="mt-4 pt-4 border-t border-[#E7E5E4]">
                        <a href="<?php echo esc_url($artikkel_lenke); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm text-[#FF8B5E] hover:underline">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            Les mer om <?php echo esc_html($company_title); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </section>

                <!-- Verktøy Section -->
                <section class="border-t border-[#E7E5E4] pt-10">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-[#111827]">Verktøy</h2>
                        <?php if ($tool_count > 0): ?>
                        <span class="text-sm text-[#57534E]"><?php echo $tool_count; ?> verktøy</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($company_tools)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <?php foreach ($company_tools as $tool): ?>
                        <a href="<?php echo get_permalink($tool->ID); ?>" class="group block bg-white border border-[#E7E5E4] rounded-xl shadow-sm hover:shadow-md hover:border-[#D6D3D1] transition-all p-6">
                            <!-- Icon -->
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mb-5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#A8A29E" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                            </div>

                            <!-- Title -->
                            <h3 class="text-base font-semibold text-[#111827] mb-2 line-clamp-2 group-hover:text-[#1F2937]"><?php echo esc_html($tool->post_title); ?></h3>

                            <!-- Footer -->
                            <div class="flex items-center justify-between pt-4 mt-4 border-t border-[#E7E5E4]">
                                <span class="text-xs text-[#57534E]"><?php echo esc_html($company_title); ?></span>
                                <span class="inline-flex items-center gap-1 text-sm font-medium text-[#111827] group-hover:gap-2 transition-all">
                                    Se detaljer
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                                </span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-[#57534E] text-sm">
                        Ingen registrerte verktøy ennå. Tilbyr dere digitale løsninger?
                        <a href="<?php echo home_url('/verktoy/'); ?>" class="text-[#FF8B5E] hover:underline">Utforsk verktøykatalogen</a>
                    </p>
                    <?php endif; ?>
                </section>

                <!-- Temagrupper Section -->
                <section class="border-t border-[#E7E5E4] pt-10">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-[#111827]">Temagrupper</h2>
                    </div>

                    <?php if (!empty($temagrupper) && !is_wp_error($temagrupper)): ?>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($temagrupper as $gruppe): ?>
                        <a href="<?php echo get_term_link($gruppe); ?>" class="inline-flex items-center gap-2 text-sm font-medium bg-[#ECFDF5] text-[#059669] px-4 py-2 rounded-lg hover:bg-[#D1FAE5] transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                            <?php echo esc_html($gruppe->name); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-[#57534E] text-sm">
                        Ikke koblet til temagrupper ennå.
                        <a href="<?php echo home_url('/temagruppe/'); ?>" class="text-[#FF8B5E] hover:underline">Utforsk våre temagrupper</a>
                    </p>
                    <?php endif; ?>
                </section>

                <!-- Artikler Section -->
                <?php
                    $category_labels = array(
                        'fagartikkel' => 'Fagartikkel',
                        'case' => 'Case',
                        'tips' => 'Tips',
                        'nyhet' => 'Nyhet',
                        'kommentar' => 'Kommentar',
                    );
                    $article_count = count($company_articles);
                ?>
                <section class="border-t border-[#E7E5E4] pt-10">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-[#111827]">Artikler</h2>
                        <?php if ($article_count > 0): ?>
                        <span class="text-sm text-[#57534E]"><?php echo $article_count; ?> artikler</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($company_articles)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($company_articles as $article):
                            $kategori = get_field('artikkel_kategori', $article->ID);
                            $ingress = get_field('artikkel_ingress', $article->ID);
                            $author = get_the_author_meta('display_name', $article->post_author);
                        ?>
                        <div class="bg-white border border-[#E7E5E4] rounded-xl shadow-sm hover:shadow-md hover:border-[#D6D3D1] transition-all p-6 flex flex-col justify-between min-h-[200px]">
                            <div>
                                <!-- Icon -->
                                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#A8A29E" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                </div>

                                <!-- Category Tag -->
                                <?php if ($kategori && isset($category_labels[$kategori])): ?>
                                <span class="inline-block text-xs bg-white/60 text-[#57534E] px-2 py-0.5 rounded mb-3">
                                    <?php echo esc_html($category_labels[$kategori]); ?>
                                </span>
                                <?php endif; ?>

                                <!-- Title -->
                                <h3 class="font-bold text-[#111827] mb-2 line-clamp-2"><?php echo esc_html($article->post_title); ?></h3>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-between pt-3 border-t border-[#E7E5E4]">
                                <span class="text-xs text-[#57534E]"><?php echo get_the_date('j. M Y', $article->ID); ?></span>
                                <a href="<?php echo get_permalink($article->ID); ?>" class="inline-flex items-center gap-1 text-sm font-bold text-[#111827] hover:opacity-70 transition-opacity">
                                    Les artikkel
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-[#57534E] text-sm">
                        Ingen publiserte artikler ennå.
                        <a href="<?php echo home_url('/artikler/'); ?>" class="text-[#FF8B5E] hover:underline">Utforsk artikler fra nettverket</a>
                    </p>
                    <?php endif; ?>
                </section>

                <!-- Kunnskapskilder Section -->
                <?php
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
                    $kilde_count = count($company_kunnskapskilder);
                ?>
                <section class="border-t border-[#E7E5E4] pt-10">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-[#111827]">Kunnskapskilder</h2>
                        <?php if ($kilde_count > 0): ?>
                        <span class="text-sm text-[#57534E]"><?php echo $kilde_count; ?> kilder</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($company_kunnskapskilder)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($company_kunnskapskilder as $kilde):
                            $kildetype = get_field('kildetype', $kilde->ID);
                            $kort_beskrivelse = get_field('kort_beskrivelse', $kilde->ID);
                            $utgiver = get_field('utgiver', $kilde->ID);
                            $ekstern_lenke = get_field('ekstern_lenke', $kilde->ID);
                        ?>
                        <div class="bg-white border border-[#E7E5E4] rounded-xl shadow-sm hover:shadow-md hover:border-[#D6D3D1] transition-all p-6 flex flex-col justify-between min-h-[200px]">
                            <div>
                                <!-- Icon -->
                                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#A8A29E" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                                </div>

                                <!-- Type Tag -->
                                <?php if ($kildetype && isset($kildetype_labels[$kildetype])): ?>
                                <span class="inline-block text-xs bg-[#F0FDFA] text-[#0D9488] px-2 py-0.5 rounded mb-3">
                                    <?php echo esc_html($kildetype_labels[$kildetype]); ?>
                                </span>
                                <?php endif; ?>

                                <!-- Title -->
                                <h3 class="font-bold text-[#111827] mb-2 line-clamp-2"><?php echo esc_html($kilde->post_title); ?></h3>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-between pt-3 border-t border-[#E7E5E4]">
                                <span class="text-xs text-[#57534E]"><?php echo $utgiver ? esc_html($utgiver) : esc_html($company_title); ?></span>
                                <a href="<?php echo get_permalink($kilde->ID); ?>" class="inline-flex items-center gap-1 text-sm font-bold text-[#111827] hover:opacity-70 transition-opacity">
                                    Se kilde
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-[#57534E] text-sm">
                        Ingen koblede kunnskapskilder ennå.
                        <a href="<?php echo home_url('/kunnskapskilder/'); ?>" class="text-[#FF8B5E] hover:underline">Utforsk kunnskapsbiblioteket</a>
                    </p>
                    <?php endif; ?>
                </section>

            </div>

            <!-- Right Column: Sidebar -->
            <div class="lg:col-span-1 space-y-6">

                <!-- STATUS Section -->
                <section class="bg-[#F5F5F4] rounded-lg p-5">
                    <h3 class="text-xs font-bold text-[#57534E] uppercase tracking-wider mb-6">Status</h3>

                    <dl class="space-y-6">
                        <div class="flex justify-between items-center">
                            <dt class="text-sm text-[#57534E]">BIM Verdi rolle</dt>
                            <dd>
                                <?php if ($er_aktiv_deltaker): ?>
                                <span class="inline-block text-xs font-medium bg-[#DCFCE7] text-[#166534] px-2.5 py-1 rounded">
                                    <?php echo esc_html($bv_rolle); ?>
                                </span>
                                <?php else: ?>
                                <span class="inline-block text-xs font-medium bg-[#F5F5F4] text-[#57534E] px-2.5 py-1 rounded">
                                    Ikke deltaker
                                </span>
                                <?php endif; ?>
                            </dd>
                        </div>

                        <?php if ($tool_count > 0): ?>
                        <div>
                            <dt class="text-sm text-[#57534E] flex items-center gap-2 mb-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#57534E]"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                                Verktøy
                            </dt>
                            <dd class="text-sm text-[#111827] pl-[22px]"><?php echo $tool_count; ?> registrerte</dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($antall_ansatte > 0): ?>
                        <div>
                            <dt class="text-sm text-[#57534E] flex items-center gap-2 mb-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#57534E]"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                Ansatte
                            </dt>
                            <dd class="text-sm text-[#111827] pl-[22px]"><?php echo $antall_ansatte; ?> personer</dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($tilleggskontakter_count > 0): ?>
                        <div>
                            <dt class="text-sm text-[#57534E] flex items-center gap-2 mb-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#57534E]"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                Tilleggskontakter
                            </dt>
                            <dd class="text-sm text-[#111827] pl-[22px]"><?php echo $tilleggskontakter_count; ?> personer</dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </section>

                <!-- HOVEDKONTAKT Section -->
                <?php if ($hovedkontakt): ?>
                <section class="bg-[#F5F5F4] rounded-lg p-5">
                    <h3 class="text-xs font-bold text-[#57534E] uppercase tracking-wider mb-4">Hovedkontakt</h3>

                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 bg-[#E7E5E4] rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-base font-medium text-[#57534E]">
                                <?php echo esc_html(strtoupper(substr($hovedkontakt->display_name, 0, 1))); ?>
                            </span>
                        </div>
                        <div class="min-w-0">
                            <div class="font-medium text-[#111827] text-sm"><?php echo esc_html($hovedkontakt->display_name); ?></div>
                            <?php
                            $hovedkontakt_tittel = get_user_meta($hovedkontakt_id, 'stillingstittel', true);
                            if ($hovedkontakt_tittel):
                            ?>
                            <div class="text-xs text-[#57534E] mb-2"><?php echo esc_html($hovedkontakt_tittel); ?></div>
                            <?php endif; ?>
                            <?php if ($hovedkontakt->user_email): ?>
                            <a href="mailto:<?php echo esc_attr($hovedkontakt->user_email); ?>" class="text-xs text-[#FF8B5E] hover:underline break-all">
                                <?php echo esc_html($hovedkontakt->user_email); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <!-- KONTAKTINFO Section -->
                <section class="bg-[#F5F5F4] rounded-lg p-5">
                    <h3 class="text-xs font-bold text-[#57534E] uppercase tracking-wider mb-4">Kontaktinfo</h3>

                    <dl class="space-y-0 divide-y divide-[#E7E5E4]">
                        <?php if ($org_nummer): ?>
                        <div class="py-3 first:pt-0">
                            <dt class="text-xs text-[#A8A29E] mb-0.5">Org.nummer</dt>
                            <dd class="text-sm text-[#111827] font-mono"><?php echo esc_html($org_nummer); ?></dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($organisasjonsform): ?>
                        <div class="py-3">
                            <dt class="text-xs text-[#A8A29E] mb-0.5">Organisasjonsform</dt>
                            <dd class="text-sm text-[#111827]"><?php echo esc_html($organisasjonsform); ?></dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($stiftelsesdato): ?>
                        <div class="py-3">
                            <dt class="text-xs text-[#A8A29E] mb-0.5">Stiftet</dt>
                            <?php
                                    $norske_maneder = ['januar','februar','mars','april','mai','juni','juli','august','september','oktober','november','desember'];
                                    $ts = strtotime($stiftelsesdato);
                                    $dato_norsk = date('j', $ts) . '. ' . $norske_maneder[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
                                ?>
                                <dd class="text-sm text-[#111827]"><?php echo esc_html($dato_norsk); ?></dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($naeringskode): ?>
                        <div class="py-3">
                            <dt class="text-xs text-[#A8A29E] mb-0.5">Næringskode</dt>
                            <dd class="text-sm text-[#111827]">
                                <span class="font-medium"><?php echo esc_html($naeringskode); ?></span>
                                <?php if ($naeringskode_beskrivelse): ?>
                                <br><span class="text-xs text-[#57534E]"><?php echo esc_html($naeringskode_beskrivelse); ?></span>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($adresse || $postnummer || $poststed || $kommune || $land): ?>
                        <div class="py-3">
                            <dt class="text-xs text-[#A8A29E] mb-0.5">Adresse</dt>
                            <dd class="text-sm text-[#111827]">
                                <?php if ($adresse): echo esc_html($adresse) . '<br>'; endif; ?>
                                <?php
                                $location_parts = array_filter([
                                    trim($postnummer . ' ' . $poststed),
                                    $kommune ? $kommune . ' kommune' : null
                                ]);
                                echo esc_html(implode(', ', $location_parts));
                                ?>
                                <?php if ($land && strtolower($land) !== 'norge'): ?>
                                <br><?php echo esc_html($land); ?>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($telefon): ?>
                        <div class="py-3">
                            <dt class="text-xs text-[#A8A29E] mb-0.5">Telefon</dt>
                            <dd class="text-sm">
                                <a href="tel:<?php echo esc_attr($telefon); ?>" class="text-[#FF8B5E] hover:underline">
                                    <?php echo esc_html($telefon); ?>
                                </a>
                            </dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($kontakt_epost): ?>
                        <div class="py-3">
                            <dt class="text-xs text-[#A8A29E] mb-0.5">E-post</dt>
                            <dd class="text-sm">
                                <a href="mailto:<?php echo esc_attr($kontakt_epost); ?>" class="text-[#FF8B5E] hover:underline break-all">
                                    <?php echo esc_html($kontakt_epost); ?>
                                </a>
                            </dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($nettside): ?>
                        <div class="py-3">
                            <dt class="text-xs text-[#A8A29E] mb-0.5">Nettside</dt>
                            <dd class="text-sm">
                                <a href="<?php echo esc_url($nettside); ?>" target="_blank" rel="noopener" class="text-[#111827] hover:underline inline-flex items-center gap-1">
                                    <?php echo esc_html(parse_url($nettside, PHP_URL_HOST) ?: 'Besøk nettside'); ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#57534E]"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                </a>
                            </dd>
                        </div>
                        <?php endif; ?>

                        <?php if ($linkedin_url || $facebook_url || $youtube_url || $twitter_url): ?>
                        <div class="py-3 last:pb-0">
                            <dt class="text-xs text-[#A8A29E] mb-0.5">Sosiale medier</dt>
                            <dd class="flex items-center gap-3 mt-1">
                                <?php if ($linkedin_url): ?>
                                <a href="<?php echo esc_url($linkedin_url); ?>" target="_blank" rel="noopener" class="text-[#57534E] hover:text-[#0A66C2] transition-colors" title="LinkedIn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                </a>
                                <?php endif; ?>
                                <?php if ($facebook_url): ?>
                                <a href="<?php echo esc_url($facebook_url); ?>" target="_blank" rel="noopener" class="text-[#57534E] hover:text-[#1877F2] transition-colors" title="Facebook">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                                <?php endif; ?>
                                <?php if ($youtube_url): ?>
                                <a href="<?php echo esc_url($youtube_url); ?>" target="_blank" rel="noopener" class="text-[#57534E] hover:text-[#FF0000] transition-colors" title="YouTube">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                </a>
                                <?php endif; ?>
                                <?php if ($twitter_url): ?>
                                <a href="<?php echo esc_url($twitter_url); ?>" target="_blank" rel="noopener" class="text-[#57534E] hover:text-[#111827] transition-colors" title="X (Twitter)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"/></svg>
                                </a>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </section>

                <!-- CTA: Bli deltaker -->
                <?php if (!is_user_logged_in()): ?>
                <section class="bg-[#111827] rounded-lg p-5 text-center">
                    <h3 class="text-sm font-bold text-white mb-2">Bli deltaker i BIM Verdi</h3>
                    <p class="text-xs text-[#A8A29E] mb-4">
                        Få tilgang til nettverket og verktøy fra alle deltakere.
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
