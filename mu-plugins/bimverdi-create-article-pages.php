<?php
/**
 * Setup script for article pages
 * Visit: /wp-content/mu-plugins/bimverdi-create-article-pages.php (will auto-run)
 * Then delete this file after setup
 */

// Only run once
if (get_option('bimverdi_article_pages_created')) {
    return;
}

add_action('init', function() {
    // Check if already done
    if (get_option('bimverdi_article_pages_created')) {
        return;
    }
    
    // Find Min Side parent page
    $min_side = get_page_by_path('min-side');
    $parent_id = $min_side ? $min_side->ID : 0;
    
    // Create pages
    $pages_to_create = array(
        array(
            'title' => 'Artikler',
            'slug' => 'artikler',
            'template' => 'template-minside-artikler.php',
            'parent' => $parent_id,
        ),
        array(
            'title' => 'Skriv artikkel',
            'slug' => 'skriv-artikkel',
            'template' => 'template-minside-skriv-artikkel.php',
            'parent' => $parent_id,
        ),
    );
    
    foreach ($pages_to_create as $page_data) {
        // Check if page already exists
        $existing = get_page_by_path($page_data['slug'], OBJECT, 'page');
        if ($existing) {
            continue;
        }
        
        // Check with parent path
        if ($parent_id) {
            $existing = get_page_by_path('min-side/' . $page_data['slug'], OBJECT, 'page');
            if ($existing) {
                continue;
            }
        }
        
        $post_id = wp_insert_post(array(
            'post_title' => $page_data['title'],
            'post_name' => $page_data['slug'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_parent' => $page_data['parent'],
        ));
        
        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, '_wp_page_template', $page_data['template']);
            error_log('BIM Verdi: Created page "' . $page_data['title'] . '" (ID: ' . $post_id . ')');
        }
    }
    
    // Mark as done
    update_option('bimverdi_article_pages_created', true);
    
    // Flush rewrite rules for new CPT
    flush_rewrite_rules();
    
}, 999);
