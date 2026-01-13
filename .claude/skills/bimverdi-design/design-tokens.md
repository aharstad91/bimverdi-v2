# BIM Verdi Design Tokens

Quick reference for all design tokens. Copy-paste ready.

## CSS Variables

```css
:root {
  /* Colors - Primary */
  --color-primary: #FF8B5E;
  --color-primary-dark: #E67A4E;
  --color-primary-light: #FFBFA8;

  /* Colors - Neutrals */
  --color-black: #1A1A1A;
  --color-gray-dark: #383838;
  --color-gray-medium: #888888;
  --color-gray-light: #D1D1D1;
  --color-white: #FFFFFF;

  /* Colors - Background */
  --color-beige: #F7F5EF;
  --color-beige-dark: #EFE9DE;

  /* Colors - State */
  --color-success: #B3DB87;
  --color-error: #772015;
  --color-alert: #FFC845;
  --color-info: #005898;

  /* Spacing (8px scale) */
  --spacing-xs: 0.5rem;   /* 8px */
  --spacing-sm: 1rem;     /* 16px */
  --spacing-md: 1.5rem;   /* 24px */
  --spacing-lg: 2rem;     /* 32px */
  --spacing-xl: 3rem;     /* 48px */
  --spacing-2xl: 4rem;    /* 64px */

  /* Border Radius */
  --radius-sm: 0.375rem;  /* 6px */
  --radius-md: 0.5rem;    /* 8px - buttons */
  --radius-lg: 0.75rem;   /* 12px - cards */

  /* Typography */
  --font-family: 'Moderat', 'Inter', system-ui, sans-serif;
  --line-height-tight: 1.2;
  --line-height-normal: 1.5;
  --line-height-relaxed: 1.75;
}
```

## Button Classes

```html
<!-- Variants -->
<a class="bv-btn bv-btn--primary">Primary</a>
<a class="bv-btn bv-btn--secondary">Secondary</a>
<a class="bv-btn bv-btn--tertiary">Tertiary</a>
<a class="bv-btn bv-btn--danger">Danger</a>

<!-- Sizes -->
<a class="bv-btn bv-btn--primary bv-btn--small">Small (28px)</a>
<a class="bv-btn bv-btn--primary bv-btn--medium">Medium (36px)</a>
<a class="bv-btn bv-btn--primary bv-btn--large">Large (44px)</a>

<!-- With icon -->
<a class="bv-btn bv-btn--primary">
  <span class="bv-btn__icon"><svg>...</svg></span>
  <span class="bv-btn__text">Label</span>
</a>
```

## Common Lucide Icons (SVG)

```html
<!-- Plus (for "Legg til") -->
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>

<!-- Chevron Right -->
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>

<!-- Building 2 (Foretak) -->
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg>

<!-- User -->
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>

<!-- Wrench (Verktoy) -->
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>

<!-- Calendar -->
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>

<!-- Pencil (Edit) -->
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>

<!-- External Link -->
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
```

## Layout Widths

```css
/* Standard content */
.bv-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 var(--spacing-md);
}

/* Form layout */
.bv-form-layout {
  max-width: 960px;
  margin: 0 auto;
}
```

## Section Pattern

```html
<section class="bv-section">
  <h2 class="bv-section__title">Seksjonsoverskrift</h2>
  <div class="bv-section__content">
    <!-- Innhold -->
  </div>
</section>
<hr class="bv-divider">
```

## Definition List Pattern

```html
<dl class="bv-definition-list">
  <div class="bv-definition-list__item">
    <dt>Etikett</dt>
    <dd>Verdi</dd>
  </div>
  <div class="bv-definition-list__item">
    <dt>Etikett 2</dt>
    <dd>Verdi 2</dd>
  </div>
</dl>
```

## Empty State

```html
<div class="bv-empty-state">
  <p class="bv-empty-state__text">Ingen verktoy registrert enna.</p>
  <a href="#" class="bv-btn bv-btn--primary">
    <span class="bv-btn__icon"><!-- plus icon --></span>
    <span class="bv-btn__text">Legg til verktoy</span>
  </a>
</div>
```
