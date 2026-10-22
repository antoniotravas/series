<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];
$w = find_workout_any((int)($_GET['id'] ?? 0));
if (!$w || !can_view_workout($uid, $w)) {
    http_response_code(404);
    flash('error', t('w.not_found'));
    redirect('workouts.php');
}
$ownerId = (int)$w['user_id'];
$isOwner = $ownerId === $uid;
$owner = $isOwner ? $user : find_user($ownerId);
$full = $isOwner || is_coach_of($uid, $ownerId);

// recordes do dono (só para quem tem acesso a todos os seus treinos)
$pbs = [];
if ($full) {
    foreach (stats_personal_bests($ownerId, 100) as $pb) {
        $pbs[(int)$pb['distance_m']] = (float)$pb['best'];
    }
}

render('workout', [
    'title'   => $w['title'] ?: sets_summary($w['sets']),
    'active'  => $isOwner ? 'workouts' : ($full ? 'athletes' : 'network'),
    'w'       => $w,
    'pbs'     => $pbs,
    'isOwner' => $isOwner,
    'owner'   => $owner,
    'back'    => $isOwner ? 'workouts.php' : ($full ? 'workouts.php?u=' . $ownerId : 'user.php?id=' . $ownerId),
]);
