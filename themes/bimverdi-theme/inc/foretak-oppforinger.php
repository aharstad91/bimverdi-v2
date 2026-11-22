<?php
/**
 * Foretakets oppføringer: felles logikk for deltakerprofilen og foretaksfilteret
 * i verktøykatalogen og artikkelarkivet.
 *
 * Statusfeltet på /deltakere/<slug>/ lenker antall verktøy og artikler til
 * katalogene med ?deltaker=<slug> (Bård, 22.11.2026). Tallet på profilen og
 * treffene i filteret skal bygge på samme regel, derfor ligger den her.
 *
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

/**
 * Gjør ?deltaker=<slug> (eller ID) om til et publisert foretak.
 *
 * @param mixed $raw
 * @return int Foretak-ID, eller 0 hvis ugyldig.
 */
function bv_foretak_fra_param($raw) {
    if (is_array($raw) || $raw === null || $raw === '') {
        return 0;
    }

    $raw  = sanitize_text_field((string) $raw);
    $post = ctype_digit($raw)
        ? get_post((int) $raw)
        : get_page_by_path(sanitize_title($raw), OBJECT, 'foretak');

    if (!$post || $post->post_type !== 'foretak' || $post->post_status !== 'publish') {
        return 0;
    }

    return (int) $post->ID;
}

/**
 * Foretaket fra gjeldende URL (?deltaker=...).
 * NB: ikke ?foretak= — det er query-varen til posttypen foretak, og WordPress
 * viser da foretakssiden i stedet for arkivet.
 *
 * @return int
 */
function bv_foretak_filter_fra_url() {
    return bv_foretak_fra_param($_GET['deltaker'] ?? '');
}

/**
 * Publiserte artikler fra foretaket: artikkelens foretaksfelt, eller en ansatt
 * i foretaket som medforfatter. Samme regel som deltakerprofilen alltid har brukt.
 *
 * @param int $company_id
 * @return int[]
 */
function bv_foretak_artikkel_ids($company_id) {
    $company_id = (int) $company_id;
    if (!$company_id) {
        return array();
    }

    $ids = get_posts(array(
        'post_type'      => 'artikkel',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'   => 'artikkel_bedrift',
                'value' => $company_id,
            ),
        ),
    ));

    $user_ids = get_users(array(
        'meta_key'   => 'bim_verdi_company_id',
        'meta_value' => $company_id,
        'fields'     => 'ID',
    ));

    foreach ($user_ids as $uid) {
        $ids = array_merge($ids, get_posts(array(
            'post_type'      => 'artikkel',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => 'artikkel_medforfattere',
                    'value'   => '"' . (int) $uid . '"',
                    'compare' => 'LIKE',
                ),
            ),
        )));
    }

    return array_values(array_unique(array_map('intval', $ids)));
}

/**
 * Stripe over katalogen: «Viser verktøy fra X · Vis alle».
 *
 * @param int    $foretak_id
 * @param string $hva       F.eks. «verktøy» eller «artikler».
 * @param string $vis_alle  URL uten foretaksfilter.
 */
function bv_foretak_filter_stripe($foretak_id, $hva, $vis_alle) {
    if (!$foretak_id) {
        return;
    }
    ?>
    <div class="mb-6 flex flex-wrap items-center gap-x-3 gap-y-2 rounded-lg border bg-[#FFF4EE] px-4 py-3 text-sm text-[#1A1A1A]" style="border-color:#F6D3C2">
        <span>
            Viser <?php echo esc_html($hva); ?> fra
            <a href="<?php echo esc_url(get_permalink($foretak_id)); ?>" class="font-semibold underline"><?php echo esc_html(get_the_title($foretak_id)); ?></a>
        </span>
        <a href="<?php echo esc_url($vis_alle); ?>" class="ml-auto inline-flex items-center gap-1 font-medium text-[#57534E] hover:text-[#111827]">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            Vis alle
        </a>
    </div>
    <?php
}
