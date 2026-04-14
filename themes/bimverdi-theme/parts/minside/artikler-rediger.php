<?php
/**
 * Min Side - Rediger artikkel
 *
 * Edit form for pending articles. Reuses artikler-skriv structure.
 * Only pending articles owned by the current user can be edited.
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

// Get article ID
$artikkel_id = intval($_GET['id'] ?? 0);
if (!$artikkel_id) {
    wp_redirect(home_url('/min-side/artikler/'));
    exit;
}

$artikkel = get_post($artikkel_id);

// Validate: exists, is artikkel, owned by user
if (!$artikkel || $artikkel->post_type !== 'artikkel') {
    wp_redirect(add_query_arg('bv_error', 'not_found', home_url('/min-side/artikler/')));
    exit;
}

if ((int) $artikkel->post_author !== (int) $user_id && !current_user_can('manage_options')) {
    wp_redirect(add_query_arg('bv_error', 'not_owner', home_url('/min-side/artikler/')));
    exit;
}

// Only pending can be edited
if (get_post_status($artikkel_id) !== 'pending') {
    wp_redirect(add_query_arg('bv_error', 'already_published', home_url('/min-side/artikler/')));
    exit;
}

$company = get_post($company_id);

// Pre-fill values
$existing_title = $artikkel->post_title;
$existing_content = $artikkel->post_content;
$existing_ingress = get_field('artikkel_ingress', $artikkel_id) ?: '';
$existing_temagrupper = wp_get_object_terms($artikkel_id, 'temagruppe', ['fields' => 'ids']);
$existing_verktoykategorier = wp_get_object_terms($artikkel_id, 'verktoykategori', ['fields' => 'ids']);
$existing_kunnskapskilder = get_post_meta($artikkel_id, '_bv_kunnskapskilder', true) ?: [];
$existing_lenker = get_post_meta($artikkel_id, '_bv_eksterne_lenker', true) ?: [];
$existing_thumbnail = get_post_thumbnail_id($artikkel_id);

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
    'already_published'    => 'Artikkelen er allerede publisert og kan ikke redigeres herfra.',
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
    'title' => __('Rediger artikkel', 'bimverdi'),
    'description' => __('Endre artikkelen før den godkjennes.', 'bimverdi'),
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
    <?php wp_nonce_field('bimverdi_edit_artikkel'); ?>
    <input type="hidden" name="bimverdi_edit_artikkel" value="1">
    <input type="hidden" name="artikkel_id" value="<?php echo esc_attr($artikkel_id); ?>">

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
            value="<?php echo esc_attr($existing_title); ?>"
            class="w-full px-3 py-2 border border-[#D6D1C6] rounded-lg text-sm text-[#1A1A1A] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent"
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
        ><?php echo esc_textarea($existing_ingress); ?></textarea>
        <p class="mt-1 text-xs text-[#5A5A5A]"><?php _e('Maks 300 tegn. Tomt = vi bruker starten av brødteksten.', 'bimverdi'); ?></p>
    </div>

    <!-- Forsidebilde -->
    <div class="mb-6">
        <label for="artikkel_image" class="block text-sm font-medium text-[#1A1A1A] mb-1.5">
            <?php _e('Forsidebilde (valgfritt)', 'bimverdi'); ?>
        </label>
        <?php if ($existing_thumbnail): ?>
        <div class="mb-2 flex items-center gap-3">
            <?php echo wp_get_attachment_image($existing_thumbnail, 'thumbnail', false, ['class' => 'w-20 h-20 object-cover rounded']); ?>
            <span class="text-xs text-[#5A5A5A]"><?php _e('Nåværende bilde. Last opp nytt for å erstatte.', 'bimverdi'); ?></span>
        </div>
        <?php endif; ?>
        <input
            type="file"
            id="artikkel_image"
            name="artikkel_image"
            accept=".jpg,.jpeg,.png,.webp"
            class="w-full text-sm text-[#5A5A5A] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#F5F5F4] file:text-[#1A1A1A] hover:file:bg-[#E7E5E4] file:cursor-pointer"
        >
        <p class="mt-1 text-xs text-[#5A5A5A]"><?php _e('JPG, PNG eller WebP. Maks 2 MB.', 'bimverdi'); ?></p>
    </div>

    <!-- Brødtekst (wp_editor) -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-[#1A1A1A] mb-1.5">
            <?php _e('Brødtekst', 'bimverdi'); ?> <span class="text-red-600">*</span>
        </label>
        <?php
        wp_editor($existing_content, 'artikkel_content', [
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
        <p class="mt-1 text-xs text-[#5A5A5A]"><?php _e('Minst 100 tegn.', 'bimverdi'); ?></p>
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
                        <?php checked(in_array($term->term_id, $existing_temagrupper)); ?>
                        class="rounded border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($term->name); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
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
                        <?php checked(in_array($term->term_id, $existing_verktoykategorier)); ?>
                        class="rounded border-[#D6D1C6] text-[#FF8B5E] focus:ring-[#FF8B5E]">
                    <span class="text-sm text-[#1A1A1A]"><?php echo esc_html($term->name); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
    </div>

    <!-- Kunnskapskilde(r) -->
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
            <option value="<?php echo esc_attr($ks->ID); ?>" <?php selected(in_array($ks->ID, $existing_kunnskapskilder)); ?>>
                <?php echo esc_html($ks->post_title); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <p class="mt-1 text-xs text-[#5A5A5A]"><?php _e('Hold Ctrl/Cmd for å velge flere.', 'bimverdi'); ?></p>
    </div>

    <hr class="border-[#E5E0D8] my-8">

    <!-- Eksterne lenker -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-[#1A1A1A] mb-3">
            <?php _e('Eksterne lenker (valgfritt)', 'bimverdi'); ?>
        </label>
        <div id="bv-eksterne-lenker">
            <?php if (!empty($existing_lenker)): ?>
                <?php foreach ($existing_lenker as $lenke): ?>
                <div class="bv-lenke-rad flex gap-2 mb-2">
                    <input type="url" name="eksterne_lenker_url[]" value="<?php echo esc_attr($lenke['url'] ?? ''); ?>" placeholder="https://eksempel.no"
                        class="flex-1 px-3 py-2 border border-[#D6D1C6] rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    <input type="text" name="eksterne_lenker_label[]" value="<?php echo esc_attr($lenke['label'] ?? ''); ?>" placeholder="<?php esc_attr_e('Lenketekst', 'bimverdi'); ?>"
                        class="flex-1 px-3 py-2 border border-[#D6D1C6] rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    <button type="button" onclick="this.parentElement.remove()" class="p-2 text-[#57534E] hover:text-red-600 transition-colors" title="Fjern">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bv-lenke-rad flex gap-2 mb-2">
                    <input type="url" name="eksterne_lenker_url[]" placeholder="https://eksempel.no"
                        class="flex-1 px-3 py-2 border border-[#D6D1C6] rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    <input type="text" name="eksterne_lenker_label[]" placeholder="<?php esc_attr_e('Lenketekst', 'bimverdi'); ?>"
                        class="flex-1 px-3 py-2 border border-[#D6D1C6] rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    <button type="button" onclick="this.parentElement.remove()" class="p-2 text-[#57534E] hover:text-red-600 transition-colors" title="Fjern">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <button type="button" id="bv-legg-til-lenke"
            class="text-sm text-[#FF8B5E] hover:text-[#e87a4e] font-medium transition-colors">
            + <?php _e('Legg til lenke', 'bimverdi'); ?>
        </button>
        <p class="mt-1 text-xs text-[#5A5A5A]"><?php _e('Maks 5 lenker.', 'bimverdi'); ?></p>
    </div>

    <hr class="border-[#E5E0D8] my-8">

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
            'text'    => __('Lagre endringer', 'bimverdi'),
            'variant' => 'primary',
            'type'    => 'submit',
            'icon'    => 'save',
        ]); ?>
        <a href="<?php echo esc_url(bimverdi_minside_url('artikler')); ?>" class="text-sm text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
            <?php _e('Avbryt', 'bimverdi'); ?>
        </a>
    </div>
</form>

<!-- Danger zone: Slett -->
<div class="max-w-[960px] mt-12 pt-8 border-t border-red-200">
    <h3 class="text-sm font-medium text-red-700 mb-2"><?php _e('Faresone', 'bimverdi'); ?></h3>
    <p class="text-sm text-[#57534E] mb-4"><?php _e('Sletting kan ikke angres.', 'bimverdi'); ?></p>
    <?php bimverdi_button([
        'text'    => __('Slett artikkel', 'bimverdi'),
        'variant' => 'danger',
        'href'    => wp_nonce_url(
            add_query_arg([
                'action' => 'delete_artikkel',
                'artikkel_id' => $artikkel_id,
            ], home_url('/min-side/artikler/')),
            'delete_artikkel_' . $artikkel_id
        ),
        'icon'    => 'trash-2',
        'attrs'   => ['onclick' => "return confirm('" . esc_js(__('Er du sikker på at du vil slette denne artikkelen?', 'bimverdi')) . "')"],
    ]); ?>
</div>

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

    // Eksterne lenker
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
