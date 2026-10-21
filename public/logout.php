<?php
require __DIR__ . '/../src/bootstrap.php';

if (is_post()) {
    csrf_check();
    logout_user();
    flash('ok', t('auth.logged_out'));
}
redirect('login.php');
