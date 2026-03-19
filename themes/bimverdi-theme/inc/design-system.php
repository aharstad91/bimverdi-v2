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
                --font-family-display: 'Familjen Grotesk', system-ui, -apple-system, sans-serif;
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
                --heading-weight-semibold: 600;
                --heading-weight-extrabold: 800;
                --heading-tracking: -0.02em;

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
                font-family: var(--font-family-display);
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
               TYPOGRAPHY UTILITIES (shadcn-inspired)
               ============================================ */

            .bv-h1 {
                font-family: var(--font-family-display);
                font-size: 2.25rem;
                font-weight: 800;
                letter-spacing: -0.02em;
                line-height: 2.5rem;
                color: #18181B;
            }

            .bv-h2 {
                font-family: var(--font-family-display);
                font-size: 1.875rem;
                font-weight: 600;
                letter-spacing: -0.02em;
                line-height: 2.25rem;
                color: #18181B;
            }

            .bv-h3 {
                font-family: var(--font-family-display);
                font-size: 1.5rem;
                font-weight: 600;
                letter-spacing: -0.02em;
                line-height: 2rem;
                color: #18181B;
            }

            .bv-h4 {
                font-family: var(--font-family-display);
                font-size: 1.25rem;
                font-weight: 600;
                letter-spacing: -0.02em;
                line-height: 1.75rem;
                color: #18181B;
            }

            .bv-p {
                font-size: 1rem;
                line-height: 1.75rem;
                color: #18181B;
            }

            .bv-lead {
                font-size: 1.25rem;
                line-height: 1.75rem;
                color: #71717A;
            }

            .bv-large {
                font-size: 1.125rem;
                font-weight: 600;
                color: #18181B;
            }

            .bv-small {
                font-size: 0.875rem;
                font-weight: 500;
                line-height: 1;
                color: #18181B;
            }

            .bv-muted {
                font-size: 0.875rem;
                color: #71717A;
            }

            .bv-blockquote {
                margin-top: 1.5rem;
                border-left: 2px solid #E4E4E7;
                padding-left: 1.5rem;
                font-style: italic;
                color: #18181B;
            }

            .bv-code {
                position: relative;
                font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
                font-size: 0.875rem;
                font-weight: 600;
                background: #F4F4F5;
                padding: 0.2rem 0.3rem;
                border-radius: 4px;
            }

            .bv-list {
                margin: 1.5rem 0;
                margin-left: 1.5rem;
                list-style-type: disc;
                color: #18181B;
                line-height: 1.75rem;
            }

            .bv-list > li {
                margin-top: 0.5rem;
            }

            /* ============================================
               TABLE (shadcn-inspired)
               ============================================ */

            .bv-table-wrapper {
                position: relative;
                width: 100%;
                overflow: auto;
            }

            .bv-table {
                width: 100%;
                caption-side: bottom;
                font-size: 14px;
                border-collapse: collapse;
            }

            .bv-table caption {
                margin-top: 16px;
                font-size: 14px;
                color: #71717A;
            }

            .bv-table thead tr {
                border-bottom: 1px solid #E4E4E7;
            }

            .bv-table th {
                height: 40px;
                padding: 0 8px;
                text-align: left;
                vertical-align: middle;
                font-size: 14px;
                font-weight: 500;
                color: #71717A;
                white-space: nowrap;
            }

            .bv-table th.text-right,
            .bv-table td.text-right {
                text-align: right;
            }

            .bv-table tbody tr {
                border-bottom: 1px solid #E4E4E7;
                transition: background-color 0.15s;
            }

            .bv-table tbody tr:last-child {
                border-bottom: 0;
            }

            .bv-table tbody tr:hover {
                background: rgba(244, 244, 245, 0.5);
            }

            .bv-table td {
                padding: 8px;
                vertical-align: middle;
                color: #18181B;
            }

            .bv-table td.font-medium {
                font-weight: 500;
            }

            .bv-table tfoot {
                border-top: 1px solid #E4E4E7;
                background: rgba(244, 244, 245, 0.5);
            }

            .bv-table tfoot tr:last-child {
                border-bottom: 0;
            }

            .bv-table tfoot td {
                padding: 8px;
                font-weight: 500;
                color: #18181B;
            }

            /* -- Table column types -- */

            .bv-table-avatar {
                width: 36px;
                height: 36px;
                border-radius: 6px;
                background: #F5F5F4;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                flex-shrink: 0;
            }
            .bv-table-avatar img {
                width: 100%;
                height: 100%;
                object-fit: contain;
            }
            .bv-table-avatar span {
                font-size: 12px;
                font-weight: 700;
                color: #111827;
                letter-spacing: -0.02em;
            }
            .bv-table-avatar .bv-icon {
                color: #78716C;
            }

            .bv-table-badge {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                font-size: 12px;
                font-weight: 500;
                color: #57534E;
                background: #F5F5F4;
                padding: 2px 8px;
                border-radius: 100px;
                white-space: nowrap;
            }
            .bv-table-badge-icon {
                font-size: 13px;
                line-height: 1;
            }

            .bv-table-link {
                color: #18181B;
                text-decoration: none;
                font-weight: 500;
            }
            .bv-table-link:hover {
                text-decoration: underline;
            }

            .bv-table-action {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 28px;
                height: 28px;
                border-radius: 6px;
                color: #71717A;
                transition: background-color 0.15s, color 0.15s;
            }
            .bv-table-action:hover {
                background: #F5F5F4;
                color: #18181B;
            }

            /* ============================================
               PAGINATION (shadcn-inspired)
               ============================================ */

            .bv-pagination {
                display: flex;
                justify-content: center;
            }

            .bv-pagination__list {
                display: flex;
                align-items: center;
                gap: 4px;
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .bv-pagination__link {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 32px;
                height: 32px;
                padding: 0 8px;
                font-size: 14px;
                font-weight: 500;
                color: #18181B;
                text-decoration: none;
                border-radius: 6px;
                border: 1px solid transparent;
                cursor: pointer;
                transition: background-color 0.15s, border-color 0.15s;
            }

            .bv-pagination__link:hover {
                background: #F4F4F5;
                text-decoration: none;
                color: #18181B;
            }

            .bv-pagination__link--active {
                border-color: #E4E4E7;
                background: transparent;
                cursor: default;
            }

            .bv-pagination__link--active:hover {
                background: transparent;
            }

            .bv-pagination__link--disabled {
                opacity: 0.5;
                cursor: not-allowed;
                pointer-events: none;
            }

            .bv-pagination__prev,
            .bv-pagination__next {
                gap: 4px;
                padding: 0 12px;
            }

            .bv-pagination__ellipsis {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 32px;
                height: 32px;
                font-size: 14px;
                color: #71717A;
            }

            .bv-pagination__link:focus-visible {
                outline: none;
                box-shadow: 0 0 0 2px #FFFFFF, 0 0 0 4px #18181B;
            }

            /* ============================================
               ITEM (shadcn-inspired)
               Versatile list item with media, content, actions
               ============================================ */

            .bv-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px;
                border-radius: 8px;
                text-decoration: none;
                color: inherit;
                transition: background-color 0.15s;
            }

            .bv-item--outline {
                border: 1px solid #E4E4E7;
            }

            .bv-item--muted {
                background: #F4F4F5;
            }

            .bv-item--link {
                cursor: pointer;
            }

            .bv-item--link:hover {
                background: rgba(244, 244, 245, 0.5);
                text-decoration: none;
                color: inherit;
            }

            .bv-item--outline.bv-item--link:hover {
                background: #FAFAFA;
            }

            /* Small size */
            .bv-item--sm {
                gap: 10px;
                padding: 8px 12px;
            }

            /* Media — icon */
            .bv-item__media--icon {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                border-radius: 8px;
                background: #F4F4F5;
                color: #71717A;
                flex-shrink: 0;
            }

            .bv-item--sm .bv-item__media--icon {
                width: 32px;
                height: 32px;
                border-radius: 6px;
            }

            /* Media — avatar */
            .bv-item__media--avatar {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                border-radius: 9999px;
                color: #FFFFFF;
                font-size: 14px;
                font-weight: 600;
                flex-shrink: 0;
            }

            .bv-item--sm .bv-item__media--avatar {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }

            /* Content */
            .bv-item__content {
                flex: 1;
                min-width: 0;
            }

            .bv-item__title {
                font-size: 14px;
                font-weight: 500;
                color: #18181B;
                line-height: 1.4;
            }

            .bv-item__description {
                font-size: 14px;
                color: #71717A;
                line-height: 1.4;
                margin-top: 1px;
            }

            .bv-item--sm .bv-item__title {
                font-size: 13px;
            }

            .bv-item--sm .bv-item__description {
                font-size: 13px;
            }

            /* Meta text */
            .bv-item__meta {
                font-size: 14px;
                color: #71717A;
                flex-shrink: 0;
                white-space: nowrap;
            }

            /* Actions */
            .bv-item__actions {
                flex-shrink: 0;
            }

            /* Chevron for link items */
            .bv-item__chevron {
                flex-shrink: 0;
                color: #A1A1AA;
            }

            /* Item Group — stacked items with separators */
            .bv-item-group {
                display: flex;
                flex-direction: column;
            }

            .bv-item-group > .bv-item + .bv-item {
                border-top: 1px solid #E4E4E7;
            }

            .bv-item-group > .bv-item {
                border-radius: 0;
            }

            .bv-item-group > .bv-item:first-child {
                border-radius: 8px 8px 0 0;
            }

            .bv-item-group > .bv-item:last-child {
                border-radius: 0 0 8px 8px;
            }

            .bv-item-group > .bv-item:only-child {
                border-radius: 8px;
            }

            .bv-item-group--outline {
                border: 1px solid #E4E4E7;
                border-radius: 8px;
            }

            .bv-item-group--outline > .bv-item {
                border: none;
                border-radius: 0;
            }

            .bv-item-group--outline > .bv-item + .bv-item {
                border-top: 1px solid #E4E4E7;
            }

            /* ============================================
               EMPTY STATE (shadcn-inspired)
               ============================================ */

            /* Default: centered vertical stack */
            .bv-empty {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 48px 16px;
            }

            .bv-empty__icon {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 48px;
                height: 48px;
                border-radius: 9999px;
                background: #F4F4F5;
                color: #A1A1AA;
                margin-bottom: 16px;
            }

            .bv-empty__title {
                font-size: 16px;
                font-weight: 600;
                color: #18181B;
                margin-bottom: 4px;
            }

            .bv-empty__description {
                font-size: 14px;
                color: #71717A;
                max-width: 320px;
                margin: 0;
                line-height: 1.5;
            }

            .bv-empty__actions {
                margin-top: 20px;
            }

            /* Outline variant */
            .bv-empty--outline {
                border: 1px dashed #E4E4E7;
                border-radius: 8px;
            }

            /* Compact variant: horizontal inline */
            .bv-empty--compact {
                flex-direction: row;
                text-align: left;
                padding: 24px;
                gap: 12px;
                background: #FAFAFA;
                border-radius: 8px;
            }

            .bv-empty--compact .bv-empty__icon {
                width: 40px;
                height: 40px;
                margin-bottom: 0;
                flex-shrink: 0;
            }

            .bv-empty--compact .bv-empty__body {
                min-width: 0;
            }

            .bv-empty--compact .bv-empty__title {
                font-size: 14px;
                margin-bottom: 2px;
            }

            .bv-empty--compact .bv-empty__description {
                font-size: 13px;
            }

            /* ============================================
               NAVIGATION MENU (shadcn-inspired)
               Styles WordPress wp_nav_menu output
               ============================================ */

            .bv-nav {
                display: none;
                align-items: center;
            }

            @media (min-width: 768px) {
                .bv-nav {
                    display: flex;
                }
            }

            .bv-nav__list {
                display: flex;
                align-items: center;
                gap: 2px;
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .bv-nav__list .menu-item {
                position: relative;
                list-style: none;
            }

            .bv-nav__list > .menu-item > a {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 8px 12px;
                font-size: 14px;
                font-weight: 500;
                color: #18181B;
                text-decoration: none;
                border-radius: 6px;
                transition: background-color 0.15s, color 0.15s;
            }

            .bv-nav__list > .menu-item > a:hover {
                background: #F4F4F5;
                color: #18181B;
                text-decoration: none;
            }

            .bv-nav__list > .menu-item > a:focus-visible {
                outline: none;
                box-shadow: 0 0 0 2px #FFFFFF, 0 0 0 4px #18181B;
            }

            /* Chevron on dropdown triggers */
            .bv-nav__list > .menu-item.menu-item-has-children > a::after {
                content: '';
                display: inline-block;
                width: 16px;
                height: 16px;
                flex-shrink: 0;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2371717A' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
                background-size: 16px 16px;
                background-repeat: no-repeat;
                transition: transform 0.2s;
            }

            .bv-nav__list > .menu-item.menu-item-has-children:hover > a::after {
                transform: rotate(180deg);
            }

            /* Dropdown panel */
            .bv-nav__list .sub-menu {
                position: absolute;
                top: 100%;
                left: 0;
                min-width: 220px;
                margin-top: 4px;
                padding: 4px;
                background: #FFFFFF;
                border: 1px solid #E4E4E7;
                border-radius: 8px;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
                opacity: 0;
                visibility: hidden;
                transform: translateY(-4px);
                transition: opacity 0.15s, visibility 0.15s, transform 0.15s;
                z-index: 100;
                list-style: none;
            }

            .bv-nav__list .menu-item:hover > .sub-menu,
            .bv-nav__list .menu-item:focus-within > .sub-menu {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }

            .bv-nav__list .sub-menu .menu-item {
                display: block;
                padding: 0;
            }

            .bv-nav__list .sub-menu a {
                display: block;
                padding: 8px 12px;
                font-size: 14px;
                font-weight: 400;
                color: #18181B;
                text-decoration: none;
                border-radius: 6px;
                transition: background-color 0.15s;
                line-height: 1.4;
            }

            .bv-nav__list .sub-menu a:hover,
            .bv-nav__list .sub-menu a:focus {
                background: #F4F4F5;
                color: #18181B;
                text-decoration: none;
            }

            /* ============================================
               MOBILE MENU PANEL
               ============================================ */

            .bv-mobile-menu {
                top: 4rem; /* h-16 header */
            }

            .admin-bar .bv-mobile-menu {
                top: calc(4rem + 32px); /* header + mobile admin bar */
            }

            @media (min-width: 783px) {
                .admin-bar .bv-mobile-menu {
                    top: calc(4rem + 46px); /* header + desktop admin bar */
                }
            }

            /* ============================================
               MOBILE NAVIGATION
               ============================================ */

            .bv-mobile-nav {
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .bv-mobile-nav .menu-item > a {
                display: block;
                padding: 12px;
                font-size: 16px;
                font-weight: 500;
                color: #1A1A1A;
                text-decoration: none;
                border-radius: 8px;
                transition: background-color 0.15s;
            }

            .bv-mobile-nav .menu-item > a:hover,
            .bv-mobile-nav .menu-item > a:focus {
                background: #F5F5F4;
            }

            .bv-mobile-nav .menu-item.current-menu-item > a {
                font-weight: 600;
                color: #FF8B5E;
            }

            /* Flatten submenus in mobile */
            .bv-mobile-nav .sub-menu {
                list-style: none;
                margin: 0;
                padding: 0 0 0 16px;
            }

            .bv-mobile-nav .sub-menu a {
                display: block;
                padding: 10px 12px;
                font-size: 15px;
                font-weight: 400;
                color: #5A5A5A;
                text-decoration: none;
                border-radius: 6px;
                transition: background-color 0.15s;
            }

            .bv-mobile-nav .sub-menu a:hover {
                background: #F5F5F4;
                color: #1A1A1A;
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
                font-family: var(--font-family);
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
               Breadcrumb (shadcn-inspired)
               ============================================ */

            .bv-breadcrumb {
                margin-bottom: 16px;
            }

            .bv-breadcrumb__list {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
                list-style: none;
                margin: 0;
                padding: 0;
                font-size: 14px;
                line-height: 20px;
            }

            .bv-breadcrumb__item {
                display: inline-flex;
                align-items: center;
            }

            .bv-breadcrumb__link {
                color: #71717A;
                text-decoration: none;
                transition: color 0.15s ease;
            }

            .bv-breadcrumb__link:hover {
                color: #18181B;
            }

            .bv-breadcrumb__separator {
                display: inline-flex;
                align-items: center;
                color: #A1A1AA;
            }

            .bv-breadcrumb__separator svg {
                flex-shrink: 0;
            }

            .bv-breadcrumb__page {
                color: #18181B;
                font-weight: 500;
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
               FORM ELEMENTS (shadcn-inspired)
               ============================================ */

            /* Field wrapper */
            .bv-field {
                display: flex;
                flex-direction: column;
                gap: 6px;
                margin-bottom: 16px;
            }

            .bv-field__label {
                display: block;
                font-size: 14px;
                font-weight: 500;
                line-height: 20px;
                color: #18181B;
            }

            .bv-field__required {
                color: #DC2626;
                margin-left: 2px;
            }

            /* Input / Textarea / Select base */
            .bv-field__input,
            .input-hjem,
            input[type="text"],
            input[type="email"],
            input[type="password"],
            input[type="number"],
            input[type="tel"],
            input[type="date"],
            input[type="url"],
            textarea,
            select {
                width: 100%;
                height: 32px;
                padding: 0 12px;
                border: 1px solid #E4E4E7;
                border-radius: 6px;
                font-family: var(--font-family);
                font-size: 14px;
                line-height: 20px;
                color: #18181B;
                background-color: transparent;
                transition: border-color 0.15s ease, box-shadow 0.15s ease;
            }

            .bv-field__input::placeholder,
            input::placeholder,
            textarea::placeholder {
                color: #A1A1AA;
            }

            .bv-field__input:focus,
            input:focus,
            textarea:focus,
            select:focus {
                outline: none;
                border-color: #18181B;
                box-shadow: 0 0 0 2px #FFFFFF, 0 0 0 4px #18181B;
            }

            .bv-field__input:disabled,
            input:disabled,
            textarea:disabled,
            select:disabled {
                opacity: 0.5;
                cursor: not-allowed;
                background-color: #F4F4F5;
            }

            /* Error state */
            .bv-field__input--error,
            .bv-field--error .bv-field__input,
            .bv-field--error input,
            .bv-field--error textarea,
            .bv-field--error select {
                border-color: #DC2626;
            }

            .bv-field__input--error:focus,
            .bv-field--error .bv-field__input:focus,
            .bv-field--error input:focus {
                border-color: #DC2626;
                box-shadow: 0 0 0 2px #FFFFFF, 0 0 0 4px #DC2626;
            }

            /* Textarea */
            textarea,
            textarea.bv-field__input {
                height: auto;
                min-height: 80px;
                padding: 8px 12px;
                resize: vertical;
            }

            /* Select */
            select,
            select.bv-field__input {
                appearance: none;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2371717A' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 10px center;
                padding-right: 32px;
            }

            /* Description text */
            .bv-field__description {
                font-size: 13px;
                line-height: 18px;
                color: #71717A;
                margin: 0;
            }

            /* Error message */
            .bv-field__error {
                font-size: 13px;
                line-height: 18px;
                color: #DC2626;
                margin: 0;
            }

            .bv-field--error .bv-field__label {
                color: #DC2626;
            }

            /* Checkbox field */
            .bv-field--checkbox {
                flex-direction: row;
                margin-bottom: 16px;
            }

            .bv-field__checkbox-row {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .bv-field__checkbox-label {
                font-size: 14px;
                font-weight: 400;
                line-height: 20px;
                color: #18181B;
                cursor: pointer;
            }

            /* Checkbox input — shadcn style */
            .bv-field__checkbox,
            .checkbox-hjem,
            input[type="checkbox"] {
                -webkit-appearance: none;
                appearance: none;
                width: 16px;
                height: 16px;
                border: 1px solid #D4D4D8;
                border-radius: 3px;
                cursor: pointer;
                position: relative;
                flex-shrink: 0;
                transition: all 0.15s ease;
                background: white;
                margin: 0;
                padding: 0;
            }

            input[type="checkbox"]:hover {
                border-color: #A1A1AA;
            }

            input[type="checkbox"]:focus-visible {
                outline: none;
                box-shadow: 0 0 0 2px #FFFFFF, 0 0 0 4px #18181B;
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

            /* Field Group (horizontal row) */
            .bv-field-group {
                border: none;
                padding: 0;
                margin: 0 0 24px;
            }

            .bv-field-group__legend {
                font-size: 14px;
                font-weight: 600;
                line-height: 20px;
                color: #18181B;
                margin-bottom: 4px;
                padding: 0;
            }

            .bv-field-group__description {
                font-size: 13px;
                line-height: 18px;
                color: #71717A;
                margin: 0 0 12px;
            }

            .bv-field-group__fields {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 12px;
            }

            .bv-field-group__fields .bv-field {
                margin-bottom: 0;
            }

            /* Legacy compat */
            label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 500;
                font-size: 14px;
                color: #18181B;
            }

            .form-group {
                margin-bottom: var(--spacing-md);
            }

            .form-group label {
                margin-bottom: var(--spacing-xs);
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
                font-family: var(--font-family-display);
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

            .bv-section-header__row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                margin-bottom: 24px;
            }

            .bv-section-header__row .bv-section-header {
                margin-bottom: 0;
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
                font-size: 12px;
                font-weight: 600;
                line-height: 1;
                padding: 2px 10px;
                height: 22px;
                border-radius: 9999px;
                white-space: nowrap;
                border: 1px solid transparent;
                transition: color 0.15s, background-color 0.15s;
            }

            /* Variant: default — solid dark */
            .bv-badge--default {
                background: #18181B;
                color: #FAFAFA;
                border-color: #18181B;
            }

            /* Variant: secondary — subtle gray */
            .bv-badge--secondary {
                background: #F4F4F5;
                color: #18181B;
                border-color: #F4F4F5;
            }

            /* Variant: destructive — red */
            .bv-badge--destructive {
                background: #FEF2F2;
                color: #DC2626;
                border-color: #FEF2F2;
            }

            /* Variant: outline — border only */
            .bv-badge--outline {
                background: transparent;
                color: #18181B;
                border-color: #E4E4E7;
            }

            /* Semantic color overrides (work with any variant) */
            .bv-badge--green  { background: #DCFCE7; color: #166534; border-color: #DCFCE7; }
            .bv-badge--yellow { background: #FEF9C3; color: #854D0E; border-color: #FEF9C3; }
            .bv-badge--red    { background: #FEE2E2; color: #991B1B; border-color: #FEE2E2; }
            .bv-badge--gray   { background: #F4F4F5; color: #18181B; border-color: #F4F4F5; }
            .bv-badge--blue   { background: #DBEAFE; color: #1E40AF; border-color: #DBEAFE; }
            .bv-badge--orange { background: #FFF3ED; color: #C2410C; border-color: #FFF3ED; }
            .bv-badge--purple { background: #F3E8FF; color: #7C3AED; border-color: #F3E8FF; }
            .bv-badge--teal   { background: #CCFBF1; color: #0F766E; border-color: #CCFBF1; }
            .bv-badge--amber  { background: #FEF3C7; color: #92400E; border-color: #FEF3C7; }

            /* Badge icon */
            .bv-badge__icon {
                display: inline-flex;
                align-items: center;
                flex-shrink: 0;
            }

            /* ============================================
               CARD (shadcn-inspired)
               Composable card with header, content, footer
               ============================================ */

            .bv-card2 {
                border: 1px solid #E4E4E7;
                border-radius: 12px;
                background: #FFFFFF;
                box-shadow: 0 1px 2px rgba(0,0,0,0.04);
                overflow: hidden;
                color: inherit;
                text-decoration: none;
                display: block;
            }

            a.bv-card2 { cursor: pointer; }
            a.bv-card2:hover {
                box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                border-color: #D4D4D8;
                text-decoration: none;
                color: inherit;
            }

            .bv-card2__header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 16px;
                padding: 24px 24px 0;
            }

            .bv-card2--sm .bv-card2__header {
                padding: 16px 16px 0;
            }

            .bv-card2__header-text {
                flex: 1;
                min-width: 0;
            }

            .bv-card2__title {
                font-family: var(--font-family-display);
                font-size: 18px;
                font-weight: 600;
                color: #18181B;
                line-height: 1.3;
                letter-spacing: -0.01em;
            }

            .bv-card2--sm .bv-card2__title {
                font-size: 16px;
            }

            .bv-card2__description {
                font-size: 14px;
                color: #71717A;
                line-height: 1.5;
                margin-top: 4px;
            }

            .bv-card2__action {
                flex-shrink: 0;
            }

            .bv-card2__content {
                padding: 16px 24px;
                font-size: 14px;
                color: #18181B;
                line-height: 1.6;
            }

            .bv-card2--sm .bv-card2__content {
                padding: 12px 16px;
            }

            .bv-card2__footer {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 0 24px 24px;
            }

            .bv-card2--sm .bv-card2__footer {
                padding: 0 16px 16px;
            }

            .bv-card2__image {
                width: 100%;
                overflow: hidden;
            }

            .bv-card2__image img {
                width: 100%;
                height: auto;
                display: block;
                object-fit: cover;
            }

            /* ============================================
               ACCORDION (shadcn-inspired)
               Collapsible content sections
               ============================================ */

            .bv-accordion__item {
                border-bottom: 1px solid #E4E4E7;
            }

            .bv-accordion__item:last-child {
                border-bottom: 0;
            }

            /* Bordered variant */
            .bv-accordion--bordered {
                border: 1px solid #E4E4E7;
                border-radius: 8px;
                overflow: hidden;
            }

            .bv-accordion--bordered .bv-accordion__item {
                border-bottom: 1px solid #E4E4E7;
            }

            .bv-accordion--bordered .bv-accordion__item:last-child {
                border-bottom: 0;
            }

            /* Trigger / summary */
            .bv-accordion__trigger {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                padding: 16px 0;
                font-size: 14px;
                font-weight: 500;
                color: #18181B;
                cursor: pointer;
                list-style: none;
                user-select: none;
                transition: color 0.15s;
            }

            .bv-accordion--bordered .bv-accordion__trigger {
                padding: 16px;
            }

            .bv-accordion__trigger::-webkit-details-marker {
                display: none;
            }

            .bv-accordion__trigger::marker {
                display: none;
                content: '';
            }

            .bv-accordion__trigger:hover {
                text-decoration: underline;
            }

            .bv-accordion__trigger:focus-visible {
                outline: none;
                box-shadow: 0 0 0 2px #FFFFFF, 0 0 0 4px #18181B;
                border-radius: 4px;
            }

            .bv-accordion__trigger-text {
                flex: 1;
                text-align: left;
            }

            /* Chevron rotation */
            .bv-accordion__chevron {
                flex-shrink: 0;
                color: #71717A;
                transition: transform 0.2s ease;
            }

            details[open] > .bv-accordion__trigger .bv-accordion__chevron {
                transform: rotate(180deg);
            }

            /* Content panel */
            .bv-accordion__content {
                overflow: hidden;
            }

            .bv-accordion__content-inner {
                padding: 0 0 16px 0;
                font-size: 14px;
                color: #71717A;
                line-height: 1.6;
            }

            .bv-accordion--bordered .bv-accordion__content-inner {
                padding: 0 16px 16px 16px;
            }

            /* Disabled */
            .bv-accordion__item--disabled {
                opacity: 0.5;
                pointer-events: none;
            }

            /* ============================================
               ALERT (shadcn-inspired)
               Callout for important information
               ============================================ */

            .bv-alert {
                position: relative;
                display: flex;
                align-items: flex-start;
                gap: 12px;
                width: 100%;
                padding: 16px;
                border-radius: 8px;
                border: 1px solid #E4E4E7;
                font-size: 14px;
                line-height: 1.5;
            }

            /* Icon — aligned to title line-height */
            .bv-alert__icon {
                flex-shrink: 0;
                display: flex;
                align-items: center;
                height: 21px; /* matches title line-height (15px * 1.4) */
                color: #18181B;
            }

            /* No title: center icon with single-line description */
            .bv-alert--no-title {
                align-items: center;
            }

            /* Content */
            .bv-alert__content {
                flex: 1;
                min-width: 0;
            }

            .bv-alert__title {
                font-size: 15px;
                font-weight: 600;
                color: #18181B;
                line-height: 1.4;
                letter-spacing: -0.01em;
                margin: 0 0 4px 0;
                font-family: var(--font-family-display);
            }

            .bv-alert__title:last-child {
                margin-bottom: 0;
            }

            .bv-alert__description {
                font-size: 13px;
                color: #71717A;
                line-height: 1.5;
            }

            .bv-alert__description p:last-child {
                margin-bottom: 0;
            }

            /* Close button */
            .bv-alert__close {
                position: absolute;
                top: 12px;
                right: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 24px;
                height: 24px;
                padding: 0;
                border: none;
                background: none;
                color: #A1A1AA;
                cursor: pointer;
                border-radius: 4px;
                transition: color 0.15s, background-color 0.15s;
            }

            .bv-alert__close:hover {
                color: #18181B;
                background-color: #F4F4F5;
            }

            /* Variant: default */
            .bv-alert--default {
                background-color: #FAFAFA;
                border-color: #E4E4E7;
            }

            .bv-alert--default .bv-alert__icon {
                color: #18181B;
            }

            /* Variant: destructive */
            .bv-alert--destructive {
                background-color: #FEF2F2;
                border-color: #FECACA;
            }

            .bv-alert--destructive .bv-alert__icon {
                color: #DC2626;
            }

            .bv-alert--destructive .bv-alert__title {
                color: #DC2626;
            }

            .bv-alert--destructive .bv-alert__description {
                color: #991B1B;
            }

            /* Variant: success */
            .bv-alert--success {
                background-color: #F0FDF4;
                border-color: #BBF7D0;
            }

            .bv-alert--success .bv-alert__icon {
                color: #16A34A;
            }

            .bv-alert--success .bv-alert__title {
                color: #15803D;
            }

            .bv-alert--success .bv-alert__description {
                color: #166534;
            }

            /* Variant: warning */
            .bv-alert--warning {
                background-color: #FFFBEB;
                border-color: #FDE68A;
            }

            .bv-alert--warning .bv-alert__icon {
                color: #D97706;
            }

            .bv-alert--warning .bv-alert__title {
                color: #92400E;
            }

            .bv-alert--warning .bv-alert__description {
                color: #78350F;
            }

            /* ============================================
               AVATAR (shadcn-inspired)
               Circular avatar with image, initials, badge
               ============================================ */

            .bv-avatar {
                position: relative;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                border-radius: 9999px;
                flex-shrink: 0;
                vertical-align: middle;
                overflow: hidden;
            }

            .bv-avatar--sm { width: 32px; height: 32px; }
            .bv-avatar--lg { width: 48px; height: 48px; }

            .bv-avatar__image {
                width: 100%;
                height: 100%;
                object-fit: cover;
                border-radius: 9999px;
            }

            .bv-avatar__fallback {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                height: 100%;
                border-radius: 9999px;
                background: #18181B;
                color: #FAFAFA;
                font-size: 14px;
                font-weight: 600;
                line-height: 1;
                user-select: none;
            }

            .bv-avatar--sm .bv-avatar__fallback { font-size: 12px; }
            .bv-avatar--lg .bv-avatar__fallback { font-size: 16px; }

            /* Overflow count in avatar group */
            .bv-avatar__fallback--overflow {
                background: #F4F4F5;
                color: #71717A;
                font-size: 12px;
                font-weight: 500;
            }

            .bv-avatar--lg .bv-avatar__fallback--overflow { font-size: 14px; }

            /* Status badge dot */
            .bv-avatar--has-badge { overflow: visible; }

            .bv-avatar__badge {
                position: absolute;
                bottom: 0;
                right: 0;
                width: 10px;
                height: 10px;
                border-radius: 9999px;
                border: 2px solid #FFFFFF;
                box-sizing: content-box;
            }

            .bv-avatar--sm .bv-avatar__badge { width: 8px; height: 8px; }
            .bv-avatar--lg .bv-avatar__badge { width: 12px; height: 12px; }

            .bv-avatar__badge--online  { background: #22C55E; }
            .bv-avatar__badge--offline { background: #A1A1AA; }
            .bv-avatar__badge--busy    { background: #EF4444; }
            .bv-avatar__badge--away    { background: #F59E0B; }

            /* Avatar Group — overlapping */
            .bv-avatar-group {
                display: inline-flex;
                align-items: center;
            }

            .bv-avatar-group > .bv-avatar {
                border: 2px solid #FFFFFF;
                box-sizing: content-box;
                margin-left: -8px;
            }

            .bv-avatar-group > .bv-avatar:first-child {
                margin-left: 0;
            }

            /* ============================================
               SWITCH
               Toggle control — pure CSS, no JS
               ============================================ */

            .bv-switch__row {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .bv-switch__track {
                position: relative;
                display: inline-block;
                width: 44px;
                height: 24px;
                flex-shrink: 0;
                cursor: pointer;
                border-radius: 9999px;
                background: #E4E4E7;
                transition: background-color 0.15s;
            }

            .bv-switch__input {
                /* Fully invisible but still functional */
                position: absolute;
                width: 44px;
                height: 24px;
                top: 0;
                left: 0;
                margin: 0;
                padding: 0;
                opacity: 0 !important;
                cursor: pointer;
                z-index: 1;
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
                border: none;
                background: none;
            }

            .bv-switch__thumb {
                position: absolute;
                top: 2px;
                left: 2px;
                width: 20px;
                height: 20px;
                border-radius: 9999px;
                background: #FFFFFF;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                transition: transform 0.15s ease;
                pointer-events: none;
                transform: translateX(0);
            }

            /* Checked state */
            .bv-switch__input:checked ~ .bv-switch__thumb {
                transform: translateX(20px);
            }

            .bv-switch__track:has(.bv-switch__input:checked) {
                background: #18181B;
            }

            /* Focus ring */
            .bv-switch__input:focus-visible ~ .bv-switch__thumb {
                box-shadow: 0 0 0 2px #FFFFFF, 0 0 0 4px #18181B;
            }

            /* Disabled */
            .bv-switch--disabled .bv-switch__track {
                opacity: 0.5;
                cursor: not-allowed;
            }

            .bv-switch--disabled .bv-switch__input {
                cursor: not-allowed;
            }

            .bv-switch--disabled .bv-switch__label {
                opacity: 0.7;
                cursor: not-allowed;
            }

            .bv-switch__label {
                font-size: 14px;
                font-weight: 500;
                color: #18181B;
                cursor: pointer;
                user-select: none;
            }

            .bv-switch__description {
                font-size: 13px;
                color: #71717A;
                margin: 4px 0 0 56px;
            }

            /* ============================================
               TABS
               Tabbed interface — default (pill) & line
               ============================================ */

            /* Default variant — pill container */
            .bv-tabs__list {
                display: inline-flex;
                align-items: center;
                gap: 2px;
                padding: 4px;
                background: #F4F4F5;
                border-radius: 8px;
                margin-bottom: 16px;
            }

            .bv-tabs__trigger {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 6px 12px;
                font-size: 14px;
                font-weight: 500;
                line-height: 1;
                color: #71717A;
                background: transparent;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                white-space: nowrap;
                transition: color 0.15s, background-color 0.15s, box-shadow 0.15s;
            }

            .bv-tabs__trigger:hover {
                color: #18181B;
            }

            .bv-tabs__trigger--active {
                background: #FFFFFF;
                color: #18181B;
                box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            }

            .bv-tabs__trigger:focus-visible {
                outline: none;
                box-shadow: 0 0 0 2px #FFFFFF, 0 0 0 4px #18181B;
            }

            /* Line variant */
            .bv-tabs__list--line {
                background: transparent;
                padding: 0;
                gap: 0;
                border-radius: 0;
                border-bottom: 1px solid #E4E4E7;
            }

            .bv-tabs__list--line .bv-tabs__trigger {
                border-radius: 0;
                padding: 8px 16px;
                margin-bottom: -1px;
                border-bottom: 2px solid transparent;
            }

            .bv-tabs__list--line .bv-tabs__trigger--active {
                background: transparent;
                color: #18181B;
                border-bottom-color: #18181B;
                box-shadow: none;
            }

            .bv-tabs__list--line .bv-tabs__trigger:focus-visible {
                box-shadow: 0 0 0 2px #FFFFFF, 0 0 0 4px #18181B;
                border-radius: 4px 4px 0 0;
            }

            /* Tab panels */
            .bv-tabs__panel {
                display: none;
                padding: 16px 0;
            }

            .bv-tabs__panel--active {
                display: block;
            }

            /* ============================================
               PROSE STYLES — Article content typography
               ============================================ */
            .prose h2 {
                font-size: 1.5rem;
                font-weight: 700;
                margin-top: 2rem;
                margin-bottom: 1rem;
                color: #1A1A1A;
            }

            .prose h3 {
                font-size: 1.25rem;
                font-weight: 600;
                margin-top: 1.5rem;
                margin-bottom: 0.75rem;
                color: #1A1A1A;
            }

            .prose p {
                margin-bottom: 1.25rem;
                line-height: 1.75;
            }

            .prose ul, .prose ol {
                margin-bottom: 1.25rem;
                padding-left: 1.5rem;
            }

            .prose li {
                margin-bottom: 0.5rem;
            }

            .prose a {
                color: #FF8B5E;
                text-decoration: underline;
            }

            .prose a:hover {
                color: #E5743F;
            }

            .prose blockquote {
                border-left: 4px solid #FF8B5E;
                padding-left: 1rem;
                margin: 1.5rem 0;
                font-style: italic;
                color: #5A5A5A;
            }

            .prose img {
                border-radius: 0.5rem;
                margin: 1.5rem 0;
            }

            .prose code {
                background: #F5F5F4;
                padding: 0.125rem 0.25rem;
                border-radius: 0.25rem;
                font-size: 0.875em;
            }

        </style>
        <?php
    }
    
    /**
     * Enqueue design system JavaScript
     */
    public function enqueue_design_js() {
        ?>
        <script>
        /* BV Accordion — single mode (close others on open) */
        (function() {
            function initAccordions() {
                document.querySelectorAll('[data-bv-accordion="single"]').forEach(function(acc) {
                    var items = acc.querySelectorAll('.bv-accordion__item');
                    items.forEach(function(detail) {
                        detail.addEventListener('toggle', function() {
                            if (this.open) {
                                items.forEach(function(other) {
                                    if (other !== detail && other.open) {
                                        other.removeAttribute('open');
                                    }
                                });
                            }
                        });
                    });
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initAccordions);
            } else {
                initAccordions();
            }
        })();

        /* BV Tabs — panel switching */
        (function() {
            function initTabs() {
                document.querySelectorAll('[data-bv-tabs]').forEach(function(wrapper) {
                    var tabsId = wrapper.getAttribute('data-bv-tabs');
                    var triggers = wrapper.querySelectorAll('.bv-tabs__trigger[data-bv-tabs-id="' + tabsId + '"]');
                    var panels = wrapper.querySelectorAll('.bv-tabs__panel[data-bv-tabs-id="' + tabsId + '"]');

                    // Show the default active panel
                    triggers.forEach(function(t) {
                        if (t.classList.contains('bv-tabs__trigger--active')) {
                            var key = t.getAttribute('data-bv-tab');
                            panels.forEach(function(p) {
                                p.classList.toggle('bv-tabs__panel--active', p.getAttribute('data-bv-tab-panel') === key);
                            });
                        }
                    });

                    // Click handler
                    triggers.forEach(function(trigger) {
                        trigger.addEventListener('click', function() {
                            var key = this.getAttribute('data-bv-tab');
                            triggers.forEach(function(t) {
                                t.classList.toggle('bv-tabs__trigger--active', t.getAttribute('data-bv-tab') === key);
                                t.setAttribute('aria-selected', t.getAttribute('data-bv-tab') === key ? 'true' : 'false');
                            });
                            panels.forEach(function(p) {
                                p.classList.toggle('bv-tabs__panel--active', p.getAttribute('data-bv-tab-panel') === key);
                            });
                        });
                    });
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initTabs);
            } else {
                initTabs();
            }
        })();

        /* BV Alert — dismiss */
        (function() {
            document.addEventListener('click', function(e) {
                var btn = e.target.closest('.bv-alert__close');
                if (!btn) return;
                var alert = btn.closest('.bv-alert');
                if (alert) {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.15s ease';
                    setTimeout(function() { alert.remove(); }, 150);
                }
            });
        })();
        </script>
        <?php
    }
}

// Initialize design system
new BIM_Verdi_Design_System();
