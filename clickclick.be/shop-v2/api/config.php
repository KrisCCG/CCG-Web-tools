<?php
// Shop v2 switches. Upload this file again after changing it.
return [
    // true  = payments go to Mollie (needs api/secrets.php with a Mollie TEST key: test_...).
    // false = test shop: carts are checked and priced, but no payment is created and no order saved.
    'payments_enabled' => true,

    // false = only Mollie TEST keys are accepted (no real money). Set true ONLY at go-live,
    // when this shop replaces /shop/ and the login is removed.
    'allow_live' => false,
];
