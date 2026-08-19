<?php
/**
 * Archive template for Verktøy (Tools) CPT
 *
 * Public tools/software catalog with BIM Verdi design.
 * Clean, minimal styling following UI Contract v1.
 * Updated 2026-02-03: Replaced checkbox filters with compact dropdown filter bar.
 * Updated 2026-08-19: Server-side filtrering + paginering (24 per side) med AJAX-henting.
 *   Malen hentet før ALLE publiserte verktøy og filtrerte klient-side ved å skjule kort.
 *   Etter AEC AI Hub-importen (1564 publiserte verktøy) ble siden 7,9 MB. All spørre- og
 *   tellelogikk ligger nå i inc/verktoy-katalog.php, kort-markupen i
 *   parts/components/verktoy-kort.php — begge deles med AJAX-endepunktet.
 *
 * @package BimVerdi_Theme
 */

get_header();

require_once get_template_directory() . '/parts/components/filter-bar.php';
require_once get_template_directory() . '/parts/components/pagination.php';

$filters            = bv_verktoy_katalog_filters();
$counts             = bv_verktoy_katalog_counts();
$page               = bv_verktoy_katalog_page($filters);
$temagruppe_options = bv_verktoy_temagruppe_options();
$type_options       = bv_verktoy_type_options();
$kilde_options      = bv_verktoy_kilde_options();

// Basis-URL for de ekte side-lenkene (no-JS + søkemotorer). Filtrene bæres videre som
// query-args slik at «side 2 av et filtrert søk» er en reell, delbar URL.
$pagination_base_args = array();
foreach ($filters['temagruppe'] as $v) {
    $pagination_base_args['temagruppe'][] = $v;
}
foreach ($filters['type'] as $v) {
    $pagination_base_args['type_ressurs'][] = $v;
}
foreach ($filters['kilde'] as $v) {
    $pagination_base_args['kilde'][] = $v;
}
if ($filters['search'] !== '') {
    $pagination_base_args['s'] = $filters['search'];
}
?>

<div class="min-h-screen bg-white">

    <?php get_template_part('parts/components/archive-intro', null, [
        'acf_prefix'       => 'verktoy',
        'fallback_title'   => 'Verktøykatalog',
        'fallback_ingress' => 'Digitale verktøy og løsninger fra BIM Verdi-nettverket.',
        'count'            => $counts['total'],
        'count_label'      => 'verktøy',
        'tag_cloud'        => [
            'meta_filters' => [
                ['options' => $temagruppe_options, 'filter_class' => 'filter-temagruppe'],
                ['options' => $type_options, 'filter_class' => 'filter-type'],
            ],
            'max_tags' => 12,
        ],
    ]); ?>

    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Compact Filter Bar -->
        <?php
        bimverdi_filter_bar([
            'form_id'            => 'verktoy-filter-form',
            'search_name'        => 's',
            'search_value'       => $filters['search'],
            'search_placeholder' => 'Søk etter verktøy...',
            'dropdowns'          => [
                [
                    'name'         => 'temagruppe[]',
                    'label'        => 'Temagruppe',
                    'options'      => $temagruppe_options,
                    'selected'     => $filters['temagruppe'],
                    'counts'       => $counts['temagruppe'],
                    'filter_class' => 'filter-temagruppe',
                ],
                [
                    'name'         => 'type_ressurs[]',
                    'label'        => 'Type',
                    'options'      => $type_options,
                    'selected'     => $filters['type'],
                    'counts'       => $counts['type'],
                    'filter_class' => 'filter-type',
                ],
                // Kilde er flyttet ut av dropdownen til synlige toggle-pills under filterlinjen.
            ],
            'result_count'       => $page['total'],
            'total_count'        => $counts['total'],
            'result_label'       => 'verktøy',
            'extra_active_count' => count($filters['kilde']), // kilde-pills rendres utenfor dropdowns
            'reset_id'           => 'reset-filters',
            'view_toggle'        => [
                'storage_key' => 'bv-view-verktoy',
                'grid_id'     => 'verktoy-grid',
                'list_id'     => 'verktoy-list',
            ],
        ]);
        ?>

        <!-- Kilde-toggler: synlige pills som erstatter Kilde-dropdownen (synk 29.06).
             Gjenbruker .filter-kilde-checkbox-logikken; ingen valgt = viser begge kilder. -->
        <div class="flex flex-wrap items-center gap-2 mb-8 -mt-4" role="group" aria-label="Filtrer etter kilde">
            <?php
            foreach (['medlem', 'aec_ai_hub'] as $kval):
                if (!isset($kilde_options[$kval])) continue;
                $kchecked = in_array($kval, $filters['kilde'], true);
                $kcount   = isset($counts['kilde'][$kval]) ? intval($counts['kilde'][$kval]) : null;
            ?>
            <label class="cursor-pointer select-none">
                <input type="checkbox" name="kilde[]" value="<?php echo esc_attr($kval); ?>"
                       class="filter-checkbox filter-kilde peer sr-only" <?php checked($kchecked); ?>>
                <span class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-full border border-[#E7E5E4] text-[#57534E] bg-white peer-checked:bg-[#111827] peer-checked:text-white peer-checked:border-[#111827] transition-colors">
                    <?php echo esc_html($kilde_options[$kval]); ?>
                    <?php if ($kcount !== null): ?><span class="text-xs opacity-70"><?php echo $kcount; ?></span><?php endif; ?>
                </span>
            </label>
            <?php endforeach; ?>
        </div>

        <!-- Resultatområde. Begge containere fylles alltid, så view-toggle er umiddelbar. -->
        <div id="verktoy-results" data-empty="<?php echo empty($page['items']) ? '1' : '0'; ?>">

            <!-- Grid View -->
            <div id="verktoy-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8"<?php echo empty($page['items']) ? ' style="display:none"' : ''; ?>>
                <?php echo bv_verktoy_katalog_render($page['items'], 'grid'); ?>
            </div>

            <!-- List View (hidden by default) -->
            <div id="verktoy-list" style="display:none" class="mb-8">
                <div class="bg-white rounded-xl border border-[#E7E5E4] overflow-hidden overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-[#FAFAF9] border-b border-[#E7E5E4]">
                            <tr>
                                <th class="px-4 py-3 font-medium text-[#57534E]">Verktøy</th>
                                <th class="px-4 py-3 font-medium text-[#57534E]">Leverandør</th>
                                <th class="px-4 py-3 font-medium text-[#57534E]">Type</th>
                                <th class="px-4 py-3 font-medium text-[#57534E]">Tema</th>
                                <th class="px-4 py-3 font-medium text-[#57534E] w-16">Lenke</th>
                            </tr>
                        </thead>
                        <tbody id="verktoy-list-body" class="divide-y divide-[#E7E5E4]">
                            <?php echo bv_verktoy_katalog_render($page['items'], 'list'); ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty State -->
            <div id="verktoy-empty" class="bg-white rounded-lg border border-[#E7E5E4] text-center py-16 px-6"<?php echo empty($page['items']) ? '' : ' style="display:none"'; ?>>
                <div class="w-16 h-16 bg-[#F5F5F4] rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#57534E]" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </div>
                <h3 class="text-xl font-bold text-[#111827] mb-2">Ingen verktøy funnet</h3>
                <p class="text-[#57534E] mb-6 max-w-md mx-auto">Prøv å justere filtrene eller søket for å finne det du leter etter</p>
                <a href="<?php echo esc_url(get_post_type_archive_link('verktoy')); ?>" class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-lg text-white bg-[#111827] hover:bg-[#1F2937] transition-colors">
                    Vis alle verktøy
                </a>
            </div>
        </div>

        <!-- «Vis flere»: JS-puljen. data-page holder hvilken side som sist ble lastet, slik at
             en dyplenket ?paged=3 fortsetter riktig i stedet for å begynne på nytt. -->
        <div id="verktoy-load-more-wrap" class="flex justify-center mt-2 mb-8"
             data-page="<?php echo (int) $page['paged']; ?>"
             style="display:none">
            <?php bimverdi_button([
                'text'          => 'Vis flere',
                'variant'       => 'outline',
                'icon'          => 'chevron-down',
                'icon_position' => 'right',
                'id'            => 'verktoy-load-more',
            ]); ?>
        </div>

        <!-- Ekte side-lenker: eneste navigasjon uten JS, og det søkemotorer følger for å
             finne verktøy forbi side 1. Skjules når JS tar over med «Vis flere». -->
        <div id="verktoy-pagination" class="mb-8">
            <?php bimverdi_pagination([
                'current'   => $page['paged'],
                'total'     => $page['max_pages'],
                'base'      => '',
                'prev_text' => 'Forrige',
                'next_text' => 'Neste',
                'add_args'  => $pagination_base_args,
            ]); ?>
        </div>

        <?php get_template_part('parts/components/archive-cta', null, [
            'title'       => 'Har du et verktøy å dele?',
            'description' => 'Logg inn for å registrere verktøy og bidra til katalogen.',
            'cta_text'    => 'Logg inn',
            'cta_url'     => '/logg-inn/',
            'icon'        => 'log-in',
            'show_for'    => 'logged_out',
        ]); ?>

    </div>
</div>

<!-- Filter- og pagineringsskript.
     Serveren har allerede rendret riktig side ut fra URL-en, så oppstart gjør INGEN henting —
     JS overtar først når brukeren endrer et filter eller ber om flere. -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var AJAX_URL = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
    var PER_PAGE = <?php echo (int) BV_VERKTOY_PER_PAGE; ?>;

    var searchInput   = document.getElementById('verktoy-filter-form-search');
    var checkboxes    = document.querySelectorAll('.filter-checkbox');
    var gridEl        = document.getElementById('verktoy-grid');
    var listBodyEl    = document.getElementById('verktoy-list-body');
    var listEl        = document.getElementById('verktoy-list');
    var emptyEl       = document.getElementById('verktoy-empty');
    var visibleCount  = document.getElementById('visible-count');
    var mobileCount   = document.getElementById('visible-count-mobile');
    var sheetCount    = document.querySelector('.bv-filter-sheet__result-count');
    var resetBtn      = document.getElementById('reset-filters');
    var loadMoreWrap  = document.getElementById('verktoy-load-more-wrap');
    var loadMoreBtn   = document.getElementById('verktoy-load-more');
    var paginationEl  = document.getElementById('verktoy-pagination');

    var currentPage = loadMoreWrap ? parseInt(loadMoreWrap.dataset.page || '1', 10) : 1;
    var hasMore     = false;
    var busy        = false;
    var debounceTimer;

    // JS overtar: side-lenkene erstattes av «Vis flere».
    if (paginationEl) paginationEl.style.display = 'none';

    function selectedValues(selector) {
        // Desktop-dropdowns og mobil-arket speiler hverandre (data-syncs-with i filter-bar),
        // så vi leser bare desktop-settet for å unngå doble verdier.
        return Array.from(document.querySelectorAll(selector)).map(function(cb) { return cb.value; });
    }

    function currentFilters() {
        return {
            s: searchInput ? searchInput.value.trim() : '',
            temagruppe: selectedValues('[data-multiselect] .filter-temagruppe:checked'),
            type_ressurs: selectedValues('[data-multiselect] .filter-type:checked'),
            kilde: selectedValues('.filter-kilde:checked')
        };
    }

    function buildParams(filters, page) {
        var params = new URLSearchParams();
        if (filters.s) params.set('s', filters.s);
        ['temagruppe', 'type_ressurs', 'kilde'].forEach(function(key) {
            filters[key].forEach(function(v) { params.append(key, v); });
        });
        if (page > 1) params.set('paged', page);
        return params;
    }

    function updateURL(filters, page) {
        var params = buildParams(filters, page);
        var qs = params.toString();
        history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
    }

    function updateCounts(total) {
        if (visibleCount) visibleCount.textContent = total;
        if (mobileCount) mobileCount.textContent = total;
        if (sheetCount) sheetCount.textContent = total;
    }

    // Speiler js/view-toggle.js: lagret valg, men alltid grid under 768px. Trengs fordi en tom
    // trefliste skjuler BEGGE containerne — da er DOM-en ikke lenger en pålitelig kilde til
    // hvilken visning brukeren står i, og neste treff må gjenopprette den riktige.
    function preferredView() {
        if (window.innerWidth < 768) return 'grid';
        var saved = null;
        try { saved = localStorage.getItem('bv-view-verktoy'); } catch (e) {}
        return (saved === 'list') ? 'list' : 'grid';
    }

    function showResults() {
        // Står brukeren alt i en visning, er DOM-en sannheten — også når valget bryter med
        // mobilregelen (view-toggle lar deg klikke liste på smal skjerm). Vi overtar bare når
        // BEGGE containerne er skjult, altså etter en tom trefliste.
        var gridHidden = !gridEl || gridEl.style.display === 'none';
        var listHidden = !listEl || listEl.style.display === 'none';
        if (!gridHidden || !listHidden) {
            return;
        }

        var wantList = (preferredView() === 'list');
        if (gridEl) gridEl.style.display = wantList ? 'none' : '';
        if (listEl) listEl.style.display = wantList ? '' : 'none';
    }

    function setBusy(state) {
        busy = state;
        if (gridEl) gridEl.style.opacity = state ? '0.5' : '';
        if (listEl) listEl.style.opacity = state ? '0.5' : '';
        if (loadMoreBtn) loadMoreBtn.disabled = state;
    }

    function fetchPage(page, append) {
        if (busy) return;
        var filters = currentFilters();
        var params  = buildParams(filters, page);
        params.set('action', 'bv_verktoy_filter');

        setBusy(true);
        fetch(AJAX_URL + '?' + params.toString(), { credentials: 'same-origin' })
            .then(function(res) { return res.json(); })
            .then(function(payload) {
                if (!payload || !payload.success) throw new Error('Uventet svar');
                var d = payload.data;

                if (append) {
                    if (gridEl) gridEl.insertAdjacentHTML('beforeend', d.grid);
                    if (listBodyEl) listBodyEl.insertAdjacentHTML('beforeend', d.list);
                } else {
                    if (gridEl) gridEl.innerHTML = d.grid;
                    if (listBodyEl) listBodyEl.innerHTML = d.list;
                }

                currentPage = d.paged;
                hasMore     = !!d.has_more;

                var isEmpty = (d.total === 0);
                if (emptyEl) emptyEl.style.display = isEmpty ? '' : 'none';
                if (isEmpty) {
                    if (gridEl) gridEl.style.display = 'none';
                    if (listEl) listEl.style.display = 'none';
                } else {
                    showResults();
                }

                updateCounts(d.total);
                if (loadMoreWrap) loadMoreWrap.style.display = hasMore ? '' : 'none';
                updateURL(filters, append ? 1 : d.paged);
                setBusy(false);
            })
            .catch(function() {
                setBusy(false);
                // Feiler hentingen, faller vi tilbake til en vanlig sideinnlasting med samme filtre.
                window.location.search = buildParams(currentFilters(), 1).toString();
            });
    }

    function reloadFirstPage() {
        currentPage = 1;
        fetchPage(1, false);
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(reloadFirstPage, 250);
        });
    }

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', reloadFirstPage);
    });

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            fetchPage(currentPage + 1, true);
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            checkboxes.forEach(function(cb) { cb.checked = false; });
            document.querySelectorAll('[data-multiselect] [data-count]').forEach(function(badge) {
                badge.textContent = '0';
                badge.classList.remove('opacity-100');
                badge.classList.add('opacity-0');
                badge.setAttribute('aria-hidden', 'true');
            });
            var mCount = document.querySelector('[data-mobile-count]');
            if (mCount) {
                mCount.textContent = '0';
                mCount.classList.remove('opacity-100');
                mCount.classList.add('opacity-0');
            }
            reloadFirstPage();
        });
    }

    // Oppstart: serveren har rendret riktig side. Vis bare «Vis flere» hvis det finnes mer.
    hasMore = <?php echo !empty($page['has_more']) ? 'true' : 'false'; ?>;
    if (loadMoreWrap) loadMoreWrap.style.display = hasMore ? '' : 'none';
});
</script>

<?php get_footer(); ?>
