"""Runs the enrollment helper from Setup-BIMVerdi.ps1 against a fake ssh.exe (needs pwsh on POSIX)."""

import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile
import unittest


PWSH = os.environ.get('BIMVERDI_TEST_PWSH') or shutil.which('pwsh')
ROOT = Path(__file__).resolve().parents[1]


@unittest.skipUnless(PWSH and os.name == 'posix', 'Needs PowerShell and a POSIX process fixture')
class SetupFunctionsTest(unittest.TestCase):
    def run_enrollment(self, response, exit_code, request):
        source = (ROOT / 'Setup-BIMVerdi.ps1').read_text()
        match = re.search(r'^function Invoke-Enrollment.*?^}\n', source, re.S | re.M)
        self.assertIsNotNone(match)
        with tempfile.TemporaryDirectory(prefix='bimverdi setup ') as folder:
            root = Path(folder)
            executable = root / 'System32' / 'OpenSSH' / 'ssh.exe'
            executable.parent.mkdir(parents=True)
            executable.write_text(
                '#!/usr/bin/env python3\n'
                'import os,sys\n'
                'sys.stdin.read()\n'
                'sys.stdout.write(os.environ["FAKE_RESPONSE"] + "\\n")\n'
                'sys.exit(int(os.environ["FAKE_EXIT"]))\n')
            executable.chmod(0o700)
            (root / 'ssh_config').write_text('')
            script = (
                ". '" + str(ROOT / 'Invoke-Ssh.ps1') + "'\n"
                "$workspace = '" + str(root) + "'\n"
                + match.group(0) +
                "try { $r = Invoke-Enrollment " + request + "; Write-Output ('ID:' + $r.id) } "
                "catch { Write-Output ('ERR:' + $_.Exception.Message) }\n")
            path = root / 'test.ps1'
            path.write_text(script)
            result = subprocess.run([PWSH, '-NoProfile', '-File', str(path)], text=True, capture_output=True,
                                    env={**os.environ, 'WINDIR': str(root), 'FAKE_RESPONSE': response, 'FAKE_EXIT': str(exit_code)})
            self.assertEqual(result.returncode, 0, result.stderr)
            return result.stdout.strip().splitlines()[-1]

    def test_ping_answer_is_captured(self):
        line = self.run_enrollment('{"ok":true,"action":"ping","id":"pkg-1","expires_at":"2026-11-04T10:00:00+00:00","expired":false}', 0,
                                   "@{ action = 'ping' }")
        self.assertEqual(line, 'ID:pkg-1')

    def test_server_error_is_reported(self):
        line = self.run_enrollment('{"ok":false,"error":"Wrong one-time code (4 attempts left)"}', 1,
                                   "@{ action = 'enroll'; public_key = 'ssh-ed25519 AAAA'; code = '000000'; accepted_responsibility = $true }")
        self.assertIn('Wrong one-time code', line)

    def test_empty_answer_is_reported(self):
        line = self.run_enrollment('', 255, "@{ action = 'ping' }")
        self.assertIn('svarte ikke', line)


if __name__ == '__main__':
    unittest.main()
