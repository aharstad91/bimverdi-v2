<?php
/**
 * Leads Admin Dashboard
 * 
 * Provides an admin interface for viewing and managing leads
 * submitted through the søknadsbistand form
 * 
 * @package BIM_Verdi_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Leads_Admin {
    
    /**
     * Initialize the admin features
     */
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_leads_menu'));
        
        // Add status column to case list
        add_filter('manage_case_posts_columns', array($this, 'add_case_columns'));
        add_action('manage_case_posts_custom_column', array($this, 'render_case_columns'), 10, 2);
        add_filter('manage_edit-case_sortable_columns', array($this, 'sortable_columns'));
        
        // Add quick edit for status
        add_action('quick_edit_custom_box', array($this, 'quick_edit_status'), 10, 2);
        add_action('save_post_case', array($this, 'save_quick_edit'), 10, 2);
        
        // Add status filter dropdown
        add_action('restrict_manage_posts', array($this, 'add_status_filter'));
        add_filter('parse_query', array($this, 'filter_by_status'));
        
        // Add dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        
        // Add admin CSS
        add_action('admin_head', array($this, 'admin_styles'));
        
        // Add metabox for lead contact info
        add_action('add_meta_boxes', array($this, 'add_lead_metabox'));
    }
    
    /**
     * Add Leads submenu under Prosjektidéer
     */
    public function add_leads_menu() {
        add_submenu_page(
            'edit.php?post_type=case',
            'Leads Dashboard',
            '📊 Leads Dashboard',
            'edit_posts',
            'leads-dashboard',
            array($this, 'render_leads_dashboard')
        );
    }
    
    /**
     * Add custom columns to case list
     */
    public function add_case_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['lead_status'] = 'Status';
                $new_columns['lead_contact'] = 'Kontakt';
                $new_columns['lead_company'] = 'Foretak';
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Render custom columns
     */
    public function render_case_columns($column, $post_id) {
        switch ($column) {
            case 'lead_status':
                $status = get_field('case_status', $post_id);
                // Partnership-focused statuses (not approval/rejection)
                $status_labels = array(
                    'ny' => array('Ny henvendelse', '#22c55e', '#dcfce7'),
                    'under_vurdering' => array('Under vurdering', '#f59e0b', '#fef3c7'),
                    'kontaktet' => array('Kontaktet', '#3b82f6', '#dbeafe'),
                    'i_samarbeid' => array('I samarbeid', '#10b981', '#d1fae5'),
                    'arkivert' => array('Arkivert', '#6b7280', '#f3f4f6'),
                    // Legacy support
                    'godkjent' => array('I samarbeid', '#10b981', '#d1fae5'),
                    'avslag' => array('Arkivert', '#6b7280', '#f3f4f6'),
                );
                
                if (isset($status_labels[$status])) {
                    $label = $status_labels[$status];
                    printf(
                        '<span class="lead-status-badge" style="background: %s; color: %s; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">%s</span>',
                        esc_attr($label[2]),
                        esc_attr($label[1]),
                        esc_html($label[0])
                    );
                } else {
                    echo '<span style="color: #9ca3af;">—</span>';
                }
                break;
                
            case 'lead_contact':
                $name = get_post_meta($post_id, '_lead_contact_name', true);
                $email = get_post_meta($post_id, '_lead_contact_email', true);
                
                if ($name) {
                    echo '<strong>' . esc_html($name) . '</strong>';
                    if ($email) {
                        echo '<br><a href="mailto:' . esc_attr($email) . '" style="font-size: 12px;">' . esc_html($email) . '</a>';
                    }
                } else {
                    // Fall back to ACF user field
                    $user_id = get_field('innsendt_av', $post_id);
                    if ($user_id) {
                        $user = get_userdata($user_id);
                        if ($user) {
                            echo '<strong>' . esc_html($user->display_name) . '</strong>';
                            echo '<br><a href="mailto:' . esc_attr($user->user_email) . '" style="font-size: 12px;">' . esc_html($user->user_email) . '</a>';
                        }
                    } else {
                        echo '<span style="color: #9ca3af;">—</span>';
                    }
                }
                break;
                
            case 'lead_company':
                $company = get_post_meta($post_id, '_lead_contact_company', true);
                if ($company) {
                    echo esc_html($company);
                } else {
                    // Fall back to ACF relationship
                    $company_id = get_field('bedrift', $post_id);
                    if ($company_id) {
                        $company_post = get_post($company_id);
                        if ($company_post) {
                            echo '<a href="' . get_edit_post_link($company_id) . '">' . esc_html($company_post->post_title) . '</a>';
                        }
                    } else {
                        echo '<span style="color: #9ca3af;">—</span>';
                    }
                }
                break;
        }
    }
    
    /**
     * Make columns sortable
     */
    public function sortable_columns($columns) {
        $columns['lead_status'] = 'lead_status';
        return $columns;
    }
    
    /**
     * Add status filter dropdown
     */
    public function add_status_filter($post_type) {
        if ($post_type !== 'case') return;
        
        $current = isset($_GET['case_status_filter']) ? $_GET['case_status_filter'] : '';
        
        // Partnership-focused statuses (not approval/rejection)
        $statuses = array(
            '' => 'Alle statuser',
            'ny' => 'Ny henvendelse',
            'under_vurdering' => 'Under vurdering',
            'kontaktet' => 'Kontaktet',
            'i_samarbeid' => 'I samarbeid',
            'arkivert' => 'Arkivert',
        );
        
        echo '<select name="case_status_filter">';
        foreach ($statuses as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($current, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';
    }
    
    /**
     * Filter query by status
     */
    public function filter_by_status($query) {
        global $pagenow;
        
        if (!is_admin() || $pagenow !== 'edit.php') return;
        if (!isset($_GET['post_type']) || $_GET['post_type'] !== 'case') return;
        if (empty($_GET['case_status_filter'])) return;
        
        $query->query_vars['meta_query'] = array(
            array(
                'key' => 'case_status',
                'value' => sanitize_text_field($_GET['case_status_filter']),
            ),
        );
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'bimverdi_leads_widget',
            '📋 Prosjektidéer / Leads',
            array($this, 'render_dashboard_widget')
        );
    }
    
    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget() {
        // Get counts by status - partnership focused
        $statuses = array('ny', 'under_vurdering', 'kontaktet', 'i_samarbeid', 'arkivert');
        $counts = array();
        
        foreach ($statuses as $status) {
            $query = new WP_Query(array(
                'post_type' => 'case',
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => 'case_status',
                        'value' => $status,
                    ),
                ),
                'posts_per_page' => -1,
                'fields' => 'ids',
            ));
            $counts[$status] = $query->found_posts;
        }
        
        // Partnership-focused statuses
        $status_labels = array(
            'ny' => array('Ny henvendelse', '#22c55e'),
            'under_vurdering' => array('Under vurdering', '#f59e0b'),
            'kontaktet' => array('Kontaktet', '#3b82f6'),
            'i_samarbeid' => array('I samarbeid', '#10b981'),
            'arkivert' => array('Arkivert', '#6b7280'),
            // Legacy support
            'godkjent' => array('I samarbeid', '#10b981'),
            'avslag' => array('Arkivert', '#6b7280'),
        );
        
        echo '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 15px;">';
        foreach ($counts as $status => $count) {
            $label = $status_labels[$status];
            printf(
                '<div style="text-align: center; padding: 10px; background: #f9fafb; border-radius: 6px;">
                    <div style="font-size: 24px; font-weight: bold; color: %s;">%d</div>
                    <div style="font-size: 11px; color: #6b7280;">%s</div>
                </div>',
                esc_attr($label[1]),
                $count,
                esc_html($label[0])
            );
        }
        echo '</div>';
        
        // Recent leads
        $recent = new WP_Query(array(
            'post_type' => 'case',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        if ($recent->have_posts()) {
            echo '<h4 style="margin: 15px 0 10px;">Siste henvendelser</h4>';
            echo '<ul style="margin: 0; padding: 0; list-style: none;">';
            while ($recent->have_posts()) {
                $recent->the_post();
                $status = get_field('case_status');
                $status_color = isset($status_labels[$status]) ? $status_labels[$status][1] : '#6b7280';
                
                printf(
                    '<li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%%; background: %s; margin-right: 8px;"></span>
                        <a href="%s"><strong>%s</strong></a>
                        <span style="color: #9ca3af; font-size: 11px; margin-left: 8px;">%s</span>
                    </li>',
                    esc_attr($status_color),
                    get_edit_post_link(),
                    get_the_title(),
                    get_the_date()
                );
            }
            echo '</ul>';
            wp_reset_postdata();
        }
        
        printf(
            '<p style="margin-top: 15px;"><a href="%s" class="button">Se alle prosjektidéer →</a></p>',
            admin_url('edit.php?post_type=case')
        );
    }
    
    /**
     * Add metabox for lead contact info
     */
    public function add_lead_metabox() {
        add_meta_box(
            'lead_contact_info',
            '📧 Kontaktinformasjon (Lead)',
            array($this, 'render_lead_metabox'),
            'case',
            'side',
            'high'
        );
        
        add_meta_box(
            'lead_details',
            '📋 Prosjektdetaljer',
            array($this, 'render_details_metabox'),
            'case',
            'normal',
            'high'
        );
    }
    
    /**
     * Render lead contact metabox
     */
    public function render_lead_metabox($post) {
        $name = get_post_meta($post->ID, '_lead_contact_name', true);
        $email = get_post_meta($post->ID, '_lead_contact_email', true);
        $company = get_post_meta($post->ID, '_lead_contact_company', true);
        $phone = get_post_meta($post->ID, '_lead_contact_phone', true);
        $source = get_post_meta($post->ID, '_lead_source', true);
        
        if (!$name && !$email) {
            echo '<p style="color: #9ca3af; font-style: italic;">Ikke en lead fra offentlig skjema.</p>';
            return;
        }
        
        echo '<table style="width: 100%;">';
        
        if ($name) {
            printf('<tr><td style="padding: 4px 0;"><strong>Navn:</strong></td><td>%s</td></tr>', esc_html($name));
        }
        if ($email) {
            printf('<tr><td style="padding: 4px 0;"><strong>E-post:</strong></td><td><a href="mailto:%s">%s</a></td></tr>', esc_attr($email), esc_html($email));
        }
        if ($company) {
            printf('<tr><td style="padding: 4px 0;"><strong>Foretak:</strong></td><td>%s</td></tr>', esc_html($company));
        }
        if ($phone) {
            printf('<tr><td style="padding: 4px 0;"><strong>Telefon:</strong></td><td><a href="tel:%s">%s</a></td></tr>', esc_attr($phone), esc_html($phone));
        }
        if ($source === 'public_form') {
            echo '<tr><td colspan="2" style="padding-top: 10px;"><span style="background: #FDF6E3; color: #D97706; padding: 2px 6px; border-radius: 3px; font-size: 11px;">🌐 Offentlig skjema</span></td></tr>';
        }
        
        echo '</table>';
        
        // Quick action buttons
        if ($email) {
            printf(
                '<div style="margin-top: 15px;">
                    <a href="mailto:%s?subject=Angående din prosjektidé: %s" class="button button-primary" style="width: 100%%; text-align: center; margin-bottom: 5px;">📧 Send e-post</a>
                </div>',
                esc_attr($email),
                rawurlencode(get_the_title($post->ID))
            );
        }
    }
    
    /**
     * Render project details metabox
     */
    public function render_details_metabox($post) {
        $benefit_business = get_post_meta($post->ID, '_lead_benefit_business', true);
        $benefit_society = get_post_meta($post->ID, '_lead_benefit_society', true);
        $need_knowledge = get_post_meta($post->ID, '_lead_need_knowledge', true);
        $timeframe = get_post_meta($post->ID, '_lead_timeframe', true);
        
        if (!$benefit_business && !$benefit_society) {
            echo '<p style="color: #9ca3af; font-style: italic;">Ingen utfyllende detaljer fra skjema.</p>';
            return;
        }
        
        $timeframe_labels = array(
            'under_6_mnd' => 'Under 6 måneder',
            '6_12_mnd' => '6-12 måneder',
            '1_2_ar' => '1-2 år',
            'over_2_ar' => 'Over 2 år',
            'usikker' => 'Usikker',
        );
        
        echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">';
        
        if ($benefit_business) {
            echo '<div>';
            echo '<h4 style="margin: 0 0 5px;">💼 Nytte for bedrift</h4>';
            echo '<p style="margin: 0; color: #4b5563;">' . nl2br(esc_html($benefit_business)) . '</p>';
            echo '</div>';
        }
        
        if ($benefit_society) {
            echo '<div>';
            echo '<h4 style="margin: 0 0 5px;">🌍 Nytte for samfunn</h4>';
            echo '<p style="margin: 0; color: #4b5563;">' . nl2br(esc_html($benefit_society)) . '</p>';
            echo '</div>';
        }
        
        echo '</div>';
        
        if ($need_knowledge) {
            echo '<div style="margin-top: 15px;">';
            echo '<h4 style="margin: 0 0 5px;">🔬 Behov for FoU/ny kunnskap</h4>';
            echo '<p style="margin: 0; color: #4b5563;">' . nl2br(esc_html($need_knowledge)) . '</p>';
            echo '</div>';
        }
        
        if ($timeframe && isset($timeframe_labels[$timeframe])) {
            printf(
                '<div style="margin-top: 15px;">
                    <span style="background: #dbeafe; color: #1d4ed8; padding: 4px 10px; border-radius: 4px; font-size: 13px;">
                        ⏱️ Tidsramme: %s
                    </span>
                </div>',
                esc_html($timeframe_labels[$timeframe])
            );
        }
    }
    
    /**
     * Admin styles
     */
    public function admin_styles() {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'case') return;
        
        echo '<style>
            .lead-status-badge {
                display: inline-block;
                white-space: nowrap;
            }
            .column-lead_status { width: 120px; }
            .column-lead_contact { width: 200px; }
            .column-lead_company { width: 150px; }
        </style>';
    }
    
    /**
     * Render leads dashboard page
     */
    public function render_leads_dashboard() {
        // Get statistics
        $total = wp_count_posts('case')->publish;
        
        // Partnership-focused statuses (no approval/rejection)
        $statuses = array('ny', 'under_vurdering', 'kontaktet', 'i_samarbeid', 'arkivert');
        $counts = array();
        
        foreach ($statuses as $status) {
            $query = new WP_Query(array(
                'post_type' => 'case',
                'post_status' => 'publish',
                'meta_query' => array(
                    array('key' => 'case_status', 'value' => $status),
                ),
                'posts_per_page' => -1,
                'fields' => 'ids',
            ));
            $counts[$status] = $query->found_posts;
        }
        
        ?>
        <div class="wrap">
            <h1>📊 Leads Dashboard - Prosjektidéer</h1>
            
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin: 20px 0;">
                <?php
                // Partnership-focused statuses
                $status_info = array(
                    'ny' => array('Nye henvendelser', '#22c55e', '🆕'),
                    'under_vurdering' => array('Under vurdering', '#f59e0b', '🔍'),
                    'kontaktet' => array('Kontaktet', '#3b82f6', '📞'),
                    'i_samarbeid' => array('I samarbeid', '#10b981', '🤝'),
                    'arkivert' => array('Arkivert', '#6b7280', '📁'),
                );
                
                foreach ($counts as $status => $count) {
                    $info = $status_info[$status];
                    $filter_url = admin_url('edit.php?post_type=case&case_status_filter=' . $status);
                    ?>
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; text-align: center;">
                        <div style="font-size: 32px; margin-bottom: 5px;"><?php echo $info[2]; ?></div>
                        <div style="font-size: 36px; font-weight: bold; color: <?php echo esc_attr($info[1]); ?>;">
                            <?php echo $count; ?>
                        </div>
                        <div style="color: #6b7280; margin-bottom: 10px;"><?php echo esc_html($info[0]); ?></div>
                        <a href="<?php echo esc_url($filter_url); ?>" class="button button-small">Vis →</a>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <!-- Recent leads -->
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
                    <h2 style="margin-top: 0;">🕐 Siste henvendelser</h2>
                    <?php
                    $recent = new WP_Query(array(
                        'post_type' => 'case',
                        'post_status' => 'publish',
                        'posts_per_page' => 10,
                        'orderby' => 'date',
                        'order' => 'DESC',
                    ));
                    
                    if ($recent->have_posts()) {
                        echo '<table class="widefat striped">';
                        echo '<thead><tr><th>Tittel</th><th>Kontakt</th><th>Status</th><th>Dato</th><th></th></tr></thead>';
                        echo '<tbody>';
                        
                        while ($recent->have_posts()) {
                            $recent->the_post();
                            $status = get_field('case_status');
                            $name = get_post_meta(get_the_ID(), '_lead_contact_name', true);
                            $company = get_post_meta(get_the_ID(), '_lead_contact_company', true);
                            
                            if (!$name) {
                                $user_id = get_field('innsendt_av');
                                if ($user_id) {
                                    $user = get_userdata($user_id);
                                    $name = $user ? $user->display_name : '';
                                }
                            }
                            
                            $status_label = isset($status_info[$status]) ? $status_info[$status] : array($status, '#6b7280', '❓');
                            
                            printf(
                                '<tr>
                                    <td><strong><a href="%s">%s</a></strong></td>
                                    <td>%s<br><small style="color: #9ca3af;">%s</small></td>
                                    <td><span style="background: %s20; color: %s; padding: 2px 8px; border-radius: 4px; font-size: 12px;">%s %s</span></td>
                                    <td>%s</td>
                                    <td><a href="%s" class="button button-small">Åpne</a></td>
                                </tr>',
                                get_edit_post_link(),
                                get_the_title(),
                                esc_html($name ?: '—'),
                                esc_html($company ?: ''),
                                esc_attr($status_label[1]),
                                esc_attr($status_label[1]),
                                $status_label[2],
                                esc_html($status_label[0]),
                                get_the_date('d.m.Y'),
                                get_edit_post_link()
                            );
                        }
                        
                        echo '</tbody></table>';
                        wp_reset_postdata();
                    } else {
                        echo '<p style="color: #9ca3af;">Ingen prosjektidéer ennå.</p>';
                    }
                    ?>
                </div>
                
                <!-- Quick stats -->
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
                    <h2 style="margin-top: 0;">📈 Statistikk</h2>
                    
                    <div style="margin-bottom: 20px;">
                        <div style="font-size: 48px; font-weight: bold; color: #1f2937;"><?php echo $total; ?></div>
                        <div style="color: #6b7280;">Totalt antall idéer</div>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <h4>Samarbeidsrate</h4>
                        <?php
                        $active_collabs = isset($counts['i_samarbeid']) ? $counts['i_samarbeid'] : 0;
                        $conversion = $total > 0 ? round(($active_collabs / $total) * 100, 1) : 0;
                        ?>
                        <div style="background: #e5e7eb; border-radius: 4px; height: 24px; overflow: hidden;">
                            <div style="background: #22c55e; height: 100%; width: <?php echo $conversion; ?>%;"></div>
                        </div>
                        <div style="text-align: center; margin-top: 5px; color: #6b7280;"><?php echo $conversion; ?>% i samarbeid</div>
                    </div>
                    
                    <hr>
                    
                    <h4>Raske handlinger</h4>
                    <p>
                        <a href="<?php echo admin_url('edit.php?post_type=case&case_status_filter=ny'); ?>" class="button" style="width: 100%; margin-bottom: 10px;">
                            🆕 Behandle nye (<?php echo $counts['ny']; ?>)
                        </a>
                        <a href="<?php echo admin_url('post-new.php?post_type=case'); ?>" class="button" style="width: 100%;">
                            ➕ Legg til manuelt
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Quick edit for status
     */
    public function quick_edit_status($column_name, $post_type) {
        if ($column_name !== 'lead_status' || $post_type !== 'case') return;
        
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label>
                    <span class="title">Status</span>
                    <select name="case_status_quick">
                        <option value="">— Ingen endring —</option>
                        <option value="ny">Ny henvendelse</option>
                        <option value="under_vurdering">Under vurdering</option>
                        <option value="kontaktet">Kontaktet</option>
                        <option value="i_samarbeid">I samarbeid</option>
                        <option value="arkivert">Arkivert</option>
                    </select>
                </label>
            </div>
        </fieldset>
        <?php
    }
    
    /**
     * Save quick edit
     */
    public function save_quick_edit($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        if (isset($_POST['case_status_quick']) && !empty($_POST['case_status_quick'])) {
            update_field('case_status', sanitize_text_field($_POST['case_status_quick']), $post_id);
        }
    }
}

// Initialize
new BIM_Verdi_Leads_Admin();
