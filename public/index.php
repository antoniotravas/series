<?php
require __DIR__ . '/../src/bootstrap.php';

if (current_user()) {
    redirect(home_path());
}

render('home');
