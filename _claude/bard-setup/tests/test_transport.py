import base64
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest


PWSH = os.environ.get('BIMVERDI_TEST_PWSH') or shutil.which('pwsh')
HELPER = Path(__file__).resolve().parents[1] / 'Invoke-Ssh.ps1'


@unittest.skipUnless(PWSH and os.name == 'posix', 'Needs PowerShell and a POSIX process fixture')
class TransportTest(unittest.TestCase):
    def test_exact_utf8_input_arguments_and_exit_code(self):
        with tempfile.TemporaryDirectory(prefix='bimverdi transport ') as folder:
            root = Path(folder)
            executable = root / 'System32' / 'OpenSSH' / 'ssh.exe'
            executable.parent.mkdir(parents=True)
            executable.write_text(
                '#!/usr/bin/env python3\n'
                'import base64,json,sys\n'
                'print(json.dumps({"input":base64.b64encode(sys.stdin.buffer.read()).decode(),"args":sys.argv[1:]}))\n'
                'sys.stderr.write("transport-test-stderr\\n")\n'
                'sys.exit(7)\n'
            )
            executable.chmod(0o700)
            command = (
                ". '" + str(HELPER).replace("'", "''") + "'; "
                r"$result = Invoke-BIMVerdiSsh -ConfigPath 'C:\Users\Bård Hansen\ssh_config' "
                "-Alias bimverdi-prod -RemoteArguments @('sh','-s') -InputText \"printf 'Bård'`r`n\"; "
                '$result | Write-Output; Write-Output ("EXIT:" + $script:BIMVerdiSshExitCode)'
            )
            result = subprocess.run([PWSH, '-NoProfile', '-Command', command],
                                    text=True, capture_output=True, env={**os.environ, 'WINDIR': str(root)})
            self.assertEqual(result.returncode, 0, result.stderr)
            lines = result.stdout.splitlines()
            received = json.loads(lines[0])
            self.assertEqual(base64.b64decode(received['input']), "printf 'Bård'\n\n".encode())
            self.assertEqual(received['args'], ['-F', r'C:\Users\Bård Hansen\ssh_config', 'bimverdi-prod', 'sh', '-s'])
            self.assertEqual(lines[1], 'EXIT:7')
            self.assertIn('transport-test-stderr', result.stderr)


if __name__ == '__main__':
    unittest.main()
