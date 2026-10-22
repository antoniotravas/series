<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
if (!$user['is_coach']) {
    flash('error', t('athletes.not_coach'));
    redirect('profile.php?edit=1');
}
$uid = (int)$user['id'];

render('athletes', [
    'title'    => t('athletes.title'),
    'active'   => 'athletes',
    'athletes' => list_athletes($uid),
    'requests' => array_values(array_filter(coaching_requests($uid), fn($r) => $r['i_am_coach'])),
]);
