<?php
// Lista as corridas do Strava para importar para um treino (filtros por ano e mês).
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];
$connected = strava_account($uid) !== null;
$page = max(1, (int)($_GET['page'] ?? 1));

$thisYear = (int)date('Y');
$year = (int)($_GET['year'] ?? 0);
$month = (int)($_GET['month'] ?? 0);
if ($year < 2000 || $year > $thisYear) {
    $year = 0;
}
if (!$year || $month < 1 || $month > 12) {
    $month = 0;
}

// intervalo em timestamps (hora de Lisboa, como no resto da app)
$range = [];
if ($year) {
    $range = $month
        ? ['after' => mktime(0, 0, 0, $month, 1, $year), 'before' => mktime(0, 0, 0, $month + 1, 1, $year)]
        : ['after' => mktime(0, 0, 0, 1, 1, $year), 'before' => mktime(0, 0, 0, 1, 1, $year + 1)];
}

$result = ['runs' => [], 'next' => $page, 'more' => false];
if ($connected) {
    try {
        $result = strava_list_runs($uid, $page, $range);
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
    'runs'       => $result['runs'],
    'more'       => $result['more'],
    'next'       => $result['next'],
    'year'       => $year,
    'month'      => $month,
    'years'      => range($thisYear, 2000),
]);
