# BV hurtigregistrering for ByggChat 1.0.0

Implementerer **2X-KRAV – Hurtigregistrering for ByggChat (v1)**. Besøkende skanner en QR-kode, skriver inn navn og e-post og er inne i ByggChat med en gang. Den ordinære registreringen med BRREG-kobling fullføres senere.

## Oppsett

1. Last opp mappen `bv-hurtigreg` til `wp-content/plugins/` og aktiver pluginen. Aktiveringen oppretter rollen `byggchat_gjest`, adressene under `/chat/` og den daglige jobben.
2. Opprett en side med adressen **`/chat/`** og bare dette som innhold:
   ```
   [bv_hurtigreg]
   ```
   Pluginen viser siden med en lett mal uten meny, og siden mellomlagres aldri.
3. QR-koden peker til `https://bimverdi.no/chat/?k=rollup1`. Kampanjekoden `k` kan ha maks 32 tegn, og bare `a–z`, `0–9` og `-` er tillatt.
4. Under **Brukere → Hurtigregistrering → Innstillinger**:
   - Legg inn Cloudflare Turnstile site key. Secret key legges helst i `wp-config.php`:
     ```php
     define( 'BV_HURTIGREG_TURNSTILE_SECRET', '…' );
     ```
   - Juster kvoter, utløp og grenser ved behov.
5. `mu-plugins/bimverdi-byggchat-sso.php` må være på plass med `BYGGCHAT_PRIVATE_KEY_FILE`. Pluginen bruker den samme nøkkelen og det samme adgangskortet.

## Adgangskortet til ByggChat (HR-07)

Hurtigbrukere går til ByggChat gjennom den eksisterende SSO-overgangen (`/?byggchat_go=1`). Kortet er det samme RS256-JWT-et med samme nøkkel, `kid`, levetid (60 s), `jti` og adresse (`https://byggchat.bimverdi.no/sso?token=…`). For en hurtigbruker er to ting annerledes:

```json
{
  "nivaa": "Hurtigbruker",
  "foretak": null,
  "hurtig": {
    "status": "ubekreftet | bekreftet | utlopt",
    "kvote": 10,
    "kampanje": "rollup1",
    "opprettet": 1795118005,
    "utloper": 1797710005,
    "fullfor_url": "https://bimverdi.no/chat/fullfor/",
    "slett_url": "https://bimverdi.no/chat/slett/"
  }
}
```

Etter fullføring har brukeren samme `sub`, ordinært `nivaa` og ikke noe `hurtig`-felt. Derfor følger ByggChat-historikken med.

### Dette må ByggChat gjøre

- Godta `nivaa: "Hurtigbruker"`.
- Håndheve `hurtig.kvote` som antall spørsmål. Ved `0` eller oppbrukt kvote vises meldingen «Du har brukt gratiskvoten. Fullfør registreringen for å fortsette – det tar ett minutt.» med knapp til `fullfor_url`.
- Vise lenken «Fullfør registreringen» (`fullfor_url`) og «Slett kontoen» (`slett_url`).
- Oppdatere kvoten hver gang brukeren kommer via SSO, fordi status endres når e-posten bekreftes.
- Slette egen historikk når en hurtigbruker sletter kontoen. bimverdi.no sender ikke noe varsel om dette ennå. Kroken `bv_hurtigreg_anonymisert` finnes hvis det trengs.

## Flyten

| Steg | Adresse | Hva skjer |
|---|---|---|
| Skjema | `/chat/?k=…` | Navn, e-post og avkryssing. Innlogget bruker sendes rett til ByggChat. |
| Ny e-post | → `/chat/inne/` → ByggChat | Brukeren opprettes som `byggchat_gjest`, logges inn med «Husk meg» og får velkomst-e-post. GA4-hendelsen sendes, og brukeren går videre. |
| Kjent e-post | `/chat/?sjekk=1` | Ingen ny bruker. Innloggingslenke sendes på e-post. Skjermmeldingen er den samme for alle kontotyper. |
| Lenke i e-post | `/chat/lenke/` | Siden sender seg selv med POST, så e-postfiltre som forhåndsåpner lenker, ikke bruker den opp. |
| Fullføring | `/chat/fullfor/` | Krever bekreftet e-post. Felter som i `/aktiver-konto/`: navn, mobil, stilling og passord. Gmail o.l. må byttes til jobbadresse, som bekreftes med lenke. Deretter `/min-side/?welcome=1`, den ordinære BRREG-koblingen. |
| Sletting | `/chat/slett/` | Fra ByggChat (innlogget) eller fra lenken i e-post. Navn og e-post anonymiseres. |

Brukeren regnes som fullført når den ordinære flyten kobler den til et foretak (`bimverdi_company_id`) eller gir den en ordinær rolle. Da byttes `byggchat_gjest` ut med rollen rollesynken beregner, eller med `medlem`. Hurtigmetaen beholdes for statistikk.

## Tilgang (HR-14)

En `byggchat_gjest` er bare innlogget på `/chat/*`, i overgangen til ByggChat og ved utlogging. Etter profilsteget, med bekreftet jobbadresse, gjelder det også `/min-side/`, `/min-side/foretak/`, `/min-side/foretak/registrer/*`, BRREG-REST-rutene og de to AJAX-handlingene i foretakskoblingen. Alle andre steder ser WordPress gjesten som utlogget. Derfor gir verken maler, REST-ruter eller wp-admin gjesten mer enn en anonym besøkende får. Rollen har bare `read`.

## Utløp og opprydding (HR-11)

Daglig kl. 03:17 (`bv_hurtigreg_daglig`):

- Påminnelse 1 etter 7 dager og påminnelse 2 tre dager før utløp. Hver sendes bare én gang.
- Tilgangen stenges etter 30 dager uten fullføring. Rollen fjernes, sesjonene avsluttes, og innlogging med passord stoppes. Unntak: brukere som har registrert et foretak som venter på godkjenning.
- Navn og e-post anonymiseres 30 dager etter det (Å-4). Kampanjekode og tidspunkter beholdes til statistikken.
- En utløpt bruker som skriver inn e-posten på nytt, får en innloggingslenke. Lenken fører til fullføringen med kvote 0.

Loggen ligger under **Brukere → Hurtigregistrering → Logg**, og jobben kan også kjøres manuelt derfra.

## Misbruksvern (HR-12)

- Cloudflare Turnstile i usynlig modus (`interaction-only`). Uten nøkler er den av, og wp-admin varsler om det.
- Honeypot-feltet `nettside`. En robot får samme svar som en ekte bruker, men ingenting skjer.
- Maks 5 innsendinger per IP per time og maks 3 e-poster per adresse per døgn. Begge kan endres. IP og e-post lagres bare som hash.
- Skjemaet kan skrus av helt eller for enkeltkampanjer.
- MX-oppslag, og engangsdomener avvises (`bimverdi_is_disposable_domain`).

## Avklaringer fra kravets åpne spørsmål

| | Valg |
|---|---|
| Å-1 Gmail o.l. | Tillates for hurtigbrukere. Ved fullføring må brukeren bytte til jobbadresse, og den må bekreftes. |
| Å-2 Kvote | 10 og 50 spørsmål, kan endres i wp-admin. |
| Å-3 Autentisering | Tokenoverlevering (alternativ B) med det eksisterende RS256-kortet. |
| Å-4 Sletting | Anonymisering. |
| Å-5 SuperOffice | Ingen overføring ved hurtigregistrering. Admin-varslene i den ordinære flyten går ved fullføring. |

## Kroker

- `bv_hurtigreg_opprettet( $user_id, $kampanje )`
- `bv_hurtigreg_fullfort( $user_id )`
- `bv_hurtigreg_deaktivert( $user_id )`
- `bv_hurtigreg_anonymisert( $user_id, $grunn )`
- Filter `bv_hurtigreg_lett_mal` (`false` gir temaets vanlige mal på `/chat/`)
- Filter `bv_hurtigreg_ip`
- Filter `bimverdi_byggchat_claims` (i SSO-pluginen)

## Kjente begrensninger

- På stand deler mange samme nett og IP. Grensen på 5 per time bør heves under arrangementer.
- En ny e-post gir «Du er inne!», mens en kjent e-post gir «Sjekk e-posten din». Det avslører altså om en adresse er ny. Kravet godtar dette (HR-05 og akseptansekriterium 2–3), men det er verdt å vite.
- Innloggingslenker sendes ikke til kontoer med redaksjonelle eller administrative rettigheter. De får beskjed om å logge inn med passord.
- MX-oppslaget gjøres med `checkdnsrr()` og mellomlagres i et døgn per domene.
