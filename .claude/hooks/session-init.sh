#!/bin/bash

# BIM Verdi - Session Start Hook
# Injiserer kontekst fra session-log.md ved hver sesjonstart

PROJECT_ROOT="/Applications/MAMP/htdocs/bimverdi-v2"
SESSION_LOG="$PROJECT_ROOT/wp-content/_claude/session-log.md"
CLAUDE_MD="$PROJECT_ROOT/CLAUDE.md"

# Bygg kontekst-melding
CONTEXT=""

if [ -f "$SESSION_LOG" ]; then
    CONTEXT="$CONTEXT\n\n## session-log.md (sist status):\n$(cat "$SESSION_LOG")"
fi

# Output som JSON med additionalContext
if [ -n "$CONTEXT" ]; then
    # Escape for JSON
    ESCAPED_CONTEXT=$(echo -e "$CONTEXT" | jq -Rs .)

    cat <<EOF
{
  "hookSpecificOutput": {
    "hookEventName": "SessionStart",
    "additionalContext": $ESCAPED_CONTEXT
  }
}
EOF
fi

exit 0
