<?php
/**
 * Min Side - Layout Wrapper
 * 
 * Provides consistent layout wrapper for all Min Side pages
 * with sidebar navigation and main content area
 * 
 * Usage in templates:
 * get_template_part('template-parts/minside-layout', null, array(
 *     'current_page' => 'dashboard',
 *     'page_title' => 'Dashboard',
 *     'page_icon' => 'house',
 * ));
 * 
 * Then in the template, output content between the opening and closing.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get args
$current_page = isset($args['current_page']) ? $args['current_page'] : 'dashboard';
$page_title = isset($args['page_title']) ? $args['page_title'] : '';
$page_icon = isset($args['page_icon']) ? $args['page_icon'] : '';
$page_description = isset($args['page_description']) ? $args['page_description'] : '';
$show_header = isset($args['show_header']) ? $args['show_header'] : true;
?>

<div class="minside-wrapper">
    <div class="minside-container">
        
        <!-- Sidebar Navigation -->
        <?php get_template_part('template-parts/minside-sidebar', null, array('current_page' => $current_page)); ?>
        
        <!-- Main Content Area -->
        <main class="minside-content">
            
            <?php if ($show_header && $page_title): ?>
            <!-- Page Header -->
            <div class="minside-content__header">
                <div class="minside-content__title-row">
                    <?php if ($page_icon): ?>
                        <wa-icon name="<?php echo esc_attr($page_icon); ?>" library="fa" class="minside-content__icon"></wa-icon>
                    <?php endif; ?>
                    <h1 class="minside-content__title"><?php echo esc_html($page_title); ?></h1>
                </div>
                <?php if ($page_description): ?>
                    <p class="minside-content__description"><?php echo esc_html($page_description); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Content slot - templates will add content after including this -->
            <div class="minside-content__body">
