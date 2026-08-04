<?php

/**
 * LaundryHub Web Deployment & CLI Shell Runner for Shared Hosting
 * SECURITY TOKEN PROTECTED: Requires ?token=laundryhub_deploy_secret_2026
 */

$secretToken = 'laundryhub_deploy_secret_2026';
$providedToken = $_GET['token'] ?? $_POST['token'] ?? '';

if ($providedToken !== $secretToken) {
    http_response_code(403);
    die('<!DOCTYPE html><html><body style="background:#0f172a;color:#f8fafc;font-family:sans-serif;padding:3rem;text-align:center;">
        <h1 style="color:#f43f5e;">403 Access Denied</h1>
        <p style="color:#94a3b8;">Missing or invalid security token query parameter. Pass <code>?token=laundryhub_deploy_secret_2026</code> to access runner.</p>
        </body></html>');
@set_time_limit(300);
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '300');

$baseDir = realpath(__DIR__ . '/..');
chdir($baseDir);

$output = '';
$action = $_GET['action'] ?? '';


function runCommand($cmd, $baseDir)
{
    $fullCmd = "cd " . escapeshellarg($baseDir) . " 2>&1 && " . $cmd . " 2>&1";
    if (function_exists('shell_exec')) {
        return shell_exec($fullCmd);
    } elseif (function_exists('exec')) {
        $lines = [];
        exec($fullCmd, $lines);
        return implode("\n", $lines);
    } elseif (function_exists('passthru')) {
        ob_start();
        passthru($fullCmd);
        return ob_get_clean();
    } else {
        return "ERROR: shell_exec, exec, and passthru functions are disabled on this PHP server environment.";
    }
}

function getComposerCmd($baseDir)
{
    $sysComposer = trim((string) runCommand('which composer || where composer', $baseDir));
    if (!empty($sysComposer) && strpos($sysComposer, 'not found') === false && strpos($sysComposer, 'INFO:') === false) {
        return 'composer';
    }
    
    $pharPath = $baseDir . '/composer.phar';
    if (!file_exists($pharPath)) {
        runCommand('curl -sS https://getcomposer.org/installer | php', $baseDir);
    }
    
    if (file_exists($pharPath)) {
        return 'php composer.phar';
    }
    
    return 'composer';
}

if ($action === 'composer_install') {
    $composer = getComposerCmd($baseDir);
    $output = runCommand("{$composer} install --no-dev --optimize-autoloader", $baseDir);
} elseif ($action === 'composer_update') {
    $composer = getComposerCmd($baseDir);
    $output = runCommand("{$composer} update --no-dev --optimize-autoloader", $baseDir);
} elseif ($action === 'npm_build') {
    $output = runCommand("npm run build || npx vite build", $baseDir);
} elseif ($action === 'migrate') {
    $output = runCommand("php artisan migrate --force", $baseDir);
} elseif ($action === 'db_seed') {
    $output = runCommand("php artisan db:seed --force", $baseDir);
} elseif ($action === 'clear_cache') {
    $output = runCommand("php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear", $baseDir);
} elseif ($action === 'custom' && !empty($_POST['cmd'])) {
    $output = runCommand($_POST['cmd'], $baseDir);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaundryHub Web Command Runner</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 2rem; }
        .container { max-width: 960px; margin: 0 auto; background: #1e293b; padding: 2.5rem; border-radius: 1rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); border: 1px solid #334155; }
        h1 { color: #38bdf8; margin-top: 0; font-size: 1.75rem; }
        p.subtitle { color: #94a3b8; font-size: 0.95rem; margin-bottom: 2rem; }
        .btn-group { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 2rem; }
        a.btn, button.btn { background: #0284c7; color: white; padding: 0.7rem 1.25rem; border-radius: 0.5rem; text-decoration: none; font-weight: 700; border: none; cursor: pointer; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.4rem; transition: background 0.2s; }
        a.btn:hover, button.btn:hover { background: #0369a1; }
        a.btn-emerald { background: #059669; }
        a.btn-emerald:hover { background: #047857; }
        a.btn-purple { background: #9333ea; }
        a.btn-purple:hover { background: #7e22ce; }
        a.btn-amber { background: #d97706; }
        a.btn-amber:hover { background: #b45309; }
        form { display: flex; gap: 0.5rem; margin-bottom: 2rem; }
        input[type="text"] { background: #0f172a; border: 1px solid #334155; color: #f8fafc; padding: 0.7rem 1rem; border-radius: 0.5rem; flex-grow: 1; font-family: monospace; font-size: 0.9rem; }
        input[type="text"]:focus { outline: none; border-color: #38bdf8; }
        pre { background: #020617; color: #38bdf8; padding: 1.25rem; border-radius: 0.75rem; overflow-x: auto; font-family: monospace; font-size: 0.85rem; border: 1px solid #1e293b; line-height: 1.5; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 LaundryHub Web Deployment & Command Runner</h1>
        <p class="subtitle">Execute Composer, NPM build, Database migrations, and Artisan CLI commands directly from your browser on shared hosting without SSH.</p>
        
        <div class="btn-group">
            <a class="btn btn-purple" href="?token=<?= urlencode($secretToken) ?>&action=composer_install">📦 Composer Install</a>
            <a class="btn btn-emerald" href="?token=<?= urlencode($secretToken) ?>&action=npm_build">⚡ NPM Build (Vite)</a>
            <a class="btn" href="?token=<?= urlencode($secretToken) ?>&action=migrate">🗄️ Artisan Migrate</a>
            <a class="btn" href="?token=<?= urlencode($secretToken) ?>&action=db_seed">🌱 Artisan DB Seed</a>
            <a class="btn btn-amber" href="?token=<?= urlencode($secretToken) ?>&action=clear_cache">🧹 Clear Caches</a>
        </div>

        <form method="POST" action="?token=<?= urlencode($secretToken) ?>&action=custom">
            <input type="text" name="cmd" placeholder="Enter custom command, e.g.: php artisan route:list" required>
            <button type="submit" class="btn">Execute Command</button>
        </form>

        <?php if (!empty($output)): ?>
            <h2>Command Output:</h2>
            <pre><?= htmlspecialchars($output) ?></pre>
        <?php endif; ?>
    </div>
</body>
</html>
