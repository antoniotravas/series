<?php
require __DIR__ . '/../src/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

$error = null;
if (is_post()) {
    csrf_check();
    $email = (string)($_POST['email'] ?? '');
    if (too_many_attempts()) {
        $error = t('auth.throttled');
    } elseif ($user = attempt_login($email, (string)($_POST['password'] ?? ''))) {
        login_user($user);
        $next = $_SESSION['after_login'] ?? null;
        unset($_SESSION['after_login']);
        // só permitir regressar a caminhos internos
        if (is_string($next) && str_starts_with($next, config('base_url') . '/') && !str_contains($next, '//')) {
            header('Location: ' . $next);
            exit;
        }
        redirect('dashboard.php');
    } else {
        $error = t('auth.bad_login');
    }
    keep_old(['email' => $email]);
}

render('login', ['title' => t('auth.login.title'), 'active' => 'login', 'error' => $error]);
clear_old();
