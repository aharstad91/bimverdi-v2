#!/usr/bin/env python3
"""Prepare a private Windows bundle and provision its expiring SSH bootstrap."""

import argparse
import base64
import datetime as dt
import io
import json
from pathlib import Path
import secrets
import subprocess
import tarfile
import zipfile


SOURCE = Path(__file__).resolve().parent
HOST = 'bimver_28287@jana-osl.servebolt.cloud'
HOSTNAME = 'jana-osl.servebolt.cloud'
REMOTE_HOME = '/cust/0/bimver_15781/bimver_28287/home'


def ssh(command, content=None):
    return subprocess.run(['ssh', '-o', 'BatchMode=yes', '-o', 'ConnectTimeout=15', HOST, command],
                          input=content, text=True, capture_output=True, check=True).stdout


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, required=True)
    parser.add_argument('--context-repo', type=Path)
    parser.add_argument('--days', type=int, default=7, choices=range(1, 15))
    args = parser.parse_args()
    output = args.output.expanduser().resolve()
    if output.is_relative_to(SOURCE.parents[2]):
        parser.error('Private bundles must be stored outside the WordPress web root')
    output.mkdir(parents=True, exist_ok=True, mode=0o700)
    output.chmod(0o700)
    now = dt.datetime.now(dt.timezone.utc)
    enrollment_id = 'bard-' + now.strftime('%Y%m%d') + '-' + secrets.token_hex(4)
    folder = output / enrollment_id
    folder.mkdir(mode=0o700)
    key = folder / '.enrollment_key'
    subprocess.run(['ssh-keygen', '-q', '-t', 'ed25519', '-N', '', '-C', enrollment_id, '-f', str(key)], check=True)
    key.chmod(0o600)
    public_key = ' '.join(key.with_suffix('.pub').read_text().split()[:2])
    expires = now + dt.timedelta(days=args.days)
    remote_dir = REMOTE_HOME + '/.bimverdi-onboarding/' + enrollment_id
    manifest = {
        'id': enrollment_id, 'created_at': now.isoformat(), 'expires_at': expires.isoformat(),
        'bootstrap_key': public_key, 'remote_helper': remote_dir + '/enroll.py',
    }
    known = subprocess.run(['ssh-keygen', '-F', HOSTNAME], text=True, capture_output=True, check=True).stdout
    host_keys = [line for line in known.splitlines() if line and not line.startswith('#')
                 and line.split()[1] == 'ssh-ed25519']
    if len(host_keys) != 1:
        raise RuntimeError('Expected one trusted Ed25519 host key in known_hosts')
    host_key = HOSTNAME + ' ' + ' '.join(host_keys[0].split()[1:3]) + '\n'
    helper = (SOURCE / 'server-enroll.py').read_bytes()
    force = '/usr/bin/python3 ' + remote_dir + '/enroll.py'
    entry = ('restrict,expiry-time="' + expires.strftime('%Y%m%d%H%M%SZ')
             + '",command="' + force + '" ' + public_key + ' ' + enrollment_id)
    # All interpolated values are Python literals, never shell command fragments.
    provision = f'''import base64,fcntl,json,os,tempfile
from pathlib import Path
p=Path({remote_dir!r})
p.mkdir(parents=True,exist_ok=False,mode=0o700)
p.parent.chmod(0o700)
(p/'enroll.py').write_bytes(base64.b64decode({base64.b64encode(helper).decode()!r}))
(p/'enroll.py').chmod(0o600)
(p/'enrollment.json').write_text({json.dumps(manifest, indent=2)!r}+'\\n')
(p/'enrollment.json').chmod(0o600)
auth=Path.home()/'.ssh/authorized_keys'
with (auth.parent/'.bimverdi-keys.lock').open('a') as lock:
 fcntl.flock(lock,fcntl.LOCK_EX)
 old=auth.read_text() if auth.exists() else ''
 if old and not old.endswith('\\n'): old+='\\n'
 fd,name=tempfile.mkstemp(dir=auth.parent,prefix='.authorized_keys-')
 with os.fdopen(fd,'w') as stream:
  stream.write(old+{entry!r}+'\\n')
  stream.flush()
  os.fsync(stream.fileno())
 os.replace(name,auth)
print('Enrollment provisioned')
'''
    files = {name: (SOURCE / name).read_bytes() for name in (
        'Start-BIMVerdi.cmd', 'Setup-BIMVerdi.ps1', 'BIMVerdi.ps1', 'Invoke-Ssh.ps1', 'CLAUDE.md', 'LES-MEG.txt')}
    # Windows PowerShell 5.1 needs a BOM to read UTF-8 Norwegian text correctly.
    for name in list(files):
        if name.endswith('.ps1'):
            files[name] = b'\xef\xbb\xbf' + files[name]
    files['known_hosts'] = host_key.encode()
    files['enrollment.json'] = (json.dumps(manifest, indent=2) + '\n').encode()
    files['.enrollment_key'] = key.read_bytes()
    files['.gitignore'] = b'*\n'
    if args.context_repo:
        ref = 'origin/BV-2.0-main'
        commit = subprocess.check_output(['git', '-C', str(args.context_repo), 'rev-parse', ref], text=True).strip()
        archive = subprocess.check_output(['git', '-C', str(args.context_repo), 'archive', ref, 'bedrift', 'CLAUDE.md'])
        with tarfile.open(fileobj=io.BytesIO(archive)) as source:
            for member in source:
                if member.isfile() and member.name.endswith('.md'):
                    files['kontekst/' + member.name] = source.extractfile(member).read()
        files['kontekst/KILDE.txt'] = ('Kilde: baardkr/bimverdi-context\nGren: BV-2.0-main\nCommit: '
                                      + commit + '\nHentet: ' + now.isoformat() + '\n').encode()
    bundle = output / ('BIMVerdi-Windows-' + enrollment_id + '.zip')
    with zipfile.ZipFile(bundle, 'w', compression=zipfile.ZIP_DEFLATED) as target:
        for name, content in files.items():
            target.writestr('BIMVerdi-startpakke/' + name, content)
    bundle.chmod(0o600)
    try:
        ssh('python3 -', provision)
    except Exception:
        bundle.unlink(missing_ok=True)
        raise
    receipt = {
        'id': enrollment_id, 'bundle': str(bundle), 'expires_at': manifest['expires_at'],
        'remote_helper': manifest['remote_helper'], 'bootstrap_key': public_key,
        'revoke_hint': 'Remove the authorized_keys line containing this bootstrap public key; keep all other lines.',
    }
    (folder / 'admin-receipt.json').write_text(json.dumps(receipt, indent=2) + '\n')
    (folder / 'admin-receipt.json').chmod(0o600)
    print(json.dumps({'bundle': str(bundle), 'expires_at': manifest['expires_at'], 'id': enrollment_id}))


if __name__ == '__main__':
    main()
