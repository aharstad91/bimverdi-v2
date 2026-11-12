# Evaluering av ByggChat-svar

Formålet med denne mappen er å gjøre kvalitetsarbeidet målbart: i stedet for å
«føle» om et svar ble bedre, måler du det på et fast sett spørsmål før og etter
hver endring (ny søkemetode, endret systemprompt, ny modell osv.).

## Slik gjør du det

1. **Fyll ut spørsmålssettet.** Åpne `sporsmalssett-mal.csv` og lag 15–20 spørsmål
   som ligner det ekte testere kommer til å spørre om. Ta med vanlige faktaspørsmål
   med tydelig fasit, noen vanskelige/tvetydige, og minst 2–3 «avgrensningsspørsmål»
   som IKKE finnes i kildene (rad 9–10 er eksempler). Sett `forventet_kilde = INGEN`
   for disse.

2. **Kjør en runde.** Logg inn, still hvert spørsmål i chatten, og noter score.

3. **Score hvert svar** på tre akser, 0–2 poeng hver:
   - `score_riktig` – er innholdet korrekt?
   - `score_kildehenvisning` – peker [n]-henvisningene til riktig kilde?
   - `score_ingen_dikting` – holder svaret seg til kildene? (For avgrensningsspørsmål:
     2 poeng hvis chatten korrekt sier at den ikke fant dekning.)

   Maks 6 poeng per spørsmål. Summer og del på antall for en snittscore.

4. **Endre én ting av gangen**, kjør settet på nytt, og sammenlign snittscoren.

## Tips

- Lagre en kopi per runde (f.eks. `runde-1-fulltekst.csv`, `runde-2-hybrid.csv`).
- Lav `score_kildehenvisning` på mange spørsmål = gjenfinningen svikter (prøv
  semantisk/hybrid eller reranking). Dårlig formulering men riktig kilde =
  systempromten bør justeres.

Filformat: semikolon-separert (`;`) så det åpner rett i norsk Excel.
