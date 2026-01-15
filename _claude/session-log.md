# BIM Verdi v2 - Session Log

> **Delt kontekstfil mellom Claude Chat og Claude Code**
> Oppdateres løpende av begge parter for å holde synk.

---

## Sist oppdatert
- **Når:** 2026-01-15 kl ettermiddag
- **Av:** Claude Code
- **Hva:** Utvidet single-foretak.php med koblede entiteter (Trello XjGmJjRJ)

---

## Aktiv kontekst

### Nåværende fokus
- Trello-kort "Deltakerprofil - vis koblede entiteter" implementert
- Grid-basert kortvisning for verktøy
- Alle seksjoner (Temagrupper, Artikler, Kunnskapskilder) vises alltid

### Siste endringer
- `single-foretak.php` utvidet med grid-layout og inviterende empty states
- Fikset ACF location bug (`medlemsbedrift` → `foretak`)
- Oppdatert GIT-WORKFLOW.md med tydeligere push-regel

### Åpne spørsmål / blokkere
- Ingen

### Viktig å huske
- MVP-lansering: Januar 2025
- Budsjett: 100.000 NOK
- 6 temagrupper: ByggesaksBIM, ProsjektBIM, EiendomsBIM, MiljøBIM, SirkBIM, BIMtech

---

## Changelog

### 2026-01-15

#### [Code] - Deltakerprofil: Vis koblede entiteter (Trello XjGmJjRJ)
- **Trello-kort:** https://trello.com/c/XjGmJjRJ
- **Implementert:**
  - Grid-basert kortvisning for Verktøy (2-kolonner, kort med ikon/tags/footer)
  - Temagrupper-seksjon med inviterende empty state
  - Artikler-seksjon vises alltid (med empty state hvis tom)
  - Kunnskapskilder-seksjon vises alltid (med empty state hvis tom)
- **Bug-fiks:** ACF feltgruppe `group_medlemsbedrift_info` hadde feil location (`medlemsbedrift` → `foretak`)
- **Oppdatert .gitignore:** `!plugins/bim-verdi-core/**` for å inkludere plugin-filer
- **Dokumentasjon:** GIT-WORKFLOW.md oppdatert med strengere push-regel (ALDRI push uten eksplisitt tillatelse)
- **Filer endret:**
  - `themes/bimverdi-theme/single-foretak.php` (major UI overhaul)
  - `plugins/bim-verdi-core/acf-json/group_medlemsbedrift_info.json`
  - `.gitignore`
  - `.claude/GIT-WORKFLOW.md`
- **Note:** Verktøy-query viser 9 resultater som er korrekt - databasen har duplikater (f.eks. "Autodesk AEC Collection" finnes flere ganger)

---

### 2026-01-13

#### [Code] 16:30-17:30 - Deltakere: Arkiv og singulær side FULLFØRT ✅
- Trello-kort: https://trello.com/c/u1KIqXDp
- **Redesignet archive-foretak.php:**
  - Endret bakgrunn fra `bg-[#F7F5EF]` til `bg-[#FAFAF8]`
  - Standardisert max-width til `max-w-7xl` (fra `max-w-[1280px]`)
  - Forbedret kortdesign:
    - Hele kortet klikkbart (`<a>` tag)
    - Lagt til beskrivelse-snippet (15 ord)
    - Viser temagrupper (grønn tag) i tillegg til bransjekategorier
    - Border-t divider under "Se profil"
    - Hover-effekter på tittel og chevron
- **Redesignet single-foretak.php:**
  - Fullstendig rewrite basert på single-verktoy.php mønsteret
  - Fjernet hvite cards → borderless sections med `border-t border-[#E5E0D8] pt-10`
  - Sidebar: `bg-[#F7F5EF] rounded-lg p-5` (beige boxes)
  - Seksjoner: Om foretaket, Verktøy, Artikler, Kunnskapskilder, Ansatte
  - Definition lists med `divide-y divide-[#E5E0D8]`
- **Testet via Chrome MCP:** Begge sider fungerer og følger UI-CONTRACT.md
- **Filer endret:**
  - `themes/bimverdi-theme/archive-foretak.php`
  - `themes/bimverdi-theme/single-foretak.php`

#### [Code] 15:30-16:00 - Git-opprydding og CPT-fiks
- **Fikset manglende CPTs:** `theme_group` og `artikkel` var forsvunnet fra `class-post-types.php`
  - Hentet tilbake kode fra git-historikk (commits 10489b3, 58aea99)
  - Testet via Chrome MCP - `/temagruppe/bygg-chat/` fungerer
- **Oppdatert .gitignore:** Endret fra `plugins/` til `plugins/*` med unntak for `bim-verdi-core/`
  - Egenutviklede plugins trackes nå i git
  - 3.part plugins installeres via admin
- **Opprettet `.claude/GIT-WORKFLOW.md`:** Git best practices for alle prosjekter
  - Conventional commits på engelsk (feat:, fix:, refactor:, etc.)
  - Spør før push
- **Opprettet `.claude/local-dev.json`:** Lagrer WP admin credentials for Chrome MCP (gitignored)
- **Pushet til GitHub:** Commit `a0f36c3` med alle fikser

#### [Code] 14:00-14:15 - Kunnskapskilder-flyt TESTET ✅
- Testet full flyt via Chrome DevTools MCP:
  1. Logget inn som "Claude AI" bruker
  2. Navigerte til Min Side → Kunnskapskilder → Registrer
  3. Fylte ut og submittet Gravity Form
  4. Verifiserte CPT post opprettet med status "Draft"
  5. Admin publiserte posten
  6. Verifiserte publisert kilde vises på Min Side med status "Publisert"
- **Fiks 1:** Endret consent-felt til checkbox-felt i `class-kunnskapskilde-form-setup.php`
  - GF consent-felt krever kompleks konfigurasjon, ikke egnet for programmatisk oppretting
  - Checkbox med `inputs`-array fungerer korrekt
- **Fiks 2:** Fikset dobbel URL-bug i `parts/components/page-header.php`
  - `home_url()` ble kalt på URL som allerede var full URL fra `bimverdi_minside_url()`
  - Lagt til sjekk for om URL starter med http/https før wrapping

#### [Code] 13:00-13:30 - Forbedringer CPT Kunnskapskilder
- Gravity Form opprettes nå **automatisk** via `GFAPI::add_form()`
  - Ny fil: `setup/class-kunnskapskilde-form-setup.php`
  - Form ID bestemmes dynamisk, ikke hardkodet
  - Field map lagres i options for handler
- Handler oppdatert til å bruke dynamiske Field IDs via `inputName`
- Endret fra page template til **archive-template** (`archive-kunnskapskilde.php`)
  - URL automatisk via CPT rewrite: `/kunnskapskilder`
  - Ingen WordPress-side nødvendig
  - Slettet `templates/public/template-kunnskapskildekatalog.php`

#### [Code] 11:30-13:00 - CPT Kunnskapskilder FULLFØRT ✅
- Trello-kort: https://trello.com/c/D5HI3Z4i
- **Implementert:**
  - CPT registrering i `class-post-types.php`
  - Taxonomy `kunnskapskildekategori` i `class-taxonomies.php`
  - Lagt til `kunnskapskilde` i temagruppe-taxonomy
  - ACF feltgruppe: `acf-json/group_kunnskapskilde_info.json`
  - Gravity Forms handler: `handlers/class-kunnskapskilde-form-handler.php`
    - URL-duplikatvalidering implementert
    - Registrert i `class-gravity-forms-manager.php`
  - Min Side routes og navigasjon i `minside-helpers.php`
  - Min Side templates:
    - `parts/minside/kunnskapskilder-list.php`
    - `parts/minside/kunnskapskilder-registrer.php`
    - `parts/minside/kunnskapskilder-rediger.php`
  - Single-template: `single-kunnskapskilde.php`
  - Archive-template: `archive-kunnskapskilde.php`
- **Oppsett etter deploy:**
  - Flush rewrite rules (Innstillinger → Permalenker → Lagre)

#### [Code] 09:00-11:00 - Trello-integrasjon og workflow
- Satt opp Trello MCP med API-nøkler
- Analysert alle kort i kolonne 1-5
- Kartlagt kodebase mot Trello-oppgaver (statusrapport)
- Forenklet Trello-struktur: Innboks → Backlog → Prioritert → I arbeid
- Opprettet `FEATURE-REQUEST-GUIDE.md` for Bård
- Filer: `FEATURE-REQUEST-GUIDE.md`

### 2025-01-13

#### [Code] 23:20 - SessionStart hook satt opp
- Opprettet `.claude/hooks/session-init.sh` som automatisk injiserer SESSION-LOG.md ved sesjonstart
- Opprettet `.claude/settings.json` med hook-konfigurasjon
- Symlinket hooks til worktree for konsistent oppførsel
- Filer: `.claude/hooks/session-init.sh`, `.claude/settings.json`

#### [Code] 22:00-23:00 - Chrome MCP testing
- Konfigurert Chrome DevTools MCP i Claude Desktop
- Testet full verktøy-registreringsflyt via browser automation
- Opprettet testverktøy "Claude BIM Assistant" via Min Side
- **Status:** Chrome MCP fungerer, men krever manuell bekreftelse i Desktop app

#### [Chat] 14:45 - Fullført workflow-oppsett
- Opprettet `CLAUDE-CODE-INSTRUCTIONS.md` med detaljerte instruksjoner
- Oppdatert `CLAUDE.md` med referanse til nye filer

#### [Chat] 14:30 - Opprettet SESSION-LOG
- Opprettet denne filen for delt kontekst
- Definert workflow for oppdatering

---

## Neste steg (prioritert)

1. [x] ~~SessionStart hook for automatisk lesing~~
2. [x] ~~Trello MCP-integrasjon~~
3. [x] ~~Feature request workflow for Bård~~
4. [x] ~~Implementer CPT Kunnskapskilder~~ ✅
5. [x] ~~Programmatisk GF-oppretting~~ ✅
6. [x] ~~Test kunnskapskilder-flyt~~ ✅
7. [x] ~~Deltakere: Arkiv og singulær side~~ ✅
8. [ ] Fortsett med neste prioriterte Trello-kort

---

## Relevante filer

| Fil | Beskrivelse |
|-----|-------------|
| `CLAUDE.md` | Prosjektdokumentasjon for Claude |
| `CLAUDE-CODE-INSTRUCTIONS.md` | Workflow-instruksjoner for Claude Code |
| `FEATURE-REQUEST-GUIDE.md` | Guide for Bårds feature requests |
| `.claude/settings.json` | Hook-konfigurasjon |
| `.claude/hooks/session-init.sh` | SessionStart hook script |
| `UI-CONTRACT.md` | Design system regler |
| `.claude/GIT-WORKFLOW.md` | Git best practices |
| `.claude/local-dev.json` | WP admin credentials (gitignored) |

---

## Notater

- Trello MCP: Board ID `5baa05429aab173fc9448b9f` (BIM Verdi v2)
- **Trello workflow:** Innboks → Backlog → I arbeid → Validering → Arkivert
- Validering-kolonnen (ID: `6966360265b3f446325436ec`) brukes til testing/QA før godkjenning
- Feature requests: Bård bruker Claude Chat med prompt fra FEATURE-REQUEST-GUIDE.md
- SessionStart hook injiserer denne filen automatisk ved ny sesjon

