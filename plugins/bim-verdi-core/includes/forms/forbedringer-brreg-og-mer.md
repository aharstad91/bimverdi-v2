# Plan: Min Side forbedringer - BRreg, ICS og filtrering

Implementere fire hovedforbedringer: BRreg API-integrasjon for foretaksoppslag, ICS-fil generering for arrangementer, forbedret verktøyfiltrering med multi-valg, og dynamisk avmeldingsfrist.

## Steps

1. **BRreg API-integrasjon** – Opprett mu-plugin (`mu-plugins/bimverdi-brreg-api.php`) med REST endpoint som søker Brønnøysundregisteret. Frontend JS-komponent med autocomplete for org.nr/navn som henter bedriftsnavn, adresse, postnummer, poststed, næringskode(r) og organisasjonsform.

2. **Finn foretak dropdown** – Oppdater `template-minside-registrer-foretak.php` og Gravity Forms handler (`class-foretak-form-handler.php`) med BRreg-søkefelt som auto-fyller skjemaet og validerer at org.nr ikke allerede er registrert.

3. **ICS-fil for arrangementer** – Opprett mu-plugin (`mu-plugins/bimverdi-ics-generator.php`) som genererer ICS-filer ved påmelding. Legg til "Last ned til kalender" knapp i `template-minside-arrangementer.php` og send ICS-vedlegg ved påmeldingsbekreftelse.

4. **Dynamisk avmeldingsfrist** – Oppdater `group_arrangement_info.json` med logikk for ulik frist basert på format: fysisk=48t, digitalt=24t, hybrid=48t. Oppdater templater til å bruke dynamisk beregning.

5. **Verktøy multi-filtrering** – Refaktorer `archive-verktoy.php` med checkbox-baserte filtre for: verktoykategori, temagruppe (via `formaalstema`-feltet), og type_ressurs. Implementer AJAX-filtrering for bedre UX.

6. **Inviter kolleger forbedring** – Gjennomgå eksisterende invitasjonssystem i Min Side og sikre at nye brukere automatisk kobles til riktig foretak ved registrering.

## Beslutninger

1. **BRreg API caching?** ✅ JA - Cache oppslag i transients (15 min) for å redusere API-kall.

2. **ICS-vedlegg i e-post?** ✅ BEGGE - Send ICS som vedlegg i bekreftelsesmail OG tilby nedlastningslenke i Min Side.

3. **Næringskode-visning?** ❌ NEI - Brukes kun internt for oversikt, lagres ikke på foretaksprofil.

---

## Research Findings

### BRreg API (Enhetsregisteret)

**Base URL:** `https://data.brreg.no/enhetsregisteret`

| Endpoint | Description |
|----------|-------------|
| `GET /api/enheter` | Search for units (companies) |
| `GET /api/enheter/{orgnr}` | Get specific company by org number |
| `GET /api/underenheter` | Search sub-units |

**Data Available Per Company:**
- Organisasjonsnummer (9-digit ID)
- Navn (Company name)
- Organisasjonsform (AS, ENK, NUF, etc.)
- Forretningsadresse (Business address)
- Postadresse (Postal address)
- Næringskode (Industry codes - NACE)
- Kommune (Municipality)
- Registreringsdato
- Epost/Telefon (as of Oct 2024)
- Konkurs/Avvikling status

**Authentication:** No authentication required for public API

**Search Parameters:**
- `navn` - Search by company name
- `organisasjonsnummer` - Filter by org number
- `kommunenummer` - Filter by municipality
- `naeringskode` - Filter by industry code

**Example:**
```
GET https://data.brreg.no/enhetsregisteret/api/enheter?navn=BIM
```

### Current Implementation Status

| Feature | Status | Key Files |
|---------|--------|-----------|
| BRreg API | ❌ Not integrated | None |
| Finn foretak | Manual entry only | `class-foretak-form-handler.php` |
| Arrangement | ✅ Complete | `group_arrangement_info.json`, templates |
| Avmeldingsfrist | ✅ Implemented (48h hardcoded) | Uses 48h before event logic |
| ICS/Calendar | ❌ Not implemented | - |
| Verktøy filtering | Partial | `archive-verktoy.php` |
| Temagruppe on verktøy | Uses `formaalstema` field | Not taxonomy |
| Mine prosjektidéer | ✅ Complete | `template-minside-prosjektideer.php` |

### Existing ACF Fields

**Foretak (`group_foretak_info.json`):**
- `organisasjonsnummer` (text, 9 digits, required)
- `bedriftsnavn` (text, required)
- `bedriftsbeskrivelse` (textarea)
- `adresse`, `postnummer`, `poststed`
- `kontakt_epost`, `telefon`, `webside`

**Arrangement (`group_arrangement_info.json`):**
- `arrangement_dato` (date_picker, required)
- `tidspunkt_start`, `tidspunkt_slutt` (time_picker)
- `pamelding_frist` (date_picker) - "Siste dag for på-/avmelding"
- `arrangement_format` (button_group: fysisk/digitalt/hybrid)
- `fysisk_adresse`, `motelenke` (conditional)
- `maks_deltakere`

**Verktøy (`group_verktoy_info.json`):**
- `formaalstema` (radio: ByggesaksBIM, ProsjektBIM, EiendomsBIM, MiljøBIM, SirkBIM, Opplæring, Annet)
- `type_ressurs` (radio: Programvare, Standard, Metodikk, etc.)
- Uses `verktoykategori` taxonomy
