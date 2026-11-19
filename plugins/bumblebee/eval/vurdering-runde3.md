# Vurdering runde 3 – Bumblebee

Dato: 12.11.2026. Uavhengig måling, ingen kodeendring. Hvert sett er kjørt 2 ganger. Poengene er snittet av de to kjøringene, 0–2 per kriterium, med samme strenghet som runde 2.
Detaljer per spørsmål: `vurdering-runde3.json`. Svar: `svar-runde3-test.json`, `svar-runde3-k1.json`, `svar-runde3-k2.json`.

## Totaler

| Sett | Riktig | Kilde | Ingen dikting | Sum | Prosent |
|---|---|---|---|---|---|
| Test, runde 2 | 58/60 | 58/60 | 56/60 | 172/180 | 95,6 % |
| Test, runde 3 | 59/60 | 59,5/60 | 57/60 | 175,5/180 | 97,5 % |
| Kontroll 1, runde 2 | 27/30 | 29/30 | 29/30 | 85/90 | 94,4 % |
| Kontroll 1, runde 3 | 28/30 | 29/30 | 29/30 | 86/90 | 95,6 % |
| Kontroll 2, runde 3 (nytt) | 18,5/20 | 20/20 | 18,5/20 | 57/60 | 95,0 % |

Kostnad: 0,38 USD (test 0,21, kontroll 1 0,10, kontroll 2 0,07). `wp bbee status` viste 0 biter uten embedding og vektorlageret i takt (23 828 = 23 828). Derfor trengtes ikke `wp bbee embed`.

## Kontroller

- Alle henvisningsnumre [n] finnes i kildelista med samme n. `sources` er lik de siterte i `all_sources` i alle 110 svar.
- Ingen kilder er fra reserverte, ukjente eller sperrede domener.
- `no_coverage` er riktig i 110 av 110 svar. De 10 avgrensningsspørsmålene har `true` og ingen tall. Alle andre har `false` og minst én henvisning.

## Stabilitet

De to kjøringene gir ulik poengsum på 3 av 55 spørsmål: id 1, 21 og 201. Kildelista var den samme i begge kjøringer, så variasjonen kommer fra modellen.

## Svarlengde

Median for svar med dekning er 76,5 ord i testsettet. Runde 2 hadde 177 (178,5 med samme telling). Kontroll 1 har 100 ord mot 218,5, og kontroll 2 har 86 ord. Det lengste svaret har 180 ord. Fyllavsnitt om hva kildene ikke sier gikk fra 32 til 0. Fire svar har fortsatt en overskriftslinje, for eksempel «Noen detaljer:» og «Kort oppsummert:». `tidy_answer` fjerner bare «Detaljer:».

## Krav og anbefaling

Jeg fant 5 tydelige feil i 110 svar, alle i testsettet:

- Id 18 (begge kjøringer): Kilden sier «Det bør være minst ett toalett per 15 kvinner». Svaret skriver «Det skal være».
- Id 19 (begge kjøringer): Kilden sier «skal». Svaret skriver «FutureBuilt Sirkulær anbefaler». Denne feilen er ny, fordi modellen nå retter for mye.
- Id 1 (kjøring 2): Svaret sier at restlevetid må dokumenteres «for å oppfylle TEK17». Det står ikke i kilden.

Dette er rettet siden runde 2: id 7 beholder kildens «skal» og kaller ikke metoden et krav. Id 104 sier «kan ikke bore hvis eieren nekter» i stedet for «må ikke». Id 207 skiller riktig mellom bindende plan og veiledende norm. Grensetilfelle: id 5, kjøring 2, skriver «Det kreves … testing» om en rapport.

## Spørsmål 1 – betongdragere

Begge kjøringer begynner slik: «Resertifisering av betongdragere i ombruksprosjekter krever grundig dokumentasjon, testing og vurdering av bæreevne og restlevetid, som beskrevet i NS 3682 Ombruk av prefabrikkerte betongelementer.»

- Kjøring 1 siterer Resirqel [1] og ROT-veilederen [2]. Poeng: 2/2/2.
- Kjøring 2 siterer bare ROT-veilederen. Den legger Resirqel-innhold på [2] og har TEK17-påstanden. Poeng: 2/1/1.

Snittet er 5 av 6, mot 2 av 6 i runde 2. Ingen kjøring forklarer Resirqel-strategien tydelig: destruktiv test av et utvalg (NS-ISO 2859), så ikke-destruktiv test av resten.

## Testsettet mot runde 2

- Bedre: id 1 (+3), id 7 (+1), id 21 (+0,5).
- Verre: id 19 (−1, skal→anbefaler).
- Uendret: 26 spørsmål. Id 18 har fortsatt bør→skal.

## Overtilpasning

Jeg ser ikke noe tegn på overtilpasning. Testsettet ligger på 97,5 %, kontroll 1 på 95,6 % og kontroll 2 på 95,0 %. Forskjellen er 2–2,5 prosentpoeng. Kontroll 1 ble også bedre (+1, id 104), selv om ingen endring var rettet mot det settet. Tapene i kontrollsettene har generelle årsaker:

- Id 107: Svaret sier «i snitt 42 uker». Det tallet er summen av fristene i loven, ikke snittet. Biten med 577→890 dager hentes ikke opp.
- Id 110: Workshop XII-rapporten er nå i indeksen (20 biter), men et norsk spørsmål henter den ikke opp. Problemet er nå søket, ikke dataene.
- Id 206: Svaret sier at «SOSI-filer i GML-format kan fortsatt sendes». Kilden sier at SOSI-formatet utgår og at GML tar over.
- Id 201, kjøring 2: Svaret lager en formel av ødelagt PDF-tekst.

## Viktigste problemer

1. Modalverb fra kilder som ikke er forskrift blir fortsatt feil i begge retninger (id 18 og 19).
2. Engelske kilder blir ikke hentet opp for norske spørsmål (id 110), og tall i lange rapporter blir oversett (id 107).
3. Modellen leser tall og regler for bokstavelig eller feil: frister blir til snitt (id 107), og «SOSI-format utgår» blir til «kan fortsatt sendes» (id 206).
4. Ødelagte formler fra PDF-er blir gjenskapt som om de var riktige (id 201).
5. Små formatfeil: overskriftslinjer som «Noen detaljer:» og «Kort oppsummert:».

## Neste steg

1. Gi prompten en kort regel med eksempel for kilder som ikke er forskrift: «bør» i kilden gir «bør» i svaret, og «skal» i kriterier gir «skal».
2. Søk på tvers av språk: Lag en engelsk versjon av spørsmålet i tillegg, eller gi engelske rapporter en norsk oppsummering når de indekseres.
3. Ikke la modellen gjengi formler fra PDF-tekst med ødelagte tegn. Merk bitene, eller be modellen vise til kilden.
4. Utvid `tidy_answer` til flere overskrifter som ender med kolon, for eksempel «Noen detaljer:» og «Kort oppsummert:».
5. Behold 2 kjøringer per sett. Variasjonen er nå liten (3 av 55), men id 1 viser at én kjøring kan gi feil bilde.
