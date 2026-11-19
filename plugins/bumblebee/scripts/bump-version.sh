#!/usr/bin/env bash
#
# Løpende versjonsbumping for ByggChat ALFA pilot.
# Oppdaterer versjonsnummeret ETT sted -> plugin-header + BBEE_VERSION + readme.
#
# Bruk:
#   ./scripts/bump-version.sh patch   # 0.7.0 -> 0.7.1  (standard)
#   ./scripts/bump-version.sh minor   # 0.7.1 -> 0.8.0
#   ./scripts/bump-version.sh major   # 0.8.0 -> 1.0.0
#   ./scripts/bump-version.sh 0.9.2   # sett eksakt versjon
#
set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MAIN="$DIR/bumblebee.php"
README="$DIR/readme.txt"

current="$(grep -oiE "define\( 'BBEE_VERSION', '([0-9]+\.[0-9]+\.[0-9]+)'" "$MAIN" | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')"
IFS='.' read -r MA MI PA <<< "$current"

arg="${1:-patch}"
case "$arg" in
  major) new="$((MA+1)).0.0" ;;
  minor) new="${MA}.$((MI+1)).0" ;;
  patch) new="${MA}.${MI}.$((PA+1))" ;;
  *[0-9].*[0-9].*[0-9]) new="$arg" ;;
  *) echo "Ukjent argument: $arg" >&2; exit 1 ;;
esac

sed -i -E "s/(\* Version:[[:space:]]*)[0-9]+\.[0-9]+\.[0-9]+/\1${new}/" "$MAIN"
sed -i -E "s/(define\( 'BBEE_VERSION', ')[0-9]+\.[0-9]+\.[0-9]+/\1${new}/" "$MAIN"
sed -i -E "s/(Stable tag:[[:space:]]*)[0-9]+\.[0-9]+\.[0-9]+/\1${new}/" "$README" || true

echo "Versjon: ${current} -> ${new}"
