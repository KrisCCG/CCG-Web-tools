# Incident-response runbook

One-page runbook for the most likely operational/security incidents on the flyer / print calculator. Print this and keep it next to the shop till.

---

## 1. Suspected breach (someone has access they shouldn't)

**Isolate first, investigate after.**

1. **Rotate Mollie API key** — login to Mollie dashboard → Developers → API keys → revoke the current `live_*` key, create a new one. Paste the new value into `secrets.php` on the server (SFTP).
2. **Rotate kDrive token** — Infomaniak admin → My profile → Security → Application passwords. Delete the current one, create a new one scoped to "kDrive WebDAV." Update `secrets.php`.
3. **Rotate backup passphrase** — generate a new one (`php -r 'echo bin2hex(random_bytes(32)), "\n";'`), update `secrets.php`. ⚠️ Existing encrypted backups on kDrive can still be opened with the OLD passphrase — keep the old one in your password manager until those archives age out (30 days).
4. **Invalidate all live CSRF tokens** — `rm /calculator/orders/.csrf-tokens.json`. Customers mid-flow will get a "session expired" message and have to reload.
5. **Preserve evidence** — copy `audit.log`, `paid_orders.log`, and any recent `error_log` somewhere safe before doing anything that might overwrite them.
6. **Notify customers** within 72h if PII was exposed (GDPR Article 33/34). Use the `customer_email` list from `paid_orders.log`.
7. **Notify Belgian DPA** (Autorité de protection des données) if PII confirmed exposed: <https://www.dataprotectionauthority.be/notification-of-a-data-breach>

---

## 2. Lost / corrupted `/calculator/orders/` directory

The Mollie payment history is the authoritative record — every paid transaction lives in Mollie's dashboard regardless of what happened on our side.

1. **Restore from kDrive** — see `BACKUP.md`. Pick the most recent archive that predates the corruption.
2. **Reconcile against Mollie** — login to Mollie → Payments. For every paid transaction since the backup, check that a matching `{invoice}.json` exists in the restored `/orders/`. For gaps, manually rebuild the JSON from the Mollie metadata (the original `pay.php` writes the same metadata it stores locally to Mollie's `metadata` field, so it's all there).
3. **Notify affected customers** if any artwork is unrecoverable (it lives only in our server, never in Mollie). They'll need to re-upload — apologise and offer a freebie.

---

## 3. Mollie webhook stopped firing

Customer paid but the order JSON is still `status: pending`.

1. `thankyou.php` already does a fallback Mollie API poll — confirm the customer at least saw the success page.
2. Manually re-fire the webhook from Mollie's dashboard: Payments → click the payment → "Send webhook again." That will re-trigger `webhook.php` and finalize the order normally.
3. If many webhooks are failing: check `tail -100 /calculator/orders/audit.log | grep webhook` for `webhook.bad_ip` events — Mollie may have changed their source IPs; update `mollie_webhook_ips` in `secrets.php`.

---

## 4. Disk full

```bash
du -sh /calculator/orders/*
```

In order of "most likely culprit":

1. `orders/temp/` — abandoned uploads. Run `php /calculator/cleanup.php` manually; the 1-in-100 probabilistic cleanup may have missed if traffic is low.
2. `orders/*/artwork/` — finalized customer artwork. Old paid orders' artwork can be deleted once printed. The `retention.php` weekly sweep purges artwork 6 months after `paid_at` automatically.
3. `audit.log` — rare, but if rate-limiting kicked in heavily it can grow. Rotate by hand: `mv audit.log audit-$(date +%Y-W%V).log && touch audit.log`.
4. `.ratelimit/` — tiny per-IP counters. Delete the whole directory if it ever grows weird; it self-recreates.

---

## 5. Backup script keeps failing

Check `/calculator/orders/backup.log` for the failure line.

- `secrets.php missing or incomplete` — one of `backup_passphrase`, `kdrive_user`, `kdrive_token`, `kdrive_endpoint` is empty in `secrets.php`. Refill from `secrets.example.php`.
- `phardata failed: …` — usually disk space in `/tmp`; sometimes file permissions inside `/orders/`.
- `openssl_encrypt failed` — extremely rare; means PHP's openssl extension is missing or `backup_passphrase` is somehow invalid. Re-check `secrets.php`.
- `kDrive PUT http=401/403` — the `kdrive_token` in `secrets.php` is invalid or revoked. Regenerate (see incident #1 step 2).
- `kDrive PUT http=404` — the `clickclick-backups/` folder no longer exists in kDrive. Recreate it via the web UI.
- `kDrive PUT http=507` — kDrive is out of quota. Free space in kDrive web UI or upgrade.
- `verify FAILED` — file uploaded but the size mismatched. Retry; if persistent, the upload may be truncating — check Infomaniak's max-request size for WebDAV.

---

## 6. Customer requests data deletion (GDPR right-to-erasure)

```bash
# On the server, basic-auth login required:
curl --user "kris:<password>" \
  "https://clickclick.be/calculator/admin/erase.php?email=customer@example.com&dry=1"
```

The `&dry=1` returns a preview (matched/redacted counts) without writing anything. Drop `&dry=1` to actually redact. The endpoint:

- Replaces PII fields in every matching `{invoice}.json` with `[redacted-YYYY-MM]`
- Deletes any remaining artwork files for the customer
- Logs the action to `audit.log` with the admin username

The PDF invoices (`Receipt_*.pdf`, `Invoice_*.pdf`) are **not** auto-deleted because they're the legal tax record (7-year retention). If the customer specifically asks for the PDF to also go, delete manually after confirming with the accountant.

**Backup archives on kDrive** still contain the customer's data until they age out at 30 days. GDPR explicitly permits short backup-rotation retention windows, so the default behaviour is fine — you can tell the customer "your data will be fully gone within 30 days as old backup snapshots rotate out." If the customer insists on immediate full deletion across backups, manually delete the matching archive files from kDrive — easiest path is the kDrive web UI; alternatively `curl -X DELETE -u 'user:token' https://.../clickclick-backups/clickclick-orders-….tar.gz.enc` using the credentials from `secrets.php`.

Reply to the customer within 30 days (GDPR), confirming the redaction and listing what was kept and why.

---

## Quick reference — log locations

| Log | Path | What it tells you |
|---|---|---|
| Audit | `/calculator/orders/audit.log` | Every payment/upload/webhook event, PII-redacted |
| Paid orders | `/calculator/orders/paid_orders.log` | One line per successful payment |
| Backup | `/calculator/orders/backup.log` | Nightly backup run results |
| Cleanup | `/calculator/orders/cleanup.log` | 03:00 temp-upload sweep |
| Retention | `/calculator/orders/retention.log` | Weekly PII redaction |
| PHP errors | Infomaniak admin → Logs → PHP error | Server-side exceptions |
