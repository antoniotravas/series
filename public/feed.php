<?php
// Próxima página do feed da rede (fragmento HTML pedido pelo app.js ao descer a página).
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$offset = max(0, (int)($_GET['offset'] ?? 0));
$rows = activity_feed((int)$user['id'], FEED_PAGE + 1, $offset);

// nunca guardar em cache (o servidor podia devolver sempre a mesma página)
header('Cache-Control: no-store, private');
header('X-Feed-More: ' . (count($rows) > FEED_PAGE ? '1' : '0'));
view('partials/workout_rows', ['rows' => array_slice($rows, 0, FEED_PAGE), 'showAuthor' => true]);
// o mesmo sinal também no HTML, caso algum proxy retire o cabeçalho
echo '<p hidden data-feed-has-more="' . (count($rows) > FEED_PAGE ? '1' : '0') . '"></p>';
