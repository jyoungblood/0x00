<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\BufferedOutput;

try {
    require __DIR__.'/../vendor/autoload.php';
    $credentials = json_decode(base64_decode(trim(stream_get_contents(STDIN)), true), true, flags: JSON_THROW_ON_ERROR);
    foreach (['username', 'password'] as $key) {
        if (! is_string($credentials[$key] ?? null) || $credentials[$key] === '') {
            throw new RuntimeException('Missing migration credentials.');
        }
    }

    $app = require __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();
    $connection = config('database.default');
    $database = config('database.connections.'.$connection);
    $expectedDatabase = $argv[1] ?? '';
    if (! $app->isProduction() || config('app.debug') || ! $app->configurationIsCached()
        || $expectedDatabase === '' || ($database['database'] ?? null) !== $expectedDatabase
        || ($database['driver'] ?? null) !== 'mysql'
        || ! empty($database['url']) || isset($database['read']) || isset($database['write'])) {
        throw new RuntimeException('Expected cached production configuration with a single MySQL connection.');
    }

    // Override this CLI process only; the web user's cached configuration stays untouched.
    $database['username'] = $credentials['username'];
    $database['password'] = $credentials['password'];
    config(['database.connections.'.$connection => $database]);
    DB::purge($connection);
    $output = new BufferedOutput;
    $status = $kernel->call('migrate', ['--force' => true, '--no-interaction' => true], $output);
    fwrite(STDOUT, str_replace(array_values($credentials), '[redacted]', $output->fetch()));
    exit($status);
} catch (Throwable) {
    fwrite(STDERR, "Migration failed. Check the deployment credentials, database grants, release configuration and pending migrations. Exception details suppressed to protect credentials.\n");
    exit(1);
}
