<?php
// Recebe um GPX (formulário de treino) e devolve as repetições detetadas em JSON.
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
header('Content-Type: application/json; charset=utf-8');

$fail = function (string $msg): never {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
};

if (!is_post()) {
    $fail(t('error.gpx_upload'));
}
csrf_check();

$file = $_FILES['gpx'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > GPX_MAX_BYTES) {
    $fail(t('error.gpx_upload'));
}
$head = file_get_contents($file['tmp_name'], false, null, 0, 2048);
if ($head === false || stripos($head, '<gpx') === false) {
    $fail(t('error.gpx_invalid'));
}

try {
    $r = gpx_detect($file['tmp_name']);
} catch (Throwable $ex) {
    error_log('Deteção GPX: ' . $ex->getMessage());
    $fail(t('error.gpx_invalid'));
}
if (!$r['sets']) {
    $fail(t('gpx.none'));
}

echo json_encode([
    'ok'         => true,
    'date'       => $r['date'],
    'start_time' => $r['start_time'],
    'temperature_c' => $r['temperature_c'],
    'summary'    => t('gpx.found', ['s' => sets_summary($r['sets'])])
                  . ($r['temperature_c'] !== null ? ' ' . t('gpx.temp', ['t' => $r['temperature_c']]) : ''),
    'sets'       => $r['sets'],
    'reps'       => array_map(fn($x) => [
        'label' => fmt_distance($x['distance_m']) . ' · ' . fmt_time((float)$x['seconds'], 0)
                 . ' · ' . t('gpx.gps', ['m' => $x['gps_m']])
                 . ($x['hr'] ? ' · ' . t('gpx.hr', ['n' => $x['hr'], 'max' => $x['hr_max']]) : ''),
        'work'  => $x['work'],
    ], $r['reps']),
], JSON_UNESCAPED_UNICODE);
