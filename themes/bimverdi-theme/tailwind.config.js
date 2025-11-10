/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.php",
    "./template-parts/**/*.php",
    "./inc/**/*.php",
    "./js/**/*.js",
  ],
  
  theme: {
    extend: {
      colors: {
        // EXACT COLOR PALETTE EXTRACTED FROM HJEM.NO (November 2024)
        // Primary brand color - warm orange/coral (hjem.no signature)
        'bim-orange': {
          DEFAULT: '#FF8B5E',  // --tertiary from hjem.no
          50: '#FFF5F2',
          100: '#FFBFA8',      // --tertiary-20: Light orange
          500: '#FF8B5E',      // Main brand orange (warm, inviting)
          600: '#E67A4E',
          700: '#CC6A3E',
          800: '#B35A2E',
        },
        // Secondary - purple for innovation
        'bim-purple': {
          DEFAULT: '#5E36FE',  // --secondary-100 from hjem.no
          100: '#DFD7C6',      // --secondary-10: Light tint
          500: '#5E36FE',      // Main secondary purple
          600: '#4E26DE',
          700: '#3E16BE',
        },
        // Beige/warm neutrals (hjem.no aesthetic)
        'bim-beige': {
          DEFAULT: '#F7F5EF',  // --surface-dim: Main background
          100: '#F7F5EF',      // Main surface (warm white)
          200: '#EFE9DE',      // --state-hover: Hover state
          300: '#DFD7C6',      // Darker beige
          400: '#CABBA6',      // --on-surface-dim-variant
          500: '#C8BBA3',      // --tertiary-variant: Warm neutral
        },
        // Black/dark neutrals (hjem.no text colors)
        'bim-black': {
          DEFAULT: '#0F0F0F',  // --primary: Main text
          50: '#F6F6F6',       // --primary-5: Lightest
          100: '#EBEBEB',      // Light gray
          200: '#D1D1D1',      // --stroke-100: Borders
          300: '#888888',      // --primary-20: Medium light
          400: '#6D6D6D',      // --primary-40
          500: '#4F4F4F',      // --primary-60
          600: '#3D3D3D',      // --primary-80
          700: '#383838',      // --primary-variant: Dark variant
          900: '#0F0F0F',      // Main black
        },
        // UI state colors (from hjem.no CSS variables)
        'bim-success': '#B3DB87',    // --success: Green
        'bim-error': '#772015',      // --error: Red/brown
        'bim-alert': '#FFC845',      // --alert: Yellow
        'bim-info': '#005898',       // --information: Blue
      },
      
      fontFamily: {
        // EXACT: hjem.no uses Moderat font family
        sans: ['Moderat', 'Inter', 'system-ui', '-apple-system', 'sans-serif'],
        display: ['Moderat', 'Inter', 'system-ui', 'sans-serif'],
      },
      
      fontSize: {
        // Typography scale (inspired by hjem.no)
        'xs': ['0.75rem', { lineHeight: '1rem' }],
        'sm': ['0.875rem', { lineHeight: '1.25rem' }],
        'base': ['1rem', { lineHeight: '1.5rem' }],
        'lg': ['1.125rem', { lineHeight: '1.75rem' }],
        'xl': ['1.25rem', { lineHeight: '1.75rem' }],
        '2xl': ['1.5rem', { lineHeight: '2rem' }],
        '3xl': ['1.875rem', { lineHeight: '2.25rem' }],
        '4xl': ['2.25rem', { lineHeight: '2.5rem' }],
        '5xl': ['3rem', { lineHeight: '1.2' }],
      },
      
      spacing: {
        // Consistent spacing scale
        '128': '32rem',
        '144': '36rem',
      },
      
      borderRadius: {
        // EXACT: hjem.no uses 25px for primary buttons, 100px for pills
        'hjem': '25px',           // --primary-radius: Main buttons
        'hjem-pill': '100px',     // --secondary-radius: Pill shapes
        'DEFAULT': '0.5rem',
        'sm': '0.375rem',
        'md': '0.5rem',
        'lg': '0.75rem',
        'xl': '1rem',
        '2xl': '1.5rem',
      },
      
      boxShadow: {
        // Subtle shadows for depth
        'sm': '0 1px 2px 0 rgb(0 0 0 / 0.05)',
        'DEFAULT': '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)',
        'md': '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
        'lg': '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
        'card': '0 2px 8px rgba(0, 0, 0, 0.08)',
      },
    },
  },
  
  plugins: [
    require('daisyui'),
  ],
  
  daisyui: {
    themes: [
      {
        bimverdi: {
          // EXACT HJEM.NO COLOR SCHEME (November 2024)
          // Primary: Warm orange (hjem.no signature color)
          "primary": "#FF8B5E",           // --tertiary (warm, inviting orange)
          "primary-focus": "#E67A4E",     // Darker orange
          "primary-content": "#FFFFFF",   // White text on orange
          
          // Secondary: Purple for innovation
          "secondary": "#5E36FE",         // --secondary-100 (vibrant purple)
          "secondary-focus": "#4E26DE",   // Darker purple
          "secondary-content": "#FFFFFF", // White text on purple
          
          // Accent: Alert/warning yellow
          "accent": "#FFC845",            // --alert (bright yellow)
          "accent-focus": "#E6B33E",
          "accent-content": "#0F0F0F",    // Dark text on yellow
          
          // Neutral: Dark gray/black
          "neutral": "#383838",           // --primary-variant
          "neutral-focus": "#0F0F0F",     // --primary (main black)
          "neutral-content": "#FFFFFF",
          
          // Base: Backgrounds (warm beige palette)
          "base-100": "#FFFFFF",          // White
          "base-200": "#F7F5EF",          // --surface-dim (warm beige)
          "base-300": "#EFE9DE",          // --state-hover (darker beige)
          "base-content": "#0F0F0F",      // --primary (main text black)
          
          // State colors (from hjem.no CSS variables)
          "info": "#005898",              // --information (blue)
          "success": "#B3DB87",           // --success (green)
          "warning": "#FFC845",           // --alert (yellow)
          "error": "#772015",             // --error (red/brown)
          
          // Border and component styling (matching hjem.no)
          "--rounded-box": "0.75rem",     // Card border radius
          "--rounded-btn": "25px",        // --primary-radius: Button radius
          "--rounded-badge": "100px",     // --secondary-radius: Pill shapes
          
          "--animation-btn": "0.25s",     // Button animation
          "--animation-input": "0.2s",    // Input animation
          
          "--btn-text-case": "none",      // Button text (normal case)
          "--border-btn": "2px",          // Button border width
          "--tab-border": "1px",          // Tab border width
        },
      },
    ],
    
    // Additional daisyUI config
    styled: true,           // Include daisyUI colors and design
    base: true,             // Apply base styles
    utils: true,            // Add utility classes
    logs: true,             // Show logs in console
    rtl: false,             // Disable right-to-left
    prefix: "",             // No prefix for daisyUI classes
    darkTheme: "dark",      // Dark theme (optional, for future)
  },
}
