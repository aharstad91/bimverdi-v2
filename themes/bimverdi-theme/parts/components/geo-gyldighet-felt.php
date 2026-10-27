<?php
/**
 * Geografisk gyldighet for registrering og redigering av kunnskapskilder.
 *
 * Bård (Trello): nytt nivå «Regionalt». Velger brukeren det, vises to trinn:
 *   1. Fylke (ett eller flere).
 *   2. Kommuner i de valgte fylkene (valgfritt, med søk). Tomt = hele fylket.
 * Oslo er både fylke og kommune, så kommunen krysses av automatisk.
 *
 * Data og regler ligger i mu-plugins/bimverdi-kunnskapskilde-geografi.php.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Nedtrekksmenyen for nivå. Står i samme rutenett som «Dataformat».
 *
 * @param string $valgt Valgt nivå (slug).
 */
function bimverdi_geo_gyldighet_select($valgt = '') {
    ?>
    <div>
        <label for="geografisk_gyldighet" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Geografisk gyldighet</label>
        <select id="geografisk_gyldighet" name="geografisk_gyldighet" class="w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] bg-white focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
            <option value="">Velg gyldighet</option>
            <?php foreach (bimverdi_geo_gyldighet_valg() as $value => $label) : ?>
            <option value="<?php echo esc_attr($value); ?>" <?php selected((string) $valgt, $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
}

/**
 * Panelet med fylke og kommuner. Skrives ut i full bredde under rutenettet,
 * og vises bare når nivået er «regionalt».
 *
 * @param string   $niva     Valgt nivå.
 * @param string[] $fylker   Valgte fylkesnummer.
 * @param string[] $kommuner Valgte kommunenummer.
 */
function bimverdi_geo_region_panel($niva = '', $fylker = array(), $kommuner = array()) {
    $data     = bimverdi_geo_data();
    $fylker   = array_map('strval', (array) $fylker);
    $kommuner = array_map('strval', (array) $kommuner);
    ?>
    <div id="geo-region-panel" class="pl-4 border-l-4 border-[#E5E0D5] space-y-6" <?php echo $niva === 'regionalt' ? '' : 'hidden'; ?>>

        <!-- Trinn 1: fylke -->
        <fieldset>
            <legend class="block text-sm font-semibold text-[#1A1A1A] mb-1">
                Fylke <span class="text-red-500">*</span>
            </legend>
            <p class="text-xs text-[#888888] mb-3">Velg ett eller flere fylker. Oslo er både fylke og kommune.</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-2">
                <?php foreach ($data['fylker'] as $fylke) : ?>
                <label class="flex items-center gap-2 text-sm text-[#1A1A1A] cursor-pointer">
                    <input type="checkbox" name="geografisk_fylker[]" value="<?php echo esc_attr($fylke['nr']); ?>"
                           data-geo-fylke
                           class="rounded border-[#E5E0D5] text-[#FF8B5E] focus:ring-[#FF8B5E]"
                           <?php checked(in_array($fylke['nr'], $fylker, true)); ?>>
                    <?php echo esc_html($fylke['navn']); ?>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <!-- Trinn 2: kommuner i valgte fylker -->
        <fieldset>
            <legend class="block text-sm font-semibold text-[#1A1A1A] mb-1">Kommuner</legend>
            <p class="text-xs text-[#888888] mb-3">Valgfritt. La det stå tomt hvis kilden gjelder hele fylket.</p>

            <p data-geo-tom class="text-sm text-[#5A5A5A]" <?php echo $fylker ? 'hidden' : ''; ?>>Velg fylke først.</p>

            <div data-geo-kommuner <?php echo $fylker ? '' : 'hidden'; ?>>
                <label for="geo-kommune-sok" class="sr-only">Søk etter kommune</label>
                <input type="search" id="geo-kommune-sok" placeholder="Søk etter kommune" autocomplete="off"
                       class="w-full px-4 py-2.5 mb-3 border border-[#E5E0D5] rounded-lg text-sm text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">

                <div class="max-h-[300px] overflow-y-auto border border-[#E5E0D5] rounded-lg px-4 py-3 space-y-5">
                    <?php foreach ($data['fylker'] as $fylke) :
                        $antall_valgt = count(array_intersect(array_map('strval', $fylke['kommuner']), $kommuner));
                    ?>
                    <div data-geo-gruppe="<?php echo esc_attr($fylke['nr']); ?>" <?php echo in_array($fylke['nr'], $fylker, true) ? '' : 'hidden'; ?>>
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#5A5A5A] mb-2">
                            <?php echo esc_html($fylke['navn']); ?>
                            <span data-geo-antall class="font-normal normal-case"><?php echo $antall_valgt ? '(' . (int) $antall_valgt . ' valgt)' : ''; ?></span>
                        </p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-2">
                            <?php foreach ($fylke['kommuner'] as $knr) :
                                $kommune = $data['kommuner'][$knr];
                                $sok     = mb_strtolower(implode(' ', array_merge([$kommune['navn']], $kommune['andre_navn'])));
                            ?>
                            <label class="flex items-center gap-2 text-sm text-[#1A1A1A] cursor-pointer" data-geo-sok="<?php echo esc_attr($sok); ?>">
                                <input type="checkbox" name="geografisk_kommuner[]" value="<?php echo esc_attr($kommune['nr']); ?>"
                                       class="rounded border-[#E5E0D5] text-[#FF8B5E] focus:ring-[#FF8B5E]"
                                       <?php checked(in_array($kommune['nr'], $kommuner, true)); ?>>
                                <?php echo esc_html($kommune['navn']); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <p data-geo-ingen-treff class="text-sm text-[#5A5A5A]" hidden>Ingen kommuner passer søket.</p>
                </div>
            </div>
        </fieldset>
    </div>
    <script>
    (function () {
        var panel = document.getElementById('geo-region-panel');
        var niva = document.getElementById('geografisk_gyldighet');
        if (!panel || !niva) return;

        var sok = document.getElementById('geo-kommune-sok');
        var fylker = panel.querySelectorAll('[data-geo-fylke]');
        var tom = panel.querySelector('[data-geo-tom]');
        var liste = panel.querySelector('[data-geo-kommuner]');
        var ingenTreff = panel.querySelector('[data-geo-ingen-treff]');

        function gruppe(nr) {
            return panel.querySelector('[data-geo-gruppe="' + nr + '"]');
        }

        function oppdaterAntall(g) {
            var n = g.querySelectorAll('input:checked').length;
            g.querySelector('[data-geo-antall]').textContent = n ? '(' + n + ' valgt)' : '';
        }

        function filtrer() {
            var ord = (sok.value || '').toLowerCase().trim();
            var treff = 0;
            panel.querySelectorAll('[data-geo-gruppe]').forEach(function (g) {
                if (g.hidden) return;
                var synlige = 0;
                g.querySelectorAll('[data-geo-sok]').forEach(function (l) {
                    var vis = !ord || l.getAttribute('data-geo-sok').indexOf(ord) !== -1;
                    l.style.display = vis ? '' : 'none';
                    if (vis) synlige++;
                });
                g.style.display = synlige ? '' : 'none';
                treff += synlige;
            });
            ingenTreff.hidden = treff > 0;
        }

        function oppdaterFylker() {
            var noenValgt = false;
            fylker.forEach(function (cb) {
                var g = gruppe(cb.value);
                if (!g) return;
                g.hidden = !cb.checked;
                if (cb.checked) noenValgt = true;
            });
            tom.hidden = noenValgt;
            liste.hidden = !noenValgt;
            filtrer();
        }

        fylker.forEach(function (cb) {
            cb.addEventListener('change', function () {
                var g = gruppe(cb.value);
                if (!g) return;
                var bokser = g.querySelectorAll('input[type="checkbox"]');
                if (!cb.checked) {
                    // Fylket er valgt bort: kommunene i det skal ikke sendes med.
                    bokser.forEach(function (k) { k.checked = false; });
                } else if (bokser.length === 1) {
                    // Oslo: fylket og kommunen er det samme.
                    bokser[0].checked = true;
                }
                oppdaterAntall(g);
                oppdaterFylker();
            });
        });

        panel.querySelectorAll('[data-geo-gruppe] input[type="checkbox"]').forEach(function (k) {
            k.addEventListener('change', function () {
                oppdaterAntall(k.closest('[data-geo-gruppe]'));
            });
        });

        sok.addEventListener('input', filtrer);
        // Enter i søkefeltet skal ikke sende skjemaet.
        sok.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') e.preventDefault();
        });

        niva.addEventListener('change', function () {
            panel.hidden = niva.value !== 'regionalt';
        });

        var skjema = panel.closest('form');
        if (skjema) {
            skjema.addEventListener('submit', function (e) {
                if (e.defaultPrevented || niva.value !== 'regionalt') return;
                if (!panel.querySelector('[data-geo-fylke]:checked')) {
                    e.preventDefault();
                    panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    alert('Velg minst ett fylke når gyldigheten er regional.');
                }
            });
        }

        oppdaterFylker();
    })();
    </script>
    <?php
}
