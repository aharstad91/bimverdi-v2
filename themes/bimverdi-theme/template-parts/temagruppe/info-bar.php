<?php
/**
 * Temagruppe Info Bar
 *
 * Two-column layout: Intro text (66%) + Fagradgiver/CTA sidebar (33%)
 * Placed directly under the hero section to create connection at the top.
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

// Dummy intro text (will be replaced by ACF field later)
$intro_tekst = get_field('intro_tekst', $post_id);
if (empty($intro_tekst)) {
    $intro_tekst = 'Denne temagruppen samler aktorer som jobber med ' . strtolower($temagruppe_navn) . '-relaterte problemstillinger. Vi deler erfaringer, utvikler felles losninger, og bidrar til a heve kompetansen i bransjen gjennom workshops, moter og samarbeidsprosjekter.';
}
?>

<section class="grid grid-cols-1 lg:grid-cols-3 gap-16">

    <!-- Left Column: Introduction (2/3 width) -->
    <div class="lg:col-span-2">
        <p class="text-lg text-[#5A5A5A] leading-relaxed">
            <?php echo esc_html($intro_tekst); ?>
        </p>
    </div>

    <!-- Right Column: Fagradgiver + CTA (1/3 width) -->
    <div class="lg:col-span-1 space-y-6">

        <!-- Fagradgiver -->
        <?php if ($fagansvarlig_navn) : ?>
        <div class="flex items-start gap-4">
            <!-- Profile Image -->
            <div class="flex-shrink-0">
                <?php if ($bilde_url) : ?>
                <img
                    src="<?php echo esc_url($bilde_url); ?>"
                    alt="<?php echo esc_attr($fagansvarlig_navn); ?>"
                    class="w-12 h-12 rounded-full object-cover"
                    loading="lazy"
                >
                <?php else : ?>
                <div class="w-12 h-12 rounded-full bg-[#FF8B5E] flex items-center justify-center text-white font-semibold text-sm">
                    <?php echo esc_html($initials); ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="min-w-0">
                <p class="text-xs font-medium text-[#888888] uppercase tracking-wide mb-1">Fagradgiver</p>

                <?php if ($fagansvarlig_linkedin) : ?>
                <a
                    href="<?php echo esc_url($fagansvarlig_linkedin); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-sm font-semibold text-[#1A1A1A] hover:text-[#FF8B5E] hover:underline inline-flex items-center gap-1.5"
                >
                    <?php echo esc_html($fagansvarlig_navn); ?>
                    <?php echo bimverdi_icon('linkedin', 14); ?>
                </a>
                <?php else : ?>
                <p class="text-sm font-semibold text-[#1A1A1A]">
                    <?php echo esc_html($fagansvarlig_navn); ?>
                </p>
                <?php endif; ?>

                <?php if ($fagansvarlig_tittel) : ?>
                <p class="text-sm text-[#5A5A5A]">
                    <?php echo esc_html($fagansvarlig_tittel); ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- CTA Button -->
        <div>
            <?php
            bimverdi_button([
                'text' => 'Bli med i gruppen',
                'variant' => 'primary',
                'icon' => 'arrow-right',
                'icon_position' => 'right',
                'href' => home_url('/min-side/'),
                'full_width' => true,
            ]);
            ?>
            <p class="text-xs text-[#888888] mt-2">
                Via din bedriftsprofil i Min Side
            </p>
        </div>

    </div>

</section>
