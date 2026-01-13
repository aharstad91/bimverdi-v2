<?php
/**
 * User Profile Edit Template
 * 
 * Frontend page template for users to edit their profile information.
 * Uses ACF's acf_form() to display and handle profile updates.
 * 
 * This template displays:
 * - User profile edit form (using acf_form)
 * - Current profile information
 * - Save/submit button
 * - Success/error messages
 * 
 * Usage:
 * Create a page and set this as the page template, or include this template
 * in your custom page template with: include(plugin_dir_path(__FILE__) . 'templates/user-profile-edit.php');
 * 
 * @package BIM_Verdi_Core
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$current_user_id = get_current_user_id();
$profile = bim_get_user_profile($current_user_id);
?>

<div class="bim-profile-edit-container">
    
    <div class="profile-header">
        <h1>Min Profil</h1>
        <p class="subtitle">Rediger din personlige informasjon</p>
    </div>
    
    <div class="profile-content">
        
        <!-- Current Profile Summary -->
        <div class="profile-summary">
            <div class="profile-avatar">
                <?php echo get_avatar($profile['email'], 100); ?>
            </div>
            
            <div class="profile-details">
                <h2><?php echo esc_html(bim_get_user_display_name($current_user_id)); ?></h2>
                <p class="profile-email"><?php echo esc_html($profile['email']); ?></p>
                
                <?php if ($profile['job_title']): ?>
                    <p class="profile-title">
                        <strong>Stilling:</strong> <?php echo esc_html($profile['job_title']); ?>
                    </p>
                <?php endif; ?>
                
                <?php if ($profile['phone']): ?>
                    <p class="profile-phone">
                        <strong>Telefon:</strong> 
                        <a href="tel:<?php echo esc_attr($profile['phone']); ?>">
                            <?php echo esc_html($profile['phone']); ?>
                        </a>
                    </p>
                <?php endif; ?>
                
                <?php if ($profile['linkedin_url']): ?>
                    <p class="profile-linkedin">
                        <strong>LinkedIn:</strong>
                        <a href="<?php echo esc_url($profile['linkedin_url']); ?>" target="_blank" rel="noopener noreferrer">
                            Besøk profil →
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ACF Edit Form -->
        <div class="profile-edit-form">
            <h3>Rediger informasjon</h3>
            
            <?php
            // Check if ACF Pro is available
            if (!function_exists('acf_form')) {
                echo '<div class="alert alert-warning">ACF Pro er ikke aktivert. Vennligst kontakt administrator.</div>';
            } else {
                // Display ACF form for user profile
                acf_form(array(
                    'id' => 'acf-user-profile-form',
                    'post_id' => 'user_' . $current_user_id,
                    'field_groups' => array('group_bim_verdi_user_profile'),
                    'form' => true,
                    'return' => add_query_arg('profile_updated', '1', get_permalink()),
                    'html_before_fields' => '<fieldset class="acf-fields">',
                    'html_after_fields' => '</fieldset>',
                    'html_before_submit' => '<div class="acf-submit">',
                    'html_after_submit' => '</div>',
                    'submit_button' => 'Lagre endringer',
                    'updated_message' => 'Profilen din har blitt oppdatert! ✓',
                ));
            }
            ?>
        </div>
        
        <!-- Success Message -->
        <?php if (isset($_GET['profile_updated']) && $_GET['profile_updated'] == '1'): ?>
            <div class="alert alert-success">
                <p>✓ Profilen din har blitt lagret.</p>
            </div>
        <?php endif; ?>
        
        <!-- Back Link -->
        <div class="profile-actions">
            <a href="<?php echo esc_url(get_author_posts_url($current_user_id)); ?>" class="btn btn-secondary">
                ← Tilbake til profil
            </a>
        </div>
        
    </div>
    
</div>

<style>
.bim-profile-edit-container {
    max-width: 600px;
    margin: 40px auto;
    padding: 20px;
}

.profile-header {
    margin-bottom: 40px;
    border-bottom: 2px solid #e8e8e8;
    padding-bottom: 20px;
}

.profile-header h1 {
    margin: 0 0 5px 0;
    font-size: 32px;
}

.profile-header .subtitle {
    margin: 0;
    color: #666;
    font-size: 16px;
}

.profile-summary {
    display: flex;
    gap: 30px;
    margin-bottom: 40px;
    padding: 20px;
    background: #f8f8f8;
    border-radius: 8px;
}

.profile-avatar {
    flex-shrink: 0;
}

.profile-avatar img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
}

.profile-details h2 {
    margin: 0 0 5px 0;
    font-size: 22px;
}

.profile-email {
    margin: 5px 0;
    color: #666;
}

.profile-title,
.profile-phone,
.profile-linkedin {
    margin: 10px 0;
}

.profile-edit-form {
    background: white;
    padding: 30px;
    border: 1px solid #e8e8e8;
    border-radius: 8px;
    margin-bottom: 30px;
}

.profile-edit-form h3 {
    margin-top: 0;
    font-size: 20px;
}

.acf-fields {
    margin-bottom: 20px;
}

.acf-field {
    margin-bottom: 20px;
}

.acf-submit {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e8e8e8;
}

.acf-submit button {
    background: linear-gradient(135deg, #FF8B5E 0%, #E67A4E 100%);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 25px;
    font-size: 16px;
    cursor: pointer;
    transition: transform 0.2s;
}

.acf-submit button:hover {
    transform: translateY(-2px);
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
    border: 1px solid;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border-color: #ffeaa7;
}

.profile-actions {
    text-align: center;
    margin-top: 30px;
}

.btn {
    display: inline-block;
    padding: 12px 24px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-secondary {
    background: #e8e8e8;
    color: #333;
}

.btn-secondary:hover {
    background: #d8d8d8;
    transform: translateY(-2px);
}

@media (max-width: 600px) {
    .profile-summary {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .profile-edit-form {
        padding: 20px;
    }
    
    .profile-header h1 {
        font-size: 24px;
    }
}
</style>
