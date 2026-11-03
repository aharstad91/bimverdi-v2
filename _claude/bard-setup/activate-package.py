#!/usr/bin/env python3
"""Arm a built bundle right before it is handed over.

Installs the gate and the enrollment helper on the server, registers the bootstrap key with a
short expiry, and prints a one-time code. Read the code to Bård by phone; never send it with
the ZIP. Re-running on an unused package replaces the code and the expiry.
"""

import argparse
import base64
import datetime as dt
import json
import hashlib
from pathlib import Path
import secrets
import subprocess

from common import (GATE_DATA_DIR, GATE_DIR, HOST, REMOTE_KEYS_LIB, SOURCE, read_admin_state, ssh_python,
                    write_admin_state)


def ping(state):
    """Use the bootstrap key from here to confirm the server side answers."""
    result = subprocess.run(
        ['ssh', '-o', 'BatchMode=yes', '-o', 'IdentitiesOnly=yes', '-o', 'ConnectTimeout=15',
         '-i', state['bootstrap_private_key'], HOST, 'ping'],
        input='{"action":"ping"}', text=True, capture_output=True, timeout=60)
    if result.returncode != 0:
        raise RuntimeError('Ping via engangsnøkkelen feilet: ' + (result.stderr.strip() or result.stdout.strip()))
    return json.loads(result.stdout)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('folder', type=Path, help='Package folder created by prepare-package.py')
    parser.add_argument('--hours', type=int, default=24, choices=range(1, 169), metavar='1-168')
    parser.add_argument('--force', action='store_true', help='Re-arm even if a key is already registered')
    args = parser.parse_args()
    state_path, state = read_admin_state(args.folder.expanduser().resolve())
    now = dt.datetime.now(dt.timezone.utc)
    expires = now + dt.timedelta(hours=args.hours)
    code = ''.join(secrets.choice('0123456789') for _ in range(6))
    salt = secrets.token_hex(16)
    code_hash = hashlib.sha256((salt + code).encode()).hexdigest()
    manifest = {
        'id': state['id'], 'label': state['label'], 'package_version': state['package_version'],
        'created_at': state['created_at'], 'activated_at': now.isoformat(timespec='seconds'),
        'expires_at': expires.isoformat(timespec='seconds'), 'bootstrap_key': state['bootstrap_public_key'],
        'gate_command': state['gate_command'], 'code_salt': salt, 'code_hash': code_hash, 'attempts': 0,
    }
    bootstrap_line = ('restrict,expiry-time="' + expires.strftime('%Y%m%d%H%M%SZ') + '",command="/usr/bin/python3 '
                      + state['remote_dir'] + '/enroll.py" ' + state['bootstrap_public_key'] + ' ' + state['id'])
    enroll = base64.b64encode((SOURCE / 'server-enroll.py').read_bytes()).decode()
    gate = base64.b64encode((SOURCE / 'server-gate.py').read_bytes()).decode()
    # Every interpolated value is a Python literal; nothing is passed through a shell.
    program = REMOTE_KEYS_LIB + f'''
import base64, json, os
from pathlib import Path
remote_dir = Path({state['remote_dir']!r})
force = {args.force!r}
existing = remote_dir / 'enrollment.json'
if existing.exists():
    old = json.loads(existing.read_text())
    if old.get('registered_key') and not force:
        raise SystemExit('Pakken er allerede bundet til en nøkkel. Bruk revoke-package.py, eller --force.')
    if old.get('revoked'):
        raise SystemExit('Pakken er trukket tilbake. Bygg en ny pakke.')
remote_dir.mkdir(parents=True, exist_ok=True, mode=0o700)
remote_dir.parent.chmod(0o700)
remote_dir.chmod(0o700)
(remote_dir / 'enroll.py').write_bytes(base64.b64decode({enroll!r}))
(remote_dir / 'enroll.py').chmod(0o600)
(remote_dir / 'enrollment.json').write_text({json.dumps(manifest, indent=2)!r} + '\\n')
(remote_dir / 'enrollment.json').chmod(0o600)
gate_dir = Path({GATE_DIR!r})
gate_dir.mkdir(parents=True, exist_ok=True, mode=0o700)
gate_dir.chmod(0o700)
gate_path = gate_dir / 'gate.py'
if gate_path.exists():
    gate_path.chmod(0o600)
gate_path.write_bytes(base64.b64decode({gate!r}))
gate_path.chmod(0o500)
for name in ('', 'log', 'db', 'moved'):
    folder = Path({GATE_DATA_DIR!r}) / name
    folder.mkdir(parents=True, exist_ok=True, mode=0o700)
    folder.chmod(0o700)
removed, added = edit_authorized_keys([{state['id']!r}], [{bootstrap_line!r}])
print(json.dumps({{'removed': removed, 'added': added}}))
'''
    print(ssh_python(program).strip())
    answer = ping(state)
    if not answer.get('ok') or answer.get('expired') or answer.get('id') != state['id']:
        raise SystemExit('Serveren svarte uventet på ping: ' + json.dumps(answer))
    state.setdefault('activations', []).append({
        'activated_at': manifest['activated_at'], 'expires_at': manifest['expires_at'], 'code_hash': code_hash})
    write_admin_state(state_path, state)
    print()
    print('Pakke:        ' + state['id'])
    print('ZIP:          ' + state['bundle'])
    print('Utløper:      ' + expires.astimezone().strftime('%Y-%m-%d %H:%M %Z'))
    print('Engangskode:  ' + code + '   <- les denne til Bård på telefon. Den står ikke i ZIP-en.')
    print('Send ZIP-en nå. Trekk tilbake med: python3 _claude/bard-setup/revoke-package.py --id ' + state['id'])


if __name__ == '__main__':
    main()
