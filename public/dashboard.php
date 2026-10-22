<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$athlete = viewed_athlete($user);   // o próprio, ou um atleta do treinador (?u=)
$uid = (int)$athlete['id'];
$isOwner = $uid === (int)$user['id'];

$pbs = stats_personal_bests($uid);
$pbsByDistance = $pbs;
usort($pbsByDistance, fn($a, $b) => $a['distance_m'] <=> $b['distance_m']);
$distances = array_map('intval', array_column($pbs, 'distance_m'));
$progDist = (int)($_GET['d'] ?? 0);
if (!in_array($progDist, $distances, true)) {
    $progDist = $distances[0] ?? 0;
}

render('dashboard', [
    'title'       => $isOwner ? t('nav.dashboard') : t('dash.of', ['name' => $athlete['display_name']]),
    'active'      => $isOwner ? 'dashboard' : 'athletes',
    'athlete'     => $athlete,
    'isOwner'     => $isOwner,
    'totals'      => stats_totals($uid),
    'streak'      => stats_week_streak($uid),
    'pbs'         => $pbs,
    'pbsByDistance' => $pbsByDistance,
    'trained'     => stats_trained_distances($uid),
    'weekly'      => stats_weekly_volume($uid),
    'progDist'    => $progDist,
    'progression' => $progDist ? stats_progression($uid, $progDist) : [],
    'recent'      => list_workouts($uid, workout_filters_from_query([]), 1, 5)['rows'],
    // treinadores: os atletas aparecem no painel de quem treina
    'athletes'    => $isOwner && $user['is_coach'] ? list_athletes($uid) : [],
]);
