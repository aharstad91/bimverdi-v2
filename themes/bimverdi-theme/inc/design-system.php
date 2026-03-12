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
               BIM VERDI MARKETPLACE DESIGN SYSTEM
               Clean, modern, directory-style
               ============================================ */
            :root {
                /* ---- Brand ---- */
                --color-primary: #FF8B5E;        /* Warm orange - main brand */
                --color-primary-dark: #E67A4E;
                --color-primary-light: #FFBFA8;

                /* ---- Text ---- */
                --color-text: #111827;           /* Primary text (gray-900) */
                --color-text-secondary: #57534E; /* Secondary/meta text (stone-600) */
                --color-text-muted: #A8A29E;     /* Muted/disabled text (stone-400) */

                /* ---- Surfaces ---- */
                --color-bg: #FFFFFF;             /* White background */
                --color-bg-alt: #FAFAF9;         /* Alternating section bg (stone-50) */
                --color-bg-surface: #F5F5F4;     /* Elevated surface (stone-100) */
                --color-bg-white: #FFFFFF;       /* Card/panel background */
                --color-bg-dark: #111827;        /* Dark sections (hero, CTA, badges) */

                /* ---- Dividers ---- */
                --color-divider: #E7E5E4;        /* Primary divider (stone-200) */
                --color-divider-light: rgba(231, 229, 228, 0.6); /* Subtle divider */

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
                --color-secondary-light: #E7E5E4;
                --color-black: #111827;
                --color-gray-dark: #44403C;
                --color-gray-medium: #A8A29E;
                --color-gray-light: #E7E5E4;
                --color-white: #FFFFFF;
                --color-beige: #F5F5F4;
                --color-beige-dark: #E7E5E4;

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
                --font-family: 'Inter', system-ui, -apple-system, sans-serif;
                --font-family-serif: 'Crimson Text', Georgia, serif;
                --line-height-tight: 1.2;
                --line-height-normal: 1.5;
                --line-height-relaxed: 1.75;
                
                /* Z-index scale */
                --z-dropdown: 1000;
                --z-modal: 1050;
                --z-popover: 1060;
                --z-tooltip: 1070;

                /* === Semantic Design Tokens (v2) === */

                /* Semantic colors */
                --color-text-primary: #1A1A1A;
                --color-text-secondary: #5A5A5A;
                --color-text-muted: #888888;
                --color-text-inverse: #FFFFFF;
                --color-bg-page: #F7F5EF;
                --color-bg-surface: #FFFFFF;
                --color-bg-surface-alt: #F5F5F4;
                --color-bg-muted: #EFE9DE;
                --color-border: #E7E5E4;
                --color-border-strong: #D6D1C6;
                --color-accent: #FF8B5E;
                --color-accent-hover: #FF7A47;
                --color-accent-light: #FFF3ED;
                --color-success: #16A34A;
                --color-warning: #D97706;
                --color-error: #DC2626;
                --color-info: #2563EB;

                /* Temagruppe colors */
                --color-temagruppe-bimtech: #3B82F6;
                --color-temagruppe-gronn-bim: #22C55E;
                --color-temagruppe-digital-samhandling: #A855F7;
                --color-temagruppe-forvaltning: #14B8A6;
                --color-temagruppe-baerekraft: #F59E0B;
                --color-temagruppe-standard: #FF8B5E;

                /* Typography */
                --font-family-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                --heading-weight-light: 300;
                --heading-weight-regular: 400;
                --heading-weight-medium: 500;
                --heading-weight-semibold: 600;
                --heading-tracking-tight: -0.03em;
                --heading-tracking: -0.02em;
                --heading-tracking-normal: -0.01em;

                /* Border radius */
                --radius-sm: 4px;
                --radius-md: 8px;
                --radius-lg: 12px;
                --radius-xl: 16px;
                --radius-full: 9999px;

                /* Shadows */
                --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
                --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
                --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.04);

                /* Spacing */
                --space-1: 4px;
                --space-2: 8px;
                --space-3: 12px;
                --space-4: 16px;
                --space-6: 24px;
                --space-8: 32px;
                --space-12: 48px;
                --space-16: 64px;
                --space-20: 80px;
                --space-24: 96px;
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
                background-color: #FFFFFF;
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
               BIMVerdi Button Component (shadcn-inspired)
               Variants: default, secondary, outline, ghost, destructive, link
               Sizes: sm (28px), default (32px), lg (36px), icon (32x32)
               ============================================ */

            .bv-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                height: 32px;
                padding: 0 12px;
                border-radius: 6px;
                font-family: 'Inter', var(--font-family);
                font-weight: 500;
                font-size: 14px;
                line-height: 20px;
                letter-spacing: -0.01em;
                text-decoration: none;
                cursor: pointer;
                transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease, opacity 0.15s ease;
                white-space: nowrap;
                border: 1px solid transparent;
            }

            .bv-btn:focus-visible {
                outline: none;
                box-shadow: 0 0 0 2px #FFFFFF, 0 0 0 4px #1A1A1A;
            }

            /* Default variant (was: primary) — solid dark fill */
            .bv-btn--default,
            .bv-btn--primary {
                background-color: #18181B;
                color: #FAFAFA;
                border-color: #18181B;
            }

            .bv-btn--default:hover,
            .bv-btn--primary:hover {
                background-color: #18181BE6; /* 90% opacity */
            }

            .bv-btn--default:active,
            .bv-btn--primary:active {
                background-color: #09090B;
            }

            /* Secondary variant — muted fill */
            .bv-btn--secondary {
                background-color: #F4F4F5;
                color: #18181B;
                border-color: #F4F4F5;
            }

            .bv-btn--secondary:hover {
                background-color: #E4E4E7;
                border-color: #E4E4E7;
            }

            .bv-btn--secondary:active {
                background-color: #D4D4D8;
                border-color: #D4D4D8;
            }

            /* Outline variant — border, transparent bg */
            .bv-btn--outline {
                background-color: transparent;
                color: #18181B;
                border-color: #E4E4E7;
            }

            .bv-btn--outline:hover {
                background-color: #F4F4F5;
                color: #18181B;
            }

            .bv-btn--outline:active {
                background-color: #E4E4E7;
            }

            /* Ghost variant (was: tertiary) — no border, subtle hover */
            .bv-btn--ghost,
            .bv-btn--tertiary {
                background-color: transparent;
                color: #18181B;
                border-color: transparent;
            }

            .bv-btn--ghost:hover,
            .bv-btn--tertiary:hover {
                background-color: #F4F4F5;
                color: #18181B;
            }

            /* Destructive variant (was: danger) — red fill */
            .bv-btn--destructive,
            .bv-btn--danger {
                background-color: #DC2626;
                color: #FAFAFA;
                border-color: #DC2626;
            }

            .bv-btn--destructive:hover,
            .bv-btn--danger:hover {
                background-color: #DC2626E6; /* 90% opacity */
            }

            .bv-btn--destructive:active,
            .bv-btn--danger:active {
                background-color: #B91C1C;
                border-color: #B91C1C;
            }

            /* Link variant — text only, underline on hover */
            .bv-btn--link {
                background-color: transparent;
                color: #18181B;
                border-color: transparent;
                height: auto;
                padding: 0;
                text-underline-offset: 4px;
            }

            .bv-btn--link:hover {
                text-decoration: underline;
            }

            /* Size variants */
            .bv-btn--sm,
            .bv-btn--small {
                height: 28px;
                padding: 0 10px;
                font-size: 13px;
                gap: 6px;
                border-radius: 6px;
            }

            .bv-btn--default-size,
            .bv-btn--medium {
                height: 32px;
                padding: 0 12px;
            }

            .bv-btn--lg,
            .bv-btn--large {
                height: 36px;
                padding: 0 16px;
                gap: 8px;
                border-radius: 6px;
            }

            /* Icon-only size — square button */
            .bv-btn--icon {
                height: 32px;
                width: 32px;
                padding: 0;
            }

            .bv-btn--icon-sm {
                height: 28px;
                width: 28px;
                padding: 0;
            }

            .bv-btn--icon-lg {
                height: 36px;
                width: 36px;
                padding: 0;
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

            /* Icon styling inside buttons */
            .bv-btn__icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .bv-btn__icon svg,
            .bv-btn svg {
                width: 16px;
                height: 16px;
                pointer-events: none;
                flex-shrink: 0;
            }

            .bv-btn--sm .bv-btn__icon svg,
            .bv-btn--small .bv-btn__icon svg,
            .bv-btn--sm svg,
            .bv-btn--small svg {
                width: 14px;
                height: 14px;
            }

            .bv-btn--lg .bv-btn__icon svg,
            .bv-btn--large .bv-btn__icon svg,
            .bv-btn--lg svg,
            .bv-btn--large svg {
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
            
            /* Checkbox — shadcn style */
            .checkbox-hjem,
            input[type="checkbox"] {
                -webkit-appearance: none;
                appearance: none;
                width: 1rem;
                height: 1rem;
                border: 1px solid #D4D4D8;
                border-radius: 3px;
                cursor: pointer;
                position: relative;
                flex-shrink: 0;
                transition: all 0.15s ease;
                background: white;
            }

            input[type="checkbox"]:hover {
                border-color: #A1A1AA;
            }

            input[type="checkbox"]:focus-visible {
                outline: 2px solid var(--color-primary);
                outline-offset: 2px;
            }

            input[type="checkbox"]:checked {
                background: #18181B;
                border-color: #18181B;
            }

            input[type="checkbox"]:checked::after {
                content: '';
                position: absolute;
                left: 50%;
                top: 45%;
                width: 4.5px;
                height: 8px;
                border: solid white;
                border-width: 0 2px 2px 0;
                transform: translate(-50%, -50%) rotate(45deg);
            }

            input[type="checkbox"]:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            /* Radio — shadcn style */
            input[type="radio"] {
                -webkit-appearance: none;
                appearance: none;
                width: 1rem;
                height: 1rem;
                border: 1px solid #D4D4D8;
                border-radius: 50%;
                cursor: pointer;
                position: relative;
                flex-shrink: 0;
                transition: all 0.15s ease;
                background: white;
            }

            input[type="radio"]:hover {
                border-color: #A1A1AA;
            }

            input[type="radio"]:focus-visible {
                outline: 2px solid var(--color-primary);
                outline-offset: 2px;
            }

            input[type="radio"]:checked {
                border-color: #18181B;
            }

            input[type="radio"]:checked::after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 8px;
                height: 8px;
                background: #18181B;
                border-radius: 50%;
                transform: translate(-50%, -50%);
            }

            input[type="radio"]:disabled {
                opacity: 0.5;
                cursor: not-allowed;
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
                align-items: flex-start;
                gap: 0.5rem;
                cursor: pointer;
            }

            .checkbox-item input,
            .radio-item input {
                margin-top: 2px;
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

            /* ============================================
               MARKETPLACE CARD COMPONENT
               ============================================ */

            .bv-card {
                display: block;
                height: 100%;
                background: #FFFFFF;
                border: 1px solid #E7E5E4;
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.04);
                transition: all 0.2s ease;
                padding: 1.5rem;
                text-decoration: none;
                color: inherit;
            }

            .bv-card:hover {
                box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                border-color: #D6D3D1;
                text-decoration: none;
                color: inherit;
            }

            /* ============================================
               CATEGORY CHIP
               ============================================ */

            .bv-chip {
                display: inline-flex;
                align-items: center;
                padding: 0.375rem 0.875rem;
                border-radius: 100px;
                font-size: 0.8125rem;
                font-weight: 500;
                background: #FFFFFF;
                color: #57534E;
                border: 1px solid #E7E5E4;
                transition: all 0.15s ease;
                text-decoration: none;
                cursor: pointer;
                white-space: nowrap;
            }

            .bv-chip:hover {
                background: #F5F5F4;
                border-color: #D6D3D1;
                text-decoration: none;
                color: #111827;
            }

            .bv-chip.active,
            .bv-chip--active {
                background: #111827;
                color: #FFFFFF;
                border-color: #111827;
            }

            .bv-chip.active:hover,
            .bv-chip--active:hover {
                background: #1F2937;
                color: #FFFFFF;
            }

            /* ============================================
               HERO ROTATING TEXT
               ============================================ */

            .bv-hero-rotating {
                position: relative;
                display: inline-grid;
            }

            .bv-hero-rotating > span {
                grid-area: 1 / 1;
                opacity: 0;
                transform: translateY(8px);
                transition: opacity 0.4s ease, transform 0.4s ease;
            }

            #hero-rotating > span {
                text-decoration: underline;
                text-decoration-color: #D6D3D1;
                text-underline-offset: 6px;
                text-decoration-thickness: 2px;
            }

            .bv-hero-rotating > span.active {
                opacity: 1;
                transform: translateY(0);
            }

            /* Hero pagination dots (vertical) */
            .bv-hero-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                border: none;
                padding: 0;
                background: #D6D3D1;
                cursor: pointer;
                transition: all 0.3s ease;
                flex-shrink: 0;
            }

            .bv-hero-dot.active {
                height: 24px;
                border-radius: 4px;
                background: #111827;
            }

            .bv-hero-dot:hover:not(.active) {
                background: #A8A29E;
            }

            /* ============================================
               FAQ ACCORDION
               ============================================ */

            .bv-faq details {
                border-bottom: 1px solid #E7E5E4;
            }

            .bv-faq details:first-child {
                border-top: 1px solid #E7E5E4;
            }

            .bv-faq summary {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1.25rem 0;
                font-size: 1rem;
                font-weight: 600;
                color: #111827;
                cursor: pointer;
                list-style: none;
                user-select: none;
            }

            .bv-faq summary::-webkit-details-marker {
                display: none;
            }

            .bv-faq summary::after {
                content: '';
                display: inline-block;
                width: 20px;
                height: 20px;
                flex-shrink: 0;
                margin-left: 1rem;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23A8A29E' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: center;
                transition: transform 0.2s ease;
            }

            .bv-faq details[open] summary::after {
                transform: rotate(180deg);
            }

            .bv-faq details p {
                padding: 0 0 1.25rem 0;
                margin: 0;
                color: #57534E;
                font-size: 0.9375rem;
                line-height: 1.7;
            }

            /* ============================================
               STATS BADGE (hero inline pill)
               ============================================ */

            .bv-stats-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.5rem 1rem;
                background: #F5F5F4;
                border: 1px solid #E7E5E4;
                border-radius: 100px;
                font-size: 0.8125rem;
                color: #57534E;
            }

            .bv-stats-badge .dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #22C55E;
                flex-shrink: 0;
            }

            /* ============================================
               SECTION HEADER COMPONENT
               ============================================ */

            .bv-section-header { margin-bottom: 24px; }
            .bv-section-header--center { text-align: center; }
            .bv-section-header__eyebrow {
                display: block;
                font-size: 12px;
                font-weight: 600;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                color: #FF8B5E;
                margin-bottom: 8px;
            }
            .bv-section-header__heading {
                font-weight: 300;
                letter-spacing: -0.02em;
                color: #1A1A1A;
                margin: 0;
            }
            .bv-section-header__subtitle {
                color: #5A5A5A;
                margin-top: 8px;
                font-size: 1.0625rem;
                line-height: 1.5;
            }

            /* ============================================
               VIEW TOGGLE (grid/list switcher)
               ============================================ */

            .bv-view-toggle {
                display: none;
                align-items: center;
                background: #F5F5F4;
                border-radius: 0.5rem;
                padding: 0.125rem;
            }
            @media (min-width: 768px) {
                .bv-view-toggle { display: flex; }
            }
            .bv-view-toggle__btn {
                padding: 0.5rem 0.625rem;
                border-radius: 0.375rem;
                transition: all 0.15s ease;
                border: none;
                background: transparent;
                color: #57534E;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
            }
            .bv-view-toggle__btn--active,
            .bv-view-toggle__btn[aria-pressed="true"] {
                background: #FFFFFF;
                color: #111827;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            }

            /* ============================================
               TAG CLOUD (archive headers)
               ============================================ */

            .bv-tag-cloud {
                display: none;
                flex-wrap: wrap;
                gap: 0.5rem;
                align-items: flex-start;
                align-content: flex-start;
            }

            @media (min-width: 1024px) {
                .bv-tag-cloud {
                    display: flex;
                }
            }

            @keyframes bv-tag-fade-in {
                from { opacity: 0; transform: translateY(4px) rotate(var(--tag-rotate, 0deg)); }
                to   { opacity: 1; transform: translateY(0) rotate(var(--tag-rotate, 0deg)); }
            }

            .bv-tag-cloud__tag {
                display: inline-flex;
                align-items: center;
                padding: 0.375rem 0.875rem;
                border-radius: 100px;
                font-size: 0.8125rem;
                font-weight: 500;
                color: #57534E;
                background: #FFFFFF;
                border: 1px solid #D6D1C6;
                white-space: nowrap;
                transform: rotate(var(--tag-rotate, 0deg));
                animation: bv-tag-fade-in 0.35s ease-out forwards;
                animation-delay: var(--tag-delay, 0s);
                opacity: 0;
                transition: transform 0.2s ease, border-color 0.2s ease, color 0.2s ease;
            }

            button.bv-tag-cloud__tag {
                cursor: pointer;
            }

            button.bv-tag-cloud__tag:hover {
                transform: rotate(0deg);
                border-color: #111827;
                color: #111827;
            }

            button.bv-tag-cloud__tag:focus-visible {
                outline: 2px solid #FF8B5E;
                outline-offset: 2px;
            }

            span.bv-tag-cloud__tag {
                cursor: default;
            }

            @media (prefers-reduced-motion: reduce) {
                .bv-tag-cloud__tag {
                    animation: none;
                    opacity: 1;
                    transform: rotate(0deg) !important;
                }
            }

            /* ============================================
               LINE CLAMP UTILITIES
               ============================================ */

            .line-clamp-2 {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .line-clamp-3 {
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            /* ============================================
               STAT PILL COMPONENT
               ============================================ */

            .bv-stat-pill { display: flex; flex-direction: column; align-items: center; gap: 4px; }
            .bv-stat-pill__number { font-size: 2rem; font-weight: 300; letter-spacing: -0.03em; line-height: 1; }
            .bv-stat-pill__label { font-size: 0.8125rem; color: #5A5A5A; }
            .bv-stat-pill--orange .bv-stat-pill__number { color: #FF8B5E; }
            .bv-stat-pill--black .bv-stat-pill__number { color: #1A1A1A; }

            /* ============================================
               BADGE COMPONENT
               Inline pill badges for status, categories, etc.
               ============================================ */

            .bv-badge {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                font-weight: 500;
                border-radius: 9999px;
                white-space: nowrap;
            }

            /* Size variants */
            .bv-badge--small { padding: 2px 8px; font-size: 12px; line-height: 18px; }
            .bv-badge--medium { padding: 4px 12px; font-size: 13px; line-height: 20px; }

            /* Color variants */
            .bv-badge--green { background: #DCFCE7; color: #166534; }
            .bv-badge--yellow { background: #FEF9C3; color: #854D0E; }
            .bv-badge--red { background: #FEE2E2; color: #991B1B; }
            .bv-badge--gray { background: #F3F4F6; color: #4B5563; }
            .bv-badge--blue { background: #DBEAFE; color: #1E40AF; }
            .bv-badge--orange { background: #FFF3ED; color: #C2410C; }
            .bv-badge--purple { background: #F3E8FF; color: #7C3AED; }
            .bv-badge--teal { background: #CCFBF1; color: #0F766E; }
            .bv-badge--amber { background: #FEF3C7; color: #92400E; }

            /* Category variant - more subtle with border */
            .bv-badge--category { background: #F5F5F4; color: #57534E; border: 1px solid #E7E5E4; }

            /* Badge icon styling */
            .bv-badge__icon {
                display: inline-flex;
                align-items: center;
                flex-shrink: 0;
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
