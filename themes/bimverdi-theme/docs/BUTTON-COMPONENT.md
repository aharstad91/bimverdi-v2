# BIMVerdi Button Component

**Last updated:** 2026-01-12  
**Location:** `parts/components/button.php`

## Overview

The BIMVerdi button component provides consistent button styling based on the Figma design system. It supports two main variants that match the Figma specification:

- **Primary**: Filled black (`#1A1A1A`) background with white text
- **Secondary**: Outline with black border and black text

## Design Specifications (from Figma)

- **Height:** 36px (medium), 28px (small), 44px (large)
- **Border Radius:** 8px
- **Font:** Inter Medium, 14px, line-height 20px, letter-spacing -0.15px
- **Icon Size:** 16px (positioned left or right)
- **Gap:** 8px between icon and text

## Usage

### PHP Function

```php
<?php
// Simple primary button
bimverdi_button([
    'text'    => 'Lagre',
    'variant' => 'primary',
    'href'    => '/save/'
]);

// Secondary button with icon
bimverdi_button([
    'text'    => 'Rediger',
    'variant' => 'secondary',
    'icon'    => 'square-pen',
    'href'    => '/edit/'
]);

// Submit button
bimverdi_button([
    'text'    => 'Send inn',
    'variant' => 'primary',
    'type'    => 'submit',
    'icon'    => 'check'
]);

// Full-width button
bimverdi_button([
    'text'       => 'Registrer deg',
    'variant'    => 'primary',
    'href'       => '/register/',
    'full_width' => true,
    'size'       => 'large'
]);

// Icon on the right
bimverdi_button([
    'text'          => 'Les mer',
    'variant'       => 'secondary',
    'icon'          => 'arrow-right',
    'icon_position' => 'right',
    'href'          => '/article/'
]);
?>
```

### Parameters

| Parameter       | Type    | Default   | Description                                      |
|-----------------|---------|-----------|--------------------------------------------------|
| `text`          | string  | `''`      | Button label text                                |
| `variant`       | string  | `primary` | `primary`, `secondary`, `tertiary`, `danger`    |
| `size`          | string  | `medium`  | `small`, `medium`, `large`                       |
| `icon`          | string  | `null`    | Lucide icon name (see list below)               |
| `icon_position` | string  | `left`    | `left` or `right`                               |
| `href`          | string  | `null`    | URL for link button (renders as `<a>`)          |
| `type`          | string  | `button`  | `button`, `submit`, `reset` for `<button>`      |
| `disabled`      | bool    | `false`   | Disable the button                               |
| `class`         | string  | `''`      | Additional CSS classes                           |
| `id`            | string  | `''`      | Element ID                                       |
| `onclick`       | string  | `''`      | JavaScript onclick handler                       |
| `target`        | string  | `''`      | Link target (`_blank`, etc.)                    |
| `full_width`    | bool    | `false`   | Make button 100% width                          |

## Available Icons

The component includes a curated set of Lucide icons. Here are the most common:

### Navigation & Actions
- `plus`, `x`, `check`
- `arrow-right`, `arrow-left`
- `chevron-right`, `chevron-left`, `chevron-down`, `chevron-up`
- `external-link`, `download`, `upload`
- `square-pen`, `pencil` (edit icons)
- `shield`, `shield-check`

### Content & Data
- `eye`, `eye-off`
- `copy`, `trash-2`, `save`
- `settings`, `search`, `filter`

### Objects
- `wrench`, `building-2`
- `user`, `users`
- `file-text`, `lightbulb`
- `calendar`, `mail`, `phone`, `globe`, `link`

### Status
- `info`, `alert-circle`, `check-circle`, `x-circle`, `loader`

### Social & Layout
- `linkedin`, `share-2`
- `layout-dashboard`, `menu`, `more-vertical`, `more-horizontal`
- `log-out`

## Icon Helper Function

For standalone icons (not in buttons), use:

```php
<?php
// Get SVG markup
echo bimverdi_icon('wrench', 20);

// With custom class
echo bimverdi_icon('user', 16, 'my-custom-class');
?>
```

## CSS Classes

The component uses these CSS classes (defined in `inc/design-system.php`):

| Class               | Description                          |
|---------------------|--------------------------------------|
| `.bv-btn`           | Base button styles                  |
| `.bv-btn--primary`  | Filled black background             |
| `.bv-btn--secondary`| Outline with black border           |
| `.bv-btn--tertiary` | Ghost button (no border)            |
| `.bv-btn--danger`   | Red destructive action              |
| `.bv-btn--small`    | 28px height                         |
| `.bv-btn--medium`   | 36px height (default)               |
| `.bv-btn--large`    | 44px height                         |
| `.bv-btn--full-width` | 100% width                        |
| `.bv-btn--disabled` | Disabled state                      |
| `.bv-btn__icon`     | Icon container                      |
| `.bv-btn__text`     | Text label                          |

## Integration with Page Header

The `page-header` component uses this button system automatically:

```php
<?php get_template_part('parts/components/page-header', null, [
    'title'       => 'Mine verktøy',
    'description' => 'Oversikt over dine verktøy',
    'actions'     => [
        ['text' => 'Nytt verktøy', 'url' => '/registrer-verktoy/', 'variant' => 'primary', 'icon' => 'plus'],
        ['text' => 'Eksporter', 'url' => '/export/', 'variant' => 'secondary', 'icon' => 'download'],
    ],
]); ?>
```

## Migration from Old Button Styles

### From inline Tailwind classes:

```php
// OLD
<a href="/url/" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-[#1A1A1A] rounded-lg hover:bg-[#333]">
    Button text
</a>

// NEW
<?php bimverdi_button([
    'text'    => 'Button text',
    'variant' => 'primary',
    'href'    => '/url/'
]); ?>
```

### From `wa-button`:

```php
// OLD
<wa-button variant="brand" href="/url/">Button text</wa-button>

// NEW (for BIMVerdi-styled buttons)
<?php bimverdi_button([
    'text'    => 'Button text',
    'variant' => 'primary',
    'href'    => '/url/'
]); ?>
```

### From `btn-hjem-*` classes:

```php
// OLD
<a href="/url/" class="btn btn-hjem-primary">Button text</a>

// NEW
<?php bimverdi_button([
    'text'    => 'Button text',
    'variant' => 'primary',
    'href'    => '/url/'
]); ?>
```

## Accessibility

The component follows accessibility best practices:
- Uses semantic `<button>` or `<a>` elements appropriately
- Includes `aria-disabled="true"` for disabled buttons
- Has visible focus states (`:focus-visible` outline)
- Icons have `aria-hidden="true"` to prevent screen reader noise
- Links opening in new tabs include `rel="noopener noreferrer"`
