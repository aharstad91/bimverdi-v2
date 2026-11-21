<?php
/**
 * Min Side - Én samtale (krav 31, runde A)
 *
 * /min-side/meldinger/traad/?id=123. Bare de to partene ser samtalen.
 * Å åpne siden markerer motpartens meldinger som lest. Svar sendes med
 * vanlig POST (håndteres i mu-plugins/bimverdi-meldinger.php).
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$bruker_id = get_current_user_id();

if (!function_exists('bv_meldinger_hent_traad')) {
    echo '<p>' . esc_html__('Meldinger er ikke tilgjengelig akkurat nå.', 'bimverdi') . '</p>';
    return;
}

if (!bv_meldinger_har_foretak($bruker_id)) {
    get_template_part('parts/components/block-vegg', null, [
        'oppgave_label' => 'meldinger',
        'retry_url'     => home_url('/min-side/?retry=1'),
    ]);
    return;
}

$traad_id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$traad     = bv_meldinger_hent_traad($traad_id);
$liste_url = home_url('/min-side/meldinger/');

if (!bv_meldinger_er_deltaker($traad, $bruker_id)) : ?>
    <?php get_template_part('parts/components/page-header', null, [
        'title' => __('Meldinger', 'bimverdi'),
    ]); ?>
    <p style="color: #5A5A5A;"><?php esc_html_e('Fant ikke samtalen.', 'bimverdi'); ?>
        <a href="<?php echo esc_url($liste_url); ?>" style="color: #1A1A1A; text-decoration: underline;"><?php esc_html_e('Tilbake til meldinger', 'bimverdi'); ?></a></p>
    <?php
    return;
endif;

bv_meldinger_marker_lest($bruker_id, $traad_id);

$motpart_id      = bv_meldinger_motpart($traad, $bruker_id);
$motpart_navn    = bv_meldinger_navn($motpart_id);
$motpart_foretak = bv_meldinger_foretaksnavn($motpart_id);
$meldinger       = bv_meldinger_i_traad($traad_id);

// Kan brukeren svare nå? Viser årsaken i stedet for skjemaet hvis ikke.
$kan_svare = bv_kan_sende_melding($bruker_id, $motpart_id);
$sperre    = is_wp_error($kan_svare) && $kan_svare->get_error_code() !== 'bv_melding_for_mange'
    ? $kan_svare->get_error_message()
    : '';

// Feil fra forrige innsending, og eventuelt utkast som skal tilbake i feltet.
$feilkode = isset($_GET['bv_feil']) ? sanitize_key(wp_unslash($_GET['bv_feil'])) : '';
$feiltekst = '';
if ($feilkode !== '') {
    $feiltekst = $feilkode === 'utlopt'
        ? __('Skjemaet utløp. Prøv igjen.', 'bimverdi')
        : bv_meldinger_feiltekst($feilkode, $motpart_id);
}
$utkast_nokkel = 'bv_meld_utkast_' . $bruker_id . '_' . $traad_id;
$utkast = (string) get_transient($utkast_nokkel);
if ($utkast !== '') {
    delete_transient($utkast_nokkel);
}

$undertittel = $motpart_foretak !== ''
    ? sprintf(__('Samtale med %1$s i %2$s', 'bimverdi'), $motpart_navn, $motpart_foretak)
    : sprintf(__('Samtale med %s', 'bimverdi'), $motpart_navn);
?>

<?php get_template_part('parts/components/page-header', null, [
    'title'       => $motpart_navn,
    'description' => $undertittel,
    'actions'     => [[
        'text'    => __('Alle meldinger', 'bimverdi'),
        'url'     => $liste_url,
        'variant' => 'secondary',
        'icon'    => 'arrow-left',
    ]],
]); ?>

<div class="bv-samtale">
    <ol class="bv-samtale__liste">
        <?php $antall = count($meldinger); foreach ($meldinger as $i => $m): $fra_meg = $m['avsender_id'] === $bruker_id; ?>
            <li class="bv-samtale__melding<?php echo $fra_meg ? ' bv-samtale__melding--meg' : ''; ?>"<?php echo ($i === $antall - 1) ? ' id="siste"' : ''; ?>>
                <div class="bv-samtale__meta">
                    <span class="bv-samtale__avsender"><?php echo $fra_meg ? esc_html__('Du', 'bimverdi') : esc_html($m['avsender']); ?></span>
                    <?php if (!$fra_meg && $m['foretak'] !== ''): ?>
                        <span class="bv-samtale__foretak"><?php echo esc_html($m['foretak']); ?></span>
                    <?php endif; ?>
                    <time class="bv-samtale__tid" datetime="<?php echo esc_attr(mysql2date('c', $m['opprettet'])); ?>"><?php echo esc_html(bv_meldinger_tid($m['opprettet'])); ?></time>
                </div>
                <div class="bv-samtale__tekst"><?php echo nl2br(esc_html($m['tekst'])); ?></div>
            </li>
        <?php endforeach; ?>
    </ol>

    <?php if (isset($_GET['sendt']) && $feiltekst === ''): ?>
        <p class="bv-samtale__ok" role="status"><?php esc_html_e('Meldingen er sendt.', 'bimverdi'); ?></p>
    <?php endif; ?>

    <?php if ($sperre !== ''): ?>
        <p class="bv-samtale__sperre"><?php echo esc_html($sperre); ?></p>
    <?php else: ?>
        <form method="post" action="" class="bv-samtale__skjema">
            <?php wp_nonce_field('bv_melding_svar_' . $traad_id); ?>
            <input type="hidden" name="bv_melding_svar" value="1">
            <input type="hidden" name="traad_id" value="<?php echo (int) $traad_id; ?>">

            <?php if ($feiltekst !== ''): ?>
                <p class="bv-samtale__feil" role="alert"><?php echo esc_html($feiltekst); ?></p>
            <?php endif; ?>

            <label for="bv-melding-tekst" class="bv-samtale__etikett"><?php esc_html_e('Svar', 'bimverdi'); ?></label>
            <textarea id="bv-melding-tekst" name="tekst" rows="5" maxlength="<?php echo (int) BIMVERDI_MELDINGER_MAKS_TEGN; ?>"
                      class="bv-samtale__felt" required><?php echo esc_textarea($utkast); ?></textarea>
            <p class="bv-samtale__hjelp"><?php esc_html_e('Ren tekst, maks 5 000 tegn. Mottakeren får varsel på Min Side og på e-post.', 'bimverdi'); ?></p>

            <button type="submit" class="bv-btn bv-btn--primary px-6 py-2.5 text-sm font-semibold rounded-lg">
                <?php esc_html_e('Send', 'bimverdi'); ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<style>
.bv-samtale { max-width: 760px; }
.bv-samtale__liste { list-style: none; margin: 0 0 32px; padding: 0; border-top: 1px solid #E5E0D5; }
.bv-samtale__melding { padding: 16px 0; border-bottom: 1px solid #E5E0D5; }
.bv-samtale__melding--meg { padding-left: 24px; }
.bv-samtale__meta { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 12px; margin-bottom: 6px; }
.bv-samtale__avsender { font-weight: 600; color: #1A1A1A; }
.bv-samtale__melding--meg .bv-samtale__avsender { color: #57534E; }
.bv-samtale__foretak { font-size: 14px; color: #5A5A5A; }
.bv-samtale__tid { margin-left: auto; font-size: 13px; color: #78716C; white-space: nowrap; }
.bv-samtale__tekst { font-size: 16px; line-height: 1.6; color: #1A1A1A; overflow-wrap: anywhere; }
.bv-samtale__ok { margin: 0 0 16px; color: #166534; font-size: 15px; }
.bv-samtale__sperre { padding: 16px 0; border-top: 1px solid #E5E0D5; color: #5A5A5A; }
.bv-samtale__feil { margin: 0 0 12px; color: #B91C1C; font-size: 15px; }
.bv-samtale__etikett { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 600; color: #1A1A1A; }
.bv-samtale__felt { display: block; width: 100%; box-sizing: border-box; padding: 12px 16px; border: 1px solid #E5E0D5; border-radius: 8px; font: inherit; color: #1A1A1A; }
.bv-samtale__felt:focus { outline: 2px solid #FF8B5E; outline-offset: 0; border-color: transparent; }
.bv-samtale__hjelp { margin: 6px 0 16px; font-size: 12px; color: #888888; }
@media (max-width: 600px) {
    .bv-samtale__melding--meg { padding-left: 12px; }
    .bv-samtale__tid { margin-left: 0; width: 100%; }
}
</style>
