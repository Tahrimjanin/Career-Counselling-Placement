<?php
// Safe local bootstrap: preserve existing settings and encryption keys.
$root = dirname(__DIR__);
$backend = $root.'/ccp-api';
foreach (['pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'zip', 'dom'] as $extension) {
    if (!extension_loaded($extension)) { fwrite(STDERR, "Enable PHP extension: $extension in the loaded php.ini\n"); exit(1); }
}
$env = $backend.'/.env';
if (!file_exists($env)) {
    copy(file_exists($backend.'/.env.txt') ? $backend.'/.env.txt' : $backend.'/.env.example', $env);
}
$text = file_get_contents($env);
if (!preg_match('/^APP_KEY=/m', $text)) { file_put_contents($env, rtrim($text)."\nAPP_KEY=\n"); }
if (!preg_match('/^APP_KEY=(.*)$/m', $text, $match) || trim(trim($match[1]), chr(34).chr(39)) === '') {
    chdir($backend);
    passthru(escapeshellarg(PHP_BINARY).' artisan key:generate', $code);
    if ($code !== 0) { exit($code); }
}
$web = $root.'/ccp-web';
if (!file_exists($web.'/.env')) { copy($web.'/.env.example', $web.'/.env'); }
echo "Environment prepared. Existing database settings and keys are preserved.\n";
