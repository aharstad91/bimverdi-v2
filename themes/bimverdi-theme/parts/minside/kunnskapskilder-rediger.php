<?php
/**
 * Part: Rediger kunnskapskilde
 *
 * Skjema for redigering av eksisterende kunnskapskilde.
 * Brukes på /min-side/kunnskapskilder/rediger/?kunnskapskilde_id=XX
 *
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

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
if ($kilde_author == $user_id) {
    $can_edit = true;
}
if ($registrert_av && $registrert_av == $user_id) {
    $can_edit = true;
}
if ($company_id && $kilde_company && $kilde_company == $company_id) {
    $can_edit = true;
}
if (current_user_can('manage_options')) {
    $can_edit = true;
}

if (!$can_edit) {
    wp_redirect(bimverdi_minside_url('kunnskapskilder'));
    exit;
}

// Get kunnskapskilde data
$kilde_navn = get_field('kunnskapskilde_navn', $kunnskapskilde_id) ?: $kunnskapskilde->post_title;
$kilde_type = get_field('kildetype', $kunnskapskilde_id);
$kilde_status = get_post_status($kunnskapskilde_id);
$kilde_updated = get_the_modified_date('d.m.Y', $kunnskapskilde_id);

// Kildetype labels
$kildetype_labels = [
    'standard' => 'Standard',
    'veileder' => 'Veileder',
    'mal' => 'Mal/Template',
    'forskningsrapport' => 'Forskningsrapport',
    'casestudie' => 'Casestudie',
    'opplaering' => 'Opplæring',
    'dokumentasjon' => 'Dokumentasjon',
    'nettressurs' => 'Nettressurs',
    'annet' => 'Annet'
];
$kilde_type_label = isset($kildetype_labels[$kilde_type]) ? $kildetype_labels[$kilde_type] : ($kilde_type ?: 'Ukategorisert');
?>

<!-- Breadcrumb -->
<nav class="mb-6" aria-label="Brødsmulesti">
    <ol class="flex items-center gap-2 text-sm text-[#5A5A5A]">
        <li>
            <a href="<?php echo esc_url(bimverdi_minside_url('')); ?>" class="hover:text-[#1A1A1A] transition-colors">
                Min side
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li>
            <a href="<?php echo esc_url(bimverdi_minside_url('kunnskapskilder')); ?>" class="hover:text-[#1A1A1A] transition-colors">
                Kunnskapskilder
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li class="text-[#1A1A1A] font-medium" aria-current="page"><?php echo esc_html($kilde_navn); ?></li>
    </ol>
</nav>

<!-- Page Header -->
<?php
get_template_part('parts/components/page-header', null, [
    'title' => 'Rediger kunnskapskilde',
    'description' => 'Oppdater informasjon om ' . esc_html($kilde_navn)
]);
?>

<!-- Form Container (960px centered per UI-CONTRACT.md) -->
<div class="max-w-3xl mx-auto">

    <!-- Kunnskapskilde Info Badge -->
    <div class="mb-8 p-4 bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg flex items-center justify-between">
        <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
            </svg>
            <div>
                <p class="font-semibold text-[#1A1A1A]"><?php echo esc_html($kilde_navn); ?></p>
                <p class="text-sm text-[#5A5A5A]"><?php echo esc_html($kilde_type_label); ?></p>
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
            <p class="text-xs text-[#5A5A5A] mt-1">Oppdatert <?php echo $kilde_updated; ?></p>
        </div>
    </div>

    <!-- Gravity Form -->
    <div class="bg-white border border-[#E5E0D5] rounded-lg p-8">
        <h2 class="text-xl font-bold text-[#1A1A1A] mb-6 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
                <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.375 2.625a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4Z"/>
            </svg>
            Oppdater kunnskapskilde
        </h2>

        <?php
        // Display Gravity Form with dynamically created form ID
        $form_id = function_exists('bim_verdi_get_kunnskapskilde_form_id')
            ? bim_verdi_get_kunnskapskilde_form_id()
            : null;

        if ($form_id && function_exists('gravity_form')) {
            gravity_form($form_id, false, false, false, array(
                'kunnskapskilde_id' => $kunnskapskilde_id,
            ), true);
        } else {
            echo '<div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">';
            echo '<strong>Feil:</strong> Skjema er ikke tilgjengelig. Vennligst kontakt administrator.';
            echo '</div>';
        }
        ?>
    </div>

    <!-- Info Section - No Delete for Kunnskapskilder per requirements -->
    <div class="mt-12 pt-8 border-t border-[#D6D1C6]">
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
    <div class="mt-8 pt-6 border-t border-[#E5E0D5]">
        <a href="<?php echo esc_url(bimverdi_minside_url('kunnskapskilder')); ?>" class="inline-flex items-center gap-2 text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Tilbake til kunnskapskilder
        </a>
    </div>
</div>
