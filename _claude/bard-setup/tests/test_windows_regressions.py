"""Regressions found on Bård's Windows PC on 05.11.2026. They cannot run on Windows here, so they check the scripts."""

import os
from pathlib import Path
import re
import shutil
import subprocess
import unittest


PWSH = os.environ.get('BIMVERDI_TEST_PWSH') or shutil.which('pwsh')
ROOT = Path(__file__).resolve().parents[1]


class WindowsRegressionTest(unittest.TestCase):
    def test_private_files_are_locked_with_icacls_not_set_acl(self):
        # Set-Acl also writes the audit section and fails for normal users (SeSecurityPrivilege).
        source = (ROOT / 'Setup-BIMVerdi.ps1').read_text()
        self.assertIsNone(re.search(r'^\s*Set-Acl\b', source, re.M))
        self.assertIn('icacls.exe', source)

    def test_launcher_refuses_to_run_from_inside_the_zip(self):
        source = (ROOT / 'Start-BIMVerdi.cmd').read_text()
        self.assertIn('if not exist "%~dp0Setup-BIMVerdi.ps1"', source)

    @unittest.skipUnless(PWSH, 'Needs PowerShell')
    def test_public_key_comment_is_removed(self):
        # Windows ssh-keygen -y prints "ssh-ed25519 <blob> <comment>"; enroll.py accepts only type and blob.
        source = (ROOT / 'Setup-BIMVerdi.ps1').read_text()
        expression = "-split '\\s+')[0..1] -join ' '"
        self.assertIn(expression, source)
        command = "$k = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAA bimverdi-bard'; Write-Output ((($k) " + expression + ')'
        result = subprocess.run([PWSH, '-NoProfile', '-Command', command], text=True, capture_output=True)
        self.assertEqual(result.stdout.strip(), 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAA', result.stderr)


if __name__ == '__main__':
    unittest.main()
