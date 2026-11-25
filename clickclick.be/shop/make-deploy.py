#!/usr/bin/env python3
"""
Builds ./deploy/ — one folder holding the three public directories, ready to
push to the server with the VS Code SFTP extension.

    python3 make-deploy.py

Rebuilds /papers/ and /legal/ from their text sources first, then copies only
the files that belong on a webserver. Re-run it after ANY change; it clears and
rewrites deploy/ each time, so the folder is always an exact mirror of what
should be live.

WHAT IT LEAVES OUT, AND WHY
    build.py, text/*.txt, img/README.txt   build tools and internal notes
    README.txt, .DS_Store, .vscode/        editor and repo clutter
    .htpasswd                              a password file. It is already on
                                           the server and is gitignored here by
                                           your own convention, so it stays out
                                           of the mirror. See DEPLOY.txt for
                                           why that matters when syncing.

VS CODE GOTCHA (the one that wastes an afternoon)
    The SFTP extension only works when the folder holding .vscode/sftp.json is
    the WORKSPACE ROOT. Open ./deploy/ itself — "Open Folder…" on deploy, not
    on the project. Opening the project root makes uploads fail silently.
    After editing sftp.json, run "Developer: Reload Window" or the old config
    stays cached.
"""
import os, shutil, subprocess, sys

ROOT   = os.path.dirname(os.path.abspath(__file__))
DEPLOY = os.path.join(ROOT, "deploy")

# (source dir, [files], [dirs])  — everything else in the source is skipped.
PARTS = [
    # NEVER add .htpasswd here. flyers/.htpasswd is not the shop's own password
    # file — it is the ONE shared file the whole host authenticates against:
    # /tools/, /flyers/, /print-calculator/, /calculator/ and /calculator/admin/
    # all point their AuthUserFile at it. Shipping a local copy overwrites the
    # live one and locks every gated area to whatever password this machine
    # happened to have. It is already on the server; leave it there.
    # (It was included once, on 19 Aug 2026, and did exactly that.)
    ("shop",   [".htaccess", "index.html", "logo.png", "caddy.svg",
                "bespoke-icon.svg"],                       ["fonts", "lib"]),
    # /flyers/ only redirects to /shop/ since Oct 2026 (printed QR codes).
    ("flyers", [".htaccess"],                               []),
    ("legal",  [".htaccess", "index.html", "fr.html", "nl.html"], ["fonts"]),
    ("papers", [".htaccess", "index.html", "fr.html", "nl.html"], ["fonts"]),
]
SKIP_NAMES = {".DS_Store", "README.txt", ".htpasswd"}

# Webserver-safe modes. Some source files in this repo are 700 (they were
# created by tools that set the executable bit), and shutil.copy2 preserves
# that. A 700 file uploaded to the server is unreadable by Apache, which
# answers 403 — so every copy is normalised here rather than trusting the
# source. Directories need +x to be traversable at all.
FILE_MODE = 0o644
DIR_MODE  = 0o755


def normalise_modes(root):
    """Force 644 on files and 755 on directories under root."""
    fixed = 0
    for dp, dirs, files in os.walk(root):
        for d in dirs:
            path = os.path.join(dp, d)
            if os.stat(path).st_mode & 0o777 != DIR_MODE:
                os.chmod(path, DIR_MODE); fixed += 1
        for f in files:
            path = os.path.join(dp, f)
            if os.stat(path).st_mode & 0o777 != FILE_MODE:
                os.chmod(path, FILE_MODE); fixed += 1
    return fixed


def build(sub):
    """Regenerate a directory's HTML from its content source."""
    script = os.path.join(ROOT, sub, "build.py")
    if os.path.exists(script):
        subprocess.run([sys.executable, script], check=True,
                       cwd=os.path.join(ROOT, sub),
                       stdout=subprocess.DEVNULL)
        print(f"  built   {sub}/")


def copy_dir(src, dst):
    shutil.copytree(src, dst,
                    ignore=lambda d, names: [n for n in names if n in SKIP_NAMES])



def verify():
    """Every local asset each page asks for must actually be in deploy/.

    Copying a directory wholesale is not proof it holds what the HTML needs —
    one missing webfont produces a page that silently falls back to a system
    face, which is exactly the failure this catches. Sample photos under img/
    are exempt: they are meant to be absent until they are shot, and degrade
    to a labelled placeholder by design.
    """
    import re
    missing = []
    for dirpath, _, files in os.walk(DEPLOY):
        for f in files:
            if not f.endswith(".html"):
                continue
            page = os.path.join(dirpath, f)
            html = open(page, encoding="utf-8").read()
            refs = set(re.findall(r"url\((?:&#39;)?([^)'\"]+?)(?:&#39;)?\)", html))
            refs |= set(re.findall(r'(?:src|href)="([^":#]+\.(?:png|svg|js|woff2|css))"', html))
            for ref in refs:
                if ref.startswith(("data:", "http", "//")):
                    continue
                target = (os.path.join(DEPLOY, ref.lstrip("/")) if ref.startswith("/")
                          else os.path.join(dirpath, ref))
                # Sample photos are exempt — absent by design until shot.
                # Test the resolved path, not the raw ref: the refs are
                # relative ("img/x.jpg"), so matching on "/img/" silently
                # exempts nothing and floods the report.
                if "img" in os.path.normpath(target).split(os.sep):
                    continue
                if not os.path.exists(target):
                    missing.append((os.path.relpath(page, DEPLOY), ref))
    return sorted(set(missing))


def main():
    print("Rebuilding pages from source…")
    build("papers")
    build("legal")

    if os.path.exists(DEPLOY):
        shutil.rmtree(DEPLOY)
    os.makedirs(DEPLOY)

    n = 0
    print("Assembling deploy/ …")
    for sub, files, dirs in PARTS:
        out = os.path.join(DEPLOY, sub)
        os.makedirs(out, exist_ok=True)
        for f in files:
            src = os.path.join(ROOT, sub, f)
            if not os.path.exists(src):
                sys.exit(f"MISSING: {sub}/{f} — deploy folder NOT written")
            shutil.copy2(src, os.path.join(out, f)); n += 1
        for d in dirs:
            src = os.path.join(ROOT, sub, d)
            if not os.path.isdir(src):
                sys.exit(f"MISSING: {sub}/{d}/ — deploy folder NOT written")
            copy_dir(src, os.path.join(out, d))
            n += sum(len(fs) for _, _, fs in os.walk(os.path.join(out, d)))

    # papers/img/ ships empty: the sample photos are dropped in later and need
    # no rebuild, but the directory has to exist for them to land in.
    os.makedirs(os.path.join(DEPLOY, "papers", "img"), exist_ok=True)

    for extra in ("DEPLOY.txt", os.path.join(".vscode", "sftp.json")):
        src = os.path.join(ROOT, "deploy-files", extra)
        if os.path.exists(src):
            dst = os.path.join(DEPLOY, extra)
            os.makedirs(os.path.dirname(dst), exist_ok=True)
            shutil.copy2(src, dst); n += 1

    fixed = normalise_modes(DEPLOY)
    if fixed:
        print(f"  normalised {fixed} file/directory permissions to 644/755")

    stray = [os.path.relpath(os.path.join(dp, f), DEPLOY)
             for dp, _, fs in os.walk(DEPLOY) for f in fs
             if f == ".htpasswd" or f.endswith(".htpasswd")]
    if stray:
        shutil.rmtree(DEPLOY)
        sys.exit("REFUSING TO BUILD: a password file reached the bundle "
                 f"({', '.join(stray)}). It is shared host-wide — uploading a "
                 "copy locks every gated area. deploy/ has been removed.")

    missing = verify()
    if missing:
        print("\nDEPLOY FOLDER IS INCOMPLETE — do not upload:")
        for page, ref in missing:
            print(f"  {page} needs {ref}, which is not in deploy/")
        sys.exit(1)

    size = sum(os.path.getsize(os.path.join(dp, f))
               for dp, _, fs in os.walk(DEPLOY) for f in fs)
    print(f"\ndeploy/ ready — {n} files, {size/1024:.0f} KB")
    print("Open the deploy folder itself in VS Code, then: SFTP: Upload Folder")


if __name__ == "__main__":
    main()
