<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];
$other = find_user((int)($_GET['id'] ?? 0));
if (!$other) {
    http_response_code(404);
    flash('error', t('net.user_not_found'));
    redirect('network.php');
}
if ((int)$other['id'] === $uid) {
    redirect('profile.php');
}
$oid = (int)$other['id'];
$page = max(1, (int)($_GET['page'] ?? 1));
$filters = workout_filters_from_query([]);
$filters['visibility'] = visible_levels($uid, $oid);

render('user', [
    'title'    => $other['display_name'],
    'active'   => 'network',
    'other'    => $other,
    'rel'      => relation_with($user, $other),
    'isCoach'  => is_coach_of($uid, $oid),
    'page'     => $page,
    'result'   => list_workouts($oid, $filters, $page),
]);
