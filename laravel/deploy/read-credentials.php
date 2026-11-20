<?php

use Dotenv\Dotenv;

try {
    require_once __DIR__.'/../vendor/autoload.php';
    $path = __DIR__.'/../.env.deploy';
    if (! is_file($path) || ! is_readable($path) || (fileperms($path) & 0077) !== 0) {
        throw new RuntimeException;
    }
    $values = Dotenv::parse(file_get_contents($path));
    $credentials = [
        'username' => $values['DEPLOY_DB_USERNAME'] ?? '',
        'password' => $values['DEPLOY_DB_PASSWORD'] ?? '',
    ];
    foreach ($credentials as $value) {
        if (! is_string($value) || $value === '' || $value === 'CHANGE_ME') {
            throw new RuntimeException;
        }
    }

    // Only the migration SSH pipeline requests output. Preflight stays silent.
    if (($argv[1] ?? '') === '--payload') {
        echo base64_encode(json_encode($credentials, JSON_THROW_ON_ERROR)), PHP_EOL;
    }
} catch (Throwable) {
    fwrite(STDERR, "Configure DEPLOY_DB_USERNAME and DEPLOY_DB_PASSWORD in local .env.deploy (chmod 600), and run composer install locally.\n");
    exit(1);
}
