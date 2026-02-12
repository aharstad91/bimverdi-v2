<?php
/**
 * Single Kunnskapskilde Template
 *
 * Displays detailed information about a single knowledge source.
 * Design based on UI Contract v1 - Variant B (Dividers/Whitespace)
 *
 * @package BimVerdi_Theme
 */

get_header('minside');

if (have_posts()) : while (have_posts()) : the_post();

// Get ACF fields
$kunnskapskilde_navn = get_field('kunnskapskilde_navn') ?: get_the_title();
$kort_beskrivelse = get_field('kort_beskrivelse');
$detaljert_beskrivelse = get_field('detaljert_beskrivelse');
$ekstern_lenke = get_field('ekstern_lenke');
$utgiver = get_field('utgiver');
$spraak = get_field('spraak');
$versjon = get_field('versjon');
$utgivelsesaar = get_field('utgivelsesaar');
$kildetype = get_field('kildetype');
$geografisk_gyldighet = get_field('geografisk_gyldighet');
$dataformat = get_field('dataformat');
$ant_lovpalagte = get_field('ant_lovpalagte_standarder');
$lovpalagte_standarder = get_field('lovpalagte_standarder');
$ant_anbefalte = get_field('ant_anbefalte_standarder');
$anbefalte_standarder = get_field('anbefalte_standarder');
$tilgang = get_field('tilgang');
$registrert_av = get_field('registrert_av');
$tilknyttet_bedrift = get_field('tilknyttet_bedrift');

// Get taxonomy terms
$temagruppe_terms = wp_get_post_terms(get_the_ID(), 'temagruppe');
$kategori_terms = wp_get_post_terms(get_the_ID(), 'kunnskapskildekategori');

// Check if current user can edit
$current_user_id = get_current_user_id();
$can_edit = false;
if ($current_user_id) {
    $user_company_id = get_user_meta($current_user_id, 'bim_verdi_company_id', true);
    if (current_user_can('manage_options')) {
        $can_edit = true;
    } elseif ($registrert_av && $registrert_av == $current_user_id) {
        $can_edit = true;
    } elseif ($tilknyttet_bedrift && $user_company_id && $tilknyttet_bedrift == $user_company_id) {
        $can_edit = true;
    } elseif (get_post_field('post_author', get_the_ID()) == $current_user_id) {
        $can_edit = true;
    }
}

// Kildetype labels
$kildetype_labels = [
    'standard' => 'Standard (ISO, NS, etc.)',
    'veiledning' => 'Veiledning/metodikk',
    'forskrift_norsk' => 'Forskrift (norsk lov)',
    'forordning_eu' => 'Forordning (EU/EØS)',
    'mal' => 'Mal/Template',
    'forskningsrapport' => 'Forskningsrapport',
    'casestudie' => 'Casestudie',
    'opplaering' => 'Opplæringsmateriell',
    'dokumentasjon' => 'Verktøydokumentasjon',
    'nettressurs' => 'Nettressurs/Database',
    'annet' => 'Annet',
    // Legacy values
    'veileder' => 'Veileder',
];

// Geografisk gyldighet labels
$geo_labels = [
    'nasjonalt' => 'Nasjonalt/Norsk',
    'nordisk' => 'Nordisk',
    'europeisk' => 'Europeisk',
    'internasjonalt' => 'Internasjonalt',
    'annet' => 'Annet',
];

// Dataformat labels
$dataformat_labels = [
    'pdf' => 'PDF-dokument',
    'web_aapent' => 'Web-innhold - åpent',
    'web_lukket' => 'Web-innhold - lukket/betalt',
    'api' => 'Åpent API',
    'ifc' => 'IFC-fil',
    'database' => 'Database/register',
    'annet' => 'Annet',
];

// Språk labels
$spraak_labels = [
    'norsk' => 'Norsk',
    'engelsk' => 'Engelsk',
    'svensk' => 'Svensk',
    'dansk' => 'Dansk',
    'flerspraklig' => 'Flerspråklig'
];

$kilde_updated = get_the_modified_date('d.m.Y');
$kilde_created = get_the_date('d.m.Y');
?>

<main class="min-h-screen bg-[#FAFAF8]">
    <div class="max-w-7xl mx-auto px-6 py-8">

        <!-- Breadcrumb -->
        <nav class="mb-6" aria-label="Brødsmulesti">
            <ol class="flex items-center gap-2 text-sm text-[#5A5A5A]">
                <li>
                    <a href="<?php echo esc_url(home_url('/kunnskapskilder/')); ?>" class="hover:text-[#1A1A1A] transition-colors flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        Kunnskapskilder
                    </a>
                </li>
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </li>
                <li class="text-[#1A1A1A] font-medium line-clamp-1" aria-current="page"><?php echo esc_html($kunnskapskilde_navn); ?></li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-6 mb-10">
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-[#1A1A1A] mb-1"><?php echo esc_html($kunnskapskilde_navn); ?><?php echo bimverdi_admin_id_badge(); ?></h1>
                <?php if ($utgiver): ?>
                <p class="text-[#5A5A5A]"><?php echo esc_html($utgiver); ?><?php if ($utgivelsesaar): ?> - <?php echo esc_html($utgivelsesaar); ?><?php endif; ?></p>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-3 flex-shrink-0">
                <?php if ($can_edit): ?>
                <?php bimverdi_button([
                    'text' => 'Rediger',
                    'variant' => 'secondary',
                    'icon' => 'square-pen',
                    'href' => bimverdi_minside_url('kunnskapskilder/rediger', ['kunnskapskilde_id' => get_the_ID()])
                ]); ?>
                <?php endif; ?>
                <?php if ($ekstern_lenke): ?>
                <?php bimverdi_button([
                    'text' => 'Åpne kilde',
                    'variant' => 'primary',
                    'icon' => 'external-link',
                    'href' => $ekstern_lenke,
                    'target' => '_blank'
                ]); ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Two-Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

            <!-- Left Column: Main Content -->
            <div class="lg:col-span-2 space-y-10">

                <!-- Beskrivelse Section -->
                <section>
                    <h2 class="text-lg font-bold text-[#1A1A1A] mb-4">Om denne kilden</h2>

                    <?php if ($kort_beskrivelse): ?>
                    <p class="text-[#5A5A5A] text-lg mb-4"><?php echo esc_html($kort_beskrivelse); ?></p>
                    <?php endif; ?>

                    <?php if ($detaljert_beskrivelse): ?>
                    <div class="prose prose-sm max-w-none text-[#5A5A5A]">
                        <?php echo wpautop($detaljert_beskrivelse); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Tags -->
                    <?php if (!empty($temagruppe_terms) || !empty($kategori_terms)): ?>
                    <div class="flex flex-wrap gap-2 pt-6 mt-6 border-t border-[#E5E0D8]">
                        <?php if (!empty($temagruppe_terms)): ?>
                            <?php foreach ($temagruppe_terms as $term): ?>
                            <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-3 py-1.5 rounded">
                                <?php echo esc_html($term->name); ?>
                            </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (!empty($kategori_terms)): ?>
                            <?php foreach ($kategori_terms as $term): ?>
                            <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-3 py-1.5 rounded">
                                <?php echo esc_html($term->name); ?>
                            </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </section>

                <!-- Detaljer Section (Definition List) -->
                <section class="border-t border-[#E5E0D8] pt-10">
                    <h2 class="text-lg font-bold text-[#1A1A1A] mb-6">Detaljer</h2>

                    <dl class="space-y-0 divide-y divide-[#E5E0D8]">
                        <!-- Utgiver -->
                        <?php if ($utgiver): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Utgiver</dt>
                            <dd class="text-sm text-[#1A1A1A]"><?php echo esc_html($utgiver); ?></dd>
                        </div>
                        <?php endif; ?>

                        <!-- År -->
                        <?php if ($utgivelsesaar): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">År (antatt)</dt>
                            <dd class="text-sm text-[#1A1A1A]"><?php
                                $aar_display = [
                                    'eldre' => 'Eldre enn 2022',
                                ];
                                echo esc_html($aar_display[$utgivelsesaar] ?? $utgivelsesaar);
                            ?></dd>
                        </div>
                        <?php endif; ?>

                        <!-- Geografisk gyldighet -->
                        <?php if ($geografisk_gyldighet): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Geografisk gyldighet</dt>
                            <dd class="text-sm text-[#1A1A1A]"><?php echo esc_html($geo_labels[$geografisk_gyldighet] ?? $geografisk_gyldighet); ?></dd>
                        </div>
                        <?php endif; ?>

                        <!-- Dataformat -->
                        <?php if ($dataformat): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Dataformat</dt>
                            <dd class="text-sm text-[#1A1A1A]"><?php echo esc_html($dataformat_labels[$dataformat] ?? $dataformat); ?></dd>
                        </div>
                        <?php endif; ?>

                        <!-- Versjon -->
                        <?php if ($versjon): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Versjon</dt>
                            <dd class="text-sm text-[#1A1A1A]"><?php echo esc_html($versjon); ?></dd>
                        </div>
                        <?php endif; ?>

                        <!-- Tilgang -->
                        <?php if ($tilgang && $tilgang !== 'ukjent'): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Tilgang</dt>
                            <dd class="text-sm text-[#1A1A1A]"><?php
                                $tilgang_labels = [
                                    'gratis' => 'Gratis',
                                    'betalt' => 'Betalt',
                                    'abonnement' => 'Abonnement',
                                    'ukjent' => 'Ukjent',
                                ];
                                echo esc_html($tilgang_labels[$tilgang] ?? $tilgang);
                            ?></dd>
                        </div>
                        <?php endif; ?>

                        <!-- Lovpålagte standarder -->
                        <?php if ($ant_lovpalagte || $lovpalagte_standarder): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Lovpålagte standarder</dt>
                            <dd class="text-sm text-[#1A1A1A]">
                                <?php if ($ant_lovpalagte): ?>
                                    <span class="font-medium"><?php echo esc_html($ant_lovpalagte); ?></span>
                                <?php endif; ?>
                                <?php if ($lovpalagte_standarder): ?>
                                    <?php if ($ant_lovpalagte): ?><br><?php endif; ?>
                                    <?php echo esc_html($lovpalagte_standarder); ?>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <?php endif; ?>

                        <!-- Anbefalte standarder -->
                        <?php if ($ant_anbefalte || $anbefalte_standarder): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Anbefalte standarder</dt>
                            <dd class="text-sm text-[#1A1A1A]">
                                <?php if ($ant_anbefalte): ?>
                                    <span class="font-medium"><?php echo esc_html($ant_anbefalte); ?></span>
                                <?php endif; ?>
                                <?php if ($anbefalte_standarder): ?>
                                    <?php if ($ant_anbefalte): ?><br><?php endif; ?>
                                    <?php echo esc_html($anbefalte_standarder); ?>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <?php endif; ?>

                        <!-- Språk -->
                        <?php if ($spraak): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Språk</dt>
                            <dd class="text-sm text-[#1A1A1A]"><?php echo esc_html(isset($spraak_labels[$spraak]) ? $spraak_labels[$spraak] : $spraak); ?></dd>
                        </div>
                        <?php endif; ?>

                        <!-- Kildetype -->
                        <?php if ($kildetype): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Kildetype</dt>
                            <dd class="text-sm">
                                <span class="inline-block text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A] px-3 py-1.5 rounded">
                                    <?php echo esc_html(isset($kildetype_labels[$kildetype]) ? $kildetype_labels[$kildetype] : $kildetype); ?>
                                </span>
                            </dd>
                        </div>
                        <?php endif; ?>

                        <!-- Ekstern lenke -->
                        <?php if ($ekstern_lenke): ?>
                        <div class="grid grid-cols-2 py-6 gap-4">
                            <dt class="text-sm text-[#5A5A5A]">Ekstern lenke</dt>
                            <dd class="text-sm">
                                <a href="<?php echo esc_url($ekstern_lenke); ?>"
                                   target="_blank"
                                   rel="noopener"
                                   class="text-[#1A1A1A] hover:underline inline-flex items-center gap-1">
                                    <?php echo esc_html(parse_url($ekstern_lenke, PHP_URL_HOST) ?: $ekstern_lenke); ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                </a>
                            </dd>
                        </div>
                        <?php endif; ?>

                    </dl>
                </section>

            </div>

            <!-- Right Column: Sidebar -->
            <div class="lg:col-span-1 space-y-6">

                <!-- STATUS Section -->
                <section class="bg-[#F7F5EF] rounded-lg p-5">
                    <h3 class="text-xs font-bold text-[#5A5A5A] uppercase tracking-wider mb-6">Status</h3>

                    <dl class="space-y-6">
                        <div class="flex justify-between items-center">
                            <dt class="text-sm text-[#5A5A5A]">Status</dt>
                            <dd>
                                <?php
                                $post_status = get_post_status();
                                $status_label = $post_status === 'publish' ? 'Publisert' : ($post_status === 'pending' ? 'Venter' : 'Kladd');
                                $status_class = $post_status === 'publish' ? 'bg-[#DCFCE7] text-[#166534]' : 'bg-[#FEF9C3] text-[#854D0E]';
                                ?>
                                <span class="inline-block text-xs font-medium <?php echo $status_class; ?> px-2.5 py-1 rounded">
                                    <?php echo $status_label; ?>
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm text-[#5A5A5A] flex items-center gap-2 mb-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                Registrert
                            </dt>
                            <dd class="text-sm text-[#1A1A1A] pl-[22px]"><?php echo esc_html($kilde_created); ?></dd>
                        </div>

                        <div>
                            <dt class="text-sm text-[#5A5A5A] flex items-center gap-2 mb-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                Sist oppdatert
                            </dt>
                            <dd class="text-sm text-[#1A1A1A] pl-[22px]"><?php echo esc_html($kilde_updated); ?></dd>
                        </div>

                        <?php
                        $bedrift = $tilknyttet_bedrift ? get_post($tilknyttet_bedrift) : null;
                        if ($bedrift):
                        ?>
                        <div>
                            <dt class="text-sm text-[#5A5A5A] flex items-center gap-2 mb-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>
                                Registrert av
                            </dt>
                            <dd class="text-sm text-[#1A1A1A] pl-[22px]"><?php echo esc_html($bedrift->post_title); ?></dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </section>

                <!-- SNARVEIER Section -->
                <section class="bg-[#F7F5EF] rounded-lg p-5">
                    <h3 class="text-xs font-bold text-[#5A5A5A] uppercase tracking-wider mb-4">Snarveier</h3>

                    <nav class="space-y-0 divide-y divide-[#E5E0D8]">
                        <?php if ($ekstern_lenke): ?>
                        <a href="<?php echo esc_url($ekstern_lenke); ?>"
                           target="_blank"
                           rel="noopener"
                           class="block py-3 text-sm text-[#1A1A1A] hover:text-[#F97316] transition-colors">
                            Åpne ekstern kilde
                        </a>
                        <?php endif; ?>


                        <a href="<?php echo esc_url(home_url('/kunnskapskilder/')); ?>"
                           class="block py-3 text-sm text-[#1A1A1A] hover:text-[#F97316] transition-colors">
                            Tilbake til katalogen
                        </a>
                    </nav>
                </section>

            </div>

        </div>

    </div>
</main>

<?php
endwhile;
endif;

get_footer();
?>
