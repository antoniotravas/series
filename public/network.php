<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];
$term = trim((string)($_GET['q'] ?? ''));
$coaching = coaching_requests($uid);

render('network', [
    'title'       => t('net.title'),
    'active'      => 'network',
    'term'        => $term,
    'results'     => mb_strlen($term) >= 2 ? search_users($uid, $term) : null,
    'incoming'    => connection_requests_in($uid),
    'outgoing'    => connection_requests_out($uid),
    'coachIn'     => array_values(array_filter($coaching, fn($r) => $r['incoming'])),
    'coachOut'    => array_values(array_filter($coaching, fn($r) => !$r['incoming'])),
    'connections' => list_connections($uid),
    'coaches'     => list_coaches($uid),
    'feed'        => activity_feed($uid),
]);
