# Copilot Instructions for BIM Verdi Project

## Project Overview
BIM Verdi MVP - WordPress medlemsportal for Norwegian BIM industry network.

**Stack:** WordPress + ACF Pro + Gravity Forms Pro + Web Awesome  
**Hosting:** Servebolt (standard PHP only)  
**Timeline:** Januar 2025 launch

---

## 🎯 Communication Style: Critical Advisor

Act as a calm advisor.

**Do NOT:**
- Oversell solutions
- Sugarcoat problems

**DO:**
2. Point out logical gaps
3. Highlight hidden risks and trade-offs
4. Say clearly what is unclear or unsupported
5. Recommend best practices based on experience
---

## 🔑 WordPress Admin Access

**URL:** http://localhost:8888/bimverdi-v2/wp-admin  
**Brukernavn:** Claude AI  
**Passord:** r&Tb6f6*tEv3C9MkAh

Bruk Chrome MCP til å logge inn ved behov for å teste eller administrere WordPress.

## Icon Framework: Lucide Icons

**Standard:** All icons in the project use **Lucide Icons** (https://lucide.dev).

Implementation:
- **WordPress:** Inline SVG from lucide-react package or lucide CDN
- **React prototype:** `lucide-react` package
- **HTML/CSS:** Inline SVG or external SVG files

Common icons used in BIM Verdi:
- Dashboard: `layout-dashboard`
- Tools/Wrench: `wrench`
- Articles: `file-text` or `file-lines`
- Ideas: `lightbulb`
- Events: `calendar`
- User/Account: `user`
- Company: `building-2`
- Settings: `settings`
- Chevron: `chevron-down` / `chevron-right`
- Arrow: `arrow-right` / `arrow-left`
- Search: `search`
- More options: `more-vertical`

---

## Technical Architecture

### Custom Post Types (CPT)
**IMPORTANT:** Use constants from `class-content-types.php` instead of hardcoded strings.

| Norwegian Name | Constant | Internal Slug | English URL |
|----------------|----------|---------------|-------------|
| Medlemsbedrift | `BV_CPT_COMPANY` | `foretak` | `/companies/` |
| Verktøy | `BV_CPT_TOOL` | `verktoy` | `/tools/` |
| Arrangement | `BV_CPT_EVENT` | `arrangement` | `/events/` |
| Påmelding | `BV_CPT_REGISTRATION` | `pamelding` | (private) |
| Prosjektidé | `BV_CPT_IDEA` | `case` | (private) |
| Prosjekt | `BV_CPT_PROJECT` | `prosjekt` | `/projects/` |
| Temagruppe | `BV_CPT_THEME_GROUP` | `theme_group` | `/theme-groups/` |
| Artikkel | `BV_CPT_ARTICLE` | `artikkel` | `/articles/` |

### Taxonomies
**Use taxonomy constants for all references:**

| Norwegian Name | Constant | Internal Slug |
|----------------|----------|---------------|
| Bransjekategori | `BV_TAX_INDUSTRY` | `bransjekategori` |
| Kundetype | `BV_TAX_CUSTOMER_TYPE` | `kundetype` |
| Temagruppe | `BV_TAX_TOPIC` | `temagruppe` |
| Verktøykategori | `BV_TAX_TOOL_CATEGORY` | `verktoykategori` |
| Arrangementstype | `BV_TAX_EVENT_TYPE` | `arrangementstype` |
| Artikkelkategori | `BV_TAX_ARTICLE_CATEGORY` | `artikkelkategori` |

### Helper Functions
```php
// Get CPT slug by semantic name
bv_get_cpt('company');  // Returns 'foretak'

// Get taxonomy slug by semantic name  
bv_get_tax('industry'); // Returns 'bransjekategori'

// Or use constants directly
$query = new WP_Query(['post_type' => BV_CPT_COMPANY]);
```

See [INTERNATIONALIZATION.md](../plugins/bim-verdi-core/docs/INTERNATIONALIZATION.md) for full documentation.

### Key Relationships
- User → Medlemsbedrift (1:1 via ACF)
- Medlemsbedrift → Verktøy (1:many)
- Medlemsbedrift → Artikkel (1:many)
- User → Påmelding → Arrangement (many:many)

---

## Temagrupper (6 groups)
1. ByggesaksBIM - Building permit processes
2. ProsjektBIM - Project phase BIM
3. EiendomsBIM - Property management
4. MiljøBIM - Climate calculations
5. SirkBIM - Circular economy
6. BIMtech - Technology & AI

---

## Code Standards
- Use WordPress coding standards
- Norwegian comments for business logic
- English for code/function names
- Keep MVP approach - simple solutions first
- Use ACF frontend forms for Min Side

---

## 🎨 Design Standards

### ⭐ UI Contract (Normative spec for all HTML/CSS)
**MUST READ:** [UI-CONTRACT.md](../UI-CONTRACT.md)

All UI code must follow the BIMVerdi UI Contract (v1). It defines:
- Core design principles (whitespace, explicit clickability, information ≠ action)
- Layout system & spacing rules
- Visual language (containers, dividers, typography, colors)
- Interaction rules & component contracts
- Page recipes for collections, detail views, and create/edit flows
- AI implementation checklist

**Non-negotiable:** Semantic headings, keyboard navigation, WCAG AA contrast.

---

### Component Framework
**Primary:** Web Awesome (wa-* components)  
**Utility:** Tailwind CSS (for layout/spacing only)

### Web Awesome Components (PREFERRED)
Always use Web Awesome for interactive elements:
- `<wa-button>` - All buttons
- `<wa-card>` - Content containers
- `<wa-badge>` / `<wa-tag>` - Status/labels
- `<wa-alert>` - Notifications
- `<wa-avatar>` - User/company images
- `<wa-tab-group>` - Navigation tabs
- `<wa-icon>` - Icons (Font Awesome via `library="fa"`)

### Tailwind Usage Rules
**DO use for:**
- Layout: `flex`, `grid`, `container`, `gap-*`
- Spacing: `p-*`, `m-*`, `space-y-*`
- Responsive: `md:`, `lg:` breakpoints
- Text: `text-sm`, `font-bold`, `text-gray-600`

**DON'T use:**
- ❌ `shadow-*` - Use Web Awesome card shadows instead
- ❌ `rounded-*` for buttons - Use `<wa-button>`
- ❌ `bg-*` for interactive elements - Use wa-* components
- ❌ Custom button styles - Use `<wa-button variant="...">`

### Color Palette
```css
/* Brand colors - use via Tailwind or CSS vars */
--bim-orange: #F97316;      /* Primary action */
--bim-purple: #7C3AED;      /* Secondary/accent */
--bim-beige: #FDF6E3;       /* Background */
--bim-black: #1F2937;       /* Text */
```

### Shadow Policy
- **Cards:** Let `<wa-card>` handle shadows automatically
- **Hover effects:** Use `hover:shadow-lg` sparingly, prefer `transition-colors`
- **No manual shadows** on buttons, badges, or form elements

### Button Variants
```html
<wa-button variant="brand">Primary action</wa-button>
<wa-button variant="success">Positive action</wa-button>
<wa-button variant="warning">Caution action</wa-button>
<wa-button variant="danger">Destructive action</wa-button>
<wa-button variant="neutral" outline>Secondary action</wa-button>
```

---

## 📋 Gravity Forms Policy

### ⚠️ VIKTIG: Alle skjemaer skal bruke Gravity Forms
**IKKE lag custom PHP-skjemaer.** Alle bruker-facing skjemaer skal implementeres med Gravity Forms Pro.

Fordeler:
- Konsistent styling og oppførsel
- Innebygd validering og spam-beskyttelse
- Enkel administrasjon i WordPress admin
- Hooks for automatisering (gform_after_submission, etc.)

### Naming Convention

All Gravity Forms skal følge et prefix-system for enkel kategorisering:

### Prefixes
| Prefix | Bruksområde | Eksempel |
|--------|-------------|----------|
| `[Bruker]` | Skjemaer synlig på Min Side (bruker-facing) | `[Bruker] - Registrering av verktøy` |
| `[System]` | Backend/automatiske prosesser, ikke bruker-facing | `[System] - Registrering Steg 1 (Email)` |
| `[Admin]` | Kun for admin-bruk i backend | `[Admin] - Manuell brukeroppretting` |
| `[Public]` | Åpne skjemaer (ikke innlogget) | `[Public] - Kontaktskjema` |

### Aktive skjemaer (per desember 2024)
| ID | Navn | Type |
|:--:|:-----|:-----|
| 1 | `[Bruker] - Registrering av verktøy` | Bruker |
| 2 | `[Bruker] - Registrering av foretak` | Bruker |
| 4 | `[Bruker] - Redigering av profil` | Bruker |
| 5 | `[System] - Registrering Steg 1 (Email)` | System |
| 6 | `[System] - Registrering Steg 2 (Verifisering)` | System |
| 7 | `[System] - Redigering av foretak` | System |

> **Merk:** Skjema ID 3 ("Registrering av bruker") er legacy og ligger i papirkurv.

---

## File Locations
- Theme: `wp-content/themes/bimverdi-theme/`
- Shared Context: `wp-content/shared-context/` (gitignored)
- Must-use plugins: `wp-content/mu-plugins/`
