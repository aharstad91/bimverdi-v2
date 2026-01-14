<?php
/**
 * Force recreate event form
 * Visit: /wp-admin/?recreate_event_form=1
 */

add_action('admin_init', function() {
    if (isset($_GET['recreate_event_form']) && current_user_can('manage_options')) {
        delete_option('bimverdi_event_form_created');
        
        // Force reload to trigger form creation
        if (!isset($_GET['recreated'])) {
            wp_redirect(admin_url('admin.php?page=gf_edit_forms&recreated=1'));
            exit;
        }
    }
});
