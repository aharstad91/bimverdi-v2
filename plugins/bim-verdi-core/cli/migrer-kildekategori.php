<?php
/**
 * Trello #358: slå «Kategori» (taksonomien kunnskapskildekategori) sammen med
 * «Kildetype» (nå «Kilde-kategori», maks to valg).
 *
 * Bruk (fra WordPress-roten):
 *   wp eval-file wp-content/plugins/bim-verdi-core/cli/migrer-kildekategori.php          # tørrkjøring, skriver ingenting
 *   wp eval-file wp-content/plugins/bim-verdi-core/cli/migrer-kildekategori.php apply    # skriver, lager sikkerhetskopi først
 *
 * Regel: dagens kildetype beholdes først, så legges kategoriene til (samme
 * valg i kildetype). Maks to. Kategori-termene og termkoblingene slettes ikke.
 * Rapporten til Bård (uklarheter) skrives til uploads/kildekategori-rapport-*.json.
 */

if (!defined('ABSPATH')) {
    exit;
}

$apply = (($args[0] ?? '') === 'apply');
$maks  = bimverdi_kildekategori_maks();
$ids   = get_posts([
    'post_type'      => 'kunnskapskilde',
    'post_status'    => ['publish', 'draft', 'pending', 'private', 'future'],
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'orderby'        => 'ID',
    'order'          => 'ASC',
]);

$sikkerhetskopi = [];
$rapport        = ['for_mange' => [], 'avviker' => [], 'ukjent_term' => [], 'tom' => []];
$endres         = 0;
$uendret        = 0;

foreach ($ids as $id) {
    $raa      = get_post_meta($id, 'kildetype', true);
    $gammel   = bimverdi_normaliser_kildekategorier($raa);
    $termer   = wp_get_post_terms($id, 'kunnskapskildekategori');
    $termer   = is_wp_error($termer) ? [] : $termer;
    $fra_term = [];
    foreach ($termer as $t) {
        $m = bimverdi_kategori_slug_til_kildekategori($t->slug);
        if ($m === '') {
            $rapport['ukjent_term'][] = ['id' => $id, 'term' => $t->name];
            continue;
        }
        $fra_term[$t->name] = $m;
    }

    $samlet = $gammel;
    foreach ($fra_term as $m) {
        if (!in_array($m, $samlet, true)) {
            $samlet[] = $m;
        }
    }
    $lagret   = array_slice($samlet, 0, $maks);
    $droppet  = array_slice($samlet, $maks);
    $tilfoyd  = array_values(array_diff($lagret, $gammel));

    $tittel = get_post_meta($id, 'kunnskapskilde_navn', true) ?: get_the_title($id);
    $rad = [
        'id'         => $id,
        'tittel'     => $tittel,
        'status'     => get_post_status($id),
        'kildetype'  => $gammel,
        'kategori'   => array_keys($fra_term),
        'resultat'   => $lagret,
        'droppet'    => $droppet,
        'rediger'    => admin_url('post.php?post=' . $id . '&action=edit'),
    ];

    if (empty($lagret)) {
        $rapport['tom'][] = $rad;
    }
    if (!empty($droppet)) {
        $rapport['for_mange'][] = $rad;
    } elseif (!empty($tilfoyd)) {
        $rapport['avviker'][] = $rad;
    }

    $ny_lagring = $lagret;
    $er_lik     = is_array(maybe_unserialize($raa)) && maybe_unserialize($raa) === $ny_lagring;
    if ($er_lik) {
        $uendret++;
        continue;
    }
    $endres++;
    $sikkerhetskopi[$id] = ['kildetype_raa' => $raa, 'kategorier' => wp_list_pluck($termer, 'slug')];
    if ($apply && !empty($lagret)) {
        update_field('kildetype', $lagret, $id);
    }
}

$stempel = gmdate('Ymd-His');
$mappe   = wp_upload_dir()['basedir'];
if ($apply) {
    file_put_contents($mappe . "/kildekategori-sikkerhetskopi-$stempel.json", wp_json_encode($sikkerhetskopi, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
file_put_contents($mappe . "/kildekategori-rapport-$stempel.json", wp_json_encode($rapport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo ($apply ? "SKREVET" : "TØRRKJØRING") . ": " . count($ids) . " kilder, $endres endres, $uendret er uendret\n";
echo "Over grensen (kuttet til $maks): " . count($rapport['for_mange']) . "\n";
echo "Kategori la til noe nytt: " . count($rapport['avviker']) . "\n";
echo "Ukjent term: " . count($rapport['ukjent_term']) . "\n";
echo "Uten kilde-kategori etterpå: " . count($rapport['tom']) . "\n";
echo "Rapport: $mappe/kildekategori-rapport-$stempel.json\n";
if ($apply) {
    echo "Sikkerhetskopi: $mappe/kildekategori-sikkerhetskopi-$stempel.json\n";
}
