<?php
// Muda entre modo treinador e modo atleta (só para treinadores).
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
if (is_post() && $user['is_coach']) {
    csrf_check();
    set_mode((string)($_POST['mode'] ?? 'coach'));
}
redirect(home_path($user));
