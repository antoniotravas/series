<?php
// Ações sobre um ou vários treinos: desativar, reativar ou apagar.
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];

// voltar à página de onde veio (só caminhos internos)
$back = (string)($_POST['back'] ?? '');
if (!str_starts_with($back, config('base_url') . '/') || str_contains($back, '//')) {
    $back = url('workouts.php');
}

if (is_post()) {
    csrf_check();
    $ids = array_map('intval', (array)($_POST['ids'] ?? []));
    $action = $_POST['action'] ?? '';
    if (!$ids) {
        flash('error', t('w.bulk.none'));
    } elseif ($action === 'deactivate' || $action === 'activate') {
        $n = set_workouts_active($uid, $ids, $action === 'activate');
        flash('ok', t('w.bulk.' . $action . 'd', ['n' => $n]));
    } elseif ($action === 'delete') {
        foreach ($ids as $id) {
            delete_workout($uid, $id);
        }
        flash('ok', t('w.bulk.deleted', ['n' => count($ids)]));
        // o treino apagado deixou de existir: não voltar à sua página
        if (str_contains($back, 'workout.php')) {
            $back = url('workouts.php');
        }
    }
}
header('Location: ' . $back);
exit;
