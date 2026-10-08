<?php
/**
 * Kilde-kategori-felt (avkrysning, maks to valg) for registrering og redigering
 * av kunnskapskilder. Trello #358 (Bård): erstatter nedtrekksmenyen «Kildetype».
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Skriv ut feltet. Maks antall valg håndheves i nettleseren (de øvrige boksene
 * blir inaktive når grensen er nådd) og igjen på serveren.
 *
 * @param string[] $valgt Slugs som allerede er valgt.
 */
function bimverdi_kilde_kategori_felt($valgt = array()) {
    $valg = bimverdi_kildekategori_valg();
    $maks = bimverdi_kildekategori_maks();
    ?>
    <fieldset id="kilde-kategori-felt" data-maks="<?php echo (int) $maks; ?>">
        <legend class="block text-sm font-semibold text-[#1A1A1A] mb-1">
            Kilde-kategori <span class="text-red-500">*</span>
        </legend>
        <p class="text-xs text-[#888888] mb-3">Velg inntil <?php echo $maks === 2 ? 'to' : (int) $maks; ?>.</p>
        <div class="grid sm:grid-cols-2 gap-x-6 gap-y-2">
            <?php foreach ($valg as $slug => $label) : ?>
            <label class="flex items-center gap-2 text-sm text-[#1A1A1A] cursor-pointer">
                <input type="checkbox" name="kildetype[]" value="<?php echo esc_attr($slug); ?>"
                       class="rounded border-[#E5E0D5] text-[#FF8B5E] focus:ring-[#FF8B5E]"
                       <?php checked(in_array($slug, (array) $valgt, true)); ?>>
                <?php echo esc_html($label); ?>
            </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <script>
    (function () {
        var felt = document.getElementById('kilde-kategori-felt');
        if (!felt) return;
        var maks = parseInt(felt.getAttribute('data-maks'), 10) || 2;
        var bokser = felt.querySelectorAll('input[type="checkbox"]');
        function oppdater() {
            var antall = felt.querySelectorAll('input:checked').length;
            bokser.forEach(function (b) { b.disabled = antall >= maks && !b.checked; });
        }
        bokser.forEach(function (b) { b.addEventListener('change', oppdater); });
        var skjema = felt.closest('form');
        if (skjema) {
            skjema.addEventListener('submit', function (e) {
                if (felt.querySelectorAll('input:checked').length === 0) {
                    e.preventDefault();
                    felt.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    alert('Velg minst én kilde-kategori.');
                }
            });
        }
        oppdater();
    })();
    </script>
    <?php
}
