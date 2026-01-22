<?php
/**
 * Min Side - Rediger artikkel
 *
 * Skjema for redigering av eksisterende artikkel.
 * Brukes på /min-side/artikler/rediger/?id=XX
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user's company (check both meta keys for legacy compatibility)
$company_id = get_user_meta($user_id, 'bimverdi_company_id', true);
if (!$company_id) {
    $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
}

// Get article ID from URL parameter
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect if no article ID
if (!$article_id) {
    wp_redirect(bimverdi_minside_url('artikler'));
    exit;
}

// Get the article post
$article = get_post($article_id);

// Verify article exists and is correct post type
if (!$article || $article->post_type !== 'artikkel') {
    wp_redirect(bimverdi_minside_url('artikler'));
    exit;
}

// Check if user has permission to edit (must be author)
$can_edit = false;
if ($article->post_author == $user_id) {
    $can_edit = true;
}
if (current_user_can('manage_options')) {
    $can_edit = true;
}

if (!$can_edit) {
    wp_redirect(bimverdi_minside_url('artikler'));
    exit;
}

// Get article data
$article_title = $article->post_title;
$article_status = get_post_status($article_id);
$article_updated = get_the_modified_date('d.m.Y', $article_id);
$article_date = get_the_date('d.m.Y', $article_id);

// Get article category
$category_terms = wp_get_post_terms($article_id, 'artikkelkategori');
$article_category = !empty($category_terms) ? $category_terms[0]->name : '';

// Find article form (same as artikler-skriv.php)
$article_form_id = 0;
if (class_exists('GFAPI')) {
    $forms = GFAPI::get_forms();
    foreach ($forms as $form) {
        if (strpos($form['title'], 'Skriv artikkel') !== false || strpos($form['title'], 'artikkel') !== false) {
            $article_form_id = $form['id'];
            break;
        }
    }
}
?>

<!-- Breadcrumb -->
<nav class="mb-6" aria-label="Brødsmulesti">
    <ol class="flex items-center gap-2 text-sm text-[#5A5A5A]">
        <li>
            <a href="<?php echo esc_url(bimverdi_minside_url()); ?>" class="hover:text-[#1A1A1A] transition-colors">
                <?php _e('Min side', 'bimverdi'); ?>
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li>
            <a href="<?php echo esc_url(bimverdi_minside_url('artikler')); ?>" class="hover:text-[#1A1A1A] transition-colors">
                <?php _e('Mine artikler', 'bimverdi'); ?>
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li class="text-[#1A1A1A] font-medium" aria-current="page"><?php _e('Rediger', 'bimverdi'); ?></li>
    </ol>
</nav>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => __('Rediger artikkel', 'bimverdi'),
    'description' => sprintf(__('Oppdater «%s»', 'bimverdi'), esc_html($article_title)),
]); ?>

<!-- Form Container (960px centered per UI-CONTRACT.md) -->
<div class="max-w-3xl mx-auto">

    <!-- Article Info Badge -->
    <div class="mb-8 p-4 bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg flex items-center justify-between">
        <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            <div>
                <p class="font-semibold text-[#1A1A1A]"><?php echo esc_html($article_title); ?></p>
                <?php if ($article_category): ?>
                    <p class="text-sm text-[#5A5A5A]"><?php echo esc_html($article_category); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-right">
            <?php
            $status_labels = [
                'publish' => __('Publisert', 'bimverdi'),
                'pending' => __('Venter godkjenning', 'bimverdi'),
                'draft'   => __('Kladd', 'bimverdi'),
            ];
            $status_classes = [
                'publish' => 'bg-green-100 text-green-800',
                'pending' => 'bg-yellow-100 text-yellow-800',
                'draft'   => 'bg-gray-100 text-gray-800',
            ];
            $status_label = $status_labels[$article_status] ?? ucfirst($article_status);
            $status_class = $status_classes[$article_status] ?? 'bg-gray-100 text-gray-800';
            ?>
            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?php echo $status_class; ?>">
                <?php echo esc_html($status_label); ?>
            </span>
            <p class="text-xs text-[#5A5A5A] mt-1"><?php _e('Oppdatert', 'bimverdi'); ?> <?php echo $article_updated; ?></p>
        </div>
    </div>

    <!-- Gravity Form -->
    <div class="bg-white border border-[#E5E0D5] rounded-lg p-8">
        <h2 class="text-xl font-bold text-[#1A1A1A] mb-6 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#5A5A5A]">
                <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.375 2.625a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4Z"/>
            </svg>
            <?php _e('Oppdater artikkel', 'bimverdi'); ?>
        </h2>

        <?php
        if ($article_form_id && function_exists('gravity_form')) {
            // Pass article_id to form for pre-population
            gravity_form($article_form_id, false, false, false, array(
                'article_id' => $article_id,
            ), true);
        } else {
            ?>
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <p class="text-amber-800 font-semibold mb-2"><?php _e('Skjema ikke tilgjengelig', 'bimverdi'); ?></p>
                <p class="text-amber-700 text-sm">
                    <?php _e('Artikkelskjemaet er ikke satt opp ennå.', 'bimverdi'); ?>
                    <?php if (current_user_can('manage_options')): ?>
                        <a href="<?php echo admin_url('admin.php?page=gf_new_form'); ?>" class="text-amber-900 underline"><?php _e('Opprett skjema i Gravity Forms', 'bimverdi'); ?></a>
                    <?php else: ?>
                        <?php _e('Ta kontakt med administrator.', 'bimverdi'); ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php
        }
        ?>
    </div>

    <!-- Article Preview Link -->
    <?php if ($article_status === 'publish'): ?>
    <div class="mt-8 p-4 bg-[#F7F5EF] border border-[#EFE9DE] rounded-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <span class="text-sm text-[#5A5A5A]"><?php _e('Artikkelen er publisert og synlig for alle.', 'bimverdi'); ?></span>
            </div>
            <a href="<?php echo get_permalink($article_id); ?>" target="_blank" class="inline-flex items-center gap-2 text-sm font-medium text-[#1A1A1A] hover:underline">
                <?php _e('Se artikkelen', 'bimverdi'); ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                    <polyline points="15 3 21 3 21 9"/>
                    <line x1="10" y1="14" x2="21" y2="3"/>
                </svg>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Note: No delete option - per PRD, users should contact admin to delete content -->
    <div class="mt-12 pt-8 border-t border-[#D6D1C6]">
        <div class="p-4 bg-[#F2F0EB] border border-[#E5E0D8] rounded-lg">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A] flex-shrink-0 mt-0.5">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 16v-4"/>
                    <path d="M12 8h.01"/>
                </svg>
                <p class="text-sm text-[#5A5A5A]">
                    <?php _e('Trenger du å slette artikkelen? Ta kontakt med BIM Verdi-administrasjonen.', 'bimverdi'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Back Link -->
    <div class="mt-8 pt-6 border-t border-[#E5E0D5]">
        <a href="<?php echo esc_url(bimverdi_minside_url('artikler')); ?>" class="inline-flex items-center gap-2 text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            <?php _e('Tilbake til mine artikler', 'bimverdi'); ?>
        </a>
    </div>
</div>
