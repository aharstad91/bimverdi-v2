<?php
/**
 * Universal Block-vegg (Krav 22)
 *
 * Vises når en innlogget bruker uten foretak forsøker en registrering som
 * krever foretaks-kobling (arrangement, nyhetsbrev, kunnskapskilde, m.fl.).
 *
 * Ordrett tekst per krav 22, R22.6:
 *   "Du må koble deg til ditt foretak/arbeidsgiver før du går videre"
 *
 * Usage:
 *   get_template_part('parts/components/block-vegg', null, [
 *       'oppgave_label' => 'arrangement-påmelding',
 *       'retry_url'     => home_url('/min-side/?retry=1'),
 *   ]);
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$args = $args ?? [];
$oppgave_label = isset($args['oppgave_label']) && $args['oppgave_label'] !== ''
    ? (string) $args['oppgave_label']
    : 'registreringen';
$retry_url = isset($args['retry_url']) && $args['retry_url'] !== ''
    ? (string) $args['retry_url']
    : home_url('/min-side/?retry=1');
$dashboard_url = home_url('/min-side/');
?>
<section class="bv-block-vegg" data-block-type="foretak-required">
    <div class="bv-block-vegg__inner">
        <div class="bv-block-vegg__eyebrow">Foretakskobling kreves</div>

        <h1 class="bv-block-vegg__heading">
            Du må koble deg til ditt foretak/arbeidsgiver før du går videre
        </h1>

        <p class="bv-block-vegg__body">
            Vi trenger å vite hvilket foretak du tilhører før vi kan registrere deg
            på <?php echo esc_html($oppgave_label); ?>.
            Det tar to minutter og du blir sendt tilbake hit etterpå.
        </p>

        <div class="bv-block-vegg__actions">
            <?php if (function_exists('bimverdi_button')) :
                bimverdi_button([
                    'text'    => 'Koble til foretak',
                    'variant' => 'primary',
                    'size'    => 'lg',
                    'href'    => $retry_url,
                    'icon'    => 'building-2',
                ]);
            else : ?>
                <a class="bv-btn bv-btn--primary" href="<?php echo esc_url($retry_url); ?>">Koble til foretak</a>
            <?php endif; ?>

            <a class="bv-block-vegg__secondary-link" href="<?php echo esc_url($dashboard_url); ?>">
                Tilbake til dashboard
            </a>
        </div>

        <p class="bv-block-vegg__help">
            Trenger du hjelp eller spesialhåndtering? Bruk
            <a href="https://bimverdi.no/tilbakemelding/" target="_blank" rel="noopener">tilbakemeldingsskjemaet</a>.
        </p>
    </div>
</section>

<style>
.bv-block-vegg {
    max-width: 720px;
    margin: 48px auto;
    padding: 0 24px;
    font-family: inherit;
}
.bv-block-vegg__inner {
    border-top: 1px solid #D6D1C6;
    border-bottom: 1px solid #D6D1C6;
    padding: 56px 0;
}
.bv-block-vegg__eyebrow {
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #5A5A5A;
    margin-bottom: 16px;
}
.bv-block-vegg__heading {
    font-size: 28px;
    line-height: 1.25;
    font-weight: 500;
    color: #1A1A1A;
    margin: 0 0 20px 0;
    letter-spacing: -0.01em;
}
.bv-block-vegg__body {
    font-size: 16px;
    line-height: 1.6;
    color: #1A1A1A;
    margin: 0 0 32px 0;
    max-width: 56ch;
}
.bv-block-vegg__actions {
    display: flex;
    align-items: center;
    gap: 24px;
    margin-bottom: 32px;
    flex-wrap: wrap;
}
.bv-block-vegg__secondary-link {
    color: #5A5A5A;
    text-decoration: underline;
    text-decoration-color: #D6D1C6;
    font-size: 15px;
}
.bv-block-vegg__secondary-link:hover {
    color: #1A1A1A;
    text-decoration-color: #1A1A1A;
}
.bv-block-vegg__help {
    font-size: 14px;
    color: #5A5A5A;
    margin: 0;
}
.bv-block-vegg__help a {
    color: #1A1A1A;
    text-decoration: underline;
    text-decoration-color: #D6D1C6;
}
@media (max-width: 600px) {
    .bv-block-vegg {
        margin: 24px auto;
    }
    .bv-block-vegg__heading {
        font-size: 22px;
    }
    .bv-block-vegg__inner {
        padding: 40px 0;
    }
}
</style>
