<?php
/**
 * Temagruppe Info Bar
 *
 * Horizontal row with three equal cards: Status, Fagansvarlig, and CTA.
 * Dashboard-style layout for the theme group page.
 *
 * @package BimVerdi_Theme
 *
 * @param array $args {
 *     @type int      $post_id        Post ID of the temagruppe
 *     @type string   $status         Status: aktiv|planlegging|pause
 *     @type string   $motefrekvens   Meeting frequency text
 *     @type int      $member_count   Number of member companies
 *     @type int      $event_count    Number of upcoming events
 * }
 */

if (!defined('ABSPATH')) exit;

$post_id = $args['post_id'] ?? get_the_ID();
$status = $args['status'] ?? 'aktiv';
$motefrekvens = $args['motefrekvens'] ?? '';
$member_count = $args['member_count'] ?? 0;
$event_count = $args['event_count'] ?? 0;
$temagruppe_navn = get_the_title($post_id);

// Get fagansvarlig fields
$fagansvarlig_navn = get_field('fagansvarlig_navn', $post_id);
$fagansvarlig_tittel = get_field('fagansvarlig_tittel', $post_id);
$fagansvarlig_bedrift_id = get_field('fagansvarlig_bedrift', $post_id);
$fagansvarlig_bilde_id = get_field('fagansvarlig_bilde', $post_id);
$fagansvarlig_linkedin = get_field('fagansvarlig_linkedin', $post_id);

// Get company info
$bedrift_navn = '';
$bedrift_url = '';
if ($fagansvarlig_bedrift_id) {
    $bedrift_navn = get_the_title($fagansvarlig_bedrift_id);
    $bedrift_url = get_permalink($fagansvarlig_bedrift_id);
}

// Get image URL
$bilde_url = $fagansvarlig_bilde_id ? wp_get_attachment_image_url($fagansvarlig_bilde_id, 'thumbnail') : null;

// Generate initials for fallback
$initials = '';
if ($fagansvarlig_navn) {
    $name_parts = explode(' ', $fagansvarlig_navn);
    if (count($name_parts) >= 2) {
        $initials = strtoupper(substr($name_parts[0], 0, 1) . substr(end($name_parts), 0, 1));
    } else {
        $initials = strtoupper(substr($fagansvarlig_navn, 0, 2));
    }
}

// Status configuration
$status_config = [
    'aktiv' => [
        'label' => 'Aktiv',
        'color' => '#22C55E',
        'bg' => '#DCFCE7',
    ],
    'planlegging' => [
        'label' => 'Under planlegging',
        'color' => '#EAB308',
        'bg' => '#FEF9C3',
    ],
    'pause' => [
        'label' => 'Pause',
        'color' => '#6B7280',
        'bg' => '#F3F4F6',
    ],
];

$current_status = $status_config[$status] ?? $status_config['aktiv'];
?>

<section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

    <!-- Card 1: Status -->
    <div class="bg-white rounded-lg border border-[#E5E0D8] p-6">
        <h3 class="text-sm font-semibold text-[#5A5A5A] uppercase tracking-wide mb-4">
            Status
        </h3>

        <!-- Status Badge -->
        <div class="mb-4">
            <span
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium"
                style="background-color: <?php echo esc_attr($current_status['bg']); ?>; color: <?php echo esc_attr($current_status['color']); ?>;"
            >
                <span class="w-2 h-2 rounded-full" style="background-color: <?php echo esc_attr($current_status['color']); ?>;"></span>
                <?php echo esc_html($current_status['label']); ?>
            </span>
        </div>

        <!-- Info List -->
        <dl class="space-y-3">
            <?php if ($motefrekvens) : ?>
            <div class="flex justify-between items-start">
                <dt class="text-sm text-[#5A5A5A]">Motefrekvens</dt>
                <dd class="text-sm font-medium text-[#1A1A1A] text-right"><?php echo esc_html($motefrekvens); ?></dd>
            </div>
            <?php endif; ?>

            <div class="flex justify-between items-start">
                <dt class="text-sm text-[#5A5A5A]">Deltakere</dt>
                <dd class="text-sm font-medium text-[#1A1A1A]">
                    <?php echo esc_html($member_count); ?> bedrifter
                </dd>
            </div>

            <div class="flex justify-between items-start">
                <dt class="text-sm text-[#5A5A5A]">Kommende moter</dt>
                <dd class="text-sm font-medium text-[#1A1A1A]">
                    <?php echo esc_html($event_count); ?> planlagt
                </dd>
            </div>
        </dl>
    </div>

    <!-- Card 2: Fagansvarlig -->
    <div class="bg-white rounded-lg border border-[#E5E0D8] p-6">
        <h3 class="text-sm font-semibold text-[#5A5A5A] uppercase tracking-wide mb-4">
            Fagansvarlig
        </h3>

        <?php if ($fagansvarlig_navn) : ?>
        <div class="flex items-start gap-4">
            <!-- Profile Image -->
            <div class="flex-shrink-0">
                <?php if ($bilde_url) : ?>
                <img
                    src="<?php echo esc_url($bilde_url); ?>"
                    alt="<?php echo esc_attr($fagansvarlig_navn); ?>"
                    class="w-14 h-14 rounded-full object-cover"
                    loading="lazy"
                >
                <?php else : ?>
                <div class="w-14 h-14 rounded-full bg-[#FF8B5E] flex items-center justify-center text-white font-semibold text-base">
                    <?php echo esc_html($initials); ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="min-w-0">
                <?php if ($fagansvarlig_linkedin) : ?>
                <a
                    href="<?php echo esc_url($fagansvarlig_linkedin); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-base font-semibold text-[#1A1A1A] hover:text-[#FF8B5E] hover:underline inline-flex items-center gap-1.5"
                >
                    <?php echo esc_html($fagansvarlig_navn); ?>
                    <?php echo bimverdi_icon('linkedin', 14); ?>
                </a>
                <?php else : ?>
                <p class="text-base font-semibold text-[#1A1A1A]">
                    <?php echo esc_html($fagansvarlig_navn); ?>
                </p>
                <?php endif; ?>

                <?php if ($fagansvarlig_tittel) : ?>
                <p class="text-sm text-[#5A5A5A] mt-0.5">
                    <?php echo esc_html($fagansvarlig_tittel); ?>
                </p>
                <?php endif; ?>

                <?php if ($bedrift_navn) : ?>
                <p class="text-sm text-[#5A5A5A] mt-0.5">
                    <?php if ($bedrift_url) : ?>
                    <a href="<?php echo esc_url($bedrift_url); ?>" class="hover:text-[#1A1A1A] hover:underline">
                        <?php echo esc_html($bedrift_navn); ?>
                    </a>
                    <?php else : ?>
                    <?php echo esc_html($bedrift_navn); ?>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php else : ?>
        <p class="text-sm text-[#5A5A5A]">
            Fagansvarlig er ikke satt enna.
        </p>
        <?php endif; ?>
    </div>

    <!-- Card 3: CTA -->
    <div class="bg-[#F7F5EF] rounded-lg border border-[#E5E0D8] p-6 flex flex-col">
        <h3 class="text-sm font-semibold text-[#5A5A5A] uppercase tracking-wide mb-4">
            Bli med
        </h3>

        <p class="text-sm text-[#5A5A5A] mb-4 flex-1">
            Registrer interesse for <?php echo esc_html($temagruppe_navn); ?> via din bedriftsprofil i Min Side.
        </p>

        <?php
        bimverdi_button([
            'text' => 'Ga til Min Side',
            'variant' => 'primary',
            'icon' => 'arrow-right',
            'icon_position' => 'right',
            'href' => home_url('/min-side/'),
            'full_width' => true,
        ]);
        ?>
    </div>

</section>
