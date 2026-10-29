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

// O GPX só serve para preencher o formulário: nunca é guardado no servidor.
try {
    $r = gpx_detect($file['tmp_name']);
} catch (Throwable $ex) {
    error_log('Deteção GPX: ' . $ex->getMessage());
    @unlink($file['tmp_name']);
    $fail(t('error.gpx_invalid'));
}
@unlink($file['tmp_name']);
if (!$r['sets']) {
    $fail(t('gpx.none'));
}

echo json_encode(gpx_prefill_payload($r, (int)$user['id']), JSON_UNESCAPED_UNICODE);
