<?php
/**
 * Template Name: Min Side - Artikler
 * 
 * Shows user's articles and allows writing new ones
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

// Get user's articles
$user_articles = new WP_Query(array(
    'post_type' => 'artikkel',
    'post_status' => array('publish', 'pending', 'draft'),
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
    'author' => $user_id,
));

// Status labels and colors
$status_labels = array(
    'utkast' => array('label' => 'Utkast', 'variant' => 'neutral', 'icon' => 'file-pen'),
    'til_godkjenning' => array('label' => 'Til godkjenning', 'variant' => 'warning', 'icon' => 'clock'),
    'publisert' => array('label' => 'Publisert', 'variant' => 'success', 'icon' => 'circle-check'),
    'avvist' => array('label' => 'Trenger revisjon', 'variant' => 'danger', 'icon' => 'pen-to-square'),
);

// Category labels
$category_labels = array(
    'fagartikkel' => 'Fagartikkel',
    'case' => 'Case',
    'tips' => 'Tips og triks',
    'nyhet' => 'Nyhet',
    'kommentar' => 'Kommentar',
);

// Form ID for article creation
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
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex gap-8" style="flex-direction: row;">
        
        <!-- Sidebar -->
        <?php get_template_part('template-parts/minside-sidebar', null, array('current_page' => 'artikler')); ?>
        
        <!-- Main Content -->
        <div class="flex-1" style="min-width: 0;">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Mine artikler</h1>
                <p class="text-gray-600">Skriv og del fagartikler, case-studier og tips for kunnskapsdeling.</p>
            </div>
            
            <!-- Write new article CTA -->
            <wa-card class="mb-6" style="--padding: 1.5rem;">
                <div class="flex flex-col md:flex-row items-center gap-4">
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 mb-1">Del din kunnskap!</h3>
                        <p class="text-gray-600 text-sm">Skriv en artikkel og vis frem kompetansen til ditt foretak.</p>
                    </div>
                    <wa-button variant="brand" href="<?php echo esc_url(home_url('/min-side/skriv-artikkel/')); ?>">
                        <wa-icon library="fa" name="far-pen-to-square" slot="prefix"></wa-icon>
                        Skriv artikkel
                    </wa-button>
                </div>
            </wa-card>
            
            <?php if ($user_articles->have_posts()) : ?>
                
                <!-- Articles list -->
                <div class="space-y-4">
                    <?php while ($user_articles->have_posts()) : $user_articles->the_post();
                        $artikkel_status = get_field('artikkel_status');
                        $status_info = isset($status_labels[$artikkel_status]) ? $status_labels[$artikkel_status] : $status_labels['utkast'];
                        $kategori = get_field('artikkel_kategori');
                        $ingress = get_field('artikkel_ingress');
                        $admin_kommentar = get_field('artikkel_admin_kommentar');
                        $post_status = get_post_status();
                        
                        // Override with WordPress post status if published
                        if ($post_status === 'publish') {
                            $artikkel_status = 'publisert';
                            $status_info = $status_labels['publisert'];
                        }
                    ?>
                    
                    <wa-card>
                        <div class="p-5">
                            <!-- Header -->
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900"><?php the_title(); ?></h3>
                                    <?php if ($kategori && isset($category_labels[$kategori])) : ?>
                                        <span class="text-sm text-gray-500"><?php echo esc_html($category_labels[$kategori]); ?></span>
                                    <?php endif; ?>
                                </div>
                                <wa-tag variant="<?php echo esc_attr($status_info['variant']); ?>">
                                    <wa-icon library="fa" name="fas-<?php echo esc_attr($status_info['icon']); ?>" slot="prefix"></wa-icon>
                                    <?php echo esc_html($status_info['label']); ?>
                                </wa-tag>
                            </div>
                            
                            <!-- Ingress -->
                            <?php if ($ingress) : ?>
                                <p class="text-gray-600 mb-4"><?php echo esc_html($ingress); ?></p>
                            <?php elseif (has_excerpt()) : ?>
                                <p class="text-gray-600 mb-4"><?php echo get_the_excerpt(); ?></p>
                            <?php endif; ?>
                            
                            <!-- Meta info -->
                            <div class="flex flex-wrap gap-4 text-sm text-gray-500 mb-4">
                                <span>
                                    <wa-icon library="fa" name="far-calendar" class="mr-1"></wa-icon>
                                    Opprettet: <?php echo get_the_date('j. F Y'); ?>
                                </span>
                                <?php if ($post_status === 'publish') : ?>
                                    <span>
                                        <wa-icon library="fa" name="far-eye" class="mr-1"></wa-icon>
                                        <a href="<?php the_permalink(); ?>" class="text-orange-600 hover:underline">Se publisert artikkel</a>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Admin feedback (if revision needed) -->
                            <?php if ($artikkel_status === 'avvist' && $admin_kommentar) : ?>
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r mb-4">
                                    <div class="flex items-start">
                                        <wa-icon library="fa" name="fas-comment-dots" class="text-yellow-500 mt-1 mr-3"></wa-icon>
                                        <div>
                                            <p class="font-medium text-yellow-800 mb-1">Tilbakemelding fra redaksjonen</p>
                                            <div class="text-yellow-700 text-sm">
                                                <?php echo wp_kses_post($admin_kommentar); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Actions -->
                            <div class="flex gap-2">
                                <?php if ($post_status !== 'publish') : ?>
                                    <wa-button variant="neutral" size="small" outline href="<?php echo esc_url(home_url('/min-side/rediger-artikkel/?id=' . get_the_ID())); ?>">
                                        <wa-icon library="fa" name="far-pen-to-square" slot="prefix"></wa-icon>
                                        Rediger
                                    </wa-button>
                                <?php endif; ?>
                                
                                <?php if ($post_status === 'publish') : ?>
                                    <wa-button variant="neutral" size="small" outline href="<?php the_permalink(); ?>">
                                        <wa-icon library="fa" name="far-eye" slot="prefix"></wa-icon>
                                        Les artikkel
                                    </wa-button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </wa-card>
                    
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
                
            <?php else : ?>
                
                <!-- Empty state -->
                <wa-card>
                    <div class="p-8 text-center">
                        <wa-icon library="fa" name="far-file-lines" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></wa-icon>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Ingen artikler ennå</h3>
                        <p class="text-gray-600 mb-4">Du har ikke skrevet noen artikler. Del din kunnskap med BIM-nettverket!</p>
                        <wa-button variant="brand" href="<?php echo esc_url(home_url('/min-side/skriv-artikkel/')); ?>">
                            <wa-icon library="fa" name="far-pen-to-square" slot="prefix"></wa-icon>
                            Skriv din første artikkel
                        </wa-button>
                    </div>
                </wa-card>
                
            <?php endif; ?>
            
            <!-- Info about article process -->
            <wa-card class="mt-6">
                <div class="p-5">
                    <h3 class="font-semibold text-gray-900 mb-3">
                        <wa-icon library="fa" name="fas-circle-info" class="text-blue-500 mr-2"></wa-icon>
                        Om artikkelpublisering
                    </h3>
                    <div class="text-sm text-gray-600 space-y-2">
                        <p><strong>1. Skriv:</strong> Lag artikkelen din med vårt enkle skjema.</p>
                        <p><strong>2. Send til godkjenning:</strong> BIM Verdi-teamet gjennomgår innholdet.</p>
                        <p><strong>3. Publisert:</strong> Artikkelen blir synlig i artikkelarkivet og på ditt foretaks profil.</p>
                    </div>
                </div>
            </wa-card>
            
        </div>
    </div>
</div>

<?php get_footer(); ?>
