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

En ZIP på avveie er verdiløs uten koden. Fem feil koder sperrer pakken. Aktivering kan gjentas på en ubrukt pakke;
da får den ny kode og nytt utløp.

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
- `~/private/bard-gate/db/`: komprimerte databasekopier (`wp db export | gzip`), maks én per 10 min, 10 siste.
- `~/private/bard-gate/moved/`: SQL-dumper gaten har flyttet ut av `site/public`.

Engangsnøkkelen har `restrict`, `expiry-time` og `enroll.py` som tvungen kommando.
`enroll.py` krever riktig engangskode og bekreftet ansvar, binder pakken til den første nøkkelen,
og legger inn Bårds nøkkel med `restrict,command="gate.py <etikett>"`. `finalize` fjerner engangsnøkkelen.

Gaten:
- `sh -s` med skript på stdin er normalveien (det `BIMVerdi.ps1` bruker). Et vanlig kommandoargument kjøres via `sh -c`.
- Sperrer det som aldri er legitimt (se listen i `CLAUDE.md`). Returkode 77.
- Klassifiserer skriptet som lesende eller skrivende. Skrivende skript utløser databasekopi, og etterpå
  PHP-syntakssjekk av nye filer, flytting av SQL-dumper fra webroten og en HTTP-sjekk av `wp-login.php`.
- `gate-log [n]` og `gate-backups` som kommandoargument gir Bård innsyn uten å røre loggmappen.

Kjente begrensninger, med vilje:
- Gaten kjører som samme Linux-bruker som Bård. Han (eller agenten hans) kan redigere eller omgå den.
  Sperrelisten er et uhellsvern, ikke en sikkerhetsgrense. Loggen er bevis, ikke sperre.
- Servebolts API-nøkkel (`~/environment.json`) og serverens deploy-nøkkel (`~/.ssh/id_ed25519`) er lesbare
  for kontoen. Gaten sperrer omtale av filene, men ikke mer.
- `scp`/`sftp` er sperret. Kode går via Git, opplastinger via wp-admin.

## GitHub

Repo `aharstad91/bimverdi-v2` (offentlig). Ruleset «main: PR-flyt, ingen force-push» (id 26451329):
pull request påkrevd med 0 godkjenninger, ingen force-push, ingen sletting, påkrevd statussjekk `php-lint`.
Repository admin (Andreas) kan omgå. `baardkr` har `write` og merger selv.
Workflow: `.github/workflows/php-lint.yml` kjører `php -l` på alle PHP-filer i `themes/`, `mu-plugins/` og
`plugins/bim-verdi-core/` med PHP 8.4 (samme som Servebolt).

## Verifisering

```sh
BIMVERDI_TEST_PWSH=~/.codex/artifacts/bimverdi-bard-setup-20261103/tools/pwsh \
python3 -m unittest discover -s _claude/bard-setup/tests -v
```

- `test_enrollment.py`: kode, sperring etter fem feil, binding, utløp (ikke for finalize), tilbakekalling,
  ugyldig input, samtidige forsøk, gate-kommando på den permanente nøkkelen.
- `test_gate.py`: sperreliste, les/skriv-klassifisering, databasekopi og rotasjon, logg, meta-kommandoer.
- `test_transport.py` og `test_powershell_syntax.py`: PowerShell-hjelperen og parsing av skriptene (trenger `pwsh`).
- `tests/live-smoke.py` kjører hele flyten mot Servebolt med en engangspakke og rydder etter seg.

Windows-rettigheter, installasjon av OpenSSH og skrivebordssnarveien må verifiseres på Bårds PC (bimverdi-82o).
