<?php
// Shop v2 — minimal secrets file (Mollie TEST mode).
//
// 1. Replace test_PASTE_YOUR_MOLLIE_TEST_KEY_HERE with your Mollie TEST API key
//    (Mollie dashboard > Developers > API keys > "Test API key", starts with test_).
//    Keep the quotes and the comma.
// 2. Save/rename this file to exactly:  secrets.php
// 3. Upload it to  /sites/clickclick.be/shop-v2/api/   (next to pay.php).
// 4. Do NOT keep a copy inside any folder that gets uploaded or backed up publicly.
//
// shop-v2 refuses live_ keys on purpose (see api/config.php 'allow_live').

return [
    'mollie_key' => 'test_PASTE_YOUR_MOLLIE_TEST_KEY_HERE',
    // ── Outgoing mail through our own Infomaniak mailbox (stops mails landing in spam) ──
    // Without these two lines the shop falls back to PHP mail(), which is not DKIM-signed.
    // 'smtp_user' must be the full address of a real mailbox: shop@clickclickgraphics.com.
    // Use an app password created for it in the Infomaniak Manager (not your login password).
    'smtp_user' => 'shop@clickclickgraphics.com',
    'smtp_pass' => 'PASTE_APP_PASSWORD_HERE',
    // 'smtp_host' => 'mail.infomaniak.com',   // default
    // 'smtp_port' => 465,                     // default (SSL)

];
