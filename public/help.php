<?php
// Manual de utilização com casos de uso (acessível com ou sem sessão).
require __DIR__ . '/../src/bootstrap.php';

render('help', [
    'title'   => t('help.title'),
    'active'  => 'help',
]);
