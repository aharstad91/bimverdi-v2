#!/usr/bin/env python3
"""Exchange one expiring SSH enrollment credential for one client-owned key."""

import base64
import datetime as dt
import fcntl
import hashlib
import json
import os
from pathlib import Path
import struct
import sys
import tempfile


def parse_key(value):
    if not isinstance(value, str) or '\n' in value or '\r' in value:
        raise ValueError('Invalid public key')
    parts = value.split()
    if len(parts) != 2 or parts[0] != 'ssh-ed25519':
        raise ValueError('Expected an Ed25519 public key without options or comment')
    raw = base64.b64decode(parts[1], validate=True)
    prefix = struct.pack('>I', 11) + b'ssh-ed25519' + struct.pack('>I', 32)
    if len(raw) != len(prefix) + 32 or not raw.startswith(prefix):
        raise ValueError('Invalid Ed25519 public key')
    return ' '.join(parts), 'SHA256:' + base64.b64encode(hashlib.sha256(raw).digest()).decode().rstrip('=')


def atomic_write(path, content):
    fd, name = tempfile.mkstemp(prefix='.' + path.name + '-', dir=path.parent)
    try:
        with os.fdopen(fd, 'w') as stream:
            stream.write(content)
            stream.flush()
            os.fsync(stream.fileno())
        os.replace(name, path)
    finally:
        if os.path.exists(name):
            os.unlink(name)


def main():
    payload = sys.stdin.read(4097)
    if len(payload) > 4096:
        raise ValueError('Request too large')
    request = json.loads(payload)
    action = request.get('action')
    if action not in ('enroll', 'finalize'):
        raise ValueError('Unsupported action')
    key, fingerprint = parse_key(request.get('public_key'))
    state_path = Path(__file__).resolve().parent / 'enrollment.json'
    ssh_dir = Path.home() / '.ssh'
    authorized_path = ssh_dir / 'authorized_keys'
    with (ssh_dir / '.bimverdi-keys.lock').open('a') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX)
        state = json.loads(state_path.read_text())
        expires = dt.datetime.fromisoformat(state['expires_at'])
        if dt.datetime.now(dt.timezone.utc) >= expires:
            raise ValueError('Enrollment expired; ask Andreas for a new package')
        registered = state.get('registered_key')
        if registered and registered != key:
            raise ValueError('This package is already bound to another key')
        content = authorized_path.read_text() if authorized_path.exists() else ''
        if action == 'enroll':
            if state.get('finalized'):
                print(json.dumps({'ok': True, 'action': action, 'fingerprint': fingerprint, 'finalized': True}))
                return
            # Persist the binding before granting access, so a retry cannot switch identities.
            state['registered_key'] = key
            atomic_write(state_path, json.dumps(state, indent=2) + '\n')
            if not any(key.split()[1] in line.split() for line in content.splitlines()):
                content += ('' if not content or content.endswith('\n') else '\n')
                content += 'restrict ' + key + ' bimverdi-bard-' + state['id'] + '\n'
                atomic_write(authorized_path, content)
        else:
            if registered != key:
                raise ValueError('Register the key before finalizing')
            bootstrap_blob = state['bootstrap_key'].split()[1]
            content = ''.join(line for line in content.splitlines(keepends=True)
                              if bootstrap_blob not in line.split())
            atomic_write(authorized_path, content)
            state['finalized'] = True
            atomic_write(state_path, json.dumps(state, indent=2) + '\n')
        print(json.dumps({'ok': True, 'action': action, 'fingerprint': fingerprint}))


if __name__ == '__main__':
    try:
        main()
    except (ValueError, KeyError, OSError) as error:
        print(json.dumps({'ok': False, 'error': str(error)}))
        sys.exit(1)
