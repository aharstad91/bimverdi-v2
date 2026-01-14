# Claude Code - Session Workflow Instructions

> **VIKTIG:** Les og følg disse instruksjonene i hver sesjon.

## Ved sesjonstart (ALLTID)

1. **Les session-log.md** i `wp-content/_claude/session-log.md`
2. **Gi kort oppsummering** av sist status til Andreas
3. **Sjekk "Neste steg"** for å se hva som bør prioriteres

## Under arbeid

### Oppdater session-log.md når du:
- Oppretter, endrer eller sletter filer
- Fikser bugs eller implementerer features
- Støter på blokkere eller problemer
- Fullfører en oppgave fra "Neste steg"

### Format for oppføringer:

```markdown
#### [Code] HH:MM - Kort beskrivelse
- Hva ble gjort
- Filer berørt: `filnavn.php`, `annen-fil.php`
- **Neste:** Eventuelt oppfølging
```

### Oppdater også:
- **"Sist oppdatert"**-seksjonen øverst
- **"Aktiv kontekst"** hvis fokus endrer seg
- **"Neste steg"** - huk av fullførte, legg til nye

## Ved sesjonslutt

Før du avslutter en lengre sesjon, oppdater session-log.md med:
1. Oppsummering av hva som ble gjort
2. Eventuelle uløste problemer
3. Anbefalte neste steg

## Kommunikasjon med Claude Chat

Andreas bruker også Claude Chat (samme app, annen fane) for:
- Strategisk planlegging
- Diskusjon av arkitektur og design
- Gjennomgang av dokumentasjon (som bimverdimvp.pdf)

Claude Chat oppdaterer også session-log.md. **Les alltid filen** for å se om Chat har lagt til beslutninger eller endret prioriteringer.

## Deployment Workflow (GitHub → Servebolt)

### ⚠️ OBLIGATORISK: Tailwind CSS SKAL bygges før HVER commit!

> **CLAUDE: Kjør ALLTID `npm run build` før git commit når du har endret template-filer (.php)!**
> Dette er IKKE valgfritt. Nye Tailwind-klasser havner ikke i prod uten dette steget.

```bash
# ALLTID denne rekkefølgen ved commit:
cd /Applications/MAMP/htdocs/bimverdi-v2/wp-content/themes/bimverdi-theme
npm run build
cd /Applications/MAMP/htdocs/bimverdi-v2/wp-content
git add .
git commit -m "feat: Description"
git push origin main
```

Tailwind genererer kun CSS for klasser som faktisk brukes i koden. Arbitrary values som `bg-[#1A1A1A]` krever kompilering.

### Hva blir deployet?

| Mappe/fil | Deployet? | Merknad |
|-----------|-----------|---------|
| `themes/bimverdi-theme/` | ✅ Ja | Inkludert `dist/` med kompilert CSS |
| `plugins/bim-verdi-core/` | ✅ Ja | Egenutviklet plugin |
| `mu-plugins/` | ❌ Nei | Må kopieres manuelt |
| `plugins/*` (andre) | ❌ Nei | Installeres via WP admin |
| `uploads/` | ❌ Nei | Media håndteres separat |

### Etter deploy - sjekkliste

1. **Tøm cache** på Servebolt (Caching → Purge)
2. **Hard refresh** i nettleser (`Cmd+Shift+R`)
3. **Test i incognito** for å utelukke nettleser-cache
4. **Sjekk plugins** er aktivert (ACF Pro, Gravity Forms, bim-verdi-core)

### Feilsøking: Styling fungerer lokalt men ikke live

1. **Sjekk om CSS inneholder klassen** - Søk i `dist/style.css` på live
2. **Kjør `npm run build`** lokalt og push på nytt
3. **Verifiser deploy** - Sjekk hash i Servebolt Git-panel matcher siste commit

## Viktige prosjektfiler

| Fil | Les når |
|-----|---------|
| `_claude/trello-workflow.md` | For Trello-bruk og ansvarsfordeling |
| `_claude/session-log.md` | **ALLTID** ved start |
| `CLAUDE.md` | Ved behov for prosjektoversikt |
| `_claude/ui-contract.md` | Før UI/template-arbeid |
| `_claude/redesign-progress.md` | For status på redesign |

## Eksempel på god sesjon

```
Andreas: "Fortsett der vi slapp"

Claude Code: *leser session-log.md*

"Ifølge session-log.md jobbet vi sist med X.
Chat har i mellomtiden besluttet Y.
Neste steg er Z. Skal jeg fortsette med det?"

*gjør arbeidet*

*oppdaterer session-log.md*

"Ferdig med Z. Har oppdatert session-log.md med endringene."
```

---

## Hook-konfigurasjon (gjenskape ved behov)

Hvis session-hook mangler, opprett filen `.claude/settings.local.json` i prosjektroten (`/Applications/MAMP/htdocs/bimverdi-v2/`) med dette innholdet:

```json
{
  "hooks": {
    "UserPromptSubmit": [
      {
        "matcher": "",
        "hooks": [
          {
            "type": "command",
            "command": "cat wp-content/_claude/session-log.md wp-content/_claude/instructions.md 2>/dev/null || true"
          }
        ]
      }
    ]
  }
}
```

Denne hooken leser session-log.md og instructions.md automatisk ved hver prompt.

---

**Denne filen ligger i:** `wp-content/_claude/instructions.md`
