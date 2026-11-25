# Shop v2 — backup, retention, erasure (ported 21 Oct 2026)

Local notes. **Do not upload this file** (like README.txt).

| Script | URL (now → after go-live) | What | When |
|---|---|---|---|
| `api/backup.php` | `/shop-v2/api/backup.php` → `/shop/api/backup.php` | AES-256 encrypted tarball of `api/orders/` to kDrive, keeps 30 days | daily 04:00 |
| `api/retention.php` | `…/retention.php` (`?dry=1` = report only) | artwork deleted after 180 days; personal data redacted after 2 years; payment log redacted | weekly, Sunday 05:00 |
| `api/cleanup.php` | `…/cleanup.php` | removes unpaid staged uploads (`orders/temp/`) | as in the old shop |
| `api/admin/erase.php` | `…/admin/erase.php?email=…` (`&dry=1` first) | GDPR erasure for one customer | on request |

All four are behind a login (cron realm for the first three, admin realm for erase). They live in `api/.htaccess` / `api/admin/.htaccess`.

## One-time setup on the server
1. `api/secrets.php` needs: `backup_passphrase`, `kdrive_endpoint`, `kdrive_user`, `kdrive_token`, `alert_email`
   (the same values as the old shop's secrets.php; see `secrets.example.php`). Keep a second copy of the
   passphrase in the password manager. Without it the archives cannot be opened.
2. Infomaniak Manager → Hosting → Web → Task Scheduler: add three jobs, each "This URL is password protected"
   with the **cron** login:
   - `https://clickclick.be/shop-v2/api/backup.php`    daily 04:00
   - `https://clickclick.be/shop-v2/api/retention.php` weekly Sunday 05:00
   - `https://clickclick.be/shop-v2/api/cleanup.php`   same schedule as the old shop's cleanup job
3. Run backup once by hand ("Run now", or open the URL in the browser with the cron login). The page prints the
   log lines; a good run ends with `verify … match=yes` and `done`. Then check the file on kDrive.
4. Run retention once with `?dry=1` first and read the report.
5. **At go-live** (shop-v2 becomes /shop/): change the three Task Scheduler URLs.

## What is backed up
Everything in `api/orders/` except `temp/` (unpaid staging uploads), `.ratelimit/`, `.csrf-tokens.json`, `backup.log`:
order JSON (incl. customer data), `{invoice}/artwork/…`, receipt PDFs, `paid_orders.log`, the rotated `audit-YYYY-WW.log`,
invoice counters, `calc_log.jsonl`. Archive name: `clickclick-shop-orders-YYYY-MM-DD_HHMMSS.tar.gz.enc` (backups made before 22 Oct 2026 are named `clickclick-shop-v2-orders-…`).

## Restore
```bash
# 1. download the archive from kDrive
openssl enc -d -aes-256-cbc -pbkdf2 -in clickclick-shop-orders-2026-10-22_040001.tar.gz.enc -out orders.tar.gz   # asks the passphrase
tar tzf orders.tar.gz | head                       # check before restoring
# 2a. everything:   cd <web root>/shop-v2/api/orders/ && tar xzf /path/to/orders.tar.gz
# 2b. one invoice:  tar xzf orders.tar.gz '2026-042.json' '2026-042/'   then copy the two items back
```

## Differences from the old shop's scripts
- Artwork of cart orders sits in per-item folders (`artwork/items/{id}/`): retention and erasure now delete recursively
  (the old scripts left those files behind).
- `TEST-…` order numbers are recognised.
- `paid_orders.log` has an extra TEST/LIVE column; both formats are handled. Erasure now also redacts the customer's log rows.
- Uploaded file names are redacted too (they can contain a person's name).
- Backup leaves out the unpaid staging uploads (up to 100 MB each).

## Not covered (decide separately)
- Receipt PDFs (`orders/Receipt_*.pdf`) hold name and address and are kept: they are the tax document.
  **Decision 21 Oct 2026 (Kris): keep the receipts for the time being.** The privacy page says personal data is replaced
  after 2 years and invoices are kept in the accounting software; the server copies are an exception until this is revisited.
  If it stays that way, the privacy page wording should say so.
- Orders that were never paid (status open/failed) keep their personal data until someone deletes them: neither retention nor cleanup touches them.
