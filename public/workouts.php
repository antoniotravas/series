<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
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
