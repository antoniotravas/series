<?php
// Política de privacidade (acessível com ou sem sessão).
require __DIR__ . '/../src/bootstrap.php';

render('privacy', [
    'title'  => current_lang() === 'en' ? 'Privacy policy' : 'Política de privacidade',
    'active' => 'help',
]);
