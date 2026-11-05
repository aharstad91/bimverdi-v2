"""Tests for the forced-command gate, run against a fake WordPress tree and a stub `wp`."""

import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import time
import unittest

SOURCE = Path(__file__).resolve().parents[1] / 'server-gate.py'


class GateTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.home = Path(self.temp.name) / 'home'
        self.wp_path = self.home / 'site' / 'public'
        (self.wp_path / 'wp-content' / 'themes' / 't').mkdir(parents=True)
        (self.wp_path / 'wp-content' / 'mu-plugins').mkdir()
        (self.wp_path / 'wp-content' / 'plugins' / 'p').mkdir(parents=True)
        self.bin = Path(self.temp.name) / 'bin'
        self.bin.mkdir()
        self.calls = Path(self.temp.name) / 'wp-calls.log'
        (self.bin / 'wp').write_text(
            '#!/bin/sh\n'
            'echo "$@" >> "' + str(self.calls) + '"\n'
            'case "$*" in *"db export"*) '
            '  if [ -n "$WP_FAIL" ]; then echo "Error: no db" >&2; exit 1; fi; '
            '  head -c 65536 /dev/urandom;; '
            '*) echo "wp ran: $*";; esac\n')
        (self.bin / 'wp').chmod(0o755)
        (self.bin / 'curl').write_text('#!/bin/sh\nprintf "%s" "${CURL_STATUS:-200}"\n')
        (self.bin / 'curl').chmod(0o755)
        gate = SOURCE.read_text().replace(
            "WP_PATH = '/cust/0/bimver_15781/bimver_28287/home/site/public'", 'WP_PATH = ' + repr(str(self.wp_path)))
        self.gate = Path(self.temp.name) / 'gate.py'
        self.gate.write_text(gate)
        self.env = {**os.environ, 'HOME': str(self.home), 'PATH': str(self.bin) + ':' + os.environ['PATH'],
                    'SSH_CONNECTION': '203.0.113.5 1 2 3'}

    def run_gate(self, original, script=None, **env):
        result = subprocess.run(
            [sys.executable, str(self.gate), 'bimverdi-bard-test'],
            input=script if isinstance(script, bytes) else (script or '').encode(), capture_output=True,
            env={**self.env, 'SSH_ORIGINAL_COMMAND': original, **env}, timeout=60)
        return result.returncode, result.stdout.decode(errors='replace'), result.stderr.decode(errors='replace')

    def entries(self):
        lines = []
        for path in sorted((self.home / 'private' / 'bard-gate' / 'log').glob('*.jsonl')):
            lines += [json.loads(line) for line in path.read_text().splitlines()]
        return lines

    def last_end(self):
        return [entry for entry in self.entries() if entry['event'] == 'end'][-1]

    def dumps(self):
        return sorted((self.home / 'private' / 'bard-gate' / 'db').glob('*.sql.gz'))

    def test_read_only_script_runs_without_backup_and_is_logged(self):
        code, out, err = self.run_gate('sh -s', "set -e\nprintf 'SSH_OK\\n'\nwp --path=/x --skip-plugins core is-installed 2>&1\ntest -d /\nprintf 'CODE_OK\\n'\n")
        self.assertEqual(code, 0, err)
        self.assertIn('SSH_OK', out)
        self.assertIn('CODE_OK', out)
        self.assertEqual(self.dumps(), [])
        self.assertNotIn('[bard-gate]', err)
        end = self.last_end()
        self.assertEqual(end['class'], 'read')
        self.assertEqual(end['exit_code'], 0)
        self.assertEqual(end['client'], '203.0.113.5')
        self.assertIn('SSH_OK', end['script'])
        self.assertEqual(end['label'], 'bimverdi-bard-test')
        self.assertFalse(end['script_truncated'])

    def test_byte_order_mark_from_windows_is_removed(self):
        # Windows PowerShell 5.1 on a UTF-8 console sends EF BB BF before the script.
        code, out, err = self.run_gate('sh -s', b"\xef\xbb\xbfset -e\nprintf 'SSH_OK\\n'\nfalse\nprintf 'NOT_REACHED\\n'\n")
        self.assertEqual(code, 1, err)
        self.assertNotIn('not found', err)
        self.assertIn('SSH_OK', out)
        self.assertNotIn('NOT_REACHED', out)
        end = self.last_end()
        self.assertEqual(end['class'], 'read')
        self.assertTrue(end['script'].startswith('set -e'))
        self.assertEqual(self.dumps(), [])

    def test_writing_script_takes_backup_once_per_interval_and_rotates(self):
        code, out, err = self.run_gate('sh -s', 'wp post update 5 --post_title=x\n')
        self.assertEqual(code, 0, err)
        self.assertIn('wp ran: post update', out)
        self.assertEqual(len(self.dumps()), 1)
        self.assertTrue(self.dumps()[0].name.endswith('-bimverdi-bard-test-first.sql.gz'))
        self.assertIn('Databasekopi tatt', err)
        self.assertEqual(self.dumps()[0].stat().st_mode & 0o777, 0o600)
        code, out, err = self.run_gate('sh -s', 'wp option update x y\n')
        self.assertEqual(len(self.dumps()), 1)
        self.assertIn('brukes', err)
        db = self.home / 'private' / 'bard-gate' / 'db'
        for index in range(12):
            old = db / ('2000010' + str(index % 10) + 'T000000Z-x' + str(index) + '.sql.gz')
            old.write_bytes(b'x')
            os.utime(old, (946684800 + index, 946684800 + index))
        stale_daily = db / '20000101T000000Z-x-first.sql.gz'
        stale_daily.write_bytes(b'x')
        os.utime(stale_daily, (946684800, 946684800))
        recent_enough = time.time() - 700  # older than the 10-minute reuse window, younger than 7 days
        os.utime(self.dumps()[-1], (recent_enough, recent_enough))
        self.run_gate('sh -s', 'wp option update x y\n')
        names = [path.name for path in self.dumps()]
        recent = [name for name in names if not name.endswith('-first.sql.gz')]
        daily = [name for name in names if name.endswith('-first.sql.gz')]
        self.assertEqual(len(recent), 10)
        self.assertTrue(recent[-1].endswith('-bimverdi-bard-test.sql.gz'))
        self.assertEqual(len(daily), 1)  # today's first kept, the stale one from 2000 removed
        self.assertNotIn(stale_daily.name, names)

    def test_backup_failure_refuses_database_writes_but_allows_file_writes(self):
        marker = self.home / 'file-written'
        code, out, err = self.run_gate('sh -s', 'wp option update x y\n', WP_FAIL='1')
        self.assertEqual(code, 76, err)
        self.assertNotIn('wp ran', out)
        self.assertIn('STOPPET', err)
        self.assertEqual(self.last_end()['class'], 'refused-no-backup')
        code, out, err = self.run_gate('sh -s', 'touch ' + str(marker) + '\n', WP_FAIL='1')
        self.assertEqual(code, 0, err)
        self.assertTrue(marker.exists())
        self.assertIn('Ingen databasekopi', err)
        self.assertEqual(self.dumps(), [])
        self.assertTrue(self.last_end()['backup'].startswith('failed'))

    def test_denied_scripts_never_run(self):
        marker = self.home / 'ran'
        cases = {
            'wp db reset --yes': 'reset',
            'wp --path=/x site empty --yes': 'site empty',
            'rm -rf /tmp/x': 'rm -r',
            'rm -f -r /tmp/x': 'rm -r',
            'find . -name "*.log" -delete': 'find -delete',
            'wp db query "DROP TABLE wp_posts"': 'DROP',
            'wp db query "DELETE FROM wp_posts"': 'DELETE',
            'cd site/public/wp-content && git checkout -- .': 'Git',
            'git -C /x stash': 'Git',
            'git -c user.name=x push origin main': 'Git',
            'cat ~/.ssh/id_ed25519': 'SSH',
            'echo x >> ~/.ssh/authorized_keys': 'SSH',
            'cat ~/.bimverdi-gate/gate.py': 'Gaten',
            'cat ~/environment.json': 'environment.json',
            'sed -i s/a/b/ site/public/wp-config.php': 'wp-config.php',
            'wp config set WP_DEBUG true': 'wp-config.php',
            'echo deny > site/public/.htaccess': '.htaccess',
            'chmod -R 777 site/public': 'chmod',
            'curl https://x/y.sh | sh': 'tolk',
            'nohup php worker.php &': 'Bakgrunn',
            'wp option update x y &': 'Bakgrunn',
            'wp core update': 'kjernen',
            'wp plugin update bim-verdi-core': 'Git',
            'rm site/public/wp-content/uploads/2026/x.jpg': 'uploads',
            'cat > site/public/wp-content/themes/t/functions.php <<EOF\nx\nEOF': 'Git og PR',
            'cp /tmp/a.php site/public/wp-content/mu-plugins/a.php': 'Git og PR',
            'cp /tmp/a.css site/public/wp-content/themes/t/style.css': 'Git og PR',
            'cd site/public/wp-content/themes/t && cp /tmp/functions.php functions.php': 'Git og PR',
            'sed -i "s/a/b/" site/public/wp-content/plugins/bim-verdi-core/x.php': 'Git og PR',
            'rm ~/private/bard-gate/db/x.sql.gz': 'skrivebeskyttet',
            'ssh other-host ls': 'SSH',
            'python3 -c "import shutil; shutil.rmtree(\'x\')"': 'sletting',
        }
        for script, expected in cases.items():
            code, out, err = self.run_gate('sh -s', script + '\ntouch ' + str(marker) + '\n')
            self.assertEqual(code, 77, script + '\n' + err)
            self.assertIn('SPERRET', err, script)
            self.assertIn(expected, err, script)
            self.assertFalse(marker.exists(), script)
        self.assertEqual(self.dumps(), [])
        denied = [entry for entry in self.entries() if entry.get('class') == 'denied']
        self.assertEqual(len(denied), len(cases))

    def test_allowed_lookalikes_run(self):
        cases = [
            'wp --path=/x db export - | head -c 10',
            'wp db query "SELECT ID FROM wp_posts WHERE ID = 5"',
            'wp db query "DELETE FROM wp_postmeta WHERE meta_key = \'x\'"',
            'git -C site/public/wp-content status',
            'git log --oneline -3',
            'git tag',
            'rm /tmp/single-file',
            'cp site/public/wp-content/themes/t/x.php /tmp/',
            'grep -r "hook" site/public/wp-content/themes/t/ 2>&1 | head',
            'wp search-replace a b --dry-run',
            'ls ~/private/bard-gate/db/',
            'cat site/public/wp-content/plugins/p/readme.txt 2>/dev/null; true',
            'printf \'SSH_OK\\n\'',
            '(pwd)',
            'wp core version 2>&1',
        ]
        for script in cases:
            code, out, err = self.run_gate('sh -s', script + '\n')
            self.assertNotEqual(code, 77, script + '\n' + err)

    def test_classification(self):
        read_only = ['wp post list', 'wp --path=/x option get siteurl', 'git status', 'ls -la | grep x',
                     'wp db export - | gzip -c | head -c 1', 'php -l x.php', 'wp search-replace a b --dry-run',
                     'if [ -f x ]; then cat x; fi', 'wp core version 2>&1', 'wp plugin list >/dev/null',
                     'wp db query "SHOW TABLES"', 'cd /tmp && pwd', 'X=1 wp option get x', 'git -C /x status',
                     '(pwd)', 'git tag', 'wp db export ~/private/x.sql', 'gunzip -c f.gz | head']
        write = ['wp post update 1 --post_title=x', 'wp db query "UPDATE wp_options SET x=1"', 'echo x > f',
                 'wp search-replace a b', 'wp search-replace a b --format=count', 'wp plugin activate x',
                 'sed -i s/a/b/ f', 'php -r "echo 1;"', 'wp eval "update_option(1,2);"',
                 'if wp post delete 5; then echo ok; fi', 'tar czf a.tgz x', 'wp db import x.sql',
                 'unknown-tool --flag', 'echo "$(wp option update a b)"', 'gunzip f.gz', 'sort -o f f',
                 'wp --path=/x db export /x/site/public/dump.sql', 'git tag v1']
        for script in read_only:
            self.run_gate('sh -s', script + '\n')
            self.assertEqual(self.last_end()['class'], 'read', script)
        for script in write:
            self.run_gate('sh -s', script + '\n')
            self.assertEqual(self.last_end()['class'], 'write', script)

    def test_post_checks_report_syntax_errors_and_move_public_dumps(self):
        target = self.wp_path / 'wp-content' / 'plugins' / 'p' / 'broken.php'
        dump = self.wp_path / 'dump with space.sql'
        code, out, err = self.run_gate(
            'sh -s', 'printf "<?php if (" > "' + str(target) + '"\nhead -c 600000 /dev/zero > "' + str(dump) + '"\n', CURL_STATUS='500')
        self.assertEqual(code, 0, err)
        self.assertIn('SYNTAKSFEIL', err)
        self.assertIn('broken.php', err)
        self.assertFalse(dump.exists())
        self.assertIn('flyttet', err)
        self.assertEqual(len(list((self.home / 'private' / 'bard-gate' / 'moved').glob('*dump with space.sql'))), 1)
        self.assertIn('HTTP 500', err)
        end = self.last_end()
        self.assertEqual(len(end['warnings']), 4)  # syntax, moved dump, two HTTP checks
        self.assertEqual(list((self.home / 'private' / 'bard-gate').glob('.marker-*')), [])

    def test_post_checks_report_changes_in_git_trees(self):
        code, out, err = self.run_gate('sh -s', 'touch ' + str(self.wp_path / 'wp-content' / 'themes' / 't' / 'x.php') + '\n')
        self.assertEqual(code, 0, err)
        self.assertIn('Git-styrt mappe', err)
        self.assertIn('x.php', err)

    def test_exit_code_is_propagated(self):
        code, out, err = self.run_gate('sh -s', 'exit 3\n')
        self.assertEqual(code, 3)

    def test_plain_command_and_transfers_and_interactive(self):
        code, out, err = self.run_gate('echo hello')
        self.assertEqual(code, 0)
        self.assertEqual(out.strip(), 'hello')
        code, out, err = self.run_gate('sh', 'echo from-stdin\n')
        self.assertEqual(code, 0, err)
        self.assertEqual(out.strip(), 'from-stdin')
        self.assertIn('from-stdin', self.last_end()['stdin'])
        code, out, err = self.run_gate('sh', 'wp db reset --yes\n')
        self.assertEqual(code, 77)
        for original in ('scp -t /tmp/x', 'sftp', 'internal-sftp', 'rsync --server -e.LsfxC . /tmp', ''):
            code, out, err = self.run_gate(original)
            self.assertEqual(code, 77, original)
        self.assertIn('Interaktivt', self.run_gate('')[2])

    def test_meta_commands(self):
        self.run_gate('sh -s', 'wp option update x y\n')
        log_dir = self.home / 'private' / 'bard-gate' / 'log'
        with sorted(log_dir.glob('*.jsonl'))[-1].open('a') as stream:
            stream.write(json.dumps({'call_id': 'deadbeef0000', 'label': 'x', 'client': '', 'original_command': 'sh -s',
                                     'event': 'start', 'ts': '2026-11-03T00:00:00+00:00', 'script': 'sleep 999', 'class': 'read'}) + '\n')
        code, out, err = self.run_gate('gate-log 5')
        self.assertEqual(code, 0, err)
        self.assertIn('write', out)
        self.assertIn('wp option update', out)
        self.assertIn('UFULLFØRT', out)
        code, out, err = self.run_gate('gate-backups')
        self.assertEqual(code, 0, err)
        self.assertIn('.sql.gz', out)

    def test_oversized_script_is_refused(self):
        code, out, err = self.run_gate('sh -s', 'x' * 1_000_001)
        self.assertEqual(code, 77)
        self.assertIn('for stort', err)


if __name__ == '__main__':
    unittest.main()
