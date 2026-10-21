<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];

$filters = workout_filters_from_query($_GET);
$page = max(1, (int)($_GET['page'] ?? 1));
$result = list_workouts($uid, $filters, $page);

render('workouts', [
    'title'     => t('w.list.title'),
    'active'    => 'workouts',
    'filters'   => $filters,
    'page'      => $page,
    'result'    => $result,
    'distances' => user_distances($uid),
    'years'     => user_years($uid),
    'locations' => user_locations($uid),
]);
