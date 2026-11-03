# Startpakke for Bårds Windows-PC

Pakken setter opp produksjonstilgang fra Claude Code via SSH og WP-CLI.
Den lager Bårds permanente nøkkel på hans PC.
En tidsbegrenset nøkkel kan bare registrere én permanent nøkkel.
Oppsettet fjerner engangstilgangen etter at det har testet Bårds nøkkel.

## Lag en pakke

Kjør fra repoets `wp-content`-mappe med fungerende SSH-tilgang til Servebolt:

```sh
python3 _claude/bard-setup/prepare-package.py \
  --output ~/.codex/artifacts/bimverdi-bard-setup \
  --context-repo /Applications/MAMP/htdocs/bimverdi-context
```

Hent `origin/BV-2.0-main` i kontekstrepoet først.
Byggeren henter kravkopien fra denne referansen. Den tar ikke med lokale, uferdige endringer.
Parameteren `--context-repo` er valgfri.

Kommandoen lager ZIP-filen utenfor nettstedets offentlige mappe.
Den registrerer en engangsnøkkel på serveren. Tilgangen utløper etter sju dager.
Send ZIP-filen privat til Bård. Han følger `LES-MEG.txt`.
Legg aldri pakken, den private engangsnøkkelen eller databasekopier i Git.

## Tilgang på serveren

Serveren lagrer oppsettet i `~/.bimverdi-onboarding/<pakke-id>/`.
Engangsnøkkelen har en tvungen kommando og et utløpstidspunkt i `~/.ssh/authorized_keys`.
Kommandoen avviser ugyldige nøkler og binder pakken til den første nøkkelen som blir registrert.
Låsing hindrer at samtidige forsøk registrerer ulike nøkler.
Gjenkjøring med samme nøkkel er tillatt, slik at et avbrutt oppsett kan fortsette.

Bårds permanente nøkkel får vanlig kommandotilgang som den eksisterende nettstedskontoen `bimver_28287`.
`restrict` slår av videresending av porter, agent og X11, samt PTY.
Nøkkelen gir tilgang til produksjonens filer og data.
Dette oppretter ingen egen Linux-bruker og gir ingen root-tilgang.

For å trekke tilbake tilgangen, fjern bare linjen merket `bimverdi-bard-<pakke-id>` fra `authorized_keys`.
For en ubrukt pakke, fjern bare engangsnøkkelen som står i den lokale kvitteringen `admin-receipt.json`.
Bevar alle andre nøkler. Opprett en ny pakke hvis den gamle utløper.

## Verifisering

```sh
python3 -m unittest discover -s _claude/bard-setup/tests -v
```

Seks prosesstester dekker registrering, gjenkjøring, nøkkelbinding, utløp, ugyldig input og samtidige forsøk.
En transporttest kontrollerer UTF-8, linjeskift, filstier med mellomrom, feilmeldinger og returkode.
Transporttesten trenger `pwsh` på en POSIX-maskin. Oppgi banen med `BIMVERDI_TEST_PWSH` hvis verktøyet mangler i PATH.
En separat test mot Servebolt bekreftet nøkkelbyttet og at engangsnøkkelen sluttet å virke.
PowerShell-hjelperen besto en test mot serveren med norske tegn og anførselstegn.
PowerShell-parseren godkjente alle tre skriptene.
Testen brukte PowerShell på Mac. Windows-rettigheter, installasjon av OpenSSH og skrivebordssnarveien må verifiseres på Bårds PC.

GitHub-invitasjonen til `baardkr` må godtas før han får skrivetilgang til koderepoet.
Ingen lokal WordPress-installasjon, testdatabase eller nettleserinnlogging inngår i pakken.
