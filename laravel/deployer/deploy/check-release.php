<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;

try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require __DIR__.'/../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();

    $expectedDatabase = $argv[1] ?? '';
    if (! $app->isProduction() || config('app.debug') || ! config('app.key')) {
        throw new RuntimeException('Production environment, APP_DEBUG=false and APP_KEY are required.');
    }
    $key = config('app.key');
    $key = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
    if (! is_string($key) || ! Encrypter::supported($key, config('app.cipher')) || ! $app->configurationIsCached()) {
        throw new RuntimeException('A valid APP_KEY and release-specific configuration cache are required.');
    }
    if ($expectedDatabase === '' || DB::connection()->getDatabaseName() !== $expectedDatabase) {
        throw new RuntimeException('Database does not match the configured deployment target.');
    }
    DB::select('SELECT 1');
    if (! is_file(public_path('build/manifest.json')) || is_file(public_path('hot'))) {
        throw new RuntimeException('Production assets are missing or a Vite development marker exists.');
    }
    $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true, flags: JSON_THROW_ON_ERROR);
    foreach ($manifest as $entry) {
        foreach (array_merge([$entry['file']], $entry['css'] ?? [], $entry['assets'] ?? []) as $asset) {
            if (! is_file(public_path('build/'.$asset))) {
                throw new RuntimeException('A manifest asset is missing from the release.');
            }
        }
    }
    if (config('queue.default') === 'database' && config('cache.default') !== 'database') {
        throw new RuntimeException('Use the shared database cache for queue restart signals.');
    }
    fwrite(STDOUT, "Release boots with production configuration, database access and built assets.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'Release check failed: '.$exception->getMessage().PHP_EOL);
    exit(1);
}
