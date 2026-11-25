#!/usr/bin/env python3
"""
Print the full remote tree of clickclick.be over SFTP.

Works on SFTP-only accounts, where `ssh host find ...` is refused. Reads the
connection details from .vscode/sftp.json so there is nothing to retype, and
asks for the password interactively — it is never passed on the command line
(where it would land in shell history) and never written to disk.

    pip3 install paramiko
    python3 tools/sftp-tree.py                 # tree to stdout
    python3 tools/sftp-tree.py > tree.txt      # or to a file
    python3 tools/sftp-tree.py --depth 2       # shallower
    python3 tools/sftp-tree.py --sizes         # include file sizes

Flags files that are commonly sensitive so they stand out in the listing.
"""
import argparse, getpass, json, os, stat, sys

WATCH = ('.htpasswd', 'wp-config.php', 'secrets.php', '.env', 'wp-login.php')
WATCH_EXT = ('.xlsx', '.jsonl', '.sql', '.log')

# Directories worth naming but not walking into. wp-includes and wp-admin hold
# thousands of files and will drop the SFTP connection before the useful part of
# the tree is reached — that is what killed the first run. They are stock
# WordPress; the interesting question is only whether they are there at all.
SKIP = {'wp-includes', 'wp-admin', 'node_modules', '.git', 'languages',
        'languages-old', 'cache', 'upgrade', 'upgrade-temp-backup',
        'upgrade-temp-backup-old'}


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--depth', type=int, default=4)
    ap.add_argument('--sizes', action='store_true')
    ap.add_argument('--config', default=os.path.join('.vscode', 'sftp.json'))
    a = ap.parse_args()

    try:
        import paramiko
    except ImportError:
        sys.exit("paramiko is not installed.  pip3 install paramiko")

    cfg = json.load(open(a.config))
    host, port = cfg['host'], int(cfg.get('port', 22))
    user, root = cfg['username'], cfg['remotePath']
    print(f"# {user}@{host}:{root}", file=sys.stderr)
    pw = getpass.getpass(f"Password for {user}@{host}: ")

    t = paramiko.Transport((host, port))
    t.connect(username=user, password=pw)
    # The first run died with "Server connection dropped" partway through a big
    # directory. A keepalive stops the server closing an apparently idle link.
    t.set_keepalive(15)
    sftp = paramiko.SFTPClient.from_transport(t)
    sftp.get_channel().settimeout(60)

    counts = {'dirs': 0, 'files': 0, 'bytes': 0}
    flagged = []

    def walk(path, depth, prefix=''):
        if depth > a.depth:
            return
        try:
            entries = sorted(sftp.listdir_attr(path), key=lambda e: (not stat.S_ISDIR(e.st_mode), e.filename.lower()))
        except Exception as e:
            # Keep going: one unreadable or oversized directory should not cost
            # the whole listing, as it did on the first attempt.
            print(f"{prefix}[unreadable: {e}]")
            return
        for i, e in enumerate(entries):
            last = i == len(entries) - 1
            branch = '└── ' if last else '├── '
            full = path.rstrip('/') + '/' + e.filename
            if stat.S_ISDIR(e.st_mode):
                counts['dirs'] += 1
                if e.filename in SKIP:
                    print(f"{prefix}{branch}{e.filename}/   [not walked]")
                    continue
                print(f"{prefix}{branch}{e.filename}/")
                walk(full, depth + 1, prefix + ('    ' if last else '│   '))
            else:
                counts['files'] += 1
                counts['bytes'] += e.st_size or 0
                mark = ''
                if e.filename in WATCH or e.filename.endswith(WATCH_EXT):
                    mark = '   <-- check exposure'
                    flagged.append(full)
                size = f"  {e.st_size:>9,}" if a.sizes else ''
                print(f"{prefix}{branch}{e.filename}{size}{mark}")

    print(root)
    walk(root, 1)
    print(f"\n# {counts['dirs']} directories, {counts['files']} files, "
          f"{counts['bytes']/1024/1024:.1f} MB (to depth {a.depth})")
    if flagged:
        print("\n# Flagged by name. NOTE: many are already protected by an")
        print("# .htaccess in their own directory (calculator/orders/ and")
        print("# calculator/data/ both deny direct access). Check each with:")
        print("#   curl -s -o /dev/null -w '%{http_code}\\n' https://clickclick.be<path>")
        for f in flagged:
            print("#   " + f.replace(root, ''))
    sftp.close(); t.close()


if __name__ == '__main__':
    main()
