# Backup & restore — Click Click order data

`calculator/backup.php` runs nightly at 04:00 (Infomaniak Task Scheduler — HTTPS, basic-auth gated) and uploads an **AES-256-CBC-encrypted** tarball of `/calculator/orders/` to Infomaniak kDrive. Last 30 daily archives are kept on kDrive.

Encryption is pure-PHP (`openssl_encrypt` with PBKDF2 key derivation) because Infomaniak's web PHP disables `exec()` — so no `gpg` shell-out is possible. The output format is byte-compatible with `openssl enc -aes-256-cbc -pbkdf2`, so any Mac/Linux with `openssl` installed can decrypt without needing PHP.

## What's backed up

Everything in `/calculator/orders/` **except** `backup.log` (it's the log of the run itself) and `.ratelimit/` (transient rate-limit counters). That covers:

- `{YYYY}-{NNN}.json` — order metadata (incl. customer PII)
- `{YYYY}-{NNN}/artwork/*` — uploaded artwork files
- `paid_orders.log` — append-only payment log
- `audit.log` (or its weekly rotation `audit-YYYY-WW.log`) — events / incident-response trail
- `invoice_counter_{YYYY}.txt` — invoice numbering
- `Receipt_*.pdf` / `Invoice_*.pdf` — generated PDFs

## Where it goes

`secrets.php → kdrive_endpoint`:
`https://1775931.connect.kdrive.infomaniak.com/clickclick-backups/`

Files are named `clickclick-orders-YYYY-MM-DD_HHMMSS.tar.gz.enc`.

## Restore procedure

You'll need:
- An archive downloaded from kDrive (`clickclick-orders-*.tar.gz.enc`)
- The `backup_passphrase` value from `secrets.php` (or your 1Password copy)
- Any Mac/Linux with `openssl` and `tar` installed (built into macOS — no extra installs)

```bash
# 1. Download the archive locally — kDrive web UI → right-click → Download.

# 2. Decrypt (openssl prompts for the passphrase):
openssl enc -d -aes-256-cbc -pbkdf2 \
    -in clickclick-orders-2026-05-14_040000.tar.gz.enc \
    -out orders.tar.gz
# Paste the backup_passphrase when prompted.

# 3. Verify the archive before clobbering anything:
tar tzf orders.tar.gz | head
tar tzf orders.tar.gz | wc -l     # rough sanity check on file count

# 4. Restore — pick ONE of these:

#    a) Full clobber (when /orders/ is empty or destroyed)
cd /home/clients/<id>/sites/clickclick.be/calculator/orders/
tar xzf /path/to/orders.tar.gz

#    b) Selective restore (just one invoice)
cd /tmp && mkdir restore && cd restore
tar xzf /path/to/orders.tar.gz '2026-042.json' '2026-042/'
scp -r 2026-042* kris@clickclick.be:/home/clients/<id>/sites/clickclick.be/calculator/orders/

# 5. Re-set perms if needed (Apache must be able to read):
chmod -R u+rw,g+r,o+r /home/clients/<id>/sites/clickclick.be/calculator/orders/
```

If `openssl enc` complains about "bad magic number," the archive isn't encrypted with the PBKDF2 format — that would be a sign of corruption during download.

## Manual run (testing)

**Via Infomaniak Task Scheduler** (the production path):
- Manager → Hosting → clickclick.be → Web → Task Scheduler → "Click Click — backup orders to kDrive" → **Run now**.
- Check `backup.log` afterwards by SFTP-pulling `/calculator/orders/backup.log`.

**Via SSH** (if your hosting plan has SSH enabled):
```bash
ssh kris@clickclick.be
php /home/clients/<id>/sites/clickclick.be/calculator/backup.php
tail -20 /home/clients/<id>/sites/clickclick.be/calculator/orders/backup.log
```

A clean run logs (sizes will vary):

```
2026-05-14T04:00:01+00:00 start
2026-05-14T04:00:02+00:00 tar+gz ok size=4825614B
2026-05-14T04:00:03+00:00 encrypt ok size=4825632B
2026-05-14T04:00:08+00:00 kdrive put ok http=201 file=clickclick-orders-2026-05-14_040001.tar.gz.enc
2026-05-14T04:00:09+00:00 verify remote=4825632B local=4825632B match=yes
2026-05-14T04:00:10+00:00 retention sweep removed=1
2026-05-14T04:00:10+00:00 done
```

## What if kDrive is down at run time?

The script logs the failure and emails `alert_email` (set in `secrets.php`). The local `/orders/` directory is untouched, so nothing is lost — just rerun the script later, or wait for the next 04:00 cron.

## What if I lose the passphrase?

The encrypted archives become unrecoverable. Treat `backup_passphrase` like a Mollie API key:

- Don't store it only in `secrets.php` on the live server
- Keep a second copy in a password manager (1Password / Bitwarden) under the Click Click Graphics vault
- Rotate it if exposure is suspected (next backup will use the new one; old archives stay readable with the old one until they age out at 30 days)

## Second tier (Infomaniak built-in)

Infomaniak shared hosting includes automatic daily snapshots kept for 4 weeks. Those are independent of our backup script and recover the entire site, not just `/orders/`. Restore via the Infomaniak admin panel → Backup → Restore. Use this if the kDrive copy is also unrecoverable, or for non-`/orders/` data (code, `secrets.php`, `.htpasswd`).
