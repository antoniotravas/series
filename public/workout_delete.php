<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
if (is_post()) {
    csrf_check();
    delete_workout((int)$user['id'], (int)($_POST['id'] ?? 0));
    flash('ok', t('w.deleted'));
}
redirect('workouts.php');
