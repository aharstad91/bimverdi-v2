<?php
/**
 * Delingsvalg — LinkedIn, e-post og «Kopier lenke» i én rad.
 *
 * Bård, Trello #348 punkt 9 (10.09.2026): «Legg inn delingsvalg øverst på alle
 * sider (artikler, verktøy, kunnskapskilder etc.) — øverst på ALLE sider, ikke
 * nederst som i ARTIKLER.» Raden lå tidligere innbakt nederst i
 * single-artikkel.php; den er løftet ut hit så alle malene deler samme markup
 * og samme logging.
 *
 * Alle tre knappene bærer data-bv-del-post/-kanal, som JS-en i
 * parts/components/del-knapp.php lytter på — delingene havner dermed i
 * Innstillinger → Delingslogg uansett hvilken kanal som brukes.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Skriv ut delingsraden.
 *
 * @param array $args {
 *     @type int|WP_Post|null $post   Posten som deles. Standard: gjeldende.
 *     @type string           $label  Ledeteksten foran knappene.
 *     @type string           $class  Ekstra klasser på ytterste element.
 * }
 * @return void
 */
function bimverdi_delingsvalg($args = array()) {
    $args = wp_parse_args($args, array(
        'post'  => null,
        'label' => 'Del denne siden:',
        'class' => '',
    ));

    $post = get_post($args['post']);
    if (!$post || !function_exists('bimverdi_del_knapp')) {
        return;
    }

    $permalink = get_permalink($post);
    bimverdi_delingsvalg_stil();
    ?>
    <div class="bv-delingsvalg <?php echo esc_attr($args['class']); ?>">
        <span class="bv-delingsvalg__ledetekst"><?php echo esc_html($args['label']); ?></span>

        <a class="bv-del-knapp" target="_blank" rel="noopener"
           href="<?php echo esc_url('https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($permalink)); ?>"
           data-bv-del-post="<?php echo esc_attr($post->ID); ?>"
           data-bv-del-kanal="linkedin">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
            LinkedIn
        </a>

        <?php bimverdi_del_knapp(array('post' => $post, 'text' => 'E-post')); ?>

        <button type="button" class="bv-del-knapp bv-delingsvalg__kopier"
                data-bv-del-post="<?php echo esc_attr($post->ID); ?>"
                data-bv-del-kanal="kopier"
                data-bv-kopier-url="<?php echo esc_url($permalink); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            <span class="bv-delingsvalg__kopier-tekst">Kopier lenke</span>
        </button>
    </div>
    <?php
}

/**
 * Stil og kopier-oppførsel, skrevet ut én gang per sidevisning.
 *
 * @return void
 */
function bimverdi_delingsvalg_stil() {
    static $skrevet = false;
    if ($skrevet) {
        return;
    }
    $skrevet = true;
    ?>
    <style>
    .bv-delingsvalg { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
    .bv-delingsvalg__ledetekst { font-size: 14px; color: #5A5A5A; }
    .bv-delingsvalg .bv-del-knapp { cursor: pointer; font-family: inherit; }
    </style>
    <script>
    (function () {
        /* Klippebordet er ikke tilgjengelig over http eller i eldre nettlesere.
           Da faller vi tilbake til en usynlig tekstboks + execCommand, slik at
           knappen aldri bare «gjør ingenting». */
        function kopier(tekst) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(tekst);
            }
            return new Promise(function (ok, feil) {
                var boks = document.createElement('textarea');
                boks.value = tekst;
                boks.setAttribute('readonly', '');
                boks.style.position = 'fixed';
                boks.style.opacity = '0';
                document.body.appendChild(boks);
                boks.select();
                try { document.execCommand('copy') ? ok() : feil(); }
                catch (e) { feil(e); }
                finally { document.body.removeChild(boks); }
            });
        }

        document.addEventListener('click', function (e) {
            var knapp = e.target.closest ? e.target.closest('[data-bv-kopier-url]') : null;
            if (!knapp) { return; }
            var etikett = knapp.querySelector('.bv-delingsvalg__kopier-tekst');
            kopier(knapp.getAttribute('data-bv-kopier-url')).then(function () {
                if (!etikett) { return; }
                var opprinnelig = etikett.textContent;
                etikett.textContent = 'Kopiert!';
                setTimeout(function () { etikett.textContent = opprinnelig; }, 2000);
            }).catch(function () {
                if (etikett) { etikett.textContent = 'Kopier manuelt'; }
            });
        });
    })();
    </script>
    <?php
}
