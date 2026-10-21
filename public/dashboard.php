<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];

$pbs = stats_personal_bests($uid);
$pbsByDistance = $pbs;
usort($pbsByDistance, fn($a, $b) => $a['distance_m'] <=> $b['distance_m']);
$distances = array_map('intval', array_column($pbs, 'distance_m'));
$progDist = (int)($_GET['d'] ?? 0);
if (!in_array($progDist, $distances, true)) {
    $progDist = $distances[0] ?? 0;
}

render('dashboard', [
    'title'       => t('nav.dashboard'),
    'active'      => 'dashboard',
    'totals'      => stats_totals($uid),
    'streak'      => stats_week_streak($uid),
    'pbs'         => $pbs,
    'pbsByDistance' => $pbsByDistance,
    'weekly'      => stats_weekly_volume($uid),
    'progDist'    => $progDist,
    'progression' => $progDist ? stats_progression($uid, $progDist) : [],
    'recent'      => list_workouts($uid, workout_filters_from_query([]), 1, 5)['rows'],
]);
