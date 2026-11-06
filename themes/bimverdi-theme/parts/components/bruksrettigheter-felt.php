<?php
/**
 * Bruksrettigheter-felt for registrering og redigering av kunnskapskilder.
 * Bård 05.11.2026: lisens, maskinell bruk (AI/TDM) og lenke til vilkår.
 * Valglistene ligger i mu-plugins/bimverdi-kunnskapskilde-registration.php.
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Skriv ut seksjonen.
 *
 * @param array $verdier ['bruksrettigheter' => '', 'maskinell_bruk' => '', 'vilkaar_lenke' => ''].
 */
function bimverdi_bruksrettigheter_felt($verdier = array()) {
    $verdier = wp_parse_args($verdier, array(
        'bruksrettigheter' => '',
        'maskinell_bruk'   => '',
        'vilkaar_lenke'    => '',
    ));
    $select_class = 'w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] bg-white focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent';
    $input_class  = 'w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent';
    ?>
    <hr class="border-[#E5E0D5]">
    <h2 class="text-lg font-semibold text-[#111827]">Bruksrettigheter</h2>
    <p class="text-xs text-[#888888]">Hva er lov å gjøre med innholdet? Sjekk vilkår, lisensmerke eller «Om nettstedet» hos kilden. Velg «Ukjent» hvis du ikke vet.</p>

    <div class="grid lg:grid-cols-2 gap-4">
        <div>
            <label for="bruksrettigheter" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Lisens / bruksrett</label>
            <select id="bruksrettigheter" name="bruksrettigheter" class="<?php echo esc_attr($select_class); ?>">
                <option value="">Velg bruksrett</option>
                <?php foreach (bimverdi_bruksrettigheter_valg() as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($verdier['bruksrettigheter'], $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="maskinell_bruk" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Maskinell bruk (scraping/AI)</label>
            <select id="maskinell_bruk" name="maskinell_bruk" class="<?php echo esc_attr($select_class); ?>">
                <option value="">Velg</option>
                <?php foreach (bimverdi_maskinell_bruk_valg() as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($verdier['maskinell_bruk'], $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div>
        <label for="vilkaar_lenke" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Lenke til vilkår eller lisens</label>
        <input type="url" id="vilkaar_lenke" name="vilkaar_lenke"
               value="<?php echo esc_attr($verdier['vilkaar_lenke']); ?>"
               placeholder="https://..."
               class="<?php echo esc_attr($input_class); ?>">
    </div>
    <?php
}

/**
 * Utgivers kontaktperson og e-post (Bård 06.11.2026).
 * Vises bare for admin på kildesiden. Brukes til å kontakte utgiver om bruk av innholdet.
 *
 * @param array $verdier ['utgiver_kontaktperson' => '', 'utgiver_epost' => ''].
 */
function bimverdi_utgiver_kontakt_felt($verdier = array()) {
    $verdier = wp_parse_args($verdier, array(
        'utgiver_kontaktperson' => '',
        'utgiver_epost'         => '',
    ));
    $input_class = 'w-full px-4 py-3 border border-[#E5E0D5] rounded-lg text-[#1A1A1A] placeholder:text-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent';
    ?>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="utgiver_kontaktperson" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Utgivers kontaktperson</label>
            <input type="text" id="utgiver_kontaktperson" name="utgiver_kontaktperson"
                   value="<?php echo esc_attr($verdier['utgiver_kontaktperson']); ?>"
                   placeholder="Navn eller funksjon, f.eks. Kundeservice"
                   class="<?php echo esc_attr($input_class); ?>">
        </div>
        <div>
            <label for="utgiver_epost" class="block text-sm font-semibold text-[#1A1A1A] mb-2">Utgivers e-post</label>
            <input type="email" id="utgiver_epost" name="utgiver_epost"
                   value="<?php echo esc_attr($verdier['utgiver_epost']); ?>"
                   placeholder="F.eks. post@utgiver.no"
                   class="<?php echo esc_attr($input_class); ?>">
        </div>
    </div>
    <p class="text-xs text-[#888888]">Kontaktinformasjonen vises bare for BIM Verdi-administratorer.</p>
    <?php
}
