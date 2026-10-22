<?php

// Runs DatabaseSeeder only when no user exists yet, so restarts and rolling
// deploys never seed twice.

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\ConsoleOutput;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (DB::table('users')->exists()) {
    echo "Database already has users, skipping demo seed.\n";
    exit(0);
}

echo "Empty database, seeding demo data.\n";
exit(Artisan::call('db:seed', ['--force' => true], new ConsoleOutput));
