<?php

/**
 * LaundryHub Direct PHP Deployment Runner (No shell_exec / No SSH required)
 * Boots Laravel Framework directly in pure PHP to run migrations and seeders.
 */



@set_time_limit(300);
@ini_set('memory_limit', '512M');

$baseDir = realpath(__DIR__ . '/..');

echo "<pre>";
echo "==================================================\n";
echo "🚀 LaundryHub Direct PHP Deployment Runner\n";
echo "==================================================\n";
echo "Deployment Root: $baseDir\n\n";

// Change to the project root directory
chdir($baseDir);

try {
    // 1. Boot Laravel Framework directly in PHP (without shell_exec)
    if (!file_exists($baseDir . '/repositories/lundaryHubV1/vendor/autoload.php')) {
        die("❌ ERROR: vendor/autoload.php not found. Ensure the vendor directory is pulled to cPanel.\n");
    }

    require $baseDir . '/repositories/lundaryHubV1/vendor/autoload.php';
    $app = require_once $baseDir . '/repositories/lundaryHubV1/bootstrap/app.php';

    // Bootstrap the console kernel
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    echo "✅ Laravel Framework booted successfully!\n\n";

    // 2. Verify Database Connection
    echo "====================================\n";
    echo "Checking Database Connection\n";
    echo "====================================\n";
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $dbName = config('database.connections.mysql.database');
        $dbHost = config('database.connections.mysql.host');
        echo "✅ Connected to MySQL Database: '$dbName' on '$dbHost'\n\n";
    } catch (\Throwable $dbEx) {
        echo "❌ Database Connection Failed: " . $dbEx->getMessage() . "\n";
        echo "💡 Check .env on cPanel: ensure DB_DATABASE and DB_USERNAME have cPanel prefixes (e.g. cpaneluser_dbname) and privileges are granted.\n\n";
        die("Deployment halted due to Database Connection failure.\n");
    }

    // 3. Run Database Migrations
    echo "====================================\n";
    echo "Running: Database Migrations\n";
    echo "====================================\n";
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $migOutput = \Illuminate\Support\Facades\Artisan::output();
        echo $migOutput ? $migOutput : "Migrations completed / Nothing to migrate.\n";
    } catch (\Throwable $e) {
        echo "❌ Migration Error: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 4. Run Database Seeders
    echo "====================================\n";
    echo "Running: Database Seeders\n";
    echo "====================================\n";
    try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        $seedOutput = \Illuminate\Support\Facades\Artisan::output();
        echo $seedOutput ? $seedOutput : "Seeders completed successfully.\n";
    } catch (\Throwable $e) {
        echo "❌ Seeder Error: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 5. Clear Caches
    echo "====================================\n";
    echo "Running: Clear & Optimize Caches\n";
    echo "====================================\n";
    try {
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        echo "✅ All application caches cleared successfully.\n";
    } catch (\Throwable $e) {
        echo "Cache Notice: " . $e->getMessage() . "\n";
    }
    echo "\n";

    echo "====================================\n";
    echo "🎉 Deployment Completed Successfully!\n";
    echo "====================================\n";

} catch (\Throwable $e) {
    echo "❌ Deployment Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "</pre>";
