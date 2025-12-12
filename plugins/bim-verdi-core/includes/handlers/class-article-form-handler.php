<?php
/**
 * Article Form Handler
 * 
 * Handles Gravity Forms submissions for article creation
 * Creates artikkel post with proper relationships and status
 * 
 * @package BIM_Verdi_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Article_Form_Handler {
    
    /**
     * Constructor - hook into Gravity Forms
     */
    public function __construct() {
        // Hook after submission
        add_action('gform_after_submission', array($this, 'handle_article_submission'), 10, 2);
    }
    
    /**
     * Handle article form submission
     */
    public function handle_article_submission($entry, $form) {
        // Check if this is the article form
        if (strpos($form['title'], 'Skriv artikkel') === false) {
            return;
        }
        
        // Get form data
        $title = rgar($entry, '2');
        $ingress = rgar($entry, '3');
        $content = rgar($entry, '4');
        $kategori = rgar($entry, '6');
        $temagrupper = rgar($entry, '7'); // Checkbox returns comma-separated values
        $user_id = rgar($entry, '8');
        $company_id = rgar($entry, '9');
        $save_as_draft = rgar($entry, '10');
        
        // Fallback to current user if hidden field didn't work
        if (empty($user_id)) {
            $user_id = get_current_user_id();
        }
        
        // Get company ID from user meta if not in form
        if (empty($company_id)) {
            $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
        }
        
        // Determine post status and artikkel_status
        $is_draft = !empty($save_as_draft) && strpos($save_as_draft, 'utkast') !== false;
        $post_status = 'pending'; // All submissions go to pending for admin review
        $artikkel_status = $is_draft ? 'utkast' : 'til_godkjenning';
        
        // Create the article post
        $post_data = array(
            'post_title'   => sanitize_text_field($title),
            'post_content' => wp_kses_post($content),
            'post_excerpt' => sanitize_textarea_field($ingress),
            'post_status'  => $post_status,
            'post_type'    => 'artikkel',
            'post_author'  => $user_id,
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            error_log('BIM Verdi Article Handler: Kunne ikke opprette artikkel - ' . $post_id->get_error_message());
            return;
        }
        
        // Save ACF fields
        if (function_exists('update_field')) {
            update_field('artikkel_bedrift', $company_id, $post_id);
            update_field('artikkel_status', $artikkel_status, $post_id);
            update_field('artikkel_ingress', $ingress, $post_id);
            update_field('artikkel_kategori', $kategori, $post_id);
        }
        
        // Handle temagruppe taxonomy
        if (!empty($temagrupper)) {
            $temagruppe_slugs = array_map('trim', explode(',', $temagrupper));
            
            // Map checkbox values to term slugs
            $term_mapping = array(
                'byggesaksbim' => 'byggesaksbim',
                'prosjektbim' => 'prosjektbim',
                'eiendomsbim' => 'eiendomsbim',
                'miljobim' => 'miljobim',
                'sirkbim' => 'sirkbim',
                'bimtech' => 'bimtech',
            );
            
            $term_ids = array();
            foreach ($temagruppe_slugs as $slug) {
                $slug = strtolower(trim($slug));
                if (isset($term_mapping[$slug])) {
                    $term = get_term_by('slug', $term_mapping[$slug], 'temagruppe');
                    if ($term) {
                        $term_ids[] = $term->term_id;
                    }
                }
            }
            
            if (!empty($term_ids)) {
                wp_set_post_terms($post_id, $term_ids, 'temagruppe');
            }
        }
        
        // Store meta for tracking
        update_post_meta($post_id, '_article_submitted_via', 'gravity_forms');
        update_post_meta($post_id, '_article_entry_id', $entry['id']);
        update_post_meta($post_id, '_article_submitted_date', current_time('mysql'));
        
        // Log success
        error_log('BIM Verdi Article Handler: Artikkel opprettet - ID: ' . $post_id . ', Status: ' . $artikkel_status);
        
        // Send notification to admin if not draft
        if (!$is_draft) {
            $this->notify_admin_new_article($post_id, $user_id, $title);
        }
    }
    
    /**
     * Send admin notification for new article
     */
    private function notify_admin_new_article($post_id, $user_id, $title) {
        $admin_email = get_option('admin_email');
        $user = get_userdata($user_id);
        $author_name = $user ? $user->display_name : 'Ukjent';
        $edit_link = admin_url('post.php?post=' . $post_id . '&action=edit');
        
        $subject = 'Ny artikkel til godkjenning: ' . $title;
        $message = "En ny artikkel er sendt til godkjenning.\n\n";
        $message .= "Tittel: {$title}\n";
        $message .= "Forfatter: {$author_name}\n";
        $message .= "Rediger: {$edit_link}\n";
        
        wp_mail($admin_email, $subject, $message);
    }
}

// Initialize
new BIM_Verdi_Article_Form_Handler();
