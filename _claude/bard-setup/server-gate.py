#!/usr/bin/env python3
"""Forced command for Bård's permanent SSH key (installed as ~/.bimverdi-gate/gate.py <label>).

The holder of the key works on production on their own responsibility. The gate does not try
to make that safe; it makes it visible and recoverable:

- Every call is logged with the full script (truncated at 64 KB) and its result.
- A short list of never-legitimate operations is refused (destroying the database, deleting
  trees, rewriting the production git tree, touching keys/config, uploading files, background
  jobs that outlive the call).
- Before any script that may write, a compressed database dump is taken (at most once per
  10 minutes; the newest 10 plus the first of each day for 7 days are kept). If the dump fails
  and the script writes to the database, the script is refused.
- After a writing script, new PHP files are syntax-checked, changes inside the Git-managed
  trees are reported, SQL dumps left in the web root are moved out of it, and the site is fetched
  to detect a broken install.

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
SITE_CHECK_URLS = ('https://bimverdi.no/wp-login.php', 'https://bimverdi.no/')
HOME = Path.home()
BASE = HOME / 'private' / 'bard-gate'
LOG_DIR = BASE / 'log'
DB_DIR = BASE / 'db'
MOVED_DIR = BASE / 'moved'
MAX_SCRIPT_BYTES = 1_000_000
LOG_BODY_LIMIT = 65_536
KEEP_DUMPS = 10
KEEP_DAILY_DAYS = 7
DUMP_INTERVAL_SECONDS = 600
DUMP_TIMEOUT_SECONDS = 600
POST_CHECK_FILE_LIMIT = 200
DENIED_EXIT = 77
NO_BACKUP_EXIT = 76

# --- Refusal rules -------------------------------------------------------------------------
# Each rule: (regex on the whole lower-cased script, message).
DENY_RULES = [
    (r'\bwp\b[^\n;|&]*\bdb\s+(reset|drop|clean)\b',
     'wp db reset/drop/clean sletter hele databasen.'),
    (r'\bwp\b[^\n;|&]*\bsite\s+(empty|delete)\b',
     'wp site empty/delete sletter alt innhold.'),
    (r'\bwp\b[^\n;|&]*\bcore\s+(update|download|install|update-db|multisite-\w+)\b',
     'Oppdatering av WordPress-kjernen gjør Andreas.'),
    (r'\bwp\b[^\n;|&]*\bconfig\s+(set|create|delete|shuffle-salts|edit)\b',
     'wp-config.php endres bare av Andreas. Bruk wp config get/list for å lese.'),
    (r'\bwp\b[^\n;|&]*\b(plugin|theme)\s+(delete|update|install|uninstall)\b[^\n;|&]*\b(bim-verdi-core|bimverdi-theme)\b',
     'bim-verdi-core og bimverdi-theme ligger i Git. Endre dem via PR, ikke med wp plugin/theme.'),
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
     r'update-ref|symbolic-ref|remote\s+(add|remove|rm|set-url)|branch\s+-[dDMm])\b',
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
    (r'\b(nohup|setsid|disown|screen|tmux)\b|(?<![>&|\d])&(?![&>])',
     'Bakgrunnskjøring (&, nohup, setsid, screen, tmux) er sperret. Jobben må være ferdig når kommandoen avslutter.'),
    (r'\b(sudo|su|doas)\b',
     'Det finnes ingen root-tilgang her.'),
]

# Writing code straight into the Git-managed trees bypasses lint and is lost on next deploy.
GIT_TREES = r'(themes|mu-plugins|plugins/bim-verdi-core)/'
WRITE_TOKENS = r'(>>?|\btee\b|\bsed\s+-i\b|\bperl\s+-i\b|\bpatch\b|\bunzip\b|\btar\s+-?x|\bcp\b|\bmv\b|\binstall\b|\bgunzip\b)'
CODE_WRITE_RULES = [
    r'(>>?|\btee\b|\bsed\s+-i\b|\bperl\s+-i\b|\bpatch\b|\bunzip\b|\btar\s+-?x)[^\n;|&]*' + GIT_TREES,
]
CODE_WRITE_MESSAGE = ('Kode i themes/, mu-plugins/ og plugins/bim-verdi-core/ endres via Git og PR, '
                      'ikke rett på serveren. Endringer der forsvinner ved neste publisering.')

# --- Read-only classification ---------------------------------------------------------------
READ_ONLY_COMMANDS = {
    'ls', 'cat', 'head', 'tail', 'less', 'more', 'grep', 'egrep', 'fgrep', 'rg', 'wc', 'pwd',
    'echo', 'printf', 'true', 'false', 'test', '[', 'du', 'df', 'stat', 'file', 'date', 'whoami',
    'id', 'env', 'printenv', 'which', 'type', 'uname', 'hostname', 'uniq', 'cut', 'tr',
    'awk', 'diff', 'cmp', 'md5sum', 'sha256sum', 'sha1sum', 'base64', 'zcat', 'readlink',
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
    ('db', 'size'), ('db', 'tables'), ('db', 'columns'), ('db', 'check'), ('db', 'prefix'),
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
    ('cli', 'version'), ('cli', 'info'), ('cli', 'has-command'), ('help',),
}
WP_READ_ONLY_FLAGS = ('--dry-run', '--help')
SHELL_PREFIX_WORDS = ('env', 'time', 'nice', 'if', 'then', 'else', 'elif', 'while', 'until', 'do', '!', 'command', 'builtin')


def wp_is_read_only(tokens):
    """tokens start after 'wp'. Global options (--path=...) come first."""
    rest = [token for token in tokens if not token.startswith('-')]
    flags = [token for token in tokens if token.startswith('-')]
    if any(flag in WP_READ_ONLY_FLAGS for flag in flags) or not rest:
        return True
    if rest[:2] == ['db', 'query'] and len(rest) > 2:
        return bool(re.match(r'^\s*(select|show|describe|desc|explain)\b', rest[2], re.IGNORECASE))
    if rest[:2] == ['db', 'export']:
        target = rest[2] if len(rest) > 2 else ''
        # A dump written under the web root must trigger the post-check that moves it away.
        return target in ('', '-') or not ('public' in target or 'wp-content' in target)
    if rest[0] in ('acf', 'gf'):
        return len(rest) > 1 and rest[1] in ('list', 'get', 'export')
    for length in (3, 2, 1):
        if tuple(rest[:length]) in WP_READ_SUBCOMMANDS:
            return True
    return False


def normalise_segment(segment):
    segment = segment.strip()
    while segment.startswith(('(', '{')):
        segment = segment[1:].strip()
    while segment.endswith((')', '}')):
        segment = segment[:-1].strip()
    return segment


def command_tokens(segment):
    """Tokens of a simple command with env assignments and shell keywords stripped, or None."""
    try:
        tokens = shlex.split(normalise_segment(segment), comments=True)
    except ValueError:
        return None
    while tokens and (re.match(r'^[A-Za-z_][A-Za-z0-9_]*=', tokens[0]) or tokens[0] in SHELL_PREFIX_WORDS):
        tokens = tokens[1:]
    return tokens


def segment_is_read_only(segment):
    segment = normalise_segment(segment)
    if not segment or segment.startswith('#'):
        return True
    if re.search(r'>(?!&[12]\b|\s*/dev/null\b)', segment) or '$(' in segment or '`' in segment:
        return False
    tokens = command_tokens(segment)
    if tokens is None:
        return False
    if not tokens:
        return True
    command = tokens[0].rsplit('/', 1)[-1]
    if command == 'wp':
        return wp_is_read_only(tokens[1:])
    if command == 'git':
        args = tokens[1:]
        while args and args[0].startswith('-'):
            args = args[2:] if args[0] in ('-C', '-c', '--git-dir', '--work-tree') else args[1:]
        if not args or args[0] not in GIT_READ_SUBCOMMANDS:
            return False
        if args[0] in ('config', 'remote', 'branch', 'tag'):
            extra = [token for token in args[1:] if not token.startswith('-')]
            return not extra or (args[0] == 'config' and extra[0] in ('get', 'list'))
        return True
    if command == 'php':
        return any(token in ('-l', '-v', '-i', '-m', '--version') for token in tokens[1:]) and '-r' not in tokens
    if command == 'find':
        return not any(token in ('-delete', '-exec', '-execdir', '-ok', '-fprint', '-fprintf') for token in tokens)
    if command == 'sed':
        return not any(token.startswith('-i') for token in tokens[1:]) and not any(
            re.search(r'(^|;)\s*w\s', token) for token in tokens[1:])
    if command == 'sort':
        return '-o' not in tokens and not any(token.startswith('--output') for token in tokens)
    if command == 'xargs':
        return len(tokens) > 1 and segment_is_read_only(' '.join(tokens[1:]))
    if command == 'tar':
        return len(tokens) > 1 and ('t' in tokens[1].lstrip('-') and 'x' not in tokens[1].lstrip('-'))
    if command in ('gzip', 'gunzip', 'bzip2', 'xz'):
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


def script_writes_database(script):
    """True when a segment runs a non-read wp command, mysql, or an interpreter that could."""
    for segment in split_segments(script):
        if '$(' in segment or '`' in segment:
            return True
        tokens = command_tokens(segment)
        if tokens is None:
            return True
        if not tokens:
            continue
        command = tokens[0].rsplit('/', 1)[-1]
        if command == 'wp' and not wp_is_read_only(tokens[1:]):
            return True
        if command in ('mysql', 'mysqladmin', 'php', 'python3', 'python', 'perl', 'sh', 'bash', 'dash'):
            return True
    return False


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
    # cd into a Git-managed tree followed by any writing token counts as writing into it.
    if re.search(r'\bcd\b[^\n;|&]*' + GIT_TREES, lowered) and re.search(WRITE_TOKENS, lowered):
        return CODE_WRITE_MESSAGE
    for segment in split_segments(lowered):
        tokens = command_tokens(segment)
        if tokens and len(tokens) >= 3 and tokens[0].rsplit('/', 1)[-1] in ('cp', 'mv', 'install'):
            if re.search(GIT_TREES, tokens[-1]):
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


def now_iso():
    return dt.datetime.now(dt.timezone.utc).isoformat(timespec='seconds')


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


def rotate_dumps():
    dumps = sorted(DB_DIR.glob('*.sql.gz'), key=lambda item: item.stat().st_mtime)
    daily = [item for item in dumps if item.name.endswith('-first.sql.gz')]
    recent = [item for item in dumps if not item.name.endswith('-first.sql.gz')]
    for old in recent[:-KEEP_DUMPS]:
        old.unlink(missing_ok=True)
    cutoff = time.time() - KEEP_DAILY_DAYS * 86400
    for old in daily:
        if old.stat().st_mtime < cutoff:
            old.unlink(missing_ok=True)


def take_backup(label):
    """Return (status, path). status: 'created' | 'reused' | 'failed'."""
    ensure_dirs()
    with (BASE / '.backup.lock').open('a') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX)
        dumps = sorted(DB_DIR.glob('*.sql.gz'), key=lambda item: item.stat().st_mtime)
        if dumps and time.time() - dumps[-1].stat().st_mtime < DUMP_INTERVAL_SECONDS:
            return 'reused', str(dumps[-1])
        today = dt.datetime.now(dt.timezone.utc).strftime('%Y%m%d')
        first_today = not any(item.name.startswith(today) and item.name.endswith('-first.sql.gz') for item in dumps)
        stamp = dt.datetime.now(dt.timezone.utc).strftime('%Y%m%dT%H%M%SZ')
        target = DB_DIR / (stamp + '-' + label + ('-first' if first_today else '') + '.sql.gz')
        partial = target.with_suffix('.gz.part')
        export = gzip = None
        try:
            with partial.open('wb') as out:
                export = subprocess.Popen(
                    ['wp', '--path=' + WP_PATH, '--skip-plugins', '--skip-themes', 'db', 'export', '-'],
                    stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                gzip = subprocess.Popen(['gzip', '-1'], stdin=export.stdout, stdout=out, stderr=subprocess.DEVNULL)
                export.stdout.close()
                _, export_error = export.communicate(timeout=DUMP_TIMEOUT_SECONDS)
                gzip.wait(timeout=60)
            if export.returncode != 0 or gzip.returncode != 0 or partial.stat().st_size < 1024:
                partial.unlink(missing_ok=True)
                say('Databasekopi feilet: ' + export_error.decode(errors='replace').strip()[-300:])
                return 'failed', None
            partial.chmod(0o600)
            partial.replace(target)
        except Exception as error:  # noqa: BLE001 - any failure means "no backup", which we report
            for process in (export, gzip):
                if process and process.poll() is None:
                    process.kill()
            partial.unlink(missing_ok=True)
            say('Databasekopi feilet: ' + type(error).__name__ + ' ' + str(error)[-300:])
            return 'failed', None
        rotate_dumps()
        return 'created', str(target)


def make_marker(call_id):
    """A file whose ctime is just before the script starts; `find -cnewer` cannot be fooled by cp -p."""
    ensure_dirs()
    marker = BASE / ('.marker-' + call_id)
    marker.touch()
    time.sleep(0.05)
    return marker


def find_newer(marker, roots, extra):
    """Paths (NUL-separated) changed after the marker. Returns (paths, error)."""
    roots = [root for root in roots if os.path.isdir(root)]
    if not roots:
        return [], None
    result = subprocess.run(['find', *roots, '-xdev', '-type', 'f', '-cnewer', str(marker), *extra, '-print0'],
                            capture_output=True, timeout=120)
    paths = [path.decode(errors='replace') for path in result.stdout.split(b'\0') if path]
    error = result.stderr.decode(errors='replace').strip() if result.returncode != 0 else None
    return paths, error


def post_checks(marker, call_id):
    """Return a list of warnings after a writing script."""
    warnings = []
    content = WP_PATH + '/wp-content'
    try:
        changed, error = find_newer(marker, [content + '/themes', content + '/mu-plugins', content + '/plugins'],
                                    ['-not', '-path', '*/node_modules/*', '-not', '-path', '*/vendor/*'])
        if error:
            warnings.append('Søk etter endrede filer feilet delvis: ' + error[:200])
        git_managed = [path for path in changed if re.search(GIT_TREES, path)]
        if git_managed:
            warnings.append('Filer i Git-styrt mappe ble endret på serveren: ' + ', '.join(git_managed[:5])
                            + (' …' if len(git_managed) > 5 else '')
                            + '. Endringen er ikke lintet og forsvinner ved neste publisering. Legg den i en PR.')
        php_files = [path for path in changed if path.endswith('.php')]
        if len(php_files) > POST_CHECK_FILE_LIMIT:
            warnings.append('Over ' + str(POST_CHECK_FILE_LIMIT) + ' PHP-filer endret; bare de første ble syntakssjekket.')
        for path in php_files[:POST_CHECK_FILE_LIMIT]:
            lint = subprocess.run(['php', '-l', path], capture_output=True, text=True, timeout=30)
            if lint.returncode != 0:
                warnings.append('SYNTAKSFEIL i ' + path + ': ' + (lint.stderr or lint.stdout).strip()[:300]
                                + ' Nettstedet kan være nede. Rett filen nå.')
    except Exception as error:  # noqa: BLE001
        warnings.append('Kunne ikke syntakssjekke nye PHP-filer: ' + str(error)[:200])
    try:
        dumps, error = find_newer(marker, [WP_PATH], ['-size', '+512k', '(', '-name', '*.sql', '-o', '-name', '*.sql.gz',
                                                       '-o', '-name', '*.sql.zip', ')'])
        if error:
            warnings.append('Søk etter dumper i den offentlige mappen feilet delvis: ' + error[:200])
        for index, path in enumerate(dumps[:50]):
            target = MOVED_DIR / (dt.datetime.now(dt.timezone.utc).strftime('%Y%m%dT%H%M%SZ') + '-' + call_id + '-'
                                  + str(index) + '-' + Path(path).name)
            shutil.move(path, target)
            target.chmod(0o600)
            warnings.append('Databasedump i den offentlige mappen er flyttet til ' + str(target)
                            + '. Skriv aldri dumper under site/public.')
    except Exception as error:  # noqa: BLE001
        warnings.append('Kunne ikke sjekke etter dumper i den offentlige mappen: ' + str(error)[:200])
    for url in SITE_CHECK_URLS:
        try:
            status = subprocess.run(
                ['curl', '-sS', '-m', '20', '-o', '/dev/null', '-w', '%{http_code}', '-H', 'Cache-Control: no-cache',
                 url + ('?bard-gate=' + call_id if url.endswith('/') else '')],
                capture_output=True, text=True, timeout=30).stdout.strip()
            if status != '200':
                warnings.append('Nettstedet svarer HTTP ' + (status or 'ukjent') + ' på ' + url
                                + ' etter denne kommandoen. Sjekk og rett med en gang.')
        except Exception as error:  # noqa: BLE001
            warnings.append('Kunne ikke sjekke ' + url + ': ' + str(error)[:200])
    marker.unlink(missing_ok=True)
    return warnings


def show_log(count):
    ensure_dirs()
    entries = []
    for path in sorted(LOG_DIR.glob('*.jsonl'))[-3:]:
        for line in path.read_text().splitlines():
            try:
                entries.append(json.loads(line))
            except ValueError:
                continue
    ended = {entry['call_id'] for entry in entries if entry.get('event') == 'end'}
    rows = [entry for entry in entries if entry.get('event') == 'end'
            or (entry.get('event') == 'start' and entry['call_id'] not in ended)]
    for entry in rows[-count:]:
        head = (entry.get('script') or entry.get('original_command') or '').strip().splitlines()
        summary = head[0][:120] if head else ''
        if entry.get('event') == 'start':
            print(entry['ts'], 'UFULLFØRT (ingen sluttlinje; avbrutt eller fortsatt i gang)', summary)
            continue
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
    print('Kopiene dekker bare databasen. Gjenopprett med: gunzip -c <fil> | wp --path=' + WP_PATH + ' db import -')


def run(label):
    original = os.environ.get('SSH_ORIGINAL_COMMAND', '')
    client = (os.environ.get('SSH_CONNECTION') or '').split(' ')[0]
    call_id = uuid.uuid4().hex[:12]
    started = time.time()
    base_entry = {'call_id': call_id, 'label': label, 'client': client, 'original_command': original[:2000]}
    state = {'logged_end': False}
    entry = {}

    def finish(**fields):
        state['logged_end'] = True
        log({**base_entry, **entry, **fields, 'event': 'end', 'ts': now_iso()})

    def deny(reason, message):
        if message:
            say(message)
        finish(**{'class': 'denied', 'denied_reason': reason, 'exit_code': DENIED_EXIT})
        return DENIED_EXIT

    try:
        stripped = original.strip()
        if stripped in ('gate-log', 'gate-backups') or stripped.startswith('gate-log '):
            parts = stripped.split()
            finish(**{'class': 'meta', 'exit_code': 0})
            if parts[0] == 'gate-backups':
                show_backups()
            else:
                show_log(int(parts[1]) if len(parts) > 1 and parts[1].isdigit() else 30)
            return 0

        if not stripped:
            return deny('interactive shell', 'Interaktivt skall er sperret. Bruk BIMVerdi.ps1 (sh -s med skript på stdin).')
        first = stripped.split()[0].rsplit('/', 1)[-1]
        if first in ('scp', 'sftp', 'sftp-server', 'internal-sftp', 'rsync'):
            return deny('file transfer', 'Filoverføring (scp/sftp/rsync) er sperret. Kode går via Git og PR; opplastinger via wp-admin.')

        # Everything that reaches the server is read first and logged. A caller that keeps stdin
        # open (plain `ssh host cmd` from a terminal) must use `ssh -n`.
        data = sys.stdin.buffer.read(MAX_SCRIPT_BYTES + 1)
        if len(data) > MAX_SCRIPT_BYTES:
            return deny('too large', 'Skriptet er for stort (over 1 MB). Del det opp.')
        if stripped in ('sh -s', 'bash -s', '/bin/sh -s', '/bin/bash -s'):
            mode = 'script'
            script = data.decode('utf-8', errors='replace')
            shell = '/bin/bash' if 'bash' in stripped and os.path.exists('/bin/bash') else '/bin/sh'
            child_args = [shell, '-s']
            text_for_rules = script
            raw = data
        else:
            mode = 'command'
            script = original
            child_args = ['/bin/sh', '-c', original]
            text_for_rules = original + '\n' + data.decode('utf-8', errors='replace')
            raw = original.encode()
        entry = {'mode': mode, 'script': script[:LOG_BODY_LIMIT], 'script_sha256': hashlib.sha256(raw).hexdigest(),
                 'script_bytes': len(raw), 'script_truncated': len(script) > LOG_BODY_LIMIT}
        if mode == 'command' and data:
            entry['stdin'] = data.decode('utf-8', errors='replace')[:LOG_BODY_LIMIT]
            entry['stdin_bytes'] = len(data)

        denial = find_denial(text_for_rules)
        if denial:
            say('SPERRET: ' + denial)
            say('Kommandoen ble ikke kjørt. Hendelsen er loggført (' + call_id + ').')
            return deny(denial, '')

        read_only = script_is_read_only(text_for_rules)
        entry['class'] = 'read' if read_only else 'write'
        log({**base_entry, **entry, 'event': 'start', 'ts': now_iso()})

        backup = None
        if not read_only:
            status, path = take_backup(label)
            backup = status + (':' + path if path else '')
            if status == 'created':
                say('Databasekopi tatt: ' + path)
            elif status == 'reused':
                say('Databasekopi fra de siste 10 minuttene brukes: ' + path)
            elif script_writes_database(text_for_rules):
                say('STOPPET: Ingen databasekopi kunne tas, og kommandoen kan endre databasen. Prøv igjen, eller be Andreas.')
                finish(**{'class': 'refused-no-backup', 'backup': backup, 'exit_code': NO_BACKUP_EXIT})
                return NO_BACKUP_EXIT
            else:
                say('ADVARSEL: Ingen databasekopi. Kommandoen kjøres likevel fordi den ser ut til å endre bare filer.')

        marker = make_marker(call_id) if not read_only else None
        sys.stdout.flush()
        sys.stderr.flush()
        try:
            child = subprocess.run(child_args, input=data, cwd=HOME)
            exit_code = child.returncode if child.returncode >= 0 else 128 - child.returncode
        except Exception as error:  # noqa: BLE001
            say('Kunne ikke starte kommandoen: ' + str(error)[:200])
            exit_code = 126

        warnings = post_checks(marker, call_id) if not read_only else []
        for warning in warnings:
            say('ADVARSEL: ' + warning)
        finish(**{'backup': backup, 'exit_code': exit_code, 'duration_s': round(time.time() - started, 2),
                  'warnings': warnings})
        return exit_code
    except BaseException as error:  # noqa: BLE001 - always leave an end line
        if not state['logged_end']:
            finish(**{'class': entry.get('class', 'aborted'), 'exit_code': 130,
                      'aborted': type(error).__name__ + ': ' + str(error)[:200],
                      'duration_s': round(time.time() - started, 2)})
        raise


def main():
    label = sys.argv[1] if len(sys.argv) > 1 else 'ukjent'
    try:
        return run(label)
    except KeyboardInterrupt:
        return 130


if __name__ == '__main__':
    sys.exit(main())
