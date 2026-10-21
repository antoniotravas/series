<?php
require __DIR__ . '/../src/bootstrap.php';

if (is_post()) {
    csrf_check();
    $lang = (string)($_POST['lang'] ?? '');
    set_lang($lang);
    if (($u = current_user()) && isset(LANGS[$lang])) {
        q('UPDATE users SET lang = ? WHERE id = ?', [$lang, $u['id']]);
    }
    $back = (string)($_POST['back'] ?? '');
    if (str_starts_with($back, config('base_url') . '/') && !str_contains($back, '//')) {
        header('Location: ' . $back);
        exit;
    }
}
redirect('');
