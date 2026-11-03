#!/usr/bin/env python3
"""Build a private Windows bundle for Bård. Nothing is provisioned on the server.

The bundle is inert until `activate-package.py` is run right before it is handed over:
activation registers the bootstrap key on the server with a short expiry and prints the
one-time code that Andreas reads to Bård by phone.
"""

import argparse
import datetime as dt
import io
import json
from pathlib import Path
import secrets
import subprocess
import tarfile
import zipfile

from common import (GATE_PATH, ONBOARDING_DIR, PACKAGE_VERSION, SOURCE, gate_command, label_for,
                    trusted_host_key, write_admin_state)

BUNDLE_FILES = ('Start-BIMVerdi.cmd', 'Setup-BIMVerdi.ps1', 'BIMVerdi.ps1', 'Invoke-Ssh.ps1',
                'CLAUDE.md', 'LES-MEG.txt')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, required=True, help='Folder outside the web root')
    parser.add_argument('--context-repo', type=Path, help='Local clone of baardkr/bimverdi-context')
    parser.add_argument('--name', default='bard', help='Short name of the recipient (used in the package id)')
    args = parser.parse_args()
    output = args.output.expanduser().resolve()
    if output.is_relative_to(SOURCE.parents[2]):
        parser.error('Private bundles must be stored outside the WordPress web root')
    output.mkdir(parents=True, exist_ok=True, mode=0o700)
    output.chmod(0o700)
    now = dt.datetime.now(dt.timezone.utc)
    enrollment_id = args.name + '-' + now.strftime('%Y%m%d') + '-' + secrets.token_hex(4)
    folder = output / enrollment_id
    folder.mkdir(mode=0o700)
    key = folder / '.enrollment_key'
    subprocess.run(['ssh-keygen', '-q', '-t', 'ed25519', '-N', '', '-C', enrollment_id, '-f', str(key)], check=True)
    key.chmod(0o600)
    public_key = ' '.join(key.with_suffix('.pub').read_text().split()[:2])
    remote_dir = ONBOARDING_DIR + '/' + enrollment_id
    manifest = {
        'id': enrollment_id, 'package_version': PACKAGE_VERSION, 'created_at': now.isoformat(timespec='seconds'),
        'remote_helper': remote_dir + '/enroll.py', 'label': label_for(enrollment_id),
    }
    files = {name: (SOURCE / name).read_bytes() for name in BUNDLE_FILES}
    # Windows PowerShell 5.1 needs a BOM to read UTF-8 Norwegian text correctly.
    for name in list(files):
        if name.endswith('.ps1'):
            files[name] = b'\xef\xbb\xbf' + files[name]
    files['.claude/settings.json'] = (SOURCE / 'claude-settings.json').read_bytes()
    files['known_hosts'] = trusted_host_key().encode()
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
                                      + commit + '\nHentet: ' + now.isoformat(timespec='seconds') + '\n').encode()
    bundle = output / ('BIMVerdi-Windows-' + enrollment_id + '.zip')
    with zipfile.ZipFile(bundle, 'w', compression=zipfile.ZIP_DEFLATED) as target:
        for name, content in files.items():
            target.writestr('BIMVerdi-startpakke/' + name, content)
    bundle.chmod(0o600)
    write_admin_state(folder / 'admin-package.json', {
        'id': enrollment_id, 'package_version': PACKAGE_VERSION, 'bundle': str(bundle),
        'created_at': manifest['created_at'], 'bootstrap_public_key': public_key,
        'bootstrap_private_key': str(key), 'remote_dir': remote_dir, 'label': manifest['label'],
        'gate_path': GATE_PATH, 'gate_command': gate_command(enrollment_id), 'activations': [],
    })
    print(json.dumps({'bundle': str(bundle), 'id': enrollment_id, 'folder': str(folder),
                      'next': 'python3 _claude/bard-setup/activate-package.py ' + str(folder)}, indent=2))


if __name__ == '__main__':
    main()
