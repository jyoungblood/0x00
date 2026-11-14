<?php

use Dotenv\Dotenv;

require_once __DIR__.'/../vendor/autoload.php';

try {
    $path = getenv('DEPLOY_CREDENTIALS_FILE') ?: __DIR__.'/../.env.deploy';
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

    return $credentials;
} catch (Throwable) {
    $message = 'Configure DEPLOY_DB_USERNAME and DEPLOY_DB_PASSWORD in .env.deploy (chmod 600), or set DEPLOY_CREDENTIALS_FILE to a private dotenv file.';
    if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
        fwrite(STDERR, $message.PHP_EOL);
        exit(1);
    }
    throw new RuntimeException($message);
}
