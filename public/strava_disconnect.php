<?php
// Desliga a conta Strava (só por POST, com token CSRF).
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
if (is_post()) {
    csrf_check();
    strava_disconnect((int)$user['id']);
    flash('ok', t('strava.disconnected'));
}
redirect('strava_activities.php');
