<?php
/**
 * Min Side - Artikler List Part
 *
 * Shows user's own articles with status badges and actions.
 * Used by template-minside-universal.php
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Egne artikler, der brukeren er medforfatter, og kollegers i samme foretak
// (regelen bor i mu-plugins/bimverdi-artikkel-redigering.php)
$user_articles = bimverdi_artikler_for_bruker($user_id);
?>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => __('Mine artikler', 'bimverdi'),
    'description' => __('Artikler du og kollegene dine har sendt inn. Du kan rette både ventende og publiserte artikler.', 'bimverdi'),
    'actions' => bimverdi_can_access('write_article') ? [
        ['text' => __('Skriv ny artikkel', 'bimverdi'), 'url' => bimverdi_minside_url('artikler/skriv'), 'variant' => 'primary', 'icon' => 'plus'],
    ] : [],
]); ?>

<!-- Success: submitted -->
<?php if (isset($_GET['submitted']) && $_GET['submitted'] === '1'): ?>
<div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </svg>
    <p class="text-green-800 text-sm"><?php _e('Artikkelen er sendt inn! Den vil bli gjennomgått før publisering.', 'bimverdi'); ?></p>
</div>
<?php endif; ?>

<!-- Success: updated -->
<?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
<div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </svg>
    <p class="text-green-800 text-sm"><?php _e('Artikkelen er oppdatert!', 'bimverdi'); ?></p>
</div>
<?php endif; ?>

<!-- Success: publisert artikkel endret -->
<?php if (isset($_GET['publisert_endret']) && $_GET['publisert_endret'] === '1'): ?>
<div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </svg>
    <p class="text-green-800 text-sm"><?php _e('Endringene er publisert og synlige på nettsiden.', 'bimverdi'); ?></p>
</div>
<?php endif; ?>

<!-- Success: deleted -->
<?php if (isset($_GET['deleted']) && $_GET['deleted'] === '1'): ?>
<div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </svg>
    <p class="text-green-800 text-sm"><?php _e('Artikkelen er slettet.', 'bimverdi'); ?></p>
</div>
<?php endif; ?>

<!-- Error messages -->
<?php if (!empty($_GET['bv_error'])):
    $error_code = sanitize_text_field($_GET['bv_error']);
    $error_messages = [
        'nonce'             => __('Lenken har utløpt. Prøv igjen.', 'bimverdi'),
        'not_owner'         => __('Du har ikke tilgang til denne artikkelen.', 'bimverdi'),
        'not_found'         => __('Artikkelen ble ikke funnet.', 'bimverdi'),
        'already_published' => __('Publiserte artikler kan ikke slettes herfra. Skriv til post@bimverdi.no.', 'bimverdi'),
        'not_editable'      => __('Denne artikkelen er skjult eller et utkast, og kan bare endres av BIM Verdi. Skriv til post@bimverdi.no.', 'bimverdi'),
        'laast'             => __('BIM Verdi har satt opp denne artikkelen med bilder eller spesielt oppsett som skjemaet ikke kan vise. Send endringene dine til post@bimverdi.no, så legger vi dem inn.', 'bimverdi'),
        'system'            => __('En teknisk feil oppstod. Prøv igjen.', 'bimverdi'),
    ];
    $error_text = $error_messages[$error_code] ?? '';
    if ($error_text):
?>
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    <p class="text-red-800 text-sm"><?php echo esc_html($error_text); ?></p>
</div>
<?php endif; endif; ?>

<?php if (!empty($user_articles)): ?>
<!-- Articles Table -->
<div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead>
            <tr class="border-b border-[#E7E5E4]">
                <th class="py-3 pr-4 text-xs font-medium text-[#57534E]"><?php _e('Artikkel', 'bimverdi'); ?></th>
                <th class="px-4 py-3 text-xs font-medium text-[#57534E]" data-sort-col="1" aria-sort="none">
                    <button type="button" class="bv-sort-knapp"><?php _e('Status', 'bimverdi'); ?> <span class="bv-sort-pil" aria-hidden="true">↕</span></button>
                </th>
                <th class="px-4 py-3 text-xs font-medium text-[#57534E] hidden sm:table-cell" data-sort-col="2" aria-sort="none">
                    <button type="button" class="bv-sort-knapp"><?php _e('Dato', 'bimverdi'); ?> <span class="bv-sort-pil" aria-hidden="true">↕</span></button>
                </th>
                <th class="pl-4 py-3 text-xs font-medium text-[#57534E] text-right"><?php _e('Handlinger', 'bimverdi'); ?></th>
            </tr>
        </thead>
        <tbody id="bv-artikler-tbody">
            <?php foreach ($user_articles as $article):
                $status = get_post_status($article->ID);
                $status_map = [
                    'publish' => ['bg-[#DCFCE7] text-[#166534]', __('Publisert', 'bimverdi')],
                    'pending' => ['bg-[#FEF9C3] text-[#854D0E]', __('Venter på godkjenning', 'bimverdi')],
                    'private' => ['bg-[#F5F5F4] text-[#57534E]', __('Skjult av BIM Verdi', 'bimverdi')],
                    'draft'   => ['bg-[#F5F5F4] text-[#57534E]', __('Utkast hos BIM Verdi', 'bimverdi')],
                ];
                [$status_class, $status_label] = $status_map[$status] ?? $status_map['draft'];
                $date = get_the_date('d.m.Y', $article->ID);
                // Lokal post_date: GMT-datoen er tom for ikke-publiserte artikler
                $date_sort = (int) get_post_time('U', false, $article->ID);
                // Rekkefølge ved sortering på status: det som krever handling først
                $status_rang = ['pending' => 1, 'publish' => 2, 'draft' => 3, 'private' => 4][$status] ?? 3;
                $kan_redigere = bimverdi_artikkel_kan_redigere($article->ID, $user_id)
                    && bimverdi_artikkel_kan_redigeres_status($article->ID);
                $laast = $kan_redigere && bimverdi_artikkel_er_laast($article->ID);
                $forfatter_navn = (int) $article->post_author !== (int) $user_id
                    ? get_the_author_meta('display_name', $article->post_author)
                    : '';
            ?>
            <tr class="border-b border-[#E7E5E4] hover:bg-[#F5F5F4] transition-colors">
                <!-- Tittel -->
                <td class="py-4 pr-4">
                    <div class="flex items-center gap-3">
                    <?php if (has_post_thumbnail($article->ID)): ?>
                        <?php echo get_the_post_thumbnail($article->ID, 'thumbnail', [
                            'class'        => 'w-16 h-12 rounded object-cover flex-shrink-0 bv-artikkel-miniatyr',
                            'alt'          => '',
                            'loading'      => 'lazy',
                            'data-preview' => get_the_post_thumbnail_url($article->ID, 'medium_large'),
                        ]); ?>
                    <?php else: ?>
                        <div class="w-16 h-12 rounded bg-[#F5F5F4] flex items-center justify-center flex-shrink-0" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-[#A8A29E]"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                        </div>
                    <?php endif; ?>
                    <div class="min-w-0">
                        <?php if ($status === 'publish'): ?>
                            <a href="<?php echo get_permalink($article->ID); ?>" class="font-medium text-[#111827] hover:text-[#FF8B5E] transition-colors block leading-tight">
                                <?php echo esc_html($article->post_title); ?>
                            </a>
                        <?php else: ?>
                            <span class="font-medium text-[#111827] block leading-tight">
                                <?php echo esc_html($article->post_title); ?>
                            </span>
                        <?php endif; ?>
                        <?php
                        $temagrupper = get_the_terms($article->ID, 'temagruppe');
                        if ($temagrupper && !is_wp_error($temagrupper)):
                            $names = wp_list_pluck($temagrupper, 'name');
                        ?>
                            <p class="text-xs text-[#57534E] mt-0.5"><?php echo esc_html(implode(', ', $names)); ?></p>
                        <?php endif; ?>
                        <?php if ($forfatter_navn): ?>
                            <p class="text-xs text-[#57534E] mt-0.5"><?php printf(esc_html__('Skrevet av %s', 'bimverdi'), esc_html($forfatter_navn)); ?></p>
                        <?php endif; ?>
                    </div>
                    </div>
                </td>

                <!-- Status -->
                <td class="px-4 py-4 align-middle" data-sort="<?php echo (int) $status_rang; ?>">
                    <span class="inline-block text-xs font-medium <?php echo $status_class; ?> px-2.5 py-1 rounded-full whitespace-nowrap">
                        <?php echo $status_label; ?>
                    </span>
                </td>

                <!-- Dato -->
                <td class="px-4 py-4 hidden sm:table-cell align-middle text-sm text-[#57534E]" data-sort="<?php echo $date_sort; ?>">
                    <?php echo $date; ?>
                </td>

                <!-- Handlinger -->
                <td class="pl-4 py-4 text-right align-middle">
                    <div class="flex items-center justify-end gap-1">
                        <?php if ($status === 'publish'): ?>
                            <!-- Vis artikkel -->
                            <a href="<?php echo get_permalink($article->ID); ?>" class="p-2 text-[#57534E] hover:text-[#111827] hover:bg-[#F5F5F4] rounded transition-colors" title="<?php esc_attr_e('Vis artikkel', 'bimverdi'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if ($kan_redigere && !$laast): ?>
                            <!-- Rediger -->
                            <a href="<?php echo esc_url(bimverdi_minside_url('artikler/rediger') . '?id=' . $article->ID); ?>" class="p-2 text-[#57534E] hover:text-[#111827] hover:bg-[#F5F5F4] rounded transition-colors" title="<?php esc_attr_e('Rediger', 'bimverdi'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                            </a>
                        <?php elseif ($laast): ?>
                            <!-- Låst: satt opp i Gutenberg -->
                            <a href="<?php echo esc_url(add_query_arg('bv_error', 'laast', bimverdi_minside_url('artikler'))); ?>" class="p-2 text-[#A8A29E] hover:text-[#57534E] rounded transition-colors" title="<?php esc_attr_e('Kan ikke redigeres her — se forklaring', 'bimverdi'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if ($status === 'pending' && $kan_redigere): ?>
                            <!-- Slett -->
                            <a href="<?php echo esc_url(wp_nonce_url(
                                add_query_arg([
                                    'action' => 'delete_artikkel',
                                    'artikkel_id' => $article->ID,
                                ], home_url('/min-side/artikler/')),
                                'delete_artikkel_' . $article->ID
                            )); ?>"
                               class="p-2 text-[#57534E] hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                               title="<?php esc_attr_e('Slett', 'bimverdi'); ?>"
                               onclick="return confirm('<?php esc_attr_e('Er du sikker på at du vil slette denne artikkelen?', 'bimverdi'); ?>');">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Forhåndsvisning av fremhevet bilde ved mouseover -->
    <style>
        .bv-artikkel-miniatyr { cursor: zoom-in; }
        #bv-miniatyr-preview { position: fixed; z-index: 1000; display: none; pointer-events: none;
            width: 360px; max-width: calc(100vw - 32px); background: #fff; padding: 4px;
            border: 1px solid #E7E5E4; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,.18); }
        #bv-miniatyr-preview img { display: block; width: 100%; height: auto; border-radius: 6px; }
        @media (hover: none) { #bv-miniatyr-preview { display: none !important; } }
        .bv-sort-knapp { display: inline-flex; align-items: center; gap: 4px; font: inherit; color: inherit;
            background: none; border: 0; padding: 0; cursor: pointer; white-space: nowrap; }
        .bv-sort-knapp:hover { color: #111827; }
        .bv-sort-pil { color: #A8A29E; }
        th[aria-sort="ascending"] .bv-sort-pil, th[aria-sort="descending"] .bv-sort-pil { color: #111827; }
    </style>
    <script>
    (function () {
        var tbody = document.getElementById('bv-artikler-tbody');
        if (!tbody) return;
        var heads = document.querySelectorAll('th[data-sort-col]');
        // Dato faller nyest først som tiebreaker
        function verdi(rad, col) { return parseInt(rad.children[col].getAttribute('data-sort'), 10) || 0; }
        heads.forEach(function (th) {
            th.querySelector('button').addEventListener('click', function () {
                var col = parseInt(th.getAttribute('data-sort-col'), 10);
                var naa = th.getAttribute('aria-sort');
                // Første klikk: dato nyest først, status «venter» først
                var stigende = naa === 'none' ? col === 1 : naa === 'descending';
                var rader = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
                rader.sort(function (a, b) {
                    var d = verdi(a, col) - verdi(b, col);
                    if (!stigende) d = -d;
                    return d || (verdi(b, 2) - verdi(a, 2));
                });
                rader.forEach(function (r) { tbody.appendChild(r); });
                heads.forEach(function (h) {
                    h.setAttribute('aria-sort', 'none');
                    h.querySelector('.bv-sort-pil').textContent = '↕';
                });
                th.setAttribute('aria-sort', stigende ? 'ascending' : 'descending');
                th.querySelector('.bv-sort-pil').textContent = stigende ? '↑' : '↓';
            });
        });
    })();
    </script>
    <div id="bv-miniatyr-preview" aria-hidden="true"><img alt=""></div>
    <script>
    (function () {
        var box = document.getElementById('bv-miniatyr-preview');
        if (!box) return;
        var img = box.querySelector('img');
        function place(e) {
            var w = box.offsetWidth, h = box.offsetHeight, gap = 16;
            var x = e.clientX + gap, y = e.clientY - h / 2;
            if (x + w > window.innerWidth - 8) x = e.clientX - w - gap;
            y = Math.max(8, Math.min(y, window.innerHeight - h - 8));
            box.style.left = x + 'px';
            box.style.top = y + 'px';
        }
        document.querySelectorAll('.bv-artikkel-miniatyr[data-preview]').forEach(function (t) {
            t.addEventListener('mouseenter', function (e) {
                img.onload = function () { place(e); };
                img.src = t.getAttribute('data-preview');
                box.style.display = 'block';
                place(e);
            });
            t.addEventListener('mousemove', place);
            t.addEventListener('mouseleave', function () { box.style.display = 'none'; });
        });
    })();
    </script>

    <!-- Article count -->
    <div class="flex justify-end py-3 text-xs text-[#57534E]">
        <?php printf(__('Viser %d artikler', 'bimverdi'), count($user_articles)); ?>
    </div>
</div>
<?php endif; ?>

<?php if (empty($user_articles)): ?>
<!-- Empty state -->
<div class="mt-8">
    <div class="max-w-lg mx-auto text-center py-12 px-6">
        <div class="w-16 h-16 bg-[#F5F5F4] rounded-lg flex items-center justify-center mx-auto mb-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-[#A8A29E]"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-[#111827] mb-2"><?php _e('Du har ikke skrevet noen artikler ennå', 'bimverdi'); ?></h3>
        <p class="text-sm text-[#57534E] mb-6 leading-relaxed">
            <?php _e('Del erfaringer, prosjektrapporter eller verktøy-tips med andre BIM Verdi-medlemmer.', 'bimverdi'); ?>
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <?php if (bimverdi_can_access('write_article')): ?>
            <?php bimverdi_button([
                'text'    => __('Skriv din første artikkel', 'bimverdi'),
                'variant' => 'primary',
                'href'    => bimverdi_minside_url('artikler/skriv'),
                'icon'    => 'pencil',
            ]); ?>
            <?php endif; ?>
            <?php bimverdi_button([
                'text'    => __('Utforsk artikler', 'bimverdi'),
                'variant' => 'secondary',
                'href'    => home_url('/artikler/'),
                'icon'    => 'search',
            ]); ?>
        </div>
    </div>
</div>
<?php endif; ?>
