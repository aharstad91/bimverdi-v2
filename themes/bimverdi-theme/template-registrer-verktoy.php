<?php
/**
 * Template Name: Registrer Verktøy
 * 
 * Tool registration page with Gravity Forms
 * Implements Variant B (Dividers/Whitespace) design from UI-CONTRACT.md
 * Allows company owners to register and share tools/resources
 * 
 * @package BimVerdi_Theme
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

// Redirect if not connected to a company
if (!$company_id) {
    wp_redirect(home_url('/min-side/'));
    exit;
}

get_header();

$user_roles = $current_user->roles;
$is_company_owner = in_array('company_owner', $user_roles) || current_user_can('manage_options');
$company = $company_id ? get_post($company_id) : null;

// Start Min Side layout
get_template_part('template-parts/minside-layout-start', null, array(
    'current_page' => 'verktoy',
    'page_title' => 'Registrer Verktøy',
    'page_icon' => 'plus',
    'page_description' => 'Del verktøy og ressurser med BIM Verdi-nettverket',
));
?>

<!-- PageHeader (Variant B Style) -->
<div class="mb-12">
    <div class="flex flex-col md:flex-row md:items-start justify-between gap-6 md:gap-8">
        <div class="flex-1">
            <h1 class="text-4xl font-bold tracking-tight text-[#1A1A1A] mb-2">Registrer Verktøy</h1>
            <p class="text-lg text-[#5A5A5A] max-w-2xl">
                Del verktøy, programvare og ressurser med BIM Verdi-nettverket
            </p>
        </div>
        <?php if ($company): ?>
        <div class="flex-shrink-0">
            <div class="bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg p-4">
                <div class="text-xs font-bold uppercase tracking-wider text-[#5A5A5A] mb-2">Din bedrift</div>
                <div class="text-lg font-semibold text-[#1A1A1A]"><?php echo esc_html($company->post_title); ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Form Section (960px centered layout per UI-CONTRACT.md section 2.2) -->
<div class="max-w-2xl mx-auto mb-16">
    <h2 class="text-xl font-bold text-[#1A1A1A] mb-8">Fyll inn verktøydetaljer</h2>

    <div class="gform-wrapper-register">
        <?php
        // Display Gravity Form ID 1 [Bruker] - Registrering av verktøy
        if (function_exists('gravity_form')) {
            gravity_form(1, false, false, false, null, false); // AJAX disabled
        } else {
            echo '<wa-alert variant="error" open>';
            echo '<wa-icon slot="icon" name="exclamation-circle" library="fa"></wa-icon>';
            echo '<strong>Feil:</strong> Skjema er ikke tilgjengelig. Vennligst kontakt administrator.';
            echo '</wa-alert>';
        }
        ?>
    </div>
</div>

<!-- Information Section (Borderless with divider) -->
<div class="max-w-2xl mx-auto border-t border-[#D6D1C6] pt-12">
    <h3 class="text-sm font-bold text-[#1A1A1A] uppercase tracking-wider mb-6 flex items-center gap-2">
        <wa-icon name="circle-info" library="fa"></wa-icon>
        Om verktøyregistrering
    </h3>
    
    <div class="space-y-4">
        <div class="flex gap-4">
            <div class="flex-shrink-0">
                <wa-icon name="check-circle" library="fa" style="color: #B3DB87; font-size: 1.25rem;"></wa-icon>
            </div>
            <div>
                <div class="font-semibold text-[#1A1A1A] mb-1">Registrer verktøy som din bedrift tilbyr</div>
                <p class="text-[#5A5A5A] text-sm">
                    Leggi til programvare, plugins, eller andre ressurser som du bruker eller tilbyr
                </p>
            </div>
        </div>

        <div class="flex gap-4">
            <div class="flex-shrink-0">
                <wa-icon name="check-circle" library="fa" style="color: #B3DB87; font-size: 1.25rem;"></wa-icon>
            </div>
            <div>
                <div class="font-semibold text-[#1A1A1A] mb-1">Legg til detaljer</div>
                <p class="text-[#5A5A5A] text-sm">
                    Beskrivelse, link, pris, og hvilke plattformer verktøyet støtter
                </p>
            </div>
        </div>

        <div class="flex gap-4">
            <div class="flex-shrink-0">
                <wa-icon name="check-circle" library="fa" style="color: #B3DB87; font-size: 1.25rem;"></wa-icon>
            </div>
            <div>
                <div class="font-semibold text-[#1A1A1A] mb-1">Synlig for alle medlemmer</div>
                <p class="text-[#5A5A5A] text-sm">
                    Verktøyet blir vist i verktøykatalogen og mine verktøy for andre medlemmer
                </p>
            </div>
        </div>

        <div class="flex gap-4">
            <div class="flex-shrink-0">
                <wa-icon name="check-circle" library="fa" style="color: #B3DB87; font-size: 1.25rem;"></wa-icon>
            </div>
            <div>
                <div class="font-semibold text-[#1A1A1A] mb-1">Administrer senere</div>
                <p class="text-[#5A5A5A] text-sm">
                    Rediger, oppdater eller slett verktøy fra din bedriftsprofil når som helst
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Back Link -->
<div class="text-center mt-12">
    <a href="<?php echo esc_url(home_url('/min-side/')); ?>" class="inline-flex items-center text-[#FF8B5E] hover:text-[#1A1A1A] transition-colors font-medium text-sm">
        <wa-icon name="chevron-left" library="fa" style="font-size: 0.875rem; margin-right: 0.5rem;"></wa-icon>
        Tilbake til Min Side
    </a>
</div>

<style>
/* Gravity Form styling for Variant B (Dividers/Whitespace) - Create Flow */
.gform-wrapper-register .gform_wrapper {
    background: transparent;
    padding: 0;
    border: none;
    box-shadow: none;
}

.gform-wrapper-register .gform_wrapper input[type="text"],
.gform-wrapper-register .gform_wrapper input[type="email"],
.gform-wrapper-register .gform_wrapper input[type="url"],
.gform-wrapper-register .gform_wrapper input[type="number"],
.gform-wrapper-register .gform_wrapper textarea,
.gform-wrapper-register .gform_wrapper select {
    border: 1px solid #D6D1C6;
    border-radius: 0.5rem;
    padding: 0.75rem;
    font-size: 1rem;
    color: #1A1A1A;
    background: white;
    width: 100%;
}

.gform-wrapper-register .gform_wrapper input:focus,
.gform-wrapper-register .gform_wrapper textarea:focus,
.gform-wrapper-register .gform_wrapper select:focus {
    outline: none;
    border-color: #FF8B5E;
    box-shadow: 0 0 0 3px rgba(255, 139, 94, 0.1);
}

.gform-wrapper-register .gform_wrapper .gfield {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #D6D1C6;
}

.gform-wrapper-register .gform_wrapper .gfield:last-child {
    border-bottom: none;
}

.gform-wrapper-register .gform_wrapper label {
    display: block;
    font-weight: 500;
    color: #5A5A5A;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.gform-wrapper-register .gform_wrapper .gform_footer {
    padding: 0;
    background: transparent;
    border: none;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #D6D1C6;
    display: flex;
    gap: 1rem;
}

.gform-wrapper-register .gform_wrapper .gform_footer input[type="submit"],
.gform-wrapper-register .gform_wrapper .gform_footer button {
    background: #1A1A1A;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 500;
    cursor: pointer;
    font-size: 1rem;
}

.gform-wrapper-register .gform_wrapper .gform_footer input[type="submit"]:hover,
.gform-wrapper-register .gform_wrapper .gform_footer button:hover {
    background: #333;
}

.gform-wrapper-register .gform_wrapper .gform_footer input[type="reset"] {
    background: transparent;
    color: #1A1A1A;
    border: 1px solid #1A1A1A;
}

.gform-wrapper-register .gform_wrapper .gform_footer input[type="reset"]:hover {
    background: #F7F5EF;
}
</style>

<?php 
get_template_part('template-parts/minside-layout-end');
get_footer(); 
?>
