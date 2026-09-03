<?php
/**
 * Diskusjonsbanner — peker mot kommentartråden nederst på siden.
 *
 * Bård, Trello #347 punkt 2 (bekreftet muntlig 03.09.2026): øverst på
 * artikler, verktøy, deltakere, arrangement og temagrupper skal det stå en
 * oppfordring om å bruke diskusjonen, med lenke til innlogging.
 *
 * Banneret rendres KUN når tråden faktisk er aktiv for posten
 * (`bimverdi_diskusjon_aktiv()`). Ellers ville det lovet noe som ikke finnes
 * lenger ned på siden — f.eks. på kunnskapskilder, der tråden står av (R17).
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Skriv ut diskusjonsbanneret for gjeldende post.
 *
 * @param array $args {
 *     @type int|WP_Post|null $post   Posten banneret gjelder. Standard: gjeldende.
 *     @type string           $class  Ekstra klasser på ytterste element.
 * }
 * @return void
 */
function bimverdi_diskusjon_banner($args = array()) {
    $args = wp_parse_args($args, array(
        'post'  => null,
        'class' => '',
    ));

    if (!function_exists('bimverdi_diskusjon_aktiv')) {
        return;
    }

    $post = get_post($args['post']);
    if (!$post || !bimverdi_diskusjon_aktiv($post)) {
        return;
    }

    $innlogget = is_user_logged_in();
    $permalink = get_permalink($post);
    $logg_inn  = home_url('/logg-inn/?redirect_to=' . rawurlencode($permalink . '#diskusjon'));

    bimverdi_diskusjon_banner_stil();
    ?>
    <div class="bv-diskusjonsbanner <?php echo esc_attr($args['class']); ?>" data-bv-diskusjonsbanner>
        <span class="bv-diskusjonsbanner__ikon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
        </span>

        <p class="bv-diskusjonsbanner__tekst">
            <?php if ($innlogget) : ?>
                <strong>Se diskusjonen nederst på siden.</strong>
                Du kan tagge @navn på brukere i nettverket og følge nye innspill — de blir varslet på e-post.
                Del gjerne siden med andre.
            <?php else : ?>
                <strong>Se «diskusjon» nederst på siden. Du kan tagge @navn på brukere i nettverket og følge nye innspill.</strong>
                <a href="<?php echo esc_url($logg_inn); ?>">Logg inn</a> og bruk muligheten.
                Du blir varslet på e-post hvis noen tagger deg. Del gjerne siden med andre.
            <?php endif; ?>
        </p>

        <a class="bv-diskusjonsbanner__hopp" href="#diskusjon">Til diskusjonen</a>

        <button type="button" class="bv-diskusjonsbanner__lukk" aria-label="Skjul denne meldingen">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>
    <?php
}

/**
 * Stil og lukke-oppførsel, skrevet ut én gang per sidevisning.
 *
 * Ligger i komponenten fordi banneret kalles fra fem maler — en egen
 * CSS-/JS-fil for tolv regler ville kostet to ekstra forespørsler.
 *
 * Lukking huskes i sessionStorage, ikke localStorage: Bård vil at meldingen
 * skal synes. Den forsvinner for resten av økten, og er tilbake neste gang.
 *
 * @return void
 */
function bimverdi_diskusjon_banner_stil() {
    static $skrevet = false;
    if ($skrevet) {
        return;
    }
    $skrevet = true;
    ?>
    <style>
    .bv-diskusjonsbanner {
        display: flex; align-items: flex-start; gap: 12px;
        background: #EFE9DE; border-left: 3px solid #FF8B5E;
        padding: 12px 16px; margin-bottom: 24px; border-radius: 4px;
    }
    .bv-diskusjonsbanner__ikon { color: #FF8B5E; flex-shrink: 0; margin-top: 2px; }
    .bv-diskusjonsbanner__tekst {
        margin: 0; font-size: 14px; line-height: 1.55; color: #1A1A1A; flex: 1 1 auto;
    }
    .bv-diskusjonsbanner__tekst strong { font-weight: 600; }
    .bv-diskusjonsbanner__tekst a,
    .bv-diskusjonsbanner__hopp { color: #772015; text-decoration: underline; }
    .bv-diskusjonsbanner__tekst a:hover,
    .bv-diskusjonsbanner__hopp:hover { opacity: .75; }
    .bv-diskusjonsbanner__hopp {
        flex-shrink: 0; font-size: 14px; font-weight: 600; white-space: nowrap; margin-top: 1px;
    }
    .bv-diskusjonsbanner__lukk {
        flex-shrink: 0; background: none; border: 0; padding: 2px; cursor: pointer;
        color: #5A5A5A; line-height: 0; border-radius: 3px;
    }
    .bv-diskusjonsbanner__lukk:hover { color: #1A1A1A; background: rgba(0,0,0,.06); }
    @media (max-width: 640px) {
        .bv-diskusjonsbanner { flex-wrap: wrap; }
        .bv-diskusjonsbanner__tekst { flex-basis: 100%; order: 3; }
        .bv-diskusjonsbanner__hopp { order: 4; }
    }
    </style>
    <script>
    (function () {
        var NOKKEL = 'bv_diskusjonsbanner_skjult';
        var lagret = null;
        try { lagret = window.sessionStorage; } catch (e) { lagret = null; }

        document.addEventListener('DOMContentLoaded', function () {
            var bannere = document.querySelectorAll('[data-bv-diskusjonsbanner]');
            if (!bannere.length) { return; }

            if (lagret && lagret.getItem(NOKKEL) === '1') {
                bannere.forEach(function (b) { b.hidden = true; });
                return;
            }

            bannere.forEach(function (banner) {
                var knapp = banner.querySelector('.bv-diskusjonsbanner__lukk');
                if (!knapp) { return; }
                knapp.addEventListener('click', function () {
                    banner.hidden = true;
                    if (lagret) { try { lagret.setItem(NOKKEL, '1'); } catch (e) {} }
                });
            });
        });
    })();
    </script>
    <?php
}
