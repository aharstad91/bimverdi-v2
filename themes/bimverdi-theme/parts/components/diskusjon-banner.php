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
 * Trello #348 punkt 8 (10.09.2026): banneret skal stå «øverst på ALLE nye/gamle
 * sider og poster som har diskusjonsfelt». De seks CPT-malene kaller det
 * eksplisitt; sider (som /prosjekter/byggchat/) og alt som måtte komme til
 * senere fanges av the_content-nettet nederst i fila. Dobbelt-rendering er
 * umulig fordi hvert kall merkes av per post.
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
    if (bimverdi_diskusjon_banner_skrevet($post->ID, true)) {
        return; // Allerede skrevet ut av malen — nettet under skal ikke gjenta det.
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

/**
 * Har banneret allerede blitt skrevet ut for denne posten i denne visningen?
 *
 * @param int  $post_id Posten.
 * @param bool $marker  Sett true for å markere den som skrevet ut.
 * @return bool Statusen FØR et eventuelt merke ble satt.
 */
function bimverdi_diskusjon_banner_skrevet($post_id, $marker = false) {
    static $skrevet = array();
    $post_id = (int) $post_id;
    $var     = isset($skrevet[$post_id]);
    if ($marker) {
        $skrevet[$post_id] = true;
    }
    return $var;
}

/**
 * Sikkerhetsnett for punkt 8: legg banneret øverst i innholdet på enhver
 * singelvisning med aktiv diskusjon der malen ikke allerede har gjort det.
 *
 * Prioritet 5 — før wpautop og resten av innholdsfiltrene, så markupen vår
 * ikke pakkes inn i avsnittstagger. Vaktene sikrer at det bare skjer i
 * hovedløkka på en singelside: utdrag i arkiv og lister er uberørt.
 *
 * @param string $innhold Innholdet.
 * @return string
 */
add_filter('the_content', function ($innhold) {
    if (is_admin() || !is_singular() || !in_the_loop() || !is_main_query()) {
        return $innhold;
    }
    if (!function_exists('bimverdi_diskusjon_aktiv') || !bimverdi_diskusjon_aktiv()) {
        return $innhold;
    }
    if (bimverdi_diskusjon_banner_skrevet(get_the_ID())) {
        return $innhold;
    }

    ob_start();
    bimverdi_diskusjon_banner();
    return ob_get_clean() . $innhold;
}, 5);
