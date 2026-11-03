import base64
import datetime as dt
import json
import os
from pathlib import Path
import shutil
import struct
import subprocess
import tempfile
import unittest


SOURCE = Path(__file__).resolve().parents[1] / 'server-enroll.py'


def public_key(value):
    wire = struct.pack('>I', 11) + b'ssh-ed25519' + struct.pack('>I', 32) + bytes([value]) * 32
    return 'ssh-ed25519 ' + base64.b64encode(wire).decode()


class EnrollmentTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.home = Path(self.temp.name)
        self.state = self.home / '.bimverdi-onboarding' / 'test'
        self.state.mkdir(parents=True)
        shutil.copyfile(SOURCE, self.state / 'enroll.py')
        self.auth = self.home / '.ssh' / 'authorized_keys'
        self.auth.parent.mkdir()
        self.existing = 'restrict ' + public_key(1) + ' existing-user\n'
        self.bootstrap = public_key(2)
        self.auth.write_text(self.existing + 'restrict,command="enroll" ' + self.bootstrap + ' bootstrap\n')
        self.manifest = {
            'id': 'test',
            'expires_at': (dt.datetime.now(dt.timezone.utc) + dt.timedelta(hours=1)).isoformat(),
            'bootstrap_key': self.bootstrap,
        }
        self.save()

    def save(self):
        (self.state / 'enrollment.json').write_text(json.dumps(self.manifest))

    def process(self, request):
        return subprocess.Popen(
            [os.sys.executable, str(self.state / 'enroll.py')],
            stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE,
            text=True, env={**os.environ, 'HOME': str(self.home)},
        )

    def call(self, action='enroll', key=None):
        process = self.process(None)
        out, err = process.communicate(json.dumps({'action': action, 'public_key': key or public_key(3)}))
        return process.returncode, out, err

    def test_register_retry_and_finalize_preserve_other_access(self):
        self.assertEqual(self.call()[0], 0)
        self.assertEqual(self.call()[0], 0)
        self.assertEqual(self.auth.read_text().count(public_key(3)), 1)
        self.assertEqual(self.call('finalize')[0], 0)
        self.assertNotIn(self.bootstrap, self.auth.read_text())
        self.assertIn(self.existing, self.auth.read_text())
        self.assertIn(public_key(3), self.auth.read_text())
        self.assertEqual(self.call('finalize')[0], 0)
        self.assertEqual(self.auth.stat().st_mode & 0o777, 0o600)

    def test_first_key_binds_package(self):
        self.assertEqual(self.call()[0], 0)
        self.assertNotEqual(self.call(key=public_key(4))[0], 0)
        self.assertNotIn(public_key(4), self.auth.read_text())

    def test_expired_package_cannot_add_access(self):
        self.manifest['expires_at'] = '2000-01-01T00:00:00+00:00'
        self.save()
        before = self.auth.read_text()
        self.assertNotEqual(self.call()[0], 0)
        self.assertEqual(self.auth.read_text(), before)

    def test_key_options_and_newlines_are_rejected(self):
        before = self.auth.read_text()
        for key in ['command="sh" ' + public_key(3), public_key(3) + '\n' + public_key(4), 'ssh-ed25519 YWJj']:
            self.assertNotEqual(self.call(key=key)[0], 0)
        self.assertEqual(self.auth.read_text(), before)

    def test_finalize_requires_registration(self):
        before = self.auth.read_text()
        self.assertNotEqual(self.call('finalize')[0], 0)
        self.assertEqual(self.auth.read_text(), before)

    def test_concurrent_registration_accepts_only_one_identity(self):
        requests = [{'action': 'enroll', 'public_key': public_key(value)} for value in (3, 4)]
        processes = [self.process(request) for request in requests]
        for process, request in zip(processes, requests):
            process.stdin.write(json.dumps(request))
            process.stdin.close()
            process.stdin = None
        for process in processes:
            process.communicate()
        self.assertEqual(sorted(process.returncode for process in processes), [0, 1])
        content = self.auth.read_text()
        self.assertEqual(sum(public_key(value) in content for value in (3, 4)), 1)
        self.assertIn(self.existing, content)


if __name__ == '__main__':
    unittest.main()
