<?php
// OPTIONAL helper — only needed if the password prompt does NOT appear after upload.
// It prints the exact absolute path of THIS folder on the server.
//
//   1. Upload this file into /PaperStock/ next to .htaccess and .htpasswd
//   2. Open https://clickclick.be/PaperStock/whereami.php in your browser
//   3. Copy the "AuthUserFile ..." line it prints and paste it over the
//      AuthUserFile line in .htaccess, then re-upload .htaccess
//   4. DELETE this file from the server afterwards (it leaks the server path)
header('Content-Type: text/plain; charset=utf-8');
echo "Folder path : " . __DIR__ . "\n\n";
echo "AuthUserFile " . __DIR__ . "/.htpasswd\n";
