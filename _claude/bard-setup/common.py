"""Shared constants and helpers for the admin-side package tools (run on Andreas's Mac)."""

import json
from pathlib import Path
import subprocess

SOURCE = Path(__file__).resolve().parent
HOST = 'bimver_28287@jana-osl.servebolt.cloud'
HOSTNAME = 'jana-osl.servebolt.cloud'
REMOTE_HOME = '/cust/0/bimver_15781/bimver_28287/home'
WP_PATH = REMOTE_HOME + '/site/public'
ONBOARDING_DIR = REMOTE_HOME + '/.bimverdi-onboarding'
GATE_DIR = REMOTE_HOME + '/.bimverdi-gate'
GATE_PATH = GATE_DIR + '/gate.py'
GATE_DATA_DIR = REMOTE_HOME + '/private/bard-gate'
PACKAGE_VERSION = 2


def ssh_python(code, timeout=60):
    """Run a Python program on the server through stdin. Returns stdout."""
    result = subprocess.run(
        ['ssh', '-o', 'BatchMode=yes', '-o', 'ConnectTimeout=15', HOST, 'python3 -'],
        input=code, text=True, capture_output=True, timeout=timeout)
    if result.returncode != 0:
        raise RuntimeError('Serverkommandoen feilet: ' + (result.stderr.strip() or result.stdout.strip()))
    return result.stdout


def label_for(enrollment_id):
    return 'bimverdi-bard-' + enrollment_id


def gate_command(enrollment_id):
    return '/usr/bin/python3 ' + GATE_PATH + ' ' + label_for(enrollment_id)


def trusted_host_key():
    """Return the single trusted Ed25519 host key line from the admin's known_hosts."""
    known = subprocess.run(['ssh-keygen', '-F', HOSTNAME], text=True, capture_output=True, check=True).stdout
    host_keys = [line for line in known.splitlines() if line and not line.startswith('#')
                 and line.split()[1] == 'ssh-ed25519']
    if len(host_keys) != 1:
        raise RuntimeError('Forventet nøyaktig én Ed25519-vertsnøkkel for ' + HOSTNAME + ' i known_hosts')
    return HOSTNAME + ' ' + ' '.join(host_keys[0].split()[1:3]) + '\n'


def read_admin_state(folder):
    path = Path(folder) / 'admin-package.json'
    if not path.exists():
        raise SystemExit('Fant ikke admin-package.json i ' + str(folder))
    return path, json.loads(path.read_text())


def write_admin_state(path, state):
    path.write_text(json.dumps(state, indent=2) + '\n')
    path.chmod(0o600)


# Remote helpers used by activate/revoke. The lock is the same one enroll.py takes, so state and
# key changes from the admin side never interleave with a running enrollment.
REMOTE_KEYS_LIB = r'''
import contextlib, fcntl, json, os, tempfile
from pathlib import Path

SSH_DIR = Path.home() / '.ssh'
AUTH = SSH_DIR / 'authorized_keys'

@contextlib.contextmanager
def keys_lock():
    SSH_DIR.mkdir(mode=0o700, exist_ok=True)
    with (SSH_DIR / '.bimverdi-keys.lock').open('a') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX)
        yield

def atomic_write(path, content, mode=0o600):
    path = Path(path)
    fd, name = tempfile.mkstemp(dir=path.parent, prefix='.' + path.name + '-')
    try:
        with os.fdopen(fd, 'wb') as stream:
            stream.write(content if isinstance(content, bytes) else content.encode())
            stream.flush()
            os.fsync(stream.fileno())
        os.chmod(name, mode)
        os.replace(name, path)
    finally:
        if os.path.exists(name):
            os.unlink(name)

def key_line_parts(line):
    """(options, key_blob, comment) for an authorized_keys line, or None."""
    parts = line.split()
    if len(parts) < 2:
        return None
    for index, part in enumerate(parts):
        if part in ('ssh-ed25519', 'ssh-rsa', 'ecdsa-sha2-nistp256', 'sk-ssh-ed25519@openssh.com') and index + 1 < len(parts):
            return ' '.join(parts[:index]), parts[index + 1], ' '.join(parts[index + 2:])
    return None

def rewrite_authorized_keys(drop, add_lines):
    """Caller holds keys_lock(). drop(options, blob, comment) -> True removes the line."""
    lines = AUTH.read_text().splitlines(keepends=True) if AUTH.exists() else []
    kept = []
    for line in lines:
        parsed = key_line_parts(line)
        if parsed and drop(*parsed):
            continue
        kept.append(line)
    removed = len(lines) - len(kept)
    if kept and not kept[-1].endswith('\n'):
        kept[-1] += '\n'
    content = ''.join(kept) + ''.join(line.rstrip('\n') + '\n' for line in add_lines)
    atomic_write(AUTH, content, 0o600)
    return removed, len(add_lines)
'''
