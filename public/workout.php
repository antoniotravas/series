<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$w = find_workout((int)$user['id'], (int)($_GET['id'] ?? 0));
if (!$w) {
    http_response_code(404);
    flash('error', t('w.not_found'));
    redirect('workouts.php');
}

$pbs = [];
foreach (stats_personal_bests((int)$user['id'], 100) as $pb) {
    $pbs[(int)$pb['distance_m']] = (float)$pb['best'];
}

render('workout', [
    'title'  => $w['title'] ?: sets_summary($w['sets']),
    'active' => 'workouts',
    'w'      => $w,
    'pbs'    => $pbs,
]);
