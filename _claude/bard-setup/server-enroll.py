#!/usr/bin/env python3
"""Exchange one expiring SSH enrollment credential plus a one-time code for one gated, client-owned key.

Installed as ~/.bimverdi-onboarding/<id>/enroll.py and run as the forced command of the bootstrap key.
State lives in enrollment.json next to this file. Requests are one JSON object on stdin:

  {"action": "ping"}
  {"action": "enroll", "public_key": "ssh-ed25519 AAAA...", "code": "123456",
   "accepted_responsibility": true, "client_name": "..."}
  {"action": "finalize", "public_key": "ssh-ed25519 AAAA..."}
"""

import base64
import datetime as dt
import fcntl
import hashlib
import hmac
import json
import os
from pathlib import Path
import struct
import sys
import tempfile

MAX_ATTEMPTS = 5


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
        os.chmod(name, 0o600)
        os.replace(name, path)
    finally:
        if os.path.exists(name):
            os.unlink(name)


def now():
    return dt.datetime.now(dt.timezone.utc)


def record(state_dir, event, **details):
    with (state_dir / 'events.log').open('a') as stream:
        stream.write(json.dumps({'ts': now().isoformat(timespec='seconds'), 'event': event,
                                 'client': (os.environ.get('SSH_CONNECTION') or '').split(' ')[0], **details}) + '\n')


def code_matches(state, code):
    if not isinstance(code, str) or not code.strip():
        return False
    digest = hashlib.sha256((state['code_salt'] + code.strip()).encode()).hexdigest()
    return hmac.compare_digest(digest, state['code_hash'])


def strip_lines(content, predicate):
    return ''.join(line for line in content.splitlines(keepends=True) if not predicate(line))


def main():
    payload = sys.stdin.read(4097)
    if len(payload) > 4096:
        raise ValueError('Request too large')
    request = json.loads(payload)
    if not isinstance(request, dict):
        raise ValueError('Request must be a JSON object')
    action = request.get('action')
    if action not in ('ping', 'enroll', 'finalize'):
        raise ValueError('Unsupported action')
    state_dir = Path(__file__).resolve().parent
    state_path = state_dir / 'enrollment.json'
    ssh_dir = Path.home() / '.ssh'
    authorized_path = ssh_dir / 'authorized_keys'
    with (ssh_dir / '.bimverdi-keys.lock').open('a') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX)
        state = json.loads(state_path.read_text())
        expires = dt.datetime.fromisoformat(state['expires_at'])
        expired = now() >= expires
        bootstrap_blob = state['bootstrap_key'].split()[1]
        content = authorized_path.read_text() if authorized_path.exists() else ''

        if action == 'ping':
            print(json.dumps({'ok': True, 'action': action, 'id': state['id'], 'expires_at': state['expires_at'],
                              'expired': expired, 'registered': bool(state.get('registered_key')),
                              'finalized': bool(state.get('finalized')), 'revoked': bool(state.get('revoked'))}))
            return

        key, fingerprint = parse_key(request.get('public_key'))
        if state.get('revoked'):
            raise ValueError('This package has been revoked; ask Andreas for a new one')
        registered = state.get('registered_key')
        if registered and registered != key:
            record(state_dir, 'rejected-other-key', fingerprint=fingerprint, action=action)
            raise ValueError('This package is already bound to another key')

        if action == 'enroll':
            if state.get('finalized'):
                print(json.dumps({'ok': True, 'action': action, 'fingerprint': fingerprint, 'finalized': True}))
                return
            if expired:
                raise ValueError('Enrollment expired; ask Andreas for a new package')
            if state.get('attempts', 0) >= MAX_ATTEMPTS:
                raise ValueError('Too many wrong codes; this package is locked. Ask Andreas for a new one')
            if not registered and not code_matches(state, request.get('code')):
                state['attempts'] = state.get('attempts', 0) + 1
                left = MAX_ATTEMPTS - state['attempts']
                if left <= 0:
                    # Burn the bootstrap credential: nothing can be done with this package any more.
                    content = strip_lines(content, lambda line: bootstrap_blob in line.split())
                    atomic_write(authorized_path, content)
                    state['burned_at'] = now().isoformat(timespec='seconds')
                atomic_write(state_path, json.dumps(state, indent=2) + '\n')
                record(state_dir, 'wrong-code', fingerprint=fingerprint, attempts=state['attempts'])
                raise ValueError('Wrong one-time code (' + str(max(left, 0)) + ' attempts left)')
            if request.get('accepted_responsibility') is not True:
                raise ValueError('The responsibility statement must be accepted before access is granted')
            # Persist the binding before granting access, so a retry cannot switch identities.
            if not registered:
                state['registered_key'] = key
                state['registered_fingerprint'] = fingerprint
                state['registered_at'] = now().isoformat(timespec='seconds')
                state['registered_from'] = (os.environ.get('SSH_CONNECTION') or '').split(' ')[0]
                state['client_name'] = str(request.get('client_name', ''))[:120]
                state['accepted_responsibility_at'] = state['registered_at']
                atomic_write(state_path, json.dumps(state, indent=2) + '\n')
            blob = key.split()[1]
            # Drop any older line carrying the same key (for example from a previous package) so the
            # gate and label of this package always apply.
            content = strip_lines(content, lambda line: blob in line.split())
            content += ('' if not content or content.endswith('\n') else '\n')
            content += 'restrict,command="' + state['gate_command'] + '" ' + key + ' ' + state['label'] + '\n'
            atomic_write(authorized_path, content)
            record(state_dir, 'enrolled', fingerprint=fingerprint)
            print(json.dumps({'ok': True, 'action': action, 'fingerprint': fingerprint, 'label': state['label']}))
        else:
            if registered != key:
                raise ValueError('Register the key before finalizing')
            content = strip_lines(content, lambda line: bootstrap_blob in line.split())
            atomic_write(authorized_path, content)
            state['finalized'] = True
            state['finalized_at'] = now().isoformat(timespec='seconds')
            atomic_write(state_path, json.dumps(state, indent=2) + '\n')
            record(state_dir, 'finalized', fingerprint=fingerprint)
            print(json.dumps({'ok': True, 'action': action, 'fingerprint': fingerprint}))


if __name__ == '__main__':
    try:
        main()
    except (ValueError, KeyError, OSError, TypeError, AttributeError) as error:
        print(json.dumps({'ok': False, 'error': str(error)}))
        sys.exit(1)
