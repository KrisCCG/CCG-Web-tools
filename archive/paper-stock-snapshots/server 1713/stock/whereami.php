<?php
// TEMPORARY helper — tells you the absolute path of this folder.
// 1. Upload this file into the stock/ folder (it's already in the package).
// 2. Open https://clickclick.be/stock/whereami.php in your browser.
// 3. Copy the line it prints and paste it into .htaccess as:
//        AuthUserFile "....../stock/.htpasswd"
// 4. DELETE this file afterwards.
header('Content-Type: text/plain; charset=utf-8');
echo "AuthUserFile \"" . __DIR__ . "/.htpasswd\"\n";
