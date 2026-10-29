<?php
// Retorno do Strava: troca o código de autorização por tokens e guarda-os.
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];

$expected = $_SESSION['strava_state'] ?? '';
unset($_SESSION['strava_state']);
if ($expected === '' || !hash_equals($expected, (string)($_GET['state'] ?? ''))) {
    flash('error', t('strava.error'));
    redirect('strava_activities.php');
}
if (isset($_GET['error']) || empty($_GET['code'])) {
    flash('error', t('strava.denied'));
    redirect('strava_activities.php');
}

$tokens = strava_exchange_code((string)$_GET['code']);
if (!$tokens) {
    flash('error', t('strava.error'));
    redirect('strava_activities.php');
}

strava_save_tokens($uid, $tokens, (int)($tokens['athlete']['id'] ?? 0) ?: null);
flash('ok', t('strava.connected'));
redirect('strava_activities.php');
