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


# Remote helper used by activate/revoke: edits authorized_keys under the shared lock.
# It removes every line that matches a marker and appends the given lines.
REMOTE_KEYS_LIB = r'''
import fcntl, os, tempfile
from pathlib import Path

def edit_authorized_keys(remove_markers, add_lines):
    ssh_dir = Path.home() / '.ssh'
    ssh_dir.mkdir(mode=0o700, exist_ok=True)
    auth = ssh_dir / 'authorized_keys'
    with (ssh_dir / '.bimverdi-keys.lock').open('a') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX)
        lines = auth.read_text().splitlines(keepends=True) if auth.exists() else []
        kept = [line for line in lines if not any(marker in line for marker in remove_markers)]
        removed = len(lines) - len(kept)
        if kept and not kept[-1].endswith('\n'):
            kept[-1] += '\n'
        content = ''.join(kept) + ''.join(line.rstrip('\n') + '\n' for line in add_lines)
        fd, name = tempfile.mkstemp(dir=ssh_dir, prefix='.authorized_keys-')
        try:
            with os.fdopen(fd, 'w') as stream:
                stream.write(content)
                stream.flush()
                os.fsync(stream.fileno())
            os.chmod(name, 0o600)
            os.replace(name, auth)
        finally:
            if os.path.exists(name):
                os.unlink(name)
        return removed, len(add_lines)
'''
