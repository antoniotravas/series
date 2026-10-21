<?php
require __DIR__ . '/../src/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

$errors = [];
if (is_post()) {
    csrf_check();
    $res = register_user($_POST);
    if (isset($res['user'])) {
        login_user($res['user']);
        flash('ok', t('auth.welcome', ['name' => $res['user']['display_name']]));
        redirect('dashboard.php');
    }
    $errors = $res['errors'];
    keep_old($_POST);
}

render('register', ['title' => t('auth.register.title'), 'errors' => $errors]);
clear_old();
