<?php
/**
 * Part: Registrer foretak (dedikert side)
 *
 * Wrapper med breadcrumb + page-header. Selve skjemaet ligger i
 * parts/minside/foretak-registrer-form.php (gjenbrukes inline på dashboard).
 *
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Check if user already has a company - redirect to foretak page
$existing_foretak_id = bimverdi_user_has_foretak($user_id);
if ($existing_foretak_id && get_post_status($existing_foretak_id) === 'publish') {
    wp_redirect(home_url('/min-side/foretak/'));
    exit;
}

// Hvis bruker har valgt foretak via Brreg-søk på dashboard og lander her
// direkte (f.eks. via gammel lenke), pre-fyll skjemaet med samme data.
$preselected = null;
if (function_exists('bimverdi_get_bruker_foretak')) {
    $bruker_foretak = bimverdi_get_bruker_foretak($user_id);
    if ($bruker_foretak && !empty($bruker_foretak['orgnr']) && !empty($bruker_foretak['navn'])) {
        $preselected = $bruker_foretak;
    }
}
?>

<!-- Breadcrumb -->
<nav class="mb-6" aria-label="Brødsmulesti">
    <ol class="flex items-center gap-2 text-sm text-[#57534E]">
        <li>
            <a href="<?php echo esc_url(home_url('/min-side/')); ?>" class="hover:text-[#111827] transition-colors">
                Min side
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li class="text-[#111827] font-medium" aria-current="page">Registrer foretak</li>
    </ol>
</nav>

<!-- Page Header -->
<?php
get_template_part('parts/components/page-header', null, [
    'title' => 'Registrer foretak',
    'description' => 'Koble ditt foretak til BIM Verdi nettverksportalen'
]);
?>

<?php
get_template_part('parts/minside/foretak-registrer-form', null, [
    'preselected' => $preselected,
]);
