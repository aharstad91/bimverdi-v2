<?php
/**
 * Min Side - Mine prosjekter
 *
 * Viser Bårds plugin BV pilotprosjekt (bv-pilot-prosjekt) inne i Min Side.
 * Selve lista (egne, delt med meg, kolleger) rendres av pluginet via
 * bvp_mine_prosjekter(); vi legger bare på vår egen sideoverskrift.
 * Pluginet finnes kun på prod, så siden faller tilbake til en melding
 * hvis det ikke er aktivt.
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$bvp_aktiv = function_exists('bvp_mine_prosjekter');

$actions = [];
if ($bvp_aktiv && class_exists('BVP_Minside')) {
    $actions[] = [
        'text'    => __('Registrer nytt prosjekt', 'bimverdi'),
        'url'     => add_query_arg('bvp_view', 'skjema', BVP_Minside::skjema_url()),
        'variant' => 'primary',
        'icon'    => 'plus',
    ];
}
?>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title'       => __('Mine prosjekter', 'bimverdi'),
    'description' => __('Piloter og prosjekter du har registrert, delt med deg eller registrert av kolleger.', 'bimverdi'),
    'actions'     => $actions,
]); ?>

<?php if ($bvp_aktiv): ?>
    <style>
        /* Pluginet sentrerer seg i 820px og bruker blå aksent; inne i Min Side skal det
           følge sidebredden og våre mørke knapper. Bårds plugin-CSS røres ikke. */
        .bv-minside-prosjekter .bvp { max-width: none; margin-inline: 0; --bvp-accent: #1A1A1A; }
    </style>
    <div class="bv-minside-prosjekter">
    <?php
    // Tittel og knapp ligger i sideoverskriften over, så de slås av i pluginets visning.
    echo bvp_mine_prosjekter([
        'tittel' => '',
        'knapp'  => false,
    ]);
    ?>
    </div>
<?php else: ?>
    <p class="text-[#5A5A5A]"><?php _e('Prosjektregistrering er ikke tilgjengelig akkurat nå.', 'bimverdi'); ?></p>
<?php endif; ?>
