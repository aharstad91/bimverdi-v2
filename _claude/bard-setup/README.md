# Startpakke for Bårds Windows-PC

Pakken gir Bårds Claude Code tilgang til produksjon via SSH og WP-CLI. Bård har tilgangen på eget ansvar.
Vernet er lagdelt: GitHub-ruleset på `main` (hardt), gate på serveren (logg, sperreliste, databasekopi) og
`CLAUDE.md`/`.claude/settings.json` i arbeidsmappen (mykt).

## Flyt for Andreas

```sh
cd wp-content
git -C /Applications/MAMP/htdocs/bimverdi-context fetch origin
python3 _claude/bard-setup/prepare-package.py \
  --output ~/.codex/artifacts/bimverdi-bard-setup \
  --context-repo /Applications/MAMP/htdocs/bimverdi-context
# ... ZIP-en er død til du aktiverer den. Aktiver rett før du sender:
python3 _claude/bard-setup/activate-package.py ~/.codex/artifacts/bimverdi-bard-setup/<pakke-id> --hours 24
```

`activate-package.py` installerer gaten og registreringshjelperen på serveren, legger inn engangsnøkkelen med
utløp, og skriver ut en engangskode på seks siffer. Les koden til Bård på telefon. Den står ikke i ZIP-en.
Send ZIP-en privat. Bård følger `LES-MEG.txt`.

En ZIP på avveie er verdiløs uten koden. Fem feil koder sperrer pakken. Den som har ZIP-en kan altså låse pakken,
men ikke få tilgang. Aktivering kan gjentas på en ubrukt pakke; da får den ny kode, nytt utløp og nullstilte forsøk.
`--force` erstatter en eksisterende binding: den gamle permanente nøkkelen mister tilgang, og det loggføres i `events.log`.

Trekk tilbake:

```sh
python3 _claude/bard-setup/revoke-package.py --list
python3 _claude/bard-setup/revoke-package.py --id <pakke-id>
```

Legg aldri pakken, engangsnøkkelen eller databasekopier i Git. `.gitignore` i denne mappen dekker det.

## På serveren

- `~/.bimverdi-onboarding/<pakke-id>/`: `enroll.py`, `enrollment.json` (tilstand, kodehash, binding), `events.log`.
- `~/.bimverdi-gate/gate.py`: tvungen kommando for Bårds permanente nøkkel. Overskrives ved hver aktivering.
- `~/private/bard-gate/log/<dato>.jsonl`: full logg, én `start`- og én `end`-linje per kall.
- `~/private/bard-gate/db/`: komprimerte databasekopier (`wp db export | gzip`), maks én per 10 min, 10 siste pluss
  dagens første i 7 dager. Kopiene dekker bare databasen. Feiler kopien, stoppes kommandoer som kan endre databasen (kode 76).
- `~/private/bard-gate/moved/`: SQL-dumper gaten har flyttet ut av `site/public`.

Engangsnøkkelen har `restrict`, `expiry-time` og `enroll.py` som tvungen kommando.
`enroll.py` krever riktig engangskode og bekreftet ansvar, binder pakken til den første nøkkelen,
og legger inn Bårds nøkkel med `restrict,command="gate.py <etikett>"`. `finalize` fjerner engangsnøkkelen.

Gaten:
- `sh -s` med skript på stdin er normalveien (det `BIMVerdi.ps1` bruker). Et vanlig kommandoargument kjøres via `sh -c`;
  stdin leses og logges også da.
- Sperrer det som aldri er legitimt (se listen i `CLAUDE.md`). Returkode 77.
- Klassifiserer skriptet som lesende eller skrivende. Skrivende skript utløser databasekopi, og etterpå
  PHP-syntakssjekk av nye filer, varsel om endringer i Git-styrte mapper, flytting av SQL-dumper fra webroten
  og HTTP-sjekk av `wp-login.php` og forsiden.
- `gate-log [n]` og `gate-backups` som kommandoargument gir Bård innsyn uten å røre loggmappen.

Kjente begrensninger, med vilje:
- Gaten kjører som samme Linux-bruker som Bård. Han (eller agenten hans) kan redigere eller omgå den.
  Sperrelisten er et uhellsvern, ikke en sikkerhetsgrense. Loggen er bevis, ikke sperre.
- Servebolts API-nøkkel (`~/environment.json`) og serverens deploy-nøkkel (`~/.ssh/id_ed25519`) er lesbare
  for kontoen. Gaten sperrer omtale av filene, men ikke mer.
- `scp`/`sftp` og bakgrunnskjøring (`&`) er sperret. Kode går via Git, opplastinger via wp-admin.
- Sperrelisten er regex og treffer også tekst (`grep 'rm -rf' fil` sperres). Loggen trunkerer skript over 64 KB,
  men sha256 av hele skriptet er med.

## GitHub

Repo `aharstad91/bimverdi-v2` (offentlig). Ruleset «main: PR-flyt, ingen force-push» (id 26451329):
pull request påkrevd med 0 godkjenninger, ingen force-push, ingen sletting, påkrevd statussjekk `php-lint`.
Repository admin (Andreas) kan omgå. `baardkr` har `write` og merger selv.
Workflow: `.github/workflows/php-lint.yml` kjører `php -l` på alle sporede PHP-filer med PHP 8.4 (samme som Servebolt).
En PR kan i prinsippet endre workflowen selv; `CLAUDE.md` forbyr det, og det synes i PR-diffen.

## Verifisering

```sh
BIMVERDI_TEST_PWSH=~/.codex/artifacts/bimverdi-bard-setup-20261103/tools/pwsh \
python3 -m unittest discover -s _claude/bard-setup/tests -v
```

- `test_enrollment.py`: kode, sperring etter fem feil, binding, utløp (ikke for finalize), tilbakekalling,
  ugyldig input, samtidige forsøk, gate-kommando på den permanente nøkkelen.
- `test_gate.py`: sperreliste, les/skriv-klassifisering, databasekopi og rotasjon, stopp uten kopi, etterkontroll, logg.
- `test_transport.py`, `test_setup_functions.py` og `test_powershell_syntax.py`: PowerShell-hjelperen, registreringskallet
  i Setup mot en falsk ssh, og parsing av skriptene (trenger `pwsh`).
- `tests/live-smoke.py` kjører hele flyten mot Servebolt med en engangspakke og rydder etter seg.

- `test_windows_regressions.py`: de tre feilene fra Bårds PC 05.11.2026 (se under).

Verifisert på Bårds PC (Windows 11, PowerShell 5.1) 05.11.2026: oppsett, registrering, statussjekk, gate og PR-flyt.
Tre Windows-feil kom fram og er rettet:
- `Set-Acl` krever SeSecurityPrivilege (admin). Setup låser nøkkelfilene med `icacls` i stedet.
- `ssh-keygen -y` på Windows skriver kommentaren bak nøkkelen. Setup sender bare type og nøkkel.
- Et dobbeltklikk på `Start-BIMVerdi.cmd` inne i ZIP-en kopierer bare den filen til Temp. Den stopper nå med en forklaring.

Windows PowerShell 5.1 på en UTF-8-konsoll legger en BOM (EF BB BF) foran stdin til ssh. Gaten fjerner den
før skriptet kjører. Uten det feilet første linje (`set -e`) stille.
