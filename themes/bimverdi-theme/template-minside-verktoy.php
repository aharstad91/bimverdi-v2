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

// Start Min Side layout
get_template_part('template-parts/minside-layout-start', null, array(
    'current_page' => 'verktoy',
    'page_title' => 'Verktøy',
    'page_icon' => 'wrench',
    'page_description' => 'Oversikt over verktøy du har registrert i BIM Verdi',
));
?>

<!-- Header Actions -->
<div class="flex justify-between items-center mb-6">
    <div class="text-sm text-gray-600">
        Du har registrert <strong class="text-orange-600"><?php echo count($user_tools); ?></strong> verktøy
    </div>
    <wa-button variant="brand" href="<?php echo esc_url(home_url('/min-side/registrer-verktoy/')); ?>">
        <wa-icon slot="prefix" name="plus" library="fa"></wa-icon>
        Registrer nytt verktøy
    </wa-button>
</div>

<?php if (empty($user_tools)): ?>
    <!-- Empty State -->
    <wa-card class="text-center py-12">
        <div class="flex flex-col items-center">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                <wa-icon name="wrench" library="fa" style="font-size: 3rem; color: var(--wa-color-neutral-400);"></wa-icon>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-3">Ingen verktøy registrert ennå</h2>
            <p class="text-gray-600 mb-6 max-w-md">
                Del dine favorittverktøy med BIM Verdi-medlemmer! Registrer programvare, plugins, 
                eller andre nyttige verktøy du bruker i ditt daglige arbeid.
            </p>
            <div class="flex gap-3">
                <wa-button variant="brand" size="large" href="<?php echo esc_url(home_url('/min-side/registrer-verktoy/')); ?>">
                    <wa-icon slot="prefix" name="plus" library="fa"></wa-icon>
                    Registrer ditt første verktøy
                </wa-button>
                <wa-button variant="neutral" outline size="large" href="<?php echo esc_url(home_url('/verktoy/')); ?>">
                    <wa-icon slot="prefix" name="eye" library="fa"></wa-icon>
                    Se verktøykatalog
                </wa-button>
            </div>
        </div>
    </wa-card>
<?php else: ?>
    <!-- Tools Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <?php foreach ($user_tools as $tool): 
            $logo = get_field('logo', $tool->ID);
            $beskrivelse = get_field('beskrivelse', $tool->ID);
            $nettside = get_field('nettside', $tool->ID);
            $pris = get_field('pris', $tool->ID);
            $kategori_terms = get_the_terms($tool->ID, 'verktoy_kategori');
            $tool_status = get_post_status($tool->ID);
            $status_variant = $tool_status === 'publish' ? 'success' : 'warning';
            $status_label = $tool_status === 'publish' ? 'Publisert' : ($tool_status === 'pending' ? 'Venter' : 'Kladd');
        ?>
        <wa-card class="hover:shadow-lg transition-shadow">
            <div class="p-5">
                <!-- Logo and Title -->
                <div class="flex items-start gap-4 mb-4">
                    <div class="flex-shrink-0 w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                        <?php if ($logo): ?>
                            <img src="<?php echo esc_url($logo['url']); ?>" alt="<?php echo esc_attr($tool->post_title); ?>" class="w-full h-full object-cover" />
                        <?php else: ?>
                            <wa-icon name="wrench" library="fa" style="font-size: 1.5rem; color: var(--wa-color-neutral-400);"></wa-icon>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow min-w-0">
                        <h3 class="text-lg font-bold text-gray-900 truncate mb-1">
                            <?php echo esc_html($tool->post_title); ?>
                        </h3>
                        <wa-badge variant="<?php echo $status_variant; ?>" size="small"><?php echo $status_label; ?></wa-badge>
                    </div>
                </div>

                <!-- Categories -->
                <?php if ($kategori_terms && !is_wp_error($kategori_terms)): ?>
                    <div class="flex flex-wrap gap-1 mb-3">
                        <?php foreach (array_slice($kategori_terms, 0, 2) as $term): ?>
                            <wa-tag size="small"><?php echo esc_html($term->name); ?></wa-tag>
                        <?php endforeach; ?>
                        <?php if (count($kategori_terms) > 2): ?>
                            <wa-tag size="small" variant="neutral">+<?php echo count($kategori_terms) - 2; ?></wa-tag>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Description -->
                <?php if ($beskrivelse): ?>
                    <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                        <?php echo esc_html(wp_trim_words($beskrivelse, 15)); ?>
                    </p>
                <?php endif; ?>

                <!-- Price -->
                <?php if ($pris): ?>
                    <div class="mb-4">
                        <span class="text-lg font-semibold text-orange-600">
                            <?php echo esc_html($pris); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="flex gap-2 pt-3 border-t border-gray-100">
                    <wa-button variant="neutral" outline size="small" href="<?php echo esc_url(get_permalink($tool->ID)); ?>">
                        <wa-icon slot="prefix" name="eye" library="fa"></wa-icon>
                        Se
                    </wa-button>
                    <wa-button variant="brand" outline size="small" class="flex-1" href="<?php echo esc_url(home_url('/min-side/rediger-verktoy/?tool_id=' . $tool->ID)); ?>">
                        <wa-icon slot="prefix" name="pen" library="fa"></wa-icon>
                        Rediger
                    </wa-button>
                    <?php if ($nettside): ?>
                        <wa-button variant="brand" size="small" href="<?php echo esc_url($nettside); ?>" target="_blank">
                            <wa-icon name="external-link" library="fa"></wa-icon>
                        </wa-button>
                    <?php endif; ?>
                </div>
            </div>
        </wa-card>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php 
get_template_part('template-parts/minside-layout-end');
get_footer(); 
?>
