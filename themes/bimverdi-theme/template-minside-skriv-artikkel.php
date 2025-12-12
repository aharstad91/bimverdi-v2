<?php
/**
 * Template Name: Min Side - Skriv Artikkel
 * 
 * Form page for writing new articles
 * 
 * @package BIMVerdi
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/logg-inn/?redirect_to=' . urlencode($_SERVER['REQUEST_URI'])));
    exit;
}

get_header();

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get article form ID
$article_form_id = 0;
if (class_exists('GFAPI')) {
    $forms = GFAPI::get_forms();
    foreach ($forms as $form) {
        if (strpos($form['title'], 'Skriv artikkel') !== false) {
            $article_form_id = $form['id'];
            break;
        }
    }
}

// Get user's company for display
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
$company_name = $company_id ? get_the_title($company_id) : '';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex gap-8" style="flex-direction: row;">
        
        <!-- Sidebar -->
        <?php get_template_part('template-parts/minside-sidebar', null, array('current_page' => 'artikler')); ?>
        
        <!-- Main Content -->
        <div class="flex-1" style="min-width: 0;">
            
            <!-- Back link -->
            <div class="mb-4">
                <a href="<?php echo esc_url(home_url('/min-side/artikler/')); ?>" class="text-gray-600 hover:text-gray-900 text-sm">
                    <wa-icon library="fa" name="fas-arrow-left" class="mr-1"></wa-icon>
                    Tilbake til mine artikler
                </a>
            </div>
            
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Skriv artikkel</h1>
                <p class="text-gray-600">Del din kunnskap med BIM-nettverket.</p>
            </div>
            
            <!-- Author info -->
            <?php if ($company_name) : ?>
                <wa-alert variant="neutral" class="mb-6" open>
                    <wa-icon library="fa" name="fas-building" slot="icon"></wa-icon>
                    <strong>Forfatter:</strong> <?php echo esc_html($current_user->display_name); ?> 
                    <span class="text-gray-500">fra <?php echo esc_html($company_name); ?></span>
                </wa-alert>
            <?php endif; ?>
            
            <!-- Article form -->
            <wa-card>
                <div class="p-6 lg:p-8">
                    <?php 
                    if ($article_form_id && function_exists('gravity_form')) {
                        gravity_form($article_form_id, false, false, false, null, true, 12);
                    } else {
                        ?>
                        <wa-alert variant="warning" open>
                            <wa-icon library="fa" name="fas-triangle-exclamation" slot="icon"></wa-icon>
                            <strong>Skjema ikke tilgjengelig</strong><br>
                            Artikkelskjemaet er ikke satt opp ennå. 
                            <?php if (current_user_can('manage_options')) : ?>
                                <a href="<?php echo admin_url('?bimverdi_create_article_form=1'); ?>" class="text-orange-600 hover:underline">Opprett skjema</a>
                            <?php else : ?>
                                Ta kontakt med administrator.
                            <?php endif; ?>
                        </wa-alert>
                        <?php
                    }
                    ?>
                </div>
            </wa-card>
            
            <!-- Writing tips -->
            <div class="grid md:grid-cols-2 gap-4 mt-6">
                <wa-card>
                    <div class="p-5">
                        <h3 class="font-semibold text-gray-900 mb-3">
                            <wa-icon library="fa" name="fas-lightbulb" class="text-yellow-500 mr-2"></wa-icon>
                            Tips for gode artikler
                        </h3>
                        <ul class="text-sm text-gray-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <wa-icon library="fa" name="fas-check" class="text-green-500 mt-1 text-xs"></wa-icon>
                                <span>Bruk en tydelig og beskrivende tittel</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <wa-icon library="fa" name="fas-check" class="text-green-500 mt-1 text-xs"></wa-icon>
                                <span>Start med det viktigste - konklusjonen først</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <wa-icon library="fa" name="fas-check" class="text-green-500 mt-1 text-xs"></wa-icon>
                                <span>Del opp teksten med mellomoverskrifter</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <wa-icon library="fa" name="fas-check" class="text-green-500 mt-1 text-xs"></wa-icon>
                                <span>Inkluder konkrete eksempler</span>
                            </li>
                        </ul>
                    </div>
                </wa-card>
                
                <wa-card>
                    <div class="p-5">
                        <h3 class="font-semibold text-gray-900 mb-3">
                            <wa-icon library="fa" name="fas-circle-info" class="text-blue-500 mr-2"></wa-icon>
                            Hva skjer etter innsending?
                        </h3>
                        <ol class="text-sm text-gray-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xs font-bold">1</span>
                                <span>Artikkelen sendes til gjennomgang</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xs font-bold">2</span>
                                <span>BIM Verdi-teamet vurderer innholdet</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xs font-bold">3</span>
                                <span>Du får beskjed når artikkelen publiseres</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xs font-bold">4</span>
                                <span>Artikkelen vises i arkivet og på foretakets profil</span>
                            </li>
                        </ol>
                    </div>
                </wa-card>
            </div>
            
        </div>
    </div>
</div>

<!-- Custom styles for article form -->
<style>
    .gform_wrapper .gfield textarea {
        min-height: 200px;
    }
    
    .gform_wrapper .gfield--type-post_content .wp-editor-area {
        min-height: 300px !important;
    }
    
    .gform_wrapper .gsection {
        margin: 2rem 0 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #F97316;
    }
    
    .gform_wrapper .gsection_title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1f2937;
    }
    
    .gform_wrapper .gform_button {
        background: #F97316 !important;
        color: white !important;
        padding: 0.875rem 2rem !important;
        border: none !important;
        border-radius: 0.5rem !important;
        font-size: 1rem !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        transition: background 0.2s !important;
    }
    
    .gform_wrapper .gform_button:hover {
        background: #ea580c !important;
    }
</style>

<?php get_footer(); ?>
