#!/usr/bin/env python3
"""Forced command for Bård's permanent SSH key (installed as ~/.bimverdi-gate/gate.py <label>).

The holder of the key works on production on their own responsibility. The gate does not try
to make that safe; it makes it visible and recoverable:

- Every call is logged with the full script (truncated at 64 KB) and its result.
- A short list of never-legitimate operations is refused (destroying the database, deleting
  trees, rewriting the production git tree, touching keys/config, uploading files).
- Before any script that may write, a compressed database dump is taken (at most once per
  10 minutes, the newest 10 are kept).
- After a writing script, new PHP files are syntax-checked, SQL dumps left in the web root are
  moved out of it, and the login page is fetched to detect a broken site.

The gate runs as the same Linux user as the key holder and can be edited by them. It is a
guard rail against accidents, not a security boundary.
"""

import datetime as dt
import fcntl
import hashlib
import json
import os
from pathlib import Path
import re
import shlex
import shutil
import subprocess
import sys
import time
import uuid

WP_PATH = '/cust/0/bimver_15781/bimver_28287/home/site/public'
SITE_CHECK_URL = 'https://bimverdi.no/wp-login.php'
HOME = Path.home()
BASE = HOME / 'private' / 'bard-gate'
LOG_DIR = BASE / 'log'
DB_DIR = BASE / 'db'
MOVED_DIR = BASE / 'moved'
MAX_SCRIPT_BYTES = 1_000_000
LOG_BODY_LIMIT = 65_536
KEEP_DUMPS = 10
DUMP_INTERVAL_SECONDS = 600
DENIED_EXIT = 77

# --- Refusal rules -------------------------------------------------------------------------
# Each rule: (regex on the whole script, message). Matching is case-insensitive.
DENY_RULES = [
    (r'\bwp\b[^\n;|&]*\bdb\s+(reset|drop|clean)\b',
     'wp db reset/drop/clean sletter hele databasen.'),
    (r'\bwp\b[^\n;|&]*\bsite\s+(empty|delete)\b',
     'wp site empty/delete sletter alt innhold.'),
    (r'\bwp\b[^\n;|&]*\bcore\s+(update|download|install|update-db|multisite-\w+)\b',
     'Oppdatering av WordPress-kjernen gjør Andreas.'),
    (r'\bwp\b[^\n;|&]*\bconfig\s+(set|create|delete|shuffle-salts|edit)\b',
     'wp-config.php endres bare av Andreas. Bruk wp config get/list for å lese.'),
    (r'\bwp\b[^\n;|&]*\b(plugin|theme)\s+delete\b[^\n;|&]*\b(bim-verdi-core|bimverdi-theme)\b',
     'bim-verdi-core og bimverdi-theme ligger i Git. Slett dem ikke på serveren.'),
    (r'\b(drop\s+(database|schema|table)|truncate\s+(table\s+)?\w)',
     'DROP og TRUNCATE i SQL er sperret.'),
    (r'\bdelete\s+from\s+[`\w.]+\s*(;|$|\'|")',
     'DELETE uten WHERE er sperret.'),
    (r'\brm\b[^\n;|&]*(\s-[a-z]*r[a-z]*\b|\s--recursive\b)',
     'Rekursiv sletting (rm -r) er sperret. Slett enkeltfiler, eller be Andreas.'),
    (r'\brm\b[^\n;|&]*\buploads\b',
     'Sletting i uploads/ er sperret. Opplastede filer finnes ikke i Git.'),
    (r'\bfind\b[^\n;|&]*(-delete|-exec\s+rm)\b',
     'find -delete / -exec rm er sperret.'),
    (r'\b(rmtree|shred|mkfs)\b|\bdd\s+if=|\btruncate\s+-s',
     'Masse-sletting og lavnivå diskverktøy er sperret.'),
    (r'\bgit\b(\s+(?:-C|-c|--git-dir|--work-tree)\s+\S+|\s+-\S+)*\s+(reset|checkout|restore|clean|stash|push|pull|fetch|merge|rebase|'
     r'commit|add|rm|mv|switch|cherry-pick|revert|am|apply|init|gc|prune|filter-branch|'
     r'update-ref|symbolic-ref|tag|remote\s+(add|remove|rm|set-url)|branch\s+-[dDMm])\b',
     'Git på serveren er skrivebeskyttet. Bruk git status/log/diff/show. Kode endres via GitHub og PR.'),
    (r'(^|[\s/\'"])\.git/',
     'Mappen .git/ på serveren skal ikke røres.'),
    (r'(^|[\s/\'"=~])\.ssh\b|\bauthorized_keys\b|\bssh-keygen\b|\bssh-add\b|(^|[\s;|&])ssh\s',
     'SSH-nøkler og utgående SSH fra serveren er sperret.'),
    (r'\.bimverdi-gate|\.bimverdi-onboarding|bard-gate/log',
     'Gaten og loggen er skrivebeskyttet. Bruk -Action Log for å lese loggen.'),
    (r'\benvironment\.(json|ini)\b',
     'Servebolt-nøkkelen i environment.json er utenfor oppdraget.'),
    (r'\bwp-config\.php\b',
     'wp-config.php endres bare av Andreas. Bruk wp config get/list for å lese.'),
    (r'(^|[\s/\'"])\.htaccess\b',
     '.htaccess endres via Git (wp-content) eller av Andreas (rot).'),
    (r'\bchmod\s+(-R|--recursive|[0-7]*(777|666))\b|\bchown\b',
     'Rekursiv chmod, 777/666 og chown er sperret.'),
    (r'\b(pkill|killall)\b|\bkill\s+-9\s+-1\b|\bcrontab\b',
     'Prosessdrap og crontab er sperret.'),
    (r'\b(curl|wget)\b[^\n]*\|\s*(sh|bash|dash|python3?|php|perl)\b',
     'Nedlasting rett inn i en tolk er sperret.'),
    (r'\b(nohup|setsid|disown|screen|tmux)\b',
     'Bakgrunnsprosesser som overlever økten er sperret.'),
    (r'\b(sudo|su|doas)\b',
     'Det finnes ingen root-tilgang her.'),
]

# Writing code straight into the Git-managed trees bypasses lint and is lost on next deploy.
GIT_TREES = r'(themes|mu-plugins|plugins/bim-verdi-core)/'
CODE_WRITE_RULES = [
    r'(>>?|\btee\b|\bsed\s+-i\b|\bperl\s+-i\b|\bpatch\b|\bunzip\b|\btar\s+-?x)[^\n;|&]*' + GIT_TREES,
]
CODE_WRITE_MESSAGE = ('Kode i themes/, mu-plugins/ og plugins/bim-verdi-core/ endres via Git og PR, '
                      'ikke rett på serveren. Endringer der forsvinner ved neste publisering.')

# --- Read-only classification ---------------------------------------------------------------
READ_ONLY_COMMANDS = {
    'ls', 'cat', 'head', 'tail', 'less', 'more', 'grep', 'egrep', 'fgrep', 'rg', 'wc', 'pwd',
    'echo', 'printf', 'true', 'false', 'test', '[', 'du', 'df', 'stat', 'file', 'date', 'whoami',
    'id', 'env', 'printenv', 'which', 'type', 'uname', 'hostname', 'sort', 'uniq', 'cut', 'tr',
    'awk', 'diff', 'cmp', 'md5sum', 'sha256sum', 'sha1sum', 'base64', 'zcat', 'gunzip', 'readlink',
    'realpath', 'basename', 'dirname', 'set', 'export', 'cd', ':', 'sleep', 'jq', 'column', 'nl',
    'tac', 'rev', 'xxd', 'od', 'strings', 'tree', 'lsof', 'ps', 'uptime', 'free', 'nproc', 'exit',
    'return', 'local', 'read', 'seq', 'expr', 'let', 'shift', 'fi', 'for', 'done', 'case', 'esac',
    'in', 'break', 'continue',
}
GIT_READ_SUBCOMMANDS = {'status', 'log', 'diff', 'show', 'blame', 'ls-files', 'rev-parse', 'branch',
                        'remote', 'describe', 'ls-tree', 'cat-file', 'shortlog', 'config', 'grep', 'tag'}
WP_READ_SUBCOMMANDS = {
    ('core', 'is-installed'), ('core', 'version'), ('core', 'check-update'), ('core', 'verify-checksums'),
    ('config', 'get'), ('config', 'list'), ('config', 'path'), ('config', 'has'), ('config', 'is-true'),
    ('db', 'export'), ('db', 'size'), ('db', 'tables'), ('db', 'columns'), ('db', 'check'), ('db', 'prefix'),
    ('option', 'get'), ('option', 'list'), ('option', 'pluck'),
    ('post', 'list'), ('post', 'get'), ('post', 'exists'), ('post', 'meta', 'list'), ('post', 'meta', 'get'),
    ('post', 'term', 'list'), ('post', 'url'),
    ('user', 'list'), ('user', 'get'), ('user', 'exists'), ('user', 'meta', 'list'), ('user', 'meta', 'get'),
    ('user', 'list-caps'),
    ('plugin', 'list'), ('plugin', 'get'), ('plugin', 'status'), ('plugin', 'is-installed'),
    ('plugin', 'is-active'), ('plugin', 'path'), ('plugin', 'verify-checksums'), ('plugin', 'search'),
    ('theme', 'list'), ('theme', 'get'), ('theme', 'status'), ('theme', 'is-installed'),
    ('theme', 'is-active'), ('theme', 'path'), ('theme', 'mod', 'list'), ('theme', 'mod', 'get'),
    ('term', 'list'), ('term', 'get'), ('term', 'meta', 'list'), ('term', 'meta', 'get'),
    ('menu', 'list'), ('menu', 'item', 'list'), ('menu', 'location', 'list'),
    ('cron', 'event', 'list'), ('cron', 'schedule', 'list'), ('cron', 'test'),
    ('rewrite', 'list'), ('role', 'list'), ('cap', 'list'), ('taxonomy', 'list'), ('taxonomy', 'get'),
    ('post-type', 'list'), ('post-type', 'get'), ('media', 'list'), ('comment', 'list'), ('comment', 'get'),
    ('comment', 'count'), ('transient', 'get'), ('transient', 'list'), ('transient', 'type'),
    ('site', 'list'), ('sidebar', 'list'), ('widget', 'list'), ('language', 'core', 'list'),
    ('cli', 'version'), ('cli', 'info'), ('cli', 'has-command'), ('help',), ('search-replace',),
    ('acf',), ('gf',),
}
WP_READ_ONLY_FLAGS = ('--dry-run', '--help', '--format=count')


def wp_is_read_only(tokens):
    """tokens start after 'wp'. Global options (--path=...) come first."""
    rest = [token for token in tokens if not token.startswith('-')]
    flags = [token for token in tokens if token.startswith('-')]
    if any(flag in WP_READ_ONLY_FLAGS for flag in flags) or not rest:
        return True
    if rest[:2] == ['db', 'query'] and len(rest) > 2:
        return bool(re.match(r'^\s*(select|show|describe|desc|explain)\b', rest[2], re.IGNORECASE))
    for length in (3, 2, 1):
        if tuple(rest[:length]) in WP_READ_SUBCOMMANDS:
            if rest[0] == 'search-replace':
                return False
            if rest[0] in ('acf', 'gf'):
                return len(rest) > 1 and rest[1] in ('list', 'get', 'export')
            return True
    return False


def segment_is_read_only(segment):
    segment = segment.strip()
    if not segment or segment.startswith('#'):
        return True
    if re.search(r'>(?!&[12]\b|\s*/dev/null\b)', segment):
        return False
    try:
        tokens = shlex.split(segment, comments=True)
    except ValueError:
        return False
    tokens = [token for token in tokens if token not in ('(', ')', '{', '}')]
    while tokens and (re.match(r'^[A-Za-z_][A-Za-z0-9_]*=', tokens[0])
                      or tokens[0] in ('env', 'time', 'nice', 'if', 'then', 'else', 'elif', 'while', 'until',
                                       'do', '!', 'command', 'builtin')):
        tokens = tokens[1:]
    if not tokens:
        return True
    command = tokens[0].rsplit('/', 1)[-1]
    if command == 'wp':
        return wp_is_read_only(tokens[1:])
    if command == 'git':
        args = [token for token in tokens[1:] if not token.startswith('-')]
        return bool(args) and args[0] in GIT_READ_SUBCOMMANDS and not (
            args[0] in ('config', 'remote', 'tag', 'branch') and len(args) > 1 and args[1] not in ('-v', 'list', 'get'))
    if command == 'php':
        return any(token in ('-l', '-v', '-i', '-m', '--version') for token in tokens[1:]) and '-r' not in tokens
    if command == 'find':
        return not any(token in ('-delete', '-exec', '-execdir', '-ok') for token in tokens)
    if command == 'sed':
        return not any(token.startswith('-i') for token in tokens[1:])
    if command == 'xargs':
        return len(tokens) > 1 and segment_is_read_only(' '.join(tokens[1:]))
    if command == 'tar':
        return len(tokens) > 1 and ('t' in tokens[1].lstrip('-') and 'x' not in tokens[1].lstrip('-'))
    if command in ('gzip', 'bzip2', 'xz'):
        return any(token in ('-c', '-l', '-t', '--stdout', '--list', '--test') for token in tokens[1:])
    return command in READ_ONLY_COMMANDS


def split_segments(script):
    """Rough split into simple commands. Heredoc bodies are classified like commands (harmless)."""
    pieces = []
    for line in script.splitlines():
        for piece in re.split(r'\|\||&&|;|\|(?!\|)|(?<![>&])&(?![&>])', line):
            pieces.append(piece)
    return pieces


def script_is_read_only(script):
    return all(segment_is_read_only(segment) for segment in split_segments(script))


def deny_backup_write(script):
    for line in script.splitlines():
        if 'bard-gate/db' in line and re.search(r'\b(rm|mv|truncate|shred)\b|>', line):
            return 'Databasekopiene i bard-gate/db er skrivebeskyttet. Les dem gjerne.'
    return None


def find_denial(script):
    lowered = script.lower()
    for pattern, message in DENY_RULES:
        if re.search(pattern, lowered, re.IGNORECASE | re.MULTILINE):
            return message
    message = deny_backup_write(lowered)
    if message:
        return message
    for pattern in CODE_WRITE_RULES:
        if re.search(pattern, lowered, re.IGNORECASE | re.MULTILINE):
            return CODE_WRITE_MESSAGE
    for segment in split_segments(lowered):
        try:
            tokens = shlex.split(segment, comments=True)
        except ValueError:
            continue
        if len(tokens) >= 3 and tokens[0].rsplit('/', 1)[-1] in ('cp', 'mv', 'install'):
            if re.search(GIT_TREES, tokens[-1]) and tokens[-1].endswith(('.php', '/')):
                return CODE_WRITE_MESSAGE
    return None


# --- Infrastructure ------------------------------------------------------------------------
def ensure_dirs():
    for folder in (BASE, LOG_DIR, DB_DIR, MOVED_DIR):
        folder.mkdir(parents=True, exist_ok=True, mode=0o700)
        try:
            folder.chmod(0o700)
        except OSError:
            pass


def log(entry):
    ensure_dirs()
    path = LOG_DIR / (dt.datetime.now(dt.timezone.utc).strftime('%Y-%m-%d') + '.jsonl')
    with path.open('a') as stream:
        fcntl.flock(stream, fcntl.LOCK_EX)
        stream.write(json.dumps(entry, ensure_ascii=False) + '\n')
    try:
        path.chmod(0o600)
    except OSError:
        pass


def say(message):
    sys.stderr.write('[bard-gate] ' + message + '\n')
    sys.stderr.flush()


def take_backup(label):
    """Return (status, path). status: 'created' | 'reused' | 'failed'."""
    ensure_dirs()
    with (BASE / '.backup.lock').open('a') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX)
        dumps = sorted(DB_DIR.glob('*.sql.gz'), key=lambda item: item.stat().st_mtime)
        if dumps and time.time() - dumps[-1].stat().st_mtime < DUMP_INTERVAL_SECONDS:
            return 'reused', str(dumps[-1])
        stamp = dt.datetime.now(dt.timezone.utc).strftime('%Y%m%dT%H%M%SZ')
        target = DB_DIR / (stamp + '-' + label + '.sql.gz')
        partial = target.with_suffix('.gz.part')
        try:
            with partial.open('wb') as out:
                export = subprocess.Popen(
                    ['wp', '--path=' + WP_PATH, '--skip-plugins', '--skip-themes', 'db', 'export', '-'],
                    stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                gzip = subprocess.Popen(['gzip', '-1'], stdin=export.stdout, stdout=out, stderr=subprocess.PIPE)
                export.stdout.close()
                export_error = export.stderr.read()
                export.wait(timeout=600)
                gzip.wait(timeout=600)
            if export.returncode != 0 or gzip.returncode != 0 or partial.stat().st_size < 1024:
                partial.unlink(missing_ok=True)
                say('Databasekopi feilet: ' + export_error.decode(errors='replace').strip()[-300:])
                return 'failed', None
            partial.chmod(0o600)
            partial.replace(target)
        except Exception as error:  # noqa: BLE001 - any failure means "no backup", which we report
            partial.unlink(missing_ok=True)
            say('Databasekopi feilet: ' + str(error)[-300:])
            return 'failed', None
        for old in sorted(DB_DIR.glob('*.sql.gz'), key=lambda item: item.stat().st_mtime)[:-KEEP_DUMPS]:
            old.unlink(missing_ok=True)
        return 'created', str(target)


def make_marker(call_id):
    """A file whose mtime is just before the script starts; `find -newer` is POSIX and portable."""
    ensure_dirs()
    marker = BASE / ('.marker-' + call_id)
    marker.touch()
    past = time.time() - 2
    os.utime(marker, (past, past))
    return marker


def post_checks(marker):
    """Return a list of warnings after a writing script."""
    warnings = []
    try:
        changed = subprocess.run(
            ['find', WP_PATH + '/wp-content/themes', WP_PATH + '/wp-content/mu-plugins', WP_PATH + '/wp-content/plugins',
             '-xdev', '-type', 'f', '-name', '*.php', '-newer', str(marker),
             '-not', '-path', '*/node_modules/*', '-not', '-path', '*/vendor/*'],
            capture_output=True, text=True, timeout=60).stdout.split()
        for path in changed[:50]:
            lint = subprocess.run(['php', '-l', path], capture_output=True, text=True, timeout=30)
            if lint.returncode != 0:
                warnings.append('SYNTAKSFEIL i ' + path + ': ' + (lint.stderr or lint.stdout).strip()[:300]
                                + ' Nettstedet kan være nede. Rett filen nå.')
    except Exception as error:  # noqa: BLE001
        warnings.append('Kunne ikke syntakssjekke nye PHP-filer: ' + str(error)[:200])
    try:
        dumps = subprocess.run(
            ['find', WP_PATH, '-xdev', '-type', 'f', '-newer', str(marker), '-size', '+512k',
             '(', '-name', '*.sql', '-o', '-name', '*.sql.gz', '-o', '-name', '*.sql.zip', ')'],
            capture_output=True, text=True, timeout=120).stdout.split()
        for path in dumps[:20]:
            target = MOVED_DIR / (dt.datetime.now(dt.timezone.utc).strftime('%Y%m%dT%H%M%SZ') + '-' + Path(path).name)
            shutil.move(path, target)
            target.chmod(0o600)
            warnings.append('Databasedump i den offentlige mappen er flyttet til ' + str(target)
                            + '. Skriv aldri dumper under site/public.')
    except Exception as error:  # noqa: BLE001
        warnings.append('Kunne ikke sjekke etter dumper i den offentlige mappen: ' + str(error)[:200])
    try:
        status = subprocess.run(
            ['curl', '-sS', '-m', '20', '-o', '/dev/null', '-w', '%{http_code}', '-H', 'Cache-Control: no-cache',
             SITE_CHECK_URL], capture_output=True, text=True, timeout=30).stdout.strip()
        if not status.startswith(('2', '3')):
            warnings.append('Nettstedet svarer HTTP ' + (status or 'ukjent') + ' på ' + SITE_CHECK_URL
                            + ' etter denne kommandoen. Sjekk og rett med en gang.')
    except Exception as error:  # noqa: BLE001
        warnings.append('Kunne ikke sjekke nettstedet: ' + str(error)[:200])
    marker.unlink(missing_ok=True)
    return warnings


def show_log(count):
    ensure_dirs()
    entries = []
    for path in sorted(LOG_DIR.glob('*.jsonl'))[-3:]:
        entries.extend(line for line in path.read_text().splitlines() if line.strip())
    for line in entries[-count:]:
        try:
            entry = json.loads(line)
        except ValueError:
            continue
        if entry.get('event') != 'end':
            continue
        head = (entry.get('script') or '').strip().splitlines()
        summary = head[0][:120] if head else ''
        print(entry['ts'], entry.get('class', '?'), 'exit=' + str(entry.get('exit_code')),
              'backup=' + str(entry.get('backup') or '-'), summary)
        for warning in entry.get('warnings') or []:
            print('    ! ' + warning)


def show_backups():
    ensure_dirs()
    for path in sorted(DB_DIR.glob('*.sql.gz')):
        stat = path.stat()
        print(dt.datetime.fromtimestamp(stat.st_mtime, dt.timezone.utc).isoformat(timespec='seconds'),
              str(round(stat.st_size / 1_048_576, 1)) + ' MB', path)
    print('Gjenopprett med: wp --path=' + WP_PATH + ' db import <fil> (pakk ut med gunzip -c først).')


def main():
    label = sys.argv[1] if len(sys.argv) > 1 else 'ukjent'
    original = os.environ.get('SSH_ORIGINAL_COMMAND', '')
    client = (os.environ.get('SSH_CONNECTION') or '').split(' ')[0]
    call_id = uuid.uuid4().hex[:12]
    started = time.time()
    base_entry = {'call_id': call_id, 'label': label, 'client': client, 'original_command': original[:2000]}

    if original.strip() in ('gate-log', 'gate-backups') or original.strip().startswith('gate-log '):
        parts = original.split()
        log({**base_entry, 'event': 'end', 'ts': dt.datetime.now(dt.timezone.utc).isoformat(timespec='seconds'),
             'class': 'meta', 'exit_code': 0})
        if parts[0] == 'gate-backups':
            show_backups()
        else:
            show_log(int(parts[1]) if len(parts) > 1 and parts[1].isdigit() else 30)
        return 0

    if original.strip() in ('sh -s', 'bash -s', '/bin/sh -s', '/bin/bash -s'):
        mode = 'script'
        data = sys.stdin.buffer.read(MAX_SCRIPT_BYTES + 1)
        if len(data) > MAX_SCRIPT_BYTES:
            say('Skriptet er for stort (over 1 MB). Del det opp.')
            log({**base_entry, 'event': 'end', 'ts': dt.datetime.now(dt.timezone.utc).isoformat(timespec='seconds'),
                 'class': 'denied', 'denied_reason': 'too large', 'exit_code': DENIED_EXIT})
            return DENIED_EXIT
        script = data.decode('utf-8', errors='replace')
        child_args = ['/bin/sh', '-s']
    elif not original.strip():
        say('Interaktivt skall er sperret. Bruk BIMVerdi.ps1 (sh -s med skript på stdin).')
        log({**base_entry, 'event': 'end', 'ts': dt.datetime.now(dt.timezone.utc).isoformat(timespec='seconds'),
             'class': 'denied', 'denied_reason': 'interactive shell', 'exit_code': DENIED_EXIT})
        return DENIED_EXIT
    else:
        mode = 'command'
        script = original
        data = None
        first = original.split()[0].rsplit('/', 1)[-1] if original.split() else ''
        if first in ('scp', 'sftp', 'sftp-server', 'internal-sftp', 'rsync'):
            say('Filoverføring (scp/sftp/rsync) er sperret. Kode går via Git og PR; opplastinger via wp-admin.')
            log({**base_entry, 'event': 'end', 'ts': dt.datetime.now(dt.timezone.utc).isoformat(timespec='seconds'),
                 'class': 'denied', 'denied_reason': 'file transfer', 'exit_code': DENIED_EXIT})
            return DENIED_EXIT
        child_args = ['/bin/sh', '-c', original]

    digest = hashlib.sha256(script.encode('utf-8', errors='replace')).hexdigest()
    entry = {**base_entry, 'mode': mode, 'script': script[:LOG_BODY_LIMIT], 'script_sha256': digest,
             'script_bytes': len(script.encode('utf-8', errors='replace'))}

    denial = find_denial(script)
    if denial:
        say('SPERRET: ' + denial)
        say('Kommandoen ble ikke kjørt. Hendelsen er loggført (' + call_id + ').')
        log({**entry, 'event': 'end', 'ts': dt.datetime.now(dt.timezone.utc).isoformat(timespec='seconds'),
             'class': 'denied', 'denied_reason': denial, 'exit_code': DENIED_EXIT})
        return DENIED_EXIT

    read_only = script_is_read_only(script)
    entry['class'] = 'read' if read_only else 'write'
    log({**entry, 'event': 'start', 'ts': dt.datetime.now(dt.timezone.utc).isoformat(timespec='seconds')})

    backup = None
    if not read_only:
        status, path = take_backup(label)
        backup = status + (':' + path if path else '')
        if status == 'created':
            say('Databasekopi tatt: ' + path)
        elif status == 'reused':
            say('Databasekopi fra de siste 10 minuttene brukes: ' + path)
        else:
            say('ADVARSEL: Ingen databasekopi. Kommandoen kjøres likevel. Servebolt har timesbackup.')

    marker = make_marker(call_id) if not read_only else None
    sys.stdout.flush()
    sys.stderr.flush()
    try:
        if data is not None:
            child = subprocess.run(child_args, input=data, cwd=HOME)
        else:
            child = subprocess.run(child_args, cwd=HOME)
        exit_code = child.returncode if child.returncode >= 0 else 128 - child.returncode
    except Exception as error:  # noqa: BLE001
        say('Kunne ikke starte kommandoen: ' + str(error)[:200])
        exit_code = 126

    warnings = post_checks(marker) if not read_only else []
    for warning in warnings:
        say('ADVARSEL: ' + warning)
    log({**entry, 'event': 'end', 'ts': dt.datetime.now(dt.timezone.utc).isoformat(timespec='seconds'),
         'backup': backup, 'exit_code': exit_code, 'duration_s': round(time.time() - started, 2),
         'warnings': warnings})
    return exit_code


if __name__ == '__main__':
    try:
        sys.exit(main())
    except KeyboardInterrupt:
        sys.exit(130)
