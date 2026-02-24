<?php
/**
 * Temagruppe CTA - Bli med
 *
 * Call-to-action section encouraging users to join the theme group.
 * Uses soft panel style for action areas per UI contract.
 *
 * @package BimVerdi_Theme
 *
 * @param array $args {
 *     @type int $post_id Post ID of the temagruppe
 * }
 */

if (!defined('ABSPATH')) exit;

$temagruppe_navn = get_the_title();
?>

<section class="bg-gray-50 rounded-lg p-5">
    <h3 class="text-base font-semibold text-[#1A1A1A] mb-2">
        Bli med i <?php echo esc_html($temagruppe_navn); ?>
    </h3>

    <p class="text-sm text-[#5A5A5A] mb-4">
        Registrer interesse for denne temagruppen via din bedriftsprofil i Min Side.
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
</section>
