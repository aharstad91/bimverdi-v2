#!/bin/bash
# Hook: Aktiveres når bruker skriver @design i meldingen
# Leser og returnerer design skill-filene som kontekst

SKILL_DIR="$(dirname "$0")/../skills/bimverdi-design"

if [ -f "$SKILL_DIR/SKILL.md" ]; then
    echo "## Design Mode Aktivert"
    echo ""
    echo "Følgende design-regler gjelder for denne sesjonen:"
    echo ""
    cat "$SKILL_DIR/SKILL.md"
    echo ""
    echo "---"
    echo ""
    echo "## Design Tokens (quick reference)"
    echo ""
    cat "$SKILL_DIR/design-tokens.md"
fi
