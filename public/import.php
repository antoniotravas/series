<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];
$error = null;

if (isset($_GET['reset'])) {
    unset($_SESSION['import']);
    redirect('import.php');
}

if (is_post()) {
    csrf_check();

    // 2.º passo: gravar os treinos escolhidos na pré-visualização
    if (isset($_POST['confirm'])) {
        $items = $_SESSION['import'] ?? [];
        $n = 0;
        foreach ((array)($_POST['pick'] ?? []) as $i) {
            $item = $items[(int)$i] ?? null;
            if (!$item || $item['errors']) {
                continue;
            }
            $w = workout_from_input($item['input']);
            if (isset($w['data'])) {
                save_workout($uid, $w['data'], $w['sets']);
                $n++;
            }
        }
        unset($_SESSION['import']);
        flash('ok', t('imp.done', ['n' => $n]));
        redirect('workouts.php');
    }

    // 1.º passo: ler o ficheiro
    $file = $_FILES['file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $error = t('imp.err_upload');
    } elseif ($file['size'] > IMPORT_MAX_BYTES) {
        $error = t('imp.err_size', ['mb' => IMPORT_MAX_BYTES / 1024 / 1024]);
    } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
        $error = t('imp.err_type');
    } else {
        try {
            $items = import_prepare($uid, $file['tmp_name']);
        } catch (Throwable $ex) {
            error_log('Importação: ' . $ex->getMessage());
            $items = null;
        }
        if ($items === null) {
            $error = t('imp.err_read');
        } elseif (!$items) {
            $error = t('imp.err_empty');
        } else {
            $_SESSION['import'] = $items;
            redirect('import.php');
        }
    }
}

render('import', [
    'title'  => t('imp.title'),
    'active' => 'workouts',
    'items'  => $_SESSION['import'] ?? null,
    'error'  => $error,
]);
