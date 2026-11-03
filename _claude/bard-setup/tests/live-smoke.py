#!/usr/bin/env python3
"""End-to-end check against Servebolt with a disposable package. Run from wp-content on Andreas's Mac.

  python3 _claude/bard-setup/tests/live-smoke.py

It builds and activates a package named smoke-..., enrols a throwaway client key with the one-time
code, exercises the gate over real SSH, finalizes, revokes, and cleans up. Production data is not
changed: the only writing command sets and deletes a 60-second transient.
"""

import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys
import tempfile
import zipfile

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))
from common import HOST, WP_PATH, ssh_python  # noqa: E402

PY = sys.executable
results = []


def check(name, condition, detail=''):
    results.append((name, bool(condition)))
    print(('PASS ' if condition else 'FAIL ') + name + ('' if condition else '  -> ' + str(detail)[:400]))


def run(args, **kwargs):
    return subprocess.run(args, text=True, capture_output=True, timeout=180, **kwargs)


def main():
    scratch = Path(tempfile.mkdtemp(prefix='bimverdi-smoke-'))
    os.chmod(scratch, 0o700)
    package_id = None
    try:
        built = run([PY, str(ROOT / 'prepare-package.py'), '--output', str(scratch / 'out'), '--name', 'smoke'])
        check('bygg pakke', built.returncode == 0, built.stderr)
        info = json.loads(built.stdout)
        package_id = info['id']
        folder = Path(info['folder'])
        zip_path = Path(info['bundle'])
        with zipfile.ZipFile(zip_path) as archive:
            names = archive.namelist()
        check('ZIP uten kode/utløp', not any(n.endswith('admin-package.json') for n in names)
              and 'code' not in json.dumps(json.loads(zipfile.ZipFile(zip_path).read('BIMVerdi-startpakke/enrollment.json'))),
              names)
        check('ZIP har settings.json', 'BIMVerdi-startpakke/.claude/settings.json' in names)

        activated = run([PY, str(ROOT / 'activate-package.py'), str(folder), '--hours', '1'])
        check('aktiver pakke', activated.returncode == 0, activated.stderr + activated.stdout)
        match = re.search(r'Engangskode:\s+(\d{6})', activated.stdout)
        check('engangskode skrevet ut', bool(match), activated.stdout)
        code = match.group(1) if match else '000000'

        client = scratch / 'client'
        client.mkdir(mode=0o700)
        with zipfile.ZipFile(zip_path) as archive:
            archive.extractall(client)
        package = client / 'BIMVerdi-startpakke'
        (package / '.enrollment_key').chmod(0o600)
        key = client / 'bard_key'
        subprocess.run(['ssh-keygen', '-q', '-t', 'ed25519', '-N', '', '-f', str(key)], check=True)
        public_key = ' '.join(key.with_suffix('.pub').read_text().split()[:2])
        config = scratch / 'ssh_config'
        config.write_text(
            'Host bimverdi-prod\n    IdentityFile ' + str(key) + '\n'
            'Host bimverdi-enroll\n    IdentityFile ' + str(package / '.enrollment_key') + '\n'
            'Host bimverdi-prod bimverdi-enroll\n    HostName jana-osl.servebolt.cloud\n    User bimver_28287\n'
            '    Port 22\n    IdentitiesOnly yes\n    BatchMode yes\n    ConnectTimeout 15\n'
            '    StrictHostKeyChecking yes\n    HostKeyAlgorithms ssh-ed25519\n'
            '    UserKnownHostsFile ' + str(package / 'known_hosts') + '\n    GlobalKnownHostsFile none\n')

        def ssh(alias, command, stdin=''):
            return run(['ssh', '-F', str(config), alias] + command, input=stdin)

        shell = ssh('bimverdi-enroll', ['id'], 'not json')
        check('engangsnøkkel gir ikke shell', shell.returncode != 0 and 'uid=' not in shell.stdout, shell.stdout)
        ping = ssh('bimverdi-enroll', ['ping'], '{"action":"ping"}')
        answer = json.loads(ping.stdout or '{}')
        check('ping via engangsnøkkel', answer.get('ok') and not answer.get('expired') and answer.get('id') == package_id, ping.stdout + ping.stderr)

        wrong = ssh('bimverdi-enroll', ['enroll'], json.dumps({'action': 'enroll', 'public_key': public_key, 'code': '000000', 'accepted_responsibility': True}))
        check('feil kode avvist', wrong.returncode != 0 and 'attempts left' in wrong.stdout, wrong.stdout)
        no_accept = ssh('bimverdi-enroll', ['enroll'], json.dumps({'action': 'enroll', 'public_key': public_key, 'code': code}))
        check('uten ansvar avvist', no_accept.returncode != 0, no_accept.stdout)
        enrolled = ssh('bimverdi-enroll', ['enroll'], json.dumps({'action': 'enroll', 'public_key': public_key, 'code': code, 'accepted_responsibility': True, 'client_name': 'smoke'}))
        answer = json.loads(enrolled.stdout or '{}')
        check('registrering med riktig kode', answer.get('ok') and answer.get('fingerprint', '').startswith('SHA256:'), enrolled.stdout + enrolled.stderr)

        status_script = ("set -e\nprintf 'SSH_OK\\n'\nwp --path=" + WP_PATH + " --skip-plugins --skip-themes core is-installed\n"
                         "printf 'DATABASE_OK\\n'\ntest -r " + WP_PATH + "/wp-content/themes/bimverdi-theme/functions.php\nprintf 'CODE_OK\\n'\n")
        status = ssh('bimverdi-prod', ['sh', '-s'], status_script)
        check('Status via gate (lesende, ingen kopi)', status.returncode == 0 and 'CODE_OK' in status.stdout and '[bard-gate]' not in status.stderr, status.stdout + status.stderr)

        denied = ssh('bimverdi-prod', ['sh', '-s'], 'wp --path=' + WP_PATH + ' db reset --yes\n')
        check('wp db reset sperret', denied.returncode == 77 and 'SPERRET' in denied.stderr, denied.stderr)
        denied = ssh('bimverdi-prod', ['sh', '-s'], 'cd ' + WP_PATH + '/wp-content && git checkout -- .\n')
        check('git checkout sperret', denied.returncode == 77, denied.stderr)
        denied = ssh('bimverdi-prod', ['sh', '-s'], 'echo x > ' + WP_PATH + '/wp-content/mu-plugins/smoke.php\n')
        check('PHP rett i mu-plugins sperret', denied.returncode == 77, denied.stderr)
        scp = run(['scp', '-O', '-F', str(config), str(config), 'bimverdi-prod:/tmp/smoke'])
        check('scp sperret', scp.returncode != 0, scp.stdout + scp.stderr)
        interactive = run(['ssh', '-T', '-F', str(config), 'bimverdi-prod'], input='')
        check('interaktivt skall sperret', interactive.returncode == 77, interactive.stderr)
        plain = ssh('bimverdi-prod', ['echo', 'hei'])
        check('vanlig kommando via sh -c', plain.returncode == 0 and plain.stdout.strip() == 'hei', plain.stdout + plain.stderr)

        write = ssh('bimverdi-prod', ['sh', '-s'], 'wp --path=' + WP_PATH + ' --skip-plugins --skip-themes transient set bard_gate_smoke 1 60\n')
        check('skrivende kommando tar databasekopi', write.returncode == 0 and 'Databasekopi tatt' in write.stderr and 'ADVARSEL' not in write.stderr, write.stdout + write.stderr)
        again = ssh('bimverdi-prod', ['sh', '-s'], 'wp --path=' + WP_PATH + ' --skip-plugins --skip-themes transient delete bard_gate_smoke\n')
        check('kopi gjenbrukes innen 10 min', again.returncode == 0 and 'brukes' in again.stderr, again.stderr)
        log = ssh('bimverdi-prod', ['gate-log', '5'])
        check('gate-log', log.returncode == 0 and 'transient' in log.stdout, log.stdout + log.stderr)
        backups = ssh('bimverdi-prod', ['gate-backups'])
        check('gate-backups viser kopi', package_id in backups.stdout, backups.stdout + backups.stderr)

        helper = json.loads((package / 'enrollment.json').read_text())['remote_helper']
        final = ssh('bimverdi-prod', ['/usr/bin/python3', helper], json.dumps({'action': 'finalize', 'public_key': public_key}))
        check('enroll.py via permanent nøkkel sperres av gaten', final.returncode == 77, final.stdout + final.stderr)
        sftp = run(['scp', '-F', str(config), str(config), 'bimverdi-prod:/tmp/smoke'])
        check('scp i sftp-modus sperret', sftp.returncode != 0, sftp.stdout + sftp.stderr)
        final = ssh('bimverdi-enroll', ['finalize'], json.dumps({'action': 'finalize', 'public_key': public_key}))
        check('finalize via engangsnøkkel', final.returncode == 0 and json.loads(final.stdout).get('ok'), final.stdout + final.stderr)
        dead = ssh('bimverdi-enroll', ['ping'], '{"action":"ping"}')
        check('engangsnøkkel død etter finalize', dead.returncode != 0, dead.stdout)
        still = ssh('bimverdi-prod', ['echo', 'hei'])
        check('permanent nøkkel virker etter finalize', still.returncode == 0, still.stderr)

        revoked = run([PY, str(ROOT / 'revoke-package.py'), '--id', package_id])
        check('revoke', revoked.returncode == 0 and '"removed_lines": 1' in revoked.stdout, revoked.stdout + revoked.stderr)
        gone = ssh('bimverdi-prod', ['echo', 'hei'])
        check('permanent nøkkel død etter revoke', gone.returncode != 0, gone.stdout)
    finally:
        if package_id:
            cleanup = ssh_python(
                'import shutil\nfrom pathlib import Path\n'
                'for p in (Path.home()/"private/bard-gate/db").glob("*' + package_id + '*"): p.unlink()\n'
                'd = Path.home()/".bimverdi-onboarding/' + package_id + '"\n'
                'shutil.rmtree(d, ignore_errors=True)\n'
                'auth = Path.home()/".ssh/authorized_keys"\n'
                'print("rest:", sum(1 for l in auth.read_text().splitlines() if "' + package_id + '" in l) if auth.exists() else 0)\n')
            check('opprydding på server', 'rest: 0' in cleanup, cleanup)
        shutil.rmtree(scratch, ignore_errors=True)
    failed = [name for name, ok in results if not ok]
    print()
    print(str(len(results) - len(failed)) + ' av ' + str(len(results)) + ' sjekker besto.')
    sys.exit(1 if failed else 0)


if __name__ == '__main__':
    main()
