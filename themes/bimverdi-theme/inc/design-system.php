<?php
/**
 * BIM Verdi Design System
 * 
 * Registers design system elements, custom post types for colors,
 * and utility functions for consistent styling across the site.
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIM_Verdi_Design_System {
    
    public function __construct() {
        add_action('wp_head', [$this, 'enqueue_design_css']);
        add_action('wp_footer', [$this, 'enqueue_design_js']);
    }
    
    /**
     * Enqueue design system CSS variables and custom styles
     */
    public function enqueue_design_css() {
        ?>
        <style>
            /* ============================================
               BIM VERDI COLOR PALETTE (hjem.no inspired)
               ============================================ */
            :root {
                /* Primary Colors */
                --color-primary: #FF8B5E;        /* Warm orange - main brand color */
                --color-primary-dark: #E67A4E;
                --color-primary-light: #FFBFA8;
                
                /* Secondary Colors */
                --color-secondary: #5E36FE;      /* Purple - innovation */
                --color-secondary-dark: #4E26DE;
                --color-secondary-light: #DFD7C6;
                
                /* Neutrals */
                --color-black: #0F0F0F;          /* Main text */
                --color-gray-dark: #383838;
                --color-gray-medium: #888888;
                --color-gray-light: #D1D1D1;
                --color-white: #FFFFFF;
                
                /* Background */
                --color-beige: #F7F5EF;          /* Warm background */
                --color-beige-dark: #EFE9DE;
                
                /* State Colors */
                --color-success: #B3DB87;        /* Green */
                --color-error: #772015;          /* Red/brown */
                --color-alert: #FFC845;          /* Yellow */
                --color-info: #005898;           /* Blue */
                
                /* Spacing */
                --spacing-xs: 0.5rem;
                --spacing-sm: 1rem;
                --spacing-md: 1.5rem;
                --spacing-lg: 2rem;
                --spacing-xl: 3rem;
                --spacing-2xl: 4rem;
                
                /* Border Radius */
                --radius-sm: 0.375rem;
                --radius-md: 0.5rem;
                --radius-lg: 0.75rem;
                --radius-xl: 1rem;
                --radius-button: 25px;
                --radius-pill: 100px;
                
                /* Shadow */
                --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                --shadow-card: 0 2px 8px rgba(0, 0, 0, 0.08);
                
                /* Typography */
                --font-family: 'Moderat', 'Inter', system-ui, -apple-system, sans-serif;
                --line-height-tight: 1.2;
                --line-height-normal: 1.5;
                --line-height-relaxed: 1.75;
                
                /* Z-index scale */
                --z-dropdown: 1000;
                --z-modal: 1050;
                --z-popover: 1060;
                --z-tooltip: 1070;
            }
            
            /* ============================================
               BASE ELEMENTS
               ============================================ */
            
            * {
                box-sizing: border-box;
            }
            
            html {
                font-family: var(--font-family);
                color: var(--color-black);
                background-color: var(--color-white);
            }
            
            body {
                margin: 0;
                padding: 0;
                line-height: var(--line-height-normal);
                color: var(--color-black);
                background-color: #FAFAF8;
            }
            
            h1, h2, h3, h4, h5, h6 {
                font-family: var(--font-family);
                color: var(--color-black);
                line-height: var(--line-height-tight);
                margin: var(--spacing-lg) 0 var(--spacing-md) 0;
            }
            
            h1 {
                font-size: 3rem;
                font-weight: 700;
            }
            
            h2 {
                font-size: 2rem;
                font-weight: 700;
            }
            
            h3 {
                font-size: 1.5rem;
                font-weight: 600;
            }
            
            h4 {
                font-size: 1.25rem;
                font-weight: 600;
            }
            
            h5 {
                font-size: 1.125rem;
                font-weight: 600;
            }
            
            h6 {
                font-size: 1rem;
                font-weight: 600;
            }
            
            p {
                margin: 0 0 var(--spacing-md) 0;
                font-size: 1rem;
                line-height: var(--line-height-relaxed);
            }
            
            a {
                color: var(--color-primary);
                text-decoration: none;
                transition: all 0.2s ease;
            }
            
            a:hover {
                color: var(--color-primary-dark);
                text-decoration: underline;
            }
            
            /* ============================================
               BUTTONS (daisyUI overrides & custom)
               ============================================ */
            
            .btn-hjem {
                border-radius: var(--radius-button);
                font-weight: 600;
                font-size: 1rem;
                padding: 0.75rem 2rem;
                border: none;
                cursor: pointer;
                transition: all 0.25s ease;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                text-decoration: none;
            }
            
            .btn-hjem-primary {
                background-color: var(--color-primary);
                color: white;
            }
            
            .btn-hjem-primary:hover {
                background-color: var(--color-primary-dark);
                transform: translateY(-2px);
                box-shadow: var(--shadow-lg);
            }
            
            .btn-hjem-secondary {
                background-color: var(--color-secondary);
                color: white;
            }
            
            .btn-hjem-secondary:hover {
                background-color: var(--color-secondary-dark);
                transform: translateY(-2px);
                box-shadow: var(--shadow-lg);
            }
            
            .btn-hjem-outline {
                background-color: transparent;
                color: var(--color-primary);
                border: 2px solid var(--color-primary);
            }
            
            .btn-hjem-outline:hover {
                background-color: var(--color-primary);
                color: white;
            }
            
            .btn-hjem-ghost {
                background-color: transparent;
                color: var(--color-primary);
                border: none;
            }
            
            .btn-hjem-ghost:hover {
                background-color: var(--color-beige);
            }
            
            .btn-sm {
                padding: 0.5rem 1.25rem;
                font-size: 0.875rem;
            }
            
            .btn-lg {
                padding: 1rem 2.5rem;
                font-size: 1.125rem;
            }
            
            .btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            
            /* ============================================
               FORM ELEMENTS
               ============================================ */
            
            .input-hjem,
            input[type="text"],
            input[type="email"],
            input[type="password"],
            input[type="number"],
            input[type="tel"],
            input[type="date"],
            textarea,
            select {
                width: 100%;
                padding: 0.75rem 1rem;
                border: 1px solid var(--color-gray-light);
                border-radius: var(--radius-md);
                font-family: var(--font-family);
                font-size: 1rem;
                transition: all 0.2s ease;
                background-color: white;
            }
            
            input:focus,
            textarea:focus,
            select:focus {
                outline: none;
                border-color: var(--color-primary);
                box-shadow: 0 0 0 3px rgba(255, 139, 94, 0.1);
            }
            
            textarea {
                resize: vertical;
                min-height: 6rem;
            }
            
            label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 600;
                font-size: 0.95rem;
                color: var(--color-black);
            }
            
            .form-group {
                margin-bottom: var(--spacing-md);
            }
            
            .form-group label {
                margin-bottom: var(--spacing-xs);
            }
            
            .checkbox-hjem,
            input[type="checkbox"],
            input[type="radio"] {
                width: 1.25rem;
                height: 1.25rem;
                cursor: pointer;
                accent-color: var(--color-primary);
            }
            
            .checkbox-group,
            .radio-group {
                display: flex;
                flex-direction: column;
                gap: var(--spacing-sm);
            }
            
            .checkbox-item,
            .radio-item {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                cursor: pointer;
            }
            
            /* ============================================
               CARDS
               ============================================ */
            
            .card-hjem {
                background-color: white;
                border-radius: var(--radius-lg);
                padding: var(--spacing-md);
                box-shadow: var(--shadow-card);
                transition: all 0.2s ease;
                border: 1px solid var(--color-gray-light);
            }
            
            .card-hjem:hover {
                transform: translateY(-4px);
                box-shadow: var(--shadow-lg);
            }
            
            .card-hjem-header {
                margin-bottom: var(--spacing-md);
                padding-bottom: var(--spacing-md);
                border-bottom: 1px solid var(--color-beige-dark);
            }
            
            .card-hjem-title {
                margin: 0 0 0.5rem 0;
                font-size: 1.25rem;
                font-weight: 600;
                color: var(--color-black);
            }
            
            .card-hjem-subtitle {
                margin: 0;
                font-size: 0.9rem;
                color: var(--color-gray-medium);
            }
            
            .card-hjem-body {
                margin-bottom: var(--spacing-md);
            }
            
            .card-hjem-footer {
                padding-top: var(--spacing-md);
                border-top: 1px solid var(--color-beige-dark);
                display: flex;
                gap: var(--spacing-sm);
                justify-content: flex-start;
            }
            
            /* ============================================
               BADGES & TAGS
               ============================================ */
            
            .badge-hjem {
                display: inline-flex;
                align-items: center;
                padding: 0.375rem 0.875rem;
                border-radius: var(--radius-pill);
                font-size: 0.875rem;
                font-weight: 600;
                gap: 0.5rem;
            }
            
            .badge-primary {
                background-color: var(--color-primary);
                color: white;
            }
            
            .badge-secondary {
                background-color: var(--color-secondary);
                color: white;
            }
            
            .badge-outline {
                background-color: transparent;
                border: 1px solid var(--color-primary);
                color: var(--color-primary);
            }
            
            .badge-success {
                background-color: var(--color-success);
                color: var(--color-black);
            }
            
            .badge-error {
                background-color: var(--color-error);
                color: white;
            }
            
            /* ============================================
               UTILITY CLASSES
               ============================================ */
            
            .text-primary { color: var(--color-primary); }
            .text-secondary { color: var(--color-secondary); }
            .text-success { color: var(--color-success); }
            .text-error { color: var(--color-error); }
            .text-alert { color: var(--color-alert); }
            .text-info { color: var(--color-info); }
            
            .bg-beige { background-color: var(--color-beige); }
            .bg-beige-dark { background-color: var(--color-beige-dark); }
            
            .mt-sm { margin-top: var(--spacing-sm); }
            .mt-md { margin-top: var(--spacing-md); }
            .mt-lg { margin-top: var(--spacing-lg); }
            .mb-sm { margin-bottom: var(--spacing-sm); }
            .mb-md { margin-bottom: var(--spacing-md); }
            .mb-lg { margin-bottom: var(--spacing-lg); }
            
            .p-sm { padding: var(--spacing-sm); }
            .p-md { padding: var(--spacing-md); }
            .p-lg { padding: var(--spacing-lg); }
            
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .text-left { text-align: left; }
            
            .flex { display: flex; }
            .flex-column { flex-direction: column; }
            .gap-sm { gap: var(--spacing-sm); }
            .gap-md { gap: var(--spacing-md); }
            .gap-lg { gap: var(--spacing-lg); }
            
            .rounded-sm { border-radius: var(--radius-sm); }
            .rounded-md { border-radius: var(--radius-md); }
            .rounded-lg { border-radius: var(--radius-lg); }
            .rounded-full { border-radius: var(--radius-pill); }
            
            .shadow-sm { box-shadow: var(--shadow-sm); }
            .shadow-md { box-shadow: var(--shadow-md); }
            .shadow-lg { box-shadow: var(--shadow-lg); }
            
            /* ============================================
               RESPONSIVE
               ============================================ */
            
            @media (max-width: 768px) {
                h1 {
                    font-size: 2rem;
                }
                
                h2 {
                    font-size: 1.5rem;
                }
                
                h3 {
                    font-size: 1.25rem;
                }
                
                .btn-hjem {
                    font-size: 0.95rem;
                    padding: 0.65rem 1.5rem;
                }
            }
        </style>
        <?php
    }
    
    /**
     * Enqueue design system JavaScript
     */
    public function enqueue_design_js() {
        // Placeholder for future JS functionality
    }
}

// Initialize design system
new BIM_Verdi_Design_System();
