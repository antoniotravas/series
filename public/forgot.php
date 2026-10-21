<?php
require __DIR__ . '/../src/bootstrap.php';

$sent = false;
if (is_post()) {
    csrf_check();
    if (too_many_attempts()) {
        flash('error', t('auth.throttled'));
    } else {
        $email = (string)($_POST['email'] ?? '');
        record_failed_login($email); // conta para o limite, evita abuso de envio de emails
        create_password_reset($email);
        $sent = true;
    }
}

render('forgot', ['title' => t('auth.forgot.title'), 'sent' => $sent]);
