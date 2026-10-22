<?php
// Próxima página do feed da rede (fragmento HTML pedido pelo app.js ao descer a página).
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$offset = max(0, (int)($_GET['offset'] ?? 0));
$rows = activity_feed((int)$user['id'], FEED_PAGE + 1, $offset);

header('X-Feed-More: ' . (count($rows) > FEED_PAGE ? '1' : '0'));
view('partials/workout_rows', ['rows' => array_slice($rows, 0, FEED_PAGE), 'showAuthor' => true]);
