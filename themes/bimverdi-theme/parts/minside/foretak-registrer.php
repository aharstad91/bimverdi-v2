<?php
/**
 * Part: Registrer foretak
 *
 * Skjema for registrering av nytt foretak via Gravity Forms med BRreg autocomplete.
 * Brukes på /min-side/foretak/registrer/ og /min-side/registrer-foretak/
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

<!-- Form Container (960px centered per UI Contract) -->
<div class="max-w-3xl mx-auto">

    <!-- Info Section -->
    <div class="mb-8 p-4 bg-[#F5F5F4] rounded-lg">
        <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FF8B5E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            <p class="text-sm text-[#57534E]">
                <strong class="text-[#111827]">Tips:</strong> Start å skrive foretaksnavnet, så henter vi automatisk informasjon fra Brønnøysundregistrene.
            </p>
        </div>
    </div>

    <!-- Gravity Form -->
    <div class="bg-white rounded-lg border border-[#E7E5E4] p-6">
        <?php
        if (function_exists('gravity_form')) {
            // Form ID 2 = [Bruker] - Registrering av foretak
            gravity_form(2, false, false, false, null, true, 12);
        } else {
            ?>
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                Gravity Forms er ikke aktivert. Kontakt administrator.
            </div>
            <?php
        }
        ?>
    </div>

    <!-- Help Link -->
    <div class="mt-6 text-center text-sm text-[#57534E]">
        <p>Trenger du hjelp? Kontakt oss på
            <a href="mailto:post@bimverdi.no" class="text-[#FF8B5E] hover:underline">post@bimverdi.no</a>
        </p>
    </div>

</div>

<style>
/* Gravity Forms styling for this page */
.gform_wrapper .gform_button,
.gform_wrapper input[type="submit"] {
    background: #FF8B5E !important;
    color: white !important;
    padding: 0.875rem 2rem !important;
    border: none !important;
    border-radius: 0.5rem !important;
    font-size: 1rem !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    width: 100% !important;
    margin-top: 1rem !important;
}

.gform_wrapper .gform_button:hover,
.gform_wrapper input[type="submit"]:hover {
    background: #e07a52 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(255, 139, 94, 0.3) !important;
}

.gform_wrapper .gfield_label {
    font-weight: 600 !important;
    color: #111827 !important;
    margin-bottom: 0.5rem !important;
}

.gform_wrapper input[type="text"],
.gform_wrapper input[type="url"],
.gform_wrapper input[type="email"],
.gform_wrapper input[type="tel"],
.gform_wrapper textarea,
.gform_wrapper select {
    width: 100% !important;
    padding: 0.75rem 1rem !important;
    border: 1px solid #E7E5E4 !important;
    border-radius: 0.5rem !important;
    font-size: 1rem !important;
    transition: border-color 0.2s, box-shadow 0.2s !important;
}

.gform_wrapper input[type="text"]:focus,
.gform_wrapper input[type="url"]:focus,
.gform_wrapper input[type="email"]:focus,
.gform_wrapper input[type="tel"]:focus,
.gform_wrapper textarea:focus,
.gform_wrapper select:focus {
    border-color: #FF8B5E !important;
    box-shadow: 0 0 0 3px rgba(255, 139, 94, 0.1) !important;
    outline: none !important;
}

.gform_wrapper .gfield_description {
    font-size: 0.875rem !important;
    color: #57534E !important;
    margin-top: 0.25rem !important;
}

.gform_wrapper .gform_footer {
    margin-top: 1.5rem !important;
    padding-top: 1rem !important;
    border-top: 1px solid #E7E5E4 !important;
}
</style>
