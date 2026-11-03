import os
from pathlib import Path
import shutil
import subprocess
import unittest


PWSH = os.environ.get('BIMVERDI_TEST_PWSH') or shutil.which('pwsh')
ROOT = Path(__file__).resolve().parents[1]


@unittest.skipUnless(PWSH, 'Needs PowerShell')
class PowerShellSyntaxTest(unittest.TestCase):
    def test_scripts_parse_without_errors(self):
        for name in ('Setup-BIMVerdi.ps1', 'BIMVerdi.ps1', 'Invoke-Ssh.ps1'):
            command = (
                "$errors = $null; [System.Management.Automation.Language.Parser]::ParseFile('"
                + str(ROOT / name).replace("'", "''") + "', [ref]$null, [ref]$errors) | Out-Null; "
                "if ($errors) { $errors | ForEach-Object { Write-Output $_.Message }; exit 1 }")
            result = subprocess.run([PWSH, '-NoProfile', '-Command', command], text=True, capture_output=True)
            self.assertEqual(result.returncode, 0, name + ': ' + result.stdout + result.stderr)


if __name__ == '__main__':
    unittest.main()
