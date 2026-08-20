# AEC AI Hub-synk

Importerer verktøy fra Stjepan Mikulićs **AEC AI Hub** inn i den eksisterende
`verktoy`-CPT-katalogen. Modulen ligger i `bim-verdi-core` (ikke en frittstående
`plugins/`-plugin) fordi **kun `mu-plugins/**` + `plugins/bim-verdi-core/**` er git-sporet** —
en separat plugin ville hatt samme single-point-of-failure som doffin-pluginen (gitignore-et,
kun på server, ingen backup). Alt her er versjonskontrollert.

> **Status:** Trinn 1 (fixture) + Trinn 2 (live Notion-kilde, ukentlig cron) implementert.
> Planer: `docs/plans/2026-06-03-002-feat-aec-ai-hub-sync-trinn1-plan.md` og
> `docs/plans/2026-08-19-001-feat-aec-hub-live-synk-plan.md`.

---

## To kilder — samme rørlegging

| | Fixture (Trinn 1) | Live (Trinn 2) |
|---|---|---|
| Hvor | `data/aec-ai-hub-tools.json` (committet juni-2026-snapshot) | `aiinaec.notion.site` v3-endepunkter |
| Rader | 475, hvorav 238 Champions | 1921 (per 19.08.2026) |
| Champion-kolonne | finnes → `champion_gate = true` | **fjernet av Stefan** → `champion_gate = false`, alt importeres |
| Slås på med | default | `wp bimverdi aihub-source live`, eller `--live` per kjøring |

Kildevalget avgjøres av `BV_AIHUB_Tool_Source::is_live()`: konstanten `BV_AIHUB_LIVE`
(wp-config/plugin-default), option-en `bv_aihub_live_source`, og filteret
`bimverdi_aihub_live` (CLI-flagg/test) — i den presedensrekkefølgen.

**Champion-gaten er kildeeid.** `Tool_Source` setter `champion_gate` i fetch-kontrakten,
orkestratoren videresender den til `Tool_Upserter::champion_filter_and_dedup($tools, $gate)`.
Default = PÅ, slik at fixture-oppførselen (og selftestens 238/236-assertions) er uendret.

### Live-kilden: tre harde læringer

Verifisert mot API-et 18.–19.08.2026. Ikke «forenkle» bort — hver av dem gir stille datatap:

1. **Nettleser-UA er påkrevd.** Notion svarer 403 på python-urllib/curl-default.
2. **recordMap-en kappes på ~1000 blokker per kall.** Et ufiltrert kall mot 1921 rader gir
   1921 `blockIds` men bare 1000 oppløste rader — resten forsvinner uten feilmelding.
   Derfor partisjoneres henting på `Category` (største bøtte i dag: Platform = 737), med
   trunkeringsvakt per partisjon og sub-partisjonering på `AI-Driven` som reserve.
3. **`result.sizeHint` er hele collection-størrelsen**, ikke antall treff for filteret.
   Ubrukelig som treffteller, men perfekt totalorakel: union av partisjoner < `sizeHint`
   → hard abort (ellers ville orphan-ryddingen avpublisert differansen).

Property-ID-er (`Bt>z`, `iqZN`, …) hardkodes **aldri** — de slås opp fra schemaet på navn
(`Name`, `URL`, `Category`, `Description`, `AI-Driven`). Mangler en påkrevd kolonne →
abort med hvilke kolonner som faktisk finnes, i stedet for 1900 tomme felt.

### Ukentlig synk

`BV_AIHUB_Cron` (`class-aihub-cron.php`) planlegger `bv_aihub_weekly_sync` på WP-Cron.
**Av som standard** (`bv_aihub_cron_enabled = 0`); slås på med `wp bimverdi aihub-cron enable`.
Rapport-e-post går til én adresse som allowlistes hardt rett før `wp_mail()`
(`BV_AIHUB_Cron::ALLOWED_RECIPIENTS`) — ingen option eller filter kan gjøre en
medlemsadresse til mottaker.

---

## Datakilde

- **Rå kilde:** `data/aec-ai-hub-source-cd1f55e653214763b0bfb544ccc40b94.csv` (475 rader,
  Notion CSV-eksport av DB `cd1f55e6…`). Kolonner: `Name, AI-Driven, Category, Champion,
  Description, URL`.
- **Normalisert fixture:** `data/aec-ai-hub-tools.json` — struktur `{ _meta, tools[] }`,
  full 475-snapshot (IKKE forhåndsfiltrert; importeren velger de 238 Champions).
- **HTTP-blokk:** `data/.htaccess` (`Require all denied`) — kildedata er aldri web-eksponert.
- **Ingen Notion page-id finnes i kilden.** Identitet = normalisert URL (se under).

Hver `tools[]`-rad:
`{ identity_key, name, short_desc, long_desc, url, logo_url, categories[], champion(bool), ai_driven(bool) }`.

Verifiserte tall (deterministisk fra kilden):

| Mål | Antall |
|-----|-------:|
| Verktøy totalt | 475 |
| Champions (`champion=true`) | 238 |
| Champions som også er AI-drevne (`ai_driven=true`) | 176 |
| Champions som IKKE er AI-drevne | 62 |
| Unike Champions etter dedup | 236 |
| Umappbare kategorier | ~54 |
| Rader uten URL | 0 |

---

## Identitet = normalisert URL (host lowercased, PATH BEVART)

Det finnes **ingen Notion page-id** i kilden, så primær identitet er en normalisert URL,
lagret som `_bv_aec_source_key`. Normaliseringen gjøres av den **delte** funksjonen
`bv_aec_normalize_url()` (`helpers.php`) — samme funksjon brukes av fixture-generatoren og
upserteren, ellers lager upserteren duplikater i stedet for å treffe eksisterende post.

Regel:

1. Strip scheme (`https://`, `http://`).
2. Strip ledende `www.`.
3. Drop query (`?…`) + fragment (`#…`).
4. Drop trailing slash.
5. **Lowercase KUN hostname. PATH BEVARES med original case.**

**Hvorfor path bevares (load-bearing):** GitHub-paths er case-sensitive —
`github.com/SHL-Digital-Practice/ai.sthetic` ≠ `…/shl-digital-practice/ai.sthetic`,
og `SpeckleLCA` ≠ `specklelca`. ≥5 distinkte `github.com`-verktøy (≥2 Champions) ville
kollapset til ett enkelt verktøy under blind lowercasing → 238 ville blitt færre.
Fixturens `identity_key` er allerede produsert med denne regelen; en full-fixture
paritets-assert (alle 475 rader: `bv_aec_normalize_url(url) === identity_key`) i selftesten
fanger enhver generator/upserter-divergens deterministisk.

Eksempler:

| Rå URL | `identity_key` |
|--------|----------------|
| `https://www.density.io/` | `density.io` |
| `https://github.com/SHL-Digital-Practice/ai.sthetic` | `github.com/SHL-Digital-Practice/ai.sthetic` |

---

## Skjult post_meta (`_bv_aec_*`)

Alle synk-eide felt er skjult meta med `_bv_`-prefiks. **Synk-eierskap styres av
`_bv_aec_managed`, ALDRI av det synlige `kilde`-ACF-feltet** — en menneske-satt `kilde`
gir ingen synk-eierskap.

| Meta-nøkkel | Verdi / betydning |
|-------------|-------------------|
| `_bv_aec_managed` | `'1'` settes **KUN** på poster upserteren selv INSERTer. Enhver update/term/status/orphan-operasjon krever `_bv_aec_managed='1'` **og** `post_type=verktoy`. |
| `_bv_aec_source_key` | Normalisert URL (host-lowercase, path bevart) = **primær identitet**. Erstatter den tidligere `_bv_aec_notion_id`. |
| `_bv_aec_canonical_url` | Rå-URL fra `url`-feltet, persistert så normaliseringen kan revurderes uten å miste kilden. |
| `_bv_aec_name_key` | Normalisert navn-hash (lowercase, trim, kollaps whitespace, strip parentetiske suffikser, sha1) = **svak sekundær** reconciliation-HINT. Brukes ALDRI som hard merge-nøkkel — ved URL-miss + name_key-treff logges «mulig URL-endring, manuell vurdering», aldri auto-merge. |
| `_bv_aec_ai_driven` | Lagres **alltid eksplisitt** som string `'1'` (176) eller `'0'` (62) på ALLE managed-poster. Gatekeeper for AI-badge. |
| `_bv_aec_source` | «AEC AI Hub by Stjepan Mikulić / aiinaec.com». Settes på ALLE 238. Driver attribusjon OG Kilde-filteret. |
| `_bv_aec_raw_category` | Rå kategoristreng fra kilden (enkeltverdi i denne eksporten). |
| `_bv_aec_synced_at` | Tidsstempel siste synk. |
| `_bv_aec_last_sync_status` | Statusen synken sist satte — synk auto-transisjonerer status KUN når nåværende status == denne (angrer bare egen handling). |
| `_bv_unmapped` | `1` når kildekategorien ikke finnes i Bårds matrise (→ «Andre kategorier»-term). Fortsatt et nyttig datafaktum for rapportering, men ikke lenger en sperre mot publisering. |
| `_bv_orphaned` | `1` når et tidligere managed verktøy ikke lenger finnes i kilden (soft-unpublish til draft, aldri hard-delete). |
| `_bv_aec_manual_override` | Fryser både felt OG status mot videre synk. |

### `ai_driven` (`'1'`/`'0'`) vs `champion` — to separate flagg

- **`champion`** styrer hvilke 238 av 475 som importeres (import-filteret).
- **`ai_driven`** styrer KUN om AI-badgen vises — på de 176 der `_bv_aec_ai_driven='1'`.
  De 62 ikke-AI Champions får full attribusjon, men **ingen AI-badge** (badge på alle ville
  vært uærlig).

**Kritisk PHP-felle:** `get_post_meta($id, $key, true)` returnerer **aldri** PHP-boolean —
`"1"` for lagret `true`, og `""` for både lagret `false` OG fraværende nøkkel. Derfor:

- Lagre alltid eksplisitt `'1'`/`'0'` (string), aldri bool `false`, aldri utelatt.
- Gate badgen på `(string) get_post_meta(...) === '1'` — **aldri** `=== true` (alltid false mot meta-output → badge skjult på alle 176).
- Tre skillbare tilstander: managed AI = `'1'`, managed ikke-AI = `'0'`,
  deltaker-verktøy = nøkkel fraværende (`metadata_exists()` skiller «lagret false» fra «fraværende»).

---

## Dedup (kilden HAR dup-URL-er — men kun 2 av 3 kolliderer etter champion-filter)

Fixturen dedupes **IKKE** (speiler kilden 1:1 = 475). Dedup skjer i upserteren, **etter**
champion-filteret, som deterministisk merge-collapse på `_bv_aec_source_key`:

- **`youai.ai`** (2× Champion+AI, identisk kategori `['Platform']`, ulikt navn) → navnekonflikt;
  velg lengste/mest beskrivende navn («YouAi (MindStudio)») + **logg dedup-warning**.
- **`superhuman.com`** (2× Champion+AI, identiske felt) → tap-fri collapse: behold første.
- **`dronedeploy.com`** (1 Champion, 1 ikke-Champion) → champion-filteret fjerner allerede
  ikke-Champion-raden → **kolliderer IKKE** etter filtrering. Ikke dedup i fixturen.

238 Champions − 2 kolliderende = **236 unike**. Kjøringen skal **fullføre** (ikke abort) —
alle dup-er her er løsbare av regelen.

---

## Kategorisering → temagruppe (utenfor matrisen → «Andre kategorier»)

`wp_set_object_terms('temagruppe', …, append=false)` (gated på `_bv_aec_managed`) er
autoritativ. De 6 gyldige termene: ByggesaksBIM, ProsjektBIM, EiendomsBIM, MiljøBIM,
SirkBIM, BIMtech. **Ingen `formaalstema`-mirror** (det filteret er allerede ødelagt).

Kategorier utenfor Bårds matrise (Assistant, AR/VR/MR, News, Learning) er **aldri termløse**
— de får temagruppe-termen **«Andre kategorier»** + `_bv_unmapped=1` og opprettes som draft.

**Bårds beslutning 20.08.2026:** «Andre kategorier» er en ekte, synlig temagruppe på linje
med de seks, ikke en holdekategori. Den vises som sjuende valg i verktøy-arkivets
temagruppe-fasett (sist, siden den ikke er en merkevare-temagruppe), og utkastene kan
publiseres med `aihub-publish-batch "Andre kategorier" --confirm`. `--alle-mappede` holder
den fortsatt utenfor, så en bred publisering aldri sveiper den med seg utilsiktet.

---

## Publisering = default DRAFT + bulk/batch-godkjenning

`BV_AIHUB_AUTOPUBLISH = false` (hard sikring — importeren setter ALDRI `publish` selv).
De 236 importeres som **draft**. Godkjenning er en separat, manuell bulk/batch-handling:

- **Batch per temagruppe:** publiser f.eks. alle `Design Creation`-utkast samlet etter stikkprøve.
- **Stikkprøve-gate:** verktøyet viser N tilfeldige drafts + totalantall + dedup-warnings +
  unmapped-count, og krever eksplisitt bekreftelse (ikke default-ja).
- **Unmapped** krever remapping til en ekte temagruppe før godkjenning.

> Eksterne verktøy i medlemskatalogen er en ny innholds-proveniens-modell (nå i skala 236)
> → krever en formell **B-beslutning** i `docs/krav/`. Utkast: `claude/bard-context/b-032-utkast-eksternt-kilded-katalog.md`.

---

## WP-CLI-kommandoer

Metoder på `BIM_Verdi_CLI_Commands` (samme `bimverdi`-namespace som foretak-import — ikke et nytt `add_command`):

```bash
# Synk (kilde → managed draft-verktøy). Publiserer ALDRI selv.
wp bimverdi aihub-sync --dry-run           # beregn alt, ingen skriv
wp bimverdi aihub-sync                     # idempotent upsert + orphan-rekonsiliering
wp bimverdi aihub-sync --live --dry-run    # tving live Notion-kilde for denne kjøringen
wp bimverdi aihub-sync --fixture           # tving committet fixture

# Kildevalg (varig per miljø, lagres som option)
wp bimverdi aihub-source                   # vis konstant + option + effektiv kilde
wp bimverdi aihub-source live

# Ukentlig synk (WP-Cron). AV som standard.
wp bimverdi aihub-cron status
wp bimverdi aihub-cron enable
wp bimverdi aihub-cron run                 # kjør nå via cron-kodeveien, inkl. rapport-e-post

# Committet, self-cleaning selftest (G4-paritet, idempotens, deltaker-vakt, AI-flagg '1'/'0',
# dedup, orphan-livssyklus, floor, abort). Exit 0 = grønt. Produksjonstrygg (rører aldri ekte poster).
wp bimverdi aihub-selftest

# Bulk/batch-godkjenning (Decision 6). Publiserer ALDRI umappbare; krever --confirm.
wp bimverdi aihub-publish-batch ProsjektBIM            # stikkprøve + totaler, INGEN skriv
wp bimverdi aihub-publish-batch ProsjektBIM --confirm  # publiser batchen
wp bimverdi aihub-publish-batch --alle-mappede --confirm
```

Eierskaps-identiteter er konfigurerbare (oppretter ALDRI foretak/bruker automatisk):
`BV_AIHUB_AUTHOR_ID` (post_author), `BV_AIHUB_OWNER_FORETAK_ID` (ACF `eier_leverandor`) —
konstant i wp-config eller filtrene `bimverdi_aec_author_id` / `bimverdi_aec_owner_foretak_id`.
Uten dem: author = innloggende/første admin, `eier_leverandor` tom (attribusjon via `_bv_aec_source`).

---

## Tredjeparts-avhengighet (ACF Pro)

`update_field`/`get_field` avhenger av at **ACF Pro** er lastet. ACF Pro
(`plugins/advanced-custom-fields-pro`) er også gitignore-et (tredjeparts, reinstallerbart) —
all ACF-skriv må derfor guardes med `function_exists('update_field')`, med
`update_post_meta`-fallback der det er relevant.

---

## Trinn 2-cutover (gjennomført på localhost 19.08.2026)

Live-kilden er implementert i `class-notion-client.php` +
`BV_AIHUB_Tool_Source::fetch_from_live()`. Ingen token: hub-en er publisert offentlig, og
notion.site sine v3-endepunkter svarer uautentisert (den planlagte
`BV_AIHUB_NOTION_TOKEN`-konstanten trengs altså ikke).

Notion page-id følger med som `notion_id` og lagres som `_bv_aec_notion_id` — **kun sekundær
korrelasjon/sporbarhet.** URL er fortsatt primærnøkkel (`_bv_aec_source_key`), og
identitetsmigreringen ble derfor gratis: alle 230 av de tidligere importerte verktøyene traff
eksisterende poster på URL-nøkkel (0 duplikater).

Resultat av førstegangskjøringen på localhost:

| Mål | Antall |
|-----|-------:|
| Hentet fra hub-en | 1921 |
| Unike etter dedup | 1907 (14 droppet, 13 kolliderende URL-er) |
| Nye utkast | 1677 |
| Oppdatert (traff juni-importen) | 230 |
| Forsvunnet fra kilden (orphan) | 6 (4 av dem var publisert → avpublisert) |
| Utenfor matrisen → «Andre kategorier» | 381 (Assistant 324, Learning 32, AR/VR/MR 15, News 10) |
| Deltakerverktøy rørt | 0 |

Servebolt kan kjøre WP-Cron via UNIX-cron (`wp cron event run --due-now`, B-005) — men det er en
konfigurerbar Optimizer-toggle, så verifiser den før cron slås på i produksjon. Mutexen i `run()`
gjør cron + manuell CLI + selftest trygge å overlappe.
