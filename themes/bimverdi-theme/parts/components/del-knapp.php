<?php
/**
 * Del-knapp — åpner e-postklienten med lenke til siden og en ferdig tekst.
 *
 * Bård, Trello #347 punkt 1. Teksten er hans: «Ta en titt på dette
 * <sidelenke> - jeg tror det kan være av interesse.»
 *
 * href er en ekte mailto-lenke, så knappen fungerer også uten JavaScript.
 * JS-en logger klikket via sendBeacon og lar default-handlingen fortsette —
 * loggingen kan altså aldri stå i veien for delingen.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Skriv ut del-knappen.
 *
 * @param array $args {
 *     @type int|WP_Post|null $post  Posten som deles. Standard: gjeldende.
 *     @type string           $text  Knappetekst.
 *     @type string           $class Ekstra klasser.
 * }
 * @return void
 */
function bimverdi_del_knapp($args = array()) {
    $args = wp_parse_args($args, array(
        'post'  => null,
        'text'  => 'Del via e-post',
        'class' => '',
    ));

    $post = get_post($args['post']);
    if (!$post) {
        return;
    }

    $permalink = get_permalink($post);
    $tittel    = get_the_title($post);

    $emne  = $tittel;
    $brod  = 'Ta en titt på dette ' . $permalink . ' - jeg tror det kan være av interesse.';
    $mailto = 'mailto:?subject=' . rawurlencode($emne) . '&body=' . rawurlencode($brod);

    bimverdi_del_knapp_script();
    ?>
    <a href="<?php echo esc_attr($mailto); ?>"
       class="bv-del-knapp <?php echo esc_attr($args['class']); ?>"
       data-bv-del-post="<?php echo esc_attr($post->ID); ?>"
       data-bv-del-kanal="epost">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
        <?php echo esc_html($args['text']); ?>
    </a>
    <?php
}

/**
 * Stil + logg-kall, skrevet ut én gang per sidevisning.
 *
 * @return void
 */
function bimverdi_del_knapp_script() {
    static $skrevet = false;
    if ($skrevet) {
        return;
    }
    $skrevet = true;

    $endepunkt = esc_url_raw(rest_url('bimverdi/v1/del-logg'));
    // Nonce kun for innloggede — utloggede skal kunne dele, og REST-ruten er
    // åpen. Nonce sendes når den finnes, så innloggede blir riktig identifisert
    // i loggen istedenfor å havne som «ikke innlogget».
    $nonce = is_user_logged_in() ? wp_create_nonce('wp_rest') : '';
    ?>
    <style>
    .bv-del-knapp {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 12px; font-size: 14px; font-weight: 500;
        color: #57534E; background: #fff;
        border: 1px solid #E5E0D8; border-radius: 8px;
        text-decoration: none; transition: background-color .15s;
    }
    .bv-del-knapp:hover { background: #FAFAF9; color: #1A1A1A; }
    </style>
    <script>
    (function () {
        var ENDEPUNKT = <?php echo wp_json_encode($endepunkt); ?>;
        var NONCE = <?php echo wp_json_encode($nonce); ?>;

        document.addEventListener('click', function (e) {
            var lenke = e.target.closest ? e.target.closest('[data-bv-del-post]') : null;
            if (!lenke) { return; }

            var data = {
                post_id: parseInt(lenke.getAttribute('data-bv-del-post'), 10),
                kanal: lenke.getAttribute('data-bv-del-kanal') || 'epost'
            };

            // sendBeacon kan ikke sette egne headere, så innloggede trenger
            // fetch for å få nonce med. Begge er «fire and forget» — vi venter
            // ikke, og mailto-lenken åpner uansett.
            try {
                if (NONCE) {
                    fetch(ENDEPUNKT, {
                        method: 'POST',
                        credentials: 'same-origin',
                        keepalive: true,
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
                        body: JSON.stringify(data)
                    }).catch(function () {});
                } else if (navigator.sendBeacon) {
                    navigator.sendBeacon(ENDEPUNKT, new Blob([JSON.stringify(data)], { type: 'application/json' }));
                } else {
                    fetch(ENDEPUNKT, {
                        method: 'POST',
                        keepalive: true,
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    }).catch(function () {});
                }
            } catch (err) { /* logging skal aldri blokkere delingen */ }
        }, true);
    })();
    </script>
    <?php
}
