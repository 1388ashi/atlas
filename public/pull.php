<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

$config = [
    'key' => 'backend',
];

if (!isset($_GET['backend']) && $_GET['backend'] === $config['key']) {
    header('HTTP/1.0 403 Forbidden');
    die('Forbidden');
}

chdir('../');

if (str_contains($_SERVER['HTTP_ACCEPT'], 'html')) {
    echo '<pre>';
}
echo shell_exec('git reset --hard') . PHP_EOL;
echo exec('git diff') . PHP_EOL;
echo shell_exec('git pull https://khadem:3v5qM2d7RzFwaH5@ugit.app/back-end/shopit.git');
if (str_contains($_SERVER['HTTP_ACCEPT'], 'html')) {
    echo '</pre>';
}
