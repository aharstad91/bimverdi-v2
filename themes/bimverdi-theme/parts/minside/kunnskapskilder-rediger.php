<?php
/**
 * Part: Rediger kunnskapskilde
 *
 * Plain HTML form for editing an existing kunnskapskilde.
 * Replaces Gravity Forms Forms #19-23.
 * Brukes på /min-side/kunnskapskilder/rediger/?kunnskapskilde_id=XX
 *
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_id = get_user_meta($user_id, 'bimverdi_company_id', true)
           ?: get_user_meta($user_id, 'bim_verdi_company_id', true);

// Get kunnskapskilde ID from URL parameter
$kunnskapskilde_id = isset($_GET['kunnskapskilde_id']) ? intval($_GET['kunnskapskilde_id']) : 0;

// Redirect if no ID
if (!$kunnskapskilde_id) {
    wp_redirect(bimverdi_minside_url('kunnskapskilder'));
    exit;
}

// Get the kunnskapskilde post
$kunnskapskilde = get_post($kunnskapskilde_id);

// Verify it exists
if (!$kunnskapskilde || $kunnskapskilde->post_type !== 'kunnskapskilde') {
    wp_redirect(bimverdi_minside_url('kunnskapskilder'));
    exit;
}

// Check if user has permission to edit
$kilde_author = $kunnskapskilde->post_author;
$registrert_av = get_field('registrert_av', $kunnskapskilde_id);
$kilde_company = get_field('tilknyttet_bedrift', $kunnskapskilde_id);

$can_edit = false;
if ($kilde_author == $user_id) $can_edit = true;
if ($registrert_av && $registrert_av == $user_id) $can_edit = true;
if ($company_id && $kilde_company && $kilde_company == $company_id) $can_edit = true;
if (current_user_can('manage_options')) $can_edit = true;

if (!$can_edit) {
    wp_redirect(bimverdi_minside_url('kunnskapskilder'));
    exit;
}

// Get kunnskapskilde data from ACF fields
$kilde_navn            = get_field('kunnskapskilde_navn', $kunnskapskilde_id) ?: $kunnskapskilde->post_title;
$kort_beskrivelse      = get_field('kort_beskrivelse', $kunnskapskilde_id);
$detaljert_beskrivelse = get_field('detaljert_beskrivelse', $kunnskapskilde_id);
$ekstern_lenke         = get_field('ekstern_lenke', $kunnskapskilde_id);
$utgiver               = get_field('utgiver', $kunnskapskilde_id);
$current_spraak        = get_field('spraak', $kunnskapskilde_id);
$versjon               = get_field('versjon', $kunnskapskilde_id);
$current_utgivelsesaar = get_field('utgivelsesaar', $kunnskapskilde_id);
$current_tilgang       = get_field('tilgang', $kunnskapskilde_id);
$current_kildetype     = get_field('kildetype', $kunnskapskilde_id);
$current_geo           = get_field('geografisk_gyldighet', $kunnskapskilde_id);
$current_dataformat    = get_field('dataformat', $kunnskapskilde_id);
$ant_lovpalagte        = get_field('ant_lovpalagte_standarder', $kunnskapskilde_id);
$lovpalagte            = get_field('lovpalagte_standarder', $kunnskapskilde_id);
$ant_anbefalte         = get_field('ant_anbefalte_standarder', $kunnskapskilde_id);
$anbefalte             = get_field('anbefalte_standarder', $kunnskapskilde_id);

$kilde_status  = get_post_status($kunnskapskilde_id);
$kilde_updated = get_the_modified_date('d.m.Y', $kunnskapskilde_id);

// Kildetype labels for info badge
$kildetype_labels = [
    'standard'          => 'Standard',
    'veiledning'        => 'Veiledning',
    'forskrift_norsk'   => 'Forskrift (norsk)',
    'forordning_eu'     => 'Forordning (EU)',
    'mal'               => 'Mal/Template',
    'forskningsrapport' => 'Forskningsrapport',
    'casestudie'        => 'Casestudie',
    'opplaering'        => 'Opplæring',
    'dokumentasjon'     => 'Dokumentasjon',
    'nettressurs'       => 'Nettressurs',
    'annet'             => 'Annet',
];
$kilde_type_label = $kildetype_labels[$current_kildetype] ?? ($current_kildetype ?: 'Ukategorisert');

// Error handling
$error = isset($_GET['bv_error']) ? sanitize_text_field($_GET['bv_error']) : '';
$error_messages = [
    'nonce'             => 'Skjemaet utløp. Vennligst prøv igjen.',
    'rate_limit'        => 'For mange forsøk. Vennligst vent litt.',
    'missing_name'      => 'Navn på kunnskapskilde er påkrevd.',
    'missing_url'       => 'Ekstern lenke er påkrevd.',
    'missing_kildetype' => 'Du må velge kildetype.',
    'url_duplicate'     => 'Denne lenken er allerede registrert. Vennligst bruk en annen URL.',
    'not_found'         => 'Kunnskapskilden ble ikke funnet.',
    'permission'        => 'Du har ikke tilgang til å redigere denne kunnskapskilden.',
    'system'            => 'En teknisk feil oppstod. Vennligst prøv igjen.',
];
$error_text = $error_messages[$error] ?? '';

// Select options (same as registrer)
$kildetype_options = [
    'standard'         => 'Standard (ISO, NS, etc.)',
    'veiledning'       => 'Veiledning/metodikk',
    'forskrift_norsk'  => 'Forskrift (norsk lov)',
    'forordning_eu'    => 'Forordning (EU/EØS)',
    'mal'              => 'Mal/Template',
    'forskningsrapport'=> 'Forskningsrapport',
    'casestudie'       => 'Casestudie',
    'opplaering'       => 'Opplæringsmateriell',
    'dokumentasjon'    => 'Verktøydokumentasjon',
    'nettressurs'      => 'Nettressurs/Database',
    'annet'            => 'Annet',
];

$spraak_options = [
    'norsk'       => 'Norsk',
    'engelsk'     => 'Engelsk',
    'svensk'      => 'Svensk',
    'dansk'       => 'Dansk',
    'flerspraklig'=> 'Flerspråklig',
    'annet'       => 'Annet',
];

$geo_options = [
    'nasjonalt'      => 'Nasjonalt/Norsk',
    'nordisk'        => 'Nordisk',
    'europeisk'      => 'Europeisk',
    'internasjonalt' => 'Internasjonalt',
    'annet'          => 'Annet',
];

$dataformat_options = [
    'pdf'        => 'PDF-dokument',
    'web_aapent' => 'Web-innhold - åpent',
    'web_lukket' => 'Web-innhold - lukket/betalt',
    'api'        => 'Åpent API',
    'ifc'        => 'IFC-fil',
    'database'   => 'Database/register',
    'annet'      => 'Annet',
];

$tilgang_options = [
    'gratis'     => 'Gratis',
    'betalt'     => 'Betalt',
    'abonnement' => 'Abonnement',
    'ukjent'     => 'Ukjent',
];

$aar_options = ['2026', '2025', '2024', '2023', '2022', 'Eldre enn 2022'];

// Get taxonomy terms for checkboxes
$temagrupper = get_terms(['taxonomy' => 'temagruppe', 'hide_empty' => false]);
if (is_wp_error($temagrupper)) $temagrupper = [];

$kategorier = get_terms(['taxonomy' => 'kunnskapskildekategori', 'hide_empty' => false]);
if (is_wp_error($kategorier)) $kategorier = [];

// Get current taxonomy terms for this kunnskapskilde
$current_temagrupper = wp_get_post_terms($kunnskapskilde_id, 'temagruppe', ['fields' => 'slugs']);
if (is_wp_error($current_temagrupper)) $current_temagrupper = [];

$current_kategorier = wp_get_post_terms($kunnskapskilde_id, 'kunnskapskildekategori', ['fields' => 'slugs']);
if (is_wp_error($current_kategorier)) $current_kategorier = [];
?>

<!-- Breadcrumb -->
<nav class="mb-6" aria-label="Brødsmulesti">
    <ol class="flex items-center gap-2 text-sm text-[#57534E]">
        <li><a href="<?php echo esc_url(bimverdi_minside_url('')); ?>" class="hover:text-[#111827] transition-colors">Min side</a></li>
        <li><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></li>
        <li><a href="<?php echo esc_url(bimverdi_minside_url('kunnskapskilder')); ?>" class="hover:text-[#111827] transition-colors">Kunnskapskilder</a></li>
        <li><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></li>
        <li class="text-[#111827] font-medium" aria-current="page"><?php echo esc_html($kilde_navn); ?></li>
    </ol>
</nav>

<?php get_template_part('parts/components/page-header', null, [
    'title' => 'Rediger kunnskapskilde',
    'description' => 'Oppdater informasjon om ' . esc_html($kilde_navn)
]); ?>

<div class="max-w-3xl mx-auto">

    <!-- Kunnskapskilde Info Badge -->
    <div class="mb-8 p-4 bg-[#F5F5F4] border border-[#E7E5E4] rounded-lg flex items-center justify-between">
        <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#57534E]">
                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
            </svg>
            <div>
                <p class="font-semibold text-[#111827]"><?php echo esc_html($kilde_navn); ?></p>
                <p class="text-sm text-[#57534E]"><?php echo esc_html($kilde_type_label); ?></p>
            </div>
        </div>
        <div class="text-right">
            <?php
            $status_label = $kilde_status === 'publish' ? 'Publisert' : ($kilde_status === 'pending' ? 'Venter godkjenning' : 'Utkast');
            $status_class = $kilde_status === 'publish' ? 'bg-green-100 text-green-800' : ($kilde_status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-800');
            ?>
            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?php echo $status_class; ?>">
                <?php echo $status_label; ?>
            </span>
            <p class="text-xs text-[#57534E] mt-1">Oppdatert <?php echo $kilde_updated; ?></p>
        </div>
    </div>

    <?php if ($error_text): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <p class="text-red-800 text-sm"><?php echo esc_html($error_text); ?></p>
    </div>
    <?php endif; ?>

    <form method="post" action="" class="space-y-6">
        <?php wp_nonce_field('bimverdi_edit_kunnskapskilde'); ?>
        <input type="hidden" name="bimverdi_edit_kunnskapskilde" value="1">
        <input type="hidden" name="kunnskapskilde_id" value="<?php echo esc_attr($kunnskapskilde_id); ?>">
        <div style="position:absolute;left:-9999px;" aria-hidden="true">
            <input type="text" name="bv_website_url" tabindex="-1" autocomplete="off">
        </div>

        <h2 class="text-lg font-semibold text-[#111827]">Grunnleggende informasjon</h2>

        <!-- Navn -->
        <div>
            <label for="kunnskapskilde_navn" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                Navn på kunnskapskilde <span class="text-red-500">*</span>
            </label>
            <input type="text" id="kunnskapskilde_navn" name="kunnskapskilde_navn" required maxlength="100"
                   value="<?php echo esc_attr($kilde_navn); ?>"
                   class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
        </div>

        <!-- Kort beskrivelse -->
        <div>
            <label for="kort_beskrivelse" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Kort beskrivelse</label>
            <textarea id="kort_beskrivelse" name="kort_beskrivelse" rows="3" maxlength="250"
                      placeholder="Beskriv kunnskapskilden kort (maks 250 tegn)"
                      class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent resize-y"><?php echo esc_textarea($kort_beskrivelse); ?></textarea>
        </div>

        <!-- Detaljert beskrivelse -->
        <div>
            <label for="detaljert_beskrivelse" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Detaljert beskrivelse</label>
            <textarea id="detaljert_beskrivelse" name="detaljert_beskrivelse" rows="5"
                      placeholder="Utfyllende beskrivelse (valgfritt)"
                      class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent resize-y"><?php echo esc_textarea($detaljert_beskrivelse); ?></textarea>
        </div>

        <!-- Ekstern lenke -->
        <div>
            <label for="ekstern_lenke" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                Ekstern lenke (URL) <span class="text-red-500">*</span>
            </label>
            <input type="url" id="ekstern_lenke" name="ekstern_lenke" required
                   value="<?php echo esc_attr($ekstern_lenke); ?>"
                   class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
            <p class="mt-1 text-xs text-[#888888]">Hver lenke kan kun registreres én gang.</p>
        </div>

        <hr class="border-[#E5E0D5]">
        <h2 class="text-lg font-semibold text-[#111827]">Klassifisering</h2>

        <!-- Kildetype -->
        <div>
            <label for="kildetype" class="block text-sm font-semibold text-[#1A1A1A] mb-2">
                Kildetype <span class="text-red-500">*</span>
            </label>
            <select id="kildetype" name="kildetype" required
                    class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] bg-white focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                <option value="">Velg kildetype</option>
                <?php foreach ($kildetype_options as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($current_kildetype, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <!-- Tilgang -->
            <div>
                <label for="tilgang" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Tilgang</label>
                <select id="tilgang" name="tilgang"
                        class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] bg-white focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    <option value="">Velg tilgangstype</option>
                    <?php foreach ($tilgang_options as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($current_tilgang, $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Språk -->
            <div>
                <label for="spraak" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Språk</label>
                <select id="spraak" name="spraak"
                        class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] bg-white focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    <option value="">Velg språk</option>
                    <?php foreach ($spraak_options as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($current_spraak, $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <!-- Geografisk gyldighet -->
            <div>
                <label for="geografisk_gyldighet" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Geografisk gyldighet</label>
                <select id="geografisk_gyldighet" name="geografisk_gyldighet"
                        class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] bg-white focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    <option value="">Velg gyldighet</option>
                    <?php foreach ($geo_options as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($current_geo, $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dataformat -->
            <div>
                <label for="dataformat" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Dataformat</label>
                <select id="dataformat" name="dataformat"
                        class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] bg-white focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    <option value="">Velg format</option>
                    <?php foreach ($dataformat_options as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($current_dataformat, $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <hr class="border-[#E5E0D5]">
        <h2 class="text-lg font-semibold text-[#111827]">Detaljer</h2>

        <div class="grid sm:grid-cols-3 gap-4">
            <!-- Utgiver -->
            <div>
                <label for="utgiver" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Utgiver</label>
                <input type="text" id="utgiver" name="utgiver"
                       value="<?php echo esc_attr($utgiver); ?>"
                       placeholder="F.eks. Standard Norge"
                       class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
            </div>

            <!-- Versjon -->
            <div>
                <label for="versjon" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Versjon</label>
                <input type="text" id="versjon" name="versjon"
                       value="<?php echo esc_attr($versjon); ?>"
                       placeholder="F.eks. 2.0"
                       class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
            </div>

            <!-- Utgivelsesår -->
            <div>
                <label for="utgivelsesaar" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Utgivelsesår</label>
                <select id="utgivelsesaar" name="utgivelsesaar"
                        class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] bg-white focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    <option value="">Velg år</option>
                    <?php foreach ($aar_options as $year):
                        $year_value = strtolower(str_replace(' ', '_', $year));
                    ?>
                    <option value="<?php echo esc_attr($year_value); ?>" <?php selected($current_utgivelsesaar, $year_value); ?>><?php echo esc_html($year); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <!-- Lovpålagte standarder -->
            <div>
                <label for="ant_lovpalagte_standarder" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Ant. lovpålagte standarder</label>
                <input type="number" id="ant_lovpalagte_standarder" name="ant_lovpalagte_standarder" min="0"
                       value="<?php echo esc_attr($ant_lovpalagte); ?>"
                       placeholder="0"
                       class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
            </div>
            <div>
                <label for="lovpalagte_standarder" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Lovpålagte standarder</label>
                <input type="text" id="lovpalagte_standarder" name="lovpalagte_standarder"
                       value="<?php echo esc_attr($lovpalagte); ?>"
                       placeholder="NS 3420, NS-EN 1990, ..."
                       class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <!-- Anbefalte standarder -->
            <div>
                <label for="ant_anbefalte_standarder" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Ant. anbefalte standarder</label>
                <input type="number" id="ant_anbefalte_standarder" name="ant_anbefalte_standarder" min="0"
                       value="<?php echo esc_attr($ant_anbefalte); ?>"
                       placeholder="0"
                       class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
            </div>
            <div>
                <label for="anbefalte_standarder" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Anbefalte standarder</label>
                <input type="text" id="anbefalte_standarder" name="anbefalte_standarder"
                       value="<?php echo esc_attr($anbefalte); ?>"
                       placeholder="ISO 16739, ISO 19650, ..."
                       class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
            </div>
        </div>

        <?php if (!empty($temagrupper)): ?>
        <hr class="border-[#E5E0D5]">
        <!-- Temagrupper -->
        <fieldset>
            <legend class="text-sm font-semibold text-[#1A1A1A] mb-1">Temagrupper</legend>
            <p class="text-xs text-[#888888] mb-3">Velg relevante temagrupper.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($temagrupper as $term): ?>
                <label class="flex items-start gap-3 p-3 rounded-lg border border-[#E5E0D5] hover:border-[#FF8B5E] hover:bg-[#FFF8F5] transition-colors cursor-pointer has-[:checked]:border-[#FF8B5E] has-[:checked]:bg-[#FFF8F5]">
                    <input type="checkbox" name="temagrupper[]" value="<?php echo esc_attr($term->slug); ?>"
                           <?php checked(in_array($term->slug, $current_temagrupper)); ?>
                           class="mt-0.5 w-4 h-4 rounded border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($term->name); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <?php endif; ?>

        <?php if (!empty($kategorier)): ?>
        <!-- Kategorier -->
        <fieldset>
            <legend class="text-sm font-semibold text-[#1A1A1A] mb-1">Kategorier</legend>
            <p class="text-xs text-[#888888] mb-3">Velg relevante kategorier.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($kategorier as $term): ?>
                <label class="flex items-start gap-3 p-3 rounded-lg border border-[#E5E0D5] hover:border-[#FF8B5E] hover:bg-[#FFF8F5] transition-colors cursor-pointer has-[:checked]:border-[#FF8B5E] has-[:checked]:bg-[#FFF8F5]">
                    <input type="checkbox" name="kategorier[]" value="<?php echo esc_attr($term->slug); ?>"
                           <?php checked(in_array($term->slug, $current_kategorier)); ?>
                           class="mt-0.5 w-4 h-4 rounded border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($term->name); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <?php endif; ?>

        <!-- Submit -->
        <div class="pt-4">
            <button type="submit"
                    class="bv-btn bv-btn--primary px-6 py-2.5 text-sm font-semibold rounded-lg inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Oppdater kunnskapskilde
            </button>
        </div>
    </form>

    <!-- Info Section - No Delete for Kunnskapskilder -->
    <div class="mt-12 pt-8 border-t border-[#E7E5E4]">
        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4"/>
                    <path d="M12 8h.01"/>
                </svg>
                <div>
                    <p class="font-medium text-blue-900">Sletting ikke tilgjengelig</p>
                    <p class="text-sm text-blue-800 mt-1">
                        Kunnskapskilder kan ikke slettes av brukere. Kontakt administrator hvis du ønsker å fjerne denne posten.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Link -->
    <div class="mt-8 pt-6 border-t border-[#E7E5E4]">
        <a href="<?php echo esc_url(bimverdi_minside_url('kunnskapskilder')); ?>" class="inline-flex items-center gap-2 text-[#57534E] hover:text-[#111827] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Tilbake til kunnskapskilder
        </a>
    </div>
</div>
