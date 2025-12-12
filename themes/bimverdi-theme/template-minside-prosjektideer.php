<?php
/**
 * Template Name: Min Side - Prosjektidéer
 * 
 * Shows user's submitted project ideas on Min Side
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

// Get user's submitted ideas (via user ID or email matching)
$user_ideas = new WP_Query(array(
    'post_type' => 'case',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => '_lead_user_id',
            'value' => $user_id,
            'compare' => '=',
        ),
        array(
            'key' => '_lead_contact_email',
            'value' => $current_user->user_email,
            'compare' => '=',
        ),
        array(
            'key' => 'innsendt_av',
            'value' => $user_id,
            'compare' => '=',
        ),
    ),
));

// Status labels and colors - Partnership focused, not approval/rejection
$status_labels = array(
    'ny' => array('label' => 'Ny henvendelse', 'variant' => 'success', 'icon' => 'circle-check'),
    'under_vurdering' => array('label' => 'Under vurdering', 'variant' => 'warning', 'icon' => 'clock'),
    'kontaktet' => array('label' => 'Kontaktet', 'variant' => 'primary', 'icon' => 'phone'),
    'i_samarbeid' => array('label' => 'I samarbeid', 'variant' => 'success', 'icon' => 'handshake'),
    'arkivert' => array('label' => 'Arkivert', 'variant' => 'neutral', 'icon' => 'box-archive'),
    // Legacy support
    'godkjent' => array('label' => 'I samarbeid', 'variant' => 'success', 'icon' => 'handshake'),
    'avslag' => array('label' => 'Arkivert', 'variant' => 'neutral', 'icon' => 'box-archive'),
);
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex gap-8" style="flex-direction: row;">
        
        <!-- Sidebar -->
        <?php get_template_part('template-parts/minside-sidebar'); ?>
        
        <!-- Main Content -->
        <div class="flex-1" style="min-width: 0;">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Mine prosjektidéer</h1>
                <p class="text-gray-600">Oversikt over dine innsendte prosjektidéer og søknader om bistand.</p>
            </div>
            
            <!-- Submit new idea CTA -->
            <wa-card class="mb-6" style="--padding: 1.5rem;">
                <div class="flex flex-col md:flex-row items-center gap-4">
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 mb-1">Har du en ny prosjektidé?</h3>
                        <p class="text-gray-600 text-sm">Send inn din idé så hjelper vi deg med vurdering og søknadsbistand.</p>
                    </div>
                    <wa-button variant="brand" href="<?php echo esc_url(home_url('/soknadsbistand/')); ?>">
                        <wa-icon library="fa" name="far-lightbulb" slot="prefix"></wa-icon>
                        Send inn ny idé
                    </wa-button>
                </div>
            </wa-card>
            
            <?php if ($user_ideas->have_posts()) : ?>
                
                <!-- Ideas list -->
                <div class="space-y-4">
                    <?php while ($user_ideas->have_posts()) : $user_ideas->the_post(); 
                        $status = get_field('case_status');
                        $status_info = isset($status_labels[$status]) ? $status_labels[$status] : $status_labels['ny'];
                        $short_desc = get_field('kort_beskrivelse');
                        $date_sent = get_field('dato_sendt');
                        $feedback = get_field('tilbakemelding');
                        $timeframe = get_post_meta(get_the_ID(), '_lead_timeframe', true);
                        
                        $timeframe_labels = array(
                            'under_6_mnd' => 'Under 6 måneder',
                            '6_12_mnd' => '6-12 måneder',
                            '1_2_ar' => '1-2 år',
                            'over_2_ar' => 'Over 2 år',
                            'usikker' => 'Usikker',
                        );
                    ?>
                    
                    <wa-card>
                        <div class="p-5">
                            <!-- Header -->
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
                                <h3 class="text-lg font-semibold text-gray-900"><?php the_title(); ?></h3>
                                <wa-tag variant="<?php echo esc_attr($status_info['variant']); ?>">
                                    <wa-icon library="fa" name="fas-<?php echo esc_attr($status_info['icon']); ?>" slot="prefix"></wa-icon>
                                    <?php echo esc_html($status_info['label']); ?>
                                </wa-tag>
                            </div>
                            
                            <!-- Description -->
                            <?php if ($short_desc) : ?>
                                <p class="text-gray-600 mb-4"><?php echo esc_html($short_desc); ?></p>
                            <?php endif; ?>
                            
                            <!-- Meta info -->
                            <div class="flex flex-wrap gap-4 text-sm text-gray-500 mb-4">
                                <?php if ($date_sent) : ?>
                                    <span>
                                        <wa-icon library="fa" name="far-calendar" class="mr-1"></wa-icon>
                                        Sendt: <?php echo esc_html(date_i18n('j. F Y', strtotime($date_sent))); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($timeframe && isset($timeframe_labels[$timeframe])) : ?>
                                    <span>
                                        <wa-icon library="fa" name="far-clock" class="mr-1"></wa-icon>
                                        Tidsramme: <?php echo esc_html($timeframe_labels[$timeframe]); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Feedback from BIM Verdi (if any) -->
                            <?php if ($feedback) : ?>
                                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r">
                                    <div class="flex items-start">
                                        <wa-icon library="fa" name="fas-comment-dots" class="text-blue-400 mt-1 mr-3"></wa-icon>
                                        <div>
                                            <p class="font-medium text-blue-800 mb-1">Tilbakemelding fra BIM Verdi</p>
                                            <div class="text-blue-700 text-sm prose prose-sm">
                                                <?php echo wp_kses_post($feedback); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Status-specific messages -->
                            <?php if ($status === 'ny') : ?>
                                <div class="bg-green-50 border border-green-200 rounded-lg p-3 mt-4">
                                    <p class="text-green-700 text-sm">
                                        <wa-icon library="fa" name="fas-circle-info" class="mr-1"></wa-icon>
                                        Din idé er mottatt og venter på behandling. Du vil høre fra oss snart!
                                    </p>
                                </div>
                            <?php elseif ($status === 'under_vurdering') : ?>
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-4">
                                    <p class="text-yellow-700 text-sm">
                                        <wa-icon library="fa" name="fas-hourglass-half" class="mr-1"></wa-icon>
                                        Din idé er under vurdering. Vi tar kontakt for mer informasjon om nødvendig.
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </wa-card>
                    
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
                
            <?php else : ?>
                
                <!-- Empty state -->
                <wa-card>
                    <div class="p-8 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <wa-icon library="fa" name="far-lightbulb" style="font-size: 2rem; color: #9ca3af;"></wa-icon>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Ingen prosjektidéer ennå</h3>
                        <p class="text-gray-600 mb-6">Du har ikke sendt inn noen prosjektidéer. Har du en idé du ønsker å utforske?</p>
                        <wa-button variant="brand" href="<?php echo esc_url(home_url('/soknadsbistand/')); ?>">
                            <wa-icon library="fa" name="fas-plus" slot="prefix"></wa-icon>
                            Send inn din første idé
                        </wa-button>
                    </div>
                </wa-card>
                
            <?php endif; ?>
            
            <!-- Info box about the service -->
            <div class="mt-8">
                <wa-alert variant="primary" open>
                    <strong>Om søknadsbistand</strong><br>
                    BIM Verdi hjelper medlemmer og aktører i BIM-bransjen med å vurdere prosjektidéer og søke støtte fra virkemiddelapparatet (Innovasjon Norge, Forskningsrådet, EU-programmer m.m.).
                    <br><br>
                    <a href="<?php echo esc_url(home_url('/om-soknadsbistand/')); ?>" class="text-blue-600 hover:underline">Les mer om tjenesten →</a>
                </wa-alert>
            </div>
            
        </div>
    </div>
</div>

<?php get_footer(); ?>
