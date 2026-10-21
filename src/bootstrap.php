<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

$configFile = ROOT . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Falta config/config.php (copiar de config.sample.php).');
}
$GLOBALS['config'] = require $configFile;

require ROOT . '/src/helpers.php';

if (config('debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

date_default_timezone_set('Europe/Lisbon');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => config('base_url') . '/',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name('series_sid');
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

require ROOT . '/src/db.php';
require ROOT . '/src/i18n.php';
require ROOT . '/src/auth.php';
require ROOT . '/src/workouts.php';
require ROOT . '/src/import.php';
