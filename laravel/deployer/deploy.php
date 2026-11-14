<?php

namespace Deployer;

require 'recipe/laravel.php';

// Per-site settings. The reusable tasks live in deploy/laravel.php.
host('production')
    ->setHostname('203.0.113.10')
    ->setRemoteUser('example')
    ->setPort(2200)
    ->setForwardAgent(false)
    ->setSshArguments(['-o StrictHostKeyChecking=yes', '-o BatchMode=yes'])
    ->setDeployPath('/home/example/app');

set('branch', 'main');
set('bin/php', '/opt/cpanel/ea-php83/root/usr/bin/php');
set('bin/composer', '{{bin/php}} -d allow_url_fopen=1 /home/example/bin/composer');
set('public_url', 'https://example.com');
set('expected_database', 'example_laravel_production');
set('queue_worker', false);
set('public_root', '/home/example/public_html');

// Keep account-managed PHP settings and certificate validation files across releases.
add('shared_files', ['public/.user.ini', 'public/php.ini']);
add('shared_dirs', ['public/.well-known']);

require __DIR__.'/deploy/laravel.php';
