<?php
/**
 * Plugin Name: BIM Verdi - Foretak og medforfattere i artikkelskjemaet
 * Description: Lar forfattere velge foretak og medforfattere (deltakerforetak og deres brukere) når de skriver eller redigerer artikler på Min side.
 * Version: 1.0.0
 *
 * Bård 05.11.2026: wp-admin har feltene «Tilknyttet foretak» og «Medforfattere»
 * på artikler, men skjemaet på /min-side/artikler/skriv/ hadde ingen av dem.
 *
 * Utvalget er deltakerforetak (bv_rolle Deltaker/Prosjektdeltaker/Partner —
 * samme regel som «Deltakere»-ruten i ressurs-rig.php) og brukerne knyttet
 * til dem. Forfatterens eget foretak og verdier som allerede står på
 * artikkelen er alltid med, så et lagre fra skjemaet aldri fjerner noe en
 * admin har satt i wp-admin.
 *
 * MERK: medforfattere får redigeringstilgang til artikkelen
 * (bimverdi_artikkel_kan_redigere()). Skjemaet sier det til brukeren.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Brukerens foretak-ID (begge meta-nøklene som er i bruk). */
function bimverdi_afm_bruker_foretak($user_id) {
    return (int) (get_user_meta($user_id, 'bimverdi_company_id', true)
        ?: get_user_meta($user_id, 'bim_verdi_company_id', true));
}

/**
 * Deltakerforetak som kan velges, sortert på navn.
 *
 * @param int[] $alltid_med Foretak-ID-er som alltid tas med (eget foretak, nåværende verdi).
 * @return array [foretak_id => tittel]
 */
function bimverdi_afm_foretak_valg(array $alltid_med = []) {
    $ids = get_posts([
        'post_type'      => 'foretak',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [[
            'key'     => 'bv_rolle',
            'value'   => ['Deltaker', 'Prosjektdeltaker', 'Partner'],
            'compare' => 'IN',
        ]],
    ]);
    $ids = array_unique(array_merge(array_map('intval', $ids), array_filter(array_map('intval', $alltid_med))));
    $valg = [];
    foreach ($ids as $id) {
        if (get_post_type($id) === 'foretak') {
            $valg[$id] = get_the_title($id);
        }
    }
    natcasesort($valg);
    return $valg;
}

/**
 * Brukere som kan velges som medforfattere, gruppert på foretak.
 *
 * @param int[] $foretak_ids  Foretak brukerne hentes fra.
 * @param int[] $alltid_med   Bruker-ID-er som alltid tas med (nåværende medforfattere).
 * @param int   $utenom       Bruker-ID som holdes utenfor (forfatteren selv).
 * @return array [gruppenavn => [user_id => visningsnavn]]
 */
function bimverdi_afm_bruker_valg(array $foretak_ids, array $alltid_med = [], $utenom = 0) {
    $grupper = [];
    $sett    = [];
    if (!empty($foretak_ids)) {
        $verdier = array_map('strval', array_keys(array_flip($foretak_ids)));
        $brukere = get_users([
            'meta_query' => [
                'relation' => 'OR',
                ['key' => 'bimverdi_company_id', 'value' => $verdier, 'compare' => 'IN'],
                ['key' => 'bim_verdi_company_id', 'value' => $verdier, 'compare' => 'IN'],
            ],
            'orderby' => 'display_name',
        ]);
        foreach ($brukere as $u) {
            if ((int) $u->ID === (int) $utenom) {
                continue;
            }
            $f = bimverdi_afm_bruker_foretak($u->ID);
            $gruppe = $f ? get_the_title($f) : 'Uten foretak';
            $grupper[$gruppe][(int) $u->ID] = $u->display_name;
            $sett[(int) $u->ID] = true;
        }
    }
    foreach (array_filter(array_map('intval', $alltid_med)) as $uid) {
        if (isset($sett[$uid]) || $uid === (int) $utenom) {
            continue;
        }
        $u = get_userdata($uid);
        if ($u) {
            $grupper['Andre'][$uid] = $u->display_name;
        }
    }
    ksort($grupper, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($grupper as &$g) {
        natcasesort($g);
    }
    unset($g);
    return $grupper;
}

/**
 * Felles kontekst for skjema og lagring: hva som er lov å velge.
 *
 * @param int $user_id     Innlogget bruker.
 * @param int $artikkel_id 0 ved ny artikkel.
 * @return array ['foretak' => [...], 'brukere' => [...], 'valgt_foretak' => int, 'valgte_brukere' => int[], 'forfatter' => int]
 */
function bimverdi_afm_kontekst($user_id, $artikkel_id = 0) {
    $eget_foretak   = bimverdi_afm_bruker_foretak($user_id);
    $forfatter      = $artikkel_id ? (int) get_post_field('post_author', $artikkel_id) : (int) $user_id;
    $valgt_foretak  = $artikkel_id ? (int) get_post_meta($artikkel_id, 'artikkel_bedrift', true) : 0;
    $valgt_foretak  = $valgt_foretak ?: $eget_foretak;
    $valgte_brukere = ($artikkel_id && function_exists('bimverdi_artikkel_medforfatter_ids'))
        ? bimverdi_artikkel_medforfatter_ids($artikkel_id) : [];

    $foretak = bimverdi_afm_foretak_valg([$eget_foretak, $valgt_foretak]);
    $brukere = bimverdi_afm_bruker_valg(array_keys($foretak), $valgte_brukere, $forfatter);

    return [
        'foretak'        => $foretak,
        'brukere'        => $brukere,
        'valgt_foretak'  => $valgt_foretak,
        'valgte_brukere' => $valgte_brukere,
        'forfatter'      => $forfatter,
    ];
}

/**
 * Les og valider foretak + medforfattere fra POST.
 * Bare verdier som finnes i utvalget godtas.
 *
 * @return array ['foretak' => int (0 = ikke sendt/ugyldig), 'medforfattere' => int[]|null (null = feltet ikke sendt)]
 */
function bimverdi_afm_les_post($user_id, $artikkel_id = 0) {
    $k = bimverdi_afm_kontekst($user_id, $artikkel_id);

    $foretak = (int) ($_POST['artikkel_foretak'] ?? 0);
    if (!isset($k['foretak'][$foretak])) {
        $foretak = 0;
    }

    $medforfattere = null;
    if (isset($_POST['bv_medforfattere_sendt'])) {
        $lov = [];
        foreach ($k['brukere'] as $g) {
            $lov += $g;
        }
        $medforfattere = [];
        foreach ((array) ($_POST['artikkel_medforfattere'] ?? []) as $uid) {
            $uid = (int) $uid;
            if (isset($lov[$uid]) && $uid !== $k['forfatter']) {
                $medforfattere[] = $uid;
            }
        }
        $medforfattere = array_values(array_unique($medforfattere));
    }

    return ['foretak' => $foretak, 'medforfattere' => $medforfattere];
}

/**
 * Skriv ut feltene «Foretak» og «Medforfattere».
 */
function bimverdi_afm_render_felt($user_id, $artikkel_id = 0) {
    $k = bimverdi_afm_kontekst($user_id, $artikkel_id);
    $felt = 'w-full px-3 py-2 border border-[#D6D1C6] rounded-lg text-sm text-[#1A1A1A] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent';
    ?>
    <!-- Foretak -->
    <div class="mb-6">
        <label for="artikkel_foretak" class="block text-sm font-medium text-[#1A1A1A] mb-1.5">
            <?php _e('Foretak', 'bimverdi'); ?>
        </label>
        <select id="artikkel_foretak" name="artikkel_foretak" class="<?php echo esc_attr($felt); ?>">
            <?php foreach ($k['foretak'] as $id => $navn) : ?>
                <option value="<?php echo (int) $id; ?>" <?php selected($id, $k['valgt_foretak']); ?>><?php echo esc_html($navn); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="mt-1 text-xs text-[#5A5A5A]"><?php _e('Foretaket artikkelen vises under. Standard er ditt eget foretak.', 'bimverdi'); ?></p>
    </div>

    <!-- Medforfattere -->
    <div class="mb-6">
        <input type="hidden" name="bv_medforfattere_sendt" value="1">
        <label for="bv-medforfatter-select" class="block text-sm font-medium text-[#1A1A1A] mb-1.5">
            <?php _e('Medforfattere (valgfritt)', 'bimverdi'); ?>
        </label>
        <input type="text" id="bv-medforfatter-filter"
            placeholder="<?php esc_attr_e('Søk etter person eller foretak...', 'bimverdi'); ?>"
            class="<?php echo esc_attr($felt); ?> mb-2">
        <select name="artikkel_medforfattere[]" id="bv-medforfatter-select" multiple size="8" class="<?php echo esc_attr($felt); ?>">
            <?php foreach ($k['brukere'] as $gruppe => $brukere) : ?>
                <optgroup label="<?php echo esc_attr($gruppe); ?>">
                    <?php foreach ($brukere as $uid => $navn) : ?>
                        <option value="<?php echo (int) $uid; ?>" data-sok="<?php echo esc_attr(mb_strtolower($navn . ' ' . $gruppe)); ?>"
                            <?php selected(in_array((int) $uid, $k['valgte_brukere'], true)); ?>><?php echo esc_html($navn); ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
        <p class="mt-1 text-xs text-[#5A5A5A]"><?php _e('Personer fra deltakerforetak som har bidratt. Hold Ctrl/Cmd for å velge flere. Medforfattere vises på artikkelen og kan også redigere den.', 'bimverdi'); ?></p>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var f = document.getElementById('bv-medforfatter-filter');
        var s = document.getElementById('bv-medforfatter-select');
        if (f && s) {
            f.addEventListener('input', function () {
                var q = this.value.toLowerCase();
                Array.prototype.forEach.call(s.querySelectorAll('optgroup'), function (g) {
                    var synlig = 0;
                    Array.prototype.forEach.call(g.querySelectorAll('option'), function (o) {
                        var vis = o.selected || (o.getAttribute('data-sok') || '').indexOf(q) !== -1;
                        o.style.display = vis ? '' : 'none';
                        if (vis) synlig++;
                    });
                    g.style.display = synlig ? '' : 'none';
                });
            });
        }
        var fs = document.getElementById('artikkel_foretak');
        var by = document.getElementById('bv-byline-foretak');
        if (fs && by) {
            fs.addEventListener('change', function () {
                by.textContent = fs.options[fs.selectedIndex].text;
            });
        }
    });
    </script>
    <?php
}
