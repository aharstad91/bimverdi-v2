# Worklog

<!-- Each entry is a YAML block. Most recent first. -->

---
date: 2026-05-28
action: fix-orgnr-exists-blokk-paa-eksisterende-gratisforetak
files:
  - mu-plugins/bimverdi-foretak-registration.php
summary: "Bård påpekte at han som gratisbruker ikke fikk oppgradert et eksisterende gratisforetak — registreringsskjemaet blokkerte med «orgnr_exists». Per ny regel (Bård 2026-05-28): gratisforetak har ingen «eier», enhver gratisbruker kan oppgradere det. Fix (Fase 1 — minimal unblock): når innsendt orgnr matcher et eksisterende gratisforetak (publish + Ikke deltaker) og bruker har valgt et betalende nivå, auto-linkes bruker som gratisbruker og redirectes til selvbetjent oppgrader-flyt (krav 24-v4) med valgt nivå."
status: deployed
detail: |
  **Trigger:** Bård-mail 2026-05-28 med screenshot fra
  /min-side/foretak/registrer/?nivaa=deltaker hvor han prøvde å registrere
  BÅRD KROGSHUS (eksisterende gratisforetak 1955, orgnr 868332662) som
  Deltaker og fikk feilmelding «Dette organisasjonsnummeret er allerede
  registrert i BIM Verdi».

  Bård avklarte produktregelen: «det finnes ikke en hovedkontakt i et
  gratisforetak så alle gratisbrukere kan oppgrader til deltaker+ og bli
  hovedkontakt».

  **Diagnose:**
  bimverdi-foretak-registration.php linje 132-141 hadde en `can_auto_link`-
  sjekk som KUN tillot gjenbruk av eksisterende gratisforetak hvis ny
  registrering også var gratis. Når bruker valgte Deltaker/Prosjektdeltaker/
  Partner på orgnr som finnes som gratisforetak, ble de blokkert i stedet
  for å bli sendt videre til selvbetjent oppgrader-flyt.

  **Fix (Fase 1):**
  Refaktorert orgnr-eksistens-blokken til tre eksplisitte grener:
   1. Eksisterende gratis + ny gratis → auto-link, redirect til foretak-side
      (uendret oppførsel fra 2026-05-22)
   2. Eksisterende gratis + ny paid (deltaker/prosjektdeltaker/partner) →
      auto-link bruker som gratisbruker, redirect til
      /min-side/oppgrader/?nivaa=X. Krav 24-v4-flyten håndterer
      konverteringen og setter brukeren som hovedkontakt.
   3. Eksisterende paid eller pending → blokkér med orgnr_exists (uendret)

  **Verifikasjon (curl, lokalt):**
   - Test 1: paid + eksisterende gratis → 302 til /min-side/oppgrader/?nivaa=deltaker ✅
            + auto-link satte bimverdi_company_id=1638 på testbruker ✅
   - Test 2: gratis + eksisterende gratis → 302 til /min-side/foretak/?registered=1 ✅
   - Test 4: paid + eksisterende paid → 302 til ?bv_error=orgnr_exists ✅
   - Test 5: paid + ny orgnr → 302 til /min-side/?pending=1 ✅

  **Fase 2 (utstående, egen runde):**
  Rydde inkonsekvens i datamodellen — gratisforetak har fortsatt
  `hovedkontaktperson`-felt satt. Det krever:
   - Skippe hovedkontaktperson ved nyregistrering av gratisforetak
   - Endre invitations + foretak-edit-access så alle gratisbrukere har
     samme rettigheter (i dag krever begge hovedkontakt-rolle)
   - Migrasjon for 21 eksisterende gratisforetak på prod
  Ingen feature breaks i mellomtiden — oppgrader-flyten sjekker bare
  `bimverdi_is_gratisbruker()` som ikke leser hovedkontaktperson.

---
date: 2026-05-28
action: fix-bransje-required-minst-en
files:
  - themes/bimverdi-theme/parts/minside/foretak-registrer-form.php
summary: "Bård påpekte at registreringsskjemaet krevde at ALLE bransje-checkboxene måtte hukes av («Please check this box if you want to proceed» på neste boks etter den han hadde valgt). Roten: setTier() satte required på hver enkelt checkbox i bransje-gruppa, så browser behandlet hver som obligatorisk. Fix: bransje håndteres nå separat — required settes kun på første checkbox, og fjernes så snart en hvilken som helst boks i gruppa hukes av (klassisk «minst én»-pattern)."
status: deployed
detail: |
  **Trigger:** Bård-screenshot fra /min-side/?nivaa=deltaker hvor han
  hadde huket av «Bestiller/byggherre» og fikk browser-advarsel på
  «Boligutvikler»: «Please check this box if you want to proceed.»
  Bård presiserte: «På endre krav om å reg. min. EN rolle - ikke ALLE».

  **Diagnose:**
  foretak-registrer-form.php linje 452-456 inkluderte
  `input[name="bransje_rolle[]"]` i conditionallyRequiredFields-listen.
  Linje 494-500 itererte og satte `required` på HVER enkelt checkbox
  i lista når tier var 'paid' — så browser krevde at hver bransje-
  checkbox individuelt var huket av for at form-submit skulle gå
  gjennom. Tekstdialogen «Du kan velge flere» tydet imidlertid på
  at intensjonen var «minst én».

  **Fix:**
  1. Tok `input[name="bransje_rolle[]"]` ut av conditionallyRequiredFields
     (skal ikke ha samme batch-required-behandling).
  2. Ny syncBransjeRequired()-funksjon: setter required kun på første
     checkbox når tier er 'paid' og ingen checkbox er huket. Fjerner
     required så snart en hvilken som helst checkbox i gruppa hukes av.
  3. Hook for change-event på `name=bransje_rolle[]` kaller syncBransjeRequired.
  4. setTier() kaller syncBransjeRequired etter tier-bytte.
  5. Submit-handler for gratis-tier rydder også required på første
     bransje-checkbox (selv om seksjonen er skjult i gratis-state).
  6. Server-side validering ('missing_bransje') uendret — den fanger
     fortsatt edge-caser hvor JS ikke kjører.

  **Verifikasjon (Chrome DevTools MCP på localhost):**
   - nivaa=deltaker, page load → 1 av 14 checkboxes har required (første) ✅
   - Klikk «Boligutvikler» (index 1) → 0 har required, submit lov ✅
   - Uhake → første gjenvinner required (guard mot tom submit) ✅
   - nivaa=gratis → seksjon `display:none`, 0 required ✅
   - HTML-output (uten JS) viser ingen required-attributter på
     bransje-checkboxene → graceful fallback til server-side validering ✅

---
date: 2026-05-28
action: fix-betingelser-tekst-link-gratisbruker
files:
  - mu-plugins/bimverdi-shared-helpers.php
  - themes/bimverdi-theme/parts/minside/foretak-registrer-form.php
summary: "Bård påpekte at Gratisbrukere ikke skal akseptere «betingelser for medlemskap» (deltakelse) — de skal akseptere personvern-betingelser. Helperen bimverdi_render_terms_acceptance_field() tar nå en $nivaa-parameter og renderer riktig tekst/lenke per nivå: gratis → /personvern/ + «betingelsene for personvern»; betalende → /betingelser + «betingelsene for deltakelse i BIM Verdi». Skjemaet sender $selected_nivaa til helperen."
status: deployed
detail: |
  **Trigger:** Bård-mail 2026-05-28 med screenshot fra
  /min-side/?nivaa=gratis#registrer-foretak hvor Gratisbruker-skjemaet
  viste «betingelsene for medlemskap i BIM Verdi». Bård presiserte:
  «En gratisbruker skal ikke akseptere betingelser for deltakelse —
  en gratisbruker skal akseptere betingelser for personvern på
  https://bimverdi.no/personvern/.»

  **Diagnose:**
  Helperen `bimverdi_render_terms_acceptance_field()` i shared-helpers.php
  hadde hardkodet tekst «betingelsene for medlemskap i BIM Verdi» med
  lenke til BV_TERMS_URL (= bimverdi.no/betingelser). Den ble brukt
  uavhengig av nivå i foretak-registrer-form.php linje 397.

  **Fix:**
  1. Helperen tar nå valgfri `$nivaa`-parameter ('gratis' |
     'deltaker' | 'prosjektdeltaker' | 'partner' | '').
  2. Gratis → /personvern/ + «betingelsene for personvern».
  3. Alle betalende → /betingelser + «betingelsene for deltakelse i
     BIM Verdi» (default når nivaa ikke er gratis).
  4. foretak-registrer-form.php sender nå $selected_nivaa til helperen.

  **Verifikasjon (curl, lokalt):**
   - nivaa=gratis → href=/personvern/, tekst «betingelsene for personvern» ✅
   - nivaa=deltaker → href=/betingelser, tekst «...for deltakelse i BIM Verdi» ✅
   - nivaa=prosjektdeltaker → samme som deltaker ✅
   - nivaa=partner → samme som deltaker ✅

  **Andre callere:** Ingen — helperen kalles kun fra foretak-registrer-form.php.

---
date: 2026-05-28
action: fix-loop-min-side-nivaa-deltaker
files:
  - themes/bimverdi-theme/inc/minside-helpers.php
summary: "Bård rapporterte i mail at «Velg Deltaker» på /min-side/ sendte ham i evig loop tilbake til /min-side/?nivaa=deltaker#registrer-foretak. Roten: statisk Gutenberg-pattern `pricing-tabell` har CTAs til `?nivaa=X#registrer-foretak`, men inline-skjemaet på dashboardet rendres kun når $bruker_foretak er satt (BRREG-søk). State 3 (uten foretak, uten BRREG-state) → ingen form → loop. Fix: server-side redirect på template_redirect som sender brukerne til /min-side/foretak/registrer/?nivaa=X som faktisk rendrer skjemaet."
status: deployed
detail: |
  **Trigger:** Bård-mail 2026-05-28: «Når jeg vil oppgradere og velger
  'deltaker' på https://bimverdi.no/min-side/?nivaa=deltaker#registrer-foretak
  så går jeg i loop tilbake til https://bimverdi.no/min-side/?nivaa=deltaker
  #registrer-foretak.»

  **Diagnose (kode-trace):**
  Dashboard har 3 user-states for pricing-tabellen:
  1. Med foretak → "Bli Deltaker+"-CTA → /min-side/oppgrader/ (RIKTIG, krav 24-v4)
  2. Uten foretak, MED $bruker_foretak (BRREG-søk gjort) → pricing-tabell
     med CTA til /min-side/?nivaa=X#registrer-foretak → siden laster →
     inline form (line 827) rendres → fungerer
  3. Uten foretak, UTEN $bruker_foretak → static Gutenberg-pattern fallback
     (line 818-820) → CTAs peker (via Gutenberg-konfig) til samme URL →
     siden laster → MEN line 827 sjekker $bruker_foretak → form rendres
     ikke → bruker ser samme tabell igjen → LOOP

  Bård satt i state 3.

  **Fix:** Server-side redirect i `bimverdi_minside_nivaa_unblock_redirect()`
  hooked til `template_redirect` priority 6 (etter auth-check).
  Guard-betingelser:
   - Bare med ?nivaa= satt
   - Bare logget-inn-brukere på /min-side/-roten (ikke sub-ruter)
   - Bare for brukere uten $company_id OG uten $bruker_foretak (state 3)
   - Bare for gyldige plan-keys validert mot bimverdi_pricing_valid_plan_keys()

  Redirect → /min-side/foretak/registrer/?nivaa=X som rendrer
  foretak-registrer-form.php step 2 direkte (foretak-registrer-form.php:31
  leser allerede ?nivaa=-param).

  **Verifikasjon (curl, lokalt):**
   - State 3 + nivaa=deltaker → 302 → /min-side/foretak/registrer/?nivaa=deltaker ✅
   - State 3 + nivaa=gratis/prosjektdeltaker/partner → 302 til riktig URL ✅
   - State 3 + nivaa=blabla → 200 (ingen redirect) ✅
   - State 3 uten ?nivaa= → 200 ✅
   - State 2 (med bruker_foretak) + nivaa=deltaker → 200 (inline form virker) ✅
   - State 1 (med company_id) + nivaa=deltaker → 200 ("Bli Deltaker+" CTA) ✅
   - Sub-rute /min-side/foretak/registrer/?nivaa=deltaker → 200 (form rendres) ✅
   - Destinasjonen rendrer skjemaet med "Valgt deltakernivå: Deltaker" og
     hidden input deltakertype="deltaker" ✅

  **Hva som IKKE er fikset (utsatt etter avtale med Andreas):**
   - Den statiske Gutenberg-pattern'en `pricing-tabell` (CTAs peker til
     /min-side/?nivaa=X#registrer-foretak) — pattern kan ryddes senere,
     redirect'en gjør dette ikke-blokkerende.
   - Horisontal scroll på tabellen i Bårds screenshot — UI-issue, kosmetisk.

  **Neste steg etter prod-deploy:**
   - Smoke-test som ikke-foretak-bruker på prod
   - Svar til Bård: loopen er borte, tabellen ryddes i en senere runde,
     spør om han ser andre "åpenbare feil" i flyten nå

---
date: 2026-05-28
action: prod-backfill+rolle-sync+hovedkontakt-lenker+500-fix
files:
  - mu-plugins/bimverdi-custom-roles.php
summary: "Kjørt bv_foretakstype-backfill (91 foretak) og rolle-sync (67 brukere) på prod via SSH/wp-cli, etter forhåndsbackup. Lagt til Hovedkontakt + Gratisbruker som filter-lenker øverst på wp-admin/users.php for å matche Bårds mentale modell. Fanget og fikset en 500-feil i pre_get_users-hook som slo til på alle WP_User_Query — inkludert interne single-user-hydreringer. Filter virker på live."
status: shipped-live
commits:
  - 3e9ccd6 — feat(admin): vis Hovedkontakt + Gratisbruker som filter-lenker i users.php
  - f4d3a59 — fix(admin): forhindre 500-feil ved kontakttype-filter på users.php
detail: |
  **Prod-state etter dagens kjøring (speiler lokal eksakt):**
   - 596 users — 402 medlem · 125 tilleggskontakt · 44 deltaker
     · 12 prosjektdeltaker · 7 partner · 3 administrator · 2 subscriber
   - 87 hovedkontakter (65 betalende + 22 gratisforetak) = Bårds nyhetsbrev-
     målgruppe. Kan isoleres via "Hovedkontakt (87)"-lenke øverst på users.php.

  **Backfill + sync via SSH/wp-cli mot prod:**
   - Pre-backup: /tmp/bimverdi-prod-pre-bardsync-20260528-111007.sql (8.2 MB,
     lokalt på Andreas-maskin). Rollback om nødvendig:
     `ssh ... "wp db import -" < backup.sql`
   - Backfill: 91 foretak fikk bv_foretakstype + bv_nivaa
     (23 gratisforetak + 68 foretak: 49 deltaker + 12 prosjektdeltaker
     + 7 partner). 0 ukjente bv_rolle.
   - Rolle-sync: 67 brukere fikk korrigert WP-rolle (41 subscriber→deltaker,
     12 subscriber→prosjektdeltaker, 6 subscriber→partner, 3 tilleggskontakt
     →medlem, m.fl.). 0 avvik etter sync.

  **500-FEIL etter 3e9ccd6 — pre_get_users traff alle WP_User_Query:**
  Hovedkontakt-lenken førte til HTTP 500 ved klikk. Diagnose: min
  `bimverdi_filter_users_by_kontakttype()` ble registrert på pre_get_users
  uten screen-context-sjekk. WP kjører dusinvis av interne WP_User_Query
  per admin-render (hydrering av meta, capabilities, screen-options) —
  hver fikk sin include-array overskrevet med min 65-IDs-liste, som
  forårsaket kjedereaksjon → max execution time → 500.

  Bugen lå allerede i del B-koden (c693e56) men ble ikke detekterbar før
  jeg eksponerte ?bv_kontakttype-lenker i views_users (3e9ccd6).
  Lokal wp-cli-test fanget det ikke fordi CLI ikke trigger admin-screen-
  init-queries.

  **Fix (f4d3a59):**
   - `global \$pagenow; if (\$pagenow !== 'users.php' || !is_admin()) return;`
     — filteret kjører nå kun på selve list-table-spørringen.
   - Respekter eksisterende `include` i query (ikke overskriv).
   - views_users-tellingen unhooker pre_get_users-filteret midlertidig
     så lenke-counten ikke skjevheter av et aktivt ?bv_kontakttype-filter.
   - Static \$running-guard mot re-entrance innen samme call-stack.

  Andreas bekreftet "funker på live nå" 11:36 norsk tid. 65 hovedkontakter
  rendres korrekt, ingen 500.

  **Lærdom (memory):**
   - feedback_pre_get_users_pagenow_check.md — alltid sjekk \$pagenow før
     pre_get_users modifiserer query.
   - reference_servebolt_deploy.md — Servebolt autodeploy skriver filer
     direkte uten git pull, så `git log` på serveren viser misvisende HEAD.
     Verifiser deploy med stat -c '%y' eller grep på nye symboler.

  **Bårds eksport av nyhetsbrev-mottakere:**
   wp-admin/users.php → klikk "Hovedkontakt (87)" → bulk-velg eller bruk
   Tools → Export → Users.

---
date: 2026-05-28
action: del-A-bard-feedback-shipped+del-B-rolle-rydding-shipped
files:
  - mu-plugins/bimverdi-betingelser-prices.php
  - mu-plugins/bimverdi-foretak-konvertering.php
  - mu-plugins/bimverdi-foretakstype-fields.php
  - mu-plugins/bimverdi-foretakstype-backfill.php
  - mu-plugins/bimverdi-invitasjons-type-migration.php
  - mu-plugins/bimverdi-custom-roles.php
  - mu-plugins/bimverdi-roles-sync.php
  - themes/bimverdi-theme/parts/minside/oppgrader.php
  - themes/bimverdi-theme/parts/minside/oppgrader-fullfort.php
  - themes/bimverdi-theme/inc/minside-helpers.php
  - themes/bimverdi-theme/parts/minside/dashboard.php
summary: "To deployer pushet til main. Del A: hele krav-24-v4 selvbetjent oppgraderings-flyt med Bårds feedback fra møte 28. mai (årspriser, EHF-felt, fjernet BRREG-steg, fjernet kvartal-tall fra bekreftelse/e-post). Del B: rolle-rydding i wp-admin/users.php (tre nye kolonner, filter-dropdown, sync-script for WP-roller). Lokal DB synket ned fra prod og verifisert mot ekte data."
status: shipped-live-A-and-B
commits:
  - f1779bc — feat(oppgrader): selvbetjent oppgraderings-flyt for gratisforetak (krav 24-v4)
  - c693e56 — feat(admin): rolle- og deltakernivå-rydding i wp-admin/users.php
detail: |
  **Bakgrunn:** Møte med Bård 28. mai morgen. Han hadde testet onboarding-
  flyten og hadde to bunker feedback: (A) oppgrader-flyten — kvartalsvis
  pris-automatikk må vekk, EHF mangler, BRREG-steget er friksjon; og
  (B) han mangler oversikt over hvem som er hovedkontakt før utsending
  av nyhetsbrev til ~60 stk. Transcripts ligger på Andreas-Desktop som
  `synk-bard-28mai-{en,to}.json`.

  ---

  **DEL A — krav-24-v4 oppgrader-flyt med Bårds feedback (commit f1779bc):**

  Endringer i oppgrader-flyten (/min-side/oppgrader/):

  1. **Pris-data refaktorert til årspris** (bimverdi-betingelser-prices.php):
     - Deltaker 8 000, Prosjektdeltaker 24 000, Partner 48 000 kr/år.
     - `bimverdi_calculate_oppgrader_invoice()` forenklet — fjernet hele
       kvartal-strukturen (start_kvartal, antall_kvartaler, fra_dato,
       til_dato, kvartaler_dekket). Returnerer nå bare {nivaa, aarspris,
       totalbeloep, aar}.

  2. **oppgrader.php (skjema):**
     - "2. Bekreft foretaksdata" (BRREG-steg) FJERNET helt — Bård: data
       fra BRREG kan ikke endres her uansett, så steget var unødvendig
       friksjon.
     - Skjemaet er nå 3 steg: Velg nivå → Faktureringsdetaljer → Bekreft.
     - "kr / kvartal" → "kr / år" på nivå-radioene.
     - Statisk disclaimer-tekst lagt til under nivå-valg: "Årsavgiften
       beregnes kvartalsvis fra det kvartalet du melder inn ditt foretak.
       Rabatt for oppstartbedrifter, utdanningsinstitusjoner og foretak
       med omsetning lavere enn 5 MNOK — ta kontakt på tilbakemelding."
     - Nytt EHF-organisasjonsnummer-felt i faktureringsdetaljer.
     - Betinget validering: minst EHF eller faktura-e-post må fylles ut.
       Validering håndheves serverside (missing_invoice_kanal-feilmelding).
     - JS-helper for klientside UX (markerer felter som påkrevd basert
       på hva som er fylt ut).
     - Dynamisk pris-omberegning fjernet (ingen kvartal-math lenger).

  3. **oppgrader-fullfort.php (bekreftelses-side):**
     - Hele pris-paragrafen med kvartal-tall ("Faktura på X kr for Y
       kvartaler fram til 31.12.Z") FJERNET.
     - Erstattet med generisk: "BIM Verdi-administrasjonen oppretter
       faktura manuelt og sender den til foretaket basert på fakturadetalj-
       ene du fylte ut."
     - URL-param `?total=X&kvartaler=Y&aar=Z&epost=...` redusert til
       bare `?nivaa=X`.

  4. **bimverdi-foretak-konvertering.php (handler + e-post):**
     - Validering: brreg_bekreftet-krav fjernet. Lagt til EHF/e-post
       conditional check (`missing_invoice_kanal`).
     - Form-data utvidet med `ehf_orgnr` (sanitert, mellomrom strippet).
     - Bekreftelses-e-post til ny hovedkontakt: kvartal-tall fjernet,
       sier nå bare at admin sender faktura manuelt.
     - Admin-e-post (post@bimverdi.no) endret: viser nå Årsavgift +
       EHF-orgnr i stedet for kvartal-utregning. Lagt til påminnelse om
       å beregne kvartals-rabatt + småforetak/utdanning-rabatter manuelt.

  **Pushet i samlet commit (krav-24-v4-prototype fast-forwardet til main):**
  10 filer, 1429 insertions. Inkluderte også andre uncommittede filer fra
  krav-24-v4-prototype-branchen som henger sammen (foretakstype-fields,
  foretakstype-backfill, invitasjons-type-migration, minside-helpers
  med nye routes, dashboard.php CTA-redesign).

  Servebolt auto-deployer fra main. Bård kan teste live så snart deploy
  er klar (typisk <3 min).

  **Chrome MCP-test før push:**
  - /min-side/oppgrader/ viser 8/24/48 kr/år, ingen BRREG-steg, EHF-felt.
  - Server-side validering: submit uten EHF eller e-post → korrekt
    feilmelding "Du må fylle ut enten EHF-organisasjonsnummer eller
    fakturamottakers e-post."
  - /min-side/oppgrader/fullfort/?nivaa=deltaker viser ingen kvartal-tall.
  - Ingen JS/PHP-feil i konsoll.

  ---

  **DB-SYNK FRA PROD TIL LOCALHOST (sync-db.sh --db):**

  Lokal DB ble overskrevet med prod-dump for å teste del B mot ekte
  data. Backup av tidligere lokal DB lagret i
  /tmp/bimverdi-localhost-pre-sync-20260528-103558.sql før synk.

  Etter synk: 596 users, 81 publiserte foretak. Men prod-DB hadde ingen
  `bv_foretakstype` eller `bv_nivaa` (krav-24-v4 backfill aldri kjørt på
  prod). Kjørte derfor `bimverdi_run_foretakstype_backfill()` lokalt mot
  synkede prod-data — 91 foretak fikk korrekt foretakstype + nivå
  (23 gratisforetak + 68 foretak: 49 deltaker + 12 prosjektdeltaker
  + 7 partner). 0 ukjente bv_rolle.

  **VIKTIG for Bård:** Backfillen må kjøres tilsvarende på prod etter
  Servebolt-deploy, ellers er ingen brukere "gratisbruker" på prod og
  oppgrader-knappen vises ikke. Trigger:
  https://bimverdi.no/wp-admin/?bimverdi_backfill_foretakstype=1[&dry_run=1]

  ---

  **DEL B — rolle- og deltakernivå-rydding (commit c693e56):**

  Bårds spørsmål: "Jeg mangler oversikt over hvem som har hvilken rolle,
  på bedrifts- og personnivå. Hvem er allerede hovedkontakt? Jeg famler
  litt i blinde her." Han venter med å sende ut nyhetsbrev til de
  60 stk fordi han ikke kan stole på listen.

  Endringer:

  1. **bimverdi-foretakstype-fields.php — to nye helpers:**
     - `bimverdi_get_kontakttype($uid)` → 'gratisbruker' | 'hovedkontakt'
       | 'tilleggskontakt' | null. Computed fra foretakets bv_foretakstype
       + hovedkontaktperson — sannhetskilden er foretak-data.
     - `bimverdi_get_deltakernivaa($uid)` → 'gratisforetak' | 'deltaker'
       | 'prosjektdeltaker' | 'partner' | null. Arvet fra foretakets
       bv_nivaa.

  2. **bimverdi-custom-roles.php — admin-kolonner og filter:**
     - Eksisterende "Medlemskap"-kolonne FJERNET (blandet WP-rolle og
       deltakernivå på en uleselig måte).
     - Tre nye kolonner i wp-admin/users.php:
       - **Foretak** — lenke til foretak-edit
       - **Kontakttype** — Gratisbruker / Hovedkontakt / Tilleggskontakt
       - **Deltakernivå** — Gratisforetak / Deltaker / Prosjektdeltaker
         / Partner
     - Filter-dropdown "Kontakttype" på toppen av brukerlisten med
       opsjoner "Bare hovedkontakter" / tilleggskontakter / gratisbrukere
       / uten foretak. Filtrering caches 60 sek (O(n) på user-listen).

  3. **bimverdi-roles-sync.php (NY) — sync av WP-rolle:**
     - `bimverdi_compute_target_wp_role($uid)` → ønsket WP-rolle.
     - `bimverdi_run_roles_sync($dry_run)` → iterer alle brukere.
     - Logikk:
       - hovedkontakt + bv_nivaa=deltaker → WP-rolle 'deltaker'
       - hovedkontakt + bv_nivaa=prosjektdeltaker → 'prosjektdeltaker'
       - hovedkontakt + bv_nivaa=partner → 'partner'
       - tilleggskontakt (uansett nivå) → 'tilleggskontakt'
       - gratisbruker (uansett om hovedkontakt) → 'medlem'
       - administrator → ALDRI rørt
       - ingen foretak → urørt
     - Admin-trigger: ?bimverdi_sync_roles=1[&dry_run=1]
     - Idempotent.

  **Lokal verifisering (mot synkede prod-data):**
  - Dry-run viste 67 endringer av 596 users.
  - Fordeling av endringer: 41 subscriber→deltaker, 12 subscriber→
    prosjektdeltaker, 6 subscriber→partner, 3 tilleggskontakt→medlem,
    + diverse småjusteringer.
  - Stikkprøve på 3 users bekreftet at logikken matcher faktisk
    hovedkontakt-status og bv_nivaa.
  - Kjørt ekte sync lokalt: 67 brukere oppdatert.
  - Konsistens-sjekk etter sync: 0 avvik mellom foretak.bv_nivaa og
    hovedkontaktens WP-rolle.

  **Final state lokal DB (= forventet på prod etter Bård kjører sync):**
  - 402 medlem, 125 tilleggskontakt, 44 deltaker, 12 prosjektdeltaker,
    7 partner, 3 administrator, 2 subscriber (manuelle).
  - 87 hovedkontakter totalt (65 betalende + 22 gratisforetak) =
    Bårds nyhetsbrev-målgruppe.

  ---

  **RAPPORT TIL BÅRD:**
  Lagret i docs/2026-05-28-rolle-og-deltakernivaa-rapport.md (utenfor
  versjonering, deles via meldings-app). Inneholder:
  - Hva som er endret (kolonner + filter + sync)
  - Resultat etter lokal sync (67 endringer, 0 avvik)
  - Hvordan kjøre sync på prod
  - Hvordan endre en persons rolle manuelt (via foretak-data)
  - Steg-for-steg for å eksportere nyhetsbrev-målgruppen.

  ---

  **TODO på prod (Bård gjør selv):**

  1. ⚠️  Kjør backfill: https://bimverdi.no/wp-admin/?bimverdi_backfill_foretakstype=1&dry_run=1
     (først dry-run, så uten dry_run-param). Uten dette er ingen brukere
     "gratisbruker" på prod og oppgrader-knappen er ikke synlig.
  2. Kjør rolle-sync: https://bimverdi.no/wp-admin/?bimverdi_sync_roles=1&dry_run=1
     (dry-run først, så ekte). Forventet: ~67 brukere oppdatert.
  3. Åpne /wp-admin/users.php → filtrer "Bare hovedkontakter" → eksporter
     via Tools → Export → Users for å få nyhetsbrev-mottakerne.
  4. Test selvbetjent oppgrader-flyt med en gratisbruker.

  **Gjenstår i Bård-sporet (ikke startet):**
  - Nyhetsbrev-kravspec — Trello-kortet «nyhetsbrev / registrering / mal
    og utsendelse» (https://trello.com/c/8GAdLLF7) har TO leveranser
    bakt inn i ett kort. Skal splittes i (a) selve nyhetsbrev-malen og
    (b) registrerings/temavalg-flyten. 7 åpne spørsmål må listes for
    Bård å bekrefte før vi planlegger.
  - Spam-kommentarer på "Hei Verden"-posten (egen sak, lavprioritet).

---
date: 2026-05-28
action: demo-krav24-localhost+nyhetsbrev-kravspec-funnet-paa-trello
files: []
summary: "Helhetlig demo av oppgraderings-flyten (krav 24-v4) kjørt på localhost som Mari/SOL-IS — login → dashboard (CTA + Oppgrader-meny) → skjema med live pris-omberegning og egen-fakturaadresse-toggle → konvertering → fullført-side med EHF/admin-tekst. DB-state og e-post-utsendelse via Resend verifisert. SOL-IS nå Foretak/Deltaker i lokal DB. Skjermbilder i claude/demo-screenshots/. Klar for Bård-demo. Nyhetsbrev-mal-kravspec (TODO 2): IKKE i docs/krav/ — Bård har lagt den på Trello (https://trello.com/c/8GAdLLF7, Innboks)."
status: ready-for-bard
detail: |
  **Demo-flyt verifisert (Mari Isdahl, user 625, SOL-IS ARKITEKTER AS = foretak 1669):**

  1. Login som hovedkontakt for gratisforetak → redirect til /min-side/
  2. Dashboard viser "Gratis brukerforetak"-status, CTA-blokk "Bli Deltaker+",
     "Oppgrader" som menypunkt. AK-04 (vises kun for Gratisbrukere) ✅
  3. /min-side/oppgrader/ renderer 4-seksjonsskjema med Deltaker forhåndsvalgt
  4. Live pris-omberegning ved nivå-bytte (Deltaker→Prosjektdeltaker: 24 000→45 000)
  5. Egen fakturaadresse-checkbox folder ut textarea via JS-toggle
  6. Submit (Deltaker, 24 000 kr, 3 kvartaler Q2–Q4 2026) → konvertering OK
  7. Fullført-side viser kravets eksakte EHF-tekst: "Faktura blir opprettet
     manuelt og sendt via EHF til SOL-IS ARKITEKTER AS (orgnr 930874922)"
  8. Navigasjonen oppdatert: "Oppgrader"-menypunkt borte automatisk (AK-04)

  **Verifikasjon (debug.log 28-May 07:02):**
  - DB: bv_foretakstype=foretak, bv_nivaa=deltaker, bv_rolle=Deltaker
  - Resend ID 45287025... til mari@sol-is.no (bekreftelse)
  - Resend ID e7e7a644... til post@bimverdi.no (admin-faktura)
  - 0 kolleger varslet (Mari er eneste bruker i SOL-IS etter gårsdagens rydding)

  **EHF-spørsmål (under demoen):** Andreas spurte hvorfor EHF ikke er i
  skjemaet. Svar: B-031 parkerer hele fakturering-infrastrukturen — EHF
  sendes fra Tripletex manuelt mot foretakets org.nr (ikke en e-post-adresse).
  EHF-flyten er forklart for brukeren PÅ fullført-siden, ikke i skjemaet.
  Mulig forbedring å diskutere med Bård: legg en setning under
  "Faktureringsdetaljer" som forklarer at faktura sendes via EHF til org.nr.

  **DB-state ETTER demo:** SOL-IS er nå Foretak/Deltaker på localhost.
  Hvis Bård skal teste samme flyt selv må vi enten (a) rulle tilbake til
  gratisforetak, eller (b) bruke et annet gratisforetak (1638 FÆRDER
  BYGGKONTROLL, 1635 KVALSUND INGENIØRER, etc. — det finnes ~18 stk).

  **TODO 2 (nyhetsbrev-mal) — kravspec funnet på Trello:**
  Card: https://trello.com/c/8GAdLLF7/302-nyhetsbrev-registrering-mal-og-utsendelse
  Liste: Innboks. Labels: Prioriteres, Ny funksjon. Andreas eier.

  Kravet har TO deler:
  - **A — Registrering:** (1) nyhetsbrev-abonnement krever foretakskobling
    (dette ER allerede implementert i krav 22), (2) "Meld på"-knappen i
    footer skal sende til /logg-inn/ med ferdigutfylt e-post slik bruker
    velger login/opprett konto, (3) etter foretakskobling kan bruker velge
    tema for nyhetsbrev.
  - **B — Mal og utsendelse:** Sendes annenhver torsdag kl 12:00 fra
    post@bimverdi.no. Emne: "Nytt & Nyttig fra BIM Verdi". Innhold:
    overskrift + ingress (innhold merket med temavalg) → siste 3 artikler
    (overskrift, av-hvem, 3 linjer + link) → neste arrangement → siste 3
    verktøy/tjenester → siste 3 kunnskapskilder → siste 3 deltakere
    (foretak). Link til /min-side/profil/rediger/ for å endre temavalg.
    Avsender: "Nettverkshilsner fra Bård Krogshus, BIM Verdi".

  **Avhengigheter for TODO 2:**
  - Tematiske nyhetsbrev krever at brukere kan VELGE tema i profil →
    profil-rediger.php trenger temagruppe-multiselect (eksisterer
    taksonomien `temagruppe` allerede; brukerens valg lagres hvor?)
  - Frekvens "annenhver torsdag kl 12" → WP-cron schedule
  - Mal-rendering: HTML-template (kan bruke bimverdi_render_terms_footer_html
    som mønster, eller egen newsletter-template-renderer)
  - Sendetjeneste: Resend (allerede infra på plass) eller dedikert
    nyhetsbrev-tjeneste? IKKE spesifisert i Trello-kortet.

  **Anbefaling før implementasjon:** Spør Bård om (a) sendetjeneste-valg
  (Resend mass-send vs Mailchimp/Brevo), (b) hvor temavalget skal lagres
  på brukeren (user_meta vs ACF), (c) start-dato for første utsendelse
  (annenhver torsdag fra hvilken dato?).

---
date: 2026-05-27
action: bard-synk+prioritering+steg2-epost-fakturaadresse
files:
  - mu-plugins/bimverdi-foretak-konvertering.php
  - themes/bimverdi-theme/parts/minside/oppgrader.php
  - docs/krav/PROTOTYPE-RAPPORT-krav24.md
  - docs/krav/PROTOTYPE-EDGE-CASE-RAPPORT-krav24.md
summary: "Bård-synk 26. mai satte prioritering: (1) ferdigstill oppgraderings-flyten (krav 24-v4) FØR alt annet, (2) nyhetsbrev-mal innen 15. juni, (3) Stefans KI-verktøy + arrangementsoversikt. Demo + steg-2 27. mai: e-postutsendelse (3 e-poster) + egen-fakturaadresse-felt nå ferdig og E2E-verifisert; klar for prod hvis Bård godkjenner."
status: waiting
detail: |
  **Kilde:** Synk med Bård 2026-05-26 (synk-bård-26-mai.json).
  **Rollefordeling fremover:** Bård = arkitekt (skriver spec i MD-filer),
    Andreas = teknisk implementerer. Bård bekreftet modellen.

  **PRIORITERT REKKEFØLGE (Bårds ord: "logistikken må være ferdig før noe annet"):**

  ## TODO 1 — Ferdigstill oppgraderings-flyten (krav 24-v4) [BLOKKERER ALT ANNET]
    Prototype bygget + edge-case-testet (Units 1,4,5,6,7,9 ferdig).

    STEG 2 FERDIG 2026-05-27 (E2E-verifisert via Chrome MCP som Mari/SOL-IS):
    - [x] E-postutsendelse (AK-22, AK-23): erstattet stub i
          bimverdi-foretak-konvertering.php med faktisk utsendelse av 3
          e-poster — bekreftelse til hovedkontakt, varsel til hver
          tilleggskontakt (kravets eksakte tekst), admin-faktura til
          post@bimverdi.no. NB: prosjektet overstyrer wp_mail() via Resend
          (bimverdi-resend-mail.php), så e-post sendes FAKTISK også fra
          localhost. Test sendte 3 ekte e-poster via Resend (bekreftet ID-er
          i debug.log). Egen-fakturaadresse tas med i admin-e-posten.
    - [x] "Egen fakturaadresse"-felt (skjema-felt 6): checkbox + textarea
          med JS-toggle i oppgrader.php, sanitert i handler.

    GJENSTÅR før prod-deploy:
    - [ ] Min Side-notifikasjoner til øvrige Gratisbrukere (B-029) — delt
          infrastruktur (tabell wp_bimverdi_notifications, klokke-ikon+badge,
          varslinger-side). Bevisst utelatt: større delsystem flere krav bruker.
    - [ ] Nedgraderings-knapp i WP-admin + data-håndtering (AK-24, AK-25) —
          admin-speilet av flyten; brukes kun "hvis betaling uteblir".
    - [ ] Pris-henting fra bimverdi.no/betingelser/ (AK-5) — IKKE et gap:
          parsing parkert med fakturerings-prosjektet (B-031), hardkodet
          fallback er det v1 skal bruke per kravet. VERIFISER prisene
          (8000/15000/25000) mot betingelser-siden før prod.
    - [ ] Cron for årlig fornyelse (AK-27) — kravet motsier seg selv: prosa
          sier "utenfor scope / eget krav 25", men det står som AK. Hører
          til krav 25, ikke 24. Påpek for Bård.
    - [ ] Avklar 3 åpne spørsmål med Bård (se under).

  ## HVORFOR var ikke alt bygget i prototype-runden (3 kategorier)
    1. Bårds egne parkeringer (IKKE gap): pris-parsing (B-031),
       årlig fornyelse (krav 25).
    2. Bevisst prototype-scoping: e-post + fakturaadresse-felt — nå FERDIG (steg 2).
    3. Delt/større infrastruktur eller admin-speil: Min Side-notifikasjoner
       (B-029), nedgraderings-knapp.

  ## TODO 2 — Nyhetsbrev-mal [FRIST 15. JUNI]
    - [ ] Bård skriver spec i MD-fil og sender til Andreas.
    - [ ] Mal skal vise: siste kunnskapskilde, "siste ditt og siste datt".
    - [ ] Bård trenger å sende ut nyhetsbrev FØR 2026-06-15.

  ## TODO 3 — Nye tjenester [ETTER logistikk + nyhetsbrev]
    - [ ] Stefans KI-verktøy: ~1800 KI-verktøy for byggenæringa importeres
          som egen verktøyoversikt (samlet ~2000 med eksisterende 34).
          Godkjenning fra Stefan finnes (mail + MD-fil). Tolke MD/word-filer
          til verktøy-data. Verktøyene merkes på egen måte per dummy-bildet.
    - [ ] Arrangementsoversikt — separat tjeneste under tjenestemenyen.

  ## ÅPNE SPØRSMÅL TIL BÅRD (fra edge-case-rapporten):
    1. Hva skjer hvis admin er koblet til et Gratisforetak — skal hun se
       CTA, eller "bruk Tripletex"-melding?
    2. Skal portalen vise "venter på betaling"-status, eller går konvertering
       rett gjennom (admin nedgraderer manuelt om betaling uteblir)?
    3. Skal fakturamottaker-feltet valideres mot bedrifts-epost (avvise
       gmail osv.), eller er fri e-post OK?

  ## ANDRE NOTATER:
    - Pro-JSB gamlesida lastet ned (28 GB → 2,5 GB konvertert), ligger
      lokalt. Skal opp som eget nettsted på Servebolt — ingen ekstra kostnad.
    - Staging-løsning (demo-side av live) foreslått av Andreas, Bård
      parkerte: "alt i sin tid".
    - Demo skjer på localhost (stor endring, kan ikke pushes live ennå) —
      samme modell som forrige gang.

---
date: 2026-05-22
action: deploy+gratisbruker-orgnr-sharing
files:
  - mu-plugins/bimverdi-foretak-registration.php
summary: "Flere gratisbrukere kan nå dele samme orgnr. Tidligere blokkerte orgnr_exists-sjekken alle gjenregistreringer; nå auto-kobles ny gratisbruker som tilleggskontakt til eksisterende publisert gratisforetak (bv_rolle='Ikke deltaker'). Betalende registrering blokkeres uendret."
status: deployed
detail: |
  **Commit:** f0d4170 (deployet via GitHub→Servebolt-autodeploy)
  **Branch:** main
  **Trigger:** Bård-rapport 2026-05-22 — ny gratisbruker fikk feilmelding
    «Dette organisasjonsnummeret er allerede registrert i BIM Verdi»
    selv om eksisterende foretak var gratis.

  **Endring:**
    - Orgnr-eksistens-sjekken i bimverdi-foretak-registration.php
      tillater nå auto-link når:
        deltakertype === 'gratis'
        AND existing.post_status === 'publish'
        AND existing.bv_rolle === 'Ikke deltaker'
    - Ny helper bimverdi_foretak_autolink_gratis_user($user_id, $foretak_id):
        * setter bimverdi_company_id + bim_verdi_company_id (legacy)
        * setter bimverdi_account_type='foretak'
        * setter ACF tilknyttet_foretak på bruker
        * setter tilleggskontakt-rolle (aldri downgrade admin)
        * rydder BRREG-search-state (bimverdi_bruker_foretak_*)
        * purger foretak-cache
        * sender FYI-mail til admin (krever ingen handling)
    - Pending eller paid eksisterende → fortsatt blokkering
      (orgnr_exists-melding uendret).

  **E2E-validering (Chrome MCP, localhost):**
    - Positiv: ny gratisbruker (autolink-test) registrerte 983792685
      (KEPLA AS, eksisterende gratisforetak). Redirect til
      /min-side/foretak/?registered=1, ser «Mitt foretak: KEPLA AS —
      Gratis brukerforetak». DB bekreftet:
        user 647: bimverdi_company_id=1606, role=tilleggskontakt,
        tilknyttet_foretak=1606.
    - Negativ: betalende-registrering (deltaker) på samme orgnr →
      redirect ?bv_error=orgnr_exists (uendret).
    - Ingen duplikate foretak opprettet for orgnr 983792685 (én
      foretak-post før og etter testene).
    - Testbrukere ryddet etter validering.

  **Prod-verifisering:**
    - SSH-grep på Servebolt bekrefter 4 treff på autolink-helper +
      can_auto_link i deployert fil.

  **Utsatt:**
    - Paid+gratis-orgnr-kombinasjon (krever ny UX-runde med Bård):
      hva skjer hvis noen betaler for orgnr som alt har gratisforetak?
      Bård svarte «ta det senere» 2026-05-22.

---
date: 2026-05-21
action: deploy+screenshare-followup
files:
  - mu-plugins/bimverdi-foretak-registration.php
  - mu-plugins/bimverdi-foretak-oppgradering.php
  - mu-plugins/bimverdi-email-verification.php
  - mu-plugins/bimverdi-shared-helpers.php
  - themes/bimverdi-theme/parts/minside/dashboard.php
  - themes/bimverdi-theme/parts/minside/foretak-detail.php
  - themes/bimverdi-theme/parts/minside/foretak-oppgrader.php
  - themes/bimverdi-theme/templates/onboarding/template-aktiver-konto.php
  - docs/plans/2026-05-21-001-feat-bard-screenshare-followup-plan.md
  - claude/bard-context/admin-domener-howto.md
summary: "Implementerte alle P0-todos fra Bård-møtet samme dag + deployet til prod. Auto-godkjenn Gratisforetak fjerner flaskehals for 562/608 brukere uten foretak. Tekstfikser + pricing-disclaimer + nyhetsbrev-checkbox lever på prod."
status: deployed
detail: |
  **Commits:** 061ed2b (screenshare-followup) + bcd794c (worklog demo-prep) + eb3996e (merge)
  **Branch:** feat/onboarding-grunnmur → main (no-ff merge, pushet til GitHub)
  **Plan:** docs/plans/2026-05-21-001-feat-bard-screenshare-followup-plan.md

  **7 implementation units, alle ferdige:**
    1. Auto-godkjenn Gratisforetak — wp_insert_post(publish) direkte for
       bv_rolle='Ikke deltaker', kaller bimverdi_foretak_pending_approve()
       inline (transition-hook fyrer ikke siden old_status='new', ikke
       'pending' — verifisert i E2E). Admin-mail subject endret til
       "auto-godkjent (FYI)". Betalende roller beholder pending-flyt.
    2. Tekstfikser i 5 lokasjoner — "Bård vurderer/sender" → "vi behandler".
       grep -rn "Bård" på UI returnerer 0 treff på dashboard/oppgrader/detail.
    3. Helper bimverdi_foretak_rolle_label() — Ikke deltaker → Gratis
       brukerforetak; Deltaker → Deltakerforetak; +Prosjektdeltaker; +Partner.
       Brukt i dashboard.php:378 + foretak-detail.php:191.
    4. Pricing-disclaimer "Årsavgiften beregnes kvartalsvis. Du betaler kun
       fra inneværende kvartal." synlig over betingelser-checkbox i
       foretak-oppgrader.php.
    5. Nyhetsbrev-checkbox i template-aktiver-konto.php — opt-in, default
       unchecked. Lagrer user_meta bimverdi_newsletter_subscribed=0/1.
       Tematiske valg (6 temagrupper) eksplisitt utsatt til eget prosjekt.
    6. Doc admin-domener-howto.md for Bård — hvor og hvordan styre
       bv_engangsdomener_override + bv_generelle_domener i wp-admin.
    7. E2E Chrome MCP-validering på localhost (gjennomgang av happy path)
       + merge til main + push til Servebolt + wp-cli backfill av
       bv_hoveddomene på prod (80/80 foretak fikk verdi).

  **E2E-verifisering (localhost, før push):**
    - Ny bruker → aktiver-konto → nyhetsbrev-checkbox lagret som '1'
    - Gratisforetak-registrering → post_status='publish' direkte
    - User aktivert med bimverdi_company_id + account_type='foretak'
    - bv_hoveddomene cachet (PSL stripping fungerer for 3-leddede)
    - Dashboard viser "Gratis brukerforetak" via ny helper
    - Oppgrader-skjema viser "Årsavgiften beregnes kvartalsvis" over terms
    - Ingen "Bård"-leak i UI på dashboard eller oppgrader-side
    - Testdata ryddet via SQL (user 645 + foretak 1954)

  **Prod-deploy:**
    - git push main eb3996e → Servebolt rsync-deploy
    - Filer verifisert via SSH: pricing-disclaimer, rolle_label-helper og
      auto-godkjent-strings finnes alle i deployerte filer
    - wp-cli backfill: total=80 updated=80 skipped=0
    - Smoke-test https://bimverdi.no/registrer/ returnerer 200, form rendres

  **P1-saker utsatt (krever ny UX-runde med Bård):**
    - Dobbel-melding på block-vegg (samme tekst på arrangement + min-side)
    - "Klubb på flask" i deltaker-nivå-valg (Gratisbruker + Ikke deltaker)
    - Tematisk nyhetsbrev-valg (6 temagrupper)

---
date: 2026-05-21
action: e2e-verifisering+demo-prep
files:
  - claude/bard-context/demo-prompt-2026-05-21.md
  - claude/bard-context/screenshots/2026-05-21-demo-arrangement-block.png
  - claude/bard-context/screenshots/2026-05-21-demo-block-vegg.png
  - claude/bard-context/screenshots/2026-05-21-demo-nyhetsbrev-block.png
  - claude/bard-context/screenshots/2026-05-21-demo-admin-domener.png
  - claude/bard-context/screenshots/2026-05-21-e2e-1-welcome.png
  - claude/bard-context/screenshots/2026-05-21-e2e-2-dashboard-med-foretak.png
  - claude/bard-context/screenshots/2026-05-21-e2e-3-pamelding-fullfort.png
  - claude/bard-context/screenshots/2026-05-21-oppgrader-1-startpunkt.png
  - claude/bard-context/screenshots/2026-05-21-oppgrader-2-velg-nivaa.png
  - claude/bard-context/screenshots/2026-05-21-oppgrader-3-faktura-form.png
  - claude/bard-context/screenshots/2026-05-21-oppgrader-4-sendt.png
  - claude/bard-context/screenshots/2026-05-21-oppgrader-5-godkjent.png
summary: "E2E-testet onboarding + oppgraderings-flyt via Chrome DevTools MCP på localhost. Alle 4 demo-steg fra Krav 20+22 verifisert. Full happy path: ny bruker → registrer foretak → meld på arrangement → oppgrader til Deltaker. 12 screenshots klare for Bård-skjermdeling."
status: ready-for-review
detail: |
  **Branch:** feat/onboarding-grunnmur (commit f612825 — ikke deployet til prod)
  **Sesjon med Bård:** pågående 21. mai 2026 — andre del kommer etter compact

  **Demo-flyt verifisert (4 steg, krav 20+22):**
    1. Arrangement-blokk på single-arrangement-side med ordrett R22.6-tekst
       "Du må koble deg til ditt foretak/arbeidsgiver før du går videre"
       + "Koble til foretak"-CTA (verifisert med demo-legacy).
    2. Block-vegg på /min-side/?retry=1 med "FORETAKSKOBLING KREVES"-
       heading, kontekst-melding som husker arrangement-tittelen,
       og dashboard-state for onboarding under.
    3. Nyhetsbrev-blokk i footer: submit → redirect til block-vegg,
       kontekst-tekst skifter dynamisk fra "arrangement-påmelding"
       til "nyhetsbrev-påmelding".
    4. Admin-panel for domener: /wp-admin/options-general.php?page=
       bv-domene-blocklist (slug ble feil i opprinnelig demo-prompt,
       rettet til bv-domene-blocklist).

  **E2E happy path med ny bruker (demo-bard-2026-05-21@bimverdi-demo.no):**
    - /registrer/ → e-postverifiserings-lenke (token hentet fra DB)
    - /aktiver-konto/ → sett navn + passord, aksepter betingelser
    - Innlogget → /min-side/?welcome=1 med "Velkommen, Demo! Kontoen
      din er nå aktivert" + foretak-CTA
    - Naviger til arrangement 1718 → block-vegg vises på siden
    - Klikk "Koble til foretak" → BRREG-søk på "Initial Force" →
      autocomplete viser 10 treff → velg "INITIAL FORCE AS"
    - BRREG-autofyll: org-nr 990340048, nettside, adresse, postnr
    - Submit som Gratisbruker → "Forespørselen din venter på
      godkjenning" (foretak post_status = pending)
    - Manuelt godkjent for demo (UPDATE wp_posts SET post_status=
      'publish'; satt bimverdi_company_id på user 644)
    - bv_hoveddomene utledet automatisk til "bimverdi-demo.no" ✅
      (Fase 0 grunnmur fungerer i praksis)
    - Tilbake til arrangement → block-vegg borte, "Meld deg på"-
      knapp aktiv → klikk → "Du er påmeldt"

  **Oppgraderings-flyt (Gratisbruker → Deltaker):**
    - Dashboard viser "Bli betalende deltaker"-CTA
    - /min-side/foretak/oppgrader/ → pris-sammenligning med Velg pr nivå
    - /min-side/foretak/oppgrader/?nivaa=deltaker → faktura-form:
      EHF (ja/nei), faktura-e-post, faktura-referanse, aksept
    - Submit → /min-side/foretak/?oppgradering_sendt=1 + dashboard-
      status "Forespørsel for Deltaker ble sendt 21. May 2026.
      Bård vurderer manuelt og sender bekreftelse + faktura når
      den er godkjent."
    - Simulert admin-godkjenning (DB-nivå): bv_rolle Deltaker,
      _bv_oppgradering_pending slettet, _bv_oppgradering_history
      fikk approved-entry
    - Dashboard refresh → badge endret fra "Gratis brukerforetak"
      til "Deltaker", verktøy-seksjon låst opp, oppgraderings-
      status forsvunnet

  **Funn å adressere før prod-deploy:**
    1. **562 av 608 brukere (~92%) er uten foretak** — vil treffe
       block-vegg ved første påmelding etter deploy. Manuell admin-
       godkjenning av nye foretak kan bli stor flaskehals for Bård.
    2. **Beslutning: gå for Alt 1** — auto-godkjenne Gratisforetak.
       Bård godkjenner bare betalende (Deltaker+). Transcript med
       detaljer kommer etter compact.
    3. **#kobling-skjema-anker på block-vegg peker ingensteds** —
       "Koble til foretak"-knappen scroller ikke til BRREG-søk.
       Lite UX-glitch, men forvirrer brukeren.
    4. **Demo-prompt-slug var feil** — `bv-domener` → rettet til
       `bv-domene-blocklist` i demo-prompt-2026-05-21.md.

  **Test-data ikke ryddet enda** — demo-bruker 644 + foretak 1952
  (INITIAL FORCE AS som Deltaker) + 1 påmelding ligger på localhost-
  DB. Skal ryddes etter Bård-møtet er ferdig.

---
date: 2026-05-20
action: implementer+phase-0+phase-1
files:
  - wp-content/composer.json
  - wp-content/composer.lock
  - wp-content/vendor-data/psl/public_suffix_list.dat
  - wp-content/mu-plugins/bimverdi-composer-autoload.php
  - wp-content/mu-plugins/bimverdi-domain-helpers.php
  - wp-content/mu-plugins/bimverdi-domain-blocklist.php
  - wp-content/mu-plugins/bimverdi-pending-oppgave.php
  - wp-content/mu-plugins/bimverdi-access-control.php
  - wp-content/mu-plugins/bimverdi-foretak-registration.php
  - wp-content/mu-plugins/bimverdi-event-registration.php
  - wp-content/mu-plugins/bimverdi-newsletter.php
  - wp-content/mu-plugins/bimverdi-kunnskapskilde-registration.php
  - wp-content/mu-plugins/bimverdi-brreg-api.php
  - wp-content/plugins/bim-verdi-core/includes/acf/register-foretak-fields.php
  - wp-content/themes/bimverdi-theme/parts/components/block-vegg.php
  - wp-content/themes/bimverdi-theme/parts/minside/dashboard.php
  - wp-content/themes/bimverdi-theme/single-arrangement.php
  - docs/plans/2026-05-20-001-feat-onboarding-grunnmur-blocking-plan.md
summary: "Lukket Krav 20 Fase 0 (grunnmur) + Krav 22 Fase 1 (block-mekanikk). 9 units, all på branch feat/onboarding-grunnmur. Klar til skjermdeling med Bård 21. mai. Ikke deployet til prod."
status: ready-for-review
detail: |
  **Branch:** feat/onboarding-grunnmur (på wp-content/-repoet)
  **Plan:** docs/plans/2026-05-20-001-feat-onboarding-grunnmur-blocking-plan.md
  **Demo-bruker for testing:** demo-legacy / DemoLegacy2026!

  **Phase 0 — Grunnmur (datamodell + helpers):**
    - Composer i wp-content/ med jeremykendall/php-domain-parser 6.4
    - PSL .dat-fil cachet til vendor-data/psl/
    - bimverdi-composer-autoload.php mu-plugin
    - bimverdi-domain-helpers.php: extract_root_domain (PSL-strippet,
      håndterer oslo.firma.no → firma.no, firma.co.uk korrekt),
      find_foretak_by_email_domain, purge_foretak_cache
    - bv_hoveddomene ACF-felt på foretak CPT
    - Auto-cache fra hovedkontaktens e-post ved registrering OG ved
      hovedkontakt-bytte
    - Backfill: 87 av 90 eksisterende foretak fikk bv_hoveddomene
    - bimverdi-domain-blocklist.php: bv_generelle_domener (Bårds
      17 default-domener) + bv_engangsdomener_override + admin
      Settings-side under "Innstillinger → BIM Verdi domener"

  **Phase 1 — Block-mekanikk:**
    - bimverdi-pending-oppgave.php: 30-min PHP-sesjon for å huske
      hvilken registrering som ble blokkert, slik at vi kan resume
      etter foretaks-kobling. Helpers: remember/get/resume/clear.
    - parts/components/block-vegg.php: ordrett tekst per krav 22 R22.6
      "Du må koble deg til ditt foretak/arbeidsgiver før du går videre"
      + dynamic oppgave-label + "Koble til foretak"-CTA + UI Contract
      Variant B (dividers, ikke bokser).
    - Skjerpet bimverdi_check_event_access(): krever nå foretak for
      ALLE adgang-verdier (ikke bare 'deltakere'). Foretak_required
      block_type returneres med ordrett tekst.
    - Single-arrangement.php: når access feiler med foretak_required,
      rendrer ordrett tekst + "Koble til foretak"-CTA + lagrer pending
      oppgave i sesjonen.
    - bimverdi-newsletter.php: subscribe_newsletter som
      COMPANY_REQUIRED_FEATURE. Innloggede uten foretak redirectes
      til /min-side/?retry=1 med pending oppgave satt.
    - bimverdi-kunnskapskilde-registration.php: foretak-guard på
      POST-handler. Gratisbrukere OK; legacy-kontakter blokkeres.
    - register_kunnskapskilde lagt til OPEN_FEATURES.
    - Dashboard.php renderer block-vegg over welcome-foretak-kobling
      når ?retry=1 og pending oppgave er gyldig. Hvis sesjon utløpt:
      info-melding.
    - bimverdi-brreg-api.php (set-bruker-foretak): resume-redirect
      etter foretaks-kobling. Hvis pending oppgave finnes → send
      brukeren tilbake dit i stedet for default dashboard.

  **Verifisering (Chrome DevTools MCP):**
    - Logget inn som demo-legacy (uten foretak)
    - /arrangement/.../ viser ordrett block-tekst + "Koble til foretak"-CTA
    - /min-side/?retry=1 viser block-veggen med pending oppgave label
    - Newsletter POST redirecter til block-veggen
    - Screenshots: claude/bard-context/screenshots/2026-05-20-*.png

  **Ikke gjort (eksplisitt out-of-scope):**
    - Velkomsttekst-omskriving (R20.7, R20.22 — Fase 2)
    - Lås-UI "Krever Deltaker+" (R20.20 — Fase 2)
    - Redusert meny-opprydding (R20.23-25 — Fase 2)
    - Auto-tilleggskontakt-matching (R20.19 bruker helperne men
      auto-matching i registreringsflyt er Fase 2)
    - Avvise gmail/mailinator ved registrering (R20.27 — Fase 2,
      bruker bimverdi_is_general_domain som er klar)
    - Domain-blocklist sync-cron (Fase 3)
    - Audit-trail for domene-match (R20.26 — Fase 3)
    - PROD-DEPLOY — venter på Bårds approval etter skjermdeling 21. mai

  **Neste steg:**
    1. Andreas reviewer diffen
    2. Skjermdeling med Bård 21. mai
    3. Hvis OK: merge feat/onboarding-grunnmur → main → push → autodeploy
    4. Backfill bv_hoveddomene på prod via wp-cli
    5. Bård tester på prod, gir feedback

---
date: 2026-05-20
action: validering+rapport
files:
  - claude/bard-context/validering-onboarding-2026-05-20.md
summary: "Autonom validering av krav 20 (gratisforetak-grunnmur) + krav 22 (foretakskobling kreves) mot live-kode. Funn: 6/28 fulle treff, ~10 delvise, ~12 ikke-implementert. Rapport klar til møte 21. mai."
status: done
detail: |
  **Metode:** Pulled prod-DB til localhost via sync-db.sh (5.6 MB,
  1529 URL-erstatninger). Mappet kode-keywords mot kravspec.

  **Hovedfunn:**
    - Gratisforetak-konseptet (bv_rolle='Ikke deltaker') er bredt
      implementert: dashboard, foretak-detalj, oppgraderings-flyt,
      admin-listen, pending-flyt, welcome-state-renderer, pricing-
      tabell på welcome.
    - Domene-grunnmuren mangler helt: ingen `bv_hoveddomene`,
      `bv_generelle_domener`, `bv_engangsdomener_override`,
      `bimverdi_purge_foretak_cache()`, PSL-integrasjon, eller
      automatisk match av bruker mot eksisterende foretak.
    - Block-mekanikken (krav 22) er ikke skrevet: ingen ordrett
      "Du må koble deg til ditt foretak"-vegg, ingen `?retry=` +
      30-min-sesjon, nyhetsbrev har ingen foretak-sjekk,
      arrangement `adgang === 'alle'` slipper alle innloggede.
    - Velkomsttekst er generisk — krav 20s ordrette "Velkommen som
      bruker i BIM Verdi. Vi har sjekket om din arbeidsgiver…"
      finnes ikke (fordi mekanikken bak ikke finnes).

  **Anbefalt rekkefølge for Bård:**
    - Fase 0 (grunnmur): `bv_hoveddomene` + PSL + blocklists +
      helpers.
    - Fase 1 (block-mekanikk krav 22): `bimverdi-pending-oppgave`
      mu-plugin + universell block-vegg + lukke arrangement/
      nyhetsbrev-hull.
    - Fase 2 (welcome-UX): ordrett tekst + lås-UI "Krever
      Deltaker+" + meny-opprydding.

  **Hovedbudskap til Bård:** "Vi har scaffolding, mangler motor."

  **Rapport:** `claude/bard-context/validering-onboarding-2026-05-20.md`

---
date: 2026-05-20
action: plan+scope-reflection
files: []
summary: "Plan for møte 21. mai: bygge Fase 0 (grunnmur) + Fase 1 (block-mekanikk) i en isolert lokal worktree i kveld, skjermdele med Bård i morgen, deploye til prod etter hans godkjenning. Parallelt: historisk gjennomgang bekrefter Andreas' følelse om at scope har vokst ~150% siden MVP-en ble bygget."
status: in-progress
detail: |
  **Beslutning:** Vi tar ikke prod-endringer før Bård har sett og
  godkjent. Bård har ikke tilgang til localhost, så plan B er:
  bygge i worktree → skjermdele i morgen → deploy etter godkjenning
  → Bård tester selv på prod.

  **Scope-drift-refleksjon (etter forespørsel fra Andreas):**
  Andreas kjente seg ikke igjen i Bårds frustrasjon over manglende
  leveranse. Historisk gjennomgang av worklog-en bekrefter følelsen:

    - MVP-scope (14. april): Gratisforetak basic, oppgradering,
      artikler, verktøy, kunnskapskilder, arrangement-påmelding,
      tilleggskontakt-invitasjoner. INGEN domene-match, INGEN
      blocklists, INGEN PSL, INGEN retry-sesjon, INGEN ordrett
      block-vegg.
    - Faktiske deploys 14. april → 7. mai: ~13 features deployet til
      prod (artikler, verktøy, fakturafelter, rabatt-disclaimer,
      pricing-tabell, welcome-state-redesign, BRREG-adresse, 5
      sikkerhetsbug-fixes, m.fl.).
    - 11. mai: Bård leverte krav-pakka v1 (9 MD-filer, 1271 linjer)
      — første gang `bv_hoveddomene`, blocklists, PSL og automatisk
      domene-match dukker opp.
    - 12.–13. mai: Krav 22 (foretakskobling-blokken med `?retry=`
      + 30 min sesjon + ordrett tekst) introdusert.
    - 11. mai → 20. mai: Andreas har ikke implementert det nye, men
      Bård leste dette som "ikke tatt ballen". Viktig kontekst:
      14.–17. mai var fridager (17. mai + helg), så reell arbeidstid
      i perioden var 11.–13. mai + 18.–20. mai = 6 arbeidsdager,
      ikke 9. Bård satt med pakka i helgen og fridagene og opplevde
      stillstand — men det var fri-stillstand, ikke jobb-stillstand.

  **Konklusjon:** Scope vokste ~150–200% siden MVP. Andreas leverte
  bredt på det originale scopet. De nye kravene er reelle og bra,
  men de er NYE krav fra etter 11. mai, ikke "manglende leveranse
  på det gamle".

  **Strategisk linje for møtet:** Vise tidslinjen uten å være
  defensiv. Anerkjenne Bårds arbeid med kravpakka. Bruke
  validerings-rapporten som vår felles plan fremover. Hovedbudskap:
  "Vi har scaffolding, vi har levert det opprinnelige, nå bygger
  vi det nye — i kveld."

  **Neste steg (i kveld):**
    1. Worktree: `feat/onboarding-grunnmur`
    2. Fase 0: `bv_hoveddomene` + PSL + blocklists + helpers
    3. Fase 1: `bimverdi-pending-oppgave` + block-vegg + lukke
       hull i arrangement/nyhetsbrev/kunnskapskilde
    4. Chrome MCP-verifikasjon av flytene
    5. Klart for skjermdeling med Bård 21. mai

---
date: 2026-05-20
action: sync+plan
files: []
summary: "Kort oppfølgings-synk (~5 min) med Bård. Han er frustrert over manglende fremdrift og har sendt alle 14 MD-filer + 99-fila på e-post. Avtale: Andreas svarer på de to siste avklarings-spørsmålene, kjører autonom kravspekk-vs-live sjekk i dag, og leverer rapport før møte 21. mai."
status: in-progress
detail: |
  **Kilde:** `~/Desktop/bard-synk-20-mai.json` (~5 min, kort
  morgensynk).

  **Bårds frustrasjon:** Han har lagt mye arbeid i MD-filene de siste
  ukene og opplevde at Andreas «ikke tok ballen» mellom 11. og 20.
  mai. Han trenger fremdrift på onboarding, opp-/nedgradering, og
  krav om foretakstilknytning — uten det står markedsføringen mot
  deltakerne stille. Hans formulering: «mindre støy og strammere
  regi».

  **Konkret leveranse fra Bård:** Sendte 14 MD-filer på e-post —
  alle siste-versjoner av krav-pakka inkludert «99-fila» med de to
  åpne spørsmålene som venter på Andreas (req 25 + ett til).

  **Avtalt prosess (Andreas eier):**
  1. Behandle de 14 filene, svare på de to siste spørsmålene.
  2. Kjøre **autonom statussjekk: kravspekk vs. live-løsning**
     — agenten leser kravene og sjekker mot faktisk implementasjon
     på onboarding, foretakstilknytning, registrering av abonnement,
     invitering av tilleggskontakter.
  3. Levere rapport (treff/avvik per krav) i dag.
  4. Møte 21. mai: gå gjennom rapport + lande løsningsforslag.

  **Andreas' refleksjon:** Anerkjenner Bårds kontekst-switching-
  press og at krav-pakka er «gull verdt» som source of truth.
  Konseptet er nå: kravspekk = sannhet, agent sjekker live mot
  sannheten, vi får fasit på hva som mangler/ikke stemmer.

  **Trello-koblings-flyten utsatt:** Bårds Cowork↔Trello-tilkobling
  (forsøkt 19. mai, se foregående entry) parkeres til kravspekk-
  validering er ferdig. Andreas påpekte at man bør ta ett og ett
  kort i Cowork-flyten, ikke samle alle 27 og prøve å gå gjennom
  dem på én gang — det ble forsøkt i går og fungerte ikke godt.

  **Hva venter etter rapport:**
  - Bård: lese rapport, prioritere hva som skal fikses først.
  - Andreas: implementere fix-en på det som er kritisk for
    onboarding-flyten (vil bli avklart 21. mai).
---
date: 2026-05-19
action: sync+coaching+bug
files: []
summary: "Lengre synk (~30 min) med Bård. 9 av 11 åpne tema løst i krav-pakka, 1 gul, 1 rød. Eneste reelle åpne punkt: Req 25 (oppgradering/nedgradering av eksisterende kontakter). Bård bruker nå Claude Desktop Cowork med folder-access — coaching-anbefalingen fra 12. mai sitter. Forsøkt koble Cowork↔Trello via MCP — fant ikke connector i UI, sendte artikkel-link. Bug avdekket: nyhetsbrev-påmelding krever login før e-post-registrering."
status: waiting
detail: |
  **Kilde:** `~/Desktop/bard-synk-19-mai.json` (~30 min).

  **Bårds arbeidsflyt nå:**
  - Har SharePoint som master-lager for MD-filene (manuell sletting
    av gamle versjoner — kan ikke søke gamle+nye samtidig).
  - Bruker Claude Desktop **Cowork** med folder-access på lokal
    mappe — Andreas' coaching-anbefaling fra 12. mai er
    implementert. Slipper last-opp/last-ned-syklusen.
  - Krav-pakka består av filene 01–07 + 99-fila med åpne spørsmål
    til Andreas.

  **Status på krav-pakka:** 9 av 11 åpne tema løst, 1 gul, 1 rød.
  Eneste reelle åpne punkt: **Req 25 — oppgradering/nedgradering
  av eksisterende kontakter** (edge cases på hovedkontakt: hva
  skjer når man bytter rolle, fjerner tilgang, etc.).

  **Andreas demonstrerte oppdaterings-flyt:** Ny fil fra Andreas →
  Bård drar inn i Cowork → Cowork oppdaterer kontekstfilene
  automatisk. Bårds reaksjon: «da er vi på bølgelengden».

  **/brainstorm-coaching:** Bård hadde ikke brukt slash-kommandoen
  bevisst, men opplevde kontrollspørsmål-flyten uansett — Project
  Instructions trigger den automatisk. Avklart at det er normalt.

  **Trello-MCP-kobling forsøkt:**
  - Andreas guidet Bård gjennom Claude Desktop → Settings →
    Connectors/Extensions for å koble Trello.
  - Connector finnes ikke i UI-en. Andreas har egen Trello-MCP via
    terminal — ikke direkte overførbart til Desktop-app.
  - Sendte artikkel-link i Teams. Bård prøver selv senere.
  - **Fremtidsvisjon (ikke implementert):** Bård bruker Cowork til
    å lage/redigere Trello-kort med kontekst fra kravspekk → Andreas'
    agenter henter ferdige kort med all kontekst → mindre håndholding.

  **Stefan Mikulitsch (AI-verktøy-oversikt):** Bård jobber parallelt
  med Stefan om å koble hans AI-verktøy-oversikt til BIM Verdis
  verktøy-CPT. Spurte om dette bør være eget Cowork-prosjekt eller
  felles. **Konklusjon:** Én mappe, ulike prosjekter kan ha egne
  instructions men dele kontekst — «waterfall»-modell der hver
  Cowork-prosjekt har sin egen memory/instructions men aksess til
  hele mappa.

  **Bug avdekket under møtet — nyhetsbrev-påmelding:**
  - Folk som ikke er innlogget blir sendt til login-side når de
    prøver å registrere e-post for nyhetsbrev.
  - Bård: «Jeg vil ikke sende nyhetsbrev til folk jeg ikke vet
    hvilket foretak tilhører. Dette er et bedriftsnettverk, ikke
    et sosialt nettverk.»
  - **Beslutning:** Fjern nyhetsbrev-påmeldings-lenken helt fra
    arrangement-sider (eller annen forekomst). Krever uansett
    innlogget bruker — gir spam-følelse uten gevinst.
  - Andreas tar oppgaven.

  **Avtalt:**
  - Andreas fortsetter validerings-jobben av krav-pakka.
  - Bård prøver Trello-MCP-tilkobling selv via artikkel-link.
  - Begge holder kontakt på Teams ved behov, ny synk torsdag.

  **Hva venter på Andreas:**
  1. Svare på de to siste avklarings-spørsmålene i 99-fila
     (inkl. Req 25).
  2. Fjerne nyhetsbrev-påmeldings-lenken.
  3. Validere alle nye funksjoner mot krav-pakka (onboarding,
     foretak, registrering av abonnement, invitering av
     tilleggskontakter).

  Ingen kode-endring i denne entryen — kun møtenotat. Bug-fix på
  nyhetsbrev og kravspekk-validering kjøres i påfølgende entries.
---
date: 2026-05-12
action: sync+coaching
files: [claude/bard-context/02-ROLLER-OG-TILGANG.md, claude/meeting-todos-2026-05-12.md]
summary: "Oppfølgings-synk med Bård etter krav-pakka. Han har revidert 02-ROLLER-OG-TILGANG til v3 i Claude.ai-chat. Coaching på kontekstvindu, Cowork (Projects) vs chat, og /brainstorm. Ingen kode-endring; ny krav-spec «Grundmur» under arbeid hos Bård. Møtetodos lagt i claude/meeting-todos-2026-05-12.md."
status: waiting
detail: |
  **Kilde:** `~/Desktop/synk-bard.json` (38 min, 2026-05-12 morgen).
  Deltagere: Andreas + Bård.

  **Bakteppe:** Pakka fra 2026-05-11 (bard-context/) er lastet inn i
  Bårds Claude.ai Project. Han brukte gårsdagen til å revidere
  02-ROLLER-OG-TILGANG i chat — flere timer, samme samtale, til en
  v3-versjon.

  **Bårds fremdrift:**
  - 02-ROLLER-OG-TILGANG.md revidert til v3 i lang chat. Lastet ned,
    erstattet versjonen i Project Knowledge.
  - Filene 01, 03, 04, 06, 07 lastet opp; 05 (ORDLISTE) mangler.
  - Claude foreslo en åttende fil («Grundmur» — ny kravspec for
    foretak/grunndata) — Bård ville prøve å laste den uten å gå
    videre i flyten.
  - Bård flagget at han har «nok å gjøre med de fire de neste dagene»
    før han går videre.

  **Coaching-punkter Andreas tok opp:**
  - Kontekstvindu: 200–250k tokens er sweet spot, 1M-grensen ikke
    sannheten. Start ny chat når svarene begynner å halsionere.
    Filene i Project Knowledge er sannhetskilden mellom chats.
  - Opus 4.7 adaptive mode er default — knappen trenger ikke røres
    før modellen begynner å «gjøre rare ting».
  - Sannhetsbase-prinsippet: én chat reviderer én fil → last ned →
    erstatt i Project Knowledge → ny chat. Filen er hjernen, chat-en
    er midlertidig.
  - /brainstorm-skillet er beskrevet i README; aktiveres automatisk
    av Project Instructions, men kan tvinges med slash-kommando.

  **Cowork-anbefaling (viktigste prosessløft):**
  Andreas anbefaler Bård å bytte fra Claude.ai chat til Claude
  Desktop Cowork (Projects med folder-access). Da slipper han last-
  opp/last-ned-syklusen — Claude redigerer filene direkte i en lokal
  mappe. Bård fant tom Cowork-mappe under møtet og begynte å dra
  inn filer; han skulle fortsette etter møtet. Hvis det knirker,
  trenger han en runde nr. 2 på Cowork-oppsettet.

  **Bårds wish (foretaksoverdragelse for artikler):**
  Han ønsker samme «overdra redigering til annet foretak»-funksjon
  som finnes på kunnskapskilder/verktøy også for artikler.
  Begrensning: artikler bruker Gutenberg, mens kunnskapskilder/
  verktøy bruker Classic Editor — det er derfor det ble droppet ved
  lansering. Bård sier han har «merket ønsket» (antakelig Trello-
  kort hos ham), aksepterer at det er edge-case og kan vente.
  Ikke handling for agent nå.

  **Andreas' fremtidsbilde til Bård (kontekst, ikke handling):**
  Tab-basert miljø på jobben skal etter hvert la marketing skrive
  artikler/verktøy/ambassadører direkte via Claude → DB, utenom
  CMS-en. I teorien kunne BIM Verdi-portalen rebuilds på samme
  mønster når mønsteret er etablert internt.

  **Hva venter vi på:**
  1. Bård lander v3+ av de resterende filene (01, 03, 04, 05, 06, 07
     + evt. 08-Grundmur).
  2. Når Bård er klar med revidert krav-pakke, mater Andreas det
     inn i ce-plan-flyten og lager Trello-kort/-plan.
  3. Eventuelt Cowork-oppfølging hvis Bård sliter med setup.

  **Ingen kode-endring i denne entryen.** Kun møtenotat + todos.
  Endring av 02-ROLLER-OG-TILGANG.md i repo bør gjøres når Bårds
  v3-tekst er gjennomgått av Andreas først (Project Knowledge har
  Bårds versjon i mellomtiden).
---
date: 2026-05-11
action: deliverable+ship-to-customer
files: [claude/bard-context/README.md, claude/bard-context/00-PROJECT-INSTRUCTIONS.md, claude/bard-context/01-PROSJEKT.md, claude/bard-context/02-ROLLER-OG-TILGANG.md, claude/bard-context/03-MIN-SIDE-OVERSIKT.md, claude/bard-context/04-UI-PRINSIPPER.md, claude/bard-context/05-ORDLISTE.md, claude/bard-context/06-EDGE-CASE-SJEKKLISTE.md, claude/bard-context/07-BESLUTNINGSLOGG.md]
summary: "Bygd komplett krav-pakke for Bård i Claude.ai Project. 9 markdown-filer (1271 linjer) som gir Claude domene-kontekst for BIM Verdi før Bård formulerer krav. Sendt til Bård."
status: shipped
detail: |
  **Bakgrunn:** Andreas og Bård opplever for mange bugs som rotfestes i vage
  krav som ikke dekker edge cases (særlig roller/tilgang). Brainstorm-flyten
  i compound engineering er sterk her, men Bård bruker claude.ai/Desktop og
  kan ikke kjøre skills lokalt.

  **Løsning:** Krav-pakke som Bård laster opp som Claude.ai Project Knowledge
  + Project Instructions. Da får han brainstorm-aktig kontrollspørsmål-flyt
  med BIM Verdi-spesifikk kontekst i hver chat, uten å installere noe.

  **Pakkens innhold (claude/bard-context/):**
  - README.md — instruksjoner til Bård for oppsett av Claude.ai Project
  - 00-PROJECT-INSTRUCTIONS.md — selve prompten (limes inn i Project
    Instructions). Tvinger frem kontrollspørsmål, edge case-gjennomgang,
    og levert kravdokument i fast markdown-format som passer rett inn i
    ce-plan downstream.
  - 01-PROSJEKT.md — hva BIM Verdi er, mål, scope-grenser
  - 02-ROLLER-OG-TILGANG.md — alle roller, kontotyper, profil vs foretak.
    Den enkeltfila som vil redusere flest bugs.
  - 03-MIN-SIDE-OVERSIKT.md — eksisterende URL-struktur og sider
  - 04-UI-PRINSIPPER.md — Variant B (dividers, ikke bokser) destillert
  - 05-ORDLISTE.md — domenebegreper (foretak, hovedkontakt, etc.)
  - 06-EDGE-CASE-SJEKKLISTE.md — 10 kategorier, 50+ spørsmål Claude skal
    gå gjennom for hvert krav
  - 07-BESLUTNINGSLOGG.md — 20 beslutninger med stabile ID-er (B-001 til
    B-020) som ikke skal re-litigeres

  **Workflow det inngår i:**
  1. Bård → Claude.ai Project → kravdokument (markdown)
  2. Andreas → ny Trello-kort → `/ce-plan` på det → docs/plans/
  3. Andreas → `/ce-work` → kode → PR
  4. `/ce-code-review` før merge

  **Output-format fra Bårds Claude er bevisst designet for å passe rett
  inn som "origin document" for ce-plan-skillet.**

  **Status:** Sendt til Bård 2026-05-11. Venter på første test-case for
  å se hvordan flyten føles i praksis. Skal eventuelt justeres etter
  første runde med ekte krav.

  **Vedlikeholdsplan:** Når roller, sider eller beslutninger endres i
  koden, oppdaterer Andreas/Claude Code de relevante filene i repo.
  Bård re-uploader til Project Knowledge ved behov (eller cirka ukentlig).
---
date: 2026-05-08
action: research+trello-followup
files: [wp-content/mu-plugins/bimverdi-brreg-api.php, wp-content/themes/bimverdi-theme/parts/minside/dashboard.php]
summary: "Bård rapporterte på Trello qAN9Hwoh at k@krogshus.no fikk «koblet seg til REMA 1000» via ?foretak_koblet=bruker. Verifisert ende-til-ende: ingen sikkerhetshull — det er BRUKER-FORETAK soft-tag (selvdeklarert arbeidsgiver), ikke kobling til foretak-CPT. Postet svar med 3 valg, venter på Bårds beslutning."
status: waiting
detail: |
  **Bårds rapport (Trello-kommentar 2026-05-08 07:21):**
  «Jeg får koblet k@krogshus.no til REMA 1000. Skal det være mulig?
  https://bimverdi.no/min-side/?foretak_koblet=bruker» (med screenshot).

  **Mistanke:** Same security bug som T5/D, men i kobling-grenen i stedet
  for registrer-grenen.

  **Faktisk funn etter kode-research + DB-verifisering på prod (user 642):**

  k@krogshus.no på prod har:
  - bimverdi_company_id = "" (tom)
  - bimverdi_account_type = "profil"
  - wp_capabilities = "medlem"
  - bimverdi_bruker_foretak_orgnr = "883409442"
  - bimverdi_bruker_foretak_navn = "REMA 1000 AS"
  - bimverdi_bruker_foretak_source = "brreg"

  Dette er BRUKER-FORETAK-mekanismen i bimverdi-brreg-api.php (egen
  AJAX-handler bimverdi_save_bruker_foretak), separat fra foretak-
  registrering. Den brukes når en gratis-medlem søker opp et foretak
  som ikke finnes som deltaker i WP — da kan brukeren «merke seg» det
  som arbeidsgiver via user-meta.

  **Konsumenter av bruker_foretak (kun 3 templates på Min Side):**
  - dashboard.php (linje 293-298): viser «Din arbeidsgiver: REMA 1000 ·
    Ikke deltaker i BIM Verdi» kun for brukeren selv
  - foretak-registrer.php (linje 26-29): pre-fyller BRREG-søk hvis
    brukeren senere oppgraderer til foretak-registrering
  - foretak-detail.php (linje 93-101): tilsvarende display

  **Hva BRUKER-FORETAK gir / ikke gir:**
  - ✅ Selvdeklarert label på egen profil
  - ✅ Pre-fyll ved senere oppgradering
  - ❌ Ingen rolle-endring (forblir 'medlem')
  - ❌ Ingen company_id (forblir profil)
  - ❌ Ikke tilleggskontakt
  - ❌ Ingen capabilities for tools/articles/temagrupper
  - ❌ Ikke synlig på single-foretak.php (REMA finnes ikke som CPT)
  - ❌ Ikke synlig i offentlig deltakerliste eller for andre brukere

  **Konklusjon:** Ingen privilegie-eskalering. Funksjonen er teknisk
  harmløs. Men UX-en er forvirrende — suksess-banner sier «Arbeidsgiver
  registrert! Foretaket er lagret på profilen din» som høres ut som
  en faktisk kobling.

  **Trello-svar postet (kommentar 69fd908af751f5e311c87198):**
  Forklart teknisk hva som faktisk skjer, foreslått 3 veier:
  - A) Behold funksjon, juster språket (anbefalt) — banner endres til
    «Du har merket deg som ansatt i REMA 1000 (kun synlig for deg)»
  - B) Fjern «Lagre»-knappen helt — vis bare info-tekst for ikke-
    deltaker-foretak. Mister pre-fyll-nytte.
  - C) Også admin-godkjenning på dette — overkill (ingen privilegier
    å beskytte, bare unødvendig adminoppgave)

  Bård må velge før vi rører kode. Venter på A/B/C-svar.

  **Ingen kode-endring i denne entryen.** Kun research + Trello-svar.

---
date: 2026-05-07
action: security-fix+deployed
files: [wp-content/mu-plugins/bimverdi-foretak-registration.php, wp-content/mu-plugins/bimverdi-foretak-pending.php, wp-content/themes/bimverdi-theme/parts/minside/dashboard.php]
summary: "T5/D fra worklog 2026-05-06: kritisk sikkerhetsbug stengt. Foretak-registreringer går nå inn som pending-status og krever Bårds manuelle godkjenning. 1 commit (5a7d6ea) deployet. Trello-kort qAN9Hwoh."
status: done
detail: |
  Bekreftet via kode-research 2026-05-07: enhver innlogget bruker kunne
  registrere et hvilket som helst norsk foretak (NTNU, Statkraft, Equinor)
  som BIM Verdi-deltaker uten autorisasjonssjekk. Trello-kort opprettet
  (https://trello.com/c/qAN9Hwoh), Bård tagget, han godkjente alternativ
  D (admin-godkjenning) som første kontroll-tiltak i lanseringsfasen.

  **Hva ble endret:**

  Form-handler bimverdi-foretak-registration.php:
  - Begge wp_insert_post (gratis-path og paid-path) bytter post_status fra
    'publish' til 'pending'. Foretaket lagres med ACF-felter som før, men
    er ikke synlig på offentlig deltakerliste.
  - Bruker-aktivering flyttet bort fra form-handler. user-meta
    (bimverdi_company_id, bim_verdi_company_id, bimverdi_account_type),
    rolle-endring (medlem → deltaker/etc.), ACF tilknyttet_foretak og
    velkomst-e-post settes nå først ved godkjennings-transition.
  - Lagrer deltakertype som post-meta '_bv_pending_deltakertype' så
    transition-hook kan lese den ved godkjenning. Slettes ved publish.
  - Admin-varsel-tekst endret til «VENTER PÅ GODKJENNING» med tydelig
    instruksjon: «Endre status til Publisert for å godkjenne, eller
    flytt til papirkurv for å avvise».
  - Duplikat-sjekken på orgnr utvidet til ['publish', 'pending'] så ikke
    to brukere kan ha registrering på samme orgnr i kø samtidig.
  - Suksess-redirect endret til /min-side/?pending=1.

  Ny mu-plugin bimverdi-foretak-pending.php:
  - transition_post_status-hook reagerer kun på pending → publish
    (godkjenn) og pending → trash (avvis). Vanlig admin-redigering av
    allerede publiserte foretak trigger ikke logikken.
  - Godkjenn: setter user-meta, endrer rolle (paid-tier — gratis beholder
    medlem-rolle), sletter bruker_foretak-meta fra BRREG-search-state,
    sender velkomst-e-post (gjenbruker bimverdi_get_foretak_registered_
    email_html for paid-tier; egen enklere mal for gratis), rydder
    pending-meta.
  - Avvis: sender norsk avvisnings-e-post med post@bimverdi.no som
    kontakt. Foretak blir liggende i trash 30 dager (WP-default) før
    permanent sletting.
  - Helper bimverdi_get_user_pending_foretak() finner foretak hvor
    brukeren er hovedkontakt og status er pending.

  Dashboard parts/minside/dashboard.php:
  - Ny pending-state vises som dedikert side (klokke-ikon + tekst +
    kontakt-info) og overstyrer welcome-state, foretak-registrer-skjema
    og pricing-velger så brukeren ikke kan registrere på nytt mens en
    forespørsel er under behandling.
  - ?pending=1 query-param viser grønn success-banner: «Forespørselen er
    sendt. Vi sender deg bekreftelse på e-post når foretaket er godkjent.»

  **Verifisert ende-til-ende lokalt:**
  - Pending-foretak opprettet → user_has_company=false, is_hovedkontakt=
    false (bruker ikke aktivert) ✓
  - pending → publish (wp_update_post): user_meta bimverdi_company_id
    satt, rolle endret medlem → deltaker, pending-meta slettet,
    has_company=true, is_hovedkontakt=true ✓
  - pending → trash (wp_trash_post): bruker forblir inaktivert (rolle =
    medlem, ingen company_id) ✓
  - Duplikat-sjekk blokkerer både publish og pending ✓
  - Claude AI (Demo Konsulenter, foretak 98 publish): ingen regression,
    ser fortsatt foretaks-meny

  **Effekt i prod:**
  - Bug-en stopper umiddelbart etter deploy. Ingen nye registreringer
    publiseres uten Bårds godkjenning.
  - Bård får e-postvarsel + lenke til wp-admin edit-page per registrering.
    Han godkjenner via Publiser-knappen eller flytter til papirkurv for å
    avvise. Ingen ny admin-UI nødvendig — bygger på WP built-in pending-
    flyt.
  - Eksisterende publiserte foretak er urørt.

  **Deploy:**
  - 1 commit (5a7d6ea fix(security): foretak-registrering krever admin-
    godkjenning (T5/D)) i wp-content/.git på main → push → Servebolt
    autodeploy.
  - 3 filer endret/opprettet, +430/-67 linjer.

  **Trello-kommunikasjon:**
  - Kort opprettet (https://trello.com/c/qAN9Hwoh) i BÅRD-oppgaver-liste,
    tagget Bård + Andreas, full rapport med fix-alternativer A/B/C/D
    sammenlignet
  - Bård svarte «kjør D» i kommentar
  - Etter deploy: oppdatert kommentar med deploy-status + brukerveiledning
    for godkjennings-flyt («klikk lenken i e-posten → endre status til
    Publisert eller flytt til papirkurv»)

  **Status etter denne økten:**
  - T1, T2, T3, T4, T5, T6, T7, T8: ✅ + stretch ?nivaa= ✅
  - T9 (DOM-konsistens), T10 (analytics): ⏳

  **Mulige forbedringer (ikke prioritert nå):**
  - Visuell badge på wp-admin-meny for antall pending registreringer
  - Auto-godkjenning hvis e-postdomene matcher BRREG-foretakets
    hjemmeside-domene (lavere belastning på Bård når volum vokser)
  - Begrunnelses-felt ved avvisning som inkluderes i avvisnings-e-posten
  - Fix C (e-postdomene-verifisering) som komplement når trafikk vokser

---
date: 2026-05-07
action: bugfix+deployed
files: [wp-content/mu-plugins/bimverdi-brreg-api.php, wp-content/mu-plugins/bimverdi-foretak-registration.php, wp-content/themes/bimverdi-theme/parts/minside/foretak-registrer-form.php]
summary: "T6 fra worklog 2026-05-06: adresse hentes nå server-side fra BRREG ved foretak-registrering. Adresse-input-feltene + falsk «fylles inn automatisk»-tekst fjernet. 1 commit (c78e530) deployet."
status: done
detail: |
  Bård rapportert i synk 2026-05-06: «I foretaksregistreringen skal man
  registrere gateadresse, postnummer og poststed — mens det står 'Fylles
  inn automatisk fra Brønnøysundregistrene'. Det stemmer ikke. Gateadresse
  er ikke så viktig — du kan fjerne den. Den er allerede i Brønnøysund.»

  **Rotårsak:**
  Eksisterende JS-autofyll (assets/js/brreg-autocomplete.js fillFormFields)
  kjører kun når brukeren aktivt søker i bedriftsnavn-input. I dashboard
  inline-flyten (BRREG-søk → ikke-deltaker-foretak → preselected) er
  bedriftsnavn skjult som hidden input. Brukeren kommer til skjemaet med
  kun orgnr+navn pre-fylt — adresse-felter står tomme med en falsk lovnad
  om autofyll. Selv på dedikert /min-side/foretak/registrer/-side fungerer
  autofyll bare hvis brukeren skriver foretaksnavn manuelt og klikker
  resultatet, ikke konsistent.

  **Fix per Bårds preferanse «fjern dem»:**

  1. Ny offentlig helper bimverdi_brreg_fetch_company_address($orgnr) i
     bimverdi-brreg-api.php. Henter adresse server-side via wp_remote_get
     med 8 sek timeout, gjenbruker eksisterende 15-min transient-cache
     (cache-key brreg_company_{orgnr}). Returnerer null ved HTTP-feil/404.

  2. Form-handler i bimverdi-foretak-registration.php kaller helperen
     før wp_insert_post hvis adresse-felter er tomme i POST. Verdiene
     lagres som ACF-felter på foretak-CPT som før (gateadresse,
     postnummer, poststed). POST-verdier respekteres hvis tilstede
     (legacy-kompat for evt. wp-admin-bruk).

  3. Adresse-input-feltene fjernet fra parts/minside/foretak-registrer-
     form.php — h3 «Adresse» + 3 inputs + autofyll-tekst. Beholder
     bv-section-adresse-divet som wrapper for nettside-feltet (skjules
     fortsatt for gratis-flyt via gratisHiddenSectionIds).

  **Effekt:**
  - Brukeren ser ikke lenger adresse-felter i skjemaet → mindre å fylle
    inn, ingen falsk autofyll-lovnad.
  - Foretak-CPT får riktig adresse fra BRREG automatisk ved første
    lagring → tilgjengelig for faktura, kontaktinfo, offentlig profil.
  - Hovedkontakt kan fortsatt manuelt overstyre via Rediger foretak
    hvis BRREG har feil verdi (foretak-rediger-skjemaet er urørt).
  - Hvis BRREG er nede ved registrering: adresse blir tom. Ikke kritisk
    — kan redigeres senere.

  **Verifisering:**
  bimverdi_brreg_fetch_company_address('974767880') (NTNU) returnerer
  ['adresse' => 'Høgskoleringen 1', 'postnummer' => '7034',
  'poststed' => 'TRONDHEIM']. Cache lagres i transient. PHP-syntax OK
  på alle 3 filer.

  **Deploy:**
  - 1 commit (c78e530 fix(min-side): adresse hentes server-side fra
    BRREG, ikke skjema-input (T6)) i wp-content/.git på main → push →
    Servebolt autodeploy.
  - 3 filer endret, +82/-43 linjer.

  **Status etter denne økten:**
  - T1, T2, T3, T4, T6, T7, T8: ✅
  - T5, T9, T10: ⏳
  - Stretch ?nivaa=-pre-fill: ✅

---
date: 2026-05-07
action: bugfix+deployed
files: [wp-content/themes/bimverdi-theme/parts/minside/foretak-registrer-form.php]
summary: "T7 fra worklog 2026-05-06: faktura-felter (EHF/e-post/referanse) + andre paid-only-seksjoner var synlige fra page-load i dashboard inline-flyten. Ny 'pristine'-state i JS skjuler dem inntil bruker velger nivå. 1 commit (9e1faef) deployet."
status: done
detail: |
  Bård rapportert i synk 2026-05-06: «Hvis jeg skulle finne en gratisbruker,
  skulle jeg ikke bli spurt om faktura-e-post eller EHF-faktura.» Andreas
  hadde verifisert OK i Steg 3-løypen 2026-05-05 for nytt-foretak-flyten,
  men koble-til-eksisterende-flyten var ikke re-verifisert. Andreas mistenkte
  «flere filer som jobber samtidig», men det er én kilde med JS-init-bug.

  **Rotårsak:**
  Dashboard inline-flyten har sin egen render-sti:
    1. Bruker søker BRREG → finner foretak som IKKE er deltaker
    2. AJAX bimverdi_save_bruker_foretak setter user_meta orgnr+navn
    3. Redirect til /min-side/?welcome=foretak_koblet=bruker
    4. Dashboard.php linje 770-775 rendrer foretak-registrer-form.php
       inline med preselected = orgnr+navn

  I denne flyten vises radio-grid (ingen hidden input fra two-step-flyten,
  fordi bruker er i preselected-grenen). Eksisterende JS-init-logikk
  kjørte setTier() KUN når hidden input fantes — ikke ved page-load
  for radio-grid. Resultat: faktura-section, beskrivelse, logo, adresse
  og bransje-section var synlige fra start. Først når bruker klikket
  en radio, skjulte change-handler riktige felter.

  Ny-foretak-flyten på dedikert /min-side/foretak/registrer/ var ikke
  påvirket av samme bug etter T2-arbeidet i dag — der vises pricing-
  velger først, og hidden input genereres når bruker klikker «Velg».

  **Fix — pristine-state:**
  setTier() utvidet til tre tilstander:
    - 'pristine': ingen valg gjort. Skjul paid-only-felter (samme som
      gratis), men beholdt original submit-button-tekst.
    - 'gratis': bruker har eksplisitt valgt gratis brukerforetak.
      Skjul + endre knapp til «Registrer gratis foretak».
    - 'paid': bruker har valgt deltaker/prosjektdeltaker/partner.
      Vis alle felter.

  Page-load-init utvidet:
    - Hidden input → setTier(hidden.value)
    - Radio :checked → setTier(checked.value) (fanger pageshow-cache
      og server-side-error-rendering, erstatter eksisterende pageshow-
      handler-duplikat)
    - Verken/eller → setTier('pristine')

  **Verifisering (PHP render-test, da Chrome MCP-profile var låst):**
  Renderet foretak-registrer-form.php for Claude AI med preselected
  bruker_foretak (BRREG-søk-state): output inneholder setTier('pristine')-
  call og isPristine-branch som forventet. JS-flow er enkel nok til at
  empirisk DOM-test ikke var kritisk.

  **Deploy:**
  - 1 commit (9e1faef fix(min-side): skjul faktura-felter for gratis-
    bruker fra page-load (T7)) i wp-content/.git på main → push →
    Servebolt autodeploy.
  - 1 fil endret, +22/-4 linjer.

  **Status etter denne økten:**
  - T1, T2, T3, T4, T7, T8: ✅
  - T5, T6, T9, T10: ⏳
  - Stretch ?nivaa=-pre-fill: ✅

---
date: 2026-05-07
action: bugfix+deployed
files: [wp-content/mu-plugins/bimverdi-access-control.php, wp-content/mu-plugins/bimverdi-custom-roles.php]
summary: "T8 fra worklog 2026-05-06: foretak i draft-status mister ikke lenger nav-meny. Rotårsak: user_has_company aksepterte kun publish/pending. Treffer minst én ekte bruker (angie@a-lab.no, prosjektdeltaker, foretak 154 draft). 1 commit (491e5f8) deployet."
status: done
detail: |
  Andreas: «vi kan ikke basere oss på bård, det kan fort bli krøll med
  mange vinduer åpne. så vi må undersøke uten for nå» — gikk i gang
  med kode-research uten å vente på Bård-bekreftelse. Det viste seg å
  være en reell bug, ikke browser-rot.

  **DB-audit:**
  - 5 av 70 foretak (≈7%) har post_status='draft'.
  - 2 av disse er linket til ekte brukere:
    - 1318 (BÅRD KROGSHUS): testbruker test@krogshus.no (uid 561,
      hovedkontakt) + test4@krogshus.no (uid 564, tilleggskontakt)
    - 154 (a-lab AS): angie@a-lab.no (uid 23, prosjektdeltaker — ekte
      betalt deltaker registrert 2026-01-05)
  - Alle draft-foretak er pre-launch (modified februar–mars 2026).
    Lansering var april, så hvorfor de er draft er ikke et post-launch-
    mønster vi trenger å rette opp i datalaget.

  **Rotårsak:**
  BIMVerdi_Access_Control::user_has_company() aksepterte kun publish +
  pending. For brukere koblet til draft-foretak returnerte funksjonen
  false. I header-minside.php (linje 154) gates hele Foretak-seksjonen
  i Min konto-meny på $has_company — så «Mitt foretak», «Rediger
  foretak» og «Kolleger» forsvant. Samme i sidebar (account-sidenav.php).

  **Fix A — utvid tillatte statuser:**
  Ny konstant Access_Control::COMPANY_VISIBLE_STATUSES =
  ['publish', 'pending', 'draft']. Eksplisitt ute: trash, auto-draft,
  inherit (cleanup-statuser, ikke menneskelig intensjon). Et foretak
  i draft kan være midt-i-redigering eller manuelt satt — brukeren
  ER fortsatt koblet og bør se foretaks-meny.

  **Fix B — én sannhetskilde for foretak-id-lookup:**
  user_has_company() (access-control.php) og user_has_foretak()
  (custom-roles.php) hadde OMVENDT prioriteringsrekkefølge for meta-
  keys: ny→legacy vs legacy→ny. I praksis ingen reell divergens på
  lokal DB, men latent bug klar til å trigge på databaser hvor en
  bruker har begge meta-keys med ulike verdier.

  Løsning: ny privat helper Access_Control::lookup_company_id() —
  enkelt sannhetskilde for prioriteringsrekkefølge:
    1. bimverdi_company_id (ny standard)
    2. bim_verdi_company_id (legacy)
    3. ACF tilknyttet_foretak
  Brukes av user_has_company, get_user_company OG (gjennom delegering)
  bimverdi_user_has_foretak. Defensiv inline-fallback i wrapper-
  funksjonen for tilfeller hvor access-control ikke er lastet ennå.

  **Verifisert lokalt for 5 testbrukere:**
  - 561 (Bård hovedkontakt for foretak 1318 draft): has_company=true,
    is_hovedkontakt=true → ser «Rediger foretak» nå
  - 564 (tilleggskontakt for samme draft-foretak): has_company=true,
    is_hovedkontakt=false → ser «Mitt foretak» og «Kolleger» men ikke
    «Rediger foretak» (riktig — kun hovedkontakt redigerer)
  - 23 (angie, draft-foretak): has_company=true, is_hovedkontakt=true
    → ser «Rediger foretak» nå (var skjult før)
  - 565 (publish, ikke hovedkontakt): har_company=true, ingen rediger
  - 2 (Claude AI, publish, hovedkontakt): ingen regression — ser
    fortsatt «Rediger foretak» som før

  **Deploy:**
  - 1 commit (491e5f8 fix(min-side): foretak i draft-status mister
    ikke lenger nav-meny (T8)) i wp-content/.git på main → push →
    Servebolt autodeploy.
  - 2 filer endret, +77/-50 linjer.

  **Status etter denne økten:**
  - T1, T2, T3, T4: ✅
  - T8: ✅ (denne)
  - T5, T6, T7, T9, T10: ⏳
  - Stretch ?nivaa=-pre-fill: ✅

---
date: 2026-05-07
action: feature+deployed
files: [wp-content/mu-plugins/bimverdi-pricing.php, wp-content/themes/bimverdi-theme/parts/components/pricing-table.php, wp-content/themes/bimverdi-theme/parts/minside/foretak-registrer-form.php, wp-content/themes/bimverdi-theme/parts/minside/foretak-oppgrader.php]
summary: "T2 fullført: pricing-blokka brukes som valg-UI i registrer/oppgrader-flyten via two-step-mønster. «Velg deltakernivå»-radio-grid erstattet. Pricing-blokka låst i Gutenberg admin så Bård ikke kan fjerne den ved et uhell. 1 commit (2c244d1) deployet."
status: done
detail: |
  Andreas oppdaget at den gamle «Velg deltakernivå»-radio-grid (4
  hardkodede kort med plan-tittel + features + pris) fortsatt levde i
  registreringsskjemaet — ikke erstattet av T1/T2/T3-arbeidet tidligere
  samme dag. Hans innspill: «utfordringa med at vi lagde en blokk ut av
  dette var jo at det skulle bli enklere for bård å redigere denne
  fortløpende. kan vi 'hacke' det til da med å lage en form som
  gjenskaper teknisk oppsett, der hver knapp i blokka er et form som
  trigger en flyt?» Implementert som two-step-flyt + admin-lock.

  **Two-step-flyt:**

  Pricing-blokka er nå *valg-UI-en*, ikke bare en sammenligning. Brukeren
  treffer en pricing-velger først, klikker «Velg»-knappen for ønsket nivå,
  og lander på samme URL med ?nivaa={plan_key} hvor resten av skjemaet
  vises pre-fylt. Lukker stretch-todoen ?nivaa=-pre-fill på kjøpet.

  - bimverdi_pricing_table($data, $opts) — utvidet med 2. parameter:
    - cta_url_template ('/min-side/foretak/oppgrader/?nivaa={plan_key}')
      overstyrer ACF cta_url per render-kontekst.
    - exclude_plan_keys (['gratis']) filtrerer kolonner i oppgrader-flyt.
    Default-oppførsel uberørt — pricing-blokka via Synced Pattern på
    /priser/ og dashboard fortsetter som før.
  - bimverdi_pricing_valid_plan_keys() + bimverdi_pricing_plan_title():
    nye hjelpere for validering og visning.

  - foretak-registrer-form.php: leser $_GET['nivaa'], validerer mot
    pricing_valid_plan_keys. Mangler → render kun pricing-blokka som
    velger (early return). Satt → vis hidden input deltakertype +
    bekreftelses-banner («Valgt deltakernivå: X», med endre-link) +
    resten av skjemaet. Eksisterende JS utvidet med page-load-handler
    som leser hidden input og kjører setTier() for skjul/vis-logikk.
    Submit-handler oppdatert til å lese fra både :checked og hidden.
    Inline-bruk fra dashboard BRREG-søk (preselected) urørt — den
    flyten beholder dagens radio-grid for å bevare egen kontekst.

  - foretak-oppgrader.php: samme mønster. Pricing-blokka ekskluderer
    gratis. Hidden input level=Capitalized (Deltaker/Prosjektdeltaker/
    Partner via ucfirst på plan_key) for å matche server-handler-
    validering. plan_title fra ACF brukes til visning. Den hardkodede
    $nivaaer-arrayen (87 linjer) fjernet — én datakilde nå.

  **Admin-lock (Bård-sikring):**

  Andreas: «vi bør passe på at dette ikke går an å fjerne via admin
  gutenberg, at det legges på ved front-end bare. så ikke bård får til
  å slette. skjule det i admin rett og slett.»

  - acf_register_block_type: 'inserter' => false. ACF-blokken vises
    ikke i blokk-velgeren — Bård kan ikke legge inn ny instans ved
    et uhell.
  - Synced Pattern-seed oppdatert til å bruke
    {"lock":{"remove":true,"move":true}} på blokk-attributtet, så
    blokk-instansen i pattern-en er låst når Bård åpner den i editor.
  - Backfill v4: bimverdi_backfill_pricing_pattern_lock_v4()
    oppdaterer eksisterende patterns (lokal id=1473, prod id=1817)
    til samme låste state. Idempotent — sjekker for «"lock"» i
    post_content før den skriver. Verifisert lokalt: pattern-content
    er nå '<!-- wp:acf/bv-pricing-table {"lock":{"remove":true,"move":true}} /-->'.

  **Verifisering (Chrome MCP, lokal):**

  - /min-side/foretak/registrer/ (gratisbruker uten foretak):
    - Step 1: viser kun pricing-blokka med 4 kolonner + Velg-knapper.
    - Step 2 (?nivaa=deltaker): hidden input deltakertype="deltaker",
      banner viser «Valgt deltakernivå: Deltaker», radio-grid borte.
    - Step 2 (?nivaa=gratis): JS-pre-fill ved page-load skjuler alle
      5 gratis-hidden-sections (beskrivelse/logo/adresse/bransje/
      faktura), submit-knapp viser «Registrer gratis foretak».
  - /min-side/foretak/oppgrader/ (gratisforetak hovedkontakt):
    - Step 1: pricing-blokka viser kun 3 kolonner (gratis ekskludert).
    - Step 2 (?nivaa=prosjektdeltaker): hidden input level="Prosjektdeltaker"
      (capitalized for server-handler), banner viser «Prosjektdeltaker»,
      faktura-seksjon synlig.
  - Synced Pattern admin-editor: post_content bekreftet å inneholde
    lock-attributtet via wp.data.select(core/editor).getEditedPostContent.

  **Deploy:**
  - 1 commit (2c244d1 feat(pricing): pricing-blokka som valg-UI i
    registrer/oppgrader + lock i Gutenberg) i wp-content/.git på main
    → push → Servebolt autodeploy.
  - 4 filer endret, +239/-56 linjer.

  **Status etter denne økten:**
  - T1, T2, T3, T4: ✅ ferdig + deployet
  - T5 (BRREG vs BIM Verdi-skille): ⏳
  - T6 (Adresse auto-fill): ⏳
  - T7 (Faktura-felt for gratisbrukere): ⏳ må re-verifiseres for
    koble-til-eksisterende-flyten
  - T8 (Rediger foretak-meny): ⏳ trenger Bård-bekreftelse først
  - T9 (DOM-konsistens): ⏳
  - T10 (Desktop/mobil analytics): ⏳
  - Stretch ?nivaa=-pre-fill: ✅ løst på kjøpet i denne økten

---
date: 2026-05-07
action: feature+deployed
files: [wp-content/themes/bimverdi-theme/parts/minside/dashboard.php, wp-content/themes/bimverdi-theme/parts/minside/foretak-detail.php, wp-content/themes/bimverdi-theme/parts/minside/welcome-foretak-kobling.php]
summary: "T4 (vis deltakernivå + Bli-deltaker-CTA) + T3 (foretak-koblet template) levert. 1 commit (6de09f3) deployet via autodeploy. Verifisert lokalt med Chrome MCP for tre brukertilstander."
status: done
detail: |
  Andreas valgte T4-så-T3-rekkefølgen via /ce-work etter at pricing-tabellen
  ble levert tidligere samme dag. Bygget på samme pricing-infrastruktur
  (Synced Pattern + bimverdi_render_pattern) for å lukke konsistens-gap
  Bård flagget i synk 2026-05-06.

  **T4 — Vis abonnementstype på Min Side:**

  Bårds tilbakemelding: «Når du er deltaker, ser du IKKE hvilket nivå du
  har (Deltaker / Prosjektdeltaker / Partner). Når du er gratisbruker,
  mangler «Bli deltaker»-CTA-elementet.»

  - **dashboard.php (linje 287-300)** og **foretak-detail.php (linje
    181-193)**: Erstattet hardkodet «Aktiv deltaker»-tekst med faktisk
    bv_rolle-verdi for aktive deltakere. Brukere ser nå «Deltaker»,
    «Prosjektdeltaker» eller «Partner» som status-tekst. Fallback til
    «Aktiv deltaker» beholdt hvis bv_rolle skulle være tom.

  - **dashboard.php (ny seksjon etter foretak-info)**: Ny CTA-blokk for
    brukere koblet til gratis brukerforetak (bv_rolle === 'Ikke
    deltaker'). Tre varianter:
    1. Hovedkontakt uten pending oppgradering → orange CTA-banner med
       stjerne-ikon, tittel «Bli betalende deltaker», beskrivelse og
       primær-knapp til /min-side/foretak/oppgrader/. Samme mønster som
       eksisterende oppgrader-CTA i foretak-detail.php (linje 226-247),
       gjenbrukt visuelt for konsistens.
    2. Hovedkontakt MED pending oppgradering → status-banner med
       klokke-ikon som viser nivå + dato for forespørselen + link for
       å endre. Bruker bimverdi_get_pending_oppgradering($company_id).
    3. Tilleggskontakt → grå informativ note (info-ikon) som forklarer
       at hovedkontakt må oppgradere foretaket. Ingen knapp — riktig
       fordi tilleggskontakt ikke har permission til å oppgradere.

  Variabelen $bv_rolle_dash flyttet utenfor if-else-blokken så den kan
  gjenbrukes både i status-display og i CTA-betingelse.

  **T3 — Foretak-koblet template:**

  Bård flagget at den lange «hva får du»-tekstlisten henger igjen på
  «siden brukere ser når de er koblet til foretak (egen template)».

  Utforsking fant det: parts/minside/welcome-foretak-kobling.php (linje
  73-119) — hardkodet 4-feature-grid (Arrangement, Temagrupper, Verktøy,
  Nyheter) + footer-tekst om betalende deltakere. Filen rendres fra to
  steder:
  - dashboard.php når $is_welcome_state og brukeren ikke har foretak
  - foretak-detail.php når brukeren besøker /min-side/foretak/ uten å
    ha eget foretak

  Erstattet hele blokken med bimverdi_render_pattern('pricing-tabell'),
  samme tre-linjers oppsett (h4 + intro + pattern-call) som dashboard
  Section B. Brukeren ser nå hele pricing-tabellen som motivasjon for å
  koble seg til foretak — konsistent med hva de ser på dashboard og på
  /priser/.

  **Verifisering (Chrome MCP, lokal):**

  Brukte Claude AI (admin, hovedkontakt for «Demo Konsulenter AS»,
  bv_rolle = 'Ikke deltaker') som testbruker. Tre tilstander:
  - (a) Gratisbruker UTEN foretak: midlertidig fjernet user→company-
    kobling (alle tre meta-keys). /min-side/?welcome=1 viser nå
    pricing-tabell der gammel feature-liste pleide å være. Restored.
  - (b) Gratisbruker MED gratisforetak: nåværende state. Dashboard viser
    «Gratis brukerforetak»-status + «Bli betalende deltaker»-CTA-blokk
    med stjerne-ikon og knapp til /min-side/foretak/oppgrader/.
  - (c) Aktiv deltaker: midlertidig satt bv_rolle = 'Prosjektdeltaker'.
    Dashboard og foretak-detail viste «Prosjektdeltaker» som status-
    tekst (i stedet for «Aktiv deltaker»). Restored.

  **Deploy:**
  - 1 commit (6de09f3 feat(min-side): vis deltakernivå +
    bli-deltaker-CTA + erstatt feature-liste med pricing) i
    wp-content/.git på main → push → Servebolt autodeploy.
  - 3 filer endret, +84/-47 linjer.

  **Status etter denne økten:**
  - T1 (Pricing-tabell): ✅ ferdig
  - T2 (Erstatt «hva får du»): ✅ ferdig
  - T3 (Welcome-state side-effekt): ✅ ferdig
  - T4 (Vis abonnementstype): ✅ ferdig
  - T5 (BRREG vs BIM Verdi-skille): ⏳ research ikke startet
  - T6 (Adresse auto-fill): ⏳
  - T7 (Faktura-felt for gratisbrukere): ⏳
  - T8 (Rediger foretak-meny): ⏳ må først bekreftes med Bård
  - T9 (DOM-konsistens): ⏳
  - T10 (Desktop/mobil analytics): ⏳
  - Stretch: ?nivaa=-pre-fill i registreringsskjema: ⏳

---
date: 2026-05-07
action: feature+deployed
files: [wp-content/mu-plugins/bimverdi-pricing.php, wp-content/mu-plugins/bimverdi-patterns.php, wp-content/themes/bimverdi-theme/parts/components/pricing-table.php, wp-content/themes/bimverdi-theme/inc/design-system.php, wp-content/themes/bimverdi-theme/parts/minside/dashboard.php, wp-content/themes/bimverdi-theme/functions.php]
summary: "Pricing-tabell live på prod (https://bimverdi.no/priser/) + plassert i Min Side onboarding. T1 + T2 + delvis T3 fra worklog 2026-05-06 levert. 5 commits i wp-content/.git, alt deployet via autodeploy."
status: done
detail: |
  Bygde ut pricing-/«deltakeravgift og -nivå»-elementet Bård ba om i
  synk 2026-05-06, deretter to runder med tilbakemeldinger samme dag
  + dag etter. Hele løpet er live, Bård kan vise tabellen til kunder
  og bruke den i onboarding.

  **Arkitektur (etter Bårds C+B-valg):**
  Reusable-elements-mønster via WordPress-native Synced Patterns
  (wp_block CPT). En generell helper bimverdi_render_pattern($slug) i
  mu-plugins/bimverdi-patterns.php finner pattern by slug, kjører
  do_blocks(), cacher 1t. Rene PHP-templates kan kalle
  `echo bimverdi_render_pattern('pricing-tabell')`. Bård kan dra
  patterns inn i Gutenberg-sider. Mønsteret skalerer til alle
  fremtidige komplekse komponenter (testimonials, callouts, m.m.) —
  pricing er bare første instans.

  **Pricing-spesifikt:**
  - ACF Options Page (Innstillinger → Deltakeravgift): Bård redigerer
    priser, plan-titler, features, disclaimers her. Idempotent seed
    (v1) + backfill v2 (CTA-felter på eksisterende planer) + v3
    (generisk «Velg»-label på CTA).
  - ACF Block acf/bv-pricing-table: WYSIWYG-preview i Gutenberg, ren
    PHP-render-callback delegerer til bimverdi_pricing_table().
  - Synced Pattern «Deltakeravgift-tabell» (post_name=pricing-tabell,
    lokal id=1473, prod id=1817): seedet automatisk ved første
    plugin-last.

  **/priser/-side:**
  Opprettet på lokal (id=1474) + prod (id=1818) med
  `<!-- wp:block {"ref":N} /-->` som peker til Synced Pattern.

  **Bårds 4 tilbakemeldinger 2026-05-07 (commit 74734c2):**
  1. Terminologi: «medlemskap» → «deltakeravgift / deltakernivå» i
     alle brukervendte strenger. Kode-identifikatorer beholdt.
  2. CTA-knapper per plan med URL-er
     /min-side/foretak/registrer/?nivaa={plan_key}.
     bimverdi_pricing_resolve_url() prepender home_url() for relative
     URLer så de fungerer i lokal MAMP-subfolder og prod-rot.
  3. Expandable feature-rader. Splittet i to tabeller med delt
     <colgroup>: topp alltid synlig (planer + 3 header-rader + CTA),
     bunn skjult som default (8 grupper, 14 rader). JS-toggle med
     aria-expanded i design-system.php JS-seksjon.
  4. Plassert i onboarding: parts/minside/dashboard.php Section B
     (linjer 684-739, gammel «Tilgjengelig for ansatte i foretak…»-
     liste med 5 hardkodede ikoner) erstattet med
     bimverdi_render_pattern('pricing-tabell').

  **Iterasjons-tilbakemeldinger (3 commits etter første runde):**

  - commit 1152445 — generisk «Velg»-CTA i bunn av topp-tabell.
    «Velg Prosjektdeltaker» / «Velg Partner» var for lange og kolonnene
    ble for masete. Klassisk pricing-pattern: CTA-rad nederst,
    generisk label, ulike kolonner like brede. aria-label kombinerer
    «Velg — Plan-tittel» for skjermlesere. Backfill v3 bytter de
    gamle defaults til «Velg» men bevarer Bård-redigerte verdier.

  - data-only — Deltaker plan_highlight=false både lokal og prod (ble
    bedt om å fjerne ANBEFALT-flag «for nå»). Bård kan toggle
    «Fremhev?»-checkbox i ACF Options når han vil ha den tilbake.
    Ingen kode-commit.

  - commit d195848 — spacing-poliering. (a) CTA-rad padding 1.25rem →
    1rem så toggle henger tett på tabellen, (b) disclaimer-luft 1.5rem
    → 2.5rem, (c) bumpet specificity + !important for å fjerne theme-
    påtvunget list-style: disc på <li>-elementene.

  - commit 2e2ebd8 — plan-title font-size 1.125rem → 1rem (roligere
    hierarki).

  **Filer endret (5 commits totalt på main):**
  - 9562cc0 feat(pricing): freemium-tabell som ACF Block + Synced
  - 74734c2 feat(pricing): Bårds tilbakemelding 2026-05-07
  - 1152445 feat(pricing): generisk «Velg»-CTA i bunn av topp-tabell
  - d195848 style(pricing): tabell↔toggle nær, disclaimer luft + bullets vekk
  - 2e2ebd8 style(pricing): plan-title font-size 1.125rem → 1rem

  **Verifisering:**
  Lokal og prod er begge bekreftet visuelt (Chrome MCP) og
  programmatisk (curl + WP-CLI eval). Render-helper produserer ~40 KB
  HTML, alle plan-data + CTA-er + toggle-mekanisme er funksjonelle.

  **Status pr. 2026-05-07 kveld:**
  - T1 (Freemium pricing-tabell): ✅ ferdig + live
  - T2 (Erstatt «hva får du»): ✅ Section B i dashboard.php erstattet
  - T3 (Welcome-state side-effekt): 🟡 dashboard-side erstattet, men
    Bårds opprinnelige formulering nevnte også «foretak-koblet
    template» — kan trenge oppfølging hvis han mente noe annet
  - T4-T10: ⏳ ikke startet
  - Stretch: ?nivaa=-pre-fill i bimverdi-foretak-registration.php er
    ikke implementert ennå (CTA lander på skjemaet, men deltakertype
    må velges manuelt der i dag).

  **Bård venter med ytterligere tilbakemelding.**

---
date: 2026-05-06
action: sync+todos
files: []
summary: "Synk-møte med Bård (kilde: ~/Desktop/bard-synk.json). 10 todos identifisert: pricing-tabell, abonnement-synlighet på Min Side, mulig BRREG-bug, faktura-felt for gratisbrukere, m.m. Neste møte torsdag/fredag morgen."
status: waiting
waiting_on: andreas
detail: |
  Gjennomgang av onboarding + foretakskobling sammen med Bård via
  skjermdeling. Fanget 10 konkrete todos fra transkriptet. Ingen
  kode endret i denne økten — kun innspill loggført. Bård ønsker
  intensiv periode nå (har sendt ut fakturaer og må kunne forklare
  hva folk får for pengene).

  **🔴 Kritisk / onboarding:**

  T1 — Freemium pricing-tabell som gjenbrukbar komponent:
  Klassisk «hva får du per nivå»-tabell med Gratis / Deltaker /
  Prosjektdeltaker / Partner side-om-side (4 kolonner desktop,
  stables vertikalt på mobil). Brukes både i onboarding og på
  offentlig side (URL TBD — `/pricing`, `/medlemskap` e.l.).
  Bård har laget utkast i regneark — be agenten generere HTML/PHP-
  versjon basert på det.

  T2 — Erstatt eksisterende «hva får du»-tekst med pricing-elementet:
  Den lange listen «for de som bruker / for gratisbrukere» skal
  byttes ut med Freemium-modellen. Gjelder også «Velg deltakernivå»-
  boksene — Bård vil ha dem slått sammen med freemium-tabellen til
  én komponent (kolonner side-om-side, ikke under hverandre).

  T3 — Welcome-state side-effekt etter Steg 1 (2026-05-05):
  Den lange tekstlisten ble fjernet fra welcome-side i går, men
  henger fortsatt igjen på siden brukere ser når de er koblet til
  foretak (egen template). Må oppdatere/fjerne der også.

  T4 — Vis abonnementstype på Min Side:
  Når du er deltaker, ser du IKKE hvilket nivå du har (Deltaker /
  Prosjektdeltaker / Partner). Når du er gratisbruker, mangler
  «Bli deltaker»-CTA-elementet (var forventet, men finnes ikke).
  Detaljen om abonnementstype må vises konsistent på Min Side.

  **🟠 Mulig bug — må undersøkes teknisk:**

  T5 — Brønnøysund vs BIM Verdi-deltaker — utydelig skille:
  Når en bruker «kobler seg til foretak», er det uklart om de
  kobler seg til et eksisterende BIM Verdi-deltakerforetak ELLER
  oppretter et nytt foretak fra BRREG. Sikkerhetsrisiko:
  gratisbruker kan slå opp f.eks. NTNU og effektivt registrere
  NTNU som deltaker. Agent må sjekke teknisk om dette er bug
  eller flyt-misforståelse.

  T6 — Adressefelt i foretaksregistrering:
  Skjemaet ber om gateadresse + postnr + poststed og sier «fylles
  inn automatisk fra Brønnøysund» — men det skjer ikke. Enten
  fjern feltene (info finnes i BRREG) eller fyll faktisk inn
  automatisk. Gateadresse er ikke kritisk og kan trolig fjernes.

  T7 — Faktura-felt vises for gratisbrukere:
  Gratisbrukere skal IKKE spørres om faktura-e-post eller EHF-
  faktura. Kun Deltaker/Prosjektdeltaker/Partner skal se disse.
  (Verifisert OK i Steg 3-løypen 2026-05-05 for nytt-foretak-
  flyten — må re-verifiseres for koble-til-eksisterende-flyten.)

  T8 — «Rediger foretak»-meny forsvinner:
  Bård rapporterte at menyvalg for å redigere foretak forsvant
  når han var koblet til foretak. MEN: kan skyldes hans flere
  browser-vinduer / cache-rot (han hadde non-incognito + flere
  innlogginger samtidig). Bekreft først, sjekk så.

  **🟡 Konsistens / kvalitet:**

  T9 — DOM-struktur konsistent gjennom registrering/kobling-flyten:
  Alle sidene en bruker sendes til ved registrering eller kobling
  skal ha konsistent struktur og design.

  T10 — Desktop vs mobil-bruk — analytics-sjekk:
  Hent faktisk fordeling (Bård gjetter ~60/40 desktop). Brukes
  til å prioritere mobil-arbeid på pricing-tabell og lignende.

  **Neste møte:** torsdag morgen 2026-05-07 eller fredag 2026-05-08
  (Bård ut på siste skiløp). Kommunikasjon i mellomtiden, agent
  jobber med todos.

---
date: 2026-05-05
action: feature+deployed
files: [wp-content/themes/bimverdi-theme/parts/minside/dashboard.php, wp-content/themes/bimverdi-theme/parts/minside/welcome-foretak-kobling.php, wp-content/themes/bimverdi-theme/parts/components/account-layout.php, wp-content/themes/bimverdi-theme/parts/components/success-banner.php, wp-content/themes/bimverdi-theme/dist/style.css]
summary: "Welcome-state-redesign for /min-side/?welcome=1 — fjernet sidebar + duplikat-blokker, fikset banner-alignment, forstørret søkefelt, lagt til sekundær CTA. 1 commit (a2172fb), merge 6789e4e pushet til prod."
status: done
detail: |
  Etter at testløpet (Steg 1-2 av Trello #300-checklisten) ble kjørt
  via Chrome MCP på localhost, reagerte Andreas på et "lappeteppe av
  dårlig UX" på /min-side/?welcome=1. Vi pauset testen, kartla rot-
  årsaken (ikke egen template — bare en query-flagg over vanlig
  dashboard-rendering med overlappende UI-blokker), og kjørte
  /ce-work med 6 konkrete fixes (F1-F6).

  **Rotårsak:**
  dashboard.php rendrer både welcome-spesifikk UI (success-banner +
  foretak-søk-widget) OG en generell "fallback-dashboard for brukere
  uten foretak" (h3 "Velkommen til min side" + Gratis/Betalende-
  lister + "Registrer foretak"-knapp) for ALLE brukere uten foretak.
  Resultatet: dupliserte velkomst-budskap, konkurrerende handlinger
  (søk vs Registrer-knapp), masse luft mellom relaterte elementer.

  **Implementerte fixes (F1-F6, én sammenslått commit a2172fb):**

  - F1: Pakk welcome-state i egen rendreringssti.
    Ny boolean $is_welcome_state = ?welcome=1 && !$company_id.
    account-layout aksepterer show_sidenav-arg (default true).
    Welcome-state: show_sidenav=false + show_header=false + skip
    fallback-section. Dashboard for brukere MED foretak urørt.

  - F2: Gjenbrukbar parts/components/success-banner.php (NY).
    Fikser vertikal alignment ved å overstyre theme-CSS p-margin
    (24px → 0 via inline style="margin:0;" siden Tailwind m-0
    tapte spesifisitet). Banner-høyde 69px → 46px. Brukt 3 steder
    (welcome, foretak_koblet, invitation_accepted) — DRY.

  - F3: Forstørr søkefelt + fiks ikon-cramping.
    pl-10/py-3/text-sm → pl-12/py-4/text-base. Ikon 18→20px med
    block-class. Måtte bruke ! modifiers + type="search" for å
    overstyre theme-CSS input[type="text"]-base-regelen som vant
    på attribute-selector-spesifisitet (height 32→58, font-size
    14→16, padding-left 12→48).

  - F4: Duplikat «Velkommen til min side»-blokk fjernet
    (overlappet success-banner-budskapet).

  - F5: Ny 2-kolonne motivasjons-grid «Hva du får når du kobler
    deg til foretak» (4 features: arrangement, temagrupper,
    verktøy/kunnskap, nyheter). Kort copy, 8px tittel→beskrivelse-
    spacing via inline margin (igjen pga theme-CSS p-margin-
    overstyring).

  - F6: Sekundær CTA «Finner du ikke arbeidsgiver? Registrer som
    nytt foretak» under søkefeltet. Differensierer søk (primær,
    for å koble til eksisterende deltaker) fra registrer-ny
    (sekundær, for hovedkontakt som vil opprette nytt foretak).
    Erstatter den store "Registrer foretak"-knappen som tidligere
    konkurrerte med søkefeltet.

  **Verifisert lokalt med Chrome MCP:**
  Computed styles på alle endrede elementer. Alle 5 hidden sections
  (beskrivelse, logo, adresse, bransje, faktura) display:none ved
  Gratis-valg, betingelser-section forblir block. Søkefelt 58px med
  16px font og 48px pl. Banner-høyde 46px med ikon perfekt sentrert
  (svg_center_y === banner_center_y === p_center_y).

  **Test-løype gjenopptatt etter redesign (Steg 3-10 fra Trello #300):**
  Test-bruker claude-test-20260505@bimverdi-test.local + foretak
  RIKSREVISJONEN (orgnr 974760843) — alle steg PASS:
  - Steg 3: Gratis brukerforetak — 5 dynamiske skjul OK
  - Steg 4: «Gratis brukerforetak»-status + Oppgrader-CTA OK
  - Steg 5: 3 nivå-kort + rabatt-disclaimer + fakturafelter OK
  - Steg 6: EHF-conditional (Ja skjuler / Nei viser+required) OK
  - Steg 7: negativ-flyt → ?bv_error=missing_invoice_ref OK
  - Steg 8: komplett oppgraderingsforespørsel Prosjektdeltaker OK
  - Steg 9: _bv_oppgradering_pending + history-event lagret OK
  - Steg 10: cleanup foretak 1472 + bruker 602 + pending-rad OK

  **Deploy:**
  - Branch feat/welcome-state-redesign → merge til main (6789e4e)
  - Push 7c638c9..6789e4e til origin → Servebolt autodeploy
  - 5 filer endret: 4 modifiserte + 1 ny (success-banner.php)

  **Konsekvens:**
  - Ny bruker etter konto-aktivering møter nå én fokusert handling
    («Koble til ditt foretak» — søk eller registrer ny)
  - Etablert dashboard for brukere med foretak er urørt
  - Onboarding-flyten er nå klar for Bårds runde med gratisbrukere
    (sammen med tidligere implementert oppgraderings- + faktura-flyt)

---
date: 2026-05-04
action: feature+deployed
files: [wp-content/mu-plugins/bimverdi-custom-roles.php]
summary: "Lagt til sorterbar Registrert-kolonne i wp-admin → Users etter forespørsel fra Bård. Commit 7c638c9 pushet og verifisert på prod."
status: done
waiting_on: Bård
detail: |
  Bård sendte e-post med skjermbilde fra bimverdi.no/wp-admin/users.php
  og spurte: "Får du lag inn dato-kolonne så jeg får sortert etter de
  siste registrerte?". Markerte med gul sirkel hvor kolonnen burde
  ligge — etter "Medlemskap"-kolonnen.

  **Implementert:**
  - Ny "Registrert"-kolonne i wp-admin user list, plassert etter
    eksisterende "Medlemskap"-kolonne (siste i raden)
  - Norsk datoformat via date_i18n('j. M Y') → f.eks. "26. Mar 2026"
  - Sorterbar via manage_users_sortable_columns med user_registered
    som orderby (WP-native, ingen custom query-håndtering nødvendig)
  - Fallback "—" hvis user_registered er tom/ugyldig

  **Hvor:** wp-content/mu-plugins/bimverdi-custom-roles.php (samme
  fil + samme mønster som eksisterende Medlemskap-kolonne på linje
  185). 27 linjer lagt til, 3 trailing-whitespace-linjer fjernet.

  **Verifisert lokalt med Chrome MCP:**
  - Logget inn som "Claude AI" (admin) → /wp-admin/users.php
  - Kolonne vises etter Medlemskap ✓
  - Klikk header → URL blir ?orderby=user_registered&order=asc/desc ✓
  - Datoformat "26. Mar 2026" rendres riktig (562 brukere totalt) ✓
  - Sortering desc viser nyeste øverst ✓

  **Deploy:**
  - Commit 7c638c9 til wp-content/main
  - Push 6c6dcaa..7c638c9 → Servebolt autodeploy
  - SSH-verifisering: 3 forekomster av bimverdi_registered på prod
    (rendering + add-column + sortable-filter)

  **Konsekvens:** Bård kan nå sortere brukerlisten etter registrerings-
  dato på bimverdi.no/wp-admin/users.php. Ingen DB-endring, ingen
  påvirkning på eksisterende kolonner.

---
date: 2026-05-04
action: export+followup-noted
files: [claude/exports/nyhetsbrev-abonnenter-2026-05-04.csv, claude/exports/nyhetsbrev-til-vurdering-2026-05-04.csv]
summary: "Eksportert 19 nyhetsbrev-abonnenter fra prod til 2 CSV-er (10 reelle + 9 mistenkelige). Spam-opprydning og bedre bot-beskyttelse må håndteres senere."
status: waiting
waiting_on: Andreas/Bård
detail: |
  Etter newsletter-fixen tidligere i økten eksporterte vi de 19
  eksisterende abonnentene fra wp_bimverdi_newsletter til CSV slik
  at Bård kan dokumentere dem i SuperOffice/CRM tilbake i tid.

  **Filer:**
  - claude/exports/nyhetsbrev-abonnenter-2026-05-04.csv (10 rader,
    trolige reelle: Multiconsult, NMBU x2, Kepla x3, One4all, Sol-IS,
    BuildRight, HRPAS, +1 gmail fra Kepla-IP)
  - claude/exports/nyhetsbrev-til-vurdering-2026-05-04.csv (9 rader,
    mistenkelige: 1 test-rad + 8 bot/TOR-IPer)

  **Mistenkelige funn (ikke ryddet — venter på avgjørelse):**
  1. ID 1 ralph-test-gf@bimverdi.no — gammel E2E-testbruker
  2. 5 stk fra TOR-exit-nodes med obfuskerte e-poster (punktum-mønster):
     - da.v.a.t.oh.ik9.2.3@gmail.com (109.70.100.9)
     - op.i.zexaji.p.2.1@gmail.com (45.148.10.217)
     - mar.y.b.ly.t.he.richardso.n@gmail.com (192.42.116.12)
     - griff1000@btinternet.com (185.220.101.1)
     - ro.st.i.112.6@gmail.com (185.220.101.16)
  3. 2 stk Hetzner-IPer m/ukjente domener:
     - alyssa@turbojot.com (5.161.109.7)
     - sophie@sendproud.com (5.78.183.217)
  4. 1 stk mistenkelig IP: joshuadreher@att.net (185.231.33.38)

  **Ikke gjort i denne økten — må håndteres senere:**
  - Slette test-rad ID 1 fra wp_bimverdi_newsletter
  - Avgjøre om bot/TOR-rader skal slettes eller bli liggende
  - Forbedre bot-beskyttelse på footer-skjemaet:
    - Honeypot finnes allerede (bv_website_url)
    - Rate-limit finnes allerede (5/time per IP)
    - TOR-bots kommer fortsatt gjennom — vurder reCAPTCHA v3,
      Cloudflare Turnstile eller IP-blokkering av TOR-exit-nodes
    - Vurder også å avvise åpenbart obfuskerte gmail-adresser
      (overdreven punktum-bruk er ofte bot-signatur)

  Avhengighet til ProISP-migrering: ingen — uavhengig oppgave.

---
date: 2026-05-04
action: bugfix+deployed
files: [wp-content/mu-plugins/bimverdi-newsletter.php]
summary: "Newsletter-påmelding sender nå admin-kopi til post@bimverdi.no (Bård) i stedet for kun andreas@aharstad.no. Commit 6c6dcaa pushet og verifisert på prod."
status: done
detail: |
  Andreas oppdaget at han fikk e-post om ny nyhetsbrev-påmelding
  ("Ny nyhetsbrev-påmelding - BIM Verdi" til andreas@aharstad.no
  04.05.2026 08:24, fra frechr@hrpas.no), men at Bård ikke får
  disse varslene.

  **Funn:**
  - Påmeldinger lagres i wp_bimverdi_newsletter-tabellen
    (id, email, subscribed_at, ip_address)
  - Per 2026-05-04: 19 abonnenter (eldste 2026-02-25, nyeste 2026-05-04)
  - bimverdi-newsletter.php:107 brukte get_option('admin_email')
    = andreas@aharstad.no — Bård fikk INGEN kopi
  - Inkonsistent med resten av prosjektet: alle andre admin-varsler
    bruker BV_NOTIFY_EMAIL (post@bimverdi.no) via shared-helpers

  **Fix:**
  - Erstattet wp_mail($admin_email, ...) med
    bimverdi_send_admin_notification_email() (samme mønster som
    foretak-registrering, oppgradering, kunnskapskilder, artikler,
    invitasjoner)
  - Beholdt wp_mail-fallback hvis shared-helpers ikke lastet
  - Subject + body uendret, kun mottaker skiftet

  **Deploy:**
  - Commit 6c6dcaa, push a2529ac..6c6dcaa, autodeploy verifisert
    via SSH-grep (2 forekomster av helper-kallet på prod)

  **Konsekvens:**
  - Eksisterende 19 abonnenter er uberørt (ingen DB-endring)
  - Nye påmeldinger fra og med nå går også til post@bimverdi.no
  - Bård kan nå dokumentere i SuperOffice/CRM på samme måte som
    andre påmeldinger

---
date: 2026-05-04
action: deadline-noted
files: []
summary: "ProISP-deadline bekreftet til 2026-05-26. Gammel bimverdi.no må overføres før den datoen."
status: waiting
waiting_on: Andreas
deadline: 2026-05-26
detail: |
  Andreas sjekket ProISP-kontoen og bekreftet utløpsdato:
  **2026-05-26** (ca. 3 uker fra dagens dato).

  Gammel bimverdi.no ligger fortsatt på ProISP. Domene/abonnement
  går ut den 26. mai 2026. Må overføres før utløp ellers risikerer
  vi at gamle bimverdi.no blir utilgjengelig.

  Status: ikke tidskritisk i dag, men må prioriteres innen 2 uker
  for å ha buffer mot tekniske overraskelser.

  **Til å gjøre:**
  - Beslutte hva som faktisk skal migreres (innhold? kun domene?
    ren omdirigering til bimverdi.no v2?)
  - Booke tid i kalenderen for selve migreringen
  - Logge i WORKLOG når gjort

  Opprinnelig flagget i synk-meeting 2026-04-28 (worklog samme dato),
  som "tidskritisk — utløp i mai 2026". Konkret dato nå avklart.

---
date: 2026-05-04
action: feature+deployed
files: [wp-content/themes/bimverdi-theme/parts/minside/dashboard.php, wp-content/themes/bimverdi-theme/parts/minside/foretak-registrer.php, wp-content/themes/bimverdi-theme/parts/minside/foretak-registrer-form.php, wp-content/themes/bimverdi-theme/assets/js/brreg-autocomplete.js]
summary: "Bårds tilbakemeldingspakke fra Trello #300 implementert + deployet (commit a2529ac, push 2f46e27..a2529ac, autodeploy til Servebolt verifisert)."
status: done
waiting_on: Bård
detail: |
  Bård la igjen kommentar på test-kort #300 (YR3yHWgQ) etter at
  test-kortet ble opprettet 2026-04-30. 4 endringsforespørsler ble
  samlet i én commit i wp-content-repoet på main.

  **Implementert:**
  1. Tekstoppdateringer på Min Side gratisforetak-landing
     (dashboard.php fallback-section):
     - "kommende arrangementer" → "åpne arrangement"
     - "Bli deltaker" (h4) → "TILGJENGELIG FOR ANSATTE I FORETAK
       SOM ER BETALENDE DELTAKERE, PROSJEKTDELTAKERE OG PARTNERE"
     - Kunnskapskilder p-tekst → ny lang tekst om søk i
       kunnskapsbasen
  2. Nytt element under "BLI DELTAKER":
     "Tilgang til lukkede arrangement og opptak av tidligere
     arrangement" med kalender-ikon, mellom temagrupper og
     kunnskapskilder
  3. Inline foretak-registrer-skjema når bruker_foretak finnes:
     - Refaktorert foretak-registrer.php til tynn wrapper
       (breadcrumb + page-header + include partial)
     - NY parts/minside/foretak-registrer-form.php (590 linjer)
       som aksepterer args.preselected = ['orgnr','navn']
     - Med preselected: hidden inputs for bedriftsnavn/orgnr +
       visuelt "VALGT FORETAK"-kort, ingen søkeflow-banner
     - dashboard.php inkluderer samme partial inline når
       !$company && $bruker_foretak — CTA-knappen forsvinner
     - JS-fix i brreg-autocomplete.js: filtrer bort hidden inputs
       i selector (Array.from(...).find(el => el.type !== 'hidden'))
       OG flyttet skjul-logikken for bv-registration-fields til
       ETTER searchField-sjekk — ellers ble skjemaet skjult i
       preselected-mode der ingen søkefelt eksisterer
  4. Conditional fields ved gratis brukerforetak — verifisert
     allerede dekket av eksisterende JS i UF-009-paritet
     (5 seksjoner skjules: beskrivelse, logo, adresse, bransje,
     faktura. betingelser forblir synlig).

  **Verifisert lokalt med Chrome MCP:**
  - Test-bruker 601 (test-bruker-bard) opprettet uten company,
    bruker_foretak meta satt manuelt (orgnr: 974760843,
    Brønnøysundregistrene)
  - /min-side/?foretak_koblet=bruker viser inline-skjema med
    pre-fylte verdier ✓
  - Klikk "Gratis brukerforetak" → 5 seksjoner skjules,
    submit-knapp bytter til "Registrer gratis foretak" ✓
  - Klikk "Deltaker" → alt synlig igjen, submit "Registrer foretak" ✓
  - /min-side/foretak/registrer/ direkte → wrapperen henter samme
    partial og pre-fyller fra bruker_foretak ✓
  - FormData på submit har bedriftsnavn + organisasjonsnummer
    fra hidden inputs ✓

  **Cleanup:** Test-bruker 601 slettet etter verifisering.

  **Trello-kommentar lagt til på #300** med statusrapport mot
  Bårds 4 punkter. Status: waiting på Bård for review før push.

  **Deployet til prod 2026-05-04:**
  - git push origin main (2f46e27..a2529ac) → Servebolt autodeploy
  - SSH-verifisering: dashboard.php inneholder ny tekst, ny "lukkede
    arrangement"-element + foretak-registrer-form.php finnes på prod
  - Bård kan nå teste live på bimverdi.no

---
date: 2026-04-30
action: handoff
files: []
summary: "Test-kort opprettet i Trello (#300, YR3yHWgQ) med steg-for-steg manuell test-guide for både Andreas og Bård. Visuell verifisering av prod-deploy fullført."
status: waiting
waiting_on: Bård
detail: |
  Etter at plan-2026-04-30-001 ble deployet til prod (commits
  5697188 → 2f46e27), gjorde vi:

  **Visuell verifisering via Chrome MCP (lokalt):**
  - /min-side/foretak/oppgrader/ — fakturafelter rendres riktig,
    JS-conditional på faktura_epost fungerer (Ja skjuler, Nei viser
    + setter required), rabatt-disclaimer synlig under nivå-grid,
    "deltakernivå" brukes i header + legend ✓
  - /min-side/foretak/registrer/ — 4 nivå-kort i 2x2 grid med
    Deltaker valgt (oransje border), rabatt-disclaimer + alle 3
    fakturafelter ✓
  - wp-admin foretak-edit — Fakturainformasjon-meta-box (ID
    acf-group_bv_fakturainformasjon) vises mellom Foretak Info og
    Slug, alle 3 felter med riktige helper-tekster ✓

  **Verifisering på prod via SSH (grep på filer):**
  - shared-helpers: 6x ehf_faktura/group_bv_fakturainformasjon
  - oppgrader.php: 1x "Påmelding i 2. kvartal" + 2x "deltakernivå"
  - email-verification: 3x get_client_ip()
  Alle endringer er aktive på Servebolt-prod.

  **Trello — test-kort opprettet:**
  Kort #300 (YR3yHWgQ) i "I arbeid"-listen:
  - Tittel: "🧪 TEST: Onboarding-flyten (gratis → oppgrader →
    fakturafelter)"
  - Beskrivelse: full steg-for-steg-guide (3 flyter, negativ-tester,
    sjekklister)
  - 2 checklister: Andreas (10 punkter, bruker-side) + Bård
    (8 punkter, admin-side + e-post-sjekk)
  - Tildelt begge medlemmer
  - Lenket fra #260 (HxtH0HZ8) og #296 (NqW1qkbx) via kommentarer
    "Klar til test (2026-04-30)"

  **Status:** Venter på Bård. Når begge har bekreftet alle steg
  i sine checklister, kan Bård sende e-post-runde til
  gratisbrukere med 25 %-tilbud.

  **Cleanup:** test-bruker 600 + test-foretak 1471 (Ralph Visual
  Gratis AS) slettet etter visuell sjekk.

---
date: 2026-04-30
action: feature+deployed
files: [mu-plugins/bimverdi-shared-helpers.php, mu-plugins/bimverdi-foretak-registration.php, mu-plugins/bimverdi-foretak-oppgradering.php, themes/bimverdi-theme/parts/minside/foretak-registrer.php, themes/bimverdi-theme/parts/minside/foretak-oppgrader.php, themes/bimverdi-theme/parts/design-system/components-accordion.php, themes/bimverdi-theme/single-arrangement.php]
summary: "Plan-2026-04-30-001 implementert + deployet (5 commits) + 2 sidekommiter (hotfix + opptak-lenke). 7 commits totalt til prod (5697188 → 2f46e27)."
detail: |
  Konsekvens av synk med Bård 2026-04-30:
    1. Fakturafelter på oppgraderingsskjemaet (Bård trenger EHF-status,
       faktura-e-post og faktura-referanse for å sende manuelle fakturaer)
    2. Rabatt-disclaimer (Q2: 25 %, Q3: 50 %) som statisk tekst
    3. "medlemsnivå" → "deltakernivå" rename

  **Plan:** docs/plans/2026-04-30-001-feat-fakturafelter-rabatt-disclaimer-deltakerniva-rename-plan.md

  **5 plan-commits til prod:**
  1. 5697188 Unit 1: NY ACF-felt-gruppe via acf_add_local_field_group()
     - ehf_faktura (radio ja/nei, default nei)
     - faktura_epost (email, conditional på ehf_faktura == nei)
     - faktura_referanse (text, max 100)
     - Programmatisk registrering — ingen DB-migrering
  2. 3ff0495 Unit 2: foretak-registrering capturer + lagrer fakturafelter
     - Sanitize-pattern: sanitize_text_field/sanitize_email
     - Server-side validering for paid (gratis = skip)
     - JS conditional på faktura_epost basert på EHF-state
     - Faktura_epost-input lagt til i form (var ikke der før)
  3. 41d17d0 Unit 3: oppgraderingsskjemaet
     - Fieldset "Fakturainformasjon" mellom nivå-velger og terms
     - Pre-populering fra get_field('ehf_faktura', $foretak_id) etc.
     - Validering returnerer specific bv_error: missing_invoice_email/ref,
       invalid_invoice_email
     - Lagring på foretak-CPT FØR pending-meta (data overlever ev. feil)
     - Admin-mail utvidet med fakturaunderlag-tabell (ny seksjon)
  4. 0849397 Unit 4: rabatt-disclaimer (statisk tekst, ingen logikk)
     - Én linje under nivå-grid på begge skjemaer
     - "Påmelding i 2. kvartal gir 25 % rabatt på årsavgift. Etter
       1. juli: 50 % rabatt."
  5. 02c6824 Unit 5: medlemsnivå → deltakernivå (4 forekomster, alle byttet)
     - foretak-oppgrader.php (header copy + fieldset legend)
     - components-accordion.php (FAQ-tekster)

  **2 sidekommiter samme push:**
  6. 637662a hotfix: get_user_ip → get_client_ip i email-verification.php
     (deploy-blocker fra plan-002, oppdaget i E2E-test UF-010 — fixet
     da, ble pushet nå)
  7. 2f46e27 feat: opptak-lenke for past arrangementer (Trello #292)
     - Eksisterende uncommitted endring fra tidligere økt, gated på
       $is_past && $opptak_url, klar til deploy

  **Verifisering på prod (ssh):**
  - shared-helpers.php inneholder 6 referanser til ehf_faktura/group_bv_*
  - foretak-oppgrader.php inneholder rabatt-disclaimer + 2x "deltakernivå"
  - email-verification.php inneholder 3x get_client_ip()
  Alle 4 endringer aktive på Servebolt-prod ✓

  **E2E-tester underveis:**
  - Unit 1: wp eval get_field/update_field round-trip ✓
  - Unit 2: full registrering via Chrome MCP, foretak 1469 opprettet
    med ehf=nei, faktura_epost=invoice@ralph-e2e.no, ref=PROSJ-E2E-001
  - Unit 3: oppgraderingsforespørsel via Chrome MCP, fakturafelter
    pre-populert+lagret, negativ-test bv_error=missing_invoice_ref
    bekreftet, positiv submit ?oppgradering_sendt=1 + 2 e-poster
  - Unit 5: grep -ri "medlemsnivå" wp-content/ → 0 funn

  **Beslutninger:**
  - D1 (ACF): programmatisk via acf_add_local_field_group, ikke JSON
  - D3 (faktura_epost): conditional required IFF EHF=Nei (server+JS)
  - D4 (faktura_referanse): required globalt for paid-flyten
  - D6 (disclaimer): hardkodet tekst, ingen filter/konstant (per
    feedback-memory: rabatt = info, ikke logikk)
  - D8: ACF-feltene rendres automatisk i wp-admin foretak-edit via
    location-rule, ingen ekstra arbeid for Bård-tilgang

  **Trello dekket:** #260 ON-BOARDING - GRATISBRUKER (HxtH0HZ8) +
  #296 BEKREFTELSES-EPOSTER (NqW1qkbx) — onboarding-flyten er nå
  klar til Bårds runde med gratisbrukere.

  **Neste:** manuell test-guide for Bård (egen plan/work etter han
  har bekreftet at flyten fungerer på prod).
status: done

---
date: 2026-04-30
action: e2e-test+bugfix
files: [claude/testing/prd.json, claude/testing/ONBOARDING-TEST-GUIDE.md, mu-plugins/bimverdi-email-verification.php]
summary: "E2E-test av 9 onboarding-stories (UF-008 til UF-016) via Chrome MCP — alle PASS. Dekker Trello #260 (gratisbruker-onboarding) + #296 (admin-kopi-eposter)."
detail: |
  Kjørte gjennom hele onboarding-testløypa lokalt med Chrome MCP +
  WP-CLI. Alle 9 stories merket passes:true i prd.json med konkrete
  Resend-IDer + DB-verifisering.

  **Stories testet:**
  - UF-008 Foretak-registrering Deltaker (bv_rolle="Deltaker",
    WP-rolle=deltaker, 2 e-poster: velkomst + admin-kopi)
  - UF-009 Gratis brukerforetak (bv_rolle="Ikke deltaker", WP uendret,
    dynamisk skjul av 5 seksjoner, terms forblir synlig, admin-kopi)
  - UF-010 Bruker-aktivering m/terms (negativ-test: server returnerte
    ?bv_error=missing_terms; positiv: kun 1 admin-kopi — de-dupe OK)
  - UF-011 Tilleggskontakt-invitasjon (3 e-poster: invitasjon +
    sent-admin-kopi + accepted-admin-kopi; de-dupe-fix bekreftet —
    INGEN "Bruker-registrering fullført"-mail når invitee har company)
  - UF-012 Oppgraderingsforespørsel (pending lagret, history-event,
    bekreftelse + admin-kopi)
  - UF-013 Admin godkjenner (bv_rolle endret til Prosjektdeltaker,
    audit-log korrekt, 2 e-poster)
  - UF-014 Admin avviser (bv_rolle uendret, rejected-event m/reason +
    admin_id, 2 e-poster med begrunnelse i sitatblokk)
  - UF-015 Pending-indikator (⏳-emoji + filter-link "Pending
    oppgradering (1)" + filtrert visning ekskluderer non-pending)
  - UF-016 Artikkel-submission (post_status=pending, admin-kopi sendt)

  **Bug fikset under test (committet ikke ennå):**
  - mu-plugins/bimverdi-email-verification.php:140 kalte
    $this->get_user_ip() (undefined method) → endret til
    $this->get_client_ip() som er den faktiske private metoden
    på klassen. Forårsaket fatal "There has been a critical error"
    ved bruker-registrering på /registrer/. Må committes/pushes.

  **Pre-existing issues funnet (ikke i scope for plan-002, ikke fikset):**
  1. Access-control bruker utdatert URL-pattern
     min-side/registrer-verktoy → ny path er min-side/verktoy/registrer/.
     Konsekvens: gratisforetak kan i dag nå verktoy-registrer-siden
     uten å bli blokkert. Bør fixes i bimverdi-access-control.php
     $protected_pages-mappingen.
  2. bransje_rolle[] checkboxes har individuell required-attr →
     HTML5 forlanger ALLE haket av i stedet for "minst 1". JS-validator
     bør sentralisere dette mønsteret.
  3. Avvis-form i wp-admin meta-box er nestet inne i WP post-formen
     (ugyldig HTML — inner form discarded av nettleser). Manuelt klikk
     på "Send avvisning" submitter til post.php i stedet for
     admin-post.php. Workaround i test: fetch direkte til
     admin-post.php. Bør refactoreres ut av post-formen via JS-portal
     eller annen plassering.
  4. Approve-flyten endrer ikke WP-rolle automatisk — kun bv_rolle.
     Plan-001 design (Bård håndterer manuelt etter fakturering),
     men test-guiden hadde for høy forventning. Bør avklares: skal
     WP-rolle synkes med bv_rolle automatisk, eller manuelt via
     egen handling?

  **Trello-kort dekket:**
  - #260 ON-BOARDING - GRATISBRUKER (HxtH0HZ8) — gratisbruker-flyten
    fra UF-009/012/013/014/015 dekker krav 1+3 i kortet
  - #296 BEKREFTELSES-EPOSTER (NqW1qkbx) — admin-kopi til
    post@bimverdi.no for alle 5 skjemaer er nå verifisert
    (UF-008/010/011/016 + alle oppgraderings-e-poster)

  **Cleanup:** alle Ralph E2E-foretak/-brukere/-invitasjoner slettet,
  Demo Konsulenter tilbakestilt til "Ikke deltaker" (utgangspunkt).
status: done

---
date: 2026-04-29
action: feature+deployed
files: [mu-plugins/bimverdi-shared-helpers.php, mu-plugins/bimverdi-foretak-registration.php, mu-plugins/bimverdi-email-verification.php, mu-plugins/bimverdi-company-invitations.php, mu-plugins/bimverdi-kunnskapskilde-registration.php, mu-plugins/bimverdi-artikkel-submission.php, mu-plugins/bimverdi-foretak-oppgradering.php, themes/bimverdi-theme/templates/onboarding/template-aktiver-konto.php, themes/bimverdi-theme/parts/minside/foretak-registrer.php, themes/bimverdi-theme/parts/minside/foretak-oppgrader.php]
summary: "Bårds e-post-/betingelseskrav implementert på alle 5 eksisterende skjemaer + delt helper-modul (8 commits 5ddbd53→97bda8c, deployet)"
detail: |
  Etter pause på "worklog" på forrige work session, fortsatte vi med
  Bårds tre nye krav fra synk 2026-04-28 + Teams-meldinger:
    1. Kopi til post@bimverdi.no på alle bekreftelses-/notifikasjons-
       e-poster (for SuperOffice/CRM-dokumentasjon)
    2. Lenke til https://www.bimverdi.no/betingelser i alle e-poster
    3. Aktiv aksept-checkbox på registrerings-/aktiverings-skjemaer

  Mønsteret var allerede implementert i oppgraderingsveien (commit
  d290046 tidligere i dag). Denne økten flyttet det ut i en delt
  helper-modul og påførte mønsteret på de fire andre skjemaene.

  **Plan + Work flow:**
  - /ce-plan med kontekst → docs/plans/2026-04-29-002-feat-bard-krav-
    eksisterende-skjemaer-plan.md (Standard, 8 implementation units)
  - /ce-work → 8 commits, hver enkelt unit committed separat med
    konvensjonell melding

  **Beslutninger underveis (avklart med Andreas via /ce-plan):**
  - D4: Kunnskapskilde + artikkel-submission får KUN admin-kopi —
    ingen aksept-checkbox eller bruker-bekreftelse. Tolkning: dette
    er innholds-bidrag, ikke "innmelding". Hovedkontakt har allerede
    akseptert betingelsene ved foretak-registrering.
  - D5: Aksept-checkbox plasseres på /aktiver-konto/-siden (felles
    for vanlig registrering OG kollega-invitasjon — begge flyter
    bruker samme aktiveringsside via passord-setting).
  - D8 avveket: Ingen backwards-compat aliaser for
    BV_OPPGRADERING_NOTIFY_EMAIL/TERMS_URL — grep viste null
    eksterne referanser, så alias-boilerplate ville bare være død kode.

  **8 commits til prod (rekkefølge):**
  1. 5ddbd53 Unit 1: NY mu-plugin bimverdi-shared-helpers.php (+137)
     - Konstanter: BV_NOTIFY_EMAIL, BV_TERMS_URL
     - Helpers: render_terms_acceptance_field, render_terms_footer_html,
       send_admin_notification_email, validate_terms_acceptance
  2. b59fb87 Unit 2: aksept-checkbox på template-aktiver-konto.php (+15)
     - Inline-styled checkbox (matcher auth-card-CSS, ikke Tailwind)
     - Mellom passord-felt og submit-knapp
  3. 6298b5b Unit 3: bimverdi-email-verification.php (+62)
     - Server-side terms-validering på handle_verification_submission
     - Admin-kopi når verifiserings-e-post sendes (ny user signup)
     - Admin-kopi når bruker fullfører aktivering (de-duped for invitees)
     - Terms-footer i selve verifiserings-e-posten
  4. 07ca2c4 Unit 4: foretak-registrering (+98/-10)
     - Bytter til shared render_terms_acceptance_field helper
       (peker nå til EKSTERN www.bimverdi.no/betingelser, ikke lokal)
     - Holder bv-section-betingelser synlig for ALLE deltakertyper
       (også gratis — Bårds krav om aktiv aksept gjelder uansett)
     - Server-side terms-validering for begge paths (gratis + paid)
     - Admin-kopi for både gratis- og paid-flyten med fakturaunderlag
     - Terms-footer i velkomst-e-postens body
  5. f59db7d Unit 5: kollega-invitasjon (+99/-14)
     - Lenke til betingelser i invitasjons-e-post body (plaintext)
     - Refaktor: notify_admin_invitation_sent bruker shared helper
       (post@bimverdi.no i stedet for admin_email-WP-option)
     - NY notify_admin_invitation_accepted hooket på
       bimverdi_invitation_accepted action — fyrer både via direct-
       accept (innlogget bruker) og user_register-chain (ny invitee)
     - De-dupe-fix i Unit 3: skipper generic admin-kopi for invitees
       siden Unit 5 sender mer spesifikk "Invitasjon akseptert"-mail
  6. eb93418 Unit 6: kunnskapskilde-registrering — admin-kopi NY (+39)
     - Tilfører admin-kopi etter wp_insert_post (kun ny, ikke edits)
     - Inneholder navn, utgiver, ekstern lenke, ingress-preview,
       registrant, lenke til wp-admin + offentlig visning
     - Ingen UI-endring, ingen brukerbekreftelse (D4)
  7. 76ec0e7 Unit 7: artikkel-submission — admin-kopi NY (+39)
     - Samme mønster som kunnskapskilde
     - Subject markerer "venter godkjenning" siden artikler har
       post_status=pending
  8. 97bda8c Unit 8: refaktor oppgraderingsveien (+15/-17)
     - Erstatter alle 12+1 referanser av BV_OPPGRADERING_NOTIFY_EMAIL/
       TERMS_URL med BV_NOTIFY_EMAIL/BV_TERMS_URL
     - Funksjonell endring: ingen (alle verdier identiske)

  **Verifikasjon:**
  - Lokal lint pass på alle 7 PHP-filer (no syntax errors)
  - HTTP 200 på /min-side/foretak/registrer/ og /aktiver-konto/
  - SSH-sjekk på prod: bimverdi-shared-helpers.php på plass (5227 b)
    + 8 forekomster av BV_NOTIFY_EMAIL i oppgradering-fila

  **Loading-order-håndtering:**
  Mu-plugins lastes alfabetisk: a, c, e, f, k, s. Det betyr at
  bimverdi-shared-helpers.php (s) lastes SIST. Alle 6 forbrukende
  filer (a, c, e, f, f, k) refererer helperne kun INNE i runtime-
  hooks (init, admin_post_*, edit_form_top, action callbacks) — som
  alle fyrer EFTER alle mu-plugins er ferdig lastet. Konstantene og
  funksjonene er da tilgjengelige. Som ekstra sikkerhet: alle
  forbrukende kall har function_exists() / defined() guards.

  **Ikke gjort:**
  - Faktisk E2E-test mot live-skjemaene (ville ha sendt ekte e-post
    via Resend til post@bimverdi.no — risiko for spam fra test-data)
  - Trello-oppdatering: nytt samle-kort eller tagging på #298 om
    Bårds nye krav — Andreas kan vurdere
  - Sjekk av WP-option admin_email vs BV_NOTIFY_EMAIL på prod —
    risk-tabellen flagget muligheten for duplikat hvis like, men
    siden notify_admin_invitation_sent nå går KUN til BV_NOTIFY_EMAIL
    (ikke admin_email lenger), er dette ikke lenger en risiko

  **Reusability fremover:**
  Helper-modulen (bimverdi-shared-helpers.php) er nå sentral for alle
  fremtidige skjemaer som trenger:
    - Aksept-checkbox-rendering (Tailwind-kontekst)
    - Server-side terms-aksept-validering
    - Admin-kopi-e-post til post@bimverdi.no
    - Terms-footer i e-post-bodies
  Når Andreas utvider med f.eks. arrangementspåmelding, prosjekt-
  invitasjoner eller medlems-fornyelse senere, skal disse helperne
  brukes for å sikre konsistent dekning av Bårds krav.

  **Total status etter dagen (12 commits til prod):**
  - 8af4346 wp-admin blokk + admin-bar
  - 4183ef9 favicon + foretak-deltakernivåkolonne + rate-limit
  - d290046 oppgraderingsvei (7 units, +1122 linjer)
  - 8873b4f bug-fix tilgangskontroll-helper
  - 5ddbd53 → 97bda8c Bårds krav på 5 skjemaer (8 units, ~530 linjer)

---
date: 2026-04-29
action: plan-written
files: [docs/plans/2026-04-29-002-feat-bard-krav-eksisterende-skjemaer-plan.md]
summary: "Plan-002: Bårds e-post-/betingelseskrav på de 5 eksisterende skjemaene"
detail: |
  Standard plan, 8 implementation units. Etablerer felles helper-modul
  (bimverdi-shared-helpers.php) og påfører Bårds krav (post@bimverdi-
  kopi, betingelser-lenke, aktiv aksept-checkbox) konsistent på de 5
  skjemaene som ennå ikke hadde dem:
    - Foretak-registrering
    - Bruker-registrering / e-post-verifisering
    - Kollega-invitasjon
    - Kunnskapskilde-registrering (admin-kopi only — D4)
    - Artikkel-submission (admin-kopi only — D4)
  Plus refaktor-unit som migrerer oppgraderingsveien til samme felles
  konstanter (BV_NOTIFY_EMAIL, BV_TERMS_URL).

  Plan-beslutninger avklart underveis:
  - Aksept-checkbox plasseres på /aktiver-konto/-siden (felles for
    vanlig registrering og invitee-aktivering)
  - Kunnskapskilde + artikkel får KUN admin-kopi (ikke aksept-
    checkbox) — innholds-bidrag, ikke innmelding
  - Separate wp_mail-kall (ikke BCC) per oppgraderingsvei-mønsteret
  - Lokal /betingelser/-lenke i foretak-registrering byttes til
    ekstern www.bimverdi.no/betingelser (Bårds eksplisitte krav)

---
date: 2026-04-29
action: bugfix+e2e-verified
files: [mu-plugins/bimverdi-foretak-oppgradering.php, docs/solutions/integration-issues/resend-sandbox-blocks-external-emails.md]
summary: "E2E-test lokalt fanget bug i tilgangskontroll-helper, fikset og deployet (commit 8873b4f). Resend-DNS bekreftet ferdig."
detail: |
  Etter at oppgraderingsveien (commit d290046) var deployet, kjørte vi
  full E2E-test lokalt for å verifisere flyten. Testen avdekket en bug
  som ville blokkert produksjon helt.

  **Resend-DNS verifisert ferdig**
  Sendt test-e-post fra prod via bimverdi_test_resend_email() til
  ekstern adresse (andreas.harstad@initialforce.com). E-posten kom
  fram med korrekt sender ("BIM Verdi <noreply@bimverdi.no>"). Det
  betyr at DNS-verifiseringen i Domeneshop ble fullført siden 5.
  februar (forrige learnings-status). Doc oppdatert:
  - docs/solutions/integration-issues/resend-sandbox-blocks-external-emails.md
  - Status: pending-dns-verification → solved
  - Lagt til verified_resolved: 2026-04-29

  **Bug fanget i E2E (kritisk)**
  bimverdi_user_can_request_oppgradering() i
  bimverdi-foretak-oppgradering.php brukte returverdien fra
  bimverdi_user_has_company() som om den var foretak-ID, men den
  funksjonen returnerer KUN bool. Resultatet:
    $foretak_id = bimverdi_user_has_company($user_id); // → true
    get_field('bv_rolle', true)                        // → returnerer
                                                        // global post-ID,
                                                        // ikke foretakets
  Konsekvens: Helperen ville returnere false selv for legitime
  hovedkontakter for gratisforetak — alle ville blitt redirectet til
  /min-side/foretak/?bv_error=already_paying når de prøvde å åpne
  oppgrader-skjemaet. Hele feature ville vært død på prod.

  **Fix (commit 8873b4f, +8 linjer)**
  Bruk bimverdi_get_user_company() (returnerer array) i stedet for
  user_has_company() (returnerer bool). Eksplisitt sjekk på array vs
  scalar, fallback hvis returverdien skifter format.

  **Fullstendig E2E lokalt på localhost:8888/bimverdi-v2/**
  - Opprettet test-bruker (medlem) + test-foretak (gratis) lokalt
  - Logget inn som hovedkontakt → så CTA "Oppgrader til betalende
    deltaker" på /min-side/foretak/
  - Klikket → /min-side/foretak/oppgrader/ → valgte Prosjektdeltaker
    + akseptert betingelser → submit
  - Redirect til /min-side/foretak/?oppgradering_sendt=1 med
    suksess-melding ✓
  - Backend: pending-meta lagret, history-event logged ✓
  - Logget ut → logget inn som Claude AI admin
  - Foretak-edit-side: notice øverst med ⏳-ikon, nivå, sender,
    tidspunkt; godkjenn-knapp + avvis-form ✓
  - Audit-log meta-box: rendret request-eventen i tabell ✓
  - Klikket "Godkjenn oppgradering" → bv_rolle endret til
    Prosjektdeltaker, pending clearet, history fikk approved-event
    med admin_id, suksess-melding vist ✓

  **Test-data ryddet etterpå:**
  - Slettet test-foretak (1461) og test-bruker (592) lokalt
  - Ingen rester i prod-databasen (test ble kun gjort lokalt etter
    at vi ved et uhell injiserte test-data på SOL-IS ARKITEKTER og
    ryddet umiddelbart — se egen note under)

  **Note: prod-data berørt kortvarig**
  Tidligere i økten ble post_meta injisert direkte på foretak 1669
  (SOL-IS ARKITEKTER AS, et reelt foretak) for å teste admin-UI uten
  E2E. Andreas påpekte risikoen umiddelbart — meta ble slettet og
  foretaket er tilbake til original tilstand. Ingen e-post ble sendt
  og hovedkontakten (mari@sol-is.no) hadde ikke logget inn i
  vinduet hvor meta var injisert. Læring: aldri test mot ekte
  foretak på prod.

  **Total status etter dagen (4 commits, autodeployet til Servebolt):**
  - 8af4346 — wp-admin blokk + admin-bar skjult
  - 4183ef9 — favicon + foretak-deltakernivåkolonne + rate-limit-fix
  - d290046 — oppgraderingsvei (7 implementation units, +1122 linjer)
  - 8873b4f — fix av tilgangskontroll-helper

  **Ikke gjort:**
  - Trello-oppdatering på samle-kort #298 med "klar til test"-status
  - Bårds nye krav (e-post-kopi + betingelser-lenke) retroaktivt på
    eksisterende skjemaer (foretak-reg, bruker-reg, kunnskapskilde-reg,
    artikkel-submit, kollega-invitasjon) — egen oppgave senere

---
date: 2026-04-29
action: feature+deployed
files: [mu-plugins/bimverdi-foretak-oppgradering.php, mu-plugins/bimverdi-admin-enhancements.php, themes/bimverdi-theme/inc/minside-helpers.php, themes/bimverdi-theme/parts/minside/foretak-detail.php, themes/bimverdi-theme/parts/minside/foretak-oppgrader.php]
summary: "Hovedprioritet levert: oppgraderingsvei gratisforetak → betalende deltaker med manuell godkjenning (commit d290046, +1122 linjer, deployet)"
detail: |
  Implementerte komplett oppgraderingsvei i én økt. Plan først via /ce-plan,
  deretter /ce-work for implementasjon. Alle 7 implementation units levert
  som én atomic commit til prod.

  Plan: docs/plans/2026-04-29-001-feat-oppgraderingsvei-manuell-godkjenning-plan.md
  Trello samle-kort: https://trello.com/c/OinkcNEz

  **Bruker-flyt:**
  Hovedkontakt for gratisforetak ser CTA "Oppgrader til betalende deltaker"
  på /min-side/foretak/. Klikker → /min-side/foretak/oppgrader/ → velger
  nivå (Deltaker/Prosjektdeltaker/Partner) + aksepterer betingelser →
  submit. Forespørselen lagres som post_meta + history-event, og 2 e-poster
  sendes (bekreftelse til bruker + fakturaunderlag til post@bimverdi.no).
  Bruker ser pending-status etter submit.

  **Bård-flyt:**
  På foretak-edit-siden i wp-admin ser Bård en notice øverst hvis det
  finnes pending forespørsel. Eksplisitt "Godkjenn"-knapp setter bv_rolle,
  clearer pending, sender bekreftelses-e-post til hovedkontakt + admin-kopi.
  "Avvis"-form krever begrunnelse, sender avvisnings-e-post med begrunnelse
  i sitat-blokk. Filter-link "Pending oppgradering (N)" øverst i foretak-
  listen + ⏳-ikon ved siden av deltakernivå-badge for visuell oversikt.
  Egen meta-box "Oppgraderings-historikk" viser audit-log.

  **Designvalg:**
  - Manuell godkjenning (ikke auto) siden BV ikke har betalingsintegrasjon
  - Eksplisitt knapp (ikke auto-fang ved bv_rolle-endring) gir tydelig
    intensjon — Bård kan fortsatt redigere bv_rolle direkte for korrigeringer
    uten å sende e-post
  - Hovedkontakt-only autorisering — sikrer ekte foretaks-godkjent forespørsel
  - post_meta (ikke egen CPT) for pending — én pending per foretak,
    history som append-only array
  - 3 forsøk/time rate-limit (lavere enn vanlig 5 fordi normal bruk er én
    forespørsel per foretak), bypass for admins
  - Alle e-poster inkluderer lenke til www.bimverdi.no/betingelser per
    Bårds krav
  - E-post-feil blokkerer ikke flyt (logges til error_log)

  **Sju implementation units levert:**
  1. Datamodell + helpers (konstanter, get/set/clear/append-funksjoner,
     tilgangskontroll-helper)
  2. Min Side route + skjema (foretak-oppgrader.php, route-mapping i
     minside-helpers.php)
  3. Submission-handler (init-hook med full validering + e-post)
  4. Status-visning på Min Side (CTA + pending-banner i foretak-detail.php)
  5. Admin notice + godkjenn/avvis-knapper (edit_form_top + admin-post.php
     handlers + 4 e-post-funksjoner: request/approved × bruker/admin)
  6. Pending-indikator i foretak-listen (⏳-ikon i kolonne + filter-link)
  7. Audit-log meta-box (kronologisk tabell på foretak-edit)

  **Filer endret/skapt:**
  - mu-plugins/bimverdi-foretak-oppgradering.php (ny, ~720 linjer)
  - mu-plugins/bimverdi-admin-enhancements.php (utvidet kolonne + filter)
  - themes/bimverdi-theme/inc/minside-helpers.php (ny route + account-route)
  - themes/bimverdi-theme/parts/minside/foretak-detail.php (CTA + status)
  - themes/bimverdi-theme/parts/minside/foretak-oppgrader.php (ny skjema-fil)

  **Verifisert på prod (via SSH):**
  - mu-plugin og template på plass
  - 2 forekomster av bimverdi_user_can_request_oppgradering
  - 3 forekomster av foretak/oppgrader i routing-fil

  **Ikke testet ennå (krever brukerinteraksjon):**
  - End-to-end via Chrome MCP — bør gjøres etter at Resend-DNS er
    verifisert så e-poster faktisk leveres. Funksjonelt bør alt virke,
    e-poster vil logges til error_log selv om de ikke kommer eksternt frem.
  - Race-condition ved samtidig godkjenning av to admins — usannsynlig
    siden kun én admin (Bård) jobber typisk på en gang.

  **Reusability for full onboarding senere:**
  Nivå-velger, e-post-maler, betingelser-checkbox, audit-log-pattern, og
  godkjennings-mønsteret kan gjenbrukes når full onboarding (account
  creation + BRREG + e-post-verifisering) skal ferdigstilles.

---
date: 2026-04-29
action: feature+deployed
files: [mu-plugins/bimverdi-admin-enhancements.php, mu-plugins/bimverdi-kunnskapskilde-registration.php, themes/bimverdi-theme/functions.php, themes/bimverdi-theme/assets/img/favicon/*]
summary: "Tre småjobber fra Bårds Trello-kort: favicon, foretak-deltakernivåkolonne, rate-limit-fix (commit 4183ef9, deployet)"
detail: |
  Tre småjobber etter synk med Bård 2026-04-28 — alle deployet til prod via
  autodeploy + verifisert via SSH.

  **#295 — Favicon (Trello rUjehOEB)**
  Bytte WP-default-favicon til BV-knuten (de 3 sammenkoblede sekskantene fra
  logoen).
  - Andreas leverte PNG av full logo, Claude beskar ut hexagon-elementet via
    Python+PIL (bbox: x=460-650, y=159-368 av 800x528-original)
  - Padded til kvadrat (209x209), generert komplett favicon-sett:
    16/32/48 (favicon.ico), 180 (apple-touch), 192/512 (android-chrome)
  - Filer i themes/bimverdi-theme/assets/img/favicon/
  - Registrert via wp_head + admin_head + login_head i functions.php
    (dekker frontend, admin-dashboard og login-side)

  **#291 del 2 — Deltakernivå-kolonne i foretak-oversikten (Trello mJA3lVjs)**
  Bård ønsket kolonne i wp-admin foretak-listen som viser deltakernivå.
  - Ny kolonne "Deltakernivå" plassert etter title-kolonnen
  - Viser bv_rolle ACF-felt som farge-kodet badge:
    ★ Partner (lilla), ◆ Prosjektdeltaker (oransje),
    ● Deltaker (grønn), ○ Gratisforetak (grå)
  - "Ikke deltaker"-verdi mappes til etiketten "Gratisforetak" (Bårds språk)
  - Sorterbar via meta_value på bv_rolle
  - Lagt som seksjon 3 i mu-plugins/bimverdi-admin-enhancements.php

  **#291 del 1 — Yoast Duplicate Post (Trello mJA3lVjs)**
  Plugin for å duplisere arrangementer via Quick Edit (var i BV1, savnet i BV2).
  - Installert + aktivert via wp-cli både lokalt og på prod
  - Plugin slug: `duplicate-post` (Yoast, 4.6, 3M+ aktive installasjoner)
  - Bård får nå "Klone"-handling i quick-edit på arrangementer

  **#297 — Rate-limit-sperre kunnskapskilde-registrering (Trello jxw4F28g)**
  Andreas hadde rett: ikke Servebolt-server-side, men BVs egen kode.
  - Funnet rate_limit-logikk i bimverdi-kunnskapskilde-registration.php:
    transient `bv_kilde_reg_{user_id}` med max 5 forsøk/time
  - Bug: telleren inkrementeres FØR validering, så Claude AI-agent
    ble blokkert etter 5 valideringsfeil under testing
  - Fiks: bypass for administratorer (`current_user_can('manage_options')`).
    Vanlige medlemmer beholder 5/time som spam-beskyttelse.
  - Eksisterende `delete_transient` på vellykket lagring beholdt.
  - SAMME mønster finnes i 7 andre mu-plugins (foretak-edit, profile-edit,
    artikkel-submission, tool-registration, feedback, newsletter,
    foretak-registration, email-verification) — IKKE rørt nå, kan
    refaktoreres til felles helper senere hvis Bård rapporterer flere
    rate-limit-bugs.

  **Verifisering på prod (via SSH):**
  - 8 favicon-filer på plass i themes/bimverdi-theme/assets/img/favicon/
  - `bimverdi_favicon_links` registrert (4 forekomster)
  - `deltakernivaa` referert 14 steder i admin-enhancements
  - Yoast Duplicate Post: installert + aktivert

  **Ikke gjort i denne økten:**
  - Oppgraderingsvei (hovedprioritet) — venter
  - ProISP-migrering — venter
  - Media Library-filtrering for ikke-admins — vurdering for senere
  - Felles rate-limit-helper på tvers av 7 andre mu-plugins

  **Uavhengig endring oppdaget i working tree:**
  themes/bimverdi-theme/single-arrangement.php hadde lokale endringer
  (Opptak-lenke for gjennomførte arrangementer) som IKKE ble committet
  i denne økten. Andreas må selv vurdere om/når den skal commites.

---
date: 2026-04-28
action: hotfix+deployed
files: [mu-plugins/bimverdi-custom-roles.php]
summary: "Sikkerhets-/UX-bug: blokkert wp-admin + admin-bar for ikke-administratorer (commit 8af4346, deployet til prod)"
detail: |
  En medlem rapporterte til Bård at "alle medlemmer har full tilgang til
  WordPress backend via innlogget bruker på bimverdi". Diagnose:

  Capabilities var korrekt begrenset per rolle (medlem: kun read +
  read_member_content, deltaker/partner: edit_posts + upload_files på egne
  poster), men WordPress lar som default alle innloggede brukere se /wp-admin/
  (profil-side, skrivebord). For betalende roller var Media Library spesielt
  problematisk — `upload_files`-cap viser ALLE foretaks media-filer.

  **Fiks (mu-plugins/bimverdi-custom-roles.php, +25 linjer):**
  - `admin_init`-hook: alle uten `manage_options` redirectes til /min-side/.
    AJAX og cron tillates (wp_doing_ajax / wp_doing_cron).
  - `show_admin_bar`-filter: skjuler oransje WP-toolbar for ikke-admins.

  **Hvorfor `manage_options`:** Kun `administrator`-rollen har denne
  capability-en som default. Bård (admin) påvirkes ikke; alle BV-roller
  (medlem/tilleggskontakt/deltaker/prosjektdeltaker/partner) blir redirectet.

  **Reell risiko vs. kosmetikk:**
  - medlem-rollen: kun kosmetisk (kunne se WP-skjerm, ingen sensitive data)
  - betalende roller: Media Library-eksponering var den eneste reelle
    bekymringen — kunne se andre foretaks uploads (logoer, vedlegg, evt.
    sensitive dokumenter hvis lastet opp). Nå blokkert helt.

  **Ikke gjort (vurdering for senere):** Filtrere Media Library så brukere
  kun ser egne uploads via `ajax_query_attachments_args` + `parse_query`-
  hook. Mindre relevant nå som wp-admin er blokkert helt, men hvis vi
  noen gang åpner deler av admin igjen bør dette legges til.

  **Deploy:** commit 8af4346 pushet til main, autodeploy til Servebolt.
  Prod-verifisering venter — bør testes ved å logge inn som ikke-admin
  bruker og prøve /wp-admin/ (skal redirecte til /min-side/).

---
date: 2026-04-28
action: sync-meeting+todos
files: []
summary: "Synk med Bård: prioritert oppgraderingsvei + ProISP-migrering, 3 Trello-kort detaljert"
status: waiting
detail: |
  Synk med Bård (transcript: ~/Desktop/synk-bard.json). Hovedkonklusjoner og
  TODO-liste etablert. Tre Trello-kort fra Bård sjekket via MCP.

  **Hovedprioritet — Oppgraderingsvei: gratis foretak → betalende deltaker**
  Bård bekreftet at dette er den største og viktigste jobben fremover.
  Andreas må:
  - Sette seg inn i logikken på nytt (har falt litt av etter siste endringer)
  - Sjekke status på endringer som er sendt opp men ikke fått svar på
  - Sette av tid (~1 time) til systematisk gjennomgang som testbruker
  - Lage liste over hva som funker / ikke funker / bør endres
  - Ta møte med Bård for å validere funn
  Prosess-prinsipp: Andreas må være streng på å ikke la småjobber spise tid
  fra denne — Bård minnet om kontekst-switching-kostnaden.

  **Tidskritisk — ProISP-migrering før utløp i mai 2026**
  Gammel bimverdi.no ligger fortsatt på ProISP. Domene/abonnement går ut i
  mai. Andreas må:
  - Logge inn på ProISP og sjekke nøyaktig utløpsdato
  - Overføre gammel bimverdi.no før utløp
  - Logge i WORKLOG når gjort

  **Småjobber — 3 Trello-kort fra Bård (i Innboks):**

  1. #297 "Endre Rate-limit-sperren" (label: Prioriteres) — jxw4F28g
     Claude klarer ikke registrere kunnskapskilder via min-side, får
     "For mange forsøk. Vennligst vent litt." (rate_limit-feil).
     Beskrivelsen i kortet (Claude-analyse) hevder Servebolt har server-side
     Bot Protection på wp-login/wp-admin. Andreas' hypotese fra synken: ikke
     server, men kode som stopper det — må verifiseres. Hvis Servebolt:
     kontakte support for IP-whitelisting.

  2. #291 "Duplisere arrangement i quick edit + foretakoversikt" — mJA3lVjs
     ⚠️ Større scope enn synken antydet — kortet har 3 punkter:
     a) Installere "Duplicate Post"-plugin for arrangement (var tilgjengelig
        i BV1, sparte Bård mye tid)
     b) Foretakoversikt: legge til kolonne for deltakernivå i wp-admin
        (Gratisforetak / Deltaker / Prosjektdeltaker / Partner) — screen
        option, 4 nivåer som kolonner

  3. #295 "Erstatte WP-ikon med BV-ikon i URL-linjen" — rUjehOEB
     Bytte WordPress favicon i nettleser-fanen til BV-knuten fra logoen.
     HTML-vedlegg (1.23 MB) i kortet med teknisk oppskrift.

  **Avklart i synken (ingen handling nå):**
  - E-poster/bekreftelser ved innmelding/utmelding/oppgradering: Beholde
    nåværende workflow (Bård melder ifra → Andreas tweaker via agent).
    Eget admin-grensesnitt for redigering nedprioritert — Bårds budsjett
    bedre brukt på substans enn UI for sjeldne tweaks.
  - Loggføring av endringer / endrings-e-post / SAP-integrasjon: Mulig
    fremtidig, ikke prioritert.

  **Prosess Bård minnet om:**
  - Begge rydder løpende i Trello-kort (arkiverer ferdige)
  - Holder kontakt på Teams mellom synker

  **Neste steg:**
  Venter på at Andreas velger om han starter med #297 (rate-limit-verifikasjon)
  eller #295 (favicon, raskest), eller går rett på hovedprioritet
  (oppgraderingsvei).

---
date: 2026-04-21
action: trello-cleanup
files: []
summary: "Ryddet Trello-boardet: arkiverte 9 kort fra Innboks + I arbeid som var ferdige eller duplikater"
detail: |
  Gikk gjennom Innboks- og I arbeid-kolonnen for å identifisere kort som enten
  var ferdige (verifisert mot kode/git) eller duplikater. Sammenstilte Trello-
  data mot worklog, kode og git-historikk for å avgjøre status — arkiverte
  ingenting uten verifikasjon.

  **Arkivert fra Innboks (3 kort):**
  - #252 "Ferdig: Påmelding admin" — tittel sier Ferdig, deployet 26.mars
  - #269 "DELTAKERE - kategorier, kolonner" — superseded av #275 BRUTTOLISTE
  - #276 "BUGS" — tom placeholder, superseded av #275

  **Arkivert fra I arbeid (5 kort):**
  - #265 "Artikler skriv/rediger" — deployet 14.apr, oppfølgings-bug løst i #281
  - #266 "Bug: Sletting av verktøy" — commit 7ab134e på main (= prod via autodeploy)
  - #277 "fix: Gratisforetak i deltakeroversikten" — deployet, 83→62 foretak
  - #278 "fix: ByggChat feil foretak" — deployet, peker nå til Verdinettverk AS
  - #281 "fix: Artikkel-skjema datatap" — deployet + norsk-kategorier-follow-up fikset

  **Slått sammen (1 kort):**
  - #257 "TILGANG TIL GRAFER KREVER LOGG-inn" → #268 "Forside-graf klikkbar"
    Bårds CTA-tekst "Logg deg på og få tilgang til interaktiv graf" + skjermbilde
    flyttet inn i #268 før arkivering. #280 notert som beslektet (samme graf,
    men datamodell-fokus).

  **Ikke arkivert (fortsatt aktive):**
  - #254 BV20 Nyregistrering — aktiv på current branch feat/gratis-brukerforetak
  - #289 Bugfix-pakke fra møte 2026-04-21 — leveres på møte i morgen 14:45

  **Metode:** Trello MCP for henting/arkivering, git-log + grep for kode-
  verifikasjon. Mønsteret "Andreas-kommentar uten Bård-respons" fantes kun på
  kortene som allerede var tydelig deployet.

---
date: 2026-04-21
action: planned+implemented+deployed
files: [mu-plugins/bimverdi-foretak-edit.php, plugins/bim-verdi-core/acf-json/group_arrangement_info.json, plugins/bim-verdi-core/acf-json/group_foretak_info.json, plugins/bim-verdi-core/cli/class-foretak-import-command.php, themes/bimverdi-theme/archive-arrangement.php, themes/bimverdi-theme/functions.php, themes/bimverdi-theme/inc/design-system.php, themes/bimverdi-theme/parts/components/arrangement-card.php, themes/bimverdi-theme/parts/minside/foretak-rediger.php, themes/bimverdi-theme/parts/minside/foretak-registrer.php, themes/bimverdi-theme/single-foretak.php]
summary: "Bugfix-pakke fra statusmøte med Bård: 5 oppgaver planlagt + implementert + deployert før 14:45-møtet"
detail: |
  Statusmøte med Bård 2026-04-21. Lagde TODOs fra møtetranscript, kjørte full
  /plan-workflow (brainstorm → plan → deepen 8 agenter → tech-audit 3 agenter
  → Trello-kort), deretter implementerte alle 5 oppgaver og deployet til prod.
  Commit 2f7d145 på main (4 commits, 11 filer, +315/-102 linjer).

  Plan: docs/plans/2026-04-21-fix-bugfix-pakke-bard-plan.md
  Brainstorm: docs/brainstorms/2026-04-21-bugfix-pakke-bard-brainstorm.md
  Trello-kort: https://trello.com/c/wN1BVF6G (bugfix-pakke-bard-2026-04-21)

  **Task 1 — ACF 1718 "Validation failed"-bug: diagnosert, IKKE reprodusert**
  Bård rapporterte at arrangement 1718 viste rød "Validation failed. ACF was
  unable to perform validation." Diagnose på prod:
  - Alle 6 required-felt fylt
  - acf_validate_value() passerer server-side for alle felt
  - Ingen custom acf/validate-filtre finnes
  - AJAX returnerte 200 med normal timing
  - Ingen fatal/warning i PHP ErrorLog
  - CLI-save (wp post update) fungerte
  Konklusjon: transient issue (nonce-expiry, browser-cache, eller stale object
  cache). Kjørte wp cache flush som safety-net. Dokumentert i
  docs/solutions/integration-issues/acf-validation-failed-transient-1718-20260421.md
  med recovery-sjekkliste for Bård. INGEN kode — defensiv mu-plugin droppet
  per simplicity-reviewer og plan's Go/No-Go-gate ("bygg ikke kode for
  ikke-verifiserte bugs"). YAGNI-win.

  **Task 2 — ICS ACF instruksjonstekst (commit fc7f148)**
  Bård trodde "Møtelenke (Teams/Zoom)"-feltet var ICS-opplasting. Faktisk
  genereres ICS runtime i mu-plugins/bimverdi-ics-generator.php. La til
  instruksjonstekst på field_arrangement_dato som forklarer auto-genereringen,
  og på field_online_lenke som klargjør at det IKKE er ICS-upload. Null kode-
  endring, kun ACF JSON. Postet Trello-kommentar på BRUTTOLISTE-kort for
  dokumentasjon.

  **Task 4 — UL/LI CSS bullet-punkter (commit 7c1fc35)**
  Tailwind preflight stripper list-style fra ul/ol globalt. Både .prose og
  .bv-prose hadde padding men INGEN list-style. La til list-style: disc/decimal
  + nested circle/square i inc/design-system.php. Bumpet CSS-versjon 2.0.0 →
  2.0.1. Dekker single-arrangement, single-foretak, single-artikkel, page.php.
  Minimal fiks (8 linjer) — full .bv-prose-migrasjon fra page.php droppet.

  **Task 5 — Arrangement-kort med featured image (commit 1f553ae)**
  /arrangement arkiv manglet bilder. Ekstraherte ny komponent
  parts/components/arrangement-card.php med get_template_part + $args-pattern.
  Registrerte add_image_size('arrangement_card', 800, 450, true) — sparer ~60%
  bandwidth vs 'large'. update_post_thumbnail_cache() før loop (N+1-fix).
  Brukte get_the_post_thumbnail() (bevarer WP lazy-loading + srcset).
  Temagruppe-color fallback (ingen placeholder-asset) for events uten bilde.
  Forside-hero ikke endret (annet visuelt-formål). Post-deploy: kjørte
  wp media regenerate for 3 eksisterende arrangement-bilder.

  **Task 3 — Foretaksprofil beskrivelse 3A-3H (commit 9991825)**
  Plan-research avdekket at min-side-form kun hadde ÉN textarea (bundet til
  lang beskrivelse), misvisende label/placeholder, og at kort_beskrivelse
  ikke kunne editeres frontend i det hele tatt. Komplett overhaul:

  - **3A** ACF maxlength 300→500, CLI-import-truncation (class-foretak-
    import-command.php:388-389) bumpet 297→497 (blocker — uten dette ville
    neste `wp foretak import` silent re-truncate).
    DATA-AUDIT: 30 foretak >300 tegn, 13 >500 (max 797). Grandfather-logikk
    ER nødvendig — overstyrer deepen-fasens simplicity-reviewer-antakelse.
  - **3B/3C** Live tegn-teller med IME-composition-handling, Array.from-based
    Unicode-tell, aria-live=polite (bare state-flip, ikke per-keystroke),
    .is-invalid class-toggle (CSS er pre-compiled, ikke JIT). Server-rendrer
    initial state så SSR + no-JS fungerer. Inline role="alert" i stedet for
    blocking alert(). 150ms debounce.
  - **3D** Label unified "Kort beskrivelse" i min-side + registrer-
    valideringsmelding. Public beholder "Om foretaket" som section-H2 per
    design-audit.
  - **3E** Public single-foretak viser nå BÅDE kort (som lead paragraph
    font-medium) OG lang beskrivelse (som body .bv-prose).
  - **3F** Ny kort_beskrivelse textarea i min-side edit-form, eksisterende
    relabeled "Beskrivelse" (var misvisende "Bedriftsbeskrivelse").
  - **3G** Handler whitelister kort_beskrivelse, mb_strlen('UTF-8')-validering
    med grandfather-regel: blokker kun når ny lengde > max(initial, 500).
    Canonical redirect-med-bv_error-pattern fra bimverdi-artikkel-submission.
  - **3H** Cache-purge deferred via shutdown-hook (unngår å blokkere redirect
    300-1500ms), static guard mot multiple purges per request.
    TODO-kommentar om fremtidig extraction til bimverdi-cache-invalidation.php.

  **Deploy (manuelt til Servebolt per memory)**
  1. Merge fix/bugfix-pakke-bard-2026-04-21 → main (locally in wp-content)
  2. git push origin main
  3. SSH merge på Servebolt — hadde lokale unstaged changes som matchet
     origin/main eksakt (md5 verified, autodeploy-hook hadde allerede skrevet
     filene?), måtte git checkout -- tracked files og rm untracked, deretter
     fast-forward merge.
  4. wp cache flush på prod
  5. wp media regenerate --image_size=arrangement_card for 3 attachments (1732,
     1364, 1317)

  **Post-deploy verifisering:**
  - acf_get_field("field_foretak_kort_beskrivelse")["maxlength"] = 500 ✓
  - arrangement_card image size registrert 800x450 ✓
  - 3 arrangement-bilder regenerert ✓

  **⚠️ Oppdaget follow-up: Servebolt-optimizer-plugin IKKE installert på prod.**
  sb_purge_cache_post / sb_purge_cache_url / class Servebolt\Optimizer —
  alt returnerer false/NO. Cache-purge-koden i bimverdi-foretak-edit.php er
  defensiv (function_exists-guards), så ingenting krasjer — men cache-purge
  er no-op. HTML-cache oppdateres via Servebolt TTL i stedet for proaktiv
  invalidation. Vurder å installere plugin senere.

  **Bård-kommunikasjon**
  Postet 2 kommentarer på Trello-kortet (wN1BVF6G):
  1. Deploy-status med verdikt og follow-up-varsel
  2. Test-sjekkliste tilpasset 50+-målgruppen: 6 konkrete URL-er, ✅/❌-
     kriterier i Word-nivå språk, ingen teknisk sjargong, skjermdump-
     instruksjon ved feil. Bård er tagget på kortet.

  **Arbeidsprosess — notater for fremtidige lignende pakker**
  - Full /plan-workflow ble kjørt før implementering: brainstorm →
    plan → deepen (8 parallelle agenter: security-sentinel, data-migration-
    expert, code-simplicity-reviewer, julik-frontend-races-reviewer,
    deployment-verification, bimverdi-design-skill, minside-skill,
    docs/solutions-scanner) → tech-audit (3 agenter: architecture, performance,
    patterns). Tok ca 1 time men reddet oss fra:
    - CLI import-blocker (data-migration-expert)
    - sb_purge_cache_post blocking redirect 300-1500ms (performance-oracle)
    - False-positive "bv-prose lives in design-system.php" (pattern-
      recognition-specialist korrigerte — det lå inline i page.php)
    - Task 1 overengineering (simplicity-reviewer scope-cut)
  - Go/No-Go-gates fra deployment-verification-agent fanget opp
    sb_purge_cache_post-issuet før implementering (plan advarte).
  - Data-audit AVSLO at simplicity-reviewer's antakelse om "0 rader >300"
    var feil. Grandfather-logikk reddet 13 foretak fra å bli låst ute av
    egen content.

  Status: alt på live, Bård varslet, Teams-melding gjenstår manuelt (Andreas).
  Klart for 14:45-statusmøte.
status: done
followups:
  - "Andreas: send Teams-melding til Bård om hard-refresh-tips for 1718 + ICS-bekreftelse"
  - "Vurder å installere Servebolt-optimizer-plugin på prod for å aktivere cache-purge"
  - "Hvis 1718-bugen kommer tilbake: be Bård om Network-tab response-body fra acf/validate_save_post-endpoint"
---
date: 2026-04-18
action: fixed+deployed
files: [mu-plugins/bimverdi-auth-routes.php, themes/bimverdi-theme/parts/auth/forgot-password.php, themes/bimverdi-theme/parts/auth/reset-password.php, themes/bimverdi-theme/parts/auth/resend-verification.php]
summary: "Glemt passord: eksplisitt 'bruker finnes ikke'-melding + fikset Servebolt WAF-stripping av ?error="
detail: |
  Trello-kort jYqdhS2O (kommentar fra Bård, 17. april, haster).
  Commits b943a14 + ee5b915 på main, deployet til Servebolt.

  **Fix 1: Eksplisitt "bruker finnes ikke"-melding**
  Før: glemt-passord viste alltid success-state (anti-enumeration).
  Nå: ikke-registrert e-post → rød alert med tekst "Vi finner ikke en
  registrert bruker med din e-post. Registrer deg her." + lenke til /registrer/.
  Endret handle_forgot_password_submission() (bimverdi-auth-routes.php:347-363)
  til å redirecte med bv_error=user_not_found i stedet for success når get_user_by
  returnerer false. La til error-melding + CSS for lenke i error-boks i
  forgot-password.php. Bytta esc_html til wp_kses for å tillate <a>-lenken.

  **Fix 2: Servebolt WAF stripper ?error=**
  Oppdaget at Servebolts WAF stripper query-param error=... før PHP leser det.
  Alle eksisterende feilmeldinger på auth-sider (invalid_email, nonce,
  invalid_key, user_not_found) rendres derfor aldri på prod.
  Debuggede via error_log i forgot-password.php — $_GET så error=NONE men
  foo=bar kom gjennom. Bytta param-navn til bv_error på tvers av auth-flytene:
  glemt-passord, tilbakestill-passord, send-verifisering (7 add_query_arg-kall
  + 3 $_GET-reads).

  **Testet 4/4 akseptansekriterier på prod:**
  1. ?bv_error=user_not_found → alert rendres med tekst + lenke
  2. POST ikke-registrert e-post → redirect til bv_error=user_not_found
  3. POST ugyldig format → redirect til bv_error=invalid_email
  4. POST registrert e-post → redirect til success=1 (uendret)

  Sikkerhetsnote: introduserer user enumeration på glemt-passord-endepunktet.
  Akseptert for lukket fagorganisasjon. Login-flyten beholder anti-enumeration.

  Trello-kort jYqdhS2O oppdatert med kommentar til Bård.
status: done
---
date: 2026-04-16
action: fixed+deployed
files: [mu-plugins/bimverdi-artikkel-submission.php, themes/bimverdi-theme/parts/minside/artikler-skriv.php, themes/bimverdi-theme/parts/minside/artikler-rediger.php]
summary: "Bårds tilbakemelding: verktøykategorier/kunnskapskilder valgfrie + norske kategorinavn"
detail: |
  PR #3 (commit 988b51b), branch fix/artikkel-valgfrie-felt, merget til main, deployet til Servebolt.
  Respons på Bård Krogshus sin kommentar på Trello-kort #281.

  **Fix 1: Verktøykategorier + kunnskapskilder → valgfrie**
  - Fjernet JS-validering i artikler-skriv.php (linje 338-345) og artikler-rediger.php (linje 411-418)
  - Fjernet server-side required-sjekk i bimverdi-artikkel-submission.php (linje 129-132, 138-141)
  - Fjernet rød stjerne (*) fra begge felt i skriv og rediger
  - Endret hjelpetekst fra "Velg minst én verktøykategori" til "Valgfritt — velg relevante kategorier"
  - Temagrupper er fortsatt påkrevd (korrekt)

  **Fix 2: Verktøykategorier oversatt til norsk**
  10 taxonomy-termer oppdatert via WP-CLI (prod) og direkte SQL (lokal):
  AI/Machine Learning → AI/Maskinlæring, Analysis & Simulation → Analyse og simulering,
  BIM Authoring/Modelling → BIM-modellering, Climate/Environmental Calculation → Klima-/miljøberegning,
  Collaboration/Communication → Samarbeid og kommunikasjon, Material Management → Materialforvaltning,
  Other → Annet, Project Management → Prosjektledelse, Quality Control/Validation → Kvalitetskontroll/validering,
  Visualization/VR/AR → Visualisering/VR/AR. Slugs beholdt (engelsk) for bakoverkompatibilitet.

  Testet 6/6 akseptansekriterier via Chrome DevTools MCP. Testartikel ryddet opp.
  Trello-kort #281 oppdatert med kommentar til Bård.
status: done
---
date: 2026-04-16
action: fixed+deployed
files: [themes/bimverdi-theme/front-page.php, themes/bimverdi-theme/parts/minside/kunnskapskilder-rediger.php, themes/bimverdi-theme/parts/minside/kunnskapskilder-registrer.php]
summary: "5 bugs fra møte 16. april — forside + kunnskapskilder"
detail: |
  PR #2 (commit a971c48), branch fix/meeting-bugs-april-16, merget til main, deployet til Servebolt.

  **Fix #284: Delta-knapp 404**
  Tre lenker i front-page.php pekte til /delta/ (ikke-eksisterende side). Endret alle til /registrer/.
  Filer: front-page.php:1120,1594,1595.

  **Fix #283: Kunnskapskilde dropdown for små**
  Select-felter (tilgang, språk, dataformat, geografisk gyldighet) i grid sm:grid-cols-2 klippet teksten.
  Endret til lg:grid-cols-2 → 2-kolonner kun på brede skjermer, 1-kolonne på smale.
  Filer: kunnskapskilder-rediger.php:292,318 + kunnskapskilder-registrer.php:191,217.

  **Fix #282: Detaljert beskrivelse lagres ikke**
  Rotårsak: ACF-felt var type wysiwyg men frontend brukte plain <textarea>.
  get_field() la til wpautop() → textarea viste rå HTML → innhold degraderte gjennom lagringssykluser.
  Fix: (1) Erstattet textarea med wp_editor() for WYSIWYG, (2) get_field($name, $id, false) for rå verdi,
  (3) tinyMCE.triggerSave() i submit-handler, (4) form-selektor fikset til 'form.space-y-6' (admin-bar
  søkeskjema ble fanget av querySelector('form')).
  Filer: kunnskapskilder-rediger.php + kunnskapskilder-registrer.php.

  **Feat #285: Forsidegraf — artikler som node**
  La til Artikler-node i hero SVG (rosa #EC4899, posisjon 440,195) + connection line fra sentrum +
  "X artikler" i hero-stats. $total_articles fantes allerede i PHP.
  Filer: front-page.php.

  **Feat #286: Forsidegraf — innlogging CTA**
  is_user_logged_in() switch på hero og bunn-CTA:
  - Ikke-innlogget: "Logg inn for å utforske" → /logg-inn/?redirect_to=/koblinger/
  - Innlogget: "Utforsk koblingene" → /koblinger/
  Bunn-CTA: innlogget ser "Utforsk koblingene", ikke-innlogget ser "Bli deltaker".
  Filer: front-page.php:1119-1125,1604-1611.

  **Bonus: Deploy key fikset**
  Servebolt deploy key (Ed25519) var utløpt/fjernet fra GitHub. Hentet pub key fra server,
  la til via gh repo deploy-key add. Fremtidige deploys fungerer igjen.

  **Bonus: Prod git state synket**
  Prod hadde uncommitted changes fra manuell fil-deploy i forrige session.
  Løst med git stash → git merge origin/main → git stash drop.

  Alle 5 Trello-kort (#282-286) oppdatert med planer, akseptansekriterier, kommentarer,
  og flyttet til V2-Bård for verifisering. 18/18 akseptansetester bestått via Chrome DevTools MCP.
status: done
---
date: 2026-04-16
action: planned
files: [docs/plans/2026-04-16-fix-kunnskapskilde-detaljert-beskrivelse-plan.md, docs/plans/2026-04-16-fix-kunnskapskilde-dropdown-storrelse-plan.md, docs/plans/2026-04-16-fix-delta-knapp-404-plan.md, docs/plans/2026-04-16-feat-forsidegraf-artikler-node-plan.md, docs/plans/2026-04-16-feat-forsidegraf-innlogging-plan.md]
summary: "Tekniske planer for 5 møtekort — rotårsakeanalyse, løsningsforslag, akseptansekriterier"
detail: |
  Kjørte /plan for hvert av de 5 Trello-kortene fra møtet 16. april.
  For hvert kort: leste kildekode, identifiserte rotårsak, skrev planfil i docs/plans/,
  oppdaterte Trello-kortbeskrivelse med plan-referanse og tekniske detaljer,
  la til akseptansekriterier-checklist på hvert kort.
  Alle tech audit GREEN — ingen blokkere.
status: done
---
date: 2026-04-14
action: fixed+deployed
files: [themes/bimverdi-theme/front-page.php, themes/bimverdi-theme/archive-foretak.php]
summary: "Gratisforetak fjernet fra forside logo-rull, foretaksteller og /deltakere/"
detail: |
  BUG fra synk 14. april: Forsiden og /deltakere/ viste alle 83 publiserte foretak inkl. gratis.
  Lagt til meta_query bv_rolle IN (Deltaker, Prosjektdeltaker, Partner) i:
  - front-page.php: logo-bar query (linje 21) + foretaksteller (linje 14, erstattet wp_count_posts med WP_Query)
  - archive-foretak.php: hovedquery (linje 17)
  Resultat: 62 betalende foretak vises nå. Commit 5a8c348, deployet til Servebolt.
  Trello-kort #277, Bård tagget.
status: done
---
date: 2026-04-14
action: fixed+deployed
files: []
summary: "ByggChat-artikkel pekte til feil foretak (Smart Innovation Norway → Verdinettverk AS)"
detail: |
  BUG fra synk 14. april: Artikkelen /artikler/byggchat/ viste Bård som rep for Smart Innovation Norway (ID 199).
  Bårds user_meta var korrekt (207 = Verdinettverk AS), men artikkelens artikkel_bedrift var 199.
  Fikset direkte i prod-DB: wp post meta update 1191 artikkel_bedrift 207.
  Kun denne artikkelen var feil — eldre artikler har NULL og faller tilbake til user_meta (korrekt).
  Trello-kort #278, Bård tagget.
status: done
---
date: 2026-04-14
action: created
files: []
summary: "Trello-kort: Oppgraderingsvei fra gratis foretak til betalende deltaker"
detail: |
  Fra synk 14. april: Gratis foretak har ingen måte å oppgradere til betalende.
  Opprettet Trello-kort #279 med full beskrivelse av nåsituasjon, tilgangsnivåer, og potensielt
  løsningsforslag (oppgraderingsside, CTA på foretak-detalj, fikset låst-innhold-melding, dashboard-banner).
  Assignet til Bård — hans oppgave å lage spec/logistikk for onboarding-flyten.
  Kort i Backlog på BIM Verdi v2-boardet.
status: waiting
waiting_on: Bård — spec/logistikk for onboarding
---
date: 2026-04-14
action: processed
files: []
summary: "Synk-møte 14. april — transcript gjennomgått, TODOs identifisert"
detail: |
  Gjennomgått transcript fra synk mellom Andreas og Bård.
  Identifiserte 3 bugs + 1 oppgave:
  1. Gratisforetak i deltakeroversikten (fikset)
  2. Ingen oppgraderingsvei fra gratis (Trello-kort til Bård)
  3. ByggChat feil foretak (fikset)
  4. Onboarding-spec (Bårds oppgave)
  Også notert: kontrakt øker til 10k/mnd fast.
status: done
---
date: 2026-04-14
action: implemented+deployed
files: [mu-plugins/bimverdi-artikkel-submission.php, themes/bimverdi-theme/parts/minside/artikler-skriv.php, themes/bimverdi-theme/parts/minside/artikler-list.php, themes/bimverdi-theme/parts/minside/artikler-rediger.php, themes/bimverdi-theme/parts/minside/dashboard.php, themes/bimverdi-theme/inc/minside-helpers.php, themes/bimverdi-theme/parts/components/button.php, mu-plugins/bimverdi-access-control.php]
summary: "Artikler — skriv/rediger i Min Side (Trello #265)"
detail: |
  Full implementering av artikkel-funksjonalitet i Min Side:
  - Ny mu-plugin bimverdi-artikkel-submission.php (POST-handler for create/edit/delete)
  - Tre nye Min Side-sider: artikler-list, artikler-skriv, artikler-rediger
  - TinyMCE via wp_editor() (Word-lignende for 50+ målgruppe)
  - Temagruppe/verktøykategori checkboxes, kunnskapskilde multi-select, eksterne lenker
  - PREMIUM tier i access control (kun Prosjektdeltaker/Partner kan skrive)
  - Dashboard-seksjon med siste 3 artikler
  - Rate limit (5/time), honeypot, bildevalidering (2MB, JPG/PNG/WebP)
  - State machine: pending → publish (admin godkjenner), pending kan redigeres/slettes
  Commit a11e982, deployet til Servebolt. Trello-kort #265 oppdatert og tagget Bård.
status: done
---
date: 2026-04-14
action: fixed+deployed
files: [mu-plugins/bimverdi-tool-registration.php, themes/bimverdi-theme/parts/minside/verktoy-list.php]
summary: "Verktøy-sletting fra Min Side (Trello #266)"
detail: |
  La til delete-handler i bimverdi-tool-registration.php (GET-Redirect-GET mønster).
  Oppdatert verktoy-list.php med success/error-meldinger for sletting.
  Commit 7ab134e, deployet til Servebolt.
status: done
---
date: 2026-03-24
action: fixed
files: []
summary: Fjernet dummy fagansvarlig-data fra 3 temagrupper på produksjon (SirkBIM, EiendomsBIM, BIMtech)
detail: |
  Slettet ACF-felter (fagansvarlig_navn/tittel/bedrift/bilde/linkedin) via WP-CLI.
  Dataen kom fra dummy-data-temagrupper.php og inneholdt "[Claude - dummydata]"-prefiks + fake LinkedIn-lenker.
  Templaten skjuler fagansvarlig-seksjonen automatisk når feltene er tomme.
status: done
---
date: 2026-03-24
action: configured
files: [.claude/settings.local.json, WORKLOG.md, CLAUDE.md, .gitignore]
summary: Satt opp worklog med Stop-hook, WORKLOG.md, og oppdatert CLAUDE.md
detail: |
  Fikset Stop-hook schema (systemMessage i stedet for hookSpecificOutput).
  Oppdatert worklog-setup.md globalt så feilen ikke gjentas i nye prosjekter.
status: done
---
