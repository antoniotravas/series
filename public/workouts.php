<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();

// modo treinador, sem atleta escolhido: os treinos de todos os atletas
if (coach_mode($user) && empty($_GET['u'])) {
    $athletes = list_athletes((int)$user['id']);
    $allIds = array_map('intval', array_column($athletes, 'id'));
    $pick = (int)($_GET['a'] ?? 0);
    $ids = in_array($pick, $allIds, true) ? [$pick] : $allIds;
    $filters = workout_filters_from_query($_GET);
    $page = max(1, (int)($_GET['page'] ?? 1));
    render('workouts', [
        'title'     => t('w.list.title_athletes'),
        'active'    => 'workouts',
        'athlete'   => $user,
        'isOwner'   => false,
        'coachAll'  => true,
        'athletes'  => $athletes,
        'pick'      => $ids === $allIds ? 0 : $pick,
        'filters'   => $filters,
        'page'      => $page,
        'result'    => list_workouts($ids, $filters, $page),
        'distances' => user_distances($ids),
        'years'     => user_years($ids),
        'locations' => user_locations($ids),
    ]);
    exit;
}

$athlete = viewed_athlete($user);   // o próprio, ou um atleta do treinador (?u=)
$uid = (int)$athlete['id'];
$isOwner = $uid === (int)$user['id'];

$filters = workout_filters_from_query($_GET);
remember_list_query($uid);
$page = max(1, (int)($_GET['page'] ?? 1));
$result = list_workouts($uid, $filters, $page);

render('workouts', [
    'title'     => $isOwner ? t('w.list.title') : t('w.list.title_of', ['name' => $athlete['display_name']]),
    'active'    => $isOwner ? 'workouts' : 'athletes',
    'athlete'   => $athlete,
    'isOwner'   => $isOwner,
    'filters'   => $filters,
    'page'      => $page,
    'result'    => $result,
    'distances' => user_distances($uid),
    'years'     => user_years($uid),
    'locations' => user_locations($uid),
]);
