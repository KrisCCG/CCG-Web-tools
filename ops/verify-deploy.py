#!/usr/bin/env python3
"""Verify that each deploy unit's local root maps 1:1 to the server root.

Read-only — uploads nothing, changes nothing. For every local file that would
deploy, checks the server has it at the identical relative path and compares
sizes. Run it after any upload to confirm what landed.

    python3 ops/verify-deploy.py

Reads the host, credentials and ignore list straight from each unit's
.vscode/sftp.json, so there is nothing to configure.

Note: .DS_Store and .vscode files may show as "not on server". That is correct
and expected — they are in the ignore list and are never uploaded. This
script's pattern matching is simpler than the SFTP extension's.
"""
import fnmatch, json, os, stat, sys
import paramiko

UNITS = [
    ("shop",            "clickclick.be/shop"),
    ("clickclick-chat", "clickclick.be/clickclick-chat"),
]
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

def ignored(rel, patterns):
    parts = rel.split('/')
    for p in patterns:
        if p.startswith('**/'):
            if fnmatch.fnmatch(parts[-1], p[3:]): return True
        if fnmatch.fnmatch(rel, p) or rel == p: return True
        for i in range(len(parts)):
            seg = '/'.join(parts[:i+1])
            if seg == p or fnmatch.fnmatch(seg, p): return True
    return False

def local_files(base, patterns):
    out = {}
    for dirpath, dirnames, filenames in os.walk(base):
        rel_dir = os.path.relpath(dirpath, base)
        rel_dir = '' if rel_dir == '.' else rel_dir
        dirnames[:] = [d for d in dirnames
                       if not ignored(f"{rel_dir}/{d}".lstrip('/'), patterns)]
        for fn in filenames:
            rel = f"{rel_dir}/{fn}".lstrip('/')
            if ignored(rel, patterns): continue
            full = os.path.join(dirpath, fn)
            try: out[rel] = os.path.getsize(full)
            except OSError: out[rel] = None   # broken symlink
    return out

def main():
    results = []
    for label, unit in UNITS:
        base = os.path.join(ROOT, unit)
        cfg = json.load(open(os.path.join(base, '.vscode', 'sftp.json')))
        remote = cfg['remotePath'].rstrip('/')
        patterns = cfg.get('ignore', [])
        lf = local_files(base, patterns)

        t = paramiko.Transport((cfg['host'], int(cfg.get('port', 22))))
        t.connect(username=cfg['username'], password=cfg['password'])
        t.set_keepalive(15)
        sftp = paramiko.SFTPClient.from_transport(t)
        sftp.get_channel().settimeout(60)

        match, missing, sizediff = [], [], []
        for rel, lsize in sorted(lf.items()):
            try:
                st = sftp.stat(f"{remote}/{rel}")
                if lsize is None or st.st_size == lsize: match.append(rel)
                else: sizediff.append((rel, lsize, st.st_size))
            except IOError:
                missing.append(rel)
        sftp.close(); t.close()
        results.append((label, remote, len(lf), match, missing, sizediff))

    for label, remote, total, match, missing, sizediff in results:
        print(f"\n=== {label} — {total} deployable files")
        print(f"    remotePath: {remote}")
        print(f"    present & identical : {len(match)}")
        print(f"    present, size differs: {len(sizediff)}")
        print(f"    not on server        : {len(missing)}")
        for rel, l, r in sizediff[:12]:
            print(f"      ~ {rel}  local {l:,} vs server {r:,}")
        if len(sizediff) > 12: print(f"      ... {len(sizediff)-12} more")
        for rel in missing[:12]:
            print(f"      - {rel}")
        if len(missing) > 12: print(f"      ... {len(missing)-12} more")

main()
