<?php
require __DIR__ . '/../src/bootstrap.php';

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$reset = find_password_reset($token);
$errors = [];

if ($reset && is_post()) {
    csrf_check();
    $errors = validate_password((string)($_POST['password'] ?? ''), (string)($_POST['password_confirm'] ?? ''));
    if (!$errors) {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($_POST['password'], PASSWORD_DEFAULT), $reset['user_id']]);
        q('UPDATE password_resets SET used_at = NOW() WHERE id = ?', [$reset['id']]);
        flash('ok', t('auth.reset.done'));
        redirect('login.php');
    }
}

render('reset', ['title' => t('auth.reset.title'), 'reset' => $reset, 'token' => $token, 'errors' => $errors]);
