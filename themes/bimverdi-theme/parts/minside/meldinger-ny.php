<?php
/**
 * Min Side - Ny lukket melding (krav 31 v3, runde B)
 *
 * /min-side/meldinger/ny/           → søk etter mottaker (navn eller foretak)
 * /min-side/meldinger/ny/?til=ID    → skriv til én mottaker
 *
 * Finnes samtalen allerede, sendes brukeren rett til den (template_redirect i
 * mu-plugins/bimverdi-meldinger.php). Vanlige skjemaer, ingen JS (B-002).
 * Før lansering kan bare administratorer bruke siden.
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$bruker_id = get_current_user_id();
$liste_url = home_url('/min-side/meldinger/');

if (!function_exists('bv_meldinger_sok_mottakere')) {
    echo '<p>' . esc_html__('Lukkede meldinger er ikke tilgjengelig akkurat nå.', 'bimverdi') . '</p>';
    return;
}

if (!bv_meldinger_har_foretak($bruker_id)) {
    get_template_part('parts/components/block-vegg', null, [
        'oppgave_label' => 'lukkede meldinger',
        'tekst'         => bv_meldinger_vegg_tekst(),
        'retry_url'     => home_url('/min-side/?retry=1'),
    ]);
    return;
}

$tilbake = [[
    'text'    => __('Alle lukkede meldinger', 'bimverdi'),
    'url'     => $liste_url,
    'variant' => 'secondary',
    'icon'    => 'arrow-left',
]];

if (!bv_meldinger_kan_starte($bruker_id)) {
    get_template_part('parts/components/page-header', null, [
        'title'   => __('Ny lukket melding', 'bimverdi'),
        'actions' => $tilbake,
    ]);
    echo '<p style="color: #5A5A5A;">' . esc_html(bv_meldinger_feiltekst('ikke_lansert')) . '</p>';
    return;
}

$til = isset($_GET['til']) ? (int) $_GET['til'] : 0;

/* ---------------------------------------------------------------------
 * Skriv til én mottaker
 * --------------------------------------------------------------------- */
if ($til) :
    $kan = bv_kan_sende_melding($bruker_id, $til);
    $mottaker_navn    = bv_meldinger_navn($til);
    $mottaker_foretak = bv_meldinger_foretaksnavn($til);
    // «Gjelder»: siden knappen sto på. Vises bare hvis begge kan se siden.
    $gjelder_id = function_exists('bv_meldinger_gjelder_gyldig')
        ? bv_meldinger_gjelder_gyldig(isset($_GET['fra']) ? (int) $_GET['fra'] : 0, $bruker_id, $til)
        : 0;

    $feilkode  = isset($_GET['bv_feil']) ? sanitize_key(wp_unslash($_GET['bv_feil'])) : '';
    $feiltekst = '';
    if ($feilkode !== '') {
        $feiltekst = $feilkode === 'utlopt'
            ? __('Skjemaet utløp. Prøv igjen.', 'bimverdi')
            : bv_meldinger_feiltekst($feilkode, $til);
    }
    $utkast_nokkel = 'bv_meld_utkast_ny_' . $bruker_id . '_' . $til;
    $utkast = (string) get_transient($utkast_nokkel);
    if ($utkast !== '') {
        delete_transient($utkast_nokkel);
    }

    get_template_part('parts/components/page-header', null, [
        'title'       => __('Ny lukket melding', 'bimverdi'),
        'description' => $mottaker_foretak !== ''
            ? sprintf(__('Til %1$s i %2$s. Bare dere to ser samtalen.', 'bimverdi'), $mottaker_navn, $mottaker_foretak)
            : sprintf(__('Til %s. Bare dere to ser samtalen.', 'bimverdi'), $mottaker_navn),
        'actions'     => $tilbake,
    ]);
    ?>
    <div class="bv-ny">
        <?php if (is_wp_error($kan) && $kan->get_error_code() !== 'bv_melding_for_mange'): ?>
            <p class="bv-ny__sperre"><?php echo esc_html($kan->get_error_message()); ?></p>
            <p><a class="bv-ny__lenke" href="<?php echo esc_url(bv_meldinger_ny_url()); ?>"><?php esc_html_e('Søk etter en annen mottaker', 'bimverdi'); ?></a></p>
        <?php else: ?>
            <form method="post" action="">
                <?php wp_nonce_field('bv_melding_ny_' . $til); ?>
                <input type="hidden" name="bv_melding_ny" value="1">
                <input type="hidden" name="til" value="<?php echo (int) $til; ?>">
                <?php if ($gjelder_id): ?>
                    <input type="hidden" name="gjelder" value="<?php echo (int) $gjelder_id; ?>">
                    <?php echo bv_meldinger_gjelder_html($gjelder_id, 'bv-gjelder bv-gjelder--skjema'); ?>
                <?php endif; ?>

                <?php if ($feiltekst !== ''): ?>
                    <p class="bv-ny__feil" role="alert"><?php echo esc_html($feiltekst); ?></p>
                <?php endif; ?>

                <label for="bv-ny-tekst" class="bv-ny__etikett"><?php esc_html_e('Melding', 'bimverdi'); ?></label>
                <textarea id="bv-ny-tekst" name="tekst" rows="6" maxlength="<?php echo (int) BIMVERDI_MELDINGER_MAKS_TEGN; ?>"
                          class="bv-ny__felt" required><?php echo esc_textarea($utkast); ?></textarea>
                <p class="bv-ny__hjelp"><?php esc_html_e('Ren tekst, maks 5 000 tegn. Mottakeren får varsel på Min Side og på e-post. E-postadressen din vises ikke.', 'bimverdi'); ?></p>

                <button type="submit" class="bv-btn bv-btn--primary px-6 py-2.5 text-sm font-semibold rounded-lg">
                    <?php esc_html_e('Send', 'bimverdi'); ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
<?php
/* ---------------------------------------------------------------------
 * Søk etter mottaker
 * --------------------------------------------------------------------- */
else :
    $sok   = isset($_GET['sok']) ? trim(sanitize_text_field(wp_unslash($_GET['sok']))) : '';
    $treff = $sok !== '' ? bv_meldinger_sok_mottakere($bruker_id, $sok) : [];

    get_template_part('parts/components/page-header', null, [
        'title'       => __('Ny lukket melding', 'bimverdi'),
        'description' => __('Søk etter personen du vil skrive til, på navn eller foretak.', 'bimverdi'),
        'actions'     => $tilbake,
    ]);
    ?>
    <div class="bv-ny">
        <form method="get" action="<?php echo esc_url(bv_meldinger_ny_url()); ?>" class="bv-ny__sok" role="search">
            <label for="bv-ny-sok" class="bv-ny__etikett"><?php esc_html_e('Navn eller foretak', 'bimverdi'); ?></label>
            <div class="bv-ny__sokrad">
                <input type="search" id="bv-ny-sok" name="sok" minlength="2" maxlength="60" required
                       value="<?php echo esc_attr($sok); ?>" class="bv-ny__felt" autocomplete="off">
                <button type="submit" class="bv-btn bv-btn--primary px-6 py-2.5 text-sm font-semibold rounded-lg"><?php esc_html_e('Søk', 'bimverdi'); ?></button>
            </div>
            <p class="bv-ny__hjelp"><?php esc_html_e('Minst 2 tegn. Treffene kommer mens du skriver. Du finner bare personer som tar imot lukkede meldinger.', 'bimverdi'); ?></p>
        </form>

        <div id="bv-ny-resultat" aria-live="polite">
        <?php if ($sok !== ''): ?>
            <?php if (mb_strlen($sok, 'UTF-8') < 2): ?>
                <p class="bv-ny__sperre"><?php esc_html_e('Skriv minst 2 tegn.', 'bimverdi'); ?></p>
            <?php elseif (!$treff): ?>
                <p class="bv-ny__sperre"><?php echo esc_html(sprintf(__('Ingen treff på «%s».', 'bimverdi'), $sok)); ?></p>
            <?php else: ?>
                <ul class="bv-ny__treff">
                    <?php foreach ($treff as $t): ?>
                        <li class="bv-ny__rad">
                            <span>
                                <span class="bv-ny__navn"><?php echo esc_html($t['navn']); ?></span>
                                <?php if ($t['foretak'] !== ''): ?><span class="bv-ny__foretak"><?php echo esc_html($t['foretak']); ?></span><?php endif; ?>
                            </span>
                            <a class="bv-ny__lenke" href="<?php echo esc_url(bv_meldinger_ny_url($t['id'])); ?>"><?php esc_html_e('Skriv lukket melding', 'bimverdi'); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (count($treff) >= 20): ?>
                    <p class="bv-ny__hjelp"><?php esc_html_e('Viser de 20 første treffene. Skriv mer for å snevre inn.', 'bimverdi'); ?></p>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
        </div>
    </div>

    <script>
    // Treff mens du skriver (B-002: JS for interaksjon). Uten JS virker skjemaet som før.
    (function () {
        var felt = document.getElementById('bv-ny-sok');
        var boks = document.getElementById('bv-ny-resultat');
        if (!felt || !boks || !window.fetch) return;
        var api   = <?php echo wp_json_encode(rest_url('bimverdi/v1/meldinger/mottakere')); ?>;
        var nonce = <?php echo wp_json_encode(wp_create_nonce('wp_rest')); ?>;
        var nyUrl = <?php echo wp_json_encode(bv_meldinger_ny_url()); ?>;
        var tekst = {
            skriv: <?php echo wp_json_encode(__('Skriv lukket melding', 'bimverdi')); ?>,
            ingen: <?php echo wp_json_encode(__('Ingen treff på «%s».', 'bimverdi')); ?>,
            mange: <?php echo wp_json_encode(__('Viser de 20 første treffene. Skriv mer for å snevre inn.', 'bimverdi')); ?>,
            feil:  <?php echo wp_json_encode(__('Søket feilet. Trykk «Søk» for å prøve igjen.', 'bimverdi')); ?>
        };
        var tidtaker = null, siste = '';

        function el(tag, klasse, innhold) {
            var e = document.createElement(tag);
            if (klasse) e.className = klasse;
            if (innhold) e.textContent = innhold;
            return e;
        }
        function vis(sok, treff) {
            boks.innerHTML = '';
            if (!treff.length) {
                boks.appendChild(el('p', 'bv-ny__sperre', tekst.ingen.replace('%s', sok)));
                return;
            }
            var ul = el('ul', 'bv-ny__treff');
            treff.forEach(function (t) {
                var li = el('li', 'bv-ny__rad'), span = el('span');
                span.appendChild(el('span', 'bv-ny__navn', t.navn));
                if (t.foretak) span.appendChild(el('span', 'bv-ny__foretak', t.foretak));
                var a = el('a', 'bv-ny__lenke', tekst.skriv);
                a.href = nyUrl + (nyUrl.indexOf('?') === -1 ? '?' : '&') + 'til=' + encodeURIComponent(t.id);
                li.appendChild(span); li.appendChild(a); ul.appendChild(li);
            });
            boks.appendChild(ul);
            if (treff.length >= 20) boks.appendChild(el('p', 'bv-ny__hjelp', tekst.mange));
        }
        function sok() {
            var q = felt.value.trim();
            if (q === siste) return;
            siste = q;
            if (q.length < 2) { boks.innerHTML = ''; return; }
            fetch(api + (api.indexOf('?') === -1 ? '?' : '&') + 'sok=' + encodeURIComponent(q), {
                credentials: 'same-origin',
                headers: { 'X-WP-Nonce': nonce }
            }).then(function (r) {
                if (!r.ok) throw new Error(r.status);
                return r.json();
            }).then(function (data) {
                if (felt.value.trim() !== q) return; // Utdatert svar.
                vis(q, Array.isArray(data) ? data : []);
                try { history.replaceState(null, '', nyUrl + '?sok=' + encodeURIComponent(q)); } catch (e) {}
            }).catch(function () {
                boks.innerHTML = '';
                boks.appendChild(el('p', 'bv-ny__sperre', tekst.feil));
            });
        }
        felt.addEventListener('input', function () {
            clearTimeout(tidtaker);
            tidtaker = setTimeout(sok, 300);
        });
    })();
    </script>
<?php endif; ?>

<style>
.bv-ny { max-width: 760px; }
.bv-ny__etikett { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 600; color: #1A1A1A; }
.bv-ny__felt { display: block; width: 100%; box-sizing: border-box; padding: 12px 16px; border: 1px solid #E5E0D5; border-radius: 8px; font: inherit; color: #1A1A1A; background: #FFFFFF; }
.bv-ny__felt:focus { outline: 2px solid #FF8B5E; outline-offset: 0; border-color: transparent; }
.bv-ny__sokrad { display: flex; gap: 12px; align-items: stretch; }
.bv-ny__sokrad .bv-ny__felt { flex: 1; }
.bv-ny__hjelp { margin: 6px 0 16px; font-size: 12px; color: #888888; }
.bv-ny__feil { margin: 0 0 12px; color: #B91C1C; font-size: 15px; }
.bv-ny__sperre { padding: 16px 0; border-top: 1px solid #E5E0D5; color: #5A5A5A; }
.bv-ny__treff { list-style: none; margin: 8px 0 0; padding: 0; border-top: 1px solid #E5E0D5; }
.bv-ny__rad { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: baseline; gap: 6px 16px; padding: 14px 0; border-bottom: 1px solid #E5E0D5; }
.bv-ny__navn { font-weight: 600; color: #1A1A1A; margin-right: 10px; }
.bv-ny__foretak { font-size: 14px; color: #5A5A5A; }
.bv-ny__lenke { font-size: 14px; font-weight: 500; color: #1A1A1A; text-decoration: underline; text-decoration-color: #D6D1C6; }
.bv-ny__lenke:hover { text-decoration-color: #1A1A1A; }
.bv-gjelder { margin: 0 0 16px; font-size: 14px; color: #5A5A5A; }
.bv-gjelder a { color: #1A1A1A; text-decoration: underline; text-decoration-color: #D6D1C6; }
.bv-gjelder a:hover { text-decoration-color: #1A1A1A; }
@media (max-width: 600px) {
    .bv-ny__sokrad { flex-direction: column; }
}
</style>
