# Click Click — Paper Stock — deploy guide

A small internal web app to track paper stock, reordering, and offer feasibility.
Runs on ordinary PHP shared hosting (same kind that runs your other calculators).
Shared data is stored in one JSON file and protected by a staff login.

---

## 1. What's in this folder (`stock/`)

```
stock/
├── index.html          ← the whole app (single self-contained file)
├── .htaccess           ← staff login (Basic Auth)  ← EDIT one line, see step 3
├── .htpasswd           ← login: CCG / Print
├── api/
│   └── state.php       ← read/write the shared data
└── data/
    ├── .htaccess       ← blocks public access to the data
    └── state.json      ← the live stock data (starts with your 3 papers)
```

## 2. Upload

SFTP the entire `stock/` folder into the web root of **clickclick.be**, next to
your other calculators, so it is reachable at:

```
https://clickclick.be/stock/
```

(Any folder name is fine — `stock`, `paper`, `voorraad`… just keep the inside
structure identical.)

## 3. Set the login file path (one line)

Open `stock/.htaccess` and replace the placeholder in `AuthUserFile` with the
**absolute server path** to `.htpasswd`. On Infomaniak it looks like:

```
AuthUserFile "/home/clients/xxxxxxxxxxxx/sites/clickclick.be/stock/.htpasswd"
```

You can see the absolute path in the Infomaniak File Manager (or run `pwd` over
SSH if available). Save and re-upload that one file.

> If Basic Auth gives trouble on your plan, you can instead enable password
> protection for the `stock/` folder from the Infomaniak Manager UI — then you
> can delete `.htaccess` and `.htpasswd`.

## 4. Make `data/` writable

The app saves to `data/state.json`. Make sure the `data/` folder is writable by
the web server (File Manager → Permissions → `755`, or `775` if needed). If you
ever want a clean start, just empty `state.json` to `{}` — the app reseeds.

## 5. Done

Open https://clickclick.be/stock/ , log in with **CCG / Print**.
Both of you now see and edit the same live stock. Add it to your phone home
screen ("Add to Home Screen") for an app-like icon.

---

## Login

| User | Password |
|------|----------|
| CCG  | Print    |

To change it, regenerate `.htpasswd`. Best (bcrypt):

```
htpasswd -cB .htpasswd CCG
```

The shipped file uses Apache's `{SHA}` scheme, which works everywhere but is
unsalted — fine for an internal tool; switch to `-B` (bcrypt) if you prefer.

## How the data works

- One JSON document holds every paper + the activity log + the open offer.
- Every change in the app POSTs the whole document to `api/state.php`, which
  writes it with a file lock (`flock`). Reads come from the same file on load.
- This is **last-write-wins**: fine for 2–3 people who rarely edit the exact
  same second. If you grow the team or want change-by-change history, move
  storage to SQLite (the API surface stays the same).
- **Automatic backups:** every save first snapshots the previous state into
  `data/backups/` (plus one snapshot per day), and the live file is written
  atomically. See **Backups** below for how to restore.
- The browser also keeps a local backup copy, so if the server is briefly
  unreachable the app still opens with the last data it saw.

## Admin section

The app has an **Admin** tab (desktop). It's locked behind the same password
(**Print**) so the protected actions can't be hit by accident on the floor:

- **Export Excel** — download the full list as `.xlsx`.
- **Reset to sheet values** — restore the original papers from the built-in seed.
- **Delete papers** — remove a line (with confirmation).

Delete lives **only** here now (it was removed from the stock cards). Admin is
not shown on phones. When you want separate per-person logins (e.g. only one
account can reach Admin), add users to `.htpasswd` and we can gate the tab to a
specific user — happy to wire that up.

## The Excel export

The desktop toolbar has **⤓ Export Excel** — it downloads the full list as a
real `.xlsx` for your separate records. It does not touch the live data.

## Backups

Two layers, so a bad save, an accidental **Reset to sheet values**, or a deleted
paper is always recoverable.

**1. Automatic, on the server — nothing to remember.**
`api/state.php` keeps history by itself:

- before every save it copies the previous `data/state.json` to
  `data/backups/state-YYYY-MM-DD_HHMMSS.json` (keeps the last 300 saves);
- it also keeps one snapshot per day in `data/backups/daily/` (~1 year);
- the live file is written *temp-then-rename*, so a crash mid-save can never
  leave a corrupt `state.json`.

`data/backups/` lives inside `data/`, which is already blocked from the web.

**To restore a backup** (in FileZilla):

1. *(Safety first)* download the current `stock/data/state.json` to your PC, in
   case you pick the wrong one.
2. Open `stock/data/backups/` and find the snapshot from **just before** the
   mistake — files are named `state-YYYY-MM-DD_HHMMSS.json` in **server time**.
   *(Or use `stock/data/backups/daily/state-YYYY-MM-DD.json` to roll a whole day
   back.)*
3. **Download** that file to your PC and **rename** it to `state.json`.
4. **Upload** it into `stock/data/`, choosing **Overwrite** when prompted.
5. **Reload the app** — you're back to that point.

(No app button is involved — restoring is just putting an old `state.json` back.)

**2. Off-server, weekly — protects against the whole server failing.**
Pick whichever is easiest:

- **⤓ Export Excel** every Monday and save it to Google Drive; **or**
- download `data/state.json` via FileZilla into your `Node-CCG` git folder and
  commit it.

How much history to keep is set at the top of `api/state.php`
(`$KEEP_SNAPSHOTS`, `$KEEP_DAILY`).

## Security checklist

No secrets live in this tool, but to keep it from being changed by the wrong
person — or from breaking — confirm:

- [ ] **Staff login is active.** Open `https://clickclick.be/stock/` in a private
  window; it must ask for a password. If it doesn't, `.htaccess` `AuthUserFile`
  still points at the placeholder — set it to the real absolute path (step 3) and
  re-upload.
- [ ] **`data/` is not web-reachable.** Visiting
  `https://clickclick.be/stock/data/state.json` should return 403 Forbidden.
- [ ] **Delete `whereami.php`** from the server after setup — it only exists to
  print the absolute path during install and shouldn't stay live.
- [ ] *(optional)* switch the login to bcrypt: `htpasswd -cB .htpasswd CCG`.
- [ ] keep the app master (`index.html`) in the `Node-CCG` git repo so the tool
  itself can always be restored.
