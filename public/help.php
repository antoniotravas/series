<?php
// Manual de utilização com casos de uso (acessível com ou sem sessão).
require __DIR__ . '/../src/bootstrap.php';

$user = current_user();

render('help', [
    'title'   => t('help.title'),
    'active'  => 'help',
    'isAdmin' => $user && $user['is_admin'],
]);
