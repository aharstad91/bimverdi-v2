<?php
/**
 * AEC AI Hub — kategori → temagruppe-mapper.
 *
 * Deterministisk mapping fra kildens AEC-kategori til BIM Verdis temagruppe-termer
 * (de 6 faste: ByggesaksBIM, ProsjektBIM, EiendomsBIM, MiljøBIM, SirkBIM, BIMtech).
 * Umappbare kategorier → en egen «Ukategorisert»-holdeterm + unmapped=true (ALDRI termløse) —
 * verktøyene holdes som draft til remapping til en ekte temagruppe.
 *
 * ── MAPPING: Bårds bekreftede matrise (2026-06-09) ────────────────────────────
 * Kilde: Bårds CSV «BIM-AEC - Ark 1» (kategori → BIM Verdi-temagruppe, med eksplisitt
 * «Ukategorisert»-kolonne for catch-all). De 11 radene under er matrisen eksakt.
 * Kategorier UTENFOR matrisen → «Ukategorisert». Juster `$map` (eller hekt
 * `bimverdi_aec_category_map`-filteret) ved senere endringer — ingen annen kode trenger det.
 *
 * Naming-avvik mellom matrise og faktiske kildedata (FLAGG til Bård):
 *   - Matrisen har «Engineering» (→ ProsjektBIM), men kategorien forekommer IKKE i dataene
 *     (de 15 reelle kategoriene). Tatt med for fullstendighet — uskadelig (matcher ingenting).
 *   - Dataene har «Structural Design» (4 Champions), men den finnes IKKE i matrisen. Behandlet
 *     som data-ekvivalent av «Engineering» → mappet til ProsjektBIM (Andreas-godkjent 2026-06-09).
 *     Bekreft med Bård ved anledning; fjern raden fra $map hvis han vil ha den i «Ukategorisert».
 *
 * Multi-mapping er Bårds INTENSJON (matrisen gir selv Data Analysis → 5 termer, AEC
 * Hackathon → 5, Robotics → 4). Med `append=false` får et slikt verktøy alle termene og
 * vises på flere temagruppe-landingssider — bekreftet ønsket, ikke en feil.
 *
 * @package BIMVerdiCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class BV_AIHUB_Category_Mapper {

    /** Holdeterm-navn for umappbare kategorier (Bårds catch-all-navn; egen temagruppe-term, ikke termløs). */
    const UNMAPPED_TERM = 'Ukategorisert';

    /**
     * AEC-kategori → temagruppe-termnavn (kun MAPPBARE rader).
     *
     * Kategorier som IKKE finnes her behandles som umappbare → «Ukategorisert». De kjente
     * umappbare (se known_unmappable()) er bevisst utelatt her av nettopp den grunn.
     *
     * @return array<string,string[]>
     */
    public static function map() {
        // Bårds matrise «BIM-AEC - Ark 1» (2026-06-09), eksakt. Termnavn må matche taksonomiens
        // 6 faste termer EKSAKT (inkl. «MiljøBIM» med ø) — ellers lager wp_set_object_terms
        // dupliserte termer. Kategorier som IKKE står her → «Ukategorisert» (catch-all).
        $map = array(
            // AEC-kategori (kildenavn)  =>  temagruppe-termer (Bårds x-er per rad)
            'Design Development'   => array('ByggesaksBIM', 'ProsjektBIM'),
            'Design Specification' => array('ProsjektBIM'),
            'Engineering'          => array('ProsjektBIM'),                  // i matrisen; forekommer ikke i data
            'Structural Design'    => array('ProsjektBIM'),                  // data-ekvivalent av «Engineering» (Andreas-godkjent 2026-06-09; bekreft m/ Bård)
            'Platform'             => array('ProsjektBIM', 'EiendomsBIM', 'BIMtech'),
            'Construction'         => array('ProsjektBIM'),
            'Design Creation'      => array('ProsjektBIM'),
            'Surveying'            => array('BIMtech'),
            'Data Analysis'        => array('ProsjektBIM', 'EiendomsBIM', 'SirkBIM', 'MiljøBIM', 'BIMtech'),
            'PropTech'             => array('EiendomsBIM'),
            'AEC Hackathon'        => array('ProsjektBIM', 'EiendomsBIM', 'SirkBIM', 'MiljøBIM', 'BIMtech'),
            'Robotics'             => array('ProsjektBIM', 'EiendomsBIM', 'SirkBIM', 'BIMtech'),
        );

        /**
         * Filtrer kategori→temagruppe-mappingen. Hekt her for å justere mot Bårds
         * bekreftede grid (#312) uten å endre kode.
         *
         * @param array<string,string[]> $map
         */
        return apply_filters('bimverdi_aec_category_map', $map);
    }

    /**
     * Kjente umappbare kategorier (planens R4 — forventet ~54 verktøy blant 238 Champions).
     * Kun for rapportering/klarhet; map_tool_categories() behandler ALT utenfor map() som
     * umappbart uansett (også ukjente, nye kategorier).
     *
     * @return string[]
     */
    public static function known_unmappable() {
        // Etter Bårds matrise + Structural Design→ProsjektBIM (2026-06-09):
        // Assistant 38 + AR/VR/MR 6 + News 4 + Learning 3 = 51 Champions umappet.
        // (AEC Hackathon og Structural Design er nå MAPPET.)
        return array('Assistant', 'AR/VR/MR', 'News', 'Learning');
    }

    /**
     * Map ett verktøys kategorier til temagruppe-termer.
     *
     * Kilden er enkeltverdi (én kategori per rad) i denne eksporten; multi-kategori
     * støttes (union, dedup) men er teoretisk for Trinn 1.
     *
     * @param string[]|string $categories
     * @return array{term_names:string[],unmapped:bool,raw_categories:string[]}
     */
    public static function map_tool_categories($categories) {
        $map        = self::map();
        $term_names = array();
        $raw        = array();

        foreach ((array) $categories as $cat) {
            $cat = is_string($cat) ? trim($cat) : '';
            if ($cat === '') {
                continue;
            }
            $raw[] = $cat;
            if (isset($map[$cat])) {
                foreach ($map[$cat] as $term) {
                    $term_names[] = $term;
                }
            }
        }

        $term_names = array_values(array_unique($term_names));

        if (empty($term_names)) {
            // Umappbar (kjent umappbar ELLER ukjent kategori) → Ukategorisert, draft, raw bevart.
            return array(
                'term_names'     => array(self::UNMAPPED_TERM),
                'unmapped'       => true,
                'raw_categories' => $raw,
            );
        }

        return array(
            'term_names'     => $term_names,
            'unmapped'       => false,
            'raw_categories' => $raw,
        );
    }

    /**
     * Sørg for at «Ukategorisert»-termen finnes i temagruppe-taksonomien (idempotent, guardet).
     *
     * Må kalles sent (etter at taksonomien er registrert) — typisk fra upserteren rett før
     * term-tildeling. Returnerer term_id, eller false hvis taksonomien mangler / insert feiler.
     *
     * @return int|false
     */
    public static function ensure_unmapped_term() {
        if (!taxonomy_exists('temagruppe')) {
            error_log('[BV_AIHUB] ensure_unmapped_term(): taksonomien «temagruppe» er ikke registrert ennå.');
            return false;
        }

        $existing = term_exists(self::UNMAPPED_TERM, 'temagruppe');
        if (!empty($existing)) {
            return is_array($existing) ? (int) $existing['term_id'] : (int) $existing;
        }

        $res = wp_insert_term(
            self::UNMAPPED_TERM,
            'temagruppe',
            array(
                'description' => 'Holdekategori (Bårds «Ukategorisert») for eksternt synkede verktøy med umappbar kildekategori. Holdes som draft og ekskluderes fra offentlig temagruppe-nav/filter til verktøyet remappes til en ekte temagruppe.',
            )
        );

        if (is_wp_error($res)) {
            error_log('[BV_AIHUB] kunne ikke opprette «Ukategorisert»-term: ' . $res->get_error_message());
            return false;
        }

        return (int) $res['term_id'];
    }
}
