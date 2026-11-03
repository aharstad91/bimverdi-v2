#!/usr/bin/env python3
"""Revoke a package on the server: removes its bootstrap key and its permanent key, marks it revoked.

  revoke-package.py --list            show every BIM Verdi line in authorized_keys (keys masked)
  revoke-package.py --id <pakke-id>   revoke one package (bootstrap + permanent key)
  revoke-package.py --all             revoke every package created by these tools

Matching is exact: the line's comment equals the package id or its label, or the key blob equals
the key the package registered. Lines that do not belong to these tools are never touched.
State is marked revoked under the same lock enroll.py uses, before the keys are removed, so a
concurrent enrollment cannot put the key back. Logs and database dumps are kept.
"""

import argparse

from common import ONBOARDING_DIR, REMOTE_KEYS_LIB, ssh_python


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    group = parser.add_mutually_exclusive_group(required=True)
    group.add_argument('--list', action='store_true')
    group.add_argument('--id')
    group.add_argument('--all', action='store_true')
    args = parser.parse_args()
    if args.list:
        program = REMOTE_KEYS_LIB + '''
lines = AUTH.read_text().splitlines() if AUTH.exists() else []
for line in lines:
    parsed = key_line_parts(line)
    if parsed and ('bimverdi-bard-' in parsed[2] or '.bimverdi-onboarding/' in parsed[0]):
        print((parsed[0] + ' ' if parsed[0] else '') + 'ssh-ed25519 ' + parsed[1][:8] + '… ' + parsed[2])
print('Totalt', len(lines), 'linje(r) i authorized_keys.')
'''
        print(ssh_python(program).strip())
        return
    program = REMOTE_KEYS_LIB + f'''
import datetime
onboarding = Path({ONBOARDING_DIR!r})
target = {args.id!r}
if target:
    ids = [target]
else:
    ids = sorted(p.name for p in onboarding.iterdir() if p.is_dir()) if onboarding.exists() else []
if target and not (onboarding / target).exists():
    print('Ingen pakke med id', target, 'på serveren. Fjerner likevel linjer som bærer id-en.')
with keys_lock():
    blobs = set()
    for item in ids:
        state_path = onboarding / item / 'enrollment.json'
        if state_path.exists():
            state = json.loads(state_path.read_text())
            if state.get('registered_key'):
                blobs.add(state['registered_key'].split()[1])
            if state.get('bootstrap_key'):
                blobs.add(state['bootstrap_key'].split()[1])
            state['revoked'] = True
            state['revoked_at'] = datetime.datetime.now(datetime.timezone.utc).isoformat(timespec='seconds')
            atomic_write(state_path, json.dumps(state, indent=2) + '\\n')
    labels = set()
    for item in ids:
        labels.update({{item, 'bimverdi-bard-' + item}})
    def drop(options, blob, comment):
        return comment in labels or blob in blobs or any('.bimverdi-onboarding/' + item + '/' in options for item in ids)
    removed, _ = rewrite_authorized_keys(drop, [])
print(json.dumps({{'packages': ids, 'removed_lines': removed}}))
'''
    print(ssh_python(program).strip())


if __name__ == '__main__':
    main()
