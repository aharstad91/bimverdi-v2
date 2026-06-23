<?php
/**
 * Plugin Name: BIM Verdi – Quick Edit av deltakernivå på foretak
 * Description: Lar admin endre et foretaks deltakernivå rett fra hurtigredigering
 *              i foretak-listen. STILLE operasjon (ingen e-post/varsling, jf. synk
 *              23.06). Setter de tre koblede feltene konsistent (bv_foretakstype +
 *              bv_nivaa + legacy bv_rolle) OG re-synker WP-rollen til foretakets
 *              brukere, slik at tilgang og rolle ikke kommer i utakt.
 *
 * ⚠️  Dette muterer tilgang/roller. Endring er stille og uten faktura/refusjon —
 *     en ren admin-override. Varslingslogistikk er utsatt til etter sommeren.
 *
 * Bygger på den EKSISTERENDE 'deltakernivaa'-kolonnen i bimverdi-admin-enhancements.php
 * (leser bv_rolle). Speiler felt-mappingen fra den kanoniske konverteringen i
 * bimverdi-foretak-konvertering.php (operasjon 1-2 + 3b), men UTEN e-post.
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Mapping: nivå-nøkkel → konsistent felt-trippel.
 * Nøklene matcher dropdown-verdiene; verdiene matcher datamodellen.
 */
function bimverdi_nivaa_field_map() {
    return [
        'gratisforetak'    => ['foretakstype' => 'gratisforetak', 'nivaa' => '',                 'rolle' => 'Ikke deltaker'],
        'deltaker'         => ['foretakstype' => 'foretak',       'nivaa' => 'deltaker',         'rolle' => 'Deltaker'],
        'prosjektdeltaker' => ['foretakstype' => 'foretak',       'nivaa' => 'prosjektdeltaker', 'rolle' => 'Prosjektdeltaker'],
        'partner'          => ['foretakstype' => 'foretak',       'nivaa' => 'partner',          'rolle' => 'Partner'],
    ];
}

/**
 * Avled dropdown-nøkkel fra legacy bv_rolle (det kolonnen viser).
 *
 * @param string $rolle
 * @return string nivå-nøkkel
 */
function bimverdi_nivaa_key_from_rolle($rolle) {
    switch ((string) $rolle) {
        case 'Deltaker':         return 'deltaker';
        case 'Prosjektdeltaker': return 'prosjektdeltaker';
        case 'Partner':          return 'partner';
        default:                 return 'gratisforetak'; // 'Ikke deltaker' / tom
    }
}

/**
 * KANONISK, STILLE setter for et foretaks deltakernivå.
 *
 * Setter bv_foretakstype + bv_nivaa + bv_rolle konsistent, og re-synker WP-rollen
 * til alle koblede brukere. Sender ALDRI e-post. Trygg å kalle fra WP-CLI.
 *
 * @param int    $foretak_id
 * @param string $level  'gratisforetak'|'deltaker'|'prosjektdeltaker'|'partner'
 * @return true|WP_Error
 */
function bimverdi_admin_set_foretak_nivaa($foretak_id, $level) {
    $foretak_id = (int) $foretak_id;
    $map = bimverdi_nivaa_field_map();

    if (!isset($map[$level])) {
        return new WP_Error('invalid_level', 'Ugyldig nivå: ' . $level);
    }
    $post = get_post($foretak_id);
    if (!$post || $post->post_type !== 'foretak') {
        return new WP_Error('invalid_foretak', 'Foretak finnes ikke: ' . $foretak_id);
    }

    $m = $map[$level];

    // Finn koblede brukere FØR mutering (så vi vet hvem som skal re-synkes).
    // Samme oppslag som den kanoniske konverteringen (konvertering.php:159-166):
    // brukere koblet KUN via ACF-fallback 'tilknyttet_foretak' fanges ikke her, men
    // tilgang er live (leser foretak-data) og full bimverdi_run_roles_sync() retter
    // WP-rollen ved neste kjøring. Bevisst konsistent med konverteringen, ikke regresjon.
    // Merk: ved nedgradering til gratisforetak lar vi hovedkontaktperson-feltet stå —
    // ufarlig (tilgang leser bv_foretakstype/bv_rolle), og det re-settes ved ev. ny oppgradering.
    $linked = get_users([
        'meta_query' => [
            'relation' => 'OR',
            ['key' => 'bimverdi_company_id', 'value' => $foretak_id],
            ['key' => 'bim_verdi_company_id', 'value' => $foretak_id],
        ],
        'fields' => ['ID'],
    ]);

    // Skriv den konsistente trippelen.
    update_field('bv_foretakstype', $m['foretakstype'], $foretak_id);
    update_field('bv_nivaa',        $m['nivaa'],        $foretak_id);
    update_field('bv_rolle',        $m['rolle'],        $foretak_id);

    // Rydd ACF/meta-cache så etterfølgende lesninger ser de nye verdiene.
    if (function_exists('acf_flush_value_cache')) {
        acf_flush_value_cache($foretak_id);
    }
    wp_cache_delete($foretak_id, 'post_meta');

    // Re-synk WP-rollen til koblede brukere (stille, idempotent, hopper over admins).
    $synced = [];
    if (function_exists('bimverdi_sync_user_wp_role')) {
        foreach ($linked as $u) {
            $res = bimverdi_sync_user_wp_role((int) $u->ID, false);
            if (!empty($res['changed'])) {
                $synced[] = ['id' => (int) $u->ID, 'from' => $res['from'], 'to' => $res['to']];
            }
        }
    }

    // Cache-purge (samme som konverteringen).
    if (function_exists('bimverdi_purge_foretak_cache')) {
        bimverdi_purge_foretak_cache($foretak_id);
    }
    wp_cache_delete('medlemmer_list', 'bimverdi');
    wp_cache_delete('foretak_' . $foretak_id, 'bimverdi');

    // Audit-logg (ingen e-post — dette ER varslingen).
    $actor = function_exists('wp_get_current_user') ? wp_get_current_user() : null;

    // Varig audit-spor på foretaket (error_log roteres bort på Servebolt; en
    // tilgangsmuterende handling bør ha holdbar sporbarhet). Beholder siste 20.
    $audit = get_post_meta($foretak_id, '_bv_nivaa_audit', true);
    if (!is_array($audit)) {
        $audit = [];
    }
    $audit[] = [
        'tid'    => current_time('mysql'),
        'bruker' => get_current_user_id(),
        'login'  => $actor ? $actor->user_login : '',
        'til'    => $level,
        'rolle'  => $m['rolle'],
        'synket' => array_map(function ($s) { return $s['id'] . ':' . $s['from'] . '→' . $s['to']; }, $synced),
    ];
    update_post_meta($foretak_id, '_bv_nivaa_audit', array_slice($audit, -20));

    error_log(sprintf(
        '[bimverdi-nivaa-quickedit] Foretak %d satt til "%s" (type=%s, nivaa=%s, rolle=%s) av bruker %d (%s). WP-roller re-synket: %s',
        $foretak_id, $level, $m['foretakstype'], $m['nivaa'] ?: '(tom)', $m['rolle'],
        get_current_user_id(), $actor ? $actor->user_login : '?',
        empty($synced) ? 'ingen' : wp_json_encode($synced)
    ));

    /**
     * Hook for evt. framtidig logikk (cache, integrasjoner). Bevisst INGEN
     * varslingsjobb koblet på her ennå (Bård 23.06: stille endring).
     */
    do_action('bimverdi_after_admin_nivaa_change', $foretak_id, $level, $synced);

    return true;
}

// ════════════════════════════════════════════════════════════════════
// QUICK EDIT — UI på foretak-listen (bygger på 'deltakernivaa'-kolonnen)
// ════════════════════════════════════════════════════════════════════

/**
 * Skjult data-bærer i 'deltakernivaa'-cellen for JS-prefill av dropdownen.
 * Kjører på samme kolonne som badge-rendereren (prioritet 20 = etter den).
 */
add_action('manage_foretak_posts_custom_column', 'bimverdi_nivaa_quickedit_inline_data', 20, 2);
function bimverdi_nivaa_quickedit_inline_data($column, $post_id) {
    if ($column !== 'deltakernivaa' || !current_user_can('manage_options')) {
        return;
    }
    $rolle = function_exists('get_field') ? get_field('bv_rolle', $post_id) : get_post_meta($post_id, 'bv_rolle', true);
    $key   = bimverdi_nivaa_key_from_rolle($rolle);
    printf('<span class="bv-qe-nivaa" data-nivaa="%s" style="display:none;"></span>', esc_attr($key));
}

/**
 * Dropdown i hurtigredigerings-skjemaet (kun foretak + 'deltakernivaa'-kolonnen).
 */
add_action('quick_edit_custom_box', 'bimverdi_nivaa_quickedit_box', 10, 2);
function bimverdi_nivaa_quickedit_box($column_name, $post_type) {
    if ($post_type !== 'foretak' || $column_name !== 'deltakernivaa' || !current_user_can('manage_options')) {
        return;
    }
    wp_nonce_field('bv_qe_nivaa', 'bv_qe_nivaa_nonce');
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label class="inline-edit-group">
                <span class="title">Deltakernivå</span>
                <select name="bv_qe_deltakernivaa">
                    <option value="gratisforetak">○ Gratisforetak</option>
                    <option value="deltaker">● Deltaker</option>
                    <option value="prosjektdeltaker">◆ Prosjektdeltaker</option>
                    <option value="partner">★ Partner</option>
                </select>
            </label>
            <p class="howto" style="margin:4px 0 0;">Endrer nivå stille (ingen e-post) + synker WP-rolle.</p>
        </div>
    </fieldset>
    <?php
}

/**
 * Prefill dropdownen med foretakets nåværende nivå når Quick Edit åpnes.
 */
add_action('admin_enqueue_scripts', 'bimverdi_nivaa_quickedit_js');
function bimverdi_nivaa_quickedit_js($hook) {
    if ($hook !== 'edit.php') {
        return;
    }
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'foretak' || !current_user_can('manage_options')) {
        return;
    }
    $js = <<<'JS'
(function($){
    if (typeof inlineEditPost === 'undefined' || !inlineEditPost.edit) { return; }
    var _bvEdit = inlineEditPost.edit;
    inlineEditPost.edit = function(id){
        _bvEdit.apply(this, arguments);
        var postId = 0;
        if (typeof id === 'object') { postId = parseInt(this.getId(id), 10); }
        if (!postId) { return; }
        var nivaa = $('#post-' + postId).find('.bv-qe-nivaa').data('nivaa');
        if (nivaa) {
            $('#edit-' + postId).find('select[name="bv_qe_deltakernivaa"]').val(nivaa);
        }
    };
})(jQuery);
JS;
    wp_add_inline_script('inline-edit-post', $js);
}

/**
 * Lagre quick-edit-valget. Kjører kun ved inline-save (feltet finnes ikke i
 * full-editor-POST), med nonce + cap + autosave-guards.
 */
add_action('save_post_foretak', 'bimverdi_nivaa_quickedit_save', 10, 2);
function bimverdi_nivaa_quickedit_save($post_id, $post) {
    // Bare når vårt quick-edit-felt er med (full editor sender ACF-felter, ikke dette).
    if (!isset($_POST['bv_qe_deltakernivaa'])) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    if (!isset($_POST['bv_qe_nivaa_nonce']) || !wp_verify_nonce($_POST['bv_qe_nivaa_nonce'], 'bv_qe_nivaa')) {
        return;
    }
    if (!current_user_can('manage_options') || !current_user_can('edit_post', $post_id)) {
        return;
    }

    $level = sanitize_text_field(wp_unslash($_POST['bv_qe_deltakernivaa']));
    $map   = bimverdi_nivaa_field_map();
    if (!isset($map[$level])) {
        return;
    }

    // Idempotent: hopp over hvis nivået allerede er det valgte (unngår unødig re-sync).
    $current_rolle = function_exists('get_field') ? get_field('bv_rolle', $post_id) : get_post_meta($post_id, 'bv_rolle', true);
    if (bimverdi_nivaa_key_from_rolle($current_rolle) === $level) {
        return;
    }

    bimverdi_admin_set_foretak_nivaa($post_id, $level);
}
