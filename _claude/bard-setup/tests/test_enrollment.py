import base64
import datetime as dt
import hashlib
import json
import os
from pathlib import Path
import shutil
import struct
import subprocess
import tempfile
import unittest


SOURCE = Path(__file__).resolve().parents[1] / 'server-enroll.py'
CODE = '123456'
SALT = 'salt'


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
        self.auth.write_text(self.existing + 'restrict,command="enroll" ' + self.bootstrap + ' test\n')
        self.manifest = {
            'id': 'test', 'label': 'bimverdi-bard-test',
            'expires_at': (dt.datetime.now(dt.timezone.utc) + dt.timedelta(hours=1)).isoformat(),
            'bootstrap_key': self.bootstrap, 'gate_command': '/usr/bin/python3 /home/x/.bimverdi-gate/gate.py bimverdi-bard-test',
            'code_salt': SALT, 'code_hash': hashlib.sha256((SALT + CODE).encode()).hexdigest(), 'attempts': 0,
        }
        self.save()

    def save(self):
        (self.state / 'enrollment.json').write_text(json.dumps(self.manifest))

    def load(self):
        return json.loads((self.state / 'enrollment.json').read_text())

    def process(self):
        return subprocess.Popen(
            [os.sys.executable, str(self.state / 'enroll.py')],
            stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE,
            text=True, env={**os.environ, 'HOME': str(self.home)},
        )

    def call(self, action='enroll', key=None, code=CODE, raw=None, **extra):
        process = self.process()
        request = raw if raw is not None else json.dumps({
            'action': action, 'public_key': key or public_key(3), 'code': code,
            'accepted_responsibility': True, 'client_name': 'PC\\bard', **extra})
        out, err = process.communicate(request)
        return process.returncode, out, err

    def test_register_retry_and_finalize_preserve_other_access(self):
        code, out, _ = self.call()
        self.assertEqual(code, 0, out)
        self.assertEqual(self.call()[0], 0)
        content = self.auth.read_text()
        self.assertEqual(content.count(public_key(3)), 1)
        self.assertIn('restrict,command="' + self.manifest['gate_command'] + '" ' + public_key(3) + ' bimverdi-bard-test\n', content)
        self.assertEqual(self.call('finalize')[0], 0)
        self.assertNotIn(self.bootstrap, self.auth.read_text())
        self.assertIn(self.existing, self.auth.read_text())
        self.assertIn(public_key(3), self.auth.read_text())
        self.assertEqual(self.call('finalize')[0], 0)
        self.assertEqual(self.auth.stat().st_mode & 0o777, 0o600)
        state = self.load()
        self.assertTrue(state['finalized'])
        self.assertEqual(state['client_name'], 'PC\\bard')
        self.assertIn('accepted_responsibility_at', state)
        self.assertIn('enrolled', (self.state / 'events.log').read_text())

    def test_ping_needs_no_code_and_reports_state(self):
        code, out, _ = self.call(raw='{"action":"ping"}')
        self.assertEqual(code, 0, out)
        answer = json.loads(out)
        self.assertEqual(answer['id'], 'test')
        self.assertFalse(answer['expired'])
        self.assertFalse(answer['registered'])

    def test_wrong_code_counts_and_burns_bootstrap_after_five(self):
        before = self.auth.read_text()
        for attempt in range(1, 5):
            code, out, _ = self.call(code='000000')
            self.assertNotEqual(code, 0)
            self.assertIn(str(5 - attempt) + ' attempts left', out)
            self.assertEqual(self.auth.read_text(), before)
        self.assertNotEqual(self.call(code='000000')[0], 0)
        self.assertNotIn(self.bootstrap, self.auth.read_text())
        self.assertIn(self.existing, self.auth.read_text())
        self.assertNotEqual(self.call()[0], 0)  # even the right code is refused now
        self.assertNotIn(public_key(3), self.auth.read_text())

    def test_responsibility_must_be_accepted(self):
        process = self.process()
        out, _ = process.communicate(json.dumps({'action': 'enroll', 'public_key': public_key(3), 'code': CODE}))
        self.assertNotEqual(process.returncode, 0)
        self.assertNotIn(public_key(3), self.auth.read_text())

    def test_first_key_binds_package_and_retry_needs_no_code(self):
        self.assertEqual(self.call()[0], 0)
        self.assertNotEqual(self.call(key=public_key(4))[0], 0)
        self.assertNotIn(public_key(4), self.auth.read_text())
        self.assertEqual(self.call(code='wrong')[0], 0)

    def test_reused_key_from_older_package_gets_this_gate(self):
        self.auth.write_text(self.auth.read_text() + 'restrict ' + public_key(3) + ' bimverdi-bard-old\n')
        self.assertEqual(self.call()[0], 0)
        lines = [line for line in self.auth.read_text().splitlines() if public_key(3) in line]
        self.assertEqual(len(lines), 1)
        self.assertIn('gate.py bimverdi-bard-test', lines[0])

    def test_expired_package_cannot_add_access_but_can_finalize(self):
        self.assertEqual(self.call()[0], 0)
        self.manifest = self.load()
        self.manifest['expires_at'] = '2000-01-01T00:00:00+00:00'
        self.save()
        self.assertEqual(self.call('finalize')[0], 0)
        self.assertNotIn(self.bootstrap, self.auth.read_text())
        self.manifest = self.load()
        self.manifest.pop('registered_key')
        self.manifest.pop('finalized')
        self.save()
        before = self.auth.read_text()
        self.assertNotEqual(self.call(key=public_key(4))[0], 0)
        self.assertEqual(self.auth.read_text(), before)

    def test_revoked_package_is_dead(self):
        self.manifest['revoked'] = True
        self.save()
        before = self.auth.read_text()
        self.assertNotEqual(self.call()[0], 0)
        self.assertEqual(self.auth.read_text(), before)

    def test_bad_input_is_rejected_cleanly(self):
        before = self.auth.read_text()
        for key in ['command="sh" ' + public_key(3), public_key(3) + '\n' + public_key(4), 'ssh-ed25519 YWJj']:
            self.assertNotEqual(self.call(key=key)[0], 0)
        for raw in ['[1,2]', '"x"', 'null', '{"action":"enroll"}', '{"action":"nope"}', '{']:
            code, out, err = self.call(raw=raw)
            self.assertNotEqual(code, 0, raw)
            self.assertEqual(err, '', raw)
            self.assertFalse(json.loads(out)['ok'], raw)
        self.assertEqual(self.auth.read_text(), before)

    def test_finalize_requires_registration(self):
        before = self.auth.read_text()
        self.assertNotEqual(self.call('finalize')[0], 0)
        self.assertEqual(self.auth.read_text(), before)

    def test_concurrent_registration_accepts_only_one_identity(self):
        requests = [{'action': 'enroll', 'public_key': public_key(value), 'code': CODE,
                     'accepted_responsibility': True} for value in (3, 4)]
        processes = [self.process() for _ in requests]
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
