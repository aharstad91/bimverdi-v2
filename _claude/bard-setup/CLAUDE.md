# BIM Verdi – Bårds arbeidsplass

Du hjelper Bård Krogshus med BIM Verdi. Svar på norsk med korte, klare setninger.
Gjør det tekniske arbeidet selv. Be bare Bård om innlogginger og valg du ikke kan avgjøre fra prosjektet.

## Første oppstart

Hvis `ssh_config` mangler, kjør `Setup-BIMVerdi.ps1` i Windows PowerShell.
Les resultatet før du fortsetter. Den ferdige arbeidsmappen ligger i `%LOCALAPPDATA%\BIMVerdi`.
Be Bård velge denne mappen i Code etter oppsettet.

Kjør `BIMVerdi.ps1 -Action Status` når du starter en ny arbeidsøkt.
En vellykket test bekrefter SSH, WordPress-database og tilgang til kode på serveren.
Rapporter bare at tilgangen virker når testen faktisk lykkes.

## Tilgang som allerede er satt opp

- Nettsted: `https://bimverdi.no`.
- Miljø: **produksjon**. Endringer her virker på det offentlige nettstedet med en gang.
- SSH: `jana-osl.servebolt.cloud`, port `22`, bruker `bimver_28287`.
- WordPress: `/cust/0/bimver_15781/bimver_28287/home/site/public`.
- Kode: WordPress-mappen over, under `wp-content/`.
- Koden bruker et eget PHP-tema, ACF Pro, Gravity Forms og Tailwind CSS.
- Bårds nøkkel: `%USERPROFILE%\.ssh\bimverdi_bard_ed25519`.
- SSH-oppsett: `ssh_config` i denne arbeidsmappen.
- Bruk Bårds egen nøkkel. Kontoen på Servebolt er den eksisterende nettstedskontoen.

Kjør kommandoene fra arbeidsmappen med Windows PowerShell:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\BIMVerdi.ps1 -Action Status
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\BIMVerdi.ps1 -Action Wp -Command 'post list --post_type=page --fields=ID,post_title,post_status --format=table'
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\BIMVerdi.ps1 -Action Run -Command 'pwd'
```

`Run` kjører en vanlig kommando på Linux-serveren. `Wp` kjører WP-CLI med riktig WordPress-mappe.
Hjelpeskriptet sender kommandoen gjennom standard input. Det bevarer anførselstegn og norske tegn.
Hvis du bruker Git Bash, start `powershell.exe` for å kjøre disse skriptene.
Håndter WP-CLI-varsler ut fra returkode og faktisk resultat. Ikke anta at et varsel betyr feil.

## Slik arbeider du

Les gjeldende data før du foreslår eller utfører en endring.
Bruk WordPress-funksjoner og WP-CLI til å oppdatere innhold og data.
Ta vare på de berørte verdiene lokalt før du endrer dem, slik at du kan angre.
Lag en databasebackup før du endrer mange poster eller databasestrukturen.
Oppbevar backuper utenfor nettstedets offentlige mappe.
Endre bare det oppgaven gjelder. Verifiser resultatet og forklar kort hva som ble endret.
Send e-post og meldinger bare når Bård uttrykkelig ber om det.

Kode ligger i `aharstad91/bimverdi-v2`. Bård bruker GitHub-kontoen `baardkr`.
Ved kodearbeid, bruk en lokal Git-kopi og en egen gren.
Sett opp Git og GitHub CLI ved behov. La Bård fullføre GitHub-innlogging i nettleseren.
Del aldri passord, nøkler eller innloggingskoder i chat eller Git.
Bygg CSS med `npm run build` i `themes/bimverdi-theme` når maler eller Tailwind-klasser endres.
Push til `main` utløser automatisk publisering til Servebolt ifølge prosjektoppsettet.
Publiser når oppgaven gir autorisasjon til det. Ikke publiser andre personers uferdige arbeid.
Direkte endringer i produksjonens kode kan forsvinne ved neste publisering. Lagre kodeendringer i Git.

## Krav og felles forståelse

Gjeldende krav ligger i `baardkr/bimverdi-context`, gren `BV-2.0-main`, mappe `bedrift/`.
Les repoets `CLAUDE.md` og relevante krav før produktendringer.
Hvis pakken har en `kontekst/`-mappe, er den en datert kopi for første oppstart.
Hent gjeldende versjon fra GitHub før kravarbeid. Ikke bruk kopien som oppdatert fasit.
Kontekstrepoets instrukser gjelder når du endrer krav der.

## Grenser for oppsettet

Denne arbeidsplassen har produksjonstilgang via SSH og WP-CLI.
Den har ingen lokal WordPress-installasjon eller egen testdatabase.
Nettlesertesting krever et tilgjengelig nettleserverktøy og eventuelt Bårds egen WordPress-innlogging.
Ikke påstå at slike tilganger er satt opp før du har verifisert dem.
Hvis SSH svikter, vis den konkrete feilen. Ikke slå av kontroll av serverens nøkkel.
Ikke les eller skriv ut private nøkler. Engangsnøkkelen slettes etter vellykket oppsett.
