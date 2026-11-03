#!/usr/bin/env python3
"""Revoke a package on the server: removes its bootstrap key and its permanent key, marks it revoked.

  revoke-package.py --list            show every BIM Verdi line in authorized_keys (keys masked)
  revoke-package.py --id <pakke-id>   revoke one package (bootstrap + permanent key)
  revoke-package.py --all             revoke every package created by these tools

Lines that do not belong to these tools are never touched. Logs and database dumps are kept.
"""

import argparse
import json

from common import ONBOARDING_DIR, REMOTE_KEYS_LIB, ssh_python


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    group = parser.add_mutually_exclusive_group(required=True)
    group.add_argument('--list', action='store_true')
    group.add_argument('--id')
    group.add_argument('--all', action='store_true')
    args = parser.parse_args()
    if args.list:
        program = '''
import re
from pathlib import Path
auth = Path.home() / '.ssh/authorized_keys'
lines = auth.read_text().splitlines() if auth.exists() else []
for line in lines:
    if 'bimverdi-bard-' in line or '.bimverdi-onboarding/' in line:
        print(re.sub(r'(ssh-ed25519 [A-Za-z0-9+/]{8})[A-Za-z0-9+/=]+', r'\\1…', line))
print('Totalt', len(lines), 'linje(r) i authorized_keys.')
'''
        print(ssh_python(program).strip())
        return
    program = REMOTE_KEYS_LIB + f'''
import datetime, json
from pathlib import Path
onboarding = Path({ONBOARDING_DIR!r})
target = {args.id!r}
ids = [target] if target else sorted(p.name for p in onboarding.iterdir() if p.is_dir()) if onboarding.exists() else []
if target and not (onboarding / target).exists():
    print('Ingen pakke med id', target, 'på serveren. Fjerner likevel linjer som bærer id-en.')
markers = []
for item in ids:
    markers += [' ' + item + '\\n', ' ' + item, 'bimverdi-bard-' + item, '.bimverdi-onboarding/' + item + '/']
removed = 0
if markers:
    removed, _ = edit_authorized_keys(markers, [])
for item in ids:
    state_path = onboarding / item / 'enrollment.json'
    if state_path.exists():
        state = json.loads(state_path.read_text())
        state['revoked'] = True
        state['revoked_at'] = datetime.datetime.now(datetime.timezone.utc).isoformat(timespec='seconds')
        state_path.write_text(json.dumps(state, indent=2) + '\\n')
print(json.dumps({{'packages': ids, 'removed_lines': removed}}))
'''
    print(ssh_python(program).strip())


if __name__ == '__main__':
    main()
