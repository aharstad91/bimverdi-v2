<!-- 
Template Name: Min Side - Verktøy
Description: Page showing user's registered tools
-->
<?php
/**
 * Template Name: Min Side - Verktøy
 * 
 * Template for displaying user's registered tools
 * 
 * @package BimVerdi_Theme
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user's company
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

// Get tools owned by user's company (not by author!)
$user_tools = array();

if ($company_id) {
    // Check if user is hovedkontakt for their company
    $hovedkontakt = get_field('hovedkontaktperson', $company_id);
    $is_hovedkontakt = ($hovedkontakt == $user_id);
    
    // Only show tools if user is hovedkontakt
    if ($is_hovedkontakt) {
        $args = array(
            'post_type' => 'verktoy',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'draft', 'pending'),
            'meta_query' => array(
                array(
                    'key' => 'eier_leverandor',
                    'value' => $company_id,
                    'compare' => '='
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $user_tools = get_posts($args);
    }
}
?>

<!-- Min Side Horizontal Tab Navigation -->
<?php 
$current_tab = 'verktoy';
get_template_part('template-parts/minside-tabs', null, array('current_tab' => $current_tab));
?>

<div class="min-h-screen bg-bim-beige-100 py-8">
    <div class="container mx-auto px-4">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-bold text-bim-black-900 mb-2">Mine Verktøy</h1>
                <p class="text-lg text-bim-black-700">
                    Oversikt over verktøy du har registrert i BIM Verdi
                </p>
            </div>
            <a href="<?php echo esc_url(home_url('/min-side/registrer-verktoy/')); ?>" class="btn btn-hjem-primary btn-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Registrer Nytt Verktøy
            </a>
        </div>

        <?php if (empty($user_tools)): ?>
            <!-- Empty State -->
            <div class="card-hjem">
                <div class="card-body p-12 text-center">
                    <div class="w-24 h-24 bg-bim-beige-200 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-bim-black-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-bim-black-900 mb-3">Ingen verktøy registrert ennå</h2>
                    <p class="text-lg text-bim-black-700 mb-6 max-w-2xl mx-auto">
                        Del dine favorittverktøy med BIM Verdi-medlemmer! Registrer programvare, plugins, 
                        eller andre nyttige verktøy du bruker i ditt daglige arbeid.
                    </p>
                    <div class="flex gap-4 justify-center">
                        <a href="<?php echo esc_url(home_url('/min-side/registrer-verktoy/')); ?>" class="btn btn-hjem-primary btn-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Registrer Ditt Første Verktøy
                        </a>
                        <a href="<?php echo esc_url(home_url('/verktoy/')); ?>" class="btn btn-outline border-bim-orange text-bim-orange hover:bg-bim-orange hover:text-white btn-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Se Verktøykatalog
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Tools Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($user_tools as $tool): 
                    $logo = get_field('logo', $tool->ID);
                    $beskrivelse = get_field('beskrivelse', $tool->ID);
                    $nettside = get_field('nettside', $tool->ID);
                    $pris = get_field('pris', $tool->ID);
                    $kategori_terms = get_the_terms($tool->ID, 'verktoy_kategori');
                ?>
                <div class="card-hjem hover:shadow-xl transition-all duration-200">
                    <div class="card-body p-6">
                        <!-- Logo and Title -->
                        <div class="flex items-start gap-4 mb-4">
                            <div class="flex-shrink-0 w-16 h-16 bg-bim-beige-200 rounded-lg flex items-center justify-center overflow-hidden">
                                <?php if ($logo): ?>
                                    <img src="<?php echo esc_url($logo['url']); ?>" alt="<?php echo esc_attr($tool->post_title); ?>" class="w-full h-full object-cover" />
                                <?php else: ?>
                                    <svg class="w-8 h-8 text-bim-black-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="text-xl font-bold text-bim-black-900">
                                        <?php echo esc_html($tool->post_title); ?>
                                    </h3>
                                    <?php if ($tool->post_status === 'draft' || $tool->post_status === 'pending'): ?>
                                        <span class="badge badge-sm bg-yellow-500 text-white border-0">
                                            <?php echo $tool->post_status === 'draft' ? 'Til godkjenning' : 'Under godkjenning'; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($kategori_terms && !is_wp_error($kategori_terms)): ?>
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach ($kategori_terms as $term): ?>
                                            <span class="badge badge-sm bg-bim-purple text-white border-0">
                                                <?php echo esc_html($term->name); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Description -->
                        <?php if ($beskrivelse): ?>
                            <p class="text-sm text-bim-black-700 mb-4 line-clamp-3">
                                <?php echo esc_html(wp_trim_words($beskrivelse, 20)); ?>
                            </p>
                        <?php endif; ?>

                        <!-- Price -->
                        <?php if ($pris): ?>
                            <div class="mb-4">
                                <span class="text-lg font-semibold text-bim-orange">
                                    <?php echo esc_html($pris); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Actions -->
                        <div class="flex gap-2 mt-auto">
                            <a href="<?php echo esc_url(get_permalink($tool->ID)); ?>" class="btn btn-sm btn-outline border-bim-black-400 text-bim-black-700 hover:bg-bim-black-100 hover:text-bim-black-900">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Se
                            </a>
                            <a href="<?php echo esc_url(home_url('/min-side/rediger-verktoy/?tool_id=' . $tool->ID)); ?>" class="btn btn-sm btn-outline border-bim-orange text-bim-orange hover:bg-bim-orange hover:text-white flex-1">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Rediger
                            </a>
                            <?php if ($nettside): ?>
                                <a href="<?php echo esc_url($nettside); ?>" target="_blank" class="btn btn-sm btn-hjem-primary">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                    Besøk
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Stats -->
            <div class="mt-8 text-center">
                <p class="text-bim-black-600">
                    Du har registrert <strong class="text-bim-orange"><?php echo count($user_tools); ?></strong> verktøy
                </p>
            </div>
        <?php endif; ?>

    </div>
</div>

<style>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php get_footer(); ?>
