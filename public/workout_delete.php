<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];
$ownerId = $uid;
if (is_post()) {
    csrf_check();
    // o dono, ou o treinador que registou o treino
    $w = find_workout_any((int)($_POST['id'] ?? 0));
    if ($w && can_edit_workout($uid, $w)) {
        $ownerId = (int)$w['user_id'];
        delete_workout($ownerId, (int)$w['id']);
        flash('ok', t('w.deleted'));
    }
}
redirect(workouts_list_path($ownerId, $ownerId === $uid));
