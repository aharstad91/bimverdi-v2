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
               BIM VERDI "OATMEAL" DESIGN SYSTEM
               Warm, structured, enterprise-calm
               ============================================ */
            :root {
                /* ---- Brand ---- */
                --color-primary: #FF8B5E;        /* Warm orange - main brand */
                --color-primary-dark: #E67A4E;
                --color-primary-light: #FFBFA8;

                /* ---- Text ---- */
                --color-text: #1A1A1A;           /* Primary text */
                --color-text-secondary: #5A5A5A; /* Secondary/meta text */
                --color-text-muted: #888888;     /* Muted/disabled text */

                /* ---- Surfaces ---- */
                --color-bg: #F7F5EF;             /* Warm background (beige) */
                --color-bg-surface: #EFE9DE;     /* Elevated surface */
                --color-bg-white: #FFFFFF;       /* Card/panel background */
                --color-bg-dark: #1A1A1A;        /* Dark sections (hero, CTA, badges) */

                /* ---- Dividers ---- */
                --color-divider: #D6D1C6;        /* Primary divider */
                --color-divider-light: #E5E0D5;  /* Subtle divider (list items) */

                /* ---- Temagruppe palette ---- */
                --color-tg-orange: #FF8B5E;      /* Modellkvalitet */
                --color-tg-blue: #005898;        /* ByggesaksBIM */
                --color-tg-green: #6B9B37;       /* ProsjektBIM */
                --color-tg-purple: #5E36FE;      /* EiendomsBIM */
                --color-tg-teal: #0D9488;        /* MiljøBIM */
                --color-tg-amber: #D97706;       /* BIMtech */

                /* ---- State ---- */
                --color-success: #B3DB87;
                --color-error: #772015;
                --color-alert: #FFC845;
                --color-info: #005898;

                /* ---- Legacy aliases ---- */
                --color-secondary: #5E36FE;
                --color-secondary-dark: #4E26DE;
                --color-secondary-light: #DFD7C6;
                --color-black: #0F0F0F;
                --color-gray-dark: #383838;
                --color-gray-medium: #888888;
                --color-gray-light: #D1D1D1;
                --color-white: #FFFFFF;
                --color-beige: #F7F5EF;
                --color-beige-dark: #EFE9DE;
                
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
               MISSING TAILWIND UTILITIES
               (Manually added until next CSS rebuild)
               ============================================ */

            /* Font size minimum 15px */
            .text-15 { font-size: 0.9375rem; line-height: 1.4; }

            .grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }

            /* Spacing utilities missing from pre-compiled CSS */
            .py-14 { padding-top: 3.5rem; padding-bottom: 3.5rem; }
            .py-16 { padding-top: 4rem; padding-bottom: 4rem; }
            .pt-14 { padding-top: 3.5rem; }
            .pt-16 { padding-top: 4rem; }
            .pb-12 { padding-bottom: 3rem; }
            .mb-10 { margin-bottom: 2.5rem; }

            @media (min-width: 768px) {
                .md\:block {
                    display: block;
                }
            }

            @media (min-width: 1024px) {
                .lg\:grid-cols-2 {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
                .lg\:grid-cols-6 {
                    grid-template-columns: repeat(6, minmax(0, 1fr));
                }
                .lg\:gap-12 {
                    gap: 3rem;
                }
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
            
            /* ============================================
               BIMVerdi Button Component (Figma Design)
               Height: 36px, Border Radius: 8px
               Font: Inter Medium, 14px/20px, tracking -0.15px
               ============================================ */
            
            .bv-btn {
                /* Base styles matching Figma spec */
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                height: 36px;
                padding: 0 16px;
                border-radius: 8px;
                font-family: 'Inter', var(--font-family);
                font-weight: 500;
                font-size: 14px;
                line-height: 20px;
                letter-spacing: -0.15px;
                text-decoration: none;
                cursor: pointer;
                transition: all 0.15s ease;
                white-space: nowrap;
                border: 1px solid transparent;
            }
            
            .bv-btn:focus-visible {
                outline: 2px solid var(--color-primary, #FF8B5E);
                outline-offset: 2px;
            }
            
            /* Primary variant: Filled black (#1a1a1a) */
            .bv-btn--primary {
                background-color: #1A1A1A;
                color: #FFFFFF;
                border-color: #1A1A1A;
            }
            
            .bv-btn--primary:hover {
                background-color: #333333;
                border-color: #333333;
            }
            
            .bv-btn--primary:active {
                background-color: #000000;
                border-color: #000000;
            }
            
            /* Secondary variant: Outline with black border */
            .bv-btn--secondary {
                background-color: #FFFFFF;
                color: #1A1A1A;
                border-color: #1A1A1A;
            }
            
            .bv-btn--secondary:hover {
                background-color: #F5F5F5;
            }
            
            .bv-btn--secondary:active {
                background-color: #EBEBEB;
            }
            
            /* Tertiary/Ghost variant: No border, subtle hover */
            .bv-btn--tertiary {
                background-color: transparent;
                color: #1A1A1A;
                border-color: transparent;
            }
            
            .bv-btn--tertiary:hover {
                background-color: #F5F5F5;
            }
            
            /* Danger variant */
            .bv-btn--danger {
                background-color: #DC2626;
                color: #FFFFFF;
                border-color: #DC2626;
            }
            
            .bv-btn--danger:hover {
                background-color: #B91C1C;
                border-color: #B91C1C;
            }
            
            /* Size variants */
            .bv-btn--small {
                height: 28px;
                padding: 0 12px;
                font-size: 13px;
                gap: 6px;
            }
            
            .bv-btn--medium {
                height: 36px;
                padding: 0 16px;
            }
            
            .bv-btn--large {
                height: 44px;
                padding: 0 24px;
                font-size: 15px;
                gap: 10px;
            }
            
            /* Full width */
            .bv-btn--full-width {
                width: 100%;
            }
            
            /* Disabled state */
            .bv-btn--disabled,
            .bv-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
                pointer-events: none;
            }
            
            /* Icon styling */
            .bv-btn__icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            
            .bv-btn__icon svg {
                width: 16px;
                height: 16px;
            }
            
            .bv-btn--small .bv-btn__icon svg {
                width: 14px;
                height: 14px;
            }
            
            .bv-btn--large .bv-btn__icon svg {
                width: 18px;
                height: 18px;
            }
            
            .bv-btn__text {
                display: inline-block;
            }
            
            /* Lucide icon base styling */
            .bv-icon {
                display: inline-block;
                vertical-align: middle;
                flex-shrink: 0;
            }
            
            /* ============================================
               Legacy button styles (for backwards compat)
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
               OATMEAL UTILITY CLASSES
               Semantic tokens for consistent theming
               ============================================ */

            /* Text colors */
            .oat-text          { color: var(--color-text); }
            .oat-text-secondary { color: var(--color-text-secondary); }
            .oat-text-muted    { color: var(--color-text-muted); }
            .oat-text-primary  { color: var(--color-primary); }

            /* Background colors */
            .oat-bg            { background-color: var(--color-bg); }
            .oat-bg-surface    { background-color: var(--color-bg-surface); }
            .oat-bg-white      { background-color: var(--color-bg-white); }
            .oat-bg-dark       { background-color: var(--color-bg-dark); }

            /* Borders / dividers */
            .oat-border        { border-color: var(--color-divider); }
            .oat-border-light  { border-color: var(--color-divider-light); }

            /* Hover states */
            .oat-hover:hover   { background-color: var(--color-bg-surface); }

            /* Legacy aliases */
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

            /* Footer Newsletter GF Styling */
            .bv-footer-newsletter .gform_wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }
            .bv-footer-newsletter .gform_wrapper .gform_body {
                margin: 0;
            }
            .bv-footer-newsletter .gform_wrapper .gfield {
                margin: 0 !important;
                padding: 0 !important;
            }
            .bv-footer-newsletter .gform_wrapper input[type="email"] {
                background: transparent !important;
                border: none !important;
                border-bottom: 1px solid #1A1A1A !important;
                border-radius: 0 !important;
                padding: 0.5rem 0 !important;
                font-size: 0.875rem !important;
                color: #1A1A1A !important;
                box-shadow: none !important;
                outline: none !important;
                width: 100% !important;
            }
            .bv-footer-newsletter .gform_wrapper input[type="email"]::placeholder {
                color: #888 !important;
            }
            .bv-footer-newsletter .gform_wrapper input[type="email"]:focus {
                border-bottom-color: #FF8B5E !important;
            }
            .bv-footer-newsletter .gform_wrapper .gform_footer,
            .bv-footer-newsletter .gform_wrapper .gform_page_footer {
                margin: 0.75rem 0 0 !important;
                padding: 0 !important;
            }
            .bv-footer-newsletter .gform_wrapper input[type="submit"] {
                background: #1A1A1A !important;
                color: #fff !important;
                border: none !important;
                border-radius: 6px !important;
                padding: 0.5rem 1.25rem !important;
                font-size: 0.8125rem !important;
                font-weight: 600 !important;
                cursor: pointer !important;
                transition: opacity 0.2s !important;
            }
            .bv-footer-newsletter .gform_wrapper input[type="submit"]:hover {
                opacity: 0.8 !important;
            }
            .bv-footer-newsletter .gform_wrapper .gform_confirmation_message {
                color: #2e7d32 !important;
                font-size: 0.875rem !important;
                padding: 0 !important;
                margin: 0 !important;
                background: none !important;
                border: none !important;
            }
            .bv-footer-newsletter .gform_wrapper .gfield_label,
            .bv-footer-newsletter .gform_wrapper .gfield_required {
                display: none !important;
            }
            .bv-footer-newsletter .gform_wrapper .validation_error,
            .bv-footer-newsletter .gform_wrapper .gfield_error .gfield_label {
                display: none !important;
            }
            .bv-footer-newsletter .gform_wrapper .gfield_error input[type="email"] {
                border-bottom-color: #772015 !important;
            }

            /* ---- GSAP Page Transition ---- */
            main, footer { opacity: 0; }
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
