<?php
/**
 * Min Side - Meldinger (krav 31, runde A)
 *
 * Liste over brukerens samtaler, nyeste først. Logikk og data ligger i
 * mu-plugins/bimverdi-meldinger.php. Uten foretakskobling vises blokk-veggen
 * fra krav 22.
 *
 * Egen, avgrenset CSS (ingen nye Tailwind-klasser, så CSS-bygg trengs ikke).
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$bruker_id = get_current_user_id();

if (!function_exists('bv_meldinger_traader')) {
    echo '<p>' . esc_html__('Meldinger er ikke tilgjengelig akkurat nå.', 'bimverdi') . '</p>';
    return;
}

if (!bv_meldinger_har_foretak($bruker_id)) {
    get_template_part('parts/components/block-vegg', null, [
        'oppgave_label' => 'lukkede meldinger',
        'retry_url'     => home_url('/min-side/?retry=1'),
    ]);
    return;
}

$traader = bv_meldinger_traader($bruker_id);

$actions = [];
if (function_exists('bv_meldinger_start_synlig') && bv_meldinger_start_synlig()) {
    $actions[] = [
        'text'    => __('Ny lukket melding', 'bimverdi'),
        'url'     => bv_meldinger_ny_url(),
        'variant' => 'primary',
        'icon'    => 'plus',
    ];
}
?>

<?php get_template_part('parts/components/page-header', null, [
    'title'       => __('Lukkede meldinger', 'bimverdi'),
    'description' => __('Samtaler mellom deg og én annen bruker. Bare dere to ser dem.', 'bimverdi'),
    'actions'     => $actions,
]); ?>

<div class="bv-meldinger">
    <?php if (isset($_GET['skjult'])): ?>
        <p class="bv-meldinger__ok" role="status"><?php esc_html_e('Samtalen er skjult.', 'bimverdi'); ?></p>
    <?php endif; ?>
    <?php if (!$traader): ?>
        <p class="bv-meldinger__tom"><?php esc_html_e('Du har ingen lukkede meldinger ennå.', 'bimverdi'); ?></p>
    <?php else: ?>
        <ul class="bv-meldinger__liste">
            <?php foreach ($traader as $t): ?>
                <li class="bv-meldinger__rad<?php echo $t['uleste'] ? ' bv-meldinger__rad--ulest' : ''; ?>">
                    <a class="bv-meldinger__lenke" href="<?php echo esc_url(bv_meldinger_traad_url($t['traad_id'])); ?>">
                        <span class="bv-meldinger__topp">
                            <span class="bv-meldinger__navn"><?php echo esc_html($t['motpart_navn']); ?></span>
                            <?php if ($t['motpart_foretak'] !== ''): ?>
                                <span class="bv-meldinger__foretak"><?php echo esc_html($t['motpart_foretak']); ?></span>
                            <?php endif; ?>
                            <span class="bv-meldinger__tid"><?php echo esc_html(bv_meldinger_tid($t['siste_melding'])); ?></span>
                        </span>
                        <span class="bv-meldinger__utdrag">
                            <?php if ($t['siste_fra_meg']): ?><span class="bv-meldinger__deg"><?php esc_html_e('Du:', 'bimverdi'); ?></span> <?php endif; ?>
                            <?php echo esc_html($t['utdrag']); ?>
                        </span>
                        <?php if ($t['uleste']): ?>
                            <span class="bv-meldinger__ulest">
                                <?php echo esc_html(sprintf(_n('%d ulest', '%d uleste', $t['uleste'], 'bimverdi'), $t['uleste'])); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<style>
.bv-meldinger { max-width: 960px; }
.bv-meldinger__ok { margin: 0 0 16px; color: #166534; font-size: 15px; }
.bv-meldinger__tom { color: #5A5A5A; padding: 24px 0; border-top: 1px solid #E5E0D5; }
.bv-meldinger__liste { list-style: none; margin: 0; padding: 0; border-top: 1px solid #E5E0D5; }
.bv-meldinger__rad { border-bottom: 1px solid #E5E0D5; }
.bv-meldinger__lenke { display: block; padding: 16px 4px; color: #1A1A1A; text-decoration: none; }
.bv-meldinger__lenke:hover { background: #FAFAF9; }
.bv-meldinger__lenke:focus-visible { outline: 2px solid #FF8B5E; outline-offset: 2px; }
.bv-meldinger__topp { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 12px; }
.bv-meldinger__navn { font-weight: 500; }
.bv-meldinger__rad--ulest .bv-meldinger__navn { font-weight: 700; }
.bv-meldinger__foretak { color: #5A5A5A; font-size: 14px; }
.bv-meldinger__tid { margin-left: auto; color: #78716C; font-size: 13px; white-space: nowrap; }
.bv-meldinger__utdrag { display: block; margin-top: 4px; color: #57534E; font-size: 15px; overflow-wrap: anywhere; }
.bv-meldinger__deg { color: #78716C; }
.bv-meldinger__ulest { display: inline-block; margin-top: 6px; font-size: 13px; font-weight: 600; color: #C2410C; }
@media (max-width: 600px) {
    .bv-meldinger__tid { margin-left: 0; width: 100%; }
}
</style>
