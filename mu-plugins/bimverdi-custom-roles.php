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
 * Sjekk om bruker er tilknyttet et foretak
 */
function bimverdi_user_has_foretak($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    // Sjekk via bim_verdi_company_id
    $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
    if (!empty($company_id)) {
        return $company_id;
    }
    
    // Sjekk via bimverdi_company_id (ny standard)
    $company_id = get_user_meta($user_id, 'bimverdi_company_id', true);
    if (!empty($company_id)) {
        return $company_id;
    }
    
    // Sjekk via ACF tilknyttet_foretak
    if (function_exists('get_field')) {
        $foretak = get_field('tilknyttet_foretak', 'user_' . $user_id);
        if ($foretak) {
            return is_object($foretak) ? $foretak->ID : $foretak;
        }
    }
    
    return false;
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
 * Vis rolle i admin user list
 */
add_filter('manage_users_columns', 'bimverdi_add_membership_column');
function bimverdi_add_membership_column($columns) {
    $columns['bimverdi_membership'] = __('Medlemskap', 'bimverdi');
    return $columns;
}

add_action('manage_users_custom_column', 'bimverdi_show_membership_column', 10, 3);
function bimverdi_show_membership_column($value, $column_name, $user_id) {
    if ($column_name === 'bimverdi_membership') {
        $level = bimverdi_get_membership_level($user_id);
        
        $labels = array(
            'partner' => '<span style="color: #7C3AED; font-weight: bold;">★ Partner</span>',
            'prosjektdeltaker' => '<span style="color: #F97316; font-weight: bold;">◆ Prosjektdeltaker</span>',
            'deltaker' => '<span style="color: #10B981;">● Deltaker</span>',
            'tilleggskontakt' => '<span style="color: #6B7280;">+ Tilleggskontakt</span>',
            'medlem' => '<span style="color: #9CA3AF;">○ Medlem</span>',
        );
        
        return $labels[$level] ?? '-';
    }
    
    return $value;
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
        wp_redirect(wp_login_url(get_permalink()));
        exit;
    }
}
