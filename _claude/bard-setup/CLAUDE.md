# BIM Verdi – Bårds arbeidsplass

Du hjelper Bård Krogshus med BIM Verdi. Svar på norsk med korte, klare setninger.
Gjør det tekniske arbeidet selv. Be bare Bård om innlogginger og valg du ikke kan avgjøre fra prosjektet.

## Ansvar

Bård har denne tilgangen på eget ansvar. Han bekreftet det ved oppsettet.
Tilgangen går rett til produksjon: `https://bimverdi.no`. Endringer virker med en gang for alle besøkende.
Andreas godkjenner ikke endringer på forhånd. Han ser loggen og Git-historikken i ettertid.
Serveren logger alle kommandoer med hele skriptet (inntil 64 KB).

Dette betyr for deg:
- Les før du endrer.
- Gjør små steg. Verifiser etter hvert steg.
- Hvis du er usikker, stopp og spør Bård.
- Ikke gjør noe du ikke kan forklare og angre.

## Første oppstart

Hvis `ssh_config` mangler, kjør `Setup-BIMVerdi.ps1` i Windows PowerShell.
Les resultatet før du fortsetter. Den ferdige arbeidsmappen ligger i `%LOCALAPPDATA%\BIMVerdi`.
Be Bård velge denne mappen i Code etter oppsettet.

Kjør `BIMVerdi.ps1 -Action Status` når du starter en ny arbeidsøkt.
En vellykket test bekrefter SSH, WordPress-database og tilgang til kode på serveren.
Rapporter bare at tilgangen virker når testen faktisk lykkes.

## Tilgang som allerede er satt opp

- Nettsted: `https://bimverdi.no`.
- Miljø: **produksjon**.
- SSH: `jana-osl.servebolt.cloud`, port `22`, bruker `bimver_28287`.
- WordPress: `/cust/0/bimver_15781/bimver_28287/home/site/public`.
- Kode på serveren: WordPress-mappen over, under `wp-content/`. Koden der er en utrulling fra Git. Endre den ikke direkte.
- Koden bruker et eget PHP-tema, ACF Pro, Gravity Forms og Tailwind CSS.
- Bårds nøkkel: `%USERPROFILE%\.ssh\bimverdi_bard_ed25519`.
- SSH-oppsett: `ssh_config` i denne arbeidsmappen.
- Alle kommandoer går gjennom en gate på serveren. Se avsnittet «Gaten på serveren».

Kjør kommandoene fra arbeidsmappen med Windows PowerShell:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\BIMVerdi.ps1 -Action Status
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\BIMVerdi.ps1 -Action Wp -Command 'post list --post_type=page --fields=ID,post_title,post_status --format=table'
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\BIMVerdi.ps1 -Action Run -Command 'pwd'
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\BIMVerdi.ps1 -Action Log
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\BIMVerdi.ps1 -Action Backups
```

`Run` kjører et skript på Linux-serveren. `Wp` kjører WP-CLI med riktig WordPress-mappe.
`Log` viser de siste 40 kommandoene i gateloggen. `Backups` viser databasekopiene gaten har tatt.
Hjelpeskriptet sender kommandoen gjennom standard input. Det bevarer anførselstegn og norske tegn.
Hvis du bruker Git Bash, start `powershell.exe` for å kjøre disse skriptene.
Håndter WP-CLI-varsler ut fra returkode og faktisk resultat. Ikke anta at et varsel betyr feil.

## Gaten på serveren

Gaten er et vern mot uhell. Den er ikke en sikkerhetsgrense, og den erstatter ikke din egen vurdering.

Gaten gjør dette:
- Logger hver kommando med hele skriptet (inntil 64 KB), tidspunkt og resultat.
- Tar en komprimert databasekopi før kommandoer som kan skrive. Maks én kopi per 10 minutter. De 10 siste beholdes, pluss dagens første i 7 dager, i `~/private/bard-gate/db/`. Kopien dekker bare databasen. Filer dekkes av Git og Servebolts egne backuper.
- Hvis kopien feiler og kommandoen kan endre databasen, stopper gaten kommandoen (returkode 76). Prøv igjen, eller be Andreas.
- Etter kommandoer som kan skrive: syntakssjekker nye PHP-filer, melder om filer som er endret i Git-styrte mapper, flytter SQL-dumper ut av den offentlige mappen, og sjekker at innloggingssiden og forsiden svarer 200.
- Sperrer en kort liste med kommandoer. Da får du returkode 77 og en melding som starter med `[bard-gate] SPERRET`.

Les alle linjer som starter med `[bard-gate]`. En `ADVARSEL` betyr at nettstedet kan være skadet. Rett det med en gang.

Gaten sperrer:
- `wp db reset/drop/clean`, `wp site empty`, `DROP`, `TRUNCATE`, `DELETE` uten `WHERE`.
- `rm -r`, `find -delete`, sletting i `uploads/`, rekursiv `chmod`, `chmod 777`.
- Git-kommandoer som skriver på serveren (`reset`, `checkout`, `stash`, `pull`, `push`, `commit` og lignende). `git status`, `git log` og `git diff` er lov.
- Skriving av filer rett inn i `themes/`, `mu-plugins/` og `plugins/bim-verdi-core/` på serveren. Disse endres via Git.
- `wp-config.php`, `.htaccess`, SSH-nøkler, `environment.json`, gaten selv og loggen.
- `wp core update`, `wp config set`, `wp plugin/theme update` av bim-verdi-core og bimverdi-theme.
- Filoverføring med `scp`/`sftp`, bakgrunnskjøring (`&`, `nohup`, `screen`), utgående SSH.

Gaten leser alt den får på stdin før den kjører. Hvis du kjører `ssh` selv uten `BIMVerdi.ps1`, bruk `ssh -n` når du ikke sender data.
Sperrelisten er tekstbasert. Den kan treffe tekst i et skript, for eksempel `grep 'rm -rf' fil`. Skriv søket annerledes.

Hvis gaten sperrer noe du mener er nødvendig, forklar det til Bård. Han tar det opp med Andreas.

## Kode: via Git og PR

Koden ligger i `aharstad91/bimverdi-v2`. Repoet er `wp-content/`-mappen. Bård bruker GitHub-kontoen `baardkr` og har skrivetilgang.
Grenen `main` er beskyttet. Bare Andreas (admin) kan pushe rett til `main`. Ingen kan tvinge historikk. Alle andre endringer går gjennom en pull request.
En pull request kan merges når sjekken `php-lint` er grønn. Ingen person må godkjenne. Bård merger selv.
Når en pull request merges til `main`, publiserer Servebolt automatisk til `https://bimverdi.no`.

Slik arbeider du med kode:
1. Klon repoet til `repo\` i denne arbeidsmappen hvis det mangler: `git clone https://github.com/aharstad91/bimverdi-v2.git repo`.
2. Sett opp Git og GitHub CLI ved behov. La Bård fullføre innlogging i nettleseren.
3. Hent siste `main`: `git switch main` og `git pull`.
4. Lag en gren per oppgave: `git switch -c bard/<kort-tema>`.
5. Gjør endringen. Hold den liten. Bygg CSS med `npm run build` i `themes/bimverdi-theme` når maler eller Tailwind-klasser endres.
6. Commit med en kort norsk melding. Push: `git push -u origin HEAD`.
7. Lag pull request: `gh pr create --fill`. Vent på `php-lint`: `gh pr checks --watch`.
8. Når sjekken er grønn: `gh pr merge --squash --delete-branch`.
9. Vent ett minutt. Verifiser på `https://bimverdi.no`. Grønn `php-lint` bekrefter bare syntaks, ikke at nettstedet virker. Hvis noe er galt, lag en ny pull request som retter det.

Aldri:
- Kjør Git-kommandoer som skriver på serveren. Gaten sperrer dem.
- Skriv PHP-filer rett inn i `themes/`, `mu-plugins/` eller `plugins/bim-verdi-core/` på serveren. De forsvinner ved neste publisering.
- Push til `main`. Bruk alltid en gren og en pull request.
- Legg passord, nøkler eller koder i Git eller chat.
- Endre `.github/workflows/` for å få en sjekk grønn. En rød `php-lint` betyr at koden har en feil.

Bårds egne plugins ligger bare på serveren og ikke i Git: `bae-kalender`, `bimverdi-byggchat`, `bimverdi-doffin`,
`bv-begrep`, `bv-besok`, `bv-lenkevakt`, `bv-pilot-prosjekt`, `bv-trafikk`, `bv-wp-epostlister`, `stotteordninger-bae`.
Andreas tok kopi av dem 03.11.2026. Disse skal inn i repoet, én plugin per pull request, som egne oppgaver.
Hent filer fra serveren slik: `-Action Run -Command 'cd /cust/0/bimver_15781/bimver_28287/home/site/public/wp-content/plugins && tar czf - <mappe> | base64'`. Dekod lokalt.
Når en plugin er i repoet, må `.gitignore` i repoet slippe den gjennom (`!plugins/<navn>/`).

## Data og innhold

Les gjeldende data før du foreslår eller utfører en endring.
Bruk WP-CLI til å oppdatere innhold og data. Kjør `wp search-replace` med `--dry-run` først.
Ta vare på de berørte verdiene lokalt før du endrer dem, slik at du kan angre.
Gaten tar databasekopi før skrivende kommandoer. Ta en egen kopi med `wp db export ~/private/<navn>.sql` før store endringer.
Skriv aldri databasekopier under `site/public`. Gaten flytter dem bort, men filen kan ha vært synlig et øyeblikk.
Endre bare det oppgaven gjelder. Verifiser resultatet og forklar kort hva som ble endret.
Send e-post og meldinger bare når Bård uttrykkelig ber om det.

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
Ikke endre gaten, loggen, `ssh_config` eller `.claude/settings.json`.
