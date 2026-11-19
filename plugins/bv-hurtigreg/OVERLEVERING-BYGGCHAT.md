# Hurtigbrukere i ByggChat – overlevering til Alexander

**Fra:** Bård Krogshus (produkteier) · **Dato:** 19.11.2026
**Gjelder:** byggchat.bimverdi.no
**Bakgrunn:** 2X-KRAV – Hurtigregistrering for ByggChat (v1). bimverdi.no-delen er ferdig og ligger i `plugins/bv-hurtigreg` ([PR #47](https://github.com/aharstad91/bimverdi-v2/pull/47)).

## Kort fortalt

Folk skal kunne skanne en QR-kode på stand og være i gang med ByggChat på noen sekunder, med bare navn og e-post. På bimverdi.no blir de **hurtigbrukere**. De logges inn og sendes til ByggChat gjennom den samme SSO-en du allerede har.

ByggChat må gjøre fire ting:

1. Godta det nye nivået `Hurtigbruker`.
2. Håndheve en spørsmålskvote som står i adgangskortet.
3. Vise lenkene «Fullfør registreringen» og «Slett kontoen».
4. Slette hurtigbrukernes data når kontoen deres slettes på bimverdi.no.

Ingenting annet i SSO-en er endret.

## Dette er uendret

- Samme RS256-JWT, samme nøkkel og `kid`, og samme JWKS: `https://bimverdi.no/wp-json/bimverdi/v1/byggchat/jwks`
- Samme mottaksadresse: `https://byggchat.bimverdi.no/sso?token=…`
- Samme ramme: `iss` = `https://bimverdi.no`, `aud` = `byggchat`, `sub` (bruker-ID), `iat`, `exp` (60 s) og `jti` (engangs)
- Samme personfelter: `email`, `name`, `given_name`, `family_name`
- Vanlige brukere får nøyaktig samme kort som før.

## Dette er nytt for hurtigbrukere

```json
{
  "iss": "https://bimverdi.no",
  "aud": "byggchat",
  "sub": "4711",
  "email": "kari.nordmann@gmail.com",
  "name": "Kari Nordmann",
  "given_name": "Kari",
  "family_name": "Nordmann",
  "foretak": null,
  "nivaa": "Hurtigbruker",
  "iat": 1795118005,
  "exp": 1795118065,
  "jti": "7cd7df51-1768-41e4-90f6-c7a45b141bcf",
  "hurtig": {
    "status": "ubekreftet",
    "kvote": 10,
    "kampanje": "rollup1",
    "opprettet": 1795118005,
    "utloper": 1797710005,
    "fullfor_url": "https://bimverdi.no/chat/fullfor/",
    "slett_url": "https://bimverdi.no/chat/slett/"
  }
}
```

| Felt | Type | Betydning |
|---|---|---|
| `nivaa` | `"Hurtigbruker"` | Nytt nivå ved siden av Gratisbruker, Deltaker, Prosjektdeltaker og Partner. |
| `foretak` | `null` | En hurtigbruker er aldri koblet til foretak. |
| `hurtig` | objekt | Finnes **bare** for hurtigbrukere. Mangler feltet, er brukeren ordinær. |
| `hurtig.status` | `ubekreftet` \| `bekreftet` \| `utlopt` | `ubekreftet` betyr at e-postadressen **ikke er bekreftet**. `bekreftet` betyr at brukeren har trykket på lenken i e-posten. `utlopt` betyr at 30 dager har gått uten fullføring. |
| `hurtig.kvote` | heltall ≥ 0 | Maks antall spørsmål totalt, ikke per dag. Standard er 10 for ubekreftet, 50 for bekreftet og 0 for utløpt. Verdiene kan endres i wp-admin. |
| `hurtig.kampanje` | streng \| `null` | Hvor brukeren kom fra, f.eks. `rollup1`. Kan brukes i ByggChats egen statistikk. |
| `hurtig.opprettet` | Unix-tid | Når hurtigbrukeren ble opprettet. |
| `hurtig.utloper` | Unix-tid | Når tilgangen stenges hvis brukeren ikke har fullført. |
| `hurtig.fullfor_url` | URL | Siden for å fullføre registreringen. |
| `hurtig.slett_url` | URL | Siden der brukeren sletter kontoen selv. |

## Det ByggChat må gjøre

### 1. Godta nivået og knytte brukeren til `sub`

- Godta `nivaa: "Hurtigbruker"`. Avvis ikke ukjente nivåer, og behandle dem ikke som betalende.
- **Knytt alltid brukeren til `sub`, aldri til `email`.** Er `hurtig.status` lik `ubekreftet`, har ingen kontrollert at personen eier adressen. Slår ByggChat sammen kontoer på e-post, kan hvem som helst få tilgang til en annens historikk ved å registrere seg med vedkommendes adresse. Hos oss kan ikke en adresse som allerede finnes, gi en ny hurtigbruker, men ByggChat kan ha egne brukere som WordPress ikke kjenner.
- Oppdater lagret `nivaa` og `hurtig` **hver gang** brukeren kommer via SSO. Status og kvote endres, for eksempel fra 10 til 50 når e-posten bekreftes.

### 2. Håndheve kvoten

- Tell spørsmålene hurtigbrukeren har stilt, per `sub`.
- Er antallet **≥ `hurtig.kvote`**, eller er `now` ≥ `hurtig.utloper`, skal ByggChat ikke svare på nye spørsmål. Vis i stedet meldingen under.
- Sjekk `utloper` også inne i en pågående ByggChat-sesjon. Ellers kan en lang sesjon brukes etter at tilgangen er stengt.
- Kvoten er totalt antall. Når kvoten øker fra 10 til 50, har brukeren 40 spørsmål igjen, ikke 50 nye.
- Kommer brukeren via SSO **uten** `hurtig`-feltet, har den fullført. Fjern begrensningen.

**Melding når kvoten er brukt opp:**

> Du har brukt gratiskvoten. Fullfør registreringen for å fortsette – det tar ett minutt.
>
> **[Fullfør registreringen]** → `hurtig.fullfor_url`

**Forslag til teller** som alltid vises for hurtigbrukere:

> 7 av 10 gratisspørsmål igjen · [Fullfør registreringen]

Er `status` lik `ubekreftet`, kan du gjerne legge til: «Bekreft e-posten din for å få 50 spørsmål.»

### 3. Lenkene

For hurtigbrukere skal disse alltid være synlige, f.eks. i menyen eller profilen:

- **Fullfør registreringen** → `hurtig.fullfor_url`
- **Slett kontoen** → `hurtig.slett_url`

Begge sidene krever at brukeren er innlogget på bimverdi.no **i samme nettleser**. Det er de nesten alltid, fordi registreringen logger dem inn med «Husk meg». På en annen enhet får de beskjed om å bruke lenken i e-posten. Fra ByggChat er det nok å lenke til adressene.

### 4. Etter fullføring

Når brukeren har fullført, får den samme `sub` og ordinært `nivaa`, og `hurtig`-feltet forsvinner. **Historikken skal følge med.** Det gjør den automatisk så lenge ByggChat knytter brukeren til `sub`.

### 5. Sletting og lagringstid (personvern)

På bimverdi.no anonymiseres en hurtigbruker i to tilfeller:

- når brukeren sletter kontoen selv via `slett_url`, eller
- 30 dager etter `utloper`, hvis den ikke har fullført. Lagringstiden kan endres i wp-admin.

ByggChat bør slette eller anonymisere sine data om brukeren i de samme tilfellene, som samtaler, navn og e-post. Personvernerklæringen lover det.

bimverdi.no sender **ikke** noe varsel til ByggChat i dag. To muligheter:

- **A, enklest:** ByggChat sletter selv data for brukere som sist hadde `hurtig` i kortet, når `now` > `hurtig.utloper` + 30 dager. Selvsletting fanges ikke opp før da.
- **B:** Vi legger til et signert kall fra bimverdi.no til ByggChat når en hurtigbruker anonymiseres. Kroken `bv_hurtigreg_anonymisert` finnes allerede. Du må bestemme adresse og format.

**Spørsmål til deg:** Hvilken av dem vil du ha?

## Slik tester du

1. Åpne `https://bimverdi.no/chat/?k=test` i et privat vindu og registrer deg med en e-post som ikke har konto. Du sendes rett til ByggChat med et kort som har `status: "ubekreftet"` og `kvote: 10`.
2. Trykk på knappen i e-posten «Velkommen til ByggChat». Neste gang du åpner ByggChat via bimverdi.no, har kortet `status: "bekreftet"` og `kvote: 50`.
3. Slett testkontoen med «Slett kontoen nå» nederst i e-posten, eller be Bård slette den i wp-admin.

Kampanjekoden `test` holder testene adskilt i statistikken.

## Akseptansekriterier for ByggChat-delen

1. En hurtigbruker kommer inn uten ny innlogging og ser telleren for gjenværende spørsmål.
2. Etter 10 spørsmål (ubekreftet) stopper ByggChat og viser meldingen med knappen «Fullfør registreringen».
3. Etter bekreftet e-post og ny innlogging via bimverdi.no er grensen 50 spørsmål totalt.
4. Etter `utloper` svarer ikke ByggChat, heller ikke i en pågående sesjon.
5. Etter fullføring er grensen borte og historikken er beholdt.
6. Brukere blir aldri slått sammen på e-post.
7. Data om slettede eller utløpte hurtigbrukere fjernes i tråd med avsnitt 5.

Spørsmål kan sendes til Bård.
