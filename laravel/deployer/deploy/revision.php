<?php

// Copied into public/ during deployment to verify the release PHP-FPM actually executes.
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

$path = __DIR__.'/../REVISION';
$revision = is_file($path) ? trim((string) file_get_contents($path)) : '';
if (! preg_match('/^[a-f0-9]{40}$/', $revision)) {
    http_response_code(503);
    exit(1);
}

echo $revision.PHP_EOL;
