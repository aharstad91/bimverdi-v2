<?php
/**
 * Single Arrangement Template
 * 
 * Displays detailed information about a single event with registration functionality
 * Uses Web Awesome components for modern UI
 * 
 * @package BimVerdi_Theme
 */

get_header();

if (have_posts()) : while (have_posts()) : the_post();

$arrangement_id = get_the_ID();

// Get ACF fields
$dato = get_field('arrangement_dato');
$tid_start = get_field('tidspunkt_start');
$tid_slutt = get_field('tidspunkt_slutt');
$pamelding_frist = get_field('pamelding_frist');
$format = get_field('arrangement_format');
$fysisk_adresse = get_field('fysisk_adresse');
$motelenke = get_field('motelenke');
$beskrivelse = get_field('arrangement_beskrivelse');
$maks_deltakere = get_field('maks_deltakere');
$status = get_field('arrangement_status');
$prosjektleder_id = get_field('prosjektleder');

// Get featured image
$featured_image = get_the_post_thumbnail_url($arrangement_id, 'large');

// Get taxonomies
$arrangementstyper = wp_get_post_terms($arrangement_id, 'arrangementstype', array('fields' => 'all'));
$temagrupper = wp_get_post_terms($arrangement_id, 'temagruppe', array('fields' => 'all'));

// Count registrations
$registrations = get_posts(array(
    'post_type' => 'pamelding',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => 'pamelding_arrangement',
            'value' => $arrangement_id,
        ),
        array(
            'key' => 'pamelding_status',
            'value' => 'aktiv',
        ),
    ),
));
$antall_pameldte = count($registrations);

// Check capacity
$har_ledig_plass = true;
$plasser_igjen = null;
if ($maks_deltakere) {
    $plasser_igjen = $maks_deltakere - $antall_pameldte;
    $har_ledig_plass = $plasser_igjen > 0;
}

// Check if user is registered
$bruker_er_pameldt = false;
$bruker_pamelding_id = null;
if (is_user_logged_in()) {
    $current_user_id = get_current_user_id();
    foreach ($registrations as $reg) {
        if (get_field('pamelding_bruker', $reg->ID) == $current_user_id) {
            $bruker_er_pameldt = true;
            $bruker_pamelding_id = $reg->ID;
            break;
        }
    }
}

// Check registration deadline
$frist_dato = $pamelding_frist ?: $dato;
$frist_passert = strtotime($frist_dato) < strtotime('today');

// Check if event is in the past
$arrangement_passert = strtotime($dato) < strtotime('today');

// Format helpers
function bimverdi_format_icon($format) {
    $icons = [
        'fysisk' => 'location-dot',
        'digitalt' => 'video',
        'hybrid' => 'laptop-house',
    ];
    return $icons[$format] ?? 'calendar';
}

function bimverdi_format_label($format) {
    $labels = [
        'fysisk' => 'Fysisk arrangement',
        'digitalt' => 'Digitalt arrangement',
        'hybrid' => 'Hybrid (fysisk + digitalt)',
    ];
    return $labels[$format] ?? $format;
}

function bimverdi_status_variant($status) {
    $variants = [
        'planlagt' => 'success',
        'avholdt' => 'neutral',
        'avlyst' => 'danger',
    ];
    return $variants[$status] ?? 'neutral';
}

function bimverdi_status_label($status) {
    $labels = [
        'planlagt' => 'Planlagt',
        'avholdt' => 'Avholdt',
        'avlyst' => 'Avlyst',
    ];
    return $labels[$status] ?? $status;
}

// Check avmeldingsfrist (48 timer før)
$kan_avmelde = true;
$avmeldingsfrist_formatert = '';
if ($bruker_er_pameldt) {
    $arrangement_datetime = strtotime($dato . ' ' . $tid_start);
    $avmeldingsfrist = $arrangement_datetime - (48 * 60 * 60);
    $kan_avmelde = time() < $avmeldingsfrist;
    $avmeldingsfrist_formatert = date_i18n('H:i j. F', $avmeldingsfrist);
}

?>

<div class="min-h-screen bg-gradient-to-b from-bim-beige-50 to-white py-8">
    <div class="container mx-auto px-4 max-w-5xl">
        
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <wa-breadcrumb>
                <wa-breadcrumb-item href="<?php echo home_url(); ?>">Hjem</wa-breadcrumb-item>
                <wa-breadcrumb-item href="<?php echo home_url('/arrangementer/'); ?>">Arrangementer</wa-breadcrumb-item>
                <wa-breadcrumb-item><?php echo esc_html(get_the_title()); ?></wa-breadcrumb-item>
            </wa-breadcrumb>
        </nav>

        <!-- Status Banner for cancelled/past events -->
        <?php if ($status === 'avlyst'): ?>
        <wa-alert variant="danger" open class="mb-6">
            <wa-icon slot="icon" name="circle-xmark" library="fa"></wa-icon>
            <strong>Dette arrangementet er avlyst</strong>
        </wa-alert>
        <?php elseif ($status === 'avholdt' || $arrangement_passert): ?>
        <wa-alert variant="neutral" open class="mb-6">
            <wa-icon slot="icon" name="clock-rotate-left" library="fa"></wa-icon>
            <strong>Dette arrangementet er gjennomført</strong>
        </wa-alert>
        <?php endif; ?>

        <!-- Hero Section with Image -->
        <div class="relative mb-8 rounded-2xl overflow-hidden shadow-lg">
            <?php if ($featured_image): ?>
                <div class="h-64 md:h-80 bg-cover bg-center" style="background-image: url('<?php echo esc_url($featured_image); ?>');">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                </div>
            <?php else: ?>
                <div class="h-64 md:h-80 bg-gradient-to-br from-bim-orange-500 to-bim-purple-600">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                </div>
            <?php endif; ?>
            
            <!-- Date badge -->
            <div class="absolute top-6 left-6 bg-white rounded-xl shadow-lg p-4 text-center min-w-[80px]">
                <div class="text-3xl font-bold text-bim-orange-600"><?php echo date('d', strtotime($dato)); ?></div>
                <div class="text-sm uppercase font-medium text-gray-600"><?php echo date('M', strtotime($dato)); ?></div>
                <div class="text-xs text-gray-400"><?php echo date('Y', strtotime($dato)); ?></div>
            </div>
            
            <!-- Status badge -->
            <div class="absolute top-6 right-6">
                <wa-badge variant="<?php echo bimverdi_status_variant($status); ?>" size="large">
                    <?php echo bimverdi_status_label($status); ?>
                </wa-badge>
            </div>
            
            <!-- Title overlay -->
            <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
                <div class="flex flex-wrap gap-2 mb-3">
                    <?php if (!empty($arrangementstyper)): ?>
                        <?php foreach ($arrangementstyper as $type): ?>
                            <wa-badge variant="neutral"><?php echo esc_html($type->name); ?></wa-badge>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (!empty($temagrupper)): ?>
                        <?php foreach ($temagrupper as $gruppe): ?>
                            <wa-badge variant="brand"><?php echo esc_html($gruppe->name); ?></wa-badge>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <h1 class="text-3xl md:text-4xl font-bold"><?php the_title(); ?></h1>
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="md:col-span-2 space-y-6">
                
                <!-- Quick Info Cards -->
                <div class="grid sm:grid-cols-2 gap-4">
                    <!-- Date & Time -->
                    <wa-card>
                        <div class="flex items-start gap-4 p-4">
                            <div class="w-12 h-12 bg-bim-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <wa-icon name="calendar-days" library="fa" style="font-size: 1.5rem; color: var(--wa-color-brand-600);"></wa-icon>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 mb-1">Dato og tid</div>
                                <div class="font-bold text-gray-900">
                                    <?php echo date('j. F Y', strtotime($dato)); ?>
                                </div>
                                <div class="text-gray-600">
                                    <?php echo esc_html($tid_start); ?>
                                    <?php if ($tid_slutt): ?>
                                        - <?php echo esc_html($tid_slutt); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </wa-card>
                    
                    <!-- Location -->
                    <wa-card>
                        <div class="flex items-start gap-4 p-4">
                            <div class="w-12 h-12 bg-bim-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <wa-icon name="<?php echo bimverdi_format_icon($format); ?>" library="fa" style="font-size: 1.5rem; color: var(--wa-color-primary-600);"></wa-icon>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 mb-1"><?php echo bimverdi_format_label($format); ?></div>
                                <?php if ($format === 'fysisk' || $format === 'hybrid'): ?>
                                    <div class="font-bold text-gray-900"><?php echo esc_html($fysisk_adresse); ?></div>
                                <?php endif; ?>
                                <?php if (($format === 'digitalt' || $format === 'hybrid') && $motelenke): ?>
                                    <?php if ($bruker_er_pameldt): ?>
                                        <a href="<?php echo esc_url($motelenke); ?>" target="_blank" class="text-bim-orange-600 hover:underline flex items-center gap-1">
                                            <wa-icon name="video" library="fa" style="font-size: 0.875rem;"></wa-icon>
                                            Åpne møtelenke
                                        </a>
                                    <?php else: ?>
                                        <div class="text-gray-500 italic text-sm">Møtelenke vises etter påmelding</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </wa-card>
                </div>
                
                <!-- Description -->
                <wa-card>
                    <div slot="header" class="font-bold">Om arrangementet</div>
                    <div class="prose max-w-none">
                        <?php if ($beskrivelse): ?>
                            <?php echo wp_kses_post($beskrivelse); ?>
                        <?php else: ?>
                            <?php the_content(); ?>
                        <?php endif; ?>
                    </div>
                </wa-card>
                
            </div>
            
            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- Registration Card -->
                <wa-card class="border-2 border-bim-orange-200">
                    <div slot="header" class="font-bold flex items-center justify-between">
                        <span>Påmelding</span>
                        <?php if ($maks_deltakere && !$har_ledig_plass): ?>
                            <wa-badge variant="warning">Fulltegnet</wa-badge>
                        <?php endif; ?>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if ($status === 'avlyst'): ?>
                            <wa-alert variant="danger" open>
                                <wa-icon slot="icon" name="circle-xmark" library="fa"></wa-icon>
                                Arrangementet er avlyst
                            </wa-alert>
                            
                        <?php elseif ($status === 'avholdt' || $arrangement_passert): ?>
                            <wa-alert variant="neutral" open>
                                <wa-icon slot="icon" name="clock-rotate-left" library="fa"></wa-icon>
                                Arrangementet er gjennomført
                            </wa-alert>
                            
                        <?php elseif ($bruker_er_pameldt): ?>
                            <wa-alert variant="success" open>
                                <wa-icon slot="icon" name="circle-check" library="fa"></wa-icon>
                                <strong>Du er påmeldt!</strong>
                            </wa-alert>
                            
                            <?php if ($kan_avmelde): ?>
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="avmelding-form">
                                    <input type="hidden" name="action" value="bimverdi_avmeld_arrangement">
                                    <input type="hidden" name="pamelding_id" value="<?php echo $bruker_pamelding_id; ?>">
                                    <input type="hidden" name="arrangement_id" value="<?php echo $arrangement_id; ?>">
                                    <?php wp_nonce_field('avmeld_arrangement_' . $bruker_pamelding_id); ?>
                                    
                                    <wa-button variant="danger" outline class="w-full" type="submit" onclick="return confirm('Er du sikker på at du vil melde deg av?');">
                                        <wa-icon slot="prefix" name="user-minus" library="fa"></wa-icon>
                                        Meld deg av
                                    </wa-button>
                                </form>
                                <p class="text-xs text-gray-500 text-center">
                                    Avmeldingsfrist: <?php echo $avmeldingsfrist_formatert; ?>
                                </p>
                            <?php else: ?>
                                <wa-alert variant="warning" open>
                                    <wa-icon slot="icon" name="clock" library="fa"></wa-icon>
                                    Avmeldingsfristen er passert (48 timer før)
                                </wa-alert>
                            <?php endif; ?>
                            
                        <?php elseif (!$har_ledig_plass): ?>
                            <wa-alert variant="warning" open>
                                <wa-icon slot="icon" name="users" library="fa"></wa-icon>
                                <strong>Arrangementet er fulltegnet</strong>
                            </wa-alert>
                            
                        <?php elseif ($frist_passert): ?>
                            <wa-alert variant="warning" open>
                                <wa-icon slot="icon" name="clock" library="fa"></wa-icon>
                                <strong>Påmeldingsfristen er passert</strong>
                            </wa-alert>
                            
                        <?php elseif (!is_user_logged_in()): ?>
                            <p class="text-gray-600 text-sm">
                                Du må være innlogget for å melde deg på.
                            </p>
                            <wa-button variant="brand" class="w-full" href="<?php echo wp_login_url(get_permalink()); ?>">
                                <wa-icon slot="prefix" name="right-to-bracket" library="fa"></wa-icon>
                                Logg inn for å melde deg på
                            </wa-button>
                            
                        <?php else: ?>
                            <!-- Registration Form -->
                            <div id="pamelding-skjema">
                                <?php 
                                // Display Gravity Forms registration form with arrangement_id in URL
                                if (function_exists('gravity_form')) {
                                    $current_url = add_query_arg('arrangement_id', $arrangement_id, get_permalink());
                                    ?>
                                    <script>
                                        // Ensure arrangement_id is added to form action URL
                                        document.addEventListener('DOMContentLoaded', function() {
                                            var form = document.querySelector('#gform_9');
                                            if (form && !form.action.includes('arrangement_id')) {
                                                form.action = form.action + (form.action.includes('?') ? '&' : '?') + 'arrangement_id=<?php echo $arrangement_id; ?>';
                                            }
                                        });
                                    </script>
                                    <?php
                                    gravity_form(9, false, false, false, array('arrangement_id' => $arrangement_id), true);
                                } else {
                                    // Fallback simple form
                                    ?>
                                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                                        <input type="hidden" name="action" value="bimverdi_pamelding_arrangement">
                                        <input type="hidden" name="arrangement_id" value="<?php echo $arrangement_id; ?>">
                                        <?php wp_nonce_field('pamelding_arrangement_' . $arrangement_id); ?>
                                        
                                        <wa-button variant="brand" class="w-full" type="submit">
                                            <wa-icon slot="prefix" name="user-plus" library="fa"></wa-icon>
                                            Meld deg på
                                        </wa-button>
                                    </form>
                                    <?php
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Registration deadline -->
                        <?php if ($pamelding_frist && !$frist_passert && !$bruker_er_pameldt && $status === 'planlagt'): ?>
                            <p class="text-sm text-gray-500 flex items-center gap-2">
                                <wa-icon name="clock" library="fa" style="font-size: 0.875rem;"></wa-icon>
                                Påmeldingsfrist: <?php echo date('j. F Y', strtotime($pamelding_frist)); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </wa-card>
                
                <!-- Organizer Card -->
                <?php if ($prosjektleder_id): 
                    $prosjektleder = get_userdata($prosjektleder_id);
                    if ($prosjektleder):
                ?>
                <wa-card>
                    <div slot="header" class="font-bold">Arrangør</div>
                    <div class="flex items-center gap-3">
                        <wa-avatar 
                            initials="<?php echo esc_attr(strtoupper(substr($prosjektleder->display_name, 0, 2))); ?>"
                            style="--size: 3rem;">
                        </wa-avatar>
                        <div>
                            <div class="font-medium text-gray-900"><?php echo esc_html($prosjektleder->display_name); ?></div>
                            <div class="text-sm text-gray-500"><?php echo esc_html($prosjektleder->user_email); ?></div>
                        </div>
                    </div>
                </wa-card>
                <?php endif; endif; ?>
                
                <!-- Share Card -->
                <wa-card>
                    <div slot="header" class="font-bold">Del arrangement</div>
                    <div class="flex gap-2">
                        <wa-button variant="neutral" size="small" onclick="navigator.clipboard.writeText(window.location.href); alert('Lenke kopiert!');">
                            <wa-icon slot="prefix" name="link" library="fa"></wa-icon>
                            Kopier lenke
                        </wa-button>
                        <wa-button variant="neutral" size="small" href="mailto:?subject=<?php echo rawurlencode(get_the_title()); ?>&body=<?php echo rawurlencode(get_permalink()); ?>">
                            <wa-icon slot="prefix" name="envelope" library="fa"></wa-icon>
                            E-post
                        </wa-button>
                    </div>
                </wa-card>
                
            </div>
        </div>

    </div>
</div>

<?php endwhile; endif; ?>

<?php get_footer(); ?>
