<?php
/**
 * Click Click — secrets template.
 *
 * Copy to `secrets.php` on the server and fill in real values. The real
 * `secrets.php` is gitignored and must be uploaded once via SFTP after every
 * fresh deploy of /shop-v2/api/.
 *
 * Loaded from: pay.php, webhook.php, thankyou.php, backup.php, retention.php,
 *              admin/erase.php, csrf.php.
 *
 * Tip — generate the backup_passphrase with:
 *   php -r 'echo bin2hex(random_bytes(32)), "\n";'
 *
 * The kdrive_token is an *app-specific password*, not your Infomaniak login —
 * create one at https://manager.infomaniak.com → My profile → Security →
 * Application passwords. Scope it to "kDrive WebDAV" only.
 */

declare(strict_types=1);

return [
    // ── Payment provider ─────────────────────────────────────────────────
    'mollie_key'         => 'test_REPLACE_WITH_KEY',   // shop-v2 accepts test_… keys only (see config.php)

    // ── Mollie webhook source IPs (defence-in-depth allowlist) ───────────
    // Verify current list against Mollie docs at deploy time.
    // Leave this OUT (or empty) while testing: Mollie's addresses can change, and every webhook is
    // verified through Mollie's API anyway. Add it back at go-live if you want the extra filter.
    // 'mollie_webhook_ips' => [ ... ],

    // ── Outgoing mail through our own Infomaniak mailbox (stops mails landing in spam) ──
    // Without these two lines the shop falls back to PHP mail(), which is not DKIM-signed.
    // 'smtp_user' must be the full address of a real mailbox: shop@clickclickgraphics.com.
    // Use an app password created for it in the Infomaniak Manager (not your login password).
    'smtp_user' => 'shop@clickclickgraphics.com',
    'smtp_pass' => 'PASTE_APP_PASSWORD_HERE',
    // 'smtp_host' => 'mail.infomaniak.com',   // default
    // 'smtp_port' => 465,                     // default (SSL)

    // ── Backup target (Infomaniak kDrive over WebDAV) ────────────────────
    'backup_passphrase'  => 'REPLACE_WITH_LONG_RANDOM_STRING',
    'kdrive_user'        => 'kris@clickclickgraphics.com',
    'kdrive_token'       => 'REPLACE_WITH_APP_PASSWORD',
    // Endpoint shape (replace <account> + <user>):
    //   https://<account>.kdrive.infomaniak.com/remote.php/dav/files/<user>/clickclick-backups/
    'kdrive_endpoint'    => 'https://REPLACE.kdrive.infomaniak.com/remote.php/dav/files/REPLACE/clickclick-backups/',

    // ── Operational ──────────────────────────────────────────────────────
    'alert_email'        => 'kris@clickclickgraphics.com',  // backup failures go here
    'admin_email'        => 'kris@clickclickgraphics.com',  // erasure-request audit recipient
];
