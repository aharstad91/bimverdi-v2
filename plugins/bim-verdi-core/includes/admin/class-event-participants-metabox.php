<?php
/**
 * Event Participants Metabox
 * 
 * Shows participant list on arrangement edit screen in admin
 * Includes CSV export and manual registration functionality
 * 
 * @package BIM_Verdi_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Event_Participants_Metabox {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_metabox'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_ajax_bimverdi_export_participants', array($this, 'export_csv'));
        add_action('wp_ajax_bimverdi_manual_registration', array($this, 'manual_registration'));
        add_action('wp_ajax_bimverdi_remove_registration', array($this, 'remove_registration'));
    }
    
    /**
     * Add metabox to arrangement post type
     */
    public function add_metabox() {
        add_meta_box(
            'bimverdi_event_participants',
            'Påmeldinger',
            array($this, 'render_metabox'),
            'arrangement',
            'normal',
            'high'
        );
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_styles($hook) {
        global $post_type;
        
        if ($hook === 'post.php' && $post_type === 'arrangement') {
            wp_add_inline_style('wp-admin', '
                .bimverdi-participants-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 15px;
                }
                .bimverdi-participants-table th,
                .bimverdi-participants-table td {
                    padding: 10px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                .bimverdi-participants-table th {
                    background: #f5f5f5;
                    font-weight: 600;
                }
                .bimverdi-participants-table tr:hover {
                    background: #f9f9f9;
                }
                .bimverdi-capacity-badge {
                    display: inline-block;
                    padding: 5px 12px;
                    border-radius: 4px;
                    font-weight: 600;
                    margin-right: 10px;
                }
                .bimverdi-capacity-ok {
                    background: #d4edda;
                    color: #155724;
                }
                .bimverdi-capacity-warning {
                    background: #fff3cd;
                    color: #856404;
                }
                .bimverdi-capacity-full {
                    background: #f8d7da;
                    color: #721c24;
                }
                .bimverdi-actions {
                    margin-top: 15px;
                    display: flex;
                    gap: 10px;
                    flex-wrap: wrap;
                }
                .bimverdi-status-aktiv {
                    color: #155724;
                    background: #d4edda;
                    padding: 2px 8px;
                    border-radius: 3px;
                    font-size: 12px;
                }
                .bimverdi-status-avmeldt {
                    color: #856404;
                    background: #fff3cd;
                    padding: 2px 8px;
                    border-radius: 3px;
                    font-size: 12px;
                }
            ');
        }
    }
    
    /**
     * Render the metabox content
     */
    public function render_metabox($post) {
        $arrangement_id = $post->ID;
        
        // Get registrations
        $registrations = get_posts(array(
            'post_type' => 'pamelding',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'pamelding_arrangement',
                    'value' => $arrangement_id,
                ),
            ),
            'orderby' => 'date',
            'order' => 'ASC',
        ));
        
        // Count active registrations
        $active_count = 0;
        foreach ($registrations as $reg) {
            if (get_field('pamelding_status', $reg->ID) === 'aktiv') {
                $active_count++;
            }
        }
        
        // Get capacity
        $maks_deltakere = get_field('maks_deltakere', $arrangement_id);
        
        // Capacity badge
        $capacity_class = 'bimverdi-capacity-ok';
        $capacity_text = $active_count . ' påmeldt';
        
        if ($maks_deltakere) {
            $capacity_text = $active_count . '/' . $maks_deltakere . ' plasser';
            $percentage = ($active_count / $maks_deltakere) * 100;
            
            if ($percentage >= 100) {
                $capacity_class = 'bimverdi-capacity-full';
            } elseif ($percentage >= 80) {
                $capacity_class = 'bimverdi-capacity-warning';
            }
        }
        
        ?>
        <div class="bimverdi-participants-wrapper">
            
            <!-- Capacity Overview -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div>
                    <span class="bimverdi-capacity-badge <?php echo $capacity_class; ?>">
                        <?php echo $capacity_text; ?>
                    </span>
                    <?php if ($maks_deltakere && $active_count >= $maks_deltakere): ?>
                        <span style="color: #721c24; font-weight: 600;">⚠️ Fulltegnet</span>
                    <?php endif; ?>
                </div>
                
                <!-- Actions -->
                <div class="bimverdi-actions">
                    <button type="button" class="button button-secondary" onclick="bimverdiExportCSV(<?php echo $arrangement_id; ?>)">
                        <span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
                        Eksporter til CSV
                    </button>
                    <button type="button" class="button button-primary" onclick="bimverdiShowAddParticipant()">
                        <span class="dashicons dashicons-plus" style="vertical-align: middle;"></span>
                        Legg til deltaker
                    </button>
                </div>
            </div>
            
            <!-- Add Participant Form (hidden by default) -->
            <div id="bimverdi-add-participant-form" style="display: none; margin-top: 15px; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                <h4 style="margin-top: 0;">Legg til deltaker manuelt</h4>
                <table class="form-table">
                    <tr>
                        <th><label for="manual_user">Velg bruker</label></th>
                        <td>
                            <select id="manual_user" style="width: 300px;">
                                <option value="">-- Velg bruker --</option>
                                <?php
                                $users = get_users(array('orderby' => 'display_name'));
                                foreach ($users as $user) {
                                    // Check if already registered
                                    $already_registered = false;
                                    foreach ($registrations as $reg) {
                                        if (get_field('pamelding_bruker', $reg->ID) == $user->ID &&
                                            get_field('pamelding_status', $reg->ID) === 'aktiv') {
                                            $already_registered = true;
                                            break;
                                        }
                                    }
                                    if (!$already_registered) {
                                        echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . ' (' . $user->user_email . ')</option>';
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" onclick="bimverdiAddParticipant(<?php echo $arrangement_id; ?>)">
                        Legg til
                    </button>
                    <button type="button" class="button button-secondary" onclick="document.getElementById('bimverdi-add-participant-form').style.display='none';">
                        Avbryt
                    </button>
                </p>
            </div>
            
            <!-- Participants Table -->
            <?php if (!empty($registrations)): ?>
                <table class="bimverdi-participants-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Navn</th>
                            <th>Foretak</th>
                            <th>E-post</th>
                            <th>Telefon</th>
                            <th>Påmeldt</th>
                            <th>Status</th>
                            <th>Handling</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 0;
                        foreach ($registrations as $reg):
                            $user_id = get_field('pamelding_bruker', $reg->ID);
                            $user = get_userdata($user_id);
                            if (!$user) continue;
                            
                            $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
                            $company = $company_id ? get_post($company_id) : null;
                            $telefon = get_user_meta($user_id, 'telefon', true);
                            $pameldt_tid = get_field('tidspunkt_pameldt', $reg->ID);
                            $status = get_field('pamelding_status', $reg->ID);
                            $notater = get_field('pamelding_notater', $reg->ID);
                            
                            // Extract phone from notes if stored there
                            if (!$telefon && $notater && preg_match('/Telefon:\s*([^\n]+)/', $notater, $matches)) {
                                $telefon = trim($matches[1]);
                            }
                            
                            $counter++;
                        ?>
                        <tr data-registration-id="<?php echo $reg->ID; ?>">
                            <td><?php echo $counter; ?></td>
                            <td>
                                <strong><?php echo esc_html($user->display_name); ?></strong>
                            </td>
                            <td><?php echo $company ? esc_html($company->post_title) : '<em>-</em>'; ?></td>
                            <td>
                                <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                                    <?php echo esc_html($user->user_email); ?>
                                </a>
                            </td>
                            <td><?php echo $telefon ? esc_html($telefon) : '<em>-</em>'; ?></td>
                            <td>
                                <?php 
                                if ($pameldt_tid) {
                                    echo date('d.m.Y H:i', strtotime($pameldt_tid));
                                } else {
                                    echo date('d.m.Y H:i', strtotime($reg->post_date));
                                }
                                ?>
                            </td>
                            <td>
                                <span class="bimverdi-status-<?php echo $status; ?>">
                                    <?php echo $status === 'aktiv' ? 'Aktiv' : ($status === 'avmeldt' ? 'Avmeldt' : 'Gjennomført'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($status === 'aktiv'): ?>
                                    <button type="button" class="button button-small" 
                                            onclick="bimverdiRemoveParticipant(<?php echo $reg->ID; ?>, '<?php echo esc_js($user->display_name); ?>')">
                                        <span class="dashicons dashicons-no" style="vertical-align: middle;"></span>
                                        Fjern
                                    </button>
                                <?php else: ?>
                                    <em style="color: #666;">-</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="margin-top: 15px; color: #666;">
                    <em>Ingen påmeldinger ennå.</em>
                </p>
            <?php endif; ?>
            
        </div>
        
        <script>
        function bimverdiShowAddParticipant() {
            document.getElementById('bimverdi-add-participant-form').style.display = 'block';
        }
        
        function bimverdiExportCSV(arrangementId) {
            window.location.href = ajaxurl + '?action=bimverdi_export_participants&arrangement_id=' + arrangementId + '&_wpnonce=<?php echo wp_create_nonce('export_participants'); ?>';
        }
        
        function bimverdiAddParticipant(arrangementId) {
            var userId = document.getElementById('manual_user').value;
            
            if (!userId) {
                alert('Velg en bruker');
                return;
            }
            
            // Disable button during request
            var btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Legger til...';
            
            jQuery.post(ajaxurl, {
                action: 'bimverdi_manual_registration',
                arrangement_id: arrangementId,
                user_id: userId,
                _wpnonce: '<?php echo wp_create_nonce('manual_registration'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Feil: ' + (response.data || 'Ukjent feil'));
                    btn.disabled = false;
                    btn.textContent = 'Legg til';
                }
            }).fail(function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                alert('Teknisk feil: ' + error);
                btn.disabled = false;
                btn.textContent = 'Legg til';
            });
        }
        
        function bimverdiRemoveParticipant(registrationId, userName) {
            if (!confirm('Er du sikker på at du vil fjerne ' + userName + ' fra arrangementet?')) {
                return;
            }
            
            jQuery.post(ajaxurl, {
                action: 'bimverdi_remove_registration',
                registration_id: registrationId,
                _wpnonce: '<?php echo wp_create_nonce('remove_registration'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Feil: ' + response.data);
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Export participants to CSV
     */
    public function export_csv() {
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        
        check_admin_referer('export_participants');
        
        $arrangement_id = intval($_GET['arrangement_id']);
        $arrangement = get_post($arrangement_id);
        
        if (!$arrangement || $arrangement->post_type !== 'arrangement') {
            wp_die('Ugyldig arrangement');
        }
        
        // Get registrations
        $registrations = get_posts(array(
            'post_type' => 'pamelding',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'pamelding_arrangement',
                    'value' => $arrangement_id,
                ),
                array(
                    'key' => 'pamelding_status',
                    'value' => 'aktiv',
                ),
            ),
            'orderby' => 'date',
            'order' => 'ASC',
        ));
        
        // Set headers for CSV download
        $filename = sanitize_file_name('deltakere-' . $arrangement->post_name . '-' . date('Y-m-d')) . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        // Create output
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header row
        fputcsv($output, array(
            'Nr',
            'Navn',
            'E-post',
            'Foretak',
            'Telefon',
            'Påmeldt dato',
            'Status',
        ), ';');
        
        // Data rows
        $counter = 0;
        foreach ($registrations as $reg) {
            $user_id = get_field('pamelding_bruker', $reg->ID);
            $user = get_userdata($user_id);
            if (!$user) continue;
            
            $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
            $company = $company_id ? get_post($company_id) : null;
            $telefon = get_user_meta($user_id, 'telefon', true);
            $pameldt_tid = get_field('tidspunkt_pameldt', $reg->ID);
            $status = get_field('pamelding_status', $reg->ID);
            
            $counter++;
            
            fputcsv($output, array(
                $counter,
                $user->display_name,
                $user->user_email,
                $company ? $company->post_title : '',
                $telefon ?: '',
                $pameldt_tid ? date('d.m.Y H:i', strtotime($pameldt_tid)) : date('d.m.Y H:i', strtotime($reg->post_date)),
                $status === 'aktiv' ? 'Aktiv' : ($status === 'avmeldt' ? 'Avmeldt' : 'Gjennomført'),
            ), ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Manual registration via AJAX
     */
    public function manual_registration() {
        check_ajax_referer('manual_registration');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Ikke tilgang');
        }
        
        $arrangement_id = intval($_POST['arrangement_id']);
        $user_id = intval($_POST['user_id']);
        $note = sanitize_text_field($_POST['note']);
        
        // Validate
        $arrangement = get_post($arrangement_id);
        $user = get_userdata($user_id);
        
        if (!$arrangement || !$user) {
            wp_send_json_error('Ugyldig arrangement eller bruker');
        }
        
        // Check if already registered
        $existing = get_posts(array(
            'post_type' => 'pamelding',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => 'pamelding_arrangement',
                    'value' => $arrangement_id,
                ),
                array(
                    'key' => 'pamelding_bruker',
                    'value' => $user_id,
                ),
                array(
                    'key' => 'pamelding_status',
                    'value' => 'aktiv',
                ),
            ),
        ));
        
        if (!empty($existing)) {
            wp_send_json_error('Brukeren er allerede påmeldt');
        }
        
        // Create registration
        $pamelding_data = array(
            'post_title' => sprintf('%s - %s', $user->display_name, $arrangement->post_title),
            'post_type' => 'pamelding',
            'post_status' => 'publish',
        );
        
        $pamelding_id = wp_insert_post($pamelding_data);
        
        if (is_wp_error($pamelding_id)) {
            wp_send_json_error('Kunne ikke opprette påmelding');
        }
        
        // Set ACF fields
        update_field('pamelding_bruker', $user_id, $pamelding_id);
        update_field('pamelding_arrangement', $arrangement_id, $pamelding_id);
        update_field('tidspunkt_pameldt', current_time('Y-m-d H:i:s'), $pamelding_id);
        update_field('pamelding_status', 'aktiv', $pamelding_id);
        
        // Add note
        $admin_note = sprintf('[%s] Lagt til manuelt av admin', current_time('d.m.Y H:i'));
        if ($note) {
            $admin_note .= ': ' . $note;
        }
        update_field('pamelding_notater', $admin_note, $pamelding_id);
        
        wp_send_json_success(array('pamelding_id' => $pamelding_id));
    }
    
    /**
     * Remove registration via AJAX
     */
    public function remove_registration() {
        check_ajax_referer('remove_registration');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Ikke tilgang');
        }
        
        $registration_id = intval($_POST['registration_id']);
        $registration = get_post($registration_id);
        
        if (!$registration || $registration->post_type !== 'pamelding') {
            wp_send_json_error('Ugyldig påmelding');
        }
        
        // Update status to avmeldt
        update_field('pamelding_status', 'avmeldt', $registration_id);
        
        // Add note
        $existing_notes = get_field('pamelding_notater', $registration_id) ?: '';
        $new_note = sprintf("\n[%s] Fjernet av admin", current_time('d.m.Y H:i'));
        update_field('pamelding_notater', $existing_notes . $new_note, $registration_id);
        
        wp_send_json_success();
    }
}

// Initialize
new BIM_Verdi_Event_Participants_Metabox();
