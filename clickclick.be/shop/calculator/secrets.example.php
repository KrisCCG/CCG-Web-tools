<?php
/**
 * Click Click — secrets template.
 *
 * Copy to `secrets.php` on the server and fill in real values. The real
 * `secrets.php` is gitignored and must be uploaded once via SFTP after every
 * fresh deploy of /calculator/.
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
    'mollie_key'         => 'test_REPLACE_WITH_KEY',   // live_… in production

    // ── Mollie webhook source IPs (defence-in-depth allowlist) ───────────
    // Verify current list against Mollie docs at deploy time.
    'mollie_webhook_ips' => [
        '52.215.235.193',
        '18.200.207.245',
        '99.81.91.62',
        '3.252.230.111',
    ],

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
