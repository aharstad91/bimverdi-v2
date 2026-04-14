<?php
/**
 * Min Side - Skriv ny artikkel
 *
 * Plain HTML form for submitting a new article.
 * Uses wp_editor() for WYSIWYG content editing.
 *
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_id = get_user_meta($user_id, 'bimverdi_company_id', true)
           ?: get_user_meta($user_id, 'bim_verdi_company_id', true);

// Redirect if not premium
if (!bimverdi_can_access('write_article')) {
    wp_redirect(home_url('/min-side/'));
    exit;
}

$company = get_post($company_id);

// Error handling
$error = isset($_GET['bv_error']) ? sanitize_text_field($_GET['bv_error']) : '';
$error_messages = [
    'nonce'                => 'Skjemaet utløp. Vennligst prøv igjen.',
    'rate_limit'           => 'For mange forsøk. Vennligst vent litt.',
    'no_company'           => 'Du må ha et foretak tilknyttet kontoen din.',
    'missing_title'        => 'Tittel er påkrevd.',
    'title_too_long'       => 'Tittelen kan maks være 120 tegn.',
    'ingress_too_long'     => 'Ingressen kan maks være 300 tegn.',
    'content_too_short'    => 'Brødteksten må være minst 100 tegn.',
    'missing_temagruppe'   => 'Du må velge minst én temagruppe.',
    'missing_verktoykategori' => 'Du må velge minst én verktøykategori.',
    'missing_kunnskapskilde'  => 'Du må velge minst én kunnskapskilde.',
    'invalid_file_type'    => 'Ugyldig filtype. Tillatte: JPG, PNG, WebP.',
    'file_too_large'       => 'Bildet er for stort. Maks 2 MB.',
    'upload_failed'        => 'Kunne ikke laste opp bildet. Prøv igjen.',
    'system'               => 'En teknisk feil oppstod. Prøv igjen.',
];
$error_text = $error_messages[$error] ?? '';

// Get taxonomy terms
$temagrupper = get_terms(['taxonomy' => 'temagruppe', 'hide_empty' => false, 'orderby' => 'name']);
$verktoykategorier = get_terms(['taxonomy' => 'verktoykategori', 'hide_empty' => false, 'orderby' => 'name']);

// Get kunnskapskilder
$kunnskapskilder = get_posts([
    'post_type' => 'kunnskapskilde',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
]);
?>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => __('Skriv ny artikkel', 'bimverdi'),
    'description' => __('Del erfaringer, prosjektrapporter eller innsikt med andre medlemmer.', 'bimverdi'),
]); ?>

<!-- Error message -->
<?php if ($error_text): ?>
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    <p class="text-red-800 text-sm"><?php echo esc_html($error_text); ?></p>
</div>
<?php endif; ?>

<form method="post" action="" enctype="multipart/form-data" class="max-w-[960px]">
    <?php wp_nonce_field('bimverdi_register_artikkel'); ?>
    <input type="hidden" name="bimverdi_register_artikkel" value="1">

    <!-- Honeypot -->
    <div style="position:absolute;left:-9999px;" aria-hidden="true">
        <label for="bv_website_url">Ikke fyll ut dette feltet</label>
        <input type="text" name="bv_website_url" id="bv_website_url" tabindex="-1" autocomplete="off" value="">
    </div>

    <!-- Tittel -->
    <div class="mb-6">
        <label for="artikkel_title" class="block text-sm font-medium text-[#1A1A1A] mb-1.5">
            <?php _e('Tittel', 'bimverdi'); ?> <span class="text-red-600">*</span>
        </label>
        <input
            type="text"
            id="artikkel_title"
            name="artikkel_title"
            maxlength="120"
            required
            class="w-full px-3 py-2 border border-[#D6D1C6] rounded-lg text-sm text-[#1A1A1A] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent"
            placeholder="<?php esc_attr_e('Gi artikkelen en tydelig tittel', 'bimverdi'); ?>"
        >
        <p class="mt-1 text-xs text-[#5A5A5A]"><?php _e('Maks 120 tegn', 'bimverdi'); ?></p>
    </div>

    <!-- Ingress -->
    <div class="mb-6">
        <label for="artikkel_ingress" class="block text-sm font-medium text-[#1A1A1A] mb-1.5">
            <?php _e('Ingress (valgfri)', 'bimverdi'); ?>
        </label>
        <textarea
            id="artikkel_ingress"
            name="artikkel_ingress"
            maxlength="300"
            rows="3"
            class="w-full px-3 py-2 border border-[#D6D1C6] rounded-lg text-sm text-[#1A1A1A] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent resize-vertical"
            placeholder="<?php esc_attr_e('Kort oppsummering av artikkelen', 'bimverdi'); ?>"
        ></textarea>
        <p class="mt-1 text-xs text-[#5A5A5A]"><?php _e('Maks 300 tegn. Tomt = vi bruker starten av brødteksten.', 'bimverdi'); ?></p>
    </div>

    <!-- Forsidebilde -->
    <div class="mb-6">
        <label for="artikkel_image" class="block text-sm font-medium text-[#1A1A1A] mb-1.5">
            <?php _e('Forsidebilde (valgfritt)', 'bimverdi'); ?>
        </label>
        <input
            type="file"
            id="artikkel_image"
            name="artikkel_image"
            accept=".jpg,.jpeg,.png,.webp"
            class="w-full text-sm text-[#5A5A5A] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#F5F5F4] file:text-[#1A1A1A] hover:file:bg-[#E7E5E4] file:cursor-pointer"
        >
        <p class="mt-1 text-xs text-[#5A5A5A]"><?php _e('JPG, PNG eller WebP. Maks 2 MB. Tomt = vi viser en fargeblokk.', 'bimverdi'); ?></p>
    </div>

    <!-- Brødtekst (wp_editor) -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-[#1A1A1A] mb-1.5">
            <?php _e('Brødtekst', 'bimverdi'); ?> <span class="text-red-600">*</span>
        </label>
        <?php
        wp_editor('', 'artikkel_content', [
            'media_buttons'  => false,
            'textarea_name'  => 'artikkel_content',
            'textarea_rows'  => 15,
            'editor_height'  => 400,
            'editor_class'   => 'bv-editor-area',
            'quicktags'      => false,
            'wpautop'        => false,
            'tinymce'        => [
                'toolbar1'       => 'bold,italic,underline,separator,bullist,numlist,separator,link,unlink,separator,undo,redo',
                'toolbar2'       => '',
                'block_formats'  => 'Paragraph=p;Heading 2=h2;Heading 3=h3;Blockquote=blockquote',
                'statusbar'      => false,
            ],
        ]);
        ?>
        <p class="mt-1 text-xs text-[#5A5A5A]"><?php _e('Minst 100 tegn. Bruk verktøylinjen for formatering.', 'bimverdi'); ?></p>
    </div>

    <hr class="border-[#E5E0D8] my-8">

    <!-- Temagruppe(r) -->
    <div class="mb-6">
        <fieldset>
            <legend class="block text-sm font-medium text-[#1A1A1A] mb-3">
                <?php _e('Temagruppe(r)', 'bimverdi'); ?> <span class="text-red-600">*</span>
            </legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($temagrupper as $term): ?>
                <label class="flex items-center gap-2 p-2 rounded hover:bg-[#F5F5F4] cursor-pointer">
                    <input type="checkbox" name="temagrupper[]" value="<?php echo esc_attr($term->term_id); ?>"
                        class="rounded border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($term->name); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <p class="mt-1 text-xs text-[#5A5A5A]"><?php _e('Velg minst én temagruppe artikkelen hører til.', 'bimverdi'); ?></p>
        </fieldset>
    </div>

    <!-- Verktøykategori(er) -->
    <div class="mb-6">
        <fieldset>
            <legend class="block text-sm font-medium text-[#1A1A1A] mb-3">
                <?php _e('Verktøykategori(er)', 'bimverdi'); ?> <span class="text-red-600">*</span>
            </legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($verktoykategorier as $term): ?>
                <label class="flex items-center gap-2 p-2 rounded hover:bg-[#F5F5F4] cursor-pointer">
                    <input type="checkbox" name="verktoykategorier[]" value="<?php echo esc_attr($term->term_id); ?>"
                        class="rounded border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($term->name); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <p class="mt-1 text-xs text-[#5A5A5A]"><?php _e('Velg minst én verktøykategori.', 'bimverdi'); ?></p>
        </fieldset>
    </div>

    <!-- Kunnskapskilde(r) — searchable multi-select -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-[#1A1A1A] mb-1.5">
            <?php _e('Kunnskapskilde(r)', 'bimverdi'); ?> <span class="text-red-600">*</span>
        </label>
        <input
            type="text"
            id="bv-kunnskapskilde-filter"
            placeholder="<?php esc_attr_e('Søk etter kunnskapskilder...', 'bimverdi'); ?>"
            class="w-full px-3 py-2 mb-2 border border-[#D6D1C6] rounded-lg text-sm text-[#1A1A1A] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent"
        >
        <select
            name="kunnskapskilder[]"
            id="bv-kunnskapskilde-select"
            multiple
            size="8"
            class="w-full px-3 py-2 border border-[#D6D1C6] rounded-lg text-sm text-[#1A1A1A] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent"
        >
            <?php foreach ($kunnskapskilder as $ks): ?>
            <option value="<?php echo esc_attr($ks->ID); ?>"><?php echo esc_html($ks->post_title); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="mt-1 text-xs text-[#5A5A5A]"><?php _e('Hold Ctrl/Cmd for å velge flere. Velg minst én.', 'bimverdi'); ?></p>
    </div>

    <hr class="border-[#E5E0D8] my-8">

    <!-- Eksterne lenker (dynamisk, maks 5) -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-[#1A1A1A] mb-3">
            <?php _e('Eksterne lenker (valgfritt)', 'bimverdi'); ?>
        </label>
        <div id="bv-eksterne-lenker">
            <div class="bv-lenke-rad flex gap-2 mb-2">
                <input type="url" name="eksterne_lenker_url[]" placeholder="https://eksempel.no"
                    class="flex-1 px-3 py-2 border border-[#D6D1C6] rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                <input type="text" name="eksterne_lenker_label[]" placeholder="<?php esc_attr_e('Lenketekst', 'bimverdi'); ?>"
                    class="flex-1 px-3 py-2 border border-[#D6D1C6] rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                <button type="button" onclick="this.parentElement.remove()" class="p-2 text-[#57534E] hover:text-red-600 transition-colors" title="Fjern">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>
        <button type="button" id="bv-legg-til-lenke"
            class="text-sm text-[#FF8B5E] hover:text-[#e87a4e] font-medium transition-colors">
            + <?php _e('Legg til lenke', 'bimverdi'); ?>
        </button>
        <p class="mt-1 text-xs text-[#5A5A5A]"><?php _e('Maks 5 lenker. Vises i bunnen av artikkelen.', 'bimverdi'); ?></p>
    </div>

    <hr class="border-[#E5E0D8] my-8">

    <!-- Info boxes -->
    <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
        <?php _e('Etter du sender inn ser du status i listen din. Vi sjekker innsendte artikler manuelt før publisering.', 'bimverdi'); ?>
    </div>

    <!-- Byline preview -->
    <div class="mb-6 p-4 bg-[#F5F5F4] rounded-lg">
        <p class="text-xs text-[#5A5A5A] mb-1"><?php _e('Artikkelen publiseres med byline:', 'bimverdi'); ?></p>
        <p class="text-sm font-medium text-[#1A1A1A]">
            <?php echo esc_html($current_user->display_name); ?>, <?php echo esc_html($company ? $company->post_title : ''); ?>
        </p>
    </div>

    <!-- Submit -->
    <div class="flex items-center gap-4">
        <?php bimverdi_button([
            'text'    => __('Send inn for godkjenning', 'bimverdi'),
            'variant' => 'primary',
            'type'    => 'submit',
            'icon'    => 'send',
        ]); ?>
        <a href="<?php echo esc_url(bimverdi_minside_url('artikler')); ?>" class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
            <?php _e('Avbryt', 'bimverdi'); ?>
        </a>
    </div>
</form>

<!-- JavaScript: Kunnskapskilde filter + Eksterne lenker -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Kunnskapskilde filter
    var filterInput = document.getElementById('bv-kunnskapskilde-filter');
    var selectEl = document.getElementById('bv-kunnskapskilde-select');
    if (filterInput && selectEl) {
        filterInput.addEventListener('input', function() {
            var query = this.value.toLowerCase();
            var options = selectEl.options;
            for (var i = 0; i < options.length; i++) {
                var text = options[i].textContent.toLowerCase();
                options[i].style.display = text.indexOf(query) !== -1 ? '' : 'none';
            }
        });
    }

    // Eksterne lenker — legg til rad
    var container = document.getElementById('bv-eksterne-lenker');
    var addBtn = document.getElementById('bv-legg-til-lenke');
    if (addBtn && container) {
        addBtn.addEventListener('click', function() {
            if (container.querySelectorAll('.bv-lenke-rad').length >= 5) return;
            var row = document.createElement('div');
            row.className = 'bv-lenke-rad flex gap-2 mb-2';
            row.innerHTML = '<input type="url" name="eksterne_lenker_url[]" placeholder="https://eksempel.no" class="flex-1 px-3 py-2 border border-[#D6D1C6] rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">'
                + '<input type="text" name="eksterne_lenker_label[]" placeholder="Lenketekst" class="flex-1 px-3 py-2 border border-[#D6D1C6] rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">'
                + '<button type="button" onclick="this.parentElement.remove()" class="p-2 text-[#57534E] hover:text-red-600 transition-colors" title="Fjern"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>';
            container.appendChild(row);
        });
    }

    // Disable submit button after first click
    var form = document.querySelector('form[enctype]');
    if (form) {
        form.addEventListener('submit', function() {
            var btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.style.opacity = '0.6';
            }
        });
    }
});
</script>
