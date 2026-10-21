<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$w = q('SELECT id, workout_date, gpx_file FROM workouts WHERE id = ? AND user_id = ?', [(int)($_GET['id'] ?? 0), $user['id']])->fetch();
$path = $w && $w['gpx_file'] ? ROOT . '/storage/gpx/' . basename($w['gpx_file']) : null;
if (!$path || !is_file($path)) {
    http_response_code(404);
    exit;
}
header('Content-Type: application/gpx+xml');
header('Content-Disposition: attachment; filename="treino-' . $w['workout_date'] . '.gpx"');
header('Content-Length: ' . filesize($path));
readfile($path);
