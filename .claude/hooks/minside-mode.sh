#!/bin/bash
# Hook: Aktiveres når bruker skriver @minside i meldingen
# Leser og returnerer Min Side skill som kontekst

SKILL_DIR="$(dirname "$0")/../skills/minside"

if [ -f "$SKILL_DIR/SKILL.md" ]; then
    echo "## Min Side Mode Aktivert"
    echo ""
    echo "Full kontekst for /min-side/ utvikling er lastet."
    echo ""
    cat "$SKILL_DIR/SKILL.md"
fi
