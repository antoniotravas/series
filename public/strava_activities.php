<?php
// Lista as corridas do Strava para importar para um treino.
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];
$connected = strava_account($uid) !== null;
$page = max(1, (int)($_GET['page'] ?? 1));

$runs = [];
if ($connected) {
    try {
        $runs = strava_list_runs($uid, $page);
    } catch (Throwable $ex) {
        error_log('Strava: ' . $ex->getMessage());
        flash('error', t('strava.error'));
    }
}

render('strava', [
    'title'      => t('strava.title'),
    'active'     => 'workouts',
    'connected'  => $connected,
    'configured' => strava_configured(),
    'runs'       => $runs,
    'page'       => $page,
]);
