<?php
/**
 * BIM Verdi Custom Roles
 * 
 * Definerer custom roller for BIM Verdi medlemskap:
 * - medlem: Gratis medlemskap (kan være med/uten foretak, men betaler ikke)
 * - tilleggskontakt: Invitert av hovedkontakt (tilknyttet foretak)
 * - deltaker: Betalende deltaker (standard nivå) - tilknyttet foretak
 * - prosjektdeltaker: Betalende deltaker (middels nivå) - tilknyttet foretak
 * - partner: Betalende deltaker (høyeste nivå) - tilknyttet foretak
 * 
 * NB: deltaker/prosjektdeltaker/partner har IDENTISKE rettigheter,
 * forskjellen er kun forretningsmessig (pris/støttenivå).
 * 
 * Viktig: "medlem" kan være tilknyttet foretak, men har ikke betalt partnerskap.
 * Tilgang til Min Side er tilgjengelig for alle roller.
 * 
 * @package BimVerdi
 */

// Registrer roller ved plugin-aktivering
add_action('init', 'bimverdi_register_custom_roles');

function bimverdi_register_custom_roles() {
    
    // Base capabilities for alle med Min Side-tilgang
    $min_side_caps = array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
    );
    
    // Capabilities for alle tilknyttet foretak (inkl. tilleggskontakt)
    $foretak_caps = array(
        'read' => true,
        'edit_posts' => true,           // Kan redigere egne innlegg
        'publish_posts' => true,        // Kan publisere artikler
        'delete_posts' => true,
        'upload_files' => true,
        'edit_published_posts' => true,
        'delete_published_posts' => true,
        // Custom capabilities
        'manage_foretak_profile' => true,   // Redigere foretak-profil
        'publish_verktoy' => true,           // Publisere verktøy
        'read_member_content' => true,       // Lese medlemsinnhold
    );
    
    // MEDLEM - Gratis medlemskap uten foretak
    add_role('medlem', __('Medlem', 'bimverdi'), array(
        'read' => true,
        'read_member_content' => true,  // Kan lese artikler/innhold
    ));
    
    // TILLEGGSKONTAKT - Invitert av hovedkontakt
    add_role('tilleggskontakt', __('Tilleggskontakt', 'bimverdi'), $foretak_caps);
    
    // DELTAKER - Betalende deltaker (standard)
    add_role('deltaker', __('Deltaker', 'bimverdi'), array_merge($foretak_caps, array(
        'invite_colleagues' => true,  // Kan invitere hvis hovedkontakt
    )));
    
    // PROSJEKTDELTAKER - Betalende deltaker (middels nivå)
    add_role('prosjektdeltaker', __('Prosjektdeltaker', 'bimverdi'), array_merge($foretak_caps, array(
        'invite_colleagues' => true,
    )));
    
    // PARTNER - Betalende deltaker (høyeste nivå)
    add_role('partner', __('Partner', 'bimverdi'), array_merge($foretak_caps, array(
        'invite_colleagues' => true,
    )));
}

/**
 * Sjekk om bruker er tilknyttet et foretak.
 *
 * Returnerer foretak-id (int) eller false. Ingen post-status-filtering —
 * funksjonen er en ren data-lookup. Bruk bimverdi_user_has_company() når
 * UI-synlighet krever publish/pending/draft-status.
 *
 * Delegerer til BIMVerdi_Access_Control::lookup_company_id() for å holde
 * meta-key-prioritet konsistent med user_has_company og get_user_company.
 */
function bimverdi_user_has_foretak($user_id = null) {
    if (class_exists('BIMVerdi_Access_Control')) {
        return BIMVerdi_Access_Control::lookup_company_id($user_id);
    }

    // Defensiv fallback hvis access-control ikke er lastet ennå.
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    $company_id = get_user_meta($user_id, 'bimverdi_company_id', true);
    if (empty($company_id)) {
        $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
    }
    if (empty($company_id) && function_exists('get_field')) {
        $acf = get_field('tilknyttet_foretak', 'user_' . $user_id);
        $company_id = is_object($acf) ? $acf->ID : $acf;
    }
    return empty($company_id) ? false : (int) $company_id;
}

/**
 * Sjekk om bruker er hovedkontakt for sitt foretak
 */
function bimverdi_is_hovedkontakt($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $foretak_id = bimverdi_user_has_foretak($user_id);
    if (!$foretak_id) {
        return false;
    }

    // Check if ACF is available
    if (!function_exists('get_field')) {
        return false;
    }

    $hovedkontakt_id = get_field('hovedkontaktperson', $foretak_id);
    return ($hovedkontakt_id == $user_id);
}

/**
 * Sjekk om bruker er betalende deltaker (deltaker/prosjektdeltaker/partner)
 */
function bimverdi_is_paying_member($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }
    
    $paying_roles = array('deltaker', 'prosjektdeltaker', 'partner');
    foreach ($paying_roles as $role) {
        if (in_array($role, $user->roles)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Hent brukerens medlemskapsnivå (for fakturering)
 */
function bimverdi_get_membership_level($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }
    
    // Sjekk i prioritert rekkefølge
    if (in_array('partner', $user->roles)) {
        return 'partner';
    }
    if (in_array('prosjektdeltaker', $user->roles)) {
        return 'prosjektdeltaker';
    }
    if (in_array('deltaker', $user->roles)) {
        return 'deltaker';
    }
    if (in_array('tilleggskontakt', $user->roles)) {
        return 'tilleggskontakt';
    }
    if (in_array('medlem', $user->roles)) {
        return 'medlem';
    }

    return false;
}

/**
 * MERK: bimverdi_resolve_user_foretak_id(), bimverdi_get_kontakttype() og
 * bimverdi_get_deltakernivaa() er ALLEREDE definert i bimverdi-foretakstype-fields.php
 * (sannhetskilde: bv_foretakstype + bv_nivaa + hovedkontaktperson). Kolonnene under
 * bruker dem via function_exists()-guard. Ikke redefiner her — det gir redeclare-fatal.
 */

/**
 * Nyhetsbrev-status for en bruker. Leser kun user_meta — WP primer user-meta-
 * cachen for hele list-tabellen (cache_users()), så dette blir cache-treff, ikke N+1.
 * '1' = abonnent, '0' = nei/avmeldt, ellers ukjent (pre-eksisterende bruker).
 * Footer-skjema-tabellen (wp_bimverdi_newsletter, e-post-keyed) krysskobles
 * bevisst IKKE per rad her — egen avmeldings-sporing er en oppfølging (se rapport).
 */
function bimverdi_get_newsletter_status($user_id) {
    $meta = get_user_meta($user_id, 'bimverdi_newsletter_subscribed', true);
    if ($meta === '1') {
        return 'subscribed';
    }
    if ($meta === '0') {
        return 'no';
    }
    return 'unknown';
}

/**
 * BIM Verdi-kolonner i wp-admin/users.php (krav 02-v5)
 *
 * Erstatter tidligere "Medlemskap"-kolonne (som blandet deltakernivå og rolle)
 * med tre tydelige kolonner som henter sannhetskilden fra foretak-data:
 *   - Foretak       — lenke til tilknyttet foretak (eller "—")
 *   - Kontakttype   — Gratisbruker / Hovedkontakt / Tilleggskontakt (computed)
 *   - Deltakernivå  — Gratisforetak / Deltaker / Prosjektdeltaker / Partner
 *
 * Alle tre er sorterbare (kort #317). Fordi verdiene er computed på tvers av
 * meta-tabeller finnes det ingen user-meta å sortere på — join-kjeden som
 * gjenskaper oppslaget i SQL ligger i bimverdi_handle_user_derived_orderby().
 * Endrer du utledningen i helperne under, må CASE-uttrykkene der oppdateres i
 * samme slengen, ellers sorterer kolonnen annerledes enn den viser.
 */
add_filter('manage_users_columns', 'bimverdi_add_user_columns');
function bimverdi_add_user_columns($columns) {
    $columns['bimverdi_foretak']        = __('Foretak', 'bimverdi');
    $columns['bimverdi_kontakttype']    = __('Kontakttype', 'bimverdi');
    $columns['bimverdi_deltakernivaa']  = __('Deltakernivå', 'bimverdi');
    $columns['bimverdi_newsletter']     = __('Nyhetsbrev', 'bimverdi');
    return $columns;
}

add_action('manage_users_custom_column', 'bimverdi_show_user_columns', 10, 3);
function bimverdi_show_user_columns($value, $column_name, $user_id) {
    if ($column_name === 'bimverdi_foretak') {
        $foretak_id = function_exists('bimverdi_resolve_user_foretak_id')
            ? bimverdi_resolve_user_foretak_id($user_id)
            : 0;
        if (!$foretak_id) {
            return '<span style="color:#9CA3AF;">—</span>';
        }
        $title = get_the_title($foretak_id) ?: '(uten navn)';
        $edit  = admin_url('post.php?post=' . $foretak_id . '&action=edit');
        return sprintf('<a href="%s">%s</a>', esc_url($edit), esc_html($title));
    }

    if ($column_name === 'bimverdi_kontakttype') {
        $type = function_exists('bimverdi_get_kontakttype')
            ? bimverdi_get_kontakttype($user_id)
            : null;
        $labels = [
            'hovedkontakt'    => '<span style="color:#7C3AED; font-weight:bold;">★ Hovedkontakt</span>',
            'tilleggskontakt' => '<span style="color:#6B7280;">+ Tilleggskontakt</span>',
            'gratisbruker'    => '<span style="color:#9CA3AF;">○ Gratisbruker</span>',
        ];
        return $labels[$type] ?? '<span style="color:#9CA3AF;">—</span>';
    }

    if ($column_name === 'bimverdi_deltakernivaa') {
        $nivaa = function_exists('bimverdi_get_deltakernivaa')
            ? bimverdi_get_deltakernivaa($user_id)
            : null;
        $labels = [
            'partner'          => '<span style="color:#7C3AED; font-weight:bold;">Partner</span>',
            'prosjektdeltaker' => '<span style="color:#F97316; font-weight:bold;">Prosjektdeltaker</span>',
            'deltaker'         => '<span style="color:#10B981;">Deltaker</span>',
            'gratisforetak'    => '<span style="color:#9CA3AF;">Gratisforetak</span>',
        ];
        return $labels[$nivaa] ?? '<span style="color:#9CA3AF;">—</span>';
    }

    if ($column_name === 'bimverdi_newsletter') {
        $status = function_exists('bimverdi_get_newsletter_status')
            ? bimverdi_get_newsletter_status($user_id)
            : 'unknown';
        $labels = [
            'subscribed' => '<span style="color:#10B981; font-weight:bold;">✓ Ja</span>',
            'no'         => '<span style="color:#6B7280;">Nei</span>',
            'unknown'    => '<span style="color:#9CA3AF;">—</span>',
        ];
        return $labels[$status] ?? $labels['unknown'];
    }

    if ($column_name === 'bimverdi_registered') {
        $user = get_userdata($user_id);
        if (!$user || empty($user->user_registered)) {
            return '—';
        }
        $timestamp = strtotime($user->user_registered);
        return $timestamp ? date_i18n('j. M Y', $timestamp) : '—';
    }

    return $value;
}

/**
 * Legg til Kontakttype-lenker (Hovedkontakt / Gratisbruker / Tilleggskontakt
 * / Uten foretak) i den native WP-rolle-lenkeraden øverst på users.php.
 *
 * Disse er computed fra foretak-data (ikke WP-roller), så de gir Bård det
 * mentale bildet han forventer ("hvor mange hovedkontakter har vi?")
 * uten å måtte åpne dropdown-en under.
 */
add_filter('views_users', 'bimverdi_add_kontakttype_views');
function bimverdi_add_kontakttype_views($views) {
    // Telleren er O(n) men caches i 5 min så pageload ikke straffes på hver
    // visning. Cache invalideres når foretak-data endres er ikke kritisk —
    // tallene oppdateres av seg selv innenfor 5 min.
    $counts = wp_cache_get('bv_kontakttype_view_counts', 'bimverdi');
    if ($counts === false) {
        $counts = ['hovedkontakt' => 0, 'tilleggskontakt' => 0, 'gratisbruker' => 0, 'ingen' => 0];
        // Unhook filteret midlertidig så tellingen ikke begrenses av et aktivt
        // ?bv_kontakttype-filter (vi vil telle ALLE brukere uavhengig av
        // gjeldende filter-state).
        remove_action('pre_get_users', 'bimverdi_filter_users_by_kontakttype');
        $all_users = get_users(['fields' => ['ID'], 'number' => -1]);
        add_action('pre_get_users', 'bimverdi_filter_users_by_kontakttype');
        foreach ($all_users as $u) {
            $type = function_exists('bimverdi_get_kontakttype') ? bimverdi_get_kontakttype((int) $u->ID) : null;
            if ($type === null) {
                $counts['ingen']++;
            } elseif (isset($counts[$type])) {
                $counts[$type]++;
            }
        }
        wp_cache_set('bv_kontakttype_view_counts', $counts, 'bimverdi', 5 * MINUTE_IN_SECONDS);
    }

    $current = isset($_GET['bv_kontakttype']) ? sanitize_key($_GET['bv_kontakttype']) : '';
    $labels = [
        'hovedkontakt'    => __('Hovedkontakt', 'bimverdi'),
        'gratisbruker'    => __('Gratisbruker', 'bimverdi'),
    ];
    foreach ($labels as $key => $label) {
        $url = add_query_arg('bv_kontakttype', $key, admin_url('users.php'));
        $class = ($current === $key) ? ' class="current" aria-current="page"' : '';
        $views['bv_' . $key] = sprintf(
            '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
            esc_url($url),
            $class,
            esc_html($label),
            (int) $counts[$key]
        );
    }
    return $views;
}

/**
 * Filter-dropdown: "Kontakttype" over user-listen.
 *
 * Lar admin isolere alle hovedkontakter (Bårds nyhetsbrev-målgruppe),
 * tilleggskontakter eller gratisbrukere. Filtrert resultat går gjennom
 * WPs innebygde CSV-eksport (Tools → Export users), så Bård kan trekke ut
 * en e-postliste uten egen rapport-funksjonalitet.
 */
add_action('restrict_manage_users', 'bimverdi_render_kontakttype_filter');
function bimverdi_render_kontakttype_filter($which) {
    // restrict_manage_users kjøres to ganger (top + bottom) — kun top.
    if ($which !== 'top') {
        return;
    }
    $selected = isset($_GET['bv_kontakttype']) ? sanitize_key($_GET['bv_kontakttype']) : '';
    $options = [
        ''                => __('Alle kontakttyper', 'bimverdi'),
        'hovedkontakt'    => __('Bare hovedkontakter', 'bimverdi'),
        'tilleggskontakt' => __('Bare tilleggskontakter', 'bimverdi'),
        'gratisbruker'    => __('Bare gratisbrukere', 'bimverdi'),
        'ingen'           => __('Uten foretak', 'bimverdi'),
    ];
    echo '<label class="screen-reader-text" for="bv_kontakttype">' . esc_html__('Filtrer på kontakttype', 'bimverdi') . '</label>';
    echo '<select name="bv_kontakttype" id="bv_kontakttype" style="margin-right:6px;">';
    foreach ($options as $val => $label) {
        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr($val),
            selected($selected, $val, false),
            esc_html($label)
        );
    }
    echo '</select>';
    submit_button(__('Filtrer', 'bimverdi'), '', 'bv_filter_submit', false);
}

/**
 * Bygg WP_User_Query meta_query for kontakttype-filteret.
 *
 * Filtreringen er O(n) på user-listen (én meta-sjekk per user) men WP gir
 * oss ikke et bedre alternativ uten å bygge en custom tabell — og 600 users
 * er innenfor toleransen.
 */
add_action('pre_get_users', 'bimverdi_filter_users_by_kontakttype');
function bimverdi_filter_users_by_kontakttype($query) {
    global $pagenow;

    // Bare apply på selve users.php list-table-spørringen — IKKE på alle
    // WP_User_Query (WP kjører tonnevis av interne user-queries for hydrering
    // av meta, capabilities osv., og hvis vi forstyrrer dem får vi 500).
    if ($pagenow !== 'users.php' || !is_admin()) {
        return;
    }
    if (empty($_GET['bv_kontakttype'])) {
        return;
    }
    // Hvis queryen allerede har 'include' satt (f.eks. fra en annen filter
    // eller en intern hydrering-query), respekter det og ikke overskriv.
    $existing_include = $query->get('include');
    if (!empty($existing_include)) {
        return;
    }

    $type = sanitize_key($_GET['bv_kontakttype']);
    if (!in_array($type, ['hovedkontakt', 'tilleggskontakt', 'gratisbruker', 'ingen'], true)) {
        return;
    }

    // Re-entrance guard: vi kaller get_users() inne her for å bygge include-
    // listen, og det vil trigge pre_get_users igjen i denne samme call-stacken.
    static $running = false;
    if ($running) {
        return;
    }

    // Bygg liste av matchende user-IDer. Cache 60 sek mot pagineringskall.
    $cache_key = 'bv_kontakttype_users_' . $type;
    $matching_ids = wp_cache_get($cache_key, 'bimverdi');
    if ($matching_ids === false) {
        $running = true;
        $all = get_users(['fields' => ['ID'], 'number' => -1]);
        $running = false;
        $matching_ids = [];
        foreach ($all as $u) {
            $uid = (int) $u->ID;
            $user_type = function_exists('bimverdi_get_kontakttype')
                ? bimverdi_get_kontakttype($uid)
                : null;
            if ($type === 'ingen') {
                if ($user_type === null) $matching_ids[] = $uid;
            } else {
                if ($user_type === $type) $matching_ids[] = $uid;
            }
        }
        if (empty($matching_ids)) {
            $matching_ids = [0]; // ingen treff — dummy så ingen vises
        }
        wp_cache_set($cache_key, $matching_ids, 'bimverdi', 60);
    }

    $query->set('include', $matching_ids);
}

/**
 * Vis registreringsdato i admin user list (sorterbar)
 */
add_filter('manage_users_columns', 'bimverdi_add_registered_column');
function bimverdi_add_registered_column($columns) {
    $columns['bimverdi_registered'] = __('Registrert', 'bimverdi');
    return $columns;
}

add_filter('manage_users_sortable_columns', 'bimverdi_make_registered_column_sortable');
function bimverdi_make_registered_column_sortable($columns) {
    $columns['bimverdi_registered'] = 'user_registered';
    return $columns;
}

/**
 * Gjør «Navn» + «Nyhetsbrev» sorterbare (synk 29.06, Bård-ønske om klikkbare
 * kolonneheadere). «Navn» bruker native display_name-orderby; «Nyhetsbrev» er
 * meta-basert og oversettes i pre_get_users under.
 *
 * Foretak + Kontakttype + Deltakernivå er også sorterbare (kort #317, Bårds
 * prio 1 i synk 04.08). De har ingen egen user-meta-nøkkel — verdiene utledes
 * fra foretaket brukeren er koblet til — så de sorteres via en join-kjede i
 * bimverdi_handle_user_derived_orderby() lenger ned, ikke via meta-kartet.
 */
add_filter('manage_users_sortable_columns', 'bimverdi_user_sortable_columns');
function bimverdi_user_sortable_columns($columns) {
    $columns['name']                   = 'display_name';           // native WP_User_Query-orderby
    $columns['bimverdi_newsletter']    = 'bv_orderby_newsletter';  // token → meta, se kart under
    $columns['bimverdi_foretak']       = 'bv_orderby_foretak';     // utledet, se join-kjeden
    $columns['bimverdi_kontakttype']   = 'bv_orderby_kontakttype';
    $columns['bimverdi_deltakernivaa'] = 'bv_orderby_deltakernivaa';
    return $columns;
}

/**
 * Kart fra sorterings-token → user-meta-nøkkel for meta-baserte kolonner.
 * Andre mu-plugins (f.eks. «Sist oppdatert») registrerer egne tokens via filteret.
 */
function bimverdi_user_orderby_meta_map() {
    return apply_filters('bimverdi_user_orderby_meta_map', [
        'bv_orderby_newsletter' => 'bimverdi_newsletter_subscribed',
    ]);
}

/**
 * Oversett en meta-basert sorterings-token til faktisk meta-sortering.
 *
 * Vi legger på en EGEN LEFT JOIN mot meta-nøkkelen og sorterer på den aliasen.
 * LEFT JOIN beholder ALLE brukere (også de uten metaen — NULL sorteres sist),
 * og vi sorterer garantert på RIKTIG meta. (En meta_query OR(EXISTS,NOT EXISTS)
 * + orderby-på-klausul ville i WP_User_Query havnet på den nøkkelløse primær-
 * joinen og sortert på en vilkårlig meta-rad — verifisert feil i adversariell
 * review 29.06.) Samme $pagenow-vakt som kontakttype-filteret (memory:
 * pre_get_users uten $pagenow-sjekk krasjer WP sine interne user-fetches).
 */
add_action('pre_get_users', 'bimverdi_handle_user_meta_orderby');
function bimverdi_handle_user_meta_orderby($query) {
    global $pagenow;
    if ($pagenow !== 'users.php' || !is_admin()) {
        return;
    }
    $orderby = $query->get('orderby');
    if (!is_string($orderby) || $orderby === '') {
        return;
    }
    $map = bimverdi_user_orderby_meta_map();
    if (!isset($map[$orderby])) {
        return;
    }
    $meta_key = $map[$orderby];
    $dir = (strtoupper((string) $query->get('order')) === 'ASC') ? 'ASC' : 'DESC';

    // Engangs, query-scopet pre_user_query: egen LEFT JOIN + ORDER BY på den.
    $rewrite = function ($uq) use ($meta_key, $dir, &$rewrite) {
        global $wpdb;
        $alias = 'bv_sort_meta';
        $uq->query_from .= $wpdb->prepare(
            " LEFT JOIN {$wpdb->usermeta} AS {$alias} ON ({$wpdb->users}.ID = {$alias}.user_id AND {$alias}.meta_key = %s)",
            $meta_key
        );
        $uq->query_orderby = sprintf(
            'ORDER BY %s.meta_value %s, %s.ID ASC',
            $alias,
            $dir,
            $wpdb->users
        );
        remove_action('pre_user_query', $rewrite);
    };
    add_action('pre_user_query', $rewrite);
}

/**
 * Sortering av Foretak / Kontakttype / Deltakernivå (kort #317, synk 04.08).
 *
 * Disse tre kolonnene har ingen egen user-meta å sortere på — de UTLEDES av
 * foretaket brukeren er koblet til. Vi løser det ved å bygge samme oppslag i
 * SQL som PHP-helperne gjør, i stedet for å denormalisere en rang-nøkkel ned
 * på hver bruker. Da slipper vi backfill og synk-hooks som kan drifte, og
 * sorteringen kan aldri vise noe annet enn kolonnen selv viser.
 *
 * Join-kjeden speiler bimverdi_resolve_user_foretak_id():
 *   bimverdi_company_id → bim_verdi_company_id → tilknyttet_foretak (ACF)
 * i den rekkefølgen, via COALESCE. Alle tre nøklene er i aktiv bruk i basen.
 *
 * VIKTIG PARITETSDETALJ: bimverdi_get_foretakstype() defaulter til
 * 'gratisforetak' når bv_foretakstype mangler eller er ugyldig. CASE-uttrykkene
 * under må derfor behandle «ikke 'foretak'» som gratisforetak — ikke som ukjent.
 *
 * Brukere uten foretak (viser «—») sorteres alltid sist, uansett retning.
 * Samme $pagenow-vakt som de andre handlerne (memory: pre_get_users uten
 * $pagenow-sjekk krasjer WP sine interne user-fetches).
 */
add_action('pre_get_users', 'bimverdi_handle_user_derived_orderby');
function bimverdi_handle_user_derived_orderby($query) {
    global $pagenow, $wpdb;
    if ($pagenow !== 'users.php' || !is_admin()) {
        return;
    }
    $orderby = $query->get('orderby');
    if (!is_string($orderby) || strpos($orderby, 'bv_orderby_') !== 0) {
        return;
    }

    $users = $wpdb->users;

    // Selve foretak-koblingen, uavhengig av om posten fortsatt finnes.
    // PHP-helperen returnerer den rå ID-en her og sjekker ALDRI at posten
    // eksisterer — en bruker som peker på et slettet foretak vises derfor som
    // «Gratisbruker»/«Gratisforetak» (get_foretakstype() defaulter dit), ikke
    // som «—». Vi må speile det, ellers sorterer raden et annet sted enn
    // etiketten tilsier. Gjelder 3 brukere i dag (peker på foretak 143).
    $link = "COALESCE(NULLIF(bv_fk1.meta_value, ''), NULLIF(bv_fk2.meta_value, ''), NULLIF(bv_fk3.meta_value, ''))";

    // Rang-uttrykk per kolonne. NULL = «—» (ingen foretak-kobling i det hele tatt).
    $foretak_expr = "CASE WHEN {$link} IS NULL THEN NULL ELSE COALESCE(bv_f.post_title, '') END";
    $kontakttype_rank = "CASE
            WHEN {$link} IS NULL THEN NULL
            WHEN bv_ftype.meta_value = 'foretak' AND bv_fhk.meta_value = CAST({$users}.ID AS CHAR) THEN 1
            WHEN bv_ftype.meta_value = 'foretak' THEN 2
            ELSE 3
        END";
    $deltakernivaa_rank = "CASE
            WHEN {$link} IS NULL THEN NULL
            WHEN bv_ftype.meta_value <> 'foretak' OR bv_ftype.meta_value IS NULL THEN 1
            WHEN bv_fniv.meta_value = 'deltaker' THEN 2
            WHEN bv_fniv.meta_value = 'prosjektdeltaker' THEN 3
            WHEN bv_fniv.meta_value = 'partner' THEN 4
            ELSE NULL
        END";

    $specs = [
        'bv_orderby_foretak'       => ['expr' => $foretak_expr, 'foretak_meta' => false],
        'bv_orderby_kontakttype'   => ['expr' => $kontakttype_rank, 'foretak_meta' => true],
        'bv_orderby_deltakernivaa' => ['expr' => $deltakernivaa_rank, 'foretak_meta' => true],
    ];
    if (!isset($specs[$orderby])) {
        return;
    }
    $expr         = $specs[$orderby]['expr'];
    $foretak_meta = $specs[$orderby]['foretak_meta'];
    $dir = (strtoupper((string) $query->get('order')) === 'ASC') ? 'ASC' : 'DESC';

    // Engangs, query-scopet pre_user_query — samme mønster som meta-sorteringen.
    $rewrite = function ($uq) use ($expr, $foretak_meta, $dir, $users, &$rewrite) {
        global $wpdb;

        // Foretak-kobling: tre kandidat-nøkler, første ikke-tomme vinner.
        $join = " LEFT JOIN {$wpdb->usermeta} AS bv_fk1 ON (bv_fk1.user_id = {$users}.ID AND bv_fk1.meta_key = 'bimverdi_company_id')"
              . " LEFT JOIN {$wpdb->usermeta} AS bv_fk2 ON (bv_fk2.user_id = {$users}.ID AND bv_fk2.meta_key = 'bim_verdi_company_id')"
              . " LEFT JOIN {$wpdb->usermeta} AS bv_fk3 ON (bv_fk3.user_id = {$users}.ID AND bv_fk3.meta_key = 'tilknyttet_foretak')"
              . " LEFT JOIN {$wpdb->posts} AS bv_f ON (bv_f.post_type = 'foretak' AND bv_f.ID = COALESCE("
              . "NULLIF(bv_fk1.meta_value, ''), NULLIF(bv_fk2.meta_value, ''), NULLIF(bv_fk3.meta_value, '')))";

        if ($foretak_meta) {
            $join .= " LEFT JOIN {$wpdb->postmeta} AS bv_ftype ON (bv_ftype.post_id = bv_f.ID AND bv_ftype.meta_key = 'bv_foretakstype')"
                   . " LEFT JOIN {$wpdb->postmeta} AS bv_fhk ON (bv_fhk.post_id = bv_f.ID AND bv_fhk.meta_key = 'hovedkontaktperson')"
                   . " LEFT JOIN {$wpdb->postmeta} AS bv_fniv ON (bv_fniv.post_id = bv_f.ID AND bv_fniv.meta_key = 'bv_nivaa')";
        }

        $uq->query_from .= $join;

        // «—» alltid sist: NULL-nøkkelen sorteres stigende uansett $dir.
        $uq->query_orderby = sprintf(
            'ORDER BY (%1$s) IS NULL ASC, (%1$s) %2$s, %3$s.ID ASC',
            $expr,
            $dir,
            $users
        );

        remove_action('pre_user_query', $rewrite);
    };
    add_action('pre_user_query', $rewrite);
}

/**
 * Vis BIM Verdi medlemskap i bruker-profil (admin)
 */
add_action('show_user_profile', 'bimverdi_show_membership_in_profile');
add_action('edit_user_profile', 'bimverdi_show_membership_in_profile');

function bimverdi_show_membership_in_profile($user) {
    $level = bimverdi_get_membership_level($user->ID);
    $has_company = bimverdi_user_has_foretak($user->ID);
    $is_paying = bimverdi_is_paying_member($user->ID);
    $is_hovedkontakt = bimverdi_is_hovedkontakt($user->ID);
    
    $level_labels = array(
        'partner' => '★ Partner (høyeste støttenivå)',
        'prosjektdeltaker' => '◆ Prosjektdeltaker (middels støttenivå)',
        'deltaker' => '● Deltaker (standard støttenivå)',
        'tilleggskontakt' => '+ Tilleggskontakt (invitert av hovedkontakt)',
        'medlem' => '○ Medlem (gratis medlemskap)',
    );
    
    $level_colors = array(
        'partner' => '#7C3AED',
        'prosjektdeltaker' => '#F97316',
        'deltaker' => '#10B981',
        'tilleggskontakt' => '#6B7280',
        'medlem' => '#9CA3AF',
    );
    
    ?>
    <h2>BIM Verdi Medlemskap</h2>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">Medlemskapsnivå</th>
            <td>
                <?php if ($level): ?>
                    <strong style="color: <?php echo $level_colors[$level]; ?>; font-size: 16px;">
                        <?php echo $level_labels[$level]; ?>
                    </strong>
                <?php else: ?>
                    <span style="color: #999;">Ingen BIM Verdi rolle tildelt</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th scope="row">Status</th>
            <td>
                <?php if ($is_paying): ?>
                    <span style="color: #10B981;">✓ Betalende deltaker</span>
                <?php else: ?>
                    <span style="color: #9CA3AF;">Gratis medlemskap</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th scope="row">Tilknyttet foretak</th>
            <td>
                <?php if ($has_company): 
                    $company = bimverdi_get_user_company($user->ID);
                ?>
                    <a href="<?php echo admin_url('post.php?post=' . $company['id'] . '&action=edit'); ?>">
                        <?php echo esc_html($company['name']); ?>
                    </a>
                    <?php if ($is_hovedkontakt): ?>
                        <span style="color: #7C3AED; font-weight: bold;"> (Hovedkontakt)</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span style="color: #999;">Ikke koblet til foretak</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th scope="row">Rettigheter</th>
            <td>
                <ul style="margin: 0;">
                    <?php
                    $capabilities = array(
                        'read_member_content' => 'Lese medlemsinnhold',
                        'manage_foretak_profile' => 'Redigere foretak-profil',
                        'publish_verktoy' => 'Publisere verktøy',
                        'publish_posts' => 'Publisere artikler',
                        'invite_colleagues' => 'Invitere kolleger (kun hovedkontakt)',
                    );
                    
                    foreach ($capabilities as $cap => $label):
                        $has_cap = user_can($user->ID, $cap);
                        $icon = $has_cap ? '✓' : '✗';
                        $color = $has_cap ? '#10B981' : '#D1D5DB';
                    ?>
                        <li style="color: <?php echo $color; ?>;">
                            <?php echo $icon; ?> <?php echo $label; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Redirect ikke-innloggede brukere fra Min Side
 */
add_action('template_redirect', 'bimverdi_min_side_access_control');
function bimverdi_min_side_access_control() {
    if (is_page('min-side') && !is_user_logged_in()) {
        wp_redirect(home_url('/logg-inn/?redirect_to=' . urlencode(get_permalink())));
        exit;
    }
}

/**
 * Blokker wp-admin for ikke-administratorer.
 * Medlemmer/deltakere skal aldri se WordPress-backend — alt skjer via Min Side.
 * AJAX (admin-ajax.php) og cron tillates fordi frontend bruker disse.
 */
add_action('admin_init', 'bimverdi_block_wp_admin_for_non_admins');
function bimverdi_block_wp_admin_for_non_admins() {
    if (wp_doing_ajax() || wp_doing_cron()) {
        return;
    }
    if (current_user_can('manage_options')) {
        return;
    }
    wp_safe_redirect(home_url('/min-side/'));
    exit;
}

/**
 * Skjul admin-bar (oransje WP-toolbar) for alle ikke-administratorer.
 */
add_filter('show_admin_bar', 'bimverdi_hide_admin_bar_for_non_admins');
function bimverdi_hide_admin_bar_for_non_admins($show) {
    return current_user_can('manage_options') ? $show : false;
}
