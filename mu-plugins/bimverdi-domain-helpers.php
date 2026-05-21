<?php
/**
 * Plugin Name: BIM Verdi - Domain Helpers
 * Description: Helpere for hoveddomene-uttrekk (PSL), foretak-oppslag via domene og cache-purging.
 * Version: 1.0.0
 *
 * Disse helperne er grunnmuren for automatisk Tilleggskontakt-matching og
 * blocklist-håndhevelse (Krav 20 / B-027).
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

use Pdp\Rules;
use Pdp\Domain;

/**
 * Last inn Public Suffix List som en cached singleton.
 *
 * PSL-fila er "frosen" i wp-content/vendor-data/psl/. Den kan synkroniseres
 * månedlig via cron-jobb senere (Fase 2/3).
 *
 * @return Rules|null Null hvis pakke/data ikke tilgjengelig.
 */
function bimverdi_get_psl_rules() {
    static $rules = null;
    static $tried_load = false;

    if ($rules !== null || $tried_load) {
        return $rules;
    }
    $tried_load = true;

    if (!class_exists('Pdp\\Rules')) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BIMVerdi domain-helpers: Pdp\\Rules mangler — kjør composer install i wp-content/.');
        }
        return null;
    }

    $psl_path = WP_CONTENT_DIR . '/vendor-data/psl/public_suffix_list.dat';
    if (!file_exists($psl_path)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BIMVerdi domain-helpers: PSL-fila mangler på ' . $psl_path);
        }
        return null;
    }

    try {
        $rules = Rules::fromPath($psl_path);
    } catch (\Throwable $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BIMVerdi domain-helpers: PSL-parsing feilet: ' . $e->getMessage());
        }
        $rules = null;
    }

    return $rules;
}

/**
 * Trekk ut "hoveddomenet" (registrable domain) fra en e-postadresse.
 *
 * Bruker PSL for korrekt håndtering av norske 3-leddede domener (gs.oslo.no),
 * .co.uk og andre eTLDs. Alltid lowercase + trim før parsing.
 *
 * Eksempler:
 *   jens@firma.no            → "firma.no"
 *   jens@oslo.firma.no       → "firma.no"   (subdomain stripped)
 *   jens@firma.co.uk         → "firma.co.uk" (eTLD respected)
 *   jens@gs.oslo.no          → "gs.oslo.no" (3-leddet norsk public suffix)
 *   ikke-en-epost            → null
 *
 * @param string $email
 * @return string|null Lowercase root-domene, eller null ved feil.
 */
function bimverdi_extract_root_domain($email) {
    if (!is_string($email) || $email === '') {
        return null;
    }

    $email = strtolower(trim($email));

    // Hent domenedel fra e-post
    $at_pos = strrpos($email, '@');
    if ($at_pos === false || $at_pos === strlen($email) - 1) {
        return null;
    }

    $domain_part = substr($email, $at_pos + 1);
    if ($domain_part === '' || !preg_match('/^[a-z0-9.\-]+$/', $domain_part)) {
        return null;
    }

    $rules = bimverdi_get_psl_rules();
    if (!$rules) {
        // Fallback uten PSL: bruk de to siste leddene. Dette er IKKE korrekt
        // for .co.uk eller gs.oslo.no, men hindrer fatal i degraded mode.
        $parts = explode('.', $domain_part);
        $n = count($parts);
        if ($n < 2) {
            return null;
        }
        return $parts[$n - 2] . '.' . $parts[$n - 1];
    }

    try {
        $domain = Domain::fromIDNA2008($domain_part);
        $result = $rules->resolve($domain);
        $registrable = $result->registrableDomain()->toString();
        return $registrable !== '' ? $registrable : null;
    } catch (\Throwable $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BIMVerdi extract_root_domain: PSL resolve feilet for "' . $domain_part . '": ' . $e->getMessage());
        }
        return null;
    }
}

/**
 * Slå opp et foretak via hoveddomenet i en e-post.
 *
 * Bruker meta-felt bv_hoveddomene på CPT foretak. Per B-027 finnes hoveddomenet
 * kun på ett foretak om gangen — første treff returneres med warning ved
 * eventuell kollisjon.
 *
 * @param string $email
 * @return WP_Post|null Foretak-post, eller null hvis ingen treff.
 */
function bimverdi_find_foretak_by_email_domain($email) {
    $root_domain = bimverdi_extract_root_domain($email);
    if (!$root_domain) {
        return null;
    }

    $query = new WP_Query([
        'post_type'      => defined('BV_CPT_COMPANY') ? BV_CPT_COMPANY : 'foretak',
        'post_status'    => ['publish', 'pending', 'draft'],
        'posts_per_page' => 2,
        'fields'         => 'all',
        'no_found_rows'  => true,
        'meta_query'     => [
            [
                'key'     => 'bv_hoveddomene',
                'value'   => $root_domain,
                'compare' => '=',
            ],
        ],
    ]);

    if (empty($query->posts)) {
        return null;
    }

    if (count($query->posts) > 1 && defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf(
            'BIMVerdi find_foretak_by_email_domain: domain "%s" matcher flere foretak (%s). B-027 brutt — sjekk data.',
            $root_domain,
            implode(',', wp_list_pluck($query->posts, 'ID'))
        ));
    }

    return $query->posts[0];
}

/**
 * Sentral cache-purge for et foretak.
 *
 * Kalles ved opprettelse, oppdatering og sletting av foretak (Krav 20 / B-027).
 * Invalidiserer object-cache, post-cache og — hvis Servebolt-plugin er aktiv —
 * server-side cache for foretakets URL + medlemslista.
 *
 * @param int $foretak_id
 * @return void
 */
function bimverdi_purge_foretak_cache($foretak_id) {
    $foretak_id = (int) $foretak_id;
    if ($foretak_id <= 0) {
        return;
    }

    // Standard WP post-cache invalidering
    clean_post_cache($foretak_id);

    // Servebolt full-page-cache (hvis pluginen er aktiv)
    if (function_exists('sb_cache_purge_post')) {
        sb_cache_purge_post($foretak_id);
    }

    // Medlemslista (offentlig oversikt over foretak) må også purges
    $members_page = get_page_by_path('medlemmer');
    if ($members_page) {
        clean_post_cache($members_page->ID);
        if (function_exists('sb_cache_purge_post')) {
            sb_cache_purge_post($members_page->ID);
        }
    }

    /**
     * Hook for tilleggsmoduler som vil reagere på foretak-cache-purge.
     *
     * @param int $foretak_id
     */
    do_action('bimverdi_purge_foretak_cache', $foretak_id);
}
